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

function script_close()
{
	global $phperror, $MAIN_CFG, $_CLASS;

	if ($phperror)
	{
		$_CLASS['user']->set_data('debug', $phperror);
	}
	
	if ($MAIN_CFG['global']['error'] == 3)
	{
		$_CLASS['user']->set_data('querylist', $_CLASS['db']->querylist);
		$_CLASS['user']->set_data('querydetails', $_CLASS['db']->querydetails);
	}
	
	//Handle email/cron queue. // phpbb 2.1.2 only.
	//if (time() - $config['queue_interval'] >= $config['last_queue_run'] && !defined('IN_ADMIN'))
	//{
		if (file_exists('cache/queue.php'))
		{
			requireOnce('includes/forums/functions_messenger.'.$phpEx);
			$queue = new queue();
			$queue->process();
		}
	//}
	
	$_CLASS['user']->save_session();
	$_CLASS['cache']->save();
	$_CLASS['db']->sql_close();

}

function session_users()
{
	global $_CLASS, $config;
	static $loaded = false;
	
	if ($loaded) 
	{
		return;
	}
	
	$loaded = array();
	
	$sql = 'SELECT u.username, u.user_id, u.user_allow_viewonline, u.user_type, u.username,
				s.session_ip, s.session_viewonline, u.user_colour, s.session_page, s.session_url
			FROM '.USERS_TABLE.' u, ' . SESSIONS_TABLE . ' s
		WHERE u.user_id = s.session_user_id
			AND s.session_time >= ' . (time() - ($config['load_online_time'] * 60)) . ' 
			ORDER BY u.username ASC, s.session_ip ASC';
	$result = $_CLASS['db']->sql_query($sql);
	
	while($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$loaded[] = $row;
	}
	
	$_CLASS['db']->sql_freeresult($result);
	return $loaded;
}

function requireOnce($file) {
	static $loaded = array();

	if (!isset($loaded[$file])) {
		require_once($file);
		$loaded[$file] = 1;
	}
}

function loadclass($file, $name) {
	global $_CLASS;

	if (!isset($_CLASS[$name])) {
		require($file);
		$_CLASS[$name] =& new $name;
	}
}
   
function optimize_table($table = false) {
	global $_CLASS;

	If ($table) {
		$_CLASS['db']->sql_query('OPTIMIZE TABLE '. $_CLASS['db']->sql_escape($table));
	}

	$result = $_CLASS['db']->sql_query('SHOW TABLES');
	
	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
	
			if ($table) {
				$table .= ', ' . $row['0'];
			} else {
				$table = $row['0'];
			}
	   
	}	
		
	$time = time() + $MAIN_CFG['server']['optimizerate'];
	$_CLASS['db']->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".$time."' WHERE cfg_field='nextoptimize' AND cfg_name='server'");
	$_CLASS['db']->sql_query('OPTIMIZE TABLE '. put_string($table, $type='string', $nohtml=1));
			
}

function get_variable($var_name, $type, $default='', $vartype='string')
{
	switch ($type)
	{
		Case 'GET':
			if  (!empty($_GET[$var_name]) && !is_array($_GET[$var_name])) {
				$variable = check_variable($_GET[$var_name], $default, $vartype);
				return $variable;
			} else {
				return $default;
			}
			
			break;
			
		Case 'POST':
			if (!empty($_POST[$var_name]) && !is_array($_POST[$var_name])) {
				$variable = check_variable($_POST[$var_name], $default, $vartype);
				return $variable;
			} else {
				return $default;
			}
			
			break;
			
		default:
			return $default;
			break;
	}
}

function check_variable($variable, $default, $vartype)
{
	switch ($vartype)
	{
	 	Case 'integer':
			$variable = (is_numeric($variable)) ? $variable : $default;
		break;
		
		default:
			$variable = trim(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $variable));
			$variable = preg_replace("#\n{3,}#", "\n\n", $variable);
			$variable = strip_slashes($variable);
		break;
		
	}
	
	return $variable;
}

function put_string($str, $type='string', $nohtml=0)
{
    global $_CLASS;
    
    if ($type == 'integer') {
		return (is_numeric($str)) ? $str : '0';
    }
    
    if ($nohtml)
    {
		$str = strip_tags($str); 
    }
    
    return $_CLASS['db']->sql_escape($str);
}

function strip_slashes($str)
{
	return (STRIP) ? stripslashes($str) : $str ;
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