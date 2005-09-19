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
// $Id: functions.php,v 1.304 2004/09/17 09:11:32 acydburn Exp $
//
// FILENAME  : functions.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001,2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Error messages just incase we can't get our configs
$config_error = '<center>There is currently a problem with the site<br/>';
$config_error .= 'Please try again later<br /><br />Error Code: DB3</center>';

if (is_null($config = $_CLASS['core_cache']->get('config')))
{
	$config = $cached_config = array();

	$sql = 'SELECT config_name, config_value, is_dynamic
		FROM ' . FORUMS_CONFIG_TABLE;
			
	if (!$result = $_CLASS['core_db']->query($sql))
	{
		trigger_error($config_error, E_USER_ERROR);
	}
	
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (!$row['is_dynamic'])
		{
			$cached_config[$row['config_name']] = $row['config_value'];
		}

		$config[$row['config_name']] = $row['config_value'];
	}
	$_CLASS['core_db']->free_result($result);

	$_CLASS['core_cache']->put('config', $cached_config);

	unset($cached_config);
}
else
{
	$sql = 'SELECT config_name, config_value
		FROM ' . FORUMS_CONFIG_TABLE . '
		WHERE is_dynamic = 1';
	$result = $_CLASS['core_db']->query($sql);
	
	if ($result = $_CLASS['core_db']->query($sql))
	{
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$config[$row['config_name']] = $row['config_value'];
		}
	}
}

function set_config($config_name, $config_value, $is_dynamic = false)
{
	global $_CLASS, $config;

	$sql = 'UPDATE ' . FORUMS_CONFIG_TABLE . "
		SET config_value = '" . $_CLASS['core_db']->escape($config_value) . "'
		WHERE config_name = '" . $_CLASS['core_db']->escape($config_name) . "'";
	$_CLASS['core_db']->query($sql);

	if (!$_CLASS['core_db']->affected_rows() && !isset($config[$config_name]))
	{
		$sql = 'INSERT INTO ' . FORUMS_CONFIG_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'config_name'	=> $config_name,
			'config_value'	=> $config_value,
			'is_dynamic'	=> ($is_dynamic) ? 1 : 0));
		$_CLASS['core_db']->query($sql);
	}

	$config[$config_name] = $config_value;

	if (!$is_dynamic)
	{
		$_CLASS['core_cache']->destroy('config');
	}
}

function set_var(&$result, $var, $type, $multibyte = false)
{
	settype($var, $type);
	$result = $var;

	if ($type == 'string')
	{
		$result = strip_slashes(trim(htmlspecialchars(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $result))));

		if ($multibyte)
		{
			$result = preg_replace('#&amp;(\#[0-9]+;)#', '&\1', $result);
		}
	}
	return $result;
}

/**
* request_var
*
* Used to get passed variable
*/
function request_var($var_name, $default, $multibyte = false)
{
	if (!isset($_REQUEST[$var_name]) || (is_array($_REQUEST[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($_REQUEST[$var_name])))
	{
		return (is_array($default)) ? array() : $default;
	}

	$var = $_REQUEST[$var_name];
	if (!is_array($default))
	{
		$type = gettype($default);
	}
	else
	{
		list($key_type, $type) = each($default);
		$type = gettype($type);
		$key_type = gettype($key_type);
	}

	if (is_array($var))
	{
		$_var = $var;
		$var = array();

		foreach ($_var as $k => $v)
		{
			if (is_array($v))
			{
				foreach ($v as $_k => $_v)
				{
					set_var($k, $k, $key_type);
					set_var($_k, $_k, $key_type);
					set_var($var[$k][$_k], $_v, $type, $multibyte);
				}
			}
			else
			{
				set_var($k, $k, $key_type);
				set_var($var[$k], $v, $type, $multibyte);
			}
		}
	}
	else
	{
		set_var($var, $var, $type, $multibyte);
	}
		
	return $var;
}

function get_userdata($user)
{
	global $_CLASS;

	$sql = 'SELECT *
		FROM ' . USERS_TABLE . '
		WHERE ';
	$sql .= ((is_integer($user)) ? "user_id = $user" : "username = '" .  $_CLASS['core_db']->escape($user) . "'") . " AND user_id <> " . ANONYMOUS;
	$result = $_CLASS['core_db']->query($sql);

	return ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row : false;
}

// Create forum rules for given forum 
function generate_forum_rules(&$forum_data)
{
	global $_CLASS;
	
	if (!$forum_data['forum_rules'] && !$forum_data['forum_rules_link'])
	{
		$_CLASS['core_template']->assign('S_FORUM_RULES', false);
		return;
	}

	if ($forum_data['forum_rules'])
	{
		require_once(SITE_FILE_ROOT.'includes/forums/bbcode.php');
		$bbcode = new bbcode($forum_data['forum_rules_bbcode_bitfield']);
		
		$bbcode->bbcode_second_pass($forum_data['forum_rules'], $forum_data['forum_rules_bbcode_uid']);

		$forum_data['forum_rules'] = smiley_text($forum_data['forum_rules'], !($forum_data['forum_rules_flags'] & 2));
		$forum_data['forum_rules'] = str_replace("\n", '<br />', censor_text($forum_data['forum_rules']));
		unset($bbcode);
	}

	$_CLASS['core_template']->assign_array(array(
		'S_FORUM_RULES'	=> true,
		'U_FORUM_RULES'	=> $forum_data['forum_rules_link'],
		'FORUM_RULES'   => $forum_data['forum_rules'])
	);
}

// Create forum navigation links for given forum, create parent
// list if currently null, assign basic forum info to template
function generate_forum_nav(&$forum_data)
{
	global $_CLASS;

	// Get forum parents
	$forum_parents = get_forum_parents($forum_data);

	// Build navigation links
	foreach ($forum_parents as $parent_forum_id => $parent_data)
	{
		list($parent_name, $parent_type) = array_values($parent_data);

		$_CLASS['core_template']->assign_vars_array('navlinks', array(
			'S_IS_CAT'		=>	($parent_type == FORUM_CAT) ? true : false,
			'S_IS_LINK'		=>	($parent_type == FORUM_LINK) ? true : false,
			'S_IS_POST'		=>	($parent_type == FORUM_POST) ? true : false,
			'FORUM_NAME'	=>	$parent_name,
			'FORUM_ID'		=>	$parent_forum_id,
			'U_VIEW_FORUM'	=>	generate_link('Forums&amp;file=viewforum&amp;f='.$parent_forum_id)
			)
		);
	}

	$_CLASS['core_template']->assign_vars_array('navlinks', array(
		'S_IS_CAT'		=>	($forum_data['forum_type'] == FORUM_CAT) ? true : false,
		'S_IS_LINK'		=>	($forum_data['forum_type'] == FORUM_LINK) ? true : false,
		'S_IS_POST'		=>	($forum_data['forum_type'] == FORUM_POST) ? true : false,
		'FORUM_NAME'	=>	$forum_data['forum_name'],
		'FORUM_ID'		=>  $forum_data['forum_id'],
		'U_VIEW_FORUM'	=>	generate_link('Forums&amp;file=viewforum&amp;f=' . $forum_data['forum_id'])
		)
	);

	$_CLASS['core_template']->assign_array(array(
		'FORUM_ID' 		=> $forum_data['forum_id'],
		'FORUM_NAME'	=> $forum_data['forum_name'],
		'FORUM_DESC'	=> strip_tags($forum_data['forum_desc']))
	);

	return;
}

// Returns forum parents as an array. Get them from forum_data if available, or update the database otherwise
function get_forum_parents(&$forum_data)
{
	global $_CLASS;

	$forum_parents = array();
	
	if ($forum_data['parent_id'])
	{
		if (!$forum_data['forum_parents'])
		{
			$sql = 'SELECT forum_id, forum_name, forum_type
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE left_id < ' . $forum_data['left_id'] . '
					AND right_id > ' . $forum_data['right_id'] . '
				ORDER BY left_id ASC';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$forum_parents[$row['forum_id']] = array($row['forum_name'], (int) $row['forum_type']);
			}
			$_CLASS['core_db']->free_result($result);

			$forum_data['forum_parents'] = serialize($forum_parents);

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
				SET forum_parents = '" . $_CLASS['core_db']->escape($forum_data['forum_parents']) . "'
				WHERE parent_id = " . $forum_data['parent_id'];
			$_CLASS['core_db']->query($sql);
		}
		else
		{
			$forum_parents = unserialize($forum_data['forum_parents']);
		}
	}

	return $forum_parents;
}

// Obtain list of moderators of each forum
function get_moderators($forum_id = false)
{
	global $config, $_CLASS;

	if (!$config['load_moderators'])
	{
		return array();
	}

	$forum_sql = '';

	if ($forum_id)
	{
		$forum_sql = is_array($forum_id) ? 'AND forum_id IN (' . implode(', ', $forum_id) . ')' : 'AND forum_id = ' . $forum_id;
	}

	$sql = 'SELECT *
		FROM ' . FORUMS_MODERATOR_TABLE . "
		WHERE display_on_index = 1
			$forum_sql";
	$result = $_CLASS['core_db']->query($sql);

	$forum_moderators = array();

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$forum_moderators[$row['forum_id']][] = empty($row['user_id']) ? '<a href="' . generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']) . '">' . (isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</a>' : '<a href="' . generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $row['username'] . '</a>';
	}
	$_CLASS['core_db']->free_result($result);

	return $forum_moderators;
}

// User authorisation levels output
function gen_forum_auth_level($mode, $forum_id)
{
	global $_CLASS;

	$rules = array(
		($_CLASS['auth']->acl_get('f_post', $forum_id)) ? $_CLASS['core_user']->lang['RULES_POST_CAN'] : $_CLASS['core_user']->lang['RULES_POST_CANNOT'],
		($_CLASS['auth']->acl_get('f_reply', $forum_id)) ? $_CLASS['core_user']->lang['RULES_REPLY_CAN'] : $_CLASS['core_user']->lang['RULES_REPLY_CANNOT'],
		($_CLASS['auth']->acl_gets(array('f_edit', 'm_edit'), $forum_id)) ? $_CLASS['core_user']->lang['RULES_EDIT_CAN'] : $_CLASS['core_user']->lang['RULES_EDIT_CANNOT'],
		($_CLASS['auth']->acl_gets(array('f_delete', 'm_delete'), $forum_id)) ? $_CLASS['core_user']->lang['RULES_DELETE_CAN'] : $_CLASS['core_user']->lang['RULES_DELETE_CANNOT'],
		($_CLASS['auth']->acl_get('f_attach', $forum_id) && $_CLASS['auth']->acl_get('u_attach', $forum_id)) ? $_CLASS['core_user']->lang['RULES_ATTACH_CAN'] : $_CLASS['core_user']->lang['RULES_ATTACH_CANNOT']
	);

	foreach ($rules as $rule)
	{
		$_CLASS['core_template']->assign_vars_array('rules', array('RULE' => $rule));
	}

	return;
}

function gen_sort_selects(&$limit_days, &$sort_by_text, &$sort_days, &$sort_key, &$sort_dir, &$s_limit_days, &$s_sort_key, &$s_sort_dir, &$u_sort_param)
{
	global $_CLASS;

	$sort_dir_text = array('a' => $_CLASS['core_user']->lang['ASCENDING'], 'd' => $_CLASS['core_user']->lang['DESCENDING']);

	$s_limit_days = '<select name="st">';
	foreach ($limit_days as $day => $text)
	{
		$selected = ($sort_days == $day) ? ' selected="selected"' : '';
		$s_limit_days .= '<option value="' . $day . '"' . $selected . '>' . $text . '</option>';
	}
	$s_limit_days .= '</select>';

	$s_sort_key = '<select name="sk">';
	foreach ($sort_by_text as $key => $text)
	{
		$selected = ($sort_key == $key) ? ' selected="selected"' : '';
		$s_sort_key .= '<option value="' . $key . '"' . $selected . '>' . $text . '</option>';
	}
	$s_sort_key .= '</select>';

	$s_sort_dir = '<select name="sd">';
	foreach ($sort_dir_text as $key => $value)
	{
		$selected = ($sort_dir == $key) ? ' selected="selected"' : '';
		$s_sort_dir .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
	}
	$s_sort_dir .= '</select>';

	$u_sort_param = "st=$sort_days&amp;sk=$sort_key&amp;sd=$sort_dir";

	return;
}

function make_jumpbox($action, $forum_id = false, $select_all = false, $acl_list = false)
{
	global $config, $_CLASS;

	if (!$forum_id)
	{
		$forum_id = request_var('f', 0);
	}

	if (!$config['load_jumpbox'])
	{
		return;
	}

	$sql = 'SELECT forum_id, forum_name, parent_id, forum_type, left_id, right_id
		FROM ' . FORUMS_FORUMS_TABLE . '
		ORDER BY left_id ASC';
	$result = $_CLASS['core_db']->query($sql, 600);

	$right = $padding = 0;
	$padding_store = array('0' => 0);
	$display_jumpbox = false;
	
	// Sometimes it could happen that forums will be displayed here not be displayed within the index page
	// This is the result of forums not displayed at index, having list permissions and a parent of a forum with no permissions.
	// If this happens, the padding could be "broken"

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		///  Work on padding ////
		if ($row['left_id'] < $right)
		{
			$padding++;
			$padding_store[$row['parent_id']] = $padding;
		}
		else if ($row['left_id'] > $right + 1)
		{
			$padding = $padding_store[$row['parent_id']];
		}

		$right = $row['right_id'];
		
		if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
		{
			// Non-postable forum with no subforums, don't display
			continue;
		}

		if (!$_CLASS['auth']->acl_get('f_list', $row['forum_id']))
		{
			// if the user does not have permissions to list this forum skip
			continue;
		}
		
		if ($acl_list && !$_CLASS['auth']->acl_gets($acl_list, $row['forum_id']))
		{
			continue;
		}
		
		if (!$display_jumpbox)
		{
			$_CLASS['core_template']->assign_vars_array('jumpbox_forums', array(
				'FORUM_ID'		=> ($select_all) ? 0 : -1,
				'FORUM_NAME'	=> ($select_all) ? $_CLASS['core_user']->get_lang('ALL_FORUMS') : $_CLASS['core_user']->get_lang('SELECT_FORUM'),
				'SELECTED'		=> '',
				'S_IS_CAT'		=> false,
				'S_IS_LINK'		=> false,
				'S_IS_POST'		=> false,
				'PADDING'		=> $padding)
			);

			$display_jumpbox = true;
		}

		$_CLASS['core_template']->assign_vars_array('jumpbox_forums', array(
			'FORUM_ID'		=> $row['forum_id'],
			'FORUM_NAME'	=> $row['forum_name'],
			'SELECTED'		=> ($row['forum_id'] == $forum_id) ? ' selected="selected"' : '',
			'S_IS_CAT'		=> ($row['forum_type'] == FORUM_CAT) ? true : false,
			'S_IS_LINK'		=> ($row['forum_type'] == FORUM_LINK) ? true : false,
			'S_IS_POST'		=> ($row['forum_type'] == FORUM_POST) ? true : false,
			'PADDING'		=> $padding)
		);
	}
	$_CLASS['core_db']->free_result($result);

	$_CLASS['core_template']->assign_array(array(
		'S_DISPLAY_JUMPBOX'	=> $display_jumpbox,
		'S_JUMPBOX_ACTION'	=> $action)
	);

	return;
}

// Topic and forum watching common code
//do we need user_id ?
function watch_topic_forum($mode, $user_id, $forum_id, $topic_id, $notify_status = 'unset', $start = 0)
{
	global $_CLASS, $config, $_CORE_CONFIG;

	if (!$_CLASS['core_user']->is_user || !$_CORE_CONFIG['email']['email_enable'] || !$config['allow_topic_notify'])
	{
		return array('link' => '', 'title' => '', 'watching' => false);
	}

	if ($mode == 'forum')
	{
		$where = "user_id = $user_id AND forum_id = $forum_id AND topic_id = 0";
		$url = 'f='.$forum_id;
	}
	else
	{
		$where = "user_id = $user_id AND forum_id = $forum_id AND topic_id IN ($topic_id, 0)";
		$url = 't='.$topic_id;
	}

	$is_watching = false;

	if ($notify_status == 'unset')
	{
		// Is user watching this thread?
		$sql = 'SELECT notify_status
			FROM '.FORUMS_WATCH_TABLE."
			 WHERE $where";

		$result = $_CLASS['core_db']->query($sql);

		$notify_status = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row['notify_status'] : null;
		$_CLASS['core_db']->free_result($result);
	}

	if (is_null($notify_status))
	{
		if (isset($_GET['watch']))
		{
			if ($_GET['watch'] == $mode)
			{
				$is_watching = true;

				// Remove all topics that are being watched, we watching the whole forum dude
				if ($mode == 'forum')
				{
					$sql = 'DELETE FROM ' . FORUMS_WATCH_TABLE . "
						WHERE forum_id = $forum_id
							AND user_id = $user_id";
				
					$_CLASS['core_db']->query($sql);
				}

				$sql_array = array(
					'user_id' => (int) $user_id,
					'forum_id' => $forum_id,
					'topic_id' => $topic_id,
					'notify_status' => 0,
					'notify_type' => 0
				);

				$_CLASS['core_db']->query('INSERT INTO ' . FORUMS_WATCH_TABLE . ' '.$_CLASS['core_db']->sql_build_array('INSERT', $sql_array));
			}

			$_CLASS['core_display']->meta_refresh(3, generate_link("Forums&amp;file=view$mode&amp;$url&amp;start=$start"));
			$message = $_CLASS['core_user']->lang['ARE_WATCHING_' . strtoupper($mode)] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_' . strtoupper($mode)], '<a href="' . generate_link("Forums&amp;file=view$mode&amp;$url&amp;start=$start") . '">', '</a>');
			trigger_error($message);
		}
	}
	else
	{
		if (isset($_GET['unwatch']))
		{
			if ($_GET['unwatch'] == $mode)
			{
				$is_watching = false;

				if ($mode == 'forum')
				{
					$where_delete = "forum_id = $forum_id AND topic_id = 0";
				}
				else
				{
					$where_delete = "forum_id = $forum_id AND topic_id = $topic_id";
				}

				$sql = 'DELETE FROM ' . FORUMS_WATCH_TABLE . "
							WHERE user_id = $user_id AND $where_delete";

				$_CLASS['core_db']->query($sql);
			}

			$_CLASS['core_display']->meta_refresh(3, generate_link("Forums&amp;file=view$mode&amp;$url&amp;start=$start"));
			$message = $_CLASS['core_user']->lang['NOT_WATCHING_' . strtoupper($mode)] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_' . strtoupper($mode)], '<a href="' . generate_link("Forums&amp;file=view$mode&amp;$url&amp;start=$start") . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			$is_watching = true;

			if ($notify_status)
			{
				$sql = 'UPDATE ' . FORUMS_WATCH_TABLE . "
					SET notify_status = 0
					WHERE $where";
				$_CLASS['core_db']->query($sql);
			}
		}
	}

	return array(
			'watching' => true,
			'link' => generate_link("Forums&amp;file=view$mode&amp;$url&amp;" . (($is_watching) ? 'unwatch' : 'watch') . "=$mode&amp;start=$start"),
			'title' => $_CLASS['core_user']->lang[(($is_watching) ? 'STOP' : 'START') . '_WATCHING_' . strtoupper($mode)],
	);
}

// Marks a topic or forum as read
function markread($mode, $forum_id = 0, $topic_id = 0, $marktime = false)
{
	global $config, $_CLASS, $_CORE_CONFIG;

	if ($_CLASS['core_user']->is_bot)
	{
		return;
	}

	// Default tracking type
	$time = ($marktime) ? $marktime : $_CLASS['core_user']->time;

	switch ($mode)
	{
		case 'forum':
			$forum_id = is_array($forum_id) ? array_map('intval', $forum_id) : array($forum_id);

			if ($_CLASS['core_user']->is_user && $config['load_db_lastread'])
			{
				$sql = 'SELECT forum_id, topic_id
					FROM ' . FORUMS_TRACK_TABLE . ' 
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
						AND forum_id IN (' . implode(', ', $forum_id). ')';
				$result = $_CLASS['core_db']->query($sql);

				$update_array = $delete_array = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					if ($row['topic_id'])
					{
						$delete_array[] = $row['forum_id'];
					}
					else
					{
						$update_array[] = $row['forum_id'];
					}
				}
				$_CLASS['core_db']->free_result($result);

				if (!empty($delete_array))
				{
					$sql = 'DELETE FROM ' . FORUMS_TRACK_TABLE . '
						WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
							AND forum_id IN (' . implode(', ', $delete_array) . ') AND  topic_id <> 0';
					$_CLASS['core_db']->query($sql);
				}
				unset($delete_array);

				if (!empty($update_array))
				{
					$sql = 'UPDATE ' . FORUMS_TRACK_TABLE . "
						SET mark_time = $time 
						WHERE user_id = " . $_CLASS['core_user']->data['user_id'] . '
							AND forum_id IN (' . implode(', ', $update_array) . ')
							AND topic_id = 0';
					$_CLASS['core_db']->query($sql);
				}

				if ($sql_insert = array_diff($forum_id, $update_array))
				{
					foreach ($sql_insert as $forum_id)
					{
						$sql_ary = array(
							'user_id'		=> $_CLASS['core_user']->data['user_id'],
							'topic_id'		=> 0,
							'forum_id'		=> $forum_id,
							'mark_time'		=> $time
						);

						$_CLASS['core_db']->query('INSERT INTO ' . FORUMS_TRACK_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary));
					}
				}
			}
			else
			{
				$tracking = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));
				
				if (!is_array($tracking))
				{
					$tracking = array();
				}

				foreach ($forum_id as $f_id)
				{
					$forum_id36 = base_convert($f_id, 10, 36);

					unset($tracking[$forum_id36]);
					$tracking[$forum_id36][0] = base_convert($time, 10, 36);
				}

				$_CLASS['core_user']->set_cookie('track', serialize($tracking), time() + 31536000);
				unset($tracking);
			}
		break;

		case 'topic':
			settype($topic_id, 'integer');
			settype($forum_id, 'integer');

			if ($_CLASS['core_user']->is_user && $config['load_db_lastread'])
			{
				$sql = 'UPDATE ' . FORUMS_TRACK_TABLE . "
							SET forum_id = $forum_id, mark_time = $time
								WHERE topic_id = $topic_id
								AND user_id = ".$_CLASS['core_user']->data['user_id'];

				if (!$_CLASS['core_db']->query($sql) || !$_CLASS['core_db']->affected_rows())
				{
					$sql_ary = array(
						'user_id'		=> $_CLASS['core_user']->data['user_id'],
						'topic_id'		=> $topic_id,
						'forum_id'		=> $forum_id,
						'mark_time'		=> $time
					);

					$_CLASS['core_db']->query('INSERT INTO ' . FORUMS_TRACK_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary));
				}
			}
			else
			{
				$tracking = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));

				if (!is_array($tracking))
				{
					$tracking = array();
				}

				/*
				// If the cookie grows larger than 2000 characters we will remove
				// the smallest value
				if (strlen($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track']) > 2000)
				{
					foreach ($tracking as $f => $t_ary)
					{
						if (!isset($m_value) || min($t_ary) < $m_value)
						{
							$m_value = min($t_ary);
							$m_tkey = array_search($m_value, $t_ary);
							$m_fkey = $f;
						}
					}
					unset($tracking[$m_fkey][$m_tkey]);
				}*/

				$topic_id36 = base_convert($topic_id, 10, 36);
				$forum_id36 = base_convert($forum_id, 10, 36);

				if (isset($tracking[$forum_id36][$topic_id36]) && base_convert($tracking[$forum_id36][$topic_id36], 36, 10) < $time)
				{
					$tracking[$forum_id36][$topic_id36] = base_convert($time, 10, 36);

					$_CLASS['core_user']->set_cookie('track', serialize($tracking), time() + 31536000);
				}
				elseif (!isset($tracking[$forum_id36][$topic_id36]))
				{
					$tracking[$forum_id36][$topic_id36] = base_convert($time, 10, 36);
					$_CLASS['core_user']->set_cookie('track', serialize($tracking), time() + 31536000);
				}
				unset($tracking);
			}
		break;
	}
}

// Obtain list of naughty words and build preg style replacement arrays for use by the
// calling script, note that the vars are passed as references this just makes it easier
// to return both sets of arrays
function obtain_word_list()
{
	global $_CLASS, $config;

	if (!$_CLASS['core_user']->optionget('viewcensors') && $config['allow_nocensors'])
	{
		return;
	}

	if (is_null($censors = $_CLASS['core_cache']->get('word_censors')))
	{
		$sql = 'SELECT word, replacement
			FROM  ' . FORUMS_WORDS_TABLE;
		$result = $_CLASS['core_db']->query($sql);

		$censors = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$censors['match'][] = '#\b(' . str_replace('\*', '\w*?', preg_quote($row['word'], '#')) . ')\b#i';
			$censors['replace'][] = $row['replacement'];
		}

		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_cache']->put('word_censors', $censors);
	}

	return $censors;
}

// Obtain icons
function obtain_icons()
{
	global $_CLASS;

	if (is_null($icons = $_CLASS['core_cache']->get('icons')))
	{
		$sql = 'SELECT *
			FROM ' . FORUMS_ICONS_TABLE . ' 
			ORDER BY icons_order';
		$result = $_CLASS['core_db']->query($sql);

		$icons = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$icons[$row['icons_id']]['img'] = $row['icons_url'];
			$icons[$row['icons_id']]['width'] = (int) $row['icons_width'];
			$icons[$row['icons_id']]['height'] = (int) $row['icons_height'];
			$icons[$row['icons_id']]['display'] = (bool) $row['display_on_posting'];
		}

		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_cache']->put('icons', $icons);
	}

	return $icons;
}

// Obtain ranks
function obtain_ranks()
{
	global $_CLASS;

	if (is_null($ranks = $_CLASS['core_cache']->get('ranks')))
	{
		$sql = 'SELECT *
			FROM ' . FORUMS_RANKS_TABLE . '
			ORDER BY rank_min DESC';
		$result = $_CLASS['core_db']->query($sql);

		$ranks = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['rank_special'])
			{
				$ranks['special'][$row['rank_id']] = array(
					'rank_title'	=>	$row['rank_title'],
					'rank_image'	=>	$row['rank_image']
				);
			}
			else
			{
				$ranks['normal'][] = array(
					'rank_title'	=>	$row['rank_title'],
					'rank_min'		=>	$row['rank_min'],
					'rank_image'	=>	$row['rank_image']
				);
			}
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_cache']->put('ranks', $ranks);
	}

	return $ranks;
}

// Obtain allowed extensions
function obtain_attach_extensions($forum_id = false)
{
	global $_CLASS;

	if (is_null($extensions = $_CLASS['core_cache']->get('extensions')))
	{
		// The rule is to only allow those extensions defined. ;)
		$sql = 'SELECT e.extension, g.*
			FROM ' . FORUMS_EXTENSIONS_TABLE . ' e, ' . FORUMS_EXTENSION_GROUPS_TABLE . ' g
			WHERE e.group_id = g.group_id
				AND g.allow_group = 1';
		$result = $_CLASS['core_db']->query($sql);

		$extensions = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$extension = strtolower(trim($row['extension']));

			$extensions[$extension]['display_cat']		= (int) $row['cat_id'];
			$extensions[$extension]['download_mode']	= (int) $row['download_mode'];
			$extensions[$extension]['upload_icon']		= trim($row['upload_icon']);
			$extensions[$extension]['max_filesize']		= (int) $row['max_filesize'];
		
			$allowed_forums = ($row['allowed_forums']) ? unserialize(trim($row['allowed_forums'])) : array();
			
			if ($row['allow_in_pm'])
			{
				$allowed_forums = array_merge($allowed_forums, array(0));
			}
			
			// Store allowed extensions forum wise
			$extensions['_allowed_'][$extension] = empty($allowed_forums) ? 0 : $allowed_forums;
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_cache']->put('extensions', $extensions);
	}

	if ($forum_id !== false)
	{
		$return = array();

		foreach ($extensions['_allowed_'] as $extension => $check)
		{
			$allowed = false;

			if (is_array($check))
			{
				// Check for private messaging
				if (count($check) == 1 && $check[0] == 0)
				{
					$allowed = true;
				}
				else
				{
					$allowed = (!in_array($forum_id, $check)) ? false : true;
				}
			}
			else
			{
				$allowed = ($forum_id == 0) ? false : true;
			}
			
			if ($allowed)
			{
				$return['_allowed_'][$extension] = 0;
				$return[$extension] = $extensions[$extension];
			}
		}

		$extensions = $return;
	}
	
	return $extensions;
}

function generate_board_url()
{
	global $config;

	$path = preg_replace('#^/?(.*?)/?$#', '\1', trim($config['script_path']));

	return (($config['cookie_secure']) ? 'https://' : 'http://') . preg_replace('#^/?(.*?)/?$#', '\1', trim($config['server_name'])) . (($config['server_port'] <> 80) ? ':' . trim($config['server_port']) : '') . (($path) ? '/' . $path : '');
}

// Generate forum login box
function login_forum_box($forum_data)
{
	global $config, $_CLASS;

	$sql = 'SELECT forum_id
		FROM ' . FORUMS_ACCESS_TABLE ."
		WHERE forum_id = '".$forum_data['forum_id']."'
			AND user_id = '".$_CLASS['core_user']->data['user_id']."'
			AND session_id = '".$_CLASS['core_user']->session_id."'";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_db']->free_result($result);
		return true;
	}
	$_CLASS['core_db']->free_result($result);

	$password = request_var('password', '');

	if ($password)
	{
		// Remove expired authorised sessions
		$sql = 'SELECT session_id 
			FROM ' . SESSIONS_TABLE;
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$sql_in = array();
			do
			{
				$sql_in[] = "'" . $_CLASS['core_db']->escape($row['session_id']) . "'";
			}
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

			$sql = 'DELETE FROM ' . FORUMS_ACCESS_TABLE . '
				WHERE session_id NOT IN (' . implode(', ', $sql_in) . ')';
			$_CLASS['core_db']->query($sql);
		}
		$_CLASS['core_db']->free_result($result);

		if ($password == $forum_data['forum_password'])
		{
			$sql = 'INSERT INTO ' . FORUMS_ACCESS_TABLE . ' (forum_id, user_id, session_id)
				VALUES (' . $forum_data['forum_id'] . ', ' . $_CLASS['core_user']->data['user_id'] . ", '" . $_CLASS['core_db']->escape($_CLASS['core_user']->session_id) . "')";
			$_CLASS['core_db']->query($sql);

			return true;
		}

		$_CLASS['core_template']->assign('LOGIN_ERROR', $_CLASS['core_user']->lang['WRONG_PASSWORD']);
	}

	page_header();

	$_CLASS['core_template']->display('modules/Forums/login_forum.html');

	script_close();
}

// Bump Topic Check - used by posting and viewtopic
function bump_topic_allowed($forum_id, $topic_bumped, $last_post_time, $topic_poster, $last_topic_poster)
{
	global $config, $_CLASS;

	// Check permission and make sure the last post was not already bumped
	if (!$_CLASS['auth']->acl_get('f_bump', $forum_id) || $topic_bumped)
	{
		return false;
	}

	// Check bump time range, is the user really allowed to bump the topic at this time?
	$bump_time = ($config['bump_type'] == 'm') ? $config['bump_interval'] * 60 : (($config['bump_type'] == 'h') ? $config['bump_interval'] * 3600 : $config['bump_interval'] * 86400);

	// Check bump time
	if ($last_post_time + $bump_time > time())
	{
		return false;
	}

	// Check bumper, only topic poster and last poster are allowed to bump
	if ($topic_poster != $_CLASS['core_user']->data['user_id'] && $last_topic_poster != $_CLASS['core_user']->data['user_id'] && !$_CLASS['auth']->acl_get('m_', $forum_id))
	{
		return false;
	}

	// A bump time of 0 will completely disable the bump feature... not intended but might be useful.
	return $bump_time;
}

// Censoring
function censor_text($text)
{
	global $_CLASS;

	if ($_CLASS['core_user']->is_user && !$_CLASS['core_user']->optionget('viewcensors'))
	{
		return $text;
	}

	$censors = obtain_word_list();

	if (!empty($censors))
	{
		return preg_replace($censors['match'], $censors['replace'], $text);
	}

	return $text;
}

// Smiley processing
function smiley_text($text, $force_option = false)
{
	global $config, $_CLASS;

	return ($force_option || !$config['allow_smilies'] || !$_CLASS['core_user']->optionget('viewsmilies')) ? preg_replace('#<!\-\- s(.*?) \-\-><img src="\{SMILIES_PATH\}\/.*? \/><!\-\- s\1 \-\->#', '\1', $text) : str_replace('<img src="{SMILIES_PATH}', '<img src="' . $config['smilies_path'], $text);
}

// Inline Attachment processing
function parse_inline_attachments(&$text, $attachments, &$update_count, $forum_id = 0, $preview = false)
{
	global $config, $_CLASS;

	$unset_array = array();
	$tpl_size = count($attachments);

	preg_match_all('#<!\-\- ia([0-9]+) \-\->(.*?)<!\-\- ia\1 \-\->#', $text, $matches, PREG_PATTERN_ORDER);

	//print_r($matches);
	if (count($matches[1]))
	{
		$matches[1] = array_unique($matches[1]);

		foreach ($matches[1] as $key => $index)
		{
			// Flip index if we are displaying the reverse way
// whats this display_order all about ?
			$index = ($config['display_order']) ? ($tpl_size - ($index + 1)) : $index;

			if (isset($attachments[$index]))
			{
				$inline_attachments[$key] = display_attachments($forum_id, array($attachments[$index]), $update_count, $preview, true);
				$unset_array[] = $index;
			}
			else
			{
				$inline_attachments[$key] = false;
			}
		}
//print_r($inline_attachments);
		//$inline_attachments = display_attachments($forum_id, $inline_attachments, $update_count, $preview, true);

		$replace = array();
		foreach ($matches[1] as $key => $index)
		{
			$replace['from'][] = $matches[0][$key];
			$replace['to'][] = ($inline_attachments[$key]) ? $inline_attachments[$key][0] : sprintf($_CLASS['core_user']->lang['MISSING_INLINE_ATTACHMENT'], $matches[2][array_search($index, $matches[1])]);
		}

		if (isset($replace['from']))
		{
			$text = str_replace($replace['from'], $replace['to'], $text);
		}
	}

	return $unset_array;
}

// Check if extension is allowed to be posted within forum X (forum_id 0 == private messaging)
function extension_allowed($forum_id, $extension, &$extensions)
{
	if (empty($extensions))
	{
		$extensions = obtain_attach_extensions();
	}

	if (!isset($extensions['_allowed_'][$extension]))
	{
		return false;
	}

	$check = $extensions['_allowed_'][$extension];

	if (is_array($check))
	{
		// Check for private messaging
		if (count($check) == 1 && $check[0] == 0)
		{
			return true;
		}

		return (!in_array($forum_id, $check)) ? false : true;
	}
	else
	{
		return ($forum_id == 0) ? false : true;
	}

	return false;
}

function page_header()
{
	global $config, $SID, $_CLASS, $_CORE_CONFIG;

	define('HEADER_INC', TRUE);

	// Generate logged in/logged out status
	if ($_CLASS['core_user']->is_user)
	{
		$u_login_logout = generate_link('Control_Panel&amp;mode=logout');
		$l_login_logout = sprintf($_CLASS['core_user']->lang['LOGOUT_USER'], $_CLASS['core_user']->data['username']);
	}
	elseif (!$_CLASS['core_user']->is_bot)
	{
		$u_login_logout = generate_link('Control_Panel&amp;mode=login');
		$l_login_logout = $_CLASS['core_user']->lang['LOGIN'];
	}

	// Last visit date/time
	$s_last_visit = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->format_date($_CLASS['core_user']->data['session_last_visit']) : '';

	// Get users online list ... if required
	$l_online_users = $online_userlist = $l_online_record = $l_online_time = '';
	
	if ($config['load_online'] && $config['load_online_time'])
	{
		$userlist_ary = $userlist_visible = array();
		$logged_visible_online = $logged_hidden_online = $guests_online = $prev_user_id = 0;
		$prev_session_ip = $reading_sql = '';
		//////////////////
		/// Need to fix this\
		/// Think about removing session_users();
		/////////////////
		if (!empty($_REQUEST['f']))
		{
			$f = request_var('f', 0);
			$reading_sql = "AND s.session_url LIKE '%f=$f%'";
		}

		$session_users = session_users();
		foreach ($session_users as $row)
		{
			// User is logged in and therefor not a guest
			if ($row['user_id'] != ANONYMOUS)
			{
				// Skip multiple sessions for one user
				if ($row['user_id'] != $prev_user_id)
				{
					if ($row['user_colour'])
					{
						$row['username'] = '<b style="color:#' . $row['user_colour'] . '">' . $row['username'] . '</b>';
					}

					if ($row['user_allow_viewonline'] && !$row['session_hidden'])
					{
						$user_online_link = $row['username'];
						$logged_visible_online++;
					}
					else
					{
						$user_online_link = '<i>' . $row['username'] . '</i>';
						$logged_hidden_online++;
					}
// Fix this
					if ($row['user_allow_viewonline'] || $_CLASS['auth']->acl_get('u_viewonline'))
					{
						$user_online_link = ($row['user_type'] & USER_BOT) ? "<a href=\"" . generate_link('Members_List&amp;&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $user_online_link . '</a>' : $user_online_link;
						$online_userlist .= ($online_userlist != '') ? ', ' . $user_online_link : $user_online_link;
					}
				}

				$prev_user_id = $row['user_id'];
			}
			else
			{
				// Skip multiple sessions for one user
				if ($row['session_ip'] != $prev_session_ip)
				{
					$guests_online++;
				}
			}

			$prev_session_ip = $row['session_ip'];
		}
		unset($session_users);
		
		if (!$online_userlist)
		{
			$online_userlist = $_CLASS['core_user']->lang['NONE'];
		}

		if (empty($_REQUEST['f']))
		{
			$online_userlist = $_CLASS['core_user']->lang['REGISTERED_USERS'] . ' ' . $online_userlist;
		}
		else
		{
			$l_online = ($guests_online == 1) ? $_CLASS['core_user']->lang['BROWSING_FORUM_GUEST'] : $_CLASS['core_user']->lang['BROWSING_FORUM_GUESTS'];
			$online_userlist = sprintf($l_online, $online_userlist, $guests_online);
		}

		$total_online_users = $logged_visible_online + $logged_hidden_online + $guests_online;

		if ($total_online_users > $config['record_online_users'])
		{
			set_config('record_online_users', $total_online_users, TRUE);
			set_config('record_online_date', time(), TRUE);
		}

		// Build online listing
		$vars_online = array(
			'ONLINE'=> array('total_online_users', 'l_t_user_s'),
			'REG'	=> array('logged_visible_online', 'l_r_user_s'),
			'HIDDEN'=> array('logged_hidden_online', 'l_h_user_s'),
			'GUEST'	=> array('guests_online', 'l_g_user_s')
		);

		foreach ($vars_online as $l_prefix => $var_ary)
		{
			switch (${$var_ary[0]})
			{
				case 0:
					${$var_ary[1]} = $_CLASS['core_user']->lang[$l_prefix . '_USERS_ZERO_TOTAL'];
					break;

				case 1:
					${$var_ary[1]} = $_CLASS['core_user']->lang[$l_prefix . '_USER_TOTAL'];
					break;

				default:
					${$var_ary[1]} = $_CLASS['core_user']->lang[$l_prefix . '_USERS_TOTAL'];
					break;
			}
		}
		unset($vars_online);

		$l_online_users = sprintf($l_t_user_s, $total_online_users);
		$l_online_users .= sprintf($l_r_user_s, $logged_visible_online);
		$l_online_users .= sprintf($l_h_user_s, $logged_hidden_online);
		$l_online_users .= sprintf($l_g_user_s, $guests_online);

		$l_online_record = sprintf($_CLASS['core_user']->lang['RECORD_ONLINE_USERS'], $config['record_online_users'], $_CLASS['core_user']->format_date($config['record_online_date']));

		$l_online_time = ($config['load_online_time'] == 1) ? 'VIEW_ONLINE_TIME' : 'VIEW_ONLINE_TIMES';
		$l_online_time = sprintf($_CLASS['core_user']->lang[$l_online_time], $config['load_online_time']);
		
	}
	
	$l_privmsgs_text = $l_privmsgs_text_unread = '';

	// Obtain number of new private messages if user is logged in
	if ($_CLASS['core_user']->is_user)
	{
		if ($_CLASS['core_user']->data['user_new_privmsg'])
		{
			$l_message_new = ($_CLASS['core_user']->data['user_new_privmsg'] == 1) ? $_CLASS['core_user']->lang['NEW_PM'] : $_CLASS['core_user']->lang['NEW_PMS'];
			$l_privmsgs_text = sprintf($l_message_new, $_CLASS['core_user']->data['user_new_privmsg']);
		}
		else
		{
			$l_privmsgs_text = $_CLASS['core_user']->lang['NO_NEW_PM'];
		}

		$l_privmsgs_text_unread = '';

		if ($_CLASS['core_user']->data['user_unread_privmsg'] && $_CLASS['core_user']->data['user_unread_privmsg'] != $_CLASS['core_user']->data['user_new_privmsg'])
		{
			$l_message_unread = ($_CLASS['core_user']->data['user_unread_privmsg'] == 1) ? $_CLASS['core_user']->lang['UNREAD_PM'] : $_CLASS['core_user']->lang['UNREAD_PMS'];
			$l_privmsgs_text_unread = sprintf($l_message_unread, $_CLASS['core_user']->data['user_unread_privmsg']);
		}
	}

	// Which timezone?
	$tz = ($_CLASS['core_user']->is_user) ? strval(doubleval($_CLASS['core_user']->data['user_timezone'])) : strval(doubleval($_CORE_CONFIG['global']['default_timezone']));

	// The following assigns all _common_ variables that may be used at any point
	// in a template.
	$_CLASS['core_template']->assign_array(array(
		'LAST_VISIT_DATE' 			=> sprintf($_CLASS['core_user']->lang['YOU_LAST_VISIT'], $s_last_visit),
		'CURRENT_TIME'				=> sprintf($_CLASS['core_user']->lang['CURRENT_TIME'], $_CLASS['core_user']->format_date(time(), false, true)),
		'TOTAL_USERS_ONLINE' 		=> $l_online_users,
		'LOGGED_IN_USER_LIST' 		=> $online_userlist,
		'RECORD_USERS' 				=> $l_online_record,
		'PRIVATE_MESSAGE_INFO' 		=> $l_privmsgs_text,
		'PRIVATE_MESSAGE_INFO_UNREAD' 	=> $l_privmsgs_text_unread,
		
		'L_LOGIN_LOGOUT' 		=> $l_login_logout,
		'L_REGISTER' 			=> $_CLASS['core_user']->lang['REGISTER'],
		'L_INDEX' 				=> $_CLASS['core_user']->lang['FORUM_INDEX'], 
		'L_ONLINE_EXPLAIN'		=> $l_online_time, 
		'U_PRIVATEMSGS'			=> generate_link('Control_Panel&amp;i=pm&amp;mode=' . (($_CLASS['core_user']->data['user_new_privmsg'] || $l_privmsgs_text_unread) ? 'unread' : 'view_messages')),
		'U_RETURN_INBOX'		=> generate_link("Control_Panel&amp;i=pm&amp;folder=inbox"),
		'U_MEMBERLIST' 			=> generate_link('Members_List'),
		'U_VIEWONLINE' 			=> generate_link('View_Online'),
		'U_MEMBERSLIST'			=> generate_link('Members_List'),
		'U_LOGIN_LOGOUT'		=> $u_login_logout,
		'U_INDEX' 				=> generate_link('Forums'),
		'U_SEARCH' 				=> generate_link('Forums&amp;file=search'),
		'U_REGISTER' 			=> generate_link('Control_Panel&amp;mode=register'),
		'U_PROFILE' 			=> generate_link('Control_Panel'),
		'U_MODCP' 				=> generate_link('Forums&amp;file=mcp'),
		'U_FAQ' 				=> generate_link('Forums&amp;file=faq'),
		'U_SEARCH_SELF'			=> generate_link('Forums&amp;file=search&amp;search_id=egosearch'),
		'U_SEARCH_NEW' 			=> generate_link('Forums&amp;file=search&amp;search_id=newposts'),
		'U_SEARCH_UNANSWERED'	=> generate_link('Forums&amp;file=search&amp;search_id=unanswered'),
		'U_SEARCH_ACTIVE_TOPICS'=> generate_link('Forums&amp;file=search&amp;search_id=active_topics'),
		'U_DELETE_COOKIES'		=> generate_link('Control_Panel&amp;mode=delete_cookies'),

		'S_USER_LOGGED_IN' 		=> ($_CLASS['core_user']->data['user_id'] != ANONYMOUS) ? true : false,
		'S_REGISTERED_USER'		=> $_CLASS['core_user']->is_user,
		'S_USER_PM_POPUP' 		=> $_CLASS['core_user']->optionget('popuppm'),
		'S_USER_LANG'			=> $_CLASS['core_user']->data['user_lang'], 
		'S_USER_BROWSER' 		=> ($_CLASS['core_user']->data['session_browser']) ? $_CLASS['core_user']->data['session_browser'] : $_CLASS['core_user']->lang['UNKNOWN_BROWSER'],
		'S_CONTENT_DIRECTION' 	=> $_CLASS['core_user']->lang['DIRECTION'],
		'S_CONTENT_DIR_LEFT' 	=> $_CLASS['core_user']->lang['LEFT'],
		'S_CONTENT_DIR_RIGHT' 	=> $_CLASS['core_user']->lang['RIGHT'],
		'S_TIMEZONE'			=> ($_CLASS['core_user']->data['user_dst'] || (!$_CLASS['core_user']->is_user && $_CORE_CONFIG['global']['default_dst'])) ? sprintf($_CLASS['core_user']->lang['ALL_TIMES'], $_CLASS['core_user']->lang['tz'][$tz/3600], $_CLASS['core_user']->lang['tz']['dst']) : sprintf($_CLASS['core_user']->lang['ALL_TIMES'], $_CLASS['core_user']->lang['tz'][$tz/3600], ''),

		'S_DISPLAY_ONLINE_LIST'	=> ($config['load_online']) ? 1 : 0, 
		'S_DISPLAY_SEARCH'		=> ($config['load_search']) ? 1 : 0, 
		'S_DISPLAY_PM'			=> ($config['allow_privmsg']) ? 1 : 0, 
		'S_DISPLAY_MEMBERLIST'	=> $_CLASS['auth']->acl_get('u_viewprofile'), 

		'T_SMILIES_PATH'		=> "{$config['smilies_path']}/",
		'T_AVATAR_PATH'			=> "{$config['avatar_path']}/",
		'T_AVATAR_GALLERY_PATH'	=> "{$config['avatar_gallery_path']}/",
		'T_ICONS_PATH'			=> "{$config['icons_path']}/",
		'T_RANKS_PATH'			=> "{$config['ranks_path']}/",
		'T_UPLOAD_PATH'			=> "{$config['upload_path']}/",

// Temp
		'T_IMAGE_PATH'			=> "themes/viperal/template/modules/Forums/images/",

		'U_ACP'				=> ($_CLASS['core_user']->is_admin && $_CLASS['auth']->acl_get('a_')) ? generate_link('Forums', array('admin' => true)) : '',
		'L_ACP'				=> $_CLASS['core_user']->lang['ACP']
	));
}

?>