<?php
/** 
*
* @package phpBB3
* @version $Id: auth.php,v 1.26 2006/08/12 19:06:09 grahamje Exp $ 
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
*/

/**
* ACP Permission/Auth class
* @package phpBB3
*/
class forums_auth_admin extends forums_auth
{
	var $option_ids = array();

	/**
	* Init auth settings
	*/
	function forums_auth_admin()
	{
		global $_CLASS;

		$this->acl_options = $_CLASS['core_cache']->get('acl_options');

		if (is_null($this->acl_options))
		{
			$sql = 'SELECT auth_option, auth_option_id, is_global, is_local
				FROM ' . FORUMS_ACL_OPTIONS_TABLE . '
				ORDER BY auth_option_id';
			$result = $_CLASS['core_db']->query($sql);

			$global = $local = 0;
			$this->acl_options = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if ($row['is_global'])
				{
					$this->acl_options['global'][$row['auth_option']] = $global++;
				}

				if ($row['is_local'])
				{
					$this->acl_options['local'][$row['auth_option']] = $local++;
				}
				
				$this->option_ids[$row['auth_option']] = $row['auth_option_id'];
			}
			$_CLASS['core_db']->free_result($result);

			$_CLASS['core_cache']->put('acl_options', $this->acl_options);
			$_CLASS['core_cache']->put('acl_option_ids', $this->option_ids);
		}
		else
		{
			$this->option_ids = $_CLASS['core_cache']->get('acl_option_ids');

			if (is_null($this->option_ids))
			{
				$sql = 'SELECT auth_option_id, auth_option
					FROM ' . FORUMS_ACL_OPTIONS_TABLE;
				$result = $_CLASS['core_db']->query($sql);
	
				$this->option_ids = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$this->option_ids[$row['auth_option']] = $row['auth_option_id'];
				}
				$_CLASS['core_db']->free_result($result);

				$_CLASS['core_cache']->put('acl_option_ids', $this->option_ids);
			}
		}
	}
	
	/**
	* Get permission mask
	* This function only supports getting permissions of one type (for example a_)
	*
	* @param set|view $mode defines the permissions we get, view gets effective permissions (checking user AND group permissions), set only gets the user or group permission set alone
	* @param mixed $user_id user ids to search for (a user_id or a group_id has to be specified at least)
	* @param mixed $group_id group ids to search for, return group related settings (a user_id or a group_id has to be specified at least)
	* @param mixed $forum_id forum_ids to search for. Defining a forum id also means getting local settings
	* @param string $auth_option the auth_option defines the permission setting to look for (a_ for example)
	* @param local|global $scope the scope defines the permission scope. If local, a forum_id is additionally required
	* @param ACL_NEVER|ACL_NO|ACL_YES $acl_fill defines the mode those permissions not set are getting filled with
	*/
	function get_mask($mode, $user_id = false, $group_id = false, $forum_id = false, $auth_option = false, $scope = false, $acl_fill = ACL_NEVER)
	{
		global $_CLASS;

		$hold_ary = array();
		$view_user_mask = ($mode === 'view' && $group_id === false) ? true : false;

		if ($auth_option === false || $scope === false)
		{
			return array();
		}

		$acl_user_function = ($mode === 'set') ? 'acl_user_raw_data' : 'acl_raw_data';

		if (!$view_user_mask)
		{
			if ($forum_id !== false)
			{
				$hold_ary = ($group_id !== false) ? $this->acl_group_raw_data($group_id, $auth_option . '%', $forum_id) : $this->$acl_user_function($user_id, $auth_option . '%', $forum_id);
			}
			else
			{
				$hold_ary = ($group_id !== false) ? $this->acl_group_raw_data($group_id, $auth_option . '%', ($scope === 'global') ? 0 : false) : $this->$acl_user_function($user_id, $auth_option . '%', ($scope === 'global') ? 0 : false);
			}
		}

		// Make sure hold_ary is filled with every setting (prevents missing forums/users/groups)
		$ug_id = ($group_id !== false) ? ((!is_array($group_id)) ? array($group_id) : $group_id) : ((!is_array($user_id)) ? array($user_id) : $user_id);
		$forum_ids = ($forum_id !== false) ? ((!is_array($forum_id)) ? array($forum_id) : $forum_id) : (($scope == 'global') ? array(0) : array());

		// Only those options we need
		$compare_options = array_diff(preg_replace('/^((?!' . $auth_option . ').+)|(' . $auth_option . ')$/', '', array_keys($this->acl_options[$scope])), array(''));

		// If forum_ids is false and the scope is local we actually want to have all forums within the array
		if ($scope == 'local' && !sizeof($forum_ids))
		{
			$sql = 'SELECT forum_id 
				FROM ' . FORUMS_FORUMS_TABLE;
			$result = $_CLASS['core_db']->query($sql, 120);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$forum_ids[] = $row['forum_id'];
			}
			$_CLASS['core_db']->free_result($result);
		}

		if ($view_user_mask)
		{
			$auth2 = null;

			$sql = 'SELECT user_id, user_permissions, user_type
				FROM ' . CORE_USERS_TABLE . '
				WHERE user_id IN ('.implode(', ',$ug_id).')';
			$result = $_CLASS['core_db']->query($sql);

			while ($userdata = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if ($_CLASS['core_user']->data['user_id'] != $userdata['user_id'])
				{
					$auth2 = new auth();
					$auth2->acl($userdata);
				}
				else
				{
					global $auth;
					$auth2 = &$auth;
				}

				
				$hold_ary[$userdata['user_id']] = array();
				foreach ($forum_ids as $f_id)
				{
					$hold_ary[$userdata['user_id']][$f_id] = array();
					foreach ($compare_options as $option)
					{
						$hold_ary[$userdata['user_id']][$f_id][$option] = $auth2->acl_get($option, $f_id);
					}
				}
			}
			$_CLASS['core_db']->free_result($result);

			unset($userdata);
			unset($auth2);
		}

		foreach ($ug_id as $_id)
		{
			if (!isset($hold_ary[$_id]))
			{
				$hold_ary[$_id] = array();
			}

			foreach ($forum_ids as $f_id)
			{
				if (!isset($hold_ary[$_id][$f_id]))
				{
					$hold_ary[$_id][$f_id] = array();
				}
			}
		}

		// Now, we need to fill the gaps with $acl_fill. ;)

		// Now switch back to keys
		if (sizeof($compare_options))
		{
			$compare_options = array_combine($compare_options, array_fill(1, sizeof($compare_options), $acl_fill));
		}

		// Defining the user-function here to save some memory
		$return_acl_fill = create_function('$value', 'return ' . $acl_fill . ';');

		// Actually fill the gaps
		if (sizeof($hold_ary))
		{
			foreach ($hold_ary as $ug_id => $row)
			{
				foreach ($row as $id => $options)
				{
					// Do not include the global auth_option
					unset($options[$auth_option]);

					// Not a "fine" solution, but at all it's a 1-dimensional 
					// array_diff_key function filling the resulting array values with zeros
					// The differences get merged into $hold_ary (all permissions having $acl_fill set)
					$hold_ary[$ug_id][$id] = array_merge($options, 

						array_map($return_acl_fill,
							array_flip(
								array_diff(
									array_keys($compare_options), array_keys($options)
								)
							)
						)
					);
				}
			}
		}
		else
		{
			$hold_ary[($group_id !== false) ? $group_id : $user_id][(int) $forum_id] = $compare_options;
		}

		return $hold_ary;
	}

	/**
	* Get permission mask for roles
	* This function only supports getting masks for one role
	*/
	function get_role_mask($role_id)
	{
		global $_CLASS;

		$hold_ary = array();

		$sql = 'SELECT group_id, user_id, forum_id
			FROM ' . FORUMS_ACL_TABLE . '
			WHERE auth_role_id = ' . $role_id . '
			ORDER BY forum_id';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['user_id'])
			{
				$hold_ary[$row['forum_id']]['users'][] = $row['user_id'];
			}

			if ($row['group_id'])
			{
				$hold_ary[$row['forum_id']]['groups'][] = $row['group_id'];
			}
		}
		$_CLASS['core_db']->free_result($result);

		return $hold_ary;
	}

	/**
	* Display permission mask (assign to template)
	*/
	function display_mask($mode, $permission_type, &$hold_ary, $user_mode = 'user', $local = false, $group_display = true)
	{
		global $_CLASS;

		// Define names for template loops, might be able to be set
		$tpl_pmask = 'p_mask';
		$tpl_fmask = 'f_mask';
		$tpl_category = 'category';
		$tpl_mask = 'mask';

		$l_acl_type = (isset($_CLASS['core_user']->lang['ACL_TYPE_' . (($local) ? 'LOCAL' : 'GLOBAL') . '_' . strtoupper($permission_type)])) ? $_CLASS['core_user']->lang['ACL_TYPE_' . (($local) ? 'LOCAL' : 'GLOBAL') . '_' . strtoupper($permission_type)] : 'ACL_TYPE_' . (($local) ? 'LOCAL' : 'GLOBAL') . '_' . strtoupper($permission_type);

		// Allow trace for viewing permissions and in user mode
		$show_trace = ($mode == 'view' && $user_mode == 'user') ? true : false;

		// Get names
		if ($user_mode === 'user')
		{
			$sql = 'SELECT user_id as ug_id, username as ug_name
				FROM ' . CORE_USERS_TABLE . '
				WHERE user_id IN ('.implode(', ', array_keys($hold_ary)) . ')
				ORDER BY username ASC';
		}
		else
		{
			$sql = 'SELECT group_id as ug_id, group_name as ug_name, group_type
				FROM ' . CORE_GROUPS_TABLE . '
				WHERE group_id IN ('.implode(', ', array_keys($hold_ary)) . ')
				ORDER BY group_type DESC, group_name ASC';
		}
		$result = $_CLASS['core_db']->query($sql);

		$ug_names_ary = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$ug_names_ary[$row['ug_id']] = ($user_mode === 'user') ? $row['ug_name'] : (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['ug_name']] : $row['ug_name']);
		}
		$_CLASS['core_db']->free_result($result);

		// Get used forums
		$forum_ids = array();
		foreach ($hold_ary as $ug_id => $row)
		{
			$forum_ids = array_merge($forum_ids, array_keys($row));
		}
		$forum_ids = array_unique($forum_ids);

		$forum_names_ary = array();
		if ($local)
		{
			$forum_names_ary = make_forum_select(false, false, true, false, false, false, true);
		}
		else
		{
			$forum_names_ary[0] = $l_acl_type;
		}

		// Get available roles
		$sql = 'SELECT *
			FROM ' . FORUMS_ACL_ROLES_TABLE . "
			WHERE role_type = '" . $_CLASS['core_db']->escape($permission_type) . "'
			ORDER BY role_order ASC";
		$result = $_CLASS['core_db']->query($sql);

		$roles = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$roles[$row['role_id']] = $row;
		}
		$_CLASS['core_db']->free_result($result);

		$cur_roles = $this->acl_role_data($user_mode, $permission_type, array_keys($hold_ary));

		// Build js roles array (role data assignments)
		$s_role_js_array = '';
		
		if (sizeof($roles))
		{
			$s_role_js_array = array();

			// Make sure every role (even if empty) has its array defined
			foreach ($roles as $_role_id => $null)
			{
				$s_role_js_array[$_role_id] = "\n" . 'role_options[' . $_role_id . '] = new Array();' . "\n";
			}

			$sql = 'SELECT r.role_id, o.auth_option, r.auth_setting
				FROM ' . FORUMS_ACL_ROLES_DATA_TABLE . ' r, ' . FORUMS_ACL_OPTIONS_TABLE . ' o
				WHERE o.auth_option_id = r.auth_option_id
					AND r.role_id IN ('.implode(', ', array_keys($roles)).')';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$flag = substr($row['auth_option'], 0, strpos($row['auth_option'], '_') + 1);
				if ($flag == $row['auth_option'])
				{
					continue;
				}

				$s_role_js_array[$row['role_id']] .= 'role_options[' . $row['role_id'] . '][\'' . $row['auth_option'] . '\'] = ' . $row['auth_setting'] . '; ';
			}
			$_CLASS['core_db']->free_result($result);

			$s_role_js_array = implode('', $s_role_js_array);
		}

		$_CLASS['core_template']->assign('S_ROLE_JS_ARRAY', $s_role_js_array);

		// Now obtain memberships
		$user_groups_default = $user_groups_custom = array();
		if ($user_mode === 'user' && $group_display)
		{
			require_once SITE_FILE_ROOT.'includes/functions_user.php';

			$memberships = group_membership(array_keys($hold_ary));

			// User is not a member of any group? Bad admin, bad bad admin...
			if ($memberships)
			{
				foreach ($memberships as $user_id => $group_array)
				{
					foreach ($group_array as $row)
					{
						if ($row['group_type'] == GROUP_SYSTEM)
						{
							$user_groups_default[$row['user_id']][] = $_CLASS['core_user']->get_lang('G_' . $row['group_name']);
						}
						else
						{
							$user_groups_custom[$row['user_id']][] = $_CLASS['core_user']->get_lang('G_' . $row['group_name']);
						}
					}
				}
			}
			unset($memberships);
		}

		// If we only have one forum id to display or being in local mode and more than one user/group to display, 
		// we switch the complete interface to group by user/usergroup instead of grouping by forum
		// To achive this, we need to switch the array a bit
		if (sizeof($forum_ids) == 1 || ($local && sizeof($ug_names_ary) > 1))
		{
			$hold_ary_temp = $hold_ary;
			$hold_ary = array();
			foreach ($hold_ary_temp as $ug_id => $row)
			{
				foreach ($row as $forum_id => $auth_row)
				{
					$hold_ary[$forum_id][$ug_id] = $auth_row;
				}
			}
			unset($hold_ary_temp);

			$count_pmask = 0;

			foreach ($hold_ary as $forum_id => $forum_array)
			{
				$content_array = $categories = array();
				$this->build_permission_array($hold_ary[$forum_id], $content_array, $categories, array_keys($ug_names_ary));

				$pmask_array[$count_pmask] = array(
					'NAME'			=> ($forum_id == 0) ? $forum_names_ary[0] : $forum_names_ary[$forum_id]['forum_name'],
					'CATEGORIES'	=> implode('</th><th>', $categories),

					'USER_GROUPS_DEFAULT'	=> '',
					'USER_GROUPS_CUSTOM'	=> '',
	
					'L_ACL_TYPE'	=> $l_acl_type,

					'S_LOCAL'		=> ($local) ? true : false,
					'S_GLOBAL'		=> (!$local) ? true : false,
					'S_NUM_CATS'	=> sizeof($categories),
					'S_VIEW'		=> ($mode === 'view') ? true : false,
					'S_NUM_OBJECTS'	=> sizeof($content_array),
					'S_USER_MODE'	=> ($user_mode === 'user') ? true : false,
					'S_GROUP_MODE'	=> ($user_mode === 'group') ? true : false
				);

				$count_fmask = 0;

				foreach ($content_array as $ug_id => $ug_array)
				{
					// Build role dropdown options
					$current_role_id = (isset($cur_roles[$ug_id][$forum_id])) ? $cur_roles[$ug_id][$forum_id] : 0;

					$s_role_options = '';
					foreach ($roles as $role_id => $role_row)
					{
						$role_description = (!empty($_CLASS['core_user']->lang[$role_row['role_description']])) ? $_CLASS['core_user']->lang[$role_row['role_description']] : nl2br($role_row['role_description']);
						$title = ($role_description) ? ' title="' . $role_description . '"' : '';
						$s_role_options .= '<option value="' . $role_id . '"' . (($role_id == $current_role_id) ? ' selected="selected"' : '') . $title . '>' . $role_row['role_name'] . '</option>';
					}

					if ($s_role_options)
					{
						$s_role_options = '<option value="0"' . ((!$current_role_id) ? ' selected="selected"' : '') . ' title="' . htmlspecialchars($_CLASS['core_user']->lang['NO_ROLE_ASSIGNED_EXPLAIN']) . '">' . $_CLASS['core_user']->lang['NO_ROLE_ASSIGNED'] . '</option>' . $s_role_options;
					}

					$pmask_array[$count_pmask][$tpl_fmask][$count_fmask] = array(
						'NAME'				=> $ug_names_ary[$ug_id],
						'FOLDER_IMAGE'		=> false,
						'PADDING'			=> false,
						'S_ROLE_OPTIONS'	=> $s_role_options,
						'UG_ID'				=> $ug_id,
						'FORUM_ID'			=> $forum_id
					);
					
					$pmask_array[$count_pmask][$tpl_fmask][$count_fmask][$tpl_category] = $this->assign_cat_array($ug_array, $tpl_category, $tpl_mask, $ug_id, $forum_id, $show_trace);

					$count_fmask++;
				}
				$count_pmask++;
			}
		}
		else
		{
			$count_pmask = 0;

			foreach ($ug_names_ary as $ug_id => $ug_name)
			{
				if (!isset($hold_ary[$ug_id]))
				{
					continue;
				}

				$content_array = $categories = array();
				$this->build_permission_array($hold_ary[$ug_id], $content_array, $categories, array_keys($forum_names_ary));

				$pmask_array[$count_pmask] = array(
					'NAME'			=> $ug_name,
					'CATEGORIES'	=> implode('</th><th>', $categories),

					'USER_GROUPS_DEFAULT'	=> ($user_mode === 'user' && isset($user_groups_default[$ug_id]) && sizeof($user_groups_default[$ug_id])) ? implode(', ', $user_groups_default[$ug_id]) : '',
					'USER_GROUPS_CUSTOM'	=> ($user_mode === 'user' && isset($user_groups_custom[$ug_id]) && sizeof($user_groups_custom[$ug_id])) ? implode(', ', $user_groups_custom[$ug_id]) : '',
					'L_ACL_TYPE'			=> $l_acl_type,

					'S_LOCAL'		=> ($local) ? true : false,
					'S_GLOBAL'		=> (!$local) ? true : false,
					'S_NUM_CATS'	=> sizeof($categories),
					'S_VIEW'		=> ($mode === 'view') ? true : false,
					'S_NUM_OBJECTS'	=> sizeof($content_array),
					'S_USER_MODE'	=> ($user_mode === 'user') ? true : false,
					'S_GROUP_MODE'	=> ($user_mode === 'group') ? true : false
				);

				$count_fmask = 0;

				foreach ($content_array as $forum_id => $forum_array)
				{
					// Build role dropdown options
					$current_role_id = isset($cur_roles[$ug_id][$forum_id]) ? $cur_roles[$ug_id][$forum_id] : 0;

					$s_role_options = '';
					foreach ($roles as $role_id => $role_row)
					{
						$role_description = (!empty($_CLASS['core_user']->lang[$role_row['role_description']])) ? $_CLASS['core_user']->lang[$role_row['role_description']] : nl2br($role_row['role_description']);
						$title = ($role_description) ? ' title="' . $role_description . '"' : '';
						$s_role_options .= '<option value="' . $role_id . '"' . (($role_id == $current_role_id) ? ' selected="selected"' : '') . $title . '>' . $role_row['role_name'] . '</option>';
					}

					if ($s_role_options)
					{
						$s_role_options = '<option value="0"' . ((!$current_role_id) ? ' selected="selected"' : '') . ' title="' . htmlspecialchars($_CLASS['core_user']->lang['NO_ROLE_ASSIGNED_EXPLAIN']) . '">' . $_CLASS['core_user']->lang['NO_ROLE_ASSIGNED'] . '</option>' . $s_role_options;
					}

					if (!$forum_id)
					{
						$folder_image = '';
					}
					else
					{
						if ($forum_names_ary[$forum_id]['forum_status'] == ITEM_LOCKED)
						{
							$folder_image = '<img src="images/icon_folder_lock_small.gif" width="19" height="18" alt="' . $_CLASS['core_user']->lang['FORUM_LOCKED'] . '" />';
						}
						else
						{
							switch ($forum_names_ary[$forum_id]['forum_type'])
							{
								case FORUM_LINK:
									$folder_image = '<img src="images/icon_folder_link_small.gif" width="22" height="18" alt="' . $_CLASS['core_user']->lang['FORUM_LINK'] . '" />';
								break;

								default:
									$folder_image = ($forum_names_ary[$forum_id]['left_id'] + 1 != $forum_names_ary[$forum_id]['right_id']) ? '<img src="images/icon_folder_sub_small.gif" width="22" height="18" alt="' . $_CLASS['core_user']->lang['SUBFORUM'] . '" />' : '<img src="images/icon_folder_small.gif" width="19" height="18" alt="' . $_CLASS['core_user']->lang['FOLDER'] . '" />';
								break;
							}
						}
					}

					$pmask_array[$count_pmask][$tpl_fmask][$count_fmask] = array(
						'NAME'				=> ($forum_id == 0) ? $forum_names_ary[0] : $forum_names_ary[$forum_id]['forum_name'],
						'PADDING'			=> ($forum_id == 0) ? '' : $forum_names_ary[$forum_id]['padding'],
						'FOLDER_IMAGE'		=> $folder_image,
						'S_ROLE_OPTIONS'	=> $s_role_options,
						'UG_ID'				=> $ug_id,
						'FORUM_ID'			=> $forum_id
					);

					$pmask_array[$count_pmask][$tpl_fmask][$count_fmask][$tpl_category] = $this->assign_cat_array($forum_array, $tpl_category, $tpl_mask, $ug_id, $forum_id, $show_trace);
					$count_fmask++;
				}
				$count_pmask++;
			}
		}

		$_CLASS['core_template']->assign($tpl_pmask, $pmask_array);
	}

	/**
	* Display permission mask for roles
	*/
	function display_role_mask(&$hold_ary)
	{
		global $_CLASS;

		if (!sizeof($hold_ary))
		{
			return;
		}

		// Get forum names
		$sql = 'SELECT forum_id, forum_name
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_id IN ('.implode(', ', array_keys($hold_ary)).')';
		$result = $_CLASS['core_db']->query($sql);

		$forum_names = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_names[$row['forum_id']] = $row['forum_name'];
		}
		$_CLASS['core_db']->free_result($result);

		$count = 0;

		foreach ($hold_ary as $forum_id => $auth_ary)
		{
			$role_mask[$count] = array(
				'NAME'				=> ($forum_id == 0) ? $_CLASS['core_user']->lang['GLOBAL_MASK'] : $forum_names[$forum_id],
				'FORUM_ID'			=> $forum_id
			);


			if (isset($auth_ary['users']) && sizeof($auth_ary['users']))
			{
				$sql = 'SELECT user_id, username
					FROM ' . CORE_USERS_TABLE . '
					WHERE user_id IN ('.implode(', ', $auth_ary['users']) . ')
					ORDER BY username';
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$role_mask[$count]['users'][] = array(
						'USER_ID'		=> $row['user_id'],
						'USERNAME'		=> $row['username'],
						'U_PROFILE'		=> generate_link("members_list&amp;mode=viewprofile&amp;u={$row['user_id']}")
					);
				}
				$_CLASS['core_db']->free_result($result);
			}

			if (isset($auth_ary['groups']) && sizeof($auth_ary['groups']))
			{
				$sql = 'SELECT group_id, group_name, group_type
					FROM ' . CORE_GROUPS_TABLE . '
					WHERE group_id IN ('.implode(', ', $auth_ary['groups']) . ')
					ORDER BY group_type ASC, group_name';
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$role_mask[$count]['groups'][] = array(
						'GROUP_ID'		=> $row['group_id'],
						'GROUP_NAME'	=> isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
						'U_PROFILE'		=> generate_link("members_list&amp;mode=group&amp;g={$row['group_id']}")
					);
				}
				$_CLASS['core_db']->free_result($result);
			}
			$count++;
		}
		$_CLASS['core_template']->assign('role_mask', $role_mask);
	}

	/**
	* NOTE: this function is not in use atm
	* Add a new option to the list ... $options is a hash of form ->
	* $options = array(
	*	'local'		=> array('option1', 'option2', ...),
	*	'global'	=> array('optionA', 'optionB', ...)
	* );
	*/
	function acl_add_option($options)
	{
		global $_CLASS;

		if (!is_array($options))
		{
			return false;
		}

		$cur_options = array();

		$sql = 'SELECT auth_option, is_global, is_local
			FROM ' . FORUMS_ACL_OPTIONS_TABLE . '
			ORDER BY auth_option_id';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['is_global'])
			{
				$cur_options['global'][] = $row['auth_option'];
			}

			if ($row['is_local'])
			{
				$cur_options['local'][] = $row['auth_option'];
			}
		}
		$_CLASS['core_db']->free_result($result);

		// Here we need to insert new options ... this requires discovering whether
		// an options is global, local or both and whether we need to add an permission
		// set flag (x_)
		$new_options = array('local' => array(), 'global' => array());

		foreach ($options as $type => $option_ary)
		{
			$option_ary = array_unique($option_ary);

			foreach ($option_ary as $option_value)
			{
				if (!in_array($option_value, $cur_options[$type]))
				{
					$new_options[$type][] = $option_value;
				}

				$flag = substr($option_value, 0, strpos($option_value, '_') + 1);

				if (!in_array($flag, $cur_options[$type]) && !in_array($flag, $new_options[$type]))
				{
					$new_options[$type][] = $flag;
				}
			}
		}
		unset($options);

		$options = array();
		$options['local'] = array_diff($new_options['local'], $new_options['global']);
		$options['global'] = array_diff($new_options['global'], $new_options['local']);
		$options['local_global'] = array_intersect($new_options['local'], $new_options['global']);

		$sql_ary = array();

		foreach ($options as $type => $option_ary)
		{
			foreach ($option_ary as $option)
			{
				$sql_ary[] = array(
					'auth_option'	=> $option,
					'is_global'		=> ($type == 'global' || $type == 'local_global') ? 1 : 0,
					'is_local'		=> ($type == 'local' || $type == 'local_global') ? 1 : 0
				);
			}
		}

		if (sizeof($sql_ary))
		{
			$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_ary, FORUMS_ACL_OPTIONS_TABLE);
		}

		$cache->destroy('acl_options');
		$this->acl_clear_prefetch();

		return true;
	}

	/**
	* Set a user or group ACL record
	*/
	function acl_set($ug_type, $forum_id, $ug_id, $auth, $role_id = 0, $clear_prefetch = true)
	{
		global $_CLASS;

		// One or more forums
		if (!is_array($forum_id))
		{
			$forum_id = array($forum_id);
		}

		// One or more users
		if (!is_array($ug_id))
		{
			$ug_id = array($ug_id);
		}

		$ug_id_sql =  $ug_type . '_id IN ('.implode(', ', array_map('intval', $ug_id)).')';
		$forum_sql =  'forum_id IN ('.implode(', ', array_map('intval', $forum_id)).')';

		// Instead of updating, inserting, removing we just remove all current settings and re-set everything...
		$id_field = $ug_type . '_id';

		// Get any flags as required
		reset($auth);
		$flag = key($auth);
		$flag = substr($flag, 0, strpos($flag, '_') + 1);

		// This ID (the any-flag) is set if one or more permissions are true...
		$any_option_id = (int) $this->option_ids[$flag];

		// Remove any-flag from auth ary
		if (isset($auth[$flag]))
		{
			unset($auth[$flag]);
		}

		// Remove current auth options...
		$auth_option_ids = array();
		foreach ($auth as $auth_option => $auth_setting)
		{
			$auth_option_ids[] = (int) $this->option_ids[$auth_option];
		}

		$sql = 'DELETE FROM '.FORUMS_ACL_TABLE."
			WHERE $forum_sql
				AND $ug_id_sql
				AND auth_option_id IN ($any_option_id, " . implode(', ', $auth_option_ids) . ')';
		$_CLASS['core_db']->query($sql);

		// Remove those having a role assigned... the correct type of course...
		$sql = 'SELECT role_id
			FROM ' . FORUMS_ACL_ROLES_TABLE . "
			WHERE role_type = '" . $_CLASS['core_db']->escape($flag) . "'";
		$result = $_CLASS['core_db']->query($sql);

		$role_ids = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$role_ids[] = $row['role_id'];
		}
		$_CLASS['core_db']->free_result($result);

		if (sizeof($role_ids))
		{
			$sql = 'DELETE FROM '.FORUMS_ACL_TABLE."
				WHERE $forum_sql
					AND $ug_id_sql
					AND auth_option_id = 0
					AND auth_role_id IN (".implode(', ', $role_ids).')';
			$_CLASS['core_db']->query($sql);
		}

		// Ok, include the any-flag if one or more auth options are set to yes...
		foreach ($auth as $auth_option => $setting)
		{
			if ($setting == ACL_YES && (!isset($auth[$flag]) || $auth[$flag] == ACL_NEVER))
			{
				$auth[$flag] = ACL_YES;
			}
		}

		$sql_ary = array();
		foreach ($forum_id as $forum)
		{
			$forum = (int) $forum;

			if ($role_id)
			{
				foreach ($ug_id as $id)
				{
					$sql_ary[] = array(
						$id_field			=> (int) $id,
						'forum_id'			=> (int) $forum,
						'auth_option_id'	=> 0,
						'auth_setting'		=> 0,
						'auth_role_id'		=> $role_id
					);
				}
			}
			else
			{
				foreach ($auth as $auth_option => $setting)
				{
					$auth_option_id = (int) $this->option_ids[$auth_option];

					if ($setting != ACL_NO)
					{
						foreach ($ug_id as $id)
						{
							$sql_ary[] = array(
								$id_field			=> (int) $id,
								'forum_id'			=> (int) $forum,
								'auth_option_id'	=> (int) $auth_option_id,
								'auth_setting'		=> (int) $setting
							);
						}
					}
				}
			}
		}

		if (sizeof($sql_ary))
		{
			$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_ary, FORUMS_ACL_TABLE);
		}

		if ($clear_prefetch)
		{
			$this->acl_clear_prefetch();
		}
	}

	/**
	* Set a role-specific ACL record
	*/
	function acl_set_role($role_id, $auth)
	{
		global $_CLASS;

		// Get any-flag as required
		reset($auth);
		$flag = key($auth);
		$flag = substr($flag, 0, strpos($flag, '_') + 1);
		
		// Remove any-flag from auth ary
		if (isset($auth[$flag]))
		{
			unset($auth[$flag]);
		}

		// Re-set any flag...
		foreach ($auth as $auth_option => $setting)
		{
			if ($setting == ACL_YES && (!isset($auth[$flag]) || $auth[$flag] == ACL_NEVER))
			{
				$auth[$flag] = ACL_YES;
			}
		}

		$sql_ary = array();
		foreach ($auth as $auth_option => $setting)
		{
			$auth_option_id = (int) $this->option_ids[$auth_option];

			if ($setting != ACL_NO)
			{
				$sql_ary[] = array(
					'role_id'			=> (int) $role_id,
					'auth_option_id'	=> (int) $auth_option_id,
					'auth_setting'		=> (int) $setting
				);
			}
		}

		// If no data is there, we set the any-flag to ACL_NEVER...
		if (!sizeof($sql_ary))
		{
			$sql_ary[] = array(
				'role_id'			=> (int) $role_id,
				'auth_option_id'	=> $this->option_ids[$flag],
				'auth_setting'		=> ACL_NEVER
			);
		}

		// Remove current auth options...
		$sql = 'DELETE FROM ' . FORUMS_ACL_ROLES_DATA_TABLE . '
			WHERE role_id = ' . $role_id;
		$_CLASS['core_db']->query($sql);

		$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_ary, FORUMS_ACL_ROLES_DATA_TABLE);

		$this->acl_clear_prefetch();
	}

	/**
	* Remove local permission
	*/
	function acl_delete($mode, $ug_id = false, $forum_id = false, $permission_type = false)
	{
		global $_CLASS;

		if ($ug_id === false && $forum_id === false)
		{
			return;
		}

		$option_id_ary = array();
		$id_field  = $mode . '_id';

		$where_sql = array();

		if ($forum_id !== false)
		{
			$where_sql[] = !is_array($forum_id) ? 'forum_id = ' . (int) $forum_id : 'forum_id IN ('.implode(', ', array_map('intval', $forum_id)).')';
		}

		if ($ug_id !== false)
		{
			$where_sql[] = !is_array($ug_id) ? $id_field . ' = ' . (int) $ug_id : $id_field.' IN ('.implode(', ', array_map('intval', $ug_id)).')';
		}

		// There seem to be auth options involved, therefore we need to go through the list and make sure we capture roles correctly
		if ($permission_type !== false)
		{
			// Get permission type
			$sql = 'SELECT auth_option, auth_option_id
				FROM ' . FORUMS_ACL_OPTIONS_TABLE . "
				WHERE auth_option LIKE '" . $_CLASS['core_db']->escape($permission_type) . "%'";
			$result = $_CLASS['core_db']->query($sql);

			$auth_id_ary = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$option_id_ary[] = $row['auth_option_id'];
				$auth_id_ary[$row['auth_option']] = ACL_NO;
			}
			$_CLASS['core_db']->free_result($result);

			// First of all, lets grab the items having roles with the specified auth options assigned
			$sql = "SELECT auth_role_id, $id_field, forum_id
				FROM ".FORUMS_ACL_TABLE.', ' . FORUMS_ACL_ROLES_TABLE . " r
				WHERE auth_role_id <> 0
					AND auth_role_id = r.role_id
					AND r.role_type = '{$permission_type}'
					AND " . implode(' AND ', $where_sql) . '
				ORDER BY auth_role_id';
			$result = $_CLASS['core_db']->query($sql);

			$cur_role_auth = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$cur_role_auth[$row['auth_role_id']][$row['forum_id']][] = $row[$id_field];
			}
			$_CLASS['core_db']->free_result($result);

			// Get role data for resetting data
			if (sizeof($cur_role_auth))
			{
				$sql = 'SELECT ao.auth_option, rd.role_id, rd.auth_setting
					FROM ' . FORUMS_ACL_OPTIONS_TABLE . ' ao, ' . FORUMS_ACL_ROLES_DATA_TABLE . ' rd
					WHERE ao.auth_option_id = rd.auth_option_id
						AND rd.role_id IN ('.implode(', ', array_keys($cur_role_auth)).')';
				$result = $_CLASS['core_db']->query($sql);

				$auth_settings = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					// We need to fill all auth_options, else setting it will fail...
					if (!isset($auth_settings[$row['role_id']]))
					{
						$auth_settings[$row['role_id']] = $auth_id_ary;
					}
					$auth_settings[$row['role_id']][$row['auth_option']] = $row['auth_setting'];
				}
				$_CLASS['core_db']->free_result($result);

				// Set the options
				foreach ($cur_role_auth as $role_id => $auth_row)
				{
					foreach ($auth_row as $f_id => $ug_row)
					{
						$this->acl_set($mode, $f_id, $ug_row, $auth_settings[$role_id], 0, false);
					}
				}
			}
		}

		// Now, normally remove permissions...
		if ($permission_type !== false)
		{
			$where_sql[] = 'auth_option_id IN ('.implode(', ', array_map('intval', $option_id_ary)).')';
		}
		
		$sql = 'DELETE FROM '. FORUMS_ACL_TABLE .'
			WHERE ' . implode(' AND ', $where_sql);
		$_CLASS['core_db']->query($sql);

		$this->acl_clear_prefetch();
	}

	/**
	* Assign category to template
	* used by display_mask()
	*/
	function assign_cat_array(&$category_array, $tpl_category, $tpl_mask, $ug_id, $forum_id, $show_trace = false)
	{
		global $_CLASS;

		$count_category = 0;

		foreach ($category_array as $cat => $cat_array)
		{
			//$_CLASS['core_template']->assign_vars_array($tpl_cat, array(
			$return_array[$count_category] = array(
				'S_YES'		=> ($cat_array['S_YES'] && !$cat_array['S_NEVER'] && !$cat_array['S_NO']) ? true : false,
				'S_NEVER'	=> ($cat_array['S_NEVER'] && !$cat_array['S_YES'] && !$cat_array['S_NO']) ? true : false,
				'S_NO'		=> ($cat_array['S_NO'] && !$cat_array['S_NEVER'] && !$cat_array['S_YES']) ? true : false,
							
				'CAT_NAME'	=> $_CLASS['core_user']->lang['permission_cat'][$cat]
			);

			$count_mask = 0;

			foreach ($cat_array['permissions'] as $permission => $allowed)
			{
				//$_CLASS['core_template']->assign_vars_array($tpl_cat . '.' . $tpl_mask, array(
				$return_array[$count_category][$tpl_mask][$count_mask] = array(
					'S_YES'		=> ($allowed == ACL_YES) ? true : false,
					'S_NEVER'	=> ($allowed == ACL_NEVER) ? true : false,
					'S_NO'		=> ($allowed == ACL_NO) ? true : false,

					'UG_ID'			=> $ug_id,
					'FORUM_ID'		=> $forum_id,
					'FIELD_NAME'	=> $permission,
					'S_FIELD_NAME'	=> 'setting[' . $ug_id . '][' . $forum_id . '][' . $permission . ']',

					'U_TRACE'		=> ($show_trace) ? append_sid("{$phpbb_admin_path}index.$phpEx", "i=permissions&amp;mode=trace&amp;u=$ug_id&amp;f=$forum_id&amp;auth=$permission") : '',

					'PERMISSION'	=> $_CLASS['core_user']->lang['acl_' . $permission]['lang']
				);
				$count_mask++;
			}
			$count_category++;
		}
		return $return_array;
	}

	/**
	* Building content array from permission rows with explicit key ordering
	* used by display_mask()
	*/
	function build_permission_array(&$permission_row, &$content_array, &$categories, $key_sort_array)
	{
		global $_CLASS;

		foreach ($key_sort_array as $forum_id)
		{
			if (!isset($permission_row[$forum_id]))
			{
				continue;
			}

			$permissions = $permission_row[$forum_id];
			ksort($permissions);

			foreach ($permissions as $permission => $auth_setting)
			{
				if (!isset($_CLASS['core_user']->lang['acl_' . $permission]))
				{
					$_CLASS['core_user']->lang['acl_' . $permission] = array(
						'cat'	=> 'misc',
						'lang'	=> '{ acl_' . $permission . ' }'
					);
				}
			
				$cat = $_CLASS['core_user']->lang['acl_' . $permission]['cat'];
			
				// Build our categories array
				if (!isset($categories[$cat]))
				{
					$categories[$cat] = $_CLASS['core_user']->lang['permission_cat'][$cat];
				}

				// Build our content array
				if (!isset($content_array[$forum_id]))
				{
					$content_array[$forum_id] = array();
				}

				if (!isset($content_array[$forum_id][$cat]))
				{
					$content_array[$forum_id][$cat] = array(
						'S_YES'			=> false,
						'S_NEVER'		=> false,
						'S_NO'			=> false,
						'permissions'	=> array(),
					);
				}

				$content_array[$forum_id][$cat]['S_YES'] |= ($auth_setting == ACL_YES) ? true : false;
				$content_array[$forum_id][$cat]['S_NEVER'] |= ($auth_setting == ACL_NEVER) ? true : false;
				$content_array[$forum_id][$cat]['S_NO'] |= ($auth_setting == ACL_NO) ? true : false;

				$content_array[$forum_id][$cat]['permissions'][$permission] = $auth_setting;
			}
		}
	}

	/**
	* Use permissions from another user. This transferes a permission set from one user to another.
	* The other user is always able to revert back to his permission set.
	* This function does not check for lower/higher permissions, it is possible for the user to gain 
	* "more" permissions by this.
	* Admin permissions will not be copied.
	*/
	function ghost_permissions($from_user_id, $to_user_id)
	{
		global $_CLASS;

		if ($to_user_id == ANONYMOUS)
		{
			return false;
		}

		$hold_ary = $this->acl_raw_data($from_user_id, false, false);

		if (isset($hold_ary[$from_user_id]))
		{
			$hold_ary = $hold_ary[$from_user_id];
		}
		
		// Key 0 in $hold_ary are global options, all others are forum_ids

		// We disallow copying admin permissions
		foreach ($this->acl_options['global'] as $opt => $id)
		{
			if (strpos($opt, 'a_') === 0)
			{
				$hold_ary[0][$opt] = ACL_NEVER;
			}
		}

		// Force a_switchperm to be allowed
		$hold_ary[0]['a_switchperm'] = ACL_YES;

		$user_permissions = $this->build_bitstring($hold_ary);

		if (!$user_permissions)
		{
			return false;
		}

		$sql = 'UPDATE ' . CORE_USERS_TABLE . "
			SET user_permissions = '" . $_CLASS['core_db']->escape($user_permissions) . "',
				user_perm_from = $from_user_id
			WHERE user_id = " . $to_user_id;
		$_CLASS['core_db']->query($sql);

		return true;
	}
}

?>