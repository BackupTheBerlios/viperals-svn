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
mb_internal_encoding('UTF-8');
//mb_http_output('UTF-8');

// Remove registered globals
if ((bool) ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $value)
	{
		unset($$var_name);
	}
}

define('STRIP_SLASHES', get_magic_quotes_gpc());

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

// Move to index files
$base_memory_usage = function_exists('memory_get_usage') ? memory_get_usage() : 0;

require($site_file_root.'includes/functions.php');
require($site_file_root.'config.php');
require($site_file_root.'includes/tables.php');
require($site_file_root.'includes/handler.php');
require($site_file_root.'includes/mailer.php');
require($site_file_root.'includes/db/'.$site_db['type'].'.php');
require($site_file_root.'includes/display/template.php');
require($site_file_root.'includes/cache/cache.php');
require($site_file_root.'includes/cache/cache_' . $acm_type . '.php');

// Load basic classes
load_class(false, 'core_error_handler');
load_class(false, 'core_cache', 'cache_'.$acm_type);
load_class(false, 'core_template');
load_class(false, 'core_db', 'db_'.$site_db['type']);

// Set error handler
$_CLASS['core_error_handler']->start();
//$_CLASS['core_error_handler']->stop();
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (function_exists('register_shutdown_function'))
{
	register_shutdown_function('script_close');
}

$_CLASS['core_db']->connect($site_db);
unset($sitedb);

$_CLASS['core_db']->return_on_error = true;

// Error messages just incase we can't get our configs
$config_error = '<center>There is currently a problem with the site<br/>';
$config_error .= 'Please try again later<br /><br />Error Code: DB3</center>';

if (is_null($_CORE_CONFIG = $_CLASS['core_cache']->get('core_config')))
{
	$_CORE_CONFIG = array();

	$sql = 'SELECT * FROM '.CORE_CONFIG_TABLE;
		
	if (!$result = $_CLASS['core_db']->query($sql))
	{
		trigger_error($config_error, E_USER_ERROR);
	}
	
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CORE_CONFIG[$row['section']][$row['name']] = $row['value'];
	}
	$_CLASS['core_db']->free_result($result);

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
require($site_file_root.'includes/auth/auth_db.php');
require($site_file_root.'includes/display/blocks.php');
require($site_file_root.'includes/display/display.php');

load_class(false, 'core_display');
load_class(false, 'core_blocks');
load_class(false, 'core_user');

$_CLASS['core_user']->start();

if (!$_CLASS['core_user']->is_user && $_CORE_CONFIG['global']['only_registered'])
{
	// conformation image 
	login_box(array('full_screen'	=> true));
}
		
if ($_CLASS['core_user']->is_admin)
{
	$_CLASS['core_error_handler']->report = $_CORE_CONFIG['server']['error_options'];	
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

?>