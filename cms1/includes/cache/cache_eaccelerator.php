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

class cache_eaccelerator extends cache
{
	var $unique_id = 'gjkghkjkhgkhj';

	function load($name)
	{
		$this->vars[$name] = eaccelerator_get($name);

		if (is_null($this->vars[$name]))
		{
			unset($this->vars[$name]);
			return false;
		}
		else
		{	
			return unserialize($this->vars[$name]);
		}
	}
	
	function put($name, $value, $ttl = 604800)
	{
		$ttl = ((int) $ttl) ? (int) $ttl : 604800;

		if (eaccelerator_lock($name))
		{
			eaccelerator_put($name, serialize($value), $expire);
			eaccelerator_unlock($name);
		}

		$this->vars[$name] = $value;
	}
	
	function gc()
	{
		eaccelerator_gc();
	}

	function destroy($name, $table = '')
	{
		eaccelerator_rm($name);
	}

// need some testing here
	function sql_load($query)
	{
		$query = preg_replace('/[\n\r\s\t]+/', ' ', $query);
		$name = md5($query);
		
		if ($data = $this->load($name))
		{
			$query_id = 'Cache id #' . sizeof($this->sql_rowset);
			
			$this->sql_rowset[$query_id] = $data;
			return $query_id;
		}
		return false;
	}

	function sql_save($query, &$query_result, $ttl)
	{
		global $_CLASS;

		$query = preg_replace('/[\n\r\s\t]+/', ' ', $query);
		$name = md5($query);
		
		$lines = array();
		$query_id = 'Cache id #' . sizeof($this->sql_rowset);
						
		$this->sql_rowset[$query_id] = array();	

		while ($row = $_CLASS['core_db']->sql_fetchrow($query_result))
		{
			$this->sql_rowset[$query_id][] = $row;
		}
		$_CLASS['core_db']->sql_freeresult($query_result);
		$query_result = $query_id;
		
		$this->put($name, $this->sql_rowset[$query_id], $ttl);
	}
}

?>