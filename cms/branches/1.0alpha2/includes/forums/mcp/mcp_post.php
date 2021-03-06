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
// $Id: mcp_post.php,v 1.4 2004/07/19 20:13:16 acydburn Exp $
//
// FILENAME  : mcp_post.php
// STARTED   : Thu Jul 08, 2004
// COPYRIGHT : � 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

function mcp_post_details($id, $mode, $action, $url)
{
	global $config, $_CLASS;
	
	$_CLASS['core_user']->add_lang('posting');

	$_CLASS['core_template']->assign(array(
		'L_POST_DETAILS'		=> $_CLASS['core_user']->lang['POST_DETAILS'],
		'L_POST_SUBJECT'		=> $_CLASS['core_user']->lang['POST_SUBJECT'],
		'L_POSTER'				=> $_CLASS['core_user']->lang['POSTER'],
		'L_READ_PROFILE'		=> $_CLASS['core_user']->lang['READ_PROFILE'],
		'L_READ_USERNOTES'		=> $_CLASS['core_user']->lang['READ_USERNOTES'],
		'L_READ_WARNINGS'		=> $_CLASS['core_user']->lang['READ_WARNINGS'],
		'L_THIS_POST_IP'		=> $_CLASS['core_user']->lang['THIS_POST_IP'],
		'L_POSTED'				=> $_CLASS['core_user']->lang['POSTED'],
		'L_PREVIEW'				=> $_CLASS['core_user']->lang['PREVIEW'],
		'L_APPROVE'				=> $_CLASS['core_user']->lang['APPROVE'],
		'L_DISAPPROVE'			=> $_CLASS['core_user']->lang['DISAPPROVE'],
		'L_REPORTS'				=> $_CLASS['core_user']->lang['REPORTS'],
		'L_ADD_FEEDBACK'		=> $_CLASS['core_user']->lang['ADD_FEEDBACK'],
		'L_FEEDBACK'			=> $_CLASS['core_user']->lang['FEEDBACK'],
		'L_DELETE_MARKED'		=> $_CLASS['core_user']->lang['DELETE_MARKED'],
		'L_DELETE_ALL'			=> $_CLASS['core_user']->lang['DELETE_ALL'],
		'L_REPORTER'			=> $_CLASS['core_user']->lang['REPORTER'],
		'L_MORE_INFO'			=> $_CLASS['core_user']->lang['MORE_INFO'],
		'L_MOD_OPTIONS'			=> $_CLASS['core_user']->lang['MOD_OPTIONS'],
		'L_CHANGE_POSTER'		=> $_CLASS['core_user']->lang['CHANGE_POSTER'],
		'L_CONFIRM'				=> $_CLASS['core_user']->lang['CONFIRM'],
		'L_SEARCH'				=> $_CLASS['core_user']->lang['SEARCH'],
		'L_MOD_OPTIONS'			=> $_CLASS['core_user']->lang['MOD_OPTIONS'],
		'L_UNLOCK_POST'			=> $_CLASS['core_user']->lang['UNLOCK_POST'],
		'L_UNLOCK_POST_EXPLAIN'	=> $_CLASS['core_user']->lang['UNLOCK_POST_EXPLAIN'],
		'L_LOCK_POST'			=> $_CLASS['core_user']->lang['LOCK_POST'],
		'L_LOCK_POST_EXPLAIN'	=> $_CLASS['core_user']->lang['LOCK_POST_EXPLAIN'],
		'L_DELETE_POST'			=> $_CLASS['core_user']->lang['DELETE_POST'],
		'L_SUBMIT'				=> $_CLASS['core_user']->lang['SUBMIT'],
		'L_IP_INFO'				=> $_CLASS['core_user']->lang['IP_INFO'],
		'L_OTHER_USERS'			=> $_CLASS['core_user']->lang['OTHER_USERS'],
		'L_NO_MATCHES_FOUND'	=> $_CLASS['core_user']->lang['NO_MATCHES_FOUND'],
		'L_OTHER_IPS'			=> $_CLASS['core_user']->lang['OTHER_IPS'],
		'L_LOOKUP_ALL'			=> $_CLASS['core_user']->lang['LOOKUP_ALL'],
		'L_JUMP_TO'				=> $_CLASS['core_user']->lang['JUMP_TO'],
		'L_GO'					=> $_CLASS['core_user']->lang['GO'],
		'L_LOOKUP_IP'			=> $_CLASS['core_user']->lang['LOOKUP_IP'])
	);
	

	$post_id = request_var('p', 0);
	$start	= request_var('start', 0);

	// Get post data
	$post_info = get_post_data(array($post_id));

	if (!sizeof($post_info))
	{
		trigger_error($_CLASS['core_user']->lang['POST_NOT_EXIST']);
	}

	$post_info = $post_info[$post_id];

	switch ($action)
	{
		case 'chgposter_search':
		
			$username = request_var('username', '');

			if ($username)
			{
				$users_ary = array();

				if (strpos($username, '*') === false)
				{
					$username = "*$username*";
				}
				$username = str_replace('*', '%', str_replace('%', '\%', $username));

				$sql = 'SELECT user_id, username
					FROM ' . USERS_TABLE . "
					WHERE username LIKE '" . $_CLASS['core_db']->sql_escape($username) . "'
						AND user_type NOT IN (" . USER_INACTIVE . ', ' . USER_IGNORE . ')
						AND user_id <> ' . $post_info['user_id'];
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$users_ary[strtolower($row['username'])] = $row;
				}

				$user_select = '';
				ksort($users_ary);
				foreach ($users_ary as $row)
				{
					$user_select .= '<option value="' . $row['user_id'] . '">' . $row['username'] . "</option>\n";
				}
			}

			if (!$user_select)
			{
				$_CLASS['core_template']->assign('MESSAGE', $_CLASS['core_user']->lang['NO_MATCHES_FOUND']);
			}

			$_CLASS['core_template']->assign(array(
				'S_USER_SELECT'		=>	$user_select,
				'SEARCH_USERNAME'	=>	request_var('username', ''))
			);
			break;

		case 'chgposter':

			$new_user = request_var('u', 0);

			if ($new_user && $_CLASS['auth']->acl_get('m_', $post_info['forum_id']) && $new_user != $post_info['user_id'])
			{
				$sql = 'UPDATE ' . POSTS_TABLE . "
					SET poster_id = $new_user
					WHERE post_id = $post_id";
				$_CLASS['core_db']->sql_query($sql);

				if ($post_info['topic_last_post_id'] == $post_info['post_id'] || $post_info['forum_last_post_id'] == $post_info['post_id'])
				{
					sync('topic', 'topic_id', $post_info['topic_id'], false, false);
					sync('forum', 'forum_id', $post_info['forum_id'], false, false);
				}
				
				// Renew post info
				$post_info = get_post_data(array($post_id));

				if (!sizeof($post_info))
				{
					trigger_error($_CLASS['core_user']->lang['POST_NOT_EXIST']);
				}

				$post_info = $post_info[$post_id];
			}
			break;
			
		case 'del_marked':
		case 'del_all':
		case 'add_feedback':
			
			$deletemark = ($action == 'del_marked') ? true : false;
			$deleteall	= ($action == 'del_all') ? true : false;
			$marked		= request_var('marknote', 0);
			$usernote	= request_var('usernote', '');

			if (($deletemark || $deleteall) && $_CLASS['auth']->acl_get('a_clearlogs'))
			{
				$where_sql = '';
				if ($deletemark && $marked)
				{
					$sql_in = array();
					foreach ($marked as $mark)
					{
						$sql_in[] = $mark;
					}
					$where_sql = ' AND log_id IN (' . implode(', ', $sql_in) . ')';
					unset($sql_in);
				}

				$sql = 'DELETE FROM ' . LOG_TABLE . '
					WHERE log_type = ' . LOG_USERS . " 
						$where_sql";
				$_CLASS['core_db']->sql_query($sql);

				add_log('admin', 'LOG_USERS_CLEAR');

				$msg = ($deletemark) ? 'MARKED_DELETED' : 'ALL_DELETED';
				$redirect = generate_link("$url&amp;i=$id&amp;mode=post_details");
				$_CLASS['core_display']->meta_refresh(2, $redirect);
				trigger_error($_CLASS['core_user']->lang[$msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
			}

			if ($usernote && $action == 'add_feedback')
			{
				add_log('admin', 'LOG_USER_FEEDBACK', $post_info['username']);
				add_log('user', $post_info['user_id'], 'LOG_USER_GENERAL', $usernote);

				$redirect = generate_link("$url&amp;i=$id&amp;mode=post_details");
				$_CLASS['core_display']->meta_refresh(2, $redirect);
				trigger_error($_CLASS['core_user']->lang['USER_FEEDBACK_ADDED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
			}
			break;
			
		default:
	}

	// Set some vars
	$users_ary = array();
	$poster = ($post_info['user_colour']) ? '<span style="color:#' . $post_info['user_colour'] . '">' . $post_info['username'] . '</span>' : $post_info['username'];

	// Process message, leave it uncensored
	$message = $post_info['post_text'];
	if ($post_info['bbcode_bitfield'])
	{
		global $site_file_root;
		require_once($site_file_root.'includes/forums/bbcode.php');
		$bbcode = new bbcode($post_info['bbcode_bitfield']);
		$bbcode->bbcode_second_pass($message, $post_info['bbcode_uid'], $post_info['bbcode_bitfield']);
	}
	$message = smiley_text($message);

	$_CLASS['core_template']->assign(array(
		'U_MCP_ACTION'			=> generate_link($url.'&amp;i=main&amp;quickmod=1'), // Use this for mode paramaters
		'U_POST_ACTION'			=> generate_link("$url&amp;i=$id&amp;mode=post_details"), // Use this for action parameters
		'U_APPROVE_ACTION'		=> generate_link('Forums&amp;file=mcp&amp;i=queue&amp;p='.$post_id),

		'S_CAN_VIEWIP'			=> $_CLASS['auth']->acl_get('m_ip', $post_info['forum_id']),
		'S_CAN_CHGPOSTER'		=> $_CLASS['auth']->acl_get('m_', $post_info['forum_id']),
		'S_CAN_LOCK_POST'		=> $_CLASS['auth']->acl_get('m_lock', $post_info['forum_id']),
		'S_CAN_DELETE_POST'		=> $_CLASS['auth']->acl_get('m_delete', $post_info['forum_id']),

		'S_POST_REPORTED'		=> $post_info['post_reported'],
		'S_POST_UNAPPROVED'		=> !$post_info['post_approved'],
		'S_POST_LOCKED'			=> $post_info['post_edit_locked'],
		'S_USER_WARNINGS'		=> ($post_info['user_warnings']) ? true : false,
		'S_SHOW_USER_NOTES'		=> true,
		'S_CLEAR_ALLOWED'		=> ($_CLASS['auth']->acl_get('a_clearlogs')) ? true : false,

		'U_VIEW_PROFILE'		=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $post_info['user_id']),
//		'U_MCP_USERNOTES'		=> generate_link('Forums&amp;file=mcp&amp;i=notes&amp;mode=user_notes&amp;u=' . $post_info['user_id']),
//		'U_MCP_WARNINGS'		=> generate_link('Forums&amp;file=mcp&amp;i=warnings&amp;mode=view_user&amp;u=' . $post_info['user_id']),
		'U_EDIT'				=> ($_CLASS['auth']->acl_get('m_edit', $post_info['forum_id'])) ? generate_link("Forums&amp;file=posting&amp;mode=edit&amp;f={$post_info['forum_id']}&amp;p={$post_info['post_id']}") : '',

		'RETURN_TOPIC'			=> sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;p=$post_id#$post_id").'">', '</a>'),
		'RETURN_FORUM'			=> sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link("Forums&amp;file=viewforum&amp;f={$post_info['forum_id']}&amp;start={$start}").'">', '</a>'),
		'REPORTED_IMG'			=> $_CLASS['core_user']->img('icon_reported', $_CLASS['core_user']->lang['POST_REPORTED']),
		'UNAPPROVED_IMG'		=> $_CLASS['core_user']->img('icon_unapproved', $_CLASS['core_user']->lang['POST_UNAPPROVED']),
		'EDIT_IMG'				=> $_CLASS['core_user']->img('btn_edit', $_CLASS['core_user']->lang['EDIT_POST']),

		'POSTER_NAME'			=> $poster,
		'POST_PREVIEW'			=> $message,
		'POST_SUBJECT'			=> $post_info['post_subject'],
		'POST_DATE'				=> $_CLASS['core_user']->format_date($post_info['post_time']),
		'POST_IP'				=> $post_info['poster_ip'],
		'POST_IPADDR'			=> @gethostbyaddr($post_info['poster_ip']),
		'POST_ID'				=> $post_info['post_id'])
	);
	
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
				'ID'			=> $row['id'])
			);
		}
	}
	
	// Get Reports
	if ($_CLASS['auth']->acl_get('m_', $post_info['forum_id']))
	{
		$sql = 'SELECT r.*, re.*, u.user_id, u.username 
			FROM ' . REPORTS_TABLE . ' r, ' . USERS_TABLE . ' u, ' . REASONS_TABLE . " re
			WHERE r.post_id = $post_id
				AND r.reason_id = re.reason_id
				AND u.user_id = r.user_id
			ORDER BY r.report_time DESC";
		$result = $_CLASS['core_db']->sql_query($sql);

		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$_CLASS['core_template']->assign('S_SHOW_REPORTS', true);

			do
			{
				$_CLASS['core_template']->assign_vars_array('reports', array(
					'REPORT_ID'		=> $row['report_id'],
					'REASON_TITLE'	=> $_CLASS['core_user']->lang['report_reasons']['TITLE'][strtoupper($row['reason_name'])],
					'REASON_DESC'	=> $_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][strtoupper($row['reason_name'])],
					'REPORTER'		=> ($row['user_id'] != ANONYMOUS) ? $row['username'] : $_CLASS['core_user']->lang['GUEST'],
					'U_REPORTER'	=> ($row['user_id'] != ANONYMOUS) ? generate_link('Members_List&amp;mode=viewprofile&amp;u='.$row['user_id']) : '',
					'USER_NOTIFY'	=> ($row['user_notify']) ? true : false,
					'REPORT_TIME'	=> $_CLASS['core_user']->format_date($row['report_time']),
					'REPORT_TEXT'	=> str_replace("\n", '<br />', trim($row['report_text'])))
				);
			}
			while ($row = $_CLASS['core_db']->sql_fetchrow($result));
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}
	
	// Get IP
	if ($_CLASS['auth']->acl_get('m_ip', $post_info['forum_id']))
	{
		$rdns_ip_num = request_var('rdns', '');

		if ($rdns_ip_num != 'all')
		{
			$_CLASS['core_template']->assign(array(
				'U_LOOKUP_ALL'	=> generate_link($url.'&amp;i=main&amp;mode=post_details&amp;rdns=all'))
			);
		}

		// Get other users who've posted under this IP
		$sql = 'SELECT u.user_id, u.username, COUNT(*) as postings
			FROM ' . USERS_TABLE . ' u, ' . POSTS_TABLE . " p
			WHERE p.poster_id = u.user_id
				AND p.poster_ip = '{$post_info['poster_ip']}'
				AND p.poster_id <> {$post_info['user_id']}
			GROUP BY u.user_id
			ORDER BY postings DESC";
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
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

				'U_PROFILE'		=> ($row['user_id'] == ANONYMOUS) ? '' : generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
				'U_SEARCHPOSTS' => generate_link('Forums&amp;file=search&amp;search_author=' . urlencode($row['username']) . '&amp;showresults=topics'))
			);
		}
		$_CLASS['core_db']->sql_freeresult($result);

		// Get other IP's this user has posted under
		$sql = 'SELECT poster_ip, COUNT(*) AS postings
			FROM ' . POSTS_TABLE . '
			WHERE poster_id = ' . $post_info['poster_id'] . '
			GROUP BY poster_ip
			ORDER BY postings DESC';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$hostname = (($rdns_ip_num == $row['poster_ip'] || $rdns_ip_num == 'all') && $row['poster_ip']) ? @gethostbyaddr($row['poster_ip']) : '';

			$_CLASS['core_template']->assign_vars_array('iprow', array(
				'IP'			=> $row['poster_ip'],
				'HOSTNAME'		=> $hostname,
				'NUM_POSTS'		=> $row['postings'],	
				'L_POST_S'		=> ($row['postings'] == 1) ? $_CLASS['core_user']->lang['POST'] : $_CLASS['core_user']->lang['POSTS'],

				'U_LOOKUP_IP'	=> ($rdns_ip_num == $row['poster_ip'] || $rdns_ip_num == 'all') ? '' : generate_link("$url&amp;i=$id&amp;mode=post_details&amp;rdns={$row['poster_ip']}#ip"),
				'U_WHOIS'		=> generate_link("Forums&amp;file=mcp&amp;i=$id&amp;mode=whois&amp;ip={$row['poster_ip']}"))
			);
		}
		$_CLASS['core_db']->sql_freeresult($result);

		// If we were not searching for a specific username fill
		// the user_select box with users who have posted under
		// the same IP
		if ($action != 'chgposter_search')
		{
			$user_select = '';
			ksort($users_ary);
			foreach ($users_ary as $row)
			{
				$user_select .= '<option value="' . $row['user_id'] . '">' . $row['username'] . "</option>\n";
			}
			$_CLASS['core_template']->assign('S_USER_SELECT', $user_select);
		}
	}

}

?>