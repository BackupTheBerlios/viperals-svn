<?php
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}


$module['ACP_LOGGING']['ACP_ADMIN_LOGS']			 =  ($_CLASS['forums_auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_logs&amp;mode=admin', array('admin' => true)) : false;
$module['ACP_LOGGING']['ACP_MOD_LOGS']				 = ($_CLASS['forums_auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_logs&amp;mode=mod', array('admin' => true)) : false;
$module['ACP_LOGGING']['ACP_CRITICAL_LOGS']			 = ($_CLASS['forums_auth']->acl_get('a_')) ? generate_link('forums&amp;file=admin_logs&amp;mode=critical', array('admin' => true)) : false;

$module['ACP_MANAGE_FORUMS']['ACP_MANAGE_FORUMS']		= ($_CLASS['forums_auth']->acl_gets('a_forum', 'a_forumadd', 'a_forumdel')) ? generate_link('forums&amp;file=admin_forums', array('admin' => true)) : false;
$module['ACP_MANAGE_FORUMS']['ACP_PRUNE_FORUMS']   		= ($_CLASS['forums_auth']->acl_get('a_prune')) ? generate_link('forums&amp;file=admin_prune&amp;mode=forums', array('admin' => true)) : false;

$module['ACP_PERMISSION_MASKS']['ACP_FORUM_PERMISSIONS']		= ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups') || $_CLASS['forums_auth']->acl_get('a_viewauth')) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_forum_local', array('admin' => true)) : false;
$module['ACP_PERMISSION_MASKS']['ACP_FORUM_MODERATORS'] 		= ($_CLASS['forums_auth']->acl_get('a_mauth') && ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_mod_local', array('admin' => true)) : false;
$module['ACP_PERMISSION_MASKS']['ACP_USERS_FORUM_PERMISSIONS']	= ($_CLASS['forums_auth']->acl_get('a_authusers') && ($_CLASS['forums_auth']->acl_get('a_mauth') || $_CLASS['forums_auth']->acl_get('a_fauth'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_user_local', array('admin' => true)) : false;
$module['ACP_PERMISSION_MASKS']['ACP_GROUPS_FORUM_PERMISSIONS']	= ($_CLASS['forums_auth']->acl_get('a_authgroups') && ($_CLASS['forums_auth']->acl_get('a_mauth') || $_CLASS['forums_auth']->acl_get('a_fauth'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_group_local', array('admin' => true)) : false;
$module['ACP_PERMISSION_MASKS']['ACP_ADMINISTRATORS']			= ($_CLASS['forums_auth']->acl_get('a_aauth') && ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_admin_global', array('admin' => true)) : false;
$module['ACP_PERMISSION_MASKS']['ACP_GLOBAL_MODERATORS']		= ($_CLASS['forums_auth']->acl_get('a_mauth') && ($_CLASS['forums_auth']->acl_get('a_authusers') || $_CLASS['forums_auth']->acl_get('a_authgroups'))) ? generate_link('forums&amp;file=admin_permissions&amp;mode=setting_mod_global', array('admin' => true)) : false;

$module['ACP_PERMISSION_ROLES']['ACP_ADMIN_ROLES']		= ($_CLASS['forums_auth']->acl_get('a_roles') && $_CLASS['forums_auth']->acl_get('a_aauth')) ? generate_link('forums&amp;file=admin_permission_roles&amp;mode=admin_roles', array('admin' => true)) : false;
$module['ACP_PERMISSION_ROLES']['ACP_USER_ROLES']		= ($_CLASS['forums_auth']->acl_get('a_roles') && $_CLASS['forums_auth']->acl_get('a_uauth')) ? generate_link('forums&amp;file=admin_permission_roles&amp;mode=user_roles', array('admin' => true)) : false;
$module['ACP_PERMISSION_ROLES']['ACP_MOD_ROLES']		= ($_CLASS['forums_auth']->acl_get('a_roles') && $_CLASS['forums_auth']->acl_get('a_mauth')) ? generate_link('forums&amp;file=admin_permission_roles&amp;mode=mod_roles', array('admin' => true)) : false;
$module['ACP_PERMISSION_ROLES']['ACP_FORUM_ROLES']		= ($_CLASS['forums_auth']->acl_get('a_roles') && $_CLASS['forums_auth']->acl_get('a_fauth')) ? generate_link('forums&amp;file=admin_permission_roles&amp;mode=forum_roles', array('admin' => true)) : false;


$module['ACP_GENERAL_CONFIGURATION']['ACP_BOARD_FEATURES']		= ($_CLASS['forums_auth']->acl_get('a_board')) ? generate_link('forums&amp;file=admin_board&amp;mode=features', array('admin' => true)) : false;
$module['ACP_GENERAL_CONFIGURATION']['ACP_POST_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_board')) ? generate_link('forums&amp;file=admin_board&amp;mode=post', array('admin' => true)) : false;
$module['ACP_GENERAL_CONFIGURATION']['ACP_LOAD_SETTINGS']		= ($_CLASS['forums_auth']->acl_get('a_server')) ? generate_link('forums&amp;file=admin_board&amp;mode=load', array('admin' => true)) : false;

$module['ATTACHMENTS']['ACP_ATTACHMENT_SETTINGS'] 	= ($_CLASS['forums_auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=attach', array('admin' => true)) : false;
$module['ATTACHMENTS']['ACP_MANAGE_EXTENSIONS'] 	= ($_CLASS['forums_auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=extensions', array('admin' => true)) : false;
$module['ATTACHMENTS']['ACP_ORPHAN_ATTACHMENTS'] 	= ($_CLASS['forums_auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=orphan', array('admin' => true)) : false;
$module['ATTACHMENTS']['ACP_EXTENSION_GROUPS'] 		= ($_CLASS['forums_auth']->acl_get('a_attach')) ? generate_link('forums&amp;file=admin_attachments&amp;mode=ext_groups', array('admin' => true)) : false;

$module['ACP_CAT_POSTING']['ACP_RANKS']		= ($_CLASS['forums_auth']->acl_get('a_ranks')) ? generate_link('forums&amp;file=admin_ranks', array('admin' => true)) : false;
$module['ACP_CAT_POSTING']['ACP_BBCODES']	= ($_CLASS['forums_auth']->acl_get('a_bbcode')) ? generate_link('forums&amp;file=admin_bbcodes', array('admin' => true)) : false;
$module['ACP_CAT_POSTING']['ACP_ICONS']		= ($_CLASS['forums_auth']->acl_get('a_icons')) ? generate_link('forums&amp;file=admin_icons&amp;mode=icons', array('admin' => true)) : false;

?>