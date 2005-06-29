<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

if (!defined('VIPERAL'))
{
    die;
}

set_magic_quotes_runtime(0);
error_reporting(E_ALL);
//error_reporting(0);

// Remove registered globals
if ((bool) ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $value)
	{
		unset($$var_name);
	}
	unset($variable, $value);
}

define('STRIP', (get_magic_quotes_gpc()) ? true : false);

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

$phpversion = explode('.', PHP_VERSION);
$phpversion = intval($phpversion[0].$phpversion[1]);

$base_memory_usage = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;
           
require($site_file_root.'includes/functions.php');
require($site_file_root.'config.php');
require($site_file_root.'includes/tables.php');
require($site_file_root.'includes/handler.php');
require($site_file_root.'includes/db/'.$site_db['type'].'.php');
require($site_file_root.'includes/display/template.php');
require($site_file_root.'includes/cache/cache.php');
require($site_file_root.'includes/cache/cache_' . $acm_type . '.php');

// Load basic classes
load_class(false, 'core_error_handler', 'core_error_handler');
load_class(false, 'core_cache', 'cache_'.$acm_type);
load_class(false, 'core_template', 'core_template');
load_class(false, 'core_db', 'sql_db');

// Set error handler
$_CLASS['core_error_handler']->start();
$_CLASS['core_error_handler']->stop();
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (function_exists('register_shutdown_function'))
{
	register_shutdown_function('script_close');
}

$_CLASS['core_db']->sql_connect($site_db);

unset($sitedb);

$_CLASS['core_db']->return_on_error = true;

// Error messages just incase we can't get our configs
$config_error = '<center>There is currently a problem with the site<br/>';
$config_error .= 'Please try again later<br /><br />Error Code: DB3</center>';

//remove this config.
if (($config = $_CLASS['core_cache']->get('config')) !== false)
{
	$sql = 'SELECT config_name, config_value
		FROM ' . CONFIG_TABLE . '
		WHERE is_dynamic = 1';
	$result = $_CLASS['core_db']->sql_query($sql);
	
	if (is_array($result))
	{
		trigger_error($config_error, E_USER_ERROR);
	}
	
	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$config[$row['config_name']] = $row['config_value'];
	}
}
else
{
	$config = $cached_config = array();

	$sql = 'SELECT config_name, config_value, is_dynamic
		FROM ' . CONFIG_TABLE;
			
	if (!$result = $_CLASS['core_db']->sql_query($sql))
	{
		trigger_error($config_error, E_USER_ERROR);
	}
	
	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		if (!$row['is_dynamic'])
		{
			$cached_config[$row['config_name']] = $row['config_value'];
		}

		$config[$row['config_name']] = $row['config_value'];
	}
	$_CLASS['core_db']->sql_freeresult($result);

	$_CLASS['core_cache']->put('config', $cached_config);

	unset($cached_config);
}

if (($_CORE_CONFIG = $_CLASS['core_cache']->get('core_config')) === false)
{
	$_CORE_CONFIG = array();

	$sql = 'SELECT * FROM '.CORE_CONFIG_TABLE;
		
	if (!$result = $_CLASS['core_db']->sql_query($sql))
	{
		trigger_error($config_error, E_USER_ERROR);
	}
	
	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$_CORE_CONFIG[$row['section']][$row['name']] = $row['value'];
	}
	$_CLASS['core_db']->sql_freeresult($result);

	$_CLASS['core_cache']->put('core_config', $_CORE_CONFIG);
}

unset($config_error);

$_CLASS['core_db']->return_on_error = false;

$_CLASS['core_cache']->remove('core_config');
$_CLASS['core_cache']->remove('config');

if (VIPERAL == 'FEED')
{
	if (check_maintance_status(true) === true || check_load_status(true) === true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		die;
	}
	return;
}

// Load user based classes, and display options
require($site_file_root.'includes/session.php');
require($site_file_root.'includes/user.php');
require($site_file_root.'includes/auth/auth.php');
require($site_file_root.'includes/display/blocks.php');
require($site_file_root.'includes/display/display.php');

load_class(false, 'core_auth');
load_class(false, 'core_user');

$_CLASS['core_user'] =& new core_user();
$_CLASS['core_user']->startup();

if ($_CLASS['core_user']->is_admin && $_CORE_CONFIG['global']['error'])
{
	$_CLASS['core_error_handler']->report = $_CORE_CONFIG['global']['error'];	
}
else
{
	$_CORE_CONFIG['global']['error'] = ERROR_NONE;
	$_CLASS['core_error_handler']->report = ERROR_NONE;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_CLASS['core_user']->new_session)
{
	// error here
}

// Do the theme
$themeprev = get_variable('prevtheme', 'REQUEST', false);
$theme = $_CLASS['core_user']->get_data('user_theme');

if ($themeprev && ($themeprev != $theme) && check_theme($themeprev))
{
	$theme = $themeprev;
	
	if (!get_variable('prevtemp', 'REQUEST', false))
	{
		$_CLASS['core_user']->set_data('user_theme', $theme);
	}
}
elseif (!$theme || !check_theme($theme))
{
	$theme = ($_CLASS['core_user']->data['user_theme']) ? $_CLASS['core_user']->data['user_theme'] : $_CORE_CONFIG['global']['default_theme'];     

	if (!check_theme($theme))
	{
		if (check_theme($_CORE_CONFIG['global']['default_theme']))
		{
			$theme = $_CORE_CONFIG['global']['default_theme'];
		}
		else
		{
			// We need a theme, don't we ?
			$handle = opendir('themes');
			$theme = false;
			
			while ($file = readdir($handle))
			{
				if (!strpos('.',$file) && check_theme($file))
				{
					$theme = $file;
					break;
				}
			}
			closedir($handle);
			
			if (!$theme)
			{
				trigger_error('Something here');
			}
		}
	}
}

require($site_file_root.'themes/'.$theme.'/index.php');

load_class(false, 'core_display', 'theme_display');
load_class(false, 'core_blocks');

$_CLASS['core_display']->theme = $theme;

/*
if ((time() - $config['cache_gc']) > $config['cache_last_gc'])
{
	//$_CLASS['core_cache']->gc();
	set_config('cache_last_gc', time(), true);
}

// maybe add to register_shutdown_function()
If ($_CORE_CONFIG['server']['optimize_rate'] && ($_CORE_CONFIG['server']['optimize_last'] + $_CORE_CONFIG['server']['optimize_rate']) < time())
{
	set_core_config('server', 'optimize_last', time());
	$_CLASS['core_db']->sql_optimize_tables();
	//optimize_cache();
}*/
?>