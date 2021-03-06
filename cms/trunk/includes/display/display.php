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
	var $header = array('js' => array(), 'regular' => array(), 'meta' => array());
	var $footer = '';

	var $displayed = array('header'=> false, 'footer'=> false);
	var $message = '';

	var $theme = false;
	var $homepage = false;

	var $pages_holding = array();
	var $page = array();

	var $copyright = 'Powered by <a href="http://www.viperal.com">CMS alpha-dev</a> (c) 2004 - 2005 Ryan Marshall ( Viperal )';

	/*
		Handles sorting and auth'ing of modules
	*/
	function process_page($page, $type = 'page')
	{
		if (!$page)
		{
			return '404:_PAGE_NOT_FOUND';
		}

		global $_CLASS, $_CORE_MODULE;

		settype($page['page_status'], 'int');
		settype($page['page_type'], 'int');

		switch ($type)
		{
			case 'admin':
				settype($page['module_status'], 'int');

				if ($page['module_status'] !== STATUS_ACTIVE)
				{
					return '_PAGE_NOT_ACTIVE';
				}

				foreach ($page as $key => $value)
				{
					$temp[str_replace('module_', 'page_', $key)] = $value;
				}
				$page = $temp;
				unset($temp);

				if (!$_CLASS['core_auth']->admin_power($_CORE_MODULE['page_name']))
				{
					trigger_error('NOT_AUTH', E_USER_ERROR);
				}
				
				if (file_exists(SITE_FILE_ROOT.'admin/'.$page['page_name'].'.php'))
				{
					$page['page_location'] = SITE_FILE_ROOT.'admin/'.$page['page_name'].'.php';
				}
				elseif (file_exists(SITE_FILE_ROOT.'modules/'.$page['page_name'].'/admin/index.php'))
				{
					$page['page_location'] = SITE_FILE_ROOT.'modules/'.$page['page_name'].'/admin/index.php';
				}
				else
				{
					return '404:_PAGE_NOT_FOUND';
				}

				$page['page_blocks'] = 0;
				$page['page_blocks'] |= BLOCK_LEFT;
				$page['page_blocks'] |= BLOCK_RIGHT;
			break;
			
			case 'ajax':
				$page['page_location'] = SITE_FILE_ROOT.'modules/'.$page['page_name'].'/ajax.php';

			//case 'feed':
			default:
				settype($page['page_blocks'], 'int');
				settype($page['page_type'], 'int');

				if ($page['page_status'] !== STATUS_ACTIVE)
				{
					if ($page['page_status'] === STATUS_DISABLED && $_CLASS['core_auth']->admin_auth('pages'))
					{
						$_CLASS['core_display']->message = '<b>Module '.$page['page_name'].' Isn\'t Active</b><br />';
					}
					else
					{
						return '_PAGE_NOT_ACTIVE';
					}
				}

				//authization check here
				$page['page_auth'] = ($page['page_auth']) ? @unserialize($page['page_auth']) : '';
		
				if (($page['page_auth'] && !$_CLASS['core_auth']->auth($page['page_auth'])) && !$_CLASS['core_auth']->admin_power('pages'))
				{
					return '_PAGE_NOT_AUTH';
				}
	
				if (!$page['page_location'])
				{
					$page['page_location'] = SITE_FILE_ROOT.'modules/'.$page['page_name'].'/index.php';
					
					if (!file_exists($page['page_location']))
					{
						return '404:_PAGE_NOT_FOUND';
					}
				}
			break;
		}

		//first page control the sides.
		if (!empty($this->pages_holding))
		{
			$page['page_sides'] = $this->pages_holding[0]['page_sides'];
		}
		else
		{
			$_CLASS['core_user']->page = $page['page_name'];
		}

		$this->pages_holding[] = $page;

		return true;
	}

	/*
		Returns parsed page data
	*/
	function generate_page($type = 'page')
	{
		if (!empty($this->pages_holding))
		{
			// this also unsets $this->modules
			$this->page = array_shift($this->pages_holding);

			if ($this->page['page_location'])
			{
				if ($this->page['page_type'] === PAGE_TEMPLATE)
				{
					global $_CLASS;

					$_CLASS['core_user']->user_setup();

					$this->display(false, $this->page['page_location']);

					return true;
				}

				$this->supported = array();

				require_once $this->page['page_location'];

				if (in_array($type, $this->supported))
				{
					$class_name = 'module_'.$this->page['page_name'];
	
					if (class_exists($class_name))
					{
						$module = new $class_name;
						$method = $type.'_'.$this->page['page_name'];
	
						if (method_exists($module, $method))
						{
							$module->$method();
						}					
					}
				}
			}

			return true;
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

		header('Content-Type: text/html; charset=utf-8');
		header('Content-Language: ' . $_CLASS['core_user']->lang['LANG']);

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
			$this->header['js'][] = '<script type="text/javascript">window.open(\''. preg_replace('/&amp;/', '&', generate_link('Control_Panel&i=pm&mode=popup', array('full' => true)))."', '_phpbbprivmsg','height=135,resizable=yes,status=no,width=400');</script>";
			//$_CLASS['core_db']->sql_query('UPDATE ' . USERS_TABLE . ' SET user_new_privmsg = 0 WHERE user_id = ' . $_CLASS['core_user']->data['user_id']);
		}

		$this->header['regular'][] = '<meta name="generator" content="Viperal CMS ( www.viperal.com ) Copyright(c) '.date('Y').'" />';

		if (file_exists('favicon.ico'))
		{
			$this->header['regular'][] = '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';
		}

		$this->header['regular'][] = '<link rel="alternate" type="application/xml" title="RSS" href="'.generate_base_url().'feed.php?feed=rdf" />';

		if ($_CORE_CONFIG['maintenance']['active'] && $_CORE_CONFIG['maintenance']['start'] < $_CLASS['core_user']->time)
		{
			$this->message = '<b>System is in maintenance mode</b><br />';
		}

		$this->header['js'][] = '<script type="text/javascript" src="javascript/common.js"></script>';
		$this->header['js'][] = "<script type=\"text/javascript\">\nvar cms_session_id = '{$_CLASS['core_user']->data['session_id']}';\nvar cms_cookie_path = '{$_CORE_CONFIG['server']['cookie_path']}';\nvar cms_cookie_domain = '{$_CORE_CONFIG['server']['cookie_domain']}';\n</script>";
		$this->header['meta'][] = '<base href="'.generate_base_url().'" />';

		$this->header['meta'][] = '<meta name="description" content="Development Site for Viperal CMS" />';
		$this->header['meta'][] = '<meta name="keywords" content="viperal, cms, php, mysql, postgresql, postgres, sqlite, nuke, community, forums, bulletin, boards, javascript, open source, GPL, online, html" />';
		
		$_CLASS['core_template']->assign_array(array(
			'SITE_LANG'			=>	$_CLASS['core_user']->lang['LANG'],
			'SITE_TITLE'		=>	$_CORE_CONFIG['global']['site_name'].': '.(is_array($_CORE_MODULE['module_title']) ? implode(' &gt; ', $_CORE_MODULE['module_title']) : $_CORE_MODULE['module_title']),
			'SITE_URL'			=>	generate_base_url(),
			'SID'				=>	empty($_CLASS['core_user']->data['session_id']) ? '' : $_CLASS['core_user']->data['session_id'],
			'SITE_NAME'			=>	$_CORE_CONFIG['global']['site_name'],
			'HEADER_MESSAGE'	=>	$this->message,
			'HEADER_META'		=>	empty($this->header['meta']) ? '' : implode("\n", $this->header['meta']),
			'HEADER_REGULAR'	=>	empty($this->header['regular']) ? '' : implode("\n", $this->header['regular']),
			'HEADER_JS' 		=>	empty($this->header['js']) ? '' : implode("\n", $this->header['js']),
			'FOOTER_CONTENT'	=> $this->footer,
		));

		$_CLASS['core_blocks']->generate(BLOCK_MESSAGE_TOP);
		$_CLASS['core_blocks']->generate(BLOCK_TOP);

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

		if ($this->generate_page())
		{
			return;
		}

		$this->displayed['footer'] = true;

		$_CLASS['core_blocks']->generate(BLOCK_BOTTOM);
		$_CLASS['core_blocks']->generate(BLOCK_MESSAGE_BOTTOM);

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

		$this->header['meta'][] = '<meta http-equiv="refresh" content="' . $time . ';url=' . (($url) ? $url : generate_link(false, array('full' => true))) . '">';
	}
}

?>