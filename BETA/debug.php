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

define('CPG_NUKE', 'XMLFEED');
require('mainfile.php');
//error_reporting(E_ALL);

/*if (!is_admin())
{
	die('this is admin only :-) need better language :-(');
}*/
error_reporting(0);

$debug	= new debug();
$debug->display();
	
class debug
{
	function display()
	{
		global $_CLASS;
		$mode = get_variable('mode', 'GET', false);
		$this->debug_data = $_CLASS['user']->get_data('debug');

		switch ($mode)
		{
			case 'notice':
				$this->display_notice();
				break;
			case 'warning':
				$this->display_warning();
				break;

			default:
				$mode = 'notice';
				$this->display_notice();
		}
		
		//print_r($this->debug_data);

	    $_CLASS['template']->assign(array(
			'MODE'				=>	$mode,
			'L_NOTICES'			=>	'NOTICE ERRORS',
			'L_WARNINGS'		=>	'WARNING ERRORS',
			'L_QUERIES'			=>	'DB QUERY DETAILS',
			'bottomblock'		=>	false,
			'MAIN_CONTENT'		=>	false,
			)
		);
	
		$_CLASS['template']->display('debug.html');
	}
	
	function display_notice()
	{
		global $_CLASS;
		if (!empty($this->debug_data['E_NOTICE'])) {

			$size = count($this->debug_data['E_NOTICE']);
			for ($i=0; $i< $size; $i++)
			{
				$_CLASS['template']->assign_vars_array('error_notice', array(
					'errfile'	=> $this->debug_data['E_NOTICE'][$i]['errfile'],
					'errline'	=> $this->debug_data['E_NOTICE'][$i]['errline'], 
					'msg_text' => $this->debug_data['E_NOTICE'][$i]['msg_text']	)
				);
			}
		}
	}
	
	function display_warning()
	{
		global $_CLASS;
		if (isset($this->debug_data['E_WARNING'])) {
		
			$size = count($phperror['E_WARNING']);
		
			for ($i=0; $i<$size; $i++)
			{
				$_CLASS['template']->assign_vars_array('error_warnings', array(
					'errfile'	=> $this->debug_data['E_WARNING'][$i]['errfile'],
					'errline'	=> $this->debug_data['E_WARNING'][$i]['errline'], 
					'msg_text' => $this->debug_data['E_WARNING'][$i]['msg_text'])
				);
			}
		}
	}
}
?>