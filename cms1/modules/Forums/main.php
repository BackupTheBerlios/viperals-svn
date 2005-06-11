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

// -------------------------------------------------------------
//
// $Id: index.php,v 1.146 2004/09/01 15:55:40 psotfx Exp $
//
// FILENAME  : index.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL')) {
    header('location: ../../');
    die();
}

require_once($site_file_root.'includes/forums/functions.php');
load_class($site_file_root.'includes/forums/auth.php', 'auth');

$_CLASS['auth']->acl($_CLASS['core_user']->data);

$_CLASS['core_user']->add_img();

require($site_file_root.'includes/forums/functions_display.php');
display_forums('', $config['load_moderators']);

// Set some stats, get posts count from forums data if we... hum... retrieve all forums data
$total_posts = $config['num_posts'];
$total_topics = $config['num_topics'];
$total_users = $config['num_users'];
$newest_user = $config['newest_username'];
$newest_uid = $config['newest_user_id'];

$l_total_user_s = ($total_users == 0) ? 'TOTAL_USERS_ZERO' : 'TOTAL_USERS_OTHER';
$l_total_post_s = ($total_posts == 0) ? 'TOTAL_POSTS_ZERO' : 'TOTAL_POSTS_OTHER';
$l_total_topic_s = ($total_topics == 0) ? 'TOTAL_TOPICS_ZERO' : 'TOTAL_TOPICS_OTHER';

// Grab group details for legend display
$sql = 'SELECT group_id, group_name, group_colour, group_type
	FROM ' . GROUPS_TABLE . ' 
	WHERE group_legend = 1
		AND group_type <> ' . GROUP_HIDDEN;
$result = $_CLASS['core_db']->sql_query($sql);

$legend = '';
while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$legend .= (($legend != '') ? ', ' : '') . '<a style="color:#' . $row['group_colour'] . '" href="'.generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']) . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</a>';
}
$_CLASS['core_db']->sql_freeresult($result);


// Generate birthday list if required ...
$birthday_list = '';
if ($config['load_birthdays'])
{
	$now = getdate();
	$sql = 'SELECT user_id, username, user_colour, user_birthday 
		FROM ' . USERS_TABLE . " 
		WHERE user_birthday LIKE '" . sprintf('%2d-%2d-', $now['mday'], $now['mon']) . "%'
			AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
	$result = $_CLASS['core_db']->sql_query($sql);

	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$user_colour = ($row['user_colour']) ? ' style="color:#' . $row['user_colour'] .'"' : '';
		$birthday_list .= (($birthday_list != '') ? ', ' : '') . '<a' . $user_colour . ' href="' . generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $row['username'] . '</a>';
		
		if ($age = (int)substr($row['user_birthday'], -4))
		{
			$birthday_list .= ' (' . ($now['year'] - $age) . ')';
		}
	}
	$_CLASS['core_db']->sql_freeresult($result);
}

/// lets assign those language that are needed///
$_CLASS['core_template']->assign(array(
	'L_FORUM'			=> $_CLASS['core_user']->lang['FORUM'],
	'L_TOPICS'			=> $_CLASS['core_user']->lang['TOPICS'],
	'L_POSTS'			=> $_CLASS['core_user']->lang['POSTS'],
	'L_LAST_POST'		=> $_CLASS['core_user']->lang['LAST_POST'],
	'L_MARK_FORUMS_READ'=> $_CLASS['core_user']->lang['MARK_FORUMS_READ'],
	'L_WHO_IS_ONLINE'	=> $_CLASS['core_user']->lang['WHO_IS_ONLINE'],
	'L_BIRTHDAYS'		=> $_CLASS['core_user']->lang['BIRTHDAYS'],
	'L_STATISTICS'		=> $_CLASS['core_user']->lang['STATISTICS'],
	'L_USERNAME'		=> $_CLASS['core_user']->lang['USERNAME'],
	'L_GO'				=> $_CLASS['core_user']->lang['GO'],
	'L_PASSWORD'		=> $_CLASS['core_user']->lang['PASSWORD'],
	'L_LOG_ME_IN'		=> $_CLASS['core_user']->lang['LOG_ME_IN'],
	'L_NO_BIRTHDAYS'	=> $_CLASS['core_user']->lang['NO_BIRTHDAYS'],
	'L_LEGEND'			=> $_CLASS['core_user']->lang['LEGEND'],
	'L_LOGIN'			=> $_CLASS['core_user']->lang['LOGIN'],
	'L_REDIRECTS'		=> $_CLASS['core_user']->lang['REDIRECTS'],
	'L_NO_POSTS'		=> $_CLASS['core_user']->lang['NO_POSTS'],
	'L_NO_FORUMS'		=> $_CLASS['core_user']->lang['NO_FORUMS'],
	'L_DELETE_COOKIES'	=> $_CLASS['core_user']->lang['DELETE_COOKIES'],
	'L_NEW_POSTS'		=> $_CLASS['core_user']->lang['NEW_POSTS'],
	'L_NO_NEW_POSTS'	=> $_CLASS['core_user']->lang['NO_NEW_POSTS'],
	'L_FORUM_LOCKED'	=> $_CLASS['core_user']->lang['FORUM_LOCKED'])
);

// Assign index specific vars
$_CLASS['core_template']->assign(array(
	'TOTAL_POSTS'	=> sprintf($_CLASS['core_user']->lang[$l_total_post_s], $total_posts),
	'TOTAL_TOPICS'	=> sprintf($_CLASS['core_user']->lang[$l_total_topic_s], $total_topics),
	'TOTAL_USERS'	=> sprintf($_CLASS['core_user']->lang[$l_total_user_s], $total_users),
	'NEWEST_USER'	=> sprintf($_CLASS['core_user']->lang['NEWEST_USER'], '<a href="'. generate_link('Members_List&amp;mode=viewprofile&amp;u='.$newest_uid) . '">', $newest_user, '</a>'), 
	'LEGEND'		=> $legend, 
	'BIRTHDAY_LIST'	=> $birthday_list, 

	'FORUM_IMG'			=>	$_CLASS['core_user']->img('forum', 'NO_NEW_POSTS'),
	'FORUM_NEW_IMG'		=>	$_CLASS['core_user']->img('forum_new', 'NEW_POSTS'),
	'FORUM_LOCKED_IMG'	=>	$_CLASS['core_user']->img('forum_locked', 'NO_NEW_POSTS_LOCKED'),

	'S_LOGIN_ACTION'			=> generate_link('Control_Panel&amp;mode=login'), 
	'S_DISPLAY_BIRTHDAY_LIST'	=> ($config['load_birthdays']) ? true : false, 

	'U_MARK_FORUMS' => generate_link('Forums&amp;mark=forums')
	)
);

// Output page
$_CLASS['core_display']->display_head();

page_header();

$_CLASS['core_template']->display('modules/Forums/index_body.html');

$_CLASS['core_display']->display_footer();

?>