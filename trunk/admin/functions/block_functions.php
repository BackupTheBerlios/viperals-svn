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

function block_auth($id)
{
	global $_CLASS;

	$result = $_CLASS['core_db']->query('SELECT position, auth FROM ' . BLOCKS_TABLE . ' WHERE id='.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);
	
	if (!$block)
	{
		trigger_error('BLOCK_NOT_FOUND');
	}
	
	$block['auth'] = ($block['auth']) ? unserialize($block['auth']) : '';
	
	check_position($block['position']);
	
	$_CLASS['core_display']->display_header();

	$auth = $_CLASS['core_auth']->generate_auth_options($block['auth']);

	if ($auth !== false)
	{
		if (is_null($auth))
		{
			$block['auth'] = $auth = '';
		}
		else
		{
			$block['auth'] = $auth;
			$auth = $_CLASS['core_db']->escape(serialize($auth));
		}

		$_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . " SET auth = '$auth' WHERE id = $id");
		$_CLASS['core_cache']->destroy('blocks');
	}
	
	$_CLASS['core_display']->display_footer();
}
		
function block_change($id)
{
	global $_CLASS;
	
	$result = $_CLASS['core_db']->query('SELECT block_status, block_position FROM ' . BLOCKS_TABLE . ' WHERE block_id='.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block)
	{
		trigger_error('BLOCK_NOT_FOUND');
	}

	check_position($block['block_position']);
	$status = ($block['block_status']) ? 0 : 1;

	$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_status = '.$status.' WHERE block_id = '.$id);

	$_CLASS['core_cache']->destroy('blocks');
}

function block_order($id, $option)
{
	global $_CLASS;

	if (!in_array($option, array('down', 'up', 'bottom', 'top')))
	{
		return;
	}

	$result = $_CLASS['core_db']->query('SELECT block_position, block_order FROM ' . BLOCKS_TABLE . ' WHERE block_id= ' . $id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block)
	{
		trigger_error('BLOCK_NOT_FOUND');
	}

	check_position($block['block_position']);
	settype($block['block_order'], 'integer');

	switch ($option)
	{
		case 'down':

			$result = $_CLASS['core_db']->query('SELECT MAX(block_order) as block_order FROM ' . BLOCKS_TABLE . ' WHERE block_position='.$block['block_position']);
			list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
			$_CLASS['core_db']->free_result($result);

			if ($block['block_order'] < $max_order)
			{
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = block_order-1 WHERE block_position = '.$block['block_position'].' AND block_order='.($block['block_order'] + 1));
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = '.($block['block_order'] + 1).' WHERE block_id ='. $id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;

		case 'bottom':

			$result = $_CLASS['core_db']->query('SELECT MAX(weight) as block_order FROM ' . BLOCKS_TABLE . ' WHERE block_position='.$block['block_position']);
			list($max_order) = $_CLASS['core_db']->fetch_row_($result);
			$_CLASS['core_db']->free_result($result);

			if ($block['block_order'] < $max_order)
			{
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = block_order-1 WHERE block_position='.$block['block_position'].' AND block_order > '.$block['block_order']);
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = '.$max_order.' WHERE block_id = '.$id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;

		case 'up':

			if ($block['block_order'] && $block['weight'] != 1)
			{
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = block_order+1 WHERE block_position='.$block['block_position'].' AND block_order = '.($block['block_order'] - 1));
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order='.($block['block_order'] -1 ).' WHERE block_id ='. $id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;

		case 'top':

			if ($block['block_order'] != 1)
			{
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = block_order+1 WHERE block_position='.$block['block_position'].' AND block_order < '.$block['block_order']);
				$result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = 1 WHERE block_id = '.$id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;
	}
}

function block_delete($id, $return_link = false)
{
    global $_CLASS;

	$result = $_CLASS['core_db']->query('SELECT block_order, block_type, block_position FROM ' . BLOCKS_TABLE . ' WHERE block_id='.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block || ($block['block_type'] == BLOCKTYPE_SYSTEM))
	{
		trigger_error(($block) ? 'BLOCK_NOT_DELETABLE' : 'BLOCK_NOT_FOUND');
	}

	check_position($block['block_position']);

    if (display_confirmation())
    {
		$_CLASS['core_db']->query('DELETE from ' . BLOCKS_TABLE . ' where block_id='.$id);
        $result = $_CLASS['core_db']->query('UPDATE ' . BLOCKS_TABLE . ' SET block_order = block_order-1 WHERE block_position='.$block['block_position'].' AND block_order > '.$block['block_order']);

        $_CLASS['core_cache']->destroy('blocks');
        
        if ($return_link)
        {
			trigger_error('Block deleted<br/><a href="'.$return_link.'">Click here to return</a>');	        
		}
	}
	
	if ($return_link)
	{
		url_redirect($return_link);
	}
}

?>