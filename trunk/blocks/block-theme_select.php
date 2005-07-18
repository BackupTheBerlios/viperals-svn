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
    die;
}

$this->content = '
<form action="'.generate_link($_CLASS['core_user']->url).'" method="post">
	<p style="text-align: center;">
		<select name="prevtheme" onchange="this.form.submit();">'.theme_select().'</select>
		<br /><br /><input class="button" value="Select" type="submit" />
	</p>
</form>';

?>