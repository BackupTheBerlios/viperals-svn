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
// $Id: mcp_post.php,v 1.4 2004/07/19 20:13:16 acydburn Exp $
//
// FILENAME  : mcp_post.php
// STARTED   : Thu Jul 08, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------


$_CLASS['core_user']->add_lang('posting');

$post_id = get_variable('p', 'REQUEST', false, 'int');
$action = get_variable('action', 'REQUEST');

if (!$post_id && $action !== 'whois')
{
	trigger_error('POST_NOT_EXIST');
}

if ($action !== 'whois')
{
	// Get post data
	$post_info = get_post_data(array($post_id));
	
	if (empty($post_info[$post_id]))
	{
		trigger_error('POST_NOT_EXIST');
	}
	$post_info = $post_info[$post_id];
	
	$url = 'forums&amp;file=mcp';
	$start	= get_variable('start', 'REQUEST', 0, 'int');
}

switch ($action)
{
	case 'whois':
		$ip = get_variable('ip', 'REQUEST');
		require_once SITE_FILE_ROOT.'includes/functions_user.php';

		$whois = user_ipwhois($ip);

		$whois = preg_replace('#(\s)([\w\-\._\+]+@[\w\-\.]+)(\s)#', '\1<a href="mailto:\2">\2</a>\3', $whois);
		$whois = preg_replace('#(\s)(http:/{2}[^\s]*)(\s)#', '\1<a href="\2" target="_blank">\2</a>\3', $whois);
		
		$_CLASS['core_template']->assign_array(array(
			'RETURN_POST'	=> sprintf($_CLASS['core_user']->get_lang('RETURN_POST'), '<a href="' . generate_link("forums&amp;file=mcp&amp;mode=$mode&amp;p=$post_id") . '">', '</a>'),
			'WHOIS'			=> trim($whois)
		));

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP'), 'modules/forums/mcp_whois.html');
	break;

	case 'chgposter':
	case 'chgposter_ip':
		if ($action == 'chgposter')
		{
			$username = get_variable('username', 'REQUEST', '');
			$sql_where = ($username) ?  "username = '" . $_CLASS['core_db']->escape($username) . "'" : '';
		}
		else
		{
			$new_user_id = get_variable('u', 'REQUEST', 0, 'int');
			$sql_where = ($new_user_id) ? 'user_id = ' . $new_user_id : '';
		}

		if ($sql_where)
		{
			$sql = 'SELECT *
				FROM ' . CORE_USERS_TABLE . '
				WHERE ' . $sql_where;
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);
		}

		if (!$sql_where || !$row)
		{
			trigger_error('NO_USER');
		}

		if ($_CLASS['forums_auth']->acl_get('m_chgposter', $post_info['forum_id']))
		{
			change_poster($post_info, $row);
		}
	break;
}

// Set some vars
$users_ary = array();
$poster = ($post_info['user_colour']) ? '<span style="color:#' . $post_info['user_colour'] . '">' . $post_info['username'] . '</span>' : $post_info['username'];

// Process message, leave it uncensored
$message = $post_info['post_text'];

if ($post_info['bbcode_bitfield'])
{
	require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';

	$bbcode = new bbcode($post_info['bbcode_bitfield']);
	$bbcode->bbcode_second_pass($message, $post_info['bbcode_uid'], $post_info['bbcode_bitfield']);
}

$message = smiley_text($message);
$message = str_replace("\n", '<br />', $message);

$_CLASS['core_template']->assign_array(array(
	'U_MCP_ACTION'			=> generate_link($url.'&amp;i=main&amp;quickmod=1'), // Use this for mode paramaters
	'U_POST_ACTION'			=> generate_link("$url&amp;mode=post_details"), // Use this for action parameters
	'U_APPROVE_ACTION'		=> generate_link('forums&amp;file=mcp&amp;i=queue&amp;p='.$post_info['post_id']),

	'S_CAN_VIEWIP'			=> $_CLASS['forums_auth']->acl_get('m_ip', $post_info['forum_id']),
	'S_CAN_CHGPOSTER'		=> $_CLASS['forums_auth']->acl_get('m_', $post_info['forum_id']),
	'S_CAN_LOCK_POST'		=> $_CLASS['forums_auth']->acl_get('m_lock', $post_info['forum_id']),
	'S_CAN_DELETE_POST'		=> $_CLASS['forums_auth']->acl_get('m_delete', $post_info['forum_id']),

	'S_POST_REPORTED'		=> ($post_info['post_reported']),
	'S_POST_UNAPPROVED'		=> (!$post_info['post_approved']),
	'S_POST_LOCKED'			=> ($post_info['post_edit_locked']),
	'S_USER_NOTES'			=> true,
	'S_CLEAR_ALLOWED'		=> ($_CLASS['forums_auth']->acl_get('a_clearlogs')),

	'U_EDIT'				=> ($_CLASS['forums_auth']->acl_get('m_edit', $post_info['forum_id'])) ? generate_link("forums&amp;file=posting&amp;mode=edit&amp;p={$post_info['post_id']}") : '',
	'U_FIND_MEMBER'			=> generate_link('members_list&amp;mode=search_user&amp;form=mcp_chgposter&amp;field=username'),
	'U_MCP_APPROVE'			=> generate_link('forums&amp;file=mcp&amp;i=queue&amp;mode=approve_details&amp;p=' . $post_info['post_id']),
	'U_MCP_REPORT'			=> generate_link('forums&amp;file=mcp&amp;i=reports&amp;mode=report_details&amp;p=' . $post_info['post_id']),
	'U_MCP_USER_NOTES'		=> generate_link('forums&amp;file=mcp&amp;i=notes&amp;mode=user_notes&amp;u=' . $post_info['user_id']),
	'U_MCP_WARN_USER'		=> $_CLASS['forums_auth']->acl_get('m_warn') ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=warn&amp;mode=warn_user&amp;u=' . $post_info['user_id']) : '',
	'U_VIEW_POST'			=> generate_link('forums&amp;file=viewtopic&amp;p=' . $post_info['post_id'] . '#p' . $post_info['post_id']),
	'U_VIEW_PROFILE'		=> generate_link('members_list&amp;mode=viewprofile&amp;u=' . $post_info['user_id']),
	'U_VIEW_TOPIC'			=> generate_link('forums&amp;file=viewtopic&amp;t=' . $post_info['topic_id']),

	'RETURN_TOPIC'			=> sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("forums&amp;file=viewtopic&amp;p={$post_info['post_id']}#{$post_info['post_id']}").'">', '</a>'),
	'RETURN_FORUM'			=> sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link("forums&amp;file=viewforum&amp;f={$post_info['forum_id']}&amp;start={$start}").'">', '</a>'),

	'REPORTED_IMG'			=> $_CLASS['core_user']->img('icon_reported', $_CLASS['core_user']->lang['POST_REPORTED']),
	'UNAPPROVED_IMG'		=> $_CLASS['core_user']->img('icon_unapproved', $_CLASS['core_user']->lang['POST_UNAPPROVED']),
	'EDIT_IMG'				=> $_CLASS['core_user']->img('btn_edit', $_CLASS['core_user']->lang['EDIT_POST']),
	'SEARCH_IMG'			=> $_CLASS['core_user']->img('btn_search', 'SEARCH_USER_POSTS'),
	
	'POSTER_NAME'			=> $poster,
	'POST_PREVIEW'			=> $message,
	'POST_SUBJECT'			=> $post_info['post_subject'],
	'POST_DATE'				=> $_CLASS['core_user']->format_date($post_info['post_time']),
	'POST_IP'				=> $post_info['poster_ip'],
	'POST_IPADDR'			=> @gethostbyaddr($post_info['poster_ip']),
	'POST_ID'				=> $post_info['post_id']
));

// Get User Notes
$log_data = array();
$log_count = 0;

view_log('user', $log_data, $log_count, $config['posts_per_page'], 0, 0, 0, $post_info['user_id']);

if ($log_count)
{
	$_CLASS['core_template']->assign('S_USER_NOTES', true);

	foreach ($log_data as $row)
	{
		$_CLASS['core_template']->assign_vars_array('usernotes', array(
			'REPORT_BY'		=> $row['username'],
			'REPORT_AT'		=> $_CLASS['core_user']->format_date($row['time']),
			'ACTION'		=> $row['action'],
			'ID'			=> $row['id']
		));
	}
}

// Get Reports
/*
if ($_CLASS['forums_auth']->acl_get('m_', $post_info['forum_id']))
{
	$sql = 'SELECT r.*, re.*, u.user_id, u.username 
		FROM ' . FORUMS_REPORTS_TABLE . ' r, ' . CORE_USERS_TABLE . ' u, ' . DORUMS_REPORTS_REASONS_TABLE . " re
		WHERE r.post_id = $post_id
			AND r.reason_id = re.reason_id
			AND u.user_id = r.user_id
		ORDER BY r.report_time DESC";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_template']->assign('S_SHOW_REPORTS', true);

		do
		{
			if (isset($_CLASS['core_user']->lang['report_reasons']['TITLE'][strtoupper($row['reason_title'])]) && isset($_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_title'])]))
			{
				$row['reson_description'] = $user->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_title'])];
				$row['reason_title'] = $user->lang['report_reasons']['TITLE'][strtoupper($row['reason_title'])];
			}

			$_CLASS['core_template']->assign_vars_array('reports', array(
				'REPORT_ID'		=> $row['report_id'],
				'REASON_TITLE'	=> $row['reason_title'],
				'REASON_DESC'	=> $row['reason_description'],
				'REPORTER'		=> ($row['user_id'] != ANONYMOUS) ? $row['username'] : $_CLASS['core_user']->lang['GUEST'],
				'U_REPORTER'	=> ($row['user_id'] != ANONYMOUS) ? generate_link('members_list&amp;mode=viewprofile&amp;u='.$row['user_id']) : '',
				'USER_NOTIFY'	=> ($row['user_notify']),
				'REPORT_TIME'	=> $_CLASS['core_user']->format_date($row['report_time']),
				'REPORT_TEXT'	=> str_replace("\n", '<br />', trim($row['report_text'])))
			);
		}
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
	}
	$_CLASS['core_db']->free_result($result);
}*/

// Get IP
if ($_CLASS['forums_auth']->acl_get('m_info', $post_info['forum_id']))
{
	$rdns_ip_num = get_variable('rdns', 'REQUEST');
	$users_ary = array();

	$_CLASS['core_template']->assign('U_LOOKUP_ALL', ($rdns_ip_num === 'all') ? false : generate_link($url.'&amp;i=main&amp;mode=post_details&amp;rdns=all&amp;p='.$post_info['post_id']));

	// Get other users who've posted under this IP
	$sql = 'SELECT u.user_id, u.username, COUNT(*) as postings
		FROM ' . CORE_USERS_TABLE . ' u, ' . FORUMS_POSTS_TABLE . " p
		WHERE p.poster_id = u.user_id
			AND p.poster_ip = '" . $_CLASS['core_db']->escape($post_info['poster_ip']) . "'
			AND p.poster_id <> {$post_info['user_id']}
		GROUP BY u.user_id, u.username
		ORDER BY postings DESC";

	$result = $_CLASS['core_db']->query($sql);
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		// Fill the user select list with users who have posted
		// under this IP
		if ($row['user_id'] != $post_info['poster_id'])
		{
			$users_ary[strtolower($row['username'])] = $row;
		}

		$_CLASS['core_template']->assign_vars_array('userrow', array(
			'USERNAME'		=> ($row['user_id'] == ANONYMOUS) ? $_CLASS['core_user']->lang['GUEST'] : $row['username'],
			'NUM_POSTS'		=> $row['postings'],	
			'L_POST_S'		=> ($row['postings'] == 1) ? $_CLASS['core_user']->lang['POST'] : $_CLASS['core_user']->lang['POSTS'],

			'U_PROFILE'		=> ($row['user_id'] == ANONYMOUS) ? '' : generate_link('members_list&amp;mode=viewprofile&amp;u=' . $row['user_id']),
			'U_SEARCHPOSTS' => generate_link('forums&amp;file=search&amp;author=' . urlencode($row['username']) . '&amp;sr=topics')
		));
	}
	$_CLASS['core_db']->free_result($result);
	
	$user_select = '';
	ksort($users_ary);

	foreach ($users_ary as $row)
	{
		$user_select .= '<option value="' . $row['user_id'] . '">' . $row['username'] . "</option>\n";

	}
	$_CLASS['core_template']->assign('S_USER_SELECT', $user_select);

	unset($users_ary, $user_select);

	// Get other IP's this user has posted under
	$sql = 'SELECT poster_ip, COUNT(*) AS postings
		FROM ' . FORUMS_POSTS_TABLE . '
		WHERE poster_id = ' . $post_info['poster_id'] . '
		GROUP BY poster_ip
		ORDER BY postings DESC';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$hostname = (($rdns_ip_num == $row['poster_ip'] || $rdns_ip_num == 'all') && $row['poster_ip']) ? @gethostbyaddr($row['poster_ip']) : '';

		$_CLASS['core_template']->assign_vars_array('iprow', array(
			'IP'			=> $row['poster_ip'],
			'HOSTNAME'		=> $hostname,
			'NUM_POSTS'		=> $row['postings'],	
			'L_POST_S'		=> ($row['postings'] == 1) ? $_CLASS['core_user']->lang['POST'] : $_CLASS['core_user']->lang['POSTS'],

			'U_LOOKUP_IP'	=> ($rdns_ip_num == $row['poster_ip'] || $rdns_ip_num == 'all') ? '' : generate_link("$url&amp;mode=post_details&amp;p={$post_info['post_id']}&amp;rdns={$row['poster_ip']}#ip"),
			'U_WHOIS'		=> generate_link("forums&amp;file=mcp&amp;mode=post_details&amp;action=whois&amp;ip={$row['poster_ip']}")
		));
	}
	$_CLASS['core_db']->free_result($result);
}

page_header();
$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP'), 'modules/forums/mcp_post.html');


/**
* Change a post's poster
*/
function change_poster(&$post_info, $userdata)
{
	global $_CLASS;

	if (empty($userdata) || $userdata['user_id'] == $post_info['user_id'])
	{
		return;
	}

	$post_id = $post_info['post_id'];

	$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . "
		SET poster_id = {$userdata['user_id']}
		WHERE post_id = $post_id";
	$_CLASS['core_db']->query($sql);

	// Resync topic/forum if needed
	if ($post_info['topic_last_post_id'] == $post_id || $post_info['forum_last_post_id'] == $post_id || $post_info['topic_first_post_id'] == $post_id)
	{
		sync('topic', 'topic_id', $post_info['topic_id'], false, false);
		sync('forum', 'forum_id', $post_info['forum_id'], false, false);
	}

	// Adjust post counts
	if ($post_info['post_postcount'])
	{
		$sql = 'UPDATE ' . CORE_USERS_TABLE . '
			SET user_posts = user_posts - 1
			WHERE user_id = ' . $post_info['user_id'];
		$_CLASS['core_db']->query($sql);

		$sql = 'UPDATE ' . CORE_USERS_TABLE . '
			SET user_posts = user_posts + 1
			WHERE user_id = ' . $userdata['user_id'];
		$_CLASS['core_db']->query($sql);
	}

	// Add posted to information for this topic for the new user
	//markread('post', $post_info['forum_id'], $post_info['topic_id'], $_CLASS['core_user']->time, $userdata['user_id']);

	// Remove the dotted topic option if the old user has no more posts within this topic
	/*if ($config['load_db_track'] && $post_info['user_id'] != ANONYMOUS)
	{
		$sql = 'SELECT topic_id
			FROM ' . FORUMS_POSTS_TABLE . '
			WHERE topic_id = ' . $post_info['topic_id'] . '
				AND poster_id = ' . $post_info['user_id'];
		$result = $_CLASS['core_db']->query_limit($sql, 1);
		$topic_id = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$topic_id = (int) $topic_id['topic_id'];

		if (!$topic_id)
		{
			$sql = 'DELETE FROM ' . FORUMS_TOPICS_POSTED_TABLE . '
				WHERE user_id = ' . $post_info['user_id'] . '
					AND topic_id = ' . $post_info['topic_id'];
			$_CLASS['core_db']->query($sql);
		}
	}*/

	// Do not change the poster_id within the attachments table, since they were still posted by the original user

	$from_username = $post_info['username'];
	$to_username = $userdata['username'];

	// Renew post info
	$post_info = get_post_data(array($post_id));

	if (empty($post_info[$post_id]))
	{
		trigger_error($user->lang['POST_NOT_EXIST']);
	}

	$post_info = $post_info[$post_id];

	// Now add log entry
	add_log('mod', $post_info['forum_id'], $post_info['topic_id'], 'LOG_MCP_CHANGE_POSTER', $post_info['topic_title'], $from_username, $to_username);
}

?>