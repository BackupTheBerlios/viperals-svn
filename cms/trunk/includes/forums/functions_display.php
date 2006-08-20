<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||

$Id$
*/
// -------------------------------------------------------------
//
// $Id: functions_display.php,v 1.59 2004/09/17 09:11:47 acydburn Exp $
//
// FILENAME  : functions_display.php
// STARTED   : Thu Nov 07, 2002
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW		 : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

function display_forums($root_data = '', $display_moderators = true, $return_moderators = false)
{
	global $config, $_CLASS, $_CORE_CONFIG;

	$mark_read = request_var('mark', '');

	$forum_id_ary = $active_forum_ary = $forum_rows = $subforums = $forum_moderators = $forum_ids_moderator = $mark_forums = array();
	$visible_forums = 0;

	if (!$root_data)
	{
		$root_data = array('forum_id' => 0);
		$sql_where = '';
	}
	else
	{
		$sql_where = 'AND left_id > ' . $root_data['left_id'] . ' AND left_id < ' . $root_data['right_id'];
	}

	// Display list of active topics for this category?
	$show_active = (isset($root_data['forum_flags']) && $root_data['forum_flags'] & 16) ? true : false;

	if ($_CLASS['core_user']->is_user &&  $config['load_db_lastread'])
	{
		$sql_from = ' LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
				AND ft.forum_id = f.forum_id AND ft.topic_id = 0)';
		$lastread_select = ', ft.mark_time ';
	}
	else
	{
		$sql_from = $lastread_select = $sql_lastread = '';
		$tracking_topics = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));
	}

	$sql = "SELECT f.* $lastread_select 
		FROM ". FORUMS_FORUMS_TABLE . " f $sql_from
		WHERE forum_status <> " . ITEM_DELETING . "
		$sql_where
		ORDER BY f.left_id";
	$result = $_CLASS['core_db']->query($sql);

	$branch_root_id = $root_data['forum_id'];

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($mark_read == 'forums' && $_CLASS['core_user']->is_user)
		{
			if ($_CLASS['auth']->acl_get('f_list', $row['forum_id']))
			{
				$forum_id_ary[] = $row['forum_id'];
			}

			continue;
		}

		if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
		{
			// Non-postable forum with no subforums: don't display
			continue;
		}

		if (isset($right_id))
		{
			if ($row['left_id'] < $right_id)
			{
				continue;
			}
			unset($right_id);
		}

		if (!$_CLASS['auth']->acl_get('f_list', $row['forum_id']))
		{
			// if the user does not have permissions to list this forum, skip everything until next branch
			$right_id = $row['right_id'];
			continue;
		}

		// Display active topics from this forum?
		if ($show_active && $row['forum_type'] == FORUM_POST && $_CLASS['auth']->acl_get('f_read', $row['forum_id']) && ($row['forum_flags'] & 16))
		{
			if (!isset($active_forum_ary['forum_topics']))
			{
				$active_forum_ary['forum_topics'] = 0;
			}

			if (!isset($active_forum_ary['forum_posts']))
			{
				$active_forum_ary['forum_posts'] = 0;
			}

			$active_forum_ary['forum_id'][]		= $row['forum_id'];
			$active_forum_ary['enable_icons'][] = $row['enable_icons'];
			$active_forum_ary['forum_topics']	+= ($_CLASS['auth']->acl_get('m_approve', $row['forum_id'])) ? $row['forum_topics_real'] : $row['forum_topics'];
			$active_forum_ary['forum_posts']	+= $row['forum_posts'];
		}

		if ($row['parent_id'] == $root_data['forum_id'] || $row['parent_id'] == $branch_root_id)
		{
			if ($row['forum_type'] != FORUM_CAT)
			{
				$forum_ids_moderator[] = $row['forum_id'];
			}

			// Direct child
			$parent_id = $row['forum_id'];
			$forum_rows[$row['forum_id']] = $row;

			if ($row['forum_type'] == FORUM_CAT && $row['parent_id'] == $root_data['forum_id'])
			{
				$branch_root_id = $row['forum_id'];
			}
			$forum_rows[$parent_id]['forum_id_last_post'] = $row['forum_id'];
		}
		elseif ($row['forum_type'] != FORUM_CAT)
		{
			$subforums[$parent_id][$row['forum_id']]['display'] = ($row['display_on_index']) ? true : false;
			$subforums[$parent_id][$row['forum_id']]['name'] = $row['forum_name'];

			$forum_rows[$parent_id]['forum_topics'] += ($_CLASS['auth']->acl_get('m_approve', $row['forum_id'])) ? $row['forum_topics_real'] : $row['forum_topics'];
			
			// Do not list redirects in LINK Forums as Posts.
			if ($row['forum_type'] != FORUM_LINK)
			{
				$forum_rows[$parent_id]['forum_posts'] += $row['forum_posts'];
			}

			if ($row['forum_last_post_time'] > $forum_rows[$parent_id]['forum_last_post_time'])
			{
				$forum_rows[$parent_id]['forum_last_post_id'] = $row['forum_last_post_id'];
				$forum_rows[$parent_id]['forum_last_post_time'] = $row['forum_last_post_time'];
				$forum_rows[$parent_id]['forum_last_poster_id'] = $row['forum_last_poster_id'];
				$forum_rows[$parent_id]['forum_last_poster_name'] = $row['forum_last_poster_name'];
				$forum_rows[$parent_id]['forum_id_last_post'] = $row['forum_id'];
			}
			else
			{
				$forum_rows[$parent_id]['forum_id_last_post'] = $row['forum_id'];
			}
		}

		if (!$_CLASS['core_user']->is_user || !$config['load_db_lastread'])
		{
			$forum_id36 = base_convert($row['forum_id'], 10, 36);
			$row['mark_time'] = isset($tracking_topics[$forum_id36][0]) ? (int) base_convert($tracking_topics[$forum_id36][0], 36, 10) : 0;
		}

		if ($row['mark_time'] < $row['forum_last_post_time'])
		{
			$forum_unread[$parent_id] = true;
		}
	}
	$_CLASS['core_db']->free_result($result);

	// Handle marking posts
	if ($mark_read == 'forums')
	{
		markread('mark', $forum_id_ary);

		$redirect = generate_link('Forums');
		$_CLASS['core_display']->meta_refresh(3, $redirect);

		$message = (strpos($redirect, 'viewforum') !== false) ? 'RETURN_FORUM' : 'RETURN_INDEX';
		$message = $_CLASS['core_user']->lang['FORUMS_MARKED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang[$message], '<a href="' . $redirect . '">', '</a> ');
		trigger_error($message);
	}

	// Grab moderators ... if necessary
	if ($display_moderators)
	{
		if ($return_moderators)
		{
			$forum_ids_moderator[] = $root_data['forum_id'];
		}
		$forum_moderators = get_moderators($forum_ids_moderator);
	}

	// Loop through the forums
	foreach ($forum_rows as $row)
	{
		// Empty category
		if ($row['parent_id'] == $root_data['forum_id'] && $row['forum_type'] == FORUM_CAT)
		{
			$_CLASS['core_template']->assign_vars_array('forumrow', array(
				'S_IS_CAT'			=>	true,
				'FORUM_ID'			=>	$row['forum_id'],
				'FORUM_NAME'		=>	$row['forum_name'],
				'FORUM_DESC'		=>	$row['forum_desc'],
				//'FORUM_FOLDER_IMG'		=> ($row['forum_image']) ? '<img src="' . $phpbb_root_path . $row['forum_image'] . '" alt="' . $_CLASS['core_user']->lang['FORUM_CAT'] . '" />' : '',
				//'FORUM_FOLDER_IMG_SRC'	=> ($row['forum_image']) ? $phpbb_root_path . $row['forum_image'] : '',
				'U_VIEWFORUM'		=>	generate_link('forums&amp;file=viewforum&amp;f=' . $row['forum_id']))
			);
			
			continue;
		}


		$visible_forums++;
		$forum_id = $row['forum_id'];

		$subforums_list = $l_subforums = '';
		
		// Generate list of subforums if we need to
		if (isset($subforums[$forum_id]))
		{
			$links = array();

			foreach ($subforums[$forum_id] as $subforum_id => $subforum_row)
			{
				if ($subforum_row['display'] && $subforum_row['name'])
				{
					$links[] = '<a href="' .generate_link('forums&amp;file=viewforum&amp;f='.$subforum_id).'">' .  $subforum_row['name'] . '</a>';
				}
				unset($subforums[$forum_id][$subforum_id]);
			}
		
			$subforums_list = implode(', ', $links);
			$l_subforums = (count($links) === 1) ? $_CLASS['core_user']->lang['SUBFORUM'] . ': ' : $_CLASS['core_user']->lang['SUBFORUMS'] . ': ';

			unset($links);
	
			$folder_image = (!empty($forum_unread[$forum_id])) ? 'sub_forum_new' : 'sub_forum';
		}
		else
		{
			switch ($row['forum_type'])
			{
				case FORUM_POST:
					$folder_image = (!empty($forum_unread[$forum_id])) ? 'forum_new' : 'forum';
				break;

				case FORUM_LINK:
					$folder_image = 'forum_link';
				break;
			}
		}


		// Which folder should we display?
		if ($row['forum_status'] == ITEM_LOCKED)
		{
// forum_locked_new , need an image for this one
			$folder_image = empty($forum_unread[$forum_id]) ? 'forum_locked' : 'folder_locked_new';
			$folder_alt = 'FORUM_LOCKED';
		}
		else
		{
			$folder_alt = empty($forum_unread[$forum_id]) ? 'NO_NEW_POSTS' : 'NEW_POSTS';
		}

		// Create last post link information, if appropriate
		if ($row['forum_last_post_id'])
		{
			$last_post_time = $_CLASS['core_user']->format_date($row['forum_last_post_time']);

			$last_poster = ($row['forum_last_poster_name'] != '') ? $row['forum_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'];
			$last_poster_url = ($row['forum_last_poster_id'] == ANONYMOUS) ? '' : generate_link('members_list&amp;mode=viewprofile&amp;u='  . $row['forum_last_poster_id']);
			
			$last_post_url = generate_link('forums&amp;file=viewtopic&amp;f='.$row['forum_id_last_post'].'&amp;p='.$row['forum_last_post_id'].'#'.$row['forum_last_post_id'], false, false, false);
		}
		else
		{
			$last_post_time = $last_poster = $last_poster_url = $last_post_url = '';
		}


		// Output moderator listing ... if applicable
		$l_moderator = $moderators_list = '';
		if ($display_moderators && !empty($forum_moderators[$forum_id]))
		{
			$l_moderator = (count($forum_moderators[$forum_id]) == 1) ? $_CLASS['core_user']->lang['MODERATOR'] : $_CLASS['core_user']->lang['MODERATORS'];
			$moderators_list = implode(', ', $forum_moderators[$forum_id]);
		}

		$l_post_click_count = ($row['forum_type'] == FORUM_LINK) ? 'CLICKS' : 'POSTS';
		$post_click_count = ($row['forum_type'] != FORUM_LINK || $row['forum_flags'] & 1) ? $row['forum_posts'] : '';

		$_CLASS['core_template']->assign_vars_array('forumrow', array(
			'S_IS_CAT'			=> false, 
			'S_IS_LINK'			=> ($row['forum_type'] == FORUM_LINK), 
			'S_UNREAD_FORUM'	=> !empty($forum_unread[$forum_id]),
			'S_LOCKED_FORUM'	=> ($row['forum_status'] == ITEM_LOCKED) ? true : false,
			
			'FORUM_ID'			=> $row['forum_id'], 
			'FORUM_FOLDER_IMG'	=> ($row['forum_image']) ? '<img src="' . $row['forum_image'] . '" alt="' . $folder_alt . '" />' : $_CLASS['core_user']->img($folder_image, $folder_alt),
			//'FORUM_FOLDER_IMG_SRC'	=> ($row['forum_image']) ? $row['forum_image'] : $_CLASS['core_user']->img($folder_image, $folder_alt, false, '', 'src'),
			'FORUM_NAME'		=> $row['forum_name'],
			'FORUM_DESC'		=> $row['forum_desc'], 
			'FORUM_LOCKED' 		=> ($row['forum_status'] == ITEM_LOCKED) ? 1 : 0,

			
			$l_post_click_count	=> $post_click_count,
			'TOPICS'			=> $row['forum_topics'],
			'LAST_POST_TIME'	=> $last_post_time,
			'LAST_POSTER'		=> $last_poster,
			'MODERATORS'		=> $moderators_list,
			'SUBFORUMS'			=> $subforums_list,

			'L_SUBFORUM_STR'	=> $l_subforums,
			'L_MODERATOR_STR'	=> $l_moderator,
			'L_FORUM_FOLDER_ALT'=> $folder_alt,
			
			'U_LAST_POSTER'		=> $last_poster_url, 
			'U_LAST_POST'		=> $last_post_url, 
			'U_VIEWFORUM'		=> ($row['forum_type'] != FORUM_LINK || $row['forum_flags'] & 1) ? generate_link('forums&amp;file=viewforum&amp;f=' . $row['forum_id']) : $row['forum_link'])
		);
	}

	$_CLASS['core_template']->assign_array(array(
		'MODIFY_FORUM'		=> $_CLASS['auth']->acl_get('a_forum'),
		'U_MARK_FORUMS'		=> generate_link('forums&amp;file=viewforum&amp;f=' . $root_data['forum_id'] . '&amp;mark=Forums'), 
		'S_HAS_SUBFORUM'	=> ($visible_forums) ? true : false,
		'LAST_POST_IMG'			=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'), 

		'L_SUBFORUM'		=> ($visible_forums == 1) ? $_CLASS['core_user']->lang['SUBFORUM'] : $_CLASS['core_user']->lang['SUBFORUMS']
	));

	if ($return_moderators)
	{
		return array($active_forum_ary, $forum_moderators);
	}

	return array($active_forum_ary, array());
}

/**
* Create forum rules for given forum
*/
function generate_forum_rules(&$forum_data)
{
	if (!$forum_data['forum_rules'] && !$forum_data['forum_rules_link'])
	{
		return;
	}

	global $_CLASS;

	if ($forum_data['forum_rules'])
	{
		$forum_data['forum_rules'] = generate_text_for_display($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options']);
	}

	$_CLASS['core_template']->assign_array(array(
		'S_FORUM_RULES'	=> true,
		'U_FORUM_RULES'	=> $forum_data['forum_rules_link'],
		'FORUM_RULES'	=> $forum_data['forum_rules']
	));
}

/**
* Create forum navigation links for given forum, create parent
* list if currently null, assign basic forum info to template
*/
function generate_forum_nav(&$forum_data)
{
	global $_CLASS;

	if (!$_CLASS['forums_auth']->acl_get('f_list', $forum_data['forum_id']))
	{
		return;
	}

	// Get forum parents
	$forum_parents = get_forum_parents($forum_data);

	// Build navigation links
	foreach ($forum_parents as $parent_forum_id => $parent_data)
	{
		list($parent_name, $parent_type) = array_values($parent_data);

		// Skip this parent if the user does not have the permission to view it
		if (!$_CLASS['forums_auth']->acl_get('f_list', $parent_forum_id))
		{
			continue;
		}

		$_CLASS['core_template']->assign_vars_array('navlinks', array(
			'S_IS_CAT'		=> ($parent_type == FORUM_CAT) ? true : false,
			'S_IS_LINK'		=> ($parent_type == FORUM_LINK) ? true : false,
			'S_IS_POST'		=> ($parent_type == FORUM_POST) ? true : false,
			'FORUM_NAME'	=> $parent_name,
			'FORUM_ID'		=> $parent_forum_id,
			'U_VIEW_FORUM'	=> generate_link('forums&amp;file=viewforum&amp;f='.$parent_forum_id)
		));
	}

	$_CLASS['core_template']->assign_vars_array('navlinks', array(
		'S_IS_CAT'		=> ($forum_data['forum_type'] == FORUM_CAT) ? true : false,
		'S_IS_LINK'		=> ($forum_data['forum_type'] == FORUM_LINK) ? true : false,
		'S_IS_POST'		=> ($forum_data['forum_type'] == FORUM_POST) ? true : false,
		'FORUM_NAME'	=> $forum_data['forum_name'],
		'FORUM_ID'		=> $forum_data['forum_id'],
		'U_VIEW_FORUM'	=> generate_link('forums&amp;file=viewforum&amp;f=' . $forum_data['forum_id'])
	));

	$_CLASS['core_template']->assign_array(array(
		'FORUM_ID' 		=> $forum_data['forum_id'],
		'FORUM_NAME'	=> $forum_data['forum_name'],
		'FORUM_DESC'	=> generate_text_for_display($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options']))
	);

	return;
}

/**
* Returns forum parents as an array. Get them from forum_data if available, or update the database otherwise
*/
function get_forum_parents(&$forum_data)
{
	global $_CLASS;

	$forum_parents = array();

	if ($forum_data['parent_id'] > 0)
	{
		if ($forum_data['forum_parents'] == '')
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

/**
* Obtain list of moderators of each forum
*/
function get_moderators($forum_id = false)
{
	global $config, $_CLASS;

	// Have we disabled the display of moderators? If so, then return
	// from whence we came ...
	if (!$config['load_moderators'])
	{
		return array();
	}

	$forum_sql = '';

	if ($forum_id !== false)
	{
		// If we don't have a forum then we can't have a moderator
		if (is_array($forum_id) && !sizeof($forum_id))
		{
			return;
		}

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
		$forum_moderators[$row['forum_id']][] = empty($row['user_id']) ? '<a href="' . generate_link('members_list&amp;mode=group&amp;g=' . $row['group_id']) . '">' . (isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</a>' : '<a href="' . generate_link('members_list&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $row['username'] . '</a>';
	}
	$_CLASS['core_db']->free_result($result);

	return $forum_moderators;
}

/**
* User authorisation levels output
*/
function gen_forum_auth_level($mode, $forum_id, $forum_status)
{
	global $_CLASS, $config;

	$locked = ($forum_status == ITEM_LOCKED && !$_CLASS['forums_auth']->acl_get('m_edit', $forum_id)) ? true : false;

	$rules = array(
		($_CLASS['forums_auth']->acl_get('f_post', $forum_id) && !$locked) ? $_CLASS['core_user']->lang['RULES_POST_CAN'] : $_CLASS['core_user']->lang['RULES_POST_CANNOT'],
		($_CLASS['forums_auth']->acl_get('f_reply', $forum_id) && !$locked) ? $_CLASS['core_user']->lang['RULES_REPLY_CAN'] : $_CLASS['core_user']->lang['RULES_REPLY_CANNOT'],
		($_CLASS['forums_auth']->acl_gets('f_edit', 'm_edit', $forum_id) && !$locked) ? $_CLASS['core_user']->lang['RULES_EDIT_CAN'] : $_CLASS['core_user']->lang['RULES_EDIT_CANNOT'],
		($_CLASS['forums_auth']->acl_gets('f_delete', 'm_delete', $forum_id) && !$locked) ? $_CLASS['core_user']->lang['RULES_DELETE_CAN'] : $_CLASS['core_user']->lang['RULES_DELETE_CANNOT'],
	);

	if ($config['allow_attachments'])
	{
		$rules[] = ($_CLASS['forums_auth']->acl_get('f_attach', $forum_id) && $_CLASS['forums_auth']->acl_get('u_attach') && !$locked) ? $_CLASS['core_user']->lang['RULES_ATTACH_CAN'] : $_CLASS['core_user']->lang['RULES_ATTACH_CANNOT'];
	}

	foreach ($rules as $rule)
	{
		$_CLASS['core_template']->assign_vars_array('rules', array('RULE' => $rule));
	}

	return;
}

function topic_status(&$topic_row, $replies, $unread_topic, &$folder_img, &$folder_alt, &$topic_type)
{
	global $_CLASS, $config;

	$folder = $folder_new = '';

	if ($topic_row['topic_status'] == ITEM_MOVED)
	{
		$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_MOVED'];
		$folder_img = 'topic_moved';
		$folder_alt = 'VIEW_TOPIC_MOVED';
	}
	else
	{
		switch ($topic_row['topic_type'])
		{
			case POST_GLOBAL:
				$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_GLOBAL'];
				$folder = 'global_read';
				$folder_new = 'global_unread';
			break;

			case POST_ANNOUNCE:
				$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_ANNOUNCEMENT'];
				$folder = 'announce_read';
				$folder_new = 'announce_unread';
			break;

			case POST_STICKY:
				$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_STICKY'];
				$folder = 'sticky_read';
				$folder_new = 'sticky_unread';
			break;

			default:
				$topic_type = '';
				$folder = 'topic_read';
				$folder_new = 'topic_unread';

				if ($config['hot_threshold'] && $replies >= $config['hot_threshold'])
				{
					$folder .= '_hot';
					$folder_new .= '_hot';
				}
			break;
		}

		if ($topic_row['topic_status'] == ITEM_LOCKED)
		{
			$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_LOCKED'];
			$folder .= '_locked';
			$folder_new .= '_locked';
		}


		$folder_img = ($unread_topic) ? $folder_new : $folder;
		$folder_alt = ($unread_topic) ? 'NEW_POSTS' : (($topic_row['topic_status'] == ITEM_LOCKED) ? 'TOPIC_LOCKED' : 'NO_NEW_POSTS');

		// Posted image?
		if (!empty($topic_row['topic_posted']) && $topic_row['topic_posted'])
		{
			$folder_img .= '_mine';
		}
	}

	if ($topic_row['poll_start'])
	{
		$topic_type .= $_CLASS['core_user']->lang['VIEW_TOPIC_POLL'];
	}
}

// Display Attachments
function display_attachments($forum_id, $attachment_data, &$update_count, $force_physical = false, $parse = false)
{
	global $config, $_CLASS;

	$datas = array();
	$extensions = obtain_attach_extensions();

	if (!is_array($update_count))
	{
		$update_count = array();
	}

	foreach ($attachment_data as $attachment)
	{
		$attachment['extension'] = strtolower(trim($attachment['extension']));

		if (!extension_allowed($forum_id, $attachment['extension'], $extensions))
		{
			$data['category'] = 'DENIED';
			$data['lang'] = sprintf($_CLASS['core_user']->get_lang('EXTENSION_DISABLED_AFTER_POSTING'), $attachment['extension']);
		}
		else
		{
			$filename = $config['upload_path'] . '/' . basename($attachment['physical_filename']);
			// to easy isn't it ?
			$thumbnail_filename = $config['upload_path'] . '/thumb_' . basename($attachment['physical_filename']);

			$display_cat = $extensions[$attachment['extension']]['display_cat'];
	
			if ($display_cat == ATTACHMENT_CATEGORY_IMAGE)
			{
				if ($attachment['thumbnail'])
				{
					$display_cat = ATTACHMENT_CATEGORY_THUMB;
				}
				else
				{
					if ($config['img_display_inlined'])
					{
						if ($config['img_link_width'] || $config['img_link_height'])
						{
							list($width, $height) = getimagesize($filename);
	
							$display_cat = (!$width && !$height) ? ATTACHMENT_CATEGORY_IMAGE : (($width <= $config['img_link_width'] && $height <= $config['img_link_height']) ? ATTACHMENT_CATEGORY_IMAGE : ATTACHMENT_CATEGORY_NONE);
						}
					}
					else
					{
						$display_cat = ATTACHMENT_CATEGORY_NONE;
					}
				}
			}
	
			switch ($display_cat)
			{
				// Images
				case ATTACHMENT_CATEGORY_IMAGE:
					$data['category'] = 'IMAGE';
					$data['image_src'] = $filename;

					//$attachment['download_count']++;
					$update_count[] = $attachment['attach_id'];
				break;
					
				// Images, but display Thumbnail
				case ATTACHMENT_CATEGORY_THUMB:
					$data['category'] = 'THUMBNAIL';
	
					$data['image_src'] = $thumbnail_filename;
					$data['link'] = (!$force_physical) ? generate_link('forums&amp;file=download&amp;id=' . $attachment['attach_id']) : $filename;
				break;
	
				// Windows Media Streams
				case ATTACHMENT_CATEGORY_WM:
					$data['category'] = 'WM_STREAM';
					$data['link'] = $filename;
	
					// Viewed/Heared File ... update the download count (download.php is not called here)
					//$attachment['download_count']++;
					$update_count[] = $attachment['attach_id'];
				break;
	
				// Real Media Streams
				case ATTACHMENT_CATEGORY_RM:
					$data['category'] = 'RM_STREAM';
					$data['link'] = $filename;
	
					// Viewed/Heared File ... update the download count (download.php is not called here)
					//$attachment['download_count']++;
					$update_count[] = $attachment['attach_id'];
				break;
	
				default:
					$data['category'] = 'FILE';
					$data['link'] = (!$force_physical) ? generate_link('forums&amp;file=download&amp;id=' . $attachment['attach_id']) : $filename;
				break;
			}
			
			$data['lang_size'] = ($attachment['filesize'] >= 1048576) ? round((round($attachment['filesize'] / 1048576 * 100) / 100), 2) .$_CLASS['core_user']->lang['MB'] : (($attachment['filesize'] >= 1024) ? round((round($attachment['filesize'] / 1024 * 100) / 100), 2)  . $_CLASS['core_user']->lang['KB']: $attachment['filesize'] . $_CLASS['core_user']->lang['BYTES']);
			$data['lang_views'] = (empty($attachment['download_count']) || !$attachment['download_count']) ? $_CLASS['core_user']->lang['DOWNLOAD_NONE'] : (($attachment['download_count'] == 1) ? sprintf($_CLASS['core_user']->lang['DOWNLOAD_COUNT'], $attachment['download_count']) : sprintf($_CLASS['core_user']->lang['DOWNLOAD_COUNTS'], $attachment['download_count']));
	
			$data['icon'] = (isset($extensions[$attachment['extension']]['upload_icon']) && $extensions[$attachment['extension']]['upload_icon']) ? $config['upload_icons_path'] . '/' . trim($extensions[$attachment['extension']]['upload_icon']) : false;
			$data['name'] = basename($attachment['real_filename']);
			$data['comment'] = str_replace("\n", '<br />', censor_text($attachment['comment']));
		}

		if ($parse)
		{
			$_CLASS['core_template']->assign_vars_array('attachments', $data);
			$datas[] = $_CLASS['core_template']->display('modules/forums/attachments.html', true);
		}
		else
		{
			$datas[] = $data;
		}
	}

	return $datas;
}

/**
* Assign/Build custom bbcodes for display in screens supporting using of bbcodes
* The custom bbcodes buttons will be placed within the template block 'custom_codes'
*/
function display_custom_bbcodes()
{
	global $_CLASS;

	// Start counting from 22 for the bbcode ids (every bbcode takes two ids - opening/closing)
	$num_predefined_bbcodes = 22;

	/*
	* @todo while adjusting custom bbcodes, think about caching this query as well as correct ordering
	*/
	$sql = 'SELECT bbcode_id, bbcode_tag, bbcode_helpline
		FROM ' . FORUMS_BBCODES_TABLE . '
		WHERE display_on_posting = 1';
	$result = $_CLASS['core_db']->query($sql);

	$i = 0;
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_template']->assign_vars_array('custom_tags', array(
			'BBCODE_NAME'		=> "'[{$row['bbcode_tag']}]', '[/" . str_replace('=', '', $row['bbcode_tag']) . "]'",
			'BBCODE_ID'			=> $num_predefined_bbcodes + ($i * 2),
			'BBCODE_TAG'		=> $row['bbcode_tag'],
			'BBCODE_HELPLINE'	=> $row['bbcode_helpline']
		));

		$i++;
	}
	$_CLASS['core_db']->free_result($result);
}

/**
* Display reasons
*/
function display_reasons($reason_id = 0)
{
	global $_CLASS;
return;
	$sql = 'SELECT * 
		FROM ' . REPORTS_REASONS_TABLE . ' 
		ORDER BY reason_order ASC';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		// If the reason is defined within the language file, we will use the localized version, else just use the database entry...
		if (isset($_CLASS['core_user']->lang['report_reasons']['TITLE'][strtoupper($row['reason_title'])]) && isset($_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_title'])]))
		{
			$row['reson_description'] = $_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_title'])];
			$row['reason_title'] = $_CLASS['core_user']->lang['report_reasons']['TITLE'][strtoupper($row['reason_title'])];
		}

		$_CLASS['core_template']->assign_vars_array('reason', array(
			'ID'			=> $row['reason_id'],
			'TITLE'			=> $row['reason_title'],
			'DESCRIPTION'	=> $row['reason_description'],
			'S_SELECTED'	=> ($row['reason_id'] == $reason_id) ? true : false)
		);
	}
	$_CLASS['core_db']->free_result($result);
}

/**
* Display user activity (action forum/topic)
*/
function display_user_activity(&$userdata)
{
	global $auth, $template, $db, $user;
	global $phpbb_root_path, $phpEx;

	// Init new auth class if user is different
	if ($user->data['user_id'] != $userdata['user_id'])
	{
		$auth2 = new auth();
		$auth2->acl($userdata);

		$post_count_ary = $auth2->acl_getf('!f_postcount');
	}
	else
	{
		$post_count_ary = $auth->acl_getf('!f_postcount');
	}

	$forum_read_ary = $auth->acl_getf('!f_read');

	$forum_ary = array();

	// Do not include those forums the user is not having read access to...
	foreach ($forum_read_ary as $forum_id => $not_allowed)
	{
		if ($not_allowed['f_read'])
		{
			$forum_ary[] = (int) $forum_id;
		}
	}

	// Now do not include those forums where the posts do not count...
	foreach ($post_count_ary as $forum_id => $not_counted)
	{
		if ($not_counted['f_postcount'])
		{
			$forum_ary[] = (int) $forum_id;
		}
	}

	$forum_ary = array_unique($forum_ary);
	$post_count_sql = (sizeof($forum_ary)) ? 'AND ' . $db->sql_in_set('f.forum_id', $forum_ary, true) : '';

	// Firebird does not support ORDER BY on aliased columns
	// MySQL does not support ORDER BY on functions
	switch (SQL_LAYER)
	{
		case 'firebird':
			$sql = 'SELECT f.forum_id, COUNT(p.post_id) AS num_posts
				FROM ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . ' f 
				WHERE p.poster_id = ' . $userdata['user_id'] . " 
					AND f.forum_id = p.forum_id 
					$post_count_sql
				GROUP BY f.forum_id
				ORDER BY COUNT(p.post_id) DESC";
		break;

		default:
			$sql = 'SELECT f.forum_id, COUNT(p.post_id) AS num_posts
				FROM ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . ' f 
				WHERE p.poster_id = ' . $userdata['user_id'] . " 
					AND f.forum_id = p.forum_id 
					$post_count_sql
				GROUP BY f.forum_id
				ORDER BY num_posts DESC";
		break;
	}

	$result = $db->sql_query_limit($sql, 1);
	$active_f_row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!empty($active_f_row))
	{
		$sql = 'SELECT forum_name
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . $active_f_row['forum_id'];
		$result = $db->sql_query($sql, 3600);
		$active_f_row['forum_name'] = (string) $db->sql_fetchfield('forum_name');
		$db->sql_freeresult($result);
	}

	// Firebird does not support ORDER BY on aliased columns
	// MySQL does not support ORDER BY on functions
	switch (SQL_LAYER)
	{
		case 'firebird':
			$sql = 'SELECT t.topic_id, COUNT(p.post_id) AS num_posts   
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f  
				WHERE p.poster_id = ' . $userdata['user_id'] . " 
					AND t.topic_id = p.topic_id  
					AND f.forum_id = t.forum_id 
					$post_count_sql
				GROUP BY t.topic_id
				ORDER BY COUNT(p.post_id) DESC";
		break;

		default:
			$sql = 'SELECT t.topic_id, COUNT(p.post_id) AS num_posts   
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f  
				WHERE p.poster_id = ' . $userdata['user_id'] . " 
					AND t.topic_id = p.topic_id  
					AND f.forum_id = t.forum_id 
					$post_count_sql
				GROUP BY t.topic_id
				ORDER BY num_posts DESC";
		break;
	}

	$result = $db->sql_query_limit($sql, 1);
	$active_t_row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!empty($active_t_row))
	{
		$sql = 'SELECT topic_title
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id = ' . $active_t_row['topic_id'];
		$result = $db->sql_query($sql);
		$active_t_row['topic_title'] = (string) $db->sql_fetchfield('topic_title');
		$db->sql_freeresult($result);
	}

	$userdata['active_t_row'] = $active_t_row;
	$userdata['active_f_row'] = $active_f_row;

	$active_f_name = $active_f_id = $active_f_count = $active_f_pct = '';
	if (!empty($active_f_row['num_posts']))
	{
		$active_f_name = $active_f_row['forum_name'];
		$active_f_id = $active_f_row['forum_id'];
		$active_f_count = $active_f_row['num_posts'];
		$active_f_pct = ($userdata['user_posts']) ? ($active_f_count / $userdata['user_posts']) * 100 : 0;
	}

	$active_t_name = $active_t_id = $active_t_count = $active_t_pct = '';
	if (!empty($active_t_row['num_posts']))
	{
		$active_t_name = $active_t_row['topic_title'];
		$active_t_id = $active_t_row['topic_id'];
		$active_t_count = $active_t_row['num_posts'];
		$active_t_pct = ($userdata['user_posts']) ? ($active_t_count / $userdata['user_posts']) * 100 : 0;
	}

	$template->assign_vars(array(
		'ACTIVE_FORUM'			=> $active_f_name,
		'ACTIVE_FORUM_POSTS'	=> ($active_f_count == 1) ? sprintf($user->lang['USER_POST'], 1) : sprintf($user->lang['USER_POSTS'], $active_f_count),
		'ACTIVE_FORUM_PCT'		=> sprintf($user->lang['POST_PCT_ACTIVE'], $active_f_pct),
		'ACTIVE_TOPIC'			=> censor_text($active_t_name),
		'ACTIVE_TOPIC_POSTS'	=> ($active_t_count == 1) ? sprintf($user->lang['USER_POST'], 1) : sprintf($user->lang['USER_POSTS'], $active_t_count),
		'ACTIVE_TOPIC_PCT'		=> sprintf($user->lang['POST_PCT_ACTIVE'], $active_t_pct),
		'U_ACTIVE_FORUM'		=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $active_f_id),
		'U_ACTIVE_TOPIC'		=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 't=' . $active_t_id))
	);
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

				$_CLASS['core_db']->sql_query_build('INSERT', $sql_array, FORUMS_WATCH_TABLE);
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
			'link' => generate_link("forums&amp;file=view$mode&amp;$url&amp;" . (($is_watching) ? 'unwatch' : 'watch') . "=$mode&amp;start=$start"),
			'title' => $_CLASS['core_user']->lang[(($is_watching) ? 'STOP' : 'START') . '_WATCHING_' . strtoupper($mode)],
	);
}
?>