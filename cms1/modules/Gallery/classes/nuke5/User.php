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
 * $Id: User.php,v 1.9 2004/02/03 05:03:02 beckettmw Exp $
 */
?>
<?php
class Nuke5_User extends Abstract_User
{
	function loadByUid($uid)
	{
		global $_CLASS, $gallery_save;
		
		if ($uid == $_CLASS['user']->data['user_id'])
		{
			$this->uid = $_CLASS['user']->data['user_id'];
			$this->fullname = $_CLASS['user']->data['username'];
			$this->email = $_CLASS['user']->data['user_email'];
			$this->isAdmin = 0;
			$this->canCreateAlbums = 0;
			$this->username = $_CLASS['user']->data['username'];
			
			return;
		}
		
		$query = false;
		
		if (empty($gallery_save['user_id'][$uid]))
		{
			$sql = 'select username, user_id, user_email from ' . USERS_TABLE . " where user_id ='$uid'";
				
			$result = $_CLASS['db']->sql_query($sql);
			$row = $_CLASS['db']->sql_fetchrow($result);
			$_CLASS['db']->sql_freeresult($result);
			$query = true;
			
			//Man wonder if i can get a better way to do this
			$gallery_save['user_id'][$uid] = array('username' => $row['username'], 'user_email' => $row['user_email']);
			$gallery_save['username'][$row['username']] = array('user_id' => $row['user_id'], 'user_email' => $row['user_email']);
		}
		
		$this->fullname = $this->username = ($query) ? $row['username'] : $gallery_save['user_id'][$uid]['username'];
		$this->email = ($query) ? $row['user_email'] : $gallery_save['user_id'][$uid]['user_email'];
		$this->isAdmin = 0;
		$this->canCreateAlbums = 0;
		$this->uid = $uid;
	}

	function loadByUserName($uname) 
	{
		global $_CLASS, $gallery_save;

		if ($uname == $_CLASS['user']->data['username'])
		{
			$this->uid = $_CLASS['user']->data['user_id'];
			$this->fullname = $_CLASS['user']->data['username'];
			$this->email = $_CLASS['user']->data['user_email'];
			$this->isAdmin = 0;
			$this->canCreateAlbums = 0;
			$this->username = $uname;
			return;
		}
		
		$query = false;
		
		if (empty($gallery_save['username'][$uname]))
		{
			$sql = 'select username, user_id, user_email from ' . USERS_TABLE . " where username ='$uname'";
				
			$result = $_CLASS['db']->sql_query($sql);
			$row = $_CLASS['db']->sql_fetchrow($result);
			$_CLASS['db']->sql_freeresult($result);
			$query = true;
			
			//Man wonder if i can get a better way to do this
			$gallery_save['user_id'][$row['user_id']] = array('username' => $row['username'], 'user_email' => $row['user_email']);
			$gallery_save['username'][$uname] = array('user_id' => $row['user_id'], 'user_email' => $row['user_email']);
		}
		
		$this->uid = ($query) ? $row['user_id'] : $gallery_save['username'][$uname]['user_id'];
		$this->fullname = $uname;
		$this->email = ($query) ? $row['user_email'] : $gallery_save['username'][$uname]['user_email'];
		$this->isAdmin = 0;
		$this->canCreateAlbums = 0;
		$this->username = $uname;
	}
}

?>