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

if (VIPERAL !== 'Admin') 
{
	die;
}

// Just testing the layout, i know they not acurrate
$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM ' . USERS_TABLE . ' WHERE user_type = ' . USER_BOT);
list($count_bots) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);

$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM ' . USERS_TABLE . ' WHERE user_type = ' . USER_NORMAL);
list($count_users) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);


$_CLASS['core_template']->assign_array(array(
	'COUNT_BOTS'	=> $count_bots,
	'COUNT_USERS'	=> $count_users,
	'LINK_ADD_USER'	=> generate_link('users&amp;mode=add_user', array('admin' => true)),
	'LINK_EDIT_USER'=> generate_link('users&amp;mode=edit_user', array('admin' => true)),
	
	'LINK_USER_INDEX'		=> generate_link('users', array('admin' => true)),
	'LINK_VIEW_BOTS'		=> generate_link('users&amp;mode=bots', array('admin' => true)),
	'LINK_VIEW_DISABLED'	=> generate_link('users&amp;mode=disabled', array('admin' => true)),
	'LINK_VIEW_UNACTIVATED'	=> generate_link('users&amp;mode=unactivated', array('admin' => true)),
));

if (isset($_REQUEST['mode']))
{
	switch ($_REQUEST['mode'])
	{
		case 'bots':
			$id = (int) get_variable('id', 'GET', false);
			
			if ($id && isset($_REQUEST['option']))
			{
				require_once($site_file_root.'includes/functions_user.php');
			
				switch ($_REQUEST['option'])
				{
					case 'activate':
						user_activate($id);
					break;
			
					case 'deactivate':
						user_disable($id);
					break;
				
					case 'delete':
						if (display_confirmation())
						{
							$sql = 'SELECT user_id, user_type
								FROM ' . USERS_TABLE . ' 
								WHERE user_id = '.$id;
				
							$result = $_CLASS['core_db']->query($sql);
							$row = $_CLASS['core_db']>fetch_row_assoc($result);
							$_CLASS['core_db']->free_result($result);
				
							if ($row['user_type'] != USER_BOT)
							{
								break;
							}
				
							user_delete($id);
				
							trigger_error($_CLASS['core_user']->lang['BOT_DELETED']);
						}
					break;
				}
			}
			
			$sql = 'SELECT user_id, username, user_status, user_last_visit 
				FROM ' . USERS_TABLE . '
				WHERE user_type = ' . USER_BOT . ' ORDER BY user_last_visit DESC';
			
			$result = $_CLASS['core_db']->query($sql);
			
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('admin_bots', array(
					'ACTIVE'		=> ($row['user_status'] == STATUS_ACTIVE),
					'NAME'			=> $row['username'],
					'LINK_DELETE'	=> generate_link('users&amp;mode=bots&amp;option=delete&amp;id='.$row['user_id'], array('admin' => true)),
					'LINK_STATUS'	=> generate_link('users&amp;mode=bots&amp;option='.(($row['user_status'] == STATUS_ACTIVE) ? 'deactivate' :  'activate').'&amp;id='.$row['user_id'], array('admin' => true)),
					'LINK_EDIT'		=> generate_link('users&amp;mode=bots&amp;options=edit&amp;id='.$row['user_id'], array('admin' => true)),
					'LAST_VISIT'	=> ($row['user_last_visit']) ?  $_CLASS['core_user']->format_date($row['user_last_visit']) : $_CLASS['core_user']->lang['BOT_NEVER'],
					'L_STATUS'		=> ($row['user_status'] == STATUS_ACTIVE) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
				));
			}
			
			$_CLASS['core_db']->free_result($result);
			
			$_CLASS['core_display']->display(false, 'admin/users/bots.html');
		break;

		case 'disabled':
		case 'unactivated':
			if ($_REQUEST['mode'] == 'unactivated')
			{
				$status = STATUS_PENDING;
				$template = 'admin/users/unactivated.html';
				$link = 'users&amp;mode=unactivated';
			}
			else
			{
				$status = STATUS_DISABLED;
				$template = 'admin/users/disabled.html';
				$link = 'users&amp;mode=disabled';
			}
$status = STATUS_DISABLED;
			$start = get_variable('start', 'GET', false, 'integer');

			$sql = 'SELECT user_id, username, user_regdate
				FROM ' . USERS_TABLE . '
					WHERE user_type = '.USER_NORMAL.'
					AND user_status = '.$status;

			$result = $_CLASS['core_db']->query_limit($sql, 20, $start);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$_CLASS['core_template']->assign_vars_array('users_admin', array(
						'user_id'		=> $row['user_id'],
						'user_name'		=> $row['username'],
						'registered'	=> $_CLASS['core_user']->format_time($row['user_regdate']),
						'link_profile'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
						'link_activate'	=> generate_link('&amp;user_mode=activate&amp;id=' . $row['user_id'], array('admin' => true)),
						'link_remove'	=> generate_link('&amp;user_mode=remove&amp;id=' . $row['user_id'], array('admin' => true)),
						'link_remind'	=> generate_link('&amp;user_mode=remind&amp;id=' . $row['user_id'], array('admin' => true)),
						'link_details'	=> '',
				));
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'SELECT count(*) as count FROM ' . USERS_TABLE . '
				WHERE user_type = '.USER_NORMAL.'
				AND user_status = '.$status;
		
			$result = $_CLASS['core_db']->query($sql);
			list($count) = $_CLASS['core_db']->fetch_row_num($result);
			$_CLASS['core_db']->free_result($result);
			
			$pagination = generate_pagination($link, $count, 20, $start, true);
			$_CLASS['core_template']->assign('USERS_PAGINATION', $pagination['formated']);

			$_CLASS['core_display']->display(false, $template);
		break;
	}
}

$user_status = array(STATUS_PENDING, STATUS_DISABLED);
$count = 0;

// needed view more
foreach ($user_status as $status)
{
	$sql = 'SELECT user_id, username, user_regdate
		FROM ' . USERS_TABLE . '
			WHERE user_type = '.USER_NORMAL.'
			AND user_status = '.$status;

	$limit = ($count) ? 10 : 20 - $count;
	$result = $_CLASS['core_db']->query_limit($sql, $limit);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$type = ($status == STATUS_DISABLED) ? 'users_disabled' : 'users_unactivated';
	
		$_CLASS['core_template']->assign_vars_array($type, array(
				'user_id'		=> $row['user_id'],
				'user_name'		=> $row['username'],
				'registered'	=> $_CLASS['core_user']->format_time($row['user_regdate']),
				'link_profile'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
				'link_activate'	=> generate_link('&amp;user_mode=activate&amp;id=' . $row['user_id'], array('admin' => true)),
				'link_remove'	=> generate_link('&amp;user_mode=remove&amp;id=' . $row['user_id'], array('admin' => true)),
				'link_remind'	=> generate_link('&amp;user_mode=remind&amp;id=' . $row['user_id'], array('admin' => true)),
				'link_details'	=> '',
		));
		
		$count ++;
	}
	$_CLASS['core_db']->free_result($result);
}

$_CLASS['core_display']->display(false, 'admin/users/index.html');

?>