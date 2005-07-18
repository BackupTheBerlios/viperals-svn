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
    die();
}

$_CLASS['core_user']->add_lang();

$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['RECOMEND_US']);

function recommend($sender_name, $sender_email, $receiver_name='', $receiver_email='', $message='', $error = false)
{
	global $_CLASS, $_CORE_MODULE;
  
	$_CLASS['core_template']->assign(array( 
		'L_YOURNAME' 			=> $_CLASS['core_user']->lang['YOURNAME'],
		'L_YOUREMAIL'	 		=> $_CLASS['core_user']->lang['YOUREMAIL'],
		'L_FRIENDNAME' 			=> $_CLASS['core_user']->lang['FRIENDNAME'],
		'L_FRIENDEMAIL'	 		=> $_CLASS['core_user']->lang['FRIENDEMAIL'],
		'L_MESSAGE' 			=> $_CLASS['core_user']->lang['MESSAGE'],
		'L_PREVIEW' 			=> $_CLASS['core_user']->lang['PREVIEW'],
		'L_SUBMIT' 				=> $_CLASS['core_user']->lang['SUBMIT'],
		'IP'					=> $_CLASS['core_user']->ip,
		'ERROR' 				=> $error,
		'MESSAGE' 				=> $message,
		'ACTION' 				=> generate_link($_CORE_MODULE['name']),
		'RECEIVER_EMAIL' 		=> $receiver_email,
		'RECEIVER_NAME' 		=> $receiver_name,
		'SENDER_EMAIL' 			=> $sender_email,
		'SENDER_NAME' 			=> $sender_name,
		)
	);
		
	$_CLASS['core_template']->display('modules/Recommend_Us/index.html');
	
	$_CLASS['core_display']->display_footer();

}

function send_recommend($sender_name, $sender_email, $receiver_name, $receiver_email, $message, $preview)
{
	global $_CLASS, $_CORE_CONFIG;
	
	$mail_message = '<center>Hi '.$receiver_name.' <br /><br /> '.$sender_name.' has recommended you look at this site.'
				.'<br /><br /><a href="'.$_CORE_CONFIG['global']['site_url'].'">'.$_CORE_CONFIG['global']['site_name'].' - '.$_CORE_CONFIG['global']['site_url'].'</a>';
	
	if ($message)
	{
		$message .= '<br /><br /><b>There following message was attached by sender</b><br />'.$message;
	}
	
	$mail_message .= '<br /><br /><br /><center>Message Sent from IP '. $_CLASS['core_user']->ip . '<br />Please report spammers at '. $_CORE_CONFIG['global']['site_url'] .'</center>';


	if ($preview)
	{
		$_CLASS['core_template']->assign('PREVIEW', $mail_message);
		return;
	}
	
	$subject = $_CLASS['core_user']->lang['RECOMMENDATION'] . $sender_name;

	if (send_mail($mailer_message, $mail_message, true, $subject, $receiver_email, $receiver_name, $sender_email, $sender_name))
	{
		trigger_error($_CLASS['core_user']->lang['MESSAGE_SENT']);
		
	} else {
	
		$message = $_CLASS['core_user']->lang['MESSAGE_PROBLEM'];
			
		if ($_CLASS['core_user']->is_admin)
		{
			$message .= '<br /><div align="center"><b>'.$_CLASS['PHPMailer']->ErrorInfo.'</b></div>';
		}
				
		trigger_error($message);
	}
	
	$_CLASS['core_display']->display_footer();
  
}

if (isset($_POST['recommend']) || isset($_POST['preview']))
{

    $data['FNAME'] = get_variable('receiver_name', 'POST', '');
    $data['FEMAIL'] = get_variable('receiver_email', 'POST', '');
    $data['NAME'] = get_variable('sender_name', 'POST', '');
    $data['EMAIL'] = get_variable('sender_email', 'POST', '');
    $message = get_variable('message', 'POST', '');
    $error = '';

	foreach ($data as $field => $value)
	{
		
		if (!$value)
		{
				$error .= $_CLASS['core_user']->lang['ERROR_'.$field].'<br />';
				unset($field, $value, $lang);
        
        } elseif (($field == 'EMAIL' || $field == 'FEMAIL') && !check_email($value)) {
        
			$error .= $_CLASS['core_user']->lang['BAD_EMAIL'].'<br />';
		}
	}
	
	if (isset($_POST['preview']) || !$error)
	{
		send_recommend($data['NAME'], $data['EMAIL'], $data['FNAME'], $data['FEMAIL'], $message, isset($_POST['preview']));
	}
	
	if (isset($_POST['preview']) || $error)
	{
		recommend($data['NAME'], $data['EMAIL'], $data['FNAME'], $data['FEMAIL'], $message, $error);
	}
	
} else {

	if ($_CLASS['core_user']->is_user)
	{
		$sender_name = $_CLASS['core_user']->data['username'];
		$sender_email = $_CLASS['core_user']->data['user_email'];
	} else {
		$sender_email = $sender_name = '';
	}
	
	recommend($sender_name, $sender_email);
}

?>