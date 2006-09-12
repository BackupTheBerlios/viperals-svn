<?php
// -------------------------------------------------------------
//
// $Id: admin_permissions.php,v 1.22 2004/05/26 18:55:25 acydburn Exp $
//
// FILENAME  : admin_permissions.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

$u_action = '';
$permission_dropdown = '';
$mode = request_var('mode', 'view_forum_local');

load_class(SITE_FILE_ROOT.'includes/forums/admin/auth.php', 'forums_auth_admin');

$_CLASS['core_user']->add_lang('admin_permissions', 'forums');
$_CLASS['core_user']->add_lang('admin_permissions_phpbb', 'forums');


// Trace has other vars
if ($mode === 'trace')
{
	$user_id = request_var('u', 0);
	$forum_id = request_var('f', 0);
	$permission = request_var('auth', '');

	if ($user_id && isset($_CLASS['forums_auth_admin']->option_ids[$permission]) && $_CLASS['forums_auth']->acl_get('a_viewauth'))
	{
		$page_title = sprintf($_CLASS['core_user']->lang['TRACE_PERMISSION'], $_CLASS['core_user']->lang['acl_' . $permission]['lang']);
		permission_trace($user_id, $forum_id, $permission);

		$_CLASS['core_display']->display($page_title, 'modules/forums/admin/permission_trace.html');
	}

	trigger_error('NO_MODE');
}

// Set some vars
$action = get_variable('action', 'REQUEST', false, 'array');

if ($action)
{
	$action = key($action);
}

$action = isset($_POST['psubmit']) ? 'apply_permissions' : $action;

$all_forums = request_var('all_forums', 0);
$subforum_id = request_var('subforum_id', 0);
$forum_id = request_var('forum_id', array(0));

$username = request_var('username', array(''));
$usernames = request_var('usernames', '');
$user_id = request_var('user_id', array(0));

$group_id = request_var('group_id', array(0));
$select_all_groups = request_var('select_all_groups', 0);

// If select all groups is set, we pre-build the group id array (this option is used for other screens to link to the permission settings screen)
if ($select_all_groups)
{
	// Add default groups to selection
	$sql_and = (!$config['coppa_enable']) ? " AND group_name NOT IN ('INACTIVE_COPPA', 'REGISTERED_COPPA')" : '';

	$sql = 'SELECT group_id
		FROM ' . CORE_GROUPS_TABLE . '
		WHERE group_type = ' . GROUP_SPECIAL . "
		$sql_and";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$group_id[] = $row['group_id'];
	}
	$_CLASS['core_db']->free_result($result);
}

// Map usernames to ids and vice versa
if ($usernames)
{
	$username = explode("\n", $usernames);
}
unset($usernames);

if (sizeof($username) && !sizeof($user_id))
{
	user_get_id_name($user_id, $username);

	if (!sizeof($user_id))
	{
		trigger_error($_CLASS['core_user']->lang['SELECTED_USER_NOT_EXIST'] . adm_back_link($u_action));
	}
}
unset($username);

// Build forum ids (of all forums are checked or subforum listing used)
if ($all_forums)
{
	$sql = 'SELECT forum_id
		FROM ' . FORUMS_FORUMS_FORUMS_TABLE . '
		ORDER BY left_id';
	$result = $_CLASS['core_db']->query($sql);

	$forum_id = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$forum_id[] = $row['forum_id'];
	}
	$_CLASS['core_db']->free_result($result);
}
else if ($subforum_id)
{
	$forum_id = array();
	foreach (get_forum_branch($subforum_id, 'children') as $row)
	{
		$forum_id[] = $row['forum_id'];
	}
}

// Define some common variables for every mode
$error = array();

$permission_scope = (strpos($mode, '_global') !== false) ? 'global' : 'local';

$_CLASS['core_template']->assign_array(array(
	'S_SELECT_USERGROUP'		=> false,
	'S_SELECT_USERGROUP_VIEW'	=> false,
	'S_SETTING_PERMISSIONS'		=> false,
	'S_VIEWING_PERMISSIONS'		=> false,
	'S_SELECT_FORUM'			=> false,
	'S_SELECT_GROUP'			=> false,
	'S_SELECT_USER'				=> false,
));
			
// Showing introductionary page?
if ($mode === 'intro')
{
	$page_title = 'ACP_PERMISSIONS';

	$_CLASS['core_template']->assign('S_INTRO', true);

	$_CLASS['core_display']->display($page_title, 'modules/forums/admin/acp_permissions.html');

	return;
}
else
{
	$_CLASS['core_template']->assign('S_INTRO', false);
}

switch ($mode)
{
	case 'setting_user_global':
	case 'setting_group_global':
		$permission_dropdown = array('u_', 'm_', 'a_');
		$permission_victim = ($mode === 'setting_user_global') ? array('user') : array('group');
		$page_title = ($mode === 'setting_user_global') ? 'ACP_USERS_PERMISSIONS' : 'ACP_GROUPS_PERMISSIONS';
	break;

	case 'setting_user_local':
	case 'setting_group_local':
		$permission_dropdown = array('f_', 'm_');
		$permission_victim = ($mode === 'setting_user_local') ? array('user', 'forums') : array('group', 'forums');
		$page_title = ($mode === 'setting_user_local') ? 'ACP_USERS_FORUM_PERMISSIONS' : 'ACP_GROUPS_FORUM_PERMISSIONS';
	break;

	case 'setting_admin_global':
	case 'setting_mod_global':
		$permission_dropdown = (strpos($mode, '_admin_') !== false) ? array('a_') : array('m_');
		$permission_victim = array('usergroup');
		$page_title = ($mode === 'setting_admin_global') ? 'ACP_ADMINISTRATORS' : 'ACP_GLOBAL_MODERATORS';
	break;

	case 'setting_mod_local':
	case 'setting_forum_local':
		$permission_dropdown = ($mode === 'setting_mod_local') ? array('m_') : array('f_');
		$permission_victim = array('forums', 'usergroup');
		$page_title = ($mode === 'setting_mod_local') ? 'ACP_FORUM_MODERATORS' : 'ACP_FORUM_PERMISSIONS';
	break;

	case 'view_admin_global':
	case 'view_user_global':
	case 'view_mod_global':
		$permission_dropdown = ($mode === 'view_admin_global') ? array('a_') : (($mode === 'view_user_global') ? array('u_') : array('m_'));
		$permission_victim = array('usergroup_view');
		$page_title = ($mode === 'view_admin_global') ? 'ACP_VIEW_ADMIN_PERMISSIONS' : (($mode === 'view_user_global') ? 'ACP_VIEW_USER_PERMISSIONS' : 'ACP_VIEW_GLOBAL_MOD_PERMISSIONS');
	break;

	case 'view_mod_local':
	case 'view_forum_local':
		$permission_dropdown = ($mode === 'view_mod_local') ? array('m_') : array('f_');
		$permission_victim = array('forums', 'usergroup_view');
		$page_title = ($mode === 'view_mod_local') ? 'ACP_VIEW_FORUM_MOD_PERMISSIONS' : 'ACP_VIEW_FORUM_PERMISSIONS';
	break;

	default:
		trigger_error('INVALID_MODE');
	break;
}

$_CLASS['core_template']->assign_array(array(
	'L_TITLE'		=> $_CLASS['core_user']->get_lang($page_title),
	'L_EXPLAIN'		=> $_CLASS['core_user']->get_lang($page_title . '_EXPLAIN')
));

// Get permission type
$permission_type = request_var('type', $permission_dropdown[0]);

if (!in_array($permission_type, $permission_dropdown))
{
	trigger_error($_CLASS['core_user']->lang['WRONG_PERMISSION_TYPE'] . adm_back_link($u_action));
}


// Handle actions
if (strpos($mode, 'setting_') === 0 && $action)
{
	switch ($action)
	{
		case 'delete':
			// All users/groups selected?
			$all_users = (isset($_POST['all_users'])) ? true : false;
			$all_groups = (isset($_POST['all_groups'])) ? true : false;

			if ($all_users || $all_groups)
			{
				$items = retrieve_defined_user_groups($permission_scope, $forum_id, $permission_type);

				if ($all_users && sizeof($items['user_ids']))
				{
					$user_id = $items['user_ids'];
				}
				else if ($all_groups && sizeof($items['group_ids']))
				{
					$group_id = $items['group_ids'];
				}
			}

			if (sizeof($user_id) || sizeof($group_id))
			{
				remove_permissions($mode, $permission_type, $_CLASS['forums_auth_admin'], $user_id, $group_id, $forum_id);
			}
			else
			{
				trigger_error($_CLASS['core_user']->lang['NO_USER_GROUP_SELECTED'] . adm_back_link($u_action));
			}
		break;

		case 'apply_permissions':
			if (!isset($_POST['setting']))
			{
				trigger_error($_CLASS['core_user']->lang['NO_AUTH_SETTING_FOUND'] . adm_back_link($u_action));
			}

			set_permissions($mode, $permission_type, $_CLASS['forums_auth_admin'], $user_id, $group_id);
		break;

		case 'apply_all_permissions':
			if (!isset($_POST['setting']))
			{
				trigger_error($_CLASS['core_user']->lang['NO_AUTH_SETTING_FOUND'] . adm_back_link($u_action));
			}

			set_all_permissions($mode, $permission_type, $_CLASS['forums_auth_admin'], $user_id, $group_id);
		break;
	}
}


// Setting permissions screen
$s_hidden_fields = generate_hidden_fields(array(
	'user_id'		=> $user_id,
	'group_id'		=> $group_id,
	'forum_id'		=> $forum_id,
	'type'			=> $permission_type
));

// Go through the screens/options needed and present them in correct order
foreach ($permission_victim as $victim)
{
	switch ($victim)
	{
		case 'forum_dropdown':

			if (sizeof($forum_id))
			{
				check_existence('forum', $forum_id);
				continue 2;
			}

			$_CLASS['core_template']->assign_array(array(
				'S_SELECT_FORUM'		=> true,
				'S_FORUM_OPTIONS'		=> make_forum_select(false, false, true, false, false))
			);

		break;

		case 'forums':

			if (sizeof($forum_id))
			{
				check_existence('forum', $forum_id);
				continue 2;
			}

			$forum_list = make_forum_select(false, false, true, false, false, false, true);

			// Build forum options
			$s_forum_options = '';
			foreach ($forum_list as $f_id => $f_row)
			{
				$s_forum_options .= '<option value="' . $f_id . '"' . $f_row['selected'] . '>' . $f_row['padding'] . $f_row['forum_name'] . '</option>';
			}

			// Build subforum options
			$s_subforum_options = build_subforum_options($forum_list);

			$_CLASS['core_template']->assign_array(array(
				'S_SELECT_FORUM'		=> true,
				'S_FORUM_OPTIONS'		=> $s_forum_options,
				'S_SUBFORUM_OPTIONS'	=> $s_subforum_options,
				'S_FORUM_ALL'			=> true,
				'S_FORUM_MULTIPLE'		=> true
			));

		break;

		case 'user':

			if (sizeof($user_id))
			{
				check_existence('user', $user_id);
				continue 2;
			}

			$_CLASS['core_template']->assign_array(array(
				'S_SELECT_USER'			=> true,
				'U_FIND_USERNAME'		=> generate_link('members_list&amp;mode=searchuser&amp;form=select_victim&amp;field=username')
			));

		break;

		case 'group':

			if (sizeof($group_id))
			{
				check_existence('group', $group_id);
				continue 2;
			}

			$_CLASS['core_template']->assign_array(array(
				'S_SELECT_GROUP'		=> true,
				'S_GROUP_OPTIONS'		=> group_select_options(false)
			));

		break;

		case 'usergroup':
		case 'usergroup_view':

			if (sizeof($user_id) || sizeof($group_id))
			{
				if (sizeof($user_id))
				{
					check_existence('user', $user_id);
				}

				if (sizeof($group_id))
				{
					check_existence('group', $group_id);
				}

				continue 2;
			}

			$items = retrieve_defined_user_groups($permission_scope, $forum_id, $permission_type);

			// Now we check the users... because the "all"-selection is different here (all defined users/groups)
			$all_users = (isset($_POST['all_users'])) ? true : false;
			$all_groups = (isset($_POST['all_groups'])) ? true : false;

			if ($all_users && sizeof($items['user_ids']))
			{
				$user_id = $items['user_ids'];
				continue 2;
			}

			if ($all_groups && sizeof($items['group_ids']))
			{
				$group_id = $items['group_ids'];
				continue 2;
			}

			$_CLASS['core_template']->assign_array(array(
				'S_SELECT_USERGROUP'		=> ($victim === 'usergroup') ? true : false,
				'S_SELECT_USERGROUP_VIEW'	=> ($victim == 'usergroup_view') ? true : false,
				'S_DEFINED_USER_OPTIONS'	=> $items['user_ids_options'],
				'S_DEFINED_GROUP_OPTIONS'	=> $items['group_ids_options'],
				'S_ADD_GROUP_OPTIONS'		=> group_select_options(false, $items['group_ids']),
				'U_FIND_USERNAME'			=> generate_link('members_list&amp;mode=searchuser&amp;form=add_user&amp;field=username')
			));

		break;
	}

	$_CLASS['core_template']->assign_array(array(
		'U_ACTION'				=> $u_action,
		'ANONYMOUS_USER_ID'		=> ANONYMOUS,

		'S_SELECT_VICTIM'		=> true,
		'S_CAN_SELECT_USER'		=> ($_CLASS['forums_auth']->acl_get('a_authusers')) ? true : false,
		'S_CAN_SELECT_GROUP'	=> ($_CLASS['forums_auth']->acl_get('a_authgroups')) ? true : false,
		'S_HIDDEN_FIELDS'		=> $s_hidden_fields
	));

	// Let the forum names being displayed
	if (sizeof($forum_id))
	{
		$sql = 'SELECT forum_name
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_id IN (' . implode(', ', $forum_id) . ')
			ORDER BY forum_name ASC';
		$result = $_CLASS['core_db']->query($sql);

		$forum_names = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_names[] = $row['forum_name'];
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign_array(array(
			'S_FORUM_NAMES'		=> (sizeof($forum_names)) ? true : false,
			'FORUM_NAMES'		=> implode(', ', $forum_names)
		));
	}

	$_CLASS['core_display']->display($page_title, 'modules/forums/admin/acp_permissions.html');

	return;
}

// Do not allow forum_ids being set and no other setting defined (will bog down the server too much)
if (sizeof($forum_id) && !sizeof($user_id) && !sizeof($group_id))
{
	trigger_error($_CLASS['core_user']->lang['ONLY_FORUM_DEFINED'] . adm_back_link($u_action));
}

$_CLASS['core_template']->assign_array(array(
	'S_PERMISSION_DROPDOWN'		=> (sizeof($permission_dropdown) > 1) ? build_permission_dropdown($permission_dropdown, $permission_type) : false,
	'L_PERMISSION_TYPE'			=> $_CLASS['core_user']->get_lang('ACL_TYPE_' . strtoupper($permission_type)),

	'U_ACTION'					=> $u_action,
	'S_HIDDEN_FIELDS'			=> $s_hidden_fields
));

if (strpos($mode, 'setting_') === 0)
{
	$_CLASS['core_template']->assign_array(array(
		'S_SETTING_PERMISSIONS'		=> true
	));

	$hold_ary = $_CLASS['forums_auth_admin']->get_mask('set', (sizeof($user_id)) ? $user_id : false, (sizeof($group_id)) ? $group_id : false, (sizeof($forum_id)) ? $forum_id : false, $permission_type, $permission_scope, ACL_NO);
	$_CLASS['forums_auth_admin']->display_mask('set', $permission_type, $hold_ary, ((sizeof($user_id)) ? 'user' : 'group'), (($permission_scope == 'local') ? true : false));
}
else
{
	$_CLASS['core_template']->assign_array(array(
		'S_VIEWING_PERMISSIONS'		=> true
	));

	$hold_ary = $_CLASS['forums_auth_admin']->get_mask('view', (sizeof($user_id)) ? $user_id : false, (sizeof($group_id)) ? $group_id : false, (sizeof($forum_id)) ? $forum_id : false, $permission_type, $permission_scope, ACL_NEVER);
	$_CLASS['forums_auth_admin']->display_mask('view', $permission_type, $hold_ary, ((sizeof($user_id)) ? 'user' : 'group'), (($permission_scope == 'local') ? true : false));
}

$_CLASS['core_display']->display($page_title, 'modules/forums/admin/acp_permissions.html');

/**
* Build +subforum options
*/
function build_subforum_options($forum_list)
{
	global $_CLASS;

	$s_options = '';

	$forum_list = array_merge($forum_list);

	foreach ($forum_list as $key => $row)
	{
		$s_options .= '<option value="' . $row['forum_id'] . '"' . $row['selected'] . '>' . $row['padding'] . $row['forum_name'];

		// We check if a branch is there...
		$branch_there = false;

		foreach (array_slice($forum_list, $key + 1) as $temp_row)
		{
			if ($temp_row['left_id'] > $row['left_id'] && $temp_row['left_id'] < $row['right_id'])
			{
				$branch_there = true;

				break;
			}
			continue;
		}
		
		if ($branch_there)
		{
			$s_options .= ' [' . $_CLASS['core_user']->get_lang('PLUS_SUBFORUMS') . ']';
		}

		$s_options .= '</option>';
	}

	return $s_options;
}

/**
* Build dropdown field for changing permission types
*/
function build_permission_dropdown($options, $default_option)
{
	global $_CLASS;
	
	$s_dropdown_options = '';
	foreach ($options as $setting)
	{
		if (!$_CLASS['forums_auth']->acl_get('a_' . str_replace('_', '', $setting) . 'auth'))
		{
			continue;
		}
		$selected = ($setting == $default_option) ? ' selected="selected"' : '';
		$s_dropdown_options .= '<option value="' . $setting . '"' . $selected . '>' . $_CLASS['core_user']->lang['permission_type'][$setting] . '</option>';
	}

	return $s_dropdown_options;
}

/**
* Check if selected items exist. Remove not found ids and if empty return error.
*/
function check_existence($mode, &$ids)
{
	global $_CLASS;

	switch ($mode)
	{
		case 'user':
			$table = CORE_USERS_TABLE;
			$sql_id = 'user_id';
		break;

		case 'group':
			$table = CORE_GROUPS_TABLE;
			$sql_id = 'group_id';
		break;

		case 'forum':
			$table = FORUMS_FORUMS_TABLE;
			$sql_id = 'forum_id';
		break;
	}

	$sql = "SELECT $sql_id
		FROM $table
		WHERE $sql_id IN (" . implode(', ', $ids) . ')';
	$result = $_CLASS['core_db']->query($sql);

	$ids = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$ids[] = $row[$sql_id];
	}
	$_CLASS['core_db']->free_result($result);

	if (!sizeof($ids))
	{
		trigger_error($_CLASS['core_user']->lang['SELECTED_' . strtoupper($mode) . '_NOT_EXIST'] . adm_back_link($u_action));
	}
}

/** 
* Apply permissions
*/
function set_permissions($mode, $permission_type, &$forums_auth_admin, &$user_id, &$group_id)
{
	global $_CLASS;

	$psubmit = request_var('psubmit', array(0));

	// User or group to be set?
	$ug_type = (sizeof($user_id)) ? 'user' : 'group';

	// Check the permission setting again
	if (!$_CLASS['forums_auth']->acl_get('a_' . str_replace('_', '', $permission_type) . 'auth') || !$_CLASS['forums_auth']->acl_get('a_auth' . $ug_type . 's'))
	{
		trigger_error($_CLASS['core_user']->lang['NO_ADMIN'] . adm_back_link($u_action));
	}
	
	$ug_id = $forum_id = 0;

	// We loop through the auth settings defined in our submit
	list($ug_id, ) = each($psubmit);
	list($forum_id, ) = each($psubmit[$ug_id]);

	$auth_settings = array_map('intval', $_POST['setting'][$ug_id][$forum_id]);

	// Do we have a role we want to set?
	$assigned_role = (isset($_POST['role'][$ug_id][$forum_id])) ? (int) $_POST['role'][$ug_id][$forum_id] : 0;

	// Do the admin want to set these permissions to other items too?
	$inherit = request_var('inherit', array(0));

	$ug_id = array($ug_id);
	$forum_id = array($forum_id);

	if (sizeof($inherit))
	{
		foreach ($inherit as $_ug_id => $forum_id_ary)
		{
			// Inherit users/groups?
			if (!in_array($_ug_id, $ug_id))
			{
				$ug_id[] = $_ug_id;
			}

			// Inherit forums?
			$forum_id = array_merge($forum_id, array_keys($forum_id_ary));
		}
	}

	$forum_id = array_unique($forum_id);

	// If the auth settings differ from the assigned role, then do not set a role...
	if ($assigned_role)
	{
		if (!check_assigned_role($assigned_role, $auth_settings))
		{
			$assigned_role = 0;
		}
	}

	// Update the permission set...
	$forums_auth_admin->acl_set($ug_type, $forum_id, $ug_id, $auth_settings, $assigned_role);

	// Do we need to recache the moderator lists?
	if ($permission_type == 'm_')
	{
		cache_moderators();
	}

	// Remove users who are now moderators or admins from everyones foes list
	if ($permission_type == 'm_' || $permission_type == 'a_')
	{
		update_foes();
	}

	log_action($mode, 'add', $permission_type, $ug_type, $ug_id, $forum_id);

	trigger_error($_CLASS['core_user']->lang['AUTH_UPDATED'] . adm_back_link($u_action));
}

/** 
* Apply all permissions
*/
function set_all_permissions($mode, $permission_type, &$forums_auth_admin, &$user_id, &$group_id)
{
	global $_CLASS;

	// User or group to be set?
	$ug_type = (sizeof($user_id)) ? 'user' : 'group';

	// Check the permission setting again
	if (!$_CLASS['forums_auth']->acl_get('a_' . str_replace('_', '', $permission_type) . 'auth') || !$_CLASS['forums_auth']->acl_get('a_auth' . $ug_type . 's'))
	{
		trigger_error($_CLASS['core_user']->lang['NO_ADMIN'] . adm_back_link($u_action));
	}

	$auth_settings = (isset($_POST['setting'])) ? $_POST['setting'] : array();
	$auth_roles = (isset($_POST['role'])) ? $_POST['role'] : array();
	$ug_ids = $forum_ids = array();

	// We need to go through the auth settings
	foreach ($auth_settings as $ug_id => $forum_auth_row)
	{
		$ug_id = (int) $ug_id;
		$ug_ids[] = $ug_id;

		foreach ($forum_auth_row as $forum_id => $auth_options)
		{
			$forum_id = (int) $forum_id;
			$forum_ids[] = $forum_id;

			// Check role...
			$assigned_role = (isset($auth_roles[$ug_id][$forum_id])) ? (int) $auth_roles[$ug_id][$forum_id] : 0;

			// If the auth settings differ from the assigned role, then do not set a role...
			if ($assigned_role)
			{
				if (!check_assigned_role($assigned_role, $auth_options))
				{
					$assigned_role = 0;
				}
			}

			// Update the permission set...
			$forums_auth_admin->acl_set($ug_type, $forum_id, $ug_id, $auth_options, $assigned_role, false);
		}
	}

	$forums_auth_admin->acl_clear_prefetch();

	// Do we need to recache the moderator lists?
	if ($permission_type == 'm_')
	{
		cache_moderators();
	}

	// Remove users who are now moderators or admins from everyones foes list
	if ($permission_type == 'm_' || $permission_type == 'a_')
	{
		update_foes();
	}

	log_action($mode, 'add', $permission_type, $ug_type, $ug_ids, $forum_ids);

	trigger_error($_CLASS['core_user']->lang['AUTH_UPDATED'] . adm_back_link($u_action));
}

/**
* Compare auth settings with auth settings from role
* returns false if they differ, true if they are equal
*/
function check_assigned_role($role_id, &$auth_settings)
{
	global $_CLASS;

	$sql = 'SELECT o.auth_option, r.auth_setting
		FROM ' . FORUMS_ACL_OPTIONS_TABLE . ' o, ' . FORUMS_ACL_ROLES_DATA_TABLE . ' r
		WHERE o.auth_option_id = r.auth_option_id
			AND r.role_id = ' . $role_id;
	$result = $_CLASS['core_db']->query($sql);

	$test_auth_settings = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$test_auth_settings[$row['auth_option']] = $row['auth_setting'];
	}
	$_CLASS['core_db']->free_result($result);

	// We need to add any ACL_NO setting from auth_settings to compare correctly
	foreach ($auth_settings as $option => $setting)
	{
		if ($setting == ACL_NO)
		{
			$test_auth_settings[$option] = $setting;
		}
	}

	if (sizeof(array_diff_assoc($auth_settings, $test_auth_settings)))
	{
		return false;
	}

	return true;
}

/**
* Remove permissions
*/
function remove_permissions($mode, $permission_type, &$forums_auth_admin, &$user_id, &$group_id, &$forum_id)
{
	global $_CLASS;
		
	// User or group to be set?
	$ug_type = (sizeof($user_id)) ? 'user' : 'group';

	// Check the permission setting again
	if (!$_CLASS['forums_auth']->acl_get('a_' . str_replace('_', '', $permission_type) . 'auth') || !$_CLASS['forums_auth']->acl_get('a_auth' . $ug_type . 's'))
	{
		trigger_error($_CLASS['core_user']->lang['NO_ADMIN'] . adm_back_link($u_action));
	}

	$forums_auth_admin->acl_delete($ug_type, (($ug_type == 'user') ? $user_id : $group_id), (sizeof($forum_id) ? $forum_id : false), $permission_type);

	// Do we need to recache the moderator lists?
	if ($permission_type == 'm_')
	{
		cache_moderators();
	}

	log_action($mode, 'del', $permission_type, $ug_type, (($ug_type == 'user') ? $user_id : $group_id), (sizeof($forum_id) ? $forum_id : array(0 => 0)));

	trigger_error($_CLASS['core_user']->lang['AUTH_UPDATED'] . adm_back_link($u_action));
}

/**
* Log permission changes
*/
function log_action($mode, $action, $permission_type, $ug_type, $ug_id, $forum_id)
{
	global $_CLASS;

	if (!is_array($ug_id))
	{
		$ug_id = array($ug_id);
	}

	if (!is_array($forum_id))
	{
		$forum_id = array($forum_id);
	}

	// Logging ... first grab user or groupnames ...
	$sql = ($ug_type == 'group') ? 'SELECT group_name as name, group_type FROM ' . CORE_GROUPS_TABLE . ' WHERE ' : 'SELECT username as name FROM ' . CORE_USERS_TABLE . ' WHERE ';
	$sql .=   (($ug_type === 'group') ? 'group_id' : 'user_id').' IN (' . implode(', ', array_map('intval', $ug_id)) . ')';
	$result = $_CLASS['core_db']->query($sql);

	$l_ug_list = '';
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$l_ug_list .= (($l_ug_list != '') ? ', ' : '') . ((isset($row['group_type']) && $row['group_type'] == GROUP_SPECIAL) ? '<span class="blue">' . $_CLASS['core_user']->lang['G_' . $row['name']] . '</span>' : $row['name']);
	}
	$_CLASS['core_db']->free_result($result);

	$mode = str_replace('setting_', '', $mode);

	if ($forum_id[0] == 0)
	{
		add_log('admin', 'LOG_ACL_' . strtoupper($action) . '_' . strtoupper($mode) . '_' . strtoupper($permission_type), $l_ug_list);
	}
	else
	{
		// Grab the forum details if non-zero forum_id
		$sql = 'SELECT forum_name  
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_id IN (' . implode(', ', $forum_id) . ')';
		$result = $_CLASS['core_db']->query($sql);

		$l_forum_list = '';
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$l_forum_list .= (($l_forum_list != '') ? ', ' : '') . $row['forum_name'];
		}
		$_CLASS['core_db']->free_result($result);

		add_log('admin', 'LOG_ACL_' . strtoupper($action) . '_' . strtoupper($mode) . '_' . strtoupper($permission_type), $l_forum_list, $l_ug_list);
	}
}

/**
* Update foes - remove moderators and administrators from foe lists...
*/
function update_foes()
{
	global $_CLASS;

	$perms = array();
	foreach ($_CLASS['forums_auth']->acl_get_list(false, array('a_', 'm_'), false) as $forum_id => $forum_ary)
	{
		foreach ($forum_ary as $auth_option => $user_ary)
		{
			$perms = array_merge($perms, $user_ary);
		}
	}

	if (sizeof($perms))
	{
		$sql = 'DELETE FROM ' . ZEBRA_TABLE . ' 
			WHERE zebra_id IN (' . implode(', ', array_unique($perms)) . ')
				AND foe = 1';
		$_CLASS['core_db']->query($sql);
	}
	unset($perms);
}

/**
* Display a complete trace tree for the selected permission to determine where settings are set/unset
*/
function permission_trace($user_id, $forum_id, $permission)
{
	global $_CLASS;

	if ($user_id != $_CLASS['core_user']->data['user_id'])
	{
		$sql = 'SELECT user_id, username, user_permissions, user_type
			FROM ' . CORE_USERS_TABLE . '
			WHERE user_id = ' . $user_id;
		$result = $_CLASS['core_db']->query($sql);
		$userdata = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
	}
	else
	{
		$userdata = $_CLASS['core_user']->data;
	}

	if (!$userdata)
	{
		trigger_error('NO_USERS');
	}

	$forum_name = false;

	if ($forum_id)
	{
		$sql = 'SELECT forum_name
			FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
		$result = $_CLASS['core_db']->query($sql, 3600);
		$forum_name = $db->sql_fetchfield('forum_name');
		$_CLASS['core_db']->free_result($result);
	}

	$back = request_var('back', 0);

	$_CLASS['core_template']->assign_array(array(
		'PERMISSION'			=> $_CLASS['core_user']->lang['acl_' . $permission]['lang'],
		'PERMISSION_USERNAME'	=> $userdata['username'],
		'FORUM_NAME'			=> $forum_name,
		'U_BACK'				=> ($back) ? build_url(array('f', 'back')) . "&amp;f=$back" : '')
	);

	$template->assign_block_vars('trace', array(
		'WHO'			=> $_CLASS['core_user']->lang['DEFAULT'],
		'INFORMATION'	=> $_CLASS['core_user']->lang['TRACE_DEFAULT'],

		'S_SETTING_NO'		=> true,
		'S_TOTAL_NO'		=> true
	));

	$sql = 'SELECT DISTINCT g.group_name, g.group_id, g.group_type
		FROM ' . CORE_GROUPS_TABLE . ' g
			LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (ug.group_id = g.group_id)
		WHERE ug.user_id = ' . $user_id . '
			AND ug.user_pending = 0
		ORDER BY g.group_type DESC, g.group_id DESC';
	$result = $_CLASS['core_db']->query($sql);

	$groups = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$groups[$row['group_id']] = array(
			'auth_setting'		=> ACL_NO,
			'group_name'		=> ($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']
		);
	}
	$_CLASS['core_db']->free_result($result);

	$total = ACL_NO;
	if (sizeof($groups))
	{
		// Get group auth settings
		$hold_ary = $_CLASS['forums_auth']->acl_group_raw_data(array_keys($groups), $permission, $forum_id);

		foreach ($hold_ary as $group_id => $forum_ary)
		{
			$groups[$group_id]['auth_setting'] = $hold_ary[$group_id][$forum_id][$permission];
		}
		unset($hold_ary);

		foreach ($groups as $id => $row)
		{
			switch ($row['auth_setting'])
			{
				case ACL_NO:
					$information = $_CLASS['core_user']->lang['TRACE_GROUP_NO'];
				break;

				case ACL_YES:
					$information = ($total == ACL_YES) ? $_CLASS['core_user']->lang['TRACE_GROUP_YES_TOTAL_YES'] : (($total == ACL_NEVER) ? $_CLASS['core_user']->lang['TRACE_GROUP_YES_TOTAL_NEVER'] : $_CLASS['core_user']->lang['TRACE_GROUP_YES_TOTAL_NO']);
					$total = ($total == ACL_NO) ? ACL_YES : $total;
				break;

				case ACL_NEVER:
					$information = ($total == ACL_YES) ? $_CLASS['core_user']->lang['TRACE_GROUP_NEVER_TOTAL_YES'] : (($total == ACL_NEVER) ? $_CLASS['core_user']->lang['TRACE_GROUP_NEVER_TOTAL_NEVER'] : $_CLASS['core_user']->lang['TRACE_GROUP_NEVER_TOTAL_NO']);
					$total = ACL_NEVER;
				break;
			}

			$template->assign_block_vars('trace', array(
				'WHO'			=> $row['group_name'],
				'INFORMATION'	=> $information,

				'S_SETTING_NO'		=> ($row['auth_setting'] == ACL_NO) ? true : false,
				'S_SETTING_YES'		=> ($row['auth_setting'] == ACL_YES) ? true : false,
				'S_SETTING_NEVER'	=> ($row['auth_setting'] == ACL_NEVER) ? true : false,
				'S_TOTAL_NO'		=> ($total == ACL_NO) ? true : false,
				'S_TOTAL_YES'		=> ($total == ACL_YES) ? true : false,
				'S_TOTAL_NEVER'		=> ($total == ACL_NEVER) ? true : false)
			);
		}
	}

	// Get user specific permission...
	$hold_ary = $_CLASS['forums_auth']->acl_user_raw_data($user_id, $permission, $forum_id);
	$auth_setting = (!sizeof($hold_ary)) ? ACL_NO : $hold_ary[$user_id][$forum_id][$permission];

	switch ($auth_setting)
	{
		case ACL_NO:
			$information = ($total == ACL_NO) ? $_CLASS['core_user']->lang['TRACE_USER_NO_TOTAL_NO'] : $_CLASS['core_user']->lang['TRACE_USER_KEPT'];
			$total = ($total == ACL_NO) ? ACL_NEVER : $total;
		break;

		case ACL_YES:
			$information = ($total == ACL_YES) ? $_CLASS['core_user']->lang['TRACE_USER_YES_TOTAL_YES'] : (($total == ACL_NEVER) ? $_CLASS['core_user']->lang['TRACE_USER_YES_TOTAL_NEVER'] : $_CLASS['core_user']->lang['TRACE_USER_YES_TOTAL_NO']);
			$total = ($total == ACL_NO) ? ACL_YES : $total;
		break;

		case ACL_NEVER:
			$information = ($total == ACL_YES) ? $_CLASS['core_user']->lang['TRACE_USER_NEVER_TOTAL_YES'] : (($total == ACL_NEVER) ? $_CLASS['core_user']->lang['TRACE_USER_NEVER_TOTAL_NEVER'] : $_CLASS['core_user']->lang['TRACE_USER_NEVER_TOTAL_NO']);
			$total = ACL_NEVER;
		break;
	}

	$template->assign_block_vars('trace', array(
		'WHO'			=> $userdata['username'],
		'INFORMATION'	=> $information,

		'S_SETTING_NO'		=> ($auth_setting == ACL_NO) ? true : false,
		'S_SETTING_YES'		=> ($auth_setting == ACL_YES) ? true : false,
		'S_SETTING_NEVER'	=> ($auth_setting == ACL_NEVER) ? true : false,
		'S_TOTAL_NO'		=> false,
		'S_TOTAL_YES'		=> ($total == ACL_YES) ? true : false,
		'S_TOTAL_NEVER'		=> ($total == ACL_NEVER) ? true : false)
	);

	// global permission might overwrite local permission
	if (($forum_id != 0) && isset($_CLASS['forums_auth']->acl_options['global'][$permission]))
	{
		if ($user_id != $_CLASS['core_user']->data['user_id'])
		{
			$auth2 = new auth();
			$auth2->acl($userdata);
			$auth_setting = $auth2->acl_get($permission);
		}
		else
		{
			$auth_setting = $_CLASS['forums_auth']->acl_get($permission);
		}

		if ($auth_setting)
		{
			$information = ($total == ACL_YES) ? $_CLASS['core_user']->lang['TRACE_USER_GLOBAL_YES_TOTAL_YES'] : $_CLASS['core_user']->lang['TRACE_USER_GLOBAL_YES_TOTAL_NEVER'];
			$total = ACL_YES;
		}
		else
		{
			$information = $_CLASS['core_user']->lang['TRACE_USER_GLOBAL_NEVER_TOTAL_KEPT'];
		}

		$template->assign_block_vars('trace', array(
			'WHO'			=> sprintf($_CLASS['core_user']->lang['TRACE_GLOBAL_SETTING'], $userdata['username']),
			'INFORMATION'	=> sprintf($information, '<a href="' . $u_action . "&amp;u=$user_id&amp;f=0&amp;auth=$permission&amp;back=$forum_id\">", '</a>'),

			'S_SETTING_NO'		=> false,
			'S_SETTING_YES'		=> $auth_setting,
			'S_SETTING_NEVER'	=> !$auth_setting,
			'S_TOTAL_NO'		=> false,
			'S_TOTAL_YES'		=> ($total == ACL_YES) ? true : false,
			'S_TOTAL_NEVER'		=> ($total == ACL_NEVER) ? true : false)
		);
	}

	// Take founder status into account, overwriting the default values
	if ($userdata['user_type'] == USER_FOUNDER && strpos($permission, 'a_') === 0)
	{
		$template->assign_block_vars('trace', array(
			'WHO'			=> $userdata['username'],
			'INFORMATION'	=> $_CLASS['core_user']->lang['TRACE_USER_FOUNDER'],

			'S_SETTING_NO'		=> ($auth_setting == ACL_NO) ? true : false,
			'S_SETTING_YES'		=> ($auth_setting == ACL_YES) ? true : false,
			'S_SETTING_NEVER'	=> ($auth_setting == ACL_NEVER) ? true : false,
			'S_TOTAL_NO'		=> false,
			'S_TOTAL_YES'		=> true,
			'S_TOTAL_NEVER'		=> false)
		);
	}
}

/**
* Get already assigned users/groups
*/
function retrieve_defined_user_groups($permission_scope, $forum_id, $permission_type)
{
	global $_CLASS;

	$sql_forum_id = ($permission_scope === 'global') ? 'AND a.forum_id = 0' : ((sizeof($forum_id)) ? 'AND a.forum_id IN (' . implode(', ', $forum_id) . ')' : 'AND a.forum_id <> 0');
	$sql_permission_option = "AND o.auth_option LIKE '" . $_CLASS['core_db']->escape($permission_type) . "%'";

	$sql = 'SELECT DISTINCT u.username, u.user_reg_date, u.user_id
		FROM '. CORE_USERS_TABLE.' u, '. FORUMS_ACL_OPTIONS_TABLE .' o, '. FORUMS_ACL_TABLE .' a
		LEFT JOIN '. FORUMS_ACL_ROLES_DATA_TABLE." r ON (a.auth_role_id = r.role_id)
		WHERE (a.auth_option_id = o.auth_option_id OR r.auth_option_id = o.auth_option_id)
		$sql_permission_option
		$sql_forum_id
		AND u.user_id = a.user_id
		ORDER BY u.username, u.user_reg_date ASC";
	$result = $_CLASS['core_db']->query($sql);

	$s_defined_user_options = '';
	$defined_user_ids = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$s_defined_user_options .= '<option value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
		$defined_user_ids[] = $row['user_id'];
	}
	$_CLASS['core_db']->free_result($result);

	$sql = 'SELECT DISTINCT g.group_type, g.group_name, g.group_id
			FROM '.CORE_GROUPS_TABLE.' g, '. FORUMS_ACL_OPTIONS_TABLE .' o,
			'. FORUMS_ACL_TABLE .' a
			LEFT JOIN '. FORUMS_ACL_ROLES_DATA_TABLE ." r ON (a.auth_role_id = r.role_id)
			WHERE (a.auth_option_id = o.auth_option_id OR r.auth_option_id = o.auth_option_id)
			$sql_permission_option
			$sql_forum_id
			AND g.group_id = a.group_id
			ORDER BY g.group_type DESC, g.group_name ASC";
	$result = $_CLASS['core_db']->query($sql);

	$s_defined_group_options = '';
	$defined_group_ids = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$s_defined_group_options .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' class="sep"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		$defined_group_ids[] = $row['group_id'];
	}
	$_CLASS['core_db']->free_result($result);

	return array(
		'group_ids'			=> $defined_group_ids,
		'group_ids_options'	=> $s_defined_group_options,
		'user_ids'			=> $defined_user_ids,
		'user_ids_options'	=> $s_defined_user_options
	);
}

?>