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
// $Id: functions_user.php,v 1.37 2004/05/02 13:05:38 acydburn Exp $
//
// FILENAME  : functions_user.php
// STARTED   : Sat Dec 16, 2000
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//
// User functions
//

// Obtain user_ids from usernames or vice versa. Returns false on
// success else the error string
function user_get_id_name(&$user_id_ary, &$username_ary)
{
	global $_CLASS;

	// Are both arrays already filled? Yep, return else
	// are neither array filled? 
	if ($user_id_ary && $username_ary)
	{
		return;
	}
	else if (!$user_id_ary && !$username_ary)
	{
		return 'NO_USERS';
	}

	$which_ary = ($user_id_ary) ? 'user_id_ary' : 'username_ary';

	if ($$which_ary  && !is_array($$which_ary))
	{
		$$which_ary = array($$which_ary);
	}

	$sql_in = ($which_ary == 'user_id_ary') ? array_map('intval', $$which_ary) : preg_replace('#^[\s]*(.*?)[\s]*$#e', "\"'\" . \$_CLASS['core_db']->escape('\\1') . \"'\"", $$which_ary);
	unset($$which_ary);

	// Grab the user id/username records
	$sql_where = ($which_ary == 'user_id_ary') ? 'user_id' : 'username';
	$sql = 'SELECT user_id, username 
		FROM ' . USERS_TABLE . " 
		WHERE $sql_where IN (" . implode(', ', $sql_in) . ')';
	$result = $_CLASS['core_db']->query($sql);

	if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
	{
		return 'NO_USERS';
	}

	$id_ary = $username_ary = array();
	do
	{
		$username_ary[$row['user_id']] = $row['username'];
		$user_id_ary[] = $row['user_id'];
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
	$_CLASS['core_db']->free_result($result);

	return false;
}

// Updates a username across all relevant tables/fields
function user_update_name($old_name, $new_name)
{
	global $config, $_CLASS;

	$update_ary = array(
		FORUMS_TABLE	=> array('forum_last_poster_name'), 
		MODERATOR_TABLE	=> array('username'), 
		POSTS_TABLE		=> array('post_username'), 
		TOPICS_TABLE	=> array('topic_first_poster_name', 'topic_last_poster_name'),
	);

	foreach ($update_ary as $table => $field_ary)
	{
		foreach ($field_ary as $field)
		{
			$sql = "UPDATE $table 
				SET $field = '$new_name' 
				WHERE $field = '$old_name'";
			$_CLASS['core_db']->query($sql);
		}
	}

	if ($config['newest_username'] == $old_name)
	{
		set_config('newest_username', $new_name);
	}
}

function user_delete($mode, $user_id)
{
	global $config, $_CLASS;
	
	$_CLASS['core_db']->sql_transaction();

	switch ($mode)
	{
		case 'retain':
			$sql = 'UPDATE ' . FORUMS_TABLE . '
				SET forum_last_poster_id = ' . ANONYMOUS . " 
				WHERE forum_last_poster_id = $user_id";
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET poster_id = ' . ANONYMOUS . " 
				WHERE poster_id = $user_id";
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_poster = ' . ANONYMOUS . "
				WHERE topic_poster = $user_id";
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_last_poster_id = ' . ANONYMOUS . "
				WHERE topic_last_poster_id = $user_id";
			$_CLASS['core_db']->query($sql);
			break;

		case 'remove':

			if (!function_exists('delete_posts'))
			{
				global $site_file_root;

				include($site_file_root.'includes/forums/functions_admin.php');
			}

			$sql = 'SELECT topic_id, COUNT(post_id) AS total_posts 
				FROM ' . POSTS_TABLE . " 
				WHERE poster_id = $user_id
				GROUP BY topic_id";
			$result = $_CLASS['core_db']->query($sql);

			$topic_id_ary = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$topic_id_ary[$row['topic_id']] = $row['total_posts'];
			}
			$_CLASS['core_db']->free_result($result);
			
			if (!count($topic_id_ary))
			{
				break;
			}

			$sql = 'SELECT topic_id, topic_replies, topic_replies_real 
				FROM ' . TOPICS_TABLE . ' 
				WHERE topic_id IN (' . implode(', ', array_keys($topic_id_ary)) . ')';
			$result = $_CLASS['core_db']->query($sql);

			$del_topic_ary = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if (max($row['topic_replies'], $row['topic_replies_real']) + 1 == $topic_id_ary[$row['topic_id']])
				{
					$del_topic_ary[] = $row['topic_id'];
				}
			}
			$_CLASS['core_db']->free_result($result);

			if (sizeof($del_topic_ary))
			{
				$sql = 'DELETE FROM ' . TOPICS_TABLE . ' 
					WHERE topic_id IN (' . implode(', ', $del_topic_ary) . ')';
				$_CLASS['core_db']->query($sql);
			}

			// Delete posts, attachments, etc.
			delete_posts('poster_id', $user_id);

			break;
	}

	$table_ary = array(USERS_TABLE, USER_GROUP_TABLE, TOPICS_WATCH_TABLE, FORUMS_WATCH_TABLE, ACL_USERS_TABLE, TOPICS_TRACK_TABLE, FORUMS_TRACK_TABLE);

	foreach ($table_ary as $table)
	{
		$sql = "DELETE FROM $table 
			WHERE user_id = $user_id";
		$_CLASS['core_db']->query($sql);
	}

	// Reset newest user info if appropriate
	if ($config['newest_user_id'] == $user_id)
	{
		$sql = 'SELECT user_id, username 
			FROM ' . USERS_TABLE . '
			WHERE user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')
			ORDER BY user_id DESC
			LIMIT 1';
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			set_config('newest_user_id', $row['user_id']);
			set_config('newest_username', $row['username']);
		}
		$_CLASS['core_db']->freeresult($result);
	}

	set_config('num_users', $config['num_users'] - 1, TRUE);

	$_CLASS['core_db']->sql_transaction('commit');

	return false;
}

// Flips user_type from active to inactive and vice versa, handles
// group membership updates
function user_active_flip($user_id, $user_type, $user_actkey = false, $username = false)
{
	global $_CLASS;

	$sql = 'SELECT group_id, group_name 
		FROM ' . GROUPS_TABLE . " 
		WHERE group_name IN ('REGISTERED', 'REGISTERED_COPPA', 'INACTIVE', 'INACTIVE_COPPA')";
	$result = $_CLASS['core_db']->query($sql);

	$group_id_ary = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$group_id_ary[$row['group_name']] = $row['group_id'];
	}
	$_CLASS['core_db']->free_result($result);

	$sql = 'SELECT group_id 
		FROM ' . USER_GROUP_TABLE . " 
		WHERE user_id = $user_id";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($group_name = array_search($row['group_id'], $group_id_ary))
		{
			break;
		}
	}
	$_CLASS['core_db']->free_result($result);

	$current_group = ($user_type == USER_NORMAL) ? 'REGISTERED' : 'INACTIVE';
	$switch_group = ($user_type == USER_NORMAL) ? 'INACTIVE' : 'REGISTERED';

	$new_group_id = $group_id_ary[str_replace($current_group, $switch_group, $group_name)];

	$sql = 'UPDATE ' . USER_GROUP_TABLE . " 
		SET group_id = $new_group_id 
		WHERE user_id = $user_id
			AND group_id = " . $group_id_ary[$group_name];
	$_CLASS['core_db']->query($sql);

	$sql_ary = array(
		'user_type'		=> ($user_type == USER_NORMAL) ? USER_INACTIVE : USER_NORMAL
	);

	if ($group_id == $group_id_ary[$group_name])
	{
		$sql_ary['group_id'] = $new_group_id;
	}

	if ($user_actkey !== false)
	{
		$sql_ary['user_actkey'] = $user_actkey;
	}

	$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . "
		WHERE user_id = $user_id";
	$_CLASS['core_db']->query($sql);

	$_CLASS['auth']->acl_clear_prefetch($user_id);

	if (!function_exists('add_log'))
	{
		global $site_file_root;

		include($site_file_root.'includes/forums/functions_admin.php');
	}

	if (!$username)
	{
		$sql = 'SELECT username
			FROM ' . USERS_TABLE . " 
			WHERE user_id = $user_id";
		$result = $_CLASS['core_db']->query($sql);
		
		extract($_CLASS['core_db']->fetch_row_assoc($result));
		$_CLASS['core_db']->free_result($result);
	}

	$log = ($user_type == USER_NORMAL) ? 'LOG_USER_INACTIVE' : 'LOG_USER_ACTIVE';
	add_log('admin', $log, $username);

	return false;
}

function user_ban($mode, $ban, $ban_len, $ban_len_other, $ban_exclude, $ban_reason)
{
	global $_CLASS;

	// Delete stale bans
	$sql = "DELETE FROM " . BANLIST_TABLE . "
		WHERE ban_end < " . time() . "
			AND ban_end <> 0";
	$_CLASS['core_db']->query($sql);

	$ban_list = (!is_array($ban)) ? array_unique(explode("\n", $ban)) : $ban;
	$ban_list_log = implode(', ', $ban_list);

	$current_time = time();

	if ($ban_len)
	{
		if ($ban_len != -1 || !$ban_len_other)
		{
			$ban_end = max($current_time, $current_time + ($ban_len) * 60);
		}
		else
		{
			$ban_other = explode('-', $ban_len_other);
			$ban_end = max($current_time, gmmktime(0, 0, 0, $ban_other[1], $ban_other[2], $ban_other[0]));
		}
	}
	else
	{
		$ban_end = 0;
	}

	$banlist = array();

	switch ($mode)
	{
		case 'user':
			$type = 'ban_userid';

			if (in_array('*', $ban_list))
			{
				$banlist[] = '*';
			}
			else
			{
				$sql = 'SELECT user_id
					FROM ' . USERS_TABLE . '
					WHERE username IN (' . implode(', ', array_diff(preg_replace('#^[\s]*(.*?)[\s]*$#', "'\\1'", $ban_list), array("''"))) . ')';
				$result = $_CLASS['core_db']->query($sql);

				if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					do
					{
						$banlist[] = $row['user_id'];
					}
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
				}
			}
			break;

		case 'ip':
			$type = 'ban_ip';

			foreach ($ban_list as $ban_item)
			{
				if (preg_match('#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})[ ]*\-[ ]*([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#', trim($ban_item), $ip_range_explode))
				{
					// Don't ask about all this, just don't ask ... !
					$ip_1_counter = $ip_range_explode[1];
					$ip_1_end = $ip_range_explode[5];

					while ($ip_1_counter <= $ip_1_end)
					{
						$ip_2_counter = ($ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[2] : 0;
						$ip_2_end = ($ip_1_counter < $ip_1_end) ? 254 : $ip_range_explode[6];

						if($ip_2_counter == 0 && $ip_2_end == 254)
						{
							$ip_2_counter = 256;
							$ip_2_fragment = 256;

							$banlist[] = "'$ip_1_counter.*'";
						}

						while ($ip_2_counter <= $ip_2_end)
						{
							$ip_3_counter = ($ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[3] : 0;
							$ip_3_end = ($ip_2_counter < $ip_2_end || $ip_1_counter < $ip_1_end) ? 254 : $ip_range_explode[7];

							if ($ip_3_counter == 0 && $ip_3_end == 254)
							{
								$ip_3_counter = 256;
								$ip_3_fragment = 256;

								$banlist[] = "'$ip_1_counter.$ip_2_counter.*'";
							}

							while ($ip_3_counter <= $ip_3_end)
							{
								$ip_4_counter = ($ip_3_counter == $ip_range_explode[3] && $ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[4] : 0;
								$ip_4_end = ($ip_3_counter < $ip_3_end || $ip_2_counter < $ip_2_end) ? 254 : $ip_range_explode[8];

								if ($ip_4_counter == 0 && $ip_4_end == 254)
								{
									$ip_4_counter = 256;
									$ip_4_fragment = 256;

									$banlist[] = "'$ip_1_counter.$ip_2_counter.$ip_3_counter.*'";
								}

								while ($ip_4_counter <= $ip_4_end)
								{
									$banlist[] = "'$ip_1_counter.$ip_2_counter.$ip_3_counter.$ip_4_counter'";
									$ip_4_counter++;
								}
								$ip_3_counter++;
							}
							$ip_2_counter++;
						}
						$ip_1_counter++;
					}
				}
				else if (preg_match('#^([\w\-_]\.?){2,}$#is', trim($ban_item)))
				{
					$ip_ary = gethostbynamel(trim($ban_item));

					foreach ($ip_ary as $ip)
					{
						if (!empty($ip))
						{
							$banlist[] = "'" . $ip . "'";
						}
					}
				}
				else if (preg_match('#^([0-9]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})$#', trim($ban_item)) || preg_match('#^[a-f0-9:]+\*?$#i', trim($ban_item)))
				{
					$banlist[] = "'" . trim($ban_item) . "'";
				}
				else if (preg_match('#^\*$#', trim($ban_item)))
				{
					$banlist[] = "'*'";
				}
			}
			break;

		case 'email':
			$type = 'ban_email';

			foreach ($ban_list as $ban_item)
			{
				if (preg_match('#^.*?@*|(([a-z0-9\-]+\.)+([a-z]{2,3}))$#i', trim($ban_item)))
				{
					$banlist[] = "'" . trim($ban_item) . "'";
				}
			}
			break;
	}

	$sql = "SELECT $type
		FROM " . BANLIST_TABLE . "
		WHERE $type <> '' 
			AND ban_exclude = $ban_exclude";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$banlist_tmp = array();
		do
		{
			switch ($mode)
			{
				case 'user':
					$banlist_tmp[] = $row['ban_userid'];
					break;

				case 'ip':
					$banlist_tmp[] = "'" . $row['ban_ip'] . "'";
					break;

				case 'email':
					$banlist_tmp[] = "'" . $row['ban_email'] . "'";
					break;
			}
		}
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

		$banlist = array_unique(array_diff($banlist, $banlist_tmp));
		unset($banlist_tmp);
	}

	if (sizeof($banlist))
	{
		$sql = '';
		foreach ($banlist as $ban_entry)
		{
			switch (SQL_LAYER)
			{
				case 'mysql':
					$sql .= (($sql != '') ? ', ' : '') . "($ban_entry, $current_time, $ban_end, $ban_exclude, '$ban_reason')";
					break;
					
				case 'mysql4':
				case 'mysqli':
				case 'mssql':
				case 'sqlite':
					$sql .= (($sql != '') ? ' UNION ALL ' : '') . " SELECT $ban_entry, $current_time, $ban_end, $ban_exclude, '$ban_reason'";
					break;

				default:
					$sql = 'INSERT INTO ' . BANLIST_TABLE . " ($type, ban_start, ban_end, ban_exclude, ban_reason)
						VALUES ($ban_entry, $current_time, $ban_end, $ban_exclude, '$ban_reason')";
					$_CLASS['core_db']->query($sql);
			}
		}

		if ($sql)
		{
			$sql = 'INSERT INTO ' . BANLIST_TABLE . " ($type, ban_start, ban_end, ban_exclude, ban_reason)
				VALUES $sql";
			$_CLASS['core_db']->query($sql);
		}

		if (!$ban_exclude)
		{
			$sql = '';
			switch ($mode)
			{
				case 'user':
					$sql = 'WHERE session_user_id IN (' . implode(', ', $banlist) . ')';
					break;

				case 'ip':
					$sql = 'WHERE session_ip IN (' . implode(', ', $banlist) . ')';
					break;

				case 'email':
					$sql = 'SELECT user_id
						FROM ' . USERS_TABLE . '
						WHERE user_email IN (' . implode(', ', $banlist) . ')';
					$result = $_CLASS['core_db']->query($sql);

					$sql_in = array();
					if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						do
						{
							$sql_in[] = $row['user_id'];
						}
						while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

						$sql = 'WHERE session_user_id IN (' . str_replace('*', '%', implode(', ', $sql_in)) . ")";
					}
					break;
			}

			if ($sql)
			{
				$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
					$sql";
				$_CLASS['core_db']->query($sql);
			}
		}

		if (!function_exists('add_log'))
		{
			global $site_file_root;

			include($site_file_root.'includes/forums/functions_admin.php');
		}

		// Update log
		$log_entry = ($ban_exclude) ? 'LOG_BAN_EXCLUDE_' : 'LOG_BAN_';
		add_log('admin', $log_entry . strtoupper($mode), $ban_reason, $ban_list_log);
	}

	return false;
}

function user_unban($mode, $ban)
{
	global $_CLASS;

	// Delete stale bans
	$sql = "DELETE FROM " . BANLIST_TABLE . "
		WHERE ban_end < " . time() . "
			AND ban_end <> 0";
	$_CLASS['core_db']->query($sql);

	$unban_sql = implode(', ', $ban);

	if ($unban_sql)
	{
		$l_unban_list = '';
		// Grab details of bans for logging information later
		switch ($mode)
		{
			case 'user':
				$sql = 'SELECT u.username AS unban_info
					FROM ' . USERS_TABLE . ' u, ' . BANLIST_TABLE . " b 
					WHERE b.ban_id IN ($unban_sql) 
						AND u.user_id = b.ban_userid";
				break;

			case 'email':
				$sql = 'SELECT ban_email AS unban_info 
					FROM ' . BANLIST_TABLE . "
					WHERE ban_id IN ($unban_sql)";
				break;

			case 'ip':
				$sql = 'SELECT ban_ip AS unban_info 
					FROM ' . BANLIST_TABLE . "
					WHERE ban_id IN ($unban_sql)";
				break;
		}
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$l_unban_list .= (($l_unban_list != '') ? ', ' : '') . $row['unban_info'];
		}

		$sql = 'DELETE FROM ' . BANLIST_TABLE . "
			WHERE ban_id IN ($unban_sql)";
		$_CLASS['core_db']->query($sql);

		if (!function_exists('add_log'))
		{
			global $site_file_root;

			include($site_file_root.'includes/forums/functions_admin.php');
		}

		add_log('admin', 'LOG_UNBAN_' . strtoupper($mode), $l_unban_list);
	}

	return false;

}

// Whois facility
function user_ipwhois($ip)
{
	$ipwhois = '';

	$match = array(
		'#RIPE\.NET#is'				=> 'whois.ripe.net',
		'#whois\.apnic\.net#is'		=> 'whois.apnic.net',
		'#nic\.ad\.jp#is'			=> 'whois.nic.ad.jp',
		'#whois\.registro\.br#is'	=> 'whois.registro.br'
	);

	if (($fsk = @fsockopen('whois.arin.net', 43)))
	{
		fputs($fsk, "$ip\n");
		while (!feof($fsk))
		{
			$ipwhois .= fgets($fsk, 1024);
		}
		@fclose($fsk);
	}

	foreach (array_keys($match) as $server)
	{
		if (preg_match($server, $ipwhois))
		{
			$ipwhois = '';
			if (($fsk = @fsockopen($match[$server], 43)))
			{
				fputs($fsk, "$ip\n");
				while (!feof($fsk))
				{
					$ipwhois .= fgets($fsk, 1024);
				}
				@fclose($fsk);
			}
			break;
		}
	}

	return $ipwhois;
}
//
// Data validation ... used primarily but not exclusively by
// ucp modules
//

// "Master" function for validating a range of data types
function validate_data($data, $val_ary)
{
	$error = array();

	foreach ($val_ary as $var => $val_seq)
	{
		if (!is_array($val_seq[0]))
		{
			$val_seq = array($val_seq);
		}

		foreach ($val_seq as $validate)
		{
			$function = array_shift($validate);
			array_unshift($validate, $data[$var]);

			if ($result = call_user_func_array('validate_' . $function, $validate))
			{
				$error[] = $result . '_' . strtoupper($var);
			}
		}
	}

	return $error;
}

function validate_string($string, $optional = false, $min = 0, $max = 0)
{
	if (empty($string) && $optional)
	{
		return false;
	}

	if ($min && strlen($string) < $min)
	{
		return 'TOO_SHORT';
	}
	else if ($max && strlen($string) > $max)
	{
		return 'TOO_LONG';
	}

	return false;
}

function validate_num($num, $optional = false, $min = 0, $max = 1E99)
{
	if (empty($num) && $optional)
	{
		return false;
	}

	if ($num < $min)
	{
		return 'TOO_SMALL';
	}
	else if ($num > $max) 
	{
		return 'TOO_LARGE';
	}

	return false;
}

function validate_match($string, $optional = false, $match)
{
	if (empty($string) && $optional)
	{
		return false;
	}

	if (!preg_match($match, $string))
	{
		return 'WRONG_DATA';
	}
	return false;
}

// Check to see if the username has been taken, or if it is disallowed.
// Also checks if it includes the " character, which we don't allow in usernames.
// Used for registering, changing names, and posting anonymously with a username
function validate_username($username)
{
	global $_CORE_CONFIG, $_CLASS;

	if (strtolower($_CLASS['core_user']->data['username']) == strtolower($username))
	{
		return false;
	}

	if (!preg_match('#^' . str_replace('\\\\', '\\', $_CORE_CONFIG['user']['allow_name_chars']) . '$#i', $username))
	{
		return 'INVALID_CHARS';
	}

	$sql = 'SELECT username
		FROM ' . USERS_TABLE . "
		WHERE LOWER(username) = '" . strtolower($_CLASS['core_db']->escape($username)) . "'";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		return 'USERNAME_TAKEN';
	}
	$_CLASS['core_db']->free_result($result);

	$sql = 'SELECT group_name
		FROM ' . GROUPS_TABLE . "
		WHERE LOWER(group_name) = '" . strtolower($_CLASS['core_db']->escape($username)) . "'";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		return 'USERNAME_TAKEN';
	}
	$_CLASS['core_db']->free_result($result);

	$sql = 'SELECT disallow_username
		FROM ' . DISALLOW_TABLE;
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (preg_match('#' . str_replace('*', '.*?', preg_quote($row['disallow_username'], '#')) . '#i', $username))
		{
			return 'USERNAME_DISALLOWED';
		}
	}
	$_CLASS['core_db']->free_result($result);

	$sql = 'SELECT word
		FROM  ' . WORDS_TABLE;
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (preg_match('#(' . str_replace('\*', '.*?', preg_quote($row['word'], '#')) . ')#i', $username))
		{
			return 'USERNAME_DISALLOWED';
		}
	}
	$_CLASS['core_db']->free_result($result);

	return false;
}

// TODO?
// Ability to limit types of email address ... not by banning, seperate table
// capability to require (or deny) use of certain addresses when user is
// registering from certain IP's/hosts

// Check to see if email address is banned or already present in the DB
function validate_email($email)
{
	global $_CORE_CONFIG, $_CLASS;

	if (strtolower($_CLASS['core_user']->data['user_email']) == strtolower($email))
	{
		return false;
	}

	if (!preg_match('#^[a-z0-9\.\-_\+]+?@(.*?\.)*?[a-z0-9\-_]+?\.[a-z]{2,4}$#i', $email))
	{
		return 'EMAIL_INVALID';
	}

	$sql = 'SELECT ban_email
		FROM ' . BANLIST_TABLE;
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (preg_match('#^' . str_replace('*', '.*?', $row['ban_email']) . '$#i', $email))
		{
			return 'EMAIL_BANNED';
		}
	}
	$_CLASS['core_db']->free_result($result);

	if (!$_CORE_CONFIG['user']['allow_emailreuse'])
	{
		$sql = 'SELECT user_email_hash
			FROM ' . USERS_TABLE . "
			WHERE user_email_hash = " . crc32(strtolower($email)) . strlen($email);
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			return 'EMAIL_TAKEN';
		}
		$_CLASS['core_db']->free_result($result);
	}

	return false;
}

//
// Avatar functions
//

function avatar_delete($id)
{
	global $config, $_CORE_CONFIG;

	if (file_exists($config['avatar_path'] . '/' . $id))
	{
		@unlink($config['avatar_path'] . '/' . $id);
	}

	return false;
 }

function avatar_remote($data, &$error)
{
	global $config, $_CLASS;

	if (!preg_match('#^(http|https|ftp)://#i', $data['remotelink']))
	{
		$data['remotelink'] = 'http://' . $data['remotelink'];
	}

	if (!preg_match('#^(http|https|ftp)://(.*?\.)*?[a-z0-9\-]+?\.[a-z]{2,4}:?([0-9]*?).*?\.(gif|jpg|jpeg|png)$#i', $data['remotelink']))
	{
		$error[] = $_CLASS['core_user']->lang['AVATAR_URL_INVALID'];
		return false;
	}

	if ((!($data['width'] || $data['height']) || $data['remotelink'] != $_CLASS['core_user']->data['user_avatar']) && ($config['avatar_max_width'] || $config['avatar_max_height']))
	{
		list($width, $height) = @getimagesize($data['remotelink']);

		if (!$width || !$height)
		{
			$error[] = $_CLASS['core_user']->lang['AVATAR_NO_SIZE'];
			return false;
		}
		else if ($width > $config['avatar_max_width'] || $height > $config['avatar_max_height'])
		{
			$error[] = sprintf($_CLASS['core_user']->lang['AVATAR_WRONG_SIZE'], $config['avatar_max_width'], $config['avatar_max_height']);
			return false;
		}
	}
	else if ($data['width'] > $config['avatar_max_width'] || $data['height'] > $config['avatar_max_height'])
	{
		$error[] = sprintf($_CLASS['core_user']->lang['AVATAR_WRONG_SIZE'], $config['avatar_max_width'], $config['avatar_max_height']);
		return false;
	}

	return array(AVATAR_REMOTE, $data['remotelink'], $width, $height);
}

function avatar_upload($data, &$error)
{
	global $site_file_root, $config, $_CLASS;

	// Init upload class
	include_once($site_file_root.'includes/forums/functions_upload.php');
	
	$upload = new fileupload('AVATAR_', array('jpg', 'jpeg', 'gif', 'png'), $config['avatar_filesize'], $config['avatar_min_width'], $config['avatar_min_height'], $config['avatar_max_width'], $config['avatar_max_height']);
							
	if (!empty($_FILES['uploadfile']['name']))
	{
		$file = $upload->form_upload('uploadfile');
	}
	else
	{
		$file = $upload->remote_upload($data['uploadurl']);
	}

	$file->clean_filename('real', $_CLASS['core_user']->data['user_id'] . '_');
	$file->move_file($config['avatar_path']);

	if (sizeof($file->error))
	{
		$file->remove();
		$error = array_merge($error, $file->error);
	}
	
	return array(AVATAR_UPLOAD, $file->get('realname'), $file->get('width'), $file->get('height'));
}

//
// Usergroup functions
//

// Add or edit a group. If we're editing a group we only update user
// parameters such as rank, etc. if they are changed
function group_create($group_id, $type, $name, $desc)
{
	global $config, $_CLASS, $file_upload;

	$error = array();

	// Check data
	if (!strlen($name) || strlen($name) > 40)
	{
		$error[] = (!strlen($name)) ? $_CLASS['core_user']->lang['GROUP_ERR_USERNAME'] : $_CLASS['core_user']->lang['GROUP_ERR_USER_LONG'];
	}

	if (strlen($desc) > 255)
	{
		$error[] = $_CLASS['core_user']->lang['GROUP_ERR_DESC_LONG'];
	}

	if (!in_array($type, array(GROUP_OPEN, GROUP_CLOSED, GROUP_HIDDEN, GROUP_SPECIAL, GROUP_FREE)))
	{
		$error[] = $_CLASS['core_user']->lang['GROUP_ERR_TYPE'];
	}

	if (!sizeof($error))
	{
		$sql_ary = array(
			'group_name'			=> (string) $name,
			'group_description'		=> (string) $desc,
			'group_type'			=> (int) $type,
		);

		$attribute_ary = array('group_colour' => 'string', 'group_rank' => 'int', 'group_avatar' => 'string', 'group_avatar_type' => 'int', 'group_avatar_width' => 'int', 'group_avatar_height' => 'int', 'group_receive_pm' => 'int', 'group_message_limit' => 'int');

		$i = 4;
		foreach ($attribute_ary as $attribute => $type)
		{
			if (func_num_args() > $i && ($value = func_get_arg($i)) !== false)
			{
				settype($value, $type);

				$sql_ary[$attribute] = $$attribute = $value;
			}
			$i++;
		}
		
		$group_only_ary = array('group_receive_pm' => 'int', 'group_message_limit' => 'int');

		foreach ($group_only_ary as $attribute => $type)
		{
			if (func_num_args() > $i && ($value = func_get_arg($i)) !== false)
			{
				settype($value, $type);

				$sql_ary[$attribute] = $value;
			}
			$i++;
		}
		
		$sql = ($group_id) ? 'UPDATE ' . GROUPS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . "	WHERE group_id = $group_id" : 'INSERT INTO ' . GROUPS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary);
		$_CLASS['core_db']->query($sql);

		$sql_ary = array();
		foreach ($attribute_ary as $attribute => $type)
		{
			if (isset($$attribute))
			{
				$sql_ary[str_replace('group', 'user', $attribute)] = $$attribute;
			}
		}

		if (sizeof($sql_ary))
		{
			$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . "
				WHERE group_id = $group_id";
			$_CLASS['core_db']->query($sql);
		}

		if (!function_exists('add_log'))
		{
			global $site_file_root;

			include($site_file_root.'includes/forums/functions_admin.php');
		}

		$log = ($group_id) ? 'LOG_GROUP_UPDATED' : 'LOG_GROUP_CREATED';
		add_log('admin', $log, $name);
	}

	return (sizeof($error)) ? $error : false;
}

function group_delete($group_id, $group_name = false)
{
	global $_CLASS;

	if (!$group_name)
	{
		$sql = 'SELECT group_name
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $group_id";
		$result = $_CLASS['core_db']->query($sql);

		if (!extract($_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error("Could not obtain name of group $group_id", E_USER_ERROR);
		}
		$_CLASS['core_db']->free_result($result);
	}

	$start = 0;

	do
	{
		$user_id_ary = $username_ary = array();

		// Batch query for group members, call group_user_del
		$sql = 'SELECT u.user_id, u.username
			FROM ' . USER_GROUP_TABLE . ' ug, ' . USERS_TABLE . " u
			WHERE ug.group_id = $group_id
				AND u.user_id = ug.user_id 
			LIMIT $start, 200";
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			do
			{
				$user_id_ary[] = $row['user_id'];
				$username_ary[] = $row['username'];

				$start++;
			}
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

			group_user_del($group_id, $user_id_ary, $username_ary, $group_name);
		}
		else
		{
			$start = 0;
		}
		$_CLASS['core_db']->free_result($result);
	}
	while ($start);
	
	// Delete group
	$sql = 'DELETE FROM ' . GROUPS_TABLE . " 
		WHERE group_id = $group_id";
	$_CLASS['core_db']->query($sql);

	if (!function_exists('add_log'))
	{
		global $site_file_root;

		include($site_file_root.'includes/forums/functions_admin.php');
	}

	add_log('admin', 'LOG_GROUP_DELETE', $group_name);

	return false;
}

function group_user_add($group_id, $user_id_ary = false, $username_ary = false, $group_name = false, $default = false, $leader = 0)
{
	global $_CLASS;

	// We need both username and user_id info
	user_get_id_name($user_id_ary, $username_ary);

	// Remove users who are already members of this group
	$sql = 'SELECT user_id, group_leader  
		FROM ' . USER_GROUP_TABLE . '   
		WHERE user_id IN (' . implode(', ', $user_id_ary) . ") 
			AND group_id = $group_id";
	$result = $_CLASS['core_db']->query($sql);

	$add_id_ary = $update_id_ary = array();
	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		do
		{
			$add_id_ary[] = $row['user_id'];

			if ($leader && !$row['group_leader'])
			{
				$update_id_ary[] = $row['user_id'];
			}
		}
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
	}
	$_CLASS['core_db']->free_result($result);

	// Do all the users exist in this group?
	$add_id_ary = array_diff($user_id_ary, $add_id_ary);
	unset($id_ary);

	// If we have no users 
	if (!sizeof($add_id_ary) && !sizeof($update_id_ary))
	{
		return 'GROUP_USERS_EXIST';
	}

	if (sizeof($add_id_ary))
	{
		// Insert the new users 
		switch (SQL_LAYER)
		{
			case 'mysql':
			case 'mysql4':
			case 'mysqli':
			case 'mssql':
			case 'sqlite':

				$sql = 'INSERT INTO ' . USER_GROUP_TABLE . " (user_id, group_id, group_leader) 
					VALUES " . implode(', ', preg_replace('#^([0-9]+)$#', "(\\1, $group_id, $leader)",  $add_id_ary));
				$_CLASS['core_db']->query($sql);
				break;
			
			default:
				foreach ($add_id_ary as $user_id)
				{
					$sql = 'INSERT INTO ' . USER_GROUP_TABLE . " (user_id, group_id, group_leader)
						VALUES ($user_id, $group_id, $leader)";
					$_CLASS['core_db']->query($sql);
				}
				break;
		}
	}

	$usernames = array();
	if (sizeof($update_id_ary))
	{
		$sql = 'UPDATE ' . USER_GROUP_TABLE . ' 
			SET group_leader = 1 
			WHERE user_id IN (' . implode(', ', $update_id_ary) . ")
				AND group_id = $group_id";
		$_CLASS['core_db']->query($sql);

		foreach ($update_id_ary as $id)
		{
			$usernames[] = $username_ary[$id];
		}
	}
	else
	{
		foreach ($add_id_ary as $id)
		{
			$usernames[] = $username_ary[$id];
		}
	}

	if ($default)
	{
		$attribute_ary = array('group_colour' => 'string', 'group_rank' => 'int', 'group_avatar' => 'string', 'group_avatar_type' => 'int', 'group_avatar_width' => 'int', 'group_avatar_height' => 'int');
	
		// Were group attributes passed to the function? If not we need to obtain them
		if (func_num_args() > 6)
		{
			$i = 6;
			foreach ($attribute_ary as $attribute => $type)
			{
				if (func_num_args() > $i && ($value = func_get_arg($i)) !== false)
				{
					settype($value, $type);

					$sql_ary[$attribute] = $$attribute = $value;
				}
				$i++;
			}
		}
		else
		{
			$sql = 'SELECT group_colour, group_rank, group_avatar, group_avatar_type, group_avatar_width, group_avatar_height  
				FROM ' . GROUPS_TABLE . " 
				WHERE group_id = $group_id";
			$result = $_CLASS['core_db']->query($sql);

			if (!extract($_CLASS['core_db']->fetch_row_assoc($result)))
			{
				trigger_error("Could not obtain group attributes for group_id $group_id", E_USER_ERROR);
			}

			if (!$group_avatar_width)
			{
				unset($group_avatar_width);
			}
			if (!$group_avatar_height)
			{
				unset($group_avatar_height);
			}
		}

		$sql_set = '';
		foreach ($attribute_ary as $attribute => $type)
		{
			if (isset($$attribute))
			{
				$field = str_replace('group_', 'user_', $attribute);

				switch ($type)
				{
					case 'int':
						$sql_set .= ", $field = " . (int) $$attribute;
						break;
					case 'double':
						$sql_set .= ", $field = " . (double) $$attribute;
						break;
					case 'string':
						$sql_set .= ", $field = '" . (string) $_CLASS['core_db']->escape($$attribute) . "'";
						break;
				}
			}
		}

		$sql = 'UPDATE ' . USERS_TABLE . "
			SET group_id = $group_id$sql_set  
			WHERE user_id IN (" . implode(', ', $user_id_ary) . ')';
		$_CLASS['core_db']->query($sql);
	}

	// Clear permissions cache of relevant users
	$_CLASS['auth']->acl_clear_prefetch($user_id_ary);

	if (!$group_name)
	{
		$sql = 'SELECT group_name
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $group_id";
		$result = $_CLASS['core_db']->query($sql);

		if (!extract($_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error("Could not obtain name of group $group_id", E_USER_ERROR);
		}
	}

	if (!function_exists('add_log'))
	{
		global $site_file_root;

		include($site_file_root.'includes/forums/functions_admin.php');
	}

	$log = ($leader) ? 'LOG_MODS_ADDED' : 'LOG_USERS_ADDED';

	add_log('admin', $log, $group_name, implode(', ', $username_ary));

	unset($username_ary);
	unset($user_id_ary);

	return false;
}

// Remove a user/s from a given group. When we remove users we update their
// default group_id. We do this by examining which "special" groups they belong
// to. The selection is made based on a reasonable priority system
function group_user_del($group_id, $user_id_ary = false, $username_ary = false, $group_name = false)
{
	global $_CLASS;

	$group_order = array('ADMINISTRATORS', 'SUPER_MODERATORS', 'REGISTERED_COPPA', 'REGISTERED', 'BOTS', 'GUESTS');

	$attribute_ary = array('group_colour' => 'string', 'group_rank' => 'int', 'group_avatar' => 'string', 'group_avatar_type' => 'int', 'group_avatar_width' => 'int', 'group_avatar_height' => 'int');

	// We need both username and user_id info
	user_get_id_name($user_id_ary, $username_ary);

	$sql = 'SELECT * 
		FROM ' . GROUPS_TABLE . ' 
		WHERE group_name IN (' . implode(', ', preg_replace('#^(.*)$#', "'\\1'", $group_order)) . ')';
	$result = $_CLASS['core_db']->query($sql);

	$group_order_id = $special_group_data = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$group_order_id[$row['group_name']] = $row['group_id'];

		$special_group_data[$row['group_id']]['group_colour']			= $row['group_colour'];
		$special_group_data[$row['group_id']]['group_rank']				= $row['group_rank'];
		$special_group_data[$row['group_id']]['group_avatar']			= $row['group_avatar'];
		$special_group_data[$row['group_id']]['group_avatar_type']		= $row['group_avatar_type'];
		$special_group_data[$row['group_id']]['group_avatar_width']		= $row['group_avatar_width'];
		$special_group_data[$row['group_id']]['group_avatar_height']	= $row['group_avatar_height'];
	}
	$_CLASS['core_db']->free_result($result);

	// What special group memberships exist for these users?
	$sql = 'SELECT g.group_id, g.group_name, ug.user_id 
		FROM ' . USER_GROUP_TABLE . ' ug, ' . GROUPS_TABLE . ' g 
		WHERE ug.user_id IN (' . implode(', ', $user_id_ary) . ") 
			AND g.group_id = ug.group_id
			AND g.group_id <> $group_id 
			AND g.group_type = " . GROUP_SPECIAL . '
		ORDER BY ug.user_id, g.group_id';
	$result = $_CLASS['core_db']->query($sql);

	$temp_ary = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (!isset($temp_ary[$row['user_id']]) || array_search($row['group_name'], $group_order) < $temp_ary[$row['user_id']])
		{
			$temp_ary[$row['user_id']] = $row['group_id'];
		}
	}
	$_CLASS['core_db']->free_result($result);

	$sql_where_ary = array();
	foreach ($temp_ary as $uid => $gid)
	{
		$sql_where_ary[$gid][] = $uid;
	}
	unset($temp_ary);

	foreach ($special_group_data as $gid => $default_data_ary)
	{
		if ($sql_where = implode(', ', $sql_where_ary[$gid]))
		{
			$sql_set = '';
			foreach ($special_group_data[$gid] as $attribute => $value)
			{
				$field = str_replace('group_', 'user_', $attribute);

				switch ($attribute_ary[$attribute])
				{
					case 'int':
						$sql_set .= ", $field = " . (int) $value;
						break;
					case 'double':
						$sql_set .= ", $field = " . (double) $value;
						break;
					case 'string':
						$sql_set .= ", $field = '" . $_CLASS['core_db']->escape($value) . "'";
						break;
				}
			}

			// Set new default
			$sql = 'UPDATE ' . USERS_TABLE . " 
				SET group_id = $gid$sql_set 
				WHERE user_id IN (" . implode(', ', $sql_where_ary[$gid]) . ')';
			$_CLASS['core_db']->query($sql);
		}
	}
	unset($special_group_data);

	$sql = 'DELETE FROM ' . USER_GROUP_TABLE . " 
		WHERE group_id = $group_id
			AND user_id IN (" . implode(', ', $user_id_ary) . ')';
	$_CLASS['core_db']->query($sql);
	unset($default_ary);

	// Clear permissions cache of relevant users
	$_CLASS['auth']->acl_clear_prefetch($user_id_ary);

	if (!$group_name)
	{
		$sql = 'SELECT group_name
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $group_id";
		$result = $_CLASS['core_db']->query($sql);

		if (!extract($_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error("Could not obtain name of group $group_id", E_USER_ERROR);
		}
	}

	if (!function_exists('add_log'))
	{
		global $site_file_root;

		include($site_file_root.'includes/forums/functions_admin.php');
	}

	$log = 'LOG_GROUP_REMOVE';

	add_log('admin', $log, $group_name, implode(', ', $username_ary));

	unset($username_ary);
	unset($user_id_ary);

	return false;
}

// This is used to promote (to leader), demote or set as default a member/s
function group_user_attributes($action, $group_id, $user_id_ary = false, $username_ary = false, $group_name = false)
{
	global $_CLASS;

	// We need both username and user_id info
	user_get_id_name($user_id_ary, $username_ary);

	switch ($action)
	{
		case 'demote':
		case 'promote':
			$sql = 'UPDATE ' . USER_GROUP_TABLE . '
				SET group_leader = ' . (($action == 'promote') ? 1 : 0) . "  
				WHERE group_id = $group_id
					AND user_id IN (" . implode(', ', $user_id_ary) . ')';
			$_CLASS['core_db']->query($sql);

			$log = ($action == 'promote') ? 'LOG_GROUP_PROMOTED' : 'LOG_GROUP_DEMOTED';
			break;

		case 'approve':
			$sql = 'UPDATE ' . USER_GROUP_TABLE . " 
				SET user_pending = 0 
				WHERE group_id = $group_id 
					AND user_id IN (" . implode(', ', $user_id_ary) . ')';
			$_CLASS['core_db']->query($sql);

			$log = 'LOG_GROUP_APPROVE';
			break;

		case 'default':
			$attribute_ary = array('group_colour' => 'string', 'group_rank' => 'int', 'group_avatar' => 'string', 'group_avatar_type' => 'int', 'group_avatar_width' => 'int', 'group_avatar_height' => 'int');

			// Were group attributes passed to the function? If not we need
			// to obtain them
			if (func_num_args() > 5)
			{
				$i = 5;
				foreach ($attribute_ary as $attribute => $type)
				{
					if (func_num_args() > $i && ($value = func_get_arg($i)) !== false)
					{
						settype($value, $type);

						$sql_ary[$attribute] = $$attribute = $value;
					}
					$i++;
				}
			}
			else
			{
				$sql = 'SELECT group_colour, group_rank, group_avatar, group_avatar_type, group_avatar_width, group_avatar_height 
					FROM ' . GROUPS_TABLE . " 
					WHERE group_id = $group_id";
				$result = $_CLASS['core_db']->query($sql);

				if (!extract($_CLASS['core_db']->fetch_row_assoc($result)))
				{
					return 'NO_GROUP';
				}
				$_CLASS['core_db']->free_result($result);

				if (!$group_avatar_width)
				{
					unset($group_avatar_width);
				}
				if (!$group_avatar_height)
				{
					unset($group_avatar_height);
				}
			}

			// FAILURE HERE when grabbing data from DB and checking "isset" ... will
			// be true for all similar functionality

			$sql_set = '';
			foreach ($attribute_ary as $attribute => $type)
			{
				if (isset($$attribute))
				{
					$field = str_replace('group_', 'user_', $attribute);

					switch ($type)
					{
						case 'int':
							$sql_set .= ", $field = " . (int) $$attribute;
							break;
						case 'double':
							$sql_set .= ", $field = " . (double) $$attribute;
							break;
						case 'string':
							$sql_set .= ", $field = '" . (string) $_CLASS['core_db']->escape($$attribute) . "'";
							break;
					}
				}
			}

			$sql = 'UPDATE ' . USERS_TABLE . "
				SET group_id = $group_id$sql_set  
				WHERE user_id IN (" . implode(', ', $user_id_ary) . ')';
			$_CLASS['core_db']->query($sql);

			$log = 'LOG_GROUP_DEFAULTS';
			break;
	}

	if (!function_exists('add_log'))
	{
		global $site_file_root;

		include($site_file_root.'includes/forums/functions_admin.php');
	}

	// Clear permissions cache of relevant users
	$_CLASS['auth']->acl_clear_prefetch($user_id_ary);

	if (!$group_name)
	{
		$sql = 'SELECT group_name
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $group_id";
		$result = $_CLASS['core_db']->query($sql);

		if (!extract($_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error("Could not obtain name of group $group_id", E_USER_ERROR);
		}
	}

	add_log('admin', $log, $group_name, implode(', ', $username_ary));

	unset($username_ary);
	unset($user_id_ary);

	return false;
}

/**
* Obtain either the members of a specified group, the groups the specified user is subscribed to
* or checking if a specified user is in a specified group
*
* Note: Extend select statement as needed
* Note2: Never use this more than once... first group your users/groups
*/
function group_memberships($group_id_ary = false, $user_id_ary = false, $return_bool = false)
{
	global $_CLASS;

	if (!$group_id_ary && !$user_id_ary)
	{
		return true;
	}

	$sql = 'SELECT group_id, user_id, user_status
		FROM ' . USER_GROUP_TABLE . '
		WHERE ';

	if ($group_id_ary && $user_id_ary)
	{
		$sql .= " group_id " . ((is_array($group_id_ary)) ? ' IN (' . implode(', ', $group_id_ary) . ')' : " = $group_id_ary") . "
				AND user_id " . ((is_array($user_id_ary)) ? ' IN (' . implode(', ', $user_id_ary) . ')' : " = $user_id_ary");
	}
	else if ($group_id)
	{
		$sql .= " group_id " . ((is_array($group_id_ary)) ? ' IN (' . implode(', ', $group_id_ary) . ')' : " = $group_id_ary");
	}
	else if ($user_id_ary)
	{
		$sql .= " user_id " . ((is_array($user_id_ary)) ? ' IN (' . implode(', ', $user_id_ary) . ')' : " = $user_id_ary");
	}
	
	$result = ($return_bool) ? $_CLASS['core_db']->query_limit($sql, 1) : $_CLASS['core_db']->query($sql);
	
	$row = $_CLASS['core_db']->fetch_row_assoc($result);

	if ($return_bool)
	{
		$_CLASS['core_db']->free_result($result);
		return ($row) ? true : false;
	}

	$result = array();

	do
	{
		$result[] = $row;
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
	
	return $result;
}

?>