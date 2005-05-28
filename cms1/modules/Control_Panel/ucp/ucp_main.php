<?php
// -------------------------------------------------------------
//
// $Id: ucp_main.php,v 1.19 2004/07/11 15:20:32 acydburn Exp $
//
// FILENAME  : ucp_main.php
// STARTED   : Mon May 19, 2003
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

class ucp_main extends module  
{
	function ucp_main($id, $mode)
	{
		global $config, $_CLASS, $phpEx, $site_file_root, $_CORE_CONFIG;

		$_CLASS['core_template']->assign(array(
			'ERROR' => false,
			'topicrow' => false,
			'S_PRIVMSGS' => false,
			'WARNINGS' => false,
			'draftrow' => false)
		);
		
		switch ($mode)
		{
			case 'front':

				$_CLASS['core_user']->add_lang(false,'Members_List');

				if ($config['load_db_lastread'] || $config['load_db_track'])
				{
					if ($config['load_db_lastread'])
					{
						$sql = 'SELECT mark_time 
							FROM ' . FORUMS_TRACK_TABLE . ' 
							WHERE forum_id = 0
								AND user_id = ' . $_CLASS['core_user']->data['user_id'];
						$result = $_CLASS['core_db']->sql_query($sql);

						$track_data = $_CLASS['core_db']->sql_fetchrow($result);
						$_CLASS['core_db']->sql_freeresult($result);
					}

					switch (SQL_LAYER)
					{
						case 'oracle':
							break;

						default:
							$sql_from = '(' . TOPICS_TABLE . ' t LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id AND tt.user_id = ' . $_CLASS['core_user']->data['user_id'] . '))';
							break;
					}
					$sql_select = ', tt.mark_type, tt.mark_time';

				}
				else
				{
					$sql_from = TOPICS_TABLE . ' t ';
					$sql_select = '';
				}

				$tracking_topics = (isset($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) ? unserialize(stripslashes($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) : array();
		
				// Has to be in while loop if we not only check forum id 0
				if ($config['load_db_lastread'])
				{
					$forum_check = $track_data['mark_time'];
				}
				else
				{
					$forum_check = (isset($tracking_topics[0][0])) ? base_convert($tracking_topics[0][0], 36, 10) + $config['board_startdate'] : 0;
				}

				$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_ANNOUNCEMENT'];
				$folder = 'folder_announce';
				$folder_new = $folder . '_new';

				// Determine first forum the user is able to read into - for global announcement link
				$forum_ary = $_CLASS['auth']->acl_getf('f_read');
				$g_forum_id = 0;

				foreach ($forum_ary as $forum_id => $allowed)
				{
					if (!$allowed['f_read'])
					{
						unset($forum_ary[$forum_id]);
					}
				}
				$forum_ary = array_unique(array_keys($forum_ary));

				$sql = 'SELECT forum_id 
					FROM ' . FORUMS_TABLE . '
					WHERE forum_type = ' . FORUM_POST . '
						AND forum_id IN (' . implode(', ', $forum_ary) . ')';
				$result = $_CLASS['core_db']->sql_query_limit($sql, 1);
				$g_forum_id = (int) $_CLASS['core_db']->sql_fetchfield('forum_id', 0, $result);
				$_CLASS['core_db']->sql_freeresult($result);

				$sql = "SELECT t.* $sql_select 
					FROM $sql_from
					WHERE t.forum_id = 0
						AND t.topic_type = " . POST_GLOBAL . '
					ORDER BY t.topic_last_post_time DESC';
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$forum_id = $row['forum_id'];
					$topic_id = $row['topic_id'];

					if ($row['topic_status'] == ITEM_LOCKED)
					{
						$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_LOCKED'];
						$folder = 'folder_locked';
						$folder_new = 'folder_locked_new';
					}

					$unread_topic = true;

					if ($config['load_db_lastread'])
					{
						$topic_check = $row['mark_time'];
					}
					else
					{
						$topic_id36 = base_convert($topic_id, 10, 36);
						$topic_check = (isset($tracking_topics[0][$topic_id36])) ? base_convert($tracking_topics[0][$topic_id36], 36, 10) + $config['board_startdate'] : 0;
					}

					if ($topic_check >= $row['topic_last_post_time'] || $forum_check >= $row['topic_last_post_time'])
					{
						$unread_topic = false;
					}

					$newest_post_img = ($unread_topic) ? '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$g_forum_id&amp;t=$topic_id&amp;view=unread#unread").'">' . $_CLASS['core_user']->img('icon_post_newest', 'VIEW_NEWEST_POST') . '</a> ' : '';
					$folder_img = ($unread_topic) ? $folder_new : $folder;
					$folder_alt = ($unread_topic) ? 'NEW_POSTS' : (($row['topic_status'] == ITEM_LOCKED) ? 'TOPIC_LOCKED' : 'NO_NEW_POSTS');

					// Posted image?
					if (!empty($row['mark_type']))
					{
						$folder_img .= '_posted';
					}

					$view_topic_url = getlink("Forums&amp;file=viewtopic&amp;f=$g_forum_id&amp;t=$topic_id");

					$last_post_img = '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$g_forum_id&amp;t=$topic_id&amp;p=" . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id']) . '">' . $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST') . '</a>';

					$last_post_author = ($row['topic_last_poster_id'] == ANONYMOUS) ? (($row['topic_last_poster_name'] != '') ? $row['topic_last_poster_name'] . ' ' : $_CLASS['core_user']->lang['GUEST'] . ' ') : '<a href="'.getlink('Members_List&amp;mode=viewprofile&amp;u='  . $row['topic_last_poster_id']) . '">' . $row['topic_last_poster_name'] . '</a>';

					$_CLASS['core_template']->assign_vars_array('topicrow', array(
						'FORUM_ID' 			=> $forum_id,
						'TOPIC_ID' 			=> $topic_id,
						'GOTO_PAGE'			=> '',
						'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
						'LAST_POST_AUTHOR' 	=> $last_post_author,
						'TOPIC_TITLE' 		=> censor_text($row['topic_title']),
						'TOPIC_TYPE' 		=> $topic_type,

						'LAST_POST_IMG' 	=> $last_post_img,
						'NEWEST_POST_IMG' 	=> $newest_post_img,
						'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
						'ATTACH_ICON_IMG'	=> ($_CLASS['auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', '') : '',

						'S_USER_POSTED'		=> (!empty($row['mark_type'])) ? true : false, 

						'U_VIEW_TOPIC'	=> $view_topic_url)
					);
				}
				$_CLASS['core_db']->sql_freeresult($result);

				$post_count_ary = $_CLASS['auth']->acl_getf('f_postcount');
				
				$forum_ary = array();
				foreach ($post_count_ary as $forum_id => $allowed)
				{
					if ($allowed['f_read'] && $allowed['f_postcount'])
					{
						$forum_ary[] = $forum_id;
					}
				}

				$post_count_sql = (sizeof($forum_ary)) ? 'AND f.forum_id IN (' . implode(', ', $forum_ary) . ')' : '';
				unset($forum_ary, $post_count_ary);

				if ($post_count_sql)
				{
					// NOTE: The following three queries could be a problem for big boards
					
					// Grab all the relevant data
					$sql = 'SELECT COUNT(p.post_id) AS num_posts   
						FROM ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . ' f
						WHERE p.poster_id = ' . $_CLASS['core_user']->data['user_id'] . " 
							AND f.forum_id = p.forum_id 
							$post_count_sql";
					$result = $_CLASS['core_db']->sql_query($sql);

					$num_real_posts = min($_CLASS['core_user']->data['user_posts'], $_CLASS['core_db']->sql_fetchfield('num_posts', 0, $result));
					$_CLASS['core_db']->sql_freeresult($result);

					$sql = 'SELECT f.forum_id, f.forum_name, COUNT(post_id) AS num_posts   
						FROM ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . ' f 
						WHERE p.poster_id = ' . $_CLASS['core_user']->data['user_id'] . " 
							AND f.forum_id = p.forum_id 
							$post_count_sql
						GROUP BY f.forum_id, f.forum_name  
						ORDER BY num_posts DESC"; 
					$result = $_CLASS['core_db']->sql_query_limit($sql, 1);

					$active_f_row = $_CLASS['core_db']->sql_fetchrow($result);
					$_CLASS['core_db']->sql_freeresult($result);

					$sql = 'SELECT t.topic_id, t.topic_title, COUNT(p.post_id) AS num_posts   
						FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f  
						WHERE p.poster_id = ' . $_CLASS['core_user']->data['user_id'] . " 
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
					$num_real_posts = 0;
					$active_f_row = $active_t_row = array();
				}

				// Do the relevant calculations 
				$memberdays = max(1, round((time() - $_CLASS['core_user']->data['user_regdate']) / 86400));
				$posts_per_day = $_CLASS['core_user']->data['user_posts'] / $memberdays;
				$percentage = ($config['num_posts']) ? min(100, ($num_real_posts / $config['num_posts']) * 100) : 0;

				$active_f_name = $active_f_id = $active_f_count = $active_f_pct = '';
				if (!empty($active_f_row['num_posts']))
				{
					$active_f_name = $active_f_row['forum_name'];
					$active_f_id = $active_f_row['forum_id'];
					$active_f_count = $active_f_row['num_posts'];
					$active_f_pct = ($_CLASS['core_user']->data['user_posts']) ? ($active_f_count / $_CLASS['core_user']->data['user_posts']) * 100 : 0;
				}
				unset($active_f_row);

				$active_t_name = $active_t_id = $active_t_count = $active_t_pct = '';
				if (!empty($active_t_row['num_posts']))
				{
					$active_t_name = $active_t_row['topic_title'];
					$active_t_id = $active_t_row['topic_id'];
					$active_t_count = $active_t_row['num_posts'];
					$active_t_pct = ($_CLASS['core_user']->data['user_posts']) ? ($active_t_count / $_CLASS['core_user']->data['user_posts']) * 100 : 0;
				}
				unset($active_t_row);


				$_CLASS['core_template']->assign(array(
					'USER_COLOR'		=> (!empty($_CLASS['core_user']->data['user_colour'])) ? $_CLASS['core_user']->data['user_colour'] : '', 
					'KARMA'				=> ($config['enable_karma']) ? $_CLASS['core_user']->lang['KARMA'][$_CLASS['core_user']->data['user_karma']] : '', 
					'JOINED'			=> $_CLASS['core_user']->format_date($_CLASS['core_user']->data['user_regdate'], $_CLASS['core_user']->lang['DATE_FORMAT']),
					'VISITED'			=> (empty($last_visit)) ? ' - ' : $_CLASS['core_user']->format_date($last_visit, $_CLASS['core_user']->lang['DATE_FORMAT']),
					'POSTS'				=> ($_CLASS['core_user']->data['user_posts']) ? $_CLASS['core_user']->data['user_posts'] : 0,
					'POSTS_DAY'			=> sprintf($_CLASS['core_user']->lang['POST_DAY'], $posts_per_day),
					'POSTS_PCT'			=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $percentage),
					'ACTIVE_FORUM'		=> $active_f_name, 
					'ACTIVE_FORUM_POSTS'=> ($active_f_count == 1) ? sprintf($_CLASS['core_user']->lang['USER_POST'], 1) : sprintf($_CLASS['core_user']->lang['USER_POSTS'], $active_f_count), 
					'ACTIVE_FORUM_PCT'	=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $active_f_pct), 
					'ACTIVE_TOPIC'		=> $active_t_name,
					'ACTIVE_TOPIC_POSTS'=> ($active_t_count == 1) ? sprintf($_CLASS['core_user']->lang['USER_POST'], 1) : sprintf($_CLASS['core_user']->lang['USER_POSTS'], $active_t_count), 
					'ACTIVE_TOPIC_PCT'	=> sprintf($_CLASS['core_user']->lang['POST_PCT'], $active_t_pct), 

					'OCCUPATION'	=> (!empty($row['user_occ'])) ? $row['user_occ'] : '',
					'INTERESTS'		=> (!empty($row['user_interests'])) ? $row['user_interests'] : '',

					'KARMA_IMG'			=> ($config['enable_karma']) ? $_CLASS['core_user']->img('karma_center', $_CLASS['core_user']->lang['KARMA'][$_CLASS['core_user']->data['user_karma']], false, (int) $_CLASS['core_user']->data['user_karma']) : '', 
					'L_YOUR_WARNINGS'	=> $_CLASS['core_user']->lang['YOUR_WARNINGS'],
					'L_YOUR_KARMA'		=> $_CLASS['core_user']->lang['YOUR_KARMA'],

//					'S_GROUP_OPTIONS'	=> $group_options, 

					'U_SEARCH_USER'		=> ($_CLASS['auth']->acl_get('u_search')) ? getlink('Forums&amp;file=search&amp;search_author=' . urlencode($_CLASS['core_user']->data['username']) . "&amp;show_results=posts") : '',  
					'U_ACTIVE_FORUM'	=> getlink('Forums&amp;file=viewforum&amp;f='.$active_f_id),
					'U_ACTIVE_TOPIC'	=> getlink('Forums&amp;file=viewtopic&amp;t='.$active_t_id))
				);

				break;

			case 'subscribed':

				require($site_file_root.'includes/forums/functions_display.' . $phpEx);
				//$_CLASS['core_user']->add_lang('viewforum');

				$unwatch = (isset($_POST['unwatch'])) ? true : false;
				
				if ($unwatch)
				{
					$forums = (isset($_POST['f'])) ? implode(', ', array_map('intval', array_keys($_POST['f']))) : false;
					$topics = (isset($_POST['t'])) ? implode(', ', array_map('intval', array_keys($_POST['t']))) : false;

					if ($forums || $topics)
					{
						$l_unwatch = '';
						if ($forums)
						{
							$sql = 'DELETE FROM ' . FORUMS_WATCH_TABLE . "
								WHERE forum_id IN ($forums) 
									AND user_id = " .$_CLASS['core_user']->data['user_id'];
							$_CLASS['core_db']->sql_query($sql);

							$l_unwatch .= '_FORUMS';
						}

						if ($topics)
						{
							$sql = 'DELETE FROM ' . TOPICS_WATCH_TABLE . "
								WHERE topic_id IN ($topics) 
									AND user_id = " .$_CLASS['core_user']->data['user_id'];
							$_CLASS['core_db']->sql_query($sql);

							$l_unwatch .= '_TOPICS';
						}

						$message = $_CLASS['core_user']->lang['UNWATCHED' . $l_unwatch] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel&amp;i=$id&amp;mode=subscribed").'">', '</a>');
						
						$_CLASS['core_display']->meta_refresh(3, getlink("Control_Panel&amp;i=$id&amp;mode=subscribed"));
						trigger_error($message);
					}
				}

				if ($config['load_db_lastread'])
				{
					switch (SQL_LAYER)
					{
						case 'oracle':
							break;

						default:
							$sql_from = '(' . FORUMS_TABLE . ' f  LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' AND ft.forum_id = f.forum_id))';
							break;
					}
					$lastread_select = ', ft.mark_time ';
				}
				else
				{
					$sql_from = FORUMS_TABLE . ' f ';
					$lastread_select = '';

					$tracking_topics = (isset($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) ? unserialize(stripslashes($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) : array();
				}

				$sql = "SELECT f.*$lastread_select 
					FROM $sql_from, " . FORUMS_WATCH_TABLE . ' fw
					WHERE fw.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
						AND f.forum_id = fw.forum_id 
					ORDER BY left_id';
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$forum_id = $row['forum_id'];

					$unread_forum = false;
					$tracking_topics[$forum_id][0] = (isset($tracking_topics[$forum_id][0])) ? $tracking_topics[$forum_id][0] : '';
					$forum_check = (!$config['load_db_lastread']) ? $tracking_topics[$forum_id][0] : $row['mark_time'];

					if ($forum_check < $row['forum_last_post_time'])
					{
						$unread_forum = true;
					}
	
					// Which folder should we display?
					if ($row['forum_status'] == ITEM_LOCKED)
					{
						$folder_image = ($unread_forum) ? 'folder_locked_new' : 'folder_locked';
						$folder_alt = 'FORUM_LOCKED';
					}
					else
					{
						$folder_image = ($unread_forum) ? 'folder_new' : 'folder';
						$folder_alt = ($unread_forum) ? 'NEW_POSTS' : 'NO_NEW_POSTS';
					}

					// Create last post link information, if appropriate
					if ($row['forum_last_post_id'])
					{
						$last_post_time = $_CLASS['core_user']->format_date($row['forum_last_post_time']);

						$last_poster = ($row['forum_last_poster_name'] != '') ? $row['forum_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'];
						$last_poster_url = ($row['forum_last_poster_id'] == ANONYMOUS) ? '' : getlink('Members_List&amp;mode=viewprofile&amp;u='  . $row['forum_last_poster_id']);

						$last_post_url = getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;p=" . $row['forum_last_post_id'] . '#' . $row['forum_last_post_id']);
					}
					else
					{
						$last_post_time = $last_poster = $last_poster_url = $last_post_url = '';
					}

					$_CLASS['core_template']->assign_vars_array('forumrow', array(
						'FORUM_ID'			=> $forum_id, 
						'FORUM_FOLDER_IMG'	=> $_CLASS['core_user']->img($folder_image, $folder_alt),
						'FORUM_NAME'		=> $row['forum_name'],
						'LAST_POST_IMG'		=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'), 
						'LAST_POST_TIME'	=> $last_post_time,
						'LAST_POST_AUTHOR'	=> $last_poster,
						
						'U_LAST_POST_AUTHOR'=> $last_poster_url, 
						'U_LAST_POST'		=> $last_post_url, 
						'U_VIEWFORUM'		=> getlink('Forums&amp;file=viewforum&amp;f=' . $row['forum_id']))
					);
				}
				$_CLASS['core_db']->sql_freeresult($result);

				$sql_f_select = '';
				
				// Subscribed Topics
				$start = request_var('start', 0);
	
				$sql = 'SELECT COUNT(topic_id) as topics_count
					FROM ' . TOPICS_WATCH_TABLE . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$result = $_CLASS['core_db']->sql_query($sql);
				$topics_count = (int) $_CLASS['core_db']->sql_fetchfield('topics_count', 0, $result);
				$_CLASS['core_db']->sql_freeresult($result);

				if ($topics_count)
				{
					$_CLASS['core_template']->assign(array(
						'PAGINATION'	=> generate_pagination("Control_Panel&amp;i=$id&amp;mode=$mode", $topics_count, $config['topics_per_page'], $start),
						'PAGE_NUMBER'	=> on_page($topics_count, $config['topics_per_page'], $start),
						'TOTAL_TOPICS'	=> ($topics_count == 1) ? $_CLASS['core_user']->lang['VIEW_FORUM_TOPIC'] : sprintf($_CLASS['core_user']->lang['VIEW_FORUM_TOPICS'], $topics_count))
					);
				}
				
				$sql_from = ($config['load_db_lastread'] || $config['load_db_track']) ? '(' . TOPICS_TABLE . ' t LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id AND tt.user_id = ' . $_CLASS['core_user']->data['user_id'] . '))' : TOPICS_TABLE . ' t';
				$sql_f_tracking = ($config['load_db_lastread']) ? 'LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.forum_id = t.forum_id AND ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . '), ' : '';

				$sql_t_select = ($config['load_db_lastread'] || $config['load_db_track']) ? ', tt.mark_type, tt.mark_time' : '';
				$sql_f_select = ($config['load_db_lastread']) ? ', ft.mark_time AS forum_mark_time' : '';
				
				$sql = "SELECT t.* $sql_f_select $sql_t_select 
					FROM $sql_from $sql_f_tracking " . TOPICS_WATCH_TABLE . ' tw
					WHERE tw.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
						AND t.topic_id = tw.topic_id 
					ORDER BY t.topic_last_post_time DESC';
				$result = $_CLASS['core_db']->sql_query_limit($sql, $config['topics_per_page'], $start);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$topic_id = $row['topic_id'];
					$forum_id = $row['forum_id'];
					
					if ($config['load_db_lastread'])
					{
						$mark_time_topic = (is_user()) ? $row['mark_time'] : 0;
						$mark_time_forum = $row['forum_mark_time'];
					}
					else
					{
						$topic_id36 = base_convert($topic_id, 10, 36);
						$forum_id36 = ($row['topic_type'] == POST_GLOBAL) ? 0 : $forum_id;
						$mark_time_topic = (isset($tracking_topics[$forum_id36][$topic_id36])) ? base_convert($tracking_topics[$forum_id36][$topic_id36], 36, 10) + $config['board_startdate'] : 0;

						$mark_time_forum = (isset($tracking_topics[$forum_id][0])) ? base_convert($tracking_topics[$forum_id][0], 36, 10) + $config['board_startdate'] : 0;
					}

					// Replies
					$replies = ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];

					if ($row['topic_status'] == ITEM_MOVED)
					{
						$topic_id = $row['topic_moved_id'];
					}

					// Get folder img, topic status/type related informations
					$folder_img = $folder_alt = $topic_type = '';
					$unread_topic = topic_status($row, $replies, $mark_time_topic, $mark_time_forum, $folder_img, $folder_alt, $topic_type);
					
					$newest_post_img = ($unread_topic) ? '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;view=unread#unread", false, false, false).'">' . $_CLASS['core_user']->img('icon_post_newest', 'VIEW_NEWEST_POST') . '</a> ' : '';

					$view_topic_url = "Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id";

					$_CLASS['core_template']->assign_vars_array('topicrow', array(
						'FORUM_ID' 			=> $forum_id,
						'TOPIC_ID' 			=> $topic_id,
						'TOPIC_AUTHOR' 		=> topic_topic_author($row),
						'FIRST_POST_TIME' 	=> $_CLASS['core_user']->format_date($row['topic_time']),
						'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
						'LAST_VIEW_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_view_time']),
						'LAST_POST_AUTHOR' 	=> ($row['topic_last_poster_name'] != '') ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],
						'PAGINATION' 		=> topic_generate_pagination($replies, 'Forums&amp;file=viewtopic&amp;f=' . (($row['forum_id']) ? $row['forum_id'] : $forum_id) . "&amp;t=$topic_id"),
						'REPLIES' 			=> $replies,
						'VIEWS' 			=> $row['topic_views'],
						'TOPIC_TITLE' 		=> censor_text($row['topic_title']),
						'TOPIC_TYPE' 		=> $topic_type,

						'LAST_POST_IMG' 	=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'),
						'NEWEST_POST_IMG' 	=> $newest_post_img,
						'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
						'TOPIC_ICON_IMG'	=> (!empty($icons[$row['icon_id']])) ? '<img src="' . $config['icons_path'] . '/' . $icons[$row['icon_id']]['img'] . '" width="' . $icons[$row['icon_id']]['width'] . '" height="' . $icons[$row['icon_id']]['height'] . '" alt="" title="" />' : '',
						'ATTACH_ICON_IMG'	=> ($_CLASS['auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', sprintf($_CLASS['core_user']->lang['TOTAL_ATTACHMENTS'], $row['topic_attachment'])) : '',

						'S_TOPIC_TYPE'			=> $row['topic_type'],
						'S_USER_POSTED'			=> (!empty($row['mark_type'])) ? true : false,
						'S_UNREAD_TOPIC'		=> $unread_topic,

						'U_LAST_POST'		=> getlink($view_topic_url .  '&amp;p=' . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id'], false, false, false),
						'U_LAST_POST_AUTHOR'=> ($row['topic_last_poster_id'] != ANONYMOUS && $row['topic_last_poster_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']) : '',
						'U_VIEW_TOPIC'		=> getlink($view_topic_url))
					);
					
				}
				$_CLASS['core_db']->sql_freeresult($result);

				break;

			case 'bookmarks':
				
				if (!$config['allow_bookmarks'])
				{
					$_CLASS['core_template']->assign('S_NO_DISPLAY_BOOKMARKS', true);
					break;
				} else {
					$_CLASS['core_template']->assign('S_NO_DISPLAY_BOOKMARKS' , false);
				}
				
				require($site_file_root.'includes/forums/functions_display.' . $phpEx);
				//$_CLASS['core_user']->add_lang('viewforum');

				$move_up = request_var('move_up', 0);
				$move_down = request_var('move_down', 0);

				$sql = 'SELECT MAX(order_id) as max_order_id FROM ' . BOOKMARKS_TABLE . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$result = $_CLASS['core_db']->sql_query($sql);
				$max_order_id = $_CLASS['core_db']->sql_fetchfield('max_order_id', 0, $result);
				$_CLASS['core_db']->sql_freeresult($result);

				if ($move_up || $move_down)
				{
					if (($move_up && $move_up != 1) || ($move_down && $move_down != $max_order_id))
					{
						$order = ($move_up) ? $move_up : $move_down;
						$order_total = $order * 2 + (($move_up) ? -1 : 1);
		
						$sql = 'UPDATE ' . BOOKMARKS_TABLE . "
							SET order_id = $order_total - order_id
							WHERE order_id IN ($order, " . (($move_up) ? $order - 1 : $order + 1) . ')
								AND user_id = ' . $_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);
					}
				}
				
				if (isset($_POST['unbookmark']))
				{
					$s_hidden_fields = '<input type="hidden" name="unbookmark" value="1" />';
					$topics = (isset($_POST['t'])) ? array_map('intval', array_keys($_POST['t'])) : array();
					$url = getlink('Control_Panel&amp;i=main&amp;mode=bookmarks');
					
					if (empty($topics))
					{
						trigger_error('NO_BOOKMARKS_SELECTED');
					}
					
					foreach ($topics as $topic_id)
					{
						$s_hidden_fields .= '<input type="hidden" name="t[' . $topic_id . ']" value="1" />';
					}

					if (confirm_box(true))
					{
						$sql = 'DELETE FROM ' . BOOKMARKS_TABLE . '
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
								AND topic_id IN (' . implode(', ', $topics) . ')';
						$_CLASS['core_db']->sql_query($sql);

						// Re-Order bookmarks (possible with one query? This query massaker is not really acceptable...)
						$sql = 'SELECT topic_id FROM ' . BOOKMARKS_TABLE . '
							WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
							ORDER BY order_id ASC';
						$result = $_CLASS['core_db']->sql_query($sql);

						$i = 1;
						while ($row = $_CLASS['core_db']->sql_fetchrow($result))
						{
							$_CLASS['core_db']->sql_query('UPDATE ' . BOOKMARKS_TABLE . "
								SET order_id = '$i'
								WHERE topic_id = '{$row['topic_id']}'
									AND user_id = '{$_CLASS['core_user']->data['user_id']}'");
							$i++;
						}
						$_CLASS['core_db']->sql_freeresult($result);

						$_CLASS['core_display']->meta_refresh(3, $url);
						$message = $_CLASS['core_user']->lang['BOOKMARKS_REMOVED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $url . '">', '</a>');
						trigger_error($message);
					}
					else
					{
						confirm_box(false, 'REMOVE_SELECTED_BOOKMARKS', $s_hidden_fields);
					}
				}

				// We grab deleted topics here too...
				// NOTE: At the moment bookmarks are not removed with topics, might be useful later (not really sure how though. :D)
				// But since bookmarks are sensible to the user, they should not be deleted without notice.
				$sql = 'SELECT b.order_id, b.topic_id as b_topic_id, t.*, f.forum_name
					FROM ' . BOOKMARKS_TABLE . ' b
						LEFT JOIN ' . TOPICS_TABLE . ' t ON b.topic_id = t.topic_id
						LEFT JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
					WHERE b.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
					ORDER BY b.order_id ASC';
				$result = $_CLASS['core_db']->sql_query($sql);
				
				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$forum_id = $row['forum_id'];
					$topic_id = $row['b_topic_id'];

					$replies = ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];
					
					// Get folder img, topic status/type related informations
					$folder_img = $folder_alt = $topic_type = '';
					$unread_topic = topic_status($row, $replies, time(), time(), $folder_img, $folder_alt, $topic_type);

					$view_topic_url = getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id");
//					$last_post_img = '<a href="'.getlink("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;p=" . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id']) . '">' . $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST') . '</a>';

					$_CLASS['core_template']->assign_vars_array('forummarks', array(
						'FORUM_ID' 			=> $forum_id,
						'TOPIC_ID' 			=> $topic_id,
						'S_DELETED_TOPIC'	=> (!$row['topic_id']) ? true : false,
						'TOPIC_TITLE' 		=> censor_text($row['topic_title']),
						'TOPIC_TYPE' 		=> $topic_type,
						'FORUM_NAME'		=> $row['forum_name'],

						'TOPIC_AUTHOR' 		=> topic_topic_author($row),
						'FIRST_POST_TIME' 	=> $_CLASS['core_user']->format_date($row['topic_time']),
						'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
						'LAST_VIEW_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_view_time']),
						'LAST_POST_AUTHOR' 	=> ($row['topic_last_poster_name'] != '') ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],
						'PAGINATION' 		=> topic_generate_pagination($replies, 'Forums&amp;file=viewtopic&amp;f=' . (($row['forum_id']) ? $row['forum_id'] : $forum_id) . "&amp;t=$topic_id"),
				

						'POSTED_AT'			=> $_CLASS['core_user']->format_date($row['topic_time']),
						'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
						'ATTACH_ICON_IMG'	=> ($_CLASS['auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', '') : '',

						'U_VIEW_TOPIC'		=> $view_topic_url,
						'U_VIEW_FORUM'		=> getlink('Forums&amp;file=viewforum&amp;f='.$forum_id),
						'U_MOVE_UP'			=> ($row['order_id'] != 1) ? getlink("Control_Panel&amp;i=main&amp;mode=bookmarks&amp;move_up={$row['order_id']}") : '',
						'U_MOVE_DOWN'		=> ($row['order_id'] != $max_order_id) ? getlink("Control_Panel&amp;i=main&amp;mode=bookmarks&amp;move_down={$row['order_id']}") : '')
					);
				}
				
				$_CLASS['core_template']->assign(array(
					'L_BOOKMARKS_FORUMS'		=> $_CLASS['core_user']->lang['BOOKMARKS_FORUMS'],
					'L_BOOKMARKS_SITE'			=> $_CLASS['core_user']->lang['BOOKMARKS_SITE'],
					'L_BOOKMARKS_DISABLED'		=> $_CLASS['core_user']->lang['BOOKMARKS_DISABLED'])
				);
					
				break;

			case 'drafts':
				global $ucp;
				
				$pm_drafts = ($ucp->name == 'pm') ? true : false;

				$_CLASS['core_user']->add_lang('posting','Forums');

				$edit = (isset($_REQUEST['edit'])) ? true : false;
				$submit = (isset($_POST['submit'])) ? true : false;
				$draft_id = ($edit) ? intval($_REQUEST['edit']) : 0;
				$delete = (isset($_POST['delete'])) ? true : false;

				$s_hidden_fields = ($edit) ? '<input type="hidden" name="edit" value="' . $draft_id . '" />' : '';
				$draft_subject = $draft_message = '';

				if ($delete)
				{
					$drafts = (isset($_POST['d'])) ? implode(', ', array_map('intval', array_keys($_POST['d']))) : '';

					if ($drafts)
					{
						$sql = 'DELETE FROM ' . DRAFTS_TABLE . "
							WHERE draft_id IN ($drafts) 
								AND user_id = " .$_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);

						$message = $_CLASS['core_user']->lang['DRAFTS_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel&amp;i=$id&amp;mode=$mode").'">', '</a>');

						$_CLASS['core_display']->meta_refresh(3, getlink("Control_Panel&amp;i=$id&amp;mode=$mode"));
						trigger_error($message);
					}
				}

				if ($submit && $edit)
				{
					$draft_subject = preg_replace('#&amp;(\#[0-9]+;)#', '&\1', request_var('subject', ''));
					$draft_message = (isset($_POST['message'])) ? htmlspecialchars(trim(str_replace(array('\\\'', '\\"', '\\0', '\\\\'), array('\'', '"', '\0', '\\'), $_POST['message']))) : '';
					$draft_message = preg_replace('#&amp;(\#[0-9]+;)#', '&\1', $draft_message);

					if ($draft_message && $draft_subject)
					{
						$draft_row = array(
							'draft_subject' => $draft_subject,
							'draft_message' => $draft_message
						);

						$sql = 'UPDATE ' . DRAFTS_TABLE . ' 
							SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $draft_row) . " 
							WHERE draft_id = $draft_id
								AND user_id = " . $_CLASS['core_user']->data['user_id'];
						$_CLASS['core_db']->sql_query($sql);

						$message = $_CLASS['core_user']->lang['DRAFT_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.getlink("Control_Panel&amp;i=$id&amp;mode=$mode").'">', '</a>');

						$_CLASS['core_display']->meta_refresh(3, getlink("Control_Panel&amp;i=$id&amp;mode=$mode"));
						trigger_error($message);
					}
					else
					{
						$_CLASS['core_template']->assign('ERROR', ($draft_message == '') ? $_CLASS['core_user']->lang['EMPTY_DRAFT'] : (($draft_subject == '') ? $_CLASS['core_user']->lang['EMPTY_DRAFT_TITLE'] : ''));
					}
				}

				if (!$pm_drafts)
				{
					$sql = 'SELECT d.*, f.forum_name
						FROM ' . DRAFTS_TABLE . ' d, ' . FORUMS_TABLE . ' f
						WHERE d.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' ' .
							(($edit) ? "AND d.draft_id = $draft_id" : '') . '
							AND f.forum_id = d.forum_id
							ORDER BY d.save_time DESC';
				}
				else
				{
					$sql = 'SELECT * FROM ' . DRAFTS_TABLE . '
						WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . ' ' .
							(($edit) ? "AND draft_id = $draft_id" : '') . '
							AND forum_id = 0 
							AND topic_id = 0
							ORDER BY save_time DESC';
				}
				$result = $_CLASS['core_db']->sql_query($sql);
				
				$draftrows = $topic_ids = array();

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					if ($row['topic_id'])
					{
						$topic_ids[] = (int) $row['topic_id'];
					}
					$draftrows[] = $row;
				}
				$_CLASS['core_db']->sql_freeresult($result);
				
				if (sizeof($topic_ids))
				{
					$sql = 'SELECT topic_id, forum_id, topic_title
						FROM ' . TOPICS_TABLE . '
						WHERE topic_id IN (' . implode(',', array_unique($topic_ids)) . ')';
					$result = $_CLASS['core_db']->sql_query($sql);

					while ($row = $_CLASS['core_db']->sql_fetchrow($result))
					{
						$topic_rows[$row['topic_id']] = $row;
					}
					$_CLASS['core_db']->sql_freeresult($result);
				}
				unset($topic_ids);
				
				$_CLASS['core_template']->assign('S_EDIT_DRAFT', $edit);

				foreach ($draftrows as $draft)
				{
					$link_topic = $link_forum = $link_pm = false;
					$insert_url = $view_url = $title = '';

					if ($pm_drafts)
					{
						$link_pm = true;
						$insert_url = getlink("Control_Panel&amp;i=$id&amp;mode=compose&amp;d=" . $draft['draft_id']);
					}
					else if (isset($topic_rows[$draft['topic_id']]) && $_CLASS['auth']->acl_get('f_read', $topic_rows[$draft['topic_id']]['forum_id']))
					{
						$link_topic = true;
						$view_url = getlink('Forums&amp;file=viewtopic&amp;f=' . $topic_rows[$draft['topic_id']]['forum_id'] . "&amp;t=" . $draft['topic_id']);
						$title = $topic_rows[$draft['topic_id']]['topic_title'];

						$insert_url = getlink('Forums&amp;file=posting&amp;f=' . $topic_rows[$draft['topic_id']]['forum_id'] . '&amp;t=' . $draft['topic_id'] . '&amp;mode=reply&amp;d=' . $draft['draft_id']);
					}
					else if ($_CLASS['auth']->acl_get('f_read', $draft['forum_id']))
					{
						$link_forum = true;
						$view_url = getlink('Forums&amp;file=viewforum&amp;f=' . $draft['forum_id']);
						$title = $draft['forum_name'];

						$insert_url = getlink('Forums&amp;file=posting&amp;f=' . $draft['forum_id'] . '&amp;mode=post&amp;d=' . $draft['draft_id']);
					}
						
					$template_row = array(
						'DATE'			=> $_CLASS['core_user']->format_date($draft['save_time']),
						'DRAFT_MESSAGE'	=> ($submit) ? $draft_message : $draft['draft_message'],
						'DRAFT_SUBJECT'	=> ($submit) ? $draft_subject : $draft['draft_subject'],
						'TITLE'			=> $title,
						
						'L_SAVE_DATE'	 	=> $_CLASS['core_user']->lang['SAVE_DATE'],
						'L_DRAFT_TITLE' 	=> $_CLASS['core_user']->lang['DRAFT_TITLE'],
						'L_YOUR_DETAILS' 	=> $_CLASS['core_user']->lang['YOUR_DETAILS'],
						'L_JOINED' 			=> $_CLASS['core_user']->lang['JOINED'],
						'L_TOTAL_POSTS' 	=> $_CLASS['core_user']->lang['TOTAL_POSTS'],
						
						'DRAFT_ID'			=> $draft['draft_id'],
						'FORUM_ID'			=> $draft['forum_id'],
						'TOPIC_ID'			=> $draft['topic_id'],
						
						'L_FONT_SIZE'		=> $_CLASS['core_user']->lang['FONT_SIZE'],
						'L_FONT_TINY'		=> $_CLASS['core_user']->lang['FONT_TINY'],
						'L_FONT_SMALL'		=> $_CLASS['core_user']->lang['FONT_SMALL'],
						'L_FONT_NORMAL'		=> $_CLASS['core_user']->lang['FONT_NORMAL'],
						'L_FONT_LARGE'		=> $_CLASS['core_user']->lang['FONT_LARGE'],
						'L_FONT_HUGE'		=> $_CLASS['core_user']->lang['FONT_HUGE'],
						'L_CLOSE_TAGS'		=> $_CLASS['core_user']->lang['CLOSE_TAGS'],

						'U_VIEW'			=> $view_url,
						'U_VIEW_EDIT'		=> getlink("Control_Panel&amp;i=$id&amp;mode=$mode&amp;edit=" . $draft['draft_id']),
						'U_INSERT'			=> $insert_url,

						'S_LINK_TOPIC'		=> $link_topic,
						'S_LINK_FORUM'		=> $link_forum,
						'S_LINK_PM'			=> $link_pm,
						'S_HIDDEN_FIELDS'	=> $s_hidden_fields
					);
						
					($edit) ? $_CLASS['core_template']->assign($template_row) : $_CLASS['core_template']->assign_vars_array('draftrow', $template_row);
				}

				$_CLASS['core_template']->assign(array(
					'L_SAVE_DATE'	 		=> $_CLASS['core_user']->lang['SAVE_DATE'],
					'L_DRAFT_TITLE' 		=> $_CLASS['core_user']->lang['DRAFT_TITLE'],
					'L_LOAD_DRAFT' 			=> $_CLASS['core_user']->lang['LOAD_DRAFT'],
					'L_JOINED' 				=> $_CLASS['core_user']->lang['JOINED'],
					)
				);
				
				break;
		}


		$_CLASS['core_template']->assign(array( 
			'L_TITLE'					=> $_CLASS['core_user']->lang['UCP_MAIN_' . strtoupper($mode)],
			'S_DISPLAY_MARK_ALL'		=> ($mode == 'watched' || ($mode == 'drafts' && !isset($_GET['edit']))) ? true : false, 
			'S_HIDDEN_FIELDS'			=> (isset($s_hidden_fields)) ? $s_hidden_fields : '',
			'S_UCP_ACTION'				=> getlink("Control_Panel&amp;i=$id&amp;mode=$mode"),
			'L_UCP' 					=> $_CLASS['core_user']->lang['UCP'],
			'L_UCP_WELCOME' 			=> $_CLASS['core_user']->lang['UCP_WELCOME'],
			'L_IMPORTANT_NEWS'	 		=> $_CLASS['core_user']->lang['IMPORTANT_NEWS'],
			'L_NO_IMPORTANT_NEWS' 		=> $_CLASS['core_user']->lang['NO_IMPORTANT_NEWS'],
			'L_YOUR_DETAILS' 			=> $_CLASS['core_user']->lang['YOUR_DETAILS'],
			'L_JOINED' 					=> $_CLASS['core_user']->lang['JOINED'],
			'L_TOTAL_POSTS' 			=> $_CLASS['core_user']->lang['TOTAL_POSTS'],
			'L_ACTIVE_IN_FORUM' 		=> (isset($_CLASS['core_user']->lang['ACTIVE_IN_FORUM'])) ? $_CLASS['core_user']->lang['ACTIVE_IN_FORUM'] : '',
			'L_ACTIVE_IN_TOPIC' 		=> (isset($_CLASS['core_user']->lang['ACTIVE_IN_TOPIC'])) ? $_CLASS['core_user']->lang['ACTIVE_IN_TOPIC'] : '',
			'L_SEARCH_USER_POSTS' 		=> (isset($_CLASS['core_user']->lang['SEARCH_USER_POSTS'])) ? $_CLASS['core_user']->lang['SEARCH_USER_POSTS'] : '',
			'L_YOUR_DETAILS' 			=> $_CLASS['core_user']->lang['YOUR_DETAILS'],
			'L_WATCHED_FORUMS' 			=> $_CLASS['core_user']->lang['WATCHED_FORUMS'],
			'L_NO_POSTS' 				=> $_CLASS['core_user']->lang['NO_POSTS'],
			'L_NO_WATCHED_FORUMS'	 	=> $_CLASS['core_user']->lang['NO_WATCHED_FORUMS'],
			'L_WATCHED_TOPICS' 			=> $_CLASS['core_user']->lang['WATCHED_TOPICS'],
			'L_NO_WATCHED_TOPICS'	 	=> $_CLASS['core_user']->lang['NO_WATCHED_TOPICS'],
			'L_UNWATCH_MARKED' 			=> $_CLASS['core_user']->lang['UNWATCH_MARKED'],
			'L_FORUM' 					=> $_CLASS['core_user']->lang['FORUM'],
			'L_POSTED' 					=> $_CLASS['core_user']->lang['POSTED'],
			'L_MOVE_UP' 				=> $_CLASS['core_user']->lang['MOVE_UP'],
			'L_MOVE_DOWN' 				=> $_CLASS['core_user']->lang['MOVE_DOWN'],
			'L_NO_BOOKMARKS' 			=> $_CLASS['core_user']->lang['NO_BOOKMARKS'],
			'L_REMOVE_BOOKMARK_MARKED' 	=> $_CLASS['core_user']->lang['REMOVE_BOOKMARK_MARKED'],
			'L_NO_SAVED_DRAFTS' 		=> $_CLASS['core_user']->lang['NO_SAVED_DRAFTS'],
			'L_DRAFTS_EXPLAIN'	 		=> $_CLASS['core_user']->lang['DRAFTS_EXPLAIN'],
			'L_OPTIONS' 				=> $_CLASS['core_user']->lang['OPTIONS'],
			'L_PRIVATE_MESSAGE' 		=> $_CLASS['core_user']->lang['PRIVATE_MESSAGE'],
			'L_VIEW_EDIT' 				=> $_CLASS['core_user']->lang['VIEW_EDIT'],
			'L_SUBJECT' 				=> $_CLASS['core_user']->lang['SUBJECT'],
			'L_MESSAGE' 				=> $_CLASS['core_user']->lang['MESSAGE'],
			'L_EDIT_DRAFT_EXPLAIN'		=> $_CLASS['core_user']->lang['EDIT_DRAFT_EXPLAIN'],
			'L_BACK_TO_DRAFTS' 			=> $_CLASS['core_user']->lang['BACK_TO_DRAFTS'],
			'L_SUBMIT' 					=> $_CLASS['core_user']->lang['SUBMIT'],
			'L_RESET' 					=> $_CLASS['core_user']->lang['RESET'],
			'L_MARK_ALL' 				=> $_CLASS['core_user']->lang['MARK_ALL'],
			'L_UNMARK_ALL' 				=> $_CLASS['core_user']->lang['UNMARK_ALL'],
			'L_DELETE' 					=> $_CLASS['core_user']->lang['DELETE'],
			'L_DELETE_MARKED' 			=> $_CLASS['core_user']->lang['DELETE_MARKED'])
		);
		
		$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_main_' . $mode . '.html');
	}

	function install()
	{
	}

	function uninstall()
	{
	}

	function module()
	{
		$details = array(
			'name'			=> 'UCP - Main',
			'description'	=> 'Front end for User Control Panel', 
			'filename'		=> 'main',
			'version'		=> '1.0.0', 
			'phpbbversion'	=> '2.2.0'
		);
		return $details;
	}
}

?>