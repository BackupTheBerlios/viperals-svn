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
    header('location: ../../');
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
    global $_CLASS, $prefix, $MAIN_CFG, $templates;
    
    $start = get_variable('start', 'GET', false, 'integer');
	
	/*$_CLASS['template']->caching = true;
		
	if (!$start && $_CLASS['template']->is_cached('modules/News/index.html')) {
		
		$_CLASS['template']->display('modules/News/index.html');
		$_CLASS['template']->caching = false;
		$_CLASS['display']->display_footer();
	}*/
	
	if ($start) { $_CLASS['template']->caching = false; }
	
	$limit = ($_CLASS['user']->data['storynum']) ? $_CLASS['user']->data['storynum'] : $MAIN_CFG['global']['storyhome'];
    $sql = 'SELECT * FROM '.$prefix.'_news WHERE status=1 ORDER BY time DESC';
    $result = $_CLASS['db']->sql_query_limit($sql, $limit, $start);
    
    while ($row = $_CLASS['db']->sql_fetchrow($result))
    {
       	if (THEMEPLATE)
       	{
          
			$id = $row['id'] + 1000;
			
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
                'ID'      		=> $id,
                'IMAGE'    		=> 'themes/viperal/images/'.(hideblock($id) ? 'plus.gif' : 'minus.gif'),
                'COLLAPSE'  	=> hideblock($id) ? 'style="display: none"' : '',
                'TOPIC'  		=> getlink('News&amp;new_topic='.$row['topic'])
                )
            );
       
       }
    }
    $_CLASS['db']->sql_freeresult($result);

	$result = $_CLASS['db']->sql_query('SELECT COUNT(*) AS total FROM '.$prefix.'_news');
	$row = $_CLASS['db']->sql_fetchrow($result);
	$_CLASS['db']->sql_freeresult($result);

	$base_url = 'News';
	$_CLASS['user']->img['pagination_sep'] = ' | ';
	
	$pagination = generate_pagination($base_url, $row['total'], $limit, $start, true, 'NEWS_');
	$_CLASS['template']->assign('NEWS_PAGINATION', $pagination);

    $_CLASS['display']->display_head();

    if (THEMEPLATE)
    {
        $_CLASS['template']->display('modules/News/index.html');
		//$_CLASS['template']->caching = false;
    } 
    
    $_CLASS['display']->display_footer();
}

function view_story($print = false)
{
    global $_CLASS, $prefix, $MAIN_CFG;
    
    $id = get_variable('id', 'GET', false, 'integer');
    
    if (!$id)
    {
		trigger_error('Sorry the new article was not found');
    }
    
    $start	= get_variable('start', 'GET', false, 'integer');

	$limit = ($_CLASS['user']->data['storynum']) ? $_CLASS['user']->data['storynum'] : $MAIN_CFG['global']['storyhome'];
    $sql = 'SELECT * FROM '.$prefix."_news WHERE status=1 AND id='$id'";
    $result = $_CLASS['db']->sql_query($sql);
    
    if (!$row = $_CLASS['db']->sql_fetchrow($result))
    {
		trigger_error('Sorry the new article was not found');
    }
    
   	if (!$print)
   	{
		$_CLASS['display']->display_head('Nghdfghgd');
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
			'NEWS_COLLAPSE' 	=> hideblock($id) ? '' : '',
			'NEWS_TOPIC' 		=> getlink('News&amp;new_topic='.$row['topic'])
			)
		);
   
	}
      
    $_CLASS['db']->sql_freeresult($result);


	if ($print)
	{
		$_CLASS['template']->display('modules/News/print.html');
		
	} else {
	
		if (THEMEPLATE)
		{
			$_CLASS['template']->display('modules/News/view.html');
		}
    }
    $_CLASS['display']->display_footer();
}


?>