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

if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'bots')
{
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
}

$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM ' . USERS_TABLE . ' WHERE user_type = ' . USER_BOT);
list($count_bots) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);

$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM ' . USERS_TABLE . ' WHERE user_type = ' . USER_NORMAL);
list($count_users) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);


$_CLASS['core_template']->assign_array(array(
	'COUNT_BOTS'	=> $count_bots,
	'COUNT_USERS'	=> $count_users,
	'LINK_BOTS'		=> generate_link('users&amp;mode=bots', array('admin' => true)),
	'LINK_ADD_USER'	=> generate_link('users&amp;mode=add_user', array('admin' => true)),
	'LINK_EDIT_USER'=> generate_link('users&amp;mode=edit_user', array('admin' => true)),
	//'LAST_VISIT'	=> ($row['user_last_visit']) ?  $_CLASS['core_user']->format_date($row['user_last_visit']) : $_CLASS['core_user']->lang['BOT_NEVER'],
	//'L_STATUS'		=> ($row['user_status'] == STATUS_ACTIVE) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
));

$_CLASS['core_display']->display(false, 'admin/users/index.html');

?>