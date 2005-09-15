<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||
*/

class core_auth
{
	var $_user_id;
	var $_group_ids = array();
	var $_user_group;

	var $_got_data = false;
	var $_admin_permission = array();

	function core_auth($user_id = false, $user_group = false)
	{
		global $_CLASS;

		$this->_user_id = ($user_id) ? $user_id : $_CLASS['core_user']->data['user_id'];
		$this->_user_group = ($user_group) ? $user_group : $_CLASS['core_user']->data['user_group'];
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
				if ($row['user_status'] != STATUS_ACTIVE)
				{
					$status =  ($row['user_status'] == STATUS_DISABLED) ? 'ACTIVE_ERROR' : 'UNACTIVATED_ERROR';
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

// should this be extended for defualt groups only, and all in group ?
		$sql = 'SELECT * FROM ' . ADMIN_AUTH_TABLE ." 
					WHERE user_id = {$this->_user_id}
					OR group_id = {$this->_user_group} ORDER BY user_id";

		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['admin_status'] == STATUS_PENDING)
			{
				continue;
			}

			if (!isset($this->_admin_permission[$row['admin_section']]))
			{
				$this->_admin_permission[$row['admin_section']]['core']['status'] = $row['admin_status'];

				if (trim($row['admin_options']) && is_array($row['admin_options'] = @unserialize($row['admin_options'])))
				{
					$this->_admin_permission[$row['admin_section']] += $row['options'];
				}
			}
		}

		$this->_got_data = true;
		$_CLASS['core_db']->free_result($result);
	}

	function admin_power($section, $option = false)
	{
		global $_CLASS;

		if (!$_CLASS['core_user']->is_admin || !$this->admin_auth())
		{
			return false;
		}

		if (!isset($this->_admin_permission[$section]))
		{
			return isset($this->_admin_permission['_all_']) ?  true : false;
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
				$confirm_code = get_variable('confirm_code', 'POST', false);
	
				if (!$code || !$confirm_code || $code !== $confirm_code)
				{
					$error = 'CONFIRM_CODE_WRONG';
				}
			}

			if (!$error)
			{
				$result = $this->user_auth($user_name, $user_password);

				if (is_numeric($result))
				{
					$_CLASS['core_user']->login($result, $login_array['admin_login'], !empty($_POST['hidden']), !empty($_POST['auto_login']));

					$login_array['redirect'] = generate_link(get_variable('redirect', 'POST', $login_array['redirect']), array('admin' => $login_array['admin_login']));	

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
			$_CLASS['core_user']->session_data_set('confirmation_code', generate_string(6));
		}
		else
		{
			$confirm_image = false;
		}

		$_CLASS['core_template']->assign_array(array(
			'LOGIN_ERROR'			=> $_CLASS['core_user']->get_lang($error),
			'LOGIN_EXPLAIN'			=> $_CLASS['core_user']->get_lang($login_array['explain']), 

			'U_SEND_PASSWORD'	 	=> ($_CORE_CONFIG['email']['email_enable']) ? generate_link('Control_Panel&amp;mode=sendpassword') : '',
			'U_RESEND_ACTIVATION'   => ($_CORE_CONFIG['user']['activation'] != USER_ACTIVATION_NONE && $_CORE_CONFIG['email']['email_enable']) ? generate_link('Control_Panel&amp;mode=resend_act') : '',
			'U_TERMS_USE'			=> generate_link('Control_Panel&amp;mode=terms'), 
			'U_PRIVACY'				=> generate_link('Control_Panel&amp;mode=privacy'),
			'U_REGISTER'			=> generate_link('Control_Panel&amp;mode=register'),
			'U_CONFIRM_IMAGE'		=> $confirm_image,

			'USERNAME'				=> isset($data['user_name']) ? $data['user_name'] : '',

			'S_DISPLAY_FULL_LOGIN'  => ($login_array['full_login']),
			'S_LOGIN_ACTION'		=> (!$login_array['admin_login']) ? generate_link($_CLASS['core_user']->url) : generate_link(false, array('admin' => true)),
			'S_HIDDEN_FIELDS' 		=> $s_hidden_fields,
		));

		if (!$template && $login_array['full_screen'])
		{
			$template = 'login_body_full.html';
		}

		$_CLASS['core_template']->display(($template) ? $template : 'login_body.html');
		script_close();
	}

	function auth_dump()
	{

	}

	function auth($options_array, $option = 'core_status')
	{
		global $_CLASS;

		if (!$options_array || (empty($options_array['groups']) && empty($options_array['users'])))
		{
			return true;
		}

		if (isset($options_array['users'][$this->_user_id]))
		{
			return (isset($options_array['users'][$this->_user_id][$option]) ? $options_array['users'][$this->_user_id][$option] : false);
		}

		if (!empty($options_array['groups'][1]))
		{
			$return = true;

			// need to sort/seperate this so that Only Default goups are first
			foreach ($options_array['groups'][1] as $id => $group_options)
			{
				if ($id == $this->_user_group)
				{
					return (isset($group_options[$option]) ? $group_options[$option] : false);
				}
			}
		}

		if (!empty($options_array['groups'][0]))
		{
			//get the ids for all the groups
			$ids = array_keys($options_array['groups'][0]);
			//remove the groups that the user isn't a member of
			$ids = array_intersect($ids, $this->__group_ids);
			// need to sort/seperate this so that Only Default goups are first
			foreach ($ids as $id)
			{
// will need some sort of ordering if the option is not regular  true/false (0/1)
				// if the option is not false, return it. The user has permission
				if (!empty($options_array['groups'][0][$id][$option]))
				{
					return $group_options[$option];
				}
			}
		}

		return false;
	}

	function generate_auth_options($auth_options = array(), $options_extend = false, $return_link = false)
	{
		global $_CLASS, $site_file_root;

		$auth_options['groups'][0] = empty($auth_options['groups'][0]) ? array() : $auth_options['groups'][0];
		$auth_options['groups'][1] = empty($auth_options['groups'][1]) ? array() : $auth_options['groups'][1];

		$auth_options['users'] = empty($auth_options['users']) ? array() : $auth_options['users'];

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

					$submited_options = get_variable('auth_options', 'POST', array(), 'array');

					if (count($setup['users']))
					{
						$setup['users'] = user_get_id($setup['users'], $null);
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
						$auth_options['groups'][$submited_options['core_auth_type']][$id] = array('core_status' => $submited_options['core_status']);
					}

					foreach ($setup['users'] as $id)
					{
						$auth_options['users'][$id] = array('core_status' => $submited_options['core_status']);
					}

					unset($setup);
					//print_r($auth_options); die;
				break;

				case 'remove':
					$ids['groups'] = array_map('intval', get_variable('groups_current', 'POST', array(), 'array'));
					$ids['users'] = array_map('intval', get_variable('users_current', 'POST', array(), 'array'));
					
					$function = ($mode == 'add') ? 'array_merge' : 'array_diff';

// We need to tell with is only group and with is in group.

					foreach ($ids['groups'] as $groups_id)
					{
						if (isset($auth_options['groups'][1][$groups_id]))
						{
							unset($auth_options['groups'][1][$groups_id]);
						}

						if (isset($auth_options['groups'][0][$groups_id]))
						{
							unset($auth_options['groups'][0][$groups_id]);
						}
					}

					foreach ($auth_options['users'] as $key => $ignore)
					{
						if (in_array($key, $ids['users']))
						{
							unset($auth_options['users'][$key]);
						}
					}
					
				break;
			
				case 'set':

				break;
			}

			$return = null;

			if (!empty($auth_options['users'])|| !empty($auth_options['groups'][0])	|| !empty($auth_options['groups'][1]))
			{
				$return =& $auth_options;
			}
		}

		$group_list = $allowed_group_list = $disallowed_group_list = $allowed_user_list = $disallowed_user_list = '';

		if (!empty($auth_options['users']))
		{
			$sql = 'SELECT user_id, username, user_colour
				FROM ' . USERS_TABLE . '
				WHERE user_id IN ('.implode(', ', array_keys($auth_options['users'])).')
					ORDER BY username';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$user_list = ($auth_options['users'][$row['user_id']]['core_status'] == 1) ? 'allowed_user_list' : 'disallowed_user_list';

				$$user_list .= '<option ' . (($row['user_colour']) ? ' style="color: #'.$row['user_colour'].';"' : '') . ' value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			}
			$_CLASS['core_db']->free_result($result);
		}
// this can be removed, when everthing else is updated
		$groups_ids = array_merge(array_keys($auth_options['groups'][0]), array_keys($auth_options['groups'][1]));

		if (!empty($groups_ids))
		{
			$sql = 'SELECT group_id, group_name, group_type 
				FROM ' . GROUPS_TABLE . '
				WHERE group_id IN ('.implode(', ', $groups_ids).')
					ORDER BY group_type DESC, group_name';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$group_auth_type = isset($auth_options['groups'][1][$row['group_id']]['core_status']) ? 1 : 0;
				$group_list = ($auth_options['groups'][$group_auth_type][$row['group_id']]['core_status']) ? 'allowed_group_list' : 'disallowed_group_list';
				
				$$group_list .= '<option' . (($group_auth_type == 1) ? ' style="color: #006699;"' : '') . ' value="' . $row['group_id'] . '">' . (isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
				
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
			$group_list .= '<option value="' . $row['group_id'] . '">' . (isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
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

		return $return;
	}
}

?>