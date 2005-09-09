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

error_reporting(E_ALL);

//echo str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/'; die;
$site_file_root = 'C:/Program Files/Apache Group/Apache2/cms/';

if (!extension_loaded('mbstring'))
{
	require_once($site_file_root.'includes/compatiblity/mbstring.php');
}

set_magic_quotes_runtime(0);
mb_internal_encoding('UTF-8');

define('STRIP_SLASHES', get_magic_quotes_gpc());

// Remove registered globals
if ((bool) ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $value)
	{
		unset($$var_name);
	}
}

require_once($site_file_root.'includes/functions.php');
require_once($site_file_root.'includes/display/template.php');

load_class(false, 'core_template');

$_REQUEST['stage'] = isset($_REQUEST['stage']) ? (int) $_REQUEST['stage'] : 0;

if ($_REQUEST['stage'] && !$_POST['agreement_signed'])
{
	$_REQUEST['stage'] = 0;
}

Switch ($_REQUEST['stage'])
{
	case 1:
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

		foreach ($holding_array as $name => $setting)
		{
			if (extension_loaded($setting['extension']))
			{
				$database_options .= '<option value="' . $name . '">' . $setting['lang'] . '</option>';
				$database_array[$name] = $setting;
			}
		}

		$php_config = array(
			/*array(
				'lang'	=> 'PHP version',
				'recommended'	=> '4.2.3 +',
				'current'		=> PHP_VERSION
			),*/
			array(
				'lang'	=> 'Safe Mode',
				'ini'	=> 'safe_mode',
				'recommended'	=> false,
				'current'		=> (bool) ini_get('safe_mode')
			),
			array(
				'lang'	=> 'Magic Quotes GPC',
				'ini'	=> 'magic_quotes_gpc',
				'recommended'	=> false,
				'current'		=> (bool) ini_get('magic_quotes_gpc')
			),
			array(
				'lang'	=> 'Register Globals',
				'ini'	=> 'register_globals',
				'recommended'	=> false,
				'current'		=> (bool) ini_get('register_globals')
			),
			array(
				'lang'	=> 'Output Buffering',
				'ini'	=> 'output_buffering',
				'recommended'	=> false,
				'current'		=> (bool) ini_get('output_buffering')
			),
		);

		$_CLASS['core_template']->assign_array(array(
			'database_options'	=> $database_options,
			'php_config'		=> $php_config,
		));
		
		$_CLASS['core_template']->display('installer/stage1.html');
	break;

	default:
	case 0:
		$_CLASS['core_template']->display('installer/agreement.html');
	break;
}
?>