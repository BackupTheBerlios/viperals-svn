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

if (!defined('VIPERAL'))
{
	header('location: /');
    die;
}

set_magic_quotes_runtime(0);
//error_reporting(E_ALL);
error_reporting(0);

define('STRIP', (get_magic_quotes_gpc()) ? true : false);

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

$base_memory_usage = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;

require($site_file_root.'config.php');
require($site_file_root.'tables.php');
require($site_file_root.'includes/handler.php');
require($site_file_root.'includes/smarty/Smarty.class.php');
require($site_file_root.'includes/functions.php');
require($site_file_root.'includes/cache/cache_' . $acm_type . '.'.$phpEx);
require($site_file_root.'includes/db/'.$sitedb['dbtype'].'.'.$phpEx);
require($site_file_root.'includes/session.'.$phpEx);

$_CLASS['error'] =& new core_error_handler;
$_CLASS['error']->start();

$mod = get_variable('mod', 'REQUEST', false);
$file = get_variable('file', 'REQUEST', 'index');

$_CLASS['template'] =& new Smarty();
$_CLASS['db'] =& new sql_db();
$_CLASS['db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);

if (VIPERAL == 'MINILOAD')
{
	return;
}

$_CLASS['user']	=& new user();
$_CLASS['cache'] =& new cache();

$_CLASS['user']->startup();
$_CLASS['user']->start();
$db	=& $_CLASS['db'];

if (is_admin() && $MAIN_CFG['global']['error'])
{
	$_CLASS['error']->report = $MAIN_CFG['global']['error'];
	
} else {

	$MAIN_CFG['global']['error'] = ERROR_NONE;
}

if ($MAIN_CFG['global']['maintenance'] && !is_admin() && VIPERAL != 'Admin')
{
	trigger_error($MAIN_CFG['global']['maintenance_text'], E_USER_ERROR);
}


// && $name != 'Gallery' quick fix for the gallery, it needed for the javascripts...
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_CLASS['user']->new_session && $mod != 'Gallery')
{
	$error = '<div align="center"><b>Someone with IP '.$_CLASS['user']->ip.'<br />'
			.'tried to send information thru POST <br />from another location or site with the following url: '.$_SERVER['HTTP_REFERER'].'<br />'
			.'to the following page: http://'.getenv('HTTP_HOST').$_SERVER['REQUEST_URI'].'<br /><br /></div>';
	
	$subject = 'Off site POST at '.$MAIN_CFG['global']['sitename'];

	if (!send_mail($mailer_message, $error, 1, $subject) && is_admin())
	{
		echo $_CLASS['PHPMailer']->ErrorInfo;
	}
	
	trigger_error('<div align="center"><b>Man i need to fix this up lol<br /> Posting from another server not allowed dud!</b></div>', E_USER_ERROR);
}

require_once($site_file_root.'includes/display/banners.php');
require_once($site_file_root.'includes/display/blocks.php');
require_once($site_file_root.'includes/display/display.php');

loadclass(false, 'display');
loadclass(false, 'blocks');

If ($MAIN_CFG['server']['optimize_rate'] && ($MAIN_CFG['server']['optimize_last'] + $MAIN_CFG['server']['optimize_rate']) < time())
{
	optimize_table();
	//optimize_cache();
}


// This seriously has to go, what you can make yout own or something.
// lol talking to myself again 0_0 ...
function send_mail(&$mailer_message, $message, $html='', $subject='', $to='', $to_name='', $from='', $from_name='' )
{
	global $MAIN_CFG, $_CLASS, $phpEx;

	loadclass($site_file_root.'includes/mailer/class.phpmailer.php', 'PHPMailer');
	
	ini_set('sendmail_from', ($from) ? $from : $MAIN_CFG['global']['admin_mail']);

	if ($MAIN_CFG['email']['smtp_on'])
	{
		$_CLASS['PHPMailer']->IsSMTP();
		$_CLASS['PHPMailer']->Host = $MAIN_CFG['email']['smtphost'];
		
		if ($MAIN_CFG['email']['smtp_auth'])
		{
			$_CLASS['PHPMailer']->SMTPAuth = true;
			$_CLASS['PHPMailer']->Username = $MAIN_CFG['email']['smtp_uname'];
			$_CLASS['PHPMailer']->Password = $MAIN_CFG['email']['smtp_pass'];
		}
	}
	
	$_CLASS['PHPMailer']->From = ($from) ? trim_text($from) : $MAIN_CFG['global']['admin_mail'];;
	$_CLASS['PHPMailer']->FromName = ($from_name) ? trim_text($from_name) : $MAIN_CFG['global']['sitename'];
	$_CLASS['PHPMailer']->AddAddress(($to) ? trim_text($to) : $MAIN_CFG['global']['admin_mail'], $to_name);
	//$_CLASS['PHPMailer']->AddReplyTo($from, $from_name);
	$AltBody = strip_tags($message);
	
	$_CLASS['PHPMailer']->Subject = strip_tags(trim_text($subject));
	$_CLASS['PHPMailer']->IsHTML(($MAIN_CFG['email']['allow_html_email']) ? true : false);
	$_CLASS['PHPMailer']->Body	= ($MAIN_CFG['email']['allow_html_email']) ? $message : $AltBody;

	
	$_CLASS['PHPMailer']->AltBody = $AltBody;
	
	return $_CLASS['PHPMailer']->Send();
}

?>