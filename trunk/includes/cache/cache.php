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
//	õ															//
//**************************************************************//

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