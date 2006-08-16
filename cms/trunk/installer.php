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
define('VIPERAL', 'INSTALLER');

error_reporting(E_ALL);

define('SITE_FILE_ROOT', str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/');

if (!extension_loaded('mbstring'))
{
	require_once SITE_FILE_ROOT.'includes/compatiblity/mbstring.php';
}

set_magic_quotes_runtime(0);
mb_internal_encoding('UTF-8');
set_time_limit(0);

define('STRIP_SLASHES', get_magic_quotes_gpc());

// Remove registered globals
if ((bool) ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $value)
	{
		unset($$var_name);
	}
}

require_once SITE_FILE_ROOT.'includes/functions.php';
require_once SITE_FILE_ROOT.'includes/display/template.php';

load_class(false, 'core_template');

$holding_array = array(
	'mysql'		=> array(
		'lang'			=> 'MySQL',
		'extension'		=> 'mysql', 
	),
	'mysql3'			=> array(
		'lang'			=> 'MySQL3',
		'extension'		=> 'mysql',
	),
	'mysqli'	=> array(
		'lang'			=> 'MySQL 4.1.x',
		'extension'		=> 'mysqli', 
	),
	'mssql'		=> array(
		'lang'			=> 'MS SQL Server 7/2000',
		'extension'		=> 'mssql', 
	),
	'postgres' => array(
		'lang'			=> 'PostgreSQL',
		'extension'		=> 'pgsql', 
	),
	'sqlite'		=> array(
		'lang'			=> 'SQLite',
		'extension'		=> 'sqlite', 
	),
	'sqlite_pdo'		=> array(
		'lang'			=> 'SQLite 3 (PDO)',
		'extension'		=> 'pdo_sqlite', 
	)
);

$database_array = array();
$database_options = '';
$db_layer = isset($_POST['layer']) ? $_POST['layer'] : false;

foreach ($holding_array as $name => $setting)
{
	if (extension_loaded($setting['extension']))
	{
		$database_options .= '<option value="' . $name . '"'.(($db_layer == $name) ? ' selected="selected"' : '').'>' . $setting['lang'] . '</option>';
		$database_array[$name] = $setting;
	}
}

unset($holding_array);

$error = array();
$stage = isset($_POST['stage']) ? (int) $_POST['stage'] : 0;

if ($stage && !$_POST['agreement_signed'])
{
	$stage = 0;
}

if ($stage === 5)
{
	if (file_exists(SITE_FILE_ROOT.'config.php'))
	{
		require_once SITE_FILE_ROOT.'config.php';
	}

	$site_name		= get_variable('site_name', 'POST');
	$site_domain	= get_variable('site_domain', 'POST');
	$site_path		= get_variable('site_path', 'POST');
	$site_port		= get_variable('site_port', 'POST');

	$cookie_domain	= get_variable('cookie_domain', 'POST');
	$cookie_path	= get_variable('cookie_path', 'POST');
	$cookie_name	= get_variable('cookie_name', 'POST');

	$username		= get_variable('username', 'POST');
	$password		= get_variable('password', 'POST');
	$password_confirm= get_variable('password_confirm', 'POST');
	$email			= get_variable('email', 'POST');
	$email_confirm	= get_variable('email_confirm', 'POST');

	if (!$username)
	{
		$error[] = 'Invalid Username';
	}

	if (!$password || ($password !== $password_confirm))
	{
		$error[] = 'Passwords do not match.';
	}

	if (!$email || ($email !== $email_confirm))
	{
		$error[] = 'Emails Address do not match.';
	}

	if (!$site_domain)
	{
		$error[] = 'Site Domain is empty.';
	}

	if (!$site_domain)
	{
		$error[] = 'Site Domain is empty.';
	}

	if (isset($site_db))
	{
		load_class(SITE_FILE_ROOT.'includes/db/'.$site_db['type'].'.php', 'core_db', 'db_'.$site_db['type']);

		$_CLASS['core_db']->connect($site_db);
		$config_content = '';
	}
	else
	{
		$error[] = '<b>Please upload the content listed in the "Config.php Content" Section</b>';
		$config_content = get_variable('config_content', 'POST');
	}

	if (empty($error))
	{
		require_once SITE_FILE_ROOT.'includes/tables.php';
		require_once SITE_FILE_ROOT.'includes/cache/cache.php';
		require_once SITE_FILE_ROOT.'includes/cache/cache_' . $acm_type . '.php';

		load_class(false, 'core_cache', 'cache_'.$acm_type);
		$_CLASS['core_cache']->destroy_all();

		set_core_config('global', 'site_name', $site_name, false);
		set_core_config('server', 'site_domain', $site_domain, false);
		set_core_config('server', 'site_path', $site_path, false);
		set_core_config('server', 'site_port', $site_port, false);

		set_core_config('email', 'site_email', $email, false);

		set_core_config('server', 'cookie_domain', $cookie_domain, false);
		set_core_config('server', 'cookie_path', $cookie_path, false);
		set_core_config('server', 'cookie_name', $cookie_name, false);
		set_core_config('server', 'site_secure', 0, false);

		set_core_config('user', 'newest_username', $username, true);
		
		$user_update = array(
			'username'				=> $username,
			'user_password'			=> encode_password($password, 'md5'),
			'user_password_encoding'=> 'md5',
			'user_email'			=> $email
		);

		$_CLASS['core_db']->query('UPDATE ' . CORE_USERS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $user_update). ' WHERE user_id = 2');

		$_CLASS['core_template']->assign_array(array(
			'admin_link'		=> generate_link(false, array('full' => true, 'sid' => false, 'admin' => true)),
			'username'			=> $username,
		));

		$_CLASS['core_template']->display('installer/complete.html');
		
		script_close();
	}

	$_CLASS['core_template']->assign_array(array(
		'site_name'			=> $site_name,
		'site_domain'		=> $site_domain,
		'site_path'			=> $site_path,
		'site_port'			=> $site_port,
		'cookie_domain'		=> $cookie_domain,
		'cookie_path'		=> $cookie_path,
		'cookie_name'		=> $cookie_name,
		'username'			=> $username,
		'password'			=> $password,
		'password_confirm'	=> $password_confirm,
		'email'				=> $email,
		'email_confirm'		=> $email_confirm,
		'error'				=> empty($error) ? false : implode('<br/>', $error),
		'config_content'	=> $config_content
	));

	$_CLASS['core_template']->display('installer/stage4.html');

	script_close();
}

if ($stage === 4)
{
	if ($db_layer && in_array($db_layer, array_keys($database_array)))
	{
		load_class(SITE_FILE_ROOT.'includes/db/'.$db_layer.'.php', 'core_db', 'db_'.$db_layer);

		$site_db = array();
		$site_db['type']		= $db_layer;
		$site_db['persistent']	= false;

		$_CLASS['core_db']->report_error(false);

		if (strpos($db_layer, 'sqlite') === false)
		{
			$site_db['server']		= get_variable('server', 'POST');
			$site_db['port']		= get_variable('port', 'POST');
			$site_db['database']	= get_variable('database', 'POST');
			$site_db['username']	= get_variable('username', 'POST');
			$site_db['password']	= get_variable('password', 'POST');
			
			if (!$site_db['username'] || !$site_db['database'])
			{
				$error[] = 'Required Information missing';
			}
		}
		else
		{
			$site_db['file'] = get_variable('file_'.$db_layer, 'POST');

			if (!$site_db['file'])
			{
				$error[] = 'Required Information missing';
			}
		}
	}
	else
	{
		$error[] = 'Database not supported on your system';
	}

	if (empty($error) && !$_CLASS['core_db']->connect($site_db))
	{
		if (!$_CLASS['core_db']->link_identifier)
		{
			$db_error = $_CLASS['core_db']->sql_error(false, true);
			$error[] = 'Could not connect to the database'.(($db_error['message']) ? '<br/>'.$db_error['message'] : '');
		}
		else
		{
			if (isset($_POST['test']))
			{
				$error[] = 'Database Selection fail, will attempt to create in next step';
			}
			else
			{
				switch ($db_layer)
				{
					case 'mysql':
					case 'mysqli':
						if (is_null($_CLASS['core_db']->utf8_supported))
						{
							$_CLASS['core_db']->check_utf8_support();
						}
						
						if ($_CLASS['core_db']->utf8_supported)
						{
							$_CLASS['core_db']->query('CREATE DATABASE '.$site_db['database'].' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
						}
						else
						{
							$_CLASS['core_db']->query('CREATE DATABASE '.$site_db['database']);
						}
					break;

					case 'mysql3':
						$_CLASS['core_db']->query('CREATE DATABASE  '.$site_db['database']);
					break;

					case 'postgres':
						if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
						{
							$_CLASS['core_db']->query('CREATE DATABASE '.$site_db['database'] .' WITH ENCODING UNICODE');
						}
						else
						{
							$_CLASS['core_db']->query('CREATE DATABASE '.$site_db['database']);
						}
					break;
				}

				$_CLASS['core_db']->disconnect();
				$_CLASS['core_db']->connect($site_db);

				if (!$_CLASS['core_db']->connect($site_db))
				{
					$error[] = 'Database Creation Failed';
				}
			}
		}
	}
	
	if (empty($error))
	{
		$user_prefix	= get_variable('user_prefix', 'POST');
		$table_prefix	= get_variable('table_prefix', 'POST');

		require_once SITE_FILE_ROOT.'includes/tables.php';
	
		$sql = 'SELECT * FROM ' . CORE_CONFIG_TABLE;
		$result = $_CLASS['core_db']->query_limit($sql, 1);
		$_CLASS['core_db']->free_result($result);
	
		if ($result)
		{
			$error[] = 'Creation Failed: Current installation found';
		}
	}

	if (isset($_POST['test']) || !empty($error))
	{
		$stage = 3;
	}
	else
	{
		$_CLASS['core_db']->transaction();
		require(SITE_FILE_ROOT.'install/build_tables.php');
		$_CLASS['core_db']->transaction('commit');
	
		$_CLASS['core_db']->transaction();
		require(SITE_FILE_ROOT.'install/build_data.php');
		$_CLASS['core_db']->transaction('commit');
		
		$_CLASS['core_db']->optimize_tables();
		
		$_CLASS['core_db']->report_error(true);
	
		$config_data = "<?php\n\n";

		$sql = 'SELECT * FROM ' . CORE_CONFIG_TABLE;
		$result = $_CLASS['core_db']->query_limit($sql, 1);
		$_CLASS['core_db']->free_result($result);

		if (!$result)
		{
			$stage = 3;
			$error[] = 'Installation failed<br/> Please confirm that your using the currect database layer';
		}
		else
		{
			foreach ($site_db as $name => $value)
			{
				if (is_bool($value))
				{
					$value = ($value) ? 'true' : 'false';
				}
				elseif (!is_int($value))
				{
					$value = "'$value'";
				}
	
				$config_data .= "\$site_db['$name'] = $value;\n";
			}

			$config_data .= "\n";
			$config_data .= "\$table_prefix\t= '$table_prefix';\n";
			$config_data .= "\$user_prefix\t= '$user_prefix';\n\n";
			$config_data .= "\$acm_type\t\t= 'file';\n\n";
			$config_data .= "if (!defined('INDEX_PAGE'))\n{\n\tdefine('INDEX_PAGE', 'index.php');\n}\n\n";
			$config_data .= "if (!defined('ADMIN_PAGE'))\n{\n\tdefine('ADMIN_PAGE', 'admin.php');\n}\n\n";
			$config_data .= '?>';

			if (file_put_contents(SITE_FILE_ROOT.'config.php', $config_data))
			{
				$config_data = false;
			}
			else
			{
				$error[] = 'Failed to write to your config.php file<br/>Please upload the content listed in the "Config.php Content" Section';
			}

			$path = str_replace('\\','/', dirname(getenv('SCRIPT_NAME')));

			if (substr($path, -1) != '/')
			{
				$path .= '/';
			}

			$path = str_replace('install/', '', $path);
			$domain = empty($_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

			$_CLASS['core_template']->assign_array(array(
				'site_name'			=> 'New CMS Site',
				'site_domain'		=> $domain,
				'site_path'			=> $path,
				'site_port'			=> ($_SERVER['SERVER_PORT'] == 80) ? '' : $_SERVER['SERVER_PORT'],
				'cookie_domain'		=> $domain,
				'cookie_path'		=> $path,
				'cookie_name'		=> 'cms',
				'username'			=> '',
				'password'			=> '',
				'password_confirm'	=> '',
				'email'				=> '',
				'email_confirm'		=> '',
				'error'				=> empty($error) ? false : implode('<br/>', $error),
				'config_content'	=> $config_data
			));

			$_CLASS['core_template']->display('installer/stage4.html');
	
			script_close();
		}
	}
}

if ($stage === 3)
{
	if (isset($_POST['test']) && empty($error))
	{
		$error[] = 'Database Setting Perfect :-)';
	}

	$_CLASS['core_template']->assign_array(array(
		'database_options'	=> $database_options,
		'error'				=> empty($error) ? false : implode('<br/>', $error),

		'server'		=> isset($site_db['server']) ? $site_db['server'] : 'localhost',
		'port'			=> isset($site_db['port']) ? $site_db['port'] : '',
		'database'		=> isset($site_db['database']) ? $site_db['database'] : '',
		'username'		=> isset($site_db['username']) ? $site_db['username'] : '',
		'password'		=> isset($site_db['password']) ? $site_db['password'] : '',
		'file'			=> isset($site_db['file']) ? $site_db['file'] : '',
		'table_prefix'	=> get_variable('table_prefix', 'POST', 'cms_'),
		'user_prefix'	=> get_variable('user_prefix', 'POST', 'cms_'),
	));

	$_CLASS['core_template']->display('installer/stage3.html');

	script_close();
}

if ($stage === 2)
{
	$_CLASS['core_template']->assign_array(array(
		'error'				=> false,
		'continue'			=> true,

		'cache'				=> @is_writable(SITE_FILE_ROOT.'cache'),
		'cache_template'	=> @is_writable(SITE_FILE_ROOT.'cache/template'),
		'cache_hooks'		=> @is_writable(SITE_FILE_ROOT.'cache/hooks'),
		'upload_folder'		=> @is_writable('images/avatars/upload'),
	));
	
	$_CLASS['core_template']->display('installer/stage2.html');
	script_close();
}

if ($stage === 1)
{
	$gd_info = gd_info();
	$continue = true;

	if (!$compatible = version_compare(PHP_VERSION, '4.2.0', '>='))
	{
		$continue = false;
	}

	$_CLASS['core_template']->assign_array(array(
		'error'				=> false,
		'magic_quotes_gpc'	=> ((bool) ini_get('magic_quotes_gpc') === false),
		'output_buffering'	=> ((int) ini_get('output_buffering') === 0),
		'register_globals'	=> ((bool) ini_get('register_globals') === false),
		'safe_mode'			=> ((bool) ini_get('safe_mode') === false),

		'php_version'			=> PHP_VERSION,
		'workable_Version'		=> $compatible,
		'recommended_Version'	=> version_compare(PHP_VERSION, '4.3.0', '>='),
		
		'mbstring'	=> extension_loaded('mbstring'),
		'zlib'		=> extension_loaded('zlib'),
		'gd'		=> extension_loaded('gd'),
		'gd_version'=> $gd_info['GD Version'],

		'continue'	=> $continue,
	));
	
	$_CLASS['core_template']->display('installer/stage1.html');
	script_close();
}

if (!$stage)
{
	$_CLASS['core_template']->display('installer/agreement.html');
	script_close();
}

?>