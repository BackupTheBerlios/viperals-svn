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

Switch (get_variable('mode', 'POST', false))
{
	case 'forum_edit_title':
		$forum_id = get_variable('id', 'POST', false, 'int');
		$title = get_variable('title', 'POST', false);
		
		if (!$forum_id || !$title || !$_CLASS['auth']->acl_get('a_forum'))
		{
			die;
		}

		$title = mb_strtolower(htmlentities($title, ENT_QUOTES, 'UTF-8'));
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
			die;
		}

		$result = $_CLASS['core_db']->query('SELECT forum_id FROM ' . FORUMS_TOPICS_TABLE . ' WHERE topic_id = '.$topic_id);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$row || !$_CLASS['auth']->acl_get('m_edit', $row['forum_id']))
		{
			die;
		}

		$title = mb_strtolower(htmlentities($title, ENT_QUOTES, 'UTF-8'));
		$array = array('topic_title' => $title);

		$_CLASS['core_db']->report_error(false);
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . ' SET '. $_CLASS['core_db']->sql_build_array('UPDATE', $array).' WHERE topic_id = '.$topic_id);

		echo $title;
	break;
}

?>