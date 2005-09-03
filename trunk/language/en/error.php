<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal©	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
$homepage = 'http://www.viperal.com';

$error = array(
	'403' => array(
		'header'	=> 'HTTP/1.0 403 Forbidden',
		'lang'		=> '<div style="text-align: center"<b>Sorry, you don\'t have currect authorization to view this page.</b><br /><br /> Please check the URL for proper spelling and capitalization<br />'
					.'<br /><a href="'. $homepage .'">Click here to homepage</a><br />[ <a href="javascript:history.go(-1)">Go Back</a> ]</div>'),
	'404' => array(
		'header'	=> 'HTTP/1.0 404 Not Found',
		'lang'		=> '<div style="text-align: center"<b>Sorry, the page you requested was not found.</b><br /><br /> Please check the URL for proper spelling and capitalization<br /> If you believe this is a broken like please contact the stie admin.<br />'
		.'<br /><a href="'. $homepage .'">Click here to homepage</a><br />[ <a href="javascript:history.go(-1)">Go Back</a> ]</div>'),	
);

?>