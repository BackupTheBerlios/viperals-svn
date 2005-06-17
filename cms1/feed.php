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
$site_file_root = 'C:/apachefriends/xampp/cms/';

require($site_file_root.'core.php');

error_reporting(0);
header('Content-Type: text/xml');

$result = $_CLASS['core_db']->sql_query('SELECT id, title, time, intro, poster_name FROM '.$prefix.'_news ORDER BY id DESC LIMIT 10');

$last_post_time = 0;
	
while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	if ($row['time'] > $last_post_time)
	{
		$last_post_time = $row['time'];
	}
	
	$_CLASS['core_template']->assign_vars_array('items', array(
		'TITLE' 		=> htmlspecialchars($row['title'], ENT_QUOTES),
		'LINK' 			=> generate_link('News&amp;mode=view&amp;id='.$row['id'], array('full' => true)),
		'DESCRIPTION' 	=> htmlspecialchars(strip_tags($row['intro']), ENT_QUOTES),
		'TIME'			=> gmdate('M d Y H:i:s', $row['time']) .' GMT',
		'AUTHOR'		=> $row['poster_name']
	));
}

$_CLASS['core_db']->sql_freeresult($result);

$_CLASS['core_template']->assign(array(
		'SITE_NAME' 	=> $_CORE_CONFIG['global']['site_name'],
		'SITE_URL' 		=> $_CORE_CONFIG['global']['site_url'],
		'SLOGAN' 		=> $_CORE_CONFIG['global']['slogan'],
		'LANG'			=> 'en-us',
		'LAST_MODIFIED'	=> gmdate('M d Y H:i:s', $last_post_time) .' GMT',
		'TIME'			=> gmdate('M d Y H:i:s', time()) .' GMT'
	));


$feed = get_variable('feed', 'GET', false);

Switch ($feed)
{
	case 'rdf':
	$_CLASS['core_template']->display('rss/rdf.html');
	break;
	
	case 'rss2':
	$_CLASS['core_template']->display('rss/rss2.html');
	break;
	
	case 'rss':
	$_CLASS['core_template']->display('rss/rss91.html');
	break;
		
	default:
	$_CLASS['core_template']->display('rss/rss91.html');
}

script_close();
die;

?>