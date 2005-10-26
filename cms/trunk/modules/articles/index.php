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

if (!defined('ARTICLES_TABLE'))
{
	global $table_prefix;

	define('ARTICLES_TABLE', $table_prefix.'articles');
}

$this->supported = array('page', 'feed');

class module_articles
{
	function page_articles()
	{
		global $_CLASS;
		
		$_CLASS['core_user']->user_setup();
		
		if (isset($_GET['mode']))
		{
			Switch ($_GET['mode'])
			{
				Case 'print':
					$print = true;
				Case 'view':
					$print = isset($print);
		
					$id = get_variable('id', 'GET', false, 'int');
				
					if (!$id)
					{
						trigger_error('ARTICLE_NOT_FOUND');
					}
				
					$result = $_CLASS['core_db']->query('SELECT * FROM ' . ARTICLES_TABLE . ' WHERE articles_id = ' . $id);
					$row = $_CLASS['core_db']->fetch_row_assoc($result);
					$_CLASS['core_db']->free_result($result);
		
					if (!$row || $row['articles_status'] != STATUS_ACTIVE)
					{
						trigger_error('ARTICLE_NOT_FOUND');
					}
		
					$_CLASS['core_template']->assign_array(array(
						'ARTICLES_POSTER' 		=> ($row['poster_name']) ? $row['poster_name'] : $_CLASS['core_user']->get_lang('ANONYMOUS'),
						'ARTICLES_POSTER_LINK'	=> ($row['poster_name'] && $row['poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : '',
						'ARTICLES_TEXT'			=> $row['articles_text'],
						'ARTICLES_CONTENT_LINK' => generate_link('articles&amp;mode=view&amp;id='.$row['articles_id']),
						'ARTICLES_TIME'			=> $_CLASS['core_user']->format_date($row['articles_posted']),
						'ARTICLES_TITLE'		=> $row['articles_title'],
						'ARTICLES_ID'       	=> $id,
						'ARTICLES_LINK_PRINT'	=> generate_link('articles&amp;mode=print&amp;id='.$row['articles_id']),
						'ARTICLES_LINK_SEND'	=> generate_link('articles&amp;mode=send&amp;id='.$row['articles_id']),
					));
		
					$_CLASS['core_display']->display(false, ($print) ? 'modules/articles/print.html' : 'modules/articles/view.html');	
		
					script_close();
				break;
			}
		}
		
		$start = get_variable('start', 'GET', false, 'int');
		$collapable_holding = array();
		$expire_updated = false;
		$limit = 10;
		
		$sql = 'SELECT * FROM '.  ARTICLES_TABLE .' WHERE articles_status = ' . STATUS_ACTIVE . ' ORDER BY articles_order ASC';
		$result = $_CLASS['core_db']->query_limit($sql, $limit, $start);
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
		// this can cause problems, only thing to do is remove the limit query and do a loop until we get the needed articles
			if ($row['articles_auth'] && !$_CLASS['core_auth']->auth(@unserialize($row['articles_auth'])) && !$_CLASS['core_auth']->admin_power('articles'))
			{
				continue;
			}
			
			if ($row['articles_expires'] && !$expire_updated && ($_CLASS['core_user']->time > $row['articles_expires']))
			{
				$_CLASS['core_db']->query('UPDATE '.ARTICLES_TABLE.' SET articles_status = ' . STATUS_DISABLED . ' WHERE articles_expires > 0 AND articles_expires <= ' . $_CLASS['core_user']->time);
				$expire_updated = true;
		
				continue;
			}
		
			if ($row['articles_starts'] && ($row['articles_starts'] > $_CLASS['core_user']->time))
			{
				continue;
			}
		
			$_CLASS['core_template']->assign_vars_array('articles', array(
				'poster' 		=> ($row['poster_name']) ? $row['poster_name'] : $_CLASS['core_user']->get_lang('ANONYMOUS'),
				'content'		=> ($row['articles_intro']) ? $row['articles_intro'] : $row['articles_text'],
				'time'			=> $_CLASS['core_user']->format_date($row['articles_posted']),
				'title'			=> $row['articles_title'],
				'id'      		=> $row['articles_id'],
		
				'collapse'  	=> check_collapsed_status('a_'.$row['articles_id']),
				'full_story'	=> ($row['articles_intro'] && $row['articles_text']),
		
				'link_poster'	=> ($row['poster_name'] && $row['poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : '',
				'link_content'  => generate_link('articles&amp;mode=view&amp;id='.$row['articles_id']),
				'link_print' 	=> generate_link('articles&amp;mode=print&amp;id='.$row['articles_id']),
				'link_send' 	=> generate_link('articles&amp;mode=send&amp;id='.$row['articles_id']),
			));
		
			$collapable_holding[] = 'a_'.$row['articles_id'];
		}
		$_CLASS['core_db']->free_result($result);
		
		// Garbage collection, would cause problems with guest/loggin articl views
		if ($cookie_data = get_variable('collapsed_items', 'COOKIE'))
		{
			$collapsed_items = ($cookie_data) ? explode(':', $cookie_data) : array();
			$count = count($collapsed_items);
		
			for($i = 0; $i < $count; $i++)
			{
				if (mb_strpos($collapsed_items[$i], 'a_') === 0 && !in_array($collapsed_items[$i], $collapable_holding))
				{
					unset($collapsed_items[$i]);
				}
			}
		
			$collapsed_items = implode(':', $collapsed_items);
		
			setcookie('collapsed_items', $collapsed_items, (int) $_CLASS['core_user']->time + 31536000000, '/');
		}
		
		$result = $_CLASS['core_db']->query('SELECT COUNT(*) AS total FROM ' . ARTICLES_TABLE . ' WHERE articles_status = '.STATUS_ACTIVE);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		$pagination = generate_pagination('articles', $row['total'], $limit, $start);
		
		$_CLASS['core_template']->assign_array(array(
			'articles_pagination' 		=> $pagination['formated'],
			'articles_pagination_array' => $pagination['array']
		));
		
		$_CLASS['core_display']->display(false, 'modules/articles/index.html');
	}
	
	function feed_articles()
	{
		global $_CLASS;

		$result = $_CLASS['core_db']->query_limit('SELECT articles_id, articles_title, articles_intro, articles_text, articles_posted, articles_starts, poster_name FROM ' . ARTICLES_TABLE . ' ORDER BY articles_order ASC', 10);
		
		$last_post_time = 0;
		
		// Need to fix this, add auth and expires/start check ( will have to remove limit )
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$time = ($row['articles_starts']) ? $row['articles_starts'] : $row['articles_posted'];
			$text = ($row['articles_intro']) ? $row['articles_intro'] : $row['articles_text'];
		
			if ($time > $last_post_time)
			{
				$last_post_time = $time;
			}
		
			$_CLASS['core_template']->assign_vars_array('items', array(
				'TITLE' 			=> htmlspecialchars($row['articles_title'], ENT_QUOTES, 'UTF-8'),
				'LINK' 				=> generate_link('articles&amp;mode=view&amp;id='.$row['articles_id'], array('full' => true, 'sid' => false)),
				'DESCRIPTION' 		=> htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8'),
				'DESCRIPTION_HTML' 	=> htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
				'TIME'				=> date('M d Y H:i:s', $time) .' GMT',
				'AUTHOR'			=> htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8')
			));
		}
		$_CLASS['core_db']->free_result($result);


		if ($last_post_time)
		{
			define('DISPLAY_FEED', true);

			$_CLASS['core_template']->assign('LAST_MODIFIED', date('M d Y H:i:s', $last_post_time) .' GMT');
	
			header('Last-Modified: '.$last_post_time);
		}
	}
}
?>