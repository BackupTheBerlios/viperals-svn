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

error_reporting(0);

if (extension_loaded('zlib'))
{
	ob_start('ob_gzhandler');
}

//$site_file_root = getenv('DOCUMENT_ROOT').'/';
$site_file_root = 'C:/Program Files/Apache Group/Apache2/cms/';
$lang = 'en';

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
{
	$accept_lang_array = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	foreach ($accept_lang_array as $accept_lang)
	{
		$accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
		if (file_exists($site_file_root.'language/' . $accept_lang . '/error.php'))
		{
			$lang = $accept_lang;
			break;
		}
		
		$accept_lang = substr($accept_lang, 0, 2);
		if (file_exists($site_file_root.'language/' . $accept_lang . '/error.php'))
		{
			$lang = $accept_lang;
			break;
		}
	}
}

require($site_file_root.'language/' . $lang . '/error.php');
require($site_file_root.'includes/smarty/Smarty.class.php');

$_CLASS['template'] =& new Smarty();

$_CLASS['template']->assign('MESSAGE_TEXT',  (empty($error[$_GET['error']]) ? $error['404'] : $error[$_GET['error']]));
		
$_CLASS['template']->display('error.html');
	
die;

?>