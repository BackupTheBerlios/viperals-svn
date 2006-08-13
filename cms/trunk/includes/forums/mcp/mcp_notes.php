<?php
/** 
*
* @package mcp
* @version $Id: mcp_notes.php,v 1.26 2006/07/29 11:35:32 grahamje Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/



global $_CLASS;

$mode = get_variable('mode', 'REQUEST');
$action = (isset($_REQUEST['action']) && is_array($_REQUEST['action'])) ? get_variable('action', 'REQUEST', false, 'array') : get_variable('action', 'REQUEST');

if (is_array($action))
{
	list($action, ) = each($action);
}
//$this->page_title = 'MCP_NOTES';

switch ($mode)
{
	case 'user_notes':
		//$_CLASS['core_user']->add_lang('acp/common.php');

		mcp_notes_user_view($action);
		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP_NOTES'), 'modules/forums/mcp_notes_user.html');
	break;
	
	//case 'front':
	default:
		$_CLASS['core_template']->assign_array(array(
			'U_FIND_MEMBER'		=> generate_link('members_list&amp;mode=search_user&amp;form=mcp&amp;field=username'),
			'U_POST_ACTION'		=> generate_link('forums&amp;file=mcp&amp;i=notes&amp;mode=user_notes'),

			'L_TITLE'			=> $_CLASS['core_user']->get_lang('MCP_NOTES')
		));

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang('MCP_NOTES'), 'modules/forums/mcp_notes_front.html');
	break;
}

/**
* Display user notes
*/
function mcp_notes_user_view($action)
{
	global $_CLASS, $_CORE_CONFIG, $config;

	$user_id = request_var('u', 0);
	$username = request_var('username', '');
	$start = request_var('start', 0);
	$st	= request_var('st', 0);
	$sk	= request_var('sk', 'b');
	$sd	= request_var('sd', 'd');

	$url = 'forums&amp;file=mcp&amp;i=notes&mode=user_notes';

	$sql_where = ($user_id) ? "user_id = $user_id" : "username = '" . $db->sql_escape($username) . "'";

	$sql = 'SELECT *
		FROM ' . CORE_USERS_TABLE . "
		WHERE $sql_where";
	$result = $_CLASS['core_db']->query($sql);
	$userrow = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$userrow)
	{
		trigger_error('NO_USER');
	}

	$user_id = $userrow['user_id'];

	$deletemark = ($action === 'del_marked');
	$deleteall	= ($action === 'del_all');
	$marked		= get_variable('marknote', 'REQUEST', false, 'array:int');
	$usernote	= request_var('usernote', '', true);

	// Handle any actions
	if (($deletemark || $deleteall) && $_CLASS['forums_auth']->acl_get('a_clearlogs'))
	{
		$where_sql = '';
		if ($deletemark && $marked)
		{
			/*$sql_in = array();
			foreach ($marked as $mark)
			{
				$sql_in[] = $mark;
			}*/
			$where_sql = ' AND log_id IN (' . implode(', ', $marked) . ')';
			/*unset($sql_in);*/
		}

		if ($where_sql || $deleteall)
		{
			$sql = 'DELETE FROM ' . FORUMS_LOG_TABLE . '
				WHERE log_type = ' . LOG_USERS . " 
					AND reportee_id = $user_id
					$where_sql";
			$_CLASS['core_db']->query($sql);

			add_log('admin', 'LOG_CLEAR_USER', $userrow['username']);

			$msg = ($deletemark) ? 'MARKED_NOTES_DELETED' : 'ALL_NOTES_DELETED';
			$redirect = generate_link($url . '&amp;u=' . $user_id);

			$_CLASS['core_display']->meta_refresh(3, $redirect);
			trigger_error($_CLASS['core_user']->lang[$msg] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
		}
	}

	if ($usernote && $action === 'add_feedback')
	{
		add_log('admin', 'LOG_USER_FEEDBACK', $userrow['username']);
		add_log('user', $user_id, 'LOG_USER_GENERAL', $usernote);

		$redirect = generate_link($url . '&amp;u=' . $user_id);
		$_CLASS['core_display']->meta_refresh(3, $redirect);
		trigger_error($_CLASS['core_user']->lang['USER_FEEDBACK_ADDED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
	}

	// Generate the appropriate user information for the user we are looking at
	$rank_title = $rank_img = '';
	//get_user_rank($userrow['user_rank'], $userrow['user_posts'], $rank_title, $rank_img);

	$avatar_img = '';
	if (!empty($userrow['user_avatar']))
	{
		switch ($userrow['user_avatar_type'])
		{
			case AVATAR_UPLOAD:
				$avatar_img = $_CORE_CONFIG['global']['path_avatar_upload'] . '/';
			break;

			case AVATAR_GALLERY:
				$avatar_img = $_CORE_CONFIG['global']['path_avatar_gallery'] . '/';
			break;
		}
		$avatar_img .= $userrow['user_avatar'];

		$avatar_img = '<img src="' . $avatar_img . '" width="' . $userrow['user_avatar_width'] . '" height="' . $userrow['user_avatar_height'] . '" alt="" />';
	}

	$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_ENTRIES'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 365 => $_CLASS['core_user']->lang['1_YEAR']);
	$sort_by_text = array('a' => $_CLASS['core_user']->lang['SORT_USERNAME'], 'b' => $_CLASS['core_user']->lang['SORT_DATE'], 'c' => $_CLASS['core_user']->lang['SORT_IP'], 'd' => $_CLASS['core_user']->lang['SORT_ACTION']);
	$sort_by_sql = array('a' => 'l.username', 'b' => 'l.log_time', 'c' => 'l.log_ip', 'd' => 'l.log_operation');

	$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
	gen_sort_selects($limit_days, $sort_by_text, $st, $sk, $sd, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

	// Define where and sort sql for use in displaying logs
	$sql_where = ($st) ? ($_CLASS['core_user']->time - ($st * 86400)) : 0;
	$sql_sort = $sort_by_sql[$sk] . ' ' . (($sd == 'd') ? 'DESC' : 'ASC');

	$log_data = array();
	$log_count = 0;
	view_log('user', $log_data, $log_count, $config['posts_per_page'], $start, 0, 0, $user_id, $sql_where, $sql_sort);

	$_CLASS['core_template']->assign('S_USER_NOTES', false);

	if ($log_count)
	{
		$_CLASS['core_template']->assign('S_USER_NOTES', true);

		foreach ($log_data as $row)
		{
			$_CLASS['core_template']->assign_vars_array('usernotes', array(
				'REPORT_BY'		=> $row['username'],
				'REPORT_AT'		=> $_CLASS['core_user']->format_date($row['time']),
				'ACTION'		=> $row['action'],
				'IP'			=> $row['ip'],
				'ID'			=> $row['id']
			));
		}
	}

	$pagination = generate_pagination($url . "&amp;u=$user_id&amp;st=$st&amp;sk=$sk&amp;sd=$sd", $log_count, $config['posts_per_page'], $start);

	$_CLASS['core_template']->assign_array(array(
		'U_POST_ACTION'			=> generate_link($url . '&amp;u=' . $user_id),
		'S_CLEAR_ALLOWED'		=> $_CLASS['forums_auth']->acl_get('a_clearlogs'),
		'S_SELECT_SORT_DIR'		=> $s_sort_dir,
		'S_SELECT_SORT_KEY'		=> $s_sort_key,
		'S_SELECT_SORT_DAYS'	=> $s_limit_days,

		'L_TITLE'			=> $_CLASS['core_user']->get_lang('MCP_NOTES_USER'),

		'PAGE_NUMBER'		=> on_page($log_count, $config['posts_per_page'], $start),
		'PAGINATION'		=> $pagination['formated'],
		'PAGINATION_ARRAY'	=> $pagination['array'],
		'TOTAL_REPORTS'		=> ($log_count == 1) ? $_CLASS['core_user']->get_lang('LIST_REPORT') : sprintf($_CLASS['core_user']->get_lang('LIST_REPORTS'), $log_count),

		'USERNAME'			=> $userrow['username'],
		'USER_COLOR'		=> (!empty($userrow['user_colour'])) ? $userrow['user_colour'] : '',
		'RANK_TITLE'		=> $rank_title,
		'JOINED'			=> $_CLASS['core_user']->format_date($userrow['user_reg_date']),
		'POSTS'				=> ($userrow['user_posts']) ? $userrow['user_posts'] : 0,
		'WARNINGS'			=> (@$userrow['user_warnings']) ? $userrow['user_warnings'] : 0,

		'AVATAR_IMG'		=> $avatar_img,
		'RANK_IMG'			=> $rank_img,
	));
}

?>