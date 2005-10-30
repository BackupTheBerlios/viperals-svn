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
		
		$_CLASS['core_template']->assign_array(array(
			'THEME_TABLE_OPEN'	=> $this->table_open,
			'THEME_TABLE_CLOSE'	=> $this->table_close,
			'THEME_STYLESHEET'	=> 'themes/viperal/style/style.css',
			'THEME_PATH'		=> 'themes/viperal',
		));
	}

	function theme_header()
	{
		global $_CORE_CONFIG, $_CORE_MODULE, $_CLASS;

		$_CLASS['core_template']->assign_array(array(
			'THEME_MAININDEX'	=> generate_link(),
			'THEME_SITENAME'	=> $_CORE_CONFIG['global']['site_name'],
			'THEME_MARGINRIGHT'	=> $_CLASS['core_blocks']->check_side(BLOCK_RIGHT) ? '180px' : '0px',
			'THEME_MARGINLEFT' 	=> $_CLASS['core_blocks']->check_side(BLOCK_LEFT) ? '180px' : '0px'
		));

		if ($_CLASS['core_display']->homepage)
		{
			$_CLASS['core_template']->assign('PAGE_TITLE', $_CLASS['core_user']->lang['HOME']);
		}
		else
		{
			$_CLASS['core_template']->assign('PAGE_TITLE', $_CLASS['core_user']->lang['HOME'].' &gt; '.(is_array($_CORE_MODULE['module_title']) ? implode(' &gt; ', $_CORE_MODULE['module_title']) : $_CORE_MODULE['module_title']));
		}
		
		$_CLASS['core_blocks']->generate(BLOCK_LEFT);
	
		$_CLASS['core_template']->display('header.html');
	}

	function theme_footer()
	{
		global $_CLASS;

		$_CLASS['core_blocks']->generate(BLOCK_RIGHT);

		$_CLASS['core_template']->assign('THEME_FOOTER', $_CLASS['core_display']->footmsg());
		
		$_CLASS['core_template']->display('footer.html');
	}
}
?>