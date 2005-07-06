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

		if (!empty($data['users_allowed']) && in_array($_CLASS['core_user']->data['user_id'], $data['users_allowed']))
		{
			return true;
		}

		if (!empty($data['groups_allowed']) && in_array($_CLASS['core_user']->data['user_id'], $data['groups_allowed']))
		{
			return true;
		}

		return false;
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

	function make_options($options = array(), $display = false)
	{
		global $_CLASS;

		//print_r($_POST);
		if (!is_array($options))
		{
			$options = array();
		}

		$options['groups_allowed'] = empty($options['groups_allowed']) ? array() : $options['groups_allowed'];
		$options['users_allowed'] = empty($options['users_allowed']) ? array() : $options['users_allowed'];

		if (!$display)
		{
			if (empty($_POST['submit']))
			{
				return false;
			}

			$g_remove = empty($_POST['g_remove']) ? array() : $_POST['g_remove'];
			$u_remove = empty($_POST['u_remove']) ? array() : $_POST['u_remove'];
			$u_add = ($_POST['u_add']) ? explode("\n", modify_lines($_POST['u_add'], "\n")) : array();
			$g_add = empty($_POST['g_add']) ? array() : $_POST['g_add'];
			$user_ids = array();

			if (count($u_add))
			{
				$sql = 'SELECT user_id
							FROM ' . USERS_TABLE . " 
							WHERE username IN ('" . implode("' ,'", $u_add) . "')";
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$user_ids[] = $row['user_id'];
				}
				$_CLASS['core_db']->sql_freeresult($result);
			}

			$options['groups_allowed'] = array_merge(array_diff($options['groups_allowed'], $g_remove), $g_add);
			$options['users_allowed'] = array_merge(array_diff($options['users_allowed'], $u_remove), $user_ids);

			return (empty($options['groups_allowed']) && empty($options['users_allowed'])) ? true : $options;
		}

		//print_r($options['groups_allowed']);
		//print_r($options['users_allowed']);

		$group_list = $current_user_list = $current_group_list = '';

		if (count($options['users_allowed']))
		{
			$sql = 'SELECT user_id, username, user_colour
				FROM ' . USERS_TABLE . '
				WHERE user_id IN ('.implode(', ', $options['users_allowed']).')
					ORDER BY username';
			$result = $_CLASS['core_db']->sql_query($sql);

			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$current_user_list .= '<option ' . (($row['user_colour'] == GROUP_SPECIAL) ? ' style="color: #'.$row['user_colour'].';"' : '') . ' value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			}
			$_CLASS['core_db']->sql_freeresult($result);
		}

		if (count($options['groups_allowed']))
		{
			$sql = 'SELECT group_id, group_name, group_type 
				FROM ' . GROUPS_TABLE . '
				WHERE group_id IN ('.implode(', ', $options['groups_allowed']).')
					ORDER BY group_type DESC, group_name';
			$result = $_CLASS['core_db']->sql_query($sql);

			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$current_group_list .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
			}
			$_CLASS['core_db']->sql_freeresult($result);
		}

		$sql = "SELECT group_id, group_name, group_type 
			FROM " . GROUPS_TABLE . "
			ORDER BY group_type DESC, group_name";
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$group_list .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['core_db']->sql_freeresult($result);
	
		$_CLASS['core_template']->assign(array(
			'P_ADD_GROUPS'		=> $group_list,
			'P_ADD_USERS'		=> '',
			'P_CURRENT_USERS'	=> $current_user_list,
			'P_CURRENT_GROUPS'	=> $current_group_list,
		));
	
		$_CLASS['core_template']->display('permission.html');
	}
}

?>