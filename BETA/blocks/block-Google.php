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

$Site_domain = 'viperal.com';
$Site_logo = 'http://www.viperal.com/themes/PH2/images/Viperal_Logo.png';

$this->content = '<div align="center"><br />
<form name="gs" method="get" action="http://www.google.com/custom">
<input name="hl" value="en" type="hidden" />
<input name="lr" value="" type="hidden" />
<input name="ie" value="ISO-8859-1" type="hidden" />

<input name="cof" value="L:'.$Site_logo.';AH:center;" type="hidden" />
<input name="domains" value="'.$Site_domain.'" type="hidden" />
<input name="q" size="15" maxlength="2048" value="" type="text" />
<input id="site" name="sitesearch" value="'.$Site_domain.'" type="hidden" />
<br /><br /><input class="btnlite" name="btnG" value="Search" type="submit" />

</form></div>';

?>