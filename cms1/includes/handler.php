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
// Add saving reports to a log file and its define ..
// Fix up error level defines

class core_error_handler
{
	var $active;
	var $previous_level;
	var $previous_logger;
	var $error_array = array();
	var $error;
	var $report;
	var $logging;
	var $error_setting = array('type', 'title', 'redirect');
	
	function start($report = ERROR_NONE, $log_file = false)
	{
		if ($this->active)
		{
			return;
		}
		
		$this->active = true;
		$this->report = $report;
		$this->previous_level = ini_set('error_reporting', 0);
		$this->logging = ($logfile && is_writable($logfile)) ? true : false;
		
		if ($this->logging && $log_file)
		{
			$this->previous_logger = ini_set('error_log', $log_file);
		}
		
		set_error_handler(array(&$this, 'handler'));
	}
	
	function stop($level = false)
	{
		if (!$this->active)
		{
			return;
		}
		
		$this->active = false;
		
		// logging could of been changed by user
		if ($this->logging || $this->previous_logger)
		{
			ini_set('error_log', $this->previous_logger);
		}
		
		error_reporting(($level) ? $level : $this->previous_level);
		restore_error_handler();
	}
	
	function handler($errtype, $error, $errfile, $errline)
	{

		global $_CLASS, $site_file_root, $_CORE_CONFIG;
		
		if ($this->report != ERROR_NONE)
		{
			//damn windows
			$errfile = str_replace('\\','/', $errfile);
			// Remove the root paths, site files, along with document root
			$errfile = str_replace($site_file_root, '', str_replace($_SERVER['DOCUMENT_ROOT'],'', $errfile));
		}
				
		switch ($errtype)
		{
			case E_NOTICE:
			case E_WARNING:
			
				if (!$this->report)
				{
					if ($errtype == E_WARNING)
					{
						header("HTTP/1.0 500 INTERNAL SERVER ERROR");
						script_close();
						die('E_WARNING error');
					}
					return;
				}
				
				$errtype = ($errtype == E_NOTICE) ? 'E_NOTICE' : 'E_WARNING';
				$this->error = array('type' => $errtype, 'error' => $error, 'file'=> $errfile, 'line' => $errline);
				$this->format_error($errtype);

				if ($errtype == 'E_WARNING')
				{
					if (empty($_CLASS['core_user']))
					{
						if ($this->report == ERROR_ONPAGE)
						{
							echo "PHP $type: in file <b>".$this->error['file'].'</b> on line <b>'
								.$this->error['line'].'</b>: <b>'.$this->error['error'].'</b><br/>';
						
						} else {
						
							echo 'There is a error on this page that isn\'t detectable with the error'
								.' reoprter<br/>Please set error level to 4 in core.php to see the error';
						}
						
					}
					
					if ($this->logging)
					{
						$this->error_log();
					}
					
					header("HTTP/1.0 500 INTERNAL SERVER ERROR");
					echo "PHP $type: in file <b>".$this->error['file'].'</b> on line <b>'
								.$this->error['line'].'</b>: <b>'.$this->error['error'].'</b><br/>';
					script_close();
					die;
				}
				
				$this->error_setting = array('type', 'title', 'redirect');
				
			break;
	
			case E_USER_ERROR:
			
				$error = (!empty($_CLASS['core_user']->lang[$error])) ? $_CLASS['core_user']->lang[$error] : $error;
					
				if ($this->error_setting['header'])
				{
					$header_array = array(
						'404' => 'HTTP/1.0 404 Not Found',
						'503' => 'HTTP/1.0 503 Service Unavailable'
						);
						
					if (!empty($header_array[$this->error_setting['header']]))
					{
						header($header_array[$this->error_setting['header']]);
					}
				}
				
				if (!empty($_CLASS['core_display']) && $_CLASS['core_display']->displayed['header'])
				{
					OpenTable();
					echo '<h2 align="center">Error</h2>';
					echo '<br clear="all" /><table width="85%" cellspacing="0" cellpadding="0" border="0" align="center"><tr><td><br clear="all" />' . $error . '<hr />Please notify the board administrator or webmaster : <a href="mailto:' . $_CORE_CONFIG['global']['admin_mail'] . '">' . $_CORE_CONFIG['global']['admin_mail'] . '</a></td></tr></table><br clear="all" /></body></html>';
					CloseTable();
					
					$_CLASS['core_display']->display_footer();
				}
				
				$_CLASS['core_template']->assign('MESSAGE_TEXT',  $error);
						
				$_CLASS['core_template']->display('error.html');
					
				script_close(false);
				die;
				
				break;
	
			case E_USER_NOTICE:

				$this->error_setting['title'] = (!empty($_CLASS['core_user']->lang[$this->error_setting['title']])) ? $_CLASS['core_user']->lang[$this->error_setting['title']] : $this->error_setting['title'];
				
				$error = (!empty($_CLASS['core_user']->lang[$error])) ? $_CLASS['core_user']->lang[$error] : $error;
				
				$_CLASS['core_display']->display_head($this->error_setting['title']);

				if (defined('IN_ADMIN') && !empty($_CLASS['core_user']->data['session_admin']))
				{
					// this is phpbb 2.1.2 remove it
					adm_page_message($msg_title, $msg_text, false);
					adm_page_footer();
				}
				else
				{
					$_CLASS['core_template']->assign(array(
						'MESSAGE_TITLE'	=> $this->error_setting['title'],
						'MESSAGE_TEXT'	=> $error)
					);
					
					$this->error_setting = array('type', 'title', 'redirect', 'header');
					
					$_CLASS['core_template']->display('message.html');
	
					$_CLASS['core_display']->display_footer(false);
				}
	
			break;
		}
	}

   function format_error($type)
   {
		if ($this->report == ERROR_ONPAGE)
		{
			echo "PHP $type: in file <b>".$this->error['file'].'</b> on line <b>'.$this->error['line'].'</b>: <b>'.$this->error['error'].'</b><br/>';		
		}
		else
		{
			$this->error_array[$type][] = $this->error;
		}
		
		if ($this->logging)
		{
			//$this->error_log();
		}
   }
}

?>