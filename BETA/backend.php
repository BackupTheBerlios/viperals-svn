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

define('CPG_NUKE', 'XMLFEED');
error_reporting(0);

require('mainfile.php');

header('Content-Type: text/xml');

global $_CLASS;

$result = $_CLASS['db']->sql_query('SELECT id, title, time, intro, poster_name FROM '.$prefix.'_news ORDER BY id DESC LIMIT 10');

$_CLASS['template']->assign(array(
		'SITE_NAME' => $MAIN_CFG['global']['sitename'],
		'SITE_URL' 	=> $MAIN_CFG['global']['nukeurl'],
		'SLOGAN' 	=> $MAIN_CFG['global']['slogan'],
		'LANG'		=> $MAIN_CFG['global']['backend_language'],
		'TIME'		=> time()
	));
		
while ($row = $db->sql_fetchrow($result)) {
		$_CLASS['template']->assign_vars_array('items', array(
		
		'TITLE' 		=> htmlentities($row['title'], ENT_QUOTES),
		'LINK' 			=> getlink('News&amp;mode=view&amp;id='.$row['id'], true, true, false),
		'DESCRIPTION' 	=> htmlentities(strip_tags($row['intro']), ENT_QUOTES),
		'TIME'			=> $row['time'],
		'AUTHOR'		=> $row['poster_name']
	));
}
$_CLASS['db']->sql_freeresult($result);

$feed = get_variable('feed', 'GET', false);

Switch ($feed)
{
	case 'rdf':
	$_CLASS['template']->display('rss/rdf.html');
	break;
	
	case 'rss2':
	$_CLASS['template']->display('rss/rss2.html');
	break;
	
	case 'rss':
	$_CLASS['template']->display('rss/rss91.html');
	break;
		
	default:
	$_CLASS['template']->display('rss/rss91.html');
}

require('footer.php');

?>