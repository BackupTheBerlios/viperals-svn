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

require_once($site_file_root.'includes/forums/functions_admin.php');

/*
$sections = array(0 => array (
    'module_id' => '9',
    'module_title' => 'MAIN',
    'module_filename' => 'main',
    'module_subs' => 'front
forum_view
topic_view
post_details',
    'module_acl' => 'acl_m_',
  ),
  1 => array (
    'module_id' => '10',
    'module_title' => 'QUEUE',
    'module_filename' => 'queue',
    'module_subs' => 'unapproved_topics
unapproved_posts
reports',
    'module_acl' => 'acl_m_approve',
));

foreach  ($sections as $row)
{
	$selected = ($row['module_filename'] == 'kk') ?  true : false;

	// Get the localised lang string if available, or make up our own otherwise
	$module_lang = 'MCP_' . $row['module_title'];

	$_CLASS['core_template']->assign_vars_array('mcp_section', array(
		'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : ucfirst(str_replace('_', ' ', strtolower($row['module_title']))),
		'S_SELECTED'	=> $selected, 
		'U_TITLE'		=> generate_link()
	));

	if ($selected)
	{
		$module_id = $row['module_id'];
		$module_name = $row['module_filename'];

		if ($row['module_subs'])
		{
			$submodules_ary = explode("\n", $row['module_subs']);

			foreach ($submodules_ary as $submodule_title)
			{
				$submodule_title = trim($submodule_title);

				if (!$submodule_title)
				{
					continue;
				}
				
			
				// Only show those rows we are able to access
				if (($submodule_title == 'post_details' && !$post_id) || ($submodule_title == 'topic_view' && !$topic_id) || ($submodule_title == 'forum_view' && !$forum_id))
				{
					continue;
				}
				

				$suffix = ($post_id) ? "&amp;p=$post_id" : '';
				$suffix .= ($topic_id) ? "&amp;t=$topic_id" : '';
				$suffix .= ($forum_id) ? "&amp;f=$forum_id" : '';

				$selected = ($submodule_title == 'kll') ? true : false;

				// Get the localised lang string if available, or make up our own otherwise
				$module_lang = strtoupper($module_type . '_' . $module_name . '_' . $submodule_title);

				$_CLASS['core_template']->assign_vars_array("{$module_type}_subsection", array(
					'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : ucfirst(str_replace('_', ' ', strtolower($module_lang))),
					'S_SELECTED'	=> $selected,
					'ADD_ITEM'		=> $this->add_menu_item($row['module_filename'], $submodule_title),
					'U_TITLE'		=> generate_link($module_url . '&amp;i=' . $module_id . '&amp;mode=' . $submodule_title . $suffix))
				);

			}
		}
	}
}

//$_CLASS['core_blocks']->load_blocks();
$_CLASS['core_blocks']->blocks_loaded = true;

$data = array(
	'block_title'		=> 'Forum Administration',
	'block_position'	=> BLOCK_LEFT,
	'block_file'		=> 'block-forums_mcp.php',
);

$_CLASS['core_blocks']->add_block($data);
*/

// Add Item to Submodule Title
function add_menu_item($module_name, $mode)
{
	global $_CLASS;

	if ($module_name != 'queue')
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

if (!$_CLASS['auth']->acl_get('m_'))
{
	trigger_error('YOUR_NO_MODERATOR');
}

$mode	= get_variable('mode', 'REQUEST', 'front');
$action = get_variable('action', 'REQUEST');
$quick_mod = isset($_REQUEST['quickmod']);

if ($mode === 'approve' || $mode === 'disapprove')
{
	$module = 'queue';
}

if ($action == 'merge_select')
{
	$mode = 'forum_view';
}

// Topic view modes
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

if (!$quick_mod)
{
	switch ($mode)
	{
		case 'front':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_front.php');
			script_close(false);
			//$this->display($_CLASS['core_user']->lang['MCP'], 'mcp_front.html');
		break;

		case 'forum_view':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_forum.php');
			script_close(false);
		break;
		
		case 'topic_view':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_topic.php');
			script_close(false);
		break;
			
		case 'post_details':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_post.php');
			
			mcp_post_details($id, $mode, $action, $url);
			
			$this->display($_CLASS['core_user']->lang['MCP'], 'mcp_post.html');
			script_close(false);
		break;
	}

	//script_close(false);
}

require_once(SITE_FILE_ROOT.'includes/forums/mcp/mcp_main.php');
	
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

		if (empty($topic_ids))
		{
			trigger_error('NO_TOPIC_SELECTED');
		}

		change_topic_type($mode, $topic_ids);
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

	return $rowset;
}

// Get simple post data
function get_post_data($post_ids, $acl_list = false)
{
	global $_CLASS;

	$rowset = array();

	$sql = 'SELECT p.*, u.*, t.*, f.*
		FROM ' . FORUMS_POSTS_TABLE . ' p LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON (f.forum_id = p.forum_id),
		' . USERS_TABLE . ' u, ' . FORUMS_TOPICS_TABLE . ' t
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
		if (!$acl_list || $_CLASS['auth']->acl_get($acl_list, $row['forum_id']))
		{
			$forum_ids[] = $row['forum_id'];
			$ids[] = $row[$sql_id];
		}
	}
	$_CLASS['core_db']->free_result($result);

	return empty($ids) ? false : array_unique($forum_ids);
}

// REMOVE

function build_hidden_fields($array)
{
	return generate_hidden_fields($array);
}
?>