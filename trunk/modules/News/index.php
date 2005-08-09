<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
// Add sticky
// Add send
// Add order "id"
// User new per page option, maybe add a limit to how many they can display

if (!defined('VIPERAL'))
{
    die();
}	

Switch (get_variable('mode', 'GET', false))
{

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

function news_main()
{
    global $_CLASS, $prefix, $_CORE_CONFIG, $templates;
    
    $start = get_variable('start', 'GET', false, 'integer');
	
	if ($start)
	{
		$_CLASS['core_template']->caching = false;
	}
	
	$limit = $_CORE_CONFIG['global']['storyhome'];
    $sql = 'SELECT * FROM '.$prefix.'_news WHERE status=1 ORDER BY time DESC';
    $result = $_CLASS['core_db']->sql_query_limit($sql, $limit, $start);
    
    while ($row = $_CLASS['core_db']->sql_fetchrow($result))
    {
		$id = $row['id'] + 1000;
		
		$_CLASS['core_template']->assign_vars_array('news', array(
			'POSTER' 		=> ($row['poster_name']) ? $row['poster_name'] : $_CLASS['core_user']->lang['ANONYMOUS'],
			'POSTER_LINK'	=> ($row['poster_name'] && $row['poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : '',
			'CONTENT'		=> $row['intro'],
			'FULL_STORY'	=> (strlen($row['story']) > 5) ? true : false,
			'CONTENT_LINK'  => generate_link('News&amp;mode=view&amp;id='.$row['id']),
			'PRINT_LINK' 	=> generate_link('News&amp;mode=print&amp;id='.$row['id']),
			'TIME'			=> $_CLASS['core_user']->format_date($row['time']),
			'TITLE'			=> $row['title'],
			'ID'      		=> $id,
			'COLLAPSE'  	=> hideblock($id) ? true : false,
			'TOPIC'  		=> generate_link('News&amp;new_topic='.$row['topic'])
			)
		);
    }
    
    $_CLASS['core_db']->sql_freeresult($result);

	$result = $_CLASS['core_db']->sql_query('SELECT COUNT(*) AS total FROM '.$prefix.'_news');
	$row = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	$pagination = generate_pagination('News', $row['total'], $limit, $start, true, 'NEWS_');
	$_CLASS['core_template']->assign('NEWS_PAGINATION', $pagination);

    $_CLASS['core_display']->display_header();

    $_CLASS['core_template']->display('modules/News/index.html');

    $_CLASS['core_display']->display_footer();
}

function view_story($print = false)
{
    global $_CLASS, $prefix, $_CORE_CONFIG;
    
    $id = get_variable('id', 'GET', false, 'integer');
    
    if (!$id)
    {
		trigger_error('Sorry the new article was not found');
    }
    
    $start	= get_variable('start', 'GET', false, 'integer');

	$limit = ($_CLASS['core_user']->data['storynum']) ? $_CLASS['core_user']->data['storynum'] : $_CORE_CONFIG['global']['storyhome'];
    $sql = 'SELECT * FROM '.$prefix."_news WHERE status=1 AND id='$id'";
    $result = $_CLASS['core_db']->sql_query($sql);
    
    if (!$row = $_CLASS['core_db']->sql_fetchrow($result))
    {
		trigger_error('Sorry the new article was not found');
    }
    
   	if (!$print)
   	{
		$_CLASS['core_display']->display_head('Nghdfghgd');
	}

	$id = $row['id']+1000;
	
	$_CLASS['core_template']->assign(array(
	   //'IMG_TOPIC'   => (file_exists("themes/$ThemeSel/images/topics/$topicinfo[topicimage]") ? "themes/$ThemeSel/images/topics/$topicinfo[topicimage]" : "$tipath$topicinfo[topicimage]"),
		'NEWS_POSTER' 		=> ($row['poster_name']) ? $row['poster_name'] : $_CLASS['core_user']->lang['ANONYMOUS'],
		'NEWS_POSTER_LINK'	=> ($row['poster_name'] && $row['poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : '',
		'NEWS_INTRO'		=> $row['intro'],
		'NEWS_FULL_STORY'	=> (strlen($row['story']) > 5) ? $row['story'] : false,
		'NEWS_CONTENT_LINK' => generate_link('News&amp;mode=view&amp;id='.$row['id']),
		'NEWS_TIME'			=> $_CLASS['core_user']->format_date($row['time']),
		'NEWS_TITLE'		=> $row['title'],
		'NEWS_ID'       	=> $id,
		'NEWS_COLLAPSE' 	=> hideblock($id) ? '' : '',
		'NEWS_TOPIC' 		=> generate_link('News&amp;new_topic='.$row['topic'])
		)
	);
	
      
    $_CLASS['core_db']->sql_freeresult($result);


	if ($print)
	{
		$_CLASS['core_template']->display('modules/News/print.html');	
	}
	else
	{
		$_CLASS['core_template']->display('modules/News/view.html');
    }
    
    $_CLASS['core_display']->display_footer();
}

?>