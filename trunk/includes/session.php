<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal©	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

class sessions
{
	var $load;
	var $new_session = false;
	var $save_session = false;

	var $sid_link_prefex = 'sid';
	var $sid_link = false;

	function start()
	{
		global $_CLASS, $_CORE_CONFIG, $SID, $mod;
		
		$this->server_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') ? true : false;

		$session_data = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_data', 'COOKIE'));
// should spearate this, since I have to check to make sure the 2 values are in the array, would be easy to not do this
		$session_data = (is_array($session_data)) ? $session_data : array();
		$session_data['session_id'] = get_variable('sid', 'GET');

		if ($cookie_sid = get_variable($_CORE_CONFIG['server']['cookie_name'] . '_sid', 'COOKIE'))
		{
			// session id in url > cookie
			if (!$session_data['session_id'] || ($cookie_sid === $session_data['session_id']))
			{
				$session_data['session_id'] = $cookie_sid;
				$this->sid_link = (defined('NEED_SID')) ? 'sid='.$session_data['session_id'] : false;
			}
		}
		else
		{
			$this->sid_link = 'sid='.$session_data['session_id'];
		}

		if ($session_data['session_id'])
		{
			$sql = 'SELECT u.*, s.*
				FROM ' . SESSIONS_TABLE . ' s, ' . USERS_TABLE . " u
				WHERE s.session_id = '" . $_CLASS['core_db']->escape($session_data['session_id']) . "'
					AND u.user_id = s.session_user_id";
					
			$result = $_CLASS['core_db']->query($sql);

			$this->data = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (isset($this->data['user_id']))
			{
				$valid  = true;

				if ($_CORE_CONFIG['server']['browser_check'] && ($this->data['session_browser'] != $this->browser))
				{
					$valid  = false;
				}

				if ($valid && $_CORE_CONFIG['server']['ip_check'])
				{
					$check_ip = implode('.', explode('.', $this->data['session_ip'], $_CORE_CONFIG['server']['ip_check']));
					
					if ($check_ip != substr($this->ip, 0, strlen($check_ip)))
					{
						$valid  = false;
					}
				}

				if ($valid)
				{
					// Set session update a minute or so after last update or if page changes
					if (($this->time - $this->data['session_time']) > 60 || ($this->data['session_url'] != $this->url))
					{
						$this->save_session = true;
					}
					
					$this->data['session_data'] = ($this->data['session_data']) ? unserialize($this->data['session_data']) : array();
					
					$this->is_user	= ($this->data['user_id'] != ANONYMOUS && ($this->data['user_type'] == USER_NORMAL || $this->data['user_type'] == USER_FOUNDER));
					$this->is_bot 	= (!$this->is_user && $this->data['user_id'] != ANONYMOUS);
					$this->is_admin = ($this->data['session_admin'] == ADMIN_IS_ADMIN);

					check_maintance_status();
					
					$this->load = check_load_status();

					return true;
				}
			}

			$this->data = array();
		}

		check_maintance_status();
		$this->load = check_load_status();

		return $this->login();
	}

	function can_create()
	{
		global $_CLASS, $_CORE_CONFIG;

		if ($_CORE_CONFIG['server']['limit_sessions'])
		{
			$sql = 'SELECT COUNT(*) AS sessions
				FROM ' . SESSIONS_TABLE . '
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
	function session_create()
	{
		global $_CLASS, $_CORE_CONFIG, $config;
		$auto_log = false;

		$this->data['session_last_visit'] = ($this->data['user_last_visit']) ? $this->data['user_last_visit'] : $this->time;

		$session_id = (function_exists('sha1')) ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true));

		$session_data = array(
			'session_id'			=> (string) $session_id,
			'session_user_id'		=> (int) $this->data['user_id'],
			'session_start'			=> (int) $this->time,
			'session_time'			=> (int) $this->time,
			'session_browser'		=> (string) $this->browser,
			'session_page'			=> (string) $this->page,
			'session_url'			=> (string) $this->url,
			'session_ip'			=> (string) $this->ip,
			'session_user_type'		=> (string) $this->data['user_type'],
			'session_admin'			=> (int) $this->data['session_admin'],
			'session_auth'			=> (int) serialize($_CLASS['core_auth']->auth_dump()),
			'session_viewonline'	=> (int) $this->data['session_viewonline'],
		);

		$_CLASS['core_db']->query('INSERT INTO ' . SESSIONS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $session_data));

		$this->new_session =  true;

		$this->data = array_merge($this->data, $session_data);
		unset($session_data);

		if ($this->time > $config['session_last_gc'] + $config['session_gc'])
		{
			$this->gc($this->time);
		}

		if (!$this->is_bot)
		{
			if ($auto_log && $this->is_user)
			{
				$cookie_data['login_code'] = $session_data['user_password'];
				$cookie_data['user_id'] = $this->data['user_id'];
				
				$this->set_cookie('data', serialize($cookie_data), $this->time + 31536000);
			}
			elseif ($this->new_session)
			{
				$this->set_cookie('data', '', 0);
			}

			$this->set_cookie('sid', $this->data['session_id'], 0);
		}

		$this->sid_link = ($this->is_bot) ? false : 'sid='.$this->data['session_id'];

		$this->data['sessions'] = array();

		return true;
	}

	function session_destroy($session_id = false)
	{
		global $_CLASS;

		if (!$session_id)
		{
			$session_id = $this->data['session_id'];
			
			$this->set_cookie('data', '', $this->time - 31536000);
			$this->set_cookie('sid', '', $this->time - 31536000);
			
			if ($this->data['session_time'])
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_last_visit = ' . $this->data['session_time'] . '
					WHERE user_id = ' . $this->data['user_id'];
				$_CLASS['core_db']->query($sql);
			}
		}
		
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
			WHERE session_id = '" . $_CLASS['core_db']->escape($session_id)."'";

		$_CLASS['core_db']->query($sql);
	}
	
	function gc($time)
	{
		global $_CORE_CONFIG, $_CLASS;

		$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
			WHERE session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']);

		$result = $_CLASS['core_db']->query($sql);

		$_CLASS['core_db']->optimize_tables('SESSIONS_TABLE');
	}

	function session_data_get($name)
	{
		return (empty($this->data['session_data'][$name])) ? false : $this->data['session_data'][$name];
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
			'session_data'			=> ($this->data['session_data']) ? serialize($this->data['session_data']) : '',
			'session_page'			=> (string) $this->page,
			'session_time'			=> (int) $this->time,
			'session_url'			=> (string) $this->url,
		);

		$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_array) . "
				WHERE session_id = '" . $_CLASS['core_db']->escape($this->data['session_id']) . "'";

		$_CLASS['core_db']->query($sql);

		$this->session_save = false;
	}
}

?>