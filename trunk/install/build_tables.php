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

$install_prefix = 'test_';
$time_field = 1122762758; // max Friday January 19th 2038

/*
	Admin Auth Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'admins');

$_CLASS['core_db']->add_table_field_char('section', 100);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('status', 0, 1);
$_CLASS['core_db']->add_table_field_text('options', 60000);

$_CLASS['core_db']->add_table_index('auth_id', 'primary');
$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');

$_CLASS['core_db']->table_create('commit');

/*
	Blocks Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'blocks');

$_CLASS['core_db']->add_table_field_int('id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('title', 100);
$_CLASS['core_db']->add_table_field_int('type', 0, 1);
$_CLASS['core_db']->add_table_field_int('position', 0, 1);
$_CLASS['core_db']->add_table_field_int('weight', 0, 200);
$_CLASS['core_db']->add_table_field_int('active', 0, 1);
$_CLASS['core_db']->add_table_field_int('start', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('expires', 0, $time_field);
$_CLASS['core_db']->add_table_field_text('content', 60000);
$_CLASS['core_db']->add_table_field_char('file', 255);
$_CLASS['core_db']->add_table_field_text('auth', 60000);
$_CLASS['core_db']->add_table_field_char('rss_url', 255);
$_CLASS['core_db']->add_table_field_int('rss_rate', 0, 60000);
$_CLASS['core_db']->add_table_field_int('rss_expires', 0, $time_field);

$_CLASS['core_db']->add_table_index('id', 'primary');
$_CLASS['core_db']->add_table_index('type');
$_CLASS['core_db']->add_table_index('position');

$_CLASS['core_db']->table_create('commit');

/*
	Sessions Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'config');

$_CLASS['core_db']->add_table_field_char('section', 20);
$_CLASS['core_db']->add_table_field_char('name', 20);
$_CLASS['core_db']->add_table_field_text('value', 60000);

$_CLASS['core_db']->add_table_index('section');
$_CLASS['core_db']->add_table_index('name');


$_CLASS['core_db']->table_create('commit');


/*
	Grouyps Table
	`group_chgpass` smallint(6) NOT NULL default '0',

*/
$_CLASS['core_db']->table_create('start', $install_prefix.'groups');

$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('group_type', 0, 1, 1);
$_CLASS['core_db']->add_table_field_char('group_name', 50);
$_CLASS['core_db']->add_table_field_int('group_display', 0, 1);
$_CLASS['core_db']->add_table_field_char('group_avatar', 100);

$_CLASS['core_db']->add_table_field_int('group_avatar_type', 0, 2000);
$_CLASS['core_db']->add_table_field_int('group_avatar_width', 0, 2000);
$_CLASS['core_db']->add_table_field_int('group_avatar_height', 0, 2000);
$_CLASS['core_db']->add_table_field_int('group_rank', 0, 20000, -1);

$_CLASS['core_db']->add_table_field_char('group_colour', 6);
$_CLASS['core_db']->add_table_field_int('group_sig_chars', 0, 160000);
$_CLASS['core_db']->add_table_field_int('group_receive_pm', 0, 1);
$_CLASS['core_db']->add_table_field_int('group_message_limit', 0, 16000000);

$_CLASS['core_db']->add_table_field_char('group_description', 255);
$_CLASS['core_db']->add_table_field_int('group_legend', 0, 1);

$_CLASS['core_db']->add_table_index('group_id', 'primary');
$_CLASS['core_db']->add_table_index('group_legend');

$_CLASS['core_db']->table_create('commit');

/*
	Groups Users Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'groups_users');

$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('user_id', 100);
$_CLASS['core_db']->add_table_field_int('user_status', 0, 1);

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');

$_CLASS['core_db']->table_create('commit');

/*
	Modules Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'modules');

$_CLASS['core_db']->add_table_field_int('id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('name', 100);
$_CLASS['core_db']->add_table_field_char('title', 100);
$_CLASS['core_db']->add_table_field_int('active', 0, 1);
$_CLASS['core_db']->add_table_field_int('type', 0, 1);
$_CLASS['core_db']->add_table_field_int('sides', 0, 1);
$_CLASS['core_db']->add_table_field_text('auth', 60000);
$_CLASS['core_db']->add_table_field_text('admin_options', 200);

$_CLASS['core_db']->add_table_index('id', 'primary');
$_CLASS['core_db']->add_table_index('name', 'unique');
$_CLASS['core_db']->add_table_index('homepage'); // to be removed late on

$_CLASS['core_db']->table_create('commit');

/*
	Sessions Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'sessions');

$_CLASS['core_db']->add_table_field_char('session_id', 40);
$_CLASS['core_db']->add_table_field_int('session_user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('session_last_visit', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('session_start', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('session_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_char('session_ip', 18);
$_CLASS['core_db']->add_table_field_char('session_browser', 255);
$_CLASS['core_db']->add_table_field_char('session_page', 100);
$_CLASS['core_db']->add_table_field_char('session_url', 255);
$_CLASS['core_db']->add_table_field_int('session_user_type', 0, 1);
$_CLASS['core_db']->add_table_field_int('session_admin', 0, 1);
$_CLASS['core_db']->add_table_field_text('session_auth', 60000);
$_CLASS['core_db']->add_table_field_text('session_data', 60000);

$_CLASS['core_db']->add_table_index('session_id', 'primary');
$_CLASS['core_db']->add_table_index('session_time');
$_CLASS['core_db']->add_table_index('session_user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Smiles Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'smilies');

$_CLASS['core_db']->add_table_field_int('smiley_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('code', 10);
$_CLASS['core_db']->add_table_field_char('emotion', 50);
$_CLASS['core_db']->add_table_field_char('smiley_url', 50);
$_CLASS['core_db']->add_table_field_int('smiley_width', 0, 200);
$_CLASS['core_db']->add_table_field_int('smiley_height', 0, 200);
$_CLASS['core_db']->add_table_field_int('smiley_order', 0, 200);
$_CLASS['core_db']->add_table_field_int('display_on_posting', 0, 1);

$_CLASS['core_db']->add_table_index('smiley_id', 'primary');
$_CLASS['core_db']->add_table_index('display_on_posting');

$_CLASS['core_db']->table_create('commit');

//////
//Quick Message
//////

$_CLASS['core_db']->table_create('start', $install_prefix.'quick_message');

$_CLASS['core_db']->add_table_field_int('id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('user_name', 80);
$_CLASS['core_db']->add_table_field_char('ip', 18);
$_CLASS['core_db']->add_table_field_text('message', 200);
$_CLASS['core_db']->add_table_field_int('time', 0, $time_field);

$_CLASS['core_db']->add_table_index('id', 'primary');
$_CLASS['core_db']->add_table_index('time');

$_CLASS['core_db']->table_create('commit');


/*

`user_passchg` int(11) NOT NULL default '0',
`user_timezone` decimal(5,2) NOT NULL default '0.00',
`user_dst` tinyint(1) NOT NULL default '0',
 `user_unread_privmsg` tinyint(4) unsigned NOT NULL default '0',
`user_occ` varchar(255) NOT NULL default '',
`user_newpasswd` varchar(40) NOT NULL default '',
 `user_emailtime` int(11) NOT NULL default '0',
  `user_actkey` varchar(32) NOT NULL default '',
  `user_newpasswd` varchar(40) NOT NULL default '',
  `user_viewemail` tinyint(1) NOT NULL default '0',
user_lastpost_time
  
*/


$_CLASS['core_db']->table_create('start', $install_prefix.'users');

$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('username', 80);
$_CLASS['core_db']->add_table_field_char('user_password', 40);
$_CLASS['core_db']->add_table_field_char('user_password_encoding', 10);
$_CLASS['core_db']->add_table_field_int('user_type', 0, 1);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('user_rank', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('user_ip', 255);
$_CLASS['core_db']->add_table_field_char('user_agent', 255);
$_CLASS['core_db']->add_table_field_int('user_timezone', -43200 , 46800);

$_CLASS['core_db']->add_table_field_int('user_regdate', 0, $time_field);
$_CLASS['core_db']->add_table_field_text('user_permissions', 60000); // phpBBs
$_CLASS['core_db']->add_table_field_char('user_email', 100);
$_CLASS['core_db']->add_table_field_char('user_birthday', 10);
$_CLASS['core_db']->add_table_field_text('user_data', 6000);
$_CLASS['core_db']->add_table_field_int('user_last_visit', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('user_last_post_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('user_warnings', 0, 10000);
$_CLASS['core_db']->add_table_field_char('user_lang', 10);
$_CLASS['core_db']->add_table_field_char('user_theme', 60);
$_CLASS['core_db']->add_table_field_int('user_rank', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('user_colour', 6);
$_CLASS['core_db']->add_table_field_char('user_dateformat', 14);
$_CLASS['core_db']->add_table_field_int('user_new_privmsg', 0, 1);

$_CLASS['core_db']->add_table_field_text('user_sig', 60000);
$_CLASS['core_db']->add_table_field_char('user_from', 100);
$_CLASS['core_db']->add_table_field_char('user_icq', 15);
$_CLASS['core_db']->add_table_field_char('user_aim', 255);
$_CLASS['core_db']->add_table_field_char('user_yim', 255);
$_CLASS['core_db']->add_table_field_char('user_msnm', 255);
$_CLASS['core_db']->add_table_field_char('user_jabber', 255);
$_CLASS['core_db']->add_table_field_char('user_website', 255);

$_CLASS['core_db']->add_table_field_char('user_interests', 255);
$_CLASS['core_db']->add_table_field_char('user_occ', 255);

$_CLASS['core_db']->add_table_field_int('user_posts', 16000000);

$_CLASS['core_db']->add_table_field_int('user_message_limit', 0, 200);
$_CLASS['core_db']->add_table_field_int('user_message_rules', 0, 1);

// look at these
$_CLASS['core_db']->add_table_field_int('user_full_folder', -10, 16000000, -3);
$_CLASS['core_db']->add_table_field_int('user_attachsig', 0, 1, 1);
///

$_CLASS['core_db']->add_table_field_int('user_notify', 0, 1);
$_CLASS['core_db']->add_table_field_int('user_notify_pm', 0, 1);
$_CLASS['core_db']->add_table_field_int('user_notify_type', 0, 200);

$_CLASS['core_db']->add_table_field_int('user_allow_pm', 0, 1, 1);
$_CLASS['core_db']->add_table_field_int('user_allow_email', 0, 1, 1);

$_CLASS['core_db']->add_table_field_char('user_sig_bbcode_uid', 5);
$_CLASS['core_db']->add_table_field_int('user_sig_bbcode_bitfield', 0, 1600);

$_CLASS['core_db']->add_table_field_int('user_topic_show_days', 0, 200);
$_CLASS['core_db']->add_table_field_char('user_topic_sortby_type', 1);
$_CLASS['core_db']->add_table_field_char('user_topic_sortby_dir', 1);

$_CLASS['core_db']->add_table_field_int('user_post_show_days', 0, 200);
$_CLASS['core_db']->add_table_field_char('user_post_sortby_type', 1);
$_CLASS['core_db']->add_table_field_char('user_post_sortby_dir', 1);

$_CLASS['core_db']->add_table_field_int('user_allow_viewonline', 0, 1, 1);
$_CLASS['core_db']->add_table_field_int('user_allow_viewemail', 0, 1, 1);
$_CLASS['core_db']->add_table_field_int('user_allow_massemail', 0, 1, 1);

$_CLASS['core_db']->add_table_field_char('user_avatar', 200);
$_CLASS['core_db']->add_table_field_int('user_avatar_type', 0, 10);
$_CLASS['core_db']->add_table_field_int('user_avatar_width', 0, 100);
$_CLASS['core_db']->add_table_field_int('user_avatar_height', 0, 100);

$_CLASS['core_db']->add_table_field_char('user_act_key', 40);

$_CLASS['core_db']->add_table_index('user_id', 'primary');
$_CLASS['core_db']->add_table_index('username');
$_CLASS['core_db']->add_table_index('user_birthday');

$_CLASS['core_db']->table_create('commit');

//////
//Forums
//////

/*
	Attachments Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_attachments');

$_CLASS['core_db']->add_table_field_int('attach_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('post_msg_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('poster_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('download_count', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('in_message'. 0, 1);

$_CLASS['core_db']->add_table_field_text('comment', 200);
$_CLASS['core_db']->add_table_field_char('physical_filename', 255);
$_CLASS['core_db']->add_table_field_char('real_filename', 255);
$_CLASS['core_db']->add_table_field_int('thumbnail'. 0, 1);
$_CLASS['core_db']->add_table_field_char('extension', 50);
$_CLASS['core_db']->add_table_field_char('mimetype', 100);
$_CLASS['core_db']->add_table_field_int('filesize', 0, 1000000000);
$_CLASS['core_db']->add_table_field_int('filetime', 0, $time_field);

$_CLASS['core_db']->add_table_index('attach_id', 'primary');
$_CLASS['core_db']->add_table_index('post_msg_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('poster_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Auth Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_auth');

$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('auth_option_id', 0, 2000);
$_CLASS['core_db']->add_table_field_int('auth_setting', 0, 1);

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');
$_CLASS['core_db']->add_table_index('auth_option_id');
#$_CLASS['core_db']->add_table_index('auth_option', 'unique');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Auth Options Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_auth_options');

$_CLASS['core_db']->add_table_field_int('auth_option_id', 0, 2000, 0, true);
$_CLASS['core_db']->add_table_field_char('auth_option', 20);
$_CLASS['core_db']->add_table_field_int('is_global', 0, 1);
$_CLASS['core_db']->add_table_field_int('is_local', 0, 1);
$_CLASS['core_db']->add_table_field_int('founder_only', 0, 1);

$_CLASS['core_db']->add_table_index('auth_option_id', 'primary');
$_CLASS['core_db']->add_table_index('auth_option');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Auth Presets Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_auth_presets');

$_CLASS['core_db']->add_table_field_int('preset_id', 0, 20000, 0, true);
$_CLASS['core_db']->add_table_field_int('preset_user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('preset_name', 50);
$_CLASS['core_db']->add_table_field_char('preset_type', 2);
$_CLASS['core_db']->add_table_field_text('preset_data', 2000);

$_CLASS['core_db']->add_table_index('preset_id', 'primary');
$_CLASS['core_db']->add_table_index('preset_user_id');
$_CLASS['core_db']->add_table_index('preset_type');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Bookmarks Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_bookmarks');

$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('order_id', 0, 16000000);

$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Config Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_config');

$_CLASS['core_db']->add_table_field_char('config_name', 100);
$_CLASS['core_db']->add_table_field_char('config_value', 255);
$_CLASS['core_db']->add_table_field_int('is_dynamic', 0, 1);

$_CLASS['core_db']->add_table_index('config_name', 'primary');
$_CLASS['core_db']->add_table_index('is_dynamic');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Disallow Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_disallow');

$_CLASS['core_db']->add_table_field_int('disallow_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('disallow_username', 30);

$_CLASS['core_db']->add_table_index('disallow_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Drafts Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_drafts');

$_CLASS['core_db']->add_table_field_int('draft_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('save_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_char('draft_subject', 50);
$_CLASS['core_db']->add_table_field_text('draft_message', 0, 16000000);

$_CLASS['core_db']->add_table_index('draft_id', 'primary');
$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('save_time');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Extensions Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_extensions');

$_CLASS['core_db']->add_table_field_int('extension_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('extension', 100);

$_CLASS['core_db']->add_table_index('extension_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Extension Groups Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_extension_groups');

$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('group_name', 20);
$_CLASS['core_db']->add_table_field_int('cat_id', 0, 200);
$_CLASS['core_db']->add_table_field_int('allow_group', 0, 1);
$_CLASS['core_db']->add_table_field_int('download_mode', 0, 1);
$_CLASS['core_db']->add_table_field_char('upload_icon', 100);
$_CLASS['core_db']->add_table_field_int('max_filesize', 0, 1000000000);
$_CLASS['core_db']->add_table_field_text('allowed_forums', 2000);
$_CLASS['core_db']->add_table_field_int('allow_in_pm', 0, 1);

$_CLASS['core_db']->add_table_index('group_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Forums Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_forums');

$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000, 0, true);
$_CLASS['core_db']->add_table_field_int('parent_id', 0, 16000);
$_CLASS['core_db']->add_table_field_int('left_id', 0, 16000);
$_CLASS['core_db']->add_table_field_int('right_id', 0, 16000);
$_CLASS['core_db']->add_table_field_text('forum_parents', 20000);
$_CLASS['core_db']->add_table_field_char('forum_name', 150);
$_CLASS['core_db']->add_table_field_text('forum_desc', 20000);
$_CLASS['core_db']->add_table_field_text('forum_rules', 20000);
$_CLASS['core_db']->add_table_field_char('forum_rules_link', 200);
$_CLASS['core_db']->add_table_field_char('forum_rules_flags', 50);
$_CLASS['core_db']->add_table_field_int('forum_rules_bbcode_bitfield',0 , 1000000000);
$_CLASS['core_db']->add_table_field_char('forum_rules_bbcode_uid', 5);
$_CLASS['core_db']->add_table_field_char('forum_link', 200);
$_CLASS['core_db']->add_table_field_char('forum_password', 40);
$_CLASS['core_db']->add_table_field_char('forum_password_encoding', 10);
$_CLASS['core_db']->add_table_field_char('forum_image', 200);
$_CLASS['core_db']->add_table_field_int('forum_topics_per_page', 0, 200);
$_CLASS['core_db']->add_table_field_int('forum_type', 0, 1);
$_CLASS['core_db']->add_table_field_int('forum_status', 0, 1);
$_CLASS['core_db']->add_table_field_int('forum_posts', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_topics', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_topics_real', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_last_post_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_last_poster_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_last_post_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_char('forum_last_poster_name', 50);
$_CLASS['core_db']->add_table_field_int('forum_flags', 0, 200);
$_CLASS['core_db']->add_table_field_int('display_on_index', 0, 1);
$_CLASS['core_db']->add_table_field_int('enable_indexing', 0, 1);
$_CLASS['core_db']->add_table_field_int('enable_icons', 0, 1);
$_CLASS['core_db']->add_table_field_int('prune_next', 0, $time_field);

//////
$_CLASS['core_db']->add_table_field_int('prune_days', 0, 200);
$_CLASS['core_db']->add_table_field_int('prune_viewed', 0, 200);
$_CLASS['core_db']->add_table_field_int('prune_freq', 0, 200);
//////

$_CLASS['core_db']->add_table_index('forum_id', 'primary');
$_CLASS['core_db']->add_table_index('left_id');
$_CLASS['core_db']->add_table_index('right_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums POsts Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_posts');

$_CLASS['core_db']->add_table_field_int('post_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000);
$_CLASS['core_db']->add_table_field_int('right_id', 0, 16000);
$_CLASS['core_db']->add_table_field_int('poster_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('post_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_char('poster_ip', 20);
$_CLASS['core_db']->add_table_field_char('post_username', 50);
$_CLASS['core_db']->add_table_field_int('enable_bbcode', 0, 1);
$_CLASS['core_db']->add_table_field_int('enable_html', 0, 1);
$_CLASS['core_db']->add_table_field_int('enable_smilies', 0, 1);
$_CLASS['core_db']->add_table_field_int('enable_sig', 0, 1);
$_CLASS['core_db']->add_table_field_int('post_edit_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('post_edit_count', 0, 20000);
$_CLASS['core_db']->add_table_field_int('post_attachment', 0, 1);
$_CLASS['core_db']->add_table_field_char('post_subject', 50);
$_CLASS['core_db']->add_table_field_text('post_text', 10000000);
$_CLASS['core_db']->add_table_field_char('bbcode_uid', 10);
$_CLASS['core_db']->add_table_field_int('bbcode_bitfield', 0, 1000000000);
$_CLASS['core_db']->add_table_field_int('icon_id', 0, 200, 1);
$_CLASS['core_db']->add_table_field_int('enable_magic_url', 0, 1, 1);
$_CLASS['core_db']->add_table_field_int('post_approved', 0, 1, 1);
$_CLASS['core_db']->add_table_field_char('post_edit_reason', 255);
$_CLASS['core_db']->add_table_field_int('post_edit_user', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('post_edit_locked', 0, 1);
$_CLASS['core_db']->add_table_field_int('post_reported', 0, 1);
$_CLASS['core_db']->add_table_field_char('post_encoding', 10, 'utf-8'); // to be removed
$_CLASS['core_db']->add_table_field_char('post_checksum', 32);

$_CLASS['core_db']->add_table_index('post_id', 'primary');
$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('poster_id');
$_CLASS['core_db']->add_table_index('post_approved');
$_CLASS['core_db']->add_table_index('post_time');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Topics Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_topics');

$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('topic_title', 50);
$_CLASS['core_db']->add_table_field_int('topic_poster', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('topic_views', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_replies', 0, 16000000);  
$_CLASS['core_db']->add_table_field_int('topic_status', 0, 1);
$_CLASS['core_db']->add_table_field_int('topic_type', 0, 1);
$_CLASS['core_db']->add_table_field_int('topic_last_post_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_first_post_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_moved_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('icon_id', 0, 200);

$_CLASS['core_db']->add_table_field_int('topic_attachment', 0, 1);
$_CLASS['core_db']->add_table_field_int('topic_approved', 0, 1, 1);
$_CLASS['core_db']->add_table_field_int('topic_reported', 0, 1); 
$_CLASS['core_db']->add_table_field_int('topic_time_limit', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('topic_replies_real', 16000000);
$_CLASS['core_db']->add_table_field_char('topic_first_poster_name', 50);
$_CLASS['core_db']->add_table_field_int('topic_last_poster_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('topic_last_poster_name', 50);
$_CLASS['core_db']->add_table_field_int('topic_last_post_time', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('topic_last_view_time', 0, $time_field);

$_CLASS['core_db']->add_table_field_int('topic_bumped', 0, 1);
$_CLASS['core_db']->add_table_field_int('topic_bumper', 0, 16000000);
  
$_CLASS['core_db']->add_table_field_char('poll_title', 100);
$_CLASS['core_db']->add_table_field_int('poll_start', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('poll_length', 0, $time_field); 
$_CLASS['core_db']->add_table_field_int('poll_max_options', 0, 200, 1);
$_CLASS['core_db']->add_table_field_int('poll_last_vote', 0, $time_field);
$_CLASS['core_db']->add_table_field_int('poll_vote_change', 0, 1);

$_CLASS['core_db']->add_table_index('topic_id', 'primary');
$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_moved_id');
$_CLASS['core_db']->add_table_index('topic_status');
$_CLASS['core_db']->add_table_index('topic_type');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Extensions Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_tracking');

$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('mark_time', 0, $time_field);

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Icons Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_icons');

$_CLASS['core_db']->add_table_field_int('icons_id', 0, 2000, 0, true);
$_CLASS['core_db']->add_table_field_char('icons_url', 50);
$_CLASS['core_db']->add_table_field_int('icons_width', 0, 2000);
$_CLASS['core_db']->add_table_field_int('icons_height', 0, 2000);
$_CLASS['core_db']->add_table_field_int('icons_order', 0, 2000);
$_CLASS['core_db']->add_table_field_int('display_on_posting', 0, 1);

$_CLASS['core_db']->add_table_index('icons_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Forums Watch Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_watch');

$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('notify_status', 0, 1);

$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

//
//Combine, maybe
//
/*
	Forums Topics Watch Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'topics_watch');

$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('notify_status', 0, 1);

$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Modules Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_modules');

$_CLASS['core_db']->add_table_field_int('module_id', 0, 20000, 0, true);
$_CLASS['core_db']->add_table_field_char('module_type', 3);
$_CLASS['core_db']->add_table_field_char('module_title', 50);
$_CLASS['core_db']->add_table_field_char('module_filename', 50);
$_CLASS['core_db']->add_table_field_int('module_order', 0, 200);
$_CLASS['core_db']->add_table_field_int('module_enabled', 0, 1);
$_CLASS['core_db']->add_table_field_text('module_subs', 2000);
$_CLASS['core_db']->add_table_field_char('module_acl', 200);


$_CLASS['core_db']->add_table_index('module_id', 'primary');
$_CLASS['core_db']->add_table_index('module_type');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Moderator Cache Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_moderator_cache');

$_CLASS['core_db']->add_table_field_int('forum_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('username', 50);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('groupname', 50);
$_CLASS['core_db']->add_table_field_int('display_on_index', 0, 1);

$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('display_on_index');

$_CLASS['core_db']->table_create('commit');

// Look into Forum polls by alpha 3 //

/*
	Forums Poll Results Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_poll_results');

$_CLASS['core_db']->add_table_field_int('poll_option_id', 0, 2000);
$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('poll_option_text', 255);
$_CLASS['core_db']->add_table_field_int('poll_option_total', 0, 16000000);

$_CLASS['core_db']->add_table_index('poll_option_id');
$_CLASS['core_db']->add_table_index('topic_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Poll Voters Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_poll_voters');

$_CLASS['core_db']->add_table_field_int('topic_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('poll_option_id', 0, 2000);
$_CLASS['core_db']->add_table_field_int('vote_user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('vote_user_ip', 40);

$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('vote_user_id');
$_CLASS['core_db']->add_table_index('vote_user_ip');

$_CLASS['core_db']->table_create('commit');

// Look into Forum polls by alpha 3 //

/*
	Forums Poll Voters Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_ranks');

$_CLASS['core_db']->add_table_field_int('rank_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('rank_title', 50);
$_CLASS['core_db']->add_table_field_int('rank_min', -1, 16000000);
$_CLASS['core_db']->add_table_field_int('rank_special', 0, 1);
$_CLASS['core_db']->add_table_field_char('rank_image', 100);

$_CLASS['core_db']->add_table_index('rank_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Zebra Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_zebra');

$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('zebra_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('friend', 0, 1);
$_CLASS['core_db']->add_table_field_int('foe', 0, 1);

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('zebra_id');

$_CLASS['core_db']->table_create('commit');

?>