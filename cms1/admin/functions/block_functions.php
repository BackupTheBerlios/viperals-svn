<?php

function block_change($id)
{	
	global $_CLASS;
	
	$result = $_CLASS['core_db']->sql_query('SELECT active, position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	if (!$block)
	{
		// use trigger error
		url_redirect(generate_link('', array('admin' => true)));
	}
	
	check_position($block['position']);

	$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET active='.intval(!$block['active']).' WHERE id='.$id);
	
	$_CLASS['core_cache']->destroy('blocks');
}

    
function block_weight($id, $option)
{ 
	global $_CLASS;
	
	// Add in_array so we don't waste time
	
	$result = $_CLASS['core_db']->sql_query('SELECT position, weight FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);
			
	if (!$block)
	{
		// use trigger error
		url_redirect(generate_link('', array('admin' => true)));
	}
	
	check_position($block['position']);

	switch ($option)
	{
	
		case 'down':
		
			$result = $_CLASS['core_db']->sql_query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position='.$block['position']);
			$maxweight = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
			
			if ($block['weight'] < $maxweight['weight'])
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight='.($block['weight']+1));
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight+1 WHERE id ='. $id);
			}
			
			$_CLASS['core_cache']->destroy('blocks');
			break;
			
		case 'up':
		
			if ($block['weight'] && $block['weight'] != 1)
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight+1 WHERE position='.$block['position'].' AND weight='.($block['weight']-1));
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE id ='. $id);
			}
			
			$_CLASS['core_cache']->destroy('blocks');
			break;
		
		case 'bottom':
		
			$result = $_CLASS['core_db']->sql_query('SELECT MAX(weight) as weight FROM '.BLOCKS_TABLE.' WHERE position='.$block['position']);
			$maxweight = $_CLASS['core_db']->sql_fetchrow($result);
			$_CLASS['core_db']->sql_freeresult($result);
			
			if ($block['weight'] < $maxweight['weight'])
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight > '.$block['weight']);
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight='.$maxweight['weight'].' WHERE id='.$id);
			}
			
			$_CLASS['core_cache']->destroy('blocks');
			break;
					
		case 'top':
			
		
			if ($block['weight'] != 1)
			{
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight+1 WHERE position='.$block['position'].' AND weight < '.$block['weight']);
				$result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=1 WHERE id='.$id);
			}
			
			$_CLASS['core_cache']->destroy('blocks');
			break;
	}
}

function block_delete($id)
{
    global $_CLASS;
    
	$result = $_CLASS['core_db']->sql_query('SELECT weight, type, position FROM '.BLOCKS_TABLE.' WHERE id='.$id);
	$block = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	if (!$block || ($block['type'] == BLOCKTYPE_SYSTEM))
	{
		// use trigger error
		url_redirect(generate_link('', array('admin' => true)));
	}
	
	check_position($block['position']);

    if (get_variable('ok', 'REQUEST', false))
    {
		
        $result = $_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET weight=weight-1 WHERE position='.$block['position'].' AND weight > '.$block['weight']);
        $_CLASS['core_db']->sql_query('delete from '.BLOCKS_TABLE.' where id='.$id);

        $_CLASS['core_cache']->destroy('blocks');
		        
    } else {
    
		$_CLASS['core_display']->display_head();
		OpenTable();
		echo '<center>Remove Message ?';
		echo '<br /><br />[ <a href="'.generate_link("&amp;mode=delete&amp;id=$id&amp;ok=1", array('admin' => true)).'">Yes</a> | <a href="'.generate_link('', array('admin' => true)).'">No</a> ]</center>';
		CloseTable();
        $_CLASS['core_display']->display_footer();
    }
}


?>