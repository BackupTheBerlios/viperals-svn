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
    Header('Location: ../');
    die();
}

$this->content = '<div style="text-align: center;"><form action="'.$_SERVER['REQUEST_URI'].'" method="post"><select name="prevtheme">';

$this->content .= theme_select();

$this->content .= '</select><br /><br /><input class="btnlite" name="" value="Select" type="submit" /><br /><br />
This change is temp to you session.</form></div>';

?>