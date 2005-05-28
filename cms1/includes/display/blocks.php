<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

/* to do
RSS system
options interface
add bottom messages
*/

class core_blocks
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
		
		global $_CORE_MODULE, $_CLASS;

		$trueside = $side;
		
		if ($_CLASS['core_user']->lang['DIRECTION'] == 'rtl')
		{
			$side = (($side == BLOCK_LEFT) ? BLOCK_RIGHT : BLOCK_LEFT);
		}
			
		if ($_CORE_MODULE['sides'] == BLOCK_ALL || ($side == BLOCK_LEFT && $_CORE_MODULE['sides'] == BLOCK_LEFT) || ($side == BLOCK_RIGHT && $_CORE_MODULE['sides'] == BLOCK_RIGHT))
		{
			$this->load_blocks();
			
			if (!empty($this->blocks_array[$side]))
			{
				return $side_check[$trueside] = $side;
			}
		}
		
		return $side_check[$trueside] = false;
	}
	
	function load_blocks($force = false)
	{
		if ($this->blocks_loaded && !$force)
		{
			return;
		}
		
		global $_CLASS;

		if (($this->blocks_array = $_CLASS['core_cache']->get('blocks')) === false)
		{
			$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.BLOCKS_TABLE.' WHERE active > 0 ORDER BY weight ASC');
			
			$this->blocks_array = array();
			
			while($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$this->blocks_array[$row['position']][] = $row;
			}
			
			$_CLASS['core_db']->sql_freeresult($result);
			$_CLASS['core_cache']->put('blocks', $this->blocks_array);
			$_CLASS['core_cache']->save();
		}
		
		$_CLASS['core_cache']->remove('blocks');
		$this->blocks_loaded = true;
	}
	
	function add_block($data, $position = false)
	{
//remove $this->blocks_loaded
		$this->blocks_loaded = true;
				
		$option_array = array('title','position', 'file' , 'time' ,'expires', 'id',	'auth',	'type', 'options');
		
// use an array_merge
		foreach($option_array as $option)
		{
			$data_perpared[$option] = (empty($data[$option])) ? '' : $data[$option];
		}
		
		$this->blocks_array[$data['position']][] = $data_perpared;
	}
	
	function display($position)
	{
		if ($position == BLOCK_LEFT || $position == BLOCK_RIGHT)
		{
			$position = $this->check_side($position);
			
			if ($position == false)
			{
				return false;
			}
		}
		
		$this->load_blocks();
			
		if (empty($this->blocks_array[$position]))
		{
			return false;
		}
		
		static $expire_updated = false;
		global $_CLASS;
		
		foreach($this->blocks_array[$position] as $this->block)
		{
			$this->block['position'] = $position;
			
			//auth check and language check here.
			/*if (!$_CLASS['core_user']->admin_auth('blocks') && !$_CLASS['core_user']->auth($this->block['auth']))
			{
				continue;
			}*/

			if ($this->block['expires'] && !$expire_updated && ($_CLASS['core_user']->time > $this->block['expires']))
			{
				$_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET active=0 WHERE expires > 0 AND expires <='.$_CLASS['core_user']->time);
										
				$_CLASS['core_cache']->destroy('blocks');
				$expire_updated = true;

				continue;
			}
			
			if ($this->block['time'] && ($this->block['time'] > $_CLASS['core_user']->time))
			{
				continue;
			}
			
			if ($this->block['options'])
			{
				@eval('$this->block += '.$this->block['options'].';');
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
				
			case BLOCKTYPE_MESSAGE:
			case BLOCKTYPE_MESSAGE_GLOBAL:
			
				$this->block_message();
				break;
					
			case BLOCKTYPE_HTML:
				$this->block_html();
				break;
				
			case BLOCKTYPE_FEED;
				$this->block_feed();
				break;
		}
		
		return;
	}
	
	function block_file()
	{
		global $_CLASS, $site_file_root;
		
		if ($this->block['file'] && file_exists($site_file_root.'blocks/'.$this->block['file']))
		{
			
			/*
			$startqueries = $_CLASS['core_db']->sql_num_queries();
			$startqueriestime = $_CLASS['core_db']->sql_time;
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
				//if ($_CLASS['core_user']->admin_auth('blocks'))
				//{
				//	$this->content = ($this->info) ? $this->info : $_CLASS['core_user']->lang['BLOCK_ERROR2'];
				//} else {
					return;
				//}
			}

		} else {
		
			//if ($_CLASS['core_user']->admin_auth('blocks'))
		//	{
			//	$this->content = $_CLASS['core_user']->lang['BLOCK_ERROR1'];
		//	} else {
				return;
		//	}
			
		}

		/*
		$this->content .= '<div style="text-align: center;">';
		if ($_CLASS['core_db']->sql_num_queries() - $startqueries)
		{
			$this->content .= '<br />block queries: '.($_CLASS['core_db']->sql_num_queries() - $startqueries)
							.' in '.round($_CLASS['core_db']->sql_time - $startqueriestime, 4).' s';
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
		
		if (($this->block['type'] == BLOCKTYPE_MESSAGE) && (!$_CLASS['core_display']->homepage))
		{
			return;
		}
		
		/*if ($_CLASS['core_user']->admin_auth('messages'))
		{
			$expires = ($this->block['expires']) ? $_CLASS['core_user']->lang['EXPIRES'].' '.$_CLASS['core_user']->format_date($this->block['expires']) : false;
			$edit_link = generate_link('messages&amp;mode=edit&amp;id='.$this->block['id'], array('admin' => true));
		
		} else {*/
		
			$edit_link = $expires = false;
		//}
		
		$_CLASS['core_template']->assign_vars_array('messageblock', array(
				//'TITLE'	=> $_CLASS['core_user']->get_lang($this->block['title']),
				'TITLE'		=> $this->block['title'],
				'CONTENT'	=> $this->block['content'],
				'EXPIRES'	=> $expires,
				'EDIT_LINK'	=> $edit_link,
				'L_EDIT'	=> $_CLASS['core_user']->lang['EDIT'],
				'HIDE'		=> hideblock($this->block['id']) ? 'style="display: none"' : '',
				'ID'		=> $this->block['id'],
			)
		);
	}
	
	function block_side()
	{
		global $_CLASS;
		
		$this->block['position'] = ($this->block['position'] == BLOCK_RIGHT) ? 'right' : 'left';
		
		$_CLASS['core_template']->assign_vars_array($this->block['position'].'block', array(
			//'TITLE'	=> $_CLASS['core_user']->get_lang($this->block['title']),
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
	
	function block_feed()
	{
		global $site_file_root, $_CLASS;
		
		loadclass($site_file_root.'includes/core_rss.php', 'core_rss');
		$status = $_CLASS['core_rss']->get_rss($this->block['opt_rss_url'], $this->block['content']);
		
		if (!$status)
		{
//admin only message here
			return;
		}
		
		if (!$_CLASS['core_rss']->rss_expire)
		{
			$_CLASS['core_db']->sql_query('UPDATE '.BLOCKS_TABLE.' SET content="'.$_CLASS['core_db']->sql_escape($_CLASS['core_rss']->get_rss_data_raw($this->block['opt_rss_expire'])).'" WHERE id='.$this->block['id']);
			$_CLASS['core_cache']->destroy('blocks');
		}

//block_rss/file
		
		$this->content = '<center>';
		
		while ($data = $_CLASS['core_rss']->get_rss_data())
		{
			$this->content .= '<strong><big>&middot;</big></strong> <a href="'.$data['link'].'" target="new">'.$data['title'].'</a><br />';
		}
		
		if (!empty($_CLASS['core_rss']->rss_info['link']))
		{
			$this->content .= '<br /><a href="'.$_CLASS['core_rss']->rss_info['link'].'" target="_blank"><b>Read More</b></a>';
		}
		
		$this->content .= '</center>';
		
		$this->block_side();
	}
	
	function block_center()
	{
		global $_CLASS;
		
		$this->block['position'] = ($this->block['position'] == BLOCK_TOP) ? 'center' : 'bottom';
		
		$_CLASS['core_template']->assign_vars_array($this->block['position'].'block', array(
			//'TITLE'	=> $_CLASS['core_user']->get_lang($this->block['title']),
			'TITLE'   => $this->block['title'],
			'CONTENT' => $this->content,
			'TEMPLATE' => $this->template
			)
		);
	}
}

?>