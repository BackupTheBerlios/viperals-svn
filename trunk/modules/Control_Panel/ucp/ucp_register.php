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

class ucp_register extends module 
{
	function ucp_register($id, $mode)
	{
		global $site_file_root, $config, $_CLASS, $_CORE_CONFIG;
		
		if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_DISABLE)
		{
			trigger_error('UCP_REGISTER_DISABLE');
		}

		$_CLASS['core_template']->assign('S_UCP_ACTION', generate_link('Control_Panel&amp;mode=register'));

		$coppa		= isset($_REQUEST['coppa']) ? (int) $_REQUEST['coppa'] : null;
		$submit		= isset($_POST['submit']);

		$error = $data = array();
		$s_hidden_fields = '';


		if (!isset($_POST['agreed']))
		{
			if ($_CORE_CONFIG['user']['coppa_enable'] && is_null($coppa))
			{
				$now = explode(':', gmdate('m:j:Y'));

				$coppa_birthday = $_CLASS['core_user']->format_date(mktime(12, 0, 0, $now[0], $now[1], $now[2] - 13), 'D M d, Y'); 

				$_CLASS['core_template']->assign_array(array(
					'L_COPPA_NO'		=> sprintf($_CLASS['core_user']->lang['UCP_COPPA_BEFORE'], $coppa_birthday),
					'L_COPPA_YES'		=> sprintf($_CLASS['core_user']->lang['UCP_COPPA_ON_AFTER'], $coppa_birthday),

					'U_COPPA_NO'		=> generate_link('Control_Panel&amp;mode=register&amp;coppa=0'), 
					'U_COPPA_YES'		=> generate_link('Control_Panel&amp;mode=register&amp;coppa=1'), 

					'S_SHOW_COPPA'		=> true, 
					'S_HIDDEN_FIELDS'	=> $s_hidden_fields,
					'S_REGISTER_ACTION'	=> generate_link('Control_Panel&amp;mode=register'))
				);
			}
			else
			{
				$s_hidden_fields .= '<input type="hidden" name="coppa" value="' . $coppa . '" />';

				$_CLASS['core_template']->assign_array(array(
					'S_SHOW_COPPA'		=> false,
					'S_HIDDEN_FIELDS'	=> $s_hidden_fields,
					'S_REGISTER_ACTION'	=> generate_link('Control_Panel&amp;mode=register')
				));
			}

			$this->display($_CLASS['core_user']->lang['REGISTER'], 'ucp_agreement.html');

			script_close();
		}

		if ($submit)
		{
			require_once($site_file_root.'includes/functions_user.php');

			$error = array();

			$username	= get_variable('username', 'POST', false);
			$password	= get_variable('password', 'POST', false);
			$email		= get_variable('email', 'POST', false);
			
			//when we add this make sure to confirm that it's one of the installed langs
			$lang		= $_CORE_CONFIG['global']['default_lang'];
			$tz			= get_variable('tz', 'POST', false);

			if (strpos($username, "\n"))
			{
				die;
			}

			$username_validate = validate_username($username);

			if ($username_validate !== true)
			{
				$error[] = $username_validate;
			}

			if (!$password || $password !== get_variable('password_confirm', 'POST', ''))
			{
				$error[] = 'PASSWORD_ERROR';
			}

			if (!$email || $email !== get_variable('email_confirm', 'POST', ''))
			{
				$error[] = 'PASSWORD_ERROR';
			}

			if ($_CORE_CONFIG['user']['enable_confirm'])
			{
				$confirmation_code = $_CLASS['core_user']->session_data_get('confirmation_code');
				$confirm_code = trim(get_variable('confirm_code', 'POST', false));

				if (!$confirm_code || !$confirmation_code || $confirm_code != $confirmation_code)
				{
					$error[] = $_CLASS['core_user']->lang['CONFIRM_CODE_WRONG'];
				}

				// we don't need this any more
				$_CLASS['core_user']->user_data_kill('confirmation_code');
			}

		//$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error);
			if (empty($error))
			{
				// Tz need to be moved out of the lang file, only use it for languages
				if (!$tz || !in_array($tz, $_CLASS['core_user']->lang['tz']['zones']))
				{
					$tz = null;
				}
	
				$password = encode_password($password, $_CORE_CONFIG['user']['password_encoding']);
	
				if (!$password)
				{
					//do some admin contact thing here
					die('Try again later');
				}
	
				if ($coppa || $_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_SELF || $_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN)
				{
					if (!$_CORE_CONFIG['email']['email_enable'])
					{
						//do some admin contact thing here
						die('Try again later');
					}
	
					$user_status = STATUS_PENDING;
					$user_act_key = generate_string(10);
				}
				else
				{
					$user_status = STATUS_ACTIVE;
					$user_act_key = null;
				}
	
				if ($user_status === STATUS_ACTIVE)
				{
					set_core_config('user', 'newest_user_id', $row['user_id'], false);
					set_core_config('user', 'newest_username', $row['username'], false);
					set_core_config('user', 'num_users', $_CORE_CONFIG['user']['num_users'] + 1, false);
				}
	
				$data = array(
					'username'		=> $username,
					'user_email'	=> $email,
// add an option so admin can set with group they added to
					'user_group'	=> ($coppa) ? 3 : 2,
					'user_reg_date'	=> gmtime(),
					'user_timezone'	=> $tz,

					'user_password'			=> $password,
					'user_password_encoding'=> $_CORE_CONFIG['user']['password_encoding'],

					'user_lang'			=> ($lang == $_CORE_CONFIG['global']['default_lang']) ? null :$lang ,
					'user_type'			=> USER_NORMAL,
					'user_status'		=> $user_status,
					'user_act_key'		=> $user_act_key,
					'user_ip'			=> $_CLASS['core_user']->ip,
				);

				user_add($data);
			}
		}

		$s_hidden_fields .= '<input type="hidden" name="coppa" value="' . $coppa . '" />';
		$s_hidden_fields .= '<input type="hidden" name="agreed" value="true" />';
		$confirm_image = '';
		
		// Visual Confirmation - Show images
		if ($_CORE_CONFIG['user']['enable_confirm'])
		{
			if ($submit)
			{
				if ($_CORE_CONFIG['user']['max_reg_attempts'])
				{
					$attempts = (int) $_CLASS['core_user']->session_data_get('reg_attempts');

					if ($attempts >= $_CORE_CONFIG['user']['max_reg_attempts'])
					{
						trigger_error($_CLASS['core_user']->lang['TOO_MANY_REGISTERS']);
					}

					$_CLASS['core_user']->session_data_get('reg_attempts', ($attempts + 1));
				}

				$_CLASS['core_user']->session_data_set('confirmation_code', generate_string(6));
			}

			$confirm_image = '<img src="'.generate_link('system&amp;mode=confirmation_image').'" alt="" title="" />';
		}

		$l_reg_cond = '';

		switch ($_CORE_CONFIG['user']['require_activation'])
		{
			case USER_ACTIVATION_SELF:
				$l_reg_cond = $_CLASS['core_user']->lang['UCP_EMAIL_ACTIVATE'];
			break;

			case USER_ACTIVATION_ADMIN:
				$l_reg_cond = $_CLASS['core_user']->lang['UCP_ADMIN_ACTIVATE'];
			break;
		}

		$user_char_ary = array('.*' => 'USERNAME_CHARS_ANY', '[\w]+' => 'USERNAME_ALPHA_ONLY', '[\w_\+\. \-\[\]]+' => 'USERNAME_ALPHA_SPACERS');

		$_CLASS['core_template']->assign(array(
			'ERROR'						=> empty($error) ? false : implode('<br />', $error), 
			'USERNAME'					=> isset($username) ? $username : '',
			'PASSWORD'					=> isset($password) ? $password : '',
			'EMAIL'						=> isset($email) ? $email : '',
			'EMAIL_CONFIRM'				=> isset($email_confirm) ? $email_confirm : '', // should remove this also maybe
			'CONFIRM_IMG'				=> $confirm_image,

			'L_CONFIRM_EXPLAIN'			=> sprintf($_CLASS['core_user']->lang['CONFIRM_EXPLAIN'], '<a href="mailto:' . htmlentities($config['board_contact']) . '">', '</a>'), 
			'L_ITEMS_REQUIRED'			=> $l_reg_cond, 
			'L_USERNAME_EXPLAIN'		=> sprintf($_CLASS['core_user']->lang[$user_char_ary[$_CORE_CONFIG['user']['allow_name_chars']] . '_EXPLAIN'], $_CORE_CONFIG['user']['min_name_chars'], $_CORE_CONFIG['user']['max_name_chars']),
			'L_NEW_PASSWORD_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['NEW_PASSWORD_EXPLAIN'], $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 

			//'S_LANG_OPTIONS'	=> language_select($lang), 
			'S_TZ_OPTIONS'		=> tz_select(isset($tz) ? $tz : $_CORE_CONFIG['global']['default_timezone']),
			'S_COPPA'			=> $coppa, 
			'S_HIDDEN_FIELDS'	=> $s_hidden_fields,
			'S_UCP_ACTION'		=> generate_link("Control_Panel&amp;mode=register"))
		);
		
		$this->display($_CLASS['core_user']->lang['REGISTER'], 'ucp_register.html');
	}
}

?>