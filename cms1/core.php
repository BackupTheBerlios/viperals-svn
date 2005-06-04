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

if (!defined('VIPERAL'))
{
	header('location: /');
    die;
}

set_magic_quotes_runtime(0);
error_reporting(E_ALL);
//error_reporting(0);

// what's an empty sting '' or ' ' ?
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

// Maybe add full table ?
$_CORE_MODULE['compatiblity'] = false;

require($site_file_root.'includes/functions.php');
require($site_file_root.'config.php');
require($site_file_root.'includes/tables.php');
require($site_file_root.'includes/handler.php');
require($site_file_root.'includes/smarty/Smarty.class.php');
require($site_file_root.'includes/cache/cache_' . $acm_type . '.php');
require($site_file_root.'includes/db/'.$sitedb['dbtype'].'.php');
require($site_file_root.'includes/session.php');

$_CLASS['core_error_handler'] =& new core_error_handler;
$_CLASS['core_error_handler']->start();
//$_CLASS['core_error_handler']->stop();

$mod = get_variable('mod', 'REQUEST', false);
$file = get_variable('file', 'REQUEST', 'index');

if (function_exists('register_shutdown_function'))
{
	register_shutdown_function('script_close');
}

$_CLASS['core_template'] =& new Smarty();
$_CLASS['core_db'] =& new sql_db();
$_CLASS['core_db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);

unset($sitedb);

$_CLASS['core_cache'] =& new cache();

// maybe add to register_shutdown_function()
If ($_CORE_CONFIG['server']['optimize_rate'] && ($_CORE_CONFIG['server']['optimize_last'] + $_CORE_CONFIG['server']['optimize_rate']) < time())
{
	set_core_config('server', 'optimize_last', time());
	$_CLASS['core_db']->sql_optimize_tables();
	//optimize_cache();
}

if (VIPERAL == 'FEED')
{
	if (check_maintance_status(true) === true || check_load_status(true) === true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		die;
	}
	return;
}

loadclass($site_file_root.'includes/auth/auth.php', 'core_auth');
$_CLASS['core_user'] =& new user();

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

require_once($site_file_root.'includes/display/banners.php');
require_once($site_file_root.'includes/display/blocks.php');
require_once($site_file_root.'includes/display/display.php');

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
			// We need a theme ..
			$handle = opendir('themes');
			
			while ($file = readdir($handle))
			{
				if (!strpos('.',$file) && check_theme($file))
				{
					$theme = $file;
					break;
				}
			}
			closedir($handle);
		}
	}
}

require($site_file_root.'themes/'.$theme.'/index.php');

loadclass(false, 'core_display', 'theme_display');
loadclass(false, 'core_blocks');

$_CLASS['core_display']->theme = $theme;

?>