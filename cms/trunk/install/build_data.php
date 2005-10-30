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

$time = gmtime();

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_access (admin_section, user_id, admin_status) VALUES ('_all_', 2, 2)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'path_avatar_upload', 'images/avatars/upload', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'path_avatar_gallery', 'images/avatars/gallery', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'path_smilies', 'images/smilies', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'site_name', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_theme', 'viperal', 'string', 1)");
$content = $_CLASS['core_db']->escape('<a href="feed.php?feed=rss1" title="RSS 1.0 / RDF Feed"><img alt="RSS 1.0 / RDF" src="images/rss10_logo.gif" /></a> <a href="feed.php?feed=rss2" title="RSS 2.0 Feed"><img alt="RSS 2.0" src="images/rss20_logo.gif" /></a> <a href="feed.php?feed=rss" title="RSS 0.91 Feed"><img alt="RSS 0.9" src="images/rss090_logo.gif" /></a>');
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'foot1', '$content', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'foot2', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_lang', 'en', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_dst', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'only_registered', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_dateformat', 'D M d, Y g:i a', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_timezone', '-5', 'float', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'link_optimization', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'index_page', 'contact', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('maintenance', 'active', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('maintenance', 'start', '', 'int', 1)");
$content = $_CLASS['core_db']->escape('<p align="center"><b>Sorry we are currently updating this site.<br>Please try again later</b></p>');
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('maintenance', 'text', '$content', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'allow_html_email', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'email_enable', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'email_function_name', 'mail', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp', 0, 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_host', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_port', '', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_username', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_password', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'site_email', 'none@none.com', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'allow_name_chars', '.*', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'enable_confirm', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'coppa_enable', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'coppa_fax', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'coppa_mail', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'min_name_chars', '5', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'max_reg_attempts', '10', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'max_name_chars', '50', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'min_pass_chars', '5', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'max_pass_chars', '25', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'allow_email_reuse', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'allow_name_change', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'newest_username', 'admin', 'string', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'newest_user_id', '2', 'int', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'activation', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'total_users', '1', 'int', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'password_encoding', 'md5', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'path', '/', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'cookie_domain', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'cookie_name', 'viperal', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'cookie_path', '/', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'limit_load', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'limit_sessions', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'ip_check', '4', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'session_length', '3600', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'error_options', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_secure', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_domain', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_path', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."config (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_port', '', 'int', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('GUESTS', 1, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('REGISTERED', 1, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('REGISTERED_COPPA', 1, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('ADMINISTRATORS', 1, 2, 'AA0000', 1, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('BOTS', 1, 2, '9E8DA7', 1, '')");
// To be seperated
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('SUPER_MODERATORS', 1, 2, '00AA00', 0, '')");


//Admin
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (2, 2, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (4, 2, 10)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (6, 2, 10)");
// Anonymous
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (1, 1, 2)");
// Bots
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (5, 3, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (5, 4, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (5, 5, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (5, 6, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (5, 7, 2)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."groups_members (group_id, user_id, member_status) VALUES (5, 8, 2)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."blocks (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('Contol Panel', 3, 2, 1, 1, 'block-Control_Panel.php')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."blocks (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('Modules', 0, 2, 1, 2, 'block-Modules.php')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."blocks (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('User', 0, 2, 1, 3, 'block-User.php')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."blocks (block_title, block_type, block_status, block_position, block_order, block_rss_url, block_rss_rate) VALUES ('php.net RSS Feed', 1, 2, 2, 1, 'http://www.php.net/news.rss', 7200)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."blocks (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('Theme Select', 0, 2, 2, 2, 'block-theme_select.php')");

$content = $_CLASS['core_db']->escape('<div style="text-align:center">Hello and welcome</div>');
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."blocks (block_title, block_type, block_status, block_position, block_order, block_content) VALUES ('Welcome', 4, 2, 5, 4, '$content')");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('blocks', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('groups', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('system', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('messages', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('modules', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('pages', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('smiles', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('users', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."admin_modules (module_name, module_status, module_type) VALUES ('groups', 2, 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('main', 2, 'front\r\nsubscribed\r\nbookmarks,cfg_allow_bookmarks\r\ndrafts')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('pm', 2, 'view_messages\r\ncompose\r\nunread\r\ndrafts\r\noptions')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('profile', 2,'profile_info\r\nreg_details\r\nsignature\r\navatar')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('prefs', 2, 'personal\r\nview\r\npost')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('zebra', 2, 'friends\r\nfoes')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('attachments', 1, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('groups', 2, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."control_panel_modules (module_name, module_status, module_subs) VALUES ('calender', 2,'month_view\r\nday_view\r\nadd_event')");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('articles', 0, 1, 102)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('contact', 0, 2, 102)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('calender', 0, 1, 98)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('control_panel', 0, 2, 98)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('forums', 0, 2, 98)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('members_list', 0, 2, 98)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."pages (page_name, page_type, page_status, page_blocks) VALUES ('quick_message', 0, 1, 102)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':D', 'grin.png', 'Very Happy', 19, 19, 1, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':)', 'smile.png', 'Smile', 19, 19, 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':(', 'sad.png', 'Sad', 19, 19, 3, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':o', 'surprised.png', 'Surprised', 19, 19, 4, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':eek:', 'surprised.png', 'Surprised', 19, 19, 4, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES ('8O', 'eek.png', 'Shocked', 19, 19, 5, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':?', 'confused.png', 'Confused', 19, 19, 6, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES ('8)', 'cool.png', 'Cool', 19, 19, 7, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':lol:', 'laugh.png', 'Laughing', 19, 19, 8, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':x', 'mad.png', 'Mad', 19, 19, 9, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':P', 'icon_razz.gif', 'Razz', 19, 19, 10, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':oops:', 'embarrassed.png', 'Embarassed', 19, 19, 11, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':cry:', 'cry.png', 'Crying or Very sad', 19, 19, 12, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':evil:', 'evil.png', 'Evil or Very Mad', 19, 19, 13, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':twisted:', 'twisted.png', 'Twisted Evil', 19, 19, 14, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':roll:', 'rolleyes.png', 'Rolling Eyes', 19, 19, 19, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (';)', 'wink.png', 'Wink', 19, 19, 16, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':!:', 'exclaim.png', 'Exclamation', 19, 19, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':?:', 'question.png', 'Question', 19, 19, 18, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':idea:', 'idea.png', 'Idea', 19, 19, 19, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':arrow:', 'arrow.png', 'Arrow', 19, 19, 20, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':|', 'neutral.png', 'Neutral', 19, 19, 21, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."smilies (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':mrgreen:', 'mrgreen.png', 'Mr. Green', 19, 19, 22, 0)");

$data = serialize(array(
	'viewimg' => 1,
	'viewflash' => 1,
	'viewsmilies' => 1,
	'viewsigs'	=> 1,
	'viewavatars' => 1,
	'viewcensors' => 1,

	'bbcode' => 1,
	'html'	=> 1,
	'smilies' => 1,
	'attachsig' => 1,
	'html'	=> 1,
));

$admin_data = $guest_data = $bot_data = $_CLASS['core_db']->escape($data);

$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (0, 2, 1, 'Anonymous', '', '', '', $time, '', 1, 0, 0, 0, 0, '$guest_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (1, 2, 4, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'md5', '', $time, '', 'AA0000', 1, 1, 1, 1, 1, '$admin_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Googlebot', '', '', '', $time, '216.239.46.,64.68.8.,66.249.64.,66.249.71.', 'Googlebot/', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'MSN', '', '', '', $time, '65.54.188.', 'msnbot/', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Yahoo', '', '', '', $time, '66.196.90.1', 'Yahoo! Slurp', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Alexa', '', '', '', $time, '66.28.250.,209.237.238.', 'ia_archiver', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'NaverBot', '', '', '', $time, '218.145.25.,61.78.61.,61.78.61.', 'NaverBot-', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$user_prefix."users (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Jetbot', '', '', '', $time, '64.71.144.', 'Jetbot/', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");

// Forums
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_list', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_read', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_post', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_reply', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_quote', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_edit', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_user_lock', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_delete', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_bump', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_poll', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_vote', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_votechg', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_announce', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_sticky', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_download', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_icons', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_html', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_bbcode', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_smilies', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_img', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_flash', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_sigs', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_search', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_email', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_rate', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_ignoreflood', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_postcount', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_moderate', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_report', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local) VALUES ('f_subscribe', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_edit', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_delete', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_move', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_lock', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_split', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_merge', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_approve', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_unrate', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_auth', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_ip', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_local, is_global) VALUES ('m_info', 1, 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_sendemail', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_readpm', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_sendpm', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_sendim', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_hideonline', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_viewonline', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_viewprofile', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_chgavatar', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_chggrp', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_chgemail', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_chgname', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_chgpasswd', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_chgcensors', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_search', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_savedrafts', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_download', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_sig', 1)");

// START: This should be replaced by beta
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_server', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_defaults', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_board', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_cookies', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_clearlogs', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_words', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_icons', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_bbsmiley_code', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_email', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_styles', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_user', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_useradd', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_userdel', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_ranks', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_ban', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_names', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_group', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_groupadd', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_groupdel', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_forum', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_forumadd', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_forumdel', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_prune', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_auth', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_authmods', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_authadmins', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_authusers', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_authgroups', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_authdeps', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_backup', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_restore', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_search', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_events', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('a_cron', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_html', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_bbsmiley_code', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_smilies', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_download', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_report', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_edit', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_printpm', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_emailpm', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_forward', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_delete', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_img', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth_options (auth_option, is_global) VALUES ('u_pm_flash', 1)");

// ADMINISTRATOR group
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth (group_id, forum_id, auth_option_id, auth_setting) SELECT 4, 0, auth_option_id, 1 FROM ".$table_prefix."forums_auth_options WHERE auth_option LIKE 'u_%'");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth (group_id, forum_id, auth_option_id, auth_setting) SELECT 4, 0, auth_option_id, 1 FROM ".$table_prefix."forums_auth_options WHERE auth_option LIKE 'a_%'");

// SUPER MODERATOR group
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth (group_id, forum_id, auth_option_id, auth_setting) SELECT 6, 0, auth_option_id, 1 FROM ".$table_prefix."forums_auth_options WHERE auth_option LIKE 'm_%'");

# REGISTERED/REGISTERED COPPA groups - common forum rights
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth  (group_id, forum_id, auth_option_id, auth_setting) SELECT 2, 0, auth_option_id, 1 FROM ".$table_prefix."forums_auth_options WHERE auth_option LIKE 'u_%' AND auth_option NOT IN ('u_chggrp', 'u_viewonline', 'u_chgname')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_auth  (group_id, forum_id, auth_option_id, auth_setting) SELECT 3, 0, auth_option_id, 1 FROM ".$table_prefix."forums_auth_options WHERE auth_option LIKE 'u_%' AND auth_option NOT IN ('u_chgcensors', 'u_chggrp', 'u_viewonline', 'u_chgname')");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_attachments', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_bbcode', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_html', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_html_tags', 'b,i,u,pre', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_smilies', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_topic_notify', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_forum_notify', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_avatar_local', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_avatar_remote', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_avatar_upload', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_nocensors', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_bookmarks', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_disable_msg', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_email_form', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_login_attempts', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('min_ratings', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('posts_per_page', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('topics_per_page', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('hot_threshold', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_path', 'images/avatars/upload', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_gallery_path', 'images/avatars/gallery', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('smilies_path', 'images/smilies', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('icons_path', 'images/icons', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('upload_icons_path', 'images/upload_icons', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('ranks_path', 'images/ranks', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('email_enable', '1', 0)");// maybe keep
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_privmsg', '1', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_online_time', '5', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_online', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_birthdays', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_moderators', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_jumpbox', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_search', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_search_upd', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_search_phr', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_db_lastread', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_onlinetrack', '1', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('search_gc', '7200', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('queue_interval', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('version', '2.1.2', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_post_chars', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_post_smilies', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_quote_depth', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_sig_chars', '255', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_poll_options', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('min_search_chars', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_search_chars', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('edit_time', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('display_last_edited', '1', 0)");

//$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_email_sig', 'Thanks, The Management', 0)");
//$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_email', '', 0)");
//$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_contact', '', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_start_date', $time, 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('flood_interval', '15', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('bump_interval', '10h', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('search_interval', '', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_filesize', '6144', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_min_width', '20', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_min_height', '20', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_max_width', '90', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('avatar_max_height', '90', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('lastread', '432000', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('display_order', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_filesize', '262144', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_filesize_pm', '262144', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('attachment_quota', '52428800', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_attachments', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_attachments_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_pm_attach', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('upload_path', 'files', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_display_inlined', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('secure_downloads', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('secure_allow_deny', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('secure_allow_empty_referer', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_max_width', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_max_height', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_link_width', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_link_height', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_create_thumbnail', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_min_thumb_filesize', '12000', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('img_imagick', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('pm_max_boxes', '4', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('pm_max_msgs', '50', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_html_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_bbsmiley_code_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_smilies_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_download_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_report_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('load_online_guests', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_img_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('auth_flash_pm', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('enable_pm_icons', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('pm_edit_time', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_mass_pm', '1', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('record_online_users', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('record_online_date', '0', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_posts', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_topics', '0', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('num_files', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('upload_dir_size', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('search_last_gc', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('last_queue_run', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('full_folder_action', '2', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('bump_type', 'm', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_img', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('board_hide_emails', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_bbsmiley_code', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_flash', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_html', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_img', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('allow_sig_smilies', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_config (config_name, config_value, is_dynamic) VALUES ('max_post_urls', '0', 0)");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extension_groups (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Images', 1, 1, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extension_groups (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Archives', 0, 1, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extension_groups (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Plain Text', 0, 0, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extension_groups (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Documents', 0, 0, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extension_groups (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Real Media', 3, 0, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extension_groups (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Windows Media', 2, 0, 1, '', 0, '')");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (1, 'gif')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (1, 'png')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (1, 'jpeg')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (1, 'jpg')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (1, 'tif')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (1, 'tga')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (2, 'gtar')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (2, 'gz')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (2, 'tar')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (2, 'zip')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (2, 'rar')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (2, 'ace')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (3, 'txt')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (3, 'c')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (3, 'h')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (3, 'cpp')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (3, 'hpp')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (3, 'diz')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'xls')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'doc')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'dot')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'pdf')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'ai')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'ps')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (4, 'ppt')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (5, 'rm')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (6, 'wma')");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_extensions (group_id, extension) VALUES (6, 'wmv')");

$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/arrow_bold_rgt.gif', 19, 19, 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/redface_anim.gif', 19, 19, 9, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/mr_green.gif', 19, 19, 10, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/musical.gif', 19, 19, 4, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/asterix.gif', 19, 19, 2, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/square.gif', 19, 19, 3, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/alien_grn.gif', 19, 19, 5, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/idea.gif', 19, 19, 8, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/question.gif', 19, 19, 6, 1)");
$_CLASS['core_db']->query('INSERT INTO '.$table_prefix."forums_icons (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/exclaim.gif', 19, 19, 7, 1)");

?>