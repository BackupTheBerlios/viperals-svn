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

if (!defined('QUICK_MESSAGE_TABLE'))
{
	define('QUICK_MESSAGE_TABLE', 'test_quick_message');
}

global $prefix, $_CLASS, $_CORE_CONFIG;

$this->content = '<div style="width: 100%; height: '.$_CORE_CONFIG['quick_message']['height'].'px; overflow: auto;">';

$result = $_CLASS['core_db']->query_limit('SELECT * from '.QUICK_MESSAGE_TABLE.' ORDER BY message_time DESC', 10);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$words_array = explode(' ',html_entity_decode($row['message_text'], ENT_QUOTES, 'UTF-8'));
	//$words_array = explode(' ',html_entity_decode($row['message_text'], ENT_QUOTES));

	$row['message_text'] = '';

    foreach($words_array as $words)
    {
		if (substr($words, 0, 4) != '[url')
		{
			$row['message_text'] .= ' '.wordwrap($words, 18, "\n", 1);
		}
		else
		{
			$row['message_text'] .= $words;
		}
    }

	$row['message_text'] = htmlentities($row['message_text'], ENT_QUOTES, 'UTF-8');

	unset($words_array, $words);
	
	$this->content .= '<div style="padding: 4px;">';
	
	if ($row['poster_name'])
	{
		if ($row['poster_id']) 
		{
			$this->content .= '<a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']).'"><b>' . $row['poster_name'] . ': </b></a>';
		}
		else
		{
			$this->content .= '<b>' . $row['poster_name'] . ': </b>'; 
		}
	}
	else
	{
		$this->content .= '<b>' . $_CLASS['core_user']->lang['ANONYMOUS'] . ': </b>';
	}

	if ($row['poster_id'])
	{
		$row['message_text'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['message_text']);
	}

	$this->content .= $row['message_text'].'<br />'. $_CLASS['core_user']->format_date($row['message_time']) .'</div><hr/>';
}

$_CLASS['core_db']->free_result($result);

$this->content .= '</div><div align="center"><a href="'.generate_link('Quick_Message').'">Message History</a><br />';

if (!$_CLASS['core_user']->is_user && !$_CORE_CONFIG['quick_message']['anonymous_posting'])
{
	$this->content .= '<br/>Only registered users can post<br />[ <a href="'.generate_link('Control_Panel&amp;mode=register').'">Register</a>&nbsp;|&nbsp;<a href="'.generate_link('Control_Panel').'">Login</a> ]<br /></div>';

	return;
}

$this->content .= '<form name="post" method="post" action="'.generate_link('Quick_Message&amp;mode=add').'">';

if (!$_CLASS['core_user']->is_user && $_CORE_CONFIG['quick_message']['anonymous_posting'] == '2')
{
	$this->content .= 'Name: <br /><input class="post" type="text" style="width:90%;" name="poster_name" size="10" maxlength="10" /><br />';
}

$this->content .= 'Message <br/> <textarea name="message" style="width:90%;" rows="3"></textarea><br /><br />
			<input class="button" type="submit" name="submit" value="Post" />
		</div></form>';

?>