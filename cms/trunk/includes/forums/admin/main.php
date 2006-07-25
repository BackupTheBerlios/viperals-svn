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
		if (!$_CLASS['auth']->acl_get('a_defaults'))
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

		$post_count_ary = $_CLASS['auth']->acl_getf('f_postcount');

		$forum_ary = array();
		foreach ($post_count_ary as $forum_id => $allowed)
		{
			if ($allowed['f_postcount'])
			{
				$forum_ary[] = $forum_id;
			}
		}
		
		if (!sizeof($forum_ary))
		{
			$_CLASS['core_db']->query('UPDATE ' . USERS_TABLE . ' SET user_posts = 0');
		}
		else
		{
			$sql = 'SELECT COUNT(post_id) AS num_posts, poster_id
				FROM ' . FORUMS_POSTS_TABLE . '
				WHERE poster_id <> ' . ANONYMOUS . '
					AND forum_id IN (' . implode(', ', $forum_ary) . ')
				GROUP BY poster_id';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_db']->query('UPDATE ' . USERS_TABLE . " SET user_posts = {$row['num_posts']} WHERE user_id = {$row['poster_id']}");
			}
			$_CLASS['core_db']->free_result($result);
		}

		add_log('admin', 'LOG_RESYNC_POSTCOUNTS');
		break;
}

// Get forum statistics
$total_posts = $config['num_posts'];
$total_topics = $config['num_topics'];
$total_users = $_CORE_CONFIG['user']['total_users'];
$total_files = $config['num_files'];

$start_date = $_CLASS['core_user']->format_date($config['board_start_date']);

$boarddays = (gmtime() - $config['board_start_date']) / 86400;

$posts_per_day = sprintf('%.2f', $total_posts / $boarddays);
$topics_per_day = sprintf('%.2f', $total_topics / $boarddays);
$users_per_day = sprintf('%.2f', $total_users / $boarddays);
$files_per_day = sprintf('%.2f', $total_files / $boarddays);

$upload_dir_size = ($config['upload_dir_size'] >= 1048576) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['MB'], ($config['upload_dir_size'] / 1048576)) : (($config['upload_dir_size'] >= 1024) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['KB'], ($config['upload_dir_size'] / 1024)) : sprintf('%.2f ' . $_CLASS['core_user']->lang['BYTES'], $config['upload_dir_size']));

$avatar_dir_size = 0;

if ($avatar_dir = @opendir($config['avatar_path']))
{
	while ($file = readdir($avatar_dir))
	{
		if ($file{0} != '.')
		{
			$avatar_dir_size += filesize($config['avatar_path'] . '/' . $file);
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

adm_page_header($_CLASS['core_user']->lang['ADMIN_INDEX']);

?>

<script language="Javascript" type="text/javascript">
<!--
	function marklist(status)
	{
		for (i = 0; i < document.inactive.length; i++)
		{
			document.inactive.elements[i].checked = status;
		}
	}
//-->
</script>

<h1><?php echo $_CLASS['core_user']->lang['WELCOME_PHPBB']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['ADMIN_INTRO']; ?></p>

<h1><?php echo $_CLASS['core_user']->lang['FORUM_STATS']; ?></h1>

<form name="statistics" method="post" action="<?php echo generate_link('forums', array('admin' => true)); ?>"><table class="tablebg" width="100%" cellpadding="4" cellspacing="1" border="0">
	<tr>
		<th width="25%" nowrap="nowrap" height="25"><?php echo $_CLASS['core_user']->lang['STATISTIC']; ?></th>
		<th width="25%"><?php echo $_CLASS['core_user']->lang['VALUE']; ?></th>
		<th width="25%" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['STATISTIC']; ?></th>
		<th width="25%"><?php echo $_CLASS['core_user']->lang['VALUE']; ?></th>
	</tr>
	<tr>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['NUMBER_POSTS']; ?>:</td>
		<td class="row2"><b><?php echo $total_posts; ?></b></td>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['POSTS_PER_DAY']; ?>:</td>
		<td class="row2"><b><?php echo $posts_per_day; ?></b></td>
	</tr>
	<tr>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['NUMBER_TOPICS']; ?>:</td>
		<td class="row2"><b><?php echo $total_topics; ?></b></td>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['TOPICS_PER_DAY']; ?>:</td>
		<td class="row2"><b><?php echo $topics_per_day; ?></b></td>
	</tr>
	<tr>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['NUMBER_USERS']; ?>:</td>
		<td class="row2"><b><?php echo $total_users; ?></b></td>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['USERS_PER_DAY']; ?>:</td>
		<td class="row2"><b><?php echo $users_per_day; ?></b></td>
	</tr>
	<tr>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['NUMBER_FILES']; ?>:</td>
		<td class="row2"><b><?php echo $total_files; ?></b></td>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['FILES_PER_DAY']; ?>:</td>
		<td class="row2"><b><?php echo $files_per_day; ?></b></td>
	</tr>
	<tr>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['BOARD_STARTED']; ?>:</td>
		<td class="row2"><b><?php echo $start_date; ?></b></td>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['AVATAR_DIR_SIZE']; ?>:</td>
		<td class="row2"><b><?php echo $avatar_dir_size; ?></b></td>
	</tr>
	<tr>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['DATABASE_SIZE']; ?>:</td>
		<td class="row2"><b><?php echo $dbsize; ?></b></td>
		<td class="row1" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['UPLOAD_DIR_SIZE']; ?>:</td>
		<td class="row2"><b><?php echo $upload_dir_size; ?></b></td>
	</tr>
	<tr>
		<td class="cat" colspan="4" align="right"><select name="action"><option value="online"><?php echo $_CLASS['core_user']->lang['RESET_ONLINE']; ?></option><option value="date"><?php echo $_CLASS['core_user']->lang['RESET_DATE']; ?></option><option value="stats"><?php echo $_CLASS['core_user']->lang['RESYNC_STATS']; ?></option><option value="user"><?php echo $_CLASS['core_user']->lang['RESYNC_POSTCOUNTS']; ?></option>
		</select> <input class="btnlite" type="submit" name="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" />&nbsp;</td>
	</tr>
</table></form>

<h1><?php echo $_CLASS['core_user']->lang['ADMIN_LOG']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['ADMIN_LOG_INDEX_EXPLAIN']; ?></p>

<table class="tablebg" width="100%" cellpadding="4" cellspacing="1" border="0">
	<tr>
		<th width="15%" height="25" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['USERNAME']; ?></th>
		<th width="15%"><?php echo $_CLASS['core_user']->lang['IP']; ?></th>
		<th width="20%"><?php echo $_CLASS['core_user']->lang['TIME']; ?></th>
		<th width="45%" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['ACTION']; ?></th>
	</tr>
<?php

	view_log('admin', $log_data, $log_count, 10);

	$row_class = 'row2';
	for($i = 0; $i < sizeof($log_data); $i++)
	{
		$row_class = ($row_class == 'row1') ? 'row2' : 'row1';

?>
	<tr>
		<td class="<?php echo $row_class; ?>"><?php echo $log_data[$i]['username']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center"><?php echo $log_data[$i]['ip']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center"><?php echo $_CLASS['core_user']->format_date($log_data[$i]['time']); ?></td>
		<td class="<?php echo $row_class; ?>"><?php echo $log_data[$i]['action']; ?></td>
	</tr>
<?php

	}
	?></table>
	<?php

	adm_page_footer();

?>