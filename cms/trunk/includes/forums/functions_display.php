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
// $Id: functions_display.php,v 1.59 2004/09/17 09:11:47 acydburn Exp $
//
// FILENAME  : functions_display.php
// STARTED   : Thu Nov 07, 2002
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW		 : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

function display_forums($root_data = '', $display_moderators = true)
{
	global $config, $_CLASS, $_CORE_CONFIG;

	// Get posted/get info
	$mark_read = request_var('mark', '');

	$forum_id_ary = $active_forum_ary = $forum_rows = $subforums = $forum_moderators = $mark_forums = array();
	$visible_forums = 0;

	if (!$root_data)
	{
		$root_data = array('forum_id' => 0);
		$sql_where = '';
	}
	else
	{
		$sql_where = 'AND left_id > ' . $root_data['left_id'] . ' AND left_id < ' . $root_data['right_id'];
	}

	// Display list of active topics for this category?
	$show_active = (isset($root_data['forum_flags']) && $root_data['forum_flags'] & 16) ? true : false;

	if ($_CLASS['core_user']->is_user &&  $config['load_db_lastread'])
	{
		$sql_from = ' LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
				AND ft.forum_id = f.forum_id AND ft.topic_id = 0)';
		$lastread_select = ', ft.mark_time ';
	}
	else
	{
		$sql_from = $lastread_select = $sql_lastread = '';
		$tracking_topics = @unserialize(get_variable($_CORE_CONFIG['server']['cookie_name'] . '_track', 'COOKIE'));
	}

	$sql = "SELECT f.* $lastread_select 
		FROM ". FORUMS_FORUMS_TABLE . " f $sql_from
		WHERE forum_status <> ".ITEM_DELETING."
		$sql_where
		ORDER BY f.left_id";
	$result = $_CLASS['core_db']->query($sql);

	$branch_root_id = $root_data['forum_id'];
	$forum_ids		= array($root_data['forum_id']);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($mark_read == 'forums' && $_CLASS['core_user']->is_user)
		{
			if ($_CLASS['auth']->acl_get('f_list', $row['forum_id']))
			{
				$forum_id_ary[] = $row['forum_id'];
			}

			continue;
		}

		if (isset($right_id))
		{
			if ($row['left_id'] < $right_id)
			{
				continue;
			}
			unset($right_id);
		}

		if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
		{
			// Non-postable forum with no subforums: don't display
			continue;
		}

		$forum_id = $row['forum_id'];

		if (!$_CLASS['auth']->acl_get('f_list', $forum_id))
		{
			// if the user does not have permissions to list this forum, skip everything until next branch
			$right_id = $row['right_id'];
			continue;
		}

		// Display active topics from this forum?
		if ($show_active && $row['forum_type'] == FORUM_POST && $_CLASS['auth']->acl_get('f_read', $forum_id) && ($row['forum_flags'] & 16))
		{
			$active_forum_ary['forum_id'][]		= $forum_id;
			$active_forum_ary['enable_icons'][] = $row['enable_icons'];
			$active_forum_ary['forum_topics']	+= ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $row['forum_topics_real'] : $row['forum_topics'];
			$active_forum_ary['forum_posts']	+= $row['forum_posts'];
		}

		if ($row['parent_id'] == $root_data['forum_id'] || $row['parent_id'] == $branch_root_id)
		{
			// Direct child
			$parent_id = $forum_id;
			$forum_rows[$forum_id] = $row;
			$forum_ids[] = $forum_id;

			if (!$row['parent_id'] && $row['forum_type'] == FORUM_CAT && $row['parent_id'] == $root_data['forum_id'])
			{
				$branch_root_id = $forum_id;
			}
			$forum_rows[$parent_id]['forum_id_last_post'] = $row['forum_id'];
		}
		elseif ($row['forum_type'] != FORUM_CAT)
		{
			$subforums[$parent_id]['display'] = ($row['display_on_index']) ? true : false;;
			$subforums[$parent_id]['name'][$forum_id] = $row['forum_name'];

			$forum_rows[$parent_id]['forum_topics'] += ($_CLASS['auth']->acl_get('m_approve', $forum_id)) ? $row['forum_topics_real'] : $row['forum_topics'];
			
			// Do not list redirects in LINK Forums as Posts.
			if ($row['forum_type'] != FORUM_LINK)
			{
				$forum_rows[$parent_id]['forum_posts'] += $row['forum_posts'];
			}

			if (isset($forum_rows[$parent_id]) && $row['forum_last_post_time'] > $forum_rows[$parent_id]['forum_last_post_time'])
			{
				$forum_rows[$parent_id]['forum_last_post_id'] = $row['forum_last_post_id'];
				$forum_rows[$parent_id]['forum_last_post_time'] = $row['forum_last_post_time'];
				$forum_rows[$parent_id]['forum_last_poster_id'] = $row['forum_last_poster_id'];
				$forum_rows[$parent_id]['forum_last_poster_name'] = $row['forum_last_poster_name'];
				$forum_rows[$parent_id]['forum_id_last_post'] = $forum_id;
			}
			else
			{
				$forum_rows[$parent_id]['forum_id_last_post'] = $forum_id;
			}
		}

		if (!$_CLASS['core_user']->is_user || !$config['load_db_lastread'])
		{
			$forum_id36 = base_convert($forum_id, 10, 36);
			$row['mark_time'] = isset($tracking_topics[$forum_id36][0]) ? (int) base_convert($tracking_topics[$forum_id36][0], 36, 10) : 0;
		}

		if ($row['mark_time'] < $row['forum_last_post_time'])
		{
			$forum_unread[$parent_id] = true;
		}
	}
	$_CLASS['core_db']->free_result($result);

	// Handle marking posts
	if ($mark_read == 'forums')
	{
		markread('mark', $forum_id_ary);

		$redirect = generate_link('Forums');
		$_CLASS['core_display']->meta_refresh(3, $redirect);

		$message = (strpos($redirect, 'viewforum') !== false) ? 'RETURN_FORUM' : 'RETURN_INDEX';
		$message = $_CLASS['core_user']->lang['FORUMS_MARKED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang[$message], '<a href="' . $redirect . '">', '</a> ');
		trigger_error($message);
	}

	// Grab moderators ... if necessary
	if ($display_moderators)
	{
		$forum_moderators = get_moderators($forum_ids);
	}

	// Loop through the forums
	$root_id = $root_data['forum_id'];

	foreach ($forum_rows as $row)
	{
		if ($row['parent_id'] == $root_id && !$row['parent_id'])
		{
			if ($row['forum_type'] == FORUM_CAT)
			{
				$hold = $row;
				continue;
			}
			else
			{
				unset($hold);
			}
		}
		else if (!empty($hold))
		{
			$_CLASS['core_template']->assign_vars_array('forumrow', array(
				'S_IS_CAT'			=>	TRUE,
				'FORUM_ID'			=>	$hold['forum_id'],
				'FORUM_NAME'		=>	$hold['forum_name'],
				'FORUM_DESC'		=>	$hold['forum_desc'],
				'U_VIEWFORUM'		=>	generate_link('Forums&amp;file=viewforum&amp;f=' . $hold['forum_id']))
			);
			unset($hold);
		}

		$visible_forums++;
		$forum_id = $row['forum_id'];

		$subforums_list = $l_subforums = '';
		
		// Generate list of subforums if we need to
		if (isset($subforums[$forum_id]))
		{
			if ($subforums[$forum_id]['display'])
			{
				$links = array();

				foreach ($subforums[$forum_id]['name'] as $subforum_id => $subforum_name)
				{
					if (!empty($subforum_name))
					{
						$links[] = '<a href="' .generate_link('Forums&amp;file=viewforum&amp;f='.$subforum_id).'">' . $subforum_name . '</a>';
					}
				}

				if (!empty($links))
				{
					$subforums_list = implode(', ', $links);
					$l_subforums = (count($subforums[$forum_id]) == 1) ? $_CLASS['core_user']->lang['SUBFORUM'] . ': ' : $_CLASS['core_user']->lang['SUBFORUMS'] . ': ';
				}

				unset($links);
			}

			$folder_image = (!empty($forum_unread[$forum_id])) ? 'sub_forum_new' : 'sub_forum';
		}
		else
		{
			switch ($row['forum_type'])
			{
				case FORUM_POST:
					$folder_image = (!empty($forum_unread[$forum_id])) ? 'forum_new' : 'forum';
					break;

				case FORUM_LINK:
					$folder_image = 'forum_link';
					break;
			}
		}


		// Which folder should we display?
		if ($row['forum_status'] == ITEM_LOCKED)
		{
// forum_locked_new , need an image for this one
			$folder_image = empty($forum_unread[$forum_id]) ? 'forum_locked' : 'folder_locked_new';
			$folder_alt = 'FORUM_LOCKED';
		}
		else
		{
			$folder_alt = empty($forum_unread[$forum_id]) ? 'NO_NEW_POSTS' : 'NEW_POSTS';
		}

		// Create last post link information, if appropriate
		if ($row['forum_last_post_id'])
		{
			$last_post_time = $_CLASS['core_user']->format_date($row['forum_last_post_time']);

			$last_poster = ($row['forum_last_poster_name'] != '') ? $row['forum_last_poster_name'] : $_CLASS['core_user']->lang['GUEST'];
			$last_poster_url = ($row['forum_last_poster_id'] == ANONYMOUS) ? '' : generate_link('Members_List&amp;mode=viewprofile&amp;u='  . $row['forum_last_poster_id']);
			
			$last_post_url = generate_link('Forums&amp;file=viewtopic&amp;f='.$row['forum_id_last_post'].'&amp;p='.$row['forum_last_post_id'].'#'.$row['forum_last_post_id'], false, false, false);
		}
		else
		{
			$last_post_time = $last_poster = $last_poster_url = $last_post_url = '';
		}


		// Output moderator listing ... if applicable
		$l_moderator = $moderators_list = '';
		if ($display_moderators && !empty($forum_moderators[$forum_id]))
		{
			$l_moderator = (count($forum_moderators[$forum_id]) == 1) ? $_CLASS['core_user']->lang['MODERATOR'] : $_CLASS['core_user']->lang['MODERATORS'];
			$moderators_list = implode(', ', $forum_moderators[$forum_id]);
		}

		$l_post_click_count = ($row['forum_type'] == FORUM_LINK) ? 'CLICKS' : 'POSTS';
		$post_click_count = ($row['forum_type'] != FORUM_LINK || $row['forum_flags'] & 1) ? $row['forum_posts'] : '';

		$_CLASS['core_template']->assign_vars_array('forumrow', array(
			'S_IS_CAT'			=> false, 
			'S_IS_LINK'			=> ($row['forum_type'] == FORUM_LINK), 

			'LAST_POST_IMG'		=> $_CLASS['core_user']->img('icon_post_latest', 'VIEW_LATEST_POST'), 

			'FORUM_ID'			=> $row['forum_id'], 
			'FORUM_FOLDER_IMG'	=> ($row['forum_image']) ? '<img src="' . $row['forum_image'] . '" alt="' . $folder_alt . '" />' : $_CLASS['core_user']->img($folder_image, $folder_alt),
			//'FORUM_FOLDER_IMG_SRC'	=> ($row['forum_image']) ? $row['forum_image'] : $_CLASS['core_user']->img($folder_image, $folder_alt, false, '', 'src'),
			'FORUM_NAME'		=> $row['forum_name'],
			'FORUM_DESC'		=> $row['forum_desc'], 
			'FORUM_LOCKED' 		=> ($row['forum_status'] == ITEM_LOCKED) ? 1 : 0,

			
			$l_post_click_count	=> $post_click_count,
			'TOPICS'			=> $row['forum_topics'],
			'LAST_POST_TIME'	=> $last_post_time,
			'LAST_POSTER'		=> $last_poster,
			'MODERATORS'		=> $moderators_list,
			'SUBFORUMS'			=> $subforums_list,

			'L_SUBFORUM_STR'	=> $l_subforums,
			'L_MODERATOR_STR'	=> $l_moderator,
			'L_FORUM_FOLDER_ALT'=> $folder_alt,
			
			'U_LAST_POSTER'		=> $last_poster_url, 
			'U_LAST_POST'		=> $last_post_url, 
			'U_VIEWFORUM'		=> ($row['forum_type'] != FORUM_LINK || $row['forum_flags'] & 1) ? generate_link('Forums&amp;file=viewforum&amp;f=' . $row['forum_id']) : $row['forum_link'])
		);
	}

	$_CLASS['core_template']->assign_array(array(
		'MODIFY_FORUM'		=> $_CLASS['auth']->acl_get('a_forum'),
		'U_MARK_FORUMS'		=> generate_link('Forums&amp;file=viewforum&amp;f=' . $root_data['forum_id'] . '&amp;mark=Forums'), 
		'S_HAS_SUBFORUM'	=> ($visible_forums) ? true : false,
		'L_SUBFORUM'		=> ($visible_forums == 1) ? $_CLASS['core_user']->lang['SUBFORUM'] : $_CLASS['core_user']->lang['SUBFORUMS']
	));

	return $active_forum_ary;
}

function topic_status(&$topic_row, $replies, $mark_time, &$unread, &$folder_img, &$folder_alt, &$topic_type)
{
	global $_CLASS, $config;

	$folder = $folder_new = '';
	$unread = ($mark_time < $topic_row['topic_last_post_time']);

	if ($topic_row['topic_status'] == ITEM_MOVED)
	{
		$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_MOVED'];
		$folder_img = 'folder_moved';
		$folder_alt = 'VIEW_TOPIC_MOVED';
		//$status = 9;
	}
	else
	{
		if ($topic_row['topic_status'] == ITEM_LOCKED)
		{
			$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_LOCKED'];
			$folder_img = ($unread) ? 'folder_locked_new' :'folder_locked';
			//$status = ($unread) ? 11 : 10;
		}
		else
		{
			switch ($topic_row['topic_type'])
			{
				case POST_GLOBAL:
				case POST_ANNOUNCE:
					$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_ANNOUNCEMENT'];
					$folder_img = ($unread) ? 'folder_announce_new' : 'folder_announce';
					//$status = ($unread) ? 8 : 7;
				break;
	
				case POST_STICKY:
					$topic_type = $_CLASS['core_user']->lang['VIEW_TOPIC_STICKY'];
					$folder_img = ($unread) ? 'folder_sticky_new' : 'folder_sticky';
					//$status = ($unread) ? 6 : 5;
				break;
	
				default:
					if ($replies >= $config['hot_threshold'])
					{
						$folder_img = ($unread) ? 'folder_hot_new': 'folder_hot';
						//$status = ($unread) ? 4 : 3;
					}
					else
					{
						$folder_img = ($unread) ? 'folder_new' : 'folder';
						//$status = ($unread) ? 2 : 1;
					}
				break;
			}
		}

		$folder_alt = ($unread) ? 'NEW_POSTS' : (($topic_row['topic_status'] == ITEM_LOCKED) ? 'TOPIC_LOCKED' : 'NO_NEW_POSTS');
	}

	if ($topic_row['poll_start'])
	{
		$topic_type .= $_CLASS['core_user']->lang['VIEW_TOPIC_POLL'];
	}

	//return $status;
}

// Display Attachments
function display_attachments($forum_id, $attachment_data, &$update_count, $force_physical = false, $parse = false)
{
	global $config, $_CLASS;

	$datas = array();
	$extensions = obtain_attach_extensions();

	if (!is_array($update_count))
	{
		$update_count = array();
	}

	foreach ($attachment_data as $attachment)
	{
		$attachment['extension'] = strtolower(trim($attachment['extension']));

		if (!extension_allowed($forum_id, $attachment['extension'], $extensions))
		{
			$data['category'] = 'DENIED';
			$data['lang'] = sprintf($_CLASS['core_user']->get_lang('EXTENSION_DISABLED_AFTER_POSTING'), $attachment['extension']);
		}
		else
		{
			$filename = $config['upload_path'] . '/' . basename($attachment['physical_filename']);
			// to easy isn't it ?
			$thumbnail_filename = $config['upload_path'] . '/thumb_' . basename($attachment['physical_filename']);

			$display_cat = $extensions[$attachment['extension']]['display_cat'];
	
			if ($display_cat == ATTACHMENT_CATEGORY_IMAGE)
			{
				if ($attachment['thumbnail'])
				{
					$display_cat = ATTACHMENT_CATEGORY_THUMB;
				}
				else
				{
					if ($config['img_display_inlined'])
					{
						if ($config['img_link_width'] || $config['img_link_height'])
						{
							list($width, $height) = getimagesize($filename);
	
							$display_cat = (!$width && !$height) ? ATTACHMENT_CATEGORY_IMAGE : (($width <= $config['img_link_width'] && $height <= $config['img_link_height']) ? ATTACHMENT_CATEGORY_IMAGE : ATTACHMENT_CATEGORY_NONE);
						}
					}
					else
					{
						$display_cat = ATTACHMENT_CATEGORY_NONE;
					}
				}
			}
	
			switch ($display_cat)
			{
				// Images
				case ATTACHMENT_CATEGORY_IMAGE:
					$data['category'] = 'IMAGE';
					$data['image_src'] = $filename;

					//$attachment['download_count']++;
					$update_count[] = $attachment['attach_id'];
				break;
					
				// Images, but display Thumbnail
				case ATTACHMENT_CATEGORY_THUMB:
					$data['category'] = 'THUMBNAIL';
	
					$data['image_src'] = $thumbnail_filename;
					$data['link'] = (!$force_physical) ? generate_link('Forums&amp;file=download&amp;id=' . $attachment['attach_id']) : $filename;
				break;
	
				// Windows Media Streams
				case ATTACHMENT_CATEGORY_WM:
					$data['category'] = 'WM_STREAM';
					$data['link'] = $filename;
	
					// Viewed/Heared File ... update the download count (download.php is not called here)
					//$attachment['download_count']++;
					$update_count[] = $attachment['attach_id'];
				break;
	
				// Real Media Streams
				case ATTACHMENT_CATEGORY_RM:
					$data['category'] = 'RM_STREAM';
					$data['link'] = $filename;
	
					// Viewed/Heared File ... update the download count (download.php is not called here)
					//$attachment['download_count']++;
					$update_count[] = $attachment['attach_id'];
				break;
	
				default:
					$data['category'] = 'FILE';
					$data['link'] = (!$force_physical) ? generate_link('Forums&amp;file=download&amp;id=' . $attachment['attach_id']) : $filename;
				break;
			}
			
			$data['lang_size'] = ($attachment['filesize'] >= 1048576) ? round((round($attachment['filesize'] / 1048576 * 100) / 100), 2) .$_CLASS['core_user']->lang['MB'] : (($attachment['filesize'] >= 1024) ? round((round($attachment['filesize'] / 1024 * 100) / 100), 2)  . $_CLASS['core_user']->lang['KB']: $attachment['filesize'] . $_CLASS['core_user']->lang['BYTES']);
			$data['lang_views'] = (!$attachment['download_count']) ? $_CLASS['core_user']->lang['DOWNLOAD_NONE'] : (($attachment['download_count'] == 1) ? sprintf($_CLASS['core_user']->lang['DOWNLOAD_COUNT'], $attachment['download_count']) : sprintf($_CLASS['core_user']->lang['DOWNLOAD_COUNTS'], $attachment['download_count']));
	
			$data['icon'] = (isset($extensions[$attachment['extension']]['upload_icon']) && $extensions[$attachment['extension']]['upload_icon']) ? $config['upload_icons_path'] . '/' . trim($extensions[$attachment['extension']]['upload_icon']) : false;
			$data['name'] = basename($attachment['real_filename']);
			$data['comment'] = str_replace("\n", '<br />', censor_text($attachment['comment']));
		}

		if ($parse)
		{
			$_CLASS['core_template']->assign_vars_array('attachments', $data);
			$datas[] = $_CLASS['core_template']->display('modules/Forums/attachments.html', true);
		}
		else
		{
			$datas[] = $data;
		}
	}

	return $datas;
}

?>