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
/* to do
move Auth, lang, and expire/begin checks to load_blocks
*/

class core_blocks
{
	var $blocks_array = array();
	var $blocks_loaded;
	var $info;
	var $content;
	var $template;

	/*
		Check to see there's any blocks for the specified side
		should be used to stop themes from displaying blank sides
	*/
	function check_side($side)
	{
// expand this for center blocks
		static $side_check = array();
		
		if (isset($side_check[$side]))
		{
			return $side_check[$side];
		}

		global $_CORE_MODULE, $_CLASS;

		$trueside = $side;

		if ($_CLASS['core_user']->lang['DIRECTION'] == 'rtl')
		{
			$side = ($side == BLOCK_LEFT) ? BLOCK_RIGHT : BLOCK_LEFT;
		}
			
		if ($_CORE_MODULE['module_sides'] == BLOCK_ALL || ($side == BLOCK_LEFT && $_CORE_MODULE['module_sides'] == BLOCK_LEFT) || ($side == BLOCK_RIGHT && $_CORE_MODULE['module_sides'] == BLOCK_RIGHT))
		{
			$this->load_blocks();

			if (!empty($this->blocks_array[$side]))
			{
				return $side_check[$trueside] = $side;
			}
		}
		
		return $side_check[$trueside] = false;
	}

	/*
		Load Blocks from cache or databases, also run needed checks
	*/
// Add auth check here
	function load_blocks($force = false)
	{
		if ($this->blocks_loaded && !$force)
		{
			return;
		}

		global $_CLASS;

		if (is_null($this->blocks_array = $_CLASS['core_cache']->get('blocks')))
		{
			$result = $_CLASS['core_db']->query('SELECT * FROM '.BLOCKS_TABLE.' WHERE block_status = '.STATUS_ACTIVE.' ORDER BY block_order ASC');

			$this->blocks_array = array();

			while($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$row['block_auth'] = ($row['block_auth']) ? unserialize($row['block_auth']) : '';
				$this->blocks_array[$row['block_position']][] = $row;
			}

			$_CLASS['core_db']->free_result($result);
			$_CLASS['core_cache']->put('blocks', $this->blocks_array);

			$_CLASS['core_cache']->save();
		}

		$_CLASS['core_cache']->remove('blocks');
		$this->blocks_loaded = true;
	}

	/*
	*/
	function add_block($data, $position = false)
	{
		$option_array = array('block_status' => STATUS_ACTIVE, 'block_title' => '','block_position' => BLOCK_LEFT, 'block_content' => '', 'block_file' => '', 'block_starts' => 0,'block_expires' => 0, 'block_id' => 0,	'block_auth' => '',	'block_type' => '');

		foreach ($option_array as $option => $value)
		{
			$data_perpared[$option] = isset($data[$option]) ? $data[$option] : $value ;
		}

		$this->blocks_array[$data_perpared['block_position']][] = $data_perpared;
	}

	/*
	*/
	function display($position)
	{
		$this->display_position = $position;

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
			if ($this->block['block_auth'] && !$_CLASS['core_auth']->auth($this->block['block_auth']) && !$_CLASS['core_auth']->admin_power('blocks'))
			{
				continue;
			}

			if ($this->block['block_expires'] && !$expire_updated && ($_CLASS['core_user']->time > $this->block['block_expires']))
			{
				$_CLASS['core_db']->query('UPDATE '.BLOCKS_TABLE.' SET block_status = ' . STATUS_DISABLED . ' WHERE block_expires > 0 AND block_expires <= ' . $_CLASS['core_user']->time);
										
				$_CLASS['core_cache']->destroy('blocks');
				$expire_updated = true;

				continue;
			}

			if ($this->block['block_starts'] && ($this->block['block_starts'] > $_CLASS['core_user']->time))
			{
				continue;
			}

			$this->display_blocks();
			$this->content = '';
		}

		unset($this->blocks_array[$position]);
	}

	/*
	*/
	function display_blocks()
	{
		Switch ($this->block['block_type'])
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

	/*
	*/
	function block_file()
	{
		global $_CLASS, $site_file_root;

		if ($this->block['block_file'] && file_exists($site_file_root.'blocks/'.$this->block['block_file']))
		{
			$this->info = false;

			//$_CLASS['core_error_handler']->debug_start($this->block['block_title']);

			include($site_file_root.'blocks/'.$this->block['block_file']);

			//$_CLASS['core_error_handler']->debug_stop($this->block['block_title']);

			if (!$this->content && !$this->template)
			{
				if ($_CLASS['core_auth']->admin_power('blocks'))
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
			if ($_CLASS['core_auth']->admin_power('blocks'))
			{
				$this->content = '<center><strong>'.$_CLASS['core_user']->lang['BLOCK_ERROR1'].'</strong></center>';
			}
			else
			{
				return;
			}		
		}

		//$this->content .= '<div style="text-align: center;"><br />'.$_CLASS['core_error_handler']->debug_get($this->block['block_title'], 'formated').'</div>';
		//$_CLASS['core_error_handler']->debug_remove($this->block['block_title']);

		if ($this->display_position == BLOCK_LEFT || $this->display_position == BLOCK_RIGHT)
		{
			$this->block_side();
		}
		else
		{
			$this->block_center();
		}
	}

	function block_message()
	{
		global $_CLASS;

		if (($this->block['block_type'] == BLOCKTYPE_MESSAGE) && (!$_CLASS['core_display']->homepage))
		{
			return;
		}

		if ($_CLASS['core_auth']->admin_power('messages'))
		{
			$expires = ($this->block['block_expires']) ? $_CLASS['core_user']->lang['EXPIRES'].' '.$_CLASS['core_user']->format_date($this->block['block_expires']) : false;
			$edit_link = generate_link('messages&amp;mode=edit&amp;id='.$this->block['block_id'], array('admin' => true));
		}
		else
		{
		
			$edit_link = $expires = false;
		}

		$postion = ($this->display_position == BLOCK_MESSAGE_TOP) ? 'top' : 'bottom';

		$_CLASS['core_template']->assign_vars_array('message_block_'.$postion, array(
				//'TITLE'		=> $_CLASS['core_user']->get_lang($this->block['block_title']),
				'title'		=> $this->block['block_title'],
				'collapsed'	=> check_collapsed_status('block_'.$this->block['block_id']),
				'content'	=> $this->block['block_content'],
				'expires'	=> $expires,
				'edit_link'	=> $edit_link,
				'id'		=> $this->block['block_id'],
		));
	}

	function block_side()
	{
		global $_CLASS;
		
		$position = ($this->display_position == BLOCK_RIGHT) ? 'right' : 'left';
		
		$_CLASS['core_template']->assign_vars_array('block_'.$position, array(
			//'TITLE'		=> $_CLASS['core_user']->get_lang($this->block['block_title']),
			'title'		=> $this->block['block_title'],
			'content'	=> $this->content,
			'collapsed'	=> check_collapsed_status('block_'.$this->block['block_id']),
			'id'		=> $this->block['block_id'],
		));
	}

	function block_html()
	{
		$this->content = $this->block['block_content'];

		if ($this->display_position == BLOCK_LEFT || $this->display_position == BLOCK_RIGHT)
		{
			$this->block_side();
		}
		else
		{
			$this->block_center();
		}
	}

	function block_feed()
	{
		global $site_file_root, $_CLASS;
// think about disabling the block automatically if there's url problems
// update core_rss file
		if ($this->block['block_content'] && (!$this->block['block_rss_expires'] || $this->block['block_rss_expires'] > time()))
		{
			$this->content = $this->block['block_content'];

			if ($this->display_position == BLOCK_LEFT || $this->display_position == BLOCK_RIGHT)
			{
				$this->block_side();
			}
			else
			{
				$this->block_center();
			}

			return;
		}

		if ($this->block['block_file'] && file_exists($site_file_root.'blocks/rss/'.$this->block['block_file']))
		{
			include($site_file_root.'blocks/rss/'.$this->block['block_file']);
			
			if (!$this->content)
			{
				return;
			}
		}
		else
		{
			load_class($site_file_root.'includes/core_rss.php', 'core_rss');
			$_CLASS['core_rss']->setup(false, array('title', 'link'));
				
			if (!$_CLASS['core_rss']->get_rss($this->block['block_rss_url']))
			{
	//admin only message here
				return;
			}

			$this->content = '<center><br />';

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

		settype($this->block['block_rss_rate'], 'integer');

		if ($this->block['block_rss_rate'] !== -1)
		{
			$this->block['block_rss_expires'] = ($this->block['block_rss_rate']) ? gmtime() + (int) $this->block['block_rss_rate'] : 0;
			
			$sql = 'UPDATE '.BLOCKS_TABLE."
				SET block_content = '".$_CLASS['core_db']->escape($this->content)."', block_rss_expires = ".$this->block['block_rss_expires']." 
					WHERE block_id = ".$this->block['block_id'];
				
			$_CLASS['core_db']->query($sql);

			$_CLASS['core_cache']->destroy('blocks');
		}

		if ($this->display_position == BLOCK_LEFT || $this->display_position == BLOCK_RIGHT)
		{
			$this->block_side();
		}
		else
		{
			$this->block_center();
		}
	}

	function block_center()
	{
		global $_CLASS;
		
		$position = ($this->display_position == BLOCK_TOP) ? 'center' : 'bottom';
		
		$_CLASS['core_template']->assign_vars_array('block_'.$position , array(
			'TITLE'		=> $_CLASS['core_user']->get_lang($this->block['block_title']),
			'CONTENT'	=> $this->content,
			'TEMPLATE'	=> $this->template
		));
	}
}

?>