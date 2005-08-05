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
if (!defined('VIPERAL'))
{
    die;
}

//class theme_display extends core_display
class viperal_theme
{
	function viperal_theme()
	{
		global $_CLASS;

		$this->table_open	= '<div class="OpenTable"><div class="outer"><div class="inner">';
		$this->table_close	= '</div></div></div>';
		
		$_CLASS['core_template']->assign(array(
			'A_TABLE_OPEN'	=> $this->table_open,
			'A_TABLE_CLOSE'	=> $this->table_close,
			'A_STYLESHEET'	=> '/themes/viperal/style/style.css',
		));
	}

	function theme_header()
	{
		global $_CORE_CONFIG, $_CORE_MODULE, $_CLASS;

		$_CLASS['core_template']->assign(array(
			'THEME_MAININDEX'	=> generate_link(),
			'THEME_SITENAME'	=> $_CORE_CONFIG['global']['site_name'],
			'THEME_MARGINRIGHT'	=> ($_CLASS['core_blocks']->check_side(BLOCK_RIGHT)) ? '180px' : '0px',
			'THEME_MARGINLEFT' 	=> ($_CLASS['core_blocks']->check_side(BLOCK_LEFT)) ? '180px' : '0px'
		));
		
		if ($_CLASS['core_display']->homepage)
		{
			$_CLASS['core_template']->assign('PAGE_TITLE', $_CLASS['core_user']->lang['HOME']);
		}
		else
		{
			$_CLASS['core_template']->assign('PAGE_TITLE', $_CLASS['core_user']->lang['HOME'].' &gt; '.(is_array($_CORE_MODULE['title']) ? implode(' &gt; ', $_CORE_MODULE['title']) : $_CORE_MODULE['title']));
		}
		
		$_CLASS['core_blocks']->display(BLOCK_LEFT);
	
		$_CLASS['core_template']->display('header.html');
	}

	function theme_footer()
	{
		global $_CLASS;
		
		$_CLASS['core_blocks']->display(BLOCK_RIGHT);
		
		$_CLASS['core_template']->assign('THEME_FOOTER', $_CLASS['core_display']->footmsg());
		
		$_CLASS['core_template']->display('footer.html');
	}
}
?>