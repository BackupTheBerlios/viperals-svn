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

define('VIPERAL', 'Admin');

error_reporting(E_ALL);

/* require_once SITE_FILE_ROOT.'core.php'; */
require_once 'core.php';


if (!$_CLASS['core_user']->is_admin)
{
	//die('this is admin only :-) need better language :-(');
}

$_CLASS['core_handler']->stop();
$_CLASS['core_user']->user_setup();

error_reporting(E_ALL);

$mode = get_variable('mode', 'GET', false);

$_CLASS['core_template']->assign_array(array(
	'MODE'				=>	$mode,
	'L_NOTICES'			=>	'NOTICE ERRORS',
	'L_WARNINGS'		=>	'WARNING ERRORS',
	'L_QUERIES'			=>	'DB QUERY DETAILS',
));

switch ($mode)
{
	case 'warning':
		global $_CLASS;
		
		$debug_data = $_CLASS['core_user']->session_data_get('debug');
		
		if (empty($debug_data['E_WARNING']))
		{
			 break;
		}

		$size = count($debug_data['E_WARNING']);
	
		for ($i=0; $i < $size; $i++)
		{
			$_CLASS['core_template']->assign_vars_array('error_warnings', array(
				'errfile'	=> $debug_data['E_WARNING'][$i]['file'],
				'errline'	=> $debug_data['E_WARNING'][$i]['line'], 
				'msg_text' => $debug_data['E_WARNING'][$i]['error']
			));
		}
	break;

	case 'queries':
		$query_details = $_CLASS['core_user']->session_data_get('query_details', array());
		$query_list = $_CLASS['core_user']->session_data_get('query_list', array());

		foreach ($query_list as $key => $value)
		{
			$tempid = $first = false;
			$header = array();
		
			foreach ($query_details[$key] as $value2)
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
	break;
	
	default:
	/* case 'notice': */
		$debug_data = $_CLASS['core_user']->session_data_get('debug');
		
		if (empty($debug_data['E_NOTICE']))
		{
			break;
		}
		$size = count($debug_data['E_NOTICE']);
		
		for ($i = 0; $i < $size; $i++)
		{
			$_CLASS['core_template']->assign_vars_array('error_notice', array(
				'errfile'	=> $debug_data['E_NOTICE'][$i]['file'],
				'errline'	=> $debug_data['E_NOTICE'][$i]['line'], 
				'msg_text' => $debug_data['E_NOTICE'][$i]['error']
			));
		}
	break;
}

$_CLASS['core_template']->display('debug.html');
script_close(false);

?>