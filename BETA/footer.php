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

/*
this file is no longer being used please review the core/displayt/display.php for more info on the new stuff
call footor with 
$_CLASS['display']->foot();
$C_LASS['display']->footmsg();
*/
if (!CPG_NUKE) {
    Header('Location: /');
    die();
}
global $_CLASS;

$_CLASS['display']->display_footer();

function footmsg() {
	global $CLASS;
	$C_LASS['display']->footer();
}

?>