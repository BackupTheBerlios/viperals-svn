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
	$quickmod = false;
}

// Forum view modes
if (in_array($mode, array('resync')))
{
	$_REQUEST['action'] = $action = $mode;
	$mode = 'forum_view';
	$quickmod = false;
}

if (!$quick_mod)
{
	switch ($mode)
	{
		case 'front':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_front.php');
			//$this->display($_CLASS['core_user']->lang['MCP'], 'mcp_front.html');
		break;

		case 'forum_view':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_forum.php');
		break;
		
		case 'topic_view':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_topic.php');
		break;
			
		case 'post_details':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_post.php');
			
			mcp_post_details($id, $mode, $action, $url);
			
			$this->display($_CLASS['core_user']->lang['MCP'], 'mcp_post.html');
		break;

		default:
			require_once(SITE_FILE_ROOT.'includes/forums/mcp/mcp_main.php');
		break;
	}

	script_close(false);
}

switch ($mode)
{
	case 'lock':
	case 'unlock':
	case 'lock_post':
	case 'unlock_post':
	case 'make_sticky':
	case 'make_announce':
	case 'make_global':
	case 'make_normal':
	case 'fork':
	case 'move':
	case 'delete_post':
	case 'delete_topic':
		require_once(SITE_FILE_ROOT.'includes/forums/mcp/mcp_main.php');
	break;

	default:
		trigger_error("$mode not allowed as quickmod");
	break;
}

script_close(false);

// Build simple hidden fields from array
function build_hidden_fields($field_ary)
{
	$s_hidden_fields = '';

	foreach ($field_ary as $name => $vars)
	{
		if (is_array($vars))
		{
			foreach ($vars as $key => $value)
			{
				$s_hidden_fields .= '<input type="hidden" name="' . $name . '[' . $key . ']" value="' . $value . '" />';
			}
		}
		else
		{
			$s_hidden_fields .= '<input type="hidden" name="' . $name . '" value="' . $vars . '" />';
		}
	}

	return $s_hidden_fields;
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
			LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
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
	$min_time = ($sort_days) ? time() - ($sort_days * 86400) : 0;

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

// LITTLE HELPER
//

?>