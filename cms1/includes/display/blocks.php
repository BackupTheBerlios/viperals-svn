<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal©	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
//print_r(apache_request_headers());

/* to do
options interface
add bottom messages
look at rss again :-|
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
// expand this for center blocks
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
				
		$option_array = array('title','position', 'content', 'file' , 'modules', 'time' ,'expires', 'id',	'auth',	'type', 'options');
		
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
		
		$this->content = '';
		
		foreach($this->blocks_array[$position] as $this->block)
		{
			if ($this->block['auth'] && !$_CLASS['core_auth']->admin_auth('blocks') && !$_CLASS['core_auth']->auth($this->block['auth']))
			{
				continue;
			}
			
//language check here.
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
			
			if ($this->block['modules'])
			{
				$this->block['modules'] = unserialize($this->block['modules']);
// Homepage needs it's own value
				if (!in_array($_CORE_MODULE['title'], $this->block['modules']))
				{
					continue;
				}
			}
			
			if ($this->block['options'])
			{
				$this->block['options'] = unserialize($this->block['options']);
			}
			
			$this->display_blocks();
			$this->content = '';
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
			$this->info = false;

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
				if ($_CLASS['core_auth']->admin_auth('blocks'))
				{
					$this->content = '<center><strong>'.(($this->info) ? $this->info : $_CLASS['core_user']->lang['BLOCK_ERROR2']).'</strong></center>';
				}
				else
				{
					return;
				}
			}
		}
		else
		{
			if ($_CLASS['core_auth']->admin_auth('blocks'))
			{
				$this->content = '<center><strong>'.$_CLASS['core_user']->lang['BLOCK_ERROR1'].'</strong></center>';
			}
			else
			{
				return;
			}		
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
	}
			
	function block_message()
	{
		global $_CLASS;
		
		if (($this->block['type'] == BLOCKTYPE_MESSAGE) && (!$_CLASS['core_display']->homepage))
		{
			return;
		}
		
		if ($_CLASS['core_auth']->admin_auth('messages'))
		{
			$expires = ($this->block['expires']) ? $_CLASS['core_user']->lang['EXPIRES'].' '.$_CLASS['core_user']->format_date($this->block['expires']) : false;
			$edit_link = generate_link('messages&amp;mode=edit&amp;id='.$this->block['id'], array('admin' => true));
		
		}
		else
		{
		
			$edit_link = $expires = false;
		}
		
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
// think about disabling the block automatically if there's url problems
// update core_rss file
		if ($this->block['content'] && $this->block['options']['rss_expires'] > time())
		{
			$this->content = $this->block['content'];
			
			if ($this->block['position'] == BLOCK_LEFT || $this->block['position'] == BLOCK_RIGHT)
			{
				$this->block_side();
			} else {
				$this->block_center();
			}
			
			return;
		}
		
		if ($this->block['file'] && file_exists($site_file_root.'blocks/rss/'.$this->block['file']))
		{
			include($site_file_root.'blocks/rss/'.$this->block['file']);
			
			if (!$this->content)
			{
				return;
			}
		}
		else
		{
			loadclass($site_file_root.'includes/core_rss.php', 'core_rss');
			$_CLASS['core_rss']->item_tags = array('title', 'link');
				
			if (!$_CLASS['core_rss']->get_rss($this->block['options']['rss_url']))
			{
	//admin only message here
				return;
			}
			
			$this->content = '<center>';
			
			while ($data = $_CLASS['core_rss']->get_rss_data())
			{
				$this->content .= '<a href="'.$data['link'].'" target="new">'.$data['title'].'</a><hr width="30%"/>';
			}
			
			if (!empty($_CLASS['core_rss']->rss_info['link']))
			{
				$this->content .= '<br /><a href="'.$_CLASS['core_rss']->rss_info['link'].'" target="_blank"><b>Read More</b></a>';
			}
			
			$this->content .= '</center>';
		}
		
		
		if ($this->block['options']['rss_rate'])
		{
			$this->block['options']['rss_expires'] = time() + $this->block['options']['rss_rate'];
			
			$sql = 'UPDATE '.BLOCKS_TABLE."
				SET content='".$_CLASS['core_db']->sql_escape($this->content)."'
				, options='".$_CLASS['core_db']->sql_escape(serialize($this->block['options']))."' 
					WHERE id=".$this->block['id'];
				
			$_CLASS['core_db']->sql_query($sql);
			$_CLASS['core_cache']->destroy('blocks');
		}
		
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