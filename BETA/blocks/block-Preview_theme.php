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

if (!defined('VIPERAL')) {
    Header('Location: ../');
    die();
}
global $_CLASS;

$theme = array();

$handle = opendir('themes');
while ($file = readdir($handle)) {
	if (!ereg('[.]',$file)) {
		if (file_exists("themes/$file/index.php")) {
			$theme[] = array('file' => $file, 'template'=> true);
		} elseif (file_exists("themes/$file/theme.php")) {
			$theme[] = array('file' => $file, 'template'=> false);
		} 
	} 
}

closedir($handle);

$this->content = '<div style="text-align: center;"><form action="'.$_SERVER['REQUEST_URI'].'" method="post"><select name="prevtheme">';

$count = count($theme);

for ($i=0; $i < $count; $i++) {
	
	$theme[$i]['name'] = ($theme[$i]['template']) ? $theme[$i]['file'].' *' : $theme[$i]['file'];
	if ($theme[$i]['file'] == $_CLASS['display']->theme)
	{
		$this->content .= '<option value="'.$theme[$i]['file'].'" selected="selected">'.$theme[$i]['name'].'</option>';
	} else {
		$this->content .= '<option value="'.$theme[$i]['file'].'">'.$theme[$i]['name'].'</option>';
	}
}

unset($theme);

$this->content .= '</select><br /><br /><input class="btnlite" name="" value="Select" type="submit" /><br /><br />
This change is temp to you session.</form></div>';
?>