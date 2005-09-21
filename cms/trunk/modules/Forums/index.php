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

$file = get_variable('file', 'REQUEST', false);

$approved_files = array('viewforum', 'viewtopic', 'report', 'faq', 'posting', 'download', 'mcp', 'search');

if (!$file || !in_array($file, $approved_files))
{
	$file = 'main';
}

unset($approved_files);

require_once(SITE_FILE_ROOT.'includes/forums/functions.php');
load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'auth');

$_CLASS['auth']->acl($_CLASS['core_user']->data);

$_CLASS['core_user']->user_setup();
include(dirname(__FILE__)."/$file.php");

?>