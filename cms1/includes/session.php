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
/*
	Contains Parts Based from phpBB3
	copyright (c) 2005 phpBB Group 
	license http://opensource.org/licenses/gpl-license.php GNU Public License 
*/

class sessions
{
	var $load;
	var $new_session = false;
	var $save_session = false;
	var $need_url_id = true;

	function start()
	{
		global $_CLASS, $_CORE_CONFIG, $SID, $mod;
		
		$this->server_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') ? true : false;

		$this->need_url_id = true;

		$session_data = (!empty($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_data'])) ? unserialize(stripslashes($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_data'])) : array();
		$session_data['session_id'] = get_variable('sid', 'GET', false);

		if (!empty($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']))
		{
			// session id in url > cookie
			if (!$session_data['session_id'] || (trim($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']) === $session_data['session_id']))
			{
				$session_data['session_id'] = trim($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']);
				$this->need_url_id = (defined('NEED_SID')) ? true : false;
			}
		}

		if ($session_data['session_id'])
		{
			$sql = 'SELECT u.*, s.*
				FROM ' . SESSIONS_TABLE . ' s, ' . USERS_TABLE . " u
				WHERE s.session_id = '" . $_CLASS['core_db']->sql_escape($session_data['session_id']) . "'
					AND u.user_id = s.session_user_id";
					
			$result = $_CLASS['core_db']->sql_query($sql);

			$this->data = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if (isset($this->data['user_id']))
			{
				$valid  = true;

				if ($_CORE_CONFIG['server']['browser_check'] && ($this->data['session_browser'] != $this->browser))
				{
					$valid  = false;
				}

				if ($_CORE_CONFIG['server']['ip_check'])
				{
					$s_ip = implode('.', explode('.', $this->data['session_ip'], $_CORE_CONFIG['server']['ip_check']));
					$u_ip = implode('.', explode('.', $this->ip, $_CORE_CONFIG['server']['ip_check']));
					
					if ($u_ip != $s_ip)
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
					$this->user_setup();
					
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
			$result = $_CLASS['core_db']->sql_query($sql);
		
			$row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
		
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

		$this->data['session_last_visit'] = ($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : $this->time;

		$session_id = (function_exists('sha1')) ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true));

		$session_data = array(
			'session_id'			=> (string) $session_id,
			'session_user_id'		=> (int) $this->data['user_id'],
			'session_start'			=> (int) $this->time,
			'session_last_visit'	=> (int) $this->data['session_last_visit'],
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

		$_CLASS['core_db']->sql_query('INSERT INTO ' . SESSIONS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $session_data));

		$this->new_session = $this->need_url_id = true;

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

		$this->need_url_id = ($this->is_bot) ? false : true;

		$this->data['sessions'] = array();
		$this->user_setup();

		return true;
	}

	// Destroy a session
	function session_destroy($session_id = false, $return = false)
	{
		global $_CLASS;

		if (!$session_id)
		{
			$session_id = $this->data['session_id'];
			$id = $this->data['user_id'];
			
			$this->set_cookie('data', '', $this->time - 31536000);
			$this->set_cookie('sid', '', $this->time - 31536000);
			
			if ($this->data['session_time'])
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_lastvisit = ' . $this->data['session_time'] . '
					WHERE user_id = ' . $id;
				$_CLASS['core_db']->sql_query($sql);
			}
		}
		
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
			WHERE session_id = '" . $_CLASS['core_db']->sql_escape($session_id)."'";

		$_CLASS['core_db']->sql_query($sql);

		if ($return)
		{
			return;
		}

		// Reset some basic data immediately
		$sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . ANONYMOUS;
		$result = $_CLASS['core_db']->sql_query($sql);
	
		$this->data = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);

		$this->data['session_id'] = '';
		$this->data['session_time'] = $this->data['session_admin'] = 0;

		$this->need_url_id = $this->is_user = $this->is_bot = $this->is_admin = false;
	}
	
	// Garbage collection
	function gc($time)
	{
		global $_CLASS, $_CORE_CONFIG;

		$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
			WHERE session_user_id = ' . ANONYMOUS . '
				AND session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']);
		$_CLASS['core_db']->sql_query($sql);

		switch (SQL_LAYER)
		{
			case 'mysql4':
			case 'mysqli':
			
				// Keep only the most recent session for each user
				// Note: if the user is currently browsing the board, his
				// last_visit field won't be updated, which I believe should be
				// the normal behavior anyway
				$_CLASS['core_db']->sql_return_on_error(TRUE);

				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					USING ' . SESSIONS_TABLE . ' s1, ' . SESSIONS_TABLE . ' s2
					WHERE s1.session_user_id = s2.session_user_id
						AND s1.session_time < s2.session_time';
				$_CLASS['core_db']->sql_query($sql);

				$_CLASS['core_db']->sql_return_on_error(FALSE);

				// Update last visit time
				$sql = 'UPDATE ' . USERS_TABLE. ' u, ' . SESSIONS_TABLE . ' s
					SET u.user_lastvisit = s.session_time, u.user_lastpage = s.session_page
					WHERE s.session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']) . '
						AND u.user_id = s.session_user_id';
				$_CLASS['core_db']->sql_query($sql);

				// Delete everything else now
				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					WHERE session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']);
				$_CLASS['core_db']->sql_query($sql);

				set_config('session_last_gc', $time);
				break;

			default:
// need one for postgres

				// Get expired sessions, only most recent for each user
				$sql = 'SELECT session_user_id, session_page, MAX(session_time) AS recent_time
					FROM ' . SESSIONS_TABLE . '
					WHERE session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']) . '
						GROUP BY session_user_id, session_page';

				$result = $_CLASS['core_db']->sql_query_limit($sql, 5);
				$row = $_CLASS['core_db']->sql_fetchrow($result);
				
				if (!$row)
				{
					return set_config('session_last_gc', $time);
				}
				
				$user_id = array();

				do
				{
					$sql = 'UPDATE ' . USERS_TABLE . '
						SET user_lastvisit = ' . $row['recent_time'] . ", user_lastpage = '" . $_CLASS['core_db']->sql_escape($row['session_page']) . "'
						WHERE user_id = " . $row['session_user_id'];
					$_CLASS['core_db']->sql_query($sql);

					$user_id[] = $row['session_user_id'];
				}
				while ($row = $_CLASS['core_db']->sql_fetchrow($result));

				$_CLASS['core_db']->sql_freeresult($result);


				if (count($user_id) < 5)
				{
					$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
						WHERE session_time < " . ($time - $_CORE_CONFIG['server']['session_length']);

					set_config('session_last_gc', $time);
				}
				else
				{
					$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
						WHERE session_user_id IN ($del_user_id)
							AND session_time < " . ($time - $_CORE_CONFIG['server']['session_length']);
				}

				$_CLASS['core_db']->sql_query($sql);

				break;
		}

		return;
	}

	function get_data($name)
	{
		return (empty($this->data['session_data'][$name])) ? false : $this->data['session_data'][$name];
	}

	function kill_data($name)
	{
		if (empty($this->data['session_data'][$name]))
		{
			return;
		}

		unset($this->data['session_data'][$name]);
		$this->save_session = true;
	}

	function set_data($name, $value, $force_save = false)
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
				WHERE session_id = '" . $_CLASS['core_db']->sql_escape($this->data['session_id']) . "'";

		$_CLASS['core_db']->sql_query($sql);

		$this->session_save = false;
	}

	function set_cookie($name, $cookiedata, $cookietime)
	{
		global $_CORE_CONFIG;

		if ($this->server_local)
		{
			setcookie($_CORE_CONFIG['server']['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $_CORE_CONFIG['server']['cookie_path']);
		}
		else
		{
			setcookie($_CORE_CONFIG['server']['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $_CORE_CONFIG['server']['cookie_path'], $_CORE_CONFIG['server']['cookie_domain'], $_CORE_CONFIG['server']['cookie_secure']);
		}
	}
}

?>