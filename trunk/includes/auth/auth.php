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

class core_auth
{
	var $_user_id;
	var $_group_id;

	var $_got_data = false;
	var $_admin_permission = array();

	function core_auth($user_id = false, $group_id = false)
	{
		global $_CLASS;

		$this->_user_id = ($user_id) ? $user_id : $_CLASS['core_user']->data['user_id'];
		$this->_group_id = ($group_id) ? $group_id : $_CLASS['core_user']->data['group_id'];
	}

	function user_auth($user_name, $user_password)
	{
		global $_CLASS;

		$sql = 'SELECT user_id, username, user_password, user_password_encoding, user_status 
					FROM ' . USERS_TABLE . " WHERE username = '" . $_CLASS['core_db']->escape($user_name) . "'";

		$result = $_CLASS['core_db']->query($sql);
		$status = false;
	
		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if (encode_password($user_password, $row['user_password_encoding']) == $row['user_password'])
			{
				if ($row['user_status'] == USER_DISABLE || $row['user_status'] == USER_UNACTIVATED)
				{
					$status =  ($row['user_type'] == USER_INACTIVE) ? 'ACTIVE_ERROR' : 'UNACTIVATED_ERROR';
				}

				return (int) $row['user_id'];
			}
		}

		$_CLASS['core_db']->free_result($result);
		
		return $status;
	}

	function admin_auth()
	{
		if (!$this->_got_data)
		{
			$this->admin_get_data();
		}
		
		return !empty($this->_admin_permission);
	}

	function admin_get_data()
	{
		global $_CLASS;

		$sql = 'SELECT * FROM ' . AUTH_ADMIN_TABLE ." 
					WHERE user_id = {$this->_user_id}
					OR group_id = {$this->_group_id} ORDER BY user_id";

		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['status'] == STATUS_PENDING)
			{
				continue;
			}

			if (!isset($this->_admin_permission[$row['section']]))
			{
				$this->_admin_permission[$row['section']]['core']['status'] = $row['status'];

				if ($row['options'] && is_array($row['options'] = @unserialize($row['options'])))
				{
					$this->_admin_permission[$row['section']] = $row['options'];
				}
			}
		}
	
		$this->_got_data = true;
		$_CLASS['core_db']->free_result($result);
	}

	function admin_power($section, $option = false)
	{
		global $_CLASS;

		if (!$_CLASS['core_user']->is_admin || !$this->admin_auth() || !isset($this->_admin_permission[$section]))
		{
			if (isset($this->_admin_permission['/all/']))
			{
				return true;
			}

			return false;
		}

		if ($option)
		{
			return isset($this->_admin_permission[$section][$option]) ? false : $this->_admin_permission[$section][$option];
		}

		return $this->_admin_permission[$section]['core']['status'];
	}

	function do_login($login_options, $template)
	{
		global $_CLASS, $_CORE_CONFIG;

		$error = '';

		$login_array = array(
			'redirect' 		=> false,
			'explain' 	 	=> false,
			'success'  		=> '',
			'admin_login'	=> false,
			'full_login'	=> true,
			'full_screen'	=> false,
		);
	
		if (is_array($login_options))
		{
			$login_array = array_merge($login_array, $login_options);
		}

		if (isset($_POST['login']))
		{
			$user_name		= get_variable('username', 'POST');
			$user_password	= get_variable('password', 'POST');

			if (!$user_name || !$user_password)
			{
				$error = 'INCOMPLETE_LOGIN_INFO';
			}	

			if (!$error && $_CORE_CONFIG['user']['enable_confirm'])
			{
				$code = $_CLASS['core_user']->session_data_get('confirmation_code');
				$confirm_code = get_variable('confirm_code', 'POST');
	
				if (!$code || !$confirm_code || $code !== $confirm_code)
				{
				//	$error = 'CONFIRM_CODE_WRONG';
				}
			}

			if (!$error)
			{
				$result = $this->user_auth($user_name, $user_password);

				if (is_numeric($result))
				{
					$data = array(
						'admin_login'		=> $login_array['admin_login'],
						'auto_log'			=> (!empty($_POST['autologin'])) ? true : false,
						'show_online'		=> (!empty($_POST['viewonline'])) ? 0 : 1,
					);
			
					$_CLASS['core_user']->login($result, $data['admin_login'], $data['show_online']);

					$login_array['redirect'] = generate_link(get_variable('redirect', 'POST', $login_array['redirect']), array('admin' => $data['admin_login']));	

					$_CLASS['core_display']->meta_refresh(5, $login_array['redirect']);
					$message = (($login_array['success']) ? $_CLASS['core_user']->get_lang($login_array['success']) : $_CLASS['core_user']->lang['LOGIN_REDIRECT']) . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $login_array['redirect'] . '">', '</a> ');

					trigger_error($message);
				}

				$error = (is_string($result)) ? $result : 'LOGIN_ERROR';
			}
		}

		if (!$login_array['redirect'])
		{
			$login_array['redirect'] = htmlspecialchars($_CLASS['core_user']->url);
		}

		$s_hidden_fields = '<input type="hidden" name="redirect" value="' . $login_array['redirect'] . '" />';

		if ($_CORE_CONFIG['user']['enable_confirm'])
		{
			$confirm_image = '<img src="'.generate_link('system&amp;mode=confirmation_image').'" alt="" title="" />';
			$_CLASS['core_user']->session_data_set('confirmation_code', strtoupper(generate_string(6)));
		}
		else
		{
			$confirm_image = false;
		}

		$_CLASS['core_template']->assign(array(
			'LOGIN_ERROR'			=> $_CLASS['core_user']->get_lang($error),
			'LOGIN_EXPLAIN'			=> $_CLASS['core_user']->get_lang($login_array['explain']), 

			'U_SEND_PASSWORD'	 	=> ($_CORE_CONFIG['email']['email_enable']) ? generate_link('Control_Panel&amp;mode=sendpassword') : '',
			'U_RESEND_ACTIVATION'   => ($_CORE_CONFIG['user']['require_activation'] != USER_ACTIVATION_NONE && $_CORE_CONFIG['email']['email_enable']) ? generate_link('Control_Panel&amp;mode=resend_act') : '',
			'U_TERMS_USE'			=> generate_link('Control_Panel&amp;mode=terms'), 
			'U_PRIVACY'				=> generate_link('Control_Panel&amp;mode=privacy'),
			'U_REGISTER'			=> generate_link('Control_Panel&amp;mode=register'),
			'U_CONFIRM_IMAGE'		=> $confirm_image,

			'USERNAME'				=> isset($data['user_name']) ? $data['user_name'] : '',

			'S_DISPLAY_FULL_LOGIN'  => ($login_array['full_login']),
			'S_LOGIN_ACTION'		=> (!$login_array['admin_login']) ? generate_link($_CLASS['core_user']->url) : generate_link(false, array('admin' => true)),
			'S_HIDDEN_FIELDS' 		=> $s_hidden_fields,
		));

		if ($login_array['full_screen'])
		{
			$_CLASS['core_template']->display('login_body_full.html');
		}

		$_CLASS['core_template']->display(($template) ? $template : 'login_body.html');
		script_close();
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

			if (!empty($data['users_disallowed']) && in_array($this->_user_id, $data['users_disallowed']))
			{
				$return = false;
			}

			if ($return && !empty($data['groups_disallowed']) && in_array($this->_group_id, $data['groups_disallowed']))
			{
				$return = false;
			}

			return $return;
		}
		else
		{
			if (!empty($data['users_allowed']) && in_array($this->_user_id, $data['users_allowed']))
			{
				return true;
			}
	
			if (!empty($data['groups_allowed']) && in_array($this->_group_id, $data['groups_allowed']))
			{
				if (empty($data['users_disallowed']) || !in_array($this->_user_id, $data['users_disallowed']))
				{
					return true;
				}
			}

			return false;
		}
	}

	function generate_auth_options($options = array(), $options_extend = false, $return_link = false)
	{
		global $_CLASS, $site_file_root;

		$options['groups'] = empty($options['groups']) ? array() : $options['groups'];
		$options['users'] = empty($options['users']) ? array() : $options['users'];

		$mode = $return = false;
		$checks = array('add', 'remove', 'set');
		
		foreach ($checks as $check)
		{
			if (isset($_POST[$check]))
			{
				$mode = $check;
				break;
			}
		}

		if ($mode)
		{
			require_once($site_file_root.'includes/functions_user.php');
			$ids = array('groups' => array(), 'users' => array());

			switch ($mode)
			{
				case 'add':
					$setup['groups'] = get_variable('groups_add', 'POST', array(), 'array');
					$setup['users'] = explode("\n", get_variable('users_add', 'POST'));

					if (count($setup['users']))
					{
						$setup['users'] = user_get_id($setup['users']);
					}

					if (count($setup['groups']))
					{
						$sql = 'SELECT group_id
							FROM ' . GROUPS_TABLE . '
							WHERE group_id IN ('.implode(', ', array_map('intval', $setup['groups'])).')';
						$result = $_CLASS['core_db']->query($sql);

						$setup['groups'] = array();

						while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
						{
							$setup['groups'][] = $row['group_id'];
						}
						$_CLASS['core_db']->free_result($result);
					}

					foreach ($setup['groups'] as $id)
					{
						$options['groups'][$id] = array('status' => 1);
					}

					foreach ($setup['users'] as $id)
					{
						$options['users'][$id] = array('status' => 1);
					}

					unset($setup);
					//print_r($options);
				break;

				case 'remove':
					$ids['groups'] = array_map('intval', get_variable('groups_current', 'POST', array(), 'array'));
					$ids['users'] = array_map('intval', get_variable('users_current', 'POST', array(), 'array'));
					
					$function = ($mode == 'add') ? 'array_merge' : 'array_diff';

					foreach ($options['groups'] as $key => $ignore)
					{
						if (in_array($key, $ids['groups']))
						{
							unset($options['groups'][$key]);
						}
					}

					foreach ($options['users'] as $key => $ignore)
					{
						if (in_array($key, $ids['users']))
						{
							unset($options['users'][$key]);
						}
					}

					//print_r($options);
				break;
			
				case 'set':

				break;
			}

			foreach ($options as $option)
			{
				if (!empty($option))
				{
					$return =& $options;
					break;
				}
			}
		}

		$group_list = $allowed_group_list = $disallowed_group_list = $allowed_user_list = $disallowed_user_list = '';
		$groups_ids = array_keys($options['users']);

		if (!empty($options['users']))
		{
			$sql = 'SELECT user_id, username, user_colour
				FROM ' . USERS_TABLE . '
				WHERE user_id IN ('.implode(', ', array_keys($options['users'])).')
					ORDER BY username';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$user_list = ($options['users'][$row['user_id']]['status'] === 1) ? 'allowed_user_list' : 'disallowed_user_list';

				$$user_list .= '<option ' . (($row['user_colour']) ? ' style="color: #'.$row['user_colour'].';"' : '') . ' value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			}
			$_CLASS['core_db']->free_result($result);
		}

		if (!empty($groups_ids))
		{
			$sql = 'SELECT group_id, group_name, group_type 
				FROM ' . GROUPS_TABLE . '
				WHERE group_id IN ('.implode(', ', array_keys($options['groups'])).')
					ORDER BY group_type DESC, group_name';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$group_list = ($options['groups'][$row['group_id']]['status'] === 1) ? 'allowed_group_list' : 'disallowed_group_list';
				
				$$group_list .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
				
			}
			$_CLASS['core_db']->free_result($result);
		}

		$sql = 'SELECT group_id, group_name, group_type 
			FROM ' . GROUPS_TABLE . 
				(empty($groups_ids) ? '' : ' WHERE group_id NOT IN ('.implode(', ', $groups_ids).')').'
					ORDER BY group_type DESC, group_name';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$group_list .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['core_db']->free_result($result);
	
		$_CLASS['core_template']->assign(array(
			'P_ADD_GROUPS'		=> $group_list,
			'P_CURRENT_USERS'	=> $allowed_user_list,
			'P_DCURRENT_USERS'	=> $disallowed_user_list,
			'P_CURRENT_GROUPS'	=> $allowed_group_list,
			'P_DCURRENT_GROUPS'	=> $disallowed_group_list,
		));

		$_CLASS['core_template']->display('permission.html');

		return ($return !== false) ? $return : false;
	}
}

?>