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

$_CLASS['display']->display_head($_CLASS['user']->lang['FEEDBACK_TITLE']);

function feedback($sender_name, $sender_email, $message='', $error = false)
{
	global $_CLASS, $Module;
	
		$_CLASS['template']->assign(array( 
			'L_FEEDBACKNOTE' 		=> $_CLASS['user']->lang['FEEDBACK_NOTE'],
			'L_YOURNAME' 			=> $_CLASS['user']->lang['YOURNAME'],
			'L_YOUREMAIL'	 		=> $_CLASS['user']->lang['YOUREMAIL'],
			'L_MESSAGE' 			=> $_CLASS['user']->lang['MESSAGE'],
			'L_PREVIEW' 			=> $_CLASS['user']->lang['PREVIEW'],
			'L_SUBMIT' 				=> $_CLASS['user']->lang['SUBMIT'],
			'IP'					=> $_CLASS['user']->ip,
			'ERROR' 				=> $error,
			'MESSAGE' 				=> $message,
			'ACTION' 				=> getlink($Module['name']),
			'SENDER_EMAIL' 			=> $sender_email,
			'SENDER_NAME' 			=> $sender_name,
			)
		);
		
	$_CLASS['template']->display('modules/Contact/index.html');
}

function send_feedback($sender_name, $sender_email, $message, $preview = false)
{
	global $_CLASS, $MAIN_CFG;

        $mail_message = '<br />' .$message . '<br /><br />
        <center>Message Sent from '. $MAIN_CFG['global']['siteurl'] .'<br>
        '. $_CLASS['user']->lang['SENT_BY'] . ': ' . $sender_name . '<br />
        '. $_CLASS['user']->lang['SENDER_EMAIL'] . ': '. $sender_email . '<br />
        '.	$_CLASS['user']->lang['WITH_IP'] . $_CLASS['user']->ip . '<br /></center>';
        
        if (!$preview)
        {
			if (is_admin() && $send_to ) {
				$to = $send_to;
			} else {
				$to = $MAIN_CFG['global']['admin_mail'];        
			}
		
			$subject = $MAIN_CFG['global']['sitename'] . $_CLASS['user']->lang['FEEDBACK'];
          
			if (send_mail($mailer_message, $mail_message, true, $subject, $to,  $to_name='', $sender_email, $sender_name))
			{
				
				trigger_error($_CLASS['user']->lang['FEEDBACK_SENT']);
				
			} else {
			
				$mail_message = $_CLASS['user']->lang['FEEDBACK_PROBLEM'];
				
				if (is_admin())
				{
					$mail_message .=  '<br /><div align="center"><b>'.$_CLASS['PHPMailer']->ErrorInfo.'</b></div>';
				}
			
				trigger_error($mail_message);
			}
			

		} else {
		
			OpenTable();
				echo '<div align="center"><b>'.$_CLASS['user']->lang['MESSAGE_PREVIEW'].'</b></div>';
				echo $mail_message;
			CloseTable();
        }
}
 

If (!empty($_POST['preview']) || !empty($_POST['contact'])) {

    $data['MESSAGE'] = get_variable('message', 'POST', '');
    $data['NAME'] = get_variable('sender_name', 'POST', '');
    $data['EMAIL'] = get_variable('sender_email', 'POST', '');
    
    $error = '';

	foreach ($data as $field => $value) {
		if (!$value)
		{
				$error .= $_CLASS['user']->lang['ERROR_'.$field].'<br />';
				unset($field, $value, $lang);
				
        } elseif ($field == 'EMAIL' && !check_email($value)) {
        
			$error .= $_CLASS['user']->lang['BAD_EMAIL'].'<br />';
		}
	} 
	
	if (!empty($_POST['preview']) && $data['MESSAGE']) {
	
		send_feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $preview = true);
		feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $error);
	
	} elseif (!empty($_POST['contact']) && !$error) {
	
		send_feedback($data['NAME'], $data['EMAIL'], $data['MESSAGE']);
	
	} else {
	
		feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $error);
	}
	
		
} else {

	$sender_name = ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? $_CLASS['user']->data['username'] : '';
	$sender_email = ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? $_CLASS['user']->data['user_email'] : '';

	feedback($sender_name, $sender_email);
  
}

$_CLASS['display']->display_footer();

?>