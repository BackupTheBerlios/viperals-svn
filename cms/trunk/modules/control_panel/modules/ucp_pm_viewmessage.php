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

load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);
	
function view_message($parent_class, $folder_id, $msg_id, $folder, &$message_row)
{
	///$id, $mode
	global $_CLASS, $_CORE_CONFIG, $config;

	$_CLASS['core_user']->add_lang('viewtopic');

	$msg_id		= (int) $msg_id;
	$folder_id	= (int) $folder_id;
	$author_id	= (int) $message_row['author_id'];
	
	// Not able to view message, it was deleted by the sender
	if ($message_row['pm_deleted'])
	{
		trigger_error('NO_AUTH_READ_REMOVED_MESSAGE');
	}
	
	// Do not allow hold messages to be seen
	if ($folder_id == PRIVMSGS_HOLD_BOX)
	{
		trigger_error('NO_AUTH_READ_HOLD_MESSAGE');
	}

	// Grab icons
	$icons = array();
	obtain_icons($icons);

	$bbcode = false;

	// Instantiate BBCode if need be
	if ($message_row['bbcode_bitfield'])
	{
		require_once(SITE_FILE_ROOT.'includes/forums/bbcode.php');
		$bbcode = new bbcode($message_row['bbcode_bitfield']);
	}

	// Assign TO/BCC Addresses to template
	write_pm_addresses(array('to' => $message_row['to_address'], 'bcc' => $message_row['bcc_address']), $author_id);

	$user_info = get_user_informations($author_id, $message_row);

	// Parse the message and subject

	// Replace naughty words such as farty pants
	$message_row['message_subject'] = censor_text($message_row['message_subject']);
	$message_row['message_text'] = censor_text($message_row['message_text']);

	// If the board has HTML off but the message has HTML on then we process it, else leave it alone
	if ($message_row['enable_html'] && !$config['auth_html_pm'])
	{
		$message = preg_replace('#(<!\-\- h \-\-><)([\/]?.*?)(><!\-\- h \-\->)#is', "&lt;\\2&gt;", $message);
	}

	// Second parse bbcode here
	if ($message_row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($message_row['message_text'], $message_row['bbcode_uid'], $message_row['bbcode_bitfield']);
	}

	// Always process smilies after parsing bbcodes
	$message_row['message_text'] = str_replace("\n", '<br />', smiley_text($message_row['message_text']));

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
		if ($config['auth_download_pm'])
		{
			$sql = 'SELECT * 
				FROM ' . FORUMS_ATTACHMENTS_TABLE . "
				WHERE post_msg_id = $msg_id
					AND in_message = 1
				ORDER BY filetime " . ((!$config['display_order']) ? 'DESC' : 'ASC') . ', post_msg_id ASC';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$attachments[] = $row;
			}
			$_CLASS['core_db']->free_result($result);
	
			// No attachments exist, but message table thinks they do so go ahead and reset attach flags
			if (empty($attachments))
			{
				$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TABLE . " 
					SET message_attachment = 0 
					WHERE msg_id = $msg_id";
				$_CLASS['core_db']->query($sql);
			}
		}
		else
		{
			$display_notice = true;
		}
	}

	$_CLASS['core_template']->assign('S_HAS_ATTACHMENTS', false);

	if (!empty($attachments))
	{
		require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';
		$null = array();

		$unset_attachments = parse_inline_attachments($message, $attachments, $update_count, 0);

		// Needed to let not display the inlined attachments at the end of the post again
		foreach ($unset_attachments as $index)
		{
			unset($attachments[$index]);
		}
		unset($unset_attachments);
			
		// Update the attachment download counts
		if (!empty($update_count))
		{
			$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . ' 
				SET download_count = download_count + 1 
				WHERE attach_id IN (' . implode(', ', array_unique($update_count)) . ')';
			$_CLASS['core_db']->query($sql);
		}

		if (!empty($attachments))
		{
			$_CLASS['core_template']->assign('S_HAS_ATTACHMENTS', true);
			$_CLASS['core_template']->assign('attachment', display_attachments(0, $attachments, $update_count, true));
		}

		unset($attachment_data, $null);
	}

	$user_info['sig'] = '';
	$signature = ($message_row['enable_sig'] && $config['allow_sig'] && $_CLASS['core_user']->user_data_get('viewsigs')) ? $user_info['user_sig'] : '';
	
	// End signature parsing, only if needed
	if ($signature)
	{
		if ($user_info['user_sig_bbcode_bitfield'])
		{
			if ($bbcode === false)
			{
				require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';
				$bbcode = new bbcode($user_info['user_sig_bbcode_bitfield']);
			}

			$bbcode->bbcode_second_pass($signature, $user_info['user_sig_bbcode_uid'], $user_info['user_sig_bbcode_bitfield']);
		}

		$signature = smiley_text($signature);
		$signature = str_replace("\n", '<br />', censor_text($signature));
	}

	$url = 'Control_Panel&amp;i=pm';

	$_CLASS['core_template']->assign_array(array(
		'AUTHOR_NAME'		=> ($user_info['user_colour']) ? '<span style="color:#' . $user_info['user_colour'] . '">' . $user_info['username'] . '</span>' : $user_info['username'],
		'AUTHOR_RANK' 		=> $user_info['rank_title'],
		'RANK_IMAGE' 		=> $user_info['rank_image'],
		'AUTHOR_AVATAR'		=> isset($user_info['avatar']) ? $user_info['avatar'] : '',
		'AUTHOR_JOINED'		=> $_CLASS['core_user']->format_date($user_info['user_reg_date']),
		'AUTHOR_POSTS' 		=> (!empty($user_info['user_posts'])) ? $user_info['user_posts'] : '',
		'AUTHOR_FROM' 		=> (!empty($user_info['user_from'])) ? $user_info['user_from'] : '',

		'ONLINE_IMG'		=> (!$config['load_onlinetrack']) ? '' : ((isset($user_info['online']) && $user_info['online']) ? $_CLASS['core_user']->img('btn_online', $_CLASS['core_user']->lang['ONLINE']) : $_CLASS['core_user']->img('btn_offline', $_CLASS['core_user']->lang['OFFLINE'])),
		'S_ONLINE'			=> (!$config['load_onlinetrack']) ? false : ((isset($user_info['online']) && $user_info['online']) ? true : false),
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
		'MESSAGE' 			=> $message_row['message_text'],
		'SIGNATURE' 		=> ($message_row['enable_sig']) ? $signature : '',
		'EDITED_MESSAGE'	=> $l_edited_by,

		'U_MCP_REPORT'		=> generate_link('Forums&amp;file=mcp&amp;mode=pm_details&amp;p=' . $message_row['msg_id']),
		'U_REPORT'			=> (false) ? generate_link('Forums&amp;file=report&amp;pm=' . $message_row['msg_id']) : '',
		'U_INFO'			=> ($_CLASS['forums_auth']->acl_get('m_') && ($message_row['message_reported'] || $message_row['pm_forwarded'])) ? generate_link('Forums&amp;file=mcp&amp;mode=pm_details&amp;p=' . $message_row['msg_id']) : '',
		'U_DELETE' 			=> generate_link("$url&amp;mode=compose&amp;action=delete&amp;f=$folder_id&amp;p=" . $message_row['msg_id']),
		'U_AUTHOR_PROFILE' 	=> generate_link('members_list&amp;mode=viewprofile&amp;u=' . $author_id),
		'U_EMAIL' 			=> $user_info['email'],
		'U_QUOTE'			=> ($author_id != $_CLASS['core_user']->data['user_id']) ? generate_link("$url&amp;mode=compose&amp;action=quote&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '',
		'U_EDIT' 			=> (($message_row['message_time'] > time() - $config['pm_edit_time'] || !$config['pm_edit_time']) && $folder_id == PRIVMSGS_OUTBOX) ? generate_link("$url&amp;mode=compose&amp;action=edit&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '', 
		'U_POST_REPLY_PM' 	=> ($author_id != $_CLASS['core_user']->data['user_id']) ? generate_link("$url&amp;mode=compose&amp;action=reply&amp;f=$folder_id&amp;p=" . $message_row['msg_id']) : '',
		'U_PREVIOUS_PM'		=> generate_link("$url&amp;f=$folder_id&amp;p=" . $message_row['msg_id'] . "&amp;view=previous"),
		'U_NEXT_PM'			=> generate_link("$url&amp;f=$folder_id&amp;p=" . $message_row['msg_id'] . "&amp;view=next"),

		'S_MESSAGE_REPORTED'=> ($message_row['message_reported'] && $_CLASS['forums_auth']->acl_get('m_')) ? true : false,
		'S_DISPLAY_NOTICE'	=> $display_notice && $message_row['message_attachment'],

		'U_PRINT_PM'		=> generate_link("$url&amp;f=$folder_id&amp;p=" . $message_row['msg_id'] . "&amp;view=print"),
		'U_EMAIL_PM'		=> ($_CORE_CONFIG['email']['email_enable']) ? 'Email' : '',
		'U_FORWARD_PM'		=> generate_link("$url&amp;mode=compose&amp;action=forward&amp;f=$folder_id&amp;p=" . $message_row['msg_id'])
	));

	if (!isset($_REQUEST['view']) || $_REQUEST['view'] !== 'print')
	{
		// Message History
		if (message_history($msg_id, $_CLASS['core_user']->data['user_id'], $message_row, $folder))
		{
			$_CLASS['core_template']->assign('S_DISPLAY_HISTORY', true);
		}
	}
}	

// Display Message History
function message_history($msg_id, $user_id, &$message_row, $folder)
{
	global $config, $_CLASS, $bbcode;

	// Get History Messages (could be newer)
	$sql = 'SELECT t.*, p.*, u.*
		FROM ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . CORE_USERS_TABLE . ' u
		WHERE t.msg_id = p.msg_id 
			AND p.author_id = u.user_id
			AND t.folder_id NOT IN (' . PRIVMSGS_NO_BOX . ', ' . PRIVMSGS_HOLD_BOX . ")
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
	$sql .= ($sort_dir === 'd') ? 'ASC' : 'DESC';

	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);

	if (!$row)
	{
		$_CLASS['core_db']->free_result($result);
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
			$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['bbcode_bitfield']);
		}
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
	$_CLASS['core_db']->free_result($result);

	$title = ($sort_dir == 'a') ? $row['message_subject'] : $title;

	if (count($rowset) === 1)
	{
		return false;
	}
	
	// Instantiate BBCode class
	if ((empty($bbcode) || $bbcode === false) && $bbcode_bitfield !== '')
	{
		require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';
		$bbcode = new bbcode(base64_encode($bbcode_bitfield));
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
			$next_history_pm = empty($next_history_pm) ? 0 : (int) $next_history_pm['msg_id'];
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
			'U_AUTHOR_PROFILE' 	=> generate_link('members_list&amp;mode=viewprofile&amp;u='.$author_id),
			'U_QUOTE'			=> ($author_id != $_CLASS['core_user']->data['user_id']) ? generate_link("$url&amp;mode=compose&amp;action=quote&amp;f=" . $folder_id . "&amp;p=" . $row['msg_id']) : '',
			'U_POST_REPLY_PM' 	=> ($author_id != $_CLASS['core_user']->data['user_id']) ? generate_link("$url&amp;mode=compose&amp;action=reply&amp;f=$folder_id&amp;p=" . $row['msg_id']) : '')
		);
		unset($rowset[$id]);
		$prev_id = $id;
	}

	$_CLASS['core_template']->assign_array(array(
		'QUOTE_IMG' => $_CLASS['core_user']->img('btn_quote', $_CLASS['core_user']->lang['REPLY_WITH_QUOTE']),
		'TITLE'		=> $title,

		'U_VIEW_NEXT_HISTORY'		=> generate_link("$url&amp;p=" . (($next_history_pm) ? $next_history_pm : $msg_id)),
		'U_VIEW_PREVIOUS_HISTORY'	=> generate_link("$url&amp;p=" . (($previous_history_pm) ? $previous_history_pm : $msg_id)))
	);

	return true;
}

/**
* Get User Informations (only for message display)
*/
function get_user_informations($user_id, $user_row)
{
	global $config, $_CORE_CONFIG, $_CLASS;

	if (!$user_id)
	{
		return array();
	}

	if (empty($user_row))
	{
		$sql = 'SELECT *
			FROM ' . CORE_USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $_CLASS['core_db']->query($sql);
		$user_row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
	}

	// Grab ranks
	$ranks = obtain_ranks();

	// Generate online information for user
	
	$user_row['online'] = false;

	if ($config['load_onlinetrack'])
	{
		$sql = 'SELECT MAX(session_hidden) AS session_hidden
			FROM ' . CORE_SESSIONS_TABLE . " 
			WHERE session_user_id = $user_id
			AND session_time < " . ($_CLASS['core_user']->time - $_CORE_CONFIG['server']['session_length']);
		$result = $_CLASS['core_db']->query_limit($sql, 1);

		$update_time = $config['load_online_time'] * 60;

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$user_row['online'] = (!$row['session_hidden'] && $user_row['user_allow_viewonline']) ? true : false;
		}

		$_CLASS['core_db']->free_result($result);
	}

	if ($user_row['user_avatar'] && $_CLASS['core_user']->user_data_get('viewavatars'))
	{
		$avatar_img = '';

		switch ($user_row['user_avatar_type'])
		{
			case AVATAR_UPLOAD:
				$avatar_img = $_CORE_CONFIG['global']['path_avatar_upload'] . '/';
			break;

			case AVATAR_GALLERY:
				$avatar_img = $_CORE_CONFIG['global']['path_avatar_gallery'] . '/';
			break;
		}
		$avatar_img .= $user_row['user_avatar'];

		$user_row['avatar'] = '<img src="' . $avatar_img . '" width="' . $user_row['user_avatar_width'] . '" height="' . $user_row['user_avatar_height'] . '" border="0" alt="" />';
	}

	if (!empty($user_row['user_rank']))
	{
		$user_row['rank_title'] = empty($ranks['special'][$user_row['user_rank']]['rank_title']) ? '' : $ranks['special'][$user_row['user_rank']]['rank_title'];
		$user_row['rank_image'] = empty($ranks['special'][$user_row['user_rank']]['rank_image']) ? '' : '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$user_row['user_rank']]['rank_image'] . '" border="0" alt="' . $ranks['special'][$user_row['user_rank']]['rank_title'] . '" title="' . $ranks['special'][$user_row['user_rank']]['rank_title'] . '" /><br />';
	}
/*	elseif (isset($ranks['normal']))
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
*/
	else
	{
		$user_row['rank_title'] = $user_row['rank_image'] = '';
	}

	if (!empty($user_row['user_allow_viewemail']))
	{
		$user_row['email'] = ($config['board_email_form'] && $_CORE_CONFIG['email']['email_enable']) ? generate_link('members_list&amp;mode=email&amp;u='.$user_id) : (($config['board_hide_emails']) ? '' : 'mailto:' . $user_row['user_email']);
	}
	else
	{
		$user_row['email'] = '';
	}

	return $user_row;
}

?>