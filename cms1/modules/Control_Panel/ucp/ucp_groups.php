<?php
// -------------------------------------------------------------
//
// $Id: ucp_groups.php,v 1.1 2004/09/01 15:47:45 psotfx Exp $
//
// FILENAME  : ucp_groups.php
// STARTED   : Sun Jun 6, 2004
// COPYRIGHT : � 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------

class ucp_groups extends module
{
	function ucp_groups($id, $mode)
	{
		global $config, $db, $_CLASS, $SID, $phpEx;

		$_CLASS['core_user']->add_lang('groups');

		$submit		= (!empty($_POST['submit'])) ? true : false;
		$delete		= (!empty($_POST['delete'])) ? true : false;
		$error = $data = array();

		switch ($mode)
		{
			case 'membership':

				$sql = 'SELECT g.group_id, g.group_name, g.group_description, g.group_type, ug.group_leader, ug.user_pending
					FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
					WHERE ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
						AND g.group_id = ug.group_id
					ORDER BY g.group_type DESC, g.group_name';
				$result = $db->sql_query($sql);

				$group_id_ary = array();
				$leader_count = $member_count = $pending_count = 0;
				while ($row = $db->sql_fetchrow($result))
				{
					$block = ($row['group_leader']) ? 'leader' : (($row['user_pending']) ? 'pending' : 'member');

					$_CLASS['core_template']->assign_vars_array($block, array(
						'GROUP_ID'		=> $row['group_id'],
						'GROUP_NAME'	=> ($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
						'GROUP_DESC'	=> ($row['group_type'] <> GROUP_SPECIAL) ? $row['group_description'] : $_CLASS['core_user']->lang['GROUP_IS_SPECIAL'],
						'GROUP_SPECIAL'	=> ($row['group_type'] <> GROUP_SPECIAL) ? false : true,

						'U_VIEW_GROUP'	=> getlink('Members_List&amp;mode=group&amp;g=' . $row['group_id']),

						'S_GROUP_DEFAULT'	=> ($row['group_id'] == $_CLASS['core_user']->data['group_id']) ? true : false,
						'S_ROW_COUNT'		=> ${$block . '_count'}++,)
					);

					$group_id_ary[] = $row['group_id'];
				}
				$db->sql_freeresult($result);

				// Hide hidden groups unless user is an admin with group privileges
				$sql_and = ($_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? '<> ' . GROUP_SPECIAL : 'NOT IN (' . GROUP_SPECIAL . ', ' . GROUP_HIDDEN . ')';
				$sql = 'SELECT group_id, group_name, group_description, group_type
					FROM ' . GROUPS_TABLE . '
					WHERE group_id NOT IN (' . implode(', ', $group_id_ary) . ")
						AND group_type $sql_and
					ORDER BY group_type DESC, group_name";
				$result = $db->sql_query($sql);

				$nonmember_count = 0;
				while ($row = $db->sql_fetchrow($result))
				{

					$_CLASS['core_template']->assign_vars_array('nonmember', array(
						'GROUP_ID'		=> $row['group_id'],
						'GROUP_NAME'	=> ($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
						'GROUP_DESC'	=> $row['group_description'],
						'GROUP_SPECIAL'	=> ($row['group_type'] <> GROUP_SPECIAL) ? false : true,
						'GROUP_CLOSED'	=> ($row['group_type'] <> GROUP_CLOSED || $_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? false : true,

						'U_VIEW_GROUP'	=> getlink('Members_List&amp;mode=group&amp;g=' . $row['group_id']),

						'S_ROW_COUNT'	=> $nonmember_count++,)
					);
				}
				$db->sql_freeresult($result);

				$_CLASS['core_template']->assign(array(
					'S_CHANGE_DEFAULT'	=> ($_CLASS['auth']->acl_get('u_chggrp')) ? true : false,
					'S_LEADER_COUNT'	=> $leader_count,
					'S_MEMBER_COUNT'	=> $member_count,
					'S_PENDING_COUNT'	=> $pending_count,
					'S_NONMEMBER_COUNT'	=> $nonmember_count,
					
					'L_GROUPS_EXPLAIN'		=> $_CLASS['core_user']->lang['GROUPS_EXPLAIN'],
					'L_GROUP_DETAILS'		=> $_CLASS['core_user']->lang['GROUP_DETAILS'],
					'L_MARK'				=> $_CLASS['core_user']->lang['MARK'],
					'L_GROUP_LEADER'		=> $_CLASS['core_user']->lang['GROUP_LEADER'],
					'L_GROUP_MEMBER'		=> $_CLASS['core_user']->lang['GROUP_MEMBER'],
					'L_GROUP_PENDING'		=> $_CLASS['core_user']->lang['GROUP_PENDING'],
					'L_GROUP_NONMEMBER'		=> $_CLASS['core_user']->lang['GROUP_NONMEMBER'],
					'L_SUBMIT'				=> $_CLASS['core_user']->lang['SUBMIT'])
				);

				break;

			case 'manage':
				break;
		}

		$this->display($_CLASS['core_user']->lang['UCP_GROUPS'], 'ucp_groups_' . $mode . '.html');
	}
}

/*
	include($phpbb_root_path . 'includes/emailer.'.$phpEx);
	$emailer = new emailer($config['smtp_delivery']);

	$email_headers = 'From: ' . $config['board_email'] . "\nReturn-Path: " . $config['board_email'] . "\r\n";

	$emailer->use_template('group_request', $moderator['user_lang']);
	$emailer->email_address($moderator['user_email']);
	$emailer->set_subject();//$lang['Group_request']
	$emailer->extra_headers($email_headers);

	$emailer->assign_vars(array(
		'SITENAME' => $config['sitename'],
		'GROUP_MODERATOR' => $moderator['username'],
		'EMAIL_SIG' => str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']),

		'U_GROUPCP' => $server_url . '?' . 'g' . "=$group_id&validate=true")
	);
	$emailer->send();
	$emailer->reset();
*/

?>