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

// :-S why did phpbb2.1.2 use session_admin, i had it first good damit. lol
// fix this it should check to see if the admin class is loaded and check the users permission.
// loaded with sessions.

function is_admin()
{
    global $_CLASS;
    return ($_CLASS['user']->data['session_admin']) ? true : false;
}

// I like this so it stays
function is_user()
{
    global $_CLASS;
    return ($_CLASS['user']->data['user_id'] != ANONYMOUS) ? true : false;
}

function set_config($config_name, $config_value, $is_dynamic = false)
{
	global $_CLASS, $config;

	$sql = 'UPDATE ' . CONFIG_TABLE . "
		SET config_value = '" . $_CLASS['db']->sql_escape($config_value) . "'
		WHERE config_name = '" . $_CLASS['db']->sql_escape($config_name) . "'";
	$_CLASS['db']->sql_query($sql);

	if (!$_CLASS['db']->sql_affectedrows() && !isset($config[$config_name]))
	{
		$sql = 'INSERT INTO ' . CONFIG_TABLE . ' ' . $_CLASS['db']->sql_build_array('INSERT', array(
			'config_name'	=> $config_name,
			'config_value'	=> $config_value,
			'is_dynamic'	=> ($is_dynamic) ? 1 : 0));
		$_CLASS['db']->sql_query($sql);
	}

	$config[$config_name] = $config_value;

	if (!$is_dynamic)
	{
		$_CLASS['cache']->destroy('config');
	}
}

function script_close($save = true)
{
	global $MAIN_CFG, $site_file_root, $_CLASS;

	if (!empty($_CLASS['user']))
	{

		//Handle email/cron queue. // phpbb 2.1.2 only.
		//if (time() - $config['queue_interval'] >= $config['last_queue_run'] && !defined('IN_ADMIN'))
		if (file_exists($site_file_root.'cache/queue.php'))
		{
			require_once($site_file_root.'includes/forums/functions_messenger.php');
			$queue = new queue();
			$queue->process();
		}
		
		if ($save)
		{
			if ($MAIN_CFG['global']['error'])
			{
				if (!empty($_CLASS['db']->querylist))
				{
					$_CLASS['user']->set_data('querylist', $_CLASS['db']->querylist);
					$_CLASS['user']->set_data('querydetails', $_CLASS['db']->querydetails);
				}
				
				if (isset($_CLASS['error']) && (!empty($_CLASS['db']->querylist) || !empty($_CLASS['error']->error_array)))
				{
					$_CLASS['user']->set_data('debug', $_CLASS['error']->error_array);
				}
			
			}
						
			$_CLASS['user']->save_session();
		}
		
		$_CLASS['cache']->save();
		$_CLASS['db']->sql_close();
	
	}
	elseif (!empty($_CLASS['cache']))
	{
		$_CLASS['cache']->save();
		$_CLASS['db']->sql_close();
	}
}

function session_users()
{
	global $_CLASS, $config;
	static $loaded = false;
	
	if ($loaded) 
	{
		return $loaded;
	}
	
	$loaded = array();
	
	$sql = 'SELECT u.username, u.user_id, u.user_type, u.user_allow_viewonline, u.user_colour, s.session_ip, s.session_viewonline, s.session_url, s.session_page
			FROM '.USERS_TABLE.' u, ' . SESSIONS_TABLE . ' s
			WHERE s.session_time >= ' . (time() - (intval($config['load_online_time']) * 60)) .'
			AND u.user_id = s.session_user_id
			ORDER BY u.username ASC, s.session_ip ASC';
	$result = $_CLASS['db']->sql_query($sql);
	
	$update = false;
	
	while($row = $_CLASS['db']->sql_fetchrow($result))
	{
		// update current user info with current page and url as it is done at the end of script.
		if (!$update && (($row['user_id'] != ANONYMOUS && $row['user_id'] == $_CLASS['user']->data['user_id']) || ($row['user_id'] == ANONYMOUS && $row['session_ip'] == $_CLASS['user']->ip)))
		{
			$row['session_url'] = $_CLASS['user']->url;
			$row['session_page'] = $_CLASS['user']->page;
			$update = true;
		}
		
		$loaded[] = $row;
	}
	
	$_CLASS['db']->sql_freeresult($result);
	return $loaded;
}

function loadclass($file, $name)
{
	global $_CLASS;

	if (!isset($_CLASS[$name]))
	{
		require($file);
		$_CLASS[$name] =& new $name;
	}
}

function optimize_table($table = false)
{
	global $_CLASS, $MAIN_CFG, $prefix;
	// this needs alot of testing lol. works for me for now.
	if ($table)
	{
		$_CLASS['db']->sql_query('OPTIMIZE TABLE '. $_CLASS['db']->sql_escape($table));
		return;
	}

	$result = $_CLASS['db']->sql_query('SHOW TABLES');
	
	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$key = array_keys($row);

		if ($table)
		{
			$table .= ', ' . $row[$key[0]];
		} else {
			$table = $row[$key[0]];
		}
	}
	
	if ($table)
	{
		$_CLASS['db']->sql_query('OPTIMIZE TABLE '. $_CLASS['db']->sql_escape($table));
		$time = time() + $MAIN_CFG['server']['optimize_rate'];
	}

	$_CLASS['db']->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value='.$time." WHERE cfg_field='optimize_last' AND cfg_name='server'");
	$_CLASS['cache']->destroy('main_cfg');
}


function get_variable($var_name, $type, $default='', $vartype='string')
{
	switch ($type)
	{
		Case 'GET':
			if  (!empty($_GET[$var_name]) && !is_array($_GET[$var_name]))
			{
				return check_variable($_GET[$var_name], $default, $vartype);
			} else {
				return $default;
			}
			
			break;
			
		Case 'POST':
			if (!empty($_POST[$var_name]) && !is_array($_POST[$var_name]))
			{
				return check_variable($_POST[$var_name], $default, $vartype);
			} else {
				return $default;
			}
			
			break;
		
		Case 'REQUEST':
			if (!empty($_REQUEST[$var_name]) && !is_array($_REQUEST[$var_name]))
			{
				return check_variable($_REQUEST[$var_name], $default, $vartype);
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
			// some from phpbb2.1.2 lets make our own,
			$variable = trim(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $variable));
			$variable = preg_replace("#\n{3,}#", "\n\n", $variable);
			$variable = strip_slashes($variable);
		break;
		
	}
	
	return $variable;
}

function strip_slashes($str)
{
	return (STRIP) ? stripslashes($str) : $str ;
}


function tool_tip_text($message)
{
	htmlentities($message);
	$message = trim_text($message, '<br />');
	$message = ereg_replace("'","\'", $message);
	return $message;
}

// fix me, add preg replace,
function trim_text($text, $replacement = ' ')
{
	$text = str_replace("\r\n", $replacement, $text);
	$text = str_replace("\n", $replacement, $text);
	return trim($text);
}

// Windows doesm't require the sorting
// so add a check for windows and skip alot of unneed work
function theme_select($default = '')
{
	static $theme;
	
	if ($theme)
	{
		return $theme;
	}
	
	global $_CLASS;
	
	$themetmp = array();
	
	$theme = '';
	$handle = opendir('themes');
	while ($file = readdir($handle)) {
		if (!ereg('[.]',$file)) {
			if (file_exists("themes/$file/index.php")) {
				$themetmp[] = array('file' => $file, 'template'=> true);
			} elseif (file_exists("themes/$file/theme.php")) {
				$themetmp[] = array('file' => $file, 'template'=> false);
			} 
		} 
	}
	
	closedir($handle);
	
	$count = count($themetmp);
	
	for ($i=0; $i < $count; $i++) {
		
		$themetmp[$i]['name'] = ($themetmp[$i]['template']) ? $themetmp[$i]['file'].' *' : $themetmp[$i]['file'];
		if ($themetmp[$i]['file'] == $_CLASS['display']->theme)
		{
			$theme .= '<option value="'.$themetmp[$i]['file'].'" selected="selected">'.$themetmp[$i]['name'].'</option>';
		} else {
			$theme .= '<option value="'.$themetmp[$i]['file'].'">'.$themetmp[$i]['name'].'</option>';
		}
	}
	
	unset($themetmp);
	
	return $theme;
}

/***********************************************************************************

	Under the GNU General Public License version 2
	Copyright (c) 2004 by CPG-Nuke Dev Team 	http://www.cpgnuke.com

************************************************************************************/
function getlink($str = false, $UseLEO = true, $full = false, $showSID = true)
 {
    global $Module, $mainindex, $MAIN_CFG, $_CLASS, $SID;
    
    $tempSID = ($showSID) ? $SID : '';
    
    if (!$str || $str{0} == '&')
    {
		$str = $Module['title'].$str;
    }
    
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
        
			$str = '?name='.$str;
			$str = $MAIN_CFG['server']['path'].$mainindex.$str.$tempSID;
		
		}
    }
    
    if ($full)
    {
		$str = 'http://'.getenv('HTTP_HOST').$str;
    }
    
    return $str;
}

function adminlink($link)
{
    global $adminindex;
    return $adminindex.'?system='.$link;
}

function url_redirect($url = false)
{
    global $db, $cache, $mainindex;

	script_close();
	
	$url = ($url) ? $url : 'http://'.getenv('HTTP_HOST').'/'.$MAIN_CFG['server']['path'].$mainindex;
	$url = str_replace('&amp;', '&', $url);
	
	// Redirect via an HTML form for PITA webservers
	if (preg_match('#Microsoft|WebSTAR|Xitami#', getenv('SERVER_SOFTWARE')))
	{
		header('Refresh: 0; URL=' . $url);
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . $url . '"><title>Redirect</title></head><body><div align="center">' . sprintf($user->lang['URL_REDIRECT'], '<a href="' . $url . '">', '</a>') . '</div></body></html>';
		exit;
	}

	// Behave as per HTTP/1.1 spec for others
	header('Location: ' . $url);
	
    exit;
}

function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = true, $tpl_prefix = '')
{
	//Code Copyright 2004 phpBB Group - http://www.phpbb.com/
	global $_CLASS;

	$seperator = $_CLASS['user']->img['pagination_sep'];

	$total_pages = ceil($num_items/$per_page);

	if ($total_pages == 1 || !$num_items)
	{
		return false;
	}

	$on_page = floor($start_item / $per_page) + 1;

	$page_string = ($on_page == 1) ? '<strong>1</strong>' : '<a href="' . getlink($base_url, false) . '">1</a>';
	
	if ($total_pages > 5)
	{
		$start_cnt = min(max(1, $on_page - 4), $total_pages - 5);
		$end_cnt = max(min($total_pages, $on_page + 4), 6);

		$page_string .= ($start_cnt > 1) ? ' ... ' : $seperator;

		for($i = $start_cnt + 1; $i < $end_cnt; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page), false) . '">' . $i . '</a>';
			if ($i < $end_cnt - 1)
			{
				$page_string .= $seperator;
			}
		}

		$page_string .= ($end_cnt < $total_pages) ? ' ... ' : $seperator;
	}
	else
	{
		$page_string .= $seperator;

		for($i = 2; $i < $total_pages; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page), false) . '">' . $i . '</a>';
			if ($i < $total_pages)
			{
				$page_string .= $seperator;
			}
		}
	}

	$page_string .= ($on_page == $total_pages) ? '<strong>' . $total_pages . '</strong>' : '<a href="' . getlink($base_url . '&amp;start=' . (($total_pages - 1) * $per_page), false) . '">' . $total_pages . '</a>';

	$_CLASS['template']->assign(array(
		'L_GOTO_PAGE'	=> $_CLASS['user']->lang['GOTO_PAGE'],
		'L_PREVIOUS'	=>	$_CLASS['user']->lang['PREVIOUS'],
		'L_NEXT'		=> $_CLASS['user']->lang['NEXT'],
		'L_PREVIOUS'	=>	$_CLASS['user']->lang['PREVIOUS'],
		$tpl_prefix . 'BASE_URL'	=> getlink($base_url),
		$tpl_prefix . 'PER_PAGE'	=> $per_page,
		
		$tpl_prefix . 'PREVIOUS_PAGE'	=> ($on_page == 1) ? '' : getlink($base_url . '&amp;start=' . (($on_page - 2) * $per_page), false),
		$tpl_prefix . 'NEXT_PAGE'	=> ($on_page == $total_pages) ? '' : getlink($base_url . '&amp;start=' . ($on_page * $per_page), false))
	);
	return $page_string;
}

function on_page($num_items, $per_page, $start)
{
	global $_CLASS;

	$on_page = floor($start / $per_page) + 1;

	$_CLASS['template']->assign('ON_PAGE', $on_page);

	return sprintf($_CLASS['user']->lang['PAGE_OF'], $on_page, max(ceil($num_items / $per_page), 1));
}

function check_email($email)
{
	return preg_match('#^[a-z0-9\.\-_\+]+?@(.*?\.)*?[a-z0-9\-_]+?\.[a-z]{2,4}$#i', $email);
}
?>