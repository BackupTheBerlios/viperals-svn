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
    Header('Location: ../../');
    die('Sorry you can\'t access this file directly');
}

// Import GET/POST/Cookie variables for older modules
if (!ini_get('register_globals')) {
	import_request_variables('GPC');
}

?>