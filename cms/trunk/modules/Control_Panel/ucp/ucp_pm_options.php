<?php
// -------------------------------------------------------------
//
// $Id: ucp_pm_options.php,v 1.2 2004/06/02 18:07:40 acydburn Exp $
//
// FILENAME  : options.php
// STARTED   : Mon Apr 19, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

function message_options($id, $mode, $global_privmsgs_rules, $global_rule_conditions)
{
	global $_CLASS, $config;

	$redirect_url = generate_link('Control_Panel&i=pm&mode=options');
	
	$_CLASS['core_template']->assign_array(array(
		'ERROR_MESSAGE'				=> false,
		'S_RULE_DEFINED'			=> false,
		'S_COND_DEFINED'			=> false,
		'NONE_CONDITION'			=> false,
		'S_ACTION_DEFINED'			=> false,
		'NOTIFICATION_MESSAGE'		=> false,
		'rule'						=> false)
	);

	// Change "full folder" setting - what to do if folder is full
	if (isset($_POST['fullfolder']))
	{
		$full_action = request_var('full_action', 0);

		$set_folder_id = 0;
		switch ($full_action)
		{
			case 1:
				$set_folder_id = FULL_FOLDER_DELETE;
				break;
			case 2:
				$set_folder_id = request_var('full_move_to', PRIVMSGS_INBOX);
				break;
			case 3:
				$set_folder_id = FULL_FOLDER_HOLD;
				break;
			default:
				$full_action = 0;
		}

		if ($full_action)
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_full_folder = ' . $set_folder_id . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
			$_CLASS['core_db']->query($sql);

			$_CLASS['core_user']->data['user_full_folder'] = $set_folder_id;
			
			$message = $_CLASS['core_user']->lang['FULL_FOLDER_OPTION_CHANGED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $redirect_url . '">', '</a>');
			$_CLASS['core_display']->meta_refresh(3, $redirect_url);
			trigger_error($message);
		}
	}
	
	// Add Folder
	if (isset($_POST['addfolder']))
	{
		$folder_name = request_var('foldername', '');
		
		if ($folder_name)
		{
			$sql = 'SELECT folder_name 
				FROM ' . PRIVMSGS_FOLDER_TABLE . "
				WHERE folder_name = '" . $_CLASS['core_db']->sql_escape($folder_name) . "'
					AND user_id = " . $_CLASS['core_user']->data['user_id'];
			$result = $_CLASS['core_db']->query_limit($sql, 1);

			if ($_CLASS['core_db']->fetch_row_assoc($result))
			{
				trigger_error(sprintf($_CLASS['core_user']->lang['FOLDER_NAME_EXIST'], $folder_name));
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'SELECT COUNT(folder_id) as num_folder
				FROM ' . PRIVMSGS_FOLDER_TABLE . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
			$result = $_CLASS['core_db']->query($sql);
			
			if ($_CLASS['core_db']->sql_fetchfield('num_folder', 0, $result) >= $config['pm_max_boxes'])
			{
				trigger_error('MAX_FOLDER_REACHED');
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'INSERT INTO ' . PRIVMSGS_FOLDER_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
				'user_id' => (int) $_CLASS['core_user']->data['user_id'], 'folder_name' => $folder_name));
			$_CLASS['core_db']->query($sql);

			$message = $_CLASS['core_user']->lang['FOLDER_ADDED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $redirect_url . '">', '</a>');
			$_CLASS['core_display']->meta_refresh(3, $redirect_url);
			trigger_error($message);

		}
	}
	
	// Rename folder
	if (isset($_POST['rename_folder']))
	{
		$new_folder_name = request_var('new_folder_name', '');
		$rename_folder_id= request_var('rename_folder_id', 0);

		if (!$new_folder_name)
		{
			trigger_error('NO_NEW_FOLDER_NAME');
		}

		// Select custom folder
		$sql = 'SELECT folder_name, pm_count
			FROM ' . PRIVMSGS_FOLDER_TABLE . '
			WHERE user_id = '.$_CLASS['core_user']->data['user_id']."
				AND folder_id = $rename_folder_id";
		$result = $_CLASS['core_db']->query_limit($sql, 1);
		$folder_row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$folder_row)
		{
			trigger_error('CANNOT_RENAME_FOLDER');
		}

		$sql = 'UPDATE ' . PRIVMSGS_FOLDER_TABLE . " 
			SET folder_name = '" . $_CLASS['core_db']->sql_escape($new_folder_name) . "'
			WHERE folder_id = $rename_folder_id
				AND user_id = ".$_CLASS['core_user']->data['user_id'];
		$_CLASS['core_db']->query($sql);

		$message = $_CLASS['core_user']->lang['FOLDER_RENAMED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $redirect_url . '">', '</a>');
		$_CLASS['core_display']->meta_refresh(3, $redirect_url);
		trigger_error($message);
	}

	// Remove Folder
	if (isset($_POST['remove_folder']))
	{
		$remove_folder_id = request_var('remove_folder_id', 0);

		// Default to "move all messages to inbox"
		$remove_action = request_var('remove_action', 1);
		$move_to = request_var('move_to', PRIVMSGS_INBOX);

		// Move to same folder?
		if ($remove_action == 1 && $remove_folder_id == $move_to)
		{
			trigger_error('CANNOT_MOVE_TO_SAME_FOLDER');
		}
		
		// Select custom folder
		$sql = 'SELECT folder_name, pm_count
			FROM ' . PRIVMSGS_FOLDER_TABLE . '
			WHERE user_id = '.$_CLASS['core_user']->data['user_id']."
				AND folder_id = $remove_folder_id";
		$result = $_CLASS['core_db']->query_limit($sql, 1);
		$folder_row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$folder_row)
		{
			trigger_error('CANNOT_REMOVE_FOLDER');
		}

		$s_hidden_fields = '<input type="hidden" name="remove_folder_id" value="' . $remove_folder_id . '" />';
		$s_hidden_fields .= '<input type="hidden" name="remove_action" value="' . $remove_action . '" />';
		$s_hidden_fields .= '<input type="hidden" name="move_to" value="' . $move_to . '" />';
		$s_hidden_fields .= '<input type="hidden" name="remove_folder" value="1" />';

		// Do we need to confirm?
		if (confirm_box(true))
		{
			// Gather message ids
			$sql = 'SELECT msg_id 
				FROM ' . PRIVMSGS_TO_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . "
					AND folder_id = $remove_folder_id";
			$result = $_CLASS['core_db']->query($sql);

			$msg_ids = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$msg_ids[] = (int) $row['msg_id'];
			}
			$_CLASS['core_db']->free_result($result);

			// First of all, copy all messages to another folder... or delete all messages
			switch ($remove_action)
			{
				// Move Messages
				case 1:
					$message_limit = (!$_CLASS['core_user']->data['user_message_limit']) ? $config['pm_max_msgs'] : $_CLASS['core_user']->data['user_message_limit'];
					$num_moved = move_pm($_CLASS['core_user']->data['user_id'], $message_limit, $msg_ids, $move_to, $remove_folder_id);
					
					// Something went wrong, only partially moved?
					if ($num_moved != $folder_row['pm_count'])
					{
						trigger_error(sprintf($_CLASS['core_user']->lang['MOVE_PM_ERROR'], $num_moved, $folder_row['pm_count']));
					}
					break;

				// Remove Messages
				case 2:
					delete_pm($_CLASS['core_user']->data['user_id'], $msg_ids, $remove_folder_id);
					break;
			}

			// Remove folder
			$sql = 'DELETE FROM ' . PRIVMSGS_FOLDER_TABLE . '
				WHERE user_id = '.$_CLASS['core_user']->data['user_id']."
					AND folder_id = $remove_folder_id";
			$_CLASS['core_db']->query($sql);

			// Check full folder option. If the removed folder has been specified as destination switch back to inbox
			if ($_CLASS['core_user']->data['user_full_folder'] == $remove_folder_id)
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_full_folder = ' . PRIVMSGS_INBOX . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->query($sql);

				$_CLASS['core_user']->data['user_full_folder'] = PRIVMSGS_INBOX;
			}

			$meta_info = generate_link('Control_Panel&amp;i=pm&amp;mode='.$mode);
			$message = $_CLASS['core_user']->lang['FOLDER_REMOVED'];

			$_CLASS['core_display']->meta_refresh(3, $meta_info);
			$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $meta_info . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			confirm_box(false, 'REMOVE_FOLDER', $s_hidden_fields);
		}
	}

	// Add Rule
	if (isset($_POST['add_rule']))
	{
		$check_option	= request_var('check_option', 0);
		$rule_option	= request_var('rule_option', 0);
		$cond_option	= request_var('cond_option', '');
		$action_option	= explode('|', request_var('action_option', ''));
		$rule_string	= ($cond_option != 'none') ? request_var('rule_string', '') : '';
		$rule_user_id	= ($cond_option != 'none') ? request_var('rule_user_id', 0) : 0;
		$rule_group_id	= ($cond_option != 'none') ? request_var('rule_group_id', 0) : 0;

		$action = (int) $action_option[0];
		$folder_id = (int) $action_option[1];

		if (!$action || !$check_option || !$rule_option || !$cond_option || ($cond_option != 'none' && !$rule_string))
		{
			trigger_error('RULE_NOT_DEFINED');
		}

		if (($cond_option == 'user' && !$rule_user_id) || ($cond_option == 'group' && !$rule_group_id))
		{
			trigger_error('RULE_NOT_DEFINED');
		}
		
		$rule_ary = array(
			'user_id'			=> $_CLASS['core_user']->data['user_id'],
			'rule_check'		=> $check_option,
			'rule_connection'	=> $rule_option,
			'rule_string'		=> $rule_string,
			'rule_user_id'		=> $rule_user_id,
			'rule_group_id'		=> $rule_group_id,
			'rule_action'		=> $action,
			'rule_folder_id'	=> $folder_id
		);

		$sql = 'SELECT rule_id 
			FROM ' . PRIVMSGS_RULES_TABLE . '
			WHERE ' . $_CLASS['core_db']->sql_build_array('SELECT', $rule_ary);
		$result = $_CLASS['core_db']->query($sql);

		if ($_CLASS['core_db']->fetch_row_assoc($result))
		{
			trigger_error('RULE_ALREADY_DEFINED');
		}
		$_CLASS['core_db']->free_result($result);
		
		$sql = 'INSERT INTO ' . PRIVMSGS_RULES_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $rule_ary);
		$_CLASS['core_db']->query($sql);

		$message = $_CLASS['core_user']->lang['RULE_ADDED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $redirect_url . '">', '</a>');
		$_CLASS['core_display']->meta_refresh(3, $redirect_url);
		trigger_error($message);
	}

	// Remove Rule
	if (isset($_POST['delete_rule']) && !isset($_POST['cancel']))
	{
		$delete_id = array_map('intval', array_keys($_POST['delete_rule']));
		$delete_id = (int) $delete_id[0];

		if (!$delete_id)
		{
			redirect(generate_link('Control_Panel&amp;i=pm&amp;mode='.$mode));
		}

		$s_hidden_fields = '<input type="hidden" name="delete_rule[' . $delete_id . ']" value="1" />';

		// Do we need to confirm ?
		if (confirm_box(true))
		{
			$sql = 'DELETE FROM ' . PRIVMSGS_RULES_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . "
					AND rule_id = $delete_id";
			$_CLASS['core_db']->query($sql);

			$meta_info = generate_link("Control_Panel$SID&amp;i=pm&amp;mode=$mode");
			$message = $_CLASS['core_user']->lang['RULE_DELETED'];

			$_CLASS['core_display']->meta_refresh(3, $meta_info);
			$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $meta_info . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			confirm_box(false, 'DELETE_RULE', $s_hidden_fields);
		}
	}
	
	$folder = array();
	$message_limit = (!$_CLASS['core_user']->data['user_message_limit']) ? $config['pm_max_msgs'] : $_CLASS['core_user']->data['user_message_limit'];

	$sql = 'SELECT COUNT(msg_id) as num_messages
		FROM ' . PRIVMSGS_TO_TABLE . '
		WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
			AND folder_id = ' . PRIVMSGS_INBOX;
	$result = $_CLASS['core_db']->query($sql);
	list($num_messages) = $_CLASS['core_db']->fetch_row_num($result);
	$_CLASS['core_db']->free_result($result);
	
	$folder[PRIVMSGS_INBOX] = array(
		'folder_name'	=> $_CLASS['core_user']->lang['PM_INBOX'], 
		'message_status'=> sprintf($_CLASS['core_user']->lang['FOLDER_MESSAGE_STATUS'], $num_messages, $message_limit)
	);

	$sql = 'SELECT folder_id, folder_name, pm_count 
		FROM ' . PRIVMSGS_FOLDER_TABLE . '
			WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
	$result = $_CLASS['core_db']->query($sql);

	$num_user_folder = 0;
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$num_user_folder++;
		$folder[$row['folder_id']] = array(
			'folder_name'	=> $row['folder_name'], 
			'message_status'=> sprintf($_CLASS['core_user']->lang['FOLDER_MESSAGE_STATUS'], $row['pm_count'], $message_limit)
		);
	}
	$_CLASS['core_db']->free_result($result);

	$s_full_folder_options = $s_to_folder_options = $s_folder_options = '';
	
	if ($_CLASS['core_user']->data['user_full_folder'] == FULL_FOLDER_NONE)
	{
		// -3 here to let the correct folder id be selected
		$to_folder_id = $config['full_folder_action']-3;
	}
	else
	{
		$to_folder_id = $_CLASS['core_user']->data['user_full_folder'];
	}
	
	foreach ($folder as $folder_id => $folder_ary)
	{
		$s_full_folder_options .= '<option value="' . $folder_id . '"' . (($_CLASS['core_user']->data['user_full_folder'] == $folder_id) ? ' selected="selected"' : '') . '>' . $folder_ary['folder_name'] . ' (' . $folder_ary['message_status'] . ')</option>';
		//$s_to_folder_options .= '<option value="' . $folder_id . '"' . (($to_folder_id == $folder_id) ? ' selected="selected"' : '') . '>' . $folder_ary['folder_name'] . ' (' . $folder_ary['message_status'] . ')</option>';
		$s_to_folder_options .= '<option value="' . $folder_id . '>' . $folder_ary['folder_name'] . ' (' . $folder_ary['message_status'] . ')</option>';
	
		if ($folder_id != PRIVMSGS_INBOX)
		{
			$s_folder_options .= '<option value="' . $folder_id . '">' . $folder_ary['folder_name'] . ' (' . $folder_ary['message_status'] . ')</option>';
		}
	}

	$s_delete_checked = ($_CLASS['core_user']->data['user_full_folder'] == FULL_FOLDER_DELETE) ? ' checked="checked"' : '';
	$s_hold_checked = ($_CLASS['core_user']->data['user_full_folder'] == FULL_FOLDER_HOLD) ? ' checked="checked"' : '';
	$s_move_checked = ($_CLASS['core_user']->data['user_full_folder'] >= 0) ? ' checked="checked"' : '';

	if ($_CLASS['core_user']->data['user_full_folder'] == FULL_FOLDER_NONE)
	{
		switch ($config['full_folder_action'])
		{
			case 1:
				$s_delete_checked = ' checked="checked"';
				break;
				
			case 2:
				$s_hold_checked = ' checked="checked"';
				break;
		}
	}

	$_CLASS['core_template']->assign_array(array(
		'S_FULL_FOLDER_OPTIONS'	=> $s_full_folder_options,
		'S_TO_FOLDER_OPTIONS'	=> $s_to_folder_options,
		'S_FOLDER_OPTIONS'		=> $s_folder_options,
		'S_DELETE_CHECKED'		=> $s_delete_checked,
		'S_HOLD_CHECKED'		=> $s_hold_checked,
		'S_MOVE_CHECKED'		=> $s_move_checked,
		'S_MAX_FOLDER_REACHED'	=> ($num_user_folder >= $config['pm_max_boxes']) ? true : false,

		'DEFAULT_ACTION'		=> ($config['full_folder_action'] == 1) ? $_CLASS['core_user']->lang['DELETE_OLDEST_MESSAGES'] : $_CLASS['core_user']->lang['HOLD_NEW_MESSAGES'],
			
		'U_FIND_USERNAME'		=> generate_link('Members_List&amp;mode=searchuser&amp;form=ucp&amp;field=rule_string'))
	);

	$rule_lang = $action_lang = $check_lang = array();

	// Build all three language arrays
	preg_replace('#^((RULE|ACTION|CHECK)_([A-Z0-9_]+))$#e', "\${strtolower('\\2') . '_lang'}[constant('\\1')] = \$_CLASS['core_user']->lang['PM_\\2']['\\3']", array_keys(get_defined_constants()));
	
	/*
		Rule Ordering:
			-> CHECK_* -> RULE_* [IN $global_privmsgs_rules:CHECK_*] -> [IF $rule_conditions[RULE_*] [|text|bool|user|group|own_group]] -> ACTION_*
	*/

	$check_option	= request_var('check_option', 0);
	$rule_option	= request_var('rule_option', 0);
	$cond_option	= request_var('cond_option', '');
	$action_option	= request_var('action_option', '');
	$back = (isset($_REQUEST['back'])) ? request_var('back', '') : array();

	if (sizeof($back))
	{
		if ($action_option)
		{
			$action_option = '';
		}
		else if ($cond_option)
		{
			$cond_option = '';
		}
		else if ($rule_option)
		{
			$rule_option = 0;
		}
		else if ($check_option)
		{
			$check_option = 0;
		}
	}

	if (isset($back['action']) && $cond_option == 'none')
	{
		$back['cond'] = true;
	}

	// Check
	define_check_option(($check_option && !isset($back['rule'])) ? true : false, $check_option, $check_lang);

	if ($check_option && !isset($back['rule']))
	{
		define_rule_option(($rule_option && !isset($back['cond'])) ? true : false, $rule_option, $rule_lang, $global_privmsgs_rules[$check_option]);
	}

	if ($rule_option && !isset($back['cond']))
	{
		if (!isset($global_rule_conditions[$rule_option]))
		{
			$cond_option = 'none';
			$_CLASS['core_template']->assign('NONE_CONDITION', true);
		}
		else
		{
			define_cond_option(($cond_option && !isset($back['action'])) ? true : false, $cond_option, $rule_option, $global_rule_conditions);
		}
	}

	if ($cond_option && !isset($back['action']))
	{
		define_action_option(false, $action_option, $action_lang, $folder);
	}
	
	show_defined_rules($_CLASS['core_user']->data['user_id'], $check_lang, $rule_lang, $action_lang, $folder);
}

function define_check_option($hardcoded, $check_option, $check_lang)
{
	global $_CLASS;

	$s_check_options = '';
	if (!$hardcoded)
	{
		foreach ($check_lang as $value => $lang)
		{
			$s_check_options .= '<option value="' . $value . '"' . (($value == $check_option) ? ' selected="selected"' : '') . '>' . $lang . '</option>'; 
		}
	}

	$_CLASS['core_template']->assign_array(array(
		'S_CHECK_DEFINED'	=> true,
		'S_CHECK_SELECT'	=> ($hardcoded) ? false : true,
		'CHECK_CURRENT'		=> isset($check_lang[$check_option]) ? $check_lang[$check_option] : '',
		'S_CHECK_OPTIONS'	=> $s_check_options,
		'CHECK_OPTION'		=> $check_option)
	);
}

function define_action_option($hardcoded, $action_option, $action_lang, $folder)
{
	global $_CLASS;

	$l_action = $s_action_options = '';
	if ($hardcoded)
	{
		$option = explode('|', $action_option);
		$action = (int) $option[0];
		$folder_id = (int) $option[1];

		$l_action = $action_lang[$action];
		if ($action == ACTION_PLACE_INTO_FOLDER)
		{
			$l_action .= ' -> ' . $folder[$folder_id]['folder_name'];
		}
	}
	else
	{
		foreach ($action_lang as $action => $lang)
		{
			if ($action == ACTION_PLACE_INTO_FOLDER)
			{
				foreach ($folder as $folder_id => $folder_ary)
				{
					$s_action_options .= '<option value="' . $action . '|' . $folder_id . '"' . (($action_option == $action . '|' . $folder_id) ? ' selected="selected"' : '') . '>' . $lang . ' -> ' . $folder_ary['folder_name'] . '</option>';
				}
			}
			else
			{
				$s_action_options .= '<option value="' . $action . '|0"' . (($action_option == $action . '|0') ? ' selected="selected"' : '') . '>' . $lang . '</option>';
			}
		}
	}

	$_CLASS['core_template']->assign_array(array(
		'S_ACTION_DEFINED'	=> true,
		'S_ACTION_SELECT'	=> ($hardcoded) ? false : true,
		'ACTION_CURRENT'	=> $l_action,
		'S_ACTION_OPTIONS'	=> $s_action_options,
		'ACTION_OPTION'		=> $action_option)
	);
}

function define_rule_option($hardcoded, $rule_option, $rule_lang, $check_ary)
{
	global $_CLASS;

	$s_rule_options = '';
	if (!$hardcoded)
	{
		foreach ($check_ary as $value => $_check)
		{
			$s_rule_options .= '<option value="' . $value . '"' . (($value == $rule_option) ? ' selected="selected"' : '') . '>' . $rule_lang[$value] . '</option>'; 
		}
	}

	$_CLASS['core_template']->assign_array(array(
		'S_RULE_DEFINED'	=> true,
		'S_RULE_SELECT'		=> !$hardcoded,
		'RULE_CURRENT'		=> isset($rule_lang[$rule_option]) ? $rule_lang[$rule_option] : '',
		'S_RULE_OPTIONS'	=> $s_rule_options,
		'RULE_OPTION'		=> $rule_option)
	);
}

function define_cond_option($hardcoded, $cond_option, $rule_option, $global_rule_conditions)
{
	global $_CLASS;
	
	$_CLASS['core_template']->assign_array(array(
		'S_COND_DEFINED'	=> true,
		'S_COND_SELECT'		=> (!$hardcoded && isset($global_rule_conditions[$rule_option])) ? true : false)
	);

	// Define COND_OPTION
	if (!isset($global_rule_conditions[$rule_option]))
	{
		$_CLASS['core_template']->assign_array(array(
			'COND_OPTION'	=> 'none',
			'COND_CURRENT'	=> false)
		);
		return;
	}
	
	// Define Condition
	$condition = $global_rule_conditions[$rule_option];
	$current_value = '';

	switch ($condition)
	{
		case 'text':
			$rule_string = request_var('rule_string', '');

			$_CLASS['core_template']->assign_array(array(
				'S_TEXT_CONDITION'	=> true,
				'CURRENT_STRING'	=> $rule_string,
				'CURRENT_USER_ID'	=> 0,
				'CURRENT_GROUP_ID'	=> 0)
			);

			$current_value = $rule_string;
			break;

		case 'user':
			$rule_user_id = request_var('rule_user_id', 0);
			$rule_string = request_var('rule_string', '');

			if ($rule_string && !$rule_user_id)
			{
				$sql = 'SELECT user_id
					FROM ' . USERS_TABLE . "
					WHERE username = '" . $_CLASS['core_db']->sql_escape($rule_string) . "'";
				$result = $_CLASS['core_db']->query($sql);
				
				if (!($rule_user_id = $_CLASS['core_db']->sql_fetchfield('user_id', 0, $result)))
				{
					$rule_string = '';
				}
				$_CLASS['core_db']->free_result($result);
			}
			else if (!$rule_string && $rule_user_id)
			{
				$sql = 'SELECT username
					FROM ' . USERS_TABLE . "
					WHERE user_id = $rule_user_id";
				$result = $_CLASS['core_db']->query($sql);
				
				if (!($rule_string = $_CLASS['core_db']->sql_fetchfield('username', 0, $result)))
				{
					$rule_user_id = 0;
				}
				$_CLASS['core_db']->free_result($result);
			}

			$_CLASS['core_template']->assign_array(array(
				'S_USER_CONDITION'	=> true,
				'CURRENT_STRING'	=> $rule_string,
				'CURRENT_USER_ID'	=> $rule_user_id,
				'CURRENT_GROUP_ID'	=> 0)
			);

			$current_value = $rule_string;
			break;

		case 'group':
			$rule_group_id = request_var('rule_group_id', 0);
			$rule_string = request_var('rule_string', '');

			$_CLASS['core_template']->assign_array(array(
				'S_GROUP_CONDITION'	=> true,
				'CURRENT_STRING'	=> $rule_string,
				'CURRENT_USER_ID'	=> 0,
				'CURRENT_GROUP_ID'	=> $rule_group_id)
			);

			$current_value = $rule_string;

			break;

		default:
			return;
	}

	$_CLASS['core_template']->assign_array(array(
		'COND_OPTION'	=> $condition,
		'COND_CURRENT'	=> $current_value)
	);
}

function show_defined_rules($user_id, $check_lang, $rule_lang, $action_lang, $folder)
{
	global $_CLASS;

	$sql = 'SELECT *
		FROM ' . PRIVMSGS_RULES_TABLE . '
		WHERE user_id = ' . $user_id;
	$result = $_CLASS['core_db']->query($sql);
	
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_template']->assign_vars_array('rule', array(
			'RULE_ID'	=> $row['rule_id'],
			'CHECK'		=> $check_lang[$row['rule_check']],
			'RULE'		=> $rule_lang[$row['rule_connection']],
			'STRING'	=> $row['rule_string'],
			'ACTION'	=> $action_lang[$row['rule_action']],
			'FOLDER'	=> ($row['rule_action'] == ACTION_PLACE_INTO_FOLDER) ? $folder[$row['rule_folder_id']]['folder_name'] : '')
		);
	}
	$_CLASS['core_db']->free_result($result);
}

?>