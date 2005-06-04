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

global $bgcolor1, $bgcolor2;
// should i keep this or not :-S
$bgcolor1 = '#FFFFFF';
$bgcolor2 = '#C7D0D7';

// Just a test or an example
// Remove once documented
if (!defined('VIPERAL'))
{
    // must be done, extends class will case a error if theme_blocks is not defined
    Header('Location: /');
    die();
}

/*class theme_blocks extends core_blocks
{
	function block_side()
	{
		global $_CLASS;
		
		$this->block['position'] = ($this->block['position'] == BLOCK_RIGHT) ? 'right' : 'left';
		
		$_CLASS['core_template']->assign_vars_array($this->block['position'].'block', array(
			'TITLE'		=> $this->block['title'],
			'CONTENT'	=> $this->content,
			'ID'		=> $this->block['id'],
			'COLLAPSE'	=> hideblock($this->block['id']) ? 'style="display: none"' : '',
			'TEMPLATE'	=> $this->template,
			)
		);
	}
}

loadclass(false, 'core_blocks', 'theme_blocks');*/

function OpenTable()
{
    echo '<div class="OpenTable"><div class="outer"><div class="inner">';
}

function OpenTable2()
{
    echo '<div class="outer"><div class="inner">';
}

function CloseTable()
{
    echo '</div></div></div>';
}

function CloseTable2()
{
    echo '</div></div>';
}

class theme_display extends core_display
{
	function theme_display()
	{
		// assign this to template
		/*$this->table_open = '<div class="OpenTable">';
		$this->table_close = '</div>';*/
	}

	function theme_header()
	{
		global $_CORE_CONFIG, $_CORE_MODULE, $_CLASS;
	
		$_CLASS['core_template']->assign(array(
			'THEME_MAININDEX'	=> generate_link(),
			'THEME_SITENAME'	=> $_CORE_CONFIG['global']['site_name'],
			'MARGINRIGHT'		=> ($_CLASS['core_blocks']->check_side(BLOCK_RIGHT)) ? '180px' : '0px',
			'MARGINLEFT' 		=> ($_CLASS['core_blocks']->check_side(BLOCK_LEFT)) ? '180px' : '0px'
			)
		);
		
		if ($_CLASS['core_display']->homepage)
		{
			$_CLASS['core_template']->assign('PAGE_TITLE', $_CLASS['core_user']->lang['HOME']);
		} else {
			$_CLASS['core_template']->assign('PAGE_TITLE', $_CLASS['core_user']->lang['HOME'].' &gt; '.$_CORE_MODULE['title']);
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