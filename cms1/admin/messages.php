<?php

if (VIPERAL != 'Admin') 
{
	header('Location: ../../../'); die;
}

require($site_file_root.'admin/functions/block_functions.php');
$_CLASS['core_user']->add_lang('admin/blocks.php');

function check_position($position)
{
	$appoved_blocks = array(BLOCK_MESSAGE_TOP, BLOCK_MESSAGE_BOTTOM);
	$position = (int) $position;
	
	if (!in_array($position, $appoved_blocks, true))
	{
		url_redirect(generate_link('messages', array('admin' => true)));
		die;
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

    default:
		message_admin();
    
}

die;

function message_admin()
{
    global $admin, $prefix, $_CLASS;
   
    $_CLASS['core_display']->display_head();
        
    $result = $_CLASS['core_db']->sql_query('SELECT id, position, title, time, weight, active, expires FROM '.BLOCKS_TABLE." WHERE type=4 ORDER BY weight");
    $count = $_CLASS['core_db']->sql_numrows($result);
    
	$block_position = array(BLOCK_MESSAGE_TOP => 'top', BLOCK_MESSAGE_BOTTOM => 'bottom');

    while($row = $_CLASS['core_db']->sql_fetchrow($result))
    {
    
    	$_CLASS['core_template']->assign_vars_array($block_position[$row['position']].'_admin_messages', array(
			'ACTIVE'		=> ($block['active']) ? true : false,
			'ACTIVE_LINK'	=> generate_link('messages&amp;mode=change&amp;id='.$row['id'], array('admin' => true)),
			'CHANGE'		=> ($row['active']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
			'VIEW_LINK' 	=> generate_link('messages&amp;mode=show&amp;id='.$row['id'], array('admin' => true)),
			'EDIT_LINK'		=> generate_link('messages&amp;mode=edit&amp;id='.$row['id'], array('admin' => true)),
			'DELETE_LINK' 	=> generate_link('messages&amp;mode=delete&amp;id='.$row['id'], array('admin' => true)),
			'EXPIRES'		=> ($row['expires']) ? $_CLASS['core_user']->format_date($row['expires']) : false,
			'STARTS'		=> ($row['time'] > time()) ? $_CLASS['core_user']->format_date($row['time']) : false,
			'TITLE'			=> $row['title'],
			'WEIGHT_UP' 	=> ($row['weight'] < $count) ? true : false,
			'WEIGHT_DOWN'	=> ($row['weight'] > 1) ? true : false,
			'WEIGHT_MOVE_UP' 	=> generate_link('blocks&amp;mode=weight&amp;option=down&amp;bid='.$row['id'], array('admin' => true)),
			'WEIGHT_MOVE_TOP' 	=> generate_link('blocks&amp;mode=weight&amp;option=top&amp;bid='.$row['id'], array('admin' => true)),
			'WEIGHT_MOVE_DOWN'	=> generate_link('blocks&amp;mode=weight&amp;option=down&amp;bid='.$row['id'], array('admin' => true)),
			'WEIGHT_MOVE_BOTTOM'=> generate_link('blocks&amp;mode=weight&amp;option=bottom&amp;bid='.$row['id'], array('admin' => true)),
		));
  
    }
    $_CLASS['core_db']->sql_freeresult($result);
    
    $_CLASS['core_template']->assign(array(
		'L_TITLE'			=> $_CLASS['core_user']->lang['TITLE'],
		'L_POSITION'		=> $_CLASS['core_user']->lang['POSITION'],
		'L_TYPE'			=> $_CLASS['core_user']->lang['TITLE'],
		'L_ACTIVE'			=> $_CLASS['core_user']->lang['STATUS'],
		'L_EXPIRES'			=> $_CLASS['core_user']->lang['EXPIRES'],
		'L_STARTS'			=> $_CLASS['core_user']->lang['STARTS'],
		'L_VIEW'			=> $_CLASS['core_user']->lang['VIEW'],
		'L_LANGUAGE'		=> $_CLASS['core_user']->lang['TITLE'],
		'L_FUNCTIONS'		=> $_CLASS['core_user']->lang['OPTIONS'],
		'L_EDIT'			=> $_CLASS['core_user']->lang['EDIT'],
		'L_ADD_NEW'			=> 'New Message',
		'L_BLOCK_HTML'		=> 'Add HTML block',
		'L_BLOCK_RSS'		=> 'Add RSS block',
		'L_DELETE'			=> $_CLASS['core_user']->lang['DELETE'],
		'B_ACTION'			=> generate_link('messages&amp;mode=add', array('admin' => true))
		)		
	);
	
    OpenTable();

	$_CLASS['core_template']->display('admin/messages/index.html');
    
    CloseTable();
    $_CLASS['core_display']->display_footer();

}

function message_delete($id)
{
    global $_CLASS;
    if (get_variable('ok', 'GET', false))
    {
		$result = $_CLASS['core_db']->sql_query('SELECT weight, position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);

		if (!$block)
		{
			url_redirect(generate_link('messages', array('admin' => true)));
		}
		
		check_position($block['position']);
				
        $result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight > '.$block['weight']);
        $_CLASS['core_db']->sql_query('delete from '.BLOCKS_TABLE.' where id='.$id);

        $_CLASS['core_cache']->destroy('blocks');
		url_redirect(generate_link('messages', array('admin' => true)));
        
    } else {
    
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
    
	if (isset($_REQUEST['id']) && $id = get_id())
	{
		$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
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
	else
	{
		if (!$block)
		{
			messages_get_data($block_post, $error);
			$error = '';
		}
	}
	
	$_CLASS['core_template']->assign(array(
		'B_TITLE'			=> $block['title'],
		'B_CONTENT'			=> $block['content'],
		'B_ACTIVE'			=> $block['active'],
		'B_EXPIRES'			=> is_numeric($block['expires']) ? $_CLASS['core_user']->format_date($block['expires']) : $block['expires'],
		'B_ERROR'			=> $error,
		'B_STARTS'			=> is_numeric($block['time']) ? $_CLASS['core_user']->format_date($block['time']) : $block['time'],
		'B_CURRENT_TIME'	=> $_CLASS['core_user']->format_date(time()),
		'B_DELETE_LINK'		=> ($id) ? generate_link('messages&amp;mode=delete&amp;id='.$id, array('admin' => true)) : false,
		'L_YES'				=> $_CLASS['core_user']->lang['YES'],
		'L_NO' 				=> $_CLASS['core_user']->lang['NO'],
		'L_TITLE'			=> $_CLASS['core_user']->lang['TITLE'],
		'L_TYPE'			=> $_CLASS['core_user']->lang['TITLE'],
		'L_ACTIVE'			=> $_CLASS['core_user']->lang['STATUS'],
		'L_EXPIRES'			=> $_CLASS['core_user']->lang['EXPIRES'],
		'L_STARTS'			=> $_CLASS['core_user']->lang['STARTS'],
		'L_ACTIVE'			=> $_CLASS['core_user']->lang['ACTIVE'],
		'L_MESSAGE'			=> $_CLASS['core_user']->lang['MESSAGE'],
		'L_DELETE'			=> $_CLASS['core_user']->lang['DELETE'],
		'L_DELETE_THIS'		=> $_CLASS['core_user']->lang['DELETE_THIS'],
		'B_ACTION'			=> generate_link('messages&amp;mode=save'.(($id) ? '&amp;id='.$id : ''), array('admin' => true))
		)		
	);
	
	$_CLASS['core_display']->display_head();
	$_CLASS['core_template']->display('admin/messages/edit.html');
	$_CLASS['core_display']->display_footer();		
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
	
	$data['active'] = get_variable('active', 'POST', 0);
	$data['expires'] = get_variable('expires', 'POST', 0);
	$data['time'] = get_variable('time', 'POST', '');

	if ($data['time'])
	{
		if (($time = strtotime($data['time'])) === -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_START_TIME'].'<br />';
		} else {
			$data['time'] = $time;
		}
	}
	
	if ($data['expires'])
	{
		if (($expires = strtotime($data['expires'])) === -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_END_TIME'].'<br />';
		} else {
			$data['expires'] = $expires;
		}
	}
}

function message_save()
{
    global $_CLASS;
    
	if (isset($_REQUEST['id']) && $id = get_id())
	{
		$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
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

		$_CLASS['core_db']->sql_query($sql);
	}
	else
	{
		messages_get_data($data, $error);
		
		if ($error)
		{
			return message_edit($data, $error);
		}
		
		$result = $_CLASS['core_db']->sql_query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position='.BLOCK_MESSAGE_TOP);
		$maxweight = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
// Make a selecte option for these 2		
		$data['position'] = BLOCK_MESSAGE_TOP;
		$data['type'] = BLOCKTYPE_MESSAGE;

		$data['weight'] = (int) $maxweight['weight'] + 1;
		
		$sql = 'INSERT INTO '.BLOCKS_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data);
		$_CLASS['core_db']->sql_query($sql);
	}
	
	$_CLASS['core_cache']->destroy('blocks');
	$_CLASS['core_display']->meta_refresh('3', generate_link('messages', array('admin' => true)));
	trigger_error(sprintf($_CLASS['core_user']->lang['SAVED'], generate_link('messages', array('admin' => true))));	
}

?>