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

		if (is_null($this->acl_options = $_CLASS['core_cache']->get('acl_options')))
		{
			$sql = 'SELECT auth_option, is_global, is_local
				FROM ' . ACL_OPTIONS_TABLE . '
				ORDER BY auth_option_id';
			$result = $_CLASS['core_db']->query($sql);

			$global = $local = 0;
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

		return isset($cache[$f][$opt]) ? $cache[$f][$opt] : false;
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
		$hold_ary = empty($hold_ary[$userdata['user_id']]) ? '' : $hold_ary[$userdata['user_id']];

		foreach ($this->acl_options['global'] as $opt => $id)
		{
			if (strstr($opt, 'a_'))
			{
				$hold_ary[0][$opt] = 1;
			}
		}

		$hold_str = '';
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
				SET user_permissions = '" . $_CLASS['core_db']->escape($userdata['user_permissions']) . "'
				WHERE user_id = " . $userdata['user_id'];
			$_CLASS['core_db']->query($sql);
		}
		unset($hold_ary);
	}

	function acl_raw_data($user_id = false, $opts = false, $forum_id = false)
	{
		global $_CLASS;

		if (!$user_id)
		{
// not sure if that is needed anywhere. maybe add the others later...
			return;
		}

		$sql_user = ($user_id) ? (is_array($user_id) ? 'user_id IN (' . implode(', ', $user_id) . ')' : 'user_id = '.$user_id) : '';
		$sql_forum = ($forum_id) ? (is_array($forum_id) ? 'AND a.forum_id IN (' . implode(', ', $forum_id) . ')' : 'AND a.forum_id = '.$forum_id) : '';
		$sql_opts = ($opts) ? (is_array($opts) ? ' AND ao.auth_option IN (' . implode(', ', preg_replace('#^[\s]*?(.*?)[\s]*?$#e', "\"'\" . \$_CLASS['core_db']->escape('\\1') . \"'\"", $opts)) . ')' : "AND ao.auth_option = '".$_CLASS['core_db']->escape($opts)."'") : '';
		$groups = $group_members = $hold_ary = array();

		$sql = 'SELECT group_id, user_id FROM ' . USER_GROUP_TABLE ." WHERE $sql_user AND user_status <> ".STATUS_PENDING;
// This is the why phpBB3 seems to be, the why they wanted it to act may have been the above.
// atleast when you look at the coding....	
		//$sql = 'SELECT group_id, user_id FROM ' . USERS_TABLE .' WHERE '.$sql_user;
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$groups[] = $row['group_id'];
			$group_members[$row['group_id']][] = $row['user_id'];
		}

// make sure AND ( bla=blaa OR blaa2=baa2 ) works right with all databases
		$sql_user = empty($groups) ? ' AND a.' . $sql_user :  'AND (a.'.$sql_user.' OR a.group_id IN ('.implode(', ', $groups).'))';

		// Sort by group_id since we want user setting to over right grp..  specific > broad
		$sql = 'SELECT ao.auth_option, a.user_id, a.group_id, a.forum_id, a.auth_setting
					FROM ' . ACL_TABLE . ' a, ' . ACL_OPTIONS_TABLE . " ao
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
				foreach ($group_members[$row['group_id']] as $user_id)
				{
					$hold_ary[$user_id][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
				}

				continue;
			}

			$hold_ary[$row['user_id']][$row['forum_id']][$row['auth_option']] = $row['auth_setting'];
		}
//print_r($hold_ary);
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
		$_CLASS['core_db']->query($sql);

		return;
	}

	function acl_group_raw_data($group_id = false, $opts = false, $forum_id = false)
	{
		global $_CLASS;

		$sql_group = ($group_id) ? ((!is_array($group_id)) ? "group_id = $group_id" : 'group_id IN (' . implode(', ', $group_id) . ')') : '';
		$sql_forum = ($forum_id) ? ((!is_array($forum_id)) ? "AND a.forum_id = $forum_id" : 'AND a.forum_id IN (' . implode(', ', $forum_id) . ')') : '';
		$sql_opts = ($opts) ? ((!is_array($opts)) ? "AND ao.auth_option = '$opts'" : 'AND ao.auth_option IN (' . implode(', ', preg_replace('#^[\s]*?(.*?)[\s]*?$#e', "\"'\" . \$_CLASS['core_db']->escape('\\1') . \"'\"", $opts)) . ')') : '';

		$hold_ary = array();

		// Grab group settings ... ACL_NO overrides ACL_YES so act appropriatley
		$sql = 'SELECT a.group_id, ao.auth_option, a.forum_id, a.auth_setting
			FROM ' . ACL_OPTIONS_TABLE . ' ao, ' . ACL_GROUPS_TABLE . ' a
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