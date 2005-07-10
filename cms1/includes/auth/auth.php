<?php
/*
	This should store it's own user_id and group_id
	Shouldn't use anything directly from $_CLASS['core_user']
	
	ALTER TABLE `cms_blocks` CHANGE `auth` `auth` TINYTEXT NOT NULL 
*/

class core_auth
{
	var $acl = array();
	var $option = array();
	var $got_data = false;
	var $user_permission = array();
	var $group_permission = array();

	function user_auth($user_name, $user_password)
	{
		global $_CLASS;

		$sql = 'SELECT user_id, username, user_password, user_password_encoding, user_type 
					FROM ' . USERS_TABLE . " WHERE username = '" . $_CLASS['core_db']->sql_escape($user_name) . "'";

		$result = $_CLASS['core_db']->sql_query($sql);
		$status = false;
	
		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			if (encode_password($user_password, $row['user_password_encoding']) == $row['user_password'])
			{
				$status = ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) ? $row['user_type'] : (int) $row['user_id'];
			}
		}
		
		$_CLASS['core_db']->sql_freeresult($result);
		
		return $status;
	}

	function admin_auth()
	{
		if (!$this->got_data)
		{
			$this->get_data();
		}
		
		return (!empty($this->user_permission) || !empty($this->group_permission));
	}

	function get_data($id = false, $g_id = false)
	{
		global $_CLASS;
		// take this for now, has alot of work to be done

		$id = ($id) ? $id : $_CLASS['core_user']->data['user_id'];
		$g_id = ($g_id) ? $g_id : $_CLASS['core_user']->data['group_id'];

		$sql = 'SELECT * FROM ' . AUTH_ADMIN_TABLE ." 
					WHERE user_id = $id OR  group_id = $g_id ORDER BY user_id";
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			if ($row['user_id'])
			{
				$this->user_permission[$row['section_id']] = $row['status'];
			}
			elseif ($row['group_id'] && !isset($this->user_permission[$row['user_id']][$row['section_id']]))
			{
				$this->group_permission[$row['section_id']] = $row['status'];
			}
		}
			
		$this->got_data = true;
		$_CLASS['core_db']->sql_freeresult($result);
	}

	function auth_dump()
	{

	}

	function auth($data)
	{
		global $_CLASS;

		if (!$data)
		{
			return true;
		}

		if (empty($data['users_allowed']) && empty($data['groups_allowed']))
		{
			$return = true;

			if (!empty($data['users_disallowed']) && in_array($_CLASS['core_user']->data['user_id'], $data['users_disallowed']))
			{
				$return = false;
			}

			if ($return && !empty($data['groups_disallowed']) && in_array($_CLASS['core_user']->data['group_id'], $data['groups_disallowed']))
			{
				$return = false;
			}

			return $return;
		}
		else
		{
			if (!empty($data['users_allowed']) && in_array($_CLASS['core_user']->data['user_id'], $data['users_allowed']))
			{
				return true;
			}
	
			if (!empty($data['groups_allowed']) && in_array($_CLASS['core_user']->data['group_id'], $data['groups_allowed']))
			{
				if (empty($data['users_disallowed']) || !in_array($_CLASS['core_user']->data['user_id'], $data['users_disallowed']))
				{
					return true;
				}
			}

			return false;
		}
	}

	function admin_power($section_id = false)
	{
		global $_CLASS;

		if (!$_CLASS['core_user']->is_admin)
		{
			return false;
		}
		// no no no not yet
		return true;
	}

	function generate_auth_options($options = array(), $display = false, $return = false)
	{
		global $_CLASS;

		$options['groups_allowed'] = empty($options['groups_allowed']) ? array() : $options['groups_allowed'];
		$options['users_allowed'] = empty($options['users_allowed']) ? array() : $options['users_allowed'];
		$options['groups_disallowed'] = empty($options['groups_disallowed']) ? array() : $options['groups_disallowed'];
		$options['users_disallowed'] = empty($options['users_disallowed']) ? array() : $options['users_disallowed'];

		if (!$display)
		{
			if (empty($_POST['submit']))
			{
				return false;
			}

			$user_ids = array('disallowed' => array(), 'allowed' => array());

			// Allowed
			$g_remove = empty($_POST['g_remove']) ? array() : $_POST['g_remove'];
			$u_remove = empty($_POST['u_remove']) ? array() : $_POST['u_remove'];
			$u_add['allowed'] = ($_POST['u_add']) ? explode("\n", modify_lines($_POST['u_add'], "\n")) : array();
			$g_add = empty($_POST['g_add']) ? array() : $_POST['g_add'];

			// Disallowed
			$dg_remove = empty($_POST['dg_remove']) ? array() : $_POST['dg_remove'];
			$du_remove = empty($_POST['du_remove']) ? array() : $_POST['du_remove'];
			$u_add['disallowed'] = ($_POST['du_add']) ? explode("\n", modify_lines($_POST['du_add'], "\n")) : array();
			$dg_add = empty($_POST['dg_add']) ? array() : $_POST['dg_add'];

			foreach ($u_add as $name => $values)
			{
				if (count($values))
				{
					$sql = 'SELECT user_id
								FROM ' . USERS_TABLE . " 
								WHERE username IN ('" . implode("' ,'", $values) . "')";
					$result = $_CLASS['core_db']->sql_query($sql);
	
					while ($row = $_CLASS['core_db']->sql_fetchrow($result))
					{
						$user_ids[$name][] = $row['user_id'];
					}
					$_CLASS['core_db']->sql_freeresult($result);
				}
			}

			$options['groups_allowed'] = array_diff(array_merge($options['groups_allowed'], $g_add), $g_remove);
			$options['users_allowed'] = array_diff(array_merge($options['users_allowed'], $user_ids['allowed']), $u_remove);

			$options['groups_disallowed'] = array_diff(array_merge($options['groups_disallowed'], $dg_add), $dg_remove, $options['groups_allowed']);
			$options['users_disallowed'] = array_diff(array_merge($options['users_disallowed'], $user_ids['disallowed']), $du_remove, $options['users_allowed']);

			foreach ($options as $option)
			{
				if (!empty($option))
				{
					return $options;
				}
			}

			return true;
		}

		$group_list = $allowed_group_list = $disallowed_group_list = $allowed_user_list = $disallowed_user_list = '';

		$set_users = array_merge($options['users_allowed'], $options['users_disallowed']);
		$set_groups = array_merge($options['groups_allowed'], $options['groups_disallowed']);

		if (count($set_users))
		{
			$sql = 'SELECT user_id, username, user_colour
				FROM ' . USERS_TABLE . '
				WHERE user_id IN ('.implode(', ', $set_users).')
					ORDER BY username';
			$result = $_CLASS['core_db']->sql_query($sql);

			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$user_list = (in_array($row['user_id'], $options['users_allowed'])) ? 'allowed_user_list' : 'disallowed_user_list';

				$$user_list .= '<option ' . (($row['user_colour']) ? ' style="color: #'.$row['user_colour'].';"' : '') . ' value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			}
			$_CLASS['core_db']->sql_freeresult($result);
		}

		if (count($set_groups))
		{
			$sql = 'SELECT group_id, group_name, group_type 
				FROM ' . GROUPS_TABLE . '
				WHERE group_id IN ('.implode(', ', $set_groups).')
					ORDER BY group_type DESC, group_name';
			$result = $_CLASS['core_db']->sql_query($sql);

			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$group_list = (in_array($row['group_id'], $options['groups_allowed'])) ? 'allowed_group_list' : 'disallowed_group_list';
				
				$$group_list .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
				
			}
			$_CLASS['core_db']->sql_freeresult($result);
		}

		$sql = 'SELECT group_id, group_name, group_type 
			FROM ' . GROUPS_TABLE . 
				((!empty($set_groups)) ? ' WHERE group_id NOT IN ('.implode(', ', $set_groups).')' : '').'
					ORDER BY group_type DESC, group_name';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$group_list .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['core_db']->sql_freeresult($result);
	
		$_CLASS['core_template']->assign(array(
			'P_ADD_GROUPS'		=> $group_list,
			'P_CURRENT_USERS'	=> $allowed_user_list,
			'P_DCURRENT_USERS'	=> $disallowed_user_list,
			'P_CURRENT_GROUPS'	=> $allowed_group_list,
			'P_DCURRENT_GROUPS'	=> $disallowed_group_list,
		));
	
		return $_CLASS['core_template']->display('permission.html', $return);
	}
}

?>