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

//echo str_replace('\\','/', dirname(getenv('SCRIPT_FILENAME'))).'/'; die;
$site_file_root = 'C:/apachefriends/xampp/cms/';

require($site_file_root.'core.php');
$_CLASS['core_user']->add_lang('admin/common.php');

$_CORE_MODULE['title'] = $_CLASS['core_user']->lang['ADMIN'];
$_CORE_MODULE['sides'] = BLOCK_ALL;
$_CLASS['core_blocks']->blocks_loaded = true;

if (!$_CLASS['core_user']->is_user)
{
	if ($_CLASS['core_user']->is_bot)
	{
		url_redirect();
	}

	login_box(array('admin_login' => true, 'full_screen' => true,  'full_login' => false, 'explain' => $_CLASS['core_user']->lang['LOGIN_ADMIN'], 'success' => $_CLASS['core_user']->lang['LOGIN_ADMIN_SUCCESS']));
}

// redo this, don't want unneeded loggins. maybe set at session create (ADMIN_NOT_LOGGED, ADMIN_NOT_ADMIN)
if ($_CLASS['core_user']->data['session_admin'] == ADMIN_NOT_LOGGED)
{
	login_box(array('admin_login' => true, 'full_screen' => true, 'full_login' => false, 'explain' => $_CLASS['core_user']->lang['LOGIN_ADMIN_CONFIRM'], 'success' => $_CLASS['core_user']->lang['LOGIN_ADMIN_SUCCESS']));
}

if (!$_CLASS['core_user']->is_admin)
{
	trigger_error('NOT_ADMIN');
}

$mod = get_variable('mod', 'REQUEST', false);

if ($mod)
{
	$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.CORE_MODULES_TABLE." WHERE name='".$_CLASS['core_db']->sql_escape($mod)."'");
	$_CORE_MODULE = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);
}

if (!$mod || !$_CORE_MODULE)
{
	$_CORE_MODULE = array('title' => '', 'name' => '');
	$file_path = $site_file_root.'admin/index.php';
}
else
{
	if (file_exists($site_file_root.'admin/'.$_CORE_MODULE['name'].'.php'))
	{
		$file_path = $site_file_root.'admin/'.$_CORE_MODULE['name'].'.php';
	}
	else
	{
		$file_path = (file_exists($site_file_root.'modules/'.$_CORE_MODULE['name'].'/admin/index.php')) ? $site_file_root.'modules/'.$_CORE_MODULE['name'].'/admin/index.php' : false;
	}
}

if (!$file_path)
{
	trigger_error('NO_ADMIN_MODULE', E_USER_ERROR);
}

if ($_CORE_MODULE['name'])
{
	if (!$_CLASS['core_auth']->admin_auth($_CORE_MODULE['name']))
	{
		trigger_error('NOT_AUTH', E_USER_ERROR);
	}
}

$_CORE_MODULE['title'] = $_CLASS['core_user']->lang['ADMIN'].' &gt; '.$_CORE_MODULE['title'];
$_CORE_MODULE['sides'] = BLOCK_ALL;
	
$_CLASS['core_blocks']->add_block(array(
		'title'		=> 'Administration',
		'position'	=> BLOCK_LEFT,
		'file'		=> 'block-Admin.php',
	));

//load_class($site_file_root.'includes/core_editor.php', 'core_editor');
//$_CLASS['core_editor']->setup();
require($file_path);
    
?>