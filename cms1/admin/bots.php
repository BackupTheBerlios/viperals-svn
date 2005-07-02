<?php

$option = get_variable('option', 'GET', false);
$id = (int) get_variable('id', 'GET', false);

switch ($option)
{
	case 'activate':

		if ($id)
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_type = '.USER_BOT_ACTIVE.'
				WHERE user_type = '.USER_BOT_INACTIVE.' AND user_id ='.$id;
			$_CLASS['core_db']->sql_query($sql);
		}
		break;

	case 'deactivate':

		if ($id)
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_type = '.USER_BOT_INACTIVE.'
				WHERE user_type = '.USER_BOT_ACTIVE.' AND user_id ='.$id;
			$_CLASS['core_db']->sql_query($sql);
		}
		break;

	case 'delete':

		if ($id)
		{
			$sql = 'SELECT user_id, user_type
				FROM ' . USERS_TABLE . ' 
				WHERE user_id = '.$id;

			$result = $_CLASS['core_db']>sql_query($sql);
			$row = $_CLASS['core_db']>sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);

			if (!in_array($row['user_type'], array(USER_BOT_ACTIVE, USER_BOT_INACTIVE)))
			{
				continue;
			}

// remove permissions also
			foreach (array(USERS_TABLE, USER_GROUP_TABLE) as $table)
			{
				$sql = "DELETE FROM $table
					WHERE user_id = $id";
				$_CLASS['core_db']->sql_query($sql);
			}

			trigger_error($_CLASS['core_user']->lang['BOT_DELETED']);
		}
		break;
}

$sql = 'SELECT user_id, username, user_type, user_lastvisit 
	FROM ' . USERS_TABLE . ' u
	WHERE user_type IN (' . USER_BOT_ACTIVE . ', ' . USER_BOT_INACTIVE . ')
		ORDER BY user_lastvisit DESC';

$result = $_CLASS['core_db']->sql_query($sql);

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$_CLASS['core_template']->assign_vars_array('admin_bots', array(
		'ACTIVE'		=> ($row['user_type'] == USER_BOT_ACTIVE) ? true : false,
		'NAME'			=> $row['username'],
		'DELETE_LINK'	=> generate_link('system&amp;mode=bots&amp;option=delete&amp;id='.$row['user_id'], array('admin' => true)),
		'STATUS_LINK'	=> generate_link('system&amp;mode=bots&amp;option='.(($row['user_type'] == USER_BOT_ACTIVE) ? 'deactivate' :  'activate').'&amp;id='.$row['user_id'], array('admin' => true)),
		'EDIT_LINK'		=> generate_link('system&amp;mode=bots&amp;options=edite&amp;id='.$row['user_id'], array('admin' => true)),
		'LAST_VISIT'	=> ($row['user_lastvisit']) ?  $_CLASS['core_user']->format_date($row['user_lastvisit']) : $_CLASS['core_user']->lang['BOT_NEVER'],
		'L_STATUS'		=> ($block['active']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
	));
}

$_CLASS['core_db']->sql_freeresult($result);

$_CLASS['core_template']->assign('ACTION', generate_link('system&amp;mode=bots', array('admin' => true))); 

$_CLASS['core_display']->display_head();
$_CLASS['core_template']->display('admin/system/bots.html');
$_CLASS['core_display']->display_footer();

?>