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

global $table_prefix;

if (!defined('QUICK_MESSAGE_TABLE'))
{
	define('QUICK_MESSAGE_TABLE', $table_prefix.'quick_message');
}

function qm_block_content()
{
	global $_CLASS, $_CORE_CONFIG;
	
	$content = '<div style="width: 100%; height: '.$_CORE_CONFIG['quick_message']['height'].'px; overflow: auto;">';
	
	$result = $_CLASS['core_db']->query_limit('SELECT * from '.QUICK_MESSAGE_TABLE.' ORDER BY message_time DESC', 10);
	
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$words_array = explode(' ', $row['message_text']);
	
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
		
		$content .= '<div style="padding: 4px;">';
		
		if ($row['poster_name'])
		{
			$row['poster_name'] = htmlentities($row['poster_name'], ENT_QUOTES, 'UTF-8');
	
			if ($row['poster_id']) 
			{
				$content .= '<a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']).'"><b>' . $row['poster_name'] . ': </b></a>';
			}
			else
			{
				$content .= '<b>' . $row['poster_name'] . ': </b>'; 
			}
		}
		else
		{
			$content .= '<b>' . $_CLASS['core_user']->lang['ANONYMOUS'] . ': </b>';
		}
	
		if ($row['poster_id'])
		{
			$row['message_text'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['message_text']);
		}
	
		$content .= $row['message_text'].'<br />'. $_CLASS['core_user']->format_date($row['message_time']) .'</div><hr/>';
	}
	
	$_CLASS['core_db']->free_result($result);
	
	$content .= '</div>';

	return $content;
}

?>