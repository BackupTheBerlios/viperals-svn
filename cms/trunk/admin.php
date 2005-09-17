<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal©	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
define('VIPERAL', 'Admin');
//define('NEED_SID', true);

require('core.php');

$_CLASS['core_user']->user_setup(null);
$_CLASS['core_display']->load_theme('viperal_admin', SITE_FILE_ROOT.'themes_admin/viperal_admin');

$_CLASS['core_user']->add_lang('admin/common.php');

$_CORE_MODULE['module_title'] = $_CLASS['core_user']->lang['ADMIN'];
$_CORE_MODULE['module_sides'] = BLOCK_ALL;
$_CLASS['core_blocks']->blocks_loaded = true;

if (!$_CLASS['core_user']->is_user)
{
	if ($_CLASS['core_user']->is_bot)
	{
		url_redirect();
	}

	login_box(array('admin_login' => true, 'full_screen' => true,  'full_login' => false, 'explain' => 'LOGIN_ADMIN', 'success' => 'LOGIN_ADMIN_SUCCESS'), 'login_admin.html');
}

if ($_CLASS['core_user']->data['session_admin'] == ADMIN_NOT_LOGGED)
{
	login_box(array('admin_login' => true, 'full_screen' => true, 'full_login' => false, 'explain' => 'LOGIN_ADMIN_CONFIRM', 'success' => 'LOGIN_ADMIN_SUCCESS'), 'login_admin.html');
}

if (!$_CLASS['core_user']->is_admin)
{
	trigger_error('NOT_ADMIN');
}

$mod = get_variable('mod', 'REQUEST', false);
$file_path = false;

if ($mod)
{
	$result = $_CLASS['core_db']->query('SELECT * FROM '.CORE_MODULES_TABLE." WHERE module_name='".$_CLASS['core_db']->escape($mod)."'");
	$_CORE_MODULE = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);
}

if (!$mod || !$_CORE_MODULE)
{
	$_CORE_MODULE = array('module_title' => '', 'module_name' => '');
	$file_path = SITE_FILE_ROOT.'admin/index.php';
}
else
{
	if (file_exists(SITE_FILE_ROOT.'admin/'.$_CORE_MODULE['module_name'].'.php'))
	{
		$file_path = SITE_FILE_ROOT.'admin/'.$_CORE_MODULE['module_name'].'.php';
	}
	else
	{
		$file_path = (file_exists(SITE_FILE_ROOT.'modules/'.$_CORE_MODULE['module_name'].'/admin/index.php')) ? SITE_FILE_ROOT.'modules/'.$_CORE_MODULE['module_name'].'/admin/index.php' : false;
	}
}

if (!$file_path)
{
	trigger_error('NO_ADMIN_MODULE', E_USER_ERROR);
}

if ($_CORE_MODULE['module_name'])
{
	if (!$_CLASS['core_auth']->admin_power($_CORE_MODULE['module_name']))
	{
		trigger_error('NOT_AUTH', E_USER_ERROR);
	}
}

$_CORE_MODULE['module_title'] = $_CLASS['core_user']->lang['ADMIN'].' &gt; '.$_CORE_MODULE['module_title'];
$_CORE_MODULE['module_sides'] = BLOCK_ALL;
	
require(SITE_FILE_ROOT.'admin/menu.php');

$main_menu = build_menu($menu);

$_CLASS['core_template']->assign_array(array(
		'LINK_HOME'		=> generate_link(),
		'LINK_ADMIN'	=> generate_link(false, array('admin' => true)),

		'MENU_MAIN'			=> $main_menu['content'],
		'MENU_MAIN_ITEMS'	=> $main_menu['menu'],
));


//load_class(SITE_FILE_ROOT.'includes/core_editor.php', 'core_editor');
//$_CLASS['core_editor']->setup();
require($file_path);
    
?>