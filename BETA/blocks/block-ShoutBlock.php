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

global $prefix, $_CLASS, $bgcolor1, $bgcolor2, $MAIN_CFG;

$this->content = '<div style="width: 100%; height: '.$MAIN_CFG['Shoutblock']['height'].'px; overflow: auto;">
';

$bgcolor = '';


$result = $_CLASS['db']->sql_query('select * from '.$prefix.'_shoutblock order by time DESC LIMIT '. $MAIN_CFG['Shoutblock']['number']);

while ($row = $_CLASS['db']->sql_fetchrow($result)) {

	$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;
	
	$this->content .= '<div style="padding: 4px; background-color:' . $bgcolor . ';">';
	
	if ($row['user_name'])
	{
		if ($row['user_id'] && is_user()) 
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
	
	$this->content .= $row['shout'].'<br />'.(($MAIN_CFG['Shoutblock']['time']) ? $_CLASS['user']->format_date($row['time']) : '').'</div>';
}

$_CLASS['db']->sql_freeresult($result);

$this->content .= '</div>';

$this->content .= '<form name="post" method="post" action="'.getlink('Shoutblock&amp;option=shout').'" enctype="multipart/form-data" accept-charset="utf-8">'
				.'		<div align="center"><a href="'.getlink('Shoutblock').'">Shout History</a><br />';
if (!is_user()) {
	if ( !$MAIN_CFG['Shoutblock']['allow_anonymous'] )
	{
		$this->content .= 'Only Registered Users can Shout<br /><a href="'.getlink('Control_Panel&amp;mode=register').'">[ Register&nbsp;|&nbsp;</a><a href="'.getlink('Control_Panel').'">Login ]</a><br /></div>
		';
		return;
	} elseif ($MAIN_CFG['Shoutblock']['allow_anonymous'] == '2') {
		$this->content .= 'Name: <br /><input class="post" type="text" name="name" size="10" maxlength="10" /><br />';
	}
}

$this->content .= '<input class="post" type="text" name="shout" size="20" maxlength="'.$MAIN_CFG['Shoutblock']['maxlength'].'" value="" /><br /><br />
				<input type="hidden" name="redirect" value="'.htmlentities($_CLASS['user']->url).'" />
			<input class="btnlite" type="submit" name="submit" value="Shout" />&nbsp;&nbsp;&nbsp;
		<a title="View smilies. (opens new window)" target="_smilies" onclick="window.open(\''.getlink('smilies&amp;field=comment&amp;form=form1').'\', \'_smilies\', \'HEIGHT=200,resizable=yes,scrollbars=yes,WIDTH=230\');return false;" href="'.getlink('smilies&amp;field=comment&amp;form=form1').'"><input class="btnlite" type="button" value="Smilies" /></a>
		</div></form>
		';

?>