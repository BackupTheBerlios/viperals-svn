<?php
if (!defined('CPG_NUKE')) {
    Header("Location: ../../");
    die();
}

global $sitedb, $_CLASS, $db;
require('includes/db/'.$sitedb['dbtype'].'.'.$phpEx);

$_CLASS['db'] =& new sql_db();
$_CLASS['db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);
$db	=& $_CLASS['db'];

if (CPG_NUKE != 'Admin')
{
	unset($sitedb);
}

?>