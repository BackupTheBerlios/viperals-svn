<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

function is_admin()
{
    global $_CLASS;
    return ($_CLASS['core_user']->data['session_admin']) ? true : false;
}

function is_user()
{
    global $_CLASS;
    return ($_CLASS['core_user']->data['user_id'] != ANONYMOUS) ? true : false;
}

function unique_id()
{
	list($sec, $usec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	return uniqid(mt_rand(), true);
}

function encode_password($pure, $encoding = 'md5')
{
	return md5($pure);
}

// Generate login box or verify password
function login_box($login_options = false)
{
	global $SID, $_CLASS, $_CORE_CONFIG;
	
	$err = '';
	$login_array = array(
		'redirect' 		=> false,
		'explain' 	 	=> '',
		'success'  		=> '',
		'admin_login'	=> false,
		'full_login'	=> true,
	 );
	
	if (is_array($login_options))
	{
		$login_array = array_merge($login_array, $login_options);
	} 	
	
	if (isset($_POST['login']))
	{
		$data = array(
			'user_name'			=> get_variable('username', 'POST'),
			'user_password'		=> get_variable('password', 'POST'),
			'admin_login'		=> $login_array['admin_login'],
			'auto_log'			=> (!empty($_POST['autologin'])) ? true : false,
			'show_online'		=> (!empty($_POST['viewonline'])) ? 0 : 1,
			'auth_error_return'	=> true,
		 );
		
		$result = false;
		
		if ($data['user_name'] && $data['user_password'] && ($result = $_CLASS['core_user']->create($data)) === true)
		{
			if ($login_array['admin_login'])
			{
				//add_log('admin', 'LOG_ADMIN_AUTH_SUCCESS');
			}
			
			$login_array['redirect'] = get_variable('redirect', 'POST', false);
			
			if ($login_array['redirect'])
			{
				$login_array['redirect'] = (strpos($login_array['redirect'], '?') !== false) ? $login_array['redirect'].'&amp;' : $login_array['redirect'].'?';
				$login_array['redirect'] .= 'sid='.$_CLASS['core_user']->data['session_id'];
			} else {
				$login_array['redirect'] = generate_link();
			}

			//$_CLASS['core_display']->meta_refresh(3, $login_array['redirect']);

			$message = (($login_array['success']) ? $login_array['success'] : $_CLASS['core_user']->lang['LOGIN_REDIRECT']) . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $login_array['redirect'] . '">', '</a> ');
			trigger_error($message);
		}

		if ($login_array['admin_login'])
		{
			//add_log('admin', 'LOG_ADMIN_AUTH_FAIL');
		}
		
		if (is_string($result))
		{
			trigger_error($result, E_USER_ERROR);
		}

		// If we get an integer zero then we are inactive, else the username/password is wrong
		$err = ($result === 0) ? $_CLASS['core_user']->lang['ACTIVE_ERROR'] :  $_CLASS['core_user']->lang['LOGIN_ERROR'];
	}

	if (!$login_array['redirect'])
	{
		$login_array['redirect'] = htmlspecialchars($_CLASS['core_user']->url);
	}
	
	$s_hidden_fields = '<input type="hidden" name="redirect" value="' . $login_array['redirect'] . '" />';
	$s_hidden_fields .= '<input type="hidden" name="sid" value="' . $_CLASS['core_user']->data['session_id'] . '" />';

	$_CLASS['core_template']->assign(array(
		'LOGIN_ERROR'			=> $err, 
		'LOGIN_EXPLAIN'			=> $login_array['explain'], 
		'U_SEND_PASSWORD'	 	=> ($_CORE_CONFIG['email']['email_enable']) ? generate_link('Control_Panel&amp;mode=sendpassword') : '',
		'U_RESEND_ACTIVATION'   => ($_CORE_CONFIG['user']['require_activation'] != USER_ACTIVATION_NONE && $_CORE_CONFIG['email']['email_enable']) ? generate_link('Control_Panel&amp;mode=resend_act') : '',
		'U_TERMS_USE'			=> generate_link('Control_Panel&amp;mode=terms'), 
		'U_PRIVACY'				=> generate_link('Control_Panel&amp;mode=privacy'),
		'U_REGISTER'			=> generate_link('Control_Panel&amp;mode=register'),
		'USERNAME'				=> '',
		'S_DISPLAY_FULL_LOGIN'  => ($login_array['full_login']) ? true : false,
		'S_LOGIN_ACTION'		=> (!$login_array['admin_login']) ? generate_link('Control_Panel&amp;mode=login') : generate_link(false, array('admin' => true)),
		'S_HIDDEN_FIELDS' 		=> $s_hidden_fields,
		'L_LOGIN'				=> $_CLASS['core_user']->lang['LOGIN'],
		'L_LOGIN_INFO'			=> $_CLASS['core_user']->lang['LOGIN_INFO'], 
		'L_TERMS_USE'			=> $_CLASS['core_user']->lang['TERMS_USE'],
		'L_USERNAME'			=> $_CLASS['core_user']->lang['USERNAME'],
		'L_PASSWORD' 			=> $_CLASS['core_user']->lang['PASSWORD'],
		'L_REGISTER'			=> $_CLASS['core_user']->lang['REGISTER'],
		'L_RESEND_ACTIVATION'	=> $_CLASS['core_user']->lang['RESEND_ACTIVATION'],
		'L_FORGOT_PASS'			=> $_CLASS['core_user']->lang['FORGOT_PASS'],
		'L_HIDE_ME'				=> $_CLASS['core_user']->lang['HIDE_ME'],
		'L_LOG_ME_IN'			=> $_CLASS['core_user']->lang['LOG_ME_IN'],
		'L_PRIVACY'				=> $_CLASS['core_user']->lang['PRIVACY']
		)
	);
	
	$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['LOGIN']);
	
	$_CLASS['core_template']->display('modules/Forums/login_body.html');
	
	$_CLASS['core_display']->display_footer();
}

function set_config($config_name, $config_value, $is_dynamic = false)
{
	global $_CLASS, $config;

	$sql = 'UPDATE ' . CONFIG_TABLE . "
		SET config_value = '" . $_CLASS['core_db']->sql_escape($config_value) . "'
		WHERE config_name = '" . $_CLASS['core_db']->sql_escape($config_name) . "'";
	$_CLASS['core_db']->sql_query($sql);

	if (!$_CLASS['core_db']->sql_affectedrows() && !isset($config[$config_name]))
	{
		$sql = 'INSERT INTO ' . CONFIG_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'config_name'	=> $config_name,
			'config_value'	=> $config_value,
			'is_dynamic'	=> ($is_dynamic) ? 1 : 0));
		$_CLASS['core_db']->sql_query($sql);
	}

	$config[$config_name] = $config_value;

	if (!$is_dynamic)
	{
		$_CLASS['core_cache']->destroy('config');
	}
}

function maintance_status($return = false)
{
	global $_CORE_CONFIG, $_CLASS;
// Add maintance start time, so we can notify users when maintance is starting with popup maybe !
	
	if ($_CORE_CONFIG['global']['maintenance'] && VIPERAL != 'Admin')
	{
		if (isset($_CLASS['core_user']) && $_CLASS['core_user']->is_admin)
		{
			return false;
		}
		
		if ($return)
		{
			return true;
		}

		trigger_error($_CORE_CONFIG['global']['maintenance_text'], E_USER_ERROR);
	}
	
	return false;
}

function load_status($return = false)
{
	global $_CORE_CONFIG, $_CLASS;
	
	$load = false;
	
	if (@file_exists('/proc/loadavg'))
	{
		if ($load_tmp = @file('/proc/loadavg'))
		{
			list($load) = explode(' ', $load_tmp[0]);

			if ($_CORE_CONFIG['server']['limit_load'] && $load > doubleval($_CORE_CONFIG['server']['limit_load']) && VIPERAL != 'Admin')
			{
				if (isset($_CLASS['core_user']) && $_CLASS['core_user']->is_admin)
				{
					return false;
				}
				
				if ($return)
				{
					return true;
				}
				trigger_error('BOARD_UNAVAILABLE');
			}
		}
	}

	return $load;
}
function script_close($save = true)
{
	global $_CORE_CONFIG, $site_file_root, $_CLASS;

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
			if ($_CORE_CONFIG['global']['error'])
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
		$_CLASS['core_error_handler']->stop();  //stopped to halt the db sql_close error, look into a fix.
	}
	
	if (!empty($_CLASS['core_db']))
	{
		$_CLASS['core_db']->sql_close();
	}
	
	/*
	// Call cron-type script
	if (!defined('IN_CRON'))
	{
		$cron_type = '';

		if (time() - $config['queue_interval'] > $config['last_queue_run'] && !defined('IN_ADMIN') && file_exists($phpbb_root_path . 'cache/queue.' . $phpEx))
		{
			// Process email queue
			$cron_type = 'queue';
		}
		else if (method_exists($cache, 'tidy') && time() - $config['cache_gc'] > $config['cache_last_gc'])
		{
			// Tidy the cache
			$cron_type = 'tidy_cache';
		}
		else if (time() - (7 * 24 * 3600) > $config['database_last_gc'])
		{
			// Tidy some table rows every week
			$cron_type = 'tidy_database';
		}

		if ($cron_type)
		{
			$template->assign_var('RUN_CRON_TASK', '<img src="' . $phpbb_root_path . 'cron.' . $phpEx . '?cron_type=' . $cron_type . '" width="1" height="1" />');
		}
	} */
	
	/*
	if (!class_exists('tidy'))
	{
		$html = ob_get_clean();
		
		// Specify configuration
		$config = array(
				   'indent'        => true,
				   'output-xhtml'  => true,
				   'wrap'          => 200);
		
		// Tidy
		$tidy = new tidy;
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();
		
		// Output
		echo $tidy;
	}*/
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

function loadclass($file, $name, $class = false)
{
	global $_CLASS;
	
	$class = ($class) ? $class : $name;
	
	if (empty($_CLASS[$name]) || !is_object($_CLASS[$name]))
	{
		if ($file)
		{
			require_once($file);
		}
		$_CLASS[$name] =& new $class;
	}
}


/// This does the optimizatiopn 2x
/// Need to resave the config table after it is distroyed
function optimize_table($table = false)
{
	global $_CLASS, $_CORE_CONFIG, $prefix;
	// this needs alot of testing lol. works for me for now.
	if ($table)
	{
		$_CLASS['core_db']->sql_query('OPTIMIZE TABLE '. $_CLASS['core_db']->sql_escape($table));
		return;
	}

	$result = $_CLASS['core_db']->sql_query('SHOW TABLES');
	
	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
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
		$_CLASS['core_db']->sql_query('OPTIMIZE TABLE '. $_CLASS['core_db']->sql_escape($table));
		$time = time() + $_CORE_CONFIG['server']['optimize_rate'];
	}

	$_CLASS['core_db']->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value='.$time." WHERE cfg_field='optimize_last' AND cfg_name='server'");
	$_CLASS['core_cache']->destroy('main_cfg');
	
}


function get_variable($var_name, $type, $default = '', $vartype = 'string')
{
	/*$type = "_$type";

	global $$type;
	
	// not sure how good this is, check mem. usage
	// If linking works the way I think  it should be ok, else it could slow things down
	$type =& $$type;
	
	if  (isset($type[$var_name]) && !is_array($type[$var_name]))
	{
		return check_variable($type[$var_name], $default, $vartype);
	} else {
		return $default;
	}*/
	
					
	switch ($type)
	{
		Case 'GET':
			if  (isset($_GET[$var_name]) && !is_array($_GET[$var_name]))
			{
				return check_variable($_GET[$var_name], $default, $vartype);
			} else {
				return $default;
			}
			
			break;
			
		Case 'POST':
			if (isset($_POST[$var_name]) && !is_array($_POST[$var_name]))
			{
				return check_variable($_POST[$var_name], $default, $vartype);
			} else {
				return $default;
			}
			
			break;
		
		Case 'REQUEST':
			if (isset($_REQUEST[$var_name]) && !is_array($_REQUEST[$var_name]))
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
			$variable = (is_numeric($variable)) ? (int) $variable : $default;
		break;
		
		default:
			$variable = trim(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $variable));
			$variable = strip_slashes($variable);
		break;
	}
	
	return $variable;
}

function strip_slashes($str)
{
	return (STRIP) ? stripslashes($str) : $str ;
}

function get_bots()
{
	global $_CLASS;
	
	if (($bots = $_CLASS['core_cache']->get('bots')) === false)
	{
		$bots = array();
		
		$sql = 'SELECT user_id, bot_agent, bot_ip
			FROM ' . BOTS_TABLE . '
			WHERE bot_active = 1';
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

// fix me, add preg replace,
function trim_text($text, $replacement = ' ')
{
	$text = str_replace("\r\n", $replacement, $text);
	$text = str_replace("\n", $replacement, $text);
	return trim($text);
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
		if (!strpos('.',$file))
		{
			if (file_exists($site_file_root."themes/$file/index.php"))
			{
				$themetmp[] = array('file' => $file, 'template'=> true);
			} elseif (file_exists($site_file_root."themes/$file/theme.php")) {
				$themetmp[] = array('file' => $file, 'template'=> false);
			} 
		} 
	}
	
	closedir($handle);
	
	$count = count($themetmp);
	
	for ($i=0; $i < $count; $i++)
	{
		$themetmp[$i]['name'] = ($themetmp[$i]['template']) ? $themetmp[$i]['file'].' *' : $themetmp[$i]['file'];
		if ($themetmp[$i]['file'] == $default)
		{
			$theme .= '<option value="'.$themetmp[$i]['file'].'" selected="selected">'.$themetmp[$i]['name'].'</option>';
		} else {
			$theme .= '<option value="'.$themetmp[$i]['file'].'">'.$themetmp[$i]['name'].'</option>';
		}
	}
	
	return $theme;
}

function generate_link($link = false, $link_options = false)
{
	global $_CLASS, $_CORE_MODULE, $mainindex, $adminindex;
	
	$options = array(
		'admin' => false,
		'full' => false,
		'sid' => true,
//'force_sid' => false  {maybe add}
	);

	if (is_array($link_options))
	{
		$options = array_merge($options, $link_options);
	} 	
	
	$file = ($options['admin']) ? $adminindex : $mainindex;
	
	if (!$link)
	{
		$link = $file;
		
		if ($_CLASS['core_user']->need_url_id && $options['sid'])
		{
			$link .= '?sid='.$_CLASS['core_user']->data['session_id'];
		}
	
	} else {
	
		if ($link{0} == '&')
		{
			$link = $_CORE_MODULE['title'].$link;
		}
		
		$link = $file.'?mod='.$link;
		
		// somtimes it ok to repeat strpos($link, '?') !== false is to much :-)
		if ($_CLASS['core_user']->need_url_id && $options['sid'])
		{
			$link .= '&amp;sid='.$_CLASS['core_user']->data['session_id'];
		}
    }
    
    $_CLASS['core_user']->need_url_id = true;

	
    if ($options['full'])
    {
		return generate_base_url().$link;
    }
    
    return $link;
}

function generate_base_url()
{
	static $base = false;
	
	if (!$base)
	{
		global $_CORE_CONFIG;
		
		$base = ($_CORE_CONFIG['server']['cookie_secure']) ? 'https://' : 'http://' ;
		$base .= trim((isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_CORE_CONFIG['server']['site_domain']));
		$base .= (($_CORE_CONFIG['server']['site_port'] <> 80) ? ':' . trim($_CORE_CONFIG['server']['site_port']) : '') . $_CORE_CONFIG['server']['site_path'];
	}
	
	return $base;
}

function getlink($str = false, $UseLEO = true, $full = false, $showSID = true)
 {
    global $_CORE_MODULE, $mainindex, $_CORE_CONFIG, $_CLASS, $SID;
    
    if (!$str)
    {
		$str = $_CORE_MODULE['title'];
    }
    
    return generate_link($str);
}

function adminlink($link = false)
{
    return generate_link($link, array('admin' => true));
}

function url_redirect($url = false)
{
    global $db, $cache, $mainindex;

	script_close();
	
	$url = ($url) ? $url : 'http://'.getenv('HTTP_HOST').'/'.$_CORE_CONFIG['server']['path'].$mainindex;
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

function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = false, $tpl_prefix = '')
{
	//Code Copyright 2004 phpBB Group - http://www.phpbb.com/
	global $_CLASS;

	//$seperator = $_CLASS['core_user']->img['pagination_sep'];
	$seperator = ' | ';

	$admin_link = (VIPERAL == 'Admin') ? true : false;
	
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
		'L_GOTO_PAGE'	=>	$_CLASS['core_user']->lang['GOTO_PAGE'],
		'L_PREVIOUS'	=>	$_CLASS['core_user']->lang['PREVIOUS'],
		'L_NEXT'		=>	$_CLASS['core_user']->lang['NEXT'],
		'L_PREVIOUS'	=>	$_CLASS['core_user']->lang['PREVIOUS'],
		$tpl_prefix . 'BASE_URL'	=> generate_link($base_url),
		$tpl_prefix . 'PER_PAGE'	=> $per_page,
		
		$tpl_prefix . 'PREVIOUS_PAGE'	=> ($on_page == 1) ? '' : generate_link($base_url . '&amp;start=' . (($on_page - 2) * $per_page), $admin_link),
		$tpl_prefix . 'NEXT_PAGE'	=> ($on_page == $total_pages) ? '' : generate_link($base_url . '&amp;start=' . ($on_page * $per_page), $admin_link))
	);
	return $page_string;
}

function on_page($num_items, $per_page, $start)
{
	global $_CLASS;

	$on_page = floor($start / $per_page) + 1;

	$_CLASS['core_template']->assign('ON_PAGE', $on_page);

	return sprintf($_CLASS['core_user']->lang['PAGE_OF'], $on_page, max(ceil($num_items / $per_page), 1));
}

function check_email($email)
{
	return preg_match('#^[a-z0-9\.\-_\+]+?@(.*?\.)*?[a-z0-9\-_]+?\.[a-z]{2,4}$#i', $email);
}
?>