<?php
// Will be keeping my eye of 'other products' to ensure these things don't
// mysteriously appear elsewhere, think up your own solutions!
class auth
{
	var $founder = false;
	var $acl = array();
	var $option = array();
	var $acl_options = array();

	function acl(&$userdata)
	{
		global $_CLASS;

		if (!($this->acl_options = $_CLASS['cache']->get('acl_options')))
		{
			$sql = 'SELECT auth_option, is_global, is_local
				FROM ' . ACL_OPTIONS_TABLE . '
				ORDER BY auth_option_id';
			$result = $_CLASS['db']->sql_query($sql);

			$global = $local = 0;
			while ($row = $_CLASS['db']->sql_fetchrow($result))
			{
				if (!empty($row['is_global']))
				{
					$this->acl_options['global'][$row['auth_option']] = $global++;
				}
				if (!empty($row['is_local']))
				{
					$this->acl_options['local'][$row['auth_option']] = $local++;
				}
			}
			$_CLASS['db']->sql_freeresult($result);

			$_CLASS['cache']->put('acl_options', $this->acl_options);
			$this->acl_clear_prefetch();
			$this->acl_cache($userdata);
		}
		else if (!$userdata['user_permissions'])
		{
			$this->acl_cache($userdata);
		}

		foreach (explode("\n", $userdata['user_permissions']) as $f => $seq)
		{
			if ($seq)
			{
				$i = 0;
				while ($subseq = substr($seq, $i, 6))
				{
					if (!isset($this->acl[$f]))
					{
						$this->acl[$f] = '';
					}
					$this->acl[$f] .= str_pad(base_convert($subseq, 36, 2), 31, 0, STR_PAD_LEFT);
					$i += 6;
				}
			}
		}
		return;
	}

	// Look up an option
	function acl_get($opt, $f = 0)
	{
		static $cache;

		if (!isset($cache[$f][$opt]))
		{
			$cache[$f][$opt] = false;
			if (isset($this->acl_options['global'][$opt]))
			{
				if (isset($this->acl[0]))
				{
					$cache[$f][$opt] = $this->acl[0]{$this->acl_options['global'][$opt]};
				}
			}
			if (isset($this->acl_options['local'][$opt]))
			{
				if (isset($this->acl[$f]))
				{
					$cache[$f][$opt] |= $this->acl[$f]{$this->acl_options['local'][$opt]};
				}
			}
		}

		// Needs to change ... check founder status when updating cache?
		return $cache[$f][$opt];
	}

	function acl_getf($opt)
	{
		static $cache;

		if (isset($this->acl_options['local'][$opt]))
		{
			foreach ($this->acl as $f => $bitstring)
			{
				if (!isset($cache[$f][$opt]))
				{
					$cache[$f][$opt] = false;

					$cache[$f][$opt] = $bitstring{$this->acl_options['local'][$opt]};
					if (isset($this->acl_options['global'][$opt]))
					{
						$cache[$f][$opt] |= $this->acl[0]{$this->acl_options['global'][$opt]};
					}
				}
			}
		}

		return $cache;
	}

	function acl_gets()
	{
		$args = func_get_args();
		$f = array_pop($args);

		if (!is_numeric($f))
		{
			$args[] = $f;
			$f = 0;
		}

		// alternate syntax: acl_gets(array('m_', 'a_'), $forum_id)
		if (is_array($args[0]))
		{
			$args = $args[0];
		}

		$acl = 0;
		foreach ($args as $opt)
		{
			$acl |= $this->acl_get($opt, $f);
		}

		return $acl;
	}

	function acl_get_list($user_id = false, $opts = false, $forum_id = false)
	{
		$hold_ary = $this->acl_raw_data($user_id, $opts, $forum_id);

		$auth_ary = array();
		foreach ($hold_ary as $user_id => $forum_ary)
		{
			foreach ($forum_ary as $forum_id => $auth_option_ary)
			{
				foreach ($auth_option_ary as $auth_option => $auth_setting)
				{
					if ($auth_setting == ACL_YES)
					{
						$auth_ary[$forum_id][$auth_option][] = $user_id;
					}
				}
			}
		}

		return $auth_ary;
	}

	// Cache data
	function acl_cache(&$userdata)
	{
		global $_CLASS;

		$hold_ary = $this->acl_raw_data($userdata['user_id'], false, false);
		$hold_ary = $hold_ary[$userdata['user_id']];

		// If this user is founder we're going to force fill the admin options ...
		if ($userdata['user_type'] == USER_FOUNDER)
		{
			foreach ($this->acl_options['global'] as $opt => $id)
			{
				if (strstr($opt, 'a_'))
				{
					$hold_ary[0][$opt] = 1;
				}
			}
		}

		$hold_str = $userdata['user_permissions'];
		if (is_array($hold_ary))
		{
			ksort($hold_ary);

			$last_f = 0;
			foreach ($hold_ary as $f => $auth_ary)
			{
				$ary_key = (!$f) ? 'global' : 'local';

				$bitstring = array();
				foreach ($this->acl_options[$ary_key] as $opt => $id)
				{
					if (!empty($auth_ary[$opt]))
					{
						$bitstring[$id] = 1;

						$option_key = substr($opt, 0, strpos($opt, '_') + 1);
						if (empty($holding[$this->acl_options[$ary_key][$option_key]]))
						{
							$bitstring[$this->acl_options[$ary_key][$option_key]] = 1;
						}
					}
					else
					{
						$bitstring[$id] = 0;
					}
				}

				$bitstring = implode('', $bitstring);

				$hold_str .= str_repeat("\n", $f - $last_f);

				for ($i = 0; $i < strlen($bitstring); $i += 31)
				{
					$hold_str .= str_pad(base_convert(str_pad(substr($bitstring, $i, 31), 31, 0, STR_PAD_RIGHT), 2, 36), 6, 0, STR_PAD_LEFT);
				}

				$last_f = $f;
			}
			unset($bitstring);

			$userdata['user_permissions'] = rtrim($hold_str);

			$sql = 'UPDATE ' . USERS_TABLE . "
				SET user_permissions = '" . $_CLASS['db']->sql_escape($userdata['user_permissions']) . "'
				WHERE user_id = " . $userdata['user_id'];
			$_CLASS['db']->sql_query($sql);
		}
		unset($hold_ary);

		return;
	}

	function acl_raw_data($user_id = false, $opts = false, $forum_id = false)
	{
		global $_CLASS;

		$sql_user = ($user_id) ? ((!is_array($user_id)) ? "user_id = $user_id" : 'user_id IN (' . implode(', ', $user_id) . ')') : '';
		$sql_forum = ($forum_id) ? ((!is_array($forum_id)) ? "AND a.forum_id = $forum_id" : 'AND a.forum_id IN (' . implode(', ', $forum_id) . ')') : '';
		$sql_opts = ($opts) ? ((!is_array($opts)) ? "AND ao.auth_option = '$opts'" : 'AND ao.auth_option IN (' . implode(', ', preg_replace('#^[\s]*?(.*?)[\s]*?$#e', "\"'\" . \$_CLASS['db']->sql_escape('\\1') . \"'\"", $opts)) . ')') : '';

		$hold_ary = array();
		$sql = 'SELECT ao.auth_option, a.user_id, a.forum_id, a.auth_setting
			FROM ' . ACL_OPTIONS_TABLE . ' ao, ' . ACL_USERS_TABLE . ' a
			WHERE ao.auth_option_id = a.auth_option_id
				' . (($sql_user) ? 'AND a.' . $sql_user : '') . "
				$sql_forum
				$sql_opts
			ORDER BY a.forum_id, ao.auth_option";
		$result = $_CLASS['db']->sql_query($sql);

		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			$hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
		}
		$_CLASS['db']->sql_freeresult($result);

		$sql = 'SELECT ug.user_id, ao.auth_option, a.forum_id, a.auth_setting
			FROM ' . USER_GROUP_TABLE . ' ug, ' . ACL_OPTIONS_TABLE . ' ao, ' . ACL_GROUPS_TABLE . ' a
			WHERE ao.auth_option_id = a.auth_option_id
				AND a.group_id = ug.group_id
				' . (($sql_user) ? 'AND ug.' . $sql_user : '') . "
				$sql_forum
				$sql_opts
			ORDER BY a.forum_id, ao.auth_option";
		$result = $_CLASS['db']->sql_query($sql);

		while ($row = $_CLASS['db']->sql_fetchrow($result))
		{
			if (!isset($hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']]) || (isset($hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']]) && $hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']] != ACL_NO))
			{
				$hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
			}
		}
		$_CLASS['db']->sql_freeresult($result);

		return $hold_ary;
	}

	// Clear one or all users cached permission settings
	function acl_clear_prefetch($user_id = false)
	{
		global $_CLASS;

		$where_sql = ($user_id) ? ' WHERE user_id ' . ((is_array($user_id)) ? ' IN (' . implode(', ', array_map('intval', $user_id)) . ')' : " = $user_id") : '';

		$sql = 'UPDATE ' . USERS_TABLE . "
			SET user_permissions = ''
			$where_sql";
		$_CLASS['db']->sql_query($sql);

		return;
	}

	// Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
	function login($username, $password, $autologin = false, $viewonline = 1, $admin = 0)
	{
		global $config, $db, $_CLASS, $phpEx;

		$method = trim($config['auth_method']);

		if (file_exists('includes/auth/auth_' . $method . '.' . $phpEx))
		{
			include_once('includes/auth/auth_' . $method . '.' . $phpEx);

			$method = 'login_' . $method;
			if (function_exists($method))
			{
				$login = $method($username, $password);

				// If login returned anything other than an array there was an error
				if (!is_array($login))
				{
					return $login;
				}

				$autologin = (!empty($autologin)) ? md5($password) : '';

				return $_CLASS['user']->create($login['user_id'], $autologin, true, $viewonline, $admin);
			}
		}

		trigger_error('Authentication method not found', E_USER_ERROR);
	}
}
?>