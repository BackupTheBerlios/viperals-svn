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

$sql = 'UPDATE ' . USERS_TABLE. ' u, ' . SESSIONS_TABLE . ' s
			SET u.user_last_visit = s.session_time
			WHERE s.session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']) . '
				AND s.session_user_id <> ' . ANONYMOUS .'
				AND u.user_id = s.session_user_id';
$_CLASS['core_db']->sql_query($sql);

$sql = 'DELETE FROM ' . SESSIONS_AUTOLOGIN_TABLE . '
			WHERE auto_login_time < ' . ($time - 2592000);
$_CLASS['core_db']->query($sql);

$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
			WHERE session_time < ' . ($time - $_CORE_CONFIG['server']['session_length']);
$_CLASS['core_db']->query($sql);

$_CLASS['core_db']->optimize_tables(SESSIONS_TABLE);

?>