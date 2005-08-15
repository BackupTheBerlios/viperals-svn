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
*/

$_CLASS['core_db']->table_create('start', $install_prefix.'quick_message');

$_CLASS['core_db']->add_table_field_int('message_id', array('max' => 16000000, 'auto_increment' => true));
$_CLASS['core_db']->add_table_field_text('message_text', 200);
field_unix_time('message_time');
$_CLASS['core_db']->add_table_field_int('poster_id', array('max' => 16000000));
$_CLASS['core_db']->add_table_field_char('poster_name', 80);
$_CLASS['core_db']->add_table_field_char('poster_ip', 18);

$_CLASS['core_db']->add_table_index('message_id', 'primary');
$_CLASS['core_db']->add_table_index('message_time');

$_CLASS['core_db']->table_create('commit');

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (config_section, config_name, config_value, config_cache) VALUES ('quick_message', 'anonymous_posting', '2', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (config_section, config_name, config_value, config_cache) VALUES ('quick_message', 'delete_time', '300', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (config_section, config_name, config_value, config_cache) VALUES ('quick_message', 'height', '200', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (config_section, config_name, config_value, config_cache) VALUES ('quick_message', 'last_post_check', '150', 1)");
$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."config (config_section, config_name, config_value, config_cache) VALUES ('quick_message', 'length_max', '150', 1)");

$_CLASS['core_db']->query('INSERT INTO '.$install_prefix."quick_message (poster_id, poster_name, poster_ip, message_text, message_time) VALUES (0, 'Site', '', 'Lets do this !', ".gmtime().")");

$installed = true;

?>