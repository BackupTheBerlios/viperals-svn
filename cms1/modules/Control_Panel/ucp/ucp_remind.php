<?php
// -------------------------------------------------------------
//
// $Id: ucp_remind.php,v 1.8 2004/02/21 12:47:34 acydburn Exp $
//
// FILENAME  : ucp_remind.php
// STARTED   : Mon May 19, 2003
// COPYRIGHT : © 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
class ucp_remind extends module 
{
	function ucp_remind($id, $mode)
	{
		global $config, $_CLASS, $site_file_root;
		
		$_CLASS['core_template']->assign('S_HIDDEN_FIELDS', false);

		$submit = (isset($_POST['submit'])) ? true : false;

		if ($submit)
		{
			$username	= request_var('username', '');
			$email		= request_var('email', '');

			$sql = 'SELECT user_id, username, user_email, user_jabber, user_notify_type, user_type, user_lang
				FROM ' . USERS_TABLE . "
				WHERE user_email = '" . $_CLASS['core_db']->sql_escape($email) . "'
					AND username = '" . $_CLASS['core_db']->sql_escape($username) . "'";
			$result = $_CLASS['core_db']->sql_query($sql);

			if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
			{
				trigger_error('NO_EMAIL_USER');
			}
			$_CLASS['core_db']->sql_freeresult($result);

			if ($row['user_type'] == USER_INACTIVE)
			{
				trigger_error('ACCOUNT_NOT_ACTIVATED');
			}

			$server_url = generate_board_url();
			$username = $row['username'];
			$user_id = $row['user_id'];

			$key_len = 54 - strlen($server_url);
			$key_len = ($key_len > 6) ? $key_len : 6;
			$user_actkey = substr(gen_rand_string(10), 0, $key_len);
			$user_password = gen_rand_string(8);

			$sql = 'UPDATE ' . USERS_TABLE . "
				SET user_newpasswd = '" . $_CLASS['core_db']->sql_escape(md5($user_password)) . "', user_actkey = '" . $_CLASS['core_db']->sql_escape($user_actkey) . "'
				WHERE user_id = " . $row['user_id'];
			$_CLASS['core_db']->sql_query($sql);

			include_once($site_file_root. 'includes/forums/functions_messenger.php');

			$messenger = new messenger();

			$messenger->template('user_activate_passwd', $row['user_lang']);
			
			$messenger->replyto($_CLASS['core_user']->data['user_email']);
			$messenger->to($row['user_email'], $row['username']);
			$messenger->im($row['user_jabber'], $row['username']);

			$messenger->assign_vars(array(
				'SITENAME'		=> $config['site_name'],
				'USERNAME'		=> $username,
				'PASSWORD'		=> $user_password,
				'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
				'U_ACTIVATE'	=> generate_link("Control_Panel&mode=activate&u=$user_id&k=$user_actkey", array('full' => true, 'sid' => false)))
			);

			$messenger->send($row['user_notify_type']);
			$messenger->save_queue();
			
			$_CLASS['core_display']->meta_refresh(3, generate_link());

			$message = $_CLASS['core_user']->lang['PASSWORD_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_INDEX'],  '<a href="' . generate_link() . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			$username = $email = '';
		}

		$_CLASS['core_template']->assign(array(
			
			'USERNAME'	=> $username,
			'EMAIL'		=> $email)
		);

		$this->display($_CLASS['core_user']->lang['UCP_REMIND'], 'ucp_remind.html');
	}
}

?>