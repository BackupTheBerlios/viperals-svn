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
*/

class blocks
{
	var $blocks_array = array();
	var $blocks_loaded;
	var $info;
	var $content;
	var $template;
	
	function check_side($side)
	{
		static $side_check = array();

		if (!empty($side_check[$side]))
		{
			return $side_check[$side];
		}

		global $Module;

		if ($Module['sides'] == BLOCK_ALL || ($side == BLOCK_LEFT && $Module['sides'] == BLOCK_LEFT) || ($side == BLOCK_RIGHT && $Module['sides'] == BLOCK_RIGHT))
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
			$result = $_CLASS['db']->sql_query('SELECT * FROM '.BLOCKS_TABLE.' WHERE active > 0 ORDER BY weight ASC');

			while($row = $_CLASS['db']->sql_fetchrow($result))
			{
				$this->blocks_array[$row['position']][] = $row;
			}
			
			$_CLASS['cache']->put('blocks', $this->blocks_array);
			$_CLASS['db']->sql_freeresult($result);
		}
		
		$this->blocks_loaded = true;
	}
	
	function display($position)
	{
		if (($position == BLOCK_LEFT || $position == BLOCK_RIGHT) && !$this->check_side($position))
		{
			return false;
		}
				
		if (!$this->blocks_loaded)
		{
			$this->load_blocks();
		}
		
		if (empty($this->blocks_array[$position]))
		{
			return false;
		}
		
		static $expire_updated = false;
		global $_CLASS;
		
		foreach($this->blocks_array[$position] AS $this->block)
		{
			//auth check and language check here.
			if (!$_CLASS['user']->admin_auth('blocks') && !$_CLASS['user']->auth($this->block['auth']))
			{
				continue;
			}
			
			if ($this->block['expires'] && !$expire_updated && ($_CLASS['user']->time >= $this->block['expires']))
			{
				$_CLASS['db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET active=0 WHERE expires > 0 AND expires <='.$_CLASS['user']->time);
										
				$_CLASS['cache']->destroy('blocks');
				$expire_updated = true;

				continue;
			}
			
			if ($this->block['time'] && ($this->block['time'] > $_CLASS['user']->time))
			{
				continue;
			}
			
			$this->display_blocks();

		}
		
		unset($this->blocks_array[$position]);
	}

	function display_blocks()
	{
		Switch ($this->block['type'])
		{
			case BLOCKTYPE_FILE:
			case BLOCKTYPE_SYSTEM:
				
				$this->block_file();
				break;
				
			case BLOCKTYPE_MESSAGE_BOTTTOM:
			case BLOCKTYPE_MESSAGE_TOP:
				$this->block_message();
				break;
					
			case BLOCKTYPE_HTML:
				$this->block_html();
				break;
				
			case BLOCKTYPE_FEED();
				$this->block_feed();
				break;
		}
		
		return;
	}
	
	function block_file()
	{
		global $_CLASS, $site_file_root;
		
		if (file_exists($site_file_root.'blocks/'.$this->block['file']))
		{
			
			/*
			$startqueries = $_CLASS['db']->sql_num_queries();
			$startqueriestime = $_CLASS['db']->sql_time;
			$starttime = explode(' ', microtime());
			$starttime = $starttime[0] + $starttime[1];
			*/
		
			include($site_file_root.'blocks/'.$this->block['file']);
			
			/*
			$endtime = explode(' ', microtime());
			$endtime = $endtime[0] + $endtime[1];
			*/
			
			if (!$this->content && !$this->template)
			{
				if ($_CLASS['user']->admin_auth('blocks'))
				{
					$this->content = ($this->info) ? $this->info : $_CLASS['user']->lang['BLOCK_ERROR2'];
				} else {
					return;
				}
			}

		} else {
		
			if ($_CLASS['user']->admin_auth('blocks'))
			{
				$this->content = $_CLASS['user']->lang['BLOCK_ERROR1'];
			} else {
				return;
			}
			
		}

		/*
		$this->content .= '<div style="text-align: center;">';
		if ($_CLASS['db']->sql_num_queries() - $startqueries)
		{
			$this->content .= '<br />block queries: '.($_CLASS['db']->sql_num_queries() - $startqueries)
							.' in '.round($_CLASS['db']->sql_time - $startqueriestime, 4).' s';
		}
		$this->content .= '<br />Generation time: '.round($endtime - $starttime, 4).'s';
		$this->content .= '</div>';
		*/

		if ($this->block['position'] == BLOCK_LEFT || $this->block['position'] == BLOCK_RIGHT)
		{
			$this->block_side();
		} else {
			$this->block_center();
		}
		
		$this->content = $this->template = $this->info = false;
	}
			
	function block_message()
	{
		global $_CLASS;
		
		if (($this->block['active'] == '1') && (!$_CLASS['display']->homepage))
		{
			return;
		}
		
		if ($_CLASS['user']->admin_auth('message'))
		{
			$expires = ($this->block['expires']) ? $_CLASS['user']->lang['EXPIRES'].' '.$_CLASS['user']->format_date($this->block['expires']) : false;
			$edit = '<a href="'.adminlink('message&amp;mode=edit&amp;id='.$this->block['id']).'">'.$_CLASS['user']->lang['EDIT'].'</a>';
		
		} else {
		
			$edit = $expires = false;
		}
		
		$_CLASS['template']->assign_vars_array('messageblock', array(
				'TITLE'		=> $this->block['title'],
				'CONTENT'	=> $this->block['content'],
				'EXPIRES'	=> $expires,
				'EDIT'		=> $edit,
				'HIDE'		=> hideblock($this->block['id']) ? 'style="display: none"' : '',
				'ID'		=> $this->block['id'],
			)
		);
	}
	
	function block_side()
	{
		global $_CLASS;
		
		$this->block['position'] = ($this->block['position'] == BLOCK_RIGHT) ? 'right' : 'left';
		
		$_CLASS['template']->assign_vars_array($this->block['position'].'block', array(
			'TITLE'		=> $this->block['title'],
			'CONTENT'	=> $this->content,
			'ID'		=> $this->block['id'],
			'COLLAPSE'	=> hideblock($this->block['id']) ? 'style="display: none"' : '',
			'TEMPLATE'	=> $this->template,
			)
		);
	}
	
	function block_html()
	{
		$this->content = $this->block['content'];
		
		if ($this->block['position'] == BLOCK_LEFT || $this->block['position'] == BLOCK_RIGHT)
		{
			$this->block_side();
		} else {
			$this->block_center();
		}
	}
	
	function block_center()
	{
		global $_CLASS;
		
		$this->block['position'] = ($this->block['position'] == BLOCK_TOP) ? 'center' : 'bottom';
		
		$_CLASS['template']->assign_vars_array($this->block['position'].'block', array(
			'TITLE'   => $this->block['title'],
			'CONTENT' => $this->content,
			'TEMPLATE' => $this->template
			)
		);
	}
}

?>