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

if (!defined('CALENDER_TABLE'))
{
	define('CALENDER_TABLE', $table_prefix.'calender');
}

class calender_configurator
{
	var $error = array();
	
	function install()
	{
		global $_CLASS;

		$_CLASS['core_db']->table_create('start', CALENDER_TABLE);
		
		$_CLASS['core_db']->add_table_field_int('calender_id', array('max' => 16000000, 'auto_increment' => true));
		$_CLASS['core_db']->add_table_field_char('calender_title', 80);
		$_CLASS['core_db']->add_table_field_int('calender_starts', array('max' => 200000000));
		$_CLASS['core_db']->add_table_field_int('calender_expires', array('max' => 200000000));
		$_CLASS['core_db']->add_table_field_text('calender_text', 60000, true);
		$_CLASS['core_db']->add_table_field_text('calender_notes', 60000, true);
		$_CLASS['core_db']->add_table_field_char('calender_recur_rate', 80);
		$_CLASS['core_db']->add_table_field_char('calender_start_time', 5, true);
		$_CLASS['core_db']->add_table_field_int('calender_duration', array('max' => 200000000, 'null' => true));

		$_CLASS['core_db']->add_table_index('calender_id', 'primary');
		$_CLASS['core_db']->add_table_index('calender_starts');
		$_CLASS['core_db']->add_table_index('calender_expires');

		$_CLASS['core_db']->table_create('commit');

		/*$array = array(
			'articles_title'	=> 'Welcome Post',
			'articles_posted'	=> (int) $_CLASS['core_user']->time,
			'articles_text'		=> '<p align="center">I need something snappy here</p>',
			'articles_order'	=> 1,
			'articles_type'		=> 1,
			'articles_status'	=> STATUS_ACTIVE,
			'poster_id'			=> 0,
			'poster_ip'			=> (string) ''
		);*/

		//$_CLASS['core_db']->query('INSERT INTO ' . ARTICLES_TABLE . ' '. $_CLASS['core_db']->sql_build_array('INSERT', $array));

		return true;
	}

	function uninstall()
	{
		global $_CLASS;

		$_CLASS['core_db']->report_error(false);

		$_CLASS['core_db']->query('DROP TABLE ' . CALENDER_TABLE);

		$_CLASS['core_db']->report_error(true);
		
		return true;
	}
}

?>