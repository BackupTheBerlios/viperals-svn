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

if (!defined('VIPERAL'))
{
    die;
}

define('ANONYMOUS', 1);

/* Global Status types */
define('STATUS_DISABLED', 0);
define('STATUS_PENDING', 1);
define('STATUS_ACTIVE', 2);
define('STATUS_DELETING', 3);

define('STATUS_NORMAL', 9);
define('STATUS_LEADER', 10);

define('USER_ACTIVATION_NONE', 0);
define('USER_ACTIVATION_SELF', 1);
define('USER_ACTIVATION_ADMIN', 2);
define('USER_ACTIVATION_DISABLE', 3);

define('AVATAR_UPLOAD', 1);
define('AVATAR_REMOTE', 2);
define('AVATAR_GALLERY', 3);

define('USER_GUEST', 0);
define('USER_NORMAL', 1);
define('USER_BOT', 2);

define('USER_UNACTIVATED', 0);
define('USER_ACTIVE', 1);
define('USER_DISABLE', 2);

define('ADMIN_NOT_LOGGED', 0);
define('ADMIN_NOT_ADMIN', 1);
define('ADMIN_IS_ADMIN', 2);

/* BLOCKS */
define('BLOCK_LEFT', 1);
define('BLOCK_RIGHT', 2);
define('BLOCK_TOP', 3);
define('BLOCK_BOTTOM', 4);
define('BLOCK_MESSAGE_TOP', 5);
define('BLOCK_MESSAGE_BOTTOM', 6);

define('BLOCKTYPE_FILE',0);
define('BLOCKTYPE_FEED',1);
define('BLOCKTYPE_HTML',2);
define('BLOCKTYPE_SYSTEM',3);
define('BLOCKTYPE_MESSAGE', 4);
define('BLOCKTYPE_MESSAGE_GLOBAL', 5);

define('PAGE_MODULE', 0);
define('PAGE_TEMPLATE', 1);

define('ACL_NEVER', 0);
define('ACL_YES', 1);
define('ACL_NO', -1);

define('GROUP_HIDDEN', 0);
define('GROUP_SYSTEM', 1);
define('GROUP_SPECIAL', 2);
define('GROUP_REQUEST', 3);
define('GROUP_CLOSED', 4);
define('GROUP_UNRESTRAINED', 5);


// Forum/Topic states
define('FORUM_CAT', 0);
define('FORUM_POST', 1);
define('FORUM_LINK', 2);
define('ITEM_UNLOCKED', 0);
define('ITEM_LOCKED', 1);
define('ITEM_MOVED', 2);
define('ITEM_DELETING', 3);

// Topic types
define('POST_NORMAL', 0);
define('POST_STICKY', 1);
define('POST_ANNOUNCE', 2);
define('POST_GLOBAL', 3);

// Notify methods
define('NOTIFY_EMAIL', 0);
define('NOTIFY_IM', 1);
define('NOTIFY_BOTH', 2);

// Email Priority Settings
define('MAIL_LOW_PRIORITY', 4);
define('MAIL_NORMAL_PRIORITY', 3);
define('MAIL_HIGH_PRIORITY', 2);

// Log types
define('LOG_ADMIN', 0);
define('LOG_MOD', 1);
define('LOG_CRITICAL', 2);
define('LOG_USERS', 3);

// Private messaging - Do NOT change these values
define('PRIVMSGS_HOLD_BOX', -4);
define('PRIVMSGS_NO_BOX', -3);
define('PRIVMSGS_OUTBOX', -2);
define('PRIVMSGS_SENTBOX', -1);
define('PRIVMSGS_INBOX', 0);

// Full Folder Actions
define('FULL_FOLDER_NONE', -3);
define('FULL_FOLDER_DELETE', -2);
define('FULL_FOLDER_HOLD', -1);

// Download Modes - Attachments
define('INLINE_LINK', 1);
define('PHYSICAL_LINK', 2);

// Categories - Attachments
define('ATTACHMENT_CATEGORY_NONE', 0);
define('ATTACHMENT_CATEGORY_IMAGE', 1); // Inline Images
define('ATTACHMENT_CATEGORY_WM', 2); // Windows Media Files - Streaming
define('ATTACHMENT_CATEGORY_RM', 3); // Real Media Files - Streaming
define('ATTACHMENT_CATEGORY_THUMB', 4); // Not used within the database, only while displaying posts
//define('SWF_CAT', 5); // Replaced by [flash]? or an additional possibility?

// BBCode UID length
define('BBCODE_UID_LEN', 5);

// Number of core BBCodes
define('NUM_CORE_BBCODES', 12);

// Profile Field Types
define('FIELD_INT', 1);
define('FIELD_STRING', 2);
define('FIELD_TEXT', 3);
define('FIELD_BOOL', 4);
define('FIELD_DROPDOWN', 5);
define('FIELD_DATE', 6);


define('FORUMS_ACL_TABLE', $table_prefix.'forums_auth');
define('FORUMS_ACL_OPTIONS_TABLE', $table_prefix.'forums_auth_options');
define('FORUMS_ACL_PRESETS_TABLE', $table_prefix.'forums_auth_presets');
define('FORUMS_ACL_ROLES_TABLE', $table_prefix.'forums_acl_roles');
define('FORUMS_ACL_ROLES_DATA_TABLE', $table_prefix.'forums_acl_roles_data');

define('FORUMS_ATTACHMENTS_TABLE', $table_prefix.'forums_attachments');
define('FORUMS_BOOKMARKS_TABLE', $table_prefix.'forums_bookmarks');
define('FORUMS_BBCODES_TABLE', $table_prefix.'forums_bbcodes');
define('FORUMS_CONFIG_TABLE', $table_prefix.'forums_config');
define('FORUMS_DRAFTS_TABLE', $table_prefix.'forums_drafts');
define('FORUMS_FORUMS_TABLE', $table_prefix.'forums_forums');
define('FORUMS_ACCESS_TABLE', $table_prefix.'forums_access');
define('FORUMS_ICONS_TABLE', $table_prefix.'forums_icons');
define('FORUMS_LOG_TABLE', $table_prefix.'forums_log');
define('FORUMS_MODERATOR_TABLE', $table_prefix.'forums_moderator_cache');
define('FORUMS_MODULES_TABLE', $table_prefix . 'forums_modules');
define('FORUMS_POSTS_TABLE', $table_prefix.'forums_posts');
define('FORUMS_PRIVMSGS_TABLE', $table_prefix.'forums_privmsgs');
define('FORUMS_PRIVMSGS_TO_TABLE', $table_prefix.'forums_privmsgs_to');
define('FORUMS_PRIVMSGS_FOLDER_TABLE', $table_prefix.'forums_privmsgs_folder');
define('FORUMS_PRIVMSGS_RULES_TABLE', $table_prefix.'forums_privmsgs_rules');
define('FORUMS_REPORTS_TABLE', $table_prefix.'forums_reports');
define('FORUMS_REASONS_TABLE', $table_prefix.'forums_reports_reasons');
define('FORUMS_SITELIST_TABLE', $table_prefix.'forums_sitelist');
define('FORUMS_SEARCH_TABLE', $table_prefix.'forums_search_results');
define('FORUMS_SEARCH_WORD_TABLE', $table_prefix.'forums_search_wordlist');
define('FORUMS_SEARCH_MATCH_TABLE', $table_prefix.'forums_search_wordmatch');
define('FORUMS_TOPICS_TABLE', $table_prefix.'forums_topics');
define('FORUMS_POLL_OPTIONS_TABLE', $table_prefix.'forums_poll_results');
define('FORUMS_POLL_VOTES_TABLE', $table_prefix.'forums_poll_voters');
define('FORUMS_WATCH_TABLE', $table_prefix.'forums_watch');
define('FORUMS_TRACK_TABLE', $table_prefix.'forums_tracking');
define('FORUMS_RANKS_TABLE', $table_prefix.'forums_ranks');
define('FORUMS_EXTENSIONS_TABLE', $table_prefix.'forums_extensions');
define('FORUMS_EXTENSION_GROUPS_TABLE', $table_prefix.'forums_extension_groups');

define('CORE_ADMIN_AUTH_TABLE', $table_prefix.'admin_access');
define('CORE_ADMIN_MODULES_TABLE', $table_prefix.'admin_modules');

define('CORE_BLOCKS_TABLE', $table_prefix.'blocks');

define('CORE_CENSOR_TABLE', $table_prefix.'censor');
define('CORE_CONFIG_TABLE', $table_prefix.'config');
define('CORE_CONTROL_PANEL_MODULES_TABLE', $table_prefix . 'control_panel_modules');

define('CORE_PAGES_TABLE', $table_prefix.'pages');
define('CORE_SMILIES_TABLE', $table_prefix.'smilies');

define('CORE_USER_GROUP_TABLE', $table_prefix.'groups_members');

define('CORE_GROUPS_TABLE', $table_prefix.'groups');
define('CORE_GROUPS_MEMBERS_TABLE', $table_prefix.'groups_members');

define('CORE_SESSIONS_AUTOLOGIN_TABLE', $table_prefix.'sessions_auto_login');
define('CORE_SESSIONS_TABLE', $table_prefix.'sessions');



define('CORE_USERS_TABLE', $user_prefix.'users');
define('USERS_NOTES_TABLE', $table_prefix.'forums_users_notes');
define('ZEBRA_TABLE', $table_prefix.'forums_zebra');
//define('LOG_TABLE', $table_prefix.'log');


// can be removed
define('DISALLOW_TABLE', $table_prefix.'forums_disallow');

?>