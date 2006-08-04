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

$Id$
*/

class sessions
{
	var $load;
	var $new_session = false;
	var $save_session = false;

	var $sid_link_prefex = 'sid';
	var $sid_link = false;
	var $need_sid = true;

	var $autologin_code = '';

	function start()
	{
		global $_CLASS, $_CORE_CONFIG, $SID, $mod;

		$session_id = get_variable($_CORE_CONFIG['server']['cookie_name'] . '_sid', 'COOKIE');
		$session_id_url = get_variable('sid', 'GET');

		if ($session_id_url && (!$session_id || $session_id !== $session_id_url))
		{
			$session_id = $session_id_url;
		}
		elseif (!defined('NEED_SID'))
		{
			$this->need_sid = false;
		}

		if ($session_id)
		{
			$sql = 'SELECT u.*, s.*
				FROM ' . CORE_SESSIONS_TABLE . ' s, ' . CORE_USERS_TABLE . " u
				WHERE s.session_id = '" . $_CLASS['core_db']->escape($session_id) . "'
					AND u.user_id = s.session_user_id";
			$result = $_CLASS['core_db']->query($sql);

			$this->data = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (isset($this->data['user_id']) && ($this->data['user_id'] == ANONYMOUS || $this->data['user_status'] == STATUS_ACTIVE))
			{
				$valid  = true;

				if ($this->data['session_browser'] !== $this->browser)
				{
					$valid  = false;
				}

				if ($valid && $_CORE_CONFIG['server']['ip_check'])
				{
					$check_ip = implode('.', explode('.', $this->data['session_ip'], $_CORE_CONFIG['server']['ip_check']));
					
					if ($check_ip !== substr($this->ip, 0, strlen($check_ip)))
					{
						$valid  = false;
					}
				}

				if ($valid)
				{
					// Set session update a minute or so after last update or if page changes
					if (($this->time - $this->data['session_time']) > 60 || ($this->data['session_url'] !== $this->url))
					{
						$this->save_session = true;
					}

					$this->data['session_data'] = ($this->data['session_data']) ? unserialize($this->data['session_data']) : array();
					$this->data['user_data'] = ($this->data['user_data']) ? unserialize($this->data['user_data']) : array();

					load_class(false, 'core_auth', 'auth_db');

					$this->is_user	= ($this->data['user_type'] == USER_NORMAL);
					$this->is_bot 	= ($this->data['user_type'] == USER_BOT);
					$this->is_admin = ($this->data['session_admin'] == ADMIN_IS_ADMIN);

					check_maintance_status();
					
					if ($this->is_bot)
					{
						$this->need_sid = false;
					}

					$this->autologin_code = $this->data['session_autologin'];
					$this->load = check_load_status();
					$this->sid_link = 'sid='.$this->data['session_id'];

					return true;
				}
			}

			$this->data = array();
		}

		$user_id = ANONYMOUS;

		$ali = get_variable($_CORE_CONFIG['server']['cookie_name'] . '_ali', 'COOKIE', false, 'int');
		$alc = get_variable($_CORE_CONFIG['server']['cookie_name'] . '_alc', 'COOKIE');

		if ($ali && $alc)
		{
			if ($id = $this->autologin_retrieve($ali, $alc))
			{
				$user_id = $id;
			}
		}

		check_maintance_status();
		$this->load = check_load_status();

		return $this->login($user_id);
	}

	function can_create()
	{
		global $_CLASS, $_CORE_CONFIG;

		if ($_CORE_CONFIG['server']['limit_sessions'])
		{
			$sql = 'SELECT COUNT(*) AS sessions
				FROM ' . CORE_SESSIONS_TABLE . '
				WHERE session_time >= ' . ($this->time - 60);
			$result = $_CLASS['core_db']->query($sql);
		
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);
		
			if ((int) $row['sessions'] > (int) $_CORE_CONFIG['server']['limit_sessions'])
			{
				$this->gc($this->time);
		
				return false;
			}
		}

		return true;
	}

	// Create a new session
	function session_create($auto_log = false)
	{
		global $_CLASS, $_CORE_CONFIG, $config;

		if (isset($this->data['session_id']) && $this->data['session_id'])
		{
			$this->session_destroy(false, false);
		}

		if ($this->is_bot)
		{
			$sql = 'SELECT u.*, s.*
				FROM ' . CORE_SESSIONS_TABLE . ' s, ' . CORE_USERS_TABLE . ' u
				WHERE s.session_user_id = '. (int) $this->data['user_id'] .'
					AND u.user_id = s.session_user_id';

			$result = $_CLASS['core_db']->query($sql);
			$temp = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);
			
			if ($temp)
			{
				$this->data = $temp;

				if (($this->time - $this->data['session_time']) > 60 || ($this->data['session_url'] !== $this->url))
				{
					$this->save_session = true;
				}

				return true;
			}
		}

		$this->data['session_last_visit'] = ($this->data['user_last_visit']) ? $this->data['user_last_visit'] : $this->time;

		$session_id = function_exists('sha1') ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true));

		$session_data = array(
			'session_id'		=> (string) $session_id,
			'session_user_id'	=> (int) $this->data['user_id'],
			'session_start'		=> (int) $this->time,
			'session_time'		=> (int) $this->time,
			'session_browser'	=> (string) $this->browser,
			'session_page'		=> (string) $this->page, // need to be added back
			'session_url'		=> (string) $this->url,
			'session_ip'		=> (string) $this->ip,
			'session_last_visit'=> (int) $this->data['session_last_visit'],
			'session_user_type'	=> (int) $this->data['user_type'],
			'session_admin'		=> (int) $this->data['session_admin'],
			'session_auth'		=> (int) serialize($_CLASS['core_auth']->auth_dump()),
			'session_hidden'	=> (int) $this->data['session_hidden'],
			'session_autologin'	=> (string) $this->autologin_code,
		);

		$_CLASS['core_db']->sql_query_build('INSERT', $session_data, CORE_SESSIONS_TABLE);

		$this->new_session =  true;

		$this->data = array_merge($this->data, $session_data);
		unset($session_data);

		$this->gc($this->time);

		/*if ($this->time > $config['session_last_gc'] + $config['session_gc'])
		{
			$this->gc($this->time);
		}*/

		if (!$this->is_bot)
		{
			if ($auto_log && $this->is_user)
			{
				$this->autologin_generate();
			}

			$this->set_cookie('sid', $session_id, 0);
		}

		$this->need_sid = ($this->is_bot) ? false : true;
		$this->sid_link = 'sid='.$this->data['session_id'];

		$this->data['sessions'] = array();

		return true;
	}

	function autologin_generate()
	{
		global $_CLASS;

		$this->autologin_code = function_exists('sha1') ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true));

		$data_array = array(
			'user_id'				=> (int) $this->data['user_id'],
			'auto_login_code'		=> (string) $this->autologin_code,
			'auto_login_browser'	=> (string) $this->browser,
			'auto_login_time'		=> (int) $this->time,
		);

		$_CLASS['core_db']->sql_query_build('INSERT', $data_array, CORE_SESSIONS_AUTOLOGIN_TABLE);

		$this->set_cookie('ali', $this->data['user_id'], $this->time + 2592000);
		$this->set_cookie('alc', $this->autologin_code, $this->time + 2592000);
	}

	function autologin_retrieve($user_id, $code)
	{
		global $_CLASS;

		settype($user_id, 'int');

		$sql = 'SELECT * FROM ' . CORE_SESSIONS_AUTOLOGIN_TABLE . " 
					WHERE user_id = $user_id
					AND auto_login_code = '" . $_CLASS['core_db']->escape($code) . "'";

		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$return = false;

		// This is about all we can validate other than the code, and user_id
		if ($row && $row['auto_login_browser'] === $this->browser)
		{
			$return = $user_id;

			$this->autologin_code = function_exists('sha1') ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true));

			$data_array = array(
				'auto_login_code'		=> (string) $this->autologin_code,
				'auto_login_time'		=> (int) $this->time,
			);
		
			$sql = 'UPDATE ' . CORE_SESSIONS_AUTOLOGIN_TABLE . '	SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data_array) . "
						WHERE user_id = $user_id
						AND auto_login_code = '" . $_CLASS['core_db']->escape($code) . "'";
			$_CLASS['core_db']->query($sql);

			// need to update both the code and the user_id,
			// since user_id cookie has an expiry time
			$this->set_cookie('alc', $this->autologin_code, $this->time + 2592000);
			$this->set_cookie('ali', $user_id, $this->time + 2592000);
		}

		return $return;
	}

	function autologin_destroy($user_id, $code)
	{
		global $_CLASS;
		
		$sql = 'DELETE FROM ' . CORE_SESSIONS_AUTOLOGIN_TABLE . ' 
			WHERE user_id = '.(INT) $user_id."
			AND auto_login_code = '" . $_CLASS['core_db']->escape($code) . "'";
				
		$_CLASS['core_db']->query($sql);
	}

	function session_destroy($session_id = false, $logout = true)
	{
		global $_CLASS;

		if (!$session_id)
		{
			$session_id = $this->data['session_id'];

			if ($logout)
			{
				$this->set_cookie('sid', '', $this->time - 31536000);
	
				if ($this->autologin_code)
				{
					$this->autologin_destroy($this->data['user_id'], $this->autologin_code);
				}
			}

			$this->save_session = false;
		}

		$sql = 'DELETE FROM ' . CORE_SESSIONS_TABLE . "
			WHERE session_id = '" . $_CLASS['core_db']->escape($session_id)."'";

		$_CLASS['core_db']->query($sql);

		$_CLASS['core_db']->optimize_tables(CORE_SESSIONS_TABLE);
	}

	function gc($time)
	{
// Move to cron
		if (!is_numeric($time))
		{
			return;
		}

		global $_CORE_CONFIG, $_CLASS;

		$_CLASS['core_db']->transaction();

		// Remove all expired guess sessions
		$sql = 'DELETE FROM ' . CORE_SESSIONS_TABLE . '
			WHERE session_user_id = ' . ANONYMOUS . '
			AND session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']);
		$_CLASS['core_db']->query($sql);

// see if other database supports UPDATE sELECT
		switch ($_CLASS['core_db']->db_layer)
		{
			//Oracle8 suporrts this also, I believe
			case 'mysql':
			case 'mysqli':
				$sql = 'UPDATE ' . CORE_USERS_TABLE. ' u, ' . CORE_SESSIONS_TABLE . ' s
							SET u.user_last_visit = s.session_time
								WHERE s.session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']) . '
								AND u.user_id = s.session_user_id';
				$_CLASS['core_db']->query($sql);
			break;

			default:
				$sql = 'SELECT session_user_id, MAX(session_time) AS session_time
							FROM ' . CORE_SESSIONS_TABLE . '
								WHERE session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']) . '
								GROUP BY session_user_id';
				$result = $_CLASS['core_db']->query($sql);
				
				// Should be fast with the transaction
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$sql = 'UPDATE ' . CORE_USERS_TABLE . '
							SET user_last_visit = ' . $row['session_time'] . '
								WHERE user_id = ' . $row['session_user_id'];
					$_CLASS['core_db']->query($sql);
				}
			break;
		}

		$sql = 'DELETE FROM ' . CORE_SESSIONS_TABLE . '
				WHERE session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']);
			$_CLASS['core_db']->query($sql);

		$sql = 'DELETE FROM ' . CORE_SESSIONS_AUTOLOGIN_TABLE . '
					WHERE auto_login_time < ' . ($time - 2592000);
		$_CLASS['core_db']->query($sql);
		
		$_CLASS['core_db']->transaction('commit');

		$_CLASS['core_db']->optimize_tables(CORE_SESSIONS_TABLE);
	}

	function session_data_get($name, $default = false)
	{
		return empty($this->data['session_data'][$name]) ? $default : $this->data['session_data'][$name];
	}

	function session_data_remove($name)
	{
		if (empty($this->data['session_data'][$name]))
		{
			return;
		}

		unset($this->data['session_data'][$name]);
		$this->save_session = true;
	}

	function session_data_set($name, $value, $force_save = false)
	{
		if (!empty($this->data['session_data'][$name]) && ($this->data['session_data'][$name] == $value))
		{
			return;
		}
		
		$this->data['session_data'][$name] = $value;
		$this->save_session = true;
		
		if ($force_save)
		{
			$this->save();
		}
	}

	function save()
	{
		global $_CLASS;

		if (!$this->save_session)
		{
			return;
		}

		$sql_array = array(
			'session_data'			=> !empty($this->data['session_data']) ? serialize($this->data['session_data']) : '',
			'session_page'			=> (string) $this->page,
			'session_time'			=> (int) $this->time,
			'session_url'			=> (string) $this->url,
		);

		$sql = 'UPDATE ' . CORE_SESSIONS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_array) . "
				WHERE session_id = '" . $_CLASS['core_db']->escape($this->data['session_id']) . "'";

		$_CLASS['core_db']->query($sql);

		$this->session_save = false;
	}
}

?>