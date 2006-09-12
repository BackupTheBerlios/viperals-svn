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

if (!defined('VIPERAL'))
{
    die;
}

if (extension_loaded('zlib') && !ob_get_length())
{
	@ob_start('ob_gzhandler');
}

/* 
ini_set('display_errors', 1);
error_reporting(E_STRICT);
*/

set_magic_quotes_runtime(0);
error_reporting(E_ALL);

if (function_exists('date_default_timezone_set'))
{
	error_reporting(E_ALL | E_STRICT);

	@date_default_timezone_set('America/New_York');
}

/* Error reporting tyoes */
define('ERROR_NONE', 0);
define('ERROR_ONPAGE', 1);
define('ERROR_DEBUGGER', 2);
define('SERVER_LOCAL', (strpos($_SERVER['HTTP_HOST'], 'localhost') === 0 || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === 0));
define('STRIP_SLASHES', get_magic_quotes_gpc());

/* Remove registered globals */
if (ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $value)
	{
		unset($$var_name);
	}
}

if (!defined('SITE_FILE_ROOT'))
{
	define('SITE_FILE_ROOT', str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/');
}

if (!extension_loaded('mbstring'))
{
	require_once SITE_FILE_ROOT.'includes/compatiblity/mbstring.php';
}

mb_internal_encoding('UTF-8');
/* mb_http_output('UTF-8'); */

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];
$base_memory_usage = get_memory_usage();

/* REQUEST_URI not set in IIS */
if (empty($_SERVER['REQUEST_URI']))
{
	$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];

	if ($_SERVER['QUERY_STRING'])
	{
		$_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
	}
}

require_once SITE_FILE_ROOT.'includes/display/template.php';
require_once SITE_FILE_ROOT.'includes/functions.php';
require_once SITE_FILE_ROOT.'includes/handler.php';
require_once SITE_FILE_ROOT.'config.php';

@register_shutdown_function('script_close');

/* 
	Load First Set of Classes
*/
load_class(false, 'core_template');
load_class(false, 'core_handler');

/* We're going ot use our own error handler */
/*$_CLASS['core_handler']->start();

$_CLASS['core_handler']->start();
	$_CLASS['core_handler']->stop();
*/

if (empty($site_db))
{
	if (VIPERAL === 'FEED' || VIPERAL === 'AJAX')
	{
		header('HTTP/1.0 503 Service Unavailable');
		die;
	}

	trigger_error('503:<p style="text-align:center">Site isn\'t Installed<br/><a href="installer.php">Click here to install</a></p>', E_USER_ERROR);
}

/* 
	Load Second Set of Classes
*/
require_once SITE_FILE_ROOT.'includes/tables.php';
require_once SITE_FILE_ROOT.'includes/db/'.$site_db['type'].'.php';
require_once SITE_FILE_ROOT.'includes/cache/cache.php';
require_once SITE_FILE_ROOT.'includes/cache/cache_' . $acm_type . '.php';

load_class(false, 'core_cache', 'cache_'.$acm_type);
load_class(false, 'core_db', 'db_'.$site_db['type']);

$_CLASS['core_db']->connect($site_db);
unset($sitedb);

$_CLASS['core_db']->report_error(false);

/* Error messages just incase we can't get our configs */
$config_error = '503:<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB3</center>';

if (is_null($_CORE_CONFIG = $_CLASS['core_cache']->get('core_config')))
{
	$_CORE_CONFIG = $cache = array();

	$sql = 'SELECT * FROM '.CORE_CONFIG_TABLE;
		
	if (!$result = $_CLASS['core_db']->query($sql))
	{
		if (VIPERAL === 'FEED' || VIPERAL === 'AJAX')
		{
			header('HTTP/1.0 503 Service Unavailable');
			die;
		}

		trigger_error($config_error, E_USER_ERROR);
	}
	
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($row['config_type'])
		{
			settype($row['config_value'], $row['config_type']);
		}

		if ($row['config_cache'])
		{
			$cache[$row['config_section']][$row['config_name']] = $row['config_value'];
		}

		$_CORE_CONFIG[$row['config_section']][$row['config_name']] = $row['config_value'];
	}
	$_CLASS['core_db']->free_result($result);

	$_CLASS['core_cache']->put('core_config', $cache);
	unset($cache);
}
else
{
	$sql = 'SELECT * FROM ' . CORE_CONFIG_TABLE . ' WHERE config_cache = 0';
	
	if (!$result = $_CLASS['core_db']->query($sql))
	{
		if (VIPERAL === 'FEED' || VIPERAL === 'AJAX')
		{
			header('HTTP/1.0 503 Service Unavailable');
			die;
		}

		trigger_error($config_error, E_USER_ERROR);
	}

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($row['config_type'])
		{
			settype($row['config_value'], $row['config_type']);
		}

		$_CORE_CONFIG[$row['config_section']][$row['config_name']] = $row['config_value'];
	}
	$_CLASS['core_db']->free_result($result);
}

$_CORE_CONFIG['email']['site_mail'] = 'viperal1@gmail.com';
$_CLASS['core_db']->report_error(true);
unset($config_error);

$_CLASS['core_cache']->remove('core_config');
$_CLASS['core_cache']->remove('config');

if (VIPERAL === 'FEED' || VIPERAL === 'AJAX')
{
	if (check_maintance_status(true) === true || check_load_status(true) === true)
	{
		header('HTTP/1.0 503 Service Unavailable');

		script_close(false);
	}
}

/* 
	Load third Set of Classes :
		User based classes
		Display handler
*/

require SITE_FILE_ROOT.'includes/session.php';
require SITE_FILE_ROOT.'includes/user.php';
require SITE_FILE_ROOT.'includes/auth/auth.php';
require SITE_FILE_ROOT.'includes/auth/auth_db.php';
require SITE_FILE_ROOT.'includes/display/display.php';

load_class(false, 'core_display');
load_class(false, 'core_user');

$_CLASS['core_user']->start();

if (!$_CLASS['core_user']->is_user && $_CORE_CONFIG['global']['only_registered'])
{
	if (VIPERAL === 'FEED' || VIPERAL === 'AJAX')
	{
		header('HTTP/1.0 503 Service Unavailable');

		script_close(false);
	}

	/* conformation image  ? */
	login_box(array('full_screen'	=> true));
}

if (VIPERAL !== 'FEED' && VIPERAL !== 'AJAX' && $_CLASS['core_user']->is_admin)
{
	$_CLASS['core_handler']->report = $_CORE_CONFIG['server']['error_options'];	
}
else
{
	$_CORE_CONFIG['server']['error_options'] = ERROR_NONE;
	$_CLASS['core_handler']->report = ERROR_NONE;
}


if (VIPERAL === 'FEED')
{
	return;
}

require SITE_FILE_ROOT.'includes/display/blocks.php';
load_class(false, 'core_blocks');

/*
$_CORE_CONFIG['server']['error_options'] = ERROR_DEBUGGER;	
$_CORE_CONFIG['server']['error_options'] = ERROR_ONPAGE;	
 
$_CLASS['core_handler']->report = $_CORE_CONFIG['server']['error_options'];
*/


if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_CLASS['core_user']->new_session)
{
	die;	/* error here */
}

function get_memory_usage()
{
	if (function_exists('memory_get_usage'))
	{
		return memory_get_usage();
	}

/*
	This pisses me off, seeing that screen pop up all the time :-(
	Enable it if you want, Will be disabled by default
*/

/*
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
	{
		exec('tasklist /fi "PID eq ' . getmypid() . '" /fo LIST', $output); 
		//Mem Usage: 24,064 K
		if (!empty($output[5]))
		{
			$usage = (int) str_replace(',', '', substr($output[5], strpos($output[5], ':' ) + 1));
			// hopefully it always returns the value in KBs
			return $usage * 1000;
		}
	}
*/
	return 0;
}

?>