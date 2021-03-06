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

function avatar_gallery(&$current_folder, &$folders, &$error)
{
	global $config, $_CLASS, $_CORE_CONFIG;

	$path = $config['avatar_gallery_path'];
	$data = $folders = array();

	if ($current_folder)
	{
		$path .= '/'.$current_folder;
	}
	
	if (!file_exists($path) || !is_dir($path))
	{
		return $data;
	}

	$dir = @opendir($path);

	$count = 0;

	while ($file = readdir($dir))
	{
		if (preg_match('#\.(gif$|png$|jpg|jpeg)$#i', $file))
		{	
			$data[$count]['file'] = ($current_folder) ? $current_folder.'/'.$file : $file; 
			$data[$count]['name'] = ucfirst(str_replace('_', ' ', preg_replace('#^(.*)\..*$#', '\1', $file)));

			$count++;
		}

		if (!$current_folder)
		{
			if ($file{0} != '.' && is_dir("$path/$file"))
			{
				$folders[] = $file;
			}
		}
	}
	closedir($dir);
	
	if ($current_folder)
	{
		$dir = @opendir($config['avatar_gallery_path']);

		while ($file = readdir($dir))
		{
			if ($file{0} != '.' && is_dir($config['avatar_gallery_path']."/$file"))
			{	
				$folders[] = $file;
			}
		}
		closedir($dir);
	}

	if (!$current_folder && empty($data) && !empty($folders))
	{
		$current_folder = $folders[0];

		$path = $config['avatar_gallery_path'].'/'.$current_folder;

		$dir = @opendir($path);
		while ($file = readdir($dir))
		{
			if (preg_match('#\.(gif$|png$|jpg|jpeg)$#i', $file))
			{	
				$data[$count]['file'] = $current_folder.'/'.$file; 
				$data[$count]['name'] = ucfirst(str_replace('_', ' ', preg_replace('#^(.*)\..*$#', '\1', $file)));
	
				$count++;
			}
		}
		closedir($dir);
	}

	ksort($data);

	return $data;
}

function check_user_id(&$user_id, $bypass = false)
{
	// should we just return false, if this array map is different from the one sent

	$user_id = array_map('intval', $user_id);
	$user_id = array_unique($user_id);

	// array map should make 0 values for notint values
	$key = array_search(0, $user_id);

	if ($key !== false && !is_null($key))
	{
		unset($user_id[$key]);
	}

// make bypass an array maybe, along with protected id. would be better if you want to extend this
	if ($bypass)
	{
		// You shouldn'y do anything to guest
		if (($key = array_search(1, $user_id)) === false)
		{
			unset($user_id[$key]);
		}
	
		// First admin is always specail
		if (($key = array_search(2, $user_id)) === false)
		{
			unset($user_id[$key]);
		}
	}

	if (empty($user_id))
	{
		return false;
	}
	
	return $user_id;
}

function user_add(&$data)
{
	global $_CLASS, $_CORE_CONFIG;

	$default_data = array(
		'user_allow_viewonline' => 1,
		'user_allow_viewemail'	=> 1,
		'user_allow_massemail'	=> 1,
		'user_new_privmsg'		=> 0,
		'user_allow_pm'			=> 1,
		'user_allow_email'		=> 1,
	);

	$data = array_merge($default_data, $data);

// add a required array here, then find feilds that are not in the data array
	$_CLASS['core_db']->transaction();

	$sql = 'INSERT INTO ' . USERS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data);
	$_CLASS['core_db']->query($sql);

	$data['user_id'] = $_CLASS['core_db']->insert_id(USERS_TABLE, 'user_id');

	$sql = 'INSERT INTO ' . USER_GROUP_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
		'group_id'		=> (int) $data['user_group'],
		'user_id'		=> (int) $data['user_id'],
		'member_status'	=> $data['user_status']
	));
	
	$_CLASS['core_db']->query($sql);

	$_CLASS['core_db']->transaction('commit');
}

function user_activate($user_id, $update_stats = true)
{
	global $_CLASS, $_CORE_CONFIG;

	$user_id = is_array($user_id) ? $user_id : array($user_id);

	if (check_user_id($user_id) == false)
	{
		return;
	}
	
	$_CLASS['core_db']->transaction();

// hook here
	$sql = 'UPDATE ' . USERS_TABLE . '
		SET user_status = ' . STATUS_ACTIVE . '
			WHERE user_id  IN (' . implode(', ', $user_id) . ')
			AND user_type <>' . USER_GUEST;

	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . USER_GROUP_TABLE . '
		SET member_status = ' . STATUS_ACTIVE . '
			WHERE user_id  IN (' . implode(', ', $user_id) . ') 
			AND member_status = ' . STATUS_DISABLED;

	$_CLASS['core_db']->query($sql);
	
	if ($update_stats)
	{
		set_core_config('user', 'total_users', $_CORE_CONFIG['user']['total_users'] + count($user_id));

		$_CLASS['core_cache']->destroy('core_config');
	}

	$_CLASS['core_db']->transaction('commit');
}

function user_activate_reminder($user_id)
{

}

function user_disable($user_id, $update_stats = true)
{
	global $_CLASS, $_CORE_CONFIG;

	$user_id = is_array($user_id) ? $user_id : array($user_id);

	if (check_user_id($user_id) == false)
	{
		return;
	}

// hook here -- maybe ?
	$_CLASS['core_db']->transaction();

	// disabled the user first
	$sql = 'UPDATE ' . USERS_TABLE . '
		SET user_status = ' . STATUS_DISABLED . '
			WHERE user_id  IN (' . implode(', ', $user_id) . ')
			AND user_type <>' . USER_GUEST;
	$_CLASS['core_db']->query($sql);

	// Now we disable the user in his active groups
	//	( note disable is not uses for group removal, the entry is just deleted )
// should also remove all pending groups
	$sql = 'UPDATE ' . USER_GROUP_TABLE . '
		SET member_status = ' . STATUS_DISABLED . '
			WHERE user_id IN (' . implode(', ', $user_id) . ')
			AND member_status = ' . STATUS_ACTIVE;
	$_CLASS['core_db']->query($sql);

	if ($update_stats)
	{
		if (in_array($_CORE_CONFIG['user']['newest_user_id'], $user_id))
		{
			$sql = 'SELECT user_id, username FROM ' . USERS_TABLE . '
				WHERE user_type = '.USER_NORMAL.' AND user_status = '.STATUS_ACTIVE.'
				ORDER BY user_reg_date';
	
			$result = $_CLASS['core_db']->query_limit($sql, 1);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);
	
			set_core_config('user', 'newest_user_id', $row['user_id'], false);
			set_core_config('user', 'newest_username', $row['username'], false);
		}
	
		$total_users = $_CORE_CONFIG['user']['total_users'] - count($user_id);
		set_core_config('user', 'total_users', $total_users, false);
		
		$_CLASS['core_cache']->destroy('core_config');
	}
	
	$_CLASS['core_db']->transaction('commit');
}

function user_delete($user_id, $quick = false)
{
	global $_CLASS;

	$user_id = is_array($user_id) ? $user_id : array($user_id);

	if (check_user_id($user_id) == false)
	{
		return;
	}

	if ($quick)
	{
		$sql = "DELETE FROM USERS_TABLE
			WHERE user_id IN (" . implode(', ', $user_id) . ')';
		$_CLASS['core_db']->query($sql);
		
		return;
	}

// Maybe we should make this a cron
// and just set the user type to deleted or something
	set_time_limit(0);
	ignore_user_abort(true);

	// We disable users first to make sure things go right
	user_disable($user_id);

	$_CLASS['core_db']->transaction();

	$optimize_array = array();
	$tables = array(USERS_TABLE => 'user_id', USER_GROUP_TABLE => 'user_id');
// hook here

// Move this to hooks on seperation
	$tables += array(FORUMS_ACL_TABLE => 'user_id', FORUMS_WATCH_TABLE => 'user_id', FORUMS_TRACK_TABLE => 'user_id');

	$sql = 'UPDATE ' . FORUMS_TABLE . '
		SET forum_last_poster_id = ' . ANONYMOUS . " 
		WHERE forum_last_poster_id IN (" . implode(', ', $user_id) . ')';
	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . POSTS_TABLE . '
		SET poster_id = ' . ANONYMOUS . " 
		WHERE poster_id IN (" . implode(', ', $user_id) . ')';
	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_poster = ' . ANONYMOUS . "
		WHERE topic_poster IN (" . implode(', ', $user_id) . ')';
	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_last_poster_id = ' . ANONYMOUS . "
		WHERE topic_last_poster_id IN (" . implode(', ', $user_id) . ')';
	$_CLASS['core_db']->query($sql);

	switch ($_CLASS['core_db']->db_layer)
	{
		//case 'mysql4':
		//case 'mysqli':
		//DELETE FROM t1, t2 USING t1, t2, t3 WHERE t1.id=t2.id AND t2.id=t3.id;
		//break;

		default:
			foreach ($tables as $table => $feild)
			{
				$sql = "DELETE FROM $table 
					WHERE $feild IN (" . implode(', ', $user_id) . ')';
				$_CLASS['core_db']->query($sql);
			}
			$optimize_array[] = $table;
		break;
	}

// error on commit fail ( think about seperation commits for hooks )
	$_CLASS['core_db']->transaction('commit');
	
// This should be in hooks
	$_CLASS['core_db']->optimize_tables($optimize_array);
}

function user_get_id($username, &$difference)
{
	global $_CLASS;

	$difference = array();

	$username = is_array($username) ? $username : array($username);

	$data = array('user_id' => array(), 'username' => array());

	$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . " 
				WHERE username IN ('" . implode("' ,'", $_CLASS['core_db']->escape_array($username)) . "')";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$data['user_id'][] = $row['user_id'];
		$data['username'][] = $row['username'];
	}
	$_CLASS['core_db']->free_result($result);

	$difference = array_diff($username, $data['username']);

	return $data['user_id'];
}

function user_get_name($user_id, &$difference)
{
	global $_CLASS;

	$difference = array();

	$user_id = array_map('intval', is_array($user_id) ? $user_id : array($user_id));

	if (empty($user_id))
	{
		return;
	}

	$data = array('user_id' => array(), 'username' => array());

	$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . ' 
				WHERE user_id IN (' . implode(', ', $user_id) . ')';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$data['user_id'][] = $row['user_id'];
		$data['username'][] = $row['username'];
	}
	$_CLASS['core_db']->free_result($result);

	$difference = array_diff($user_id, $data['user_id']);

	return $data['username'];
}

function groups_user_remove($group_id, $user_id)
{
	global $_CLASS;

	$group_id = is_array($group_id) ? $group_id : array($group_id);
	$user_id = is_array($user_id) ? $user_id : array($user_id);

	$group_id = array_map('intval', $group_id);

	if (check_user_id($user_id) == false)
	{
		return;
	}

	if (empty($group_id))
	{
		return;
	}

	$sql = 'SELECT user_id FROM ' . USERS_TABLE . ' 
				WHERE user_group IN (' . implode(', ', $group_id) . ')
				AND user_id IN (' . implode(', ', $user_id) . ')';
	$result = $_CLASS['core_db']->query($sql);

	$defaults = array();

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$defaults[] = $row['user_id'];
	}
	$_CLASS['core_db']->free_result($result);
	
	// We move all users that are removed from the default groups to
	// REGISTERED / REGISTERED_COPPA
	if (!empty($defaults))
	{
// need to update/completion
		$result = $_CLASS['core_db']->query('SELECT * FROM ' . GROUPS_TABLE . ' 	WHERE group_id = 4');

		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$sql = 'UPDATE FROM '. USERS_TABLE .' 
					SET user_group = 4, user_rank = -1
					WHERE user_id IN (' . implode(', ', $group_id) . ')';
		$result = $_CLASS['core_db']->query($sql);
	}

	$sql = 'DELETE FROM ' . USER_GROUP_TABLE . '
		WHERE group_id IN ('. implode(', ', $group_id) . ')
		AND user_id IN ('. implode(', ', $user_id) .')';

	$result = $_CLASS['core_db']->query($sql);
}

function groups_user_add($group_id, $user_id, $status)
{
	global $_CLASS;

	$group_id = is_array($group_id) ? $group_id : array($group_id);
	$user_id = is_array($user_id) ? $user_id : array($user_id);

	$group_id = array_map('intval', $group_id);
	$user_id = array_map('intval', $user_id);

	if (empty($group_id) || empty($user_id))
	{
		return;
	}

	$_CLASS['core_db']->transaction();

	foreach ($user_id as $u_id)
	{
		foreach ($group_id as $g_id)
		{
			$data = array(
				'group_id'		=> (int) $g_id,
				'user_id'		=> (int) $u_id,
				'member_status'	=> (int) $status,
			);
		
			$_CLASS['core_db']->query('INSERT INTO '.USER_GROUP_TABLE.' '.$_CLASS['core_db']->sql_build_array('INSERT', $data));
		}
	}

	$_CLASS['core_db']->transaction('commit');
}

function validate_username($username)
{
	global $_CORE_CONFIG, $_CLASS;

	$username = strtolower($username);

	if (strtolower($_CLASS['core_user']->data['username']) == $username)
	{
		return 'USERNAME_TAKEN';
	}

	$length = mb_strlen($username);

	if ($length > $_CORE_CONFIG['user']['max_name_chars'] || $length < $_CORE_CONFIG['user']['min_name_chars'])
	{
		return 'USERNAME_INVALID_LENGHT';
	}

	if (!preg_match('#^' . $_CORE_CONFIG['user']['allow_name_chars'] . '$#i', $username))
	{
		return 'USERNAME_INVALID_CHARS';
	}

	$sql = 'SELECT username
		FROM ' . USERS_TABLE . "
		WHERE LOWER(username) = '" . $_CLASS['core_db']->escape($username) . "'";

	$result = $_CLASS['core_db']->query_limit($sql, 1);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (is_array($row))
	{
		return 'USERNAME_TAKEN';
	}

	return true;
}

?>