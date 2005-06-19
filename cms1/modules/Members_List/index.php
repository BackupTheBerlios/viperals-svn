<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
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
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    die();
}

require_once($site_file_root.'includes/forums/functions.php');
load_class($site_file_root.'includes/forums/auth.php', 'auth');

$_CLASS['auth']->acl($_CLASS['core_user']->data);

$_CLASS['core_user']->add_lang();
$_CLASS['core_user']->add_img(false, 'Forums');

$_CLASS['core_template']->assign(array(
	'S_SEARCH_USER'	=> false,
	'S_SHOW_GROUP'	=> false
	));

// Grab data
$mode		= request_var('mode', '');
$action		= request_var('action', '');
$user_id	= request_var('u', ANONYMOUS);
$group_id	= request_var('g', 0);
$topic_id	= request_var('t', 0);

$start		= request_var('start', 0);
$submit		= (isset($_POST['submit'])) ? true : false;

$sort_key	= request_var('sk', 'c');
$sort_dir	= request_var('sd', 'a');

$window		= false;

// Grab rank information for later
obtain_ranks($ranks);

// What do you want to do today? ... oops, I think that line is taken ...
switch ($mode)
{
	case 'leaders':
		// Display a listing of board admins, moderators

		$_CLASS['core_user']->add_lang('groups', 'Forums');
		
		$page_title = $_CLASS['core_user']->lang['LEADER'];
		$template_html = 'memberlist_leaders.html';

		$user_ary = $_CLASS['auth']->acl_get_list(false, array('a_', 'm_'), false);

		$admin_id_ary = $mod_id_ary = $forum_id_ary = array();
		foreach ($user_ary as $forum_id => $forum_ary)
		{
			foreach ($forum_ary as $auth_option => $id_ary)
			{
				(!$forum_id && $auth_option == 'a_') ? $admin_id_ary += $id_ary : $mod_id_ary += $id_ary;

				if ($forum_id)
				{
					foreach ($id_ary as $id)
					{
						$forum_id_ary[$id][] = $forum_id;
					}
				}
			}
		}

		$sql = 'SELECT forum_id, forum_name 
			FROM ' . FORUMS_TABLE . '
			WHERE forum_type = ' . FORUM_POST;
		$result = $_CLASS['core_db']->sql_query($sql);
		
		$forums = array();
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$forums[$row['forum_id']] = $row['forum_name'];
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$sql = 'SELECT u.user_id, u.username, u.user_colour, u.user_rank, u.user_posts, g.group_id, g.group_name, g.group_colour, g.group_type, ug.user_id as ug_user_id
			FROM ' . USERS_TABLE . ' u, ' . GROUPS_TABLE . ' g
			LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (ug.group_id = g.group_id AND ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . ')
			WHERE u.user_id IN (' . implode(', ', $admin_id_ary + $mod_id_ary) . ')
				AND u.group_id = g.group_id
			ORDER BY g.group_name ASC, u.username ASC';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$which_row = (in_array($row['user_id'], $admin_id_ary)) ? 'admin' : 'mod';

			$s_forum_select = '';
			if ($which_row == 'mod' && sizeof(array_diff(array_keys($forums), $forum_id_ary[$row['user_id']])))
			{
				foreach ($forum_id_ary[$row['user_id']] as $forum_id)
				{
					if (isset($forums[$forum_id]) && $_CLASS['auth']->acl_get('f_list', $forum_id))
					{
						$s_forum_select .= '<option value="">' . $forums[$forum_id] . '</option>';
					}
				}
			}
			
			if ($row['group_type'] == GROUP_HIDDEN && !$_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel') && $row['ug_user_id'] != $_CLASS['core_user']->data['user_id'])
			{
				$group_name = $_CLASS['core_user']->lang['UNDISCLOSED'];
				$u_group = '';
			}
			else
			{
				$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'];
				$u_group = generate_link('Members_List&amp;mode=group&amp;g='.$row['group_id']);
			}

			$rank_title = $rank_img = '';
			get_user_rank($row['user_rank'], $row['user_posts'], $rank_title, $rank_img);

			$_CLASS['core_template']->assign_vars_array($which_row, array(
				'USER_ID'		=> $row['user_id'],
				'FORUMS'		=> $s_forum_select,
				'USERNAME'		=> $row['username'],
				'USER_COLOR'	=> $row['user_colour'],
				'RANK_TITLE'	=> $rank_title,
				'GROUP_NAME'	=> $group_name,
				'GROUP_COLOR'	=> $row['group_colour'],

				'RANK_IMG'		=> $rank_img,

				'U_GROUP'		=> $u_group,
				'U_VIEWPROFILE'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u='.$row['user_id']),
				'U_PM'			=> ($_CLASS['auth']->acl_get('u_sendpm')) ? generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$user_id) : '')
			);
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$_CLASS['core_template']->assign(array(
			'PM_IMG'		=> $_CLASS['core_user']->img('btn_pm', $_CLASS['core_user']->lang['MESSAGE']))
		);
		break;

	case 'contact':
		$_CLASS['core_template']->assign(array(
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
			case 'icq':
				$lang = 'ICQ';
				$sql_field = 'user_icq';
				$s_select = 'S_SEND_ICQ';
				$s_action = 'http://wwp.icq.com/scripts/WWPMsg.dll';
				break;

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
				$s_action = generate_link("Members_List&amp;mode=contact&amp;action=$action&amp;u=$user_id");
				break;

			default:
				trigger_error('NO_USER_DATA');
				die;
				break;
		}

		// Grab relevant data
		$sql = "SELECT user_id, username, user_email, user_lang, $sql_field
			FROM " . USERS_TABLE . "
			WHERE user_id = $user_id
				AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
		$result = $_CLASS['core_db']->sql_query($sql);

		if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
		{
			trigger_error('NO_USER_DATA');
		}
		$_CLASS['core_db']->sql_freeresult($result);

		// Post data grab actions
		switch ($action)
		{
			case 'icq':
				$presence_img = '<img src="http://web.icq.com/whitepages/online?icq=' . $row[$sql_field] . '&amp;img=5" width="18" height="18" border="0" alt="" />';
				break;

			case 'jabber':
				if ($submit && @extension_loaded('xml'))
				{
					// Add class loader
					require_once($site_file_root.'includes/forums/functions_messenger.php');

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
				break;
		}

		$_CLASS['core_template']->assign(array(
		
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
		// Display a profile
		if ($user_id == ANONYMOUS)
		{
			trigger_error('NO_USER');
		}

		// We left join on the session table to see if the user is currently online
		$sql = 'SELECT username, user_id, user_type, group_id, user_colour, user_permissions, user_sig, user_sig_bbcode_uid, user_sig_bbcode_bitfield, user_allow_viewemail, user_posts, user_regdate, user_rank, user_from, user_occ, user_interests, user_website, user_email, user_icq, user_aim, user_yim, user_msnm, user_jabber, user_avatar, user_avatar_width, user_avatar_height, user_avatar_type, user_lastvisit
			FROM ' . USERS_TABLE . "
			WHERE user_id = $user_id
				AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
		$result = $_CLASS['core_db']->sql_query($sql);

		if (!($member = $_CLASS['core_db']->sql_fetchrow($result)))
		{
			$_CLASS['core_db']->sql_freeresult($result);
			trigger_error('NO_USER');
		}
		
		$_CLASS['core_db']->sql_freeresult($result);

		$sql = 'SELECT g.group_id, g.group_name, g.group_type
			FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug
			WHERE ug.user_id = $user_id
				AND g.group_id = ug.group_id" . ((!$_CLASS['auth']->acl_gets('a_group')) ? ' AND group_type <> ' . GROUP_HIDDEN : '') . '
			ORDER BY group_type, group_name';
		$result = $_CLASS['core_db']->sql_query($sql);

		$group_options = '';
		
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$group_options .= '<option value="' . $row['group_id'] . '"'.(($member['group_id'] == $row['group_id'])? ' selected="selected"' : '').'>' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['core_db']->sql_freeresult($result);
		
		$page_title = sprintf($_CLASS['core_user']->lang['VIEWING_PROFILE'], $member['username']);
		$template_html = 'memberlist_view.html';
		
		$sql = 'SELECT MAX(session_time) AS session_time
			FROM ' . SESSIONS_TABLE . "
			WHERE session_user_id = $user_id";
		$result = $_CLASS['core_db']->sql_query($sql);

		$row = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);

		$member['session_time'] = (isset($row['session_time'])) ? $row['session_time'] : 0;
		unset($row);

		// Obtain list of forums where this users post count is incremented
		$auth2 = new auth();
		$auth2->acl($member);
		$f_postcount_ary = $auth2->acl_getf('f_postcount');

		$sql_forums = array();
		foreach ($f_postcount_ary as $forum_id => $allow)
		{
			if ($allow['f_postcount'])
			{
				$sql_forums[] = $forum_id;
			}
		}

		$post_count_sql = (sizeof($sql_forums)) ? 'AND f.forum_id IN (' . implode(', ', $sql_forums) . ')' : '';
		unset($sql_forums, $f_postcount_ary, $auth2);

		// Grab all the relevant data
		$sql = 'SELECT COUNT(p.post_id) AS num_posts
			FROM ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . " f
			WHERE p.poster_id = $user_id
				AND f.forum_id = p.forum_id
				$post_count_sql";
		$result = $_CLASS['core_db']->sql_query($sql);

		$num_real_posts = min($_CLASS['core_user']->data['user_posts'], $_CLASS['core_db']->sql_fetchfield('num_posts', 0, $result));
		$_CLASS['core_db']->sql_freeresult($result);

		// Change post_count_sql to an forum_id array the user is able to see
		$f_forum_ary = $_CLASS['auth']->acl_getf('f_read');

		$sql_forums = array();
		foreach ($f_forum_ary as $forum_id => $allow)
		{
			if (isset($allow['f_read']) && $allow['f_read'])
			{
				$sql_forums[] = $forum_id;
			}
		}

		$post_count_sql = (sizeof($sql_forums)) ? 'AND f.forum_id IN (' . implode(', ', $sql_forums) . ')' : '';
		unset($sql_forums, $f_forum_ary);

		if ($post_count_sql)
		{
			$sql = 'SELECT f.forum_id, f.forum_name, COUNT(post_id) AS num_posts
				FROM ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . " f
				WHERE p.poster_id = $user_id
					AND f.forum_id = p.forum_id
					$post_count_sql
				GROUP BY f.forum_id, f.forum_name
				ORDER BY num_posts DESC";
			$result = $_CLASS['core_db']->sql_query_limit($sql, 1);

			$active_f_row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			$sql = 'SELECT t.topic_id, t.topic_title, COUNT(p.post_id) AS num_posts
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . " f
				WHERE p.poster_id = $user_id
					AND t.topic_id = p.topic_id
					AND f.forum_id = t.forum_id
					$post_count_sql
				GROUP BY t.topic_id, t.topic_title
				ORDER BY num_posts DESC";
			$result = $_CLASS['core_db']->sql_query_limit($sql, 1);

			$active_t_row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
		}
		else
		{
			$active_f_row = $active_t_row = array();
		}

		// Do the relevant calculations
		$memberdays = max(1, round((time() - $member['user_regdate']) / 86400));
		$posts_per_day = $member['user_posts'] / $memberdays;
		$percentage = ($config['num_posts']) ? min(100, ($num_real_posts / $config['num_posts']) * 100) : 0;

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

		if ($member['user_sig_bbcode_bitfield'] && $member['user_sig'])
		{
			// Add class loader
			require_once($site_file_root.'includes/forums/bbcode.php');
			$bbcode = new bbcode();
			$bbcode->bbcode_second_pass($member['user_sig'], $member['user_sig_bbcode_uid'], $member['user_sig_bbcode_bitfield']);
		}

		if ($member['user_sig'])
		{
			$member['user_sig'] = censor_text(smiley_text($member['user_sig']));
		}

		$poster_avatar = '';
		if (!empty($member['user_avatar']))
		{
			switch ($member['user_avatar_type'])
			{
				case AVATAR_UPLOAD:
					$poster_avatar = $config['avatar_path'] . '/';
					break;
				case AVATAR_GALLERY:
					$poster_avatar = $config['avatar_gallery_path'] . '/';
					break;
			}
			$poster_avatar .= $member['user_avatar'];

			$poster_avatar = '<img src="' . $poster_avatar . '" width="' . $member['user_avatar_width'] . '" height="' . $member['user_avatar_height'] . '" border="0" alt="" />';
		}

		$_CLASS['core_template']->assign(show_profile($member));
		
		// Custom Profile Fields
		$profile_fields = array();
		if ($config['load_cpf_viewprofile'])
		{
			require_once($site_file_root.'includes/forums/functions_profile_fields.php');
			$cp = new custom_profile();
			$profile_fields = $cp->generate_profile_fields_template('grab', $user_id);

			$profile_fields = (isset($profile_fields[$user_id])) ? $cp->generate_profile_fields_template('show', false, $profile_fields[$user_id]) : array();
		}
		
		$_CLASS['core_template']->assign(array(
			'POSTS_DAY'			=> sprintf($_CLASS['core_user']->lang['POST_DAY'], $posts_per_day),
			'POSTS_PCT'			=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $percentage),
			'ACTIVE_FORUM'		=> $active_f_name,
			'ACTIVE_FORUM_POSTS'=> ($active_f_count == 1) ? sprintf($_CLASS['core_user']->lang['USER_POST'], 1) : sprintf($_CLASS['core_user']->lang['USER_POSTS'], $active_f_count),
			'ACTIVE_FORUM_PCT'	=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $active_f_pct),
			'ACTIVE_TOPIC'		=> censor_text($active_t_name),
			'ACTIVE_TOPIC_POSTS'=> ($active_t_count == 1) ? sprintf($_CLASS['core_user']->lang['USER_POST'], 1) : sprintf($_CLASS['core_user']->lang['USER_POSTS'], $active_t_count),
			'ACTIVE_TOPIC_PCT'	=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $active_t_pct),

			'LOCATION'		=> (!empty($member['user_from'])) ? censor_text($member['user_from']) : '',
			'OCCUPATION'    => (!empty($member['user_occ'])) ? censor_text($member['user_occ']) : '',
			'INTERESTS'		=> (!empty($member['user_interests'])) ? censor_text($member['user_interests']) : '',
			'SIGNATURE'		=> (!empty($member['user_sig'])) ? str_replace("\n", '<br />', $member['user_sig']) : '',

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

			'S_PROFILE_ACTION'	=> generate_link('Members_List&amp;mode=group'),
			'S_GROUP_OPTIONS'	=> $group_options,
			'S_CUSTOM_FIELDS'	=> (isset($profile_fields['row']) && sizeof($profile_fields['row'])) ? true : false,
			
			'U_ADD_FRIEND'		=> generate_link('Control_Panel&amp;i=zebra&amp;add=' . urlencode($member['username'])),
			'U_ADD_FOE'			=> generate_link('Control_Panel&amp;i=zebra&amp;mode=foes&amp;add=' . urlencode($member['username'])),
			'U_ACTIVE_FORUM'	=> generate_link('Forums&amp;file=viewforum&amp;f='.$active_f_id),
			'U_ACTIVE_TOPIC'	=> generate_link('Forums&amp;file=viewtopic&amp;t='.$active_t_id),
			
			'L_VIEWING_PROFILE' 	=> sprintf($_CLASS['core_user']->lang['VIEWING_PROFILE'], $member['username']),
		));
		
		if (isset($profile_fields['row']) && sizeof($profile_fields['row']))
		{
			$_CLASS['core_template']->assign($profile_fields['row']);
		}
		
		if (isset($profile_fields['blockrow']) && sizeof($profile_fields['blockrow']))
		{
			foreach ($profile_fields['blockrow'] as $field_data)
			{
				$_CLASS['core_template']->assign_vars_array('custom_fields', $field_data);
			}
		}
		break;

	case 'email':
		// Send an email
		$page_title = $_CLASS['core_user']->lang['SEND_EMAIL'];
		$template_html = 'memberlist_email.html';
		
		$_CLASS['core_template']->assign(array(
			'MESSAGE' => false,
			'SUBJECT' => false)
		);
		
		if (!$_CORE_CONFIG['email']['email_enable'])
		{
			trigger_error('EMAIL_DISABLED');
		}

		if (($user_id == ANONYMOUS || !$config['board_email_form']) && !$topic_id)
		{
			trigger_error('NO_EMAIL');
		}

		if (!$_CLASS['auth']->acl_get('u_sendemail'))
		{
			trigger_error('NO_EMAIL');
		}

		// Are we trying to abuse the facility?
		if (time() - $_CLASS['core_user']->data['user_emailtime'] < $config['flood_interval'])
		{
			trigger_error('FLOOD_EMAIL_LIMIT');
		}

		$name		= strip_tags(request_var('name', ''));
		$email		= strip_tags(request_var('email', ''));
		$email_lang = request_var('lang', '');
		$subject	= request_var('subject', '');
		$message	= request_var('message', '');
		$cc			= (!empty($_POST['cc_email'])) ? true : false;

		// Are we sending an email to a user on this board? Or are we sending a
		// topic heads-up message?
		if (!$topic_id)
		{
			// Get the appropriate username, etc.
			$sql = 'SELECT username, user_email, user_allow_viewemail, user_lang, user_jabber, user_notify_type
				FROM ' . USERS_TABLE . "
				WHERE user_id = $user_id
					AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
			$result = $_CLASS['core_db']->sql_query($sql);

			if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
			{
				trigger_error('NO_USER');
			}
			$_CLASS['core_db']->sql_freeresult($result);

			// Can we send email to this user?
			if (!$row['user_allow_viewemail'] && !$_CLASS['auth']->acl_get('a_user'))
			{
				trigger_error('NO_EMAIL');
			}
		}
		else
		{
			$sql = 'SELECT forum_id, topic_title
				FROM ' . TOPICS_TABLE . "
				WHERE topic_id = $topic_id";
			$result = $_CLASS['core_db']->sql_query($sql);

			if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
			{
				trigger_error('NO_TOPIC');
			}
			$_CLASS['core_db']->sql_freeresult($result);

			if (!$_CLASS['auth']->acl_get('f_read', $row['forum_id']))
			{
				trigger_error('NO_FORUM_READ');
			}

			if (!$_CLASS['auth']->acl_get('f_email', $row['forum_id']))
			{
				trigger_error('NO_EMAIL');
			}
		}

		// User has submitted a message, handle it
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
				if (!$email || !preg_match('#^.*?@(.*?\.)?[a-z0-9\-]+\.[a-z]{2,4}$#i', $email))
				{
					$error[] = $_CLASS['core_user']->lang['EMPTY_ADDRESS_EMAIL'];
				}

				if (!$name)
				{
					$error[] = $_CLASS['core_user']->lang['EMPTY_NAME_EMAIL'];
				}
			}

			if (!sizeof($error))
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_emailtime = ' . time() . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$result = $_CLASS['core_db']->sql_query($sql);

				// Add class loader
				require_once($site_file_root.'includes/forums/functions_messenger.php');

				$email_tpl	= (!$topic_id) ? 'profile_send_email' : 'email_notify';
				$email_lang = (!$topic_id) ? $row['user_lang'] : $email_lang;
				$email		= (!$topic_id) ? $row['user_email'] : $email;

				$messenger = new messenger();

				$messenger->template($email_tpl, $email_lang);
				$messenger->subject($subject);

				$messenger->replyto($_CLASS['core_user']->data['user_email']);
				$messenger->to($email, $row['username']);

				if (!$topic_id)
				{
					$messenger->im($row['user_jabber'], $row['username']);
				}

				if ($cc)
				{
					$messenger->cc($_CLASS['core_user']->data['user_email'], $_CLASS['core_user']->data['username']);
				}

				$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger->headers('X-AntiAbuse: User_id - ' . $_CLASS['core_user']->data['user_id']);
				$messenger->headers('X-AntiAbuse: Username - ' . $_CLASS['core_user']->data['username']);
				$messenger->headers('X-AntiAbuse: User IP - ' . $_CLASS['core_user']->ip);

				$messenger->assign_vars(array(
					'SITENAME'		=> $_CORE_CONFIG['global']['sitename'],
					'BOARD_EMAIL'	=> $config['board_contact'],
					'FROM_USERNAME' => stripslashes($_CLASS['core_user']->data['username']),
					'TO_USERNAME'   => ($topic_id) ? stripslashes($name) : stripslashes($row['username']),
					'MESSAGE'		=> $message,
					'TOPIC_NAME'	=> ($topic_id) ? strtr($row['topic_title'], array_flip(get_html_translation_table(HTML_ENTITIES))) : '',

					'U_TOPIC'	=> ($topic_id) ? generate_link('Forums&amp;file=viewforum&amp;f=' . $row['forum_id'] . "&t=$topic_id", true, true, false) : '')
				);

				$messenger->send($row['user_notify_type']);
				$messenger->save_queue();

				$_CLASS['core_display']->meta_refresh(3, generate_link());
				$message = (!$topic_id) ? sprintf($_CLASS['core_user']->lang['RETURN_INDEX'],  '<a href="' . generate_link() . '">', '</a>') : sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'],  '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=" . $row['topic_id']) . '">', '</a>');
				trigger_error($_CLASS['core_user']->lang['EMAIL_SENT'] . '<br /><br />' . $message);
			}
		}

		if ($topic_id)
		{
			$_CLASS['core_template']->assign(array(
				'EMAIL'			=> htmlspecialchars($email),
				'NAME'			=> htmlspecialchars($name),
				'TOPIC_TITLE'	=> $row['topic_title'],

				'U_TOPIC'	=> generate_link("Forums&amp;file=viewtopic&amp;f={$row['forum_id']}&amp;t=$topic_id"),
				'S_LANG_OPTIONS'=> ($topic_id) ? language_select($email_lang) : '')
			);
		}
		$_CLASS['core_template']->assign(array(
			'USERNAME'		=> (!$topic_id) ? addslashes($row['username']) : '',
			'ERROR_MESSAGE'	=> (sizeof($error)) ? implode('<br />', $error) : '',

			'L_EMAIL_BODY_EXPLAIN'	=> $_CLASS['core_user']->get_lang((!$topic_id) ? 'EMAIL_BODY_EXPLAIN' : 'EMAIL_TOPIC_EXPLAIN'),

			'S_POST_ACTION' => (!$topic_id) ? generate_link('Members_List&amp;mode=email&amp;u='.$user_id, array('full' => true)) : generate_link("Members_List&amp;mode=email&amp;f={$row['forum_id']}&amp;t=$topic_id", array('full' => true)),
			'S_SEND_USER'	=> (!$topic_id) ? true : false,
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
		$sort_key_sql = array('a' => 'u.username', 'b' => 'u.user_from', 'c' => 'u.user_regdate', 'd' => 'u.user_posts', 'e' => 'u.user_email', 'f' => 'u.user_website', 'g' => 'u.user_icq', 'h' => 'u.user_aim', 'i' => 'u.user_msnm', 'j' => 'u.user_yim', 'k' => 'u.user_jabber', 'l' => 'u.user_lastvisit', 'm' => 'u.user_rank DESC, u.user_posts');

		$sort_dir_text = array('a' => $_CLASS['core_user']->lang['ASCENDING'], 'd' => $_CLASS['core_user']->lang['DESCENDING']);

		$s_sort_key = '';
		foreach ($sort_key_text as $key => $value)
		{
			$selected = ($sort_key == $key) ? ' selected="selected"' : '';
			$s_sort_key .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}

		$s_sort_dir = '';
		foreach ($sort_dir_text as $key => $value)
		{
			$selected = ($sort_dir == $key) ? ' selected="selected"' : '';
			$s_sort_dir .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}

		// Additional sorting options for user search ... if search is enabled, if not
		// then only admins can make use of this (for ACP functionality)
		$sql_select = $sql_from = $sql_where = $sql_fields =  '';

		$form	= $window = request_var('form', '');
		$field	= request_var('field', 'username');

		if ($mode == 'searchuser' && ($config['load_search'] || $_CLASS['auth']->acl_get('a_')))
		{
			$username	= request_var('username', '');
			$email		= request_var('email', '');
			$icq		= request_var('icq', '');
			$aim		= request_var('aim', '');
			$yahoo		= request_var('yahoo', '');
			$msn		= request_var('msn', '');
			$jabber		= request_var('jabber', '');

			$joined_select	= request_var('joined_select', 'lt');
			$active_select	= request_var('active_select', 'lt');
			$count_select	= request_var('count_select', 'eq');
			$joined			= explode('-', request_var('joined', ''));
			$active			= explode('-', request_var('active', ''));
			$count			= (request_var('count', '')) ? request_var('count', 0) : '';
			$ipdomain		= request_var('ip', '');

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

			$sql_where .= ($username) ? " AND u.username LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($username)) ."'" : '';
			$sql_where .= ($email) ? " AND u.user_email LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($email)) ."' " : '';
			$sql_where .= ($icq) ? " AND u.user_icq LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($icq)) ."' " : '';
			$sql_where .= ($aim) ? " AND u.user_aim LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($aim)) ."' " : '';
			$sql_where .= ($yahoo) ? " AND u.user_yim LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($yahoo)) ."' " : '';
			$sql_where .= ($msn) ? " AND u.user_msnm LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($msn)) ."' " : '';
			$sql_where .= ($jabber) ? " AND u.user_jabber LIKE '" . str_replace('*', '%', $_CLASS['core_db']->sql_escape($jabber)) . "' " : '';
			$sql_where .= (is_numeric($count)) ? ' AND u.user_posts ' . $find_key_match[$count_select] . ' ' . (int) $count . ' ' : '';
			$sql_where .= (sizeof($joined) > 1) ? " AND u.user_regdate " . $find_key_match[$joined_select] . ' ' . gmmktime(0, 0, 0, intval($joined[1]), intval($joined[2]), intval($joined[0])) : '';
			$sql_where .= (sizeof($active) > 1) ? " AND u.user_lastvisit " . $find_key_match[$active_select] . ' ' . gmmktime(0, 0, 0, $active[1], intval($active[2]), intval($active[0])) : '';

			if ($ipdomain && $_CLASS['auth']->acl_get('m_ip'))
			{
				$ips = (preg_match('#[a-z]#', $ipdomain)) ? implode(', ', preg_replace('#([0-9]{1,3}\.[0-9]{1,3}[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#', "'\\1'", gethostbynamel($ipdomain))) : "'" . str_replace('*', '%', $ipdomain) . "'";

				$sql = 'SELECT DISTINCT poster_id
					FROM ' . POSTS_TABLE . '
					WHERE poster_ip ' . ((preg_match('#%#', $ips)) ? 'LIKE' : 'IN') . " ($ips)";
				$result = $_CLASS['core_db']->sql_query($sql);

				if ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$ip_sql = array();
					do
					{
						$ip_sql[] = $row['poster_id'];
					}
					while ($row = $_CLASS['core_db']->sql_fetchrow($result));

					$sql_where .= ' AND u.user_id IN (' . implode(', ', $ip_sql) . ')';
				}
				else
				{
					// A minor fudge but it does the job :D
					$sql_where .= " AND u.user_id IN ('-1')";
				}
			}
		}

		$first_char = request_var('first_char', '');

		if ($first_char == 'other')
		{
			$sql_where = '';
			for ($i = 65; $i < 91; $i++)
			{
				$sql_where .= " AND u.username NOT LIKE '" . chr($i) . "%'";
			}
		}
		else if ($first_char)
		{
			$sql_where = " AND u.username LIKE '" . $_CLASS['core_db']->sql_escape(substr($first_char, 0, 1)) . "%'";
		}

		// Are we looking at a usergroup? If so, fetch additional info
		// and further restrict the user info query
		if ($mode == 'group')
		{
			// We JOIN here to save a query for determining membership for hidden groups. ;)
			
			$sql = 'SELECT g.*, ug.user_id
				FROM ' . GROUPS_TABLE . ' g
				LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . " AND ug.group_id = $group_id)
				WHERE g.group_id = $group_id";
	
			$result = $_CLASS['core_db']->sql_query($sql);
			$group_row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if (!$group_row)
			{
				trigger_error('NO_GROUP');
			}

			switch ($group_row['group_type'])
			{
				case GROUP_OPEN:
					$group_row['group_type'] = 'OPEN';
					break;

				case GROUP_CLOSED:
					$group_row['group_type'] = 'CLOSED';
					break;

				case GROUP_HIDDEN:
					$group_row['group_type'] = 'HIDDEN';
					
					// Check for membership or special permissions
					if (!$_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel') && $group_row['user_id'] != $_CLASS['core_user']->data['user_id'])
					{
						trigger_error('NO_GROUP');
					}
					break;

				case GROUP_SPECIAL:
					$group_row['group_type'] = 'SPECIAL';
					break;

				case GROUP_FREE:
					$group_row['group_type'] = 'FREE';
					break;
			}

			$avatar_img = '';
			if ($group_row['group_avatar'])
			{
				switch ($group_row['group_avatar_type'])
				{
					case AVATAR_UPLOAD:
						$avatar_img = $config['avatar_path'] . '/';
						break;
					case AVATAR_GALLERY:
						$avatar_img = $config['avatar_gallery_path'] . '/';
						break;
				}
				$avatar_img .= $group_row['group_avatar'];

				$avatar_img = '<img src="' . $avatar_img . '" width="' . $group_row['group_avatar_width'] . '" height="' . $group_row['group_avatar_height'] . '" border="0" alt="" />';
			}

			$rank_title = $rank_img = '';
			if ($group_row['group_rank'] != -1)
			{
				$rank_title = $ranks['special'][$group_row['group_rank']]['rank_title'];
				$rank_img = (!empty($ranks['special'][$group_row['group_rank']]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$group_row['group_rank']]['rank_image'] . '" border="0" alt="' . $ranks['special'][$group_row['group_rank']]['rank_title'] . '" title="' . $ranks['special'][$group_row['group_rank']]['rank_title'] . '" /><br />' : '';	
			}

			$_CLASS['core_template']->assign(array(
				'GROUP_DESC'    => $group_row['group_description'],
				'GROUP_NAME'    => $group_row['group_name'],
				'GROUP_COLOR'   => $group_row['group_colour'],
				'GROUP_TYPE'	=> $_CLASS['core_user']->lang['GROUP_IS_'. $group_row['group_type']],
				'GROUP_RANK'	=> $rank_title,

				'AVATAR_IMG'	=> $avatar_img,
				'RANK_IMG'		=> $rank_img,

				'U_PM'			=> ($_CLASS['auth']->acl_get('u_sendpm') && $group_row['group_receive_pm'] && $config['allow_mass_pm']) ? generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;g='.$group_id) : '',)
			);

			$sql_fields = ', ug.user_status';
			$sql_from = ', ' . USER_GROUP_TABLE . ' ug ';
			$sql_where .= " AND u.user_id = ug.user_id AND ug.group_id = $group_id";
		}

		// Sorting and order
		$order_by = $sort_key_sql[$sort_key] . '  ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

		// Count the users ...
		if ($sql_where)
		{
			$sql = 'SELECT COUNT(u.user_id) AS total_users
				FROM ' . USERS_TABLE . " u$sql_from
				WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
				$sql_where";
			$result = $_CLASS['core_db']->sql_query($sql);

			$total_users = ($row = $_CLASS['core_db']->sql_fetchrow($result)) ? $row['total_users'] : 0;
			$_CLASS['core_db']->sql_freeresult($result);
		}
		else
		{
			$total_users = $config['num_users'];
		}
		
		$s_char_options = '<option value=""' . ((!$first_char) ? ' selected="selected"' : '') . '>&nbsp; &nbsp;</option>';

		for ($i = 65; $i < 91; $i++)
		{
			$s_char_options .= '<option value="' . chr($i) . '"' . (($first_char == chr($i)) ? ' selected="selected"' : '') . '>' . chr($i) . '</option>';
		}
		$s_char_options .= '<option value="other"' . (($first_char == 'other') ? ' selected="selected"' : '') . '>Other</option>';
		
		// Pagination string
		$pagination_url = $pagination_url2 = 'Members_List';

		// Build a relevant pagination_url
		$global_var = ($submit) ? '_POST' : '_GET';
		foreach ($$global_var as $key => $var)
		{
			// what don't we need ?
			if (in_array($key, array('submit', 'sid', 'mod')) || !$var)
			{ 
				continue;
			}
			
			$pagination_url .= '&amp;' . $key . '=' . urlencode(htmlspecialchars($var));
			$pagination_url2 .= ($key != 'start') ? '&amp;' . $key . '=' . urlencode(htmlspecialchars($var)) : '';
		}

		$u_hide_find_member = $pagination_url;
		$pagination_url .= (($mode) ? '&amp;mode='.$mode : '') . (($first_char) ? '&amp;first_char='.$first_char : '');

		// Some search user specific data
		if ($mode == 'searchuser' && ($config['load_search'] || $_CLASS['auth']->acl_get('a_')))
		{
			$_CLASS['core_template']->assign(array(
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
				'S_SEARCH_ACTION' 		=> generate_link("Members_List&amp;mode=searchuser&amp;form=$form&amp;field=$field"))
			);
		}

		$sql = 'SELECT session_user_id, MAX(session_time) AS session_time
			FROM ' . SESSIONS_TABLE . '
			WHERE session_time >= ' . (time() - $_CORE_CONFIG['server']['session_length']) . '
				AND session_user_id <> ' . ANONYMOUS . '
			GROUP BY session_user_id';
		$result = $_CLASS['core_db']->sql_query($sql);

		$session_times = array();
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$session_times[$row['session_user_id']] = $row['session_time'];
		}
		$_CLASS['core_db']->sql_freeresult($result);
		
		// Do the SQL thang
		$sql = 'SELECT u.username, u.user_id, u.user_colour, u.user_allow_viewemail, u.user_posts, u.user_regdate, u.user_rank, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_msnm, u.user_jabber, u.user_avatar, u.user_avatar_type, u.user_lastvisit 
			'. $sql_fields .' FROM ' . USERS_TABLE . " u$sql_from
			WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
				$sql_where
			ORDER BY $order_by";
		$result = $_CLASS['core_db']->sql_query_limit($sql, $config['topics_per_page'], $start);

		$id_cache = array();
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			if ($mode == 'group' && $row['user_status'] == STATUS_PENDING)
			{
				 continue;
			}

			$row['session_time'] = (!empty($session_times[$row['user_id']])) ? $session_times[$row['user_id']] : '';
			$id_cache[$row['user_id']] = $row;
		}
		$_CLASS['core_db']->sql_freeresult($result);
		
		// Load custom profile fields
		if ($config['load_cpf_memberlist'])
		{
			include($site_file_root. 'includes/forums/functions_profile_fields.php');
			$cp = new custom_profile();
			// Grab all profile fields from users in id cache for later use - similar to the poster cache
			$profile_fields_cache = $cp->generate_profile_fields_template('grab', array_keys($id_cache));
		}
		
		$i = 0;
		foreach ($id_cache as $user_id => $row)
		{
			$cp_row = array();

			if ($config['load_cpf_memberlist'])
			{
				$cp_row = (isset($profile_fields_cache[$user_id])) ? $cp->generate_profile_fields_template('show', false, $profile_fields_cache[$user_id]) : array();
			}

			$option_row = ($mode == 'group' && $row['user_status'] == STATUS_LEADER) ? 'leader_row' : 'member_row';

			$$option_row = array_merge(show_profile($row), array(
				'ROW_NUMBER'		=> $i + ($start + 1),
				'S_CUSTOM_PROFILE'	=> (isset($cp_row['row']) && sizeof($cp_row['row'])) ? true : false,
				'U_VIEWPROFILE'		=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']))
			);

			if (isset($cp_row['row']) && sizeof($cp_row['row']))
			{
				$$option_row = array_merge($$option_row, $cp_row['row']);
			}
			
			$_CLASS['core_template']->assign_vars_array($option_row, $$option_row);
			
			/*if (isset($cp_row['blockrow']) && sizeof($cp_row['blockrow']))
			{
				foreach ($cp_row['blockrow'] as $field_data)
				{
					////////////
					// Need to be fixed
					////////////
					$_CLASS['core_template']->assign_vars_array('memberrow.custom_fields', $field_data);
				}
			}*/
			$i++;
			unset($id_cache[$user_id]);
	}
		

	// Generate page
	$_CLASS['core_template']->assign(array(
		'PAGINATION' 	=> generate_pagination($pagination_url2, $total_users, $config['topics_per_page'], $start),
		'PAGE_NUMBER' 	=> on_page($total_users, $config['topics_per_page'], $start),
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

		'U_FIND_MEMBER'		=> (!empty($config['load_search']) || $_CLASS['auth']->acl_get('a_')) ? generate_link('Members_List&amp;mode=searchuser') : '',
		'U_HIDE_FIND_MEMBER'=> ($mode == 'searchuser') ? generate_link($u_hide_find_member) : '',
		'U_SORT_USERNAME'	=> generate_link($pagination_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_FROM'		=> generate_link($pagination_url . '&amp;sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_JOINED'		=> generate_link($pagination_url . '&amp;sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_POSTS'		=> generate_link($pagination_url . '&amp;sk=d&amp;sd=' . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_EMAIL'		=> generate_link($pagination_url . '&amp;sk=e&amp;sd=' . (($sort_key == 'e' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_WEBSITE'	=> generate_link($pagination_url . '&amp;sk=f&amp;sd=' . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_ICQ'		=> generate_link($pagination_url . '&amp;sk=g&amp;sd=' . (($sort_key == 'g' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_AIM'		=> generate_link($pagination_url . '&amp;sk=h&amp;sd=' . (($sort_key == 'h' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_MSN'		=> generate_link($pagination_url . '&amp;sk=i&amp;sd=' . (($sort_key == 'i' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_YIM'		=> generate_link($pagination_url . '&amp;sk=j&amp;sd=' . (($sort_key == 'j' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_ACTIVE'		=> generate_link($pagination_url . '&amp;sk=k&amp;sd=' . (($sort_key == 'k' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_SORT_RANK'		=> generate_link($pagination_url . '&amp;sk=l&amp;sd=' . (($sort_key == 'l' && $sort_dir == 'a') ? 'd' : 'a')),
		'U_LIST_CHAR'		=> generate_link($pagination_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'l' && $sort_dir == 'a') ? 'd' : 'a')),

		'S_SEND_MESSAGE'	=> ($_CLASS['auth']->acl_get('u_sendpm')) ? true : false,
		'S_SHOW_GROUP'		=> ($mode == 'group') ? true : false,
		'S_MODE_SELECT'		=> $s_sort_key,
		'S_ORDER_SELECT'	=> $s_sort_dir,
		'S_CHAR_OPTIONS'	=> $s_char_options,
		'S_MODE_ACTION'		=> generate_link($pagination_url . (($form) ? "&amp;form=$form" : ''))
	));
}


// Output the page
$_CLASS['core_template']->assign('DISPLAY_STYLESHEET_LINK', $window);
page_header();

if (!$window)
{
	$_CLASS['core_display']->display_head($page_title);

	$_CLASS['core_template']->display('modules/Members_List/'.$template_html);

	$_CLASS['core_display']->display_footer();
}
else
{
	$_CLASS['core_template']->display('modules/Members_List/'.$template_html);
	script_close();
	
	exit;
}

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

function show_profile($data)
{
	global $config, $_CORE_CONFIG, $_CLASS, $ranks, $SID;

	$username = $data['username'];
	$user_id = $data['user_id'];

	$rank_title = $rank_img = '';
	get_user_rank($data['user_rank'], $data['user_posts'], $rank_title, $rank_img);
	
	if (!empty($data['user_allow_viewemail']) || $_CLASS['auth']->acl_get('a_email'))
	{
		$email = ($config['board_email_form'] && $_CORE_CONFIG['email']['email_enable']) ? generate_link('Members_List&amp;mode=email&amp;u='.$user_id) : (($config['board_hide_emails'] && !$_CLASS['auth']->acl_get('a_email')) ? '' : 'mailto:' . $data['user_email']);
	}
	else
	{
		$email = '';
	}
	
	$last_visit = (!empty($data['session_time'])) ? $data['session_time'] : $data['user_lastvisit'];

	// Dump it out to the template
	// TODO
	// Add permission check for IM clients
	return array(
		'USERNAME'		=> $username,
		'USER_COLOR'	=> (!empty($data['user_colour'])) ? $data['user_colour'] : '',
		'RANK_TITLE'	=> $rank_title,

		'JOINED'		=> $_CLASS['core_user']->format_date($data['user_regdate'], $_CLASS['core_user']->lang['DATE_FORMAT']),
		'VISITED'		=> (empty($last_visit)) ? ' - ' : $_CLASS['core_user']->format_date($last_visit, $_CLASS['core_user']->lang['DATE_FORMAT']),
		'POSTS'			=> ($data['user_posts']) ? $data['user_posts'] : 0,

		'ONLINE_IMG'	=> (intval($data['session_time']) >= time() - ($config['load_online_time'] * 60)) ? $_CLASS['core_user']->img('btn_online', $_CLASS['core_user']->lang['USER_ONLINE']) : $_CLASS['core_user']->img('btn_offline', $_CLASS['core_user']->lang['USER_ONLINE']),
		'RANK_IMG'		=> $rank_img,
		'ICQ_STATUS_IMG'=> (!empty($data['user_icq'])) ? '<img src="http://web.icq.com/whitepages/online?icq=' . $data['user_icq'] . '&amp;img=5" width="18" height="18" border="0" />' : '',
		
		'U_PROFILE'		=> generate_link('Members_List&amp;mode=viewprofile&amp;u='.$user_id),
		'U_SEARCH_USER'	=> ($_CLASS['auth']->acl_get('u_search')) ? generate_link('Forums&amp;file=search&amp;search_author=' . urlencode($username) . '&amp;show_results=posts') : '',
		'U_PM'			=> ($_CLASS['auth']->acl_get('u_sendpm')) ? generate_link('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$user_id) : '',
		'U_EMAIL'		=> $email,
		'U_WWW'			=> (!empty($data['user_website'])) ? $data['user_website'] : '',
		'U_ICQ'			=> ($data['user_icq']) ? generate_link('Members_List&amp;mode=contact&amp;action=icq&amp;u='.$user_id) : '',
		'U_AIM'			=> ($data['user_aim']) ? generate_link('Members_List&amp;mode=contact&amp;action=aim&amp;u='.$user_id) : '',
		'U_YIM'			=> ($data['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . $data['user_yim'] . '&.src=pg' : '',
		'U_MSN'			=> ($data['user_msnm']) ? generate_link('Members_List&amp;mode=contact&amp;action=msnm&amp;u='.$user_id) : '',
		'U_JABBER'		=> ($data['user_jabber']) ? generate_link('Members_List&amp;mode=contact&amp;action=jabber&amp;u='.$user_id) : '',

		'S_ONLINE'		=> (intval($data['session_time']) >= time() - 300) ? true : false
	);
}

?>