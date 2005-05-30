<?php

if (VIPERAL != 'Admin') 
{
	header('Location: ../../../'); die;
}

require($site_file_root.'admin/functions/block_functions.php');
$_CLASS['core_user']->add_lang('admin/blocks.php');

$mode = get_variable('mode', 'GET', false);

if (!$mode)
{
	blocks_admin();
	die;
}

function check_position($position, $redirect = true)
{
	$appoved_blocks = array(BLOCK_RIGHT, BLOCK_TOP, BLOCK_BOTTOM, BLOCK_LEFT);
	$position = (int) $position;
	
	if (!in_array($position, $appoved_blocks, true))
	{
		if ($redirect)
		{
			url_redirect(generate_link('blocks', array('admin' => true)));
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
		url_redirect(generate_link('blocks', array('admin' => true)));
		die;
	}
	
	return $id;
}

switch ($mode)
{
	case 'change':
	
		block_change(get_id());
		url_redirect(generate_link('blocks', array('admin' => true)));

    break;
    
    case 'weight':

		block_weight(get_id(), get_variable('option', 'GET', false));
		url_redirect(generate_link('blocks', array('admin' => true)));
		
    break;
    	
    case 'delete':
    
		block_delete(get_id());
		url_redirect(generate_link('blocks', array('admin' => true)));
		
    break;
    
    case 'position':
    
		$id = get_id();
		
		$position = get_variable('option', 'GET', false, 'integer');
		
		// Would cause problems is $data['position'] = (int) 0
		if (!$position)
		{
			url_redirect(generate_link('blocks', array('admin' => true)));
		}
		
		blocks_change_position($position, $id);
		
		url_redirect(adminlink('blocks'));
		
    break;
    
    case 'add':
    case 'edit':

		block_add();
		
    break;
       
    case 'save':
    
		block_save();
		
    break;
        
    default:
    
		blocks_admin();
    die;
    
}

function blocks_change_position($position, $id)
{
	global $_CLASS;
	
	check_position($position);
	
	$result = $_CLASS['core_db']->sql_query('SELECT position, weight FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	check_position($block['position']);
	
	if ($block['position'] == $position)
	{
		return false;
	}
		
	$result = $_CLASS['core_db']->sql_query('SELECT weight FROM '.BLOCKS_TABLE." WHERE position='".$position."' ORDER BY weight DESC LIMIT 0,1");
	$max_weight = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);
	
	$_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE." SET weight=weight-1 WHERE position='".$block['position']."' AND weight > ".$block['weight']);
	$_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE." set position='".$position."', weight='".intval($max_weight['weight'] + 1)."' where id='$id'");

	$_CLASS['core_cache']->destroy('blocks');
	
	return true;
}

function blocks_admin()
{
    global $_CLASS, $site_file_root;
	
	blocks_block('main');
	
	$_CLASS['core_blocks']->add_block($data);

    $result = $_CLASS['core_db']->sql_query('SELECT id, title, type,  position, weight, active, file, auth FROM '.BLOCKS_TABLE." WHERE type != '4' ORDER BY weight");
    $blocks = array();
    
    while($row = $_CLASS['core_db']->sql_fetchrow($result))
    {
        $blocks[$row['position']][] = $row;
    }
	$_CLASS['core_db']->sql_freeresult($result);
	
    $block_type = array(BLOCK_RIGHT => 'right', BLOCK_TOP => 'centertop', BLOCK_BOTTOM => 'centerbottom', BLOCK_LEFT => 'left');
    
    foreach ($block_type as $pos => $name)
    {
    	if (empty($blocks[$pos]))
    	{
			continue;
    	}
    	
    	foreach ($blocks[$pos] as $block)
		{
			$position = ($pos != BLOCK_LEFT) ? '<a href="'.adminlink('blocks&amp;mode=position&amp;option='.BLOCK_LEFT.'&amp;id='.$block['id']).'">[ '.$_CLASS['core_user']->lang['B_LEFT'].' </a> | ' : '[ '.$_CLASS['core_user']->lang['B_LEFT'].' | ';
		    $position .= ($pos != BLOCK_RIGHT) ? '<a href="'.adminlink('blocks&amp;mode=position&amp;option='.BLOCK_RIGHT.'&amp;id='.$block['id']).'"> '.$_CLASS['core_user']->lang['B_RIGHT'].' </a> ]' : ' '.$_CLASS['core_user']->lang['B_RIGHT'].' ]';
		   	$position .= ($pos != BLOCK_TOP) ? '<br/> [ <a href="'.adminlink('blocks&amp;mode=position&amp;option='.BLOCK_TOP.'&amp;id='.$block['id']).'">'.$_CLASS['core_user']->lang['B_CENTER_TOP'].'</a> | ' : '<br/> [ '.$_CLASS['core_user']->lang['B_CENTER_TOP'].' | ';
			$position .= ($pos != BLOCK_BOTTOM) ? '<a href="'.adminlink('blocks&amp;mode=position&amp;option='.BLOCK_BOTTOM.'&amp;id='.$block['id']).'">'.$_CLASS['core_user']->lang['B_CENTER_BOTTOM'].'</a> ] ' : $_CLASS['core_user']->lang['B_CENTER_BOTTOM'].' ] ';
// Add errors, testing now
			$error = false;
			
			switch ($block['type'])
			{
				case BLOCKTYPE_FILE:
					if (!$block['file'] || !file_exists($site_file_root.'blocks/'.$block['file']))
					{
						$error = 'File_MISSING';
					}
				break;
			}
			
			$who_view = '';
			
			$_CLASS['core_template']->assign_vars_array($name.'_admin_blocks', array(
					'ACTIVE'		=> ($block['active']) ? true : false,
					'ACTIVE_LINK'	=> generate_link('blocks&amp;mode=change&amp;id='.$block['id'], array('admin' => true)),
					'CHANGE'		=> ($block['active']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
					'ERROR'			=> $error,
					'EDIT_LINK'		=> generate_link('blocks&amp;mode=edit&amp;id='.$block['id'], array('admin' => true)),
					'DELETE_LINK' 	=> ($block['type'] != BLOCKTYPE_SYSTEM) ? generate_link('blocks&amp;mode=delete&amp;id='.$block['id'], array('admin' => true)) : '',
					'TITLE'			=> $block['title'],
					'TYPE'			=> $_CLASS['core_user']->get_lang('TYPE_'.$block['type']),
					'POSITION'		=> $position,
					'WEIGHT_UP' 	=> ($block['weight'] < count($blocks[$pos])) ? true : false,
					'WEIGHT_DOWN'	=> ($block['weight'] > 1) ? true : false,
					'WEIGHT_MOVE_UP' 	=> generate_link('blocks&amp;mode=weight&amp;option=down&amp;id='.$block['id'], array('admin' => true)),
					'WEIGHT_MOVE_TOP' 	=> generate_link('blocks&amp;mode=weight&amp;option=top&amp;id='.$block['id'], array('admin' => true)),
					'WEIGHT_MOVE_DOWN'	=> generate_link('blocks&amp;mode=weight&amp;option=down&amp;id='.$block['id'], array('admin' => true)),
					'WEIGHT_MOVE_BOTTOM'=> generate_link('blocks&amp;mode=weight&amp;option=bottom&amp;id='.$block['id'], array('admin' => true)),
			));
		}
		unset($blocks[$pos], $block);
    }
       
    $_CLASS['core_template']->assign(array(
		'L_TITLE'			=> $_CLASS['core_user']->lang['TITLE'],
		'L_POSITION'		=> $_CLASS['core_user']->lang['POSITION'],
		'L_WEIGHT'			=> $_CLASS['core_user']->lang['ORDER'],
		'L_TYPE'			=> $_CLASS['core_user']->lang['TYPE'],
		'L_ACTIVE'			=> $_CLASS['core_user']->lang['STATUS'],
		'L_VIEW'			=> $_CLASS['core_user']->lang['VIEW'],
		'L_LANGUAGE'		=> $_CLASS['core_user']->lang['TITLE'],
		'L_FUNCTIONS'		=> $_CLASS['core_user']->lang['OPTIONS'],
		'L_EDIT'			=> $_CLASS['core_user']->lang['EDIT'],
		'L_BLOCK_REGULAR'	=> 'Add New regular Block',
		'L_BLOCK_HTML'		=> 'Add New HTML Block',
		'L_BLOCK_FEED'		=> 'Add New Feed Block',
		'L_DELETE'			=> $_CLASS['core_user']->lang['DELETE'],
		'N_BLOCK_FILE'		=> generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_FILE, array('admin' => true)),
		'N_BLOCK_FEED'		=> generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_FEED, array('admin' => true)),
		'N_BLOCK_HTML'		=> generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_HTML, array('admin' => true))
		)		
	);

    $_CLASS['core_display']->display_head();
	OpenTable();

	$_CLASS['core_template']->display('admin/blocks/index.html');
    
    CloseTable();
    $_CLASS['core_display']->display_footer();

}

function block_add($block = false, $error = false)
{
    global $_CLASS;
    
	if (isset($_REQUEST['id']) && $id = get_id())
	{
		$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
		if (!$block)
		{
			url_redirect(generate_link('blocks', array('admin' => true)));
		}
		
		check_position($block['position']);
		
		if (isset($_POST['submit']))
		{
			// need to re-validate data with the db type
			block_get_data($block_post, $error, $block['type']);
			$block = array_merge($block, $block_post);
		}
	
		unset($block_post);
		blocks_block();
	}
	else
	{
		if (!$block)
		{
			block_get_data($block, $error, get_variable('type', 'GET', BLOCKTYPE_FILE));
			$error = '';
		}
		blocks_block('add');
	}
	
	$b_show_content = $b_file = $b_rss_show = $b_rss_refresh = $b_rss_url = false;
	$b_header = $_CLASS['core_user']->lang['ADD_NEW'];

	switch ($block['type'])
	{
		case BLOCKTYPE_HTML:
			$b_show_content = true;
			
		break;
		
		case BLOCKTYPE_FILE:
			$b_file = block_select($block['file']);
			
		break;
		
		case BLOCKTYPE_FEED:
			$b_rss_show = true;
			$b_rss_refresh = ($block['refresh']) ? $block['refresh'] : 3600; // 1hr defualt
			$b_rss_url = ($block['url']) ? $block['url'] : '';
			
		break;
	}
	
	$_CLASS['core_template']->assign(array(
		'B_ACTION'		=> adminlink('blocks&amp;mode=save&amp;type='.$block['type'].(($id) ? '&amp;id='.$id : '')),
		'B_HEADER'		=> $b_header,
		'B_TITLE'		=> $block['title'],
		'B_FILE' 		=> $b_file,
		'B_POSITION'	=> block_position_select($block['position']),
		'B_ACTIVE'		=> $block['active'],
		'B_SHOW_CONTENT'=> $b_show_content,
		'B_CONTENT'		=> $block['content'],
		'B_RSS_SHOW'	=> $b_rss_show,
		'B_RSS_REFRESH'	=> $b_rss_refresh,
		'B_RSS_URL'		=> $b_rss_url,
		'B_ERROR'		=> $error,
		'B_EXPIRES'		=> is_numeric($block['expires']) ? $_CLASS['core_user']->format_date($block['expires']) : $block['expires'],
		'B_STARTS'		=> is_numeric($block['time']) ? $_CLASS['core_user']->format_date($block['time']) : $block['time'],
		'B_CURRENT_TIME'=> $_CLASS['core_user']->format_date(time()),
		'L_TITLE'		=> $_CLASS['core_user']->lang['TITLE'],
		'L_FILE'		=> $_CLASS['core_user']->lang['FILE'],
		'L_RSS_REFRESH'	=> $_CLASS['core_user']->lang['RSS_REFRESH'],
		'L_RSS_URL'		=> $_CLASS['core_user']->lang['RSS_URL'],
		'L_POSITION'	=> $_CLASS['core_user']->lang['POSITION'],
		'L_WEIGHT'		=> $_CLASS['core_user']->lang['ORDER'],
		'L_TYPE'		=> $_CLASS['core_user']->lang['TYPE'],
		'L_ACTIVE'		=> $_CLASS['core_user']->lang['ACTIVE'],
		'L_VIEW'		=> $_CLASS['core_user']->lang['VIEW'],
		'L_LANGUAGE'	=> $_CLASS['core_user']->lang['TITLE'],
		'L_FUNCTIONS'	=> $_CLASS['core_user']->lang['OPTIONS'],
		'L_EDIT'		=> $_CLASS['core_user']->lang['EDIT'],
		'L_YES'			=> $_CLASS['core_user']->lang['YES'],
		'L_NO'			=> $_CLASS['core_user']->lang['NO'],
		'L_STARTS'		=> $_CLASS['core_user']->lang['STARTS'],
		'L_EXPIRES'		=> $_CLASS['core_user']->lang['EXPIRES'],
		'L_VIEW'		=> $_CLASS['core_user']->lang['VIEW'],
		'L_HTML_CONTENT'=> $_CLASS['core_user']->lang['HTML_CONTENT'],
		'L_FUNCTIONS'	=> $_CLASS['core_user']->lang['OPTIONS'],
		'L_EDIT'		=> $_CLASS['core_user']->lang['EDIT']
		)
	);
			
	$_CLASS['core_display']->display_head();
	OpenTable();
	$_CLASS['core_template']->display('admin/blocks/edit.html');
	CloseTable();
	$_CLASS['core_display']->display_footer();

}

function blocks_block($mode = false)
{
    global $_CLASS;
	
	$content = '<table class="tablebg" cellspacing="1" width="100%"><tbody><tr><th>Options</th>	</tr>';
			
	if ($mode == 'main')
	{
		$content .= '<tr><td class="row1"><b class="phpbbnav">Main</b>
		<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">
		<li>&#187; <b>Main</li></ul></td><tr>';//<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">
	}
	else
	{
		$content .= '<tr><td class="row2" onmouseover="this.className=\'row1\'" onmouseout="this.className=\'row2\'" onclick="location.href=\''.generate_link('blocks', array('admin' => true)).'\'" nowrap="nowrap"><a class="phpbbnav" href="'.generate_link('blocks', array('admin' => true)).'">Main</a>
		</td></tr>';
	}

	if ($mode == 'add')
	{
		$content .= '<tr><td class="row1"><b class="phpbbnav">Add</b><ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';
		$content .= '<li>&#187;<a href="'.generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_FILE, array('admin' => true)).'"> New Regular Block</a></li>';
		$content .= '<li>&#187;<a href="'.generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_FEED, array('admin' => true)).'"> New Feed Block</a></li>';
		$content .= '<li>&#187;<a href="'.generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_HTML, array('admin' => true)).'"> New HTML Block</a></li></ul></td></tr>';
	}
	else
	{
		$content .= '<td class="row2" onmouseover="this.className=\'row1\'" onmouseout="this.className=\'row2\'" onclick="location.href=\''.generate_link('blocks&amp;mode=add', array('admin' => true)).'\'" nowrap="nowrap"><a class="phpbbnav" href="'.generate_link('blocks&amp;mode=add', array('admin' => true)).'">Add New Block</a>
		</td></tr>';
	}
	
	$content .= '</tbody></table>';
	
	$data = array(
		'title' 	=> 'Block Administration',
		'position'	=> BLOCK_LEFT,
		'type' 		=> BLOCKTYPE_HTML,
		'content'	=> $content,
	);
	
	$_CLASS['core_blocks']->add_block($data);
}

function block_get_data(&$data, &$error, $type = false)
{
	global $_CLASS;

	$error = '';
	$data = array();

	$data['title'] = get_variable('b_title', 'POST', '');

	// leave here for mods, maybe !
	foreach ($data as $field => $value)
	{
		if (!$value)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_'.$field].'<br />';
		}
	}
	
	$data['position'] = get_variable('b_position', 'POST', false, 'integer');

	// Would cause problems is $data['position'] = (int) 0
	if (!$data['position'] && check_position($data['position'], false))
	{
		$data['position'] = BLOCK_RIGHT;
	}

	$data['active'] = (int) get_variable('b_active', 'POST', 0);
	$data['expires'] = get_variable('b_expires', 'POST', '');
	$data['time'] = get_variable('b_time', 'POST', '');

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
	
	$appoved_types = array(BLOCKTYPE_FILE, BLOCKTYPE_FEED, BLOCKTYPE_HTML, BLOCKTYPE_SYSTEM);
	$data['type'] = ($type) ? (int) $type : (int) get_variable('b_type', 'REQUEST', BLOCKTYPE_FILE);

	if (!in_array($data['type'], $appoved_types, true))
	{
		$data['type'] = BLOCKTYPE_FILE;
	}
	
	$data['content'] = '';
	
	switch ($data['type'])
	{
		case BLOCKTYPE_HTML:
			// add content check here
			$data['content'] = trim_text(get_variable('b_content', 'POST', ''));

			if (strlen($data['content']) < 8)
			{
				$error .= $_CLASS['core_user']->lang['ERROR_content'].'<br />';
			}

		break;
		
		//case BLOCKTYPE_FILE:
		case BLOCKTYPE_FILE:
			// Add a file check here
			$data['file'] = trim(get_variable('b_file', 'POST', ''));

		break;
		
		case BLOCKTYPE_FEED:
			// Add an url rss check here
			$data['url'] = get_variable('b_url', 'POST', '');
			$data['refresh'] = get_variable('b_refresh', 'POST', '');
			
		break;
	}
}

function block_position_select($default = false)
{
	global $site_file_root, $_CLASS;
	
	$block_position_array = array(
		BLOCK_RIGHT 	=> $_CLASS['core_user']->lang['B_RIGHT'],
		BLOCK_TOP 		=> $_CLASS['core_user']->lang['B_CENTER_TOP'],
		BLOCK_BOTTOM	=> $_CLASS['core_user']->lang['B_CENTER_BOTTOM'],
		BLOCK_LEFT 		=> $_CLASS['core_user']->lang['B_LEFT']
	);
	
	// Needs some work if a position = 0 can cause problems
	$default = ($default && array_key_exists($default, $block_position_array)) ? $default : BLOCK_LEFT;

	$block_position = '';
	
	foreach ($block_position_array as $value => $name)
	{
		if ($value == $default)
		{
			$block_position .= '<option value="'.$value.'" selected="selected">'.$name.'</option>';
		} else {
			$block_position .= '<option value="'.$value.'">'.$name.'</option>';
		}
	}
	
	return $block_position;
}

function block_save()
{
	global $_CLASS;
	
	if (isset($_REQUEST['id']) && $id = get_id())
	{
		$result = $_CLASS['core_db']->sql_query('SELECT weight, position, type FROM '.BLOCKS_TABLE.' WHERE id='.$id);
		$block = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
		
		if (!$block)
		{	
			url_redirect(generate_link('blocks', array('admin' => true)));
		}

		check_position($block['position']);
		// need to validate data with the db type
		block_get_data($data, $error, $block['type']);
		
		if ($error)
		{
			return block_add($data, $error);
		}
	
		//update old position weight if new position is not the same
		if ($block['position'] != $data['position'])
		{
			// Make an error msg, just incase this fails for some reason
			blocks_change_position($data['position'], $id);
		}
		
		$sql = 'UPDATE '.BLOCKS_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE id='.$id;
		
		$_CLASS['core_db']->sql_query($sql);
		
	} else {
	
		block_get_data($data, $error, get_variable('type', 'REQUEST', BLOCKTYPE_FILE));

		if ($error)
		{
			return block_add($data, $error);
		}
		
		$result = $_CLASS['core_db']->sql_query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position='.$data['position']);
		$maxweight = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);
				
		$data['weight'] = (int) $maxweight['weight'] + 1;
		
		$sql = 'INSERT INTO '.BLOCKS_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data);
		
		$_CLASS['core_db']->sql_query($sql);
	}

	$_CLASS['core_cache']->destroy('blocks');
	$_CLASS['core_display']->meta_refresh('3', generate_link('blocks', array('admin' => true)));
	trigger_error(sprintf($_CLASS['core_user']->lang['SAVED'], generate_link('blocks', array('admin' => true))));	
	
}

function block_select($default = false)
{
	global $site_file_root, $_CLASS;
	
	$block_list = array();
	$default = ($default) ? $default : $_CLASS['core_display']->theme;
	
	$block_list = '';
	$block_list_array = array();
	
	$handle = opendir($site_file_root.'blocks');
	while ($file = readdir($handle))
	{
		if(substr($file, 0, 6) == 'block-')
		{
			$block_list_array[$file] = ereg_replace('_',' ',substr($file,6,-4));
		} 
	}
	
	closedir($handle);
	
	foreach ($block_list_array as $value => $name)
	{
		
		if ($value == $default)
		{
			$block_list .= '<option value="'.$value.'" selected="selected">'.$name.'</option>';
		} else {
			$block_list .= '<option value="'.$value.'">'.$name.'</option>';
		}
	}
	
	return $block_list;
}

?>