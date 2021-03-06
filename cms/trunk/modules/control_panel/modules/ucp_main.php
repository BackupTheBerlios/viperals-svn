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

global $_CLASS, $_CORE_CONFIG;

$_CLASS['core_template']->assign_array(array(
	'ERROR' 		=> false,
	'topicrow'		=> false,
	'WARNINGS'		=> false,
	'draftrow'		=> false
));

$s_hidden_fields = '';

$_CLASS['core_user']->user_setup();

switch ($this->mode)
{
	case 'subscribed':
		$_CLASS['core_user']->add_img(false, 'forums');

		require_once SITE_FILE_ROOT.'includes/forums/functions.php';
		load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
		
		$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);
		require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';

		$unwatch = isset($_POST['unwatch']);
		
		if ($unwatch)
		{
			$forums = array_unique(get_variable('f', 'POST', array(), 'array:int'));
			$topics = array_unique(get_variable('t', 'POST', array(), 'array:int'));

			if (!empty($forums) || !empty($topics))
			{
				$l_unwatch = '';

				if (!empty($forums))
				{
					$sql = 'DELETE FROM ' . FORUMS_WATCH_TABLE . '
						WHERE forum_id IN ('.implode(', ', $forums).') AND topic_id = 0
							AND user_id = ' .$_CLASS['core_user']->data['user_id'];
					$_CLASS['core_db']->query($sql);

					$l_unwatch .= '_FORUMS';
				}

				if (!empty($topics))
				{
					$sql = 'DELETE FROM ' . FORUMS_WATCH_TABLE . '
						WHERE topic_id IN ('.implode(', ', $topics) .')
							AND user_id = ' .$_CLASS['core_user']->data['user_id'];
					$_CLASS['core_db']->query($sql);

					$l_unwatch .= '_TOPICS';
				}

				$message = $_CLASS['core_user']->lang['UNWATCHED' . $l_unwatch] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'. generate_link($this->link_parent.'&amp;mode=subscribed').'">', '</a>');
				
				$_CLASS['core_display']->meta_refresh(3, generate_link($this->link_parent.'&amp;mode=subscribed'));
				trigger_error($message);
			}
		}

		if ($config['load_db_lastread'])
		{
			$sql_from = FORUMS_FORUMS_TABLE . ' f  LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
							AND ft.forum_id = f.forum_id AND ft.topic_id = 0)';
			$lastread_select = ', ft.mark_time ';
		}
		else
		{
			$sql_from = FORUMS_FORUMS_TABLE . ' f ';
			$lastread_select = '';

			$tracking = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));

			if (!is_array($tracking))
			{
				$tracking = array();
			}
		}

		$sql = "SELECT f.*$lastread_select 
			FROM $sql_from, " . FORUMS_WATCH_TABLE . ' fw
			WHERE fw.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
				 AND fw.topic_id = 0 AND f.forum_id = fw.forum_id
			ORDER BY left_id';

		$result = $_CLASS['core_db']->query($sql);
		//$topics_count = $_CLASS['core_db']->num_rows($result);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_id = (int) $row['forum_id'];

			$unread_forum = false;
			
			if ($config['load_db_lastread'])
			{
				$mark_time_forum = $row['mark_time'];
			}
			else
			{
				$forum_id36 = base_convert($forum_id, 10, 36);
				$mark_time_forum = isset($tracking[$forum_id36][0]) ? (int) base_convert($tracking[$forum_id36][0], 36, 10) : 0;
			}

			if ($mark_time_forum < $row['forum_last_post_time'])
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
				$last_poster_url = ($row['forum_last_poster_id'] == ANONYMOUS) ? '' : generate_link('members_list&amp;mode=viewprofile&amp;u='  . $row['forum_last_poster_id']);

				$last_post_url = generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;p=" . $row['forum_last_post_id'] . '#' . $row['forum_last_post_id']);
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
				'U_VIEWFORUM'		=> generate_link('forums&amp;file=viewforum&amp;f=' . $row['forum_id']))
			);
		}
		$_CLASS['core_db']->free_result($result);

		// Subscribed Topics
		$start = get_variable('start', 'REQUEST', 0, 'int');

		if ($config['load_db_lastread'])
		{
			$sql_from = FORUMS_TOPICS_TABLE . ' t LEFT JOIN ' . FORUMS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id AND tt.user_id = ' . $_CLASS['core_user']->data['user_id'] . ')';
			$sql_t_select = ', tt.mark_time';
		}
		else
		{
			$sql_from = FORUMS_TOPICS_TABLE . ' t';
			$sql_t_select = '';
		}

		$sql = "SELECT t.* $sql_t_select 
			FROM ". FORUMS_WATCH_TABLE . " tw, $sql_from 
			WHERE tw.user_id = " . $_CLASS['core_user']->data['user_id'] . '
				AND t.topic_id = tw.topic_id 
			ORDER BY t.topic_last_post_time DESC';

		$result = $_CLASS['core_db']->query_limit($sql, $config['topics_per_page'], $start);
		$topics_count = $_CLASS['core_db']->num_rows($result);

		if ($topics_count)
		{
			$pagination = generate_pagination($this->link, $topics_count, $config['topics_per_page'], $start);

			$_CLASS['core_template']->assign_array(array(
				'PAGINATION'		=> $pagination['formated'],
				'PAGINATION_ARRAY'	=> $pagination['array'],
				'PAGE_NUMBER'		=> on_page($topics_count, $config['topics_per_page'], $start),
				'TOTAL_TOPICS'		=> ($topics_count === 1) ? $_CLASS['core_user']->lang['VIEW_FORUM_TOPIC'] : sprintf($_CLASS['core_user']->lang['VIEW_FORUM_TOPICS'], $topics_count)
			));
		}
		else
		{
			$_CLASS['core_template']->assign('TOTAL_TOPICS', false);
		}

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$topic_id = (int) $row['topic_id'];
			$forum_id = (int) $row['forum_id'];
			
			if (!$config['load_db_lastread'])
			{
				$topic_id36 = base_convert($topic_id, 10, 36);
				$forum_id36 = ($row['topic_type'] == POST_GLOBAL) ? 0 : base_convert($forum_id, 10, 36);

				$mark_time_topic = isset($tracking[$forum_id36][$topic_id36]) ? (int) base_convert($tracking[$forum_id36][$topic_id36], 36, 10) : 0;
				$mark_time_forum = isset($tracking[$forum_id36][0]) ? (int) base_convert($tracking[$forum_id36][0], 36, 10) : 0;

				$row['mark_time'] = max($mark_time_topic, $mark_time_forum);
			}

			// Replies
			$replies = $_CLASS['forums_auth']->acl_get('m_approve', $forum_id) ? $row['topic_replies_real'] : $row['topic_replies'];

			if ($row['topic_status'] == ITEM_MOVED)
			{
				$topic_id = $row['topic_moved_id'];
			}

			// Get folder img, topic status/type related informations
			$folder_img = $folder_alt = $topic_type = '';

			topic_status($row, $replies, $row['mark_time'], $unread_topic, $folder_img, $folder_alt, $topic_type);
			$newest_post_img = ($unread_topic) ? '<a href="'. generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;view=unread#unread").'">' . $_CLASS['core_user']->img('icon_post_newest', 'VIEW_NEWEST_POST') . '</a> ' : '';

			$view_topic_url = 'forums&amp;file=viewtopic&amp;t='.$topic_id;
			$pagination = generate_pagination($view_topic_url, $replies, $config['topics_per_page'], 0);

			$_CLASS['core_template']->assign_vars_array('topicrow', array(
				'FORUM_ID' 			=> $forum_id,
				'TOPIC_ID' 			=> $topic_id,

				'TOPIC_AUTHOR' 		=> ($row['topic_poster'] == ANONYMOUS) ? (($row['topic_first_poster_name']) ? $row['topic_first_poster_name'] : $_CLASS['core_user']->get_lang('GUEST')) : $row['topic_first_poster_name'],
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

				'LAST_POST_IMG' 	=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'),
				'NEWEST_POST_IMG' 	=> $newest_post_img,
				'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
				'TOPIC_ICON_IMG'	=> empty($icons[$row['icon_id']]) ? '' : '<img src="' . $config['icons_path'] . '/' . $icons[$row['icon_id']]['img'] . '" width="' . $icons[$row['icon_id']]['width'] . '" height="' . $icons[$row['icon_id']]['height'] . '" alt="" title="" />',
				'ATTACH_ICON_IMG'	=> ($_CLASS['forums_auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', sprintf($_CLASS['core_user']->lang['TOTAL_ATTACHMENTS'], $row['topic_attachment'])) : '',

				'S_TOPIC_TYPE'		=> $row['topic_type'],
				'S_UNREAD_TOPIC'	=> $unread_topic,

				'U_LAST_POST'		=> generate_link($view_topic_url .  '&amp;p=' . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id']),
				'U_LAST_POST_AUTHOR'=> ($row['topic_last_poster_id'] && $row['topic_last_poster_id'] != ANONYMOUS) ? generate_link('members_list&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']) : '',
				'U_VIEW_TOPIC'		=> generate_link($view_topic_url))
			);
			
		}
		$_CLASS['core_db']->free_result($result);
	break;

	case 'bookmarks':
		require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';

		$move_up = request_var('move_up', 0);
		$move_down = request_var('move_down', 0);

		$sql = 'SELECT MAX(order_id) as max_order_id FROM ' . FORUMS_BOOKMARKS_TABLE . '
			WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
		$result = $_CLASS['core_db']->query($sql);
		list($max_order_id) = $_CLASS['core_db']->fetch_row_num($result);
		$_CLASS['core_db']->free_result($result);

		if ($move_up || $move_down)
		{
			if (($move_up && $move_up != 1) || ($move_down && $move_down != $max_order_id))
			{
				$order = ($move_up) ? $move_up : $move_down;
				$order_total = $order * 2 + (($move_up) ? -1 : 1);

				$sql = 'UPDATE ' . FORUMS_BOOKMARKS_TABLE . "
					SET order_id = $order_total - order_id
					WHERE order_id IN ($order, " . (($move_up) ? $order - 1 : $order + 1) . ')
						AND user_id = ' . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->query($sql);
			}
		}
		
		if (isset($_POST['unbookmark']))
		{
			$topics = array_unique(get_variable('t', 'POST', array(), 'array:int'));
			
			if (empty($topics))
			{
				trigger_error('NO_BOOKMARKS_SELECTED');
			}

			$hidden_fields = array(
				'unbookmark' => 1,
				't' => $topics
			);

			if (display_confirmation($_CLASS['core_user']->get_lang('REMOVE_SELECTED_BOOKMARKS'), generate_hidden_fields($hidden_fields)))
			{
				$sql = 'DELETE FROM ' . FORUMS_BOOKMARKS_TABLE . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
						AND topic_id IN (' . implode(', ', $topics) . ')';
				$_CLASS['core_db']->query($sql);

				$sql = 'SELECT topic_id FROM ' . FORUMS_BOOKMARKS_TABLE . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
					ORDER BY order_id ASC';
				$result = $_CLASS['core_db']->query($sql);

				$i = 1;
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$_CLASS['core_db']->query('UPDATE ' . FORUMS_BOOKMARKS_TABLE . "
						SET order_id = '$i'
						WHERE topic_id = '{$row['topic_id']}'
							AND user_id = '{$_CLASS['core_user']->data['user_id']}'");
					$i++;
				}
				$_CLASS['core_db']->free_result($result);

				$url = generate_link('control_panel&amp;i=main&amp;mode=bookmarks');

				$_CLASS['core_display']->meta_refresh(3, $url);
				$message = $_CLASS['core_user']->lang['BOOKMARKS_REMOVED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="' . $url . '">', '</a>');
				trigger_error($message);
			}
		}

		// We grab deleted topics here too...
		// NOTE: At the moment bookmarks are not removed with topics, might be useful later (not really sure how though. :D)
		// But since bookmarks are sensible to the user, they should not be deleted without notice.
		$sql = 'SELECT b.order_id, b.topic_id as b_topic_id, t.*, f.forum_name
			FROM ' . FORUMS_BOOKMARKS_TABLE . ' b
				LEFT JOIN ' . FORUMS_TOPICS_TABLE . ' t ON b.topic_id = t.topic_id
				LEFT JOIN ' . FORUMS_FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
			WHERE b.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
			ORDER BY b.order_id ASC';
		$result = $_CLASS['core_db']->query($sql);
		
		if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
		{
			$_CLASS['core_db']->free_result($result);
			
			$_CLASS['core_template']->assign_array(array(
					'S_BOOKMARKS'			=> false,
					'S_BOOKMARKS_DISABLED'	=> false
			));
			break;
		}

		$bookmarks = true;
		
		do
		{
			$forum_id = $row['forum_id'];
			$topic_id = $row['b_topic_id'];
			$bookmarks = true;

			$replies = ($_CLASS['forums_auth']->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];
			
			// Get folder img, topic status/type related informations
			$folder_img = $folder_alt = $topic_type = $unread_topic = '';
			topic_status($row, $replies, $_CLASS['core_user']->time, $unread_topic, $folder_img, $folder_alt, $topic_type);

			$view_topic_url = "forums&amp;file=viewtopic&amp;t=$topic_id";
//					$last_post_img = '<a href="'.generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;p=" . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id']) . '">' . $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST') . '</a>';

			$pagination = generate_pagination('forums&amp;file=viewtopic&amp;t='.$topic_id, $replies, $config['posts_per_page'], 0);

			$_CLASS['core_template']->assign_vars_array('forummarks', array(
				'FORUM_ID' 			=> $forum_id,
				'TOPIC_ID' 			=> $topic_id,
				'S_DELETED_TOPIC'	=> (!$row['topic_id']) ? true : false,
				'TOPIC_TITLE' 		=> censor_text($row['topic_title']),
				'FORUM_NAME'		=> $row['forum_name'],

				'TOPIC_AUTHOR' 		=> ($row['topic_poster'] == ANONYMOUS) ? (($row['topic_first_poster_name']) ? $row['topic_first_poster_name'] : $_CLASS['core_user']->get_lang('GUEST')) : $row['topic_first_poster_name'],
				'LINK_AUTHOR' 		=> ($row['topic_poster'] == ANONYMOUS) ? '' : generate_link('members_list&amp;mode=viewprofile&amp;u=' . $row['topic_poster']),

				'FIRST_POST_TIME' 	=> $_CLASS['core_user']->format_date($row['topic_time']),
				'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
				'LAST_VIEW_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_view_time']),
				'LAST_POST_AUTHOR' 	=> ($row['topic_last_poster_name']) ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],
				'LAST_POST_IMG' 	=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'),

				'PAGINATION'		=> $pagination['formated'],
				'PAGINATION_ARRAY'	=> $pagination['array'],

				'POSTED_AT'			=> $_CLASS['core_user']->format_date($row['topic_time']),
				'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
				'ATTACH_ICON_IMG'	=> ($_CLASS['forums_auth']->acl_gets('f_download', 'u_download', $forum_id) && $row['topic_attachment']) ? $_CLASS['core_user']->img('icon_attach', '') : '',

				'U_VIEW_TOPIC'		=> generate_link($view_topic_url),
				'U_VIEW_FORUM'		=> generate_link('forums&amp;file=viewforum&amp;f='.$forum_id),
				
				'U_LAST_POST'		=> generate_link($view_topic_url .  '&amp;p=' . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id']),
				'U_LAST_POST_AUTHOR'=> ($row['topic_last_poster_id'] != ANONYMOUS) ? generate_link('members_list&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']) : '',
				'U_MOVE_UP'			=> ($row['order_id'] != 1) ? generate_link("control_panel&amp;i=main&amp;mode=bookmarks&amp;move_up={$row['order_id']}") : '',
				'U_MOVE_DOWN'		=> ($row['order_id'] != $max_order_id) ? generate_link("control_panel&amp;i=main&amp;mode=bookmarks&amp;move_down={$row['order_id']}") : '')
			);
		}
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign_array(array(
			'S_BOOKMARKS'			=> $bookmarks,
			'S_BOOKMARKS_DISABLED'	=> false
		));
	break;

	case 'drafts':
		global $ucp;
		
		$pm_drafts = ($this->module === 'pm') ? true : false;

		$_CLASS['core_user']->add_lang('posting','forums');

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
				$sql = 'DELETE FROM ' . FORUMS_DRAFTS_TABLE . "
					WHERE draft_id IN ($drafts) 
						AND user_id = " .$_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->query($sql);

				$message = $_CLASS['core_user']->lang['DRAFTS_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link($this->link).'">', '</a>');

				$_CLASS['core_display']->meta_refresh(3, generate_link($this->link));
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

				$sql = 'UPDATE ' . FORUMS_DRAFTS_TABLE . ' 
					SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $draft_row) . " 
					WHERE draft_id = $draft_id
						AND user_id = " . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->query($sql);

				$message = $_CLASS['core_user']->lang['DRAFT_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link($this->link).'">', '</a>');

				$_CLASS['core_display']->meta_refresh(3, generate_link($this->link));
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
				FROM ' . FORUMS_DRAFTS_TABLE . ' d, ' . FORUMS_FORUMS_TABLE . ' f
				WHERE d.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' ' .
					(($edit) ? "AND d.draft_id = $draft_id" : '') . '
					AND f.forum_id = d.forum_id
					ORDER BY d.save_time DESC';
		}
		else
		{
			$sql = 'SELECT * FROM ' . FORUMS_DRAFTS_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . ' ' .
					(($edit) ? "AND draft_id = $draft_id" : '') . '
					AND forum_id = 0 
					AND topic_id = 0
					ORDER BY save_time DESC';
		}
		$result = $_CLASS['core_db']->query($sql);
		
		$draftrows = $topic_ids = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['topic_id'])
			{
				$topic_ids[] = (int) $row['topic_id'];
			}
			$draftrows[] = $row;
		}
		$_CLASS['core_db']->free_result($result);
		
		if (sizeof($topic_ids))
		{
			$sql = 'SELECT topic_id, forum_id, topic_title
				FROM ' . FORUMS_TOPICS_TABLE . '
				WHERE topic_id IN (' . implode(',', array_unique($topic_ids)) . ')';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_rows[$row['topic_id']] = $row;
			}
			$_CLASS['core_db']->free_result($result);
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
				$insert_url = generate_link($this->link_parent.'&amp;mode=compose&amp;d=' . $draft['draft_id']);
			}
			else if (isset($topic_rows[$draft['topic_id']]) && $_CLASS['forums_auth']->acl_get('f_read', $topic_rows[$draft['topic_id']]['forum_id']))
			{
				$link_topic = true;
				$view_url = generate_link('forums&amp;file=viewtopic&amp;f=' . $topic_rows[$draft['topic_id']]['forum_id'] . "&amp;t=" . $draft['topic_id']);
				$title = $topic_rows[$draft['topic_id']]['topic_title'];

				$insert_url = generate_link('forums&amp;file=posting&amp;f=' . $topic_rows[$draft['topic_id']]['forum_id'] . '&amp;t=' . $draft['topic_id'] . '&amp;mode=reply&amp;d=' . $draft['draft_id']);
			}
			else if ($_CLASS['forums_auth']->acl_get('f_read', $draft['forum_id']))
			{
				$link_forum = true;
				$view_url = generate_link('forums&amp;file=viewforum&amp;f=' . $draft['forum_id']);
				$title = $draft['forum_name'];

				$insert_url = generate_link('forums&amp;file=posting&amp;f=' . $draft['forum_id'] . '&amp;mode=post&amp;d=' . $draft['draft_id']);
			}
				
			$template_row = array(
				'DATE'			=> $_CLASS['core_user']->format_date($draft['save_time']),
				'DRAFT_MESSAGE'	=> ($submit) ? $draft_message : $draft['draft_message'],
				'DRAFT_SUBJECT'	=> ($submit) ? $draft_subject : $draft['draft_subject'],
				'TITLE'			=> $title,

				'DRAFT_ID'			=> $draft['draft_id'],
				'FORUM_ID'			=> $draft['forum_id'],
				'TOPIC_ID'			=> $draft['topic_id'],

				'U_VIEW'			=> $view_url,
				'U_VIEW_EDIT'		=> generate_link($this->link.'&amp;edit=' . $draft['draft_id']),
				'U_INSERT'			=> $insert_url,

				'S_LINK_TOPIC'		=> $link_topic,
				'S_LINK_FORUM'		=> $link_forum,
				'S_LINK_PM'			=> $link_pm,
				'S_HIDDEN_FIELDS'	=> $s_hidden_fields
			);
				
			($edit) ? $_CLASS['core_template']->assign_array($template_row) : $_CLASS['core_template']->assign_vars_array('draftrow', $template_row);
		}

	break;

	default:
	//case 'front':
		$this->mode = 'front';

// remove
		$_CLASS['core_user']->add_lang(false, 'members_list');

/*
		if ($config['load_db_lastread'])
		{
			if ($config['load_db_lastread'])
			{
				$sql = 'SELECT mark_time 
					FROM ' . FORUMS_TRACK_TABLE . ' 
					WHERE forum_id = 0
						AND user_id = ' . $_CLASS['core_user']->data['user_id'];
				$result = $_CLASS['core_db']->query($sql);

				$track_data = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			}

			$sql_from = FORUMS_TOPICS_TABLE . ' t LEFT JOIN ' . FORUMS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id AND tt.user_id = ' . $_CLASS['core_user']->data['user_id'] . ')';
			$sql_select = ', tt.mark_time';

		}
		else
		{
			$sql_from = FORUMS_TOPICS_TABLE . ' t ';
			$sql_select = '';
		}

		// Has to be in while loop if we not only check forum id 0
		if ($config['load_db_lastread'])
		{
			$forum_check = $track_data['mark_time'];
		}
		else
		{
			$tracking_topics = (isset($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) ? unserialize(stripslashes($_COOKIE[$_CORE_CONFIG['server']['cookie_name'] . '_track'])) : array();
			$forum_check = (isset($tracking_topics[0][0])) ? base_convert($tracking_topics[0][0], 36, 10) + $config['board_startdate'] : 0;
		}

		$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_ANNOUNCEMENT'];
		$folder = 'folder_announce';
		$folder_new = $folder . '_new';

		$sql = "SELECT t.* $sql_select 
			FROM $sql_from
			WHERE t.forum_id = 0
				AND t.topic_type = " . POST_GLOBAL . '
			ORDER BY t.topic_last_post_time DESC';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
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

			$newest_post_img = ($unread_topic) ? '<a href="'.generate_link("forums&amp;file=viewtopic&amp;t=$topic_id&amp;view=unread#unread").'">' . $_CLASS['core_user']->img('icon_post_newest', 'VIEW_NEWEST_POST') . '</a> ' : '';
			$folder_img = ($unread_topic) ? $folder_new : $folder;
			$folder_alt = ($unread_topic) ? 'NEW_POSTS' : (($row['topic_status'] == ITEM_LOCKED) ? 'TOPIC_LOCKED' : 'NO_NEW_POSTS');

			// Posted image?
			$view_topic_url = generate_link("forums&amp;file=viewtopic&amp;&amp;t=$topic_id");

			$last_post_img = '<a href="'. generate_link("forums&amp;file=viewtopic&amp;t=$topic_id&amp;p=" . $row['topic_last_post_id'] . '#' . $row['topic_last_post_id']) . '">' . $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST') . '</a>';

			$_CLASS['core_template']->assign_vars_array('topicrow', array(
				'FORUM_ID'			=> $forum_id,
				'TOPIC_ID'			=> $topic_id,
				'GOTO_PAGE'			=> '',
				'LAST_POST_TIME'	=> $_CLASS['core_user']->format_date($row['topic_last_post_time']),
				'LAST_POST_AUTHOR'	=> ($row['topic_last_poster_name']) ? $row['topic_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'],

				'TOPIC_TITLE'		=> censor_text($row['topic_title']),
				'TOPIC_TYPE'		=> $topic_type,

				'LAST_POST_IMG'		=> $last_post_img,
				'NEWEST_POST_IMG'	=> $newest_post_img,
				'TOPIC_FOLDER_IMG' 	=> $_CLASS['core_user']->img($folder_img, $folder_alt),
				'ATTACH_ICON_IMG'	=> $row['topic_attachment'] ? $_CLASS['core_user']->img('icon_attach', '') : '',

				'U_LAST_POST_AUTHOR'	=> ($row['topic_last_poster_id'] != ANONYMOUS) ? generate_link('members_list&amp;mode=viewprofile&amp;u='  . $row['topic_last_poster_id']) : false,
				'U_VIEW_TOPIC'		=> $view_topic_url)
			);
		}
		$_CLASS['core_db']->free_result($result);
*/

		$_CLASS['core_template']->assign_array(array(
			'JOINED'			=> $_CLASS['core_user']->format_date($_CLASS['core_user']->data['user_reg_date']),
			'VISITED'			=> empty($_CLASS['core_user']->data['user_lastvisit']) ? ' - ' : $_CLASS['core_user']->format_date($_CLASS['core_user']->data['user_lastvisit']),
			'POSTS'				=> (int) $_CLASS['core_user']->data['user_posts'],

			'U_SEARCH_USER'		=> generate_link('forums&amp;file=search&amp;search_author=' . urlencode($_CLASS['core_user']->data['username']) . "&amp;show_results=posts"),  
		));
	break;
}


$_CLASS['core_template']->assign_array(array( 
	'L_TITLE'					=> $_CLASS['core_user']->lang['UCP_MAIN_' . strtoupper($this->mode)],
	'S_DISPLAY_MARK_ALL'		=> ($this->mode === 'watched' || ($this->mode === 'drafts' && !isset($_GET['edit']))) ? true : false, 
	'S_HIDDEN_FIELDS'			=> (isset($s_hidden_fields)) ? $s_hidden_fields : '',
	'S_DISPLAY_FORM'			=> true,
	'S_UCP_ACTION'				=> generate_link($this->link),
));

$_CLASS['core_display']->display(false, 'modules/control_panel/ucp_main_' . $this->mode . '.html');

?>