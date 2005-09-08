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

$install_prefix = 'test_';

function field_unix_time($name, $null = false)
{
	global $_CLASS;

	$_CLASS['core_db']->add_table_field_int($name, array('max' => 200000000, 'null' => $null));
}

/*
	Admin Auth Table
*/

$_CLASS['core_db']->table_create('start', $install_prefix.'admins');

$_CLASS['core_db']->add_table_field_char('admin_section', 100);

$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('admin_status', array('max' => 10));
$_CLASS['core_db']->add_table_field_text('admin_options', 60000, true);

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');
$_CLASS['core_db']->add_table_index('admin_status');

$_CLASS['core_db']->table_create('commit');

/*
	Blocks Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'blocks');

$_CLASS['core_db']->add_table_field_int('block_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('block_title', 100);
$_CLASS['core_db']->add_table_field_int('block_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('block_status', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('block_position', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('block_order', array('max' => 200));
field_unix_time('block_starts', true);
field_unix_time('block_expires', true);
$_CLASS['core_db']->add_table_field_text('block_content', 60000, true);
$_CLASS['core_db']->add_table_field_char('block_file', 255, true);
$_CLASS['core_db']->add_table_field_char('block_rss_url', 255, true);
$_CLASS['core_db']->add_table_field_int('block_rss_rate', array('min' => -1, 'max' => 60000, 'null' => true));
field_unix_time('block_rss_expires', true);
$_CLASS['core_db']->add_table_field_text('block_auth', 60000, true);

$_CLASS['core_db']->add_table_index('block_id', 'primary');
$_CLASS['core_db']->add_table_index('block_type');
$_CLASS['core_db']->add_table_index('block_position');

$_CLASS['core_db']->table_create('commit');

/*
	Config Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'config');

$_CLASS['core_db']->add_table_field_char('config_section', 20);
$_CLASS['core_db']->add_table_field_char('config_name', 20);
$_CLASS['core_db']->add_table_field_text('config_value', 60000);
$_CLASS['core_db']->add_table_field_int('config_cache', array('max' => 1, 'null' => true));

$_CLASS['core_db']->add_table_index(array('config_section', 'config_name'), 'primary');
$_CLASS['core_db']->add_table_index('config_cache');

$_CLASS['core_db']->table_create('commit');


/*
	Groups Table
*/

$_CLASS['core_db']->table_create('start', $install_prefix.'groups');

// this section needs updating, with null fields
$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('group_name', 50);
$_CLASS['core_db']->add_table_field_int('group_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('group_status', array('max' => 10));

$_CLASS['core_db']->add_table_field_int('group_rank', array('max' => 2000, 'null' => true));
$_CLASS['core_db']->add_table_field_char('group_colour', 6, true);
$_CLASS['core_db']->add_table_field_char('group_avatar', 100, true);
$_CLASS['core_db']->add_table_field_int('group_avatar_type', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_int('group_avatar_width', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_int('group_avatar_height', array('max' => 200, 'null' => true));

$_CLASS['core_db']->add_table_field_int('group_sig_chars', array('max' => 160000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('group_receive_pm', array('max' => 10, 'null' => true));
$_CLASS['core_db']->add_table_field_int('group_message_limit', array('max' => 10000, 'null' => true));

$_CLASS['core_db']->add_table_field_char('group_description', 255);
$_CLASS['core_db']->add_table_field_int('group_display', array('max' => 10, 'null' => true)); // temp null
$_CLASS['core_db']->add_table_field_int('group_legend', array('max' => 10));

$_CLASS['core_db']->add_table_index('group_id', 'primary');
$_CLASS['core_db']->add_table_index('group_display');
$_CLASS['core_db']->add_table_index('group_legend');

$_CLASS['core_db']->table_create('commit');

/*
	Groups Members Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'groups_members');

$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('member_status', array('max' => 16000000));

$_CLASS['core_db']->add_table_index('group_id');
$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('member_status');

$_CLASS['core_db']->table_create('commit');

/*
	Modules Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'modules');

$_CLASS['core_db']->add_table_field_int('module_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('module_name', 100);
$_CLASS['core_db']->add_table_field_char('module_title', 100, true);
$_CLASS['core_db']->add_table_field_int('module_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('module_status', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('module_sides', array('max' => 10, 'null' => true));
$_CLASS['core_db']->add_table_field_text('module_auth', 60000, true);
$_CLASS['core_db']->add_table_field_text('module_auth_options', 60000, true);
$_CLASS['core_db']->add_table_field_text('module_admin_options', 6000, true);

$_CLASS['core_db']->add_table_index('module_id', 'primary');
$_CLASS['core_db']->add_table_index('module_name', 'unique');

$_CLASS['core_db']->table_create('commit');

/*
	Sessions Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'sessions');

$_CLASS['core_db']->add_table_field_char('session_id', 40);
$_CLASS['core_db']->add_table_field_int('session_user_id', array('max' => 16000000));
field_unix_time('session_last_visit');
field_unix_time('session_start');
field_unix_time('session_time');
$_CLASS['core_db']->add_table_field_char('session_ip', 18);
$_CLASS['core_db']->add_table_field_char('session_browser', 255);
$_CLASS['core_db']->add_table_field_char('session_page', 100);
$_CLASS['core_db']->add_table_field_char('session_url', 255);
$_CLASS['core_db']->add_table_field_int('session_user_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('session_admin', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('session_hidden', array('max' => 10));

$_CLASS['core_db']->add_table_field_text('session_data', 60000);
$_CLASS['core_db']->add_table_field_text('session_auth', 60000);

$_CLASS['core_db']->add_table_index('session_id', 'primary');
$_CLASS['core_db']->add_table_index('session_time');
$_CLASS['core_db']->add_table_index('session_user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Sessions Auto login Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'sessions_auto_login');

$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('auto_login_browser', 255);
$_CLASS['core_db']->add_table_field_char('auto_login_code', 40);
field_unix_time('auto_login_time');

$_CLASS['core_db']->add_table_index(array('user_id', 'auto_login_code'), 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Smiles Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'smilies');

$_CLASS['core_db']->add_table_field_int('smiley_id', array('max' => 16000000, 'auto_increment' => true));

$_CLASS['core_db']->add_table_field_char('smiley_code', 10);
$_CLASS['core_db']->add_table_field_char('smiley_src', 200);
$_CLASS['core_db']->add_table_field_char('smiley_description', 50);

$_CLASS['core_db']->add_table_field_int('smiley_width', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('smiley_height', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('smiley_order', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('smiley_type', array('max' => 10));

$_CLASS['core_db']->add_table_index('smiley_id', 'primary');
$_CLASS['core_db']->add_table_index('smiley_type');

$_CLASS['core_db']->table_create('commit');

/*

`user_unread_privmsg` tinyint(4) unsigned NOT NULL default '0',
*/

$_CLASS['core_db']->table_create('start', $install_prefix.'users');

$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('username', 80);
$_CLASS['core_db']->add_table_field_char('user_password', 40);
$_CLASS['core_db']->add_table_field_char('user_password_encoding', 10);
$_CLASS['core_db']->add_table_field_char('user_email', 100);
$_CLASS['core_db']->add_table_field_int('user_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('user_status', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('user_group', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('user_ip', 255);
$_CLASS['core_db']->add_table_field_char('user_agent', 255, true);
$_CLASS['core_db']->add_table_field_char('user_birthday', 10, true);
$_CLASS['core_db']->add_table_field_text('user_data', 6000, true);

$_CLASS['core_db']->add_table_field_char('user_act_key', 10, true);
$_CLASS['core_db']->add_table_field_char('user_new_password', 40, true);
$_CLASS['core_db']->add_table_field_char('user_new_password_encoding', 10, true);

field_unix_time('user_reg_date');
field_unix_time('user_last_visit', true);

$_CLASS['core_db']->add_table_field_int('user_dst',  array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_char('user_lang', 10, true);
$_CLASS['core_db']->add_table_field_char('user_time_format', 20, true);
$_CLASS['core_db']->add_table_field_int('user_timezone', array('min' => -43200, 'max' => 46800, 'null' => true));
$_CLASS['core_db']->add_table_field_char('user_theme', 60, true);
$_CLASS['core_db']->add_table_field_char('user_colour', 6, true);

$_CLASS['core_db']->add_table_field_int('user_allow_viewonline', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('user_allow_viewemail', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('user_allow_massemail', array('max' => 1));

$_CLASS['core_db']->add_table_field_int('user_new_privmsg', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('user_unread_privmsg', array('max' => 1, 'null' => true));

$_CLASS['core_db']->add_table_field_text('user_sig', 60000, true);
$_CLASS['core_db']->add_table_field_char('user_from', 100, true);
$_CLASS['core_db']->add_table_field_char('user_icq', 15, true);
$_CLASS['core_db']->add_table_field_char('user_aim', 255, true);
$_CLASS['core_db']->add_table_field_char('user_yim', 255, true);
$_CLASS['core_db']->add_table_field_char('user_msnm', 255, true);
$_CLASS['core_db']->add_table_field_char('user_jabber', 255, true);
$_CLASS['core_db']->add_table_field_char('user_website', 255, true);
$_CLASS['core_db']->add_table_field_char('user_interests', 255, true);
$_CLASS['core_db']->add_table_field_char('user_occ', 255, true);

// look at these
//$_CLASS['core_db']->add_table_field_int('user_message_limit', 0, 200);
//$_CLASS['core_db']->add_table_field_int('user_message_rules', 0, 1);
//$_CLASS['core_db']->add_table_field_int('user_full_folder', -1array('max' => 16000000), -3);
//$_CLASS['core_db']->add_table_field_int('user_attachsig', 0, 1, 1);
///

$_CLASS['core_db']->add_table_field_int('user_notify', array('max' => 1, 'null' => true));
//$_CLASS['core_db']->add_table_field_int('user_notify_pm', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('user_notify_type', array('max' => 10, 'null' => true));

$_CLASS['core_db']->add_table_field_int('user_allow_pm', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('user_allow_email', array('max' => 1));

$_CLASS['core_db']->add_table_field_char('user_sig_bbcode_uid', 5, true);
$_CLASS['core_db']->add_table_field_int('user_sig_bbcode_bitfield', array('max' => 1600, 'null' => true));

$_CLASS['core_db']->add_table_field_int('user_topic_show_days', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_char('user_topic_sortby_type', 1, true);
$_CLASS['core_db']->add_table_field_char('user_topic_sortby_dir', 1, true);

$_CLASS['core_db']->add_table_field_int('user_post_show_days', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_char('user_post_sortby_type', 1, true);
$_CLASS['core_db']->add_table_field_char('user_post_sortby_dir', 1, true);

$_CLASS['core_db']->add_table_field_int('user_posts', array('max' => 16000000, 'null' => true));
field_unix_time('user_last_post_time', true);

$_CLASS['core_db']->add_table_field_text('user_permissions', 60000, true); // phpBBs rename user_forums_permissions

// these can be null I think
$_CLASS['core_db']->add_table_field_char('user_avatar', 200, true);
$_CLASS['core_db']->add_table_field_int('user_avatar_type', array('max' => 10, 'null' => true));
$_CLASS['core_db']->add_table_field_int('user_avatar_width', array('max' => 100, 'null' => true));
$_CLASS['core_db']->add_table_field_int('user_avatar_height', array('max' => 100, 'null' => true));

$_CLASS['core_db']->add_table_field_int('user_rank', array('max' => 16000000, 'null' => true));

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

$_CLASS['core_db']->add_table_field_int('attach_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('post_msg_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('poster_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('download_count', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('in_message', array('max' => 1));

$_CLASS['core_db']->add_table_field_text('comment', 200);
$_CLASS['core_db']->add_table_field_char('physical_filename', 255);
$_CLASS['core_db']->add_table_field_char('real_filename', 255);
$_CLASS['core_db']->add_table_field_int('thumbnail', array('max' => 1));
$_CLASS['core_db']->add_table_field_char('extension', 50);
$_CLASS['core_db']->add_table_field_char('mimetype', 100);
$_CLASS['core_db']->add_table_field_int('filesize', array('max' => 16000000));
field_unix_time('filetime');

$_CLASS['core_db']->add_table_index('attach_id', 'primary');
$_CLASS['core_db']->add_table_index('post_msg_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('poster_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Auth Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_auth');

$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('auth_option_id', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('auth_setting', array('max' => 1));

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('group_id');
$_CLASS['core_db']->add_table_index('auth_option_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Auth Options Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_auth_options');

$_CLASS['core_db']->add_table_field_int('auth_option_id', array('max' => 2000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('auth_option', 20);
// These 2 are useless, change to type
$_CLASS['core_db']->add_table_field_int('is_global', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('is_local', array('max' => 1, 'null' => true));

$_CLASS['core_db']->add_table_index('auth_option_id', 'primary');
$_CLASS['core_db']->add_table_index('auth_option', 'unique');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Auth Presets Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_auth_presets');

$_CLASS['core_db']->add_table_field_int('preset_id', array('max' => 16000));
$_CLASS['core_db']->add_table_field_int('preset_user_id', array('max' => 16000000));
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

$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('order_id', array('max' => 16000000));

$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Config Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_config');

$_CLASS['core_db']->add_table_field_char('config_name', 100);
$_CLASS['core_db']->add_table_field_char('config_value', 255);
$_CLASS['core_db']->add_table_field_int('is_dynamic', array('max' => 1));

$_CLASS['core_db']->add_table_index('config_name', 'primary');
$_CLASS['core_db']->add_table_index('is_dynamic');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Disallow Table
*/
/*
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_disallow');

$_CLASS['core_db']->add_table_field_int('disallow_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('disallow_username', 30);

$_CLASS['core_db']->add_table_index('disallow_id', 'primary');

$_CLASS['core_db']->table_create('commit');
*/

/*
	Forums Drafts Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_drafts');

$_CLASS['core_db']->add_table_field_int('draft_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000));
field_unix_time('save_time');
$_CLASS['core_db']->add_table_field_char('draft_subject', 50);
$_CLASS['core_db']->add_table_field_text('draft_message',  16000000);

$_CLASS['core_db']->add_table_index('draft_id', 'primary');
$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('save_time');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Extensions Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_extensions');

$_CLASS['core_db']->add_table_field_int('extension_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('extension', 100);

$_CLASS['core_db']->add_table_index('extension_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Extension Groups Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_extension_groups');

$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('group_name', 20);
$_CLASS['core_db']->add_table_field_int('cat_id', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('allow_group', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('download_mode', array('max' => 1));
$_CLASS['core_db']->add_table_field_char('upload_icon', 100);
$_CLASS['core_db']->add_table_field_int('max_filesize', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_text('allowed_forums', 2000);
$_CLASS['core_db']->add_table_field_int('allow_in_pm', array('max' => 1));

$_CLASS['core_db']->add_table_index('group_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Forums Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_forums');

$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('parent_id', array('max' => 16000));
$_CLASS['core_db']->add_table_field_int('left_id', array('max' => 16000));
$_CLASS['core_db']->add_table_field_int('right_id', array('max' => 16000));
$_CLASS['core_db']->add_table_field_text('forum_parents', 20000);
$_CLASS['core_db']->add_table_field_char('forum_name', 150);
$_CLASS['core_db']->add_table_field_text('forum_desc', 20000);
$_CLASS['core_db']->add_table_field_text('forum_rules', 20000, true);
$_CLASS['core_db']->add_table_field_char('forum_rules_link', 200, true);
$_CLASS['core_db']->add_table_field_char('forum_rules_flags', 50, true);
$_CLASS['core_db']->add_table_field_int('forum_rules_bbcode_bitfield', array('max' => 1000000000, 'null' => true));
$_CLASS['core_db']->add_table_field_char('forum_rules_bbcode_uid', 5, true);
$_CLASS['core_db']->add_table_field_char('forum_link', 200, true);
$_CLASS['core_db']->add_table_field_char('forum_password', 40, true);
$_CLASS['core_db']->add_table_field_char('forum_password_encoding', 10, true);
$_CLASS['core_db']->add_table_field_char('forum_image', 200, true);
$_CLASS['core_db']->add_table_field_int('forum_topics_per_page', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forum_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('forum_status', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('forum_posts', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forum_topics', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forum_topics_real', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forum_last_post_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forum_last_poster_id', array('max' => 16000000, 'null' => true));
field_unix_time('forum_last_post_time', true);
$_CLASS['core_db']->add_table_field_char('forum_last_poster_name', 50, true);
$_CLASS['core_db']->add_table_field_int('forum_flags', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('display_on_index', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_indexing', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_icons', array('max' => 1));
field_unix_time('prune_next', true);

//////

$_CLASS['core_db']->add_table_field_int('enable_prune', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('prune_days', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('prune_viewed', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('prune_freq', array('max' => 200));
//////

$_CLASS['core_db']->add_table_index('forum_id', 'primary');
$_CLASS['core_db']->add_table_index('left_id');
$_CLASS['core_db']->add_table_index('right_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums POsts Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_posts');

$_CLASS['core_db']->add_table_field_int('post_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000));
$_CLASS['core_db']->add_table_field_int('right_id', array('max' => 16000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('poster_id', array('max' => 16000000));
field_unix_time('post_time');
$_CLASS['core_db']->add_table_field_char('poster_ip', 20);
$_CLASS['core_db']->add_table_field_char('post_username', 50);
$_CLASS['core_db']->add_table_field_int('enable_bbcode', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_html', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_smilies', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_sig', array('max' => 1));
field_unix_time('post_edit_time', true);
$_CLASS['core_db']->add_table_field_int('post_edit_count', array('max' => 20000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('post_attachment', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_char('post_subject', 50);
$_CLASS['core_db']->add_table_field_text('post_text', 10000000);
$_CLASS['core_db']->add_table_field_char('bbcode_uid', 10);
$_CLASS['core_db']->add_table_field_int('bbcode_bitfield', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('icon_id', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('enable_magic_url', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('post_approved', array('max' => 1));
$_CLASS['core_db']->add_table_field_char('post_edit_reason', 255, true);
$_CLASS['core_db']->add_table_field_int('post_edit_user', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('post_edit_locked', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('post_reported', array('max' => 1, 'null' => true));
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

$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('topic_title', 50);
$_CLASS['core_db']->add_table_field_int('topic_poster', array('max' => 16000000));
field_unix_time('topic_time');
$_CLASS['core_db']->add_table_field_int('topic_views', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('topic_replies', array('max' => 16000000, 'null' => true));  
$_CLASS['core_db']->add_table_field_int('topic_status', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('topic_type', array('max' => 1));

// we should set this on topic creation
$_CLASS['core_db']->add_table_field_int('topic_last_post_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('topic_first_post_id', array('max' => 16000000, 'null' => true));

$_CLASS['core_db']->add_table_field_int('topic_moved_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('icon_id', array('max' => 200));

$_CLASS['core_db']->add_table_field_int('topic_attachment', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('topic_approved', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('topic_reported', array('max' => 1, 'null' => true)); 
field_unix_time('topic_time_limit');
$_CLASS['core_db']->add_table_field_int('topic_replies_real', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_char('topic_first_poster_name', 50);
$_CLASS['core_db']->add_table_field_int('topic_last_poster_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_char('topic_last_poster_name', 50, true);
field_unix_time('topic_last_post_time', true);
field_unix_time('topic_last_view_time', true);

$_CLASS['core_db']->add_table_field_int('topic_bumped', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('topic_bumper', array('max' => 16000000, 'null' => true));
  
$_CLASS['core_db']->add_table_field_char('poll_title', 100, true);
field_unix_time('poll_start', true);
field_unix_time('poll_length', true); 
$_CLASS['core_db']->add_table_field_int('poll_max_options', array('max' => 200, 'null' => true));
field_unix_time('poll_last_vote', true);
$_CLASS['core_db']->add_table_field_int('poll_vote_change', array('max' => 1, 'null' => true));

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

$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
field_unix_time('mark_time');

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Icons Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_icons');

$_CLASS['core_db']->add_table_field_int('icons_id', array('max' => 2000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('icons_url', 50);
$_CLASS['core_db']->add_table_field_int('icons_width', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('icons_height', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('icons_order', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('display_on_posting', array('max' => 1));

$_CLASS['core_db']->add_table_index('icons_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Forums Watch Table
*/

$_CLASS['core_db']->table_create('start', $install_prefix.'forums_watch');

$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('notify_type', array('max' => 10));
$_CLASS['core_db']->add_table_field_int('notify_status', array('max' => 10));

$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Modules Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_modules');

$_CLASS['core_db']->add_table_field_int('module_id', array('max' => 2000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('module_type', 3);
$_CLASS['core_db']->add_table_field_char('module_title', 50);
$_CLASS['core_db']->add_table_field_char('module_filename', 50);
$_CLASS['core_db']->add_table_field_int('module_order', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('module_enabled', array('max' => 1));
$_CLASS['core_db']->add_table_field_text('module_subs', 2000);
$_CLASS['core_db']->add_table_field_char('module_acl', 200);


$_CLASS['core_db']->add_table_index('module_id', 'primary');
$_CLASS['core_db']->add_table_index('module_type');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Moderator Cache Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_moderator_cache');

$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('username', 50);
$_CLASS['core_db']->add_table_field_int('group_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('groupname', 50);
$_CLASS['core_db']->add_table_field_int('display_on_index', array('max' => 1));

$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('display_on_index');

$_CLASS['core_db']->table_create('commit');

// Look into Forum polls by alpha 3 //

/*
	Forums Poll Results Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_poll_results');

$_CLASS['core_db']->add_table_field_int('poll_option_id', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('poll_option_text', 255);
$_CLASS['core_db']->add_table_field_int('poll_option_total', array('max' => 16000000));

$_CLASS['core_db']->add_table_index('poll_option_id');
$_CLASS['core_db']->add_table_index('topic_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Poll Voters Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_poll_voters');

$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('poll_option_id', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('vote_user_id', array('max' => 16000000));
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

$_CLASS['core_db']->add_table_field_int('rank_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('rank_title', 50);
$_CLASS['core_db']->add_table_field_int('rank_min', array('min' => -1, 'max' => 16000000));
$_CLASS['core_db']->add_table_field_int('rank_special', array('max' => 1));
$_CLASS['core_db']->add_table_field_char('rank_image', 100);

$_CLASS['core_db']->add_table_index('rank_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Words Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_search_wordlist');

$_CLASS['core_db']->add_table_field_int('word_id', array('max' => 3000000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('word_text', 100);
$_CLASS['core_db']->add_table_field_int('word_common', array('max' => 1));

$_CLASS['core_db']->add_table_index('word_id', 'primary');
$_CLASS['core_db']->add_table_index('word_text');

$_CLASS['core_db']->table_create('commit');

/*
	Forums search_wordmatch Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_search_wordmatch');

$_CLASS['core_db']->add_table_field_int('post_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('word_id', array('max' => 3000000000));
$_CLASS['core_db']->add_table_field_int('title_match', array('max' => 1));

$_CLASS['core_db']->add_table_index('word_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Words Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_words');

$_CLASS['core_db']->add_table_field_int('word_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_char('word', 100);
$_CLASS['core_db']->add_table_field_char('replacement', 100);

$_CLASS['core_db']->add_table_index('word_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Zebra Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_zebra');

$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('zebra_id', array('max' => 16000000));

// conbine these 2
$_CLASS['core_db']->add_table_field_int('friend', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('foe', array('max' => 1, 'null' => true));

$_CLASS['core_db']->add_table_index('user_id');
$_CLASS['core_db']->add_table_index('zebra_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Log Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_log');

$_CLASS['core_db']->add_table_field_int('log_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('log_type', array('max' => 200));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('forum_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('topic_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('reportee_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_char('log_ip', 40);
field_unix_time('log_time');
$_CLASS['core_db']->add_table_field_text('log_operation', 60000);
$_CLASS['core_db']->add_table_field_text('log_data', 60000);

$_CLASS['core_db']->add_table_index('log_id', 'primary');
$_CLASS['core_db']->add_table_index('log_type');
$_CLASS['core_db']->add_table_index('forum_id');
$_CLASS['core_db']->add_table_index('topic_id');
$_CLASS['core_db']->add_table_index('reportee_id');
$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

/*
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_privmsgs');

$_CLASS['core_db']->add_table_field_int('msg_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('root_level', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('author_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('icon_id', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_char('author_ip', 40);
field_unix_time('message_time');
$_CLASS['core_db']->add_table_field_int('message_reported', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('enable_bbcode', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_html', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_smilies', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_magic_url', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('enable_sig', array('max' => 1));

$_CLASS['core_db']->add_table_field_char('message_subject', 60);
$_CLASS['core_db']->add_table_field_text('message_text', 10000000);
$_CLASS['core_db']->add_table_field_char('message_edit_reason', 100, true);
$_CLASS['core_db']->add_table_field_int('message_edit_user', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_char('message_checksum', 11);
$_CLASS['core_db']->add_table_field_int('message_attachment', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('bbcode_bitfield', 1000000000);
$_CLASS['core_db']->add_table_field_char('bbcode_uid', 5);
field_unix_time('message_edit_time', true);
$_CLASS['core_db']->add_table_field_int('message_edit_count', array('max' => 200, 'null' => true));
$_CLASS['core_db']->add_table_field_text('to_address', 1000000);
$_CLASS['core_db']->add_table_field_text('bcc_address', 1000000);

$_CLASS['core_db']->add_table_index('msg_id', 'primary');
$_CLASS['core_db']->add_table_index('author_ip');
$_CLASS['core_db']->add_table_index('message_time');
$_CLASS['core_db']->add_table_index('author_id');
$_CLASS['core_db']->add_table_index('root_level');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Log Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_privmsgs_folder');

$_CLASS['core_db']->add_table_field_int('folder_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('folder_name', 40);
$_CLASS['core_db']->add_table_field_int('pm_count', array('max' => 16000000, 'null' => true));

$_CLASS['core_db']->add_table_index('folder_id', 'primary');
$_CLASS['core_db']->add_table_index('user_id');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Log Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_privmsgs_rules');

$_CLASS['core_db']->add_table_field_int('rule_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('rule_check', array('max' => 2000));
$_CLASS['core_db']->add_table_field_int('rule_connection', array('max' => 2000));
$_CLASS['core_db']->add_table_field_char('rule_string', 255);
$_CLASS['core_db']->add_table_field_int('rule_user_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('rule_group_id', array('max' => 16000000, 'null' => true));
$_CLASS['core_db']->add_table_field_int('rule_action', array('max' => 2000));
$_CLASS['core_db']->add_table_field_text('rule_folder_id', 16000000);

$_CLASS['core_db']->add_table_index('rule_id', 'primary');

$_CLASS['core_db']->table_create('commit');

/*
	Forums Log Table
*/
$_CLASS['core_db']->table_create('start', $install_prefix.'forums_privmsgs_to');

$_CLASS['core_db']->add_table_field_int('msg_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('user_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('author_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_int('deleted', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('msg_new', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('unread', array('max' => 1));
$_CLASS['core_db']->add_table_field_int('replied', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('marked', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('forwarded', array('max' => 1, 'null' => true));
$_CLASS['core_db']->add_table_field_int('folder_id', array('max' => 16000000));

$_CLASS['core_db']->add_table_index('msg_id');
$_CLASS['core_db']->add_table_index(array('user_id', 'folder_id'));

$_CLASS['core_db']->table_create('commit');

?>