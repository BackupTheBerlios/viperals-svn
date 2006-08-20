<?php
// -------------------------------------------------------------
//
// $Id: admin_forums.php,v 1.30 2004/07/08 22:40:41 acydburn Exp $
//
// FILENAME  : admin_forums.php
// STARTED   : Thu Jul 12, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Get general vars
$update		= isset($_POST['update']);
$mode		= request_var('mode', '');
$action		= request_var('action', '');
$forum_id	= request_var('f', 0);
$parent_id	= request_var('parent_id', 0);

$l_title	= '';
$forum_data = $errors = array();

if (!$_CLASS['forums_auth']->acl_get('a_forum'))
{
	trigger_error('NO_ADMIN');
}

switch ($action)
{
	case 'delete':
		if (!$_CLASS['forums_auth']->acl_get('a_forumdel'))
		{
			trigger_error($_CLASS['core_user']->lang['NO_PERMISSION_FORUM_DELETE'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}
	break;

	case 'add':
		if (!$_CLASS['forums_auth']->acl_get('a_forumadd'))
		{
			trigger_error($_CLASS['core_user']->lang['NO_PERMISSION_FORUM_ADD'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}
	break;
}

$_CLASS['core_template']->assign_array(array(
	'S_EDIT_FORUM'		=> false,
	'S_DELETE_FORUM'	=> false,
	'S_RESYNCED'		=> false,
));

// Major routines
if ($update)
{
	switch ($action)
	{
		case 'delete':
			if (!$forum_id)
			{
				trigger_error('NO_FORUM');
			}

			$action_subforums	= request_var('action_subforums', '');
			$subforums_to_id	= request_var('subforums_to_id', 0);
			$action_posts		= request_var('action_posts', '');
			$posts_to_id		= request_var('posts_to_id', 0);

			$errors = delete_forum($forum_id, $action_posts, $action_subforums, $posts_to_id, $subforums_to_id);

			if (sizeof($errors))
			{
				break;
			}

			$_CLASS['forums_auth']->acl_clear_prefetch();

			trigger_error($_CLASS['core_user']->lang['FORUM_DELETED'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		break;

		case 'edit':
			if (!$forum_id)
			{
				trigger_error('NO_FORUM');
			}

			$forum_data = array(
				'forum_id'		=>	$forum_id
			);

		case 'add':
			$forum_data += array(
				'parent_id'				=> $parent_id,
				'forum_type'			=> request_var('forum_type', FORUM_POST),
				'type_action'			=> request_var('type_action', ''),
				'forum_status'			=> request_var('forum_status', ITEM_UNLOCKED),
				'forum_name'			=> request_var('forum_name', '', true),
				'forum_link'			=> request_var('forum_link', ''),
				'forum_link_track'		=> request_var('forum_link_track', false),
				'forum_desc'			=> request_var('forum_desc', '', true),
				'forum_desc_uid'		=> '',
				'forum_desc_options'	=> 0,
				'forum_desc_bitfield'	=> '',
				'forum_rules'			=> request_var('forum_rules', '', true),
				'forum_rules_uid'		=> '',
				'forum_rules_options'	=> 0,
				'forum_rules_bitfield'	=> '',
				'forum_rules_link'		=> request_var('forum_rules_link', ''),
				'forum_image'			=> request_var('forum_image', ''),
				'display_on_index'		=> request_var('display_on_index', false),
				'forum_topics_per_page'	=> request_var('topics_per_page', 0), 
				'enable_indexing'		=> request_var('enable_indexing',true), 
				'enable_icons'			=> request_var('enable_icons', false),
				'enable_prune'			=> request_var('enable_prune', false),
				'enable_post_review'	=> request_var('enable_post_review', true),
				'prune_days'			=> request_var('prune_days', 7),
				'prune_viewed'			=> request_var('prune_viewed', 7),
				'prune_freq'			=> request_var('prune_freq', 1),
				'prune_old_polls'		=> request_var('prune_old_polls', false),
				'prune_announce'		=> request_var('prune_announce', false),
				'prune_sticky'			=> request_var('prune_sticky', false),
				'forum_password'		=> request_var('forum_password', ''),
				'forum_password_confirm'=> request_var('forum_password_confirm', ''),
			);

			if ($mode === 'add')
			{
				$forum_data += array(
					'forum_posts'			=> 0,
					'forum_topics'			=> 0,
					'forum_topics_real'		=> 0,
				);
			}

			$forum_data['show_active'] = ($forum_data['forum_type'] == FORUM_POST) ? request_var('display_recent', false) : request_var('display_active', false);

			// Get data for forum rules if specified...
			if ($forum_data['forum_rules'])
			{
				generate_text_for_storage($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options'], request_var('rules_parse_bbcode', false), request_var('rules_parse_urls', false), request_var('rules_parse_smilies', false));
			}

			// Get data for forum description if specified
			if ($forum_data['forum_desc'])
			{
				generate_text_for_storage($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options'], request_var('desc_parse_bbcode', false), request_var('desc_parse_urls', false), request_var('desc_parse_smilies', false));
			}

			$_CLASS['core_db']->transaction();
				$errors = update_forum_data($forum_data);
			$_CLASS['core_db']->transaction('commit');

			if (!sizeof($errors))
			{
				$forum_perm_from = request_var('forum_perm_from', 0);

				// Copy permissions?
				if ($forum_perm_from)
				{
					// if we edit a forum delete current permissions first
					if ($action === 'edit')
					{
						$sql = 'DELETE FROM ' . FORUMS_ACL_TABLE . '
							WHERE forum_id = ' . (int) $forum_data['forum_id'];
						$_CLASS['core_db']->query($sql);
					}

					// From the mysql documentation:
					// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
					// Due to this we stay on the safe side if we do the insertion "the manual way"

					// Copy permisisons from/to the acl users table (only forum_id gets changed)
					$sql = 'SELECT user_id, group_id, auth_option_id, auth_role_id, auth_setting
						FROM ' . FORUMS_ACL_TABLE . '
						WHERE forum_id = ' . $forum_perm_from;
					$result = $_CLASS['core_db']->query($sql);

					$sql_array = array();
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$sql_array[] = array(
							'user_id'			=> (int) $row['user_id'],
							'group_id'			=> (int) $row['group_id'],
							'forum_id'			=> (int) $forum_data['forum_id'],
							'auth_option_id'	=> (int) $row['auth_option_id'],
							'auth_role_id'		=> (int) $row['auth_role_id'],
							'auth_setting'		=> (int) $row['auth_setting']
						);
					}
					$_CLASS['core_db']->free_result($result);

					// Now insert the data
					if (!empty($sql_array))
					{
						$_CLASS['core_db']->sql_query_build('MULTI_INSERT', $sql_array, FORUMS_ACL_TABLE);
						unset($sql_array);
					}
				}

				$_CLASS['forums_auth']->acl_clear_prefetch();

				$acl_url = '&amp;mode=setting_forum_local&amp;forum_id[]=' . $forum_data['forum_id'] . '&amp;select_all_groups=1';

				$message = ($action == 'add') ? $_CLASS['core_user']->lang['FORUM_CREATED'] : $_CLASS['core_user']->lang['FORUM_UPDATED'];

				// Redirect to permissions
				if ($_CLASS['forums_auth']->acl_get('a_fauth'))
				{
					$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['REDIRECT_ACL'], '<a href="' . generate_link('forums&amp;i=permissions'. $acl_url, array('admin' => true)) . '">', '</a>');
				}

				// redirect directly to permission settings screen if authed
				if ($action === 'add' && !$forum_perm_from && $_CLASS['forums_auth']->acl_get('a_fauth'))
				{
					$_CLASS['core_display']->meta_refresh(4, generate_link('forums&amp;i=permissions'. $acl_url, array('admin' => true)));
				}

				trigger_error($message . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
			}
		break;
	}
}

switch ($action)
{
	case 'move_up':
	case 'move_down':
	
		if (!$forum_id)
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}
	
		$sql = 'SELECT *
			FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
	
		if (!$row)
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}
	
		$move_forum_name = move_forum_by($row, $action, 1);
	
		if ($move_forum_name !== false)
		{
			add_log('admin', 'LOG_FORUM_' . strtoupper($action), $row['forum_name'], $move_forum_name);
		}
	
	break;
	
	case 'sync':
		if (!$forum_id)
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}
	
		$sql = 'SELECT forum_name, forum_type
			FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
	
		if (!$row)
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}
	
		sync('forum', 'forum_id', $forum_id);
		add_log('admin', 'LOG_FORUM_SYNC', $row['forum_name']);
	
		$_CLASS['core_template']->assign('L_FORUM_RESYNCED', sprintf($_CLASS['core_user']->lang['FORUM_RESYNCED'], $row['forum_name']));
	
	break;

	case 'add':
	case 'edit':
		if ($update)
		{
			$forum_data['forum_flags'] = 0;
			$forum_data['forum_flags'] += (request_var('forum_link_track', false)) ? 1 : 0;
			$forum_data['forum_flags'] += (request_var('prune_old_polls', false)) ? 2 : 0;
			$forum_data['forum_flags'] += (request_var('prune_announce', false)) ? 4 : 0;
			$forum_data['forum_flags'] += (request_var('prune_sticky', false)) ? 8 : 0;
			$forum_data['forum_flags'] += ($forum_data['show_active']) ? 16 : 0;
			$forum_data['forum_flags'] += (request_var('enable_post_review', true)) ? 32 : 0;
		}

		// Show form to create/modify a forum
		if ($action == 'edit')
		{
			$l_title = 'EDIT_FORUM';
			$row = get_forum_info($forum_id);
			$old_forum_type = $row['forum_type'];

			if (!$update)
			{
				$forum_data = $row;
			}

			// Make sure there is no forum displayed for parents_list having the current forum id as a parent...
			$sql = 'SELECT forum_id
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE parent_id = ' . $forum_id;
			$result = $_CLASS['core_db']->query($sql);

			$exclude_forums = array($forum_id);
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$exclude_forums[] = $row['forum_id'];
			}
			$_CLASS['core_db']->free_result($result);

			$parents_list = make_forum_select($forum_data['parent_id'], $exclude_forums, false, false, false);

			$forum_data['forum_password_confirm'] = $forum_data['forum_password'];
		}
		else
		{
			$l_title = 'CREATE_FORUM';

			$forum_id = $parent_id;
			$parents_list = make_forum_select($parent_id, false, false, false, false);

			// Fill forum data with default values
			if (!$update)
			{
				$forum_data = array(
					'parent_id'				=> $parent_id,
					'forum_type'			=> FORUM_POST,
					'forum_status'			=> ITEM_UNLOCKED,
					'forum_name'			=> request_var('forum_name', '', true),
					'forum_link'			=> '',
					'forum_link_track'		=> false,
					'forum_desc'			=> '',
					'forum_rules'			=> '',
					'forum_rules_link'		=> '',
					'forum_image'			=> '',
					'forum_style'			=> 0,
					'display_on_index'		=> false,
					'forum_topics_per_page'	=> 0, 
					'enable_indexing'		=> true, 
					'enable_icons'			=> false,
					'enable_prune'			=> false,
					'prune_days'			=> 7,
					'prune_viewed'			=> 7,
					'prune_freq'			=> 1,
					'forum_flags'			=> 0,
					'forum_password'		=> '',
					'forum_password_confirm'=> '',
				);
			}
		}

		$forum_rules_data = array(
			'text'			=> $forum_data['forum_rules'],
			'allow_bbcode'	=> true,
			'allow_smilies'	=> true,
			'allow_urls'	=> true
		);

		$forum_desc_data = array(
			'text'			=> $forum_data['forum_desc'],
			'allow_bbcode'	=> true,
			'allow_smilies'	=> true,
			'allow_urls'	=> true
		);

		$forum_rules_preview = '';

		// Parse rules if specified
		if ($forum_data['forum_rules'])
		{
			if (!isset($forum_data['forum_rules_uid']))
			{
				// Before we are able to display the preview and plane text, we need to parse our request_var()'d value...
				$forum_data['forum_rules_uid'] = '';
				$forum_data['forum_rules_bitfield'] = '';
				$forum_data['forum_rules_options'] = 0;

				generate_text_for_storage($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options'], request_var('rules_allow_bbcode', false), request_var('rules_allow_urls', false), request_var('rules_allow_smiliess', false));
			}

			// Generate preview content
			$forum_rules_preview = generate_text_for_display($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options']);

			// decode...
			$forum_rules_data = generate_text_for_edit($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_options']);
		}

		// Parse desciption if specified
		if ($forum_data['forum_desc'])
		{
			if (!isset($forum_data['forum_desc_uid']))
			{
				// Before we are able to display the preview and plane text, we need to parse our request_var()'d value...
				$forum_data['forum_desc_uid'] = '';
				$forum_data['forum_desc_bitfield'] = '';
				$forum_data['forum_desc_options'] = 0;

				generate_text_for_storage($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options'], request_var('desc_allow_bbcode', false), request_var('desc_allow_urls', false), request_var('desc_allow_smiliess', false));
			}

			// decode...
			$forum_desc_data = generate_text_for_edit($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_options']);
		}

		$forum_type_options = '';
		$forum_type_ary = array(FORUM_CAT => 'CAT', FORUM_POST => 'FORUM', FORUM_LINK => 'LINK');

		foreach ($forum_type_ary as $value => $lang)
		{
			$forum_type_options .= '<option value="' . $value . '"' . (($value == $forum_data['forum_type']) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['TYPE_' . $lang] . '</option>';
		}

		$statuslist = '<option value="' . ITEM_UNLOCKED . '"' . (($forum_data['forum_status'] == ITEM_UNLOCKED) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['UNLOCKED'] . '</option><option value="' . ITEM_LOCKED . '"' . (($forum_data['forum_status'] == ITEM_LOCKED) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['LOCKED'] . '</option>';

		$sql = 'SELECT forum_id
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_type = ' . FORUM_POST . "
				AND forum_id <> $forum_id";
		$result = $_CLASS['core_db']->query($sql);

		if ($_CLASS['core_db']->fetch_row_assoc($result))
		{
			$_CLASS['core_template']->assign_array(array(
				'S_MOVE_FORUM_OPTIONS'		=> make_forum_select($forum_data['parent_id'], $forum_id, false, true, false))
			);
		}
		$_CLASS['core_db']->free_result($result);

		$s_show_display_on_index = false;

		if ($forum_data['parent_id'] > 0)
		{
			// if this forum is a subforum put the "display on index" checkbox
			if ($parent_info = get_forum_info($forum_data['parent_id']))
			{
				if ($parent_info['parent_id'] > 0 || $parent_info['forum_type'] == FORUM_CAT)
				{
					$s_show_display_on_index = true;
				}
			}
		}

		$_CLASS['core_template']->assign_array(array(
			'S_EDIT_FORUM'		=> true,
			'S_ERROR'			=> (sizeof($errors)) ? true : false,
			'S_PARENT_ID'		=> $parent_id,
			'S_FORUM_PARENT_ID'	=> $forum_data['parent_id'],
			'S_ADD_ACTION'		=> ($action === 'add') ? true : false,

			'U_BACK'		=> generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true)),
			'U_EDIT_ACTION'	=> generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id . "&amp;action=$action&amp;f=$forum_id", array('admin' => true)),

			'L_COPY_PERMISSIONS_EXPLAIN'	=> $_CLASS['core_user']->get_lang('COPY_PERMISSIONS_' . strtoupper($action) . '_EXPLAIN'),
			'ERROR_MSG'						=> (sizeof($errors)) ? implode('<br />', $errors) : '',

			'FORUM_NAME'				=> $forum_data['forum_name'],
			'FORUM_DATA_LINK'			=> $forum_data['forum_link'],
			'FORUM_IMAGE'				=> $forum_data['forum_image'],
			'FORUM_IMAGE_SRC'			=> ($forum_data['forum_image']) ? $forum_data['forum_image'] : '',
			'FORUM_POST'				=> FORUM_POST,
			'FORUM_LINK'				=> FORUM_LINK,
			'FORUM_CAT'					=> FORUM_CAT,
			'PRUNE_FREQ'				=> $forum_data['prune_freq'],
			'PRUNE_DAYS'				=> $forum_data['prune_days'],
			'PRUNE_VIEWED'				=> $forum_data['prune_viewed'],
			'TOPICS_PER_PAGE'			=> $forum_data['forum_topics_per_page'],
			'FORUM_PASSWORD'			=> $forum_data['forum_password'],
			'FORUM_PASSWORD_CONFIRM'	=> $forum_data['forum_password_confirm'],
			'FORUM_RULES_LINK'			=> $forum_data['forum_rules_link'],
			'FORUM_RULES'				=> $forum_data['forum_rules'],
			'FORUM_RULES_PREVIEW'		=> $forum_rules_preview,
			'FORUM_RULES_PLAIN'			=> $forum_rules_data['text'],
			'S_BBCODE_CHECKED'			=> ($forum_rules_data['allow_bbcode']) ? true : false,
			'S_SMILIES_CHECKED'			=> ($forum_rules_data['allow_smilies']) ? true : false,
			'S_URLS_CHECKED'			=> ($forum_rules_data['allow_urls']) ? true : false,

			'FORUM_DESC'				=> $forum_desc_data['text'],
			'S_DESC_BBCODE_CHECKED'		=> ($forum_desc_data['allow_bbcode']) ? true : false,
			'S_DESC_SMILIES_CHECKED'	=> ($forum_desc_data['allow_smilies']) ? true : false,
			'S_DESC_URLS_CHECKED'		=> ($forum_desc_data['allow_urls']) ? true : false,

			'S_FORUM_TYPE_OPTIONS'		=> $forum_type_options,
			'S_PARENT_OPTIONS'			=> $parents_list,
			'S_STATUS_OPTIONS'			=> $statuslist,
			'S_FORUM_OPTIONS'			=> make_forum_select(($action === 'add') ? $forum_data['parent_id'] : false, false, false, false, false),
			'S_SHOW_DISPLAY_ON_INDEX'	=> $s_show_display_on_index,
			'S_FORUM_POST'				=> ($forum_data['forum_type'] == FORUM_POST) ? true : false,
			'S_FORUM_ORIG_POST'			=> (isset($old_forum_type) && $old_forum_type == FORUM_POST) ? true : false,
			'S_FORUM_LINK'				=> ($forum_data['forum_type'] == FORUM_LINK) ? true : false,
			'S_FORUM_CAT'				=> ($forum_data['forum_type'] == FORUM_CAT) ? true : false,
			'S_ENABLE_INDEXING'			=> ($forum_data['enable_indexing']) ? true : false,
			'S_TOPIC_ICONS'				=> ($forum_data['enable_icons']) ? true : false,
			'S_DISPLAY_ON_INDEX'		=> ($forum_data['display_on_index']) ? true : false,
			'S_PRUNE_ENABLE'			=> ($forum_data['enable_prune']) ? true : false,
			'S_FORUM_LINK_TRACK'		=> ($forum_data['forum_flags'] & 1) ? true : false,
			'S_PRUNE_OLD_POLLS'			=> ($forum_data['forum_flags'] & 2) ? true : false,
			'S_PRUNE_ANNOUNCE'			=> ($forum_data['forum_flags'] & 4) ? true : false,
			'S_PRUNE_STICKY'			=> ($forum_data['forum_flags'] & 8) ? true : false,
			'S_DISPLAY_ACTIVE_TOPICS'	=> ($forum_data['forum_flags'] & 16) ? true : false,
			'S_ENABLE_POST_REVIEW'		=> ($forum_data['forum_flags'] & 32) ? true : false,
			)
		);

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($l_title), 'modules/forums/admin/acp_forums.html');

		return;

	break;

	case 'delete':

		if (!$forum_id)
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM'] . adm_back_link(generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true))));
		}

		$forum_data = get_forum_info($forum_id);

		$subforums_id = array();

		$subforums = get_forum_branch($forum_id, 'children');
		foreach ($subforums as $row)
		{
			$subforums_id[] = $row['forum_id'];
		}

		$forums_list = make_forum_select($forum_data['parent_id'], $subforums_id);

		$sql = 'SELECT forum_id
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_type = ' . FORUM_POST . "
				AND forum_id <> $forum_id";
		$result = $_CLASS['core_db']->query($sql);

		if ($_CLASS['core_db']->fetch_row_assoc($result))
		{
			$_CLASS['core_template']->assign_array(array(
				'S_MOVE_FORUM_OPTIONS'		=> make_forum_select($forum_data['parent_id'], $subforums_id)) // , false, true, false???
			);
		}
		$_CLASS['core_db']->free_result($result);

		$parent_id = ($parent_id == $forum_id) ? 0 : $parent_id;

		$_CLASS['core_template']->assign_array(array(
			'S_DELETE_FORUM'		=> true,
			'U_ACTION'				=> generate_link("forums&amp;file=admin_forums&amp;parent_id=$parent_id&amp;parent_id={$parent_id}&amp;action=delete&amp;f=$forum_id", array('admin' => true)),
			'U_BACK'				=> generate_link('forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true)),

			'FORUM_NAME'			=> $forum_data['forum_name'],
			'S_FORUM_POST'			=> ($forum_data['forum_type'] == FORUM_POST) ? true : false,
			'S_HAS_SUBFORUMS'		=> ($forum_data['right_id'] - $forum_data['left_id'] > 1) ? true : false,
			'S_FORUMS_LIST'			=> $forums_list,
			'S_ERROR'				=> (sizeof($errors)) ? true : false,
			'ERROR_MSG'				=> (sizeof($errors)) ? implode('<br />', $errors) : '')
		);

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($l_title), 'modules/forums/admin/acp_forums.html');

		return;
	break;
}

// Default management page
if (!$parent_id)
{
	$navigation = $_CLASS['core_user']->lang['FORUM_INDEX'];
}
else
{
	$navigation = '<a href="' . generate_link('forums&amp;file=admin_forums', array('admin' => true)) . '">' . $_CLASS['core_user']->lang['FORUM_INDEX'] . '</a>';

	$forums_nav = get_forum_branch($parent_id, 'parents', 'descending');
	foreach ($forums_nav as $row)
	{
		if ($row['forum_id'] == $parent_id)
		{
			$navigation .= ' -&gt; ' . $row['forum_name'];
		}
		else
		{
			$navigation .= ' -&gt; <a href="' . generate_link('forums&amp;file=admin_forums&amp;parent_id='.$row['forum_id'], array('admin' => true)) . '">' . $row['forum_name'] . '</a>';
		}
	}
}

// Jumpbox
$forum_box = make_forum_select($parent_id, false, false, false, false); //make_forum_select($parent_id);

if ($action == 'sync')
{
	$_CLASS['core_template']->assign('S_RESYNCED', true);
}

$sql = 'SELECT *
	FROM ' . FORUMS_FORUMS_TABLE . "
	WHERE parent_id = $parent_id
	ORDER BY left_id";
$result = $_CLASS['core_db']->query($sql);

if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$_CLASS['core_template']->assign('S_NO_FORUMS', false);

	do
	{
		$forum_type = $row['forum_type'];

		if ($row['forum_status'] == ITEM_LOCKED)
		{
			$folder_image = '<img src="modules/forums/images/admin/icon_folder_lock.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['LOCKED'] . '" />';
		}
		else
		{
			switch ($forum_type)
			{
				case FORUM_LINK:
					$folder_image = '<img src="modules/forums/images/admin/icon_folder_link.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['LINK'] . '" />';
				break;

				default:
					$folder_image = ($row['left_id'] + 1 != $row['right_id']) ? '<img src="modules/forums/images/admin/icon_subfolder.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['SUBFORUM'] . '" />' : '<img src="modules/forums/images/admin/icon_folder.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['FOLDER'] . '" />';
				break;
			}
		}

		$url = "forums&amp;file=admin_forums&amp;parent_id=&amp;parent_id=$parent_id&amp;f={$row['forum_id']}";

		$forum_title = ($forum_type != FORUM_LINK) ? '<a href="' . generate_link('forums&amp;file=admin_forums&amp;parent_id=' . $row['forum_id'], array('admin' => true)) . '">' : '';
		$forum_title .= $row['forum_name'];
		$forum_title .= ($forum_type != FORUM_LINK) ? '</a>' : '';

		$_CLASS['core_template']->assign_vars_array('forums', array(
			'FOLDER_IMAGE'		=> $folder_image,
			'FORUM_NAME'		=> $row['forum_name'],
			'FORUM_DESCRIPTION'	=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
			'FORUM_TOPICS'		=> $row['forum_topics'],
			'FORUM_POSTS'		=> $row['forum_posts'],

			'S_FORUM_LINK'		=> ($forum_type == FORUM_LINK) ? true : false,
			'S_FORUM_POST'		=> ($forum_type == FORUM_POST) ? true : false,

			'U_FORUM'			=> generate_link('forums&amp;file=admin_forums&amp;parent_id=' . $row['forum_id'], array('admin' => true)),
			'U_MOVE_UP'			=> generate_link($url . '&amp;action=move_up', array('admin' => true)),
			'U_MOVE_DOWN'		=> generate_link($url . '&amp;action=move_down', array('admin' => true)),
			'U_EDIT'			=> generate_link($url . '&amp;action=edit', array('admin' => true)),
			'U_DELETE'			=> generate_link($url . '&amp;action=delete', array('admin' => true)),
			'U_SYNC'			=> generate_link($url . '&amp;action=sync', array('admin' => true))
		));
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
}
elseif ($parent_id)
{
	$row = get_forum_info($parent_id);

	$url = 'forums&amp;file=admin_forums&amp;parent_id=' . $parent_id . '&amp;f=' . $row['forum_id'];

	$_CLASS['core_template']->assign_array(array(
		'S_NO_FORUMS'		=> true,

		'U_EDIT'			=> generate_link($url . '&amp;action=edit', array('admin' => true)),
		'U_DELETE'			=> generate_link($url . '&amp;action=delete', array('admin' => true)),
		'U_SYNC'			=> generate_link($url . '&amp;action=sync', array('admin' => true))
	));
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_template']->assign_array(array(
	'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',
	'NAVIGATION'	=> $navigation,
	'FORUM_BOX'		=> $forum_box,
	'U_SEL_ACTION'	=> generate_link('forums&amp;file=admin_forums', array('admin' => true)),
	'U_ACTION'		=> generate_link('forums&amp;file=admin_forums&amp;parent_id=' . $parent_id, array('admin' => true))
));

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($l_title), 'modules/forums/admin/acp_forums.html');


/**
* Get forum details
*/
function get_forum_info($forum_id)
{
	global $_CLASS;

	$sql = 'SELECT *
		FROM ' . FORUMS_FORUMS_TABLE . "
		WHERE forum_id = $forum_id";
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if (!$row)
	{
		trigger_error("Forum #$forum_id does not exist", E_USER_ERROR);
	}

	return $row;
}

/**
* Update forum data
*/
function update_forum_data(&$forum_data)
{
	global $_CLASS;

	$errors = array();

	if (!$forum_data['forum_name'])
	{
		$errors[] = $_CLASS['core_user']->lang['FORUM_NAME_EMPTY'];
	}

	if ($forum_data['forum_password'] || $forum_data['forum_password_confirm'])
	{
		if ($forum_data['forum_password'] != $forum_data['forum_password_confirm'])
		{
			$forum_data['forum_password'] = $forum_data['forum_password_confirm'] = '';
			$errors[] = $_CLASS['core_user']->lang['FORUM_PASSWORD_MISMATCH'];
		}
	}

	if ($forum_data['prune_days'] < 0 || $forum_data['prune_viewed'] < 0 || $forum_data['prune_freq'] < 0)
	{
		$forum_data['prune_days'] = $forum_data['prune_viewed'] = $forum_data['prune_freq'] = 0;
		$errors[] = $_CLASS['core_user']->lang['FORUM_DATA_NEGATIVE'];
	}

	// Set forum flags
	// 1 = link tracking
	// 2 = prune old polls
	// 4 = prune announcements
	// 8 = prune stickies
	// 16 = show active topics
	// 32 = enable post review
	$forum_data['forum_flags'] = 0;
	$forum_data['forum_flags'] += ($forum_data['forum_link_track']) ? 1 : 0;
	$forum_data['forum_flags'] += ($forum_data['prune_old_polls']) ? 2 : 0;
	$forum_data['forum_flags'] += ($forum_data['prune_announce']) ? 4 : 0;
	$forum_data['forum_flags'] += ($forum_data['prune_sticky']) ? 8 : 0;
	$forum_data['forum_flags'] += ($forum_data['show_active']) ? 16 : 0;
	$forum_data['forum_flags'] += ($forum_data['enable_post_review']) ? 32 : 0;

	// Unset data that are not database fields
	$forum_data_sql = $forum_data;

	unset($forum_data_sql['forum_link_track']);
	unset($forum_data_sql['prune_old_polls']);
	unset($forum_data_sql['prune_announce']);
	unset($forum_data_sql['prune_sticky']);
	unset($forum_data_sql['show_active']);
	unset($forum_data_sql['enable_post_review']);
	unset($forum_data_sql['forum_password_confirm']);

	// What are we going to do tonight Brain? The same thing we do everynight,
	// try to take over the world ... or decide whether to continue update
	// and if so, whether it's a new forum/cat/link or an existing one
	if (sizeof($errors))
	{
		return $errors;
	}

	if (!isset($forum_data_sql['forum_id']))
	{
		// no forum_id means we're creating a new forum
		unset($forum_data_sql['type_action']);

		if ($forum_data_sql['parent_id'])
		{
			$sql = 'SELECT left_id, right_id
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_id = ' . $forum_data_sql['parent_id'];
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$row)
			{
				trigger_error($_CLASS['core_user']->lang['PARENT_NOT_EXIST'] . adm_back_link(u_action . '&amp;' . $parent_id));
			}

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET left_id = left_id + 2, right_id = right_id + 2
				WHERE left_id > ' . $row['right_id'];
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET right_id = right_id + 2
				WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
			$_CLASS['core_db']->query($sql);

			$forum_data_sql['left_id'] = $row['right_id'];
			$forum_data_sql['right_id'] = $row['right_id'] + 1;
		}
		else
		{
			$sql = 'SELECT MAX(right_id) AS right_id
				FROM ' . FORUMS_FORUMS_TABLE;
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			$forum_data_sql['left_id'] = $row['right_id'] + 1;
			$forum_data_sql['right_id'] = $row['right_id'] + 2;
		}

		$_CLASS['core_db']->sql_query_build('INSERT', $forum_data_sql, FORUMS_FORUMS_TABLE);
		$forum_data['forum_id'] = $_CLASS['core_db']->insert_id(FORUMS_FORUMS_TABLE, 'forum_id');

		add_log('admin', 'LOG_FORUM_ADD', $forum_data['forum_name']);
	}
	else
	{
		$row = get_forum_info($forum_data_sql['forum_id']);

		if ($row['forum_type'] == FORUM_POST && $row['forum_type'] != $forum_data_sql['forum_type'])
		{
			// we're turning a postable forum into a non-postable forum
			if ($forum_data_sql['type_action'] == 'move')
			{
				$to_forum_id = request_var('to_forum_id', 0);

				if ($to_forum_id)
				{
					$errors = move_forum_content($forum_data_sql['forum_id'], $to_forum_id);
				}
				else
				{
					return array($_CLASS['core_user']->lang['NO_DESTINATION_FORUM']);
				}
			}
			else if ($forum_data_sql['type_action'] == 'delete')
			{
				$errors = delete_forum_content($forum_data_sql['forum_id']);
			}
			else
			{
				return array($_CLASS['core_user']->lang['NO_FORUM_ACTION']);
			}

			$forum_data_sql['forum_posts'] = $forum_data_sql['forum_topics'] = $forum_data_sql['forum_topics_real'] = 0;
		}

		if (sizeof($errors))
		{
			return $errors;
		}

		if ($row['parent_id'] != $forum_data_sql['parent_id'])
		{
			$errors = move_forum($forum_data_sql['forum_id'], $forum_data_sql['parent_id']);
		}

		if (sizeof($errors))
		{
			return $errors;
		}

		unset($forum_data_sql['type_action']);

		if ($row['forum_name'] != $forum_data_sql['forum_name'])
		{
			// the forum name has changed, clear the parents list of child forums
			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
				SET forum_parents = ''
				WHERE left_id > " . $row['left_id'] . '
					AND right_id < ' . $row['right_id'];
			$_CLASS['core_db']->query($sql);
		}

		// Setting the forum id to the forum id is not really received well by some dbs. ;)
		$forum_id = $forum_data_sql['forum_id'];
		unset($forum_data_sql['forum_id']);

		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $forum_data_sql) . '
			WHERE forum_id = ' . $forum_id;
		$_CLASS['core_db']->query($sql);

		// Add it back
		$forum_data['forum_id'] = $forum_id;

		add_log('admin', 'LOG_FORUM_EDIT', $forum_data['forum_name']);
	}

	return $errors;
}

/**
* Move forum
*/
function move_forum($from_id, $to_id)
{
	global $_CLASS;

	$moved_forums = get_forum_branch($from_id, 'children', 'descending');
	$from_data = $moved_forums[0];
	$diff = sizeof($moved_forums) * 2;

	$moved_ids = array();
	for ($i = 0; $i < sizeof($moved_forums); ++$i)
	{
		$moved_ids[] = $moved_forums[$i]['forum_id'];
	}

	// Resync parents
	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET right_id = right_id - $diff, forum_parents = ''
		WHERE left_id < " . $from_data['right_id'] . "
			AND right_id > " . $from_data['right_id'];
	$_CLASS['core_db']->query($sql);

	// Resync righthand side of tree
	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET left_id = left_id - $diff, right_id = right_id - $diff, forum_parents = ''
		WHERE left_id > " . $from_data['right_id'];
	$_CLASS['core_db']->query($sql);

	if ($to_id > 0)
	{
		$to_data = get_forum_info($to_id);

		// Resync new parents
		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
			SET right_id = right_id + $diff, forum_parents = ''
			WHERE " . $to_data['right_id'] . ' BETWEEN left_id AND right_id
				AND forum_id NOT IN (' . implode(', ', $moved_ids) . ')';
		$_CLASS['core_db']->query($sql);

		// Resync the righthand side of the tree
		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
			SET left_id = left_id + $diff, right_id = right_id + $diff, forum_parents = ''
			WHERE left_id > " . $to_data['right_id'] . '
				AND forum_id NOT IN (' . implode(', ', $moved_ids) . ')';
		$_CLASS['core_db']->query($sql);

		// Resync moved branch
		$to_data['right_id'] += $diff;

		if ($to_data['right_id'] > $from_data['right_id'])
		{
			$diff = '+ ' . ($to_data['right_id'] - $from_data['right_id'] - 1);
		}
		else
		{
			$diff = '- ' . abs($to_data['right_id'] - $from_data['right_id'] - 1);
		}
	}
	else
	{
		$sql = 'SELECT MAX(right_id) AS right_id
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_id NOT IN (' . implode(', ', $moved_ids) . ')';
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$diff = '+ ' . ($row['right_id'] - $from_data['left_id'] + 1);
	}

	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET left_id = left_id $diff, right_id = right_id $diff, forum_parents = ''
		WHERE forum_id IN (" . implode(', ', $moved_ids) . ')';
	$_CLASS['core_db']->query($sql);
}

/**
* Move forum content from one to another forum
*/
function move_forum_content($from_id, $to_id, $sync = true)
{
	global $_CLASS;

	$table_ary = array(ACL_GROUPS_TABLE, ACL_USERS_TABLE, LOG_TABLE, POSTS_TABLE, TOPICS_TABLE, DRAFTS_TABLE, TOPICS_TRACK_TABLE);

	foreach ($table_ary as $table)
	{
		$sql = "UPDATE $table
			SET forum_id = $to_id
			WHERE forum_id = $from_id";
		$_CLASS['core_db']->query($sql);
	}
	unset($table_ary);

	$table_ary = array(FORUMS_ACCESS_TABLE, FORUMS_TRACK_TABLE, FORUMS_WATCH_TABLE, MODERATOR_CACHE_TABLE);

	foreach ($table_ary as $table)
	{
		$sql = "DELETE FROM $table
			WHERE forum_id = $from_id";
		$_CLASS['core_db']->query($sql);
	}

	if ($sync)
	{
		// Delete ghost topics that link back to the same forum then resync counters
		sync('topic_moved');
		sync('forum', 'forum_id', $to_id);
	}

	return array();
}

/**
* Remove complete forum
*/
function delete_forum($forum_id, $action_posts = 'delete', $action_subforums = 'delete', $posts_to_id = 0, $subforums_to_id = 0)
{
	global $_CLASS;

	$forum_data = get_forum_info($forum_id);

	$errors = array();
	$log_action_posts = $log_action_forums = $posts_to_name = $subforums_to_name = '';
	$forum_ids = array($forum_id);

	if ($action_posts == 'delete')
	{
		$log_action_posts = 'POSTS';
		$errors = array_merge($errors, delete_forum_content($forum_id));
	}
	else if ($action_posts == 'move')
	{
		if (!$posts_to_id)
		{
			$errors[] = $_CLASS['core_user']->lang['NO_DESTINATION_FORUM'];
		}
		else
		{
			$log_action_posts = 'MOVE_POSTS';

			$sql = 'SELECT forum_name 
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_id = ' . $posts_to_id;
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$row)
			{
				$errors[] = $_CLASS['core_user']->lang['NO_FORUM'];
			}
			else
			{
				$posts_to_name = $row['forum_name'];
				$errors = array_merge($errors, move_forum_content($forum_id, $posts_to_id));
			}
		}
	}

	if (sizeof($errors))
	{
		return $errors;
	}

	if ($action_subforums == 'delete')
	{
		$log_action_forums = 'FORUMS';
		$rows = get_forum_branch($forum_id, 'children', 'descending', false);

		foreach ($rows as $row)
		{
			$forum_ids[] = $row['forum_id'];
			$errors = array_merge($errors, delete_forum_content($row['forum_id']));
		}

		if (sizeof($errors))
		{
			return $errors;
		}

		$diff = sizeof($forum_ids) * 2;

		$sql = 'DELETE FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_id IN (' . implode(', ', $forum_ids) . ')';
		$_CLASS['core_db']->query($sql);
	}
	else if ($action_subforums == 'move')
	{
		if (!$subforums_to_id)
		{
			$errors[] = $_CLASS['core_user']->lang['NO_DESTINATION_FORUM'];
		}
		else
		{
			$log_action_forums = 'MOVE_FORUMS';

			$sql = 'SELECT forum_name 
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_id = ' . $subforums_to_id;
			$result = $_CLASS['core_db']->query($sql);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if (!$row)
			{
				$errors[] = $_CLASS['core_user']->lang['NO_FORUM'];
			}
			else
			{
				$subforums_to_name = $row['forum_name'];

				$sql = 'SELECT forum_id
					FROM ' . FORUMS_FORUMS_TABLE . "
					WHERE parent_id = $forum_id";
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					move_forum($row['forum_id'], $subforums_to_id);
				}
				$_CLASS['core_db']->free_result($result);

				$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
					SET parent_id = $subforums_to_id
					WHERE parent_id = $forum_id";
				$_CLASS['core_db']->query($sql);

				$diff = 2;
				$sql = 'DELETE FROM ' . FORUMS_FORUMS_TABLE . "
					WHERE forum_id = $forum_id";
				$_CLASS['core_db']->query($sql);
			}
		}

		if (sizeof($errors))
		{
			return $errors;
		}
	}
	else
	{
		$diff = 2;
		$sql = 'DELETE FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
		$_CLASS['core_db']->query($sql);
	}

	// Resync tree
	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET right_id = right_id - $diff
		WHERE left_id < {$forum_data['right_id']} AND right_id > {$forum_data['right_id']}";
	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET left_id = left_id - $diff, right_id = right_id - $diff
		WHERE left_id > {$forum_data['right_id']}";
	$_CLASS['core_db']->query($sql);

	// Delete forum ids from extension groups table
	$sql = 'SELECT group_id, allowed_forums 
		FROM ' . FORUMS_EXTENSION_GROUPS_TABLE;
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if (!$row['allowed_forums'])
		{
			continue;
		}

		$allowed_forums = unserialize(trim($row['allowed_forums']));
		$allowed_forums = array_diff($allowed_forums, $forum_ids);

		$sql = 'UPDATE ' . FORUMS_EXTENSION_GROUPS_TABLE . " 
			SET allowed_forums = '" . ((sizeof($allowed_forums)) ? serialize($allowed_forums) : '') . "'
			WHERE group_id = {$row['group_id']}";
		$_CLASS['core_db']->query($sql);
	}
	$_CLASS['core_db']->free_result($result);

	$_CLASS['core_cache']->destroy('_extensions');

	$log_action = implode('_', array($log_action_posts, $log_action_forums));

	switch ($log_action)
	{
		case 'MOVE_POSTS_MOVE_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_POSTS_MOVE_FORUMS', $posts_to_name, $subforums_to_name, $forum_data['forum_name']);
		break;

		case 'MOVE_POSTS_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_POSTS_FORUMS', $posts_to_name, $forum_data['forum_name']);
		break;

		case 'POSTS_MOVE_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_POSTS_MOVE_FORUMS', $subforums_to_name, $forum_data['forum_name']);
		break;

		case '_MOVE_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_FORUMS', $subforums_to_name, $forum_data['forum_name']);
		break;

		case 'MOVE_POSTS_':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_POSTS', $posts_to_name, $forum_data['forum_name']);
		break;

		case 'POSTS_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_POSTS_FORUMS', $forum_data['forum_name']);
		break;

		case '_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_FORUMS', $forum_data['forum_name']);
		break;

		case 'POSTS_':
			add_log('admin', 'LOG_FORUM_DEL_POSTS', $forum_data['forum_name']);
		break;

		default:
			add_log('admin', 'LOG_FORUM_DEL_FORUM', $forum_data['forum_name']);
		break;
	}

	return $errors;
}

/**
* Delete forum content
*/
function delete_forum_content($forum_id)
{
	global $_CLASS;

	require_once SITE_FILE_ROOT.'includes/forums/functions_posting.php';

	$_CLASS['core_db']->transaction();

	// Select then delete all attachments
	$sql = 'SELECT a.topic_id
		FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_ATTACHMENTS_TABLE . " a
		WHERE p.forum_id = $forum_id
			AND a.in_message = 0
			AND a.topic_id = p.topic_id";
	$result = $_CLASS['core_db']->query($sql);	

	$topic_ids = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$topic_ids[] = $row['topic_id'];
	}
	$_CLASS['core_db']->free_result($result);

	delete_attachments('topic', $topic_ids, false);

	switch ($_CLASS['core_db']->db_layer)
	{
		case 'mysql4':
		case 'mysqli':

			// Delete everything else and thank MySQL for offering multi-table deletion
			$tables_ary = array(
				FORUMS_SEARCH_WORDMATCH_TABLE	=> 'post_id',
				FORUMS_REPORTS_TABLE			=> 'post_id',
				//WARNINGS_TABLE			=> 'post_id',
				FORUMS_BOOKMARKS_TABLE			=> 'topic_id',
				FORUMS_WATCH_TABLE			=> 'topic_id',
				//TOPICS_POSTED_TABLE		=> 'topic_id',
				FORUMS_POLL_OPTIONS_TABLE	=> 'topic_id',
				FORUMS_POLL_VOTES_TABLE		=> 'topic_id',
			);

			$sql = 'DELETE ' . FORUMS_POSTS_TABLE;
			$sql_using = "\nFROM " . FORUMS_POSTS_TABLE;
			$sql_where = "\nWHERE " . FORUMS_POSTS_TABLE . ".forum_id = $forum_id\n";

			foreach ($tables_ary as $table => $field)
			{
				$sql .= ", $table ";
				$sql_using .= ", $table ";
				$sql_where .= "\nAND $table.$field = " . POSTS_TABLE . ".$field";
			}

			$_CLASS['core_db']->query($sql . $sql_using . $sql_where);

		break;

		default:
		
			// Delete everything else and curse your DB for not offering multi-table deletion
			$tables_ary = array(
				'post_id'	=>	array(
					FORUMS_SEARCH_WORDMATCH_TABLE,
					FORUMS_REPORTS_TABLE,
					//WARNINGS_TABLE,
				),

				'topic_id'	=>	array(
					FORUMS_BOOKMARKS_TABLE,
					FORUMS_WATCH_TABLE,
					//TOPICS_POSTED_TABLE,
					FORUMS_POLL_OPTIONS_TABLE,
					FORUMS_POLL_VOTES_TABLE,
				)
			);

			foreach ($tables_ary as $field => $tables)
			{
				$start = 0;

				do
				{
					$sql = "SELECT $field
						FROM " . FORUMS_POSTS_TABLE . '
						WHERE forum_id = ' . $forum_id;
					$result = $_CLASS['core_db']->query_limit($sql, 500, $start);

					$ids = array();
					while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
					{
						$ids[] = $row[$field];
					}
					$_CLASS['core_db']->free_result($result);

					if (sizeof($ids))
					{
						$start += sizeof($ids);

						foreach ($tables as $table)
						{
							$_CLASS['core_db']->query("DELETE FROM $table WHERE $field IN (" . implode(', ', $id_list) . ')');
						}
					}
				}
				while ($row);
			}
			unset($ids, $id_list);

		break;
	}

	$table_ary = array(FORUMS_ACL_TABLE, FORUMS_TRACK_TABLE, FORUMS_WATCH_TABLE, FORUMS_LOG_TABLE, FORUMS_MODERATOR_CACHE_TABLE, FORUMS_POSTS_TABLE, FORUMS_TOPICS_TABLE);//, TOPICS_TRACK_TABLE

	foreach ($table_ary as $table)
	{
		$_CLASS['core_db']->query("DELETE FROM $table WHERE forum_id = $forum_id");
	}

	// Set forum ids to 0
	$table_ary = array(FORUMS_DRAFTS_TABLE);

	foreach ($table_ary as $table)
	{
		$_CLASS['core_db']->query("UPDATE $table SET forum_id = 0 WHERE forum_id = $forum_id");
	}

	$_CLASS['core_db']->transaction('commit');

	// Make sure the overall post/topic count is correct...
	$sql = 'SELECT COUNT(post_id) AS stat 
		FROM ' . FORUMS_POSTS_TABLE . '
		WHERE post_approved = 1';
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	set_config('num_posts', (int) $row['stat'], true);

	$sql = 'SELECT COUNT(topic_id) AS stat
		FROM ' . FORUMS_TOPICS_TABLE . '
		WHERE topic_approved = 1';
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	set_config('num_topics', (int) $row['stat'], true);

	$sql = 'SELECT COUNT(attach_id) as stat
		FROM ' . FORUMS_ATTACHMENTS_TABLE;
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	set_config('num_files', (int) $row['stat'], true);

	$sql = 'SELECT SUM(filesize) as stat
		FROM ' . FORUMS_ATTACHMENTS_TABLE;
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	set_config('upload_dir_size', (int) $row['stat'], true);

	add_log('admin', 'LOG_RESYNC_STATS');

	return array();
}

/**
* Move forum position by $steps up/down
*/
function move_forum_by($forum_row, $action = 'move_up', $steps = 1)
{
	global $_CLASS;

	/**
	* Fetch all the siblings between the module's current spot
	* and where we want to move it to. If there are less than $steps
	* siblings between the current spot and the target then the
	* module will move as far as possible
	*/
	$sql = 'SELECT forum_id, forum_name, left_id, right_id
		FROM ' . FORUMS_FORUMS_TABLE . "
		WHERE parent_id = {$forum_row['parent_id']}
			AND " . (($action == 'move_up') ? "right_id < {$forum_row['right_id']} ORDER BY right_id DESC" : "left_id > {$forum_row['left_id']} ORDER BY left_id ASC");
	$result = $_CLASS['core_db']->query_limit($sql, $steps);

	$target = array();
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$target = $row;
	}
	$_CLASS['core_db']->free_result($result);

	if (!sizeof($target))
	{
		// The forum is already on top or bottom
		return false;
	}

	/**
	* $left_id and $right_id define the scope of the nodes that are affected by the move.
	* $diff_up and $diff_down are the values to substract or add to each node's left_id
	* and right_id in order to move them up or down.
	* $move_up_left and $move_up_right define the scope of the nodes that are moving
	* up. Other nodes in the scope of ($left_id, $right_id) are considered to move down.
	*/
	if ($action == 'move_up')
	{
		$left_id = $target['left_id'];
		$right_id = $forum_row['right_id'];

		$diff_up = $forum_row['left_id'] - $target['left_id'];
		$diff_down = $forum_row['right_id'] + 1 - $forum_row['left_id'];

		$move_up_left = $forum_row['left_id'];
		$move_up_right = $forum_row['right_id'];
	}
	else
	{
		$left_id = $forum_row['left_id'];
		$right_id = $target['right_id'];

		$diff_up = $forum_row['right_id'] + 1 - $forum_row['left_id'];
		$diff_down = $target['right_id'] - $forum_row['right_id'];

		$move_up_left = $forum_row['right_id'] + 1;
		$move_up_right = $target['right_id'];
	}

	// Now do the dirty job
	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET left_id = left_id + CASE
			WHEN left_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
			ELSE {$diff_down}
		END,
		right_id = right_id + CASE
			WHEN right_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
			ELSE {$diff_down}
		END,
		forum_parents = ''
		WHERE 
			left_id BETWEEN {$left_id} AND {$right_id}
			AND right_id BETWEEN {$left_id} AND {$right_id}";
	$_CLASS['core_db']->query($sql);

	return $target['forum_name'];
}

?>