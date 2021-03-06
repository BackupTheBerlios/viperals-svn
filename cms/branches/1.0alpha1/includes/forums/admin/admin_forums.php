<?php
// -------------------------------------------------------------
//
// $Id: admin_forums.php,v 1.30 2004/07/08 22:40:41 acydburn Exp $
//
// FILENAME  : admin_forums.php
// STARTED   : Thu Jul 12, 2001
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ]
//
// -------------------------------------------------------------
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Get general vars
$update		= (isset($_POST['update'])) ? true : false;
$mode		= request_var('mode', '');
$action		= request_var('action', '');
$forum_id	= request_var('f', 0);
$parent_id	= request_var('parent_id', 0);

$forum_data = $errors = array();

// Do we have permissions?
switch ($mode)
{
	case 'add':
		$acl = 'a_forumadd';
	break;

	case 'delete':
		$acl = 'a_forumdel';
	break;

	default:
		$acl = 'a_forum';
	break;
}

if (!$_CLASS['auth']->acl_get($acl))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}


// Major routines
if ($update)
{
	switch ($mode)
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

			delete_forum($forum_id, $action_posts, $action_subforums, $posts_to_id, $subforums_to_id);

			$_CLASS['auth']->acl_clear_prefetch();

			$show_prev_info = false;
			trigger_error('FORUM_DELETED');
		break;

		case 'edit':
			if (!$forum_id)
			{
				trigger_error('NO_FORUM');
			}

			$forum_data = array(
				'forum_id'		=>	$forum_id
			);

			// No break here

		case 'add':
			$forum_data += array(
				'parent_id'				=> $parent_id,
				'forum_type'			=> request_var('forum_type', FORUM_POST),
				'forum_status'			=> request_var('forum_status', ITEM_UNLOCKED),
				'forum_name'			=> request_var('forum_name', ''),
				'forum_link'			=> request_var('forum_link', ''),
				'forum_link_track'		=> request_var('forum_link_track', FALSE),
				'forum_desc'			=> str_replace("\n", '<br />', request_var('forum_desc', '')),
				'forum_rules'			=> request_var('forum_rules', ''),
				'forum_rules_link'		=> request_var('forum_rules_link', ''),
				'forum_image'			=> request_var('forum_image', ''),
				'display_on_index'		=> request_var('display_on_index', FALSE),
				'forum_topics_per_page'	=> request_var('topics_per_page', 0), 
				'enable_indexing'		=> request_var('enable_indexing',true), 
				'enable_icons'			=> request_var('enable_icons', FALSE),
				'enable_prune'			=> request_var('enable_prune', FALSE),
				'prune_days'			=> request_var('prune_days', 7),
				'prune_viewed'			=> request_var('prune_viewed', 7),
				'prune_freq'			=> request_var('prune_freq', 1),
				'prune_old_polls'		=> request_var('prune_old_polls', FALSE),
				'prune_announce'		=> request_var('prune_announce', FALSE),
				'prune_sticky'			=> request_var('prune_sticky', FALSE),
				'forum_password'		=> request_var('forum_password', ''),
				'forum_password_confirm'=> request_var('forum_password_confirm', ''),
				'forum_posts'			=> 0,
				'forum_topics'			=> 0,
				'forum_topics_real'		=> 0,
			);

			if ($forum_data['forum_rules'])
			{
				require_once($site_file_root.'includes/forums/message_parser.php');

				$allow_bbcode = request_var('parse_bbcode', false);
				$allow_smilies = request_var('parse_smilies', false);
				$allow_urls = request_var('parse_urls', false);

				$forum_data['forum_rules_flags'] = (($allow_bbcode) ? 1 : 0) + (($allow_smilies) ? 2 : 0) + (($allow_urls) ? 4 : 0);

				$message_parser = new parse_message($forum_data['forum_rules']);
				$message_parser->parse(false, $allow_bbcode, $allow_urls, $allow_smilies);
			
				$forum_data['forum_rules'] = $message_parser->message;
				$forum_data['forum_rules_bbcode_uid'] = $message_parser->bbcode_uid;
				$forum_data['forum_rules_bbcode_bitfield'] = $message_parser->bbcode_bitfield;
				unset($message_parser);
			}
					
			$errors = update_forum_data($forum_data);

			if ($errors)
			{
				break;
			}

			// 
			$_CLASS['auth']->acl_clear_prefetch();

			// Redirect to permissions
			$message = ($mode == 'add') ? $_CLASS['core_user']->lang['FORUM_CREATED'] : $_CLASS['core_user']->lang['FORUM_UPDATED'];
			$message .= '<br /><br />' . sprintf($_CLASS['core_user']->lang['REDIRECT_ACL'], '<a href="'.generate_link('Forums&amp;file=admin_permissions&amp;mode=forum&amp;submit_usergroups=true&amp;ug_type=forum&amp;action=usergroups&amp;f[forum][]=' . $forum_data['forum_id'], array('admin' => true)) . '">', '</a>');
			$show_prev_info = ($mode == 'edit') ? true : false;

			trigger_error($message);
			break;
	}
}

switch ($mode)
{
	case 'add':
	case 'edit':
		if (isset($_POST['update']))
		{
			extract($forum_data);
		}
		else
		{
			$forum_id				= request_var('f', 0);
			$parent_id				= request_var('parent_id', 0);
			$style_id				= request_var('style_id', 0);
			$forum_type				= request_var('forum_type', FORUM_POST);
			$forum_status			= request_var('forum_status', ITEM_UNLOCKED);
			$forum_desc				= request_var('forum_desc', '');
			$forum_name				= request_var('forum_name', '');
			$forum_rules_link		= request_var('forum_rules_link', '');
			$forum_rules			= request_var('forum_rules', '');
			$forum_password			= request_var('forum_password', '');
			$forum_password_confirm	= request_var('forum_password_confirm', '');

			$forum_rules_flags		= 0;
			$forum_rules_flags		+= (request_var('parse_bbcode', false)) ? 1 : 0;
			$forum_rules_flags		+= (request_var('parse_smilies', false)) ? 2 : 0;
			$forum_rules_flags		+= (request_var('parse_urls', false)) ? 4 : 0;
		}
		
		// Show form to create/modify a forum
		if ($mode == 'edit')
		{
			$l_title = $_CLASS['core_user']->lang['EDIT_FORUM'];
			$forum_data = get_forum_info($forum_id);

			if (!isset($_POST['forum_type']))
			{
				extract($forum_data);
			}
			else
			{
				$old_forum_type = $forum_data['forum_type'];
			}
			unset($forum_data);

			$parents_list = make_forum_select($parent_id, $forum_id, false, false, false);
			$forums_list = make_forum_select($parent_id, $forum_id, false, true, false);

			$forum_password_confirm = $forum_password;

			$bbcode_checked		= ($forum_rules_flags & 1) ? ' checked="checked"' : '';
			$smilies_checked	= ($forum_rules_flags & 2) ? ' checked="checked"' : '';
			$urls_checked		= ($forum_rules_flags & 4) ? ' checked="checked"' : '';
		}
		else
		{
			$l_title = $_CLASS['core_user']->lang['CREATE_FORUM'];

			$forum_id = $parent_id;
			$parents_list = make_forum_select($parent_id, false, false, false, false);

			if ($parent_id)
			{
				$temp_forum_desc = $forum_desc;
				$temp_forum_name = $forum_name;
				$temp_forum_rules = $forum_rules;
				$temp_forum_rules_link = $forum_rules_link;
				$temp_forum_type = $forum_type;

				extract(get_forum_info($parent_id));
				$forum_type = $temp_forum_type;
				$forum_name = $temp_forum_name;
				$forum_desc = $temp_forum_desc;
				$forum_rules = $temp_forum_rules;
				$forum_rules_link = $temp_forum_rules_link;
				$forum_password_confirm = $forum_password;
			}
		}

		if ($forum_rules)
		{
			require_once($site_file_root.'includes/forums/functions_posting.php');
			require_once($site_file_root.'includes/forums/message_parser.php');
			
			$message_parser = new parse_message($forum_rules);
			if (isset($forum_rules_bbcode_uid))
			{
				$message_parser->bbcode_uid = $forum_rules_bbcode_uid;
				$message_parser->bbcode_bitfield = $forum_rules_bbcode_bitfield;
			}
			else
			{
				$message_parser->parse(false, ($forum_rules_flags & 1), ($forum_rules_flags & 4), ($forum_rules_flags & 2));
			}
		}
		
		$forum_type_options = '';
		$forum_type_ary = array(FORUM_CAT => 'CAT', FORUM_POST => 'FORUM', FORUM_LINK => 'LINK');
		foreach ($forum_type_ary as $value => $lang)
		{
			$forum_type_options .= '<option value="' . $value . '"' . (($value == $forum_type) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['TYPE_' . $lang] . '</option>';
		}

		$statuslist = '<option value="' . ITEM_UNLOCKED . '"' . (($forum_status == ITEM_UNLOCKED) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['UNLOCKED'] . '</option><option value="' . ITEM_LOCKED . '"' . (($forum_status == ITEM_LOCKED) ? ' selected="selected"' : '') . '>' . $_CLASS['core_user']->lang['LOCKED'] . '</option>';
$enable_icons = isset($enable_icons) ? $enable_icons : 0;
$enable_indexing = isset($enable_indexing) ? $enable_indexing : 0;
$enable_prune = isset($enable_prune) ? $enable_prune : 0;
$display_on_index = isset($display_on_index) ? $display_on_index : 0;
$forum_flags = isset($forum_flags) ? $forum_flags : 0;

$bbcode_checked = isset($bbcode_checked) ? $bbcode_checked : false;
$smilies_checked = isset($smilies_checked) ? $smilies_checked : false;
$urls_checked = isset($urls_checked) ? $urls_checked : false;

$old_forum_type = isset($old_forum_type) ? $old_forum_type : '';
$forum_image = isset($forum_image) ? $forum_image : '';
$prune_freq = isset($prune_freq) ? $prune_freq : '';
$prune_days = isset($prune_days) ? $prune_days : '';
$prune_viewed = isset($prune_viewed) ? $prune_viewed : '';
$forum_link = isset($forum_link) ? $forum_link : '';

$forum_topics_per_page = isset($topics_per_page) ? $topics_per_page : '';


		$indexing_yes = ($enable_indexing) ? ' checked="checked"' : '';
		$indexing_no = (!$enable_indexing) ? ' checked="checked"' : '';
		$topic_icons_yes = ($enable_icons) ? ' checked="checked"' : '';
		$topic_icons_no = (!$enable_icons) ? ' checked="checked"' : '';
		$display_index_yes = ($display_on_index) ? ' checked="checked"' : '';
		$display_index_no = (!$display_on_index) ? ' checked="checked"' : '';

		$prune_enable_yes = ($enable_prune) ? ' checked="checked"' : '';
		$prune_enable_no = (!$enable_prune) ? ' checked="checked"' : '';
		$prune_old_polls_yes = ($forum_flags & 2) ? ' checked="checked"' : '';
		$prune_old_polls_no = (!($forum_flags & 2)) ? ' checked="checked"' : '';
		$prune_announce_yes = ($forum_flags & 4) ? ' checked="checked"' : '';
		$prune_announce_no = (!($forum_flags & 4)) ? ' checked="checked"' : '';
		$prune_sticky_yes = ($forum_flags & 8) ? ' checked="checked"' : '';
		$prune_sticky_no = (!($forum_flags & 8)) ? ' checked="checked"' : '';

		$forum_link_track_yes = ($forum_flags & 1) ? ' checked="checked"' : '';
		$forum_link_track_no = (!($forum_flags & 1)) ? ' checked="checked"' : '';

		$navigation = '<a href="'.generate_link('Forums&amp;file=admin_forums', array('admin' => true)) . '">' . $_CLASS['core_user']->lang['FORUM_INDEX'] . '</a>';

		$forums_nav = get_forum_branch($forum_id, 'parents', 'descending');
		foreach ($forums_nav as $row)
		{
			$navigation .= ($row['forum_id'] == $forum_id) ? ' -&gt; ' . $row['forum_name'] : ' -&gt; <a href="'.generate_link('Forums&amp;file=admin_forums&amp;parent_id=' . $row['forum_id'], array('admin' => true)) . '">' . $row['forum_name'] . '</a>';
		}

		adm_page_header($l_title);

?>

<p><?php echo $_CLASS['core_user']->lang['FORUM_ADMIN_EXPLAIN'] ?></p>

<h1><?php echo $l_title ?></h1>

<p><?php echo $_CLASS['core_user']->lang['FORUM_EDIT_EXPLAIN'] ?></p>

<form method="post" name="edit" action="<?php echo generate_link('Forums&amp;file=admin_forums&amp;mode='.$mode . (($forum_id) ? "&amp;f=$forum_id" : ''), array('admin' => true)); ?>"><table width="100%" cellspacing="2" cellpadding="2" border="0" align="center">
	<tr>
		<td class="nav"><?php echo $navigation ?></td>
	</tr>
</table>

<table class="tablebg" width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['FORUM_SETTINGS'] ?></th>
	</tr>
<?php

		if (!empty($errors))
		{

?>
	<tr>
		<td class="row3" colspan="2" align="center"><span style="color:red"><?php echo implode('<br />', $errors); ?></span></td>
	</tr>
<?php

		}

?>
	<tr>
		<td class="row1" width="33%"><b><?php echo $_CLASS['core_user']->lang['FORUM_TYPE'] ?>: </b></td>
		<td class="row2"><select name="forum_type" onchange="this.form.submit();"><?php echo $forum_type_options; ?></select><?php
	
		if ($old_forum_type == FORUM_POST && $forum_type != FORUM_POST)
		{
			// Forum type being changed to a non-postable type, let the user decide between
			// deleting all posts or moving them to another forum (if applicable)

?><br /><input type="radio" name="action" value="delete" checked="checked" /> <?php echo $_CLASS['core_user']->lang['DELETE_ALL_POSTS'];

			$sql = 'SELECT forum_id
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_type = ' . FORUM_POST . "
					AND forum_id <> $forum_id";
			$result = $_CLASS['core_db']->query($sql);

			if ($_CLASS['core_db']->fetch_row_assoc($result))
			{
?>&nbsp;<input type="radio" name="action" value="move" /> <?php echo $_CLASS['core_user']->lang['MOVE_POSTS_TO'] ?> <select name="to_forum_id"><?php echo $forums_list ?></select><?php

			}
		}

?></td>
	</tr>
<?php

		if ($forum_type == FORUM_POST)
		{

?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_STATUS'] ?>: </b></td>
		<td class="row2"><select name="forum_status"><?php echo $statuslist ?></select></td>
	</tr>
<?php

		}

?>
	<tr>
		<td class="row1" width="40%"><b><?php echo $_CLASS['core_user']->lang['FORUM_PARENT'] ?>: </b></td>
		<td class="row2"><select name="parent_id"><option value="0"><?php echo $_CLASS['core_user']->lang['NO_PARENT'] ?></option><?php echo $parents_list ?></select></td>
	</tr>
<?php

		if ($forum_type == FORUM_LINK)
		{

?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_LINK'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_LINK_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" size="40" name="forum_link" value="<?php echo $forum_link; ?>" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_LINK_TRACK'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_LINK_TRACK_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="forum_link_track" value="1"<?php echo $forum_link_track_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="forum_link_track" value="0"<?php echo $forum_link_track_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
<?php

		}

?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_NAME']; ?>: </b></td>
		<td class="row2"><input class="post" type="text" size="40" name="forum_name" value="<?php echo $forum_name ?>" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_DESC'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_DESC_EXPLAIN']; ?></span> </td>
		<td class="row2"><textarea class="post" rows="5" cols="45" wrap="virtual" name="forum_desc"><?php echo htmlspecialchars(str_replace('<br />', "\n", $forum_desc)); ?></textarea></td>
	</tr>
<?php
	if ($forum_type != FORUM_LINK)
	{
?>	
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_RULES_LINK']; ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_RULES_LINK_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" size="40" name="forum_rules_link" value="<?php echo $forum_rules_link ?>" /></td>
	</tr>
<?php
	if ($forum_rules)
	{
?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_RULES_PREVIEW'] ?>: </b></td>
		<td class="row2"><?php echo $message_parser->format_display(false, ($forum_rules_flags & 1), ($forum_rules_flags & 4), ($forum_rules_flags & 2), false); ?></td>
	</tr>
<?php
	}
?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_RULES'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_RULES_EXPLAIN']; ?></span></td>
		<td class="row2"><table cellspacing="2" cellpadding="0" border="0"><tr><td colspan="6"><textarea class="post" rows="4" cols="70" name="forum_rules">
<?php 
		if ($forum_rules)
		{
			$message_parser->decode_message();
			echo $message_parser->message;
		}
?></textarea></td></tr><tr>
			<td width="10"><input type="checkbox" name="parse_bbcode"<?php echo $bbcode_checked; ?> /></td><td><?php echo $_CLASS['core_user']->lang['PARSE_BBCODE']; ?></td><td width="10"><input type="checkbox" name="parse_smilies"<?php echo $smilies_checked; ?> /></td><td><?php echo $_CLASS['core_user']->lang['PARSE_SMILIES']; ?></td><td width="10"><input type="checkbox" name="parse_urls"<?php echo $urls_checked; ?> /></td><td><?php echo $_CLASS['core_user']->lang['PARSE_URLS']; ?></td></tr></table>
		</td>
	</tr>
<?php
	}
?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_IMAGE']; ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_IMAGE_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" size="40" name="forum_image" value="<?php echo $forum_image ?>" /><br /><?php
	
		if ($forum_image)
		{
			echo '<img src="' . $forum_image . '" alt="" />';
		}
		
?></td>
	</tr>
<?php

		if ($forum_type == FORUM_POST)
		{

?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['ENABLE_INDEXING'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['ENABLE_INDEXING_EXPLAIN'] ?></span></td>
		<td class="row2"><input type="radio" name="enable_indexing" value="1"<?php echo $indexing_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="enable_indexing" value="0"<?php echo $indexing_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['ENABLE_TOPIC_ICONS'] ?>: </b></td>
		<td class="row2"><input type="radio" name="enable_icons" value="1"<?php echo $topic_icons_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="enable_icons" value="0"<?php echo $topic_icons_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
<?php

			if ($mode == 'edit' && $parent_id > 0)
			{
				// if this forum is a subforum put the "display on index" checkbox
				if ($parent_info = get_forum_info($parent_id))
				{
					if ($parent_info['parent_id'] > 0 || $parent_info['forum_type'] == FORUM_CAT)
					{

?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['LIST_INDEX'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['LIST_INDEX_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="display_on_index" value="1"<?php echo $display_index_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="display_on_index" value="0"<?php echo $display_index_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
<?php

					}
				}
			}

?>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_AUTO_PRUNE'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_AUTO_PRUNE_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="enable_prune" value="1"<?php echo $prune_enable_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="enable_prune" value="0"<?php echo $prune_enable_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['AUTO_PRUNE_FREQ'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['AUTO_PRUNE_FREQ_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="prune_freq" value="<?php echo $prune_freq ?>" size="5" /> <?php echo $_CLASS['core_user']->lang['DAYS']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['AUTO_PRUNE_DAYS'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['AUTO_PRUNE_DAYS_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="prune_days" value="<?php echo $prune_days ?>" size="5" /> <?php echo $_CLASS['core_user']->lang['DAYS']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['AUTO_PRUNE_VIEWED'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['AUTO_PRUNE_VIEWED_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="prune_viewed" value="<?php echo $prune_viewed ?>" size="5" /> <?php echo $_CLASS['core_user']->lang['DAYS']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['PRUNE_OLD_POLLS'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['PRUNE_OLD_POLLS_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="prune_old_polls" value="1"<?php echo $prune_old_polls_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="prune_old_polls" value="0"<?php echo $prune_old_polls_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['PRUNE_ANNOUNCEMENTS'] ?>: </b></td>
		<td class="row2"><input type="radio" name="prune_announce" value="1"<?php echo $prune_announce_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="prune_announce" value="0"<?php echo $prune_announce_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['PRUNE_STICKY'] ?>: </b></td>
		<td class="row2"><input type="radio" name="prune_sticky" value="1"<?php echo $prune_sticky_yes; ?> /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="prune_sticky" value="0"<?php echo $prune_sticky_no; ?> /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_TOPICS_PAGE'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_TOPICS_PAGE_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="topics_per_page" value="<?php echo $forum_topics_per_page; ?>" size="3" maxlength="3" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_PASSWORD'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_PASSWORD_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="password" name="forum_password" value="<?php echo $forum_password; ?>" size="25" maxlength="25" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['FORUM_PASSWORD_CONFIRM'] ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['FORUM_PASSWORD_CONFIRM_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="password" name="forum_password_confirm" value="<?php echo $forum_password_confirm; ?>" size="25" maxlength="25" /></td>
	</tr>
<?php

		}

?>
	<tr>
		<td class="cat" colspan="2" align="center"><input class="btnmain" name="update" type="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" /> &nbsp;<input class="btnlite" type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" /></td>
	</tr>
</table></form>

<br clear="all" />

<?php

		adm_page_footer();
		break;

	case 'delete':

		$forum_id = request_var('f', 0);

		adm_page_header($_CLASS['core_user']->lang['MANAGE']);
		extract(get_forum_info($forum_id));

		$subforums_id = array();
		$subforums = get_forum_branch($forum_id, 'children');
		foreach ($subforums as $row)
		{
			$subforums_id[] = $row['forum_id'];
		}

		$forums_list = make_forum_select($parent_id, $subforums_id);
		$move_posts_list = make_forum_select($parent_id, $subforums_id);

?>

<p><?php echo $_CLASS['core_user']->lang['FORUM_ADMIN_EXPLAIN']; ?></p>

<h1><?php echo $_CLASS['core_user']->lang['FORUM_DELETE'] ?></h1>

<p><?php echo $_CLASS['core_user']->lang['FORUM_DELETE_EXPLAIN'] ?></p>

<form action="<?php echo generate_link('Forums&amp;file=admin_forums', array('admin' => true)) ?>&mode=delete&amp;f=<?php echo $forum_id ?>" method="post"><table class="tablebg" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['FORUM_DELETE'] ?></th>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['FORUM_NAME']; ?>: </td>
		<td class="row1"><b><?php echo $forum_name ?></b></td>
	</tr>
<?php

	if ($forum_type == FORUM_POST)
	{

?>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['ACTION'] ?>: </td>
		<td class="row1"><table cellspacing="0" cellpadding="2" border="0">
			<tr>
				<td><input type="radio" name="action_posts" value="delete" checked="checked" /> <?php echo $_CLASS['core_user']->lang['DELETE_ALL_POSTS'] ?></td>
			</tr>
<?php

			$sql = 'SELECT forum_id
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_type = ' . FORUM_POST . "
					AND forum_id <> $forum_id";
			$result = $_CLASS['core_db']->query($sql);

			if ($_CLASS['core_db']->fetch_row_assoc($result))
			{

?>
			<tr>
				<td><input type="radio" name="action_posts" value="move" /> <?php echo $_CLASS['core_user']->lang['MOVE_POSTS_TO'] ?> <select name="posts_to_id" ?><?php echo $move_posts_list ?></select></td>
			</tr>
<?php

			}

?>
		</table></td>
	</tr>
<?php

	}

	if ($right_id - $left_id > 1)
	{

?>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['ACTION'] ?>:</td>
		<td class="row1"><table cellspacing="0" cellpadding="2" border="0">
			<tr>
				<td><input type="radio" name="action_subforums" value="delete" checked="checked" /> <?php echo $_CLASS['core_user']->lang['DELETE_SUBFORUMS'] ?></td>
			</tr>
			<tr>
				<td><input type="radio" name="action_subforums" value="move" /> <?php echo $_CLASS['core_user']->lang['MOVE_SUBFORUMS_TO'] ?> <select name="subforums_to_id" ?><?php echo $forums_list ?></select></td>
			</tr>
		</table></td>
	</tr>
<?php

	}

?>
	<tr>
		<td class="cat" colspan="2" align="center"><input type="submit" name="update" value="<?php echo $_CLASS['core_user']->lang['SUBMIT'] ?>" class="btnmain" /></td>
	</tr>
</table></form>
<?php

		adm_page_footer();
		break;

	case 'move_up':
	case 'move_down':
		$sql = 'SELECT parent_id, left_id, right_id
			FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
		$result = $_CLASS['core_db']->query($sql);

		if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM']);
		}
		$_CLASS['core_db']->free_result($result);

		extract($row);
		$forum_info = array($forum_id => $row);

		// Get the adjacent forum
		$sql = 'SELECT forum_id, forum_name, left_id, right_id
			FROM ' . FORUMS_FORUMS_TABLE . "
			WHERE parent_id = $parent_id
				AND " . (($mode == 'move_up') ? "right_id < $right_id ORDER BY right_id DESC" : "left_id > $left_id ORDER BY left_id ASC");
		$result = $_CLASS['core_db']->query_limit($sql, 1);

		if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
		{
			// already on top or at bottom
			break;
		}
		$_CLASS['core_db']->free_result($result);

		if ($mode == 'move_up')
		{
			$log_action = 'LOG_FORUM_MOVE_UP';
			$up_id = $forum_id;
			$down_id = $row['forum_id'];
		}
		else
		{
			$log_action = 'LOG_FORUM_MOVE_DOWN';
			$up_id = $row['forum_id'];
			$down_id = $forum_id;
		}

		$move_forum_name = $row['forum_name'];
		$forum_info[$row['forum_id']] = $row;
		$diff_up = $forum_info[$up_id]['right_id'] - $forum_info[$up_id]['left_id'];
		$diff_down = $forum_info[$down_id]['right_id'] - $forum_info[$down_id]['left_id'];

		$forum_ids = array();
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE left_id > ' . $forum_info[$up_id]['left_id'] . '
				AND right_id < ' . $forum_info[$up_id]['right_id'];
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$forum_ids[] = $row['forum_id'];
		}
		$_CLASS['core_db']->free_result($result);

		// Start transaction
		$_CLASS['core_db']->transaction();

		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET left_id = left_id + ' . ($diff_up + 1) . ', right_id = right_id + ' . ($diff_up + 1) . '
			WHERE left_id > ' . $forum_info[$down_id]['left_id'] . '
				AND right_id < ' . $forum_info[$down_id]['right_id'];
		$_CLASS['core_db']->query($sql);

		if (count($forum_ids))
		{
			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET left_id = left_id - ' . ($diff_down + 1) . ', right_id = right_id - ' . ($diff_down + 1) . '
				WHERE forum_id IN (' . implode(', ', $forum_ids) . ')';
			$_CLASS['core_db']->query($sql);
		}

		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET left_id = ' . $forum_info[$down_id]['left_id'] . ', right_id = ' . ($forum_info[$down_id]['left_id'] + $diff_up) . '
			WHERE forum_id = ' . $up_id;
		$_CLASS['core_db']->query($sql);

		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET left_id = ' . ($forum_info[$up_id]['right_id'] - $diff_down) . ', right_id = ' . $forum_info[$up_id]['right_id'] . '
			WHERE forum_id = ' . $down_id;
		$_CLASS['core_db']->query($sql);

		$_CLASS['core_db']->transaction('commit');

		$forum_data = get_forum_info($forum_id);

		add_log('admin', $log_action, $forum_data['forum_name'], $move_forum_name);
		unset($forum_data);
		break;

	case 'sync':
		if (!$forum_id)
		{
			trigger_error('NO_FORUM');
		}

		$sql = "SELECT forum_name
			FROM " . FORUMS_FORUMS_TABLE . "
			WHERE forum_id = $forum_id";
		$result = $_CLASS['core_db']->query($sql);

		if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
		{
			trigger_error($_CLASS['core_user']->lang['NO_FORUM']);
		}
		$_CLASS['core_db']->free_result($result);

		sync('forum', 'forum_id', $forum_id);
		add_log('admin', 'LOG_FORUM_SYNC', $row['forum_name']);

		break;
}

// Default management page

if (!$parent_id)
{
	$navigation = $_CLASS['core_user']->lang['FORUM_INDEX'];
}
else
{
	$navigation = '<a href="'.generate_link('Forums&amp;file=admin_forums', array('admin' => true)) . '">' . $_CLASS['core_user']->lang['FORUM_INDEX'] . '</a>';

	$forums_nav = get_forum_branch($parent_id, 'parents', 'descending');
	foreach ($forums_nav as $row)
	{
		if ($row['forum_id'] == $parent_id)
		{
			$navigation .= ' -&gt; ' . $row['forum_name'];
		}
		else
		{
			$navigation .= ' -&gt; <a href="'.generate_link('Forums&amp;file=admin_forums&amp;parent_id=' . $row['forum_id'], array('admin' => true)) . '">' . $row['forum_name'] . '</a>';
		}
	}
}

// Jumpbox
$forum_box = make_forum_select($parent_id);

// Front end
adm_page_header($_CLASS['core_user']->lang['MANAGE']);

?>

<h1><?php echo $_CLASS['core_user']->lang['MANAGE']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['FORUM_ADMIN_EXPLAIN']; ?></p><?php

if ($mode == 'sync')
{
	echo '<br /><div class="gen" align="center"><b>' . $_CLASS['core_user']->lang['FORUM_RESYNCED'] . '</b></div>';
}

?><form method="post" action="<?php echo generate_link('Forums&amp;file=admin_forums&amp;parent_id='.$parent_id, array('admin' => true)) ?>"><table width="100%" cellspacing="2" cellpadding="2" border="0" align="center">
	<tr>
		<td class="nav"><?php echo $navigation ?></td>
	</tr>
</table>
		
<table class="tablebg" width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="6"><?php echo $_CLASS['core_user']->lang['FORUM_ADMIN'] ?></th>
	</tr>
<?php

$sql = 'SELECT *
	FROM ' . FORUMS_FORUMS_TABLE . "
	WHERE parent_id = $parent_id
	ORDER BY left_id";
$result = $_CLASS['core_db']->query($sql);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$forum_type = $row['forum_type'];

	if ($row['forum_status'] == ITEM_LOCKED)
	{
		$folder_image = '<img src="images/modules/forums/adm/images/icon_folder_lock.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['LOCKED'] . '" />';
	}
	else
	{
		switch ($forum_type)
		{
			case FORUM_LINK:
				$folder_image = '<img src="images/modules/forums/adm/images/icon_folder_link.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['LINK'] . '" />';
				break;

			default:
				$folder_image = ($row['left_id'] + 1 != $row['right_id']) ? '<img src="images/modules/forums/adm/images/icon_subfolder.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['SUBFORUM'] . '" />' : '<img src="images/modules/forums/adm/images/icon_folder.gif" width="46" height="25" alt="' . $_CLASS['core_user']->lang['FOLDER'] . '" />';
		}
	}

	$forum_title = ($forum_type != FORUM_LINK) ? '<a href="'.generate_link('Forums&amp;file=admin_forums&amp;parent_id=' . $row['forum_id'], array('admin' => true)) . '">' : '';
	$forum_title .= $row['forum_name'];
	$forum_title .= ($forum_type != FORUM_LINK) ? '</a>' : '';
	$url = "Forums&amp;file=admin_forums&amp;parent_id=$parent_id&amp;f=" . $row['forum_id'];

?>
	<tr>
		<td class="row1" width="5%"><?php echo $folder_image; ?></td>
		<td class="row1" width="50%"><table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td><span class="forumlink"><?php echo $forum_title ?></span></td>
				</tr>
			</table>
			<table cellspacing="5" cellpadding="0" border="0">
				<tr>
					<td class="gensmall"><?php echo $row['forum_desc'] ?></td>
				</tr>
			</table>
<?php

	if ($forum_type == FORUM_POST)
	{

?>
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td class="gensmall">&nbsp;<?php echo $_CLASS['core_user']->lang['TOPICS']; ?>: <b><?php echo $row['forum_topics'] ?></b> / <?php echo $_CLASS['core_user']->lang['POSTS']; ?>: <b><?php echo $row['forum_posts'] ?></b></td>
				</tr>
			</table>
<?php

	}

?></td>
		<td class="row2" width="15%" align="center" valign="middle" nowrap="nowrap"><a href="<?php echo generate_link($url.'&amp;mode=move_up', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['MOVE_UP'] ?></a><br /><a href="<?php echo generate_link($url.'&amp;mode=move_down', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['MOVE_DOWN'] ?></a></td>
		<td class="row2" width="20%" align="center" valign="middle" nowrap="nowrap">&nbsp;<a href="<?php echo generate_link($url.'&amp;mode=edit', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['EDIT'] ?></a> | <a href="<?php echo generate_link($url.'&amp;mode=delete', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['DELETE'] ?></a><?php
			
	if ($forum_type != FORUM_LINK)
	{

?> | <a href="<?php echo generate_link($url.'&amp;mode=sync', array('admin' => true)); ?>"><?php echo $_CLASS['core_user']->lang['RESYNC'] ?></a><?php
	

	}
	
?>&nbsp;</td>
	</tr>
<?php

}

?>
	<tr>
		<td width="100%" colspan="6" class="cat"><input type="hidden" name="mode" value="add" /><input class="post" type="text" name="forum_name" /> <input class="btnlite" type="submit" value="<?php echo $_CLASS['core_user']->lang['CREATE_FORUM'] ?>" /></td>
	</tr>
</table></form>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_forums', array('admin' => true)) ?>"><table width="100%" cellpadding="1" cellspacing="1" border="0">
	<tr>
		<td align="right"><?php echo $_CLASS['core_user']->lang['SELECT_FORUM']; ?>: <select name="parent_id" onchange="if(this.options[this.selectedIndex].value != -1){ this.form.submit(); }"><?php echo $forum_box; ?></select> <input class="btnlite" type="submit" value="<?php echo $_CLASS['core_user']->lang['GO']; ?>" /><input type="hidden" name="sid" value="<?php echo $_CLASS['core_user']->session_id; ?>" /></td>
	</tr>
</table></form>
<?php

adm_page_footer();

//
// END
//


// ------------------
// Begin function block
//
function get_forum_info($forum_id)
{
	global $_CLASS;

	$sql = 'SELECT *
		FROM ' . FORUMS_FORUMS_TABLE . "
		WHERE forum_id = $forum_id";
	$result = $_CLASS['core_db']->query($sql);

	if (!($row = $_CLASS['core_db']->fetch_row_assoc($result)))
	{
		trigger_error("Forum #$forum_id does not exist", E_USER_ERROR);
	}

	return $row;
}

function update_forum_data(&$forum_data)
{
	global $_CLASS;

	$errors = array();
	if (!trim($forum_data['forum_name']))
	{
		$errors[] = $_CLASS['core_user']->lang['FORUM_NAME_EMPTY'];
	}

	if (!empty($_POST['forum_password']) || !empty($_POST['forum_password_confirm']))
	{
		if ($_POST['forum_password'] != $_POST['forum_password_confirm'])
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
	$forum_data['forum_flags'] = 0;
	$forum_data['forum_flags'] += ($forum_data['forum_link_track']) ? 1 : 0;
	$forum_data['forum_flags'] += ($forum_data['prune_old_polls']) ? 2 : 0;
	$forum_data['forum_flags'] += ($forum_data['prune_announce']) ? 4 : 0;
	$forum_data['forum_flags'] += ($forum_data['prune_sticky']) ? 8 : 0;

	// Unset data that are not database fields
	unset($forum_data['forum_link_track']);
	unset($forum_data['prune_old_polls']);
	unset($forum_data['prune_announce']);
	unset($forum_data['prune_sticky']);
	unset($forum_data['forum_password_confirm']);

	// What are we going to do tonight Brain? The same thing we do everynight,
	// try to take over the world ... or decide whether to continue update
	// and if so, whether it's a new forum/cat/link or an existing one
	if (count($errors))
	{
		return $errors;
	}

	if (empty($forum_data['forum_id']))
	{
		// no forum_id means we're creating a new forum

		$_CLASS['core_db']->transaction();

		if ($forum_data['parent_id'])
		{
			$sql = 'SELECT left_id, right_id
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_id = ' . $forum_data['parent_id'];
			$result = $_CLASS['core_db']->query($sql);

			if (!$row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				trigger_error('Parent does not exist', E_USER_ERROR);
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET left_id = left_id + 2, right_id = right_id + 2
				WHERE left_id > ' . $row['right_id'];
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET right_id = right_id + 2
				WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
			$_CLASS['core_db']->query($sql);

			$forum_data['left_id'] = $row['right_id'];
			$forum_data['right_id'] = $row['right_id'] + 1;
		}
		else
		{
			$sql = 'SELECT MAX(right_id) AS right_id
				FROM ' . FORUMS_FORUMS_TABLE;
			$result = $_CLASS['core_db']->query($sql);

			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			$forum_data['left_id'] = $row['right_id'] + 1;
			$forum_data['right_id'] = $row['right_id'] + 2;
		}

		$sql = 'INSERT INTO ' . FORUMS_FORUMS_TABLE . ' ' . $_CLASS['core_db']->sql_build_array('INSERT', $forum_data);
		$_CLASS['core_db']->query($sql);
		
		$_CLASS['core_db']->transaction('commit');

		$forum_data['forum_id'] = $_CLASS['core_db']->insert_id(FORUMS_FORUMS_TABLE, 'forum_id');
		add_log('admin', 'LOG_FORUM_ADD', $forum_data['forum_name']);
	}
	else
	{
		$row = get_forum_info($forum_data['forum_id']);

		if ($forum_data['forum_type'] != FORUM_POST && $row['forum_type'] != $forum_data['forum_type'])
		{
			// we're turning a postable forum into a non-postable forum

			if (empty($forum_data['action']))
			{
				// TODO: error message if no action is specified

				return array($_CLASS['core_user']->lang['']);
			}
			elseif ($forum_data['action'] == 'move')
			{
				if (!empty($forum_data['to_forum_id']))
				{
					$errors = move_forum_content($forum_data['forum_id'], $forum_data['to_forum_id']);				
				}
				else
				{
					return array($_CLASS['core_user']->lang['SELECT_DESTINATION_FORUM']);
				}
			}
			elseif ($forum_data['action'] == 'delete')
			{
				$errors = delete_forum_content($forum_data['forum_id']);
			}

			$forum_data['forum_posts'] = 0;
			$forum_data['forum_topics'] = 0;
			$forum_data['forum_topics_real'] = 0;
		}

		if ($row['parent_id'] != $forum_data['parent_id'])
		{
			$errors = move_forum($forum_data['forum_id'], $forum_data['parent_id']);
		}
		elseif ($row['forum_name'] != $forum_data['forum_name'])
		{
			// the forum name has changed, clear the parents list of child forums

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
				SET forum_parents = ''
				WHERE left_id > " . $row['left_id'] . '
					AND right_id < ' . $row['right_id'];
			$_CLASS['core_db']->query($sql);
		}

		if (count($errors))
		{
			return $errors;
		}

		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $forum_data) . '
			WHERE forum_id = ' . $forum_data['forum_id'];
		$_CLASS['core_db']->query($sql);

		add_log('admin', 'LOG_FORUM_EDIT', $forum_data['forum_name']);
	}
}

function move_forum($from_id, $to_id)
{
	global $_CLASS;

	$moved_forums = get_forum_branch($from_id, 'children', 'descending');
	$from_data = $moved_forums[0];
	$diff = count($moved_forums) * 2;

	$moved_ids = array();
	for ($i = 0; $i < count($moved_forums); ++$i)
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

function move_forum_content($from_id, $to_id, $sync = TRUE)
{
	// TODO: empty tables like forum_tracks or forum_access

	global $_CLASS;

	$table_ary = array(LOG_TABLE, POSTS_TABLE, TOPICS_TABLE);
	foreach ($table_ary as $table)
	{
		$sql = "UPDATE $table
			SET forum_id = $to_id
			WHERE forum_id = $from_id";
		$_CLASS['core_db']->query($sql);
	}
	unset($table_ary);

	if ($sync)
	{
		// Delete ghost topics that link back to the same forum
		// then resync counters

		sync('topic_moved');
		sync('forum', 'forum_id', $to_id);
	}
}

function delete_forum($forum_id, $action_posts = 'delete', $action_subforums = 'delete', $posts_to_id = 0, $subforums_to_id = 0)
{
	global $_CLASS;

	$row = get_forum_info($forum_id);
	extract($row);

	$errors = array();
	$log_action_posts = $log_action_forums = '';

	if ($action_posts == 'delete')
	{
		$_CLASS['core_db']->query('UPDATE '.FORUMS_FORUMS_TABLE.' SET forum_status = '.ITEM_DELETING.' WHERE forum_id = '.$forum_id);

		$log_action_posts = 'POSTS';
		
		if ($delete_error = delete_forum_content($forum_id))
		{
			$errors[] = $delete_error;
		}
	}
	elseif ($action_posts == 'move')
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

			if (!$row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$errors[] = $_CLASS['core_user']->lang['NO_FORUM'];
			}
			else
			{
				$posts_to_name = $row['forum_name'];
				unset($row);

				$errors[] = move_forum_content($forum_id, $subforums_to_id);
			}
		}
	}

	if (count($errors))
	{
		return $errors;
	}

	if ($action_subforums == 'delete')
	{
		$log_action_forums = 'FORUMS';

		$forum_ids = array($forum_id);
		$rows = get_forum_branch($forum_id, 'children', 'descending', FALSE);

// Maybe add feild to the get_forum_branch
		foreach ($rows as $row)
		{
			$forum_ids[] = $row['forum_id'];
		}
		unset($rows);

		$_CLASS['core_db']->query('UPDATE '.FORUMS_FORUMS_TABLE.'  SET forum_status = '.ITEM_DELETING.' WHERE forum_id  IN (' . implode(', ', $forum_ids) . ')');

		foreach ($forum_ids as $forum_id)
		{
			if ($delete_error = delete_forum_content($forum_id))
			{
				$errors[] = $delete_error;
			}
		}

		if (count($errors))
		{
			return $errors;
		}

		$diff = count($forum_ids) * 2;

		$sql = 'DELETE FROM ' . FORUMS_FORUMS_TABLE . '
			WHERE forum_id IN (' . implode(', ', $forum_ids) . ')';
		$_CLASS['core_db']->query($sql);
	}
	elseif ($action_subforums == 'move')
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

			if (!$row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$errors[] = $_CLASS['core_user']->lang['NO_FORUM'];
			}
			else
			{
				$subforums_to_name = $row['forum_name'];
				unset($row);

				$sql = 'SELECT forum_id
					FROM ' . FORUMS_FORUMS_TABLE . "
					WHERE parent_id = $forum_id";
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					move_forum($row['forum_id'], intval($_POST['subforums_to_id']));
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

		if (count($errors))
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
		WHERE left_id < $right_id AND right_id > $right_id";
	$_CLASS['core_db']->query($sql);

	$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . "
		SET left_id = left_id - $diff, right_id = right_id - $diff
		WHERE left_id > $right_id";
	$_CLASS['core_db']->query($sql);

	if (!is_array($forum_ids))
	{
		$forum_ids = array($forum_id);
	}

	// Delete forum ids from extension groups table
	$sql = 'SELECT group_id, allowed_forums 
		FROM ' . EXTENSION_GROUPS_TABLE . "
		WHERE allowed_forums <> ''";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$allowed_forums = unserialize(trim($row['allowed_forums']));
		$allowed_forums = array_diff($allowed_forums, $forum_ids);
		$sql = 'UPDATE ' . EXTENSION_GROUPS_TABLE . " 
			SET allowed_forums = '" . ((sizeof($allowed_forums)) ? serialize($allowed_forums) : '') . "'
			WHERE group_id = {$row['group_id']}";
		$_CLASS['core_db']->query($sql);
	}
	$_CLASS['core_cache']->destroy('extensions');

	$log_action = implode('_', array($log_action_posts, $log_action_forums));

	switch ($log_action)
	{
		case 'MOVE_POSTS_MOVE_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_POSTS_MOVE_FORUMS', $posts_to_name, $subforums_to_name, $forum_name);
			break;
		case 'MOVE_POSTS_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_POSTS_FORUMS', $posts_to_name, $forum_name);
			break;
		case 'POSTS_MOVE_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_POSTS_MOVE_FORUMS', $subforums_to_name, $forum_name);
			break;
		case '_MOVE_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_FORUMS', $subforums_to_name, $forum_name);
			break;
		case 'MOVE_POSTS_':
			add_log('admin', 'LOG_FORUM_DEL_MOVE_POSTS', $posts_to_name, $forum_name);
			break;
		case 'POSTS_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_POSTS_FORUMS', $forum_name);
			break;
		case '_FORUMS':
			add_log('admin', 'LOG_FORUM_DEL_FORUMS', $forum_name);
			break;
		case 'POSTS_':
			add_log('admin', 'LOG_FORUM_DEL_POSTS', $forum_name);
			break; 
	}

	return $errors;
}

function delete_forum_content($forum_id)
{
	global $_CLASS, $site_file_root;
	require_once($site_file_root.'includes/forums/functions_posting.php');

	$_CLASS['core_db']->transaction();

	switch ($_CLASS['core_db']->db_layer)
	{
// Needs updating and testing
/*		case 'mysql4':
		case 'mysqli':
			// Select then delete all attachments
			$sql = 'SELECT a.topic_id
				FROM ' . POSTS_TABLE . ' p, ' . ATTACHMENTS_TABLE . " a
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

			// Delete everything else and thank MySQL for offering multi-table deletion
			$tables_ary = array(
				BOOKMARKS_TABLE 	=> 'bm.topic_id',
				SEARCH_MATCH_TABLE	=> 'wm.post_id',
				REPORTS_TABLE		=> 're.post_id',
				TOPICS_WATCH_TABLE	=> 'tw.topic_id',
				TOPICS_TRACK_TABLE	=> 'tt.topic_id',
				POLL_OPTIONS_TABLE	=> 'po.topic_id',
				POLL_VOTES_TABLE	=> 'pv.topic_id'
			);

			$sql = 'DELETE QUICK FROM ' . POSTS_TABLE;
			$sql_using = "\nUSING " . POSTS_TABLE . ' p';
			$sql_where = "\nWHERE p.forum_id = $forum_id\n";
			$sql_optimise = 'OPTIMIZE TABLE . ' . POSTS_TABLE;

			foreach ($tables_ary as $table => $field)
			{
				$sql .= ", $table";
				$sql_using .= ", $table " . strtok($field, '.');
				$sql_where .= "\nAND $field = p." . strtok('');
				$sql_optimise .= ', ' . $table;
			}

			$_CLASS['core_db']->query($sql . $sql_using . $sql_where);

			$tables_ary = array(FORUMS_ACCESS_TABLE, TOPICS_TABLE, FORUMS_TRACK_TABLE, FORUMS_WATCH_TABLE, ACL_GROUPS_TABLE, ACL_USERS_TABLE, MODERATOR_TABLE, LOG_TABLE);
			foreach ($tables_ary as $table)
			{
				$_CLASS['core_db']->query("DELETE QUICK FROM $table WHERE forum_id = $forum_id");
				$sql_optimise .= ', ' . $table;
			}

			// Now optimise a hell lot of tables
			$_CLASS['core_db']->query($sql_optimise);
		break;
*/
		default:
			// Select then delete all attachments
			$sql = 'SELECT a.attach_id, a.physical_filename, a.thumbnail
				FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_ATTACHMENTS_TABLE . " a
				WHERE p.forum_id = $forum_id
					AND a.in_message = 0
					AND a.post_msg_id = p.post_id";
			$result = $_CLASS['core_db']->query($sql);	

			$attach_ids = array();
			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$attach_ids[] = $row['attach_id'];

				phpbb_unlink($row['physical_filename'], 'file');

				if ($row['thumbnail'])
				{
					phpbb_unlink($row['physical_filename'], 'thumbnail');
				}
			}
			$_CLASS['core_db']->free_result($result);

			if (count($attach_ids))
			{
				$attach_id_list = implode(',', array_unique($attach_ids));
				$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_ATTACHMENTS_TABLE . " WHERE attach_id IN ($attach_id_list)");
				unset($attach_ids, $attach_id_list);
			}

			// Delete everything else and curse your DB for not offering multi-table deletion
			$tables_ary = array(
				'post_id'	=>	array(
					FORUMS_SEARCH_MATCH_TABLE,
					FORUMS_REPORTS_TABLE,
				),
				
				'topic_id'	=>	array(
					FORUMS_BOOKMARKS_TABLE,
					FORUMS_WATCH_TABLE,
					FORUMS_TRACK_TABLE,
					FORUMS_POLL_OPTIONS_TABLE,
					FORUMS_POLL_VOTES_TABLE
				)
			);

			foreach ($tables_ary as $field => $tables)
			{
				$start = 0;
				$id_array = array();

				$sql = "SELECT $field
					FROM " . FORUMS_POSTS_TABLE . '
					WHERE forum_id = ' . $forum_id;
				$result = $_CLASS['core_db']->query($sql);

				while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
				{
					$id_array[] = $row[$field];
				}
				$_CLASS['core_db']->free_result($result);

				if (empty($id_array))
				{
					continue;
				}

				foreach ($tables as $table)
				{
					$_CLASS['core_db']->query("DELETE FROM $table WHERE $field IN (".implode(',', $id_array).')');
				}
			}
			unset($id_array);

			$table_ary = array(FORUMS_POSTS_TABLE, FORUMS_TRACK_TABLE, FORUMS_WATCH_TABLE, FORUMS_TOPICS_TABLE, FORUMS_ACL_TABLE, FORUMS_MODERATOR_TABLE, FORUMS_LOG_TABLE);

			foreach ($table_ary as $table)
			{
				$_CLASS['core_db']->query("DELETE FROM $table WHERE forum_id = $forum_id");
			}
		break;
	}

	$_CLASS['core_db']->transaction('commit');
	
	// NEED to have this done at the end, maybe a cran
	$tables = array_unique(array_merge($tables_ary['post_id'], $tables_ary['topic_id'], $table_ary, array(FORUMS_ATTACHMENTS_TABLE)));
	$_CLASS['core_db']->optimize_tables($tables);
}

function recalc_btree()
{
	global $_CLASS;

	$sql = 'SELECT forum_id, parent_id, left_id, right_id 
		FROM ' . FORUMS_FORUMS_TABLE . '
		ORDER BY parent_id ASC';
	$f_result = $_CLASS['core_db']->query($sql);

	while ($forum_data = $_CLASS['core_db']->fetch_row_assoc($f_result))
	{
		if ($forum_data['parent_id'])
		{
			$sql = 'SELECT left_id, right_id
				FROM ' . FORUMS_FORUMS_TABLE . '
				WHERE forum_id = ' . $forum_data['parent_id'];
			$result = $_CLASS['core_db']->query($sql);

			if (!$row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . ' SET parent_id = 0 WHERE forum_id = ' . $forum_data['forum_id'];
				$_CLASS['core_db']->query($sql);
			}
			$_CLASS['core_db']->free_result($result);

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET left_id = left_id + 2, right_id = right_id + 2
				WHERE left_id > ' . $row['right_id'];
			$_CLASS['core_db']->query($sql);

			$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
				SET right_id = right_id + 2
				WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
			$_CLASS['core_db']->query($sql);

			$forum_data['left_id'] = $row['right_id'];
			$forum_data['right_id'] = $row['right_id'] + 1;
		}
		else
		{
			$sql = 'SELECT MAX(right_id) AS right_id
				FROM ' . FORUMS_FORUMS_TABLE;
			$result = $_CLASS['core_db']->query($sql);

			$row = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);

			$forum_data['left_id'] = $row['right_id'] + 1;
			$forum_data['right_id'] = $row['right_id'] + 2;
		}
	
		$sql = 'UPDATE ' . FORUMS_FORUMS_TABLE . '
			SET left_id = ' . $forum_data['left_id'] . ', right_id = ' . $forum_data['right_id'] . '
			WHERE forum_id = ' . $forum_data['forum_id'];
		$_CLASS['core_db']->query($sql);
	}
	$_CLASS['core_db']->free_result($f_result);
}

//
// End function block
// ------------------

?>