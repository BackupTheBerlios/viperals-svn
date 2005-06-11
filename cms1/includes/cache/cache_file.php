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

			if (empty($this->vars[$name]) || time() > $this->expires[$name])
			{
				$this->destroy($name);
				return $this->vars[$name] = false;
			}

			return $this->vars[$name];
		}
		else
		{
			return false;
		}
	}

	function put($name, $data, $ttl = 31536000)
	{
		$new_line = chr(10);
		$protection_code = "if (!defined('VIPERAL')) { die('Hello'); }$new_line";
		$expires = time() + $ttl;

		$data = "<?php $protection_code \$this->vars['$name'] = ".$this->format_array($data)."; \n\$this->expires['$name'] = $expires;  ?>";

		if ($fp = @fopen($this->cache_dir . "cache_$name.php", 'wb'))
		{
			@flock($fp, LOCK_EX);
			fwrite($fp, $data);
			@flock($fp, LOCK_UN);
			fclose($fp);
		}

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