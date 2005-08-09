<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

$install_prefix = 'test_';


$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."admins (user_id, group_id, section, status, options) VALUES (2, 0, '/all/', 1, '')");


$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."blocks (id, title, type, content, position, weight, active, file) VALUES (1, 'Control Panel', , 'block-Control_Panel.php')");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'site_name', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'site_url', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'start_date', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'default_theme', 'viperal')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'foot1', '<a href=\"feed.php?feed=rss1\" title=\"RSS 1.0 / RDF Feed\"><img alt=\"RSS 1.0 / RDF\" src=\"images/rss10_logo.gif\" /></a> <a href=\"feed.php?feed=rss2\" title=\"RSS 2.0 Feed\"><img alt=\"RSS 2.0\" src=\"images/rss20_logo.gif\" /></a> <a href=\"feed.php?feed=rss\" title=\"RSS 0.91 Feed\"><img alt=\"RSS 0.9\" src=\"images/rss090_logo.gif\" /></a>')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'foot2', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'default_lang', 'en')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'only_registered', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'default_dateformat', 'D M d, Y g:i a')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'default_timezone', '-5')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'link_optimization', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('global', 'index_page', 'News')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('maintenance', 'active', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('maintenance', 'start', '1118095980')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('maintenance', 'text', '<p align=\"center\"><b>Sorry we are currently updating this site.<br>\nPlease try again later</b></p>\n<p align=\"center\"><b>Viperal</b>\n</p>')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'allow_html_email', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'email_enable', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'email_function_name', 'mail')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'smtp_auth_type', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'smtp_username', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'smtp_password', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('email', 'smtp_host', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'require_activation', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'coppa_enable', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'coppa_fax', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'coppa_mail', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'enable_confirm', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'min_name_chars', '5')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'allow_name_chars', '[\\w_\\+\\. \\-\\[\\]]+')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'max_reg_attempts', '10')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'max_name_chars', '50')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'min_pass_chars', '5')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'max_pass_chars', '25')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'chg_passforce', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'allow_emailreuse', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('user', 'allow_namechange', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'path', '/')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'cookie_domain', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'cookie_name', 'viperal')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'cookie_path', '/')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'cookie_secure', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'limit_load', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'limit_sessions', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'ip_check', '4')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'browser_check', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'session_length', '3600')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'error_options', '0')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'optimize_rate', '1200000')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'optimize_last', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'site_domain', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'site_path', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('server', 'site_port', '')");




$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups (group_id, group_name, group_type, group_colour, group_legend, group_avatar, group_description) VALUES (1, 'GUESTS', 3, '', 0, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups (group_id, group_name, group_type, group_colour, group_legend, group_avatar, group_description) VALUES (2, 'REGISTERED', 3, '', 0, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups (group_id, group_name, group_type, group_colour, group_legend, group_avatar, group_description) VALUES (3, 'REGISTERED_COPPA', 3, '', 0, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups (group_id, group_name, group_type, group_colour, group_legend, group_avatar, group_description) VALUES (4, 'ADMINISTRATORS', 3, 'AA0000', 1, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups (group_id, group_name, group_type, group_colour, group_legend, group_avatar, group_description) VALUES (5, 'BOTS', 3, '9E8DA7', 1, '', '')");
// To be seperated
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups (group_id, group_name, group_type, group_colour, group_legend, group_avatar, group_description) VALUES (6, 'SUPER_MODERATORS', 3, '00AA00', 0, '', '')");


//Admin
#$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (2, 2, 2)");
#$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (3, 2, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (4, 2, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (6, 2, 2)");
// Anonymous
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (1, 1, 1)");
// Bots
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (5, 3, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (5, 4, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (5, 5, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (5, 6, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (5, 7, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."groups_users (group_id, user_id, user_status) VALUES (5, 8, 1)");


$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (1, 'blocks', 'Blocks', 0, 0, 0, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (2, 'messages', 'Messages', 0, 0, 0, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (3, 'system', 'System', 0, 0, 0, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (4, 'modules', 'Modules', 0, 0, 0, '', 'add:radio\r\n')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (5, 'News', 'News', 1, 1, 1, '0', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (6, 'Recommend_Us', 'Recommend Us', 1, 1, 1, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (7, 'Submit_News', 'Submit News', 1, 1, 1, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (8, 'Forums', 'Forums', 1, 1, 2, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (9, 'View_Online', 'View_Online', 1, 1, 1, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (10, 'Members_List', 'Members_List', 1, 1, 2, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (11, 'Control_Panel', 'Control_Panel', 1, 1, 2, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (12, 'Calender', 'Calender', 1, 1, 2, '', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."modules (id, name, title, active, type, sides, auth, admin_options) VALUES (13, 'Quick_Message', 'Quick_Message', 1, 1, 1, '', '')");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':D', 'grin.png', 'Very Happy', 19, 19, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':)', 'smile.png', 'Smile', 19, 19, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':(', 'sad.png', 'Sad', 19, 19, 3)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':o', 'surprised.png', 'Surprised', 19, 19, 4)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':eek:', 'surprised.png', 'Surprised', 19, 19, 4)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES ('8O', 'eek.png', 'Shocked', 19, 19, 5)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':?', 'confused.png', 'Confused', 19, 19, 6)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES ('8)', 'cool.png', 'Cool', 19, 19, 7)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':lol:', 'laugh.png', 'Laughing', 19, 19, 8)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':x', 'mad.png', 'Mad', 19, 19, 9)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':P', 'icon_razz.gif', 'Razz', 19, 19, 10)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':oops:', 'embarrassed.png', 'Embarassed', 19, 19, 11)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':cry:', 'cry.png', 'Crying or Very sad', 19, 19, 12)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':evil:', 'evil.png', 'Evil or Very Mad', 19, 19, 13)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':twisted:', 'twisted.png', 'Twisted Evil', 19, 19, 14)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':roll:', 'rolleyes.png', 'Rolling Eyes', 19, 19, 19)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (';)', 'wink.png', 'Wink', 19, 19, 16)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':!:', 'exclaim.png', 'Exclamation', 19, 19, 17)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':?:', 'question.png', 'Question', 19, 19, 18)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':idea:', 'idea.png', 'Idea', 19, 19, 19)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':arrow:', 'arrow.png', 'Arrow', 19, 19, 20)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':|', 'neutral.png', 'Neutral', 19, 19, 21)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."smilies (code, smiley_url, emotion, smiley_width, smiley_height, smiley_order) VALUES (':mrgreen:', 'mrgreen.png', 'Mr. Green', 19, 19, 22)");



$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username) VALUES (1, 2, 1, 'Anonymous')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_password, user_password_encoding, user_colour) VALUES (2, 3, 4, 'Admin', '21232f297a57a5a743894a0e4a801fc3', 'md5', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_ip, user_agent, user_colour) VALUES (3, 4, 5, 'Googlebot', '216.239.46.,64.68.8,66.249.64.', 'Googlebot/', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_ip, user_agent, user_colour) VALUES (4, 4, 5, 'MSN', '65.54.188.', 'msnbot/', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_ip, user_agent, user_colour) VALUES (5, 4, 5, 'Yahoo', '66.196.90.1', 'Yahoo! Slurp', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_ip, user_agent, user_colour) VALUES (6, 4, 5, 'Alexa', '66.28.250.,209.237.238.', 'ia_archiver', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_ip, user_agent, user_colour) VALUES (7, 4, 5, 'NaverBot', '218.145.25.,61.78.61.,61.78.61.', 'NaverBot-', '')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."users (user_id, user_type, group_id, username, user_ip, user_agent, user_colour) VALUES (8, 4, 5, 'Jetbot', '64.71.144.', 'Jetbot/', '')");




// Forums

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_list', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_read', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_post', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_reply', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_quote', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_edit', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_user_lock', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_delete', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_bump', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_poll', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_vote', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_votechg', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_announce', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_sticky', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_attach', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_download', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_icons', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_html', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_bbcode', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_smilies', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_img', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_flash', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_sigs', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_search', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_email', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_rate', 1");
#$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_print', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_ignoreflood', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_postcount', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_moderate', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_report', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local) VALUES ('f_subscribe', 1");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_edit', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_delete', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_move', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_lock', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_split', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_merge', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_approve', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_unrate', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_auth', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_ip', 1, 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_local, is_global) VALUES ('m_info', 1, 1");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_server', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_defaults', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_board', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_cookies', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_clearlogs', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_words', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_icons', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_bbcode', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_attach', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_email', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_styles', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_user', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_useradd', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_userdel', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_ranks', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_ban', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_names', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_group', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_groupadd', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_groupdel', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_forum', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_forumadd', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_forumdel', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_prune', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_auth', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_authmods', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_authadmins', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_authusers', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_authgroups', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_authdeps', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_backup', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_restore', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_search', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_events', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('a_cron', 1");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_sendemail', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_readpm', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_sendpm', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_sendim', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_hideonline', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_viewonline', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_viewprofile', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_chgavatar', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_chggrp', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_chgemail', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_chgname', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_chgpasswd', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_chgcensors', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_search', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_savedrafts', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_download', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_attach', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_sig', 1");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_attach', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_html', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_bbcode', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_smilies', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_download', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_report', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_edit', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_printpm', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_emailpm', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_forward', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_delete', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_img', 1");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."auth_options (auth_option, is_global) VALUES ('u_pm_flash', 1");


$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_attachments', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_bbcode', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_html', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_html_tags', 'b,i,u,pre', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_smilies', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_topic_notify', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_forum_notify', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_avatar_local', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_avatar_remote', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_avatar_upload', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_nocensors', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_cpf_viewtopic', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_bookmarks', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_cpf_viewprofile', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_disable_msg', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_email_form', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('cookie_secure', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_login_attempts', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('min_ratings', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('posts_per_page', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('topics_per_page', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('hot_threshold', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_path', 'images/avatars/upload', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_gallery_path', 'images/avatars/gallery', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smilies_path', 'images/smilies', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('icons_path', 'images/icons', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('upload_icons_path', 'images/upload_icons', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('ranks_path', 'images/ranks', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('email_enable', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_privmsg', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_cpf_memberlist', '0', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_online_time', '5', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_online', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_birthdays', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_moderators', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_jumpbox', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_search', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_search_upd', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_search_phr', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_db_lastread', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_db_track', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_onlinetrack', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('search_gc', '7200', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('queue_interval', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('version', '2.1.2', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_post_chars', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_post_smilies', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_quote_depth', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_sig_chars', '255', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_poll_options', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('min_search_chars', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_search_chars', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('edit_time', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('display_last_edited', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_email_sig', 'Thanks, The Management', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_email', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_contact', '', 0)");

// to be removed
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('email_function_name', 'mail', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('email_package_size', '50', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smtp_delivery', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smtp_host', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smtp_port', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smtp_auth_method', 'PLAIN', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smtp_username', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smtp_password', '', 0)");
////

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('jab_enable', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('jab_host', 'jabber.org', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('jab_port', '5222', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('jab_username', 'viperal_site', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('jab_password', '19site83', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('jab_resource', '', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('flood_interval', '15', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('bump_interval', '10h', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('search_interval', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_filesize', '6144', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_min_width', '20', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_min_height', '20', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_max_width', '90', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_max_height', '90', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('lastread', '432000', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('display_order', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_filesize', '262144', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_filesize_pm', '262144', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('attachment_quota', '52428800', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_attachments', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_attachments_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_pm_attach', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('upload_path', 'files', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_display_inlined', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('secure_downloads', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('secure_allow_deny', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('secure_allow_empty_referer', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_max_width', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_max_height', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_link_width', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_link_height', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_create_thumbnail', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_min_thumb_filesize', '12000', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_imagick', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('pm_max_boxes', '4', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('pm_max_msgs', '50', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_html_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_bbcode_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_smilies_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_download_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_report_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_online_guests', '1', 0)");
#$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('print_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('email_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('forward_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_img_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_flash_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('enable_pm_icons', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('pm_edit_time', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_mass_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('record_online_users', '2', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('record_online_date', '1117686190', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('newest_user_id', '334', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('newest_username', 'Grendal', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_users', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_posts', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_topics', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_files', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('upload_dir_size', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('search_last_gc', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('last_queue_run', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('full_folder_action', '2', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('bump_type', 'm', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('enable_karma', '1', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_img', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_hide_emails', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_bbcode', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_flash', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_html', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_img', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_smilies', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_post_urls', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('database_last_gc', '0', 1)");




















$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('quick_message', 'time', '1')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('quick_message', 'number', '25')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('quick_message', 'height', '200')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('quick_message', 'allow_anonymous', '2')");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (section, name, value) VALUES ('quick_message', 'maxlength', '150')");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."quick_message (id, user_id, user_name, ip, message, time) VALUES (1, 0, 'Site', '', 'Lets do this !', ".gmtime().")");
?>