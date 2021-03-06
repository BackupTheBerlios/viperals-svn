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
// $Id: mcp_main.php,v 1.9 2004/07/10 22:47:42 acydburn Exp $
//
// FILENAME  : mcp_main.php
// STARTED   : Mon Sep 02, 2003
// COPYRIGHT : 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Lock/Unlock Topic/Post
function lock_unlock($mode, $ids)
{
	global $_CLASS;

	if ($mode === 'lock' || $mode === 'unlock')
	{
		$table = FORUMS_TOPICS_TABLE;
		$sql_id = 'topic_id';
		$set_id = 'topic_status';
		$l_prefix = 'TOPIC';
	}
	else
	{
		$table = FORUMS_POSTS_TABLE;
		$sql_id = 'post_id';
		$set_id = 'post_edit_locked';
		$l_prefix = 'POST';
	}
	
	if (!check_ids($ids, $table, $sql_id, array('f_user_lock', 'm_lock')))
	{// redirect maybe
		return;
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);//generate_link('Forums')
	$message = $_CLASS['core_user']->get_lang(strtoupper($mode) . '_' . $l_prefix . ((count($ids) === 1) ? '' : 'S'));

	$hidden_fields = generate_hidden_fields(array(
		$sql_id . '_list'	=> $ids,
		'mode'				=> $mode,
		'redirect'			=> $redirect
	));

	$success_msg = false;

	if (display_confirmation($message, $hidden_fields))
	{
		$sql = "UPDATE $table
			SET $set_id = " . (($mode === 'lock' || $mode === 'lock_post') ? ITEM_LOCKED : ITEM_UNLOCKED) . "
			WHERE $sql_id IN (" . implode(', ', $ids) . ")";
		$_CLASS['core_db']->query($sql);

		$data = ($mode === 'lock' || $mode === 'unlock') ? get_topic_data($ids) : get_post_data($ids);

		foreach ($data as $id => $row)
		{
			add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_' . strtoupper($mode), $row['topic_title']);
		}

		$success_msg = $l_prefix . ((count($ids) === 1) ? '' : 'S') . '_' . (($mode == 'lock' || $mode == 'lock_post') ? 'LOCKED' : 'UNLOCKED') . '_SUCCESS';
	}

	$redirect = generate_link($redirect);

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(2, $redirect);
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
	}
}

// Change Topic Type
function change_topic_type($mode, $topic_ids, $forum_id)
{
	global $_CLASS;

	if (!check_ids($topic_ids, FORUMS_TOPICS_TABLE, 'topic_id', array('f_announce', 'f_sticky', 'm_')))
	{
		return;
	}

	switch ($mode)
	{
		case 'make_announce':
			$new_topic_type = POST_ANNOUNCE;
			$check_acl = 'f_announce';
			$l_new_type = (count($topic_ids) === 1) ? 'MCP_MAKE_ANNOUNCEMENT' : 'MCP_MAKE_ANNOUNCEMENTS';
		break;

		case 'make_global':
			$new_topic_type = POST_GLOBAL;
			$check_acl = 'f_announce';
			$l_new_type = (count($topic_ids) === 1) ? 'MCP_MAKE_GLOBAL' : 'MCP_MAKE_GLOBALS';
		break;

		case 'make_sticky':
			$new_topic_type = POST_STICKY;
			$check_acl = 'f_sticky';
			$l_new_type = (count($topic_ids) === 1) ? 'MCP_MAKE_STICKY' : 'MCP_MAKE_STICKIES';
		break;

		default:
			$new_topic_type = POST_NORMAL;
			$check_acl = '';
			$l_new_type = (count($topic_ids) === 1) ? 'MCP_MAKE_NORMAL' : 'MCP_MAKE_NORMALS';
		break;
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);

	$hidden_fields = generate_hidden_fields(array(
		'topic_id_list'	=> $topic_ids,
		'mode'			=> $mode,
		'redirect'		=> $redirect
	));
	$success_msg = '';

	if (display_confirmation($_CLASS['core_user']->get_lang($l_new_type), $hidden_fields))
	{
		if ($new_topic_type !== POST_GLOBAL)
		{
			$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . "
				SET topic_type = $new_topic_type
				WHERE topic_id IN (" . implode(', ', $topic_ids) . ')
					AND forum_id <> 0';
			$_CLASS['core_db']->query($sql);

			if ($forum_id)
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . "
					SET topic_type = $new_topic_type, forum_id = $forum_id
						WHERE topic_id IN (" . implode(', ', $topic_ids) . ')
						AND forum_id = 0';
				$_CLASS['core_db']->query($sql);
			}
		}
		else
		{
			$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . "
				SET topic_type = $new_topic_type
				WHERE topic_id IN (" . implode(', ', $topic_ids) . ")";
			$_CLASS['core_db']->query($sql);

			move_topics($topic_ids, 0, true);
		}

		$success_msg = (count($topic_ids) === 1) ? 'TOPIC_TYPE_CHANGED' : 'TOPICS_TYPE_CHANGED';

		$data = get_topic_data($topic_ids);

		$_CLASS['core_db']->transaction();

		foreach ($data as $topic_id => $row)
		{
			add_log('mod', $forum_id, $topic_id, 'LOG_TOPIC_TYPE_CHANGED', $row['topic_title']);
		}
		
		$_CLASS['core_db']->transaction('commit');
	}

	$redirect = generate_link($redirect, array('full' => true));

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(2, $redirect);
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
	}
}


// Move Topic
function mcp_move_topic($topic_ids)
{
	global $_CLASS;
	
	$old_forums = check_ids($topic_ids, FORUMS_TOPICS_TABLE, 'topic_id', 'm_move');

	if (!$old_forums)
	{
		return;
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);
	$to_forum_id = get_variable('to_forum_id', 'POST', 0, 'int');

	$additional_msg = $success_msg = '';

	if ($to_forum_id)
	{
		$forum_data = get_forum_data($to_forum_id, 'm_');

		if (empty($forum_data[$to_forum_id]))
		{
			$additional_msg = $_CLASS['core_user']->lang['FORUM_NOT_EXIST'];
			$to_forum_id = 0;
		}
		else
		{
			$forum_data = $forum_data[$to_forum_id];

			if ($forum_data['forum_type'] != FORUM_POST)
			{
				$additional_msg = $_CLASS['core_user']->lang['FORUM_NOT_POSTABLE'];
			}
			elseif (!$_CLASS['auth']->acl_get('f_post', $to_forum_id))
			{
				$additional_msg = $_CLASS['core_user']->lang['USER_CANNOT_POST'];
			}
			elseif (in_array($to_forum_id, $old_forums))
			{
				$additional_msg = $_CLASS['core_user']->lang['CANNOT_MOVE_SAME_FORUM'];
			}
		}
	}

	if (!$to_forum_id || $additional_msg)
	{
		unset($_POST['confirm']);
	}
	
	$hidden_fields = generate_hidden_fields(array(
		'topic_id_list'	=> $topic_ids,
		'mode'			=> 'move',
		'redirect'		=> $redirect
	));

	$_CLASS['core_template']->assign_array(array(
		'S_FORUM_SELECT'		=> make_forum_select($to_forum_id, $old_forums, false, true, true),
		'S_CAN_LEAVE_SHADOW'	=> true,
		'ADDITIONAL_MSG'		=> $additional_msg
	));

	$message = $_CLASS['core_user']->get_lang('MOVE_TOPIC' . ((count($topic_ids) === 1) ? '' : 'S'));

	page_header();

	if (display_confirmation($message, $hidden_fields, 'modules/Forums/mcp_move.html'))
	{
		$topic_data = get_topic_data($topic_ids);
		$leave_shadow = isset($_POST['move_leave_shadow']);

		// Move topics, but do not resync yet
		move_topics($topic_ids, $to_forum_id, false);

		$_CLASS['core_db']->transaction();

		$forum_ids = array($to_forum_id);
		$shadow = array();

		foreach ($topic_data as $topic_id => $row)
		{
			// Get the list of forums to resync, add a log entry
			$forum_ids[] = $row['forum_id'];
			add_log('mod', $to_forum_id, $topic_id, 'LOG_MOVE', $row['forum_name']);

			// Leave a redirection if required and only if the topic is visible to users
			if ($leave_shadow && $row['topic_approved'])
			{
				$shadow[] = array(
					'forum_id'				=>	(int) $row['forum_id'],
					'icon_id'				=>	(int) $row['icon_id'],
					'topic_attachment'		=>	(int) $row['topic_attachment'],
					'topic_approved'		=>	1,
					'topic_reported'		=>	(int) $row['topic_reported'],
					'topic_title'			=>	(string) $row['topic_title'],
					'topic_poster'			=>	(int) $row['topic_poster'],
					'topic_time'			=>	(int) $row['topic_time'],
					'topic_time_limit'		=>	(int) $row['topic_time_limit'],
					'topic_views'			=>	(int) $row['topic_views'],
					'topic_replies'			=>	(int) $row['topic_replies'],
					'topic_replies_real'	=>	(int) $row['topic_replies_real'],
					'topic_status'			=>	ITEM_MOVED,
					'topic_type'			=>	(int) $row['topic_type'],
					'topic_first_post_id'	=>	(int) $row['topic_first_post_id'],
					'topic_first_poster_name'=>	(string) $row['topic_first_poster_name'],
					'topic_last_post_id'	=>	(int) $row['topic_last_post_id'],
					'topic_last_poster_id'	=>	(int) $row['topic_last_poster_id'],
					'topic_last_poster_name'=>	(string) $row['topic_last_poster_name'],
					'topic_last_post_time'	=>	(int) $row['topic_last_post_time'],
					'topic_last_view_time'	=>	(int) $row['topic_last_view_time'],
					'topic_moved_id'		=>	(int) $row['topic_id'],
					'topic_bumped'			=>	(int) $row['topic_bumped'],
					'topic_bumper'			=>	(int) $row['topic_bumper'],
					'poll_title'			=>	(string) $row['poll_title'],
					'poll_start'			=>	(int) $row['poll_start'],
					'poll_length'			=>	(int) $row['poll_length'],
					'poll_max_options'		=>	(int) $row['poll_max_options'],
					'poll_last_vote'		=>	(int) $row['poll_last_vote']
				);
			}
		}

		$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $shadow, FORUMS_TOPICS_TABLE);
		$_CLASS['core_db']->transaction('commit');

		unset($topic_data, $shadow);

		// Now sync forums
		sync('forum', 'forum_id', $forum_ids);

		$success_msg = (count($topic_ids) === 1) ? 'TOPIC_MOVED_SUCCESS' : 'TOPICS_MOVED_SUCCESS';
	}

	$redirect = generate_link($redirect);

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, $redirect);

		$message = $_CLASS['core_user']->lang[$success_msg];
		$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>');
		$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('Forums&amp;file=viewforum&amp;f='.$to_forum_id).'">', '</a>');
		$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_NEW_FORUM'], '<a href='.generate_link('Forums&amp;file=viewforum&amp;f='.$to_forum_id).'">', '</a>');
		
		trigger_error($message);
	}
}

// Delete Topics
function mcp_delete_topic($topic_ids)
{
	global $_CLASS;

	if (!check_ids($topic_ids, FORUMS_TOPICS_TABLE, 'topic_id', 'm_delete'))
	{
		return;
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);

	$hidden_fields = generate_hidden_fields(array(
		'topic_id_list'	=> $topic_ids,
		'mode'			=> 'delete_topic',
		'redirect'		=> $redirect
	));

	$success_msg = '';
	$message = $_CLASS['core_user']->get_lang((count($topic_ids) === 1) ? 'DELETE_TOPIC' : 'DELETE_TOPICS');

	if (display_confirmation($message, $hidden_fields))
	{
		$success_msg = (count($topic_ids) === 1) ? 'TOPIC_DELETED_SUCCESS' : 'TOPICS_DELETED_SUCCESS';

		$data = get_topic_data($topic_ids);

		foreach ($data as $topic_id => $row)
		{
			add_log('mod', $row['forum_id'], 0, 'LOG_TOPIC_DELETED', $row['topic_title']);
		}

		$return = delete_topics('topic_id', $topic_ids, true);
	}

	$redirect = generate_link($redirect);

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, $redirect);
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'. $redirect . '">', '</a>'));
	}
}

// Delete Posts
function mcp_delete_post($post_ids)
{
	global $_CLASS;

	if (!check_ids($post_ids, FORUMS_POSTS_TABLE, 'post_id', 'm_delete'))
	{
		return;
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);

	$hidden_fields = generate_hidden_fields(array(
		'post_id_list'	=> $post_ids,
		'mode'			=> 'delete_post',
		'redirect'		=> $redirect
	));

	$success_msg = '';
	$message = $_CLASS['core_user']->get_lang((count($post_ids) === 1) ? 'DELETE_POST' : 'DELETE_POSTS');

	if (display_confirmation($message, $hidden_fields))
	{
		// Count the number of topics that are affected
		// I did not use COUNT(DISTINCT ...) because I remember having problems
		// with it on older versions of MySQL -- Ashe

		$sql = 'SELECT DISTINCT topic_id
			FROM ' . FORUMS_POSTS_TABLE . '
			WHERE post_id IN (' . implode(', ', $post_ids) . ')';
		$result = $_CLASS['core_db']->query($sql);

		$topic_id_list = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$topic_id_list[] = $row['topic_id'];
		}
		$_CLASS['core_db']->free_result($result);

		$affected_topics = count($topic_id_list);
		$post_data = get_post_data($post_ids);

		foreach ($post_data as $id => $row)
		{
			add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_DELETE_POST', $row['post_subject']);
		}
		unset($post_data);

		// Now delete the posts, topics and forums are automatically resync'ed
		delete_posts('post_id', $post_ids);
					
		$sql = 'SELECT COUNT(topic_id) AS topics_left
			FROM ' . FORUMS_TOPICS_TABLE . '
			WHERE topic_id IN (' . implode(', ', $topic_id_list) . ')';

		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$deleted_topics = ($row['topics_left']) ? ($affected_topics - $row['topics_left']) : $affected_topics;
		$topic_id = request_var('t', 0);

		// Return links
		$return_link = array();
		if ($affected_topics === 1 && !$deleted_topics && $topic_id)
		{
			$return_link[] = sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id").'">', '</a>');
		}

		$return_link[] = sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('forums&amp;file=viewforum&amp;f='.$forum_id).'">', '</a>');

		if (count($post_ids) === 1)
		{
			if ($deleted_topics)
			{
				// We deleted the only post of a topic, which in turn has
				// been removed from the database
				$success_msg = $_CLASS['core_user']->lang['TOPIC_DELETED_SUCCESS'];
			}
			else
			{
				$success_msg = $_CLASS['core_user']->lang['POST_DELETED_SUCCESS'];
			}
		}
		else
		{
			if ($deleted_topics)
			{
				// Some of topics disappeared
				$success_msg = $_CLASS['core_user']->lang['POSTS_DELETED_SUCCESS'] . '<br /><br />' . $_CLASS['core_user']->lang['EMPTY_TOPICS_REMOVED_WARNING'];
			}
			else
			{
				$success_msg = $_CLASS['core_user']->lang['POSTS_DELETED_SUCCESS'];
			}
		}
	}

	$redirect = generate_link('forums');

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, $redirect);
		trigger_error($success_msg . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>') . '<br /><br />' . implode('<br /><br />', $return_link));
	}
}

// Fork Topic
function mcp_fork_topic($topic_ids)
{
	global $_CLASS, $config;

	if (!check_ids($topic_ids, FORUMS_TOPICS_TABLE, 'topic_id', 'm_'))
	{
		return;
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);
	$to_forum_id = get_variable('to_forum_id', 'POST', 0, 'int');

	$additional_msg = $success_msg = '';

	if ($to_forum_id)
	{
		$forum_data = get_forum_data($to_forum_id, 'm_');

		if (empty($forum_data[$to_forum_id]))
		{
			$additional_msg = $_CLASS['core_user']->lang['FORUM_NOT_EXIST'];
		}
		else
		{
			$forum_data = $forum_data[$to_forum_id];

			if ($forum_data['forum_type'] != FORUM_POST)
			{
				$additional_msg = $_CLASS['core_user']->lang['FORUM_NOT_POSTABLE'];
			}
			elseif (!$_CLASS['auth']->acl_get('f_post', $to_forum_id))
			{
				$additional_msg = $_CLASS['core_user']->lang['USER_CANNOT_POST'];
			}
		}
	}

	if (!$to_forum_id || $additional_msg)
	{
		unset($_POST['confirm']);
	}

	$hidden_fields = generate_hidden_fields(array(
		'topic_id_list'	=> $topic_ids,
		'mode'			=> 'fork',
		'redirect'		=> $redirect
	));

	$_CLASS['core_template']->assign_array(array(
		'S_FORUM_SELECT'		=> make_forum_select($to_forum_id, false, false, true, true),
		'S_CAN_LEAVE_SHADOW'	=> false,
		'ADDITIONAL_MSG'		=> $additional_msg
	));

	$message = $_CLASS['core_user']->get_lang('FORK_TOPIC' . ((count($topic_ids) === 1) ? '' : 'S'));

	page_header();

	if (display_confirmation($message, $hidden_fields, 'modules/Forums/mcp_move.html'))
	{
		$topic_data = get_topic_data($topic_ids);

		$total_posts = 0;
		$new_topic_id_list = $new_topic_forum_name_list = $insert_array = array();

		$_CLASS['core_db']->transaction();

		foreach ($topic_data as $topic_id => $topic_row)
		{
// just change $row values for forum_id, topic_reported;
// get_topic_data gets some unneeded stuff, remove it so we can just use $row

			$sql_ary = array(
				'forum_id'					=> (int) $to_forum_id,
				'icon_id'					=> (int) $topic_row['icon_id'],
				'topic_attachment'			=> (int) $topic_row['topic_attachment'],
				'topic_approved'			=> 1,
				'topic_reported'			=> 0,
				'topic_title'				=> (string) $topic_row['topic_title'],
				'topic_poster'				=> (int) $topic_row['topic_poster'],
				'topic_time'				=> (int) $topic_row['topic_time'],
				'topic_replies'				=> (int) $topic_row['topic_replies_real'],
				'topic_replies_real'		=> (int) $topic_row['topic_replies_real'],
				'topic_status'				=> (int) $topic_row['topic_status'],
				'topic_type'				=> (int) $topic_row['topic_type'],
				'topic_first_poster_name'	=> (string) $topic_row['topic_first_poster_name'],
				'topic_last_poster_id'		=> (int) $topic_row['topic_last_poster_id'],
				'topic_last_poster_name'	=> (string) $topic_row['topic_last_poster_name'],
				'topic_last_post_time'		=> (int) $topic_row['topic_last_post_time'],
				'topic_last_view_time'		=> (int) $topic_row['topic_last_view_time'],
				'topic_bumped'				=> (int) $topic_row['topic_bumped'],
				'topic_bumper'				=> (int) $topic_row['topic_bumper'],
				'topic_views'				=> 0,
				'poll_title'				=> (string) $topic_row['poll_title'],
				'poll_start'				=> (int) $topic_row['poll_start'],
				'poll_length'				=> (int) $topic_row['poll_length']
			);

			$_CLASS['core_db']->sql_query_build('INSERT', $sql_ary, FORUMS_TOPICS_TABLE);
			unset($sql_ary);

			$new_topic_id = $_CLASS['core_db']->insert_id(FORUMS_TOPICS_TABLE, 'topic_id');

			$new_topic_id_list[$topic_id] = $new_topic_id;
			$new_topic_forum_name_list[$topic_id] = $topic_row['forum_name'];

			if ($topic_row['poll_start'])
			{
				$poll_rows = array();

				$sql = 'SELECT * 
					FROM ' . FORUMS_POLL_OPTIONS_TABLE . " 
					WHERE topic_id = $topic_id";
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$insert_array[FORUMS_POLL_OPTIONS_TABLE][] = array(
						'poll_option_id'	=> (int) $row['poll_option_id'],
						'topic_id'			=> (int) $new_topic_id,
						'poll_option_text'	=> (string) $row['poll_option_text'],
						'poll_option_total'	=> 0
					);
				}
				$_CLASS['core_db']->free_result($result);
			}
			unset($topic_data[$topic_id]);

			$sql = 'SELECT *
				FROM ' . FORUMS_POSTS_TABLE . "
				WHERE topic_id = $topic_id
				ORDER BY post_id ASC";
			$result = $_CLASS['core_db']->query($sql);
	
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$total_posts++;

				$insert_array[FORUMS_POSTS_TABLE][] = array(
					'topic_id'			=> (int) $new_topic_id,
					'forum_id'			=> (int) $to_forum_id,
					'poster_id'			=> (int) $row['poster_id'],
					'icon_id'			=> (int) $row['icon_id'],
					'poster_ip'			=> (string) $row['poster_ip'],
					'post_time'			=> (int) $row['post_time'],
					'post_approved'		=> 1,
					'post_reported'		=> 0,
					'enable_bbcode'		=> (int) $row['enable_bbcode'],
					'enable_html'		=> (int) $row['enable_html'],
					'enable_smilies'	=> (int) $row['enable_smilies'],
					'enable_magic_url'	=> (int) $row['enable_magic_url'],
					'enable_sig'		=> (int) $row['enable_sig'],
					'post_username'		=> (string) $row['post_username'],
					'post_subject'		=> (string) $row['post_subject'],
					'post_text'			=> (string) $row['post_text'],
					'post_edit_reason'	=> (string) $row['post_edit_reason'],
					'post_edit_user'	=> (int) $row['post_edit_user'],
					'post_checksum'		=> (string) $row['post_checksum'],
					'post_attachment'	=> (int) $row['post_attachment'],
					'bbcode_bitfield'	=> (int) $row['bbcode_bitfield'],
					'bbcode_uid'		=> (string) $row['bbcode_uid'],
					'post_edit_time'	=> (int) $row['post_edit_time'],
					'post_edit_count'	=> (int) $row['post_edit_count'],
					'post_edit_locked'	=> (int) $row['post_edit_locked']
				);

				// Copy Attachments
				if ($row['post_attachment'])
				{
					$_CLASS['core_db']->query('INSERT INTO ' . FORUMS_POSTS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array_pop($insert_array[FORUMS_POSTS_TABLE])));
					$new_post_id = $_CLASS['core_db']->insert_id(FORUMS_POSTS_TABLE, 'post_id');

					$sql = 'SELECT * FROM ' . FORUMS_ATTACHMENTS_TABLE . "
						WHERE post_msg_id = {$row['post_id']}
							AND topic_id = $topic_id
							AND in_message = 0";
					$result = $_CLASS['core_db']->query($sql);
					
					while ($attach_row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$insert_array[FORUMS_ATTACHMENTS_TABLE][] = array(
							'post_msg_id'		=> (int) $new_post_id,
							'topic_id'			=> (int) $new_topic_id,
							'in_message'		=> 0,
							'poster_id'			=> (int) $attach_row['poster_id'],
							'physical_filename'	=> (string) basename($attach_row['physical_filename']),
							'real_filename'		=> (string) basename($attach_row['real_filename']),
							'download_count'	=> (int) $attach_row['download_count'],
							'attach_comment'	=> (string) $attach_row['attach_comment'],
							'extension'			=> (string) $attach_row['extension'],
							'mimetype'			=> (string) $attach_row['mimetype'],
							'filesize'			=> (int) $attach_row['filesize'],
							'filetime'			=> (int) $attach_row['filetime'],
							'thumbnail'			=> (int) $attach_row['thumbnail']
						);
					}
					$_CLASS['core_db']->free_result($result);
				}
			}
			$_CLASS['core_db']->free_result($result);
		}
		unset($topic_data);

		$_CLASS['core_db']->transaction('commit');

		if (!empty($new_topic_id_list))
		{
			if (!empty($insert_array[FORUMS_POLL_OPTIONS_TABLE]))
			{
				$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $insert_array[FORUMS_POLL_OPTIONS_TABLE], FORUMS_POLL_OPTIONS_TABLE);
			}
			if (!empty($insert_array[FORUMS_POSTS_TABLE]))
			{
				$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $insert_array[FORUMS_POSTS_TABLE], FORUMS_POSTS_TABLE);
			}
			if (!empty($insert_array[FORUMS_ATTACHMENTS_TABLE]))
			{
				$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $insert_array[FORUMS_ATTACHMENTS_TABLE], FORUMS_ATTACHMENTS_TABLE);
			}
			unset($insert_array);
			
			// Sync new topics, parent forums and board stats
			sync('topic', 'topic_id', $new_topic_id_list, true);
			sync('forum', 'forum_id', $to_forum_id, true);
			set_config('num_topics', $config['num_topics'] + count($new_topic_id_list));
			set_config('num_posts', $config['num_posts'] + $total_posts);
	
			foreach ($new_topic_id_list as $topic_id => $new_topic_id)
			{
				add_log('mod', $to_forum_id, $new_topic_id, 'LOG_FORK', $new_topic_forum_name_list[$topic_id]['forum_name']);
			}
			
			$success_msg = (count($topic_ids) === 1) ? 'TOPIC_FORKED_SUCCESS' : 'TOPICS_FORKED_SUCCESS';
		}
	}

	$redirect = generate_link($redirect);

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, generate_link('forums&amp;file=viewforum&amp;f='.$to_forum_id));
		$return_link = sprintf($_CLASS['core_user']->lang['RETURN_NEW_FORUM'], '<a href="'. $redirect . '">', '</a>');

		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . $return_link);
	}
}

?>