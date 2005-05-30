<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// COPYRIGHT : � 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
//
// -------------------------------------------------------------

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
	var $need_url_id = false;


	function startup()
	{
		global $_CLASS, $_CORE_CONFIG, $SID, $mod;
		
// make sure all $SID is removed first XSS problems
		$SID = false;
		
		$this->time = time();
		$this->server_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') ? true : false;
		$this->browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $_ENV['HTTP_USER_AGENT'];
		$this->url = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : $_ENV['REQUEST_URI'];
		$this->page = $mod;

		if (($pos = strpos($this->url, 'sid')) !== false)
		{
			$this->url = substr($this->url, 0, $pos-1);
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
			if (!$session_data['session_id'] || ($session_data['session_id'] && (trim($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_sid']) === $session_data['session_id'])))
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

		if (!empty($session_data['session_id']))
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
				$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, $_CORE_CONFIG['user']['ip_check']));
				$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, $_CORE_CONFIG['user']['ip_check']));

				$s_browser = ($_CORE_CONFIG['user']['browser_check']) ? $this->data['session_browser'] : '';
				$u_browser = ($_CORE_CONFIG['user']['browser_check']) ? $this->browser : '';

				if ($u_ip == $s_ip && $s_browser == $u_browser)
				{
					// Set session update a minute or so after last update or if page changes
					if (($this->time - $this->data['session_time']) > 60 || ($this->data['session_url'] != $this->url))
					{
						$this->session_save = true;
					}
					
					$this->data['session_data'] = unserialize($this->data['session_data']);
					
					$this->is_user = ($this->data['user_id'] != ANONYMOUS && ($this->data['user_type'] == USER_NORMAL || $this->data['user_type'] == USER_FOUNDER)) ? true : false;
					$this->is_bot 	= (!$this->is_user && $this->data['user_id'] != ANONYMOUS) ? true : false;
/// Amin logging should have 3 option "user is admin" / "USer is not admin" / "Never checked"
					$this->is_admin = ($this->data['session_admin']) ? true : false;

					check_maintance_status();
					
					if (check_load_status())
					{
						$this->load = check_load_status();
					}
					
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

		if (isset($session_data['login_code']) && isset($session_data['user_id']))
		{
			return $this->create(array('user_id' => $session_data['user_id'], 'user_password' => $session_data['login_code'], 'auto_log' => true));
		}

		return $this->create();
	}

	// Create a new session
	function create($data = array())
	{
		global $_CLASS, $config;
		
		$session_data = array(
			'user_name'			=> false,
			'user_id'			=> false,
			'user_password'		=> false,
			'admin_login'		=> false,
			'auth_error_return'	=> false,
			'auto_log'			=> false,
		);
		
		$session_data = array_merge($session_data, array_filter($data));
		$session_data['user_id'] = (int) $session_data['user_id'];
		$session_data['is_bot'] = false;
		
		$bots_array = get_bots();

		foreach ($bots_array as $bot)
		{
			if ($bot['bot_agent'] && preg_match('#' . preg_quote($bot['bot_agent'], '#') . '#i', $this->browser))
			{
				$session_data['is_bot'] = true;
			}
			
			if ($bot['bot_ip'] && (!$bot['bot_agent'] || $session_data['is_bot']))
			{
				$session_data['is_bot'] = false;
				
				foreach (explode(',', $bot['bot_ip']) as $bot_ip)
				{
					if (($bot_ip == $this->ip) || (strpos($this->ip, $bot_ip) === 0))
					{
						$session_data['is_bot'] = true;
					}
				}
			}
			
			if ($session_data['is_bot'])
			{
				$session_data['user_id'] = $bot['user_id'];
				break;
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
		}

		if ($auth === true)
		{
			if ($session_data['user_id']) 
			{
				$where_sql =  'u.user_id = '.$session_data['user_id'];
			} else {
				$where_sql =  "u.username = '".$_CLASS['core_db']->sql_escape($session_data['user_name'])."'";
			}
			
			$sql = 'SELECT u.*, s.*
				FROM (' . USERS_TABLE . ' u
					LEFT JOIN ' . SESSIONS_TABLE . " s ON s.session_user_id = u.user_id)
					WHERE $where_sql
				ORDER BY s.session_time DESC";
			$result = $_CLASS['core_db']->sql_query_limit($sql, 1);
	
			$this->data = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
			
			/*
			If you want users to log in more than once, need to check brower && ip
			Use regular for bots. May only be usefull for developers.
			
			if (!$session_data['is_bot'])
			{
				if ($session_data['user_id']) 
				{
					$where_sql =  'user_id = '.$session_data['user_id'];
				} else {
					$where_sql =  "username = '".$_CLASS['core_db']->sql_escape($session_data['user_name'])."'";
				}
			
				$sql = 'SELECT * FROM '. USERS_TABLE . '
						WHERE '.$where_sql;
						
				$result = $_CLASS['core_db']->sql_query($sql);
				$this->data = $_CLASS['core_db']->sql_fetchrow($result);
				$_CLASS['core_db']->sql_freeresult($result);
				
				// Look at database.sessions indexs
				$sql = 'SELECT * FROM ' . SESSIONS_TABLE . "
						WHERE session_ip = '".$_CLASS['core_db']->sql_escape($this->ip)."'
							 AND session_browser = '".$_CLASS['core_db']->sql_escape($this->browser)."'";
						//ORDER BY session_time DESC";
	
				$result = $_CLASS['core_db']->sql_query_limit($sql, 1);
				
				if ($sessions_dump = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$this->data += $sessions_dump;
				}
				else
				{
					$this->data['session_time'] = $this->data['session_id'] = 0;
				}
				
				$_CLASS['core_db']->sql_freeresult($result);
				unset($sessions_dump);
			{
			else 
			{
				regular fetch here
			}
			*/
		} 
		
		if (($auth !== true || !$this->data['session_time']) && $config['active_sessions'])
		{
			// Limit sessions in 1 minute period
			$sql = 'SELECT COUNT(*) AS sessions
				FROM ' . SESSIONS_TABLE . '
				WHERE session_time >= ' . ($this->time - 60);
			$result = $_CLASS['core_db']->sql_query($sql);

			$row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if (intval($row['sessions']) > intval($config['active_sessions']))
			{
				trigger_error('BOARD_UNAVAILABLE');
			}
		}
		
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

		}
		
		$this->is_user = (!$session_data['is_bot'] && ($auth === true)) ? true : false;
/// Amin logging should have 3 option "user is admin" / "USer is not admin" / "Never checked"
		$this->is_admin = ($this->is_user && $session_data['admin_login']) ? true : false;
		$this->is_bot = ($session_data['is_bot']) ? true : false;
		
		$this->data['session_last_visit'] = ($this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : time());
	
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
			'session_admin'			=> (int) $this->is_admin,
			'session_viewonline'	=> (int) true,	// ADD
		);
		
		
		if ($this->data['session_id'])
		{
// if this is a user should we update the user:last_visit time considering it's like a new session
			$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_array) . "
				WHERE session_id = '" . $_CLASS['core_db']->sql_escape($this->data['session_id']) . "'";
			
			$_CLASS['core_db']->sql_query($sql);
			
		} else {	
	
// maybe make a loop here incase, just incase the session_id already exsits
			$sql_array['session_id'] = (string) md5(unique_id());

			$_CLASS['core_db']->sql_query('INSERT INTO ' . SESSIONS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_array));
			
			$this->new_session = true;
		}
		
// need to make sure that false values are also stored
		$this->data = array_merge($this->data, $sql_array);
		unset($sql_array);
		
		if ($this->time > $config['session_last_gc'] + $config['session_gc'])
		{
			$this->gc($this->time);
		}

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

		$this->need_url_id = true;
		$this->data['sessions'] = array();
	
		return true;
	}

	// Destroy a session
	function destroy()
	{
		global $_CLASS;

		$this->set_cookie('data', '', $this->time - 31536000);
		$this->set_cookie('sid', '', $this->time - 31536000);

		// Delete existing session, update last visit info first!
		$sql = 'UPDATE ' . USERS_TABLE . '
			SET user_lastvisit = ' . $this->data['session_time'] . '
			WHERE user_id = ' . $this->data['user_id'];
		$_CLASS['core_db']->sql_query($sql);

		$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
			WHERE session_id = '" . $_CLASS['core_db']->sql_escape($this->data['session_id']) . "'
				AND session_user_id = " . $this->data['user_id'];
		$_CLASS['core_db']->sql_query($sql);

		// Reset some basic data immediately
		$this->data['user_id'] = ANONYMOUS;

		$sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . ANONYMOUS;
		$result = $_CLASS['core_db']->sql_query($sql);
	
		$this->data = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);

		$this->data['session_id'] = '';
		$this->need_url_id = $this->data['session_time'] = $this->data['session_admin'] = 0;
		// Trigger EVENT_END_SESSION

		return true;
	}
	
	function auth($data)
	{
		global $config, $site_file_root;

		$method = trim($config['auth_method']);

		if (file_exists($site_file_root.'includes/auth/auth_' . $method . '.php'))
		{
			include_once($site_file_root.'includes/auth/auth_' . $method . '.php');

			$method = 'login_' . $method;
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

		switch (SQL_LAYER)
		{
			case 'mysql4':
			case 'mysqli':
			
				// Firstly, delete guest sessions
				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					WHERE session_user_id = ' . ANONYMOUS . '
						AND session_time < ' . ($time - $_CORE_CONFIG['user']['session_length']);
				$_CLASS['core_db']->sql_query($sql);

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
					WHERE s.session_time < ' . ($time - $_CORE_CONFIG['user']['session_length']) . '
						AND u.user_id = s.session_user_id';
				$_CLASS['core_db']->sql_query($sql);

				// Delete everything else now
				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					WHERE session_time < ' . ($time - $_CORE_CONFIG['user']['session_length']);
				$_CLASS['core_db']->sql_query($sql);

				set_config('session_last_gc', $time);
				break;

			default:

				// Get expired sessions, only most recent for each user
				$sql = 'SELECT session_user_id, session_page, MAX(session_time) AS recent_time
					FROM ' . SESSIONS_TABLE . '
					WHERE session_time < ' . ($time - $_CORE_CONFIG['user']['session_length']) . '
					GROUP BY session_user_id, session_page';
				$result = $_CLASS['core_db']->sql_query_limit($sql, 5);

				$del_user_id = '';
				$del_sessions = 0;
				if ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					do
					{
						if ($row['session_user_id'] != ANONYMOUS)
						{
							$sql = 'UPDATE ' . USERS_TABLE . '
								SET user_lastvisit = ' . $row['recent_time'] . ", user_lastpage = '" . $_CLASS['core_db']->sql_escape($row['session_page']) . "'
								WHERE user_id = " . $row['session_user_id'];
							$_CLASS['core_db']->sql_query($sql);
						}

						$del_user_id .= (($del_user_id != '') ? ', ' : '') . $row['session_user_id'];
						$del_sessions++;
					}
					while ($row = $_CLASS['core_db']->sql_fetchrow($result));
				}

				if ($del_user_id)
				{
					// Delete expired sessions
					$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
						WHERE session_user_id IN ($del_user_id)
							AND session_time < " . ($time - $_CORE_CONFIG['user']['session_length']);
					$_CLASS['core_db']->sql_query($sql);
				}

				if ($del_sessions < 5)
				{
					// Less than 5 sessions, update gc timer ... else we want gc
					// called again to delete other sessions
					set_config('session_last_gc', $time);
				}
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
		} else {
			setcookie($_CORE_CONFIG['server']['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $_CORE_CONFIG['server']['cookie_path'], $_CORE_CONFIG['server']['cookie_domain'], $_CORE_CONFIG['server']['cookie_secure']);
		}
	}
}

// Contains (at present) basic user methods such as configuration
// creating date/time ... keep this?
class user extends session
{
	var $lang = array();
	var $img = array();
	var $date_format;
	var $timezone;
	var $dst;

	var $lang_name;
	var $lang_path;

	var $keyoptions = array('viewimg' => 0, 'viewflash' => 1, 'viewsmilies' => 2, 'viewsigs' => 3, 'viewavatars' => 4, 'viewcensors' => 5, 'attachsig' => 6, 'html' => 7, 'bbcode' => 8, 'smilies' => 9, 'popuppm' => 10, 'report_pm_notify' => 11);
	var $keyvalues = array();

	function start()
	{
		global $_CLASS, $_CORE_CONFIG, $site_file_root;

		if ($this->data['user_id'] != ANONYMOUS)
		{
			$this->lang_name = (file_exists($site_file_root.'language/' . $this->data['user_lang'] . '/common.php')) ? $this->data['user_lang'] : $_CORE_CONFIG['global']['default_lang'];
			$this->lang_path = $site_file_root.'language/' . $this->lang_name . '/';

			$this->date_format = $this->data['user_dateformat'];
			$this->timezone = $this->data['user_timezone'] * 3600;
			$this->dst = $this->data['user_dst'] * 3600;
		
			if (VIPERAL != 'Admin' && $_CORE_CONFIG['user']['chg_passforce'] && $this->data['user_passchg'] < time() - ($_CORE_CONFIG['user']['chg_passforce'] * 86400))
			{
				global $name;

				if ($name != 'Control_Panel')
				{
					url_redirect(generate_link('Control_Panel&i=profile&mode=reg_details'));
				}
			}
		}
		else
		{
			$this->lang_name = $_CORE_CONFIG['global']['default_lang'];
			$this->lang_path = $site_file_root.'language/' . $this->lang_name . '/';
			$this->date_format = $_CORE_CONFIG['global']['default_dateformat'];
			$this->timezone = $_CORE_CONFIG['global']['default_timezone'] * 3600;
			$this->dst = $_CORE_CONFIG['global']['default_dst'] * 3600;

			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			{
				$accept_lang_ary = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach ($accept_lang_ary as $accept_lang)
				{
					// Set correct format ... guess full xx_YY form
					$accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
					if (file_exists('language/' . $accept_lang . '/common.php'))
					{
						$this->lang_name = $_CORE_CONFIG['global']['default_lang'] = $accept_lang;
						$this->lang_path = $site_file_root.'language/' . $accept_lang . '/';
						break;
					}
					else
					{
						// No match on xx_YY so try xx
						$accept_lang = substr($accept_lang, 0, 2);
						if (file_exists('language/' . $accept_lang . '/common.php'))
						{
							$this->lang_name = $_CORE_CONFIG['global']['default_lang'] = $accept_lang;
							$this->lang_path = $site_file_root.'language/' . $accept_lang . '/';
							break;
						}
					}
				}
			}
		}
		
		require($this->lang_path . 'common.php');
	}

	function add_img($img_file = false, $module = false, $lang = false)
	{
		global $site_file_root;

		$img_file = ($img_file) ? "$img_file.php" : 'index.php';

		if (!$img_file || !ereg('/', $img_file)) {
		
			global $_CORE_MODULE, $_CLASS;
			
			$module = ($module) ? $module : $_CORE_MODULE['name'];
			$lang = ($lang) ? $this->lang_name.'/' : '';
			
			if (file_exists($site_file_root.'themes/'.$_CLASS['core_display']->theme.'/template/modules/'.$module."/images/$lang$img_file"))
			{
				include($site_file_root.'themes/'.$_CLASS['core_display']->theme.'/template/modules/'.$module."/images/$lang$img_file");
			} else {
				include($site_file_root.'modules/'.$module."/images/$lang.$img_file");
			}
			
		} else {
		
			include($img_file.'.php');
			
		}
	}
	
	function get_lang($lang)
	{
	
		if (isset($this->lang[$lang]))
		{
			return $this->lang[$lang];
		}
		
		return ucfirst(strtolower(preg_replace('/_/', ' ', $lang)));
	}
	
	function img($img, $alt = '', $width = false, $suffix = '')
	{
		static $imgs;
		
		if (empty($imgs[$img . $suffix]) || $width !== false)
		{
			if (!isset($this->img[$img]) || !$this->img[$img])
			{
				// Do not fill the image to let designers decide what to do if the image is empty
				$imgs[$img . $suffix] = '';
				return $imgs[$img . $suffix];
			}
			global $_CLASS;

			if ($width === false)
			{
				list($imgsrc, $height, $width) = explode('*', $this->img[$img]);
			}
			else
			{
				list($imgsrc, $height) = explode('*', $this->img[$img]);
			}

			if ($suffix !== '')
			{
				$imgsrc = str_replace('{SUFFIX}', $suffix, $imgsrc);
			}
			
			$imgsrc = '"' . str_replace('{LANG}', $this->lang_name, $imgsrc) . '"';
			$width = ($width) ? ' width="' . $width . '"' : '';
			$height = ($height) ? ' height="' . $height . '"' : '';
			
			$imgs[$img . $suffix] = $imgsrc . $width . $height;
			
		}
		
		$alt = (!empty($this->lang[$alt])) ? $this->lang[$alt] : $alt;
		return '<img src=' . $imgs[$img . $suffix] . ' alt="' . $alt . '" title="' . $alt . '" name="' . $img . '" />';
	}
		
	function add_lang($langfile = false, $module = false)
	{
		global $site_file_root;
//Need a check for if the lang file exsists
	
		//print_r(debug_backtrace());
		if (is_array($langfile))
		{
			foreach ($langfile as $key => $lang_file)
			{
				//$key = (string) $key;
				$this->add_lang($lang_file, $module);
			}
			
			unset($lang);
			return;
		}
		
		if (strpos($langfile, '/') !== false)
		{
			include($site_file_root."language/$this->lang_name/$langfile");
			return;
		}
		
		$langfile = ($langfile) ? $langfile.'.php' : 'index.php';
		
		if (!$module)
		{
			global $_CORE_MODULE;
			
			include($site_file_root.'modules/'.$_CORE_MODULE['name']."/language/$this->lang_name/$langfile");
			return;
		} 
		
		include($site_file_root."modules/$module/language/$this->lang_name/$langfile");		
	}

	function format_date($gmepoch, $format = false, $forcedate = false)
	{
		static $midnight;

		if (!$gmepoch)
		{
			return;
		}
		
		$format = (!$format) ? $this->date_format : $format;

		if (!$midnight)
		{
			list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $this->timezone + $this->dst));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $this->timezone - $this->dst;
		}
	
		if (strpos($format, '|') === false || (!($gmepoch > $midnight && !$forcedate) && !($gmepoch > $midnight - 86400 && !$forcedate)))
		{
			return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $this->timezone + $this->dst), $this->lang['datetime']);
		}
		
		if ($gmepoch > $midnight && !$forcedate)
		{
			$format = substr($format, 0, strpos($format, '|')) . '||' . substr(strrchr($format, '|'), 1);
			return str_replace('||', $this->lang['datetime']['TODAY'], strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $this->lang['datetime']));
		}
		else if ($gmepoch > $midnight - 86400 && !$forcedate)
		{
			$format = substr($format, 0, strpos($format, '|')) . '||' . substr(strrchr($format, '|'), 1);
			return str_replace('||', $this->lang['datetime']['YESTERDAY'], strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $this->lang['datetime']));
		}
	}
	
	// Get profile fields for user
	function get_profile_fields($user_id)
	{
		global $user, $_CLASS;

		if (isset($this->profile_fields))
		{
			return;
		}

		// TODO: think about adding this to the session code too?
		// Grabbing all user specific options (all without the need of special complicate adding to the sql query) might be useful...
		$sql = 'SELECT * FROM ' . PROFILE_DATA_TABLE . "
			WHERE user_id = $user_id";
		$result = $_CLASS['core_db']->sql_query_limit($sql, 1);

		$this->profile_fields = (!($row = $_CLASS['core_db']->sql_fetchrow($result))) ? array() : $row;
		$_CLASS['core_db']->sql_freeresult($result);
	}
	
	//remove this
	function get_iso_lang_id()
	{
		global $_CLASS;

		if (isset($this->lang_id))
		{
			return $this->lang_id;
		}

		if (!$this->lang_name)
		{
			$this->lang_name = $MAIN_CFG['global']['default_lang'];
		}

		$sql = 'SELECT lang_id
			FROM ' . LANG_TABLE . "
			WHERE lang_iso = '{$this->lang_name}'";
		$result = $_CLASS['core_db']->sql_query($sql);
		
		$lang_id = (int) $_CLASS['core_db']->sql_fetchfield('lang_id', 0, $result);
		$_CLASS['core_db']->sql_freeresult($result);
		
		return $lang_id;
	}
	
	// Start code for checking/setting option bit field for user table
	function optionget($key, $data = false)
	{
		if (!isset($this->keyvalues[$key]))
		{
			$var = ($data) ? $data : $this->data['user_options'];
			$this->keyvalues[$key] = ($var & 1 << $this->keyoptions[$key]) ? true : false;
		}
		return $this->keyvalues[$key];
	}

	function optionset($key, $value, $data = false)
	{
		$var = ($data) ? $data : $this->data['user_options'];

		if ($value && !($var & 1 << $this->keyoptions[$key]))
		{
			$var += 1 << $this->keyoptions[$key];
		}
		else if (!$value && ($var & 1 << $this->keyoptions[$key]))
		{
			$var -= 1 << $this->keyoptions[$key];
		}
		else
		{
			return ($data) ? $var : false;
		}

		if (!$data)
		{
			$this->data['user_options'] = $var;
			return true;
		}
		else
		{
			return $var;
		}
	}
}

?>