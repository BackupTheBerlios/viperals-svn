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

if (!defined('VIPERAL')) {
    Header('Location: ../../');
    die('Sorry you can\'t access this file directly');
}

global $sitedb, $_CLASS, $db;
require('includes/db/'.$sitedb['dbtype'].'.'.$phpEx);

$_CLASS['db'] =& new sql_db();
$_CLASS['db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);
$db	=& $_CLASS['db'];

if (VIPERAL != 'Admin')
{
	unset($sitedb);
}

?>