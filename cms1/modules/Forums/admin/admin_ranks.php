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


// Do we have permission?
if (!$_CLASS['auth']->acl_get('a_ranks'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Check mode
if (isset($_REQUEST['mode']))
{
	$mode = $_REQUEST['mode'];
}
else
{
	// These could be entered via a form button
	if (isset($_POST['add']))
	{
		$mode = 'add';
	}
	else if (isset($_POST['save']))
	{
		$mode = 'save';
	}
	else
	{
		$mode = '';
	}
}

$rank_id = (isset($_GET['id'])) ? intval($_GET['id']) : 0;


//
switch ($mode)
{
	case 'edit':
	case 'add':

		$data = $ranks = $existing_imgs = array();
		$result = $_CLASS['core_db']->sql_query('SELECT * 
			FROM ' . RANKS_TABLE . ' 
			ORDER BY rank_special DESC, rank_min DESC');
		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			do
			{
				$existing_imgs[] = $row['rank_image'];
				if ($mode == 'edit' && $rank_id == $row['rank_id'])
				{
					$ranks = $row;
				}
			}
			while ($row = $_CLASS['core_db']->sql_fetchrow($result));
		}
		$_CLASS['core_db']->sql_freeresult($result);

		$imglist = filelist($config['ranks_path'], '');

		$edit_img = $filename_list = '';
		foreach ($imglist as $path => $img_ary)
		{
			foreach ($img_ary as $img)
			{
				$img = substr($path, 1) . (($path != '') ? '/' : '') . $img; 

				if (!in_array($img, $existing_imgs) || $mode == 'edit')
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
		unset($existing_imgs);
		unset($imglist);

		// They want to add a new rank, show the form.
		$s_hidden_fields = '<input type="hidden" name="mode" value="save" />';

		adm_page_header($_CLASS['core_user']->lang['RANKS']);

?>

<script language="javascript" type="text/javascript" defer="defer">
<!--

function update_image(newimage)
{
	document.image.src = (newimage) ? "<?php echo $config['ranks_path']; ?>/" + newimage : "../images/spacer.gif";
}

function update_image_dimensions()
{
	if (document.image.height && document.forms[0].height)
	{
		document.forms[0].height.value = document.image.height;
		document.forms[0].width.value = document.image.width;
	}
}

//-->
</script>

<h1><?php echo $_CLASS['core_user']->lang['RANKS']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['RANKS_EXPLAIN']; ?></p>

<form method="post" action="<?php echo generate_link('forums&amp;file=admin_ranks&amp;id='.$rank_id, array('admin' => true)); ?>"><table class="tablebg" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['RANKS']; ?></th>
	</tr>
	<tr>
		<td class="row1" width="40%"><?php echo $_CLASS['core_user']->lang['RANK_TITLE']; ?>: </td>
		<td class="row2"><input class="post" type="text" name="title" size="25" maxlength="40" value="<?php echo $ranks['rank_title']; ?>" /></td>
	</tr>
	<tr>
		<td class="row1" width="40%"><?php echo $_CLASS['core_user']->lang['RANK_IMAGE']; ?>:</td>
		<td class="row2"><table width="100%" cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td valign="middle"><select name="rank_image" onchange="update_image(this.options[selectedIndex].value);"><?php echo $filename_list ?></select></td>
				<td>&nbsp;&nbsp;</td>
				<td valign="middle"><img src="<?php echo ($edit_img) ? $config['ranks_path'] . '/' . $edit_img : '../images/spacer.gif' ?>" name="image" border="0" alt="" title="" onload="update_image_dimensions()" /></td>
			</tr>
		</table></td>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['RANK_SPECIAL']; ?>: </td>
		<td class="row2"><input type="radio" name="special_rank" value="1"<?php echo ($ranks['rank_special']) ? ' checked="checked"' : ''; ?> /><?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp;&nbsp;<input type="radio" name="special_rank" value="0"<?php echo (!$ranks['rank_special']) ? ' checked="checked"' : ''; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['RANK_MINIMUM']; ?>: </td>
		<td class="row2"><input class="post" type="text" name="min_posts" size="5" maxlength="10" value="<?php echo ($ranks['rank_special']) ? '' : $ranks['rank_min']; ?>" /></td>
	</tr>
	<tr>
		<td class="cat" colspan="2" align="center"><?php echo $s_hidden_fields; ?><input type="submit" name="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" class="btnmain" />&nbsp;&nbsp;<input type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" class="btnlite" /></td>
	</tr>
</table></form>

<?php

		adm_page_footer();

		break;

	case 'save':

		//
		// Ok, they sent us our info, let's update it.
		//

		$rank_id = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
		$rank_title = (isset($_POST['title'])) ? trim($_POST['title']) : '';
		$special_rank = (!empty($_POST['special_rank'])) ? 1 : 0;
		$min_posts = (isset($_POST['min_posts'])) ? intval($_POST['min_posts']) : -1;
		$rank_image = (isset($_POST['rank_image'])) ? trim(htmlspecialchars($_POST['rank_image'])) : '';

		if ($special_rank == 1)
		{
			$min_posts = -1;
		}

		// The rank image has to be a jpg, gif or png
		if ($rank_image != '' && !preg_match('#(\.gif|\.png|\.jpg|\.jpeg)$#i', $rank_image))
		{
			$rank_image = '';
		}

		if ($rank_id)
		{
			$sql = "UPDATE " . RANKS_TABLE . "
				SET rank_title = '" . $_CLASS['core_db']->sql_escape($rank_title) . "', rank_special = $special_rank, rank_min = $min_posts, rank_image = '" . $_CLASS['core_db']->sql_escape($rank_image) . "'
				WHERE rank_id = $rank_id";

			$message = $_CLASS['core_user']->lang['RANK_UPDATED'];
		}
		else
		{
			$sql = "INSERT INTO " . RANKS_TABLE . " (rank_title, rank_special, rank_min, rank_image)
				VALUES ('" . $_CLASS['core_db']->sql_escape($rank_title) . "', $special_rank, $min_posts, '" . $_CLASS['core_db']->sql_escape($rank_image) . "')";

			$message = $_CLASS['core_user']->lang['RANK_ADDED'];
		}
		$_CLASS['core_db']->sql_query($sql);

		$_CLASS['core_cache']->destroy('ranks');

		trigger_error($message);

		break;

	case 'delete':

		// Ok, they want to delete their rank
		$rank_id = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;

		if ($rank_id)
		{
			$sql = "DELETE FROM " . RANKS_TABLE . "
				WHERE rank_id = $rank_id";
			$_CLASS['core_db']->sql_query($sql);

			$sql = "UPDATE " . USERS_TABLE . "
				SET user_rank = 0
				WHERE user_rank = $rank_id";
			$_CLASS['core_db']->sql_query($sql);

			$_CLASS['core_cache']->destroy('ranks');

			trigger_error($_CLASS['core_user']->lang['RANK_REMOVED']);
		}
		else
		{
			trigger_error($_CLASS['core_user']->lang['MUST_SELECT_RANK']);
		}

		break;

	default:

		adm_page_header($_CLASS['core_user']->lang['RANKS']);

?>

<h1><?php echo $_CLASS['core_user']->lang['RANKS']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['RANKS_EXPLAIN']; ?></p>

<form method="post" action="<?php echo generate_link('forums&amp;file=admin_ranks', array('admin' => true)); ?>"><table class="tablebg" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th><?php echo $_CLASS['core_user']->lang['RANK_IMAGE']; ?></th>
		<th><?php echo $_CLASS['core_user']->lang['RANK_TITLE']; ?></th>
        <th><?php echo $_CLASS['core_user']->lang['RANK_MINIMUM']; ?></th>
		<th><?php echo $_CLASS['core_user']->lang['ACTION']; ?></th>
	</tr>
<?php

		// Show the default page
		$sql = "SELECT * FROM " . RANKS_TABLE . "
			ORDER BY rank_min ASC, rank_special ASC";
		$result = $_CLASS['core_db']->sql_query($sql);

		if ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$row_class = 'row2';
			
			do
			{
				$row_class = ($row_class != 'row1') ? 'row1' : 'row2';

?>
	<tr>
		<td class="<?php echo $row_class; ?>" align="center"><?php
	
				if ($row['rank_image'])
				{
		
?><img src="<?php echo $config['ranks_path'] . '/' . $row['rank_image']; ?>"" border="0" alt="<?php echo $row['rank_title']; ?>" title="<?php echo $row['rank_title']; ?>" /><?php

				}
				else
				{
					echo '-';
				}

?></td>
		<td class="<?php echo $row_class; ?>" align="center"><?php echo $row['rank_title']; ?></td>
        <td class="<?php echo $row_class; ?>" align="center"><?php echo ($row['rank_special']) ? '-' : $row['rank_min']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center">&nbsp;<a href="<?php echo generate_link('forums&amp;file=admin_ranks&amp;mode=edit&amp;id=' . $row['rank_id'], array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['EDIT']; ?></a> | <a href="<?php echo generate_link('forums&amp;file=admin_ranks&amp;mode=delete&amp;id=' . $row['rank_id'], array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['DELETE']; ?></a>&nbsp;</td>
	</tr>
<?php

			}
			while ($row = $_CLASS['core_db']->sql_fetchrow($result));
		}

?>
	<tr>
		<td class="cat" colspan="5" align="center"><input type="submit" class="btnmain" name="add" value="<?php echo $_CLASS['core_user']->lang['ADD_RANK']; ?>" /></td>
	</tr>
</table></form>

<?php

		adm_page_footer();

		break;
}

?>