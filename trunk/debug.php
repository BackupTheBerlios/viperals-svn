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

define('VIPERAL', 'Admin');

//echo str_replace('\\','/', getenv('DOCUMENT_ROOT')); die;
$site_file_root = '';

require($site_file_root.'core.php');

if (!$_CLASS['core_user']->is_admin)
{
	//die('this is admin only :-) need better language :-(');
}

$_CLASS['core_error_handler']->stop();
error_reporting(E_ALL);

$_CLASS['core_debug'] = new debug();
$_CLASS['core_debug']->display();
	
class debug
{
	function display()
	{
		global $_CLASS;
		$mode = get_variable('mode', 'GET', false);

		switch ($mode)
		{
			case 'notice':
				$this->display_notice();
				break;
				
			case 'warning':
				$this->display_warning();
				break;
				
			case 'queries':
				$this->display_queries();
				break;
				
			default:
				$mode = 'notice';
				$this->display_notice();
		}
		
	    $_CLASS['core_template']->assign(array(
			'MODE'				=>	$mode,
			'L_NOTICES'			=>	'NOTICE ERRORS',
			'L_WARNINGS'		=>	'WARNING ERRORS',
			'L_QUERIES'			=>	'DB QUERY DETAILS',
			'bottomblock'		=>	false,
			'MAIN_CONTENT'		=>	false,
		));
	
		$_CLASS['core_template']->display('debug.html');
		script_close(false);
	}
	
	function display_notice()
	{
		global $_CLASS;
		
		$this->debug_data = $_CLASS['core_user']->get_data('debug');

		if (!empty($this->debug_data['E_NOTICE']))
		{

			$size = count($this->debug_data['E_NOTICE']);
			
			for ($i = 0; $i < $size; $i++)
			{
				$_CLASS['core_template']->assign_vars_array('error_notice', array(
					'errfile'	=> $this->debug_data['E_NOTICE'][$i]['file'],
					'errline'	=> $this->debug_data['E_NOTICE'][$i]['line'], 
					'msg_text' => $this->debug_data['E_NOTICE'][$i]['error']
				));
			}
			
			return;
		}
	}
	
	function display_warning()
	{
		global $_CLASS;
		
		$this->debug_data = $_CLASS['core_user']->get_data('debug');

		if (!empty($this->debug_data['E_WARNING']))
		{
		
			$size = count($this->debug_data['E_WARNING']);
		
			for ($i=0; $i<$size; $i++)
			{
				$_CLASS['core_template']->assign_vars_array('error_warnings', array(
					'errfile'	=> $this->debug_data['E_WARNING'][$i]['file'],
					'errline'	=> $this->debug_data['E_WARNING'][$i]['line'], 
					'msg_text' => $this->debug_data['E_WARNING'][$i]['error']
				));
			}
		}
	}
	
	function display_queries()
	{
		global $_CLASS;
		
		$this->querydetails = $_CLASS['core_user']->get_data('querydetails');
		$this->querylist = $_CLASS['core_user']->get_data('querylist');

		foreach ($this->querylist as $key => $value)
		{
			$tempid = $first = false;
			$header = array();

			foreach ($this->querydetails[$key] as $value2)
			{
				$queries = array();

				if (!is_array($value2))
				{
					continue;
				}
				
				if ($tempid !== $key)
				{
					foreach (array_keys($value2) as $key2)
					{
						$header[] = array('value' => (($key2) ? ucwords(str_replace('_', ' ', $key2)) : '&nbsp;'));
					}
					$tempid = $key;
				}
				
				foreach ($value2 as $key3 => $value3)
				{
					if (!$first || $first != $key3)
					{
						if (!$first)
						{
							$first = $key3;
						}
						
						$queries[] = array('row' => $key, 'value' => $value3, 'new' => false);
					}
					else
					{
						$queries[] = array('row' => $key, 'value' => $value3, 'new' => true);
					}
				}
			}

			$value['query'] = preg_replace('/\t(AND|OR)(\W)/', "\$1\$2", htmlspecialchars(preg_replace('/[\s]*[\n\r\t]+[\n\r\s\t]*/', "\n", $value['query'])));

			$_CLASS['core_template']->assign_vars_array('query', array('row' => $key, 'query' => $value['query'], 'header' => $header, 'queries' => $queries, 'file' => $value['file'], 'line' => $value['line'], 'affected' => $value['affected'], 'time' => round($value['time'], 4)));
		}
	}
}

?>