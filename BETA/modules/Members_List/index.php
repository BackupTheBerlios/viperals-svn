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

// TODO
// Add permission check for IM clients

if (!defined('VIPERAL')) {
    header('location: ../../');
    die();
}
require_once($site_file_root.'includes/forums/functions.'.$phpEx);
loadclass($site_file_root.'includes/forums/auth.'.$phpEx, 'auth');

$_CLASS['user']->add_lang();
$_CLASS['user']->add_img(0, 'Forums');

$_CLASS['auth']->acl($_CLASS['user']->data);

$_CLASS['template']->assign(array(
	'S_SEARCH_USER' => false,
	'S_SHOW_GROUP' => false)
);

// Grab data
$form = false;
$sql_where = false;
$mode		= request_var('mode', '');
$action		= request_var('action', '');
$user_id	= request_var('u', ANONYMOUS);
$group_id	= request_var('g', 0);
$topic_id	= request_var('t', 0);

$start	= request_var('start', 0);
$submit = (isset($_POST['submit'])) ? true : false;

$sort_key = request_var('sk', 'c');
$sort_dir = request_var('sd', 'a');


// Grab rank information for later
$ranks = array();
obtain_ranks($ranks);

// What do you want to do today? ... oops, I think that line is taken ...
switch ($mode)
{
	case 'leaders':
		// TODO
		// Display a listing of board admins, moderators?
		$user_ary = $_CLASS['auth']->acl_get_list(false, array('a_', 'm_'), false);

		$user_id_ary = array();
		foreach ($user_ary as $forum_id => $forum_ary)
		{
			foreach ($forum_ary as $auth_option => $id_ary)
			{
				$user_id_ary += $id_ary;
			}
		}

		$sql = 'SELECT user_id, username
			FROM ' . USERS_TABLE . '
			WHERE user_id IN (' . implode(', ', $user_id_ary) . ')';
		$result = $_CLASS['db']->sql_query($sql);

		$_CLASS['db']->sql_freeresult($result);

		foreach ($user_ary[0]['u_'] as $user_id)
		{
		}

		break;

	case 'contact':
		$_CLASS['template']->assign(array(
			'S_SEND_ICQ' => false,
			'S_SEND_AIM' => false,
			'S_SEND_JABBER' => false,
			'S_SENT_JABBER' => false,
			'S_SEND_MSNM' => false,
			'S_NO_SEND_JABBER' => false)
		);
		
		$page_title = $_CLASS['user']->lang['IM_USER'];
		$template_html = 'memberlist_im.html';

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

			case 'msn':
				$lang = 'MSNM';
				$sql_field = 'user_msnm';
				$s_select = 'S_SEND_MSNM';
				$s_action = '';
				break;

			case 'jabber':
				$lang = 'JABBER';
				$sql_field = 'user_jabber';
				$s_select = (@extension_loaded('xml')) ? 'S_SEND_JABBER' : 'S_NO_SEND_JABBER';
				$s_action = getlink("Members_List&amp;mode=contact&amp;action=$action&amp;u=$user_id");
				break;

			default:
				$lang = 'JABBER';
				$sql_field = 'user_jabber';
				$s_select = (@extension_loaded('xml')) ? 'S_SEND_JABBER' : 'S_NO_SEND_JABBER';
				$s_action = getlink("Members_List&amp;mode=contact&amp;action=$action&amp;u=$user_id");
				break;
		}

		// Grab relevant data
		$sql = "SELECT user_id, username, user_email, user_lang, $sql_field
			FROM " . USERS_TABLE . "
			WHERE user_id = $user_id";
		$result = $_CLASS['db']->sql_query($sql);

		if (!($row = $_CLASS['db']->sql_fetchrow($result)))
		{
			trigger_error($_CLASS['user']->lang['NO_USER_DATA']);
		}
		$_CLASS['db']->sql_freeresult($result);

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
					require_once($site_file_root.'includes/forums/functions_messenger.'.$phpEx);

					$subject = sprintf($_CLASS['user']->lang['IM_JABBER_SUBJECT'], $_CLASS['user']->data['username'], $config['server_name']);
					$message = $_POST['message'];

					$messenger = new messenger();

					$messenger->template('profile_send_email', $row['user_lang']);
					$messenger->subject($subject);

					$messenger->replyto($_CLASS['user']->data['user_email']);
					$messenger->im($row['user_jabber'], $row['username']);

					$messenger->assign_vars(array(
						'SITENAME'		=> $config['sitename'],
						'BOARD_EMAIL'	=> $config['board_contact'],
						'FROM_USERNAME' => $_CLASS['user']->data['username'],
						'TO_USERNAME'	=> $row['username'],
						'MESSAGE'		=> $message)
					);

					$messenger->send(NOTIFY_IM);
					$messenger->queue->save();

					$s_select = 'S_SENT_JABBER';
				}
				break;
		}

		$_CLASS['template']->assign(array(
			'L_SEND_IM'				=> $_CLASS['user']->lang['SEND_IM'],
			'L_IM_RECIPIENT'		=> $_CLASS['user']->lang['IM_RECIPIENT'],
			'L_IM_NAME'				=> $_CLASS['user']->lang['IM_NAME'],
			'L_IM_MESSAGE'			=> $_CLASS['user']->lang['IM_MESSAGE'],
			'L_IM_SEND'				=> $_CLASS['user']->lang['IM_SEND'],
			'L_IM_ADD_CONTACT'		=> $_CLASS['user']->lang['IM_ADD_CONTACT'],
			'L_IM_SEND_MESSAGE'		=> $_CLASS['user']->lang['IM_SEND_MESSAGE'],
			'L_IM_DOWNLOAD_APP'		=> $_CLASS['user']->lang['IM_DOWNLOAD_APP'],
			'L_IM_AIM_EXPRESS'		=> $_CLASS['user']->lang['IM_AIM_EXPRESS'],
			'L_IM_NO_JABBER'		=> $_CLASS['user']->lang['IM_NO_JABBER'],
			'L_IM_SENT_JABBER'		=> $_CLASS['user']->lang['IM_SENT_JABBER'],
			
			'IM_CONTACT'	=> $row[$sql_field],
			'USERNAME'		=> addslashes($row['username']),
			'EMAIL'			=> $row['user_email'],
			'CONTACT_NAME'	=> $row[$sql_field],
			'SITENAME'		=> addslashes($config['sitename']),

			'PRESENCE_IMG'		=> $presence_img,

			'L_SEND_IM_EXPLAIN'	=> $_CLASS['user']->lang['IM_' . $lang],
			'L_IM_SENT_JABBER'	=> sprintf($_CLASS['user']->lang['IM_SENT_JABBER'], $row['username']),

			$s_select			=> true,
			'S_IM_ACTION'		=> $s_action)
		);

		break;

	case 'viewprofile':
		// Display a profile
		if ($user_id == ANONYMOUS)
		{
			trigger_error($_CLASS['user']->lang['NO_USER']);
		}

		// We left join on the session table to see if the user is currently online
		$sql = 'SELECT username, user_id, group_id, user_colour, user_permissions, user_karma, user_sig, user_sig_bbcode_uid, user_sig_bbcode_bitfield, user_allow_viewemail, user_posts, user_regdate, user_rank, user_from, user_occ, user_interests, user_website, user_email, user_icq, user_aim, user_yim, user_msnm, user_jabber, user_avatar, user_avatar_width, user_avatar_height, user_avatar_type, user_lastvisit
			FROM ' . USERS_TABLE . "
			WHERE user_id = $user_id";
		$result = $_CLASS['db']->sql_query($sql);

		if (!($member = $_CLASS['db']->sql_fetchrow($result)))
		{
			$_CLASS['db']->sql_freeresult($result);
			trigger_error($_CLASS['user']->lang['NO_USER']);
		}
		
		$_CLASS['db']->sql_freeresult($result);

		$sql = 'SELECT g.group_id, g.group_name, g.group_type
			FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug
			WHERE ug.user_id = $user_id
				AND g.group_id = ug.group_id" . (($_CLASS['auth']->acl_get('a_groups'))? ' AND g.group_type <> ' . GROUP_HIDDEN : '') . '
			ORDER BY group_type, group_name';
		$result = $_CLASS['db']->sql_query($sql);

		$group_options = '';
		
		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$group_options .= '<option value="' . $row['group_id'] . '"'.(($member['group_id'] == $row['group_id'])? ' selected="selected"' : '').'>' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$_CLASS['db']->sql_freeresult($result);
		
		$page_title = sprintf($_CLASS['user']->lang['VIEWING_PROFILE'], $member['username']);
		$template_html = 'memberlist_view.html';
		
		$sql = 'SELECT MAX(session_time) AS session_time
			FROM ' . SESSIONS_TABLE . "
			WHERE session_user_id = $user_id";
		$result = $_CLASS['db']->sql_query($sql);

		$row = $_CLASS['db']->sql_fetchrow($result);
		$_CLASS['db']->sql_freeresult($result);

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
		$result = $_CLASS['db']->sql_query($sql);

		$num_real_posts = min($_CLASS['user']->data['user_posts'], $_CLASS['db']->sql_fetchfield('num_posts', 0, $result));
		$_CLASS['db']->sql_freeresult($result);

		// Change post_count_sql to an forum_id array the user is able to see
		$f_forum_ary = $_CLASS['auth']->acl_getf('f_read');

		$sql_forums = array();
		foreach ($f_forum_ary as $forum_id => $allow)
		{
			if ($allow['f_read'])
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
			$result = $_CLASS['db']->sql_query_limit($sql, 1);

			$active_f_row = $_CLASS['db']->sql_fetchrow($result);
			$_CLASS['db']->sql_freeresult($result);

			$sql = 'SELECT t.topic_id, t.topic_title, COUNT(p.post_id) AS num_posts
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . " f
				WHERE p.poster_id = $user_id
					AND t.topic_id = p.topic_id
					AND f.forum_id = t.forum_id
					$post_count_sql
				GROUP BY t.topic_id, t.topic_title
				ORDER BY num_posts DESC";
			$result = $_CLASS['db']->sql_query_limit($sql, 1);

			$active_t_row = $_CLASS['db']->sql_fetchrow($result);
			$_CLASS['db']->sql_freeresult($result);
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
			require_once($site_file_root.'includes/forums/bbcode.'.$phpEx);
			$bbcode = new bbcode();
			$bbcode->bbcode_second_pass($member['user_sig'], $member['user_sig_bbcode_uid'], $member['user_sig_bbcode_bitfield']);
		}

		if ($member['user_sig'])
		{
			$member['user_sig'] = smilie_text($member['user_sig']);
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

		$_CLASS['template']->assign(show_profile($member));
		
		// Custom Profile Fields
		$profile_fields = array();
		if ($config['load_cpf_viewprofile'])
		{
			require_once($site_file_root.'includes/forums/functions_profile_fields.' . $phpEx);
			$cp = new custom_profile();
			$profile_fields = $cp->generate_profile_fields_template('grab', $user_id);

			$profile_fields = (isset($profile_fields[$user_id])) ? $cp->generate_profile_fields_template('show', false, $profile_fields[$user_id]) : array();

			if (sizeof($profile_fields))
			{
				$_CLASS['template']->assign($profile_fields);
			}
		}
		
		$_CLASS['template']->assign(array(
			'POSTS_DAY'			=> sprintf($_CLASS['user']->lang['POST_DAY'], $posts_per_day),
			'POSTS_PCT'			=> sprintf($_CLASS['user']->lang['POST_PCT'], $percentage),
			'ACTIVE_FORUM'		=> $active_f_name,
			'ACTIVE_FORUM_POSTS'=> ($active_f_count == 1) ? sprintf($_CLASS['user']->lang['USER_POST'], 1) : sprintf($_CLASS['user']->lang['USER_POSTS'], $active_f_count),
			'ACTIVE_FORUM_PCT'	=> sprintf($_CLASS['user']->lang['POST_PCT'], $active_f_pct),
			'ACTIVE_TOPIC'		=> $active_t_name,
			'ACTIVE_TOPIC_POSTS'=> ($active_t_count == 1) ? sprintf($_CLASS['user']->lang['USER_POST'], 1) : sprintf($_CLASS['user']->lang['USER_POSTS'], $active_t_count),
			'ACTIVE_TOPIC_PCT'	=> sprintf($_CLASS['user']->lang['POST_PCT'], $active_t_pct),

			'OCCUPATION'	=> (!empty($member['user_occ'])) ? $member['user_occ'] : '',
			'INTERESTS'		=> (!empty($member['user_interests'])) ? $member['user_interests'] : '',
			'SIGNATURE'		=> (!empty($member['user_sig'])) ? str_replace("\n", '<br />', $member['user_sig']) : '',

			'AVATAR_IMG'	=> $poster_avatar,
			'PM_IMG'		=> $_CLASS['user']->img('btn_pm', $_CLASS['user']->lang['MESSAGE']),
			'EMAIL_IMG'		=> $_CLASS['user']->img('btn_email', $_CLASS['user']->lang['EMAIL']),
			'WWW_IMG'		=> $_CLASS['user']->img('btn_www', $_CLASS['user']->lang['WWW']),
			'ICQ_IMG'		=> $_CLASS['user']->img('btn_icq', $_CLASS['user']->lang['ICQ']),
			'AIM_IMG'		=> $_CLASS['user']->img('btn_aim', $_CLASS['user']->lang['AIM']),
			'MSN_IMG'		=> $_CLASS['user']->img('btn_msnm', $_CLASS['user']->lang['MSNM']),
			'YIM_IMG'		=> $_CLASS['user']->img('btn_yim', $_CLASS['user']->lang['YIM']),
			'JABBER_IMG'	=> $_CLASS['user']->img('btn_jabber', $_CLASS['user']->lang['JABBER']),
			'SEARCH_IMG'	=> $_CLASS['user']->img('btn_search', $_CLASS['user']->lang['SEARCH']),

			'S_PROFILE_ACTION'	=> getlink('Members_List&amp;mode=group'),
			'S_GROUP_OPTIONS'	=> $group_options,
			'S_CUSTOM_FIELDS'	=> (sizeof($profile_fields)) ? true : false,
			
			'U_ADD_FRIEND'		=> getlink('Control_Panel&amp;i=zebra&amp;add=' . urlencode($member['username'])),
			'U_ACTIVE_FORUM'	=> getlink('Forums&amp;file=viewforum&amp;f='.$active_f_id),
			'U_ACTIVE_TOPIC'	=> getlink('Forums&amp;file=viewtopic&amp;t='.$active_t_id),
			
			'L_VIEWING_PROFILE' 	=> sprintf($_CLASS['user']->lang['VIEWING_PROFILE'], $member['username']),
			'L_USER_PRESENCE' 		=> $_CLASS['user']->lang['USER_PRESENCE'],
			'L_USER_FORUM'	 		=> $_CLASS['user']->lang['USER_FORUM'],
			'L_ADD_FRIEND' 			=> $_CLASS['user']->lang['ADD_FRIEND'],
			'L_JOINED' 				=> $_CLASS['user']->lang['JOINED'],
			'L_VISITED' 			=> $_CLASS['user']->lang['VISITED'],
			'L_TOTAL_POSTS' 		=> $_CLASS['user']->lang['TOTAL_POSTS'],
			'L_SEARCH_USER_POSTS' 	=> $_CLASS['user']->lang['SEARCH_USER_POSTS'],
			'L_ACTIVE_IN_FORUM' 	=> $_CLASS['user']->lang['ACTIVE_IN_FORUM'],
			'L_ACTIVE_IN_TOPIC' 	=> $_CLASS['user']->lang['ACTIVE_IN_TOPIC'],
			'L_CONTACT_USER' 		=> $_CLASS['user']->lang['CONTACT_USER'],
			'L_ABOUT_USER' 			=> $_CLASS['user']->lang['ABOUT_USER'],
			'L_EMAIL_ADDRESS' 		=> $_CLASS['user']->lang['EMAIL_ADDRESS'],
			'L_PM'				 	=> $_CLASS['user']->lang['PM'],
			'L_MSNM' 				=> $_CLASS['user']->lang['MSNM'],
			'L_YIM' 				=> $_CLASS['user']->lang['YIM'],
			'L_AIM'	 				=> $_CLASS['user']->lang['AIM'],
			'L_ICQ' 				=> $_CLASS['user']->lang['ICQ'],
			'L_JABBER' 				=> $_CLASS['user']->lang['JABBER'],
			'L_USERGROUPS' 			=> $_CLASS['user']->lang['USERGROUPS'],
			'L_GO' 					=> $_CLASS['user']->lang['GO'],
			'L_LOCATION' 			=> $_CLASS['user']->lang['LOCATION'],
			'L_OCCUPATION'		 	=> $_CLASS['user']->lang['OCCUPATION'],
			'L_INTERESTS' 			=> $_CLASS['user']->lang['INTERESTS'],
			'L_WEBSITE' 			=> $_CLASS['user']->lang['WEBSITE'],
			'L_SIGNATURE' 			=> $_CLASS['user']->lang['SIGNATURE'],
			'L_EMAIL_ADDRESS' 		=> $_CLASS['user']->lang['EMAIL_ADDRESS'],
			'L_PM'				 	=> $_CLASS['user']->lang['PM'])
		);
		break;

	case 'email':
		// Send an email
		$page_title = $_CLASS['user']->lang['SEND_EMAIL'];
		$template_html = 'memberlist_email.html';
		
		$_CLASS['template']->assign(array(
			'MESSAGE' => false,
			'SUBJECT' => false)
		);
		
		if (empty($config['email_enable']))
		{
			trigger_error($_CLASS['user']->lang['EMAIL_DISABLED']);
		}

		if (($user_id == ANONYMOUS || empty($config['board_email_form'])) && !$topic_id)
		{
			trigger_error($_CLASS['user']->lang['NO_EMAIL']);
		}

		if (!$_CLASS['auth']->acl_get('u_sendemail'))
		{
			trigger_error($_CLASS['user']->lang['NO_EMAIL']);
		}

		// Are we trying to abuse the facility?
		if (time() - $_CLASS['user']->data['user_emailtime'] < $config['flood_interval'])
		{
			trigger_error($lang['FLOOD_EMAIL_LIMIT']);
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
			$result = $_CLASS['db']->sql_query($sql);

			if (!($row = $_CLASS['db']->sql_fetchrow($result)))
			{
				trigger_error($_CLASS['user']->lang['NO_USER']);
			}
			$_CLASS['db']->sql_freeresult($result);

			// Can we send email to this user?
			if (empty($row['user_allow_viewemail']) && !$_CLASS['auth']->acl_get('a_user'))
			{
				trigger_error($_CLASS['user']->lang['NO_EMAIL']);
			}
		}
		else
		{
			$sql = 'SELECT forum_id, topic_title
				FROM ' . TOPICS_TABLE . "
				WHERE topic_id = $topic_id";
			$result = $_CLASS['db']->sql_query($sql);

			if (!($row = $_CLASS['db']->sql_fetchrow($result)))
			{
				trigger_error($_CLASS['user']->lang['NO_TOPIC']);
			}
			$_CLASS['db']->sql_freeresult($result);

			if (!$_CLASS['auth']->acl_get('f_read', $row['forum_id']))
			{
				trigger_error($_CLASS['user']->lang['NO_FORUM_READ']);
			}

			if (!$_CLASS['auth']->acl_get('f_email', $row['forum_id']))
			{
				trigger_error($_CLASS['user']->lang['NO_EMAIL']);
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
					$error[] = $_CLASS['user']->lang['EMPTY_SUBJECT_EMAIL'];
				}

				if (!$message)
				{
					$error[] = $_CLASS['user']->lang['EMPTY_MESSAGE_EMAIL'];
				}
			}
			else
			{
				if (!$email || !preg_match('#^.*?@(.*?\.)?[a-z0-9\-]+\.[a-z]{2,4}$#i', $email))
				{
					$error[] = $_CLASS['user']->lang['EMPTY_ADDRESS_EMAIL'];
				}

				if (!$name)
				{
					$error[] = $_CLASS['user']->lang['EMPTY_NAME_EMAIL'];
				}
			}

			if (!sizeof($error))
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_emailtime = ' . time() . '
					WHERE user_id = ' . $_CLASS['user']->data['user_id'];
				$result = $_CLASS['db']->sql_query($sql);

				// Add class loader
				require_once($site_file_root.'includes/forums/functions_messenger.'.$phpEx);

				$email_tpl	= (!$topic_id) ? 'profile_send_email' : 'email_notify';
				$email_lang = (!$topic_id) ? $row['user_lang'] : $email_lang;
				$email		= (!$topic_id) ? $row['user_email'] : $email;

				$messenger = new messenger();

				$messenger->template($email_tpl, $email_lang);
				$messenger->subject($subject);

				$messenger->replyto($_CLASS['user']->data['user_email']);
				$messenger->to($email, $row['username']);

				if (!$topic_id)
				{
					$messenger->im($row['user_jabber'], $row['username']);
				}

				if ($cc)
				{
					$messenger->cc($_CLASS['user']->data['user_email'], $_CLASS['user']->data['username']);
				}

				$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger->headers('X-AntiAbuse: User_id - ' . $_CLASS['user']->data['user_id']);
				$messenger->headers('X-AntiAbuse: Username - ' . $_CLASS['user']->data['username']);
				$messenger->headers('X-AntiAbuse: User IP - ' . $_CLASS['user']->ip);

				$messenger->assign_vars(array(
					'SITENAME'		=> $MAIN_CFG['global']['sitename'],
					'BOARD_EMAIL'	=> $config['board_contact'],
					'FROM_USERNAME' => $_CLASS['user']->data['username'],
					'TO_USERNAME'	=> ($topic_id) ? $name : $row['username'],
					'MESSAGE'		=> $message,
					'TOPIC_NAME'	=> ($topic_id) ? strtr($row['topic_title'], array_flip(get_html_translation_table(HTML_ENTITIES))) : '',

					'U_TOPIC'	=> ($topic_id) ? getlink('Forums&amp;file=viewforum&amp;f=' . $row['forum_id'] . "&t=$topic_id", true, true, false) : '')
				);

				$messenger->send($row['user_notify_type']);
				$messenger->queue->save();

				$_CLASS['display']->meta_refresh(3, getlink());
				$message = (!$topic_id) ? sprintf($_CLASS['user']->lang['RETURN_INDEX'],  '<a href="' . getlink() . '">', '</a>') : sprintf($_CLASS['user']->lang['RETURN_TOPIC'],  '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=" . $row['topic_id']) . '">', '</a>');
				trigger_error($_CLASS['user']->lang['EMAIL_SENT'] . '<br /><br />' . $message);
			}
		}

		if ($topic_id)
		{
			$_CLASS['template']->assign(array(
				'EMAIL'			=> htmlspecialchars($email),
				'NAME'			=> htmlspecialchars($name),
				'TOPIC_TITLE'	=> $row['topic_title'],

				'U_TOPIC'	=> getlink('Forums&amp;file=viewtopic&amp;f=' . $row['forum_id'] . '&amp;t=topic_id'),

				'S_LANG_OPTIONS'=> ($topic_id) ? language_select($email_lang) : '')
			);
		}
		$_CLASS['template']->assign(array(
			'USERNAME'		=> (!$topic_id) ? addslashes($row['username']) : '',
			'ERROR_MESSAGE'	=> (sizeof($error)) ? implode('<br />', $error) : '',

			'L_EMAIL_BODY_EXPLAIN'	=> (!$topic_id) ? $_CLASS['user']->lang['EMAIL_BODY_EXPLAIN'] : $_CLASS['user']->lang['EMAIL_TOPIC_EXPLAIN'],

			'S_POST_ACTION' => (!$topic_id) ? getlink('Members_List&amp;mode=email&amp;u='.$user_id, false, false) : getlink("Members_List&amp;mode=email&amp;f=$forum_id&amp;t=$topic_id", false, false),
			'S_SEND_USER'	=> (!$topic_id) ? true : false,
			'L_EMPTY_MESSAGE_EMAIL' => $_CLASS['user']->lang['EMPTY_MESSAGE_EMAIL'],
			'L_EMPTY_SUBJECT_EMAIL' => $_CLASS['user']->lang['EMPTY_SUBJECT_EMAIL'],
			'L_SEND_EMAIL'	 		=> $_CLASS['user']->lang['SEND_EMAIL'],
			'L_RECIPIENT' 			=> $_CLASS['user']->lang['RECIPIENT'],
			'L_SUBJECT' 			=> $_CLASS['user']->lang['SUBJECT'],
			'L_EMAIL_ADDRESS' 		=> $_CLASS['user']->lang['EMAIL_ADDRESS'],
			'L_REAL_NAME' 			=> $_CLASS['user']->lang['REAL_NAME'],
			'L_DEST_LANG' 			=> $_CLASS['user']->lang['DEST_LANG'],
			'L_DEST_LANG_EXPLAIN' 	=> $_CLASS['user']->lang['DEST_LANG_EXPLAIN'],
			'L_MESSAGE_BODY' 		=> $_CLASS['user']->lang['MESSAGE_BODY'],
			'L_EMAIL_BODY_EXPLAIN' 	=> $_CLASS['user']->lang['EMAIL_BODY_EXPLAIN'],
			'L_OPTIONS' 			=> $_CLASS['user']->lang['OPTIONS'],
			'L_CC_EMAIL' 			=> $_CLASS['user']->lang['CC_EMAIL'])
		);
		break;

	case 'group':
	
		$_CLASS['user']->add_lang('groups', 'Forums');
		
	default:
		// The basic memberlist
		$page_title = $_CLASS['user']->lang['MEMBERLIST'];
		$template_html = 'memberlist_body.html';

		// Sorting
		$sort_key_text = array('a' => $_CLASS['user']->lang['SORT_USERNAME'], 'b' => $_CLASS['user']->lang['SORT_LOCATION'], 'c' => $_CLASS['user']->lang['SORT_JOINED'], 'd' => $_CLASS['user']->lang['SORT_POST_COUNT'], 'e' => $_CLASS['user']->lang['SORT_EMAIL'], 'f' => $_CLASS['user']->lang['WEBSITE'], 'g' => $_CLASS['user']->lang['ICQ'], 'h' => $_CLASS['user']->lang['AIM'], 'i' => $_CLASS['user']->lang['MSNM'], 'j' => $_CLASS['user']->lang['YIM'], 'k' => $_CLASS['user']->lang['SORT_LAST_ACTIVE'], 'l' => $_CLASS['user']->lang['SORT_RANK']);
		$sort_key_sql = array('a' => 'u.username', 'b' => 'u.user_from', 'c' => 'u.user_regdate', 'd' => 'u.user_posts', 'e' => 'u.user_email', 'f' => 'u.user_website', 'g' => 'u.user_icq', 'h' => 'u.user_aim', 'i' => 'u.user_msnm', 'j' => 'u.user_yim', 'k' => 'u.user_lastvisit', 'l' => 'u.user_rank DESC, u.user_posts');

		$sort_dir_text = array('a' => $_CLASS['user']->lang['ASCENDING'], 'd' => $_CLASS['user']->lang['DESCENDING']);

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
		$where_sql = '';
		if ($mode == 'searchuser' && ($config['load_search'] || $_CLASS['auth']->acl_get('a_')))
		{
			$form	= request_var('form', '');
			$field	= request_var('field', 'username');

			$username	= request_var('username', '');
			$email		= request_var('email', '');
			$icq		= request_var('icq', '');
			$aim		= request_var('aim', '');
			$yahoo		= request_var('yahoo', '');
			$msn		= request_var('msn', '');

			$joined_select	= request_var('joined_select', 'lt');
			$active_select	= request_var('active_select', 'lt');
			$count_select	= request_var('count_select', 'eq');
			$joined			= explode('-', request_var('joined', ''));
			$active			= explode('-', request_var('active', ''));
			$count			= request_var('count', 0);
			$ipdomain		= request_var('ip', '');

			$find_key_match = array('lt' => '<', 'gt' => '>', 'eq' => '=');

			$find_count = array('lt' => $_CLASS['user']->lang['LESS_THAN'], 'eq' => $_CLASS['user']->lang['EQUAL_TO'], 'gt' => $_CLASS['user']->lang['MORE_THAN']);
			$s_find_count = '';
			foreach ($find_count as $key => $value)
			{
				$selected = ($count_select == $key) ? ' selected="selected"' : '';
				$s_find_count .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}

			$find_time = array('lt' => $_CLASS['user']->lang['BEFORE'], 'gt' => $_CLASS['user']->lang['AFTER']);
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

			$sql_where .= ($username) ? " AND u.username LIKE '" . str_replace('*', '%', $_CLASS['db']->sql_escape($username)) ."'" : '';
			$sql_where .= ($email) ? " AND u.user_email LIKE '" . str_replace('*', '%', $_CLASS['db']->sql_escape($email)) ."' " : '';
			$sql_where .= ($icq) ? " AND u.user_icq LIKE '" . str_replace('*', '%', $_CLASS['db']->sql_escape($icq)) ."' " : '';
			$sql_where .= ($aim) ? " AND u.user_aim LIKE '" . str_replace('*', '%', $_CLASS['db']->sql_escape($aim)) ."' " : '';
			$sql_where .= ($yahoo) ? " AND u.user_yim LIKE '" . str_replace('*', '%', $_CLASS['db']->sql_escape($yahoo)) ."' " : '';
			$sql_where .= ($msn) ? " AND u.user_msnm LIKE '" . str_replace('*', '%', $_CLASS['db']->sql_escape($msn)) ."' " : '';
			$sql_where .= ($count) ? " AND u.user_posts " . $find_key_match[$count_select] . " $count " : '';
			$sql_where .= (sizeof($joined) > 1) ? " AND u.user_regdate " . $find_key_match[$joined_select] . ' ' . gmmktime(0, 0, 0, intval($joined[1]), intval($joined[2]), intval($joined[0])) : '';
			$sql_where .= (sizeof($active) > 1) ? " AND u.user_lastvisit " . $find_key_match[$active_select] . ' ' . gmmktime(0, 0, 0, $active[1], intval($active[2]), intval($active[0])) : '';

			if ($ipdomain)
			{
				$ips = (preg_match('#[a-z]#', $ipdomain)) ? implode(', ', preg_replace('#([0-9]{1,3}\.[0-9]{1,3}[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#', "'\\1'", gethostbynamel($ipdomain))) : "'" . str_replace('*', '%', $ipdomain) . "'";

				$sql = 'SELECT DISTINCT poster_id
					FROM ' . POSTS_TABLE . '
					WHERE poster_ip ' . ((preg_match('#%#', $ips)) ? 'LIKE' : 'IN') . " ($ips)";
				$result = $_CLASS['db']->sql_query($sql);

				if ($row = $_CLASS['db']->sql_fetchrow($result))
				{
					$ip_sql = array();
					do
					{
						$ip_sql[] = $row['poster_id'];
					}
					while ($row = $_CLASS['db']->sql_fetchrow($result));

					$sql_where .= ' AND u.user_id IN (' . implode(', ', $ip_sql) . ')';
				}
				else
				{
					// A minor fudge but it does the job :D
					$sql_where .= " AND u.user_id IN ('-1')";
				}
			}
		}

		// Are we looking at a usergroup? If so, fetch additional info
		// and further restrict the user info query
		$sql_from = $sql_where = '';
		if ($mode == 'group')
		{
			$sql = 'SELECT *
				FROM ' . GROUPS_TABLE . "
				WHERE group_id = $group_id";
			$result = $_CLASS['db']->sql_query($sql);

			if (!extract($_CLASS['db']->sql_fetchrow($result)))
			{
				trigger_error($_CLASS['user']->lang['NO_GROUP']);
			}
			$_CLASS['db']->sql_freeresult($result);

			switch ($group_type)
			{
				case GROUP_OPEN:
					$group_type = 'OPEN';
					break;
				case GROUP_CLOSED:
					$group_type = 'CLOSED';
					break;
				case GROUP_HIDDEN:
					$group_type = 'HIDDEN';
					break;
				case GROUP_SPECIAL:
					$group_type = 'SPECIAL';
					break;
				case GROUP_FREE:
					$group_type = 'FREE';
					break;
			}

			$avatar_img = '';
			if ($group_avatar)
			{
				switch ($group_avatar_type)
				{
					case AVATAR_UPLOAD:
						$avatar_img = $config['avatar_path'] . '/';
						break;
					case AVATAR_GALLERY:
						$avatar_img = $config['avatar_gallery_path'] . '/';
						break;
				}
				$avatar_img .= $group_avatar;

				$avatar_img = '<img src="' . $avatar_img . '" width="' . $group_avatar_width . '" height="' . $group_avatar_height . '" border="0" alt="" />';
			}

			$rank_title = $rank_img = '';
			if (!empty($group_rank))
			{
				$rank_title = (!empty($ranks['special'][$group_rank]['rank_title'])) ? $ranks['special'][$group_rank]['rank_title'] : '';
				$rank_img = (!empty($ranks['special'][$group_rank]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$group_rank]['rank_image'] . '" border="0" alt="' . $ranks['special'][$group_rank]['rank_title'] . '" title="' . $ranks['special'][$group_rank]['rank_title'] . '" /><br />' : '';
			}

			$_CLASS['template']->assign(array(
				'GROUP_DESC'	=> $group_description,
				'GROUP_NAME'	=> $group_name,
				'GROUP_COLOR'	=> $group_colour,
				'GROUP_TYPE'	=> $_CLASS['user']->lang['GROUP_IS_' . $group_type],
				'GROUP_RANK'	=> $rank_title,

				'AVATAR_IMG'	=> $avatar_img,
				'RANK_IMG'		=> $rank_img,

				'U_PM'			=> ($_CLASS['auth']->acl_get('u_sendpm') && $group_receive_pm && $config['allow_mass_pm']) ? getlink('Control_Panel&amp;i=pm&amp;mode=compose&amp;g='.$group_id) : '')
			);

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
				WHERE u.user_type <> " . USER_IGNORE . "
				$sql_where";
			$result = $_CLASS['db']->sql_query($sql);

			$total_users = ($row = $_CLASS['db']->sql_fetchrow($result)) ? $row['total_users'] : 0;
		}
		else
		{
			$total_users = $config['num_users'];
		}

		// Pagination string
		$pagination_url = $pagination_url2 = 'Members_List';

		// Build a relevant pagination_url
		$global_var = ($submit) ? '_POST' : '_GET';
		foreach ($$global_var as $key => $var)
		{
			if (in_array($key, array('submit', 'start', 'mode')) || !$var)
			{
				$pagination_url .= '&amp;' . $key . '=' . urlencode(htmlspecialchars($var));
				$pagination_url2 .= ($key != 'start') ? '&amp;' . $key . '=' . urlencode(htmlspecialchars($var)) : '';
			}
		}

		// Some search user specific data
		if ($mode == 'searchuser' && ($config['load_search'] || $_CLASS['auth']->acl_get('a_')))
		{
			$_CLASS['template']->assign(array(
				'USERNAME'	=> $username,
				'EMAIL'		=> $email,
				'ICQ'		=> $icq,
				'AIM'		=> $aim,
				'YAHOO'		=> $yahoo,
				'MSNM'		=> $msn,
				'JOINED'	=> implode('-', $joined),
				'ACTIVE'	=> implode('-', $active),
				'COUNT'		=> $count,
				'IP'		=> $ipdomain,

				'L_FIND_USERNAME' 			=> $_CLASS['user']->lang['FIND_USERNAME'],
				'L_FIND_USERNAME_EXPLAIN'	=> $_CLASS['user']->lang['FIND_USERNAME_EXPLAIN'],
				'L_ICQ'	 					=> $_CLASS['user']->lang['ICQ'],
				'L_AIM' 					=> $_CLASS['user']->lang['AIM'],
				'L_YIM' 					=> $_CLASS['user']->lang['YIM'],
				'L_MSNM' 					=> $_CLASS['user']->lang['MSNM'],
				'L_LAST_ACTIVE' 			=> $_CLASS['user']->lang['LAST_ACTIVE'],
				'L_POST_IP' 				=> $_CLASS['user']->lang['POST_IP'],
				'L_SORT_BY' 				=> $_CLASS['user']->lang['SORT_BY'],
				'L_SEARCH' 					=> $_CLASS['user']->lang['SEARCH'],
				'L_RESET' 					=> $_CLASS['user']->lang['RESET'],
				'L_OPTIONS' 				=> $_CLASS['user']->lang['OPTIONS'],
				
				'S_SEARCH_USER' 		=> true,
				'S_FORM_NAME' 			=> $form,
				'S_FIELD_NAME' 			=> $field,
				'S_COUNT_OPTIONS' 		=> $s_find_count,
				'S_SORT_OPTIONS' 		=> $s_sort_key,
				'S_USERNAME_OPTIONS'	=> isset($username_list) ? $username_list : '',
				'S_JOINED_TIME_OPTIONS' => $s_find_join_time,
				'S_ACTIVE_TIME_OPTIONS' => $s_find_active_time,
				'S_SEARCH_ACTION' 		=> getlink("Members_List&amp;mode=searchuser&amp;form=$form&amp;field=$field"))
			);
		}

		$sql = 'SELECT session_user_id, MAX(session_time) AS session_time
			FROM ' . SESSIONS_TABLE . '
			WHERE session_time >= ' . (time() - 300) . '
				AND session_user_id <> ' . ANONYMOUS . '
			GROUP BY session_user_id';
		$result = $_CLASS['db']->sql_query($sql);

		$session_times = array();
		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$session_times[$row['session_user_id']] = $row['session_time'];
		}
		$_CLASS['db']->sql_freeresult($result);
	
		// Do the SQL thang
		$sql = 'SELECT u.username, u.user_id, u.user_karma, u.user_colour, u.user_allow_viewemail, u.user_posts, u.user_regdate, u.user_rank, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_msnm, u.user_avatar, u.user_jabber, u.user_avatar_type, u.user_lastvisit
			FROM ' . USERS_TABLE . " u$sql_from
			WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
				$sql_where
			ORDER BY $order_by";
		$result = $_CLASS['db']->sql_query_limit($sql, $config['topics_per_page'], $start);

		if ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$i = 0;
			do
			{
				$row['session_time'] = (!empty($session_times[$row['user_id']])) ? $session_times[$row['user_id']] : '';

				$_CLASS['template']->assign_vars_array('memberrow', array_merge(show_profile($row), array(
					'ROW_NUMBER'	=> $i + ($start + 1),

					'U_VIEWPROFILE'		=> getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id'])))
				);

				$i++;
			}
			while ($row = $_CLASS['db']->sql_fetchrow($result));
		}

	// Generate page
	$_CLASS['template']->assign(array(
		'PAGINATION' 	=> generate_pagination($pagination_url2, $total_users, $config['topics_per_page'], $start),
		'PAGE_NUMBER' 	=> on_page($total_users, $config['topics_per_page'], $start),
		'TOTAL_USERS'	=> ($total_users == 1) ? $_CLASS['user']->lang['LIST_USER'] : sprintf($_CLASS['user']->lang['LIST_USERS'], $total_users),

		'PROFILE_IMG'	=> $_CLASS['user']->img('btn_profile', $_CLASS['user']->lang['PROFILE']),
		'PM_IMG'		=> $_CLASS['user']->img('btn_pm', $_CLASS['user']->lang['MESSAGE']),
		'EMAIL_IMG'		=> $_CLASS['user']->img('btn_email', $_CLASS['user']->lang['EMAIL']),
		'WWW_IMG'		=> $_CLASS['user']->img('btn_www', $_CLASS['user']->lang['WWW']),
		'ICQ_IMG'		=> $_CLASS['user']->img('btn_icq', $_CLASS['user']->lang['ICQ']),
		'AIM_IMG'		=> $_CLASS['user']->img('btn_aim', $_CLASS['user']->lang['AIM']),
		'MSN_IMG'		=> $_CLASS['user']->img('btn_msnm', $_CLASS['user']->lang['MSNM']),
		'YIM_IMG'		=> $_CLASS['user']->img('btn_yim', $_CLASS['user']->lang['YIM']),
		'JABBER_IMG'	=> $_CLASS['user']->img('btn_jabber', $_CLASS['user']->lang['JABBER']),
		'SEARCH_IMG'	=> $_CLASS['user']->img('btn_search', $_CLASS['user']->lang['SEARCH']),

		'L_SELECT_SORT_METHOD'	=> $_CLASS['user']->lang['SELECT_SORT_METHOD'],
		'L_SUBMIT'				=> $_CLASS['user']->lang['SUBMIT'],
		'L_FIND_USERNAME'		=> $_CLASS['user']->lang['FIND_USERNAME'],
		'L_USERNAME'			=> $_CLASS['user']->lang['USERNAME'],
		'L_JOINED'				=> $_CLASS['user']->lang['JOINED'],
		'L_POSTS'				=> $_CLASS['user']->lang['POSTS'],
		'L_RANK'				=> $_CLASS['user']->lang['RANK'],
		'L_SEND_MESSAGE'		=> ($_CLASS['auth']->acl_get('u_sendpm')) ? $_CLASS['user']->lang['SEND_MESSAGE'] : '',
		'L_EMAIL'				=> $_CLASS['user']->lang['EMAIL'],
		'L_WEBSITE'				=> $_CLASS['user']->lang['WEBSITE'],
		'L_MARK'				=> $_CLASS['user']->lang['MARK'],
		'L_NO_MEMBERS'			=> $_CLASS['user']->lang['NO_MEMBERS'],
		'L_SELECT_MARKED'		=> $_CLASS['user']->lang['SELECT_MARKED'],
		'L_ORDER'				=> $_CLASS['user']->lang['ORDER'],
		'L_MARK_ALL'			=> $_CLASS['user']->lang['MARK_ALL'],
		'L_UNMARK_ALL'			=> $_CLASS['user']->lang['UNMARK_ALL'],
		'L_GROUP_NAME'			=> $_CLASS['user']->lang['GROUP_NAME'],
		'L_GROUP_DESC'			=> $_CLASS['user']->lang['GROUP_DESC'],
		'L_GROUP_INFORMATION'	=> $_CLASS['user']->lang['GROUP_INFORMATION'],
		
		'U_FIND_MEMBER'		=> (!empty($config['load_search']) || $_CLASS['auth']->acl_get('a_')) ? getlink('Members_List&amp;mode=searchuser') : '',
		'U_SORT_USERNAME'	=> getlink($pagination_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_FROM'		=> getlink($pagination_url . '&amp;sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_JOINED'		=> getlink($pagination_url . '&amp;sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_POSTS'		=> getlink($pagination_url . '&amp;sk=d&amp;sd=' . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_EMAIL'		=> getlink($pagination_url . '&amp;sk=e&amp;sd=' . (($sort_key == 'e' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_WEBSITE'	=> getlink($pagination_url . '&amp;sk=f&amp;sd=' . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_ICQ'		=> getlink($pagination_url . '&amp;sk=g&amp;sd=' . (($sort_key == 'g' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_AIM'		=> getlink($pagination_url . '&amp;sk=h&amp;sd=' . (($sort_key == 'h' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_MSN'		=> getlink($pagination_url . '&amp;sk=i&amp;sd=' . (($sort_key == 'i' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_YIM'		=> getlink($pagination_url . '&amp;sk=j&amp;sd=' . (($sort_key == 'j' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_ACTIVE'		=> getlink($pagination_url . '&amp;sk=k&amp;sd=' . (($sort_key == 'k' && $sort_dir == 'a') ? 'd' : 'a'), false, false),
		'U_SORT_RANK'		=> getlink($pagination_url . '&amp;sk=l&amp;sd=' . (($sort_key == 'l' && $sort_dir == 'a') ? 'd' : 'a'), false, false),

		'S_SHOW_GROUP'		=> ($mode == 'group') ? true : false,
		'S_MODE_SELECT'		=> $s_sort_key,
		'S_ORDER_SELECT'	=> $s_sort_dir,
		'S_MODE_ACTION'		=> getlink('Members_List&amp;mode=searchuser' . (($form) ? "&amp;form=$form" : '')))
	);
}


// Output the page
if (!$form) {

	$_CLASS['display']->display_head($page_title);
	
	page_header();
	
	OpenTable();
	
	//make_jumpbox(getlink("Forums&amp;file=viewforum"));
	$_CLASS['template']->display('modules/Members_List/'.$template_html);

	CloseTable();
	$_CLASS['display']->display_footer();

} else {

	page_header();
	
	$_CLASS['template']->assign('DISPLAY_STYLESHEET_LINK', true);
	$_CLASS['template']->display('modules/Members_List/'.$template_html);
	script_close();
	
	exit;
}

// ---------
// FUNCTIONS
//
function show_profile($data)
{
	global $config, $_CLASS, $ranks, $SID;

	$username = $data['username'];
	$user_id = $data['user_id'];

	$rank_title = $rank_img = '';

	if (!empty($data['user_rank']))
	{
		$rank_title = (!empty($ranks['special'][$data['user_rank']]['rank_title'])) ? $ranks['special'][$data['user_rank']]['rank_title'] : '';
		$rank_img = (!empty($ranks['special'][$data['user_rank']]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$data['user_rank']]['rank_image'] . '" border="0" alt="' . $ranks['special'][$data['user_rank']]['rank_title'] . '" title="' . $ranks['special'][$data['user_rank']]['rank_title'] . '" /><br />' : '';
	}
	elseif(!empty($ranks['normal']))
	{
		foreach ($ranks['normal'] as $rank)
		{
			if ($data['user_posts'] >= $rank['rank_min'])
			{
				$rank_title = $rank['rank_title'];
				$rank_img = (!empty($rank['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $rank['rank_image'] . '" border="0" alt="' . $rank['rank_title'] . '" title="' . $rank['rank_title'] . '" /><br />' : '';
				break;
			}
		}
	}

	$email = (!empty($data['user_allow_viewemail']) || $_CLASS['auth']->acl_get('a_email')) ? ((!empty($config['board_email_form'])) ? getlink('Members_List&amp;mode=email&amp;u='.$user_id) : 'mailto:' . $data['user_email']) : '';

	$last_visit = (!empty($data['session_time'])) ? $data['session_time'] : $data['user_lastvisit'];

	// Dump it out to the template
	// TODO
	// Add permission check for IM clients
	return array(
		'USERNAME'		=> $username,
		'USER_COLOR'	=> (!empty($data['user_colour'])) ? $data['user_colour'] : '',
		'RANK_TITLE'	=> $rank_title,
		'KARMA'			=> ($config['enable_karma']) ? $_CLASS['user']->lang['KARMA'][$data['user_karma']] : '',  

		'JOINED'		=> $_CLASS['user']->format_date($data['user_regdate'], $_CLASS['user']->lang['DATE_FORMAT']),
		'VISITED'		=> (empty($last_visit)) ? ' - ' : $_CLASS['user']->format_date($last_visit, $_CLASS['user']->lang['DATE_FORMAT']),
		'POSTS'			=> ($data['user_posts']) ? $data['user_posts'] : 0,

		'KARMA_IMG'		=>	($config['enable_karma']) ? $_CLASS['user']->img('karma_center', $_CLASS['user']->lang['KARMA'][$data['user_karma']], false, (int) $data['user_karma']) : '',  
		'ONLINE_IMG'	=> (intval($data['session_time']) >= time() - ($config['load_online_time'] * 60)) ? $_CLASS['user']->img('btn_online', $_CLASS['user']->lang['USER_ONLINE']) : $_CLASS['user']->img('btn_offline', $_CLASS['user']->lang['USER_ONLINE']),
		'RANK_IMG'		=> $rank_img,
		'ICQ_STATUS_IMG'=> (!empty($data['user_icq'])) ? '<img src="http://web.icq.com/whitepages/online?icq=' . $data['user_icq'] . '&img=5" width="18" height="18" border="0" />' : '',

		'U_PROFILE'		=> getlink('Members_List&amp;mode=viewprofile&amp;u='.$user_id),
		'U_SEARCH_USER'	=> ($_CLASS['auth']->acl_get('u_search')) ? getlink('Forums&amp;file=search&amp;search_author=' . urlencode($username) . '&amp;show_results=posts') : '',
		'U_PM'			=> ($_CLASS['auth']->acl_get('u_sendpm')) ? getlink('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$user_id) : '',
		'U_EMAIL'		=> $email,
		'U_WWW'			=> (!empty($data['user_website'])) ? $data['user_website'] : '',
		'U_ICQ'			=> ($data['user_icq']) ? getlink('Members_List&amp;mode=contact&amp;action=icq&amp;u='.$user_id) : '',
		'U_AIM'			=> ($data['user_aim']) ? getlink('Members_List&amp;mode=contact&amp;action=aim&amp;u='.$user_id) : '',
		'U_YIM'			=> ($data['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . $data['user_yim'] . '&.src=pg' : '',
		'U_MSN'			=> ($data['user_msnm']) ? getlink('Members_List&amp;mode=contact&amp;action=msn&amp;u='.$user_id) : '',
		'U_JABBER'		=> ($data['user_jabber']) ? getlink('Members_List&amp;mode=contact&amp;action=jabber&amp;u='.$user_id) : '',

		'S_ONLINE'	=> (intval($data['session_time']) >= time() - 300) ? true : false
	);
}
//
// FUNCTIONS
// ---------

?>