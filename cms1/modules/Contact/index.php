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

if (!defined('VIPERAL'))
{
    die();
}

$_CLASS['core_user']->add_lang();

$_CLASS['core_display']->display_header($_CLASS['core_user']->lang['FEEDBACK_TITLE']);

function feedback($sender_name, $sender_email, $message='', $error = false)
{
	global $_CLASS, $_CORE_MODULE;
	
		$_CLASS['core_template']->assign(array( 
			'L_FEEDBACKNOTE' 		=> $_CLASS['core_user']->lang['FEEDBACK_NOTE'],
			'L_YOURNAME' 			=> $_CLASS['core_user']->lang['YOURNAME'],
			'L_YOUREMAIL'	 		=> $_CLASS['core_user']->lang['YOUREMAIL'],
			'L_MESSAGE' 			=> $_CLASS['core_user']->lang['MESSAGE'],
			'L_PREVIEW' 			=> $_CLASS['core_user']->lang['PREVIEW'],
			'L_SUBMIT' 				=> $_CLASS['core_user']->lang['SUBMIT'],
			'IP'					=> $_CLASS['core_user']->ip,
			'ERROR' 				=> $error,
			'MESSAGE' 				=> $message,
			'ACTION' 				=> generate_link($_CORE_MODULE['name']),
			'SENDER_EMAIL' 			=> $sender_email,
			'SENDER_NAME' 			=> $sender_name,
			)
		);
		
	$_CLASS['core_template']->display('modules/Contact/index.html');
}

function send_feedback($sender_name, $sender_email, $message, $preview = false)
{
	global $_CLASS, $_CORE_CONFIG;

	$mail_message = '<br />' .$message . '<br /><br />
	<center>Message Sent from '. $_CORE_CONFIG['global']['site_url'] .'<br>
	'. $_CLASS['core_user']->lang['SENT_BY'] . ': ' . $sender_name . '<br />
	'. $_CLASS['core_user']->lang['SENDER_EMAIL'] . ': '. $sender_email . '<br />
	'.	$_CLASS['core_user']->lang['WITH_IP'] . $_CLASS['core_user']->ip . '<br /></center>';
	
	if (!$preview)
	{
		if ($_CLASS['core_user']->is_admin && $send_to )
		{
			$to = $send_to;
		}
		else
		{
			$to = $_CORE_CONFIG['global']['admin_mail'];        
		}
	
		$subject = $_CORE_CONFIG['global']['site_name'] . $_CLASS['core_user']->lang['FEEDBACK'];
	  
		if (send_mail($mailer_message, $mail_message, true, $subject, $to,  $to_name='', $sender_email, $sender_name))
		{
			trigger_error($_CLASS['core_user']->lang['FEEDBACK_SENT']);
		}
		else
		{
			$mail_message = $_CLASS['core_user']->lang['FEEDBACK_PROBLEM'];
			
			if (is_admin())
			{
				$mail_message .=  '<br /><div align="center"><b>'.$_CLASS['PHPMailer']->ErrorInfo.'</b></div>';
			}
		
			trigger_error($mail_message);
		}
	}
	else
	{
		echo '<div align="center"><b>'.$_CLASS['core_user']->lang['MESSAGE_PREVIEW'].'</b></div>';
		echo $mail_message;
	}
}
 

If (!empty($_POST['preview']) || !empty($_POST['contact']))
{
	$data['MESSAGE'] = get_variable('message', 'POST', '');
	$data['NAME'] = get_variable('sender_name', 'POST', '');
	$data['EMAIL'] = get_variable('sender_email', 'POST', '');
    
	$error = '';

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
		feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $error);
	}
	elseif (!empty($_POST['contact']) && !$error)
	{
		send_feedback($data['NAME'], $data['EMAIL'], $data['MESSAGE']);
	}
	else
	{
		feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $error);
	}	
}
else
{
	$sender_name = ($_CLASS['core_user']->data['user_id'] != ANONYMOUS) ? $_CLASS['core_user']->data['username'] : '';
	$sender_email = ($_CLASS['core_user']->data['user_id'] != ANONYMOUS) ? $_CLASS['core_user']->data['user_email'] : '';

	feedback($sender_name, $sender_email);
}

$_CLASS['core_display']->display_footer();

?>