<?php
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

//Need to be in the admin menu look into making a new one or using this one :-)
/*if ($_CLASS['forums_auth']->acl_get('a_ban'))
{
	$module['USER']['BAN_USERS'] = generate_link('forums&amp;file=admin_ban'..'&amp;mode=user');
	$module['USER']['BAN_EMAILS'] = generate_link('forums&amp;file=admin_ban'..'&amp;mode=email');
	$module['USER']['BAN_IPS'] = generate_link('forums&amp;file=admin_ban'..'&amp;mode=ip');
}*/

$module['LOG']['ADMIN_LOGS']			 =  ($_CLASS['forums_auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_viewlogs&amp;mode=admin', array('admin' => true)) : false;
$module['LOG']['MOD_LOGS']				 = ($_CLASS['forums_auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_viewlogs&amp;mode=mod', array('admin' => true)) : false;
$module['LOG']['CRITICAL_LOGS']			 = ($_CLASS['forums_auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_viewlogs&amp;mode=critical', array('admin' => true)) : false;
	
$module['DB']['SEARCH_INDEX'] 				= ($_CLASS['forums_auth']->acl_get('a_search')) ? generate_link('forums&amp;file=admin_search', array('admin' => true)) : false;

$module['USER']['RANKS']					= ($_CLASS['forums_auth']->acl_get('a_ranks')) ? generate_link('forums&amp;file=admin_ranks', array('admin' => true)) : '';
$module['USER']['DISALLOW']					= ($_CLASS['forums_auth']->acl_get('a_names')) ? generate_link('forums&amp;file=admin_disallow', array('admin' => true)) : '';

$module['FORUM']['MANAGE']					= ($_CLASS['forums_auth']->acl_gets('a_forum', 'a_forumadd', 'a_forumdel')) ? generate_link('forums&amp;file=admin_forums', array('admin' => true)) : false;
$module['FORUM']['PRUNE']   				= ($_CLASS['forums_auth']->acl_get('a_prune')) ? generate_link('forums&amp;file=admin_prune&amp;mode=forums', array('admin' => true)) : false;

$module['PERM']['ACP_FORUM_PERMISSIONS']		= ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups') || $_CLASS['forums_auth']->acl_get('a_viewauth')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_forum_local', array('admin' => true)) : '';
$module['PERM']['ACP_FORUM_MODERATORS'] 		= ($_CLASS['forums_auth']->acl_get('a_mauth') && ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_mod_local', array('admin' => true)) : '';
$module['PERM']['ACP_USERS_FORUM_PERMISSIONS']	= ($_CLASS['forums_auth']->acl_get('a_authusers') && ($_CLASS['forums_auth']->acl_get('a_mauth') || $_CLASS['forums_auth']->acl_get('a_fauth'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_user_local', array('admin' => true)) : '';
$module['PERM']['ACP_GROUPS_FORUM_PERMISSIONS']	= ($_CLASS['forums_auth']->acl_get('a_authgroups') && ($_CLASS['forums_auth']->acl_get('a_mauth') || $_CLASS['forums_auth']->acl_get('a_fauth'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_group_local', array('admin' => true)) : '';
$module['PERM']['ACP_ADMINISTRATORS']			= ($_CLASS['forums_auth']->acl_get('a_aauth') && ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_admin_global', array('admin' => true)) : '';
$module['PERM']['ACP_GLOBAL_MODERATORS']		= ($_CLASS['forums_auth']->acl_get('a_mauth') && ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_mod_global', array('admin' => true)) : '';

$module['GENERAL']['AVATAR_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_board')) ? generate_link('forums&amp;file=admin_board&amp;mode=avatar', array('admin' => true)) : '';
$module['GENERAL']['BOARD_DEFAULTS']		= ($_CLASS['forums_auth']->acl_get('a_defaults')) ? generate_link('forums&amp;file=admin_board&amp;mode=default', array('admin' => true)) : '';
$module['GENERAL']['BOARD_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_board')) ? generate_link('forums&amp;file=admin_board&amp;mode=setting', array('admin' => true)) : '';
$module['GENERAL']['EMAIL_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=email', array('admin' => true)) : '';
$module['GENERAL']['LOAD_SETTINGS']			= ($_CLASS['forums_auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=load', array('admin' => true)) : '';
$module['GENERAL']['SERVER_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=server', array('admin' => true)) : '';
$module['GENERAL']['MESSAGE_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_defaults')) ? generate_link('forums&amp;file=admin_board&amp;mode=message', array('admin' => true)) : '';
$module['GENERAL']['ATTACHMENT_SETTINGS'] 	= ($_CLASS['forums_auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=attach', array('admin' => true)) : '';
$module['GENERAL']['IM'] 					= ($_CLASS['forums_auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_jabber', array('admin' => true)) : '';
$module['GENERAL']['PHP_INFO'] 				= ($_CLASS['forums_auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_phpinfo', array('admin' => true)) : '';
$module['GENERAL']['MASS_EMAIL']			= ($_CLASS['forums_auth']->acl_get('a_email')) ? generate_link('forums&amp;file=admin_email', array('admin' => true)) : '';

$module['POST']['ATTACHMENTS'] 				= ($_CLASS['forums_auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=ext_groups', array('admin' => true)) : '';
$module['POST']['BBCODES']					= ($_CLASS['forums_auth']->acl_get('a_bbcode')) ? generate_link('forums&amp;file=admin_bbcodes', array('admin' => true)) : '';
$module['POST']['ICONS']					= ($_CLASS['forums_auth']->acl_get('a_icons')) ? generate_link('forums&amp;file=admin_icons&amp;mode=icons', array('admin' => true)) : '';

?>