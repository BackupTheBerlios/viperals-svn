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

if (!defined('VIPERAL'))
{
    die;
}

global $table_prefix;

if (!defined('QUICK_MESSAGE_TABLE'))
{
	define('QUICK_MESSAGE_TABLE', $table_prefix.'quick_message');
}

class Quick_Message_configurator
{
	function install()
	{
		global $_CLASS;

		$_CLASS['core_db']->table_create('start', QUICK_MESSAGE_TABLE);
		
		$_CLASS['core_db']->add_table_field_int('message_id', array('max' => 16000000, 'auto_increment' => true));
		$_CLASS['core_db']->add_table_field_text('message_text', 200);
		$_CLASS['core_db']->add_table_field_int('message_time', array('max' => 200000000));
		$_CLASS['core_db']->add_table_field_int('poster_id', array('max' => 16000000));
		$_CLASS['core_db']->add_table_field_char('poster_name', 80);
		$_CLASS['core_db']->add_table_field_char('poster_ip', 18);
		
		$_CLASS['core_db']->add_table_index('message_id', 'primary');
		$_CLASS['core_db']->add_table_index('message_time');
		
		$_CLASS['core_db']->table_create('commit');
		
		// use set config ?
		set_core_config('quick_message', 'anonymous_posting', 2, false, true);
		set_core_config('quick_message', 'delete_time', 300, false, true);
		set_core_config('quick_message', 'height', 200, false, true);
		set_core_config('quick_message', 'last_post_check', 150, false, true);
		set_core_config('quick_message', 'length_max', 150, false, true);

		$_CLASS['core_cache']->destroy('core_config');

		$array = array(
			'message_text'	=> 'Lets do this !',
			'message_time'	=> (int) $_CLASS['core_user']->time,
			'poster_id'		=> 0,
			'poster_name'	=> (string) '',
			'poster_ip'		=> (string) ''
		);

		$_CLASS['core_db']->query('INSERT INTO ' . QUICK_MESSAGE_TABLE . ' '. $_CLASS['core_db']->sql_build_array('INSERT', $array));

		return true;
	}

	function uninstall()
	{
		global $_CLASS;

		$_CLASS['core_db']->report_error(false);

		$_CLASS['core_db']->query('DROP TABLE ' . QUICK_MESSAGE_TABLE);
		$_CLASS['core_db']->query('DELETE FROM ' . CORE_CONFIG_TABLE . " WHERE config_section = 'quick_message'");

		$_CLASS['core_db']->report_error(true);
		
		return true;
	}
}

?>