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

// Add departments

if (!defined('VIPERAL'))
{
    die;
}

$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_lang();

$error = '';

if (!empty($_POST['preview']) || !empty($_POST['contact']))
{
	$data['MESSAGE']= trim(get_variable('message', 'POST', ''));
	$data['NAME']	= get_variable('sender_name', 'POST', '');
	$data['EMAIL']	= get_variable('sender_email', 'POST', '');

	foreach ($data as $field => $value)
	{
		if (!$value)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_'.$field].'<br />';
			unset($field, $value, $lang);
        }
        elseif ($field == 'EMAIL' && !check_email($value))
        {
			$error .= $_CLASS['core_user']->lang['BAD_EMAIL'].'<br />';
		}
	} 

	if (!empty($_POST['preview']) && $data['MESSAGE'])
	{
		send_feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $preview = true);
	}
	elseif (!empty($_POST['contact']) && !$error)
	{
		send_feedback($data['NAME'], $data['EMAIL'], $data['MESSAGE']);
	}

	$sender_name = $data['NAME'];
	$sender_email = $data['EMAIL'];
	$message = $data['MESSAGE'];
}
else
{
	$sender_name = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['username'] : '';
	$sender_email = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['user_email'] : '';
	$message = '';
}

$_CLASS['core_template']->assign_array(array( 
	'ERROR' 				=> $error,
	'MESSAGE' 				=> $message,
	'ACTION' 				=> generate_link($_CORE_MODULE['module_name']),
	'SENDER_EMAIL' 			=> $sender_email,
	'SENDER_NAME' 			=> $sender_name,
));

$_CLASS['core_template']->display('modules/Contact/index.html');

// remove this function
function send_feedback($sender_name, $sender_email, $message, $preview = false)
{
	global $_CLASS, $_CORE_CONFIG;

	$_CLASS['core_template']->assign_array(array(
		'SENT_FROM'		=> $sender_name,
		'SENDER_NAME'	=> $sender_name,
		'SENDER_EMAIL'	=> $sender_email,
		'SENDER_IP'		=> $_CLASS['core_user']->ip,
		'MESSAGE' 		=> $message,
	));

	$body = trim($_CLASS['core_template']->display('modules/Contact/email/index.html', true));

	if ($preview)
	{
		$_CLASS['core_template']->assign('PREVIEW', $body);

		return;
	}

	require_once($site_file_root.'includes/mailer.php');

	$mailer = new core_mailer;
	$mailer->to($_CORE_CONFIG['email']['site_mail'], false);
	$mailer->subject($_CLASS['core_user']->get_lang('SITE_FEEDBACK'));

	$mailer->message = $body;

	trigger_error($mailer->send() ? 'SEND_SUCCESSFULL' : $mailer->error);
}
?>