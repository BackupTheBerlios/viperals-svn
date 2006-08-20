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

// -------------------------------------------------------------
//
// $Id: pagestart.php,v 1.18 2004/08/02 14:31:45 psotfx Exp $
//
// FILENAME  : pagestart.php
// STARTED   : Thu Aug 2, 2001
// COPYRIGHT : 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

global $_CLASS, $_CORE_CONFIG;

// Some often used variables
$safe_mode	= (@ini_get('safe_mode') || @strtolower(ini_get('safe_mode')) == 'on') ? true : false;
$file_uploads = (@ini_get('file_uploads') || strtolower(@ini_get('file_uploads')) == 'on') ? true : false;

$data = array(
	'block_title'		=> 'Forum Administration',
	'block_position'	=> BLOCK_LEFT,
	'block_file'		=> 'block-Admin_Forums.php',
);

$_CLASS['core_blocks']->add_block($data);

load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
$_CLASS['auth'] =& $_CLASS['forums_auth'];

require_once(SITE_FILE_ROOT.'includes/forums/functions.php');
require_once(SITE_FILE_ROOT.'includes/forums/functions_admin.php');

$_CLASS['core_user']->add_lang('admin', 'forums');
//$_CLASS['core_user']->add_img(false, 'Forums');
$_CLASS['auth']->acl($_CLASS['core_user']->data);

$file = get_variable('file', 'REQUEST', 'main');

$_CLASS['core_template']->assign_array(array(
	'USERNAME'				=> $_CLASS['core_user']->data['username'],

	//'U_ADM_INDEX'			=> append_sid("{$phpbb_admin_path}index.$phpEx"),
	//'U_INDEX'				=> append_sid("index.$phpEx"),

	'T_IMAGES_PATH'			=> "images/",
	'T_SMILIES_PATH'		=> "{$config['smilies_path']}/",
	'T_AVATAR_PATH'			=> "{$config['avatar_path']}/",
	'T_AVATAR_GALLERY_PATH'	=> "{$config['avatar_gallery_path']}/",
	'T_ICONS_PATH'			=> "{$config['icons_path']}/",
	'T_RANKS_PATH'			=> "{$config['ranks_path']}/",
	'T_UPLOAD_PATH'			=> "{$config['upload_path']}/",

	'ICON_MOVE_UP'		=> '<img src="modules/forums/images/admin/icon_up.gif" alt="' . $_CLASS['core_user']->lang['MOVE_UP'] . '" title="' . $_CLASS['core_user']->lang['MOVE_UP'] . '" />',
	'ICON_MOVE_DOWN'	=> '<img src="modules/forums/images/admin/icon_down.gif" alt="' . $_CLASS['core_user']->lang['MOVE_DOWN'] . '" title="' . $_CLASS['core_user']->lang['MOVE_DOWN'] . '" />',
	'ICON_EDIT'			=> '<img src="modules/forums/images/admin/icon_edit.gif" alt="' . $_CLASS['core_user']->lang['EDIT'] . '" title="' . $_CLASS['core_user']->lang['EDIT'] . '" />',
	'ICON_DELETE'		=> '<img src="modules/forums/images/admin/icon_delete.gif" alt="' . $_CLASS['core_user']->lang['DELETE'] . '" title="' . $_CLASS['core_user']->lang['DELETE'] . '" />',
	'ICON_SYNC'			=> '<img src="modules/forums/images/admin/icon_sync.gif" alt="' . $_CLASS['core_user']->lang['RESYNC'] . '" title="' . $_CLASS['core_user']->lang['RESYNC'] . '" />',
));
	
if (file_exists(SITE_FILE_ROOT.'includes/forums/admin/'.$file.'.php'))
{
	require(SITE_FILE_ROOT.'includes/forums/admin/'.$file.'.php');
}
else
{
	require(SITE_FILE_ROOT.'includes/forums/admin/main.php');
}

/**
* Generate back link for acp pages
*/
function adm_back_link($u_action)
{
	global $_CLASS;

	return '<br /><br /><a href="' . $u_action . '">&laquo; ' . $_CLASS['core_user']->lang['BACK_TO_PREV'] . '</a>';
}

/**
* Build select field options in acp pages
*/
function build_select($option_ary, $option_default = false)
{
	global $_CLASS;

	$html = '';
	foreach ($option_ary as $value => $title)
	{
		$selected = ($option_default !== false && $value == $option_default) ? ' selected="selected"' : '';
		$html .= '<option value="' . $value . '"' . $selected . '>' . $_CLASS['core_user']->get_lang($title) . '</option>';
	}

	return $html;
}

/**
* Build radio fields in acp pages
*/
function h_radio($name, &$input_ary, $input_default = false, $id = false, $key = false)
{
	global $_CLASS;

	$html = '';
	$id_assigned = false;
	foreach ($input_ary as $value => $title)
	{
		$selected = ($input_default !== false && $value == $input_default) ? ' checked="checked"' : '';
		$html .= ($html) ? ' &nbsp; ' : '';
		$html .= '<input type="radio" name="' . $name . '"' . (($id && !$id_assigned) ? ' id="' . $id . '"' : '') . ' value="' . $value . '"' . $selected . (($key) ? ' accesskey="' . $key . '"' : '') . ' class="radio" /> ' . $_CLASS['core_user']->lang[$title];
		$id_assigned = true;
	}

	return $html;
}

/**
* Build configuration template for acp configuration pages
*/
function build_cfg_template($tpl_type, $key, &$new, $config_key, $vars)
{
	global $_CLASS;

	$tpl = '';
	$name = 'config[' . $config_key . ']';

	switch ($tpl_type[0])
	{
		case 'text':
		case 'password':
			$size = (int) $tpl_type[1];
			$maxlength = (int) $tpl_type[2];

			$tpl = '<input id="' . $key . '" type="' . $tpl_type[0] . '"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="' . $name . '" value="' . $new[$config_key] . '" />';
		break;

		case 'dimension':
			$size = (int) $tpl_type[1];
			$maxlength = (int) $tpl_type[2];

			$tpl = '<input id="' . $key . '" type="text"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="config[' . $config_key . '_height]" value="' . $new[$config_key . '_height'] . '" /> x <input type="text"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="config[' . $config_key . '_width]" value="' . $new[$config_key . '_width'] . '" />';
		break;

		case 'textarea':
			$rows = (int) $tpl_type[1];
			$cols = (int) $tpl_type[2];

			$tpl = '<textarea id="' . $key . '" name="' . $name . '" rows="' . $rows . '" cols="' . $cols . '">' . $new[$config_key] . '</textarea>';
		break;

		case 'radio':
			$key_yes	= ($new[$config_key]) ? ' checked="checked"' : '';
			$key_no		= (!$new[$config_key]) ? ' checked="checked"' : '';

			$tpl_type_cond = explode('_', $tpl_type[1]);
			$type_no = ($tpl_type_cond[0] == 'disabled' || $tpl_type_cond[0] == 'enabled') ? false : true;

			$tpl_no = '<input type="radio" name="' . $name . '" value="0"' . $key_no . ' class="radio" />&nbsp;' . (($type_no) ? $_CLASS['core_user']->lang['NO'] : $_CLASS['core_user']->lang['DISABLED']);
			$tpl_yes = '<input type="radio" id="' . $key . '" name="' . $name . '" value="1"' . $key_yes . ' class="radio" />&nbsp;' . (($type_no) ? $_CLASS['core_user']->lang['YES'] : $_CLASS['core_user']->lang['ENABLED']);

			$tpl = ($tpl_type_cond[0] == 'yes' || $tpl_type_cond[0] == 'enabled') ? $tpl_yes . '&nbsp;&nbsp;' . $tpl_no : $tpl_no . '&nbsp;&nbsp;' . $tpl_yes;
		break;

		case 'select':
		case 'custom':
			
			$return = '';

			if (isset($vars['method']))
			{
				$call = array($module->module, $vars['method']);
			}
			else if (isset($vars['function']))
			{
				$call = $vars['function'];
			}
			else
			{
				break;
			}

			if (isset($vars['params']))
			{
				$args = array();
				foreach ($vars['params'] as $value)
				{
					switch ($value)
					{
						case '{CONFIG_VALUE}':
							$value = $new[$config_key];
						break;

						case '{KEY}':
							$value = $key;
						break;
					}

					$args[] = $value;
				}
			}
			else
			{
				$args = array($new[$config_key], $key);
			}
			
			$return = call_user_func_array($call, $args);

			if ($tpl_type[0] == 'select')
			{
				$tpl = '<select id="' . $key . '" name="' . $name . '">' . $return . '</select>';
			}
			else
			{
				$tpl = $return;
			}

		break;

		default:
		break;
	}

	if (isset($vars['append']))
	{
		$tpl .= $vars['append'];
	}

	return $tpl;
}

?>