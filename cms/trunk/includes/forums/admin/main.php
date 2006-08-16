<?php
// -------------------------------------------------------------
//
// $Id: index.php,v 1.24 2004/05/26 18:55:25 acydburn Exp $
//
// FILENAME  : adm/index.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Do we have any admin permissions at all?
if (!$_CLASS['auth']->acl_get('a_'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Define some vars
$action = request_var('action', '');

switch ($action)
{
	case 'stats':
		if (!$_CLASS['auth']->acl_get('a_board'))
		{
			trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
		}

		$sql = 'SELECT COUNT(post_id) AS stat 
			FROM ' . FORUMS_POSTS_TABLE . '
			WHERE post_approved = 1';
		$result = $_CLASS['core_db']->query($sql);

		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		set_config('num_posts', (int) $row['stat'], true);

		$sql = 'SELECT COUNT(topic_id) AS stat
			FROM ' . FORUMS_TOPICS_TABLE . '
			WHERE topic_approved = 1';
		$result = $_CLASS['core_db']->query($sql);

		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		set_config('num_topics', (int) $row['stat'], true);

		$sql = 'SELECT COUNT(attach_id) as stat
			FROM ' . FORUMS_ATTACHMENTS_TABLE;
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
	
		set_config('num_files', (int) $row['stat'], true);
		$_CLASS['core_db']->free_result($result);

		$sql = 'SELECT SUM(filesize) as stat
			FROM ' . FORUMS_ATTACHMENTS_TABLE;
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);

		set_config('upload_dir_size', (int) $row['stat'], true);
		$_CLASS['core_db']->free_result($result);

		add_log('admin', 'LOG_RESYNC_STATS');
	break;
		
	case 'user':
		if (!$_CLASS['auth']->acl_get('a_defaults'))
		{
			trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
		}

		$sql = 'SELECT COUNT(post_id) AS num_posts, poster_id
			FROM ' . FORUMS_POSTS_TABLE . '
			WHERE post_postcount = 1
			GROUP BY poster_id';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$_CLASS['core_db']->query('UPDATE ' . CORE_USERS_TABLE . " SET user_posts = {$row['num_posts']} WHERE user_id = {$row['poster_id']}");
		}
		$_CLASS['core_db']->free_result($result);

		add_log('admin', 'LOG_RESYNC_POSTCOUNTS');
	break;
}

// Get forum statistics
$total_posts = $config['num_posts'];
$total_topics = $config['num_topics'];
$total_users = $_CORE_CONFIG['user']['total_users'];
$total_files = $config['num_files'];

$start_date = $_CLASS['core_user']->format_date($config['board_start_date']);

$boarddays = ($_CLASS['core_user']->time - $config['board_start_date']) / 86400;

$posts_per_day = sprintf('%.2f', $total_posts / $boarddays);
$topics_per_day = sprintf('%.2f', $total_topics / $boarddays);
$users_per_day = sprintf('%.2f', $total_users / $boarddays);
$files_per_day = sprintf('%.2f', $total_files / $boarddays);

$upload_dir_size = ($config['upload_dir_size'] >= 1048576) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['MB'], ($config['upload_dir_size'] / 1048576)) : (($config['upload_dir_size'] >= 1024) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['KB'], ($config['upload_dir_size'] / 1024)) : sprintf('%.2f ' . $_CLASS['core_user']->lang['BYTES'], $config['upload_dir_size']));

$avatar_dir_size = 0;

if ($avatar_dir = @opendir($_CORE_CONFIG['global']['path_avatar_upload']))
{
	while (($file = readdir($avatar_dir)) !== false)
	{
		if ($file{0} != '.' && $file != 'CVS' && strpos($file, 'index.') === false)
		{
			$avatar_dir_size += filesize($_CORE_CONFIG['global']['path_avatar_upload'] . '/' . $file);
		}
	}
	@closedir($avatar_dir);

	// This bit of code translates the avatar directory size into human readable format
	// Borrowed the code from the PHP.net annoted manual, origanally written by:
	// Jesse (jesse@jess.on.ca)
	$avatar_dir_size = ($avatar_dir_size >= 1048576) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['MB'], ($avatar_dir_size / 1048576)) : (($avatar_dir_size >= 1024) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['KB'], ($avatar_dir_size / 1024)) : sprintf('%.2f ' . $_CLASS['core_user']->lang['BYTES'], $avatar_dir_size));
}
else
{
	// Couldn't open Avatar dir.
	$avatar_dir_size = $_CLASS['core_user']->lang['NOT_AVAILABLE'];
}

if ($posts_per_day > $total_posts)
{
	$posts_per_day = $total_posts;
}

if ($topics_per_day > $total_topics)
{
	$topics_per_day = $total_topics;
}

if ($users_per_day > $total_users)
{
	$users_per_day = $total_users;
}

if ($files_per_day > $total_files)
{
	$files_per_day = $total_files;
}

// Remove
$dbsize = $_CLASS['core_user']->lang['NOT_AVAILABLE'];
$s_action_options = build_select(array('online' => 'RESET_ONLINE', 'date' => 'RESET_DATE', 'stats' => 'RESYNC_STATS', 'user' => 'RESYNC_POSTCOUNTS'));

$_CLASS['core_template']->assign_array(array(
	'TOTAL_POSTS'		=> $total_posts,
	'POSTS_PER_DAY'		=> $posts_per_day,
	'TOTAL_TOPICS'		=> $total_topics,
	'TOPICS_PER_DAY'	=> $topics_per_day,
	'TOTAL_USERS'		=> $total_users,
	'USERS_PER_DAY'		=> $users_per_day,
	'TOTAL_FILES'		=> $total_files,
	'FILES_PER_DAY'		=> $files_per_day,
	'START_DATE'		=> $start_date,
	'AVATAR_DIR_SIZE'	=> $avatar_dir_size,
	'UPLOAD_DIR_SIZE'	=> $upload_dir_size,

	'U_ACTION'			=> generate_link('forums', array('admin' => true)),
	'U_ADMIN_LOG'		=> generate_link('forums&amp;i=logs&amp;mode=admin', array('admin' => true)),

	'S_ACTION_OPTIONS'	=> $_CLASS['forums_auth']->acl_get('a_board') ? $s_action_options : '',
));

$log_data = array();
$log_count = 0;

if (!$_CLASS['forums_auth']->acl_get('a_viewlogs'))
{
	view_log('admin', $log_data, $log_count, 5);

	foreach ($log_data as $row)
	{
		$_CLASS['core_template']->assign_vars_array('log', array(
			'USERNAME'	=> $row['username'],
			'IP'		=> $row['ip'],
			'DATE'		=> $_CLASS['core_user']->format_date($row['time']),
			'ACTION'	=> $row['action']
		));
	}
}

$_CLASS['core_display']->display($_CLASS['core_user']->lang['ADMIN_INDEX'], 'modules/forums/admin/acp_main.html');

?>