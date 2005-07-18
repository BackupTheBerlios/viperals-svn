<?php
// -------------------------------------------------------------
//
// $Id: ucp_activate.php,v 1.8 2004/05/26 20:16:20 acydburn Exp $
//
// FILENAME  : ucp_activate.php
// STARTED   : Mon May 19, 2003
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
 
class ucp_activate extends module 
{
	function ucp_activate($id, $mode)
	{
		global $config, $_CORE_CONFIG, $_CLASS, $site_file_root;

		//$user_id = request_var('u', 0);
		$user_id = get_variable('u', 'REQUEST', 0, 'integer')
		$key = request_var('k', '');

		$sql = 'SELECT user_id, username, user_type, user_email, user_newpasswd, user_lang, user_notify_type, user_actkey
			FROM ' . USERS_TABLE . "
			WHERE user_id = $user_id";
		$result = $_CLASS['core_db']->sql_query($sql);

		if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
		{
			trigger_error($_CLASS['core_user']->lang['NO_USER']);
		}
		$_CLASS['core_db']->sql_freeresult($result);

		if ($row['user_type'] <> USER_INACTIVE && !$row['user_newpasswd'])
		{
			$_CLASS['core_display']->meta_refresh(3);
			trigger_error($_CLASS['core_user']->lang['ALREADY_ACTIVATED']);
		}
		
		if ($row['user_actkey'] != $key)
		{
			trigger_error($_CLASS['core_user']->lang['WRONG_ACTIVATION']);
		}

		$update_password = ($row['user_newpasswd']) ? true : false;

		if ($update_password)
		{
			$sql_ary = array(
				'user_type'			=> USER_NORMAL,
				'user_actkey'		=> '',
				'user_password'		=> $row['user_newpasswd'],
				'user_newpasswd'	=> ''
			);

			$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
				WHERE user_id = ' . $row['user_id'];
			$result = $_CLASS['core_db']->sql_query($sql);
		}
		
		// TODO: check for group membership after password update... active_flip there too
		if (!$update_password)
		{
			// Now we need to demote the user from the inactive group and add him to the registered group

			include_once($site_file_root.'includes/forums/functions_user.php');
			user_active_flip($row['user_id'], $row['user_type'], '', $row['username']);
		}
		
		if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN && !$update_password)
		{
			include_once($site_file_root.'includes/forums/functions_messenger.php');

			$messenger = new messenger();

			$messenger->template('admin_welcome_activated', $row['user_lang']);
			
			$messenger->replyto($config['board_contact']);
			$messenger->to($row['user_email'], $row['username']);

			$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
			$messenger->headers('X-AntiAbuse: User_id - ' . $_CLASS['core_user']->data['user_id']);
			$messenger->headers('X-AntiAbuse: Username - ' . $_CLASS['core_user']->data['username']);
			$messenger->headers('X-AntiAbuse: User IP - ' . $_CLASS['core_user']->ip);

			$messenger->assign_vars(array(
				'SITENAME'	=> $_CORE_CONFIG['global']['sitename'],
				'USERNAME'	=> $row['username'],
				'EMAIL_SIG' => str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']))
			);

			$messenger->send($row['user_notify_type']);
			$messenger->save_queue();

			$message = 'ACCOUNT_ACTIVE_ADMIN';
		}
		else
		{
			$message = (!$update_password) ? 'ACCOUNT_ACTIVE' : 'PASSWORD_ACTIVATED';
		}

		if (!$update_password)
		{
			set_config('newest_user_id', $row['user_id']);
			set_config('newest_username', $row['username']);
			set_config('num_users', $config['num_users'] + 1, TRUE);
		}

		$_CLASS['core_display']->meta_refresh(3, generate_link());
		trigger_error($_CLASS['core_user']->lang[$message]);
	}
}

?>