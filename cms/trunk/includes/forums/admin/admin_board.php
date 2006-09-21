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

$_CLASS['core_user']->add_lang('admin_board', 'forums');

$action	= request_var('action', '');
$mode	= request_var('mode', '');
$submit = (isset($_POST['submit'])) ? true : false;
$u_action = 'forums&amp;file=admin_board&amp;mode='.$mode;

$new_config = array();

// Validation types are: string, int, bool, rpath, path
switch ($mode)
{
	case 'features':
		$display_vars = array(
			'title'	=> 'ACP_BOARD_FEATURES',
			'vars'	=> array(
				'legend1'				=> 'ACP_BOARD_FEATURES',
				'allow_topic_notify'	=> array('lang' => 'ALLOW_TOPIC_NOTIFY',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_forum_notify'	=> array('lang' => 'ALLOW_FORUM_NOTIFY',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_attachments'		=> array('lang' => 'ALLOW_ATTACHMENTS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_pm_attach'		=> array('lang' => 'ALLOW_PM_ATTACHMENTS',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_bbcode'			=> array('lang' => 'ALLOW_BBCODE',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_smilies'			=> array('lang' => 'ALLOW_SMILIES',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_sig'				=> array('lang' => 'ALLOW_SIG',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_nocensors'		=> array('lang' => 'ALLOW_NO_CENSORS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'allow_bookmarks'		=> array('lang' => 'ALLOW_BOOKMARKS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),

				'legend2'				=> 'ACP_LOAD_SETTINGS',
				'load_birthdays'		=> array('lang' => 'YES_BIRTHDAYS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'load_moderators'		=> array('lang' => 'YES_MODERATORS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'load_jumpbox'			=> array('lang' => 'YES_JUMPBOX',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
			)
		);
	break;

	case 'post':
		$display_vars = array(
			'title'	=> 'ACP_POST_SETTINGS',
			'vars'	=> array(
				'legend1'				=> 'GENERAL_OPTIONS',
				'allow_topic_notify'	=> array('lang' => 'ALLOW_TOPIC_NOTIFY',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_forum_notify'	=> array('lang' => 'ALLOW_FORUM_NOTIFY',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_bbcode'			=> array('lang' => 'ALLOW_BBCODE',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_smilies'			=> array('lang' => 'ALLOW_SMILIES',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'allow_post_links'		=> array('lang' => 'ALLOW_POST_LINKS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'allow_nocensors'		=> array('lang' => 'ALLOW_NO_CENSORS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'allow_bookmarks'		=> array('lang' => 'ALLOW_BOOKMARKS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'enable_post_confirm'	=> array('lang' => 'VISUAL_CONFIRM_POST',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),

				'legend2'				=> 'POSTING',
				'bump_type'				=> false,
				'edit_time'				=> array('lang' => 'EDIT_TIME',				'validate' => 'int',	'type' => 'text:3:3', 'explain' => true, 'append' => ' ' . $_CLASS['core_user']->lang['MINUTES']),
				'display_last_edited'	=> array('lang' => 'DISPLAY_LAST_EDITED',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'flood_interval'		=> array('lang' => 'FLOOD_INTERVAL',		'validate' => 'int',	'type' => 'text:3:4', 'explain' => true),
				'bump_interval'			=> array('lang' => 'BUMP_INTERVAL',			'validate' => 'int',	'type' => 'custom', 'method' => 'bump_interval', 'explain' => true),
				'topics_per_page'		=> array('lang' => 'TOPICS_PER_PAGE',		'validate' => 'int',	'type' => 'text:3:4', 'explain' => false),
				'posts_per_page'		=> array('lang' => 'POSTS_PER_PAGE',		'validate' => 'int',	'type' => 'text:3:4', 'explain' => false),
				'hot_threshold'			=> array('lang' => 'HOT_THRESHOLD',			'validate' => 'int',	'type' => 'text:3:4', 'explain' => false),
				'max_poll_options'		=> array('lang' => 'MAX_POLL_OPTIONS',		'validate' => 'int',	'type' => 'text:4:4', 'explain' => false),
				'max_post_chars'		=> array('lang' => 'CHAR_LIMIT',			'validate' => 'int',	'type' => 'text:4:6', 'explain' => true),
				'max_post_smilies'		=> array('lang' => 'SMILIES_LIMIT',			'validate' => 'int',	'type' => 'text:4:4', 'explain' => true),
				'max_post_urls'			=> array('lang' => 'MAX_POST_URLS',			'validate' => 'int',	'type' => 'text:5:4', 'explain' => true),
				'max_post_font_size'	=> array('lang' => 'MAX_POST_FONT_SIZE',	'validate' => 'int',	'type' => 'text:5:4', 'explain' => true),
				'max_quote_depth'		=> array('lang' => 'QUOTE_DEPTH_LIMIT',		'validate' => 'int',	'type' => 'text:4:4', 'explain' => true),
				'max_post_img_width'	=> array('lang' => 'MAX_POST_IMG_WIDTH',	'validate' => 'int',	'type' => 'text:5:4', 'explain' => true),
				'max_post_img_height'	=> array('lang' => 'MAX_POST_IMG_HEIGHT',	'validate' => 'int',	'type' => 'text:5:4', 'explain' => true),
			)
		);
	break;

	case 'load':
		$display_vars = array(
			'title'	=> 'ACP_LOAD_SETTINGS',
			'vars'	=> array(
				'legend2'				=> 'GENERAL_OPTIONS',
				'load_anon_lastread'	=> array('lang' => 'YES_ANON_READ_MARKING',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'load_online'			=> array('lang' => 'YES_ONLINE',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'load_online_guests'	=> array('lang' => 'YES_ONLINE_GUESTS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'load_onlinetrack'		=> array('lang' => 'YES_ONLINE_TRACK',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				'load_birthdays'		=> array('lang' => 'YES_BIRTHDAYS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'load_moderators'		=> array('lang' => 'YES_MODERATORS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'load_jumpbox'			=> array('lang' => 'YES_JUMPBOX',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
				'load_user_activity'	=> array('lang' => 'LOAD_USER_ACTIVITY',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
				
				'legend3'				=> 'CUSTOM_PROFILE_FIELDS',
			)
		);
	break;

	default:
		trigger_error('NO_MODE', E_USER_ERROR);
	break;
}

$new_config = $config;
$cfg_array = (isset($_REQUEST['config'])) ? request_var('config', array('' => ''), true) : $new_config;
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

	if ($submit)
	{
		set_config($config_name, $config_value);
	}
}

if ($submit)
{
	add_log('admin', 'LOG_CONFIG_' . strtoupper($mode));

	trigger_error($_CLASS['core_user']->lang['CONFIG_UPDATED'] . adm_back_link(generate_link($u_action, array('admin' => true))));
}

$page_title = $display_vars['title'];

$_CLASS['core_template']->assign_array(array(
	'L_TITLE'			=> $_CLASS['core_user']->lang[$display_vars['title']],
	'L_TITLE_EXPLAIN'	=> $_CLASS['core_user']->lang[$display_vars['title'] . '_EXPLAIN'],

	'S_ERROR'			=> (sizeof($error)) ? true : false,
	'ERROR_MSG'			=> implode('<br />', $error),

	'U_ACTION'			=> generate_link($u_action, array('admin' => true))
));

// Output relevant page
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
			'LEGEND'		=> (isset($_CLASS['core_user']->lang[$vars])) ? $_CLASS['core_user']->lang[$vars] : $vars
		));

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
		'S_LEGEND'		=> false,
		'KEY'			=> $config_key,
		'TITLE'			=> (isset($_CLASS['core_user']->lang[$vars['lang']])) ? $_CLASS['core_user']->lang[$vars['lang']] : $vars['lang'],
		'S_EXPLAIN'		=> $vars['explain'],
		'TITLE_EXPLAIN'	=> $l_explain,
		'CONTENT'		=> build_cfg_template($type, $config_key, $new_config, $config_key, $vars),
	));

	unset($display_vars['vars'][$config_key]);
}

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_board.html');

/**
* Select bump interval
*/
function bump_interval($value, $key)
{
	global $_CLASS, $new_config;

	$s_bump_type = '';
	$types = array('m' => 'MINUTES', 'h' => 'HOURS', 'd' => 'DAYS');
	foreach ($types as $type => $lang)
	{
		$selected = ($new_config['bump_type'] == $type) ? ' selected="selected"' : '';
		$s_bump_type .= '<option value="' . $type . '"' . $selected . '>' . $_CLASS['core_user']->lang[$lang] . '</option>';
	}

	return '<input id="' . $key . '" type="text" size="3" maxlength="4" name="config[bump_interval]" value="' . $value . '" />&nbsp;<select name="config[bump_type]">' . $s_bump_type . '</select>';
}

/**
* Board disable option and message
*/
function board_disable($value, $key)
{
	global $_CLASS;

	$radio_ary = array(1 => 'YES', 0 => 'NO');

	return h_radio('config[board_disable]', $radio_ary, $value) . '<br /><input id="' . $key . '" type="text" name="config[board_disable_msg]" maxlength="255" size="40" value="' . $new_config['board_disable_msg'] . '" />';
}

/**
* Select default dateformat
*/
function dateformat_select($value, $key)
{
	global $_CLASS;

	$dateformat_options = '';

	foreach ($_CLASS['core_user']->lang['dateformats'] as $format => $null)
	{
		$dateformat_options .= '<option value="' . $format . '"' . (($format == $value) ? ' selected="selected"' : '') . '>';
		$dateformat_options .= $_CLASS['core_user']->format_date(time(), $format, true) . ((strpos($format, '|') !== false) ? ' [' . $_CLASS['core_user']->lang['RELATIVE_DAYS'] . ']' : '');
		$dateformat_options .= '</option>';
	}

	$dateformat_options .= '<option value="custom"';
	if (!in_array($value, array_keys($_CLASS['core_user']->lang['dateformats'])))
	{
		$dateformat_options .= ' selected="selected"';
	}
	$dateformat_options .= '>' . $_CLASS['core_user']->lang['CUSTOM_DATEFORMAT'] . '</option>';

	return "<select name=\"dateoptions\" id=\"dateoptions\" onchange=\"if (this.value == 'custom') { document.getElementById('$key').value = '$value'; } else { document.getElementById('$key').value = this.value; }\">$dateformat_options</select>
	<input type=\"text\" name=\"config[$key]\" id=\"$key\" value=\"$value\" maxlength=\"30\" />";
}

?>