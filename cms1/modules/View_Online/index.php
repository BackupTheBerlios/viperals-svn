<?php
// -------------------------------------------------------------
//
// $Id: viewonline.php,v 1.88 2004/08/01 16:29:12 acydburn Exp $
//
// FILENAME  : viewonline.php
// STARTED   : Sat Dec 16, 2000
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
// Need to do some work here

if (!defined('VIPERAL'))
{
    die();
}

require($site_file_root.'includes/forums/functions.php');
load_class($site_file_root.'includes/forums/auth.php', 'auth');
$_CLASS['auth']->acl($_CLASS['core_user']->data);

// Get and set some variables
$mode		= request_var('mode', '');
$session_id	= request_var('s', '');
$start		= request_var('start', 0);
$sort_key	= request_var('sk', 'b');
$sort_dir	= request_var('sd', 'd');
$show_guests= ($config['load_online_guests']) ? request_var('sg', 0) : 0;

$sort_key_text = array('a' => $_CLASS['core_user']->lang['SORT_USERNAME'], 'b' => $_CLASS['core_user']->lang['SORT_LOCATION'], 'c' => $_CLASS['core_user']->lang['SORT_JOINED']);
$sort_key_sql = array('a' => 'u.username', 'b' => 's.session_time', 'c' => 's.session_page');

// Sorting and order
$order_by = $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

// Whois requested
if ($mode == 'whois')
{
	require($site_file_root.'includes/forums/functions_user.php');

	$sql = 'SELECT u.user_id, u.username, u.user_type, s.session_ip
	FROM ' . USERS_TABLE . ' u, ' . SESSIONS_TABLE . " s
	WHERE s.session_id = '" . $_CLASS['core_db']->sql_escape($session_id) . "'
		AND	u.user_id = s.session_user_id";
	$result = $_CLASS['core_db']->sql_query($sql);

	if ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$whois = user_ipwhois($row['session_ip']);

		$whois = preg_replace('#(\s+?)([\w\-\._\+]+?@[\w\-\.]+?)(\s+?)#s', '\1<a href="mailto:\2">\2</a>\3', $whois);
		$whois = preg_replace('#(\s+?)(http://.*?)(\s+?)#s', '\1<a href="\2" target="_blank">\2</a>\3', $whois);

		$_CLASS['core_template']->assign(array(
			'L_WHOIS' => $_CLASS['core_user']->lang['WHOIS'],
			'WHOIS'   => trim($whois))
		);
	}
	$_CLASS['core_db']->sql_freeresult($result);

	$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['WHO_IS_ONLINE']);
	
	page_header();
	make_jumpbox('viewforum.php');
		
	$_CLASS['core_template']->display('modules/View_Online/viewonline_whois.html');

	$_CLASS['core_display']->display_footer();
}

// Forum info
$sql = 'SELECT forum_id, forum_name, parent_id, forum_type, left_id, right_id
	FROM ' . FORUMS_TABLE . '
	ORDER BY left_id ASC';
$result = $_CLASS['core_db']->sql_query($sql, 600);

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$forum_data[$row['forum_id']] = $row['forum_name'];
}
$_CLASS['core_db']->sql_freeresult($result);

$sql = 'SELECT COUNT(DISTINCT session_ip) as num_guests FROM ' . SESSIONS_TABLE . '
	WHERE session_user_id = ' . ANONYMOUS . '
		AND session_time >= ' . (time() - ($config['load_online_time'] * 60));
		
$result = $_CLASS['core_db']->sql_query($sql);
$num_guests = (int) $_CLASS['core_db']->sql_fetchfield('num_guests', 0, $result);
$_CLASS['core_db']->sql_freeresult($result);

$sql = 'SELECT COUNT(*) as num_users FROM ' . SESSIONS_TABLE . '
	WHERE session_user_id <> ' . ANONYMOUS . 
	(($_CLASS['auth']->acl_get('u_viewonline')) ? '' : ' AND session_viewonline = 1 ') .'
		AND session_time >= ' . (time() - ($config['load_online_time'] * 60));
		
$result = $_CLASS['core_db']->sql_query($sql);
$num_users = (int) $_CLASS['core_db']->sql_fetchfield('num_users', 0, $result);
$_CLASS['core_db']->sql_freeresult($result);

$num_sessions = $num_users + $num_guests;

// Get user list
$sql = 'SELECT u.user_id, u.username, u.user_type, u.user_allow_viewonline, u.user_colour, s.session_id, s.session_time, s.session_url, s.session_page, s.session_ip, s.session_viewonline
	FROM ' . USERS_TABLE . ' u, ' . SESSIONS_TABLE . ' s
	WHERE u.user_id = s.session_user_id
		AND s.session_time >= ' . (time() - ($config['load_online_time'] * 60)) .
		(($_CLASS['auth']->acl_get('u_viewonline')) ? '' : ' AND session_viewonline = 1 ') .
		((!$show_guests) ? ' AND s.session_user_id <> ' . ANONYMOUS : '') . '
	ORDER BY ' . $order_by;
$result = $_CLASS['core_db']->sql_query_limit($sql, $config['topics_per_page'], $start);

$prev_id = $prev_ip = array();
$logged_visible_online = $logged_hidden_online = 0;

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	if ($row['user_id'] != ANONYMOUS && !isset($prev_id[$row['user_id']]))
	{
		$view_online = false;
		
		if ($row['user_colour'])
		{
			$row['username'] = '<b style="color:#' . $row['user_colour'] . '">' . $row['username'] . '</b>';
		}

		if (!$row['user_allow_viewonline'] || !$row['session_viewonline'])
		{
			$row['username'] = '<i>' . $row['username'] . '</i>';
		}

		$prev_id[$row['user_id']] = 1;
	}
	else if ($show_guests && $row['user_id'] == ANONYMOUS && !isset($prev_ip[$row['session_ip']]))
	{
		$prev_ip[$row['session_ip']] = 1;
	}
	else
	{
		continue;
	}

	$location = ($row['session_page']) ? $row['session_page'] : 'Home';
	$location_url = generate_link($row['session_url']);

	$location = eregi_replace('_',' ', $location);
	$location_url = htmlentities(html_entity_decode($location_url));
	
	$_CLASS['core_template']->assign_vars_array('user_row', array(
		'USERNAME'			=> $row['username'],
		'LASTUPDATE' 		=> $_CLASS['core_user']->format_date($row['session_time']),
		'FORUM_LOCATION'	=> $location, 
		'USER_IP'			=> ($_CLASS['auth']->acl_get('a_')) ? (($mode == 'lookup' && $session_id == $row['session_id']) ? gethostbyaddr($row['session_ip']) : $row['session_ip']) : '', 

		'U_USER_PROFILE'	=> (($row['user_type'] == USER_NORMAL || $row['user_type'] == USER_FOUNDER) && $row['user_id'] != ANONYMOUS) ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) : '',
		'U_USER_IP'			=> generate_link('View_Online' . (($mode != 'lookup' || $row['session_id'] != $session_id) ? '&amp;s=' . $row['session_id'] : '') . "&amp;mode=lookup&amp;sg=$show_guests&amp;start=$start&amp;sk=$sort_key&amp;sd=$sort_dir"),
		'U_WHOIS'			=> generate_link('View_Online&amp;mode=whois&amp;s=' . $row['session_id']),
		'U_FORUM_LOCATION'	=> $location_url,
		'S_GUEST'			=> ($row['user_id'] == ANONYMOUS) ? true : false,
		'S_USER_TYPE'		=> $row['user_type'])
	);
}

$_CLASS['core_db']->sql_freeresult($result);
unset($prev_id, $prev_ip);

// Generate reg/hidden/guest online text
$vars_online = array(
	'REG'	=> array('num_users', 'l_r_user_s'),
	//'HIDDEN'=> array('logged_hidden_online', 'l_h_user_s'),
	'GUEST' => array('num_guests', 'l_g_user_s')
);

foreach ($vars_online as $l_prefix => $var_ary)
{
	switch ($$var_ary[0])
	{
		case 0:
			$$var_ary[1] = $_CLASS['core_user']->lang[$l_prefix . '_USERS_ZERO_ONLINE'];
			break;

		case 1:
			$$var_ary[1] = $_CLASS['core_user']->lang[$l_prefix . '_USER_ONLINE'];
			break;

		default:
			$$var_ary[1] = sprintf($_CLASS['core_user']->lang[$l_prefix . '_USERS_ONLINE'], $$var_ary[0]);
			break;
	}
}
unset($vars_online);

$pagination = generate_pagination("View_Online&amp;sg=$show_guests&amp;sk=$sort_key&amp;sd=$sort_dir", $num_sessions, $config['topics_per_page'], $start);

// Grab group details for legend display
$sql = 'SELECT group_id, group_name, group_colour, group_type
	FROM ' . GROUPS_TABLE . '
	WHERE group_legend = 1';

$result = $_CLASS['core_db']->sql_query($sql);

$legend = '';
while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$legend .= (($legend != '') ? ', ' : '') . '<a style="color:#' . $row['group_colour'] . '" href="' . generate_link('Members_List&amp;mode=group&amp;g=' . $row['group_id']) . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $_CLASS['core_user']->lang['G_' . $row['group_name']] : $row['group_name']) . '</a>';
}
$_CLASS['core_db']->sql_freeresult($result);

// Send data to template
$_CLASS['core_template']->assign(array(
	'TOTAL_REGISTERED_USERS_ONLINE'	=> $l_r_user_s,
	//'TOTAL_REGISTERED_USERS_ONLINE'	=> $l_r_user_s .$l_h_user_s,
	'TOTAL_GUEST_USERS_ONLINE'		=> $l_g_user_s,

	'LEGEND'				=> $legend, 
	'PAGINATION'			=> $pagination,
	'PAGE_NUMBER'			=> on_page($num_sessions, $config['topics_per_page'], $start),
	
	'U_SORT_USERNAME'		=> generate_link('View_Online&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a')),
	'U_SORT_UPDATED'		=> generate_link('View_Online&amp;sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a')),
	'U_SORT_LOCATION'		=> generate_link('View_Online&amp;sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a')),

	'U_SWITCH_GUEST_DISPLAY'=> generate_link('View_Online&amp;sg=' . ((int) !$show_guests)),
	'L_SWITCH_GUEST_DISPLAY'=> ($show_guests) ? $_CLASS['core_user']->lang['HIDE_GUESTS'] : $_CLASS['core_user']->lang['DISPLAY_GUESTS'],
	'S_SWITCH_GUEST_DISPLAY'=> ($config['load_online_guests']) ? true : false)
	
);

// We do not need to load the who is online box here. ;)
$config['load_online'] = false;

$_CLASS['core_display']->display_head($_CLASS['core_user']->lang['WHO_IS_ONLINE']);

page_header();

$_CLASS['core_template']->display('modules/View_Online/viewonline_body.html');

$_CLASS['core_display']->display_footer();

?>