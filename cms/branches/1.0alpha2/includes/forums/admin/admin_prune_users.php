<?php
/***************************************************************************
 *                           admin_prune_users.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: admin_prune_users.php,v 1.6 2004/05/02 13:05:36 acydburn Exp $
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

if (!empty($setmodules))
{
	if (!$auth->acl_get('a_userdel'))
	{
		return;
	}

	$module['USER']['PRUNE_USERS'] = basename(__FILE__) . $SID;

	return;
}

define('IN_PHPBB', 1);

require('pagestart.' . $phpEx);

// Do we have forum admin permissions?
if (!$auth->acl_get('a_userdel'))
{
	trigger_error($user->lang['NO_ADMIN']);
}

// Set mode
$mode = (isset($_REQUEST['mode'])) ? htmlspecialchars($_REQUEST['mode']) : '';

// Do prune
if (isset($_POST['prune']))
{
	if (empty($_POST['confirm']))
	{
		$values = array('prune', 'deactivate', 'delete', 'users', 'username', 'email', 'joined_select', 'active_select', 'count_select', 'joined', 'active', 'count', 'deleteposts');

		$l_message = '<form method="post" action="admin_prune_users.' . $phpEx . $SID . '">' . $user->lang['Confirm_prune_users'] . '<br /><br /><input class="btnlite" type="submit" name="confirm" value="' . $user->lang['Yes'] . '" />&nbsp;&nbsp;<input class="btnlite" type="submit" name="cancel" value="' . $user->lang['No'] . '" />';

		foreach ($values as $field)
		{
			$l_message .= (!empty($_POST[$field])) ? '<input type="hidden" name="' . $field . '" value="' . urlencode($_POST[$field]) . '" />' : '';
		}

		$l_message .= '</form>';

		adm_page_header($user->lang['Prune_users']);

?>

<h1><?php echo $user->lang['PRUNE_USERS']; ?></h1>

<p><?php echo $user->lang['PRUNE_USERS_EXPLAIN']; ?></p>

<?php

		page_message($user->lang['CONFIRM'], $l_message, false);
		adm_page_footer();

	}
	else if (isset($_POST['confirm']))
	{
		if (!empty($_POST['users']))
		{
			$users = explode("\n", urldecode($_POST['users']));

			$where_sql = '';
			foreach ($users as $username)
			{
				$where_sql .= (($where_sql != '') ? ', ' : '') . '\'' . trim($username) . '\'';
			}
			$where_sql = " AND username IN ($where_sql)";
		}
		else
		{
			$username = (!empty($_POST['username'])) ? urldecode($_POST['username']) : '';
			$email = (!empty($_POST['email'])) ? urldecode($_POST['email']) : '';

			$joined_select = (!empty($_POST['joined_select'])) ? $_POST['joined_select'] : 'lt';
			$active_select = (!empty($_POST['active_select'])) ? $_POST['active_select'] :'lt';
			$count_select = (!empty($_POST['count_select'])) ? $_POST['count_select'] : 'eq';
			$joined = (!empty($_POST['joined'])) ? explode('-', $_POST['joined']) : array();
			$active = (!empty($_POST['active'])) ? explode('-', $_POST['active']) :array();
			$count = (!empty($_POST['count'])) ? intval($_POST['count']) : '';

			$key_match = array('lt' => '<', 'gt' => '>', 'eq' => '=');
			$sort_by_types = array('username', 'user_email', 'user_posts', 'user_regdate', 'user_lastvisit');

			$where_sql = '';
			$where_sql .= ($username) ? " AND username LIKE '" . str_replace('*', '%', $username) ."'" : '';
			$where_sql .= ($email) ? " AND user_email LIKE '" . str_replace('*', '%', $email) ."' " : '';
			$where_sql .= ($joined) ? " AND user_regdate " . $key_match[$joined_select] . " " . gmmktime(0, 0, 0, intval($joined[1]), intval($joined[2]), intval($joined[0])) : '';
			$where_sql .= ($count) ? " AND user_posts " . $key_match[$count_select] . " $count " : '';
			$where_sql .= ($active) ? " AND user_lastvisit " . $key_match[$active_select] . " " . gmmktime(0, 0, 0, $active[1], intval($active[2]), intval($active[0])) : '';
		}

		$sql = 'SELECT username, user_id FROM ' . USERS_TABLE . '
			WHERE user_id <> ' . ANONYMOUS . "
			$where_sql";
		$result = $db->sql_query($sql);

		$where_sql = '';
		$user_ids = array();
		$usernames = array();
		if ($row = $db->sql_fetchrow($result))
		{
			do
			{
				$where_sql .= (($where_sql != '') ? ', ' : '') . $row['user_id'];
				$user_ids[] = $row['user_id'];
				$usernames[] = $row['username'];
			}
			while ($row = $db->sql_fetchrow($result));

			$where_sql = " AND user_id IN ($where_sql)";
		}
		$db->sql_freeresult($result);

		if ($where_sql != '')
		{
			$sql = '';
			if (!empty($_POST['delete']))
			{
				if (!empty($_POST['deleteposts']))
				{
					// Call unified post deletion routine?

					$l_log = 'LOG_PRUNE_USER_DEL_DEL';
				}
				else
				{
					for($i = 0; $i < sizeof($user_ids); $i++)
					{
						$sql = 'UPDATE ' . POSTS_TABLE . '
							SET poster_id = ' . ANONYMOUS . ", post_username = '" . $usernames[$i] . "'
							WHERE user_id = " . $userids[$i];
//						$db->sql_query($sql);
					}

					$l_log = 'LOG_PRUNE_USER_DEL_ANON';
				}

				$sql = 'DELETE FROM ' . USERS_TABLE;
			}
			else if (!empty($_POST['deactivate']))
			{
				$sql = 'UPDATE ' . USERS_TABLE . " 
					SET user_active = 0";

				$l_log = 'LOG_PRUNE_USER_DEAC';
			}

			$sql .= ' WHERE user_id <> ' . ANONYMOUS . "
				$where_sql";
//			$db->sql_query($sql);

			add_log('admin', $l_log, implode(', ', $usernames));

			unset($user_ids);
			unset($usernames);
		}

		trigger_error($user->lang['SUCCESS_USER_PRUNE']);
	}
}


// Front end
$find_count = array('lt' => $user->lang['LESS_THAN'], 'eq' => $user->lang['EQUAL_TO'], 'gt' => $user->lang['MORE_THAN']);
$s_find_count = '';
foreach ($find_count as $key => $value)
{
	$selected = ($key == 'eq') ? ' selected="selected"' : '';
	$s_find_count .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
}

$find_time = array('lt' => $user->lang['BEFORE'], 'gt' => $user->lang['AFTER']);
$s_find_join_time = '';
foreach ($find_time as $key => $value)
{
	$s_find_join_time .= '<option value="' . $key . '">' . $value . '</option>';
}
$s_find_active_time = '';
foreach ($find_time as $key => $value)
{
	$s_find_active_time .= '<option value="' . $key . '">' . $value . '</option>';
}

//
//
//
adm_page_header($user->lang['PRUNE_USERS']);

?>

<h1><?php echo $user->lang['PRUNE_USERS']; ?></h1>

<p><?php echo $user->lang['PRUNE_USERS_EXPLAIN']; ?></p>

<form method="post" name="post" action="<?php echo "admin_prune_users.$phpEx$SID"; ?>"><table class="bg" width="95%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $user->lang['PRUNE_USERS']; ?></th>
	</tr>
	<tr>
		<td class="row1" width="40%"><b><?php echo $user->lang['USERNAME']; ?>: </b></td>
		<td class="row2"><input class="post" type="text" name="username" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['EMAIL']; ?>: </b></td>
		<td class="row2"><input class="post" type="text" name="email" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['JOINED']; ?>: </b><br /><span class="gensmall"><?php echo $user->lang['Joined_explain']; ?></span></td>
		<td class="row2"><select name="joined_select"><?php echo $s_find_join_time; ?></select> <input class="post" type="text" name="joined" maxlength="10" size="10" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['LAST_ACTIVE']; ?>: </b><br /><span class="gensmall"><?php echo $user->lang['Last_active_explain']; ?></span></td>
		<td class="row2"><select name="active_select"><?php echo $s_find_active_time; ?></select> <input class="post" type="text" name="active" maxlength="10" size="10" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['POSTS']; ?>: </b></td>
		<td class="row2"><select name="count_select"><?php echo $s_find_count; ?></select> <input class="post" type="text" name="count" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['PRUNE_USERS']; ?>: </b><br /><span class="gensmall"><?php echo $user->lang['SELECT_USERS_EXPLAIN']; ?></span></td>
		<td class="row2"><textarea name="users" cols="40" rows="5"></textarea></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['DELETE_USER_POSTS']; ?>: </b><br /><span class="gensmall"><?php echo $user->lang['DELETE_USER_POSTS_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="deleteposts" value="1" /> <?php echo $user->lang['YES']; ?>&nbsp;&nbsp;<input type="radio" name="deleteposts" value="0" checked="checked" /> <?php echo $user->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $user->lang['DEACTIVATE_DELETE']; ?>: </b><br /><span class="gensmall"><?php echo $user->lang['DEACTIVATE_DELETE_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="action" value="delete" /> <?php echo $user->lang['DELETE_USERS']; ?>&nbsp;&nbsp;<input type="radio" name="action" value="deactivate" checked="checked" /> <?php echo $user->lang['DEACTIVATE']; ?></td>
	</tr>
	<tr>
		<td class="cat" colspan="2" align="center"><input class="btnlite" type="submit" name="update" value="<?php echo $user->lang['SUBMIT']; ?>" />&nbsp;&nbsp;<input type="submit" name="usersubmit" value="<?php echo $user->lang['FIND_USERNAME']; ?>" class="btnlite" onClick="window.open('<?php echo "../search.$phpEx$SID&amp;mode=searchuser&amp;field=users"; ?>', '_phpbbsearch', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=650');return false;" /><input type="hidden" name="prune" value="1" /></td>
	</tr>
</table></form>

<?php

adm_page_footer();

?>