<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

if (VIPERAL != 'Admin') 
{
	die;
}

$_CLASS['core_user']->add_lang('admin/blocks.php');

$mode = get_variable('mode', 'GET', false);

function get_id($rediret = true)
{
	$id = get_variable('id', 'GET', false, 'integer');

	if (!$id && $rediret)
	{
		url_redirect(generate_link('modules', array('admin' => true)));
		die;
	}
	
	return $id;
}

function check_type($type, $redirect = true)
{
	$appoved_type = array(MODULE_NORMAL);
	$type = (int) $type;
	
	if (!in_array($type, $appoved_type, true))
	{
		if ($redirect)
		{
			url_redirect(generate_link('modules', array('admin' => true)));
			die;
		}
		return false;
	}
	
	return true;
}

switch ($mode)
{
	case 'change':
		modules_change(get_id());
		url_redirect(generate_link('modules', array('admin' => true)));
    break;
    
 	
    case 'uninstall':
		modules_uninstall();	
    break;
    
	case 'auth':
		$id = get_id();
		
		$result = $_CLASS['core_db']->sql_query('SELECT type, auth FROM ' . CORE_MODULES_TABLE . ' WHERE id='.$id);
		$module = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
		if (!$module)
		{
			trigger_error('MODULE_NOT_FOUND');
		}
	
		$module['auth'] = ($module['auth']) ? unserialize($module['auth']) : '';
		
		check_type($module['type']);
		
		if ($auth = $_CLASS['core_auth']->generate_auth_options($module['auth']))
		{
			$module['auth'] = ($auth === true) ? '' : $auth;
			$auth = ($auth === true) ? '' : $_CLASS['core_db']->sql_escape(serialize($auth));
		
			$_CLASS['core_db']->sql_query('UPDATE '.CORE_MODULES_TABLE." set auth = '$auth' WHERE id = $id");
			$_CLASS['core_cache']->destroy('blocks');
		}
		
		$_CLASS['core_display']->display_head();
		$_CLASS['core_auth']->generate_auth_options($module['auth'], true);
		$_CLASS['core_display']->display_footer();
	

    default:
		modules_admin();
	break;
}

function modules_admin()
{
    global $_CLASS, $site_file_root;
	
    $result = $_CLASS['core_db']->sql_query('SELECT * FROM '.CORE_MODULES_TABLE.' ORDER BY homepage DESC');

    $modules = array();
    
    while($row = $_CLASS['core_db']->sql_fetchrow($result))
    {
        $modules[(int) $row['type']][] = $row;
    }
	$_CLASS['core_db']->sql_freeresult($result);

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
			$_CLASS['core_template']->assign_vars_array($name.'_modules', array(
					'ACTIVE'		=> ($module['active']) ? true : false,
					'ACTIVE_LINK'	=> generate_link('modules&amp;mode=change&amp;id='.$module['id'], array('admin' => true)),
					'CHANGE'		=> ($module['active']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
					'ERROR'			=> '',
					'EDIT_LINK'		=> generate_link('modules&amp;mode=edit&amp;id='.$module['id'], array('admin' => true)),
					'AUTH_LINK'		=> generate_link('modules&amp;mode=auth&amp;id='.$module['id'], array('admin' => true)),
					'ADMIN_AUTH_LINK'=> ($admin_auth) ? generate_link('modules&amp;mode=admin_auth&amp;id='.$module['id'], array('admin' => true)) : '' ,
					'UNINSTALL_LINK'=> ($module['type'] != BLOCKTYPE_SYSTEM) ? generate_link('modules&amp;mode=uninstall&amp;id='.$module['id'], array('admin' => true)) : '',
					'TITLE'			=> $module['title'],
			));
		}
		unset($modules[$type], $module);
    }
       
    $_CLASS['core_display']->display_head();

	$_CLASS['core_template']->display('admin/modules/index.html');
    
    $_CLASS['core_display']->display_footer();
}

function modules_change($id)
{	
	global $_CLASS;
	
	$result = $_CLASS['core_db']->sql_query('SELECT active, type FROM '.CORE_MODULES_TABLE.' WHERE id='.$id);
	$module = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	if (!$module)
	{
		trigger_error('MODULE_NOT_FOUND');
	}

	check_type($module['type']);

	$result = $_CLASS['core_db']->sql_query('UPDATE '.CORE_MODULES_TABLE.' SET active='.intval(!$module['active']).' WHERE id='.$id);
}

function modules_homepage($id, $option)
{
	global $_CLASS;

	if (!in_array($option, array('down', 'up', 'bottom', 'top')))
	{
		return;
	}

	$result = $_CLASS['core_db']->sql_query('SELECT type, homepage FROM '.CORE_MODULES_TABLE.' WHERE id ='. $id);
	$module = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	if (!$module || !$module['homepage'])
	{
		trigger_error('MODULE_NOT_FOUND');
	}

	check_type($module['position']);
	$module['homepage'] = (int) $module['homepage'];

	switch ($option)
	{
		case 'down':
			$result = $_CLASS['core_db']->sql_query('SELECT MAX(homepage) as homepage FROM '.CORE_MODULES_TABLE);
			$max_homepage = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if ($module['homepage'] < $max_homepage['homepage'])
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage-1 WHERE position='.$module['position'].' AND homepage='.($module['homepage'] + 1));
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage='.($module['homepage'] + 1).' WHERE id ='. $id);
			}
		break;

		case 'up':
			if ($module['homepage'] && $module['homepage'] != 1)
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage+1 WHERE position='.$module['position'].' AND homepage='.($module['homepage'] - 1));
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage='.($module['homepage'] -1 ).' WHERE id ='. $id);
			}
		break;

		case 'bottom':
			$result = $_CLASS['core_db']->sql_query('SELECT MAX(homepage) as homepage FROM '.CORE_MODULES_TABLE);
			$max_homepage = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if ($module['homepage'] < $max_homepage['homepage'])
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage-1 WHERE position='.$module['position'].' AND homepage > '.$module['homepage']);
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage='.$max_homepage['homepage'].' WHERE id='.$id);
			}
		break;

		case 'top':
			if ($module['homepage'] != 1)
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage=homepage+1 WHERE position='.$module['position'].' AND homepage < '.$module['homepage']);
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET homepage=1 WHERE id='.$id);
			}
		break;
	}
}
?>