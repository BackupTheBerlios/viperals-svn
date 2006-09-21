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
if (!$_CLASS['forums_auth']->acl_get('a_icons'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

$_CLASS['core_user']->add_lang('admin_posting', 'forums');

// Set up general vars
$action = request_var('action', '');
$action = (isset($_POST['add'])) ? 'add' : $action;
$action = (isset($_POST['edit'])) ? 'edit' : $action;
$action = (isset($_POST['import'])) ? 'import' : $action;
$icon_id = request_var('id', 0);

$u_action = 'forums&file=admin_icons';

$page_title = 'ACP_ICONS';

// Clear some arrays
$_images = $_paks = array();
$notice = '';

// Grab file list of paks and images
if ($action == 'edit' || $action == 'add' || $action == 'import')
{
	$imglist = filelist($config['icons_path'], '');

	foreach ($imglist as $path => $img_ary)
	{
		foreach ($img_ary as $img)
		{
			$img_size = @getimagesize($config['icons_path'] . '/' . $path . $img);

			if (!$img_size[0] || !$img_size[1])
			{
				continue;
			}

			$_images[$path . $img]['file'] = $path . $img;
			$_images[$path . $img]['width'] = $img_size[0];
			$_images[$path . $img]['height'] = $img_size[1];
		}
	}
	unset($imglist);

	$dir = @opendir($config['icons_path']);
	while (($file = @readdir($dir)) !== false)
	{
		if (is_file($config['icons_path'] . '/' . $file) && preg_match('#\.pak$#i', $file))
		{
			$_paks[] = $file;
		}
	}
	@closedir($dir);
}

// What shall we do today? Oops, I believe that's trademarked ...
switch ($action)
{
	case 'edit':
		unset($_images);
		$_images = array();

	// no break;

	case 'add':

		$order_list = '';

		$sql = 'SELECT * 
			FROM '.FORUMS_ICONS_TABLE.'
			ORDER BY icons_order ' . (($icon_id || $action == 'add') ? 'DESC' : 'ASC');
		$result = $_CLASS['core_db']->query($sql);

		$data = array();
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($action == 'add')
			{
				unset($_images[$row['icons_url']]);
			}

			if ($row['icons_id'] == $icon_id)
			{
				$after = true;
				$data[$row['icons_url']] = $row;
			}
			else
			{
				if ($action == 'edit' && !$icon_id)
				{
					$data[$row['icons_url']] = $row;
				}

				$selected = '';
				if (!empty($after))
				{
					$selected = ' selected="selected"';
					$after = false;
				}

				$after_txt = $row['icons_url'];
				$order_list = '<option value="' . ($row['icons_order']) . '"' . $selected . '>' . sprintf($_CLASS['core_user']->lang['AFTER_ICONS'], ' -&gt; ' . htmlspecialchars($after_txt)) . '</option>' . $order_list;
			}
		}
		$_CLASS['core_db']->free_result($result);

		$order_list = '<option value="1"' . ((!isset($after)) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['FIRST'] . '</option>' . $order_list;

		if ($action === 'add')
		{
			$data = $_images;
		}

		$colspan = '5';
		$colspan += ($icon_id) ? 1 : 0;
		$colspan += ($action == 'add') ? 2 : 0;

		$_CLASS['core_template']->assign_array(array(
			'S_EDIT'		=> true,
			'S_ADD'			=> ($action === 'add') ? true : false,
			'S_ORDER_LIST'	=> $order_list,

			'L_TITLE'		=> $_CLASS['core_user']->lang['ACP_ICONS'],
			'L_EXPLAIN'		=> $_CLASS['core_user']->lang['ACP_ICONS_EXPLAIN'],
			'L_CONFIG'		=> $_CLASS['core_user']->lang['ICONS_CONFIG'],
			'L_URL'			=> $_CLASS['core_user']->lang['ICONS_URL'],
			'L_LOCATION'	=> $_CLASS['core_user']->lang['ICONS_LOCATION'],
			'L_WIDTH'		=> $_CLASS['core_user']->lang['ICONS_WIDTH'],
			'L_HEIGHT'		=> $_CLASS['core_user']->lang['ICONS_HEIGHT'],
			'L_ORDER'		=> $_CLASS['core_user']->lang['ICONS_ORDER'],

			'COLSPAN'		=> $colspan,
			'ID'			=> $icon_id,

			'U_BACK'		=> generate_link($u_action, array('admin' => true)),
			'U_ACTION'		=> generate_link($u_action . '&amp;action=' . (($action == 'add') ? 'create' : 'modify'), array('admin' => true)),
		));

		foreach ($data as $img => $img_row)
		{
			$_CLASS['core_template']->assign_vars_array('items', array(
				'IMG'		=> $img,
				'IMG_SRC'	=> $config['icons_path'] . '/' . $img,

				'S_ID'				=> (isset($img_row['icons_id'])) ? true : false,
				'ID'				=> (isset($img_row['icons_id'])) ? $img_row['icons_id'] : 0,
				'WIDTH'				=> (!empty($img_row['icons_width'])) ? $img_row['icons_width'] : $img_row['width'],
				'HEIGHT'			=> (!empty($img_row['icons_height'])) ? $img_row['icons_height'] : $img_row['height'],
				'POSTING_CHECKED'	=> (!empty($img_row['display_on_posting']) || $action === 'add') ? ' checked="checked"' : '')
			);
		}

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_icons.html');

		return;

	break;

	case 'create':
	case 'modify':

		// Get items to create/modify
		$images = (isset($_POST['image'])) ? array_keys(request_var('image', array('' => 0))) : array();
		
		// Now really get the items
		$image_id		= (isset($_POST['id'])) ? array_map('intval', $_POST['id']) : array();
		$image_order	= (isset($_POST['order'])) ? array_map('intval', $_POST['order']) : array();
		$image_width	= (isset($_POST['width'])) ? array_map('intval', $_POST['width']) : array();
		$image_height	= (isset($_POST['height'])) ? array_map('intval', $_POST['height']) : array();
		$image_add		= (isset($_POST['add_img'])) ? array_map('intval', $_POST['add_img']) : array();
		$image_emotion	= request_var('emotion', array('' => ''));
		$image_code		= request_var('code', array('' => ''));
		$image_display_on_posting = (isset($_POST['display_on_posting'])) ? array_map('intval', $_POST['display_on_posting']) : array();

		foreach ($images as $image)
		{
			if ($action == 'create' && !isset($image_add[$image]))
			{
			}
			else
			{
				if ($image_width[$image] == 0 || $image_height[$image] == 0)
				{
					$img_size = @getimagesize($config['icons_path'] . '/' . $image);
					$image_width[$image] = $img_size[0];
					$image_height[$image] = $img_size[1];
				}

				$img_sql = array(
					'icons_url'				=> $image,
					'icons_width'			=> $image_width[$image],
					'icons_height'			=> $image_height[$image],
					'display_on_posting'	=> (isset($image_display_on_posting[$image])) ? 1 : 0,
				);

				if (!empty($image_order[$image]))
				{
					$img_sql = array_merge($img_sql, array(
						'icons_order'	=>	$image_order[$image] . '.5')
					);
				}

				if ($action == 'modify')
				{
					$sql = 'UPDATE '.FORUMS_ICONS_TABLE.'
						SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $img_sql) . " 
						WHERE icons_id = " . $image_id[$image];
					$_CLASS['core_db']->query($sql);
				}
				else
				{
					$sql = 'INSERT INTO '.FORUMS_ICONS_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', $img_sql);
					$_CLASS['core_db']->query($sql);
				}

				$update = false;

				if ($action == 'modify' && !empty($image_order[$image]))
				{
					$update = true;

					$sql = 'SELECT icons_order 
						FROM '.FORUMS_ICONS_TABLE.'
						WHERE icons_id = ' . $image_id[$image];
					$result = $_CLASS['core_db']->query($sql);

					$order_old = $_CLASS['core_db']->fetch_row_assoc($result);
					$order_old = (int) $order_old['icons_order'];

					$_CLASS['core_db']->free_result($result);

					if ($order_old == $image_order[$image])
					{
						$update = false;
					}

					if ($order_old > $image_order[$image])
					{
						$sign = '+';
						$where =  'icons_order >= ' . $image_order[$image] . " AND icons_order < $order_old";
					}
					else if ($order_old < $image_order[$image])
					{
						$sign = '-';
						$where = "icons_order > $order_old AND icons_order < " . $image_order[$image];
						$sql['icons_order'] = $image_order[$image] - 1;
					}
				}

				if ($update)
				{
					$sql = 'UPDATE '.FORUMS_ICONS_TABLE."
						SET icons_order = icons_order $sign 1
						WHERE $where";
					$_CLASS['core_db']->query($sql);
				}
			}
		}
		
		$_CLASS['core_cache']->destroy('icons');

		if ($action == 'modify')
		{
			trigger_error($_CLASS['core_user']->lang['ICONS_EDITED'] . adm_back_link(generate_link($u_action, array('admin' => true))));
		}
		else
		{
			trigger_error($_CLASS['core_user']->lang['ICONS_ADDED'] . adm_back_link(generate_link($u_action, array('admin' => true))));
		}

	break;

	case 'import':

		$pak = request_var('pak', '');
		$current = request_var('current', '');

		if ($pak != '')
		{
			$order = 0;

			// The user has already selected a icon_pak file
			if ($current == 'delete')
			{
				$_CLASS['core_db']->query('TRUNCATE TABLE '.FORUMS_ICONS_TABLE);

				// Reset all icon_ids
				$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . ' SET icon_id = 0');
				$_CLASS['core_db']->query('UPDATE ' . FORUMS_POSTS_TABLE . ' SET icon_id = 0');
			}
			else 
			{
				$cur_img = array();

				$sql = 'SELECT icons_url
					FROM '.FORUMS_ICONS_TABLE;
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					++$order;
					$cur_img[$row[$field_sql]] = 1;
				}
				$_CLASS['core_db']->free_result($result);
			}

			if (!($pak_ary = @file($config['icons_path'] . '/' . $pak)))
			{
				trigger_error($_CLASS['core_user']->lang['PAK_FILE_NOT_READABLE'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
			}

			foreach ($pak_ary as $pak_entry)
			{
				$data = array();
				if (preg_match_all("#'(.*?)', #", $pak_entry, $data))
				{
					if (sizeof($data[1]) != 4)
					{
						trigger_error($_CLASS['core_user']->lang['WRONG_PAK_TYPE'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
					}

					// Stripslash here because it got addslashed before... (on export)
					$img = stripslashes($data[1][0]);
					$width = stripslashes($data[1][1]);
					$height = stripslashes($data[1][2]);
					$display_on_posting = stripslashes($data[1][3]);

					if (isset($data[1][4]) && isset($data[1][5]))
					{
						$emotion = stripslashes($data[1][4]);
						$code = stripslashes($data[1][5]);
					}

					if ($current == 'replace' && !empty($cur_img[$img]))
					{
						$replace_sql = $img;
						$sql = array(
							'icons_url'		=> $img,
							'icons_height'		=> (int) $height,
							'icons_width'		=> (int) $width,
							'display_on_posting'	=> (int) $display_on_posting,
						);

						$sql = 'UPDATE '.FORUMS_ICONS_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql) . " 
							WHERE $field_sql = '" . $_CLASS['core_db']->escape($replace_sql) . "'";
						$_CLASS['core_db']->query($sql);
					}
					else
					{
						++$order;

						$sql = array(
							'icons_url'	=> $img,
							'icons_height'	=> (int) $height,
							'icons_width'	=> (int) $width,
							'icons_order'	=> (int) $order,
							'display_on_posting'=> (int) $display_on_posting,
						);

						$_CLASS['core_db']->query('INSERT INTO '.FORUMS_ICONS_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', $sql));
					}
				}
			}

			$_CLASS['core_cache']->destroy('icons');

			trigger_error($_CLASS['core_user']->lang['ICONS_IMPORT_SUCCESS'] . adm_back_link(generate_link($u_action, array('admin' => true))));
		}
		else
		{
			$pak_options = '';

			foreach ($_paks as $pak)
			{
				$pak_options .= '<option value="' . $pak . '">' . htmlspecialchars($pak) . '</option>';
			}

			$_CLASS['core_template']->assign_array(array(
				'S_CHOOSE_PAK'		=> true,
				'S_PAK_OPTIONS'		=> $pak_options,

				'L_TITLE'			=> $_CLASS['core_user']->lang['ACP_ICONS'],
				'L_EXPLAIN'			=> $_CLASS['core_user']->lang['ACP_ICONS_EXPLAIN'],
				'L_NO_PAK_OPTIONS'	=> $_CLASS['core_user']->lang['NO_ICONS_PAK'],
				'L_CURRENT'			=> $_CLASS['core_user']->lang['CURRENT_ICONS'],
				'L_CURRENT_EXPLAIN'	=> $_CLASS['core_user']->lang['CURRENT_ICONS_EXPLAIN'],
				'L_IMPORT_SUBMIT'	=> $_CLASS['core_user']->lang['IMPORT_ICONS'],

				'U_BACK'		=> generate_link($u_action, array('admin' => true)),
				'U_ACTION'		=> generate_link($u_action . '&amp;action=import', array('admin' => true)),
			));
		}
	break;

	case 'export':

		$page_title = 'EXPORT_ICONS';
		$tpl_name = 'message_body';

		$_CLASS['core_template']->assign_array(array(
			'MESSAGE_TITLE'		=> $_CLASS['core_user']->lang['EXPORT_ICONS'],
			'MESSAGE_TEXT'		=> sprintf($_CLASS['core_user']->lang['EXPORT_ICONS_EXPLAIN'], '<a href="' . generate_link($u_action . '&amp;action=send', array('admin' => true)).'">', '</a>'))
		);

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_icons.html');

	break;

	case 'send':

		$sql = 'SELECT * 
			FROM '.FORUMS_ICONS_TABLE.'
			ORDER BY icons_order';
		$result = $_CLASS['core_db']->query($sql);

		$pak = '';
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$pak .= "'" . addslashes($row['icons_url']) . "', ";
			$pak .= "'" . addslashes($row['icons_width']) . "', ";
			$pak .= "'" . addslashes($row['icons_height']) . "', ";
			$pak .= "'" . addslashes($row['display_on_posting']) . "', ";
			$pak .= "\n";
		}
		$_CLASS['core_db']->free_result($result);

		if ($pak != '')
		{
			garbage_collection();

			header('Pragma: public');

			// Send out the Headers
			header('Content-Type: text/x-delimtext; name="icons.pak"');
			header('Content-Disposition: inline; filename="icons.pak"');
			echo $pak;

			flush();
			exit;
		}
		else
		{
			trigger_error($_CLASS['core_user']->lang['NO_ICONS_EXPORT'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
		}

	break;

	case 'delete':
		if (display_confirmation())
		{
			$sql = 'DELETE FROM '.FORUMS_ICONS_TABLE.'
				WHERE icons_id = '.$icon_id;
			$_CLASS['core_db']->query($sql);
	
			// Reset appropriate icon_ids
			$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . " 
				SET icon_id = 0 
				WHERE icon_id = $icon_id");
	
			$_CLASS['core_db']->query('UPDATE ' . FORUMS_POSTS_TABLE . " 
				SET icon_id = 0 
				WHERE icon_id = $icon_id");
	
			$notice = $_CLASS['core_user']->lang['ICONS_DELETED'];
	
			$_CLASS['core_cache']->destroy('icons');
		}

	break;

	case 'move_up':
	case 'move_down':

		$image_order = request_var('order', 0);
		$order_total = $image_order * 2 + (($action == 'move_up') ? -1 : 1);

		$sql = 'UPDATE '.FORUMS_ICONS_TABLE."
			SET icons_order = $order_total - icons_order
			WHERE icons_order IN ($image_order, " . (($action == 'move_up') ? $image_order - 1 : $image_order + 1) . ')';
		$_CLASS['core_db']->query($sql);

		$_CLASS['core_cache']->destroy('icons');

	break;
}

// By default, check that image_order is valid and fix it if necessary
$sql = 'SELECT icons_id AS order_id, icons_order AS fields_order
	FROM '.FORUMS_ICONS_TABLE.'
	ORDER BY display_on_posting DESC, icons_order';
$result = $_CLASS['core_db']->query($sql);

if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$order = 0;
	do
	{
		++$order;
		if ($row['fields_order'] != $order)
		{
			$_CLASS['core_db']->query('UPDATE '.FORUMS_ICONS_TABLE."
				SET icons_order = $order
				WHERE icons_id = " . $row['order_id']);
		}
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_template']->assign_array(array(
	'L_TITLE'			=> $_CLASS['core_user']->lang['ACP_ICONS'],
	'L_EXPLAIN'			=> $_CLASS['core_user']->lang['ACP_' . 'ICONS_EXPLAIN'],
	'L_IMPORT'			=> $_CLASS['core_user']->lang['IMPORT_ICONS'],
	'L_EXPORT'			=> $_CLASS['core_user']->lang['EXPORT_ICONS'],
	'L_NOT_DISPLAYED'	=> $_CLASS['core_user']->lang['ICONS_NOT_DISPLAYED'],
	'L_ICON_ADD'		=> $_CLASS['core_user']->lang['ADD_ICONS'],
	'L_ICON_EDIT'		=> $_CLASS['core_user']->lang['EDIT_ICONS'],

	'NOTICE'			=> $notice,
	'COLSPAN'			=> 3,

	'U_ACTION'			=> generate_link($u_action, array('admin' => true)),
	'U_IMPORT'			=> generate_link($u_action . '&amp;action=import', array('admin' => true)),
	'U_EXPORT'			=> generate_link($u_action . '&amp;action=export', array('admin' => true)),
));

$spacer = false;

$sql = 'SELECT * 
	FROM '.FORUMS_ICONS_TABLE.'
	ORDER BY icons_order ASC';
$result = $_CLASS['core_db']->query($sql);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{

	$_CLASS['core_template']->assign_vars_array('items', array(
		'S_SPACER'		=> (!$spacer && !$row['display_on_posting']) ? true : false,
		'ALT_TEXT'		=> '',
		'IMG_SRC'		=> $config['icons_path'] . '/' . $row['icons_url'],
		'WIDTH'			=> $row['icons_width'],
		'HEIGHT'		=> $row['icons_height'],
		'CODE'			=> (isset($row['code'])) ? $row['code'] : '',
		'EMOTION'		=> (isset($row['emotion'])) ? $row['emotion'] : '',
		'U_EDIT'		=> generate_link($u_action . '&amp;action=edit&amp;id=' . $row['icons_id'], array('admin' => true)),
		'U_DELETE'		=> generate_link($u_action . '&amp;action=delete&amp;id=' . $row['icons_id'], array('admin' => true)),
		'U_MOVE_UP'		=> generate_link($u_action . '&amp;action=move_up&amp;order=' . $row['icons_order'], array('admin' => true)),
		'U_MOVE_DOWN'	=> generate_link($u_action . '&amp;action=move_down&amp;order=' . $row['icons_order'], array('admin' => true))
	));

	if (!$spacer && !$row['display_on_posting'])
	{
		$spacer = true;
	}
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_icons.html');

?>