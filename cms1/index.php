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
$site_file_root = 'C:/apachefriends/xampp/cms/';
require($site_file_root.'core.php');

// needed for the content module 
//$content = key($_GET);

$mod = get_variable('mod', 'REQUEST', false);

if (!$mod)
{
	// Set as homepage 
	$_CLASS['core_display']->homepage = true;
}
else
{
	$path = "modules/$mod/index.php";
}

if ($_CLASS['core_display']->homepage)
{
	// Get homepage modules
	$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.CORE_MODULES_TABLE.' WHERE homepage > 0 ORDER BY homepage ASC');
	
	While ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$_CLASS['core_display']->add_module($row);
	}
	
	$_CORE_MODULE = $_CLASS['core_display']->get_module();
	$path = 'modules/'.$_CORE_MODULE['name'].'/index.php';

}
else
{
	//Grab module data if it exsits
	$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.CORE_MODULES_TABLE.' WHERE type='.MODULE_NORMAL." AND name='".$_CLASS['core_db']->sql_escape($mod)."'");
	$_CORE_MODULE = $_CLASS['core_db']->sql_fetchrow($result);
}

$_CLASS['core_db']->sql_freeresult($result);
$path = $site_file_root.$path;

// Bug with more then one homepage
// Move this to Display class or something

if (!$_CORE_MODULE || !file_exists($path))
{
	$_CORE_MODULE['sides'] = BLOCK_ALL;

	// If it's the homepage show messages and blocks
	if ($_CLASS['core_display']->homepage) 
	{
		$_CLASS['core_display']->display_head();

		// Hey admin we don't have a modules set
		if ($_CLASS['core_auth']->admin_auth('modules'))
		{
			$_CLASS['core_display']->message = _NO_HOMEPAGE_ADMIN;
		}

		$_CLASS['core_display']->display_footer();

	}
	else
	{
		$this->error_setting['header'] = '404';
		trigger_error('_PAGE_NOT_FOUND', E_USER_ERROR);
	}
}

$_CLASS['core_user']->page = $mod;

if (!$_CORE_MODULE['active'])
{
	if (!$_CLASS['core_auth']->admin_auth('modules'))
	{
		$_CORE_MODULE['sides'] = BLOCK_ALL;
		trigger_error('_MODULE_UNACTIVE');
	}
	$_CLASS['core_display']->message = "<b>This Modules Isn't Active</b><br />";
}

if (!$_CLASS['core_display']->homepage && !$_CLASS['core_auth']->auth($_CORE_MODULE['auth']))
{
	trigger_error('Not Auth!');
}

require($path);

?>