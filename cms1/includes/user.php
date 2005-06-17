<?php

class core_user extends session
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