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

if (extension_loaded('zlib'))
{
	ob_start('ob_gzhandler');
}

//$site_file_root = getenv('DOCUMENT_ROOT').'/';
$site_file_root = 'C:/Program Files/Apache Group/Apache2/cms/';

if (isset($_GET['error']) && is_numeric($_GET['error']))
{
	error_reporting(0);
	
	require($site_file_root.'language/error.php');
	require($site_file_root.'includes/smarty/Smarty.class.php');
	
	$_CLASS['template'] =& new Smarty();
	
	$_CLASS['template']->assign('MESSAGE_TEXT',  (empty($error[$_GET['error']]) ? $error['404'] : $error[$_GET['error']]));
			
	$_CLASS['template']->display('error.html');
	
}

die;

?>