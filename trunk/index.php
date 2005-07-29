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
define('VIPERAL', 'CMS');

//echo str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/'; die;
$site_file_root = '';
require($site_file_root.'core.php');

$mod = get_variable('mod', 'REQUEST', false);

if (!$mod)
{
	// Set as homepage 
	$_CLASS['core_display']->homepage = true;

	// Get homepage modules
	$result = $_CLASS['core_db']->query('SELECT * FROM '.CORE_MODULES_TABLE.' WHERE homepage > 0 ORDER BY homepage ASC');

	While ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_display']->add_module($row);
	}

	$_CLASS['core_db']->free_result($result);

	if (!($_CORE_MODULE = $_CLASS['core_display']->get_module()))
	{
		$_CORE_MODULE['sides'] = BLOCK_ALL;
		$_CORE_MODULE += array('name' => '', 'title' => ''); // temp

		$_CLASS['core_display']->display_head();
		// Hey admin we don't have a modules set
		if ($_CLASS['core_auth']->admin_auth('modules'))
		{
			$_CLASS['core_display']->message = '_NO_HOMEPAGE_ADMIN';
		}
	
		$_CLASS['core_display']->display_footer();
	}
}
else
{
	if ($mod == 'system')
	{
		include_once($site_file_root.'includes/system.php');

		$mode = get_variable('mode', 'REQUEST', false);
		if (!$mode || !function_exists($mode))
		{
			script_close(false);
		}

		$mode();
		script_close(false);
	}

	//Grab module data if it exsits
	$result = $_CLASS['core_db']->query('SELECT * FROM '.CORE_MODULES_TABLE.' WHERE type='.MODULE_NORMAL." AND name='".$_CLASS['core_db']->escape($mod)."'");
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$status = $_CLASS['core_display']->add_module($row);
	
	if ($status !== true)
	{
		trigger_error($status, E_USER_ERROR);
	}

	$_CORE_MODULE = $_CLASS['core_display']->get_module();
}

$path = $site_file_root.'modules/'.$_CORE_MODULE['name'].'/index.php';
$_CLASS['core_user']->page = $_CORE_MODULE['name'];

require_once($path);

script_close();

?>