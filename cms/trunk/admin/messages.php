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

if (VIPERAL !== 'Admin') 
{
	die;
}

require($site_file_root.'admin/functions/block_functions.php');
$_CLASS['core_user']->add_lang('admin/blocks.php');

function check_position($position, $redirect = true)
{
	$appoved_blocks = array(BLOCK_MESSAGE_TOP, BLOCK_MESSAGE_BOTTOM);
	$position = (int) $position;
	
	if (!in_array($position, $appoved_blocks, true))
	{
		if ($redirect)
		{
			redirect(generate_link('messages', array('admin' => true, 'full' => true)));
		}
		return false;
	}
	
	return true;
}

if (isset($_REQUEST['mode']))
{
	if ($id = get_variable('id', 'GET', false, 'integer'))
	{
		switch ($_REQUEST['mode'])
		{
			case 'change':
				block_change($id);
			break;
			
			case 'order':
				block_order($id, get_variable('option', 'GET', false));
			break;
		
			case 'delete':
				block_delete($id, generate_link('messages', array('admin' => true)));
			break;
		  
			case 'auth':
				block_auth($id);
			break;
		}
	}

	switch ($_REQUEST['mode'])
	{
		case 'add':
		case 'edit':
			message_edit($id);
			script_close(false);
		break;

		case 'save':
			message_save($id);
		break;
	}
}

$result = $_CLASS['core_db']->query('SELECT block_id, block_position, block_title, block_starts, block_expires, block_order, block_status FROM ' . BLOCKS_TABLE . '
				WHERE block_position IN ('.BLOCK_MESSAGE_TOP.', '.BLOCK_MESSAGE_BOTTOM.') 
					 ORDER BY block_position, block_order ASC');

$block_position = array(BLOCK_MESSAGE_TOP => 'top', BLOCK_MESSAGE_BOTTOM => 'bottom');
$in_position = false;
$messages = array();

while($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	if ($in_position != $row['block_position'])
	{
		$weigth[$row['block_position']] = $row['block_order'];
		$in_position == $row['block_position'];
	}
	$messages[] = $row;
}

$_CLASS['core_db']->free_result($result);

foreach ($messages as $row)
{
	$active = $row['block_status'] == STATUS_ACTIVE;

	$_CLASS['core_template']->assign_vars_array($block_position[$row['block_position']].'_admin_messages', array(
		'ACTIVE'		=> $active,
		'CHANGE'		=> ($active) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],

		'AUTH_LINK'		=> generate_link('messages&amp;mode=auth&amp;id='.$row['block_id'], array('admin' => true)),
		'ACTIVE_LINK'	=> generate_link('messages&amp;mode=change&amp;id='.$row['block_id'], array('admin' => true)),
		'VIEW_LINK' 	=> generate_link('messages&amp;mode=show&amp;id='.$row['block_id'], array('admin' => true)),
		'EDIT_LINK'		=> generate_link('messages&amp;mode=edit&amp;id='.$row['block_id'], array('admin' => true)),
		'DELETE_LINK' 	=> generate_link('messages&amp;mode=delete&amp;id='.$row['block_id'], array('admin' => true)),

		'EXPIRES'		=> ($row['block_expires'] > $_CLASS['core_user']->time) ? $_CLASS['core_user']->format_date($row['block_expires']) : false,
		'STARTS'		=> ($row['block_starts'] > $_CLASS['core_user']->time) ? $_CLASS['core_user']->format_date($row['block_starts']) : false,
		'TITLE'			=> $row['block_title'],

		'ORDER_DOWN' 	=> ($row['block_order'] < $weigth[$row['block_position']]),
		'ORDER_UP'		=> ($row['block_order'] > 1),

		'LINK_ORDER_UP' 	=> generate_link('messages&amp;mode=order&amp;option=up&amp;id='.$row['block_id'], array('admin' => true)),
		'LINK_ORDER_TOP' 	=> generate_link('messages&amp;mode=order&amp;option=top&amp;id='.$row['block_id'], array('admin' => true)),
		'LINK_ORDER_DOWN'	=> generate_link('messages&amp;mode=order&amp;option=down&amp;id='.$row['block_id'], array('admin' => true)),
		'LINK_ORDER_BOTTOM'	=> generate_link('messages&amp;mode=order&amp;option=bottom&amp;id='.$row['block_id'], array('admin' => true)),
	));
}

$_CLASS['core_template']->assign_array(array(
	'L_ADD_NEW'			=> 'New Message',
	'L_BLOCK_HTML'		=> 'Add HTML block',
	'L_BLOCK_RSS'		=> 'Add RSS block',
	'B_ACTION'			=> generate_link('messages&amp;mode=add', array('admin' => true))
));

$_CLASS['core_template']->display('admin/messages/index.html');

script_close();

function message_edit($id = false, $block = false, $error = false)
{
    global $_CLASS;

	if ($id)
	{
		$result = $_CLASS['core_db']->query('SELECT * FROM '.BLOCKS_TABLE.' WHERE block_id = ' . $id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$block)
		{
			redirect(generate_link('messages', array('admin' => true)));
		}
		
		check_position($block['block_position']);

		if (isset($_POST['submit']))
		{
			// need to re-validate data with the db type
			messages_get_data($block_post, $error);
			$block = array_merge($block, $block_post);
		}

		unset($block_post);
	}
	
	$_CLASS['core_template']->assign_array(array(
		'B_TITLE'			=> $block['block_title'],
		'B_CONTENT'			=> $block['block_content'],
		'B_ACTIVE'			=> $block['block_status'],
		'B_EXPIRES'			=> is_numeric($block['block_expires']) ? $_CLASS['core_user']->format_date($block['block_expires'], 'M d, Y h:i a') : $block['block_expires'],
		'B_ERROR'			=> $error,
		'B_STARTS'			=> is_numeric($block['block_starts']) ? $_CLASS['core_user']->format_date($block['block_starts'], 'M d, Y h:i a') : $block['block_starts'],
		'B_POSITION'		=> message_position_select($block['block_position']),
		'B_TYPE'			=> message_type_select($block['block_type']),
		'B_DELETE_LINK'		=> ($id) ? generate_link('messages&amp;mode=delete&amp;id='.$id, array('admin' => true)) : false,
		'B_ACTION'			=> generate_link('messages&amp;mode=save'.(($id) ? '&amp;id='.$id : ''), array('admin' => true)),
		'B_CURRENT_TIME'=> $_CLASS['core_user']->format_date($_CLASS['core_user']->time)
	));
	
	$_CLASS['core_template']->display('admin/messages/edit.html');
}

function messages_get_data(&$data, &$error)
{
	global $_CLASS;
	
	$error = '';
	$data = array();

	if (!isset($_POST['submit']))
	{
		return;
	}

	$data['block_title'] = get_variable('title', 'POST', '');
	$data['block_content'] = get_variable('content', 'POST', '');
	
	foreach ($data as $field => $value)
	{
		if (!$value)
		{
			$error .= $_CLASS['core_user']->get_lang('ERROR_'.$field).'<br />';
		}
	}
	
	$data['block_status']	= (get_variable('active', 'POST', STATUS_DISABLED, 'integer') === STATUS_DISABLED) ? STATUS_DISABLED : STATUS_ACTIVE;
	$data['block_expires']	= get_variable('expires', 'POST', 0);
	$data['block_starts']	= get_variable('starts', 'POST', '');
	$data['block_position']	= get_variable('b_position', 'POST', BLOCK_MESSAGE_TOP, 'integer');
	$data['block_type'] 	= get_variable('b_type', 'REQUEST', BLOCKTYPE_MESSAGE, 'integer');

	$appoved_types = array(BLOCKTYPE_MESSAGE, BLOCKTYPE_MESSAGE_GLOBAL);

	if (!in_array($data['block_type'], $appoved_types, true))
	{
		$data['block_type'] = BLOCKTYPE_MESSAGE;
	}
	
	if (!$data['block_position'] || !check_position($data['block_position'], false))
	{
		$data['block_position'] = BLOCK_MESSAGE_TOP;
	}
	
	$start = $expires = '';

	if ($data['block_starts'])
	{
		$start = strtotime($data['block_starts']);

		if (!$start || $start == -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_START_TIME'].'<br />';
		}
	}

	if ($data['block_expires'])
	{
		$expires = strtotime($data['block_expires']);

		if (!$expires || $expires == -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_END_TIME'].'<br />';
		}
	}
	
	if (!$error)
	{
		$data['block_starts'] = ($start) ? $_CLASS['core_user']->time_convert($start, 'gmt') : 0;
		$data['block_expires'] = ($expires) ? $_CLASS['core_user']->time_convert($expires, 'gmt') : 0;
	}
}

function message_save($id = false)
{
    global $_CLASS;
    
	if ($id)
	{
		$result = $_CLASS['core_db']->query('SELECT block_position FROM ' . BLOCKS_TABLE . ' WHERE block_id = '. $id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$block)
		{
			redirect(generate_link('messages', array('admin' => true)));
		}

		check_position($block['block_position']);
		// need to validate data with the db type
		messages_get_data($data, $error);
		
		if ($error)
		{
			return message_edit($id, $data, $error);
		}
	
		$sql = 'UPDATE ' . BLOCKS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE block_id = '.$id;

		$_CLASS['core_db']->query($sql);
	}
	else
	{
		messages_get_data($data, $error);

		if ($error)
		{
			return message_edit(false, $data, $error);
		}

		$result = $_CLASS['core_db']->query('SELECT MAX(block_order) as block_order FROM ' . BLOCKS_TABLE . ' WHERE block_position = '.$data['block_position']);
		list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
		$_CLASS['core_db']->free_result($result);

		$data['block_order'] = (int) $max_order + 1;

		$_CLASS['core_db']->query('INSERT INTO ' . BLOCKS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data));
	}

	$_CLASS['core_cache']->destroy('blocks');

	$_CLASS['core_display']->meta_refresh('3', generate_link('messages', array('admin' => true)));
	trigger_error(sprintf($_CLASS['core_user']->lang['SAVED'], generate_link('messages', array('admin' => true))));	
}

function message_position_select($default = false)
{
	global $site_file_root, $_CLASS;

	$block_position_array = array(
		BLOCK_MESSAGE_TOP		=> 'Top',
		BLOCK_MESSAGE_BOTTOM	=> 'Bottom',
	);

	// Needs some work if a position = 0 can cause problems
	$default = ($default && array_key_exists($default, $block_position_array)) ? $default : BLOCK_MESSAGE_TOP;

	$block_position = '';

	foreach ($block_position_array as $value => $name)
	{
		if ($value == $default)
		{
			$block_position .= '<option value="'.$value.'" selected="selected">'.$name.'</option>';
		}
		else
		{
			$block_position .= '<option value="'.$value.'">'.$name.'</option>';
		}
	}

	return $block_position;
}

function message_type_select($default = false)
{
	global $site_file_root, $_CLASS;

	$block_position_array = array(
		BLOCKTYPE_MESSAGE			=> 'Normal Message',
		BLOCKTYPE_MESSAGE_GLOBAL	=> 'Global Message',
	);

	// Needs some work if a position = 0 can cause problems
	$default = ($default && array_key_exists($default, $block_position_array)) ? $default : BLOCKTYPE_MESSAGE;

	$block_position = '';

	foreach ($block_position_array as $value => $name)
	{
		if ($value == $default)
		{
			$block_position .= '<option value="'.$value.'" selected="selected">'.$name.'</option>';
		}
		else
		{
			$block_position .= '<option value="'.$value.'">'.$name.'</option>';
		}
	}

	return $block_position;
}

?>