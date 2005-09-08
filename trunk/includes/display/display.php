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

class core_display
{
	var $header = array('js'=>'', 'regular'=>'', 'meta'=>'', 'body'=>'');
	var $displayed = array('header'=> false, 'footer'=> false);
	var $message = '';

	var $theme = false;
	var $homepage = false;
	var $modules = array();

	var $copyright = 'Powered by <a href="http://www.viperal.com">CMS alpha-dev</a> (c) 2004 - 2005 Ryan Marshall ( Viperal )';

	/*
		Handles sorting and auth'ing of modules
	*/
	function add_module($module, $homepage = true)
	{
		global $_CLASS, $site_file_root;

		if (!$module || !file_exists($site_file_root.'modules/'.$module['module_name'].'/index.php'))
		{
			return '404:_PAGE_NOT_FOUND';
		}

		if ($module['module_status'] != STATUS_ACTIVE)
		{
			if (!$_CLASS['core_auth']->admin_auth('modules'))
			{
				return '_MODULE_NOT_ACTIVE';
			}

			$_CLASS['core_display']->message = '<b>Module '.$module['module_name'].' Isn\'t Active</b><br />';
		}

		//authization check here
		$module['module_auth'] = ($module['module_auth']) ? unserialize($module['module_auth']) : '';

		if (($module['module_auth'] && !$_CLASS['core_auth']->auth($module['module_auth'])) && !$_CLASS['core_auth']->admin_power('modules'))
		{
			return '_MODULE_NOT_AUTH';
		}

		//first module control the sides.
		if (!empty($this->modules))
		{
			$module['module_sides'] = $this->modules[0]['module_sides'];
		}

		$this->modules[] = $module;

		return true;
	}

	/*
		Returns parsed module data
	*/
	function get_module()
	{
		if (isset($this->modules))
		{
			// this also unsets $this->modules
			return array_shift($this->modules);
		}

		return false;
	}

	/*
		Recommended Site headers.
		Changes here is not recommended unless you know what your doing
	*/
	function headers()
	{
		global $_CLASS;

		header('Content-Type: text/html; charset=UTF-8');
		header('Content-language: ' . $_CLASS['core_user']->lang['LANG']);

		header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
		header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
		header('Expires: 0');
		header('Pragma: no-cache');
	}

	/*
		Handles displaying of the Basic top section of site ( Top messages and blocks ).
		Also calls themes header $this->headers(); with should handle side blocks extra.
	*/
	function display($title, $template = false)
	{
		global $_CLASS, $_CORE_MODULE;

		if ($title)
		{
			$_CORE_MODULE['module_title'] = $title;
		}

		if ($template)
		{
			$_CLASS['core_template']->display($template);
		}

		script_close();
	}

	function display_header($title = false)
	{
		global $_CLASS, $_CORE_CONFIG, $_CORE_MODULE;

		if ($this->displayed['header'])
		{
			return;
		}

		$this->displayed['header'] = true;

		if (extension_loaded('zlib') && !ob_get_length())
		{
			ob_start('ob_gzhandler');
		}

		if ($title)
		{
			$_CORE_MODULE['module_title'] = $title;
		}
		elseif (!$_CORE_MODULE['module_title'])
		{
// should move this somewhere else
			$_CORE_MODULE['module_title'] = $_CORE_MODULE['module_name'];
		}

		$this->headers();

		if ($_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_new_privmsg'] && $_CLASS['core_user']->user_data_get('popuppm'))
		{
			$this->header['js'] .= '<script type="text/javascript">window.open(\''. preg_replace('/&amp;/', '&', generate_link('Control_Panel&i=pm&mode=popup', array('full' => true)))."', '_phpbbprivmsg','height=135,resizable=yes,status=no,width=400');</script>";
			$_CLASS['core_db']->sql_query('UPDATE ' . USERS_TABLE . ' SET user_new_privmsg = 0 WHERE user_id = ' . $_CLASS['core_user']->data['user_id']);
		}

		$this->header['regular'] .= '<meta name="generator" content="Viperal CMS ( www.viperal.com ) Copyright(c) '.date('Y').'" />';

		if (file_exists('favicon.ico'))
		{
			$this->header['regular'] .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';
		}

		$this->header['regular'] .= '<link rel="alternate" type="application/xml" title="RSS" href="'.generate_base_url().'backend.php?feed=rdf" />';

		if ($_CORE_CONFIG['maintenance']['active'] && $_CORE_CONFIG['maintenance']['start'] < time())
		{
			$this->message = '<b>System is in maintenance mode</b><br />';
		}


		$_CLASS['core_template']->assign_array(array(
			'SITE_LANG'			=>	$_CLASS['core_user']->lang['LANG'],
			'SITE_TITLE'		=>	$_CORE_CONFIG['global']['site_name'].': '.(is_array($_CORE_MODULE['module_title']) ? implode(' &gt; ', $_CORE_MODULE['module_title']) : $_CORE_MODULE['module_title']),
			'SITE_BASE'			=>	generate_base_url(),
			'SITE_CHARSET'		=>	'UTF-8',
			'SITE_NAME'			=>	$_CORE_CONFIG['global']['site_name'],
			'SITE_URL'			=>	$_CORE_CONFIG['global']['site_url'],
			'HEADER_MESSAGE'	=>	$this->message,
			'HEADER_REGULAR'	=>	$this->header['meta'].$this->header['regular'],
			'HEADER_JS' 		=>	$this->header['js'],
			'HEADER_BODY' 		=>	$this->header['body']				
		));

		$_CLASS['core_blocks']->display(BLOCK_MESSAGE_TOP);

		if ($this->homepage)
		{  
			$_CLASS['core_blocks']->display(BLOCK_TOP);
		}

		$this->theme->theme_header();
	}

	/*
		Handles displaying of the Basic lower section of site ( bottom messages and blocks blocks ).
	*/
	function display_footer($save = true)
	{
		global $_CLASS, $_CORE_MODULE, $_CORE_CONFIG;

		if ($this->displayed['footer'])
		{
			return;
		}

		if (!$this->displayed['header'])
		{
			script_close($save);
		}

		if ($_CORE_MODULE = $this->get_module())
		{
			global $site_file_root;

			require($site_file_root.'modules/'.$_CORE_MODULE['module_name'].'/index.php');
		}

		$this->displayed['footer'] = true;

		if ($this->homepage)
		{
			$_CLASS['core_blocks']->display(BLOCK_BOTTOM);
		}

		$_CLASS['core_blocks']->display(BLOCK_MESSAGE_BOTTOM);

		if ($this->displayed['header'])
		{
			$this->theme->theme_footer();
		}
		
		script_close($save);
	}
	
	/*
		General display of basic debug info
	*/
	function footer_debug()
	{
		global $_CORE_CONFIG, $_CLASS, $starttime;

		$mtime = explode(' ', microtime());
		$totaltime = ($mtime[0] + $mtime[1] - $starttime) - $_CLASS['core_db']->queries_time;

		$debug_output = 'Code Time : '.round($totaltime, 4).'s | Queries Time '.round($_CLASS['core_db']->queries_time, 4).'s | ' . $_CLASS['core_db']->num_queries . ' Queries  ] <br /> [ GZIP : ' .  ((in_array('ob_gzhandler' , ob_list_handlers())) ? 'On' : 'Off' ) . ' | Load : '  . (($_CLASS['core_user']->load) ? $_CLASS['core_user']->load : 'N/A');

		if ($memory_usage = get_memory_usage())
		{
			global $base_memory_usage;
			
			$memory_usage -= $base_memory_usage;
			$memory_usage = ($memory_usage >= 1048576) ? round((round($memory_usage / 1048576 * 100) / 100), 2) . ' ' . $_CLASS['core_user']->lang['MB'] : (($memory_usage >= 1024) ? round((round($memory_usage / 1024 * 100) / 100), 2) . ' ' . $_CLASS['core_user']->lang['KB'] : $memory_usage . ' ' . $_CLASS['core_user']->lang['BYTES']);

			$debug_output .= ' | Memory Usage: ' . $memory_usage;	
		}

		return $debug_output;
	}

	function footmsg()
	{
		global $_CORE_CONFIG;

		$footer = $this->copyright.'<br />';

		if ($_CORE_CONFIG['global']['foot1'])
		{
			$footer .= $_CORE_CONFIG['global']['foot1'] . '<br />';
		}

		if ($_CORE_CONFIG['global']['foot2'])
		{
			$footer .= $_CORE_CONFIG['global']['foot2']. '<br />';
		}

		return $footer.'[ '.$this->footer_debug(). ']<br />';
	}
	
	function load_theme($theme, $path)
	{
		$this->theme_name = $theme;
		$this->theme_path = $path;
		
		$theme .= '_theme';
	
		require_once($path.'/index.php');

		$this->theme = new $theme();
	}

	function meta_refresh($time, $url = false)
	{
		global $_CLASS;

		$this->header['meta'] .= '<meta http-equiv="refresh" content="' . $time . ';url=' . (($url) ? $url : generate_link(false, array('full' => true))) . '">';
	}
}

?>