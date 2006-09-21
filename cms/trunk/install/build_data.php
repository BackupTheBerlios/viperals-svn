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

$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_AUTH_TABLE ." (admin_section, user_id, admin_status, admin_options) VALUES ('_all_', 2, 2, '_all_')");

$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'path_avatar_upload', 'images/avatars/upload', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'path_avatar_gallery', 'images/avatars/gallery', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'path_smilies', 'images/smilies', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'site_name', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_theme', 'viperal', 'string', 1)");
$content = $_CLASS['core_db']->escape('<a href="feed.php?mod=articles&feed=rss1" title="RSS 1.0 / RDF Feed"><img alt="RSS 1.0 / RDF" src="images/rss10_logo.gif" /></a> <a href="feed.php?mod=articles&feed=rss2" title="RSS 2.0 Feed"><img alt="RSS 2.0" src="images/rss20_logo.gif" /></a> <a href="feed.php?mod=articles&feed=rss" title="RSS 0.91 Feed"><img alt="RSS 0.9" src="images/rss090_logo.gif" /></a>');
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'foot1', '$content', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'foot2', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_lang', 'en', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_dst', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'only_registered', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_dateformat', 'D M d, Y g:i a', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'default_timezone', '-5', 'float', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'link_optimization', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('global', 'index_page', 'contact', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('maintenance', 'active', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('maintenance', 'start', '', 'int', 1)");
$content = $_CLASS['core_db']->escape('<p align="center"><b>Sorry we are currently updating this site.<br>Please try again later</b></p>');
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('maintenance', 'text', '$content', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'allow_html_email', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'email_enable', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'email_function_name', 'mail', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp', 0, 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_host', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_port', '', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_username', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'smtp_password', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('email', 'site_email', 'none@none.com', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'allow_name_chars', '.*', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'enable_confirm', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'coppa_enable', '1', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'coppa_fax', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'coppa_mail', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'min_name_chars', '5', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'max_reg_attempts', '10', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'max_name_chars', '50', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'min_pass_chars', '5', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'max_pass_chars', '25', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'allow_email_reuse', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'allow_name_change', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'newest_username', 'admin', 'string', 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'newest_user_id', '2', 'int', 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'activation', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'total_users', '1', 'int', 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'password_encoding', 'md5', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('user', 'pass_complex', '.*', 'string', 1)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'path', '/', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'cookie_domain', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'cookie_name', 'viperal', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'cookie_path', '/', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'limit_load', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'limit_sessions', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'ip_check', '4', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'session_length', '3600', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'error_options', '0', 'int', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_secure', '0', 'bool', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_domain', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_path', '', 'string', 1)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONFIG_TABLE  ." (config_section, config_name, config_value, config_type, config_cache) VALUES ('server', 'site_port', '', 'int', 1)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ." (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('GUESTS', 1, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ." (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('REGISTERED', 1, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ." (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('REGISTERED_COPPA', 1, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ." (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('ADMINISTRATORS', 1, 2, 'AA0000', 1, '')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ." (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('BOTS', 1, 2, '9E8DA7', 1, '')");
// To be seperated
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ." (group_name, group_type, group_status, group_colour, group_legend, group_description) VALUES ('SUPER_MODERATORS', 1, 2, '00AA00', 0, '')");


//Admin
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (2, 2, 2)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (4, 2, 10)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (6, 2, 10)");
// Anonymous
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (1, 1, 2)");
// Bots
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (5, 3, 2)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (5, 4, 2)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (5, 5, 2)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (5, 6, 2)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (5, 7, 2)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_GROUPS_TABLE  ."_members (group_id, user_id, member_status) VALUES (5, 8, 2)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_BLOCKS_TABLE  ." (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('Contol Panel', 3, 2, 1, 1, 'block-Control_Panel.php')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_BLOCKS_TABLE  ." (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('Modules', 0, 2, 1, 2, 'block-Modules.php')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_BLOCKS_TABLE  ." (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('User', 0, 2, 1, 3, 'block-User.php')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_BLOCKS_TABLE  ." (block_title, block_type, block_status, block_position, block_order, block_rss_url, block_rss_rate) VALUES ('php.net RSS Feed', 1, 2, 2, 1, 'http://www.php.net/news.rss', 7200)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_BLOCKS_TABLE  ." (block_title, block_type, block_status, block_position, block_order, block_file) VALUES ('Theme Select', 0, 2, 2, 2, 'block-theme_select.php')");

$content = $_CLASS['core_db']->escape('<div style="text-align:center">Hello and welcome</div>');
$_CLASS['core_db']->query('INSERT INTO '. CORE_BLOCKS_TABLE  ." (block_title, block_type, block_status, block_position, block_order, block_content) VALUES ('Welcome', 4, 2, 6, 4, '$content')");

$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('blocks', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('groups', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('system', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('messages', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('modules', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('pages', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('smiles', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('users', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('groups', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('forums', 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_ADMIN_MODULES_TABLE  ." (module_name, module_status, module_type) VALUES ('eaccelerator', 2, 0)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('main', 2, 'front\r\nsubscribed\r\nbookmarks\r\ndrafts')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('pm', 2, 'view_messages\r\ncompose\r\nunread\r\ndrafts\r\noptions')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('profile', 2,'profile_info\r\nreg_details\r\nsignature\r\navatar')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('prefs', 2, 'personal\r\nview\r\npost')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('zebra', 2, 'friends\r\nfoes')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('attachments', 1, '')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('groups', 2, '')");
$_CLASS['core_db']->query('INSERT INTO '. CORE_CONTROL_PANEL_MODULES_TABLE  ." (module_name, module_status, module_subs) VALUES ('calender', 2,'month_view\r\nday_view\r\nadd_event')");

$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('articles', 0, 1, 102)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('contact', 0, 2, 102)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('calender', 0, 1, 98)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('control_panel', 0, 2, 98)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('forums', 0, 2, 98)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('members_list', 0, 2, 98)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE  ." (page_name, page_type, page_status, page_blocks) VALUES ('quick_message', 0, 1, 102)");

$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':D', 'grin.png', 'Very Happy', 19, 19, 1, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':)', 'smile.png', 'Smile', 19, 19, 2, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':(', 'sad.png', 'Sad', 19, 19, 3, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':o', 'surprised.png', 'Surprised', 19, 19, 4, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':eek:', 'surprised.png', 'Surprised', 19, 19, 4, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES ('8O', 'eek.png', 'Shocked', 19, 19, 5, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':?', 'confused.png', 'Confused', 19, 19, 6, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES ('8)', 'cool.png', 'Cool', 19, 19, 7, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':lol:', 'laugh.png', 'Laughing', 19, 19, 8, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':x', 'mad.png', 'Mad', 19, 19, 9, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':P', 'icon_razz.gif', 'Razz', 19, 19, 10, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':oops:', 'embarrassed.png', 'Embarassed', 19, 19, 11, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':cry:', 'cry.png', 'Crying or Very sad', 19, 19, 12, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':evil:', 'evil.png', 'Evil or Very Mad', 19, 19, 13, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':twisted:', 'twisted.png', 'Twisted Evil', 19, 19, 14, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':roll:', 'rolleyes.png', 'Rolling Eyes', 19, 19, 19, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (';)', 'wink.png', 'Wink', 19, 19, 16, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':!:', 'exclaim.png', 'Exclamation', 19, 19, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':?:', 'question.png', 'Question', 19, 19, 18, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':idea:', 'idea.png', 'Idea', 19, 19, 19, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':arrow:', 'arrow.png', 'Arrow', 19, 19, 20, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':|', 'neutral.png', 'Neutral', 19, 19, 21, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_SMILIES_TABLE  ." (smiley_code, smiley_src, smiley_description, smiley_width, smiley_height, smiley_order, smiley_type) VALUES (':mrgreen:', 'mrgreen.png', 'Mr. Green', 19, 19, 22, 0)");

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

$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (0, 2, 1, 'Anonymous', '', '', '', $time, '', 1, 0, 0, 0, 0, '$guest_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (1, 2, 4, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'md5', '', $time, '', 'AA0000', 1, 1, 1, 1, 1, '$admin_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Googlebot', '', '', '', $time, '216.239.46.,64.68.8.,66.249.64.,66.249.71.', 'Googlebot/', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'MSN', '', '', '', $time, '65.54.188.', 'msnbot/', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Yahoo', '', '', '', $time, '66.196.90.1', 'Yahoo! Slurp', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Alexa', '', '', '', $time, '66.28.250.,209.237.238.', 'ia_archiver', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'NaverBot', '', '', '', $time, '222.122.194.,218.145.25.,61.78.61.,61.78.61.', 'NaverBot-', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");
$_CLASS['core_db']->query('INSERT INTO '. CORE_USERS_TABLE  ." (user_type, user_status, user_group, username, user_password, user_password_encoding, user_email, user_reg_date, user_ip, user_agent, user_colour, user_allow_viewonline, user_allow_viewemail, user_allow_massemail, user_allow_pm, user_allow_email, user_data, user_new_privmsg, user_unread_privmsg, user_posts) VALUES (2, 2, 5, 'Jetbot', '', '', '', $time, '64.71.144.', 'Jetbot/', '9E8DA7', 1, 0, 0, 0, 0, '$bot_data', 0, 0, 0)");

// Forums
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_announce', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_bbcode', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_bump', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_delete', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_download', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_edit', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_email', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_flash', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_icons', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_ignoreflood', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_img', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_list', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_noapprove', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_print', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_poll', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_post', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_postcount', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_read', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_reply', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_report', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_search', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_sigs', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_smilies', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_sticky', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_subscribe', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_user_lock', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_vote', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local) VALUES ('f_votechg', 1)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_approve', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_chgposter', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_delete', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_edit', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_info', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_lock', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_merge', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_move', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_report', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_split', 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_local, is_global) VALUES ('m_warn', 1, 1)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_sendemail', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_readpm', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_sendpm', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_sendim', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_hideonline', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_viewonline', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_viewprofile', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_search', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_savedrafts', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_download', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('u_sig', 1)");

// START: This should be replaced by beta
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_aauth', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_attach', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_authgroups', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_authusers', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_bbcode', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_board', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_clearlogs', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_fauth', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_forum', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_forumadd', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_forumdel', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_icons', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_mauth', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_modules', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_prune', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_ranks', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_reasons', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_roles', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_search', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_server', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_switchperm', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_uauth', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_user', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_viewauth', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_OPTIONS_TABLE  ." (auth_option, is_global) VALUES ('a_viewlogs', 1)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Standard Admin', 'ROLE_DESCRIPTION_ADMIN_STANDARD', 'a_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Forum Admin', 'ROLE_DESCRIPTION_ADMIN_FORUM', 'a_', 3)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('User and Groups Admin', 'ROLE_DESCRIPTION_ADMIN_USERGROUP', 'a_', 4)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Full Admin', 'ROLE_DESCRIPTION_ADMIN_FULL', 'a_', 2)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('All Features', 'ROLE_DESCRIPTION_USER_FULL', 'u_', 3)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Standard Features', 'ROLE_DESCRIPTION_USER_STANDARD', 'u_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Limited Features', 'ROLE_DESCRIPTION_USER_LIMITED', 'u_', 2)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('No Private Messages', 'ROLE_DESCRIPTION_USER_NOPM', 'u_', 4)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('No Avatar', 'ROLE_DESCRIPTION_USER_NOAVATAR', 'u_', 5)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Full Moderator', 'ROLE_DESCRIPTION_MOD_FULL', 'm_', 3)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Standard Moderator', 'ROLE_DESCRIPTION_MOD_STANDARD', 'm_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Simple Moderator', 'ROLE_DESCRIPTION_MOD_SIMPLE', 'm_', 2)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Queue Moderator', 'ROLE_DESCRIPTION_MOD_QUEUE', 'm_', 4)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Full Access', 'ROLE_DESCRIPTION_FORUM_FULL', 'f_', 6)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Standard Access', 'ROLE_DESCRIPTION_FORUM_STANDARD', 'f_', 4)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('No Access', 'ROLE_DESCRIPTION_FORUM_NOACCESS', 'f_', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Read Only Access', 'ROLE_DESCRIPTION_FORUM_READONLY', 'f_', 2)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Limited Access', 'ROLE_DESCRIPTION_FORUM_LIMITED', 'f_', 3)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Bot Access', 'ROLE_DESCRIPTION_FORUM_BOT', 'f_', 8)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('On Moderation Queue', 'ROLE_DESCRIPTION_FORUM_ONQUEUE', 'f_', 7)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_TABLE . " (role_name, role_description, role_type, role_order) VALUES ('Standard Access + Polls', 'ROLE_DESCRIPTION_FORUM_POLLS', 'f_', 5)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_attachments', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_bbcode', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_html', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_html_tags', 'b,i,u,pre', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_smilies', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_sig', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_topic_notify', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_forum_notify', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_nocensors', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_bookmarks', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('board_email_form', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('min_ratings', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('posts_per_page', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('topics_per_page', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('hot_threshold', '25', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('avatar_path', 'images/avatars/upload', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('avatar_gallery_path', 'images/avatars/gallery', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('smilies_path', 'images/smilies', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('icons_path', 'images/icons', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('upload_icons_path', 'images/upload_icons', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('ranks_path', 'images/ranks', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('email_enable', '1', 0)");// maybe keep

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('limit_search_load', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_anon_lastread', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_db_lastread', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_birthdays', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_moderators', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_jumpbox', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_search', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('load_user_activity', '1', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_block_size', '250', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_gc', '7200', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_indexing_state', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_interval', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_anonymous_interval', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_type', 'fulltext_native', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_store_results', '1800', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('version', '2.1.2', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_attachments', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_filesize', '262144', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_poll_options', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_post_chars', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_post_font_size', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_post_img_height', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_post_img_width', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_post_smilies', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_post_urls', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_quote_depth', '3', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('max_sig_chars', '255', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('min_search_author_chars', '3', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('board_start_date', $time, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('bump_interval', '10', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('bump_type', 'd', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('display_order', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('display_last_edited', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('edit_time', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('flood_interval', '15', 0)");



//$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('lastread', '432000', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('upload_path', 'files', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('secure_downloads', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('secure_allow_deny', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('secure_allow_empty_referer', '1', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_create_thumbnail', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_display_inlined', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_imagick', '', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_max_height', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_max_width', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_link_height', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_link_width', '0', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('img_min_thumb_filesize', '12000', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('record_online_users', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('record_online_date', '0', 1)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('num_posts', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('num_topics', '0', 1)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('num_files', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('upload_dir_size', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('search_last_gc', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('last_queue_run', '0', 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('full_folder_action', '2', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('allow_img', '1', 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_CONFIG_TABLE  ." (config_name, config_value, is_dynamic) VALUES ('board_hide_emails', '0', 0)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSION_GROUPS_TABLE  ." (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Images', 1, 1, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSION_GROUPS_TABLE  ." (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Archives', 0, 1, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSION_GROUPS_TABLE  ." (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Plain Text', 0, 0, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSION_GROUPS_TABLE  ." (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Documents', 0, 0, 1, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSION_GROUPS_TABLE  ." (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Real Media', 3, 0, 2, '', 0, '')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSION_GROUPS_TABLE  ." (group_name, cat_id, allow_group, download_mode, upload_icon, max_filesize, allowed_forums) VALUES ('Windows Media', 2, 0, 1, '', 0, '')");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (1, 'gif')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (1, 'png')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (1, 'jpeg')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (1, 'jpg')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (1, 'tif')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (1, 'tga')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (2, 'gtar')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (2, 'gz')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (2, 'tar')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (2, 'zip')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (2, 'rar')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (2, 'ace')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (3, 'txt')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (3, 'c')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (3, 'h')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (3, 'cpp')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (3, 'hpp')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (3, 'diz')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'xls')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'doc')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'dot')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'pdf')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'ai')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'ps')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (4, 'ppt')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (5, 'rm')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (6, 'wma')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_EXTENSIONS_TABLE  ." (group_id, extension) VALUES (6, 'wmv')");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/arrow_bold_rgt.gif', 19, 19, 1, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/redface_anim.gif', 19, 19, 9, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/mr_green.gif', 19, 19, 10, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/musical.gif', 19, 19, 4, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/asterix.gif', 19, 19, 2, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('misc/square.gif', 19, 19, 3, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/alien_grn.gif', 19, 19, 5, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/idea.gif', 19, 19, 8, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/question.gif', 19, 19, 6, 1)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ICONS_TABLE  ." (icons_url, icons_width, icons_height, icons_order, display_on_posting) VALUES ('smilies/exclaim.gif', 19, 19, 7, 1)");

# -- Roles data

# Standard Admin (a_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 1, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'a_%' AND auth_option NOT IN ('a_switchperm', 'a_jabber', 'a_phpinfo', 'a_server', 'a_styles', 'a_clearlogs', 'a_modules', 'a_language', 'a_bots', 'a_search', 'a_aauth', 'a_roles')");

# Forum admin (a_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 2, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'a_%' AND auth_option IN ('a_', 'a_authgroups', 'a_authusers', 'a_fauth', 'a_forum', 'a_forumadd', 'a_forumdel', 'a_mauth', 'a_prune', 'a_uauth', 'a_viewauth', 'a_viewlogs')");

# User and Groups Admin (a_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 3, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'a_%' AND auth_option IN ('a_', 'a_authgroups', 'a_authusers', 'a_ban', 'a_group', 'a_groupadd', 'a_groupdel', 'a_ranks', 'a_uauth', 'a_user', 'a_viewauth', 'a_viewlogs')");

# Full Admin (a_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 4, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'a_%'");

# All Features (u_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 5, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%'");

# Standard Features (u_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 6, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%' AND auth_option NOT IN ('u_viewonline', 'u_chggrp', 'u_chgname', 'u_ignoreflood', 'u_pm_flash', 'u_pm_forward')");

# Limited Features (u_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 7, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%' AND auth_option NOT IN ('u_attach', 'u_viewonline', 'u_chggrp', 'u_chgname', 'u_ignoreflood', 'u_pm_attach', 'u_pm_emailpm', 'u_pm_flash', 'u_savedrafts', 'u_search', 'u_sendemail', 'u_sendim')");

# No Private Messages (u_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 8, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%' AND auth_option IN ('u_', 'u_chgavatar', 'u_chgcensors', 'u_chgemail', 'u_chgpasswd', 'u_download', 'u_hideonline', 'u_sig', 'u_viewprofile')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 8, auth_option_id, 0 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%' AND auth_option IN ('u_readpm', 'u_sendpm', 'u_masspm')");

# No Avatar (u_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 9, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%' AND auth_option NOT IN ('u_attach', 'u_chgavatar', 'u_viewonline', 'u_chggrp', 'u_chgname', 'u_ignoreflood', 'u_pm_attach', 'u_pm_emailpm', 'u_pm_flash', 'u_savedrafts', 'u_search', 'u_sendemail', 'u_sendim')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 9, auth_option_id, 0 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'u_%' AND auth_option IN ('u_chgavatar')");

# Full Moderator (m_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 10, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'm_%'");

# Standard Moderator (m_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 11, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'm_%' AND auth_option NOT IN ('m_approve', 'm_ban', 'm_chgposter', 'm_delete')");

# Simple Moderator (m_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 12, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'm_%' AND auth_option IN ('m_', 'm_approve', 'm_delete', 'm_edit', 'm_info', 'm_report', 'm_warn')");

# Queue Moderator (m_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 13, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'm_%' AND auth_option IN ('m_', 'm_approve', 'm_edit')");

# Full Access (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 14, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%'");

# Standard Access (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 15, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option NOT IN ('f_announce', 'f_delete', 'f_ignoreflood', 'f_poll', 'f_sticky', 'f_user_lock')");

# No Access (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 16, auth_option_id, 0 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option = 'f_'");

# Read Only Access (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 17, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option IN ('f_', 'f_download', 'f_list', 'f_read', 'f_search', 'f_subscribe')");

# Limited Access (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 18, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option NOT IN ('f_announce', 'f_attach', 'f_bump', 'f_delete', 'f_flash', 'f_icons', 'f_ignoreflood', 'f_poll', 'f_sticky', 'f_user_lock', 'f_votechg')");

# Bot Access (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 19, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option IN ('f_', 'f_download', 'f_list', 'f_read')");

# On Moderation Queue (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 20, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option NOT IN ('f_announce', 'f_bump', 'f_delete', 'f_flash', 'f_icons', 'f_ignoreflood', 'f_poll', 'f_sticky', 'f_user_lock', 'f_votechg', 'f_noapprove')");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 20, auth_option_id, 0 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option IN ('f_noapprove')");

# Standard Access + Polls (f_)
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_ROLES_DATA_TABLE  ." (role_id, auth_option_id, auth_setting) SELECT 21, auth_option_id, 1 FROM  ". FORUMS_ACL_OPTIONS_TABLE  ."  WHERE auth_option LIKE 'f_%' AND auth_option NOT IN ('f_announce', 'f_delete', 'f_ignoreflood', 'f_sticky', 'f_user_lock')");

# -- Forums
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_FORUMS_TABLE ." (forum_name, forum_desc, forum_status, left_id, right_id, parent_id, forum_type, forum_posts, forum_topics, forum_topics_real, forum_last_post_id, forum_last_poster_id, forum_last_poster_name, forum_last_post_time, forum_link, forum_password, forum_image, forum_rules, forum_rules_link, forum_rules_uid, forum_desc_uid, enable_icons) VALUES ('My first Category', '', ".ITEM_UNLOCKED.",1, 4, 0, 0, 1, 1, 1, 1, 2, 'Admin', 972086460, '', '', '', '', '', '', '', 1)");

$_CLASS['core_db']->query('INSERT INTO '. FORUMS_FORUMS_TABLE ." (forum_name, forum_desc, forum_status, left_id, right_id, parent_id, forum_type, forum_posts, forum_topics, forum_topics_real, forum_last_post_id, forum_last_poster_id, forum_last_poster_name, forum_last_post_time, forum_link, forum_password, forum_image, forum_rules, forum_rules_link, forum_rules_uid, forum_desc_uid, enable_icons) VALUES ('Test Forum 1', 'This is just a test forum.', ".ITEM_UNLOCKED.", 2, 3, 1, 1, 1, 1, 1, 1, 2, 'Admin', 972086460, '', '', '', '', '', '', '', 1)");

# Permissions

# Admin user - full user features
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (user_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (2, 0, 0, 5, 0)");

# ADMINISTRATOR Group - full user features
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (4, 0, 0, 5, 0)");

# ADMINISTRATOR Group - standard admin
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (4, 0, 0, 1, 0)");

# REGISTERED and REGISTERED_COPPA having standard user features
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (2, 0, 0, 6, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (3, 0, 0, 6, 0)");

# GLOBAL_MODERATORS having full user features
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (6, 0, 0, 5, 0)");

# GLOBAL_MODERATORS having full global moderator access
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (6, 0, 0, 10, 0)");

# Giving all groups read only access to the first category
# since administrators and moderators are already within the registered users group we do not need to set them here
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (1, 1, 0, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (2, 1, 0, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (3, 1, 0, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (4, 1, 0, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (5, 1, 0, 17, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (6, 1, 0, 17, 0)");

# Giving access to the first forum

# guests having read only access
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (1, 2, 0, 17, 0)");

# registered and registered_coppa having standard access
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (2, 2, 0, 15, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (3, 2, 0, 15, 0)");

# global moderators having standard access + polls
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (6, 2, 0, 21, 0)");

# administrators having full forum and full moderator access
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (4, 2, 0, 14, 0)");
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (4, 2, 0, 10, 0)");

# Bots having bot access
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_ACL_TABLE  ." (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) VALUES (5, 2, 0, 19, 0)");

# -- Demo Topic
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_TOPICS_TABLE  ." (topic_title, topic_poster, icon_id, topic_approved, topic_time, topic_views, topic_replies, topic_replies_real, forum_id, topic_status, topic_type, topic_first_post_id, topic_first_poster_name, topic_last_post_id, topic_last_poster_id, topic_last_poster_name, topic_last_post_time, topic_last_view_time) VALUES ('Welcome to phpBB 3', 2, 0, 1, 972086460, 0, 0, 0, 2, 0, 0, 1, 'Admin', 1, 2, 'Admin', 972086460, 972086460)");

# -- Demo Post
$_CLASS['core_db']->query('INSERT INTO '. FORUMS_POSTS_TABLE  ." (topic_id, forum_id, poster_id, icon_id, post_approved, post_postcount, post_time, post_username, poster_ip, post_subject, post_text, post_checksum, bbcode_uid) VALUES (1, 2, 2, 1, 1, 1, 972086460, '', '', 'Welcome to phpBB 3', 'This is an example post in your phpBB 3.0 installation. You may delete this post, this topic and even this forum if you like since everything seems to be working!', '5dd683b17f641daf84c040bfefc58ce9', '')");

?>