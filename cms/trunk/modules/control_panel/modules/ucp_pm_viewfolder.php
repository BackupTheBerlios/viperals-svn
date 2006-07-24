<?php
// -------------------------------------------------------------
//
// $Id: ucp_pm_viewfolder.php,v 1.2 2004/07/08 22:41:03 acydburn Exp $
//
// FILENAME  : viewfolder.php
// STARTED   : Sun Apr 11, 2004
// COPYRIGHT : © 2004 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// * Called from ucp_pm with mode == 'view_messages' && action == 'view_folder'
$_CLASS['core_template']->assign_array(array(
	'S_SHOW_RECIPIENTS'	=> false,
	'messagerow'		=> false,
	'S_PM_ICONS'		=> false
));

function view_folder($parent_class, $folder_id, $folder, $type)
{
	global $_CLASS;
	
	$limit = 10;
	$icons = obtain_icons();

	$color_rows = array('marked', 'replied', 'message_reported', 'friend', 'foe');
	
	foreach ($color_rows as $var)
	{
		$_CLASS['core_template']->assign_vars_array('pm_colour_info', array(
			'IMG'	=> $_CLASS['core_user']->img("pm_{$var}", ''),
			'CLASS' => "pm_{$var}_colour",
			'LANG'	=> $_CLASS['core_user']->lang[strtoupper($var) . '_MESSAGE'])
		);
	}

	$mark_options = array('mark_important', 'delete_marked', 'mark_read', 'mark_unread');

	$s_mark_options = '';
	foreach ($mark_options as $mark_option)
	{
		$s_mark_options .= '<option value="' . $mark_option . '">' . $_CLASS['core_user']->get_lang(strtoupper($mark_option)) . '</option>';
	}

	$friend = $foe = array();

	// Get friends and foes
	$sql = 'SELECT * 
		FROM ' . ZEBRA_TABLE . ' 
		WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$friend[$row['zebra_id']] = $row['friend'];
		$foe[$row['zebra_id']] = $row['foe'];
	}
	$_CLASS['core_db']->free_result($result);

	$_CLASS['core_template']->assign_array(array(
		'S_UNREAD'		=> ($type == 'unread'),
		'S_MARK_OPTIONS'=> $s_mark_options)
	);

	$folder_info = get_pm_from($folder_id, $folder, $_CLASS['core_user']->data['user_id'], $parent_class->link_parent, $type);

	// Okay, lets dump out the page ...
	if (!empty($folder_info['pm_list']))
	{
		// Build Recipient List if in outbox/sentbox - max two additional queries
		$recipient_list = $address_list = $address = array();
		if ($folder_id == PRIVMSGS_OUTBOX || $folder_id == PRIVMSGS_SENTBOX)
		{
			
			foreach ($folder_info['rowset'] as $message_id => $row)
			{
				$address[$message_id] = rebuild_header(array('to' => $row['to_address'], 'bcc' => $row['bcc_address']));
				foreach (array('u', 'g') as $save)
				{
					if (isset($address[$message_id][$save]) && sizeof($address[$message_id][$save]))
					{
						foreach (array_keys($address[$message_id][$save]) as $ug_id)
						{
							$recipient_list[$save][$ug_id] = array('name' => $_CLASS['core_user']->lang['NA'], 'colour' => '');
						}
					}
				}
			}
		
			foreach (array('u', 'g') as $ug_type)
			{
				if (isset($recipient_list[$ug_type]) && sizeof($recipient_list[$ug_type]))
				{
					$sql = ($ug_type === 'u') ? 'SELECT user_id as id, username as name, user_colour as colour FROM ' . CORE_USERS_TABLE . ' WHERE user_id' : 'SELECT group_id as id, group_name as name, group_colour as colour FROM ' . CORE_GROUPS_TABLE . ' WHERE group_id';
					$sql .= ' IN (' . implode(', ', array_keys($recipient_list[$ug_type])) . ')';
	
					$result = $_CLASS['core_db']->query($sql);

					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$recipient_list[$ug_type][$row['id']] = array('name' => $row['name'], 'colour' => $row['colour']);
					}
					$_CLASS['core_db']->free_result($result);
				}
			}		

			foreach ($address as $message_id => $adr_ary)
			{
				foreach ($adr_ary as $type => $id_ary)
				{
					foreach ($id_ary as $ug_id => $_id)
					{
						$address_list[$message_id][] = (($type == 'u') ? '<a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u='.$ug_id).'">' : '<a href="'.generate_link('Members_List&amp;mode=group&amp;g='.$ug_id).'">') . (($recipient_list[$type][$ug_id]['colour']) ? '<span style="color:#' . $recipient_list[$type][$ug_id]['colour'] . '">' : '<span>') . $recipient_list[$type][$ug_id]['name'] . '</span></a>';
					}
				}
			}
			
			unset($recipient_list, $address);
		}

		foreach ($folder_info['pm_list'] as $message_id)
		{
			$row =& $folder_info['rowset'][$message_id];

			$folder_img = ($row['unread']) ? 'folder_new' : 'folder';
			$folder_alt = ($row['unread']) ? 'NEW_MESSAGES' : 'NO_NEW_MESSAGES';

			// Generate all URIs ...
			$message_author = '<a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['author_id']) . '">' . $row['username'] . '</a>';
			$view_message_url = generate_link($parent_class->link_parent."&amp;f=$folder_id&amp;p=$message_id");
			$remove_message_url = generate_link($parent_class->link_parent.'&amp;mode=compose&amp;action=delete&amp;p='.$message_id);
			
			$row_indicator = '';
			foreach ($color_rows as $var)
			{
				if (($var != 'friend' && $var != 'foe' && $row[$var]) || 
					(($var == 'friend' || $var == 'foe') && isset(${$var}[$row['author_id']])))
				{
					$row_indicator = $var;
					break;
				}
			}

			// Send vars to template
			$_CLASS['core_template']->assign_vars_array('messagerow', array(
				'PM_CLASS'			=> ($row_indicator) ? 'pm_' . $row_indicator . '_colour' : '',

				'FOLDER_ID' 		=> $folder_id,
				'MESSAGE_ID'		=> $message_id,
				'MESSAGE_AUTHOR'	=> $message_author,
				'SENT_TIME'		 	=> $_CLASS['core_user']->format_date($row['message_time']),
				'SUBJECT'			=> censor_text($row['message_subject']),
				'FOLDER'			=> (isset($folder[$row['folder_id']])) ? $folder[$row['folder_id']]['folder_name'] : '',
				'U_FOLDER'			=> (isset($folder[$row['folder_id']])) ? generate_link($parent_class->link_parent.'&amp;folder=' . $row['folder_id']) : '',
				'PM_ICON_IMG'		=> (!empty($icons[$row['icon_id']])) ? '<img src="' . $config['icons_path'] . '/' . $icons[$row['icon_id']]['img'] . '" width="' . $icons[$row['icon_id']]['width'] . '" height="' . $icons[$row['icon_id']]['height'] . '" alt="" title="" />' : '',
				'FOLDER_IMG'		=> $_CLASS['core_user']->img($folder_img, $folder_alt),
				'PM_IMG'			=> ($row_indicator) ? $_CLASS['core_user']->img('pm_' . $row_indicator, '') : '',
				'ATTACH_ICON_IMG'	=> ($row['message_attachment']) ? $_CLASS['core_user']->img('icon_attach', $_CLASS['core_user']->lang['TOTAL_ATTACHMENTS']) : '',
				//'S_PM_REPORTED'		=> (!empty($row['message_reported'])) ? true : false,
				'S_PM_REPORTED'		=> '',
				'S_PM_DELETED'		=> ($row['deleted']) ? true : false,
				
				'U_VIEW_PM'			=> ($row['deleted']) ? '' : $view_message_url,
				'U_REMOVE_PM'		=> ($row['deleted']) ? $remove_message_url : '',
				
				'RECIPIENTS'		=> ($folder_id == PRIVMSGS_OUTBOX || $folder_id == PRIVMSGS_SENTBOX) ? implode(', ', $address_list[$message_id]) : '',
				'U_MCP_REPORT'		=> generate_link('forums&amp;file=mcp&amp;mode=reports&amp;pm='.$message_id))
//				'U_MCP_QUEUE'		=> "mcp.$phpEx?sid={$_CLASS['core_user']->session_id}&amp;mode=mod_queue&amp;t=$topic_id")
			);
		}
		
		unset($folder_info['rowset']);
		
		$_CLASS['core_template']->assign_array(array(
			'S_SHOW_RECIPIENTS'	=> ($folder_id == PRIVMSGS_OUTBOX || $folder_id == PRIVMSGS_SENTBOX) ? true : false,
			'S_SHOW_COLOUR_LEGEND'	=> true)
		);
	}
}

// Get PM's in folder x from user x
// Get PM's in all folders from user x with type of x (unread, new)
function get_pm_from($folder_id, $folder, $user_id, $url, $type = 'folder')
{
	global $_CLASS, $_POST;

	$limit = 10;
	$start = get_variable('start', 'REQUEST', 0, 'int');

	//$sort_days = request_var('st', ((!empty($_CLASS['core_user']->data['user_post_show_days'])) ? $_CLASS['core_user']->data['user_post_show_days'] : 0));
	//$sort_key	= request_var('sk', ((!empty($_CLASS['core_user']->data['user_post_sortby_type'])) ? $_CLASS['core_user']->data['user_post_sortby_type'] : 't'));
	//$sort_dir	= request_var('sd', ((!empty($_CLASS['core_user']->data['user_post_sortby_dir'])) ? $_CLASS['core_user']->data['user_post_sortby_dir'] : 'd'));

	$sort_days	= get_variable('st', 'REQUEST', 0, 'int');
	$sort_key	= get_variable('sk', 'REQUEST', 't');
	$sort_dir	= get_variable('sd', 'REQUEST', 'd');
	
	// PM ordering options
	$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_MESSAGES'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
	$sort_by_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 's' => $_CLASS['core_user']->lang['SUBJECT']);
	$sort_by_sql = array('a' => 'u.username', 't' => 'p.message_time', 's' => 'p.message_subject');

	$sort_key = (!in_array($sort_key, array('a', 't', 's'))) ? 't' : $sort_key;

	$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
	gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

	if ($type !== 'folder')
	{
		$folder_sql = ($type === 'unread') ? 't.unread = 1' : 't.msg_new = 1';
		$folder_id = PRIVMSGS_INBOX;
	}
	else
	{
		$folder_sql = 't.folder_id = ' . (int) $folder_id;
	}

	// Limit pms to certain time frame, obtain correct pm count
	if ($sort_days)
	{
		$min_post_time = $_CLASS['core_user']->time - ($sort_days * 86400);

		$sql = 'SELECT COUNT(t.msg_id) AS pm_count
			FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . " p
			WHERE $folder_sql
				AND t.user_id = $user_id
				AND t.msg_id = p.msg_id
				AND p.message_time >= $min_post_time";
		$result = $_CLASS['core_db']->query_limit($sql, 1);

		if (isset($_POST['sort']))
		{
			$start = 0;
		}

		$pm_count = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row['pm_count'] : 0;
		$_CLASS['core_db']->free_result($result);

		$sql_limit_time = "AND p.message_time >= $min_post_time";
	}
	else
	{
		if ($type === 'folder')
		{
			$pm_count = $folder[$folder_id]['num_messages'];
		}
		else
		{
			if (in_array($folder_id, array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX)))
			{
				$sql = 'SELECT COUNT(t.msg_id) AS pm_count
					FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . " p
					WHERE $folder_sql
						AND t.user_id = $user_id
						AND t.msg_id = p.msg_id";
			}
			else
			{
				$sql = 'SELECT pm_count 
					FROM ' . FORUMS_PRIVMSGS_FOLDER_TABLE . " 
					WHERE folder_id = $folder_id
						AND user_id = $user_id";
			}
			$result = $_CLASS['core_db']->query_limit($sql, 1);
			$pm_count = ($row = $_CLASS['core_db']->fetch_row_assoc($result)) ? $row['pm_count'] : 0;
			$_CLASS['core_db']->free_result($result);
		}

		$sql_limit_time = '';
	}

	$_CLASS['core_template']->assign_array(array(
		'PAGINATION'		=> generate_pagination("$url&amp;mode=view_messages&amp;action=view_folder&amp;f=$folder_id&amp;$u_sort_param", $pm_count, $limit, $start),
		'PAGE_NUMBER'		=> on_page($pm_count, $limit, $start),
		'TOTAL_MESSAGES'	=> (($pm_count == 1) ? $_CLASS['core_user']->lang['VIEW_PM_MESSAGE'] : sprintf($_CLASS['core_user']->lang['VIEW_PM_MESSAGES'], $pm_count)),

		//'POST_IMG'			=> (!$_CLASS['auth']->acl_get('u_sendpm')) ? $_CLASS['core_user']->img('btn_locked', 'PM_LOCKED') : $_CLASS['core_user']->img('btn_post_pm', 'POST_PM'),
		'POST_IMG'			=> $_CLASS['core_user']->img('btn_post_pm', 'POST_PM'),

		'REPORTED_IMG'		=> $_CLASS['core_user']->img('icon_reported', 'MESSAGE_REPORTED'),

		//'L_NO_MESSAGES'		=> (!$_CLASS['auth']->acl_get('u_sendpm')) ? $_CLASS['core_user']->lang['POST_PM_LOCKED'] : $_CLASS['core_user']->lang['NO_MESSAGES'],
		'L_NO_MESSAGES'		=> $_CLASS['core_user']->lang['NO_MESSAGES'],

		'S_SELECT_SORT_DIR'		=> $s_sort_dir,
		'S_SELECT_SORT_KEY'		=> $s_sort_key,
		'S_SELECT_SORT_DAYS'	=> $s_limit_days,
		'S_TOPIC_ICONS'			=> true, 

		//'U_POST_NEW_TOPIC'	=> ($_CLASS['auth']->acl_get('u_sendpm')) ? generate_link($url.'&amp;mode=compose&amp;action=post') : '', 
		'U_POST_NEW_TOPIC'	=> generate_link($url.'&amp;mode=compose&amp;action=post'), 

		'S_PM_ACTION'		=> generate_link("$url&amp;mode=view_messages&amp;action=view_folder&amp;f=$folder_id"))
	);

	// Grab all pm data
	$rowset = $pm_list = array();

	// If the user is trying to reach late pages, start searching from the end
	$store_reverse = false;
	$sql_limit = $limit;

	if ($start > $pm_count / 2)
	{
		$store_reverse = true;

		if ($start + $limit > $pm_count)
		{
			$sql_limit = min($limit, max(1, $pm_count - $start));
		}

		// Select the sort order
		$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC');
		$sql_start = max(0, $pm_count - $sql_limit - $start);
	}
	else
	{
		// Select the sort order
		$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
		$sql_start = $start;
	}

	$sql = 'SELECT t.*, p.author_id, p.root_level, p.message_time, p.message_subject, p.icon_id, p.message_reported, p.to_address, p.message_attachment, p.bcc_address, u.username 
		FROM ' . FORUMS_PRIVMSGS_TO_TABLE . ' t, ' . FORUMS_PRIVMSGS_TABLE . ' p, ' . CORE_USERS_TABLE . " u
		WHERE t.user_id = $user_id
			AND p.author_id = u.user_id
			AND $folder_sql
			AND t.msg_id = p.msg_id
			$sql_limit_time
		ORDER BY $sql_sort_order";

	$result = $_CLASS['core_db']->query_limit($sql, $sql_limit, $sql_start);

	while($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$rowset[$row['msg_id']] = $row;
		$pm_list[] = $row['msg_id'];
	}
	$_CLASS['core_db']->free_result($result);

	$pm_list = ($store_reverse) ? array_reverse($pm_list) : $pm_list;

	return array(
		'pm_count'	=> $pm_count, 
		'pm_list'	=> $pm_list, 
		'rowset'	=> $rowset
	);
}

?>