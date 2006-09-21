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

require_once 'core.php';

$_CLASS['core_user']->user_setup(null);
$_CLASS['core_display']->load_theme('viperal_admin', SITE_FILE_ROOT.'themes_admin/viperal_admin');

$_CLASS['core_user']->add_lang('admin/common', null);
$_CLASS['core_blocks']->blocks_loaded = true;

if (!$_CLASS['core_user']->is_user)
{
	if ($_CLASS['core_user']->is_bot)
	{
		redirect();
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
	$result = $_CLASS['core_db']->query('SELECT * FROM '. CORE_ADMIN_MODULES_TABLE . " WHERE module_name='".$_CLASS['core_db']->escape($mod)."'");

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_CLASS['core_display']->process_page($row, 'admin');
	}
	$_CLASS['core_db']->free_result($result);
}

require_once SITE_FILE_ROOT.'admin/menu.php';

$main_menu = build_menu($menu);

$_CLASS['core_template']->assign_array(array(
		'LINK_HOME'		=> generate_link(),
		'LINK_ADMIN'	=> generate_link(false, array('admin' => true)),

		'MENU_MAIN'			=> $main_menu['content'],
		'MENU_MAIN_ITEMS'	=> $main_menu['menu'],
));

if (!$mod || !$_CLASS['core_display']->generate_page('admin'))
{
	$blocks = 0;
	$blocks |= (1 << BLOCK_LEFT);
	//$blocks |= (1 << BLOCK_RIGHT);
				
	$_CLASS['core_display']->page = array('page_title' => '', 'page_name' => '', 'page_blocks' => $blocks);
	$file_path = SITE_FILE_ROOT.'admin/index.php';
	
	require_once $file_path;
}
    
?>