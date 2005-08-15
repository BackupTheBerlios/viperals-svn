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
*/

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

function user_activate($user_id)
{
	global $_CLASS, $_CORE_CONFIG;

	$user_id = array_map('intval', is_array($user_id) ? $user_id : array($user_id));

	if (empty($user_id))
	{
		return;
	}
// hook here -- maybe ?
	$sql = 'UPDATE ' . USERS_TABLE . '
		SET user_status = ' . USER_ACTIVE . '
			WHERE user_id  IN (' . implode(', ', $user_id) . ')
			AND user_type <>' . USER_GUEST;

	$_CLASS['core_db']->query($sql);
	
	set_core_config('user', 'num_users', $_CORE_CONFIG['user']['num_users'] + count($user_id));
}

function user_disable($user_id)
{
	global $_CLASS, $_CORE_CONFIG;

	$user_id = array_map('intval', is_array($user_id) ? $user_id : array($user_id));

	if (empty($user_id))
	{
		return;
	}
// hook here -- maybe ?
	$sql = 'UPDATE ' . USERS_TABLE . '
		SET user_status = ' . USER_DISABLE . '
			WHERE user_id  IN (' . implode(', ', $user_id) . ')
			AND user_type <>' . USER_GUEST;
	$_CLASS['core_db']->query($sql);

	if (in_array($_CORE_CONFIG['user']['newest_user_id'], $user_id))
	{
		$sql = 'SELECT user_id, username FROM ' . USERS_TABLE . '
			WHERE user_type = '.USER_NORMAL.' AND user_status = '.USER_ACTIVE.'
			ORDER BY user_regdate';

		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		set_core_config('user', 'newest_user_id', $row['user_id'], false);
		set_core_config('user', 'newest_username', $row['username'], false);
	}

	set_core_config('user', 'num_users', $_CORE_CONFIG['user']['num_users'] - count($user_id), false);
	
	$_CLASS['core_cache']->destroy('core_config');
}

function user_activate_reminder($user_id)
{
	global $_CLASS;

	$user_id = array_map('intval', is_array($user_id) ? $user_id : array($user_id));

	if (empty($user_id))
	{
		return;
	}
// hook here -- maybe ?
	$sql = 'UPDATE ' . USERS_TABLE . '
		SET user_status = ' . USER_ACTIVE . '
			WHERE user_id  IN (' . implode(', ', $user_id) . ')
			AND user_type =' . USER_NORMAL;

	$_CLASS['core_db']->query($sql);
}

function user_delete($user_id, $quick = false)
{
	global $_CLASS;

	if ($quick)
	{
		$sql = "DELETE FROM USERS_TABLE
			WHERE user_id IN (" . implode(', ', $user_id) . ')';
		$_CLASS['core_db']->query($sql);
		
		return;
	}
	$user_id = array_map('intval', is_array($user_id) ? $user_id : array($user_id));

// Maybe we should make this a cron
// and just set the user type to deleted or something
	set_time_limit(0);
	ignore_user_abort(true);

	if (empty($user_id))
	{
		return;
	}

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

function groups_user_remove($group_id, $user_id)
{
	global $_CLASS;

	$group_ids = is_array($group_id) ? $group_id : array($group_id);
	$user_ids = is_array($user_id) ? $user_id : array($user_id);

	$group_id = array_map('intval', $group_id);
	$user_id = array_map('intval', $user_id);

	if (empty($group_id) || empty($user_id))
	{
		return;
	}

	$sql = 'SELECT user_id FROM ' . USERS_TABLE . ' 
				WHERE group_id IN (' . implode(', ', $group_id) . ')
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
					SET group_id = 4, user_rank = -1
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

	$group_ids = is_array($group_id) ? $group_id : array($group_id);
	$user_ids = is_array($user_id) ? $user_id : array($user_id);

	$group_id = array_map('intval', $group_id);
	$user_id = array_map('intval', $user_id);

	if (empty($group_id) || empty($user_id))
	{
		return;
	}

	$sql = 'SELECT member_user_id, member_status FROM ' . USER_GROUP_TABLE . ' 
				WHERE group_id IN (' . implode(', ', $group_id) . ')
				AND member_user_id IN (' . implode(', ', $user_id) . ')
				AND  = '.$status;
	$result = $_CLASS['core_db']->query($sql);

	$update_array = array();
	$ignore_array = array();

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$update_id[] = $row['user_id'];
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
					SET group_id = 4, user_rank = -1
					WHERE user_id IN (' . implode(', ', $group_id) . ')';
		$result = $_CLASS['core_db']->query($sql);
	}

	$sql = 'DELETE FROM ' . USER_GROUP_TABLE . '
		WHERE group_id IN ('. implode(', ', $group_id) . ')
		AND user_id IN ('. implode(', ', $user_id) .')';

	$result = $_CLASS['core_db']->query($sql);
}

?>