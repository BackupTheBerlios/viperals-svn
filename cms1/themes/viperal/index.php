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

if (!defined('VIPERAL'))
{
    // must be done, extends class will case a error if theme_blocks is not defined
    Header('Location: /');
    die();
}

global $bgcolor1, $bgcolor2;
// should i keep this or not :-S
$bgcolor1 = '#FFFFFF';
$bgcolor2 = '#C7D0D7';

// Just a test or an example
// Remove once documented
class theme_blocks extends blocks
{
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
}

loadclass(false, 'blocks', 'theme_blocks');

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


function Themeheader()
{
	global $MAIN_CFG, $Module, $_CLASS;

	$_CLASS['template']->assign(array(
		'THEME_MAININDEX'	=> generate_link(),
		'THEME_SITENAME'	=> $MAIN_CFG['global']['sitename'],
		'MARGINRIGHT'		=> ($_CLASS['blocks']->check_side(BLOCK_RIGHT)) ? '180px' : '0px',
		'MARGINLEFT' 		=> ($_CLASS['blocks']->check_side(BLOCK_LEFT)) ? '180px' : '0px'
		)
	);
	
	if ($_CLASS['display']->homepage)
	{
		$_CLASS['template']->assign('PAGE_TITLE', $_CLASS['user']->lang['HOME']);
	} else {
		$_CLASS['template']->assign('PAGE_TITLE', $_CLASS['user']->lang['HOME'].' &gt; '.$Module['title']);
	}
	
	$_CLASS['blocks']->display(BLOCK_LEFT);

	$_CLASS['template']->display('header.html');
}

function themefooter()
{
	global $_CLASS;
	
	$_CLASS['blocks']->display(BLOCK_RIGHT);
	
	$_CLASS['template']->assign('THEME_FOOTER', $_CLASS['display']->footmsg());
	
	$_CLASS['template']->display('footer.html');
}

?>