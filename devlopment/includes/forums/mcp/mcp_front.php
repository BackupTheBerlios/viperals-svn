<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
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
// COPYRIGHT : � 2004 phpBB Group
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
	global $_CLASS, $db;

	$_CLASS['template']->assign(array(
		'L_FORUM'						=> $_CLASS['user']->lang['FORUM'],
		'L_TOPIC'						=> $_CLASS['user']->lang['TOPIC'],
		'L_SUBJECT'						=> $_CLASS['user']->lang['SUBJECT'],
		'L_AUTHOR'						=> $_CLASS['user']->lang['AUTHOR'],
		'L_POST_TIME'					=> $_CLASS['user']->lang['POST_TIME'],
		'L_MODERATE'					=> $_CLASS['user']->lang['MODERATE'],
		'L_VIEW_DETAILS'				=> $_CLASS['user']->lang['VIEW_DETAILS'],
		'L_UNAPPROVED_POSTS_ZERO_TOTAL'	=> $_CLASS['user']->lang['UNAPPROVED_POSTS_ZERO_TOTAL'],
		'L_UNAPPROVED_TOTAL'			=> $_CLASS['user']->lang['UNAPPROVED_TOTAL'],
		'L_LATEST_REPORTED'				=> $_CLASS['user']->lang['LATEST_REPORTED'],
		'L_REPORT_TIME'					=> $_CLASS['user']->lang['REPORT_TIME'],
		'L_REPORTER'					=> $_CLASS['user']->lang['REPORTER'],
		'L_NO_ENTRIES'					=> $_CLASS['user']->lang['NO_ENTRIES'],
		'L_REPORTS_ZERO_TOTAL'			=> $_CLASS['user']->lang['REPORTS_ZERO_TOTAL'],
		'L_REPORTS_TOTAL'				=> $_CLASS['user']->lang['REPORTS_TOTAL'],
		'L_LATEST_UNAPPROVED'			=> $_CLASS['user']->lang['LATEST_UNAPPROVED'],			
		'L_USERNAME'					=> $_CLASS['user']->lang['USERNAME'],
		'L_IP'							=> $_CLASS['user']->lang['IP'],
		'L_ACTION'						=> $_CLASS['user']->lang['ACTION'],
		'L_TIME'						=> $_CLASS['user']->lang['TIME'],
		'L_JUMP_TO'						=> $_CLASS['user']->lang['JUMP_TO'],
		'L_VIEW_TOPIC'					=> $_CLASS['user']->lang['VIEW_TOPIC'],
		'L_VIEW_TOPIC_LOGS'				=> $_CLASS['user']->lang['VIEW_TOPIC_LOGS'],
		'L_REPORTS_TOTAL'				=> $_CLASS['user']->lang['REPORTS_TOTAL'],
		'L_GO'							=> $_CLASS['user']->lang['GO'],
		'L_LATEST_LOGS'					=> $_CLASS['user']->lang['LATEST_LOGS'])
	);
	
	// Latest 5 unapproved
	$forum_list = get_forum_list('m_approve');
	$post_list = array();

	$_CLASS['template']->assign('S_SHOW_UNAPPROVED', (!empty($forum_list)) ? true : false);
	if (!empty($forum_list))
	{
		$sql = 'SELECT COUNT(post_id) AS total
			FROM ' . POSTS_TABLE . '
			WHERE forum_id IN (0, ' . implode(', ', $forum_list) . ')
				AND post_approved = 0';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$total = $row['total'];

		if ($total)
		{
			$sql = 'SELECT post_id
				FROM ' . POSTS_TABLE . '
				WHERE forum_id IN (0, ' . implode(', ', $forum_list) . ')
					AND post_approved = 0
				ORDER BY post_id DESC';
			$result = $db->sql_query_limit($sql, 5);
			while ($row = $db->sql_fetchrow($result))
			{
				$post_list[] = $row['post_id'];
			}

			$sql = 'SELECT p.post_id, p.post_subject, p.post_time, p.poster_id, p.post_username, u.username, t.topic_id, t.topic_title, t.topic_first_post_id, f.forum_id, f.forum_name
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f, ' . USERS_TABLE . ' u
				WHERE p.post_id IN (' . implode(', ', $post_list) . ')
					AND t.topic_id = p.topic_id
					AND f.forum_id = p.forum_id
					AND p.poster_id = u.user_id
				ORDER BY p.post_id DESC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$_CLASS['template']->assign_vars_array('unapproved', array(
					'U_POST_DETAILS'=> getlink($url . '&amp;p=' . $row['post_id'] . '&amp;mode=post_details', false, false),
					'U_MCP_FORUM'	=> ($row['forum_id']) ? getlink($url . '&amp;f=' . $row['forum_id'] . '&amp;mode=forum_view', false, false) : '',
					'U_MCP_TOPIC'	=> getlink($url . '&amp;t=' . $row['topic_id'] . '&amp;mode=topic_view', false, false),
					'U_FORUM'		=> ($row['forum_id']) ? getlink('Forums&amp;file=viewforum&amp;f=' . $row['forum_id'], false, false) : '',
					'U_TOPIC'		=> getlink('Forums&amp;file=viewtopic&amp;f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id'], false, false),
					'U_AUTHOR'		=> ($row['poster_id'] == ANONYMOUS) ? '' : getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id'], false, false),

					'FORUM_NAME'	=> ($row['forum_id']) ? $row['forum_name'] : $_CLASS['user']->lang['POST_GLOBAL'],
					'TOPIC_TITLE'	=> $row['topic_title'],
					'AUTHOR'		=> ($row['poster_id'] == ANONYMOUS) ? (($row['post_username']) ? $row['post_username'] : $_CLASS['user']->lang['GUEST']) : $row['username'],
					'SUBJECT'		=> ($row['post_subject']) ? $row['post_subject'] : $_CLASS['user']->lang['NO_SUBJECT'],
					'POST_TIME'		=> $_CLASS['user']->format_date($row['post_time']))
				);				
			}
		}

		if ($total == 0)
		{
			$_CLASS['template']->assign(array(
				'L_UNAPPROVED_TOTAL'		=> $_CLASS['user']->lang['UNAPPROVED_POSTS_ZERO_TOTAL'],
				'S_HAS_UNAPPROVED_POSTS'	=> false)
			);
		}
		else
		{
			$_CLASS['template']->assign(array(
				'L_UNAPPROVED_TOTAL'		=> ($total == 1) ? $_CLASS['user']->lang['UNAPPROVED_POST_TOTAL'] : sprintf($_CLASS['user']->lang['UNAPPROVED_POSTS_TOTAL'], $total),
				'S_HAS_UNAPPROVED_POSTS'	=> true)
			);
		}
	}

	// Latest 5 reported
	$forum_list = get_forum_list('m_');
				
	$_CLASS['template']->assign('S_SHOW_REPORTS', (!empty($forum_list)) ? true : false);
	if (!empty($forum_list))
	{
		$sql = 'SELECT COUNT(r.report_id) AS total
			FROM ' . REPORTS_TABLE . ' r, ' . POSTS_TABLE . ' p
			WHERE r.post_id = p.post_id
				AND p.forum_id IN (0, ' . implode(', ', $forum_list) . ')';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$total = $row['total'];

		if ($total)
		{
			$sql = 'SELECT r.*, p.post_id, p.post_subject, u.username, t.topic_id, t.topic_title, f.forum_id, f.forum_name
				FROM ' . REPORTS_TABLE . ' r, ' . REASONS_TABLE . ' rr,' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . USERS_TABLE . ' u
				LEFT JOIN ' . FORUMS_TABLE . ' f ON f.forum_id = p.forum_id
				WHERE r.post_id = p.post_id
					AND r.reason_id = rr.reason_id
					AND p.topic_id = t.topic_id
					AND r.user_id = u.user_id
					AND p.forum_id IN (0, ' . implode(', ', $forum_list) . ')
				ORDER BY p.post_id DESC';
			$result = $db->sql_query_limit($sql, 5);

			while ($row = $db->sql_fetchrow($result))
			{
				$_CLASS['template']->assign_vars_array('report', array(
					'U_POST_DETAILS'=> getlink($url . '&amp;p=' . $row['post_id'] . '&amp;mode=post_details', false, false),
					'U_MCP_FORUM'	=> ($row['forum_id']) ? getlink($url . '&amp;f=' . $row['forum_id'] . '&amp;mode=forum_view', false, false) : '',
					'U_MCP_TOPIC'	=> getlink($url . '&amp;t=' . $row['topic_id'] . '&amp;mode=topic_view', false, false),
					'U_FORUM'		=> ($row['forum_id']) ? getlink('Forums&amp;file=viewforum&amp;f=' . $row['forum_id'], false, false) : '',
					'U_TOPIC'		=> getlink('Forums&amp;file=viewtopic&amp;f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id'], false, false),
					'U_REPORTER'	=> ($row['user_id'] == ANONYMOUS) ? '' : getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id'], false, false),

					'FORUM_NAME'	=> ($row['forum_id']) ? $row['forum_name'] : $_CLASS['user']->lang['POST_GLOBAL'],
					'TOPIC_TITLE'	=> $row['topic_title'],
					'REPORTER'		=> ($row['user_id'] == ANONYMOUS) ? $_CLASS['user']->lang['GUEST'] : $row['username'],
					'SUBJECT'		=> ($row['post_subject']) ? $row['post_subject'] : $_CLASS['user']->lang['NO_SUBJECT'],
					'REPORT_TIME'	=> $_CLASS['user']->format_date($row['report_time']))
				);				
			}
		}

		if ($total == 0)
		{
			$_CLASS['template']->assign(array(
				'L_REPORTS_TOTAL'	=>	$_CLASS['user']->lang['REPORTS_ZERO_TOTAL'],
				'S_HAS_REPORTS'		=>	false)
			);
		}
		else
		{
			$_CLASS['template']->assign(array(
				'L_REPORTS_TOTAL'	=> ($total == 1) ? $_CLASS['user']->lang['REPORT_TOTAL'] : sprintf($_CLASS['user']->lang['REPORTS_TOTAL'], $total),
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
			$_CLASS['template']->assign_vars_array('log', array(
				'USERNAME'		=> $row['username'],
				'IP'			=> $row['ip'],
				'TIME'			=> $_CLASS['user']->format_date($row['time']),
				'ACTION'		=> $row['action'],
				'U_VIEWTOPIC'	=> $row['viewtopic'],
				'U_VIEWLOGS'	=> $row['viewlogs'])
			);
		}
	}

	$_CLASS['template']->assign(array(
		'S_SHOW_LOGS'	=> (!empty($forum_list)) ? true : false,
		'S_HAS_LOGS'	=> (!empty($log)) ? true : false)
	);

	$_CLASS['template']->assign('S_MCP_ACTION', getlink($url, false, false));
	make_jumpbox(getlink($url . '&amp;mode=forum_view', false, false), 0, false, 'm_');
}

?>