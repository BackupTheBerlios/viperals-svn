<?php
/** 
*
* @package acp
* @version $Id: admin_prune.php,v 1.11 2005/04/09 12:26:30 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
*/
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Do we have permission?
if (!$_CLASS['forums_auth']->acl_get('a_prune'))
{
	trigger_error('NO_ADMIN');
}

$_CLASS['core_user']->add_lang('admin_prune', 'forums');

$tpl_name = 'acp_prune_forums';
$page_title = 'ACP_PRUNE_FORUMS';
$u_action = 'forums&amp;file=admin_prune';

global $_CLASS, $config;

$forum_id = request_var('f', array(0));
$submit = (isset($_POST['submit'])) ? true : false;

$_CLASS['core_template']->assign_array(array(
	'S_PRUNED'			=> false,
	'S_SELECT_FORUM'	=> false,
));
	
if ($submit)
{
	$prune_posted = request_var('prune_days', 0);
	$prune_viewed = request_var('prune_vieweddays', 0);
	$prune_all = !$prune_posted && !$prune_viewed;

	$prune_flags = 0;
	$prune_flags += (request_var('prune_old_polls', 0)) ? 2 : 0;
	$prune_flags += (request_var('prune_announce', 0)) ? 4 : 0;
	$prune_flags += (request_var('prune_sticky', 0)) ? 8 : 0;

	// Convert days to seconds for timestamp functions...
	$prunedate_posted = time() - ($prune_posted * 86400);
	$prunedate_viewed = time() - ($prune_viewed * 86400);

	$_CLASS['core_template']->assign_array(array(
		'S_PRUNED'		=> true)
	);

	$sql_forum = (sizeof($forum_id)) ? ' AND forum_id IN (' . implode(', ', $forum_id) .')' : '';

	// Get a list of forum's or the data for the forum that we are pruning.
	$sql = 'SELECT forum_id, forum_name 
		FROM ' . FORUMS_FORUMS_TABLE . '
		WHERE forum_type = ' . FORUM_POST . "
			$sql_forum 
		ORDER BY left_id ASC";
	$result = $_CLASS['core_db']->query($sql);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$prune_ids = array();
		$p_result['topics'] = 0;
		$p_result['posts'] = 0;
		$log_data = '';

		do
		{
			if (!$_CLASS['forums_auth']->acl_get('f_list', $row['forum_id']))
			{
				continue;
			}

			if ($prune_all)
			{
				$p_result = prune($row['forum_id'], 'posted', time(), $prune_flags, false);
			}
			else
			{
				if ($prune_posted)
				{
					$return = prune($row['forum_id'], 'posted', $prunedate_posted, $prune_flags, false);
					$p_result['topics'] += $return['topics'];
					$p_result['posts'] += $return['posts'];
				}

				if ($prune_viewed)
				{
					$return = prune($row['forum_id'], 'viewed', $prunedate_viewed, $prune_flags, false);
					$p_result['topics'] += $return['topics'];
					$p_result['posts'] += $return['posts'];
				}
			}

			$prune_ids[] = $row['forum_id'];

			$_CLASS['core_template']->assign_vars_array('pruned', array(
				'FORUM_NAME'	=> $row['forum_name'],
				'NUM_TOPICS'	=> $p_result['topics'],
				'NUM_POSTS'		=> $p_result['posts'])
			);

			$log_data .= (($log_data != '') ? ', ' : '') . $row['forum_name'];
		}
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

		// Sync all pruned forums at once
		sync('forum', 'forum_id', $prune_ids, true);
		add_log('admin', 'LOG_PRUNE', $log_data);
	}
	$_CLASS['core_db']->free_result($result);
}

// If they haven't selected a forum for pruning yet then
// display a select box to use for pruning.
if (!sizeof($forum_id))
{
	$_CLASS['core_template']->assign_array(array(
		'U_ACTION'			=> generate_link($u_action, array('admin' => true)),
		'S_SELECT_FORUM'	=> true,
		'S_FORUM_OPTIONS'	=> make_forum_select(false, false, false))
	);
}
else
{
	$sql = 'SELECT forum_id, forum_name 
		FROM ' . FORUMS_FORUMS_TABLE . ' 
		WHERE forum_id IN (' . implode(', ', $forum_id).')';
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);

	if (!$row)
	{
		$_CLASS['core_db']->free_result($result);
		trigger_error($_CLASS['core_user']->lang['NO_FORUM'] . adm_back_link(generate_link($u_action, array('admin' => true))), E_USER_WARNING);
	}

	$forum_list = $s_hidden_fields = '';
	do
	{
		$forum_list .= (($forum_list != '') ? ', ' : '') . '<b>' . $row['forum_name'] . '</b>';
		$s_hidden_fields .= '<input type="hidden" name="f[]" value="' . $row['forum_id'] . '" />';
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

	$_CLASS['core_db']->free_result($result);

	$l_selected_forums = (sizeof($forum_id) == 1) ? 'SELECTED_FORUM' : 'SELECTED_FORUMS';

	$_CLASS['core_template']->assign_array(array(
		'L_SELECTED_FORUMS'		=> $_CLASS['core_user']->get_lang($l_selected_forums),
		'U_ACTION'				=> generate_link($u_action, array('admin' => true)),
		'U_BACK'				=> generate_link($u_action, array('admin' => true)),
		'FORUM_LIST'			=> $forum_list,
		'S_HIDDEN_FIELDS'		=> $s_hidden_fields
	));
}

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'modules/forums/admin/acp_prune.html');

?>