<?php
global $bgcolor1, $bgcolor2, $bgcolor3, $bgcolor4;

$lnkcolor = '#006699';
$bgcolor1 = '#F2F1ED';
$bgcolor2 = '#F1BA67';
$bgcolor3 = '#FBCF92';
$bgcolor4 = '#F2F1ED';
//$textcolor1 = '#000000';
//$textcolor2 = '#006699';

function OpenTable() {
	echo '<table class="opentable1" cellspacing="1" cellpadding="5">'
  		.'<tr><td>';
}

function CloseTable() {
	echo '</td></tr></table><br />';
}

function OpenTable2() {
	echo '<table class="opentable1" cellspacing="0" cellpadding="5">'
  		.'<tr><td>';
}

function CloseTable2() {
	echo '</td></tr></table><br />';
}

function themehead() {
    global $slogan, $banners, $mainindex, $_CLASS, $themeblockside;
	
	$rightimage = $leftimage = '';
	
	if ($_CLASS['blocks']->check_side(BLOCK_RIGHT)) {
		$img = (hideblock('601')) ? 'themes/PH2/images/plus2.gif' : 'themes/PH2/images/minus2.gif';
		$rightimage = '<img alt="[x]" title="Show/hide content" id="pic601" src="'.$img.'" onclick="blockswitch(\'601\',\'themes/PH2/images/minus2.gif\', \'themes/PH2/images/plus2.gif\');" style="cursor:pointer" />';
	}
	
	if ($_CLASS['blocks']->check_side(BLOCK_LEFT)) {
		$img = (hideblock('600')) ? 'themes/PH2/images/plus2.gif' : 'themes/PH2/images/minus2.gif';
		$leftimage = '<img alt="[x]" title="Show/hide content" id="pic600" src="'.$img.'" onclick="blockswitch(\'600\',\'themes/PH2/images/minus2.gif\', \'themes/PH2/images/plus2.gif\');" style="cursor:pointer" />';
	}
		
    $_CLASS['template']->assign(array(
        'THEME_LEFT_VISIBLE'=> hideblock('600') ? 'style="display: none"' : '',
        'THEME_LEFT_IMAGE'	=> $leftimage,
        'THEME_RIGHT_VISIBLE'=> hideblock('601') ? 'style="display: none"' : '',
        'THEME_RIGHT_IMAGE'	=> $rightimage,
        'IS_ADMIN'   		=> is_admin(),
        'IS_USER'    		=> is_user(),
		'THEME_BANNER'     	=> '',
        'THEME_MAININDEX'	=> $mainindex,
        'THEME_DOWNLOADS'	=> getlink('Downloads'),
        'THEME_FORUMS'		=> getlink('Forums'),
        'THEME_MY_ACCOUNT'	=> getlink('Control_Panel'),
        'THEME_PRIVATE_M'	=> getlink('Control_Panel&amp;i=pm'),
        'THEME_GALLERY'		=> getlink('coppermine'),
        'THEME_SEARCH'		=> getlink('Search')
        )
    );
    
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

 void themesidebox

 Output the specific block to left or right
    $title  : the title of the block
    $content: all formatted content for the block
    $bid    : the database record ID of the block

************************************************************************************/
function themesidebox($title, $content=false, $bid, $template=false) {
    global $_CLASS, $themeblockside;
    
	$_CLASS['template']->assign_vars_array($themeblockside.'block', array(
        'TITLE'   => $title,
        'CONTENT' => $content,
        'BID'     => $bid,
        'VISIBLE' => hideblock($bid) ? 'style="display:none"' : '',
        'IMAGE'   => 'themes/PH2/images/'.(hideblock($bid) ? 'plus.gif' : 'minus.gif')
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
    $sel[($value==0)] = '';
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
        $select .= '<option'.(isset($sel[$var])?$sel[$var]:'').">$var</option>\n";
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
        $select .= "<option value=\"$val\"".(isset($sel[$val]) ? $sel[$val] : '').">$title</option>\n";
    }
    return $select.'</select>';
}
?>