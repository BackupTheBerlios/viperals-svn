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

class auth_db extends core_auth
{
// move to main once we have another auth method as an example
	function user_auth($user_name, $user_password)
	{
		global $_CLASS;

		$sql = 'SELECT user_id, username, user_password, user_password_encoding, user_type 
					FROM ' . USERS_TABLE . " WHERE username = '" . $_CLASS['core_db']->sql_escape($user_name) . "'";

		$result = $_CLASS['core_db']->sql_query($sql);
		$status = false;
	
		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			if (encode_password($user_password, $row['user_password_encoding']) === $row['user_password'])
			{
				$status = ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) ? $row['user_type'] : (int) $row['user_id'];
			}
		}
		
		$_CLASS['core_db']->sql_freeresult($result);
		
		return $status;
	}
}

?>