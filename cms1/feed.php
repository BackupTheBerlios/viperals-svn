<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
// Add last-modified

define('VIPERAL', 'MINILOAD');

//echo str_replace('\\','/', getenv('DOCUMENT_ROOT')); die;
$site_file_root = 'C:/apachefriends/xampp/cms/';

require($site_file_root.'core.php');

//error_reporting(0);
header('Content-Type: text/xml');

$result = $_CLASS['core_db']->sql_query('SELECT id, title, time, intro, poster_name FROM '.$prefix.'_news ORDER BY id DESC LIMIT 10');

$_CLASS['core_template']->assign(array(
		'SITE_NAME' => $_CORE_CONFIG['global']['site_name'],
		'SITE_URL' 	=> $_CORE_CONFIG['global']['site_url'],
		'SLOGAN' 	=> $_CORE_CONFIG['global']['slogan'],
		'LANG'		=> 'en-us',
		'TIME'		=> gmdate('M d Y H:i:s', time()) .' GMT'
	));
		
while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{

	$_CLASS['core_template']->assign_vars_array('items', array(
		'TITLE' 		=> htmlentities($row['title'], ENT_QUOTES),
		'LINK' 			=> getlink('News&amp;mode=view&amp;id='.$row['id'], true, true, false),
//htmlentities causes problems with some chars.
		'DESCRIPTION' 	=> htmlspecialchars(strip_tags($row['intro']), ENT_QUOTES),
		'TIME'			=> gmdate('M d Y H:i:s', $row['time']) .' GMT',
		'AUTHOR'		=> $row['poster_name']
	));
}

$_CLASS['core_db']->sql_freeresult($result);

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