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
require('header.php');

if (!empty($_POST['preview']))
{
		submit_news_preview();
} else {
		submit_news();
}

require('footer.php');


function submit_news($name='', $email='', $title='', $intro='', $story='', $notes='', $error='')
{
	global $_CLASS, $Module;
	
		$_CLASS['user']->add_lang();

		$_CLASS['template']->assign(array( 
			'L_STORY_TITLE'			=> $_CLASS['user']->lang['STORY_TITLE'],
			'L_STORY_INTRO' 		=> $_CLASS['user']->lang['STORY_INTRO'],
			'L_STORY_MESSAGE' 		=> $_CLASS['user']->lang['STORY_MESSAGE'],
			'L_STORY_NOTES' 		=> $_CLASS['user']->lang['STORY_NOTES'],
			'L_STORY_INTRO_HELP' 	=> $_CLASS['user']->lang['STORY_INTRO_HELP'],
			'L_STORY_STORY_HELP' 	=> $_CLASS['user']->lang['STORY_STORY_HELP'],
			'L_STORY_NOTES_HELP' 	=> $_CLASS['user']->lang['STORY_NOTES_HELP'],
			'L_YOURNAME' 			=> $_CLASS['user']->lang['YOURNAME'],
			'L_YOUREMAIL'	 		=> $_CLASS['user']->lang['YOUREMAIL'],
			'L_PREVIEW' 			=> $_CLASS['user']->lang['PREVIEW'],
			'L_SUBMIT' 				=> $_CLASS['user']->lang['SUBMIT'],
			'L_SUBMIT_MESSAGE' 		=> (is_user()) ? $_CLASS['user']->lang['MSG_USER'] : $_CLASS['user']->lang['MSG_ANON'],
			'L_SUBMIT_INTRO'		=> $_CLASS['user']->lang['SUBMIT_INTRO'],
			'LOGIN'					=> is_user(),
			'IP'					=> $_CLASS['user']->ip,
			'ERROR' 				=> ($error) ? $error : false,
			'ACTION' 				=> getlink($Module['title']),
			'EMAIL' 				=> $email,
			'NAME' 					=> $name,
			'TITLE'					=> $title,
			'STORY'					=>	$story,
			'INTRO'					=>	$intro,
			'NOTES'					=>	$notes,
			)
		);
		
	$_CLASS['template']->display('modules/Submit_News/index.html');
}

function submit_news_preview()
{
	global $_CLASS, $Module;
	
	$name = (is_user()) ? $_CLASS['user']->data['user_name'] : get_variable('name', 'POST', false);
	$email = get_variable('email', 'POST', false);
	$story = get_variable('story', 'POST', false);
	$intro = get_variable('intro', 'POST', false);
	$notes = get_variable('notes', 'POST', false);
	$title = get_variable('title', 'POST', false);

	if (THEMEPLATE) {
	
		$_CLASS['user']->add_lang();
		
        $_CLASS['template']->assign_vars_array('news', array(
		   //'IMG_TOPIC'   => (file_exists("themes/$ThemeSel/images/topics/$topicinfo[topicimage]") ? "themes/$ThemeSel/images/topics/$topicinfo[topicimage]" : "$tipath$topicinfo[topicimage]"),
			'POSTER' 		=> $name,
			'POSTER_LINK'	=> '',
			'CONTENT'		=> $intro,
			'FULL_STORY'	=> (strlen($story) > 5) ? true : false,
			'CONTENT_LINK'  => '',
			'PRINT_LINK' 	=> '',
			'TIME'			=> $_CLASS['user']->format_date(time()),
			'TITLE'			=> $title,
			'ID'       		=> '',
			'IMAGE'    		=> 'themes/viperal/images/plus.gif',
			'COLLAPSE'  	=> '',
			'TOPIC' 		=> ''
			)
		);
		
		$_CLASS['template']->assign('NEWS_NUMBERING', false);
        $_CLASS['template']->display('modules/News/index.html');
	}  
		
	submit_news($name='', $email='', $title='', $intro='', $story='', $notes='', $error='');
 
}

?>