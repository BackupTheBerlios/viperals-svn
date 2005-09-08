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


// Need to redo this, do all check before the saving

if (VIPERAL !== 'Admin') 
{
	die;
}

global $_CLASS;

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

$save = (isset($_POST['submit'])) ? true : false;

switch ($mode)
{
	case 'site':
		admin_site($save);
	break;

	case 'users':
		//admin_users($save);
	break;

	case 'system':
		admin_system($save);
	break;
}

function admin_save($data)
{
	global $_CLASS, $_CORE_CONFIG;
	
	foreach ($data AS $section => $option)
    {
		foreach ($option AS $db_name => $data_op)
		{
			$value = get_variable($data_op['post_name'], 'POST', false);

			if ($value != $_CORE_CONFIG[$section][$db_name])
			{
				set_core_config($section, $db_name, $value, false);
			}
		}
    }

	$_CLASS['core_cache']->destroy('core_config');
}

function admin_site($save)
{
	if ($save)
	{
		$data = array('global'	=> array(
			'default_theme'		=> array('post_name' => 'default_theme'),
			'link_optimization' => array('post_name' => 'link_optimization'),
			'site_name'			=> array('post_name' => 'site_name'),
			'site_url'			=> array('post_name' => 'site_url'),
			'start_date'		=> array('post_name' => 'start_date'),
			'foot1'				=> array('post_name' => 'foot1'),
			'foot2'				=> array('post_name' => 'foot2'),
		));
		admin_save($data);
	}

	global $_CLASS, $_CORE_CONFIG;

	$_CLASS['core_template']->assign_array(array(
		'A_OPTION'		=> 'site',
		'ACTION'		=> generate_link('system', array('admin' => true)),
		
		'LINK_OPTIMIZATION' => $_CORE_CONFIG['global']['link_optimization'],

		'SELECT_THEME' 		=> select_theme($_CORE_CONFIG['global']['default_theme']),
		'SITE_NAME'			=> $_CORE_CONFIG['global']['site_name'],
		'SITE_URL'			=> $_CORE_CONFIG['global']['site_url'],
		'START_DATE'		=> $_CORE_CONFIG['global']['start_date'],

		'FOOTER_FIRST' 		=> $_CORE_CONFIG['global']['foot1'],
		'FOOTER_SECOND' 	=> $_CORE_CONFIG['global']['foot2'],
		
	));

	$_CLASS['core_template']->display('admin/system/index.html');
}

function admin_system($save)
{
	if ($save)
	{
		if (!empty($_POST['maintenance_start']))
		{
			$expires = strtotime($_POST['maintenance_start']);
			$_POST['maintenance_start'] = (!$expires || $expires == -1) ? '' : $expires;
		}

		$data = array(
			'maintenance' => array(
					'active' => array('post_name' => 'maintenance'),
					'text' => array('post_name' => 'maintenance_text'),
					'start' => array('post_name' => 'maintenance_start'),
			),
			'server' => array(
				'cookie_domain' => array('post_name' => 'cookie_domain'),
				'cookie_name' 	=> array('post_name' => 'cookie_name'),
				'cookie_path' 	=> array('post_name' => 'cookie_path'),
				'error_options'	=> array('post_name' => 'error_options'),
				'site_domain'	=> array('post_name' => 'site_domain'),
				'site_port' 	=> array('post_name' => 'site_port'),
				'site_path' 	=> array('post_name' => 'site_path'),
				'site_secure'	=> array('post_name' => 'site_secure'),
				'ip_check' 		=> array('post_name' => 'ip_check'),
				'limit_load' 	=> array('post_name' => 'limit_load'),
				'limit_sessions'	=> array('post_name' => 'limit_sessions'),
				'session_length'	=> array('post_name' => 'session_length'),
			)
		);
		admin_save($data);
	}
	
	global $_CLASS, $_CORE_CONFIG;
   
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
}

?>