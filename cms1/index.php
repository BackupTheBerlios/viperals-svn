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
define('VIPERAL', 'CMS');

//$site_file_root = getenv('DOCUMENT_ROOT').'/';
$site_file_root = 'C:/Program Files/Apache Group/Apache2/cms/';
require($site_file_root.'core.php');

if (!$name)
{
	$_CLASS['display']->homepage = true;
	
} else {

	$path = "modules/$name/".(($file) ? $file : 'index').'.php';
}

switch ($name)
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

if ($_CLASS['display']->homepage)
{
	// Perparing for multihomepage modules
	// Extract all homepage modules, make into an array, send to $_CLASS['display']
	$result = $_CLASS['db']->sql_query('SELECT * FROM '.$prefix.'_modules WHERE homepage = 1');
	$Module = $_CLASS['db']->sql_fetchrow($result);
	$path = 'modules/'.$Module['name'].'/index.php';

} else {

	$result = $_CLASS['db']->sql_query_limit('SELECT * FROM '.$prefix."_modules WHERE name='".$_CLASS['db']->sql_escape($name)."'", 1);
	$Module = $_CLASS['db']->sql_fetchrow($result);

}

$_CLASS['db']->sql_freeresult($result);
$path = $site_file_root.$path;

if (!$Module || !file_exists($path))
{
	if ($_CLASS['display']->homepage) 
	{
		if (is_admin())
		{
			trigger_error('_NO_HOMEPAGE_ADMIN', E_USER_ERROR);
		} else {
			trigger_error('_NO_HOMEPAGE', E_USER_ERROR);
		}
		
	} else {
	
		$Module['sides'] = 1;
		trigger_error('_PAGE_NOT_FOUND');
	}
}

if (!$Module['active'])
{
	$Module['sides'] = 1;
	trigger_error('_MODULE_UNACTIVE');
}

if ($Module['editor'] && $MAIN_CFG['global']['wysiwyg'] && $_CLASS['user']->data['wysiwyg'])
{
	loadclass($site_file_root.'includes/editor.php');
	$_CLASS['editor']->setup($Module['editor'], $Module['editortype']);
}

if ($Module['compatiblity'])
{
	require($site_file_root.'includes/compatiblity/index.php');
}

require($path);

?>