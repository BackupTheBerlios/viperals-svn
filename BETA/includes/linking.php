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
if (!defined('CPG_NUKE')) {
    Header("Location: ../../");
    die();
}

/***********************************************************************************

 string getlink($str="", $UseLEO=true, $full=false)

 Add to the string the correct information to create a link
 and converts the link to LEO if GoogleTap is active
 example: getlink('Your_Account&amp;file=register') returns: "index.php?name=Your_Account&amp;file=register"
 index.php depends on what you've setup in config.php

	Under the GNU General Public License version 2
	Copyright (c) 2004 by CPG-Nuke Dev Team 	http://www.cpgnuke.com

************************************************************************************/
function getlink($str=false, $UseLEO=true, $full=false, $showSID=true) {
    global $Module, $mainindex, $MAIN_CFG, $_CLASS, $SID;
    
    $tempSID = ($showSID) ? $SID : '';
    
    if ((!$str || $str{0} == '&') && !$_CLASS['display']->homepage) $str = $Module['title'].$str;
    
    if ($MAIN_CFG['global']['link_optimization'] && $str && $UseLEO) {
        
        $tempSID = ereg_replace('&amp;', '/', $tempSID);

        if (ereg('file=', $str)) {
            $str = ereg_replace('file=', '', $str);
        } elseif (($first = strpos($str, '&')) !== false) {
            $first = strpos($str, '&');
            $str = substr($str,0,$first).'/index'.substr($str,$first);
        } elseif ($SID) {
            $str .= '/index';
        }
        
        $str = ereg_replace('&amp;', '/', $str);
        $str = ereg_replace('&', '/', $str);
        $str = str_replace('?', '/', $str);
        
        if (ereg('#', $str)) {
            $str = ereg_replace('#', $tempSID.'.html#', $str);
        } else {
			$str .= $tempSID.'.html';
        }
        
        $str = $MAIN_CFG['server']['path'].$str;
        
    } else {
    
        if (!$str)
        {
			$str = $MAIN_CFG['server']['path'].$mainindex; 
        } else {
			if (!$_CLASS['display']->homepage)
			{
				$str = '?name='.$str;
			} else {
				$str = (substr($str, 0, 5) == '&amp;') ? '?' . substr($str, 5) : '?' .$str;
			}
			
			$str = $MAIN_CFG['server']['path'].$mainindex.$str.$tempSID;
		
		}
    }
    
    if ($full)
    {
		$str = 'http://'.getenv('HTTP_HOST').$str;
    }
    
    return $str;
}

/***********************************************************************************

 string adminlink($str='')

 Add to the string the correct information to create a admin link
 example: adminlink('Configure') returns: "admin.php?op=Configure"
 admin.php depends on what you've setup in config.php

************************************************************************************/
function adminlink($str='') {

    global $adminindex;
    if ($str) 
    {
		return $adminindex; 
    }

    return $adminindex.'?op='.$str;
     
}

function url_redirect($url='') {
    global $db, $cache, $mainindex;

	script_close();
	
	$url = ($url) ? $url : 'http://'.getenv('HTTP_HOST').'/'.$MAIN_CFG['server']['path'].$mainindex;
	$url = str_replace('&amp;', '&', $url);
	
	// Redirect via an HTML form for PITA webservers
	if (@preg_match('#Microsoft|WebSTAR|Xitami#', getenv('SERVER_SOFTWARE')))
	{
		header('Refresh: 0; URL=' . $url);
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . $url . '"><title>Redirect</title></head><body><div align="center">' . sprintf($user->lang['URL_REDIRECT'], '<a href="' . $url . '">', '</a>') . '</div></body></html>';
		exit;
	}

	// Behave as per HTTP/1.1 spec for others
	header('Location: ' . $url);
	
    exit;
}
?>
