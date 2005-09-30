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
				$group_id = array_unique(get_variable('group_id', 'REQUEST', array(), 'array:int'));
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
			
			if (empty($group_id))
			{
				die; //temp
			}

			require_once($site_file_root.'includes/functions_user.php');

			switch ($_POST['mode'])
			{
				case 'resign':
					$sql = 'SELECT m.member_status, g.group_id, g.group_type
								FROM ' . USER_GROUP_TABLE . ' m, ' . GROUPS_TABLE . ' g 
								WHERE m.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
								AND m.group_id IN ('. implode(', ', $group_id) .')';

					$result = $_CLASS['core_db']->query($sql);

					$unset = array();
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						if ($row['group_type'] == GROUP_SYSTEM && $row['group_type'] == GROUP_SPECIAL && $row['member_status'] != STATUS_PENDING)
						{
							$unset[] = $row['user_id'];
						}
					}
					$_CLASS['core_db']->free_result($result);

					$group_id = array_diff($group_id, $unset);
					unset($unset);

					if (!empty($group_id))
					{
						groups_user_remove($group_id, $_CLASS['core_user']->data['user_id']);
					}
				break;

				case 'apply':
					$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . '
						WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
						AND group_id IN ('. implode(', ', $group_id) .')';

					$result = $_CLASS['core_db']->query($sql);

					$unset = array();
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$unset[] = $row['group_id'];
					}
					$_CLASS['core_db']->free_result($result);

					$group_id = array_diff($group_id, $unset);
					unset($unset);

					if (!empty($group_id))
					{
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

		$group_array = array();

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$row['group_status'] = STATUS_ACTIVE;

			$block = ($row['member_status'] == STATUS_LEADER) ? 'leader' : (($row['member_status'] == STATUS_PENDING) ? 'pending' : 'member');

			$_CLASS['core_template']->assign_vars_array($block, array(
				'GROUP_ID'			=> $row['group_id'],
				'GROUP_NAME'		=> isset($_CLASS['core_user']->lang['G_' . $row['group_name']]) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name'],
				'GROUP_DESC'		=> $row['group_description'],
				'GROUP_RESIGN'		=> ($row['member_status'] == STATUS_PENDING || ($row['group_type'] != GROUP_SYSTEM && $row['group_type'] != GROUP_SPECIAL)),
				'U_VIEW_GROUP'		=> generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']),
				'S_GROUP_DEFAULT'	=> ($row['group_id'] == $_CLASS['core_user']->data['user_group']) ? true : false
			));

			$group_array[] = $row['group_id'];
		}
		$_CLASS['core_db']->free_result($result);

		$sql_and = 'AND group_type NOT IN (' . GROUP_SYSTEM . ', ' . GROUP_HIDDEN . ')';

		$sql = 'SELECT group_id, group_name, group_description, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE group_id NOT IN (' . implode(', ', $group_array) . ')
				AND group_status = '. STATUS_ACTIVE ." $sql_and
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

		$_CLASS['core_template']->assign('S_UCP_ACTION', generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"));

		$this->display($_CLASS['core_user']->get_lang('UCP_GROUPS'), 'ucp_groups_membership.html');
	}
}

?>