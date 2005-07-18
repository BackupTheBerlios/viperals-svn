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

class cache_none extends cache
{
	function load($name)
	{
		return false;
	}

	function put($name, $value, $ttl = 0)
	{

	}
	
	function gc()
	{

	}

	function destroy($name)
	{

	}
}

?>