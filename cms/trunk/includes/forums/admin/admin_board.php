<?php
// -------------------------------------------------------------
//
// $Id: admin_board.php,v 1.46 2004/06/06 21:44:46 acydburn Exp $
//
// FILENAME  : admin_board.php
// STARTED   : Thu Jul 12, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Get mode
$mode	= request_var('mode', '');
$action	= request_var('action', '');
$submit = isset($_POST['submit']);

// Set config vars
$display_vars = array(
	'avatar' => array(
		'auth'	=> 'a_board',
		'title'	=> 'AVATAR_SETTINGS',
		'vars'	=> array(
			'avatar_min_height' => false, 'avatar_min_width' => false, 'avatar_max_height' => false, 'avatar_max_width' => false,
			'allow_avatar_local'	=> array('lang' => 'ALLOW_LOCAL',	'type' => 'radio:yes_no', 'explain' => false),
			'allow_avatar_remote'	=> array('lang' => 'ALLOW_REMOTE',	'type' => 'radio:yes_no', 'explain' => true),
			'allow_avatar_upload'	=> array('lang' => 'ALLOW_UPLOAD',	'type' => 'radio:yes_no', 'explain' => false),
			'avatar_filesize'		=> array('lang' => 'MAX_FILESIZE',	'type' => 'text:4:10', 'explain' => true, 'append' => ' ' . $_CLASS['core_user']->lang['BYTES']),
			'avatar_min'			=> array('lang' => 'MIN_AVATAR_SIZE',	'type' => 'dimension:3:4', 'explain' => true),
			'avatar_max'			=> array('lang' => 'MAX_AVATAR_SIZE',	'type' => 'dimension:3:4', 'explain' => true),
			'avatar_path'			=> array('lang' => 'AVATAR_STORAGE_PATH',	'type' => 'text:20:255', 'explain' => true),
			'avatar_gallery_path'	=> array('lang' => 'AVATAR_GALLERY_PATH',	'type' => 'text:20:255', 'explain' => true)
		)
	),
	'email'	=> array(
		'auth'	=> 'a_server',
		'title'	=> 'EMAIL_SETTINGS',
		'vars'	=> array(
			'email_enable'			=> array('lang' => 'ENABLE_EMAIL',			'type' => 'radio:enabled_disabled', 'explain' => true),
			'board_email_form'		=> array('lang' => 'BOARD_EMAIL_FORM',		'type' => 'radio:enabled_disabled', 'explain' => true),
			'email_function_name'	=> array('lang' => 'EMAIL_FUNCTION_NAME',	'type' => 'text:20:50', 'explain' => true),
			'email_package_size'	=> array('lang' => 'EMAIL_PACKAGE_SIZE',	'type' => 'text:5:5', 'explain' => true),
			'board_contact'			=> array('lang' => 'CONTACT_EMAIL',			'type' => 'text:25:100', 'explain' => true),
			'board_email'			=> array('lang' => 'ADMIN_EMAIL',			'type' => 'text:25:100', 'explain' => true),
			'board_email_sig'		=> array('lang' => 'EMAIL_SIG',				'type' => 'textarea:5:30', 'explain' => true),
			'smtp_delivery'			=> array('lang' => 'USE_SMTP',				'type' => 'radio:yes_no', 'explain' => true),
			'smtp_host'				=> array('lang' => 'SMTP_SERVER',			'type' => 'text:25:50', 'explain' => false),
			'smtp_port'				=> array('lang' => 'SMTP_PORT',				'type' => 'text:4:5', 'explain' => true),
			'smtp_auth_method'		=> array('lang' => 'SMTP_AUTH_METHOD',		'type' => 'select', 'options' => 'mail_auth_select(\'{VALUE}\')', 'explain' => true),
			'smtp_username'			=> array('lang' => 'SMTP_USERNAME',			'type' => 'text:25:255', 'explain' => true),
			'smtp_password'			=> array('lang' => 'SMTP_PASSWORD',			'type' => 'password:25:255', 'explain' => true)
		)
	),
	'load'	=> array(
		'auth'	=> 'a_server',
		'title'	=> 'SERVER_SETTINGS',
		'vars'	=> array(
			'load_db_track'		=> array('lang' => 'YES_POST_MARKING',	'type' => 'radio:yes_no', 'explain' => true),
			'load_db_lastread'	=> array('lang' => 'YES_READ_MARKING',	'type' => 'radio:yes_no', 'explain' => true),
			'load_online'		=> array('lang' => 'YES_ONLINE',		'type' => 'radio:yes_no', 'explain' => true),
			'load_onlinetrack'	=> array('lang' => 'YES_ONLINE_TRACK',	'type' => 'radio:yes_no', 'explain' => true),
			'load_online_time'	=> array('lang' => 'ONLINE_LENGTH',		'type' => 'text:4:3', 'explain' => true),
			'load_birthdays'	=> array('lang' => 'YES_BIRTHDAYS',		'type' => 'radio:yes_no', 'explain' => false),
			'load_moderators'	=> array('lang' => 'YES_MODERATORS',	'type' => 'radio:yes_no', 'explain' => false),
			'load_jumpbox'		=> array('lang' => 'YES_JUMPBOX',		'type' => 'radio:yes_no', 'explain' => false),
			'load_search'		=> array('lang' => 'YES_SEARCH',		'type' => 'radio:yes_no', 'explain' => true),
			'search_interval'	=> array('lang' => 'SEARCH_INTERVAL',	'type' => 'text:3:4', 'explain' => true),
			'min_search_chars'	=> array('lang' => 'MIN_SEARCH_CHARS',	'type' => 'text:3:3', 'explain' => true),
			'max_search_chars'	=> array('lang' => 'MAX_SEARCH_CHARS',	'type' => 'text:3:3', 'explain' => true),
			'load_search_upd'	=> array('lang' => 'YES_SEARCH_UPDATE',	'type' => 'radio:yes_no', 'explain' => true),
//			'load_search_phr'	=> array('lang' => 'YES_SEARCH_PHRASE',	'type' => 'radio:yes_no', 'explain' => true),
		)
	),
	'default' => array(
		'auth'	=> 'a_defaults',
		'title'	=> 'BOARD_DEFAULTS',
		'vars'	=> array(
			'allow_privmsg'			=> array('lang' => 'BOARD_PM',				'type' => 'radio:yes_no', 'explain' => true),
			'allow_topic_notify'	=> array('lang' => 'ALLOW_TOPIC_NOTIFY',	'type' => 'radio:yes_no', 'explain' => false),
			'allow_forum_notify'	=> array('lang' => 'ALLOW_FORUM_NOTIFY',	'type' => 'radio:yes_no', 'explain' => false),
			'allow_attachments'		=> array('lang' => 'ALLOW_ATTACHMENTS',		'type' => 'radio:yes_no', 'explain' => false),
			'allow_html'			=> array('lang' => 'ALLOW_HTML',			'type' => 'radio:yes_no', 'explain' => false),
			'allow_html_tags'		=> array('lang' => 'ALLOWED_TAGS',			'type' => 'text:30:255', 'explain' => true),
			'allow_bbcode'			=> array('lang' => 'ALLOW_BBCODE',			'type' => 'radio:yes_no', 'explain' => false),
			'allow_smilies'			=> array('lang' => 'ALLOW_SMILIES',			'type' => 'radio:yes_no', 'explain' => false),
			'allow_sig'				=> array('lang' => 'ALLOW_SIG',				'type' => 'radio:yes_no', 'explain' => false),
			'max_sig_chars'			=> array('lang' => 'MAX_SIG_LENGTH',		'type' => 'text:5:4', 'explain' => true),
			'allow_nocensors'		=> array('lang' => 'ALLOW_NO_CENSORS',		'type' => 'radio:yes_no', 'explain' => true),
			'allow_bookmarks'		=> array('lang' => 'ALLOW_BOOKMARKS',		'type' => 'radio:yes_no', 'explain' => true)
		)
	),
	'message' => array(
		'auth'	=> 'a_defaults',
		'title'	=> 'MESSAGE_SETTINGS',
		'lang'	=> 'ucp',
		'vars'	=> array(
			'pm_max_boxes'			=> array('lang' => 'BOXES_MAX',				'type' => 'text:4:4', 'explain' => true),
			'pm_max_msgs'			=> array('lang' => 'BOXES_LIMIT',			'type' => 'text:4:4', 'explain' => true),
			'full_folder_action'	=> array('lang' => 'FULL_FOLDER_ACTION',	'type' => 'select', 'options' => 'full_folder_select(\'{VALUE}\')', 'explain' => true),
			'pm_edit_time'			=> array('lang' => 'PM_EDIT_TIME',			'type' => 'text:3:3', 'explain' => true),
			'allow_mass_pm'			=> array('lang' => 'ALLOW_MASS_PM',			'type' => 'radio:yes_no', 'explain' => false),
			'auth_html_pm'			=> array('lang' => 'ALLOW_HTML_PM',			'type' => 'radio:yes_no', 'explain' => false),
			'auth_bbcode_pm'		=> array('lang' => 'ALLOW_BBCODE_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'auth_smilies_pm'		=> array('lang' => 'ALLOW_SMILIES_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'allow_pm_attach'		=> array('lang' => 'ALLOW_PM_ATTACHMENTS',	'type' => 'radio:yes_no', 'explain' => false),
			'auth_download_pm'		=> array('lang' => 'ALLOW_DOWNLOAD_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'allow_sig_pm'			=> array('lang' => 'ALLOW_SIG_PM',			'type' => 'radio:yes_no', 'explain' => false),
			'auth_report_pm'		=> array('lang' => 'ALLOW_REPORT_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'auth_quote_pm'			=> array('lang' => 'ALLOW_QUOTE_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'print_pm'				=> array('lang' => 'ALLOW_PRINT_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'email_pm'				=> array('lang' => 'ALLOW_EMAIL_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'forward_pm'			=> array('lang' => 'ALLOW_FORWARD_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'auth_img_pm'			=> array('lang' => 'ALLOW_IMG_PM',			'type' => 'radio:yes_no', 'explain' => false),
			'auth_flash_pm'			=> array('lang' => 'ALLOW_FLASH_PM',		'type' => 'radio:yes_no', 'explain' => false),
			'enable_pm_icons'		=> array('lang' => 'ENABLE_PM_ICONS',		'type' => 'radio:yes_no', 'explain' => false)
		)
	),
	'server' => array(
		'auth'	=> 'a_server',
		'title'	=> 'SERVER_SETTINGS',
		'vars'	=> array(
			'smilies_path'		=> array('lang' => 'SMILIES_PATH',	'type' => 'text:20:255', 'explain' => true),
			'icons_path'		=> array('lang' => 'ICONS_PATH',	'type' => 'text:20:255', 'explain' => true),
			'upload_icons_path'	=> array('lang' => 'UPLOAD_ICONS_PATH', 'type' => 'text:20:255', 'explain' => true),
			'ranks_path'		=> array('lang' => 'RANKS_PATH',	'type' => 'text:20:255', 'explain' => true)
		)
	),
	'setting' => array(
		'auth'	=> 'a_board',
		'title'	=> 'BOARD_SETTINGS',
		'vars'	=> array(
			'board_disable_msg'	=> false, 'max_name_chars' => false, 'max_pass_chars' => false, 'bump_type' => false,
			'edit_time'			=> array('lang' => 'EDIT_TIME',			'type' => 'text:3:3', 'explain' => true),
			'display_last_edited' => array('lang' => 'DISPLAY_LAST_EDITED', 'type' => 'radio:yes_no', 'explain' => true),
			'flood_interval'	=> array('lang' => 'FLOOD_INTERVAL',	'type' => 'text:3:4', 'explain' => true),
			'bump_interval'		=> array('lang' => 'BUMP_INTERVAL',		'type' => 'custom', 'options' => 'bump_interval(\'{VALUE}\')', 'explain' => true),
			'topics_per_page'	=> array('lang' => 'TOPICS_PER_PAGE',	'type' => 'text:3:4', 'explain' => false),
			'posts_per_page'	=> array('lang' => 'POSTS_PER_PAGE',	'type' => 'text:3:4', 'explain' => false),
			'hot_threshold'		=> array('lang' => 'HOT_THRESHOLD',		'type' => 'text:3:4', 'explain' => false),
			'max_poll_options'	=> array('lang' => 'MAX_POLL_OPTIONS',	'type' => 'text:4:4', 'explain' => false),
			'max_post_chars'	=> array('lang' => 'CHAR_LIMIT',		'type' => 'text:4:6', 'explain' => true),
			'max_post_smilies'	=> array('lang' => 'SMILIES_LIMIT',		'type' => 'text:4:4', 'explain' => true),
			'max_quote_depth'	=> array('lang' => 'QUOTE_DEPTH_LIMIT',	'type' => 'text:4:4', 'explain' => true)
		)
	)
);

if (!in_array($mode, array_keys($display_vars)))
{
	return;
}

// Perform the current mode
$display_vars = $display_vars[$mode];

// Check permissions
if (!$_CLASS['auth']->acl_get($display_vars['auth']))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

if (isset($display_vars['lang']))
{
	$_CLASS['core_user']->add_lang($display_vars['lang'], 'forums');
}

$new = $config;
$cfg_array = (isset($_REQUEST['config'])) ? request_var('config', array('' => '')) : $new;

// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
foreach ($display_vars['vars'] as $config_name => $null)
{
	if (!isset($cfg_array[$config_name]))
	{
		continue;
	}

	$config_value = $cfg_array[$config_name];
	$new[$config_name] = $config_value;

	if ($config_name == 'email_function_name')
	{
		$new['email_function_name'] = (empty($new['email_function_name']) || !function_exists($new['email_function_name'])) ? 'mail' : str_replace(array('(', ')'), array('', ''), trim($new['email_function_name']));
	}
	
	if ($submit)
	{
		set_config($config_name, $config_value);
	}
}

if ($submit)
{
	add_log('admin', 'LOG_' . strtoupper($mode) . '_CONFIG');

	trigger_error($_CLASS['core_user']->lang['CONFIG_UPDATED']);
}

adm_page_header($_CLASS['core_user']->lang[$display_vars['title']]);

?>

<h1><?php echo $_CLASS['core_user']->lang[$display_vars['title']]; ?></h1>

<p><?php echo $_CLASS['core_user']->lang[$display_vars['title'] . '_EXPLAIN']; ?></p>

<form action="<?php echo generate_link('forums&amp;file=admin_board&amp;mode='.$mode, array('admin' => true)); ?>" method="post"><table class="tablebg" width="95%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang[$display_vars['title']]; ?></th>
	</tr>
<?php

// Output relevant page
foreach ($display_vars['vars'] as $config_key => $vars)
{
	if (!is_array($vars))
	{
		continue;
	}

	$type = explode(':', $vars['type']);

?>

	<tr>
		<td class="row1" width="50%"><b><?php echo $_CLASS['core_user']->lang[$vars['lang']]; ?>: </b><?php echo ($vars['explain']) ? '<br /><span class="gensmall">' . $_CLASS['core_user']->lang[$vars['lang'] . '_EXPLAIN'] . '</span>' : ''; ?></td>
		<td class="row2"><?php echo build_cfg_template($type, $config_key, ((isset($vars['options'])) ? $vars['options'] : '')) . ((isset($vars['append'])) ? str_replace('{VALUE}', $new[$config_key], $vars['append']) : ''); ?></td>
	</tr>

<?php
	
	unset($display_vars['vars'][$config_key]);
}

if ($mode == 'auth')
{
	$auth_plugins = array();

	$dp = opendir($site_file_root.'includes/auth');
	while ($file = readdir($dp))
	{
		if (preg_match('#^auth_(.*?)\.php$#', $file))
		{
			$auth_plugins[] = preg_replace('#^auth_(.*?)\.php$#', '\1', $file);
		}
	}

	sort($auth_plugins);

	foreach ($auth_plugins as $method)
	{
		if ($method && file_exists($site_file_root.'includes/auth/auth_' . $method . '.php'))
		{
			include_once($site_file_root.'includes/auth/auth_' . $method . '.php');

			$method = 'admin_' . $method;
			if (function_exists($method))
			{
				if ($config_fields = $method($new))
				{
					// Check if we need to create config fields for this plugin
					foreach($config_fields as $field)
					{
						if (!isset($config[$field]))
						{
							set_config($field, '');
						}
					}
				}
				unset($config_fields);
			}
		}
	}
}

?>
	<tr>
		<td class="cat" colspan="2" align="center"><input type="submit" name="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" class="btnmain" />&nbsp;&nbsp;<input type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" class="btnlite" /></td>
	</tr>
</table></form>

<?php

adm_page_footer();

function select_auth_method($selected_method)
{
	global $new, $site_file_root;

	$auth_plugins = array();

	$dp = opendir($site_file_root.'includes/auth');
	while ($file = readdir($dp))
	{
		if (preg_match('#^auth_(.*?)\.php$#', $file))
		{
			$auth_plugins[] = preg_replace('#^auth_(.*?)\.php$#', '\1', $file);
		}
	}

	sort($auth_plugins);

	$auth_select = '';
	foreach ($auth_plugins as $method)
	{
		$selected = ($selected_method == $method) ? ' selected="selected"' : '';
		$auth_select .= '<option value="' . $method . '"' . $selected . '>' . ucfirst($method) . '</option>';
	}

	return $auth_select;
}

function mail_auth_select($selected_method)
{
	global $_CLASS;

	$auth_methods = array('PLAIN', 'LOGIN', 'CRAM-MD5', 'DIGEST-MD5', 'POP-BEFORE-SMTP');
	$s_smtp_auth_options = '';

	foreach ($auth_methods as $method)
	{
		$s_smtp_auth_options .= '<option value="' . $method . '"' . (($selected_method == $method) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['SMTP_' . str_replace('-', '_', $method)] . '</option>';
	}

	return $s_smtp_auth_options;
}

function full_folder_select($value)
{
	global $_CLASS;

	return '<option value="1"' . (($value == 1) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['DELETE_OLDEST_MESSAGES'] . '</option><option value="2"' . (($value == 2) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['HOLD_NEW_MESSAGES'] . '</option>';
}

function select_ip_check($value)
{
	global $_CLASS;

	$ip_all = ($value == 4) ? ' checked="checked"' : '';
	$ip_classc = ($value == 3) ? ' checked="checked"' : '';
	$ip_classb = ($value == 2) ? ' checked="checked"' : '';
	$ip_none = ($value == 0) ? ' checked="checked"' : '';

	$options = <<<EOT
	<input type="radio" name="config[ip_check]" value="4"$ip_all /> {$_CLASS['core_user']->lang['ALL']}&nbsp;&nbsp;<input type="radio" name="config[ip_check]" value="3"$ip_classc /> {$_CLASS['core_user']->lang['CLASS_C']}&nbsp;&nbsp;<input type="radio" name="config[ip_check]" value="2"$ip_classb /> {$_CLASS['core_user']->lang['CLASS_B']}&nbsp;&nbsp;<input type="radio" name="config[ip_check]" value="0"$ip_none /> {$_CLASS['core_user']->lang['NONE']}&nbsp;&nbsp;
EOT;
	
	return $options;
}

function select_acc_activation($value)
{
	global $_CLASS, $config;

	$activation_none	= ($value == USER_ACTIVATION_NONE) ? ' checked="checked"' : '';
	$activation_user	= ($value == USER_ACTIVATION_SELF) ? ' checked="checked"' : '';
	$activation_admin	= ($value == USER_ACTIVATION_ADMIN) ? ' checked="checked"' : '';
	$activation_disable = ($value == USER_ACTIVATION_DISABLE) ? ' checked="checked"' : '';

	$options = '<input type="radio" name="config[require_activation]" value="' . USER_ACTIVATION_NONE . '"' . $activation_none . ' /> ' . $_CLASS['core_user']->lang['ACC_NONE'];

	if ($config['email_enable'])
	{
		$options .= '&nbsp;&nbsp;<input type="radio" name="config[require_activation]" value="' . USER_ACTIVATION_SELF . '"' . $activation_user . ' /> ' . $_CLASS['core_user']->lang['ACC_USER'];
		$options .= '&nbsp;&nbsp;<input type="radio" name="config[require_activation]" value="' . USER_ACTIVATION_ADMIN . '"' . $activation_admin . ' /> ' . $_CLASS['core_user']->lang['ACC_ADMIN'];
	}
	$options .= '&nbsp;&nbsp;<input type="radio" name="config[require_activation]" value="' . USER_ACTIVATION_DISABLE . '"' . $activation_disable . ' /> ' . $_CLASS['core_user']->lang['ACC_DISABLE'];

	return $options;
}

function username_length($value)
{
	global $new, $_CLASS;

	return '<input class="post" type="text" size="3" maxlength="3" name="config[min_name_chars]" value="' . $value . '" /> ' . $_CLASS['core_user']->lang['MIN_CHARS'] . '&nbsp;&nbsp;<input class="post" type="text" size="3" maxlength="3" name="config[max_name_chars]" value="' . $new['max_name_chars'] . '" /> ' . $_CLASS['core_user']->lang['MAX_CHARS'];
}

function select_username_chars($selected_value)
{
	global $_CLASS;

	$user_char_ary = array('USERNAME_CHARS_ANY' => '.*', 'USERNAME_ALPHA_ONLY' => '[\w]+', 'USERNAME_ALPHA_SPACERS' => '[\w_\+\. \-\[\]]+');
	$user_char_options = '';
	foreach ($user_char_ary as $lang => $value)
	{
		$selected = ($selected_value == $value) ? ' selected="selected"' : '';
		$user_char_options .= '<option value="' . $value . '"' . $selected . '>' . $_CLASS['core_user']->lang[$lang] . '</option>';
	}

	return $user_char_options;
}

function password_length($value)
{
	global $new, $_CLASS;

	return '<input class="post" type="text" size="3" maxlength="3" name="config[min_pass_chars]" value="' . $value . '" /> ' . $_CLASS['core_user']->lang['MIN_CHARS'] . '&nbsp;&nbsp;<input class="post" type="text" size="3" maxlength="3" name="config[max_pass_chars]" value="' . $new['max_pass_chars'] . '" /> ' . $_CLASS['core_user']->lang['MAX_CHARS'];
}

function select_password_chars($selected_value)
{
	global $_CLASS;

	$pass_type_ary = array('PASS_TYPE_ANY' => '.*', 'PASS_TYPE_CASE' => '[a-zA-Z]', 'PASS_TYPE_ALPHA' => '[a-zA-Z0-9]', 'PASS_TYPE_SYMBOL' => '[a-zA-Z\W]'); 
	$pass_char_options = '';
	foreach ($pass_type_ary as $lang => $value)
	{
		$selected = ($selected_value == $value) ? ' selected="selected"' : '';
		$pass_char_options .= '<option value="' . $value . '"' . $selected . '>' . $_CLASS['core_user']->lang[$lang] . '</option>';
	}

	return $pass_char_options;
}

function bump_interval($value)
{
	global $new, $_CLASS;

	$s_bump_type = '';
	$types = array('m' => 'MINUTES', 'h' => 'HOURS', 'd' => 'DAYS');
	foreach ($types as $type => $lang)
	{
		$selected = ($new['bump_type'] == $type) ? 'selected="selected" ' : '';
		$s_bump_type .= '<option value="' . $type . '" ' . $selected . '>' . $_CLASS['core_user']->lang[$lang] . '</option>';
	}

	return '<input class="post" type="text" size="3" maxlength="4" name="config[bump_interval]" value="' . $value . '" />&nbsp;<select name="config[bump_type]">' . $s_bump_type . '</select>';
}

function board_disable($value)
{
	global $new, $_CLASS;

	$board_disable_yes = ($value) ? ' checked="checked"' : '';
	$board_disable_no = (!$value) ? ' checked="checked"' : '';

	return '<input type="radio" name="config[board_disable]" value="1"' . $board_disable_yes . ' /> ' . $_CLASS['core_user']->lang['YES'] . '&nbsp;&nbsp;<input type="radio" name="config[board_disable]" value="0"' . $board_disable_no . ' /> ' . $_CLASS['core_user']->lang['NO'] . '<br /><input class="post" type="text" name="config[board_disable_msg]" maxlength="255" size="40" value="' . $new['board_disable_msg'] . '" />';
}

?>