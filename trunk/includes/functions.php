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
// Redo
function check_email($email)
{
	//Code Copyright 2004 phpBB Group - http://www.phpbb.com/
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
			if ($bot['user_status'] == USER_DISABLE)
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

function check_collapsed_status($marker, $cookie = 'collapsed_items') 
{
    static $collapsed_items_array = array();

    if (!isset($collapsed_items_array[$cookie]))
    {
		$cookie_data = get_variable($cookie, 'COOKIE');
		$collapsed_items_array[$cookie] = ($cookie_data) ? explode(':', $cookie_data) : array();
    }

    return in_array($marker, $collapsed_items_array[$cookie]);
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

	if ($load = get_server_load())
	{
		if ($_CORE_CONFIG['server']['limit_load'] && $load > doubleval($_CORE_CONFIG['server']['limit_load']) && VIPERAL != 'Admin')
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

	return $load_status;
}

function get_server_load()
{
	$load = 0;

	if (file_exists('/proc/loadavg'))
	{
		if ($file = file_get_contents('/proc/loadavg'))
		{
			list($load) = explode(' ', $file);
		}
	}

	/*elseif ($load = @exec('uptime'))
	{
		$load =  substr($load, stristr('averages?:', $load));
		list($load) = explode(',', $load);
	}*/

	return $load;
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

function display_confirmation($check = false, $hidden = '')
{
	global $_CLASS, $SID;
// Add user entered confirmation code as a choose, maybe ...
	if ($check)
	{
		if (isset($_POST['cancel']))
		{
			return false;
		}
	
		if (isset($_POST['confirm']))
		{
			$code = $_CLASS['core_user']->session_data_get('confirmation_code');
			$confirm_code = get_variable('confirm_code', 'POST');

			if ($code && $confirm_code && $code !== $confirm_code)
			{
				return true;
			}
		}

		return false;
	}

	$confirmation_code = generate_string(6);
	$_CLASS['core_user']->session_data_set('confirmation_code', $confirmation_code);

	$_CLASS['core_template']->assign(array(
		'S_CONFIRM_ACTION'  => generate_link($_CLASS['core_user']->url),
		'S_HIDDEN_FIELDS'	=> $hidden.'<input type="hidden" name="confirm_code" value="' . $confirmation_code . '" />'
	));

	$_CLASS['core_template']->display('confirm_display.html');
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

	if (is_null($bots = $_CLASS['core_cache']->get('bots')))
	{
		$bots = array();
		
		$sql = 'SELECT user_id, username, user_agent, user_ip
			FROM ' . USERS_TABLE . ' WHERE user_type = ' . USER_BOT;
		$result = $_CLASS['core_db']->query($sql);
			
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$bots[] = $row;
		}
		
		$_CLASS['core_db']->free_result($result);
		$_CLASS['core_cache']->put('bots', $bots);
	}
	
	return $bots;
}

function get_variable($var_name, $type, $default = false, $var_type = 'string')
{
	$variable = null;
	
	$type = strtoupper($type);

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
		switch ($var_type)
		{
		 	case 'integer':
				$variable = is_numeric($variable) ? (int) $variable : $default;
			break;

			case 'array':
				if (!is_array($variable))
				{
					return $default;
				}

// need to add a function here to loop multi... arrays
				foreach ($variable as $key => $value)
				{
					$variable[$key] = strip_slashes(trim(modify_lines(str_replace('\xFF', ' ', $value), "\n")));
				}
			break;

			default:
				$variable = strip_slashes(trim(modify_lines(str_replace('\xFF', ' ', $variable), "\n")));
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

	if ($link && strpos($link, '#'))
	{
		list($link, $what_you_call_this) = explode('#', $link, 2);
	}
	else
	{
		$what_you_call_this = false;
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

    if ($what_you_call_this)
    {
		$link .= '#'.$what_you_call_this;
	}

    return ($options['full']) ? generate_base_url().$link : $link;
}

function generate_pagination($base_url, $total, $per_page = 10, $start = 0, $admin_link = false)
{
	global $_CLASS;

	if ($total <= $per_page)
	{
		return false;
	}

	$wrapper['normal'] = '<span class="page_link_normal"><a href="%1$s">%2$d</a></span>';
	$wrapper['current'] = '<span class="page_link_normal">%2$d</span>';
	$wrapper['first'] = '<span class="page_link_normal"><a href="%1$s">%2$d</a></span>';
	$wrapper['last'] = '<span class="page_link_normal"><a href="%1$s">%2$d</a></span>';

	$total_pages = ceil($total / $per_page);
	$current_page = ($start) ? floor($start / $per_page) + 1 : 1;

	$display['formated'] = '';
	$display['array'] = array();

	if ($total_pages > 8)
	{
		if ($current_page < 4)
		{
			$start = 1;
			$end = 5;
		}
		else
		{
			$end = min($total_pages, $current_page + 2);
			$start = ($end > $total_pages - 2) ? $total_pages - 4 : $current_page - 2;

			$display['array'][] = array('page' => 1, 'link' => generate_link($base_url, $admin_link), 'seperator' => 'after');
			$display['formated'] = sprintf($wrapper['first'], generate_link($base_url, $admin_link), 1);
		}
	}
	else
	{
		$start = 1;
		$end = $total_pages;
	}

	for($i = $start; $i <= $end; $i++)
	{
		$display['array'][] = array('page' => $i, 'link' => generate_link($base_url.'&amp;start='.(($i - 1) * $per_page)), 'seperator' => false);
		
		$this_wrapper = ($current_page == $i) ? $wrapper['current'] : $wrapper['normal'];
		$display['formated'] .= sprintf($this_wrapper, generate_link($base_url.'&amp;start='.(($i - 1) * $per_page)), $i);
	}

	if ($end != $total_pages)
	{
		$display['array'][] = array('page' => $total_pages, 'link' => generate_link($base_url.'&amp;start='.(($total_pages - 1) * $per_page), $admin_link), 'seperator' => 'before');
		$display['formated'] .= sprintf($wrapper['last'], generate_link($base_url.'&amp;start='.(($total_pages - 1)  * $per_page), $admin_link), $total_pages);
	}

//TMP
	return $display['formated'];
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

function gmtime()
{
	return (time() - date('Z'));
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

	$sql = 'UPDATE ' . CORE_CONFIG_TABLE . " SET value ='".$_CLASS['core_db']->escape($value) . "'
		WHERE (section = '" . $_CLASS['core_db']->escape($section) . "')
			AND (name = '". $_CLASS['core_db']->escape($name) ."')";

	if (!$_CLASS['core_db']->query($sql) && $auto_add)
	{
		$sql_array = array(
			'section'	=> $section,
			'name'		=> $name,
			'value'		=> $value
		);
		
		$_CLASS['core_db']->sql_return_on_error(false);

		$sql = 'INSERT INTO ' . CORE_CONFIG_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_array);
		$_CLASS['core_db']->query($sql);
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

	if (!empty($_CLASS['core_user']))
	{
		// phpbb 2.1.2 only remove.
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
				if (!empty($_CLASS['core_db']->query_list))
				{
					$_CLASS['core_user']->session_data_set('query_list', $_CLASS['core_db']->query_list);
					$_CLASS['core_user']->session_data_set('query_details', $_CLASS['core_db']->query_details);
				}
				
				if (isset($_CLASS['core_error_handler']) && !empty($_CLASS['core_error_handler']->error_array))
				{
					$_CLASS['core_user']->session_data_set('debug', $_CLASS['core_error_handler']->error_array);
				}
			}
						
			$_CLASS['core_user']->save();
		}	
	}
	
	if (!empty($_CLASS['core_cache']))
	{
		$_CLASS['core_cache']->save();
	}
	
	if (!empty($_CLASS['core_db']))
	{
		$_CLASS['core_db']->disconnect();
	}

	if (!empty($_CLASS['core_error_handler']))
	{
		$_CLASS['core_error_handler']->stop();
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
	$result = $_CLASS['core_db']->query($sql);
	
	$update = false;
	
	while($row = $_CLASS['core_db']->fetch_row_assoc($result))
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
	
	$_CLASS['core_db']->free_result($result);
	return $loaded;
}

function strip_slashes($str)
{
	return (STRIP_SLASHES) ? stripslashes($str) : $str ;
}

function theme_select($default = false)
{
	global $site_file_root, $_CLASS;
	
	$themetmp = array();
	$default = ($default) ? $default : $_CLASS['core_display']->theme_name;
	
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
	
	for ($i = 0; $i < $count; $i++)
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

function modify_lines($text, $replacement = '')
{
	return str_replace(array("\r\n", "\r", "\n"), $replacement, $text);
}

function url_redirect($url = false, $save = false)
{
	$url = ($url) ? $url : generate_link(false, array('full' => true));
	$url = trim(str_replace('&amp;', '&', $url));

	header('Location: ' . $url);

	header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
	header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
	header('Expires: 0');
	header('Pragma: no-cache');
		
	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
			<meta http-equiv="refresh" content="0; url=' . $url . '">
			<title>Redirect</title>
		</head>
		<body>
			<div align="center"><a href="' . $url . '">Click here to continue</a></div>
		</body>
	</html>';

	script_close($save);
}

if (!function_exists('file_get_contents'))
{
	//string file_get_contents ( string filename [, bool use_include_path [, resource context [, int offset [, int maxlen]]]] )
	function file_get_contents($file, $use_include_path = false)
	{
		$handle = fopen($file, 'rb', $use_include_path);

		if (!$handle)
		{
			return false;
		}

		$contents = '';

		while (!feof($handle))
		{
			$contents .= fread($handle, 8192);
		}

		fclose($handle);
	}
}

if (!function_exists('file_put_contents'))
{
	//int file_put_contents ( string filename, mixed data [, int flags [, resource context]] )
	function file_put_contents($file, $file_data)
	{
		$bytes = false;

		if (is_array($file_data))
		{
			$file_data = implode('', $file_data);
		}

		if ($fp = @fopen($file, 'wb'))
		{
			@flock($fp, LOCK_EX);
			$bytes = fwrite($fp, $file_data);
			@flock($fp, LOCK_UN);
			fclose($fp);
		}
		return $bytes;
	}
}

if (!function_exists('html_entity_decode'))
{
	//string html_entity_decode ( string string [, int quote_style [, string charset]] )
	function html_entity_decode($string, $quote_style = ENT_COMPAT, $charset = '')
	{
		return strtr($string, array_flip(get_html_translation_table(HTML_ENTITIES, $quote_style)));
	}
}


// Should 4.3 be the min, or 4.2 ?
// Move seperate file, if someone has a pre 4.3 they'll have to include that file
if (!function_exists('debug_backtrace'))
{
	function debug_backtrace()
	{
	}
}

// Should 4.3 be the min, or 4.2 ?
// Move seperate file, if someone has a pre 4.3 they'll have to include that file
if (!function_exists('var_export'))
{
	function var_export($variable, $return = false, $tab = false)
	{
		$tab = ($tab) ? $tab : chr(9);
		$lines = array();
		$new_line = chr(10);
		//"windows" = "chr(13).chr(10)"  "Mac" = "chr(13)"

		if (is_array($variable))
		{
			foreach ($variable as $key => $value)
			{
				$string = is_int($key) ? $key.' => ' : "'$key' => ";
	
				if (is_array($value))
				{
					$string .= $this->format_array($value, $tab.$tab);
				}
				elseif (is_int($value))
				{
					$string .= $value;
				}
				elseif (is_bool($value))
				{
					$string .= ($value) ? 'true' : 'false';
				}
				else
				{
					$string .= "'".str_replace("'", "\\'", str_replace('\\', '\\\\', $value)) . "'";
				}
	
				$lines[] = $string;
			}
			$formated = 'array('.$new_line. $tab . implode(','.$new_line. $tab, $lines) . ')';
		}
		elseif (is_int($variable))
		{
			$formated = (string) $array;
		}
		elseif (is_string($array))
		{
			$formated .= "'".str_replace("'", "\\'", str_replace('\\', '\\\\', $value)) . "'";
		}

		if ($return)
		{
			return $formated;
		}
		else
		{
			echo $formated;
		}
	}
}

?>