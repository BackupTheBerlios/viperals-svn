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

if (!defined('VIPERAL'))
{
	header('location: /');
    die;
}

set_magic_quotes_runtime(0);
error_reporting(E_ALL);
//error_reporting(0);

if (ini_get('register_globals'))
{
	foreach ($_REQUEST as $variable => $value)
	{
		unset($$variable);
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

// maybe add to script_close()
If ($_CORE_CONFIG['server']['optimize_rate'] && ($_CORE_CONFIG['server']['optimize_last'] + $_CORE_CONFIG['server']['optimize_rate']) < time())
{
	set_core_config('server', 'optimize_last', time());
	$_CLASS['core_db']->sql_optimize_tables();
	//optimize_cache();
}

if (VIPERAL == 'MINILOAD')
{
	if (check_maintance_status(true) === true || check_load_status(true) === true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		die;
	}
	return;
}

$_CLASS['core_user'] =& new user();

$_CLASS['core_user']->startup();
$_CLASS['core_user']->start();

if (is_admin() && $_CORE_CONFIG['global']['error'])
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

loadclass(false, 'core_display');
loadclass(false, 'core_blocks');

?>