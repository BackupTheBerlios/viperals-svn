<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||

$Id$
*/

// -------------------------------------------------------------
//
// $Id: index.php,v 1.146 2004/09/01 15:55:40 psotfx Exp $
//
// FILENAME  : index.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    die;
}

$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_img();

require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';
display_forums('', $config['load_moderators']);

// Grab group details for legend display
$sql = 'SELECT group_id, group_name, group_colour, group_type
	FROM ' . CORE_GROUPS_TABLE . ' 
	WHERE group_legend = 1
		AND group_type <> ' . GROUP_HIDDEN;
$result = $_CLASS['core_db']->query($sql);

$legend = array();
while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$legend[] .= '<a style="color:#' . $row['group_colour'] . '" href="'.generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']) . '">' . (isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</a>';
}
$_CLASS['core_db']->free_result($result);

$legend = implode(', ', $legend);

// Generate birthday list if required ...
$birthday_list = '';
if ($config['load_birthdays'])
{
	$now = getdate();
	$now = explode(':', gmdate('j:m'));

	$sql = 'SELECT user_id, username, user_colour, user_birthday 
		FROM ' . CORE_USERS_TABLE . " 
		WHERE user_birthday LIKE '" . sprintf('%2d-%2d-', $now[0], $now[1]) . "%'
			AND user_type = ".USER_NORMAL;
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$user_colour = ($row['user_colour']) ? ' style="color:#' . $row['user_colour'] .'"' : '';
		$birthday_list .= (($birthday_list != '') ? ', ' : '') . '<a' . $user_colour . ' href="' . generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) . '">' . $row['username'] . '</a>';
		
		if ($age = (int) substr($row['user_birthday'], -4))
		{
			$birthday_list .= ' (' . ($now['year'] - $age) . ')';
		}
	}
	$_CLASS['core_db']->free_result($result);
	
	unset($now);
}

$l_total_user_s = ($_CORE_CONFIG['user']['total_users'] === 0) ? 'TOTAL_USERS_ZERO' : 'TOTAL_USERS_OTHER';
$l_total_post_s = ($config['num_posts'] == 0) ? 'TOTAL_POSTS_ZERO' : 'TOTAL_POSTS_OTHER';
$l_total_topic_s = ($config['num_topics'] == 0) ? 'TOTAL_TOPICS_ZERO' : 'TOTAL_TOPICS_OTHER';

// Assign index specific vars
$_CLASS['core_template']->assign_array(array(
	'TOTAL_POSTS'	=> sprintf($_CLASS['core_user']->get_lang($l_total_post_s), $config['num_posts']),
	'TOTAL_TOPICS'	=> sprintf($_CLASS['core_user']->get_lang($l_total_topic_s), $config['num_topics']),
	'TOTAL_USERS'	=> sprintf($_CLASS['core_user']->get_lang($l_total_user_s), $_CORE_CONFIG['user']['total_users']),
	'NEWEST_USER'	=> sprintf($_CLASS['core_user']->get_lang('NEWEST_USER'), '<a href="'. generate_link('Members_List&amp;mode=viewprofile&amp;u='.$_CORE_CONFIG['user']['newest_user_id']) . '">', $_CORE_CONFIG['user']['newest_username'], '</a>'), 
	'LEGEND'		=> $legend, 
	'BIRTHDAY_LIST'	=> $birthday_list, 

	'FORUM_IMG'			=>	$_CLASS['core_user']->img('forum', 'NO_NEW_POSTS'),
	'FORUM_NEW_IMG'		=>	$_CLASS['core_user']->img('forum_new', 'NEW_POSTS'),
	'FORUM_LOCKED_IMG'	=>	$_CLASS['core_user']->img('forum_locked', 'NO_NEW_POSTS_LOCKED'),

	'S_LOGIN_ACTION'			=> generate_link('Control_Panel&amp;mode=login'), 
	'S_DISPLAY_BIRTHDAY_LIST'	=> ($config['load_birthdays']), 

	'U_MARK_FORUMS'	=> generate_link('Forums&amp;mark=forums')
));
unset($birthday_list, $legend);

page_header();

$_CLASS['core_display']->footer .= $_CLASS['core_template']->display('modules/Forums/menus.html', true);

$_CLASS['core_display']->display(false, 'modules/Forums/index_body.html');

?>