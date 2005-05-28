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

function login_db($data)
{
	global $_CLASS;
	
	if ($data['user_id'])
	{
		$sql_where = "user_id = '" . $_CLASS['core_db']->sql_escape($data['user_id']) . "'";
	
	} elseif ($data['user_name']) {
	
		$sql_where = "username = '" . $_CLASS['core_db']->sql_escape($data['user_name']) . "'";
		
	} else {

		return false;
	}	
	
	$sql = 'SELECT user_id, username, user_password, user_password_encoding, user_type FROM ' . USERS_TABLE . " WHERE $sql_where";
		
	$result = $_CLASS['core_db']->sql_query($sql);
	$status = false;

	if ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$_CLASS['core_db']->sql_freeresult($result);
		
		if ($data['is_bot'] || (encode_password($data['user_password'], $row['user_password_encoding']) == $row['user_password']))
		{
			$status = ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) ? $row['user_type'] : true;
		}
	}
	
	$_CLASS['core_db']->sql_freeresult($result);
	
	return $status;
}

?>