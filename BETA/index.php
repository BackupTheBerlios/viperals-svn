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

require('core.php');

if (!$name) {
	
	$name = $MAIN_CFG['global']['main_module'];
	
	$path = "modules/$name/index.php";

	$_CLASS['display']->homepage = true;
	
} else {

	$path = "modules/$name/".(($file) ? $file : 'index').'.php';
	
}

switch ($name)
{
	case 'redirect':

		if ($bid = get_variable('bid', 'GET', false, 'integer')) {
			clickbanner($bid);
			die;
    	}
    	url_redirect();
    	break;
}

$result = $_CLASS['db']->sql_query_limit('SELECT * FROM '.$prefix."_modules WHERE title='".$_CLASS['db']->sql_escape($name)."'", 1);

if (!($Module = $_CLASS['db']->sql_fetchrow($result)) || !file_exists($path)) {
	
	$_CLASS['db']->sql_freeresult($result);

	if (!is_admin() && $_CLASS['display']->homepage) 
	{
		url_redirect();

	} else {

		$Module['sides'] = BLOCK_ALL;
		require('header.php');
		OpenTable();
		echo '<div align="center">Sorry, the request module or feature doesn\'t exist...</div>';
		CloseTable();
		require('footer.php');
		
	}
}

$_CLASS['db']->sql_freeresult($result);

if ($Module['editor'] && $MAIN_CFG['global']['wysiwyg'])
{
	require('includes/editor.php');
	$_CLASS['editor'] =& new editor();
	$_CLASS['editor']->setup($Module['editor'], $Module['editortype']);
}

require($path);

?>