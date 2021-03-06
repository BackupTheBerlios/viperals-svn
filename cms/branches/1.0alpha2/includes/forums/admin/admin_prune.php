<?php
/** 
*
* @package acp
* @version $Id: admin_prune.php,v 1.11 2005/04/09 12:26:30 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
*/
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Do we have permission?
if (!$_CLASS['auth']->acl_get('a_prune'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Get the forum ID for pruning
$forum_id = (isset($_REQUEST['f'])) ? array_map('intval', $_REQUEST['f']) : array();

// Check for submit to be equal to Prune. If so then proceed with the pruning.
if (isset($_POST['submit']))
{
	$prune_posted = (isset($_POST['prune_days'])) ? intval($_POST['prune_days']) : 0;
	$prune_viewed = (isset($_POST['prune_vieweddays'])) ? intval($_POST['prune_vieweddays']) : 0;
	$prune_all = !$prune_posted && !$prune_viewed;
	
	$prune_flags = 0;
	$prune_flags += (!empty($_POST['prune_old_polls'])) ? 2 : 0;
	$prune_flags += (!empty($_POST['prune_announce'])) ? 4 : 0;
	$prune_flags += (!empty($_POST['prune_sticky'])) ? 8 : 0;

	// Convert days to seconds for timestamp functions...
	$prunedate_posted = time() - ($prune_posted * 86400);
	$prunedate_viewed = time() - ($prune_viewed * 86400);

	adm_page_header($_CLASS['core_user']->lang['PRUNE']);

?>

<h1><?php echo $_CLASS['core_user']->lang['PRUNE']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['PRUNE_SUCCESS']; ?></p>

<table class="tablebg" width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th><?php echo $_CLASS['core_user']->lang['FORUM']; ?></th>
		<th><?php echo $_CLASS['core_user']->lang['TOPICS_PRUNED']; ?></th>
		<th><?php echo $_CLASS['core_user']->lang['POSTS_PRUNED']; ?></th>
	</tr>
<?php

	$sql_forum = (sizeof($forum_id)) ? ' AND forum_id IN (' . implode(', ', $forum_id) . ')' : '';

	// Get a list of forum's or the data for the forum that we are pruning.
	$sql = 'SELECT forum_id, forum_name 
		FROM ' . FORUMS_TABLE . '
		WHERE forum_type = ' . FORUM_POST . "
			$sql_forum 
		ORDER BY left_id ASC";
	$result = $_CLASS['core_db']->sql_query($sql);

	if ($row = $_CLASS['core_db']->sql_fetchrow($result))
	{
		$prune_ids = array();
		$p_result['topics'] = 0;
		$p_result['posts'] = 0;
		$log_data = '';
		do
		{
			if ($_CLASS['auth']->acl_get('f_list', $row['forum_id']))
			{
				if ($prune_all)
				{
					$p_result = prune($row['forum_id'], 'posted', time(), $prune_flags, false);
				}
				else
				{
					if ($prune_posted)
					{
						$return = prune($row['forum_id'], 'posted', $prunedate_posted, $prune_flags, false);
						$p_result['topics'] += $return['topics'];
						$p_result['posts'] += $return['posts'];
					}
					if ($prune_viewed)
					{
						$return = prune($row['forum_id'], 'viewed', $prunedate_viewed, $prune_flags, false);
						$p_result['topics'] += $return['topics'];
						$p_result['posts'] += $return['posts'];
					}
				}

				
				$prune_ids[] = $row['forum_id'];

				$row_class = ($row_class == 'row1') ? 'row2' : 'row1';

?>
	<tr>
		<td class="<?php echo $row_class; ?>" align="center"><?php echo $row['forum_name']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center"><?php echo $p_result['topics']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center"><?php echo $p_result['posts']; ?></td>
	</tr>
<?php
	
				$log_data .= (($log_data != '') ? ', ' : '') . $row['forum_name'];
			}
		}
		while ($row = $_CLASS['core_db']->sql_fetchrow($result));

		// Sync all pruned forums at once
		sync('forum', 'forum_id', $prune_ids, TRUE);

		add_log('admin', 'LOG_PRUNE', $log_data);
	}
	else
	{

?>
	<tr>
		<td class="row1" align="center"><?php echo $_CLASS['core_user']->lang['NO_PRUNE']; ?></td>
	</tr>
<?php

	}
	$_CLASS['core_db']->sql_freeresult($result);

?>
</table>

<br clear="all" />

<?php

	adm_page_footer();

}

adm_page_header($_CLASS['core_user']->lang['PRUNE']);

?>

<h1><?php echo $_CLASS['core_user']->lang['PRUNE']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['FORUM_PRUNE_EXPLAIN']; ?></p>

<?php

// If they haven't selected a forum for pruning yet then
// display a select box to use for pruning.
if (!$forum_id)
{

?>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_prune', array('admin' => true)); ?>"><table class="tablebg" width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th align="center"><?php echo $_CLASS['core_user']->lang['SELECT_FORUM']; ?></th>
	</tr>
	<tr>
		<td class="row1" align="center"><select style="width:280px" name="f[]" multiple="true" size="5"><?php echo make_forum_select(false, false, false); ?></select></td>
	</tr>
	<tr>
		<td class="cat" align="center"><input class="btnmain" type="submit" value="<?php echo $_CLASS['core_user']->lang['LOOK_UP_FORUM']; ?>" />&nbsp; <input type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" class="btnlite" /></td>
	</tr>
</table></form>

<?php

}
else
{
	$sql = 'SELECT forum_id, forum_name 
		FROM ' . FORUMS_TABLE . ' 
		WHERE forum_id IN (' . implode(', ', $forum_id) . ')';
	$result = $_CLASS['core_db']->sql_query($sql);

	if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
	{
		trigger_error($_CLASS['core_user']->lang['NO_FORUM']);
	}

	$forum_list = $s_hidden_fields = '';
	do
	{
		$forum_list .= (($forum_list != '') ? ', ' : '') . '<b>' . $row['forum_name'] . '</b>';
		$s_hidden_fields .= '<input type="hidden" name="f[]" value="' . $row['forum_id'] . '" />';
	}
	while ($row = $_CLASS['core_db']->sql_fetchrow($result));
	$_CLASS['core_db']->sql_freeresult($result);

	$l_selected_forums = (sizeof($forum_id) == 1) ? 'SELECTED_FORUM' : 'SELECTED_FORUMS';

?>

<h2><?php echo $_CLASS['core_user']->lang['FORUM']; ?></h2>

<p><?php echo $_CLASS['core_user']->lang[$l_selected_forums] . ': ' . $forum_list; ?></p>

<form method="post"	action="<?php echo generate_link('Forums&amp;file=admin_prune', array('admin' => true)); ?>"><table class="tablebg" width="100%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['FORUM_PRUNE']; ?></th>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['PRUNE_NOT_POSTED']; ?></td>
		<td class="row2"><input type="text" name="prune_days" size="4" /></td>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['PRUNE_NOT_VIEWED']; ?></td>
		<td class="row2"><input type="text" name="prune_vieweddays" size="4" /></td>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['PRUNE_OLD_POLLS'] ?>: <br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['PRUNE_OLD_POLLS_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="prune_old_polls" value="1" /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="prune_old_polls" value="0" checked="checked" /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['PRUNE_ANNOUNCEMENTS'] ?>: </td>
		<td class="row2"><input type="radio" name="prune_announce" value="1" /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="prune_announce" value="0" checked="checked" /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['PRUNE_STICKY'] ?>: </td>
		<td class="row2"><input type="radio" name="prune_sticky" value="1" /> <?php echo $_CLASS['core_user']->lang['YES']; ?> &nbsp; <input type="radio" name="prune_sticky" value="0" checked="checked" /> <?php echo $_CLASS['core_user']->lang['NO']; ?></td>
	</tr>
	<tr>
		<td class="cat" colspan="2" align="center"><?php echo $s_hidden_fields; ?><input type="submit" name="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" class="btnmain"></td>
	</tr>
</table></form>

<?php

}

adm_page_footer();

?>