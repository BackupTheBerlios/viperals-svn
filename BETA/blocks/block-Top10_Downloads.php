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

global $_CLASS, $prefix;

$sql = 'SELECT lid, title, description  FROM '.$prefix.'_downloads_downloads ORDER BY date DESC LIMIT 12';
$result = $_CLASS['db']->sql_query($sql);

if (!$row = $_CLASS['db']->sql_fetchrow($result))
{
	$db->sql_freeresult($result);
	return;
}

$counter = 0;
$num = 1;
$this->content = '<table width="100%" border="0" cellpadding="0"><tr>';

do {

    if ($counter == 3)
    {
        $this->content .= '</tr><tr>';
        $counter = 0;
	}
	
	$row['description'] = htmlentities(trim_text($row['description'], '<br />'), ENT_QUOTES);

    $title = (strlen($row['title']) <  20) ? $row['title'] : substr($row['title'], 0, 20) . '...';
    
    $this->content .= '<td width="33%"><span style="font-size: 140%; font-weight: bold;" >'.$num.'</span>'
               .'&nbsp;<a style="font-weight: bold;" href="'.getlink('Downloads&amp;d_op=viewdownloaddetails&amp;lid='.$row['lid']).'">'.$title.'&nbsp;<img src="images/download.gif" border="0" alt="" title="" width="10" height="10" /></a>'
               .'<br /></td>';
    
    $counter ++;
    $num++;
}
while($row = $_CLASS['db']->sql_fetchrow($result));

$_CLASS['db']->sql_freeresult($result);

$this->content .= '</tr></table>';

?>