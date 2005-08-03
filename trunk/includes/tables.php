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
// Well this needs some works
if (!defined('VIPERAL'))
{
    Header('Location: /');
    die();
}

// User related
define('ANONYMOUS', 1);

define('USER_ACTIVATION_NONE', 0);
define('USER_ACTIVATION_SELF', 1);
define('USER_ACTIVATION_ADMIN', 2);
define('USER_ACTIVATION_DISABLE', 3);

define('AVATAR_UPLOAD', 1);
define('AVATAR_REMOTE', 2);
define('AVATAR_GALLERY', 3);

define('USER_NORMAL', 0);
define('USER_INACTIVE', 1);
define('USER_UNACTIVATED', 2);
define('USER_FOUNDER', 3);
define('USER_BOT_ACTIVE', 4);
define('USER_BOT_INACTIVE', 5);

define('ADMIN_NOT_LOGGED', 0);
define('ADMIN_NOT_ADMIN', 1);
define('ADMIN_IS_ADMIN', 2);

//Error reporting tyoe
define('ERROR_NONE', 0);
define('ERROR_ONPAGE', 1);
define('ERROR_DEBUGGER', 2);

// Block side defines
define('BLOCK_NONE', 0);
define('BLOCK_ALL', 1);
define('BLOCK_LEFT', 2);
define('BLOCK_RIGHT', 3);
define('BLOCK_TOP', 4);
define('BLOCK_BOTTOM', 5);
define('BLOCK_MESSAGE_TOP', 6);
define('BLOCK_MESSAGE_BOTTOM', 7);

define('BLOCKTYPE_FILE',0);
define('BLOCKTYPE_FEED',1);
define('BLOCKTYPE_HTML',2);
define('BLOCKTYPE_SYSTEM',3);
define('BLOCKTYPE_MESSAGE', 4);
define('BLOCKTYPE_MESSAGE_GLOBAL', 5);

define('MODULE_SYSTEM', 0);
define('MODULE_NORMAL', 1);

define('ACL_NO', 0);
define('ACL_YES', 1);
define('ACL_UNSET', -1);

// Group settings
define('GROUP_OPEN', 0);
define('GROUP_CLOSED', 1);
define('GROUP_HIDDEN', 2);
define('GROUP_SPECIAL', 3);
define('GROUP_FREE', 4);

// status settings
define('STATUS_PENDING', 0);
define('STATUS_NORMAL', 1);
define('STATUS_LEADER', 2);


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

// Lastread types
define('TRACK_NORMAL', 0);
define('TRACK_POSTED', 1);

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


define('AUTH_ADMIN_TABLE', $prefix.'admins');
define('BLOCKS_TABLE', $prefix.'blocks');
define('USER_GROUP_TABLE', $prefix.'groups_users');
define('CORE_CONFIG_TABLE', $prefix.'config');
define('CORE_MODULES_TABLE', $prefix.'modules');


// Table names
//define('ACL_GROUPS_TABLE', $prefix.'forums_auth_groups');
define('ACL_TABLE', $prefix.'forums_auth');

define('ACL_OPTIONS_TABLE', $prefix.'forums_auth_options');
define('ACL_DEPS_TABLE', $prefix.'forums_auth_deps');
define('ACL_PRESETS_TABLE', $prefix.'forums_auth_presets');
//define('ACL_USERS_TABLE', $prefix.'forums_auth_users');
define('ATTACHMENTS_TABLE', $prefix.'forums_attachments');
define('BANLIST_TABLE', $prefix.'forums_banlist');
define('BBCODES_TABLE', $prefix.'forums_bbcodes');
define('BOOKMARKS_TABLE', $prefix.'forums_bookmarks');
define('BOTS_TABLE', $prefix.'forums_bots');
define('CACHE_TABLE', $prefix.'forums_cache');
define('CONFIG_TABLE', $prefix.'forums_config');
define('CONFIRM_TABLE', $prefix.'forums_confirm');
define('PROFILE_FIELDS_TABLE', $prefix.'forums_profile_fields');
define('PROFILE_LANG_TABLE', $prefix.'forums_profile_lang');
define('PROFILE_DATA_TABLE', $prefix.'forums_profile_fields_data');
define('PROFILE_FIELDS_LANG_TABLE', $prefix.'forums_profile_fields_lang');
define('DISALLOW_TABLE', $prefix.'forums_disallow');
define('DRAFTS_TABLE', $prefix.'forums_drafts');
define('EXTENSIONS_TABLE', $prefix.'forums_extensions');
define('EXTENSION_GROUPS_TABLE', $prefix.'forums_extension_groups');
define('FORUMS_TABLE', $prefix.'forums_forums');
define('FORUMS_ACCESS_TABLE', $prefix.'forums_access');
define('GROUPS_TABLE', $prefix.'groups');
define('ICONS_TABLE', $prefix.'forums_icons');
define('LANG_TABLE', $prefix.'forums_lang');
define('LOG_TABLE', $prefix.'forums_log');
define('MODERATOR_TABLE', $prefix.'forums_moderator_cache');
define('MODULES_TABLE', $prefix . 'forums_modules');
define('POSTS_TABLE', $prefix.'forums_posts');
define('PRIVMSGS_TABLE', $prefix.'forums_privmsgs');
define('PRIVMSGS_TO_TABLE', $prefix.'forums_privmsgs_to');
define('PRIVMSGS_FOLDER_TABLE', $prefix.'forums_privmsgs_folder');
define('PRIVMSGS_RULES_TABLE', $prefix.'forums_privmsgs_rules');
define('RANKS_TABLE', $prefix.'forums_ranks');
define('RATINGS_TABLE', $prefix.'forums_ratings');
define('REPORTS_TABLE', $prefix.'forums_reports');
define('REASONS_TABLE', $prefix.'forums_reports_reasons');
define('SEARCH_TABLE', $prefix.'forums_search_results');
define('SEARCH_WORD_TABLE', $prefix.'forums_search_wordlist');
define('SEARCH_MATCH_TABLE', $prefix.'forums_search_wordmatch');
define('SESSIONS_TABLE', $prefix.'sessions');
define('SITELIST_TABLE', $prefix.'forums_sitelist');
define('SMILIES_TABLE', $prefix.'smilies');
define('STYLES_TABLE', $prefix.'forums_styles');
define('STYLES_TPL_TABLE', $prefix.'forums_styles_template');
define('STYLES_TPLDATA_TABLE', $prefix.'forums_styles_template_data');
define('STYLES_CSS_TABLE', $prefix.'forums_styles_theme');
define('STYLES_IMAGE_TABLE', $prefix.'forums_styles_imageset');
define('TOPICS_TABLE', $prefix.'forums_topics');
define('USERS_TABLE', $prefix.'users');
define('USERS_NOTES_TABLE', $prefix.'forums_users_notes');
define('WORDS_TABLE', $prefix.'forums_words');
define('POLL_OPTIONS_TABLE', $prefix.'forums_poll_results');
define('POLL_VOTES_TABLE', $prefix.'forums_poll_voters');
define('ZEBRA_TABLE', $prefix.'forums_zebra');

//define('TOPICS_WATCH_TABLE', $prefix.'forums_topics_watch');

define('FORUMS_WATCH_TABLE', $prefix.'forums_watch');
define('FORUMS_TRACK_TABLE', $prefix.'forums_tracking');

?>