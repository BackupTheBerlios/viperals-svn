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


/*
require_once(SITE_FILE_ROOT.'includes/forums/functions.php');
load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
$_CLASS['auth'] =& $_CLASS['forums_auth'];


$_CLASS['core_user']->add_img(0, 'Forums');
$_CLASS['auth']->acl($_CLASS['core_user']->data);
*/

if (!defined('VIPERAL'))
{
    die;
}

//$this->supported = array('page', 'feed', 'ajax');
$this->supported = array('page');

class module_control_panel
{
	function module_control_panel()
	{
		$this->module = get_variable('i', 'REQUEST');
		$this->mode = get_variable('mode', 'REQUEST');
	}

	function process_module($type = 'page')
	{
		global $_CLASS;
		
		if ($this->module)
		{
			$sql = 'SELECT * FROM ' . CORE_CONTROL_PANEL_MODULES_TABLE. "
					WHERE module_name = '".$_CLASS['core_db']->escape($this->module)."'";
			$result = $_CLASS['core_db']->query($sql);
			$module_info = $_CLASS['core_db']->fetch_row_assoc($result);
		}

		if (!$this->module || !$module_info || !file_exists(SITE_FILE_ROOT.'modules/Control_Panel/modules/ucp_' . $this->module . '.php'))
		{
			$this->module = 'main';
			$this->mode = ($this->module) ? false : $this->mode;
		}

		$this->link_parent = $this->link = 'control_panel&amp;i='.$this->module;

		if ($this->mode)
		{
			$this->link .= '&amp;mode='.$this->mode;
		}
	}

	function generate_panel_block()
	{
		global $_CLASS, $_CORE_CONFIG;
	
		$sql = 'SELECT *
			FROM ' . CORE_CONTROL_PANEL_MODULES_TABLE. ' ORDER BY module_id';
		$result = $_CLASS['core_db']->query($sql);
	
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$selected = ($row['module_name'] === $this->module);
		
			$module_lang = 'UCP_' . $row['module_title'];
			$_CLASS['core_template']->assign_vars_array('ucp_section', array(
				'L_TITLE'		=> isset($_CLASS['core_user']->lang[$module_lang]) ? $_CLASS['core_user']->lang[$module_lang] : mb_convert_case(str_replace('_', ' ',  ($row['module_name'])), MB_CASE_TITLE),
				'S_SELECTED'	=> $selected, 
				'U_TITLE'		=> generate_link('control_panel&amp;i=' . $row['module_name']))
			);
		
			if ($selected)
			{
				if (!$row['module_subs'])
				{
					$_CLASS['core_template']->assign('ucp_subsection', false);
		
					continue;
				}
		
				$sub_modules = explode("\n", trim($row['module_subs']));
		
				foreach ($sub_modules as $sub_module)
				{
					if (!trim($sub_module))
					{
						continue;
					}
		
					$selected = ($this->mode && $sub_module === $this->mode) ? true : false;
		
					$module_lang = strtoupper('UCP_' . $row['module_name'] . '_' . $sub_module);
		
					$_CLASS['core_template']->assign_vars_array("ucp_subsection", array(
						'L_TITLE'		=> (isset($_CLASS['core_user']->lang[$module_lang])) ? $_CLASS['core_user']->lang[$module_lang] : $module_lang,
						'S_SELECTED'	=> $selected, 
						'U_TITLE'		=> generate_link('control_panel&amp;i=' . $row['module_name'] . '&amp;mode=' . $sub_module)
					));
				}
			}
		}
		$_CLASS['core_db']->free_result($result);
	
		// Output listing of friends online
		$online_time = $_CLASS['core_user']->time = $_CORE_CONFIG['server']['session_length'];
		
		$sql = 'SELECT DISTINCT u.user_id, u.username, s.session_time, s.session_hidden
			FROM ' . CORE_USERS_TABLE . ' u, ' . ZEBRA_TABLE . ' z 
			LEFT JOIN ' . CORE_SESSIONS_TABLE . ' s ON (s.session_user_id = z.zebra_id)
			WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
				AND z.friend = 1 
				AND u.user_id = z.zebra_id';
		$result = $_CLASS['core_db']->query($sql);
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$which = ($row['session_time'] > $online_time) ? 'online' : 'offline';
		
			$_CLASS['core_template']->assign_vars_array("friends_{$which}", array(
				'U_PROFILE'	=> generate_link('members_list&amp;mode=viewprofile&amp;u=' . $row['user_id']),
				'USER_ID'	=> $row['user_id'],
				'USERNAME'	=> $row['username']
			));
		}
		$_CLASS['core_db']->free_result($result);
	}

	function page_control_panel()
	{
		global $_CLASS, $_CORE_CONFIG;

		$_CLASS['core_user']->user_setup();
		
		/* Assign some basic template varibles */
		$_CLASS['core_template']->assign_array(array(
			'S_DISPLAY_FORM'		=> false,
			'S_SHOW_PM_BOX'			=> false,
			'S_SHOW_COLOUR_LEGEND'	=> false,
			'USERNAME'				=> '',
			'friends_online'		=> false,
			'friends_offline' 		=> false,
		));
		
		$_CLASS['core_user']->add_lang();

		if (!$this->module && $this->mode)
		{
			switch ($this->mode)
			{
				case 'register':
					if ($_CLASS['core_user']->is_user || $_CLASS['core_user']->is_bot)
					{
						redirect();
					}

					require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_register.php';

				break;
			
				case 'login':
					if ($_CLASS['core_user']->is_user || $_CLASS['core_user']->is_bot)
					{
						redirect();
					}
			
					login_box();
				break;
			
				case 'logout':
					if ($_CLASS['core_user']->is_user)
					{
						$_CLASS['core_user']->logout();
					}
			
					$_CLASS['core_display']->meta_refresh(3);
			
					$message = $_CLASS['core_user']->lang['LOGOUT_REDIRECT'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . generate_link() . '">', '</a> ');
					trigger_error($message);
				break;
			
				case 'delete_cookies':
					if (display_confirmation($_CLASS['core_user']->get_lang('DELETE_COOKIES')))
					{
						global $_CORE_CONFIG;
						
						$set_time = gmtime() - 31536000;
			
						foreach ($_COOKIE as $cookie_name => $cookie_data)
						{
							$cookie_name = str_replace($_CORE_CONFIG['server']['cookie_name'] . '_', '', $cookie_name);
			
							if (strpos($cookie_name, '_poll') === false)
							{
								$_CLASS['core_user']->set_cookie($cookie_name, '', $set_time);
							}
						}

						$_CLASS['core_user']->logout();

						$_CLASS['core_display']->meta_refresh(3);

						$message = $_CLASS['core_user']->lang['COOKIES_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_INDEX'], '<a href="'.generate_link().'">', '</a>');
						trigger_error($message);
					}
			
					redirect();
				break;
			}
		}
		
		/* Only registered users can go beyond this point*/
		if (!$_CLASS['core_user']->is_user)
		{
			if ($_CLASS['core_user']->is_bot)
			{
				redirect();
			}
		
			login_box(array('explain' => $_CLASS['core_user']->get_lang('LOGIN_EXPLAIN_UCP')));
		}
		
		$this->process_module('page');
		$this->generate_panel_block('page');

		require SITE_FILE_ROOT.'modules/control_panel/modules/ucp_' . $this->module . '.php';
	}
}

?>