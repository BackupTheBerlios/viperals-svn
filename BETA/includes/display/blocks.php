<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

/* to do
RSS system
User blocks this will be a reqular block.
*/

class blocks
{
	var $blocks_array = array();
	var $blocks_loaded = false;
	var $info = false;
	var $content = false;
	var $template = false;
	
	function check_side($side)
	{
		static $side_check = array();

		if (!empty($side_check[$side]))
		{
			return $side_check[$side];
		}

		if (VIPERAL == 'Admin')
		{
			if ($side != BLOCK_LEFT)
			{
				return $side_check[$side] = false;
			}
			return $side_check[$side] = true;
		}
		
		global $Module;

		if (($Module['sides'] == BLOCK_ALL) || ($side == BLOCK_LEFT && $Module['sides'] == BLOCK_LEFT) || ($side == BLOCK_RIGHT && $Module['sides'] == BLOCK_RIGHT))
		{
			if (!$this->blocks_loaded)
			{
				$this->load_blocks();
			}
			
			if (!empty($this->blocks_array[$side]))
			{
				return $side_check[$side] = true;
			}
		}
		
		return $side_check[$side] = false;	
	}
	
	function load_blocks()
	{
		if ($this->blocks_loaded)
		{
			return;
		}
		
		global $_CLASS;

		if (!($this->blocks_array = $_CLASS['cache']->get('blocks')))
		{
			$result = $_CLASS['db']->sql_query('SELECT * FROM '.BLOCKS_TABLE." WHERE active='1' ORDER BY weight ASC");

			while($row = $_CLASS['db']->sql_fetchrow($result)) {
				$this->blocks_array[$row['position']][] = $row;
			}
			
			$_CLASS['cache']->put('blocks', $this->blocks_array);
			$_CLASS['db']->sql_freeresult($result);
		}
		
		$this->blocks_loaded = true;
	}
	
	function display($position)
	{
		static $expire_updated = false;
		
		if (($position == BLOCK_LEFT || $position == BLOCK_RIGHT) && !$this->check_side($position))
		{
			return false;
		}
		
		if (VIPERAL == 'Admin' && $position != BLOCK_LEFT)
		{
			return false;
		}
		
		global $_CLASS;
		
		if (!$this->blocks_loaded)
		{
			$this->load_blocks();
		}
		
		if (!empty($this->blocks_array[$position]))
		{
			foreach($this->blocks_array[$position] AS $this->blocksrow) {
			
				if ($this->blocksrow['expires'] && !$expire_updated && (time() >= $this->blocksrow['expires']))
				{
					$_CLASS['db']->sql_query('UPDATE '.BLOCKS_TABLE." SET active='0' WHERE expires >=".time());
					$expire_updated = true;
					$_CLASS['cache']->destroy('blocks');
	
					continue;
				}
	
				$this->display_blocks();
	
			}
		}

		return false;
	}

	function display_blocks()
	{
		Switch ($this->blocksrow['type'])
		{
	   
			case BLOCKTYPE_FILE:
			case BLOCKTYPE_SYSTEM:
				
				$this->block_file();
				break;
				
			case BLOCKTYPE_MESSAGE_TOP:
				$this->block_message();
				break;
			
			case BLOCKTYPE_MESSAGE_BOTTTOM:
				$this->block_message();
				break;
					
			case BLOCKTYPE_HTML:
				$this->block_html();
		}
		
		return;
	}
	
	function block_file()
	{
		global $_CLASS;
		
		if (!$this->blocksrow['file']) { return; };
		
		if (file_exists('blocks/'.$this->blocksrow['file'])) {
		
			/*
			$startqueries = $_CLASS['db']->sql_num_queries();
			$starttime = explode(' ', microtime());
			$starttime = $starttime[0] + $starttime[1];
			*/
		
			require('blocks/'.$this->blocksrow['file']);
			
			/*
			$endtime = explode(' ', microtime());
			$endtime = $endtime[0] + $endtime[1];
			*/
			
			if (!$this->content && !$this->template)
			{
				if (is_admin())
				{
					$this->content = ($this->info) ? $this->info : $_CLASS['user']->lang['BLOCK_ERROR2'];
				} else {
					return;
				}
			}

		} else {
		
			if (is_admin())
			{
				$this->content = $_CLASS['user']->lang['BLOCK_ERROR1'];
			} else {
				return;
			}
			
		}

		/*
		$this->content .= '<div style="text-align: center;">';
		$this->content .= '<br />block queries: '.($_CLASS['db']->sql_num_queries() - $startqueries);
		$this->content .= '<br />Generation time: '.round($endtime - $starttime, 4).'s';
		$this->content .= '</div>';
		*/

		if ($this->blocksrow['position'] == BLOCK_LEFT || $this->blocksrow['position'] == BLOCK_RIGHT)
		{
			themesidebox($this->blocksrow['title'], $this->content, $this->blocksrow['id'], $this->template);
		} else {
			$this->center_block();
		}
		
		$this->content = $this->template = $this->info = false;
	}
			
	function block_message()
	{
		global $_CLASS;
		
		if (($this->blocksrow['type'] == BLOCKTYPE_MESSAGE_TOP) && (!$_CLASS['display']->homepage))
		{
			return;
		}
		
		if (is_admin())
		{
			$expires = ($this->blocksrow['expires']) ? $_CLASS['user']->lang['EXPIRES'].' '.$_CLASS['user']->format_date($this->blocksrow['expires']) : false;
			$edit = '<a href="'.adminlink('message&amp;mode=edit&amp;id='.$this->blocksrow['id']).'">'.$_CLASS['user']->lang['EDIT'].'</a>';
		} else {
			$edit = $expires = false;
		}
		
		if (THEMEPLATE)
		{
			$_CLASS['template']->assign_vars_array('messageblock', array(
				'TITLE'		=> $this->blocksrow['title'],
				'CONTENT'	=> $this->blocksrow['content'],
				'EXPIRES'	=> $expires,
				'EDIT'		=> $edit,
				'HIDE'		=> hideblock($this->blocksrow['id']) ? 'style="display: none"' : '',
				'ID'		=> $this->blocksrow['id'],
				)
			);
		
		} else {
		
            global $textcolor2;
            
            $expires = ($expires) ? $expires.' | ' : '';
            
			OpenTable();
			echo '<div style="text-align: center; color: '.$textcolor2.'"><b>'.$this->blocksrow['title'].'</b></div><br />'.$this->blocksrow['content'];
			
			if (is_admin())
			{
				echo '<br /><br /><div align="right">[ '.$expires.$edit.' ]</font></div>';
			}
			
			CloseTable();
		}
	}
	
	function center_block() {
	
		if (THEMEPLATE)
		{
			global $_CLASS;
			
			$this->blocksrow['position'] = ($this->blocksrow['position'] == BLOCK_TOP) ? 'center' : 'bottom';
			
			$_CLASS['template']->assign_vars_array($this->blocksrow['position'].'block', array(
				'TITLE'   => $this->blocksrow['title'],
				'CONTENT' => $this->content,
				'TEMPLATE' => $this->template
				)
			);
			
		} else {
		
			OpenTable();
			echo '<div align="center"><b>'.$this->blocksrow['title'].'</b></div><br />'.$this->content;
			CloseTable();
			echo '<br />';
		
		}
	}
	
}

?>