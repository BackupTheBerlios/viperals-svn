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
Complete Message and blocks
RSS system
User blocks
*/

class blocks
{
	var $blocks = array();
	var $content = '';
	var $template = '';

	function check_side($side) {
		global $Module;
		
		if (CPG_NUKE == 'Admin')
		{
			if ($side != BLOCK_LEFT)
			{
				return false;
			}
			return true;
		}
		
		if (($Module['sides'] == BLOCK_ALL) || ($Module['sides'] == BLOCK_LEFT && $side == BLOCK_LEFT) || ($Module['sides'] == BLOCK_RIGHT && $side == BLOCK_RIGHT))
		{
			return true;
		}
				
		return false;

	}
	
	function display($position) {
		static $expire_updated = false;
		
		if (CPG_NUKE == 'Admin' && $position != BLOCK_LEFT)
		{
			return false;
		}
		
		global $_CLASS, $userinfo;
		
		if (!count($this->blocks))
		{
			if (!($this->blocks = $_CLASS['cache']->get('blocks')))
			{
				$result = $_CLASS['db']->sql_query('SELECT * FROM '.BLOCKS_TABLE." WHERE active='1' ORDER BY weight ASC");

				while($row = $_CLASS['db']->sql_fetchrow($result)) {
					$this->blocks[$row['position']][] = $row;
				}
				
				$_CLASS['cache']->put('blocks', $this->blocks);
				$_CLASS['db']->sql_freeresult($result);
			}
		}
		
		if (!empty($this->blocks[$position]))
		
		foreach($this->blocks[$position] AS $this->blocksrow) {
		
			if ($this->blocksrow['expires'] && (time() >= $this->blocksrow['expires']))
			{
				if ( !$expire_updated )
				{
					$_CLASS['db']->sql_query('UPDATE '.BLOCKS_TABLE." SET active='0' WHERE expires >=".time());
					$expire_updated = true;
				}
				
				continue;
			 }
         
			// Ow way ow way do i still have this :-(
			// Must do new system !
			if (($this->blocksrow['view'] == 0) ||
				($this->blocksrow['view'] == 1 AND is_user() || is_admin()) ||
				($this->blocksrow['view'] == 2 AND is_admin()) ||
				($this->blocksrow['view'] == 3 AND !is_user() || is_admin()) ||
				($this->blocksrow['view'] > 3 AND $userinfo['_mem_of_groups'][($row['view']-3)] || is_admin($admin))) {
				
				$this->display_blocks();
			}
		}
		
		unset($this->blocksrow);
		return false;
	}

	function display_blocks() {

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
	
	function block_file() {
	
		// Almost Completed 
		// remove old defines add new language set.
		global $_CLASS;
		if (($this->blocksrow['position'] == BLOCK_LEFT || $this->blocksrow['position'] == BLOCK_RIGHT) && !$this->check_side($this->blocksrow['position']))
		{
			return;
		}
		
		/*$startqueries = $_CLASS['db']->sql_num_queries();
		$starttime = explode(' ', microtime());
		$starttime = $starttime[0] + $starttime[1];*/

		if (!$this->blocksrow['file']) { return; };
		
		if (file_exists('blocks/'.$this->blocksrow['file'])) {
		
			require('blocks/'.$this->blocksrow['file']);
			
		} else {
		
			if (is_admin()) {
				$this->content = _BLOCKPROBLEM;
			} else {
				return;
			}
			
		}
		
		if (!$this->content && !$this->template) {
			if (is_admin()) {
				$this->content = _BLOCKPROBLEM2;
			} else {
				return;
			}
		}
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1];
		
		/*$this->content .= '<div style="text-align: center;">';
		$this->content .= '<br />block queries: '.($_CLASS['db']->sql_num_queries() - $startqueries);
		$this->content .= '<br />Generation time: '.round($endtime - $starttime, 4).'s';
		$this->content .= '</div>';*/

		if ($this->blocksrow['position'] == BLOCK_LEFT || $this->blocksrow['position'] == BLOCK_RIGHT)
		{

			themesidebox($this->blocksrow['title'], $this->content, $this->blocksrow['bid'], $this->template);

		} else {
		
			$this->themecenterbox();
		
		}
		
		$this->content = $this->template = false;
	}
			
	function block_message() {
		global $textcolor2, $_CLASS, $user;
		
		// Almost Completed
		// remove old defines add new language set.
		
		if (($this->blocksrow['type'] == BLOCKTYPE_MESSAGE_TOP) && (!$_CLASS['display']->homepage))
		{
			return;
		}
		
		if (is_admin())
		{
			$remain = ($this->blocksrow['expires']) ? _EXPIREIN.' '.$user->format_date($this->blocksrow['expires']).' '._HOURS : _UNLIMITED;
			$edit = '<a href="'.$remain.'">'._EDIT.'</a>';
		} else {
			$edit = $remain = false;
		}
		
		if (THEMEPLATE) {
			global $_CLASS;
			$_CLASS['template']->assign_vars_array('messageblock', array(
				'TITLE'		=> $this->blocksrow['title'],
				'CONTENT'	=> $this->blocksrow['content'],
				'REMAIN'	=> $remain,
				'EDIT'		=> $edit,
				'HIDE'		=> hideblock($this->blocksrow['bid']) ? 'style="display: none"' : '',
				'EDITLINK'	=> adminlink('editmsg&amp;mid='.$this->blocksrow['bid']),
				'BID'		=> $this->blocksrow['bid'],
				)
			);
			
		} else {
                
			OpenTable();
				echo '<div align="center"><font class="option" color="'.$textcolor2.'"><b>'.$this->blocksrow['title'].'</b></font></div><br /><font class="content">'.$this->blocksrow['content'].'</font>';
				if (is_admin()) {
					echo '<br /><br /><div align="center"><font class="content">[ '.$output.' - '.$remain.' - <a href="'.adminlink('editmsg&amp;mid='.$mid).'">'._EDIT.'</a> ]</font></div>';
				}
			CloseTable();
		}
	}
	
	/*function headlines($bid, $position=0, $row='') {
		global $prefix, $db;
		$bid = intval($bid);
		if (!is_array($row)) {
			$result = $db->sql_query('SELECT title, content, url, refresh, time FROM '.$prefix."_blocks WHERE bid='$bid'");
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}
		$title = $row['title'];
		$content = $row['content'];
		$url = $row['url'];
		$refresh = $row['refresh'];
		$otime = $row['time'];
		$past = time()-$refresh;
		if ($otime < $past) {
			$content = '';
			if ($items = get_rss($url)) {
				$content = '';
				for ($i=0;$i<count($items);$i++) {
					$link = $items[$i]['link'];
					$title2 = $items[$i]['title'];
					$content .= "<img src=\"images/arrow.gif\" border=\"0\" alt=\"\" title=\"\" width=\"9\" height=\"9\"><a href=\"$link\" target=\"new\">$title2</a><br />\n";
				}
			}
			$btime = time();
			$db->sql_query('UPDATE '.$prefix.'_blocks SET content=\''.addslashes($content)."', time='$btime' WHERE bid='$bid'");
		}
		$siteurl = ereg_replace('http://','',$url);
		$siteurl = explode('/',$siteurl);
		if ($content != '') {
			$content .= "<br /><a href=\"http://$siteurl[0]\" target=\"blank\"><b>"._HREADMORE.'</b></a>';
		} else {
			$content = _RSSPROBLEM;
		}
		$content = '<font class="content">'.$content.'</font>';
		if ($position == 'c' || $position == 'd') {
			themecenterbox($title, $content, $position);
		} else {
			themesidebox($title, $content, $bid);
		}
	}
	
	
	// !!! this is old
	function userblock($bid) {
		global $user;
		if(is_user() && $user->data['ublockon']) {
			$title = $user->data['username'];
			themesidebox($this->blocksrow['title'], $user->data['block'], $this->blocksrow['bid'], $this->template);
			//themesidebox($title, $user->data['block'], $bid);
		}
	}*/
	
	function themecenterbox() {
	
		if (THEMEPLATE) {
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
			echo '<div align="center" class="option"><b>'.$this->blocksrow['title'].'</b></div><br />'.$this->content;
			CloseTable();
			echo '<br />';
		}
	}
	
}

?>