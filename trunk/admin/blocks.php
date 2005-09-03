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

if (isset($_REQUEST['mode']))
{
	if ($id = get_variable('id', 'REQUEST', false, 'integer'))
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
				block_delete($id);
			break;
	
			case 'position':
				if ($position = get_variable('option', 'GET', false, 'integer'))
				{
					blocks_change_position($position, $id);
				}
			break;

			case 'edit':
				block_add($id);
				script_close(false);
			break;

			case 'auth':
				block_auth($id);
			break;
		}
	}

	switch ($_REQUEST['mode'])
	{
		case 'add':
			block_add(false);
			script_close(false);
		break;
		   
		case 'save':
			block_save($id);
		break;
	}
}

blocks_block('main');

$result = $_CLASS['core_db']->query('SELECT block_id, block_title, block_type,  block_position, block_order, block_status, block_file, block_auth
	FROM ' . BLOCKS_TABLE . '
	WHERE block_position IN (' . BLOCK_RIGHT . ', ' . BLOCK_TOP . ', ' . BLOCK_BOTTOM . ', ' . BLOCK_LEFT . ') ORDER BY block_position, block_order ASC');

$block_position = array(BLOCK_RIGHT => 'right', BLOCK_TOP => 'centertop', BLOCK_BOTTOM => 'centerbottom', BLOCK_LEFT => 'left');
$in_position = false;
$blocks = array();

while($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	if ($in_position != $row['block_position'])
	{
		$weigth[$row['block_position']] = $row['block_order'];
		$in_position == $row['block_position'];
	}
	$blocks[] = $row;
}
$_CLASS['core_db']->free_result($result);

foreach ($blocks as $block)
{
	$error = false;
	switch ($block['block_type'])
	{
		case BLOCKTYPE_FILE:
			if (!$block['block_file'] || !file_exists($site_file_root.'blocks/'.$block['block_file']))
			{
				$error = 'File_MISSING';
			}
		break;
	}

	$active = $block['block_status'] == STATUS_ACTIVE;

	$_CLASS['core_template']->assign_vars_array($block_position[$block['block_position']].'_admin_blocks', array(
			'ACTIVE'		=> $active,
			'ACTIVE_LINK'	=> generate_link('blocks&amp;mode=change&amp;id='.$block['block_id'], array('admin' => true)),
			'CHANGE'		=> ($active) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],
			'ERROR'			=> ($error) ? $_CLASS['core_user']->get_lang($error) : false,

			'EDIT_LINK'		=> generate_link('blocks&amp;mode=edit&amp;id='.$block['block_id'], array('admin' => true)),
			'AUTH_LINK'		=> generate_link('blocks&amp;mode=auth&amp;id='.$block['block_id'], array('admin' => true)),

			'DELETE_LINK' 	=> ($block['block_type'] != BLOCKTYPE_SYSTEM) ? generate_link('blocks&amp;mode=delete&amp;id='.$block['block_id'], array('admin' => true)) : '',
			'TITLE'			=> $block['block_title'],
			'TYPE'			=> $_CLASS['core_user']->get_lang('TYPE_'.$block['block_type']),

			'ORDER_DOWN' 	=> ($block['block_order'] < $weigth[$block['block_position']]),
			'ORDER_UP'		=> ($block['block_order'] > 1),

			'LINK_ORDER_UP' 	=> generate_link('blocks&amp;mode=order&amp;option=up&amp;id='.$block['block_id'], array('admin' => true)),
			'LINK_ORDER_TOP' 	=> generate_link('blocks&amp;mode=order&amp;option=top&amp;id='.$block['block_id'], array('admin' => true)),
			'LINK_ORDER_DOWN'	=> generate_link('blocks&amp;mode=order&amp;option=down&amp;id='.$block['block_id'], array('admin' => true)),
			'LINK_ORDER_BOTTOM'	=> generate_link('blocks&amp;mode=order&amp;option=bottom&amp;id='.$block['block_id'], array('admin' => true)),
	));
}

$_CLASS['core_template']->assign_array(array(
	'L_BLOCK_REGULAR'	=> 'Add New Regular Block',
	'L_BLOCK_HTML'		=> 'Add New HTML Block',
	'L_BLOCK_FEED'		=> 'Add New Feed Block',
	'N_BLOCK_FILE'		=> generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_FILE, array('admin' => true)),
	'N_BLOCK_FEED'		=> generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_FEED, array('admin' => true)),
	'N_BLOCK_HTML'		=> generate_link('blocks&amp;mode=add&amp;type='.BLOCKTYPE_HTML, array('admin' => true))
));

$_CLASS['core_template']->display('admin/blocks/index.html');
	
script_close(false);

function blocks_change_position($position, $id)
{
	global $_CLASS;

	check_position($position);

	$result = $_CLASS['core_db']->query('SELECT block_position, block_order FROM '.BLOCKS_TABLE.' WHERE block_id = '.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block || $block['block_position'] == $position)
	{
		return;
	}

	check_position($block['block_position']);

	$result = $_CLASS['core_db']->query('SELECT max(block_order) as max_order FROM '.BLOCKS_TABLE.' WHERE block_position = '.$position);
	list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
	$_CLASS['core_db']->free_result($result);

	$new_order = (int) $max_order + 1;

	$_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE." SET block_position = $position , block_order = $new_order where block_id = $id");
	$_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET block_order = block_order - 1 WHERE block_position = '.$block['block_position'].' AND block_order > '.$block['block_order']);

	$_CLASS['core_cache']->destroy('blocks');
}

function block_add($id = false, $block = false, $error = false)
{
    global $_CLASS;

	if ($id)
	{
		$result = $_CLASS['core_db']->query('SELECT * FROM '.BLOCKS_TABLE.' WHERE block_id = '.$id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$block)
		{
			return;
		}

		check_position($block['block_position']);

		if (isset($_POST['submit']))
		{
			// need to re-validate data with the db type
			block_get_data($block_post, $error, $block['block_type']);
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

	$b_show_content = $b_file = $b_rss_show = $b_rss_rate = $b_rss_url = false;
	$b_header = $_CLASS['core_user']->lang['ADD_NEW'];

	switch ($block['block_type'])
	{
		case BLOCKTYPE_HTML:
			$b_show_content = true;
		break;

		case BLOCKTYPE_FILE:
			$b_file = block_select($block['block_file']);
		break;

		case BLOCKTYPE_FEED:
			$b_rss_show = true;
			$b_rss_rate = ($block['block_rss_rate']) ? $block['block_rss_rate'] : 3600; // 1hr defualt
			$b_rss_url = ($block['block_rss_url']) ? $block['block_rss_url'] : '';
		break;
	}

	$_CLASS['core_template']->assign_array(array(
		'B_ACTION'		=> generate_link('blocks&amp;mode=save&amp;type='.$block['block_type'].(($id) ? '&amp;id='.$id : ''), array('admin' => true)),
		'B_HEADER'		=> $b_header,
		'B_TITLE'		=> $block['block_title'],
		'B_FILE' 		=> $b_file,
		'B_POSITION'	=> block_position_select($block['block_position']),
		'B_ACTIVE'		=> $block['block_status'],
		'B_SHOW_CONTENT'=> $b_show_content,
		'B_CONTENT'		=> $block['block_content'],
		'B_RSS_SHOW'	=> $b_rss_show,
		'B_RSS_REFRESH'	=> $b_rss_rate,
		'B_RSS_URL'		=> $b_rss_url,
		'B_ERROR'		=> $error,
		'B_EXPIRES'		=> is_numeric($block['block_expires']) ? $_CLASS['core_user']->format_date($block['block_expires'], 'M d, Y h:i a') : $block['block_expires'],
		'B_STARTS'		=> is_numeric($block['block_starts']) ? $_CLASS['core_user']->format_date($block['block_starts'], 'M d, Y h:i a') : $block['block_starts'],
		'B_CURRENT_TIME'=> $_CLASS['core_user']->format_date($_CLASS['core_user']->time),
	));

	$_CLASS['core_template']->display('admin/blocks/edit.html');
}

function blocks_block($mode = false)
{
    global $_CLASS;

	$content = '<table class="tablebg" cellspacing="1" width="100%"><tbody><tr><th>Options</th>	</tr>';

	if ($mode == 'main')
	{
		$content .= '<tr><td class="row1"><b class="phpbbnav">Main</b>
		<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">
		<li>&#187; <b>Main</li></ul></td><tr>';
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

	$data['block_title'] = get_variable('b_title', 'POST', '');

	// leave here for mods, maybe !
	foreach ($data as $field => $value)
	{
		if (!$value)
		{
			$error .= $_CLASS['core_user']->get_lang('ERROR_'.$field).'<br />';
		}
	}

	$data['block_position'] = get_variable('b_position', 'POST', false, 'integer');

	if (!$data['block_position'] || !check_position($data['block_position'], false))
	{
		$data['block_position'] = BLOCK_RIGHT;
	}

	$data['block_status']	= (get_variable('b_active', 'POST', STATUS_DISABLED, 'integer') === STATUS_DISABLED) ? STATUS_DISABLED : STATUS_ACTIVE;
	$data['block_expires']	= get_variable('b_expires', 'POST', '');
	$data['block_starts']	= get_variable('b_time', 'POST', '');

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

	$appoved_types = array(BLOCKTYPE_FILE, BLOCKTYPE_FEED, BLOCKTYPE_HTML, BLOCKTYPE_SYSTEM);
	$data['block_type'] = ($type) ? (int) $type : (int) get_variable('b_type', 'REQUEST', BLOCKTYPE_FILE);

	if (!in_array($data['block_type'], $appoved_types, true))
	{
		$data['block_type'] = BLOCKTYPE_FILE;
	}

	$data['block_content'] = '';

	switch ($data['block_type'])
	{
		case BLOCKTYPE_HTML:
			//$data['content'] = modify_lines(trim(get_variable('b_content', 'POST', '')), '<br/>');
			$data['block_content'] = modify_lines(trim(get_variable('b_content', 'POST', '')), '');

			if (mb_strlen($data['block_content']) < 6)
			{
				$error .= $_CLASS['core_user']->lang['ERROR_content'].'<br />';
			}
		break;
		
		case BLOCKTYPE_FILE:
			// Add a file check here
			$data['block_file'] = trim(get_variable('b_file', 'POST', ''));
		break;
		
		case BLOCKTYPE_FEED:
			// Add an url rss check here
			$data['block_rss_url'] = get_variable('b_url', 'POST', '');
			$data['block_rss_rate'] = get_variable('b_refresh', 'POST', '');
		break;
	}

	if (!$error)
	{
		$data['block_starts'] = ($start) ? $_CLASS['core_user']->time_convert($start, 'gmt') : 0;
		$data['block_expires'] = ($expires) ? $_CLASS['core_user']->time_convert($expires, 'gmt') : 0;
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
		}
		else
		{
			$block_position .= '<option value="'.$value.'">'.$name.'</option>';
		}
	}
	
	return $block_position;
}

function block_save($id = false)
{
	global $_CLASS;
	
	if ($id)
	{
		$result = $_CLASS['core_db']->query('SELECT block_order, block_position, block_type FROM '.BLOCKS_TABLE.' WHERE block_id = '.$id);
		$block = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$block)
		{	
			return;
		}

		check_position($block['block_position']);
		// need to validate data with the db type
		block_get_data($data, $error, $block['block_type']);

		if ($error)
		{
			return block_add($id, $data, $error);
		}

		//update old position order if new position is not the same
		if ($block['block_position'] != $data['block_position'])
		{
			// Make an error msg, just incase this fails for some reason
			blocks_change_position($data['block_position'], $id);
		}
		
		$sql = 'UPDATE '.BLOCKS_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE block_id = '.$id;
	}
	else
	{
		block_get_data($data, $error, get_variable('type', 'REQUEST', BLOCKTYPE_FILE));

		if ($error)
		{
			return block_add(false, $data, $error);
		}

		$result = $_CLASS['core_db']->query('SELECT MAX(block_order) as block_order FROM '.BLOCKS_TABLE.' WHERE block_position = '.$data['block_position']);
		list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
		$_CLASS['core_db']->free_result($result);
				
		$data['block_order'] = (int) $max_order + 1;
		
		$sql = 'INSERT INTO '.BLOCKS_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data);
	}

	$_CLASS['core_db']->query($sql);
	$_CLASS['core_cache']->destroy('blocks');

	$_CLASS['core_display']->meta_refresh('3', generate_link('blocks', array('admin' => true)));
	trigger_error(sprintf($_CLASS['core_user']->lang['SAVED'], generate_link('blocks', array('admin' => true))));	
	
}

function block_select($default = false)
{
	global $site_file_root, $_CLASS;

	$block_list_array = array();

	$handle = opendir($site_file_root.'blocks');

	while ($file = readdir($handle))
	{
		if (substr($file, 0, 6) == 'block-')
		{
			$block_list_array[$file] = ereg_replace('_',' ',substr($file,6,-4));
		} 
	}

	closedir($handle);

	$block_list = '';

	foreach ($block_list_array as $value => $name)
	{
		if ($value == $default)
		{
			$block_list .= '<option value="'.$value.'" selected="selected">'.$name.'</option>';
		}
		else
		{
			$block_list .= '<option value="'.$value.'">'.$name.'</option>';
		}
	}

	return $block_list;
}

?>