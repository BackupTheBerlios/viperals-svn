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

define('VIPERAL', 'CMS');
error_reporting(E_ALL);

/* require_once SITE_FILE_ROOT.'core.php'; */
require_once 'core.php';

$mod = get_variable('mod', 'REQUEST', false);

if (!$mod)
{
	/* Make it know we're at the homepage */
	$_CLASS['core_display']->homepage = true;
	

	$_CORE_CONFIG['global']['index_page'] = 'articles';

	/* Retrieve and process the homepage */
	$sql = 'SELECT * FROM ' . CORE_PAGES_TABLE . "
				WHERE page_name = '".$_CLASS['core_db']->escape($_CORE_CONFIG['global']['index_page'])."'";
	$result = $_CLASS['core_db']->query($sql);

	While ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_display']->process_page($row);
	}
	$_CLASS['core_db']->free_result($result);

	/* If home page isn't a module or template page let atleast display something */
	if (!$_CLASS['core_display']->generate_page())
	{
		/*
		$blocks = 0;
		$blocks |= BLOCK_LEFT;
		$blocks |= BLOCK_RIGHT;
		$blocks |= BLOCK_TOP;
		$blocks |= BLOCK_BOTTOM;
		$blocks |= BLOCK_MESSAGE_TOP;
		$blocks |= BLOCK_MESSAGE_BOTTOM;
		echo $blocks;
		*/

		/* Let all blocks be displayed */
		$blocks = 126;

		$_CLASS['core_display']->page = array('page_blocks' => $blocks, 'page_name' => '', 'page_title' => '');

		$_CLASS['core_user']->user_setup();
		$_CLASS['core_display']->display_header();

		/* Hey admin we don't have a modules set */
		if ($_CLASS['core_auth']->admin_auth('modules'))
		{
			$_CLASS['core_display']->message = '_NO_HOMEPAGE_ADMIN';
		}
	
		$_CLASS['core_display']->display_footer();
	}	
}
else
{
	if ($mod === 'system')
	{
		require_once SITE_FILE_ROOT.'includes/system.php';

		$mode = get_variable('mode', 'REQUEST', false);

		if (!$mode || !function_exists($mode))
		{
			header("HTTP/1.0 503 Service Unavailable");
			script_close(false);
		}

		$mode();

		script_close(false);
	}

	$sql = 'SELECT * FROM '.CORE_PAGES_TABLE.'
				WHERE page_type = ' . MODULE_NORMAL . "
				AND page_name = '" . $_CLASS['core_db']->escape($mod) . "'";

	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$status = $_CLASS['core_display']->process_page($row);
	
	if ($status !== true)
	{
		trigger_error($status, E_USER_ERROR);
	}

	$_CLASS['core_display']->generate_page();
}

script_close();

?>