<?php
// -------------------------------------------------------------
//
// $Id: admin_attachments.php,v 1.29 2004/07/08 22:40:41 acydburn Exp $
//
// FILENAME  : admin_attachments.php
// STARTED   : Sun Apr 20, 2003
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

if (!$_CLASS['forums_auth']->acl_get('a_attach'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

$_CLASS['core_user']->add_lang(array('posting', 'viewtopic', 'admin_attachments'));

$error = $notify = array();
$submit = (isset($_POST['submit'])) ? true : false;
$action = request_var('action', '');
$mode = request_var('mode', 'attach');
$u_action = 'forums&file=admin_attachments&mode='.$mode;

switch ($mode)
{
	case 'attach':
		$l_title = 'ACP_ATTACHMENT_SETTINGS';
	break;

	case 'extensions':
		$l_title = 'ACP_MANAGE_EXTENSIONS';
	break;

	case 'ext_groups':
		$l_title = 'ACP_EXTENSION_GROUPS';
	break;

	case 'orphan':
		$l_title = 'ACP_ORPHAN_ATTACHMENTS';
	break;

	default:
		trigger_error('NO_MODE', E_USER_ERROR);
	break;
}

$page_title = $l_title;

$_CLASS['core_template']->assign_array(array(
	'L_TITLE'			=> $_CLASS['core_user']->lang[$l_title],
	'L_TITLE_EXPLAIN'	=> $_CLASS['core_user']->lang[$l_title . '_EXPLAIN'],
	'U_ACTION'			=> generate_link($u_action, array('admin' => true)),
	)
);

switch ($mode)
{
	case 'attach':

		require_once SITE_FILE_ROOT.'includes/forums/functions_posting.php';

		$sql = 'SELECT group_name, cat_id
			FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . '
			WHERE cat_id > 0
			ORDER BY cat_id';
		$result = $_CLASS['core_db']->query($sql);

		$s_assigned_groups = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$s_assigned_groups[$row['cat_id']][] = $row['group_name'];
		}
		$_CLASS['core_db']->free_result($result);

		$l_legend_cat_images = $_CLASS['core_user']->lang['SETTINGS_CAT_IMAGES'] . ' [' . $_CLASS['core_user']->lang['ASSIGNED_GROUP'] . ': ' . ((sizeof($s_assigned_groups[ATTACHMENT_CATEGORY_IMAGE])) ? implode(', ', $s_assigned_groups[ATTACHMENT_CATEGORY_IMAGE]) : $_CLASS['core_user']->lang['NO_EXT_GROUP']) . ']';

		$display_vars = array(
			'title'	=> 'ACP_ATTACHMENT_SETTINGS',
			'vars'	=> array(
				'img_max_width' => false, 'img_max_height' => false, 'img_link_width' => false, 'img_link_height' => false,

				'legend1'				=> 'ACP_ATTACHMENT_SETTINGS',
				'allow_attachments'		=> array('lang' => 'ALLOW_ATTACHMENTS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_pm_attach'		=> array('lang' => 'ALLOW_PM_ATTACHMENTS',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'upload_path'			=> array('lang' => 'UPLOAD_DIR',			'validate' => 'path',	'type' => 'text:25:100', 'explain' => true),
				'display_order'			=> array('lang' => 'DISPLAY_ORDER',			'validate' => 'bool',	'type' => 'custom', 'method' => 'display_order', 'explain' => true),
				'attachment_quota'		=> array('lang' => 'ATTACH_QUOTA',			'validate' => 'int',	'type' => 'custom', 'method' => 'max_filesize', 'explain' => true),
				'max_filesize'			=> array('lang' => 'ATTACH_MAX_FILESIZE',	'validate' => 'int',	'type' => 'custom', 'method' => 'max_filesize', 'explain' => true),
				'max_filesize_pm'		=> array('lang' => 'ATTACH_MAX_PM_FILESIZE','validate' => 'int',	'type' => 'custom', 'method' => 'max_filesize', 'explain' => true),
				'max_attachments'		=> array('lang' => 'MAX_ATTACHMENTS',		'validate' => 'int',	'type' => 'text:3:3', 'explain' => false),
				'max_attachments_pm'	=> array('lang' => 'MAX_ATTACHMENTS_PM',	'validate' => 'int',	'type' => 'text:3:3', 'explain' => false),
				'secure_downloads'		=> array('lang' => 'SECURE_DOWNLOADS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'secure_allow_deny'		=> array('lang' => 'SECURE_ALLOW_DENY',		'validate' => 'int',	'type' => 'custom', 'method' => 'select_allow_deny', 'explain' => true),
				'secure_allow_empty_referer' => array('lang' => 'SECURE_EMPTY_REFERRER', 'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),

				'legend2'					=> $l_legend_cat_images,
				'img_display_inlined'		=> array('lang' => 'DISPLAY_INLINED',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'img_create_thumbnail'		=> array('lang' => 'CREATE_THUMBNAIL',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'img_max_thumb_width'		=> array('lang' => 'MAX_THUMB_WIDTH',		'validate' => 'int',	'type' => 'text:7:15', 'explain' => true, 'append' => ' px'),
				'img_min_thumb_filesize'	=> array('lang' => 'MIN_THUMB_FILESIZE',	'validate' => 'int',	'type' => 'text:7:15', 'explain' => true, 'append' => ' ' . $_CLASS['core_user']->lang['BYTES']),
				'img_imagick'				=> array('lang' => 'IMAGICK_PATH',			'validate' => 'string',	'type' => 'text:20:200', 'explain' => true, 'append' => '&nbsp;&nbsp;<span>[ <a href="' . generate_link($u_action . '&amp;action=imgmagick', array('admin' => true)).'">' . $_CLASS['core_user']->lang['SEARCH_IMAGICK'] . '</a> ]</span>'),
				'img_max'					=> array('lang' => 'MAX_IMAGE_SIZE',		'validate' => 'int',	'type' => 'dimension:3:4', 'explain' => true),
				'img_link'					=> array('lang' => 'IMAGE_LINK_SIZE',		'validate' => 'int',	'type' => 'dimension:3:4', 'explain' => true),
			)
		);

		$new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? request_var('config', array('' => '')) : $new_config;
		$error = array();

		// We validate the complete config if whished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			$new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if (in_array($config_name, array('attachment_quota', 'max_filesize', 'max_filesize_pm')))
			{
				$size_var = request_var($config_name, '');
				$new_config[$config_name] = $config_value = ($size_var == 'kb') ? round($config_value * 1024) : (($size_var == 'mb') ? round($config_value * 1048576) : $config_value);
			}

			if ($submit)
			{
				set_config($config_name, $config_value);
			}
		}

		perform_site_list();

		if ($submit)
		{
			add_log('admin', 'LOG_CONFIG_ATTACH');

			// Check Settings
			test_upload($error, $new_config['upload_path'], false);

			if (!sizeof($error))
			{
				trigger_error($_CLASS['core_user']->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}
		}

		$_CLASS['core_template']->assign('S_ATTACHMENT_SETTINGS', true);

		if ($action === 'imgmagick')
		{
			$new_config['img_imagick'] = search_imagemagick();
		}

		// We strip eventually manual added convert program, we only want the patch
		$new_config['img_imagick'] = str_replace(array('convert', '.exe'), array('', ''), $new_config['img_imagick']);

		$supported_types = get_supported_image_types();

		// Check Thumbnail Support
		if (!$new_config['img_imagick'] && (!isset($supported_types['format']) || !sizeof($supported_types['format'])))
		{
			$new_config['img_create_thumbnail'] = 0;
		}

		$_CLASS['core_template']->assign_array(array(
			'U_SEARCH_IMAGICK'		=> generate_link($u_action . '&amp;action=imgmagick', array('admin' => true)),
			'S_THUMBNAIL_SUPPORT'	=> (!$new_config['img_imagick'] && (!isset($supported_types['format']) || !sizeof($supported_types['format']))) ? false : true,
			)
		);

		// Secure Download Options - Same procedure as with banning
		$allow_deny = ($new_config['secure_allow_deny']) ? 'ALLOWED' : 'DISALLOWED';

		$sql = 'SELECT *
			FROM ' . FORUMS_SITELIST_TABLE;
		$result = $_CLASS['core_db']->query($sql);

		$defined_ips = '';
		$ips = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$value = ($row['site_ip']) ? $row['site_ip'] : $row['site_hostname'];
			if ($value)
			{
				$defined_ips .=  '<option' . (($row['ip_exclude']) ? ' class="sep"' : '') . ' value="' . $row['site_id'] . '">' . $value . '</option>';
				$ips[$row['site_id']] = $value;
			}
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign_array(array(
			'S_SECURE_DOWNLOADS'	=> $new_config['secure_downloads'],
			'S_DEFINED_IPS'			=> ($defined_ips != '') ? true : false,
			'S_WARNING'				=> empty($error) ? false : true,

			'WARNING_MSG'			=> implode('<br />', $error),
			'DEFINED_IPS'			=> $defined_ips,

			'L_SECURE_TITLE'		=> $_CLASS['core_user']->lang['DEFINE_' . $allow_deny . '_IPS'],
			'L_IP_EXCLUDE'			=> $_CLASS['core_user']->lang['EXCLUDE_FROM_' . $allow_deny . '_IP'],
			'L_REMOVE_IPS'			=> $_CLASS['core_user']->lang['REMOVE_' . $allow_deny . '_IPS'],
			)
		);

		// Output relevant options
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$_CLASS['core_template']->assign_vars_array('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($_CLASS['core_user']->lang[$vars])) ? $_CLASS['core_user']->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($_CLASS['core_user']->lang[$vars['lang_explain']])) ? $_CLASS['core_user']->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($_CLASS['core_user']->lang[$vars['lang'] . '_EXPLAIN'])) ? $_CLASS['core_user']->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}

			$_CLASS['core_template']->assign_vars_array('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> $_CLASS['core_user']->lang[$vars['lang']],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> build_cfg_template($type, $config_key, $new_config, $config_key, $vars),
			));

			unset($display_vars['vars'][$config_key]);
		}

	break;

	case 'extensions':

		if ($submit || isset($_POST['add_extension_check']))
		{
			if ($submit)
			{
				// Change Extensions ?
				$extension_change_list	= (isset($_POST['extension_change_list'])) ? array_map('intval', $_POST['extension_change_list']) : array();
				$group_select_list		= (isset($_POST['group_select'])) ? array_map('intval', $_POST['group_select']) : array();

				// Generate correct Change List
				$extensions = array();

				for ($i = 0, $size = sizeof($extension_change_list); $i < $size; $i++)
				{
					$extensions[$extension_change_list[$i]]['group_id'] = $group_select_list[$i];
				}

				$sql = 'SELECT *
					FROM ' . FORUMS_EXTENSIONS_TABLE . '
					ORDER BY extension_id';
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					if ($row['group_id'] != $extensions[$row['extension_id']]['group_id'])
					{
						$sql = 'UPDATE ' . FORUMS_EXTENSIONS_TABLE . ' 
							SET group_id = ' . (int) $extensions[$row['extension_id']]['group_id'] . '
							WHERE extension_id = ' . $row['extension_id'];
						$_CLASS['core_db']->query($sql);	

						add_log('admin', 'LOG_ATTACH_EXT_UPDATE', $row['extension']);
					}
				}
				$_CLASS['core_db']->free_result($result);

				// Delete Extension?
				$extension_id_list = (isset($_POST['extension_id_list'])) ? array_map('intval', $_POST['extension_id_list']) : array();

				if (sizeof($extension_id_list))
				{
					$sql = 'SELECT extension 
						FROM ' . FORUMS_EXTENSIONS_TABLE . '
						WHERE ' . $db->sql_in_set('extension_id', $extension_id_list);
					$result = $_CLASS['core_db']->query($sql);
					
					$extension_list = '';
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$extension_list .= ($extension_list == '') ? $row['extension'] : ', ' . $row['extension'];
					}
					$_CLASS['core_db']->free_result($result);

					$sql = 'DELETE 
						FROM ' . FORUMS_EXTENSIONS_TABLE . '
						WHERE ' . $db->sql_in_set('extension_id', $extension_id_list);
					$_CLASS['core_db']->query($sql);

					add_log('admin', 'LOG_ATTACH_EXT_DEL', $extension_list);
				}
			}

			// Add Extension?
			$add_extension			= strtolower(request_var('add_extension', ''));
			$add_extension_group	= request_var('add_group_select', 0);
			$add					= (isset($_POST['add_extension_check'])) ? true : false;

			if ($add_extension != '' && $add)
			{
				if (!sizeof($error))
				{
					$sql = 'SELECT extension_id
						FROM ' . FORUMS_EXTENSIONS_TABLE . "
						WHERE extension = '" . $db->sql_escape($add_extension) . "'";
					$result = $_CLASS['core_db']->query($sql);
					
					if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$error[] = sprintf($_CLASS['core_user']->lang['EXTENSION_EXIST'], $add_extension);
					}
					$_CLASS['core_db']->free_result($result);

					if (!sizeof($error))
					{
						$sql_ary = array(
							'group_id'	=>	$add_extension_group,
							'extension'	=>	$add_extension
						);
						
						$_CLASS['core_db']->query('INSERT INTO ' . EXTENSIONS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary));
						add_log('admin', 'LOG_ATTACH_EXT_ADD', $add_extension);
					}
				}
			}

			if (!sizeof($error))
			{
				$notify[] = $_CLASS['core_user']->lang['EXTENSIONS_UPDATED'];
			}
			
			$_CLASS['core_cache']->destroy('extensions');
		}

		$_CLASS['core_template']->assign_array(array(
			'S_EXTENSIONS'			=> true,
			'ADD_EXTENSION'			=> (isset($add_extension)) ? $add_extension : '',
			'GROUP_SELECT_OPTIONS'	=> (isset($_POST['add_extension_check'])) ? group_select('add_group_select', $add_extension_group, 'extension_group') : group_select('add_group_select', false, 'extension_group'))
		);

		$sql = 'SELECT * 
			FROM ' . FORUMS_EXTENSIONS_TABLE . ' 
			ORDER BY group_id, extension';
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$old_group_id = $row['group_id'];
			do
			{
				$s_spacer = false;

				$current_group_id = $row['group_id'];
				if ($old_group_id != $current_group_id)
				{
					$s_spacer = true;
					$old_group_id = $current_group_id;
				}

				$_CLASS['core_template']->assign_vars_array('extensions', array(
					'S_SPACER'		=> $s_spacer,
					'EXTENSION_ID'	=> $row['extension_id'],
					'EXTENSION'		=> $row['extension'],
					'GROUP_OPTIONS'	=> group_select('group_select[]', $row['group_id']))
				);
			}
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
		}
		$_CLASS['core_db']->free_result($result);

	break;

	case 'ext_groups':

		$_CLASS['core_template']->assign('S_EXTENSION_GROUPS', true);

		if ($submit)
		{
			$action = request_var('action', '');
			$group_id = request_var('g', 0);

			if ($action != 'add' && $action != 'edit')
			{
				trigger_error('WRONG_MODE', E_USER_WARNING);
			}

			if (!$group_id && $action == 'edit')
			{
				trigger_error('NO_EXT_GROUP_SPECIFIED', E_USER_WARNING);
			}

			if ($group_id)
			{
				$sql = 'SELECT * FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . "
					WHERE group_id = $group_id";
				$result = $_CLASS['core_db']->query($sql);
				$ext_row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			}
			else
			{
				$ext_row = array();
			}

			$group_name = request_var('group_name', '', true);
			$new_group_name = ($action == 'add') ? $group_name : (($ext_row['group_name'] != $group_name) ? $group_name : '');

			if (!$group_name)
			{
				$error[] = $_CLASS['core_user']->lang['NO_EXT_GROUP_NAME'];
			}

			// Check New Group Name
			if ($new_group_name)
			{
				$sql = 'SELECT group_id 
					FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . "
					WHERE LOWER(group_name) = '" . $db->sql_escape(strtolower($new_group_name)) . "'";
				$result = $_CLASS['core_db']->query($sql);

				if ($_CLASS['core_db']->fetch_row_assoc($result))
				{
					$error[] = sprintf($_CLASS['core_user']->lang['EXTENSION_GROUP_EXIST'], $new_group_name);
				}
				$_CLASS['core_db']->free_result($result);
			}

			if (!sizeof($error))
			{
				// Ok, build the update/insert array
				$upload_icon	= request_var('upload_icon', 'no_image');
				$size_select	= request_var('size_select', 'b');
				$forum_select	= request_var('forum_select', false);
				$allowed_forums	= isset($_POST['allowed_forums']) ? array_map('intval', array_values($_POST['allowed_forums'])) : array();
				$allow_in_pm	= isset($_POST['allow_in_pm']) ? true : false;
				$max_filesize	= request_var('max_filesize', 0);
				$max_filesize	= ($size_select == 'kb') ? round($max_filesize * 1024) : (($size_select == 'mb') ? round($max_filesize * 1048576) : $max_filesize);
				$allow_group	= (isset($_POST['allow_group'])) ? 1 : 0;

				if ($max_filesize == $config['max_filesize'])
				{
					$max_filesize = 0;
				}

				if (!sizeof($allowed_forums))
				{
					$forum_select = false;
				}

				$group_ary = array(
					'group_name'	=> $group_name,
					'cat_id'		=> request_var('special_category', ATTACHMENT_CATEGORY_NONE),
					'allow_group'	=> $allow_group,
					'download_mode'	=> request_var('download_mode', INLINE_LINK),
					'upload_icon'	=> ($upload_icon == 'no_image') ? '' : $upload_icon,
					'max_filesize'	=> $max_filesize,
					'allowed_forums'=> ($forum_select) ? serialize($allowed_forums) : '',
					'allow_in_pm'	=> ($allow_in_pm) ? 1 : 0
				);

				$sql = ($action === 'add') ? 'INSERT INTO ' . FORUMS_EXTENSION_GROUPS_TABLE . ' ' : 'UPDATE ' . FORUMS_EXTENSION_GROUPS_TABLE . ' SET ';
				$sql .= $_CLASS['core_db']->sql_build_array((($action === 'add') ? 'INSERT' : 'UPDATE'), $group_ary);
				$sql .= ($action === 'edit') ? " WHERE group_id = $group_id" : '';

				$_CLASS['core_db']->query($sql);

				if ($action === 'add')
				{
					$group_id = $_CLASS['core_db']->insert_id(FORUMS_EXTENSION_GROUPS_TABLE, 'group_id');
				}

				add_log('admin', 'LOG_ATTACH_EXTGROUP_' . strtoupper($action), $group_name);
			}

			$extension_list = isset($_REQUEST['extensions']) ? array_map('intval', array_values($_REQUEST['extensions'])) : array();

			if ($action == 'edit' && sizeof($extension_list))
			{
				$sql = 'UPDATE ' . FORUMS_EXTENSIONS_TABLE . "
					SET group_id = 0
					WHERE group_id = $group_id";
				$_CLASS['core_db']->query($sql);
			}

			if (sizeof($extension_list))
			{
				$sql = 'UPDATE ' . FORUMS_EXTENSIONS_TABLE . " 
					SET group_id = $group_id
					WHERE extension_id IN (" . implode(', ', $extension_list) .')';
				$_CLASS['core_db']->query($sql);
			}

			rewrite_extensions();

			if (!sizeof($error))
			{
				$notify[] = $_CLASS['core_user']->lang['SUCCESS_EXTENSION_GROUP_' . strtoupper($action)];
			}
		}
	
		$cat_lang = array(
			ATTACHMENT_CATEGORY_NONE	=> $_CLASS['core_user']->lang['NO_FILE_CAT'],
			ATTACHMENT_CATEGORY_IMAGE	=> $_CLASS['core_user']->lang['CAT_IMAGES'],
			ATTACHMENT_CATEGORY_WM		=> $_CLASS['core_user']->lang['CAT_WM_FILES'],
			ATTACHMENT_CATEGORY_RM		=> $_CLASS['core_user']->lang['CAT_RM_FILES']
		);

		$group_id = request_var('g', 0);
		$action = (isset($_POST['add'])) ? 'add' : $action;
//				$action = (($action == 'add' || $action == 'edit') && $submit && !sizeof($error)) ? 'show' : $action;

		switch ($action)
		{
			case 'delete':

				if (confirm_box(true))
				{
					$sql = 'SELECT group_name 
						FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . "
						WHERE group_id = $group_id";
					$result = $_CLASS['core_db']->query($sql);
					$group_name = (string) $db->sql_fetchfield('group_name');
					$_CLASS['core_db']->free_result($result);

					$sql = 'DELETE 
						FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . " 
						WHERE group_id = $group_id";
					$_CLASS['core_db']->query($sql);

					// Set corresponding Extensions to a pending Group
					$sql = 'UPDATE ' . FORUMS_EXTENSIONS_TABLE . "
						SET group_id = 0
						WHERE group_id = $group_id";
					$_CLASS['core_db']->query($sql);
			
					add_log('admin', 'LOG_ATTACH_EXTGROUP_DEL', $group_name);

					rewrite_extensions();

					trigger_error($_CLASS['core_user']->lang['EXTENSION_GROUP_DELETED'] . adm_back_link($u_action));
				}
				else
				{
					confirm_box(false, $_CLASS['core_user']->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						'i'			=> $id,
						'mode'		=> $mode,
						'action'	=> $action,
						'group_id'	=> $group_id,
						'action'	=> 'delete',
					)));
				}

			break;

			case 'edit':
			
				if (!$group_id)
				{
					trigger_error($_CLASS['core_user']->lang['NO_EXTENSION_GROUP'] . adm_back_link($u_action), E_USER_WARNING);
				}

				$sql = 'SELECT *
					FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . "
					WHERE group_id = $group_id";
				$result = $_CLASS['core_db']->query($sql);
				$ext_group_row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				$forum_ids = (!$ext_group_row['allowed_forums']) ? array() : unserialize(trim($ext_group_row['allowed_forums']));

			// no break;

			case 'add':
				
				if ($action == 'add')
				{
					$ext_group_row = array(
						'group_name'	=> request_var('group_name', '', true),
						'cat_id'		=> 0,
						'allow_group'	=> 1,
						'allow_in_pm'	=> 1,
						'download_mode'	=> 1,
						'upload_icon'	=> '',
						'max_filesize'	=> 0,
					);
					
					$forum_ids = array();
				}

				$extensions = array();

				$sql = 'SELECT *
					FROM ' . FORUMS_EXTENSIONS_TABLE . "
					WHERE group_id = $group_id
						OR group_id = 0
					ORDER BY extension";
				$result = $_CLASS['core_db']->query($sql);
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$extensions[] = $row;
				}
				$_CLASS['core_db']->free_result($result);

				if ($ext_group_row['max_filesize'] == 0)
				{
					$ext_group_row['max_filesize'] = (int) $config['max_filesize'];
				}

				$size_format = ($ext_group_row['max_filesize'] >= 1048576) ? 'mb' : (($ext_group_row['max_filesize'] >= 1024) ? 'kb' : 'b');

				$ext_group_row['max_filesize'] = ($ext_group_row['max_filesize'] >= 1048576) ? round($ext_group_row['max_filesize'] / 1048576 * 100) / 100 : (($ext_group_row['max_filesize'] >= 1024) ? round($ext_group_row['max_filesize'] / 1024 * 100) / 100 : $ext_group_row['max_filesize']);

				$img_path = $config['upload_icons_path'];

				$filename_list = '';
				$no_image_select = false;

				$imglist = filelist($img_path);

				if (sizeof($imglist))
				{
					$imglist = array_values($imglist);
					$imglist = $imglist[0];

					foreach ($imglist as $key => $img)
					{
						if (!$ext_group_row['upload_icon'])
						{
							$no_image_select = true;
							$selected = '';
						}
						else
						{
							$selected = ($ext_group_row['upload_icon'] == $img) ? ' selected="selected"' : '';
						}

						$filename_list .= '<option value="' . htmlspecialchars($img) . '"' . $selected . '>' . htmlspecialchars($img) . '</option>';
					}
				}

				$i = 0;
				$assigned_extensions = '';
				foreach ($extensions as $num => $row)
				{
					if ($row['group_id'] == $group_id && $group_id)
					{
						$assigned_extensions .= ($i) ? ', ' . $row['extension'] : $row['extension'];
						$i++;
					}
				}

				$s_extension_options = '';
				foreach ($extensions as $row)
				{
					$s_extension_options .= '<option' . ((!$row['group_id']) ? ' class="disabled"' : '') . ' value="' . $row['extension_id'] . '"' . (($row['group_id'] == $group_id && $group_id) ? ' selected="selected"' : '') . '>' . $row['extension'] . '</option>';
				}

				$_CLASS['core_template']->assign_array(array(
					'IMG_PATH'			=> $img_path,
					'ACTION'			=> $action,
					'GROUP_ID'			=> $group_id,
					'GROUP_NAME'		=> $ext_group_row['group_name'],
					'ALLOW_GROUP'		=> $ext_group_row['allow_group'],
					'ALLOW_IN_PM'		=> $ext_group_row['allow_in_pm'],
					'UPLOAD_ICON_SRC'	=> $img_path . '/' . $ext_group_row['upload_icon'],
					'EXTGROUP_FILESIZE'	=> $ext_group_row['max_filesize'],
					'ASSIGNED_EXTENSIONS'	=> $assigned_extensions,

					'S_CATEGORY_SELECT'			=> category_select('special_category', $group_id, 'category'),
					'S_DOWNLOAD_SELECT'			=> download_select('download_mode', $group_id, 'download_mode'),
					'S_EXT_GROUP_SIZE_OPTIONS'	=> size_select_options($size_format),
					'S_EXTENSION_OPTIONS'		=> $s_extension_options,
					'S_FILENAME_LIST'			=> $filename_list,
					'S_EDIT_GROUP'				=> true,
					'S_NO_IMAGE'				=> $no_image_select,
					'S_FORUM_IDS'				=> (sizeof($forum_ids)) ? true : false,

					'U_EXTENSIONS'				=> generate_link($u_action, array('admin' => true)),
					'L_LEGEND'					=> $_CLASS['core_user']->lang[strtoupper($action) . '_EXTENSION_GROUP'],
					)
				);

				$s_forum_id_options = '';

				$sql = 'SELECT forum_id, forum_name, parent_id, forum_type, left_id, right_id
					FROM ' . FORUMS_FORUMS_TABLE . '
					ORDER BY left_id ASC';
				$result = $_CLASS['core_db']->query($sql, 600);

				$right = $cat_right = $padding_inc = 0;
				$padding = $forum_list = $holding = '';
				$padding_store = array('0' => '');
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
					{
						// Non-postable forum with no subforums, don't display
						continue;
					}

					if (!$_CLASS['forums_auth']->acl_get('f_list', $row['forum_id']))
					{
						// if the user does not have permissions to list this forum skip
						continue;
					}

					if ($row['left_id'] < $right)
					{
						$padding .= '&nbsp; &nbsp;';
						$padding_store[$row['parent_id']] = $padding;
					}
					else if ($row['left_id'] > $right + 1)
					{
						$padding = $padding_store[$row['parent_id']];
					}

					$right = $row['right_id'];

					$selected = (in_array($row['forum_id'], $forum_ids)) ? ' selected="selected"' : '';

					if ($row['left_id'] > $cat_right)
					{
						$holding = '';
					}

					if ($row['right_id'] - $row['left_id'] > 1)
					{
						$cat_right = max($cat_right, $row['right_id']);

						$holding .= '<option value="' . $row['forum_id'] . '"' . (($row['forum_type'] == FORUM_POST) ? ' class="blue"' : '') . $selected . '>' . $padding . $row['forum_name'] . '</option>';
					}
					else
					{
						$s_forum_id_options .= $holding . '<option value="' . $row['forum_id'] . '"' . (($row['forum_type'] == FORUM_POST) ? ' class="blue"' : '') . $selected . '>' . $padding . $row['forum_name'] . '</option>';
						$holding = '';
					}
				}
				$_CLASS['core_db']->free_result($result);
				unset($padding_store);

				$_CLASS['core_template']->assign_array(array(
					'S_FORUM_ID_OPTIONS'	=> $s_forum_id_options)
				);
			
			break;

			case 'deactivate':
			case 'activate':

				if (!$group_id)
				{
					trigger_error($_CLASS['core_user']->lang['NO_EXTENSION_GROUP'] . adm_back_link($u_action), E_USER_WARNING);
				}

				$sql = 'UPDATE ' . FORUMS_EXTENSION_GROUPS_TABLE . '
					SET allow_group = ' . (($action == 'activate') ? '1' : '0') . "
					WHERE group_id = $group_id";
				$_CLASS['core_db']->query($sql);

				rewrite_extensions();

			break;
		}

		$sql = 'SELECT *
			FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . '
			ORDER BY allow_group DESC, group_name';
		$result = $_CLASS['core_db']->query($sql);

		$act_deact = 'activate';
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$s_add_spacer = ($row['allow_group'] == 0 && $act_deact == 'deactivate') ? true : false;

			$act_deact = ($row['allow_group']) ? 'deactivate' : 'activate';

			$_CLASS['core_template']->assign_vars_array('groups', array(
				'S_ADD_SPACER'		=> $s_add_spacer,
				'S_ALLOWED_IN_PM'	=> ($row['allow_in_pm']) ? true : false,
				'S_GROUP_ALLOWED'	=> ($row['allow_group']) ? true : false,

				'U_EDIT'		=> generate_link($u_action . "&amp;action=edit&amp;g={$row['group_id']}", array('admin' => true)),
				'U_DELETE'		=> generate_link($u_action . "&amp;action=delete&amp;g={$row['group_id']}", array('admin' => true)),
				'U_ACT_DEACT'	=> generate_link($u_action . "&amp;action=$act_deact&amp;g={$row['group_id']}", array('admin' => true)),

				'L_ACT_DEACT'	=> $_CLASS['core_user']->lang[strtoupper($act_deact)],
				'GROUP_NAME'	=> $row['group_name'],
				'CATEGORY'		=> $cat_lang[$row['cat_id']],
				)
			);

		}
		$_CLASS['core_db']->free_result($result);

	break;

	case 'orphan':

		if ($submit)
		{
			$delete_files = (isset($_POST['delete'])) ? array_keys(request_var('delete', array('' => 0))) : array();
			$add_files = (isset($_POST['add'])) ? array_keys(request_var('add', array('' => 0))) : array();
			$post_ids = request_var('post_id', array('' => 0));

			if (sizeof($delete_files))
			{
				$sql = 'SELECT *
					FROM ' . FORUMS_ATTACHMENTS_TABLE . '
					WHERE ' . $db->sql_in_set('attach_id', $delete_files) . '
						AND is_orphan = 1';
				$result = $_CLASS['core_db']->query($sql);

				$delete_files = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					phpbb_unlink($row['physical_filename']);

					if ($row['thumbnail'])
					{
						phpbb_unlink($row['physical_filename'], 'thumbnail');
					}

					$delete_files[$row['attach_id']] = $row['real_filename'];
				}
				$_CLASS['core_db']->free_result($result);
			}

			if (sizeof($delete_files))
			{
				$sql = 'DELETE FROM ' . FORUMS_ATTACHMENTS_TABLE . '
					WHERE ' . $db->sql_in_set('attach_id', array_keys($delete_files));
				$_CLASS['core_db']->query($sql);

				add_log('admin', 'LOG_ATTACH_ORPHAN_DEL', implode(', ', $delete_files));
				$notify[] = sprintf($_CLASS['core_user']->lang['LOG_ATTACH_ORPHAN_DEL'], implode(', ', $delete_files));
			}

			$upload_list = array();
			foreach ($add_files as $attach_id)
			{
				if (!in_array($attach_id, array_keys($delete_files)) && !empty($post_ids[$attach_id]))
				{
					$upload_list[$attach_id] = $post_ids[$attach_id];
				}
			}
			unset($add_files);

			if (sizeof($upload_list))
			{
				$_CLASS['core_template']->assign('S_UPLOADING_FILES', true);

				$sql = 'SELECT forum_id, forum_name
					FROM ' . FORUMS_FORUMS_TABLE;
				$result = $_CLASS['core_db']->query($sql);

				$forum_names = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$forum_names[$row['forum_id']] = $row['forum_name'];
				}
				$_CLASS['core_db']->free_result($result);

				$sql = 'SELECT forum_id, topic_id, post_id, poster_id
					FROM ' . FORUMS_POSTS_TABLE . '
					WHERE ' . $db->sql_in_set('post_id', $upload_list);
				$result = $_CLASS['core_db']->query($sql);

				$post_info = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$post_info[$row['post_id']] = $row;
				}
				$_CLASS['core_db']->free_result($result);

				// Select those attachments we want to change...
				$sql = 'SELECT *
					FROM ' . FORUMS_ATTACHMENTS_TABLE . '
					WHERE ' . $db->sql_in_set('attach_id', array_keys($upload_list)) . '
						AND is_orphan = 1';
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$post_row = $post_info[$upload_list[$row['attach_id']]];

					$_CLASS['core_template']->assign_vars_array('upload', array(
						'FILE_INFO'		=> sprintf($_CLASS['core_user']->lang['UPLOADING_FILE_TO'], $row['real_filename'], $post_row['post_id']),
						'S_DENIED'		=> (!$_CLASS['forums_auth']->acl_get('f_attach', $post_row['forum_id'])) ? true : false,
						'L_DENIED'		=> (!$_CLASS['forums_auth']->acl_get('f_attach', $post_row['forum_id'])) ? sprintf($_CLASS['core_user']->lang['UPLOAD_DENIED_FORUM'], $forum_names[$row['forum_id']]) : '')
					);

					if (!$_CLASS['forums_auth']->acl_get('f_attach', $post_row['forum_id']))
					{
						continue;
					}

					// Adjust attachment entry
					$sql_ary = array(
						'in_message'	=> 0,
						'is_orphan'		=> 0,
						'poster_id'		=> $post_row['poster_id'],
						'post_msg_id'	=> $post_row['post_id'],
						'topic_id'		=> $post_row['topic_id'],
					);

					$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . '
						SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
						WHERE attach_id = ' . $row['attach_id'];
					$_CLASS['core_db']->query($sql);

					$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . '
						SET post_attachment = 1
						WHERE post_id = ' . $post_row['post_id'];
					$_CLASS['core_db']->query($sql);

					$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . '
						SET topic_attachment = 1
						WHERE topic_id = ' . $post_row['topic_id'];
					$_CLASS['core_db']->query($sql);

					add_log('admin', 'LOG_ATTACH_FILEUPLOAD', $post_row['post_id'], $row['real_filename']);
				}
				$_CLASS['core_db']->free_result($result);
			}
		}

		$_CLASS['core_template']->assign_array(array(
			'S_ORPHAN'		=> true)
		);

		// Just get the files with is_orphan set and older than 3 hours
		$sql = 'SELECT *
			FROM ' . FORUMS_ATTACHMENTS_TABLE . '
			WHERE is_orphan = 1
				AND filetime < ' . (time() - 3*60*60) . '
			ORDER BY filetime DESC';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$size_lang = ($row['filesize'] >= 1048576) ? $_CLASS['core_user']->lang['MB'] : (($row['filesize'] >= 1024) ? $_CLASS['core_user']->lang['KB'] : $_CLASS['core_user']->lang['BYTES']);
			$row['filesize'] = ($row['filesize'] >= 1048576) ? round((round($row['filesize'] / 1048576 * 100) / 100), 2) : (($row['filesize'] >= 1024) ? round((round($row['filesize'] / 1024 * 100) / 100), 2) : $row['filesize']);

			$_CLASS['core_template']->assign_vars_array('orphan', array(
				'FILESIZE'			=> $row['filesize'] . ' ' . $size_lang,
				'FILETIME'			=> $_CLASS['core_user']->format_date($row['filetime']),
				'REAL_FILENAME'		=> basename($row['real_filename']),
				'PHYSICAL_FILENAME'	=> basename($row['physical_filename']),
				'ATTACH_ID'			=> $row['attach_id'],
				'POST_IDS'			=> (!empty($post_ids[$row['attach_id']])) ? $post_ids[$row['attach_id']] : '',
				'U_FILE'			=> generate_link('forums&amp;file=download&amp;id=' . $row['attach_id'])
			));
		}
		$_CLASS['core_db']->free_result($result);

	break;
}

if (sizeof($error))
{
	$_CLASS['core_template']->assign_array(array(
		'S_WARNING'		=> true,
		'WARNING_MSG'	=> implode('<br />', $error))
	);
}

if (sizeof($notify))
{
	$_CLASS['core_template']->assign_array(array(
		'S_NOTIFY'		=> true,
		'NOTIFY_MSG'	=> implode('<br />', $notify))
	);
}

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($l_title), 'modules/forums/admin/acp_attachments.html');

/**
* Build Select for category items
*/
function category_select($select_name, $group_id = false, $key = '')
{
	global $_CLASS;

	$types = array(
		ATTACHMENT_CATEGORY_NONE	=> $_CLASS['core_user']->lang['NO_FILE_CAT'],
		ATTACHMENT_CATEGORY_IMAGE	=> $_CLASS['core_user']->lang['CAT_IMAGES'],
		ATTACHMENT_CATEGORY_WM		=> $_CLASS['core_user']->lang['CAT_WM_FILES'],
		ATTACHMENT_CATEGORY_RM		=> $_CLASS['core_user']->lang['CAT_RM_FILES']
	);
	
	if ($group_id)
	{
		$sql = 'SELECT cat_id
			FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . '
			WHERE group_id = ' . (int) $group_id;
		$result = $_CLASS['core_db']->query($sql);

		$cat_type = (!($row = $_CLASS['core_db']->fetch_row_assoc($result))) ? ATTACHMENT_CATEGORY_NONE : $row['cat_id'];

		$_CLASS['core_db']->free_result($result);
	}
	else
	{
		$cat_type = ATTACHMENT_CATEGORY_NONE;
	}
	
	$group_select = '<select name="' . $select_name . '"' . (($key) ? ' id="' . $key . '"' : '') . '>';

	foreach ($types as $type => $mode)
	{
		$selected = ($type == $cat_type) ? ' selected="selected"' : '';
		$group_select .= '<option value="' . $type . '"' . $selected . '>' . $mode . '</option>';
	}

	$group_select .= '</select>';

	return $group_select;
}

/**
* Extension group select
*/
function group_select($select_name, $default_group = false, $key = '')
{
	global $_CLASS;
		
	$group_select = '<select name="' . $select_name . '"' . (($key) ? ' id="' . $key . '"' : '') . '>';

	$sql = 'SELECT group_id, group_name
		FROM ' . FORUMS_EXTENSION_GROUPS_TABLE . '
		ORDER BY group_name';
	$result = $_CLASS['core_db']->query($sql);

	$group_name = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$group_name[] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	$row['group_id'] = 0;
	$row['group_name'] = $_CLASS['core_user']->lang['NOT_ASSIGNED'];
	$group_name[] = $row;
	
	for ($i = 0; $i < sizeof($group_name); $i++)
	{
		if ($default_group === false)
		{
			$selected = ($i == 0) ? ' selected="selected"' : '';
		}
		else
		{
			$selected = ($group_name[$i]['group_id'] == $default_group) ? ' selected="selected"' : '';
		}

		$group_select .= '<option value="' . $group_name[$i]['group_id'] . '"' . $selected . '>' . $group_name[$i]['group_name'] . '</option>';
	}

	$group_select .= '</select>';

	return $group_select;
}

/**
* Build select for download modes
*/
function download_select($select_name, $group_id = false, $key = '')
{
	global $_CLASS;
		
	$types = array(
		INLINE_LINK		=> $_CLASS['core_user']->lang['MODE_INLINE'],
		PHYSICAL_LINK	=> $_CLASS['core_user']->lang['MODE_PHYSICAL']
	);
	
	if ($group_id)
	{
		$sql = "SELECT download_mode
			FROM " . FORUMS_EXTENSION_GROUPS_TABLE . "
			WHERE group_id = " . (int) $group_id;
		$result = $_CLASS['core_db']->query($sql);

		$download_mode = (!($row = $_CLASS['core_db']->fetch_row_assoc($result))) ? INLINE_LINK : $row['download_mode'];

		$_CLASS['core_db']->free_result($result);
	}
	else
	{
		$download_mode = INLINE_LINK;
	}

	$group_select = '<select name="' . $select_name . '"' . (($key) ? ' id="' . $key . '"' : '') . '>';

	foreach ($types as $type => $mode)
	{
		$selected = ($type == $download_mode) ? ' selected="selected"' : '';
		$group_select .= '<option value="' . $type . '"' . $selected . '>' . $mode . '</option>';
	}

	$group_select .= '</select>';

	return $group_select;
}

/**
* Search Imagick
*/
function search_imagemagick()
{
	$imagick = '';

	$exe = ((defined('PHP_OS')) && (preg_match('#^win#i', PHP_OS))) ? '.exe' : '';

	$magic_home = getenv('MAGICK_HOME');

	if (empty($magic_home))
	{
		$locations = array('C:/WINDOWS/', 'C:/WINNT/', 'C:/WINDOWS/SYSTEM/', 'C:/WINNT/SYSTEM/', 'C:/WINDOWS/SYSTEM32/', 'C:/WINNT/SYSTEM32/', '/usr/bin/', '/usr/sbin/', '/usr/local/bin/', '/usr/local/sbin/', '/opt/', '/usr/imagemagick/', '/usr/bin/imagemagick/');
		$path_locations = str_replace('\\', '/', (explode(($exe) ? ';' : ':', getenv('PATH'))));	

		$locations = array_merge($path_locations, $locations);

		foreach ($locations as $location)
		{
			// The path might not end properly, fudge it
			if (substr($location, -1, 1) !== '/')
			{
				$location .= '/';
			}

			if (@is_readable($location . 'mogrify' . $exe) && @filesize($location . 'mogrify' . $exe) > 3000)
			{
				$imagick = str_replace('\\', '/', $location);
				continue;
			}
		}
	}
	else
	{
		$imagick = str_replace('\\', '/', $magic_home);
	}

	return $imagick;
}

/**
* Test Settings
*/
function test_upload(&$error, $upload_dir, $create_directory = false)
{
	global $_CLASS;

	// Does the target directory exist, is it a directory and writeable.
	if ($create_directory)
	{
		if (!file_exists($upload_dir))
		{
			@mkdir($upload_dir, 0777);
			@chmod($upload_dir, 0777);
		}
	}

	if (!file_exists($upload_dir))
	{
		$error[] = sprintf($_CLASS['core_user']->lang['NO_UPLOAD_DIR'], $upload_dir);
		return;
	}

	if (!is_dir($upload_dir))
	{
		$error[] = sprintf($_CLASS['core_user']->lang['UPLOAD_NOT_DIR'], $upload_dir);
		return;
	}

	if (!is_writable($upload_dir))
	{
		$error[] = sprintf($_CLASS['core_user']->lang['NO_WRITE_UPLOAD'], $upload_dir);
		return;
	}
}

/**
* Perform operations on sites for external linking
*/
function perform_site_list()
{
	global $db, $user;

	if (isset($_REQUEST['securesubmit']))
	{
		// Grab the list of entries
		$ips = request_var('ips', '');
		$ip_list = array_unique(explode("\n", $ips));
		$ip_list_log = implode(', ', $ip_list);

		$ip_exclude = (!empty($_POST['ipexclude'])) ? 1 : 0;

		$iplist = array();
		$hostlist = array();

		foreach ($ip_list as $item)
		{
			if (preg_match('#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})[ ]*\-[ ]*([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#', trim($item), $ip_range_explode))
			{
				// Don't ask about all this, just don't ask ... !
				$ip_1_counter = $ip_range_explode[1];
				$ip_1_end = $ip_range_explode[5];

				while ($ip_1_counter <= $ip_1_end)
				{
					$ip_2_counter = ($ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[2] : 0;
					$ip_2_end = ($ip_1_counter < $ip_1_end) ? 254 : $ip_range_explode[6];

					if ($ip_2_counter == 0 && $ip_2_end == 254)
					{
						$ip_2_counter = 256;
						$ip_2_fragment = 256;

						$iplist[] = "'$ip_1_counter.*'";
					}

					while ($ip_2_counter <= $ip_2_end)
					{
						$ip_3_counter = ($ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[3] : 0;
						$ip_3_end = ($ip_2_counter < $ip_2_end || $ip_1_counter < $ip_1_end) ? 254 : $ip_range_explode[7];

						if ($ip_3_counter == 0 && $ip_3_end == 254)
						{
							$ip_3_counter = 256;
							$ip_3_fragment = 256;

							$iplist[] = "'$ip_1_counter.$ip_2_counter.*'";
						}

						while ($ip_3_counter <= $ip_3_end)
						{
							$ip_4_counter = ($ip_3_counter == $ip_range_explode[3] && $ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[4] : 0;
							$ip_4_end = ($ip_3_counter < $ip_3_end || $ip_2_counter < $ip_2_end) ? 254 : $ip_range_explode[8];

							if ($ip_4_counter == 0 && $ip_4_end == 254)
							{
								$ip_4_counter = 256;
								$ip_4_fragment = 256;

								$iplist[] = "'$ip_1_counter.$ip_2_counter.$ip_3_counter.*'";
							}

							while ($ip_4_counter <= $ip_4_end)
							{
								$iplist[] = "'$ip_1_counter.$ip_2_counter.$ip_3_counter.$ip_4_counter'";
								$ip_4_counter++;
							}
							$ip_3_counter++;
						}
						$ip_2_counter++;
					}
					$ip_1_counter++;
				}
			}
			else if (preg_match('#^([0-9]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})$#', trim($item)) || preg_match('#^[a-f0-9:]+\*?$#i', trim($item)))
			{
				$iplist[] = "'" . trim($item) . "'";
			}
			else if (preg_match('#^([\w\-_]\.?){2,}$#is', trim($item)))
			{
				$hostlist[] = "'" . trim($item) . "'";
			}
			else if (preg_match("#^([a-z0-9\-\*\._/]+?)$#is", trim($item)))
			{
				$hostlist[] = "'" . trim($item) . "'";
			}
		}

		$sql = 'SELECT site_ip, site_hostname
			FROM ' . FORUMS_SITELIST_TABLE . "
			WHERE ip_exclude = $ip_exclude";
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$iplist_tmp = array();
			$hostlist_tmp = array();
			do
			{
				if ($row['site_ip'])
				{
					$iplist_tmp[] = "'" . $row['site_ip'] . "'";
				}
				else if ($row['site_hostname'])
				{
					$hostlist_tmp[] = "'" . $row['site_hostname'] . "'";
				}
				// break;
			}
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

			$iplist = array_unique(array_diff($iplist, $iplist_tmp));
			$hostlist = array_unique(array_diff($hostlist, $hostlist_tmp));
			unset($iplist_tmp);
			unset($hostlist_tmp);
		}
		$_CLASS['core_db']->free_result($result);

		if (sizeof($iplist))
		{
			foreach ($iplist as $ip_entry)
			{
				$sql = 'INSERT INTO ' . FORUMS_SITELIST_TABLE . " (site_ip, ip_exclude)
					VALUES ($ip_entry, $ip_exclude)";
				$_CLASS['core_db']->query($sql);
			}
		}

		if (sizeof($hostlist))
		{
			foreach ($hostlist as $host_entry)
			{
				$sql = 'INSERT INTO ' . FORUMS_SITELIST_TABLE . " (site_hostname, ip_exclude)
					VALUES ($host_entry, $ip_exclude)";
				$_CLASS['core_db']->query($sql);
			}
		}
		
		if (!empty($ip_list_log))
		{
			// Update log
			$log_entry = ($ip_exclude) ? 'LOG_DOWNLOAD_EXCLUDE_IP' : 'LOG_DOWNLOAD_IP';
			add_log('admin', $log_entry, $ip_list_log);
		}

		trigger_error($_CLASS['core_user']->lang['SECURE_DOWNLOAD_UPDATE_SUCCESS']);
	}
	else if (isset($_POST['unsecuresubmit']))
	{
		$unip_sql = array_map('intval', $_POST['unip']);

		if (sizeof($unip_sql))
		{
			$l_unip_list = '';

			// Grab details of ips for logging information later
			$sql = 'SELECT site_ip, site_hostname
				FROM ' . FORUMS_SITELIST_TABLE . '
				WHERE ' . $db->sql_in_set('site_id', $unip_sql);
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$l_unip_list .= (($l_unip_list != '') ? ', ' : '') . (($row['site_ip']) ? $row['site_ip'] : $row['site_hostname']);
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'DELETE FROM ' . SITELIST_TABLE . '
				WHERE ' . $db->sql_in_set('site_id', $unip_sql);
			$_CLASS['core_db']->query($sql);

			add_log('admin', 'LOG_DOWNLOAD_REMOVE_IP', $l_unip_list);
		}

		trigger_error($_CLASS['core_user']->lang['SECURE_DOWNLOAD_UPDATE_SUCCESS']);
	}
}

/**
* Re-Write extensions cache file
*/
function rewrite_extensions()
{
	global $_CLASS;

	$sql = 'SELECT e.extension, g.*
		FROM ' . FORUMS_EXTENSIONS_TABLE . ' e, ' . FORUMS_EXTENSION_GROUPS_TABLE . ' g
		WHERE e.group_id = g.group_id
			AND g.allow_group = 1';
	$result = $_CLASS['core_db']->query($sql);

	$extensions = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$extension = $row['extension'];

		$extensions[$extension]['display_cat']	= (int) $row['cat_id'];
		$extensions[$extension]['download_mode']= (int) $row['download_mode'];
		$extensions[$extension]['upload_icon']	= (string) $row['upload_icon'];
		$extensions[$extension]['max_filesize']	= (int) $row['max_filesize'];

		$allowed_forums = ($row['allowed_forums']) ? unserialize(trim($row['allowed_forums'])) : array();

		if ($row['allow_in_pm'])
		{
			$allowed_forums = array_merge($allowed_forums, array(0));
		}

		// Store allowed extensions forum wise
		$extensions['_allowed_'][$extension] = (!sizeof($allowed_forums)) ? 0 : $allowed_forums;
	}
	$_CLASS['core_db']->free_result($result);

	$_CLASS['core_cache']->destroy('extensions');
	$_CLASS['core_cache']->put('extensions', $extensions);
}

/**
* Write display_order config field
*/
function display_order($value, $key = '')
{
	$radio_ary = array(0 => 'DESCENDING', 1 => 'ASCENDING');

	return h_radio('config[display_order]', $radio_ary, $value, $key);
}

/**
* Adjust all three max_filesize config vars for display
*/
function max_filesize($value, $key = '')
{
	// Determine size var and adjust the value accordingly
	$size_var = ($value >= 1048576) ? 'mb' : (($value >= 1024) ? 'kb' : 'b');
	$value = ($value >= 1048576) ? round($value / 1048576 * 100) / 100 : (($value >= 1024) ? round($value / 1024 * 100) / 100 : $value);

	return '<input type="text" id="' . $key . '" size="8" maxlength="15" name="config[' . $key . ']" value="' . $value . '" /> <select name="' . $key . '">' . size_select_options($size_var) . '</select>';
}

/**
* Write secure_allow_deny config field
*/
function select_allow_deny($value, $key = '')
{
	$radio_ary = array(1 => 'ORDER_ALLOW_DENY', 0 => 'ORDER_DENY_ALLOW');

	return h_radio('config[' . $key . ']', $radio_ary, $value, $key);
}
