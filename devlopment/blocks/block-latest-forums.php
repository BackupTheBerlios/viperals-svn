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

global $_CLASS, $phpEx;

$Latest_Active_Topics = 12;

$result = $_CLASS['db']->sql_query('SELECT 
forum_id, topic_last_post_id ,topic_title, 
topic_last_poster_name, topic_last_poster_id, topic_last_post_time
FROM '.TOPICS_TABLE.' WHERE topic_moved_id=0 AND topic_approved=1 ORDER BY topic_last_post_time DESC 
LIMIT 0, '.$Latest_Active_Topics);

if (!$row = $_CLASS['db']->sql_fetchrow($result))
{
	$_CLASS['db']->sql_freeresult($result);
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
    .'<a style="font-weight: bold;" href="'.getlink('Forums&amp;file=viewtopic&amp;f='.$row['forum_id'].'&amp;p='.$row['topic_last_post_id'].'#'.$row['topic_last_post_id'])
    .'" >'.$row['topic_title'].'</a><br />'
    .'<i>Last post by <a href="'.getlink('Members_List&amp;mode=viewprofile&amp;u='.$row['topic_last_poster_id']).'">'. $row['topic_last_poster_name'] .'</a>'
    .'<br/> on '.$_CLASS['user']->format_date($row['topic_last_post_time']).'</i><br /><br /></td>';
	
    $counter ++;
    $num ++;
}
while($row = $_CLASS['db']->sql_fetchrow($result));

$_CLASS['db']->sql_freeresult($result);

$this->content .= '</tr></table>';

?>