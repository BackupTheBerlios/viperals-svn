<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// $Id: memberlist.php,v 1.91 2004/09/01 15:47:43 psotfx Exp $
//
// FILENAME  : memberlist.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    die();
}

global $_CLASS, $_CORE_CONFIG;

$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_lang();
$_CLASS['core_user']->add_img(false, 'forums');

$_CLASS['core_template']->assign_array(array(
	'S_SEARCH_USER'	=> false,
	'S_SHOW_GROUP'	=> false
));

// Grab data
$mode		= get_variable('mode', 'REQUEST');
$action		= get_variable('action', 'REQUEST');

$start		= get_variable('start', 'REQUEST', false, 'int');
$submit		= isset($_POST['submit']);

$sort_key	= get_variable('sk', 'REQUEST', 'c');
$sort_dir	= get_variable('sd', 'REQUEST', 'a');

$window		= false;

// Grab rank information for later
//$ranks = obtain_ranks();

switch ($mode)
{
	case 'forum_moderators':
		require_once SITE_FILE_ROOT.'includes/forums/functions.php';
		load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');

		$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);
		
		$page_title = $_CLASS['core_user']->lang['LEADER'];
		$template_html = 'memberlist_forum_moderators.html';

		$user_ary = $_CLASS['forums_auth']->acl_get_list(false, 'm_', false);
		$modorators = $forum_id_ary = array();

		foreach ($user_ary as $forum_id => $forum_ary)
		{
			$modorators += $forum_ary['m_'];

			if ($forum_id)
			{
				foreach ($forum_ary['m_'] as $id)
				{
					$forum_id_ary[$id][] = $forum_id;
				}
			}
		}

		$sql = 'SELECT forum_id, forum_name 
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_type = ' . FORUM_POST;
		$result = $_CLASS['core_db']->query($sql);
		
		$forums_array = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forums_array[$row['forum_id']] = $row['forum_name'];
			$forum_array_ids[] = $row['forum_id'];
		}
		$_CLASS['core_db']->free_result($result);

		$sql = 'SELECT u.user_id, u.username, u.user_colour, u.user_rank, u.user_posts, g.group_id, g.group_name, g.group_colour, g.group_type, ug.user_id as ug_user_id
			FROM ' . CORE_USERS_TABLE . ' u, ' . CORE_GROUPS_TABLE . ' g
			LEFT JOIN ' . CORE_GROUPS_MEMBERS_TABLE . ' ug ON (ug.group_id = g.group_id AND ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . ')
			WHERE u.user_id IN (' . implode(', ', array_unique($modorators)) . ')
				AND u.user_group = g.group_id
			ORDER BY g.group_name ASC, u.username ASC';

		$result = $_CLASS['core_db']->query($sql);
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forums = array_intersect($forum_id_ary[$row['user_id']], $forum_array_ids);

			if (empty($forums))
			{
				continue;
			}

			$s_forum_select = '';

			foreach ($forums as $forum_id)
			{
				if ($_CLASS['forums_auth']->acl_get('f_list', $forum_id))
				{
					$s_forum_select .= '<option value="">' . $forums[$forum_id] . '</option>';
				}
			}

			if ($row['group_type'] == GROUP_HIDDEN && $row['ug_user_id'] != $_CLASS['core_user']->data['user_id'])
			{
				$group_name = $_CLASS['core_user']->get_lang('UNDISCLOSED');
				$u_group = '';
			}
			else
			{
				$group_name = isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'];
				$u_group = generate_link('members_list&amp;mode=group&amp;g='.$row['group_id']);
			}

			$rank_title = $rank_img = '';
			get_user_rank($row['user_rank'], $row['user_posts'], $rank_title, $rank_img, $rank_img_src);

			$_CLASS['core_template']->assign_vars_array('moderators', array(
				'USER_ID'		=> $row['user_id'],
				'FORUMS'		=> $s_forum_select,
				'USERNAME'		=> $row['username'],
				'USER_COLOR'	=> $row['user_colour'],
				'RANK_TITLE'	=> $rank_title,
				'GROUP_NAME'	=> $group_name,
				'GROUP_COLOR'	=> $row['group_colour'],

				'RANK_IMG'		=> $rank_img,
				'RANK_IMG_SRC'	=> $rank_img_src,

				'U_GROUP'		=> $u_group,
				'U_VIEWPROFILE'	=> generate_link('members_list&amp;mode=viewprofile&amp;u='.$row['user_id']),
				'U_PM'			=> generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$row['user_id'])
			));
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign('PM_IMG', $_CLASS['core_user']->img('btn_pm', $_CLASS['core_user']->lang['MESSAGE']));
	break;

	case 'contact':
		$_CLASS['core_template']->assign_array(array(
			'S_SEND_ICQ'	=> false,
			'S_SEND_AIM'	=> false,
			'S_SEND_JABBER' => false,
			'S_SENT_JABBER' => false,
			'S_SEND_MSNM'	=> false,
			'S_NO_SEND_JABBER' => false
		));

// IM_USER not in lang file
		$page_title = ''; //$_CLASS['core_user']->lang['IM_USER'];
		$template_html = 'memberlist_im.html';
		$window = true;

		$presence_img = '';
		switch ($action)
		{
			case 'aim':
				$lang = 'AIM';
				$sql_field = 'user_aim';
				$s_select = 'S_SEND_AIM';
				$s_action = '';
			break;

			case 'msnm':
				$lang = 'MSNM';
				$sql_field = 'user_msnm';
				$s_select = 'S_SEND_MSNM';
				$s_action = '';
				break;

			case 'jabber':
				$lang = 'JABBER';
				$sql_field = 'user_jabber';
				$s_select = (@extension_loaded('xml')) ? 'S_SEND_JABBER' : 'S_NO_SEND_JABBER';
				$s_action = generate_link("members_list&amp;mode=contact&amp;action=$action&amp;u=$user_id");
			break;

			default:
				trigger_error('NO_USER_DATA');
				die;
			break;
		}

		// Grab relevant data
		$sql = "SELECT user_id, username, user_email, user_lang, $sql_field
			FROM " . CORE_USERS_TABLE . "
			WHERE user_id = $user_id
				AND user_type = " . USER_NORMAL .' AND user_status = ' . STATUS_ACTIVE;
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row)
		{
			trigger_error('NO_USER_DATA');
		}
		

		// Post data grab actions
		switch ($action)
		{
			case 'icq':
				$presence_img = '<img src="http://web.icq.com/whitepages/online?icq=' . $row[$sql_field] . '&amp;img=5" width="18" height="18" border="0" alt="" />';
			break;

		/*	case 'jabber':
				if ($submit && @extension_loaded('xml'))
				{
					// Add class loader
					require_once(SITE_FILE_ROOT.'includes/forums/functions_messenger.php');

					$subject = sprintf($_CLASS['core_user']->lang['IM_JABBER_SUBJECT'], $_CLASS['core_user']->data['username'], $config['server_name']);
					$message = $_POST['message'];

					$messenger = new messenger();

					$messenger->template('profile_send_email', $row['user_lang']);
					$messenger->subject($subject);

					$messenger->replyto($_CLASS['core_user']->data['user_email']);
					$messenger->im($row['user_jabber'], $row['username']);

					$messenger->assign_vars(array(
						'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
						'BOARD_EMAIL'	=> $config['board_contact'],
						'FROM_USERNAME' => $_CLASS['core_user']->data['username'],
						'TO_USERNAME'	=> $row['username'],
						'MESSAGE'		=> $message)
					);

					$messenger->send(NOTIFY_IM);
					$messenger->save_queue();

					$s_select = 'S_SENT_JABBER';
				}
				break;*/
		}

		$_CLASS['core_template']->assign_array(array(
			'CONTACT_NAME'	=> $row[$sql_field],
			'IM_CONTACT'	=> $row[$sql_field],
			'USERNAME'		=> addslashes($row['username']),
			'EMAIL'			=> $row['user_email'],
			'SITENAME'		=> addslashes($_CORE_CONFIG['global']['site_name']),

			'PRESENCE_IMG'		=> $presence_img,

			'L_SEND_IM_EXPLAIN'	=> $_CLASS['core_user']->lang['IM_' . $lang],
			'L_IM_SENT_JABBER'	=> sprintf($_CLASS['core_user']->lang['IM_SENT_JABBER'], $row['username']),

			$s_select			=> true,
			'S_IM_ACTION'		=> $s_action
		));
	break;

	case 'viewprofile':
	
		$user_id = get_variable('u', 'REQUEST', ANONYMOUS, 'int');
		$username = get_variable('username', 'REQUEST');

		// Display a profile
		if ($user_id === ANONYMOUS && !$username)
		{
			trigger_error('NO_USER');
		}

		// Get user...
		if ($username)
		{
			$sql = 'SELECT *
				FROM ' . CORE_USERS_TABLE . "
				WHERE LOWER(username) = '" . $_CLASS['core_db']->escape(strtolower($username)) . "'
					AND user_type IN (" . USER_NORMAL . ')';
		}
		else
		{
			$sql = 'SELECT *
				FROM ' . CORE_USERS_TABLE . "
				WHERE user_id = $user_id
					AND user_type IN (" . USER_NORMAL . ')';
		}

		$result = $_CLASS['core_db']->query($sql);
		$member = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$member || (!$_CLASS['core_auth']->admin_power('users') && $member['user_status'] != STATUS_ACTIVE))
		{
			trigger_error(($member) ? 'USER_INACTIVE' : 'NO_USER');
		}
		
		$sql = 'SELECT g.group_id, g.group_name, g.group_type
			FROM ' . CORE_USER_GROUP_TABLE . ' ug, ' . CORE_GROUPS_TABLE . " g
			WHERE ug.user_id = $user_id
				AND g.group_id = ug.group_id AND group_type <> " . GROUP_HIDDEN. '
			ORDER BY group_type, group_name';
		$result = $_CLASS['core_db']->query($sql);

		$group_options = '';
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$group_options .= '<option value="' . $row['group_id'] . '"'.(($member['user_group'] == $row['group_id'])? ' selected="selected"' : '').'>' . (isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['core_db']->free_result($result);
		
		$page_title = sprintf($_CLASS['core_user']->lang['VIEWING_PROFILE'], $member['username']);
		$template_html = 'memberlist_view.html';
		
		$sql = 'SELECT MAX(session_time) AS session_time
			FROM ' . CORE_SESSIONS_TABLE . "
			WHERE session_user_id = $user_id";
		$result = $_CLASS['core_db']->query($sql);

		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$member['session_time'] = isset($row['session_time']) ? $row['session_time'] : 0;
		unset($row);

		$num_real_posts = $_CLASS['core_user']->data['user_posts'];

		// Do the relevant calculations
		$member_days = max(1, round(($_CLASS['core_user']->time - $member['user_reg_date']) / 86400));
		$posts_per_day = $member['user_posts'] / $member_days;

		$active_f_name = $active_f_id = $active_f_count = $active_f_pct = '';

		if (!empty($active_f_row['num_posts']))
		{
			$active_f_name = $active_f_row['forum_name'];
			$active_f_id = $active_f_row['forum_id'];
			$active_f_count = $active_f_row['num_posts'];
			$active_f_pct = ($member['user_posts']) ? ($active_f_count / $member['user_posts']) * 100 : 0;
		}
		unset($active_f_row);

		$active_t_name = $active_t_id = $active_t_count = $active_t_pct = '';
		if (!empty($active_t_row['num_posts']))
		{
			$active_t_name = $active_t_row['topic_title'];
			$active_t_id = $active_t_row['topic_id'];
			$active_t_count = $active_t_row['num_posts'];
			$active_t_pct = ($member['user_posts']) ? ($active_t_count / $member['user_posts']) * 100 : 0;
		}
		unset($active_t_row);

		if ($member['user_sig'])
		{
			$member['user_sig'] = censor_text($member['user_sig']);
			$member['user_sig'] = str_replace("\n", '<br />', $member['user_sig']);
			
			if ($member['user_sig_bbcode_bitfield'])
			{
				require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';

				$bbcode = new bbcode();
				$bbcode->bbcode_second_pass($member['user_sig'], $member['user_sig_bbcode_uid'], $member['user_sig_bbcode_bitfield']);
			}

			$member['user_sig'] = smiley_text($member['user_sig']);
		}

		$poster_avatar = '';
		if (!empty($member['user_avatar']))
		{
			switch ($member['user_avatar_type'])
			{
				case AVATAR_UPLOAD:
					$poster_avatar = $_CORE_CONFIG['global']['path_avatar_upload'] . '/';
				break;

				case AVATAR_GALLERY:
					$poster_avatar = $_CORE_CONFIG['global']['path_avatar_gallery'] . '/';
				break;
			}
			$poster_avatar .= $member['user_avatar'];

			$poster_avatar = '<img src="' . $poster_avatar . '" width="' . $member['user_avatar_width'] . '" height="' . $member['user_avatar_height'] . '" border="0" alt="" />';
		}

		$rank_title = $rank_img = '';
	
		get_user_rank($member['user_rank'], $member['user_posts'], $rank_title, $rank_img);
		
		if (!empty($member['user_allow_viewemail']))
		{
			$email = ($_CORE_CONFIG['email']['email_enable']) ? generate_link('members_list&amp;mode=email&amp;u='.$member['user_id']) : ((false) ? '' : 'mailto:' . $member['user_email']);
		}
		else
		{
			$email = '';
		}
	
		$last_visit = ($member['session_time']) ? $member['session_time'] : $member['user_last_visit'];
		$online = ($member['session_time'] >= ($_CLASS['core_user']->time - $_CORE_CONFIG['server']['session_length']));

		$age = '';
	
		if ($data['user_birthday'])
		{
			list($bday_day, $bday_month, $bday_year) = array_map('intval', explode('-', $data['user_birthday']));
	
			if ($bday_year)
			{
				$now = getdate(gmtime());
	
				$diff = $now['mon'] - $bday_month;
				if ($diff == 0)
				{
					$diff = ($now['mday'] - $bday_day < 0) ? 1 : 0;
				}
				else
				{
					$diff = ($diff < 0) ? 1 : 0;
				}
	
				$age = (int) ($now['year'] - $bday_year - $diff);
			}
		}

		$_CLASS['core_template']->assign_array(array(
			'AGE'			=> $age,
			'USERNAME'		=> $member['username'],
			'USER_COLOR'	=> ($member['user_colour']) ? $member['user_colour'] : '',
			'RANK_TITLE'	=> $rank_title,
	
			'JOINED'		=> $_CLASS['core_user']->format_date($member['user_reg_date']),
			'VISITED'		=> ($last_visit) ? ' - ' : $_CLASS['core_user']->format_date($last_visit),
			'POSTS'			=> ($member['user_posts']) ? $member['user_posts'] : 0,
	
			'ONLINE_IMG'	=> ($online) ? $_CLASS['core_user']->img('btn_online', $_CLASS['core_user']->lang['USER_ONLINE']) : $_CLASS['core_user']->img('btn_offline', $_CLASS['core_user']->lang['USER_ONLINE']),
			'RANK_IMG'		=> $rank_img,
			'ICQ_STATUS_IMG'=> ($member['user_icq']) ? '<img src="http://web.icq.com/whitepages/online?icq=' . $member['user_icq'] . '&amp;img=5" width="18" height="18" border="0" />' : '',
	
			'U_PROFILE'		=> generate_link('members_list&amp;mode=viewprofile&amp;u='.$member['user_id']),
			'U_SEARCH_USER'	=> generate_link('Forums&amp;file=search&amp;search_author=' . urlencode($member['username']) . '&amp;show_results=posts'),
			'U_PM'			=> generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$member['user_id']),
			'U_EMAIL'		=> $email,
			'U_WWW'			=> ($member['user_website']) ? $member['user_website'] : '',
			'U_ICQ'			=> ($member['user_icq']) ? generate_link('members_list&amp;mode=contact&amp;action=icq&amp;u='.$member['user_id']) : '',
			'U_AIM'			=> ($member['user_aim']) ? generate_link('members_list&amp;mode=contact&amp;action=aim&amp;u='.$member['user_id']) : '',
			'U_YIM'			=> ($member['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . $member['user_yim'] . '&.src=pg' : '',
			'U_MSN'			=> ($member['user_msnm']) ? generate_link('members_list&amp;mode=contact&amp;action=msnm&amp;u='.$member['user_id']) : '',
			'U_JABBER'		=> ($member['user_jabber']) ? generate_link('members_list&amp;mode=contact&amp;action=jabber&amp;u='.$member['user_id']) : '',
	
			'S_ONLINE'		=> $online,
			'S_USER_LOGGED_IN' => $_CLASS['core_user']->is_user
		));
		
		$_CLASS['core_template']->assign_array(array(
			'POSTS_DAY'			=> sprintf($_CLASS['core_user']->lang['POST_DAY'], $posts_per_day),
			'POSTS_PCT'			=> 0,
			'ACTIVE_FORUM'		=> $active_f_name,
			'ACTIVE_FORUM_POSTS'=> ($active_f_count == 1) ? sprintf($_CLASS['core_user']->lang['USER_POST'], 1) : sprintf($_CLASS['core_user']->lang['USER_POSTS'], $active_f_count),
			'ACTIVE_FORUM_PCT'	=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $active_f_pct),
			'ACTIVE_TOPIC'		=> censor_text($active_t_name),
			'ACTIVE_TOPIC_POSTS'=> ($active_t_count == 1) ? sprintf($_CLASS['core_user']->lang['USER_POST'], 1) : sprintf($_CLASS['core_user']->lang['USER_POSTS'], $active_t_count),
			'ACTIVE_TOPIC_PCT'	=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $active_t_pct),

			'LOCATION'		=> (!empty($member['user_from'])) ? censor_text($member['user_from']) : '',
			'OCCUPATION'    => (!empty($member['user_occ'])) ? str_replace("\n", '<br />', censor_text($member['user_occ'])) : '',
			'INTERESTS'		=> (!empty($member['user_interests'])) ? str_replace("\n", '<br />', censor_text($member['user_interests'])) : '',
			'SIGNATURE'		=> $member['user_sig'],

			'AVATAR_IMG'	=> $poster_avatar,
			'PM_IMG'		=> $_CLASS['core_user']->img('btn_pm', $_CLASS['core_user']->lang['MESSAGE']),
			'EMAIL_IMG'		=> $_CLASS['core_user']->img('btn_email', $_CLASS['core_user']->lang['EMAIL']),
			'WWW_IMG'		=> $_CLASS['core_user']->img('btn_www', $_CLASS['core_user']->lang['WWW']),
			'ICQ_IMG'		=> $_CLASS['core_user']->img('btn_icq', $_CLASS['core_user']->lang['ICQ']),
			'AIM_IMG'		=> $_CLASS['core_user']->img('btn_aim', $_CLASS['core_user']->lang['AIM']),
			'MSN_IMG'		=> $_CLASS['core_user']->img('btn_msnm', $_CLASS['core_user']->lang['MSNM']),
			'YIM_IMG'		=> $_CLASS['core_user']->img('btn_yim', $_CLASS['core_user']->lang['YIM']),
			'JABBER_IMG'	=> $_CLASS['core_user']->img('btn_jabber', $_CLASS['core_user']->lang['JABBER']),
			'SEARCH_IMG'	=> $_CLASS['core_user']->img('btn_search', $_CLASS['core_user']->lang['SEARCH']),

			'S_PROFILE_ACTION'	=> generate_link('members_list&amp;mode=group'),
			'S_GROUP_OPTIONS'	=> $group_options,
			
			'U_ADD_FRIEND'		=> generate_link('Control_Panel&amp;i=zebra&amp;add=' . urlencode($member['username'])),
			'U_ADD_FOE'			=> generate_link('Control_Panel&amp;i=zebra&amp;mode=foes&amp;add=' . urlencode($member['username'])),
			'U_ACTIVE_FORUM'	=> generate_link('Forums&amp;file=viewforum&amp;f='.$active_f_id),
			'U_ACTIVE_TOPIC'	=> generate_link('Forums&amp;file=viewtopic&amp;t='.$active_t_id),
			
			'L_VIEWING_PROFILE' 	=> sprintf($_CLASS['core_user']->lang['VIEWING_PROFILE'], $member['username']),
		));
		
	break;

	case 'email':
		// Send an email
		$page_title = $_CLASS['core_user']->lang['SEND_EMAIL'];
		$template_html = 'memberlist_email.html';
		
		$_CLASS['core_template']->assign_array(array(
			'MESSAGE' => false,
			'SUBJECT' => false
		));
		
		if (!$_CORE_CONFIG['email']['email_enable'])
		{
			trigger_error('EMAIL_DISABLED');
		}

		$user_id = get_variable('u', 'REQUEST', ANONYMOUS, 'int');
		$topic_id = get_variable('t', 'REQUEST', false, 'int');

		if ($user_id === ANONYMOUS && !$topic_id)
		{
			trigger_error('NO_EMAIL');
		}

		// Are we trying to abuse the facility?
		/*
		if (($_CLASS['core_user']->time - $_CLASS['core_user']->data['user_last_email']) < $config['flood_interval'])
		{
			trigger_error('FLOOD_EMAIL_LIMIT');
		}
		*/

		$name		= strip_tags(get_variable('name', 'POST'));
		$email		= strip_tags(get_variable('email', 'POST'));
		$subject	= get_variable('subject', 'POST');
		$message	= get_variable('message', 'POST');
		$cc			= !empty($_POST['cc_email']);
		$topic_id	= get_variable('t', 'REQUEST', false, 'int');


		// Are we sending an email to a user on this board? Or are we sending a
		// topic heads-up message?
		if (!$topic_id)
		{
			// Get the appropriate username, etc.
			$sql = 'SELECT username, user_email, user_allow_viewemail, user_lang, user_jabber, user_notify_type
				FROM ' . CORE_USERS_TABLE . "
				WHERE user_id = $user_id
					AND user_type = ". USER_NORMAL . ' AND user_status = ' . STATUS_ACTIVE;

			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$row)
			{
				trigger_error('NO_USER');
			}
			
			// Can we send email to this user?
			if (!$row['user_allow_viewemail'])
			{
				trigger_error('NO_EMAIL');
			}
		}
		else
		{
			$sql = 'SELECT forum_id, topic_title
				FROM ' . FORUMS_TOPICS_TABLE . "
				WHERE topic_id = $topic_id";
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$row)
			{
				trigger_error('NO_TOPIC');
			}

			require_once SITE_FILE_ROOT.'includes/forums/functions.php';
			load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
	
			$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);

			if (!$_CLASS['forums_auth']->acl_get('f_read', $row['forum_id']))
			{
				trigger_error('NO_FORUM_READ');
			}
		}

		$error = array();

		if ($submit)
		{
			if (!$topic_id)
			{
				if (!$subject)
				{
					$error[] = $_CLASS['core_user']->lang['EMPTY_SUBJECT_EMAIL'];
				}

				if (!$message)
				{
					$error[] = $_CLASS['core_user']->lang['EMPTY_MESSAGE_EMAIL'];
				}
			}
			else
			{
				if (!$email || !check_email($email))
				{
					$error[] = $_CLASS['core_user']->lang['EMPTY_ADDRESS_EMAIL'];
				}

				if (!$name)
				{
					$error[] = $_CLASS['core_user']->lang['EMPTY_NAME_EMAIL'];
				}
			}

			if (empty($error))
			{
				/*
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_last_email = ' . $_CLASS['core_user']->time . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$result = $_CLASS['core_db']->query($sql);
				*/

				require_once(SITE_FILE_ROOT.'includes/mailer.php');
			
				$mailer = new core_mailer;

				if ($topic_id)
				{
					$template	= 'email_notify.txt';
					$email		= $email;
					$subject	= $row['topic_title'];
				}
				else
				{
					$template	= 'profile_send_email.txt';
					$email		= $row['user_email'];
					$name 		= $row['username'];
					$subject .= 'Email a friend';
				}


				$mailer->to($email, $name);
				$mailer->reply_to($_CLASS['core_user']->data['user_email'], $_CLASS['core_user']->data['username']);

				$mailer->subject($subject);
			
				if ($cc)
				{
					$mailer->cc($_CLASS['core_user']->data['user_email'], $_CLASS['core_user']->data['username']);
				}


				//$mailer->extra_header('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$mailer->extra_header('X-AntiAbuse: User_id - ' . $_CLASS['core_user']->data['user_id']);
				$mailer->extra_header('X-AntiAbuse: Username - ' . $_CLASS['core_user']->data['username']);
				$mailer->extra_header('X-AntiAbuse: User IP - ' . $_CLASS['core_user']->ip);

				$_CLASS['core_template']->assign_array(array(
					'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
					'FROM_USERNAME' => $_CLASS['core_user']->data['username'],
					'TO_USERNAME'   => ($topic_id) ? $name : $row['username'],
					'MESSAGE'		=> $message,
					'TOPIC_NAME'	=> ($topic_id) ? html_entity_decode($row['topic_title'], HTML_ENTITIES) : '',
					'U_TOPIC'		=> ($topic_id) ? generate_link('Forums&amp;file=viewforum&amp;f=' . $row['forum_id'] . "&t=$topic_id", array('full' => true, 'sid' => false)) : '')
				);

				$mailer->message = trim($_CLASS['core_template']->display('email/members_list/'.$template, true));
				$mailer->send();

				$_CLASS['core_display']->meta_refresh(3, generate_link());
				$message = (!$topic_id) ? sprintf($_CLASS['core_user']->lang['RETURN_INDEX'],  '<a href="' . generate_link() . '">', '</a>') : sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'],  '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=" . $row['topic_id']) . '">', '</a>');
				trigger_error($_CLASS['core_user']->lang['EMAIL_SENT'] . '<br /><br />' . $message);
			}
		}

		if ($topic_id)
		{
			$_CLASS['core_template']->assign_array(array(
				'EMAIL'			=> htmlspecialchars($email),
				'NAME'			=> htmlspecialchars($name),
				'TOPIC_TITLE'	=> $row['topic_title'],

				'U_TOPIC'	=> generate_link("Forums&amp;file=viewtopic&amp;f={$row['forum_id']}&amp;t=$topic_id"),
				'S_LANG_OPTIONS'=> ($topic_id) ? language_select($email_lang) : '')
			);
		}

		$_CLASS['core_template']->assign_array(array(
			'USERNAME'		=> (!$topic_id) ? $row['username'] : '',
			'ERROR_MESSAGE'	=> empty($error) ? '' : implode('<br />', $error),

			'L_EMAIL_BODY_EXPLAIN'	=> $_CLASS['core_user']->get_lang((!$topic_id) ? 'EMAIL_BODY_EXPLAIN' : 'EMAIL_TOPIC_EXPLAIN'),

			'S_POST_ACTION' => (!$topic_id) ? generate_link('members_list&amp;mode=email&amp;u='.$user_id, array('full' => true)) : generate_link("members_list&amp;mode=email&amp;f={$row['forum_id']}&amp;t=$topic_id", array('full' => true)),
			'S_SEND_USER'	=> (!$topic_id),
		));
	break;

	case 'group':
		$_CLASS['core_user']->add_lang('groups', 'Forums');
	default:
		// The basic memberlist
		$page_title = $_CLASS['core_user']->lang['MEMBERLIST'];
		$template_html = 'memberlist_body.html';

		// Sorting
		$sort_key_text = array('a' => $_CLASS['core_user']->lang['SORT_USERNAME'], 'b' => $_CLASS['core_user']->lang['SORT_LOCATION'], 'c' => $_CLASS['core_user']->lang['SORT_JOINED'], 'd' => $_CLASS['core_user']->lang['SORT_POST_COUNT'], 'e' => $_CLASS['core_user']->lang['SORT_EMAIL'], 'f' => $_CLASS['core_user']->lang['WEBSITE'], 'g' => $_CLASS['core_user']->lang['ICQ'], 'h' => $_CLASS['core_user']->lang['AIM'], 'i' => $_CLASS['core_user']->lang['MSNM'], 'j' => $_CLASS['core_user']->lang['YIM'], 'k' => $_CLASS['core_user']->lang['JABBER'], 'l' => $_CLASS['core_user']->lang['SORT_LAST_ACTIVE'], 'm' => $_CLASS['core_user']->lang['SORT_RANK']);
		$sort_key_sql = array('a' => 'u.username', 'b' => 'u.user_from', 'c' => 'u.user_reg_date', 'd' => 'u.user_posts', 'e' => 'u.user_email', 'f' => 'u.user_website', 'g' => 'u.user_icq', 'h' => 'u.user_aim', 'i' => 'u.user_msnm', 'j' => 'u.user_yim', 'k' => 'u.user_jabber', 'l' => 'u.user_last_visit', 'm' => 'u.user_rank DESC, u.user_posts');

		$sort_dir_text = array('a' => $_CLASS['core_user']->lang['ASCENDING'], 'd' => $_CLASS['core_user']->lang['DESCENDING']);

		$s_sort_key = '';
		foreach ($sort_key_text as $key => $value)
		{
			$selected = ($sort_key === $key) ? ' selected="selected"' : '';
			$s_sort_key .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}

		$s_sort_dir = '';
		foreach ($sort_dir_text as $key => $value)
		{
			$selected = ($sort_dir === $key) ? ' selected="selected"' : '';
			$s_sort_dir .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}

		$sql_select = $sql_from = $sql_where = $sql_fields =  '';

		$form = $window = get_variable('form', 'REQUEST');

		if ($mode === 'search_user')
		{
			$field	= get_variable('field', 'REQUEST', 'username');

			$username	= get_variable('username', 'REQUEST', '');
			$email		= get_variable('email', 'REQUEST', '');
			$icq		= get_variable('icq', 'REQUEST', '');
			$aim		= get_variable('aim', 'REQUEST', '');
			$yahoo		= get_variable('yahoo', 'REQUEST', '');
			$msn		= get_variable('msn', 'REQUEST', '');
			$jabber		= get_variable('jabber', 'REQUEST', '');

			$joined_select	= get_variable('joined_select', 'REQUEST', 'lt');
			$active_select	= get_variable('active_select', 'REQUEST', 'lt');
			$count_select	= get_variable('count_select', 'REQUEST', 'eq');
			$joined			= explode('-', get_variable('joined', 'REQUEST', ''));
			$active			= explode('-', get_variable('active', 'REQUEST', ''));
			$count			= (get_variable('count', 'REQUEST', '')) ? get_variable('count', 'REQUEST', 0) : '';
			$ipdomain		= get_variable('ip', 'REQUEST', '');

			$find_key_match = array('lt' => '<', 'gt' => '>', 'eq' => '=');

			$find_count = array('lt' => $_CLASS['core_user']->lang['LESS_THAN'], 'eq' => $_CLASS['core_user']->lang['EQUAL_TO'], 'gt' => $_CLASS['core_user']->lang['MORE_THAN']);
			$s_find_count = '';
			foreach ($find_count as $key => $value)
			{
				$selected = ($count_select == $key) ? ' selected="selected"' : '';
				$s_find_count .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}

			$find_time = array('lt' => $_CLASS['core_user']->lang['BEFORE'], 'gt' => $_CLASS['core_user']->lang['AFTER']);
			$s_find_join_time = '';
			foreach ($find_time as $key => $value)
			{
				$selected = ($joined_select == $key) ? ' selected="selected"' : '';
				$s_find_join_time .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}

			$s_find_active_time = '';
			foreach ($find_time as $key => $value)
			{
				$selected = ($active_select == $key) ? ' selected="selected"' : '';
				$s_find_active_time .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}

			$sql_where .= ($username) ? " AND u.username LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($username)) ."'" : '';
			$sql_where .= ($email) ? " AND u.user_email LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($email)) ."' " : '';
			$sql_where .= ($icq) ? " AND u.user_icq LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($icq)) ."' " : '';
			$sql_where .= ($aim) ? " AND u.user_aim LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($aim)) ."' " : '';
			$sql_where .= ($yahoo) ? " AND u.user_yim LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($yahoo)) ."' " : '';
			$sql_where .= ($msn) ? " AND u.user_msnm LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($msn)) ."' " : '';
			$sql_where .= ($jabber) ? " AND u.user_jabber LIKE '" . str_replace('*', '%', $_CLASS['core_db']->escape($jabber)) . "' " : '';
			$sql_where .= (is_numeric($count)) ? ' AND u.user_posts ' . $find_key_match[$count_select] . ' ' . (int) $count . ' ' : '';
			$sql_where .= (sizeof($joined) > 1) ? " AND u.user_reg_date " . $find_key_match[$joined_select] . ' ' . gmmktime(0, 0, 0, intval($joined[1]), intval($joined[2]), intval($joined[0])) : '';
			$sql_where .= (sizeof($active) > 1) ? " AND u.user_last_visit " . $find_key_match[$active_select] . ' ' . gmmktime(0, 0, 0, $active[1], intval($active[2]), intval($active[0])) : '';

			if ($ipdomain && $_CLASS['forums_auth']->acl_get('m_ip'))
			{
				$ips = (preg_match('#[a-z]#', $ipdomain)) ? implode(', ', preg_replace('#([0-9]{1,3}\.[0-9]{1,3}[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#', "'\\1'", gethostbynamel($ipdomain))) : "'" . str_replace('*', '%', $ipdomain) . "'";

				$sql = 'SELECT DISTINCT poster_id
					FROM ' . FORUMS_POSTS_TABLE . '
					WHERE poster_ip ' . ((preg_match('#%#', $ips)) ? 'LIKE' : 'IN') . " ($ips)";
				$result = $_CLASS['core_db']->query($sql);

				if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$ip_sql = array();
					do
					{
						$ip_sql[] = $row['poster_id'];
					}
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

					$sql_where .= ' AND u.user_id IN (' . implode(', ', $ip_sql) . ')';
				}
				else
				{
					// A minor fudge but it does the job :D
					$sql_where .= " AND u.user_id IN ('-1')";
				}
			}
		}

		$first_char = get_variable('first_char', 'REQUEST');

		if ($first_char === 'other')
		{
			$sql_where = '';

			for ($i = 65; $i < 91; $i++)
			{
				$sql_where .= " AND u.username NOT LIKE '" . chr($i) . "%'";
			}
		}
		elseif ($first_char)
		{
			$sql_where = " AND u.username LIKE '" . $_CLASS['core_db']->escape(substr($first_char, 0, 1)) . "%'";
		}

		if ($mode === 'group')
		{
			$group_id = get_variable('g', 'REQUEST', false, 'int');

			$sql = 'SELECT g.*, ug.member_status
						FROM ' . CORE_GROUPS_TABLE . ' g
							LEFT JOIN ' . CORE_USER_GROUP_TABLE . ' ug ON (ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . " AND ug.group_id = $group_id)
							WHERE g.group_id = $group_id AND group_status = " . STATUS_ACTIVE;
	
			$result = $_CLASS['core_db']->query($sql);
			$group_row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$group_row)
			{
				trigger_error('NO_GROUP');
			}

			switch ($group_row['group_type'])
			{
// rename the lang names
				case GROUP_REQUEST:
					$group_row['group_type'] = 'OPEN';
				break;

				case GROUP_CLOSED:
					$group_row['group_type'] = 'CLOSED';
				break;

				case GROUP_HIDDEN:
					$group_row['group_type'] = 'HIDDEN';
					
					// Check for membership or special permissions
					if ((int) $group_row['member_status'] !== STATUS_ACTIVE)
					{
						trigger_error('NO_GROUP');
					}
				break;

				case GROUP_SYSTEM:
				case GROUP_SPECIAL:
					$group_row['group_type'] = 'SPECIAL';
				break;

				case GROUP_UNRESTRAINED:
					$group_row['group_type'] = 'FREE';
				break;
			}

			$avatar_img = '';
			if ($group_row['group_avatar'])
			{
				switch ($group_row['group_avatar_type'])
				{
					case AVATAR_UPLOAD:
						$avatar_img = $_CORE_CONFIG['global']['path_avatar_upload'] . '/';
					break;

					case AVATAR_GALLERY:
						$avatar_img = $_CORE_CONFIG['global']['path_avatar_gallery'] . '/';
					break;
				}
				
				$avatar_img .= $group_row['group_avatar'];
				$avatar_img = '<img src="' . $avatar_img . '" width="' . $group_row['group_avatar_width'] . '" height="' . $group_row['group_avatar_height'] . '" border="0" alt="" />';
			}

			$rank_title = $rank_img = '';
	
			if (isset($ranks['special'][$group_row['group_rank']]))
			{
				$rank_title = $ranks['special'][$group_row['group_rank']]['rank_title'];
				$rank_img = empty($ranks['special'][$group_row['group_rank']]['rank_image']) ? '' : '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$group_row['group_rank']]['rank_image'] . '" border="0" alt="' . $ranks['special'][$group_row['group_rank']]['rank_title'] . '" title="' . $ranks['special'][$group_row['group_rank']]['rank_title'] . '" /><br />';	
			}

			$_CLASS['core_template']->assign_array(array(
				'GROUP_DESC'    => $group_row['group_description'],
				'GROUP_NAME'    => isset($_CLASS['core_user']->lang['G_' . $group_row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $group_row['group_name']] : $group_row['group_name'],
				'GROUP_COLOR'   => $group_row['group_colour'],
				'GROUP_TYPE'	=> $_CLASS['core_user']->lang['GROUP_IS_'. $group_row['group_type']],
				'GROUP_RANK'	=> $rank_title,

				'AVATAR_IMG'	=> $avatar_img,
				'RANK_IMG'		=> $rank_img,

				'U_PM'			=> generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;g='.$group_id)
			));

			$sql_fields = ', ug.member_status';
			$sql_from = ', ' . CORE_USER_GROUP_TABLE . ' ug ';
			$sql_where .= " AND u.user_id = ug.user_id AND ug.group_id = $group_id AND member_status IN (".STATUS_ACTIVE.', '.STATUS_LEADER.')';
		}

		// Sorting and order
		$order_by = $sort_key_sql[$sort_key] . '  ' . (($sort_dir === 'a') ? 'ASC' : 'DESC');

		// Count the users ...
		if ($sql_where)
		{
			$sql = 'SELECT COUNT(*) AS total_users
				FROM ' . CORE_USERS_TABLE . " u$sql_from
				WHERE u.user_type = " . USER_NORMAL .' AND u.user_status = ' . STATUS_ACTIVE ."
				$sql_where";

			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			$total_users = $row['total_users'];
		}
		else
		{
			$total_users = $_CORE_CONFIG['user']['total_users'];
		}

		$s_char_options = '<option value=""' . ((!$first_char) ? ' selected="selected"' : '') . '>&nbsp; &nbsp;</option>';

		for ($i = 65; $i < 91; $i++)
		{
			$s_char_options .= '<option value="' . chr($i) . '"' . (($first_char == chr($i)) ? ' selected="selected"' : '') . '>' . chr($i) . '</option>';
		}
		$s_char_options .= '<option value="other"' . (($first_char == 'other') ? ' selected="selected"' : '') . '>Other</option>';
		
		// Pagination string
		$pagination_url = $pagination_url2 = 'members_list';

		// Build a relevant pagination_url
		$global_var = ($submit) ? '_POST' : '_GET';

		global $$global_var;

		foreach ($$global_var as $key => $var)
		{
			// what don't we need ?
			if (in_array($key, array('submit', 'sid', 'mod')) || !$var)
			{ 
				continue;
			}
			
			$pagination_url .= '&amp;' . $key . '=' . urlencode(htmlspecialchars($var));
			$pagination_url2 .= ($key !== 'start') ? '&amp;' . $key . '=' . urlencode(htmlspecialchars($var)) : '';
		}

		$u_hide_find_member = $pagination_url;
		$pagination_url .= (($mode) ? '&amp;mode='.$mode : '') . (($first_char) ? '&amp;first_char='.$first_char : '');

		// Some search user specific data
		if ($mode === 'search_user')
		{
			$_CLASS['core_template']->assign_array(array(
				'USERNAME'	=> $username,
				'EMAIL'		=> $email,
				'ICQ'		=> $icq,
				'AIM'		=> $aim,
				'YAHOO'		=> $yahoo,
				'MSNM'		=> $msn,
				'JABBER'	=> $jabber,
				'JOINED'	=> implode('-', $joined),
				'ACTIVE'	=> implode('-', $active),
				'COUNT'		=> $count,
				'IP'		=> $ipdomain,

				'S_SEARCH_USER' 		=> true,
				'S_FORM_NAME' 			=> $form,
				'S_FIELD_NAME' 			=> $field,
				'S_COUNT_OPTIONS' 		=> $s_find_count,
				'S_SORT_OPTIONS' 		=> $s_sort_key,
				'S_JOINED_TIME_OPTIONS' => $s_find_join_time,
				'S_ACTIVE_TIME_OPTIONS' => $s_find_active_time,
				'S_SEARCH_ACTION' 		=> generate_link("members_list&amp;mode=searchuser&amp;form=$form&amp;field=$field"))
			);
		}

		$online_time = $_CLASS['core_user']->time - $_CORE_CONFIG['server']['session_length'];

// this can be big
		$sql = 'SELECT session_user_id, MAX(session_time) AS session_time
			FROM ' . CORE_SESSIONS_TABLE . '
			WHERE session_time >= ' . ($_CLASS['core_user']->time - $_CORE_CONFIG['server']['session_length']) . '
				AND session_user_id <> ' . ANONYMOUS . '
			GROUP BY session_user_id';
		$result = $_CLASS['core_db']->query($sql);

		$session_times = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$session_times[$row['session_user_id']] = (int) $row['session_time'];
		}
		$_CLASS['core_db']->free_result($result);
		
		// Do the SQL thang
		$sql = 'SELECT u.username, u.user_id, u.user_colour, u.user_allow_viewemail, u.user_posts, u.user_reg_date,
				u.user_rank, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, 
				u.user_msnm, u.user_jabber, u.user_avatar, u.user_avatar_type, u.user_last_visit '. $sql_fields .'
					FROM ' . CORE_USERS_TABLE . " u$sql_from
					WHERE u.user_type = " . USER_NORMAL . ' AND u.user_status = ' . STATUS_ACTIVE ."
						$sql_where
						ORDER BY $order_by";

		$result = $_CLASS['core_db']->query_limit($sql, 20, $start);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$option_row = ($mode === 'group' && $row['member_status'] == STATUS_LEADER) ? 'leader_row' : 'member_row';

			$row['session_time'] = empty($session_times[$row['user_id']]) ? 0 : $session_times[$row['user_id']];

			if (!empty($row['user_allow_viewemail']))
			{
				$email = ($_CORE_CONFIG['email']['email_enable']) ? generate_link('members_list&amp;mode=email&amp;u='.$row['user_id']) : ((false) ? '' : 'mailto:' . $row['user_email']);
			}
			else
			{
				$email = '';
			}
		
			$online = ($row['session_time'] >= $online_time);

			$_CLASS['core_template']->assign_vars_array($option_row, array(
					'USERNAME'		=> $row['username'],
					'USER_COLOR'	=> ($row['user_colour']) ? $row['user_colour'] : '',

					'JOINED'		=> $_CLASS['core_user']->format_date($row['user_reg_date']),

					'ONLINE_IMG'	=> ($online) ? $_CLASS['core_user']->img('btn_online', $_CLASS['core_user']->lang['USER_ONLINE']) : $_CLASS['core_user']->img('btn_offline', $_CLASS['core_user']->lang['USER_ONLINE']),
			
					'U_PROFILE'		=> generate_link('members_list&amp;mode=viewprofile&amp;u='.$row['user_id']),
					'U_PM'			=> generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$row['user_id']),
					'U_EMAIL'		=> $email,
					'U_WWW'			=> ($row['user_website']) ? $row['user_website'] : '',
					'U_VIEWPROFILE' => generate_link('members_list&amp;mode=viewprofile&amp;u=' . $row['user_id']),

					'S_ONLINE'		=> $online
				)
			);
		}
		$_CLASS['core_db']->free_result($result);

	// Generate page
	$_CLASS['core_template']->assign_array(array(
		'PAGINATION' 	=> generate_pagination($pagination_url2, $total_users, 20, $start),
		'PAGE_NUMBER' 	=> on_page($total_users, 20, $start),
		'TOTAL_USERS'	=> ($total_users == 1) ? $_CLASS['core_user']->lang['LIST_USER'] : sprintf($_CLASS['core_user']->lang['LIST_USERS'], $total_users),

		'PROFILE_IMG'	=> $_CLASS['core_user']->img('btn_profile', $_CLASS['core_user']->lang['PROFILE']),
		'PM_IMG'		=> $_CLASS['core_user']->img('btn_pm', $_CLASS['core_user']->lang['MESSAGE']),
		'EMAIL_IMG'		=> $_CLASS['core_user']->img('btn_email', $_CLASS['core_user']->lang['EMAIL']),
		'WWW_IMG'		=> $_CLASS['core_user']->img('btn_www', $_CLASS['core_user']->lang['WWW']),
		'ICQ_IMG'		=> $_CLASS['core_user']->img('btn_icq', $_CLASS['core_user']->lang['ICQ']),
		'AIM_IMG'		=> $_CLASS['core_user']->img('btn_aim', $_CLASS['core_user']->lang['AIM']),
		'MSN_IMG'		=> $_CLASS['core_user']->img('btn_msnm', $_CLASS['core_user']->lang['MSNM']),
		'YIM_IMG'		=> $_CLASS['core_user']->img('btn_yim', $_CLASS['core_user']->lang['YIM']),
		'JABBER_IMG'	=> $_CLASS['core_user']->img('btn_jabber', $_CLASS['core_user']->lang['JABBER']),
		'SEARCH_IMG'	=> $_CLASS['core_user']->img('btn_search', $_CLASS['core_user']->lang['SEARCH']),

		'U_FIND_MEMBER'		=> generate_link('members_list&amp;mode=search_user'),
		'U_HIDE_FIND_MEMBER'=> ($mode === 'search_user') ? generate_link($u_hide_find_member) : '',
		'U_SORT_USERNAME'	=> generate_link($pagination_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_FROM'		=> generate_link($pagination_url . '&amp;sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_JOINED'		=> generate_link($pagination_url . '&amp;sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_ONLINE'		=> generate_link($pagination_url . '&amp;sk=d&amp;sd=' . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_EMAIL'		=> generate_link($pagination_url . '&amp;sk=e&amp;sd=' . (($sort_key == 'e' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_WEBSITE'	=> generate_link($pagination_url . '&amp;sk=f&amp;sd=' . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a')),
		//'U_SORT_ICQ'		=> generate_link($pagination_url . '&amp;sk=g&amp;sd=' . (($sort_key == 'g' && $sort_dir == 'a') ? 'd' : 'a')),
		//'U_SORT_AIM'		=> generate_link($pagination_url . '&amp;sk=h&amp;sd=' . (($sort_key == 'h' && $sort_dir == 'a') ? 'd' : 'a')),
		//'U_SORT_MSN'		=> generate_link($pagination_url . '&amp;sk=i&amp;sd=' . (($sort_key == 'i' && $sort_dir == 'a') ? 'd' : 'a')),
		//'U_SORT_YIM'		=> generate_link($pagination_url . '&amp;sk=j&amp;sd=' . (($sort_key == 'j' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_ACTIVE'		=> generate_link($pagination_url . '&amp;sk=k&amp;sd=' . (($sort_key == 'k' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_LIST_CHAR'		=> generate_link($pagination_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'l' && $sort_dir == 'a') ? 'd' : 'a')),

		'S_SEND_MESSAGE'	=> true,
		'S_SHOW_GROUP'		=> ($mode == 'group') ? true : false,
		'S_MODE_SELECT'		=> $s_sort_key,
		'S_ORDER_SELECT'	=> $s_sort_dir,
		'S_CHAR_OPTIONS'	=> $s_char_options,
		'S_MODE_ACTION'		=> generate_link($pagination_url . (($form) ? "&amp;form=$form" : ''))
	));
}


$_CLASS['core_template']->assign('DISPLAY_STYLESHEET_LINK', $window);
$_CLASS['core_display']->display($page_title, 'modules/members_list/'.$template_html);

script_close();
// ---------
// FUNCTIONS
//

function get_user_rank($user_rank, $user_posts, &$rank_title, &$rank_img)
{
	global $ranks, $config;

	if (!empty($user_rank))
	{
		$rank_title = (isset($ranks['special'][$user_rank]['rank_title'])) ? $ranks['special'][$user_rank]['rank_title'] : '';
		$rank_img = (!empty($ranks['special'][$user_rank]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$user_rank]['rank_image'] . '" alt="' . $ranks['special'][$user_rank]['rank_title'] . '" title="' . $ranks['special'][$user_rank]['rank_title'] . '" />' : '';
	}
	elseif (isset($ranks['normal']))
	{
		foreach ($ranks['normal'] as $rank)
		{
			if ($user_posts >= $rank['rank_min'])
			{
				$rank_title = $rank['rank_title'];
				$rank_img = (!empty($rank['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $rank['rank_image'] . '" alt="' . $rank['rank_title'] . '" title="' . $rank['rank_title'] . '" />' : '';
				break;
			}
		}
	}
}

?>