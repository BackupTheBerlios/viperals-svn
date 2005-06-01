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

//echo str_replace('\\','/', getenv('DOCUMENT_ROOT')); die;
$site_file_root = 'C:/apachefriends/xampp/cms/';

require($site_file_root.'core.php');

if (!$mod)
{
	$_CLASS['core_display']->homepage = true;
	
} else {

	$path = "modules/$mod/".(($file) ? $file : 'index').'.php';
}

switch ($mod)
{
	case 'redirect':
		// Fix banners, make into a dam class.
		if ($bid = get_variable('bid', 'GET', false, 'integer'))
		{
			clickbanner($bid);
			die;
    	}
    	
    	url_redirect();
    	break;
}

if ($_CLASS['core_display']->homepage)
{
	$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.CORE_MODULES_TABLE.' WHERE homepage > 0 ORDER BY homepage ASC');
	
	While ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$_CLASS['core_display']->add_module($row);
	}
	
	$_CORE_MODULE = $_CLASS['core_display']->get_module();
	$path = 'modules/'.$_CORE_MODULE['name'].'/index.php';

} else {

	$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.CORE_MODULES_TABLE." WHERE name='".$_CLASS['core_db']->sql_escape($mod)."'");
	$_CORE_MODULE = $_CLASS['core_db']->sql_fetchrow($result);

}

$_CLASS['core_db']->sql_freeresult($result);
$path = $site_file_root.$path;

if (!$_CORE_MODULE || !file_exists($path))
{
	$_CORE_MODULE['sides'] = BLOCK_ALL;

	if ($_CLASS['core_display']->homepage) 
	{
		if ($_CLASS['core_user']->admin_auth('modules'))
		{
			// display message inline
			trigger_error('_NO_HOMEPAGE_ADMIN', E_USER_NOTICE);
		} else {
			// Maybe someone wants only messages and/or blocks !
			$_CLASS['core_display']->display_head();
			$_CLASS['core_display']->display_footer();
		}
	} else {
		// Uncomment below for an embedded error
		// $_CLASS['core_display']->display_head();
		trigger_error('_PAGE_NOT_FOUND', E_USER_ERROR);
	}
}

if (!$_CORE_MODULE['active'])
{
	$_CORE_MODULE['sides'] = BLOCK_ALL;
	trigger_error('_MODULE_UNACTIVE');
}

//Need to add a way off getting a text auth message for ( only when there is one group auth or registered user )
//if (!$_CLASS['core_display']->homepage && !$_CLASS['core_auth']->auth($_CORE_MODULE['auth']))
//{
//	trigger_error('Not Auth!');
//}

/*
if ($_CORE_MODULE['editor'] && $MAIN_CFG['global']['wysiwyg'] && $_CLASS['core_user']->data['wysiwyg'])
{
	loadclass($site_file_root.'includes/core_editor.php');
	$_CLASS['core_editor']->setup($_CORE_MODULE['editor'], $_CORE_MODULE['editortype']);
}*/

//loadclass($site_file_root.'includes/core_editor.php', 'core_editor');
//$_CLASS['core_editor']->setup();
	
if ($_CORE_MODULE['compatiblity'])
{
	require($site_file_root.'includes/compatiblity/index.php');
}

require($path);

?>