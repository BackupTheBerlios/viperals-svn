<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// $Id: report.php,v 1.16 2004/07/10 22:47:43 acydburn Exp $
//
// FILENAME  : report.php 
// STARTED   : Thu Apr 3, 2003
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    header('location: ../../');
    die();
}

require_once($site_file_root.'includes/forums/functions.php');
load_class($site_file_root.'includes/forums/auth.php', 'auth');
$_CLASS['auth']->acl($_CLASS['core_user']->data);

$_CLASS['core_user']->add_lang('mcp');

// Report PM or Post?
$id = request_var('p', request_var('pm', 0));
$report_post = (request_var('p', 0)) ? true : false;
$reason_id	= request_var('reason_id', 0);
$user_notify= (!empty($_REQUEST['notify']) && $_CLASS['core_user']->data['user_id'] != ANONYMOUS) ? true : false;
$report_text= request_var('report_text', '');

if (!$id)
{
	trigger_error('INVALID_MODE');
}

$redirect_url = ($report_post) ? generate_link("Forums&amp;file=viewtopic&p=$id#$id") : generate_link('Control_Panel&i=pm&p='.$id);
// Has the report been cancelled?
if (isset($_POST['cancel']))
{
	redirect($redirect_url);
}

// Grab all relevant data
if ($report_post)
{
	$sql = 'SELECT f.*, t.*, p.*
		FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . " f
		WHERE p.post_id = $id
			AND p.topic_id = t.topic_id
			AND p.forum_id = f.forum_id";
}
else
{
	// Only the user itself is able to report his Private Messages
	$sql = 'SELECT p.*, t.*
		FROM ' . PRIVMSGS_TABLE . ' p, ' . PRIVMSGS_TO_TABLE . " t
		WHERE t.msg_id = $id
			AND t.user_id = " . $_CLASS['core_user']->data['user_id'] . '
			AND t.msg_id = p.msg_id';
}

$result = $_CLASS['core_db']->sql_query($sql);
$report_data = $_CLASS['core_db']->sql_fetchrow($result);
$_CLASS['core_db']->sql_freeresult($result);

if (!$report_data)
{
	$message = ($report_post) ? $_CLASS['core_user']->lang['POST_NOT_EXIST'] : $_CLASS['core_user']->lang['PM_NOT_EXIST'];
	trigger_error($message);
}

if ($report_post)
{
	$forum_id = $report_data['forum_id'];
	$topic_id = $report_data['topic_id'];

	// Check required permissions
	$acl_check_ary = array('f_list' => 'POST_NOT_EXIST', 'f_read' => 'USER_CANNOT_READ', 'f_report' => 'USER_CANNOT_REPORT');
	
	foreach ($acl_check_ary as $acl => $error)
	{
		if (!$_CLASS['auth']->acl_get($acl, $forum_id))
		{
			trigger_error($error);
		}
	}
	unset($acl_check_ary);
}
else
{
	if (!$config['auth_report_pm'] || !$_CLASS['auth']->acl_get('u_pm_report'))
	{
		trigger_error('USER_CANNOT_REPORT');
	}
}

// Check if the post has already been reported by this user
$sql = 'SELECT *
	FROM ' . REPORTS_TABLE . '
	WHERE ' . (($report_post) ? "post_id = $id" : "msg_id = $id") . '
		AND user_id = ' . $_CLASS['core_user']->data['user_id'];
$result = $_CLASS['core_db']->sql_query($sql);
$row = $_CLASS['core_db']->sql_fetchrow($result);
$_CLASS['core_db']->sql_freeresult($result);

if ($row)
{
	if ($_CLASS['core_user']->data['user_id'] != ANONYMOUS)
	{
		// A report exists, extract $row if we're going to display the form
		if ($reason_id)
		{
			$report_id = (int) $row['report_id'];
		}
		else
		{
			// Overwrite set variables
			extract($row);
		}
	}
	else
	{
		trigger_error($_CLASS['core_user']->lang['ALREADY_REPORTED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang[(($report_post) ? 'RETURN_TOPIC' : 'RETURN_MESSAGE')], '<a href="' . $redirect_url . '">', '</a>'));
	}
}
else
{
	$report_id = 0;
}

// Has the report been confirmed?
if (isset($_POST['submit']) && $reason_id)
{
	$sql = 'SELECT reason_name, reason_description
		FROM ' . REASONS_TABLE . " 
		WHERE reason_id = $reason_id";
	$result = $_CLASS['core_db']->sql_query($sql);
	$row = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	if (!$row || (!$report_text && $row['reason_name'] == 'other'))
	{
		trigger_error('EMPTY_REPORT');
	}

	$reason_desc = (!empty($_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][$row['reason_name']])) ? $_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][$row['reason_name']] : $row['reason_description'];
	
	$sql_ary = array(
		'reason_id'		=> (int) $reason_id,
		'post_id'		=> ($report_post) ? $id : 0,
		'msg_id'		=> ($report_post) ? 0 : $id,
		'user_id'		=> (int) $_CLASS['core_user']->data['user_id'],
		'user_notify'	=> (int) $user_notify,
		'report_time'	=> (int) time(),
		'report_text'	=> (string) $report_text
	);

	if ($report_id)
	{
		$sql = 'UPDATE ' . REPORTS_TABLE . '
			SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
			WHERE report_id = ' . $report_id;
		$_CLASS['core_db']->sql_query($sql);
	}
	else
	{
		$sql = 'INSERT INTO ' . REPORTS_TABLE . ' ' . 
			$_CLASS['core_db']->sql_build_array('INSERT', $sql_ary);
		$_CLASS['core_db']->sql_query($sql);
		$report_id = $_CLASS['core_db']->sql_nextid();
	}

	if ($report_post)
	{
		if (!$report_data['post_reported'])
		{
			$sql = 'UPDATE ' . POSTS_TABLE . ' 
				SET post_reported = 1 
				WHERE post_id = ' . $id;
			$_CLASS['core_db']->sql_query($sql);
		}

		if (!$report_data['topic_reported'])
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . ' 
				SET topic_reported = 1 
				WHERE topic_id = ' . $report_data['topic_id'];
			$_CLASS['core_db']->sql_query($sql);
		}
	}
	else
	{
		if (!$report_data['message_reported'])
		{
			$sql = 'UPDATE ' . PRIVMSGS_TABLE . " 
				SET message_reported = 1 
				WHERE msg_id = $id";
			$_CLASS['core_db']->sql_query($sql);
		}
	}

	// Send Notifications
	// PM: Reported Post is put into all admin's boxes (not notifying about 'this' PM)
	// All persons get notified about a new report, if notified by PM, send out email notifications too
	
	// Send notifications to moderators
	$acl_list = ($report_post) ? $_CLASS['auth']->acl_get_list(false, array('m_', 'a_'), array(0, $report_data['forum_id'])) : $_CLASS['auth']->acl_get_list(false, 'a_', 0);
	$notify_user = ($report_post) ? $acl_list[$report_data['forum_id']]['m_'] : array();
	$notify_user = array_unique(array_merge($notify_user, $acl_list[0]['a_']));
	unset($acl_list);

	// Send reported PM to responsible persons (admins)
	if (!$report_post)
	{
		foreach ($notify_user as $user_id)
		{
			$_CLASS['core_db']->sql_query('INSERT INTO ' . PRIVMSGS_TO_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
				'msg_id'	=> (int) $id,
				'user_id'	=> (int) $user_id,
				'author_id'	=> (int) $report_data['author_id'],
				'folder_id'	=> PRIVMSGS_NO_BOX,
				'new'		=> 1,
				'unread'	=> 1,
				'forwarded'	=> 0))
			);
		}

		// Update Status
		$sql = 'UPDATE ' . USERS_TABLE . ' 
			SET user_new_privmsg = user_new_privmsg + 1, user_unread_privmsg = user_unread_privmsg + 1
			WHERE user_id IN (' . implode(', ', $notify_user) . ')';
		$_CLASS['core_db']->sql_query($sql);
	}

	// How to notify them?
	$sql = 'SELECT user_id, username, user_options, user_lang, user_email, user_notify_type, user_jabber 
		FROM ' . USERS_TABLE . '
		WHERE user_id IN (' . implode(', ', $notify_user) . ')';
	$result = $_CLASS['core_db']->sql_query($sql);

	$notify_user = array();
	while ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$notify_user[$row['user_id']] = array(
			'name'	=> $row['username'],
			'email' => $row['user_email'],
			'jabber'=> $row['user_jabber'],
			'lang'	=> $row['user_lang'],
			'notify_type'	=> $row['user_notify_type'],
			
			'pm'	=> $_CLASS['core_user']->optionget('report_pm_notify', $row['user_options'])
		);
	}
	$_CLASS['core_db']->sql_freeresult($result);

	$report_data = array(
		'id'		=> $id,
		'report_id'	=> $report_id,
		'reporter'	=> $_CLASS['core_user']->data['username'],
		'reason'	=> $reason_desc,
		'text'		=> $report_text,
		'subject'	=> ($report_post) ? $report_data['post_subject'] : $report_data['message_subject'],
		'view_post'	=> ($report_post) ? generate_link("Forums&amp;file=viewtopic&amp;f={$report_data['forum_id']}&t={$report_data['topic_id']}&p=$id&e=$id") : ''
	);

	report_notification($notify_user, $report_post, $report_data);

	$_CLASS['core_display']->meta_refresh(3, $redirect_url);

	$message = $_CLASS['core_user']->lang[(($report_post) ? 'POST' : 'MESSAGE') . '_REPORTED_SUCCESS'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang[(($report_post) ? 'RETURN_TOPIC' : 'RETURN_MESSAGE')], '<a href="' . $redirect_url . '">', '</a>');
	trigger_error($message);
}

// Generate the form
$sql = 'SELECT * 
	FROM ' . REASONS_TABLE . ' 
	ORDER BY reason_priority ASC';
$result = $_CLASS['core_db']->sql_query($sql);

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$row['reason_name'] = strtoupper($row['reason_name']);

	$reason_title = (!empty($_CLASS['core_user']->lang['report_reasons']['TITLE'][$row['reason_name']])) ? $_CLASS['core_user']->lang['report_reasons']['TITLE'][$row['reason_name']] : ucwords(str_replace('_', ' ', $row['reason_name']));

	$reason_desc = (!empty($_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][$row['reason_name']])) ? $_CLASS['core_user']->lang['report_reasons']['DESCRIPTION'][$row['reason_name']] : $row['reason_desc'];

	$_CLASS['core_template']->assign_vars_array('reason', array(
		'ID'			=>	$row['reason_id'],
		'NAME'			=>	htmlspecialchars($reason_title),
		'DESCRIPTION'	=>	htmlspecialchars($reason_desc),
		'S_SELECTED'    =>	($row['reason_id'] == $reason_id) ? true : false)
	);
}
$_CLASS['core_db']->sql_freeresult($result);

$u_report = ($report_post) ? "p=$id" : "pm=$id";

$_CLASS['core_template']->assign(array(
	'REPORT_TEXT'		=>	$report_text,
	'S_REPORT_ACTION'	=>	generate_link("Forums&amp;file=report&amp;$u_report" . (($report_id) ? "&amp;report_id=$report_id" : '')),
	'S_NOTIFY'			=> (!empty($user_notify)) ? true : false,
	'S_CAN_NOTIFY'		=> ($_CLASS['core_user']->data['user_id'] == ANONYMOUS) ? false : true,
	'S_REPORT_POST'		=> $report_post)
);

if ($report_post)
{
	generate_forum_nav($report_data);
}

/// lets assign those language that are needed///
$_CLASS['core_template']->assign(array(
	'L_REPORT_POST'				=> $_CLASS['core_user']->lang['REPORT_POST'],
	'L_REPORT_POST_EXPLAIN'		=> $_CLASS['core_user']->lang['REPORT_POST_EXPLAIN'],
	'L_REASON'					=> $_CLASS['core_user']->lang['REASON'],
	'L_REPORT_NOTIFY'			=> $_CLASS['core_user']->lang['REPORT_NOTIFY'],
	'L_REPORT_NOTIFY_EXPLAIN'	=> $_CLASS['core_user']->lang['REPORT_NOTIFY_EXPLAIN'],
	'L_YES'						=> $_CLASS['core_user']->lang['YES'],
	'L_NO'						=> $_CLASS['core_user']->lang['NO'],
	'L_MORE_INFO'				=> $_CLASS['core_user']->lang['MORE_INFO'],
	'L_CAN_LEAVE_BLANK'			=> $_CLASS['core_user']->lang['CAN_LEAVE_BLANK'],
	'L_SUBMIT'					=> $_CLASS['core_user']->lang['SUBMIT'],
	'L_CANCEL'					=> $_CLASS['core_user']->lang['CANCEL'])
);

$_CLASS['core_display']->display_head(($report_post) ? $_CLASS['core_user']->lang['REPORT_POST'] : $_CLASS['core_user']->lang['REPORT_MESSAGE']);

page_header();

$_CLASS['core_template']->display('modules/Forums/report_body.html');

$_CLASS['core_display']->display_footer();

function report_notification($notify_user, $report_post, $report_data)
{
	global $config, $site_file_root;

	require_once($site_file_root.'includes/forums/functions_messenger.php');
	require_once($site_file_root.'includes/forums/functions_privmsgs.php');
	$messenger = new messenger();

	$email_sig = str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']);
	$email_template = ($report_post) ? 'new_report_post' : 'new_report_pm';
	$view_report_url = ($report_post) ? generate_link('Forums&amp;file=mcp&amp;i=queue&r=' . $report_data['report_id'], array('full' => true, 'sid' => false)) : generate_link('Forums&amp;file=mcp&amp;i=pm&p='. $report_data['id'] . '&r=' . $report_data['report_id'], array('full' => true, 'sid' => false));

	foreach ($notify_user as $user_id => $notify_row)
	{
		// Send notification by email
		if (!$notify_row['pm'])
		{
			$messenger->to($notify_row['email'], $notify_row['name']);
			$messenger->im($notify_row['jabber'], $notify_row['name']);
			$messenger->replyto($config['board_email']);

			$messenger->template($email_template, $notify_row['lang']);

			$messenger->assign_vars(array(
				'EMAIL_SIG'		=> $email_sig,
				'SITENAME'		=> $config['sitename'],
				'USERNAME'		=> $notify_row['name'],
				'SUBJECT'		=> $report_data['subject'],
				'REPORTER'		=> $report_data['reporter'],

				'REPORT_REASON'	=> $report_data['reason'],
				'REPORT_TEXT'	=> $report_data['text'],

				'U_VIEW_REPORT'	=> $view_report_url,
				'U_VIEW_POST'	=> generate_board_url() . '/' . $report_data['view_post'])
			);

			$messenger->send($notify_row['notify_type']);
			$messenger->reset();

			$messenger->save_queue();
		}
		else
		{
			// Use messenger for getting the correct message, we use the email template
			$messenger->template($email_template, $notify_row['lang']);
			
			$messenger->assign_vars(array(
				'EMAIL_SIG'		=> $email_sig,
				'SITENAME'		=> $config['sitename'],
				'USERNAME'		=> $notify_row['name'],
				'SUBJECT'		=> $report_data['subject'],
				'REPORTER'		=> $report_data['reporter'],

				'REPORT_REASON'	=> $report_data['reason'],
				'REPORT_TEXT'	=> $report_data['text'],

				'U_VIEW_REPORT'	=> generate_board_url() . '/' . $view_report_url)
			);

			// break the sending process...
			$messenger->send(false, true);
			$messenger->reset();
			
			// do not put in reporters outbox
			submit_pm('post', $report_data['subject'], '', array(), array(), array(
				'address_list'	=> array('u' => array($user_id => 'to')),
				'icon_id'		=> 0,
				'enable_bbcode' 	=> 0,
				'enable_html' 		=> 0,
				'enable_smilies' 	=> 0,
				'enable_magic_url' 	=> 1,
				'enable_sig' 		=> 0,
				'message_md5'		=> md5($messenger->msg),
				'bbcode_bitfield'	=> 0,
				'bbcode_uid'		=> 0,
				'attachment_data'	=> array(),
				'filename_data'		=> array(),
				'message'			=> $messenger->msg				
				), true, false);
		}
	}
	unset($messenger);
}

?>