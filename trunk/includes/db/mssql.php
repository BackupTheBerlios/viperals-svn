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
//																//
//**************************************************************//

class db_mssql
{
	var $link_identifier = false;
	var $db_layer = 'mssql';

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
	
	var $_limit_last_key = 0;
	var $_limit_result = array();

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

		$this->link_identifier = ($db['persistent']) ? mssql_pconnect($db['server'], $db['username'], $db['password']) : mssql_connect($db['server'], $db['username'], $db['password']);

		if ($this->link_identifier)
		{
			if (mssql_select_db($db['database']))
			{
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
	}

	function disconnect()
	{
		if (!$this->link_identifier)
		{
			return;
		}

		@mssql_close($this->link_identifier);
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

				$result = mysql_query('BEGIN  TRANSACTION', $this->link_identifier);
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
		return mssql_query($query, $this->link_identifier);
	}

	function query_limit($query = false, $total = false, $offset = 0, $backtrace = false) 
	{
		if (!$query || !$total || !$this->link_identifier) 
		{
			// no need to check for query or link_id, it's checked in db::query()
			return $this->query($query);; 
		}

		global $site_file_root;

		if (!$backtrace)
		{
			$debug_backtrace = debug_backtrace();
			$backtrace = array();
			// remove the root directorys
			$backtrace['file'] = str_replace('\\','/', $debug_backtrace[0]['file']);
			$backtrace['file'] = str_replace($site_file_root, '', str_replace($_SERVER['DOCUMENT_ROOT'],'', $backtrace['file']));

			$backtrace['line'] = $debug_backtrace[0]['line'];
		}

		$query = substr(trim($query));
		
		if (!$offset)
		{
			return $this->query("SELECT TOP($total) $query");
		}
		
		/*
			Welcome to hell

			We'll do two query and get the diffence in php to make sure things work right
			Other why needs us to know the a unique feild/key or primary key
		*/
		
		// here we go, get the all data up to the offset row. offset row = total need + offset
		$result = $this->query('SELECT TOP('.$total + $offset.") $query");

		// need to test dataseek, if it resets after the a fetch do mssql_next_result loop
		if (!$result || !mssql_data_seek($offset))
		{
			$this->free_result($result);

			return false;
		}

		/*
			change mssql_data_seek to mssql_num_rows < $offset above if this is used
			for ($i = 1; $i <= $offset; $i++)
			{
				mssql_next_result($result);
			}
		*/

		// we fetch array so both assoc and num can be used, May change later on

		$this->_limit_last_key ++;
		$result_id = 'LIMIT_'.$this->_limit_last_key;
		
		while ($row = mssql_fetch_array($result))
		{
			$this->_limit_result[$result_id][] = $row;
		}

		return $result_id;
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

	function num_rows($result = false)
	{
		if (!$result || !$this->link_identifier) 
		{ 
			return 0; 
		}

		if (strpos($result, 'LIMIT_') === 0)
		{
			return (empty($this->_limit_result[$result]) ? 0 : count($this->_limit_result[$result]));
		}

		return mssql_num_rows($result);
	}

	function affected_rows()
	{
		if (!$this->link_identifier)
		{ 
			return 0; 
		}

		$num = mssql_rows_affected($this->link_identifier);

		// not sure what it returns one fail, test maybe ?
		return (!$num || $num == -1) ? 0 : $num;
	}

	function fetch_row_assoc($result = false)
	{
		global $_CLASS;

		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		if (strpos($result, 'LIMIT_') === 0)
		{
			if (empty($this->_limit_result[$result]) || !($value = each($this->_limit_result[$result])))
			{
				return false;
			}
			return $value['value'];
		}

		return @mssql_fetch_assoc($result);
	}

	function fetch_row_num($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		if (strpos($result, 'LIMIT_') === 0)
		{
			if (empty($this->_limit_result[$result]) || !($value = each($this->_limit_result[$result])))
			{
				return false;
			}
			return $value['value'];
		}

		return @mssql_fetch_row($result);
	}

	function fetch_row_both($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		if (strpos($result, 'LIMIT_') === 0)
		{
			if (empty($this->_limit_result[$result]) || !($value = each($this->_limit_result[$result])))
			{
				return false;
			}
			return $value['value'];
		}

		return @mssql_fetch_array($result);
	}
	
	function insert_id()
	{
		if (!$this->link_identifier)
		{
			return false;
		}
		
		//@@IDENTITY
		if ($result = mssql_query('SELECT SCOPE_IDENTITY() AS Identity', $this->db_connect_id))
		{
			$row = mssql_fetch_assoc($result);
			mssql_free_result($result);
		}

		return (isset($row['Identity']) && !is_null($row['Identity'])) ? $row['Identity'] : false;
	}

	function free_result($result = false)
	{
		if ($result && strpos($result, 'LIMIT_') === 0)
		{
			if (isset($this->_limit_result[$result]))
			{
				unset($this->_limit_result[$result]);
			}
			return;
		}

		if (!$result || !isset($this->open_queries[(int) $result]) || !$this->link_identifier) 
		{ 
			return; 
		}

		unset($this->open_queries[(int) $result]);

		@mssql_free_result($result);
	}

	function escape($text)
	{
		return str_replace("'", "''", str_replace('\\', '\\\\', $text));
	}

	function escape_array($value)
	{
		return preg_replace('#(.*?)#e', "\$this->escape('\\1')", $value);
	}
		
	function optimize_tables()
	{

	}

	function version()
	{
		//add later, more important things to do
		//@@VERSION
	}

	function _error($sql = '', $backtrace)
	{
		if ($this->return_on_error)
		{
			return;
		}

		// should we use the long error getting why ?
		$message = '<u>SQL ERROR</u><br /><br />' . @mssql_get_last_message() . '<br /><br />File:<br/><br/>'.$backtrace['file'].'<br /><br />Line:<br /><br />'.$backtrace['line'].'<br /><br /><u>CALLING PAGE</u><br /><br />'.(($sql) ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br />';

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
				$this->query_details[$this->num_queries][] = '';

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
						// should we use the long error getting why ?
						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'error'=> 1, 'errorcode' => mssql_get_last_message());
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

				$fields = implode(", \n", $this->_fields);
				if ($indexs = implode(", \n", $this->_indexs))
				{
					$fields .= ", \n";
				}

				$table = 'CREATE TABLE '.$this->_table_name." ( \n" .$fields. $indexs ." \n ) ENGINE=InnoDB;";
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

	function add_table_field_int($name, $number_min, $number_max = false, $default = 0, $auto_increment = false)
	{
		$length = max(strlen($number_min), strlen($number_max));

		if ($number_min >= -0 && $number_max <= 255)
		{
			// TINYINT UNSIGNED ( 0 to 255 )
			$this->_fields[$name] =  "`$name` TINYINT($length) UNSIGNED";
		}
		elseif ($number_min >= -128 && $number_max <= 128)
		{
			// TINYINT ( -128 to 127 )
			$this->_fields[$name] =  "`$name` TINYINT($length) DEFAULT";
		}
		elseif ($number_min >= 0 && $number_max <= 65535)
		{
			// SMALLINT UNSIGNED ( 0 to 65,535 )
			$this->_fields[$name] =  "`$name` SMALLINT($length) UNSIGNED";
		}
		elseif ($number_min >= -32768 && $number_max <= 32767)
		{
			// SMALLINT ( -32,768 to 32,767 )
			$this->_fields[$name] =  "`$name` SMALLINT($length)";
		}
		elseif ($number_min >= 0 && $number_max <= 16777215)
		{
			// MEDIUMINT UNSIGNED ( 0 to 16,777,215 )
			$this->_fields[$name] =  "`$name` MEDIUMINT($length) UNSIGNED";
		}
		elseif ($number_min >= -8388608 && $number_max <= 8388607)
		{
			// MEDIUMINT ( -8,388,608 to 8,388,607 )
			$this->_fields[$name] =  "`$name` MEDIUMINT($length)";
		}
		elseif ($number_min >= -2147483647 && $number_max <= 2147483647)
		{
			// INT ( -2,147,483,647 to 2,147,483,647 )
			$this->_fields[$name] =  "`$name` INT($length)";
		}
		elseif ($number_min >= 0 && $number_max <= 4294967295) // we'll do this last
		{
			// INT UNSIGNED ( 0 to 4,294,967,295 )
			$this->_fields[$name] =  "`$name` INT($length) UNSIGNED";
		}

		if ($auto_increment)
		{
			$this->_fields[$name] .= ' auto_increment';
		}
		else
		{
			$this->_fields[$name] .= (is_null($default)) ? " NULL" : " NOT NULL DEFAULT '".(int) $default."'";
		}
	}

	function add_table_field_text($name, $characters = 60000, $null = false)
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

	function add_table_field_char($name, $characters, $default = '', $padded = false)
	{

		$this->_fields[$name] = ($padded) ? "`$name` CHAR($characters)" :  "`$name` VARCHAR($characters)";
		$this->_fields[$name] .= (is_null($default)) ? " NULL" : " NOT NULL DEFAULT '$default'";
	}

	function add_table_index($field, $type  = 'index', $index_name = false)
	{
		$index_name = ($index_name) ? $index_name : $field;

		if (empty($this->_fields[$field]))
		{
			return;
		}

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