<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// COPYRIGHT : � 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
//
// -------------------------------------------------------------

define('SQL_LAYER', 'mysql');

class sql_db
{
	var $db_connect_id;
	var $query_result;
	var $return_on_error = false;
	var $transaction = false;
	var $sql_time = 0;
	var $num_queries = 0;
	var	$querylist = array();
	var $querydetails = array();
	var $open_queries = array();
	var $caller_info = false;

	function sql_connect($server, $user, $password, $database, $port = false, $persistency = false)
	{
		$server = $server . (($port) ? ':' . $port : '');

		$this->db_connect_id = ($persistency) ? mysql_pconnect($server, $user, $password) : mysql_connect($server, $user, $password);

		if ($this->db_connect_id && $database)
		{
			if (mysql_select_db($database))
			{
				return $this->db_connect_id;
			}
			$error = '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB2</center>';
		}
		
		$error = ($error) ? $error : '<center>There is currently a problem with the site<br/>Please try again later<br /><br />Error Code: DB1</center>';
		trigger_error($error, E_USER_ERROR);

		die;
	}

	function sql_close()
	{
		if (!$this->db_connect_id)
		{
			return;
		}

		if (sizeof($this->open_queries))
		{
			foreach ($this->open_queries as $i_query_id => $query_id)
			{
				//echo $query_id;
				@mysql_free_result($query_id);
			}
		}

		return @mysql_close($this->db_connect_id);
	}

	function sql_return_on_error($fail = false)
	{
		$this->return_on_error = $fail;
	}

	function sql_num_queries()
	{
		return $this->num_queries;
	}

	function sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				$result = @mysql_query('BEGIN', $this->db_connect_id);
				$this->transaction = true;
				break;

			case 'commit':
				$result = @mysql_query('COMMIT', $this->db_connect_id);
				$this->transaction = false;
				
				if (!$result)
				{
					@mysql_query('ROLLBACK', $this->db_connect_id);
				}
				break;

			case 'rollback':
				$result = @mysql_query('ROLLBACK', $this->db_connect_id);
				$this->transaction = false;
				break;

			default:
				$result = true;
		}

		return $result;
	}

	// Base query method
	function sql_query($query = '', $cache_ttl = 0)
	{
		if (!$query)
		{
			return false;
		}
		
		global $_CLASS;
			
		if (!$this->caller_info)
		{
			$this->caller_info = debug_backtrace();
		}
		//print_r($caller_info);

		$this->num_queries++;
		$this->sql_report('start', $query);
		
		if (($this->query_result = @mysql_query($query, $this->db_connect_id)) === false)
		{
			$this->sql_error($query);
		}

		$this->sql_report('stop', $query);

		if (strpos($query, 'SELECT') !== false && $this->query_result)
		{
			$this->open_queries[(int) $this->query_result] = $this->query_result;
		}


		return ($this->query_result) ? $this->query_result : false;
	}

	function sql_query_limit($query, $total, $offset = 0, $cache_ttl = 0) 
	{ 
		if (!$query) 
		{ 
			return false; 
		}
		
		$this->query_result = false; 
		$this->caller_info = debug_backtrace();
		
		// if $total is set to 0 we do not want to limit the number of rows
		if ($total == 0)
		{
			$total = -1;
		}

		$query .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $total : $total);

		return $this->sql_query($query, $cache_ttl); 

	}

	// Idea for this from Ikonboard
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
					$values[] = "'" . $this->sql_escape($var) . "'";
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
					$values[] = "$key = '" . $this->sql_escape($var) . "'";
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
	
	function sql_numrows($query_id = false)
	{
		return ($query_id) ? @mysql_num_rows($query_id) : 0;
	}

	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? @mysql_affected_rows($this->db_connect_id) : false;
	}

	function sql_fetchrow($query_id = false)
	{
		global $_CLASS;
		
		if (!$query_id)
		{
			return false;
		}

		return @mysql_fetch_assoc($query_id);
	}

	function sql_fetchrowset($query_id = false)
	{
		if (!$query_id)
		{
			return false;
		}
		
		unset($this->rowset[$query_id]);
		unset($this->row[$query_id]);
		
		$result = array();
		
		while ($this->rowset[$query_id] = $this->sql_fetchrow($query_id))
		{
			$result[] = $this->rowset[$query_id];
		}
		return $result;
	}

	function sql_fetchfield($field, $rownum = -1, $query_id = false)
	{
		if (!$query_id)
		{
			return false;
		}
		
		if ($rownum > -1)
		{
			$result = @mysql_result($query_id, $rownum, $field);
		}
		else
		{
			if (empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
			{
				if ($this->sql_fetchrow($query_id))
				{
					$result = $this->row[$query_id][$field];
				}
			}
			else
			{
				if ($this->rowset[$query_id])
				{
					$result = $this->rowset[$query_id][$field];
				}
				elseif ($this->row[$query_id])
				{
					$result = $this->row[$query_id][$field];
				}
			}
		}
		return $result;
	}

	function sql_rowseek($rownum, $query_id = false)
	{
		if (!$query_id)
		{
			return false;
		}

		return ($query_id) ? @mysql_data_seek($query_id, $rownum) : false;
	}

	function sql_nextid()
	{
		return ($this->db_connect_id) ? @mysql_insert_id($this->db_connect_id) : false;
	}

	function sql_freeresult($query_id = false)
	{
		if (!$query_id || !isset($this->open_queries[(int) $query_id]))
		{
			return false;
		}
		
		unset($this->open_queries[(int) $query_id]);

		return @mysql_free_result($query_id);
	}

	function sql_escape($msg)
	{
		return @mysql_escape_string($msg);
	}
	
	function sql_optimize_tables($tables = false)
	{
		global $_CORE_CONFIG;
	
		if (is_array($table))
		{
// la la la la la
			return;
		}
	
		$result = $this->sql_query('SHOW TABLES');
		$table = false;
		
		while ($row = $this->sql_fetchrow($result))
		{
			if ($table)
			{
				$table .= ', ' . $this->sql_escape($row[$key[0]]);
			}
			else
			{
				$key = array_keys($row);
				$table = $this->sql_escape($row[$key[0]]);
			}
		}
		$this->sql_freeresult($result);
		
		if ($table)
		{
			$this->sql_query('OPTIMIZE TABLE '. $table);
			echo $table;
		}
	}

	function sql_error($sql = '')
	{
		if (!$this->return_on_error)
		{
			//Clean this up man
			$this_page = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : $_ENV['PHP_SELF'];
			$this_page .= '&' . ((!empty($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : $_ENV['QUERY_STRING']);
			
			$this->caller_info[0]['file'] = str_replace('\\','/', $this->caller_info[0]['file']); // Damn Windows
			$this->caller_info[0]['file'] = htmlentities(str_replace($_SERVER['DOCUMENT_ROOT'],'', $this->caller_info[0]['file']), ENT_QUOTES);

			$message = '<u>SQL ERROR</u> [ ' . SQL_LAYER . ' ]<br /><br />' . @mysql_error() . '<br /><br />File:<br/><br/>'.$this->caller_info[0]['file'].'<br /><br />Line:<br /><br />'.$this->caller_info[0]['line'].'<br /><br /><u>CALLING PAGE</u><br /><br />'  . htmlspecialchars($this_page) . (($sql) ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br />';

			if ($this->transaction)
			{
				$this->sql_transaction('rollback');
			}
			
			trigger_error($message, E_USER_ERROR);
		}

		$result = array(
			'message'	=> @mysql_error(),
			'code'		=> @mysql_errno()
		);

		return $result;
	}

	function sql_report($mode, $query = '')
	{
		global $db, $_CLASS, $_CORE_CONFIG;
		static $starttime, $query_hold;

		
		if (!$query && !empty($query_hold))
		{
			$query = $query_hold;
		}

		switch ($mode)
		{
			case 'start':
				if (empty($_CORE_CONFIG['global']['error']) || $_CORE_CONFIG['global']['error'] == 3)
				{
					$query_hold = $query;
					
					if (preg_match('/UPDATE ([a-z0-9_]+).*?WHERE(.*)/s', $query, $m))
					{
						$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
					} elseif (preg_match('/DELETE FROM ([a-z0-9_]+).*?WHERE(.*)/s', $query, $m))	{
						$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
					} else {
						$explain_query = $query;
					}
	
					if (preg_match('/^SELECT/', $explain_query))
					{
						if ($result = mysql_query("EXPLAIN $explain_query", $this->db_connect_id))
						{
							while ($row = mysql_fetch_assoc($result))
							{
								$this->querydetails[$this->num_queries][] = $row;
							}
						}
					} else {
						$this->querydetails[$this->num_queries][] = '';
					}
				}

				$starttime = explode(' ', microtime());
				$starttime = $starttime[0] + $starttime[1];
				break;

			case 'stop':
			
				global $site_file_root;

				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];
				$this->sql_time += $endtime - $starttime;
				
				// Dam Windows
				$this->caller_info[0]['file'] = str_replace('\\','/', $this->caller_info[0]['file']);
				// remove the root directorys
				$this->caller_info[0]['file'] = str_replace($site_file_root, '', str_replace($_SERVER['DOCUMENT_ROOT'],'', $this->caller_info[0]['file']));
	
				if (empty($_CORE_CONFIG['global']['error']) || $_CORE_CONFIG['global']['error'] == 3)
				{
					if ($this->query_result)
					{
						$affected = false;

						if (preg_match('/^(UPDATE|DELETE|REPLACE)/', $query))
						{
							$affected = $this->sql_affectedrows($this->query_result);
						}
						
						$this->querylist[$this->num_queries] = array('query' => $query, 'file' => $this->caller_info[0]['file'], 'line'=> $this->caller_info[0]['line'], 'affected' => $affected, 'time' => ($endtime - $starttime));
					}
					else
					{
						$error = $this->sql_error();
						$this->querylist[$this->num_queries] = array('query' => $query, 'file' => $this->caller_info[0]['file'], 'line'=> $this->caller_info[0]['line'], 'error'=> $error['code'], 'errorcode' => $error['message']);
					}
				}
				
				$this->caller_info = false;
				break;
		}
	}
}

?>