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

if (!$mod)
{
	$_CLASS['display']->homepage = true;
	
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

if ($_CLASS['display']->homepage)
{
	$result = $_CLASS['db']->sql_query('SELECT * FROM '.$prefix.'_modules WHERE homepage <> 0 ORDER BY homepage ASC');
	
	While ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		//send array to display class to be checked
		//yes it will allow for module per group, bla bla bla
		$_CLASS['display']->add_module($row);
	}
	
	$Module = $_CLASS['display']->modules[0];
	unset($_CLASS['display']->modules[0]);

	$path = 'modules/'.$Module['name'].'/index.php';

} else {

	$result = $_CLASS['db']->sql_query_limit('SELECT * FROM '.$prefix."_modules WHERE name='".$_CLASS['db']->sql_escape($mod)."'", 1);
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