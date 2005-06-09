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
// recommended setting for keys
// eaccelerator.keys  = shm

// Add an error so people know to change the keys, if they have a lucky shared server with this enabled
// Doesn't matter on dedicated servers but it doesn't hurt.

class cache_eaccelerator extends cache
{
	var $key = 'default_';

	function cache_eaccelerator()
	{
		if (!function_exists('eaccelerator_put'))
		{
			// Error here
		}
	}

	function load($name)
	{
		$this->vars[$name] = eaccelerator_get($this->key.$name);

		if (is_null($this->vars[$name]))
		{
			return $this->vars[$name] = false;
		}
		else
		{	
			return unserialize($this->vars[$name]);
		}
	}

	function put($name, $value, $ttl = 604800)
	{
		$ttl = ((int) $ttl) ? (int) $ttl : 604800;

		if (eaccelerator_lock($this->key.$name))
		{
			eaccelerator_put($this->key.$name, serialize($value), $expire);
			eaccelerator_unlock($this->key.$name);
		}

		$this->vars[$name] = $value;
	}

	function gc()
	{
		eaccelerator_gc();
	}

	function destroy($name)
	{
		eaccelerator_rm($this->key.$name);
	}
}

?>