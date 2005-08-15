<?php

if (VIPERAL !== 'Admin') 
{
	die;
}

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
	FROM ' . USERS_TABLE . ' u
	WHERE user_type = ' . USER_BOT . ' ORDER BY user_last_visit DESC';

$result = $_CLASS['core_db']->query($sql);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$_CLASS['core_template']->assign_vars_array('admin_bots', array(
		'ACTIVE'		=> ($row['user_status'] == USER_ACTIVE),
		'NAME'			=> $row['username'],
		'DELETE_LINK'	=> generate_link('bots&amp;option=delete&amp;id='.$row['user_id'], array('admin' => true)),
		'STATUS_LINK'	=> generate_link('bots&amp;option='.(($row['user_status'] == USER_ACTIVE) ? 'deactivate' :  'activate').'&amp;id='.$row['user_id'], array('admin' => true)),
		'EDIT_LINK'		=> generate_link('bots&amp;options=edite&amp;id='.$row['user_id'], array('admin' => true)),
		'LAST_VISIT'	=> ($row['user_last_visit']) ?  $_CLASS['core_user']->format_date($row['user_last_visit']) : $_CLASS['core_user']->lang['BOT_NEVER'],
		'L_STATUS'		=> ($row['user_status'] == USER_ACTIVE) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
	));
}

$_CLASS['core_db']->free_result($result);

$_CLASS['core_template']->assign('ACTION', generate_link('system&amp;mode=bots', array('admin' => true))); 

$_CLASS['core_display']->display(false, 'admin/bots.html');

?>