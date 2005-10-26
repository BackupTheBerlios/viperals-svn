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

header('Content-Type: text/html');

require_once(SITE_FILE_ROOT.'includes/forums/functions.php');
load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');

$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);

Switch (get_variable('mode', 'POST', false))
{
	case 'forum_edit_title':
		$forum_id = get_variable('id', 'POST', false, 'int');
		$title = get_variable('title', 'POST', false);
		
		if (!$forum_id || !$title || !$_CLASS['forums_auth']->acl_get('a_forum'))
		{
			script_close();
		}

		$title = htmlentities($title, ENT_QUOTES, 'UTF-8');
		$array = array('forum_name' => $title);
		
		$_CLASS['core_db']->report_error(false);
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_FORUMS_TABLE . ' SET '. $_CLASS['core_db']->sql_build_array('UPDATE', $array).' WHERE forum_id = '.$forum_id);

		echo $title;
	break;

	case 'topic_edit_title':
		$topic_id = get_variable('id', 'POST', false, 'int');
		$title = get_variable('title', 'POST', false);

		if (!$topic_id || !$title )
		{
			script_close();
		}

		$result = $_CLASS['core_db']->query('SELECT forum_id FROM ' . FORUMS_TOPICS_TABLE . ' WHERE topic_id = '.$topic_id);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row || !$_CLASS['forums_auth']->acl_get('m_edit', $row['forum_id']))
		{
			script_close();
		}

		$title = mb_strtolower(htmlentities($title, ENT_QUOTES, 'UTF-8'));
		$array = array('topic_title' => $title);

		$_CLASS['core_db']->report_error(false);
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . ' SET '. $_CLASS['core_db']->sql_build_array('UPDATE', $array).' WHERE topic_id = '.$topic_id);

		echo $title;
	break;
	
	case 'topic_lock_unlock':
		$topic_id = get_variable('id', 'POST', false, 'int');
		$lock = get_variable('lock', 'POST', 0, 'int');

		if (!$topic_id)
		{
			script_close();
		}

		$result = $_CLASS['core_db']->query('SELECT forum_id FROM ' . FORUMS_TOPICS_TABLE . ' WHERE topic_id = '.$topic_id);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row || !$_CLASS['forums_auth']->acl_get('m_lock', $row['forum_id']))
		{
			script_close();
		}

		$status = ($lock) ? ITEM_LOCKED : ITEM_UNLOCKED;
		//	WHERE topic_id IN (" . implode(', ', $topic_id) . ")";

		$sql = 'UPDATE '. FORUMS_TOPICS_TABLE ." 
			SET topic_status = $status
			WHERE topic_id = $topic_id";
		$_CLASS['core_db']->query($sql);

		echo ($lock) ? 'lock' : 'unlock';
	break;
	
	case 'forum_lock_unlock':
		$forum_id = get_variable('id', 'POST', false, 'int');
		$lock = get_variable('lock', 'POST', 0, 'int');

		if (!$forum_id || !$_CLASS['forums_auth']->acl_get('a_forum', $forum_id))
		{
			script_close();
		}

		$status = ($lock) ? ITEM_LOCKED : ITEM_UNLOCKED;
		//	WHERE topic_id IN (" . implode(', ', $topic_id) . ")";

		$sql = 'UPDATE '. FORUMS_FORUMS_TABLE ." 
			SET forum_status = $status
			WHERE forum_id = $forum_id";
		$_CLASS['core_db']->query($sql);

		echo ($lock) ? 'lock' : 'unlock';
	break;
}

script_close();
?>