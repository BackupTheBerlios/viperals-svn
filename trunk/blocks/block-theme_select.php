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

$this->content = '
<form action="" method="post">
	<p style="text-align: center;">
		<select name="prevtheme" onchange="this.form.submit();">'.select_theme().'</select>
		<br /><br /><input class="button" value="Select" type="submit" />
	</p>
</form>';

?>