<?php
/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2004 Bharat Mediratta
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * $Id: UserDB.php,v 1.11 2004/02/03 05:03:02 beckettmw Exp $
 */

class Nuke5_UserDB extends Abstract_UserDB
{

	function Nuke5_UserDB()
	{
		global $gallery;
		$this->nobody = new NobodyUser();
		$this->everybody = new EverybodyUser();
		$this->loggedIn = new LoggedInUser();
	}

	function getUidList()
	{
		global $_CLASS, $gallery_uidList;
		//static $gallery_uidList = array(); // May get killed on each class call

		if (is_array($gallery_uidList))
		{
			return $gallery_uidList;
		} else {
			$gallery_uidList = array();
		}
		
		$sql= 'select user_id from ' . USERS_TABLE . ' where user_id <> 1';
				      
		$result = $_CLASS['db']->sql_query($sql);
		
		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			array_push($gallery_uidList, $row['user_id']);
		}
		
		$_CLASS['db']->sql_freeresult($result);
		
		// wonder what this does //
		array_push($gallery_uidList, $this->nobody->getUid());
		array_push($gallery_uidList, $this->everybody->getUid());
		array_push($gallery_uidList, $this->loggedIn->getUid());

		sort($gallery_uidList);
		
		return $gallery_uidList;
	}

	function getUserByUsername($username, $level=0)
	{
		if (!strcmp($username, $this->nobody->getUsername())) {
			return $this->nobody;
		} else if (!strcmp($username, $this->everybody->getUsername())) {
			return $this->everybody;
		} else if (!strcmp($username, $this->loggedIn->getUsername())) {
			return $this->loggedIn;
		}

		$user = new Nuke5_User();
		$user->loadByUsername($username);
		return $user;
	}

	function getUserByUid($uid)
	{
		global $gallery;

		if (!$uid || !strcmp($uid, $this->nobody->getUid())) {
			return $this->nobody;
		} else if (!strcmp($uid, $this->everybody->getUid())) {
			return $this->everybody;
		} else if (!strcmp($uid, $this->loggedIn->getUid())) {
			return $this->loggedIn;
		}

		$user = new Nuke5_User();
		$user->loadByUid($uid);
		return $user;
	}
}

?>