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
// this will be core_handlers

class core_error_handler
{
	var $active;
	var $previous_level;
	var $previous_logger = false;

	var $error;
	var $error_array = array();
	var $error_setting = array('title', 'redirect');
	var $debug = array();

	var $report;
	var $logging;

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
		
		set_error_handler(array(&$this, 'error_handler'));
	}
	
	function stop($level = false)
	{
		if (!$this->active)
		{
			return;
		}
		
		$this->active = false;
		
		if ($this->previous_logger !== false)
		{
			ini_set('error_log', $this->previous_logger);
		}
		
		error_reporting(($level) ? $level : $this->previous_level);
		restore_error_handler();
	}

	function debug($option, $name, $sub_option)
	{
		global $_CLASS;

		switch ($option)
		{
			case 'start':

				$start_time = explode(' ', microtime());
				$start_time = $start_time[0] + $start_time[1];

				$this->debug[$name]['start_time'] = $start_time;
				$this->debug[$name]['queries_before_time'] = $_CLASS['core_db']->sql_time;
				$this->debug[$name]['queries_before'] = $_CLASS['core_db']->num_queries;

			break;

			case 'stop':

				if (!isset($this->debug[$name]))
				{
					return;
				}

				$end_time = explode(' ', microtime());
				$end_time = $end_time[0] + $end_time[1];

				$this->debug[$name]['end_time'] = $end_time;
				$this->debug[$name]['queries_after_time'] = $_CLASS['core_db']->sql_time;

			break;

			case 'remove':

				if (isset($this->debug[$name]))
				{
					unset($this->debug[$name]);
				}

			break;

			case 'get':

				switch ($sub_option)
				{
					case 'time':
						return round($this->debug[$name]['end_time'] - $this->debug[$name]['start_time'], 4);
					break;
		
					case 'queries':
						return $this->debug[$name]['queries_after'] - $this->debug[$name]['queries_before'];
					break;
		
					case 'queries_time':
						return round($this->debug[$name]['queries_after_time'] - $this->debug[$name]['queries_before_time'], 4);
					break;
					
					case 'formated':
						return 'Generation time: '.round($this->debug[$name]['end_time'] - $this->debug[$name]['start_time'], 4).'s<br />'
						.'Queries: '.($this->debug[$name]['queries_after'] - $this->debug[$name]['queries_before'])
						.'<br />Queries Time '.round($this->debug[$name]['queries_after_time'] - $this->debug[$name]['queries_before_time'], 4).' s<br />';
					break;
				}

			break;
		}
	}

	function error_handler($errtype, $error, $errfile, $errline)
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
					return;
				}

				$errtype = ($errtype == E_NOTICE) ? 'E_NOTICE' : 'E_WARNING';
				$this->error = array('type' => $errtype, 'error' => $error, 'file'=> $errfile, 'line' => $errline);
				$this->format_error($errtype);

				$this->error_setting = array('title', 'redirect');
			break;

			case E_USER_ERROR:

				$code = false;

				if (strpos($error, ':')) // there shouldn't be a 0 position
				{
					list($code, $error) = explode(':', $error, 2);
					
					if (!is_numeric($code))
					{
						$error = $code.$error;
					}
					else
					{
						$header_array = array(
							404 => 'HTTP/1.0 404 Not Found',
							503 => 'HTTP/1.0 503 Service Unavailable'
						);

						settype($code, 'integer');

						if (!empty($header_array[$code]))
						{
							header($header_array[$code]);
						}
					}
				}

				$error = (!empty($_CLASS['core_user']->lang[$error])) ? $_CLASS['core_user']->lang[$error] : $error;

				$_CLASS['core_template']->assign('MESSAGE_TEXT',  $error);
						
				$_CLASS['core_template']->display('error.html');
					
				script_close(false);
				die;
				
				break;
	
			case E_USER_NOTICE:

				$this->error_setting['title'] = (!empty($_CLASS['core_user']->lang[$this->error_setting['title']])) ? $_CLASS['core_user']->lang[$this->error_setting['title']] : $this->error_setting['title'];
				
				$error = (!empty($_CLASS['core_user']->lang[$error])) ? $_CLASS['core_user']->lang[$error] : $error;
				
				$_CLASS['core_display']->display_head($this->error_setting['title']);

				$_CLASS['core_template']->assign(array(
					'MESSAGE_TITLE'	=> $this->error_setting['title'],
					'MESSAGE_TEXT'	=> $error
				));
				
				$this->error_setting = array('title', 'redirect');
				
				$_CLASS['core_template']->display('message.html');

				$_CLASS['core_display']->display_footer(false);
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