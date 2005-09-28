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

class cache
{
	var $vars = array();
	var $var_expires = array();
	var $sql_rowset = array();
	
	/*
		Get a cached value
		Checks if it's loaded to memory before atempting to get from cache
	*/
	function get($name)
	{
		if (empty($this->vars[$name]))
		{
			return $this->load($name);
		}

		return $this->vars[$name];
	}

	/*
		Remove stored value from memory
	*/
	function remove($name)
	{
		if (!empty($this->vars[$name]))
		{
			unset($this->vars[$name]);
		}
	}

	function save() 
	{

	}
}

?>