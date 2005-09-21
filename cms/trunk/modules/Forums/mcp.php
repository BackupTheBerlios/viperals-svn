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

// ---------
// FUNCTIONS
//
class module
{
	var $id = 0;
	var $type;
	var $name;
	var $mode;
	var $url;

	// Private methods, should not be overwritten
	function create($module_type, $module_url, $post_id, $topic_id, $forum_id, $selected_mod = false, $selected_submod = false)
	{
		global $_CLASS, $config;
		
		$sql = 'SELECT module_id, module_title, module_filename, module_subs, module_acl
			FROM ' . FORUMS_MODULES_TABLE . "
			WHERE module_type = '{$module_type}'
				AND module_enabled = 1
			ORDER BY module_order ASC";
		$result = $_CLASS['core_db']->query($sql);

		$i = 0;
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			// Authorisation is required for the basic module
			if ($row['module_acl'])
			{
				$is_auth = false;
				eval('$is_auth = (' . preg_replace(array('#acl_([a-z_]+)#e', '#cfg_([a-z_]+)#e'), array('(int) $_CLASS[\'auth\']->acl_get("\\1", ' . $forum_id . ')', '(int) $config["\\1"]'), trim($row['module_acl'])) . ');');

				// The user is not authorised to use this module, skip it
				if (!$is_auth)
				{
					continue;
				}
			}

			$selected = ($row['module_filename'] == $selected_mod || $row['module_id'] == $selected_mod || (!$selected_mod && !$i)) ?  true : false;

			// Get the localised lang string if available, or make up our own otherwise
			$module_lang = strtoupper($module_type) . '_' . $row['module_title'];

			$_CLASS['core_template']->assign_vars_array($module_type . '_section', array(
				'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : ucfirst(str_replace('_', ' ', strtolower($row['module_title']))),
				'S_SELECTED'	=> $selected, 
				'U_TITLE'		=> generate_link($module_url . '&amp;i=' . $row['module_id']))
			);

			if ($selected)
			{
				$module_id = $row['module_id'];
				$module_name = $row['module_filename'];

				if ($row['module_subs'])
				{
					$j = 0;
					$submodules_ary = explode("\n", $row['module_subs']);
					foreach ($submodules_ary as $submodule)
					{
						$submodule = trim($submodule);
						if (!$submodule)
						{
							continue;
						}

						$submodule = explode(',', $submodule);
						$submodule_title = array_shift($submodule);
						//print_r( $submodule);
						$is_auth = true;
						foreach ($submodule as $auth_option)
						{
							eval('$is_auth = (' . preg_replace(array('#acl_([a-z_]+)#e', '#cfg_([a-z_]+)#e'), array('(int) $_CLASS[\'auth\']->acl_get("\\1", ' . $forum_id . ')', '(int) $config["\\1"]'), trim($auth_option)) . ');');

							if (!$is_auth)
							{
								break;
							}
						}

						if (!$is_auth)
						{
							continue;
						}

						// Only show those rows we are able to access
						if (($submodule_title == 'post_details' && !$post_id) || 
							($submodule_title == 'topic_view' && !$topic_id) ||
							($submodule_title == 'forum_view' && !$forum_id))
						{
							continue;
						}
			
						$suffix = ($post_id) ? "&amp;p=$post_id" : '';
						$suffix .= ($topic_id) ? "&amp;t=$topic_id" : '';
						$suffix .= ($forum_id) ? "&amp;f=$forum_id" : '';
											
						$selected = ($submodule_title == $selected_submod || (!$selected_submod && !$j)) ? true : false;

						// Get the localised lang string if available, or make up our own otherwise
						$module_lang = strtoupper($module_type . '_' . $module_name . '_' . $submodule_title);

						$_CLASS['core_template']->assign_vars_array("{$module_type}_subsection", array(
							'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : ucfirst(str_replace('_', ' ', strtolower($module_lang))),
							'S_SELECTED'	=> $selected,
							'ADD_ITEM'		=> $this->add_menu_item($row['module_filename'], $submodule_title),
							'U_TITLE'		=> generate_link($module_url . '&amp;i=' . $module_id . '&amp;mode=' . $submodule_title . $suffix))
						);

						if ($selected)
						{
							$this->mode = $submodule_title;
						}

						$j++;
					}
				}
			}

			$i++;
		}
		$_CLASS['core_db']->free_result($result);

		if (!$module_id)
		{
			trigger_error('MODULE_NOT_EXIST');
		}
//$_CLASS['core_blocks']->load_blocks();
$_CLASS['core_blocks']->blocks_loaded = true;

		$data = array(
			'block_title'		=> 'Forum Administration',
			'block_position'	=> BLOCK_LEFT,
			'block_file'		=> 'block-forums_mcp.php',
		);

		$_CLASS['core_blocks']->add_block($data);

		$this->type = $module_type;
		$this->id	= $module_id;
		$this->name = $module_name;
		$this->url = 'Forums&amp;file=mcp';
		$this->url .= ($post_id) ? "&amp;p=$post_id" : '';
		$this->url .= ($topic_id) ? "&amp;t=$topic_id" : '';
		$this->url .= ($forum_id) ? "&amp;f=$forum_id" : '';
	}

	function load($type = false, $name = false, $mode = false, $run = true)
	{
		global $site_file_root;

		if ($type)
		{
			$this->type = $type;
		}

		if ($name)
		{
			$this->name = $name;
		}

		if (!class_exists($this->type . '_' . $this->name))
		{
			require($site_file_root."includes/forums/{$this->type}/{$this->type}_{$this->name}.php");
		}
	}

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
}
//
// FUNCTIONS
// ---------

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

$mcp = new module();

// Basic parameter data
$mode	= request_var('mode', '');
$module = request_var('i', '');

// Make sure we are using the correct module
if ($mode == 'approve' || $mode == 'disapprove')
{
	$module = 'queue';
}

$quickmod = isset($_REQUEST['quickmod']);
$action = request_var('action', '');
/*
$action_ary = request_var('action', array('' => 0));

if (!empty($action_ary))
{
	list($action, ) = each($action);
}
unset($action_ary);
*/

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

if (!$quickmod)
{
	$post_id = get_variable('p', 'REQUEST', false, 'int');
	$topic_id = get_variable('t', 'REQUEST', false, 'int');
	$forum_id = get_variable('f', 'REQUEST', false, 'int');

	$url = 'Forums&amp;file=mcp';
	$url .= ($post_id) ? "&amp;p=$post_id" : '';
	$url .= ($topic_id) ? "&amp;t=$topic_id" : '';
	$url .= ($forum_id) ? "&amp;f=$forum_id" : '';

	if ($post_id)
	{
		// We determine the topic and forum id here, to make sure the moderator really has moderative rights on this post
		$sql = 'SELECT topic_id, forum_id
			FROM ' . FORUMS_POSTS_TABLE . "
			WHERE post_id = $post_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$topic_id = (int) $row['topic_id'];
		$forum_id = (int) $row['forum_id'];
	}
	elseif ($topic_id)
	{
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TOPICS_TABLE . "
			WHERE topic_id = $topic_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$forum_id = (int) $row['forum_id'];
	}

	if ($forum_id && !$_CLASS['auth']->acl_get('m_', $forum_id))
	{
		trigger_error('MODULE_NOT_EXIST');
	}

	if (!$forum_id && !$_CLASS['auth']->acl_get('m_'))
	{
		$forum_list = get_forum_list('m_');

		if (empty($forum_list))
		{
			trigger_error('MODULE_NOT_EXIST');
		}

		// We do not check all forums, only the first one should be sufficiant.
		$forum_id = $forum_list[0];
	}

// remove
	// Instantiate module system and generate list of available modules
	$mcp->create('mcp', 'Forums&amp;file=mcp', $post_id, $topic_id, $forum_id, $module);

	switch ($mode)
	{
		case 'front':
			require(SITE_FILE_ROOT.'includes/forums/mcp/mcp_front.php');
			$this->display($_CLASS['core_user']->lang['MCP'], 'mcp_front.html');
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
		FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . ' u, ' . FORUMS_TOPICS_TABLE . ' t
			LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON f.forum_id = p.forum_id
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

	return empty($ids) ? false : $forum_ids;
}

// LITTLE HELPER
//

?>