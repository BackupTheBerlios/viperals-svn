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

// TEMP

// do not modify!
if (!defined('VIPERAL'))
{
    die;
}

//echo getenv('DOCUMENT_ROOT'); die;
/*
If (getenv('DOCUMENT_ROOT') != 'C:/Program Files/Apache Group/Apache2/htdocs')
{
    die('Your site shouldn\'t access this file');
}
*/

$prefix = 'test_';
$user_prefix = 'test_';

/*
$site_db['type']		= 'mysql';
$site_db['server']		= '';
$site_db['port']		= '';
$site_db['database']	= 'cms';
$site_db['username']	= 'root';
$site_db['password']	= '';
$site_db['persistent']	= false;
*/


$site_db['type']		= 'postgres';
$site_db['server']		= '';
$site_db['port']		= '';
$site_db['database']	= 'cms';
$site_db['username']	= 'postgres';
$site_db['password']	= 'viper83';
$site_db['persistent']	= false;


/*
$site_db['type']	= 'sqlite';
$site_db['file']	= 'C:\Program Files\Apache Group\Apache2\tester';
$site_db['persistent']	= false;
*/

/*
$site_db['type']	= 'sqlite_pdo';
$site_db['file']	= 'C:\Program Files\Apache Group\Apache2\tester_pdo';
$site_db['persistent']	= false;

*/

/*
NOT WORKING

$site_db['type']		= 'mssql';
$site_db['server']		= '';
$site_db['port']		= '';
$site_db['database']	= 'tester';
$site_db['username']	= '';
$site_db['password']	= '';
$site_db['persistent']	= false;
*/

//$acm_type = 'eaccelerator';
$acm_type = 'file';

if (!defined('INDEX_PAGE'))
{
	define('INDEX_PAGE', 'index.php');
}

if (!defined('ADMIN_PAGE'))
{
	define('ADMIN_PAGE', 'admin.php');
}

?>