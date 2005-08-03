<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

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
			url_redirect(generate_link('messages', array('admin' => true)));
			die;
		}
		return false;
	}
	
	return true;
}

function get_id($rediret = true)
{
	$id = get_variable('id', 'GET', false, 'integer');

	if (!$id && $rediret)
	{
		url_redirect(generate_link('messages', array('admin' => true)));
		die;
	}
	
	return $id;
}

switch (get_variable('mode', 'GET', false))
{
	case 'change':
		block_change(get_id());
		url_redirect(generate_link('messages', array('admin' => true)));
    break;
    
    case 'weight':
		block_weight(get_id(), get_variable('option', 'GET', false));
		url_redirect(generate_link('messages', array('admin' => true)));
    break;

    case 'delete':
		block_delete(get_id());
		url_redirect(generate_link('messages', array('admin' => true)));
    break;
  
    case 'edit':
		message_edit(get_id());
    break;
    
    case 'add':
		message_edit(false);
    break;
       
    case 'save':
		message_save();
    break;

    case 'auth':
		block_auth(get_id());
    break;

    default:
		message_admin();
}

script_close(false);

function message_admin()
{
    global $prefix, $_CLASS;

    $result = $_CLASS['core_db']->query('SELECT id, position, title, start, weight, active, expires FROM '.BLOCKS_TABLE.'
					WHERE position IN ('.BLOCK_MESSAGE_TOP.', '.BLOCK_MESSAGE_BOTTOM.') 
						ORDER BY weight ASC');
	$block_position = array(BLOCK_MESSAGE_TOP => 'top', BLOCK_MESSAGE_BOTTOM => 'bottom');
	$in_position = false;
	$messages = array();

	while($row = $_CLASS['core_db']->fetch_row_assoc($result))
    {
		if ($in_position != $row['position'])
		{
			$weigth[$row['position']] = $row['weight'];
			$in_position == $row['position'];
		}
		$messages[] = $row;
	}
	$_CLASS['core_db']->free_result($result);

	foreach ($messages as $row)
	{
		$_CLASS['core_template']->assign_vars_array($block_position[$row['position']].'_admin_messages', array(
			'ACTIVE'		=> ($row['active']) ? true : false,
			'CHANGE'		=> ($row['active']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],

			'AUTH_LINK'		=> generate_link('messages&amp;mode=auth&amp;id='.$row['id'], array('admin' => true)),
			'ACTIVE_LINK'	=> generate_link('messages&amp;mode=change&amp;id='.$row['id'], array('admin' => true)),
			'VIEW_LINK' 	=> generate_link('messages&amp;mode=show&amp;id='.$row['id'], array('admin' => true)),
			'EDIT_LINK'		=> generate_link('messages&amp;mode=edit&amp;id='.$row['id'], array('admin' => true)),
			'DELETE_LINK' 	=> generate_link('messages&amp;mode=delete&amp;id='.$row['id'], array('admin' => true)),

			'EXPIRES'		=> ($row['expires'] && ($row['expires'] < $_CLASS['core_user']->time)) ? $_CLASS['core_user']->format_date($row['expires']) : false,
			'STARTS'		=> ($row['start'] > $_CLASS['core_user']->time) ? $_CLASS['core_user']->format_date($row['start']) : false,
			'TITLE'			=> $row['title'],

			'WEIGHT_DOWN' 		=> ($row['weight'] < $weigth[$row['position']]),
			'WEIGHT_UP'		=> ($row['weight'] > 1),

			'WEIGHT_MOVE_UP' 	=> generate_link('messages&amp;mode=weight&amp;option=up&amp;id='.$row['id'], array('admin' => true)),
			'WEIGHT_MOVE_TOP' 	=> generate_link('messages&amp;mode=weight&amp;option=top&amp;id='.$row['id'], array('admin' => true)),
			'WEIGHT_MOVE_DOWN'	=> generate_link('messages&amp;mode=weight&amp;option=down&amp;id='.$row['id'], array('admin' => true)),
			'WEIGHT_MOVE_BOTTOM'=> generate_link('messages&amp;mode=weight&amp;option=bottom&amp;id='.$row['id'], array('admin' => true)),
		));
    }
    $_CLASS['core_db']->free_result($result);

    $_CLASS['core_template']->assign(array(
		'L_ADD_NEW'			=> 'New Message',
		'L_BLOCK_HTML'		=> 'Add HTML block',
		'L_BLOCK_RSS'		=> 'Add RSS block',
		'B_ACTION'			=> generate_link('messages&amp;mode=add', array('admin' => true))
	));

	$_CLASS['core_template']->display('admin/messages/index.html');
}

function message_delete($id)
{
    global $_CLASS;

    if (get_variable('ok', 'GET', false))
    {
		$result = $_CLASS['core_db']->query('SELECT weight, position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$block)
		{
			url_redirect(generate_link('messages', array('admin' => true)));
		}
		
		check_position($block['position']);
				
        $result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight= weight - 1 WHERE position='.$block['position'].' AND weight > '.$block['weight']);
        $_CLASS['core_db']->query('DELETE from '.BLOCKS_TABLE.' where id='.$id);

        $_CLASS['core_cache']->destroy('blocks');
		url_redirect(generate_link('messages', array('admin' => true)));
    }
    else
    {
		$_CLASS['core_display']->display_head();
		OpenTable();
		echo '<center>Remove Message ?';
		echo '<br /><br />[ <a href="'.generate_link("messages&amp;mode=delete&amp;id=$id&amp;ok=1", array('admin' => true)).'">Yes</a> | <a href="'.generate_link('messages', array('admin' => true)).'">No</a> ]</center>';
		CloseTable();
        $_CLASS['core_display']->display_footer();
    }
}

function message_edit($block = false, $error = false)
{
    global $_CLASS;
    
    $id = false;

	if (isset($_REQUEST['id']) && $id = get_id())
	{
		$result = $_CLASS['core_db']->query('SELECT * FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$block)
		{
			url_redirect(generate_link('messages', array('admin' => true)));
		}
		
		check_position($block['position']);

		if (isset($_POST['submit']))
		{
			// need to re-validate data with the db type
			messages_get_data($block_post, $error);
			$block = array_merge($block, $block_post);
		}

		unset($block_post);
	}
	
	$_CLASS['core_template']->assign(array(
		'B_TITLE'			=> $block['title'],
		'B_CONTENT'			=> $block['content'],
		'B_ACTIVE'			=> $block['active'],
		'B_EXPIRES'			=> is_numeric($block['expires']) ? $_CLASS['core_user']->format_date($block['expires'], 'M d, Y h:i a') : $block['expires'],
		'B_ERROR'			=> $error,
		'B_STARTS'			=> is_numeric($block['start']) ? $_CLASS['core_user']->format_date($block['start'], 'M d, Y h:i a') : $block['start'],
		'B_CURRENT_TIME'	=> $_CLASS['core_user']->format_date($_CLASS['core_user']->time),
		'B_POSITION'		=> message_position_select($block['position']),
		'B_TYPE'			=> message_type_select($block['type']),
		'B_DELETE_LINK'		=> ($id) ? generate_link('messages&amp;mode=delete&amp;id='.$id, array('admin' => true)) : false,
		'B_ACTION'			=> generate_link('messages&amp;mode=save'.(($id) ? '&amp;id='.$id : ''), array('admin' => true))
		)		
	);
	
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

	$data['title'] = get_variable('title', 'POST', '');
	$data['content'] = trim(get_variable('content', 'POST', ''));
	
	foreach ($data as $field => $value)
	{
		if (!$value)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_'.$field].'<br />';
		}
	}
	
	$data['active']		= get_variable('active', 'POST', 0);
	$data['expires']	= get_variable('expires', 'POST', 0);
	$data['start']		= get_variable('time', 'POST', '');
	$data['position']	= get_variable('b_position', 'POST', BLOCK_MESSAGE_TOP, 'integer');
	$data['type'] 		= (int) get_variable('b_type', 'REQUEST', BLOCKTYPE_MESSAGE);

	$appoved_types = array(BLOCKTYPE_MESSAGE, BLOCKTYPE_MESSAGE_GLOBAL);

	if (!in_array($data['type'], $appoved_types, true))
	{
		$data['type'] = BLOCKTYPE_MESSAGE;
	}
	
	if (!$data['position'] || !check_position($data['position'], false))
	{
		$data['position'] = BLOCK_MESSAGE_TOP;
	}
	
	$start = $expires = '';

	if ($data['start'])
	{
		$start = strtotime($data['start']);

		if (!$start || $start == -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_START_TIME'].'<br />';
		}
	}

	if ($data['expires'])
	{
		$expires = strtotime($data['expires']);

		if (!$expires || $expires == -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_END_TIME'].'<br />';
		}
	}
	
	if (!$error)
	{
		$data['start'] = ($start) ? $_CLASS['core_user']->time_convert($start, 'gmt') : 0;
		$data['expires'] = ($expires) ? $_CLASS['core_user']->time_convert($expires, 'gmt') : 0;
	}
}

function message_save()
{
    global $_CLASS;
    
	if (isset($_REQUEST['id']) && $id = get_id())
	{
		$result = $_CLASS['core_db']->query('SELECT position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$block)
		{
			url_redirect(generate_link('messages', array('admin' => true)));
		}
		
		check_position($block['position']);
		// need to validate data with the db type
		messages_get_data($data, $error);
		
		if ($error)
		{
			return message_edit($data, $error);
		}
	
		$sql = 'UPDATE '.BLOCKS_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE id='.$id;

		$_CLASS['core_db']->query($sql);
	}
	else
	{
		messages_get_data($data, $error);

		if ($error)
		{
			return message_edit($data, $error);
		}

		$result = $_CLASS['core_db']->query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position = '.$data['position']);
		$maxweight = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$data['weight'] = (int) $maxweight['weight'] + 1;

		$sql = 'INSERT INTO '.BLOCKS_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data);

		$_CLASS['core_db']->query($sql);
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