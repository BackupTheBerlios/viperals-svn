<?php
// -------------------------------------------------------------
//
// $Id: ucp_zebra.php,v 1.11 2004/05/02 13:05:39 acydburn Exp $
//
// FILENAME  : ucp_zebra.php
// STARTED   : Sun Sep 28, 2003
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

class ucp_zebra extends module
{
	function ucp_zebra($id, $mode)
	{
		global $_CLASS;
		
		$_CLASS['core_template']->assign(array(
			'S_PRIVMSGS'	=> false,
			'ERROR'			=> false,
			'USERNAMES'		=> false,
			'S_USERNAME_OPTIONS' => false)
		);
		
		$submit	= (!empty($_POST['submit']) || !empty($_GET['add'])) ? true : false;
		$s_hidden_fields = '';

		if ($submit)
		{
			$var_ary = array(
				'usernames'	=> array(0),
				'add'		=> '', 
			);

			foreach ($var_ary as $var => $default)
			{
				$data[$var] = request_var($var, $default);
			}

			$var_ary = array(
				'add'	=> array('string', false)
			);

			$error = validate_data($data, $var_ary);
			
			extract($data);
			unset($data);

			if ($add && !sizeof($error))
			{
				$add = explode("\n", $add);

				// Do these name/s exist on a list already? If so, ignore ... we could be
				// 'nice' and automatically handle names added to one list present on 
				// the other (by removing the existing one) ... but I have a feeling this
				// may lead to complaints
				$sql = 'SELECT z.*, u.username 
					FROM ' . ZEBRA_TABLE . ' z, ' . USERS_TABLE . ' u 
					WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
						AND u.user_id = z.zebra_id";
				$result = $_CLASS['core_db']->sql_query($sql);

				$friends = $foes = array();
				while ($row = $_CLASS['core_db']->sql_fetchrow($result))
				{
					if ($row['friend'])
					{
						$friends[] = $row['username'];
					}
					else
					{
						$foes[] = $row['username'];
					}
				}
				$_CLASS['core_db']->sql_freeresult($result);

				$add = array_diff($add, $friends, $foes, array($_CLASS['core_user']->data['username']));
				unset($friends);
				unset($foes);

				$add = implode(', ', preg_replace('#^[\s]*?(.*?)[\s]*?$#e', "\"'\" . \$_CLASS['core_db']->sql_escape('\\1') . \"'\"", $add));

				if ($add)
				{
					$sql = 'SELECT user_id    
						FROM ' . USERS_TABLE . ' 
						WHERE username IN (' . $add . ')';
					$result = $_CLASS['core_db']->sql_query($sql);

					if ($row = $_CLASS['core_db']->sql_fetchrow($result))
					{
						$user_id_ary = array();
						do
						{
							$user_id_ary[] = $row['user_id'];
						}
						while ($row = $_CLASS['core_db']->sql_fetchrow($result));

						// Remove users from foe list if they are admins or moderators
						if ($mode == 'foes')
						{
							$perms = array();
							foreach ($_CLASS['auth']->acl_get_list($user_id_ary, array('a_', 'm_')) as $forum_id => $forum_ary)
							{
								foreach ($forum_ary as $auth_option => $user_ary)
								{
									$perms += $user_ary;
								}
							}

							// This may not be right ... it may yield true when perms equate to deny
							$user_id_ary = array_diff($user_id_ary, $perms);
							unset($perms);
						}

						if (sizeof($user_id_ary))
						{
							$sql_mode = ($mode == 'friends') ? 'friend' : 'foe';

							switch (SQL_LAYER)
							{
								case 'mysql':
									$sql = 'INSERT INTO ' . ZEBRA_TABLE . " (user_id, zebra_id, $sql_mode) 
										VALUES " . implode(', ', preg_replace('#^([0-9]+)$#', '(' . $_CLASS['core_user']->data['user_id'] . ", \\1, 1)",  $user_id_ary));
									$_CLASS['core_db']->sql_query($sql);
									break;

								case 'mysql4':
								case 'mssql':
								case 'sqlite':
									$sql = 'INSERT INTO ' . ZEBRA_TABLE . " (user_id, zebra_id, $sql_mode) 
										" . implode(' UNION ALL ', preg_replace('#^([0-9]+)$#', '(' . $_CLASS['core_user']->data['user_id'] . ", \\1, 1)",  $user_id_ary));
									$_CLASS['core_db']->sql_query($sql);
									break;

								default:
									foreach ($user_id_ary as $zebra_id)
									{
										$sql = 'INSERT INTO ' . ZEBRA_TABLE . " (user_id, zebra_id, $sql_mode)
											VALUES (" . $_CLASS['core_user']->data['user_id'] . ", $zebra_id, 1)";
										$_CLASS['core_db']->sql_query($sql);
									}
									break;
							}
						}
						else
						{
							$error[] = 'NOT_ADDED_' . strtoupper($mode);
						}
						unset($user_id_ary);
					}
					else
					{
						$error[] = 'USER_NOT_FOUND';
					}
					
					$_CLASS['core_db']->sql_freeresult($result);
				}
			}
			else if ($usernames && !sizeof($error))
			{
				// Force integer values
				$usernames = array_map('intval', $usernames);

				$sql = 'DELETE FROM ' . ZEBRA_TABLE . ' 
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
						AND zebra_id IN (' . implode(', ', $usernames) . ')';
				$_CLASS['core_db']->sql_query($sql);
			}
			
			if (!sizeof($error))
			{
				$_CLASS['core_display']->meta_refresh(3, generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"));
				$message = $_CLASS['core_user']->lang[strtoupper($mode) . '_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link("Control_Panel&amp;i=$id&amp;mode=$mode").'">', '</a>');
				trigger_error($message);
			}
			else
			{
				$_CLASS['core_template']->assign('ERROR', implode('<br />', preg_replace('#^([A-Z_]+)$#e', "(!empty(\$_CLASS['core_user']->lang['\\1'])) ? \$_CLASS['core_user']->lang['\\1'] : '\\1'", $error)));
			}
		}

		$sql_and = ($mode == 'friends') ? 'z.friend = 1' : 'z.foe = 1';
		$sql = 'SELECT z.*, u.username 
			FROM ' . ZEBRA_TABLE . ' z, ' . USERS_TABLE . ' u 
			WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
				AND $sql_and 
				AND u.user_id = z.zebra_id";
		$result = $_CLASS['core_db']->sql_query($sql);

		$s_username_options = '';
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$s_username_options .= '<option value="' . $row['zebra_id'] . '">' . $row['username'] . '</option>';
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$_CLASS['core_template']->assign(array( 
			'L_TITLE'					=> $_CLASS['core_user']->lang['UCP_ZEBRA_' . strtoupper($mode)],
			'L_YOUR_FRIENDS'			=> $_CLASS['core_user']->lang['YOUR_FRIENDS'],
			'L_YOUR_FRIENDS_EXPLAIN'	=> $_CLASS['core_user']->lang['YOUR_FRIENDS_EXPLAIN'],
			'L_ADD_FRIENDS'				=> $_CLASS['core_user']->lang['ADD_FRIENDS'],
			'L_ADD_FRIENDS_EXPLAIN'		=> $_CLASS['core_user']->lang['ADD_FRIENDS_EXPLAIN'],
			'L_NO_FRIENDS'				=> $_CLASS['core_user']->lang['NO_FRIENDS'],
			
			'L_FOES_EXPLAIN'			=> $_CLASS['core_user']->lang['FOES_EXPLAIN'],
			'L_YOUR_FOES'				=> $_CLASS['core_user']->lang['YOUR_FOES'],
			'L_ADD_FOES'				=> $_CLASS['core_user']->lang['ADD_FOES'],
			'L_ADD_FOES_EXPLAIN'		=> $_CLASS['core_user']->lang['ADD_FOES_EXPLAIN'],
			'L_YOUR_FOES_EXPLAIN'		=> $_CLASS['core_user']->lang['YOUR_FOES_EXPLAIN'],
			'L_NO_FOES'					=> $_CLASS['core_user']->lang['NO_FOES'],
			
			'L_SUBMIT'					=> $_CLASS['core_user']->lang['SUBMIT'],
			'L_RESET'					=> $_CLASS['core_user']->lang['RESET'],
			'L_FRIENDS_EXPLAIN'			=> $_CLASS['core_user']->lang['FRIENDS_EXPLAIN'],
			

			'U_SEARCH_USER'		=> generate_link('Members_List&amp;mode=searchuser&amp;form=ucp&amp;field=add'), 

			'S_USERNAME_OPTIONS'	=> $s_username_options,
			'S_HIDDEN_FIELDS'		=> $s_hidden_fields,
			'S_UCP_ACTION'			=> generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"))
		);

		$this->display($_CLASS['core_user']->lang['UCP_ZEBRA'], 'ucp_zebra_' . $mode . '.html');
	}
}

?>