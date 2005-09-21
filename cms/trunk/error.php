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

error_reporting(0);

if (extension_loaded('zlib'))
{
	ob_start('ob_gzhandler');
}

define('SITE_FILE_ROOT', str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/');

$lang = 'en';

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
{
	$accept_lang_array = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	foreach ($accept_lang_array as $accept_lang)
	{
		$accept_lang = substr($accept_lang, 0, 2);
		if (file_exists(SITE_FILE_ROOT.'language/' . $accept_lang . '/error.php'))
		{
			$lang = $accept_lang;
			break;
		}
		
		$accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
		if (file_exists(SITE_FILE_ROOT.'language/' . $accept_lang . '/error.php'))
		{
			$lang = $accept_lang;
			break;
		}
	}
}

require(SITE_FILE_ROOT.'language/' . $lang . '/error.php');
require(SITE_FILE_ROOT.'includes/display/template.php');

$_CLASS['core_template'] =& new core_template();

$_SERVER['REDIRECT_STATUS'] = isset($_SERVER['REDIRECT_STATUS']) ? $_SERVER['REDIRECT_STATUS'] : (isset($_GET['error']) ? $_GET['error'] : 404);

header(empty($error[$_SERVER['REDIRECT_STATUS']]['header']) ? $error['404']['header'] : $error[$_SERVER['REDIRECT_STATUS']]['header']);

$_CLASS['core_template']->assign_array(array(
	'MESSAGE_TEXT'	=> empty($error[$_SERVER['REDIRECT_STATUS']]['lang']) ? $error['404']['lang'] : $error[$_SERVER['REDIRECT_STATUS']]['lang'],
	'SITE_LINK'		=> 'http://'.(empty($_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']),
));
	
$_CLASS['core_template']->display('error_document.html');
	
die;

?>