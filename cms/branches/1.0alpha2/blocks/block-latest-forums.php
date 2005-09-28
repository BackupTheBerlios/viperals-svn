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

global $_CLASS;

load_class(SITE_FILE_ROOT.'includes/forums/auth.php', 'forums_auth');
$_CLASS['forums_auth']->acl($_CLASS['core_user']->data);
$auth_read = $_CLASS['forums_auth']->acl_getf('f_read');

if (!$auth_read)
{
	return;
}

$latest_active_topics = 12;

$sql = 'SELECT forum_id, topic_last_post_id ,topic_title, topic_last_poster_name, topic_last_poster_id, topic_last_post_time
			FROM ' . FORUMS_TOPICS_TABLE . ' WHERE forum_id IN ('. implode(', ', array_keys($auth_read)) .') 
				AND topic_moved_id = 0 AND topic_approved = 1
				ORDER BY topic_last_post_time DESC';

$result = $_CLASS['core_db']->query_limit($sql, $latest_active_topics);
$row = $_CLASS['core_db']->fetch_row_assoc($result);

if (!$row)
{
	$_CLASS['core_db']->free_result($result);

	return;
}

$counter = 0;
$num = 1;

$this->content = '<table width="100%" border="0" cellpadding="4"><tr>';
do {

	if ($counter == 3)
	{
		$this->content .= '</tr><tr>';

		$counter = 0;
	}

	$row['topic_title'] = (strlen($row['topic_title']) <  25) ? $row['topic_title'] : substr($row['topic_title'], 0, 25) . '...';

	$this->content .= '<td width="33%">'
    .'<span style="font-size: 140%; font-weight: bold;" >'.$num.'</span>&nbsp;'
    .'<a style="font-weight: bold;" href="'.generate_link('Forums&amp;file=viewtopic&amp;f='.$row['forum_id'].'&amp;p='.$row['topic_last_post_id'].'#'.$row['topic_last_post_id'])
    .'" >'.$row['topic_title'].'</a><br />'
    .'<i>Last post by <a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']).'">'. $row['topic_last_poster_name'] .'</a>'
    .'<br/> on '.$_CLASS['core_user']->format_date($row['topic_last_post_time']).'</i><br /><br /></td>';
	
	$counter ++;
	$num ++;
}
while($row = $_CLASS['core_db']->fetch_row_assoc($result));

$_CLASS['core_db']->free_result($result);

$this->content .= '</tr></table>';

?>