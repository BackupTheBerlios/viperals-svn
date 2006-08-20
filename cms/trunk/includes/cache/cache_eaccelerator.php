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

class cache_eaccelerator extends cache
{
	var $key = 'default_';

	function cache_eaccelerator()
	{
		if (!function_exists('eaccelerator_put'))
		{
			// Error here
		}

		eaccelerator_gc();
	}

	function load($name)
	{
		$this->vars[$name] = eaccelerator_get($this->key.$name);

		if (!is_null($this->vars[$name]))
		{
			$this->vars[$name] = unserialize($this->vars[$name]);
		}

		return $this->vars[$name];
	}

	function put($name, $data, $ttl = 604800)
	{
		$ttl = ((int) $ttl) ? (int) $ttl : 604800;
		$expires = gmtime() + $ttl;

		eaccelerator_put($this->key.$name, serialize($data), $expires);
		
		/*if (eaccelerator_lock($this->key.$name))
		{
			eaccelerator_put($this->key.$name, serialize($data), $expires);
			eaccelerator_unlock($this->key.$name);
		}*/

		$this->vars[$name] = $data;
	}

	function gc()
	{
		eaccelerator_gc();
	}

	function destroy($name)
	{
		eaccelerator_rm($this->key.$name);
		$this->remove($name);
	}

	function destroy_all()
	{
		if (!function_exists('eaccelerator_list_keys'))
		{
			return;
		}

		$key_list = eaccelerator_list_keys();

		foreach ($key_list as $key)
		{
			if (strpos($key['name'], $this->key) === 0)
			{
				$name = substr($key['name'], strlen($this->key));

				eaccelerator_rm($key['name']);
				$this->remove($name);
			}
		}
	}
}

?>