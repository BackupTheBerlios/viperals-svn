<?php
/*********************************************
  CPG-NUKE: Advanced Content Management System
  ********************************************
  Under the GNU General Public License version 2

  Last modification notes:

    $Id: index.php,v 1.28 2004/05/22 22:03:38 djmaze Exp $

*************************************************************/
if (!CPG_NUKE) {
    Header('Location: ../../');
    die();
}
		

Switch (get_variable('mode', 'GET', false))
{
	Case '':
		news_main();
	break;
	
	Case 'view':
		view_story();
	break;
	
	Case 'print':
		view_story(true);
	break;
	
	Case 'send':
		//send_story();

	break;
	
	default:
		news_main();
}
die;

function news_main() {
    global $db, $_CLASS, $prefix, $tipath, $SID, $MAIN_CFG, $currentlang, $user_news, $mainindex, $templates, $pagenum;
  
    require('header.php');
	view_news();
	require('footer.php');

}

function view_news() {
    global $_CLASS, $prefix, $MAIN_CFG, $templates;
    
    $start = get_variable('start', 'GET', false, 'integer');
	
	$_CLASS['template']->caching = true;
		
	if (!$start && $_CLASS['template']->is_cached('modules/News/index.html')) {
		
		$_CLASS['template']->display('modules/News/index.html');
		$_CLASS['template']->caching = false;
		return;
		
	}
	
	if ($start) { $_CLASS['template']->caching = false; }
	
	$limit = ($_CLASS['user']->data['storynum']) ? $_CLASS['user']->data['storynum'] : $MAIN_CFG['global']['storyhome'];
    $sql = 'SELECT s.*, c.title AS cat_title FROM '.$prefix.'_news AS s LEFT JOIN '.$prefix.'_news_cat AS c ON (c.id=s.cat_id) ORDER BY id DESC';
    $result = $_CLASS['db']->sql_query_limit($sql, $limit, $start);
    
    while ($row = $_CLASS['db']->sql_fetchrow($result))
    {
       	if (THEMEPLATE) {
          
			$id = $row['id']+1000;
			
            $_CLASS['template']->assign_vars_array('news', array(
               //'IMG_TOPIC'   => (file_exists("themes/$ThemeSel/images/topics/$topicinfo[topicimage]") ? "themes/$ThemeSel/images/topics/$topicinfo[topicimage]" : "$tipath$topicinfo[topicimage]"),
                'POSTER' 		=> ($row['poster_name']) ? $row['poster_name'] : $_CLASS['user']->lang['ANONYMOUS'],
                'POSTER_LINK'	=> ($row['poster_name'] && $row['poster_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : '',
                'CONTENT'		=> stripslashes($row['intro']),
                'FULL_STORY'	=> (strlen($row['story']) > 5) ? true : false,
                'CONTENT_LINK'  => getlink('News&amp;mode=view&amp;id='.$row['id']),
                'PRINT_LINK' 	=> getlink('News&amp;mode=print&amp;id='.$row['id']),
                'TIME'			=> $_CLASS['user']->format_date($row['time']),
                'TITLE'			=> $row['title'],
                'ID'       => $id,
                'IMAGE'     => 'themes/viperal/images/'.(hideblock($id) ? 'plus.gif' : 'minus.gif'),
                'COLLAPSE'  => hideblock($id) ? 'style="display: none"' : '',
                'TOPIC'  => getlink('News&amp;new_topic='.$row['topic'])
                )
            );
       
       }
    }
    $_CLASS['db']->sql_freeresult($result);

	// fix this up
	$result = $_CLASS['db']->sql_query('SELECT COUNT(*) AS total FROM '.$prefix.'_news limit 1');
	$num_items = ($row = $_CLASS['db']->sql_fetchrow($result)) ? $row['total'] : 0;
	$_CLASS['db']->sql_freeresult($result);
	//$base_url = 'News';
	$base_url = '';
	$per_page = 10;
	$seperator = ' | ';
	$total_pages = ceil($num_items/$per_page);
	$on_page = floor($start / $per_page) + 1;
	$page_string = ($on_page == 1) ? '<strong>1</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($on_page - 2) * $per_page), false, true) . '">' . $_CLASS['user']->lang['PREVIOUS'] . '</a>&nbsp;&nbsp;<a href="' . getlink($base_url, false, true) . '">1</a>';

	if ($total_pages > 5)
	{
		$start_cnt = min(max(1, $on_page - 4), $total_pages - 5);
		$end_cnt = max(min($total_pages, $on_page + 4), 6);
		$page_string .= ($start_cnt > 1) ? ' ... ' : $seperator;
		for($i = $start_cnt + 1; $i < $end_cnt; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page), false, true) . '">' . $i . '</a>';
			if ($i < $end_cnt - 1)
			{
				$page_string .= $seperator;
			}
		}

		$page_string .= ($end_cnt < $total_pages) ? ' ... ' : $seperator;
	}
	else
	{
		$page_string .= $seperator;
		for($i = 2; $i < $total_pages; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page), false, true) . '">' . $i . '</a>';
			if ($i < $total_pages)
			{
				$page_string .= $seperator;
			}
		}
	}

	$page_string .= ($on_page == $total_pages) ? '<strong>' . $total_pages . '</strong>' : '<a href="' . getlink($base_url . '&amp;start=' . (($total_pages - 1) * $per_page), false, true) . '">' . $total_pages . '</a>&nbsp;&nbsp;<a href="' . getlink($base_url . "&amp;start=" . ($on_page * $per_page), false, true) . '">' . $_CLASS['user']->lang['NEXT'] . '</a>';
	
	$_CLASS['template']->assign(array(
		'NEWS_NUMBERING'   => $page_string
		)
	);

    if (THEMEPLATE) {
        $_CLASS['template']->display('modules/News/index.html');
		$_CLASS['template']->caching = false;
    } 
}

function view_story($print = false) {
    global $_CLASS, $prefix, $MAIN_CFG;
    
    $id = get_variable('id', 'GET', false, 'integer');
    
    if (!$id)
    {
		trigger_error('Sorry the new article was not found');
    }
    
    $start	= get_variable('start', 'GET', false, 'integer');

	$limit = ($_CLASS['user']->data['storynum']) ? $_CLASS['user']->data['storynum'] : $MAIN_CFG['global']['storyhome'];
    $sql = 'SELECT s.*, c.title AS cat_title FROM '.$prefix.'_news AS s LEFT JOIN '.$prefix."_news_cat AS c ON (c.id=s.cat_id) WHERE s.id='$id'";
    $result = $_CLASS['db']->sql_query($sql);
    
    if (!$row = $_CLASS['db']->sql_fetchrow($result))
    {
		trigger_error('Sorry the new article was not found');
    }
    
   	if (!$print)
   	{
		require('header.php');
	}
	
	if (THEMEPLATE) {
	  
		$id = $row['id']+1000;
		
		$_CLASS['template']->assign(array(
		   //'IMG_TOPIC'   => (file_exists("themes/$ThemeSel/images/topics/$topicinfo[topicimage]") ? "themes/$ThemeSel/images/topics/$topicinfo[topicimage]" : "$tipath$topicinfo[topicimage]"),
			'NEWS_POSTER' 		=> ($row['poster_name']) ? $row['poster_name'] : $_CLASS['user']->lang['ANONYMOUS'],
			'NEWS_POSTER_LINK'	=> ($row['poster_name'] && $row['poster_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : '',
			'NEWS_INTRO'		=> $row['intro'],
			'NEWS_FULL_STORY'	=> (strlen($row['story']) > 5) ? $row['story'] : false,
			'NEWS_CONTENT_LINK' => getlink('News&amp;mode=view&amp;id='.$row['id']),
			'NEWS_TIME'			=> $_CLASS['user']->format_date($row['time']),
			'NEWS_TITLE'		=> $row['title'],
			'NEWS_ID'       	=> $id,
			'NEWS_IMAGE'     	=> 'themes/viperal/images/'.(hideblock($id) ? 'plus.gif' : 'minus.gif'),
			'NEWS_COLLAPSE' 	=> hideblock($id) ? 'style="display: none"' : '',
			'NEWS_TOPIC' 		=> getlink('News&amp;new_topic='.$row['topic'])
			)
		);
   
	}
      
    $_CLASS['db']->sql_freeresult($result);


	if ($print)
	{
		$_CLASS['template']->display('modules/News/print.html');
		
	} else {
	
		if (THEMEPLATE) {
			$_CLASS['template']->display('modules/News/view.html');
		}
    }
    require('footer.php');
}


?>