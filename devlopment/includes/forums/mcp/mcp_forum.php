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
// $Id: mcp_forum.php,v 1.3 2004/07/19 20:13:16 acydburn Exp $
//
// FILENAME  : mcp_forum.php
// STARTED   : Thu Jul 08, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//
// TODO:
//

function mcp_forum_view($id, $mode, $action, $url, $forum_info)
{
	global $phpEx, $config;
	global $_CLASS, $db;
	$_CLASS['template']->assign(array(
		'L_DISPLAY_TOPICS'			=> $_CLASS['user']->lang['DISPLAY_TOPICS'],
		'L_SORT_BY'					=> $_CLASS['user']->lang['SORT_BY'],
		'L_GO'						=> $_CLASS['user']->lang['GO'],
		'L_TOPICS'					=> $_CLASS['user']->lang['TOPICS'],
		'L_REPLIES'					=> $_CLASS['user']->lang['REPLIES'],
		'L_LAST_POST'				=> $_CLASS['user']->lang['LAST_POST'],
		'L_MARK'					=> $_CLASS['user']->lang['MARK'],
		'L_SELECT'					=> $_CLASS['user']->lang['SELECT'],
		'L_NO_TOPICS'				=> $_CLASS['user']->lang['NO_TOPICS'],
		'L_DELETE'					=> $_CLASS['user']->lang['DELETE'],
		'L_MOVE'					=> $_CLASS['user']->lang['MOVE'],
		'L_FORK'					=> $_CLASS['user']->lang['FORK'],
		'L_LOCK'					=> $_CLASS['user']->lang['LOCK'],
		'L_UNLOCK'					=> $_CLASS['user']->lang['UNLOCK'],
		'L_RESYNC'					=> $_CLASS['user']->lang['RESYNC'],
		'L_JUMP_TO'					=> $_CLASS['user']->lang['JUMP_TO'],
		'L_GO'						=> $_CLASS['user']->lang['GO'],
		'L_SUBMIT'					=> $_CLASS['user']->lang['SUBMIT'])
	);
	
	if ($action == 'merge_select')
	{
		// Fixes a "bug" that makes forum_view use the same ordering as topic_view
		unset($_POST['sk'], $_POST['sd'], $_REQUEST['sk'], $_REQUEST['sd']);
	}

	$forum_id = $forum_info['forum_id'];
	$start = request_var('start', 0);
	$topic_id_list = request_var('topic_id_list', 0);
	$post_id_list = request_var('post_id_list', 0);
	$topic_id = request_var('t', 0);

	// Resync Topics
	if ($action == 'resync')
	{
		$topic_ids = get_array('topic_id_list', 0);

		if (!$topic_ids)
		{
			$_CLASS['template']->assign('MESSAGE', $_CLASS['user']->lang['NO_TOPIC_SELECTED']);
		}
		else
		{
			mcp_resync_topics($topic_ids);
		}
	}

	$selected_ids = '';
	if ($post_id_list)
	{
		foreach ($post_id_list as $num => $post_id)
		{
			$selected_ids .= '&amp;post_id_list[' . $num . ']=' . $post_id;
		}
	}

	make_jumpbox(getlink($url . "&amp;action=$action&amp;mode=$mode", $forum_id . (($action == 'merge_select') ? $selected_ids : ''), false, false), false, 'm_');

	$topics_per_page = ($forum_info['forum_topics_per_page']) ? $forum_info['forum_topics_per_page'] : $config['topics_per_page'];

	mcp_sorting('viewforum', $sort_days, $sort_key, $sort_dir, $sort_by_sql, $sort_order_sql, $total, $forum_id);
	$forum_topics = ($total == -1) ? $forum_info['forum_topics'] : $total;
	$limit_time_sql = ($sort_days) ? 'AND t.topic_last_post_time >= ' . (time() - ($sort_days * 86400)) : '';

	$_CLASS['template']->assign(array(
		'FORUM_NAME'			=> $forum_info['forum_name'],

		'REPORTED_IMG'			=> $_CLASS['user']->img('icon_reported', 'TOPIC_REPORTED'),
		'UNAPPROVED_IMG'		=> $_CLASS['user']->img('icon_unapproved', 'TOPIC_UNAPPROVED'),

		'S_CAN_DELETE'			=> $_CLASS['auth']->acl_get('m_delete', $forum_id),
		'S_CAN_MOVE'			=> $_CLASS['auth']->acl_get('m_move', $forum_id),
		'S_CAN_FORK'			=> $_CLASS['auth']->acl_get('m_', $forum_id),
		'S_CAN_LOCK'			=> $_CLASS['auth']->acl_get('m_lock', $forum_id),
		'S_CAN_SYNC'			=> $_CLASS['auth']->acl_get('m_', $forum_id),
		'S_CAN_APPROVE'			=> $_CLASS['auth']->acl_get('m_approve', $forum_id),

		'U_VIEW_FORUM'			=> getlink('Forums&amp;file=viewforum&amp;f=' . $forum_id),
		'S_MCP_ACTION'			=> getlink($url . "&amp;action=$action&amp;mode=$mode&amp;start=$start" . (($action == 'merge_select') ? $selected_ids : ''), false, false),

		'PAGINATION'			=> generate_pagination($url . "&amp;action=$action&amp;mode=$mode" . (($action == 'merge_select') ? $selected_ids : ''), $forum_topics, $topics_per_page, $start),
		'PAGE_NUMBER'			=> on_page($forum_topics, $topics_per_page, $start),
		'TOTAL'					=> $forum_topics)
	);

	$topic_rows = array();

	$sql = 'SELECT t.*
		FROM ' . TOPICS_TABLE . " t
		WHERE (t.forum_id = $forum_id OR t.forum_id = 0)
			" . (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1') . "
			AND t.topic_type IN (" . POST_ANNOUNCE . ", " . POST_GLOBAL . ")
			$limit_time_sql
		ORDER BY $sort_order_sql";
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		$topic_rows[] = $row;
	}
	$db->sql_freeresult($result);

	$sql = 'SELECT t.*
		FROM ' . TOPICS_TABLE . " t
		WHERE t.forum_id = $forum_id
			" . (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1') . '
			AND t.topic_type IN (' . POST_NORMAL . ', ' . POST_STICKY . ")
			$limit_time_sql
		ORDER BY t.topic_type DESC, $sort_order_sql";
	$result = $db->sql_query_limit($sql, $topics_per_page, $start);

	while ($row = $db->sql_fetchrow($result))
	{
		$topic_rows[] = $row;
	}
	$db->sql_freeresult($result);

	foreach ($topic_rows as $row)
	{
		$topic_title = '';

		if ($_CLASS['auth']->acl_get('m_approve', $row['forum_id']))
		{
			$row['topic_replies'] = $row['topic_replies_real'];
		}

		if ($row['topic_status'] == ITEM_LOCKED)
		{
			$folder_img = $_CLASS['user']->img('folder_locked', 'VIEW_TOPIC_LOCKED');
		}
		else
		{
			if ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL)
			{
				$folder_img = $_CLASS['user']->img('folder_announce', 'VIEW_TOPIC_ANNOUNCEMENT');
			}
			else if ($row['topic_type'] == POST_STICKY)
			{
				$folder_img = $_CLASS['user']->img('folder_sticky', 'VIEW_TOPIC_STICKY');
			}
			else if ($row['topic_status'] == ITEM_MOVED)
			{
				$folder_img = $_CLASS['user']->img('folder_moved', 'VIEW_TOPIC_MOVED');
			}
			else
			{
				$folder_img = $_CLASS['user']->img('folder', 'NO_NEW_POSTS');
			}
		}

		if ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL)
		{
			$topic_type = $_CLASS['user']->lang['VIEW_TOPIC_ANNOUNCEMENT'] . ' ';
		}
		else if ($row['topic_type'] == POST_STICKY)
		{
			$topic_type = $_CLASS['user']->lang['VIEW_TOPIC_STICKY'] . ' ';
		}
		else if ($row['topic_status'] == ITEM_MOVED)
		{
			$topic_type = $_CLASS['user']->lang['VIEW_TOPIC_MOVED'] . ' ';
		}
		else
		{
			$topic_type = '';
		}

		if (intval($row['poll_start']))
		{
			$topic_type .= $_CLASS['user']->lang['VIEW_TOPIC_POLL'] . ' ';
		}

		$topic_title = censor_text($row['topic_title']);
			
		$_CLASS['template']->assign_vars_array('topicrow', array(
			'U_VIEW_TOPIC'		=> getlink("Forums&amp;file=mcp&amp;f=$forum_id&amp;t={$row['topic_id']}&amp;mode=topic_view", false, false),

			'S_SELECT_TOPIC'	=> ($action == 'merge_select' && $row['topic_id'] != $topic_id) ? true : false,
			'U_SELECT_TOPIC'	=> getlink($url . '&amp;mode=topic_view&amp;action=merge&amp;to_topic_id=' . $row['topic_id'] . $selected_ids, false, false),
			'U_MCP_QUEUE'		=> getlink($url . '&amp;i=queue&amp;mode=approve_details&amp;t=' . $row['topic_id'], false, false),
			'U_MCP_REPORT'		=> getlink("Forums&amp;file=mcp&amp;i=main&amp;mode=topic_view&amp;t={$row['topic_id']}&amp;action=reports", false, false),

			'ATTACH_ICON_IMG'	=> ($_CLASS['auth']->acl_gets('f_download', 'u_download', $row['forum_id']) && $row['topic_attachment']) ? $_CLASS['user']->img('icon_attach', sprintf($_CLASS['user']->lang['TOTAL_ATTACHMENTS'], $row['topic_attachment'])) : '',
			'TOPIC_FOLDER_IMG'	=>	$folder_img,
			'TOPIC_TYPE'		=>	$topic_type,
			'TOPIC_TITLE'		=>	$topic_title,
			'REPLIES'			=>	$row['topic_replies'],
			'LAST_POST_TIME'	=>	$_CLASS['user']->format_date($row['topic_last_post_time']),
			'TOPIC_ID'			=>	$row['topic_id'],
			'S_TOPIC_CHECKED'	=>	($topic_id_list && in_array($row['topic_id'], $topic_id_list)) ? 'checked="checked" ' : '',

			'S_TOPIC_REPORTED'	=>	($row['topic_reported']) ? true : false,
			'S_TOPIC_UNAPPROVED'=>	($row['topic_approved']) ? false : true)
		);
	}
	unset($topic_rows);
}

function mcp_resync_topics($topic_ids)
{
	global $db, $phpEx, $_CLASS;

	if (!($forum_id = check_ids($topic_ids, TOPICS_TABLE, 'topic_id', 'm_')))
	{
		return;
	}

	if (!sizeof($topic_ids))
	{
		$_CLASS['template']->assign('MESSAGE', $_CLASS['user']->lang['NO_TOPIC_SELECTED']);
		return;
	}
	
	// Sync everything and perform extra checks separately
	sync('topic_reported', 'topic_id', $topic_ids, false, true);
	sync('topic_attachment', 'topic_id', $topic_ids, false, true);
	sync('topic', 'topic_id', $topic_ids, true, false);

	$sql = 'SELECT topic_id, forum_id, topic_title
		FROM ' . TOPICS_TABLE . '
		WHERE topic_id IN (' . implode(', ', $topic_ids) . ')';
	$result = $db->sql_query($sql);

	// Log this action
	while ($row = $db->sql_fetchrow($result))
	{
		add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_TOPIC_RESYNC', $row['topic_title']);
	}

	$msg = (sizeof($topic_ids) == 1) ? $_CLASS['user']->lang['TOPIC_RESYNC_SUCCESS'] : $_CLASS['user']->lang['TOPICS_RESYNC_SUCCESS'];
	$_CLASS['template']->assign('MESSAGE', $msg);

	return;
}

?>