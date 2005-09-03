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

class db_mysql
{
	var $link_identifier = false;
	var $db_layer = 'mysql';

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

		$this->link_identifier = ($db['persistent']) ? @mysql_pconnect($db['server'], $db['username'], $db['password']) : @mysql_connect($db['server'], $db['username'], $db['password']);
		if ($this->link_identifier)
		{
			if (@mysql_select_db($db['database']))
			{
				mysql_query('SET NAMES utf8',$this->link_identifier);

				//mysql_query('SET character_set_results = NULL', $this->link_identifier);

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

		@mysql_close($this->link_identifier);
		$this->link_identifier = false;
	}

	function sql_return_on_error($fail = false)
	{
		$this->return_on_error = $fail;
	}

	function return_on_error($fail = false)
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

				//$result = mysql_query('SET AUTOCOMMIT = 0', $this->link_identifier);
				$result = mysql_query('START TRANSACTION', $this->link_identifier);
				$this->in_transaction = true;
			break;

			case 'commit':

				if (!$this->in_transaction)
				{
					break;
				}

				$result = mysql_query('COMMIT', $this->link_identifier);

				if (!$result && $auto_rollback)
				{
					mysql_query('ROLLBACK', $this->link_identifier);
				}

				//$result = mysql_query('SET AUTOCOMMIT=1', $this->link_identifier);
				$this->in_transaction = false;
			break;

			case 'rollback':
				if (!$this->in_transaction)
				{
					break;
				}

				$result = mysql_query('ROLLBACK', $this->link_identifier);
				//$result = mysql_query('SET AUTOCOMMIT=1', $this->link_identifier);
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
		elseif (strpos($query, 'SELECT') === 0)
		{
			$this->open_queries[(int) $this->last_result] = $this->last_result;
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
		return mysql_query($query, $this->link_identifier);
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

		return mysql_num_rows($result);
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

		return @mysql_fetch_assoc($result);
	}

	function fetch_row_num($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @mysql_fetch_row($result);
	}

	function fetch_row_both($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return @mysql_fetch_array($result);
	}
	
	function insert_id($table, $column)
	{
		return ($this->link_identifier) ? @mysql_insert_id($this->link_identifier) : false;
	}

	function free_result($result = false)
	{
		if (!$result || !isset($this->open_queries[(int) $result]) || !$this->link_identifier) 
		{ 
			return false; 
		}

		unset($this->open_queries[(int) $result]);

		return @mysql_free_result($result);
	}

	function escape($text)
	{
		if (!$this->link_identifier)
		{ 
			return false; 
		}

		return mysql_real_escape_string($text, $this->link_identifier);
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

			$this->sql_freeresult($result);
		}

		if ($table)
		{
			$this->query('OPTIMIZE TABLE '. $table);
		}
	}

	function version($return_dbname = false)
	{
		if (!$this->link_identifier)
		{
			return false;
		}
		
		return (($return_dbname) ? 'MySQL ' : '').mysql_get_server_info($this->link_identifier);
	}

	function _error($sql = '', $backtrace)
	{
		if ($this->return_on_error)
		{
			return;
		}

		$message = '<br clear="all"/><table><tr><td><u>SQL ERROR</u><br /><br />' . @mysql_error() . '<br /><br />File:<br/><br/>'.$backtrace['file'].'<br /><br />Line:<br /><br />'.$backtrace['line'].'<br /><br /><u>CALLING PAGE</u><br /><br />'.(($sql) ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br /></td></tr></table>';

		if ($this->in_transaction)
		{
			$this->transaction('rollback');
		}

		trigger_error($message, E_USER_ERROR);
		
		script_close(false);
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
// this is done even for none admins ( need to fix this )
				if (empty($_CORE_CONFIG['server']['error_options']) || $_CORE_CONFIG['server']['error_options'] == ERROR_DEBUGGER)
				{
					if (strpos($this->last_query, 'SELECT') === 0)
					{
						if ($result = @mysql_query('EXPLAIN '.$this->last_query, $this->link_identifier))
						{
							while ($row = @mysql_fetch_assoc($result))
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

				if (empty($_CORE_CONFIG['server']['error_options']) || $_CORE_CONFIG['server']['error_options'] == ERROR_DEBUGGER)
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
						$error = mysql_error();
						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'error'=> $error['code'], 'errorcode' => $error['message']);
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

				$table = 'CREATE TABLE '.$this->_table_name." ( \n" .$fields. $indexs ." \n ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";
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
		$index_name = ($index_name) ? $index_name : $field;

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

if (!function_exists('mysql_real_escape_string'))
{
	function mysql_real_escape_string($text, $null)
	{
		return mysql_escape_string($text);
	}
}

?>