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

error_reporting(0);

if (extension_loaded('zlib'))
{
	ob_start('ob_gzhandler');
}

//echo str_replace('\\','/', getenv('DOCUMENT_ROOT')); die;
$site_file_root = '';
$lang = 'en';

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
{
	$accept_lang_array = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	foreach ($accept_lang_array as $accept_lang)
	{
		$accept_lang = substr($accept_lang, 0, 2);
		if (file_exists($site_file_root.'language/' . $accept_lang . '/error.php'))
		{
			$lang = $accept_lang;
			break;
		}
		
		$accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
		if (file_exists($site_file_root.'language/' . $accept_lang . '/error.php'))
		{
			$lang = $accept_lang;
			break;
		}
	}
}

require($site_file_root.'language/' . $lang . '/error.php');
require($site_file_root.'includes/display/template.php');

$_CLASS['core_template'] =& new core_template();

header(empty($error[$_GET['error']]['header']) ? $error['404']['header'] : $error[$_GET['error']]['header']);

$_CLASS['core_template']->assign('MESSAGE_TEXT',  (empty($error[$_GET['error']]['lang']) ? $error['404']['lang'] : $error[$_GET['error']]['lang']));
		
$_CLASS['core_template']->display('error.html');
	
die;

?>