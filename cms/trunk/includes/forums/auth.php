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

// -------------------------------------------------------------
//
// COPYRIGHT : 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
//
// -------------------------------------------------------------

// Will be keeping my eye of 'other products' to ensure these things don't
// mysteriously appear elsewhere, think up your own solutions!
class forums_auth
{
	var $acl = array();
	var $cache = array();
	var $option = array();
	var $acl_options = array();

	/**
	* Init permissions
	*/
	function acl($userdata)
	{
		global $_CLASS;

		if (is_null($this->acl_options = $_CLASS['core_cache']->get('acl_options')))
		{
			$sql = 'SELECT auth_option, is_global, is_local
				FROM ' . FORUMS_ACL_OPTIONS_TABLE . '
				ORDER BY auth_option_id';
			$result = $_CLASS['core_db']->query($sql);

			$global = $local = 0;
			$this->acl_options = array();

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
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
			$_CLASS['core_db']->free_result($result);

			$_CLASS['core_cache']->put('acl_options', $this->acl_options);

			$this->acl_clear_prefetch();
			$this->acl_cache($userdata);
		}
		elseif (!trim($userdata['user_permissions']))
		{
			$this->acl_cache($userdata);
		}

		foreach (explode("\n", $userdata['user_permissions']) as $f => $seq)
		{
			if ($seq)
			{
				$i = 0;

				if (!isset($this->acl[$f]))
				{
					$this->acl[$f] = '';
				}

				while ($subseq = substr($seq, $i, 6))
				{
					// We put the original bitstring into the acl array
					$this->acl[$f] .= str_pad(base_convert($subseq, 36, 2), 31, 0, STR_PAD_LEFT);
					$i += 6;
				}
			}
		}
		return;
	}

	/**
	* Look up an option
	* if the option is prefixed with !, then the result becomes negated
	*
	* If a forum id is specified the local option will be combined with a global option if one exist.
	* If a forum id is not specified, only the global option will be checked.
	*/
	function acl_get($opt, $f = 0)
	{
		$negate = false;

		if (strpos($opt, '!') === 0)
		{
			$negate = true;
			$opt = substr($opt, 1);
		}

		if (!isset($this->cache[$f][$opt]))
		{
			// We combine the global/local option with an OR because some options are global and local.
			// If the user has the global permission the local one is true too and vice versa
			$this->cache[$f][$opt] = false;

			// Is this option a global permission setting?
			if (isset($this->acl_options['global'][$opt]))
			{
				if (isset($this->acl[0]))
				{
					$this->cache[$f][$opt] = $this->acl[0]{$this->acl_options['global'][$opt]};
				}
			}

			// Is this option a local permission setting?
			// But if we check for a global option only, we won't combine the options...
			if ($f != 0 && isset($this->acl_options['local'][$opt]))
			{
				if (isset($this->acl[$f]))
				{
					$this->cache[$f][$opt] |= $this->acl[$f]{$this->acl_options['local'][$opt]};
				}
			}
		}

		// Founder always has all global options set to true...
		return ($negate) ? !$this->cache[$f][$opt] : $this->cache[$f][$opt];
	}

	/**
	* Get forums with the specified permission setting
	* if the option is prefixed with !, then the result becomes nagated
	*
	* @param bool $clean set to true if only values needs to be returned which are set/unset
	*/
	function acl_getf($opt, $clean = false)
	{
		$acl_f = array();
		$negate = false;

		if (strpos($opt, '!') === 0)
		{
			$negate = true;
			$opt = substr($opt, 1);
		}

		// If we retrieve a list of forums not having permissions in, we need to get every forum_id
		if ($negate)
		{
			if ($this->acl_forum_ids === false)
			{
				$sql = 'SELECT forum_id 
					FROM ' . FORUMS_FORUMS_TABLE;
				
				if (sizeof($this->acl))
				{
					$sql .= ' WHERE forum_id NOT IN (' .  implode(', ', array_keys($this->acl)).')';
				}
				$result = $_CLASS['core_db']->query($sql);

				$this->acl_forum_ids = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$this->acl_forum_ids[] = $row['forum_id'];
				}
				$_CLASS['core_db']->free_result($result);
			}
		}

		if (isset($this->acl_options['local'][$opt]))
		{
			$cache = array();

			foreach ($this->acl as $f => $bitstring)
			{
				// Skip global settings
				if (!$f)
				{
					continue;
				}

				$allowed = (!isset($this->cache[$f][$opt])) ? $this->acl_get($opt, $f) : $this->cache[$f][$opt];

				if (!$clean)
				{
					$acl_f[$f][$opt] = ($negate) ? !$allowed : $allowed;
				}
				else
				{
					if (($negate && !$allowed) || (!$negate && $allowed))
					{
						$acl_f[$f][$opt] = 1;
					}
				}
			}
		}

		// If we get forum_ids not having this permission, we need to fill the remaining parts
		if ($negate && sizeof($this->acl_forum_ids))
		{
			foreach ($this->acl_forum_ids as $f)
			{
				$acl_f[$f][$opt] = 1;
			}
		}

		return $acl_f;
	}

	/**
	* Get local permission state for any forum.
	*
	* Returns true if user has the permission in one or more forums, false if in no forum.
	* If global option is checked it returns the global state (same as acl_get($opt))
	* Local option has precedence...
	*/
	function acl_getf_global($opt)
	{
		$allowed = false;

		if (isset($this->acl_options['local'][$opt]))
		{
			foreach ($this->acl as $f => $bitstring)
			{
				// Skip global settings
				if (!$f)
				{
					continue;
				}

				$allowed = (!isset($this->cache[$f][$opt])) ? $this->acl_get($opt, $f) : $this->cache[$f][$opt];

				if ($allowed)
				{
					break;
				}
			}
		}
		else if (isset($this->acl_options['global'][$opt]))
		{
			$allowed = $this->acl_get($opt);
		}

		return $allowed;
	}

	function acl_gets($opts, $f = 0)
	{
		$acl = 0;

		if (is_array($opts))
		{
			foreach ($opts as $opt)
			{
				$acl |= $this->acl_get($opt, $f);
			}
		}
		else
		{
			$acl |= $this->acl_get($opts, $f);
		}

		return $acl;
	}

	function acl_get_list($user_id = false, $opts = false, $forum_id = false)
	{
		$hold_ary = $this->acl_raw_data($user_id, $opts, $forum_id);
		
		if (empty($hold_ary))
		{
			return array();
		}

		$auth_ary = array();

		foreach ($hold_ary as $user_id => $forum_ary)
		{
			foreach ($forum_ary as $forum_id => $auth_option_ary)
			{
				foreach ($auth_option_ary as $auth_option => $auth_setting)
				{
					if ($auth_setting)
					{
						$auth_ary[$forum_id][$auth_option][] = $user_id;
					}
				}
			}
		}

		return $auth_ary;
	}

	/**
	* Cache data to user_permissions row
	*/
	function acl_cache(&$userdata)
	{
		global $_CLASS;

		// Empty user_permissions
		$userdata['user_permissions'] = '';

		$hold_ary = $this->acl_raw_data($userdata['user_id'], false, false);

		if (isset($hold_ary[$userdata['user_id']]))
		{
			$hold_ary = $hold_ary[$userdata['user_id']];
		}

		$hold_str = $this->build_bitstring($hold_ary);

		if ($hold_str)
		{
			$userdata['user_permissions'] = $hold_str;

			$sql = 'UPDATE ' . USERS_TABLE . "
				SET user_permissions = '" . $_CLASS['core_db']->escape($userdata['user_permissions']) . "',
					user_perm_from = 0
				WHERE user_id = " . $userdata['user_id'];
			$_CLASS['core_db']->query($sql);
		}
	}
	/**
	* Build bitstring from permission set
	*/
	function build_bitstring(&$hold_ary)
	{
		$hold_str = '';

		if (sizeof($hold_ary))
		{
			ksort($hold_ary);

			$last_f = 0;

			foreach ($hold_ary as $f => $auth_ary)
			{
				$ary_key = (!$f) ? 'global' : 'local';

				$bitstring = array();
				foreach ($this->acl_options[$ary_key] as $opt => $id)
				{
					if (isset($auth_ary[$opt]))
					{
						$bitstring[$id] = $auth_ary[$opt];

						$option_key = substr($opt, 0, strpos($opt, '_') + 1);

						// If one option is allowed, the global permission for this option has to be allowed too
						// example: if the user has the a_ permission this means he has one or more a_* permissions
						if ($auth_ary[$opt] == ACL_YES && (!isset($bitstring[$this->acl_options[$ary_key][$option_key]]) || $bitstring[$this->acl_options[$ary_key][$option_key]] == ACL_NEVER))
						{
							$bitstring[$this->acl_options[$ary_key][$option_key]] = ACL_YES;
						}
					}
					else
					{
						$bitstring[$id] = ACL_NEVER;
					}
				}

				// Now this bitstring defines the permission setting for the current forum $f (or global setting)
				$bitstring = implode('', $bitstring);

				// The line number indicates the id, therefore we have to add empty lines for those ids not present
				$hold_str .= str_repeat("\n", $f - $last_f);
			
				// Convert bitstring for storage - we do not use binary/bytes because PHP's string functions are not fully binary safe
				for ($i = 0; $i < strlen($bitstring); $i += 31)
				{
					$hold_str .= str_pad(base_convert(str_pad(substr($bitstring, $i, 31), 31, 0, STR_PAD_RIGHT), 2, 36), 6, 0, STR_PAD_LEFT);
				}

				$last_f = $f;
			}
			unset($bitstring);

			$hold_str = rtrim($hold_str);
		}

		return $hold_str;
	}

	/**
	* Clear one or all users cached permission settings
	*/
	function acl_clear_prefetch($user_id = false)
	{
		global $_CLASS;

		$where_sql = ($user_id) ? ' WHERE user_id ' . ((is_array($user_id)) ? ' IN (' . implode(', ', array_map('intval', $user_id)) . ')' : " = $user_id") : '';

		$sql = 'UPDATE ' . CORE_USERS_TABLE . "
			SET user_permissions = ''
			$where_sql";
		$_CLASS['core_db']->query($sql);

		return;
	}

	/**
	* Get assigned roles
	*/
	function acl_role_data($user_type, $role_type, $ug_id = false, $forum_id = false)
	{
		global $_CLASS;

		$roles = array();

		$sql_id = ($user_type == 'user') ? 'user_id' : 'group_id';

		$sql_ug = ($ug_id !== false) ? ((!is_array($ug_id)) ? "AND a.$sql_id = $ug_id" : 'AND ' . $db->sql_in_set("a.$sql_id", $ug_id)) : '';
		$sql_forum = ($forum_id !== false) ? ((!is_array($forum_id)) ? "AND a.forum_id = $forum_id" : 'AND ' . $db->sql_in_set('a.forum_id', $forum_id)) : '';

		// Grab assigned roles...
		$sql = 'SELECT a.auth_role_id, a.' . $sql_id . ', a.forum_id
			FROM ' . FORUMS_ACL_TABLE . ' a, ' . FORUMS_ACL_ROLES_TABLE . " r
			WHERE a.auth_role_id = r.role_id
				AND r.role_type = '" . $_CLASS['core_db']->escape($role_type) . "'
				$sql_ug
				$sql_forum
			ORDER BY r.role_order ASC";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$roles[$row[$sql_id]][$row['forum_id']] = $row['auth_role_id'];
		}
		$_CLASS['core_db']->free_result($result);

		return $roles;
	}

	/**
	* Get raw acl data based on user/option/forum
	*/
	function acl_raw_data($user_id = false, $opts = false, $forum_id = false)
	{
		global $_CLASS;

		$sql_user = ($user_id) ? (is_array($user_id) ? 'user_id IN (' . implode(', ', $user_id) . ')' : 'user_id = '.$user_id) : '';
		$sql_forum = ($forum_id) ? (is_array($forum_id) ? 'AND a.forum_id IN (' . implode(', ', $forum_id) . ')' : 'AND a.forum_id = '.$forum_id) : '';

		$sql_opts = '';

		if ($opts !== false)
		{
			if (!is_array($opts))
			{
				$sql_opts = (strpos($opts, '%') !== false) ? "AND ao.auth_option LIKE '" . $_CLASS['core_db']->escape($opts) . "'" : "AND ao.auth_option = '" . $_CLASS['core_db']->escape($opts) . "'";
			}
			else
			{
				$sql_opts = "AND ao.auth_option IN ('" . implode("' ,'", $_CLASS['core_db']->escape_array($opts)) . "')";
			}
		}

		$groups = $group_id_array = $group_members = $hold_ary = array();

		if ($sql_user)
		{
			$sql = 'SELECT group_id, user_id FROM ' . CORE_USER_GROUP_TABLE ." WHERE $sql_user AND member_status <> ".STATUS_PENDING;

			$result = $_CLASS['core_db']->query($sql);
	
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$groups[$row['group_id']] = true;
				$group_members[$row['group_id']][] = $row['user_id'];
			}
			$_CLASS['core_db']->free_result($result);

			$sql_user = empty($groups) ? ' AND a.' . $sql_user :  'AND (a.'.$sql_user.' OR a.group_id IN ('.implode(', ', array_keys($groups)).'))';
			unset($groups);
		}

		// Sort by group_id since we want user setting to over right grp..  specific > broad
		$sql = 'SELECT ao.auth_option, a.user_id, a.group_id, a.forum_id, a.auth_setting
					FROM ' . FORUMS_ACL_TABLE . ' a, ' . FORUMS_ACL_OPTIONS_TABLE . " ao
					WHERE a.auth_option_id = ao.auth_option_id 
						$sql_user $sql_forum $sql_opts
						ORDER BY a.group_id";

		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['auth_setting'] != ACL_YES)
			{
				continue;
			}

			if ($row['group_id'])
			{
				$group_id_array[$row['group_id']][] = $row;

				continue;
			}

			$hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
		}
		$_CLASS['core_db']->free_result($result);

		if (!empty($group_id_array))
		{
			if (empty($group_members))
			{
				$sql = 'SELECT user_group, user_id FROM ' . CORE_USERS_TABLE .' WHERE user_group IN ('.implode(', ', array_keys($group_id_array)).') AND user_status = '.STATUS_ACTIVE;
				$result = $_CLASS['core_db']->query($sql);
	
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$group_members[$row['user_group']][] = $row['user_id'];
				}
				$_CLASS['core_db']->free_result($result);
			}

			foreach ($group_id_array as $group_id => $rows)
			{
				if (empty($group_members[$group_id]))
				{
					continue;
				}

				foreach ($group_members[$group_id] as $user_id)
				{
					foreach($rows as $row)
					{
						$hold_ary[$user_id][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
					}
				}
			}
		}

		return $hold_ary;
	}

	function acl_group_raw_data($group_id = false, $opts = false, $forum_id = false)
	{
		global $_CLASS;

		$sql_group = ($group_id) ? ((!is_array($group_id)) ? "group_id = $group_id" : 'group_id IN (' . implode(', ', $group_id) . ')') : '';
		$sql_forum = ($forum_id) ? ((!is_array($forum_id)) ? "AND a.forum_id = $forum_id" : 'AND a.forum_id IN (' . implode(', ', $forum_id) . ')') : '';

		$sql_opts = '';

		if ($opts !== false)
		{
			if (!is_array($opts))
			{
				$sql_opts = (strpos($opts, '%') !== false) ? "AND ao.auth_option LIKE '" . $_CLASS['core_db']->escape($opts) . "'" : "AND ao.auth_option = '" . $_CLASS['core_db']->escape($opts) . "'";
			}
			else
			{
				$sql_opts = "AND ao.auth_option IN ('" . implode("' ,'", $_CLASS['core_db']->escape_array($opts)) . "')";
			}
		}

		$hold_ary = array();

		// Grab group settings ... ACL_NO overrides ACL_YES so act appropriatley
		$sql = 'SELECT a.group_id, ao.auth_option, a.forum_id, a.auth_setting
			FROM ' . FORUMS_ACL_OPTIONS_TABLE . ' ao, ' . FORUMS_ACL_GROUPS_TABLE . ' a
			WHERE ao.auth_option_id = a.auth_option_id
				' . (($sql_group) ? 'AND a.' . $sql_group : '') . "
				$sql_forum
				$sql_opts
			ORDER BY a.forum_id, ao.auth_option";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$hold_ary[$row['group_id']][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
		}
		$_CLASS['core_db']->free_result($result);

		return $hold_ary;
	}
}

?>