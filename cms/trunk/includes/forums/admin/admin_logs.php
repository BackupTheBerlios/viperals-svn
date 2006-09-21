<?php
// -------------------------------------------------------------
//
// $Id: admin_viewlogs.php,v 1.13 2004/11/06 14:11:47 acydburn Exp $
//
// FILENAME  : admin_viewlogs.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

if (!$_CLASS['forums_auth']->acl_get('a_'))
{
	trigger_error('NO_ADMIN');
}

$_CLASS['core_user']->add_lang('mcp', 'forums');

// Set up general vars
$action		= request_var('action', '');
$mode		= request_var('mode', '');
$forum_id	= request_var('f', 0);
$start		= request_var('start', 0);
$deletemark = isset($_POST['delmarked']) ? true : false;
$deleteall	= isset($_POST['delall']) ? true : false;
$marked		= request_var('mark', array(0));

$u_action = 'forums&file=admin_logs&mode='.$mode;

// Sort keys
$sort_days	= request_var('st', 0);
$sort_key	= request_var('sk', 't');
$sort_dir	= request_var('sd', 'd');

$log_type = constant('LOG_' . strtoupper($mode));

// Delete entries if requested and able
if (($deletemark || $deleteall) && $_CLASS['forums_auth']->acl_get('a_clearlogs'))
{
	$where_sql = '';

	if ($deletemark && sizeof($marked))
	{
		$sql_in = array();
		foreach ($marked as $mark)
		{
			$sql_in[] = $mark;
		}
		$where_sql = ' AND log_id IN (' . implode(', ', $sql_in) . ')';
		unset($sql_in);
	}

	if ($where_sql || $deleteall)
	{
		$sql = 'DELETE FROM ' . LOG_TABLE . "
			WHERE log_type = $log_type
			$where_sql";
		$_CLASS['core_db']->query($sql);

		add_log('admin', 'LOG_CLEAR_' . strtoupper($mode));
	}
}

// Sorting
$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_ENTRIES'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 365 => $_CLASS['core_user']->lang['1_YEAR']);
$sort_by_text = array('u' => $_CLASS['core_user']->lang['SORT_USERNAME'], 't' => $_CLASS['core_user']->lang['SORT_DATE'], 'i' => $_CLASS['core_user']->lang['SORT_IP'], 'o' => $_CLASS['core_user']->lang['SORT_ACTION']);
$sort_by_sql = array('u' => 'u.username', 't' => 'l.log_time', 'i' => 'l.log_ip', 'o' => 'l.log_operation');

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

// Define where and sort sql for use in displaying logs
$sql_where = ($sort_days) ? ($_CLASS['core_user']->time - ($sort_days * 86400)) : 0;
$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

$l_title = $_CLASS['core_user']->lang['ACP_' . strtoupper($mode) . '_LOGS'];
$l_title_explain = $_CLASS['core_user']->lang['ACP_' . strtoupper($mode) . '_LOGS_EXPLAIN'];

// Define forum list if we're looking @ mod logs
if ($mode == 'mod')
{
	$forum_box = '<option value="0">' . $_CLASS['core_user']->lang['ALL_FORUMS'] . '</option>' . make_forum_select($forum_id);
	
	$_CLASS['core_template']->assign_array(array(
		'S_SHOW_FORUMS'			=> true,
		'S_FORUM_BOX'			=> $forum_box
	));
}

// Grab log data
$log_data = array();
$log_count = 0;
view_log($mode, $log_data, $log_count, $config['topics_per_page'], $start, $forum_id, 0, 0, $sql_where, $sql_sort);

$_CLASS['core_template']->assign_array(array(
	'L_TITLE'		=> $l_title,
	'L_EXPLAIN'		=> $l_title_explain,
	'U_ACTION'		=> generate_link($u_action, array('admin' => true)),

	'S_ON_PAGE'		=> on_page($log_count, $config['topics_per_page'], $start),
	'PAGINATION'	=> generate_pagination($u_action . "&amp;$u_sort_param", $log_count, $config['topics_per_page'], $start, true),

	'S_LIMIT_DAYS'	=> $s_limit_days,
	'S_SORT_KEY'	=> $s_sort_key,
	'S_SORT_DIR'	=> $s_sort_dir,
	'S_CLEARLOGS'	=> true,//$_CLASS['forums_auth']->acl_get('a_clearlogs'),
));

foreach ($log_data as $row)
{
	$data = array();
		
	$checks = array('viewtopic', 'viewlogs', 'viewforum');
	foreach ($checks as $check)
	{
		if (isset($row[$check]) && $row[$check])
		{
			$data[] = '<a href="' . $row[$check] . '">' . $_CLASS['core_user']->lang['LOGVIEW_' . strtoupper($check)] . '</a>';
		}
	}

	$_CLASS['core_template']->assign_vars_array('log', array(
		'USERNAME'			=> $row['username'],
		'REPORTEE_USERNAME'	=> ($row['reportee_username'] && $row['user_id'] != $row['reportee_id']) ? $row['reportee_username'] : '',

		'IP'				=> $row['ip'],
		'DATE'				=> $_CLASS['core_user']->format_date($row['time']),
		'ACTION'			=> $row['action'],
		'DATA'				=> (sizeof($data)) ? implode(' | ', $data) : '',
		'ID'				=> $row['id'],
	));
}

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($l_title), 'modules/forums/admin/acp_logs.html');

?>