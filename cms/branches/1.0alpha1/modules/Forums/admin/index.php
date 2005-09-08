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

if (file_exists($site_file_root.'includes/forums/admin/'.$file.'.php'))
{
	require($site_file_root.'includes/forums/admin/'.$file.'.php');
}
else
{
	require($site_file_root.'includes/forums/admin/main.php');
}


// -----------------------------
// Functions
function adm_page_header($sub_title, $meta = '', $table_html = true)
{
	global $config, $db, $_CLASS;

	$_CLASS['core_display']->display_header();
	echo $_CLASS['core_display']->theme->table_open;

	if ($table_html)
	{

?>
<a name="top"></a>

<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
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
	}

	echo $_CLASS['core_display']->theme->table_close;
	$_CLASS['core_display']->display_footer();
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

?>