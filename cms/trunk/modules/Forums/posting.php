<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||

$Id$
*/

// -------------------------------------------------------------
//
// $Id: posting.php,v 1.346 2004/10/19 19:20:29 acydburn Exp $
//
// FILENAME  : posting.php
// STARTED   : Sat Feb 17, 2001
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

if (!defined('VIPERAL'))
{
    die;
}

// Grab only parameters needed here
$post_id	= request_var('p', 0);
$topic_id	= request_var('t', 0);
$forum_id	= request_var('f', 0);
$draft_id	= request_var('d', 0);

$submit		= isset($_POST['post']);
$preview	= isset($_POST['preview']);
$save		= isset($_POST['save']);
$load		= isset($_POST['load']);
$confirm	= isset($_POST['confirm']);
$delete		= isset($_POST['delete']);

$refresh	= isset($_POST['add_file']) || isset($_POST['delete_file']) || isset($_POST['edit_comment']) || isset($_POST['cancel_unglobalise']) || $save || $load;

$mode		= ($delete && !$preview && !$refresh && $submit) ? 'delete' : request_var('mode', '');

$error = array();
$current_time = $_CLASS['core_user']->time;

// Was cancel pressed? If so then redirect to the appropriate page
if ($_CLASS['core_user']->is_bot || isset($_POST['cancel']))
{
	$redirect = ($post_id) ? "Forums&amp;file=viewtopic&p=$post_id#$post_id" : (($topic_id) ? 'Forums&amp;file=viewtopic&t='.$topic_id : (($forum_id) ? 'Forums&amp;file=viewforum&f='.$forum_id : 'Forums'));
	redirect(generate_link($redirect,  array('full' => true)));
}

switch ($mode)
{
	case 'post':
		if (!$forum_id)
		{
			trigger_error('NO_FORUM');
		}

		$sql = 'SELECT *
			FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
	break;

	case 'bump':
	case 'reply':
		if (!$topic_id)
		{
			trigger_error('NO_TOPIC');
		}

		$sql = 'SELECT t.*, f.* 
			FROM ' . FORUMS_TOPICS_TABLE . ' t LEFT JOIN ' . FORUMS_FORUMS_TABLE . " f ON (f.forum_id = t.forum_id)
			WHERE t.topic_id = $topic_id";
	break;

	case 'quote':
	case 'edit':
	case 'delete':
		if (!$post_id)
		{
			trigger_error('NO_POST');
		}

		$sql = 'SELECT f.*, t.*, p.*, u.username, u.user_sig, u.user_sig_bbcode_uid, u.user_sig_bbcode_bitfield
			FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . ' u , ' . FORUMS_TOPICS_TABLE . ' t
			LEFT JOIN ' . FORUMS_FORUMS_TABLE . " f ON (f.forum_id = t.forum_id)
			WHERE p.post_id = $post_id
				AND t.topic_id = p.topic_id
				AND u.user_id = p.poster_id";
	break;

	case 'smilies':
		require_once(SITE_FILE_ROOT.'includes/forums/functions_posting.php');

		generate_smilies('window', $forum_id);

		script_close(false);
	break;

	default:
		trigger_error('NO_POST_MODE');
	break;
}

$result = $_CLASS['core_db']->query($sql);

$posting_data = $_CLASS['core_db']->fetch_row_assoc($result);
$_CLASS['core_db']->free_result($result);

if (!$posting_data)
{
	trigger_error('NO_POST');
}

require_once(SITE_FILE_ROOT.'includes/forums/message_parser.php');
require_once(SITE_FILE_ROOT.'includes/forums/functions_admin.php');
require_once(SITE_FILE_ROOT.'includes/forums/functions_posting.php');

// remove
extract($posting_data);

if ($posting_data['forum_type'] == FORUM_POST && !$_CLASS['auth']->acl_get('f_' . $mode, $forum_id) && !$_CLASS['auth']->acl_get('m_'. $mode, $forum_id))
{
	if ($_CLASS['core_user']->is_user)
	{
		trigger_error('USER_CANNOT_' . strtoupper($mode));
	}
	
	login_box(array('explain' => $_CLASS['core_user']->lang['LOGIN_EXPLAIN_' . strtoupper($mode)]));
}

$forum_id	= (int) $posting_data['forum_id'];
$topic_id	= (int) $topic_id;
$post_id	= (int) $post_id;

$posting_data['post_edit_locked'] = isset($posting_data['post_edit_locked']) ? (int) $posting_data['post_edit_locked'] : false;

$_CLASS['core_user']->add_lang(array('posting', 'mcp', 'viewtopic'));
$_CLASS['core_user']->add_img();

if ($forum_password)
{
	$forum_info = array(
		'forum_id'		=> $forum_id, 
		'forum_password'=> $forum_password
	);
	
	login_forum_box($forum_info);
	unset($forum_info);
}

$post_subject = in_array($mode, array('quote', 'edit', 'delete')) ? $posting_data['post_subject'] : (isset($posting_data['topic_title']) ? $posting_data['topic_title'] : '');
$topic_time_limit = (isset($posting_data['topic_time_limit']) && $posting_data['topic_time_limit']) ? (int) $posting_data['topic_time_limit'] / 86400 : 0;

$poll_length = (isset($poll_length)) ? (($poll_length) ? (int) $poll_length / 86400 : (int) $poll_length) : 0;
$poll_start = (isset($poll_start)) ? (int) $poll_start : 0;
$poll_options = array();

if (!isset($icon_id) || in_array($mode, array('quote', 'reply')))
{
	$icon_id = 0;
}

// Get Poll Data
if ($poll_start)
{
	$sql = 'SELECT poll_option_text 
		FROM ' . FORUMS_POLL_OPTIONS_TABLE . "
		WHERE topic_id = $topic_id
		ORDER BY poll_option_id";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$poll_options[] = trim($row['poll_option_text']);
	}
	$_CLASS['core_db']->free_result($result);
}

$orig_poll_options_size = count($poll_options);
$message_parser = new parse_message();

if (isset($post_text))
{
	$message_parser->message = $post_text;
	unset($post_text);
}

$message_parser->get_submitted_attachment_data();

// Set uninitialized variables
$uninit = array('post_attachment' => 0, 'poster_id' => 0, 'enable_magic_url' => 0, 'topic_status' => ITEM_UNLOCKED, 'topic_type' => POST_NORMAL, 'subject' => '', 'topic_title' => '', 'post_time' => 0, 'post_edit_reason' => '');

foreach ($uninit as $var_name => $default_value)
{
	if (!isset($posting_data[$var_name]))
	{
		$posting_data[$var_name] = $default_value;
	}
}

unset($uninit, $var_name, $default_value);

if ($posting_data['post_attachment'] && !$submit && !$refresh && !$preview && $mode == 'edit')
{
	$sql = 'SELECT attach_id, physical_filename, comment, real_filename, extension, mimetype, filesize, filetime, thumbnail
		FROM ' . FORUMS_ATTACHMENTS_TABLE . "
		WHERE post_msg_id = $post_id
			AND in_message = 0
		ORDER BY filetime " . ((!$config['display_order']) ? 'DESC' : 'ASC');
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$message_parser->attachment_data[] = $row;
	}

	$_CLASS['core_db']->free_result($result);
}

if (!$posting_data['poster_id'] || $posting_data['poster_id'] == ANONYMOUS)
{
	$posting_data['username'] = in_array($mode, array('quote', 'edit', 'delete')) ? trim($posting_data['post_username']) : get_variable('username', 'POST', '');
}
else
{
	$posting_data['username'] = in_array($mode, array('quote', 'edit', 'delete')) ? trim($posting_data['username']) : '';
}

$enable_urls = $posting_data['enable_magic_url'];
$enable_html = isset($enable_html) ? $enable_html : $config['allow_html'];

if (!in_array($mode, array('quote', 'edit', 'delete')))
{
	$enable_sig		= ($config['allow_sig'] && $_CLASS['core_user']->user_data_get('attachsig'));
	$enable_smilies = ($config['allow_smilies'] && $_CLASS['core_user']->user_data_get('smilies'));
	$enable_bbcode	= ($config['allow_bbcode'] && $_CLASS['core_user']->user_data_get('bbcode'));
	$enable_urls	= true;
}

$posting_data['enable_magic_url'] = $drafts = false;

// User own some drafts?
if ($_CLASS['core_user']->is_user && $_CLASS['auth']->acl_get('u_savedrafts') && $mode != 'delete')
{
	$sql = 'SELECT draft_id
		FROM ' . FORUMS_DRAFTS_TABLE . '
		WHERE (forum_id = ' . $forum_id . (($topic_id) ? " OR topic_id = $topic_id" : '') . ')
			AND user_id = ' . $_CLASS['core_user']->data['user_id'] . 
			(($draft_id) ? " AND draft_id <> $draft_id" : '');
	$result = $_CLASS['core_db']->query_limit($sql, 1);

	if ($_CLASS['core_db']->fetch_row_assoc($result))
	{
		$drafts = true;
	}
	$_CLASS['core_db']->free_result($result);
}

$check_value = (($enable_html+1) << 16) + (($enable_bbcode+1) << 8) + (($enable_smilies+1) << 4) + (($enable_urls+1) << 2) + (($enable_sig+1) << 1);

// Notify user checkbox
$notify_set = false;

if ($mode != 'post' && $_CLASS['core_user']->is_user)
{
	$sql = 'SELECT forum_id, topic_id
		FROM ' . FORUMS_WATCH_TABLE . "
		WHERE (forum_id = $forum_id OR topic_id = $topic_id)
			AND user_id = " . $_CLASS['core_user']->data['user_id'];

	$result = $_CLASS['core_db']->query_limit($sql, 1);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$notify_set = ($row['topic_id']) ? 1 : 2;
	}
	$_CLASS['core_db']->free_result($result);
}

// Forum/Topic locked?
// what about if we want to delete/etc ?
if (($posting_data['forum_status'] == ITEM_LOCKED || $posting_data['topic_status'] == ITEM_LOCKED) && !$_CLASS['auth']->acl_get('m_edit', $forum_id))
{
	$message = ($posting_data['forum_status'] == ITEM_LOCKED) ? 'FORUM_LOCKED' : 'TOPIC_LOCKED';
	trigger_error($message);
}

// Can we edit this post ... if we're a moderator with rights then always yes
// else it depends on editing times, lock status and if we're the correct user
// !$preview && !$refresh && !$submit &&
if ($mode == 'edit' && !$preview && !$refresh && !$submit && !$_CLASS['auth']->acl_get('m_edit', $forum_id))
{
	if ($posting_data['post_edit_locked'])
	{
		trigger_error('CANNOT_EDIT_POST_LOCKED');
	}

	if ($_CLASS['core_user']->data['user_id'] != $posting_data['poster_id'])
	{
		trigger_error('USER_CANNOT_EDIT');
	}

	if ($config['edit_time'] && $posting_data['post_time'] > ($current_time - $config['edit_time']))
	{
		trigger_error('CANNOT_EDIT_TIME');
	}
}

// Do we want to edit our post ?

if ($mode == 'edit')
{
	$message_parser->bbcode_uid = $bbcode_uid;
}

// should we alow ip no user deletion ?
// Delete triggered ?
if ($mode == 'delete')
{
	if ($_CLASS['auth']->acl_get('f_delete', $forum_id) && $post_id == $topic_last_post_id && ((!$_CLASS['core_user']->is_user && $posting_data['poster_id'] == ANONYMOUS && $poster_ip && $poster_ip == $_CLASS['core_user']->ip) || ($_CLASS['core_user']->is_user && $posting_data['poster_id'] == $_CLASS['core_user']->data['user_id'])))
	{
		$user_deletable = true;
	}
	else
	{
		$user_deletable = false;
	}
}

if ($mode == 'delete' && ($user_deletable || $_CLASS['auth']->acl_get('m_delete', $forum_id)))
{
	$s_hidden_fields = '<input type="hidden" name="p" value="' . $post_id . '" /><input type="hidden" name="f" value="' . $forum_id . '" /><input type="hidden" name="mode" value="delete" />';

	if (display_confirmation(false, $s_hidden_fields))
	{
		$data = array(
			'topic_first_post_id'=> $topic_first_post_id,
			'topic_last_post_id'=> $topic_last_post_id,
			'topic_approved'	=> $topic_approved,
			'topic_type'		=> $posting_data['topic_type'],
			'post_approved' 	=> $post_approved,
			'post_time'			=> $posting_data['post_time'],
			'poster_id'			=> $posting_data['poster_id']
		);
		
		$next_post_id = delete_post($mode, $post_id, $topic_id, $forum_id, $data);
	
		if ($topic_first_post_id == $topic_last_post_id)
		{
			if (!$user_deletable)
			{
				add_log('mod', $forum_id, $topic_id, 'LOG_DELETE_TOPIC', $posting_data['topic_title']);
			}

			$meta_info = generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id);
			$message = $_CLASS['core_user']->lang['POST_DELETED'];
		}
		else
		{
			if (!$user_deletable)
			{
				add_log('mod', $forum_id, $topic_id, 'LOG_DELETE_POST', $post_subject);
			}

			$meta_info = generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$next_post_id#$next_post_id");
			$message = $_CLASS['core_user']->lang['POST_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$next_post_id#$next_post_id").'">', '</a>');
		}

		$_CLASS['core_display']->meta_refresh(3, $meta_info);
		$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id).'">', '</a>');

		trigger_error($message);
	}
}

if ($mode == 'delete' && $posting_data['poster_id'] != $_CLASS['core_user']->data['user_id'] && !$_CLASS['auth']->acl_get('f_delete', $forum_id))
{
	trigger_error('DELETE_OWN_POSTS');
}

if ($mode == 'delete' && $posting_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['auth']->acl_get('f_delete', $forum_id) && $post_id != $topic_last_post_id)
{
	trigger_error('CANNOT_DELETE_REPLIED');
}

if ($mode == 'delete')
{
	trigger_error('USER_CANNOT_DELETE');
}

// Bump Topic
if ($mode == 'bump' && ($bump_time = bump_topic_allowed($forum_id, $topic_bumped, $topic_last_post_time, $topic_poster, $topic_last_poster_id)))
{
	$_CLASS['core_db']->transaction();

	$_CLASS['core_db']->query('UPDATE ' . FORUMS_POSTS_TABLE . "
		SET post_time = $current_time
		WHERE post_id = $topic_last_post_id
			AND topic_id = $topic_id");

	$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . "
		SET topic_last_post_time = $current_time,
			topic_bumped = 1,
			topic_bumper = " . $_CLASS['core_user']->data['user_id'] . "
		WHERE topic_id = $topic_id");

	$_CLASS['core_db']->query('UPDATE ' . FORUMS_FORUMS_TABLE . '
		SET ' . implode(', ', update_last_post_information('forum', $forum_id)) . "
		WHERE forum_id = $forum_id");

	$_CLASS['core_db']->query('UPDATE ' . USERS_TABLE . "
		SET user_last_post_time = $current_time
		WHERE user_id = " . $_CLASS['core_user']->data['user_id']);

	$_CLASS['core_db']->transaction('commit');

	//markread('topic', $forum_id, $topic_id, $current_time);

	add_log('mod', $forum_id, $topic_id, sprintf($_CLASS['core_user']->lang['LOGM_BUMP'], $posting_data['topic_title']));

	$_CLASS['core_display']->meta_refresh(3, generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$topic_last_post_id#$topic_last_post_id"));

	$message = $_CLASS['core_user']->lang['TOPIC_BUMPED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['VIEW_MESSAGE'], '<a href="'.generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$topic_last_post_id#$topic_last_post_id").'">', '</a>') . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('Forums&amp;file=viewforum&amp;f=' . $forum_id). '">', '</a>');

	trigger_error($message);
}
elseif ($mode == 'bump')
{
	trigger_error('BUMP_ERROR');
}

// Save Draft
if ($save && $_CLASS['core_user']->is_user && $_CLASS['auth']->acl_get('u_savedrafts'))
{
	$subject = request_var('subject', '', true);
	$subject = (!$subject && $mode != 'post') ? $posting_data['topic_title'] : $subject;
	$message = request_var('message', '', true);

	if ($subject && $message)
	{
		$sql = 'INSERT INTO ' . FORUMS_DRAFTS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'user_id'	=> $_CLASS['core_user']->data['user_id'],
			'topic_id'	=> $topic_id,
			'forum_id'	=> $forum_id,
			'save_time'	=> $current_time,
			'draft_subject' => $subject,
			'draft_message' => $message));
		$_CLASS['core_db']->query($sql);
	
		$meta_info = ($mode == 'post') ? generate_link('Forums&amp;file=viewforum&amp;f='.$forum_id) : generate_link("Forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id");

		$_CLASS['core_display']->meta_refresh(3, $meta_info);

		$message = $_CLASS['core_user']->lang['DRAFT_SAVED'] . '<br /><br />';
		$message .= ($mode != 'post') ? sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="' . $meta_info . '">', '</a>') . '<br /><br />' : '';
		$message .= sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('Forums&amp;file=viewforum&amp;f=' . $forum_id) . '">', '</a>');

		trigger_error($message);
	}

	unset($subject);
	unset($message);
}

// Move to where they should be
$_CLASS['core_template']->assign_array(array(
		'S_DRAFT_LOADED'	=> false,
		'S_SHOW_DRAFTS'		=> false,
		'S_POST_REVIEW'		=> false,
		'S_DISPLAY_PREVIEW'	=> false,
		'S_UNGLOBALISE'		=> false,
		'S_INLINE_ATTACHMENT_OPTIONS' => false,
		'S_TOPIC_TYPE_ANNOUNCE' => false,
		'S_TOPIC_TYPE_STICKY' => false,
		'S_DISPLAY_REVIEW'	=> false,
));

// Load Draft
if ($draft_id && $_CLASS['core_user']->is_user && $_CLASS['auth']->acl_get('u_savedrafts'))
{
	$sql = 'SELECT draft_subject, draft_message 
		FROM ' . FORUMS_DRAFTS_TABLE . " 
		WHERE draft_id = $draft_id
			AND user_id = " . $_CLASS['core_user']->data['user_id'];
	$result = $_CLASS['core_db']->query_limit($sql, 1);
	
	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$_REQUEST['subject'] = $row['draft_subject'];
		$_REQUEST['message'] = $row['draft_message'];

		$refresh = true;
		$_CLASS['core_template']->assign('S_DRAFT_LOADED', true);
	}
	else
	{
		$draft_id = 0;
	}
}

// HTML, BBCode, Smilies, Images and Flash status
$html_status	= ($config['allow_html'] && $_CLASS['auth']->acl_get('f_html', $forum_id));
$bbcode_status	= ($config['allow_bbcode'] && $_CLASS['auth']->acl_get('f_bbcode', $forum_id));
$smilies_status	= ($config['allow_smilies'] && $_CLASS['auth']->acl_get('f_smilies', $forum_id));
$img_status		= ($_CLASS['auth']->acl_get('f_img', $forum_id));
$flash_status	= ($_CLASS['auth']->acl_get('f_flash', $forum_id));
$quote_status	= ($_CLASS['auth']->acl_get('f_quote', $forum_id));

// Load Drafts
if ($load && $drafts)
{
	load_drafts($topic_id, $forum_id);
}

if ($submit || $preview || $refresh)
{
	$posting_data['post_edit_reason'] = ($mode == 'edit' && isset($_POST['edit_reason']) && !empty($_POST['edit_reason']) && $_CLASS['core_user']->data['user_id'] != $posting_data['poster_id']) ? get_variable('edit_reason', 'POST', '') : '';
	$posting_data['topic_type'] = isset($_POST['topic_type']) ? (int) $_POST['topic_type'] : (($mode != 'post') ? $posting_data['topic_type'] : POST_NORMAL);

	$topic_cur_post_id	= get_variable('topic_cur_post_id', 'POST', 0, 'int');

	$subject = mb_strtolower(htmlentities(get_variable('subject', 'POST', ''), ENT_QUOTES, 'UTF-8'));
	$message_parser->message = request_var('message', '', true);


	$topic_time_limit	= (isset($_POST['topic_time_limit'])) ? (int) $_POST['topic_time_limit'] : (($mode != 'post') ? $topic_time_limit : 0);
	$icon_id			= get_variable('icon', 'POST', 0, 'int');

	$enable_html 		= (!$html_status || isset($_POST['disable_html'])) ? false : true;
	$enable_bbcode 		= (!$bbcode_status || isset($_POST['disable_bbcode'])) ? false : true;
	$enable_smilies		= (!$smilies_status || isset($_POST['disable_smilies'])) ? false : true;
	$enable_urls 		= isset($_POST['disable_magic_url']) ? 0 : 1;
	$enable_sig			= (!$config['allow_sig']) ? false : (($_CLASS['core_user']->is_user && isset($_POST['attach_sig'])) ? true : false);

	$notify				= isset($_POST['notify']);
	$topic_lock			= isset($_POST['lock_topic']);
	$post_lock			= isset($_POST['lock_post']);

	$poll_delete		= isset($_POST['poll_delete']);
	
	// Faster than crc32
	if ($submit)
	{
		$status_switch  = (($enable_html+1) << 16) + (($enable_bbcode+1) << 8) + (($enable_smilies+1) << 4) + (($enable_urls+1) << 2) + (($enable_sig+1) << 1);
		$status_switch = ($status_switch != $check_value);
	}
	else
	{
		$status_switch = 1;
	}

	// Delete Poll
	if ($poll_delete && $mode == 'edit' && $poll_options && 
		((!$poll_last_vote && $posting_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['auth']->acl_get('f_delete', $forum_id)) || $_CLASS['auth']->acl_get('m_delete', $forum_id)))
	{
		switch (SQL_LAYER)
		{
			case 'mysql4':
			case 'mysqli':
				$sql = 'DELETE FROM ' . POLL_OPTIONS_TABLE . ', ' . POLL_VOTES_TABLE . "
					WHERE topic_id = $topic_id";
				$_CLASS['core_db']->query($sql);
				break;

			default:
				$sql = 'DELETE FROM ' . POLL_OPTIONS_TABLE . "
					WHERE topic_id = $topic_id";
				$_CLASS['core_db']->query($sql);

				$sql = 'DELETE FROM ' . POLL_VOTES_TABLE . "
					WHERE topic_id = $topic_id";
				$_CLASS['core_db']->query($sql);
		}
		
		$topic_sql = array(
			'poll_title'		=> '',
			'poll_start' 		=> 0,
			'poll_length'		=> 0,
			'poll_last_vote'	=> 0,
			'poll_max_options'	=> 0,
			'poll_vote_change'	=> 0
		);

		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $topic_sql) . "
			WHERE topic_id = $topic_id";
		$_CLASS['core_db']->query($sql);

		$poll_title = $poll_option_text = '';
		$poll_vote_change = $poll_max_options = $poll_length = 0;
	}
	else
	{
		$poll_title			= request_var('poll_title', '');
		$poll_length		= request_var('poll_length', 0);
		$poll_option_text	= request_var('poll_option_text', '');
		$poll_max_options	= request_var('poll_max_options', 1);
		$poll_vote_change	= ($_CLASS['auth']->acl_get('f_votechg', $forum_id) && isset($_POST['poll_vote_change'])) ? 1 : 0;
	}

	// If replying/quoting and last post id has changed
	// give user option to continue submit or return to post
	// notify and show user the post made between his request and the final submit
	if (($mode == 'reply' || $mode == 'quote') && $topic_cur_post_id && $topic_cur_post_id != $topic_last_post_id)
	{
		if (topic_review($topic_id, $forum_id, 'post_review', $topic_cur_post_id))
		{
			$_CLASS['core_template']->assign('S_POST_REVIEW',  true);
		}
		$submit = false;
		$refresh = true;
	}

	// Parse Attachments - before checksum is calculated
	$message_parser->parse_attachments('fileupload', $mode, $forum_id, $submit, $preview, $refresh);

	// Grab md5 'checksum' of new message
	$message_md5 = md5($message_parser->message);

	// Check checksum ... don't re-parse message if the same
	$update_message = ($mode != 'edit' || $message_md5 != $post_checksum || $status_switch) ? true : false;
	
	// Parse message
	if ($update_message)
	{
		$message_parser->parse($enable_html, $enable_bbcode, $enable_urls, $enable_smilies, $img_status, $flash_status, $quote_status);
	}
	else
	{
		$message_parser->bbcode_bitfield = $bbcode_bitfield;
	}

	if ($mode != 'edit' && !$preview && !$refresh && $config['flood_interval'] && !$_CLASS['auth']->acl_get('f_ignoreflood', $forum_id))
	{
		// Flood check
		$last_post_time = 0;

		if ($_CLASS['core_user']->is_user)
		{
			$last_post_time = $_CLASS['core_user']->data['user_last_post_time'];
		}
		else
		{
			$sql = 'SELECT post_time AS last_post_time
				FROM ' . FORUMS_POSTS_TABLE . "
				WHERE poster_ip = '" . $_CLASS['core_user']->ip . "'
					AND post_time > " . ($current_time - $config['flood_interval']);
			$result = $_CLASS['core_db']->query_limit($sql, 1);

			if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$last_post_time = $row['last_post_time'];
			}
			$_CLASS['core_db']->free_result($result);
		}

		if ($last_post_time)
		{
			if ($last_post_time && ($current_time - $last_post_time) < intval($config['flood_interval']))
			{
				$error[] = $_CLASS['core_user']->get_lang('FLOOD_ERROR');
			}
		}
	}

	// Validate username
	if ($posting_data['username'] && (!$_CLASS['core_user']->is_user || ($mode == 'edit' && (!$posting_data['poster_id'] || $posting_data['poster_id'] == ANONYMOUS))))
	{
		require_once(SITE_FILE_ROOT.'includes/functions_user.php');
		$result = validate_username($posting_data['username']);

		if ($result !== true)
		{
			$error[] = $_CLASS['core_user']->get_lang($result);
		}
	}

	// Parse subject
	if (!$subject && ($mode == 'post' || ($mode == 'edit' && $posting_data['topic_first_post_id'] == $posting_data['post_id'])))
	{
		$error[] = $_CLASS['core_user']->get_lang('EMPTY_SUBJECT');
	}

	$poll_last_vote = (isset($poll_last_vote)) ? $poll_last_vote : 0;

	if ($poll_option_text && 
			($mode == 'post' || ($mode == 'edit' && $post_id == $topic_first_post_id && (!$poll_last_vote || $_CLASS['auth']->acl_get('m_edit', $forum_id))))
			&& $_CLASS['auth']->acl_get('f_poll', $forum_id))
	{
		$poll = array(
			'poll_title'		=> $poll_title,
			'poll_length'		=> $poll_length,
			'poll_max_options'	=> $poll_max_options,
			'poll_option_text'	=> $poll_option_text,
			'poll_start'		=> $poll_start,
			'poll_last_vote'	=> $poll_last_vote,
			'poll_vote_change'	=> $poll_vote_change,
			'enable_html'		=> $enable_html,
			'enable_bbcode'		=> $enable_bbcode,
			'enable_urls'		=> $enable_urls,
			'enable_smilies'	=> $enable_smilies,
			'img_status'		=> $img_status
		);

		$message_parser->parse_poll($poll);
	
		$poll_options = isset($poll['poll_options']) ? $poll['poll_options'] : '';
		$poll_title = isset($poll['poll_title']) ? $poll['poll_title'] : '';

		if ($poll_last_vote && ($poll['poll_options_size'] < $orig_poll_options_size))
		{
			$message_parser->warn_msg[] = $_CLASS['core_user']->lang['NO_DELETE_POLL_OPTIONS'];
		}
	}
	else
	{
		$poll = array();
	}

	// Check topic type
	if ($posting_data['topic_type'] != POST_NORMAL && ($mode == 'post' || ($mode == 'edit' && $posting_data['topic_first_post_id'] == $posting_data['post_id'])))
	{
		switch ($posting_data['topic_type'])
		{
			case POST_GLOBAL:
			case POST_ANNOUNCE:
				$auth_option = 'f_announce';
			break;

			case POST_STICKY:
				$auth_option = 'f_sticky';
			break;

			default:
				$auth_option = '';
			break;	
		}

		if (!$_CLASS['auth']->acl_get($auth_option, $forum_id))
		{
			$error[] = $_CLASS['core_user']->lang['CANNOT_POST_' . str_replace('F_', '', strtoupper($auth_option))];
		}
	}

	if (!empty($message_parser->warn_msg))
	{
		$error[] = implode('<br />', $message_parser->warn_msg);
	}

	// Store message, sync counters
	if (empty($error) && $submit)
	{
		// Check if we want to de-globalize the topic... and ask for new forum
		if ($posting_data['topic_type'] != POST_GLOBAL)
		{
			$sql = 'SELECT topic_type, forum_id
				FROM ' . FORUMS_TOPICS_TABLE . "
				WHERE topic_id = $topic_id";
			$result = $_CLASS['core_db']->query_limit($sql, 1);

			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			
			if ($row && !$row['forum_id'] && $row['topic_type'] == POST_GLOBAL)
			{
				$to_forum_id = request_var('to_forum_id', 0);
	
				if (!$to_forum_id)
				{
					$_CLASS['core_template']->assign_array(array(
						'S_FORUM_SELECT'	=> make_forum_select(false, false, false, true, true),
						'S_UNGLOBALISE'		=> true) 
					);
			
					$submit = false;
					$refresh = true;
				}
				else
				{
					$forum_id = $to_forum_id;
				}
			}
		}

		if ($submit)
		{
			// Lock/Unlock Topic
			$change_topic_status = $posting_data['topic_status'];
			$perm_lock_unlock = ($_CLASS['auth']->acl_get('m_lock', $forum_id) || ($_CLASS['auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_id'] == $topic_poster));

			if ($posting_data['topic_status'] == ITEM_LOCKED && !$topic_lock && $perm_lock_unlock)
			{
				$change_topic_status = ITEM_UNLOCKED;
			}
			else if ($posting_data['topic_status'] == ITEM_UNLOCKED && $topic_lock && $perm_lock_unlock)
			{
				$change_topic_status = ITEM_LOCKED;
			}
		
			if ($change_topic_status != $posting_data['topic_status'])
			{
				$posting_data['topic_status'] = $change_topic_status;

				$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . "
					SET topic_status = $change_topic_status
					WHERE topic_id = $topic_id
						AND topic_moved_id = 0";
				$_CLASS['core_db']->query($sql);
			
				$user_lock = ($_CLASS['auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_id'] == $topic_poster) ? 'USER_' : '';

				//add_log('mod', $forum_id, $topic_id, 'LOG_' . $user_lock . (($change_topic_status == ITEM_LOCKED) ? 'LOCK' : 'UNLOCK'), $posting_data['topic_title']);
			}

			// Lock/Unlock Post Edit
			if ($mode == 'edit' && $posting_data['post_edit_locked'] == ITEM_LOCKED && !$post_lock && $_CLASS['auth']->acl_get('m_edit', $forum_id))
			{
				$posting_data['post_edit_locked'] = ITEM_UNLOCKED;
			}
			else if ($mode == 'edit' && $posting_data['post_edit_locked'] == ITEM_UNLOCKED && $post_lock && $_CLASS['auth']->acl_get('m_edit', $forum_id))
			{
				$posting_data['post_edit_locked'] = ITEM_LOCKED;
			}

			$post_data = array(
				'topic_title'			=> (!$posting_data['topic_title']) ? $subject : $posting_data['topic_title'],
				'topic_first_post_id'	=> (isset($topic_first_post_id)) ? (int) $topic_first_post_id : 0,
				'topic_last_post_id'	=> (isset($topic_last_post_id)) ? (int) $topic_last_post_id : 0,
				'topic_time_limit'		=> (int) $topic_time_limit,
				'topic_status'			=> (int) $posting_data['topic_status'],
				'post_id'				=> (int) $post_id,
				'topic_id'				=> (int) $topic_id,
				'forum_id'				=> (int) $forum_id,
				'icon_id'				=> (int) $icon_id,
				'poster_id'				=> (int) $posting_data['poster_id'],
				'enable_sig'			=> (bool) $enable_sig,
				'enable_bbcode'			=> (bool) $enable_bbcode,
				'enable_html' 			=> (bool) $enable_html,
				'enable_smilies'		=> (bool) $enable_smilies,
				'enable_urls'			=> (bool) $enable_urls,
				'enable_indexing'		=> (bool) $enable_indexing,
				'message_md5'			=> (string) $message_md5,
				'post_time'				=> ($posting_data['post_time']) ? (int) $posting_data['post_time'] : $current_time,
				'post_checksum'			=> (isset($post_checksum)) ? (string) $post_checksum : '',
				'post_edit_reason'		=> $posting_data['post_edit_reason'],
				'post_edit_user'		=> ($mode == 'edit') ? $_CLASS['core_user']->data['user_id'] : ((isset($post_edit_user)) ? (int) $post_edit_user : 0),
				'forum_parents'			=> $forum_parents,
				'forum_name'			=> $forum_name,
				'notify'				=> $notify,
				'notify_set'			=> $notify_set,
				'poster_ip'				=> (isset($poster_ip)) ? (int) $poster_ip : $_CLASS['core_user']->ip,
				'post_edit_locked'		=> (int) $posting_data['post_edit_locked'],
				'bbcode_bitfield'		=> (int) $message_parser->bbcode_bitfield,
				'bbcode_uid'			=> $message_parser->bbcode_uid,
				'message'				=> $message_parser->message,
				'attachment_data'		=> $message_parser->attachment_data,
				'filename_data'			=> $message_parser->filename_data
			);
			unset($message_parser);
			
			submit_post($mode, $subject, $posting_data['username'], $posting_data['topic_type'], $poll, $post_data, $update_message);
		}
	}	

	$post_subject = $subject;
}

// Preview
if (empty($error) && $preview)
{
	$posting_data['post_time'] = ($mode == 'edit') ? $posting_data['post_time'] : $current_time;

	$preview_message = $message_parser->format_display($enable_html, $enable_bbcode, $enable_urls, $enable_smilies, false);

	$preview_signature = ($mode == 'edit') ? $user_sig : $_CLASS['core_user']->data['user_sig'];
	$preview_signature_uid = ($mode == 'edit') ? $user_sig_bbcode_uid : $_CLASS['core_user']->data['user_sig_bbcode_uid'];
	$preview_signature_bitfield = ($mode == 'edit') ? $user_sig_bbcode_bitfield : $_CLASS['core_user']->data['user_sig_bbcode_bitfield'];

	// Signature
	if ($enable_sig && $config['allow_sig'] && $preview_signature && $_CLASS['auth']->acl_get('f_sigs', $forum_id))
	{
		$parse_sig = new parse_message($preview_signature);
		$parse_sig->bbcode_uid = $preview_signature_uid;
		$parse_sig->bbcode_bitfield = $preview_signature_bitfield;

		// Not sure about parameters for bbcode/smilies/urls... in signatures
		$parse_sig->format_display($config['allow_html'], $config['allow_bbcode'], true, $config['allow_smilies']);
		$preview_signature = $parse_sig->message;
		unset($parse_sig);
	}
	else
	{
		$preview_signature = '';
	}
	
	$preview_subject = censor_text($subject);
	
	// Poll Preview
	if (($mode == 'post' || ($mode == 'edit' && $post_id == $topic_first_post_id && (!$poll_last_vote || $_CLASS['auth']->acl_get('m_edit', $forum_id))))
	&& $_CLASS['auth']->acl_get('f_poll', $forum_id))
	{
		$parse_poll = new parse_message($poll_title);
		$parse_poll->bbcode_uid = $message_parser->bbcode_uid;
		$parse_poll->bbcode_bitfield = $message_parser->bbcode_bitfield;

		$parse_poll->format_display($enable_html, $enable_bbcode, $enable_urls, $enable_smilies);
		
		$_CLASS['core_template']->assign_array(array(
			'S_HAS_POLL_OPTIONS'=> !empty($poll_options),
			'S_IS_MULTI_CHOICE'	=> ($poll_max_options > 1) ? true : false,

			'POLL_QUESTION'		=> $parse_poll->message,
			
			'L_POLL_LENGTH'		=> ($poll_length) ? sprintf($_CLASS['core_user']->lang['POLL_RUN_TILL'], $_CLASS['core_user']->format_date($poll_length + $poll_start)) : '',
			'L_MAX_VOTES'		=> ($poll_max_options == 1) ? $_CLASS['core_user']->lang['MAX_OPTION_SELECT'] : sprintf($_CLASS['core_user']->lang['MAX_OPTIONS_SELECT'], $poll_max_options))
		);
		
		$parse_poll->message = implode("\n", $poll_options);
		$parse_poll->format_display($enable_html, $enable_bbcode, $enable_urls, $enable_smilies);
		$preview_poll_options = explode('<br />', $parse_poll->message);
		unset($parse_poll);
		
		foreach ($preview_poll_options as $option)
		{
			$_CLASS['core_template']->assign_vars_array('poll_option', array('POLL_OPTION_CAPTION' => $option));
		}
		unset($preview_poll_options);
	}

	$_CLASS['core_template']->assign('S_HAS_ATTACHMENTS', false);

	// Attachment Preview
	if (!empty($message_parser->attachment_data))
	{
		require_once(SITE_FILE_ROOT.'includes/forums/functions_display.php');
		$null = array();

		$attachment_data = $message_parser->attachment_data;

		$unset_attachments = parse_inline_attachments($preview_message, $attachment_data, $update_count, $forum_id, true);

		// Needed to let not display the inlined attachments at the end of the post again
		foreach ($unset_attachments as $index)
		{
			unset($attachment_data[$index]);
		}
		unset($unset_attachments);
			
		if (!empty($attachment_data))
		{
			$_CLASS['core_template']->assign('S_HAS_ATTACHMENTS', true);
			$_CLASS['core_template']->assign('attachment', display_attachments($forum_id, $attachment_data, $null, true));
		}

		unset($attachment_data, $null);
	}

	$_CLASS['core_template']->assign_array(array(
		'PREVIEW_SUBJECT'		=> $preview_subject,
		'PREVIEW_MESSAGE'		=> $preview_message,
		'PREVIEW_SIGNATURE'		=> $preview_signature,

		'S_DISPLAY_PREVIEW'		=> true
	));

	unset($preview_message, $preview_subject, $preview_signature, $preview_signature);
}

// Decode text for message display
$bbcode_uid = ($mode == 'quote' && !$preview && !$refresh && empty($error)) ? $bbcode_uid : $message_parser->bbcode_uid;
$message_parser->decode_message($bbcode_uid);

if ($mode == 'quote' && !$preview && !$refresh)
{
	$quote_username = isset($posting_data['username']) ? $posting_data['username'] : (isset($posting_data['post_username']) ? $posting_data['post_username'] : '');
	$message_parser->message = '[quote="' . $quote_username . '"]' . censor_text(trim($message_parser->message)) . "[/quote]\n";
}

if (($mode == 'reply' || $mode == 'quote') && !$preview && !$refresh)
{
	$post_subject = ((!preg_match('/^Re:/', $post_subject)) ? 'Re: ' : '') . censor_text($post_subject);
}

$attachment_data = $message_parser->attachment_data;
$filename_data = $message_parser->filename_data;
$post_text = $message_parser->message;

if (!empty($poll_options) && $poll_title)
{
	$message_parser->message = $poll_title;
	$message_parser->bbcode_uid = $bbcode_uid;

	$message_parser->decode_message();
	$poll_title = $message_parser->message;

	$message_parser->message = implode("\n", $poll_options);
	$message_parser->decode_message();
	$poll_options = explode("\n", $message_parser->message);
}
unset($message_parser);

// MAIN POSTING PAGE BEGINS HERE

// Forum moderators?
$moderators = get_moderators($forum_id);

// Generate smiley listing
generate_smilies('inline', $forum_id);

// Generate inline attachment select box
posting_gen_inline_attachments($attachment_data);

// Do show topic type selection only in first post.
$topic_type_toggle = false;

if ($mode == 'post' || ($mode == 'edit' && $post_id == $topic_first_post_id))
{
	$topic_type_toggle = posting_gen_topic_types($forum_id, $posting_data['topic_type']);
}

$s_topic_icons = false;

if ($enable_icons)
{
	$s_topic_icons = posting_gen_topic_icons($mode, $icon_id);
}



$html_checked		= (isset($enable_html)) ? !$enable_html : (($config['allow_html']) ? !$_CLASS['core_user']->user_data_get('html') : 1);
$bbcode_checked		= (isset($enable_bbcode)) ? !$enable_bbcode : (($config['allow_bbcode']) ? !$_CLASS['core_user']->user_data_get('bbcode') : 1);
$smilies_checked	= (isset($enable_smilies)) ? !$enable_smilies : (($config['allow_smilies']) ? !$_CLASS['core_user']->user_data_get('smilies') : 1);
$urls_checked		= (isset($enable_urls)) ? !$enable_urls : 0;
$sig_checked		= $enable_sig;
$notify_checked		= (isset($notify)) ? $notify : ((!$notify_set) ? (($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['user_notify'] : 0) : 1);
$lock_topic_checked	= (isset($topic_lock)) ? $topic_lock : (($posting_data['topic_status'] == ITEM_LOCKED) ? 1 : 0);
$lock_post_checked	= isset($post_lock) ? $post_lock : $posting_data['post_edit_locked'];

// Page title & action URL, include session_id for security purpose
$s_action = "Forums&amp;file=posting&amp;mode=$mode&amp;f=$forum_id";
$s_action .= ($topic_id) ? "&amp;t=$topic_id" : '';
$s_action .= ($post_id) ? "&amp;p=$post_id" : '';
$s_action = generate_link($s_action);

switch ($mode)
{
	case 'post':
		$page_title = $_CLASS['core_user']->lang['POST_TOPIC'];
		break;

	case 'quote':
	case 'reply':
		$page_title = $_CLASS['core_user']->lang['POST_REPLY'];
		break;

	case 'delete':
	case 'edit':
		$page_title = $_CLASS['core_user']->lang['EDIT_POST'];
}

$forum_data = array(
	'parent_id'		=> $parent_id,
	'left_id'		=> $left_id,
	'right_id'		=> $right_id,
	'forum_parents'	=> $forum_parents,
	'forum_name'	=> $forum_name,
	'forum_id'		=> $forum_id,
	'forum_type'	=> $posting_data['forum_type'],
	'forum_desc'	=> $forum_desc,
	'forum_rules'	=> $forum_rules,
	'forum_rules_flags' => $forum_rules_flags,
	'forum_rules_bbcode_uid' => $forum_rules_bbcode_uid,
	'forum_rules_bbcode_bitfield' => $forum_rules_bbcode_bitfield,
	'forum_rules_link' => $forum_rules_link
);

// Build Navigation Links
generate_forum_nav($forum_data);

// Build Forum Rules
generate_forum_rules($forum_data);

$s_hidden_fields = ($mode == 'reply' || $mode == 'quote') ? '<input type="hidden" name="topic_cur_post_id" value="' . $topic_last_post_id . '" />' : '';
$s_hidden_fields .= ($draft_id || isset($_REQUEST['draft_loaded'])) ? '<input type="hidden" name="draft_loaded" value="' . ((isset($_REQUEST['draft_loaded'])) ? intval($_REQUEST['draft_loaded']) : $draft_id) . '" />' : '';

$form_enctype = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off' || @ini_get('file_uploads') == '0' || !$config['allow_attachments'] || !$_CLASS['auth']->acl_gets(array('f_attach', 'u_attach'), $forum_id)) ? '' : ' enctype="multipart/form-data"';

// Start assigning vars for main posting page ...
$_CLASS['core_template']->assign_array(array(
	'L_POST_A'				=> $page_title,
	'L_ICON'				=> ($mode == 'reply' || $mode == 'quote') ? $_CLASS['core_user']->lang['POST_ICON'] : $_CLASS['core_user']->lang['TOPIC_ICON'], 
	'L_MESSAGE_BODY_EXPLAIN'=> (intval($config['max_post_chars'])) ? sprintf($_CLASS['core_user']->lang['MESSAGE_BODY_EXPLAIN'], intval($config['max_post_chars'])) : '',
	
	'FORUM_NAME' 			=> $forum_name,
	'FORUM_DESC'			=> ($forum_desc) ? strip_tags($forum_desc) : '',
	'TOPIC_TITLE' 			=> $posting_data['topic_title'],
	'MODERATORS' 			=> empty($moderators) ? '' : implode(', ', $moderators[$forum_id]),
	'USERNAME'				=> ((!$preview && $mode != 'quote') || $preview) ? $posting_data['username'] : '',
	'SUBJECT'				=> $post_subject,
	'MESSAGE'				=> $post_text,
	'HTML_STATUS'			=> ($html_status) ? $_CLASS['core_user']->lang['HTML_IS_ON'] : $_CLASS['core_user']->lang['HTML_IS_OFF'],
	'BBCODE_STATUS'			=> ($bbcode_status) ? sprintf($_CLASS['core_user']->lang['BBCODE_IS_ON'], '<a href="' . generate_link('Forums&amp;file=faq&amp;mode=bbcode') . '" target="phpbbcode" onclick="window.open(\''.generate_link('Forums&amp;file=faq&amp;mode=bbcode')."', '_phpbbcode', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=740');return false\">", '</a>') : sprintf($_CLASS['core_user']->lang['BBCODE_IS_OFF'], '<a href="' . generate_link('Forums&amp;file=faq&amp;mode=bbcode') . '" target="_phpbbcode">', '</a>'),
	'IMG_STATUS'			=> ($img_status) ? $_CLASS['core_user']->lang['IMAGES_ARE_ON'] : $_CLASS['core_user']->lang['IMAGES_ARE_OFF'],
	'FLASH_STATUS'			=> ($flash_status) ? $_CLASS['core_user']->lang['FLASH_IS_ON'] : $_CLASS['core_user']->lang['FLASH_IS_OFF'],
	'SMILIES_STATUS'		=> ($smilies_status) ? $_CLASS['core_user']->lang['SMILIES_ARE_ON'] : $_CLASS['core_user']->lang['SMILIES_ARE_OFF'],
	'MINI_POST_IMG'			=> $_CLASS['core_user']->img('icon_post', $_CLASS['core_user']->lang['POST']),
	'POST_DATE'				=> ($posting_data['post_time']) ? $_CLASS['core_user']->format_date($posting_data['post_time']) : '',
	'ERROR'					=> empty($error) ? '' : implode('<br />', $error), 
	'TOPIC_TIME_LIMIT'		=> (int) $topic_time_limit,
	'EDIT_REASON'			=> $posting_data['post_edit_reason'],

	'U_VIEW_FORUM' 			=> generate_link('Forums&amp;file=viewforum&amp;f=' . $forum_id),
	'U_VIEWTOPIC' 			=> ($mode != 'post') ? generate_link("Forums&amp;file=viewtopic&amp;$forum_id&amp;t=$topic_id") : '',

	'S_EDIT_POST'			=> ($mode == 'edit'),
	'S_EDIT_REASON'			=> ($mode == 'edit' && $_CLASS['core_user']->data['user_id'] != $posting_data['poster_id']),
	'S_DISPLAY_USERNAME'	=> (!$_CLASS['core_user']->is_user || ($mode == 'edit' && $post_username)),
	'S_SHOW_TOPIC_ICONS'	=> $s_topic_icons,
	'S_DELETE_ALLOWED' 		=> ($mode == 'edit' && (($post_id == $topic_last_post_id && $posting_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['auth']->acl_get('f_delete', $forum_id)) || $_CLASS['auth']->acl_get('m_delete', $forum_id))),
	'S_HTML_ALLOWED'		=> $html_status,
	'S_HTML_CHECKED' 		=> ($html_checked) ? ' checked="checked"' : '',
	'S_BBCODE_ALLOWED'		=> $bbcode_status,
	'S_BBCODE_CHECKED' 		=> ($bbcode_checked) ? ' checked="checked"' : '',
	'S_SMILIES_ALLOWED'		=> $smilies_status,
	'S_SMILIES_CHECKED' 	=> ($smilies_checked) ? ' checked="checked"' : '',
	'S_SIG_ALLOWED'			=> ($_CLASS['auth']->acl_get('f_sigs', $forum_id) && $config['allow_sig'] && $_CLASS['core_user']->is_user),
	'S_SIGNATURE_CHECKED' 	=> ($sig_checked) ? ' checked="checked"' : '',
	'S_NOTIFY_ALLOWED'		=> ($_CLASS['core_user']->is_user),
	'S_NOTIFY_CHECKED' 		=> ($notify_checked) ? ' checked="checked"' : '',
	'S_LOCK_TOPIC_ALLOWED'	=> (($mode == 'edit' || $mode == 'reply' || $mode == 'quote') && ($_CLASS['auth']->acl_get('m_lock', $forum_id) || ($_CLASS['auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && $_CLASS['core_user']->data['user_id'] == $topic_poster))),
	'S_LOCK_TOPIC_CHECKED'	=> ($lock_topic_checked) ? ' checked="checked"' : '',
	'S_LOCK_POST_ALLOWED'	=> ($mode == 'edit' && $_CLASS['auth']->acl_get('m_edit', $forum_id)),
	'S_LOCK_POST_CHECKED'	=> ($lock_post_checked) ? ' checked="checked"' : '',
	'S_MAGIC_URL_CHECKED' 	=> ($urls_checked) ? ' checked="checked"' : '',
	'S_TYPE_TOGGLE'			=> $topic_type_toggle,
	'S_SAVE_ALLOWED'		=> ($_CLASS['auth']->acl_get('u_savedrafts') && $_CLASS['core_user']->is_user),
	'S_HAS_DRAFTS'			=> ($_CLASS['auth']->acl_get('u_savedrafts') && $_CLASS['core_user']->is_user && $drafts),
	'S_FORM_ENCTYPE'		=> $form_enctype,

	'S_POST_ACTION' 		=> $s_action,
	'S_HIDDEN_FIELDS'		=> $s_hidden_fields)
);

// Poll entry
if (($mode == 'post' || ($mode == 'edit' && $post_id == $topic_first_post_id && (!$poll_last_vote || $_CLASS['auth']->acl_get('m_edit', $forum_id))))
	&& $_CLASS['auth']->acl_get('f_poll', $forum_id))
{
	$_CLASS['core_template']->assign_array(array(
		'S_SHOW_POLL_BOX'		=> true,
		'S_POLL_VOTE_CHANGE'    => ($_CLASS['auth']->acl_get('f_votechg', $forum_id)),
		'S_POLL_DELETE'			=> ($mode == 'edit' && $poll_options && ((!$poll_last_vote && $posting_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['auth']->acl_get('f_delete', $forum_id)) || $_CLASS['auth']->acl_get('m_delete', $forum_id))),

		'L_POLL_OPTIONS_EXPLAIN'=> sprintf($_CLASS['core_user']->lang['POLL_OPTIONS_EXPLAIN'], $config['max_poll_options']),
		'VOTE_CHANGE_CHECKED'   => (isset($poll_vote_change) && $poll_vote_change) ? ' checked="checked"' : '',
		'POLL_TITLE' 			=> (isset($poll_title)) ? $poll_title : '',
		'POLL_OPTIONS'			=> (isset($poll_options) && $poll_options) ? implode("\n", $poll_options) : '',
		'POLL_MAX_OPTIONS'		=> (isset($poll_max_options)) ? (int) $poll_max_options : 1, 
		'POLL_LENGTH' 			=> $poll_length)
	);
}
else
{
	$_CLASS['core_template']->assign_array(array(
		'S_SHOW_POLL_BOX'		=> false,
		'S_POLL_DELETE'			=> false,
	));

}

// Attachment entry
if ($form_enctype)
{
	posting_gen_attachment_entry($attachment_data, $filename_data);
}
else
{
	$_CLASS['core_template']->assign('S_SHOW_ATTACH_BOX', false);
}

// Output page ...

page_header();

// Topic review
if ($mode == 'reply' || $mode == 'quote')
{
	if (topic_review($topic_id, $forum_id))
	{
		$_CLASS['core_template']->assign('S_DISPLAY_REVIEW', true);
	}
}

make_jumpbox(generate_link('Forums&amp;file=viewforum'));

$_CLASS['core_template']->display('modules/Forums/posting_body.html');

// ---------
// FUNCTIONS
//

// Delete Post
function delete_post($mode, $post_id, $topic_id, $forum_id, &$data)
{
	global $config, $_CLASS;

	// Specify our post mode
	$post_mode = ($data['topic_first_post_id'] == $data['topic_last_post_id']) ? 'delete_topic' : (($data['topic_first_post_id'] == $post_id) ? 'delete_first_post' : (($data['topic_last_post_id'] == $post_id) ? 'delete_last_post' : 'delete'));
	$sql_data = array();
	$next_post_id = 0;

	$_CLASS['core_db']->transaction();

	if (!delete_posts('post_id', array($post_id), false))
	{
		// Try to delete topic, we may had an previous error causing inconsistency
		/*
		if ($post_mode = 'delete_topic')
		{
			delete_topics('topic_id', array($topic_id), false);
		}
		*/
		trigger_error('ALREADY_DELETED');
	}

	$_CLASS['core_db']->transaction('commit');

	// Collect the necessary informations for updating the tables
	$sql_data[FORUMS_FORUMS_TABLE] = '';

	switch ($post_mode)
	{
		case 'delete_topic':
			delete_topics('topic_id', array($topic_id), false);
			set_config('num_topics', $config['num_topics'] - 1, true);

			if ($data['topic_type'] != POST_GLOBAL)
			{
				$sql_data[FORUMS_FORUMS_TABLE] .= 'forum_posts = forum_posts - 1, forum_topics_real = forum_topics_real - 1';
				$sql_data[FORUMS_FORUMS_TABLE] .= ($data['topic_approved']) ? ', forum_topics = forum_topics - 1' : '';
				$sql_data[FORUMS_FORUMS_TABLE] .= ', ';
			}

			$sql_data[FORUMS_FORUMS_TABLE] .= implode(', ', update_last_post_information('forum', $forum_id));
			$sql_data[FORUMS_TOPICS_TABLE] = 'topic_replies_real = topic_replies_real - 1' . (($data['post_approved']) ? ', topic_replies = topic_replies - 1' : '');
			break;

		case 'delete_first_post':
			$sql = 'SELECT p.post_id, p.poster_id, p.post_username, u.username 
				FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . " u
				WHERE p.topic_id = $topic_id 
					AND p.poster_id = u.user_id 
				ORDER BY p.post_time ASC";
			$result = $_CLASS['core_db']->query_limit($sql, 1);

			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($data['topic_type'] != POST_GLOBAL)
			{
				$sql_data[FORUMS_FORUMS_TABLE] = 'forum_posts = forum_posts - 1';
			}

			$sql_data[FORUMS_TOPICS_TABLE] = 'topic_first_post_id = ' . intval($row['post_id']) . ", topic_first_poster_name = '" . (($row['poster_id'] == ANONYMOUS) ? $_CLASS['core_db']->escape($row['post_username']) : $_CLASS['core_db']->escape($row['username'])) . "'";
			$sql_data[FORUMS_TOPICS_TABLE] .= ', topic_replies_real = topic_replies_real - 1' . (($data['post_approved']) ? ', topic_replies = topic_replies - 1' : '');

			$next_post_id = (int) $row['post_id'];
			break;
			
		case 'delete_last_post':
			if ($data['topic_type'] != POST_GLOBAL)
			{
				$sql_data[FORUMS_FORUMS_TABLE] = 'forum_posts = forum_posts - 1';
			}

			$sql_data[FORUMS_FORUMS_TABLE] .= ($sql_data[FORUMS_FORUMS_TABLE]) ? ', ' : '';
			$sql_data[FORUMS_FORUMS_TABLE] .= implode(', ', update_last_post_information('forum', $forum_id));
			$sql_data[FORUMS_TOPICS_TABLE] = 'topic_bumped = 0, topic_bumper = 0, topic_replies_real = topic_replies_real - 1' . (($data['post_approved']) ? ', topic_replies = topic_replies - 1' : '');

			$update = update_last_post_information('topic', $topic_id);
			if (!empty($update))
			{
				$sql_data[FORUMS_TOPICS_TABLE] .= ', ' . implode(', ', $update);
				$next_post_id = (int) str_replace('topic_last_post_id = ', '', $update[0]);
			}
			else
			{
				$sql = 'SELECT MAX(post_id) as last_post_id
					FROM ' . FORUMS_POSTS_TABLE . "
					WHERE topic_id = $topic_id " .
						((!$_CLASS['auth']->acl_get('m_approve')) ? 'AND post_approved = 1' : '');
				$result = $_CLASS['core_db']->query($sql);
				$row = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
	
				$next_post_id = (int) $row['last_post_id'];
			}
			break;
			
		case 'delete':
			$sql = 'SELECT post_id
				FROM ' . FORUMS_POSTS_TABLE . "
				WHERE topic_id = $topic_id " . 
					((!$_CLASS['auth']->acl_get('m_approve')) ? 'AND post_approved = 1' : '') . '
					AND post_time > ' . $data['post_time'] . '
				ORDER BY post_time ASC';
			$result = $_CLASS['core_db']->query_limit($sql, 1);

			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($data['topic_type'] != POST_GLOBAL)
			{
				$sql_data[FORUMS_FORUMS_TABLE] = 'forum_posts = forum_posts - 1';
			}

			$sql_data[FORUMS_TOPICS_TABLE] = 'topic_replies_real = topic_replies_real - 1' . (($data['post_approved']) ? ', topic_replies = topic_replies - 1' : '');
			$next_post_id = (int) $row['post_id'];
	}
				
	$sql_data[USERS_TABLE] = ($_CLASS['auth']->acl_get('f_postcount', $forum_id)) ? 'user_posts = user_posts - 1' : '';
	set_config('num_posts', $config['num_posts'] - 1, true);

	$_CLASS['core_db']->transaction();

	$where_sql = array(FORUMS_FORUMS_TABLE => "forum_id = $forum_id", FORUMS_TOPICS_TABLE => "topic_id = $topic_id", USERS_TABLE => 'user_id = ' . $data['poster_id']);

	foreach ($sql_data as $table => $update_sql)
	{
		if ($update_sql)
		{
			$_CLASS['core_db']->query("UPDATE $table SET $update_sql WHERE " . $where_sql[$table]);
		}
	}

	$_CLASS['core_db']->transaction('commit');

	return $next_post_id;
}

// Submit Post
function submit_post($mode, $subject, $username, $topic_type, &$poll, &$data, $update_message = true)
{
	global $_CLASS, $config;

	// We do not handle erasing posts here
	if ($mode == 'delete')
	{
		return;
	}

	$current_time = $_CLASS['core_user']->time;

	if ($mode == 'post')
	{
		$post_mode = 'post';
		$update_message = true;
	}
	else if ($mode != 'edit')
	{
		$post_mode = 'reply';
		$update_message = true;
	}
	else if ($mode == 'edit')
	{
		$post_mode = ($data['topic_first_post_id'] == $data['topic_last_post_id']) ? 'edit_topic' : (($data['topic_first_post_id'] == $data['post_id']) ? 'edit_first_post' : (($data['topic_last_post_id'] == $data['post_id']) ? 'edit_last_post' : 'edit'));
	}


	// Collect some basic informations about which tables and which rows to update/insert
	$sql_data = array();
	$poster_id = ($mode == 'edit') ? $data['poster_id'] : (int) $_CLASS['core_user']->data['user_id'];

	// Collect Informations
	switch ($post_mode)
	{
		case 'post':
		case 'reply':
			$sql_data[FORUMS_POSTS_TABLE]['sql'] = array(
				'forum_id' 			=> ($topic_type == POST_GLOBAL) ? 0 : $data['forum_id'],
				'poster_id' 		=> (int) $_CLASS['core_user']->data['user_id'],
				'icon_id'			=> $data['icon_id'],
				'poster_ip' 		=> $_CLASS['core_user']->ip,
				'post_time'			=> $current_time,
				'post_approved' 	=> ($_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) && !$_CLASS['auth']->acl_get('m_approve')) ? 0 : 1,
				'enable_bbcode' 	=> $data['enable_bbcode'],
				'enable_html' 		=> $data['enable_html'],
				'enable_smilies' 	=> $data['enable_smilies'],
				'enable_magic_url' 	=> $data['enable_urls'],
				'enable_sig' 		=> $data['enable_sig'],
				'post_username'		=> (!$_CLASS['core_user']->is_user) ? $username : '',
				'post_subject'		=> $subject,
				'post_text' 		=> $data['message'],
				'post_checksum'		=> $data['message_md5'],
				'post_attachment'	=> (isset($data['filename_data']['physical_filename']) && !empty($data['filename_data'])) ? 1 : 0,
				'bbcode_bitfield'	=> $data['bbcode_bitfield'],
				'bbcode_uid'		=> $data['bbcode_uid'],
				'post_edit_locked'	=> $data['post_edit_locked']
			);
			break;

		case 'edit_first_post':
		case 'edit':
			if (!$_CLASS['auth']->acl_gets('m_', 'a_') || $data['post_edit_reason'])
			{
				$sql_data[FORUMS_POSTS_TABLE]['sql'] = array(
					'post_edit_time'	=> $current_time
				);

				$sql_data[FORUMS_POSTS_TABLE]['stat'][] = 'post_edit_count = post_edit_count + 1';
			}

		case 'edit_last_post':
		case 'edit_topic':

			if (($post_mode == 'edit_last_post' || $post_mode == 'edit_topic') && $data['post_edit_reason'])
			{
				$sql_data[FORUMS_POSTS_TABLE]['sql'] = array(
					'post_edit_time'	=> $current_time
				);

				$sql_data[FORUMS_POSTS_TABLE]['stat'][] = 'post_edit_count = post_edit_count + 1';
			}

			if (!isset($sql_data[FORUMS_POSTS_TABLE]['sql']))
			{
				$sql_data[FORUMS_POSTS_TABLE]['sql'] = array();
			}

			$sql_data[FORUMS_POSTS_TABLE]['sql'] = array_merge($sql_data[FORUMS_POSTS_TABLE]['sql'], array(
				'forum_id' 			=> ($topic_type == POST_GLOBAL) ? 0 : $data['forum_id'],
				'poster_id' 		=> $data['poster_id'],
				'icon_id'			=> $data['icon_id'],
				'post_approved' 	=> ($_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) && !$_CLASS['auth']->acl_get('m_approve')) ? 0 : 1,
				'enable_bbcode' 	=> $data['enable_bbcode'],
				'enable_html' 		=> $data['enable_html'],
				'enable_smilies' 	=> $data['enable_smilies'],
				'enable_magic_url' 	=> $data['enable_urls'],
				'enable_sig' 		=> $data['enable_sig'],
				'post_username'		=> ($username && $data['poster_id'] == ANONYMOUS) ? $username : '',
				'post_subject'		=> $subject,
				'post_edit_reason'	=> $data['post_edit_reason'],
				'post_edit_user'	=> (int) $data['post_edit_user'],
				'post_checksum'		=> $data['message_md5'],
				'post_attachment'	=> (isset($data['filename_data']['physical_filename']) && !empty($data['filename_data'])) ? 1 : 0,
				'bbcode_bitfield'	=> $data['bbcode_bitfield'],
				'bbcode_uid'		=> $data['bbcode_uid'],
				'post_edit_locked'	=> $data['post_edit_locked'])
			);

			if ($update_message)
			{
				$sql_data[FORUMS_POSTS_TABLE]['sql']['post_text'] = $data['message'];
			}

			break;
	}

	// And the topic ladies and gentlemen
	switch ($post_mode)
	{
		case 'post':
			$sql_data[FORUMS_TOPICS_TABLE]['sql'] = array(
				'topic_poster'			=> (int) $_CLASS['core_user']->data['user_id'],
				'topic_time'			=> $current_time,
				'forum_id' 				=> ($topic_type == POST_GLOBAL) ? 0 : $data['forum_id'],
				'icon_id'				=> $data['icon_id'],
				'topic_approved'		=> ($_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) && !$_CLASS['auth']->acl_get('m_approve')) ? 0 : 1,
				'topic_title' 			=> $subject,
				'topic_first_poster_name' => (!$_CLASS['core_user']->is_user && $username) ? $username : $_CLASS['core_user']->data['username'],
				'topic_type'			=> $topic_type,
				'topic_time_limit'		=> ($topic_type == POST_STICKY || $topic_type == POST_ANNOUNCE) ? ($data['topic_time_limit'] * 86400) : 0,
				'topic_status'			=> $data['topic_status'],
				'topic_attachment'		=> (isset($data['filename_data']['physical_filename']) && !empty($data['filename_data'])) ? 1 : 0,
				'topic_replies_real'	=> 0,
				'topic_replies'			=> 0,
				'topic_views'			=> 0,
			);

			if (isset($poll['poll_options']) && !empty($poll['poll_options']))
			{
				$sql_data[FORUMS_TOPICS_TABLE]['sql'] = array_merge($sql_data[TOPICS_TABLE]['sql'], array(
					'poll_title'		=> $poll['poll_title'],
					'poll_start'		=> ($poll['poll_start']) ? $poll['poll_start'] : $current_time,
					'poll_max_options'	=> $poll['poll_max_options'],
					'poll_length'		=> ($poll['poll_length'] * 86400),
					'poll_vote_change'	=> $poll['poll_vote_change'])
				);
			}

			$sql_data[USERS_TABLE]['stat'][] = "user_last_post_time = $current_time" . (($_CLASS['auth']->acl_get('f_postcount', $data['forum_id'])) ? ', user_posts = user_posts + 1' : '');

			if ($topic_type != POST_GLOBAL)
			{
				if (!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve'))
				{
					$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_posts = forum_posts + 1';
				}
				$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_topics_real = forum_topics_real + 1' . ((!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')) ? ', forum_topics = forum_topics + 1' : '');
			}
		break;

		case 'reply':
			$sql_data[FORUMS_TOPICS_TABLE]['stat'][] = 'topic_replies_real = topic_replies_real + 1, topic_bumped = 0, topic_bumper = 0' . ((!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')) ? ', topic_replies = topic_replies + 1' : '');
			$sql_data[USERS_TABLE]['stat'][] = "user_last_post_time = $current_time" . (($_CLASS['auth']->acl_get('f_postcount', $data['forum_id'])) ? ', user_posts = user_posts + 1' : '');
			
			if ((!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')) && $topic_type != POST_GLOBAL)
			{
				$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_posts = forum_posts + 1';
			}
		break;

		case 'edit_topic':
		case 'edit_first_post':

			$sql_data[FORUMS_TOPICS_TABLE]['sql'] = array(
				'forum_id' 					=> ($topic_type == POST_GLOBAL) ? 0 : $data['forum_id'],
				'icon_id'					=> $data['icon_id'],
				'topic_approved'			=> ($_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) && !$_CLASS['auth']->acl_get('m_approve')) ? 0 : 1,
				'topic_title' 				=> $subject,
				'topic_first_poster_name'	=> $username,
				'topic_type'				=> $topic_type,
				'topic_time_limit'			=> ($topic_type == POST_STICKY || $topic_type == POST_ANNOUNCE) ? ($data['topic_time_limit'] * 86400) : 0,
				'poll_title'				=> isset($poll['poll_options']) ? $poll['poll_title'] : '',
				'poll_start'				=> isset($poll['poll_options']) ? (($poll['poll_start']) ? $poll['poll_start'] : $current_time) : 0,
				'poll_max_options'			=> isset($poll['poll_options']) ? $poll['poll_max_options'] : 1,
				'poll_length'				=> isset($poll['poll_options']) ? ($poll['poll_length'] * 86400) : 0,
				'poll_vote_change'			=> isset($poll['poll_vote_change']) ? $poll['poll_vote_change'] : 0,
				'topic_attachment'			=> ($post_mode == 'edit_topic') ? ((isset($data['filename_data']['physical_filename']) && !empty($data['filename_data'])) ? 1 : 0) : $data['topic_attachment']
			);
			break;
	}

	$_CLASS['core_db']->transaction();

	// Submit new topic
	if ($post_mode == 'post')
	{
		$sql = 'INSERT INTO ' . FORUMS_TOPICS_TABLE . ' ' .
			$_CLASS['core_db']->sql_build_array('INSERT', $sql_data[FORUMS_TOPICS_TABLE]['sql']);
		$_CLASS['core_db']->query($sql);

		$data['topic_id'] = $_CLASS['core_db']->insert_id(FORUMS_TOPICS_TABLE, 'topic_id');

		$sql_data[FORUMS_POSTS_TABLE]['sql'] = array_merge($sql_data[FORUMS_POSTS_TABLE]['sql'], array(
			'topic_id' => $data['topic_id'])
		);
		unset($sql_data[FORUMS_TOPICS_TABLE]['sql']);
	}

	// Submit new post
	if ($post_mode == 'post' || $post_mode == 'reply')
	{
		if ($post_mode == 'reply')
		{
			$sql_data[FORUMS_POSTS_TABLE]['sql'] = array_merge($sql_data[FORUMS_POSTS_TABLE]['sql'], array(
				'topic_id' => $data['topic_id'])
			);
		}

		$sql = 'INSERT INTO ' . FORUMS_POSTS_TABLE . ' ' .
			$_CLASS['core_db']->sql_build_array('INSERT', $sql_data[FORUMS_POSTS_TABLE]['sql']);
		$_CLASS['core_db']->query($sql);
		$data['post_id'] = $_CLASS['core_db']->insert_id(FORUMS_POSTS_TABLE, 'post_id');

		if ($post_mode == 'post')
		{
			$sql_data[FORUMS_TOPICS_TABLE]['sql'] = array(
				'topic_first_post_id'	=> $data['post_id'],
				'topic_last_post_id'	=> $data['post_id'],
				'topic_last_post_time'	=> $current_time,
				'topic_last_poster_id'	=> (int) $_CLASS['core_user']->data['user_id'],
				'topic_last_poster_name'=> (!$_CLASS['core_user']->is_user && $username) ? $username : $_CLASS['core_user']->data['username']
			);
		}

		unset($sql_data[FORUMS_POSTS_TABLE]['sql']);
	}

	$make_global = false;

	// Are we globalising or unglobalising?
	if ($post_mode == 'edit_first_post' || $post_mode == 'edit_topic')
	{
		$sql = 'SELECT topic_type, topic_replies_real, topic_approved
			FROM ' . FORUMS_TOPICS_TABLE . '
			WHERE topic_id = ' . $data['topic_id'];
		$result = $_CLASS['core_db']->query($sql);

		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		// globalise
		if ($row['topic_type'] != POST_GLOBAL && $topic_type == POST_GLOBAL)
		{
			// Decrement topic/post count
			$make_global = true;
			$sql_data[FORUMS_FORUMS_TABLE]['stat'] = array();

			$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_posts = forum_posts - ' . ($row['topic_replies_real'] + 1);
			$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_topics_real = forum_topics_real - 1' . (($row['topic_approved']) ? ', forum_topics = forum_topics - 1' : '');

			// Update forum_ids for all posts
			$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . '
				SET forum_id = 0
				WHERE topic_id = ' . $data['topic_id'];
			$_CLASS['core_db']->query($sql);
		}
		// unglobalise
		else if ($row['topic_type'] == POST_GLOBAL && $topic_type != POST_GLOBAL)
		{
			// Increment topic/post count
			$make_global = true;
			$sql_data[FORUMS_FORUMS_TABLE]['stat'] = array();

			$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_posts = forum_posts + ' . ($row['topic_replies_real'] + 1);
			$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = 'forum_topics_real = forum_topics_real + 1' . (($row['topic_approved']) ? ', forum_topics = forum_topics + 1' : '');

			// Update forum_ids for all posts
			$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . '
				SET forum_id = ' . $data['forum_id'] . '
				WHERE topic_id = ' . $data['topic_id'];
			$_CLASS['core_db']->query($sql);
		}
	}

	// Update the topics table
	if (isset($sql_data[FORUMS_TOPICS_TABLE]['sql']))
	{
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . '
			SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_data[FORUMS_TOPICS_TABLE]['sql']) . '
			WHERE topic_id = ' . $data['topic_id']);
	}

	// Update the posts table
	if (isset($sql_data[FORUMS_POSTS_TABLE]['sql']))
	{
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_POSTS_TABLE . '
			SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_data[FORUMS_POSTS_TABLE]['sql']) . '
			WHERE post_id = ' . $data['post_id']);
	}

	// Update Poll Tables
	if (isset($poll['poll_options']) && !empty($poll['poll_options']))
	{
		$cur_poll_options = array();

		if ($poll['poll_start'] && $mode == 'edit')
		{
			$sql = 'SELECT * FROM ' . FORUMS_POLL_OPTIONS_TABLE . '
				WHERE topic_id = ' . $data['topic_id'] . '
				ORDER BY poll_option_id';
			$result = $_CLASS['core_db']->query($sql);

			while ($cur_poll_options[] = $_CLASS['core_db']->fetch_row_assoc($result));
			$_CLASS['core_db']->free_result($result);
		}
		
		$size = count($poll['poll_options']);

		for ($i = 0, $size; $i < $size; $i++)
		{
			if (trim($poll['poll_options'][$i]))
			{
				if (!$cur_poll_options[$i])
				{
					$sql = 'INSERT INTO ' . FORUMS_POLL_OPTIONS_TABLE . "  (poll_option_id, topic_id, poll_option_text)
						VALUES ($i, " . $data['topic_id'] . ", '" . $_CLASS['core_db']->escape($poll['poll_options'][$i]) . "')";
					$_CLASS['core_db']->query($sql);
				}
				else if ($poll['poll_options'][$i] != $cur_poll_options[$i])
				{
					$sql = "UPDATE " . FORUMS_POLL_OPTIONS_TABLE . "
						SET poll_option_text = '" . $_CLASS['core_db']->escape($poll['poll_options'][$i]) . "'
						WHERE poll_option_id = " . $cur_poll_options[$i]['poll_option_id'] . "
							AND topic_id = " . $data['topic_id'];
					$_CLASS['core_db']->query($sql);
				}
			}
		}

		if (count($poll['poll_options']) < count($cur_poll_options))
		{
			$sql = 'DELETE FROM ' . FORUMS_POLL_OPTIONS_TABLE . '
				WHERE poll_option_id >= ' . count($poll['poll_options']) . '
					AND topic_id = ' . $data['topic_id'];
			$_CLASS['core_db']->query($sql);
		}
	}

	// Submit Attachments
	if (!empty($data['attachment_data']) && $data['post_id'] && in_array($mode, array('post', 'reply', 'quote', 'edit')))
	{
		$space_taken = $files_added = 0;

		foreach ($data['attachment_data'] as $pos => $attach_row)
		{
			if ($attach_row['attach_id'])
			{
				// update entry in db if attachment already stored in db and filespace
				$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . "
					SET comment = '" . $_CLASS['core_db']->escape($attach_row['comment']) . "'
					WHERE attach_id = " . (int) $attach_row['attach_id'];
				$_CLASS['core_db']->query($sql);
			}
			else
			{
				// insert attachment into db
				if (!@file_exists($config['upload_path'] . '/' . basename($attach_row['physical_filename'])))
				{
					continue;
				}
				
				$attach_sql = array(
					'post_msg_id'		=> $data['post_id'],
					'topic_id'			=> $data['topic_id'],
					'in_message'		=> 0,
					'poster_id'			=> $poster_id,
					'physical_filename'	=> basename($attach_row['physical_filename']),
					'real_filename'		=> basename($attach_row['real_filename']),
					'download_count'	=> 0,
					'comment'			=> $attach_row['comment'],
					'extension'			=> $attach_row['extension'],
					'mimetype'			=> $attach_row['mimetype'],
					'filesize'			=> $attach_row['filesize'],
					'filetime'			=> $attach_row['filetime'],
					'thumbnail'			=> $attach_row['thumbnail']
				);

				$sql = 'INSERT INTO ' . FORUMS_ATTACHMENTS_TABLE . ' ' .
					$_CLASS['core_db']->sql_build_array('INSERT', $attach_sql);
				$_CLASS['core_db']->query($sql);

				$space_taken += $attach_row['filesize'];
				$files_added++;
			}
		}

		if (!empty($data['attachment_data']))
		{
			$sql = 'UPDATE ' . FORUMS_POSTS_TABLE . '
				SET post_attachment = 1
				WHERE post_id = ' . $data['post_id'];
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . '
				SET topic_attachment = 1
				WHERE topic_id = ' . $data['topic_id'];
			$_CLASS['core_db']->query($sql);
		}

		set_config('upload_dir_size', $config['upload_dir_size'] + $space_taken, true);
		set_config('num_files', $config['num_files'] + $files_added, true);
	}

	$_CLASS['core_db']->transaction('commit');

	if ($post_mode == 'post' || $post_mode == 'reply' || $post_mode == 'edit_last_post')
	{
		if ($topic_type != POST_GLOBAL)
		{
			$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = implode(', ', update_last_post_information('forum', $data['forum_id']));
		}

		$update = update_last_post_information('topic', $data['topic_id']);

		if (!empty($update))
		{
			$sql_data[FORUMS_TOPICS_TABLE]['stat'][] = implode(', ', $update);
		}
	}

	if ($make_global)
	{
		$sql_data[FORUMS_FORUMS_TABLE]['stat'][] = implode(', ', update_last_post_information('forum', $data['forum_id']));
	}

	if ($post_mode == 'edit_topic')
	{
		$update = update_last_post_information('topic', $data['topic_id']);

		if (!empty($update))
		{
			$sql_data[FORUMS_TOPICS_TABLE]['stat'][] = implode(', ', $update);
		}
	}

	// Update total post count, do not consider moderated posts/topics
	if (!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve'))
	{
		if ($post_mode == 'post')
		{
			set_config('num_topics', $config['num_topics'] + 1, true);
			set_config('num_posts', $config['num_posts'] + 1, true);
		}

		if ($post_mode == 'reply')
		{
			set_config('num_posts', $config['num_posts'] + 1, true);
		}
	}

	// Update forum stats
	$_CLASS['core_db']->transaction();

	$where_sql = array(FORUMS_POSTS_TABLE => 'post_id = ' . $data['post_id'], FORUMS_TOPICS_TABLE => 'topic_id = ' . $data['topic_id'], FORUMS_FORUMS_TABLE => 'forum_id = ' . $data['forum_id'], USERS_TABLE => 'user_id = ' . $_CLASS['core_user']->data['user_id']);

	foreach ($sql_data as $table => $update_ary)
	{
		if (isset($update_ary['stat']) && implode('', $update_ary['stat']))
		{
			$_CLASS['core_db']->query("UPDATE $table SET " . implode(', ', $update_ary['stat']) . ' WHERE ' . $where_sql[$table]);
		}
	}

	// Delete topic shadows (if any exist). We do not need a shadow topic for an global announcement
	if ($make_global)
	{
		$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_TOPICS_TABLE . '
			WHERE topic_moved_id = ' . $data['topic_id']);
	}

	// Fulltext parse
	if ($update_message && $data['enable_indexing'])
	{
		$search = new fulltext_search();
		$result = $search->add($mode, $data['post_id'], $data['message'], $subject);
	}

	$_CLASS['core_db']->transaction('commit');

	// Delete draft if post was loaded...
	$draft_id = request_var('draft_loaded', 0);
	if ($draft_id)
	{
		$_CLASS['core_db']->query('DELETE FROM ' . DRAFTS_TABLE . " WHERE draft_id = $draft_id AND user_id = " . $_CLASS['core_user']->data['user_id']);
	}

	// Topic Notification
	if (!$data['notify_set'] && $data['notify'])
	{
		$sql = 'INSERT INTO ' . FORUMS_TOPICS_WATCH_TABLE . ' (user_id, topic_id)
			VALUES (' . $_CLASS['core_user']->data['user_id'] . ', ' . $data['topic_id'] . ')';
		$_CLASS['core_db']->query($sql);
	}
	else if ($data['notify_set'] && !$data['notify'])
	{
		$sql = 'DELETE FROM ' . FORUMS_TOPICS_WATCH_TABLE . '
			WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND topic_id = ' . $data['topic_id'];
		$_CLASS['core_db']->query($sql);
	}

	// Mark this topic as read and posted to.
	//markread('topic', $data['forum_id'], $data['topic_id'], $data['post_time']);

	// Send Notifications
	if ($mode != 'edit' && $mode != 'delete' && (!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')))
	{
		user_notification($mode, $subject, $data['topic_title'], $data['forum_name'], $data['forum_id'], $data['topic_id'], $data['post_id']);
	}

	if ($mode == 'post')
	{
		$url = (!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')) ? generate_link('Forums&amp;file=viewtopic&amp;f=' . $data['forum_id'] . '&amp;t=' . $data['topic_id']) : generate_link('Forums&amp;file=viewforum&amp;f=' . $data['forum_id']);
	}
	else
	{
		$url = (!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')) ?  generate_link("Forums&amp;file=viewtopic&amp;f={$data['forum_id']}&amp;t={$data['topic_id']}&amp;p={$data['post_id']}#{$data['post_id']}") : generate_link("Forums&amp;file=viewtopic&amp;f={$data['forum_id']}&amp;t={$data['topic_id']}");
	}

	$_CLASS['core_display']->meta_refresh(3, $url);

	$message = ($_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) && !$_CLASS['auth']->acl_get('m_approve')) ? (($mode == 'edit') ? 'POST_EDITED_MOD' : 'POST_STORED_MOD') : (($mode == 'edit') ? 'POST_EDITED' : 'POST_STORED');
	$message = $_CLASS['core_user']->lang[$message] . ((!$_CLASS['auth']->acl_get('f_moderate', $data['forum_id']) || $_CLASS['auth']->acl_get('m_approve')) ? '<br /><br />' . sprintf($_CLASS['core_user']->lang['VIEW_MESSAGE'], '<a href="' . $url . '">', '</a>') : '') . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('Forums&amp;file=viewforum&amp;f=' . $data['forum_id']) . '">', '</a>');
	trigger_error($message);
}

?>