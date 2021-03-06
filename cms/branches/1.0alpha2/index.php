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

define('VIPERAL', 'CMS');

error_reporting(E_ALL);

//require(SITE_FILE_ROOT.'core.php');
require('core.php');

$mod = get_variable('mod', 'REQUEST', false);

if (!$mod)
{
	// Set as homepage 
	$_CLASS['core_display']->homepage = true;
	$_CORE_CONFIG['global']['index_page'] = 'articles';

	$result = $_CLASS['core_db']->query('SELECT * FROM '.CORE_MODULES_TABLE." WHERE module_name = '{$_CORE_CONFIG['global']['index_page']}'");

	While ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_display']->add_module($row);
	}

	$_CLASS['core_db']->free_result($result);

	if (!($_CORE_MODULE = $_CLASS['core_display']->get_module()))
	{
		$_CORE_MODULE['module_sides'] = BLOCK_ALL;
		$_CORE_MODULE += array('module_name' => '', 'module_title' => ''); // temp

		$_CLASS['core_user']->user_setup();
		$_CLASS['core_display']->display_header();

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
	if ($mod === 'system')
	{
		require_once(SITE_FILE_ROOT.'includes/system.php');

		$mode = get_variable('mode', 'REQUEST', false);

		if (!$mode || !function_exists($mode))
		{
			header("HTTP/1.0 503 Service Unavailable");
			script_close(false);
		}

		$mode();

		script_close(false);
	}

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
		trigger_error($status, E_USER_ERROR);
	}

	$_CORE_MODULE = $_CLASS['core_display']->get_module();
}

$path = SITE_FILE_ROOT.'modules/'.$_CORE_MODULE['module_name'].'/index.php';
$_CLASS['core_user']->page = $_CORE_MODULE['module_name'];

require_once($path);

script_close();

?>