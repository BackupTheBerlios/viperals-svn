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

function send_feedback($sender_name, $sender_email, $message, $preview = false)
{
	global $_CLASS, $_CORE_CONFIG;

	$_CLASS['core_template']->assign(array(
		'SENT_FROM'		=> $sender_name,
		'SENDER_NAME'	=> $sender_name,
		'SENDER_EMAIL'	=> $sender_email,
		'SENDER_IP'		=> $_CLASS['core_user']->ip,

		'MESSAGE' 		=> $message,
	));
	
	$body = $_CLASS['core_template']->display('modules/Contact/email/index.html', true);

	if ($preview)
	{
		$_CLASS['core_template']->assign('PREVIEW', $body);
		return;
	}
	
	//print $body;
}


$error = '';

If (!empty($_POST['preview']) || !empty($_POST['contact']))
{
	$data['MESSAGE']= get_variable('message', 'POST', '');
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

	
$_CLASS['core_template']->assign(array( 
	'ERROR' 				=> $error,
	'MESSAGE' 				=> $message,
	'ACTION' 				=> generate_link($_CORE_MODULE['name']),
	'SENDER_EMAIL' 			=> $sender_email,
	'SENDER_NAME' 			=> $sender_name,
));
		
$_CLASS['core_template']->display('modules/Contact/index.html');
$_CLASS['core_display']->display_footer();

?>