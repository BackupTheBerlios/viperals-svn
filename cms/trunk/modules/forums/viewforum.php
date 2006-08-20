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

// -------------------------------------------------------------
//
// $Id: viewforum.php,v 1.254 2004/10/13 19:30:02 acydburn Exp $
//
// FILENAME  : viewforum.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// $limit_days and read tracking
if (!defined('VIPERAL'))
{
    die;
}

require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';

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

// Configure style, language, etc.
$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_lang('viewforum');

// Grab appropriate forum data
if (!$_CLASS['core_user']->is_user)
{
	$sql = 'SELECT *
		FROM ' . FORUMS_FORUMS_TABLE . '
		WHERE forum_id = ' . $forum_id .'
		AND forum_status <> '.ITEM_DELETING;
		
	$tracking_topics = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));
	
	if (!is_array($tracking_topics))
	{
		$tracking_topics = array();
	}
}
else
{
	if ($config['load_db_lastread'])
	{
		$sql_lastread = 'LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
			AND ft.forum_id = f.forum_id AND ft.topic_id = 0)';
		$lastread_select = ', ft.mark_time ';
	}
	else
	{
		$sql_lastread = $lastread_select = '';
		$tracking_topics = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));

		if (!is_array($tracking_topics))
		{
			$tracking_topics = array();
		}
	}

	$sql = "SELECT f.*, fw.notify_status $lastread_select 
		FROM " . FORUMS_FORUMS_TABLE . ' f LEFT JOIN ' . FORUMS_WATCH_TABLE . ' fw ON (fw.forum_id = f.forum_id AND fw.user_id = ' . $_CLASS['core_user']->data['user_id'] . ") $sql_lastread
		WHERE f.forum_id = $forum_id";
		
	unset($sql_lastread, $lastread_select);
}

$result = $_CLASS['core_db']->query($sql);
$forum_data = $_CLASS['core_db']->fetch_row_assoc($result);
$_CLASS['core_db']->free_result($result);

if (!$forum_data || $forum_data['forum_status'] == ITEM_DELETING)
{
	trigger_error('NO_FORUM');
}

// Check if links are checked for permission
if (!$_CLASS['forums_auth']->acl_get('f_read', $forum_id))
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

// Are we a forum link, then redirect
if ($forum_data['forum_link'])
{
	// Does it have click tracking enabled?
	if ($forum_data['forum_flags'] & 1)
	{
		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET forum_posts = forum_posts + 1 
			WHERE forum_id = ' . $forum_id;
		$_CLASS['core_db']->query($sql);
	}

	redirect(str_replace('&amp;', '&', $forum_data['forum_link']));
}

// Add Images
$_CLASS['core_user']->add_img();

// Build navigation links
generate_forum_nav($forum_data);

// Forum Rules
generate_forum_rules($forum_data);

// Do we have subforums?
$active_forum_ary = $moderators = array();

if ($forum_data['left_id'] != $forum_data['right_id'] - 1)
{
	list($active_forum_ary, $moderators) = display_forums($forum_data, $config['load_moderators'], $config['load_moderators']);
}
else
{
	$_CLASS['core_template']->assign('S_HAS_SUBFORUM', false);
	$moderators = get_moderators($forum_id);
}

// Not postable forum or showing active topics?
if (!($forum_data['forum_type'] == FORUM_POST || (($forum_data['forum_flags'] & 16) && $forum_data['forum_type'] == FORUM_CAT)))
{
	$_CLASS['core_template']->assign_array(array(
		'S_IS_POSTABLE'			=> false,
		'S_DISPLAY_ACTIVE'		=> false,
		'S_DISPLAY_SEARCHBOX'	=> false,
		'TOTAL_TOPICS'			=> false
	));

	page_header();
	
	make_jumpbox(generate_link('forums&amp;file=viewforum'), $forum_id);
	
	$_CLASS['core_template']->display('modules/forums/viewforum_body.html');
}

// Handle marking posts
if ($mark_read == 'topics')
{
	markread('forum', $forum_id);
	
	$_CLASS['core_display']->meta_refresh(3, generate_link('forums&amp;file=viewforum&amp;f='.$forum_id));

	$message = $_CLASS['core_user']->lang['TOPICS_MARKED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="' . generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id) . '">', '</a> ');
	trigger_error($message);
}

// Is a forum specific topic count required?
if ($forum_data['forum_topics_per_page'])
{
	$config['topics_per_page'] = $forum_data['forum_topics_per_page'];
}

/*
Need test this first
// Do the forum Prune thang - cron type job ...

if ($forum_data['enable_prune'] && $forum_data['prune_next'] < $_CLASS['core_user']->time)
{
	require_once(SITE_FILE_ROOT.'includes/forums/functions_admin.php');

	if ($forum_data['prune_days'])
	{
		auto_prune($forum_id, 'posted', $forum_data['forum_flags'], $forum_data['prune_days'], $forum_data['prune_freq']);
	}
	if ($forum_data['prune_viewed'])
	{
		auto_prune($forum_id, 'viewed', $forum_data['forum_flags'], $forum_data['prune_viewed'], $forum_data['prune_freq']);
	}
}

*/

if ($_CLASS['forums_auth']->acl_get('f_subscribe', $forum_id))
{
	$notify_status = isset($forum_data['notify_status']) ? $forum_data['notify_status'] : null;
	$s_watching_forum = watch_topic_forum('forum', $_CLASS['core_user']->data['user_id'], $forum_id, 0, $notify_status);
}
else
{
	$s_watching_forum['link'] = $s_watching_forum['title'] = '';
}

gen_forum_auth_level('forum', $forum_id, $forum_data['forum_status']);

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
	$min_post_time = $_CLASS['core_user']->time - ($sort_days * 86400);

	$sql = 'SELECT COUNT(topic_id) AS num_topics
		FROM ' . FORUMS_TOPICS_TABLE . "
		WHERE forum_id = $forum_id
			AND topic_type NOT IN (" . POST_ANNOUNCE . ', ' . POST_GLOBAL . ")
			AND topic_last_post_time >= $min_post_time
		" . (($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND topic_approved = 1');
	$result = $_CLASS['core_db']->query($sql);
	$topics_count = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? (int) $row['num_topics'] : 0;
	$_CLASS['core_db']->free_result($result);

	if (isset($_POST['sort']))
	{
		$start = 0;
	}

	$sql_limit_time = "AND t.topic_last_post_time >= $min_post_time";
}
else
{
	if ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id))
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
$pagination = generate_pagination("forums&amp;file=viewforum&amp;f=$forum_id&amp;$u_sort_param", $topics_count, $config['topics_per_page'], $start);

$s_display_active = ($forum_data['forum_type'] == FORUM_CAT && ($forum_data['forum_flags'] & 16)) ? true : false;

$_CLASS['core_template']->assign_array(array(
	'PAGINATION'		=> $pagination['formated'],
	'PAGINATION_ARRAY'	=> $pagination['array'],
	'PAGE_NUMBER'		=> on_page($topics_count, $config['topics_per_page'], $start),
	'TOTAL_TOPICS'		=> ($s_display_active) ? false : (($topics_count == 1) ? $_CLASS['core_user']->lang['VIEW_FORUM_TOPIC'] : sprintf($_CLASS['core_user']->lang['VIEW_FORUM_TOPICS'], $topics_count)),
	'MODERATORS'		=> empty($moderators[$forum_id]) ? '' : implode(', ', $moderators[$forum_id]),

	'POST_IMG' 			=> ($forum_data['forum_status'] == ITEM_LOCKED) ? $_CLASS['core_user']->img('btn_locked', $post_alt) : $_CLASS['core_user']->img('btn_post', $post_alt),

	'MOD_TOPIC_LOCK'	=>	$_CLASS['forums_auth']->acl_get('m_lock', $forum_id),
	'MOD_TOPIC_TITLE'	=>	$_CLASS['forums_auth']->acl_get('m_', $forum_id),

	'FORUM_IMG'			=>	$_CLASS['core_user']->img('forum', 'NO_NEW_POSTS'),
	'FORUM_NEW_IMG'		=>	$_CLASS['core_user']->img('forum_new', 'NEW_POSTS'),
	'FORUM_LOCKED_IMG'	=>	$_CLASS['core_user']->img('forum_locked', 'NO_NEW_POSTS_LOCKED'),

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
	'S_DISPLAY_ACTIVE'		=> $s_display_active, 
	'S_SELECT_SORT_DIR'		=> $s_sort_dir,
	'S_SELECT_SORT_KEY'		=> $s_sort_key,
	'S_SELECT_SORT_DAYS'	=> $s_limit_days,
	'S_TOPIC_ICONS'			=> ($s_display_active) ? max($active_forum_ary['enable_icons']) : (($forum_data['enable_icons']) ? true : false), 
	'S_WATCH_FORUM_LINK'	=> $s_watching_forum['link'],
	'S_WATCH_FORUM_TITLE'	=> $s_watching_forum['title'],
	'S_FORUM_ACTION' 		=> generate_link("forums&amp;file=viewforum&amp;f=$forum_id&amp;start=$start"),
	'S_DISPLAY_SEARCHBOX'	=> ($_CLASS['forums_auth']->acl_get('f_search', $forum_id)) ? true : false, 
	'S_SEARCHBOX_ACTION'	=> generate_link('forums&amp;file=search&amp;search_forum[]='.$forum_id), 

	'U_MCP' 			=> ($_CLASS['forums_auth']->acl_get('m_', $forum_id)) ? generate_link("forums&amp;file=mcp&amp;f=$forum_id&amp;mode=forum_view") : '', 
	'U_POST_NEW_TOPIC'	=> generate_link('forums&amp;file=posting&amp;mode=post&amp;f='.$forum_id), 
	'U_VIEW_FORUM'		=> generate_link("forums&amp;file=viewforum&amp;f=$forum_id&amp;$u_sort_param&amp;start=$start"), 
	'U_MARK_TOPICS' 	=> generate_link("forums&amp;file=viewforum&amp;f=$forum_id&amp;mark=topics"))
);

// Grab icons
$icons = obtain_icons();

// Grab all topic data
$rowset = $announcement_list = $topic_list = $global_announce_list = array();

$sql_from = FORUMS_TOPICS_TABLE.' t ';
$sql_select = '';

if ($_CLASS['core_user']->is_user && $config['load_db_lastread'])
{
	$sql_from .= ' LEFT JOIN ' . FORUMS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id AND tt.user_id = ' . $_CLASS['core_user']->data['user_id'] . ')';
	$sql_select .= ', tt.mark_time';
}

$sql_approved = $_CLASS['forums_auth']->acl_get('m_approve', $forum_id) ? '' : 'AND t.topic_approved = 1';

if ($forum_data['forum_type'] == FORUM_POST)
{
	// Obtain announcements ... removed sort ordering, sort by time in all cases
	$sql = "SELECT DISTINCT t.* $sql_select 
		FROM $sql_from 
		WHERE t.forum_id IN ($forum_id, 0)
			AND t.topic_type IN (" . POST_ANNOUNCE . ', ' . POST_GLOBAL . ')
		ORDER BY t.topic_time DESC';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$rowset[$row['topic_id']] = $row;
		$announcement_list[] = $row['topic_id'];

		if ($row['topic_type'] == POST_GLOBAL)
		{
			$global_announce_list[$row['topic_id']] = true;
		}
	}
	$_CLASS['core_db']->free_result($result);
}

// If the user is trying to reach late pages, start searching from the end
$store_reverse = false;
$sql_limit = $config['topics_per_page'];

// what's this store_reverse all about ?
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
$sql_where = ($forum_data['forum_type'] == FORUM_POST || empty($active_forum_ary)) ? "= $forum_id" : 'IN (' . implode(', ', $active_forum_ary['forum_id']) . ')';
$sql = "SELECT t.* $sql_select
	FROM $sql_from
	WHERE t.forum_id $sql_where
		AND t.topic_type NOT IN (" . POST_ANNOUNCE . ', ' . POST_GLOBAL . ") 
		$sql_approved 
		$sql_limit_time
	ORDER BY t.topic_type " . ((!$store_reverse) ? 'DESC' : 'ASC') . ', ' . $sql_sort_order;
$result = $_CLASS['core_db']->query_limit($sql, $sql_limit, $sql_start);

$shadow_topic_list = array();
while($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$rowset[$row['topic_id']] = $row;
	$topic_list[] = $row['topic_id'];
	
	if ($row['topic_status'] == ITEM_MOVED)
	{
		$shadow_topic_list[$row['topic_moved_id']] = $row['topic_id'];
	}
}
$_CLASS['core_db']->free_result($result);

// If we have some shadow topics, update the rowset to reflect their topic informations
if (!empty($shadow_topic_list))
{
	$sql = 'SELECT *
		FROM ' . FORUMS_TOPICS_TABLE . '
		WHERE topic_id IN (' . implode(', ', array_keys($shadow_topic_list)) . ')';
	$result = $_CLASS['core_db']->query($sql);

	while($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$orig_topic_id = $shadow_topic_list[$row['topic_id']];

		// We want to retain some values
		$row = array_merge($row, array(
			'topic_moved_id'	=> $rowset[$orig_topic_id]['topic_moved_id'],
			'topic_status'		=> $rowset[$orig_topic_id]['topic_status'])
		);

		$rowset[$orig_topic_id] = $row;
	}
	$_CLASS['core_db']->free_result($result);
}
unset($shadow_topic_list);

$topic_list = ($store_reverse) ? array_merge($announcement_list, array_reverse($topic_list)) : array_merge($announcement_list, $topic_list);
unset($announcement_list);

// we only update the mark if this is made into an (int) time
// We don't check when $start is used
$mark_forum_read = ($sql_start || $sql_limit != $config['topics_per_page'] || $sql_limit_time) ? false : true;

// Okay, lets dump out the page ...
if (!empty($topic_list))
{
	if ($_CLASS['core_user']->is_user && $config['load_db_lastread'])
	{
		$mark_time_forum = $forum_data['mark_time'];
	}
	else
	{
		$forum_id36 = ($row['topic_type'] == POST_GLOBAL) ? 0 : base_convert($forum_id, 10, 36);
		$mark_time_forum = isset($tracking_topics[$forum_id36][0]) ? (int) base_convert($tracking_topics[$forum_id36][0], 36, 10) : 0;
	}

	$s_type_switch = 0;

	foreach ($topic_list as $topic_id)
	{
		$row =& $rowset[$topic_id];

		if ($config['load_db_lastread'] && $_CLASS['core_user']->is_user)
		{
			$mark_time_topic = $row['mark_time'];
		}
		else
		{
			$topic_id36 = base_convert($topic_id, 10, 36);
			$mark_time_topic = isset($tracking_topics[$forum_id36][$topic_id36]) ? (int) base_convert($tracking_topics[$forum_id36][$topic_id36], 36, 10) : 0;
		}

		/*
			Is the topic mark time that greater than the forums mark time ? 
				If so, check to see if the topic has any new post/or another topic had a new post.
					Set $mark_forum_read to the highest topic_last_post_time if there's no new posts
		*/
		if (!$mark_time_forum || ($mark_time_forum < $mark_time_topic))
		{
			if ($mark_forum_read)
			{
				$mark_forum_read = ($row['topic_last_post_time'] > $mark_time_topic) ? false : max((int)$mark_forum_read, $mark_time_topic);
			//echo $row['topic_last_post_time']. ' - '.$mark_time_topic.' :';
			}
			$mark_time = $mark_time_topic;
		}
		else
		{
			$mark_time = $mark_time_forum;
		}

		// This will allow the style designer to output a different header
		// or even seperate the list of announcements from sticky and normal
		// topics
		$s_type_switch_test = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;

		// Replies
		$replies = ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? (int) $row['topic_replies_real'] : (int) $row['topic_replies'];

		if ($row['topic_status'] == ITEM_MOVED)
		{
// currently no marking supported
			$topic_id = $row['topic_moved_id'];
			$mark_time = $_CLASS['core_user']->time;
		}

		// Get folder img, topic status/type related informations
		$folder_img = $folder_alt = $topic_type = '';
	
		$unread_topic = ($mark_time < $row['topic_last_post_time']);

		topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $topic_type);

		$topic_unapproved = (!$row['topic_approved'] && $_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? true : false;
		$posts_unapproved = ($row['topic_approved'] && $row['topic_replies'] < $row['topic_replies_real'] && $_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? true : false;

		$newest_post_img = ($unread_topic) ? '<a href="' . generate_link("forums&amp;file=viewtopic&amp;t=$topic_id&amp;view=unread#unread") . '">' . $_CLASS['core_user']->img('icon_post_newest', 'VIEW_NEWEST_POST') . '</a> ' : '';

		$view_topic_url = 'forums&amp;file=viewtopic&amp;t='.$topic_id;
		$pagination = generate_pagination('forums&amp;file=viewtopic&amp;&amp;t='.$topic_id, $replies, $config['posts_per_page']);

		// Send vars to template
		$_CLASS['core_template']->assign_vars_array('topicrow', array(
			'FORUM_ID' 			=> $forum_id,
			'TOPIC_ID' 			=> $topic_id,

			'AUTHOR' 			=> ($row['topic_poster'] == ANONYMOUS) ? (($row['topic_first_poster_name']) ? $row['topic_first_poster_name'] : $_CLASS['core_user']->get_lang('GUEST')) : $row['topic_first_poster_name'],
			'LINK_AUTHOR' 		=> ($row['topic_poster'] == ANONYMOUS) ? '' : generate_link('members_list&amp;mode=viewprofile&amp;u=' . $row['topic_poster']),

			'FIRST_POST_TIME' 	=> $_CLASS['core_user']->format_date($row['topic_time']),
			'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
			'LAST_VIEW_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_view_time']),
			'LAST_POST_AUTHOR' 	=> ($row['topic_last_poster_name']) ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],

			'PAGINATION'		=> $pagination['formated'],
			'PAGINATION_ARRAY'	=> $pagination['array'],

			'REPLIES' 			=> $replies,
			'VIEWS' 			=> $row['topic_views'],
			'TOPIC_TITLE' 		=> censor_text($row['topic_title']),
			'TOPIC_TYPE' 		=> $topic_type,

			'TOPIC_LOCKED' 		=> ($row['topic_status'] == ITEM_LOCKED) ? 1 : 0,

			'LAST_POST_IMG' 	=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'),
			'NEWEST_POST_IMG' 	=> $newest_post_img,

			'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
			//'TOPIC_FOLDER_IMG_SRC'	=> $_CLASS['core_user']->img($folder_img, $folder_alt, false, '', 'src'),
			'TOPIC_ICON_IMG'        => empty($icons[$row['icon_id']]) ? '' : $icons[$row['icon_id']]['img'],
			'TOPIC_ICON_IMG_WIDTH'  => empty($icons[$row['icon_id']]) ? '' :  $icons[$row['icon_id']]['width'],
			'TOPIC_ICON_IMG_HEIGHT' => empty($icons[$row['icon_id']]) ? '' :  $icons[$row['icon_id']]['height'],
			'ATTACH_ICON_IMG'       => ($row['topic_attachment'] && $_CLASS['forums_auth']->acl_gets(array('f_download', 'u_download'), $forum_id)) ? $_CLASS['core_user']->img('icon_attach', $_CLASS['core_user']->lang['TOTAL_ATTACHMENTS']) : '',
			'UNAPPROVED_IMG'		=> ($topic_unapproved || $posts_unapproved) ? $_CLASS['core_user']->img('icon_unapproved', ($topic_unapproved) ? 'TOPIC_UNAPPROVED' : 'POSTS_UNAPPROVED') : '',

			'S_TOPIC_UNAPPROVED'	=> $topic_unapproved,
			'S_POSTS_UNAPPROVED'	=> $posts_unapproved,
			'S_HAS_POLL'			=> ($row['poll_start']),
			'S_POST_ANNOUNCE'		=> ($row['topic_type'] == POST_ANNOUNCE),
			'S_POST_GLOBAL'			=> ($row['topic_type'] == POST_GLOBAL),
			'S_POST_STICKY'			=> ($row['topic_type'] == POST_STICKY),
			'S_TOPIC_LOCKED'		=> ($row['topic_status'] == ITEM_LOCKED),
			'S_TOPIC_MOVED'			=> ($row['topic_status'] == ITEM_MOVED),
			
			'S_TOPIC_TYPE'			=> $row['topic_type'], 
			'S_UNREAD_TOPIC'		=> $unread_topic,
			'S_TOPIC_REPORTED'		=> ($row['topic_reported'] && $_CLASS['forums_auth']->acl_get('m_report', $forum_id)),
			'S_TOPIC_UNAPPROVED'	=> (!$row['topic_approved'] && $_CLASS['forums_auth']->acl_get('m_approve', $forum_id)),

			'U_NEWEST_POST'			=> generate_link($view_topic_url . '&amp;view=unread#unread'),
			'U_LAST_POST'			=> generate_link($view_topic_url . '&amp;p=' . $row['topic_last_post_id'] . '#p' . $row['topic_last_post_id']),	
			'U_LAST_POST_AUTHOR'	=> ($row['topic_last_poster_id']&& $row['topic_last_poster_id'] != ANONYMOUS) ? generate_link('members_list&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']) : '',
			'U_VIEW_TOPIC'			=> generate_link($view_topic_url),

			'U_MCP_REPORT'			=> generate_link('forums&amp;file=mcp&amp;mode=reports&amp;t='.$topic_id),
			'U_MCP_QUEUE'       	=> generate_link('forums&amp;file=mcp&amp;i=queue&amp;mode=approve_details&amp;t='.$topic_id),

			'S_TOPIC_TYPE_SWITCH'=> ($s_type_switch == $s_type_switch_test) ? -1 : $s_type_switch_test)
		);

		$s_type_switch = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;

		unset($row, $rowset[$topic_id]);
	}

	unset($topic_list);
}

// Update the marktime only if $mark_forum_read is set to a time
if ($forum_data['forum_type'] == FORUM_POST && is_int($mark_forum_read))
{
	markread('forum', $forum_id, false, $mark_forum_read);
}


page_header();

make_jumpbox(generate_link('forums&amp;file=viewforum'), $forum_id);

$_CLASS['core_display']->footer .= $_CLASS['core_template']->display('modules/forums/menus.html', true);

$_CLASS['core_display']->display(false, 'modules/forums/viewforum_body.html');

?>