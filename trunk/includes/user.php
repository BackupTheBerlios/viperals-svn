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

// sessions should extend this
class core_user extends sessions
{
	var $browser;
	var $ip;
	var $url;
	var $page;

	var $data = array();
	var $lang = array();
	var $img = array();

	var $time_format;
	var $timezone;
	var $dst;

	var $is_admin;
	var $is_bot;
	var $is_user;

	var $user_setup = false;

	var $lang_name;
	var $lang_path;

	function core_user()
	{
		$this->browser = mb_substr((empty($_SERVER['HTTP_USER_AGENT']) ? getenv('HTTP_USER_AGENT') : $_SERVER['HTTP_USER_AGENT']), 0, 255);
		$this->url	= empty($_SERVER['REQUEST_URI']) ? getenv('REQUEST_URI') : $_SERVER['REQUEST_URI'];
		$this->ip	= empty($_SERVER['REMOTE_ADDR']) ? getenv('REMOTE_ADDR') : $_SERVER['REMOTE_ADDR'];
		$this->time	= (int) gmtime();

		if (($pos = mb_strpos($this->url, INDEX_PAGE.'?mod=')) !== false)
		{
			$pos = $pos + mb_strlen(INDEX_PAGE.'?mod=');
			$this->url = mb_substr($this->url, $pos);
			
			if (($pos = mb_strpos($this->url, 'sid')) !== false)
			{
				$this->url = mb_substr($this->url, 0, $pos - 1);
			}

			$this->url = htmlspecialchars(html_entity_decode($this->url, ENT_QUOTES));
			$this->url = mb_substr($this->url, 0, 255);
		}
		else
		{
			$this->url = '';
		}
	}

	function format_date($gmtime, $format = false)
	{
		return $this->format_time($gmtime, $format);
	}

	function format_time($gmtime, $format = false)
	{
		settype($gmtime, 'int');

		if (!$gmtime)
		{
			return false;;
		}

		$format = (!$format) ? $this->time_format : $format;

		return strtr(date($format, $this->time_convert($gmtime)), $this->lang['datetime']);
	}

	function time_convert($time, $conversion = 'user')
	{
		// user and gmt
		if ($conversion == 'gmt')
		{
			return ($time - $this->timezone - $this->dst);
		}

		return $time + $this->timezone + $this->dst;
	}

	function login($id = ANONYMOUS, $admin_login = false, $hidden = false, $auto_log = false)
	{
		global $_CLASS;

		settype($id, 'int');

		if ($bot = check_bot_status($this->browser, $this->ip))
		{
			$id = $bot;
		}

		if (!$this->can_create())
		{
			if (!$bot)
			{
				$this->user_setup();
				trigger_error('SITE_TEMP_UNAVAILABLE', E_USER_ERROR);
			}

			header('HTTP/1.0 503 Service Unavailable');
			script_close(false);
		}

		$result = $_CLASS['core_db']->query('SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = '. $id);
		$this->data = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$this->data)
		{
			die;
// Error here, however this happen
		}

		$this->is_user	= (!$bot && $this->data['user_type'] == USER_NORMAL);
		$this->is_bot 	= ($bot);

		if (isset($_CLASS['core_auth']))
		{
			unset($_CLASS['core_auth']);
		}

		load_class(false, 'core_auth', 'auth_db');
		
		$this->data['session_admin'] = ADMIN_NOT_ADMIN;

		if (!$this->is_bot && $_CLASS['core_auth']->admin_auth())
		{
			$this->data['session_admin'] = ($admin_login) ? ADMIN_IS_ADMIN : ADMIN_NOT_LOGGED;
		}

		$this->is_admin = ($this->data['session_admin'] === ADMIN_IS_ADMIN);
		$this->data['session_hidden'] = $hidden;

		$this->session_create($auto_log);
	}

	function logout()
	{
		global $_CLASS;

		if ($this->is_user)
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_last_visit = ' . (int) $this->data['session_time'] . '
				WHERE user_id = ' . (int) $this->data['user_id'];
			$_CLASS['core_db']->query($sql);
		}

		$this->session_destroy(false, true);

		$sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . ANONYMOUS;
		$result = $_CLASS['core_db']->query($sql);
	
		$this->data = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$this->data['session_id'] = '';
		$this->data['session_time'] = $this->data['session_admin'] = 0;

		$this->sid_link = $this->is_user = $this->is_bot = $this->is_admin = false;
	}

	function user_setup($theme = false)
	{
		global $_CLASS, $_CORE_CONFIG, $site_file_root;

		if ($this->user_setup)
		{
			return;
		}

		$this->user_setup = true;
		$this->data['user_unread_privmsg'] = 0; // TEMP
		
		if (!is_null($theme))
		{
			// Do the theme
			$theme_prev = get_variable('theme_preview', 'REQUEST', false);
			$theme = ($theme) ? $theme : $_CLASS['core_user']->session_data_get('user_theme');
			
			if ($theme_prev && ($theme_prev != $theme) && check_theme($theme_prev))
			{
				$theme = $theme_prev;
	
				if (!get_variable('temp_preview', 'REQUEST', false))
				{
					$_CLASS['core_user']->session_data_set('user_theme', $theme);
				}
			}
			elseif (!$theme || !check_theme($theme))
			{
				$theme = ($_CLASS['core_user']->data['user_theme']) ? $_CLASS['core_user']->data['user_theme'] : $_CORE_CONFIG['global']['default_theme'];     
			
				if (!check_theme($theme))
				{
					if (check_theme($_CORE_CONFIG['global']['default_theme']))
					{
						$theme = $_CORE_CONFIG['global']['default_theme'];
					}
					else
					{
						// We need a theme, don't we ?
						$handle = opendir('themes');
						$theme = false;
						
						while ($file = readdir($handle))
						{
							if ($file{0} !== '.' && check_theme($file))
							{
								$theme = $file;
								break;
							}
						}
						closedir($handle);
						
						if (!$theme)
						{
							trigger_error('Something here');
						}
					}
				}
			}
	
			$path = $site_file_root.'themes/'.$theme;
			
			$_CLASS['core_display']->load_theme($theme, $path);
		}

		$this->lang_name = $_CORE_CONFIG['global']['default_lang'];
		$this->lang_path = $site_file_root.'language/' . $this->lang_name . '/';

		$this->time_format = ($this->data['user_time_format']) ? $this->data['user_time_format'] : $_CORE_CONFIG['global']['default_dateformat'];
		$this->timezone = ($this->data['user_timezone']) ? $this->data['user_timezone'] : $_CORE_CONFIG['global']['default_timezone'];

		require($this->lang_path . 'common.php');
	}

	function add_img($img_file = false, $module = false, $lang = false)
	{
		global $site_file_root;

		$img_file = ($img_file) ? "$img_file.php" : 'index.php';

		if (!$img_file || !ereg('/', $img_file))
		{
			global $_CORE_MODULE, $_CLASS;
			
			$module = ($module) ? $module : $_CORE_MODULE['module_name'];
			$lang = ($lang) ? $lang : $this->lang_name;

			if (file_exists($_CLASS['core_display']->theme_path."/images/modules/$module/$img_file"))
			{
				include($_CLASS['core_display']->theme_path."/images/modules/$module/$img_file");
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

		//return ucfirst(mb_strtolower(preg_replace('/_/', ' ', $lang)));
		return mb_convert_case(preg_replace('/_/', ' ', $lang), MB_CASE_TITLE);
	}
	
	function get_img($img)
	{
		if (empty($this->img[$img]))
		{
			// this or false
			return $this->img[$img] = array('src' => false, 'width' => false, 'height' => false);
		}
		
		if (!is_array($this->img[$img]))
		{
			list($src, $height, $width) = explode('*', $this->img[$img]);
			$src = '"' . str_replace('{LANG}', $this->lang_name, $src) . '"'; // remove once everything is updated

			$this->img[$img] = array('src' => $src, 'width' => $width, 'height' => $height);
		}

		return $this->img[$img];
	}
		
	function add_lang($lang_file = false, $module = false)
	{
		global $site_file_root;
//Need a check for if the lang file exsists
	
		//print_r(debug_backtrace());
		if (is_array($lang_file))
		{
			foreach ($lang_file as $key => $lang)
			{
				$this->add_lang($lang, $module);
			}
			
			unset($lang);
			return;
		}
		
		if ($lang_file)
		{
			if (mb_strpos($lang_file, '/') !== false)
			{
				include($site_file_root."language/$this->lang_name/$lang_file");

				return;
			}

			$lang_file = $lang_file.'.php';
		}
		else
		{
			$lang_file = 'index.php';
		}

		if (!$module)
		{
			global $_CORE_MODULE;
			
			include($site_file_root.'modules/'.$_CORE_MODULE['module_name']."/language/$this->lang_name/$lang_file");

			return;
		}
		
		include($site_file_root."modules/$module/language/$this->lang_name/$lang_file");		
	}

	function user_data_get($name, $default = false)
	{
		return isset($this->data['user_data'][$name]) ? $this->data['user_data'][$name] : $default;
	}

	function user_data_kill($name)
	{
		if (!isset($this->data['user_data'][$name]))
		{
			return;
		}

		unset($this->data['user_data'][$name]);
		$this->save_session = true;
	}

	function user_data_set($name, $value, $save = false)
	{
		if (isset($this->data['user_data'][$name]) && ($this->data['user_data'][$name] == $value))
		{
			return;
		}

		$this->data['user_data'][$name] = $value;

		if ($save)
		{
		}
	}

	function set_cookie($name, $cookie_data, $cookie_time)
	{
		global $_CORE_CONFIG;

		if ($_CORE_CONFIG['server']['cookie_name'])
		{
			$name = $_CORE_CONFIG['server']['cookie_name'] . '_' . $name;
		}

		if ($this->server_local)
		{
			setcookie($name, $cookie_data, $cookie_time, $_CORE_CONFIG['server']['cookie_path']);
		}
		else
		{
			setcookie($name, $cookie_data, $cookie_time, $_CORE_CONFIG['server']['cookie_path'], $_CORE_CONFIG['server']['cookie_domain'], $_CORE_CONFIG['server']['cookie_secure']);
		}
	}
	
///////////////////
// TO BE REMOVED //
///////////////////
	function img($img, $alt = '', $width = false, $suffix = '')
	{
		$img = $this->get_img($img);

		$width = ($width || $img['width']) ? ' width="' . (($width) ? $width : $img['width']) . '"' : '';
		$height = ($img['height']) ? ' height="' . $img['height'] . '"' : '';

		return '<img src=' . $img['src'] .$width . $height .' alt="' . $alt . '" title="' . $alt . '" />';
	}

	function optionget($key, $data = false)
	{
		return $this->user_data_get($key);
	}

	function optionset($key, $value, $data = false)
	{
		return $this->user_data_set($key, $value);
	}
}

?>