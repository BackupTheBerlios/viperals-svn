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
//Add module authization check
//Add check for modules, default module should be overriten by othere moduels.
 
class display
{

	var $header = array('js'=>'', 'regular'=>'', 'meta'=>'', 'body'=>'');
	var $displayed = array('header'=> false, 'footer'=> false);
	var $message = '';
	var $theme = false;
	var $homepage = false;
	var $modules = array();
	
	function display()
	{
		global $_CLASS, $site_file_root, $MAIN_CFG, $SID;
		
		//make sure to add a test for https, bla bla bla
		$this->siteurl = 'http://'.getenv('HTTP_HOST').$MAIN_CFG['server']['path'];
		$this->copyright = 'Powered by <a href="http://www.viperal.com">Viperal CMS Pre-Beta</a> (c) 2004 Viperal';
		
		$this->themeprev = get_variable('prevtheme', 'POST', false);
		$this->themeprev = ($this->themeprev) ? $this->themeprev : get_variable('prevtheme', 'GET', false);
		
		$this->theme = $_CLASS['user']->get_data('theme');
		
		if ($this->themeprev && ($this->themeprev != $this->theme) && $this->check_theme($this->themeprev))
		{
			$this->theme = $this->themeprev;
			define('THEMEPLATE', $this->temp);
			$_CLASS['user']->set_data('theme', $this->theme);
			
		}
		elseif ($this->theme && $this->check_theme($this->theme))
		{
			define('THEMEPLATE', $this->temp);
		}
		else
		{
           	$this->theme = ($_CLASS['user']->data['theme']) ? $_CLASS['user']->data['theme']
						: $MAIN_CFG['global']['default_theme'];     
	
			if ($this->check_theme($this->theme))
			{
				define('THEMEPLATE', $this->temp);
				
			} elseif ($this->check_theme($MAIN_CFG['global']['default_theme'])) {
				
				$this->theme = $MAIN_CFG['global']['default_theme'];
				define('THEMEPLATE', $this->temp);
				
			} else {
				// error here the site has no theme
			}
		}
    	
		if (THEMEPLATE)
		{
			require($site_file_root.'themes/'.$this->theme.'/index.php');
		} else {
			require($site_file_root.'themes/'.$this->theme.'/theme.php');
		}
	}
	
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
		//Add authization check here
		
		//first module control the sides.
		if (!empty($this->modules))
		{
			$module['sides'] = $this->modules[0]['sides'];
		}
		
		$this->modules[] = $module;
	}
	
	function display_head($title = false)
	{
		global $_CLASS, $MAIN_CFG, $Module;
		
		if ($this->displayed['header'])
		{
			return;
		}
		
		$this->displayed['header'] = true;
		
		if (extension_loaded('zlib'))
		{
			ob_start('ob_gzhandler');
		}

		if ($title)
		{
			$Module['title'] = $title;
		}
		
		header('Content-Type: text/html; charset='.$_CLASS['user']->lang['ENCODING']);
		header('Content-language: ' . $_CLASS['user']->lang['LANG']);
		
		header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . " GMT" );
		header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0, max-age=0');
		//header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		if (is_user() && $_CLASS['user']->data['user_new_privmsg'] && $_CLASS['user']->optionget('popuppm'))
		{
			if (!$_CLASS['user']->data['user_last_privmsg'] || $_CLASS['user']->data['user_last_privmsg'] < $_CLASS['user']->data['session_last_visit'])
			{
				$this->header['js'] .= '<script type="text/javascript">window.open(\''
				. getlink('Control_Panel&i=pm&mode=popup', false, true)."', '_phpbbprivmsg','
				.'HEIGHT=225,resizable=yes,WIDTH=400');</script>";

				if (!$_CLASS['user']->data['user_last_privmsg'] || $_CLASS['user']->data['user_last_privmsg'] > $_CLASS['user']->data['session_last_visit'])
				{
					$sql = 'UPDATE ' . USERS_TABLE . '
						SET user_last_privmsg = ' . time() . '
						WHERE user_id = ' . $_CLASS['user']->data['user_id'];
					$_CLASS['db']->sql_query($sql);
				}
			}
		}

		$this->header['regular'] .= '<meta name="generator" content="Viperal CMS ( www.viperal.com ) Copyright(c) '.date('Y').'" />';
		
		if (file_exists('favicon.ico'))
		{
			$this->header['regular'] .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';
		}
		
		$this->header['regular'] .= '<link rel="alternate" type="application/xml" title="RSS" href="'.$this->siteurl.'backend.php?feed=rdf" />';
		
		if ($MAIN_CFG['global']['block_frames'])
		{
			$this->header['js'] .= '<script type="text/javascript">if (self != top) top.location.replace(self.location)</script>';
		}
		
		if ($MAIN_CFG['global']['maintenance'])
		{
			$this->message = 'Note your in Maintenance mode<br />';
		}
	
		$_CLASS['template']->assign(array(
			'SITE_LANG'			=>	$_CLASS['user']->lang['LANG'],
			'SITE_TITLE'		=>	$MAIN_CFG['global']['sitename'].': '.$Module['title'],
			'SITE_BASE'			=>	$this->siteurl,
			'SITE_CHARSET'		=>	$_CLASS['user']->lang['ENCODING'],
			'SITE_NAME'			=>	$MAIN_CFG['global']['sitename'],
			'SITE_URL'			=>	$MAIN_CFG['global']['siteurl'],
			'HEADER_MESSAGE'	=>	$this->message,
			'HEADER_REGULAR'	=>	$this->header['meta'].$this->header['regular'],
			'HEADER_JS' 		=>	$this->header['js'],
			'HEADER_BODY' 		=>	$this->header['body']				
			)
		);
		
		if (!THEMEPLATE)
		{
				
			$_CLASS['template']->display('head.html');
			themeheader();
			
			if ($this->message)
			{
				echo '<div style="text-align: center; font-size: 16px; color: #FF0000">'.$this->message.'</div>';
			}
			
		}
		
		$_CLASS['blocks']->display(BLOCK_MESSAGE);
		
		if ($this->homepage)
		{  
			$_CLASS['blocks']->display(BLOCK_TOP);
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
		global $_CLASS, $phpEx, $Module, $MAIN_CFG;
		static $nextmodule = 1; // Maybe make this accessable $this->nextmodule
		
		if ($this->displayed['footer'])
		{
			return;
		}
		
		if (!empty($this->modules))
		{
			//do extraction here
			global $Module, $site_file_root;
			
			$Module = $this->modules[$nextmodule];
			unset($this->modules[$nextmodule]);
			$nextmodule ++;
			
			require($site_file_root.'modules/'.$Module['name'].'/index.php');
		}
		
		$this->displayed['footer'] = true;

		if ($Module['compatiblity'] && $Module['copyright'])
		{
			OpenTable();
			echo '<div align="center">'.$Module['copyright'].'</div>';
			CloseTable();
		}
		
		if ($this->homepage)
		{
			$_CLASS['blocks']->display(BLOCK_BOTTOM);
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
		global $MAIN_CFG, $SID, $mainindex, $SID, $_CLASS, $starttime;
	
		$mtime = explode(' ', microtime());
		$totaltime = ($mtime[0] + $mtime[1] - $starttime) - $_CLASS['db']->sql_time;
	
		$debug_output = 'Code Time : '.round($totaltime, 4).'s | Queries Time '.round($_CLASS['db']->sql_time, 4).'s | ' . $_CLASS['db']->sql_num_queries() . ' Queries  ] <br /> [ GZIP : ' .  ((in_array('ob_gzhandler' , ob_list_handlers())) ? 'On' : 'Off' ) . ' | Load : '  . (($_CLASS['user']->load) ? $_CLASS['user']->load : 'N/A');
		
		if (function_exists('memory_get_usage'))
		{
			if ($memory_usage = memory_get_usage())
			{
				global $base_memory_usage;
				$memory_usage -= $base_memory_usage;
				$memory_usage = ($memory_usage >= 1048576) ? round((round($memory_usage / 1048576 * 100) / 100), 2) . ' ' . $_CLASS['user']->lang['MB'] : (($memory_usage >= 1024) ? round((round($memory_usage / 1024 * 100) / 100), 2) . ' ' . $_CLASS['user']->lang['KB'] : $memory_usage . ' ' . $_CLASS['user']->lang['BYTES']);
		
				$debug_output .= ' | Memory Usage: ' . $memory_usage;	
			}
		}

		return $debug_output;
	}
	
	function footmsg()
	{
	
		global $MAIN_CFG;
		
		$footer = $this->copyright.'<br />';
		
		if ($MAIN_CFG['global']['foot1']) {
			$footer .= $MAIN_CFG['global']['foot1'] . '<br />';
		}
		if ($MAIN_CFG['global']['foot2']) {
			$footer .= $MAIN_CFG['global']['foot2']. '<br />';
		}
		if ($MAIN_CFG['global']['foot3']) {
			$footer .= $MAIN_CFG['global']['foot3'] . '<br />';
		}
				
		return $footer.'[ '.$this->footer_debug(). ']<br />';
	}
	
	function meta_refresh($time, $url)
	{
		global $_CLASS;
		$this->header['meta'] = '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '">';
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
    // based/idea from cpgnuke www.cpgnuke.com
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
    return (empty($hiddenblocks[$id]) ? false : true);
}

?>