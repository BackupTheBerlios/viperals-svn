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
require_once 'core.php';

if ($mod = get_variable('mod', 'REQUEST', false))
{
	$sql = 'SELECT * FROM ' . CORE_PAGES_TABLE . "
				WHERE page_name = '" . $_CLASS['core_db']->escape($mod) . "'";//WHERE module_type = ' . MODULE_NORMAL . "

	//Grab module data if it exsits
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$status = $_CLASS['core_display']->process_page($row, 'ajax');

	if ($status !== true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		script_close(false);
	}

	$_CLASS['core_display']->generate_page('ajax');
}

script_close(false);

?>