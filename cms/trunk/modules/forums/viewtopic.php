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
// $Id: viewtopic.php,v 1.350 2004/09/16 18:33:17 acydburn Exp $
//
// FILENAME  : viewtopic.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

if (!defined('VIPERAL'))
{
    die;
}

// Initial var setup
$topic_id	= request_var('t', 0);
$post_id	= request_var('p', 0);

// Do we have a topic or post id?
if (!$topic_id && !$post_id)
{
	trigger_error('NO_TOPIC');
}

$voted_id	= request_var('vote_id', array(0));
$start		= request_var('start', 0);
$view		= request_var('view', '');
$update		= request_var('update', false);
$hilit_words= request_var('hilit', '');

$update_mark = false;

$sort_days	= request_var('st', ((!empty($_CLASS['core_user']->data['user_post_show_days'])) ? $_CLASS['core_user']->data['user_post_show_days'] : 0));
$sort_key	= request_var('sk', ((!empty($_CLASS['core_user']->data['user_post_sortby_type'])) ? $_CLASS['core_user']->data['user_post_sortby_type'] : 't'));
$sort_dir	= request_var('sd', ((!empty($_CLASS['core_user']->data['user_post_sortby_dir'])) ? $_CLASS['core_user']->data['user_post_sortby_dir'] : 'a'));

if ($topic_id)
{
	$sql = 'SELECT forum_id, topic_last_post_time FROM ' . FORUMS_TOPICS_TABLE . '
		WHERE topic_id = '.$topic_id;
}
else
{
	$sql = 'SELECT forum_id, topic_id FROM ' . FORUMS_POSTS_TABLE . '
		WHERE post_id = '.$post_id;
}

$result = $_CLASS['core_db']->query($sql);
$row = $_CLASS['core_db']->fetch_row_assoc($result);
$_CLASS['core_db']->free_result($result);

if (!$row)
{
	trigger_error('NO_TOPIC');
}

$forum_id = $row['forum_id'];
$topic_id = ($topic_id) ? $topic_id : $row['topic_id'];
$topic_last_post_time = isset($row['topic_last_post_time']) ? $row['topic_last_post_time'] : false;

if ($view === 'next' || $view === 'previous')
{
	if ($view === 'next')
	{
		$sql_condition = '>';
		$sql_ordering = 'ASC';
	}
	else
	{
		$sql_condition = '<';
		$sql_ordering = 'DESC';
	}

	if (!$topic_last_post_time)
	{
		$sql = 'SELECT topic_last_post_time FROM ' . FORUMS_TOPICS_TABLE . '
					WHERE topic_id = '.$topic_id;

		$result = $_CLASS['core_db']->query($sql);
		list($topic_last_post_time) = $_CLASS['core_db']->fetch_row_num($result);
		$_CLASS['core_db']->free_result($result);
	}

	$sql = 'SELECT topic_id, forum_id
		FROM ' . FORUMS_TOPICS_TABLE . "
		 WHERE forum_id = $forum_id AND topic_last_post_time $sql_condition $topic_last_post_time"
		 . ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id) ? '' : ' AND topic_approved = 1') . "
		ORDER BY topic_last_post_time $sql_ordering";

	$result = $_CLASS['core_db']->query_limit($sql, 1);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$row)
	{
		$message = ($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS';
		trigger_error($message);
	}
	else
	{
		$topic_id = $row['topic_id'];
		$forum_id = $row['forum_id'];
	}
}

/*
if (!$post_id)
{
	$join_sql = "t.topic_id = $topic_id";
}
else
{
	if ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id))
	{
		$join_sql = (!$post_id) ? "t.topic_id = $topic_id" : "p.post_id = $post_id AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_id <= $post_id";
	}
	else
	{
		$join_sql = (!$post_id) ? "t.topic_id = $topic_id" : "p.post_id = $post_id AND p.post_approved = 1 AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_approved = 1 AND p2.post_id <= $post_id";
	}
}*/

$extra_fields = $join_sql_table = '';

if ($_CLASS['core_user']->is_user)
{
	$extra_fields .= ', tw.notify_status, tw.topic_id AS watch_topic';
	$join_sql_table .= ' LEFT JOIN ' . FORUMS_WATCH_TABLE . ' tw ON (tw.user_id = ' . $_CLASS['core_user']->data['user_id'] . " 
		AND tw.forum_id = $forum_id AND tw.topic_id IN ($topic_id, 0))";

	if ($config['allow_bookmarks'])
	{
		$extra_fields .= ', bm.order_id as bookmarked';
		$join_sql_table .= ' LEFT JOIN ' . FORUMS_BOOKMARKS_TABLE . ' bm ON (bm.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
			AND t.topic_id = bm.topic_id)';
	}
}

$sql = 'SELECT t.*, f.*' . $extra_fields . '
		FROM ' . FORUMS_FORUMS_TABLE . ' f, ' . FORUMS_TOPICS_TABLE . " t  $join_sql_table 
		WHERE t.topic_id = $topic_id
		AND f.forum_id = $forum_id";

$result = $_CLASS['core_db']->query($sql);
$topic_data = $_CLASS['core_db']->fetch_row_assoc($result);
$_CLASS['core_db']->free_result($result);

if (!$topic_data || $topic_data['forum_status'] == ITEM_DELETING || (!$topic_data['topic_approved'] && !$_CLASS['forums_auth']->acl_get('m_approve', $forum_id)))
{
	trigger_error('NO_TOPIC');
}

//Check for read permission
if (!$_CLASS['forums_auth']->acl_get('f_read', $forum_id))// && !$_CLASS['forums_auth']->acl_get('m_', $forum_id)
{
	if ($_CLASS['core_user']->data['user_id'] != ANONYMOUS)
	{
		trigger_error('SORRY_AUTH_READ');
	}

	login_box(array('explain' => $_CLASS['core_user']->lang['LOGIN_VIEWFORUM']));
}

// Forum is passworded ... check whether access has been granted to this
// user this session, if not show login box
if ($topic_data['forum_password'])
{
	login_forum_box($topic_data);
}

require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';

// Are we watching this topic?
if (!isset($topic_data['notify_status']))
{
	$topic_data['notify_status'] = null;
	$topic_data['watch_topic'] = false;
}

$s_watching_topic = watch_topic_forum('topic', $_CLASS['core_user']->data['user_id'], $forum_id, $topic_id, $topic_data['notify_status'], $start);

// Bookmarks
if ($config['allow_bookmarks'] && $_CLASS['core_user']->is_user && request_var('bookmark', 0))
{
	if (!$topic_data['bookmarked'])
	{
		$sql = 'INSERT INTO ' . FORUMS_BOOKMARKS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'user_id'	=> $_CLASS['core_user']->data['user_id'],
			'topic_id'	=> $topic_id,
			'order_id'	=> 0
		));
		$_CLASS['core_db']->query($sql);

		$where_sql = '';
		$sign = '+';
	}
	else
	{
		$sql = 'DELETE FROM ' . FORUMS_BOOKMARKS_TABLE . ' 
			WHERE user_id = '.$_CLASS['core_user']->data['user_id'].'
				AND order_id = '.$topic_data['bookmarked'];
		$_CLASS['core_db']->query($sql);
	
		// Works because of current order_id selected as bookmark value (please do not change because of simplicity)
		$where_sql = " AND order_id > ".$topic_data['bookmarked'];
		$sign = '-';
	}

	// Re-Sort Bookmarks
	$sql = 'UPDATE ' . FORUMS_BOOKMARKS_TABLE . "
		SET order_id = order_id $sign 1
			WHERE user_id = {$_CLASS['core_user']->data['user_id']}
			$where_sql";
	$_CLASS['core_db']->query($sql);

	$topic_data['bookmarked'] = ($topic_data['bookmarked']) ? false : true;
//	$_CLASS['core_display']->meta_refresh(3, generate_link($viewtopic_url, false));
//	$message = (($topic_data['bookmarked']) ? $_CLASS['core_user']->lang['BOOKMARK_REMOVED'] : $_CLASS['core_user']->lang['BOOKMARK_ADDED']) . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="' . generate_link($viewtopic_url, false) . '">', '</a>');
//	trigger_error($message);
}

// We make this check here because the correct forum_id is determined
$topic_replies = ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? (int) $topic_data['topic_replies_real'] : (int) $topic_data['topic_replies'];

$topic_last_read = topic_last_read($topic_id, $forum_id);

// Check sticky/announcement time limit
if (($topic_data['topic_type'] == POST_STICKY || $topic_data['topic_type'] == POST_ANNOUNCE) && $topic_data['topic_time_limit'] && $topic_data['topic_time'] + $topic_data['topic_time_limit'] < $_CLASS['core_user']->time)
{
	$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . ' 
		SET topic_type = ' . POST_NORMAL . ', topic_time_limit = 0
		WHERE topic_id = ' . $topic_id;
	$_CLASS['core_db']->query($sql);

	$topic_data['topic_type'] = POST_NORMAL;
	$topic_data['topic_time_limit'] = 0;
}

$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_lang('viewtopic');
$_CLASS['core_user']->add_img();

// This is for determining where we are (page)
// What is start equal to?
if ($post_id)
{
	/**
	* @todo adjust for using post_time? Generally adjust query... it is not called very often though
	*/
	$sql = 'SELECT COUNT(post_id) AS prev_posts
		FROM ' . FORUMS_POSTS_TABLE . "
		WHERE topic_id = {$topic_data['topic_id']}
			" . ((!$_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? 'AND post_approved = 1' : '') . "
			AND " . (($sort_dir === 'd') ?  "post_id >= $post_id" : "post_id <= $post_id");
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$topic_data['prev_posts'] = $row['prev_posts'];
	unset($row);

	$start = floor(($topic_data['prev_posts'] - 1) / $config['posts_per_page']) * $config['posts_per_page'];
}

// Post ordering options
$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_POSTS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);

$sort_by_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 's' => $_CLASS['core_user']->lang['SUBJECT']);
$sort_by_sql = array('a' => 'u.username', 't' => 'p.post_id', 's' => 'p.post_subject');

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

// Obtain correct post count and ordering SQL if user has
// requested anything different
if ($sort_days)
{
	$min_post_time = $_CLASS['core_user']->time - ($sort_days * 86400);

	$sql = 'SELECT COUNT(post_id) AS num_posts
		FROM ' . FORUMS_POSTS_TABLE . "
		WHERE topic_id = $topic_id
			AND post_time >= $min_post_time
		" . (($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? '' : 'AND post_approved = 1');

	$result = $_CLASS['core_db']->query($sql);
	$total_posts = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row['num_posts'] : 0;
	$_CLASS['core_db']->free_result($result);

	$limit_posts_time = "AND p.post_time >= $min_post_time ";

	if (isset($_POST['sort']))
	{
		$start = 0;
	}
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
			$highlight_match .= (($highlight_match != '') ? '|' : '') . str_replace('*', '\w*?', preg_quote($word, '#'));
		}
	}

	$highlight = urlencode($hilit_words);
}

// Make sure $start is set to the last page if it exceeds the amount
if ($start < 0 || $start > $total_posts)
{
	$start = ($start < 0) ? 0 : floor(($total_posts - 1) / $config['posts_per_page']) * $config['posts_per_page'];
}

// General Viewtopic URL for return links
$viewtopic_url = "forums&amp;file=viewtopic&amp;t=$topic_id&amp;start=$start&amp;$u_sort_param" . (($highlight_match) ? "&amp;hilit=$highlight" : '');

// Grab ranks
$ranks = obtain_ranks();

// Grab icons
$icons =  obtain_icons();

// Moderators
$forum_moderators = get_moderators($forum_id);

// Generate Navigation links
generate_forum_nav($topic_data);

// Generate Forum Rules
generate_forum_rules($topic_data);

gen_forum_auth_level('topic', $forum_id, $topic_data['forum_status']);

// Does this topic contain a poll?
if (!empty($topic_data['poll_start']))
{
	$sql = 'SELECT o.*, p.bbcode_bitfield, p.bbcode_uid
		FROM ' . FORUMS_POLL_OPTIONS_TABLE . ' o, ' . FORUMS_POSTS_TABLE . " p
		WHERE o.topic_id = $topic_id 
			AND p.post_id = {$topic_data['topic_first_post_id']}
			AND p.topic_id = o.topic_id
		ORDER BY o.poll_option_id";
	$result = $_CLASS['core_db']->query($sql);

	$poll_info = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$poll_info[] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	$cur_voted_id = array();
	if ($_CLASS['core_user']->is_user)
	{
		$sql = 'SELECT poll_option_id
			FROM ' . FORUMS_POLL_VOTES_TABLE . '
			WHERE topic_id = ' . $topic_id . '
				AND vote_user_id = ' . $_CLASS['core_user']->data['user_id'];
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$cur_voted_id[] = $row['poll_option_id'];
		}
		$_CLASS['core_db']->free_result($result);
	}
	else
	{
		// Cookie based guest tracking ... I don't like this but hum ho
		// it's oft requested. This relies on "nice" users who don't feel
		// the need to delete cookies to mess with results.
		if (isset($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_poll_' . $topic_id]))
		{
			$cur_voted_id = explode(',', $_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_poll_' . $topic_id]);
		}
	}

	$s_can_vote = (((empty($cur_voted_id) && $_CLASS['forums_auth']->acl_get('f_vote', $forum_id)) || 
		($_CLASS['forums_auth']->acl_get('f_votechg', $forum_id) && $topic_data['poll_vote_change'])) &&
		(($topic_data['poll_length'] != 0 && $topic_data['poll_start'] + $topic_data['poll_length'] > $_CLASS['core_user']->time) || $topic_data['poll_length'] == 0) &&
		$topic_data['topic_status'] != ITEM_LOCKED && 
		$topic_data['forum_status'] != ITEM_LOCKED) ? true : false;
		
	$s_display_results = (!$s_can_vote || ($s_can_vote && !empty($cur_voted_id)) || $view == 'viewpoll') ? true : false;
		
	if ($update && $s_can_vote)
	{
		if (empty($voted_id) || !empty($voted_id) > $topic_data['poll_max_options'])
		{
			$_CLASS['core_display']->meta_refresh(5, generate_link("forums&amp;file=viewtopic&amp;t=$topic_id"));

			$message = empty($voted_id) ? 'NO_VOTE_OPTION' : 'TOO_MANY_VOTE_OPTIONS';
			$message = $_CLASS['core_user']->lang[$message] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;t=$topic_id").'">', '</a>');
			trigger_error($message);
		}

		$sql_ary = array();

		foreach ($voted_id as $option)
		{
			if (in_array($option, $cur_voted_id))
			{
				continue;
			}

			$sql = 'UPDATE ' . FORUMS_POLL_OPTIONS_TABLE . '
				SET poll_option_total = poll_option_total + 1
				WHERE poll_option_id = ' . (int) $option . '
					AND topic_id = ' . (int) $topic_id;
			$_CLASS['core_db']->query($sql);

			if ($_CLASS['core_user']->is_user)
			{
				$sql_ary[] = array(
					'topic_id'			=> (int) $topic_id,
					'poll_option_id'	=> (int) $option,
					'vote_user_id'		=> (int) $_CLASS['core_user']->data['user_id'],
					'vote_user_ip'		=> (string) $_CLASS['core_user']->ip,
				);

				$_CLASS['core_db']->query($sql);
			}
		}

		if (!empty($sql_ary))
		{
			$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_ary, FORUMS_POLL_VOTES_TABLE);
			unset($sql_ary);
		}

		foreach ($cur_voted_id as $option)
		{
			if (!in_array($option, $voted_id))
			{
				$sql = 'UPDATE ' . FORUMS_POLL_OPTIONS_TABLE . ' 
					SET poll_option_total = poll_option_total - 1
					WHERE poll_option_id = ' . (int) $option . '
						AND topic_id = ' . (int) $topic_id;
				$_CLASS['core_db']->query($sql);

				if ($_CLASS['core_user']->is_user)
				{
					$sql = 'DELETE FROM ' . FORUMS_POLL_VOTES_TABLE . ' 
						WHERE topic_id = ' . (int) $topic_id . '
							AND poll_option_id = ' . (int) $option . '
							AND vote_user_id = ' . (int) $_CLASS['core_user']->data['user_id'];
					$_CLASS['core_db']->query($sql);
				}
			}
		}

		if ($_CLASS['core_user']->data['user_id'] == ANONYMOUS && !$_CLASS['core_user']->is_bot)
		{
			$_CLASS['core_user']->set_cookie('poll_' . $topic_id, implode(',', $voted_id), $_CLASS['core_user']->time + 31536000);
		}

		$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . ' 
			SET poll_last_vote = ' . $_CLASS['core_user']->time . ' 
			WHERE topic_id =' . (int)  $topic_id;
			//, topic_last_post_time = ' . $_CLASS['core_user']->time . " -- for bumping topics with new votes, ignore for now
		$_CLASS['core_db']->query($sql);

		$_CLASS['core_display']->meta_refresh(5, generate_link("forums&amp;file=viewtopic&amp;t=$topic_id"));

		$message = $_CLASS['core_user']->lang['VOTE_SUBMITTED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;t=$topic_id").'">', '</a>');
		trigger_error($message);
	}

	$poll_total = 0;
	foreach ($poll_info as $poll_option)
	{
		$poll_total += $poll_option['poll_option_total'];
	}

	if ($poll_info[0]['bbcode_bitfield'])
	{
		require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';
		$poll_bbcode = new bbcode();
	}
	else
	{
		$poll_bbcode = false;
	}

	$count = count($poll_info);

	for ($i = 0; $i < $count; $i++)
	{
		$poll_info[$i]['poll_option_text'] = censor_text($poll_info[$i]['poll_option_text']);
		$poll_info[$i]['poll_option_text'] = str_replace("\n", '<br />', $poll_info[$i]['poll_option_text']);

		if ($poll_bbcode !== false)
		{
			$poll_bbcode->bbcode_second_pass($poll_info[$i]['poll_option_text'], $poll_info[$i]['bbcode_uid'], $poll_option['bbcode_bitfield']);
		}
		$poll_info[$i]['poll_option_text'] = smiley_text($poll_info[$i]['poll_option_text']);
	}

	$topic_data['poll_title'] = censor_text($topic_data['poll_title']);
	$topic_data['poll_title'] = str_replace("\n", '<br />', $topic_data['poll_title']);

	if ($poll_bbcode !== false)
	{
		$poll_bbcode->bbcode_second_pass($topic_data['poll_title'], $poll_info[0]['bbcode_uid'], $poll_info[0]['bbcode_bitfield']);
	}
	$topic_data['poll_title'] = smiley_text($topic_data['poll_title']);

	unset($poll_bbcode);
	
	foreach ($poll_info as $poll_option)
	{
		$option_pct = ($poll_total > 0) ? $poll_option['poll_option_total'] / $poll_total : 0;
		$option_pct_txt = sprintf("%.1d%%", ($option_pct * 100));

		$_CLASS['core_template']->assign_vars_array('poll_option', array(
			'POLL_OPTION_ID' 		=> $poll_option['poll_option_id'],
			'POLL_OPTION_CAPTION' 	=> $poll_option['poll_option_text'],
			'POLL_OPTION_RESULT' 	=> $poll_option['poll_option_total'],
			'POLL_OPTION_PERCENT' 	=> $option_pct_txt,
			'POLL_OPTION_PCT'		=> round($option_pct * 100),
			'POLL_OPTION_IMG' 		=> $_CLASS['core_user']->img('poll_center', $option_pct_txt, round($option_pct * 250)), 
			'POLL_OPTION_VOTED'		=> (in_array($poll_option['poll_option_id'], $cur_voted_id)) ? true : false)
		);
	}

	$poll_end = $topic_data['poll_length'] + $topic_data['poll_start'];

	$_CLASS['core_template']->assign_array(array(
		'POLL_QUESTION'		=> $topic_data['poll_title'],
		'TOTAL_VOTES' 		=> $poll_total,
		'POLL_LEFT_CAP_IMG'	=> $_CLASS['core_user']->img('poll_left'),
		'POLL_RIGHT_CAP_IMG'=> $_CLASS['core_user']->img('poll_right'),

		'L_MAX_VOTES'		=> ($topic_data['poll_max_options'] == 1) ? $_CLASS['core_user']->lang['MAX_OPTION_SELECT'] : sprintf($_CLASS['core_user']->lang['MAX_OPTIONS_SELECT'], $topic_data['poll_max_options']), 
		'L_POLL_LENGTH'		=> ($topic_data['poll_length']) ? sprintf($_CLASS['core_user']->lang[($poll_end > $_CLASS['core_user']->time) ? 'POLL_RUN_TILL' : 'POLL_ENDED_AT'], $_CLASS['core_user']->format_date($poll_end)) : '',

		'S_HAS_POLL'		=> true, 
		'S_CAN_VOTE'		=> $s_can_vote, 
		'S_DISPLAY_RESULTS'	=> $s_display_results,
		'S_IS_MULTI_CHOICE'	=> ($topic_data['poll_max_options'] > 1) ? true : false,
		'S_POLL_ACTION'		=> generate_link($viewtopic_url, false),

		'U_VIEW_RESULTS'	=> generate_link($viewtopic_url . '&amp;view=viewpoll', false))
	);

	unset($poll_end, $poll_info, $voted_id);
}
else
{
	$_CLASS['core_template']->assign('S_HAS_POLL', false); 
}

// If the user is trying to reach the second half of the topic, fetch it starting from the end
$store_reverse = false;
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
$bbcode_bitfield = $i = $i_total = 0;

// Go ahead and pull all data for this topic
$sql = 'SELECT p.post_id
	FROM ' . FORUMS_POSTS_TABLE . ' p' . (($sort_by_sql[$sort_key]{0} == 'u') ? ', ' . USERS_TABLE . ' u': '') . "
	WHERE p.topic_id = $topic_id
		" . ((!$_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . "
		" . (($sort_by_sql[$sort_key]{0} == 'u') ? 'AND u.user_id = p.poster_id': '') . "
		$limit_posts_time
	ORDER BY $sql_sort_order";
$result = $_CLASS['core_db']->query_limit($sql, $sql_limit, $sql_start);

$i = ($store_reverse) ? $sql_limit - 1 : 0;

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$post_list[$i] = $row['post_id'];
	($store_reverse) ? --$i : ++$i;
}

$_CLASS['core_db']->free_result($result);

if (empty($post_list))
{
	trigger_error($_CLASS['core_user']->lang['NO_TOPIC']);
}

$sql = 'SELECT u.*, z.friend, z.foe, p.*
	FROM ' . FORUMS_POSTS_TABLE . ' p
	LEFT JOIN ' . ZEBRA_TABLE . ' z ON (z.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' AND z.zebra_id = p.poster_id), ' . CORE_USERS_TABLE . ' u
	WHERE p.post_id IN (' . implode(', ', $post_list) . ')
		AND u.user_id = p.poster_id';

$result = $_CLASS['core_db']->query($sql);

$now = getdate($_CLASS['core_user']->time);

// Posts are stored in the $rowset array while $attach_list, $user_cache
// and the global bbcode_bitfield are built
while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$poster_id = $row['poster_id'];
	$poster	= ($poster_id == ANONYMOUS) ? ((!empty($row['post_username'])) ? $row['post_username'] : $_CLASS['core_user']->lang['GUEST']) : $row['username'];

	if ($view != 'show' || $post_id != $row['post_id'])
	{
		if ($row['foe'])
		{
			$rowset[$row['post_id']] = array(
				'foe'		=> true,
				'user_id'	=> $row['user_id'],
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
			$has_attachments = true;
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
		
		// Make sure the icon actually exists
		'icon_id'			=> (isset($icons[$row['icon_id']]['img'], $icons[$row['icon_id']]['height'], $icons[$row['icon_id']]['width'])) ? $row['icon_id'] : 0,
		'post_attachment'	=> $row['post_attachment'],
		'post_approved'		=> $row['post_approved'],
		'post_reported'		=> $row['post_reported'],
		'post_text'			=> $row['post_text'],
		'bbcode_uid'		=> $row['bbcode_uid'],
		'bbcode_bitfield'	=> $row['bbcode_bitfield'],
		'enable_html'		=> $row['enable_html'],
		'enable_smilies'	=> $row['enable_smilies'],
		'enable_sig'		=> $row['enable_sig'], 
		'friend'			=> $row['friend'],
	);

	// Define the global bbcode bitfield, will be used to load bbcodes
	$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['bbcode_bitfield']);

	// Is a signature attached? Are we going to display it?
	if ($row['enable_sig'] && $config['allow_sig'] && $_CLASS['core_user']->user_data_get('viewsigs'))
	{
		$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['user_sig_bbcode_bitfield']);
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
				'sig'					=> '',
				'sig_bbcode_uid'		=> '',
				'sig_bbcode_bitfield'	=> '',

				'online'		=> false,
				'avatar'		=> '',
				'rank_title'	=> '',
				'rank_image'	=> '',
				'rank_image_src'	=> '',
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
				'username'		=> ($row['user_colour']) ? '<span style="color:#' . $row['user_colour'] . '">' . $poster . '</span>' : $poster,
				'age'			=> '',

				'warnings'		=> 0,
			);
		}
		else
		{
			$user_sig = '';

			if ($row['enable_sig'] && $config['allow_sig'] && $_CLASS['core_user']->user_data_get('viewsigs'))
			{
				$user_sig = $row['user_sig'];
			}

			$id_cache[] = $poster_id;

			$user_cache[$poster_id] = array(
				'joined'				=> $_CLASS['core_user']->format_date($row['user_reg_date'], $_CLASS['core_user']->lang['DATE_FORMAT']),
				'posts'					=> $row['user_posts'],
				'warnings'		=> (isset($row['user_warnings'])) ? $row['user_warnings'] : 0,
				'from'					=> $row['user_from'],

				'sig'					=> $user_sig,
				'sig_bbcode_uid'		=> ($user_sig) ? $row['user_sig_bbcode_uid'] : '',
				'sig_bbcode_bitfield'	=> ($user_sig) ? $row['user_sig_bbcode_bitfield'] : '',

				'viewonline'			=> $row['user_allow_viewonline'],

				'avatar'				=> '',
				'age'					=> '',

				'rank_title'		=> '',
				'rank_image'		=> '',
				'rank_image_src'	=> '',

				'online'		=> false,
				'profile'		=> generate_link("members_list&amp;mode=viewprofile&amp;u=$poster_id"),
				'www'			=> $row['user_website'],
				'aim'			=> ($row['user_aim']) ? generate_link('members_list&amp;mode=contact&amp;action=aim&amp;u='.$poster_id) : '',
				'msn'			=> ($row['user_msnm']) ? generate_link('members_list&amp;mode=contact&amp;action=msnm&amp;u='.$poster_id) : '',
				'yim'			=> ($row['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . $row['user_yim'] . '&.src=pg' : '',
				'jabber'		=> ($row['user_jabber']) ? generate_link('members_list&amp;mode=contact&amp;action=jabber&amp;u='.$poster_id) : '',
				'search'		=> ($_CLASS['forums_auth']->acl_get('u_search')) ? generate_link('forums&amp;file=search&amp;search_author=' . urlencode($row['username']) .'&amp;showresults=posts') : '',
				'username'		=> ($row['user_colour']) ? '<span style="color:#' . $row['user_colour'] . '">' . $poster . '</span>' : $poster
			);

			if ($row['user_avatar'] && $_CLASS['core_user']->user_data_get('viewavatars'))
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
				$user_cache[$poster_id]['rank_image_src'] = (!empty($ranks['special'][$row['user_rank']]['rank_image'])) ? $config['ranks_path'] . '/' . $ranks['special'][$row['user_rank']]['rank_image'] : '';

			}
			else
			{
				$user_cache[$poster_id]['rank_title'] = $user_cache[$poster_id]['rank_image'] = '';
				$user_cache[$poster_id]['rank_image_src'] = '';
				
				/*
				Well we kind of do need this
				if (isset($ranks['normal']) && !empty($ranks['normal']))
				{
					foreach ($ranks['normal'] as $rank)
					{
						if ($row['user_posts'] >= $rank['rank_min'])
						{
							$user_cache[$poster_id]['rank_title'] = $rank['rank_title'];
							$user_cache[$poster_id]['rank_image'] = (!empty($rank['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $rank['rank_image'] . '" border="0" alt="' . $rank['rank_title'] . '" title="' . $rank['rank_title'] . '" /><br />' : '';
							$user_cache[$poster_id]['rank_image_src'] = (!empty($rank['rank_image'])) ? $config['ranks_path'] . '/' . $rank['rank_image'] : '';
							break;
						}
					}
				}
				*/
			}

			if (!empty($row['user_allow_viewemail']) || $_CLASS['forums_auth']->acl_get('a_email'))
			{
				$user_cache[$poster_id]['email'] = ($config['board_email_form'] && $_CORE_CONFIG['email']['email_enable']) ? generate_link('Members_List&amp;mode=email&amp;u='.$poster_id) : (($config['board_hide_emails'] && !$_CLASS['forums_auth']->acl_get('a_email')) ? '' : 'mailto:' . $row['user_email']);
			}
			else
			{
				$user_cache[$poster_id]['email'] = '';
			}

			if (!empty($row['user_icq']))
			{
				//$user_cache[$poster_id]['icq'] =  generate_link('members_list&amp;mode=contact&amp;action=icq&amp;u='.$poster_id);
				$user_cache[$poster_id]['icq'] = 'http://www.icq.com/people/webmsg.php?to=' . $row['user_icq'];
				$user_cache[$poster_id]['icq_status_img'] = '<img src="http://web.icq.com/whitepages/online?icq=' . $row['user_icq'] . '&amp;img=5" width="18" height="18" border="0" />';
			}
			else
			{
				$user_cache[$poster_id]['icq_status_img'] = '';
				$user_cache[$poster_id]['icq'] = '';
			}
			
			if (!empty($row['user_birthday']))
			{
				list($bday_day, $bday_month, $bday_year) = array_map('intval', explode('-', $row['user_birthday']));

				if ($bday_year)
				{
					$diff = $now['mon'] - $bday_month;
					
					if ($diff == 0)
					{
						$diff = ($now['mday'] - $bday_day < 0) ? 1 : 0;
					}
					else
					{
						$diff = ($diff < 0) ? 1 : 0;
					}
					
					$user_cache[$poster_id]['age'] = (int) ($now['year'] - $bday_year - $diff);
				}
			}
		}
	}
}
$_CLASS['core_db']->free_result($result);
unset($today);

// Generate online information for user
if ($config['load_onlinetrack'] && !empty($id_cache))
{
	$sql = 'SELECT session_user_id, session_hidden, MAX(session_time) as online_time
		FROM ' . CORE_SESSIONS_TABLE . ' 
		WHERE session_user_id IN (' . implode(', ', $id_cache) . ')
		GROUP BY session_user_id, session_hidden';

	$result = $_CLASS['core_db']->query($sql);

	$online_time = $_CLASS['core_user']->time - $_CORE_CONFIG['server']['session_length'];

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$user_cache[$row['session_user_id']]['online'] = (!$row['session_hidden'] && $row['online_time'] > $online_time);
	}
	$_CLASS['core_db']->free_result($result);
}
unset($id_cache);

// Pull attachment data
if (!empty($attach_list))
{
	if ($_CLASS['forums_auth']->acl_get('u_download') && $_CLASS['forums_auth']->acl_get('f_download', $forum_id))
	{
		$sql = 'SELECT * 
			FROM ' . FORUMS_ATTACHMENTS_TABLE . '
			WHERE post_msg_id IN (' . implode(', ', $attach_list) . ')
				AND in_message = 0
			ORDER BY filetime ' . ((!$config['display_order']) ? 'DESC' : 'ASC') . ', post_msg_id ASC';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$attachments[$row['post_msg_id']][] = $row;
		}
		$_CLASS['core_db']->free_result($result);

		// No attachments exist, but post table thinks they do so go ahead and reset post_attach flags
		if (empty($attachments))
		{
			$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . ' 
				SET post_attachment = 0 
				WHERE post_id IN (' . implode(', ', $attach_list) . ')';
			$_CLASS['core_db']->query($sql);

			// We need to update the topic indicator too if the complete topic is now without an attachment
			if (!empty($rowset) != $total_posts)
			{
				// Not all posts are displayed so we query the db to find if there's any attachment for this topic
				$sql = 'SELECT a.post_msg_id as post_id
					FROM ' . FORUMS_ATTACHMENTS_TABLE . ' a, ' . POSTS_TABLE . " p
					WHERE p.topic_id = $topic_id
						AND p.post_approved = 1
						AND p.topic_id = a.topic_id";
				$result = $_CLASS['core_db']->query_limit($sql, 1);

				if (!$_CLASS['core_db']->fetch_row_assoc($result))
				{
					$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . " 
						SET topic_attachment = 0 
						WHERE topic_id = $topic_id";
					$_CLASS['core_db']->query($sql);
				}
				$_CLASS['core_db']->free_result($result);
			}
			else
			{
				$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . " 
					SET topic_attachment = 0 
					WHERE topic_id = $topic_id";
				$_CLASS['core_db']->query($sql);
			}
		}
		elseif ($has_attachments && !$topic_data['topic_attachment'])
		{
			// Topic has approved attachments but its flag is wrong
			$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . " 
				SET topic_attachment = 1 
				WHERE topic_id = $topic_id";
			$_CLASS['core_db']->query($sql);

			$topic_data['topic_attachment'] = 1;
		}
	}
	else
	{
		$display_notice = true;
	}
}

// Instantiate BBCode if need be
if ($bbcode_bitfield !== '')
{
	require_once SITE_FILE_ROOT.'includes/forums/bbcode.php';
	$bbcode = new bbcode(base64_encode($bbcode_bitfield));
}

$i_total = count($rowset) - 1;
$prev_post_id = '';
$first_unread_makred = false;

// Output the posts
$count = count($post_list);
$_CLASS['core_template']->assign('S_NUM_POSTS', $count);

for ($i = 0; $i < $count; ++$i)
{
	$row =& $rowset[$post_list[$i]];

	//is poster is on the users ignore list ?
	if (!empty($row['foe']))
	{
		$_CLASS['core_template']->assign_vars_array('postrow', array(
			'S_IGNORE_POST' => true, 
			'L_IGNORE_POST' => sprintf($_CLASS['core_user']->lang['POST_BY_FOE'], $row['poster'], '<a href="'.generate_link("forums&amp;file=viewtopic&amp;p=" . $row['post_id'] . '&amp;view=show') . '#' . $row['post_id'] . '">', '</a>'))
		);

		continue;
	}

	$poster_id = $row['user_id'];

	// End signature parsing, only if needed
	if ($user_cache[$poster_id]['sig'] && empty($user_cache[$poster_id]['sig_parsed']))
	{
		$user_cache[$poster_id]['sig'] = censor_text($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig'] = str_replace("\n", '<br />', $user_cache[$poster_id]['sig']);

		if ($user_cache[$poster_id]['sig_bbcode_bitfield'])
		{
			$bbcode->bbcode_second_pass($user_cache[$poster_id]['sig'], $user_cache[$poster_id]['sig_bbcode_uid'], $user_cache[$poster_id]['sig_bbcode_bitfield']);
		}

		$user_cache[$poster_id]['sig'] = smiley_text($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig_parsed'] = true;
	}

	// If the board has HTML off but the post has HTML on then we process it, else leave it alone
	if ($row['enable_html'] && (!$config['allow_html'] || !$_CLASS['forums_auth']->acl_get('f_html', $forum_id)))
	{
		$row['post_text'] = preg_replace('#(<!\-\- h \-\-><)([\/]?.*?)(><!\-\- h \-\->)#is', "&lt;\\2&gt;", $row['post_text']);
	}

	$row['post_text'] = censor_text($row['post_text']);
	$row['post_text'] = str_replace("\n", '<br />', $row['post_text']);

	// Second parse bbcode here
	if ($row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield']);
	}

	// Always process smilies after parsing bbcodes
	$row['post_text'] = smiley_text($row['post_text']);

	if (isset($attachments[$row['post_id']]) && !empty($attachments[$row['post_id']]))
	{
		$unset_attachments = parse_inline_attachments($row['post_text'], $attachments[$row['post_id']], $update_count, $forum_id);

		// Needed to let not display the inlined attachments at the end of the post again
		foreach ($unset_attachments as $index)
		{
			unset($attachments[$row['post_id']][$index]);
		}
	}

	// Highlight active words (primarily for search)
	if ($highlight_match)
	{
		$message = preg_replace('#(?!(?:<(?:s(?:cript|tyle))?)[^<]*)(?<!\w)(' . $highlight_match . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $message);
	}

	if ($row['enable_html'] && ($config['allow_html'] && $_CLASS['forums_auth']->acl_get('f_html', $forum_id)))
	{
		// Remove Comments from post content
		$row['post_text'] = preg_replace('#<!\-\-(.*?)\-\->#is', '', $row['post_text']);
	}

	// Replace naughty words such as farty pants
	$row['post_subject'] = censor_text($row['post_subject']);

	// Editing information
	if (($row['post_edit_count'] && $config['display_last_edited']) || $row['post_edit_reason'])
	{
		// Get usernames for all following posts if not already stored
		if (empty($post_edit_list) && ($row['post_edit_reason'] || ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))))
		{
			// Remove all post_ids already parsed (we do not have to check them)
			$post_storage_list = (!$store_reverse) ? array_slice($post_list, $i) : array_slice(array_reverse($post_list), $i);

			$sql = 'SELECT DISTINCT u.user_id, u.username, u.user_colour 
				FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE p.post_id IN (' . implode(', ', $post_storage_list) . ")
					AND p.post_edit_count <> 0
					AND p.post_edit_user <> 0
					AND p.post_edit_user = u.user_id";
			$result2 = $_CLASS['core_db']->query($sql);
			while ($user_edit_row = $_CLASS['core_db']->fetch_row_assoc($result2))
			{
				$user_edit_row['username'] = ($user_edit_row['user_colour']) ? '<span style="color:#' . $user_edit_row['user_colour'] . '"><strong>' . $user_edit_row['username'] . '</strong></span>' : $user_edit_row['username'];
				$post_edit_list[$user_edit_row['user_id']] = $user_edit_row;
			}
			$_CLASS['core_db']->free_result($result2);
			
			unset($post_storage_list);
		}

		$l_edit_time_total = ($row['post_edit_count'] == 1) ? $_CLASS['core_user']->lang['EDITED_TIME_TOTAL'] : $_CLASS['core_user']->lang['EDITED_TIMES_TOTAL'];

		if ($row['post_edit_reason'])
		{
			$user_edit_row = $post_edit_list[$row['post_edit_user']];

			$l_edited_by = sprintf($l_edit_time_total, (!$row['post_edit_user']) ? $row['poster'] : $user_edit_row['username'], $_CLASS['core_user']->format_date($row['post_edit_time']), $row['post_edit_count']);
		}
		else
		{
			if ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))
			{
				$user_cache[$row['post_edit_user']] = $post_edit_list[$row['post_edit_user']];
			}

			$l_edited_by = sprintf($l_edit_time_total, (!$row['post_edit_user']) ? $row['poster'] : $user_cache[$row['post_edit_user']]['username'], $_CLASS['core_user']->format_date($row['post_edit_time']), $row['post_edit_count']);
		}
	}
	else
	{
		$l_edited_by = '';
	}

	// Bump information
	if ($topic_data['topic_bumped'] && $row['post_id'] == $topic_data['topic_last_post_id'] && isset($user_cache[$topic_data['topic_bumper']]) )
	{
		// It is safe to grab the username from the user cache array, we are at the last
		// post and only the topic poster and last poster are allowed to bump. However, a
		// check is still needed incase an admin bumped the topic (but didn't post in the topic)
		$l_bumped_by = '<br /><br />' . sprintf($_CLASS['core_user']->lang['BUMPED_BY'], $user_cache[$topic_data['topic_bumper']]['username'], $_CLASS['core_user']->format_date($topic_data['topic_last_post_time']));
	}
	else
	{
		$l_bumped_by = '';
	}

	if (empty($icons[$row['icon_id']]))
	{
		$icons[$row['icon_id']] = array('img' => '' , 'width' => '', 'height' => '');
	}

	if ($unread = ($row['post_time'] > $topic_last_read))
	{
		$update_mark = ($update_mark) ? (int) max($row['post_time'], $update_mark) : $row['post_time'];
	}

	$postrow = array(
		'ATTACHMENTS'	=> empty($attachments[$row['post_id']]) ? false : display_attachments($forum_id, $attachments[$row['post_id']], $update_count),
		'POSTER_NAME' 	=> $row['poster'],
		'POSTER_RANK' 	=> $user_cache[$poster_id]['rank_title'],
		'RANK_IMAGE' 	=> $user_cache[$poster_id]['rank_image'],
		'RANK_IMAGE_SRC'	=> $user_cache[$poster_id]['rank_image_src'],
		'POSTER_JOINED'		=> $user_cache[$poster_id]['joined'],
		'POSTER_POSTS'		=> $user_cache[$poster_id]['posts'],
		'POSTER_FROM'		=> $user_cache[$poster_id]['from'],
		'POSTER_AVATAR'		=> $user_cache[$poster_id]['avatar'],
		'POSTER_WARNINGS'	=> $user_cache[$poster_id]['warnings'],
		'POSTER_AGE'		=> $user_cache[$poster_id]['age'],
		
		'POST_DATE' 	=> $_CLASS['core_user']->format_date($row['post_time']),
		'POST_SUBJECT' 	=> $row['post_subject'],
		'MESSAGE' 		=> $row['post_text'],
		'SIGNATURE' 	=> ($row['enable_sig']) ? $user_cache[$poster_id]['sig'] : '',
		'EDITED_MESSAGE'=> $l_edited_by,
		'EDIT_REASON'	=> $row['post_edit_reason'],
		'BUMPED_MESSAGE'=> $l_bumped_by,

		'MINI_POST_IMG'			=> ($unread) ? $_CLASS['core_user']->img('icon_post_new', 'NEW_POST') : $_CLASS['core_user']->img('icon_post', 'POST'),
		'POST_ICON_IMG'			=> (!empty($row['icon_id'])) ? $icons[$row['icon_id']]['img'] : '',
		'POST_ICON_IMG_WIDTH'	=> (!empty($row['icon_id'])) ? $icons[$row['icon_id']]['width'] : '',
		'POST_ICON_IMG_HEIGHT'	=> (!empty($row['icon_id'])) ? $icons[$row['icon_id']]['height'] : '',
		
		'ICQ_STATUS_IMG'	=> $user_cache[$poster_id]['icq_status_img'],
		'ONLINE_IMG'		=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? '' : (($user_cache[$poster_id]['online']) ? $_CLASS['core_user']->img('btn_online', 'ONLINE') : $_CLASS['core_user']->img('btn_offline', 'OFFLINE')), 
		'S_ONLINE'			=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? false : (($user_cache[$poster_id]['online']) ? true : false),

		'U_EDIT' 			=> (($_CLASS['core_user']->data['user_id'] == $poster_id && $_CLASS['forums_auth']->acl_get('f_edit', $forum_id) && ($row['post_time'] > $_CLASS['core_user']->time - $config['edit_time'] || !$config['edit_time'])) || $_CLASS['forums_auth']->acl_get('m_edit', $forum_id)) ? generate_link("forums&amp;file=posting&amp;mode=edit&amp;p=" . $row['post_id']) : '',
		'U_QUOTE' 			=> ($_CLASS['forums_auth']->acl_get('f_quote', $forum_id)) ? generate_link("forums&amp;file=posting&amp;mode=quote&amp;p=" . $row['post_id']) : '', 
		'U_INFO'            => ($_CLASS['forums_auth']->acl_get('m_', $forum_id)) ? generate_link('forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id'], false, false) : '',
		'U_DELETE' 			=> (($_CLASS['core_user']->data['user_id'] == $poster_id && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id) && $topic_data['topic_last_post_id'] == $row['post_id'] && ($row['post_time'] > $_CLASS['core_user']->time - $config['edit_time'] || !$config['edit_time'])) || $_CLASS['forums_auth']->acl_get('m_delete', $forum_id)) ? generate_link("forums&amp;file=posting&amp;mode=delete&amp;p=" . $row['post_id']) : '',

		'U_PROFILE' 		=> $user_cache[$poster_id]['profile'],
		'U_SEARCH' 			=> $user_cache[$poster_id]['search'],
		'U_PM' 				=> ($poster_id != ANONYMOUS) ? generate_link('control_panel&amp;i=pm&amp;mode=compose&amp;action=quote&amp;q=1&amp;p=' . $row['post_id']) : '',
		'U_EMAIL' 			=> $user_cache[$poster_id]['email'],
		'U_WWW' 			=> $user_cache[$poster_id]['www'],
		'U_ICQ' 			=> $user_cache[$poster_id]['icq'],
		'U_AIM' 			=> $user_cache[$poster_id]['aim'],
		'U_MSN' 			=> $user_cache[$poster_id]['msn'],
		'U_YIM' 			=> $user_cache[$poster_id]['yim'],
		'U_JABBER'			=> $user_cache[$poster_id]['jabber'], 

		'U_REPORT'			=> generate_link('forums&amp;file=report&amp;p=' . $row['post_id']),
		'U_MCP_REPORT'		=> ($_CLASS['forums_auth']->acl_gets(array('m_', 'a_', 'f_report'), $forum_id)) ? generate_link('forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id']) : '',
		'U_MCP_APPROVE'		=> ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? generate_link('forums&amp;file=mcp&amp;i=queue&amp;action=approve&amp;post_id_list[]=' . $row['post_id'], false, false) : '',
		'U_MCP_DETAILS'		=> ($_CLASS['forums_auth']->acl_get('m_', $forum_id)) ? generate_link('forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id']) : '',
		'U_MINI_POST'		=> generate_link('forums&amp;file=viewtopic&amp;p=' . $row['post_id']) . '#' . $row['post_id'],
		'U_NEXT_POST_ID'	=> ($i < $i_total && isset($rowset[$i + 1])) ? $rowset[$i + 1]['post_id'] : '', 
		'U_PREV_POST_ID'	=> $prev_post_id, 

		'POST_ID'           => $row['post_id'],

		//'U_NOTES'			=> ($_CLASS['forums_auth']->acl_getf_global('m_')) ? generate_link('forums&amp;file=mcp&amp;i=notes&amp;mode=user_notes&amp;u=' . $poster_id) : '',
		//'U_WARN'			=> ($_CLASS['forums_auth']->acl_getf_global('m_warn') && $poster_id != $_CLASS['core_user']->data['user_id']) ? generate_link('forums&amp;file=mcp&amp;i=warn&amp;mode=warn_post&amp;f=' . $forum_id . '&amp;p=' . $row['post_id']) : '',

		'S_POST_UNAPPROVED'	=> !$row['post_approved'],
		'S_POST_REPORTED'	=> ($row['post_reported'] && $_CLASS['forums_auth']->acl_get('m_', $forum_id)),
		'S_DISPLAY_NOTICE'	=> ($display_notice && $row['post_attachment']), 
		'S_FRIEND'			=> $row['friend'],
		'S_UNREAD_POST'		=> $unread,
		'S_FIRST_UNREAD'	=> false,
		'S_IGNORE_POST'		=> false
	);

	if ($unread && !$first_unread_makred)
	{
		$postrow['S_FIRST_UNREAD'] = true;
		$first_unread_makred = true;
	}

	// Dump vars into template
	$_CLASS['core_template']->assign_vars_array('postrow', $postrow);
	
	$prev_post_id = $row['post_id'];

	unset($postrow, $rowset[$i], $attachments[$row['post_id']]);
}

unset($rowset, $user_cache);


// Update topic view and if necessary attachment view counters ... but only
// if this is the first 'page view'

//mb_strpos() should never be 0 here
if (!mb_strpos($_CLASS['core_user']->data['session_url'], '&amp;t='.$topic_id))
{
	$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . '
		SET topic_views = topic_views + 1, topic_last_view_time = ' . $_CLASS['core_user']->time . "
		WHERE topic_id = $topic_id";
	$_CLASS['core_db']->query($sql);

	// Update the attachment download counts
	if (!empty($update_count))
	{
		$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . ' 
			SET download_count = download_count + 1 
			WHERE attach_id IN (' . implode(', ', array_unique($update_count)) . ')';
		$_CLASS['core_db']->query($sql);
	}
}

$allow_change_type = ($_CLASS['forums_auth']->acl_get('m_', $forum_id) || ($_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_id'] == $topic_data['topic_poster'])) ? true : false;

// Quick mod tools
$topic_mod = '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_lock', $forum_id) || ($_CLASS['forums_auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_id'] == $topic_data['topic_poster'] && $topic_data['topic_status'] == ITEM_UNLOCKED)) ? (($topic_data['topic_status'] == ITEM_UNLOCKED) ? '<option value="lock">' . $_CLASS['core_user']->lang['LOCK_TOPIC'] . '</option>' : '<option value="unlock">' . $_CLASS['core_user']->lang['UNLOCK_TOPIC'] . '</option>') : '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_delete', $forum_id)) ? '<option value="delete_topic">' . $_CLASS['core_user']->lang['DELETE_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_move', $forum_id)) ? '<option value="move">' . $_CLASS['core_user']->lang['MOVE_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_split', $forum_id)) ? '<option value="split">' . $_CLASS['core_user']->lang['SPLIT_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_merge', $forum_id)) ? '<option value="merge">' . $_CLASS['core_user']->lang['MERGE_TOPIC'] . '</option>' : '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_', $forum_id)) ? '<option value="fork">' . $_CLASS['core_user']->lang['FORK_TOPIC'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $_CLASS['forums_auth']->acl_gets(array('f_sticky', 'f_announce'), $forum_id) && $topic_data['topic_type'] != POST_NORMAL) ? '<option value="make_normal">' . $_CLASS['core_user']->lang['MAKE_NORMAL'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $_CLASS['forums_auth']->acl_get('f_sticky', $forum_id) && $topic_data['topic_type'] != POST_STICKY) ? '<option value="make_sticky">' . $_CLASS['core_user']->lang['MAKE_STICKY'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $_CLASS['forums_auth']->acl_get('f_announce', $forum_id) && $topic_data['topic_type'] != POST_ANNOUNCE) ? '<option value="make_announce">' . $_CLASS['core_user']->lang['MAKE_ANNOUNCE'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $_CLASS['forums_auth']->acl_get('f_announce', $forum_id) && $topic_data['topic_type'] != POST_GLOBAL) ? '<option value="make_global">' . $_CLASS['core_user']->lang['MAKE_GLOBAL'] . '</option>' : '';
$topic_mod .= ($_CLASS['forums_auth']->acl_get('m_', $forum_id)) ? '<option value="viewlogs">' . $_CLASS['core_user']->lang['VIEW_TOPIC_LOGS'] . '</option>' : '';

$pagination = generate_pagination("forums&amp;file=viewtopic&amp;t=$topic_id&amp;$u_sort_param" . (($highlight_match) ? "&amp;hilit=$highlight" : ''), $total_posts, $config['posts_per_page'], $start);

$topic_data['topic_title'] = censor_text($topic_data['topic_title']);

// Send vars to template
$_CLASS['core_template']->assign_array(array(
	'FORUM_ID' 			=> $forum_id,
	'FORUM_NAME' 		=> $topic_data['forum_name'],
	'FORUM_DESC'		=> $topic_data['forum_desc'],
	'TOPIC_ID' 			=> $topic_id,
	'TOPIC_TITLE' 		=> $topic_data['topic_title'],
	'PAGINATION'		=> $pagination['formated'],
	'PAGINATION_ARRAY'	=> $pagination['array'],
	'PAGE_NUMBER' 		=> on_page($total_posts, $config['posts_per_page'], $start),
	'TOTAL_POSTS'		=> ($total_posts == 1) ? $_CLASS['core_user']->lang['VIEW_TOPIC_POST'] : sprintf($_CLASS['core_user']->lang['VIEW_TOPIC_POSTS'], $total_posts), 
	'U_MCP' 			=> ($_CLASS['forums_auth']->acl_get('m_', $forum_id)) ? generate_link("forums&amp;file=mcp&amp;mode=topic_view&amp;t=$topic_id&amp;start=$start&amp;$u_sort_param", false, false) : '',

	'MODERATORS'	=> (isset($forum_moderators[$forum_id]) && !empty($forum_moderators[$forum_id])) ? implode(', ', $forum_moderators[$forum_id]) : '',

	'POST_IMG' 		=> ($topic_data['forum_status'] == ITEM_LOCKED) ? $_CLASS['core_user']->img('btn_locked', 'FORUM_LOCKED') : $_CLASS['core_user']->img('btn_post', 'POST_NEW_TOPIC'),
	'QUOTE_IMG' 	=> $_CLASS['core_user']->img('btn_quote', 'REPLY_WITH_QUOTE'),
	'REPLY_IMG'		=> ($topic_data['forum_status'] == ITEM_LOCKED || $topic_data['topic_status'] == ITEM_LOCKED) ? $_CLASS['core_user']->img('btn_locked', 'TOPIC_LOCKED') : $_CLASS['core_user']->img('btn_reply', 'REPLY_TO_TOPIC'),
	'EDIT_IMG' 		=> $_CLASS['core_user']->img('btn_edit', 'EDIT_POST'),
	'DELETE_IMG' 	=> $_CLASS['core_user']->img('btn_delete', 'DELETE_POST'),
	'INFO_IMG'		=> $_CLASS['core_user']->img('btn_info', 'VIEW_INFO'),
	'PROFILE_IMG'	=> $_CLASS['core_user']->img('btn_profile', 'READ_PROFILE'), 
	'SEARCH_IMG'	=> $_CLASS['core_user']->img('btn_search', 'SEARCH_USER_POSTS'),
	'PM_IMG'		=> $_CLASS['core_user']->img('btn_pm', 'SEND_PRIVATE_MESSAGE'),
	'EMAIL_IMG' 	=> $_CLASS['core_user']->img('btn_email', 'SEND_EMAIL'),
	'WWW_IMG' 		=> $_CLASS['core_user']->img('btn_www', 'VISIT_WEBSITE'),
	'ICQ_IMG' 		=> $_CLASS['core_user']->img('btn_icq', 'ICQ'),
	'AIM_IMG' 		=> $_CLASS['core_user']->img('btn_aim', 'AIM'),
	'MSN_IMG' 		=> $_CLASS['core_user']->img('btn_msnm', 'MSNM'),
	'YIM_IMG' 		=> $_CLASS['core_user']->img('btn_yim', 'YIM'),
	'JABBER_IMG'	=> $_CLASS['core_user']->img('btn_jabber', 'JABBER') ,
	'REPORT_IMG'	=> $_CLASS['core_user']->img('btn_report', 'REPORT_POST'),
	'REPORTED_IMG'	=> $_CLASS['core_user']->img('icon_reported', 'POST_REPORTED'),
	'UNAPPROVED_IMG'=> $_CLASS['core_user']->img('icon_unapproved', 'POST_UNAPPROVED'),
	'WARN_IMG'		=> $_CLASS['core_user']->img('btn_warn', 'WARN_USER'),

	
	'S_SELECT_SORT_DIR' 	=> $s_sort_dir,
	'S_SELECT_SORT_KEY' 	=> $s_sort_key,
	'S_SELECT_SORT_DAYS' 	=> $s_limit_days,
	'S_SINGLE_MODERATOR'	=> (!empty($forum_moderators[$forum_id]) && count($forum_moderators[$forum_id]) > 1) ? false : true,

	'S_TOPIC_ACTION' 		=> generate_link("forums&amp;file=viewtopic&amp;t=$topic_id&amp;start=$start"),
	'S_TOPIC_MOD' 			=> ($topic_mod) ? '<select name="mode">' . $topic_mod . '</select>' : '',
	'S_MOD_ACTION' 			=> generate_link("forums&amp;file=mcp&amp;t=$topic_id&amp;quickmod=1", false, false), 

	'S_DISPLAY_SEARCHBOX'	=> ($_CLASS['forums_auth']->acl_get('f_search', $forum_id)) ? true : false, 
	'S_SEARCHBOX_ACTION'	=> generate_link('forums&amp;file=search&amp;search_forum[]='.$forum_id), 

	'U_TOPIC'				=> ($view == 'print') ? generate_link('forums&amp;file=viewtopic&amp;t='.$topic_id, array('full' => true)) : generate_link('forums&amp;file=viewtopic&amp;t='.$topic_id),
	'U_VIEW_UNREAD_POST'	=> ($first_unread_makred) ? generate_link("forums&amp;file=viewtopic&amp;t=$topic_id#unread") : false,
	'U_VIEW_TOPIC' 			=> generate_link($viewtopic_url),
	'U_VIEW_FORUM' 			=> generate_link('forums&amp;file=viewforum&amp;f='.$forum_id),
	'U_VIEW_OLDER_TOPIC'	=> generate_link("forums&amp;file=viewtopic&amp;t=$topic_id&amp;view=previous"),
	'U_VIEW_NEWER_TOPIC'	=> generate_link("forums&amp;file=viewtopic&amp;t=$topic_id&amp;view=next"),
	'U_PRINT_TOPIC'			=> ($_CLASS['forums_auth']->acl_get('f_print', $forum_id)) ? generate_link($viewtopic_url . '&amp;view=print') : '',
	'U_EMAIL_TOPIC'			=> ($_CLASS['forums_auth']->acl_get('f_email', $forum_id) && $_CORE_CONFIG['email']['email_enable']) ? generate_link('members_list&amp;mode=email&amp;t='.$topic_id) : '', 

	'S_WATCH_FORUMS'		=> $topic_data['watch_topic'] ? false : true,
	'U_WATCH_TOPIC' 		=> $s_watching_topic['link'], 
	'L_WATCH_TOPIC' 		=> $s_watching_topic['title'], 

	'U_BOOKMARK_TOPIC'		=> ($_CLASS['core_user']->is_user && $config['allow_bookmarks']) ? generate_link($viewtopic_url . '&amp;bookmark=1') : '',
	'L_BOOKMARK_TOPIC'		=> ($_CLASS['core_user']->is_user && $config['allow_bookmarks'] && $topic_data['bookmarked']) ? $_CLASS['core_user']->lang['BOOKMARK_TOPIC_REMOVE'] : $_CLASS['core_user']->lang['BOOKMARK_TOPIC'],
	
	'U_POST_NEW_TOPIC' 		=> generate_link('forums&amp;file=posting&amp;mode=post&amp;f='.$forum_id),
	'U_POST_REPLY_TOPIC' 	=> generate_link("forums&amp;file=posting&amp;mode=reply&amp;t=$topic_id"),
	'U_BUMP_TOPIC'			=> bump_topic_allowed($forum_id, $topic_data['topic_bumped'], $topic_data['topic_last_post_time'], $topic_data['topic_poster'], $topic_data['topic_last_poster_id']) ? generate_link("forums&amp;file=posting&amp;mode=bump&amp;t=$topic_id") : '')
);

// Mark topics read
if ($update_mark)
{
	if ($_CLASS['core_user']->is_user && $config['load_db_lastread'])
	{
		// Now lets get the all topics that are in the markable range,
		// with is the max topics displayed on the forum's viewforum page

		// Get the user's last mark time for this forums
// add user view options
		$sql = 'SELECT mark_time FROM '.FORUMS_TRACK_TABLE . ' 
			WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . " 
				AND forum_id = $forum_id AND topic_id = 0";
	
		$result = $_CLASS['core_db']->query($sql);
		$mark_time_forum = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row['mark_time'] : 0;
		$_CLASS['core_db']->free_result($result);
			
		$sql = 'SELECT t.topic_id, t.topic_last_post_time, tr.mark_time FROM ' . FORUMS_TOPICS_TABLE . ' t
				LEFT JOIN ' . FORUMS_TRACK_TABLE . ' tr ON (tr.topic_id = t.topic_id AND tr.user_id = '.$_CLASS['core_user']->data['user_id'].")
					WHERE t.forum_id = $forum_id
						ORDER BY t.topic_last_post_time DESC";
	}
	else
	{
		
		$tracking = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));

		if (!is_array($tracking))
		{
			$tracking = array();
		}

		$forum_id36 = base_convert($forum_id, 10, 36);
		$mark_time_forum = isset($tracking[$forum_id36][0]) ? (int) base_convert($tracking[$forum_id36][0], 36, 10) : 0;

// add user view options
		$sql = 'SELECT topic_id, topic_last_post_time FROM ' . FORUMS_TOPICS_TABLE . "
					WHERE forum_id = $forum_id
					ORDER BY topic_last_post_time DESC";
	}

	$result = $_CLASS['core_db']->query_limit($sql, $config['topics_per_page']);
	$update_forum_mark = true;
	$last_forum_post = 0;

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		// DESC order so the first post is the last post
		$last_forum_post = max($last_forum_post, $row['topic_last_post_time']);

		if (!$_CLASS['core_user']->is_user || !$config['load_db_lastread'])
		{
			$topic_id36 = base_convert($topic_id, 10, 36);
			$row['mark_time'] = isset($tracking[$forum_id36][$topic_id36]) ? (int) base_convert($tracking[$forum_id36][$topic_id36], 36, 10) : 0;
		}

		$last_mark_time = max($row['mark_time'], $mark_time_forum);

		if ($row['topic_last_post_time'] > $last_mark_time && $row['topic_id'] != $topic_id)
		{
			// We have a winner/loser
			// Set so the forum isn't marked
			$update_forum_mark = false;

			break;
		}
	}
	$_CLASS['core_db']->free_result($result);

	// Was this the last unread topic ?
	if ($update_forum_mark)
	{
		markread('forum', $forum_id, 0, $last_forum_post);
	}
	else
	{
		markread('topic', $forum_id, $topic_id, $update_mark);
	}
}

if ($view == 'print')
{
	$_CLASS['core_display']->display(false, 'modules/forums/viewtopic_print.html');
}

page_header();

make_jumpbox(generate_link('forums&amp;file=viewforum'), $forum_id);

$_CLASS['core_display']->display(array($_CLASS['core_user']->get_lang('VIEWING_TOPIC'), $topic_data['topic_title']), 'modules/forums/viewtopic_body.html');

//Move if we can
function topic_last_read($topic_id, $forum_id)
{
	global $config, $_CORE_CONFIG, $_CLASS;

	if ($_CLASS['core_user']->is_bot)
	{
		return $_CLASS['core_user']->time;
	}

	if ($_CLASS['core_user']->is_user && $config['load_db_lastread'])
	{
		$sql = 'SELECT MAX(mark_time) as mark_time
			FROM ' . FORUMS_TRACK_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . "
				AND forum_id = $forum_id
				AND topic_id IN ($topic_id, 0)";

		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		return isset($row['mark_time']) ? (int) $row['mark_time'] : 0;
	}
	else
	{
		$tracking = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));

		if (!is_array($tracking))
		{
			return 0;
		}

		$forum_id36 = base_convert($forum_id, 10, 36);
		$topic_id36 = base_convert($topic_id, 10, 36);

		$topic_last_read = $forum_last_read = 0;

		if (isset($tracking[$forum_id36][0]))
		{
			$forum_last_read = (int) base_convert($tracking[$forum_id36][0], 36, 10);
		}

		if (isset($tracking[$forum_id36][$topic_id36]))
		{
			$topic_last_read = (int) base_convert($tracking[$forum_id36][$topic_id36], 36, 10);
		}

		return (int) max($forum_last_read, $topic_last_read);
	}
}

?>