<?php
// -------------------------------------------------------------
//
// $Id: ucp_pm.php,v 1.6 2004/09/01 19:29:02 acydburn Exp $
//
// FILENAME  : ucp_pm.php
// STARTED   : Sat Mar 27, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

/**
* @package ucp
* ucp_pm
*
* Private Message Class
*
* @param int $folder display folder with the id used
* @param inbox|outbox|sentbox display folder with the associated name
*
*
*	Display Unread Messages - mode=unread
*	Display Messages (default to inbox) - mode=view_messages
*	Display single message - mode=view_messages&action=view_message&p=[msg_id] or &p=[msg_id] (short linkage)
*
*	if the folder id with (&f=[folder_id]) is used when displaying messages, one query will be saved. If it is not used, phpBB needs to grab
*	the folder id first in order to display the input boxes and folder names and such things. ;) phpBB always checks this against the database to make
*	sure the user is able to view the message.
*
*	Composing Messages (mode=compose):
*		To specific user (u=[user_id])
*		To specific group (g=[group_id])
*		Quoting a post (action=quote&q=1&p=[post_id])
*		Quoting a PM (action=quote&p=[msg_id])
*		Forwarding a PM (action=forward&p=[msg_id])
*
*
* @todo Review of post when replying/quoting
* @todo Report PM
* @todo Check Permissions (compose message - to user/group)
*
*/

class ucp_pm extends module
{
	function ucp_pm($id, $mode)
	{
		global $_CLASS, $site_file_root, $config;

		$action = '';
		
		if ($_CLASS['core_user']->data['user_id'] == ANONYMOUS)
		{
			trigger_error('NO_MESSAGE');
		}

		// Is PM disabled?
		if (!$config['allow_privmsg'])
		{
			trigger_error('PM_DISABLED');
		}

// This is loaded 2x with drafts
		$_CLASS['core_user']->add_lang('posting','Forums');
		
		$_CLASS['core_template']->assign('S_PRIVMSGS', true);

		// Folder directly specified?
		$folder_specified = request_var('folder', '');
		
		if (!in_array($folder_specified, array('inbox', 'outbox', 'sentbox')))
		{
			$folder_specified = (int) $folder_specified;
		}
		else
		{
			$folder_specified = ($folder_specified == 'inbox') ? PRIVMSGS_INBOX : (($folder_specified == 'outbox') ? PRIVMSGS_OUTBOX : PRIVMSGS_SENTBOX);
		}

		if (!$folder_specified)
		{
			$mode = (!$mode) ? request_var('mode', 'view_messages') : $mode;
		}
		else
		{
			$mode = 'view_messages';
		}

		require($site_file_root.'includes/forums/functions_privmsgs.php');
		
		$tpl_file = 'ucp_pm_' . $mode . '.html';
		switch ($mode)
		{
			// New private messages popup
			case 'popup':
			
				$indox_link = generate_link('Control_Panel&amp;i=pm&amp;folder=inbox');
				
				if ($_CLASS['core_user']->data['user_id'] != ANONYMOUS)
				{
					if ($_CLASS['core_user']->data['user_new_privmsg'])
					{
						$l_new_message = ($_CLASS['core_user']->data['user_new_privmsg'] == 1 ) ? $_CLASS['core_user']->lang['YOU_NEW_PM'] : $_CLASS['core_user']->lang['YOU_NEW_PMS'];
					}
					else
					{
						$l_new_message = $_CLASS['core_user']->lang['YOU_NO_NEW_PM'];
					}
				}

				$_CLASS['core_template']->assign(array(
					'MESSAGE'			=> $l_new_message,
					'U_JS_RETURN_INBOX'	=> $indox_link,
					'S_NOT_LOGGED_IN'	=> ($_CLASS['core_user']->data['user_id'] == ANONYMOUS) ? true : false,
					'CLICK_TO_VIEW'		=> sprintf($_CLASS['core_user']->lang['CLICK_VIEW_PRIVMSG'], '<a href="' . $indox_link . '" onclick="jump_to_inbox();return false;" target="_new">', '</a>'),
					'U_INBOX'			=> $indox_link
				));

				break;
			
			// Compose message
			case 'compose':
				$action = request_var('action', 'post');
	
				get_folder($_CLASS['core_user']->data['user_id'], $folder);
				
				if (!$_CLASS['auth']->acl_get('u_sendpm'))
				{
					trigger_error('NO_AUTH_SEND_MESSAGE');
				}

				require($site_file_root.'modules/Control_Panel/ucp/ucp_pm_compose.php');
				compose_pm($id, $mode, $action);
			
				$tpl_file = 'ucp_posting_body.html';
				break;
			
			case 'options':
				$sql = 'SELECT group_message_limit
					FROM ' . GROUPS_TABLE . '
					WHERE group_id = ' . $_CLASS['core_user']->data['user_group'];
				$result = $_CLASS['core_db']->query($sql);

				list($message_limit) = $_CLASS['core_db']->fetch_row_num($result);

				$_CLASS['core_db']->free_result($result);
				
				(int) $_CLASS['core_user']->data['message_limit'] = (!$message_limit) ? $config['pm_max_msgs'] : $message_limit;
				
				get_folder($_CLASS['core_user']->data['user_id'], $folder);

				require($site_file_root.'modules/Control_Panel/ucp/ucp_pm_options.php');
				message_options($id, $mode, $global_privmsgs_rules, $global_rule_conditions);
				break;

			case 'drafts':
				get_folder($_CLASS['core_user']->data['user_id'], $folder);
			
				require($site_file_root.'modules/Control_Panel/ucp/ucp_main.php');
				$module = new ucp_main($id, $mode);
				unset($module);
				exit;
			break;

			case 'unread':
			case 'view_messages':
			
				$sql = 'SELECT group_message_limit
					FROM ' . GROUPS_TABLE . '
					WHERE group_id = ' . $_CLASS['core_user']->data['user_group'];
				$result = $_CLASS['core_db']->query($sql);
				list($message_limit) = $_CLASS['core_db']->fetch_row_num($result);
				$_CLASS['core_db']->free_result($result);
				
				$_CLASS['core_user']->data['message_limit'] = (!$message_limit) ? $config['pm_max_msgs'] : $message_limit;
				
				if ($folder_specified)
				{
					$folder_id = $folder_specified;
					$action = 'view_folder';
				}
				else
				{
					$folder_id = request_var('f', PRIVMSGS_INBOX);
					$action = request_var('action', 'view_folder');
				}

				$msg_id = request_var('p', 0);
				$view	= request_var('view', '');
				
				if ($msg_id && $action == 'view_folder')
				{
					$action = 'view_message';
				}

				if (!$_CLASS['auth']->acl_get('u_readpm'))
				{
					trigger_error('NO_AUTH_READ_MESSAGE');
				}

// First Handle Mark actions and moving messages

				// Move PM
				if (isset($_REQUEST['move_pm']))
				{
					$move_msg_ids	= (isset($_POST['marked_msg_id'])) ? array_map('intval', $_POST['marked_msg_id']) : array();
					$dest_folder	= request_var('dest_folder', PRIVMSGS_NO_BOX);
					$cur_folder_id	= request_var('cur_folder_id', PRIVMSGS_NO_BOX);

					if (move_pm($_CLASS['core_user']->data['user_id'], $_CLASS['core_user']->data['message_limit'], $move_msg_ids, $dest_folder, $cur_folder_id))
					{
						// Return to folder view if single message moved
						if ($action == 'view_message')
						{
							$msg_id		= 0;
							$folder_id	= request_var('cur_folder_id', PRIVMSGS_NO_BOX);
							$action		= 'view_folder';
						}
					}
				}

				// Message Mark Options
				if (isset($_REQUEST['submit_mark']))
				{
					handle_mark_actions($_CLASS['core_user']->data['user_id'], request_var('mark_option', ''));
				}

				// If new messages arrived, place them into the appropiate folder
				$num_not_moved = 0;
				if ($_CLASS['core_user']->data['user_new_privmsg'] && $action == 'view_folder')
				{
					place_pm_into_folder($global_privmsgs_rules, request_var('release', 0));
					$num_not_moved = $_CLASS['core_user']->data['user_new_privmsg'];
				}
		
				if (!$msg_id && $folder_id == PRIVMSGS_NO_BOX && $mode == 'unread')
				{
					$folder_id = PRIVMSGS_INBOX;
				}
				else if ($msg_id && $folder_id == PRIVMSGS_NO_BOX)
				{
					$sql = 'SELECT folder_id
						FROM ' . FORUMS_PRIVMSGS_TO_TABLE . "
						WHERE msg_id = $msg_id
							AND user_id = " . $_CLASS['core_user']->data['user_id'];
					$result = $_CLASS['core_db']->query_limit($sql, 1);
					
					if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
					{
						trigger_error('NO_MESSAGE');
					}					
					$folder_id = (int) $row['folder_id'];
				}
			
				$message_row = array();
				if ($mode == 'view_messages' && $action == 'view_message' && $msg_id)
				{
					// Get Message user want to see
					
					if ($view == 'next' || $view == 'previous')
					{
						$sql_condition = ($view == 'next') ? '>' : '<';
						$sql_ordering = ($view == 'next') ? 'ASC' : 'DESC';

						$sql = 'SELECT t.msg_id
							FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . FORUMS_PRIVMSGS_TABLE . " p2
							WHERE p2.msg_id = $msg_id
								AND t.folder_id = $folder_id
								AND t.user_id = " . $_CLASS['core_user']->data['user_id'] . "
								AND t.msg_id = p.msg_id
								AND p.message_time $sql_condition p2.message_time
							ORDER BY p.message_time $sql_ordering";
						$result = $_CLASS['core_db']->query_limit($sql, 1);

						if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
						{
							$message = ($view == 'next') ? 'NO_NEWER_PM' : 'NO_OLDER_PM';
							trigger_error($message);
						}
						else
						{
							$msg_id = $row['msg_id'];
						}
					}
	
					$sql = 'SELECT t.*, p.*, u.*
						FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . USERS_TABLE . ' u
						WHERE t.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
							AND p.author_id = u.user_id
							AND t.folder_id = $folder_id
							AND t.msg_id = p.msg_id
							AND p.msg_id = $msg_id";
					$result = $_CLASS['core_db']->query_limit($sql, 1);

					if (!($message_row = $_CLASS['core_db']->fetch_row_assoc($result)))
					{
						trigger_error('NO_MESSAGE');
					}

					// Update unread status
					update_unread_status($message_row['unread'], $message_row['msg_id'], $_CLASS['core_user']->data['user_id'], $folder_id);
				}
				
				get_folder($_CLASS['core_user']->data['user_id'], $folder, $folder_id);
			
				$s_folder_options = $s_to_folder_options = '';
				foreach ($folder as $f_id => $folder_ary)
				{
					$option = '<option' . ((!in_array($f_id, array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX))) ? ' class="blue"' : '') . ' value="' . $f_id . '"' . ((($f_id == $folder_id && $mode != 'unread') || ($f_id === 'unread' && $mode == 'unread')) ? ' selected="selected"' : '') . '>' . $folder_ary['folder_name'] . (($folder_ary['unread_messages']) ? ' [' . $folder_ary['unread_messages'] . '] ' : '') . '</option>';

					$s_to_folder_options .= ($f_id != PRIVMSGS_OUTBOX && $f_id != PRIVMSGS_SENTBOX) ? $option : '';
					$s_folder_options .= $option;
				}

				clean_sentbox($folder[PRIVMSGS_SENTBOX]['num_messages']);
	
				// Header for message view - folder and so on
				$folder_status = get_folder_status($folder_id, $folder);
				$url = 'Control_Panel&amp;i='.$id;

				$_CLASS['core_template']->assign(array(
					'CUR_FOLDER_ID'				=> $folder_id,
					'CUR_FOLDER_NAME'			=> $folder_status['folder_name'],
					'NUM_NOT_MOVED'				=> $num_not_moved,
					'RELEASE_MESSAGE_INFO'		=> sprintf($_CLASS['core_user']->lang['RELEASE_MESSAGES'], '<a href="' . generate_link($url . '&amp;folder=' . $folder_id . '&amp;release=1').'">', '</a>'),
					'NOT_MOVED_MESSAGES'		=> ($num_not_moved == 1) ? $_CLASS['core_user']->lang['NOT_MOVED_MESSAGE'] : sprintf($_CLASS['core_user']->lang['NOT_MOVED_MESSAGES'], $num_not_moved),

					'S_FOLDER_OPTIONS'			=> $s_folder_options,
					'S_TO_FOLDER_OPTIONS'		=> $s_to_folder_options,
					'S_FOLDER_ACTION'			=> generate_link($url.'&amp;mode=view_messages&amp;action=view_folder'),
					'S_PM_ACTION'				=> generate_link("$url&amp;mode=$mode&amp;action=$action"),
					
					'U_INBOX'					=> generate_link($url.'&amp;folder=inbox'),
					'U_OUTBOX'					=> generate_link($url.'&amp;folder=outbox'),
					'U_SENTBOX'					=> generate_link($url.'&amp;folder=sentbox'),
					'U_CREATE_FOLDER'			=> generate_link($url.'&amp;mode=options'),
					
					'S_IN_INBOX'				=> ($folder_id == PRIVMSGS_INBOX) ? true : false,
					'S_IN_OUTBOX'				=> ($folder_id == PRIVMSGS_OUTBOX) ? true : false,
					'S_IN_SENTBOX'				=> ($folder_id == PRIVMSGS_SENTBOX) ? true : false,

					'FOLDER_STATUS'				=> $folder_status['message'],
					'FOLDER_MAX_MESSAGES'		=> $folder_status['max'],
					'FOLDER_CUR_MESSAGES'		=> $folder_status['cur'],
					'FOLDER_REMAINING_MESSAGES'	=> $folder_status['remaining'],
					'FOLDER_PERCENT'			=> $folder_status['percent'])
				);
				
				$_CLASS['core_template']->assign('S_VIEW_MESSAGE',  false);

				if ($mode == 'unread' || $action == 'view_folder')
				{
					require($site_file_root.'modules/Control_Panel/ucp/ucp_pm_viewfolder.php');
					view_folder($id, $mode, $folder_id, $folder, (($mode == 'unread') ? 'unread' : 'folder'));

					$tpl_file = 'ucp_pm_viewfolder.html';
				}
				else if ($action == 'view_message')
				{
					$_CLASS['core_template']->assign(array(
						'S_VIEW_MESSAGE'=> true,
						'MSG_ID'		=> $msg_id)
					);
				
					if (!$msg_id)
					{
						trigger_error('NO_MESSAGE');
					}
					
					require($site_file_root.'modules/Control_Panel/ucp/ucp_pm_viewmessage.php');
					view_message($id, $mode, $folder_id, $msg_id, $folder, $message_row);

					$tpl_file = ($view == 'print') ? 'ucp_pm_viewmessage_print.html' : 'ucp_pm_viewmessage.html';
				}
				
				break;

			default:
				trigger_error('NO_ACTION_MODE');
			break;
		}

		$_CLASS['core_template']->assign(array( 
			'L_TITLE'			=> $_CLASS['core_user']->lang['UCP_PM_' . strtoupper($mode)],
			'S_UCP_ACTION'      => generate_link("Control_Panel&amp;i=$id&amp;mode=$mode" . ((isset($action)) ? "&amp;action=$action" : '')))
		);
		
		if (((isset($view)) && $view == 'print') || ($mode == 'popup'))
		{
			//page_header($page_title);
			$_CLASS['core_template']->display('modules/Control_Panel/'.$tpl_file);
			$_CLASS['core_display']->display_footer();
		}
		else
		{
			$this->display($_CLASS['core_user']->lang['UCP_PM'], $tpl_file);
		}
	}
}

?>