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

if (!defined('CPG_NUKE')) {
    Header("Location: ../../");
    die();
}

$_CLASS['user']->add_lang();

$Module['custom_title'] = $_CLASS['user']->lang['FEEDBACK_TITLE'];

require('header.php');

function feedback($sender_name, $sender_email, $message, $error)
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
			'ERROR' 				=> ($error) ? $error : false,
			'MESSAGE' 				=> $message,
			'ACTION' 				=> getlink($Module['title']),
			'SENDER_EMAIL' 			=> $sender_email,
			'SENDER_NAME' 			=> $sender_name,
			)
		);
		
	$_CLASS['template']->display('modules/Contact/index.html');
}

function Sendfeedback($sender_name, $sender_email, $message, $preview = false)
{
global $_CLASS, $MAIN_CFG;

        $mail_message = '<br />' .$message . '<br /><br />
        <div align="center">Message Sent from '. $MAIN_CFG['global']['nukeurl'] .'<br>
        '. $_CLASS['user']->lang['SENT_BY'] . ': ' . $sender_name . '<br />
        '. $_CLASS['user']->lang['SENDER_EMAIL'] . ': '. $sender_email . '<br />
        '.	$_CLASS['user']->lang['WITH_IP'] . $_CLASS['user']->ip . '<br /></div>';
        
        if (!$preview)
        {
			if (is_admin() && $send_to ) {
				$to = $send_to;
			} else {
				$to = $MAIN_CFG['global']['adminmail'];        
			}

			$subject = $MAIN_CFG['global']['sitename'] . $_CLASS['user']->lang['FEEDBACK'];
          
			OpenTable();
			
			if (send_mail($mailer_message, $mail_message, true, $subject, $to,  $to_name='', $sender_email, $sender_name))
			{
				
				echo '<div align="center"><b>'.$_CLASS['user']->lang['FEEDBACK_SENT'].'</b></div>';
				
			} else {
				
				echo '<div align="center"><b>'.$_CLASS['user']->lang['FEEDBACK_PROBLEM'].'</b></div>';
			}
			
			echo '<br /><div align="center"><b>'.$_CLASS['PHPMailer']->ErrorInfo.'</b></div>';
			
			CloseTable();

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
		if (!$value) {
				$error .= $_CLASS['user']->lang['ERROR_'.$field].'<br />';
				unset($field, $value, $lang);
        }
	}
	
	if ($data['EMAIL'] && !preg_match('#^[a-z0-9\.\-_\+]+?@(.*?\.)*?[a-z0-9\-_]+?\.[a-z]{2,4}$#i', $data['EMAIL']))
	{
		$error .= 'Please enter a valid email address';
	}
	
	if (!empty($_POST['preview']) && $data['MESSAGE']) {
	
		Sendfeedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $preview = true);
		feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $error);
	
	} elseif (!empty($_POST['contact']) && !$error) {
	
		Sendfeedback($data['NAME'], $data['EMAIL'], $data['MESSAGE']);
	
	} else {
	
		feedback($data['NAME'],  $data['EMAIL'], $data['MESSAGE'], $error);
	}
	
		
} else {

	if (is_admin()) {
	
		$sender_email = $MAIN_CFG['global']['adminmail'];
		$sender_name = $MAIN_CFG['global']['sitename'];
		$recip = '<label for="send_to"><b>'._SEND_TO.'</b></label><br /><input type="text" name="send_to" size="30" /><br />';
	
	} else {
	
		$sender_name = ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? $_CLASS['user']->data['username'] : '';
		$sender_email = ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? $_CLASS['user']->data['user_email'] : '';
		$recip = '';
	}

	feedback($sender_name, $sender_email, $message='' , $error= '');
  
}

require('footer.php');

?>