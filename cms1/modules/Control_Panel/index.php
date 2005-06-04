<?php 
// -------------------------------------------------------------
//
// $Id: ucp.php,v 1.36 2004/07/11 15:20:34 acydburn Exp $
//
// FILENAME  : bbcode.php 
// STARTED   : Thu Nov 21, 2002
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// TODO for 2.2:
//
// * Registration
//    * Link to (additional?) registration conditions

// * Opening tab:
//    * Last visit time
//    * Last active in
//    * Most active in
//    * New PM counter
//    * Unread PM counter
//    * Link/s to MCP if applicable?

// * PM system
//    * See privmsg

// * Permissions?
//    * List permissions granted to this user (in UCP and ACP UCP)

if (!defined('VIPERAL')) {
    header('location: ../../../');
    die();
}

require_once($site_file_root.'includes/forums/functions.php');
loadclass($site_file_root.'includes/forums/auth.php', 'auth');

$_CLASS['core_user']->add_lang();
$_CLASS['core_user']->add_img(0, 'Forums');
$_CLASS['auth']->acl($_CLASS['core_user']->data);

$mode	= request_var('mode', '');
$module = request_var('i', '');

$ucp = new module();

if ($mode == 'login' || $mode == 'logout')
{
	define('IN_LOGIN', true);
}

require($site_file_root.'includes/forums/functions_user.php');

//define the undefineds
$_CLASS['core_template']->assign(array(
	'S_SHOW_PM_BOX'			=> false,
	'S_SHOW_COLOUR_LEGEND'	=> false,
	'L_OPTIONS'				=> 'Options',
	'USERNAME'				=> '',
	'friends_online'		=> false,
	'friends_offline' 		=> false,
	'L_PM_TO' 				=> $_CLASS['core_user']->lang['PM_TO'],
	'L_USERNAME' 			=> $_CLASS['core_user']->lang['USERNAME'],
	'L_ADD'	 				=> $_CLASS['core_user']->lang['ADD'],
	'L_USERNAMES' 			=> $_CLASS['core_user']->lang['USERNAMES'],
	'L_FIND_USERNAME' 		=> $_CLASS['core_user']->lang['FIND_USERNAME'],
	'L_USERGROUPS' 			=> $_CLASS['core_user']->lang['USERGROUPS'],
	'L_ADD_BCC' 			=> $_CLASS['core_user']->lang['ADD_BCC'],
	'L_ADD_TO' 				=> $_CLASS['core_user']->lang['ADD_TO'],
	'L_MESSAGE_COLOURS' 	=> $_CLASS['core_user']->lang['MESSAGE_COLOURS'],
	'L_FRIENDS' 			=> $_CLASS['core_user']->lang['FRIENDS'],
	'L_FRIENDS_ONLINE' 		=> $_CLASS['core_user']->lang['FRIENDS_ONLINE'],
	'L_NO_FRIENDS_ONLINE' 	=> $_CLASS['core_user']->lang['NO_FRIENDS_ONLINE'],
	'L_FRIENDS_OFFLINE' 	=> $_CLASS['core_user']->lang['FRIENDS_OFFLINE'],
	'L_NO_FRIENDS_OFFLINE' 	=> $_CLASS['core_user']->lang['NO_FRIENDS_OFFLINE'],
	)
);

// ---------
// FUNCTIONS
//
class module
{
	var $id = 0;
	var $type;
	var $name;
	var $mode;

	// Private methods, should not be overwritten
	function create($module_type, $module_url, $selected_mod = false, $selected_submod = false)
	{
		global $_CLASS, $config, $mainindex;

		$sql = 'SELECT module_id, module_title, module_filename, module_subs, module_acl
			FROM ' . MODULES_TABLE . "
			WHERE module_type = '{$module_type}'
				AND module_enabled = 1
			ORDER BY module_order ASC";
		$result = $_CLASS['core_db']->sql_query($sql);

		$i = 0;
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			// Authorisation is required for the basic module
			if ($row['module_acl'])
			{
				$is_auth = false;
				eval('$is_auth = (' . preg_replace(array('#acl_([a-z_]+)#e', '#cfg_([a-z_]+)#e'), array('(int) $_CLASS[\'auth\']'.'->acl_get("\\1")', '(int) $config["\\1"]'), trim($row['module_acl'])) . ');');

				// The user is not authorised to use this module, skip it
				if (!$is_auth)
				{
					continue;
				}
			}

			$selected = ($row['module_filename'] == $selected_mod || $row['module_id'] == $selected_mod || (!$selected_mod && !$i)) ?  true : false;

			// Get the localised lang string if available, or make up our own otherwise
			$module_lang = strtoupper($module_type) . '_' . $row['module_title'];
			$_CLASS['core_template']->assign_vars_array($module_type . '_section', array(
				'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : ucfirst(str_replace('_', ' ', strtolower($row['module_title']))),
				'S_SELECTED'	=> $selected, 
				'U_TITLE'		=> generate_link($module_url . '&amp;i=' . $row['module_id']))
			);

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
						if (!trim($submodule))
						{
							continue;
						}

						$submodule = explode(',', trim($submodule));
						$submodule_title = array_shift($submodule);

						$is_auth = true;
						foreach ($submodule as $auth_option)
						{
							eval('$is_auth = (' . preg_replace(array('#acl_([a-z_]+)#e', '#cfg_([a-z_]+)#e'), array('(int) $_CLASS[\'auth\']->acl_get("\\1")', '(int) $config["\\1"]'), trim($auth_option)) . ');');

							if (!$is_auth)
							{
								break;
							}
						}

						if (!$is_auth)
						{
							continue;
						}

						$selected = ($submodule_title == $selected_submod || (!$selected_submod && !$j)) ? true : false;

						// Get the localised lang string if available, or make up our own otherwise
						$module_lang = strtoupper($module_type . '_' . $module_name . '_' . $submodule_title);

						$_CLASS['core_template']->assign_vars_array("{$module_type}_subsection", array(
							'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : $module_lang,
							'S_SELECTED'	=> $selected, 
							'U_TITLE'		=> generate_link($module_url . '&amp;i=' . $module_id . '&amp;mode=' . $submodule_title)
						));

						if ($selected)
						{
							$this->mode = $submodule_title;
						}

						$j++;
					}
				} else {
					$_CLASS['core_template']->assign('ucp_subsection', false);
				}
			}

			$i++;
		}
		$_CLASS['core_db']->sql_freeresult($result);

		if (!isset($module_id) || !$module_id)
		{
			trigger_error('MODULE_NOT_EXIST');
		}

		$this->type = $module_type;
		$this->id = $module_id;
		$this->name = $module_name;
	}

	function load($type = false, $name = false, $mode = false, $run = true)
	{
		global $phpbb_root_path;

		if ($type)
		{
			$this->type = $type;
		}

		if ($name)
		{
			$this->name = $name;
		}

		if (!class_exists($this->type . '_' . $this->name))
		{
			require_once("{$this->type}/{$this->type}_{$this->name}.php");

			if ($run)
			{
				if (!isset($this->mode))
				{
					$this->mode = $mode;
				}

				eval("\$this->module = new {$this->type}_{$this->name}(\$this->id, \$this->mode);");
				if (method_exists($this->module, 'init'))
				{
					$this->module->init();
				}
			}
		}
	}

	// Displays the appropriate template with the given title
	function display($page_title, $tpl_name)
	{
		global $_CLASS;

		$_CLASS['core_display']->display_head($page_title);

		page_header();

        $_CLASS['core_template']->display('modules/Control_Panel/'.$tpl_name);

		$_CLASS['core_display']->display_footer();
		
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
//
// FUNCTIONS
// ---------


// Basic "global" modes
switch ($mode)
{
	case 'activate':
		$ucp->load('ucp', 'activate');
		$ucp->module->ucp_activate();
		redirect($mainindex);
		break;
		
	case 'resend_act':
		$ucp->load('ucp', 'resend');
		$ucp->module->ucp_resend();
		break;
	
	case 'sendpassword':
		$ucp->load('ucp', 'remind');
		$ucp->module->ucp_remind();
		break;

	case 'register':
		if ($_CLASS['core_user']->data['user_id'] != ANONYMOUS || isset($_REQUEST['not_agreed']))
		{
			redirect($mainindex);
		}

		$ucp->load('ucp', 'register');
		$ucp->module->ucp_register();
		break;

	case 'confirm':
		$ucp->load('ucp', 'confirm');
		$ucp->module->ucp_confirm();
		break;

	case 'login':
	
		if ($_CLASS['core_user']->is_user || $_CLASS['core_user']->is_bot)
		{
			redirect(generate_link());
		}

		login_box();

		break;

	case 'logout':
		if ($_CLASS['core_user']->data['user_id'] != ANONYMOUS)
		{
			$_CLASS['core_user']->destroy();
		}

		$_CLASS['core_display']->meta_refresh(3, generate_link());

		$message = $_CLASS['core_user']->lang['LOGOUT_REDIRECT'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . generate_link() . '">', '</a> ');
		trigger_error($message);
		break;
		
	case 'terms_of_use':
	case 'privacy_statement':
		break;
		
	case 'delete_cookies':
		// Delete Cookies with dynamic names (do NOT delete poll cookies)
		if (confirm_box(true))
		{
			global $_CORE_CONFIG;
			
			$set_time = time() - 31536000;
			foreach ($_COOKIE as $cookie_name => $cookie_data)
			{
				$cookie_name = str_replace($_CORE_CONFIG['server']['cookie_name'] . '_', '', $cookie_name);
				if (strpos($cookie_name, '_poll') === false)
				{
					$_CLASS['core_user']->set_cookie($cookie_name, '', $set_time);
				}
			}
			$_CLASS['core_user']->set_cookie('track', '', $set_time);
			$_CLASS['core_user']->set_cookie('data', '', $set_time);
			$_CLASS['core_user']->set_cookie('sid', '', $set_time);

			// We destroy the session here, the user will be logged out nevertheless
			$_CLASS['core_user']->destroy();

			$_CLASS['core_display']->meta_refresh(3, generate_link());

			$message = $_CLASS['core_user']->lang['COOKIES_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_INDEX'], '<a href="'.$mainindex.'">', '</a>');
			trigger_error($message);

		}
		else
		{
			confirm_box(false, 'DELETE_COOKIES', '');
		}
		redirect($mainindex);
		break;
}


// Only registered users can go beyond this point
if (!$_CLASS['core_user']->is_user)
{
	if ($_CLASS['core_user']->is_bot)
	{
//error no access
		redirect($mainindex);
	}
	
	login_box('', $_CLASS['core_user']->lang['LOGIN_EXPLAIN_UCP']);
}


// Output listing of friends online
$update_time = $config['load_online_time'] * 60;

$sql = 'SELECT DISTINCT u.user_id, u.username, MAX(s.session_time) as online_time, MIN(s.session_viewonline) AS viewonline
	FROM ((' . ZEBRA_TABLE . ' z 
	LEFT JOIN ' . SESSIONS_TABLE . ' s ON s.session_user_id = z.zebra_id), ' . USERS_TABLE . ' u)
	WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
		AND z.friend = 1 
		AND u.user_id = z.zebra_id  
	GROUP BY z.zebra_id';
$result = $_CLASS['core_db']->sql_query($sql);

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$which = (time() - $update_time < $row['online_time']) ? 'online' : 'offline';

	$_CLASS['core_template']->assign_vars_array("friends_{$which}", array(
		'U_PROFILE'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
		
		'USER_ID'	=> $row['user_id'],
		'USERNAME'	=> $row['username'])
	);
}
$_CLASS['core_db']->sql_freeresult($result);

// Output PM_TO box if message composing
if ($mode == 'compose' && request_var('action', '') != 'edit')
{
	if ($config['allow_mass_pm'])
	{
		$sql = 'SELECT group_id, group_name, group_type   
			FROM ' . GROUPS_TABLE . ' 
			WHERE group_type NOT IN (' . GROUP_HIDDEN . ', ' . GROUP_CLOSED . ')
				AND group_receive_pm = 1
			ORDER BY group_type DESC';
		$result = $_CLASS['core_db']->sql_query($sql);

		$group_options = '';
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$group_options .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' class="blue"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}

	$_CLASS['core_template']->assign(array(
		'S_SHOW_PM_BOX'		=> true,
		'S_ALLOW_MASS_PM'	=> ($config['allow_mass_pm']),
		'S_GROUP_OPTIONS'	=> ($config['allow_mass_pm']) ? $group_options : '',
		'U_SEARCH_USER'		=> generate_link('Members_List&amp;mode=searchuser&amp;form=post&amp;field=username_list'),
		'L_FIND_USERNAME'	=> $_CLASS['core_user']->lang['FIND_USERNAME'])

	);
}

// Instantiate module system and generate list of available modules
$ucp->create('ucp', 'Control_Panel', $module, $mode);

// Load and execute the relevant module
$ucp->load();

?>