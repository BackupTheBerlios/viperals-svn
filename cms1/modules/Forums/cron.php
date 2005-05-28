<?php 
/** 
*
* @package phpBB3
* @version $Id: cron.php,v 1.1 2005/04/30 14:27:59 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
*/
define('IN_CRON', true);
require_once($site_file_root.'includes/forums/functions.'.$phpEx);
loadclass($site_file_root.'includes/forums/auth.'.$phpEx, 'auth');

$cron_type = request_var('cron_type', '');

$use_shutdown_function = (@function_exists('register_shutdown_function')) ? true : false;

// Run cron-like action
// Real cron-based layer will be introduced in 3.2
switch ($cron_type)
{
	case 'queue':
		include_once($site_file_root.'includes/forums/functions_messenger.'.$phpEx);
		$queue = new queue();
		if ($use_shutdown_function)
		{
			register_shutdown_function(array(&$queue, 'process'));
		}
		else
		{
			$queue->process();
		}
		break;

	case 'tidy_cache':
		if ($use_shutdown_function)
		{
			register_shutdown_function(array(&$cache, 'tidy'));
		}
		else
		{
			$cache->tidy();
		}
		break;

	case 'tidy_database':
		include_once($site_file_root.'includes/forums/functions_admin.'.$phpEx);

		if ($use_shutdown_function)
		{
			register_shutdown_function('tidy_database');
		}
		else
		{
			tidy_database();
		}
		break;
}

// Output transparent gif
header('Cache-Control: no-cache');
header('Content-type: image/gif');
header('Content-length: 43');

echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

flush();
exit;

?>