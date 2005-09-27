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
		
		$coppa		= isset($_REQUEST['coppa']) ? (int) $_REQUEST['coppa'] : null;
		$submit		= isset($_POST['submit']);

		if ($_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_DISABLE
			|| ($coppa || $_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_SELF || $_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_ADMIN)
			&& !$_CORE_CONFIG['email']['email_enable'])
		{
			trigger_error('UCP_REGISTER_DISABLE');
		}

		$_CLASS['core_template']->assign('S_UCP_ACTION', generate_link('Control_Panel&amp;mode=register'));

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

			$email			= get_variable('email', 'POST', false);
			$email_confirm	= get_variable('email_confirm', 'POST', '');

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
				$error[] = $_CLASS['core_user']->get_lang($username_validate);
			}

			if (!$password || $password !== get_variable('password_confirm', 'POST', ''))
			{
				$error[] = $_CLASS['core_user']->get_lang('PASSWORD_ERROR');
			}

			if (!$email || $email !== $email_confirm)
			{
				$error[] = $_CLASS['core_user']->get_lang('EMAIL_ERROR');
			}
			elseif (!check_email($email))
			{
				$error[] = $_CLASS['core_user']->get_lang('EMAIL_INVALID');
			}

			if (!$tz || !in_array($tz, tz_array()))
			{
				$tz = null;
			}

			if ($_CORE_CONFIG['user']['enable_confirm'])
			{
				$confirmation_code = $_CLASS['core_user']->session_data_get('confirmation_code');
				$confirm_code = trim(get_variable('confirm_code', 'POST', false));

				if (!$confirm_code || !$confirmation_code || $confirm_code != $confirmation_code)
				{
					$error[] = $_CLASS['core_user']->get_lang('CONFIRM_CODE_WRONG');
				}

				// we don't need this any more
				$_CLASS['core_user']->user_data_kill('confirmation_code');
			}

			if (empty($error))
			{
				$encode_password = encode_password($password, $_CORE_CONFIG['user']['password_encoding']);
	
				if (!$encode_password)
				{
					//do some admin contact thing here
					die('Activation disabled: Passwaord encoding problem');
				}

				if ($coppa || $_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_SELF || $_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_ADMIN)
				{
					$user_status = STATUS_PENDING;
					$user_act_key = generate_string(10);

					if ($coppa)
					{
						$message = $_CLASS['core_user']->get_lang('ACCOUNT_COPPA');
						$email_template = 'activation_coppa_inactive.txt';
					}
					elseif ($_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_SELF)
					{
						$message = $_CLASS['core_user']->get_lang('ACCOUNT_INACTIVE');
						$email_template = 'activation_inactive.txt';
					}
					elseif ($_CORE_CONFIG['user']['activation'] == USER_ACTIVATION_ADMIN)
					{
						$message = $_CLASS['core_user']->get_lang('ACCOUNT_INACTIVE_ADMIN');
						$email_template = 'activation_admin_inactive.txt';
					}
				}
				else
				{
					$user_status = STATUS_ACTIVE;
					$user_act_key = null;
			
					$email_template = 'activation_active.txt';
					$message = $_CLASS['core_user']->get_lang('ACCOUNT_ADDED');
				}

				$data = array(
					'username'		=> (string) $username,
					'user_email'	=> (string) $email,
// add an option so admin can set with group they added to
					'user_group'	=> ($coppa) ? 3 : 2,
					'user_reg_date'	=> (int) $_CLASS['core_user']->time,
					'user_timezone'	=> (string) $tz,

					'user_password'			=> (string) $encode_password,
					'user_password_encoding'=> (string) $_CORE_CONFIG['user']['password_encoding'],

					'user_lang'			=> ($lang) ? (string) $lang : null,
					'user_type'			=> USER_NORMAL,
					'user_status'		=> (int) $user_status,
					'user_act_key'		=> (string) $user_act_key,
					'user_ip'			=> (string) $_CLASS['core_user']->ip,
				);

				user_add($data);

				if ($data['user_status'] === STATUS_ACTIVE)
				{
					set_core_config('user', 'newest_user_id', $data['user_id'], false);
					set_core_config('user', 'newest_username', $data['username'], false);
					set_core_config('user', 'total_users', $_CORE_CONFIG['user']['total_users'] + 1, false);
				}

				require_once($site_file_root.'includes/mailer.php');

				$mailer = new core_mailer();

				$mailer->to($email, $username);
				$mailer->subject('Welcome to ');

				$_CLASS['core_template']->assign_array(array(
					'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
					'WELCOME_MSG'   => sprintf($_CLASS['core_user']->lang['WELCOME_SUBJECT'], $_CORE_CONFIG['global']['site_name']),
					'USERNAME'		=> $username,
					'PASSWORD'		=> $password,
					'EMAIL_SIG'		=> '', //I like this
					'U_ACTIVATE'	=> generate_link('system&mode=activate&user_id='.$data['user_id'].'&key='.$user_act_key, array('sid' => false, 'full' => true))
				));

				if ($coppa)
				{
					$_CLASS['core_template']->assign_array(array(
						'FAX_INFO'		=> $_CORE_CONFIG['user']['coppa_fax'],
						'MAIL_INFO'		=> $_CORE_CONFIG['user']['coppa_mail'],
						'EMAIL_ADDRESS' => $email,
						'SITENAME'		=> $_CORE_CONFIG['global']['site_name']
					));
				}

				$mailer->message = trim($_CLASS['core_template']->display('email/core/'.$email_template, true));

				$mailer->send();

				$message = $message . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_INDEX'],  '<a href="'. generate_link() .'">', '</a>');
				trigger_error($message);
			}
		}

		$s_hidden_fields .= '<input type="hidden" name="coppa" value="' . $coppa . '" />';
		$s_hidden_fields .= '<input type="hidden" name="agreed" value="true" />';
		
		if ($_CORE_CONFIG['user']['enable_confirm'])
		{
			$_CLASS['core_user']->session_data_set('confirmation_code', generate_string(6));
			$confirm_image = '<img src="'.generate_link('system&amp;mode=confirmation_image').'" alt="" title="" />';
		}
		else
		{
			$confirm_image = false;
		}

		if ($submit)
		{
			if ($_CORE_CONFIG['user']['max_reg_attempts'])
			{
				$attempts = (int) $_CLASS['core_user']->session_data_get('reg_attempts', 0);

				if ($attempts > $_CORE_CONFIG['user']['max_reg_attempts'])
				{
					trigger_error('TOO_MANY_ATTEMPTS');
				}

				$_CLASS['core_user']->session_data_get('reg_attempts', ($attempts + 1));
			}
		}

		switch ($_CORE_CONFIG['user']['activation'])
		{
			case USER_ACTIVATION_SELF:
				$l_reg_cond = $_CLASS['core_user']->lang['UCP_EMAIL_ACTIVATE'];
			break;

			case USER_ACTIVATION_ADMIN:
				$l_reg_cond = $_CLASS['core_user']->lang['UCP_ADMIN_ACTIVATE'];
			break;
			
			default:
				$l_reg_cond = '';
			break;
		}

		$user_char_ary = array('.*' => 'USERNAME_CHARS_ANY', '[\w]+' => 'USERNAME_ALPHA_ONLY', '[\w_\+\. \-\[\]]+' => 'USERNAME_ALPHA_SPACERS');

		$_CLASS['core_template']->assign_array(array(
			'ERROR'			=> empty($error) ? false : implode('<br />', $error), 
			'USERNAME'		=> isset($username) ? $username : '',
			'PASSWORD'		=> isset($password) ? $password : '',
			'EMAIL'			=> isset($email) ? $email : '',
			'EMAIL_CONFIRM'	=> isset($email_confirm) ? $email_confirm : '',
			'CONFIRM_IMG'	=> $confirm_image,
			'SELECT_TZ'		=> select_tz(isset($tz) ? $tz : $_CORE_CONFIG['global']['default_timezone']),

			'L_CONFIRM_EXPLAIN'			=> sprintf($_CLASS['core_user']->lang['CONFIRM_EXPLAIN'], '<a href="mailto:' . htmlentities($config['board_contact']) . '">', '</a>'), 
			'L_ITEMS_REQUIRED'			=> $l_reg_cond, 
			'L_USERNAME_EXPLAIN'		=> sprintf($_CLASS['core_user']->lang[$user_char_ary[$_CORE_CONFIG['user']['allow_name_chars']] . '_EXPLAIN'], $_CORE_CONFIG['user']['min_name_chars'], $_CORE_CONFIG['user']['max_name_chars']),
			'L_NEW_PASSWORD_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['NEW_PASSWORD_EXPLAIN'], $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 


			'S_COPPA'			=> $coppa, 
			'S_HIDDEN_FIELDS'	=> $s_hidden_fields,
			'S_UCP_ACTION'		=> generate_link("Control_Panel&amp;mode=register"))
		);
		
		$this->display($_CLASS['core_user']->lang['REGISTER'], 'ucp_register.html');
	}
}

?>