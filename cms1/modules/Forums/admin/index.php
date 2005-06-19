<?php
// -------------------------------------------------------------
//
// $Id: pagestart.php,v 1.18 2004/08/02 14:31:45 psotfx Exp $
//
// FILENAME  : pagestart.php
// STARTED   : Thu Aug 2, 2001
// COPYRIGHT : © 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	header('Location: ../../');
	die; 
}

// Some often used variables
$safe_mode	= (@ini_get('safe_mode') || @strtolower(ini_get('safe_mode')) == 'on') ? true : false;
$file_uploads = (@ini_get('file_uploads') || strtolower(@ini_get('file_uploads')) == 'on') ? true : false;

$data = array(
	'title' => 'Forum Administration',
	'position' => BLOCK_LEFT,
	'file' => 'block-Admin_Forums.php',
);

$_CLASS['core_blocks']->add_block($data);

load_class($site_file_root.'includes/forums/auth.php', 'auth');
require_once($site_file_root.'includes/forums/functions.php');
require_once($site_file_root.'includes/forums/functions_admin.php');

$_CLASS['core_user']->add_lang('admin', 'Forums');
//$_CLASS['core_user']->add_img(false, 'Forums');
$_CLASS['auth']->acl($_CLASS['core_user']->data);

$file = get_variable('file', 'REQUEST', 'main');

if (file_exists($site_file_root.'modules/'.$_CORE_MODULE['name'].'/admin/'.$file.'.php'))
{
	require($site_file_root.'modules/'.$_CORE_MODULE['name'].'/admin/'.$file.'.php');
}
else
{
	require($site_file_root.'modules/'.$_CORE_MODULE['name'].'/admin/main.php');
}


// -----------------------------
// Functions
function adm_page_header($sub_title, $meta = '', $table_html = true)
{
	global $config, $db, $_CLASS;

	$_CLASS['core_display']->display_head();
	$_CLASS['core_display']->table_open;

	if ($table_html)
	{

?>
<a name="top"></a>

<table width="95%" cellspacing="0" cellpadding="0" border="0" align="center">
	<tr>
		<td>

<?php

	}

}

function adm_page_footer($copyright_html = true)
{
	global $cache, $config, $_CLASS;

?>

		</td>
	</tr>
</table>
<?php

	if ($copyright_html)
	{

?>

<div class="copyright" align="center">Powered by phpBB <?php echo $config['version']; ?> &copy; 2002 <a href="http://www.phpbb.com/" target="_phpbb">phpBB Group</a></div>

<br clear="all" />
<?php
	$_CLASS['core_display']->table_close;
	$_CLASS['core_display']->display_footer();

	}
}

function adm_page_message($title, $message, $show_header = false, $show_prev_info = true)
{
	global $_CLASS, $_SERVER, $_ENV;

?>
<br /><br />

<table class="tablebg" width="80%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th><?php echo $title; ?></th>
	</tr>
	<tr>
		<td class="row1" align="center"><?php echo $message; ?></td>
	</tr>
</table>

<br />

<?php

}

function adm_page_confirm($title, $message)
{
	global $_CLASS;

	// Grab data from GET and POST arrays ... note this is _not_
	// validated! Everything is typed as string to ensure no
	// funny business on displayed hidden field data. Validation
	// will be carried out by whatever processes this form.
	$var_ary = array_merge($_GET, $_POST);

	$s_hidden_fields = '';
	foreach ($var_ary as $key => $var)
	{
		if (empty($var))
		{
			continue;
		}

		if (is_array($var))
		{
			foreach ($var as $k => $v)
			{
				if (is_array($v))
				{
					foreach ($v as $_k => $_v)
					{
						set_var($var[$k][$_k], $_v, 'string');
						$s_hidden_fields .= "<input type=\"hidden\" name=\"${key}[$k][$_k]\" value=\"" . addslashes($_v) . '" />';
					}
				}
				else
				{
					set_var($var[$k], $v, 'string');
					$s_hidden_fields .= "<input type=\"hidden\" name=\"${key}[$k]\" value=\"" . addslashes($v) . '" />';
				}
			}
		}
		else
		{
			set_var($var, $var, 'string');
			$s_hidden_fields .= '<input type="hidden" name="' . $key . '" value="' . addslashes($var) . '" />';
		}
		unset($var_ary[$key]);
	}

?>

<br /><br />

<form name="confirm" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
<table class="tablebg" width="80%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th><?php echo $title; ?></th>
	</tr>
	<tr>
		<td class="row1" align="center"><?php echo $message; ?><br /><br /><input class="btnlite" type="submit" name="confirm" value="<?php echo $_CLASS['core_user']->lang['YES']; ?>" />&nbsp;&nbsp;<input class="btnmain" type="submit" name="cancel" value="<?php echo $_CLASS['core_user']->lang['NO']; ?>" /></td>
	</tr>
</table>

<?php echo $s_hidden_fields; ?>
</form>

<br />

<?php

	adm_page_footer();

}

function build_cfg_template($tpl_type, $config_key, $options = '')
{
	global $new, $_CLASS;

	$tpl = '';
	$name = 'config[' . $config_key . ']';

	switch ($tpl_type[0])
	{
		case 'text':
		case 'password':
			$size = (int) $tpl_type[1];
			$maxlength = (int) $tpl_type[2];

			$tpl = '<input class="post" type="' . $tpl_type[0] . '"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="' . $name . '" value="' . $new[$config_key] . '" />';
			break;

		case 'dimension':
			$size = (int) $tpl_type[1];
			$maxlength = (int) $tpl_type[2];

			$tpl = '<input class="post" type="text"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="config[' . $config_key . '_height]" value="' . $new[$config_key . '_height'] . '" /> x <input class="post" type="text"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="config[' . $config_key . '_width]" value="' . $new[$config_key . '_width'] . '" />';
			break;

		case 'textarea':
			$rows = (int) $tpl_type[1];
			$cols = (int) $tpl_type[2];

			$tpl = '<textarea name="' . $name . '" rows="' . $rows . '" cols="' . $cols . '">' . $new[$config_key] . '</textarea>';
			break;

		case 'radio':
			$key_yes	= ($new[$config_key]) ? ' checked="checked"' : '';
			$key_no		= (!$new[$config_key]) ? ' checked="checked"' : '';

			$tpl_type_cond = explode('_', $tpl_type[1]);
			$type_no = ($tpl_type_cond[0] == 'disabled' || $tpl_type_cond[0] == 'enabled') ? false : true;

			$tpl_no = '<input type="radio" name="' . $name . '" value="0"' . $key_no . ' />' . (($type_no) ? $_CLASS['core_user']->lang['NO'] : $_CLASS['core_user']->lang['DISABLED']);
			$tpl_yes = '<input type="radio" name="' . $name . '" value="1"' . $key_yes . ' />' . (($type_no) ? $_CLASS['core_user']->lang['YES'] : $_CLASS['core_user']->lang['ENABLED']);

			$tpl = ($tpl_type_cond[0] == 'yes' || $tpl_type_cond[0] == 'enabled') ? $tpl_yes . '&nbsp;&nbsp;' . $tpl_no : $tpl_no . '&nbsp;&nbsp;' . $tpl_yes;
			break;

		case 'select':
			eval('$s_options = ' . str_replace('{VALUE}', $new[$config_key], $options) . ';');
			$tpl = '<select name="' . $name . '">' . $s_options . '</select>';
			break;

		case 'custom':
			eval('$tpl = ' . str_replace('{VALUE}', $new[$config_key], $options) . ';');
			break;

		default:
			break;
	}

	return $tpl;
}

// General ACP module class
class module
{
	var $id = 0;
	var $type;
	var $name;
	var $mode;

	// Private methods, should not be overwritten
	function create($module_type, $module_url, $selected_mod = false, $selected_submod = false)
	{
		global $template, $_CLASS, $db, $config;

		$sql = 'SELECT module_id, module_title, module_filename, module_subs, module_acl
			FROM ' . MODULES_TABLE . "
			WHERE module_type = 'acp'
				AND module_enabled = 1
			ORDER BY module_order ASC";
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			// Authorisation is required for the basic module
			if ($row['module_acl'])
			{
				$is_auth = false;

				eval('$is_auth = (' . preg_replace(array('#acl_([a-z_]+)#e', '#cfg_([a-z_]+)#e'), array('$_CLASS[\'auth\']->acl_get("\\1")', '$config["\\1"]'), $row['module_acl']) . ');');

				// The user is not authorised to use this module, skip it
				if (!$is_auth)
				{
					continue;
				}
			}

			$selected = ($row['module_filename'] == $selected_mod || $row['module_id'] == $selected_mod || (!$selected_mod && !$i)) ?  true : false;
/*
			// Get the localised lang string if available, or make up our own otherwise
			$template->assign_block_vars($module_type . '_section', array(
				'L_TITLE'		=> (isset($_CLASS['core_user']->lang[strtoupper($module_type) . '_' . $row['module_title']])) ? $_CLASS['core_user']->lang[strtoupper($module_type) . '_' . $row['module_title']] : ucfirst(str_replace('_', ' ', strtolower($row['module_title']))),
				'S_SELECTED'	=> $selected,
				'U_TITLE'		=> $module_url . '&amp;i=' . $row['module_id'])
			);
*/
			if ($selected)
			{
				$module_id = $row['module_id'];
				$module_name = $row['module_filename'];

				if ($row['module_subs'])
				{
					$j = 0;
					$submodules_ary = explode("\n", $row['module_subs']);
					foreach ($submodules_ary as $submodule)
					{
						$submodule = explode(',', trim($submodule));
						$submodule_title = array_shift($submodule);

						$is_auth = true;
						foreach ($submodule as $auth_option)
						{
							if (!$_CLASS['auth']->acl_get($auth_option))
							{
								$is_auth = false;
							}
						}

						if (!$is_auth)
						{
							continue;
						}

						$selected = ($submodule_title == $selected_submod || (!$selected_submod && !$j)) ? true : false;
/*
						// Get the localised lang string if available, or make up our own otherwise
						$template->assign_block_vars("{$module_type}_section.{$module_type}_subsection", array(
							'L_TITLE'		=> (isset($_CLASS['core_user']->lang[strtoupper($module_type) . '_' . strtoupper($submodule_title)])) ? $_CLASS['core_user']->lang[strtoupper($module_type) . '_' . strtoupper($submodule_title)] : ucfirst(str_replace('_', ' ', strtolower($submodule_title))),
							'S_SELECTED'	=> $selected,
							'U_TITLE'		=> $module_url . '&amp;i=' . $module_id . '&amp;mode=' . $submodule_title
						));
*/
						if ($selected)
						{
							$this->mode = $submodule_title;
						}

						$j++;
					}
				}
			}

			$i++;
		}
		$db->sql_freeresult($result);

		if (!$module_id)
		{
			trigger_error('MODULE_NOT_EXIST');
		}

		$this->type = $module_type;
		$this->id = $module_id;
		$this->name = $module_name;
	}

	// Public methods to be overwritten by modules
	function module()
	{
		// Module name
		// Module filename
		// Module description
		// Module version
		// Module compatibility
		return false;
	}

	function init()
	{
		return false;
	}

	function install()
	{
		return false;
	}

	function uninstall()
	{
		return false;
	}
}
// End Functions
// -----------------------------

?>