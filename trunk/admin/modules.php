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

$_CLASS['core_user']->add_lang('admin/blocks.php');

$mode = get_variable('mode', 'GET', false);

function check_type($type, $redirect = true)
{
	$appoved_type = array(MODULE_NORMAL);
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
	if ($id = get_variable('id', 'GET', false, 'integer'))
	{
		switch ($_REQUEST['mode'])
		{
			
			case 'change':
				$result = $_CLASS['core_db']->query('SELECT module_status, module_type FROM '.CORE_MODULES_TABLE.' WHERE module_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module)
				{
					trigger_error('MODULE_NOT_FOUND');
				}
			
				check_type($module['module_type']);
			
				$status = ($module['module_status'] == STATUS_ACTIVE) ? STATUS_DISABLED : STATUS_ACTIVE;
				$result = $_CLASS['core_db']->query('UPDATE '. CORE_MODULES_TABLE . " SET module_status = $status WHERE module_id = $id");
			break;
		
			case 'uninstall':
				$result = $_CLASS['core_db']->query('SELECT module_status, module_name, module_type FROM '.CORE_MODULES_TABLE.' WHERE module_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module)
				{
					trigger_error('MODULE_NOT_FOUND');
				}
			
				check_type($module['module_type']);
				
				if (display_confirmation())
				{
					if ($module['module_status'] !== STATUS_PENDING)
					{
						if (file_exists($site_file_root.'modules/'.$module['module_name'].'/install.php'))
						{
							require_once($site_file_root.'modules/'.$module['module_name'].'/install.php');
							
							$name = $module['module_name'].'_install';
							$install = new $name;
							
							$install->uninstall();
						}
					}

					$_CLASS['core_db']->query('DELETE from ' . CORE_MODULES_TABLE . ' where module_id = '.$id);
				}
			break;

			case 'install':
				$result = $_CLASS['core_db']->query('SELECT module_status, module_name, module_type FROM '.CORE_MODULES_TABLE.' WHERE module_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module || $module['module_status'] !== STATUS_PENDING)
				{
					trigger_error($module ? 'MODULE_ALREADY_INSTALLED' : 'MODULE_NOT_FOUND');
				}

				check_type($module['module_type']);

				if (file_exists($site_file_root.'modules/'.$module['module_name'].'/install.php'))
				{
					require_once($site_file_root.'modules/'.$module['module_name'].'/install.php');
					
					$name = $module['module_name'].'_install';
					$install = new $name;
					$error = $install->install();

					if ($error == true)
					{
						$_CLASS['core_db']->query('UPDATE '.CORE_MODULES_TABLE.' set module_status = '.STATUS_DISABLED.' WHERE module_id = '.$id);
					}
					else
					{
						// do something better than this
						trigger_error($error);
					}
				}
			break;

			case 'auth':
				$result = $_CLASS['core_db']->query('SELECT module_type, module_auth FROM ' . CORE_MODULES_TABLE . ' WHERE module_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
		
				if (!$module)
				{
					trigger_error('MODULE_NOT_FOUND');
				}
		
				$module['module_auth'] = ($module['module_auth']) ? unserialize($module['module_auth']) : '';
		
				check_type($module['module_type']);
		
				$_CLASS['core_display']->display_header();
		
				$auth = $_CLASS['core_auth']->generate_auth_options($module['module_auth']);
		
				if ($auth !== false)
				{
					if (is_null($auth))
					{
						$module['module_auth'] = '';
						$auth = 'null';
					}
					else
					{
						$module['module_auth'] = $auth;
						$auth = "'".$_CLASS['core_db']->escape(serialize($auth))."'";
					}
		
					$_CLASS['core_db']->query('UPDATE '.CORE_MODULES_TABLE." set module_status = $auth WHERE module_id = $id");
					$_CLASS['core_cache']->destroy('blocks');
				}

				$_CLASS['core_display']->display_footer();
			break;
		}
	}
}


$result = $_CLASS['core_db']->query('SELECT * FROM '.CORE_MODULES_TABLE.' ORDER BY module_name');

$modules = array();

while($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$modules[$row['module_type']][] = $row;
}
$_CLASS['core_db']->free_result($result);

$admin_auth = false;
$_CLASS['core_template']->assign('S_ADMIN_AUTH_LINK', $admin_auth);

$module_type = array(MODULE_SYSTEM => 'system', MODULE_NORMAL => 'normal');

foreach ($module_type as $type => $name)
{
	if (empty($modules[$type]))
	{
		continue;
	}

	foreach ($modules[$type] as $module)
	{
		$active = $module['module_status'] == STATUS_ACTIVE;

		if ($module['module_status'] == STATUS_PENDING)
		{
			$installed = false;
			$installer_link = generate_link('modules&amp;mode=install&amp;id='.$module['module_id'], array('admin' => true));
		}
		else
		{
			$installed = true;
			$installer_link = ($module['module_type'] != BLOCKTYPE_SYSTEM) ? generate_link('modules&amp;mode=uninstall&amp;id='.$module['module_id'], array('admin' => true)) : '';
		}

		$_CLASS['core_template']->assign_vars_array($name.'_modules', array(
				'INSTALLED'		=> $installed,
				'ACTIVE'		=> ($active),
				'ACTIVE_LINK'	=> generate_link('modules&amp;mode=change&amp;id='.$module['module_id'], array('admin' => true)),
				'CHANGE'		=> ($active) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
				'ERROR'			=> '',
				'EDIT_LINK'		=> generate_link('modules&amp;mode=edit&amp;id='.$module['module_id'], array('admin' => true)),
				'AUTH_LINK'		=> generate_link('modules&amp;mode=auth&amp;id='.$module['module_id'], array('admin' => true)),
				'ADMIN_AUTH_LINK'=> ($admin_auth) ? generate_link('modules&amp;mode=admin_auth&amp;id='.$module['module_id'], array('admin' => true)) : '' ,
				'INSTALLER_LINK'=> $installer_link,
				'TITLE'			=> ($module['module_title']) ? $module['module_title'] : $module['module_name'],

		));
	}
	unset($modules[$type], $module);
}

$_CLASS['core_display']->display(false, 'admin/modules/index.html');

/*
function modules_homepage($id, $option)
{
	global $_CLASS;

	if (!in_array($option, array('down', 'up', 'bottom', 'top')))
	{
		return;
	}

	$result = $_CLASS['core_db']->query('SELECT type, homepage FROM '.CORE_MODULES_TABLE.' WHERE id ='. $id);
	$module = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$module || !$module['module_homepage'])
	{
		trigger_error('MODULE_NOT_FOUND');
	}

	check_type($module['module_position']);
	$module['module_homepage'] = (int) $module['module_homepage'];

	switch ($option)
	{
		case 'down':
			$result = $_CLASS['core_db']->query('SELECT MAX(homepage) as homepage FROM '.CORE_MODULES_TABLE);
			$max_homepage = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($module['module_homepage'] < $max_homepage['homepage'])
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage-1 WHERE position='.$module['module_position'].' AND homepage='.($module['module_homepage'] + 1));
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage='.($module['module_homepage'] + 1).' WHERE id ='. $id);
			}
		break;

		case 'up':
			if ($module['module_homepage'] && $module['module_homepage'] != 1)
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage+1 WHERE position='.$module['module_position'].' AND homepage='.($module['module_homepage'] - 1));
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage='.($module['module_homepage'] -1 ).' WHERE id ='. $id);
			}
		break;

		case 'bottom':
			$result = $_CLASS['core_db']->query('SELECT MAX(homepage) as homepage FROM '.CORE_MODULES_TABLE);
			$max_homepage = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($module['module_homepage'] < $max_homepage['homepage'])
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage-1 WHERE position='.$module['module_position'].' AND homepage > '.$module['module_homepage']);
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage='.$max_homepage['homepage'].' WHERE id='.$id);
			}
		break;

		case 'top':
			if ($module['module_homepage'] != 1)
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage+1 WHERE position='.$module['module_position'].' AND homepage < '.$module['module_homepage']);
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET homepage=1 WHERE id='.$id);
			}
		break;
	}
}
*/
?>