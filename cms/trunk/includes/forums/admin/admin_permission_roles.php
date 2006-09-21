<?php
/** 
*
* @package acp
* @version $Id: acp_permission_roles.php,v 1.12 2006/08/28 15:50:31 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @package acp
*/

load_class(SITE_FILE_ROOT.'includes/forums/admin/auth.php', 'forums_auth_admin');

$_CLASS['core_user']->add_lang('admin_permissions');
$_CLASS['core_user']->add_lang('admin_permissions_phpbb');

$submit = (isset($_POST['submit'])) ? true : false;
$role_id = request_var('role_id', 0);
$action = request_var('action', '');
$action = (isset($_POST['add'])) ? 'add' : $action;
$mode = request_var('mode', '');

$u_action = 'forums&file=admin_permission_roles&mode='.$mode;

switch ($mode)
{
	case 'admin_roles':
		$permission_type = 'a_';
		$page_title = 'ACP_ADMIN_ROLES';
	break;

	case 'user_roles':
		$permission_type = 'u_';
		$page_title = 'ACP_USER_ROLES';
	break;

	case 'mod_roles':
		$permission_type = 'm_';
		$page_title = 'ACP_MOD_ROLES';
	break;

	case 'forum_roles':
		$permission_type = 'f_';
		$page_title = 'ACP_FORUM_ROLES';
	break;

	default:
		trigger_error('INVALID_MODE', E_USER_ERROR);
	break;
}

$_CLASS['core_template']->assign_array(array(
	'L_TITLE'		=> $_CLASS['core_user']->lang[$page_title],
	'L_EXPLAIN'		=> $_CLASS['core_user']->lang[$page_title . '_EXPLAIN'])
);

// Take action... admin submitted something
if ($submit || $action === 'remove')
{
	switch ($action)
	{
		case 'remove':

			if (!$role_id)
			{
				trigger_error($_CLASS['core_user']->lang['NO_ROLE_SELECTED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

			$sql = 'SELECT *
				FROM ' . FORUMS_ACL_ROLES_TABLE . '
				WHERE role_id = ' . $role_id;
			$result = $_CLASS['core_db']->query($sql);
			$role_row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$role_row)
			{
				trigger_error($_CLASS['core_user']->lang['NO_ROLE_SELECTED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

			$hidden_fields = generate_hidden_fields(array(
				'i'			=> $id,
				'mode'		=> $mode,
				'role_id'	=> $role_id,
				'action'	=> $action,
			));

			if (display_confirmation('DELETE_ROLE', $hidden_fields))
			{
				remove_role($role_id, $permission_type);

				add_log('admin', 'LOG_' . strtoupper($permission_type) . 'ROLE_REMOVED', $role_row['role_name']);
				trigger_error($_CLASS['core_user']->lang['ROLE_DELETED'] . adm_back_link(generate_link($u_action, array('admin' => true))));
			}

		break;

		case 'edit':
			if (!$role_id)
			{
				trigger_error($_CLASS['core_user']->lang['NO_ROLE_SELECTED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

			// Get role we edit
			$sql = 'SELECT *
				FROM ' . FORUMS_ACL_ROLES_TABLE . '
				WHERE role_id = ' . $role_id;
			$result = $_CLASS['core_db']->query($sql);
			$role_row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$role_row)
			{
				trigger_error($_CLASS['core_user']->lang['NO_ROLE_SELECTED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

		// no break;

		case 'add':

			$role_name = request_var('role_name', '', true);
			$role_description = request_var('role_description', '', true);
			$auth_settings = request_var('setting', array('' => 0));

			if (!$role_name)
			{
				trigger_error($_CLASS['core_user']->lang['NO_ROLE_NAME_SPECIFIED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

			// if we add/edit a role we check the name to be unique among the settings...
			$sql = 'SELECT role_id
				FROM ' . FORUMS_ACL_ROLES_TABLE . "
				WHERE role_type = '" . $_CLASS['core_db']->escape($permission_type) . "'
					AND LOWER(role_name) = '" . $_CLASS['core_db']->escape(strtolower($role_name)) . "'";
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			// Make sure we only print out the error if we add the role or change it's name
			if ($row && ($mode === 'add' || ($mode === 'edit' && strtolower($role_row['role_name']) != strtolower($role_name))))
			{
				trigger_error(sprintf($_CLASS['core_user']->lang['ROLE_NAME_ALREADY_EXIST'], $role_name) . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

			$sql_ary = array(
				'role_name'			=> (string) $role_name,
				'role_description'	=> (string) $role_description,
				'role_type'			=> (string) $permission_type,
			);

			if ($action === 'edit')
			{
				$sql = 'UPDATE ' . FORUMS_ACL_ROLES_TABLE . ' 
					SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . ' 
					WHERE role_id = ' . $role_id;
				$_CLASS['core_db']->query($sql);
			}
			else
			{
				// Get maximum role order for inserting a new role...
				$sql = 'SELECT MAX(role_order) as max_order
					FROM ' . FORUMS_ACL_ROLES_TABLE . "
					WHERE role_type = '" . $_CLASS['core_db']->escape($permission_type) . "'";
				$result = $_CLASS['core_db']->query($sql);
				$max_order = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				$max_order = (int) $max_order['max_order'];

				$sql_ary['role_order'] = $max_order + 1;

				$sql = 'INSERT INTO ' . FORUMS_ACL_ROLES_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary);
				$_CLASS['core_db']->query($sql);

				$role_id = $_CLASS['core_db']->insert_id(FORUMS_ACL_ROLES_TABLE, 'role_id');
			}

			// Now add the auth settings
			$_CLASS['forums_auth_admin']->acl_set_role($role_id, $auth_settings);

			add_log('admin', 'LOG_' . strtoupper($permission_type) . 'ROLE_' . strtoupper($action), $role_name);

			trigger_error($_CLASS['core_user']->lang['ROLE_' . strtoupper($action) . '_SUCCESS'] . adm_back_link(generate_link($u_action, array('admin' => true))));

		break;
	}
}

// Display screens
switch ($action)
{
	case 'add':

		$options_from = request_var('options_from', 0);

		$role_row = array(
			'role_name'			=> request_var('role_name', '', true),
			'role_description'	=> request_var('role_description', '', true),
			'role_type'			=> $permission_type,
		);

		if ($options_from)
		{
			$sql = 'SELECT p.auth_option_id, p.auth_setting, o.auth_option
				FROM ' . FORUMS_ACL_ROLES_DATA_TABLE . ' p, ' . FORUMS_ACL_OPTIONS_TABLE . ' o
				WHERE o.auth_option_id = p.auth_option_id
					AND p.role_id = ' . $options_from . '
				ORDER BY p.auth_option_id';
			$result = $_CLASS['core_db']->query($sql);

			$auth_options = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$auth_options[$row['auth_option']] = $row['auth_setting'];
			}
			$_CLASS['core_db']->free_result($result);
		}
		else
		{
			$sql = 'SELECT auth_option_id, auth_option
				FROM ' . FORUMS_ACL_OPTIONS_TABLE . "
				WHERE auth_option LIKE '{$permission_type}%'
					AND auth_option <> '{$permission_type}'
				ORDER BY auth_option_id";
			$result = $_CLASS['core_db']->query($sql);

			$auth_options = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$auth_options[$row['auth_option']] = ACL_NO;
			}
			$_CLASS['core_db']->free_result($result);
		}

	// no break;

	case 'edit':

		if ($action == 'edit')
		{
			if (!$role_id)
			{
				trigger_error($_CLASS['core_user']->lang['NO_ROLE_SELECTED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}
			
			$sql = 'SELECT *
				FROM ' . FORUMS_ACL_ROLES_TABLE . '
				WHERE role_id = ' . $role_id;
			$result = $_CLASS['core_db']->query($sql);
			$role_row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			$sql = 'SELECT p.auth_option_id, p.auth_setting, o.auth_option
				FROM ' . FORUMS_ACL_ROLES_DATA_TABLE . ' p, ' . FORUMS_ACL_OPTIONS_TABLE . ' o
				WHERE o.auth_option_id = p.auth_option_id
					AND p.role_id = ' . $role_id . '
				ORDER BY p.auth_option_id';
			$result = $_CLASS['core_db']->query($sql);

			$auth_options = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$auth_options[$row['auth_option']] = $row['auth_setting'];
			}
			$_CLASS['core_db']->free_result($result);
		}

		if (!$role_row)
		{
			trigger_error($_CLASS['core_user']->lang['NO_ROLE_SELECTED'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
		}

		$_CLASS['core_template']->assign_array(array(
			'S_EDIT'			=> true,

			'U_ACTION'			=> generate_link($u_action . "&amp;action={$action}&amp;role_id={$role_id}", array('admin' => true)),
			'U_BACK'			=> generate_link($u_action, array('admin' => true)),

			'ROLE_NAME'			=> $role_row['role_name'],
			'ROLE_DESCRIPTION'	=> $role_row['role_description'],
			'L_ACL_TYPE'		=> $_CLASS['core_user']->lang['ACL_TYPE_' . strtoupper($permission_type)],
			)
		);

		// We need to fill the auth options array with ACL_NO options ;)
		$sql = 'SELECT auth_option_id, auth_option
			FROM ' . FORUMS_ACL_OPTIONS_TABLE . "
			WHERE auth_option LIKE '{$permission_type}%'
				AND auth_option <> '{$permission_type}'
			ORDER BY auth_option_id";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if (!isset($auth_options[$row['auth_option']]))
			{
				$auth_options[$row['auth_option']] = ACL_NO;
			}
		}
		$_CLASS['core_db']->free_result($result);

		// Unset global permission option
		unset($auth_options[$permission_type]);

		// Display auth options
		display_auth_options($auth_options);

		// Get users/groups/forums using this preset...
		if ($action == 'edit')
		{
			$hold_ary = $_CLASS['forums_auth_admin']->get_role_mask($role_id);

			if (sizeof($hold_ary))
			{
				$_CLASS['core_template']->assign_array(array(
					'S_DISPLAY_ROLE_MASK'	=> true,
					'L_ROLE_ASSIGNED_TO'	=> sprintf($_CLASS['core_user']->lang['ROLE_ASSIGNED_TO'], $role_row['role_name']))
				);

				$_CLASS['forums_auth_admin']->display_role_mask($hold_ary);
			}
		}

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_permission_roles.html');

		return;
	break;

	case 'move_up':
	case 'move_down':

		$order = request_var('order', 0);
		$order_total = $order * 2 + (($action == 'move_up') ? -1 : 1);

		$sql = 'UPDATE ' . FORUMS_ACL_ROLES_TABLE . '
			SET role_order = ' . $order_total  . " - role_order
			WHERE role_type = '" . $_CLASS['core_db']->escape($permission_type) . "'
				AND role_order IN ($order, " . (($action == 'move_up') ? $order - 1 : $order + 1) . ')';
		$_CLASS['core_db']->query($sql);

	break;
}

// By default, check that role_order is valid and fix it if necessary
$sql = 'SELECT role_id, role_order
	FROM ' . FORUMS_ACL_ROLES_TABLE . "
	WHERE role_type = '" . $_CLASS['core_db']->escape($permission_type) . "'
	ORDER BY role_order ASC";
$result = $_CLASS['core_db']->query($sql);

if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$order = 0;
	do
	{
		$order++;
		if ($row['role_order'] != $order)
		{
			$_CLASS['core_db']->query('UPDATE ' . FORUMS_ACL_ROLES_TABLE . " SET role_order = $order WHERE role_id = {$row['role_id']}");
		}
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
}
$_CLASS['core_db']->free_result($result);

// Display assigned items?
$display_item = request_var('display_item', 0);

// Select existing roles
$sql = 'SELECT *
	FROM ' . FORUMS_ACL_ROLES_TABLE . "
	WHERE role_type = '" . $_CLASS['core_db']->escape($permission_type) . "'
	ORDER BY role_order ASC";
$result = $_CLASS['core_db']->query($sql);

$s_role_options = '';
while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$_CLASS['core_template']->assign_vars_array('roles', array(
		'ROLE_NAME'				=> $row['role_name'],
		'ROLE_DESCRIPTION'		=> (!empty($_CLASS['core_user']->lang[$row['role_description']])) ? $_CLASS['core_user']->lang[$row['role_description']] : nl2br($row['role_description']),

		'U_EDIT'			=> generate_link($u_action . '&amp;action=edit&amp;role_id=' . $row['role_id'], array('admin' => true)),
		'U_REMOVE'			=> generate_link($u_action . '&amp;action=remove&amp;role_id=' . $row['role_id'], array('admin' => true)),
		'U_MOVE_UP'			=> generate_link($u_action . '&amp;action=move_up&amp;order=' . $row['role_order'], array('admin' => true)),
		'U_MOVE_DOWN'		=> generate_link($u_action . '&amp;action=move_down&amp;order=' . $row['role_order'], array('admin' => true)),
		'U_DISPLAY_ITEMS'	=> ($row['role_id'] == $display_item) ? '' : generate_link($u_action . '&amp;display_item=' . $row['role_id'] . '#assigned_to', array('admin' => true))
	));

	$s_role_options .= '<option value="' . $row['role_id'] . '">' . $row['role_name'] . '</option>';

	if ($display_item == $row['role_id'])
	{
		$_CLASS['core_template']->assign_array(array(
			'L_ROLE_ASSIGNED_TO'	=> sprintf($_CLASS['core_user']->lang['ROLE_ASSIGNED_TO'], $row['role_name']))
		);
	}
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_template']->assign_array(array(
	'S_ROLE_OPTIONS'		=> $s_role_options)
);

if ($display_item)
{
	$_CLASS['core_template']->assign_array(array(
		'S_DISPLAY_ROLE_MASK'	=> true)
	);

	$hold_ary = $_CLASS['forums_auth_admin']->get_role_mask($display_item);
	$_CLASS['forums_auth_admin']->display_role_mask($hold_ary);
}

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_permission_roles.html');

/**
* Display permission settings able to be set
*/
function display_auth_options($auth_options)
{
	global $_CLASS;

	$content_array = $categories = array();
	$key_sort_array = array(0);
	$auth_options = array(0 => $auth_options);

	// Making use of auth_admin method here (we do not really want to change two similar code fragments)
	$_CLASS['forums_auth_admin']->build_permission_array($auth_options, $content_array, $categories, $key_sort_array);

	$content_array = $content_array[0];

	$_CLASS['core_template']->assign('S_NUM_PERM_COLS', sizeof($categories));

	// Assign to template
	foreach ($content_array as $cat => $cat_array)
	{
		$auth = array(
			'CAT_NAME'	=> $_CLASS['core_user']->lang['permission_cat'][$cat],

			'S_YES'		=> ($cat_array['S_YES'] && !$cat_array['S_NEVER'] && !$cat_array['S_NO']) ? true : false,
			'S_NEVER'	=> ($cat_array['S_NEVER'] && !$cat_array['S_YES'] && !$cat_array['S_NO']) ? true : false,
			'S_NO'		=> ($cat_array['S_NO'] && !$cat_array['S_NEVER'] && !$cat_array['S_YES']) ? true : false
		);

		foreach ($cat_array['permissions'] as $permission => $allowed)
		{
			$auth['mask'][] = array(
				'S_YES'		=> ($allowed == ACL_YES) ? true : false,
				'S_NEVER'	=> ($allowed == ACL_NEVER) ? true : false,
				'S_NO'		=> ($allowed == ACL_NO) ? true : false,

				'FIELD_NAME'	=> $permission,
				'PERMISSION'	=> $_CLASS['core_user']->lang['acl_' . $permission]['lang']
			);
		}
		$_CLASS['core_template']->assign_vars_array('auth', $auth);
	}
}

/**
* Remove role
*/
function remove_role($role_id, $permission_type)
{
	global $_CLASS;

	$auth_admin = new forums_auth_admin();

	// Get complete auth array
	$sql = 'SELECT auth_option, auth_option_id
		FROM ' . FORUMS_ACL_OPTIONS_TABLE . "
		WHERE auth_option LIKE '" . $_CLASS['core_db']->escape($permission_type) . "%'";
	$result = $_CLASS['core_db']->query($sql);

	$auth_settings = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$auth_settings[$row['auth_option']] = ACL_NO;
	}
	$_CLASS['core_db']->free_result($result);

	// Get the role auth settings we need to re-set...
	$sql = 'SELECT o.auth_option, r.auth_setting
		FROM ' . FORUMS_ACL_ROLES_DATA_TABLE . ' r, ' . FORUMS_ACL_OPTIONS_TABLE . ' o
		WHERE o.auth_option_id = r.auth_option_id
			AND r.role_id = ' . $role_id;
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$auth_settings[$row['auth_option']] = $row['auth_setting'];
	}
	$_CLASS['core_db']->free_result($result);

	// Get role assignments
	$hold_ary = $auth_admin->get_role_mask($role_id);

	// Re-assign permissions
	foreach ($hold_ary as $forum_id => $forum_ary)
	{
		if (isset($forum_ary['users']))
		{
			$auth_admin->acl_set('user', $forum_id, $forum_ary['users'], $auth_settings, 0, false);
		}

		if (isset($forum_ary['groups']))
		{
			$auth_admin->acl_set('group', $forum_id, $forum_ary['groups'], $auth_settings, 0, false);
		}
	}

	// Remove role from users and groups just to be sure (happens through acl_set)
	$sql = 'DELETE FROM ' . FORUMS_ACL_TABLE . '
		WHERE auth_role_id = ' . $role_id;
	$_CLASS['core_db']->query($sql);

	// Remove role data and role
	$sql = 'DELETE FROM ' . FORUMS_ACL_ROLES_DATA_TABLE . '
		WHERE role_id = ' . $role_id;
	$_CLASS['core_db']->query($sql);

	$sql = 'DELETE FROM ' . FORUMS_ACL_ROLES_TABLE . '
		WHERE role_id = ' . $role_id;
	$_CLASS['core_db']->query($sql);

	$auth_admin->acl_clear_prefetch();
}


?>