<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal�	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
// Update/Add Copyright on non orignal code

function check_email($email)
{
	return preg_match('#^[a-z0-9\.\-_\+]+?@(.*?\.)*?[a-z0-9\-_]+?\.[a-z]{2,4}$#i', $email);
}

function check_bot_status($browser, $ip)
{
	$bots_array = get_bots();
	$is_bot = false;

	foreach ($bots_array as $bot)
	{
		if ($bot['user_agent'] && preg_match('#' . preg_quote($bot['user_agent'], '#') . '#i', $browser))
		{
			$is_bot = $bot['user_id'];
		}
		
		if ($bot['user_ip'] && (!$bot['user_agent'] || $is_bot))
		{
			$is_bot = false;
			
			foreach (explode(',', $bot['user_ip']) as $bot_ip)
			{
				if (strpos($ip, $bot_ip) === 0)
				{
					$is_bot = $bot['user_id'];
				}
			}
		}
		
		if ($is_bot)
		{
			if ($bot['user_type'] == USER_BOT_INACTIVE)
			{
				// How would this affect indexing ?
				header("HTTP/1.0 503 Service Unavailable");
				script_close(false);
			}
			break;
		}
	}
	
	return $is_bot;
}

/*
*/
function check_load_status($return = false)
{
	global $_CORE_CONFIG, $_CLASS;
	static $load_status = null;
	
	if (!is_null($load_status))
	{
		return $load_status;
	}

	$load_status = 0;

	if (file_exists('/proc/loadavg'))
	{
		if ($load = file('/proc/loadavg'))
		{
			list($load_status) = explode(' ', $load[0]);

			if ($_CORE_CONFIG['server']['limit_load'] && $load_status > doubleval($_CORE_CONFIG['server']['limit_load']) && VIPERAL != 'Admin')
			{
				if (VIPERAL == 'Admin' || (isset($_CLASS['core_user']) && $_CLASS['core_user']->is_admin))
				{
					return $load_status;
				}
				
				if ($return)
				{
					return $load_status = true;
				}

				trigger_error('503:SITE_UNAVAILABLE');
			}
		}
	}

	return $load_status;
}

function check_maintance_status($return = false)
{
	global $_CORE_CONFIG, $_CLASS;
	static $maintance_status = null;

	if (!is_null($maintance_status))
	{
		return $maintance_status;
	}

	if ($_CORE_CONFIG['maintenance']['active'])
	{
		if ($_CORE_CONFIG['maintenance']['start'] < time())
		{
			if (VIPERAL == 'Admin' || (isset($_CLASS['core_user']) && $_CLASS['core_user']->is_admin))
			{
				return $maintance_status = false;
			}
			
			if ($return)
			{
				return $maintance_status = true;
			}

			trigger_error('503:'.$_CORE_CONFIG['maintenance']['text'], E_USER_ERROR);
		}
		
		return $_CORE_CONFIG['maintenance']['start'];
	}

	return $maintance_status = false;
}

function check_theme($theme)
{
	global $site_file_root;
	
	if (file_exists($site_file_root.'themes/'.$theme.'/index.php'))
	{
		return true;
	}
	return false;
}

function encode_password($pure, $encoding = 'md5')
{
	switch ($encoding)
	{
		Case 'md5':
			return md5($pure);
		break;

		Case 'sha1':
			if (function_exists('sha1'))
			{
				return sha1($pure);
			}
			$encoding = 'MHASH_SHA1';
		break;
	}

	if (strpos($encoding, 'MHASH_') !== false && function_exists('mhash'))
	{
		return bin2hex(mhash($encoding, $pure));
	}

	return false;
}

function get_bots()
{
	global $_CLASS;

	if (($bots = $_CLASS['core_cache']->get('bots')) === false)
	{
		$bots = array();
		
		$sql = 'SELECT user_id, username, user_agent, user_ip
			FROM ' . USERS_TABLE . ' WHERE user_type IN (' . USER_BOT_ACTIVE . ', ' . USER_BOT_INACTIVE . ')';
		$result = $_CLASS['core_db']->sql_query($sql);
			
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$bots[] = $row;
		}
		
		$_CLASS['core_db']->sql_freeresult($result);
		$_CLASS['core_cache']->put('bots', $bots);
	}
	
	return $bots;
}

function get_variable($var_name, $type, $default = '', $vartype = 'string')
{
/*	$type = "_$type";

	global $$type;
	
// If linking works the way I think  it should be ok, else it could slow things down
	$type =& $$type;
	
	if (isset($type[$var_name]) && !is_array($type[$var_name]))
	{
		return check_variable($type[$var_name], $default, $vartype);
	}
	else
	{
		return $default;
	}
	
*/	
	$variable = null;

	switch ($type)
	{
		Case 'GET':
			$variable = isset($_GET[$var_name]) ? $_GET[$var_name] : $default;
		break;

		Case 'POST':
			$variable = isset($_POST[$var_name]) ? $_POST[$var_name] : $default;
		break;

		Case 'REQUEST':
			$variable = isset($_REQUEST[$var_name]) ? $_REQUEST[$var_name] : $default;
		break;

		Case 'COOKIE':
			$variable = isset($_COOKIE[$var_name]) ? $_COOKIE[$var_name] : $default;
		break;
	}
			
	if (is_null($variable))
	{
		return $default;
	}
	else
	{
		switch ($type)
		{
		 	Case 'integer':
				$variable = is_numeric($variable) ? (int) $variable : $default;
			break;
		
			default:
				$variable = trim(modify_lines(str_replace('\xFF', ' ', $variable), "\n"));
				//$variable = trim(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $variable));
				$variable = strip_slashes($variable);
			break;
		}

		return $variable;
	}
}

function generate_base_url()
{
	static $base = false;
	
	if (!$base)
	{
		global $_CORE_CONFIG;
		
		$base = ($_CORE_CONFIG['server']['cookie_secure']) ? 'https://' : 'http://' ;
		$base .= trim((isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_CORE_CONFIG['server']['site_domain']));
		$base .= (($_CORE_CONFIG['server']['site_port'] != 80) ? ':' . trim($_CORE_CONFIG['server']['site_port']) : '') . $_CORE_CONFIG['server']['site_path'];
	}
	
	return $base;
}

function generate_link($link = false, $link_options = false)
{
	global $_CLASS, $_CORE_MODULE;
	
	$options = array(
		'admin'		=> false,
		'full'		=> false,
		'sid'		=> true,
		'force_sid' => false
	);

	if (is_array($link_options))
	{
		$options = array_merge($options, $link_options);
	} 	

	$file = ($options['admin']) ? ADMIN_PAGE : INDEX_PAGE;
	
	if (!$link)
	{
		$link = $file;

		if ($options['force_sid'] || ($_CLASS['core_user']->sid_link && $options['sid']))
		{
			$link .= '?'.$_CLASS['core_user']->sid_link;
		}
	}
	else
	{
		if ($link{0} == '&')
		{
			$link = $_CORE_MODULE['title'].$link;
		}
		
		$link = $file.'?mod='.$link;

		if ($options['force_sid'] || ($_CLASS['core_user']->sid_link && $options['sid']))
		{
			$link .= '&amp;'.$_CLASS['core_user']->sid_link;
		}
    }

    if ($options['full'])
    {
		return generate_base_url().$link;
    }

    return $link;
}

// to be redone
function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = false, $tpl_prefix = '')
{
	//Code Copyright 2004 phpBB Group - http://www.phpbb.com/
	global $_CLASS;

	$seperator = ' | ';

	$admin_link = (VIPERAL == 'Admin') ? array('admin' => true) : '';
	
	$total_pages = ceil($num_items/$per_page);

	if ($total_pages == 1 || !$num_items)
	{
		return false;
	}

	$on_page = floor($start_item / $per_page) + 1;

	$page_string = ($on_page == 1) ? '<strong>1</strong>' : '<a href="' . generate_link($base_url, $admin_link) . '">1</a>';
	
	if ($total_pages > 5)
	{
		$start_cnt = min(max(1, $on_page - 4), $total_pages - 5);
		$end_cnt = max(min($total_pages, $on_page + 4), 6);

		$page_string .= ($start_cnt > 1) ? ' ... ' : $seperator;

		for($i = $start_cnt + 1; $i < $end_cnt; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . generate_link($base_url . "&amp;start=" . (($i - 1) * $per_page), $admin_link) . '">' . $i . '</a>';
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
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . generate_link($base_url . "&amp;start=" . (($i - 1) * $per_page), $admin_link) . '">' . $i . '</a>';
			if ($i < $total_pages)
			{
				$page_string .= $seperator;
			}
		}
	}

	$page_string .= ($on_page == $total_pages) ? '<strong>' . $total_pages . '</strong>' : '<a href="' . generate_link($base_url . '&amp;start=' . (($total_pages - 1) * $per_page), $admin_link) . '">' . $total_pages . '</a>';

	if ($add_prevnext_text)
	{
		if ($on_page != 1) 
		{
			$page_string = '<a href="' . generate_link($base_url . '&amp;start=' . (($on_page - 2) * $per_page), $admin_link) . '">' . $_CLASS['core_user']->lang['PREVIOUS'] . '</a>&nbsp;&nbsp;' . $page_string;
		}

		if ($on_page != $total_pages)
		{
			$page_string .= '&nbsp;&nbsp;<a href="' . generate_link($base_url . '&amp;start=' . ($on_page * $per_page), $admin_link) . '">' . $_CLASS['core_user']->lang['NEXT'] . '</a>';
		}
	}
	
	$_CLASS['core_template']->assign(array(
		$tpl_prefix . 'BASE_URL'	=> generate_link($base_url),
		$tpl_prefix . 'PER_PAGE'	=> $per_page,
		
		$tpl_prefix . 'PREVIOUS_PAGE'	=> ($on_page == 1) ? '' : generate_link($base_url . '&amp;start=' . (($on_page - 2) * $per_page), $admin_link),
		$tpl_prefix . 'NEXT_PAGE'	=> ($on_page == $total_pages) ? '' : generate_link($base_url . '&amp;start=' . ($on_page * $per_page), $admin_link))
	);
	return $page_string;
}

/*
	Generates random strings
*/
function generate_string($length)
{
	// Add type
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	$string = '';
	$num_chars = strlen($chars) - 1;

	for ($i = 0; $i < $length; ++$i)
	{
		$string .= substr($chars, mt_rand(0, $num_chars), 1);
	}

	return $string;
}

if (!function_exists('html_entity_decode'))
{
	//string html_entity_decode ( string string [, int quote_style [, string charset]] )
	function html_entity_decode($string, $quote_style = ENT_COMPAT, $charset = '')
	{
		$translations = array_flip(get_html_translation_table(HTML_ENTITIES, $quote_style));
		return strtr($string, $translations);
	}
}

function load_class($file, $name, $class = false)
{
	global $_CLASS;
	
	$class = ($class) ? $class : $name;
	
	if (empty($_CLASS[$name]) || !is_object($_CLASS[$name]))
	{
		if ($file)
		{
			include_once($file);
		}
		$_CLASS[$name] =& new $class;
	}
}

function login_box($login_options = false, $template = false)
{
	global $_CLASS;

	$_CLASS['core_auth']->do_login($login_options, $template);
}

function set_core_config($section, $name, $value, $clear_cache = true, $auto_add = false)
{
	global $_CLASS, $_CORE_CONFIG;
	
	$_CLASS['core_db']->sql_return_on_error(true);

	$sql = 'UPDATE ' . CORE_CONFIG_TABLE . " SET value ='".$_CLASS['core_db']->sql_escape($value) . "'
		WHERE (section = '" . $_CLASS['core_db']->sql_escape($section) . "')
			AND (name = '". $_CLASS['core_db']->sql_escape($name) ."')";

	if (!$_CLASS['core_db']->sql_query($sql) && $auto_add)
	{
		$sql_array = array(
			'section'	=> $section,
			'name'		=> $name,
			'value'		=> $value
		);
		
		$_CLASS['core_db']->sql_return_on_error(false);

		$sql = 'INSERT INTO ' . CORE_CONFIG_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_array);
		$_CLASS['core_db']->sql_query($sql);
	}

	$_CLASS['core_db']->sql_return_on_error(false);
	
	$_CORE_CONFIG[$section][$name] = $value;
	
	if ($clear_cache)
	{
		$_CLASS['core_cache']->destroy('core_config');
	}
}

function script_close($save = true)
{
	global $_CORE_CONFIG, $site_file_root, $_CLASS;

	if (defined('SCRIPT_CLOSED'))
	{
		return;
	}

	define('SCRIPT_CLOSED', true);

	$closed = true;
	
	if (!empty($_CLASS['core_user']))
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
			if ($_CLASS['core_user']->is_admin && $_CORE_CONFIG['server']['error_options'])
			{
				if (!empty($_CLASS['core_db']->querylist))
				{
					$_CLASS['core_user']->set_data('querylist', $_CLASS['core_db']->querylist);
					$_CLASS['core_user']->set_data('querydetails', $_CLASS['core_db']->querydetails);
				}
				
				if (isset($_CLASS['core_error_handler']) && (!empty($_CLASS['core_db']->querylist) || !empty($_CLASS['core_error_handler']->error_array)))
				{
					$_CLASS['core_user']->set_data('debug', $_CLASS['core_error_handler']->error_array);
				}
			}
						
			$_CLASS['core_user']->save();
		}	
	}
	
	if (!empty($_CLASS['core_cache']))
	{
		$_CLASS['core_cache']->save();
	}
	
	if (!empty($_CLASS['core_error_handler']))
	{
		$_CLASS['core_error_handler']->stop();
//stopped to halt the db sql_close error, look into a fix.
	}
	
	if (!empty($_CLASS['core_db']))
	{
		$_CLASS['core_db']->sql_close();
	}

	die;
}

function on_page($num_items, $per_page, $start)
{
	global $_CLASS;

	$on_page = floor($start / $per_page) + 1;

	$_CLASS['core_template']->assign('ON_PAGE', $on_page);

	return sprintf($_CLASS['core_user']->lang['PAGE_OF'], $on_page, max(ceil($num_items / $per_page), 1));
}

// to keep or not to keep ?
function session_users()
{
	global $_CLASS, $config;
	static $loaded = false;
	
	if ($loaded) 
	{
		return $loaded;
	}

	$loaded = array();
	//	WHERE s.session_time >= ' . (time() - (intval($config['load_online_time']) * 60)) .'

	$sql = 'SELECT s.*, u.username, u.user_id, u.user_type, u.user_allow_viewonline, u.user_colour
				FROM ' . SESSIONS_TABLE . ' s, '.USERS_TABLE.' u
					WHERE s.session_time >= ' . (time() - (21600)) .'
				AND u.user_id = s.session_user_id';
	$result = $_CLASS['core_db']->sql_query($sql);
	
	$update = false;
	
	while($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		// update current user info with current page and url as it is done at the end of script.
		if (!$update && (($row['user_id'] != ANONYMOUS && $row['user_id'] == $_CLASS['core_user']->data['user_id']) || ($row['user_id'] == ANONYMOUS && $row['session_ip'] == $_CLASS['core_user']->ip)))
		{
			$row['session_url'] = $_CLASS['core_user']->url;
			$row['session_page'] = $_CLASS['core_user']->page;
			$update = true;
		}
		
		$loaded[] = $row;
	}
	
	$_CLASS['core_db']->sql_freeresult($result);
	return $loaded;
}

function strip_slashes($str)
{
	return (STRIP) ? stripslashes($str) : $str ;
}

function theme_select($default = false)
{
	global $site_file_root, $_CLASS;
	
	$themetmp = array();
	$default = ($default) ? $default : $_CLASS['core_display']->theme;
	
	$theme = '';
	$handle = opendir($site_file_root.'themes');
	while ($file = readdir($handle))
	{
		if ($file{0} !== '.')
		{
			if (file_exists($site_file_root."themes/$file/index.php"))
			{
				$themetmp[] = array('file' => $file, 'template'=> true);
			}
		}
	}
	
	closedir($handle);
	
	$count = count($themetmp);
	
	for ($i=0; $i < $count; $i++)
	{
		if ($themetmp[$i]['file'] == $default)
		{
			$theme .= '<option value="'.$themetmp[$i]['file'].'" selected="selected">'.$themetmp[$i]['file'].'</option>';
		}
		else
		{
			$theme .= '<option value="'.$themetmp[$i]['file'].'">'.$themetmp[$i]['file'].'</option>';
		}
	}
	
	return $theme;
}

function modify_lines($text, $replacement = ' ')
{
	return str_replace(array("\r\n", "\r", "\n"), $replacement, $text);
}

function url_redirect($url = false, $save = false)
{
	$url = ($url) ? $url : generate_link(false, array('full' => true));
	$url = str_replace('&amp;', '&', $url);

	if (preg_match('/IIS|Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
	{
		header('Refresh: 0; URL=' . $url);
	}
	else
	{
		header('Location: ' . $url);
	}

	echo 'Something here for bad browers, hehe';

	script_close($save);
}

?>