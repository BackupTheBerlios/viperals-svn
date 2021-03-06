<?php
// -------------------------------------------------------------
//
// $Id: ucp_profile.php,v 1.38 2004/09/16 18:33:21 acydburn Exp $
//
// FILENAME  : ucp_profile.php
// STARTED   : Mon May 19, 2003
// COPYRIGHT : � 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

class ucp_profile extends module
{
	function ucp_profile($id, $mode)
	{
		global $config, $_CLASS, $site_file_root, $_CORE_CONFIG;
		
		$preview	= isset($_POST['preview']);
		$submit		= isset($_POST['submit']);

		$module_link	= generate_link("Control_Panel&amp;i=$id&amp;mode=$mode");

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
						//'cur_password'		=> array('string', true, $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 
						'email'				=> array(
							array('string', false, 6, 60), 
							array('email', $data['email'])),
						'email_confirm'		=> array('string', true, 6, 60), 
					);

					$error = validate_data($data, $var_ary);
					extract($data);
					unset($data);

					if ($new_password && $password_confirm != $new_password)
					{
						$error[] = 'NEW_PASSWORD_ERROR';
					}

					if (($new_password || ($_CLASS['auth']->acl_get('u_chgemail') && $email != $_CLASS['core_user']->data['user_email']) || ($username != $_CLASS['core_user']->data['username'] && $_CLASS['auth']->acl_get('u_chgname') && $_CORE_CONFIG['user']['allow_namechange'])) && encode_password($cur_password, $_CLASS['core_user']->data['user_password_encoding']) != $_CLASS['core_user']->data['user_password'])
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
							//'username'			=> ($_CLASS['auth']->acl_get('u_chgname') && $_CORE_CONFIG['user']['allow_namechange']) ? $username : $_CLASS['core_user']->data['username'], 
							'user_email'		=> ($_CLASS['auth']->acl_get('u_chgemail')) ? $email : $_CLASS['core_user']->data['user_email'], 
							//'user_password'		=> ($new_password) ? md5($new_password) : $_CLASS['core_user']->data['user_password'], 
							//'user_passchg'		=> time(), 
						);

						if ($_CORE_CONFIG['email']['email_enable'] && $email != $_CLASS['core_user']->data['user_email'] && ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_SELF || $_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN))
						{
							$template_file = ($config['require_activation'] == USER_ACTIVATION_ADMIN) ? 'user_activate_inactive.html' : 'user_activate.html';
							$mailer = new core_mailer;

							$messenger->template($template_file, $_CLASS['core_user']->data['user_lang']);
							$mailer->subject($subject);

							$messenger->to($email, $username);
							$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
							$messenger->headers('X-AntiAbuse: User_id - ' . $_CLASS['core_user']->data['user_id']);
							$messenger->headers('X-AntiAbuse: Username - ' . $_CLASS['core_user']->data['username']);
							$messenger->headers('X-AntiAbuse: User IP - ' . $_CLASS['core_user']->ip);

							$messenger->assign_vars(array(
								'SITENAME'		=> $_CORE_CONFIG['global']['sitename'],
								'USERNAME'		=> $username,
								'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
								'U_ACTIVATE'    => generate_link("Control_Panel&amp;mode=activate&u={$_CLASS['core_user']->data['user_id']}&k=$user_actkey", array('full' => true)))
							);

							$body = trim($_CLASS['core_template']->display('modules/Contact/email/index.html', true));

							$messenger->send(NOTIFY_EMAIL);

							if ($_CORE_CONFIG['user']['require_activation'] == USER_ACTIVATION_ADMIN)
							{
								// Grab an array of user_id's with a_user permissions
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
										'USERNAME'		=> $username,
										'EMAIL_SIG'		=> str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),
										'U_ACTIVATE'    => generate_link("Control_Panel&amp;mode=activate&u={$_CLASS['core_user']->data['user_id']}&k=$user_actkey", array('full' => true)))
									);

									$messenger->send($row['user_notify_type']);
								}
								$_CLASS['core_db']->sql_freeresult($result);
							}

							$messenger->save_queue();

							$sql_ary += array(
								'user_type'		=> USER_INACTIVE,
								'user_actkey'	=> $user_actkey
							);
						}

						$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . ' 
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);

						// Need to update config, forum, topic, posting, messages, etc.
						if ($username != $_CLASS['core_user']->data['username'] && $_CLASS['auth']->acl_get('u_chgname') && $_CORE_CONFIG['user']['allow_namechange'])
						{
							user_update_name($_CLASS['core_user']->data['username'], $username);
						}

						$_CLASS['core_display']->meta_refresh(3, $module_link);
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.$module_link.'">', '</a>');
						
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
					
					'L_USERNAME_EXPLAIN'		=> sprintf($_CLASS['core_user']->lang[$user_char_ary[str_replace('\\\\', '\\', $_CORE_CONFIG['user']['allow_name_chars'])] . '_EXPLAIN'], $_CORE_CONFIG['user']['min_name_chars'], $_CORE_CONFIG['user']['max_name_chars']), 
					'L_CHANGE_PASSWORD_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['CHANGE_PASSWORD_EXPLAIN'], $_CORE_CONFIG['user']['min_pass_chars'], $_CORE_CONFIG['user']['max_pass_chars']), 
				
					'S_FORCE_PASSWORD'	=> ($_CORE_CONFIG['user']['chg_passforce'] && $this->data['user_passchg'] < time() - $_CORE_CONFIG['user']['chg_passforce']) ? true : false, 
					'S_CHANGE_USERNAME'	=> ($_CORE_CONFIG['user']['allow_namechange'] && $_CLASS['auth']->acl_get('u_chgname')) ? true : false, 
					'S_CHANGE_EMAIL'	=> ($_CLASS['auth']->acl_get('u_chgemail')) ? true : false,
					'S_CHANGE_PASSWORD'	=> true
				));
				break;

			case 'profile_info':

				$error = array();
				$this_year = gmdate('Y', time());
				
				if ($submit)
				{
					$icq	= get_variable('icq', 'POST', null);
					$aim	= get_variable('aim', 'POST', null);
					$msn	= get_variable('msn', 'POST', null);
					$yim	= get_variable('yim', 'POST', null);
					$jabber = get_variable('jabber', 'POST', null);
					//$google = get_variable('google', 'POST', null);

					$website	= get_variable('website', 'POST', null);
					$location	= get_variable('location', 'POST', null);				
					$occupation	= get_variable('occupation', 'POST', null);
					$interests	= get_variable('interests', 'POST', null);

					$bday_day	= get_variable('bday_day', 'POST', false);
					$bday_month	= get_variable('bday_month', 'POST', false);
					$bday_year	= get_variable('bday_year', 'POST', false);

					if ($bday_day || $bday_month || $bday_year)
					{
						if ($bday_day < 1 || $bday_day > 31 || $bday_month < 1 || $bday_month > 12 || $bday_year < ($this_year - 100) || $bday_month > $this_year)
						{
							$error[] = $_CLASS['core_user']->get_lang('BIRTHDAY_ERROR');
						}
					}

					if (mb_strlen($interests) > 255)
					{
						$error[] = $_CLASS['core_user']->get_lang('INTEREST_LONG_ERROR');
					}

					if (mb_strlen($occupation) > 255)
					{
						$error[] = $_CLASS['core_user']->get_lang('OCCUPATION_LONG_ERROR');
					}

					if (empty($error))
					{
						$sql_ary = array(
							'user_icq'		=> $icq,
							'user_aim'		=> $aim,
							'user_msnm'		=> $msn,
							'user_yim'		=> $yim,
							'user_jabber'	=> $jabber,
							//'user_google'	=> $google,
							'user_website'	=> $website,
							'user_from'		=> $location,
							'user_occ'		=> $occupation,
							'user_interests'=> $interests,
							'user_birthday'	=> ($bday_day) ? sprintf('%2d-%2d-%4d', $bday_day, $bday_month, $bday_year) : null,
						);
	
						$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);
	
						$_CLASS['core_display']->meta_refresh(3, $module_link);
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.$module_link.'">', '</a>');
						trigger_error($message);
					}
				}

				if (!isset($bday_day))
				{
					if ($_CLASS['core_user']->data['user_birthday'])
					{
						list($bday_day, $bday_month, $bday_year) = explode('-', $_CLASS['core_user']->data['user_birthday']);
					}
					else
					{
						$bday_day = $bday_month = $bday_year = '';
					}
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

				$s_birthday_year_options = '<option value="0"' . ((!$bday_year) ? ' selected="selected"' : '') . '>--</option>';

				$i = $this_year - 100;
				for ($i; $i < $this_year; $i++)
				{
					$selected = ($i == $bday_year) ? ' selected="selected"' : '';
					$s_birthday_year_options .= "<option value=\"$i\"$selected>$i</option>";
				}

				$_CLASS['core_template']->assign_array(array(
					'ERROR'		=> empty($error) ? '' : implode('<br />', $error),

					'ICQ'		=> isset($icq) ? $icq : $_CLASS['core_user']->data['user_icq'], 
					'YIM'		=> isset($yim) ? $yim : $_CLASS['core_user']->data['user_yim'], 
					'AIM'		=> isset($aim) ? $aim : $_CLASS['core_user']->data['user_aim'], 
					'MSN'		=> isset($msn) ? $msn : $_CLASS['core_user']->data['user_msnm'], 
					//'GOOGLE'	=> isset($google) ? $google : $_CLASS['core_user']->data['user_google'], 
					'JABBER'	=> isset($jabber) ? $jabber : $_CLASS['core_user']->data['user_jabber'], 
					'WEBSITE'	=> isset($website) ? $website : $_CLASS['core_user']->data['user_website'], 
					'LOCATION'	=> isset($location) ? $location : $_CLASS['core_user']->data['user_from'], 
					'OCCUPATION'=> isset($occupation) ? $occupation : $_CLASS['core_user']->data['user_occ'], 
					'INTERESTS'	=> isset($interests) ? $interests : $_CLASS['core_user']->data['user_interests'], 

					'S_BIRTHDAY_DAY_OPTIONS'	=> $s_birthday_day_options, 
					'S_BIRTHDAY_MONTH_OPTIONS'	=> $s_birthday_month_options, 
					'S_BIRTHDAY_YEAR_OPTIONS'	=> $s_birthday_year_options,
				));
			break;

			case 'signature':
				require($site_file_root.'includes/forums/functions_posting.php');
				
				// Generate smiley listing
				generate_smilies('inline', 0);
	
				$enable_html	= ($config['allow_sig_html']) ? isset($_POST['disable_html']) : false;
				$enable_bbcode	= ($config['allow_sig_bbcode']) ? (isset($_POST['disable_bbcode']) ? false : $_CLASS['core_user']->optionget('bbcode')) : false;
				$enable_smilies = ($config['allow_sig_smilies']) ? (isset($_POST['disable_smilies']) ? false : $_CLASS['core_user']->optionget('smilies')) : false;
				$enable_urls	= (isset($_POST['disable_magic_url'])) ? false : true;
				$signature		= request_var('signature', $_CLASS['core_user']->data['user_sig']);
				
				if ($submit || $preview)
				{
					require_once($site_file_root.'includes/forums/message_parser.php');
					
					if ($signature)
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
						}
					}
					else
					{
						$sql_ary = array(
							'user_sig'					=> '', 
							'user_sig_bbcode_uid'		=> '', 
							'user_sig_bbcode_bitfield'	=> (int) ''
						);
					}
					
					if (!sizeof($error) && $submit)
					{
						$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . ' 
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);
	
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.$module_link.'\>', '</a>');
						trigger_error($message);
					}
					
					// Replace "error" strings with their real, localised form
					$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error);
				}

				$signature_preview = '';
				if ($preview && $signature)
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
					'BBCODE_STATUS'			=> ($config['allow_sig_bbcode']) ? sprintf($_CLASS['core_user']->lang['BBCODE_IS_ON'], '<a href="' . generate_link('Forums&amp;file=faq&amp;mode=bbcode') . '" target="_phpbbcode">', '</a>') : sprintf($_CLASS['core_user']->lang['BBCODE_IS_OFF'], '<a href="' . generate_link('Forums&amp;file=faq&amp;mode=bbcode') . '" target="_phpbbcode">', '</a>'),
					'SMILIES_STATUS'		=> ($config['allow_sig_smilies']) ? $_CLASS['core_user']->lang['SMILIES_ARE_ON'] : $_CLASS['core_user']->lang['SMILIES_ARE_OFF'],
					'IMG_STATUS'			=> ($config['allow_sig_img']) ? $_CLASS['core_user']->lang['IMAGES_ARE_ON'] : $_CLASS['core_user']->lang['IMAGES_ARE_OFF'],
					'FLASH_STATUS'			=> ($config['allow_sig_flash']) ? $_CLASS['core_user']->lang['FLASH_IS_ON'] : $_CLASS['core_user']->lang['FLASH_IS_OFF'],

					'L_SIGNATURE_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['SIGNATURE_EXPLAIN'], $config['max_sig_chars']),

					'S_HTML_ALLOWED'		=> $config['allow_sig_html'], 
					'S_BBCODE_ALLOWED'		=> $config['allow_sig_bbcode'], 
					'S_SMILIES_ALLOWED'		=> $config['allow_sig_smilies'],)
				);
				break;

			case 'avatar':

				$display_gallery = isset($_POST['display_gallery']);
				$folder = isset($_REQUEST['category']) ? str_replace(array('../', '..\\', './', '.\\'), '', $_REQUEST['category']) : false;

				$delete = isset($_POST['delete']);

				// Can we upload? 
				$can_upload = (file_exists($config['avatar_path']) && is_writeable($config['avatar_path']) && @ini_get('file_uploads')) ? true : false;

				if ($submit)
				{
					$gallery_avatar = isset($_POST['avatarselect']) ? str_replace(array('../', '..\\', './', '.\\'), '', $_POST['avatarselect']) : false;

					if ($config['allow_avatar_local'] && $gallery_avatar)
					{
						if (!file_exists($config['avatar_gallery_path'] . '/' . $gallery_avatar))
						{
							$error[] = 'BAD_AVATAR';
						}
						else
						{
							$type = AVATAR_GALLERY;
							$filename = $gallery_avatar;

							list($width, $height) = getimagesize($config['avatar_gallery_path'] . '/' . $gallery_avatar);
						}
					}
					else
					{
						$data['uploadurl']	= get_variable('uploadurl', 'POST', false);
						$data['remotelink']	= get_variable('remotelink', 'POST', '');
						$data['width']		= get_variable('width', 'POST', '');
						$data['height']		= get_variable('height', 'POST', '');
						$data['user_id']	= $_CLASS['core_user']->data['user_id'];

						require_once($site_file_root.'includes/forums/functions_user.php');

						if ((!empty($_FILES['uploadfile']['name']) || $data['uploadurl']) && $can_upload)
						{
							list($type, $filename, $width, $height) = avatar_upload($data, $error);
						}
						elseif ($data['remotelink'] && $config['allow_avatar_remote'])
						{
							list($type, $filename, $width, $height) = avatar_remote($data, $error);
						}
						elseif ($delete)
						{
							$type = $filename = $width = $height = '';
						}
						else
						{
							$error[] = 'IM_LOST';
						}
					}

					if (empty($error))
					{
						$sql_ary = array(
							'user_avatar'			=> $filename, 
							'user_avatar_type'		=> $type, 
							'user_avatar_width'		=> $width, 
							'user_avatar_height'	=> $height, 
						);

						$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . ' 
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);

						// Delete old avatar if present
						if ($_CLASS['core_user']->data['user_avatar'] && $filename != $_CLASS['core_user']->data['user_avatar'] && $_CLASS['core_user']->data['user_avatar_type'] != AVATAR_GALLERY)
						{
							avatar_delete($_CLASS['core_user']->data['user_avatar']);
						}

						$_CLASS['core_display']->meta_refresh(3, $module_link);
						$message = $_CLASS['core_user']->lang['PROFILE_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.$module_link.'">', '</a>');
						trigger_error($message);
					}

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
					'ERROR'			=> empty($error) ? '' : implode('<br />', $error), 
					'AVATAR'		=> $avatar_img, 
					'AVATAR_SIZE'	=> $config['avatar_filesize'], 

					'S_FORM_ENCTYPE'	=> ($can_upload) ? ' enctype="multipart/form-data"' : '', 

					'L_AVATAR_EXPLAIN'	=> sprintf($_CLASS['core_user']->lang['AVATAR_EXPLAIN'], $config['avatar_max_width'], $config['avatar_max_height'], round($config['avatar_filesize'] / 1024)),)
				);

				if ($display_gallery && $config['allow_avatar_local'])
				{
					require_once($site_file_root.'includes/functions_user.php');

					$avatar_list = avatar_gallery($folder, $folders, $error);

					array_unshift($folders, '');

					$s_category_options = '';

					foreach ($folders as $cat)
					{
						$s_category_options .= '<option value="' . $cat . '"' . (($cat == $folder) ? ' selected="selected"' : '') . '>' . (($cat) ? $cat : '--') . '</option>';
					}

					$_CLASS['core_template']->assign(array(
						'S_DISPLAY_GALLERY'	=> true,
						'S_CAT_OPTIONS'		=> $s_category_options)
					);

					foreach ($avatar_list as $avatar)
					{
						$_CLASS['core_template']->assign_vars_array('avatar',  array(
							'AVATAR_IMAGE'		=> $config['avatar_gallery_path'] . '/' . $avatar['file'],
							'AVATAR_NAME'		=> $avatar['name'],
							'AVATAR_FILE'		=> $avatar['file'],
						));
					}
					unset($avatar_list);
				}
				else
				{
					$_CLASS['core_template']->assign(array(
						'AVATAR'		=> $avatar_img,
						'AVATAR_SIZE'	=> $config['avatar_filesize'],
						'WIDTH'			=> $_CLASS['core_user']->data['user_avatar_width'],
						'HEIGHT'		=> $_CLASS['core_user']->data['user_avatar_height'],

						'S_CAN_UPLOAD'		=> $can_upload,
						'S_LINK_AVATAR'		=> ($config['allow_avatar_remote']),
						'S_GALLERY_AVATAR'	=> ($config['allow_avatar_local']),
					));
				}

				break;
		}

		$_CLASS['core_template']->assign_array(array(
			'L_TITLE'					=> $_CLASS['core_user']->lang['UCP_PROFILE_' . strtoupper($mode)],
			'S_HIDDEN_FIELDS'			=> $s_hidden_fields,
			'S_UCP_ACTION'				=> $module_link)
		);

		$this->display($_CLASS['core_user']->lang['UCP_PROFILE'], 'ucp_profile_' . $mode . '.html');
	}
}

?>