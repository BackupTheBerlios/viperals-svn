<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||

$Id$
*/

if (VIPERAL !== 'Admin') 
{
	die;
}

global $_CLASS, $_CORE_MODULE;

$_CLASS['core_user']->add_lang('admin/system.php');

$mode =	get_variable('mode', 'GET', false);

if (!$mode || !in_array($mode, array('Site', 'system')))
{
	$mode = 'site';
}

$_CLASS['core_template']->assign_array(array(
	'LINK_SITE'		=> generate_link('system&amp;mode=site', array('admin' => true)),
	'LINK_SYSTEM'	=> generate_link('system&amp;mode=system', array('admin' => true)),
	'SYSTEM_MODE'	=> $mode,
));

$save = isset($_POST['submit']);

switch ($mode)
{
	case 'site':
		if ($save)
		{
			$data = array(
				'global'	=> array(
					'default_theme'		=> get_variable('default_theme', 'POST', ''),
					'link_optimization' => get_variable('link_optimization', 'POST', 0, 'int'),
					'site_name'			=> get_variable('site_name', 'POST', ''),
					'site_url'			=> get_variable('site_url', 'POST', ''),
					'foot1'				=> get_variable('foot1', 'POST', ''),
					'foot2'				=> get_variable('foot2', 'POST', ''),
				),
				'email'	=> array(
					'email_enable'			=> get_variable('email_enable', 'POST') ? 1 : 0,
					'email_function_name'	=> get_variable('email_function_name', 'POST', ''),
					'smtp'					=> get_variable('smtp', 'POST', 0, 'int'),
					'smtp_host'				=> get_variable('smtp_host', 'POST', ''),
					'smtp_port'				=> get_variable('smtp_port', 'POST', '', 'int'),
					'smtp_username'			=> get_variable('smtp_username', 'POST', ''),
					'smtp_password'			=> get_variable('smtp_password', 'POST', ''),
					'site_email'			=> get_variable('site_email', 'POST', ''),
				)
			);
			
			setting_save($data);

			unset($data);
		}
	
		global $_CLASS, $_CORE_CONFIG;
	
		$_CLASS['core_template']->assign_array(array(
			'A_OPTION'		=> 'site',
			'ACTION'		=> generate_link('system', array('admin' => true)),
			
			'LINK_OPTIMIZATION' => $_CORE_CONFIG['global']['link_optimization'],
	
			'SELECT_THEME' 		=> select_theme($_CORE_CONFIG['global']['default_theme']),
			'SITE_NAME'			=> $_CORE_CONFIG['global']['site_name'],
	
			'EMAIL_ENABLE'		=> $_CORE_CONFIG['email']['email_enable'],
			'EMAIL_FUNCTION_NAME'=> $_CORE_CONFIG['email']['email_function_name'],
			'SMTP'				=> $_CORE_CONFIG['email']['smtp'],
			'SMTP_HOST'			=> $_CORE_CONFIG['email']['smtp_host'],
			'SMTP_PORT'			=> $_CORE_CONFIG['email']['smtp_port'],
			'SMTP_USERNAME'		=> $_CORE_CONFIG['email']['smtp_username'],
			'SMTP_PASSWORD'		=> $_CORE_CONFIG['email']['smtp_password'],
			'SITE_EMAIL'		=> $_CORE_CONFIG['email']['site_email'],
	
			'FOOTER_FIRST' 		=> $_CORE_CONFIG['global']['foot1'],
			'FOOTER_SECOND' 	=> $_CORE_CONFIG['global']['foot2'],
		));
	
		$_CLASS['core_template']->display('admin/system/index.html');
	break;

	case 'system':
		if ($save)
		{
			if (!empty($_POST['maintenance_start']))
			{
				$expires = strtotime($_POST['maintenance_start']);
				$_POST['maintenance_start'] = (!$expires || $expires === -1) ? 0 : $expires;
			}
	
			$data = array(
				'maintenance' => array(
						'active' 	=> get_variable('maintenance', 'POST') ? 1 : 0,
						'text'		=> get_variable('maintenance_text', 'POST', ''),
						'start' 	=> get_variable('maintenance_start', 'POST', 0, 'int'),
				),
				'server' => array(
					'cookie_domain' => get_variable('cookie_domain', 'POST', ''),
					'cookie_name' 	=> get_variable('cookie_name', 'POST', ''),
					'cookie_path' 	=> get_variable('cookie_path', 'POST', ''),
					'error_options'	=> get_variable('error_options', 'POST', 0, 'int'),
					'site_domain'	=> get_variable('site_domain', 'POST', ''),
					'site_port' 	=> get_variable('site_port', 'POST', ''),
					'site_path' 	=> get_variable('site_path', 'POST', ''),
					'site_secure'	=> get_variable('site_secure', 'POST', 0, 'int'),
					'ip_check' 		=> get_variable('ip_check', 'POST', 0, 'int'),
					'limit_load' 	=> get_variable('limit_load', 'POST', 0, 'int'),
					'limit_sessions'	=> get_variable('limit_sessions', 'POST', 0, 'int'),
					'session_length'	=> get_variable('session_length', 'POST', 600, 'int'),
				)
			);
	
			setting_save($data);
	
			unset($data);
		}
		
		global $_CLASS, $_CORE_CONFIG;
	
		$path = str_replace('\\','/', dirname(getenv('SCRIPT_NAME')));
		
		if (substr($path, -1) !== '/')
		{
			$path .= '/';
		}
	
		$domain = empty($_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	
		$_CLASS['core_template']->assign_array(array(
			'A_OPTION' 			=> 'system',
			'ACTION'			=> generate_link('system&amp;mode=system', array('admin' => true)),
			
			'COOKIE_DOMAIN' => $_CORE_CONFIG['server']['cookie_domain'],
			'COOKIE_NAME' 	=> $_CORE_CONFIG['server']['cookie_name'],
			'COOKIE_PATH' 	=> $_CORE_CONFIG['server']['cookie_path'],
			'ERROR'			=> $_CORE_CONFIG['server']['error_options'],
			'MAINTENANCE' 		=> $_CORE_CONFIG['maintenance']['active'],
			'MAINTENANCE_MSG' 	=> $_CORE_CONFIG['maintenance']['text'],
			'MAINTENANCE_START' => is_numeric($_CORE_CONFIG['maintenance']['start']) ? $_CLASS['core_user']->format_date($_CORE_CONFIG['maintenance']['start'], 'M d, Y h:i a') : '',
			'IP_CHECK'			=> $_CORE_CONFIG['server']['ip_check'],
			'SITE_DOMAIN'		=> $_CORE_CONFIG['server']['site_domain'],
			'SITE_PATH'			=> $_CORE_CONFIG['server']['site_path'],
			'SITE_PORT'			=> $_CORE_CONFIG['server']['site_port'],
			'SITE_SECURE'		=> $_CORE_CONFIG['server']['site_secure'],
			'LIMIT_LOAD'		=> $_CORE_CONFIG['server']['limit_load'],
			'LIMIT_SESSIONS'	=> $_CORE_CONFIG['server']['limit_sessions'],
			'SESSION_LENGTH'	=> $_CORE_CONFIG['server']['session_length'],
			
		));
	
		$_CLASS['core_template']->display('admin/system/index.html');
	break;
}

function setting_save($data)
{
	global $_CLASS, $_CORE_CONFIG;
	
	foreach ($data AS $section => $option)
    {
		foreach ($option AS $name => $value)
		{
			if (isset($_CORE_CONFIG[$section][$name]) && $value != $_CORE_CONFIG[$section][$name])
			{
				set_core_config($section, $name, $value, false);
			}
		}
    }

	$_CLASS['core_cache']->destroy('core_config');
}

?>