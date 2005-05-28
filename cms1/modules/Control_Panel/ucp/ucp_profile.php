<?php
// -------------------------------------------------------------
//
// $Id: ucp_profile.php,v 1.38 2004/09/16 18:33:21 acydburn Exp $
//
// FILENAME  : ucp_profile.php
// STARTED   : Mon May 19, 2003
// COPYRIGHT : © 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

class ucp_profile extends module
{
	function ucp_profile($id, $mode)
	{
		global $config, $db, $_CLASS, $site_file_root, $SID, $_CORE_CONFIG, $phpEx;
		$_CLASS['core_template']->assign(array(
			'S_PRIVMSGS'		=>  false,
			'profile_fields'	=>  false)

		);
		
		$s_hidden_fields = '';
		
		$_CLASS['core_user']->add_lang('posting','Forums');

		$preview	= (!empty($_POST['preview'])) ? true : false;
		$submit		= (!empty($_POST['submit'])) ? true : false;
		$delete		= (!empty($_POST['delete'])) ? true : false;
		$error = $data = array();
		$s_hidden_fields = '';
		
		switch ($mode)
		{
			case 'reg_details':

				if ($submit)
				{
					$var_ary = array(
						'username'			=> $_CLASS['core_user']->data['username'], 
						'email'				=> $_CLASS['core_user']->data['user_email'], 
						'email_confirm'		=> (string) '',
						'new_password'		=> (string) '', 
						'cur_password'		=> (string) '', 
						'password_confirm'	=> (string) '', 
					);

					foreach ($var_ary as $var => $default)
					{
						$data[$var] = request_var($var, $default);
					}

					$var_ary = array(
						'username'			=> array(
							array('string', false, $_CORE_CONFIG['user']['min_name_chars'], $_CORE_CONFIG['user']['max_name_chars']), 
							array('username', $data['username'])),
						'password_confirm'	=> array('string', true, $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 
						'new_password'		=> array('string', true, $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 
						'cur_password'		=> array('string', true, $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 
						'email'				=> array(
							array('string', false, 6, 60), 
							array('email', $data['email'])),
						'email_confirm'		=> array('string', true, 6, 60), 
					);

					$error = validate_data($data, $var_ary);
					extract($data);
					unset($data);

					if ($_CLASS['auth']->acl_get('u_chgpasswd') && $new_password && $password_confirm != $new_password)
					{
						$error[] = 'NEW_PASSWORD_ERROR';
					}

					if (($new_password || ($_CLASS['auth']->acl_get('u_chgemail') && $email != $_CLASS['core_user']->data['user_email']) || ($username != $_CLASS['core_user']->data['username'] && $_CLASS['auth']->acl_get('u_chgname') && $_CORE_CONFIG['user']['allow_namechange'])) && md5($cur_password) != $_CLASS['core_user']->data['user_password'])
					{
						$error[] = 'CUR_PASSWORD_ERROR';
					}

					if ($_CLASS['auth']->acl_get('u_chgemail') && $email != $_CLASS['core_user']->data['user_email'] && $email_confirm != $email)
					{
						$error[] = 'NEW_EMAIL_ERROR';
					}

					if (!sizeof($error))
					{
						$sql_ary = array(
							'username'			=> ($_CLASS['auth']->acl_get('u_chgname') && $_CORE_CONFIG['user']['allow_namechange']) ? $username : $_CLASS['core_user']->data['username'], 
							'user_email'		=> ($_CLASS['auth']->acl_get('u_chgemail')) ? $email : $_CLASS['core_user']->data['user_email'], 
							'user_email_hash'	=> ($_CLASS['auth']->acl_get('u_chgemail')) ? crc32(strtolower($email)) . strlen($email) : $_CLASS['core_user']->data['user_email_hash'], 
							'user_password'		=> ($_CLASS['auth']->acl_get('u_chgpasswd') && $new_password) ? md5($new_password) : $_CLASS['core_user']->data['user_password'], 
							'user_passchg'		=> time(), 
						);

						if ($_CORE_CONFIG['email']['email_enable'] && $email != $_CLASS['core_user']->data['user_email'] && ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_SELF || $_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN))
						{
							require_once($site_file_root.'includes/forums/functions_messenger.'.$phpEx);

							$server_url = generate_board_url();

							$user_actkey = gen_rand_string(10);
							$key_len = 54 - (strlen($server_url));
							$key_len = ($key_len > 6) ? $key_len : 6;
							$user_actkey = substr($user_actkey, 0, $key_len);

							$messenger = new messenger();

							$template_file = ($config['require_activation'] == USER_ACTIVATION_ADMIN) ? 'user_activate_inactive' : 'user_activate';
							$messenger->template($template_file, $_CLASS['core_user']->data['user_lang']);
							$messenger->subject($subject);

							$messenger->replyto($config['board_contact']);
							$messenger->to($email, $username);

							$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
							$messenger->headers('X-AntiAbuse: User_id - ' . $_CLASS['core_user']->data['user_id']);
							$messenger->headers('X-AntiAbuse: Username - ' . $_CLASS['core_user']->data['username']);
							$messenger->headers('X-AntiAbuse: User IP - ' . $_CLASS['core_user']->ip);

							$messenger->assign_vars(array(
								'SITENAME'		=> $_CORE_CONFIG['global']['sitename'],
								'USERNAME'		=> $username,
								'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
								'U_ACTIVATE'    => getlink("Control_Panel&amp;mode=activate&u={$_CLASS['core_user']->data['user_id']}&k=$user_actkey"))
							);

							$messenger->send(NOTIFY_EMAIL);

							if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN)
							{
								// Grab an array of user_id's with a_user permissions
								$admin_ary = $_CLASS['auth']->acl_get_list(false, 'a_user', false);

								$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type
									FROM ' . USERS_TABLE . ' 
									WHERE user_id IN (' . implode(', ', $admin_ary[0]['a_user']) .')';
								$result = $db->sql_query($sql);

								while ($row = $db->sql_fetchrow($result))
								{
									$messenger->template('admin_activate', $row['user_lang']);
									$messenger->replyto($config['board_contact']);
									$messenger->to($row['user_email'], $row['username']);
									$messenger->im($row['user_jabber'], $row['username']);

									$messenger->assign_vars(array(
										'USERNAME'		=> $username,
										'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
										'U_ACTIVATE'    => getlink("Control_Panel&amp;mode=activate&u={$_CLASS['core_user']->data['user_id']}&k=$user_actkey"))
									);

									$messenger->send($row['user_notify_type']);
								}
								$db->sql_freeresult($result);
							}

							$messenger->save_queue();

							$sql_ary += array(
								'user_type'		=> USER_INACTIVE,
								'user_actkey'	=> $user_actkey
							);
						}

						$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' 
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
						$db->sql_query($sql);

						// Need to update config, forum, topic, posting, messages, etc.
						if ($username != $_CLASS['core_user']->data['username'] && $_CLASS['auth']->acl_get('u_chgname') && $_CORE_CONFIG['user']['allow_namechange'])
						{
							user_update_name($_CLASS['core_user']->data['username'], $username);
						}

						$_CLASS['core_display']->meta_refresh(3, getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode"));
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode").'">', '</a>');
						
						trigger_error($message);
					}
					// Replace "error" strings with their real, localised form
					$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error);
				}

				$user_char_ary = array('.*' => 'USERNAME_CHARS_ANY', '[\w]+' => 'USERNAME_ALPHA_ONLY', '[\w_\+\. \-\[\]]+' => 'USERNAME_ALPHA_SPACERS');

				$_CLASS['core_template']->assign(array(
					'ERROR'				=> (sizeof($error)) ? implode('<br />', $error) : '',

					'USERNAME'			=> (isset($username)) ? $username : $_CLASS['core_user']->data['username'],
					'EMAIL'				=> (isset($email)) ? $email : $_CLASS['core_user']->data['user_email'],
					'CONFIRM_EMAIL'		=> '',
					'PASSWORD_CONFIRM'	=> (isset($password_confirm)) ? $password_confirm : '',
					'NEW_PASSWORD'		=> (isset($new_password)) ? $new_password : '',
					
					'CUR_PASSWORD'					=> '', 
					'L_UCP_PROFILE_REG_WELCOME'		=> $_CLASS['core_user']->lang['UCP_PROFILE_REG_WELCOME'],
					'L_EMAIL_ADDRESS'				=> $_CLASS['core_user']->lang['EMAIL_ADDRESS'],
					'L_CONFIRM_EMAIL'				=> $_CLASS['core_user']->lang['CONFIRM_EMAIL'],
					'L_CONFIRM_EMAIL_EXPLAIN'		=> $_CLASS['core_user']->lang['CONFIRM_EMAIL_EXPLAIN'],
					'L_CHANGE_PASSWORD'				=> $_CLASS['core_user']->lang['CHANGE_PASSWORD'],
					'L_CONFIRM_PASSWORD'			=> $_CLASS['core_user']->lang['CONFIRM_PASSWORD'],
					'L_CONFIRM_PASSWORD_EXPLAIN'	=> $_CLASS['core_user']->lang['CONFIRM_PASSWORD_EXPLAIN'],
					'L_CURRENT_PASSWORD'			=> $_CLASS['core_user']->lang['CURRENT_PASSWORD'],
					'L_CURRENT_PASSWORD_EXPLAIN'	=> $_CLASS['core_user']->lang['CURRENT_PASSWORD_EXPLAIN'],
					'L_SUBMIT'						=> $_CLASS['core_user']->lang['SUBMIT'],
					'L_RESET'						=> $_CLASS['core_user']->lang['RESET'],
					
					'L_USERNAME_EXPLAIN'		=> sprintf($_CLASS['core_user']->lang[$user_char_ary[str_replace('\\\\', '\\', $_CORE_CONFIG['user']['allow_name_chars'])] . '_EXPLAIN'], $_CORE_CONFIG['user']['min_name_chars'], $_CORE_CONFIG['user']['max_name_chars']), 
					'L_CHANGE_PASSWORD_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['CHANGE_PASSWORD_EXPLAIN'], $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 
				
					'S_FORCE_PASSWORD'	=> ($_CORE_CONFIG['user']['chg_passforce'] && $this->data['user_passchg'] < time() - $_CORE_CONFIG['user']['chg_passforce']) ? true : false, 
					'S_CHANGE_USERNAME' => ($_CORE_CONFIG['user']['allow_namechange'] && $_CLASS['auth']->acl_get('u_chgname')) ? true : false, 
					'S_CHANGE_EMAIL'	=> ($_CLASS['auth']->acl_get('u_chgemail')) ? true : false,
					'S_CHANGE_PASSWORD'	=> ($_CLASS['auth']->acl_get('u_chgpasswd')) ? true : false)
				);
				break;

			case 'profile_info':

				include($site_file_root.'includes/forums/functions_profile_fields.' . $phpEx);
				include($site_file_root.'includes/forums/message_parser.'.$phpEx);
				// TODO: The posting file is included because $message_parser->decode_message() relies on decode_message() in the posting functions.
				include($site_file_root.'includes/forums/functions_posting.'.$phpEx);
				 
				$cp = new custom_profile();

				$cp_data = $cp_error = array();

				if ($submit)
				{
					$var_ary = array(
						'icq'			=> (string) '', 
						'aim'			=> (string) '', 
						'msn'			=> (string) '', 
						'yim'			=> (string) '', 
						'jabber'		=> (string) '', 
						'website'		=> (string) '', 
						'location'		=> (string) '',
						'occupation'	=> (string) '',
						'interests'		=> (string) '',
						'bday_day'		=> 0,
						'bday_month'	=> 0,
						'bday_year'		=> 0,
					);

					foreach ($var_ary as $var => $default)
					{
						$data[$var] = request_var($var, $default);
					}

					$var_ary = array(
						'icq'			=> array(
							array('string', true, 3, 15), 
							array('match', true, '#^[0-9]+$#i')), 
						'aim'			=> array('string', true, 5, 255), 
						'msn'			=> array('string', true, 5, 255), 
						'jabber'		=> array(
							array('string', true, 5, 255), 
							array('match', true, '#^[a-z0-9\.\-_\+]+?@(.*?\.)*?[a-z0-9\-_]+?\.[a-z]{2,4}(/.*)?$#i')),
						'yim'			=> array('string', true, 5, 255), 
						'website'		=> array(
							array('string', true, 12, 255), 
							array('match', true, '#^http[s]?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i')), 
						'location'		=> array('string', true, 2, 255), 
						'occupation'	=> array('string', true, 2, 500), 
						'interests'		=> array('string', true, 2, 500), 
						'bday_day'		=> array('num', true, 1, 31),
						'bday_month'	=> array('num', true, 1, 12),
						'bday_year'		=> array('num', true, 1901, gmdate('Y', time())),
					);

					$error = validate_data($data, $var_ary);
					extract($data);
					unset($data);

					// validate custom profile fields
					$cp->submit_cp_field('profile', $_CLASS['core_user']->get_iso_lang_id(), $cp_data, $cp_error);

					if (sizeof($cp_error))
					{
						$error = array_merge($error, $cp_error);
					}
					
					if (!sizeof($error))
					{
						$sql_ary = array(
							'user_icq'		=> $icq,
							'user_aim'		=> $aim,
							'user_msnm'		=> $msn,
							'user_yim'		=> $yim,
							'user_jabber'	=> $jabber,
							'user_website'	=> $website,
							'user_from'		=> $location,
							'user_occ'		=> $occupation,
							'user_interests'=> $interests,
							'user_birthday'	=> sprintf('%2d-%2d-%4d', $bday_day, $bday_month, $bday_year),
						);

						$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
						$db->sql_query($sql);

						// Update Custom Fields
						if (sizeof($cp_data))
						{
							$sql = 'UPDATE ' . PROFILE_DATA_TABLE . ' 
								SET ' . $db->sql_build_array('UPDATE', $cp_data) . '
								WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
							$db->sql_query($sql);

							if (!$db->sql_affectedrows())
							{
								$cp_data['user_id'] = (int) $_CLASS['core_user']->data['user_id'];

								$db->return_on_error = true;

								$sql = 'INSERT INTO ' . PROFILE_DATA_TABLE . ' ' . $db->sql_build_array('INSERT', $cp_data);
								$db->sql_query($sql);

								$db->return_on_error = false;
							}
						}

						$_CLASS['core_display']->meta_refresh(3, getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode"));
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode").'">', '</a>');
						trigger_error($message);
					}
					// Replace "error" strings with their real, localised form
					$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error);
				}

				if (!isset($bday_day))
				{
					list($bday_day, $bday_month, $bday_year) = explode('-', $_CLASS['core_user']->data['user_birthday']);
				}

				$s_birthday_day_options = '<option value="0"' . ((!$bday_day) ? ' selected="selected"' : '') . '>--</option>';
				for ($i = 1; $i < 32; $i++)
				{
					$selected = ($i == $bday_day) ? ' selected="selected"' : '';
					$s_birthday_day_options .= "<option value=\"$i\"$selected>$i</option>";
				}

				$s_birthday_month_options = '<option value="0"' . ((!$bday_month) ? ' selected="selected"' : '') . '>--</option>';
				for ($i = 1; $i < 13; $i++)
				{
					$selected = ($i == $bday_month) ? ' selected="selected"' : '';
					$s_birthday_month_options .= "<option value=\"$i\"$selected>$i</option>";
				}
				$s_birthday_year_options = '';

				$now = getdate();
				$s_birthday_year_options = '<option value="0"' . ((!$bday_year) ? ' selected="selected"' : '') . '>--</option>';
				for ($i = $now['year'] - 100; $i < $now['year']; $i++)
				{
					$selected = ($i == $bday_year) ? ' selected="selected"' : '';
					$s_birthday_year_options .= "<option value=\"$i\"$selected>$i</option>";
				}
				unset($now);

				$_CLASS['core_template']->assign(array(
					'ERROR'		=> (sizeof($error)) ? implode('<br />', $error) : '',

					'ICQ'		=> (isset($icq)) ? $icq : $_CLASS['core_user']->data['user_icq'], 
					'YIM'		=> (isset($yim)) ? $yim : $_CLASS['core_user']->data['user_yim'], 
					'AIM'		=> (isset($aim)) ? $aim : $_CLASS['core_user']->data['user_aim'], 
					'MSN'		=> (isset($msn)) ? $msn : $_CLASS['core_user']->data['user_msnm'], 
					'JABBER'	=> (isset($jabber)) ? $jabber : $_CLASS['core_user']->data['user_jabber'], 
					'WEBSITE'	=> (isset($website)) ? $website : $_CLASS['core_user']->data['user_website'], 
					'LOCATION'	=> (isset($location)) ? $location : $_CLASS['core_user']->data['user_from'], 
					'OCCUPATION'=> (isset($occupation)) ? $occupation : $_CLASS['core_user']->data['user_occ'], 
					'INTERESTS'	=> (isset($interests)) ? $interests : $_CLASS['core_user']->data['user_interests'], 

					'S_BIRTHDAY_DAY_OPTIONS'	=> $s_birthday_day_options, 
					'S_BIRTHDAY_MONTH_OPTIONS'	=> $s_birthday_month_options, 
					'S_BIRTHDAY_YEAR_OPTIONS'	=> $s_birthday_year_options,)
				);
				
				// Get additional profile fields and assign them to the template block var 'profile_fields'
				$_CLASS['core_user']->get_profile_fields($_CLASS['core_user']->data['user_id']);

				$cp->generate_profile_fields('profile', $_CLASS['core_user']->get_iso_lang_id());

				break;

			case 'signature':

				if (!$_CLASS['auth']->acl_get('u_sig'))
				{
					trigger_error('NO_AUTH_SIGNATURE');
				}
				
				require($site_file_root.'includes/forums/functions_posting.'.$phpEx);
				
				$enable_html	= ($config['allow_sig_html']) ? request_var('enable_html', false) : false;
				$enable_bbcode	= ($config['allow_sig_bbcode']) ? request_var('enable_bbcode', $_CLASS['core_user']->optionget('bbcode')) : false;
				$enable_smilies = ($config['allow_sig_smilies']) ? request_var('enable_smilies', $_CLASS['core_user']->optionget('smilies')) : false;
				$enable_urls	= request_var('enable_urls', true);
				$signature		= request_var('signature', $_CLASS['core_user']->data['user_sig']);
				
				if ($submit || $preview)
				{
					require_once($site_file_root.'includes/forums/message_parser.'.$phpEx);
					
					if (!sizeof($error))
					{
						$message_parser = new parse_message($signature);
	
						// Allowing Quote BBCode
						$message_parser->parse($enable_html, $enable_bbcode, $enable_urls, $enable_smilies, $config['allow_sig_img'], $config['allow_sig_flash'], true, true, 'sig');
						
						if (sizeof($message_parser->warn_msg))
						{
							$error[] = implode('<br />', $message_parser->warn_msg);
						}
						
						if (!sizeof($error) && $submit)
						{
							$sql_ary = array(
								'user_sig'					=> (string) $message_parser->message, 
								'user_sig_bbcode_uid'		=> (string) $message_parser->bbcode_uid, 
								'user_sig_bbcode_bitfield'	=> (int) $message_parser->bbcode_bitfield
							);
	
							$sql = 'UPDATE ' . USERS_TABLE . ' 
								SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' 
								WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
							$db->sql_query($sql);
	
							$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode").'\>', '</a>');
							trigger_error($message);
						}
					}
					// Replace "error" strings with their real, localised form
					$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error);
				}

				$signature_preview = '';
				if ($preview)
				{
					// Now parse it for displaying
					$signature_preview = $message_parser->format_display($enable_html, $enable_bbcode, $enable_urls, $enable_smilies, false);
					unset($message_parser);
				}
				
				decode_message($signature, $_CLASS['core_user']->data['user_sig_bbcode_uid']);

				$_CLASS['core_template']->assign(array(
					'ERROR'				=> (sizeof($error)) ? implode('<br />', $error) : '', 
					'SIGNATURE'			=> $signature,
					'SIGNATURE_PREVIEW'	=> $signature_preview, 
					
					'S_HTML_CHECKED' 		=> (!$enable_html) ? 'checked="checked"' : '',
					'S_BBCODE_CHECKED' 		=> (!$enable_bbcode) ? 'checked="checked"' : '',
					'S_SMILIES_CHECKED' 	=> (!$enable_smilies) ? 'checked="checked"' : '',
					'S_MAGIC_URL_CHECKED' 	=> (!$enable_urls) ? 'checked="checked"' : '',

					'HTML_STATUS'			=> ($config['allow_sig_html']) ? $_CLASS['core_user']->lang['HTML_IS_ON'] : $_CLASS['core_user']->lang['HTML_IS_OFF'],
					'BBCODE_STATUS'			=> ($config['allow_sig_bbcode']) ? sprintf($_CLASS['core_user']->lang['BBCODE_IS_ON'], '<a href="' . "faq.$phpEx$SID&amp;mode=bbcode" . '" target="_phpbbcode">', '</a>') : sprintf($_CLASS['core_user']->lang['BBCODE_IS_OFF'], '<a href="' . "faq.$phpEx$SID&amp;mode=bbcode" . '" target="_phpbbcode">', '</a>'),
					'SMILIES_STATUS'		=> ($config['allow_sig_smilies']) ? $_CLASS['core_user']->lang['SMILIES_ARE_ON'] : $_CLASS['core_user']->lang['SMILIES_ARE_OFF'],
					'IMG_STATUS'			=> ($config['allow_sig_img']) ? $_CLASS['core_user']->lang['IMAGES_ARE_ON'] : $_CLASS['core_user']->lang['IMAGES_ARE_OFF'],
					'FLASH_STATUS'			=> ($config['allow_sig_flash']) ? $_CLASS['core_user']->lang['FLASH_IS_ON'] : $_CLASS['core_user']->lang['FLASH_IS_OFF'],

					'L_SIGNATURE_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['SIGNATURE_EXPLAIN'], $config['max_sig_chars']),

					'L_BBCODE_B_HELP'		=> $_CLASS['core_user']->lang['BBCODE_B_HELP'],
					'L_BBCODE_I_HELP'		=> $_CLASS['core_user']->lang['BBCODE_I_HELP'],
					'L_BBCODE_U_HELP'		=> $_CLASS['core_user']->lang['BBCODE_U_HELP'],
					'L_BBCODE_Q_HELP'		=> $_CLASS['core_user']->lang['BBCODE_Q_HELP'],
					'L_BBCODE_C_HELP'		=> $_CLASS['core_user']->lang['BBCODE_C_HELP'],
					'L_BBCODE_L_HELP'		=> $_CLASS['core_user']->lang['BBCODE_L_HELP'],
					'L_BBCODE_O_HELP'		=> $_CLASS['core_user']->lang['BBCODE_O_HELP'],
					'L_BBCODE_P_HELP'		=> $_CLASS['core_user']->lang['BBCODE_P_HELP'],
					'L_BBCODE_W_HELP'		=> $_CLASS['core_user']->lang['BBCODE_W_HELP'],
					'L_BBCODE_A_HELP'		=> $_CLASS['core_user']->lang['BBCODE_A_HELP'],
					'L_BBCODE_S_HELP'		=> $_CLASS['core_user']->lang['BBCODE_S_HELP'],
					'L_BBCODE_F_HELP'		=> $_CLASS['core_user']->lang['BBCODE_F_HELP'],
					'L_BBCODE_E_HELP'		=> $_CLASS['core_user']->lang['BBCODE_E_HELP'],
					'L_STYLES_TIP'			=> $_CLASS['core_user']->lang['STYLES_TIP'],
					'L_SIGNATURE'			=> $_CLASS['core_user']->lang['SIGNATURE'],	
					'L_SIGNATURE_PREVIEW'	=> $_CLASS['core_user']->lang['SIGNATURE_PREVIEW'],
					'L_FONT_SIZE'			=> $_CLASS['core_user']->lang['FONT_SIZE'],
					'L_FONT_TINY'			=> $_CLASS['core_user']->lang['FONT_TINY'],
					'L_FONT_SMALL'			=> $_CLASS['core_user']->lang['FONT_SMALL'],
					'L_FONT_NORMAL'			=> $_CLASS['core_user']->lang['FONT_NORMAL'],
					'L_FONT_LARGE'			=> $_CLASS['core_user']->lang['FONT_LARGE'],
					'L_FONT_HUGE'			=> $_CLASS['core_user']->lang['FONT_HUGE'],
					'L_CLOSE_TAGS'			=> $_CLASS['core_user']->lang['CLOSE_TAGS'],
					'L_DISABLE_HTML'		=> $_CLASS['core_user']->lang['DISABLE_HTML'],
					'L_DISABLE_BBCODE'		=> $_CLASS['core_user']->lang['DISABLE_BBCODE'],
					'L_DISABLE_SMILIES'		=> $_CLASS['core_user']->lang['DISABLE_SMILIES'],
					'L_DISABLE_MAGIC_URL'	=> $_CLASS['core_user']->lang['DISABLE_MAGIC_URL'],
					'L_PREVIEW'				=> $_CLASS['core_user']->lang['PREVIEW'],
					'L_SUBMIT'				=> $_CLASS['core_user']->lang['SUBMIT'],
					'L_RESET'				=> $_CLASS['core_user']->lang['RESET'],
					
					'S_HTML_ALLOWED'		=> $config['allow_sig_html'], 
					'S_BBCODE_ALLOWED'		=> $config['allow_sig_bbcode'], 
					'S_SMILIES_ALLOWED'		=> $config['allow_sig_smilies'],)
				);
				break;

			case 'avatar':

				$display_gallery = (isset($_POST['displaygallery'])) ? true : false;
				$category = request_var('category', '');
				$delete = (isset($_POST['delete'])) ? true : false;
				
				$avatarselect = request_var('avatarselect', '');
				$avatarselect = str_replace(array('../', '..\\', './', '.\\'), '', $avatarselect);
				if ($avatarselect && ($avatarselect{0} == '/' || $avatarselect{0} == "\\"))
				{
					 $avatarselect = '';
				}
				
				// Can we upload? 
				$can_upload = ($config['allow_avatar_upload'] && file_exists($config['avatar_path']) && is_writeable($config['avatar_path']) && $_CLASS['auth']->acl_get('u_chgavatar') && (@ini_get('file_uploads') || strtolower(@ini_get('file_uploads')) == 'on')) ? true : false;

				if ($submit)
				{
					$var_ary = array(
						'uploadurl'		=> (string) '', 
						'remotelink'	=> (string) '', 
						'width'			=> (string) '',
						'height'		=> (string) '' 
					);

					foreach ($var_ary as $var => $default)
					{
						$data[$var] = request_var($var, $default);
					}

					$var_ary = array(
						'uploadurl'		=> array('string', true, 5, 255), 
						'remotelink'	=> array('string', true, 5, 255), 
						'width'			=> array('string', true, 1, 3), 
						'height'		=> array('string', true, 1, 3), 
					);

					$error = validate_data($data, $var_ary);
					
					if (!empty($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == '2')
					{
						$error[] = 'Sorry the avator file size is to big';
						//trigger_error('Sorry the avator file size is to big');
					}
					
					if (!sizeof($error))
					{
						$data['user_id'] = $_CLASS['core_user']->data['user_id'];

						if ((!empty($_FILES['uploadfile']['name']) || $data['uploadurl']) && $can_upload)
						{
							list($type, $filename, $width, $height) = avatar_upload($data, $error);
						}
						else if ($data['remotelink'] && $_CLASS['auth']->acl_get('u_chgavatar') && $config['allow_avatar_remote'])
						{
							list($type, $filename, $width, $height) = avatar_remote($data, $error);
						}
						else if ($avatarselect && $_CLASS['auth']->acl_get('u_chgavatar') && $config['allow_avatar_local'])
						{
							$type = AVATAR_GALLERY;
							$filename = $avatarselect;

							list($width, $height) = getimagesize($config['avatar_gallery_path'] . '/' . $filename);
						}
						else if ($delete && $_CLASS['auth']->acl_get('u_chgavatar'))
						{
							$type = $filename = $width = $height = '';
						}
					}

					if (!sizeof($error))
					{
						// Do we actually have any data to update?
						if (sizeof($data))
						{
							$sql_ary = array(
								'user_avatar'			=> $filename, 
								'user_avatar_type'		=> $type, 
								'user_avatar_width'		=> $width, 
								'user_avatar_height'	=> $height, 
							);

							$sql = 'UPDATE ' . USERS_TABLE . ' 
								SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' 
								WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
							$db->sql_query($sql);

							// Delete old avatar if present
							if ($_CLASS['core_user']->data['user_avatar'] && $filename != $_CLASS['core_user']->data['user_avatar'] && $_CLASS['core_user']->data['user_avatar_type'] != AVATAR_GALLERY)
							{
								avatar_delete($_CLASS['core_user']->data['user_avatar']);
							}
						}

						$_CLASS['core_display']->meta_refresh(3, getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode"));
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode").'">', '</a>');
						trigger_error($message);
					}

					extract($data);
					unset($data);

					// Replace "error" strings with their real, localised form
					$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error);
				}

				// Generate users avatar
				$avatar_img = '';
				if ($_CLASS['core_user']->data['user_avatar'])
				{
					switch ($_CLASS['core_user']->data['user_avatar_type'])
					{
						case AVATAR_UPLOAD:
							$avatar_img = $config['avatar_path'] . '/';
							break;
						case AVATAR_GALLERY:
							$avatar_img = $config['avatar_gallery_path'] . '/';
							break;
					}
					$avatar_img .= $_CLASS['core_user']->data['user_avatar'];

					$avatar_img = '<img src="' . $avatar_img . '" width="' . $_CLASS['core_user']->data['user_avatar_width'] . '" height="' . $_CLASS['core_user']->data['user_avatar_height'] . '" border="0" alt="" />';
				}

				$_CLASS['core_template']->assign(array(
					'ERROR'			=> (sizeof($error)) ? implode('<br />', $error) : '', 
					'AVATAR'		=> $avatar_img, 
					'AVATAR_SIZE'	=> $config['avatar_filesize'], 

					'S_FORM_ENCTYPE'				=> ($can_upload) ? ' enctype="multipart/form-data"' : '', 
					'L_CURRENT_IMAGE'				=> $_CLASS['core_user']->lang['CURRENT_IMAGE'],
					'L_UPLOAD_AVATAR_FILE'			=> $_CLASS['core_user']->lang['UPLOAD_AVATAR_FILE'],
					'L_UPLOAD_AVATAR_URL'			=> $_CLASS['core_user']->lang['UPLOAD_AVATAR_URL'],
					'L_UPLOAD_AVATAR_URL_EXPLAIN'	=> $_CLASS['core_user']->lang['UPLOAD_AVATAR_URL_EXPLAIN'],
					'L_LINK_REMOTE_AVATAR'			=> $_CLASS['core_user']->lang['LINK_REMOTE_AVATAR'],
					'L_LINK_REMOTE_AVATAR_EXPLAIN'	=> $_CLASS['core_user']->lang['LINK_REMOTE_AVATAR_EXPLAIN'],
					'L_LINK_REMOTE_SIZE'			=> $_CLASS['core_user']->lang['LINK_REMOTE_SIZE'],
					'L_LINK_REMOTE_SIZE_EXPLAIN'	=> $_CLASS['core_user']->lang['LINK_REMOTE_SIZE_EXPLAIN'],
					'L_DISPLAY_GALLERY'				=> $_CLASS['core_user']->lang['DISPLAY_GALLERY'],
					'L_AVATAR_GALLERY'				=> $_CLASS['core_user']->lang['AVATAR_GALLERY'],
					'L_DELETE_AVATAR'				=> $_CLASS['core_user']->lang['DELETE_AVATAR'],
					'L_AVATAR_CATEGORY'				=> $_CLASS['core_user']->lang['AVATAR_CATEGORY'],
					'L_AVATAR_PAGE'					=> $_CLASS['core_user']->lang['AVATAR_PAGE'],
					'L_GO'							=> $_CLASS['core_user']->lang['GO'],
					'L_CANCEL'						=> $_CLASS['core_user']->lang['CANCEL'],
					'L_SUBMIT'						=> $_CLASS['core_user']->lang['SUBMIT'],
					'L_RESET'						=> $_CLASS['core_user']->lang['RESET'],
					
					'L_AVATAR_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['AVATAR_EXPLAIN'], $config['avatar_max_width'], $config['avatar_max_height'], round($config['avatar_filesize'] / 1024)),)
				);

				if ($display_gallery && $_CLASS['auth']->acl_get('u_chgavatar') && $config['allow_avatar_local'])
				{
					$avatar_list = avatar_gallery($category, $error);
					$category = (!$category) ? key($avatar_list) : $category;

					$s_category_options = '';
					foreach (array_keys($avatar_list) as $cat)
					{
						$s_category_options .= '<option value="' . $cat . '"' . (($cat == $category) ? ' selected="selected"' : '') . '>' . $cat . '</option>';
					}

					$_CLASS['core_template']->assign(array(
						'S_DISPLAY_GALLERY'	=> true,
						'S_CAT_OPTIONS'		=> $s_category_options)
					);

					$avatar_list = $avatar_list[$category];

					$row = 0;
					foreach ($avatar_list as $avatar_row_ary)
					{
						$_CLASS['core_template']->assign_vars_array('avatar_row', array());

						foreach ($avatar_row_ary as $avatar_col_ary)
						{
							$_CLASS['core_template']->assign_vars_array('avatar_column', array(
								'ROW'			=> $row,
								'AVATAR_IMAGE'	=> $config['avatar_gallery_path'] . '/' . $avatar_col_ary['file'],
								'AVATAR_NAME'	=> $avatar_col_ary['name'],
								'AVATAR_FILE'	=> $avatar_col_ary['file'])
							);

							$_CLASS['core_template']->assign_vars_array('avatar_option_column', array(
								'ROW'				=> $row,
								'AVATAR_IMAGE'		=> $config['avatar_gallery_path'] . '/' . $avatar_col_ary['file'],
								'S_OPTIONS_AVATAR'	=> $avatar_col_ary['file'])
							);
						}
						$row ++;
					}
					unset($avatar_list);			
				}
				else
				{
					$_CLASS['core_template']->assign(array(
						'AVATAR'		=> $avatar_img,
						'AVATAR_SIZE'	=> $config['avatar_filesize'],
						'WIDTH'			=> (isset($width)) ? $width : $_CLASS['core_user']->data['user_avatar_width'],
						'HEIGHT'		=> (isset($height)) ? $height : $_CLASS['core_user']->data['user_avatar_height'],

						'S_UPLOAD_AVATAR_FILE'	=> $can_upload,
						'S_UPLOAD_AVATAR_URL'	=> $can_upload,
						'S_LINK_AVATAR'			=> ($_CLASS['auth']->acl_get('u_chgavatar') && $config['allow_avatar_remote']) ? true : false,
						'S_GALLERY_AVATAR'		=> ($_CLASS['auth']->acl_get('u_chgavatar') && $config['allow_avatar_local']) ? true : false,
						//'S_AVATAR_CAT_OPTIONS'	=> $s_categories,
						//'S_AVATAR_PAGE_OPTIONS'	=> $s_pages,
						)
					);
				}

				break;
		}

		$_CLASS['core_template']->assign(array(
			'L_TITLE'					=> $_CLASS['core_user']->lang['UCP_PROFILE_' . strtoupper($mode)],
			
			'L_PROFILE_INFO_NOTICE'		=> $_CLASS['core_user']->lang['PROFILE_INFO_NOTICE'],
			'L_UCP_ICQ'					=> $_CLASS['core_user']->lang['UCP_ICQ'],
			'L_UCP_AIM'					=> $_CLASS['core_user']->lang['UCP_AIM'],
			'L_UCP_MSNM'				=> $_CLASS['core_user']->lang['UCP_MSNM'],
			'L_UCP_YIM'					=> $_CLASS['core_user']->lang['UCP_YIM'],
			'L_PROFILE_INFO_NOTICE'		=> $_CLASS['core_user']->lang['PROFILE_INFO_NOTICE'],
			'L_UCP_JABBER'				=> $_CLASS['core_user']->lang['UCP_JABBER'],
			'L_WEBSITE'					=> $_CLASS['core_user']->lang['WEBSITE'],
			'L_LOCATION'				=> $_CLASS['core_user']->lang['LOCATION'],
			'L_OCCUPATION'				=> $_CLASS['core_user']->lang['OCCUPATION'],
			'L_INTERESTS'				=> $_CLASS['core_user']->lang['INTERESTS'],
			'L_BIRTHDAY'				=> $_CLASS['core_user']->lang['BIRTHDAY'],
			'L_BIRTHDAY_EXPLAIN'		=> $_CLASS['core_user']->lang['BIRTHDAY_EXPLAIN'],
			'L_DAY'						=> $_CLASS['core_user']->lang['DAY'],
			'L_MONTH'					=> $_CLASS['core_user']->lang['MONTH'],
			'L_YEAR'					=> $_CLASS['core_user']->lang['YEAR'],
			'L_SUBMIT'					=> $_CLASS['core_user']->lang['SUBMIT'],
			'L_RESET'					=> $_CLASS['core_user']->lang['RESET'],
		
			'S_HIDDEN_FIELDS'			=> $s_hidden_fields,
			'S_UCP_ACTION'				=> getlink("Control_Panel$SID&amp;i=$id&amp;mode=$mode"))
		);

		$this->display($_CLASS['core_user']->lang['UCP_PROFILE'], 'ucp_profile_' . $mode . '.html');
	}
}

?>