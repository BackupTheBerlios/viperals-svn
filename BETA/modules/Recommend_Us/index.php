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
    header('location: ../../');
    die();
}

$_CLASS['user']->add_lang();

$_CLASS['display']->display_head($_CLASS['user']->lang['RECOMEND_US']);

function recommend($sender_name, $sender_email, $receiver_name='', $receiver_email='', $message='', $error = false)
{
	global $_CLASS, $Module;
  
	$_CLASS['template']->assign(array( 
		'L_YOURNAME' 			=> $_CLASS['user']->lang['YOURNAME'],
		'L_YOUREMAIL'	 		=> $_CLASS['user']->lang['YOUREMAIL'],
		'L_FRIENDNAME' 			=> $_CLASS['user']->lang['FRIENDNAME'],
		'L_FRIENDEMAIL'	 		=> $_CLASS['user']->lang['FRIENDEMAIL'],
		'L_MESSAGE' 			=> $_CLASS['user']->lang['MESSAGE'],
		'L_PREVIEW' 			=> $_CLASS['user']->lang['PREVIEW'],
		'L_SUBMIT' 				=> $_CLASS['user']->lang['SUBMIT'],
		'IP'					=> $_CLASS['user']->ip,
		'ERROR' 				=> $error,
		'MESSAGE' 				=> $message,
		'ACTION' 				=> getlink($Module['name']),
		'RECEIVER_EMAIL' 		=> $receiver_email,
		'RECEIVER_NAME' 		=> $receiver_name,
		'SENDER_EMAIL' 			=> $sender_email,
		'SENDER_NAME' 			=> $sender_name,
		)
	);
		
	$_CLASS['template']->display('modules/Recommend_Us/index.html');
	
	$_CLASS['display']->display_footer();

}

function send_recommend($sender_name, $sender_email, $receiver_name, $receiver_email, $message, $preview)
{
	global $_CLASS, $MAIN_CFG;
	
	$mail_message = '<center>Hi '.$receiver_name.' <br /><br /> '.$sender_name.' has recommended you look at this site.'
				.'<br /><br /><a href="'.$MAIN_CFG['global']['siteurl'].'">'.$MAIN_CFG['global']['sitename'].' - '.$MAIN_CFG['global']['siteurl'].'</a>';
	
	if ($message)
	{
		$message .= '<br /><br /><b>There following message was attached by sender</b><br />'.$message;
	}
	
	$mail_message .= '<br /><br /><br /><center>Message Sent from IP '. $_CLASS['user']->ip . '<br />Please report spammer at '. $MAIN_CFG['global']['siteurl'] .'</center>';


	if ($preview)
	{
		$_CLASS['template']->assign('PREVIEW', $mail_message);
		return;
	}
	
	$subject = $_CLASS['user']->lang['RECOMMENDATION'] . $sender_name;

	OpenTable();

	if (send_mail($mailer_message, $mail_message, true, $subject, $receiver_email, $receiver_name, $sender_email, $sender_name))
	{
		trigger_error($_CLASS['user']->lang['MESSAGE_SENT']);
		
	} else {
	
		$message = $_CLASS['user']->lang['MESSAGE_PROBLEM'];
			
		if (is_admin())
		{
			$message .= '<br /><div align="center"><b>'.$_CLASS['PHPMailer']->ErrorInfo.'</b></div>';
		}
				
		trigger_error($message);
	}
	
	CloseTable();
	
	$_CLASS['display']->display_footer();
  
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
				$error .= $_CLASS['user']->lang['ERROR_'.$field].'<br />';
				unset($field, $value, $lang);
        
        } elseif (($field == 'EMAIL' || $field == 'FEMAIL') && !check_email($value)) {
        
			$error .= $_CLASS['user']->lang['BAD_EMAIL'].'<br />';
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

	if (is_user())
	{
		$sender_name = $_CLASS['user']->data['username'];
		$sender_email = $_CLASS['user']->data['user_email'];
	} else {
		$sender_email = $sender_name = '';
	}
	
	recommend($sender_name, $sender_email);
}

?>