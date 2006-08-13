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
// $Id: mcp_queue.php,v 1.7 2004/08/04 19:19:42 acydburn Exp $
//
// FILENAME  : mcp_queue.php
// STARTED   : Mon Sep 02, 2003
// COPYRIGHT : © 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------


global $_CLASS, $site_file_root, $config;

$forum_id = request_var('f', 0);
$start = request_var('start', 0);
$action = (isset($_REQUEST['action']) && is_array($_REQUEST['action'])) ? get_variable('action', 'REQUEST', false, 'array') : get_variable('action', 'REQUEST');
$mode = get_variable('mode', 'REQUEST');

if (is_array($action))
{
	list($action, ) = each($action);
}

switch ($action)
{
	case 'approve':
	case 'disapprove':
		require_once SITE_FILE_ROOT.'includes/forums/functions_posting.php';

		$post_id_list = array_unique(get_variable('post_id_list', 'REQUEST', array(), 'array:int'));

		if (empty($post_id_list))
		{
			trigger_error('NO_POST_SELECTED');
		}

		($action === 'approve') ? approve_post($post_id_list, $mode) : disapprove_post($post_id_list, $mode);
	break;
}

switch ($mode)
{
	case 'approve_details':
		$_CLASS['core_user']->add_lang('posting');
		require_once SITE_FILE_ROOT.'includes/forums/functions_posting.php';

		$post_id = get_variable('p', 'REQUEST', false, 'int');
		$topic_id = get_variable('t', 'REQUEST', false, 'int');

		if ($topic_id)
		{
			$topic_info = get_topic_data(array($topic_id), 'm_approve');
			$post_id = isset($topic_info[$topic_id]['topic_first_post_id']) ? (int) $topic_info[$topic_id]['topic_first_post_id'] : false;
		}

		$post_info = ($post_id) ? get_post_data(array($post_id), 'm_approve') : false;

		if (!$post_id || empty($post_info[$post_id]))
		{
			trigger_error('NO_POST_SELECTED');
		}

		$post_info = $post_info[$post_id];

		if ($post_info['topic_first_post_id'] != $post_id && topic_review($post_info['topic_id'], $post_info['forum_id'], 'topic_review', 0, false))
		{
			$_CLASS['core_template']->assign_array(array(
				'S_TOPIC_REVIEW'	=> true,
				'TOPIC_TITLE'		=> $post_info['topic_title'])
			);
		}

		// Set some vars
		if ($post_info['user_id'] == ANONYMOUS)
		{
			$post_info['username'] = ($post_info['post_username']) ? $post_info['post_username'] : $_CLASS['core_user']->lang['GUEST'];
		}

		$poster = ($post_info['user_colour']) ? '<span style="color:#' . $post_info['user_colour'] . '">' . $post_info['username'] . '</span>' : $post_info['username'];

		// Process message, leave it uncensored
		$post_info['post_text'] = $post_info['post_text'];

		if ($post_info['bbcode_bitfield'])
		{
			require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';

			$bbcode = new bbcode($post_info['bbcode_bitfield']);
			$bbcode->bbcode_second_pass($post_info['post_text'], $post_info['bbcode_uid'], $post_info['bbcode_bitfield']);
		}
		$post_info['post_text'] = smiley_text($post_info['post_text']);

		$_CLASS['core_template']->assign_array(array(
			'S_MCP_QUEUE'			=> true,
			'S_APPROVE_ACTION'		=> generate_link("forums&amp;file=mcp&amp;i=queue&amp;p=$post_id&amp;f=$forum_id"),
			'S_CAN_VIEWIP'			=> $_CLASS['auth']->acl_get('m_info', $post_info['forum_id']),
			'S_POST_REPORTED'		=> $post_info['post_reported'],
			'S_POST_UNAPPROVED'		=> !$post_info['post_approved'],
			'S_POST_LOCKED'			=> $post_info['post_edit_locked'],
//					'S_USER_NOTES'			=> ($post_info['user_notes']) ? true : false,

			'U_EDIT'				=> $_CLASS['auth']->acl_get('m_edit', $post_info['forum_id']) ? generate_link("forums&amp;file=posting&amp;mode=edit&amp;f={$post_info['forum_id']}&amp;p={$post_info['post_id']}") : '',
			'U_MCP_APPROVE'			=>  generate_link('forums&amp;file=mcp&amp;i=queue&amp;mode=approve_details&amp;f=' . $post_info['forum_id'] . '&amp;p=' . $post_id),
			'U_MCP_REPORT'			=>  generate_link('forums&amp;file=mcp&amp;i=reports&amp;mode=report_details&amp;f=' . $post_info['forum_id'] . '&amp;p=' . $post_id),
			'U_MCP_USER_NOTES'		=>  generate_link('forums&amp;file=mcp&amp;i=notes&amp;mode=user_notes&amp;u=' . $post_info['user_id']),
			'U_MCP_WARN_USER'		=> $_CLASS['auth']->acl_get('m_warn') ? generate_link('forums&amp;file=mcp&amp;i=warn&amp;mode=warn_user&amp;u=' . $post_info['user_id']) : '',
			'U_VIEW_POST'			=> generate_link('forums&amp;file=viewtopic&amp;p=' . $post_info['post_id'] . '#p' . $post_info['post_id']),
			'U_VIEW_PROFILE'		=> generate_link('members_list&amp;mode=viewprofile&amp;u=' . $post_info['user_id']),
			'U_VIEW_TOPIC'			=> generate_link('forums&amp;file=viewtopic&amp;t=' . $post_info['topic_id']),

			'RETURN_QUEUE'			=> sprintf($_CLASS['core_user']->get_lang('RETURN_QUEUE'), '<a href="' . generate_link('forums&amp;file=mcp&amp;i=queue' . (($topic_id) ? '&amp;mode=unapproved_topics' : '&amp;mode=unapproved_posts')) . "&amp;start=$start\">", '</a>'),
			'REPORTED_IMG'			=> $_CLASS['core_user']->img('icon_reported', $_CLASS['core_user']->lang['POST_REPORTED']),
			'UNAPPROVED_IMG'		=> $_CLASS['core_user']->img('icon_unapproved', $_CLASS['core_user']->lang['POST_UNAPPROVED']),
			'EDIT_IMG'				=> $_CLASS['core_user']->img('btn_edit', $_CLASS['core_user']->lang['EDIT_POST']),

			'POSTER_NAME'			=> $poster,
			'POST_PREVIEW'			=> $post_info['post_text'],
			'POST_SUBJECT'			=> $post_info['post_subject'],
			'POST_DATE'				=> $_CLASS['core_user']->format_date($post_info['post_time']),
			'POST_IP'				=> $post_info['poster_ip'],
			'POST_IPADDR'			=> @gethostbyaddr($post_info['poster_ip']),
			'POST_ID'				=> $post_info['post_id']
		));

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP_QUEUE'), 'modules/forums/mcp_post.html');
	break;

	case 'unapproved_topics':
	case 'unapproved_posts':
		$forum_info = array();
		$topic_id = get_variable('t', 'REQUEST', false, 'int');

		if ($topic_id)
		{
			$topic_info = get_topic_data(array($topic_id));

			if (empty($topic_info[$topic_id]))
			{
				trigger_error('TOPIC_NOT_EXIST');
			}

			$topic_info = $topic_info[$topic_id];
			$forum_id = $topic_info['forum_id'];
		}

		$forum_list_approve = get_forum_list('m_approve', false, true);

		if (!$forum_id)
		{
			$forum_list = array();
			foreach ($forum_list_approve as $row)
			{
				$forum_list[] = $row['forum_id'];
			}
			
			if (!$forum_list = implode(', ', $forum_list))
			{
				trigger_error('NOT_MODERATOR');
			}

			$sql = 'SELECT SUM(forum_topics) as sum_forum_topics 
				FROM ' . FORUMS_FORUMS_TABLE . "
				WHERE forum_id IN ($forum_list)";
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			$forum_info['forum_topics'] = (int) $row['sum_forum_topics'];
		}
		else
		{
			$forum_info = get_forum_data(array($forum_id), 'm_approve');

			if (empty($forum_info[$forum_id]))
			{
				trigger_error('NOT_MODERATOR');
			}

			$forum_info = $forum_info[$forum_id];
			$forum_list = $forum_id;
		}

		$forum_options = '<option value="0"' . (($forum_id == 0) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['ALL_FORUMS'] . '</option>';
		foreach ($forum_list_approve as $row)
		{
			$forum_options .= '<option value="' . $row['forum_id'] . '"' . (($forum_id == $row['forum_id']) ? ' selected="selected"' : '') . '>' . $row['forum_name'] . '</option>';
		}

		mcp_sorting($mode, $sort_days, $sort_key, $sort_dir, $sort_by_sql, $sort_order_sql, $total, $forum_id);

		$forum_topics = ($total == -1) ? $forum_info['forum_topics'] : $total;
		$limit_time_sql = ($sort_days) ? 'AND t.topic_last_post_time >= ' . ($_CLASS['core_user']->time - ($sort_days * 86400)) : '';

		if ($mode === 'unapproved_posts')
		{
			$sql = 'SELECT DISTINCT p.post_id
				FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_TOPICS_TABLE . ' t' . (($sort_order_sql{0} == 'u') ? ', ' . CORE_USERS_TABLE . ' u' : '') . "
				WHERE p.forum_id IN (0, $forum_list)
					AND p.post_approved = 0
					" . (($sort_order_sql{0} == 'u') ? 'AND u.user_id = p.poster_id' : '') . '
					' . (($topic_id) ? 'AND p.topic_id = ' . $topic_id : '') . "
					AND t.topic_id = p.topic_id
					AND t.topic_first_post_id <> p.post_id
				ORDER BY $sort_order_sql";
			$result = $_CLASS['core_db']->query_limit($sql, $config['topics_per_page'], $start);

			$i = 0;
			$post_ids = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$post_ids[] = $row['post_id'];
				$row_num[$row['post_id']] = $i++;
			}

			if (!Empty($post_ids))
			{
				$sql = 'SELECT f.forum_id, f.forum_name, t.topic_id, t.topic_title, p.post_id, p.post_username, p.poster_id, p.post_time, u.username
					FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_FORUMS_TABLE . ' f, ' . FORUMS_TOPICS_TABLE . ' t, ' . CORE_USERS_TABLE . " u
					WHERE p.post_id IN (" . implode(', ', $post_ids) . ")
						AND t.topic_id = p.topic_id
						AND f.forum_id = p.forum_id
						AND u.user_id = p.poster_id";

				$rowset = array_flip($post_ids);
				unset($post_ids);

				$result = $_CLASS['core_db']->query($sql);
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$rowset[$row['post_id']] = $row;
				}
				$_CLASS['core_db']->free_result($result);


				/*$post_data = $rowset = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$post_data[$row['post_id']] = $row;
				}
				$_CLASS['core_db']->free_result($result);

				foreach ($post_ids as $post_id)
				{
					$rowset[] = $post_data[$post_id];
				}
				unset($post_data, $post_ids);*/
			}
			else
			{
				$rowset = array();
			}
		}
		else
		{
			$sql = 'SELECT f.forum_id, f.forum_name, t.topic_id, t.topic_title, t.topic_time AS post_time, t.topic_poster AS poster_id, t.topic_first_post_id AS post_id, t.topic_first_poster_name AS username
				FROM ' . FORUMS_TOPICS_TABLE . ' t, ' . FORUMS_FORUMS_TABLE . " f
				WHERE t.topic_approved = 0
					AND t.forum_id IN ($forum_list)
					AND f.forum_id = t.forum_id
				ORDER BY $sort_order_sql";
			$result = $_CLASS['core_db']->query_limit($sql, $config['topics_per_page'], $start);

			$rowset = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$rowset[] = $row;
			}
			$_CLASS['core_db']->free_result($result);
		}

		foreach ($rowset as $row)
		{
			if ($row['poster_id'] == ANONYMOUS)
			{
				$poster = (!empty($row['post_username'])) ? $row['post_username'] : $_CLASS['core_user']->lang['GUEST'];
			}
			else
			{
				$poster = $row['username'];
			}

			$s_checkbox = '<input type="checkbox" name="post_id_list[]" value="' . $row['post_id'] . '" />';

			$_CLASS['core_template']->assign_vars_array('postrow', array(
				'U_VIEWFORUM'	=> generate_link('forums&amp;file=viewforum&amp;f=' . $row['forum_id']),
				// Q: Why accessing the topic by a post_id instead of its topic_id?
				// A: To prevent the post from being hidden because of wrong encoding or different charset
				'U_VIEWTOPIC'	=> generate_link('forums&amp;file=viewtopic&amp;p=' . $row['post_id'] . (($mode === 'unapproved_posts') ? '#' . $row['post_id'] : '')),
				'U_VIEW_DETAILS'=> generate_link("forums&amp;file=mcp&amp;i=queue&amp;start=$start&amp;mode=approve_details&amp;p={$row['post_id']}" . (($mode === 'unapproved_topics') ? "&amp;t={$row['topic_id']}" : '')),
				'U_VIEWPROFILE'	=> ($row['poster_id'] != ANONYMOUS) ? generate_link("members_list&amp;mode=viewprofile&amp;u={$row['poster_id']}") : '',

				'POST_ID'		=> $row['post_id'],
				'FORUM_NAME'	=> ($row['forum_id']) ? $row['forum_name'] : $_CLASS['core_user']->lang['GLOBAL_ANNOUNCEMENT'],
				'POST_SUBJECT'	=> $row['topic_title'],
				'POSTER'		=> $poster,
				'POST_TIME'		=> $_CLASS['core_user']->format_date($row['post_time']),
			));
		}
		unset($rowset);

		$pagination = generate_pagination('forums&amp;file=mcp&amp;f='.$forum_id, $total, $config['topics_per_page'], $start);

		// Now display the page
		$_CLASS['core_template']->assign_array(array(
			'L_DISPLAY_ITEMS'		=> ($mode === 'unapproved_posts') ? $_CLASS['core_user']->get_lang('DISPLAY_POSTS') : $_CLASS['core_user']->get_lang('DISPLAY_TOPICS'),
			'L_EXPLAIN'				=> ($mode === 'unapproved_posts') ? $_CLASS['core_user']->get_lang('MCP_QUEUE_UNAPPROVED_POSTS_EXPLAIN') : $_CLASS['core_user']->get_lang('MCP_QUEUE_UNAPPROVED_TOPICS_EXPLAIN'),
			'L_TITLE'				=> ($mode === 'unapproved_posts') ? $_CLASS['core_user']->get_lang('MCP_QUEUE_UNAPPROVED_POSTS') : $_CLASS['core_user']->get_lang('MCP_QUEUE_UNAPPROVED_TOPICS'),
			'L_ONLY_TOPIC'			=> ($topic_id) ? sprintf($_CLASS['core_user']->lang['ONLY_TOPIC'], $topic_info['topic_title']) : '',

			'S_FORUM_OPTIONS'		=> $forum_options,
			'S_MCP_ACTION'			=> generate_link("forums&amp;file=mcp&amp;i=queue&amp;t=$topic_id&amp;f=$forum_id"),//build_url(array('t', 'f', 'sd', 'st', 'sk')),
			'S_TOPICS'				=> ($mode === 'unapproved_posts') ? false : true,

			'PAGINATION'			=> $pagination['formated'],
			'PAGINATION_ARRAY'		=> $pagination['array'],
			'PAGE_NUMBER'			=> on_page($total, $config['topics_per_page'], $start),
			'TOPIC_ID'				=> $topic_id,
			'TOTAL'					=> $total
		));

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP_QUEUE'), 'modules/forums/mcp_queue.html');
	break;
}


/**
* Approve Post/Topic
*/
function approve_post($post_id_list, $mode)
{
	global $_CLASS, $_CORE_CONFIG, $config;

	$forum_id = request_var('f', 0);

	if (!check_ids($post_id_list, FORUMS_POSTS_TABLE, 'post_id', 'm_approve'))
	{
		trigger_error('NOT_AUTHORIZED');
	}

	$redirect = get_variable('redirect', 'POST', $_CLASS['core_user']->data['session_url']);
	$success_msg = '';

	$s_hidden_fields = build_hidden_fields(array(
		'i'				=> 'queue',
		'f'				=> $forum_id,
		'mode'			=> $mode,
		'post_id_list'	=> $post_id_list,
		'action'		=> 'approve',
		'redirect'		=> $redirect
	));

	$_CLASS['core_template']->assign_array(array(
		'S_NOTIFY_POSTER'	=> true,
		'S_APPROVE'			=> true
	));

	if (display_confirmation($_CLASS['core_user']->get_lang('APPROVE_POST' . ((sizeof($post_id_list) == 1) ? '' : 'S')), $s_hidden_fields, 'modules/forums/mcp_approve.html'))
	{
		$notify_poster = (isset($_REQUEST['notify_poster'])) ? true : false;
	
		$post_info = get_post_data($post_id_list, 'm_approve');
		
		// If Topic -> total_topics = total_topics+1, total_posts = total_posts+1, forum_topics = forum_topics+1, forum_posts = forum_posts+1
		// If Post -> total_posts = total_posts+1, forum_posts = forum_posts+1, topic_replies = topic_replies+1
		
		$total_topics = $total_posts = $forum_topics = $forum_posts = 0;
		$topic_approve_sql = $topic_replies_sql = $post_approve_sql = $topic_id_list = array();
		
		foreach ($post_info as $post_id => $post_data)
		{
			$topic_id_list[$post_data['topic_id']] = 1;
			
			// Topic or Post. ;)
			if ($post_data['topic_first_post_id'] == $post_id)
			{
				if ($post_data['forum_id'])
				{
					$total_topics++;
					$forum_topics++;
				}

				$topic_approve_sql[] = $post_data['topic_id'];
			}
			else
			{
				if (!isset($topic_replies_sql[$post_data['topic_id']]))
				{
					$topic_replies_sql[$post_data['topic_id']] = 1;
				}
				else
				{
					$topic_replies_sql[$post_data['topic_id']]++;
				}
			}

			if ($post_data['forum_id'])
			{
				$total_posts++;
				$forum_posts++;
			}

			$post_approve_sql[] = $post_id;
		}
		
		if (sizeof($topic_approve_sql))
		{
			$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . '
				SET topic_approved = 1
				WHERE topic_id IN (' . implode(', ', $topic_approve_sql) . ')';
			$_CLASS['core_db']->query($sql);
		}

		if (sizeof($post_approve_sql))
		{
			$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . '
				SET post_approved = 1
				WHERE post_id IN (' . implode(', ', $post_approve_sql) . ')';
			$_CLASS['core_db']->query($sql);
		}

		if (sizeof($topic_replies_sql))
		{
			foreach ($topic_replies_sql as $topic_id => $num_replies)
			{
				$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . "
					SET topic_replies = topic_replies + $num_replies
					WHERE topic_id = $topic_id";
				$_CLASS['core_db']->query($sql);
			}
		}

		if ($forum_topics || $forum_posts)
		{
			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET ';
			$sql .= ($forum_topics) ? "forum_topics = forum_topics + $forum_topics" : '';
			$sql .= ($forum_topics && $forum_posts) ? ', ' : '';
			$sql .= ($forum_posts) ? "forum_posts = forum_posts + $forum_posts" : '';
			$sql .= " WHERE forum_id = $forum_id";

			$_CLASS['core_db']->query($sql);
		}
		
		if ($total_topics)
		{
			set_config('num_topics', $config['num_topics'] + $total_topics, true);
		}

		if ($total_posts)
		{
			set_config('num_posts', $config['num_posts'] + $total_posts, true);
		}
		unset($topic_approve_sql, $topic_replies_sql, $post_approve_sql);

		update_post_information('topic', array_keys($topic_id_list));
		update_post_information('forum', $forum_id);
		unset($topic_id_list);
		
		// Notify Poster?
		if ($notify_poster)
		{
			require_once SITE_FILE_ROOT.'includes/mailer.php';

			$mailer = new core_mailer();

			foreach ($post_info as $post_id => $post_data)
			{
				if ($post_data['poster_id'] == ANONYMOUS)
				{
					continue;
				}

				$post_data['post_subject'] = censor_text($post_data['post_subject'], true);
				$post_data['topic_title'] = censor_text($post_data['topic_title'], true);

				if ($post_data['post_id'] == $post_data['topic_first_post_id'] && $post_data['post_id'] == $post_data['topic_last_post_id'])
				{
					$email_template = 'topic_approved.txt';
					$subject = 'Topic Approved - '.$post_data['topic_title'];
				}
				else
				{
					$email_template = 'post_approved.txt';
					$subject = 'Post Approved - '.$post_data['post_subject'];
				}

				$mailer->to($post_data['user_email'], $post_data['username']);
				//$mailer->reply_to($_CORE_CONFIG['email']['site_email']);
				$mailer->subject($subject);
				//$messenger->im($post_data['user_jabber'], $post_data['username']);

				$_CLASS['core_template']->assign_array(array(
					'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
					'USERNAME'		=> $post_data['username'],
					'POST_SUBJECT'	=> $post_data['post_subject'],
					'TOPIC_TITLE'	=> $post_data['topic_title'],

					'U_VIEW_TOPIC'	=> generate_link("forums&amp;file=viewtopic&amp;t={$post_data['topic_id']}&amp;e=0"),
					'U_VIEW_POST'	=> generate_link("forums&amp;file=viewtopic&amp;p=$post_id&amp;e=$post_id")
				));

				$mailer->message = trim($_CLASS['core_template']->display('email/forums/'.$email_template, true));
				$mailer->send();
			}
		}

		// Send out normal user notifications
		foreach ($post_info as $post_id => $post_data)
		{
			if ($post_id == $post_data['topic_first_post_id'] && $post_id == $post_data['topic_last_post_id'])
			{
				// Forum Notifications
				user_notification('post', $post_data['topic_title'], $post_data['topic_title'], $post_data['forum_name'], $forum_id, $post_data['topic_id'], $post_id);
			}
			else
			{
				// Topic Notifications
				user_notification('reply', $post_data['post_subject'], $post_data['topic_title'], $post_data['forum_name'], $forum_id, $post_data['topic_id'], $post_id);
			}
		}
		unset($post_info);

		if ($forum_topics)
		{
			$success_msg = ($forum_topics == 1) ? 'TOPIC_APPROVED_SUCCESS' : 'TOPICS_APPROVED_SUCCESS';
		}
		else
		{
			$success_msg = (sizeof($post_id_list) == 1) ? 'POST_APPROVED_SUCCESS' : 'POSTS_APPROVED_SUCCESS';
		}
	}

	$redirect = request_var('redirect', generate_link('forums'));

	if (!$success_msg)
	{
		url_redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, $redirect);
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>') . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id) . '">', '</a>'));
	}
}

/**
* Disapprove Post/Topic
*/
function disapprove_post($post_id_list, $mode)
{
	global $_CLASS, $_CORE_CONFIG, $config;

	$forum_id = request_var('f', 0);

	if (!check_ids($post_id_list, FORUMS_POSTS_TABLE, 'post_id', 'm_approve'))
	{
		trigger_error('NOT_AUTHORIZED');
	}

	$redirect = request_var('redirect', $_CLASS['core_user']->data['session_page']);
	$reason = request_var('reason', '', true);
	$reason_id = request_var('reason_id', 0);
	$success_msg = $additional_msg = '';

	$s_hidden_fields = build_hidden_fields(array(
		'i'				=> 'queue',
		'f'				=> $forum_id,
		'mode'			=> $mode,
		'post_id_list'	=> $post_id_list,
		'mode'			=> 'disapprove',
		'redirect'		=> $redirect
	));

	$notify_poster = isset($_REQUEST['notify_poster']);
	$disapprove_reason = '';

	if ($reason_id)
	{
		$sql = 'SELECT reason_title, reason_description
			FROM ' . FORUMS_REPORTS_REASONS_TABLE . " 
			WHERE reason_id = $reason_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row || (!$reason && $row['reason_name'] === 'other'))
		{
			$additional_msg = $_CLASS['core_user']->lang['NO_REASON_DISAPPROVAL'];
			unset($_POST['confirm']);
		}
		else
		{
			$disapprove_reason = ($row['reason_title'] != 'other') ? ((isset($_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_title'])])) ? $_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_title'])] : $row['reason_description']) : '';
			$disapprove_reason .= ($reason) ? "\n\n" . $reason : '';
			unset($reason);
		}
	}

	require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';

	$reason = display_reasons($reason_id);

	$_CLASS['core_template']->assign_array(array(
		'S_NOTIFY_POSTER'	=> true,
		'S_APPROVE'			=> false,
		'REASON'			=> $reason,
		'ADDITIONAL_MSG'	=> $additional_msg
	));

	if (display_confirmation($_CLASS['core_user']->get_lang('DISAPPROVE_POST' . ((sizeof($post_id_list) == 1) ? '' : 'S')), $s_hidden_fields, 'modules/forums/mcp_approve.html'))
	{
		$post_info = get_post_data($post_id_list, 'm_approve');
		
		// If Topic -> forum_topics_real -= 1
		// If Post -> topic_replies_real -= 1
		
		$forum_topics_real = 0;
		$topic_replies_real_sql = $post_disapprove_sql = $topic_id_list = array();
		
		foreach ($post_info as $post_id => $post_data)
		{
			$topic_id_list[$post_data['topic_id']] = 1;
			
			// Topic or Post. ;)
			if ($post_data['topic_first_post_id'] == $post_id && $post_data['topic_last_post_id'] == $post_id)
			{
				if ($post_data['forum_id'])
				{
					$forum_topics_real++;
				}
			}
			else
			{
				if (!isset($topic_replies_real_sql[$post_data['topic_id']]))
				{
					$topic_replies_real_sql[$post_data['topic_id']] = 1;
				}
				else
				{
					$topic_replies_real_sql[$post_data['topic_id']]++;
				}
			}

			$post_disapprove_sql[] = $post_id;
		}
		
		if ($forum_topics_real)
		{
			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
				SET forum_topics_real = forum_topics_real - $forum_topics_real
				WHERE forum_id = $forum_id";
			$_CLASS['core_db']->query($sql);
		}

		if (!empty($topic_replies_real_sql))
		{
			foreach ($topic_replies_real_sql as $topic_id => $num_replies)
			{
				$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . "
					SET topic_replies_real = topic_replies_real - $num_replies
					WHERE topic_id = $topic_id";
				$_CLASS['core_db']->query($sql);
			}
		}

		if (sizeof($post_disapprove_sql))
		{
			if (!function_exists('delete_posts'))
			{
				require_once SITE_FILE_ROOT.'includes/forums/functions_admin.php';
			}

			// We do not check for permissions here, because the moderator allowed approval/disapproval should be allowed to delete the disapproved posts
			delete_posts('post_id', $post_disapprove_sql);
		}
		unset($post_disapprove_sql, $topic_replies_real_sql);

		update_post_information('topic', array_keys($topic_id_list));
		update_post_information('forum', $forum_id);
		unset($topic_id_list);
		
		// Notify Poster?
		if ($notify_poster)
		{
			require_once SITE_FILE_ROOT.'includes/mailer.php';

			$mailer = new core_mailer();

			foreach ($post_info as $post_id => $post_data)
			{
				if ($post_data['poster_id'] == ANONYMOUS)
				{
					continue;
				}

				$post_data['post_subject'] = censor_text($post_data['post_subject'], true);
				$post_data['topic_title'] = censor_text($post_data['topic_title'], true);

				if ($post_data['post_id'] == $post_data['topic_first_post_id'] && $post_data['post_id'] == $post_data['topic_last_post_id'])
				{
					$email_template = 'topic_disapproved.txt';
					$subject = 'Topic Disapproved - '.$post_data['topic_title'];
				}
				else
				{
					$email_template = 'post_disapproved.txt';
					$subject = 'Post Disapproved - '.$post_data['post_subject'];
				}

				$mailer->to($post_data['user_email'], $post_data['username']);
				//$mailer->reply_to($_CORE_CONFIG['email']['site_email']);
				$mailer->subject($subject);
				//$messenger->im($post_data['user_jabber'], $post_data['username']);

				$_CLASS['core_template']->assign_array(array(
					'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
					'USERNAME'		=> $post_data['username'],
					'REASON'		=> stripslashes($disapprove_reason),
					'POST_SUBJECT'	=> $post_data['post_subject'],
					'TOPIC_TITLE'	=> $post_data['topic_title'],
				));

				$mailer->message = trim($_CLASS['core_template']->display('email/forums/'.$email_template, true));
				$mailer->send();
			}
		}
		unset($post_info, $disapprove_reason);

		if ($forum_topics_real)
		{
			$success_msg = ($forum_topics_real == 1) ? 'TOPIC_DISAPPROVED_SUCCESS' : 'TOPICS_DISAPPROVED_SUCCESS';
		}
		else
		{
			$success_msg = (sizeof($post_id_list) == 1) ? 'POST_DISAPPROVED_SUCCESS' : 'POSTS_DISAPPROVED_SUCCESS';
		}
	}

	$redirect = request_var('redirect', generate_link('forums'));

	if (!$success_msg)
	{
		redirect($redirect);
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, generate_link("forums&amp;file=viewforum&amp;f=$forum_id"));
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id) . '">', '</a>'));
	}
}

?>