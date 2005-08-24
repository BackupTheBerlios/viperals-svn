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

class ucp_groups extends module
{
	function ucp_groups($id, $mode)
	{
		global $_CLASS, $site_file_root;

		$_CLASS['core_user']->add_lang('groups');

		$submit	= isset($_POST['submit']) ? $_POST['submit'] : false;

		if ($submit && !empty($_POST['group_id']))
		{
			if (is_array($_POST['group_id']))
			{
				$group_id = array_map('intval', get_variable('group_id', 'REQUEST', false, 'array'));
			}
			else
			{
				if ($group_id = get_variable('group_id', 'REQUEST', false, 'interger'))
				{
					$group_id = array($group_id);
				}
				else
				{
					die;
				}
			}
//empty(array())
			require_once($site_file_root.'includes/functions_user.php');

			switch ($_POST['mode'])
			{
				case 'resign':
// need to get member status and add other group types ( pending members can resign from all )
					$sql = 'SELECT group_id	FROM  ' . GROUPS_TABLE . '
								WHERE group_type = ' . GROUP_SYSTEM .'
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
// users can be added 2x here, check to see if they are in the group first
					$sql = 'SELECT group_id, FROM ' . USER_GROUP_TABLE . '
						WHERE  group_id IN ('. implode(', ', $group_id) .')';
					$result = $_CLASS['core_db']->query($sql);

					$unset = array();

					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$unset[] = $row['group_id'];
					}
					$_CLASS['core_db']->free_result($result);

					$sql = 'SELECT group_id, group_status, group_type FROM  ' . GROUPS_TABLE . '
								WHERE group_id IN ('. implode(', ', $group_id) .')';
					$result = $_CLASS['core_db']->query($sql);

					$group_id = array();

					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$status = ($row['group_type'] == GROUP_UNRESTRAINED) ? STATUS_ACTIVE : STATUS_PENDING;

						if ($row['group_status'] == STATUS_ACTIVE)
						{
							$group_id[$status][] = $row['group_id'];
						}
					}
					$_CLASS['core_db']->free_result($result);

					foreach ($group_id as $status => $ids)
					{
						groups_user_add($ids, $_CLASS['core_user']->data['user_id'], $status);
					}
				break;		
			}
		}

		$error = $data = array();

		$sql = 'SELECT g.group_id, g.group_name, g.group_description, g.group_type, ug.member_status
			FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
			WHERE ug.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND g.group_id = ug.group_id
			ORDER BY g.group_type DESC, g.group_name';
		$result = $_CLASS['core_db']->query($sql);

		$group_id_ary = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$row['group_status'] = STATUS_ACTIVE;

			$block = ($row['member_status'] == STATUS_LEADER) ? 'leader' : (($row['member_status'] == STATUS_PENDING) ? 'pending' : 'member');

			$_CLASS['core_template']->assign_vars_array($block, array(
				'GROUP_ID'			=> $row['group_id'],
				'GROUP_NAME'		=> isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'		=> $row['group_description'],
				'GROUP_RESIGN'		=> ($row['group_type'] != GROUP_SYSTEM || ($row['group_type'] == GROUP_SPECIAL && $row['member_status'] == STATUS_PENDING)),
				'U_VIEW_GROUP'		=> generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']),
				'S_GROUP_DEFAULT'	=> ($row['group_id'] == $_CLASS['core_user']->data['group_id']) ? true : false
			));

			$group_id_ary[] = $row['group_id'];
		}
		$_CLASS['core_db']->free_result($result);

		$sql_and = 'NOT IN (' . GROUP_SYSTEM . ', ' . GROUP_HIDDEN . ')';

		$sql = 'SELECT group_id, group_name, group_description, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE group_id NOT IN (' . implode(', ', $group_id_ary) . ')
				AND group_status = '. STATUS_ACTIVE ."
			ORDER BY group_type DESC, group_name";
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$_CLASS['core_template']->assign_vars_array('nonmember', array(
				'GROUP_ID'		=> $row['group_id'],
				'GROUP_NAME'	=> isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'	=> $row['group_description'],
				'GROUP_APPLY'	=> true,
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