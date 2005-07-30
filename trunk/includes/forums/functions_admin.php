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
// $Id: functions_admin.php,v 1.86 2004/09/16 18:33:18 acydburn Exp $
//
// FILENAME  : functions_admin.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001,2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
// Fix links "defined('IN_ADMIN')"
// Simple version of jumpbox, just lists authed forums
function make_forum_select($select_id = false, $ignore_id = false, $ignore_acl = false, $ignore_nonpost = false, $ignore_emptycat = true)
{
	global $_CLASS;

	$acl = ($ignore_acl) ? '' : array('f_list', 'a_forum', 'a_forumadd', 'a_forumdel');

	// This query is identical to the jumpbox one
	$sql = 'SELECT forum_id, parent_id, forum_name, forum_type, left_id, right_id
		FROM ' . FORUMS_TABLE . '
		ORDER BY left_id ASC';
	$result = $_CLASS['core_db']->query($sql);

	$right = $iteration = 0;
	$padding_store = array('0' => '');
	$forum_list = $padding = '';

	// Sometimes it could happen that forums will be displayed here not be displayed within the index page
	// This is the result of forums not displayed at index, having list permissions and a parent of a forum with no permissions.
	// If this happens, the padding could be "broken"

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($row['left_id'] < $right)
		{
			$padding .= '&nbsp; &nbsp;';
			$padding_store[$row['parent_id']] = $padding;
		}
		else if ($row['left_id'] > $right + 1)
		{
			$padding = (isset($padding_store[$row['parent_id']])) ? $padding_store[$row['parent_id']] : '';
		}

		$right = $row['right_id'];

		if ($acl && !$_CLASS['auth']->acl_gets($acl, $row['forum_id']))
		{
			continue;
		}

		if ((is_array($ignore_id) && in_array($row['forum_id'], $ignore_id)) || $row['forum_id'] == $ignore_id)
		{
			continue;
		}

		if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']) && $ignore_emptycat)
		{
			// Non-postable forum with no subforums, don't display
			continue;
		}

		if ($row['forum_type'] != FORUM_POST && $ignore_nonpost)
		{
			continue;
		}

		$selected = (is_array($select_id)) ? ((in_array($row['forum_id'], $select_id)) ? ' selected="selected"' : '') : (($row['forum_id'] == $select_id) ? ' selected="selected"' : '');

		$forum_list .= '<option value="' . $row['forum_id'] . '"' . $selected . '>' . $padding . $row['forum_name'] . '</option>';

		$iteration++;
	}
	unset($padding_store);

	return $forum_list;
}

// Generate size select form
function size_select($select_name, $size_compare)
{
	global $_CLASS;

	$size_types_text = array($_CLASS['core_user']->lang['BYTES'], $_CLASS['core_user']->lang['KB'], $_CLASS['core_user']->lang['MB']);
	$size_types = array('b', 'kb', 'mb');

	$select_field = '<select name="' . $select_name . '">';

	$size = sizeof($size_types_text);
	for ($i = 0, $size; $i < $size; $i++)
	{
		$selected = ($size_compare == $size_types[$i]) ? ' selected="selected"' : '';
		$select_field .= '<option value="' . $size_types[$i] . '"' . $selected . '>' . $size_types_text[$i] . '</option>';
	}
	
	$select_field .= '</select>';

	return ($select_field);
}

// Obtain authed forums list
function get_forum_list($acl_list = 'f_list', $id_only = TRUE, $postable_only = FALSE, $no_cache = FALSE)
{
	global $_CLASS;
	static $forum_rows;

	if (!isset($forum_rows))
	{
		// This query is identical to the jumpbox one
		$expire_time = ($no_cache) ? 0 : 120;
		$sql = 'SELECT forum_id, parent_id, forum_name, forum_type, left_id, right_id
			FROM ' . FORUMS_TABLE . '
			ORDER BY left_id ASC';
		$result = $_CLASS['core_db']->query($sql, $expire_time);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_rows[] = $row;
		}
		$_CLASS['core_db']->free_result();
	}

	$rowset = array();
	foreach ($forum_rows as $row)
	{
		if ($postable_only && $row['forum_type'] != FORUM_POST)
		{
			continue;
		}

		if ($acl_list == '' || ($acl_list != '' && $_CLASS['auth']->acl_gets($acl_list, $row['forum_id'])))
		{
			$rowset[] = ($id_only) ? $row['forum_id'] : $row;
		}
	}

	return $rowset;
}

function get_forum_branch($forum_id, $type = 'all', $order = 'descending', $include_forum = TRUE)
{
	global $_CLASS;

	switch ($type)
	{
		case 'parents':
			$condition = 'f1.left_id BETWEEN f2.left_id AND f2.right_id';
			break;

		case 'children':
			$condition = 'f2.left_id BETWEEN f1.left_id AND f1.right_id';
			break;

		default:
			$condition = 'f2.left_id BETWEEN f1.left_id AND f1.right_id OR f1.left_id BETWEEN f2.left_id AND f2.right_id';
	}

	$rows = array();

	$sql = 'SELECT f2.*
		FROM (' . FORUMS_TABLE . ' f1
		LEFT JOIN ' . FORUMS_TABLE . " f2 ON $condition)
		WHERE f1.forum_id = $forum_id
		ORDER BY f2.left_id " . (($order == 'descending') ? 'ASC' : 'DESC');
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (!$include_forum && $row['forum_id'] == $forum_id)
		{
			continue;
		}

		$rows[] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	return $rows;
}

function filelist($rootdir, $dir = '', $type = 'gif|jpg|jpeg|png')
{
	$matches = array();

	// Remove initial / if present
	$rootdir = (substr($rootdir, 0, 1) == '/') ? substr($rootdir, 1) : $rootdir;
	// Add closing / if present
	$rootdir = ($rootdir && substr($rootdir, -1) != '/') ? $rootdir . '/' : $rootdir;

	// Remove initial / if present
	$dir = (substr($dir, 0, 1) == '/') ? substr($dir, 1) : $dir;
	// Add closing / if present
	$dir = ($dir && substr($dir, -1) != '/') ? $dir . '/' : $dir;

	$dh = opendir($rootdir . $dir);
	while (($fname = readdir($dh)) !== false)
	{
		if (is_file("$rootdir$dir$fname"))
		{
			if (filesize("$rootdir$dir$fname") && preg_match('#\.' . $type . '$#i', $fname))
			{
				$matches[$dir][] = $fname;
			}
		}
		else if ($fname{0} != '.' && is_dir("$rootdir$dir$fname"))
		{
			$matches += filelist($rootdir, "$dir$fname", $type);
		}
	}
	closedir($dh);

	return $matches;
}

// Posts and topics manipulation
function move_topics($topic_ids, $forum_id, $auto_sync = true)
{
	global $_CLASS;

	$forum_ids = array($forum_id);
	$sql_where = (is_array($topic_ids)) ? 'IN (' . implode(', ', $topic_ids) . ')' : '= ' . $topic_ids;

	$sql = 'DELETE FROM ' . TOPICS_TABLE . "
		WHERE topic_moved_id $sql_where
			AND forum_id = " . $forum_id;
	$_CLASS['core_db']->query($sql);

	if ($auto_sync)
	{
		$sql = 'SELECT DISTINCT forum_id
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id ' . $sql_where;
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_ids[] = $row['forum_id'];
		}
		$_CLASS['core_db']->free_result($result);
	}
	
	$table_ary = array(TOPICS_TABLE, POSTS_TABLE, LOG_TABLE);
	foreach ($table_ary as $table)
	{
		$sql = "UPDATE $table
			SET forum_id = $forum_id
			WHERE topic_id " . $sql_where;
		$_CLASS['core_db']->query($sql);
	}
	unset($table_ary);

	if ($auto_sync)
	{
		sync('forum', 'forum_id', $forum_ids, true);
		unset($forum_ids);
	}
}

function move_posts($post_ids, $topic_id, $auto_sync = true)
{
	global $_CLASS;

	if (!is_array($post_ids))
	{
		$post_ids = array($post_ids);
	}

	if ($auto_sync)
	{
		$forum_ids = array();
		$topic_ids = array($topic_id);

		$sql = 'SELECT DISTINCT topic_id, forum_id
			FROM ' . POSTS_TABLE . '
			WHERE post_id IN (' . implode(', ', $post_ids) . ')';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_ids[] = $row['forum_id'];
			$topic_ids[] = $row['topic_id'];
		}
		$_CLASS['core_db']->free_result($result);
	}

	$sql = 'SELECT forum_id 
		FROM ' . TOPICS_TABLE . ' 
		WHERE topic_id = ' . $topic_id;
	$result = $_CLASS['core_db']->query($sql);

	if (!$row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		trigger_error('NO_TOPIC');
	}
	$_CLASS['core_db']->free_result($result);

	$sql = 'UPDATE ' . POSTS_TABLE . '
		SET forum_id = ' . $row['forum_id'] . ", topic_id = $topic_id
		WHERE post_id IN (" . implode(', ', $post_ids) . ')';
	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . ATTACHMENTS_TABLE . "
		SET topic_id = $topic_id
			AND in_message = 0
		WHERE post_msg_id IN (" . implode(', ', $post_ids) . ')';
	$_CLASS['core_db']->query($sql);

	if ($auto_sync)
	{
		$forum_ids[] = $row['forum_id'];

		sync('reported', 'topic_id', $topic_ids);
		sync('topic', 'topic_id', $topic_ids, true);
		sync('forum', 'forum_id', $forum_ids, true);
	}
}

function delete_topics($where_type, $where_ids, $auto_sync = TRUE)
{
	global $_CLASS;
	$forum_ids = $topic_ids = array();

	if (is_array($where_ids))
	{
		$where_ids = array_unique($where_ids);
	}

	if (!sizeof($where_ids))
	{
		return array('topics' => 0, 'posts' => 0);
	}

	$return = array(
		'posts'	=>	delete_posts($where_type, $where_ids, false)
	);

	$sql = 'SELECT topic_id, forum_id
		FROM ' . TOPICS_TABLE . "
		WHERE $where_type " . ((!is_array($where_ids)) ? "= $where_ids" : 'IN (' . implode(', ', $where_ids) . ')');
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$forum_ids[] = $row['forum_id'];
		$topic_ids[] = $row['topic_id'];
	}
	$_CLASS['core_db']->free_result();

	$return['topics'] = sizeof($topic_ids);

	if (!sizeof($topic_ids))
	{
		return $return;
	}

	// TODO: probably some other stuff too

	$sql_where = ' IN (' . implode(', ', $topic_ids) . ')';

	$_CLASS['core_db']->sql_transaction('begin');

	$table_ary = array(TOPICS_TRACK_TABLE, POLL_VOTES_TABLE, POLL_OPTIONS_TABLE, TOPICS_WATCH_TABLE, TOPICS_TABLE);
	foreach ($table_ary as $table)
	{
		$sql = "DELETE FROM $table 
			WHERE topic_id $sql_where";
		$_CLASS['core_db']->query($sql);
	}
	unset($table_ary);

	$sql = 'DELETE FROM ' . TOPICS_TABLE . ' 
		WHERE topic_moved_id' . $sql_where;
	$_CLASS['core_db']->query($sql);

	$_CLASS['core_db']->sql_transaction('commit');

	if ($auto_sync)
	{
		sync('forum', 'forum_id', $forum_ids, true);
		sync('topic_reported', $where_type, $where_ids);
	}

	return $return;
}

function delete_posts($where_type, $where_ids, $auto_sync = TRUE)
{
	global $_CLASS;

	if (is_array($where_ids))
	{
		$where_ids = array_unique($where_ids);
	}
	if (empty($where_ids))
	{
		return false;
	}
	$post_ids = $topic_ids = $forum_ids = array();

	$sql = 'SELECT post_id, topic_id, forum_id
		FROM ' . POSTS_TABLE . "
		WHERE $where_type " . ((!is_array($where_ids)) ? "= $where_ids" : 'IN (' . implode(', ', $where_ids) . ')');
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$post_ids[] = $row['post_id'];
		$topic_ids[] = $row['topic_id'];
		$forum_ids[] = $row['forum_id'];
	}

	if (!sizeof($post_ids))
	{
		return false;
	}

	$sql_where = implode(', ', $post_ids);

	$_CLASS['core_db']->sql_transaction('begin');

	$table_ary = array(POSTS_TABLE, RATINGS_TABLE, REPORTS_TABLE, SEARCH_MATCH_TABLE);
	//$table_ary = array(POSTS_TABLE, REPORTS_TABLE, SEARCH_MATCH_TABLE);
	
	foreach ($table_ary as $table)
	{
		$sql = "DELETE FROM $table 
			WHERE post_id IN ($sql_where)";
		$_CLASS['core_db']->query($sql);
	}
	unset($table_ary);

	delete_attachments('post', $post_ids, false);

	$_CLASS['core_db']->sql_transaction('commit');

	if ($auto_sync)
	{
		sync('reported', 'topic_id', $topic_ids);
		sync('topic', 'topic_id', $topic_ids, true);
		sync('forum', 'forum_id', $forum_ids, true);
	}

	return sizeof($post_ids);
}

// Delete Attachments
// mode => (post, topic, attach, user)
// ids => (post_ids, topic_ids, attach_ids, user_ids)
// resync => set this to false if you are deleting posts or topics...
function delete_attachments($mode, $ids, $resync = TRUE)
{
	global $_CLASS, $config;

	if (is_array($ids))
	{
		$ids = array_unique($ids);
	}
	
	if (!sizeof($ids))
	{
		return false;
	}

	$sql_id = ($mode == 'user') ? 'poster_id' : (($mode == 'post') ? 'post_msg_id' : (($mode == 'topic') ? 'topic_id' : 'attach_id'));

	$post_ids = $topic_ids = $physical = array();

	// Collect post and topics ids for later use
	if ($mode == 'attach' || $mode == 'user' || ($mode == 'topic' && $resync))
	{
		$sql = 'SELECT post_msg_id as post_id, topic_id, physical_filename, thumbnail, filesize
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE ' . $sql_id . ' IN (' . implode(', ', $ids) . ')';
		$result = $_CLASS['core_db']->query($sql);
			
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$post_ids[] = $row['post_id'];
			$topic_ids[] = $row['topic_id'];
			$physical[] = array('filename' => $row['physical_filename'], 'thumbnail' => $row['thumbnail'], 'filesize' => $row['filesize']);
		}
		$_CLASS['core_db']->free_result($result);
	}

	if ($mode == 'post')
	{
		$sql = 'SELECT topic_id, physical_filename, thumbnail, filesize
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE post_msg_id IN (' . implode(', ', $ids) . ')
				AND in_message = 0';
		$result = $_CLASS['core_db']->query($sql);
			
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$topic_ids[] = $row['topic_id'];
			$physical[] = array('filename' => $row['physical_filename'], 'thumbnail' => $row['thumbnail'], 'filesize' => $row['filesize']);
		}
		$_CLASS['core_db']->free_result($result);
	}

	// Delete attachments
	$_CLASS['core_db']->query('DELETE FROM ' . ATTACHMENTS_TABLE . ' WHERE ' . $sql_id . ' IN (' . implode(', ', $ids) . ')');
	$num_deleted = $_CLASS['core_db']->sql_affectedrows();

	if (!$num_deleted)
	{
		return 0;
	}
	
	// Delete attachments from filesystem
	$space_removed = $files_removed = 0;
	foreach ($physical as $file_ary)
	{
		if (phpbb_unlink($file_ary['filename'], 'file'))
		{
			$space_removed += $file_ary['filesize'];
			$files_removed++;
		}

		if ($file_ary['thumbnail'])
		{
			phpbb_unlink($file_ary['filename'], 'thumbnail');
		}
	}
	set_config('upload_dir_size', $config['upload_dir_size'] - $space_removed, true);
	set_config('num_files', $config['num_files'] - $files_removed, true);

	if ($mode == 'topic' && !$resync)
	{
		return $num_deleted;
	}

	if ($mode == 'post')
	{
		$post_ids = $ids;
	}
	unset($ids);

	$post_ids = array_unique($post_ids);
	$topic_ids = array_unique($topic_ids);

	// Update post indicators
	if (sizeof($post_ids))
	{
		if ($mode == 'post' || $mode == 'topic')
		{
			$_CLASS['core_db']->query('UPDATE ' . POSTS_TABLE . ' 
				SET post_attachment = 0
				WHERE post_id IN (' . implode(', ', $post_ids) . ')');
		}

		if ($mode == 'user' || $mode == 'attach')
		{
			$remaining = array();

			$sql = 'SELECT post_msg_id
					FROM ' . ATTACHMENTS_TABLE . ' 
					WHERE post_msg_id IN (' . implode(', ', $post_ids) . ')
						AND in_message = 0';
			$result = $_CLASS['core_db']->query($sql);
					
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$remaining[] = $row['post_msg_id'];		
			}
			$_CLASS['core_db']->free_result($result);

			$unset_ids = array_diff($post_ids, $remaining);
			if (sizeof($unset_ids))
			{
				$_CLASS['core_db']->query('UPDATE ' . POSTS_TABLE . ' 
					SET post_attachment = 0
					WHERE post_id IN (' . implode(', ', $unset_ids) . ')');
			}

			$remaining = array();

			$sql = 'SELECT post_msg_id
					FROM ' . ATTACHMENTS_TABLE . ' 
					WHERE post_msg_id IN (' . implode(', ', $post_ids) . ')
						AND in_message = 1';
			$result = $_CLASS['core_db']->query($sql);
					
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$remaining[] = $row['post_msg_id'];		
			}
			$_CLASS['core_db']->free_result($result);

			$unset_ids = array_diff($post_ids, $remaining);
			if (sizeof($unset_ids))
			{
				$_CLASS['core_db']->query('UPDATE ' . PRIVMSGS_TABLE . ' 
					SET message_attachment = 0
					WHERE msg_id IN (' . implode(', ', $unset_ids) . ')');
			}
		}
	}

	if (sizeof($topic_ids))
	{
		// Update topic indicator
		if ($mode == 'topic')
		{
			$_CLASS['core_db']->query('UPDATE ' . TOPICS_TABLE . '
				SET topic_attachment = 0
				WHERE topic_id IN (' . implode(', ', $topic_ids) . ')');
		}

		if ($mode == 'post' || $mode == 'user' || $mode == 'attach')
		{
			$remaining = array();

			$sql = 'SELECT topic_id
					FROM ' . ATTACHMENTS_TABLE . ' 
					WHERE topic_id IN (' . implode(', ', $topic_ids) . ')';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$remaining[] = $row['topic_id'];		
			}
			$_CLASS['core_db']->free_result($result);

			$unset_ids = array_diff($topic_ids, $remaining);
			if (sizeof($unset_ids))
			{
				$_CLASS['core_db']->query('UPDATE ' . TOPICS_TABLE . ' 
					SET topic_attachment = 0
					WHERE topic_id IN (' . implode(', ', $unset_ids) . ')');
			}
		}
	}

	return $num_deleted;
}

function delete_topic_shadows($max_age, $forum_id = '', $auto_sync = TRUE)
{
	$where = (is_array($forum_id)) ? 'AND t.forum_id IN (' . implode(', ', $forum_id) . ')' : (($forum_id) ? "AND t.forum_id = $forum_id" : '');

	switch ($_CLASS['core_db']->db_layer)
	{
		case 'mysql4':
		case 'mysqli':
			$sql = 'DELETE t.*
				FROM ' . TOPICS_TABLE . ' t, ' . TOPICS_TABLE . ' t2
				WHERE t.topic_moved_id = t2.topic_id
					AND t.topic_time < ' . (time() - $max_age)
				. $where;
			$_CLASS['core_db']->query($sql);
		break;
	
		default:
			$sql = 'SELECT t.topic_id
				FROM ' . TOPICS_TABLE . ' t, ' . TOPICS_TABLE . ' t2
				WHERE t.topic_moved_id = t2.topic_id
					AND t.topic_time < ' . (time() - $max_age)
				. $where;
			$result = $_CLASS['core_db']->query($sql);
			
			$topic_ids = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_ids[] = $row['topic_id'];
			}

			if (sizeof($topic_ids))
			{
				$sql = 'DELETE FROM ' . TOPICS_TABLE . '
					WHERE topic_id IN (' . implode(',', $topic_ids) . ')';
				$_CLASS['core_db']->query($sql);
			}
	}

	if ($auto_sync)
	{
		$where_type = ($forum_id) ? 'forum_id' : '';
		sync('forum', $where_type, $forum_id, TRUE);
	}
}

// Delete File
function phpbb_unlink($filename, $mode = 'file')
{
	global $config;

	$filename = ($mode == 'thumbnail') ? $config['upload_path'] . '/thumb_' . basename($filename) : $config['upload_path'] . '/' . basename($filename);

	return @unlink($filename);
}

// All-encompasing sync function
//
// Usage:
// sync('topic', 'topic_id', 123);			<= resync topic #123
// sync('topic', 'forum_id', array(2, 3));	<= resync topics from forum #2 and #3
// sync('topic');							<= resync all topics
// sync('topic', 'range', 'topic_id BETWEEN 1 AND 60');	<= resync a range of topics/forums (only available for 'topic' and 'forum' modes)
//
// Modes:
// - topic_moved		Removes topic shadows that would be in the same forum as the topic they link to
// - topic_approved		Resyncs the topic_approved flag according to the status of the first post
// - post_reported		Resyncs the post_reported flag, relying on actual reports
// - topic_reported		Resyncs the topic_reported flag, relying on post_reported flags
// - post_attachement	Same as post_reported, thanks to a quick Search/Replace
// - topic_attachement	Same as topic_reported, thanks to a quick Search/Replace
//
function sync($mode, $where_type = '', $where_ids = '', $resync_parents = FALSE, $sync_extra = FALSE)
{
	global $_CLASS;

	if (is_array($where_ids))
	{
		$where_ids = array_unique($where_ids);
	}
	elseif ($where_type != 'range')
	{
		$where_ids = ($where_ids) ? array($where_ids) : array();
	}

	if ($mode == 'forum' || $mode == 'topic')
	{
		if (!$where_type)
		{
				$where_sql = '';
				$where_sql_and = 'WHERE';
		}
		elseif ($where_type == 'range')
		{
			// Only check a range of topics/forums. For instance: 'topic_id BETWEEN 1 AND 60'
			$where_sql = 'WHERE (' . $mode{0} . ".$where_ids)";
			$where_sql_and = $where_sql . "\n\tAND";
		}
		else
		{
			if (!sizeof($where_ids))
			{
				// Empty array with IDs. This means that we don't have any work to do. Just return.
				return;
			}
			// Limit the topics/forums we are syncing, use specific topic/forum IDs.
         // $where_type contains the field for the where clause (forum_id, topic_id)
			$where_sql = 'WHERE ' . $mode{0} . ".$where_type IN (" . implode(', ', $where_ids) . ')';
			$where_sql_and = $where_sql . "\n\tAND";
		}
	}
	else
	{
		if (!sizeof($where_ids))
		{
			return;
		}
		// $where_type contains the field for the where clause (forum_id, topic_id)
		$where_sql = 'WHERE ' . $mode{0} . ".$where_type IN (" . implode(', ', $where_ids) . ')';
		$where_sql_and = $where_sql . "\n\tAND";
	}

	switch ($mode)
	{
		case 'topic_moved':
			switch ($_CLASS['core_db']->db_layer)
			{
				case 'mysql4':
				case 'mysqli':
					$sql = 'DELETE FROM ' . TOPICS_TABLE . '
						USING ' . TOPICS_TABLE . ' t1, ' . TOPICS_TABLE . " t2
						WHERE t1.topic_moved_id = t2.topic_id
							AND t1.forum_id = t2.forum_id";
					$_CLASS['core_db']->query($sql);
				break;
			
				default:
					$sql = 'SELECT t1.topic_id
						FROM ' .TOPICS_TABLE . ' t1, ' . TOPICS_TABLE . " t2
						WHERE t1.topic_moved_id = t2.topic_id
							AND t1.forum_id = t2.forum_id";
					$result = $_CLASS['core_db']->query($sql);

					if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$topic_id_ary = array();
						do
						{
							$topic_id_ary[] = $row['topic_id'];
						}
						while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

						$sql = 'DELETE FROM ' . TOPICS_TABLE . '
							WHERE topic_id IN (' . implode(', ', $topic_id_ary) . ')';
						$_CLASS['core_db']->query($sql);
						unset($topic_id_ary);
					}
					$_CLASS['core_db']->free_result($result);					
			}
			break;

		case 'topic_approved':
			switch ($_CLASS['core_db']->db_layer)
			{
				case 'mysql4':
				case 'mysqli':
					$sql = 'UPDATE ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . " p
						SET t.topic_approved = p.post_approved
						$where_sql_and t.topic_first_post_id = p.post_id";
					$_CLASS['core_db']->query($sql);
				break;
			
				default:
					$sql = 'SELECT t.topic_id, p.post_approved
						FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . " p
						$where_sql_and p.post_id = t.topic_first_post_id
							AND p.post_approved <> t.topic_approved";
					$result = $_CLASS['core_db']->query($sql);

					$topic_ids = array();
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$topic_ids[] = $row['topic_id'];
					}
					$_CLASS['core_db']->free_result();

					if (!sizeof($topic_ids))
					{
						return;
					}

					$sql = 'UPDATE ' . TOPICS_TABLE . '
						SET topic_approved = 1 - topic_approved
						WHERE topic_id IN (' . implode(', ', $topic_ids) . ')';
					$_CLASS['core_db']->query($sql);
			}
			break;

		case 'post_reported':
			$post_ids = $post_reported = array();

			$sql = 'SELECT p.post_id, p.post_reported
				FROM ' . POSTS_TABLE . " p
				$where_sql
				GROUP BY p.post_id, p.post_reported";
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$post_ids[$row['post_id']] = $row['post_id'];
				if ($row['post_reported'])
				{
					$post_reported[$row['post_id']] = 1;
				}
			}

			$sql = 'SELECT DISTINCT(post_id)
				FROM ' . REPORTS_TABLE . '
				WHERE post_id IN (' . implode(', ', $post_ids) . ')';
			$result = $_CLASS['core_db']->query($sql);

			$post_ids = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if (!isset($post_reported[$row['post_id']]))
				{
					$post_ids[] = $row['post_id'];
				}
				else
				{
					unset($post_reported[$row['post_id']]);
				}
			}

			// $post_reported should be empty by now, if it's not it contains
			// posts that are falsely flagged as reported
			foreach ($post_reported as $post_id => $void)
			{
				$post_ids[] = $post_id;
			}

			if (sizeof($post_ids))
			{
				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_reported = 1 - post_reported
					WHERE post_id IN (' . implode(', ', $post_ids) . ')';
				$_CLASS['core_db']->query($sql);
			}
			break;

		case 'topic_reported':
			if ($sync_extra)
			{
				sync('post_reported', $where_type, $where_ids);
			}

			$topic_ids = $topic_reported = array();

			$sql = 'SELECT DISTINCT(t.topic_id)
				FROM ' . POSTS_TABLE . " t
				$where_sql_and t.post_reported = 1";
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_reported[$row['topic_id']] = 1;
			}

			$sql = 'SELECT t.topic_id, t.topic_reported
				FROM ' . TOPICS_TABLE . " t
				$where_sql";
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if ($row['topic_reported'] ^ isset($topic_reported[$row['topic_id']]))
				{
					$topic_ids[] = $row['topic_id'];
				}
			}

			if (sizeof($topic_ids))
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_reported = 1 - topic_reported
					WHERE topic_id IN (' . implode(', ', $topic_ids) . ')';
				$_CLASS['core_db']->query($sql);
			}
			break;

		case 'post_attachment':
			$post_ids = $post_attachment = array();

			$sql = 'SELECT p.post_id, p.post_attachment
				FROM ' . POSTS_TABLE . " p
				$where_sql
				GROUP BY p.post_id, p.post_attachment";
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$post_ids[$row['post_id']] = $row['post_id'];
				if ($row['post_attachment'])
				{
					$post_attachment[$row['post_id']] = 1;
				}
			}

			$sql = 'SELECT DISTINCT(post_msg_id)
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE post_msg_id IN (' . implode(', ', $post_ids) . ')
					AND in_message = 0';

			$post_ids = array();
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if (!isset($post_attachment[$row['post_id']]))
				{
					$post_ids[] = $row['post_id'];
				}
				else
				{
					unset($post_attachment[$row['post_id']]);
				}
			}

			// $post_attachment should be empty by now, if it's not it contains
			// posts that are falsely flagged as having attachments
			foreach ($post_attachment as $post_id => $void)
			{
				$post_ids[] = $post_id;
			}

			if (sizeof($post_ids))
			{
				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_attachment = 1 - post_attachment
					WHERE post_id IN (' . implode(', ', $post_ids) . ')';
				$_CLASS['core_db']->query($sql);
			}
			break;

		case 'topic_attachment':
			if ($sync_extra)
			{
				sync('post_attachment', $where_type, $where_ids);
			}

			$topic_ids = $topic_attachment = array();

			$sql = 'SELECT DISTINCT(t.topic_id)
				FROM ' . POSTS_TABLE . " t
				$where_sql_and t.post_attachment = 1";
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_attachment[$row['topic_id']] = 1;
			}

			$sql = 'SELECT t.topic_id, t.topic_attachment
				FROM ' . TOPICS_TABLE . " t
				$where_sql";
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if ($row['topic_attachment'] ^ isset($topic_attachment[$row['topic_id']]))
				{
					$topic_ids[] = $row['topic_id'];
				}
			}

			if (sizeof($topic_ids))
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_attachment = 1 - topic_attachment
					WHERE topic_id IN (' . implode(', ', $topic_ids) . ')';
				$_CLASS['core_db']->query($sql);
			}
			break;

		case 'forum':
			// 1: Get the list of all forums
			$sql = 'SELECT f.*
				FROM ' . FORUMS_TABLE . " f
				$where_sql";
			$result = $_CLASS['core_db']->query($sql);

			$forum_data = $forum_ids = $post_ids = $last_post_id = $post_info = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if ($row['forum_type'] == FORUM_LINK)
				{
					continue;
				}

				$forum_id = (int) $row['forum_id'];
				$forum_ids[$forum_id] = $forum_id;

				$forum_data[$forum_id] = $row;
				$forum_data[$forum_id]['posts'] = 0;
				$forum_data[$forum_id]['topics'] = 0;
				$forum_data[$forum_id]['topics_real'] = 0;
				$forum_data[$forum_id]['last_post_id'] = 0;
				$forum_data[$forum_id]['last_post_time'] = 0;
				$forum_data[$forum_id]['last_poster_id'] = 0;
				$forum_data[$forum_id]['last_poster_name'] = '';
			}

			// 2: Get topic counts for each forum
			$sql = 'SELECT forum_id, topic_approved, COUNT(topic_id) AS forum_topics
				FROM ' . TOPICS_TABLE . '
				WHERE forum_id IN (' . implode(', ', $forum_ids) . ')
				GROUP BY forum_id, topic_approved';
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$forum_id = (int) $row['forum_id'];
				$forum_data[$forum_id]['topics_real'] += $row['forum_topics'];

				if ($row['topic_approved'])
				{
					$forum_data[$forum_id]['topics'] = $row['forum_topics'];
				}
			}

			// 3: Get post count and last_post_id for each forum
			$sql = 'SELECT forum_id, COUNT(post_id) AS forum_posts, MAX(post_id) AS last_post_id
				FROM ' . POSTS_TABLE . '
				WHERE forum_id IN (' . implode(', ', $forum_ids) . ')
					AND post_approved = 1
				GROUP BY forum_id';
			$result = $_CLASS['core_db']->query($sql);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$forum_id = (int) $row['forum_id'];

				$forum_data[$forum_id]['posts'] = intval($row['forum_posts']);
				$forum_data[$forum_id]['last_post_id'] = intval($row['last_post_id']);

				$post_ids[] = $row['last_post_id'];
			}

			// 4: Retrieve last_post infos
			if (sizeof($post_ids))
			{
				$sql = 'SELECT p.post_id, p.poster_id, p.post_time, p.post_username, u.username
					FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
					WHERE p.post_id IN (' . implode(', ', $post_ids) . ')
						AND p.poster_id = u.user_id';
				$result = $_CLASS['core_db']->query($sql);
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$post_info[intval($row['post_id'])] = $row;
				}
				$_CLASS['core_db']->free_result($result);

				foreach ($forum_data as $forum_id => $data)
				{
					if ($data['last_post_id'])
					{
						if (isset($post_info[$data['last_post_id']]))
						{
							$forum_data[$forum_id]['last_post_time'] = $post_info[$data['last_post_id']]['post_time'];
							$forum_data[$forum_id]['last_poster_id'] = $post_info[$data['last_post_id']]['poster_id'];
							$forum_data[$forum_id]['last_poster_name'] = ($post_info[$data['last_post_id']]['poster_id'] != ANONYMOUS) ? $post_info[$data['last_post_id']]['username'] : $post_info[$data['last_post_id']]['post_username'];
						}
						else
						{
							// For some reason we did not find the post in the db
							$forum_data[$forum_id]['last_post_id'] = 0;
							$forum_data[$forum_id]['last_post_time'] = 0;
							$forum_data[$forum_id]['last_poster_id'] = 0;
							$forum_data[$forum_id]['last_poster_name'] = '';
						}
					}
				}
				unset($post_info);
			}

			// 5: Now do that thing
			$fieldnames = array('posts', 'topics', 'topics_real', 'last_post_id', 'last_post_time', 'last_poster_id', 'last_poster_name');

			foreach ($forum_data as $forum_id => $row)
			{
				$sql = array();

				foreach ($fieldnames as $fieldname)
				{
					if ($row['forum_' . $fieldname] != $row[$fieldname])
					{
						if (preg_match('#name$#', $fieldname))
						{
							$sql['forum_' . $fieldname] = (string) $row[$fieldname];
						}
						else
						{
							$sql['forum_' . $fieldname] = (int) $row[$fieldname];
						}
					}
				}

				if (sizeof($sql))
				{
					$sql = 'UPDATE ' . FORUMS_TABLE . '
						SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql) . '
						WHERE forum_id = ' . $forum_id;
					$_CLASS['core_db']->query($sql);
				}
			}
			break;

		case 'topic':
			$topic_data = $post_ids = $approved_unapproved_ids = $resync_forums = $delete_topics = $delete_posts = array();

			$sql = 'SELECT t.topic_id, t.forum_id, t.topic_approved, ' . (($sync_extra) ? 't.topic_attachment, t.topic_reported, ' : '') . 't.topic_poster, t.topic_time, t.topic_replies, t.topic_replies_real, t.topic_first_post_id, t.topic_first_poster_name, t.topic_last_post_id, t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_post_time
				FROM ' . TOPICS_TABLE . " t
				$where_sql";
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_id = (int) $row['topic_id'];
				$topic_data[$topic_id] = $row;
				$topic_data[$topic_id]['replies_real'] = -1;
				$topic_data[$topic_id]['first_post_id'] = 0;
				$topic_data[$topic_id]['last_post_id'] = 0;
				unset($topic_data[$topic_id]['topic_id']);

				// This array holds all topic_ids
				$delete_topics[$topic_id] = '';

				if ($sync_extra)
				{
					$topic_data[$topic_id]['reported'] = 0;
					$topic_data[$topic_id]['attachment'] = 0;
				}
			}
			$_CLASS['core_db']->free_result($result);

			// Use "t" as table alias because of the $where_sql clause
         // NOTE: 't.post_approved' in the GROUP BY is causing a major slowdown.
			$sql = 'SELECT t.topic_id, t.post_approved, COUNT(t.post_id) AS total_posts, MIN(t.post_id) AS first_post_id, MAX(t.post_id) AS last_post_id
				FROM ' . POSTS_TABLE . " t
				$where_sql
				GROUP BY t.topic_id"; //, t.post_approved";
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_id = (int) $row['topic_id'];

				$row['first_post_id'] = (int) $row['first_post_id'];
				$row['last_post_id'] = (int) $row['last_post_id'];

				if (!isset($topic_data[$topic_id]))
				{
					// Hey, these posts come from a topic that does not exist
					$delete_posts[$topic_id] = '';
				}
				else
				{
					// Unset the corresponding entry in $delete_topics
					// When we'll be done, only topics with no posts will remain
					unset($delete_topics[$topic_id]);

					$topic_data[$topic_id]['replies_real'] += $row['total_posts'];
					$topic_data[$topic_id]['first_post_id'] = (!$topic_data[$topic_id]['first_post_id']) ? $row['first_post_id'] : min($topic_data[$topic_id]['first_post_id'], $row['first_post_id']);

					if ($row['post_approved'] || !$topic_data[$topic_id]['last_post_id'])
					{
						$topic_data[$topic_id]['replies'] = $row['total_posts'] - 1;
						$topic_data[$topic_id]['last_post_id'] = $row['last_post_id'];
					}
				}
			}
			$_CLASS['core_db']->free_result($result);

			foreach ($topic_data as $topic_id => $row)
			{
				$post_ids[] = $row['first_post_id'];
				if ($row['first_post_id'] != $row['last_post_id'])
				{
					$post_ids[] = $row['last_post_id'];
				}
			}

			// Now we delete empty topics and orphan posts
			if (sizeof($delete_posts))
			{
				delete_posts('topic_id', array_keys($delete_posts), FALSE);
				unset($delete_posts);
			}
			if (!sizeof($topic_data))
			{
				// If we get there, topic ids were invalid or topics did not contain any posts
				delete_topics($where_type, $where_ids, TRUE);
				return;
			}
			if (sizeof($delete_topics))
			{
				$delete_topic_ids = array();
				foreach ($delete_topics as $topic_id => $void)
				{
					unset($topic_data[$topic_id]);
					$delete_topic_ids[] = $topic_id;
				}

				delete_topics('topic_id', $delete_topic_ids, FALSE);
				unset($delete_topics, $delete_topic_ids);
			}

			$sql = 'SELECT p.post_id, p.topic_id, p.post_approved, p.poster_id, p.post_username, p.post_time, u.username
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE p.post_id IN (' . implode(',', $post_ids) . ')
					AND u.user_id = p.poster_id';
			$result = $_CLASS['core_db']->query($sql);

			$post_ids = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_id = intval($row['topic_id']);

				if ($row['post_id'] == $topic_data[$topic_id]['first_post_id'])
				{
					if ($topic_data[$topic_id]['topic_approved'] != $row['post_approved'])
					{
						$approved_unapproved_ids[] = $topic_id;
					}
					$topic_data[$topic_id]['time'] = $row['post_time'];
					$topic_data[$topic_id]['poster'] = $row['poster_id'];
					$topic_data[$topic_id]['first_poster_name'] = ($row['poster_id'] == ANONYMOUS) ? $row['post_username'] : $row['username'];
				}
				if ($row['post_id'] == $topic_data[$topic_id]['last_post_id'])
				{
					$topic_data[$topic_id]['last_poster_id'] = $row['poster_id'];
					$topic_data[$topic_id]['last_post_time'] = $row['post_time'];
					$topic_data[$topic_id]['last_poster_name'] = ($row['poster_id'] == ANONYMOUS) ? $row['post_username'] : $row['username'];
				}
			}
			$_CLASS['core_db']->free_result($result);

			// approved becomes unapproved, and vice-versa
			if (sizeof($approved_unapproved_ids))
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_approved = 1 - topic_approved
					WHERE topic_id IN (' . implode(', ', $approved_unapproved_ids) . ')';
				$_CLASS['core_db']->query($sql);
			}
			unset($approved_unapproved_ids);

			// These are fields that will be synchronised
			$fieldnames = array('time', 'replies', 'replies_real', 'poster', 'first_post_id', 'first_poster_name', 'last_post_id', 'last_post_time', 'last_poster_id', 'last_poster_name');

			if ($sync_extra)
			{
				// This routine assumes that post_reported values are correct
				// if they are not, use sync('post_reported') first
				$sql = 'SELECT t.topic_id, p.post_id
					FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . " p
					$where_sql_and p.topic_id = t.topic_id
						AND p.post_reported = 1
					GROUP BY t.topic_id";
				$result = $_CLASS['core_db']->query($sql);

				$fieldnames[] = 'reported';
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$topic_data[intval($row['topic_id'])]['reported'] = 1;
				}
				$_CLASS['core_db']->free_result($result);

				// This routine assumes that post_attachment values are correct
				// if they are not, use sync('post_attachment') first
				$sql = 'SELECT t.topic_id, p.post_id
					FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . " p
					$where_sql_and p.topic_id = t.topic_id
						AND p.post_attachment = 1
					GROUP BY t.topic_id";
				$result = $_CLASS['core_db']->query($sql);

				$fieldnames[] = 'attachment';
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$topic_data[intval($row['topic_id'])]['attachment'] = 1;
				}
				$_CLASS['core_db']->free_result($result);
			}

			foreach ($topic_data as $topic_id => $row)
			{
				$sql = array();

				foreach ($fieldnames as $fieldname)
				{
					if ($row['topic_' . $fieldname] != $row[$fieldname])
					{
						$sql['topic_' . $fieldname] = $row[$fieldname];
					}
				}

				if (sizeof($sql))
				{
					$sql = 'UPDATE ' . TOPICS_TABLE . '
						SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql) . '
						WHERE topic_id = ' . $topic_id;
					$_CLASS['core_db']->query($sql);

					$resync_forums[$row['forum_id']] = $row['forum_id'];
				}
			}
			unset($topic_data);

			// if some topics have been resync'ed then resync parent forums
			// except when we're only syncing a range, we don't want to sync forums during
			// batch processing.
			if ($resync_parents && sizeof($resync_forums) && $where_type != 'range')
			{
				sync('forum', 'forum_id', $resync_forums, TRUE);
			}
			break;
	}
}

function prune($forum_id, $prune_mode, $prune_date, $prune_flags = 0, $auto_sync = true)
{
	global $_CLASS;

	$sql_forum = (is_array($forum_id)) ? ' IN (' . implode(',', $forum_id) . ')' : " = $forum_id";

	$sql_and = '';
	if (!($prune_flags & 4))
	{
		$sql_and .= ' AND topic_type <> ' . POST_ANNOUNCE;
	}
	if (!($prune_flags & 8))
	{
		$sql_and .= ' AND topic_type <> ' . POST_STICKY;
	}

	if ($prune_mode == 'posted')
	{
		$sql_and .= " AND topic_last_post_time < $prune_date";
	}
	if ($prune_mode == 'viewed')
	{
		$sql_and .= " AND topic_last_view_time < $prune_date";
	}

	$sql = 'SELECT topic_id
		FROM ' . TOPICS_TABLE . "
		WHERE forum_id $sql_forum
			AND poll_start = 0 
			$sql_and";
	$result = $_CLASS['core_db']->query($sql);

	$topic_list = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$topic_list[] = $row['topic_id'];
	}
	$_CLASS['core_db']->free_result($result);

	if ($prune_flags & 2)
	{
		$sql = 'SELECT topic_id
			FROM ' . TOPICS_TABLE . "
			WHERE forum_id $sql_forum 
				AND poll_start > 0 
				AND poll_last_vote < $prune_date 
				$sql_and";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$topic_list[] = $row['topic_id'];
		}
		$_CLASS['core_db']->free_result($result);

		$topic_list = array_unique($topic_list);
	}

	return delete_topics('topic_id', $topic_list, $auto_sync);
}

// Function auto_prune(), this function now relies on passed vars
function auto_prune($forum_id, $prune_mode, $prune_flags, $prune_days, $prune_freq)
{
	global $_CLASS;

	$sql = 'SELECT forum_name
		FROM ' . FORUMS_TABLE . "
		WHERE forum_id = $forum_id";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$prune_date = time() - ($prune_days * 86400);
		$next_prune = time() + ($prune_freq * 86400);

		prune($forum_id, $prune_mode, $prune_date, $prune_flags, true);

		$sql = 'UPDATE ' . FORUMS_TABLE . "
			SET prune_next = $next_prune
			WHERE forum_id = $forum_id";
		$_CLASS['core_db']->query($sql);

		add_log('admin', 'LOG_AUTO_PRUNE', $row['forum_name']);
	}
	$_CLASS['core_db']->free_result($result);

	return;
}

// remove_comments will strip the sql comment lines out of an uploaded sql file
// specifically for mssql and postgres type files in the install....
function remove_comments(&$output)
{
	$lines = explode("\n", $output);
	$output = '';

	// try to keep mem. use down
	$linecount = sizeof($lines);

	$in_comment = false;
	for($i = 0; $i < $linecount; $i++)
	{
		if (trim($lines[$i]) == '/*')
		{
			$in_comment = true;
		}

		if (!$in_comment)
		{
			$output .= $lines[$i] . "\n";
		}

		if (trim($lines[$i]) == '*/')
		{
			$in_comment = false;
		}
	}

	unset($lines);
	return $output;
}

// remove_remarks will strip the sql comment lines out of an uploaded sql file
function remove_remarks(&$sql)
{
	$sql = preg_replace('/(\n){2,}/', "\n", preg_replace('/^#.*/m', "\n", $sql));
}

// split_sql_file will split an uploaded sql file into single sql statements.
// Note: expects trim() to have already been run on $sql.
function split_sql_file($sql, $delimiter)
{
	// Split up our string into "possible" SQL statements.
	$tokens = explode($delimiter, $sql);

	// try to save mem.
	$sql = '';
	$output = array();

	// we don't actually care about the matches preg gives us.
	$matches = array();

	// this is faster than calling count($oktens) every time thru the loop.
	$token_count = sizeof($tokens);
	for ($i = 0; $i < $token_count; $i++)
	{
		// Don't wanna add an empty string as the last thing in the array.
		if ($i != $token_count - 1)
		{
			// This is the total number of single quotes in the token.
			$total_quotes = preg_match_all("#'#", $tokens[$i], $matches);
			// Counts single quotes that are preceded by an odd number of backslashes,
			// which means they're escaped quotes.
			$escaped_quotes = preg_match_all("#(?<!\\\\)(\\\\\\\\)*\\\\'#", $tokens[$i], $matches);

			$unescaped_quotes = $total_quotes - $escaped_quotes;

			// If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
			if (!($unescaped_quotes % 2))
			{
				// It's a complete sql statement.
				$output[] = $tokens[$i];
				// save memory.
				$tokens[$i] = '';
			}
			else
			{
				// incomplete sql statement. keep adding tokens until we have a complete one.
				// $temp will hold what we have so far.
				$temp = $tokens[$i] . $delimiter;
				// save memory..
				$tokens[$i] = '';

				// Do we have a complete statement yet?
				$complete_stmt = false;

				for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
				{
					// This is the total number of single quotes in the token.
					$total_quotes = preg_match_all("#'#", $tokens[$j], $matches);
					// Counts single quotes that are preceded by an odd number of backslashes,
					// which means they're escaped quotes.
					$escaped_quotes = preg_match_all("#(?<!\\\\)(\\\\\\\\)*\\\\'#", $tokens[$j], $matches);

					$unescaped_quotes = $total_quotes - $escaped_quotes;

					if (($unescaped_quotes % 2) == 1)
					{
						// odd number of unescaped quotes. In combination with the previous incomplete
						// statement(s), we now have a complete statement. (2 odds always make an even)
						$output[] = $temp . $tokens[$j];

						// save memory.
						$tokens[$j] = '';
						$temp = '';

						// exit the loop.
						$complete_stmt = true;
						// make sure the outer loop continues at the right point.
						$i = $j;
					}
					else
					{
						// even number of unescaped quotes. We still don't have a complete statement.
						// (1 odd and 1 even always make an odd)
						$temp .= $tokens[$j] . $delimiter;
						// save memory.
						$tokens[$j] = '';
					}
				} // for..
			} // else
		}
	}

	return $output;
}

// Cache moderators, called whenever permissions are changed via admin_permissions. Changes of username
// and group names must be carried through for the moderators table
function cache_moderators()
{
	global $_CLASS;

	// Clear table
	$sql = ($_CLASS['core_db']->db_layer != 'sqlite') ? 'TRUNCATE ' . MODERATOR_TABLE : 'DELETE FROM ' . MODERATOR_TABLE;
	$_CLASS['core_db']->query($sql);

	// Holding array
	$m_sql = array();
	$user_id_sql = '';

	$sql = 'SELECT a.forum_id, u.user_id, u.username
		FROM  ' . ACL_OPTIONS_TABLE . '  o, ' . ACL_USERS_TABLE . ' a,  ' . USERS_TABLE . "  u
		WHERE o.auth_option = 'm_'
			AND a.auth_option_id = o.auth_option_id
			AND a.auth_setting = " . ACL_YES . ' 
			AND u.user_id = a.user_id';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$m_sql['f_' . $row['forum_id'] . '_u_' . $row['user_id']] = $row['forum_id'] . ', ' . $row['user_id'] . ", '" . $row['username'] . "', NULL, NULL";
		$user_id_sql .= (($user_id_sql) ? ', ' : '') . $row['user_id'];
	}
	$_CLASS['core_db']->free_result($result);

	// Remove users who have group memberships with DENY moderator permissions
	if ($user_id_sql)
	{
		$sql = 'SELECT a.forum_id, ug.user_id
			FROM  ' . ACL_OPTIONS_TABLE . '  o, ' . ACL_GROUPS_TABLE . ' a,  ' . USER_GROUP_TABLE . "  ug
			WHERE o.auth_option = 'm_'
				AND a.auth_option_id = o.auth_option_id
				AND a.auth_setting = " . ACL_NO . " 
				AND a.group_id = ug.group_id
				AND ug.user_id IN ($user_id_sql)";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			unset($m_sql['f_' . $row['forum_id'] . '_u_' . $row['user_id']]);
		}
		$_CLASS['core_db']->free_result($result);
	}

	$sql = 'SELECT a.forum_id, g.group_name, g.group_id
		FROM  ' . ACL_OPTIONS_TABLE . '  o, ' . ACL_GROUPS_TABLE . ' a,  ' . GROUPS_TABLE . "  g
		WHERE o.auth_option = 'm_'
			AND a.auth_option_id = o.auth_option_id
			AND a.auth_setting = " . ACL_YES . '
			AND g.group_id = a.group_id
			AND g.group_type NOT IN (' . GROUP_HIDDEN . ', ' . GROUP_SPECIAL . ')';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$m_sql['f_' . $row['forum_id'] . '_g_' . $row['group_id']] = $row['forum_id'] . ', NULL, NULL, ' . $row['group_id'] . ", '" . $row['group_name'] . "'";
	}
	$_CLASS['core_db']->free_result($result);

	if (sizeof($m_sql))
	{
		switch ($_CLASS['core_db']->db_layer)
		{
			case 'mysql':
			
				$sql = 'INSERT INTO ' . MODERATOR_TABLE . ' (forum_id, user_id, username, group_id, groupname) 
					VALUES ' . implode(', ', preg_replace('#^(.*)$#', '(\1)',  $m_sql));
				$_CLASS['core_db']->query($sql);
				break;

			case 'mysql4':
			case 'mysqli':
			case 'mssql':
			case 'sqlite':
				$sql = 'INSERT INTO ' . MODERATOR_TABLE . ' (forum_id, user_id, username, group_id, groupname)
					 ' . implode(' UNION ALL ', preg_replace('#^(.*)$#', 'SELECT \1',  $m_sql));
				$_CLASS['core_db']->query($sql);
				break;

			default:
				foreach ($m_sql as $k => $sql)
				{
					$sql = 'INSERT INTO ' . MODERATOR_TABLE . " (forum_id, user_id, username, group_id, groupname) 
						VALUES ($sql)";
					$_CLASS['core_db']->query($sql);
				}
		}
	}
}

// Logging functions
function add_log()
{
	global $_CLASS;

	$args = func_get_args();

	$mode			= array_shift($args);
	$reportee_id	= ($mode == 'user') ? intval(array_shift($args)) : '';
	$forum_id		= ($mode == 'mod') ? intval(array_shift($args)) : '';
	$topic_id		= ($mode == 'mod') ? intval(array_shift($args)) : '';
	$action			= array_shift($args);
	$data			= (!sizeof($args)) ? '' : $_CLASS['core_db']->escape(serialize($args));

	switch ($mode)
	{
		case 'admin':
			$sql = 'INSERT INTO ' . LOG_TABLE . ' (log_type, user_id, log_ip, log_time, log_operation, log_data)
				VALUES (' . LOG_ADMIN . ', ' . $_CLASS['core_user']->data['user_id'] . ", '".$_CLASS['core_user']->ip."', " . time() . ", '$action', '$data')";
			break;
		
		case 'mod':
			$sql = 'INSERT INTO ' . LOG_TABLE . ' (log_type, user_id, forum_id, topic_id, log_ip, log_time, log_operation, log_data)
				VALUES (' . LOG_MOD . ', ' . $_CLASS['core_user']->data['user_id'] . ", $forum_id, $topic_id, '".$_CLASS['core_user']->ip."', " . time() . ", '$action', '$data')";
			break;

		case 'user':
			$sql = 'INSERT INTO ' . LOG_TABLE . ' (log_type, user_id, reportee_id, log_ip, log_time, log_operation, log_data)
				VALUES (' . LOG_USERS . ', ' . $_CLASS['core_user']->data['user_id'] . ", $reportee_id, '".$_CLASS['core_user']->ip."', " . time() . ", '$action', '$data')";
			break;

		case 'critical':
			$sql = 'INSERT INTO ' . LOG_TABLE . ' (log_type, user_id, log_ip, log_time, log_operation, log_data)
				VALUES (' . LOG_CRITICAL . ', ' . $_CLASS['core_user']->data['user_id'] . ", '".$_CLASS['core_user']->ip."', " . time() . ", '$action', '$data')";
			break;
		
		default:
			return;
	}

	$_CLASS['core_db']->query($sql);
	return;
}

function view_log($mode, &$log, &$log_count, $limit = 0, $offset = 0, $forum_id = 0, $topic_id = 0, $user_id = 0, $limit_days = 0, $sort_by = 'l.log_time DESC')
{
	global $_CLASS, $phpEx;

	$topic_id_list = $is_auth = $is_mod = array();

	switch ($mode)
	{
		case 'admin':
			$log_type = LOG_ADMIN;
			$sql_forum = '';
			break;
		
		case 'mod':
			$log_type = LOG_MOD;

			if ($topic_id)
			{
				$sql_forum = 'AND l.topic_id = ' . intval($topic_id);
			}
			else if (is_array($forum_id))
			{
				$sql_forum = 'AND l.forum_id IN (' . implode(', ', array_map('intval', $forum_id)) . ')';
			}
			else
			{
				$sql_forum = ($forum_id) ? 'AND l.forum_id = ' . intval($forum_id) : '';
			}
			break;

		case 'user':
			$log_type = LOG_USERS;
			$sql_forum = 'AND l.reportee_id = ' . intval($user_id);
			break;
		
		case 'critical':
			$log_type = LOG_CRITICAL;
			$sql_forum = '';
			break;
		
		default:
			return;
	}

	$sql = "SELECT l.*, u.username
		FROM " . LOG_TABLE . " l, " . USERS_TABLE . " u
		WHERE l.log_type = $log_type
			AND u.user_id = l.user_id
			" . (($limit_days) ? "AND l.log_time >= $limit_days" : '') . "
			$sql_forum
		ORDER BY $sort_by";
	$result = $_CLASS['core_db']->query_limit($sql, $limit, $offset);

	$i = 0;
	$log = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($row['topic_id'])
		{
			$topic_id_list[] = $row['topic_id'];
		}
		
		$profile_url = (VIPERAL == 'Admin') ? generate_link('admin_users&amp;u=' . $row['user_id'], array('admin' => true)) : generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']);

		$log[$i]['id'] = $row['log_id'];
		$log[$i]['username'] = '<a href="' . $profile_url . '">' . $row['username'] . '</a>';
		$log[$i]['ip'] = $row['log_ip'];
		$log[$i]['time'] = $row['log_time'];
		$log[$i]['forum_id'] = $row['forum_id'];
		$log[$i]['topic_id'] = $row['topic_id'];
		$log[$i]['viewforum'] = ($row['forum_id'] && $_CLASS['auth']->acl_get('f_read', $row['forum_id'])) ? ((defined('IN_ADMIN')) ? '../' : '') . generate_link('Forums&amp;file=viewforum&amp;f=' . $row['forum_id']) : '';

		$log[$i]['action'] = (isset($_CLASS['core_user']->lang[$row['log_operation']])) ? $_CLASS['core_user']->lang[$row['log_operation']] : '{' . ucfirst(str_replace('_', ' ', $row['log_operation'])) . '}';
		
		if (!empty($row['log_data']))
		{
			$log_data_ary = unserialize(stripslashes($row['log_data']));

			if (isset($_CLASS['core_user']->lang[$row['log_operation']]))
			{
				foreach ($log_data_ary as $log_data)
				{
					$log_data = str_replace("\n", '<br />', censor_text($log_data));

					$log[$i]['action'] = preg_replace('#%s#', $log_data, $log[$i]['action'], 1);
				}
			}
			else
			{
				$log[$i]['action'] .= '<br />' . implode('', $log_data_ary);
			}
		}

		$i++;
	}
	$_CLASS['core_db']->free_result($result);

	if (sizeof($topic_id_list))
	{
		$topic_id_list = array_unique($topic_id_list);

		// This query is not really needed if move_topics() updates the forum_id field, 
		// altough it's also used to determine if the topic still exists in the database
		$sql = 'SELECT topic_id, forum_id
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id IN (' . implode(', ', array_map('intval', $topic_id_list)) . ')';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($_CLASS['auth']->acl_get('f_read', $row['forum_id']))
			{
				// DEBUG!! - global topic
				$config['default_forum_id'] = 2;
				$is_auth[$row['topic_id']] = ($row['forum_id']) ? $row['forum_id'] : $config['default_forum_id'];
			}

			if ($_CLASS['auth']->acl_gets('a_', 'm_', $row['forum_id']))
			{
				$is_mod[$row['topic_id']] = $row['forum_id'];
			}
		}

		foreach ($log as $key => $row)
		{
			$log[$key]['viewtopic'] = (isset($is_auth[$row['topic_id']])) ? ((defined('IN_ADMIN')) ? '../' : '') . generate_link('Forums&amp;file=viewtopic&amp;f=' . $is_auth[$row['topic_id']] . '&amp;t=' . $row['topic_id']) : '';
			$log[$key]['viewlogs'] = (isset($is_mod[$row['topic_id']])) ? ((defined('IN_ADMIN')) ? '../' : '') . generate_link('Forums&amp;file=mcp&amp;mode=topic_view&amp;action=viewlogs&amp;t=' . $row['topic_id']) : '';
		}
	}

	$sql = 'SELECT COUNT(l.log_id) AS total_entries
		FROM ' . LOG_TABLE . " l
		WHERE l.log_type = $log_type
			AND l.log_time >= $limit_days
			$sql_forum";
	$result = $_CLASS['core_db']->query($sql);

	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	$log_count =  $row['total_entries'];

	return;
}

// Extension of auth class for changing permissions
if (class_exists('auth'))
{
	class auth_admin extends auth
	{
		// Set a user or group ACL record
		function acl_set($ug_type, &$forum_id, &$ug_id, &$auth)
		{
			global $_CLASS;

			// One or more forums
			if (!is_array($forum_id))
			{
				$forum_id = array($forum_id);
			}

			// Set any flags as required
			foreach ($auth as $auth_option => $setting)
			{
				$postion = strpos($auth_option, '_');
				if ($postion == false)
				{
					continue;
				}

				$flag = substr($auth_option, 0, $postion + 1);

				if (empty($auth[$flag]) && (empty($holding_auth[$flag]) || ($holding_auth[$flag] == ACL_NO && $setting == ACL_YES)))
				{
					$holding_auth[$flag] =  $setting;
				}
			}

			$sql = 'SELECT auth_option_id, auth_option
				FROM ' . ACL_OPTIONS_TABLE;
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$option_ids[$row['auth_option']] = $row['auth_option_id'];
			}
			$_CLASS['core_db']->free_result($result);

			$sql_forum = 'AND a.forum_id IN (' . implode(', ', array_map('intval', $forum_id)) . ')';
			$id_field  = $ug_type . '_id';

			$sql =  'SELECT o.auth_option_id, o.auth_option, a.forum_id, a.auth_setting
						FROM ' . ACL_TABLE . ' a, ' . ACL_OPTIONS_TABLE . " o
							WHERE a.auth_option_id = o.auth_option_id $sql_forum
							AND a.$id_field = $ug_id";

			$result = $_CLASS['core_db']->query($sql);

			$cur_auth = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$cur_auth[$row['forum_id']][$row['auth_option_id']] = $row['auth_setting'];
			}
			$_CLASS['core_db']->free_result($result);

			$sql_ary = array();
			foreach ($forum_id as $forum)
			{
				$delete_option_ids = $update_option_ids =array();

				foreach ($auth as $auth_option => $setting)
				{
					$auth_option_id = $option_ids[$auth_option];

					switch ($setting)
					{
						case ACL_UNSET:
							if (isset($cur_auth[$forum][$auth_option_id]))
							{
								$delete_option_ids[] = (int) $auth_option_id;
							}
							break;

						default:
							if (!isset($cur_auth[$forum][$auth_option_id]))
							{
								$sql_ary['insert'][] = "$ug_id, $forum, $auth_option_id, $setting";
							}
							elseif ($cur_auth[$forum][$auth_option_id] != $setting)
							{
								$update_option_ids[$setting][] = (int) $auth_option_id;
							}
					}
				}

				if (!empty($update_option_ids))
				{
					foreach ($update_option_ids as $setting => $option_ids)
					{
						$sql_ary['update'][] = 'UPDATE ' . ACL_TABLE . " 
							SET auth_setting = $setting 
							WHERE $id_field = $ug_id 
								AND forum_id = $forum 
								AND auth_option_id IN (" . implode(', ', $option_ids) . ')';
					}
				}

				if (!empty($delete_option_ids))
				{
					$sql_ary['delete'][] = 'DELETE FROM '.ACL_TABLE ."
					WHERE $id_field = $ug_id
						AND forum_id = $forum
						AND auth_option_id IN (" . implode(', ', $delete_option_ids) . ')';
				}
			}
			unset($cur_auth);

			
			foreach ($sql_ary as $sql_type => $sql_subary)
			{
				$sql = '';

				switch ($sql_type)
				{
					case 'insert':
						switch ($_CLASS['core_db']->db_layer)
						{
							case 'mysql':
								$sql = 'VALUES ' . implode(', ', preg_replace('#^(.*?)$#', '(\1)', $sql_subary));
							break;

							case 'mysql4':
							case 'mysqli':
							case 'mssql':
							case 'sqlite':
								$sql = implode(' UNION ALL ', preg_replace('#^(.*?)$#', 'SELECT \1', $sql_subary));
							break;

							default:
								foreach ($sql_subary as $sql)
								{
									$sql = 'INSERT INTO '.ACL_TABLE." ($id_field, forum_id, auth_option_id, auth_setting) VALUES ($sql)";
									$_CLASS['core_db']->query($sql);
								}
								$sql = '';
							break;
						}

						if ($sql)
						{
							$sql = 'INSERT INTO '.ACL_TABLE." ($id_field, forum_id, auth_option_id, auth_setting) $sql";
							$_CLASS['core_db']->query($sql);
						}
					break;

					case 'update':
					case 'delete':
						foreach ($sql_subary as $sql)
						{
							$result = $_CLASS['core_db']->query($sql);
						}
					break;
				}
				unset($sql_ary[$sql_type]);
			}
			unset($sql_ary);

			$this->acl_clear_prefetch();
		}

		function acl_delete($mode, &$forum_id, &$ug_id, $auth_ids = false)
		{
			global $_CLASS;

			// One or more forums
			if (!is_array($forum_id))
			{
				$forum_id = array($forum_id);
			}

			$auth_sql = ($auth_ids) ? ' AND auth_option_id IN (' . implode(', ', array_map('intval', $auth_ids)) . ')' : '';
			$id_field  = $mode . '_id';

			foreach ($forum_id as $forum)
			{
				$sql = 'DELETE FROM '.ACL_TABLE."
					WHERE $id_field = $ug_id
						AND forum_id = $forum
						$auth_sql";
				$_CLASS['core_db']->query($sql);
			}

			$this->acl_clear_prefetch();
		}

		// NOTE: this function is not in use atm
		// Add a new option to the list ... $options is a hash of form ->
		// $options = array(
		//	'local'		=> array('option1', 'option2', ...),
		//	'global'	=> array('optionA', 'optionB', ...)
		//);
		function acl_add_option($options)
		{
			global $_CLASS;

			if (!is_array($options))
			{
				trigger_error('Incorrect parameter for acl_add_option', E_USER_ERROR);
			}

			$cur_options = array();

			$sql = "SELECT auth_option, is_global, is_local
				FROM " . ACL_OPTIONS_TABLE . "
				ORDER BY auth_option_id";
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if (!empty($row['is_global']))
				{
					$cur_options['global'][] = $row['auth_option'];
				}

				if (!empty($row['is_local']))
				{
					$cur_options['local'][] = $row['auth_option'];
				}
			}
			$_CLASS['core_db']->free_result($result);

			// Here we need to insert new options ... this requires discovering whether
			// an options is global, local or both and whether we need to add an option
			// type flag (x_)
			$new_options = array('local' => array(), 'global' => array());
			foreach ($options as $type => $option_ary)
			{
				$option_ary = array_unique($option_ary);
				foreach ($option_ary as $option_value)
				{
					if (!in_array($option_value, $cur_options[$type]))
					{
						$new_options[$type][] = $option_value;
					}

					$flag = substr($option_value, 0, strpos($option_value, '_') + 1);
					if (!in_array($flag, $cur_options[$type]) && !in_array($flag, $new_options[$type]))
					{
						$new_options[$type][] = $flag;
					}
				}
			}
			unset($options);

			$options = array();
			$options['local'] = array_diff($new_options['local'], $new_options['global']);
			$options['global'] = array_diff($new_options['global'], $new_options['local']);
			$options['local_global'] = array_intersect($new_options['local'], $new_options['global']);

			$type_sql = array('local' => '0, 1', 'global' => '1, 0', 'local_global' => '1, 1');

			$sql = '';
			foreach ($options as $type => $option_ary)
			{
				foreach ($option_ary as $option)
				{
					switch ($_CLASS['core_db']->db_layer)
					{
						case 'mysql':
							$sql .= (($sql != '') ? ', ' : '') . "('$option', " . $type_sql[$type] . ")";
							break;
							
						case 'mysql4':
						case 'mysqli':
						case 'mssql':
						case 'sqlite':
							$sql .= (($sql != '') ? ' UNION ALL ' : '') . " SELECT '$option', " . $type_sql[$type];
							break;
							
						default:
							$sql = 'INSERT INTO ' . ACL_OPTIONS_TABLE . " (auth_option, is_global, is_local)
								VALUES ($option, " . $type_sql[$type] . ")";
							$_CLASS['core_db']->query($sql);
							$sql = '';
					}
				}
			}

			if ($sql != '')
			{
				$sql = 'INSERT INTO ' . ACL_OPTIONS_TABLE . " (auth_option, is_global, is_local)
					VALUES $sql";
				$_CLASS['core_db']->query($sql);
			}

			$_CLASS['core_cache']->destroy('acl_options');
		}
	}
}

// Update Post Informations (First/Last Post in topic/forum)
// Should be used instead of sync() if only the last post informations are out of sync... faster
function update_post_information($type, $ids)
{
	global $_CLASS;

	if (!is_array($ids))
	{
		$ids = array($ids);
	}

	$update_sql = $empty_forums = array();

	$sql = 'SELECT ' . $type . '_id, MAX(post_id) as last_post_id
		FROM ' . POSTS_TABLE . "
		WHERE post_approved = 1
			AND {$type}_id IN (" . implode(', ', $ids) . ")
		GROUP BY {$type}_id";
	$result = $_CLASS['core_db']->query($sql);

	$last_post_ids = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($type == 'forum')
		{
			$empty_forums[] = $row['forum_id'];
		}

		$last_post_ids[] = $row['last_post_id'];
	}
	$_CLASS['core_db']->free_result($result);

	if ($type == 'forum')
	{
		$empty_forums = array_diff($ids, $empty_forums);

		foreach ($empty_forums as $void => $forum_id)
		{
			$update_sql[$forum_id][] = 'forum_last_post_id = 0';
			$update_sql[$forum_id][] =	'forum_last_post_time = 0';
			$update_sql[$forum_id][] = 'forum_last_poster_id = 0';
			$update_sql[$forum_id][] = "forum_last_poster_name = ''";
		}
	}

	if (sizeof($last_post_ids))
	{
		$sql = 'SELECT p.' . $type . '_id, p.post_id, p.post_time, p.poster_id, p.post_username, u.user_id, u.username
			FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
			WHERE p.poster_id = u.user_id
				AND p.post_id IN (' . implode(', ', $last_post_ids) . ')';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$update_sql[$row["{$type}_id"]][] = $type . '_last_post_id = ' . (int) $row['post_id'];
			$update_sql[$row["{$type}_id"]][] = $type . '_last_post_time = ' . (int) $row['post_time'];
			$update_sql[$row["{$type}_id"]][] = $type . '_last_poster_id = ' . (int) $row['poster_id'];
			$update_sql[$row["{$type}_id"]][] = "{$type}_last_poster_name = '" . (($row['poster_id'] == ANONYMOUS) ? $_CLASS['core_db']->escape($row['post_username']) : $_CLASS['core_db']->escape($row['username'])) . "'";
		}
		$_CLASS['core_db']->free_result($result);
	}
	unset($empty_forums, $ids, $last_post_ids);

	if (!sizeof($update_sql))
	{
		return;
	}

	$table = ($type == 'forum') ? FORUMS_TABLE : TOPICS_TABLE;

	foreach ($update_sql as $update_id => $update_sql_ary)
	{
		$sql = "UPDATE $table
			SET " . implode(', ', $update_sql_ary) . "
			WHERE {$type}_id = $update_id";
		$_CLASS['core_db']->query($sql);
	}
}

/**
* Tidy topic tracking tables
* Removes all tracking rows older than 6 months, including mark_posted informations
*/
function tidy_database()
{
	global $_CLASS;

	$remove_date = time() - (3 * 62 * 24 * 3600);

	$sql = 'DELETE FROM ' . FORUMS_TRACK_TABLE . '
		WHERE mark_time < ' . $remove_date;
	$_CLASS['core_db']->query($sql);

	$sql = 'DELETE FROM ' . TOPICS_TRACK_TABLE . '
		WHERE mark_time < ' . $remove_date;
	$_CLASS['core_db']->query($sql);

	set_config('database_last_gc', time(), true);
}

?>