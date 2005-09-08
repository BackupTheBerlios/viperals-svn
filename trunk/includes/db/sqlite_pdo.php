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

// http://sourceforge.net/projects/sqlitemanager/
class db_sqlite_pdo
{
	var $link_identifier = false;
	var $persistent;
	var $db_layer = 'sqlite';

	var $last_result;
	var $report_error = true;
	var $in_transaction;

	var $queries_time = 0;
	var $num_queries = 0;

	var	$query_list = array();
	var $query_details = array();

	var $_indexs = array();
	var $_fields = array();
	var $_table_name = false;

	function connect($db)
	{		
		if ($this->link_identifier)
		{
			$this->disconnect();
		}

		try
		{
			$this->link_identifier = new PDO('sqlite:'.$db['file'], '', '');
		}
		catch (PDOException $e)
		{
			trigger_error('Connection failed: ' . $e->getMessage());
		}

		//$this->sql_query('PRAGMA short_column_names = 1;');
		//$this->sql_query('PRAGMA full_column_names = 0;');
	}

	function disconnect()
	{
		if (!$this->link_identifier || $this->persistent)
		{
			return;
		}

		unset($this->link_identifier);
		$this->link_identifier = false;
	}

	function report_error($report)
	{
		$this->report_error = ($report);
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

				$result = $this->link_identifier->beginTransaction();

				$this->in_transaction = true;
			break;

			case 'commit':
			
				if (!$this->in_transaction)
				{
					break;
				}

				$result = $this->link_identifier->commit();

				if (!$result)
				{
					$this->link_identifier->rollBack();
				}
				
				$this->in_transaction = false;
			break;

			case 'rollback':
				if (!$this->in_transaction)
				{
					break;
				}

				$result = $this->link_identifier->rollBack();
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

		return $this->last_result;
	}

	function sql_query($query)
	{
		return $this->link_identifier->query($query);
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

		return $this->link_identifier->columnCount();
	}

	function affected_rows()
	{
		if (!$this->link_identifier)
		{ 
			return 0; 
		}

		return (int) $this->last_result->rowCount();
	}

	function fetch_row_assoc($result = false)
	{
		global $_CLASS;

		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		$this->new_array = false;

		return $result->fetch(PDO_FETCH_ASSOC);
	}

	function fetch_row_num($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return $result->fetch(PDO_FETCH_NUM);
	}

	function fetch_row_both($result = false)
	{
		if (!$result || !$this->link_identifier)
		{
			return false;
		}

		return $result->fetch(PDO_FETCH_BOTH);
	}

	function insert_id()
	{
		return ($this->link_identifier) ? (int) $this->link_identifier->lastInsertId() : 0;
	}

	function free_result($result = false)
	{
		return true;
	}

	function escape($text)
	{
		$text = $this->link_identifier->quote($text);
		$length = strlen($text) - 2;

		return substr($text, 1, $length);
	}

	function escape_array($value)
	{
		return preg_replace('#(.*?)#e', "\$this->escape('\\1')", $value);
	}

	function optimize_tables($table = '')
	{
		global $_CORE_CONFIG;

		$this->sql_query('VACUUM');
	}

	function version($return_dbname = false)
	{
		if (!$this->link_identifier)
		{
			return false;
		}
		
		//return (($return_dbname) ? 'SQLITE ' : '').sqlite_libversion();
	}

	function _error($sql = '', $backtrace)
	{
		if (!$this->report_error)
		{
			return;
		}

		$error = $this->link_identifier->errorInfo();

		$message = '<u>SQL ERROR</u><br /><br />' . $error[2] . '<br /><br />File:<br/><br/>'.$backtrace['file'].'<br /><br />Line:<br /><br />'.$backtrace['line'].'<br /><br /><u>CALLING PAGE</u><br /><br />'.(($sql) ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br />';

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
					
					$this->query_details[$this->num_queries][] = '';
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
						$error = $this->link_identifier->errorInfo();
						$this->query_list[$this->num_queries] = array('query' => $this->last_query, 'file' => $backtrace['file'], 'line'=> $backtrace['line'], 'error'=> $error[2], 'errorcode' => $error[0]);
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

				$table = 'CREATE TABLE '.$this->_table_name." ( \n" .$fields." \n );";

				if ($option == 'return')
				{
					$indexs = ($this->_indexs) ? "\n\n".implode("\n", $this->_indexs) :  '';
					return $table.$indexs;
				}

				// Have to create the table first then do the indexs
				// I guess it's ....
				if (!$this->sql_query($table))
				{
					echo $table.'<br/>';
				}
				
				foreach ($this->_indexs as $query)
				{
					if (!$this->sql_query($query))
					{
						echo $query.'<br/>';
					}
				}


			case 'cancel':
				$this->_table_name = false;
				$this->_fields = $this->_indexs = array();
			break;
		}
	}

	function add_table_field_int($name, $setting_sent)
	{
		$setting = array('default' => null, 'auto_increment' => false, 'null' => false);
		$setting = array_merge($setting, $setting_sent);

		$this->_fields[$name] =  $name .' INTEGER';

		if ($setting['auto_increment'])
		{
			$this->_fields[$name] .= ' AUTOINCREMENT';
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
		$this->_fields[$name] =  $name.' TEXT';
		$this->_fields[$name] .= ($null) ? " NULL" : " NOT NULL";
		$this->_fields[$name] .= is_null($default) ? '' : "DEFAULT '$default'";
	}

	function add_table_index($field, $type  = 'index', $index_name = false)
	{
		$index_name = ($index_name) ? $index_name : $field;

		$index_name = is_array($index_name) ? implode('_', $index_name) : $index_name;
		$index_name = $this->_table_name . '_' . $index_name;

		switch ($type)
		{
			case 'index':
			case 'unique':
				$field = is_array($field) ? implode(', ', $field) : $field;
				$this->_indexs[$index_name] = (($type == 'UNIQUE') ? 'CREATE UNIQUE' : 'CREATE') . " INDEX $index_name ON {$this->_table_name} ($field);";
			break;

			case 'primary':
// need to check if sqlite even supports 2 pkeys
				if (is_array($field))
				{
					// maybe make this regular indexes
					return;
				}

				/*
				if ($pos = strpos($this->_fields[$field], ' AUTOINCREMENT'))
				{
					$this->_fields[$field] = substr($this->_fields[$field], 0, $pos);
					$this->_fields[$field] .= ' PRIMARY KEY AUTOINCREMENT'; // AUTOINCREMENT isn't needed

					break;
				}
				*/

				$this->_fields[$field] .= ' PRIMARY KEY';
			break;
		}
	}
}

?>