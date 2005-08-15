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
		global $_CLASS, $site_file_root;

		$_CLASS['core_user']->add_lang('groups');

		$submit	= !empty($_POST['submit']);

		if (is_array($_POST['group_id']))
		{
			$group_id = array_map('intval', get_variable('group_id', 'REQUEST', false, 'array'));
		}
		elseif 
		{
			if ($group_id = get_variable('group_id', 'REQUEST', false, 'interger'))
			{
				$group_id = array($group_id);
			}
		}
	

		if ($submit && !empty($group_id))
		{
			require_once($site_file_root.'includes/functions_user.php');

			switch ($_POST['mode'])
			{
				case 'resign':
					$sql = 'SELECT group_id	FROM  ' . GROUPS_TABLE . '
								WHERE group_type = ' . GROUP_SPECIAL .'
								AND group_id IN ('. implode(', ', $group_id) .')';
					$result = $_CLASS['core_db']->query($sql);

					$special = array();
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$special[] = $row['user_id'];
					}
					$_CLASS['core_db']->free_result($result);

					$group_id = array_diff($group_id, $special);
					unset($special);

					groups_user_remove($group_id, $_CLASS['core_user']->data['user_id']);
				break;

				case 'apply':
					$sql = 'SELECT group_id, group_status, group_type FROM  ' . GROUPS_TABLE . '
								WHERE group_type IN (' . GROUP_REQUEST .', ' . GROUP_UNRESTRAINED .')
								AND group_id IN ('. implode(', ', $group_id) .')';
					$result = $_CLASS['core_db']->query($sql);

					$group_id = array()

					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						if ($row['user_id'] == STATUS_ACTIVE)
						{
							$group_id[] = $row['user_id'];
						}
					}
					$_CLASS['core_db']->free_result($result);

					groups_user_remove($group_ids, $_CLASS['core_user']->data['user_id']);
				break;		
			}
		}

		$error = $data = array();

		$sql = 'SELECT g.group_id, g.group_name, g.group_description, g.group_type, ug.user_status
			FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
			WHERE ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND g.group_id = ug.group_id
			ORDER BY g.group_type DESC, g.group_name';
		$result = $_CLASS['core_db']->query($sql);

		$group_id_ary = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$row['group_status'] = STATUS_ACTIVE;

			$block = ($row['user_status'] == STATUS_LEADER) ? 'leader' : (($row['user_status'] == STATUS_PENDING) ? 'pending' : 'member');

			$_CLASS['core_template']->assign_vars_array($block, array(
				'GROUP_ID'			=> $row['group_id'],
				'GROUP_NAME'		=> isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'		=> $row['group_description'],
				'GROUP_RESIGN'		=> ($row['group_type'] != GROUP_SYSTEM || ($row['group_type'] == GROUP_SPECIAL && $row['user_status'] == STATUS_PENDING),
				'U_VIEW_GROUP'		=> generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']),
				'S_GROUP_DEFAULT'	=> ($row['group_id'] == $_CLASS['core_user']->data['group_id']) ? true : false
			));

			$group_id_ary[] = $row['group_id'];
		}
		$_CLASS['core_db']->free_result($result);

		// Hide hidden groups unless user is an admin with group privileges
		$sql_and = ($_CLASS['auth']->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? '<> ' . GROUP_SYSTEM : 'NOT IN (' . GROUP_SYSTEM . ', ' . GROUP_HIDDEN . ')';

		$sql = 'SELECT group_id, group_name, group_description, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE group_id NOT IN (' . implode(', ', $group_id_ary) . ")
				AND group_type $sql_and
			ORDER BY group_type DESC, group_name";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$row['group_status'] = STATUS_ACTIVE;

			$_CLASS['core_template']->assign_vars_array('nonmember', array(
				'GROUP_ID'		=> $row['group_id'],
				'GROUP_NAME'	=> isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'	=> $row['group_description'],
				'GROUP_APPLY'	=> ($row['group_type'] != GROUP_SYSTEM && $row['group_status'] == STATUS_ACTIVE),
				'U_VIEW_GROUP'	=> generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id'])
			));
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign(array(
		'S_DISPLAY_FORM', true,
		'S_UCP_ACTION' => ''
		));

		$this->display($_CLASS['core_user']->get_lang('UCP_GROUPS'), 'ucp_groups_membership.html');
	}
}

?>