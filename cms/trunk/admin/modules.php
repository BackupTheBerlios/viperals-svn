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

$_CLASS['core_user']->add_lang('admin/blocks', null);

$mode = get_variable('mode', 'GET', false);

function check_type(&$type, $redirect = true)
{
	$appoved_type = array(PAGE_MODULE);
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
	if ($_REQUEST['mode'] === 'search')
	{
		if (isset($_REQUEST['option']) && isset($_REQUEST['name']) && display_confirmation())
		{
			$name = urldecode($_REQUEST['name']);
			switch ($_REQUEST['option'])
			{
				case 'add':
					if (!file_exists(SITE_FILE_ROOT."modules/$name/index.php"))
					{
						break;
					}

					$insert_array = array(
						'page_name'		=> (string) $name,
						'page_type'		=> PAGE_MODULE,
						'page_status'	=> STATUS_PENDING,
					);
					$_CLASS['core_db']->sql_query_build('INSERT', $insert_array, CORE_PAGES_TABLE);
					unset($insert_array, $name);
				break;
			}
		}
		
		//$result = $_CLASS['core_db']->query('SELECT page_name, page_type FROM '. CORE_PAGES_TABLE .' WHERE page_type = '. PAGE_MODULE);
		$result = $_CLASS['core_db']->query('SELECT page_name, page_type FROM '. CORE_PAGES_TABLE);

		$modules = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$modules[$row['page_name']] = $row;
		}
		$_CLASS['core_db']->free_result($result);

		$handle = opendir(SITE_FILE_ROOT.'modules');

		while ($name = readdir($handle))
		{
			if (mb_strpos($name, '.') === false && empty($modules[$name]) && file_exists(SITE_FILE_ROOT."modules/$name/index.php"))
			{
				$_CLASS['core_template']->assign_vars_array('modules_search', array(
					'TITLE'				=> mb_convert_case(preg_replace('/_/', ' ', $name), MB_CASE_TITLE),
					'LINK_ADD'			=> generate_link('modules&amp;mode=search&amp;option=add&amp;name='.urlencode($name), array('admin' => true)),
					'LINK_INSTALL'		=> generate_link('modules&amp;mode=search&amp;option=install&amp;name='.urlencode($name), array('admin' => true)),
					'LINK_REMOVE'		=> generate_link('modules&amp;mode=search&amp;option=remove&amp;name='.urlencode($name), array('admin' => true)),
				));
				//$_CLASS['core_db']->query('INSERT INTO '. CORE_PAGES_TABLE . " (page_name, page_type, page_status, page_sides) VALUES ($file, 1, 1, 1)");
				//echo $file;
			}
		}
		closedir($handle);
		
		$_CLASS['core_display']->display(false, 'admin/modules/search.html');
	}
	elseif ($id = get_variable('id', 'GET', false, 'int'))
	{
		switch ($_REQUEST['mode'])
		{
			case 'change':
				require_once SITE_FILE_ROOT.'admin/functions/page_functions.php';

				page_change($id);
			break;

			case 'edit':
				$result = $_CLASS['core_db']->query('SELECT * FROM '. CORE_PAGES_TABLE .' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if (!$module)
				{
					trigger_error('MODULE_NOT_FOUND');
				}

				if ($module['page_status'] != STATUS_ACTIVE && $module['page_status'] != STATUS_DISABLED)
				{
					trigger_error('MODULE_NOT_INSTALLED');
				}
	
				check_type($module['page_type']);

				if (isset($_POST['submit']))
				{
					$blocks_array = get_variable('blocks_array', 'POST', array(), 'array:int');
					$active = get_variable('active', 'POST', 0, 'int');

					$data = array();
					$data['page_title'] = get_variable('title', 'POST', null);
					$data['page_status'] = ($active) ? STATUS_ACTIVE : STATUS_DISABLED;

					$data['page_blocks'] = 0;

					foreach ($blocks_array as $value)
					{
						$data['page_blocks'] |= (1 << $value);
					}

					$_CLASS['core_db']->query('UPDATE '.CORE_PAGES_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE page_id = '.$id);

					unset($data, $blocks_array, $active);

					break;
				}

				settype($module['page_status'], 'int');
				settype($module['page_blocks'], 'int');

				$page_blocks_array = array(
					array(
						'value' => BLOCK_RIGHT,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_RIGHT'),
						'checked' => ($module['page_blocks'] & (1 << BLOCK_RIGHT))
					),
					array(
						'value' => BLOCK_TOP,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_TOP'),
						'checked' => ($module['page_blocks'] & (1 << BLOCK_TOP))
					),
					array(
						'value' => BLOCK_BOTTOM,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_BOTTOM'),
						'checked' => ($module['page_blocks'] & (1 << BLOCK_BOTTOM))
					),
					array(
						'value' => BLOCK_LEFT,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_LEFT'),
						'checked' => ($module['page_blocks'] & (1 << BLOCK_LEFT))
					),
					array(
						'value' => BLOCK_MESSAGE_TOP,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_MESSAGE_TOP'),
						'checked' => ($module['page_blocks'] & (1 << BLOCK_MESSAGE_TOP))
					),
					array(
						'value' => BLOCK_MESSAGE_BOTTOM,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_MESSAGE_BOTTOM'),
						'checked' => ($module['page_blocks'] & (1 << BLOCK_MESSAGE_BOTTOM))
					)
				);
				
				$_CLASS['core_template']->assign_array(array(
					'ADMIN_PAGE_ACTION'			=> generate_link('modules&amp;mode=edit&id='.$id, array('admin' => true)),
					'ADMIN_PAGE_TITLE'			=> $module['page_title'],
					'ADMIN_PAGE_NAME'			=> mb_convert_case(preg_replace('/_/', ' ', $module['page_name']), MB_CASE_TITLE),
					'ADMIN_PAGE_BLOCKS_ARRAY'	=> $page_blocks_array,
					'ADMIN_PAGE_ACTIVE'			=> ($module['page_status'] == STATUS_ACTIVE) ? true : false,
					'ADMIN_PAGE_ERROR'			=> '',
				));
			
				$_CLASS['core_display']->display(false, 'admin/modules/edit.html');
			break;

			case 'install':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if (!$module || $module['page_status'] != STATUS_PENDING)
				{
					trigger_error($module ? 'MODULE_ALREADY_INSTALLED' : 'MODULE_NOT_FOUND');
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

					if ($page_configurer->admin)
					{
						$array = array(
							'module_name'	=> (string) $module['page_name'],
							'module_status'	=> STATUS_ACTIVE,
							'module_type'	=> 0,
						);

						$_CLASS['core_db']->query('INSERT INTO ' . CORE_ADMIN_MODULES_TABLE . ' '. $_CLASS['core_db']->sql_build_array('INSERT', $array));
					}
				}
			break;

			case 'uninstall':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$module = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$module || $module['page_status'] == STATUS_PENDING)
				{
					trigger_error($module ? 'MODULE_NOT_UNINSTALLABLE' : 'MODULE_NOT_FOUND');
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

					if ($page_configurer->admin)
					{
						$array = array(
							'module_name'	=> (string) $module['page_name'],
							'module_status'	=> STATUS_ACTIVE,
							'module_type'	=> 0,
						);

						$_CLASS['core_db']->query('DELETE FROM ' . CORE_ADMIN_MODULES_TABLE . "  WHERE module_name = '{$module['page_name']}'");
					}
				}
			break;

			case 'remove':
				require_once SITE_FILE_ROOT.'admin/functions/page_functions.php';

				page_remove($id);
			break;

			case 'auth':
				require_once SITE_FILE_ROOT.'admin/functions/page_functions.php';

				page_auth($id);
			break;
		}
	}
}

$sql = 'SELECT * FROM '.CORE_PAGES_TABLE.'
			WHERE page_type = '. PAGE_MODULE .'
				ORDER BY page_name';

$result = $_CLASS['core_db']->query($sql);

$modules = array();
$admin_auth = false;

while ($module = $_CLASS['core_db']->fetch_row_assoc($result))
{
	settype($module['page_status'], 'int');

	$active = ($module['page_status'] === STATUS_ACTIVE);
	if ($module['page_status'] === STATUS_PENDING)
	{
		$installed = false;
		$installer_link = generate_link('modules&amp;mode=install&amp;id='.$module['page_id'], array('admin' => true));
		$remove_link = generate_link('modules&amp;mode=remove&amp;id='.$module['page_id'], array('admin' => true));
	}
	else
	{
		$installed = true;
		$installer_link = generate_link('modules&amp;mode=uninstall&amp;id='.$module['page_id'], array('admin' => true));
		$remove_link = '';
	}

	$_CLASS['core_template']->assign_vars_array('normal_modules', array(
			'ACTIVE'		=> ($active),
			'CHANGE'		=> ($active) ? $_CLASS['core_user']->get_lang('DEACTIVATE') : $_CLASS['core_user']->get_lang('ACTIVATE'),
			'ERROR'			=> '',
			'INSTALLED'		=> $installed,
			'TITLE'			=> ($module['page_title']) ? $module['page_title'] : mb_convert_case(preg_replace('/_/', ' ', $module['page_name']), MB_CASE_TITLE),

			'LINK_ACTIVE'		=> generate_link('modules&amp;mode=change&amp;id='.$module['page_id'], array('admin' => true)),
			'LINK_EDIT'			=> generate_link('modules&amp;mode=edit&amp;id='.$module['page_id'], array('admin' => true)),
			'LINK_AUTH'			=> generate_link('modules&amp;mode=auth&amp;id='.$module['page_id'], array('admin' => true)),
			'LINK_ADMIN_AUTH'	=> ($admin_auth) ? generate_link('modules&amp;mode=admin_auth&amp;id='.$module['page_id'], array('admin' => true)) : '' ,
			'LINK_INSTALLER'	=> $installer_link,
			'LINK_REMOVE'		=> $remove_link,
	));
}

$_CLASS['core_template']->assign_array(array(
	'B_SEARCH'	=> generate_link('modules&amp;mode=search', array('admin' => true)),
));

$_CLASS['core_display']->display(false, 'admin/modules/index.html');

?>