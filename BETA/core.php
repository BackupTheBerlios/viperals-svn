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

if (!defined('VIPERAL')) {
    header('location: /');
    die;
}

set_magic_quotes_runtime(0);

if (VIPERAL != 'MINILOAD')
{
	$phperror = '';
	$MAIN_CFG['global']['error'] = 3;

	error_reporting(0);
	set_error_handler('error_handler');
}

$phpver = explode('.', phpversion());
$phpver = "$phpver[0]$phpver[1]";

if (extension_loaded('zlib')) {
	ob_start('ob_gzhandler');
}

define('STRIP', (get_magic_quotes_gpc()) ? true : false);

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

$base_memory_usage = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;

require('config.php');
require('tables.php');
require('includes/smarty/Smarty.class.php');
require('includes/functions.php');
require('includes/cache/cache_' . $acm_type . '.'.$phpEx);
require('includes/db/connect.'.$phpEx);
require('includes/session.'.$phpEx);

$name = get_variable('name', 'GET', false);
$file = get_variable('file', 'GET', 'index');
$newlang = get_variable('newlang', 'GET', false);

$_CLASS['user']	=& new user();
$_CLASS['cache'] =& new cache();

$_CLASS['user']->startup();
$_CLASS['user']->start();

$_CLASS['template'] =& new Smarty();

if (VIPERAL == 'MINILOAD')  { return; }

// clean me up you can do better than this
if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_CLASS['user']->new_session ||
(isset($_SERVER['HTTP_REFERER']) && !ereg("(http://$_SERVER[HTTP_HOST])", $_SERVER['HTTP_REFERER']) && !ereg("(https://$_SERVER[HTTP_HOST])", $_SERVER['HTTP_REFERER'])&& !ereg("(http://www.$_SERVER[HTTP_HOST])", $_SERVER['HTTP_REFERER'])))) 
{
	$error = '<div align="center"><b>Someone with IP '.$_SERVER['REMOTE_ADDR'].'<br />'
			.'tried to send information thru POST <br />from another location or site with the following url: '.$_SERVER['HTTP_REFERER'].'<br />'
			.'to the following page: http://'.getenv('HTTP_HOST').$_SERVER['REQUEST_URI'].'<br /><br /></div>';
	
	if (is_admin()) {
	
		echo $error;
		$subject = 'Off site POST at '.$MAIN_CFG['global']['sitename'];
		if (!send_mail($mailer_message, $error, 1, $subject) && is_admin()) {
			echo $_CLASS['PHPMailer']->ErrorInfo;
		}
	}
	
	echo '<div align="center"><b>Man i need to fix this up lol<br /> Posting from another server not allowed dud!</b></div>';
	die();	
}

require('includes/display/display.php');
require('includes/display/blocks.php');
require('includes/display/banners.php');

$_CLASS['editor'] = false;
$_CLASS['blocks'] =& new blocks();
$_CLASS['display'] =& new display();


/*If ($MAIN_CFG['server']['nextoptimize'] < time())
{
optimize_table();
optimize_cache();
}*/

// Remove me, please remove me, add a fintion check
if (($_SERVER['REQUEST_METHOD'] == 'POST') && $file != 'posting' && $name != 'Forums' && $name != 'Control_Panel') {
	
	foreach ($_POST as $secvalue) {
 
		if (eregi("<[^>]*script *\"?[^>]*>", $secvalue))
		{
		   die_error("<b>The html tags you attempted to use are not allowed</b>", "Security ERROR");
		}
		unset($secvalue);
	}
}

if ($MAIN_CFG['global']['maintenance'] && !is_admin() && VIPERAL != 'Admin') {
	die_error('<b>'.$MAIN_CFG['global']['maintenance_text'].'</b>', 'Maintenance');
}

//doesn't save on fatal errors, add code to continue code exicution so it can save,
function error_handler($errno, $msg_text, $errfile, $errline)
{
	global $_CLASS, $phperror, $config, $show_prev_info, $MAIN_CFG;
	
	if (!$MAIN_CFG['global']['error'] && ($errno != (E_USER_ERROR || E_USER_NOTICE)))
	{
		return;
	}
	
	// Dam Windows
	$errfile = ereg_replace("[\]",'/', $errfile);
	// Lets not show site full path
	$errfile = htmlentities(ereg_replace($_SERVER['DOCUMENT_ROOT'],'', $errfile), ENT_QUOTES);
	
	switch ($errno)
	{
		case E_NOTICE:
			if ($MAIN_CFG['global']['error'] == 1)
			{
				if (!empty($_CLASS['display']) && $_CLASS['display']->displayed['header'])
				{
					$error = "<table><tr><th>PHP Notice:</th></tr><tr><td> in file <b>$errfile</b> on line <b>$errline</b>: <b>$msg_text</b><br/></td></tr></table>";
					echo '<div onmouseover="return overlib(\''.tool_tip_text($error)."', FGCOLOR, '#FFFFFF');\" onmouseout=\"return nd();\"><font color=\"#990000\">File Error</font></div>\n";
				} else {
					echo "PHP Notice: in file <b>$errfile</b> on line <b>$errline</b>: <b>$msg_text</b><br/>\n";
				}
			} else {
				$phperror['E_NOTICE'][] = array('errfile'	=> $errfile,'errline'	=> $errline, 'msg_text' => $msg_text);
			}
			break;
			
		case E_WARNING:
			if ($MAIN_CFG['global']['error'] == 1)
			{
				if (!empty($_CLASS['display']) && $_CLASS['display']->displayed['header'])
				{
					$error = "<table><tr><th>PHP WARNING:</th></tr><tr><td> in file <b>$errfile</b> on line <b>$errline</b>: <b>$msg_text</b><br/></td></tr></table>";
					echo '<div onmouseover="return overlib(\''.tool_tip_text($error)."', FGCOLOR, '#FFFFFF');\" onmouseout=\"return nd();\"><font color=\"#990000\">File Error</font></div>\n";
				} else {
					echo "PHP WARNING: in file <b>$errfile</b> on line <b>$errline</b>: <b>$msg_text</b><br/>\n";
				}
			} else {
				$phperror['E_WARNING'][] = array('errfile'	=> $errfile,'errline'	=> $errline, 'msg_text' => $msg_text);
			}
			break;

		case E_USER_ERROR:
			
			if (ereg('SQL', $msg_text)) {

				if (function_exists('OpenTable')) {
					
					OpenTable();
				
					echo '<h2 align="center">SQL Error</h2>';
					echo '<br clear="all" /><table width="85%" cellspacing="0" cellpadding="0" border="0" align="center"><tr><td><br clear="all" />' . $msg_text . '<hr />Please notify the board administrator or webmaster : <a href="mailto:' . $config['board_contact'] . '">' . $config['board_contact'] . '</a></td></tr></table><br clear="all" /></body></html>';
					
					CloseTable();
				
				}

			} else {
			
				require('header.php');
					OpenTable();
					echo '<h2 align="center">Error</h2>';
					echo '<br clear="all" /><table width="85%" cellspacing="0" cellpadding="0" border="0" align="center"><tr><td><br clear="all" />' . $msg_text . '<hr />Please notify the board administrator or webmaster : <a href="mailto:' . $config['board_contact'] . '">' . $config['board_contact'] . '</a></td></tr></table><br clear="all" /></body></html>';
					CloseTable();
					script_close();

				require('footer.php');
			}
			
			break;

		case E_USER_NOTICE:

			require('header.php');
			// this is phpbb 2.1.2 remove it
			$msg_text = (!empty($_CLASS['user']->lang[$msg_text])) ? $_CLASS['user']->lang[$msg_text] : $msg_text;
			$msg_title = (!isset($msg_title)) ? $_CLASS['user']->lang['INFORMATION'] : ((!empty($_CLASS['user']->lang[$msg_title])) ? $_CLASS['user']->lang[$msg_title] : $msg_title);
			$show_prev_info = (!isset($show_prev_info)) ? true : (bool) $show_prev_info;

			if (defined('IN_ADMIN') && !empty($user->data['session_admin']))
			{
				// this is phpbb 2.1.2 remove it
				adm_page_message($msg_title, $msg_text, false, $show_prev_info);
				adm_page_footer();
			}
			else
			{

				$_CLASS['template']->assign(array(
					'MESSAGE_TITLE'	=> (isset($msg_title)) ? $msg_title : $_CLASS['user']->lang['INFORMATION'],
					'MESSAGE_TEXT'	=> $msg_text)
				);
				
				$_CLASS['template']->display('forums/message_body.html');

				require('footer.php');
			}

			break;
	}
}

function send_mail(&$mailer_message, $message, $html='', $subject='', $to='', $to_name='', $from='', $from_name='' )
{
	global $MAIN_CFG, $_CLASS, $phpEx;

	loadclass('includes/mailer/class.phpmailer.'.$phpEx, 'PHPMailer');
	
	if ($MAIN_CFG['email']['smtp_on']) {
	
		$_CLASS['PHPMailer']->IsSMTP();
		$_CLASS['PHPMailer']->Host = $MAIN_CFG['email']['smtphost'];
		
		if ($MAIN_CFG['email']['smtp_auth']) {
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

function is_admin() {
    global $_CLASS;
    return ($_CLASS['user']->data['session_admin']) ? true : false;
}

function is_user() {
    global $_CLASS;
    return ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? true : false;
}

?>