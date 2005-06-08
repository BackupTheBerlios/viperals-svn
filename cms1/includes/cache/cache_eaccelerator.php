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
// Need a security key for those that want to use it on a share server $security_key .'_'.$var_name
// redo the sql parts
// make class an extendion to cache

class cache
{
	var $vars = array();
	var $var_expires = array();
	var $is_modified = false;
	var $data = false;
	var $sql_rowset = array();
	
	function cache()
	{
		global $site_file_root;

		$this->cache_dir = $site_file_root.'cache/';
	}

	function load($name)
	{
		$this->vars[$name] = eaccelerator_get($name);

		if ($this->vars[$name] === NULL)
		{
			unset($this->vars[$name]);
			return false;
		}
		else
		{	
			return unserialize($this->vars[$name]);
		}
	}

	function save() 
	{
		if (!is_array($this->is_modified))
		{
			return;
		}

		foreach ($this->is_modified as $file => $expire)
		{
			eaccelerator_lock($file);
			eaccelerator_put($file, serialize($this->vars[$file]), $expire);
			eaccelerator_unlock($file);
		}
		
		$this->is_modified = false;
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
	
	function exists($name)
	{
		// remove all calls to this
	}
	
	function put($name, $var, $ttl = 31536000)
	{
		$this->vars[$name] = $var;
		$this->is_modified[$name] = $ttl;
	}
	
	function gc()
	{
		eaccelerator_gc();
		set_core_config('cache_last_gc', time());
	}

	function destroy($var_name, $table = '')
	{
	// need to 
		if ($var_name == 'sql' && !empty($table))
		{
			$regex = '(' . ((is_array($table)) ? implode('|', $table) : $table) . ')';

			$dir = opendir($this->cache_dir);
			while ($entry = readdir($dir))
			{
				if (substr($entry, 0, 4) != 'sql_')
				{
					continue;
				}

				$fp = fopen($this->cache_dir . $entry, 'rb');
				$file = fread($fp, filesize($this->cache_dir . $entry));
				@fclose($fp);

				if (preg_match('#/\*.*?\W' . $regex . '\W.*?\*/#s', $file, $m))
				{
					unlink($this->cache_dir . $entry);
				}
			}
			closedir($dir);
			return;
		}
		eaccelerator_rm($var_name);
	}

	function format_array($array)
	{
		$lines = array();
		
		foreach ($array as $k => $v)
		{
			if (is_array($v))
			{
				$lines[] = "'$k'=>" . $this->format_array($v);
			}
			elseif (is_int($v))
			{
				$lines[] = "'$k'=>$v";
			}
			elseif (is_bool($v))
			{
				$lines[] = "'$k'=>" . (($v) ? 'true' : 'false');
			}
			else
			{
				$lines[] = "'$k'=>'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $v)) . "'";
			}
		}
		return 'array(' . implode(',', $lines) . ')';
	}

// See what can be done here
	function sql_load($query)
	{
		// Remove extra spaces and tabs
		$query = preg_replace('/[\n\r\s\t]+/', ' ', $query);
		$query_id = 'Cache id #' . sizeof($this->sql_rowset);

		if (!file_exists($this->cache_dir . 'sql_' . md5($query) . ".php"))
		{
			return false;
		}

		include($this->cache_dir . 'sql_' . md5($query) . ".php");

		if (!isset($expired))
		{
			return FALSE;
		}
		elseif ($expired)
		{
			unlink($this->cache_dir . 'sql_' . md5($query) . ".php");
			return FALSE;
		}

		return $query_id;
	}

	function sql_save($query, &$query_result, $ttl)
	{
		global $_CLASS;

		// Remove extra spaces and tabs
		$query = preg_replace('/[\n\r\s\t]+/', ' ', $query);

		if ($fp = @fopen($this->cache_dir . 'sql_' . md5($query) . '.php', 'wb'))
		{
			@flock($fp, LOCK_EX);

			$lines = array();
			$query_id = 'Cache id #' . sizeof($this->sql_rowset);
			$this->sql_rowset[$query_id] = array();

			while ($row = $_CLASS['core_db']->sql_fetchrow($query_result))
			{
				$this->sql_rowset[$query_id][] = $row;

				$lines[] = "unserialize('" . str_replace("'", "\\'", str_replace('\\', '\\\\', serialize($row))) . "')";
			}
			$_CLASS['core_db']->sql_freeresult($query_result);

			fwrite($fp, "<?php\n\n/*\n$query\n*/\n\n\$expired = (time() > " . (time() + $ttl) . ") ? TRUE : FALSE;\nif (\$expired) { return; }\n\n\$this->sql_rowset[\$query_id] = array(" . implode(',', $lines) . ') ?>');
			@flock($fp, LOCK_UN);
			fclose($fp);

			$query_result = $query_id;
		}
	}

	function sql_exists($query_id)
	{
		return isset($this->sql_rowset[$query_id]);
	}

	function sql_fetchrow($query_id)
	{
		return array_shift($this->sql_rowset[$query_id]);
	}
}
?>