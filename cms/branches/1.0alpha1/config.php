<?php

$site_db['type'] = 'mysql';
$site_db['persistent'] = false;
$site_db['server'] = 'localhost';
$site_db['port'] = '';
$site_db['database'] = 'bracnh';
$site_db['username'] = 'root';
$site_db['password'] = '';

$table_prefix	= 'cms_';
$user_prefix	= 'cms_';

$acm_type		= 'file';

if (!defined('INDEX_PAGE'))
{
	define('INDEX_PAGE', 'index.php');
}

if (!defined('ADMIN_PAGE'))
{
	define('ADMIN_PAGE', 'admin.php');
}

?>