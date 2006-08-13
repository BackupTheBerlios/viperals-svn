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
// $Id: mcp_forum.php,v 1.3 2004/07/19 20:13:16 acydburn Exp $
//
// FILENAME  : mcp_forum.php
// STARTED   : Thu Jul 08, 2004
// COPYRIGHT : 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

$forum_id = get_variable('f', 'REQUEST', false, 'int');
$action = get_variable('action', 'REQUEST');

if (!$forum_id || !$_CLASS['forums_auth']->acl_get('m_', $forum_id))
{
	trigger_error('MODULE_NOT_EXIST');
}
	
$_CLASS['core_user']->add_lang('viewforum');
$forum_info = get_forum_data($forum_id, 'm_');

if (empty($forum_info[$forum_id]))
{
	exit;
}

$forum_info = $forum_info[$forum_id];

$url = 'forums&amp;file=mcp&amp;f='.$forum_id;
$action = get_variable('action', 'REQUEST');
		
if ($action === 'merge_select')
{
	// Fixes a "bug" that makes forum_view use the same ordering as topic_view
	unset($_POST['sk'], $_POST['sd'], $_REQUEST['sk'], $_REQUEST['sd']);
}

$start = get_variable('start', 'REQUEST', false, 'int');
$topic_id_list = array_unique(get_variable('topic_id_list', 'REQUEST',  array(), 'array:int'));
$post_id_list = array_unique(get_variable('post_id_list', 'REQUEST',  array(), 'array:int'));
$topic_id = get_variable('t', 'REQUEST', false, 'int');;

// Resync Topics
if ($action === 'resync')
{
	if (empty($topic_id_list))
	{
		$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->lang['NO_TOPIC_SELECTED']);
	}
	else
	{
		mcp_resync_topics($topic_id_list);
	}
}

$selected_ids = '';

if (!empty($post_id_list))
{
	foreach ($post_id_list as $num => $post_id)
	{
		$selected_ids .= '&amp;post_id_list[' . $num . ']=' . $post_id;
	}
}

make_jumpbox(generate_link($url . "&amp;action=$action&amp;mode=$mode", $forum_id . (($action == 'merge_select') ? $selected_ids : '')), false, 'm_');

$topics_per_page = ($forum_info['forum_topics_per_page']) ? $forum_info['forum_topics_per_page'] : $config['topics_per_page'];

mcp_sorting('viewforum', $sort_days, $sort_key, $sort_dir, $sort_by_sql, $sort_order_sql, $total, $forum_id);

$forum_topics = ($total == -1) ? $forum_info['forum_topics'] : $total;
$limit_time_sql = ($sort_days) ? 'AND t.topic_last_post_time >= ' . ($_CLASS['core_user']->time - ($sort_days * 86400)) : '';

$pagination = generate_pagination($url . "&amp;action=$action&amp;mode=$mode" . (($action == 'merge_select') ? $selected_ids : ''), $forum_topics, $topics_per_page, $start);

$_CLASS['core_template']->assign_array(array(
	'S_TOPIC_ICONS'			=> false,
	'FORUM_NAME'			=> $forum_info['forum_name'],

	'REPORTED_IMG'			=> $_CLASS['core_user']->img('icon_reported', 'TOPIC_REPORTED'),
	'UNAPPROVED_IMG'		=> $_CLASS['core_user']->img('icon_unapproved', 'TOPIC_UNAPPROVED'),

	'S_CAN_DELETE'			=> $_CLASS['forums_auth']->acl_get('m_delete', $forum_id),
	'S_CAN_MOVE'			=> $_CLASS['forums_auth']->acl_get('m_move', $forum_id),
	'S_CAN_FORK'			=> $_CLASS['forums_auth']->acl_get('m_', $forum_id),
	'S_CAN_LOCK'			=> $_CLASS['forums_auth']->acl_get('m_lock', $forum_id),
	'S_CAN_SYNC'			=> $_CLASS['forums_auth']->acl_get('m_', $forum_id),
	'S_CAN_APPROVE'			=> $_CLASS['forums_auth']->acl_get('m_approve', $forum_id),

	'U_VIEW_FORUM'			=> generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id),
	'U_VIEW_FORUM_LOGS'		=> ($_CLASS['forums_auth']->acl_gets('a_', 'm_', $forum_id)) ? generate_link('forums&amp;file=mcp&amp;i=logs&amp;mode=forum_logs&amp;f=' . $forum_id) : '',

	'S_MCP_ACTION'			=> generate_link($url . "&amp;mode=$mode&amp;start=$start" . (($action == 'merge_select') ? $selected_ids : '')),

	'PAGINATION'			=> $pagination['formated'],
	'PAGINATION_ARRAY'		=> $pagination['array'],
	'PAGE_NUMBER'			=> on_page($forum_topics, $topics_per_page, $start),
	'TOTAL'					=> $forum_topics
));

$icons = obtain_icons();

$topic_rows = array();

$sql = 'SELECT t.*
	FROM ' . FORUMS_TOPICS_TABLE . " t
	WHERE (t.forum_id = $forum_id OR t.forum_id = 0)
		" . (($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1') . "
		AND t.topic_type IN (" . POST_ANNOUNCE . ", " . POST_GLOBAL . ")
		$limit_time_sql
	ORDER BY $sort_order_sql";
$result = $_CLASS['core_db']->query($sql);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$topic_rows[] = $row;
}
$_CLASS['core_db']->free_result($result);

$sql = 'SELECT t.*
	FROM ' . FORUMS_TOPICS_TABLE . " t
	WHERE t.forum_id = $forum_id
		" . (($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1') . '
		AND t.topic_type IN (' . POST_NORMAL . ', ' . POST_STICKY . ")
		$limit_time_sql
	ORDER BY t.topic_type DESC, $sort_order_sql";
$result = $_CLASS['core_db']->query_limit($sql, $topics_per_page, $start);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$topic_rows[] = $row;
}
$_CLASS['core_db']->free_result($result);

foreach ($topic_rows as $row)
{
	$topic_title = '';

	if ($_CLASS['forums_auth']->acl_get('m_approve', $row['forum_id']))
	{
		$row['topic_replies'] = $row['topic_replies_real'];
	}

	if ($row['topic_status'] == ITEM_LOCKED)
	{
		$folder_img = 'folder_locked';
		$folder_alt = 'VIEW_TOPIC_LOCKED';
	}
	else
	{
		if ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL)
		{
			$folder_img = 'folder_announce';
			$folder_alt = 'VIEW_TOPIC_ANNOUNCEMENT';
		}
		else if ($row['topic_type'] == POST_STICKY)
		{
			$folder_img = 'folder_sticky';
			$folder_alt = 'VIEW_TOPIC_STICKY';
		}
		else if ($row['topic_status'] == ITEM_MOVED)
		{
			$folder_img = 'folder_moved';
			$folder_alt = 'VIEW_TOPIC_MOVED';
		}
		else
		{
			$folder_img = 'folder';
			$folder_alt = 'NO_NEW_POSTS';
		}
	}

	if ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL)
	{
		$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_ANNOUNCEMENT'] . ' ';
	}
	else if ($row['topic_type'] == POST_STICKY)
	{
		$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_STICKY'] . ' ';
	}
	else if ($row['topic_status'] == ITEM_MOVED)
	{
		$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_MOVED'] . ' ';
	}
	else
	{
		$topic_type = '';
	}

	if (intval($row['poll_start']))
	{
		$topic_type .= $_CLASS['core_user']->lang['VIEW_TOPIC_POLL'] . ' ';
	}

	$topic_title = censor_text($row['topic_title']);

	$topic_unapproved = (!$row['topic_approved'] && $_CLASS['forums_auth']->acl_gets('m_approve', $row['forum_id']));
	$posts_unapproved = ($row['topic_approved'] && $row['topic_replies'] < $row['topic_replies_real'] && $_CLASS['forums_auth']->acl_gets('m_approve', $row['forum_id']));
	$u_mcp_queue = ($topic_unapproved || $posts_unapproved) ? generate_link($url . '&amp;i=queue&amp;mode='.(($topic_unapproved) ? 'approve_details' : 'unapproved_posts') .'&amp;t=' . $row['topic_id'], false, false) : false;

	$_CLASS['core_template']->assign_vars_array('topicrow', array(
		'U_VIEW_TOPIC'		=> generate_link("Forums&amp;file=mcp&amp;t={$row['topic_id']}&amp;mode=topic_view"),

		'S_SELECT_TOPIC'	=> ($action === 'merge_select' && $row['topic_id'] != $topic_id) ? true : false,
		'U_SELECT_TOPIC'	=> generate_link($url . '&amp;mode=topic_view&amp;action=merge&amp;to_topic_id=' . $row['topic_id'] . $selected_ids),
		'U_MCP_QUEUE'		=> $u_mcp_queue,
		'U_MCP_REPORT'		=> generate_link("forums&amp;file=mcp&amp;i=main&amp;mode=topic_view&amp;t={$row['topic_id']}&amp;action=reports"),

		'ATTACH_ICON_IMG'	=> ($_CLASS['forums_auth']->acl_gets('f_download', 'u_download', $row['forum_id']) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', $_CLASS['core_user']->lang['TOTAL_ATTACHMENTS']) : '',
		'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
		//'TOPIC_FOLDER_IMG_SRC'	=> $user->img($folder_img, $folder_alt, false, '', 'src'),

		'TOPIC_ICON_IMG'		=> empty($icons[$row['icon_id']]) ? '' : $icons[$row['icon_id']]['img'],
		'TOPIC_ICON_IMG_WIDTH'	=> empty($icons[$row['icon_id']]) ? '' : $icons[$row['icon_id']]['width'],
		'TOPIC_ICON_IMG_HEIGHT' => empty($icons[$row['icon_id']]) ? '' : $icons[$row['icon_id']]['height'],
		'UNAPPROVED_IMG'		=> ($topic_unapproved || $posts_unapproved) ? $_CLASS['core_user']->img('icon_unapproved', ($topic_unapproved) ? 'TOPIC_UNAPPROVED' : 'POSTS_UNAPPROVED') : '',

		'TOPIC_TYPE'		=> $topic_type,
		'TOPIC_TITLE'		=> $topic_title,
		'REPLIES'			=> ($_CLASS['forums_auth']->acl_get('m_approve', $row['forum_id'])) ? $row['topic_replies_real'] : $row['topic_replies'],
		'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
		'TOPIC_ID'			=> $row['topic_id'],
		'S_TOPIC_CHECKED'	=> ($topic_id_list && in_array($row['topic_id'], $topic_id_list)) ? 'checked="checked" ' : '',

		'S_TOPIC_REPORTED'	=> (!empty($row['topic_reported']) && $_CLASS['forums_auth']->acl_gets('m_report', $row['forum_id'])),
		'S_TOPIC_UNAPPROVED'=> $topic_unapproved,
		'S_POSTS_UNAPPROVED'=> $posts_unapproved,
		'NEWEST_POST_IMG' => false
	));
}
unset($topic_rows);

page_header();
$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP'), 'modules/forums/mcp_forum.html');

function mcp_resync_topics($topic_ids)
{
	global $_CLASS;

	if (!check_ids($topic_ids, FORUMS_TOPICS_TABLE, 'topic_id', 'm_'))
	{
		return;
	}

	if (empty($topic_ids))
	{
		$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->lang['NO_TOPIC_SELECTED']);

		return;
	}

	// Sync everything and perform extra checks separately
	sync('topic_reported', 'topic_id', $topic_ids, false, true);
	sync('topic_attachment', 'topic_id', $topic_ids, false, true);
	sync('topic', 'topic_id', $topic_ids, true, false);

	$sql = 'SELECT topic_id, forum_id, topic_title
		FROM ' . FORUMS_TOPICS_TABLE . '
		WHERE topic_id IN (' . implode(', ', $topic_ids) . ')';
	$result = $_CLASS['core_db']->query($sql);

	// Log this action
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_TOPIC_RESYNC', $row['topic_title']);
	}
	$_CLASS['core_db']->free_result($result);

	$msg = (count($topic_ids) == 1) ? $_CLASS['core_user']->lang['TOPIC_RESYNC_SUCCESS'] : $_CLASS['core_user']->lang['TOPICS_RESYNC_SUCCESS'];

	$_CLASS['core_template']->assign('MESSAGE', $msg);
}

?>