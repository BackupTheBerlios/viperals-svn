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
$time_feild = $time_feild; // max Friday January 19th 2038

/*
	Admin Auth Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'admins');

$_CLASS['core_db']->add_table_field_int('auth_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('section', 100);
$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_int('status', 0, 1);
$_CLASS['core_db']->add_table_field_text('options', 60000);

$_CLASS['core_db']->add_table_index('auth_id', 'primary');
$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');
// To much I say, may only be called in the admin menu.. every now and then
// $_CLASS['core_db']->add_table_index('section');

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
$_CLASS['core_db']->add_table_field_int('start', 0, $time_feild);
$_CLASS['core_db']->add_table_field_int('expires', 0, $time_feild);
$_CLASS['core_db']->add_table_field_text('content', 60000);
$_CLASS['core_db']->add_table_field_char('file', 255);
$_CLASS['core_db']->add_table_field_text('auth', 60000);
$_CLASS['core_db']->add_table_field_char('rss_url', 255);
$_CLASS['core_db']->add_table_field_int('rss_rate', 0, 60000);
$_CLASS['core_db']->add_table_field_int('rss_expires', 0, $time_feild);

$_CLASS['core_db']->add_table_index('id', 'primary');
$_CLASS['core_db']->add_table_index('type');
$_CLASS['core_db']->add_table_index('position');

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
$_CLASS['core_db']->add_table_field_int('homepage', 0, 10); // to be removed late on
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
$_CLASS['core_db']->add_table_field_int('session_last_visit', 0, $time_feild);
$_CLASS['core_db']->add_table_field_int('session_start', 0, $time_feild);
$_CLASS['core_db']->add_table_field_int('session_time', 0, $time_feild);
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
	Groups Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'user_group');

$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('user_id', 100);
$_CLASS['core_db']->add_table_field_int('user_status', 0, 1);

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');

$_CLASS['core_db']->table_create('commit');


/*
$_CLASS['core_db']->table_create('start', $install_prefix.'users');

$_CLASS['core_db']->add_table_field_int('user_id', 0, 16000000, 0, true);
$_CLASS['core_db']->add_table_field_char('username', 80);
$_CLASS['core_db']->add_table_field_char('user_password', 40);
$_CLASS['core_db']->add_table_field_char('user_password_encoding', 10);
$_CLASS['core_db']->add_table_field_int('user_type', 0, 1);
$_CLASS['core_db']->add_table_field_int('group_id', 0, 16000000);
$_CLASS['core_db']->add_table_field_char('user_ip', 255);
$_CLASS['core_db']->add_table_field_char('user_agent', 255);
$_CLASS['core_db']->add_table_field_int('user_regdate', 0, $time_feild);
$_CLASS['core_db']->add_table_field_text('user_permissions', 60000); // phpBBs
$_CLASS['core_db']->add_table_field_char('user_email', 100);
$_CLASS['core_db']->add_table_field_char('user_birthday', 10);
$_CLASS['core_db']->add_table_field_int('user_last_visit', 0, $time_feild);
$_CLASS['core_db']->add_table_field_int('user_last_post_time', 0, $time_feild);
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

$_CLASS['core_db']->add_table_field_int('user_posts', 16000000);

$_CLASS['core_db']->add_table_field_char('user_icq', 15);
$_CLASS['core_db']->add_table_field_char('user_aim', 255);
$_CLASS['core_db']->add_table_field_char('user_yim', 255);
$_CLASS['core_db']->add_table_field_char('user_msnm', 255);
$_CLASS['core_db']->add_table_field_char('user_jabber', 255);
$_CLASS['core_db']->add_table_field_char('user_website', 255);

$_CLASS['core_db']->add_table_index('user_id', 'primary');
$_CLASS['core_db']->add_table_index('username', 'unique');
$_CLASS['core_db']->add_table_index('session_user_id');

$_CLASS['core_db']->table_create('commit');
*/


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
$_CLASS['core_db']->add_table_field_int('filetime', 0, $time_feild);

$_CLASS['core_db']->add_table_index('attach_id', 'primary');
$_CLASS['core_db']->add_table_index('post_msg_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('poster_id');

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
$_CLASS['core_db']->add_table_field_int('time', 0, $time_feild);

$_CLASS['core_db']->add_table_index('id', 'primary');
$_CLASS['core_db']->add_table_index('time');

$_CLASS['core_db']->table_create('commit');


?>