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

// -------------------------------------------------------------
//
// COPYRIGHT : © 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
//
// -------------------------------------------------------------

class session
{
	var $data = array();
	var $session_id = '';
	var $browser = '';
	var $ip = '';
	var $url = '';
	var $page = '';
	var $load;
	var $new_data = false;
	var $new_session = false;
	var $session_save = false;


	// Called at each page start ... checks for, updates and/or creates a session
	function startup()
	{
		global $SID, $_CLASS, $MAIN_CFG, $name;
		
		$this->time = time();
		$this->server_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') ? true : false;
		$this->browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $_ENV['HTTP_USER_AGENT'];
		$this->url = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : $_ENV['REQUEST_URI'];

		//Don't want the SID in the in the URL all block or module will need to replace it with the current SID
		//Make this one replace on next look
		$this->url = eregi_replace('sid=[a-z0-9]+','', $this->url);
		$this->url = htmlentities(html_entity_decode(eregi_replace('sid=','', $this->url)));
		$this->page = ($name) ? htmlentities(html_entity_decode($name)) : '';

		if (isset($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_sid']) || isset($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_data']))
		{
			$sessiondata = (!empty($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_data'])) ? unserialize(stripslashes($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_data'])) : array();
			$this->session_id = (!empty($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_sid'])) ? trim_text($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_sid']) : false;
			$SID = (defined('NEED_SID')) ? '&amp;sid=' . $this->session_id : '&amp;sid=';
		}
		else
		{
			$sessiondata = array();
			$this->session_id = get_variable('sid', 'GET', false);
			$SID = '&amp;sid=' . $this->session_id;
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

		// Load limit check (if applicable)
		if (@file_exists('/proc/loadavg'))
		{
			if ($load = @file('/proc/loadavg'))
			{
				list($this->load) = explode(' ', $load[0]);

				if ($MAIN_CFG['server']['limit_load'] && $this->load > doubleval($MAIN_CFG['server']['limit_load']))
				{
					trigger_error('BOARD_UNAVAILABLE');
				}
			}
		}

		// session_id exists so go ahead and attempt to grab all data in preparation
		if ($this->session_id && (!defined('NEED_SID')))
		{
			$sql = 'SELECT u.*, s.*, g.*
				FROM ' . SESSIONS_TABLE . ' s, ' . USERS_TABLE . ' u, ' . GROUPS_TABLE . " g
				WHERE s.session_id = '" . $_CLASS['db']->sql_escape($this->session_id) . "'
					AND u.user_id = s.session_user_id
					AND g.group_id = u.group_id";
			$result = $_CLASS['db']->sql_query($sql);

			$this->data = $_CLASS['db']->sql_fetchrow($result);
			$_CLASS['db']->sql_freeresult($result);
	
			// Did the session exist in the DB?
			if (isset($this->data['user_id']))
			{
				// Validate IP length according to admin ... has no effect on IPv6
				$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, $MAIN_CFG['user']['ip_check']));
				$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, $MAIN_CFG['user']['ip_check']));

				$s_browser = ($MAIN_CFG['user']['browser_check']) ? $this->data['session_browser'] : '';
				$u_browser = ($MAIN_CFG['user']['browser_check']) ? $this->browser : '';

				if ($u_ip == $s_ip && $s_browser == $u_browser)
				{
					// Set session update a minute or so after last update or if page changes
					if (($this->time - $this->data['session_time']) > 60 || ($this->data['session_url'] != $this->url))
					{
						$this->session_save = true;
					}
					
					if ($this->data['session_data'])
					{
						@eval('$this->data[\'sessions\']='.$this->data['session_data'].';');
						unset($this->data['session_data']);
					}

					return true;
				}
			}
		}

		// If we reach here then no (valid) session exists. So we'll create a new one,
		// using the cookie user_id if available to pull basic user prefs.
		$autologin = (isset($sessiondata['autologinid'])) ? $sessiondata['autologinid'] : '';
		$user_id = (isset($sessiondata['userid'])) ? intval($sessiondata['userid']) : ANONYMOUS;

		return $this->create($user_id, $autologin);
	}

	// Create a new session
	function create(&$user_id, &$autologin, $set_autologin = false, $viewonline = 1, $admin = 0)
	{
		global $SID, $_CLASS, $config;
		
		$sessiondata = array();
		$current_user = $user_id;
		$bot = false;
		
		// Pull bot information from DB and loop through it
		$sql = 'SELECT user_id, bot_agent, bot_ip
			FROM ' . BOTS_TABLE . '
			WHERE bot_active = 1';
		$result = $_CLASS['db']->sql_query($sql);

		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			if ($row['bot_agent'] && preg_match('#' . preg_quote($row['bot_agent'], '#') . '#i', $this->browser))
			{
				$bot = $row['user_id'];
			}
			
			if ($row['bot_ip'] && (!$row['bot_agent'] || $bot))
			{
				foreach (explode(',', $row['bot_ip']) as $bot_ip)
				{
					if (($bot_ip == $this->ip) || (strpos($this->ip, $bot_ip) === 0))
					{
						$bot = $row['user_id'];
						break;
					}
				}
			}

			if ($bot)
			{
				$user_id = $bot;
				break;
			}
		}
		
		$_CLASS['db']->sql_freeresult($result);

		// Garbage collection ... remove old sessions updating user information
		// if necessary. It means (potentially) 11 queries but only infrequently
		if ($this->time > $config['session_last_gc'] + $config['session_gc'])
		{
			$this->gc($this->time);
		}

		// Grab user data ... join on session if it exists for session time
		$sql = 'SELECT u.*, s.session_time, s.session_id, s.session_admin, g.*
			FROM (' . USERS_TABLE . ' u, ' . GROUPS_TABLE . ' g
			LEFT JOIN ' . SESSIONS_TABLE . " s ON s.session_user_id = u.user_id)
			WHERE u.user_id = $user_id
				AND u.group_id = g.group_id
			ORDER BY s.session_time DESC";
		$result = $_CLASS['db']->sql_query_limit($sql, 1);

		$this->data = $_CLASS['db']->sql_fetchrow($result);
		$_CLASS['db']->sql_freeresult($result);

		// Check autologin request, is it valid?
		if (empty($this->data) || ($this->data['user_password'] != $autologin && !$set_autologin) || ($this->data['user_type'] == USER_INACTIVE && !$bot))
		{
			$autologin = '';
			$this->data['user_id'] = $user_id = ANONYMOUS;
		}

		// If we're a bot then we'll re-use an existing id if available
		if ($bot && $this->data['session_id'])
		{
			$this->session_id = $this->data['session_id'];
		}

		if (!$this->data['session_time'] && $config['active_sessions'])
		{
			// Limit sessions in 1 minute period
			$sql = 'SELECT COUNT(*) AS sessions
				FROM ' . SESSIONS_TABLE . '
				WHERE session_time >= ' . ($this->time - 60);
			$result = $_CLASS['db']->sql_query($sql);

			$row = $_CLASS['db']->sql_fetchrow($result);
			$_CLASS['db']->sql_freeresult($result);

			if (intval($row['sessions']) > intval($config['active_sessions']))
			{
				trigger_error('BOARD_UNAVAILABLE');
			}
		}

		// Is user banned? Are they excluded?
		if ($this->data['user_type'] != USER_FOUNDER && !$bot)
		{
			$banned = false;

			$sql = 'SELECT ban_ip, ban_userid, ban_email, ban_exclude, ban_give_reason, ban_end
				FROM ' . BANLIST_TABLE . '
				WHERE ban_end >= ' . time() . '
					OR ban_end = 0';
			$result = $_CLASS['db']->sql_query($sql);

			if ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				do
				{
					if ((!empty($row['ban_userid']) && intval($row['ban_userid']) == $this->data['user_id']) ||
						(!empty($row['ban_ip']) && preg_match('#^' . str_replace('*', '.*?', $row['ban_ip']) . '$#i', $this->ip)) ||
						(!empty($row['ban_email']) && preg_match('#^' . str_replace('*', '.*?', $row['ban_email']) . '$#i', $this->data['user_email'])))
					{
						if (!empty($row['ban_exclude']))
						{
							$banned = false;
							break;
						}
						else
						{
							$banned = true;
						}
					}
				}
				while ($row = $_CLASS['db']->sql_fetchrow($result));
			}
			$_CLASS['db']->sql_freeresult($result);

			if ($banned)
			{
				// Initiate environment ... since it won't be set at this stage
				$this->setup();

				// Determine which message to output
				$till_date = (!empty($row['ban_end'])) ? $this->format_date($row['ban_end']) : '';
				$message = (!empty($row['ban_end'])) ? 'BOARD_BAN_TIME' : 'BOARD_BAN_PERM';

				$message = sprintf($this->lang[$message], $till_date, '<a href="mailto:' . $MAIN_CFG['global']['admin_mail'] . '">', '</a>');
				// More internal HTML ... :D
				$message .= (!empty($row['ban_show_reason'])) ? '<br /><br />' . sprintf($this->lang['BOARD_BAN_REASON'], $row['ban_show_reason']) : '';
				trigger_error($message);
			}
		}

		// Is there an existing session? If so, grab last visit time from that
		$this->data['session_last_visit'] = ($this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : time());

		// Create or update the session
		$_CLASS['db']->sql_return_on_error(true);

		if ($this->session_id)
		{
			$sql_ary = array(
				'session_user_id'		=> (int) $user_id,
				'session_start'			=> (int) $this->time,
				'session_last_visit'	=> (int) $this->data['session_last_visit'],
				'session_time'			=> (int) $this->time,
				'session_browser'		=> (string) $this->browser,
				'session_page'			=> (string) $this->page,
				'session_url'			=> (string) $this->url,
				'session_ip'			=> (string) $this->ip,
				'session_admin'			=> (int) $this->data['session_admin'],
				'session_viewonline'	=> (int) $viewonline,
			);
		
		$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $_CLASS['db']->sql_build_array('UPDATE', $sql_ary) . "
			WHERE session_id = '" . $_CLASS['db']->sql_escape($this->session_id) . "'";
			
		}
		
		if (!$this->session_id || !$_CLASS['db']->sql_query($sql) || !$_CLASS['db']->sql_affectedrows())
		{
			$_CLASS['db']->sql_return_on_error(false);
			$this->session_id = md5(uniqid($this->ip));

			$sql_ary['session_id'] = (string) $this->session_id;

			$_CLASS['db']->sql_query('INSERT INTO ' . SESSIONS_TABLE . ' ' . $_CLASS['db']->sql_build_array('INSERT', $sql_ary));
			$this->new_session = true;
		}
		
		$_CLASS['db']->sql_return_on_error(false);

		if (!$bot)
		{
			$this->data['session_id'] = $this->session_id;

			// Don't set cookies if we're an admin re-authenticating
			if (!$admin || ($admin && $current_user == ANONYMOUS))
			{
				$sessiondata['userid'] = $user_id;
				$sessiondata['autologinid'] = ($autologin && $user_id != ANONYMOUS) ? $autologin : '';

				$this->set_cookie('data', serialize($sessiondata), $this->time + 31536000);
				$this->set_cookie('sid', $this->session_id, 0);
			}

			$SID = '&amp;sid=' . $this->session_id;

			if ($this->data['user_id'] != ANONYMOUS)
			{
				// Trigger EVT_NEW_SESSION
			}
		}
		else
		{
			$SID = '';
		}

		return true;
	}

	// Destroy a session
	function destroy()
	{
		global $SID, $_CLASS;

		$this->set_cookie('data', '', $this->time - 31536000);
		$this->set_cookie('sid', '', $this->time - 31536000);
		$SID = '&amp;sid=';

		// Delete existing session, update last visit info first!
		$sql = 'UPDATE ' . USERS_TABLE . '
			SET user_lastvisit = ' . $this->data['session_time'] . '
			WHERE user_id = ' . $this->data['user_id'];
		$_CLASS['db']->sql_query($sql);

		$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
			WHERE session_id = '" . $_CLASS['db']->sql_escape($this->session_id) . "'
				AND session_user_id = " . $this->data['user_id'];
		$_CLASS['db']->sql_query($sql);

		// Reset some basic data immediately
		$this->session_id = $this->data['username'] = '';
		$this->data['user_id'] = ANONYMOUS;
		$this->data['session_admin'] = 0;

		$this->new_session = true;
		// Trigger EVENT_END_SESSION

		return true;
	}

	// Garbage collection
	function gc(&$time)
	{
		global $_CLASS, $MAIN_CFG;

		switch (SQL_LAYER)
		{
			case 'mysql4':
				// Firstly, delete guest sessions
				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					WHERE session_user_id = ' . ANONYMOUS . '
						AND session_time < ' . ($time - $MAIN_CFG['user']['session_length']);
				$_CLASS['db']->sql_query($sql);

				// Keep only the most recent session for each user
				// Note: if the user is currently browsing the board, his
				// last_visit field won't be updated, which I believe should be
				// the normal behavior anyway
				$_CLASS['db']->sql_return_on_error(TRUE);

				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					USING ' . SESSIONS_TABLE . ' s1, ' . SESSIONS_TABLE . ' s2
					WHERE s1.session_user_id = s2.session_user_id
						AND s1.session_time < s2.session_time';
				$_CLASS['db']->sql_query($sql);

				$_CLASS['db']->sql_return_on_error(FALSE);

				// Update last visit time
				$sql = 'UPDATE ' . USERS_TABLE. ' u, ' . SESSIONS_TABLE . ' s
					SET u.user_lastvisit = s.session_time, u.user_lastpage = s.session_page
					WHERE s.session_time < ' . ($time - $MAIN_CFG['user']['session_length']) . '
						AND u.user_id = s.session_user_id';
				$_CLASS['db']->sql_query($sql);

				// Delete everything else now
				$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
					WHERE session_time < ' . ($time - $MAIN_CFG['user']['session_length']);
				$_CLASS['db']->sql_query($sql);

				set_config('session_last_gc', $time);
				break;

			default:

				// Get expired sessions, only most recent for each user
				$sql = 'SELECT session_user_id, session_page, MAX(session_time) AS recent_time
					FROM ' . SESSIONS_TABLE . '
					WHERE session_time < ' . ($time - $MAIN_CFG['user']['session_length']) . '
					GROUP BY session_user_id, session_page';
				$result = $_CLASS['db']->sql_query_limit($sql, 5);

				$del_user_id = '';
				$del_sessions = 0;
				if ($row = $_CLASS['db']->sql_fetchrow($result))
				{
					do
					{
						if ($row['session_user_id'] != ANONYMOUS)
						{
							$sql = 'UPDATE ' . USERS_TABLE . '
								SET user_lastvisit = ' . $row['recent_time'] . ", user_lastpage = '" . $_CLASS['db']->sql_escape($row['session_page']) . "'
								WHERE user_id = " . $row['session_user_id'];
							$_CLASS['db']->sql_query($sql);
						}

						$del_user_id .= (($del_user_id != '') ? ', ' : '') . $row['session_user_id'];
						$del_sessions++;
					}
					while ($row = $_CLASS['db']->sql_fetchrow($result));
				}

				if ($del_user_id)
				{
					// Delete expired sessions
					$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
						WHERE session_user_id IN ($del_user_id)
							AND session_time < " . ($time - $MAIN_CFG['user']['session_length']);
					$_CLASS['db']->sql_query($sql);
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
		return (empty($this->data['sessions'][$name])) ? false : $this->data['sessions'][$name];
	}
	
	function kill_data($name)
	{
		if (empty($this->data['sessions'][$name]))
		{
			return;
		}
		
		unset($this->data['sessions'][$name]);
		$this->new_data = true;
	}
	
	function set_data($name, $value)
	{
		if (!empty($this->data['sessions'][$name]) && ($this->data['sessions'][$name] == $value))
		{
			return;
		}
		
		$this->data['sessions'][$name] = $value;
			
		$this->new_data = true;
	}
	
	function save_session()
	{
		global $_CLASS;
		
		if (!$this->new_data && !$this->session_save)
		{
			return;
		}
		
		if ($this->new_data && !empty($_CLASS['cache']))
		{
			$this->new_data = ", session_data = '".$_CLASS['db']->sql_escape($_CLASS['cache']->format_array($this->data['sessions']))."'";
		}
		
		$sql = 'UPDATE ' . SESSIONS_TABLE . '
			SET session_time = '.$this->time.", session_url = '" . $_CLASS['db']->sql_escape($this->url) . "', session_page = '" . $_CLASS['db']->sql_escape($this->page) . "'
			".$this->new_data." WHERE session_id = '" . $_CLASS['db']->sql_escape($this->session_id) . "'";
		$_CLASS['db']->sql_query($sql);

		$this->new_data = $this->session_save = false;			
	}
	
	// Set a cookie
	function set_cookie($name, $cookiedata, $cookietime)
	{
		global $MAIN_CFG;
		if ($this->server_local)
		{
			setcookie($MAIN_CFG['server']['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $MAIN_CFG['server']['cookie_path']);
		} else {
			setcookie($MAIN_CFG['server']['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $MAIN_CFG['server']['cookie_path'], $MAIN_CFG['server']['cookie_domain'], $MAIN_CFG['server']['cookie_secure']);
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

	var $keyoptions = array('viewimg' => 0, 'viewflash' => 1, 'viewsmilies' => 2, 'viewsigs' => 3, 'viewavatars' => 4, 'viewcensors' => 5, 'attachsig' => 6, 'html' => 7, 'bbcode' => 8, 'smile' => 9, 'popuppm' => 10, 'report_pm_notify' => 11);
	var $keyvalues = array();

	function start()
	{
		global $_CLASS, $MAIN_CFG, $phpEx, $site_file_root;

		if ($this->data['user_id'] != ANONYMOUS)
		{
			$this->lang_name = (file_exists($site_file_root.'language/' . $this->data['user_lang'] . "/common.$phpEx")) ? $this->data['user_lang'] : $MAIN_CFG['global']['default_lang'];
			$this->lang_path = $site_file_root.'language/' . $this->lang_name . '/';

			$this->date_format = $this->data['user_dateformat'];
			$this->timezone = $this->data['user_timezone'] * 3600;
			$this->dst = $this->data['user_dst'] * 3600;
		
			if (VIPERAL != 'Admin' && $MAIN_CFG['user']['chg_passforce'] && $this->data['user_passchg'] < time() - ($MAIN_CFG['user']['chg_passforce'] * 86400))
			{
				global $name;

				if ($name != 'Control_Panel')
				{
					url_redirect(getlink('Control_Panel&i=profile&mode=reg_details'));
				}
			}
		}
		else
		{
			$this->lang_name = $MAIN_CFG['global']['default_lang'];
			$this->lang_path = $site_file_root.'language/' . $this->lang_name . '/';
			$this->date_format = $MAIN_CFG['global']['default_dateformat'];
			$this->timezone = $MAIN_CFG['global']['default_timezone'] * 3600;
			$this->dst = $MAIN_CFG['global']['default_dst'] * 3600;

			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			{
				$accept_lang_ary = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach ($accept_lang_ary as $accept_lang)
				{
					// Set correct format ... guess full xx_YY form
					$accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
					if (file_exists('language/' . $accept_lang . "/common.$phpEx"))
					{
						$this->lang_name = $accept_lang;
						$this->lang_path = $site_file_root.'language/' . $accept_lang . '/';
						break;
					}
					else
					{
						// No match on xx_YY so try xx
						$accept_lang = substr($accept_lang, 0, 2);
						if (file_exists('language/' . $accept_lang . "/common.$phpEx"))
						{
							$this->lang_name = $accept_lang;
							$this->lang_path = $site_file_root.'language/' . $accept_lang . '/';
							break;
						}
					}
				}
			}
		}
		
		require($this->lang_path . "common.$phpEx");
	}

	function add_img($img_file = false, $module = false, $lang = false)
	{
		global $phpEx;

		$img_file = ($img_file) ? "$img_file.$phpEx" : 'index.'.$phpEx;

		if (!$img_file || !ereg('/', $img_file)) {
		
			global $Module, $_CLASS;
			
			$module = ($module) ? $module : $Module['name'];
			$lang = ($lang) ? $this->lang_name.'/' : '';
			
			if (file_exists('themes/'.$_CLASS['display']->theme.'/template/modules/'.$module."/images/$lang$img_file"))
			{
				include('themes/'.$_CLASS['display']->theme.'/template/modules/'.$module."/images/$lang$img_file");
			} else {
				include('modules/'.$module."/images/$lang.$img_file");
			}
			
		} else {
		
			include($img_file.$phpEx);
			
		}
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
		
	function add_lang($langfile = false, $module = false, $langfolder = false, $use_db = false)
	{
		global $phpEx, $site_file_root;

		if ($use_db)
		{
			// now what can we use this for. 
		}
		//print_r(debug_backtrace());
		if (is_array($langfile))
		{
			foreach ($langfile as $key => $lang_file)
			{
				//$key = (string) $key;
				$this->add_lang($lang_file);
			}
			
			unset($lang);
			return;
		}
		
		$langfile = ($langfile) ? "$langfile.$phpEx" : 'index.'.$phpEx;

		if ($langfolder)
		{
			include($this->lang_path.(($module) ? $module.'/'  : '')."$langfile");
			return;
		}
		
		if (!$module)
		{
			global $Module;
			
			include($site_file_root.'modules/'.$Module['name']."/language/$this->lang_name/$langfile");
			return;
		} 
		
		include($site_file_root."modules/$module/language/$this->lang_name/$langfile");		
	}

	function format_date($gmepoch, $format = false, $forcedate = false)
	{
		static $midnight;

		$format = (!$format) ? $this->date_format : $format;

		if (!$midnight)
		{
			list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $this->timezone + $this->dst));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $this->timezone - $this->dst;
		}
	
		if ($gmepoch > $midnight && !$forcedate)
		{
			return preg_replace('#\|.*?\|#', $this->lang['datetime']['TODAY'], strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $this->lang['datetime']));
		}
		else if ($gmepoch > $midnight - 86400 && !$forcedate)
		{
			return preg_replace('#\|.*?\|#', $this->lang['datetime']['YESTERDAY'], strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $this->lang['datetime']));
		}
		else
		{
			return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $this->timezone + $this->dst), $this->lang['datetime']);
		}
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
		$result = $_CLASS['db']->sql_query($sql);

		return (int) $_CLASS['db']->sql_fetchfield('lang_id', 0, $result);
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
		$result = $_CLASS['db']->sql_query_limit($sql, 1);

		$this->profile_fields = (!($row = $_CLASS['db']->sql_fetchrow($result))) ? array() : $row;
		$_CLASS['db']->sql_freeresult($result);
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