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

		if (eaccelerator_lock($this->key.$name))
		{
			eaccelerator_put($this->key.$name, serialize($data), $ttl);
			eaccelerator_unlock($this->key.$name);
		}

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
}

?>