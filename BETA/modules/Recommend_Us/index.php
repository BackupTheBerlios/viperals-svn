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

if (!defined('CPG_NUKE')) 
{
	header('location: ../../');
	exit;
}

require_once('header.php');

$module_name = basename(dirname(__FILE__));

get_lang($module_name);


function RecommendSite($error, $fname, $fmail, $yname, $ymail) {
	global $_CLASS, $userinfo;
  
	if ($error != '') {
		OpenTable();
		
		echo '<div align="center"><font color="#990000">'.$error.'</font></div>';
		
		CloseTable();
	} else {
		if (is_user()) {
			$yname = $_CLASS['user']->data['username'];
			$ymail = $_CLASS['user']->data['user_email'];
		}
	}
	
	OpenTable();
	
	echo '<form method="post" action="'.getlink().'">
		<div align="center" class="content"><strong>'._RECOMMENDINFO.'</strong>
		<br /><br /><br />
		<strong>'._FYOURNAME.'</strong><br />
		<input type="text" name="yname" value="'.$yname.'" alt="Your Name" /><br /><br />
		<strong>'._FYOUREMAIL.'</strong><br />
		<input type="text" name="ymail" value="'.$ymail.'" alt="Your Email Address" /><br /><br /><br />
		<strong>'._FFRIENDNAME.'</strong><br />
		<input type="text" name="fname" value="'.$fname.'" alt="Your Friend\'s Name" /><br /><br />
		<strong>'._FFRIENDEMAIL.'</strong><br />
		<input type="text" name="fmail" value="'.$fmail.'" alt="Your Friend\'s Email Address" />
		<br /><br />
		<input type="submit" name="recommendSite" value="'._SEND.'" />
		</div></form>';
		
	CloseTable();
	
}


function SendSite($yname, $ymail, $fname, $fmail) {
	global $sitename, $slogan, $nukeurl, $module_name, $SID;
	
	$to_name = stripslashes(FixQuotes(check_html(removecrlf($fname))));
	$to = stripslashes(FixQuotes(check_html(removecrlf($fmail))));
	$from_name = stripslashes(FixQuotes(check_html(removecrlf($yname))));
	$from = stripslashes(FixQuotes(check_html(removecrlf($ymail))));
	
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

if (isset($_POST['recommendSite']))
{
	unset($error);
	
	foreach ($_POST as $field => $value)
	{
		if ($value == '')
		{
			$fieldlang = "_$field";
			$fieldlang = (defined($fieldlang)) ? constant($fieldlang) : $fieldlang;
			$error .=  $fieldlang. "<br />";
		}
		$field = $value;
	}
	
	if (isset($error))
	{
		RecommendSite($error, $fname, $fmail, $yname, $ymail);
	} else {
		SendSite($yname, $ymail, $fname, $fmail);
	}

} else {	
	RecommendSite($error = '', $fname = '', $fmail = '', $yname = '', $ymail = '');
}

include('footer.php');

?>