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
// make this into a subclass of display, it has to be part of that class
// maybe side_block should be part of blocks and removed and added to blocks class.
// Remove all viables.
// Opentable / CloseTable is fine just add it to the display, and assing it there to a temeplate.

global $bgcolor1, $bgcolor2;
// should i keep this or not :-S
$bgcolor1 = '#FFFFFF';
$bgcolor2 = '#C7D0D7';

function OpenTable()
{
    echo '<div class="OpenTable"><div class="outer"><div class="inner">';
}

function OpenTable2() {
    echo '<div class="outer"><div class="inner">';
}

function CloseTable() {
    echo '</div></div></div>';
}

function CloseTable2() {
    echo '</div></div>';
}


function Themeheader()
{
	global $sitename, $mainindex, $MAIN_CFG, $Module, $SID, $_CLASS;

	$_CLASS['template']->assign(array(
		'THEME_MAININDEX'	=> $mainindex.'?'.$SID,
		'THEME_SITENAME'	=> $MAIN_CFG['global']['sitename'],
		'MARGINRIGHT'		=> ($_CLASS['blocks']->check_side(BLOCK_RIGHT)) ? '180px' : '0px',
		'MARGINLEFT' 		=> ($_CLASS['blocks']->check_side(BLOCK_LEFT)) ? '180px' : '0px'
		)
	);
	
	if ($_CLASS['display']->homepage)
	{
		$_CLASS['template']->assign('PAGE_TITLE', ((VIPERAL == 'Admin') ? $Module['title'] : $_CLASS['user']->lang['HOME']));
	} else {
		$_CLASS['template']->assign('PAGE_TITLE', $_CLASS['user']->lang['HOME'].' &gt; '.$Module['title']);
	}
	
	$_CLASS['blocks']->display(BLOCK_LEFT);

	$_CLASS['template']->display('header.html');
}

function themefooter()
{
	global $_CLASS, $MAIN_CFG;
	
	$_CLASS['blocks']->display(BLOCK_RIGHT);
	
	$_CLASS['template']->assign('THEME_FOOTER', $_CLASS['display']->footmsg());
	
	$_CLASS['template']->display('footer.html');
}

function side_block($data)
{
	global $_CLASS;
	
	$data['position'] = ($data['position'] == BLOCK_RIGHT) ? 'right' : 'left';
	
	$_CLASS['template']->assign_vars_array($data['position'].'block', array(
		'TITLE'		=> $data['title'],
		'CONTENT'	=> $data['content'],
		'ID'		=> $data['id'],
		'COLLAPSE'	=> hideblock($data['id']) ? 'style="display: none"' : '',
		'TEMPLATE'	=> $data['template'],
		)
	);
}

?>