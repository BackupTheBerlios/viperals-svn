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
	function cache_file()
	{
		global $site_file_root;

		$this->cache_dir = $site_file_root.'cache/';
	}
	
	function load($name)
	{
		if (file_exists($this->cache_dir . "data_$name.php"))
		{
			require($this->cache_dir . "data_$name.php");
			
			if (empty($this->vars[$name]))
			{
				return false;
			}
			
			if (time() > $this->var_expires[$name])
			{
				$this->destroy($name);
				return false;
			}
			
			return $this->vars[$name];
		}
		else
		{
			return false;
		}
	}

	function put($name, $value, $ttl = 31536000)
	{
		$new_line = chr(10);
		$protection_code = "if (!defined('VIPERAL')) { die('Hello'); }$new_line";

		$data = '<?php '.$protection_code.' $this->vars[\''.$name.'\']=' . $this->format_array($value) . ";\n\$this->var_expires['$name'] = " . (time() + $ttl) . ' ?>';
	
		if ($fp = @fopen($this->cache_dir . "data_$name.php", 'wb'))
		{
			@flock($fp, LOCK_EX);
			fwrite($fp, $data);
			@flock($fp, LOCK_UN);
			fclose($fp);
		}

		$this->vars[$name] = $value;
	}
	
	// All cached file are loaded and not removed, memory problem here
	function gc()
	{
		$dir = opendir($this->cache_dir);
		
		while ($name = readdir($dir))
		{
			if (strpos($name, 'data_') === 0)
			{
				$var_name = preg_replace(array('/data_/', '/.php/'), '', $entry);
				
				if (empty($this->var_expires[$var_name]))
				{
					include($this->cache_dir . $entry);
				}
				
				if (time() > $this->var_expires[$var_name])
				{
					unlink($this->cache_dir . $entry);
					unset($this->var_expires[$var_name]);
				}
			}
		}
		
		closedir($dir);
	}

	function destroy($name)
	{
		unlink($this->cache_dir . "data_$name.php");
		$this->remove($name);
	}

}

?>