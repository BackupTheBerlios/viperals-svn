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

class cache_file extends cache
{
	var $cache_dir;
	var $expires;

	function cache_file()
	{
		global $site_file_root;

		$this->cache_dir = $site_file_root.'cache/';

		if (!is_writable($this->cache_dir))
		{
			//error here
		}
	}

	function load($name)
	{
		if (file_exists($this->cache_dir . "cache_$name.php"))
		{
			require($this->cache_dir . "cache_$name.php");

			if (!isset($this->vars[$name]) || gmtime() > $this->expires[$name])
			{
				$this->destroy($name);
				return $this->vars[$name] = null;
			}

			return $this->vars[$name];
		}
		else
		{
			return null;
		}
	}

	function put($name, $data, $ttl = 31536000)
	{
		$new_line = chr(10);
		$protection_code = "if (!defined('VIPERAL')) { die('Hello'); }$new_line";
		$expires = gmtime() + $ttl;

		$file_data = "<?php $protection_code \$this->vars['$name'] = ".var_export($data, true)."; \n\$this->expires['$name'] = $expires;  ?>";

		file_put_contents($this->cache_dir."cache_$name.php", $file_data);

		$this->vars[$name] = $data;
		$this->expires[$name] = $expires;
	}
	
	function gc()
	{
		$dir = opendir($this->cache_dir);

		while ($file = readdir($dir))
		{
			if (strpos($file, 'cache_') === 0)
			{
				$name = preg_replace(array('/cache_/', '/.php/'), '', $file);
				$unset = false;

				if (empty($this->expires[$name]))
				{
					include($this->cache_dir . $name);
					$unset = true;
				}
				
				if (time() > $this->expires[$name])
				{
					unlink($this->cache_dir . $file);
					$this->remove($name);
				}

				($unset) ? $this->remove($name) : '';
			}
		}

		closedir($dir);
	}

	function destroy($name, $table = '')
	{
		if (file_exists($this->cache_dir . 'cache_' . $name . ".php"))
		{
			unlink($this->cache_dir . "cache_$name.php");
			$this->remove($name);
		}
	}
}

?>