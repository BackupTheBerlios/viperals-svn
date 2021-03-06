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
	$redirect = ($post_id) ? "forums&amp;file=viewtopic&p=$post_id#$post_id" : (($topic_id) ? 'forums&amp;file=viewtopic&t='.$topic_id : (($forum_id) ? 'forums&amp;file=viewforum&f='.$forum_id : 'forums'));
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
			FROM ' . FORUMS_POSTS_TABLE . ' p, ' . CORE_USERS_TABLE . ' u , ' . FORUMS_TOPICS_TABLE . ' t
			LEFT JOIN ' . FORUMS_FORUMS_TABLE . " f ON (f.forum_id = t.forum_id)
			WHERE p.post_id = $post_id
				AND t.topic_id = p.topic_id
				AND u.user_id = p.poster_id";
	break;

	case 'smilies':
		require_once SITE_FILE_ROOT.'includes/forums/functions_posting.php';

		generate_smilies('window', $forum_id);

		script_close(false);
	break;

	default:
		trigger_error('NO_POST_MODE');
	break;
}

$result = $_CLASS['core_db']->query($sql);

$post_data = $_CLASS['core_db']->fetch_row_assoc($result);
$_CLASS['core_db']->free_result($result);

if (!$post_data)
{
	trigger_error('NO_POST');
}

require_once SITE_FILE_ROOT.'includes/forums/message_parser.php';
require_once SITE_FILE_ROOT.'includes/forums/functions_admin.php';
require_once SITE_FILE_ROOT.'includes/forums/functions_posting.php';
require_once SITE_FILE_ROOT.'includes/forums/functions_display.php';

// Use post_row values in favor of submitted ones...
$forum_id	= (!empty($post_data['forum_id'])) ? (int) $post_data['forum_id'] : (int) $forum_id;
$topic_id	= (!empty($post_data['topic_id'])) ? (int) $post_data['topic_id'] : (int) $topic_id;
$post_id	= (!empty($post_data['post_id'])) ? (int) $post_data['post_id'] : (int) $post_id;

$post_data['post_edit_locked'] = isset($post_data['post_edit_locked']) ? (int) $post_data['post_edit_locked'] : false;

$_CLASS['core_user']->add_lang(array('posting', 'mcp', 'viewtopic'));
$_CLASS['core_user']->add_img();

// Is the user able to read within this forum?
if (!$_CLASS['forums_auth']->acl_get('f_read', $forum_id))
{
	if ($_CLASS['core_user']->is_user)
	{
		trigger_error('USER_CANNOT_READ');
	}

	login_box(array('explain' => $_CLASS['core_user']->get_lang('LOGIN_EXPLAIN_POST')));
}

if ($post_data['forum_password'])
{
	login_forum_box(array(
		'forum_id'		=> $forum_id, 
		'forum_password'=> $post_data['forum_password']
	));
}

// Permission to do the action asked?
$is_authed = false;

switch ($mode)
{
	case 'post':
		if ($_CLASS['forums_auth']->acl_get('f_post', $forum_id))
		{
			$is_authed = true;
		}
	break;

	case 'bump':
		if ($_CLASS['forums_auth']->acl_get('f_bump', $forum_id))
		{
			$is_authed = true;
		}
	break;

	case 'quote':
	case 'reply':
		if ($_CLASS['forums_auth']->acl_get('f_reply', $forum_id))
		{
			$is_authed = true;
		}
	break;

	case 'edit':
		if ($_CLASS['core_user']->is_user && $_CLASS['forums_auth']->acl_gets(array('f_edit', 'm_edit'), $forum_id))
		{
			$is_authed = true;
		}
	break;

	case 'delete':
		if ($_CLASS['core_user']->is_user && $_CLASS['forums_auth']->acl_gets(array('f_delete', 'm_delete'), $forum_id))
		{
			$is_authed = true;
		}
	break;
}

if (!$is_authed)
{
	if ($_CLASS['core_user']->is_user)
	{
		trigger_error('USER_CANNOT_' . strtoupper(($mode == 'quote') ? 'reply' : $mode));
	}

	login_box(array('explain' => $_CLASS['core_user']->get_lang('LOGIN_EXPLAIN_' . strtoupper($mode))));
}
unset($is_authed);

// Is the user able to post within this forum?
if ($post_data['forum_type'] != FORUM_POST && in_array($mode, array('post', 'bump', 'quote', 'reply')))
{
	trigger_error('USER_CANNOT_FORUM_POST');
}

// Forum/Topic locked?
if (($post_data['forum_status'] == ITEM_LOCKED || (isset($post_data['topic_status']) && $post_data['topic_status'] == ITEM_LOCKED)) && !$_CLASS['forums_auth']->acl_get('m_edit', $forum_id))
{
	trigger_error(($post_data['forum_status'] == ITEM_LOCKED) ? 'FORUM_LOCKED' : 'TOPIC_LOCKED');
}

// Can we edit this post ... if we're a moderator with rights then always yes
// else it depends on editing times, lock status and if we're the correct user
if ($mode == 'edit' && !$_CLASS['forums_auth']->acl_get('m_edit', $forum_id))
{
	if ($post_data['post_edit_locked'])
	{
		trigger_error('CANNOT_EDIT_POST_LOCKED');
	}

	if ($_CLASS['core_user']->data['user_id'] != $post_data['poster_id'])
	{
		trigger_error('USER_CANNOT_EDIT');
	}

	if ($config['edit_time'] && $post_data['post_time'] > ($current_time - $config['edit_time']))
	{
		trigger_error('CANNOT_EDIT_TIME');
	}
}

// Handle delete mode...
if ($mode == 'delete')
{
	handle_post_delete($forum_id, $topic_id, $post_id, $post_data);
	exit;
}

// Bump Topic
if ($mode == 'bump')
{
	if ($bump_time = bump_topic_allowed($forum_id, $post_data['topic_bumped'], $post_data['topic_last_post_time'], $post_data['topic_poster'], $post_data['topic_last_poster_id']))
	{
		$_CLASS['core_db']->transaction();
	
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_POSTS_TABLE . "
			SET post_time = $current_time
			WHERE post_id = {$post_data['topic_last_post_id']}
				AND topic_id = $topic_id");
	
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_TOPICS_TABLE . "
			SET topic_last_post_time = $current_time,
				topic_bumped = 1,
				topic_bumper = " . $_CLASS['core_user']->data['user_id'] . "
			WHERE topic_id = $topic_id");
	
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET ' . implode(', ', update_last_post_information('forum', $forum_id)) . "
			WHERE forum_id = $forum_id");
	
		$_CLASS['core_db']->query('UPDATE ' . CORE_USERS_TABLE . "
			SET user_last_post_time = $current_time
			WHERE user_id = " . $_CLASS['core_user']->data['user_id']);
	
		$_CLASS['core_db']->transaction('commit');
	
		//markread('topic', $forum_id, $topic_id, $current_time);
	
		add_log('mod', $forum_id, $topic_id, 'LOG_BUMP_TOPIC', $post_data['topic_title']);
	
		$_CLASS['core_display']->meta_refresh(3, generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$topic_last_post_id#$topic_last_post_id"));
	
		$message = $_CLASS['core_user']->lang['TOPIC_BUMPED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['VIEW_MESSAGE'], '<a href="'.generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$topic_last_post_id#$topic_last_post_id").'">', '</a>') . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id). '">', '</a>');
		$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="' . generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id) . '">', '</a>');

		trigger_error($message);
	}

	trigger_error('BUMP_ERROR');
}

// Determine some vars
$post_data['quote_username']	= (!empty($post_data['username'])) ? $post_data['username'] : ((!empty($post_data['post_username'])) ? $post_data['post_username'] : '');
$post_data['post_edit_locked']	= (isset($post_data['post_edit_locked'])) ? (int) $post_data['post_edit_locked'] : 0;
$post_data['post_subject']		= (in_array($mode, array('quote', 'edit'))) ? $post_data['post_subject'] : ((isset($post_data['topic_title'])) ? $post_data['topic_title'] : '');
$post_data['topic_time_limit']	= (isset($post_data['topic_time_limit'])) ? (($post_data['topic_time_limit']) ? (int) $post_data['topic_time_limit'] / 86400 : (int) $post_data['topic_time_limit']) : 0;
$post_data['poll_length']		= (!empty($post_data['poll_length'])) ? (int) $post_data['poll_length'] / 86400 : 0;
$post_data['poll_start']		= (!empty($post_data['poll_start'])) ? (int) $post_data['poll_start'] : 0;
$post_data['icon_id']			= (!isset($post_data['icon_id']) || in_array($mode, array('quote', 'reply'))) ? 0 : (int) $post_data['icon_id'];
$post_data['poll_options']		= array();

if (!isset($icon_id) || in_array($mode, array('quote', 'reply')))
{
	$icon_id = 0;
}

// Get Poll Data
if ($post_data['poll_start'])
{
	$sql = 'SELECT poll_option_text 
		FROM ' . FORUMS_POLL_OPTIONS_TABLE . "
		WHERE topic_id = $topic_id
		ORDER BY poll_option_id";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$post_data['poll_options'][] = trim($row['poll_option_text']);
	}
	$_CLASS['core_db']->free_result($result);
}

$orig_poll_options_size = count($post_data['poll_options']);

$message_parser = new parse_message();

if (isset($post_data['post_text']))
{
	$message_parser->message = $post_data['post_text'];
	unset($post_data['post_text']);
}


// Set uninitialized variables
$uninit = array('post_attachment' => 0, 'poster_id' => $_CLASS['core_user']->data['user_id'], 'enable_magic_url' => 0, 'topic_status' => 0, 'topic_type' => POST_NORMAL, 'post_subject' => '', 'topic_title' => '', 'post_time' => 0, 'post_edit_reason' => '', 'notify_set' => 0);

foreach ($uninit as $var_name => $default_value)
{
	if (!isset($post_data[$var_name]))
	{
		$post_data[$var_name] = $default_value;
	}
}
unset($uninit);

$message_parser->get_submitted_attachment_data($post_data['poster_id']);

if ($post_data['post_attachment'] && !$submit && !$refresh && !$preview && $mode == 'edit')
{
	//$sql = 'SELECT attach_id, physical_filename, comment, real_filename, extension, mimetype, filesize, filetime, thumbnail
	$sql = 'SELECT attach_id, is_orphan, attach_comment, real_filename
		FROM ' . FORUMS_ATTACHMENTS_TABLE . "
		WHERE post_msg_id = $post_id
			AND in_message = 0
			AND is_orphan = 0
		ORDER BY filetime " . ((!$config['display_order']) ? 'DESC' : 'ASC');
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$message_parser->attachment_data[] = $row;
	}

	$_CLASS['core_db']->free_result($result);
}

if (!$post_data['poster_id'] || $post_data['poster_id'] == ANONYMOUS)
{
	$post_data['username'] = in_array($mode, array('quote', 'edit')) ? trim($post_data['post_username']) : get_variable('username', 'POST', '');
}
else
{
	$post_data['username'] = in_array($mode, array('quote', 'edit')) ? trim($post_data['username']) : '';
}

$post_data['enable_urls'] = $post_data['enable_magic_url'];
$post_data['enable_html'] = isset($enable_html) ? $enable_html : $config['allow_html'];

if (!in_array($mode, array('quote', 'edit')))
{
	$post_data['enable_sig']		= ($config['allow_sig'] && $_CLASS['core_user']->user_data_get('attachsig'));
	$post_data['enable_smilies']	= ($config['allow_smilies'] && $_CLASS['core_user']->user_data_get('smilies'));
	$post_data['enable_bbcode']		= ($config['allow_bbcode'] && $_CLASS['core_user']->user_data_get('bbcode'));
	$post_data['enable_urls']		= true;
}

$post_data['enable_magic_url'] = $post_data['drafts'] = false;

// User own some drafts?
if ($_CLASS['core_user']->is_user && $_CLASS['forums_auth']->acl_get('u_savedrafts'))
{
	$sql = 'SELECT draft_id
		FROM ' . FORUMS_DRAFTS_TABLE . '
		WHERE (forum_id IN (' . $forum_id . ', 0)'. (($topic_id) ? " OR topic_id = $topic_id" : '') . ')
			AND user_id = ' . $_CLASS['core_user']->data['user_id'] . 
			(($draft_id) ? " AND draft_id <> $draft_id" : '');
	$result = $_CLASS['core_db']->query_limit($sql, 1);

	if ($_CLASS['core_db']->fetch_row_assoc($result))
	{
		$drafts = true;
	}
	$_CLASS['core_db']->free_result($result);
}

$check_value = (($post_data['enable_bbcode']+1) << 8) + (($post_data['enable_smilies']+1) << 4) + (($post_data['enable_urls']+1) << 2) + (($post_data['enable_sig']+1) << 1);

// Notify user checkbox
if ($mode != 'post' && $config['allow_topic_notify'] && $_CLASS['core_user']->is_user)
{
	$sql = 'SELECT forum_id, topic_id
		FROM ' . FORUMS_WATCH_TABLE . "
		WHERE (forum_id = $forum_id OR topic_id = $topic_id)
			AND user_id = " . $_CLASS['core_user']->data['user_id'];

	$result = $_CLASS['core_db']->query_limit($sql, 1);

	if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$post_data['notify_set'] = ($row['topic_id']) ? 1 : 2;
	}
	$_CLASS['core_db']->free_result($result);
}

// Do we want to edit our post ?
if ($mode == 'edit' && $post_data['bbcode_uid'])
{
	$message_parser->bbcode_uid = $post_data['bbcode_uid'];
}
/*
// should we alow ip no user deletion ?
// Delete triggered ?
if ($mode == 'delete')
{
	if ($_CLASS['forums_auth']->acl_get('f_delete', $forum_id) && $post_id == $topic_last_post_id && ((!$_CLASS['core_user']->is_user && $post_data['poster_id'] == ANONYMOUS && $poster_ip && $poster_ip == $_CLASS['core_user']->ip) || ($_CLASS['core_user']->is_user && $post_data['poster_id'] == $_CLASS['core_user']->data['user_id'])))
	{
		$user_deletable = true;
	}
	else
	{
		$user_deletable = false;
	}
}

if ($mode == 'delete' && ($user_deletable || $_CLASS['forums_auth']->acl_get('m_delete', $forum_id)))
{
	$s_hidden_fields = '<input type="hidden" name="p" value="' . $post_id . '" /><input type="hidden" name="f" value="' . $forum_id . '" /><input type="hidden" name="mode" value="delete" />';

	if (display_confirmation(false, $s_hidden_fields))
	{
		$data = array(
			'topic_first_post_id'=> $topic_first_post_id,
			'topic_last_post_id'=> $topic_last_post_id,
			'topic_approved'	=> $topic_approved,
			'topic_type'		=> $post_data['topic_type'],
			'post_approved' 	=> $post_approved,
			'post_time'			=> $post_data['post_time'],
			'poster_id'			=> $post_data['poster_id']
		);
		
		$next_post_id = delete_post($mode, $post_id, $topic_id, $forum_id, $data);
	
		if ($topic_first_post_id == $topic_last_post_id)
		{
			if (!$user_deletable)
			{
				add_log('mod', $forum_id, $topic_id, 'LOG_DELETE_TOPIC', $post_data['topic_title']);
			}

			$meta_info = generate_link('forums&amp;file=viewforum&amp;f='.$forum_id);
			$message = $_CLASS['core_user']->lang['POST_DELETED'];
		}
		else
		{
			if (!$user_deletable)
			{
				add_log('mod', $forum_id, $topic_id, 'LOG_DELETE_POST', $post_subject);
			}

			$meta_info = generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$next_post_id#$next_post_id");
			$message = $_CLASS['core_user']->lang['POST_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="'.generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id&amp;p=$next_post_id#$next_post_id").'">', '</a>');
		}

		$_CLASS['core_display']->meta_refresh(3, $meta_info);
		$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('forums&amp;file=viewforum&amp;f='.$forum_id).'">', '</a>');

		trigger_error($message);
	}
}

if ($mode == 'delete' && $post_data['poster_id'] != $_CLASS['core_user']->data['user_id'] && !$_CLASS['forums_auth']->acl_get('f_delete', $forum_id))
{
	trigger_error('DELETE_OWN_POSTS');
}

if ($mode == 'delete' && $post_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id) && $post_id != $topic_last_post_id)
{
	trigger_error('CANNOT_DELETE_REPLIED');
}

if ($mode == 'delete')
{
	trigger_error('USER_CANNOT_DELETE');
}
*/

// HTML, BBCode, Smilies, Images and Flash status
$html_status	= ($config['allow_html'] && $_CLASS['forums_auth']->acl_get('f_html', $forum_id));
$bbcode_status	= ($config['allow_bbcode'] && $_CLASS['forums_auth']->acl_get('f_bbcode', $forum_id));
$smilies_status	= ($config['allow_smilies'] && $_CLASS['forums_auth']->acl_get('f_smilies', $forum_id));
$img_status		= ($_CLASS['forums_auth']->acl_get('f_img', $forum_id));
$url_status		= ($config['allow_post_links']) ? true : false;
$flash_status	= ($_CLASS['forums_auth']->acl_get('f_flash', $forum_id));
$quote_status	= ($_CLASS['forums_auth']->acl_get('f_reply', $forum_id));

// Save Draft
if ($save && $_CLASS['core_user']->is_user && $_CLASS['forums_auth']->acl_get('u_savedrafts'))
{
	$subject = request_var('subject', '', true);
	$subject = (!$subject && $mode != 'post') ? $post_data['topic_title'] : $subject;
	$message = request_var('message', '', true);

	if ($subject && $message)
	{
		$sql = 'INSERT INTO ' . FORUMS_DRAFTS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'user_id'	=> $_CLASS['core_user']->data['user_id'],
			'topic_id'	=> $topic_id,
			'forum_id'	=> $forum_id,
			'save_time'	=> $current_time,
			'draft_subject' => $subject,
			'draft_message' => $message
		));
		$_CLASS['core_db']->query($sql);
	
		$meta_info = ($mode == 'post') ? generate_link('forums&amp;file=viewforum&amp;f='.$forum_id) : generate_link("forums&amp;file=viewtopic&amp;f=$forum_id&amp;t=$topic_id");

		$_CLASS['core_display']->meta_refresh(3, $meta_info);

		$message = $_CLASS['core_user']->lang['DRAFT_SAVED'] . '<br /><br />';
		$message .= ($mode != 'post') ? sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="' . $meta_info . '">', '</a>') . '<br /><br />' : '';
		$message .= sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="'.generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id) . '">', '</a>');

		trigger_error($message);
	}
	/*		$s_hidden_fields = build_hidden_fields(array(
				'mode'		=> $mode,
				'save'		=> true,
				'f'			=> $forum_id,
				't'			=> $topic_id,
				'subject'	=> $subject,
				'message'	=> $message,
				)
			);

			confirm_box(false, 'SAVE_DRAFT', $s_hidden_fields);
		*/
	unset($subject, $message);

}

// Move to where they should be
$_CLASS['core_template']->assign_array(array(
		'S_DRAFT_LOADED'				=> false,
		'S_SHOW_DRAFTS'					=> false,
		'S_POST_REVIEW'					=> false,
		'S_DISPLAY_PREVIEW'				=> false,
		'S_UNGLOBALISE'					=> false,
		'S_INLINE_ATTACHMENT_OPTIONS' 	=> false,
		'S_TOPIC_TYPE_ANNOUNCE' 		=> false,
		'S_TOPIC_TYPE_STICKY'			 => false,
		'S_DISPLAY_REVIEW'				=> false,
));

// Load Draft
if ($draft_id && $_CLASS['core_user']->is_user && $_CLASS['forums_auth']->acl_get('u_savedrafts'))
{
	$sql = 'SELECT draft_subject, draft_message 
		FROM ' . FORUMS_DRAFTS_TABLE . " 
		WHERE draft_id = $draft_id
			AND user_id = " . $_CLASS['core_user']->data['user_id'];
	$result = $_CLASS['core_db']->query_limit($sql, 1);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if ($row)
	{
		$post_data['post_subject'] = $row['draft_subject'];
		$message_parser->message = $row['draft_message'];

		//$refresh = true;
		$_CLASS['core_template']->assign('S_DRAFT_LOADED', true);
	}
	else
	{
		$draft_id = 0;
	}
}

// Load Drafts
if ($load && $post_data['drafts'])
{
	load_drafts($topic_id, $forum_id);
}

if ($submit || $preview || $refresh)
{
	$post_data['topic_cur_post_id'] = request_var('topic_cur_post_id', 0);
	$post_data['post_subject'] = request_var('subject', '', true);

	// If subject is all-uppercase then we make all lowercase (we do not want to be yelled at too :P)
	// Admins/Mods might want to create all-uppercase topics, therefore we do not apply this check to them (they should know better ;))
	if ($post_data['post_subject'] && !$_CLASS['forums_auth']->acl_gets(array('a_', 'm_'), $forum_id))// && strcmp($post_data['post_subject'], strtoupper($post_data['post_subject'])) == 0)
	{
		//$subject = mb_strtolower(htmlentities(get_variable('subject', 'POST', ''), ENT_QUOTES, 'UTF-8'));
		$post_data['post_subject'] = mb_strtolower(htmlentities($post_data['post_subject'], ENT_QUOTES, 'UTF-8'));
	}	

	$message_parser->message = request_var('message', '', true);

	$post_data['username']			= request_var('username', $post_data['username']);
	$post_data['post_edit_reason']	= (!empty($_POST['edit_reason']) && $mode == 'edit' && $_CLASS['forums_auth']->acl_get('m_edit', $forum_id)) ? request_var('edit_reason', '', true) : '';

	$post_data['topic_type']		= get_variable('topic_type', 'POST', (($mode != 'post') ? (int) $post_data['topic_type'] : POST_NORMAL), 'int');
	$post_data['topic_time_limit']	= get_variable('topic_time_limit', 'POST', (($mode != 'post') ? (int) $post_data['topic_time_limit'] : 0), 'int');
	$post_data['icon_id']			= get_variable('icon', 'POST', 0, 'int');

	$post_data['enable_bbcode']		= (!$bbcode_status || isset($_POST['disable_bbcode'])) ? false : true;
	$post_data['enable_smilies']	= (!$smilies_status || isset($_POST['disable_smilies'])) ? false : true;
	$post_data['enable_urls']		= !isset($_POST['disable_magic_url']);
	$post_data['enable_sig']		= (!$config['allow_sig']) ? false : ((isset($_POST['attach_sig']) && $_CLASS['core_user']->is_user) ? true : false);

	if ($config['allow_topic_notify'] && $_CLASS['core_user']->is_user)
	{
		$notify = isset($_POST['notify']);
	}
	else
	{
		$notify = false;
	}

	$topic_lock			= isset($_POST['lock_topic']);
	$post_lock			= isset($_POST['lock_post']);
	$poll_delete		= isset($_POST['poll_delete']);

	// Faster than crc32
	if ($submit)
	{
		$status_switch = (($post_data['enable_bbcode']+1) << 8) + (($post_data['enable_smilies']+1) << 4) + (($post_data['enable_urls']+1) << 2) + (($post_data['enable_sig']+1) << 1);
		$status_switch = ($status_switch != $check_value);
	}
	else
	{
		$status_switch = 1;
	}

	// Delete Poll
	if ($poll_delete && $mode === 'edit' && count($post_data['poll_options']) && 
		((!$post_data['poll_last_vote'] && $post_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id)) || $_CLASS['forums_auth']->acl_get('m_delete', $forum_id)))
	{
		switch ($_CLASS['core_db']->db_layer)
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
			break;
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

		$post_data['poll_title'] = $post_data['poll_option_text'] = '';
		$post_data['poll_vote_change'] = $post_data['poll_max_options'] = $post_data['poll_length'] = 0;
	}
	else
	{
		$post_data['poll_title']		= request_var('poll_title', '', true);
		$post_data['poll_length']		= request_var('poll_length', 0);
		$post_data['poll_option_text']	= request_var('poll_option_text', '', true);
		$post_data['poll_max_options']	= request_var('poll_max_options', 1);
		$post_data['poll_vote_change']	= ($_CLASS['forums_auth']->acl_get('f_votechg', $forum_id) && isset($_POST['poll_vote_change'])) ? 1 : 0;
	}

	// If replying/quoting and last post id has changed
	// give user option to continue submit or return to post
	// notify and show user the post made between his request and the final submit
	if (($mode === 'reply' || $mode === 'quote') && $post_data['topic_cur_post_id'] && $post_data['topic_cur_post_id'] != $post_data['topic_last_post_id'])
	{
		// Only do so if it is allowed forum-wide
		if ($post_data['forum_flags'] & FORUM_FLAG_POST_REVIEW)
		{
			if (topic_review($topic_id, $forum_id, 'post_review', $post_data['topic_cur_post_id']))
			{
				$_CLASS['core_template']->assign('S_POST_REVIEW',  true);
			}
			$submit = false;
			$refresh = true;
		}
	}

	// Parse Attachments - before checksum is calculated
	$message_parser->parse_attachments('fileupload', $mode, $forum_id, $submit, $preview, $refresh);

	// Grab md5 'checksum' of new message
	$message_md5 = md5($message_parser->message);

	// Check checksum ... don't re-parse message if the same
	$update_message = ($mode != 'edit' || $message_md5 != $post_data['post_checksum'] || $status_switch) ? true : false;
	
	// Parse message
	if ($update_message)
	{
		$message_parser->parse(false, $post_data['enable_bbcode'], ($config['allow_post_links']) ? $post_data['enable_urls'] : false, $post_data['enable_smilies'], $img_status, $flash_status, $quote_status, $config['allow_post_links']);
	}
	else
	{
		$message_parser->bbcode_bitfield = $post_data['bbcode_bitfield'];
	}

	if ($mode !== 'edit' && !$preview && !$refresh && $config['flood_interval'] && !$_CLASS['forums_auth']->acl_get('f_ignoreflood', $forum_id))
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

		if ($last_post_time && ($current_time - $last_post_time) < intval($config['flood_interval']))
		{
			$error[] = $_CLASS['core_user']->get_lang('FLOOD_ERROR');
		}
	}

	// Validate username
	if (($post_data['username'] && !$_CLASS['core_user']->is_user) || ($mode === 'edit' && $post_data['post_username']))
	{
		require_once SITE_FILE_ROOT.'includes/functions_user.php';
		$result = validate_username($post_data['username']);

		if ($result !== true)
		{
			$error[] = $_CLASS['core_user']->get_lang($result);
		}
	}

/*
 ADD
	if ($config['enable_post_confirm'] && !$_CLASS['core_user']->is_user && in_array($mode, array('quote', 'post', 'reply')))
	{
		$confirm_id = request_var('confirm_id', '');
		$confirm_code = request_var('confirm_code', '');

		$sql = 'SELECT code
			FROM ' . CONFIRM_TABLE . "
			WHERE confirm_id = '" . $db->sql_escape($confirm_id) . "'
				AND session_id = '" . $db->sql_escape($_CLASS['core_user']->session_id) . "'
				AND confirm_type = " . CONFIRM_POST;
		$result = $db->sql_query($sql);
		$confirm_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (empty($confirm_row['code']) || strcasecmp($confirm_row['code'], $confirm_code) !== 0)
		{
			$error[] = $_CLASS['core_user']->get_lang('CONFIRM_CODE_WRONG');
		}
	}
*/

	// Parse subject
	if (!$post_data['post_subject'] && ($mode === 'post' || ($mode === 'edit' && $post_data['topic_first_post_id'] == $post_id)))
	{
		$error[] = $_CLASS['core_user']->get_lang('EMPTY_SUBJECT');
	}

	$post_data['poll_last_vote'] = isset($post_data['poll_last_vote']) ? $post_data['poll_last_vote'] : 0;

	if ($post_data['poll_option_text'] && 
		($mode === 'post' || ($mode === 'edit' && $post_id == $post_data['topic_first_post_id'] && (!$post_data['poll_last_vote'] || $_CLASS['forums_auth']->acl_get('m_edit', $forum_id))))
			&& $_CLASS['forums_auth']->acl_get('f_poll', $forum_id))
	{
		$poll = array(
			'poll_title'		=> $post_data['poll_title'],
			'poll_length'		=> $post_data['poll_length'],
			'poll_max_options'	=> $post_data['poll_max_options'],
			'poll_option_text'	=> $post_data['poll_option_text'],
			'poll_start'		=> $post_data['poll_start'],
			'poll_last_vote'	=> $post_data['poll_last_vote'],
			'poll_vote_change'	=> $post_data['poll_vote_change'],
			//'enable_html'		=> $post_data['enable_html'],
			'enable_bbcode'		=> $post_data['enable_bbcode'],
			'enable_urls'		=> $post_data['enable_urls'],
			'enable_smilies'	=> $post_data['enable_smilies'],
			'img_status'		=> $img_status
		);

		$message_parser->parse_poll($poll);

		$post_data['poll_options'] = (isset($poll['poll_options'])) ? $poll['poll_options'] : '';
		$post_data['poll_title'] = (isset($poll['poll_title'])) ? $poll['poll_title'] : '';

		if ($post_data['poll_last_vote'] && ($poll['poll_options_size'] < $orig_poll_options_size))
		{
			$message_parser->warn_msg[] = $_CLASS['core_user']->lang['NO_DELETE_POLL_OPTIONS'];
		}
	}
	else
	{
		$poll = array();
	}

	// Check topic type
	if ($post_data['topic_type'] != POST_NORMAL && ($mode == 'post' || ($mode == 'edit' && $post_data['topic_first_post_id'] == $post_id)))
	{
		switch ($post_data['topic_type'])
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

		if (!$_CLASS['forums_auth']->acl_get($auth_option, $forum_id))
		{
			$error[] = $_CLASS['core_user']->lang['CANNOT_POST_' . str_replace('F_', '', strtoupper($auth_option))];
		}
	}

	if (!empty($message_parser->warn_msg) && !$refresh)
	{
		$error[] = implode('<br />', $message_parser->warn_msg);
	}

	// Store message, sync counters
	if (empty($error) && $submit)
	{
		// Check if we want to de-globalize the topic... and ask for new forum
		if ($post_data['topic_type'] != POST_GLOBAL)
		{
			$sql = 'SELECT topic_type, forum_id
				FROM ' . FORUMS_TOPICS_TABLE . "
				WHERE topic_id = $topic_id";

			$result = $_CLASS['core_db']->query_limit($sql, 1);
			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			if ($row && !$row['forum_id'] && $row['topic_type'] == POST_GLOBAL)
			{
				$to_forum_id = request_var('to_forum_id', 0);
	
				if (!$to_forum_id)
				{
					require_once  SITE_FILE_ROOT.'includes/forums/functions_admin.php';
	
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
			$change_topic_status = $post_data['topic_status'];
			$perm_lock_unlock = ($_CLASS['forums_auth']->acl_get('m_lock', $forum_id) || ($_CLASS['forums_auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && !empty($post_data['topic_poster']) && $_CLASS['core_user']->data['user_id'] == $post_data['topic_poster'] && $post_data['topic_status'] == ITEM_UNLOCKED)) ? true : false;
		
			if ($post_data['topic_status'] == ITEM_LOCKED && !$topic_lock && $perm_lock_unlock)
			{
				$change_topic_status = ITEM_UNLOCKED;
			}
			else if ($post_data['topic_status'] == ITEM_UNLOCKED && $topic_lock && $perm_lock_unlock)
			{
				$change_topic_status = ITEM_LOCKED;
			}
		
			if ($change_topic_status != $post_data['topic_status'])
			{
				$post_data['topic_status'] = $change_topic_status;

				$sql = 'UPDATE ' . FORUMS_TOPICS_TABLE . "
					SET topic_status = $change_topic_status
					WHERE topic_id = $topic_id
						AND topic_moved_id = 0";
				$_CLASS['core_db']->query($sql);
			
				$user_lock = ($_CLASS['forums_auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && !empty($post_data['topic_poster']) && $_CLASS['core_user']->data['user_id'] == $post_data['topic_poster']) ? 'USER_' : '';

				//add_log('mod', $forum_id, $topic_id, 'LOG_' . $user_lock . (($change_topic_status == ITEM_LOCKED) ? 'LOCK' : 'UNLOCK'), $post_data['topic_title']);
			}

			// Lock/Unlock Post Edit
			if ($mode == 'edit' && $post_data['post_edit_locked'] == ITEM_LOCKED && !$post_lock && $_CLASS['forums_auth']->acl_get('m_edit', $forum_id))
			{
				$post_data['post_edit_locked'] = ITEM_UNLOCKED;
			}
			else if ($mode == 'edit' && $post_data['post_edit_locked'] == ITEM_UNLOCKED && $post_lock && $_CLASS['forums_auth']->acl_get('m_edit', $forum_id))
			{
				$post_data['post_edit_locked'] = ITEM_LOCKED;
			}

			$data = array(
				'topic_title'			=> (empty($post_data['topic_title'])) ? $post_data['post_subject'] : $post_data['topic_title'],
				'topic_first_post_id'	=> (isset($post_data['topic_first_post_id'])) ? (int) $post_data['topic_first_post_id'] : 0,
				'topic_last_post_id'	=> (isset($post_data['topic_last_post_id'])) ? (int) $post_data['topic_last_post_id'] : 0,
				'topic_time_limit'		=> (int) $post_data['topic_time_limit'],
				'topic_status'			=> (int) $post_data['topic_status'],
				'post_id'				=> (int) $post_id,
				'topic_id'				=> (int) $topic_id,
				'forum_id'				=> (int) $forum_id,
				'icon_id'				=> (int) $post_data['icon_id'],
				'poster_id'				=> (int) $post_data['poster_id'],
				'enable_sig'			=> (bool) $post_data['enable_sig'],
				'enable_bbcode'			=> (bool) $post_data['enable_bbcode'],
				'enable_html' 			=> (bool) $post_data['enable_html'],
				'enable_smilies'		=> (bool) $post_data['enable_smilies'],
				'enable_urls'			=> (bool) $post_data['enable_urls'],
				'enable_indexing'		=> (bool) $post_data['enable_indexing'],
				'message_md5'			=> (string) $message_md5,
				'post_time'				=> (isset($post_data['post_time'])) ? (int) $post_data['post_time'] : $current_time,
				'post_checksum'			=> (isset($post_data['post_checksum'])) ? (string) $post_data['post_checksum'] : '',
				'post_edit_reason'		=> $post_data['post_edit_reason'],
				'post_edit_user'		=> ($mode == 'edit') ? $_CLASS['core_user']->data['user_id'] : ((isset($post_data['post_edit_user'])) ? (int) $post_data['post_edit_user'] : 0),
				'forum_parents'			=> $post_data['forum_parents'],
				'forum_name'			=> $post_data['forum_name'],
				'notify'				=> $notify,
				'notify_set'			=> $post_data['notify_set'],
				'poster_ip'				=> (isset($post_data['poster_ip'])) ? $post_data['poster_ip'] : $_CLASS['core_user']->ip,
				'post_edit_locked'		=> (int) $post_data['post_edit_locked'],
				'bbcode_bitfield'		=> $message_parser->bbcode_bitfield,
				'bbcode_uid'			=> $message_parser->bbcode_uid,
				'message'				=> $message_parser->message,
				'attachment_data'		=> $message_parser->attachment_data,
				'filename_data'			=> $message_parser->filename_data
			);
			unset($message_parser);
			
			$redirect_url = submit_post($mode, $post_data['post_subject'], $post_data['username'], $post_data['topic_type'], $poll, $data, $update_message);

			$_CLASS['core_display']->meta_refresh(3, $redirect_url);

			$message = (!$_CLASS['forums_auth']->acl_get('f_noapprove', $data['forum_id']) && !$_CLASS['forums_auth']->acl_get('m_approve', $data['forum_id'])) ? (($mode == 'edit') ? 'POST_EDITED_MOD' : 'POST_STORED_MOD') : (($mode == 'edit') ? 'POST_EDITED' : 'POST_STORED');
			$message = $_CLASS['core_user']->get_lang($message) . (($_CLASS['forums_auth']->acl_get('f_noapprove', $data['forum_id']) || $_CLASS['forums_auth']->acl_get('m_approve', $data['forum_id'])) ? '<br /><br />' . sprintf($_CLASS['core_user']->get_lang('VIEW_MESSAGE'), '<a href="' . $redirect_url . '">', '</a>') : '');
			$message .= '<br /><br />' . sprintf($_CLASS['core_user']->get_lang('RETURN_FORUM'), '<a href="' . generate_link('forums&amp;file=viewforumf=' . $data['forum_id']) . '">', '</a>');
			trigger_error($message);
		
		}
	}	
}

// Preview
if (empty($error) && $preview)
{
	$post_data['post_time'] = ($mode == 'edit') ? $post_data['post_time'] : $current_time;

	$preview_message = $message_parser->format_display($post_data['enable_html'], $post_data['enable_bbcode'], $post_data['enable_urls'], $post_data['enable_smilies'], false);

	$preview_signature = ($mode == 'edit') ? $post_data['user_sig'] : $_CLASS['core_user']->data['user_sig'];
	$preview_signature_uid = ($mode == 'edit') ? $post_data['user_sig_bbcode_uid'] : $_CLASS['core_user']->data['user_sig_bbcode_uid'];
	$preview_signature_bitfield = ($mode == 'edit') ? $post_data['user_sig_bbcode_bitfield'] : $_CLASS['core_user']->data['user_sig_bbcode_bitfield'];

	// Signature
	if ($post_data['enable_sig'] && $config['allow_sig'] && $preview_signature && $_CLASS['forums_auth']->acl_get('f_sigs', $forum_id))
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
	
	$preview_subject = censor_text($post_data['post_subject']);
	
	// Poll Preview
	if (($mode == 'post' || ($mode == 'edit' && $post_id == $post_data['topic_first_post_id'] && (!$post_data['poll_last_vote'] || $_CLASS['forums_auth']->acl_get('m_edit', $forum_id))))
	&& $_CLASS['forums_auth']->acl_get('f_poll', $forum_id))
	{
		$parse_poll = new parse_message($post_data['poll_title']);
		$parse_poll->bbcode_uid = $message_parser->bbcode_uid;
		$parse_poll->bbcode_bitfield = $message_parser->bbcode_bitfield;

		$parse_poll->format_display($post_data['enable_html'], $post_data['enable_bbcode'], $post_data['enable_urls'], $post_data['enable_smilies']);
		
		$_CLASS['core_template']->assign_array(array(
			'S_HAS_POLL_OPTIONS'=> !empty($poll_options),
			'S_IS_MULTI_CHOICE'	=> ($post_data['poll_max_options'] > 1) ? true : false,

			'POLL_QUESTION'		=> $parse_poll->message,
			
			'L_POLL_LENGTH'		=> ($post_data['poll_length']) ? sprintf($_CLASS['core_user']->get_lang('POLL_RUN_TILL'), $_CLASS['core_user']->format_date($post_data['poll_length'] + $post_data['poll_start'])) : '',
			'L_MAX_VOTES'		=> ($post_data['poll_max_options'] == 1) ? $_CLASS['core_user']->get_lang('MAX_OPTION_SELECT') : sprintf($_CLASS['core_user']->get_lang('MAX_OPTIONS_SELECT'), $post_data['poll_max_options']))
		);
		
		$parse_poll->message = implode("\n", $post_data['poll_options']);
		$parse_poll->format_display($post_data['enable_html'], $post_data['enable_bbcode'], $post_data['enable_urls'], $post_data['enable_smilies']);
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
$post_data['bbcode_uid'] = ($mode == 'quote' && !$preview && !$refresh && !sizeof($error)) ? $post_data['bbcode_uid'] : $message_parser->bbcode_uid;
$message_parser->decode_message($post_data['bbcode_uid']);

if ($mode == 'quote' && !$submit && !$preview && !$refresh)
{
	$message_parser->message = '[quote="' . $post_data['quote_username'] . '"]' . censor_text(trim($message_parser->message)) . "[/quote]\n";
}

if (($mode == 'reply' || $mode == 'quote') && !$submit && !$preview && !$refresh)
{
	$post_data['post_subject'] = ((!preg_match('/^Re:/', $post_data['post_subject'])) ? 'Re: ' : '') . censor_text($post_data['post_subject']);
}

$attachment_data = $message_parser->attachment_data;
$filename_data = $message_parser->filename_data;
$post_data['post_text'] = $message_parser->message;

if (sizeof($post_data['poll_options']) && $post_data['poll_title'])
{
	$message_parser->message = $post_data['poll_title'];
	$message_parser->bbcode_uid = $post_data['bbcode_uid'];

	$message_parser->decode_message();
	$post_data['poll_title'] = $message_parser->message;

	$message_parser->message = implode("\n", $post_data['poll_options']);
	$message_parser->decode_message();
	$post_data['poll_options'] = explode("\n", $message_parser->message);
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

if ($mode == 'post' || ($mode == 'edit' && $post_id == $post_data['topic_first_post_id']))
{
	$topic_type_toggle = posting_gen_topic_types($forum_id, $post_data['topic_type']);
}

$s_topic_icons = false;

if ($post_data['enable_icons'])
{
	$s_topic_icons = posting_gen_topic_icons($mode, $post_data['icon_id']);
}



$html_checked		= (isset($post_data['enable_html'])) ? !$post_data['enable_html'] : (($config['allow_html']) ? !$_CLASS['core_user']->user_data_get('html') : 1);
$bbcode_checked		= (isset($post_data['enable_bbcode'])) ? !$post_data['enable_bbcode'] : (($config['allow_bbcode']) ? !$_CLASS['core_user']->user_data_get('bbcode') : 1);
$smilies_checked	= (isset($post_data['enable_smilies'])) ? !$post_data['enable_smilies'] : (($config['allow_smilies']) ? !$_CLASS['core_user']->user_data_get('smilies') : 1);
$urls_checked		= (isset($post_data['enable_urls'])) ? !$post_data['enable_urls'] : 0;
$sig_checked		= $post_data['enable_sig'];

$lock_topic_checked	= (isset($topic_lock)) ? $topic_lock : (($post_data['topic_status'] == ITEM_LOCKED) ? 1 : 0);
$lock_post_checked	= (isset($post_lock)) ? $post_lock : $post_data['post_edit_locked'];

// If in edit mode, and the user is not the poster, we do not take the notification into account
$notify_checked		= (isset($notify)) ? $notify : (($mode == 'post') ? $_CLASS['core_user']->data['user_notify'] : $post_data['notify_set']);

// Page title & action URL, include session_id for security purpose
$s_action = "forums&amp;file=posting&amp;mode=$mode&amp;f=$forum_id";
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
	break;
}

// Build Navigation Links
generate_forum_nav($post_data);

// Build Forum Rules
generate_forum_rules($post_data);
/*
if ($config['enable_post_confirm'] && !$_CLASS['core_user']->is_user && ($mode == 'post' || $mode == 'reply' || $mode == 'quote'))
{
	// Show confirm image
	$sql = 'DELETE FROM ' . CONFIRM_TABLE . "
		WHERE session_id = '" . $db->sql_escape($_CLASS['core_user']->session_id) . "'
			AND confirm_type = " . CONFIRM_POST;
	$db->sql_query($sql);

	// Generate code
	$code = gen_rand_string(mt_rand(5, 8));
	$confirm_id = md5(unique_id($_CLASS['core_user']->ip));

	$sql = 'INSERT INTO ' . CONFIRM_TABLE . ' ' . $db->sql_build_array('INSERT', array(
		'confirm_id'	=> (string) $confirm_id,
		'session_id'	=> (string) $_CLASS['core_user']->session_id,
		'confirm_type'	=> (int) CONFIRM_POST,
		'code'			=> (string) $code)
	);
	$db->sql_query($sql);

	$template->assign_vars(array(
		'S_CONFIRM_CODE'			=> true,
		'CONFIRM_ID'				=> $confirm_id,
		'CONFIRM_IMAGE'				=> '<img src="' . append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=confirm&amp;id=' . $confirm_id . '&amp;type=' . CONFIRM_POST) . '" alt="" title="" />',
		'L_POST_CONFIRM_EXPLAIN'	=> sprintf($_CLASS['core_user']->get_lang('POST_CONFIRM_EXPLAIN'), '<a href="mailto:' . htmlentities($config['board_contact']) . '">', '</a>'),
	));
}*/

$s_hidden_fields = ($mode == 'reply' || $mode == 'quote') ? '<input type="hidden" name="topic_cur_post_id" value="' . $post_data['topic_last_post_id'] . '" />' : '';
$s_hidden_fields .= '<input type="hidden" name="lastclick" value="' . $current_time . '" />';
$s_hidden_fields .= ($draft_id || isset($_REQUEST['draft_loaded'])) ? '<input type="hidden" name="draft_loaded" value="' . request_var('draft_loaded', $draft_id) . '" />' : '';

$form_enctype = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off' || @ini_get('file_uploads') == '0' || !$config['allow_attachments'] || !$_CLASS['forums_auth']->acl_get('u_attach') || !$_CLASS['forums_auth']->acl_get('f_attach', $forum_id)) ? '' : ' enctype="multipart/form-data"';

// Start assigning vars for main posting page ...
$_CLASS['core_template']->assign_array(array(
	'L_POST_A'				=> $page_title,
	'L_ICON'					=> ($mode == 'reply' || $mode == 'quote' || ($mode == 'edit' && $post_id != $post_data['topic_first_post_id'])) ? $_CLASS['core_user']->lang['POST_ICON'] : $_CLASS['core_user']->lang['TOPIC_ICON'],
	'L_MESSAGE_BODY_EXPLAIN'	=> (intval($config['max_post_chars'])) ? sprintf($_CLASS['core_user']->get_lang('MESSAGE_BODY_EXPLAIN'), intval($config['max_post_chars'])) : '',
	
	'FORUM_NAME'			=> $post_data['forum_name'],
	'FORUM_DESC'			=> ($post_data['forum_desc']) ? strip_tags($post_data['forum_desc']) : '',
	'TOPIC_TITLE'			=> censor_text($post_data['topic_title']),
	'MODERATORS' 			=> empty($moderators) ? '' : implode(', ', $moderators[$forum_id]),
	'USERNAME'				=> ((!$preview && $mode != 'quote') || $preview) ? $post_data['username'] : '',
	'SUBJECT'				=> $post_data['post_subject'],
	'MESSAGE'				=> $post_data['post_text'],
	'HTML_STATUS'			=> ($html_status) ? $_CLASS['core_user']->lang['HTML_IS_ON'] : $_CLASS['core_user']->lang['HTML_IS_OFF'],
	'BBCODE_STATUS'			=> ($bbcode_status) ? sprintf($_CLASS['core_user']->lang['BBCODE_IS_ON'], '<a href="' . generate_link('forums&amp;file=faq&amp;mode=bbcode') . '" target="phpbbcode" onclick="window.open(\''.generate_link('forums&amp;file=faq&amp;mode=bbcode')."', '_phpbbcode', 'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=740');return false\">", '</a>') : sprintf($_CLASS['core_user']->lang['BBCODE_IS_OFF'], '<a href="' . generate_link('forums&amp;file=faq&amp;mode=bbcode') . '" target="_phpbbcode">', '</a>'),
	'IMG_STATUS'			=> ($img_status) ? $_CLASS['core_user']->lang['IMAGES_ARE_ON'] : $_CLASS['core_user']->lang['IMAGES_ARE_OFF'],
	'FLASH_STATUS'			=> ($flash_status) ? $_CLASS['core_user']->lang['FLASH_IS_ON'] : $_CLASS['core_user']->lang['FLASH_IS_OFF'],
	'SMILIES_STATUS'		=> ($smilies_status) ? $_CLASS['core_user']->lang['SMILIES_ARE_ON'] : $_CLASS['core_user']->lang['SMILIES_ARE_OFF'],
	'URL_STATUS'			=> ($url_status) ? $_CLASS['core_user']->lang['URL_IS_ON'] : $_CLASS['core_user']->lang['URL_IS_OFF'],
	'MINI_POST_IMG'			=> $_CLASS['core_user']->img('icon_post', $_CLASS['core_user']->lang['POST']),
	'POST_DATE'				=> ($post_data['post_time']) ? $_CLASS['core_user']->format_date($post_data['post_time']) : '',
	'ERROR'					=> empty($error) ? '' : implode('<br />', $error), 
	'TOPIC_TIME_LIMIT'		=> (int) $post_data['topic_time_limit'],
	'EDIT_REASON'			=> $post_data['post_edit_reason'],

	'U_VIEW_FORUM' 			=> generate_link('forums&amp;file=viewforum&amp;f=' . $forum_id),
	'U_VIEWTOPIC' 			=> ($mode != 'post') ? generate_link("forums&amp;file=viewtopic&amp;$forum_id&amp;t=$topic_id") : '',

	'S_EDIT_POST'			=> ($mode == 'edit'),
	'S_EDIT_REASON'			=> ($mode == 'edit' && $_CLASS['core_user']->data['user_id'] != $post_data['poster_id']),
	'S_DISPLAY_USERNAME'	=> (!$_CLASS['core_user']->is_user || ($mode == 'edit' && $post_data['post_username'])),
	'S_SHOW_TOPIC_ICONS'	=> $s_topic_icons,
	'S_DELETE_ALLOWED'		=> ($mode == 'edit' && (($post_id == $post_data['topic_last_post_id'] && $post_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id)) || $_CLASS['forums_auth']->acl_get('m_delete', $forum_id))) ? true : false,
	'S_HTML_ALLOWED'		=> $html_status,
	'S_HTML_CHECKED' 		=> ($html_checked) ? ' checked="checked"' : '',
	'S_BBCODE_ALLOWED'		=> $bbcode_status,
	'S_BBCODE_CHECKED' 		=> ($bbcode_checked) ? ' checked="checked"' : '',
	'S_SMILIES_ALLOWED'		=> $smilies_status,
	'S_SMILIES_CHECKED' 	=> ($smilies_checked) ? ' checked="checked"' : '',
	'S_SIG_ALLOWED'			=> ($_CLASS['forums_auth']->acl_get('f_sigs', $forum_id) && $config['allow_sig'] && $_CLASS['core_user']->is_user),
	'S_SIGNATURE_CHECKED' 	=> ($sig_checked) ? ' checked="checked"' : '',
	'S_NOTIFY_ALLOWED'		=> ($_CLASS['core_user']->is_user),
	'S_NOTIFY_CHECKED' 		=> ($notify_checked) ? ' checked="checked"' : '',
	'S_LOCK_TOPIC_ALLOWED'	=> (($mode == 'edit' || $mode == 'reply' || $mode == 'quote') && ($_CLASS['forums_auth']->acl_get('m_lock', $forum_id) || ($_CLASS['forums_auth']->acl_get('f_user_lock', $forum_id) && $_CLASS['core_user']->is_user && !empty($post_data['topic_poster']) && $_CLASS['core_user']->data['user_id'] == $post_data['topic_poster'] && $post_data['topic_status'] == ITEM_UNLOCKED))) ? true : false,
	'S_LOCK_TOPIC_CHECKED'	=> ($lock_topic_checked) ? ' checked="checked"' : '',
	'S_LOCK_POST_ALLOWED'	=> ($mode == 'edit' && $_CLASS['forums_auth']->acl_get('m_edit', $forum_id)),
	'S_LOCK_POST_CHECKED'	=> ($lock_post_checked) ? ' checked="checked"' : '',
	'S_LINKS_ALLOWED'		=> $url_status,
	'S_MAGIC_URL_CHECKED' 	=> ($urls_checked) ? ' checked="checked"' : '',
	'S_TYPE_TOGGLE'			=> $topic_type_toggle,
	'S_SAVE_ALLOWED'		=> ($_CLASS['forums_auth']->acl_get('u_savedrafts') && $_CLASS['core_user']->is_user),
	'S_HAS_DRAFTS'			=> ($_CLASS['forums_auth']->acl_get('u_savedrafts') && $_CLASS['core_user']->is_user && $post_data['drafts']),
	'S_FORM_ENCTYPE'		=> $form_enctype,

	'S_BBCODE_IMG'			=> $img_status,
	'S_BBCODE_URL'			=> $url_status,
	'S_BBCODE_FLASH'		=> $flash_status,
	'S_BBCODE_QUOTE'		=> $quote_status,

	'S_POST_ACTION' 		=> $s_action,
	'S_HIDDEN_FIELDS'		=> $s_hidden_fields)
);

// Build custom bbcodes array
//display_custom_bbcodes();

// Poll entry
if (($mode == 'post' || ($mode == 'edit' && $post_id == $post_data['topic_first_post_id'] && (!$post_data['poll_last_vote'] || $_CLASS['forums_auth']->acl_get('m_edit', $forum_id))))
	&& $_CLASS['forums_auth']->acl_get('f_poll', $forum_id))
{
	$_CLASS['core_template']->assign_array(array(
		'S_SHOW_POLL_BOX'		=> true,
		'S_POLL_VOTE_CHANGE'    => ($_CLASS['forums_auth']->acl_get('f_votechg', $forum_id)),
		'S_POLL_DELETE'			=> ($mode == 'edit' && !empty($post_data['poll_options']) && ((!$post_data['poll_last_vote'] && $post_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id)) || $_CLASS['forums_auth']->acl_get('m_delete', $forum_id))),

		'L_POLL_OPTIONS_EXPLAIN'=> sprintf($_CLASS['core_user']->lang['POLL_OPTIONS_EXPLAIN'], $config['max_poll_options']),

		'VOTE_CHANGE_CHECKED'	=> (!empty($post_data['poll_vote_change'])) ? ' checked="checked"' : '',
		'POLL_TITLE'			=> (isset($post_data['poll_title'])) ? $post_data['poll_title'] : '',
		'POLL_OPTIONS'			=> (!empty($post_data['poll_options'])) ? implode("\n", $post_data['poll_options']) : '',
		'POLL_MAX_OPTIONS'		=> (isset($post_data['poll_max_options'])) ? (int) $post_data['poll_max_options'] : 1,
		'POLL_LENGTH'			=> $post_data['poll_length'])
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
if ($_CLASS['forums_auth']->acl_get('f_attach', $forum_id) && $_CLASS['forums_auth']->acl_get('u_attach') && $config['allow_attachments'] && $form_enctype)
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

make_jumpbox(generate_link('forums&amp;file=viewforum'));

$_CLASS['core_template']->display('modules/forums/posting_body.html');


/**
* Do the various checks required for removing posts as well as removing it
*/
function handle_post_delete($forum_id, $topic_id, $post_id, &$post_data)
{
	global $_CLASS;

	// If moderator removing post or user itself removing post, present a confirmation screen
	if ($_CLASS['forums_auth']->acl_get('m_delete', $forum_id) || ($post_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['core_user']->data['is_registered'] && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id) && $post_id == $post_data['topic_last_post_id']))
	{
		$s_hidden_fields = build_hidden_fields(array(
			'p'		=> $post_id,
			'f'		=> $forum_id,
			'mode'	=> 'delete')
		);

		if (confirm_box(true))
		{
			$data = array(
				'topic_first_post_id'	=> $post_data['topic_first_post_id'],
				'topic_last_post_id'	=> $post_data['topic_last_post_id'],
				'topic_approved'		=> $post_data['topic_approved'],
				'topic_type'			=> $post_data['topic_type'],
				'post_approved'			=> $post_data['post_approved'],
				'post_reported'			=> $post_data['post_reported'],
				'post_time'				=> $post_data['post_time'],
				'poster_id'				=> $post_data['poster_id'],
				'post_postcount'		=> $post_data['post_postcount']
			);

			$next_post_id = delete_post($forum_id, $topic_id, $post_id, $data);

			if ($post_data['topic_first_post_id'] == $post_data['topic_last_post_id'])
			{
				add_log('mod', $forum_id, $topic_id, 'LOG_DELETE_TOPIC', $post_data['topic_title']);

				$meta_info = generate_link('forums&amp;file=viewforumf='.$forum_id);
				$message = $_CLASS['core_user']->lang['POST_DELETED'];
			}
			else
			{
				add_log('mod', $forum_id, $topic_id, 'LOG_DELETE_POST', $post_data['post_subject']);

				$meta_info = generate_link("forums&amp;file=viewtopicf=$forum_id&amp;t=$topic_id&amp;p=$next_post_id") . "#p$next_post_id";
				$message = $_CLASS['core_user']->lang['POST_DELETED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_TOPIC'], '<a href="' . $meta_info . '">', '</a>');
			}

			$_CLASS['core_display']->meta_refresh(3, $meta_info);
			$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_FORUM'], '<a href="' . generate_link('forums&amp;file=viewforumf=' . $forum_id) . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			confirm_box(false, 'DELETE_MESSAGE', $s_hidden_fields);
		}
	}

	// If we are here the user is not able to delete - present the correct error message
	if ($post_data['poster_id'] != $_CLASS['core_user']->data['user_id'] && !$_CLASS['forums_auth']->acl_get('f_delete', $forum_id))
	{
		trigger_error('DELETE_OWN_POSTS');
	}

	if ($post_data['poster_id'] == $_CLASS['core_user']->data['user_id'] && $_CLASS['forums_auth']->acl_get('f_delete', $forum_id) && $post_id != $post_data['topic_last_post_id'])
	{
		trigger_error('CANNOT_DELETE_REPLIED');
	}

	trigger_error('USER_CANNOT_DELETE');
}

?>