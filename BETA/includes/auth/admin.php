<?php
/*
User permision overrides group permision
To do
Add group option
*/

class admin
{
	var $acl = array();
	var $option = array();
	var $got_data = false;
	var $user_permission = array();
	var $group_permission = array();


	function get_data()
	{
		global $_CLASS;
		
		$selectsql = ' WHERE user_id ='.$_CLASS['user']->data['user_id'];

		$sql = 'SELECT user_id, group_id, section_id, status 
				FROM ' . AUTH_ADMIN_TABLE . $selectsql .' ORDER BY user_id';
		$result = $_CLASS['db']->sql_query($sql);

			if ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				do
				{
					if ($row['user_id'])
					{
						$this->user_permission[$row['section_id']] = $row['status'];
					}
					elseif ($row['group_id'] && !isset($this->user_permission[$row['user_id']][$row['section_id']]))
					{
						$this->group_permission[$row['section_id']] = $row['status'];
					}
				}
				while ($row = $_CLASS['db']->sql_fetchrow($result));
			}
			
		$this->got_data = true;
		return;
	}

	function admin_auth($section_id = false)
	{
	global $_CLASS;
		
	if (!$this->got_data)
	{
		$this->get_data();
	}
	
	return admin_power();
	
	}
	
	function admin_power($section_id = false)
	{
		if (!$this->got_data)
		{
			$this->get_data();
		}
	
		if (!$this->user_permission && !$this->group_permission)
		{
			return false;
		}
	
		return true;
	}
}
?>