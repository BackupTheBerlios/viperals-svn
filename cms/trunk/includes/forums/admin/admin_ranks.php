<?php
/***************************************************************************
 *                              admin_ranks.php
 *                            -------------------
 *   begin                : Thursday, Jul 12, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: admin_ranks.php,v 1.8 2003/09/07 16:52:50 psotfx Exp $
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

// needs work

// Do we have permission?
if (!$_CLASS['forums_auth']->acl_get('a_ranks'))
{
	trigger_error('NO_ADMIN');
}


$u_action = 'forums&amp;file=admin_ranks';

$_CLASS['core_user']->add_lang('admin_posting', 'forums');

// Set up general vars
$action = request_var('action', '');
$action = (isset($_POST['add'])) ? 'add' : $action;
$action = (isset($_POST['save'])) ? 'save' : $action;
$rank_id = request_var('id', 0);

$page_title = 'ACP_MANAGE_RANKS';

switch ($action)
{
	case 'save':
		
		$rank_title = request_var('title', '', true);
		$special_rank = request_var('special_rank', 0);
		$min_posts = ($special_rank) ? 0 : request_var('min_posts', 0);
		$rank_image = request_var('rank_image', '');

		// The rank image has to be a jpg, gif or png
		if ($rank_image != '' && !preg_match('#(\.gif|\.png|\.jpg|\.jpeg)$#i', $rank_image))
		{
			$rank_image = '';
		}

		if (!$rank_title)
		{
			trigger_error($_CLASS['core_user']->lang['NO_RANK_TITLE'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
		}

		$sql_ary = array(
			'rank_title'		=> $rank_title,
			'rank_special'		=> $special_rank,
			'rank_min'			=> $min_posts,
			'rank_image'		=> html_entity_decode($rank_image)
		);
		
		if ($rank_id)
		{
			$sql = 'UPDATE ' . FORUMS_RANKS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . " WHERE rank_id = $rank_id";
			$message = $_CLASS['core_user']->lang['RANK_UPDATED'];
		}
		else
		{
			$sql = 'INSERT INTO ' . FORUMS_RANKS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql_ary);
			$message = $_CLASS['core_user']->lang['RANK_ADDED'];
		}
		$_CLASS['core_db']->query($sql);

		$_CLASS['core_cache']->destroy('ranks');

		trigger_error($message . adm_back_link(generate_link($u_action, array('admin' => true))));

	break;

	case 'delete':

		// Ok, they want to delete their rank
		if ($rank_id)
		{
			$sql = 'DELETE FROM ' . FORUMS_RANKS_TABLE . "
				WHERE rank_id = $rank_id";
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . CORE_USERS_TABLE . "
				SET user_rank = 0
				WHERE user_rank = $rank_id";
			$_CLASS['core_db']->query($sql);

			$_CLASS['core_cache']->destroy('ranks');

			trigger_error($_CLASS['core_user']->lang['RANK_REMOVED'] . adm_back_link(generate_link($u_action, array('admin' => true))));
		}
		else
		{
			trigger_error($_CLASS['core_user']->lang['MUST_SELECT_RANK'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
		}

	break;

	case 'edit':
	case 'add':

		$data = $ranks = $existing_imgs = array();
		
		$sql = 'SELECT * 
			FROM ' . FORUMS_RANKS_TABLE . ' 
			ORDER BY rank_min ASC, rank_special ASC';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$existing_imgs[] = $row['rank_image'];

			if ($action === 'edit' && $rank_id == $row['rank_id'])
			{
				$ranks = $row;
			}
		}
		$_CLASS['core_db']->free_result($result);

		$imglist = filelist($config['ranks_path'], '');

		$edit_img = $filename_list = '';

		foreach ($imglist as $path => $img_ary)
		{
			foreach ($img_ary as $img)
			{
				$img = $path . $img; 

				if (!in_array($img, $existing_imgs) || $action == 'edit')
				{
					if ($ranks && $img == $ranks['rank_image'])
					{
						$selected = ' selected="selected"';
						$edit_img = $img;
					}
					else
					{
						$selected = '';
					}

					$filename_list .= '<option value="' . htmlspecialchars($img) . '"' . $selected . '>' . $img . '</option>';
				}
			}
		}

		$filename_list = '<option value=""' . (($edit_img == '') ? ' selected="selected"' : '') . '>----------</option>' . $filename_list;
		unset($existing_imgs, $imglist);

		$_CLASS['core_template']->assign_array(array(
			'S_EDIT'			=> true,
			'U_BACK'			=> generate_link($u_action, array('admin' => true)),
			'RANKS_PATH'		=> $config['ranks_path'],
			'U_ACTION'			=> generate_link($u_action . '&amp;id=' . $rank_id, array('admin' => true)),

			'RANK_TITLE'		=> (isset($ranks['rank_title'])) ? $ranks['rank_title'] : '',
			'S_FILENAME_LIST'	=> $filename_list,
			'RANK_IMAGE'		=> ($edit_img) ? $config['ranks_path'] . '/' . $edit_img : 'images/spacer.gif',
			'S_SPECIAL_RANK'	=> (!isset($ranks['rank_special']) || $ranks['rank_special']) ? true : false,
			'MIN_POSTS'			=> (isset($ranks['rank_min']) && !$ranks['rank_special']) ? $ranks['rank_min'] : 0)
		);
				
		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_ranks.html');
		return;

	break;
}

$_CLASS['core_template']->assign_array(array(
	'U_ACTION'		=> generate_link($u_action, array('admin' => true))
));

$sql = 'SELECT *
	FROM ' . FORUMS_RANKS_TABLE . '
	ORDER BY rank_min ASC, rank_special ASC, rank_title ASC';
$result = $_CLASS['core_db']->query($sql);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$_CLASS['core_template']->assign_vars_array('ranks', array(
		'S_RANK_IMAGE'		=> ($row['rank_image']) ? true : false,
		'S_SPECIAL_RANK'	=> ($row['rank_special']) ? true : false,

		'RANK_IMAGE'		=> $config['ranks_path'] . '/' . $row['rank_image'],
		'RANK_TITLE'		=> $row['rank_title'],
		'MIN_POSTS'			=> $row['rank_min'],

		'U_EDIT'			=> generate_link($u_action . '&amp;action=edit&amp;id=' . $row['rank_id'], array('admin' => true)),
		'U_DELETE'			=> generate_link($u_action . '&amp;action=delete&amp;id=' . $row['rank_id'], array('admin' => true))
	));	
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_ranks.html');

?>