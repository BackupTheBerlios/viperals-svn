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
// Test some more cpguke and phpnuke modules

if (!defined('VIPERAL'))
{
    Header('Location: ../../');
    die();
}

//error_reporting(E_ALL);
define('CPG_NUKE', 'CMS');

//Copyright cpgnuke or phpnuke, not sure with one, replace with my checker.
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	foreach ($_POST as $value)
	{
 		if (eregi("<[^>]*script *\"?[^>]*>", $value))
		{
		   trigger_error('<b>The html tags you attempted to use are not allowed</b>', E_USER_ERROR);
		}
	}
}

// if something is set, it has a reason to be set already
extract($MAIN_CFG['global'], EXTR_SKIP);  

$currentlang = 'english'; // :-S
require($site_file_root.'language/lang-english.php');

if (!ini_get('register_globals'))
{
	// Import GET/POST/Cookie variables
	// don't want them replacing any globals we have and checked now do we.
	// Maybe check all data or something for bad codes, !! maybe a a foreach funtion
	extract($_POST, EXTR_SKIP);
	extract($_GET, EXTR_SKIP);
	//import_request_variables('GPC');
}

// bla bla bla
function get_theme()
{
    global $_CLASS;
    return $_CLASS['display']->theme;
}

function formatTimestamp($time)
{
	return formatDateTime($time);
}

//	Wonder how this work with differnent time formats
//	2004-05-13 - Works so hopefully its ok.
//	check with deferent php version closer to beta
function formatDateTime($time, $format ='')
{
    global $_CLASS;
    
    if (!is_numeric($time)) 
    {
       $time = strtotime($time);
    }
	
    return $_CLASS['user']->format_date($time);
}

// only english for now
function get_lang($module)
{
    global $site_file_root;

    $path = $site_file_root.($module == 'admin') ? 'admin/language' : "modules/$module/language";
    
	include_once($path.'/lang-english.php');

}

function check_html($str, $strip="")
{
	/* The core of this code has been lifted from phpslash */
	/* which is licenced under the GPL. */
	global $AllowableHTML;
	if ($strip == "nohtml") { $HTML=array(''); }
	else { $HTML = $AllowableHTML; }
	$str = stripslashes($str);
	$str = eregi_replace("<[[:space:]]*([^>]*)[[:space:]]*>",'<\\1>', $str);
	// Delete all spaces from html tags .
	$str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?[[:space:]]*([^\" >]*)[[:space:]]*\"?[^>]*>",'<a href="\\1">', $str);
	// Delete all attribs from Anchor, except an href, double quoted.
	$str = eregi_replace("<[[:space:]]* img[[:space:]]*([^>]*)[[:space:]]*>", '', $str);
	// Delete all img tags
	$str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?javascript[[:punct:]]*\"?[^>]*>", '', $str);
	// Delete javascript code from a href tags -- Zhen-Xjell @ http://nukecops.com
	$tmp = "";
	while (ereg("<(/?[[:alpha:]]*)[[:space:]]*([^>]*)>",$str,$reg)) {
		$i = strpos($str,$reg[0]);
		$l = strlen($reg[0]);
		if ($reg[1][0] == "/") $tag = strtolower(substr($reg[1],1));
		else $tag = strtolower($reg[1]);
		if ($a = $HTML[$tag]) {
			if ($reg[1][0] == "/") $tag = "</$tag>";
			elseif (($a == 1) || ($reg[2] == "")) $tag = "<$tag>";
			else {
				# Place here the double quote fix function.
				$attrb_list=delQuotes($reg[2]);
				// A VER
				$attrb_list = ereg_replace("&","&amp;",$attrb_list);
				$tag = "<$tag" . $attrb_list . ">";
			}
		} # Attribs in tag allowed
		else $tag = "";
		$tmp .= substr($str,0,$i) . $tag;
		$str = substr($str,$i+$l);
	}
	$str = $tmp . $str;
	$str = addslashes($str);
	return $str;
//	exit;
	/* Squash PHP tags unconditionally */
//	$str = ereg_replace("<\?","",$str);
//	return $str;
}
?>