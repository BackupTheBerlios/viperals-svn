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

class db_postgre
{
	var $link_identifier;
	var $db_layer = 'postgre';

	var $last_result;
	var $return_on_error;
	var $in_transaction;

	var $queries_time = 0;
	var $num_queries = 0;

	var	$query_list = array();
	var $query_details = array();
	var $open_queries = array();

	var $indexs = array();
	var $fields = array();
	var $table_name;
	var $table_oid;

	function connect($db)
	{		
		$connection_string = array();
//host, hostaddr, port, dbname, user, password, connect_timeout, options, tty
		if ($db['server'])
		{
			if ($db['port'])
			{
				$connection_string[] = 'port='.$db['port'];
			}

			$connection_string[] = 'host='.$db['server'];
		}

		if ($db['username'])
		{
			$connection_string[] = 'user='.$db['username'];
		}

		if ($db['password'])
		{
			$connection_string[] = 'password='.$db['password'];
		}

		$connection_string[] = 'dbname='.$db['database'];

		if ($this->link_identifier)
		{
			$this->disconnect();
		}

		$connection_string = explode(' ', $connection_string);

		$this->link_identifier = ($db['persistency']) ? @pg_pconnect($connection_string) : @pg_connect($connection_string);

		if ($this->link_identifier)
		{
			return $this->link_identifier;
		}

		$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB1</center>';
		trigger_error($error, E_USER_ERROR);

		die;
	}

	function disconnect()
	{
		if (!$this->link_identifier)
		{
			return;
		}

		@pg_close($this->link_identifier);
		$this->link_identifier = false;
	}

	function sql_return_on_error($fail = false)
	{
		$this->return_on_error = $fail;
	}

	function transaction($option = 'start')
	{
		$result = false;
		
		switch ($option)
		{
			case 'start':
				if ($this->in_transaction)
				{
					break;
				}

				$result = @pg_query($this->db_connect_id, 'START TRANSACTION');
				$this->in_transaction = true;
			break;

			case 'commit':
			
				if (!$this->in_transaction)
				{
					break;
				}

				$result = @pg_query($this->db_connect_id, 'COMMIT');
				
				if (!$result)
				{
					@pg_query($this->db_connect_id, 'ROLLBACK');
				}
				
				$this->in_transaction = false;
			break;

			case 'rollback':
				if (!$this->in_transaction)
				{
					break;
				}

				$result = @pg_query($this->db_connect_id, 'ROLLBACK');
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
			$this->_error($query);
		}
		elseif (strpos($query, 'SELECT') !== false)
		{
			$this->open_queries[(int) $this->last_result] = $this->last_result;
		}

		return $this->last_result;
	}

	function sql_query($query = false)
	{
		return @pg_query($this->link_identifier, $query);
	}

	function query_limit($query = false, $total = false, $offset = 0) 
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
		$query .= ' LIMIT '. $total . (($offset) ? ' OFFSET '.$offset : '');

		return $this->query($query, $backtrace);
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

		$fields = $values = array();

		if ($query == 'INSERT')
		{
			foreach ($array as $key => $value)
			{
				$fields[] = $key;

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

			return ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
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
				elseif (is_null($var))
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

		$row = pg_num_rows($result);

		if (!$row || $row == -1)
		{
			return 0;
		}

		return $row;
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

	function fetch_row_assoc($result = false)
	{
		global $_CLASS;

		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @pg_fetch_assoc($result);
	}

	function fetch_row_num($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @pg_fetch_row($result);
	}

	function fetch_row_both($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @pg_fetch_array($result);
	}

	function last_insert_id($table, $column)
	{
		//SELECT last_value FROM pg_get_serial_sequence('tablename','fieldname')
		$oid = @pg_last_oid($this->last_result);
	
		if ($oid === false || (!($result = $this->query("SELECT $column FROM $table WHERE oid = $oid")))
		{
			return false;
		}

		$return = $this->fetch_row_assoc($result);
		$this->free_result($result);
		
		return $return[$column];
	}

	function free_result($result = false)
	{
		if (!$result || !isset($this->open_queries[(int) $result]) || !$this->link_identifier) 
		{ 
			return false; 
		}

		unset($this->open_queries[(int) $result]);

		return @pg_free_result($result);
	}

	function escape($text)
	{
		return pg_escape_string($text);
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

			$this->query('VACUUM '. $table);
		}
		else
		{
			$result = $this->query('VACUUM');
		}
	}

	function _error($sql = '', $backtrace)
	{
		if ($this->return_on_error)
		{
			return;
		}

		$message = '<u>SQL ERROR</u><br /><br />' . @pg_last_error($this->link_identifier) . '<br /><br />File: <br/>'.$backtrace['file'].'<br/><br />Line:<br/>'.$backtrace['line'].'<br /><br /><u>SQL</u><br /><br />' . $sql .'<br />';

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
						if ($result = @pg_query("EXPLAIN $explain_query", $this->link_identifier))
						{
							while ($row = @pg_fetch_assoc($result))
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
				$indexs = implode(", \n", $this->indexs);
	

				$table = 'CREATE TABLE '.$this->table_name." ( \n" .$fields." \n )";

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

		if (!$auto_increment && $number_min >= -32768 && $number_max <= 32767)
		{
			// SMALLINT -- INT2 ( -32,768 to 32,767 )
			$this->fields[$name] =  "$name INT2";
		}
		elseif ($number_min >= -2147483648 && $number_max <= 2147483647)
		{
			// INTEGER -- INT4 ( -2,147,483,648 to 2,147,483,647 )
			$this->fields[$name] =   ($auto_increment) ? "$name SERIAL4" : "$name INT4";
		}
		elseif ($number_min >= 9223372036854775808 && $number_max <= 9223372036854775807)
		{
			// BIGINT -- INT8  ( 9,223,372,036,854,775,808 to 9,223,372,036,854,775,807 )
			$this->fields[$name] =  ($auto_increment) ? "$name SERIAL8" : "$name INT8";
		}


		if ($auto_increment)
		{
			$this->table_oid = true;
		}
		else
		{
			$this->fields[$name] .= "NOT NULL DEFAULT '".(int) $default."'";
		}
	}

	function add_table_field_text($name, $characters, $null = false)
	{
		$this->fields[$name] =  "$name TEXT".(($null) ? " NULL" : " NOT NULL");
		//$this->fields[$name] =  "$name TEXT DEFAULT '' ".(($null) ? " NULL" : " NOT NULL");
	}

	function add_table_field_char($name, $characters, $default = '', $padded = false)
	{
		if ($padded)
		{
			$this->fields[$name] =  "$name CHAR($characters)";
		}
		else
		{
			$this->fields[$name] =  "$name VARCHAR($characters)";
		}

		if (is_null($default))
		{
			$this->fields[$name] .= " NULL";
		}
		else
		{
			$this->fields[$name] .= " NOT NULL DEFAULT '$default'";
		}
	}

	function add_table_index($field, $type  = 'index', $index_name = false)
	{
		static $primary_key = false;

		$index_name = ($index_name) ? $index_name : $field;

		if (empty($this->fields[$field]))
		{
			return;
		}

		switch ($type)
		{
			case 'index':
			case 'unique':
				//CREATE INDEX a ON test USING btree (a)
				$this->indexs[$index_name] = (($type == 'UNIQUE') ? 'UNIQUE ' : '') . "INDEX $index_name ON {$this->table_name} ($field)";
			break;

			case 'primary':
				if ($primary_key)
				{
					//$this->fields[$primary_key] = strtr
				}
				$primary_key = $field;

				$this->fields[$field] .= ' PRIMARY KEY';

				//$this->indexs['primary'] = " UNIQUE INDEX {$this->table_name}_pkey ON {$this->table_name} ( $field )";
			break;
		}
	}
}

?>