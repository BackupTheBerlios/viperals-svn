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

// -------------------------------------------------------------
//
// COPYRIGHT : © 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
//
// -------------------------------------------------------------

class cache
{
	var $vars = '';
	var $var_expires = array();
	var $is_modified = FALSE;
	var $thefile = false;
	var $data = false;
	var $sql_rowset = array();
	
	function cache()
	{
		global $_CLASS, $MAIN_CFG, $config, $site_file_root;
		
		$this->cache_dir = $site_file_root.'cache/';
		
		if ($config = $this->get('config'))
		{
			$sql = 'SELECT * 
				FROM ' . CONFIG_TABLE . '
				WHERE is_dynamic = 1';
			$result = $_CLASS['db']->sql_query($sql);
		
			while ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				$config[$row['config_name']] = $row['config_value'];
			}
		}
		else
		{
			$config = $cached_config = array();
		
			$sql = 'SELECT * 
				FROM ' . CONFIG_TABLE;
			$result = $_CLASS['db']->sql_query($sql);
		
			while ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				if (!$row['is_dynamic'])
				{
					$cached_config[$row['config_name']] = $row['config_value'];
				}
		
				$config[$row['config_name']] = $row['config_value'];
			}
			$_CLASS['db']->sql_freeresult($result);
		
			$this->put('config', $cached_config);
			unset($cached_config);
		}
		
		if (!($MAIN_CFG = $this->get('main_cfg')))
		{
			$MAIN_CFG = array();
		
			$sql = 'SELECT * 
				FROM cms_config_custom';
			$result = $_CLASS['db']->sql_query($sql);
		
			while ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				$MAIN_CFG[$row['cfg_name']][$row['cfg_field']] = stripslashes($row['cfg_value']);
			}
			$_CLASS['db']->sql_freeresult($result);
		
			$this->put('main_cfg', $MAIN_CFG);
		}

		if ((time() - $config['cache_gc']) > $config['cache_last_gc'])
		{
			$this->tidy();
			set_config('cache_last_gc', time(), TRUE);
		}
		
	}

	function load($var_name)
	{
		global $phpEx;
		
		if (file_exists($this->cache_dir . "data_$var_name.$phpEx"))
		{
			require($this->cache_dir . "data_$var_name.$phpEx");
			
			if (time() > $this->var_expires[$var_name])
			{
				unset($this->var_expires[$var_name], $this->vars[$var_name]);
				return false;
			}
			
			if (empty($this->vars[$var_name]))
			{
				$this->vars[$var_name] = '';
			}
			
			return $this->vars[$var_name];
		}
		else
		{
			return false;
		}
	}

	function save() 
	{
		if (!is_array($this->is_modified))
		{
			return;
		}

		global $phpEx;
		
		foreach ($this->is_modified as $file => $expire)
		{
		
			$filedata = '<?php $this->vars[\''.$file.'\']=' . $this->format_array($this->vars[$file]) . ";\n\$this->var_expires['$file']=" . $expire . ' ?>';
	
			if ($fp = @fopen($this->cache_dir . "data_$file.$phpEx", 'wb'))
			{
				@flock($fp, LOCK_EX);
				fwrite($fp, $filedata);
				@flock($fp, LOCK_UN);
				fclose($fp);
			}	
		}
		
		unset($this->vars);
		unset($this->var_expires);
		unset($this->sql_rowset);
		$this->is_modified = false;
	}
	
	function get($var_name)
	{
		
		if (empty($this->vars[$var_name]))
		{
			return $this->load($var_name);
		}
		
		return $this->vars[$var_name];
	}
	
	function exists($var_name)
	{
		global $phpEx;
		if (file_exists($this->cache_dir . "data_$var_name.$phpEx"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function put($var_name, $var, $ttl = 31536000)
	{
		if ($var_name{0} == '_')
		{
			global $phpEx;

			if ($fp = @fopen($this->cache_dir . 'data' . $var_name . ".$phpEx", 'wb'))
			{
				@flock($fp, LOCK_EX);
				fwrite($fp, "<?php\n\$expired = (time() > " . (time() + $ttl) . ") ? TRUE : FALSE;\nif (\$expired) { return; }\n\n\$data = unserialize('" . str_replace("'", "\\'", str_replace('\\', '\\\\', serialize($var))) . "');\n?>");
				@flock($fp, LOCK_UN);
				fclose($fp);
			}
		}
		else
		{
			$this->vars[$var_name] = $var;
			$this->is_modified[$var_name] = time() + $ttl;
		}
	}
	
	function tidy()
	{
		global $phpEx;

		$dir = opendir($this->cache_dir);
		
		while ($entry = readdir($dir))
		{
			if (eregi('sql_', $entry))
			{
				$expired = TRUE;
				include($this->cache_dir . $entry);
				if ($expired)
				{
					unlink($this->cache_dir . $entry);
				}
			}
			elseif (eregi('data_', $entry))
			{
				$var_name = preg_replace(array('/data_/', '/.php/'), '', $entry);
				
				if (empty($this->var_expires[$var_name]))
				{
					include($this->cache_dir . $entry);
				}
				
				if (time() > $this->var_expires[$var_name])
				{
					unlink($this->cache_dir . $entry);
				}
			}

		}
		
		closedir($dir);
	}

	function destroy($var_name, $table = '')
	{
		global $phpEx;

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
			@closedir($dir);
		}
		elseif ($var_name{0} == '_')
		{
			@unlink($this->cache_dir . 'data' . $var_name . ".$phpEx");
		}
		elseif (isset($this->vars[$var_name]))
		{
			@unlink($this->cache_dir . 'data_' . $var_name . ".$phpEx");
			unset($this->vars[$var_name]);
			unset($this->var_expires[$var_name]);
		}
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

	function sql_load($query)
	{
		global $phpEx;

		// Remove extra spaces and tabs
		$query = preg_replace('/[\n\r\s\t]+/', ' ', $query);
		$query_id = 'Cache id #' . count($this->sql_rowset);

		if (!file_exists($this->cache_dir . 'sql_' . md5($query) . ".$phpEx"))
		{
			return false;
		}

		@include($this->cache_dir . 'sql_' . md5($query) . ".$phpEx");

		if (!isset($expired))
		{
			return FALSE;
		}
		elseif ($expired)
		{
			unlink($this->cache_dir . 'sql_' . md5($query) . ".$phpEx");
			return FALSE;
		}

		return $query_id;
	}

	function sql_save($query, &$query_result, $ttl)
	{
		global $db, $phpEx;

		// Remove extra spaces and tabs
		$query = preg_replace('/[\n\r\s\t]+/', ' ', $query);

		if ($fp = @fopen($this->cache_dir . 'sql_' . md5($query) . '.' . $phpEx, 'wb'))
		{
			@flock($fp, LOCK_EX);

			$lines = array();
			$query_id = 'Cache id #' . count($this->sql_rowset);
			$this->sql_rowset[$query_id] = array();

			while ($row = $db->sql_fetchrow($query_result))
			{
				$this->sql_rowset[$query_id][] = $row;

				$lines[] = "unserialize('" . str_replace("'", "\\'", str_replace('\\', '\\\\', serialize($row))) . "')";
			}
			$db->sql_freeresult($query_result);

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