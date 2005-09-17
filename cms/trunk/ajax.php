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
define('VIPERAL', 'AJAX');

if (empty($_GET['sid']))
{
	die;
}

//require(SITE_FILE_ROOT.'core.php');
require('core.php');

$mod = get_variable('mod', 'REQUEST', false);

if (!$mod)
{
	die;
}
else
{
	$sql = 'SELECT * FROM '.CORE_MODULES_TABLE.'
				WHERE module_type = ' . MODULE_NORMAL . "
				AND module_name = '" . $_CLASS['core_db']->escape($mod) . "'";

	//Grab module data if it exsits
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$status = $_CLASS['core_display']->add_module($row);

	if ($status !== true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		script_close(false);
	}

	$_CORE_MODULE = $_CLASS['core_display']->get_module();
}

$path = SITE_FILE_ROOT.'modules/'.$_CORE_MODULE['module_name'].'/ajax.php';
$_CLASS['core_user']->page = $_CORE_MODULE['module_name'];

require_once($path);

script_close(false);

?>