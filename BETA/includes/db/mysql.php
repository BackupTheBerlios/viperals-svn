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

	function sql_connect($sqlserver, $sqluser, $sqlpassword, $database, $port = false, $persistency = false)
	{
		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->server = $sqlserver . (($port) ? ':' . $port : '');
		$this->dbname = $database;

		$this->db_connect_id = ($this->persistency) ? @mysql_pconnect($this->server, $this->user, $this->password) : @mysql_connect($this->server, $this->user, $this->password);

		if ($this->db_connect_id && $this->dbname != '')
		{
			if (@mysql_select_db($this->dbname))
			{
				return $this->db_connect_id;
			}
		}

		return $this->sql_error('');
	}

	function sql_close()
	{
		if (!$this->db_connect_id)
		{
			return false;
		}

		if (count($this->open_queries))
		{
			foreach ($this->open_queries as $query_id)
			{
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
				$this->transaction = true;
				$result = @mysql_query('BEGIN', $this->db_connect_id);
				break;

			case 'commit':
				$this->transaction = false;
				$result = @mysql_query('COMMIT', $this->db_connect_id);
				break;

			case 'rollback':
				$this->transaction = false;
				$result = @mysql_query('ROLLBACK', $this->db_connect_id);
				break;

			default:
				$result = true;
		}

		return $result;
	}

	// Base query method
	function sql_query($query = '', $cache_ttl = 0)
	{
		if ($query != '')
		{
			global $_CLASS;
			
			// Preparing for feature db debugging...  wow we i'm a genius.
			$caller_info = debug_backtrace();
			
			$this->query_result = ($cache_ttl && !empty($_CLASS['cache'])) ? $_CLASS['cache']->sql_load($query) : false;

			if (!$this->query_result)
			{
				$this->num_queries++;
				$this->sql_report('start', $query);
				
				if (($this->query_result = @mysql_query($query, $this->db_connect_id)) === false)
				{
					$this->sql_error($query);
				}

				$this->sql_report('stop', $query);

				if ($cache_ttl && method_exists($_CLASS['cache'], 'sql_save'))
				{
					$_CLASS['cache']->sql_save($query, $this->query_result, $cache_ttl);
					// mysql_free_result happened within sql_save()
				}
				elseif (preg_match('/^SELECT/', $query))
				{
					$this->open_queries[] = $this->query_result;
				}
			}
			else
			{
				$this->sql_report('start', $query);
				$this->sql_report('fromcache', $query);
			}
		}
		else
		{
			return false;
		}

		return ($this->query_result) ? $this->query_result : false;
	}

	function sql_query_limit($query, $total, $offset = 0, $cache_ttl = 0) 
	{ 
		if ($query != '') 
		{
			$this->query_result = false; 

			// if $total is set to 0 we do not want to limit the number of rows
			if ($total == 0)
			{
				$total = -1;
			}

			$query .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $total : $total);

			return $this->sql_query($query, $cache_ttl); 
		} 
		else 
		{ 
			return false; 
		} 
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

	// Other query methods
	//
	// NOTE :: Want to remove _ALL_ reliance on sql_numrows from core code ...
	//         don't want this here by a middle Milestone
	function sql_numrows($query_id = false)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		return ($query_id) ? @mysql_num_rows($query_id) : false;
	}

	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? @mysql_affected_rows($this->db_connect_id) : false;
	}

	function sql_fetchrow($query_id = 0)
	{
		global $_CLASS;

		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		if (!empty($_CLASS['cache']) && $_CLASS['cache']->sql_exists($query_id))
		{
			return $_CLASS['cache']->sql_fetchrow($query_id);
		}
        
        /*if($query_id) {
            $this->row[$query_id] = @mysql_fetch_array($query_id);
            return (isset($this->row[$query_id])) ? $this->row[$query_id] : false;
        }
        else {
            return false;
        }*/
		
		return ($query_id) ? @mysql_fetch_assoc($query_id) : false;
	}

	function sql_fetchrowset($query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		if ($query_id)
		{
			$this->rowset[$query_id] = false;
			unset($this->row[$query_id]);
			while ($this->rowset[$query_id] = $this->sql_fetchrow($query_id))
			{
				$result[] = $this->rowset[$query_id];
			}
			return $result;
		}
		return false;
	}

	function sql_fetchfield($field, $rownum = -1, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		if ($query_id)
		{
			if ($rownum > -1)
			{
				$result = @mysql_result($query_id, $rownum, $field);
			}
			else
			{
				if (empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
				{
					if ($this->sql_fetchrow())
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
		return false;
	}

	function sql_rowseek($rownum, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		return ($query_id) ? @mysql_data_seek($query_id, $rownum) : false;
	}

	function sql_nextid()
	{
		return ($this->db_connect_id) ? @mysql_insert_id($this->db_connect_id) : false;
	}

	function sql_freeresult($query_id = false)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		if ($query_id)
		{
			// If it is not found within the open queries, we try to free a cached result. ;)
			if (!(array_search($query_id, $this->open_queries) > 0))
			{
				return false;
			}
			unset($this->open_queries[array_search($query_id, $this->open_queries)]);
		}

		return ($query_id) ? @mysql_free_result($query_id) : false;
	}

	function sql_escape($msg)
	{
		return mysql_escape_string($msg);
	}
	
	function sql_error($sql = '')
	{
		if (!$this->return_on_error)
		{
			$this_page = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : $_ENV['PHP_SELF'];
			$this_page .= '&' . ((!empty($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : $_ENV['QUERY_STRING']);

			$message = '<u>SQL ERROR</u> [ ' . SQL_LAYER . ' ]<br /><br />' . @mysql_error() . '<br /><br /><u>CALLING PAGE</u><br /><br />'  . htmlspecialchars($this_page) . (($sql != '') ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br />';

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
		global $db, $_CLASS, $MAIN_CFG;
		static $starttime, $query_hold;

		
		if (!$query && !empty($query_hold))
		{
			$query = $query_hold;
		}

		switch ($mode)
		{
			case 'start':
				if ($MAIN_CFG['global']['error'] == 3)
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

			case 'fromcache':
			
				if ($MAIN_CFG['global']['error'] != 3)
				{
					return;
				}
				
				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];
				
				$result = mysql_query($query, $this->db_connect_id);
				
				while ($void = mysql_fetch_assoc($result))
				{
					// Take the time spent on parsing rows into account
				}
				
				$splittime = explode(' ', microtime());
				$splittime = $splittime[0] + $splittime[1];

				$this->querydetails[$this->num_queries] = array('query'	=> $query, 'cache' => ($endtime - $starttime), 'time' => ($splittime - $endtime));

				mysql_free_result($result);

				break;

			case 'stop':
			
				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];
				$this->sql_time += $endtime - $starttime;
				
				if ($MAIN_CFG['global']['error'] == 3)
				{
					if ($this->query_result)
					{
						$affected = false;

						if (preg_match('/^(UPDATE|DELETE|REPLACE)/', $query))
						{
							$affected = $this->sql_affectedrows($this->query_result);
						}
						
						$this->querylist[$this->num_queries] = array('query' => $query, 'affected' => $affected, 'time' => ($endtime - $starttime));
					}
					else
					{
						$error = $this->sql_error();
						$this->querylist[$this->num_queries] = array('query' => $query, 'error'=> $error['code'], 'errorcode' => $error['message']);
					}
				}
	
				break;
		}
	}
}

?>