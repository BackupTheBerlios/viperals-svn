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

global $prefix;

if (!defined('ARTICLES_TABLE'))
{
	define('ARTICLES_TABLE', $prefix.'articles');
}

class articles_install
{
	var $error = array();
	
	function install()
	{
		global $_CLASS;

		$_CLASS['core_db']->table_create('start', ARTICLES_TABLE);
		
		$_CLASS['core_db']->add_table_field_int('articles_id', array('max' => 16000000, 'auto_increment' => true));
		$_CLASS['core_db']->add_table_field_char('articles_title', 80);
		$_CLASS['core_db']->add_table_field_int('articles_posted', array('max' => 200000000));
		$_CLASS['core_db']->add_table_field_int('articles_starts', array('max' => 200000000, 'null' => true));
		$_CLASS['core_db']->add_table_field_int('articles_expires', array('max' => 200000000, 'null' => true));
		$_CLASS['core_db']->add_table_field_text('articles_intro', 60000, true);
		$_CLASS['core_db']->add_table_field_text('articles_text', 10000000);
		$_CLASS['core_db']->add_table_field_text('articles_notes', 60000, true);

		$_CLASS['core_db']->add_table_field_int('articles_order', array('max' => 16000000));
		$_CLASS['core_db']->add_table_field_int('articles_type', array('max' => 10));
		$_CLASS['core_db']->add_table_field_int('articles_status', array('max' => 10));
		$_CLASS['core_db']->add_table_field_text('articles_auth', 60000, true);

		$_CLASS['core_db']->add_table_field_int('poster_id', array('max' => 16000000));
		$_CLASS['core_db']->add_table_field_char('poster_name', 80, true);
		$_CLASS['core_db']->add_table_field_char('poster_ip', 18);
		$_CLASS['core_db']->add_table_field_char('approver_id', 18, true);
		$_CLASS['core_db']->add_table_field_char('approver_name', 80, true);

		$_CLASS['core_db']->add_table_index('articles_id', 'primary');
		$_CLASS['core_db']->add_table_index('articles_start');
		//$_CLASS['core_db']->add_table_index('articles_type');
		//$_CLASS['core_db']->add_table_index('articles_expires');
		$_CLASS['core_db']->add_table_index('articles_order');
		$_CLASS['core_db']->add_table_index('articles_status');

		$_CLASS['core_db']->table_create('commit');

		$array = array(
			'articles_title'	=> 'Welcome Post',
			'articles_posted'	=> (int) $_CLASS['core_user']->time,
			'articles_text'		=> '<p align="center">I need something snappy here</p>',
			'articles_order'	=> 1,
			'articles_type'		=> 1,
			'articles_status'	=> STATUS_ACTIVE,
			'poster_id'			=> 0,
			'poster_ip'			=> (string) ''
		);

		$_CLASS['core_db']->query('INSERT INTO ' . ARTICLES_TABLE . ' '. $_CLASS['core_db']->sql_build_array('INSERT', $array));

		return true;
	}

	function uninstall()
	{
		global $_CLASS;

		$_CLASS['core_db']->report_error(false);

		$_CLASS['core_db']->query('DROP TABLE ' . ARTICLES_TABLE);

		$_CLASS['core_db']->report_error(true);
	}
}

?>