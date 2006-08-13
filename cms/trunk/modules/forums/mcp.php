<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// $Id: mcp.php,v 1.64 2004/09/01 15:47:43 psotfx Exp $
//
// FILENAME  : mcp.php 
// STARTED   : Mon May 5, 2003
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    die;
}

require_once SITE_FILE_ROOT.'includes/forums/functions_admin.php';

// Add Item to Submodule Title
function add_menu_item($module_name, $mode)
{
	global $_CLASS;

	if ($module_name !== 'queue')
	{
		return '';
	}

	$forum_id = request_var('f', 0);
	if ($forum_id && $_CLASS['auth']->acl_get('m_approve', $forum_id))
	{
		$forum_list = array($forum_id);
	}
	else
	{
		$forum_list = get_forum_list('m_approve');
	}

	switch ($mode)
	{
		case 'unapproved_topics':
			$sql = 'SELECT COUNT(*) AS total
				FROM ' . FORUMS_TOPICS_TABLE . '
				WHERE forum_id IN (' . implode(', ', $forum_list) . ')
					AND topic_approved = 0';
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			return ($row['total']) ? (int) $row['total'] : $_CLASS['core_user']->lang['NONE'];
		break;

		case 'unapproved_posts':

			$sql = 'SELECT COUNT(*) AS total
					FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_TOPICS_TABLE . ' t 
					WHERE p.forum_id IN (' . implode(', ', $forum_list) . ')
						AND p.post_approved = 0
						AND t.topic_id = p.topic_id
						AND t.topic_first_post_id <> p.post_id';
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			return ($row['total']) ? (int) $row['total'] : $_CLASS['core_user']->lang['NONE'];
		break;
	}
}

$_CLASS['core_user']->add_img();
$_CLASS['core_user']->add_lang('mcp');

// Only Moderators can go beyond this point
if (!$_CLASS['core_user']->is_user)
{
	if ($_CLASS['core_user']->is_bot)
	{
		redirect(generate_link('Forums'));
	}
	
	login_box(array('admin_login' => true, 'full_login' => false, 'explain' => $_CLASS['core_user']->lang['LOGIN_EXPLAIN_MCP']));
}

$i	= get_variable('i', 'REQUEST');
$mode	= $i ? $i : get_variable('mode', 'REQUEST', 'front');
$quick_mod = isset($_REQUEST['quickmod']);

$post_id = get_variable('p', 'REQUEST', 0);
$topic_id = get_variable('t', 'REQUEST', 0);
$forum_id = get_variable('f', 'REQUEST', 0);
$user_id = get_variable('u', 'REQUEST', 0);
$username = get_variable('username', 'REQUEST', '');

$action = (isset($_REQUEST['action']) && is_array($_REQUEST['action'])) ? get_variable('action', 'REQUEST', false, 'array') : get_variable('action', 'REQUEST');

if (is_array($action))
{
	list($action, ) = each($action);
}

if ($mode == 'topic_logs')
{
	$id = 'logs';
	$quickmod = false;
}

if ($mode === 'approve' || $mode === 'disapprove')
{
	$mode = 'queue';
}

if (in_array($mode, array('split', 'split_all', 'split_beyond', 'merge', 'merge_posts')))
{
	$_REQUEST['action'] = $action = $mode;
	$mode = 'topic_view';
	$quick_mod = false;
}

// Forum view modes
if (in_array($mode, array('resync')))
{
	$_REQUEST['action'] = $action = $mode;
	$mode = 'forum_view';
	$quick_mod = false;
}

if ($post_id)
{
	// We determine the topic and forum id here, to make sure the moderator really has moderative rights on this post
	$sql = 'SELECT topic_id, forum_id
		FROM ' . FORUMS_POSTS_TABLE . "
		WHERE post_id = $post_id";

	$result = $_CLASS['core_db']->query($sql);
	$_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$topic_id = (int) $row['topic_id'];
	$forum_id = (int) ($row['forum_id']) ? $row['forum_id'] : $forum_id;
}

if ($topic_id && !$forum_id)
{
	$sql = 'SELECT forum_id
		FROM ' . FORUMS_TOPICS_TABLE . "
		WHERE topic_id = $topic_id";
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$forum_id = (int) $row['forum_id'];
}

// If the user doesn't have any moderator powers (globally or locally) he can't access the mcp
if (!$_CLASS['forums_auth']->acl_get('m_'))//acl_getf_global
{
	// Except he is using one of the quickmod tools for users
	$user_quickmod_actions = array(
		'lock'			=> 'f_user_lock',
		'unlock'		=> 'f_user_lock',
		'make_sticky'	=> 'f_sticky',
		'make_announce'	=> 'f_announce',
		'make_global'	=> 'f_announce',
		'make_normal'	=> array('f_announce', 'f_sticky')
	);

	$allow_user = false;
	if ($quickmod && isset($user_quickmod_actions[$action]) && !$_CLASS['forums_auth']->acl_gets($user_quickmod_actions[$action], $forum_id))
	{
		$topic_info = get_topic_data(array($topic_id));
		if ($topic_info[$topic_id]['topic_poster'] == $user->data['user_id'])
		{
			$allow_user = true;
		}
	}

	if (!$allow_user)
	{
		trigger_error('NOT_AUTHORIZED');
	}
}

$_CLASS['core_template']->assign_array(array(
	'S_USER_LOGGED_IN'		=> true,
	'S_DISPLAY_PM'			=> false,
	'S_DISPLAY_SEARCH'		=> false,
	'S_DISPLAY_MEMBERLIST'	=> false,
	'S_DISPLAY_JUMPBOX'		=> false,
	'S_TIMEZONE'			=> '',

	'LAST_VISIT_DATE'		=> '',
	'PAGINATION'			=> '',
	'PAGINATION_ARRAY'		=> '',
	'CURRENT_TIME'			=> ''
));
		
// if the user cannot read the forum he tries to access then we won't allow mcp access either
if ($forum_id && !$_CLASS['forums_auth']->acl_get('f_read', $forum_id))
{
	trigger_error('NOT_AUTHORIZED');
}

if (!$quick_mod)
{
	switch ($mode)
	{
		case 'main':
		case 'front':
			require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_front.php';
			script_close(false);
		break;

		case 'forum_view':
			require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_forum.php';
			script_close(false);
		break;
		
		case 'topic_view':
			require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_topic.php';
			script_close(false);
		break;
			
		case 'post_details':
			require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_post.php';
			script_close(false);
		break;
		
		case 'notes':
			require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_notes.php';
			script_close(false);
		break;
		
		case 'queue':
			require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_queue.php';
			script_close(false);
		break;
	}
	//script_close(false);
}

$mode = ($mode !== 'front') ? $mode : $action;
require_once SITE_FILE_ROOT.'includes/forums/mcp/mcp_main.php';

switch ($mode)
{
	case 'lock':
	case 'unlock':
		$topic_ids = get_topic_ids($quick_mod);

		if (empty($topic_ids))
		{
			trigger_error('NO_TOPIC_SELECTED');
		}

		lock_unlock($mode, $topic_ids);
	break;

	case 'lock_post':
	case 'unlock_post':
		$post_ids = get_post_ids($quick_mod);

		if (empty($post_ids))
		{
			trigger_error('NO_POST_SELECTED');
		}

		lock_unlock($mode, $post_ids);
	break;

	case 'make_announce':
	case 'make_sticky':
	case 'make_global':
	case 'make_normal':
		$topic_ids = get_topic_ids($quick_mod);
		$forum_id = get_variable('f', 'REQUEST');

		if (empty($topic_ids))
		{
			trigger_error('NO_TOPIC_SELECTED');
		}

		change_topic_type($mode, $topic_ids, $forum_id);
	break;

	case 'move':
		$_CLASS['core_user']->add_lang('viewtopic');

		$topic_ids = get_topic_ids($quick_mod);

		if (empty($topic_ids))
		{
			trigger_error('NO_TOPIC_SELECTED');
		}

		mcp_move_topic($topic_ids);
	break;

	case 'fork':
		$_CLASS['core_user']->add_lang('viewtopic');

		$topic_ids = get_topic_ids($quick_mod);

		if (empty($topic_ids))
		{
			trigger_error('NO_TOPIC_SELECTED');
		}

		mcp_fork_topic($topic_ids);
	break;

	case 'delete_topic':
		$_CLASS['core_user']->add_lang('viewtopic');

		$topic_ids = get_topic_ids($quick_mod);

		if (empty($topic_ids))
		{
			trigger_error('NO_TOPIC_SELECTED');
		}

		mcp_delete_topic($topic_ids);
	break;

	case 'delete_post':
		$_CLASS['core_user']->add_lang('posting');

		$post_ids = get_post_ids($quick_mod);

		if (empty($post_ids))
		{
			trigger_error('NO_POST_SELECTED');
		}

		mcp_delete_post($post_ids);
	break;

	default:
		trigger_error("Unknown mode: $mode");
	break;
}

script_close(false);

function get_topic_ids($quick_mod)
{
	$topic_ids = array_unique(get_variable('topic_id_list', 'POST', array(), 'array:int'));

	if (empty($topic_ids))
	{
		if ($topic_ids = get_variable('t', 'REQUEST', false, 'int'))
		{
			$topic_ids = array($topic_ids);
		}
	}

	return $topic_ids;
}

function get_post_ids($quick_mod)
{
	$post_ids = array_unique(get_variable('post_id_list', 'POST', array(), 'array:int'));

	if (empty($post_ids))
	{
		if ($post_ids = get_variable('p', 'REQUEST', false, 'int'))
		{
			$post_ids = array($post_ids);
		}
	}

	return $post_ids;
}

// Get simple topic data
function get_topic_data($topic_ids, $acl_list = false)
{
	global $_CLASS;

	if (empty($topic_ids))
	{
		return array();
	}

	$sql = 'SELECT f.*, t.*
		FROM ' . FORUMS_TOPICS_TABLE . ' t
			LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON (t.forum_id = f.forum_id)
		WHERE t.topic_id IN (' . implode(', ', $topic_ids) . ')';
	$result = $_CLASS['core_db']->query($sql);

	$rowset = array();

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($acl_list && !$_CLASS['auth']->acl_get($acl_list, $row['forum_id']))
		{
			continue;
		}

		$rowset[$row['topic_id']] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	return $rowset;
}

// Get simple post data
function get_post_data($post_ids, $acl_list = false)
{
	global $_CLASS;

	$rowset = array();

	$sql = 'SELECT p.*, u.*, t.*, f.*
		FROM ' . FORUMS_POSTS_TABLE . ' p LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON (f.forum_id = p.forum_id),
		' . CORE_USERS_TABLE . ' u, ' . FORUMS_TOPICS_TABLE . ' t
		WHERE p.post_id IN (' . implode(', ', $post_ids) . ')
			AND u.user_id = p.poster_id
			AND t.topic_id = p.topic_id';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($acl_list && !$_CLASS['auth']->acl_get($acl_list, $row['forum_id']))
		{
			continue;
		}

		if (!$row['post_approved'] && !$_CLASS['auth']->acl_get('m_approve', $row['forum_id']))
		{
			// Moderators without the permission to approve post should at least not see them. ;)
			continue;
		}

		$rowset[$row['post_id']] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	return $rowset;
}

function get_forum_data($forum_id, $acl_list = 'f_list')
{
	global $_CLASS;
	$rowset = array();

	$sql = 'SELECT *
		FROM ' . FORUMS_FORUMS_TABLE . '
		WHERE forum_id ' . ((is_array($forum_id)) ? 'IN (' . implode(', ', $forum_id) . ')' : "= $forum_id");
	$result = $_CLASS['core_db']->query($sql);
		
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($acl_list && !$_CLASS['auth']->acl_get($acl_list, $row['forum_id']))
		{
			continue;
		}
		if ($_CLASS['auth']->acl_get('m_approve', $row['forum_id']))
		{
			$row['forum_topics'] = $row['forum_topics_real'];
		}

		$rowset[$row['forum_id']] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	return $rowset;
}

function mcp_sorting($mode, &$sort_days, &$sort_key, &$sort_dir, &$sort_by_sql, &$sort_order_sql, &$total, $forum_id = 0, $topic_id = 0, $where_sql = 'WHERE')
{
	global $_CLASS;

	$sort_days = request_var('sort_days', 0);
	$min_time = ($sort_days) ? $_CLASS['core_user']->time - ($sort_days * 86400) : 0;

	switch ($mode)
	{
		case 'viewforum':
			$type = 'topics';
			$default_key = 't';
			$default_dir = 'd';
			$sql = 'SELECT COUNT(topic_id) AS total
				FROM ' . FORUMS_TOPICS_TABLE . "
				$where_sql forum_id = $forum_id
					AND topic_type NOT IN (" . POST_ANNOUNCE . ', ' . POST_GLOBAL . ")
					AND topic_last_post_time >= $min_time";

			if (!$_CLASS['auth']->acl_get('m_approve', $forum_id))
			{
				$sql .= 'AND topic_approved = 1';
			}
		break;

		case 'viewtopic':
			$type = 'posts';
			$default_key = 't';
			$default_dir = 'a';
			$sql = 'SELECT COUNT(post_id) AS total
				FROM ' . FORUMS_POSTS_TABLE . "
				$where_sql topic_id = $topic_id
					AND post_time >= $min_time";

			if (!$_CLASS['auth']->acl_get('m_approve', $forum_id))
			{
				$sql .= 'AND post_approved = 1';
			}
		break;

		case 'unapproved_posts':
			$type = 'posts';
			$default_key = 't';
			$default_dir = 'd';
			$sql = 'SELECT COUNT(post_id) AS total
				FROM ' . FORUMS_POSTS_TABLE . "
				$where_sql forum_id IN (" . (($forum_id) ? $forum_id : implode(', ', get_forum_list('m_approve'))) . ')
					AND post_approved = 0
					AND post_time >= ' . $min_time;
		break;

		case 'unapproved_topics':
			$type = 'topics';
			$default_key = 't';
			$default_dir = 'd';
			$sql = 'SELECT COUNT(topic_id) AS total
				FROM ' . FORUMS_TOPICS_TABLE . "
				$where_sql forum_id IN (" . (($forum_id) ? $forum_id : implode(', ', get_forum_list('m_approve'))) . ')
					AND topic_approved = 0
					AND topic_time >= ' . $min_time;
		break;

		case 'reports':
			$type = 'reports';
			$default_key = 'p';
			$default_dir = 'd';
			$limit_time_sql = ($min_time) ? "AND r.report_time >= $min_time" : '';

			if ($topic_id)
			{
				$where_sql .= ' p.topic_id = ' . $topic_id;
			}
			else if ($forum_id)
			{
				$where_sql .= ' p.forum_id = ' . $forum_id;
			}
			else
			{
				$where_sql .= ' p.forum_id IN (' . implode(', ', get_forum_list('m_')) . ')';
			}
			$sql = 'SELECT COUNT(r.report_id) AS total
				FROM ' . FORUMS_REPORTS_TABLE . ' r, ' . FORUMS_POSTS_TABLE . " p
				$where_sql
					AND p.post_id = r.post_id
					$limit_time_sql";
		break;

		case 'viewlogs':
			$type = 'logs';
			$default_key = 't';
			$default_dir = 'd';
			$sql = 'SELECT COUNT(log_id) AS total
				FROM ' . FORUMS_LOG_TABLE . "
				$where_sql forum_id IN (" . (($forum_id) ? $forum_id : implode(', ', get_forum_list('m_'))) . ')
					AND log_time >= ' . $min_time . ' 
					AND log_type = ' . LOG_MOD;
		break;
	}

	$sort_key = request_var('sk', $default_key);
	$sort_dir = request_var('sd', $default_dir);
	$sort_dir_text = array('a' => $_CLASS['core_user']->lang['ASCENDING'], 'd' => $_CLASS['core_user']->lang['DESCENDING']);

	switch ($type)
	{
		case 'topics':
			$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_TOPICS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
			$sort_by_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 'tt' => $_CLASS['core_user']->lang['TOPIC_TIME'], 'r' => $_CLASS['core_user']->lang['REPLIES'], 's' => $_CLASS['core_user']->lang['SUBJECT'], 'v' => $_CLASS['core_user']->lang['VIEWS']);

			$sort_by_sql = array('a' => 't.topic_first_poster_name', 't' => 't.topic_last_post_time', 'tt' => 't.topic_time', 'r' => (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? 't.topic_replies_real' : 't.topic_replies'), 's' => 't.topic_title', 'v' => 't.topic_views');
			$limit_time_sql = ($min_time) ? "AND t.topic_last_post_time >= $min_time" : '';
		break;

		case 'posts':
			$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_POSTS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
			$sort_by_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 's' => $_CLASS['core_user']->lang['SUBJECT']);
			$sort_by_sql = array('a' => 'u.username', 't' => 'p.post_id', 's' => 'p.post_subject');
			$limit_time_sql = ($min_time) ? "AND p.post_time >= $min_time" : '';
			break;

		case 'reports':
			$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_REPORTS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
			$sort_by_text = array('p' => $_CLASS['core_user']->lang['REPORT_PRIORITY'], 'r' => $_CLASS['core_user']->lang['REPORTER'], 't' => $_CLASS['core_user']->lang['REPORT_TIME']);
			$sort_by_sql = array('p' => 'rr.reason_priority', 'r' => 'u.username', 't' => 'r.report_time');
			break;

		case 'logs':
			$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_ENTRIES'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
			$sort_by_text = array('u' => $_CLASS['core_user']->lang['SORT_USERNAME'], 't' => $_CLASS['core_user']->lang['SORT_DATE'], 'i' => $_CLASS['core_user']->lang['SORT_IP'], 'o' => $_CLASS['core_user']->lang['SORT_ACTION']);

			$sort_by_sql = array('u' => 'l.user_id', 't' => 'l.log_time', 'i' => 'l.log_ip', 'o' => 'l.log_operation');
			$limit_time_sql = ($min_time) ? "AND l.log_time >= $min_time" : '';
			break;
	}

	$sort_order_sql = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

	$s_limit_days = $s_sort_key = $s_sort_dir = $sort_url = '';
	gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $sort_url);

	$_CLASS['core_template']->assign_array(array(
		'S_SELECT_SORT_DIR'	=>	$s_sort_dir,
		'S_SELECT_SORT_KEY' =>	$s_sort_key,
		'S_SELECT_SORT_DAYS'=>	$s_limit_days)
	);

	if (($sort_days && $mode != 'viewlogs') || $mode == 'reports' || $where_sql != 'WHERE')
	{
		$result = $_CLASS['core_db']->query($sql);
		$total = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row['total'] : 0;
	}
	else
	{
		$total = -1;
	}
}

function check_ids(&$ids, $table, $sql_id, $acl_list = false)
{
	global $_CLASS;

	if (!is_array($ids) || !$ids)
	{
		return false;
	}

	$sql = "SELECT forum_id, $sql_id FROM $table
		WHERE $sql_id IN (" . implode(', ', $ids) . ')';
	$result = $_CLASS['core_db']->query($sql);

	$ids = array();

// Should make this better
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (!$acl_list || $_CLASS['auth']->acl_gets($acl_list, $row['forum_id']))
		{
			$forum_ids[] = $row['forum_id'];
			$ids[] = $row[$sql_id];
		}
	}
	$_CLASS['core_db']->free_result($result);

	return empty($ids) ? false : array_unique($forum_ids);
}

?>