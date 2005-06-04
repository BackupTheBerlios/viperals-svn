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
 
class core_display
{

	var $header = array('js'=>'', 'regular'=>'', 'meta'=>'', 'body'=>'');
	var $displayed = array('header'=> false, 'footer'=> false);
	var $message = '';
	var $theme = false;
	var $homepage = false;
	var $modules = array();
	var $copyright = 'Powered by <a href="http://www.viperal.com">Viperal CMS Pre-Beta</a> (c) 2004 Viperal';
	
	function add_module($module)
	{
		global $_CLASS;
		
		//authization check here
		if (!$_CLASS['core_auth']->admin_auth('modules') && !$_CLASS['core_auth']->auth($module['auth']))
		{
			return;
		}
		
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
		$this->display_header($title);
	}
	
	function headers()
	{
		global $_CLASS;

		header('Content-Type: text/html; charset='.$_CLASS['core_user']->lang['ENCODING']);
		header('Content-language: ' . $_CLASS['core_user']->lang['LANG']);
		
		header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
		//header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . " GMT" );
		header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
		header('Expires: 0');
		header('Pragma: no-cache');
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
			$_CORE_MODULE['title'] = $title;
		}
		
		$this->headers();
		
		if ($_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_new_privmsg'] && $_CLASS['core_user']->optionget('popuppm'))
		{
			if (!$_CLASS['core_user']->data['user_last_privmsg'] || ($_CLASS['core_user']->data['user_last_privmsg'] > $_CLASS['core_user']->data['session_time']))
			{
				$this->header['js'] .= '<script type="text/javascript">window.open(\''. preg_replace('/&amp;/', '&', generate_link('Control_Panel&i=pm&mode=popup', array('full' => true)))."', '_phpbbprivmsg','height=135,resizable=yes,status=no,width=400');</script>";

				if (!$_CLASS['core_user']->data['user_last_privmsg'] || $_CLASS['core_user']->data['user_last_privmsg'] > $_CLASS['core_user']->data['session_last_visit'])
				{
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
		
		$this->header['regular'] .= '<link rel="alternate" type="application/xml" title="RSS" href="'.generate_base_url().'backend.php?feed=rdf" />';
		
		if ($_CORE_CONFIG['global']['block_frames'])
		{
			$this->header['js'] .= '<script type="text/javascript">if (self != top) top.location.replace(self.location)</script>';
		}
		
		if ($_CORE_CONFIG['maintenance']['active'] && $_CORE_CONFIG['maintenance']['time'] < time())
		{
			$this->message = '<b>System is in maintenance mode</b><br />';
		}
	
		$_CLASS['core_template']->assign(array(
			'SITE_LANG'			=>	$_CLASS['core_user']->lang['LANG'],
			'SITE_TITLE'		=>	$_CORE_CONFIG['global']['site_name'].': '.$_CORE_MODULE['title'],
			'SITE_BASE'			=>	generate_base_url(),
			'SITE_CHARSET'		=>	$_CLASS['core_user']->lang['ENCODING'],
			'SITE_NAME'			=>	$_CORE_CONFIG['global']['site_name'],
			'SITE_URL'			=>	$_CORE_CONFIG['global']['site_url'],
			'HEADER_MESSAGE'	=>	$this->message,
			'HEADER_REGULAR'	=>	$this->header['meta'].$this->header['regular'],
			'HEADER_JS' 		=>	$this->header['js'],
			'HEADER_BODY' 		=>	$this->header['body']				
			)
		);
		
		$_CLASS['core_blocks']->display(BLOCK_MESSAGE_TOP);
		
		if ($this->homepage)
		{  
			$_CLASS['core_blocks']->display(BLOCK_TOP);
		}
		
		$this->theme_header();
		
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
			$this->theme_footer();
// maybe add to templete file, maybe !
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
		
		if ($_CORE_CONFIG['global']['foot1'])
		{
			$footer .= $_CORE_CONFIG['global']['foot1'] . '<br />';
		}
		
		if ($_CORE_CONFIG['global']['foot2'])
		{
			$footer .= $_CORE_CONFIG['global']['foot2']. '<br />';
		}
		
		if ($_CORE_CONFIG['global']['foot3'])
		{
			$footer .= $_CORE_CONFIG['global']['foot3'] . '<br />';
		}
				
		return $footer.'[ '.$this->footer_debug(). ']<br />';
	}
	
	function meta_refresh($time, $url)
	{
		global $_CLASS;
		$this->header['meta'] .= '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '">';
	}
}

function hideblock($id) 
{
    // From cpgnuke - http://dragonflycms.org/
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