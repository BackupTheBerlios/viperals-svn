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

$Id$
*/

class db_mysqli
{
	var $link_identifier = false;
	var $db_layer = 'mysqli';

	var $last_result;
	var $return_on_error;
	var $in_transaction;

	var $queries_time = 0;
	var $num_queries = 0;

	var	$query_list = array();
	var $query_details = array();
	var $open_queries = array();

	var $_indexs = array();
	var $_fields = array();
	var $_table_name = false;

	function connect($db)
	{		
		if ($db['port'])
		{
			$db['server'] .= ':' . $port;
		}

		if ($this->link_identifier)
		{
			$this->disconnect();
		}

		$this->link_identifier = ($db['persistent']) ? @mysqli_pconnect($db['server'], $db['username'], $db['password']) : @mysqli_connect($db['server'], $db['username'], $db['password']);

		if ($this->link_identifier)
		{
			if (@mysqli_select_db($this->link_identifier, $db['database']))
			{
				mysqli_query($this->link_identifier, 'SET NAMES utf8');

				//mysqli_query($this->link_identifier, 'SET character_set_results = NULL');
				//mysqli_set_charset($this->link_identifier, "utf8");

				return $this->link_identifier;
			}

			$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB2</center>';
		}
		
		if (!$error)
		{
			$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB1</center>';
		}

		$this->disconnect();

		trigger_error($error, E_USER_ERROR);
		die;
	}

	function disconnect()
	{
		if (!$this->link_identifier)
		{
			return;
		}

		@mysqli_close($this->link_identifier);
		$this->link_identifier = false;
	}

	function sql_return_on_error($fail = false)
	{
		$this->return_on_error = $fail;
	}

	function transaction($option = 'start', $auto_rollback = true)
	{
		if (!$this->link_identifier)
		{
			return;
		}

		$result = false;

		switch ($option)
		{
			case 'start':
				if ($this->in_transaction)
				{
					break;
				}

				mysqli_autocommit($this->link_identifier, false);
				$this->in_transaction = true;
			break;

			case 'commit':
			
				if (!$this->in_transaction)
				{
					break;
				}

				$result = mysqli_commit($this->link_identifier);

				if (!$result && $auto_rollback)
				{
					mysqli_rollback($this->link_identifier);
				}

				mysqli_autocommit($this->link_identifier, true);
				$this->in_transaction = false;
			break;

			case 'rollback':
				if (!$this->in_transaction)
				{
					break;
				}

				$result = mysqli_rollback($this->link_identifier);

				mysqli_autocommit($this->link_identifier, true);
				$this->in_transaction = false;
			break;
		}

		return $result;
	}

	function query($query = false,  $backtrace = false)
	{
		if (!$query || !$this->link_identifier) 
		{ 
			return false; 
		}

		global $_CLASS, $site_file_root;

		$this->num_queries++;
		$this->last_query = $query;

		if (!$backtrace)
		{
			$debug_backtrace = debug_backtrace();
			$backtrace = array();
			// remove the root directorys
			$backtrace['file'] = str_replace('\\','/', $debug_backtrace[0]['file']);
			$backtrace['file'] = str_replace($site_file_root, '', str_replace($_SERVER['DOCUMENT_ROOT'],'', $backtrace['file']));

			$backtrace['line'] = $debug_backtrace[0]['line'];
		}

		$this->_debug('start', $backtrace);
		$this->last_result = $this->sql_query($query);
		$this->_debug('stop', $backtrace);

		if ($this->last_result === false)
		{
			$this->_error($query, $backtrace);
		}
		elseif (strpos($query, 'SELECT') === false)
		{
			$this->open_queries[(string) $this->last_result] = $this->last_result;
		}

		return $this->last_result;
	}

	function query_limit($query = false, $total = false, $offset = 0, $backtrace = false) 
	{
		if (!$query || !$total || !$this->link_identifier) 
		{
			// no need to check for query or link_id, it's checked in db::query()
			return $this->query($query);
		}

		global $site_file_root;

		$query .= ' LIMIT ' . (($offset) ? $offset . ', ' : '') . $total;

		if (!$backtrace)
		{
			$debug_backtrace = debug_backtrace();
			$backtrace = array();
			// remove the root directorys
			$backtrace['file'] = str_replace('\\','/', $debug_backtrace[0]['file']);
			$backtrace['file'] = str_replace($site_file_root, '', str_replace($_SERVER['DOCUMENT_ROOT'],'', $backtrace['file']));

			$backtrace['line'] = $debug_backtrace[0]['line'];
		}

		return $this->query($query, $backtrace);
	}

	function sql_query($query = false)
	{
		return mysqli_query($this->link_identifier, $query);
	}

	/*
		Boy do I like this but it phpBB's, may have to do something similar
	*/
	function sql_build_array($query, $array = false)
	{
		if (!is_array($array))
		{
			return false;
		}

		$_fields = $values = array();

		if ($query == 'INSERT')
		{
			foreach ($array as $key => $value)
			{
				$_fields[] = $key;

				if (is_numeric($value))
				{
					$values[] = $value;
				}
				elseif (is_string($value))
				{
					$values[] = "'" . $this->escape($value) . "'";
				}
				elseif (is_null($value))
				{
					$values[] = 'NULL';
				}
				elseif (is_bool($value))
				{
					$values[] = ($value) ? 1 : 0;
				}
			}

			return ' (' . implode(', ', $_fields) . ') VALUES (' . implode(', ', $values) . ')';
		}
		elseif ($query == 'UPDATE' || $query == 'SELECT')
		{
			$values = array();
			foreach ($array as $key => $value)
			{
				if (is_numeric($value))
				{
					$values[] = $key.' = '.$value;
				}
				elseif (is_string($value))
				{
					$values[] = "$key = '" . $this->escape($value) . "'";
				}
				elseif (is_null($value))
				{
					$values[] = "$key = NULL";
				}
				elseif (is_bool($value))
				{
					$values[] = $key.' = '.(($value) ? 1 : 0);
				}

			}

			return implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
		}
	}

	function num_rows($result = false)
	{
		if (!$result || !$this->link_identifier) 
		{ 
			return 0; 
		}

		return mysqli_num_rows($result);
	}

	function affected_rows()
	{
		if (!$this->link_identifier)
		{ 
			return 0; 
		}

		$num = mysqli_affected_rows($this->link_identifier);

		return (!$num || $num == -1) ? 0 : $num;
	}

	function fetch_row_assoc($result = false)
	{
		global $_CLASS;

		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @mysqli_fetch_assoc($result);
	}

	function fetch_row_num($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @mysqli_fetch_row($result);
	}

	function fetch_row_both($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @mysqli_fetch_array($result);
	}

	function insert_id($table, $column)
	{
		return ($this->link_identifier) ? @mysqli_insert_id($this->link_identifier) : false;
	}

	function free_result($result = false)
	{
		if (!$result || !isset($this->open_queries[(string) $result]) || !$this->link_identifier) 
		{ 
			return false; 
		}

		unset($this->open_queries[(string) $result]);

		return @mysqli_free_result($result);
	}

	function escape($text)
	{
		if (!$this->link_identifier)
		{ 
			return false; 
		}
		
		return mysqli_real_escape_string($this->link_identifier, $text);
	}

	function escape_array($value)
	{
		if (!$this->link_identifier)
		{ 
			return false; 
		}

		return preg_replace('#(.*?)#e', "\$this->escape('\\1')", $value);
	}

	function optimize_tables($table = '')
	{
		global $_CORE_CONFIG;
	
		if ($table)
		{
			if (is_array($table))
			{
				$table = implode(',', $table);
			}
		}
		else
		{
			$result = $this->query('SHOW TABLES');

			while ($row = $this->fetch_row_num($result))
			{
				if ($table)
				{
					$table .= ', ' . $row[0];
				}
			}

			$this->free_result($result);
		}

		if ($table)
		{
			$this->query('OPTIMIZE TABLE '. $table);
		}
	}

	function version()
	{
		if (!$this->link_identifier)
		{
			return false;
		}

		return mysqli_get_server_info($this->link_identifier);
	}
	
	function _error($sql = '', $backtrace)
	{
		if ($this->return_on_error)
		{
			return;
		}

		$message = '<u>SQL ERROR</u><br /><br />' . @mysqli_error($this->link_identifier) . '<br /><br />File: <br/>'.$backtrace['file'].'<br/><br />Line:<br/>'.$backtrace['line'].'<br /><br /><u>SQL</u><br /><br />' . $sql .'<br />';

		if ($this->in_transaction)
		{
			$this->transaction('rollback');
		}

		trigger_error($message, E_USER_ERROR);
	}

	function _debug($mode, $backtrace)
	{
		global $_CLASS, $_CORE_CONFIG;
		static $start_time;

		if (!$this->link_identifier) 
		{ 
			return false; 
		}

		switch ($mode)
		{
			case 'start':
				if (empty($_CORE_CONFIG['global']['error']) || $_CORE_CONFIG['global']['error'] == ERROR_DEBUGGER)
				{
					if (strpos('SELECT', $this->last_query) === 0)
					{
						if ($result = @mysqli_query($this->link_identifier, 'EXPLAIN '.$this->last_query))
						{
							while ($row = @mysqli_fetch_assoc($result))
							{
								$this->query_details[$this->num_queries][] = $row;
							}
						}
					}
					else
					{
						$this->query_details[$this->num_queries][] = '';
					}
				}

				$start_time = explode(' ', microtime());
				$start_time = $start_time[0] + $start_time[1];
			break;

			case 'stop':
				global $site_file_root;

				$end_time = explode(' ', microtime());
				$end_time = $end_time[0] + $end_time[1];
				$this->queries_time += $end_time - $start_time;

				if (empty($_CORE_CONFIG['global']['error']) || $_CORE_CONFIG['global']['error'] == ERROR_DEBUGGER)
				{
					if ($this->last_result !== false)
					{
						$affected = false;

						if (preg_match('/^(UPDATE|DELETE|REPLACE)/', $this->last_query))
						{
							$affected = $this->affected_rows($this->last_result);
						}
						
						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'affected' => $affected, 'time' => ($end_time - $start_time));
					}
					else
					{
						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'error'=> mysqli_error($this->link_identifier));
					}
				}
			break;
		}
	}

	/*
		Table creation
	*/
	function table_create($option, $name = false)
	{
		switch ($option)
		{
			case 'start':
				$this->_table_name = $name;
				$this->_fields = $this->_indexs = array();
			break;

			case 'commit':
			case 'return':
				if (!$this->_table_name)
				{
					return;
				}

				$_fields = implode(", \n", $this->_fields);
				if ($_indexs = implode(", \n", $this->_indexs))
				{
					$_fields .= ", \n";
				}

				$table = 'CREATE TABLE '.$this->_table_name." ( \n" .$_fields. $_indexs ." \n ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";
				// Let users choose transaction safe InnoDB or MyISAM
				// ENGINE=MyISAM
				if ($option == 'return')
				{
					return $table;
				}

				$this->sql_query($table);

			case 'cancel':
				$this->_table_name = false;
				$this->_fields = $this->_indexs = array();
			break;
		}
	}

	function add_table_field_int($name, $setting_sent)
	{
		$setting = array('default' => null, 'min' => 0, 'max' => 0, 'auto_increment' => false, 'null' => false);
		$setting = array_merge($setting, $setting_sent);

		$length = max(strlen($setting['min']), strlen($setting['max']));

		if ($setting['min'] >= 0 && $setting['max'] <= 255)
		{
			// TINYINT UNSIGNED ( 0 to 255 )
			$this->_fields[$name] =  "`$name` TINYINT($length) UNSIGNED";
		}
		elseif ($setting['min'] >= -128 && $setting['max'] <= 128)
		{
			// TINYINT ( -128 to 127 )
			$this->_fields[$name] =  "`$name` TINYINT($length) DEFAULT";
		}
		elseif ($setting['min'] >= 0 && $setting['max'] <= 65535)
		{
			// SMALLINT UNSIGNED ( 0 to 65,535 )
			$this->_fields[$name] =  "`$name` SMALLINT($length) UNSIGNED";
		}
		elseif ($setting['min'] >= -32768 && $setting['max'] <= 32767)
		{
			// SMALLINT ( -32,768 to 32,767 )
			$this->_fields[$name] =  "`$name` SMALLINT($length)";
		}
		elseif ($setting['min'] >= 0 && $setting['max'] <= 16777215)
		{
			// MEDIUMINT UNSIGNED ( 0 to 16,777,215 )
			$this->_fields[$name] =  "`$name` MEDIUMINT($length) UNSIGNED";
		}
		elseif ($setting['min'] >= -8388608 && $setting['max'] <= 8388607)
		{
			// MEDIUMINT ( -8,388,608 to 8,388,607 )
			$this->_fields[$name] =  "`$name` MEDIUMINT($length)";
		}
		elseif ($setting['min'] >= -2147483647 && $setting['max'] <= 2147483647)
		{
			// INT ( -2,147,483,647 to 2,147,483,647 )
			$this->_fields[$name] =  "`$name` INT($length)";
		}
		elseif ($setting['min'] >= 0 && $setting['max'] <= 4294967295) // we'll do this last
		{
			// INT UNSIGNED ( 0 to 4,294,967,295 )
			$this->_fields[$name] =  "`$name` INT($length) UNSIGNED";
		}

		if ($setting['auto_increment'])
		{
			$this->_fields[$name] .= ' auto_increment';
		}
		else
		{
			$this->_fields[$name] .= ($setting['null']) ? " NULL" : " NOT NULL";
			$this->_fields[$name] .= is_null($setting['default']) ? '' : " DEFAULT '".(int) $setting['default']."'";
		}
	}

	function add_table_field_text($name, $characters, $null = false)
	{
		if ($characters <= 255)
		{
			// TINYTEXT 1 to 255 Characters
			$this->_fields[$name] =  "`$name` TINYTEXT";
		}
		elseif ($characters <= 65535)
		{
			// TEXT 1 to 65535 Characters
			$this->_fields[$name] =  "`$name` TEXT";
		}
		elseif ($characters <= 16777215)
		{
			// MEDIUMTEXT 1 to 16,777,215 Characters
			$this->_fields[$name] =  "`$name` MEDIUMTEXT";
		}
		elseif ($characters <= 4294967295)
		{
			// LONGTEXT 1 to 4,294,967,295 Characters
			$this->_fields[$name] =  "`$name` LONGTEXT";
		}

		$this->_fields[$name] .= ($null) ? " NULL" : " NOT NULL";
	}

	function add_table_field_char($name, $characters, $null = false, $default = null, $padded = false)
	{
		$this->_fields[$name] = ($padded) ? "`$name` CHAR($characters)" :  "`$name` VARCHAR($characters)";
		$this->_fields[$name] .= ($null) ? " NULL" : " NOT NULL";
		$this->_fields[$name] .= is_null($default) ? '' : "DEFAULT '$default'";
	}

	function add_table_index($field, $type  = 'index', $index_name = false)
	{
		$index_name = $index_name ? $index_name : $field;
		
		$field = is_array($field) ? implode('` , `', $field) : $field;

		switch ($type)
		{
			case 'index':
			case 'unique':
				$this->_indexs[$index_name] = (($type == 'UNIQUE') ? 'UNIQUE ' : '') . "KEY `$index_name` (`$field`)";
			break;

			case 'primary':
				$this->_indexs['primary'] = "PRIMARY KEY (`$field`)";
			break;
		}
	}
}

?>