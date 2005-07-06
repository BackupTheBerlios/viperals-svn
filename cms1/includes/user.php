<?php

class core_user extends sessions
{
	var $browser;
	var $ip;
	var $url;
	var $page;

	var $data = array();
	var $lang = array();
	var $img = array();

	var $date_format;
	var $timezone;
	var $dst;

	var $lang_name;
	var $lang_path;

// remove
	var $keyoptions = array('viewimg' => 0, 'viewflash' => 1, 'viewsmilies' => 2, 'viewsigs' => 3, 'viewavatars' => 4, 'viewcensors' => 5, 'attachsig' => 6, 'html' => 7, 'bbcode' => 8, 'smilies' => 9, 'popuppm' => 10, 'report_pm_notify' => 11);
	var $keyvalues = array();

	function core_user()
	{
		$this->browser = substr((!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : getenv('HTTP_USER_AGENT'), 0, 100);
		$this->url	= (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
		$this->ip	= (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
		$this->time	= time();

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

		if ($pos = strpos($this->url, INDEX_PAGE.'?mod=') !== false)
		{
			$pos = $pos + strlen(INDEX_PAGE.'?mod=');
			$this->url = substr($this->url, $pos);
			
			if (($pos = strpos($this->url, 'sid')) !== false)
			{
				$this->url = substr($this->url, 0, $pos - 1);
			}

			$this->url = substr($this->url, 0, 100);
		}
		else
		{
			$this->url = '';
		}

		if (!isset($_COOKIE))
		{
			$_COOKIE = array();
		}
	}

	function login($id = ANONYMOUS, $admin_login = false, $view_online = true)
	{
		global $_CLASS;

		if (isset($this->data['session_id']) && $this->data['session_id'])
		{
			$this->session_destroy(false, true);
		}

		if ($bot = check_bot_status($this->browser, $this->ip))
		{
			$id = $bot;
		}

		if (!$this->can_create())
		{
			if (!$bot)
			{
				$this->user_setup();
				trigger_error('SITE_UNAVAILABLE', E_USER_ERROR);
			}
	
			header("HTTP/1.0 503 Service Unavailable");
			script_close(false);
			die;
		}

		$result = $_CLASS['core_db']->sql_query('SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = '.$id);
		$this->data = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
		if (!$this->data)
		{
			die;
// Error here
		}

		if ($bot)
		{
			$this->is_user = false;
			$this->is_bot = true;

			$this->data['session_admin'] = ADMIN_NOT_ADMIN;
		}
		else
		{
			$this->is_user = ($id == ANONYMOUS) ? false : true;
			$this->is_bot = false;
	
			$auth = new core_auth();
			$auth->get_data($id, $_CLASS['core_user']->data['group_id']);

			if ($auth->admin_auth())
			{
				$this->data['session_admin'] = ($admin_login) ? ADMIN_IS_ADMIN : ADMIN_NOT_LOGGED;
			}
			else
			{
				$this->data['session_admin'] = ADMIN_NOT_ADMIN;
			}
			
			unset($auth);
		}
			
		$this->is_admin = ($this->data['session_admin'] == ADMIN_IS_ADMIN) ? true : false;
		$this->data['session_viewonline'] = $view_online;

		/*if (!$this->is_user && $_CORE_CONFIG['global']['only_registered'])
		{
			$this->need_url_id = false;
			login_box(array('full_screen'	=> true));
		}*/

		$this->session_create();
	}
	
	function logout()
	{
		$this->session_destroy();
		// forgot what else I needed to do here :-|
	}

	function user_setup()
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

		if (!$img_file || !ereg('/', $img_file))
		{
			global $_CORE_MODULE, $_CLASS;
			
			$module = ($module) ? $module : $_CORE_MODULE['name'];
			$lang = ($lang) ? $lang : $this->lang_name;

			if (file_exists($site_file_root.'themes/'.$_CLASS['core_display']->theme."/images/modules/$module/$img_file"))
			{
				include($site_file_root.'themes/'.$_CLASS['core_display']->theme."/images/modules/$module/$img_file");
			}
			else
			{
				include($site_file_root.'modules/'.$module."/images/$img_file");
			}
		}
		else
		{
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
		return '<img src=' . $imgs[$img . $suffix] . ' alt="' . $alt . '" title="' . $alt . '" />';
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

		settype($gmepoch, 'integer');

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
// Replace
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