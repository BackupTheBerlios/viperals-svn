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

// -------------------------------------------------------------
//
// COPYRIGHT : © 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
//
// -------------------------------------------------------------
// session_url will alway contain the users last url. This is in {module}{queries} format

/*
To do
	Complete only registered site feather
	Clean up
	Banning system
	Clean up again :-)
*/

class session
{
	var $data = array();
	var $browser = '';
	var $ip = '';
	var $url = '';
	var $page = '';
	var $load;
	var $new_data = false;
	var $new_session = false;
	var $session_save = false;
	var $need_url_id = true;


	function startup()
	{
		global $_CLASS, $_CORE_CONFIG, $SID, $mod;
		
		$this->time = time();
		$this->server_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') ? true : false;
		$this->browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $_ENV['HTTP_USER_AGENT'];
		$this->url = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : $_ENV['REQUEST_URI'];
		$this->page = $mod;

		if ($pos = strpos($this->url, INDEX_PAGE.'?mod=') !== false)
		{
			$pos = $pos + strlen(INDEX_PAGE.'?mod='); 
			$this->url = substr($this->url, $pos);
			
			if (($pos = strpos($this->url, 'sid')) !== false)
			{
				$this->url = substr($this->url, 0, $pos-1);
			}
		}
		else
		{
			$this->url = '';
		}

		if (!isset($_COOKIE))
		{
			$_COOKIE = array();
		}
		
		$this->need_url_id = true;
		
		$session_data = (!empty($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_data'])) ? unserialize(stripslashes($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_data'])) : array();
		$session_data['session_id'] = get_variable('sid', 'GET', false);
		
		//print_r($session_data);
		if (!empty($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']))
		{
			// session id in url > cookie
			if (!$session_data['session_id'] || (trim($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']) === $session_data['session_id']))
			{
				$session_data['session_id'] = trim($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']);
				$this->need_url_id = (defined('NEED_SID')) ? true : false;
			}
		}
		
		// Obtain users IP
		$this->ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');

		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$private_ip = array('#^0\.#', '#^127\.0\.0\.1#', '#^192\.168\.#', '#^172\.16\.#', '#^10\.#', '#^224\.#', '#^240\.#');
			foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $x_ip)
			{
				if (preg_match('#([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)#', $x_ip, $ip_list))
				{
					if (($this->ip = trim(preg_replace($private_ip, $this->ip, $ip_list[1]))) == trim($ip_list[1]))
					{
						break;
					}
				}
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
	
			// Did the session exist in the DB?
			if (isset($this->data['user_id']))
			{
				// Validate IP length according to admin ... has no effect on IPv6
				$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, $_CORE_CONFIG['server']['ip_check']));
				$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, $_CORE_CONFIG['server']['ip_check']));

				$s_browser = ($_CORE_CONFIG['server']['browser_check']) ? $this->data['session_browser'] : '';
				$u_browser = ($_CORE_CONFIG['server']['browser_check']) ? $this->browser : '';

				if ($u_ip == $s_ip && $s_browser == $u_browser)
				{
					// Set session update a minute or so after last update or if page changes
					if (($this->time - $this->data['session_time']) > 60 || ($this->data['session_url'] != $this->url))
					{
						$this->session_save = true;
					}
					
					if ($this->data['session_data'])
					{
						$this->data['session_data'] = unserialize($this->data['session_data']);
					}
					else
					{
						$this->data['session_data'] = array();
					}
					
					$this->is_user = ($this->data['user_id'] != ANONYMOUS && ($this->data['user_type'] == USER_NORMAL || $this->data['user_type'] == USER_FOUNDER)) ? true : false;
					$this->is_bot 	= (!$this->is_user && $this->data['user_id'] != ANONYMOUS) ? true : false;
					$this->is_admin = ($this->data['session_admin'] == ADMIN_IS_ADMIN) ? true : false;

					check_maintance_status();
					
					if (check_load_status())
					{
						$this->load = check_load_status();
					}
					
					if ($_CORE_CONFIG['global']['only_registered'] && !$this->is_user)
					{
						$this->need_url_id = false;
						login_box(array('full_screen'	=> true));
					}
					
					$this->user_setup();
					
					return true;
				}
			}
			
			$this->data = array();
		}
		
		check_maintance_status();
		
		if (check_load_status())
		{
			$this->load = check_load_status();
		}

		if ($_CORE_CONFIG['global']['only_registered'])
		{
			$this->need_url_id = false;
			login_box(array('full_screen'	=> true));
		}
		
		if (isset($session_data['login_code']) && isset($session_data['user_id']))
		{
			return $this->create(array('user_id' => $session_data['user_id'], 'user_password' => $session_data['login_code'], 'auto_log' => true));
		}

		return $this->create();
	}

	// Create a new session
	function create($data = array())
	{
		global $_CLASS, $_CORE_CONFIG, $config;
		
		$session_data = array(
			'user_name'			=> false,
			'user_id'			=> false,
			'user_password'		=> false,
			'admin_login'		=> false,
			'auth_error_return'	=> false,
			'auto_log'			=> false,
			'view_online'		=> true,
		);
		
		$session_data = array_merge($session_data, array_filter($data));
		
		$session_data['user_id']	= (int) $session_data['user_id'];
		$session_data['is_bot']		= false;
		
		$session_data['old_session_id'] = !empty($this->data['session_id']) ? $this->data['session_id'] : false;
		$session_data['old_id'] 		= !empty($this->data['user_id']) ? $this->data['user_id'] : false;

		$bots_array = get_bots();
// maybe make a check bot status funtion so it be reused
		foreach ($bots_array as $bot)
		{
			if ($bot['user_agent'] && preg_match('#' . preg_quote($bot['user_agent'], '#') . '#i', $this->browser))
			{
				$session_data['is_bot'] = true;
			}
			
			if ($bot['user_ip'] && (!$bot['user_agent'] || $session_data['is_bot']))
			{
				$session_data['is_bot'] = false;
				
				foreach (explode(',', $bot['user_ip']) as $bot_ip)
				{
					if (strpos($this->ip, $bot_ip) === 0)
					{
						$session_data['is_bot'] = true;
					}
				}
			}
			
			if ($session_data['is_bot'])
			{
				if ($bot['user_type'] == USER_BOT_INACTIVE)
				{
					// How would this affect indexing ?
					header("HTTP/1.0 503 Service Unavailable");
					script_close(false);
					die;
				}
				
				$session_data['user_id'] = $bot['user_id'];
				break;
			}
		}
		
		if ($_CORE_CONFIG['server']['limit_sessions'] && (!$session_data['old_id'] || $session_data['old_id'] == ANONYMOUS) && (VIPERAL != 'ADMIN'))
		{
			$sql = 'SELECT COUNT(*) AS sessions
				FROM ' . SESSIONS_TABLE . '
				WHERE session_time >= ' . ($this->time - 60);
			$result = $_CLASS['core_db']->sql_query($sql);

			$row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if (intval($row['sessions']) > intval($_CORE_CONFIG['server']['limit_sessions']))
			{
				$this->gc($this->time);

				if (!$session_data['is_bot'])
				{
					$this->user_setup();
					trigger_error('SITE_UNAVAILABLE', E_USER_ERROR);
				}

				header("HTTP/1.0 503 Service Unavailable");
				script_close(false);
				die;
			}
		}

		$auth = false;

		if ($session_data['user_password'] && ($session_data['user_name'] || $session_data['user_id']))
		{
			$auth = $this->auth($session_data);

			if ($session_data['auth_error_return'] && $auth !== true)
			{
				return $auth;
			}
			// error here if loggin is from a cookie
		}

		if ($auth === true)
		{
			if ($session_data['user_id']) 
			{
				$where_sql =  'u.user_id = '.$session_data['user_id'];
			}
			else
			{
				$where_sql =  "u.username = '".$_CLASS['core_db']->sql_escape($session_data['user_name'])."'";
			}
			
			$sql = 'SELECT * FROM ' . USERS_TABLE . ' u
						LEFT JOIN ' . SESSIONS_TABLE . ' s ON ( u.user_id = s.session_user_id )
						WHERE '.$where_sql;
					
			$result = $_CLASS['core_db']->sql_query($sql);
			$this->data = $_CLASS['core_db']->sql_fetchrow($result);
			
			$_CLASS['core_db']->sql_freeresult($result);
			
			if ($this->data['session_id'] && !$session_data['is_bot'])
			{
				if ($this->browser != $this->data['session_browser'] || $this->ip != $this->data['session_ip'])
				{
// clear data here
// need to make a single non changing array so we can reset these thing without ot much coding
				}
			}

			$this->is_user = (!$session_data['is_bot']) ? true : false;
			
			if ($session_data['admin_login'])
			{
				$session_admin = ($this->is_user && $_CLASS['core_auth']->admin_power()) ? ADMIN_IS_ADMIN : ADMIN_NOT_ADMIN;
			}
			else
			{
				$session_admin = ADMIN_NOT_LOGGED;
			}

			$this->is_admin = ($session_admin == ADMIN_IS_ADMIN) ? true : false;
		}

		$this->is_bot = ($session_data['is_bot']) ? true : false;

		if ($auth !== true)
		{
			$sql = 'SELECT *
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . ANONYMOUS;
			$result = $_CLASS['core_db']->sql_query($sql);
	
			$this->data = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			// reset and add some basics
			$session_data += array('user_name' => false, 'user_id' => false, 'user_password' => false, 'auto_log' => false);
			$this->data['session_time'] = $this->data['session_id'] = 0;

			$this->is_admin	= $this->is_user = false;
			$session_admin = ADMIN_NOT_ADMIN;
		}
		
		if ($session_data['old_session_id'] && $session_data['old_session_id'] != $this->data['session_id'])
		{
			$this->destroy($session_data['old_session_id'], $session_data['old_id'], true);
		}
		
		$this->data['session_last_visit'] = ($this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : time());
		$view_online = (!$this->data['user_allow_viewonline']) ? 0 : (($session_data['view_online']) ? 1 : 0);
		
		$sql_array = array(
			'session_user_id'		=> (int) $this->data['user_id'],
			'session_start'			=> (int) $this->time,
			'session_last_visit'	=> (int) $this->data['session_last_visit'],
			'session_time'			=> (int) $this->time,
			'session_browser'		=> (string) $this->browser,
			'session_page'			=> (string) $this->page,
			'session_url'			=> (string) $this->url,
			'session_ip'			=> (string) $this->ip,
			'session_user_type'		=> (string) $this->data['user_type'],
			'session_admin'			=> (int) $session_admin,
			'session_auth'			=> (int) serialize($_CLASS['core_auth']->auth_dump()),
			'session_viewonline'	=> (int) $view_online,
		);
		
		
		if ($this->data['session_id'])
		{
			$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_array) . "
				WHERE session_id = '" . $_CLASS['core_db']->sql_escape($this->data['session_id']) . "'";
			
			$_CLASS['core_db']->sql_query($sql);	
		}
		else
		{	
// maybe make a loop here incase, just incase the session_id already exsits
			$sql_array['session_id'] = (string) md5(unique_id());

			$_CLASS['core_db']->sql_query('INSERT INTO ' . SESSIONS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_array));
			
			$this->new_session = true;
		}
		
		$this->data = array_merge($this->data, $sql_array);
		unset($sql_array);
		
		if ($this->time > $config['session_last_gc'] + $config['session_gc'])
		{
			$this->gc($this->time);
		}

		if (!$this->is_bot)
		{
			if ($session_data['auto_log'] && $this->is_user)
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
	function destroy($session_id = false, $id = false,  $return = false)
	{
		global $_CLASS;

		if (!$session_id || !$id)
		{
			$session_id = $this->data['session_id'];
			$id = $this->data['user_id'];
			
			$this->set_cookie('data', '', $this->time - 31536000);
			$this->set_cookie('sid', '', $this->time - 31536000);
		}
		
		if ($this->data['session_time'])
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_lastvisit = ' . $this->data['session_time'] . '
				WHERE user_id = ' . $id;
			$_CLASS['core_db']->sql_query($sql);
		}
		
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
			WHERE session_id = '" . $_CLASS['core_db']->sql_escape($session_id) . "'
				AND session_user_id = " . $id;
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
	
	function auth($data)
	{
		global $config, $site_file_root;

		if (file_exists($site_file_root.'includes/auth/auth_' . $config['auth_method'] . '.php'))
		{
			include_once($site_file_root.'includes/auth/auth_' . $config['auth_method'] . '.php');

			$method = 'auth_' . $config['auth_method'];
			
			if (function_exists($method))
			{
				return $method($data);
			}
		}

		trigger_error('Authentication method not found', E_USER_ERROR);
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
		$this->new_data = true;
	}
	
	function set_data($name, $value, $force_save = false)
	{
		if (!empty($this->data['session_data'][$name]) && ($this->data['session_data'][$name] == $value))
		{
			return;
		}
		
		$this->data['session_data'][$name] = $value;
			
		$this->new_data = true;
		
		if ($force_save)
		{
			$this->save();
		}
	}
	
	function save()
	{
		global $_CLASS;

		if (!$this->new_data && !$this->session_save)
		{
			return;
		}

		$sql_array = array(
			'session_time'			=> (int) $this->time,
			'session_page'			=> (string) $this->page,
			'session_url'			=> (string) $this->url,
			'session_data'			=> (string) serialize($this->data['session_data']),
		);

		$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_array) . "
				WHERE session_id = '" . $_CLASS['core_db']->sql_escape($this->data['session_id']) . "'";
		
		$_CLASS['core_db']->sql_query($sql);

		$this->new_data = $this->session_save = false;
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