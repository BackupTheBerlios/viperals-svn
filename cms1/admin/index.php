<?php

if (($cms_news = $_CLASS['core_cache']->get('cms_news')) === false)
{
	$cms_news = array();
	
	load_class($site_file_root.'includes/core_rss.php', 'core_rss');
	
	if ($_CLASS['core_rss']->get_rss('http://viperal.byethost33.com/feed.php?mode=cms&feed=rss2', 3))
	{
		while ($data = $_CLASS['core_rss']->get_rss_data())
		{
			$cms_news[] = $data;
		}
	}
	$_CLASS['core_cache']->put('cms_news', $cms_news, 43200);
}

$_CLASS['core_template']->assign('cms_news', $cms_news);

$sql = 'SELECT user_id, username, user_regdate
	FROM ' . USERS_TABLE . ' 
	WHERE user_type = ' . USER_INACTIVE . ' 
		ORDER BY user_regdate ASC';
	
$result = $_CLASS['core_db']->sql_query($sql);

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$_CLASS['core_template']->assign_vars_array('admin_user', array(
			'user_name'		=> $row['username'],
			'registered'	=> $row['user_regdate'],
			'active_link'	=> '',
	));
}

$_CLASS['core_db']->sql_freeresult($result);

$_CLASS['core_display']->display_head();
$_CLASS['core_template']->display('admin/index.html');
$_CLASS['core_display']->display_footer();

?>