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
 * $Id: AdminUser.php,v 1.8 2004/02/03 05:03:01 beckettmw Exp $
 */
?>
<?php
class Nuke5_AdminUser extends Abstract_User 
{
	var $db;
	var $prefix;
	
	function Nuke5_AdminUser() 
	{
		global $_CLASS;
		
		$this->username = $_CLASS['user']->data['username'];
		$this->fullname = $_CLASS['user']->data['username'];
		$this->email = $_CLASS['user']->data['user_email'];
		
		$this->isAdmin = 1;
		$this->canCreateAlbums = 1;
		$this->uid = 'admin_'.$_CLASS['user']->data['user_id'];
	}
}

?>
