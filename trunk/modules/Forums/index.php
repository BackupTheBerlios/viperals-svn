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

$file = get_variable('file', 'REQUEST', false);

$approved_files = array('viewforum', 'viewtopic', 'report', 'faq', 'posting', 'download', 'mcp', 'search');

if (!$file || !in_array($file, $approved_files))
{
	$file = 'main';
}

unset($approved_files);

require_once($site_file_root.'includes/forums/functions.php');
load_class($site_file_root.'includes/forums/auth.php', 'auth');

$_CLASS['auth']->acl($_CLASS['core_user']->data);

$_CLASS['core_user']->user_setup();
include(dirname(__FILE__)."/$file.php");

?>