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
			'ERROR'				=> false,
			'USERNAMES'			=> false,
			'S_USERNAME_OPTIONS'=> false)
		);
		
		$s_hidden_fields = '';

		if (!empty($_POST['submit']) || !empty($_GET['add']))
		{
			$data['usernames'] = request_var('usernames', array(0));
			$data['add'] = request_var('add', '');

			$error = validate_data($data, array('add'	=> array('string', false)));
			
			if ($data['add'] && !sizeof($error))
			{
				$data['add'] = explode("\n", $data['add']);

				$sql = 'SELECT z.*, u.username 
					FROM ' . ZEBRA_TABLE . ' z, ' . USERS_TABLE . ' u 
					WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
						AND u.user_id = z.zebra_id";
				$result = $_CLASS['core_db']->query($sql);

				$friends = $foes = array();
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
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
				$_CLASS['core_db']->free_result($result);

				$data['add'] = array_diff($data['add'], $friends, $foes, array($_CLASS['core_user']->data['username']));
				unset($friends, $foes);

				$data['add'] = "'".implode("', '", $_CLASS['core_db']->escape_array($data['add']))."'";

				if ($data['add'])
				{
					$sql = 'SELECT user_id, user_type, user_status
						FROM ' . USERS_TABLE . ' 
						WHERE username IN (' . $data['add'] . ')';
					$result = $_CLASS['core_db']->query($sql);

					if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$user_id_ary = array();
						do
						{
							if ($row['user_type'] == USER_NORMAL && $row['user_status'] == STATUS_ACTIVE)
							{
								$user_id_ary[] = $row['user_id'];
							}
						}
						while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

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

							$sql = 'INSERT INTO ' . ZEBRA_TABLE . " (user_id, zebra_id, $sql_mode) 
								VALUES " . implode(', ', preg_replace('#^([0-9]+)$#', '(' . $_CLASS['core_user']->data['user_id'] . ", \\1, 1)",  $user_id_ary));
							$_CLASS['core_db']->query($sql);
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
					
					$_CLASS['core_db']->free_result($result);
				}
			}
			else if ($data['usernames'] && !sizeof($error))
			{
				// Force integer values
				$data['usernames'] = array_map('intval', $data['usernames']);

				$sql = 'DELETE FROM ' . ZEBRA_TABLE . ' 
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
						AND zebra_id IN (' . implode(', ', $data['usernames']) . ')';
				$_CLASS['core_db']->query($sql);
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
		$result = $_CLASS['core_db']->query($sql);

		$s_username_options = '';
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$s_username_options .= '<option value="' . $row['zebra_id'] . '">' . $row['username'] . '</option>';
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign(array( 
			'L_TITLE'				=> $_CLASS['core_user']->lang['UCP_ZEBRA_' . strtoupper($mode)],

			'U_SEARCH_USER'			=> generate_link('Members_List&amp;mode=searchuser&amp;form=ucp&amp;field=add'), 

			'S_USERNAME_OPTIONS'	=> $s_username_options,
			'S_HIDDEN_FIELDS'		=> $s_hidden_fields,
			'S_UCP_ACTION'			=> generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"))
		);

		$this->display($_CLASS['core_user']->lang['UCP_ZEBRA'], 'ucp_zebra_' . $mode . '.html');
	}
}

?>