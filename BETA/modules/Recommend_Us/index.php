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
    header('location: ../../');
    die();
}

$_CLASS['user']->add_lang();

$_CLASS['display']->display_head($_CLASS['user']->lang['RECOMEND_US']);

function recommend($sender_name, $sender_email, $receiver_name='', $receiver_email='', $message='', $error = false) {
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
		'ACTION' 				=> getlink($Module['title']),
		'SENDER_EMAIL' 			=> $sender_email,
		'SENDER_NAME' 			=> $sender_name,
		)
	);
		
	$_CLASS['template']->display('modules/Recommend_Us/index.html');
	
}


function send_recommend($yname, $ymail, $fname, $fmail) {
	global $sitename, $slogan, $nukeurl, $module_name, $SID;
	
	if (is_user()) {
		$yname = $_CLASS['user']->data['username'];
		$ymail = $_CLASS['user']->data['user_email'];
	}
		
	$subject = ""._HAVELOOK." $sitename";
	
	$ip_addy = $_SERVER['REMOTE_ADDR'];
	
	$message = "\n"
			  .""._HELLO." $fname,\n\n"
			  .""._YOURFRIEND." $yname "._OURSITE."\n\n"
			  ."<strong>"._WHOWEARE."</strong>\n\n"
			  ."$sitename\n"
			  ."$slogan\n"
			  .""._VISITUS." <a href=\"$nukeurl\" target=\"ResourceWindow\">$nukeurl</a>\n"
			  .""._POSTEDBY." $ip_addy | <a href=\"http://ws.arin.net/cgi-bin/whois.pl?queryinput=$ip_addy\" target=\"ResourceWindow\">"._WHOIS."</a>\n";

	OpenTable();

	if (send_mail($mailer_message, $message, $html=0, $subject, $to, $to_name, $from, $from_name))
	{
	
	echo '<div align="center" class="content">
		'._FREFERENCE . $fname .'...
		<br /><br />' . _THANKSREC .'
		</div>';
		
	} else {
	
	echo '<div align="center" class="content">There was an error Sending</div>';
	}
	CloseTable();
   
}

if (isset($_POST['recommend']))
{

    $data['FNAME'] = get_variable('receiver_name', 'POST', '');
    $data['FEMAIL'] = get_variable('receiver_email', 'POST', '');
    $data['NAME'] = get_variable('sender_email', 'POST', '');
    $data['EMAIL'] = get_variable('sender_name', 'POST', '');
    $message = get_variable('message', 'POST', '');
    $error = '';

	foreach ($data as $field => $value) {
		
		if (!$value) {
				$error .= $_CLASS['user']->lang['ERROR_'.$field].'<br />';
				unset($field, $value, $lang);
        }
         elseif ($field == 'EMAIL' || $field == 'FEMAIL' && validate_email($value)) {
			$error .= 'Please enter a valid email address<br />';
		}
	}
	
	if ($data['EMAIL'] && !validate_email($data['EMAIL']))
	{
		$error .= 'Please enter a valid email address';
	}
	
	if ($error)
	{
		recommend($data['NAME'], $data['EMAIL'], $data['FNAME'], $data['FEMAIL'], $message, $error);
	} else {
	//	send_recommend($yname, $ymail, $fname, $fmail);
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

include('footer.php');

?>