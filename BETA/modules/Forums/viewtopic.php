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
// $Id: viewtopic.php,v 1.349 2004/09/16 18:33:17 acydburn Exp $
//
// FILENAME  : viewtopic.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

if (!defined('VIPERAL')) {
    header('location: ../../');
    die();
}

global $MAIN_CFG;

requireOnce('includes/forums/functions.'.$phpEx);
loadclass('includes/forums/auth.'.$phpEx, 'auth');

$_CLASS['auth']->acl($_CLASS['user']->data);

// Initial var setup
$forum_id	= request_var('f', 0);
$topic_id	= request_var('t', 0);
$post_id	= request_var('p', 0);
$voted_id	= request_var('vote_id', 0);;

$start		= request_var('start', 0);
$view		= request_var('view', '');
$rate		= request_var('rate', '');
$sort_days	= request_var('st', 0);
$sort_key	= request_var('sk', 't');
$sort_dir	= request_var('sd', 'a');
$update		= request_var('update', false);

$hilit_words		= urldecode(request_var('hilit', ''));

// Do we have a topic or post id?
if (!$topic_id && !$post_id)
{
	trigger_error('NO_TOPIC');
}

$_CLASS['template']->assign(array(
	'S_FORUM_RULES' 			=> false,
	'S_HIDDEN_FIELDS' 			=> false,
	'S_TOPIC_ACTION' 			=> '')
);

// Find topic id if user requested a newer or older topic
$unread_post_id = '';
if ($view && !$post_id)
{
	if ($view == 'unread')
	{
		if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
		{
			if ($config['load_db_lastread'])
			{
				switch (SQL_LAYER)
				{
					case 'oracle':
						break;

					default:
						$sql_lastread = 'LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.user_id = ' . $_CLASS['user']->data['user_id'] . ' 
							AND tt.topic_id = p.topic_id)';
						$sql_unread_time = ' tt.mark_time OR tt.mark_time IS NULL';
				}
			}
			else
			{
				$sql_lastread = '';
				$sql_unread_time = 0;
				if (isset($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_track']))
				{
					$tracking_topics = unserialize(stripslashes($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_track']));
					if (isset($tracking_topics[$forum_id]))
					{
						$sql_unread_time = base_convert(max($tracking_topics[$forum_id]), 36, 10);
						$sql_unread_time = max($sql_unread_time, $_CLASS['user']->data['session_last_visit']);
					}
				}
			}

			$sql = 'SELECT p.post_id
				FROM (' . POSTS_TABLE . " p 
				$sql_lastread, " . TOPICS_TABLE . " t)
				WHERE t.topic_id = $topic_id
					AND p.topic_id = t.topic_id 
					" . (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND p.post_approved = 1') . " 
					AND (p.post_time > $sql_unread_time
						OR p.post_id = t.topic_last_post_id)
				ORDER BY p.post_time ASC";
			$result = $_CLASS['db']->sql_query_limit($sql, 1);

			if (!($row = $_CLASS['db']->sql_fetchrow($result)))
			{
				// Setup user environment so we can process lang string
				$_CLASS['user']->add_lang('viewtopic');
				meta_refresh(3, getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id"));
				$message = $_CLASS['user']->lang['NO_UNREAD_POSTS'] . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_TOPIC'], '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id"),'">', '</a>');
				trigger_error($message);
			}
			$_CLASS['db']->sql_freeresult($result);

			$unread_post_id = $post_id = $row['post_id'];
		}
	}
	else if ($view == 'next' || $view == 'previous')
	{
		$sql_condition = ($view == 'next') ? '>' : '<';
		$sql_ordering = ($view == 'next') ? 'ASC' : 'DESC';

		$sql = 'SELECT t.topic_id
			FROM ' . TOPICS_TABLE . ' t, ' . TOPICS_TABLE . " t2
			WHERE t2.topic_id = $topic_id
				AND t.forum_id = t2.forum_id
				" . (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1') . " 
				AND t.topic_last_post_time $sql_condition t2.topic_last_post_time
			ORDER BY t.topic_last_post_time $sql_ordering";
		$result = $_CLASS['db']->sql_query_limit($sql, 1);

		if (!($row = $_CLASS['db']->sql_fetchrow($result)))
		{
			$message = ($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS';
			trigger_error($message);
		}
		else
		{
			$topic_id = $row['topic_id'];
		}
	}
}

// This rather complex gaggle of code handles querying for topics but
// also allows for direct linking to a post (and the calculation of which
// page the post is on and the correct display of viewtopic)
$join_sql_table = (!$post_id) ? '' : ', ' . POSTS_TABLE . ' p, ' . POSTS_TABLE . ' p2 ';
if (!$post_id)
{
	$join_sql = "t.topic_id = $topic_id";
}
else
{
	if ($_CLASS['auth']->acl_get('m_approve', $forum_id))
	{
		$join_sql = (!$post_id) ? "t.topic_id = $topic_id" : "p.post_id = $post_id AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_id <= $post_id";
	}
	else
	{
		$join_sql = (!$post_id) ? "t.topic_id = $topic_id" : "p.post_id = $post_id AND p.post_approved = 1 AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_approved = 1 AND p2.post_id <= $post_id";
	}
}
$extra_fields = (!$post_id)  ? '' : ', COUNT(p2.post_id) AS prev_posts';
$order_sql = (!$post_id) ? '' : 'GROUP BY p.post_id, t.topic_id, t.topic_title, t.topic_status, t.topic_replies, t.topic_time, t.topic_type, t.poll_max_options, t.poll_start, t.poll_length, t.poll_title, f.forum_name, f.forum_desc, f.forum_parents, f.parent_id, f.left_id, f.right_id, f.forum_status, f.forum_id, f.forum_style, f.forum_password ORDER BY p.post_id ASC';

if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
{
	switch (SQL_LAYER)
	{
		case 'oracle':
			// TODO
			break;

		default:
			$extra_fields .= ', tw.notify_status' . (($config['allow_bookmarks']) ? ', bm.order_id as bookmarked' : '');
			$join_sql_table .= ' LEFT JOIN ' . TOPICS_WATCH_TABLE . ' tw ON (tw.user_id = ' . $_CLASS['user']->data['user_id'] . ' 
				AND t.topic_id = tw.topic_id)';
			$join_sql_table .= ($config['allow_bookmarks']) ? ' LEFT JOIN ' . BOOKMARKS_TABLE . ' bm ON (bm.user_id = ' . $_CLASS['user']->data['user_id'] . '
				AND t.topic_id = bm.topic_id)' : '';
	}
}

// Join to forum table on topic forum_id unless topic forum_id is zero
// whereupon we join on the forum_id passed as a parameter ... this
// is done so navigation, forum name, etc. remain consistent with where
// user clicked to view a global topic
$sql = 'SELECT t.topic_id, t.forum_id, t.topic_title, t.topic_attachment, t.topic_status, t.topic_approved, t.topic_replies_real, t.topic_replies, t.topic_first_post_id, t.topic_last_post_id, t.topic_last_poster_id, t.topic_last_post_time, t.topic_poster, t.topic_time, t.topic_time_limit, t.topic_type, t.topic_bumped, t.topic_bumper, t.poll_max_options, t.poll_start, t.poll_length, t.poll_title, t.poll_vote_change, f.forum_name, f.forum_desc, f.forum_parents, f.parent_id, f.left_id, f.right_id, f.forum_status, f.forum_type, f.forum_id, f.forum_style, f.forum_password, f.forum_rules, f.forum_rules_link, f.forum_rules_flags, f.forum_rules_bbcode_uid, f.forum_rules_bbcode_bitfield' . $extra_fields . '
	FROM ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f' . $join_sql_table . "
	WHERE $join_sql
		AND (f.forum_id = t.forum_id
			" . ((!$forum_id) ? '' : 'OR (t.topic_type = ' . POST_GLOBAL . " AND f.forum_id = $forum_id)") . "
			)
		$order_sql";
$result = $_CLASS['db']->sql_query($sql);

if (!($topic_data = $_CLASS['db']->sql_fetchrow($result)))
{
	// If post_id was submitted, we try at least to display the topic as a last resort...
	if ($post_id && $forum_id && $topic_id)
	{
		redirect(getlink("Forums&amp;file=viewtopic&f=$forum_id&t=$topic_id"));
	}
	trigger_error('NO_TOPIC');
}

// Extract the data
extract($topic_data);

// We make this check here because the correct forum_id is determined
$topic_replies = ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $topic_replies_real : $topic_replies;
unset($topic_replies_real);

if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
{
	if ($config['load_db_lastread'])
	{
		$sql = 'SELECT mark_time
			FROM ' . TOPICS_TRACK_TABLE . '
			WHERE user_id = ' . $_CLASS['user']->data['user_id'] . "
				AND topic_id = $topic_id";
		$result = $_CLASS['db']->sql_query($sql);
		$topic_last_read = (int) $_CLASS['db']->sql_fetchfield('mark_time', 0, $result);
		$_CLASS['db']->sql_freeresult($result);
	}
	else
	{
		$topic_last_read = 0;
		if (isset($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_track']))
		{
			$tracking_topics = unserialize(stripslashes($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_track']));

			if (isset($tracking_topics[$forum_id]))
			{
				$topic_last_read = base_convert(max($tracking_topics[$forum_id]), 36, 10);
				$topic_last_read = max($topic_last_read, $_CLASS['user']->data['session_last_visit']);
			}
			unset($tracking_topics);
		}
	}
}
else
{
	$topic_last_read = 0;
}

// Check sticky/announcement time limit
if (($topic_type == POST_STICKY || $topic_type == POST_ANNOUNCE) && $topic_time_limit && $topic_time + $topic_time_limit < time())
{
	$sql = 'UPDATE ' . TOPICS_TABLE . ' 
		SET topic_type = ' . POST_NORMAL . ', topic_time_limit = 0
		WHERE topic_id = ' . $topic_id;
	$_CLASS['db']->sql_query($sql);
	$topic_type = POST_NORMAL;
	$topic_time_limit = 0;
}

$_CLASS['user']->add_lang('viewtopic');
$_CLASS['user']->add_img(0, 'Forums');

if (!$topic_approved && !$_CLASS['auth']->acl_get('m_approve', $forum_id))
{
	trigger_error('NO_TOPIC');
}

// Start auth check
if (!$_CLASS['auth']->acl_get('f_read', $forum_id))
{
	if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
	{
		trigger_error($_CLASS['user']->lang['SORRY_AUTH_READ']);
	}

	login_box('', $_CLASS['user']->lang['LOGIN_VIEWFORUM']);
}

// Forum is passworded ... check whether access has been granted to this
// user this session, if not show login box
if ($forum_password)
{
	login_forum_box($topic_data);
}

// Redirect to login or to the correct post upon emailed notification links
if (isset($_GET['e']))
{
	$jump_to = (int) $_GET['e'];

	$redirect_url = "Forums&amp;file=viewtopic&f=$forum_id&t=$topic_id";
	if ($_CLASS['user']->data['user_id'] == ANONYMOUS)
	{
		login_box(getlink($redirect_url . "&p=$post_id&e=$jump_to"), $_CLASS['user']->lang['LOGIN_NOTIFY_TOPIC']);
	}
	else if ($jump_to > 0)
	{
		// We direct the already logged in user to the correct post...
		redirect(getlink($redirect_url . ((!$post_id) ? "&p=$jump_to" : "&p=$post_id") . "#$jump_to"));
	}
}
// Not final in the slightest! Far too simplistic
// needs alot of work look into other karma systems
// feather intergration should be simple
// get better rating type
if ($rate)
{
	// Check for rating count for previous X time

	// Grab existing rating for this post, if it exists
	$sql = 'SELECT * 
		FROM ' . RATINGS_TABLE . ' 
		WHERE user_id = ' . $_CLASS['user']->data['user_id'] . "
			AND post_id = $post_id";
	$result = $_CLASS['db']->sql_query($sql);

	switch ($rate)
	{
		case 'good':
			$rate = 1;
			break;
		case 'bad':
			$rate = -1;
			break;
	}

	$updated = ($row = $_CLASS['db']->sql_fetchrow($result)) ? true : false;
	$_CLASS['db']->sql_freeresult($result);

	// Insert rating if appropriate
	$sql = (!$updated) ? 'INSERT INTO ' . RATINGS_TABLE . ' (user_id, post_id, rating, rating_time) VALUES (' . $_CLASS['user']->data['user_id'] . ", $post_id, $rate, " . time() . ')' : 'UPDATE ' . RATINGS_TABLE . " SET rating = $rate, rating_time = " . time() . " WHERE post_id = $post_id AND user_id = " . $_CLASS['user']->data['user_id'];
	$_CLASS['db']->sql_query($sql);

	// Rating sum and count past thirty days
	$sql = 'SELECT p.poster_id, SUM(r.rating) AS rated, COUNT(r.rating) as total_ratings
		FROM ' . RATINGS_TABLE . ' r, ' . POSTS_TABLE . ' p, ' . POSTS_TABLE . " p2  
		WHERE p2.post_id = $post_id
			AND p.poster_id = p2.poster_id  
			AND p.post_time > " . (time() - (30 * 86400)) . ' 
			AND r.post_id = p.post_id 
			AND r.user_id <> p2.poster_id 
		GROUP BY p.poster_id';
	$result = $_CLASS['db']->sql_query($sql);

	$row = $_CLASS['db']->sql_fetchrow($result);
	$_CLASS['db']->sql_freeresult($result);

	// Rating sum and count since first post
	$sql = 'SELECT p.poster_id, SUM(r.rating) AS rated, COUNT(r.rating) as total_ratings
		FROM ' . RATINGS_TABLE . ' r, ' . POSTS_TABLE . ' p, ' . POSTS_TABLE . " p2  
		WHERE p2.post_id = $post_id 
			AND p.poster_id = p2.poster_id 
			AND p.post_time < " . (time() - (30 * 86400)) . ' 
			AND r.post_id = p.post_id 
			AND r.user_id <> p2.poster_id 
		GROUP BY p.poster_id';
	$result = $_CLASS['db']->sql_query($sql);

	$row = $_CLASS['db']->sql_fetchrow($result);
	$_CLASS['db']->sql_freeresult($result);

	$total_ratings = $row['total_ratings'];
	$historic_rating = ($row['rated'] / $row['total_ratings']) * 0.30;

	$total_ratings += $row['total_ratings'];
	$thirty_day_rating = ($row['rated'] / $row['total_ratings']) * 0.50;

	if ($total_ratings > $config['min_ratings'])
	{
		// Post count and reg date for this user
		$sql = 'SELECT user_id, user_regdate, user_posts 
			FROM ' . USERS_TABLE . ' 
			WHERE user_id = ' . $row['poster_id'];
		$result = $_CLASS['db']->sql_query($sql);

		$row = $_CLASS['db']->sql_fetchrow($result);
		$_CLASS['db']->sql_freeresult($result);

		$post_count_rating = ($row['user_posts'] / $config['num_posts']) * 0.1;
		$day_rating = (($row['user_regdate'] > $config['board_startdate']) ? $config['board_startdate'] / $row['user_regdate'] : 1) * 0.1;
		$poster_id = $row['user_id'];

		// Number of rated posts by this user
//		$sql = 'SELECT COUNT(DISTINCT(p.post_id)) AS rated_posts
//			FROM ' . RATINGS_TABLE . ' r , ' . POSTS_TABLE . " p 
//			WHERE p.poster_id = $poster_id  
//				AND r.post_id = p.post_id
//				AND r.user_id <> $poster_id";
//		$result = $_CLASS['db']->sql_query($sql);

//		$row = $_CLASS['db']->sql_fetchrow($result);
//		$_CLASS['db']->sql_freeresult($result);

		$karma = ($historic_rating + $thirty_day_rating + $day_rating + $post_count_rating) * 5;
		$karma = ($karma < 0) ? floor($karma) : (($karma > 0) ? ceil($karma) : 0);

		$sql = 'UPDATE ' . USERS_TABLE . "
			SET user_karma = $karma 
			WHERE user_id = $poster_id";
		$_CLASS['db']->sql_query($sql);
	}

	meta_refresh(3, getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$post_id#$post_id"));
	$message = ($updated) ? $_CLASS['user']->lang['RATING_UPDATED'] : $_CLASS['user']->lang['RATING_ADDED'];
	$message = $message . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_POST'], '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$post_id#$post_id").'">', '</a>');
	trigger_error($message);
}

// What is start equal to?
if (!empty($post_id))
{
	$start = floor(($prev_posts - 1) / $config['posts_per_page']) * $config['posts_per_page'];
}

// Post ordering options
$limit_days = array(0 => $_CLASS['user']->lang['ALL_POSTS'], 1 => $_CLASS['user']->lang['1_DAY'], 7 => $_CLASS['user']->lang['7_DAYS'], 14 => $_CLASS['user']->lang['2_WEEKS'], 30 => $_CLASS['user']->lang['1_MONTH'], 90 => $_CLASS['user']->lang['3_MONTHS'], 180 => $_CLASS['user']->lang['6_MONTHS'], 364 => $_CLASS['user']->lang['1_YEAR']);

$sort_by_text = array('a' => $_CLASS['user']->lang['AUTHOR'], 't' => $_CLASS['user']->lang['POST_TIME'], 's' => $_CLASS['user']->lang['SUBJECT']);
$sort_by_sql = array('a' => 'u.username', 't' => 'p.post_id', 's' => 'p.post_subject');

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

// Obtain correct post count and ordering SQL if user has
// requested anything different
if ($sort_days)
{
	$min_post_time = time() - ($sort_days * 86400);

	$sql = 'SELECT COUNT(post_id) AS num_posts
		FROM ' . POSTS_TABLE . "
		WHERE topic_id = $topic_id
			AND post_time >= $min_post_time
		" . (($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND p.post_approved = 1');
	$result = $_CLASS['db']->sql_query($sql);

	if (isset($_POST['sort']))
	{
		$start = 0;
	}
	$total_posts = ($row = $_CLASS['db']->sql_fetchrow($result)) ? $row['num_posts'] : 0;
	$limit_posts_time = "AND p.post_time >= $min_post_time ";
}
else
{
	$total_posts = $topic_replies + 1;
	$limit_posts_time = '';
}

// Was a highlight request part of the URI?
$highlight_match = $highlight = '';
if ($hilit_words)
{
	foreach (explode(' ', trim($hilit_words)) as $word)
	{
		if (trim($word))
		{
			$highlight_match .= (($highlight_match != '') ? '|' : '') . str_replace('\*', '\w*?', preg_quote($word, '#'));
		}
	}

	$highlight = htmlspecialchars(urlencode($hilit_words));
}

// General Viewtopic URL for return links
$viewtopic_url = "Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;start=$start&amp;$u_sort_param" . (($highlight_match) ? "&amp;hilit=$highlight" : '');

// Are we watching this topic?
$s_watching_topic = $s_watching_topic_img = array();
$s_watching_topic['link'] = $s_watching_topic['title'] = '';
if ($config['email_enable'] && $config['allow_topic_notify'] && $_CLASS['user']->data['user_id'] != ANONYMOUS)
{
	watch_topic_forum('topic', $s_watching_topic, $s_watching_topic_img, $_CLASS['user']->data['user_id'], $topic_id, $notify_status, $start);
}

// Bookmarks
if ($config['allow_bookmarks'] && $_CLASS['user']->data['user_id'] != ANONYMOUS && request_var('bookmark', 0))
{
	if (!$bookmarked)
	{
		$sql = 'INSERT INTO ' . BOOKMARKS_TABLE . ' ' . $_CLASS['db']->sql_build_array('INSERT', array(
			'user_id'	=> $_CLASS['user']->data['user_id'],
			'topic_id'	=> $topic_id,
			'order_id'	=> 0)
		);
		$_CLASS['db']->sql_query($sql);

		$where_sql = '';
		$sign = '+';
	}
	else
	{
		$sql = 'DELETE FROM ' . BOOKMARKS_TABLE . " 
			WHERE user_id = {$_CLASS['user']->data['user_id']}
				AND topic_id = $topic_id";
		$_CLASS['db']->sql_query($sql);
	
		// Works because of current order_id selected as bookmark value (please do not change because of simplicity)
		$where_sql = " AND order_id > $bookmarked";
		$sign = '-';
	}

	// Re-Sort Bookmarks
	$sql = 'UPDATE ' . BOOKMARKS_TABLE . "
		SET order_id = order_id $sign 1
			WHERE user_id = {$_CLASS['user']->data['user_id']}
			$where_sql";
	$_CLASS['db']->sql_query($sql);

	meta_refresh(3, getlink($viewtopic_url, false));
	$message = (($bookmarked) ? $_CLASS['user']->lang['BOOKMARK_REMOVED'] : $_CLASS['user']->lang['BOOKMARK_ADDED']) . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_TOPIC'], '<a href="' . getlink($viewtopic_url, false) . '">', '</a>');
	trigger_error($message);
}

// Grab ranks
$ranks = array();
obtain_ranks($ranks);

// Grab icons
$icons = array();
obtain_icons($icons);

// Grab extensions
$extensions = array();
if ($topic_attachment)
{
	obtain_attach_extensions($extensions);
}

// Forum rules listing
$s_forum_rules = '';
gen_forum_auth_level('topic', $forum_id);

// Quick mod tools
$topic_mod = '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_lock', $forum_id) || ($_CLASS['auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['user']->data['user_id'] != ANONYMOUS && $_CLASS['user']->data['user_id'] == $topic_poster)) ? (($topic_status == ITEM_UNLOCKED) ? '<option value="lock">' . $_CLASS['user']->lang['LOCK_TOPIC'] . '</option>' : '<option value="unlock">' . $_CLASS['user']->lang['UNLOCK_TOPIC'] . '</option>') : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_delete', $forum_id)) ? '<option value="delete_topic">' . $_CLASS['user']->lang['DELETE_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_move', $forum_id)) ? '<option value="move">' . $_CLASS['user']->lang['MOVE_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_split', $forum_id)) ? '<option value="split">' . $_CLASS['user']->lang['SPLIT_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_merge', $forum_id)) ? '<option value="merge">' . $_CLASS['user']->lang['MERGE_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_', $forum_id)) ? '<option value="fork">' . $_CLASS['user']->lang['FORK_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_', $forum_id) && $topic_type != POST_NORMAL) ? '<option value="make_normal">' . $_CLASS['user']->lang['MAKE_NORMAL'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('f_sticky', $forum_id) && $topic_type != POST_STICKY) ? '<option value="make_sticky">' . $_CLASS['user']->lang['MAKE_STICKY'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('f_announce', $forum_id) && $topic_type != POST_ANNOUNCE) ? '<option value="make_announce">' . $_CLASS['user']->lang['MAKE_ANNOUNCE'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('f_announce', $forum_id) && $topic_type != POST_GLOBAL) ? '<option value="make_global">' . $_CLASS['user']->lang['MAKE_GLOBAL'] . '</option>' : '';
$topic_mod .= ($_CLASS['auth']->acl_get('m_', $forum_id)) ? '<option value="viewlogs">' . $_CLASS['user']->lang['VIEW_TOPIC_LOGS'] . '</option>' : '';

// If we've got a hightlight set pass it on to pagination.
$pagination = generate_pagination("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;$u_sort_param" . (($highlight_match) ? "&amp;hilit=$highlight" : ''), $total_posts, $config['posts_per_page'], $start);

// Navigation links
generate_forum_nav($topic_data);

// Forum Rules
generate_forum_rules($topic_data);

// Moderators
$forum_moderators = array();
get_moderators($forum_moderators, $forum_id);

// This is only used for print view so ...
$server_path = (!$view) ? '' : generate_board_url() . '/';

// Replace naughty words in title
$topic_title = censor_text($topic_title);

// Send vars to template
$_CLASS['template']->assign(array(
	'FORUM_ID' 		=> $forum_id,
	'FORUM_NAME' 	=> $forum_name,
	'FORUM_DESC'	=> $forum_desc,
	'TOPIC_ID' 		=> $topic_id,
	'TOPIC_TITLE' 	=> $topic_title,
	'PAGINATION' 	=> $pagination,
	'PAGE_NUMBER' 	=> on_page($total_posts, $config['posts_per_page'], $start),
	'TOTAL_POSTS'	=> ($total_posts == 1) ? $_CLASS['user']->lang['VIEW_TOPIC_POST'] : sprintf($_CLASS['user']->lang['VIEW_TOPIC_POSTS'], $total_posts), 
	'U_MCP' 		=> ($_CLASS['auth']->acl_get('m_', $forum_id)) ? getlink("Forums&amp;file=mcp&amp;mode=topic_view&amp;f=$forum_id&amp;t=$topic_id&amp;start=$start&amp;$u_sort_param", false, false) : '',

	'MODERATORS'	=> (isset($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id])) ? implode(', ', $forum_moderators[$forum_id]) : '',

	'POST_IMG' 			=> ($forum_status == ITEM_LOCKED) ? $_CLASS['user']->img('btn_locked', 'FORUM_LOCKED') : $_CLASS['user']->img('btn_post', 'POST_NEW_TOPIC'),
	'QUOTE_IMG' 		=> $_CLASS['user']->img('btn_quote', 'REPLY_WITH_QUOTE'),
	'REPLY_IMG'			=> ($forum_status == ITEM_LOCKED || $topic_status == ITEM_LOCKED) ? $_CLASS['user']->img('btn_locked', 'TOPIC_LOCKED') : $_CLASS['user']->img('btn_reply', 'REPLY_TO_TOPIC'),
	'EDIT_IMG' 			=> $_CLASS['user']->img('btn_edit', 'EDIT_POST'),
	'DELETE_IMG' 		=> $_CLASS['user']->img('btn_delete', 'DELETE_POST'),
	'INFO_IMG'          => $_CLASS['user']->img('btn_info', 'VIEW_INFO'),
	'PROFILE_IMG'		=> $_CLASS['user']->img('btn_profile', 'READ_PROFILE'), 
	'SEARCH_IMG' 		=> $_CLASS['user']->img('btn_search', 'SEARCH_USER_POSTS'),
	'PM_IMG' 			=> $_CLASS['user']->img('btn_pm', 'SEND_PRIVATE_MESSAGE'),
	'EMAIL_IMG' 		=> $_CLASS['user']->img('btn_email', 'SEND_EMAIL'),
	'WWW_IMG' 			=> $_CLASS['user']->img('btn_www', 'VISIT_WEBSITE'),
	'ICQ_IMG' 			=> $_CLASS['user']->img('btn_icq', 'ICQ'),
	'AIM_IMG' 			=> $_CLASS['user']->img('btn_aim', 'AIM'),
	'MSN_IMG' 			=> $_CLASS['user']->img('btn_msnm', 'MSNM'),
	'YIM_IMG' 			=> $_CLASS['user']->img('btn_yim', 'YIM'),
	'JABBER_IMG'		=> $_CLASS['user']->img('btn_jabber', 'JABBER') ,
	'REPORT_IMG'		=> $_CLASS['user']->img('btn_report', 'REPORT_POST'),
	'REPORTED_IMG'		=> $_CLASS['user']->img('icon_reported', 'POST_REPORTED'),
	'UNAPPROVED_IMG'	=> $_CLASS['user']->img('icon_unapproved', 'POST_UNAPPROVED'),
	'KARMA_LEFT_IMG'        => $_CLASS['user']->img('karma_left', ''),
	'KARMA_RIGHT_IMG'       => $_CLASS['user']->img('karma_right', ''),
	
	'S_SELECT_SORT_DIR' 	=> $s_sort_dir,
	'S_SELECT_SORT_KEY' 	=> $s_sort_key,
	'S_SELECT_SORT_DAYS' 	=> $s_limit_days,
	'S_TOPIC_ACTION' 		=> getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;start=$start"),
	'S_TOPIC_MOD' 			=> ($topic_mod != '') ? '<select name="mode">' . $topic_mod . '</select>' : '',
	'S_MOD_ACTION' 			=> getlink("Forums&amp;file=mcp&amp;t=$topic_id&amp;f=$forum_id&amp;quickmod=1", false, false), 

	'S_DISPLAY_SEARCHBOX'	=> ($_CLASS['auth']->acl_get('f_search', $forum_id)) ? true : false, 
	'S_SEARCHBOX_ACTION'	=> getlink('Forums&amp;file=search&amp;f='.$forum_id), 

	'U_TOPIC'				=> (!$view == 'print') ? getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id") : getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id", true, true),
	'U_FORUM'				=> $server_path,
	'U_VIEW_UNREAD_POST'	=> getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;view=unread#unread"),
	'U_VIEW_TOPIC' 			=> getlink($viewtopic_url, false),
	'U_VIEW_FORUM' 			=> getlink('Forums&amp;file=viewforum&amp;f='.$forum_id),
	'U_VIEW_OLDER_TOPIC'	=> getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;view=previous"),
	'U_VIEW_NEWER_TOPIC'	=> getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;view=next"),
	'U_PRINT_TOPIC'			=> ($_CLASS['auth']->acl_get('f_print', $forum_id)) ? getlink($viewtopic_url . '&amp;view=print', false) : '',
	'U_EMAIL_TOPIC'			=> ($_CLASS['auth']->acl_get('f_email', $forum_id) && $config['email_enable']) ? getlink('Members_List&amp;mode=email&amp;t='.$topic_id) : '', 

	'U_WATCH_TOPIC' 		=> $s_watching_topic['link'], 
	'L_WATCH_TOPIC' 		=> $s_watching_topic['title'], 

	'U_BOOKMARK_TOPIC'		=> ($_CLASS['user']->data['user_id'] != ANONYMOUS && $config['allow_bookmarks']) ? getlink($viewtopic_url . '&amp;bookmark=1', false) : '',
	'L_BOOKMARK_TOPIC'		=> ($_CLASS['user']->data['user_id'] != ANONYMOUS && $config['allow_bookmarks'] && $bookmarked) ? $_CLASS['user']->lang['BOOKMARK_TOPIC_REMOVE'] : $_CLASS['user']->lang['BOOKMARK_TOPIC'],
	
	'U_POST_NEW_TOPIC' 		=> getlink('Forums&amp;file=posting&amp;mode=post&amp;f='.$forum_id),
	'U_POST_REPLY_TOPIC' 	=> getlink("Forums&amp;file=posting&amp;mode=reply&amp;f=$forum_id&amp;t=$topic_id"),
	'U_BUMP_TOPIC'			=> (bump_topic_allowed($forum_id, $topic_bumped, $topic_last_post_time, $topic_poster, $topic_last_poster_id)) ? getlink("Forums&amp;file=posting&amp;mode=bump&amp;f=$forum_id&amp;t=$topic_id") : '')
);

// Does this topic contain a poll?
if (!empty($poll_start))
{
	$sql = 'SELECT o.*, p.bbcode_bitfield, p.bbcode_uid
		FROM ' . POLL_OPTIONS_TABLE . ' o, ' . POSTS_TABLE . " p
		WHERE o.topic_id = $topic_id 
			AND p.post_id = $topic_first_post_id
			AND p.topic_id = o.topic_id
		ORDER BY o.poll_option_id";
	$result = $_CLASS['db']->sql_query($sql);

	$poll_info = array();
	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$poll_info[] = $row;
	}
	$_CLASS['db']->sql_freeresult($result);

	$cur_voted_id = array();
	if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
	{
		$sql = 'SELECT poll_option_id
			FROM ' . POLL_VOTES_TABLE . '
			WHERE topic_id = ' . $topic_id . '
				AND vote_user_id = ' . $_CLASS['user']->data['user_id'];
		$result = $_CLASS['db']->sql_query($sql);

		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$cur_voted_id[] = $row['poll_option_id'];
		}
		$_CLASS['db']->sql_freeresult($result);
	}
	else
	{
		// Cookie based guest tracking ... I don't like this but hum ho
		// it's oft requested. This relies on "nice" users who don't feel
		// the need to delete cookies to mess with results.
		if (isset($_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_poll_' . $topic_id]))
		{
			$cur_voted_id = explode(',', $_COOKIE[$MAIN_CFG['server']['cookie_name'] . '_poll_' . $topic_id]);
		}
	}

	$s_can_vote = (((!sizeof($cur_voted_id) && $_CLASS['auth']->acl_get('f_vote', $forum_id)) || 
		($_CLASS['auth']->acl_get('f_votechg', $forum_id) && $poll_vote_change)) &&
		(($poll_length != 0 && $poll_start + $poll_length > time()) || $poll_length == 0) &&
		$topic_status != ITEM_LOCKED && 
		$forum_status != ITEM_LOCKED) ? true : false;
	$s_display_results = (!$s_can_vote || ($s_can_vote && $voted_id) || $view == 'viewpoll') ? true : false;

	if ($update && $s_can_vote)
	{
		if (!sizeof($voted_id) || sizeof($voted_id) > $poll_max_options)
		{
			meta_refresh(5, getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id"));

			$message = (!sizeof($voted_id)) ? 'NO_VOTE_OPTION' : 'TOO_MANY_VOTE_OPTIONS';
			$message = $_CLASS['user']->lang[$message] . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_TOPIC'], '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id").'">', '</a>');
			trigger_error($message);
		}

		foreach ($voted_id as $option)
		{
			if (in_array($option, $cur_voted_id))
			{
				continue;
			}

			$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . " 
				SET poll_option_total = poll_option_total + 1 
				WHERE poll_option_id = $option 
					AND topic_id = $topic_id";
			$_CLASS['db']->sql_query($sql);

			if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
			{
				$sql = 'INSERT INTO  ' . POLL_VOTES_TABLE . " (topic_id, poll_option_id, vote_user_id, vote_user_ip) 
					VALUES ($topic_id, $option, " . $_CLASS['user']->data['user_id'] . ", '".$_CLASS['user']->ip."')";
				$_CLASS['db']->sql_query($sql);
			}
		}

		foreach ($cur_voted_id as $option)
		{
			if (!in_array($option, $voted_id))
			{
				$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . " 
					SET poll_option_total = poll_option_total - 1 
					WHERE poll_option_id = $option 
						AND topic_id = $topic_id";
				$_CLASS['db']->sql_query($sql);

				if ($_CLASS['user']->data['user_id'] != ANONYMOUS)
				{
					$sql = 'DELETE FROM ' . POLL_VOTES_TABLE . " 
						WHERE topic_id = $topic_id
							AND poll_option_id = $option 
							AND vote_user_id = " . $_CLASS['user']->data['user_id'];
					$_CLASS['db']->sql_query($sql);
				}
			}
		}

		if ($_CLASS['user']->data['user_id'] == ANONYMOUS)
		{
			$_CLASS['user']->set_cookie('poll_' . $topic_id, implode(',', $voted_id), time() + 31536000);
		}

//, topic_last_post_time = ' . time() . "  
		$sql = 'UPDATE ' . TOPICS_TABLE . ' 
			SET poll_last_vote = ' . time() . " 
			WHERE topic_id = $topic_id";
		$_CLASS['db']->sql_query($sql);

		meta_refresh(5, getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id"));

		$message = $_CLASS['user']->lang['VOTE_SUBMITTED'] . '<br /><br />' . sprintf($_CLASS['user']->lang['RETURN_TOPIC'], '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id").'">', '</a>');
		trigger_error($message);
	}

	$poll_total = 0;
	foreach ($poll_info as $poll_option)
	{
		$poll_total += $poll_option['poll_option_total'];
	}

	if ($poll_info[0]['bbcode_bitfield'])
	{
		requireOnce('includes/forums/bbcode.'.$phpEx);
		$poll_bbcode = new bbcode();
		
		$size = sizeof($poll_info);
		for ($i = 0, $size; $i < $size; $i++)
		{
			$poll_bbcode->bbcode_second_pass($poll_info[$i]['poll_option_text'], $poll_info[$i]['bbcode_uid'], $poll_option['bbcode_bitfield']);
			$poll_info[$i]['poll_option_text'] = smilie_text($poll_info[$i]['poll_option_text']);
			$poll_info[$i]['poll_option_text'] = str_replace("\n", '<br />', censor_text($poll_info[$i]['poll_option_text']));
		}

		$poll_bbcode->bbcode_second_pass($poll_title, $poll_info[0]['bbcode_uid'], $poll_info[0]['bbcode_bitfield']);
		$poll_title = smilie_text($poll_title);
		$poll_title = str_replace("\n", '<br />', censor_text($poll_title));

		unset($poll_bbcode);
	}
	
	foreach ($poll_info as $poll_option)
	{
		$option_pct = ($poll_total > 0) ? $poll_option['poll_option_total'] / $poll_total : 0;
		$option_pct_txt = sprintf("%.1d%%", ($option_pct * 100));

		$_CLASS['template']->assign_vars_array('poll_option', array(
			'POLL_OPTION_ID' 		=> $poll_option['poll_option_id'],
			'POLL_OPTION_CAPTION' 	=> $poll_option['poll_option_text'],
			'POLL_OPTION_RESULT' 	=> $poll_option['poll_option_total'],
			'POLL_OPTION_PERCENT' 	=> $option_pct_txt,
			'POLL_OPTION_IMG' 		=> $_CLASS['user']->img('poll_center', $option_pct_txt, round($option_pct * 250)), 
			'POLL_OPTION_VOTED'		=> (in_array($poll_option['poll_option_id'], $cur_voted_id)) ? true : false)
		);
	}

	$_CLASS['template']->assign(array(
		'POLL_QUESTION'		=> $poll_title,
		'TOTAL_VOTES' 		=> $poll_total,
		'POLL_LEFT_CAP_IMG'	=> $_CLASS['user']->img('poll_left'),
		'POLL_RIGHT_CAP_IMG'=> $_CLASS['user']->img('poll_right'),

		'L_MAX_VOTES'		=> ($poll_max_options == 1) ? $_CLASS['user']->lang['MAX_OPTION_SELECT'] : sprintf($_CLASS['user']->lang['MAX_OPTIONS_SELECT'], $poll_max_options), 
		'L_POLL_LENGTH'		=> ($poll_length) ? sprintf($_CLASS['user']->lang['POLL_RUN_TILL'], $_CLASS['user']->format_date($poll_length + $poll_start)) : '', 

		'S_HAS_POLL'		=> true, 
		'S_CAN_VOTE'		=> $s_can_vote, 
		'S_DISPLAY_RESULTS'	=> $s_display_results,
		'S_IS_MULTI_CHOICE'	=> ($poll_max_options > 1) ? true : false, 
		'S_POLL_ACTION'		=> getlink($viewtopic_url, false),

		'U_VIEW_RESULTS'	=> getlink($viewtopic_url . '&amp;view=viewpoll', false))
	);

	unset($poll_info, $voted_id);
}

// If the user is trying to reach the second half of the topic, fetch it starting from the end
$store_reverse = FALSE;
$sql_limit = $config['posts_per_page'];

if ($start > $total_posts / 2)
{
	$store_reverse = TRUE;

	if ($start + $config['posts_per_page'] > $total_posts)
	{
		$sql_limit = min($config['posts_per_page'], max(1, $total_posts - $start));
	}

	// Select the sort order
	$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC');
	$sql_start = max(0, $total_posts - $sql_limit - $start);
}
else
{
	// Select the sort order
	$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
	$sql_start = $start;
}

// Container for user details, only process once
$post_list = $user_cache = $id_cache = $attachments = $attach_list = $rowset = $update_count = $post_edit_list = array();
$has_attachments = $display_notice = false;
$force_encoding = '';
$bbcode_bitfield = $i = $i_total = 0;

// Go ahead and pull all data for this topic
$sql = 'SELECT p.post_id
	FROM ' . POSTS_TABLE . ' p' . (($sort_by_sql[$sort_key]{0} == 'u') ? ', ' . USERS_TABLE . ' u': '') . "
	WHERE p.topic_id = $topic_id
		" . ((!$_CLASS['auth']->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . "
		" . (($sort_by_sql[$sort_key]{0} == 'u') ? 'AND u.user_id = p.poster_id': '') . "
		$limit_posts_time
	ORDER BY $sql_sort_order";
$result = $_CLASS['db']->sql_query_limit($sql, $sql_limit, $sql_start);

$i = ($store_reverse) ? $sql_limit - 1 : 0;
while ($row = $_CLASS['db']->sql_fetchrow($result))
{
	$post_list[$i] = $row['post_id'];
	($store_reverse) ? --$i : ++$i;
}

if (empty($post_list))
{
	trigger_error($_CLASS['user']->lang['NO_TOPIC']);
}

$sql = 'SELECT u.username, u.user_id, u.user_colour, u.user_karma, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_jabber, u.user_regdate, u.user_msnm, u.user_allow_viewemail, u.user_allow_viewonline, u.user_rank, u.user_sig, u.user_sig_bbcode_uid, u.user_sig_bbcode_bitfield, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, z.friend, z.foe, p.*
	FROM ((' . POSTS_TABLE . ' p 
	LEFT JOIN ' . ZEBRA_TABLE . ' z ON (z.user_id = ' . $_CLASS['user']->data['user_id'] . ' AND z.zebra_id = p.poster_id)), ' . USERS_TABLE . ' u)  
	WHERE p.post_id IN (' . implode(', ', $post_list) . ')
		AND u.user_id = p.poster_id';
$result = $_CLASS['db']->sql_query($sql);

// Posts are stored in the $rowset array while $attach_list, $user_cache
// and the global bbcode_bitfield are built
while ($row = $_CLASS['db']->sql_fetchrow($result))
{
	$poster_id = $row['poster_id'];
	$poster	= ($poster_id == ANONYMOUS) ? ((!empty($row['post_username'])) ? $row['post_username'] : $_CLASS['user']->lang['GUEST']) : $row['username'];

	if (!$view || $view != 'show' || $post_id != $row['post_id'])
	{
		if ($row['user_karma'] < $_CLASS['user']->data['user_min_karma'])
		{
			$rowset[$row['post_id']] = array(
				'below_karma'	=> TRUE,
				'post_id'		=> $row['post_id'], 
				'poster'		=> $poster,
				'user_karma'	=> $row['user_karma']
			);

			continue;
		}
		elseif ($row['foe'])
		{
			$rowset[$row['post_id']] = array(
				'foe'		=> true,
				'post_id'	=> $row['post_id'], 
				'poster'	=> $poster,
			);

			continue;
		}
	}

	// Does post have an attachment? If so, add it to the list
	if ($row['post_attachment'] && $config['allow_attachments'])
	{
		$attach_list[] = $row['post_id'];
	
		if ($row['post_approved'])
		{
			$has_attachments = TRUE;
		}
	}

	$rowset[$row['post_id']] = array(
		'post_id'			=> $row['post_id'],
		'post_time'			=> $row['post_time'],
		'poster'			=> ($row['user_colour']) ? '<span style="color:#' . $row['user_colour'] . '">' . $poster . '</span>' : $poster,
		'user_id'			=> $row['user_id'],
		'topic_id'			=> $row['topic_id'],
		'forum_id'			=> $row['forum_id'],
		'post_subject'		=> $row['post_subject'],
		'post_edit_count'	=> $row['post_edit_count'],
		'post_edit_time'	=> $row['post_edit_time'],
		'post_edit_reason'	=> $row['post_edit_reason'],
		'post_edit_user'	=> $row['post_edit_user'],
		'icon_id'			=> $row['icon_id'],
		'post_attachment'	=> $row['post_attachment'],
		'post_approved'		=> $row['post_approved'],
		'post_reported'		=> $row['post_reported'],
		'post_text'			=> $row['post_text'],
		'post_encoding'		=> $row['post_encoding'],
		'bbcode_uid'		=> $row['bbcode_uid'],
		'bbcode_bitfield'	=> $row['bbcode_bitfield'],
		'enable_html'		=> $row['enable_html'],
		'enable_smilies'	=> $row['enable_smilies'],
		'enable_sig'		=> $row['enable_sig'], 
		'friend'			=> $row['friend'],
	);

	// Define the global bbcode bitfield, will be used to load bbcodes
	$bbcode_bitfield |= $row['bbcode_bitfield'];

	// Is a signature attached? Are we going to display it?
	if ($row['enable_sig'] && $config['allow_sig'] && $_CLASS['user']->optionget('viewsigs'))
	{
		$bbcode_bitfield |= $row['user_sig_bbcode_bitfield'];
	}

	// Cache various user specific data ... so we don't have to recompute
	// this each time the same user appears on this page
	if (!isset($user_cache[$poster_id]))
	{
		if ($poster_id == ANONYMOUS)
		{
			$user_cache[$poster_id] = array(
				'joined'		=> '',
				'posts'			=> '',
				'from'			=> '',
				'karma'                 => 0,
				'karma_img'             => '',
				'sig'					=> '',
				'sig_bbcode_uid'		=> '',
				'sig_bbcode_bitfield'	=> '',

				'online'		=> false,
				'avatar'		=> '',
				'rank_title'	=> '',
				'rank_image'	=> '',
				'sig'			=> '',
				'posts'			=> '',
				'profile'		=> '',
				'pm'			=> '',
				'email'			=> '',
				'www'			=> '',
				'icq_status_img'=> '',
				'icq'			=> '',
				'aim'			=> '',
				'msn'			=> '',
				'yim'			=> '',
				'jabber'		=> '',
				'search'		=> '',
				'username'		=> ($row['user_colour']) ? '<span style="color:#' . $row['user_colour'] . '">' . $poster . '</span>' : $poster
			);
		}
		else
		{
			$user_sig = '';

			if ($row['enable_sig'] && $config['allow_sig'] && $_CLASS['user']->optionget('viewsigs'))
			{
				$user_sig = $row['user_sig'];
			}

			$id_cache[] = $poster_id;

			$user_cache[$poster_id] = array(
				'joined'		=> $_CLASS['user']->format_date($row['user_regdate'], $_CLASS['user']->lang['DATE_FORMAT']),
				'posts'			=> (!empty($row['user_posts'])) ? $row['user_posts'] : '',
				'from'			=> (!empty($row['user_from'])) ? $row['user_from'] : '',
				'karma'			=> ($config['enable_karma']) ? $row['user_karma'] : 0, 
				'karma_img'		=> ($config['enable_karma']) ? $_CLASS['user']->img('karma_center', $_CLASS['user']->lang['KARMA'][$row['user_karma']], false, (int) $row['user_karma']) : '',
				'sig'					=> $user_sig,
				'sig_bbcode_uid'		=> (!empty($row['user_sig_bbcode_uid'])) ? $row['user_sig_bbcode_uid']  : '',
				'sig_bbcode_bitfield'	=> (!empty($row['user_sig_bbcode_bitfield'])) ? $row['user_sig_bbcode_bitfield']  : '',

				'viewonline'	=> $row['user_allow_viewonline'], 

				'avatar'		=> '',

				'online'		=> false,
				'profile'		=> getlink("Members_List&amp;mode=viewprofile&amp;u=$poster_id"),
				'www'			=> $row['user_website'],
				'aim'			=> ($row['user_aim']) ? getlink('Members_List&amp;mode=contact&amp;action=aim&amp;u='.$poster_id) : '',
				'msn'			=> ($row['user_msnm']) ? getlink('Members_List&amp;mode=contact&amp;action=msnm&amp;u='.$poster_id) : '',
				'yim'			=> ($row['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . $row['user_yim'] . '&.src=pg' : '',
				'jabber'		=> ($row['user_jabber']) ? getlink('Members_List&amp;mode=contact&amp;action=jabber&amp;u='.$poster_id) : '',
				'search'		=> ($_CLASS['auth']->acl_get('u_search')) ? getlink('Forums&amp;file=search&amp;search_author=' . urlencode($row['username']) .'&amp;showresults=posts') : '',
				'username'		=> ($row['user_colour']) ? '<span style="color:#' . $row['user_colour'] . '">' . $poster . '</span>' : $poster
			);

			if ($row['user_avatar'] && $_CLASS['user']->optionget('viewavatars'))
			{
				$avatar_img = '';
				switch ($row['user_avatar_type'])
				{
					case AVATAR_UPLOAD:
						$avatar_img = $config['avatar_path'] . '/';
						break;
					case AVATAR_GALLERY:
						$avatar_img = $config['avatar_gallery_path'] . '/';
						break;
				}
				$avatar_img .= $row['user_avatar'];

				$user_cache[$poster_id]['avatar'] = '<img src="' . $avatar_img . '" width="' . $row['user_avatar_width'] . '" height="' . $row['user_avatar_height'] . '" border="0" alt="" />';
			}

			if (!empty($row['user_rank']))
			{
				$user_cache[$poster_id]['rank_title'] = (isset($ranks['special'][$row['user_rank']])) ? $ranks['special'][$row['user_rank']]['rank_title'] : '';
				$user_cache[$poster_id]['rank_image'] = (!empty($ranks['special'][$row['user_rank']]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$row['user_rank']]['rank_image'] . '" border="0" alt="' . $ranks['special'][$row['user_rank']]['rank_title'] . '" title="' . $ranks['special'][$row['user_rank']]['rank_title'] . '" /><br />' : '';
			}
			else
			{
				if (isset($ranks['normal']) && sizeof($ranks['normal']))
				{
					foreach ($ranks['normal'] as $rank)
					{
						if ($row['user_posts'] >= $rank['rank_min'])
						{
							$user_cache[$poster_id]['rank_title'] = $rank['rank_title'];
							$user_cache[$poster_id]['rank_image'] = (!empty($rank['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $rank['rank_image'] . '" border="0" alt="' . $rank['rank_title'] . '" title="' . $rank['rank_title'] . '" /><br />' : '';
							break;
						}
					}
				}
				else
				{
					$user_cache[$poster_id]['rank_title'] = '';
					$user_cache[$poster_id]['rank_image'] = '';
				}
			}

			if (!empty($row['user_allow_viewemail']) || $_CLASS['auth']->acl_get('a_email'))
			{
				$user_cache[$poster_id]['email'] = ($config['board_email_form'] && $config['email_enable']) ? getlink('Members_List&amp;mode=email&amp;u=' . $poster_id): 'mailto:' . $row['user_email'];
			}
			else
			{
				$user_cache[$poster_id]['email'] = '';
			}

			if (!empty($row['user_icq']))
			{
				$user_cache[$poster_id]['icq'] =  getlink('Members_List&amp;mode=contact&amp;action=icq&amp;u='.$poster_id);
				$user_cache[$poster_id]['icq_status_img'] = '<img src="http://web.icq.com/whitepages/online?icq=' . $row['user_icq'] . '&amp;img=5" width="18" height="18" border="0" />';
			}
			else
			{
				$user_cache[$poster_id]['icq_status_img'] = '';
				$user_cache[$poster_id]['icq'] = '';
			}
		}
	}
}
while ($row = $_CLASS['db']->sql_fetchrow($result));
$_CLASS['db']->sql_freeresult($result);

// Load custom profile fields
if ($config['load_cpf_viewtopic'])
{
	requireOnce('includes/forums/functions_profile_fields.' . $phpEx);
	$cp = new custom_profile();
	// Grab all profile fields from users in id cache for later use - similar to the poster cache
	$profile_fields_cache = $cp->generate_profile_fields_template('grab', $id_cache);
}


// Generate online information for user
if ($config['load_onlinetrack'] && sizeof($id_cache))
{
	$sql = 'SELECT session_user_id, MAX(session_time) as online_time, MIN(session_viewonline) AS viewonline
		FROM ' . SESSIONS_TABLE . ' 
		WHERE session_user_id IN (' . implode(', ', $id_cache) . ')
		GROUP BY session_user_id';
	$result = $_CLASS['db']->sql_query($sql);

	$update_time = $config['load_online_time'] * 60;
	while ($row = $_CLASS['db']->sql_fetchrow($result))
	{
		$user_cache[$row['session_user_id']]['online'] = (time() - $update_time < $row['online_time'] && (($row['viewonline'] && $user_cache[$row['session_user_id']]['viewonline']) || $_CLASS['auth']->acl_get('u_viewonline'))) ? true : false;
	}
}
unset($id_cache);

// Pull attachment data
if (sizeof($attach_list))
{
	if ($_CLASS['auth']->acl_gets('f_download', 'u_download', $forum_id))
	{
		include('includes/forums/functions_display.' . $phpEx);

		$sql = 'SELECT * 
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE post_msg_id IN (' . implode(', ', $attach_list) . ')
				AND in_message = 0
			ORDER BY filetime ' . ((!$config['display_order']) ? 'DESC' : 'ASC') . ', post_msg_id ASC';
		$result = $_CLASS['db']->sql_query($sql);

		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$attachments[$row['post_msg_id']][] = $row;
		}
		$_CLASS['db']->sql_freeresult($result);

		// No attachments exist, but post table thinks they do so go ahead and reset post_attach flags
		if (!sizeof($attachments))
		{
			$sql = 'UPDATE ' . POSTS_TABLE . ' 
				SET post_attachment = 0 
				WHERE post_id IN (' . implode(', ', $attach_list) . ')';
			$_CLASS['db']->sql_query($sql);

			// We need to update the topic indicator too if the complete topic is now without an attachment
			if (sizeof($rowset) != $total_posts)
			{
				// Not all posts are displayed so we query the db to find if there's any attachment for this topic
				$sql = 'SELECT a.post_msg_id as post_id
					FROM ' . ATTACHMENTS_TABLE . ' a, ' . POSTS_TABLE . " p
					WHERE p.topic_id = $topic_id
						AND p.post_approved = 1
						AND p.topic_id = a.topic_id";
				$result = $_CLASS['db']->sql_query_limit($sql, 1);

				if (!$_CLASS['db']->sql_fetchrow($result))
				{
					$sql = 'UPDATE ' . TOPICS_TABLE . " 
						SET topic_attachment = 0 
						WHERE topic_id = $topic_id";
					$_CLASS['db']->sql_query($sql);
				}
			}
			else
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . " 
					SET topic_attachment = 0 
					WHERE topic_id = $topic_id";
				$_CLASS['db']->sql_query($sql);
			}
		}
		else if ($has_attachments && !$topic_data['topic_attachment'])
		{
			// Topic has approved attachments but its flag is wrong
			$sql = 'UPDATE ' . TOPICS_TABLE . " 
				SET topic_attachment = 1 
				WHERE topic_id = $topic_id";
			$_CLASS['db']->sql_query($sql);

			$topic_data['topic_attachment'] = 1;
		}
	}
	else
	{
		$display_notice = true;
	}
}

// Instantiate BBCode if need be
if ($bbcode_bitfield)
{
	require_once('includes/forums/bbcode.'.$phpEx);
	$bbcode = new bbcode($bbcode_bitfield);
}

$i_total = sizeof($rowset) - 1;
$prev_post_id = '';

$_CLASS['template']->assign(array(
	'S_NUM_POSTS' => sizeof($post_list))
);

// Output the posts
//foreach ($rowset as $i => $row)
for ($i = 0, $end = sizeof($post_list); $i < $end; ++$i)
{
	$row =& $rowset[$post_list[$i]];
	$poster_id = $row['user_id'];

	// Three situations can prevent a post being display:
	// i)   The posters karma is below the minimum of the user ... not in 2.2.x
	// ii)  The poster is on the users ignore list
	// iii) The post was made in a codepage different from the users
	if (isset($row['below_karma']))
	{
		$_CLASS['template']->assign_vars_array('postrow', array(
			'S_IGNORE_POST' => true, 
			'S_ROW_COUNT'	=> $i,

			'L_IGNORE_POST' => sprintf($_CLASS['user']->lang['POST_BELOW_KARMA'], $row['poster'], $row['user_karma'], '<a href="'.getlink("forums&amp;file=viewtopic&amp;f=$forum_id&amp;p=" . $row['post_id'] . '&amp;view=show#' . $row['post_id']) . '">', '</a>'))
		);

		continue;
	}
	elseif (isset($row['foe']) && $row['foe'])
	{
		$_CLASS['template']->assign_vars_array('postrow', array(
			'S_IGNORE_POST' => true, 
			'L_IGNORE_POST' => sprintf($_CLASS['user']->lang['POST_BY_FOE'], $row['poster'], '<a href="'.getlink("forums&amp;file=viewtopic&amp;f=$forum_id&amp;p=" . $row['post_id'] . '&amp;view=show#' . $row['post_id']) . '">', '</a>'))
		);

		continue;
	}
	else if ($row['post_encoding'] != $_CLASS['user']->lang['ENCODING'])
	{
		if ($view == 'encoding' && $post_id == $row['post_id'])
		{
			$force_encoding = $row['post_encoding'];
		}
		else
		{
			$_CLASS['template']->assign_vars_array('postrow', array(
				'S_IGNORE_POST'	=> true, 
				'L_IGNORE_POST'	=> sprintf($_CLASS['user']->lang['POST_ENCODING'], $row['poster'], '<a href="'.getlink('Forums&amp;file=viewtopic&amp;p=' . $row['post_id'] . '&amp;view=encoding#' . $row['post_id']) . '">', '</a>'))
			);

			continue;
		}
	}

	// End signature parsing, only if needed
	if ($user_cache[$poster_id]['sig'] && empty($user_cache[$poster_id]['sig_parsed']))
	{
		if ($user_cache[$poster_id]['sig_bbcode_bitfield'])
		{
			$bbcode->bbcode_second_pass($user_cache[$poster_id]['sig'], $user_cache[$poster_id]['sig_bbcode_uid'], $user_cache[$poster_id]['sig_bbcode_bitfield']);
		}

		$user_cache[$poster_id]['sig'] = smilie_text($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig'] = str_replace("\n", '<br />', censor_text($user_cache[$poster_id]['sig']));
		$user_cache[$poster_id]['sig_parsed'] = TRUE;
	}

	// Parse the message and subject
	$message = $row['post_text'];

	// If the board has HTML off but the post has HTML on then we process it, else leave it alone
	if (!$_CLASS['auth']->acl_get('f_html', $forum_id) && $row['enable_html'])
	{
		if ($row['enable_html'] && $_CLASS['auth']->acl_get('f_bbcode', $forum_id))
		{
			$message = preg_replace('#(<!\-\- h \-\-><)([\/]?.*?)(><!\-\- h \-\->)#is', "&lt;\\2&gt;", $message);
		}
	}

	// Second parse bbcode here
	if ($row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
	}

	// Always process smilies after parsing bbcodes
	$message = smilie_text($message);

	if (isset($attachments[$row['post_id']]) && sizeof($attachments[$row['post_id']]))
	{
		$unset_attachments = parse_inline_attachments($message, $attachments[$row['post_id']], $update_count, $forum_id);

		// Needed to let not display the inlined attachments at the end of the post again
		foreach ($unset_attachments as $index)
		{
			unset($attachments[$row['post_id']][$index]);
		}
	}

	// Highlight active words (primarily for search)
	if ($highlight_match)
	{
		// This was shamelessly 'borrowed' from volker at multiartstudio dot de
		// via php.net's annotated manual
		$message = str_replace('\"', '"', substr(preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "preg_replace('#\b(" . $highlight_match . ")\b#i', '<span class=\"posthilit\">\\\\1</span>', '\\0')", '>' . $message . '<'), 1, -1));
	}

	if ($row['enable_html'] && $_CLASS['auth']->acl_get('f_html', $forum_id))
	{
		// Remove Comments from post content?
		$message = preg_replace('#<!\-\-(.*?)\-\->#is', '', $message);
	}
	
	// Replace naughty words such as farty pants
	$row['post_subject'] = censor_text($row['post_subject']);
	$message = str_replace("\n", '<br />', censor_text($message));

	// Editing information
	if (($row['post_edit_count'] && $config['display_last_edited']) || $row['post_edit_reason'])
	{
		// Get usernames for all following posts if not already stored
		if (!sizeof($post_edit_list) && $row['post_edit_reason'])
		{
			// Remove all post_ids already parsed (we do not have to check them)
			$post_storage_list = array_slice($post_list, $i);

			$sql = 'SELECT DISTINCT u.user_id, u.username, u.user_colour 
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE p.post_id IN (' . implode(', ', $post_storage_list) . ")
					AND p.post_edit_count <> 0
					AND p.post_edit_user <> 0
					AND p.post_edit_reason <> ''
					AND p.post_edit_user = u.user_id";
			$result2 = $_CLASS['db']->sql_query($sql);
			while ($user_edit_row = $_CLASS['db']->sql_fetchrow($result2))
			{
				$post_edit_list[$user_edit_row['user_id']] = $user_edit_row;
			}
			$_CLASS['db']->sql_freeresult($result2);
			
			unset($post_storage_list);
		}
		$l_edit_time_total = ($row['post_edit_count'] == 1) ? $_CLASS['user']->lang['EDITED_TIME_TOTAL'] : $_CLASS['user']->lang['EDITED_TIMES_TOTAL'];

		$user_edit_row = ($row['post_edit_reason']) ? $post_edit_list[$row['post_edit_user']] : array();

		$l_edited_by = sprintf($l_edit_time_total, (!$row['post_edit_user']) ? $row['poster'] : (($user_edit_row['user_colour']) ? '<span style="color:#' . $user_edit_row['user_colour'] . '">' . $user_edit_row['username'] . '</span>' : $user_edit_row['username']), $_CLASS['user']->format_date($row['post_edit_time']), $row['post_edit_count']);
	}
	else
	{
		$l_edited_by = '';
	}

	// Bump information
	if ($topic_bumped && $row['post_id'] == $topic_last_post_id)
	{
		// It is safe to grab the username from the user cache array, we are at the last 
		// post and only the topic poster and last poster are allowed to bump
		$l_bumped_by = '<br /><br />' . sprintf($_CLASS['user']->lang['BUMPED_BY'], $user_cache[$topic_bumper]['username'], $_CLASS['user']->format_date($topic_last_post_time));
	}
	else
	{
		$l_bumped_by = '';
	}

	$cp_row = array();

	if ($config['load_cpf_viewtopic'])
	{
		$cp_row = (isset($profile_fields_cache[$poster_id])) ? $cp->generate_profile_fields_template('show', false, $profile_fields_cache[$poster_id]) : array();
	}

	$postrow = array(
		'POSTER_NAME' 	=> $row['poster'],
		'POSTER_RANK' 	=> $user_cache[$poster_id]['rank_title'],
		'RANK_IMAGE' 	=> $user_cache[$poster_id]['rank_image'],
		'POSTER_JOINED' => $user_cache[$poster_id]['joined'],
		'POSTER_POSTS' 	=> $user_cache[$poster_id]['posts'],
		'POSTER_FROM' 	=> $user_cache[$poster_id]['from'],
		'POSTER_AVATAR' => $user_cache[$poster_id]['avatar'],
		'POSTER_KARMA'	=> $user_cache[$poster_id]['karma'],
		
		'POST_DATE' 	=> $_CLASS['user']->format_date($row['post_time']),
		'POST_SUBJECT' 	=> $row['post_subject'],
		'MESSAGE' 		=> $message,
		'SIGNATURE' 	=> ($row['enable_sig']) ? $user_cache[$poster_id]['sig'] : '',
		'EDITED_MESSAGE'=> $l_edited_by,
		'EDIT_REASON'	=> $row['post_edit_reason'],
		'BUMPED_MESSAGE'=> $l_bumped_by,

		'MINI_POST_IMG' => ($_CLASS['user']->data['user_id'] != ANONYMOUS && $row['post_time'] > $_CLASS['user']->data['user_lastvisit'] && $row['post_time'] > $topic_last_read) ? $_CLASS['user']->img('icon_post_new', 'NEW_POST') : $_CLASS['user']->img('icon_post', 'POST'),
		'POST_ICON_IMG' => (!empty($row['icon_id'])) ? '<img src="' . $config['icons_path'] . '/' . $icons[$row['icon_id']]['img'] . '" width="' . $icons[$row['icon_id']]['width'] . '" height="' . $icons[$row['icon_id']]['height'] . '" alt="" title="" />' : '',
		'ICQ_STATUS_IMG'	=> $user_cache[$poster_id]['icq_status_img'],
		'KARMA_IMG'			=> $user_cache[$poster_id]['karma_img'],

		'ONLINE_IMG'		=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? '' : (($user_cache[$poster_id]['online']) ? $_CLASS['user']->img('btn_online', 'ONLINE') : $_CLASS['user']->img('btn_offline', 'OFFLINE')), 

		'U_EDIT' 			=> (($_CLASS['user']->data['user_id'] == $poster_id && $_CLASS['auth']->acl_get('f_edit', $forum_id) && ($row['post_time'] > time() - $config['edit_time'] || !$config['edit_time'])) || $_CLASS['auth']->acl_get('m_edit', $forum_id)) ? getlink("Forums&amp;file=posting&amp;mode=edit&amp;f=$forum_id&amp;p=" . $row['post_id']) : '',
		'U_QUOTE' 			=> ($_CLASS['auth']->acl_get('f_quote', $forum_id)) ? getlink("Forums&amp;file=posting&amp;mode=quote&amp;f=$forum_id&amp;p=" . $row['post_id']) : '', 
		'U_INFO'            => ($_CLASS['auth']->acl_get('m_', $forum_id)) ? getlink('Forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id'], false, false) : '',
		'U_DELETE' 			=> (($_CLASS['user']->data['user_id'] == $poster_id && $_CLASS['auth']->acl_get('f_delete', $forum_id) && $topic_data['topic_last_post_id'] == $row['post_id'] && ($row['post_time'] > time() - $config['edit_time'] || !$config['edit_time'])) || $_CLASS['auth']->acl_get('m_delete', $forum_id)) ? getlink("Forums&amp;file=posting&amp;mode=delete&amp;f=$forum_id&amp;p=" . $row['post_id']) : '',

		'U_PROFILE' 		=> $user_cache[$poster_id]['profile'],
		'U_SEARCH' 			=> $user_cache[$poster_id]['search'],
		'U_PM' 				=> ($poster_id != ANONYMOUS) ? getlink('Control_Panel&amp;i=pm&amp;mode=compose&amp;action=quote&amp;q=1&amp;p=' . $row['post_id']) : '',
		'U_EMAIL' 			=> $user_cache[$poster_id]['email'],
		'U_WWW' 			=> $user_cache[$poster_id]['www'],
		'U_ICQ' 			=> $user_cache[$poster_id]['icq'],
		'U_AIM' 			=> $user_cache[$poster_id]['aim'],
		'U_MSN' 			=> $user_cache[$poster_id]['msn'],
		'U_YIM' 			=> $user_cache[$poster_id]['yim'],
		'U_JABBER'			=> $user_cache[$poster_id]['jabber'], 

		'U_RATE_GOOD'		=> getlink('Forums&amp;file=viewtopic&amp;rate=good&amp;p=' . $row['post_id']), 
		'U_RATE_BAD'		=> getlink('Forums&amp;file=viewtopic&amp;rate=bad&amp;p=' . $row['post_id']), 
		'U_REPORT'			=> getlink('Forums&amp;file=report&amp;p=' . $row['post_id']),
		'U_MCP_REPORT'		=> ($_CLASS['auth']->acl_gets('m_', 'a_', 'f_report', $forum_id)) ? getlink('Forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id']) : '',
		'U_MCP_APPROVE'		=> ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? getlink('Forums&amp;file=mcp&amp;i=queue&amp;mode=approve&amp;post_id_list[]=' . $row['post_id'], false, false) : '',
		'U_MCP_DETAILS'		=> ($_CLASS['auth']->acl_get('m_', $forum_id)) ? getlink('Forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id']) : '',
		'U_MINI_POST'		=> getlink('Forums&amp;file=viewtopic&amp;p=' . $row['post_id'] . '#' . $row['post_id']),
		'U_NEXT_POST_ID'	=> ($i < $i_total && isset($rowset[$i + 1])) ? $rowset[$i + 1]['post_id'] : '', 
		'U_PREV_POST_ID'	=> $prev_post_id, 
		'POST_ID'           => $row['post_id'],
		//'S_CAN_RATE'		=> ($_CLASS['auth']->acl_get('f_rate', $forum_id) && $row['post_approved'] && !$row['post_reported'] && $poster_id != $_CLASS['user']->data['user_id'] && $poster_id != ANONYMOUS && $config['enable_karma']) ? true : false, 
		'S_CAN_RATE'		=> true, 

		'S_HAS_ATTACHMENTS' => (!empty($attachments[$row['post_id']])) ? TRUE : FALSE,
		'S_POST_UNAPPROVED'	=> ($row['post_approved']) ? FALSE : TRUE,
		'S_POST_REPORTED'	=> ($row['post_reported'] && $_CLASS['auth']->acl_get('m_', $forum_id)) ? TRUE : FALSE,
		'S_DISPLAY_NOTICE'	=> ($display_notice && $row['post_attachment']) ? true : false, 
		'S_FRIEND'			=> ($row['friend']) ? true : false,
		'S_UNREAD'          => ($_CLASS['user']->data['user_id'] != ANONYMOUS && $row['post_time'] > $_CLASS['user']->data['user_lastvisit'] && $row['post_time'] > $topic_last_read) ? true : false,
		'S_FIRST_UNREAD'	=> ($unread_post_id == $row['post_id']) ? true : false,
		'S_CUSTOM_FIELDS'       => (sizeof($cp_row)) ? true : false
	);

	if (sizeof($cp_row))
	{
		$postrow = array_merge($postrow, $cp_row);
	}
	
	// Dump vars into template
	$_CLASS['template']->assign_vars_array('postrow', $postrow);
	
	// Display not already displayed Attachments for this post, we already parsed them. ;)
	if (isset($attachments[$row['post_id']]) && sizeof($attachments[$row['post_id']]))
	{
		foreach ($attachments[$row['post_id']] as $attachment)
		{
			$_CLASS['template']->assign_vars_array('attachment', array(
				'TOPIC'					=> $row['post_id'],
				'DISPLAY_ATTACHMENT'	=> $attachment)
			);
		}
	}

	$prev_post_id = $row['post_id'];

	unset($rowset[$i]);
	unset($attachments[$row['post_id']]);
}
unset($rowset);
unset($user_cache);

// Update topic view and if necessary attachment view counters ... but only
// if this is the first 'page view'
if (!preg_match("#&t=$topic_id#", $_CLASS['user']->data['session_page']))
{
	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
		WHERE topic_id = $topic_id";
	$_CLASS['db']->sql_query($sql);

	// Update the attachment download counts
	if (sizeof($update_count))
	{
		$sql = 'UPDATE ' . ATTACHMENTS_TABLE . ' 
			SET download_count = download_count + 1 
			WHERE attach_id IN (' . implode(', ', array_unique($update_count)) . ')';
		$_CLASS['db']->sql_query($sql);
	}
}

// Mark topics read
$mark_forum_id = ($topic_type == POST_GLOBAL) ? 0 : $forum_id;
markread('topic', $mark_forum_id, $topic_id, $row['post_time']);

// Change encoding if appropriate
if ($force_encoding)
{
	$_CLASS['user']->lang['ENCODING'] = $force_encoding;
}

/// lets assign those language that are needed///
$_CLASS['template']->assign(array(
	'L_MODERATORS'			=> $_CLASS['user']->lang['MODERATORS'],
	'L_AUTHOR'				=> $_CLASS['user']->lang['AUTHOR'],
	'L_PRINT_TOPIC'			=> $_CLASS['user']->lang['PRINT_TOPIC'],
	'L_EMAIL_TOPIC'			=> $_CLASS['user']->lang['EMAIL_TOPIC'],
	'L_BUMP_TOPIC'			=> $_CLASS['user']->lang['BUMP_TOPIC'],
	'L_VIEW_PREVIOUS_TOPIC'	=> $_CLASS['user']->lang['VIEW_PREVIOUS_TOPIC'],
	'L_VIEW_UNREAD_POST'	=> $_CLASS['user']->lang['VIEW_UNREAD_POST'],
	'L_VIEW_NEXT_TOPIC'		=> $_CLASS['user']->lang['VIEW_NEXT_TOPIC'],
	'L_USERNAME'			=> $_CLASS['user']->lang['USERNAME'],
	'L_POST_SUBJECT'		=> $_CLASS['user']->lang['POST_SUBJECT'],
	'L_GO'					=> $_CLASS['user']->lang['GO'],
	'L_MCP'					=> $_CLASS['user']->lang['MCP'],
	'L_SEARCH_FOR'			=> $_CLASS['user']->lang['SEARCH_FOR'],
	'L_MESSAGE'				=> $_CLASS['user']->lang['MESSAGE'],
	'L_POSTED'				=> $_CLASS['user']->lang['POSTED'],
	'L_JOINED'				=> $_CLASS['user']->lang['JOINED'],
	'L_POSTS'				=> $_CLASS['user']->lang['POSTS'],
	'L_LOCATION'			=> $_CLASS['user']->lang['LOCATION'],
	'L_RATE'				=> $_CLASS['user']->lang['RATE'],
	'L_RATE_GOOD'			=> $_CLASS['user']->lang['RATE_GOOD'],
	'L_RATE_BAD'			=> $_CLASS['user']->lang['RATE_BAD'],
	'L_POST_DETAILS'		=> $_CLASS['user']->lang['POST_DETAILS'],
	'L_DISPLAY_TOPICS'		=> $_CLASS['user']->lang['DISPLAY_TOPICS'],
	'L_QUICK_MOD'			=> $_CLASS['user']->lang['QUICK_MOD'],
	'L_JUMP_TO'				=> $_CLASS['user']->lang['JUMP_TO'],
	'L_SORT_BY'				=> $_CLASS['user']->lang['SORT_BY'],
	
	'L_TOTAL_VOTES'			=> $_CLASS['user']->lang['TOTAL_VOTES'],
	'L_POLL_VOTED_OPTION'	=> $_CLASS['user']->lang['POLL_VOTED_OPTION'],
	'L_ATTACHMENTS'			=> $_CLASS['user']->lang['ATTACHMENTS'],
	'L_DOWNLOAD_NOTICE'		=>	$_CLASS['user']->lang['DOWNLOAD_NOTICE'],
	
	'L_POST_UNAPPROVED'		=> $_CLASS['user']->lang['POST_UNAPPROVED'],
	'L_POST_REPORTED'		=> $_CLASS['user']->lang['POST_REPORTED'],
	
	'L_WHO_IS_ONLINE'		=> $_CLASS['user']->lang['JUMP_TO'],
	'L_VIEW_RESULTS'		=> $_CLASS['user']->lang['VIEW_RESULTS'],
	'L_DISPLAY_POSTS'		=> $_CLASS['user']->lang['DISPLAY_POSTS'],
	'L_SUBMIT_VOTE'			=> $_CLASS['user']->lang['SUBMIT_VOTE'])
);

if ($view == 'print')
{

	$_CLASS['template']->display('modules/Forums/viewtopic_print.html');
	script_close();
	die;
	
}

$_CLASS['display']->display_head($_CLASS['user']->lang['VIEW_TOPIC'] .' &gt; ' . $topic_title);

page_header();

make_jumpbox(getlink('Forums&amp;file=viewforum'), $forum_id);
$_CLASS['template']->display('modules/Forums/viewtopic_body.html');

$_CLASS['display']->display_footer();

?>