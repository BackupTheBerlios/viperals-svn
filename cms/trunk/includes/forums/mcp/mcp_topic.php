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
// $Id: mcp_topic.php,v 1.4 2004/07/19 20:13:16 acydburn Exp $
//
// FILENAME  : mcp_topic.php
// STARTED   : Thu Jul 08, 2004
// COPYRIGHT : 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//
// TODO:
//

$_CLASS['core_user']->add_lang('viewtopic');

if (!$topic_id = get_variable('t', 'REQUEST', false, 'int'))
{
	trigger_error('TOPIC_NOT_EXIST');
}

$topic_info = get_topic_data(array($topic_id));

if (empty($topic_info[$topic_id]))
{
	trigger_error('TOPIC_NOT_EXIST');
}

$topic_info = $topic_info[$topic_id];

$url = 'Forums&amp;file=mcp&amp;t='.$topic_id;

$action = get_variable('action', 'REQUEST');
$icon_id = get_variable('icon', 'REQUEST', false, 'int');
$subject = get_variable('subject', 'POST');

$start = get_variable('start', 'REQUEST', false, 'int');

$to_topic_id = get_variable('to_topic_id', 'REQUEST', false, 'int');
$to_forum_id = get_variable('to_forum_id', 'REQUEST', false, 'int');

$post_id_list = array_unique(request_var('post_id_list', array(0)));
//echo $action;
// Split Topic?
if ($action === 'split_all' || $action === 'split_beyond')
{
	if (empty($post_id_list))
	{
		trigger_error('NO_POST_SELECTED');
	}

	if ($message = split_topic($action, $post_id_list, $topic_id, $to_forum_id, $subject))
	{
		$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->get_lang($message));
	}

	$action = 'split';
}

// Merge Posts?
if ($action == 'merge_posts')
{
	merge_posts($topic_id, $to_topic_id);

	$action = 'merge';
}

$topics_per_page = ($topic_info['forum_topics_per_page']) ? $topic_info['forum_topics_per_page'] : $config['topics_per_page'];

if ($action === 'split' && !$subject)
{
	$subject = $topic_info['topic_title'];
}

$where_sql = ($action === 'reports') ? 'WHERE post_reported = 1 AND ' : 'WHERE';
mcp_sorting('viewtopic', $sort_days, $sort_key, $sort_dir, $sort_by_sql, $sort_order_sql, $total, $topic_info['forum_id'], $topic_id, $where_sql);

$forum_topics = ($total == -1) ? $topic_info['forum_topics'] : $total;
$limit_time_sql = ($sort_days) ? 'AND t.topic_last_post_time >= ' . ($_CLASS['core_user']->time - ($sort_days * 86400)) : '';

if ($total == -1)
{
	$total = $topic_info['topic_replies'] + 1;
}

$posts_per_page = max(0, get_variable('posts_per_page', 'POST', intval($config['posts_per_page'])));

$sql = 'SELECT u.username, u.user_colour, p.*
	FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
	WHERE ' . (($action == 'reports') ? 'p.post_reported = 1 AND ' : '') . "
		p.topic_id = {$topic_id}
		AND p.poster_id = u.user_id
	ORDER BY $sort_order_sql";
$result = $_CLASS['core_db']->query_limit($sql, $posts_per_page, $start);

$rowset = array();
$bbcode_bitfield = 0;

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$rowset[] = $row;
	$bbcode_bitfield |= $row['bbcode_bitfield'];
}

if ($bbcode_bitfield)
{
	require_once(SITE_FILE_ROOT.'includes/forums/bbcode.php');
	$bbcode = new bbcode($bbcode_bitfield);
}

foreach ($rowset as $i => $row)
{
	$has_unapproved_posts = false;
	$poster = ($row['poster_id'] != ANONYMOUS) ? $row['username'] : ((!$row['post_username']) ? $_CLASS['core_user']->lang['GUEST'] : $row['post_username']);
	$poster = ($row['user_colour']) ? '<span style="color:#' . $row['user_colour'] . '">' . $poster . '</span>' : $poster;

	$message = $row['post_text'];
	$post_subject = ($row['post_subject'] != '') ? $row['post_subject'] : $topic_info['topic_title'];

	// If the board has HTML off but the post has HTML
	// on then we process it, else leave it alone
	if (!$config['allow_html'] && $row['enable_html'])
	{
		$message = preg_replace('#(<)([\/]?.*?)(>)#is', '&lt;\\2&gt;', $message);
	}

	if ($row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
	}

	$message = smiley_text($message);
	$message = str_replace("\n", '<br />', $message);

	$checked = ($post_id_list && in_array(intval($row['post_id']), $post_id_list)) ? 'checked="checked" ' : '';
	$s_checkbox = ($row['post_id'] == $topic_info['topic_first_post_id'] && $action == 'split') ? '&nbsp;' : '<input type="checkbox" name="post_id_list[]" value="' . $row['post_id'] . '" ' . $checked . '/>';

	if (!$row['post_approved'])
	{
		$has_unapproved_posts = true;
	}

	$_CLASS['core_template']->assign_vars_array('postrow', array(
		'POSTER_NAME'	=> $poster,
		'POST_DATE'		=> $_CLASS['core_user']->format_date($row['post_time']),
		'POST_SUBJECT'	=> $post_subject,
		'MESSAGE'		=> $message,
		'POST_ID'		=> $row['post_id'],

		'MINI_POST_IMG' => ($row['post_time'] > $_CLASS['core_user']->data['user_last_visit'] && $_CLASS['core_user']->is_user) ? $_CLASS['core_user']->img('icon_post_new', $_CLASS['core_user']->lang['NEW_POST']) : $_CLASS['core_user']->img('icon_post', $_CLASS['core_user']->lang['POST']),
		
		'S_CHECKBOX'		=> $s_checkbox,
		'S_POST_REPORTED'	=> ($row['post_reported']) ? true : false,
		'S_POST_UNAPPROVED'	=> ($row['post_approved']) ? false : true,
					
		'U_POST_DETAILS'	=> generate_link("$url&amp;p={$row['post_id']}&amp;mode=post_details"),
		'U_MCP_APPROVE'		=> generate_link('Forums&amp;file=mcp&amp;i=queue&amp;mode=approve&amp;post_id_list[]=' . $row['post_id']))
	);

	unset($rowset[$i]);
}

// Display topic icons for split topic
$s_topic_icons = false;

if ($_CLASS['auth']->acl_get('m_split', $topic_info['forum_id']))
{
	require_once(SITE_FILE_ROOT.'includes/forums/functions_posting.php');
	$s_topic_icons = posting_gen_topic_icons('', $icon_id);

	// Has the user selected a topic for merge?
	if ($to_topic_id)
	{
		$to_topic_info = get_topic_data(array($to_topic_id), 'm_merge');
		
		if (!sizeof($to_topic_info))
		{
			$to_topic_id = 0;
		}
		else
		{
			$to_topic_info = $to_topic_info[$to_topic_id];
		}
		

		if (!$to_topic_info['enable_icons'])
		{
			$s_topic_icons = false;
		}
	}
}

$_CLASS['core_template']->assign_array(array(
	'TOPIC_TITLE'		=> $topic_info['topic_title'],
	'U_VIEWTOPIC'		=> generate_link('Forums&amp;file=viewtopic&amp;f=' . $topic_info['forum_id'] . '&amp;t=' . $topic_info['topic_id']),

	'TO_TOPIC_ID'		=> $to_topic_id,
	'TO_TOPIC_INFO'		=> ($to_topic_id) ? sprintf($_CLASS['core_user']->lang['YOU_SELECTED_TOPIC'], $to_topic_id, '<a href="'.generate_link('Forums&amp;file=viewtopic&amp;f=' . $to_topic_info['forum_id'] . '&amp;t=' . $to_topic_id) . '" target="_new">' . $to_topic_info['topic_title'] . '</a>') : '',

	'SPLIT_SUBJECT'		=> $subject,
	'POSTS_PER_PAGE'	=> $posts_per_page,
	'MODE'				=> $mode,

	'REPORTED_IMG'		=> $_CLASS['core_user']->img('icon_reported', 'POST_REPORTED', false, true),
	'UNAPPROVED_IMG'	=> $_CLASS['core_user']->img('icon_unapproved', 'POST_UNAPPROVED', false, true),

	'S_MCP_ACTION'		=> generate_link("$url&amp;mode=$mode".(($start) ? '&amp;start='.$start : '')),
	'S_FORUM_SELECT'	=> '<select name="to_forum_id">' . (($to_forum_id) ? make_forum_select($to_forum_id) : make_forum_select($topic_info['forum_id'])) . '</select>',
	'S_CAN_SPLIT'		=> ($_CLASS['auth']->acl_get('m_split', $topic_info['forum_id']) && $action != 'merge') ? true : false,
	'S_CAN_MERGE'		=> ($_CLASS['auth']->acl_get('m_merge', $topic_info['forum_id']) && $action != 'split') ? true : false,
	'S_CAN_DELETE'		=> ($_CLASS['auth']->acl_get('m_delete', $topic_info['forum_id'])) ? true : false,
	'S_CAN_APPROVE'		=> ($has_unapproved_posts && $_CLASS['auth']->acl_get('m_approve', $topic_info['forum_id'])) ? true : false,
	'S_CAN_LOCK'		=> ($_CLASS['auth']->acl_get('m_lock', $topic_info['forum_id'])) ? true : false,
	'S_REPORT_VIEW'		=> ($action == 'reports') ? true : false,

	'S_SHOW_TOPIC_ICONS'=> $s_topic_icons,
	'S_TOPIC_ICON'		=> $icon_id,

	'RETURN_TOPIC'		=> sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;f={$topic_info['forum_id']}&amp;t={$topic_info['topic_id']}&amp;start=$start.").'">', '</a>'),
	'RETURN_FORUM'		=> sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link("Forums&amp;file=viewforum&amp;f={$topic_info['forum_id']}&amp;start=$start").'">', '</a>'),

	'PAGE_NUMBER'		=> on_page($total, $posts_per_page, $start),
	'PAGINATION'		=> (!$posts_per_page) ? '' : generate_pagination('Forums&amp;file=mcp&amp;t=' . $topic_info['topic_id'] . "&amp;mode=$mode&amp;action=$action&amp;to_topic_id=$to_topic_id&amp;posts_per_page=$posts_per_page&amp;st=$sort_days&amp;sk=$sort_key&amp;sd=$sort_dir", $total, $posts_per_page, $start),
	'TOTAL'				=> $total)
);

page_header();
make_jumpbox($url . '&amp;mode=forum_view', $topic_info['forum_id'], false, 'm_');

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP'), 'modules/Forums/mcp_topic.html');

function split_topic($mode, $post_id_list, $topic_id, $to_forum_id, $subject)
{
	global $_CLASS ;

	$start	= request_var('start', 0);
		
	if (empty($post_id_list) || !check_ids($post_id_list, FORUMS_POSTS_TABLE, 'post_id', 'm_split'))
	{
		return false;
	}

	//$post_id = $post_id_list[0];

	$post_info = get_post_data($post_id_list);

	if (empty($post_info))
	{
		return 'NO_POST_SELECTED';
	}

	$subject = trim($subject);

	if (!$subject)
	{
		return 'EMPTY_SUBJECT';
	}

	if ($to_forum_id <= 0)
	{
		return 'NO_DESTINATION_FORUM';
	}

	$forum_info = get_forum_data(array($to_forum_id), 'm_split');

	if (empty($forum_info))
	{
		return 'NOT_MODERATOR_DESTINATION';
	}

	$forum_info = $forum_info[$to_forum_id];

	if ($forum_info['forum_type'] != FORUM_POST)
	{
		return 'DESTINATION_FORUM_NOT_POSTABLE';
	}

	$redirect = request_var('redirect', $_CLASS['core_user']->data['session_page']);

	$s_hidden_fields = build_hidden_fields(array(
		'post_id_list'	=> $post_id_list,
		'f'				=> $forum_id,
		'mode'			=> 'topic_view',
		'start'			=> $start,
		'action'		=> $mode,
		't'				=> $topic_id,
		'redirect'		=> $redirect,
		'subject'		=> $subject,
		'to_forum_id'	=> $to_forum_id,
		'icon'			=> request_var('icon', 0))
	);
	$success_msg = $return_link = '';

	if (confirm_box(true))
	{
		//$post_info = $post_info[$post_id];

		if ($mode == 'split_beyond')
		{
			mcp_sorting('viewtopic', $sort_days, $sort_key, $sort_dir, $sort_by_sql, $sort_order_sql, $total, $forum_id, $topic_id);
			$limit_time_sql = ($sort_days) ? 'AND t.topic_last_post_time >= ' . (time() - ($sort_days * 86400)) : '';

			if ($sort_order_sql{0} == 'u')
			{
				$sql = 'SELECT p.post_id, p.forum_id, p.post_approved
					FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . " u
					WHERE p.topic_id = $topic_id
						AND p.poster_id = u.user_id
						$limit_time_sql
					ORDER BY $sort_order_sql";
			}
			else
			{
				$sql = 'SELECT p.post_id, p.forum_id, p.post_approved
					FROM ' . POSTS_TABLE . " p
					WHERE p.topic_id = $topic_id
						$limit_time_sql
					ORDER BY $sort_order_sql";
			}
			$result = $_CLASS['core_db']->query_limit($sql, 0, $start);

			$store = false;
			$post_id_list = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				// If splitted from selected post (split_beyond), we split the unapproved items too.
				if (!$row['post_approved'] && !$_CLASS['auth']->acl_get('m_approve', $row['forum_id']))
				{
//					continue;
				}

				// Start to store post_ids as soon as we see the first post that was selected
				if ($row['post_id'] == $post_id)
				{
					$store = true;
				}

				if ($store)
				{
					$post_id_list[] = $row['post_id'];
				}
			}
		}

		if (!sizeof($post_id_list))
		{
			trigger_error($_CLASS['core_user']->lang['NO_POST_SELECTED']);
		}

		$icon_id = request_var('icon', 0);

		$sql_ary = array(
			'forum_id'		=> $to_forum_id,
			'topic_title'	=> $subject,
			'icon_id'		=> $icon_id,
			'topic_approved'=> 1
		);
	
		$sql = 'INSERT INTO ' . TOPICS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary);
		$_CLASS['core_db']->sql_query($sql);

		$to_topic_id = $_CLASS['core_db']->sql_nextid();
		move_posts($post_id_list, $to_topic_id);

		// Change topic title of first post
		$sql = 'UPDATE ' . POSTS_TABLE . " 
			SET post_subject = '" . $_CLASS['core_db']->sql_escape($subject) . "'
			WHERE post_id = {$post_id_list[0]}";
		$_CLASS['core_db']->sql_query($sql);
		
		$success_msg = 'TOPIC_SPLIT_SUCCESS';

		// Link back to both topics
		$return_link = sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link('Forums&amp;file=viewtopic&amp;f=' . $post_info['forum_id'] . '&amp;t=' . $post_info['topic_id']) . '">', '</a>') . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_NEW_TOPIC'], '<a href="'.generate_link('Forums&amp;file=viewtopic&amp;f=' . $to_forum_id . '&amp;t=' . $to_topic_id) . '">', '</a>');
	}
	else
	{
		confirm_box(false, ($mode == 'split_all') ? 'SPLIT_TOPIC_ALL' : 'SPLIT_TOPIC_BEYOND', $s_hidden_fields);
	}

	$redirect = request_var('redirect', generate_link('Forums'));

	/*if (strpos($redirect, '?') === false)
	{
		$redirect = substr_replace($redirect, ".$phpEx$SID&", strpos($redirect, '&'), 1);
	}*/

	if (!$success_msg)
	{
		return;
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, generate_link("Forums&amp;file=viewtopic&amp;f=$to_forum_id&amp;t=$to_topic_id"));
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . $return_link);
	}
}

// Merge selected posts into selected topic
function merge_posts($topic_id, $to_topic_id)
{
	global $_CLASS;

	if (!$to_topic_id)
	{
		$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->lang['NO_FINAL_TOPIC_SELECTED']);
		return;
	}

	$topic_data = get_topic_data(array($to_topic_id), 'm_merge');

	if (!sizeof($topic_data))
	{
		$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->lang['NO_FINAL_TOPIC_SELECTED']);
		return;
	}

	$topic_data = $topic_data[$to_topic_id];

	$post_id_list	= request_var('post_id_list', array(0));
	$start			= request_var('start', 0);
		
	if (!sizeof($post_id_list))
	{
		$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->lang['NO_POST_SELECTED']);
		return;
	}
	
	if (!($forum_id = check_ids($post_id_list, POSTS_TABLE, 'post_id', 'm_merge')))
	{
		return;
	}

	$redirect = request_var('redirect', $_CLASS['core_user']->data['session_page']);

	$s_hidden_fields = build_hidden_fields(array(
		'post_id_list'	=> $post_id_list,
		'to_topic_id'	=> $to_topic_id,
		'mode'			=> 'topic_view',
		'action'		=> 'merge_posts',
		'start'			=> $start,
		'redirect'		=> $redirect,
		'f'				=> $forum_id,
		't'				=> $topic_id)
	);
	$success_msg = $return_link = '';

	if (confirm_box(true))
	{
		$to_forum_id = $topic_data['forum_id'];

		move_posts($post_id_list, $to_topic_id);
		add_log('mod', $to_forum_id, $to_topic_id, 'LOG_MERGE', $topic_data['topic_title']);
				
		// Message and return links
		$success_msg = 'POSTS_MERGED_SUCCESS';

		// Does the original topic still exist? If yes, link back to it
		$topic_data = get_topic_data(array($topic_id));

		if (sizeof($topic_data))
		{
			$return_link .= sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link('Forums&amp;file=viewtopic&amp;f=' . $forum_id . '&amp;t=' . $topic_id) . '">', '</a>');
		}

		// Link to the new topic
		$return_link .= (($return_link) ? '<br /><br />' : '') . sprintf($_CLASS['core_user']->lang['RETURN_NEW_TOPIC'], '<a href="'.generate_link('Forums&amp;file=viewtopic&amp;f=' . $to_forum_id . '&amp;t=' . $to_topic_id) . '">', '</a>');
	}
	else
	{
		confirm_box(false, 'MERGE_POSTS', $s_hidden_fields);
	}

	$redirect = request_var('redirect', generate_link('Forums'));

	/*if (strpos($redirect, '?') === false)
	{
		$redirect = substr_replace($redirect, ".$phpEx$SID&", strpos($redirect, '&'), 1);
	}*/

	if (!$success_msg)
	{
		return;
	}
	else
	{
		$_CLASS['core_display']->meta_refresh(3, generate_link("Forums&amp;file=viewtopic&amp;f=$to_forum_id&amp;t=$to_topic_id"));
		trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . $return_link);
	}
}

?>