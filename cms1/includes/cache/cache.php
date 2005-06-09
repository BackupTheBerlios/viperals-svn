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

class cache
{
	var $vars = array();
	var $var_expires = array();
	var $sql_rowset = array();
	
	function exists($name)
	{
		print_r(debug_backtrace());
		// remove all calls to this
	}
	
	function format_array($array, $tab = false)
	{
		$tab = ($tab) ? $tab : chr(9);
		$lines = array();
		$new_line = chr(10);
		//"windows" = "chr(13).chr(10)"  "Mac" = "chr(13)"

		foreach ($array as $key => $value)
		{
			$key = is_int($key) ? $key.' => ' : "'$key' => ";

			if (is_array($value))
			{
				$lines[] = $key . $this->format_array($value, $tab.$tab);
			}
			elseif (is_int($value))
			{
				$lines[] = $key.$value;
			}
			elseif (is_bool($value))
			{
				$lines[] = $key . (($value) ? 'true' : 'false');
			}
			else
			{
				$lines[] = $key . "'".str_replace("'", "\\'", str_replace('\\', '\\\\', $value)) . "'";
			}
		}
		return 'array('.$new_line. $tab . implode(','.$new_line. $tab, $lines) . ')';
	}

	function get($name)
	{
		if (empty($this->vars[$name]))
		{
			return $this->load($name);
		}

		return $this->vars[$name];
	}

	function remove($name)
	{
		if (!empty($this->vars[$name]))
		{
			unset($this->vars[$name]);
			unset($this->var_expires[$name]);
		}
	}

	function save() 
	{

	}

	function sql_exists($query_id)
	{
		return isset($this->sql_rowset[$query_id]);
	}

	function sql_fetchrow($query_id)
	{
		return array_shift($this->sql_rowset[$query_id]);
	}

	function sql_freeresult($query_id)
	{
		unset($this->sql_rowset[$query_id]);
	}
}

//set_config('cache_last_gc', time(), true);

?>