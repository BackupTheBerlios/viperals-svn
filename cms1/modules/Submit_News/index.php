<?php

if (!defined('VIPERAL'))
{
    die();
}


$_CLASS['core_user']->add_lang();

if (isset($_POST['preview']) || isset($_POST['previewfull']))
{
	submit_news_preview();
}
elseif (isset($_POST['submit']))
{
	submit_news_post();
}
else
{
	submit_news();
}

$_CLASS['core_display']->display_footer();


function submit_news($name='', $email='', $title='', $intro='', $story='', $notes='', $error = false)
{
	global $_CLASS, $_CORE_MODULE;
	
	$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['SUBMIT_NEWS']);

	$_CLASS['core_template']->assign(array( 
		'L_STORY_TITLE'			=> $_CLASS['core_user']->lang['STORY_TITLE'],
		'L_STORY_INTRO' 		=> $_CLASS['core_user']->lang['STORY_INTRO'],
		'L_STORY_MESSAGE' 		=> $_CLASS['core_user']->lang['STORY_MESSAGE'],
		'L_STORY_NOTES' 		=> $_CLASS['core_user']->lang['STORY_NOTES'],
		'L_STORY_INTRO_HELP' 	=> $_CLASS['core_user']->lang['STORY_INTRO_HELP'],
		'L_STORY_STORY_HELP' 	=> $_CLASS['core_user']->lang['STORY_STORY_HELP'],
		'L_STORY_NOTES_HELP' 	=> $_CLASS['core_user']->lang['STORY_NOTES_HELP'],
		'L_YOURNAME' 			=> $_CLASS['core_user']->lang['YOURNAME'],
		'L_YOUREMAIL'	 		=> $_CLASS['core_user']->lang['YOUREMAIL'],
		'L_PREVIEW' 			=> $_CLASS['core_user']->lang['PREVIEW'],
		'L_SUBMIT' 				=> $_CLASS['core_user']->lang['SUBMIT'],
		'L_SUBMIT_MESSAGE' 		=> ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->lang['MSG_USER'] : $_CLASS['core_user']->lang['MSG_ANON'],
		'L_SUBMIT_INTRO'		=> $_CLASS['core_user']->lang['SUBMIT_INTRO'],
		'LOGIN'					=> $_CLASS['core_user']->is_user,
		'ERROR' 				=> $error,
		'ACTION' 				=> generate_link($_CORE_MODULE['name']),
		'EMAIL' 				=> $email,
		'NAME' 					=> $name,
		'TITLE'					=> $title,
		'STORY'					=>	$story,
		'INTRO'					=>	$intro,
		'NOTES'					=>	$notes,
		)
	);
		
	$_CLASS['core_template']->display('modules/Submit_News/index.html');

}

function submit_news_preview()
{
	global $_CLASS, $_CORE_MODULE;
	
	submit_news_getdata($data, $error);

	$full = isset($_POST['previewfull']);
	
	$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['SUBMIT_NEWS']);

	$_CLASS['core_user']->add_lang();
		
	$_CLASS['core_template']->assign_vars_array('news', array(
		'POSTER' 		=> $data['NAME'],
		'POSTER_LINK'	=> '',
		'CONTENT'		=> ($full) ? $data['STORY'] : $data['INTRO'],
		'FULL_STORY'	=> (strlen($data['STORY']) > 8) ? true : false,
		'CONTENT_LINK'  => '',
		'PRINT_LINK' 	=> '',
		'TIME'			=> $_CLASS['core_user']->format_date(time()),
		'TITLE'			=> $data['TITLE'],
		'ID'       		=> '',
		'COLLAPSE'  	=> false,
		)
	);

	$_CLASS['core_template']->assign('NEWS_PAGINATION', false);
	$_CLASS['core_template']->display('modules/News/index.html');
			
	submit_news($data['NAME'], $data['EMAIL'], $data['TITLE'], $data['INTRO'], $data['STORY'], $data['NOTES'], $error);
 }

function submit_news_post()
{
	global $_CLASS, $_CORE_MODULE, $prefix;
	
	submit_news_getdata($data, $error);

	if ($error)
	{
		submit_news($data['NAME'], $data['EMAIL'], $data['TITLE'], $data['INTRO'], $data['STORY'], $data['NOTES'], $error);
		return;
	}
	
	$sql = 'INSERT INTO '.$prefix.'_news ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'title'			=>	$data['TITLE'],
			'time'			=>	time(),
			'intro'			=>	$data['INTRO'],
			'story'			=>	$data['STORY'],
			'notes'			=>	$data['NOTES'],
			'poster_name' 	=>	$data['NAME'],
			'poster_id' 	=>	($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['user_id'] : '',
			'poster_ip'		=>	$_CLASS['core_user']->ip,
			'status'		=> 0
		));
	
	$_CLASS['core_db']->sql_query($sql);
	
	trigger_error('You article was recieved and you\'ll be contacted once it is approved or is any featue infomation is needed');

}

function submit_news_getdata(&$data, &$error)
{
	global $_CLASS;
	
	$error = false;
	
	$data['NAME'] = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['username'] : get_variable('username', 'POST', false);
	$data['EMAIL'] = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['user_email'] : get_variable('email', 'POST', false);
	$data['INTRO'] = get_variable('intro', 'POST', false);
	$data['TITLE'] = get_variable('title', 'POST', false);
	
	foreach ($data as $field => $value)
	{
		if (!$value)
		{
				$error .= $_CLASS['core_user']->lang['ERROR_'.$field].'<br />';
				unset($field, $value, $lang);
				
        } elseif ($field == 'EMAIL' && !$_CLASS['core_user']->is_user  && !check_email($value)) {
        
        	$error .= $_CLASS['core_user']->lang['BAD_EMAIL'].'<br />';
        	
		}
	}
	
	$data['NOTES'] = get_variable('notes', 'POST', false);
	$data['STORY'] = get_variable('story', 'POST', false);
}

?>