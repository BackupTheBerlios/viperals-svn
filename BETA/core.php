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

if (extension_loaded('zlib'))
{
	ob_start('ob_gzhandler');
}

if (!defined('VIPERAL'))
{
    if (isset($_GET['error']) && is_numeric($_GET['error']))
    {
		error_reporting(0);
		
		require('language/error.php');
		require('includes/smarty/Smarty.class.php');
		
		$_CLASS['template'] =& new Smarty();
		
		$_CLASS['template']->assign('MESSAGE_TEXT',  (empty($error[$_GET['error']]) ? $error['404'] : $error[$_GET['error']]));
				
		$_CLASS['template']->display('error.html');
		
	} else {
	
		header('location: /');
    }
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
$_CLASS['error']->start(3);

$name = get_variable('name', 'REQUEST', false);
$file = get_variable('file', 'REQUEST', 'index');

$_CLASS['template'] =& new Smarty();
$_CLASS['db'] =& new sql_db();
$_CLASS['db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);

$_CLASS['user']	=& new user();
$_CLASS['cache'] =& new cache();

$_CLASS['user']->startup();
$_CLASS['user']->start();
$db	=& $_CLASS['db'];

if (is_admin() && $MAIN_CFG['global']['error'] && VIPERAL != 'MINILOAD')
{
	//$_CLASS['error']->report = $MAIN_CFG['global']['error'];
	
} else {

	//$MAIN_CFG['global']['error'] = 0;
}

if ($MAIN_CFG['global']['maintenance'] && !is_admin() && VIPERAL != 'Admin')
{
	// Lets not give the maintance text with minload ( for ex. feed.php )
	if (VIPERAL != 'MINILOAD')
	{
		die;
	}
	
	trigger_error($MAIN_CFG['global']['maintenance_text'], E_USER_ERROR);
}

if (VIPERAL == 'MINILOAD')
{
	return;
}

// && $name != 'Gallery' quick fix for the gallery, it needed for the javascripts...
// add language codes, move to maybe sessions.  Add a kill post code and allow script to continue.
// Or remove totally.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_CLASS['user']->new_session && $name != 'Gallery')
{
	$user = (is_user()) ? $_CLASS['user']->data['user_id'] : '';
	$error = '<div align="center"><b>There was suspectious POST on this site<br/>These are the information collected<br />'
		.'<ul><br><li>IP address - '.$_CLASS['user']->ip.'.</li><br><li>User id - '.$user.'</li><br><li>Page - http://'.$_CLASS['display']->siteurl.$_SERVER['REQUEST_URI'].'</li><br><li>Referrer - '.$_SERVER['HTTP_REFERER'].'</li></ul>'
		.'<br/><br/>The following data was sent - Please review<br/>'.$_CLASS['cache']->format_array($_POST).'</div>';
	
	$subject = 'Suspectious POST sent to site '.$MAIN_CFG['global']['sitename'];

	if (!send_mail($mailer_message, $error, 1, $subject) && is_admin())
	{
		echo $_CLASS['PHPMailer']->ErrorInfo;
	}
	
	trigger_error('<div align="center"><b>Man i need to fix this up lol<br /> Posting from another server not allowed dud!</b></div>', E_USER_ERROR);
}

require($site_file_root.'includes/display/display.php');
require($site_file_root.'includes/display/blocks.php');
require($site_file_root.'includes/display/banners.php');

$_CLASS['editor'] = false;
$_CLASS['blocks'] =& new blocks();
$_CLASS['display'] =& new display();

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