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

if (!defined('VIPERAL'))
{
    Header('Location: ../');
    die();
}

global $prefix, $_CLASS, $bgcolor1, $bgcolor2, $MAIN_CFG;

$this->content = '<div style="width: 100%; height: '.$MAIN_CFG['Shoutblock']['height'].'px; overflow: auto;">';

$bgcolor = '';

$result = $_CLASS['db']->sql_query('select * from '.$prefix.'_shoutblock order by time DESC LIMIT 10');

while ($row = $_CLASS['db']->sql_fetchrow($result)) {

	$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;
	
	$wordsarray = explode(' ',$row['shout']);
	$row['shout'] = '';

    foreach($wordsarray as $words)
    {
		if (substr($words, 0,4) != '[url')
		{
			$row['shout'] .= ' '.wordwrap($words, 18, "\n", 1);
		} else {
			$row['shout'] .= $words;
		}
    }
	
	$this->content .= '<div style="padding: 4px; background-color:' . $bgcolor . ';">';
	
	if ($row['user_name'])
	{
		if ($row['user_id']) 
		{
			$this->content .= '<a href="'.getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']).'"><b>' . $row['user_name'] . ': </b></a>
			';
		}  else {
			$this->content .= '<b>' . $row['user_name'] . ': </b>
			'; 
		}
	} else {
		$this->content .= '<b>' . $_CLASS['user']->lang['ANONYMOUS'] . ': </b>
		';
	}
	
	if ($row['user_id'])
	{
		$row['shout'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['shout']);
	}
	
	$this->content .= $row['shout'].'<br />'.(($MAIN_CFG['Shoutblock']['time']) ? $_CLASS['user']->format_date($row['time']) : '').'</div>';
}

$_CLASS['db']->sql_freeresult($result);

$this->content .= '</div>';

$this->content .= '<form name="post" method="post" action="'.getlink('Shoutblock&amp;mode=shout').'" enctype="multipart/form-data">'
				.'		<div align="center"><a href="'.getlink('Shoutblock').'">Shout History</a><br />';
if (!is_user()) 
{
	if ( !$MAIN_CFG['Shoutblock']['allow_anonymous'] )
	{
		$this->content .= 'Only Registered Users can Shout<br /><a href="'.getlink('Control_Panel&amp;mode=register').'">[ Register&nbsp;|&nbsp;</a><a href="'.getlink('Control_Panel').'">Login ]</a><br /></div>
		';
		return;
	} elseif ( $MAIN_CFG['Shoutblock']['allow_anonymous'] == '2') {
		$this->content .= 'Name: <br /><input class="post" type="text" style="width:90%;" name="user_name" size="10" maxlength="10" /><br />';
	}
}

//maxlength="'.$MAIN_CFG['Shoutblock']['maxlength'].'"

$this->content .= 'Message <br/> <textarea name="shout" style="width:90%;" rows="3"></textarea><br /><br />
			<input class="btnlite" type="submit" name="submit" value="Shout" />
		</div></form>
		';

?>