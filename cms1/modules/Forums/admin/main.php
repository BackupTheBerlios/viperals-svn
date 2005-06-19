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

// Do we have any admin permissions at all?
if (!$_CLASS['auth']->acl_get('a_'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Define some vars
$action = request_var('action', '');

$mark   = (isset($_REQUEST['mark'])) ? implode(', ', request_var('mark', array(0))) : '';

	if ($mark)
	{
		switch ($action)
		{
			case 'activate':
			case 'delete':
				if (!$_CLASS['auth']->acl_get('a_user'))
				{
					trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
				}

				$sql = 'SELECT username 
					FROM ' . USERS_TABLE . "
					WHERE user_id IN ($mark)";
				$result = $_CLASS['core_db']->sql_query($sql);
				
				$user_affected = array();
				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$user_affected[] = $row['username'];
				}
				$_CLASS['core_db']->sql_freeresult($result);

				if ($action == 'activate')
				{
					$sql = 'UPDATE ' . USERS_TABLE . ' SET user_type = ' . USER_NORMAL . " WHERE user_id IN ($mark)";
					$_CLASS['core_db']->sql_query($sql);
				}
				else if ($action == 'delete')
				{
					$sql = 'DELETE FROM ' . USER_GROUP_TABLE . " WHERE user_id IN ($mark)";
					$_CLASS['core_db']->sql_query($sql);
					$sql = 'DELETE FROM ' . USERS_TABLE . " WHERE user_id IN ($mark)";
					$_CLASS['core_db']->sql_query($sql);
				}

				if ($action != 'delete')
				{
					set_config('num_users', $config['num_users'] + $_CLASS['core_db']->sql_affectedrows(), true);
				}

				add_log('admin', 'LOG_INDEX_' . strtoupper($action), implode(', ', $user_affected));
				break;

			case 'remind':
				if (!$_CLASS['auth']->acl_get('a_user'))
				{
					trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
				}

				if (empty($config['email_enable']))
				{
					trigger_error($_CLASS['core_user']->lang['EMAIL_DISABLED']);
				}

				$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type, user_regdate, user_actkey
					FROM ' . USERS_TABLE . " 
					WHERE user_id IN ($mark)";
				$result = $_CLASS['core_db']->sql_query($sql);

				if ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					// Send the messages
					require_once($site_file_root.'includes/forums/functions_messenger.php');

					$messenger = new messenger();

					$sig = str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']);

					$usernames = array();
					do
					{
						$messenger->template('user_remind_inactive', $row['user_lang']);

						$messenger->replyto($config['board_email']);
						$messenger->to($row['user_email'], $row['username']);
						$messenger->im($row['user_jabber'], $row['username']);

						$messenger->assign_vars(array(
							'EMAIL_SIG'		=> $sig,
							'USERNAME'		=> $row['username'],
							'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
							'REGISTER_DATE'	=> $_CLASS['core_user']->format_date($row['user_regdate']), 
							
							'U_ACTIVATE'	=> generate_link('Control_Panel&mode=activate&u=' . $row['user_id'] . '&k=' . $row['user_actkey'], array('full' => true)))
						);

						$messenger->send($row['user_notify_type']);

						$usernames[] = $row['username'];
					}
					while ($row = $_CLASS['core_db']->sql_fetchrow($result));

					$messenger->save_queue();
					
					unset($email_list);

					add_log('admin', 'LOG_INDEX_REMIND', implode(', ', $usernames));
					unset($usernames);
				}
				$_CLASS['core_db']->sql_freeresult($result);
				break;
		}
	}

	switch ($action)
	{
		case 'online':
			if (!$_CLASS['auth']->acl_get('a_defaults'))
			{
				trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
			}

			set_config('record_online_users', 1, true);
			set_config('record_online_date', time(), true);
			add_log('admin', 'LOG_RESET_ONLINE');
			break;

		case 'stats':
			if (!$_CLASS['auth']->acl_get('a_defaults'))
			{
				trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
			}

			$sql = 'SELECT COUNT(post_id) AS stat 
				FROM ' . POSTS_TABLE . '
				WHERE post_approved = 1';
			$result = $_CLASS['core_db']->sql_query($sql);

			$row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
			set_config('num_posts', (int) $row['stat'], true);

			$sql = 'SELECT COUNT(topic_id) AS stat
				FROM ' . TOPICS_TABLE . '
				WHERE topic_approved = 1';
			$result = $_CLASS['core_db']->sql_query($sql);

			$row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
			set_config('num_topics', (int) $row['stat'], true);

			$sql = 'SELECT COUNT(user_id) AS stat
				FROM ' . USERS_TABLE . '
				WHERE user_type IN (' . USER_NORMAL . ',' . USER_FOUNDER . ')';
			$result = $_CLASS['core_db']->sql_query($sql);

			$row = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
			set_config('num_users', (int) $row['stat'], true);

			$sql = 'SELECT COUNT(attach_id) as stat
				FROM ' . ATTACHMENTS_TABLE;
			$result = $_CLASS['core_db']->sql_query($sql);

			set_config('num_files', (int) $_CLASS['core_db']->sql_fetchfield('stat', 0, $result), true);
			$_CLASS['core_db']->sql_freeresult($result);

			$sql = 'SELECT SUM(filesize) as stat
				FROM ' . ATTACHMENTS_TABLE;
			$result = $_CLASS['core_db']->sql_query($sql);

			set_config('upload_dir_size', (int) $_CLASS['core_db']->sql_fetchfield('stat', 0, $result), true);
			$_CLASS['core_db']->sql_freeresult($result);

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
				$_CLASS['core_db']->sql_query('UPDATE ' . USERS_TABLE . ' SET user_posts = 0');
			}
			else
			{
				$sql = 'SELECT COUNT(post_id) AS num_posts, poster_id
					FROM ' . POSTS_TABLE . '
					WHERE poster_id <> ' . ANONYMOUS . '
						AND forum_id IN (' . implode(', ', $forum_ary) . ')
					GROUP BY poster_id';
				$result = $_CLASS['core_db']->sql_query($sql);

				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					$_CLASS['core_db']->sql_query('UPDATE ' . USERS_TABLE . " SET user_posts = {$row['num_posts']} WHERE user_id = {$row['poster_id']}");
				}
				$_CLASS['core_db']->sql_freeresult($result);
			}

			add_log('admin', 'LOG_RESYNC_POSTCOUNTS');
			break;
			
		case 'date':
			if (!$_CLASS['auth']->acl_get('a_defaults'))
			{
				trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
			}

			set_config('board_startdate', time() - 1);
			add_log('admin', 'LOG_RESET_DATE');
			break;
	}

	// Get forum statistics
	$total_posts = $config['num_posts'];
	$total_topics = $config['num_topics'];
	$total_users = $config['num_users'];
	$total_files = $config['num_files'];

	$start_date = $_CLASS['core_user']->format_date($config['board_startdate']);

	$boarddays = (time() - $config['board_startdate']) / 86400;

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

	// DB size ... MySQL only
	// This code is heavily influenced by a similar routine
	// in phpMyAdmin 2.2.0
	if (preg_match('#^mysql#', SQL_LAYER))
	{
		$result = $_CLASS['core_db']->sql_query('SELECT VERSION() AS mysql_version');

		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$version = $row['mysql_version'];

			if (preg_match('#^(3\.23|4\.)#', $version))
			{
				require($site_file_root.'config.php');
				
				$db_name = (preg_match('#^(3\.23\.[6-9])|(3\.23\.[1-9][1-9])|(4\.)#', $version)) ? '`'.$site_db['database'].'`' : $site_db['database'];

				$sql = "SHOW TABLE STATUS
					FROM " . $db_name;
				$result = $_CLASS['core_db']->sql_query($sql);

				$dbsize = 0;
				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					if ((isset($row['Type']) && $row['Type'] != 'MRG_MyISAM') || (isset($row['Engine']) && $row['Engine'] == 'MyISAM'))
					{
						//if ($table_prefix != '')
						if ((isset($table_prefix)) && $table_prefix != '')
						{
							if (strstr($row['Name'], $table_prefix))
							{
								$dbsize += $row['Data_length'] + $row['Index_length'];
							}
						}
						else
						{
							$dbsize += $row['Data_length'] + $row['Index_length'];
						}
					}
				}
			}
			else
			{
				$dbsize = $_CLASS['core_user']->lang['NOT_AVAILABLE'];
			}
		}
		else
		{
			$dbsize = $_CLASS['core_user']->lang['NOT_AVAILABLE'];
		}
	}
	else if (preg_match('#^mssql#', SQL_LAYER))
	{
		$sql = 'SELECT ((SUM(size) * 8.0) * 1024.0) as dbsize
			FROM sysfiles';
		$result = $_CLASS['core_db']->sql_query($sql);

		$dbsize = ($row = $_CLASS['core_db']->sql_fetchrow($result)) ? intval($row['dbsize']) : $_CLASS['core_user']->lang['NOT_AVAILABLE'];
	}
	else
	{
		$dbsize = $_CLASS['core_user']->lang['NOT_AVAILABLE'];
	}

	if (is_int($dbsize))
	{
		$dbsize = ($dbsize >= 1048576) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['MB'], ($dbsize / 1048576)) : (($dbsize >= 1024) ? sprintf('%.2f ' . $_CLASS['core_user']->lang['KB'], ($dbsize / 1024)) : sprintf('%.2f ' . $_CLASS['core_user']->lang['BYTES'], $dbsize));
	}

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

	view_log('admin', $log_data, $log_count, 5);

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

	if ($_CLASS['auth']->acl_get('a_user'))
	{

?>

<h1><?php echo $_CLASS['core_user']->lang['INACTIVE_USERS']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['INACTIVE_USERS_EXPLAIN']; ?></p>

<form method="post" name="inactive" action="<?php echo "index.$phpEx$SID&amp;pane=right"; ?>"><table class="tablebg" width="100%" cellpadding="4" cellspacing="1" border="0">
	<tr>
		<th width="45%" height="25" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['USERNAME']; ?></th>
		<th width="45%"><?php echo $_CLASS['core_user']->lang['JOINED']; ?></th>
		<th width="5%" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['MARK']; ?></th>
	</tr>
<?php

		$sql = 'SELECT user_id, username, user_regdate
			FROM ' . USERS_TABLE . ' 
			WHERE user_type = ' . USER_INACTIVE . ' 
			ORDER BY user_regdate ASC';
		$result = $_CLASS['core_db']->sql_query($sql);

		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			do
			{
				$row_class = ($row_class == 'row1') ? 'row2' : 'row1';

?>
	<tr>
		<td class="<?php echo $row_class; ?>"><a href="<?php echo "admin_users.$phpEx$SID&amp;u=" . $row['user_id']; ?>"><?php echo $row['username']; ?></a></td>
		<td class="<?php echo $row_class; ?>"><?php echo $_CLASS['core_user']->format_date($row['user_regdate']); ?></td>
		<td class="<?php echo $row_class; ?>">&nbsp;<input type="checkbox" name="mark[]" value="<?php echo $row['user_id']; ?>" />&nbsp;</td>
	</tr>
<?php

			}
			while ($row = $_CLASS['core_db']->sql_fetchrow($result));

?>
	<tr>
		<td class="cat" colspan="3" height="28" align="right"><select name="action"><option value="activate"><?php echo $_CLASS['core_user']->lang['ACTIVATE']; ?></option><?php 
			
			if (!empty($config['email_enable']))
			{

?><option value="remind"><?php echo $_CLASS['core_user']->lang['REMIND']; ?></option><?php

			}

?><option value="delete"><?php echo $_CLASS['core_user']->lang['DELETE']; ?></option></select> <input class="btnlite" type="submit" name="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" />&nbsp;</td>
	</tr>
<?php

		}
		else
		{

?>
	<tr>
		<td class="row1" colspan="3" align="center"><?php echo $_CLASS['core_user']->lang['NO_INACTIVE_USERS']; ?></td>
	</tr>
<?php

		}

?>
</table>

<table width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<td align="right" valign="top" nowrap="nowrap"><b><span class="gensmall"><a href="javascript:marklist(true);" class="gensmall"><?php echo $_CLASS['core_user']->lang['MARK_ALL']; ?></a> :: <a href="javascript:marklist(false);" class="gensmall"><?php echo $_CLASS['core_user']->lang['UNMARK_ALL']; ?></a></span></b></td>
	</tr>
</table></form>

<?php

	}

	adm_page_footer();

?>