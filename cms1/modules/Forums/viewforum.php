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
// $Id: viewforum.php,v 1.254 2004/10/13 19:30:02 acydburn Exp $
//
// FILENAME  : viewforum.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    header('location: ../../');
    die();
}

require_once($site_file_root.'includes/forums/functions.php');
loadclass($site_file_root.'includes/forums/auth.php', 'auth');
require($site_file_root.'includes/forums/functions_display.php');

$_CLASS['auth']->acl($_CLASS['core_user']->data);

// Start initial var setup
$forum_id	= request_var('f', 0);
$mark_read	= request_var('mark', '');
$start		= request_var('start', 0);

$sort_days	= request_var('st', ((!empty($_CLASS['core_user']->data['user_topic_show_days'])) ? $_CLASS['core_user']->data['user_topic_show_days'] : 0));
$sort_key	= request_var('sk', ((!empty($_CLASS['core_user']->data['user_topic_sortby_type'])) ? $_CLASS['core_user']->data['user_topic_sortby_type'] : 't'));
$sort_dir	= request_var('sd', ((!empty($_CLASS['core_user']->data['user_topic_sortby_dir'])) ? $_CLASS['core_user']->data['user_topic_sortby_dir'] : 'd'));
// Check if the user has actually sent a forum ID with his/her request
// If not give them a nice error page.
if (!$forum_id)
{
	trigger_error('NO_FORUM');
}

// Grab appropriate forum data
if (!$_CLASS['core_user']->is_user)
{
	$sql = 'SELECT *
		FROM ' . FORUMS_TABLE . '
		WHERE forum_id = ' . $forum_id;
}
else
{
		if ($config['load_db_lastread'])
		{
			$sql_lastread = 'LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
				AND ft.forum_id = f.forum_id)';
			$lastread_select = ', ft.mark_time ';
		}
		else
		{
			$sql_lastread = $lastread_select = '';
			$tracking_topics = (isset($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) ? unserialize(stripslashes($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) : array();
		}

		$sql_from = ($sql_lastread) ? '((' . FORUMS_TABLE . ' f LEFT JOIN ' . FORUMS_WATCH_TABLE . ' fw ON (fw.forum_id = f.forum_id AND fw.user_id = ' . $_CLASS['core_user']->data['user_id'] . ")) $sql_lastread)" : '(' . FORUMS_TABLE . ' f LEFT JOIN ' . FORUMS_WATCH_TABLE . ' fw ON (fw.forum_id = f.forum_id AND fw.user_id = ' . $_CLASS['core_user']->data['user_id'] . '))';

		$sql = "SELECT f.*, fw.notify_status $lastread_select 
			FROM $sql_from 
			WHERE f.forum_id = $forum_id";
}

$result = $_CLASS['core_db']->sql_query($sql);

if (!($forum_data = $_CLASS['core_db']->sql_fetchrow($result)))
{
	trigger_error('NO_FORUM');
}
$_CLASS['core_db']->sql_freeresult($result);

if (!$_CLASS['core_user']->is_user && $config['load_db_lastread'])
{
	$forum_data['mark_time'] = 0;
}

// Is this forum a link? ... User got here either because the 
// number of clicks is being tracked or they guessed the id
if ($forum_data['forum_link'])
{
	// Does it have click tracking enabled?
	if ($forum_data['forum_flags'] & 1)
	{
		$sql = 'UPDATE ' . FORUMS_TABLE . '
			SET forum_posts = forum_posts + 1 
			WHERE forum_id = ' . $forum_id;
		$_CLASS['core_db']->sql_query($sql);
	}

	redirect(str_replace('&amp;', '&', $forum_data['forum_link']));
}

// Configure style, language, etc.
$_CLASS['core_user']->add_img();
$_CLASS['core_user']->add_lang('viewforum');

// Permissions check
if (!$_CLASS['auth']->acl_get('f_read', $forum_id))
{
	if ($_CLASS['core_user']->data['user_id'] != ANONYMOUS)
	{
		trigger_error($_CLASS['core_user']->lang['SORRY_AUTH_READ']);
	}

	login_box(array('explain' => $_CLASS['core_user']->lang['LOGIN_NOTIFY_FORUM']));
}

// Forum is passworded ... check whether access has been granted to this
// user this session, if not show login box
if ($forum_data['forum_password'])
{
	login_forum_box($forum_data);
}

/* I don't see the point in this
// Redirect to login upon emailed notification links
if (isset($_GET['e']) && $_CLASS['core_user']->data['user_id'] == ANONYMOUS)
{
	login_box(array('explain' => $_CLASS['core_user']->lang['LOGIN_NOTIFY_FORUM']));
}
*/

// Build navigation links
generate_forum_nav($forum_data);

// Forum Rules
generate_forum_rules($forum_data);

// Do we have subforums?
$active_forum_ary = $moderators = array();

if ($forum_data['left_id'] != $forum_data['right_id'] - 1)
{
	$active_forum_ary = display_forums($forum_data);
}
else
{
	$_CLASS['core_template']->assign('S_HAS_SUBFORUM', false);
}
get_moderators($moderators, $forum_id);

// Output forum listing if it is postable
if ($forum_data['forum_type'] == FORUM_POST || ($forum_data['forum_flags'] & 16))
{
	// Handle marking posts
	if ($mark_read == 'topics')
	{
		markread('mark', $forum_id);
		
		$_CLASS['core_display']->meta_refresh(3, generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id));

		$message = $_CLASS['core_user']->lang['TOPICS_MARKED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="' . generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id) . '">', '</a> ');
		trigger_error($message);
	}

	// Is a forum specific topic count required?
	if ($forum_data['forum_topics_per_page'])
	{
		$config['topics_per_page'] = $forum_data['forum_topics_per_page'];
	}

	// Do the forum Prune thang - cron type job ...
	if ($forum_data['prune_next'] < time() && $forum_data['enable_prune'])
	{
		require_once($site_file_root.'includes/forums/functions_admin.php');

		if ($forum_data['prune_days'])
		{
			auto_prune($forum_id, 'posted', $forum_data['forum_flags'], $forum_data['prune_days'], $forum_data['prune_freq']);
		}
		if ($forum_data['prune_viewed'])
		{
			auto_prune($forum_id, 'viewed', $forum_data['forum_flags'], $forum_data['prune_viewed'], $forum_data['prune_freq']);
		}
	}

	// Forum rules amd subscription info
	$s_watching_forum = $s_watching_forum_img = array();
	$s_watching_forum['link'] = $s_watching_forum['title'] = '';
	if (($config['email_enable'] || $config['jab_enable']) && $config['allow_forum_notify'] && $_CLASS['auth']->acl_get('f_subscribe', $forum_id))
	{
		$notify_status = (isset($forum_data['notify_status'])) ? $forum_data['notify_status'] : NULL;
		watch_topic_forum('forum', $s_watching_forum, $s_watching_forum_img, $_CLASS['core_user']->data['user_id'], $forum_id, $notify_status);
	}

	gen_forum_auth_level('forum', $forum_id);

	// Topic ordering options
	$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_TOPICS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);

	$sort_by_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 'r' => $_CLASS['core_user']->lang['REPLIES'], 's' => $_CLASS['core_user']->lang['SUBJECT'], 'v' => $_CLASS['core_user']->lang['VIEWS']);
	$sort_by_sql = array('a' => 't.topic_first_poster_name', 't' => 't.topic_last_post_time', 'r' => 't.topic_replies', 's' => 't.topic_title', 'v' => 't.topic_views');

	$sort_key = (!in_array($sort_key, array('a', 't', 'r', 's', 'v'))) ? 't' : $sort_key;
	
	$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
	gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

	// Limit topics to certain time frame, obtain correct topic count
	if ($sort_days)
	{
		$min_post_time = time() - ($sort_days * 86400);

		$sql = 'SELECT COUNT(topic_id) AS num_topics
			FROM ' . TOPICS_TABLE . "
			WHERE forum_id = $forum_id
				AND topic_type <> " . POST_ANNOUNCE . "  
				AND topic_last_post_time >= $min_post_time
			" . (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND topic_approved = 1');
		$result = $_CLASS['core_db']->sql_query($sql);

		if (isset($_POST['sort']))
		{
			$start = 0;
		}
		$topics_count = ($row = $_CLASS['core_db']->sql_fetchrow($result)) ? $row['num_topics'] : 0;
		$sql_limit_time = "AND t.topic_last_post_time >= $min_post_time";
	}
	else
	{
		if ($_CLASS['auth']->acl_get('m_approve', $forum_id))
		{
			$topics_count = ($forum_data['forum_topics_real']) ? $forum_data['forum_topics_real'] : 1;
		}
		else
		{
			$topics_count = ($forum_data['forum_topics']) ? $forum_data['forum_topics'] : 1;
		}

		$sql_limit_time = '';
	}

	// Basic pagewide vars
	$post_alt = ($forum_data['forum_status'] == ITEM_LOCKED) ? $_CLASS['core_user']->lang['FORUM_LOCKED'] : $_CLASS['core_user']->lang['POST_NEW_TOPIC'];

	$_CLASS['core_template']->assign(array(
		'PAGINATION'	=> generate_pagination(generate_link("Forums&amp;file=viewforum&amp;f=$forum_id&amp;$u_sort_param"), $topics_count, $config['topics_per_page'], $start),
		'PAGE_NUMBER'	=> on_page($topics_count, $config['topics_per_page'], $start),
		'TOTAL_TOPICS'	=> ($forum_data['forum_flags'] & 16) ? false : (($topics_count == 1) ? $_CLASS['core_user']->lang['VIEW_FORUM_TOPIC'] : sprintf($_CLASS['core_user']->lang['VIEW_FORUM_TOPICS'], $topics_count)),
		'MODERATORS'	=> (!empty($moderators[$forum_id])) ? implode(', ', $moderators[$forum_id]) : '',

		'POST_IMG' 				=> ($forum_data['forum_status'] == ITEM_LOCKED) ? $_CLASS['core_user']->img('btn_locked', $post_alt) : $_CLASS['core_user']->img('btn_post', $post_alt),
		'FOLDER_IMG' 			=> $_CLASS['core_user']->img('folder', 'NO_NEW_POSTS'),
		'FOLDER_NEW_IMG' 		=> $_CLASS['core_user']->img('folder_new', 'NEW_POSTS'),
		'FOLDER_HOT_IMG' 		=> $_CLASS['core_user']->img('folder_hot', 'NO_NEW_POSTS_HOT'),
		'FOLDER_HOT_NEW_IMG'	=> $_CLASS['core_user']->img('folder_hot_new', 'NEW_POSTS_HOT'),
		'FOLDER_LOCKED_IMG' 	=> $_CLASS['core_user']->img('folder_locked', 'NO_NEW_POSTS_LOCKED'),
		'FOLDER_LOCKED_NEW_IMG' => $_CLASS['core_user']->img('folder_locked_new', 'NEW_POSTS_LOCKED'),
		'FOLDER_STICKY_IMG' 	=> $_CLASS['core_user']->img('folder_sticky', 'POST_STICKY'),
		'FOLDER_STICKY_NEW_IMG' => $_CLASS['core_user']->img('folder_sticky_new', 'POST_STICKY'),
		'FOLDER_ANNOUNCE_IMG' 	=> $_CLASS['core_user']->img('folder_announce', 'POST_ANNOUNCEMENT'),
		'FOLDER_ANNOUNCE_NEW_IMG'=> $_CLASS['core_user']->img('folder_announce_new', 'POST_ANNOUNCEMENT'),
		'FOLDER_MOVED_IMG'		=> $_CLASS['core_user']->img('folder_moved', 'TOPIC_MOVED'),

		'REPORTED_IMG'			=> $_CLASS['core_user']->img('icon_reported', 'TOPIC_REPORTED'),
		'UNAPPROVED_IMG'		=> $_CLASS['core_user']->img('icon_unapproved', 'TOPIC_UNAPPROVED'),
		'GOTO_PAGE_IMG'			=> $_CLASS['core_user']->img('icon_post', 'GOTO_PAGE'),
		'L_NO_TOPICS' 			=> ($forum_data['forum_status'] == ITEM_LOCKED) ? $_CLASS['core_user']->lang['POST_FORUM_LOCKED'] : $_CLASS['core_user']->lang['NO_TOPICS'],

		'S_IS_POSTABLE'			=> ($forum_data['forum_type'] == FORUM_POST) ? true : false,
		'S_DISPLAY_ACTIVE'		=> ($forum_data['forum_type'] == FORUM_CAT && $forum_data['forum_flags'] & 16) ? true : false, 
		'S_SELECT_SORT_DIR'		=> $s_sort_dir,
		'S_SELECT_SORT_KEY'		=> $s_sort_key,
		'S_SELECT_SORT_DAYS'	=> $s_limit_days,
		'S_TOPIC_ICONS'			=> ($forum_data['forum_type'] == FORUM_CAT && $forum_data['forum_flags'] & 16) ? max($active_forum_ary['enable_icons']) : (($forum_data['enable_icons']) ? true : false), 
		'S_WATCH_FORUM_LINK'	=> $s_watching_forum['link'],
		'S_WATCH_FORUM_TITLE'	=> $s_watching_forum['title'],
		'S_FORUM_ACTION' 		=> generate_link("Forums&amp;file=viewforum&amp;f=$forum_id&amp;start=$start"),
		'S_DISPLAY_SEARCHBOX'	=> ($_CLASS['auth']->acl_get('f_search', $forum_id)) ? true : false, 
		'S_SEARCHBOX_ACTION'	=> generate_link('Forums&amp;file=search&amp;search_forum[]='.$forum_id), 

 		'U_MCP' 			=> ($_CLASS['auth']->acl_gets('m_', $forum_id)) ? generate_link("Forums&amp;file=mcp&amp;f=$forum_id&amp;mode=forum_view") : '', 
		'U_POST_NEW_TOPIC'	=> generate_link('Forums&amp;file=posting&amp;mode=post&amp;f='.$forum_id), 
		'U_VIEW_FORUM'		=> generate_link("Forums&amp;file=viewforum&amp;f=$forum_id&amp;$u_sort_param&amp;start=$start"), 
		'U_MARK_TOPICS' 	=> generate_link("Forums&amp;file=viewforum&amp;f=$forum_id&amp;mark=topics"))
	);

	// Grab icons
	$icons = array();
	obtain_icons($icons);

	// Grab all topic data
	$rowset = $announcement_list = $topic_list = array();

	$sql_from = (($config['load_db_lastread'] || $config['load_db_track']) && $_CLASS['core_user']->is_user) ? '(' . TOPICS_TABLE . ' t LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id AND tt.user_id = ' . $_CLASS['core_user']->data['user_id'] . '))' : TOPICS_TABLE . ' t ';


	$sql_approved = ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1';
	$sql_select = (($config['load_db_lastread'] || $config['load_db_track']) && $_CLASS['core_user']->is_user) ? ', tt.mark_type, tt.mark_time' : '';

	if ($forum_data['forum_type'] == FORUM_POST)
	{
		// Obtain announcements ... removed sort ordering, sort by time in all cases
		$sql = "SELECT t.* $sql_select 
			FROM $sql_from 
			WHERE t.forum_id IN ($forum_id, 0)
				AND t.topic_type IN (" . POST_ANNOUNCE . ', ' . POST_GLOBAL . ')
			ORDER BY t.topic_time DESC';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$rowset[$row['topic_id']] = $row;
			$announcement_list[] = $row['topic_id'];
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}

	// If the user is trying to reach late pages, start searching from the end
	$store_reverse = false;
	$sql_limit = $config['topics_per_page'];
	if ($start > $topics_count / 2)
	{
		$store_reverse = true;

		if ($start + $config['topics_per_page'] > $topics_count)
		{
			$sql_limit = min($config['topics_per_page'], max(1, $topics_count - $start));
		}

		// Select the sort order
		$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC');
		$sql_start = max(0, $topics_count - $sql_limit - $start);
	}
	else
	{
		// Select the sort order
		$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
		$sql_start = $start;
	}

	// Obtain other topics
	$sql_where = ($forum_data['forum_type'] == FORUM_POST || !sizeof($active_forum_ary)) ? "= $forum_id" : 'IN (' . implode(', ', $active_forum_ary['forum_id']) . ')';
	$sql = "SELECT t.* $sql_select
		FROM $sql_from
		WHERE t.forum_id $sql_where
			AND t.topic_type NOT IN (" . POST_ANNOUNCE . ', ' . POST_GLOBAL . ") 
			$sql_approved 
			$sql_limit_time
		ORDER BY t.topic_type " . ((!$store_reverse) ? 'DESC' : 'ASC') . ', ' . $sql_sort_order;
	$result = $_CLASS['core_db']->sql_query_limit($sql, $sql_limit, $sql_start);

	while($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$rowset[$row['topic_id']] = $row;
		$topic_list[] = $row['topic_id'];
	}
	$_CLASS['core_db']->sql_freeresult($result);

	$topic_list = ($store_reverse) ? array_merge($announcement_list, array_reverse($topic_list)) : array_merge($announcement_list, $topic_list);

	// Okay, lets dump out the page ...
	if (sizeof($topic_list))
	{
		if ($config['load_db_lastread'])
		{
			$mark_time_forum = $forum_data['mark_time'];
		}
		else
		{
			$mark_time_forum = (isset($tracking_topics[$forum_id][0])) ? base_convert($tracking_topics[$forum_id][0], 36, 10) + $config['board_startdate'] : 0;
		}

		$mark_forum_read = true;

		$s_type_switch = 0;
		foreach ($topic_list as $topic_id)
		{
			$row =& $rowset[$topic_id];

			if ($config['load_db_lastread'])
			{
				$mark_time_topic = ($_CLASS['core_user']->is_user) ? $row['mark_time'] : 0;
			}
			else
			{
				$topic_id36 = base_convert($topic_id, 10, 36);
				$forum_id36 = ($row['topic_type'] == POST_GLOBAL) ? 0 : $forum_id;
				$mark_time_topic = (isset($tracking_topics[$forum_id36][$topic_id36])) ? base_convert($tracking_topics[$forum_id36][$topic_id36], 36, 10) + $config['board_startdate'] : 0;
			}
			
			// This will allow the style designer to output a different header
			// or even seperate the list of announcements from sticky and normal
			// topics
			$s_type_switch_test = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;

			// Replies
			$replies = ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];

			if ($row['topic_status'] == ITEM_MOVED)
			{
				$topic_id = $row['topic_moved_id'];
			}
 
			// Get folder img, topic status/type related informations
			$folder_img = $folder_alt = $topic_type = '';
			$unread_topic = topic_status($row, $replies, $mark_time_topic, $mark_time_forum, $folder_img, $folder_alt, $topic_type);
			
			$newest_post_img = ($unread_topic) ? '<a href="' . generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;view=unread#unread") . '">' . $_CLASS['core_user']->img('icon_post_newest', 'VIEW_NEWEST_POST') . '</a> ' : '';

			// Generate all the URIs ...
			$view_topic_url = 'Forums&amp;file=viewtopic&amp;f=' . (($row['forum_id']) ? $row['forum_id'] : $forum_id) . "&amp;t=$topic_id";

			// Send vars to template
			$_CLASS['core_template']->assign_vars_array('topicrow', array(
				'FORUM_ID' 			=> $forum_id,
				'TOPIC_ID' 			=> $topic_id,
				'TOPIC_AUTHOR' 		=> topic_topic_author($row),
				'FIRST_POST_TIME' 	=> $_CLASS['core_user']->format_date($row['topic_time']),
				'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
				'LAST_VIEW_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_view_time']),
				'LAST_POST_AUTHOR' 	=> ($row['topic_last_poster_name'] != '') ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],
				'PAGINATION'		=> topic_generate_pagination($replies, 'Forums&amp;file=viewtopic&amp;f=' . (($row['forum_id']) ? $row['forum_id'] : $forum_id) . "&amp;t=$topic_id"),
				'REPLIES' 			=> $replies,
				'VIEWS' 			=> $row['topic_views'],
				'TOPIC_TITLE' 		=> censor_text($row['topic_title']),
				'TOPIC_TYPE' 		=> $topic_type,

				'LAST_POST_IMG' 	=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'),
				'NEWEST_POST_IMG' 	=> $newest_post_img,
				'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
				//'TOPIC_FOLDER_IMG_SRC'	=> $_CLASS['core_user']->img($folder_img, $folder_alt, false, '', 'src'),
				'TOPIC_ICON_IMG'        => (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
				'TOPIC_ICON_IMG_WIDTH'  => (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
				'TOPIC_ICON_IMG_HEIGHT' => (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',
				'ATTACH_ICON_IMG'       => ($_CLASS['auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', $_CLASS['core_user']->lang['TOTAL_ATTACHMENTS']) : '',

				'S_TOPIC_TYPE'			=> $row['topic_type'], 
				'S_USER_POSTED'			=> (!empty($row['mark_type'])) ? true : false, 
				'S_UNREAD_TOPIC'		=> $unread_topic,

				'S_TOPIC_REPORTED'		=> (!empty($row['topic_reported']) && $_CLASS['auth']->acl_gets('m_', $forum_id)) ? true : false,
				'S_TOPIC_UNAPPROVED'	=> (!$row['topic_approved'] && $_CLASS['auth']->acl_gets('m_approve', $forum_id)) ? true : false,

				'U_LAST_POST'       => generate_link($view_topic_url . $SID . '&amp;p=' . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id'], false, false, false),	
				'U_LAST_POST_AUTHOR'=> ($_CLASS['core_user']->is_user && $row['topic_last_poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']) : '',
				'U_VIEW_TOPIC'		=> generate_link($view_topic_url),
				'U_MCP_REPORT'		=> generate_link("Forums&amp;file=mcp&amp;mode=reports&amp;t=$topic_id"),
				'U_MCP_QUEUE'       => generate_link('Forums&amp;file=mcp&amp;i=queue&amp;mode=approve_details&amp;t='.$topic_id),
				'S_TOPIC_TYPE_SWITCH'   => ($s_type_switch == $s_type_switch_test) ? -1 : $s_type_switch_test)
			);

			$s_type_switch = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;
		
			if ($mark_time_topic < $row['topic_last_post_time'] && $mark_time_forum < $row['topic_last_post_time'])
			{
				$mark_forum_read = false;
			}
		}
	}
	else
	{
// update smarty
//$_CLASS['core_template']->assign_vars_array('topicrow', array());
	}

	// This is rather a fudge but it's the best I can think of without requiring information
	// on all topics (as we do in 2.0.x). It looks for unread or new topics, if it doesn't find
	// any it updates the forum last read cookie. This requires that the user visit the forum
	// after reading a topic
	if ($forum_data['forum_type'] == FORUM_POST && count($topic_list) && $mark_forum_read)
	{
		markread('mark', $forum_id);
	}
}
else
{
	$_CLASS['core_template']->assign(array(
		'S_IS_POSTABLE'			=> false,
		'S_DISPLAY_ACTIVE'		=> false,
		'S_DISPLAY_SEARCHBOX'	=> false,
		'TOTAL_TOPICS'			=> false
	));

}
/// lets assign those language that are needed///
$_CLASS['core_template']->assign(array(
	'L_MODERATORS'			=> $_CLASS['core_user']->lang['MODERATORS'],
	'L_AUTHOR'				=> $_CLASS['core_user']->lang['AUTHOR'],
	'L_TOPICS'				=> $_CLASS['core_user']->lang['TOPICS'],
	'L_POSTS'				=> $_CLASS['core_user']->lang['POSTS'],
	'L_LAST_POST'			=> $_CLASS['core_user']->lang['LAST_POST'],
	'L_FORUM'				=> $_CLASS['core_user']->lang['FORUM'],
	'L_MARK_FORUMS_READ'	=>	$_CLASS['core_user']->lang['MARK_FORUMS_READ'],
	'L_VIEWS'				=> $_CLASS['core_user']->lang['VIEWS'],
	'L_REPLIES'				=> $_CLASS['core_user']->lang['REPLIES'],
	'L_WHO_IS_ONLINE'		=> $_CLASS['core_user']->lang['WHO_IS_ONLINE'],
	'L_STATISTICS'			=> $_CLASS['core_user']->lang['STATISTICS'],
	'L_USERNAME'			=> $_CLASS['core_user']->lang['USERNAME'],
	'L_GO'					=> $_CLASS['core_user']->lang['GO'],
	'L_ANNOUNCEMENTS'		=> $_CLASS['core_user']->lang['ANNOUNCEMENTS'],
	'L_SEARCH_FOR'			=> $_CLASS['core_user']->lang['SEARCH_FOR'],
	'L_NEW_POSTS'			=> $_CLASS['core_user']->lang['NEW_POSTS'],
	'L_NO_NEW_POSTS'		=> $_CLASS['core_user']->lang['NO_NEW_POSTS'],
	'L_ICON_ANNOUNCEMENT'	=> $_CLASS['core_user']->lang['ICON_ANNOUNCEMENT'],
	'L_NEW_POSTS_HOT'		=> $_CLASS['core_user']->lang['NEW_POSTS_HOT'],
	'L_NO_NEW_POSTS_HOT'	=> $_CLASS['core_user']->lang['NO_NEW_POSTS_HOT'],
	'L_ICON_STICKY'			=> $_CLASS['core_user']->lang['ICON_STICKY'],
	'L_NEW_POSTS_LOCKED'	=> $_CLASS['core_user']->lang['NEW_POSTS_LOCKED'],
	'L_NO_NEW_POSTS_LOCKED'	=> $_CLASS['core_user']->lang['NO_NEW_POSTS_LOCKED'],
	'L_MOVED_TOPIC'			=> $_CLASS['core_user']->lang['MOVED_TOPIC'],
	'L_DISPLAY_TOPICS'		=> $_CLASS['core_user']->lang['DISPLAY_TOPICS'],
	'L_JUMP_TO'				=> $_CLASS['core_user']->lang['JUMP_TO'],
	'L_MCP'					=> $_CLASS['core_user']->lang['MCP'],
	'L_MARK_TOPICS_READ'	=> $_CLASS['core_user']->lang['MARK_TOPICS_READ'],
	'L_FORUM_RULES'			=> $_CLASS['core_user']->lang['FORUM_RULES'],
	'L_SORT_BY'				=> $_CLASS['core_user']->lang['SORT_BY'])
	
);

$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['VIEW_FORUM'] . ' &gt; ' . $forum_data['forum_name']);

page_header();

make_jumpbox(generate_link('Forums&amp;file=viewforum', $forum_id));

$_CLASS['core_template']->display('modules/Forums/viewforum_body.html');

$_CLASS['core_display']->display_footer();

?>