<?php
// -------------------------------------------------------------
//
// $Id: ucp_zebra.php,v 1.11 2004/05/02 13:05:39 acydburn Exp $
//
// FILENAME  : ucp_zebra.php
// STARTED   : Sun Sep 28, 2003
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

global $_CLASS;

$_CLASS['core_template']->assign_array(array(
	'ERROR'				=> false,
	'USERNAMES'			=> false,
	'S_USERNAME_OPTIONS'=> false
));

$hidden_fields = array();
$this->mode = ($this->mode === 'foes') ? 'foes' : 'friends';

if (!empty($_POST['submit']) || !empty($_GET['add']))
{
	if ($add_users = get_variable('add', 'REQUEST', false))
	{
		$add_users = array_map('trim', array_map('strtolower', explode("\n", $add_users)));

		require_once SITE_FILE_ROOT.'includes/forums/functions.php';
		load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
		
		$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);

		$sql = 'SELECT z.*, u.username 
			FROM ' . ZEBRA_TABLE . ' z, ' . CORE_USERS_TABLE . ' u 
			WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
				AND u.user_id = z.zebra_id";
		$result = $_CLASS['core_db']->query($sql);

		$friends = $foes = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['friend'])
			{
				$friends[] = strtolower($row['username']);
			}
			else
			{
				$foes[] = strtolower($row['username']);
			}
		}
		$_CLASS['core_db']->free_result($result);

		// remove friends from the username array
		$n = count($add_users);
		$add_users = array_diff($add_users, $friends);

		if (count($add_users) < $n && $mode === 'foes')
		{
			$error[] = $_CLASS['core_user']->get_lang('NOT_ADDED_FOES_FRIENDS');
		}

		// remove foes from the username array
		$n = count($add_users);
		$add_users = array_diff($add_users, $foes);

		if (count($add_users) < $n && $mode === 'friends')
		{
			$error[] = $_CLASS['core_user']->get_lang('NOT_ADDED_FRIENDS_FOES');
		}

		// remove the user himself from the username array
		$n = sizeof($add_users);
		$add_users = array_diff($add_users, array(strtolower($_CLASS['core_user']->data['username'])));

		if (sizeof($add_users) < $n)
		{
			$error[] = $_CLASS['core_user']->get_lang('NOT_ADDED_' . $l_mode . '_SELF');
		}
	
		//$add_users = array_diff($add_users, $friends, $foes, array($_CLASS['core_user']->data['username']));
		unset($friends, $foes, $n);

		if (!empty($add_users))
		{
			$sql = 'SELECT user_id, user_type, user_status
				FROM ' . CORE_USERS_TABLE . " 
				WHERE LOWER(username) IN ('" .implode("', '", $_CLASS['core_db']->escape_array($add_users))."')";
			$result = $_CLASS['core_db']->query($sql);

			$add_users = array();

			if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				do
				{
					if ($row['user_type'] == USER_NORMAL && $row['user_status'] == STATUS_ACTIVE)
					{
						$add_users[] = $row['user_id'];
					}
				}
				while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

				// Remove users from foe list if they are admins or moderators
				if ($this->mode === 'foes')
				{
					$perms = array();
					foreach ($_CLASS['forums_auth']->acl_get_list($add_users, array('a_', 'm_')) as $forum_id => $forum_ary)
					{
						foreach ($forum_ary as $auth_option => $user_ary)
						{
							$perms += $user_ary;
						}
					}

					$perms = array_unique($perms);

					if (!empty($perms))
					{
						$error[] = $_CLASS['core_user']->get_lang('NOT_ADDED_FOES_MOD_ADMIN');
					}

					$add_users = array_diff($add_users, $perms);
					unset($perms);
				}

				if (!empty($add_users))
				{
					$sql_mode = ($this->mode === 'friends') ? 'friend' : 'foe';
					
					$sql_array = array();
					foreach ($add_users as $zebra_id)
					{
						$sql_array[] = array(
							'user_id'		=> (int) $_CLASS['core_user']->data['user_id'],
							'zebra_id'		=> (int) $zebra_id,
							$sql_mode		=> 1
						);
					}

					$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_array, ZEBRA_TABLE);
				}
				else
				{
					$error[] = 'NOT_ADDED_' . strtoupper($this->mode);
				}
				unset($add_users);
			}
			else
			{
				$error[] = 'USER_NOT_FOUND_OR_INACTIVE';
			}
			
			$_CLASS['core_db']->free_result($result);
		}
	}
	elseif ($remove_users = get_variable('usernames', 'POST', false, 'array:int'))
	{
		$sql = 'DELETE FROM ' . ZEBRA_TABLE . ' 
			WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . ' 
				AND zebra_id IN (' . implode(', ', array_unique($remove_users)) . ')';
		$_CLASS['core_db']->query($sql);
	}

	if (empty($error))
	{
		$_CLASS['core_display']->meta_refresh(3, generate_link($this->link));
		$message = $_CLASS['core_user']->lang[strtoupper($this->mode) . '_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link($this->link).'">', '</a>');
		trigger_error($message);
	}
	else
	{
		$_CLASS['core_template']->assign('ERROR', implode('<br />', $error));
	}
}

$sql_and = ($this->mode === 'friends') ? 'z.friend = 1' : 'z.foe = 1';

$sql = 'SELECT u.user_id, u.username 
	FROM ' . ZEBRA_TABLE . ' z, ' . CORE_USERS_TABLE . ' u 
	WHERE z.user_id = ' . $_CLASS['core_user']->data['user_id'] . "
		AND $sql_and 
		AND u.user_id = z.zebra_id";
$result = $_CLASS['core_db']->query($sql);

$username_options = '';
while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$username_options .= '<option value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_template']->assign_array(array( 
	'L_TITLE'				=> $_CLASS['core_user']->lang['UCP_ZEBRA_' . strtoupper($this->mode)],

	'U_SEARCH_USER'			=> generate_link('members_list&amp;mode=searchuser&amp;form=ucp&amp;field=add'), 

	'S_USERNAME_OPTIONS'	=> $username_options,
	'S_HIDDEN_FIELDS'		=> generate_hidden_fields($hidden_fields),
	'S_UCP_ACTION'			=> generate_link($this->link)
));

unset($username_options);

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('UCP_ZEBRA'), 'modules/control_panel/ucp_zebra_' . $this->mode . '.html');

?>