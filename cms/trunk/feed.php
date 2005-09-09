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

//echo str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/'; die;
$site_file_root = '';

require($site_file_root.'core.php');

//error_reporting(0);
header('Content-Type: text/xml');

if (!defined('ARTICLES_TABLE'))
{
	define('ARTICLES_TABLE', $prefix.'articles');
}

$result = $_CLASS['core_db']->query_limit('SELECT articles_id, articles_title, articles_intro, articles_text, articles_posted, articles_starts, poster_name FROM ' . ARTICLES_TABLE . ' ORDER BY articles_order ASC', 10);

$last_post_time = 0;

// Need to fix this, add auth and expires/start check ( will have to remove limit )
while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$time = ($row['articles_starts']) ? $row['articles_starts'] : $row['articles_posted'];
	$text = ($row['articles_intro']) ? $row['articles_intro'] : $row['articles_text'];

	if ($time > $last_post_time)
	{
		$last_post_time = $time;
	}

	$_CLASS['core_template']->assign_vars_array('items', array(
		'TITLE' 			=> htmlspecialchars($row['articles_title'], ENT_QUOTES, 'UTF-8'),
		'LINK' 				=> generate_link('articles&amp;mode=view&amp;id='.$row['articles_id'], array('full' => true, 'sid' => false)),
		'DESCRIPTION' 		=> htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8'),
		'DESCRIPTION_HTML' 	=> htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
		'TIME'				=> date('M d Y H:i:s', $time) .' GMT',
		'AUTHOR'			=> htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8')
	));
}
$_CLASS['core_db']->free_result($result);

$last_modified = date('M d Y H:i:s', $last_post_time) .' GMT';

$_CLASS['core_template']->assign_array(array(
	'SITE_NAME' 	=> $_CORE_CONFIG['global']['site_name'],
	'SITE_URL' 		=> $_CORE_CONFIG['global']['site_url'],
	'LANG'			=> 'en-us',
	'LAST_MODIFIED'	=> $last_modified ,
	'TIME'			=> gmdate('M d Y H:i:s') .' GMT'
));

header('Last-Modified: '.$last_post_time);

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

script_close();

?>