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

class blocks
{
		
	function block_message()
	{
		global $_CLASS;
		
		if (($this->block['active'] == '1') && (!$_CLASS['display']->homepage))
		{
			return;
		}
			
		OpenTable();
		
		echo '<div style="text-align: center; color: '.$textcolor2.'"><b>'.$this->block['title'].'</b></div>'
			.'<br />'.$this->block['content'];
		
		if (is_admin() && $_CLASS['user']->admin_auth('message'))
		{
			$message = ($this->block['expires']) ? $_CLASS['user']->lang['EXPIRES'].' '.$_CLASS['user']->format_date($this->block['expires']).' | ' : false;
			$message .= '<a href="'.adminlink('message&amp;mode=edit&amp;id='.$this->block['id']).'">'.$_CLASS['user']->lang['EDIT'].'</a>';

			echo '<br /><br /><div align="right">[ '.$message.' ]</font></div>';
		}
	
		CloseTable();
		
		echo '<br />';
	
	}
	
	function block_side()
	{
		global $_CLASS;
		
		if ($this->template)
		{
			ob_start();
			
			$_CLASS['template']->display($this->template);
			//It seem to grad data from out of php also as used in the templetes, atleast with php4.3x > 
			//test with other php version use ob_get_clean() if its php4.3x > only
			//$this->content .= ob_get_clean();
			
			$this->content .= ob_get_contents();
			ob_end_clean();
		}
		
		themesidebox($this->block['title'], $this->content, $this->block['id']);
	}
	
	function block_center()
	{
		OpenTable();
		echo '<div align="center"><b>'.$this->block['title'].'</b></div><br />'.$this->content;
		CloseTable();
		echo '<br />';
	}
}

?>