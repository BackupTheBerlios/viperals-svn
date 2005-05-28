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
error_reporting(E_ALL);
//error_reporting(0);

if (@ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $value)
	{
		unset($$var_name);
	}
	unset($variable, $value);
}

define('STRIP', (get_magic_quotes_gpc()) ? true : false);

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

$phpversion = explode('.', PHP_VERSION);
$phpversion = intval($phpversion[0].$phpversion[1]);

$base_memory_usage = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;

// Maybe add full table ?
$_CORE_MODULE['compatiblity'] = false;

require($site_file_root.'config.php');
require($site_file_root.'includes/tables.php');
require($site_file_root.'includes/handler.php');
require($site_file_root.'includes/smarty/Smarty.class.php');
require($site_file_root.'includes/functions.php');
require($site_file_root.'includes/cache/cache_' . $acm_type . '.php');
require($site_file_root.'includes/db/'.$sitedb['dbtype'].'.php');
require($site_file_root.'includes/session.php');

$_CLASS['core_error_handler'] =& new core_error_handler;
$_CLASS['core_error_handler']->start();
//$_CLASS['core_error_handler']->stop();

$mod = get_variable('mod', 'REQUEST', false);
$file = get_variable('file', 'REQUEST', 'index');

$_CLASS['core_template'] =& new Smarty();
$_CLASS['core_db'] =& new sql_db();
$_CLASS['core_db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);

unset($sitedb);

$_CLASS['core_cache'] =& new cache();

if (VIPERAL == 'MINILOAD')
{
// don't do sessions on miniload
// rename to FEED
	if (maintance_status(true) || load_status(true))
	{
		header("HTTP/1.0 503 Service Unavailable");
		die;
	}
	return;
}

$_CLASS['core_user'] =& new user();

$_CLASS['core_user']->startup();
$_CLASS['core_user']->start();
$db	=& $_CLASS['core_db'];

if (is_admin() && $_CORE_CONFIG['global']['error'])
{
	$_CLASS['core_error_handler']->report = $_CORE_CONFIG['global']['error'];	
}
else
{
	$_CORE_CONFIG['global']['error'] = ERROR_NONE;
	$_CLASS['core_error_handler']->report = ERROR_NONE;
}

// testing remove when commenting
$_CORE_CONFIG['global']['error'] = true;
$_CLASS['core_error_handler']->report = ERROR_DEBUGGER;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_CLASS['core_user']->new_session)
{
	// error here
}

require_once($site_file_root.'includes/display/banners.php');
require_once($site_file_root.'includes/display/blocks.php');
require_once($site_file_root.'includes/display/display.php');

loadclass(false, 'core_display');
loadclass(false, 'core_blocks');

If ($_CORE_CONFIG['server']['optimize_rate'] && ($_CORE_CONFIG['server']['optimize_last'] + $_CORE_CONFIG['server']['optimize_rate']) < time())
{
	optimize_table();
	//optimize_cache();
}


// This seriously has to go, what you can make yout own or something.
// lol talking to myself again 0_0 ...
function send_mail(&$mailer_message, $message, $html='', $subject='', $to='', $to_name='', $from='', $from_name='' )
{
	global $_CORE_CONFIG, $_CLASS, $phpEx;

	loadclass($site_file_root.'includes/mailer/class.phpmailer.php', 'PHPMailer');
	
	ini_set('sendmail_from', ($from) ? $from : $_CORE_CONFIG['global']['admin_mail']);

	if ($_CORE_CONFIG['email']['smtp_on'])
	{
		$_CLASS['PHPMailer']->IsSMTP();
		$_CLASS['PHPMailer']->Host = $_CORE_CONFIG['email']['smtphost'];
		
		if ($_CORE_CONFIG['email']['smtp_auth'])
		{
			$_CLASS['PHPMailer']->SMTPAuth = true;
			$_CLASS['PHPMailer']->Username = $_CORE_CONFIG['email']['smtp_uname'];
			$_CLASS['PHPMailer']->Password = $_CORE_CONFIG['email']['smtp_pass'];
		}
	}
	
	$_CLASS['PHPMailer']->From = ($from) ? trim_text($from) : $_CORE_CONFIG['global']['admin_mail'];;
	$_CLASS['PHPMailer']->FromName = ($from_name) ? trim_text($from_name) : $_CORE_CONFIG['global']['sitename'];
	$_CLASS['PHPMailer']->AddAddress(($to) ? trim_text($to) : $_CORE_CONFIG['global']['admin_mail'], $to_name);
	//$_CLASS['PHPMailer']->AddReplyTo($from, $from_name);
	$AltBody = strip_tags($message);
	
	$_CLASS['PHPMailer']->Subject = strip_tags(trim_text($subject));
	$_CLASS['PHPMailer']->IsHTML(($_CORE_CONFIG['email']['allow_html_email']) ? true : false);
	$_CLASS['PHPMailer']->Body	= ($_CORE_CONFIG['email']['allow_html_email']) ? $message : $AltBody;

	
	$_CLASS['PHPMailer']->AltBody = $AltBody;
	
	return $_CLASS['PHPMailer']->Send();
}

?>