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
// $Id: functions_privmsgs.php,v 1.7 2004/09/16 18:33:20 acydburn Exp $
//
// FILENAME  : functions_privmsgs.php
// STARTED   : Sun Apr 18, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Define Rule processing schema
// NOTE: might change

/*
	Ability to simply add own rules by doing three things:
		1) Add an appropiate constant
		2) Add a new check array to the global_privmsgs_rules variable and the condition array (if one is required)
		3) Add a new language variable to ucp.php
		
		The user is then able to select the new rule. It will be checked against and handled as specified.
		To add new actions (yes, checks can be added here too) to the rule management, the core code has to be modified.
*/

define('RULE_IS_LIKE', 1);		// Is Like
define('RULE_IS_NOT_LIKE', 2);	// Is Not Like
define('RULE_IS', 3);			// Is
define('RULE_IS_NOT', 4);		// Is Not
define('RULE_BEGINS_WITH', 5);	// Begins with
define('RULE_ENDS_WITH', 6);	// Ends with
define('RULE_IS_FRIEND', 7);	// Is Friend
define('RULE_IS_FOE', 8);		// Is Foe
define('RULE_IS_USER', 9);		// Is User
define('RULE_IS_GROUP', 10);	// Is In Usergroup
define('RULE_ANSWERED', 11);	// Answered
define('RULE_FORWARDED', 12);	// Forwarded
define('RULE_TO_GROUP', 14);	// Usergroup
define('RULE_TO_ME', 15);		// Me

define('ACTION_PLACE_INTO_FOLDER', 1);
define('ACTION_MARK_AS_READ', 2);
define('ACTION_MARK_AS_IMPORTANT', 3);
define('ACTION_DELETE_MESSAGE', 4);

define('CHECK_SUBJECT', 1);
define('CHECK_SENDER', 2);
define('CHECK_MESSAGE', 3);
define('CHECK_STATUS', 4);
define('CHECK_TO', 5);

/**
* Global private message rules
* These rules define what to do if a rule is hit
*/
$global_privmsgs_rules = array(
	CHECK_SUBJECT	=> array(
		RULE_IS_LIKE		=> array('check0' => 'message_subject', 'function' => 'preg_match("/" . preg_quote({STRING}, "/") . "/i", {CHECK0})'),
		RULE_IS_NOT_LIKE	=> array('check0' => 'message_subject', 'function' => '!(preg_match("/" . preg_quote({STRING}, "/") . "/i", {CHECK0}))'),
		RULE_IS				=> array('check0' => 'message_subject', 'function' => '{CHECK0} == {STRING}'),
		RULE_IS_NOT			=> array('check0' => 'message_subject', 'function' => '{CHECK0} != {STRING}'),
		RULE_BEGINS_WITH	=> array('check0' => 'message_subject', 'function' => 'preg_match("/^" . preg_quote({STRING}, "/") . "/i", {CHECK0})'),
		RULE_ENDS_WITH		=> array('check0' => 'message_subject', 'function' => 'preg_match("/" . preg_quote({STRING}, "/") . "$/i", {CHECK0})'),
	),

	CHECK_SENDER	=> array(
		RULE_IS_LIKE		=> array('check0' => 'username', 'function' => 'preg_match("/" . preg_quote({STRING}, "/") . "/i", {CHECK0})'),
		RULE_IS_NOT_LIKE	=> array('check0' => 'username', 'function' => '!(preg_match("/" . preg_quote({STRING}, "/") . "/i", {CHECK0}))'),
		RULE_IS				=> array('check0' => 'username', 'function' => '{CHECK0} == {STRING}'),
		RULE_IS_NOT			=> array('check0' => 'username', 'function' => '{CHECK0} != {STRING}'),
		RULE_BEGINS_WITH	=> array('check0' => 'username', 'function' => 'preg_match("/^" . preg_quote({STRING}, "/") . "/i", {CHECK0})'),
		RULE_ENDS_WITH		=> array('check0' => 'username', 'function' => 'preg_match("/" . preg_quote({STRING}, "/") . "$/i", {CHECK0})'),
		RULE_IS_FRIEND		=> array('check0' => 'friend', 'function' => '{CHECK0} == 1'),
		RULE_IS_FOE			=> array('check0' => 'foe', 'function' => '{CHECK0} == 1'),
		RULE_IS_USER		=> array('check0' => 'author_id', 'function' => '{CHECK0} == {USER_ID}'),
		RULE_IS_GROUP		=> array('check0' => 'author_in_group', 'function' => 'in_array({GROUP_ID}, {CHECK0})'),
	),

	CHECK_MESSAGE	=> array(
		RULE_IS_LIKE		=> array('check0' => 'message_text', 'function' => 'preg_match("/" . preg_quote({STRING}, "/") . "/i", {CHECK0})'),
		RULE_IS_NOT_LIKE	=> array('check0' => 'message_text', 'function' => '!(preg_match("/" . preg_quote({STRING}, "/") . "/i", {CHECK0}))'),
		RULE_IS				=> array('check0' => 'message_text', 'function' => '{CHECK0} == {STRING}'),
		RULE_IS_NOT			=> array('check0' => 'message_text', 'function' => '{CHECK0} != {STRING}'),
	),

	CHECK_STATUS	=> array(
		RULE_ANSWERED		=> array('check0' => 'pm_replied', 'function' => '{CHECK0} == 1'),
		RULE_FORWARDED		=> array('check0' => 'pm_forwarded', 'function' => '{CHECK0} == 1'),
	),

	CHECK_TO		=> array(
		RULE_TO_GROUP		=> array('check0' => 'to', 'check1' => 'bcc', 'check2' => 'user_in_group', 'function' => 'in_array("g_" . {CHECK2}, {CHECK0}) || in_array("g_" . {CHECK2}, {CHECK1})'),
		RULE_TO_ME			=> array('check0' => 'to', 'check1' => 'bcc', 'function' => 'in_array("u_" . $user_id, {CHECK0}) || in_array("u_" . $user_id, {CHECK1})'),
	)
);

/**
* This is for defining which condition fields to show for which Rule
*/
$global_rule_conditions = array(
	RULE_IS_LIKE		=> 'text',
	RULE_IS_NOT_LIKE	=> 'text',
	RULE_IS				=> 'text',
	RULE_IS_NOT			=> 'text',
	RULE_BEGINS_WITH	=> 'text',
	RULE_ENDS_WITH		=> 'text',
	RULE_IS_USER		=> 'user',
	RULE_IS_GROUP		=> 'group'
);

/**
* Get all folder
*/
function get_folder($user_id, $folder_id = false)
{
	global $_CLASS;

	$folder = array();

	// Get folder informations
	$sql = 'SELECT folder_id, COUNT(*) as num_messages, SUM(pm_unread) as num_unread
		FROM ' . FORUMS_PRIVMSGS_TO_TABLE . "
		WHERE user_id = $user_id
			AND folder_id <> " . PRIVMSGS_NO_BOX . '
		GROUP BY folder_id';
	$result = $_CLASS['core_db']->query($sql);

	$num_messages = $num_unread = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$num_messages[(int) $row['folder_id']] = $row['num_messages'];
		$num_unread[(int) $row['folder_id']] = $row['num_unread'];
	}
	$_CLASS['core_db']->free_result($result);

	// Make sure the default boxes are defined
	$available_folder = array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX);

	foreach ($available_folder as $default_folder)
	{
		if (!isset($num_messages[$default_folder]))
		{
			$num_messages[$default_folder] = 0;
		}

		if (!isset($num_unread[$default_folder]))
		{
			$num_unread[$default_folder] = 0;
		}
	}

	// Adjust unread status for outbox
	$num_unread[PRIVMSGS_OUTBOX] = $num_messages[PRIVMSGS_OUTBOX];
	
	$folder[PRIVMSGS_INBOX] = array(
		'folder_name'		=> $_CLASS['core_user']->lang['PM_INBOX'],
		'num_messages'		=> $num_messages[PRIVMSGS_INBOX],
		'unread_messages'	=> $num_unread[PRIVMSGS_INBOX]
	);

	// Custom Folder
	$sql = 'SELECT folder_id, folder_name, pm_count
		FROM ' . FORUMS_PRIVMSGS_FOLDER_TABLE . "
			WHERE user_id = $user_id";
	$result = $_CLASS['core_db']->query($sql);
			
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$folder[$row['folder_id']] = array(
			'folder_name'		=> $row['folder_name'],
			'num_messages'		=> $row['pm_count'],
			'unread_messages'	=> isset($num_unread[$row['folder_id']]) ? $num_unread[$row['folder_id']] : 0
		);
	}
	$_CLASS['core_db']->free_result($result);

	$folder[PRIVMSGS_OUTBOX] = array(
		'folder_name'		=> $_CLASS['core_user']->lang['PM_OUTBOX'],
		'num_messages'		=> $num_messages[PRIVMSGS_OUTBOX],
		'unread_messages'	=> $num_unread[PRIVMSGS_OUTBOX]
	);

	$folder[PRIVMSGS_SENTBOX] = array(
		'folder_name'		=> $_CLASS['core_user']->lang['PM_SENTBOX'],
		'num_messages'		=> $num_messages[PRIVMSGS_SENTBOX],
		'unread_messages'	=> $num_unread[PRIVMSGS_SENTBOX]
	);

	// Define Folder Array for template designers (and for making custom folders usable by the template too)
	foreach ($folder as $f_id => $folder_ary)
	{
		$folder_id_name = ($f_id == PRIVMSGS_INBOX) ? 'inbox' : (($f_id == PRIVMSGS_OUTBOX) ? 'outbox' : 'sentbox');

		$_CLASS['core_template']->assign_vars_array('folder', array(
			'FOLDER_ID'			=> $f_id,
			'FOLDER_NAME'		=> $folder_ary['folder_name'],
			'NUM_MESSAGES'		=> $folder_ary['num_messages'],
			'UNREAD_MESSAGES'	=> $folder_ary['unread_messages'],

			'U_FOLDER'			=> ($f_id > 0) ? generate_link('control_panel&amp;i=pm&amp;folder=' . $f_id) : generate_link('control_panel&amp;i=pm&amp;folder=' . $folder_id_name),

			'S_CUR_FOLDER'		=> ($f_id === $folder_id),
			'S_UNREAD_MESSAGES'	=> ($folder_ary['unread_messages']),
			'S_CUSTOM_FOLDER'	=> ($f_id > 0)
		));
	}

	return $folder;
}

/**
* Delete Messages From Sentbox
* we are doing this here because this saves us a bunch of checks and queries
*/
function clean_sentbox($num_sentbox_messages)
{
	global $_CLASS, $config;

	$message_limit = ($_CLASS['core_user']->data['user_message_limit']) ? $config['pm_max_msgs'] : $_CLASS['core_user']->data['user_message_limit'];
	
	// Check Message Limit - 
	if ($message_limit && $num_sentbox_messages > $message_limit)
	{
		// Delete old messages
		$sql = 'SELECT t.msg_id
			FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p
			WHERE t.msg_id = p.msg_id
				AND t.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND t.folder_id = ' . PRIVMSGS_SENTBOX . '
			ORDER BY p.message_time ASC';
		$result = $_CLASS['core_db']->query_limit($sql, ($num_sentbox_messages - $message_limit));

		$delete_ids = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$delete_ids[] = $row['msg_id'];
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_db']->transaction();

		delete_pm($_CLASS['core_user']->data['user_id'], $delete_ids, PRIVMSGS_SENTBOX);

		$_CLASS['core_db']->transaction('commit');
	}
}

/**
* Check Rule against Message Informations
*/
function check_rule(&$rules, &$rule_row, &$message_row, $user_id)
{
	global $_CLASS, $config;

	if (!isset($rules[$rule_row['rule_check']][$rule_row['rule_connection']]))
	{
		return false;
	}

	$check_ary = $rules[$rule_row['rule_check']][$rule_row['rule_connection']];

	// Replace Check Literals
	$evaluate = $check_ary['function'];
	$evaluate = preg_replace('/{(CHECK[0-9])}/', '$message_row[$check_ary[strtolower("\1")]]', $evaluate);

	// Replace Rule Literals
	$evaluate = preg_replace('/{(STRING|USER_ID|GROUP_ID)}/', '$rule_row["rule_" . strtolower("\1")]', $evaluate);

	// Eval Statement
	$result = false;
	eval('$result = (' . $evaluate . ') ? true : false;');
		
	if (!$result)
	{
		return false;
	}

	switch ($rule_row['rule_action'])
	{
		case ACTION_PLACE_INTO_FOLDER:
			return array('action' => $rule_row['rule_action'], 'folder_id' => $rule_row['rule_folder_id']);
		break;

		case ACTION_MARK_AS_READ:
		case ACTION_MARK_AS_IMPORTANT:
			return array('action' => $rule_row['rule_action'], 'pm_unread' => $message_row['pm_unread'], 'pm_marked' => $message_row['pm_marked']);
		break;

		case ACTION_DELETE_MESSAGE:
			return array('action' => $rule_row['rule_action'], 'pm_unread' => $message_row['pm_unread'], 'pm_marked' => $message_row['pm_marked']);
		break;

		default:
			return false;
		break;
	}

	return false;
}

/**
* Place new messages into appropiate folder
*/
function place_pm_into_folder(&$global_privmsgs_rules, $release = false)
{
	global $_CLASS, $config;

	if (!$_CLASS['core_user']->data['user_new_privmsg'])
	{
		return 0;
	}
$_CLASS['core_user']->data['user_message_rules'] = 0;
$_CLASS['core_user']->data['user_full_folder'] = FULL_FOLDER_NONE;

	$user_new_privmsg = (int) $_CLASS['core_user']->data['user_new_privmsg'];
	$user_message_rules = (int) $_CLASS['core_user']->data['user_message_rules'];
	$user_id = (int) $_CLASS['core_user']->data['user_id'];

	$action_ary = $move_into_folder = array();

	if ($release)
	{
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . ' 
			SET folder_id = ' . PRIVMSGS_NO_BOX . '
			WHERE folder_id = ' . PRIVMSGS_HOLD_BOX . "
				AND user_id = $user_id";
		$_CLASS['core_db']->query($sql);
	}

	// Get those messages not yet placed into any box
	$retrieve_sql = 'SELECT t.*, p.*, u.username, u.user_id, u.user_group as group_id
		FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . CORE_USERS_TABLE . " u
		WHERE t.user_id = $user_id
			AND p.author_id = u.user_id
			AND t.folder_id = " . PRIVMSGS_NO_BOX . '
			AND t.msg_id = p.msg_id';

	if ($user_message_rules)
	{
		$result = $_CLASS['core_db']->query($retrieve_sql);

		while($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$action_ary[$row['msg_id']][] = array('action' => false);
			$move_into_folder[PRIVMSGS_INBOX][] = $row['msg_id'];
		}
		$_CLASS['core_db']->free_result($result);
	}
	else
	{
		$user_rules = $zebra = $check_rows = array();
		$user_ids = $memberships = array();

		// First of all, grab all rules and retrieve friends/foes
		$sql = 'SELECT * 
			FROM ' . FORUMS_PRIVMSGS_RULES_TABLE . "
			WHERE user_id = $user_id";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$user_rules[] = $row;
		}
		$_CLASS['core_db']->free_result($result);

		if (sizeof($user_rules))
		{
			$sql = 'SELECT zebra_id, friend, foe
				FROM ' . ZEBRA_TABLE . "
				WHERE user_id = $user_id";
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$zebra[$row['zebra_id']] = $row;
			}
			$_CLASS['core_db']->free_result($result);
		}

		// Now build a bare-bone check_row array
		$result = $_CLASS['core_db']->query($retrieve_sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$check_rows[] = array_merge($row, array(
				'to'				=> explode(':', $row['to_address']),
				'bcc'				=> explode(':', $row['bcc_address']),
				'friend'			=> (isset($zebra[$row['author_id']])) ? $zebra[$row['author_id']]['friend'] : 0,
				'foe'				=> (isset($zebra[$row['author_id']])) ? $zebra[$row['author_id']]['foe'] : 0,
				'user_in_group'		=> array($_CLASS['core_user']->data['user_group']),
				'author_in_group'	=> array())
			);

			$user_ids[] = $row['user_id'];
		}
		$_CLASS['core_db']->free_result($result);

		// Retrieve user memberships
		if (sizeof($user_ids))
		{
			$sql = 'SELECT *
				FROM ' . CORE_GROUPS_MEMBERS_TABLE . '
				WHERE user_id IN (' . implode(', ', $user_ids) . ')
					AND member_status = '.STATUS_ACTIVE;
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$memberships[$row['user_id']][] = $row['group_id'];
			}
			$_CLASS['core_db']->free_result($result);
		}

		// Now place into the appropiate folder
		foreach ($check_rows as $row)
		{
			// Add membership if set
			if (isset($memberships[$row['author_id']]))
			{
				$row['author_in_group'] = $memberships[$row['user_id']];
			}

			// Check Rule - this should be very quick since we have all informations we need
			$is_match = false;
			foreach ($user_rules as $rule_row)
			{
				if (($action = check_rule($global_privmsgs_rules, $rule_row, $row, $user_id)) !== false)
				{
					$is_match = true;
					$action_ary[$row['msg_id']][] = $action;
				}
			}

			if (!$is_match)
			{
				$action_ary[$row['msg_id']][] = array('action' => false);
				$move_into_folder[PRIVMSGS_INBOX][] = $row['msg_id'];
			}
		}

		unset($user_rules, $zebra, $check_rows, $user_ids, $memberships);
	}

	// We place actions into arrays, to save queries.
	$num_new = $num_unread = 0;
	$sql = $unread_ids = $delete_ids = $important_ids = array();

	foreach ($action_ary as $msg_id => $msg_ary)
	{
		// It is allowed to execute actions more than once, except placing messages into folder
		$folder_action = false;

		foreach ($msg_ary as $pos => $rule_ary)
		{
			if ($folder_action && $rule_ary['action'] == ACTION_PLACE_INTO_FOLDER)
			{
				continue;
			}

			switch ($rule_ary['action'])
			{
				case ACTION_PLACE_INTO_FOLDER:
					// Folder actions have precedence, so we will remove any other ones
					$folder_action = true;
					$_folder_id = (int) $rule_ary['folder_id'];
					$move_into_folder = array();
					$move_into_folder[$_folder_id][] = $msg_id;
					$num_new++;
				break;

				case ACTION_MARK_AS_READ:
					if ($rule_ary['pm_unread'])
					{
						$unread_ids[] = $msg_id;
					}

					if (!$folder_action)
					{
						$move_into_folder[PRIVMSGS_INBOX][] = $msg_id;
					}
				break;

				case ACTION_DELETE_MESSAGE:
					$delete_ids[] = $msg_id;
				break;

				case ACTION_MARK_AS_IMPORTANT:
					if (!$rule_ary['pm_marked'])
					{
						$important_ids[] = $msg_id;
					}

					if (!$folder_action)
					{
						$move_into_folder[PRIVMSGS_INBOX][] = $msg_id;
					}
				break;
			}
		}
	}

//	$num_new += count(array_unique($delete_ids));
//	$num_unread += count(array_unique($delete_ids));
	$num_unread += count(array_unique($unread_ids));

	// Do not change the order of processing
	// The number of queries needed to be executed here highly depends on the defined rules and are
	// only gone through if new messages arrive.
	$num_not_moved = 0;

	$_CLASS['core_db']->transaction();

	// Delete messages
	if (!empty($delete_ids))
	{
		delete_pm($user_id, $delete_ids, PRIVMSGS_NO_BOX);
	}

	// Set messages to Unread
	if (!empty($unread_ids))
	{
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . ' 
			SET pm_unread = 0
			WHERE msg_id IN (' . implode(', ', $unread_ids) . ")
				AND user_id = $user_id
				AND folder_id = " . PRIVMSGS_NO_BOX;
		$_CLASS['core_db']->query($sql);
	}

	// mark messages as important
	if (!empty($important_ids))
	{
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . '
			SET pm_marked = !pm_marked
			WHERE folder_id = ' . PRIVMSGS_NO_BOX . "
				AND user_id = $user_id
				AND msg_id IN (" . implode(', ', $important_ids) . ')';
		$_CLASS['core_db']->query($sql);
	}

	// Move into folder
	$folder = array();
	// Here we have ideally only one folder to move into
	foreach ($move_into_folder as $folder_id => $msg_ary)
	{
		$dest_folder = $folder_id;
		$full_folder_action = FULL_FOLDER_NONE;

/////  KILLL
$_CLASS['core_user']->data['message_limit'] = 0;

		// Check Message Limit - we calculate with the complete array, most of the time it is one message
		// But we are making sure that the other way around works too (more messages in queue than allowed to be stored)
		if ($_CLASS['core_user']->data['message_limit'] && $folder[$folder_id] && ($folder[$folder_id] + sizeof($msg_ary)) > $_CLASS['core_user']->data['message_limit'])
		{
			// Determine Full Folder Action - we need the move to folder id later eventually
			$full_folder_action = ($_CLASS['core_user']->data['user_full_folder'] == FULL_FOLDER_NONE) ? ($config['full_folder_action'] - (FULL_FOLDER_NONE*(-1))) : $_CLASS['core_user']->data['user_full_folder'];
	
			if ($full_folder_action >= 0 && ($folder[$full_folder_action] + sizeof($msg_ary)) > $_CLASS['core_user']->data['message_limit'])
			{
				$full_folder_action = $config['full_folder_action'] - (FULL_FOLDER_NONE*(-1));
			}

			// If Full Folder Action is to move to another folder, we simply adjust the destination folder
			if ($full_folder_action >= 0)
			{
				$dest_folder = $full_folder_action;
			}
			else if ($full_folder_action == FULL_FOLDER_DELETE)
			{
				// Delete some messages ;)
				$sql = 'SELECT t.msg_id
					FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . " p
					WHERE t.msg_id = p.msg_id
						AND t.user_id = $user_id
						AND t.folder_id = $dest_folder
					ORDER BY p.message_time ASC";
				$result = $_CLASS['core_db']->query_limit($sql, (($folder[$dest_folder] + sizeof($msg_ary)) - $_CLASS['core_user']->data['message_limit']));

				$delete_ids = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$delete_ids[] = $row['msg_id'];
				}
				$_CLASS['core_db']->free_result($result);
				delete_pm($user_id, $delete_ids, $dest_folder);
			}
		}
		
		// 
		if ($full_folder_action == FULL_FOLDER_HOLD)
		{
			$num_not_moved += sizeof($msg_ary);

			$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . ' 
				SET folder_id = ' . PRIVMSGS_HOLD_BOX . '
				WHERE folder_id = ' . PRIVMSGS_NO_BOX . "
					AND user_id = $user_id
					AND msg_id IN (" . implode(', ', $msg_ary) . ')';
			$_CLASS['core_db']->query($sql);
		}
		else
		{
			$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . " 
				SET folder_id = $dest_folder, pm_new = 0
				WHERE folder_id = " . PRIVMSGS_NO_BOX . "
					AND user_id = $user_id
					AND pm_new = 1
					AND msg_id IN (" . implode(', ', $msg_ary) . ')';
			$_CLASS['core_db']->query($sql);

			if ($dest_folder != PRIVMSGS_INBOX)
			{
				$sql = 'UPDATE ' . FORUMS_PRIVMSGS_FOLDER_TABLE . '
					SET pm_count = pm_count + ' . (int) $_CLASS['core_db']->affected_rows() . "
					WHERE folder_id = $dest_folder
						AND user_id = $user_id";
				$_CLASS['core_db']->query($sql);
			}
			else
			{
				$num_new += (int) $_CLASS['core_db']->affected_rows();
			}
		}
	}

	if (sizeof($action_ary))
	{
		// Move from OUTBOX to SENTBOX
		// We are not checking any full folder status here... SENTBOX is a special treatment (old messages get deleted)
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . ' 
			SET folder_id = ' . PRIVMSGS_SENTBOX . '
			WHERE folder_id = ' . PRIVMSGS_OUTBOX . '
				AND msg_id IN (' . implode(', ', array_keys($action_ary)) . ')';
		$_CLASS['core_db']->query($sql);
	}

	// Update unread and new status field
	if ($num_unread || $num_new)
	{
		$set_sql = ($num_unread) ? 'user_unread_privmsg = user_unread_privmsg - ' . $num_unread : '';
		if ($num_new)
		{
			$set_sql .= ($set_sql != '') ? ', ' : '';
			$set_sql .= 'user_new_privmsg = user_new_privmsg - ' . $num_new;
		}
		
		$_CLASS['core_db']->query('UPDATE ' . CORE_USERS_TABLE . " SET $set_sql WHERE user_id = $user_id");
		$_CLASS['core_user']->data['user_new_privmsg'] -= $num_new;
		$_CLASS['core_user']->data['user_unread_privmsg'] -= $num_unread;
	}

	$_CLASS['core_db']->transaction('commit');

	return $num_not_moved;
}	
	

/**
* Move PM from one to another folder
*/
function move_pm($user_id, $message_limit, $move_msg_ids, $dest_folder, $cur_folder_id)
{
	global $_CLASS;
	
	if (!is_array($move_msg_ids))
	{
		$move_msg_ids = array($move_msg_ids);
	}
	
	if (empty($move_msg_ids) && !in_array($dest_folder, array(PRIVMSGS_NO_BOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX)) && 
		!in_array($cur_folder_id, array(PRIVMSGS_NO_BOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX)) && $cur_folder_id != $dest_folder)
	{
		return 0;
	}
	
	// We have to check the destination folder ;)
	if ($dest_folder != PRIVMSGS_INBOX)
	{
		$sql = 'SELECT folder_id, folder_name, pm_count
			FROM ' . FORUMS_PRIVMSGS_FOLDER_TABLE . "
			WHERE folder_id = $dest_folder
				AND user_id = $user_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row)
		{
			trigger_error('NOT_AUTHORIZED');
		}

		if ($row['pm_count'] + count($move_msg_ids) > $message_limit)
		{
			$message = sprintf($_CLASS['core_user']->lang['NOT_ENOUGH_SPACE_FOLDER'], $row['folder_name']) . '<br /><br />';
			$message .= sprintf($_CLASS['core_user']->lang['CLICK_RETURN_FOLDER'], '<a href="'.generate_link('control_panel&amp;i=pm&amp;folder='.$row['folder_id']).'">', '</a>', $row['folder_name']);

			trigger_error($message);
		}
	}
	else
	{
		$sql = 'SELECT COUNT(*) as num_messages
			FROM ' . FORUMS_PRIVMSGS_TO_TABLE . '
			WHERE folder_id = ' . PRIVMSGS_INBOX . "
				AND user_id = $user_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row || ((int) $row['num_messages'] + count($move_msg_ids)) > $message_limit)
		{
			$message = sprintf($_CLASS['core_user']->lang['NOT_ENOUGH_SPACE_FOLDER'], $_CLASS['core_user']->lang['PM_INBOX']) . '<br /><br />';
			$message .= sprintf($_CLASS['core_user']->lang['CLICK_RETURN_FOLDER'], '<a href="'.generate_link('control_panel&amp;i=pm&amp;folder=inbox').'">', '</a>', $_CLASS['core_user']->lang['PM_INBOX']);
			trigger_error($message);
		}
	}

	$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . "
		SET folder_id = $dest_folder
		WHERE folder_id = $cur_folder_id
			AND user_id = $user_id
			AND msg_id IN (" . implode(', ', $move_msg_ids) . ')';

	$_CLASS['core_db']->query($sql);
	$num_moved = $_CLASS['core_db']->affected_rows();

	// Update pm counts
	if ($num_moved)
	{
		if (!in_array($cur_folder_id, array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX)))
		{
			$sql = 'UPDATE ' . FORUMS_PRIVMSGS_FOLDER_TABLE . "
				SET pm_count = pm_count - $num_moved
				WHERE folder_id = $cur_folder_id
					AND user_id = $user_id";
			$_CLASS['core_db']->query($sql);
		}

		if ($dest_folder !== PRIVMSGS_INBOX)
		{
			$sql = 'UPDATE ' . FORUMS_PRIVMSGS_FOLDER_TABLE . "
				SET pm_count = pm_count + $num_moved
				WHERE folder_id = $dest_folder
					AND user_id = $user_id";
			$_CLASS['core_db']->query($sql);
		}
	}

	return $num_moved;
}

function set_read_status($read, $msg_id, $user_id, $folder_id)
{
	global $_CLASS;

	if (empty($msg_id))
	{
		return;
	}

	$read = ($read) ? 0 : 1;

	$sql_msg = is_array($msg_id) ? 'IN ('.implode(', ', $msg_id).')' : '= '.(int) $msg_id;

	$_CLASS['core_db']->transaction();

	$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . " 
		SET pm_unread = $read
		WHERE msg_id $sql_msg
			AND user_id = $user_id
			AND folder_id = $folder_id";
	$_CLASS['core_db']->query($sql);
	
	$count = $_CLASS['core_db']->affected_rows();

	if ($count)
	{
		$sql = 'UPDATE ' . CORE_USERS_TABLE . ' 
			SET user_unread_privmsg = user_unread_privmsg '.(($read) ? '+ ' : '- '). " $count
			WHERE user_id = $user_id";
		$_CLASS['core_db']->query($sql);
		
		if ($_CLASS['core_user']->data['user_id'] == $user_id)
		{
			$_CLASS['core_user']->data['user_unread_privmsg'] = ($_CLASS['core_user']->data['user_unread_privmsg'] + (($read) ? $count : -$count));
		}
	}

	$_CLASS['core_db']->transaction('commit');
}

/**
* Handle all actions possible with marked messages
*/
function handle_mark_actions($user_id, $mark_action, $msg_ids, $cur_folder_id)
{
	global $_CLASS;

	if (empty($msg_ids))
	{
		return;
	}

	switch ($mark_action)
	{
		case 'mark_important':

			$mark_list = array();

			$sql = 'SELECT msg_id, pm_marked FROM ' . FORUMS_PRIVMSGS_TO_TABLE . "
				WHERE folder_id = $cur_folder_id
					AND user_id = $user_id
					AND msg_id IN (" . implode(', ', $msg_ids) . ')';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$row['pm_marked'] = ($row['pm_marked']) ? 0 : 1;
				$mark_list[$row['pm_marked']][] = $row['msg_id'];
			}
			$_CLASS['core_db']->free_result($result);

			if (empty($mark_list))
			{
				break;
			}

			$_CLASS['core_db']->transaction();

			foreach ($mark_list as $mark => $ids)
			{
				$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . "
					SET pm_marked = $mark
					WHERE msg_id IN (" . implode(', ', $ids) . ')';
				$_CLASS['core_db']->query($sql);
			}

			$_CLASS['core_db']->transaction('commit');
		break;

		case 'delete_marked':
			$hidden_fields = array(
				'marked_msg_id'		=> $msg_ids,
				'cur_folder_id'		=> $cur_folder_id,
				'mark_option'		=> 'delete_marked',
				'submit_mark'		=> true
			);

			if (display_confirmation($_CLASS['core_user']->get_lang('DELETE_MARKED_PM'), generate_hidden_fields($hidden_fields)))
			{
				$_CLASS['core_db']->transaction();

				delete_pm($user_id, $msg_ids, $cur_folder_id);

				$_CLASS['core_db']->transaction('commit');

				$success_msg = (count($msg_ids) === 1) ? 'MESSAGE_DELETED' : 'MESSAGES_DELETED';
				$redirect = generate_link('control_panel&amp;i=pm&amp;folder='.$cur_folder_id);
				$_CLASS['core_display']->meta_refresh(3, $redirect);
				
				trigger_error($_CLASS['core_user']->lang[$success_msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FOLDER'], '<a href="' . $redirect . '">', '</a>'));
			}
		break;

		/*
		case 'export_as_xml':
		case 'export_as_csv':
		case 'export_as_txt':
			$export_as = str_replace('export_as_', '', $mark_action);
		break;
		*/

		default:
			return false;
		break;
	}

	return true;
}

/**
* Delete PM(s)
*/
function delete_pm($user_id, $msg_ids, $folder_id)
{
	global $_CLASS;

	$user_id	= (int) $user_id;
	$folder_id	= (int) $folder_id;

	if (!$user_id)
	{
		return false;
	}

	if (!is_array($msg_ids))
	{
		if (!$msg_ids)
		{
			return false;
		}

		$msg_ids = array($msg_ids);
	}

	if (empty($msg_ids))
	{
		return false;
	}

	// Get PM Informations for later deleting
	$sql = 'SELECT msg_id, pm_unread, pm_new
		FROM ' . FORUMS_PRIVMSGS_TO_TABLE . '
		WHERE msg_id IN (' . implode(', ', array_map('intval', $msg_ids)) . ")
			AND folder_id = $folder_id
			AND user_id = $user_id";
	$result = $_CLASS['core_db']->query($sql);

	$delete_rows = array();
	$num_unread = $num_new = $num_deleted = 0;
	
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$num_unread += ($row['pm_unread']) ? 1 : 0;
		$num_new += ($row['pm_new']) ? 1 : 0;

		$delete_rows[$row['msg_id']] = 1;
	}
	$_CLASS['core_db']->free_result($result);

	unset($msg_ids);

	if (empty($delete_rows))
	{
		return false;
	}

	// if no one has read the message yet (meaning it is in users outbox)
	// then mark the message as deleted...
	if ($folder_id === PRIVMSGS_OUTBOX)
	{
		// Remove PM from Outbox
		$sql = 'DELETE FROM ' . FORUMS_PRIVMSGS_TO_TABLE . "
			WHERE user_id = $user_id AND folder_id = " . PRIVMSGS_OUTBOX . '
				AND msg_id IN (' . implode(', ', array_keys($delete_rows)) . ')';
		$_CLASS['core_db']->query($sql);

		// Update PM Information for safety
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TABLE . " SET message_text = ''
			WHERE msg_id IN (" . implode(', ', array_keys($delete_rows)) . ')';
		$_CLASS['core_db']->query($sql);

		// Set delete flag for those intended to receive the PM
		// We do not remove the message actually, to retain some basic informations (sent time for example)
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . '
			SET deleted = 1
			WHERE msg_id IN (' . implode(', ', array_keys($delete_rows)) . ')';
		$_CLASS['core_db']->query($sql);

		$num_deleted = $_CLASS['core_db']->affected_rows();
	}
	else
	{
		// Delete Private Message Informations
		$sql = 'DELETE FROM ' . FORUMS_PRIVMSGS_TO_TABLE . "
			WHERE user_id = $user_id
				AND folder_id = $folder_id
				AND msg_id IN (" . implode(', ', array_keys($delete_rows)) . ')';
		$_CLASS['core_db']->query($sql);
		$num_deleted = $_CLASS['core_db']->affected_rows();
	}

	// if folder id is user defined folder then decrease pm_count
	if (!in_array($folder_id, array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX, PRIVMSGS_NO_BOX)))
	{
		$sql = 'UPDATE ' . FORUMS_PRIVMSGS_FOLDER_TABLE . " 
			SET pm_count = pm_count - $num_deleted 
			WHERE folder_id = $folder_id";
		$_CLASS['core_db']->query($sql);
	}

	// Update unread and new status field
	if ($num_unread || $num_new)
	{
		$set_sql = ($num_unread) ? 'user_unread_privmsg = user_unread_privmsg - ' . $num_unread : '';
		if ($num_new)
		{
			$set_sql .= ($set_sql != '') ? ', ' : '';
			$set_sql .= 'user_new_privmsg = user_new_privmsg - ' . $num_new;
		}

		$_CLASS['core_db']->query('UPDATE ' . CORE_USERS_TABLE . " SET $set_sql WHERE user_id = $user_id");

		$_CLASS['core_user']->data['user_new_privmsg'] -= $num_new;
		$_CLASS['core_user']->data['user_unread_privmsg'] -= $num_unread;
	}
	
	// Now we have to check which messages we can delete completely	
	$sql = 'SELECT msg_id 
		FROM ' . FORUMS_PRIVMSGS_TO_TABLE . '
		WHERE msg_id IN (' . implode(', ', array_keys($delete_rows)) . ')';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		unset($delete_rows[$row['msg_id']]);
	}
	$_CLASS['core_db']->free_result($result);

	$delete_ids = implode(', ', array_keys($delete_rows));

	if ($delete_ids)
	{
		$sql = 'DELETE FROM ' . FORUMS_PRIVMSGS_TABLE . '
			WHERE msg_id IN (' . $delete_ids . ')';
		$_CLASS['core_db']->query($sql);
	}

	return true;
}

/**
* Rebuild message header
*/
function rebuild_header($check_ary)
{
	$address = array();

	foreach ($check_ary as $check_type => $address_field)
	{
		// Split Addresses into users and groups
		preg_match_all('/:?(u|g)_([0-9]+):?/', $address_field, $match);

		$u = $g = array();
		foreach ($match[1] as $id => $type)
		{
			${$type}[] = (int) $match[2][$id];
		}

		foreach (array('u', 'g') as $type)
		{
			if (!empty($$type))
			{
				foreach ($$type as $id)
				{
					$address[$type][$id] = $check_type;
				}
			}
		}
	}

	return $address;
}

/**
* Print out/assign recipient informations
*/
function write_pm_addresses($check_ary, $author_id, $plaintext = false)
{
	global $_CLASS;

	$addresses = array();
	
	foreach ($check_ary as $check_type => $address_field)
	{
		if (!is_array($address_field))
		{
			// Split Addresses into users and groups
			preg_match_all('/:?(u|g)_([0-9]+):?/', $address_field, $match);
	
			$u = $g = array();
			foreach ($match[1] as $id => $type)
			{
				${$type}[] = (int) $match[2][$id];
			}
		}
		else
		{
			$u = $address_field['u'];
			$g = $address_field['g'];
		}

		$address = array();
		if (!empty($u))
		{
			$sql = 'SELECT user_id, username, user_colour 
				FROM ' . CORE_USERS_TABLE . '
				WHERE user_id IN (' . implode(', ', $u) . ')
					AND user_type = ' . USER_NORMAL;
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if ($check_type == 'to' || $author_id == $_CLASS['core_user']->data['user_id'] || $row['user_id'] == $_CLASS['core_user']->data['user_id'])
				{
					if ($plaintext)
					{
						$address[] = $row['username'];
					}
					else
					{
						$address['user'][$row['user_id']] = array('name' => $row['username'], 'colour' => $row['user_colour']);
					}
				}
			}
			$_CLASS['core_db']->free_result($result);
		}

		if (!empty($g))
		{
			if ($plaintext)
			{
				$sql = 'SELECT group_name
					FROM ' . GROUPS_TABLE . ' 
						WHERE group_id IN (' . implode(', ', $g) . ')';
				$result = $_CLASS['core_db']->query($sql);
		
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					if ($check_type == 'to' || $author_id == $_CLASS['core_user']->data['user_id'] || $row['user_id'] == $_CLASS['core_user']->data['user_id'])
					{
						$address[] = $row['group_name'];
					}
				}
				$_CLASS['core_db']->free_result($result);
			}
			else
			{
				$sql = 'SELECT g.group_id, g.group_name, g.group_colour, ug.user_id
					FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
						WHERE g.group_id IN (' . implode(', ', $g) . ')
						AND g.group_id = ug.group_id
						AND ug.user_pending = 0';
				$result = $_CLASS['core_db']->query($sql);
		
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					if (!isset($address['group'][$row['group_id']]))
					{
						if ($check_type == 'to' || $author_id == $_CLASS['core_user']->data['user_id'] || $row['user_id'] == $_CLASS['core_user']->data['user_id'])
						{
							$address['group'][$row['group_id']] = array('name' => $row['group_name'], 'colour' => $row['group_colour']);
						}
					}
	
					if (isset($address['user'][$row['user_id']]))
					{
						$address['user'][$row['user_id']]['in_group'] = $row['group_id'];
					}
				}
				$_CLASS['core_db']->free_result($result);
			}
		}

		if (!empty($address) && !$plaintext)
		{
			$_CLASS['core_template']->assign('S_' . strtoupper($check_type) . '_RECIPIENT', true);

			foreach ($address as $type => $adr_ary)
			{
				foreach ($adr_ary as $id => $row)
				{
					$_CLASS['core_template']->assign_vars_array($check_type . '_recipient', array(
						'NAME'		=> $row['name'],
						'IS_GROUP'	=> ($type === 'group'),
						'IS_USER'	=> ($type === 'user'),
						'COLOUR'	=> ($row['colour']) ? $row['colour'] : '',
						'UG_ID'		=> $id,
						'U_VIEW'	=> ($type === 'user') ? (($id != ANONYMOUS) ? generate_link('members_list&amp;mode=viewprofile&amp;u=' . $id) : '') : generate_link('members_list&amp;mode=group&amp;g=' . $id)
					));
				}
			}
		}

		$addresses[$check_type] = $address;
	}

	return $addresses;
}

/**
* Get folder status
*/
function get_folder_status($folder_id, $folder)
{
	global $_CLASS, $config;

	if (isset($folder[$folder_id]))
	{
		$folder = $folder[$folder_id];
	}
	else
	{
		return false;
	}

	$message_limit = (!$_CLASS['core_user']->data['user_message_limit']) ? $config['pm_max_msgs'] : $_CLASS['core_user']->data['user_message_limit'];

	$return = array(
		'folder_name'	=> $folder['folder_name'], 
		'cur'			=> $folder['num_messages'],
		'remaining'		=> $message_limit - $folder['num_messages'],
		'max'			=> $message_limit,
		'percent'		=> ($message_limit) ? round(($folder['num_messages'] / $message_limit) * 100) : 100
	);

	$return['message'] = sprintf($_CLASS['core_user']->lang['FOLDER_STATUS_MSG'], $return['percent'], $return['cur'], $return['max']);

	return $return;
}

//
// COMPOSE MESSAGES
//

/**
* Submit PM
*/
function submit_pm($mode, $subject, &$data, $update_message, $put_in_outbox = true)
{
	global $_CLASS, $config;

	// We do not handle erasing posts here
	if ($mode === 'delete')
	{
		return;
	}

	// Collect some basic informations about which tables and which rows to update/insert
	$sql_data = array();
	$root_level = 0;

	// Recipient Informations
	$recipients = $to = $bcc = array();

	if ($mode !== 'edit')
	{
		// Build Recipient List
		// u|g => array($user_id => 'to'|'bcc')
		$_types = array('u', 'g');
		foreach ($_types as $ug_type)
		{
			if (!empty($data['address_list'][$ug_type]))
			{
				foreach ($data['address_list'][$ug_type] as $id => $field)
				{
					$id = (int) $id;

					// Do not rely on the address list being "valid"
					if (!$id || ($ug_type === 'u' && $id == ANONYMOUS))
					{
						continue;
					}

					$field = ($field === 'to') ? 'to' : 'bcc';
					if ($ug_type == 'u')
					{
						$recipients[$id] = $field;
					}
					${$field}[] = $ug_type . '_' . $id;
				}
			}
		}

		if (!empty($data['address_list']['g']))
		{
			$sql = 'SELECT group_id, user_id
				FROM ' . USER_GROUP_TABLE . '
				WHERE group_id IN (' . implode(', ', array_keys($data['address_list']['g'])) . ')
					AND user_pending = 0';
			$result = $_CLASS['core_db']->query($sql);
	
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$field = ($data['address_list']['g'][$row['group_id']] === 'to') ? 'to' : 'bcc';
				$recipients[$row['user_id']] = $field;
			}
			$_CLASS['core_db']->free_result($result);
		}

		$recipients = array_unique($recipients);
		unset($recipients[ANONYMOUS]);

		if (empty($recipients))
		{
			trigger_error('NO_RECIPIENT');
		}
	}

	$sql = '';

	switch ($mode)
	{
		case 'reply':
		case 'quote':
			$root_level = ($data['reply_from_root_level']) ? $data['reply_from_root_level'] : $data['reply_from_msg_id']; 

			// Set message_replied switch for this user
			$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TO_TABLE . '
				SET replied = 1
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
					AND msg_id = ' . $data['reply_from_msg_id'];

		// no break

		case 'forward':
		case 'post':
			$sql_data = array(
				'root_level'		=> $root_level,
				'author_id'			=> $data['from_user_id'],
				'icon_id'			=> $data['icon_id'], 
				'author_ip' 		=> $data['from_user_ip'],
				'message_time'		=> $_CLASS['core_user']->time,
				'enable_bbcode' 	=> $data['enable_bbcode'],
				'enable_html' 		=> $data['enable_html'],
				'enable_smilies' 	=> $data['enable_smilies'],
				'enable_magic_url' 	=> $data['enable_urls'],
				'enable_sig' 		=> $data['enable_sig'],
				'message_subject'	=> $subject,
				'message_text' 		=> $data['message'],
				'message_attachment'=> empty($data['attachment_data']) ? 0 : 1,
				'bbcode_bitfield'	=> $data['bbcode_bitfield'],
				'bbcode_uid'		=> $data['bbcode_uid'],
				'to_address'		=> implode(':', $to),
				'bcc_address'		=> implode(':', $bcc)
			);
		break;

		case 'edit':
			$sql_data = array(
				'icon_id'			=> $data['icon_id'],
				'message_edit_time'	=> $_CLASS['core_user']->time,
				'enable_bbcode' 	=> $data['enable_bbcode'],
				'enable_html' 		=> $data['enable_html'],
				'enable_smilies' 	=> $data['enable_smilies'],
				'enable_magic_url' 	=> $data['enable_urls'],
				'enable_sig' 		=> $data['enable_sig'],
				'message_subject'	=> $subject,
				'message_text' 		=> $data['message'],
				'message_attachment'=> empty($data['attachment_data']) ? 0 : 1,
				'bbcode_bitfield'	=> $data['bbcode_bitfield'],
				'bbcode_uid'		=> $data['bbcode_uid']
			);
		break;
	}

	$_CLASS['core_db']->transaction();

	if (!empty($sql_data))
	{
		if ($mode === 'post' || $mode === 'reply' || $mode === 'quote' || $mode === 'forward')
		{
			$_CLASS['core_db']->query('INSERT INTO ' . FORUMS_PRIVMSGS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_data));
			$data['msg_id'] = $_CLASS['core_db']->insert_id(FORUMS_PRIVMSGS_TABLE, 'msg_id');
		}
		elseif ($mode === 'edit')
		{
			$sql = 'UPDATE ' . FORUMS_PRIVMSGS_TABLE . ' 
				SET message_edit_count = message_edit_count + 1, ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_data) . ' 
				WHERE msg_id = ' . $data['msg_id'];
			$_CLASS['core_db']->query($sql);
		}
	}
	
	if ($mode !== 'edit')
	{
		if ($sql)
		{
			$_CLASS['core_db']->query($sql);
		}
		unset($sql);

		$sql_array = array();
		foreach ($recipients as $user_id => $type)
		{
			$sql_array[] = array(
				'msg_id'		=> (int) $data['msg_id'],
				'user_id'		=> (int) $user_id,
				'author_id'		=> (int) $data['from_user_id'],
				'folder_id'		=> PRIVMSGS_NO_BOX,
				'pm_new'		=> 1,
				'pm_unread'		=> 1,
				'pm_forwarded'	=> ($mode == 'forward') ? 1 : 0
			);
		}

		if (!empty($sql_array))
		{
			$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_array, FORUMS_PRIVMSGS_TO_TABLE);
			unset($sql_array);
		}

		$sql = 'UPDATE ' . CORE_USERS_TABLE . ' 
			SET user_new_privmsg = user_new_privmsg + 1, user_unread_privmsg = user_unread_privmsg + 1
			WHERE user_id IN (' . implode(', ', array_keys($recipients)) . ')';
		$_CLASS['core_db']->query($sql);

		// Put PM into outbox
		if ($put_in_outbox)
		{
			$_CLASS['core_db']->query('INSERT INTO ' . FORUMS_PRIVMSGS_TO_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
				'msg_id'		=> (int) $data['msg_id'],
				'user_id'		=> (int) $data['from_user_id'],
				'author_id'		=> (int) $data['from_user_id'],
				'folder_id'		=> PRIVMSGS_OUTBOX,
				'pm_new'		=> 0,
				'pm_unread'		=> 0,
				'pm_forwarded'	=> ($mode == 'forward') ? 1 : 0)
			));
		}
	}

	// Set user last post time
	if ($mode === 'reply' || $mode === 'quote'|| $mode === 'quotepost' || $mode === 'forward' || $mode === 'post')
	{
		$sql = 'UPDATE ' . CORE_USERS_TABLE . "
			SET user_last_post_time = {$_CLASS['core_user']->time}
			WHERE user_id = " . $_CLASS['core_user']->data['user_id'];
		$_CLASS['core_db']->query($sql);
	}

	// Submit Attachments
	if (!empty($data['attachment_data']) && $data['msg_id'] && in_array($mode, array('post', 'reply', 'quote', 'quotepost', 'edit', 'forward')))
	{
		$space_taken = $files_added = 0;
		$orphan_rows = array();

		foreach ($data['attachment_data'] as $pos => $attach_row)
		{
			$orphan_rows[(int) $attach_row['attach_id']] = array();
		}

		if (sizeof($orphan_rows))
		{
			$sql = 'SELECT attach_id, filesize, physical_filename
				FROM ' . FORUMS_ATTACHMENTS_TABLE . '
				WHERE attach_id IN (' . implode(', ', array_keys($orphan_rows)) . ')
					AND in_message = 1
					AND is_orphan = 1
					AND poster_id = ' . $_CLASS['core_user']->data['user_id'];
			$result = $_CLASS['core_db']->query($sql);

			$orphan_rows = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$orphan_rows[$row['attach_id']] = $row;
			}
			$_CLASS['core_db']->free_result($result);
		}

		foreach ($data['attachment_data'] as $pos => $attach_row)
		{
			if ($attach_row['is_orphan'] && !in_array($attach_row['attach_id'], array_keys($orphan_rows)))
			{
				continue;
			}

			if (!$attach_row['is_orphan'])
			{
				// update entry in db if attachment already stored in db and filespace
				$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . " 
					SET comment = '" . $_CLASS['core_db']->sql_escape($attach_row['comment']) . "' 
					WHERE attach_id = " . (int) $attach_row['attach_id'] . '
						AND is_orphan = 0';
				$_CLASS['core_db']->query($sql);
			}
			else
			{
				// insert attachment into db
				if (!@file_exists(SITE_FILE_ROOT . $config['upload_path'] . '/' . basename($orphan_rows[$attach_row['attach_id']]['physical_filename'])))
				{
					continue;
				}

				$space_taken += $orphan_rows[$attach_row['attach_id']]['filesize'];
				$files_added++;

				$attach_sql = array(
					'post_msg_id'		=> $data['msg_id'],
					'topic_id'			=> 0,
					'is_orphan'			=> 0,
					'poster_id'			=> $data['from_user_id'],
					'attach_comment'	=> $attach_row['attach_comment'],
				);

				$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $attach_sql) . '
					WHERE attach_id = ' . $attach_row['attach_id'] . '
						AND is_orphan = 1
						AND poster_id = ' . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->query($sql);
			}
		}

		if ($space_taken && $files_added)
		{
			set_config('upload_dir_size', $config['upload_dir_size'] + $space_taken, true);
			set_config('num_files', $config['num_files'] + $files_added, true);
		}
	}

	$_CLASS['core_db']->transaction('commit');

	// Delete draft if post was loaded...
	$draft_id = request_var('draft_loaded', 0);

	if ($draft_id)
	{
		$sql = 'DELETE FROM ' . DRAFTS_TABLE . " 
			WHERE draft_id = $draft_id 
				AND user_id = " . $_CLASS['core_user']->data['user_id'];
		$_CLASS['core_db']->query($sql);
	}

	// Send Notifications
	if ($mode !== 'edit')
	{
	//unset($recipients[$_CLASS['core_user']->data['user_id']]);

		pm_notification($mode, $data['from_username'], $recipients, $subject, $data['message']);
	}

	return $data['msg_id'];
}

/**
* PM Notification
*/
function pm_notification($mode, $author, $recipients, $subject, $message)
{
	global $_CLASS, $_CORE_CONFIG, $config;

	unset($recipients[ANONYMOUS], $recipients[$_CLASS['core_user']->data['user_id']]);

	if (empty($recipients))
	{
		return;
	}

	$subject = censor_text($subject);

	$recipient_list = implode(', ', array_unique(array_keys($recipients)));

	$sql = 'SELECT user_id, username, user_email, user_lang, user_notify_pm, user_notify_type
		FROM ' . CORE_USERS_TABLE . "
		WHERE user_id IN ($recipient_list)";
	$result = $_CLASS['core_db']->query($sql);

	$user_list = array();
// add lang support
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($row['user_notify_pm'])
		{
			$user_list[] = $row;
		}
	}
	$_CLASS['core_db']->free_result($result);
	
	if (empty($user_list))
	{
		return;
	}

	$email_sig = str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']);

	require_once SITE_FILE_ROOT.'includes/mailer.php';
	$mailer = new core_mailer;

	$count = count($user_list);
	for ($i = 0; $i < $count; $i++)
	{
		$mailer->to($user_list[$i]['user_email'], $user_list[$i]['username']);
	}

	$mailer->subject('New Private Message has arrived');

	$_CLASS['core_template']->assign_array(array(
		'EMAIL_SIG'		=> $email_sig,
		'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
		'SUBJECT'		=> html_entity_decode($subject),
		'AUTHOR_NAME'	=> html_entity_decode($author),

		'LINK_INBOX'	=> generate_link('control_panel&i=pm&mode=unread', array('full' => true, 'sid' => true))
	));

	$mailer->message = trim($_CLASS['core_template']->display('email/control_panel/pm_notify.txt', true));

	if (!$mailer->send())
	{
		//echo $mailer->error;
	}
}

?>