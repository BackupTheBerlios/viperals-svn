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

	$result = $_CLASS['core_db']->query('SELECT position, auth FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);
	
	if (!$block)
	{
		trigger_error('BLOCK_NOT_FOUND');
	}
	
	$block['auth'] = ($block['auth']) ? unserialize($block['auth']) : '';
	
	check_position($block['position']);
	
	if ($auth = $_CLASS['core_auth']->generate_auth_options($block['auth']))
	{
		$block['auth'] = ($auth === true) ? '' : $auth;
		$auth = ($auth === true) ? '' : $_CLASS['core_db']->escape(serialize($auth));
	
		$_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE." SET auth = '$auth' WHERE id = $id");
		$_CLASS['core_cache']->destroy('blocks');
	}
	
	$_CLASS['core_display']->display_header();
	$_CLASS['core_auth']->generate_auth_options($block['auth'], true);
	$_CLASS['core_display']->display_footer();
}
		
function block_change($id)
{
	global $_CLASS;
	
	$result = $_CLASS['core_db']->query('SELECT active, position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block)
	{
		trigger_error('BLOCK_NOT_FOUND');
	}
	check_position($block['position']);
	$active = ($block['active']) ? 0 : 1;

	$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET active='.$active.' WHERE id='.$id);

	$_CLASS['core_cache']->destroy('blocks');
}

function block_weight($id, $option)
{
	global $_CLASS;

	if (!in_array($option, array('down', 'up', 'bottom', 'top')))
	{
		return;
	}

	$result = $_CLASS['core_db']->query('SELECT position, weight FROM '.BLOCKS_TABLE.' WHERE id='. (int) $id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block)
	{
		trigger_error('BLOCK_NOT_FOUND');
	}

	check_position($block['position']);
	$block['weight'] = (int) $block['weight'];

	switch ($option)
	{
		case 'down':

			$result = $_CLASS['core_db']->query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position='.$block['position']);
			$maxweight = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($block['weight'] < $maxweight['weight'])
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight='.($block['weight'] + 1));
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight='.($block['weight'] + 1).' WHERE id ='. $id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;

		case 'up':

			if ($block['weight'] && $block['weight'] != 1)
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight=weight+1 WHERE position='.$block['position'].' AND weight='.($block['weight'] - 1));
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight='.($block['weight'] -1 ).' WHERE id ='. $id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;

		case 'bottom':

			$result = $_CLASS['core_db']->query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position='.$block['position']);
			$maxweight = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($block['weight'] < $maxweight['weight'])
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight > '.$block['weight']);
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight='.$maxweight['weight'].' WHERE id='.$id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;

		case 'top':

			if ($block['weight'] != 1)
			{
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight=weight+1 WHERE position='.$block['position'].' AND weight < '.$block['weight']);
				$result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight=1 WHERE id='.$id);
			}

			$_CLASS['core_cache']->destroy('blocks');
		break;
	}
}

function block_delete($id, $return_link = '')
{
    global $_CLASS;

	$result = $_CLASS['core_db']->query('SELECT weight, type, position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$block || ($block['type'] == BLOCKTYPE_SYSTEM))
	{
		trigger_error(($block) ? 'BLOCK_NOT_DELETABLE' : 'BLOCK_NOT_FOUND');
	}

	check_position($block['position']);

// TEMP
    if (get_variable('confirm', 'POST', false) == 'Delete')
    {
        $result = $_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight > '.$block['weight']);
        $_CLASS['core_db']->query('DELETE from '.BLOCKS_TABLE.' where id='.$id);

        $_CLASS['core_cache']->destroy('blocks');
        
        trigger_error('Block deleted<br/><a href="'.generate_link($return_link, array('admin' => true)).'">Click here to return</a>');	        
    }
	else
	{
		$_CLASS['core_display']->display_header();
		echo $_CLASS['core_display']->table_open;
		
		echo '<form name="confirm" action="" method="post">
		<center><b>If your certain that you want to remove this item click delete to continue<br/>';
		echo '<a href="'.generate_link($_CLASS['core_user']->url, array('admin' => true)).'">Click here to return</a><br/><br/>
		<input type="submit" name="confirm" value="Delete" class="btnmain" />&nbsp;&nbsp;<input type="submit" name="cancel" value="Cancel" class="button" />
		</center>';

		echo $_CLASS['core_display']->table_close;
		
        $_CLASS['core_display']->display_footer();
    }
}

?>