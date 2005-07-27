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

class db_mysql
{
	var $link_identifier;
	var $db_layer = 'mysql';

	var $query_result;
	var $return_on_error = false;
	var $transaction = false;

	var $queries_time = 0;
	var $num_queries = 0;

	var	$querylist = array();
	var $querydetails = array();
	var $open_queries = array();

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

		$this->link_identifier = ($db['persistency']) ? @mysql_pconnect($db['server'], $db['username'], $db['password']) : @mysql_connect($db['server'], $db['username'], $db['password']);

		if ($this->link_identifier)
		{
			if (@mysql_select_db($db['database']))
			{
				return $this->link_identifier;
			}

			$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB2</center>';
		}
		
		if (!$error)
		{
			$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB1</center>';
		}

		trigger_error($error, E_USER_ERROR);
		die;
	}

	function disconnect()
	{
		if (!$this->link_identifier)
		{
			return;
		}

		@mysql_close($this->link_identifier);
		$this->link_identifier = false;
	}

	function sql_return_on_error($fail = false)
	{
		$this->return_on_error = $fail;
	}

	function transaction($option = 'start')
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

				$result = mysql_query('START TRANSACTION', $this->link_identifier);
				$this->in_transaction = true;
			break;

			case 'commit':
			
				if (!$this->in_transaction)
				{
					break;
				}

				$result = mysql_query('COMMIT', $this->link_identifier);
				
				if (!$result)
				{
					mysql_query('ROLLBACK', $this->link_identifier);
				}
				
				$this->in_transaction = false;
			break;

			case 'rollback':
				if (!$this->in_transaction)
				{
					break;
				}

				$result = mysql_query('ROLLBACK', $this->link_identifier);
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
		elseif (strpos($query, 'SELECT') !== false)
		{
			$this->open_queries[(int) $this->last_result] = $this->last_result;
		}

		return $this->last_result;
	}

	function sql_query($query = false)
	{
		return @mysql_query($query, $this->link_identifier);
	}

	function query_limit($query = false, $total = false, $offset = 0, $backtrace = false) 
	{
		if (!$query || !$total || !$this->link_identifier) 
		{
			return false; 
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

	/*
		Boy do I like this but it phpBB's, may have to do something similar
	*/
	function sql_build_array($query, $assoc_ary = false)
	{
		if (!is_array($assoc_ary))
		{
			return false;
		}

		$fields = array();
		$values = array();

		if ($query == 'INSERT')
		{
			foreach ($assoc_ary as $key => $var)
			{
				$fields[] = $key;

				if (is_null($var))
				{
					$values[] = 'NULL';
				}
				elseif (is_string($var))
				{
					$values[] = "'" . $this->escape($var) . "'";
				}
				else
				{
					$values[] = (is_bool($var)) ? intval($var) : $var;
				}
			}

			$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		}
		else if ($query == 'UPDATE' || $query == 'SELECT')
		{
			$values = array();
			foreach ($assoc_ary as $key => $var)
			{
				if (is_null($var))
				{
					$values[] = "$key = NULL";
				}
				elseif (is_string($var))
				{
					$values[] = "$key = '" . $this->escape($var) . "'";
				}
				else
				{
					$values[] = (is_bool($var)) ? "$key = " . intval($var) : "$key = $var";
				}
			}
			$query = implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
		}

		return $query;
	}

	function num_rows($query_id = false)
	{
		if (!$query_id || !$this->link_identifier) 
		{ 
			return 0; 
		}

		return mysql_num_rows($query_id);
	}

	function affected_rows()
	{
		if (!$this->link_identifier)
		{ 
			return 0; 
		}

		$num = mysql_affected_rows($this->link_identifier);

		return (!$num || $num == -1) ? 0 : $num;
	}

	function fetch_row_assoc($query_id = false)
	{
		global $_CLASS;

		if (!$query_id || !$this->link_identifier)
		{
			return false;
		}

		return @mysql_fetch_assoc($query_id);
	}

	function fetch_row_num($query_id = false)
	{
		if (!$query_id || !$this->link_identifier)
		{
			return false;
		}

		return @mysql_fetch_row($query_id);
	}

	function fetch_row_both($query_id = false)
	{
		if (!$query_id || !$this->link_identifier)
		{
			return false;
		}

		return @mysql_fetch_array($query_id);
	}
	
	function insert_id()
	{
		return ($this->link_identifier) ? @mysql_insert_id($this->link_identifier) : false;
	}

	function free_result($query_id = false)
	{
		if (!$query_id || !isset($this->open_queries[(int) $query_id]) || !$this->link_identifier) 
		{ 
			return false; 
		}

		unset($this->open_queries[(int) $query_id]);

		return @mysql_free_result($query_id);
	}

	function escape($text)
	{
		if (function_exists('mysql_real_escape_string') && $this->link_identifier)
		{
			return mysql_real_escape_string($text, $this->link_identifier);
		}
		else
		{
			return mysql_escape_string($text);
		}
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

			$this->sql_freeresult($result);
		}

		if ($table)
		{
			$this->query('OPTIMIZE TABLE '. $table);
		}
	}

	function _error($sql = '', $backtrace)
	{
		if (!$this->return_on_error)
		{
			return;
		}

		$message = '<u>SQL ERROR</u> [ ' . SQL_LAYER . ' ]<br /><br />' . @mysql_error() . '<br /><br />File:<br/><br/>'.$backtrace['file'].'<br /><br />Line:<br /><br />'.$backtrace['line'].'<br /><br /><u>CALLING PAGE</u><br /><br />'.(($sql) ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br />';

		if ($this->transaction)
		{
			$this->sql_transaction('rollback');
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
					if (preg_match('/UPDATE ([a-z0-9_]+).*?WHERE(.*)/s', $this->last_query, $m))
					{
						$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
					}
					elseif (preg_match('/DELETE FROM ([a-z0-9_]+).*?WHERE(.*)/s', $this->last_query, $m))
					{
						$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
					}
					else
					{
						$explain_query = $this->last_query;
					}

					if (preg_match('/^SELECT/', $explain_query))
					{
						if ($result = mysql_query("EXPLAIN $explain_query", $this->link_identifier))
						{
							while ($row = mysql_fetch_assoc($result))
							{
								$this->querydetails[$this->num_queries][] = $row;
							}
						}
					}
					else
					{
						$this->querydetails[$this->num_queries][] = '';
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
							$affected = $this->affected_rows($this->query_result);
						}
						
						$this->querylist[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'affected' => $affected, 'time' => ($end_time - $start_time));
					}
					else
					{
						$error = mysql_error();
						$this->querylist[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'error'=> $error['code'], 'errorcode' => $error['message']);
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
				$this->table_name = $name;
				$this->fields = array();
			break;

			case 'commit':
			case 'return':
				if (!$this->table_name)
				{
					return;
				}

				$fields = implode(", \n", $this->fields);
				if ($indexs = implode(", \n", $this->indexs))
				{
					$fields .= ", \n";
				}

				$table = 'CREATE TABLE '.$this->table_name." ( \n" .$fields. $indexs ." \n ) ENGINE=MyISAM;";

				if ($option == 'return')
				{
					return $table;
				}

				$this->sql_query($table);

			case 'cancel':
				$this->table_name = false;
				$this->fields = array();
			break;
		}
	}

	function add_table_field_int($name, $number_min, $number_max = false, $default = 0, $auto_increment = false)
	{
		$length = max(strlen($number_min), strlen($number_max));

		if ($number_min >= -0 && $number_max <= 255)
		{
			// TINYINT UNSIGNED ( 0 to 255 )
			$this->fields[$name] =  "`$name` TINYINT($length) UNSIGNED";
		}
		elseif ($number_min >= -128 && $number_max <= 128)
		{
			// TINYINT ( -128 to 127 )
			$this->fields[$name] =  "`$name` TINYINT($length) DEFAULT";
		}
		elseif ($number_min >= 0 && $number_max <= 65535)
		{
			// SMALLINT UNSIGNED ( 0 to 65,535 )
			$this->fields[$name] =  "`$name` SMALLINT($length) UNSIGNED";
		}
		elseif ($number_min >= -32768 && $number_max <= 32767)
		{
			// SMALLINT ( -32,768 to 32,767 )
			$this->fields[$name] =  "`$name` SMALLINT($length)";
		}
		elseif ($number_min >= 0 && $number_max <= 16777215)
		{
			// MEDIUMINT UNSIGNED ( 0 to 16,777,215 )
			$this->fields[$name] =  "`$name` MEDIUMINT($length) UNSIGNED";
		}
		elseif ($number_min >= -8388608 && $number_max <= 8388607)
		{
			// MEDIUMINT ( -8,388,608 to 8,388,607 )
			$this->fields[$name] =  "`$name` MEDIUMINT($length)";
		}
		elseif ($number_min >= -2147483647 && $number_max <= 2147483647)
		{
			// INT ( -2,147,483,647 to 2,147,483,647 )
			$this->fields[$name] =  "`$name` INT($length)";
		}
		elseif ($number_min >= 0 && $number_max <= 4294967295) // we'll do this last
		{
			// INT UNSIGNED ( 0 to 4,294,967,295 )
			$this->fields[$name] =  "`$name` INT($length) UNSIGNED";
		}

		if ($auto_increment)
		{
			$this->fields[$name] .= ' auto_increment';
		}
		else
		{
			$this->fields[$name] .= " DEFAULT '".(int) $default."'";
		}
	}

	function add_table_field_text($name, $characters, $null = false)
	{
		// Add null
		if ($characters <= 255)
		{
			// TINYTEXT 1 to 255 Characters
			$this->fields[$name] =  "`$name` TINYTEXT";
		}
		elseif ($characters <= 65535)
		{
			// TEXT 1 to 65535 Characters
			$this->fields[$name] =  "`$name` TEXT";
		}
		elseif ($characters <= 16777215)
		{
			// MEDIUMTEXT 1 to 16,777,215 Characters
			$this->fields[$name] =  "`$name` MEDIUMTEXT";
		}
		elseif ($characters <= 4294967295)
		{
			// LONGTEXT 1 to 4,294,967,295 Characters
			$this->fields[$name] =  "`$name` LONGTEXT";
		}

		$this->fields[$name] .= ($null) ? " NULL" : " NOT NULL";
	}
	
	function add_table_field_char($name, $characters, $default = '', $padded = false)
	{
		if ($padded)
		{
			$this->fields[$name] =  "`$name` CHAR($characters)";
		}
		else
		{
			$this->fields[$name] =  "`$name` VARCHAR($characters)";
		}

		if (is_null($default))
		{
			$this->fields[$name] .= " NULL";
		}
		else
		{
			$this->fields[$name] .= " NOT NULL DEFAULT '$default'";  //NOT NULL "is it needed" ?
		}
	}

	function add_table_index($field, $type  = 'index', $index_name = false)
	{
		$index_name = ($index_name) ? $index_name : $field;

		if (empty($this->fields[$field]))
		{
			return;
		}

		switch ($type)
		{
			case 'index':
			case 'unique':
				$this->indexs[$index_name] = (($type == 'UNIQUE') ? 'UNIQUE ' : '') . "KEY `$index_name` (`$field`)";
			break;

			case 'primary':
				$this->indexs['primary'] = "PRIMARY KEY (`$field`)";
			break;
		}
	}
}

?>