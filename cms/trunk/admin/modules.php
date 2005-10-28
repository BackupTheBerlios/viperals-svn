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

if (VIPERAL != 'Admin') 
{
	die;
}

global $_CLASS;

$_CLASS['core_user']->add_lang('admin/blocks.php');

$mode = get_variable('mode', 'GET', false);

function check_type($type, $redirect = true)
{
	$appoved_type = array(1);
	$type = (int) $type;
	
	if (!in_array($type, $appoved_type, true))
	{
		if ($redirect)
		{
			redirect(generate_link('modules', array('admin' => true, 'full' => true)));
		}
		return false;
	}

	return true;
}
		
if (isset($_REQUEST['mode']))
{
	if ($id = get_variable('id', 'GET', false, 'int'))
	{
		switch ($_REQUEST['mode'])
		{
			case 'search':
				$result = $_CLASS['core_db']->query('SELECT page_name, page_type FROM '. CORE_PAGES_TABLE);

				$modules = array();

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$modules[$row['page_name']] = $row;
				}
				$_CLASS['core_db']->free_result($result);

				$handle = opendir(SITE_FILE_ROOT.'modules');

				while ($file = readdir($handle))
				{
					if (mb_strpos($file, '.') === false && empty($modules[$file]) && file_exists(SITE_FILE_ROOT."modules/$file/index.php"))
					{
						//$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE . " (page_name, page_type, page_status, page_sides) VALUES ($file, 1, 1, 1)");
						echo $file;
					}
				}
				closedir($handle);
			break;

			case 'change':
				$result = $_CLASS['core_db']->query('SELECT page_name, page_status, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module)
				{
					trigger_error('page_NOT_FOUND');
				}
			
				check_type($module['page_type']);
			
				$status = ($module['page_status'] == STATUS_ACTIVE) ? STATUS_DISABLED : STATUS_ACTIVE;
				
				if (file_exists(SITE_FILE_ROOT.'modules/'.$module['page_name'].'/configurator.php'))
				{
					require_once(SITE_FILE_ROOT.'modules/'.$module['page_name'].'/configurator.php');
					
					$name = $module['page_name'].'_configurator';

					if (class_exists($name))
					{
						$page_configurer = new $name;

						if (method_exists($page_configurer, 'status_change'))
						{
							$report = $page_configurer->status_change($status, $module['page_status']);
		
							if ($report !== true)
							{
								trigger_error(is_string($report) ? $report : 'STATUS_CHANGE_FAILED');
							}
						}
					}
				}

				$result = $_CLASS['core_db']->query('UPDATE '. CORE_PAGES_TABLE . " SET page_status = $status WHERE page_id = $id");
			break;

			case 'install':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module || $module['page_status'] != STATUS_PENDING)
				{
					trigger_error($module ? 'page_ALREADY_INSTALLED' : 'page_NOT_FOUND');
				}

				check_type($module['page_type']);

				if (display_confirmation())
				{
					$status = true;

					if (file_exists(SITE_FILE_ROOT.'modules/'.$module['page_name'].'/configurator.php'))
					{
						require_once(SITE_FILE_ROOT.'modules/'.$module['page_name'].'/configurator.php');
						
						$name = $module['page_name'].'_configurator';

						if (class_exists($name))
						{
							$page_configurer = new $name;
	
							if (method_exists($page_configurer, 'install'))
							{
								$status = $page_configurer->install();
			
								if ($status !== true)
								{
									trigger_error(is_string($status) ? $status : 'INSTALLATION_FAILED');
								}
							}
						}
					}

					$_CLASS['core_db']->query('UPDATE '.CORE_PAGES_TABLE.' set page_status = '.STATUS_DISABLED.' WHERE page_id = '.$id);
				}
			break;

			case 'uninstall':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module || $module['page_status'] == STATUS_PENDING)
				{
					trigger_error($module ? 'page_NOT_UNINSTALLABLE' : 'page_NOT_FOUND');
				}
			
				check_type($module['page_type']);
				
				if (display_confirmation())
				{
					if (file_exists(SITE_FILE_ROOT.'modules/'.$module['page_name'].'/configurator.php'))
					{
						require_once(SITE_FILE_ROOT.'modules/'.$module['page_name'].'/configurator.php');
						
						$name = $module['page_name'].'_configurator';

						if (class_exists($name))
						{
							$page_configurer = new $name;
	
							if (method_exists($page_configurer, 'uninstall'))
							{
								$status = $page_configurer->uninstall();
			
								if ($status !== true)
								{
									trigger_error(is_string($status) ? $status : 'UNISTALLATION_FAILED');
								}
							}
						}
					}

					$_CLASS['core_db']->query('UPDATE ' . CORE_PAGES_TABLE . ' set page_status = ' . STATUS_PENDING . ' WHERE page_id = '.$id);
				}
			break;

			case 'remove':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module || $module['page_status'] != STATUS_PENDING)
				{
					trigger_error($module ? 'page_NOT_REMOVABLE' : 'page_NOT_FOUND');
				}

				check_type($module['page_type']);

				if (display_confirmation())
				{
					$_CLASS['core_db']->query('DELETE from ' . CORE_PAGES_TABLE . ' WHERE page_id = '.$id);
				}
			break;

			case 'auth':
				$result = $_CLASS['core_db']->query('SELECT page_type, page_auth FROM ' . CORE_PAGES_TABLE . ' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
		
				if (!$module)
				{
					trigger_error('page_NOT_FOUND');
				}
		
				$module['page_auth'] = ($module['page_auth']) ? unserialize($module['page_auth']) : '';
		
				check_type($module['page_type']);
		
				$_CLASS['core_display']->display_header();
		
				$auth = $_CLASS['core_auth']->generate_auth_options($module['page_auth']);
		
				if ($auth !== false)
				{
					if (is_null($auth))
					{
						$module['page_auth'] = '';
						$auth = 'null';
					}
					else
					{
						$module['page_auth'] = $auth;
						$auth = "'".$_CLASS['core_db']->escape(serialize($auth))."'";
					}
		
					$_CLASS['core_db']->query('UPDATE '.CORE_PAGES_TABLE." set page_status = $auth WHERE page_id = $id");
					$_CLASS['core_cache']->destroy('blocks');
				}

				$_CLASS['core_display']->display_footer();
			break;
		}
	}
}


$result = $_CLASS['core_db']->query('SELECT * FROM '.CORE_PAGES_TABLE.' ORDER BY page_name');

$modules = array();

while($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$modules[$row['page_type']][] = $row;
}
$_CLASS['core_db']->free_result($result);

$admin_auth = false;
$_CLASS['core_template']->assign('S_LINK_ADMIN_AUTH', $admin_auth);

$page_type = array(0 => 'system', 1 => 'normal');

foreach ($page_type as $type => $name)
{
	if (empty($modules[$type]))
	{
		continue;
	}

	foreach ($modules[$type] as $module)
	{
		$active = $module['page_status'] == STATUS_ACTIVE;

		if ($module['page_status'] == STATUS_PENDING)
		{
			$installed = false;
			$installer_link = generate_link('modules&amp;mode=install&amp;id='.$module['page_id'], array('admin' => true));
			$remove_link = generate_link('modules&amp;mode=remove&amp;id='.$module['page_id'], array('admin' => true));
		}
		else
		{
			$installed = true;
			$installer_link = ($module['page_type'] != BLOCKTYPE_SYSTEM) ? generate_link('modules&amp;mode=uninstall&amp;id='.$module['page_id'], array('admin' => true)) : '';
			$remove_link = '';
		}

		$_CLASS['core_template']->assign_vars_array($name.'_modules', array(
				'ACTIVE'		=> ($active),
				'CHANGE'		=> ($active) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
				'ERROR'			=> '',
				'INSTALLED'		=> $installed,
				'TITLE'			=> ($module['page_title']) ? $module['page_title'] : $module['page_name'],

				'LINK_ACTIVE'		=> generate_link('modules&amp;mode=change&amp;id='.$module['page_id'], array('admin' => true)),
				'LINK_EDIT'			=> generate_link('modules&amp;mode=edit&amp;id='.$module['page_id'], array('admin' => true)),
				'LINK_AUTH'			=> generate_link('modules&amp;mode=auth&amp;id='.$module['page_id'], array('admin' => true)),
				'LINK_ADMIN_AUTH'	=> ($admin_auth) ? generate_link('modules&amp;mode=admin_auth&amp;id='.$module['page_id'], array('admin' => true)) : '' ,
				'LINK_INSTALLER'	=> $installer_link,
				'LINK_REMOVE'		=> $remove_link,
		));
	}
	unset($modules[$type], $module);
}

$_CLASS['core_display']->display(false, 'admin/modules/index.html');

?>