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

function page_auth($id)
{
	global $_CLASS;

	$result = $_CLASS['core_db']->query('SELECT page_type, page_auth FROM ' . CORE_PAGES_TABLE . ' WHERE page_id = '.$id);
	$page = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$page)
	{
		trigger_error('page_NOT_FOUND');
	}

	$page['page_auth'] = ($page['page_auth']) ? unserialize($page['page_auth']) : '';

	check_type($page['page_type']);

	$_CLASS['core_display']->display_header();

	$auth = $_CLASS['core_auth']->generate_auth_options($page['page_auth']);

	if ($auth !== false)
	{
		if (is_null($auth))
		{
			$page['page_auth'] = '';
			$auth = 'null';
		}
		else
		{
			$page['page_auth'] = $auth;
			$auth = "'".$_CLASS['core_db']->escape(serialize($auth))."'";
		}

		$_CLASS['core_db']->query('UPDATE '. CORE_PAGES_TABLE . " set page_auth = $auth WHERE page_id = $id");
		$_CLASS['core_cache']->destroy('blocks');
	}

	$_CLASS['core_display']->display_footer();
}
		
function page_change($id)
{
	global $_CLASS;
	
	$result = $_CLASS['core_db']->query('SELECT page_name, page_status, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
	$page = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$page)
	{
		trigger_error('page_NOT_FOUND');
	}

	check_type($page['page_type']);

	$status = ($page['page_status'] == STATUS_ACTIVE) ? STATUS_DISABLED : STATUS_ACTIVE;
	
	if (file_exists(SITE_FILE_ROOT.'modules/'.$page['page_name'].'/configurator.php'))
	{
		require_once(SITE_FILE_ROOT.'modules/'.$page['page_name'].'/configurator.php');
		
		$name = $page['page_name'].'_configurator';

		if (class_exists($name))
		{
			$page_configurer = new $name;

			if (method_exists($page_configurer, 'status_change'))
			{
				$report = $page_configurer->status_change($status, $page['page_status']);

				if ($report !== true)
				{
					trigger_error(is_string($report) ? $report : 'STATUS_CHANGE_FAILED');
				}
			}
		}
	}

	$result = $_CLASS['core_db']->query('UPDATE '. CORE_PAGES_TABLE . " SET page_status = $status WHERE page_id = $id");
}

function page_remove($id)
{
	global $_CLASS;

	$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
	$page = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$page || $page['page_status'] != STATUS_PENDING)
	{
		trigger_error($page ? 'MODULE_NOT_REMOVABLE' : 'MODULE_NOT_FOUND');
	}

	check_type($page['page_type']);

	if (display_confirmation())
	{
		if ($page['page_type'] == PAGE_TEMPLATE)
		{
			@unlink(SITE_FILE_ROOT.'includes/templates/'.$page['page_location']);
		}

		$_CLASS['core_db']->query('DELETE from ' . CORE_PAGES_TABLE . ' WHERE page_id = '.$id);

		return true;
	}

	return false;
}

?>