<?php
if (!defined('CPG_NUKE')) {
    Header('Location: ../../');
    die();
}

global $bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4;

$bgcolor1 = '#FFFFFF';
$bgcolor2 = '#C7D0D7';
$bgcolor3 = '#EFEFEF';
$bgcolor4 = '#FFC53A';
$textcolor1 = '#009900';
$textcolor2 = '#000000';

function OpenTable() {
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


function themehead() {
	global $sitename, $mainindex, $MAIN_CFG, $themeblockside, $SID, $_CLASS;

	$_CLASS['template']->assign(array(
		'THEME_MAININDEX'	=> $mainindex.'?'.$SID,
		'THEME_SITENAME'	=> $MAIN_CFG['global']['sitename'],
		'MARGINRIGHT'		=> ($_CLASS['blocks']->check_side(BLOCK_RIGHT)) ? '180px' : '0px',
		'MARGINLEFT' 		=> ($_CLASS['blocks']->check_side(BLOCK_LEFT)) ? '180px' : '0px'
		)
	);
	
	if ($_CLASS['display']->homepage) {
		$_CLASS['template']->assign('PAGE_TITLE', ((CPG_NUKE == 'Admin') ? $Module['custom_title'] : $_CLASS['user']->lang['HOME']));
	} else {
		$_CLASS['template']->assign('PAGE_TITLE', $_CLASS['user']->lang['HOME'].' > '.$Module['custom_title']);
	}
	
	$themeblockside = 'left';
	$_CLASS['blocks']->display(BLOCK_LEFT);
	$themeblockside = '';
}

function themefooter() {
	global $_CLASS, $MAIN_CFG, $themeblockside;
	
	$themeblockside = 'right';
	
	$_CLASS['blocks']->display(BLOCK_RIGHT);
	
	$_CLASS['template']->assign('THEME_FOOTER', $_CLASS['display']->footmsg());
	
	$_CLASS['template']->display('footer.html');
}

/***********************************************************************************

 Output the specific block to left or right
	$title  : the title of the block
	$content: all formatted content for the block
	$bid    : the database record ID of the block

************************************************************************************/
function themesidebox($title, $content=false, $bid, $template=false) {
	
	global $_CLASS, $themeblockside;
	
	$_CLASS['template']->assign_vars_array($themeblockside.'block', array(
		'TITLE'		=> $title,
		'CONTENT'	=> $content,
		'BID'		=> $bid,
		'COLLAPSE'	=> hideblock($bid) ? 'style="display: none"' : '',
		'TEMPLATE'	=> $template,
		'IMAGE'   	=> false
		)
	);
	
	if (!$themeblockside) {
		$_CLASS['template']->display('block.html');
	}
}

/***********************************************************************************

 string theme_yesno_option

 Creates 2 radio buttons with a Yes and No option
    $name : name for the <input>
    $value: current value, 1 = yes, 0 = no

************************************************************************************/
function theme_yesno_option($name, $value=0) {
    $sel[intval($value)] = ' checked="checked"';
    return '<input type="radio" name="'.$name.'" value="1"'.$sel[1].' />'._YES.' &nbsp; <input type="radio" name="'.$name.'" value="0" '.$sel[0].' />'._NO;
}
/***********************************************************************************

 string theme_select_option

 Creates a selection dropdown box of all given variables in the array
    $name : name for the <select>
    $value: current/default value
    $array: array like array("value1","value2")

************************************************************************************/
function theme_select_option($name, $value, $array) {
    $sel[$value] = ' selected="selected"';
    $select = '<select name="'.$name."\">\n";
    foreach($array as $var) {
        $select .= "<option$sel[$var]>$var</option>\n";
    }
    return $select.'</select>';
}
/***********************************************************************************

 string theme_select_box

 Creates a selection dropdown box of all given variables in the multi array
    $name : name for the <select>
    $value: current/default value
    $array: array like array("value1 => title1","value2 => title2")

************************************************************************************/
function theme_select_box($name, $value, $array) {
    $sel[$value] = ' selected="selected"';
    $select = '<select name="'.$name."\">\n";
    foreach($array as $val => $title) {
        $select .= "<option value=\"$val\"$sel[$val]>$title</option>\n";
    }
    return $select.'</select>';
}
?>