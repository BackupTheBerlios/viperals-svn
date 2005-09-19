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
// $Id: mcp_front.php,v 1.3 2004/07/19 20:13:16 acydburn Exp $
//
// FILENAME  : mcp_front.php
// STARTED   : Thu Jul 08, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//
// TODO:
//	- add list of forums user is moderator in (with links to common management facilities)
//	- add statistics (how many reports handled, how many posts locked/moved... etc.? - 
//			those would be only valid from the time the log is valid (and not purged)
//

function mcp_front_view($id, $mode, $action, $url)
{
	global $_CLASS;

	// Latest 5 unapproved
	$forum_list_all = get_forum_list('m_approve');
	$post_list = array();
	$forum_names = array();

	$forum_id = request_var('f', 0);
	
	$_CLASS['core_template']->assign('S_SHOW_UNAPPROVED', !empty($forum_list_all));

	if (!empty($forum_list_all))
	{
		$sql = 'SELECT COUNT(post_id) AS total
			FROM ' . FORUMS_POSTS_TABLE . '
			WHERE forum_id IN (0, ' . implode(', ', $forum_list_all) . ')
				AND post_approved = 0';
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$total = isset($row['total']) ? (int) $row['total'] : false;;

		if ($total)
		{
			$sql = 'SELECT post_id, forum_id
				FROM ' . FORUMS_POSTS_TABLE . '
				WHERE forum_id IN (0, ' . implode(', ', $forum_list) . ')
					AND post_approved = 0
				ORDER BY post_id DESC';
			$result = $_CLASS['core_db']->query_limit($sql, 5);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$post_list[] = $row['post_id'];
				$forum_list[] = $row['forum_id'];
			}
			unset($forum_list_all);

			$sql = 'SELECT forum_id, forum_name
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_id IN (' . implode(', ', $forum_list) . ')';
			$result = $_CLASS['core_db']->query($sql);
			
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$forum_names[$row['forum_id']] = $row['forum_name'];
			}
			$_CLASS['core_db']->free_result($result);

			unset($forum_id);

			$sql = 'SELECT p.post_id, p.post_subject, p.post_time, p.poster_id, p.post_username, u.username, t.topic_id, t.topic_title, t.topic_first_post_id, p.forum_id
				FROM ' . FORUMS_POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t,  ' . USERS_TABLE . ' u
				WHERE p.post_id IN (' . implode(', ', $post_list) . ')
					AND t.topic_id = p.topic_id
					AND p.poster_id = u.user_id
				ORDER BY p.post_id DESC';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('unapproved', array(
					'U_POST_DETAILS'=> generate_link($url . '&amp;p=' . $row['post_id'] . '&amp;mode=post_details'),
					'U_MCP_FORUM'	=> ($row['forum_id']) ? generate_link($url . '&amp;f=' . $row['forum_id'] . '&amp;mode=forum_view') : '',
					'U_MCP_TOPIC'	=> generate_link($url . '&amp;t=' . $row['topic_id'] . '&amp;mode=topic_view'),
					'U_FORUM'		=> ($row['forum_id']) ? generate_link('Forums&amp;file=viewforum&amp;f=' . $row['forum_id']) : '',
					'U_TOPIC'		=> generate_link('Forums&amp;file=viewtopic&amp;f=' . (($row['forum_id']) ? $row['forum_id'] : $forum_id) . '&amp;t=' . $row['topic_id']),
					'U_AUTHOR'		=> ($row['poster_id'] == ANONYMOUS) ? '' : generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']),

					'FORUM_NAME'	=> ($row['forum_id']) ? $forum_names[$row['forum_id']] : $_CLASS['core_user']->lang['GLOBAL_ANNOUNCEMENT'],
					'TOPIC_TITLE'	=> $row['topic_title'],
					'AUTHOR'		=> ($row['poster_id'] == ANONYMOUS) ? (($row['post_username']) ? $row['post_username'] : $_CLASS['core_user']->lang['GUEST']) : $row['username'],
					'SUBJECT'		=> ($row['post_subject']) ? $row['post_subject'] : $_CLASS['core_user']->lang['NO_SUBJECT'],
					'POST_TIME'		=> $_CLASS['core_user']->format_date($row['post_time']))
				);				
			}
		}

		if ($total)
		{
			$_CLASS['core_template']->assign_array(array(
				'L_UNAPPROVED_TOTAL'		=> ($total == 1) ? $_CLASS['core_user']->lang['UNAPPROVED_POST_TOTAL'] : sprintf($_CLASS['core_user']->lang['UNAPPROVED_POSTS_TOTAL'], $total),
				'S_HAS_UNAPPROVED_POSTS'	=> true
			));
		}
		else
		{
			$_CLASS['core_template']->assign_array(array(
				'L_UNAPPROVED_TOTAL'		=> $_CLASS['core_user']->lang['UNAPPROVED_POSTS_ZERO_TOTAL'],
				'S_HAS_UNAPPROVED_POSTS'	=> false
			));
		}
	}

	// Latest 5 reported
	//$forum_list = get_forum_list('m_');
				
	$_CLASS['core_template']->assign('S_SHOW_REPORTS', !empty($forum_list));

	if (!empty($forum_list))
	{
		$sql = 'SELECT COUNT(r.report_id) AS total
			FROM ' . FORUMS_REPORTS_TABLE . ' r, ' . POSTS_TABLE . ' p
			WHERE r.post_id = p.post_id
				AND p.forum_id IN (0, ' . implode(', ', $forum_list) . ')';
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$total = $row['total'];

		if ($total)
		{
			$sql = 'SELECT r.*, p.post_id, p.post_subject, u.username, t.topic_id, t.topic_title, f.forum_id, f.forum_name
				FROM ' . FORUMS_REPORTS_TABLE . ' r, ' . FORUMS_REASONS_TABLE . ' rr,' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_TOPICS_TABLE . ' t, ' . USERS_TABLE . ' u
				LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON f.forum_id = p.forum_id
				WHERE r.post_id = p.post_id
					AND r.reason_id = rr.reason_id
					AND p.topic_id = t.topic_id
					AND r.user_id = u.user_id
					AND p.forum_id IN (0, ' . implode(', ', $forum_list) . ')
				ORDER BY p.post_id DESC';
			$result = $_CLASS['core_db']->query_limit($sql, 5);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('report', array(
					'U_POST_DETAILS'=> generate_link($url . '&amp;p=' . $row['post_id'] . '&amp;mode=post_details'),
					'U_MCP_FORUM'	=> ($row['forum_id']) ? generate_link($url . '&amp;f=' . $row['forum_id'] . '&amp;mode=forum_view') : '',
					'U_MCP_TOPIC'	=> generate_link($url . '&amp;t=' . $row['topic_id'] . '&amp;mode=topic_view'),
					'U_FORUM'		=> ($row['forum_id']) ? generate_link('Forums&amp;file=viewforum&amp;f=' . $row['forum_id']) : '',
					'U_TOPIC'		=> generate_link('Forums&amp;file=viewtopic&amp;f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id']),
					'U_REPORTER'	=> ($row['user_id'] == ANONYMOUS) ? '' : generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),

					'FORUM_NAME'	=> ($row['forum_id']) ? $row['forum_name'] : $_CLASS['core_user']->lang['POST_GLOBAL'],
					'TOPIC_TITLE'	=> $row['topic_title'],
					'REPORTER'		=> ($row['user_id'] == ANONYMOUS) ? $_CLASS['core_user']->lang['GUEST'] : $row['username'],
					'SUBJECT'		=> ($row['post_subject']) ? $row['post_subject'] : $_CLASS['core_user']->lang['NO_SUBJECT'],
					'REPORT_TIME'	=> $_CLASS['core_user']->format_date($row['report_time']))
				);				
			}
		}

		if ($total == 0)
		{
			$_CLASS['core_template']->assign_array(array(
				'L_REPORTS_TOTAL'	=>	$_CLASS['core_user']->lang['REPORTS_ZERO_TOTAL'],
				'S_HAS_REPORTS'		=>	false)
			);
		}
		else
		{
			$_CLASS['core_template']->assign_array(array(
				'L_REPORTS_TOTAL'	=> ($total == 1) ? $_CLASS['core_user']->lang['REPORT_TOTAL'] : sprintf($_CLASS['core_user']->lang['REPORTS_TOTAL'], $total),
				'S_HAS_REPORTS'		=> true)
			);
		}
	}

	// Latest 5 logs
	$forum_list = get_forum_list(array('m_', 'a_general'));

	if (!empty($forum_list))
	{
		// Add forum_id 0 for global announcements
		$forum_list[] = 0;

		$log_count = 0;
		$log = array();
		view_log('mod', $log, $log_count, 5, 0, $forum_list);

		foreach ($log as $row)
		{
			$_CLASS['core_template']->assign_vars_array('log', array(
				'USERNAME'		=> $row['username'],
				'IP'			=> $row['ip'],
				'TIME'			=> $_CLASS['core_user']->format_date($row['time']),
				'ACTION'		=> $row['action'],
				'U_VIEWTOPIC'	=> $row['viewtopic'],
				'U_VIEWLOGS'	=> $row['viewlogs'])
			);
		}
	}

	$_CLASS['core_template']->assign_array(array(
		'S_SHOW_LOGS'	=> !empty($forum_list),
		'S_HAS_LOGS'	=> !empty($log)
	));

	$_CLASS['core_template']->assign('S_MCP_ACTION', generate_link($url));
	make_jumpbox(generate_link($url . '&amp;mode=forum_view'), 0, false, 'm_');
}

?>