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
	$result = $_CLASS['db']->sql_query('SELECT * FROM '.$prefix.'_modules WHERE homepage > 0 ORDER BY homepage ASC');
	
	While ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$_CLASS['display']->add_module($row);
	}
	
	$Module = $_CLASS['display']->get_module();
	$path = 'modules/'.$Module['name'].'/index.php';

} else {

	$result = $_CLASS['db']->sql_query_limit('SELECT * FROM '.$prefix."_modules WHERE name='".$_CLASS['db']->sql_escape($mod)."'", 1);
	$Module = $_CLASS['db']->sql_fetchrow($result);

}

$_CLASS['db']->sql_freeresult($result);
$path = $site_file_root.$path;

if (!$Module || !file_exists($path))
{
	$Module['sides'] = BLOCK_ALL;

	if ($_CLASS['display']->homepage) 
	{
		if ($_CLASS['user']->admin_auth('modules'))
		{
			// display message inline
			trigger_error('_NO_HOMEPAGE_ADMIN');
		} else {
			// Maybe someone wants only messages or blocks !. 
			$_CLASS['display']->display_head();
			$_CLASS['display']->display_footer();
		}
	} else {
		trigger_error('_PAGE_NOT_FOUND');
	}
}

if (!$Module['active'])
{
	$Module['sides'] = BLOCK_ALL;
	trigger_error('_MODULE_UNACTIVE');
}

//Need to add a way off getting a text auth message for ( only when there is one group auth or registered user )
if (!$_CLASS['display']->homepage && !$_CLASS['user']->auth($Module['auth']))
{
	trigger_error('Not Auth!');
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