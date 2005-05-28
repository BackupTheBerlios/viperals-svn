<?php
/** 
*
* @package ucp
* @version $Id: ucp_resend.php,v 1.1 2005/04/09 12:26:57 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @package ucp
* ucp_resend
* Resending activation emails
*/
class ucp_resend extends module 
{
	function ucp_resend($id, $mode)
	{
		global $site_file_root, $config, $_CORE_CONFIG, $_CLASS;

		$submit = (isset($_POST['submit'])) ? true : false;

		if ($submit)
		{
			$username	= request_var('username', '');
			$email		= request_var('email', '');

			$sql = 'SELECT user_id, username, user_email, user_type, user_lang, user_actkey
				FROM ' . USERS_TABLE . "
				WHERE user_email = '" . $_CLASS['core_db']->sql_escape($email) . "'
					AND username = '" . $_CLASS['core_db']->sql_escape($username) . "'";
			$result = $_CLASS['core_db']->sql_query($sql);

			if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
			{
				trigger_error('NO_EMAIL_USER');
			}
			$_CLASS['core_db']->sql_freeresult($result);

			if (!$row['user_actkey'])
			{
				trigger_error('ACCOUNT_ALREADY_ACTIVATED');
			}

			$server_url = generate_board_url();
			$username = $row['username'];
			$user_id = $row['user_id'];

/*			if ($coppa)
			{
				$email_template = 'coppa_welcome_inactive';
			}*/
			if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN)
			{
				$email_template = 'admin_welcome_inactive';
			}
			else
			{
				$email_template = 'user_welcome_inactive';
			}

			include_once($site_file_root.'includes/forums/functions_messenger.php');

			$messenger = new messenger(false);

			if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_SELF || $coppa)
			{
				$messenger->template('user_resend_inactive', $row['user_lang']);

				$messenger->replyto($config['board_contact']);
				$messenger->to($row['user_email'], $row['username']);

				$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger->headers('X-AntiAbuse: User_id - ' . $_CLASS['core_user']->data['user_id']);
				$messenger->headers('X-AntiAbuse: Username - ' . $_CLASS['core_user']->data['username']);
				$messenger->headers('X-AntiAbuse: User IP - ' . $_CLASS['core_user']->ip);

				$messenger->assign_vars(array(
					'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
					'WELCOME_MSG'	=> sprintf($_CLASS['core_user']->lang['WELCOME_SUBJECT'], $_CORE_CONFIG['global']['site_name']),
					'USERNAME'		=> $row['username'],
					'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),

					'U_ACTIVATE'	=> generate_link("Control_Panel&mode=activate&u={$row['user_id']}&k={$row['user_actkey']}", array('full' => true, 'sid' => false)))
				);

				if ($coppa)
				{
					$messenger->assign_vars(array(
						'FAX_INFO'		=> $_CORE_CONFIG['user']['coppa_fax'],
						'MAIL_INFO'		=> $_CORE_CONFIG['user']['coppa_mail'],
						'EMAIL_ADDRESS' => $row['user_email'],
						'SITENAME'		=> $_CORE_CONFIG['global']['site_name'])
					);
				}

				$messenger->send(NOTIFY_EMAIL);
			}

			if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN)
			{
				// Grab an array of user_id's with a_user permissions ... these users
				// can activate a user
				$admin_ary = $_CLASS['auth']->acl_get_list(false, 'a_user', false);

				$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type
					FROM ' . USERS_TABLE . '
					WHERE user_id IN (' . implode(', ', $admin_ary[0]['a_user']) .')';
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$messenger->template('admin_activate', $row['user_lang']);
					$messenger->replyto($config['board_contact']);
					$messenger->to($row['user_email'], $row['username']);
					$messenger->im($row['user_jabber'], $row['username']);

					$messenger->assign_vars(array(
						'USERNAME'		=> $row['username'],
						'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),

						'U_ACTIVATE'	=> generate_link("Control_Panel&mode=activate&u={$row['user_id']}&k={$row['user_actkey']}", array('full' => true, 'sid' => false)))
					);

					$messenger->send($row['user_notify_type']);
				}
				$_CLASS['core_db']->sql_freeresult($result);
			}

			meta_refresh(3, generate_link());

			$message = $_CLASS['core_user']->lang['ACTIVATION_EMAIL_SENT'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_INDEX'],  '<a href="' . generate_link() . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			$username = $email = '';
		}
		
		$_CLASS['core_template']->assign(array(
			'L_USERNAME' 			=> $_CLASS['core_user']->lang['USERNAME'],
			'L_EMAIL_ADDRESS'	 	=> $_CLASS['core_user']->lang['EMAIL_ADDRESS'],
			'L_EMAIL_REMIND' 		=> $_CLASS['core_user']->lang['EMAIL_REMIND'],
			'L_SUBMIT' 				=> $_CLASS['core_user']->lang['SUBMIT'],
			'L_RESET' 				=> $_CLASS['core_user']->lang['RESET'],
			'L_UCP_RESEND'			=> $_CLASS['core_user']->lang['UCP_RESEND'],
			'USERNAME'	=> $username,
			'EMAIL'		=> $email)
		);
		
		$this->display($_CLASS['core_user']->lang['UCP_RESEND'], 'ucp_resend.html');
	}
}

?>
