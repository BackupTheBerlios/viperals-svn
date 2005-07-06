<?php

if (VIPERAL != 'Admin') 
{
	die;
}

global $_CLASS;

$_CLASS['core_user']->add_lang('admin/system.php');

$_CLASS['core_template']->assign(array(
	'A_L_SITE'		=> generate_link('system&amp;mode=site', array('admin' => true)),
	'A_L_WYSIWYG'	=> generate_link('system&amp;mode=wysiwyg', array('admin' => true)),
	'A_L_SYSTEM'	=> generate_link('system&amp;mode=system', array('admin' => true)),
	'A_L_USERS'		=> generate_link('system&amp;mode=users', array('admin' => true)),
));

$option = array(
	'Site' => array(
			'lang' => $_CLASS['core_user']->get_lang('SITE')),
	'system' => array(
			'lang' => $_CLASS['core_user']->get_lang('SYSTEM')),
	'users' => array(
			'lang' => $_CLASS['core_user']->get_lang('USERS_OPTIONS')),
);

$mode =	get_variable('mode', 'GET', false);

if (!$mode || !in_array($mode, array_keys($option)))
{
	$mode = 'Site';
}

foreach ($option as $option_mode => $settings)
{
	$_CLASS['core_template']->assign_vars_array('a_options', array(
		'ACTIVE'	=> ($option_mode == $mode) ? true : false,
		'LANG'		=> $settings['lang'],
		'LINK'		=> generate_link('system&amp;mode='.$option_mode, array('admin' => true)),
	));
}

$save = (isset($_POST['submit'])) ? true : false;

switch ($mode)
{
	case 'Site':
		admin_site($save);
		break;

	case 'wysiwyg':
		admin_wysiwyg($save);
		break;

	case 'users':
		admin_users($save);
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
				//echo $data_op['post_name'].' : '. $db_name.' : '.$value.'<br/>';
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
			'site_logo'			=> array('post_name' => 'site_logo'),
			'site_name'			=> array('post_name' => 'site_name'),
			'site_url'			=> array('post_name' => 'site_url'),
			'slogan'			=> array('post_name' => 'slogan'),
			'startdate'			=> array('post_name' => 'startdate')
			)
		);
		admin_save($data);
	}

	global $_CLASS, $_CORE_CONFIG;

	$_CLASS['core_template']->assign(array(
		'A_OPTION'		=> 'site',
		'ACTION'		=> generate_link('system', array('admin' => true)),
		
		'DEFAULT_THEME' 	=> $_CORE_CONFIG['global']['default_theme'],
		'LINK_OPTIMIZATION' => $_CORE_CONFIG['global']['link_optimization'],
		'SITE_LOGO'			=> $_CORE_CONFIG['global']['site_logo'],
		'SITE_NAME'			=> $_CORE_CONFIG['global']['site_name'],
		'SITE_URL'			=> $_CORE_CONFIG['global']['site_url'],
		'SLOGAN'			=> $_CORE_CONFIG['global']['slogan'],
		'START_DATE'		=> $_CORE_CONFIG['global']['startdate'],

		'FOOTER_FIRST' 		=> $_CORE_CONFIG['global']['foot1'],
		'FOOTER_SECOND' 	=> $_CORE_CONFIG['global']['foot2'],
		
		));
	
	$handle = opendir('themes');
	
	while ($file = readdir($handle))
	{
		if ($file{0} !== '.')
		{
			if (file_exists("themes/$file/index.php"))
			{
				$_CLASS['core_template']->assign_vars_array('site_theme', array(
					'FILE'	=> $file,
					'NAME'  => $file,
				));
			}
		} 
	}
	
	closedir($handle);

	$_CLASS['core_display']->display_head();
	$_CLASS['core_template']->display('admin/system/index.html');
	$_CLASS['core_display']->display_footer();
}

function admin_system($save)
{
	if ($save)
	{
		$_POST['maintenance_start'] = (($expires = strtotime($_POST['maintenance_start'])) === -1) ? '' : $expires;

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
				'cookie_secure'	=> array('post_name' => 'cookie_secure'),
				'error_options'	=> array('post_name' => 'error_options'),
				'site_domain'	=> array('post_name' => 'site_domain'),
				'site_port' 	=> array('post_name' => 'site_port'),
				'site_path' 	=> array('post_name' => 'site_path'),
				'ip_check' 		=> array('post_name' => 'ip_check'),
				'browser_check' => array('post_name' => 'browser_check'),
				'limit_load' 	=> array('post_name' => 'limit_load'),
				'limit_sessions'	=> array('post_name' => 'limit_sessions'),
				'session_length'	=> array('post_name' => 'session_length'),
			)
		);
		admin_save($data);
	}
	
	global $_CLASS, $_CORE_CONFIG;
   
    $_CLASS['core_template']->assign(array(
		'A_OPTION' 			=> 'system',
		'ACTION'			=> generate_link('system&amp;mode=system', array('admin' => true)),
		
		'COOKIE_DOMAIN' => $_CORE_CONFIG['server']['cookie_domain'],
		'COOKIE_NAME' 	=> $_CORE_CONFIG['server']['cookie_name'],
		'COOKIE_PATH' 	=> $_CORE_CONFIG['server']['cookie_path'],
		'COOKIE_SECURE' => $_CORE_CONFIG['server']['cookie_secure'],
		'ERROR'			=> $_CORE_CONFIG['server']['error_options'],
		'MAINTENANCE' 		=> $_CORE_CONFIG['maintenance']['active'],
		'MAINTENANCE_MSG' 	=> $_CORE_CONFIG['maintenance']['text'],
		'MAINTENANCE_START' => is_numeric($_CORE_CONFIG['maintenance']['start']) ? $_CLASS['core_user']->format_date($_CORE_CONFIG['maintenance']['start'], 'M d, Y h:i a') : '',
		'BROWSER_CHECK'		=> $_CORE_CONFIG['server']['browser_check'],
		'IP_CHECK'			=> $_CORE_CONFIG['server']['ip_check'],
		'SITE_DOMAIN'		=> $_CORE_CONFIG['server']['site_domain'],
		'SITE_PATH'			=> $_CORE_CONFIG['server']['site_path'],
		'SITE_PORT'			=> $_CORE_CONFIG['server']['site_port'],
		'LIMIT_LOAD'		=> $_CORE_CONFIG['server']['limit_load'],
		'LIMIT_SESSIONS'	=> $_CORE_CONFIG['server']['limit_sessions'],
		'SESSION_LENGTH'	=> $_CORE_CONFIG['server']['session_length'],
		
		));

	$_CLASS['core_display']->display_head();
	$_CLASS['core_template']->display('admin/system/index.html');
	$_CLASS['core_display']->display_footer();
}

function admin_users($save)
{
	if ($save)
	{
		$data = array('user' => array(
			'require_activation'=> array('post_name' => 'require_activation'),
			'coppa_enable'		=> array('post_name' => 'coppa_enable'),
			'coppa_fax'			=> array('post_name' => 'coppa_fax'),
			'coppa_mail'		=> array('post_name' => 'coppa_mail'),
			'enable_confirm'	=> array('post_name' => 'enable_confirm'),
			'max_reg_attempts'	=> array('post_name' => 'max_reg_attempts'),
			'min_name_chars'	=> array('post_name' => 'min_name_chars'),
			'max_name_chars'	=> array('post_name' => 'max_name_chars'),
			'min_pass_chars'	=> array('post_name' => 'min_pass_chars'),
			'max_pass_chars'	=> array('post_name' => 'max_pass_chars'),
			'chg_passforce'		=> array('post_name' => 'chg_passforce'),
			'allow_namechange'	=> array('post_name' => 'allow_namechange'),
			'max_reg_attempts'	=> array('post_name' => 'max_reg_attempts'),
			'allow_name_chars'	=> array('post_name' => 'allow_name_chars'),
			'allow_emailreuse'	=> array('post_name' => 'allow_emailreuse'),
			)
		);
		admin_save($data);
	}

	global $_CLASS, $_CORE_CONFIG;

    $_CLASS['core_template']->assign(array(
		'A_OPTION' => 'users',
		'ACTION' => generate_link('system&amp;mode=users', array('admin' => true)),
		'L_EDITOR_OPTION' => $_CLASS['core_user']->lang['EDITOR_OPTION'],
		
		'ACTIVATION_OPTION' => $_CORE_CONFIG['user']['require_activation'],
		'COPPA_ENABLE'		=> $_CORE_CONFIG['user']['coppa_enable'],
		'COPPA_FAX'			=> $_CORE_CONFIG['user']['coppa_fax'],
		'COPPA_MAIL'		=> $_CORE_CONFIG['user']['coppa_mail'],
		'CONFIRM_ENABLE'	=> $_CORE_CONFIG['user']['enable_confirm'],
		'MAX_REG_ATTEMPTS'	=> $_CORE_CONFIG['user']['max_reg_attempts'],
		'MIN_NAME_CHARS'	=> $_CORE_CONFIG['user']['min_name_chars'],
		'MAX_NAME_CHARS'	=> $_CORE_CONFIG['user']['max_name_chars'],
		'MIN_PASS_CHARS'	=> $_CORE_CONFIG['user']['min_pass_chars'],
		'MAX_PASS_CHARS'	=> $_CORE_CONFIG['user']['max_pass_chars'],
		'CHG_PASSFORCE'		=> $_CORE_CONFIG['user']['chg_passforce'],
		'ALLOW_NAMECHANGE'	=> $_CORE_CONFIG['user']['allow_namechange'],
		'ALLOW_NAME_CHARS'	=> $_CORE_CONFIG['user']['allow_name_chars'],
		//'PASS_COMPLEX'  => $_CORE_CONFIG['user']['pass_complex'],
		//'ALLOW_NAMECHANGE'  => $_CORE_CONFIG['user']['pass_complex'],
		'ALLOW_EMAILREUSE'	=> $_CORE_CONFIG['user']['allow_emailreuse'],

		'A_ACC_OPTION'	=> array(
			array(
				'LANG' => $_CLASS['core_user']->lang['ACC_NONE'],
				'OPTION' => USER_ACTIVATION_NONE,
			),
			array(
				'LANG' => $_CLASS['core_user']->lang['ACC_USER'],
				'OPTION' => USER_ACTIVATION_SELF,
			),
			array(
				'LANG' => $_CLASS['core_user']->lang['ACC_ADMIN'],
				'OPTION' => USER_ACTIVATION_ADMIN,
			),
			array(
				'LANG' => $_CLASS['core_user']->lang['ACC_DISABLE'],
				'OPTION' => USER_ACTIVATION_DISABLE,
			))
		));

		$_CLASS['core_display']->display_head();
		$_CLASS['core_template']->display('admin/system/index.html');
		$_CLASS['core_display']->display_footer();
}

?>