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

if (VIPERAL !== 'Admin')
{
	die;
}

global $_CLASS;

/*  Just testing the layout, i know they not acurrate */
$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM ' . CORE_USERS_TABLE . ' WHERE user_type = ' . USER_BOT);
list($count_bots) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);

$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM ' . CORE_USERS_TABLE . ' WHERE user_type = ' . USER_NORMAL);
list($count_users) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);


$_CLASS['core_template']->assign_array(array(
	'COUNT_BOTS'	=> $count_bots,
	'COUNT_USERS'	=> $count_users,
	'LINK_ADD_USER'	=> generate_link('users&amp;mode=add_user', array('admin' => true)),
	'LINK_EDIT_USER'=> generate_link('users&amp;mode=edit_user', array('admin' => true)),
	'LINK_SETTINGS'	=> generate_link('users&amp;mode=setting', array('admin' => true)),

	'LINK_USER_INDEX'		=> generate_link('users', array('admin' => true)),
	'LINK_VIEW_BOTS'		=> generate_link('users&amp;mode=bots', array('admin' => true)),
	'LINK_VIEW_DISABLED'	=> generate_link('users&amp;mode=disabled', array('admin' => true)),
	'LINK_VIEW_UNACTIVATED'	=> generate_link('users&amp;mode=unactivated', array('admin' => true)),
	'LINK_VIEW_USER'		=> generate_link('users&amp;mode=user', array('admin' => true)),
));

$id = (int) get_variable('id', 'GET', false);

if (isset($_REQUEST['mode']))
{
	switch ($_REQUEST['mode'])
	{
		case 'setting':
			global $_CORE_CONFIG;

			if (isset($_POST['submit']))
			{
				$activation_array = array(USER_ACTIVATION_NONE, USER_ACTIVATION_SELF, USER_ACTIVATION_ADMIN, USER_ACTIVATION_DISABLE);
				$activation = get_variable('activation', 'POST', USER_ACTIVATION_NONE, 'int');

				$data['activation']		= in_array($activation, $activation_array) ? $activation : USER_ACTIVATION_NONE;
		 		$data['coppa_enable']	= get_variable('coppa_enable', 'POST') ? 1 : 0;
		 		$data['coppa_fax']		= get_variable('coppa_fax', 'POST', '');
		 		$data['coppa_mail']		= get_variable('coppa_mail', 'POST', '');
		 		$data['enable_confirm']	= get_variable('enable_confirm', 'POST') ? 1 : 0;
		 		$data['min_name_chars']	= get_variable('min_name_chars', 'POST', 0, 'int');
		 		$data['max_name_chars']	= get_variable('max_name_chars', 'POST', 0, 'int');
		 		$data['min_pass_chars']	= get_variable('min_pass_chars', 'POST', 0, 'int');
		 		$data['max_pass_chars']	= get_variable('max_pass_chars', 'POST', 0, 'int');
		 		$data['max_reg_attempts'] = get_variable('coppa_fax', 'POST', 0, 'int');

				$remove_config = false;

				foreach ($data as $name => $setting)
				{
					if ($setting !== $_CORE_CONFIG['user'][$name])
					{
						$remove_config = true;

						set_core_config('user', $name, $setting, false);
					}
				}

				if ($remove_config)
				{
					$_CLASS['core_cache']->destroy('core_config');
				}
				unset($remove_config, $data);
			}

			$_CLASS['core_template']->assign_array(array(
				'S_ACTION' => generate_link('users&amp;mode=setting', array('admin' => true)),
		
				'ACTIVATION_OPTION' => $_CORE_CONFIG['user']['activation'],
				'COPPA_ENABLE'		=> $_CORE_CONFIG['user']['coppa_enable'],
				'COPPA_FAX'			=> $_CORE_CONFIG['user']['coppa_fax'],
				'COPPA_MAIL'		=> $_CORE_CONFIG['user']['coppa_mail'],
				'CONFIRM_ENABLE'	=> $_CORE_CONFIG['user']['enable_confirm'],
				'MAX_REG_ATTEMPTS'	=> $_CORE_CONFIG['user']['max_reg_attempts'],
				'MIN_NAME_CHARS'	=> $_CORE_CONFIG['user']['min_name_chars'],
				'MAX_NAME_CHARS'	=> $_CORE_CONFIG['user']['max_name_chars'],
				'MIN_PASS_CHARS'	=> $_CORE_CONFIG['user']['min_pass_chars'],
				'MAX_PASS_CHARS'	=> $_CORE_CONFIG['user']['max_pass_chars'],
				//'CHG_PASSFORCE'		=> $_CORE_CONFIG['user']['chg_passforce'],
				//'ALLOW_NAMECHANGE'	=> $_CORE_CONFIG['user']['allow_namechange'],
				'ALLOW_NAME_CHARS'	=> $_CORE_CONFIG['user']['allow_name_chars'],
				
				//'PASS_COMPLEX'  => $_CORE_CONFIG['user']['pass_complex'],
				//'ALLOW_NAMECHANGE'  => $_CORE_CONFIG['user']['pass_complex'],
				//'ALLOW_EMAILREUSE'	=> $_CORE_CONFIG['user']['allow_emailreuse'],

				'A_ACC_OPTION'	=> array(
					array(
						'LANG' => $_CLASS['core_user']->lang['ACC_NONE'],
						'OPTION' => USER_ACTIVATION_NONE,
					),
					array(
						'LANG' => $_CLASS['core_user']->lang['ACC_USER'],
						'OPTION' => USER_ACTIVATION_SELF,
					),
					array(
						'LANG' => $_CLASS['core_user']->lang['ACC_ADMIN'],
						'OPTION' => USER_ACTIVATION_ADMIN,
					),
					array(
						'LANG' => $_CLASS['core_user']->lang['ACC_DISABLE'],
						'OPTION' => USER_ACTIVATION_DISABLE,
					)
				)
			));
	
			$_CLASS['core_display']->display(false, 'admin/users/setting.html');
		break;

		case 'add_user':
			global $_CORE_CONFIG;

			if (isset($_POST['submit']))
			{
				require_once SITE_FILE_ROOT.'includes/functions_user.php';


				$error = array();
	
				$username	= get_variable('username', 'POST');
				$password	= get_variable('password', 'POST');
				$email		= get_variable('email', 'POST');
				$tz			= get_variable('tz', 'POST');
				$coppa		= get_variable('coppa', 'POST');

				$error		= array();
				//when we add this make sure to confirm that it's one of the installed langs
				$lang		= $_CORE_CONFIG['global']['default_lang'];
	
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
	
				if (!$email)
				{
					$error[] = $_CLASS['core_user']->get_lang('EMAIL_ERROR');
				}
				elseif (!check_email($email)) /* we can maybe remove this check */
				{
					$error[] = $_CLASS['core_user']->get_lang('EMAIL_INVALID');
				}
	
				if (!$tz || !in_array($tz, tz_array()))
				{
					$tz = null;
				}

				if (empty($error))
				{
					$password = encode_password($password, $_CORE_CONFIG['user']['password_encoding']);
		
					if (!$password)
					{
						/* do some admin contact thing here */
						die('Try again later');
					}

					$data = array(
						'username'		=> (string) $username,
						'user_email'	=> (string) $email,
						'user_group'	=> (int) ($coppa) ? 3 : 2,
						'user_reg_date'	=> (int) $_CLASS['core_user']->time,
						'user_timezone'	=> $tz,
	
						'user_password'			=> (string) $password,
						'user_password_encoding'=> (string) $_CORE_CONFIG['user']['password_encoding'],
	
						'user_lang'			=> ($lang == $_CORE_CONFIG['global']['default_lang']) ? null : $lang,
						'user_type'			=> USER_NORMAL,
						'user_status'		=> STATUS_ACTIVE,
						'user_act_key'		=> null,
						'user_ip'			=> '',
					);
	
					user_add($data);
					
					set_core_config('user', 'newest_user_id', $data['user_id'], false);
					set_core_config('user', 'newest_username', $data['username'], false);
					set_core_config('user', 'total_users', $_CORE_CONFIG['user']['total_users'] + 1);
					
					trigger_error('USER_ADDED');
				}
			}

			$_CLASS['core_template']->assign_array(array(
				'COPPA'			=> isset($coppa) ? $coppa : false, 
				'EMAIL'			=> isset($email) ? $email : '',
				'ERROR'			=> empty($error) ? false : implode('<br />', $error),

				'PASSWORD'		=> isset($password) ? $password : '',
				'USERNAME'		=> isset($username) ? $username : '',

				'SELECT_TZ'		=> select_tz(isset($tz) ? $tz : $_CORE_CONFIG['global']['default_timezone']),
				'S_ACTION'		=> generate_link('users&amp;mode=add_user', array('admin' => true))
			));
	
			$_CLASS['core_display']->display(false, 'admin/users/add.html');
		break;

		case 'bots':
			if ($id && isset($_REQUEST['option']))
			{
				require_once SITE_FILE_ROOT.'includes/functions_user.php';
			
				$sql = 'SELECT user_id, user_type, user_status
					FROM ' . CORE_USERS_TABLE . ' 
					WHERE user_id = '.$id;
				
				$result = $_CLASS['core_db']->query($sql);
				$row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if ($row['user_type'] != USER_BOT)
				{
					break;
				}

				switch ($_REQUEST['option'])
				{
					case 'activate':
						if ($row['user_status'] != STATUS_ACTIVE)
						{
							user_activate($id);
						}
					break;
			
					case 'deactivate':
						if ($row['user_status'] == STATUS_ACTIVE)
						{
							user_disable($id);
						}
					break;
				
					case 'delete':
						if (display_confirmation())
						{
							user_delete($id);
				
							trigger_error('BOT_DELETED');
						}
					break;
				}
			}
			$start = get_variable('start', 'GET', 0, 'integer');
			$limit = 20;

			$sql = 'SELECT user_id, username, user_status, user_last_visit 
				FROM ' . CORE_USERS_TABLE . '
				WHERE user_type = ' . USER_BOT . ' ORDER BY user_last_visit, user_id DESC';
			$result = $_CLASS['core_db']->query_limit($sql, $limit, $start);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('admin_bots', array(
					'ACTIVE'		=> ($row['user_status'] == STATUS_ACTIVE),
					'NAME'			=> $row['username'],
					'LINK_DELETE'	=> generate_link('users&amp;mode=bots&amp;option=delete&amp;id='.$row['user_id'], array('admin' => true)),
					'LINK_STATUS'	=> generate_link('users&amp;mode=bots&amp;option='.(($row['user_status'] == STATUS_ACTIVE) ? 'deactivate' :  'activate').'&amp;id='.$row['user_id'], array('admin' => true)),
					'LINK_EDIT'		=> generate_link('users&amp;mode=bots&amp;options=edit&amp;id='.$row['user_id'], array('admin' => true)),
					'LAST_VISIT'	=> ($row['user_last_visit']) ?  $_CLASS['core_user']->format_date($row['user_last_visit']) : $_CLASS['core_user']->lang['BOT_NEVER'],
					'L_STATUS'		=> ($row['user_status'] == STATUS_ACTIVE) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
				));
			}
			$_CLASS['core_db']->free_result($result);

			$result = $_CLASS['core_db']->query('SELECT COUNT(*) AS total from '.CORE_USERS_TABLE.' WHERE user_type = ' . USER_BOT);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);	
			
			$pagination = generate_pagination('users&amp;mode=bots', $row['total'], $limit, $start, true);
			
			$_CLASS['core_template']->assign_array(array(
				'ADMIN_USER_PAGINATION'			=> $pagination['formated'],
				'ADMIN_USER_PAGINATION_ARRAY'	=> $pagination['array'],
				'ADMIN_USER_PAGE_NUMBER'		=> on_page($row['total'], $limit, $start),
				'ADMIN_USER_TOTAL_MESSAGES'		=> $row['total']
			));

			$_CLASS['core_display']->display(false, 'admin/users/bots.html');
		break;
	
		case 'activate':
		case 'delete':
			$_REQUEST['option'] = $_REQUEST['mode'];

		case 'disabled':
		case 'unactivated':
			if ($id && isset($_REQUEST['option']))
			{
				require_once SITE_FILE_ROOT.'includes/functions_user.php';

				$sql = 'SELECT user_id, user_type, user_status
					FROM ' . CORE_USERS_TABLE . ' 
					WHERE user_id = '.$id;

				$result = $_CLASS['core_db']->query($sql);
				$row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if ($row['user_type'] != USER_NORMAL)
				{
					break;
				}

				switch ($_REQUEST['option'])
				{
					case 'activate':
						if ($row['user_status'] != STATUS_ACTIVE)
						{
							user_activate($id);
						}
					break;

					case 'delete':
						if (display_confirmation())
						{
							$sql = 'SELECT user_id, user_type
								FROM ' . CORE_USERS_TABLE . ' 
								WHERE user_id = '.$id;
				
							$result = $_CLASS['core_db']->query($sql);
							$row = $_CLASS['core_db']>fetch_row_assoc($result);
							$_CLASS['core_db']->free_result($result);
				
							if ($row['user_type'] != USER_BOT)
							{
								break;
							}
				
							user_delete($id);
				
							trigger_error($_CLASS['core_user']->lang['BOT_DELETED']);
						}
					break;
				}
			}

			if (isset($_REQUEST['option']) && $_REQUEST['option'] === $_REQUEST['mode']) {
				break;
			}

			if ($_REQUEST['mode'] == 'unactivated')
			{
				$status = STATUS_PENDING;
				$template = 'admin/users/unactivated.html';
				$link = 'users&amp;mode=unactivated';
			}
			else
			{
				$status = STATUS_DISABLED;
				$template = 'admin/users/disabled.html';
				$link = 'users&amp;mode=disabled';
			}

			$start = get_variable('start', 'GET', false, 'integer');

			$sql = 'SELECT user_id, username, user_reg_date
				FROM ' . CORE_USERS_TABLE . '
					WHERE user_type = '.USER_NORMAL.'
					AND user_status = '.$status;

			$result = $_CLASS['core_db']->query_limit($sql, 20, $start);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('users_admin', array(
						'user_id'		=> $row['user_id'],
						'user_name'		=> $row['username'],
						'registered'	=> $_CLASS['core_user']->format_time($row['user_reg_date']),
						'link_profile'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
						'link_activate'	=> generate_link($link.'&amp;option=activate&amp;id=' . $row['user_id'], array('admin' => true)),
						'link_remove'	=> generate_link($link.'&amp;option=delete&amp;id=' . $row['user_id'], array('admin' => true)),
						'link_remind'	=> generate_link($link.'&amp;option=remind&amp;id=' . $row['user_id'], array('admin' => true)),
						'link_details'	=> '',
				));
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'SELECT count(*) as count FROM ' . CORE_USERS_TABLE . '
				WHERE user_type = '.USER_NORMAL.'
				AND user_status = '.$status;

			$result = $_CLASS['core_db']->query($sql);
			list($count) = $_CLASS['core_db']->fetch_row_num($result);
			$_CLASS['core_db']->free_result($result);

			$pagination = generate_pagination($link, $count, 20, $start, true);
			$_CLASS['core_template']->assign('USERS_PAGINATION', $pagination['formated']);

			$_CLASS['core_display']->display(false, $template);
		break;

		case 'user':
			if ($id && isset($_REQUEST['option']))
			{
				require_once SITE_FILE_ROOT.'includes/functions_user.php';

				$sql = 'SELECT user_id, user_type, user_status
					FROM ' . CORE_USERS_TABLE . ' 
					WHERE user_id = '.$id;

				$result = $_CLASS['core_db']->query($sql);
				$row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if ($row['user_type'] != USER_NORMAL)
				{
					break;
				}

				switch ($_REQUEST['option'])
				{
					case 'activate':
						if ($row['user_status'] != STATUS_ACTIVE)
						{echo 'test';
							user_activate($id);
						}
					break;

					case 'deactivate':
						if ($row['user_id'] != $_CLASS['core_user']->data['user_id'] && $row['user_status'] == STATUS_ACTIVE)
						{
							user_disable($id);
						}
					break;

					case 'delete':
						if ($row['user_id'] != $_CLASS['core_user']->data['user_id'] && display_confirmation())
						{
							user_delete($id);

							trigger_error('BOT_DELETED');
						}
					break;
				}
			}

			$start = get_variable('start', 'GET', 0, 'integer');
			$limit = 20;

			$sql = 'SELECT user_id, username, user_status, user_last_visit 
				FROM ' . CORE_USERS_TABLE . '
				WHERE user_type = ' . USER_NORMAL . ' ORDER BY username DESC';
			$result = $_CLASS['core_db']->query_limit($sql, $limit, $start);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('admin_bots', array(
					'ACTIVE'		=> ($row['user_status'] == STATUS_ACTIVE),
					'NAME'			=> $row['username'],
					'LINK_DELETE'	=> ($row['user_id'] != $_CLASS['core_user']->data['user_id']) ? generate_link('users&amp;mode=user&amp;option=delete&amp;id='.$row['user_id'], array('admin' => true)) : false,
					'LINK_STATUS'	=> ($row['user_id'] != $_CLASS['core_user']->data['user_id']) ? generate_link('users&amp;mode=user&amp;option='.(($row['user_status'] == STATUS_ACTIVE) ? 'deactivate' :  'activate').'&amp;id='.$row['user_id'], array('admin' => true)) : false,
					'LINK_EDIT'		=> generate_link('users&amp;mode=user&amp;options=edit&amp;id='.$row['user_id'], array('admin' => true)),
					'LAST_VISIT'	=> ($row['user_last_visit']) ?  $_CLASS['core_user']->format_date($row['user_last_visit']) : $_CLASS['core_user']->lang['BOT_NEVER'],
					'L_STATUS'		=> ($row['user_status'] == STATUS_ACTIVE) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
				));
			}
			$_CLASS['core_db']->free_result($result);

			$result = $_CLASS['core_db']->query('SELECT COUNT(*) AS total from '.CORE_USERS_TABLE.' WHERE user_type = ' . USER_NORMAL);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);	
			
			$pagination = generate_pagination('users&amp;mode=user', $row['total'], $limit, $start, true);
			
			$_CLASS['core_template']->assign_array(array(
				'ADMIN_USER_PAGINATION'			=> $pagination['formated'],
				'ADMIN_USER_PAGINATION_ARRAY'	=> $pagination['array'],
				'ADMIN_USER_PAGE_NUMBER'		=> on_page($row['total'], $limit, $start),
				'ADMIN_USER_TOTAL_MESSAGES'		=> $row['total']
			));

			$_CLASS['core_display']->display(false, 'admin/users/bots.html');
		break;
	}
}

$user_status = array(STATUS_PENDING, STATUS_DISABLED);
$last_count = 0;

foreach ($user_status as $status)
{
	$limit = ($last_count) ? 10 : 20 - $last_count;

	$sql = 'SELECT COUNT(*)	FROM ' . CORE_USERS_TABLE . '
				WHERE user_type = '.USER_NORMAL.'
				AND user_status = '.$status;
	$result = $_CLASS['core_db']->query($sql);
	list($count) = $_CLASS['core_db']->fetch_row_num($result);

	$last_count = $last_count + min($count, $limit);

	if ($status === STATUS_PENDING)
	{
		$more = 'MORE_PENDING';
		$link = generate_link('users&amp;mode=unactivated', array('admin' => true));
	}
	else
	{
		$more = 'MORE_DISABLED';
		$link = generate_link('users&amp;mode=disabled', array('admin' => true));
	}

	$_CLASS['core_template']->assign_array(array(
		$more			=> ($count > $limit),
		'LINK_'.$more	=> $link,
	));

	$sql = 'SELECT user_id, username, user_reg_date
		FROM ' . CORE_USERS_TABLE . '
			WHERE user_type = '.USER_NORMAL.'
			AND user_status = '.$status;

	$result = $_CLASS['core_db']->query_limit($sql, $limit);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$type = ($status == STATUS_DISABLED) ? 'users_disabled' : 'users_unactivated';

		$_CLASS['core_template']->assign_vars_array($type, array(
				'user_id'		=> $row['user_id'],
				'user_name'		=> $row['username'],
				'registered'	=> $_CLASS['core_user']->format_time($row['user_reg_date']),
				'link_profile'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
				'link_activate'	=> generate_link('&amp;mode=activate&amp;id=' . $row['user_id'], array('admin' => true)),
				'link_remove'	=> generate_link('&amp;mode=remove&amp;id=' . $row['user_id'], array('admin' => true)),
				'link_remind'	=> generate_link('&amp;mode=remind&amp;id=' . $row['user_id'], array('admin' => true)),
				'link_details'	=> '',
		));
	}
	$_CLASS['core_db']->free_result($result);
}

$_CLASS['core_display']->display(false, 'admin/users/index.html');

?>