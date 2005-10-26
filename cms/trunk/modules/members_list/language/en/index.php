<?php
// -------------------------------------------------------------
//
// $Id: memberlist.php,v 1.3 2004/05/26 18:55:27 acydburn Exp $
//
// FILENAME  : memberlist.php [ English ]
// STARTED   : Sat Dec 16, 2000
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// DEVELOPERS PLEASE NOTE 
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$this->lang += array(
	'ABOUT_USER'			=> 'Profile',
	'ACTIVE_IN_FORUM'		=> 'Most active forum',
	'ACTIVE_IN_TOPIC'		=> 'Most active topic',
	'ADD_FRIEND'			=> 'Add friend',
	'ADD_FOE'				=> 'Add foe',
	'AFTER'					=> 'After',
	'AIM'					=> 'AIM',

	'BEFORE'				=> 'Before',

	'CC_EMAIL'				=> 'Send a copy of this email to yourself',
	'CONTACT_USER'			=> 'Contact',

	'DISPLAY'				=> 'Display',
	'DEST_LANG'				=> 'Language',
	'DEST_LANG_EXPLAIN'		=> 'Select an appropriate language (if available) for the recipient of this message.',

	'EMAIL_BODY_EXPLAIN'	=> 'This message will be sent as plain text, do not include any HTML or BBCode. The return address for this message will be set to your email address.',
	'EMAIL_DISABLED'		=> 'Sorry but all email related functions have been disabled.',
	'EMAIL_SENT'			=> 'The email has been sent.',
	'EMAIL_TOPIC_EXPLAIN'	=> 'This message will be sent as plain text, do not include any HTML or BBCode. Please note that the topic information is already included in the message. The return address for this message will be set to your email address.',
	'EMPTY_ADDRESS_EMAIL'	=> 'You must provide a valid email address for the recipient.',
	'EMPTY_MESSAGE_EMAIL'	=> 'You must enter a message to be emailed.',
	'EMPTY_NAME_EMAIL'		=> 'You must enter the real name of the recipient.',
	'EMPTY_SUBJECT_EMAIL'	=> 'You must specify a subject for the email.',
	'EQUAL_TO'				=> 'Equal to',

	'FIND_USERNAME_EXPLAIN'	=> 'Use this form to search for specific members. You do not need to fill out all fields. To match partial data use * as a wildcard. When entering dates use the format yyyy-mm-dd, e.g. 2002-01-01. Use the mark checkboxes to select one or more usernames (several usernames may be accepted depending on the form itself). Alternatively you can mark the users required and click the Insert Marked button.',
	'FLOOD_EMAIL_LIMIT'		=> 'You cannot send another email at this time. Please try again later.',

	'GROUP_INFORMATION'	    => 'Group Information',
	'GROUP_NAME'		    => 'Group name',
	'GROUP_DESC'		    => 'Group description',
	'GROUP_LEADER'			=> 'Group leader',
	
	'HIDE_MEMBER_SEARCH'    => 'Hide member search',
	
	'ICQ'					=> 'ICQ',
	'IM_ADD_CONTACT'		=> 'Add Contact',
	'IM_AIM'				=> 'Please note that you need AOL Instant Messenger installed to use this.',
	'IM_AIM_EXPRESS'		=> 'AIM Express',
	'IM_DOWNLOAD_APP'		=> 'Download Application',
	'IM_ICQ'				=> 'Please note that users may have elected to not receive unsolicited instant messages.',
	'IM_JABBER'				=> 'Please note that users may have elected to not receive unsolicited instant messages.',
	'IM_JABBER_SUBJECT'		=> 'This is an automated message please do not reply! Message from user %1$s at %2$s',
	'IM_MESSAGE'			=> 'Your Message',
	'IM_MSN'				=> 'Please note that you need Windows Messenger installed to use this.',
	'IM_NAME'				=> 'Your Name',
	'IM_NO_JABBER'			=> 'Sorry, direct messaging of Jabber users is not supported on this server. You will need a Jabber client installed on your system to contact the recipient above.',
	'IM_RECIPIENT'			=> 'Recipient',
	'IM_SEND'				=> 'Send Message',
	'IM_SEND_MESSAGE'		=> 'Send Message',
	'IM_SENT_JABBER'		=> 'Your message to %1$s has been sent successfully.',

	'JABBER'				=> 'Jabber',

	'LAST_ACTIVE'			=> 'Last active',
	'LESS_THAN'				=> 'Less than',
	'LEADER'				=> 'Leader',
	'LIST_USER'				=> '1 User',
	'LIST_USERS'			=> '%d Users',

	'MORE_THAN'				=> 'More than',
	'MSNM'					=> 'MSNM',

	'NO_EMAIL'				=> 'You are not permitted to send email to this user.',
	'NO_VIEW_USERS'			=> 'You are not authorised to view the member list or profiles.',

	'ORDER'					=> 'Order',

	'POST_IP'				=> 'Posted from IP/domain',

	'RANK'					=> 'Rank',
	'REAL_NAME'				=> 'Recipient Name',
	'RECIPIENT'				=> 'Recipient',

	'SEARCH_USER_POSTS'		=> 'Search users posts',
	'SELECT_MARKED'			=> 'Select Marked',
	'SELECT_SORT_METHOD'	=> 'Select sort method',
	'SEND_EMAIL'			=> 'Email',
	'SEND_IM'				=> 'Instant Messaging',
	'SEND_MESSAGE'			=> 'Message',
	'SORT_EMAIL'			=> 'Email',
	'SORT_LAST_ACTIVE'		=> 'Last active',
	'SORT_POST_COUNT'		=> 'Post count',
	'SORT_RANK'				=> 'Rank',
	'This_group_3'	   		=> 'This is a special group, special groups are managed by the board administrators.',
	'This_group_0'	   		=> 'This is a closed group, new members cannot automatically join.',
	'This_group_group'	    => 'This is an open group, click to request membership',

	'USERNAME_BEGINS_WITH'	=> 'Username begins with',
	'USER_FORUM'			=> 'Forum statistics',
	'USER_ONLINE'			=> 'Online',
	'USER_PRESENCE'			=> 'Forum presence',

	'VIEWING_PROFILE'		=> ' %s Profile',
	'VISITED'				=> 'Last visited',

	'WWW'					=> 'Website',

	'YIM'					=> 'YIM'
);

?>