<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

function user_get_id($username, &$difference = array())
{
	global $_CLASS;

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

function user_get_name($user_ids, &$difference = array())
{
	global $_CLASS;

	$user_ids = array_map('intval', $user_ids);

	if (empty($user_ids))
	{
		return;
	}

	$data = array('user_id' => array(), 'username' => array());

	$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . ' 
				WHERE user_id IN (' . implode(', ', $user_ids) . ')';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$data['user_ids'][] = $row['user_id'];
		$data['username'][] = $row['username'];
	}
	$_CLASS['core_db']->free_result($result);

	$difference = array_diff($user_ids, $data['user_ids']);

	return $data['username'];
}

function groups_user_remove($group_ids, $user_ids)
{
	global $_CLASS;

	$group_ids = is_array($group_ids) ? $group_ids : array($group_ids);
	$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);

	$group_ids = array_map('intval', $group_ids);
	$user_ids = array_map('intval', $user_ids);

	if (empty($group_ids) || empty($user_ids))
	{
		return;
	}

	$sql = 'SELECT user_id FROM ' . USERS_TABLE . ' 
				WHERE group_id IN (' . implode(', ', $group_ids) . ')
				AND user_id IN (' . implode(', ', $group_ids) . ')';
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
					WHERE user_id IN (' . implode(', ', $group_ids) . ')';
		$result = $_CLASS['core_db']->query($sql);
	}

	$sql = 'DELETE FROM ' . USER_GROUP_TABLE . '
		WHERE group_id IN ('. implode(', ', $group_ids) . ')
		AND user_id IN ('. implode(', ', $user_ids) .')';

	$result = $_CLASS['core_db']->query($sql);
}

?>