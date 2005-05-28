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
 
class core_display
{

	var $header = array('js'=>'', 'regular'=>'', 'meta'=>'', 'body'=>'');
	var $displayed = array('header'=> false, 'footer'=> false);
	var $message = '';
	var $theme = false;
	var $homepage = false;
	var $_CORE_MODULEs = array();
	
	function core_display()
	{
		global $_CLASS, $site_file_root, $_CORE_CONFIG, $SID;
		
		//make sure to add a test for https, bla bla bla
		$this->siteurl = 'http://'.getenv('HTTP_HOST').$_CORE_CONFIG['server']['path'];
		$this->copyright = 'Powered by <a href="http://www.viperal.com">Viperal CMS Pre-Beta</a> (c) 2004 Viperal';
		
		$this->themeprev = get_variable('prevtheme', 'REQUEST', false);
		$this->theme = $_CLASS['core_user']->get_data('theme');
		
		if ($this->themeprev && ($this->themeprev != $this->theme) && $this->check_theme($this->themeprev))
		{
			$this->theme = $this->themeprev;
			define('THEMEPLATE', $this->temp);
			
			if (!get_variable('prevtemp', 'REQUEST', false))
			{
				$_CLASS['core_user']->set_data('theme', $this->theme);
			}
		}
		elseif ($this->theme && $this->check_theme($this->theme))
		{
			define('THEMEPLATE', $this->temp);
		}
		else
		{
           	$this->theme = ($_CLASS['core_user']->data['theme']) ? $_CLASS['core_user']->data['theme'] : $_CORE_CONFIG['global']['default_theme'];     
	
			if ($this->check_theme($this->theme))
			{
				define('THEMEPLATE', $this->temp);
				
			} elseif ($this->check_theme($_CORE_CONFIG['global']['default_theme'])) {
				
				$this->theme = $_CORE_CONFIG['global']['default_theme'];
				define('THEMEPLATE', $this->temp);
				
			} else {
			
				// We need a theme ..
				$handle = opendir('themes');
				
				while ($list = readdir($handle))
				{
					if (!ereg('[.]', $list) && $this->check_theme($list))
					{
						$this->theme = $list;
						define('THEMEPLATE', $this->temp);
						break;
					}
				}
				
				closedir($handle);
			}
		}
    	
		if (THEMEPLATE)
		{
			require($site_file_root.'themes/'.$this->theme.'/index.php');
		} else {
			require($site_file_root.'themes/'.$this->theme.'/theme.php');
		}
	}
	
	// maybe make this a function.
	function check_theme($theme)
	{
		global $site_file_root;
		
		if (is_dir($site_file_root.'themes/'.$theme))
		{
			
			if (file_exists($site_file_root.'themes/'.$theme.'/index.php'))
			{
				$this->temp = true;
				return '1';
			}
			elseif (file_exists($site_file_root.'themes/'.$theme.'/theme.php'))
			{
				$this->temp = false;
				return '2';
			}
		}
		return false;
	}
	
	function add_module($module)
	{
		global $_CLASS;
		
		//authization check here
		//if (!$_CLASS['core_user']->admin_auth('modules') && !$_CLASS['core_user']->auth($module['auth']))
		//{
		//	return;
		//}
		
		//first module control the sides.
		if (!empty($this->modules))
		{
			$module['sides'] = $this->modules[0]['sides'];
		}
		
		$this->modules[] = $module;
	}
	
	function get_module()
	{
		if (isset($this->modules))
		{
			// this also unsets $this->modules
			return array_shift($this->modules);
		}
		return false;
	}
	
	function display_head($title = false)
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
			$_CORE_MODULE['title'] = $title;
		}
		
		header('Content-Type: text/html; charset='.$_CLASS['core_user']->lang['ENCODING']);
		header('Content-language: ' . $_CLASS['core_user']->lang['LANG']);
		
		header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . " GMT" );
		header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		

		if (is_user() && $_CLASS['core_user']->data['user_new_privmsg'] && $_CLASS['core_user']->optionget('popuppm'))
		{
			if (!$_CLASS['core_user']->data['user_last_privmsg'] || ($_CLASS['core_user']->data['user_last_privmsg'] > $_CLASS['core_user']->data['session_time']))
			{
				$this->header['js'] .= '<script type="text/javascript">window.open(\''. preg_replace('/&amp;/', '&', generate_link('Control_Panel&i=pm&mode=popup', array('full' => true)))."', '_phpbbprivmsg','height=135,resizable=yes,status=no,width=400');</script>";

				if (!$_CLASS['core_user']->data['user_last_privmsg'] || $_CLASS['core_user']->data['user_last_privmsg'] > $_CLASS['core_user']->data['session_last_visit'])
				{
// Maybe just make just user_new_privmsg or set time to 0 
					$sql = 'UPDATE ' . USERS_TABLE . ' SET user_last_privmsg = ' . $_CLASS['core_user']->data['session_time'] . ' WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
					$_CLASS['core_db']->sql_query($sql);
				}
			}
		}

		$this->header['regular'] .= '<meta name="generator" content="Viperal CMS ( www.viperal.com ) Copyright(c) '.date('Y').'" />';
		
		if (file_exists('favicon.ico'))
		{
			$this->header['regular'] .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';
		}
		
		$this->header['regular'] .= '<link rel="alternate" type="application/xml" title="RSS" href="'.$this->siteurl.'backend.php?feed=rdf" />';
		
		if ($_CORE_CONFIG['global']['block_frames'])
		{
			$this->header['js'] .= '<script type="text/javascript">if (self != top) top.location.replace(self.location)</script>';
		}
		
		if ($_CORE_CONFIG['global']['maintenance'])
		{
			$this->message = 'Note your in Maintenance mode<br />';
		}
	
		$_CLASS['core_template']->assign(array(
			'SITE_LANG'			=>	$_CLASS['core_user']->lang['LANG'],
			'SITE_TITLE'		=>	$_CORE_CONFIG['global']['site_name'].': '.$_CORE_MODULE['title'],
			'SITE_BASE'			=>	$this->siteurl,
			'SITE_CHARSET'		=>	$_CLASS['core_user']->lang['ENCODING'],
			'SITE_NAME'			=>	$_CORE_CONFIG['global']['site_name'],
			'SITE_URL'			=>	$_CORE_CONFIG['global']['site_url'],
			'HEADER_MESSAGE'	=>	$this->message,
			'HEADER_REGULAR'	=>	$this->header['meta'].$this->header['regular'],
			'HEADER_JS' 		=>	$this->header['js'],
			'HEADER_BODY' 		=>	$this->header['body']				
			)
		);
		
		if (!THEMEPLATE)
		{
				
			$_CLASS['core_template']->display('head.html');
			themeheader();
			
			if ($this->message)
			{
				echo '<div style="text-align: center; font-size: 16px; color: #FF0000">'.$this->message.'</div>';
			}
			
		}
		
		$_CLASS['core_blocks']->display(BLOCK_MESSAGE_TOP);
		
		if ($this->homepage)
		{  
			$_CLASS['core_blocks']->display(BLOCK_TOP);
		}
		
		if (THEMEPLATE)
		{
			Themeheader();
		}
		
		if (!empty($_CLASS['editor']) && is_object($_CLASS['editor']))
		{
			$_CLASS['editor']->display();
		}
	}
	
	function display_footer($save = true)
	{
		global $_CLASS, $_CORE_MODULE, $_CORE_CONFIG;
		
		if ($this->displayed['footer'])
		{
			return;
		}
		
		// phpnuke compatiblity for like print view, popup, etc.
		// All new modules should use script_close function.  It a must for the next major release..
		if (!$this->displayed['header'])
		{
			script_close($save);
			die;
		}
		
		if ($_CORE_MODULE = $this->get_module())
		{
			global $site_file_root;
			require($site_file_root.'modules/'.$_CORE_MODULE['name'].'/index.php');
		}
		
		$this->displayed['footer'] = true;

		if ($_CORE_MODULE['compatiblity'] && $_CORE_MODULE['copyright'])
		{
			OpenTable();
			echo '<div align="center">'.$_CORE_MODULE['copyright'].'</div>';
			CloseTable();
		}
		
		if ($this->homepage)
		{
			$_CLASS['core_blocks']->display(BLOCK_BOTTOM);
		}
		
		if ($this->displayed['header'])
		{
			themefooter();
			echo '</body></html>';

		}
		
		script_close($save);

		die;	
	}
	
	function footer_debug()
	{
		global $_CORE_CONFIG, $SID, $mainindex, $SID, $_CLASS, $starttime;
	
		$mtime = explode(' ', microtime());
		$totaltime = ($mtime[0] + $mtime[1] - $starttime) - $_CLASS['core_db']->sql_time;
	
		$debug_output = 'Code Time : '.round($totaltime, 4).'s | Queries Time '.round($_CLASS['core_db']->sql_time, 4).'s | ' . $_CLASS['core_db']->sql_num_queries() . ' Queries  ] <br /> [ GZIP : ' .  ((in_array('ob_gzhandler' , ob_list_handlers())) ? 'On' : 'Off' ) . ' | Load : '  . (($_CLASS['core_user']->load) ? $_CLASS['core_user']->load : 'N/A');
		
		if (function_exists('memory_get_usage'))
		{
			if ($memory_usage = memory_get_usage())
			{
				global $base_memory_usage;
				
				$memory_usage -= $base_memory_usage;
				$memory_usage = ($memory_usage >= 1048576) ? round((round($memory_usage / 1048576 * 100) / 100), 2) . ' ' . $_CLASS['core_user']->lang['MB'] : (($memory_usage >= 1024) ? round((round($memory_usage / 1024 * 100) / 100), 2) . ' ' . $_CLASS['core_user']->lang['KB'] : $memory_usage . ' ' . $_CLASS['core_user']->lang['BYTES']);
		
				$debug_output .= ' | Memory Usage: ' . $memory_usage;	
			}
		}

		return $debug_output;
	}
	
	function footmsg()
	{
	
		global $_CORE_CONFIG;
		
		$footer = $this->copyright.'<br />';
		
		if ($_CORE_CONFIG['global']['foot1']) {
			$footer .= $_CORE_CONFIG['global']['foot1'] . '<br />';
		}
		if ($_CORE_CONFIG['global']['foot2']) {
			$footer .= $_CORE_CONFIG['global']['foot2']. '<br />';
		}
		if ($_CORE_CONFIG['global']['foot3']) {
			$footer .= $_CORE_CONFIG['global']['foot3'] . '<br />';
		}
				
		return $footer.'[ '.$this->footer_debug(). ']<br />';
	}
	
	function meta_refresh($time, $url)
	{
		global $_CLASS;
		$this->header['meta'] .= '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '">';
		//<meta http-equiv="refresh" content="3;url=index.php?sid=">
	}
	
	function overLIB()
	{
		static $displayed = false;
		
		if ($displayed)
		{
			return;
		}
		
		$displayed = true;
		$this->header['js'] .= '<script type="text/javascript" src="/includes/javascript/overlib_mini.js"></script>';
		$this->header['body'] .= '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:10;"></div>';
	}
}

function hideblock($id) 
{
    // based from cpgnuke www.cpgnuke.com
    static $hiddenblocks = false;
    
    if (!$hiddenblocks) 
    {
		$hiddenblocks = array();
		
        if (isset($_COOKIE['hiddenblocks']))
        {
            $tmphidden = explode(':', $_COOKIE['hiddenblocks']);
			foreach ($tmphidden as $value)
			{
                $hiddenblocks[$value] = true;
            }
        }
    }
    
    return (empty($hiddenblocks[$id])) ? false : true;
}

?>