<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal©	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
define('VIPERAL', 'FEED');

//echo str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/'; die;
$site_file_root = '';

require($site_file_root.'core.php');

//error_reporting(0);
header('Content-Type: text/xml');

$result = $_CLASS['core_db']->query('SELECT news_id, news_title, news_time, news_intro, poster_name FROM '.$prefix.'news ORDER BY news_id DESC LIMIT 10');

$last_post_time = 0;
	
while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	if ($row['news_time'] > $last_post_time)
	{
		$last_post_time = $row['news_time'];
	}
	
	$_CLASS['core_template']->assign_vars_array('items', array(
		'TITLE' 			=> htmlspecialchars($row['news_title'], ENT_QUOTES),
		'LINK' 				=> generate_link('News&amp;mode=view&amp;id='.$row['news_id'], array('full' => true, 'sid' => false)),
		'DESCRIPTION' 		=> htmlspecialchars(strip_tags($row['news_intro']), ENT_QUOTES),
		'DESCRIPTION_HTML' 	=> htmlspecialchars($row['news_intro'], ENT_QUOTES),
		'TIME'				=> date('M d Y H:i:s', $row['news_time']) .' GMT',
		'AUTHOR'			=> $row['poster_name']
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

//header('Last-Modified: '.$last_modified);

$feed = get_variable('feed', 'GET', false);

Switch ($feed)
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