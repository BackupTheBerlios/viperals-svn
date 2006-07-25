<?php
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

//Need to be in the admin menu look into making a new one or using this one :-)
/*if ($_CLASS['auth']->acl_get('a_ban'))
{
	$module['USER']['BAN_USERS'] = generate_link('forums&amp;file=admin_ban'..'&amp;mode=user');
	$module['USER']['BAN_EMAILS'] = generate_link('forums&amp;file=admin_ban'..'&amp;mode=email');
	$module['USER']['BAN_IPS'] = generate_link('forums&amp;file=admin_ban'..'&amp;mode=ip');
}*/

$module['LOG']['ADMIN_LOGS']			 =  ($_CLASS['auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_viewlogs&amp;mode=admin', array('admin' => true)) : false;
$module['LOG']['MOD_LOGS']				 = ($_CLASS['auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_viewlogs&amp;mode=mod', array('admin' => true)) : false;
$module['LOG']['CRITICAL_LOGS']			 = ($_CLASS['auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_viewlogs&amp;mode=critical', array('admin' => true)) : false;
	
$module['DB']['SEARCH_INDEX'] 				= ($_CLASS['auth']->acl_get('a_search')) ? generate_link('forums&amp;file=admin_search', array('admin' => true)) : false;

$module['USER']['RANKS']					= ($_CLASS['auth']->acl_get('a_ranks')) ? generate_link('forums&amp;file=admin_ranks', array('admin' => true)) : '';
$module['USER']['DISALLOW']					= ($_CLASS['auth']->acl_get('a_names')) ? generate_link('forums&amp;file=admin_disallow', array('admin' => true)) : '';

$module['FORUM']['MANAGE']					= ($_CLASS['auth']->acl_gets('a_forum', 'a_forumadd', 'a_forumdel')) ? generate_link('forums&amp;file=admin_forums', array('admin' => true)) : false;
$module['FORUM']['PRUNE']   				= ($_CLASS['auth']->acl_get('a_prune')) ? generate_link('forums&amp;file=admin_prune&amp;mode=forums', array('admin' => true)) : false;

$module['PERM']['PERMISSIONS']				= ($_CLASS['auth']->acl_get('a_auth')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=forum', array('admin' => true)) : '';
$module['PERM']['MODERATORS'] 				= ($_CLASS['auth']->acl_get('a_authmods')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=mod', array('admin' => true)) : '';
$module['PERM']['SUPER_MODERATORS']			= ($_CLASS['auth']->acl_get('a_authmods')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=supermod', array('admin' => true)) : '';
$module['PERM']['ADMINISTRATORS']			= ($_CLASS['auth']->acl_get('a_authadmins')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=admin', array('admin' => true)) : '';
$module['PERM']['USER_PERMS']				= ($_CLASS['auth']->acl_get('a_authusers')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=user', array('admin' => true)) : '';
$module['PERM']['GROUP_PERMS']				= ($_CLASS['auth']->acl_get('a_authgroups')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=group', array('admin' => true)) : '';

$module['GENERAL']['AVATAR_SETTINGS']		= ($_CLASS['auth']->acl_get('a_board')) ? generate_link('forums&amp;file=admin_board&amp;mode=avatar', array('admin' => true)) : '';
$module['GENERAL']['BOARD_DEFAULTS']		= ($_CLASS['auth']->acl_get('a_defaults')) ? generate_link('forums&amp;file=admin_board&amp;mode=default', array('admin' => true)) : '';
$module['GENERAL']['BOARD_SETTINGS']		= ($_CLASS['auth']->acl_get('a_board')) ? generate_link('forums&amp;file=admin_board&amp;mode=setting', array('admin' => true)) : '';
$module['GENERAL']['EMAIL_SETTINGS']		= ($_CLASS['auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=email', array('admin' => true)) : '';
$module['GENERAL']['LOAD_SETTINGS']			= ($_CLASS['auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=load', array('admin' => true)) : '';
$module['GENERAL']['SERVER_SETTINGS']		= ($_CLASS['auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=server', array('admin' => true)) : '';
$module['GENERAL']['MESSAGE_SETTINGS']		= ($_CLASS['auth']->acl_get('a_defaults')) ? generate_link('forums&amp;file=admin_board&amp;mode=message', array('admin' => true)) : '';
$module['GENERAL']['ATTACHMENT_SETTINGS'] 	= ($_CLASS['auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=attach', array('admin' => true)) : '';
$module['GENERAL']['IM'] 					= ($_CLASS['auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_jabber', array('admin' => true)) : '';
$module['GENERAL']['PHP_INFO'] 				= ($_CLASS['auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_phpinfo', array('admin' => true)) : '';
$module['GENERAL']['MASS_EMAIL']			= ($_CLASS['auth']->acl_get('a_email')) ? generate_link('forums&amp;file=admin_email', array('admin' => true)) : '';

$module['POST']['ATTACHMENTS'] 				= ($_CLASS['auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=ext_groups', array('admin' => true)) : '';
$module['POST']['BBCODES']					= ($_CLASS['auth']->acl_get('a_bbcode')) ? generate_link('forums&amp;file=admin_bbcodes', array('admin' => true)) : '';
$module['POST']['ICONS']					= ($_CLASS['auth']->acl_get('a_icons')) ? generate_link('forums&amp;file=admin_icons&amp;mode=icons', array('admin' => true)) : '';

?>