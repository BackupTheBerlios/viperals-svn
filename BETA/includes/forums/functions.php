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
// $Id: functions.php,v 1.295 2004/09/17 09:11:32 acydburn Exp $
//
// FILENAME  : functions.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001,2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Generates an alphanumeric random string of given length
function gen_rand_string($num_chars)
{
	$chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',  'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',  'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9');

	list($usec, $sec) = explode(' ', microtime()); 
	mt_srand($sec * $usec); 

	$max_chars = count($chars) - 1;
	$rand_str = '';
	for ($i = 0; $i < $num_chars; $i++)
	{
		$rand_str .= $chars[mt_rand(0, $max_chars)];
	}

	return $rand_str;
}

function get_userdata($user)
{
	global $_CLASS;

	$sql = 'SELECT *
		FROM ' . USERS_TABLE . '
		WHERE ';
	$sql .= ((is_integer($user)) ? "user_id = $user" : "username = '" .  $_CLASS['db']->sql_escape($user) . "'") . " AND user_id <> " . ANONYMOUS;
	$result = $_CLASS['db']->sql_query($sql);

	return ($row = $_CLASS['db']->sql_fetchrow($result)) ? $row : false;
}

// Create forum rules for given forum 
function generate_forum_rules(&$forum_data)
{
	global $phpEx, $_CLASS;
	if (!$forum_data['forum_rules'] && !$forum_data['forum_rules_link'])
	{
		return;
	}

	if ($forum_data['forum_rules'])
	{
		requireOnce('includes/forums/bbcode.' . $phpEx);
		$bbcode = new bbcode($forum_data['forum_rules_bbcode_bitfield']);
		
		$bbcode->bbcode_second_pass($forum_data['forum_rules'], $forum_data['forum_rules_bbcode_uid']);

		$forum_data['forum_rules'] = smilie_text($forum_data['forum_rules'], !($forum_data['forum_rules_flags'] & 2));
		$forum_data['forum_rules'] = str_replace("\n", '<br />', censor_text($forum_data['forum_rules']));
		unset($bbcode);
	}

	$_CLASS['template']->assign(array(
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

		$_CLASS['template']->assign_vars_array('navlinks', array(
			'S_IS_CAT'		=>	($parent_type == FORUM_CAT) ? true : false,
			'S_IS_LINK'		=>	($parent_type == FORUM_LINK) ? true : false,
			'S_IS_POST'		=>	($parent_type == FORUM_POST) ? true : false,
			'FORUM_NAME'	=>	$parent_name,
			'FORUM_ID'		=>	$parent_forum_id,
			'U_VIEW_FORUM'	=>	getlink('Forums&amp;file=viewforum&amp;f='.$parent_forum_id)
			)
		);
	}

	$_CLASS['template']->assign_vars_array('navlinks', array(
		'S_IS_CAT'		=>	($forum_data['forum_type'] == FORUM_CAT) ? true : false,
		'S_IS_LINK'		=>	($forum_data['forum_type'] == FORUM_LINK) ? true : false,
		'S_IS_POST'		=>	($forum_data['forum_type'] == FORUM_POST) ? true : false,
		'FORUM_NAME'	=>	$forum_data['forum_name'],
		'FORUM_ID'		=>  $forum_data['forum_id'],
		'U_VIEW_FORUM'	=>	getlink('Forums&amp;file=viewforum&amp;f=' . $forum_data['forum_id'])
		)
	);

	$_CLASS['template']->assign(array(
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
	
	if ($forum_data['parent_id'] > 0)
	{
		if ($forum_data['forum_parents'] == '')
		{
			$sql = 'SELECT forum_id, forum_name, forum_type
				FROM ' . FORUMS_TABLE . '
				WHERE left_id < ' . $forum_data['left_id'] . '
					AND right_id > ' . $forum_data['right_id'] . '
				ORDER BY left_id ASC';
			$result = $_CLASS['db']->sql_query($sql);

			while ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				$forum_parents[$row['forum_id']] = array($row['forum_name'], (int) $row['forum_type']);
			}
			$_CLASS['db']->sql_freeresult($result);

			$forum_data['forum_parents'] = serialize($forum_parents);

			$sql = 'UPDATE ' . FORUMS_TABLE . "
				SET forum_parents = '" . $_CLASS['db']->sql_escape($forum_data['forum_parents']) . "'
				WHERE parent_id = " . $forum_data['parent_id'];
			$_CLASS['db']->sql_query($sql);
		}
		else
		{
			$forum_parents = unserialize($forum_data['forum_parents']);
		}
	}

	return $forum_parents;
}

// Obtain list of moderators of each forum
function get_moderators(&$forum_moderators, $forum_id = false)
{
	global $config, $_CLASS;

	// Have we disabled the display of moderators? If so, then return
	// from whence we came ... 
	if (empty($config['load_moderators']))
	{
		return;
	}

	if (!empty($forum_id) && is_array($forum_id))
	{
		$forum_sql = 'AND forum_id IN (' . implode(', ', $forum_id) . ')';
	}
	else
	{
		$forum_sql = ($forum_id) ? 'AND forum_id = ' . $forum_id : '';
	}

	$sql = 'SELECT *
		FROM ' . MODERATOR_TABLE . "
		WHERE display_on_index = 1
			$forum_sql";
	$result = $_CLASS['db']->sql_query($sql);

	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$forum_moderators[$row['forum_id']][] = (!empty($row['user_id'])) ? '<a href="' . getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $row['username'] . '</a>' : '<a href="' . getlink('Members_List&amp;mode=group&amp;g=' . $row['group_id']) . '">' . $row['groupname'] . '</a>';
	}
	$_CLASS['db']->sql_freeresult($result);

	return;
}

// User authorisation levels output
function gen_forum_auth_level($mode, $forum_id)
{
	global $_CLASS;

	$rules = array(
		($_CLASS['auth']->acl_get('f_post', $forum_id)) ? $_CLASS['user']->lang['RULES_POST_CAN'] : $_CLASS['user']->lang['RULES_POST_CANNOT'],
		($_CLASS['auth']->acl_get('f_reply', $forum_id)) ? $_CLASS['user']->lang['RULES_REPLY_CAN'] : $_CLASS['user']->lang['RULES_REPLY_CANNOT'],
		($_CLASS['auth']->acl_gets('f_edit', 'm_edit', $forum_id)) ? $_CLASS['user']->lang['RULES_EDIT_CAN'] : $_CLASS['user']->lang['RULES_EDIT_CANNOT'],
		($_CLASS['auth']->acl_gets('f_delete', 'm_delete', $forum_id)) ? $_CLASS['user']->lang['RULES_DELETE_CAN'] : $_CLASS['user']->lang['RULES_DELETE_CANNOT'],
		($_CLASS['auth']->acl_get('f_attach', $forum_id) && $_CLASS['auth']->acl_get('u_attach', $forum_id)) ? $_CLASS['user']->lang['RULES_ATTACH_CAN'] : $_CLASS['user']->lang['RULES_ATTACH_CANNOT']
	);

	foreach ($rules as $rule)
	{
		$_CLASS['template']->assign_vars_array('rules', array('RULE' => $rule));
	}

	return;
}

function gen_sort_selects(&$limit_days, &$sort_by_text, &$sort_days, &$sort_key, &$sort_dir, &$s_limit_days, &$s_sort_key, &$s_sort_dir, &$u_sort_param)
{
	global $_CLASS;

	$sort_dir_text = array('a' => $_CLASS['user']->lang['ASCENDING'], 'd' => $_CLASS['user']->lang['DESCENDING']);

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

	if (!$config['load_jumpbox'])
	{
		return;
	}

	$sql = 'SELECT forum_id, forum_name, parent_id, forum_type, left_id, right_id
		FROM ' . FORUMS_TABLE . '
		ORDER BY left_id ASC';
	$result = $_CLASS['db']->sql_query($sql);

	$right = $cat_right = $padding = 0;
	$padding_store = array('0' => 0);
	$display_jumpbox = false;
	$iteration = 0;
	
	// Sometimes it could happen that forums will be displayed here not be displayed within the index page
	// This is the result of forums not displayed at index, having list permissions and a parent of a forum with no permissions.
	// If this happens, the padding could be "broken"
	$thissection = 0;
	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		
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
		
		if ($acl_list && !$_CLASS['auth']->acl_get($acl_list, $row['forum_id']))
		{
			continue;
		}
		
		if (!$display_jumpbox)
		{
			$_CLASS['template']->assign_vars_array('jumpbox_forums', array(
				'FORUM_ID'		=> ($select_all) ? 0 : -1,
				'FORUM_NAME'	=> ($select_all) ? $_CLASS['user']->lang['ALL_FORUMS'] : $_CLASS['user']->lang['SELECT_FORUM'])
			);

			$display_jumpbox = true;
		}
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

		if ($row['right_id'] - $row['left_id'] > 1)
		{
			$cat_right = max($cat_right, $row['right_id']);
		}
		for ($i = 0; $i < $padding; $i++)
		{
			$_CLASS['template']->assign_vars_array('jumpbox_forums_level', array(
			'SECTION'		=> $thissection));
		}
		
		$_CLASS['template']->assign_vars_array('jumpbox_forums', array(
			'FORUM_ID'		=> $row['forum_id'],
			'FORUM_NAME'	=> $row['forum_name'],
			'SELECTED'		=> ($row['forum_id'] == $forum_id) ? ' selected="selected"' : '',
			'S_IS_CAT'		=> ($row['forum_type'] == FORUM_CAT) ? true : false,
			'S_IS_LINK'		=> ($row['forum_type'] == FORUM_LINK) ? true : false,
			'S_IS_POST'		=> ($row['forum_type'] == FORUM_POST) ? true : false,
			'PADDING'		=> $padding)
		);

	$thissection++;
	
	}
	$_CLASS['db']->sql_freeresult($result);
	unset($padding_store);

	$_CLASS['template']->assign(array(
		'S_DISPLAY_JUMPBOX'	=> $display_jumpbox,
		'S_JUMPBOX_ACTION'	=> $action)
	);

	return;
}

// Pick a language, any language ...
function language_select($default = '')
{
	global $_CLASS;

	$sql = 'SELECT lang_iso, lang_local_name 
		FROM ' . LANG_TABLE . '
		ORDER BY lang_english_name';
	$result = $_CLASS['db']->sql_query($sql);

	$lang_options = '';
	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$selected = ($row['lang_iso'] == $default) ? ' selected="selected"' : '';
		$lang_options .= '<option value="' . $row['lang_iso'] . '"' . $selected . '>' . $row['lang_local_name'] . '</option>';
	}
	$_CLASS['db']->sql_freeresult($result);

	return $lang_options;
}

// Pick a template/theme combo,
function theme_select($default = '', $all = false)
{
	static $theme;
	
	if ($theme)
	{
		return $theme;
	}
	
	$themetmp = array();
	
	$theme = '';
	$handle = opendir('themes');
	while ($file = readdir($handle)) {
		if (!ereg('[.]',$file)) {
			if (file_exists("themes/$file/index.php")) {
				$themetmp[] = array('file' => $file, 'template'=> true);
			} elseif (file_exists("themes/$file/theme.php")) {
				$themetmp[] = array('file' => $file, 'template'=> false);
			} 
		} 
	}
	
	closedir($handle);
	
	$count = count($themetmp);
	
	for ($i=0; $i < $count; $i++) {
		
		$themetmp[$i]['name'] = ($themetmp[$i]['template']) ? $themetmp[$i]['file'].' *' : $themetmp[$i]['file'];
		if ($themetmp[$i]['file'] == $default)
		{
			$theme .= '<option value="'.$themetmp[$i]['file'].'" selected="selected">'.$themetmp[$i]['name'].'</option>';
		} else {
			$theme .= '<option value="'.$themetmp[$i]['file'].'">'.$themetmp[$i]['name'].'</option>';
		}
	}
	
	unset($themetmp);
	
	return $theme;
}
// Pick a timezone
function tz_select($default = '')
{
	global $sys_timezone, $_CLASS;

	$tz_select = '';
	foreach ($_CLASS['user']->lang['tz'] as $offset => $zone)
	{
		if (is_numeric($offset))
		{
			$selected = ($offset == $default) ? ' selected="selected"' : '';
			$tz_select .= '<option value="' . $offset . '"' . $selected . '>' . $zone . '</option>';
		}
	}

	return $tz_select;
}

// Topic and forum watching common code
function watch_topic_forum($mode, &$s_watching, &$s_watching_img, $user_id, $match_id, $notify_status = 'unset', $start = 0)
{
	global $_CLASS, $_CLASS, $start;

	$table_sql = ($mode == 'forum') ? FORUMS_WATCH_TABLE : TOPICS_WATCH_TABLE;
	$where_sql = ($mode == 'forum') ? 'forum_id' : 'topic_id';
	$u_url = ($mode == 'forum') ? 'f' : 't';

	// Is user watching this thread?
	if ($user_id != ANONYMOUS)
	{
		$can_watch = TRUE;

		if ($notify_status == 'unset')
		{
			$sql = "SELECT notify_status
				FROM $table_sql
				WHERE $where_sql = $match_id
					AND user_id = $user_id";
			$result = $_CLASS['db']->sql_query($sql);

			$notify_status = ($row = $_CLASS['db']->sql_fetchrow($result)) ? $row['notify_status'] : NULL;
			$_CLASS['db']->sql_freeresult($result);
		}

		if (!is_null($notify_status))
		{
			if (isset($_GET['unwatch']))
			{
				if ($_GET['unwatch'] == $mode)
				{
					$is_watching = 0;

					$sql = 'DELETE FROM ' . $table_sql . "
						WHERE $where_sql = $match_id
							AND user_id = $user_id";
					$_CLASS['db']->sql_query($sql);
				}
				$_CLASS['display']->meta_refresh(3, getlink("Forums&amp;file=view$mode&amp;$u_url=$match_id&amp;start=$start"));
				$message = $_CLASS['user']->lang['NOT_WATCHING_' . strtoupper($mode)] . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_' . strtoupper($mode)], '<a href="' . getlink("Forums&amp;file=view$mode&amp;" . $u_url . "=$match_id&amp;start=$start") . '">', '</a>');
				trigger_error($message);
			}
			else
			{
				$is_watching = TRUE;

				if ($notify_status)
				{
					$sql = 'UPDATE ' . $table_sql . "
						SET notify_status = 0
						WHERE $where_sql = $match_id
							AND user_id = $user_id";
					$_CLASS['db']->sql_query($sql);
				}
			}
		}
		else
		{
			if (isset($_GET['watch']))
			{
				if ($_GET['watch'] == $mode)
				{
					$is_watching = TRUE;

					$sql = 'INSERT INTO ' . $table_sql . " (user_id, $where_sql, notify_status)
						VALUES ($user_id, $match_id, 0)";
					$_CLASS['db']->sql_query($sql);
				}
				$_CLASS['display']->meta_refresh(3, getlink("Forums&amp;file=view$mode&amp;$u_url=$match_id&amp;start=$start"));
				$message = $_CLASS['user']->lang['ARE_WATCHING_' . strtoupper($mode)] . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_' . strtoupper($mode)], '<a href="' . getlink("Forums&amp;file=view$mode&amp;" . $u_url . "=$match_id&amp;start=$start") . '">', '</a>');
				trigger_error($message);
			}
			else
			{
				$is_watching = 0;
			}
		}
	}
	else
	{
		if (isset($_GET['unwatch']))
		{
			if ($_GET['unwatch'] == $mode)
			{
				login_box();
			}
		}
		else
		{
			$can_watch = 0;
			$is_watching = 0;
		}
	}

	if ($can_watch)
	{
		$s_watching['link'] = getlink("Forums&amp;file=view$mode&amp;$u_url=$match_id&amp;" . (($is_watching) ? 'unwatch' : 'watch') . "=$mode&amp;start=$start");
		$s_watching['title'] = $_CLASS['user']->lang[(($is_watching) ? 'STOP' : 'START') . '_WATCHING_' . strtoupper($mode)];
	}

	return;
}

// Marks a topic or form as read
function markread($mode, $forum_id = 0, $topic_id = 0, $marktime = false)
{
	global $config, $_CLASS;
	
	if ($_CLASS['user']->data['user_id'] == ANONYMOUS)
	{
		return;
	}

	if (!is_array($forum_id))
	{
		$forum_id = array($forum_id);
	}

	// Default tracking type
	$type = TRACK_NORMAL;
	$current_time = ($marktime) ? $marktime : time();
	$topic_id = (int) $topic_id;

	switch ($mode)
	{
		case 'mark':
			if ($config['load_db_lastread'])
			{
				$sql = 'SELECT forum_id 
					FROM ' . FORUMS_TRACK_TABLE . ' 
					WHERE user_id = ' . $_CLASS['user']->data['user_id'] . '
						AND forum_id IN (' . implode(', ', array_map('intval', $forum_id)) . ')';
				$result = $_CLASS['db']->sql_query($sql);
				
				$sql_update = array();
				while ($row = $_CLASS['db']->sql_fetchrow($result))
				{
					$sql_update[] = $row['forum_id'];
				}
				$_CLASS['db']->sql_freeresult($result);

				if (sizeof($sql_update))
				{
					$sql = 'UPDATE ' . FORUMS_TRACK_TABLE . "
						SET mark_time = $current_time 
						WHERE user_id = " . $_CLASS['user']->data['user_id'] . '
							AND forum_id IN (' . implode(', ', $sql_update) . ')';
					$_CLASS['db']->sql_query($sql);
				}

				if ($sql_insert = array_diff($forum_id, $sql_update))
				{
					foreach ($sql_insert as $forum_id)
					{
						$sql = '';
						switch (SQL_LAYER)
						{
							case 'mysql':
							case 'mysql4':
								$sql .= (($sql != '') ? ', ' : '') . '(' . $_CLASS['user']->data['user_id'] . ", $forum_id, $current_time)";
								$sql = 'VALUES ' . $sql;
								break;

							case 'mssql':
							case 'sqlite':
								$sql .= (($sql != '') ? ' UNION ALL ' : '') . ' SELECT ' . $_CLASS['user']->data['user_id'] . ", $forum_id, $current_time";
								break;

							default:
								$sql = 'INSERT INTO ' . FORUMS_TRACK_TABLE . ' (user_id, forum_id, mark_time)
									VALUES (' . $_CLASS['user']->data['user_id'] . ", $forum_id, $current_time)";
								$_CLASS['db']->sql_query($sql);
								$sql = '';
						}

						if ($sql)
						{
							$sql = 'INSERT INTO ' . FORUMS_TRACK_TABLE . " (user_id, forum_id, mark_time) $sql";
							$_CLASS['db']->sql_query($sql);
						}
					}
				}
				unset($sql_update);
				unset($sql_insert);
			}
			else
			{
				$tracking = (isset($_COOKIE[$config['cookie_name'] . '_track'])) ? unserialize(stripslashes($_COOKIE[$config['cookie_name'] . '_track'])) : array();

				foreach ($forum_id as $f_id)
				{
					unset($tracking[$f_id]);
					$tracking[$f_id][0] = base_convert($current_time - $config['board_startdate'], 10, 36);
				}

				$_CLASS['user']->set_cookie('track', serialize($tracking), time() + 31536000);
				unset($tracking);
			}
			break;

		case 'post':
			// Mark a topic as read and mark it as a topic where the user has made a post.
			$type = TRACK_POSTED;

		case 'topic':
			$forum_id =	(int) $forum_id[0];
	
			// Mark a topic as read
			if ($config['load_db_lastread'] || ($config['load_db_track'] && $type == TRACK_POSTED))
			{
				$sql = 'UPDATE ' . TOPICS_TRACK_TABLE . "
					SET mark_type = $type, mark_time = $current_time
					WHERE topic_id = $topic_id
						AND user_id = " . $_CLASS['user']->data['user_id'] . " 
						AND mark_time < $current_time";
				if (!$_CLASS['db']->sql_query($sql) || !$_CLASS['db']->sql_affectedrows())
				{
					$_CLASS['db']->sql_return_on_error(true);

					$sql = 'INSERT INTO ' . TOPICS_TRACK_TABLE . ' (user_id, topic_id, mark_type, mark_time)
						VALUES (' . $_CLASS['user']->data['user_id'] . ", $topic_id, $type, $current_time)";
					$_CLASS['db']->sql_query($sql);

					$_CLASS['db']->sql_return_on_error(false);
				}
			}

			if (!$config['load_db_lastread'])
			{
				$tracking = array();
				if (isset($_COOKIE[$config['cookie_name'] . '_track']))
				{
					$tracking = unserialize(stripslashes($_COOKIE[$config['cookie_name'] . '_track']));

					// If the cookie grows larger than 2000 characters we will remove
					// the smallest value
					if (strlen($_COOKIE[$config['cookie_name'] . '_track']) > 2000)
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
					}
				}

				if ((empty($tracking[$forum_id][0])) || base_convert($tracking[$forum_id][0], 36, 10) < $current_time)
				{
					$tracking[$forum_id][base_convert($topic_id, 10, 36)] = base_convert($current_time - $config['board_startdate'], 10, 36);

					$_CLASS['user']->set_cookie('track', serialize($tracking), time() + 31536000);
				}
				unset($tracking);
			}
			break;
	}
}


// Pagination routine, generates page number sequence
// tpl_prefix is for using different pagination blocks at one page
function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = true, $tpl_prefix = '')
{
	global $_CLASS;

	$seperator = $_CLASS['user']->theme['pagination_sep'];

	$total_pages = ceil($num_items/$per_page);

	if ($total_pages == 1 || !$num_items)
	{
		return false;
	}

	$on_page = floor($start_item / $per_page) + 1;

	$page_string = ($on_page == 1) ? '<strong>1</strong>' : '<a href="' . getlink($base_url, false) . '">1</a>';
	
	if ($total_pages > 5)
	{
		$start_cnt = min(max(1, $on_page - 4), $total_pages - 5);
		$end_cnt = max(min($total_pages, $on_page + 4), 6);

		$page_string .= ($start_cnt > 1) ? ' ... ' : $seperator;

		for($i = $start_cnt + 1; $i < $end_cnt; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page), false) . '">' . $i . '</a>';
			if ($i < $end_cnt - 1)
			{
				$page_string .= $seperator;
			}
		}

		$page_string .= ($end_cnt < $total_pages) ? ' ... ' : $seperator;
	}
	else
	{
		$page_string .= $seperator;

		for($i = 2; $i < $total_pages; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page), false) . '">' . $i . '</a>';
			if ($i < $total_pages)
			{
				$page_string .= $seperator;
			}
		}
	}

	$page_string .= ($on_page == $total_pages) ? '<strong>' . $total_pages . '</strong>' : '<a href="' . getlink($base_url . '&amp;start=' . (($total_pages - 1) * $per_page), false) . '">' . $total_pages . '</a>';
//	$page_string = $_CLASS['user']->lang['GOTO_PAGE'] . ' ' . $page_string;
//	$page_string = '<a href="javascript:jumpto();">' . $_CLASS['user']->lang['GOTO_PAGE'] . '</a> ' . $page_string;

	$_CLASS['template']->assign(array(
		$tpl_prefix . 'BASE_URL'	=> $base_url,
		$tpl_prefix . 'PER_PAGE'	=> $per_page,
		
		$tpl_prefix . 'PREVIOUS_PAGE'	=> ($on_page == 1) ? '' : $base_url . '&amp;start=' . (($on_page - 2) * $per_page),
		$tpl_prefix . 'NEXT_PAGE'	=> ($on_page == $total_pages) ? '' : $base_url . '&amp;start=' . ($on_page * $per_page))
	);
	return $page_string;
}

function on_page($num_items, $per_page, $start)
{
	global $_CLASS;

	$on_page = floor($start / $per_page) + 1;

	$_CLASS['template']->assign('ON_PAGE', $on_page);

	return sprintf($_CLASS['user']->lang['PAGE_OF'], $on_page, max(ceil($num_items / $per_page), 1));
}

// Obtain list of naughty words and build preg style replacement arrays for use by the
// calling script, note that the vars are passed as references this just makes it easier
// to return both sets of arrays
function obtain_word_list(&$censors)
{
	global $_CLASS;

	if (!$_CLASS['user']->optionget('viewcensors') && $config['allow_nocensors'])
	{
		return;
	}

	if ($_CLASS['cache']->exists('word_censors'))
	{
		$censors = $_CLASS['cache']->get('word_censors');
	}
	else
	{
		$sql = 'SELECT word, replacement
			FROM  ' . WORDS_TABLE;
		$result = $_CLASS['db']->sql_query($sql);

		$censors = array();
		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$censors['match'][] = '#\b(' . str_replace('\*', '\w*?', preg_quote($row['word'], '#')) . ')\b#i';
			$censors['replace'][] = $row['replacement'];
		}
		$_CLASS['db']->sql_freeresult($result);

		$_CLASS['cache']->put('word_censors', $censors);
	}

	return true;
}

// Obtain currently listed icons, re-caching if necessary
function obtain_icons(&$icons)
{
	global $_CLASS;

	if ($_CLASS['cache']->exists('icons'))
	{
		$icons = $_CLASS['cache']->get('icons');
	}
	else
	{
		// Topic icons
		$sql = 'SELECT *
			FROM ' . ICONS_TABLE . ' 
			ORDER BY icons_order';
		$result = $_CLASS['db']->sql_query($sql);

		$icons = array();
		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$icons[$row['icons_id']]['img'] = $row['icons_url'];
			$icons[$row['icons_id']]['width'] = (int) $row['icons_width'];
			$icons[$row['icons_id']]['height'] = (int) $row['icons_height'];
			$icons[$row['icons_id']]['display'] = (bool) $row['display_on_posting'];
		}
		$_CLASS['db']->sql_freeresult($result);

		$_CLASS['cache']->put('icons', $icons);
	}

	return;
}

// Obtain ranks
function obtain_ranks(&$ranks)
{
	global $_CLASS;

	if ($_CLASS['cache']->exists('ranks'))
	{
		$ranks = $_CLASS['cache']->get('ranks');
	}
	else
	{
		$sql = 'SELECT *
			FROM ' . RANKS_TABLE . '
			ORDER BY rank_min DESC';
		$result = $_CLASS['db']->sql_query($sql);

		$ranks = array();
		while ($row = $_CLASS['db']->sql_fetchrow($result))
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
		$_CLASS['db']->sql_freeresult($result);

		$_CLASS['cache']->put('ranks', $ranks);
	}
}

// Obtain allowed extensions
function obtain_attach_extensions(&$extensions)
{
	global $_CLASS;

	if ($_CLASS['cache']->exists('extensions'))
	{
		$extensions = $_CLASS['cache']->get('extensions');
	}
	else
	{
		// The rule is to only allow those extensions defined. ;)
		$sql = 'SELECT e.extension, g.*
			FROM ' . EXTENSIONS_TABLE . ' e, ' . EXTENSION_GROUPS_TABLE . ' g
			WHERE e.group_id = g.group_id
				AND g.allow_group = 1';
		$result = $_CLASS['db']->sql_query($sql);

		$extensions = array();
		while ($row = $_CLASS['db']->sql_fetchrow($result))
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
			$extensions['_allowed_'][$extension] = (!sizeof($allowed_forums)) ? 0 : $allowed_forums;
		}
		$_CLASS['db']->sql_freeresult($result);

		$_CLASS['cache']->put('extensions', $extensions);
	}

	return;
}

function generate_board_url()
{
	global $config;

	$path = preg_replace('#^/?(.*?)/?$#', '\1', trim($config['script_path']));

	return (($config['cookie_secure']) ? 'https://' : 'http://') . preg_replace('#^/?(.*?)/?$#', '\1', trim($config['server_name'])) . (($config['server_port'] <> 80) ? ':' . trim($config['server_port']) : '') . (($path) ? '/' . $path : '');
}

// Redirects the user to another page then exits the script nicely
function redirect($url)
{
	global $_CLASS, $config;

	script_close();

	// Make sure no &amp;'s are in, this will break the redirect
	$url = str_replace('&amp;', '&', $url);
	
	// Local redirect? If not, prepend the boards url
	$url = (strpos($url, '://') === false) ? (generate_board_url() . preg_replace('#^/?(.*?)/?$#', '/\1', trim($url))) : $url;

	// Redirect via an HTML form for PITA webservers
	if (@preg_match('#Microsoft|WebSTAR|Xitami#', getenv('SERVER_SOFTWARE')))
	{
		header('Refresh: 0; URL=' . $url);
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . $url . '"><title>Redirect</title></head><body><div align="center">' . sprintf($_CLASS['user']->lang['URL_REDIRECT'], '<a href="' . $url . '">', '</a>') . '</div></body></html>';
		exit;
	}

	// Behave as per HTTP/1.1 spec for others
	header('Location: ' . $url);
	exit;
}

// Build Confirm box
function confirm_box($check, $title = '', $hidden = '', $html_body = 'confirm_body.html')
{
	global $_CLASS, $_POST, $SID, $_CLASS;

	if (isset($_POST['cancel']))
	{
		return false;
	}
	
	$confirm = false;
	if (isset($_POST['confirm']))
	{
		// language frontier
		if ($_POST['confirm'] == $_CLASS['user']->lang['YES'])
		{
			$confirm = true;
		}
	}

	if ($check && $confirm)
	{
		$user_id = request_var('user_id', 0);
		$session_id = request_var('sess', '');
		$confirm_key = request_var('confirm_key', '');
		
		// this has to be reenables
		// The session page is already updated, but the user array holds the data before the update took place, therefore it is working here...
		/*if ($user_id != $_CLASS['user']->data['user_id'] || $session_id != $_CLASS['user']->session_id || $confirm_key != $_CLASS['user']->data['user_last_confirm_key'])
		{
			return false;
		}*/
		
		return true;
	}
	else if ($check)
	{
		return false;
	}
	
	$s_hidden_fields = '<input type="hidden" name="user_id" value="' . $_CLASS['user']->data['user_id'] . '" /><input type="hidden" name="sess" value="' . $_CLASS['user']->session_id . '" /><input type="hidden" name="sid" value="' . $SID . '" />';

	// generate activation key
	$confirm_key = gen_rand_string(10);

	// If activation key already exist, we better do not re-use the key (something very strange is going on...)
	if (request_var('confirm_key', ''))
	{
		$_CLASS['user']->url = preg_replace('#^(.*?)[&|\?]act_key=[A-Z0-9]{10}(.*?)#', '\1\2', str_replace('&amp;', '&', $_CLASS['user']->url));
		// Need to adjust...
		trigger_error('Hacking attempt');
	}

	$_CLASS['template']->assign(array(
		'MESSAGE_TITLE'		=> $_CLASS['user']->lang[$title],
		'MESSAGE_TEXT'		=> $_CLASS['user']->lang[$title . '_CONFIRM'],
		'L_NO'	 			=> $_CLASS['user']->lang['NO'],
		'YES_VALUE'			=> $_CLASS['user']->lang['YES'],
		'S_CONFIRM_ACTION'  => $_CLASS['user']->url . ((strpos($_CLASS['user']->url, '?') !== false) ? '&' : '?') . 'confirm_key=' . $confirm_key. $SID,
		'S_HIDDEN_FIELDS'	=> $hidden . $s_hidden_fields)
	);
	
	// Here we update the lastpage of the user, only here
	$sql = 'UPDATE ' . USERS_TABLE . " SET user_last_confirm_key = '" . $_CLASS['db']->sql_escape($confirm_key) . "'
		WHERE user_id = " . $_CLASS['user']->data['user_id'];
	$_CLASS['db']->sql_query($sql);
	
	require('header.php');
	
	page_header($_CLASS['user']->lang[$title]);
	$_CLASS['template']->display('forums/'.$html_body);

	page_footer();
	
	require('footer.php');
	
}

// Generate login box or verify password
function login_box($redirect = '', $l_explain = '', $l_success = '', $admin = false, $s_display = true)
{
	global $SID, $_CLASS, $pagetitle, $mainindex;

	$err = '';
	
	if (isset($_POST['login']))
	{
		$username	= request_var('username', '');
		$password	= request_var('password', '');
		$autologin	= (!empty($_POST['autologin'])) ? TRUE : FALSE;
		$viewonline = (!empty($_POST['viewonline'])) ? 0 : 1;
		$admin		= ($admin) ? 1 : 0;

		 // If authentication is successful we redirect user to previous page
		if (($result = $_CLASS['auth']->login($username, $password, $autologin, $viewonline, $admin)) === true)
		{
			$redirect = request_var('redirect', getlink());
			$_CLASS['display']->meta_refresh(3, $redirect);

			$message = (($l_success) ? $l_success : $_CLASS['user']->lang['LOGIN_REDIRECT']) . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a> ');
			trigger_error($message);
		}

		// If we get a non-numeric (e.g. string) value we output an error
		if (is_string($result))
		{
			trigger_error($result, E_USER_ERROR);
		}

		// If we get an integer zero then we are inactive, else the username/password is wrong
		$err = ($result === 0) ? $_CLASS['user']->lang['ACTIVE_ERROR'] :  $_CLASS['user']->lang['LOGIN_ERROR'];
	}

	if (!$redirect)
	{
		$redirect = htmlspecialchars($_CLASS['user']->url.$SID);
	}
	$s_hidden_fields = '<input type="hidden" name="redirect" value="' . $redirect . '" />';
	$s_hidden_fields .= '<input type="hidden" name="sid" value="' . $SID . '" />';

	$_CLASS['template']->assign(array(
		'LOGIN_ERROR'			=> $err, 
		'LOGIN_EXPLAIN'			=> $l_explain, 
		'U_SEND_PASSWORD'	 	=> getlink('Control_Panel&amp;mode=sendpassword'),
		'U_TERMS_USE'			=> getlink('Control_Panel&amp;mode=terms'), 
		'U_PRIVACY'				=> getlink('Control_Panel&amp;mode=privacy'), 
		'S_DISPLAY_FULL_LOGIN'  => ($s_display) ? true : false,
		'S_LOGIN_ACTION'		=> (!$admin) ? getlink("Control_Panel$SID&amp;mode=login") : $mainindex,
		'S_HIDDEN_FIELDS' 		=> $s_hidden_fields,
		'L_LOGIN'				=> $_CLASS['user']->lang['LOGIN'],
		'L_LOGIN_INFO'			=> $_CLASS['user']->lang['LOGIN_INFO'], 
		'L_TERMS_USE'			=> $_CLASS['user']->lang['TERMS_USE'],
		'L_USERNAME'			=> $_CLASS['user']->lang['USERNAME'],
		'L_PASSWORD' 			=> $_CLASS['user']->lang['PASSWORD'],
		'L_REGISTER'			=> $_CLASS['user']->lang['REGISTER'],
		'L_FORGOT_PASS'			=> $_CLASS['user']->lang['FORGOT_PASS'],
		'L_HIDE_ME'				=> $_CLASS['user']->lang['HIDE_ME'],
		'L_LOG_ME_IN'			=> $_CLASS['user']->lang['LOG_ME_IN'],
		'L_PRIVACY'				=> $_CLASS['user']->lang['PRIVACY']
		)
	);
	
	$pagetitle = $_CLASS['user']->lang['LOGIN'];
	require('header.php');
	
	page_header();
	page_footer();
	
	make_jumpbox(getlink("Forums&amp;file=viewforum"));
	
	$_CLASS['template']->display('forums/login_body.html');
	
	require('footer.php');
}

// Generate forum login box
function login_forum_box(&$forum_data)
{
	global $config, $_CLASS;

	$password = request_var('password', '');

	$sql = 'SELECT forum_id
		FROM ' . FORUMS_ACCESS_TABLE ."
		WHERE forum_id = '".$forum_data['forum_id']."'
			AND user_id = '".$_CLASS['user']->data['user_id']."'
			AND session_id = '".$_CLASS['user']->session_id."'";
	$result = $_CLASS['db']->sql_query($sql);

	if ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$_CLASS['db']->sql_freeresult($result);
		return true;
	}
	$_CLASS['db']->sql_freeresult($result);

	if ($password)
	{
		// Remove expired authorised sessions
		$sql = 'SELECT session_id 
			FROM ' . SESSIONS_TABLE;
		$result = $_CLASS['db']->sql_query($sql);

		if ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$sql_in = array();
			do
			{
				$sql_in[] = "'" . $_CLASS['db']->sql_escape($row['session_id']) . "'";
			}
			while ($row = $_CLASS['db']->sql_fetchrow($result));

			$sql = 'DELETE FROM ' . FORUMS_ACCESS_TABLE . '
				WHERE session_id NOT IN (' . implode(', ', $sql_in) . ')';
			$_CLASS['db']->sql_query($sql);
		}
		$_CLASS['db']->sql_freeresult($result);

		if ($password == $forum_data['forum_password'])
		{
			$sql = 'INSERT INTO ' . FORUMS_ACCESS_TABLE . ' (forum_id, user_id, session_id)
				VALUES (' . $forum_data['forum_id'] . ', ' . $_CLASS['user']->data['user_id'] . ", '" . $_CLASS['db']->sql_escape($_CLASS['user']->session_id) . "')";
			$_CLASS['db']->sql_query($sql);

			return true;
		}

		$_CLASS['template']->assign('LOGIN_ERROR', $_CLASS['user']->lang['WRONG_PASSWORD']);
	}
	require('header.php');

	page_header();
	
	$_CLASS['template']->display('forums/login_forum.html');

	page_footer();
	require('footer.php');

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
	if ($topic_poster != $_CLASS['user']->data['user_id'] && $last_topic_poster != $_CLASS['user']->data['user_id'] && !$_CLASS['auth']->acl_get('m_', $forum_id))
	{
		return false;
	}

	// A bump time of 0 will completely disable the bump feature... not intended but might be useful.
	return $bump_time;
}

// Censoring
function censor_text($text)
{
	global $censors, $_CLASS;

	if (!isset($censors))
	{
		$censors = array();

		// For ANONYMOUS, this option should be enabled by default
		if ($_CLASS['user']->optionget('viewcensors'))
		{
			obtain_word_list($censors);
		}
	}

	if (sizeof($censors) && $_CLASS['user']->optionget('viewcensors'))
	{
		return preg_replace($censors['match'], $censors['replace'], $text);
	}

	return $text;
}

// Smilie processing
function smilie_text($text, $force_option = false)
{
	global $config, $user;

	return ($force_option || !$config['allow_smilies'] || !$user->optionget('viewsmilies')) ? preg_replace('#<!\-\- s(.*?) \-\-><img src="\{SMILE_PATH\}\/.*? \/><!\-\- s\1 \-\->#', '\1', $text) : str_replace('<img src="{SMILE_PATH}', '<img src="' . $config['smilies_path'], $text);
}

// Inline Attachment processing
function parse_inline_attachments(&$text, &$attachments, &$update_count, $forum_id = 0, $preview = false)
{
	global $config, $user;

	$attachments = display_attachments($forum_id, NULL, $attachments, $update_count, $preview, true);
	$tpl_size = sizeof($attachments);

	$unset_tpl = array();

	preg_match_all('#<!\-\- ia([0-9]+) \-\->(.*?)<!\-\- ia\1 \-\->#', $text, $matches, PREG_PATTERN_ORDER);

	$replace = array();
	foreach ($matches[0] as $num => $capture)
	{
		// Flip index if we are displaying the reverse way
		$index = ($config['display_order']) ? ($tpl_size-($matches[1][$num] + 1)) : $matches[1][$num];

		$replace['from'][] = $matches[0][$num];
		$replace['to'][] = (isset($attachments[$index])) ? $attachments[$index] : sprintf($user->lang['MISSING_INLINE_ATTACHMENT'], $matches[2][array_search($index, $matches[1])]);

		$unset_tpl[] = $index;
	}

	if (isset($replace['from']))
	{
		$text = str_replace($replace['from'], $replace['to'], $text);
	}

	return array_unique($unset_tpl);
}

// Check if extension is allowed to be posted within forum X (forum_id 0 == private messaging)
function extension_allowed($forum_id, $extension, &$extensions)
{
	if (!sizeof($extensions))
	{
		$extensions = array();
		obtain_attach_extensions($extensions);
	}

	if (!isset($extensions['_allowed_'][$extension]))
	{
		return false;
	}

	$check = $extensions['_allowed_'][$extension];

	if (is_array($check))
	{
		// Check for private messaging
		if (sizeof($check) == 1 && $check[0] == 0)
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

function page_header($page_title = '')
{
	global $config, $SID, $_CLASS, $MAIN_CFG;

	define('HEADER_INC', TRUE);

	// Generate logged in/logged out status
	if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
	{
		$u_login_logout = getlink('Control_Panel&amp;mode=logout');
		$l_login_logout = sprintf($_CLASS['user']->lang['LOGOUT_USER'], $_CLASS['user']->data['username']);
	}
	else
	{
		$u_login_logout = getlink('Control_Panel&amp;mode=login');
		$l_login_logout = $_CLASS['user']->lang['LOGIN'];
	}

	// Last visit date/time
	$s_last_visit = ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? $_CLASS['user']->format_date($_CLASS['user']->data['session_last_visit']) : '';

	// Get users online list ... if required
	$l_online_users = $online_userlist = $l_online_record = '';

	if (!empty($config['load_online']) && !empty($config['load_online_time']))
	{
		$userlist_ary = $userlist_visible = array();
		$logged_visible_online = $logged_hidden_online = $guests_online = $prev_user_id = 0;
		$prev_user_ip = $prev_session_ip = $reading_sql = '';

		if (!empty($_REQUEST['f']))
		{
			$f = request_var('f', 0);
			$reading_sql = "AND s.session_url LIKE '%f=$f%'";
		}

		$sql = 'SELECT u.username, u.user_id, u.user_type, u.user_allow_viewonline, u.user_colour, s.session_ip, s.session_viewonline
			FROM ' . USERS_TABLE . ' u, ' . SESSIONS_TABLE . ' s
			WHERE s.session_time >= ' . (time() - (intval($config['load_online_time']) * 60)) . "
				$reading_sql
				AND u.user_id = s.session_user_id
			ORDER BY u.username ASC, s.session_ip ASC";
		$result = $_CLASS['db']->sql_query($sql, false);

		while ($row = $_CLASS['db']->sql_fetchrow($result))
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

					if ($row['user_allow_viewonline'] && $row['session_viewonline'])
					{
						$user_online_link = $row['username'];
						$logged_visible_online++;
					}
					else
					{
						$user_online_link = '<i>' . $row['username'] . '</i>';
						$logged_hidden_online++;
					}

					if ($row['user_allow_viewonline'] || $_CLASS['auth']->acl_get('u_viewonline'))
					{
						$user_online_link = ($row['user_type'] <> USER_IGNORE) ? "<a href=\"" . getlink('Members_List&amp;&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $user_online_link . '</a>' : $user_online_link;
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

		if (!$online_userlist)
		{
			$online_userlist = $_CLASS['user']->lang['NONE'];
		}

		if (empty($_REQUEST['f']))
		{
			$online_userlist = $_CLASS['user']->lang['REGISTERED_USERS'] . ' ' . $online_userlist;
		}
		else
		{
			$l_online = ($guests_online == 1) ? $_CLASS['user']->lang['BROWSING_FORUM_GUEST'] : $_CLASS['user']->lang['BROWSING_FORUM_GUESTS'];
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
					${$var_ary[1]} = $_CLASS['user']->lang[$l_prefix . '_USERS_ZERO_TOTAL'];
					break;

				case 1:
					${$var_ary[1]} = $_CLASS['user']->lang[$l_prefix . '_USER_TOTAL'];
					break;

				default:
					${$var_ary[1]} = $_CLASS['user']->lang[$l_prefix . '_USERS_TOTAL'];
					break;
			}
		}
		unset($vars_online);

		$l_online_users = sprintf($l_t_user_s, $total_online_users);
		$l_online_users .= sprintf($l_r_user_s, $logged_visible_online);
		$l_online_users .= sprintf($l_h_user_s, $logged_hidden_online);
		$l_online_users .= sprintf($l_g_user_s, $guests_online);

		$l_online_record = sprintf($_CLASS['user']->lang['RECORD_ONLINE_USERS'], $config['record_online_users'], $_CLASS['user']->format_date($config['record_online_date']));

		$l_online_time = ($config['load_online_time'] == 1) ? 'VIEW_ONLINE_TIME' : 'VIEW_ONLINE_TIMES';
		$l_online_time = sprintf($_CLASS['user']->lang[$l_online_time], $config['load_online_time']);
	}

	$l_privmsgs_text = $l_privmsgs_text_unread = '';
	$s_privmsg_new = false;

	// Obtain number of new private messages if user is logged in
	if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
	{
		if ($_CLASS['user']->data['user_new_privmsg'])
		{
			$l_message_new = ($_CLASS['user']->data['user_new_privmsg'] == 1) ? $_CLASS['user']->lang['NEW_PM'] : $_CLASS['user']->lang['NEW_PMS'];
			$l_privmsgs_text = sprintf($l_message_new, $_CLASS['user']->data['user_new_privmsg']);

			if (!$_CLASS['user']->data['user_last_privmsg'] || $_CLASS['user']->data['user_last_privmsg'] > $_CLASS['user']->data['session_last_visit'])
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_last_privmsg = ' . $_CLASS['user']->data['session_last_visit'] . '
					WHERE user_id = ' . $_CLASS['user']->data['user_id'];
				$_CLASS['db']->sql_query($sql);

				$s_privmsg_new = true;
			}
			else
			{
				$s_privmsg_new = false;
			}
		}
		else
		{
			$l_privmsgs_text = $_CLASS['user']->lang['NO_NEW_PM'];
			$s_privmsg_new = false;
		}

		$l_privmsgs_text_unread = '';

		if ($_CLASS['user']->data['user_unread_privmsg'] && $_CLASS['user']->data['user_unread_privmsg'] != $_CLASS['user']->data['user_new_privmsg'])
		{
			$l_message_unread = ($_CLASS['user']->data['user_unread_privmsg'] == 1) ? $_CLASS['user']->lang['UNREAD_PM'] : $_CLASS['user']->lang['UNREAD_PMS'];
			$l_privmsgs_text_unread = sprintf($l_message_unread, $_CLASS['user']->data['user_unread_privmsg']);
		}
	}

	// Which timezone?
	$tz = ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? strval(doubleval($_CLASS['user']->data['user_timezone'])) : strval(doubleval($config['board_timezone']));

	// The following assigns all _common_ variables that may be used at any point
	// in a template.
	$_CLASS['template']->assign(array(
		'SITENAME' 					=> $MAIN_CFG['global']['sitename'],
		'SITE_DESCRIPTION' 			=> $MAIN_CFG['global']['slogan'],
		'SCRIPT_NAME'                 => substr($_CLASS['user']->url, 0, strpos($_CLASS['user']->url, '.')),
		'LAST_VISIT_DATE' 				=> sprintf($_CLASS['user']->lang['YOU_LAST_VISIT'], $s_last_visit),
		'CURRENT_TIME'					=> sprintf($_CLASS['user']->lang['CURRENT_TIME'], $_CLASS['user']->format_date(time(), false, true)),
		'TOTAL_USERS_ONLINE' 			=> $l_online_users,
		'LOGGED_IN_USER_LIST' 			=> $online_userlist,
		'RECORD_USERS' 					=> $l_online_record,
		'PRIVATE_MESSAGE_INFO' 			=> $l_privmsgs_text,
		'PRIVATE_MESSAGE_INFO_UNREAD' 	=> $l_privmsgs_text_unread,
		'SID'                           => $SID,
		
		'L_LOGIN_LOGOUT' 		=> $l_login_logout,
		'L_REGISTER' 			=> $_CLASS['user']->lang['REGISTER'],
		'L_INDEX' 				=> $_CLASS['user']->lang['FORUM_INDEX'], 
		'L_ONLINE_EXPLAIN'		=> $l_online_time, 
		'U_PRIVATEMSGS'			=> getlink("Control_Panel&amp;i=pm&mode=" . (($_CLASS['user']->data['user_new_privmsg'] || $l_privmsgs_text_unread) ? 'unread' : 'view_messages')),
		'U_RETURN_INBOX'		=> getlink("Control_Panel&amp;i=pm&folder=inbox"),
		'U_MEMBERLIST' 			=> getlink('Members_List'),
		'U_VIEWONLINE' 			=> getlink('View_Online'),
		'U_MEMBERSLIST'			=> getlink('Members_List'),
		'U_LOGIN_LOGOUT'		=> $u_login_logout,
		'U_INDEX' 				=> getlink('Forums'),
		'U_SEARCH' 				=> getlink('Forums&amp;file=search'),
		'U_REGISTER' 			=> getlink('Control_Panel&amp;mode=register'),
		'U_PROFILE' 			=> getlink('Control_Panel'),
		'U_MODCP' 				=> getlink('Forums&amp;file=mcp'),
		'U_FAQ' 				=> getlink('Forums&amp;file=faq'),
		'U_SEARCH_SELF'			=> getlink('Forums&amp;file=search&amp;search_id=egosearch'),
		'U_SEARCH_NEW' 			=> getlink('Forums&amp;file=search&amp;search_id=newposts'),
		'U_SEARCH_UNANSWERED'	=> getlink('Forums&amp;file=search&amp;search_id=unanswered'),
		'U_DELETE_COOKIES'		=> getlink('Control_Panel&amp;mode=delete_cookies'),

		'S_USER_LOGGED_IN' 		=> ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? true : false,
		'S_USER_PM_POPUP' 		=> $_CLASS['user']->optionget('popuppm'),
		'S_USER_LANG'			=> $_CLASS['user']->data['user_lang'], 
		'S_USER_BROWSER' 		=> (isset($_CLASS['user']->data['session_browser'])) ? $_CLASS['user']->data['session_browser'] : $_CLASS['user']->lang['UNKNOWN_BROWSER'],
		'S_CONTENT_DIRECTION' 	=> $_CLASS['user']->lang['DIRECTION'],
		'S_CONTENT_ENCODING' 	=> $_CLASS['user']->lang['ENCODING'],
		'S_CONTENT_DIR_LEFT' 	=> $_CLASS['user']->lang['LEFT'],
		'S_CONTENT_DIR_RIGHT' 	=> $_CLASS['user']->lang['RIGHT'],
		'S_TIMEZONE' 			=> ($_CLASS['user']->data['user_dst'] || ($_CLASS['user']->data['user_id'] == ANONYMOUS && $config['board_dst'])) ? sprintf($_CLASS['user']->lang['ALL_TIMES'], $_CLASS['user']->lang['tz'][$tz], $_CLASS['user']->lang['tz']['dst']) : sprintf($_CLASS['user']->lang['ALL_TIMES'], $_CLASS['user']->lang['tz'][$tz], ''), 
		'S_DISPLAY_ONLINE_LIST'	=> (!empty($config['load_online'])) ? 1 : 0, 
		'S_DISPLAY_SEARCH'		=> (!empty($config['load_search'])) ? 1 : 0, 
		'S_DISPLAY_PM'			=> (!empty($config['allow_privmsg'])) ? 1 : 0, 
		'S_DISPLAY_MEMBERLIST'	=> (isset($_CLASS['auth'])) ? $_CLASS['auth']->acl_get('u_viewprofile') : 0, 
		
		'L_JUMP_PAGE'			=> $_CLASS['user']->lang['JUMP_PAGE'],
		'L_NEXT'				=> $_CLASS['user']->lang['NEXT'],
		'L_PREVIOUS'			=> $_CLASS['user']->lang['PREVIOUS'],
		'L_GOTO_PAGE'			=> $_CLASS['user']->lang['GOTO_PAGE'],
		'L_FAQ'					=> $_CLASS['user']->lang['FAQ'],
		'L_SEARCH'				=> $_CLASS['user']->lang['SEARCH'],
		'L_MEMBERLIST'			=> $_CLASS['user']->lang['MEMBERLIST'],
		'L_PROFILE'				=> $_CLASS['user']->lang['PROFILE'],
		'L_SEARCH_NEW'			=> $_CLASS['user']->lang['SEARCH_NEW'],
		'L_SEARCH_SELF'			=> $_CLASS['user']->lang['SEARCH_SELF'],
		'L_SEARCH_UNANSWERED'	=> $_CLASS['user']->lang['SEARCH_UNANSWERED'],
	
		'T_THEME_PATH'			=> is_dir('themes/'.$_CLASS['display']->theme.'/template/forums/theme/images') ? 'themes/'.$_CLASS['display']->theme.'/template/forums/theme' : 'includes/templates/forums/theme/', 
		)
	);
	return;
}

/// To be removed //
function page_footer()
{
	global $config, $_CLASS;

	$_CLASS['template']->assign(array(
		'PHPBB_VERSION'	=> $config['version'],
		'DEBUG_OUTPUT'	=> '', 
		'TRANSLATION_INFO' => 'Ported by <a href="http://www.viperal.com/" target="_Viperal">Viperal</a>'
		)
	);
}

?>