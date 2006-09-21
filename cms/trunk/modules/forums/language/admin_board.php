<?php
/** 
*
* acp_board [English]
*
* @package language
* @version $Id: board.php,v 1.38 2006/09/13 16:08:36 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/


// DEVELOPERS PLEASE NOTE 
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

// Board Settings
$this->lang += array(
	'ACP_BOARD_SETTINGS_EXPLAIN'	=> 'Here you can determine the basic operation of your board, from the site name through user registration to private messaging.',

	'CUSTOM_DATEFORMAT'				=> 'Custom...',
	'DEFAULT_DATE_FORMAT'			=> 'Date Format',
	'DEFAULT_DATE_FORMAT_EXPLAIN'	=> 'The date format is the same as the PHP date function.',
	'DEFAULT_LANGUAGE'				=> 'Default Language',
	'DEFAULT_STYLE'					=> 'Default Style',
	'DISABLE_BOARD'					=> 'Disable board',
	'DISABLE_BOARD_EXPLAIN'			=> 'This will make the board unavailable to users. You can also enter a short (255 character) message to display if you wish.',
	'OVERRIDE_STYLE'				=> 'Override user style',
	'OVERRIDE_STYLE_EXPLAIN'		=> 'Replaces users style with the default.',
	'RELATIVE_DAYS'					=> 'Relative days',
	'SITE_DESC'						=> 'Site description',
	'SITE_NAME'						=> 'Site name',
	'SYSTEM_DST'					=> 'Enable Daylight Savings Time',
	'SYSTEM_TIMEZONE'				=> 'System Timezone',
	'WARNINGS_EXPIRE'				=> 'Warning duration',
	'WARNINGS_EXPIRE_EXPLAIN'		=> 'Number of days after it is issued before a warning will expire from a user\'s record',
);

// Board Features
$this->lang += array(
	'ACP_BOARD_FEATURES_EXPLAIN'	=> 'Here you can enable/disable several board features',

	'ALLOW_ATTACHMENTS'			=> 'Allow Attachments',
	'ALLOW_BOOKMARKS'			=> 'Allow bookmarking topics',
	'ALLOW_BOOKMARKS_EXPLAIN'	=> 'User is able to store personal bookmarks',
	'ALLOW_BBCODE'				=> 'Allow BBCode',
	'ALLOW_FORUM_NOTIFY'		=> 'Allow Forum Watching',
	'ALLOW_NAME_CHANGE'			=> 'Allow Username changes',
	'ALLOW_NO_CENSORS'			=> 'Allow Disable of Censors',
	'ALLOW_NO_CENSORS_EXPLAIN'	=> 'User can disable word censoring.',
	'ALLOW_PM_ATTACHMENTS'		=> 'Allow Attachments in Private Messages',
	'ALLOW_SIG'					=> 'Allow Signatures',
	'ALLOW_SIG_BBCODE'			=> 'Allow BBCode in user signatures',
	'ALLOW_SIG_FLASH'			=> 'Allow use of FLASH BBCode Tag in user signatures',
	'ALLOW_SIG_IMG'				=> 'Allow use of IMG BBCode Tag in user signatures',
	'ALLOW_SIG_LINKS'			=> 'Allow use of links in user signatures',
	'ALLOW_SIG_LINKS_EXPLAIN'	=> 'If disallowed the URL bbcode tag and automatic/magic urls are disabled.',
	'ALLOW_SIG_SMILIES'			=> 'Allow use of smilies in user signatures',
	'ALLOW_SMILIES'				=> 'Allow Smilies',
	'ALLOW_TOPIC_NOTIFY'		=> 'Allow Topic Watching',
	'BOARD_PM'					=> 'Private Messaging',
	'BOARD_PM_EXPLAIN'			=> 'Enable or disable private messaging for all users.',
);

// Post Settings
$this->lang += array(
	'ACP_POST_SETTINGS_EXPLAIN'			=> 'Here you can set all default settings for posting',
	'ALLOW_POST_LINKS'					=> 'Allow links in posts/private messages',
	'ALLOW_POST_LINKS_EXPLAIN'			=> 'If disallowed the URL bbcode tag and automatic/magic urls are disabled.',

	'BUMP_INTERVAL'					=> 'Bump Interval',
	'BUMP_INTERVAL_EXPLAIN'			=> 'Number of minutes, hours or days between the last post to a topic and the ability to bump this topic.',
	'CHAR_LIMIT'					=> 'Max characters per post',
	'CHAR_LIMIT_EXPLAIN'			=> 'Set to 0 for unlimited characters.',
	'DISPLAY_LAST_EDITED'			=> 'Display last edited time information',
	'DISPLAY_LAST_EDITED_EXPLAIN'	=> 'Choose if the last edited by information to be displayed on posts',
	'EDIT_TIME'						=> 'Limit editing time',
	'EDIT_TIME_EXPLAIN'				=> 'Limits the time available to edit a new post, zero equals infinity',
	'FLOOD_INTERVAL'				=> 'Flood Interval',
	'FLOOD_INTERVAL_EXPLAIN'		=> 'Number of seconds a user must wait between posting new messages. To enable users to ignore this alter their permissions.',
	'HOT_THRESHOLD'					=> 'Posts for Popular Threshold, Set to 0 to disable hot topics.',
	'MAX_POLL_OPTIONS'				=> 'Max number of poll options',
	'MAX_POST_FONT_SIZE'			=> 'Max font size per post',
	'MAX_POST_FONT_SIZE_EXPLAIN'	=> 'Set to 0 for unlimited font size.',
	'MAX_POST_IMG_HEIGHT'			=> 'Max image height per post',
	'MAX_POST_IMG_HEIGHT_EXPLAIN'	=> 'Maximum height of an image/flash file in postings. Set to 0 for unlimited size.',
	'MAX_POST_IMG_WIDTH'			=> 'Max image width per post',
	'MAX_POST_IMG_WIDTH_EXPLAIN'	=> 'Maximum width of an image/flash file in postings. Set to 0 for unlimited size.',
	'MAX_POST_URLS'					=> 'Max links per post',
	'MAX_POST_URLS_EXPLAIN'			=> 'Set to 0 for unlimited links.',
	'POSTING'						=> 'Posting',
	'POSTS_PER_PAGE'				=> 'Posts Per Page',
	'QUOTE_DEPTH_LIMIT'				=> 'Max nested quotes per post',
	'QUOTE_DEPTH_LIMIT_EXPLAIN'		=> 'Set to 0 for unlimited depth.',
	'SMILIES_LIMIT'					=> 'Max smilies per post',
	'SMILIES_LIMIT_EXPLAIN'			=> 'Set to 0 for unlimited smilies.',
	'TOPICS_PER_PAGE'				=> 'Topics Per Page',
);
// Load Settings
$this->lang += array(
	'ACP_LOAD_SETTINGS_EXPLAIN'	=> 'Here you can enable and disable certain board functions to reduce the amount of processing required. On most servers there is no need to disable any functions. However on certain systems or in shared hosting environments it may be beneficial to disable capabilities you do not really need. You can also specify limits for system load and active sessions beyond which the board will go offline.',

	'CUSTOM_PROFILE_FIELDS'			=> 'Custom Profile Fields',
	'LIMIT_LOAD'					=> 'Limit system load',
	'LIMIT_LOAD_EXPLAIN'			=> 'If the 1 minute system load exceeds this value the board will go offline, 1.0 equals ~100% utilisation of one processor. This only functions on UNIX based servers.',
	'LIMIT_SESSIONS'				=> 'Limit sessions',
	'LIMIT_SESSIONS_EXPLAIN'		=> 'If the number of sessions exceeds this value within a one minute period the board will go offline. Set to 0 for unlimited sessions.',
	'LOAD_CPF_MEMBERLIST'			=> 'Display custom profile fields in memberlist',
	'LOAD_CPF_VIEWPROFILE'			=> 'Display custom profile fields in user profiles',
	'LOAD_CPF_VIEWTOPIC'			=> 'Display custom profile fields on viewtopic',
	'LOAD_USER_ACTIVITY'			=> 'Show users activity',
	'LOAD_USER_ACTIVITY_EXPLAIN'	=> 'Displays active topic/forum in user profiles and user control panel. It is recommended to disable this on boards with more than one million posts.',
	'RECOMPILE_TEMPLATES'			=> 'Recompile stale templates',
	'RECOMPILE_TEMPLATES_EXPLAIN'	=> 'Check for updated template files on filesystem and recompile.',
	'YES_ANON_READ_MARKING'			=> 'Enable topic marking for guests',
	'YES_ANON_READ_MARKING_EXPLAIN'	=> 'Stores read/unread status information for guests. If disabled posts are always read for guests.',
	'YES_BIRTHDAYS'					=> 'Enable birthday listing',
	'YES_JUMPBOX'					=> 'Enable display of Jumpbox',
	'YES_MODERATORS'				=> 'Enable display of Moderators',
	'YES_ONLINE'					=> 'Enable online user listings',
	'YES_ONLINE_EXPLAIN'			=> 'Display online user information on index, forum and topic pages.',
	'YES_ONLINE_GUESTS'				=> 'Enable online guest listings in viewonline',
	'YES_ONLINE_GUESTS_EXPLAIN'		=> 'Allow display of guest user informations in viewonline.',
	'YES_ONLINE_TRACK'				=> 'Enable display of user online img',
	'YES_ONLINE_TRACK_EXPLAIN'		=> 'Display online information for user in profiles and viewtopic.',
	'YES_POST_MARKING'				=> 'Enable dotted topics',
	'YES_POST_MARKING_EXPLAIN'		=> 'Indicates whether user has posted to a topic.',
	'YES_READ_MARKING'				=> 'Enable server-side topic marking',
	'YES_READ_MARKING_EXPLAIN'		=> 'Stores read/unread status information in the database rather than a cookie.',
);

?>