<?php
/** 
*
* @package acp
* @version $Id: admin_icons.php,v 1.14 2005/04/30 14:12:20 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
*/
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Do we have general permissions?
if (!$_CLASS['auth']->acl_get('a_icons'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Grab some basic parameters
$mode = request_var('mode', '');
$action = request_var('action', '');
$action = (isset($_POST['add'])) ? 'add' : $action;
$action = (isset($_POST['edit'])) ? 'edit' : $action;
$id = request_var('id', 0);

// What are we working on?
switch ($mode)
{
/*	case 'smilies':
		$table = FORUMS_SMILIES_TABLE;
		$lang = 'SMILIES';
		$fields = 'smiley';
		$img_path = $config['smilies_path'];
	break;*/

	//case 'icons':
	default:
		$table = FORUMS_ICONS_TABLE;
		$lang = 'ICONS';
		$fields = 'icons';
		$img_path = $config['icons_path'];
	break;
}

// Clear some arrays
$_images = $_paks = array();
$notice = '';


// Grab file list of paks and images
if ($action == 'edit' || $action == 'add' || $action == 'import')
{
	$imglist = filelist($img_path, '');
	
	foreach ($imglist as $path => $img_ary)
	{
		foreach ($img_ary as $img)
		{
			$img_size = @getimagesize($img_path . '/' . $path . $img);

			$_images[$path.$img]['file'] = $path.$img;
			$_images[$path.$img]['width'] = $img_size[0];
			$_images[$path.$img]['height'] = $img_size[1];
		}
	}
	unset($imglist);

	$dir = @opendir($img_path);
	while ($file = @readdir($dir))
	{
		if (is_file($img_path . '/' . $file) && preg_match('#\.pak$#i', $file))
		{
			$_paks[] = $file;
		}
	}
	@closedir($dir);
}

$data = array();

// What shall we do today? Oops, I believe that's trademarked ...
switch ($action)
{
	case 'edit':
		unset($_images);
		$_images = array();

	case 'add':

		$order_list = '';

		$sql = "SELECT * 
			FROM $table 
			ORDER BY {$fields}_order " . (($id || $action == 'add') ? 'DESC' : 'ASC');
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			do
			{
				if ($action == 'add')
				{
					unset($_images[$row[$fields . '_url']]);
				}

				if ($row[$fields . '_id'] == $id)
				{
					$after = TRUE;
					$data[$row[$fields . '_url']] = $row;
				}
				else
				{
					if ($action == 'edit' && !$id)
					{
						$data[$row[$fields . '_url']] = $row;
					}
					
					$selected = '';
					if (!empty($after))
					{
						$selected = ' selected="selected"';
						$after = FALSE;
					}

					$after_txt = ($mode == 'smilies') ? $row['code'] : $row['icons_url'];
					$order_list = '<option value="' . ($row[$fields . '_order']) . '"' . $selected . '>' . sprintf($_CLASS['core_user']->lang['AFTER_' . $lang], ' -&gt; ' . htmlspecialchars($after_txt)) . '</option>' . $order_list;
				}
			}
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
		}
		$_CLASS['core_db']->free_result($result);

		$order_list = '<option value="1"' . ((!isset($after)) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['FIRST'] . '</option>' . $order_list;

		if ($action == 'add')
		{
			$data = $_images;
		}

		$colspan = (($mode == 'smilies') ? '7' : '5');
		$colspan += ($id) ? 1 : 0;
		$colspan += ($action == 'add') ? 2 : 0;

		adm_page_header($_CLASS['core_user']->lang[$lang]);

?>

<h1><?php echo $_CLASS['core_user']->lang[$lang]; ?></h1>

<p><?php echo $_CLASS['core_user']->lang[$lang .'_EXPLAIN']; ?></p>

<form method="post" action="<?php echo generate_link("Forums&amp;file=admin_icons&amp;mode=$mode&amp;action=" . (($action == 'add') ? 'create' : 'modify'), array('admin' => true)); ?>">
<table class="tablebg" cellspacing="1" cellpadding="4" border="0" align="center">
<tr>
	<th colspan="<?php echo $colspan; ?>"><?php echo $_CLASS['core_user']->lang[$lang . '_CONFIG'] ?></th>
</tr>
<tr>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_URL'] ?></td>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_LOCATION'] ?></td>
<?php
	if ($mode == 'smilies')
	{
?>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_CODE'] ?></td>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_EMOTION'] ?></td>
<?php
	}
?>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_WIDTH'] ?></td>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_HEIGHT'] ?></td>
	<td class="cat"><?php echo $_CLASS['core_user']->lang['DISPLAY_ON_POSTING'] ?></td>
<?php
	if ($id || $action == 'add')
	{
?>
	<td class="cat"><?php echo $_CLASS['core_user']->lang[$lang . '_ORDER'] ?></td>
<?php
	}
?>
<?php
	if ($action == 'add')
	{
?>
	<td class="cat"><?php echo $_CLASS['core_user']->lang['ADD'] ?></td>
<?php
	}
?>
</tr>
<?php
	$row = 0;
	
	foreach ($data as $img => $img_row)
	{
		$row_class = (($row % 2) == 0) ? 'row1' : 'row2';
?>
<tr>
	<td align="center" class="<?php echo $row_class; ?>"><img src="<?php echo $img_path . '/' . $img ?>" border="0" alt="" title="" /><input type="hidden" name="image[<?php echo $img; ?>]" value="1" /></td>
	<td valign="top" class="<?php echo $row_class; ?>">[<?php echo $img; ?>]</td>
<?php

	if ($mode == 'smilies')
	{

?>
		<td class="<?php echo $row_class; ?>"><input class="post" type="text" name="code[<?php echo $img; ?>]" value="<?php echo (!empty($img_row['code'])) ? $img_row['code'] : '' ?>" size="10" /></td>
		<td class="<?php echo $row_class; ?>"><input class="post" type="text" name="emotion[<?php echo $img; ?>]" value="<?php echo (!empty($img_row['emotion'])) ? $img_row['emotion'] : '' ?>" size="10" /></td>
<?php

	}

?>
	<td class="<?php echo $row_class; ?>"><input class="post" type="text" size="3" name="width[<?php echo $img; ?>]" value="<?php echo (!empty($img_row[$fields .'_width'])) ? $img_row[$fields .'_width'] : $img_row['width'] ?>" /></td>
	<td class="<?php echo $row_class; ?>"><input class="post" type="text" size="3" name="height[<?php echo $img; ?>]" value="<?php echo (!empty($img_row[$fields .'_height'])) ? $img_row[$fields .'_height'] : $img_row['height'] ?>" /></td>
	<td class="<?php echo $row_class; ?>"><input type="checkbox" name="display_on_posting[<?php echo $img; ?>]"<?php echo (!empty($img_row['display_on_posting']) || $action == 'add') ? ' checked="checked"' : '' ?> /></td>
<?php
		if ($id || $action == 'add')
		{
?>
			<td class="<?php echo $row_class; ?>"><select name="order[<?php echo $img; ?>]"><?php echo $order_list ?></select></td>
<?php
		}
	
		if ($action == 'add')
		{
?>
			<td class="<?php echo $row_class; ?>"><input type="checkbox" name="add_img[<?php echo $img; ?>]" value="1" /><?php
	
	}
?>
</tr>
<?php
	if (isset($img_row[$fields . '_id']))
	{

?><input type="hidden" name="id[<?php echo $img; ?>]" value="<?php echo $img_row[$fields . '_id'] ?>" /><?php
	
	}
	$row++;
}
?>
<tr>
	<td class="cat" colspan="<?php echo $colspan; ?>" align="center"><?php 
			
	
?><input class="btnmain" type="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT'] ?>" /></td>
	</tr>
</table></form>
<?php

		adm_page_footer();
		break;

	case 'create':
	case 'modify':

		// Get items to create/modify
		$images = (isset($_POST['image'])) ? array_keys($_POST['image']) : array();
		
		// Now really get the items
		$image_id		= (isset($_POST['id'])) ? array_map('intval', $_POST['id']) : array();
		$image_order	= (isset($_POST['order'])) ? array_map('intval', $_POST['order']) : array();
		$image_width	= (isset($_POST['width'])) ? array_map('intval', $_POST['width']) : array();
		$image_height	= (isset($_POST['height'])) ? array_map('intval', $_POST['height']) : array();
		$image_add		= (isset($_POST['add_img'])) ? array_map('intval', $_POST['add_img']) : array();
		$image_emotion	= request_var('emotion', '');
		$image_code		= request_var('code', '');
		$image_display_on_posting = (isset($_POST['display_on_posting'])) ? array_map('intval', $_POST['display_on_posting']) : array();

		foreach ($images as $image)
		{
			if (($mode == 'smilies' && ($image_emotion[$image] == '' || $image_code[$image] == '')) ||
				($action == 'create' && !isset($image_add[$image])))
			{
			}
			else
			{
				if ($image_width[$image] == 0 || $image_height[$image] == 0)
				{
					$img_size = @getimagesize($img_path . '/' . $image);
					$image_width[$image] = $img_size[0];
					$image_height[$image] = $img_size[1];
				}

				$img_sql = array(
					$fields . '_url'	=>	$image,
					$fields . '_width'	=>	$image_width[$image],
					$fields . '_height'	=>	$image_height[$image],
					'display_on_posting'=>	(isset($image_display_on_posting[$image])) ? 1 : 0,
				);

				if ($mode == 'smilies')
				{
					$img_sql = array_merge($img_sql, array(
						'emotion'	=>	$image_emotion[$image],
						'code'		=>	$image_code[$image])
					);
				}
				
				if (!empty($image_order[$image]))
				{
					$img_sql = array_merge($img_sql, array(
						$fields . '_order'	=>	$image_order[$image] . '.5')
					);
				}

				if ($action == 'modify')
				{
					$sql = "UPDATE $table
						SET " . $_CLASS['core_db']->sql_build_array('UPDATE', $img_sql) . " 
						WHERE {$fields}_id = " . $image_id[$image];
					$_CLASS['core_db']->query($sql);
				}
				else
				{
					$sql = "INSERT INTO $table " . $_CLASS['core_db']->sql_build_array('INSERT', $img_sql);
					$_CLASS['core_db']->query($sql);
				}

				$update = FALSE;

				if ($action == 'modify' && !empty($image_order[$image]))
				{
					$update = TRUE;

					$result = $_CLASS['core_db']->query("SELECT {$fields}_order 
						FROM $table
						WHERE {$fields}_id = " . $image_id[$image]);
					$order_old = $_CLASS['core_db']->sql_fetchfield($fields . '_order', 0, $result);

					if ($order_old == $image_order[$image])
					{
						$update = FALSE;
					}

					if ($order_old > $image_order[$image])
					{
						$sign = '+';
						$where = $fields . '_order >= ' . $image_order[$image] . " AND {$fields}_order < $order_old";
					}
					else if ($order_old < $image_order[$image])
					{
						$sign = '-';
						$where = "{$fields}_order > $order_old AND {$fields}_order < " . $image_order[$image];
						$sql[$fields . '_order'] = $image_order[$image] - 1;
					}
				}

				if ($update)
				{
					$sql = "UPDATE $table
						SET {$fields}_order = {$fields}_order $sign 1
						WHERE $where";
					$_CLASS['core_db']->query($sql);
				}
			
			}
		}
		
		$_CLASS['core_cache']->destroy('icons');

		if ($action == 'modify')
		{
			trigger_error($_CLASS['core_user']->lang[$lang . '_EDITED']);
		}
		else
		{
			trigger_error($_CLASS['core_user']->lang[$lang . '_ADDED']);
		}

		break;

	case 'import':

		$pak = request_var('pak', '');
		$current = request_var('current', '');

		if ($pak != '')
		{
			$order = 0;

			// The user has already selected a smilies_pak file
			if ($current == 'delete')
			{
				$_CLASS['core_db']->query("TRUNCATE $table");

				switch ($mode)
				{
					case 'smilies':
						break;

					case 'icons':
						// Reset all icon_ids
						$_CLASS['core_db']->query('UPDATE ' . TOPICS_TABLE . ' 
							SET icon_id = 0');
						$_CLASS['core_db']->query('UPDATE ' . POSTS_TABLE . ' 
							SET icon_id = 0');
						break;
				}
			}
			else 
			{
				$cur_img = array();

				$field_sql = ($mode == 'smilies') ? 'code' : 'icons_url';
				$result = $_CLASS['core_db']->query("SELECT $field_sql FROM $table");

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					++$order;
					$cur_img[$row[$field_sql]] = 1;
				}
				$_CLASS['core_db']->free_result($result);
			}

			if (!($pak_ary = @file($img_path . '/' . $pak)))
			{
				trigger_error('Could not read pak file', E_USER_ERROR);
			}

			foreach ($pak_ary as $pak_entry)
			{
				$data = array();
				if (preg_match_all("#'(.*?)', #", $pak_entry, $data))
				{
					if ((sizeof($data[1]) != 3 && $mode == 'icons') || 
						(sizeof($data[1]) != 5 && $mode == 'smilies'))
					{
						trigger_error($_CLASS['core_user']->lang['WRONG_PAK_TYPE']);
					}

					$img = stripslashes($data[1][0]);
					$width = stripslashes($data[1][1]);
					$height = stripslashes($data[1][2]);
					if (isset($data[1][3]) && isset($data[1][4]))
					{
						$emotion = stripslashes($data[1][3]);
						$code = htmlentities(stripslashes($data[1][4]));
					}

					if ($current == 'replace' && 
						(($mode == 'smilies' && !empty($cur_img[$code])) || 
						($mode == 'icons' && !empty($cur_img[$img]))))
					{
						$replace_sql = ($mode == 'smilies') ? $code : $img;
						$sql = array(
							$fields . '_url'	=>	$img,
							$fields . '_height'	=>	(int) $height,
							$fields . '_width'	=>	(int) $width,
						);
						if ($mode == 'smilies')
						{
							$sql = array_merge($sql, array(
								'emotion'	=>	$emotion
							));
						}

						$_CLASS['core_db']->query("UPDATE $table SET " . $_CLASS['core_db']->sql_build_array('UPDATE', $sql) . " 
							WHERE $field_sql = '" . $_CLASS['core_db']->sql_escape($replace_sql) . "'");
					}
					else
					{
						++$order;

						$sql = array(
							$fields . '_url'	=>	$img,
							$fields . '_height'	=>	(int) $height,
							$fields . '_width'	=>	(int) $width,
							$fields . '_order'	=>	(int) $order,
						);

						if ($mode == 'smilies')
						{
							$sql = array_merge($sql, array(
								'code'		=>	$code,
								'emotion'	=>	$emotion
							));
						}
						$_CLASS['core_db']->query("INSERT INTO $table " . $_CLASS['core_db']->sql_build_array('INSERT', $sql));
					}

				}
			}

			$_CLASS['core_cache']->destroy('icons');
			trigger_error($_CLASS['core_user']->lang[$lang . '_IMPORT_SUCCESS']);
		}
		else
		{
			$pak_options = '';

			foreach ($_paks as $pak)
			{
				$pak_options .= '<option value="' . $pak . '">' . htmlspecialchars($pak) . '</option>';
			}

			adm_page_header($_CLASS['core_user']->lang[$lang]);

?>
<h1><?php echo $_CLASS['core_user']->lang[$lang] ?></h1>

<p><?php echo $_CLASS['core_user']->lang[$lang .'_EXPLAIN'] ?></p>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_icons&amp;mode=' . $mode . '&amp;action=import', array('admin' => true)); ?>">
<table class="tablebg" cellspacing="1" cellpadding="4" border="0" align="center">
<tr>
	<th colspan="2"><?php echo $_CLASS['core_user']->lang[$lang . '_IMPORT'] ?></th>
</tr>
<?php

			if ($pak_options == '')
			{

?>
<tr>
	<td class="row1" colspan="2"><?php echo $_CLASS['core_user']->lang['NO_' . $lang . '_PAK']; ?></td>
</tr>
<?php

			}
			else
			{

?>
<tr>
	<td class="row2"><?php echo $_CLASS['core_user']->lang['SELECT_PACKAGE'] ?></td>
	<td class="row1"><select name="pak"><?php echo $pak_options ?></select></td>
</tr>
<tr>
	<td class="row2"><?php echo $_CLASS['core_user']->lang['CURRENT_' . $lang] ?><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['CURRENT_' . $lang . '_EXPLAIN'] ?></span></td>
	<td class="row1"><input type="radio" name="current" value="keep" checked="checked" /> <?php echo $_CLASS['core_user']->lang['KEEP_ALL'] ?>&nbsp; &nbsp;<input type="radio" name="current" value="replace" /> <?php echo $_CLASS['core_user']->lang['REPLACE_MATCHES'] ?>&nbsp; &nbsp;<input type="radio" name="current" value="delete" /> <?php echo $_CLASS['core_user']->lang['DELETE_ALL'] ?>&nbsp;</td>
	</tr>
<tr>
	<td class="cat" colspan="2" align="center"><input class="btnmain" name="import" type="submit" value="<?php echo $_CLASS['core_user']->lang['IMPORT_' . $lang] ?>" /></td>
</tr>
<?php

			}

?>
</table></form>
<?php
			adm_page_footer();

		}
		break;

	case 'export':

		adm_page_header($_CLASS['core_user']->lang['EXPORT_' . $lang]);
		trigger_error(sprintf($_CLASS['core_user']->lang['EXPORT_' . $lang . '_EXPLAIN'], '<a href="'.generate_link('Forums&amp;file=admin_icons&amp;mode=' . $mode . '&amp;action=send', array('admin' => true)).'">', '</a>'));
		break;

	case 'send':

		$sql = "SELECT * 
			FROM $table
			ORDER BY {$fields}_order";
		$result = $_CLASS['core_db']->query($sql);

		$pak = '';
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$pak .= "'" . addslashes($row[$fields . '_url']) . "', ";
			$pak .= "'" . addslashes($row[$fields . '_height']) . "', ";
			$pak .= "'" . addslashes($row[$fields . '_width']) . "', ";
			if ($mode == 'smilies')
			{
				$pak .= "'" . addslashes($row['emotion']) . "', ";
				$pak .= "'" . addslashes($row['code']) . "', ";
			}
			$pak .= "\n";
		}
		$_CLASS['core_db']->free_result($result);

		if ($pak != '')
		{
			$_CLASS['core_db']->sql_close();

			header('Content-Type: text/x-delimtext; name="' . $fields . '.pak"');
			header('Content-disposition: attachment; filename=' . $fields . '.pak"');
			echo $pak;
			exit;
		}
		else
		{
			trigger_error($_CLASS['core_user']->lang['NO_' . $fields . '_EXPORT']);
		}
		break;

	case 'delete':

		$_CLASS['core_db']->query("DELETE FROM $table 
			WHERE {$fields}_id = $id");

		switch ($mode)
		{
			case 'smilies':
				break;

			case 'icons':
				// Reset appropriate icon_ids
				$_CLASS['core_db']->query('UPDATE ' . TOPICS_TABLE . " 
					SET icon_id = 0 
					WHERE icon_id = $id");
				$_CLASS['core_db']->query('UPDATE ' . POSTS_TABLE . " 
					SET icon_id = 0 
					WHERE icon_id = $id");
				break;
		}

		$notice = $_CLASS['core_user']->lang[$lang . '_DELETED'];

	case 'move_up':
	case 'move_down':

		if ($action != 'delete')
		{
			$image_order = intval($_GET['order']);
			$order_total = $image_order * 2 + (($action == 'move_up') ? -1 : 1);

			$sql = 'UPDATE ' . $table . '
				SET ' . $fields . "_order = $order_total - " . $fields . '_order
				WHERE ' . $fields . "_order IN ($image_order, " . (($action == 'move_up') ? $image_order - 1 : $image_order + 1) . ')';
			$_CLASS['core_db']->query($sql);

			$_CLASS['core_cache']->destroy('icons');

		}
		// No break; here, display the smilies admin back

	default:

		// By default, check that image_order is valid and fix it if necessary
		$sql = "SELECT {$fields}_id AS order_id, {$fields}_order AS fields_order
			FROM $table
			ORDER BY {$fields}_order";
		$result = $_CLASS['core_db']->query($sql);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$order = 0;
			do
			{
				++$order;
				if ($row['fields_order'] != $order)
				{
					$_CLASS['core_db']->query("UPDATE $table
						SET {$fields}_order = $order
						WHERE {$fields}_id = " . $row['order_id']);
				}
			}
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
		}
		$_CLASS['core_db']->free_result($result);

		// Output the page
		adm_page_header($_CLASS['core_user']->lang[$lang]);

?>

<h1><?php echo $_CLASS['core_user']->lang[$lang]; ?></h1>

<p><?php echo $_CLASS['core_user']->lang[$lang .'_EXPLAIN']; ?></p>

<?php

	if ($notice != '')
	{

?>
		<b style="color:green"><?php echo $notice; ?></b>
<?php
	
	}

?>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_icons&amp;mode=' . $mode, array('admin' => true)); ?>">
<table cellspacing="1" cellpadding="0" border="0" align="center">
<tr>
	<td align="right"> &nbsp;&nbsp; <a href="<?php echo generate_link('Forums&amp;file=admin_icons&amp;mode=' . $mode . '&amp;action=import', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['IMPORT_' . $lang]; ?></a> | <a href="<?php echo generate_link('Forums&amp;file=admin_icons&amp;mode=' . $mode . '&amp;action=export', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['EXPORT_' . $lang]; ?></a></td>
</tr>
<tr>
	<td>
		<table class="tablebg" width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
		<tr>
			<th><?php echo $_CLASS['core_user']->lang[$lang]; ?></th>
<?php
			if ($mode == 'smilies')
			{
?>
				<th><?php echo $_CLASS['core_user']->lang['CODE']; ?></th>
				<th><?php echo $_CLASS['core_user']->lang['EMOTION']; ?></th>
<?php
			}
?>
			<th><?php echo $_CLASS['core_user']->lang['ACTION']; ?></th>
			<th><?php echo $_CLASS['core_user']->lang['REORDER']; ?></th>
		</tr>
<?php
		$spacer = FALSE;

		$sql = "SELECT * 
			FROM $table
			ORDER BY display_on_posting DESC, {$fields}_order ASC";
		$result = $_CLASS['core_db']->query($sql);
		
		$row_class = 'row1';

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if (!$spacer && !$row['display_on_posting'])
			{
				$spacer = TRUE;
?>
		<tr>
			<td class="row3" colspan="<?php echo ($mode == 'smilies') ? 5 : 3; ?>" align="center"><?php echo $_CLASS['core_user']->lang[$lang . '_NOT_DISPLAYED'] ?></td>
		</tr>
<?php
			}

			$row_class = ($row_class != 'row1') ? 'row1' : 'row2';
			$alt_text = ($mode == 'smilies') ? htmlspecialchars($row['code']) : '';
?>
		<tr>
			<td class="<?php echo $row_class; ?>" align="center"><img src="<?php echo $img_path . '/' . $row[$fields . '_url']; ?>" width="<?php echo $row[$fields . '_width']; ?>" height="<?php echo $row[$fields . '_height']; ?>" alt="<?php echo $alt_text; ?>" title="<?php echo $alt_text; ?>" /></td>
<?php

			if ($mode == 'smilies')
			{
?>
				<td class="<?php echo $row_class; ?>" align="center"><?php echo htmlspecialchars($row['code']); ?></td>
				<td class="<?php echo $row_class; ?>" align="center"><?php echo $row['emotion']; ?></td>
<?php
			}
?>
			<td class="<?php echo $row_class; ?>" align="center"><a href="<?php echo generate_link("Forums&amp;file=admin_icons&amp;mode=$mode&amp;action=edit&amp;id=" . $row[$fields . '_id'], array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['EDIT']; ?></a> | <a href="<?php echo generate_link("Forums&amp;file=admin_icons&amp;mode=$mode&amp;action=delete&amp;id=" . $row[$fields . '_id'], array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['DELETE']; ?></a></td>
			<td class="<?php echo $row_class; ?>" align="center"><a href="<?php echo generate_link("Forums&amp;file=admin_icons&amp;mode=$mode&amp;action=move_up&amp;order=" . $row[$fields . '_order'], array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['MOVE_UP']; ?></a> <br /> <a href="<?php echo generate_link("Forums&amp;file=admin_icons&amp;mode=$mode&amp;action=move_down&amp;order=" . $row[$fields . '_order'], array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['MOVE_DOWN']; ?></a></td>
		</tr>
<?php

		}
		$_CLASS['core_db']->free_result($result);

?>
		<tr>
			<td class="cat" colspan="<?php echo ($mode == 'smilies') ? 5 : 3; ?>" align="center"><input type="submit" name="add" value="<?php echo $_CLASS['core_user']->lang['ADD_' . $lang]; ?>" class="btnmain" />&nbsp;<input type="submit" name="edit" value="<?php echo $_CLASS['core_user']->lang['EDIT_' . $lang]; ?>" class="btnmain" /></td>
		</tr>
		</table>
	</td>
</tr>
</table>
</form>

<?php
		adm_page_footer();

		break;
}

?>