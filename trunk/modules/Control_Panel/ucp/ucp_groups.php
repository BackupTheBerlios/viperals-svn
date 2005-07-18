<?php
// -------------------------------------------------------------
//
// $Id: ucp_groups.php,v 1.1 2004/09/01 15:47:45 psotfx Exp $
//
// FILENAME  : ucp_groups.php
// STARTED   : Sun Jun 6, 2004
// COPYRIGHT : © 2001, 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------

class ucp_groups extends module
{
	function ucp_groups($id, $mode)
	{
		global $config, $_CLASS, $SID;

		$_CLASS['core_user']->add_lang('groups');

		$submit	= (!empty($_POST['submit'])) ? true : false;
		$delete	= (!empty($_POST['delete'])) ? true : false;
		
		$error = $data = array();

		$sql = 'SELECT g.group_id, g.group_name, g.group_description, g.group_type, ug.user_status
			FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
			WHERE ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND g.group_id = ug.group_id
			ORDER BY g.group_type DESC, g.group_name';
		$result = $_CLASS['core_db']->sql_query($sql);

		$group_id_ary = array();
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$block = ($row['user_status'] == STATUS_LEADER) ? 'leader' : (($row['user_status'] == STATUS_PENDING) ? 'pending' : 'member');

			$_CLASS['core_template']->assign_vars_array($block, array(
				'GROUP_ID'			=> $row['group_id'],
				'GROUP_NAME'		=> ($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'		=> ($row['group_type'] <> GROUP_SPECIAL) ? $row['group_description'] : $_CLASS['core_user']->lang['GROUP_IS_SPECIAL'],
				'GROUP_SPECIAL'		=> ($row['group_type'] <> GROUP_SPECIAL) ? false : true,

				'U_VIEW_GROUP'		=> generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']),
				'S_GROUP_DEFAULT'	=> ($row['group_id'] == $_CLASS['core_user']->data['group_id']) ? true : false
			));

			$group_id_ary[] = $row['group_id'];
		}

		$_CLASS['core_db']->sql_freeresult($result);

		// Hide hidden groups unless user is an admin with group privileges
		$sql_and = ($_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? '<> ' . GROUP_SPECIAL : 'NOT IN (' . GROUP_SPECIAL . ', ' . GROUP_HIDDEN . ')';

		$sql = 'SELECT group_id, group_name, group_description, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE group_id NOT IN (' . implode(', ', $group_id_ary) . ")
				AND group_type $sql_and
			ORDER BY group_type DESC, group_name";
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$_CLASS['core_template']->assign_vars_array('nonmember', array(
				'GROUP_ID'		=> $row['group_id'],
				'GROUP_NAME'	=> ($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'	=> $row['group_description'],
				'GROUP_SPECIAL'	=> ($row['group_type'] <> GROUP_SPECIAL) ? false : true,
				'GROUP_CLOSED'	=> ($row['group_type'] <> GROUP_CLOSED || $_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? false : true,

				'U_VIEW_GROUP'	=> generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id'])
			));
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$_CLASS['core_template']->assign(array(
			'S_CHANGE_DEFAULT' 	=> ($_CLASS['auth']->acl_get('u_chggrp')),
			'S_DISPLAY_FORM'	=> true,
		));

		$this->display($_CLASS['core_user']->get_lang('UCP_GROUPS'), 'ucp_groups_membership.html');
	}
}

?>