<?php
// -------------------------------------------------------------
//
// $Id: ucp_pm_compose.php,v 1.5 2004/06/02 18:07:40 acydburn Exp $
//
// FILENAME  : compose.php
// STARTED   : Sat Mar 27, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// * Called from ucp_pm with mode == 'compose'

function compose_pm($id, $mode, $action)
{
	global $_CLASS, $site_file_root, $config;
	
	if (!$action)
	{
		$action = 'post';
	}
	
	$_CLASS['core_template']->assign_array(array(
		'S_DISPLAY_FORM'				=> false,
		'S_DRAFT_LOADED'				=> false, 
		'S_SHOW_DRAFTS'					=> false,
		'S_POST_REVIEW'					=> false,
		'S_INLINE_ATTACHMENT_OPTIONS'	=> false,
		'S_EDIT_REASON'					=> false, 
		'S_HAS_ATTACHMENTS'				=> false,
		'to_recipient'					=> false,
		'bcc_recipient'					=> false, 
		'S_DISPLAY_HISTORY'				=> false,
		'S_DISPLAY_PREVIEW'				=> false)
	);

	// Grab only parameters needed here
	$to_user_id     = request_var('u', 0);
  	$to_group_id    = request_var('g', 0);
	$msg_id			= request_var('p', 0);
	$quote_post		= request_var('q', 0);
	$draft_id		= request_var('d', 0);
	$lastclick		= request_var('lastclick', 0);
	$message_text = $subject = '';

	// Do NOT use request_var or specialchars here
	$address_list	= isset($_REQUEST['address_list']) ? $_REQUEST['address_list'] : array();

	$submit		= isset($_POST['post']);
	$preview	= isset($_POST['preview']);
	$save		= isset($_POST['save']);
	$load		= isset($_POST['load']);
	$cancel		= isset($_POST['cancel']);
	$confirm	= isset($_POST['confirm']);
	$delete		= isset($_POST['delete']);

	$remove_u	= isset($_REQUEST['remove_u']);
	$remove_g	= isset($_REQUEST['remove_g']);
	$add_to		= isset($_REQUEST['add_to']);
	$add_bcc	= isset($_REQUEST['add_bcc']);

	$refresh	= isset($_POST['add_file']) || isset($_POST['delete_file']) || isset($_POST['edit_comment']) || $save || $load
		|| $remove_u || $remove_g || $add_to || $add_bcc;

	$action		= ($delete && !$preview && !$refresh && $submit) ? 'delete' : $action;

	$error = array();
	$current_time = gmtime();

	// Was cancel pressed? If so then redirect to the appropriate page
	if ($cancel || ($current_time - $lastclick < 2 && $submit))
	{
		$redirect = generate_link("Control_Panel&amp;i=$id&amp;mode=view_messages&amp;action=view_message" . (($msg_id) ? "&amp;p=$msg_id" : ''));
		redirect($redirect);
	}

	if ($action == 'forward' && (!$config['forward_pm'] || !$_CLASS['auth']->acl_get('u_pm_forward')))
	{
		trigger_error('NO_AUTH_FORWARD_MESSAGE');
	}

	if ($action == 'edit' && !$_CLASS['auth']->acl_get('u_pm_edit'))
	{
		trigger_error('NO_AUTH_EDIT_MESSAGE');
	}

	$sql = '';

	// What is all this following SQL for? Well, we need to know
	// some basic information in all cases before we do anything.
	switch ($action)
	{
		case 'post':
		
			if (!$_CLASS['auth']->acl_get('u_sendpm'))
			{
				trigger_error('NO_AUTH_SEND_MESSAGE');
			}
		break;
			
		case 'reply':
		case 'quote':
		case 'forward':
			if (!$msg_id)
			{
				trigger_error('NO_MESSAGE');
			}

			if (!$_CLASS['auth']->acl_get('u_sendpm'))
			{
				trigger_error('NO_AUTH_SEND_MESSAGE');
			}

			if ($quote_post)
			{
				$sql = 'SELECT p.post_text as message_text, p.poster_id as author_id, p.post_time as message_time, p.bbcode_bitfield, p.bbcode_uid, p.enable_sig, p.enable_html, p.enable_smilies, p.enable_magic_url, t.topic_title as message_subject, u.username as quote_username
					FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_TOPICS_TABLE . ' t, ' . USERS_TABLE . " u
					WHERE p.post_id = $msg_id
						AND t.topic_id = p.topic_id
						AND u.user_id = p.poster_id";
			}
			else
			{
				$sql = 'SELECT t.*, p.*, u.username as quote_username
					FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . USERS_TABLE . ' u
					WHERE t.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
						AND p.author_id = u.user_id
						AND t.msg_id = p.msg_id
						AND p.msg_id = $msg_id";
			}
		break;

		case 'edit':
			if (!$msg_id)
			{
				trigger_error('NO_MESSAGE');
			}

			// check for outbox (not read) status, we do not allow editing if one user already having the message
			$sql = 'SELECT p.*, t.*
				FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p
				WHERE t.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
					AND t.folder_id = ' . PRIVMSGS_OUTBOX . "
					AND t.msg_id = $msg_id
					AND t.msg_id = p.msg_id";
		break;

		case 'delete':
			if (!$_CLASS['auth']->acl_get('u_pm_delete'))
			{
				trigger_error('NO_AUTH_DELETE_MESSAGE');
			}
		
			if (!$msg_id)
			{
				trigger_error('NO_MESSAGE');
			}

			$sql = 'SELECT msg_id, unread, new, author_id, folder_id
				FROM ' . FORUMS_PRIVMSGS_TO_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . "
					AND msg_id = $msg_id";
			break;

		case 'smilies':
			require_once($site_file_root.'includes/forums/functions_posting.php');
	
			generate_smilies('window', 0);
	
			script_close(false);
		break;

		default:
			trigger_error('NO_ACTION_MODE');
		break;
	}

	if ($sql)
	{
		$result = $_CLASS['core_db']->query_limit($sql, 1);

		if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error('NO_MESSAGE');
		}

		extract($row);
		$_CLASS['core_db']->free_result($result);
		
		$msg_id = (int) $msg_id;
		$enable_urls = $enable_magic_url;

		if (!$author_id && $msg_id)
		{
			trigger_error('NO_AUTHOR');
		}

		if (($action == 'reply' || $action == 'quote') && empty($address_list) && !$refresh && !$submit && !$preview)
		{
			$address_list = array('u' => array($author_id => 'to'));
		}
		elseif ($action == 'edit' && empty($address_list) && !$refresh && !$submit && !$preview)
		{
			// Rebuild TO and BCC Header
			$address_list = rebuild_header(array('to' => $to_address, 'bcc' => $bcc_address));
		}
		$check_value = (($enable_html+1) << 16) + (($enable_bbcode+1) << 8) + (($enable_smilies+1) << 4) + (($enable_urls+1) << 2) + (($enable_sig+1) << 1);
	}
	else
	{
		$message_attachment = 0;

		if ($to_user_id && $action == 'post')
		{
			$address_list['u'][$to_user_id] = 'to';
		}
		else if ($to_group_id && $action == 'post')
		{
			$address_list['g'][$to_group_id] = 'to';
		}
		$check_value = 0;
	}
	
	if (($to_group_id || isset($address_list['g'])) && !$config['allow_mass_pm'])
	{
		trigger_error('NO_AUTH_GROUP_MESSAGE');
	}

	if ($action == 'edit' && !$refresh && !$preview && !$submit)
	{
		if (!($message_time > time() - $config['pm_edit_time'] || !$config['pm_edit_time']))
		{
			trigger_error('CANNOT_EDIT_MESSAGE_TIME');
		}
	}

	if (!isset($icon_id))
	{
		$icon_id = 0;
	}

	require_once($site_file_root.'includes/forums/functions_admin.php');
	require_once($site_file_root.'includes/forums/functions_posting.php');
	require_once($site_file_root.'includes/forums/message_parser.php');

	$message_parser = new parse_message();

	$message_subject = isset($message_subject) ? $message_subject : '';
	$message_parser->message = ($action == 'reply') ? '' : (isset($message_text) ? $message_text : '');
	unset($message_text);

	$s_action = "Control_Panel&amp;i=$id&amp;mode=$mode&amp;action=$action";
	$s_action .= ($msg_id) ? "&amp;p=$msg_id" : '';
	$s_action .= ($quote_post) ? "&amp;q=1" : '';

	// Delete triggered ?
	if ($action == 'delete')
	{
		// Folder id has been determined by the SQL Statement
		// $folder_id = request_var('f', PRIVMSGS_NO_BOX);

		$s_hidden_fields = '<input type="hidden" name="p" value="' . $msg_id . '" /><input type="hidden" name="f" value="' . $folder_id . '" /><input type="hidden" name="action" value="delete" />';

		// Do we need to confirm ?
		if (confirm_box(true))
		{
			delete_pm($_CLASS['core_user']->data['user_id'], $msg_id, $folder_id);
						
			// TODO - jump to next message in "history"?
			$meta_info = generate_link('Control_Panel&amp;i=pm&amp;folder='.$folder_id);
			$message = $_CLASS['core_user']->lang['MESSAGE_DELETED'];

			meta_refresh(3, $meta_info);
			$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FOLDER'], '<a href="' . $meta_info . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			confirm_box(false, 'DELETE_MESSAGE', $s_hidden_fields);
		}
	}

	// Handle User/Group adding/removing
	handle_message_list_actions($address_list, $remove_u, $remove_g, $add_to, $add_bcc);

	// Check for too many recipients
	if (!$config['allow_mass_pm'] && num_recipients($address_list) > 1)
	{
		$address_list = get_recipient_pos($address_list, 1);
		$error[] = $_CLASS['core_user']->lang['TOO_MANY_RECIPIENTS'];
	}

	$message_parser->get_submitted_attachment_data();

	if ($message_attachment && !$submit && !$refresh && !$preview && $action == 'edit')
	{
		$sql = 'SELECT attach_id, physical_filename, comment, real_filename, extension, mimetype, filesize, filetime, thumbnail
			FROM ' . FORUMS_ATTACHMENTS_TABLE . "
			WHERE post_msg_id = $msg_id
				AND in_message = 1
				ORDER BY filetime " . ((!$config['display_order']) ? 'DESC' : 'ASC');
		$result = $_CLASS['core_db']->query($sql);

		$message_parser->attachment_data = array_merge($message_parser->attachment_data, $_CLASS['core_db']->fetch_row_assocset($result));
		
		$_CLASS['core_db']->free_result($result);
	}
	
	if (!in_array($action, array('quote', 'edit', 'delete', 'forward')))
	{
		$enable_sig		= ($config['allow_sig'] && $_CLASS['auth']->acl_get('u_sig') && $_CLASS['core_user']->optionget('attachsig'));
		$enable_smilies = ($config['allow_smilies'] && $_CLASS['auth']->acl_get('u_pm_smilies') && $_CLASS['core_user']->optionget('smilies'));
		$enable_bbcode	= ($config['allow_bbcode'] && $_CLASS['auth']->acl_get('u_pm_bbcode') && $_CLASS['core_user']->optionget('bbcode'));
		$enable_urls	= true;
	}

	$enable_magic_url = $drafts = false;

	// User own some drafts?
	if ($_CLASS['auth']->acl_get('u_savedrafts') && $action != 'delete')
	{
		$sql = 'SELECT draft_id
			FROM ' . FORUMS_DRAFTS_TABLE . '
			WHERE (forum_id = 0 AND topic_id = 0)
				AND user_id = ' . $_CLASS['core_user']->data['user_id'] . 
				(($draft_id) ? " AND draft_id <> $draft_id" : '');
		$result = $_CLASS['core_db']->query_limit($sql, 1);

		if ($_CLASS['core_db']->fetch_row_assoc($result))
		{
			$drafts = true;
		}
		$_CLASS['core_db']->free_result($result);
	}

	if ($action == 'edit' || $action == 'forward')
	{
		$message_parser->bbcode_uid = $bbcode_uid;
	}

$config['auth_bbcode_pm'] = true;
	$html_status	= ($config['allow_html'] && $config['auth_html_pm'] && $_CLASS['auth']->acl_get('u_pm_html'));
	$bbcode_status	= ($config['allow_bbcode'] && $config['auth_bbcode_pm'] && $_CLASS['auth']->acl_get('u_pm_bbcode'));
	$smilies_status	= ($config['allow_smilies'] && $config['auth_smilies_pm'] && $_CLASS['auth']->acl_get('u_pm_smilies'));
	$img_status		= ($config['auth_img_pm'] && $_CLASS['auth']->acl_get('u_pm_img'));
	$flash_status	= ($config['auth_flash_pm'] && $_CLASS['auth']->acl_get('u_pm_flash'));

	// Save Draft
	if ($save && $_CLASS['auth']->acl_get('u_savedrafts'))
	{
		$subject = request_var('subject', '', true);
		$subject = (!$subject && $action != 'post') ? $_CLASS['core_user']->lang['NEW_MESSAGE'] : $subject;
		$message = request_var('message', '', true);

		if ($subject && $message)
		{
			$sql = 'INSERT INTO ' . FORUMS_DRAFTS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
				'user_id'	=> $_CLASS['core_user']->data['user_id'],
				'topic_id'	=> 0,
				'forum_id'	=> 0,
				'save_time'	=> $current_time,
				'draft_subject' => $subject,
				'draft_message' => $message));
			$_CLASS['core_db']->query($sql);
	
			$_CLASS['core_display']->meta_refresh(3, generate_link('Control_Panel&i=pm&mode='.$mode));

			$message = $_CLASS['core_user']->lang['DRAFT_SAVED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link('Control_Panel&amp;i=pm&amp;mode='.$mode).'">', '</a>');

			trigger_error($message);
		}

		unset($subject);
		unset($message);
	}

	// Load Draft
	if ($draft_id && $_CLASS['auth']->acl_get('u_savedrafts'))
	{
		$sql = 'SELECT draft_subject, draft_message 
			FROM ' . FORUMS_DRAFTS_TABLE . " 
			WHERE draft_id = $draft_id
				AND topic_id = 0
				AND forum_id = 0
				AND user_id = " . $_CLASS['core_user']->data['user_id'];
		$result = $_CLASS['core_db']->query_limit($sql, 1);
	
		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$_REQUEST['subject'] = $row['draft_subject'];
			$_REQUEST['message'] = $row['draft_message'];
			$refresh = true;
			$_CLASS['core_template']->assign('S_DRAFT_LOADED', true);
		}
		else
		{
			$draft_id = 0;
		}
	}

	// Load Drafts
	if ($load && $drafts)
	{
		load_drafts(0, 0, $id);
	}

	if ($submit || $preview || $refresh)
	{
		$subject = mb_strtolower(get_variable('subject', 'POST', ''));

		$message_parser->message = request_var('message', '', true);

		$icon_id			= request_var('icon', 0);

		$enable_html 		= (!$html_status || isset($_POST['disable_html'])) ? false : true;
		$enable_bbcode 		= (!$bbcode_status || isset($_POST['disable_bbcode'])) ? false : true;
		$enable_smilies		= (!$smilies_status || isset($_POST['disable_smilies'])) ? false : true;
		$enable_urls 		= (isset($_POST['disable_magic_url'])) ? 0 : 1;
		$enable_sig			= (!$config['allow_sig']) ? false : ((isset($_POST['attach_sig'])) ? true : false);

		if ($submit)
		{
			$status_switch  = (($enable_html+1) << 16) + (($enable_bbcode+1) << 8) + (($enable_smilies+1) << 4) + (($enable_urls+1) << 2) + (($enable_sig+1) << 1);
			$status_switch = ($status_switch != $check_value);
		}
		else
		{
			$status_switch = 1;
		}

		// Parse Attachments - before checksum is calculated
		$message_parser->parse_attachments('fileupload', $action, 0, $submit, $preview, $refresh, true);
		
		// Grab md5 'checksum' of new message
		$message_md5 = md5($message_parser->message);

		// Check checksum ... don't re-parse message if the same
		$update_message = ($action != 'edit' || $message_md5 != $post_checksum || $status_switch || $preview) ? true : false;

		if ($update_message)
		{
			$message_parser->parse($enable_html, $enable_bbcode, $enable_urls, $enable_smilies, $img_status, $flash_status, true);
		}
		else
		{
			$message_parser->bbcode_bitfield = $bbcode_bitfield;
		}

		if ($action != 'edit' && !$preview && !$refresh && $config['flood_interval'] && !$_CLASS['auth']->acl_get('u_ignoreflood'))
		{
			// Flood check
			$last_post_time = $_CLASS['core_user']->data['user_last_post_time'];

			if ($last_post_time)
			{
				if ($last_post_time && ($current_time - $last_post_time) < intval($config['flood_interval']))
				{
					$error[] = $_CLASS['core_user']->lang['FLOOD_ERROR'];
				}
			}
		}

		// Subject defined
		if (!$subject && !($remove_u || $remove_g || $add_to || $add_bcc))
		{
			$error[] = $_CLASS['core_user']->lang['EMPTY_SUBJECT'];
		}

		if (empty($address_list))
		{
			$error[] = $_CLASS['core_user']->lang['NO_RECIPIENT'];
		}

		if (!empty($message_parser->warn_msg) && !($remove_u || $remove_g || $add_to || $add_bcc))
		{
			$error[] = implode('<br />', $message_parser->warn_msg);
		}

		// Store message, sync counters
		if (empty($error) && $submit)
		{
			$pm_data = array(
				'msg_id'				=> (int) $msg_id,
				'reply_from_root_level'	=> (isset($root_level)) ? (int) $root_level : 0,
				'reply_from_msg_id'		=> (int) $msg_id,
				'icon_id'				=> (int) $icon_id,
				'enable_sig'			=> (bool) $enable_sig,
				'enable_bbcode'			=> (bool) $enable_bbcode,
				'enable_html' 			=> (bool) $enable_html,
				'enable_smilies'		=> (bool) $enable_smilies,
				'enable_urls'			=> (bool) $enable_urls,
				'message_md5'			=> (int) $message_md5,
				'bbcode_bitfield'		=> (int) $message_parser->bbcode_bitfield,
				'bbcode_uid'			=> $message_parser->bbcode_uid,
				'message'				=> $message_parser->message,
				'attachment_data'		=> $message_parser->attachment_data,
				'filename_data'			=> $message_parser->filename_data,
				'address_list'			=> $address_list
			);
			unset($message_parser);
			
			// ((!$message_subject) ? $subject : $message_subject)
			$msg_id = submit_pm($action, $subject, $pm_data, $update_message);

			$return_message_url = generate_link('Control_Panel&amp;i=pm&amp;mode=view_messages&amp;action=view_message&amp;p=' . $msg_id);
			$return_folder_url = generate_link('Control_Panel&amp;i=pm&amp;folder=outbox');
			$_CLASS['core_display']->meta_refresh(3, $return_message_url);

			$message = $_CLASS['core_user']->lang['MESSAGE_STORED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['VIEW_MESSAGE'], '<a href="' . $return_message_url . '">', '</a>') . '<br /><br />' . sprintf($_CLASS['core_user']->lang['CLICK_RETURN_FOLDER'], '<a href="' . $return_folder_url . '">', '</a>', $_CLASS['core_user']->lang['PM_OUTBOX']);
			trigger_error($message);
		}

		$message_subject = stripslashes($subject);
	}

	if (empty($error) && $preview)
	{
		$post_time = ($action == 'edit') ? $post_time : $current_time;

		$preview_message = $message_parser->format_display($enable_html, $enable_bbcode, $enable_urls, $enable_smilies, false);

		$preview_signature = $_CLASS['core_user']->data['user_sig'];
		$preview_signature_uid = $_CLASS['core_user']->data['user_sig_bbcode_uid'];
		$preview_signature_bitfield = $_CLASS['core_user']->data['user_sig_bbcode_bitfield'];

		// Signature
		if ($enable_sig && $config['allow_sig'] && $preview_signature)
		{
			$parse_sig = new parse_message($preview_signature);
			$parse_sig->bbcode_uid = $preview_signature_uid;
			$parse_sig->bbcode_bitfield = $preview_signature_bitfield;

			$parse_sig->format_display($enable_html, $enable_bbcode, $enable_urls, $enable_smilies);
			$preview_signature = $parse_sig->message;
			unset($parse_sig);
		}
		else
		{
			$preview_signature = '';
		}

		// Attachment Preview
		if (!empty($message_parser->attachment_data))
		{
			require($site_file_root.'includes/forums/functions_display.php');
			$extensions = $update_count = array();
					
			$_CLASS['core_template']->assign('S_HAS_ATTACHMENTS', true);
			display_attachments(0, 'attachment', $message_parser->attachment_data, $update_count, true);
		}

		$preview_subject = censor_text($subject);

		if (empty($error))
		{
			$_CLASS['core_template']->assign_array(array(
				'POST_DATE'				=> $_CLASS['core_user']->format_date($post_time),
			
				'PREVIEW_SUBJECT'		=> $preview_subject,
				'PREVIEW_MESSAGE'		=> $preview_message, 
				'PREVIEW_SIGNATURE'		=> $preview_signature, 
				'S_DISPLAY_PREVIEW'		=> true)
				);				
		}
		unset($message_text);
	}

	// Decode text for message display
	$bbcode_uid = (($action == 'quote' || $action == 'forward')&& !$preview && !$refresh && empty($error)) ? $bbcode_uid : $message_parser->bbcode_uid;

	$message_parser->decode_message($bbcode_uid);

	if ($action == 'quote' && !$preview && !$refresh)
	{
		$message_parser->message = '[quote="' . $quote_username . '"]' . censor_text(trim($message_parser->message)) . "[/quote]\n";
	}
	
	if (($action == 'reply' || $action == 'quote') && !$preview && !$refresh)
	{
		$message_subject = ((!preg_match('/^Re:/', $message_subject)) ? 'Re: ' : '') . censor_text($message_subject);
	}

	if ($action == 'forward' && !$preview && !$refresh)
	{
		$fwd_to_field = write_pm_addresses(array('to' => $to_address), 0, true);

		$forward_text = array();
		$forward_text[] = $_CLASS['core_user']->lang['FWD_ORIGINAL_MESSAGE'];
		$forward_text[] = sprintf($_CLASS['core_user']->lang['FWD_SUBJECT'], censor_text($message_subject));
		$forward_text[] = sprintf($_CLASS['core_user']->lang['FWD_DATE'], $_CLASS['core_user']->format_date($message_time));
		$forward_text[] = sprintf($_CLASS['core_user']->lang['FWD_FROM'], $quote_username);
		$forward_text[] = sprintf($_CLASS['core_user']->lang['FWD_TO'], implode(', ', $fwd_to_field['to']));

		$message_parser->message = implode("\n", $forward_text) . "\n\n[quote=\"[url=" . generate_link("Members_List&mode=viewprofile&u={$author_id}]{$quote_username}")."[/url]\"]\n" . censor_text(trim($message_parser->message)) . "\n[/quote]";
		$message_subject = ((!preg_match('/^Fwd:/', $message_subject)) ? 'Fwd: ' : '') . censor_text($message_subject);
	}

	$attachment_data = $message_parser->attachment_data;
	$filename_data = $message_parser->filename_data;
	$message_text = $message_parser->message;
	unset($message_parser);

	// MAIN PM PAGE BEGINS HERE

	// Generate smiley listing
	generate_smilies('inline', 0);

	// Generate PM Icons
	$s_pm_icons = false;
	if ($config['enable_pm_icons'])
	{
		$s_pm_icons = posting_gen_topic_icons($action, $icon_id);
	}
	
	// Generate inline attachment select box
	posting_gen_inline_attachments($attachment_data);

	// Build address list for display
	// array('u' => array($author_id => 'to'));
	if (!empty($address_list))
	{
		// Get Usernames and Group Names
		$result = array();
		if (isset($address_list['u']) && !empty($address_list['u']))
		{
			$result['u'] = $_CLASS['core_db']->query('SELECT user_id as id, username as name, user_colour as colour 
				FROM ' . USERS_TABLE . ' 
				WHERE user_id IN (' . implode(', ', array_map('intval', array_keys($address_list['u']))) . ')');
		}
		
		if (isset($address_list['g']) && !empty($address_list['g']))
		{
			$result['g'] = $_CLASS['core_db']->query('SELECT group_id as id, group_name as name, group_colour as colour 
				FROM ' . GROUPS_TABLE . ' 
				WHERE group_receive_pm = 1 AND group_id IN (' . implode(', ', array_map('intval', array_keys($address_list['g']))) . ')');
		}

		$u = $g = array();
		foreach (array('u', 'g') as $type)
		{
			if (isset($result[$type]) && $result[$type])
			{
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result[$type]))
				{
					${$type}[$row['id']] = array('name' => $row['name'], 'colour' => $row['colour']);
				}
				$_CLASS['core_db']->free_result($result[$type]);
			}
		}

		// Now Build the address list
		$plain_address_field = '';
		foreach ($address_list as $type => $adr_ary)
		{
			foreach ($adr_ary as $id => $field)
			{
				if (!isset(${$type}[$id]))
				{
					unset($address_list[$type][$id]);
					continue;
				}
			
				$field = ($field == 'to') ? 'to' : 'bcc';
				$type = ($type == 'u') ? 'u' : 'g';
				$id = (int) $id;
				
				$_CLASS['core_template']->assign_vars_array($field . '_recipient', array(
					'NAME'		=> ${$type}[$id]['name'],
					'IS_GROUP'	=> ($type == 'g'),
					'IS_USER'	=> ($type == 'u'),
					'COLOUR'	=> (${$type}[$id]['colour']) ? ${$type}[$id]['colour'] : '',
					'UG_ID'		=> $id,
					'U_VIEW'	=> ($type == 'u') ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $id) : generate_link('Members_List&amp;mode=group&amp;g=' . $id),
					'TYPE'		=> $type)
				);
			}
		}
	}

	// Build hidden address list
	$s_hidden_address_field = '';
	foreach ($address_list as $type => $adr_ary)
	{
		foreach ($adr_ary as $id => $field)
		{
			$s_hidden_address_field .= '<input type="hidden" name="address_list[' . (($type == 'u') ? 'u' : 'g') . '][' . (int) $id . ']" value="' . (($field == 'to') ? 'to' : 'bcc') . '" />';
		}
	}

	$html_checked		= (isset($enable_html)) ? !$enable_html : (($config['allow_html'] && $_CLASS['auth']->acl_get('u_pm_html')) ? !$_CLASS['core_user']->optionget('html') : 1);
	$bbcode_checked		= (isset($enable_bbcode)) ? !$enable_bbcode : (($config['allow_bbcode'] && $_CLASS['auth']->acl_get('u_pm_bbcode')) ? !$_CLASS['core_user']->optionget('bbcode') : 1);
	$smilies_checked	= (isset($enable_smilies)) ? !$enable_smilies : (($config['allow_smilies'] && $_CLASS['auth']->acl_get('u_pm_smilies')) ? !$_CLASS['core_user']->optionget('smilies') : 1);
	$urls_checked		= (isset($enable_urls)) ? !$enable_urls : 0;
	$sig_checked		= $enable_sig;

	switch ($action)
	{
		case 'post':
			$page_title = $_CLASS['core_user']->lang['POST_NEW_PM'];
			break;

		case 'quote':
			$page_title = $_CLASS['core_user']->lang['POST_QUOTE_PM'];
			break;

		case 'reply':
			$page_title = $_CLASS['core_user']->lang['POST_REPLY_PM'];
			break;

		case 'edit':
			$page_title = $_CLASS['core_user']->lang['POST_EDIT_PM'];
			break;

		case 'forward':
			$page_title = $_CLASS['core_user']->lang['POST_FORWARD_PM'];
			break;

		default:
			trigger_error('NO_ACTION_MODE');
	}

	$s_hidden_fields = '<input type="hidden" name="lastclick" value="' . $current_time . '" />';
	$s_hidden_fields .= (isset($check_value)) ? '<input type="hidden" name="status_switch" value="' . $check_value . '" />' : '';
	$s_hidden_fields .= ($draft_id || isset($_REQUEST['draft_loaded'])) ? '<input type="hidden" name="draft_loaded" value="' . ((isset($_REQUEST['draft_loaded'])) ? intval($_REQUEST['draft_loaded']) : $draft_id) . '" />' : '';

	$form_enctype = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off' || @ini_get('file_uploads') == '0' || !$config['allow_pm_attach'] || !$_CLASS['auth']->acl_get('u_pm_attach')) ? '' : ' enctype="multipart/form-data"';

	// Start assigning vars for main posting page ...
	$_CLASS['core_template']->assign_array(array(
		'L_POST_A'					=> $page_title,
		'L_ICON'					=> $_CLASS['core_user']->lang['PM_ICON'], 
		'L_MESSAGE_BODY_EXPLAIN'	=> (intval($config['max_post_chars'])) ? sprintf($_CLASS['core_user']->lang['MESSAGE_BODY_EXPLAIN'], intval($config['max_post_chars'])) : '',
						
		'SUBJECT'				=> (isset($message_subject)) ? $message_subject : '',
		'MESSAGE'				=> $message_text,
		'HTML_STATUS'			=> ($html_status) ? $_CLASS['core_user']->lang['HTML_IS_ON'] : $_CLASS['core_user']->lang['HTML_IS_OFF'],
		'BBCODE_STATUS'			=> ($bbcode_status) ? sprintf($_CLASS['core_user']->lang['BBCODE_IS_ON'], '<a href="' . generate_link('Forums&amp;file=faq&amp;mode=bbcode') . '" target="_phpbbcode">', '</a>') : sprintf($_CLASS['core_user']->lang['BBCODE_IS_OFF'], '<a href="' . generate_link('Forums&amp;file=faq&amp;mode=bbcode') . '" target="_phpbbcode">', '</a>'),
		'IMG_STATUS'			=> ($img_status) ? $_CLASS['core_user']->lang['IMAGES_ARE_ON'] : $_CLASS['core_user']->lang['IMAGES_ARE_OFF'],
		'FLASH_STATUS'			=> ($flash_status) ? $_CLASS['core_user']->lang['FLASH_IS_ON'] : $_CLASS['core_user']->lang['FLASH_IS_OFF'],
		'SMILIES_STATUS'		=> ($smilies_status) ? $_CLASS['core_user']->lang['SMILIES_ARE_ON'] : $_CLASS['core_user']->lang['SMILIES_ARE_OFF'],
		'MINI_POST_IMG'			=> $_CLASS['core_user']->img('icon_post', $_CLASS['core_user']->lang['PM']),
		'ERROR'					=> empty($error) ? '' : implode('<br />', $error), 

		'S_EDIT_POST'			=> ($action == 'edit'),
		'S_SHOW_PM_ICONS'		=> $s_pm_icons,
		'S_HTML_ALLOWED'		=> $html_status,
		'S_HTML_CHECKED' 		=> ($html_checked) ? ' checked="checked"' : '',
		'S_BBCODE_ALLOWED'		=> $bbcode_status,
		'S_BBCODE_CHECKED' 		=> ($bbcode_checked) ? ' checked="checked"' : '',
		'S_SMILIES_ALLOWED'		=> $smilies_status,
		'S_SMILIES_CHECKED' 	=> ($smilies_checked) ? ' checked="checked"' : '',
		'S_SIG_ALLOWED'			=> ($config['allow_sig'] && $_CLASS['auth']->acl_get('u_sig')),
		'S_SIGNATURE_CHECKED' 	=> ($sig_checked) ? ' checked="checked"' : '',
		'S_MAGIC_URL_CHECKED' 	=> ($urls_checked) ? ' checked="checked"' : '',
		'S_SAVE_ALLOWED'		=> $_CLASS['auth']->acl_get('u_savedrafts'),
		'S_HAS_DRAFTS'			=> ($_CLASS['auth']->acl_get('u_savedrafts') && $drafts),
		'S_FORM_ENCTYPE'		=> $form_enctype,

		'S_POST_ACTION' 		=> generate_link($s_action),
		'S_HIDDEN_ADDRESS_FIELD'=> $s_hidden_address_field,
		'S_HIDDEN_FIELDS'		=> $s_hidden_fields)
	);

	// Attachment entry
	if ($_CLASS['auth']->acl_get('u_pm_attach') && $config['allow_pm_attach'] && $form_enctype)
	{
		posting_gen_attachment_entry($attachment_data, $filename_data);
	}
}

// For composing messages, handle list actions
function handle_message_list_actions(&$address_list, $remove_u, $remove_g, $add_to, $add_bcc)
{
	global $_REQUEST, $site_file_root;

	// Delete User [TO/BCC]
	if ($remove_u)
	{
		$remove_user_id = array_keys($_REQUEST['remove_u']);
		unset($address_list['u'][(int) $remove_user_id[0]]);
	}

	// Delete Group [TO/BCC]
	if ($remove_g)
	{
		$remove_group_id = array_keys($_REQUEST['remove_g']);
		unset($address_list['g'][(int) $remove_group_id[0]]);
	}

	// Add User/Group [TO]
	if ($add_to || $add_bcc)
	{
		$type = ($add_to) ? 'to' : 'bcc';

		// Add Selected Groups
		$group_list = isset($_REQUEST['group_list']) ? array_map('intval', $_REQUEST['group_list']) : array();

		if (!empty($group_list))
		{
			foreach ($group_list as $group_id)
			{
				$address_list['g'][$group_id] = $type;
			}
		}
		
		// Build usernames to add
		$usernames = (isset($_REQUEST['username'])) ? array(request_var('username', '')) : array();
		$username_list = request_var('username_list', '');
		if ($username_list)
		{
			$usernames = array_merge($usernames, explode("\n", $username_list));
		}

		// Reveal the correct user_ids
		if (!empty($usernames))
		{
			require_once($site_file_root.'includes/functions_user.php');

			$user_id_ary = user_get_id($usernames, $difference);
			
			if (!empty($user_id_ary))
			{
				foreach ($user_id_ary as $user_id)
				{
					$address_list['u'][$user_id] = $type;
				}
			}
		}

		// Add Friends if specified
		$friend_list = (is_array($_REQUEST['add_' . $type])) ? array_map('intval', array_keys($_REQUEST['add_' . $type])) : array();

		foreach ($friend_list as $user_id)
		{
			$address_list['u'][$user_id] = $type;
		}
	}

}

// Return number of recipients
function num_recipients($address_list)
{
	$num_recipients = 0;

	foreach ($address_list as $field => $adr_ary)
	{
		$num_recipients += sizeof($adr_ary);
	}

	return $num_recipients;
}

// Get recipient at position 'pos'
function get_recipient_pos($address_list, $position = 1)
{
	$recipient = array();

	$count = 1;
	foreach ($address_list as $field => $adr_ary)
	{
		foreach ($adr_ary as $id => $type)
		{
			if ($count == $position)
			{
				$recipient[$field][$id] = $type;
				break 2;
			}
			$count++;
		}
	}

	return $recipient;
}

?>