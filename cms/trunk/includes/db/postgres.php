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

class db_postgres
{
	var $link_identifier;
	var $db_layer = 'postgre';

	var $last_result;
	var $report_error = true;
	var $in_transaction = false;

	var $queries_time = 0;
	var $num_queries = 0;

	var	$query_list = array();
	var $query_details = array();
	var $open_queries = array();

	var $_indexs = array();
	var $_fields = array();
	var $_table_name;
	var $_table_oid;

	function connect($db)
	{		
		$connection_string = array();

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

		if ($db['database'])
		{
			$connection_string[] = 'dbname='.$db['database'];
		}

		if ($this->link_identifier)
		{
			$this->disconnect();
		}

		$connection_string = implode(' ', $connection_string);

		$this->link_identifier = ($db['persistent']) ? @pg_pconnect($connection_string) : @pg_connect($connection_string);

		if ($this->link_identifier)
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
			{
				pg_set_client_encoding($this->link_identifier, 'UNICODE');
				//pg_set_client_encoding($this->link_identifier, 'UTF8');  pg8.1 unicode still works tho
				//pg_query($this->link_identifier, "SET CLIENT_ENCODING TO 'UNICODE'"); SET NAMES 'UNICODE'
			}

			return $this->link_identifier;
		}

		if (!$this->report_error)
		{
			return false;
		}

		$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB1</center>';

		$this->disconnect();

		trigger_error($error, E_USER_ERROR);
	}

	function disconnect()
	{
		if (!$this->link_identifier)
		{
			return;
		}

		pg_close($this->link_identifier);
		$this->link_identifier = false;
	}

	function report_error($report)
	{
		$this->report_error = ($report);
	}

	function transaction($option = 'start', $auto_rollback = true)
	{
		$result = false;
		
		switch ($option)
		{
			case 'start':
				if ($this->in_transaction)
				{
					break;
				}

				$result = pg_query($this->link_identifier, 'START TRANSACTION');
				$this->in_transaction = true;
			break;

			case 'commit':
			
				if (!$this->in_transaction)
				{
					break;
				}

				$result = pg_query($this->link_identifier, 'COMMIT');
				
				if (!$result && $auto_rollback)
				{
					pg_query($this->link_identifier, 'ROLLBACK');
				}
				
				$this->in_transaction = false;
			break;

			case 'rollback':
				if (!$this->in_transaction)
				{
					break;
				}

				$result = pg_query($this->link_identifier, 'ROLLBACK');
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
			$this->sql_error($backtrace);
		}
		elseif (strpos($query, 'SELECT') === 0)
		{
			$this->open_queries[(int) $this->last_result] = $this->last_result;
		}

		return $this->last_result;
	}

	function sql_query($query = false)
	{
		return pg_query($this->link_identifier, $query);
	}

	function query_limit($query = false, $total = false, $offset = 0, $backtrace = false) 
	{
		if (!$query || !$total || !$this->link_identifier) 
		{
			// no need to check for query or link_id, it's checked in db::query()
			return $this->query($query);
		}

		global $site_file_root;

		$query .= ' LIMIT '. $total . (($offset) ? ' OFFSET '.$offset : '');

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

	function sql_query_build($query_type, $array, $table)
	{
		if (!is_array($array) || !$table)
		{
			return false;
		}

		$this->num_queries++;
		$this->last_query = '';

		SWitch (strtoupper($query_type))
		{
			case 'MULTI_INSERT':
				foreach ($array as $array2)
				{
					pg_insert($this->link_identifier, $table, $array2);
				}
			break;

			case 'INSERT':
				//	return pg_insert($this->link_identifier, $table, $array);
				// We can't use pg_insert if we need needed to get the insert_id
				$this->last_query = 'INSERT INTO ' . $table . ' '. $this->sql_build_array('INSERT', $array);
				$this->last_result = $this->query($this->last_query);
	
				return $this->last_result;
			break;

			case 'UPDATE':
				return pg_update($this->link_identifier, $table, $array);
				//return $this->query('UPDATE ' . $table . ' SET ' . $array);
			break;
		}
	}

	/*
		Boy do I like this but it phpBB's
	*/
	function sql_build_array($query_type, $array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$_fields = $values = array();

		SWitch  ($query_type)
		{
			case 'INSERT':
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
			break;

			case 'MULTI_INSERT':
				$ary = array();
				foreach ($array as $array2)
				{
					$values = array();
					foreach ($array2 as $key => $value)
					{
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
					$ary[] = '(' . implode(', ', $values) . ')';
				}
	
				return ' (' . implode(', ', array_keys($array[0])) . ') VALUES ' . implode(', ', $ary);
			break;

			case 'UPDATE':
			case 'SELECT':
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
	
				return implode(($query_type == 'UPDATE') ? ', ' : ' AND ', $values);
			break;
		}
		return false;
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

		return (int) $row;
	}

	function affected_rows()
	{
		if (!$this->link_identifier)
		{ 
			return 0; 
		}

		return (int) pg_affected_rows($this->last_result);
	}

	function fetch_row_assoc($result = false)
	{
		global $_CLASS;

		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return pg_fetch_assoc($result);
	}

	function fetch_row_num($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return pg_fetch_row($result);
	}

	function fetch_row_both($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return pg_fetch_array($result);
	}

	function insert_id($table, $column)
	{
		$oid = pg_last_oid($this->last_result);

		/* 
		Shouldn't be need, but incase they don't have oid support ( not sure why they wouldn't )
		They can fall back to the less acurrate method, totally not recommended for active sites

		if ($result = $this->query("SELECT last_value FROM pg_get_serial_sequence($table, $column)"))
		{
			$return = $this->fetch_row_assoc($result);
			$this->free_result($result);
			
			$return['last_value'];
		}
		*/

		if ($oid === false || !($result = $this->query("SELECT $column FROM $table WHERE oid = $oid")))
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

		return pg_free_result($result);
	}

	function escape($text)
	{
		return pg_escape_string($text);
	}

	function escape_array($value)
	{
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

			$this->query('VACUUM '. $table);
		}
		else
		{
			$result = $this->query('VACUUM');
		}
	}

	function sql_error($backtrace, $return = false)
	{
		if ($return)
		{
			return array(
				'message'	=> @pg_last_error($this->link_identifier),
				'code'		=> ''
			);
		}
		
		if (!$this->report_error)
		{
			return;
		}

		$message = '<u>SQL ERROR</u><br /><br />' . @pg_last_error($this->link_identifier) . '<br /><br />File: <br/>'.$backtrace['file'].'<br/><br />Line:<br/>'.$backtrace['line'].'<br /><br /><u>SQL</u><br /><br />' . $this->last_query .'<br />';

		if ($this->in_transaction)
		{
			$this->transaction('rollback');
		}

		trigger_error($message, E_USER_ERROR);
	}

	function version($return_dbname = false)
	{
		if (!$this->link_identifier)
		{
			return false;
		}

		if (function_exists('pg_version'))
		{
			$version = pg_version($this->link_identifier);

			if (isset($version['server']))
			{
				return $version['server'];
			}
		}

		$result = $this->query('SELECT VERSION() AS version');
		$row = $this->fetch_row_assoc($result);
		$this->free_result($result);

		if (!$row)
		{
			return;
		}

		if ($return_dbname)
		{	// should we return the full info ?
			return $row['version'];
		}
		else
		{
			$version = explode(' ', $row['version']);
			return $version[1];
		}
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
						if ($result = pg_query($this->link_identifier, 'EXPLAIN '.$this->last_query))
						{
							while ($row = pg_fetch_assoc($result))
							{
								$this->query_details[$this->num_queries][] = $row;
							}

							pg_free_result($result);
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
						$affected = preg_match('/^(UPDATE|DELETE|REPLACE)/', $this->last_query) ? $this->affected_rows() : 0;

						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'affected' => $affected, 'time' => ($end_time - $start_time));
					}
					else
					{
						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'error'=> pg_last_error($this->link_identifier));
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
				$this->_table_oid = false;
			break;

			case 'commit':
			case 'return':
				if (!$this->_table_name)
				{
					return;
				}

				$fields = implode(", \n", $this->_fields);

				if (isset($this->_indexs['primary']))
				{
					$fields .= ", \n".$this->_indexs['primary'];
					unset($this->_indexs['primary']);
				}
			
				$indexs = empty($this->_indexs) ? '' : "\n\n".implode("\n", $this->_indexs);

				$oid = ($this->_table_oid) ? 'WITH OIDS' : 'WITHOUT OIDS';

				$table = 'CREATE TABLE '.$this->_table_name." ( \n" .$fields." \n )\n $oid;$indexs";
//WITH ENCODING='UNICODE'  ( for database creation )
				if ($option == 'return')
				{
					return $table;
				}

				if (!$this->sql_query($table))
				{
					echo $table.'<br/>';
				}

			case 'cancel':
				$this->_table_name = $this->_table_oid = false;
				$this->_fields = $this->_indexs = array();
			break;
		}
	}

	function add_table_field_int($name, $setting_sent)
	{
		$setting = array('default' => null, 'min' => 0, 'max' => 0, 'auto_increment' => false, 'null' => false);
		$setting = array_merge($setting, $setting_sent);

		$length = max(strlen($setting['min']), strlen($setting['max']));

		if (!$setting['auto_increment'] && $setting['min'] >= -32768 && $setting['max'] <= 32767)
		{
			// SMALLINT -- INT2 ( -32,768 to 32,767 )
			$this->_fields[$name] =  "$name SMALLINT";
		}
		elseif ($setting['min'] >= -2147483648 && $setting['max'] <= 2147483647)
		{
			// INTEGER -- INT4 ( +auto_increment => SERIAL4 ) ( -2,147,483,648 to 2,147,483,647 )
			$this->_fields[$name] =   ($setting['auto_increment']) ? "$name SERIAL" : "$name INTEGER";
		}
		elseif ($setting['min'] >= -9223372036854775808 && $setting['max'] <= 9223372036854775807)
		{
			// BIGINT -- INT8 ( +auto_increment => SERIAL8 ) ( -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807 )
			$this->_fields[$name] =  ($setting['auto_increment']) ? "$name BIGSERIAL" : "$name BIGINT";
		}

		if ($setting['auto_increment'])
		{
			$this->_table_oid = true;
		}
		else
		{
			$this->_fields[$name] .= ($setting['null']) ? " NULL" : " NOT NULL";
			$this->_fields[$name] .= is_null($setting['default']) ? '' : " DEFAULT '".(int) $setting['default']."'";
		}
	}

	function add_table_field_text($name, $characters, $null = true)
	{
		$this->_fields[$name] =  "$name TEXT".(($null) ? " NULL" : " NOT NULL");
	}

	function add_table_field_char($name, $characters, $null = false, $default = null, $padded = false)
	{
		$this->_fields[$name] =  ($padded) ? "$name CHARACTER($characters)" : "$name CHARACTER VARYING($characters)";
		$this->_fields[$name] .= ($null) ? " NULL" : " NOT NULL";
		$this->_fields[$name] .= is_null($default) ? '' : "DEFAULT '$default'";
	}

	function add_table_index($field, $type  = 'index', $index_name = false)
	{
		$index_name = ($index_name) ? $index_name : $field;

		$index_name = is_array($index_name) ? implode('_', $index_name) : $index_name;
		$index_name = $this->_table_name . '_' . $index_name;

		$field = is_array($field) ? implode(', ', $field) : $field;

		switch ($type)
		{
			case 'index':
			case 'unique':
				$this->_indexs[$index_name] = (($type == 'UNIQUE') ? 'CREATE UNIQUE' : 'CREATE') . " INDEX $index_name ON {$this->_table_name} ($field);";
			break;

			case 'primary':
				$this->_indexs['primary'] = "PRIMARY KEY ($field)";
			break;
		}
	}
}

?>