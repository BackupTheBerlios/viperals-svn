<?php
// -------------------------------------------------------------
//
// $Id: ucp_pm_viewmessage.php,v 1.3 2004/07/11 15:20:32 acydburn Exp $
//
// FILENAME  : viewmessage.php
// STARTED   : Mon Apr 12, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
	
function view_message($id, $mode, $folder_id, $msg_id, $folder, $message_row)
{
	global $_CLASS, $_CORE_CONFIG, $site_file_root, $config;
	
	$_CLASS['core_user']->add_lang('viewtopic');
	$msg_id		= (int) $msg_id;
	$folder_id	= (int) $folder_id;
	$author_id	= (int) $message_row['author_id'];
	
	// Not able to view message, it was deleted by the sender
	if ($message_row['deleted'])
	{
		trigger_error('NO_AUTH_READ_REMOVED_MESSAGE');
	}
	
	// Grab icons
	$icons = array();
	obtain_icons($icons);

	// Instantiate BBCode if need be
	if ($message_row['bbcode_bitfield'])
	{
		require($site_file_root.'includes/forums/bbcode.php');
		$bbcode = new bbcode($message_row['bbcode_bitfield']);
	}

	// Assign TO/BCC Addresses to template
	write_pm_addresses(array('to' => $message_row['to_address'], 'bcc' => $message_row['bcc_address']), $author_id);

	$user_info = get_user_informations($author_id, $message_row);

	// Parse the message and subject
	$message = $message_row['message_text'];

	// If the board has HTML off but the message has HTML on then we process it, else leave it alone
	if (!$config['auth_html_pm'] || !$_CLASS['auth']->acl_get('u_pm_html'))
	{
		if ($message_row['enable_html'] && $config['auth_bbcode_pm'] && $_CLASS['auth']->acl_get('u_pm_bbcode'))
		{
			$message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $message);
		}
	}

	// Second parse bbcode here
	if ($message_row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($message, $message_row['bbcode_uid'], $message_row['bbcode_bitfield']);
	}

	// Always process smilies after parsing bbcodes
	$message = smiley_text($message);

	// Replace naughty words such as farty pants
	$message_row['message_subject'] = censor_text($message_row['message_subject']);
	$message = str_replace("\n", '<br />', censor_text($message));

	// Editing information
	if ($message_row['message_edit_count'] && $config['display_last_edited'])
	{
		$l_edit_time_total = ($message_row['message_edit_count'] == 1) ? $_CLASS['core_user']->lang['EDITED_TIME_TOTAL'] : $_CLASS['core_user']->lang['EDITED_TIMES_TOTAL'];
		$l_edited_by = '<br /><br />' . sprintf($l_edit_time_total, (!$message_row['message_edit_user']) ? $message_row['username'] : $message_row['message_edit_user'], $_CLASS['core_user']->format_date($message_row['message_edit_time']), $message_row['message_edit_count']);
	}
	else
	{
		$l_edited_by = '';
	}

	// Pull attachment data
	$display_notice = false;
	$attachments = array();

	if ($message_row['message_attachment'] && $config['allow_pm_attach'])
	{
		if ($config['auth_download_pm'] && $_CLASS['auth']->acl_get('u_pm_download'))
		{
			require($site_file_root.'includes/forums/functions_display.php');

			$sql = 'SELECT * 
				FROM ' . ATTACHMENTS_TABLE . "
				WHERE post_msg_id = $msg_id
					AND in_message = 1
				ORDER BY filetime " . ((!$config['display_order']) ? 'DESC' : 'ASC') . ', post_msg_id ASC';
			$result = $_CLASS['core_db']->sql_query($sql);

			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$attachments[] = $row;
			}
			$_CLASS['core_db']->sql_freeresult($result);
	
			// No attachments exist, but message table thinks they do so go ahead and reset attach flags
			if (!sizeof($attachments))
			{
				$sql = 'UPDATE ' . PRIVMSGS_TABLE . " 
					SET message_attachment = 0 
					WHERE msg_id = $msg_id";
				$_CLASS['core_db']->sql_query($sql);
			}
		}
		else
		{
			$display_notice = true;
		}
	}

	// Assign inline attachments
	if (isset($attachments) && sizeof($attachments))
	{
		$unset_attachments = parse_inline_attachments($message, $attachments, $update_count, 0);
	
		// Needed to let not display the inlined attachments at the end of the message again
		foreach ($unset_attachments as $index)
		{
			unset($attachments[$index]);
		}
	}

	$user_info['sig'] = '';

	$signature = ($message_row['enable_sig'] && $config['allow_sig'] && $_CLASS['auth']->acl_get('u_sig') && $_CLASS['core_user']->optionget('viewsigs')) ? $user_info['user_sig'] : '';
	
	// End signature parsing, only if needed
	if ($signature)
	{
		if ($user_info['user_sig_bbcode_bitfield'])
		{
			if (!isset($bbcode) || !$bbcode)
			{
				require($site_file_root.'includes/forums/bbcode.php');
				$bbcode = new bbcode($user_info['user_sig_bbcode_bitfield']);
			}

			$bbcode->bbcode_second_pass($signature, $user_info['user_sig_bbcode_uid'], $user_info['user_sig_bbcode_bitfield']);
		}

		$signature = smiley_text($signature);
		$signature = str_replace("\n", '<br />', censor_text($signature));
	}

	$url = 'Control_Panel&amp;i='.$id;

	$_CLASS['core_template']->assign(array(
		'AUTHOR_NAME'		=> ($user_info['user_colour']) ? '<span style="color:#' . $user_info['user_colour'] . '">' . $user_info['username'] . '</span>' : $user_info['username'],
		'AUTHOR_RANK' 		=> $user_info['rank_title'],
		'RANK_IMAGE' 		=> $user_info['rank_image'],
		'AUTHOR_AVATAR'		=> (isset($user_info['avatar'])) ? $user_info['avatar'] : '',
		'AUTHOR_JOINED'		=> $_CLASS['core_user']->format_date($user_info['user_regdate'], $_CLASS['core_user']->lang['DATE_FORMAT']),
		'AUTHOR_POSTS' 		=> (!empty($user_info['user_posts'])) ? $user_info['user_posts'] : '',
		'AUTHOR_FROM' 		=> (!empty($user_info['user_from'])) ? $user_info['user_from'] : '',

		'ONLINE_IMG'		=> (!$config['load_onlinetrack']) ? '' : ((isset($user_info['online']) && $user_info['online']) ? $_CLASS['core_user']->img('btn_online', $_CLASS['core_user']->lang['ONLINE']) : $_CLASS['core_user']->img('btn_offline', $_CLASS['core_user']->lang['OFFLINE'])),
		'DELETE_IMG' 		=> $_CLASS['core_user']->img('btn_delete', $_CLASS['core_user']->lang['DELETE_MESSAGE']),
		'INFO_IMG' 			=> $_CLASS['core_user']->img('btn_info', $_CLASS['core_user']->lang['VIEW_PM_INFO']),
		'REPORT_IMG'		=> $_CLASS['core_user']->img('btn_report', $_CLASS['core_user']->lang['REPORT_PM']),
		'REPORTED_IMG'		=> $_CLASS['core_user']->img('icon_reported', $_CLASS['core_user']->lang['MESSAGE_REPORTED_MESSAGE']),
		'PROFILE_IMG'		=> $_CLASS['core_user']->img('btn_profile', $_CLASS['core_user']->lang['READ_PROFILE']), 
		'EMAIL_IMG' 		=> $_CLASS['core_user']->img('btn_email', $_CLASS['core_user']->lang['SEND_EMAIL']),
		'QUOTE_IMG' 		=> $_CLASS['core_user']->img('btn_quote', $_CLASS['core_user']->lang['POST_QUOTE_PM']),
		'REPLY_IMG'			=> $_CLASS['core_user']->img('btn_reply_pm', $_CLASS['core_user']->lang['POST_REPLY_PM']),
		'EDIT_IMG' 			=> $_CLASS['core_user']->img('btn_edit', $_CLASS['core_user']->lang['POST_EDIT_PM']),
		'MINI_POST_IMG'		=> $_CLASS['core_user']->img('icon_post', $_CLASS['core_user']->lang['PM']),

		'SENT_DATE' 		=> $_CLASS['core_user']->format_date($message_row['message_time']),
		'SUBJECT'			=> $message_row['message_subject'],
		'MESSAGE' 			=> $message,
		'SIGNATURE' 		=> ($message_row['enable_sig']) ? $signature : '',
		'EDITED_MESSAGE'	=> $l_edited_by,

		'U_MCP_REPORT'		=> generate_link('Forums&amp;file=mcp&amp;mode=pm_details&amp;p=' . $message_row['msg_id']),
		'U_REPORT'			=> ($config['auth_report_pm'] && $_CLASS['auth']->acl_get('u_pm_report')) ? generate_link('Forums&amp;file=report&amp;pm=' . $message_row['msg_id']) : '',
		'U_INFO'			=> ($_CLASS['auth']->acl_get('m_') && ($message_row['message_reported'] || $message_row['forwarded'])) ? generate_link('Forums&amp;file=mcp&amp;mode=pm_details&amp;p=' . $message_row['msg_id']) : '',
		'U_DELETE' 			=> ($_CLASS['auth']->acl_get('u_pm_delete')) ? generate_link("$url&amp;mode=compose&amp;action=delete&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '',
		'U_AUTHOR_PROFILE' 		=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $author_id),
		'U_EMAIL' 			=> $user_info['email'],
		'U_QUOTE'			=> ($_CLASS['auth']->acl_get('u_sendpm') && $author_id != $_CLASS['core_user']->data['user_id']) ? generate_link("$url&amp;mode=compose&amp;action=quote&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '',
		'U_EDIT' 			=> (($message_row['message_time'] > time() - $config['pm_edit_time'] || !$config['pm_edit_time']) && $folder_id == PRIVMSGS_OUTBOX && $_CLASS['auth']->acl_get('u_pm_edit')) ? generate_link("$url&amp;mode=compose&amp;action=edit&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '', 
		'U_POST_REPLY_PM' 	=> ($author_id != $_CLASS['core_user']->data['user_id'] && $_CLASS['auth']->acl_get('u_sendpm')) ? generate_link("$url&amp;mode=compose&amp;action=reply&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '',
		'U_PREVIOUS_PM'		=> generate_link("$url&amp;f=$folder_id&amp;p=" . $message_row['msg_id'] . "&amp;view=previous"),
		'U_NEXT_PM'			=> generate_link("$url&amp;f=$folder_id&amp;p=" . $message_row['msg_id'] . "&amp;view=next"),

		'S_MESSAGE_REPORTED'=> ($message_row['message_reported'] && $_CLASS['auth']->acl_get('m_')) ? true : false,
		'S_HAS_ATTACHMENTS' => (sizeof($attachments)) ? true : false,
		'S_DISPLAY_NOTICE'	=> $display_notice && $message_row['message_attachment'],

		'U_PRINT_PM'		=> ($config['print_pm'] && $_CLASS['auth']->acl_get('u_pm_printpm')) ? generate_link("$url&amp;f=$folder_id&amp;p=" . $message_row['msg_id'] . "&amp;view=print") : '',
		'U_EMAIL_PM'		=> ($config['email_pm'] && $_CORE_CONFIG['email']['email_enable'] && $_CLASS['auth']->acl_get('u_pm_emailpm')) ? 'Email' : '',
		'U_FORWARD_PM'		=> ($config['forward_pm'] && $_CLASS['auth']->acl_get('u_pm_forward')) ? generate_link("$url&amp;mode=compose&amp;action=forward&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '')
	);

	// Display not already displayed Attachments for this post, we already parsed them. ;)
	if (isset($attachments) && sizeof($attachments))
	{
		foreach ($attachments as $attachment)
		{
			
			$_CLASS['core_template']->assign_vars_array('attachment', array(
				'DISPLAY_ATTACHMENT' => $attachment)
			);
		}
	}

	if (!isset($_REQUEST['view']) || $_REQUEST['view'] != 'print')
	{
		// Message History
		if (message_history($msg_id, $_CLASS['core_user']->data['user_id'], $message_row, $folder))
		{
			$_CLASS['core_template']->assign('S_DISPLAY_HISTORY', true);
		}
	}
}	

// Display Message History
function message_history($msg_id, $user_id, $message_row, $folder)
{
	global $config, $site_file_root, $_CLASS, $bbcode;

	// Get History Messages (could be newer)
	$sql = 'SELECT t.*, p.*, u.*
		FROM ' . PRIVMSGS_TABLE . ' p, ' . PRIVMSGS_TO_TABLE . ' t, ' . USERS_TABLE . ' u
		WHERE t.msg_id = p.msg_id 
			AND p.author_id = u.user_id
			AND t.folder_id <> ' . PRIVMSGS_NO_BOX . "
			AND t.user_id = $user_id";

	if (!$message_row['root_level'])
	{
		$sql .= " AND (p.root_level = $msg_id OR (p.root_level = 0 AND p.msg_id = $msg_id))";
	}
	else
	{
		$sql .= " AND (p.root_level = " . $message_row['root_level'] . ' OR p.msg_id = ' . $message_row['root_level'] . ')';
	}
	$sql .= ' ORDER BY p.message_time ';
	$sort_dir = (!empty($_CLASS['core_user']->data['user_sortby_dir'])) ? $_CLASS['core_user']->data['user_sortby_dir'] : 'd';
	$sql .= ($sort_dir == 'd') ? 'ASC' : 'DESC';

	$result = $_CLASS['core_db']->sql_query($sql);

	if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
	{
		return false;
	}

	$rowset = array();
	$bbcode_bitfield = 0;
	$folder_url = 'Control_Panel&amp;i=pm&amp;folder=';

	$title = ($sort_dir == 'd') ? $row['message_subject'] : '';
	do
	{
		$folder_id = (int) $row['folder_id'];

		$row['folder'][] = (isset($folder[$folder_id])) ? '<a href="' . generate_link($folder_url . $folder_id) . '">' . $folder[$folder_id]['folder_name'] . '</a>' : $_CLASS['core_user']->lang['UNKOWN_FOLDER'];

		if (isset($rowset[$row['msg_id']]))
		{
			$rowset[$row['msg_id']]['folder'][] = (isset($folder[$folder_id])) ? '<a href="' . generate_link($folder_url . $folder_id) . '">' . $folder[$folder_id]['folder_name'] . '</a>' : $_CLASS['core_user']->lang['UNKOWN_FOLDER'];
		}
		else
		{
			$rowset[$row['msg_id']] = $row;
			$bbcode_bitfield |= $row['bbcode_bitfield'];
		}
	}
	while ($row = $_CLASS['core_db']->sql_fetchrow($result));
	$_CLASS['core_db']->sql_freeresult($result);

	$title = ($sort_dir == 'a') ? $row['message_subject'] : $title;

	if (sizeof($rowset) == 1)
	{
		return false;
	}
	
	// Instantiate BBCode class
	if (!isset($bbcode) && $bbcode_bitfield)
	{
		if (!class_exists('bbcode'))
		{
			require($site_file_root.'includes/forums/bbcode.php');
		}
		$bbcode = new bbcode($bbcode_bitfield);
	}

	$title = censor_text($title);

	$url = 'Control_Panel&amp;i=pm';
	$next_history_pm = $previous_history_pm = $prev_id = 0;
	
	foreach ($rowset as $id => $row)
	{
		$author_id	= $row['author_id'];
		$author		= $row['username'];
		$folder_id	= (int) $row['folder_id'];

		$subject	= $row['message_subject'];
		$message	= $row['message_text'];

		if ($row['bbcode_bitfield'])
		{
			$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
		}

		$message = smiley_text($message, !$row['enable_smilies']);

		$subject = censor_text($subject);
		$message = censor_text($message);

		if ($id == $msg_id)
		{
			$next_history_pm = next($rowset);
			$next_history_pm = (sizeof($next_history_pm)) ? (int) $next_history_pm['msg_id'] : 0;
			$previous_history_pm = $prev_id;
		}

		$_CLASS['core_template']->assign_vars_array('history_row', array(
			'AUTHOR_NAME' 		=> $author,
			'SUBJECT'	 		=> $subject,
			'SENT_DATE' 		=> $_CLASS['core_user']->format_date($row['message_time']),
			'MESSAGE' 			=> str_replace("\n", '<br />', $message), 
			'FOLDER'			=> implode(', ', $row['folder']),

			'S_CURRENT_MSG'		=> ($row['msg_id'] == $msg_id),

			'U_MSG_ID'			=> $row['msg_id'],
			'U_VIEW_MESSAGE'	=> generate_link("$url&amp;f=$folder_id&amp;p=" . $row['msg_id']),
			'U_AUTHOR_PROFILE' 	=> generate_link('Members_List&amp;mode=viewprofile&amp;u='.$author_id),
			'U_QUOTE'			=> ($_CLASS['auth']->acl_get('u_sendpm') && $author_id != $_CLASS['core_user']->data['user_id']) ? generate_link("$url&amp;mode=compose&amp;action=quote&amp;f=" . $folder_id . "&amp;p=" . $row['msg_id']) : '',
			'U_POST_REPLY_PM' 	=> ($author_id != $_CLASS['core_user']->data['user_id'] && $_CLASS['auth']->acl_get('u_sendpm')) ? generate_link("$url&amp;mode=compose&amp;action=reply&amp;f=$folder_id&amp;p=" . $row['msg_id']) : '')
		);
		unset($rowset[$id]);
		$prev_id = $id;
	}

	$_CLASS['core_template']->assign(array(
		'QUOTE_IMG' => $_CLASS['core_user']->img('btn_quote', $_CLASS['core_user']->lang['REPLY_WITH_QUOTE']),
		'TITLE'		=> $title,

		'U_VIEW_NEXT_HISTORY'		=> generate_link("$url&amp;p=" . (($next_history_pm) ? $next_history_pm : $msg_id)),
		'U_VIEW_PREVIOUS_HISTORY'	=> generate_link("$url&amp;p=" . (($previous_history_pm) ? $previous_history_pm : $msg_id)))
	);

	return true;
}

// Get User Informations (only for message display)
function get_user_informations($user_id, $user_row)
{
	global $config, $_CORE_CONFIG, $_CLASS;

	if (!$user_id)
	{
		return;
	}

	if (empty($user_row))
	{
		$user_row = get_userdata((int) $user_id);
	}

	// Grab ranks
	$ranks = array();
	obtain_ranks($ranks);

	// Generate online information for user
	if ($config['load_onlinetrack'])
	{
		$sql = 'SELECT session_user_id, MAX(session_time) as online_time, MIN(session_viewonline) AS viewonline
			FROM ' . SESSIONS_TABLE . " 
			WHERE session_user_id = $user_id
			GROUP BY session_user_id";
		$result = $_CLASS['core_db']->sql_query_limit($sql, 1);

		$update_time = $config['load_online_time'] * 60;
		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$user_row['online'] = (time() - $update_time < $row['online_time'] && ($row['viewonline'] && $user_row['user_allow_viewonline'])) ? true : false;
		} else {
			$user_row['online'] = false;
		}
	}
	else
	{
		$user_row['online'] = false;
	}

	if ($user_row['user_avatar'] && $_CLASS['core_user']->optionget('viewavatars'))
	{
		$avatar_img = '';
		switch ($user_row['user_avatar_type'])
		{
			case AVATAR_UPLOAD:
				$avatar_img = $config['avatar_path'] . '/';
				break;
			case AVATAR_GALLERY:
				$avatar_img = $config['avatar_gallery_path'] . '/';
				break;
		}
		$avatar_img .= $user_row['user_avatar'];

		$user_row['avatar'] = '<img src="' . $avatar_img . '" width="' . $user_row['user_avatar_width'] . '" height="' . $user_row['user_avatar_height'] . '" border="0" alt="" />';
	}

	if (!empty($user_row['user_rank']))
	{
		$user_row['rank_title'] = (!empty($ranks['special'][$user_row['user_rank']]['rank_title'])) ? $ranks['special'][$user_row['user_rank']]['rank_title'] : '';
		$user_row['rank_image'] = (!empty($ranks['special'][$user_row['user_rank']]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$user_row['user_rank']]['rank_image'] . '" border="0" alt="' . $ranks['special'][$user_row['user_rank']]['rank_title'] . '" title="' . $ranks['special'][$user_row['user_rank']]['rank_title'] . '" /><br />' : '';
	}
	elseif(isset($ranks['normal']))
	{
		foreach ($ranks['normal'] as $rank)
		{
			if ($user_row['user_posts'] >= $rank['rank_min'])
			{
				$user_row['rank_title'] = $rank['rank_title'];
				$user_row['rank_image'] = (!empty($rank['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $rank['rank_image'] . '" border="0" alt="' . $rank['rank_title'] . '" title="' . $rank['rank_title'] . '" /><br />' : '';
				break;
			}
		}
	}

	if (!empty($user_row['user_allow_viewemail']) || $_CLASS['auth']->acl_get('a_email'))
	{
		$user_row['email'] = ($config['board_email_form'] && $_CORE_CONFIG['email']['email_enable']) ? generate_link('Members_List&amp;mode=email&amp;u='.$user_id) : (($config['board_hide_emails'] && !$_CLASS['auth']->acl_get('a_email')) ? '' : 'mailto:' . $user_row['user_email']);
	}
	else
	{
		$user_row['email'] = '';
	}

	return $user_row;
}

?>