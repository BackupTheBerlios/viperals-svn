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

define('VIPERAL', 'FEED');

error_reporting(0);

/* require(SITE_FILE_ROOT.'core.php'); */
require_once 'core.php';

if ($mod = get_variable('mod', 'REQUEST', false))
{
	/* Grab module data if it exsits */
	$sql = 'SELECT * FROM ' . CORE_PAGES_TABLE . "
				WHERE page_name = '" . $_CLASS['core_db']->escape($mod) . "'";//WHERE module_type = ' . MODULE_NORMAL . "
	
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$status = $_CLASS['core_display']->process_page($row, 'feed');

	if ($status !== true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		script_close(false);
	}

	$_CLASS['core_display']->generate_page('feed');
}

$_CLASS['core_user']->user_setup(null);

if (!defined('DISPLAY_FEED') || DISPLAY_FEED === 'custom')
{
	script_close(false);
}

global $_CORE_CONFIG;

header('Content-Type: text/xml');

$_CLASS['core_template']->assign_array(array(
	'SITE_NAME' 	=> $_CORE_CONFIG['global']['site_name'],
	'SITE_URL' 		=> generate_link(false, array('full' => true, 'sid' => false)),
	'LANG'			=> 'en-us',
	//'LAST_MODIFIED'	=> $last_modified ,
	'TIME'			=> gmdate('M d Y H:i:s') .' GMT'
));

/* Display the feed according to the selected feed type */	
Switch ($_GET['feed'])
{
	case 'rdf':
	case 'rss1':
		$_CLASS['core_template']->display('rss/rdf.html');
	break;

	case 'rss2':
		$_CLASS['core_template']->display('rss/rss2.html');
	break;

	case 'rss':
		$_CLASS['core_template']->display('rss/rss91.html');
	break;

	case 'atom':
		$_CLASS['core_template']->display('rss/atom.html');
	break;

	default:
		$_CLASS['core_template']->display('rss/rss91.html');
	break;
}

script_close(false);

?>