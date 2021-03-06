<?php
// -------------------------------------------------------------
//
// $Id: ucp_pm.php,v 1.6 2004/09/01 19:29:02 acydburn Exp $
//
// FILENAME  : ucp_pm.php
// STARTED   : Sat Mar 27, 2004
// COPYRIGHT : � 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

/** 
* Private Message Class
*
* @param int $folder display folder with the id used
* @param inbox|outbox|sentbox display folder with the associated name
*
*
*	Display Messages (default to inbox) - mode=view
*	Display single message - mode=view&p=[msg_id] or &p=[msg_id] (short linkage)
*
*	if the folder id with (&f=[folder_id]) is used when displaying messages, one query will be saved. If it is not used, phpBB needs to grab
*	the folder id first in order to display the input boxes and folder names and such things. ;) phpBB always checks this against the database to make
*	sure the user is able to view the message.
*
*	Composing Messages (mode=compose):
*		To specific user (u=[user_id])
*		To specific group (g=[group_id])
*		Quoting a post (action=quotepost&p=[post_id])
*		Quoting a PM (action=quote&p=[msg_id])
*		Forwarding a PM (action=forward&p=[msg_id])
*
* @package ucp
*/
global $_CLASS, $config;

$action = '';
$mode = false;

if (!$_CLASS['core_user']->is_user)
{
	trigger_error('NO_MESSAGE');
}

$_CLASS['core_template']->assign_array(array(
	'S_UNREAD'				=> false,
	'TOTAL_MESSAGES'		=> false,
	'S_DISPLAY_HISTORY'		=> false,
	'S_BCC_RECIPIENT'		=> false,
));

// This is loaded 2x with drafts
$_CLASS['core_user']->add_lang();
$_CLASS['core_user']->add_lang('posting', 'forums');

$_CLASS['core_template']->assign('S_PRIVMSGS', true);

// Folder directly specified?
$folder_specified = get_variable('folder', 'REQUEST');

if ($folder_specified)
{
	if (is_numeric($folder_specified))
	{
		$folder_specified = (int) $folder_specified;
	}
	else
	{
		$folder_specified = ($folder_specified === 'sentbox') ? PRIVMSGS_SENTBOX : (($folder_specified === 'outbox') ? PRIVMSGS_OUTBOX : PRIVMSGS_INBOX);
	}

	$mode = 'view';
}
else
{
	$mode = (!$mode) ? get_variable('mode', 'REQUEST', 'view') : $mode;
}

$id = 'pm';

require_once SITE_FILE_ROOT.'includes/forums/functions.php';
require_once SITE_FILE_ROOT.'includes/forums/functions_privmsgs.php';

$_CLASS['core_template']->assign_array(array( 
	'L_TITLE'			=> $_CLASS['core_user']->get_lang('UCP_PM_' . strtoupper($mode)),
	'S_UCP_ACTION'      => generate_link($this->link . ((isset($action)) ? "&amp;action=$action" : '')))
);

switch ($mode)
{
	// New private messages popup
	case 'popup':
	
		$indox_link = generate_link('control_panel&amp;i=pm&amp;folder=inbox');

		if ($_CLASS['core_user']->data['user_new_privmsg'])
		{
			$l_new_message = ($_CLASS['core_user']->data['user_new_privmsg'] == 1) ? $_CLASS['core_user']->lang['YOU_NEW_PM'] : $_CLASS['core_user']->lang['YOU_NEW_PMS'];
		}
		else
		{
			$l_new_message = $_CLASS['core_user']->lang['YOU_NO_NEW_PM'];
		}

		$_CLASS['core_template']->assign_array(array(
			'MESSAGE'			=> $l_new_message,
			'U_JS_RETURN_INBOX'	=> $indox_link,
			'S_NOT_LOGGED_IN'	=> ($_CLASS['core_user']->data['user_id'] == ANONYMOUS) ? true : false,
			'CLICK_TO_VIEW'		=> sprintf($_CLASS['core_user']->lang['CLICK_VIEW_PRIVMSG'], '<a href="' . $indox_link . '" onclick="jump_to_inbox();return false;" target="_new">', '</a>'),
			'U_INBOX'			=> $indox_link
		));

		$_CLASS['core_display']->display(false, 'modules/control_panel/ucp_pm_popup.html');
	break;

	// Compose message
	case 'compose':
		$action = get_variable('action', 'REQUEST', 'post');

		get_folder($_CLASS['core_user']->data['user_id']);
		
		require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_pm_compose.php';
		compose_pm($id, $mode, $action);
	
		$_CLASS['core_display']->display(false, 'modules/control_panel/ucp_posting_body.html');
	break;
	
	case 'options':
		$message_limit = 10;
		
		(int) $_CLASS['core_user']->data['user_message_limit'] = (!$message_limit) ? $config['pm_max_msgs'] : $message_limit;
		
		get_folder($_CLASS['core_user']->data['user_id']);

		require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_pm_options.php';
		message_options($id, $mode, $global_privmsgs_rules, $global_rule_conditions);

		$_CLASS['core_display']->display(false, 'modules/control_panel/ucp_pm_options.html');
	break;

	case 'drafts':
		get_folder($_CLASS['core_user']->data['user_id']);
	
		require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_main.php';
		$module = new ucp_main($id, $mode);
		unset($module);
		exit;
	break;

	case 'view':
		$message_limit = 10;

		$_CLASS['core_user']->data['user_message_limit'] = (!$message_limit) ? $config['pm_max_msgs'] : $message_limit;

		if ($folder_specified)
		{
			$folder_id = $folder_specified;
			$action = 'view_folder';
		}
		else
		{
			$folder_id = get_variable('f', 'REQUEST', PRIVMSGS_NO_BOX, 'int');
			$action = get_variable('action', 'REQUEST', 'view_folder');
		}

		$msg_id = get_variable('p', 'REQUEST', 0, 'int');
		$view	= get_variable('view', 'REQUEST');
		
		if ($msg_id)
		{
			$action = 'view_message';
		}

		// First Handle Mark actions and moving messages
		$submit_mark	= isset($_POST['submit_mark']);
		$move_pm		= isset($_POST['move_pm']);
		$mark_option	= get_variable('mark_option', 'REQUEST', '');
		$dest_folder	= get_variable('dest_folder', 'REQUEST', PRIVMSGS_NO_BOX);

		// Is moving PM triggered through mark options?
		if (!in_array($mark_option, array('mark_important', 'delete_marked')) && $submit_mark)
		{
			$move_pm = true;
			$dest_folder = (int) $mark_option;
			$submit_mark = false;
		}

		// Move PM
		if ($move_pm)
		{
			$msg_ids		= isset($_POST['marked_msg_id']) ? array_unique(array_map('intval', $_POST['marked_msg_id'])) : array();
			$cur_folder_id	= get_variable('cur_folder_id', 'POST', PRIVMSGS_NO_BOX, 'int');

			if (move_pm($_CLASS['core_user']->data['user_id'], $_CLASS['core_user']->data['user_message_limit'], $msg_ids, $dest_folder, $cur_folder_id))
			{
				// Return to folder view if single message moved
				if ($action === 'view_message')
				{
					$msg_id		= 0;
					$folder_id	= $cur_folder_id;
					$action		= 'view_folder';
				}
			}
		}

		// Message Mark Options
		if ($submit_mark)
		{
			$mark_option = get_variable('mark_option', 'POST');
			$msg_ids		= isset($_POST['marked_msg_id']) ? array_unique(array_map('intval', $_POST['marked_msg_id'])) : array();
			$cur_folder_id	= get_variable('cur_folder_id', 'POST', PRIVMSGS_INBOX, 'int');

			Switch ($mark_option)
			{
				case 'mark_read':
				case 'mark_unread':
					$read_status = ($mark_option === 'mark_read');
					set_read_status($read_status, $msg_ids, $_CLASS['core_user']->data['user_id'], $cur_folder_id);
				break;

				default:
					handle_mark_actions($_CLASS['core_user']->data['user_id'], $mark_option, $msg_ids, $cur_folder_id);
				break;
			}
		}

		// If new messages arrived, place them into the appropiate folder
		$num_not_moved = 0;

		if ($_CLASS['core_user']->data['user_new_privmsg'] && $action == 'view_folder')
		{
			place_pm_into_folder($global_privmsgs_rules, get_variable('release', 'POST', 0));
			$num_not_moved = $_CLASS['core_user']->data['user_new_privmsg'];

			// Make sure num_not_moved is valid.
			if ($num_not_moved < 0)
			{
				$sql = 'UPDATE ' . CORE_USERS_TABLE . '
					SET user_new_privmsg = 0, user_unread_privmsg = 0
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->query($sql);

				$num_not_moved = $_CLASS['core_user']->data['user_new_privmsg'] = $_CLASS['core_user']->data['user_unread_privmsg'] = 0;
			}
		}

		if (!$msg_id && $folder_id == PRIVMSGS_NO_BOX)
		{
			$folder_id = PRIVMSGS_INBOX;
		}
		else if ($msg_id && $folder_id == PRIVMSGS_NO_BOX)
		{
			$sql = 'SELECT folder_id
				FROM ' . FORUMS_PRIVMSGS_TO_TABLE . "
				WHERE msg_id = $msg_id
					AND user_id = " . $_CLASS['core_user']->data['user_id'];
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$row)
			{
				trigger_error('NO_MESSAGE');
			}
			$folder_id = (int) $row['folder_id'];
		}

		$message_row = array();

		if ($action === 'view_message' && $msg_id)
		{
			// Get Message user want to see
			if ($view === 'next' || $view === 'previous')
			{
				if ($view === 'next')
				{
					$sql_condition = '>';
					$sql_ordering = 'ASC';
				}
				else
				{
					$sql_condition = '<';
					$sql_ordering = 'DESC';
				}

// Redo this for sqlite
				$sql = 'SELECT t.msg_id
					FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . FORUMS_PRIVMSGS_TABLE . " p2
					WHERE p2.msg_id = $msg_id
						AND t.folder_id = $folder_id
						AND t.user_id = " . $_CLASS['core_user']->data['user_id'] . "
						AND t.msg_id = p.msg_id
						AND p.message_time $sql_condition p2.message_time
					ORDER BY p.message_time $sql_ordering";
				$result = $_CLASS['core_db']->query_limit($sql, 1);
				$row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if (!$row)
				{
					$message = ($view === 'next') ? 'NO_NEWER_PM' : 'NO_OLDER_PM';
					trigger_error($message);
				}
				else
				{
					$msg_id = $row['msg_id'];
				}
			}

			$sql = 'SELECT t.*, p.*, u.*
				FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . CORE_USERS_TABLE . ' u
				WHERE t.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
					AND p.author_id = u.user_id
					AND t.folder_id = $folder_id
					AND t.msg_id = p.msg_id
					AND p.msg_id = $msg_id";
			$result = $_CLASS['core_db']->query_limit($sql, 1);
			$message_row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$message_row)
			{
				trigger_error('NO_MESSAGE');
			}

			// Update unread status
			if ($message_row['pm_unread'])
			{
				set_read_status(true, $message_row['msg_id'], $_CLASS['core_user']->data['user_id'], $folder_id);
			}
		}

		$folder = get_folder($_CLASS['core_user']->data['user_id'], $folder_id);

		$s_folder_options = $s_to_folder_options = '';
		foreach ($folder as $f_id => $folder_ary)
		{
			$option = '<option' . ((!in_array($f_id, array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX))) ? ' class="blue"' : '') . ' value="' . $f_id . '"' . (($f_id == $folder_id) ? ' selected="selected"' : '') . '>' . $folder_ary['folder_name'] . (($folder_ary['unread_messages']) ? ' [' . $folder_ary['unread_messages'] . '] ' : '') . '</option>';

			$s_to_folder_options .= ($f_id != PRIVMSGS_OUTBOX && $f_id != PRIVMSGS_SENTBOX) ? $option : '';
			$s_folder_options .= $option;
		}
		clean_sentbox($folder[PRIVMSGS_SENTBOX]['num_messages']);

		// Header for message view - folder and so on
		$folder_status = get_folder_status($folder_id, $folder);

		$_CLASS['core_template']->assign_array(array(
			'CUR_FOLDER_ID'				=> $folder_id,
			'CUR_FOLDER_NAME'			=> $folder_status['folder_name'],
			'NUM_NOT_MOVED'				=> $num_not_moved,
			'RELEASE_MESSAGE_INFO'		=> sprintf($_CLASS['core_user']->lang['RELEASE_MESSAGES'], '<a href="' . generate_link($this->link_parent . '&amp;folder=' . $folder_id . '&amp;release=1').'">', '</a>'),
			'NOT_MOVED_MESSAGES'		=> ($num_not_moved == 1) ? $_CLASS['core_user']->lang['NOT_MOVED_MESSAGE'] : sprintf($_CLASS['core_user']->lang['NOT_MOVED_MESSAGES'], $num_not_moved),

			'S_FOLDER_OPTIONS'			=> $s_folder_options,
			'S_TO_FOLDER_OPTIONS'		=> $s_to_folder_options,
			'S_FOLDER_ACTION'			=> generate_link($this->link_parent.'&amp;mode=view&amp;action=view_folder'),
			'S_PM_ACTION'				=> generate_link($this->link_parent.'&amp;mode=$mode&amp;action='.$action),
			
			'U_INBOX'					=> generate_link($this->link_parent.'&amp;folder=inbox'),
			'U_OUTBOX'					=> generate_link($this->link_parent.'&amp;folder=outbox'),
			'U_SENTBOX'					=> generate_link($this->link_parent.'&amp;folder=sentbox'),
			'U_CREATE_FOLDER'			=> generate_link($this->link_parent.'&amp;mode=options'),
			
			'S_IN_INBOX'				=> ($folder_id == PRIVMSGS_INBOX),
			'S_IN_OUTBOX'				=> ($folder_id == PRIVMSGS_OUTBOX),
			'S_IN_SENTBOX'				=> ($folder_id == PRIVMSGS_SENTBOX),

			'FOLDER_STATUS'				=> $folder_status['message'],
			'FOLDER_MAX_MESSAGES'		=> $folder_status['max'],
			'FOLDER_CUR_MESSAGES'		=> $folder_status['cur'],
			'FOLDER_REMAINING_MESSAGES'	=> $folder_status['remaining'],
			'FOLDER_PERCENT'			=> $folder_status['percent'])
		);
		
		$_CLASS['core_template']->assign('S_VIEW_MESSAGE',  false);

		if ($action === 'view_folder')
		{
			require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_pm_viewfolder.php';
			view_folder($this, $folder_id, $folder, (($mode === 'unread') ? 'unread' : 'folder'));

			$_CLASS['core_display']->display(false,  'modules/control_panel/ucp_pm_viewfolder.html');

		}
		elseif ($action == 'view_message')
		{
			$_CLASS['core_template']->assign_array(array(
				'S_VIEW_MESSAGE'=> true,
				'MSG_ID'		=> $msg_id
			));
		
			if (!$msg_id)
			{
				trigger_error('NO_MESSAGE');
			}
			
			require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_pm_viewmessage.php';
			view_message($this, $folder_id, $msg_id, $folder, $message_row);

			$_CLASS['core_display']->display(false, 'modules/control_panel/'.(($view === 'print') ? 'ucp_pm_viewmessage_print.html' : 'ucp_pm_viewmessage.html'));
		}	
	break;

	default:
		trigger_error('NO_ACTION_MODE');
	break;
}

?>