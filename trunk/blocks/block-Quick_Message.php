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
    die;
}

global $prefix, $_CLASS, $_CORE_CONFIG;

$this->content = '<div style="width: 100%; height: '.$_CORE_CONFIG['quick_message']['height'].'px; overflow: auto;">';


$result = $_CLASS['core_db']->sql_query('select * from '.$prefix.'_quick_message order by time DESC LIMIT 10');

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{

	$words_array = explode(' ',$row['message']);
	$row['message'] = '';

    foreach($words_array as $words)
    {
		if (substr($words, 0, 4) != '[url')
		{
			$row['message'] .= ' '.wordwrap($words, 18, "\n", 1);
		}
		else
		{
			$row['message'] .= $words;
		}
    }

	unset($words_array, $words);
	
	$this->content .= '<div style="padding: 4px;">';
	
	if ($row['user_name'])
	{
		if ($row['user_id']) 
		{
			$this->content .= '<a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']).'"><b>' . $row['user_name'] . ': </b></a>
			';
		}  else {
			$this->content .= '<b>' . $row['user_name'] . ': </b>
			'; 
		}
	} else {
		$this->content .= '<b>' . $_CLASS['core_user']->lang['ANONYMOUS'] . ': </b>
		';
	}
	
	if ($row['user_id'])
	{
		$row['message'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['message']);
	}
	
	$this->content .= $row['message'].'<br />'.(($_CORE_CONFIG['quick_message']['time']) ? $_CLASS['core_user']->format_date($row['time']) : '').'</div><hr/>';
}

$_CLASS['core_db']->sql_freeresult($result);

$this->content .= '</div>';

$this->content .= '<form name="post" method="post" action="'.generate_link('Quick_Message&amp;mode=add').'" enctype="multipart/form-data">'
				.'		<div align="center"><a href="'.generate_link('Quick_Message').'">Message History</a><br />';
if (!$_CLASS['core_user']->is_user) 
{
	if ( !$_CORE_CONFIG['quick_message']['allow_anonymous'] )
	{
		$this->content .= 'Only registered users can post<br /><a href="'.generate_link('Control_Panel&amp;mode=register').'">[ Register&nbsp;|&nbsp;</a><a href="'.generate_link('Control_Panel').'">Login ]</a><br /></div>
		';
		return;
	} elseif ( $_CORE_CONFIG['quick_message']['allow_anonymous'] == '2') {
		$this->content .= 'Name: <br /><input class="post" type="text" style="width:90%;" name="user_name" size="10" maxlength="10" /><br />';
	}
}

$this->content .= 'Message <br/> <textarea name="message" style="width:90%;" rows="3"></textarea><br /><br />
			<input class="button" type="submit" name="submit" value="Post" />
		</div></form>
		';

?>