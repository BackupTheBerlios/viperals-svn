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
	var $title;
	var $active;
	var $previous_level;
	var $previous_logger;
	var $error_array = array();
	var $error;
	var $report;
	var $logging;
	
	function start($report = ERROR_NONE, $logfile = false)
	{
		if ($this->active)
		{
			return;
		}
		
		$this->active = true;
		$this->report = $report;
		$this->previous_level = ini_set('error_reporting', 0);
		$this->logging = ($logfile && is_writable($logfile)) ? true : false;
		
		if ($this->logging)
		{
			$this->previous_logger = ini_set('error_log', $logfile);
		}
		
		set_error_handler(array(&$this, 'handler'));
	}
	
	function stop($level = 0)
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

		global $_CLASS, $site_file_root, $MAIN_CFG;
		
		//dam windows
		$errfile = ereg_replace("[\]",'/', $errfile);
		// Remove the root paths, site files, along with document root
		$errfile = ereg_replace($site_file_root, '', ereg_replace($_SERVER['DOCUMENT_ROOT'],'', $errfile));

		switch ($errtype)
		{
			case E_NOTICE:
			case E_WARNING:
			
				if (!$this->report)
				{
					return;
				}
				
				$errtype = ($errtype == E_NOTICE) ? 'E_NOTICE' : 'E_WARNING';
				$this->error = array('type' => $errtype, 'error' => $error, 'file'=> $errfile, 'line' => $errline);
				$this->format_error($errtype);

				if ($errtype == 'E_WARNING')
				{
					if (empty($_CLASS['user']))
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
						//error_log();
					}
					
					script_close();
					die;
				}
				
			break;
	
			case E_USER_ERROR:
			
				if (!empty($_CLASS['display']) && $_CLASS['display']->displayed['header'])
				{
					OpenTable();
					echo '<h2 align="center">Error</h2>';
					echo '<br clear="all" /><table width="85%" cellspacing="0" cellpadding="0" border="0" align="center"><tr><td><br clear="all" />' . $error . '<hr />Please notify the board administrator or webmaster : <a href="mailto:' . $MAIN_CFG['global']['admin_mail'] . '">' . $MAIN_CFG['global']['admin_mail'] . '</a></td></tr></table><br clear="all" /></body></html>';
					CloseTable();
					
					$_CLASS['display']->display_footer();
				}
				
				$error = (!empty($_CLASS['user']->lang[$error])) ? $_CLASS['user']->lang[$error] : $error;
				$_CLASS['template']->assign('MESSAGE_TEXT',  $error);
						
				$_CLASS['template']->display('error.html');
					
				script_close(false);
				die;
				
				break;
	
			case E_USER_NOTICE:

				global $msg_title, $show_prev_info;
				// remove msg_title fix those in Forums
				
				$msg_title = (!isset($msg_title)) ? $_CLASS['user']->lang['INFORMATION'] : ((!empty($_CLASS['user']->lang[$msg_title])) ? $_CLASS['user']->lang[$msg_title] : $msg_title);
				$this->title = ($this->title) ? $this->title : $msg_title;
				
				$error = (!empty($_CLASS['user']->lang[$error])) ? $_CLASS['user']->lang[$error] : $error;
				
				$_CLASS['display']->display_head($msg_title);
	
				if (defined('IN_ADMIN') && !empty($user->data['session_admin']))
				{
					// this is phpbb 2.1.2 remove it
					$show_prev_info = (!isset($show_prev_info)) ? true : (bool) $show_prev_info;
					adm_page_message($msg_title, $msg_text, false, $show_prev_info);
					adm_page_footer();
				}
				else
				{
	
					$_CLASS['template']->assign(array(
						'MESSAGE_TITLE'	=> $this->title,
						'MESSAGE_TEXT'	=> $error)
					);
					
					$_CLASS['template']->display('message.html');
	
					$_CLASS['display']->display_footer(false);
				}
	
			break;
		}
	}

   function format_error($type)
   {
		if ($this->report == ERROR_ONPAGE)
		{
			// fix this up
			echo "PHP $type: in file <b>".$this->error['file'].'</b> on line <b>'.$this->error['line'].'</b>: <b>'.$this->error['error'].'</b><br/>';
					
		} else {
		
			$this->error_array[$type][] = $this->error;
			
		}
		
		if ($this->logging)
		{
			//error_log();
		}
   }
}

?>