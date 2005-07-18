<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )						 		//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
/*
	For phpBB3 mods
*/

class ucp_confirm extends module 
{
	function ucp_confirm($id, $mode)
	{
		include_once($site_file_root.'includes/system.php');
		confirmation_image();
	}
}

?>