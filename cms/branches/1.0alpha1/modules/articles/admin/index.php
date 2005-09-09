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
if (VIPERAL !== 'Admin') 
{
	die;
}

if (!defined('ARTICLES_TABLE'))
{
	define('ARTICLES_TABLE', $prefix.'articles');
}

if (isset($_REQUEST['mode']))
{
	if ($id = get_variable('id', 'GET', false, 'int'))
	{
		switch ($_REQUEST['mode'])
		{
			case 'change':
				$result = $_CLASS['core_db']->query('SELECT articles_status FROM ' . ARTICLES_TABLE . ' WHERE articles_id = '.$id);
				$article = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$article)
				{
					trigger_error('articles_NOT_FOUND');
				}
				
				$status = ($article['articles_status'] == STATUS_ACTIVE) ? STATUS_DISABLED : STATUS_ACTIVE;
				$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . " SET articles_status = $status WHERE articles_id = $id");
			break;
			
			case 'delete':
				$result = $_CLASS['core_db']->query('SELECT articles_order FROM ' . ARTICLES_TABLE . ' WHERE articles_id='.$id);
				$article = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$article)
				{
					trigger_error('articles_NOT_FOUND');
				}
			
				if (display_confirmation())
				{
					$_CLASS['core_db']->query('DELETE from ' . ARTICLES_TABLE . ' where articles_id = '.$id);
					$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = articles_order-1 WHERE articles_order > ' . $article['articles_order']);
				}
			break;

			case 'auth':
				$result = $_CLASS['core_db']->query('SELECT articles_auth FROM ' . ARTICLES_TABLE . ' WHERE articles_id = '.$id);
				$article = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
				
				if (!$article)
				{
					trigger_error('articles_NOT_FOUND');
				}
				
				$article['articles_auth'] = ($article['articles_auth']) ? unserialize($article['articles_auth']) : '';
				
				$_CLASS['core_display']->display_header();

				$auth = $_CLASS['core_auth']->generate_auth_options($article['articles_auth']);

				if ($auth !== false)
				{
					if (is_null($auth))
					{
						$article['articles_auth'] = '';
						$auth = 'null';
					}
					else
					{
						$article['articles_auth'] = $auth;
						$auth = "'".$_CLASS['core_db']->escape(serialize($auth))."'";
					}
			
					$_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . " SET articles_auth = $auth WHERE articles_id = $id");
				}

				$_CLASS['core_display']->display_footer();
			break;

			case 'order':
				$option = get_variable('option', 'GET', false);
			
				if (!$option || !in_array($option, array('down', 'up', 'bottom', 'top')))
				{
					break;
				}

				$result = $_CLASS['core_db']->query('SELECT articles_type, articles_order FROM ' . ARTICLES_TABLE . ' WHERE articles_id= ' . $id);
				$articles = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$articles)
				{
					trigger_error('ARTICLE_NOT_FOUND');
				}

				settype($articles['articles_order'], 'integer');

				switch ($option)
				{
					case 'down':
						$result = $_CLASS['core_db']->query('SELECT MAX(articles_order) as articles_order FROM ' . ARTICLES_TABLE . ' WHERE articles_type='.$articles['articles_type']);
						list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
						$_CLASS['core_db']->free_result($result);
			
						if ($articles['articles_order'] < $max_order)
						{
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = articles_order-1 WHERE articles_type = '.$articles['articles_type'].' AND articles_order='.($articles['articles_order'] + 1));
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = '.($articles['articles_order'] + 1).' WHERE articles_id ='. $id);
			
							$_CLASS['core_cache']->destroy('articless');
						}
					break;
			
					case 'bottom':
						$result = $_CLASS['core_db']->query('SELECT MAX(articles_order) as articles_order FROM ' . ARTICLES_TABLE . ' WHERE articles_type='.$articles['articles_type']);
						list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
						$_CLASS['core_db']->free_result($result);
			
						if ($articles['articles_order'] < $max_order)
						{
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = articles_order-1 WHERE articles_type='.$articles['articles_type'].' AND articles_order > '.$articles['articles_order']);
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = '.$max_order.' WHERE articles_id = '.$id);
			
							$_CLASS['core_cache']->destroy('articless');
						}
					break;
			
					case 'up':
						if ($articles['articles_order'] && $articles['articles_order'] != 1)
						{
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = articles_order+1 WHERE articles_type='.$articles['articles_type'].' AND articles_order = '.($articles['articles_order'] - 1));
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order='.($articles['articles_order'] -1 ).' WHERE articles_id ='. $id);
			
							$_CLASS['core_cache']->destroy('articless');
						}
					break;
			
					case 'top':

						if ($articles['articles_order'] != 1)
						{
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = articles_order+1 WHERE articles_type='.$articles['articles_type'].' AND articles_order < '.$articles['articles_order']);
							$result = $_CLASS['core_db']->query('UPDATE ' . ARTICLES_TABLE . ' SET articles_order = 1 WHERE articles_id = '.$id);
			
							$_CLASS['core_cache']->destroy('articless');
						}
					break;
				}
			break;

			case 'edit':
				articles_edit($id);
			break;
		}
	}

	switch ($_REQUEST['mode'])
	{
		case 'add':
			articles_edit(false);
		break;
		   
		case 'save':
			articles_save($id);
		break;
	}
}

// temp
$result = $_CLASS['core_db']->query('SELECT MAX(articles_order) as articles_order FROM ' . ARTICLES_TABLE);
list($count) = $_CLASS['core_db']->fetch_row_num($result);
$_CLASS['core_db']->free_result($result);

$result = $_CLASS['core_db']->query('SELECT articles_id, articles_title, articles_starts, articles_order, articles_expires, articles_status
										FROM ' . ARTICLES_TABLE . ' ORDER BY articles_order ASC');

while($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$_CLASS['core_template']->assign_vars_array('top_admin_messages', array(
		'ACTIVE'		=> ($row['articles_status']) ? true : false,
		'CHANGE'		=> ($row['articles_status']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE'],

		'AUTH_LINK'		=> generate_link('articles&amp;mode=auth&amp;id='.$row['articles_id'], array('admin' => true)),
		'ACTIVE_LINK'	=> generate_link('articles&amp;mode=change&amp;id='.$row['articles_id'], array('admin' => true)),
		'VIEW_LINK' 	=> generate_link('articles&amp;mode=show&amp;id='.$row['articles_id'], array('admin' => true)),
		'EDIT_LINK'		=> generate_link('articles&amp;mode=edit&amp;id='.$row['articles_id'], array('admin' => true)),
		'DELETE_LINK' 	=> generate_link('articles&amp;mode=delete&amp;id='.$row['articles_id'], array('admin' => true)),

		'EXPIRES'		=> ($row['articles_expires']) ? $_CLASS['core_user']->format_date($row['articles_expires']) : false,
		'STARTS'		=> ($row['articles_starts'] > $_CLASS['core_user']->time) ? $_CLASS['core_user']->format_date($row['articles_starts']) : false,
		'TITLE'			=> $row['articles_title'],

		'ORDER_DOWN' 		=> ($row['articles_order'] < $count),
		'ORDER_UP'			=> ($row['articles_order'] > 1),

		'LINK_ORDER_UP' 	=> generate_link('articles&amp;mode=order&amp;option=up&amp;id='.$row['articles_id'], array('admin' => true)),
		'LINK_ORDER_TOP' 	=> generate_link('articles&amp;mode=order&amp;option=top&amp;id='.$row['articles_id'], array('admin' => true)),
		'LINK_ORDER_DOWN'	=> generate_link('articles&amp;mode=order&amp;option=down&amp;id='.$row['articles_id'], array('admin' => true)),
		'LINK_ORDER_BOTTOM'	=> generate_link('articles&amp;mode=order&amp;option=bottom&amp;id='.$row['articles_id'], array('admin' => true)),
	));
}

$_CLASS['core_template']->assign_array(array(
	'LINK_ADD'			=> generate_link('articles&amp;mode=add', array('admin' => true))
));

$_CLASS['core_db']->free_result($result);

$_CLASS['core_display']->display(false, 'admin/articles/index.html');

script_close();

function articles_edit($id = false, $article = false, $error = false)
{
    global $_CLASS;

	if ($id)
	{
		$result = $_CLASS['core_db']->query('SELECT * FROM ' . ARTICLES_TABLE . ' WHERE articles_id = ' . $id);
		$article = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$article)
		{
			redirect(generate_link('articles', array('admin' => true)));
		}
		
		if (isset($_POST['submit']))
		{
			// need to re-validate data with the db type
			articles_get_data($articles_post, $error);
			$article = array_merge($article, $articles_post);
		}

		unset($articles_post);
	}
	
	$_CLASS['core_template']->assign_array(array(
		'B_TITLE'			=> $article['articles_title'],
		'B_TEXT'			=> $article['articles_text'],
		'B_INTRO'			=> $article['articles_intro'],
		'B_NOTES'			=> $article['articles_notes'],

		'B_ACTIVE'			=> $article['articles_status'],
		'B_EXPIRES'			=> is_numeric($article['articles_expires']) ? $_CLASS['core_user']->format_date($article['articles_expires'], 'M d, Y h:i a') : $article['articles_expires'],
		'B_ERROR'			=> $error,
		'B_STARTS'			=> is_numeric($article['articles_starts']) ? $_CLASS['core_user']->format_date($article['articles_starts'], 'M d, Y h:i a') : $article['articles_starts'],
		'B_DELETE_LINK'		=> ($id) ? generate_link('articles&amp;mode=delete&amp;id='.$id, array('admin' => true)) : false,
		'B_ACTION'			=> generate_link('articles&amp;mode=save'.(($id) ? '&amp;id='.$id : ''), array('admin' => true)),
		'B_CURRENT_TIME'=> $_CLASS['core_user']->format_date($_CLASS['core_user']->time)
	));
	
	$_CLASS['core_template']->display('admin/articles/edit.html');
}

function articles_get_data(&$data, &$error)
{
	global $_CLASS;
	
	$error = '';
	$data = array();

	if (!isset($_POST['submit']))
	{
		return;
	}

	$data['articles_title'] = get_variable('title', 'POST', '');
	$data['articles_text'] = get_variable('text', 'POST', '');

	foreach ($data as $field => $value)
	{
		if (!$value)
		{
			$error .= $_CLASS['core_user']->get_lang('ERROR_'.$field).'<br />';
		}
	}

	$data['articles_intro'] = get_variable('intro', 'POST', '');
	$data['articles_notes'] = get_variable('notes', 'POST', '');

	$data['articles_status']	= (get_variable('active', 'POST', STATUS_DISABLED, 'int') === STATUS_DISABLED) ? STATUS_DISABLED : STATUS_ACTIVE;
	$data['articles_expires']	= get_variable('expires', 'POST', 0);
	$data['articles_starts']	= get_variable('starts', 'POST', '');

	$start = $expires = '';

	if ($data['articles_starts'])
	{
		$start = strtotime($data['articles_starts']);

		if (!$start || $start == -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_START_TIME'].'<br />';
		}
	}

	if ($data['articles_expires'])
	{
		$expires = strtotime($data['articles_expires']);

		if (!$expires || $expires == -1)
		{
			$error .= $_CLASS['core_user']->lang['ERROR_END_TIME'].'<br />';
		}
	}
	
	if (!$error)
	{
		$data['articles_starts'] = ($start) ? $_CLASS['core_user']->time_convert($start, 'gmt') : 0;
		$data['articles_expires'] = ($expires) ? $_CLASS['core_user']->time_convert($expires, 'gmt') : 0;
	}
}

function articles_save($id = false)
{
    global $_CLASS;
    
	if ($id)
	{
		$result = $_CLASS['core_db']->query('SELECT articles_id FROM ' . ARTICLES_TABLE . ' WHERE articles_id = '. $id);
		$articles = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		if (!$articles)
		{
			redirect(generate_link('articles', array('admin' => true)));
		}

		// need to validate data with the db type
		articles_get_data($data, $error);

		if ($error)
		{
			return articles_edit($id, $data, $error);
		}

		$sql = 'UPDATE ' . ARTICLES_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE articles_id = '.$id;

		$_CLASS['core_db']->query($sql);
	}
	else
	{
		articles_get_data($data, $error);

		if ($error)
		{
			return articles_edit(false, $data, $error);
		}

		$result = $_CLASS['core_db']->query('SELECT MAX(articles_order) as articles_order FROM ' . ARTICLES_TABLE);
		list($max_order) = $_CLASS['core_db']->fetch_row_num($result);
		$_CLASS['core_db']->free_result($result);

		$data['articles_order'] = (int) $max_order + 1;
		$data['articles_posted'] = (int) $_CLASS['core_user']->time;
		$data['articles_type'] = 1;

		$data['poster_id'] = $_CLASS['core_user']->data['user_id'];
		$data['poster_ip'] = $_CLASS['core_user']->ip;
		$data['poster_name'] = $_CLASS['core_user']->data['username'];

		$_CLASS['core_db']->query('INSERT INTO ' . ARTICLES_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data));
	}

	$_CLASS['core_display']->meta_refresh('3', generate_link('articles', array('admin' => true)));
	trigger_error(sprintf($_CLASS['core_user']->lang['SAVED'], generate_link('articles', array('admin' => true))));	
}

?>