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

// Check permissions
if (!$_CLASS['auth']->acl_get('a_search'))
{
	trigger_error('NO_ADMIN');
}

// Start indexing
if (isset($_POST['start']) || isset($_GET['position']))
{
	$limit = 5000; // Process this many posts per batch
	$start = get_variable('position', 'REQUEST', 0, 'int');

	$count = 0;

	if (!$start)
	{
		switch ($_CLASS['core_db']->db_layer)
		{
			case 'sqlite':
			case 'sqlite_pdo':
				$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_SEARCH_TABLE);
				$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_SEARCH_WORD_TABLE);
				$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_SEARCH_MATCH_TABLE);
			break;
			
			default:
				$_CLASS['core_db']->query('TRUNCATE ' . FORUMS_SEARCH_TABLE);
				$_CLASS['core_db']->query('TRUNCATE ' . FORUMS_SEARCH_WORD_TABLE);
				$_CLASS['core_db']->query('TRUNCATE ' . FORUMS_SEARCH_MATCH_TABLE);
			break;
		}
	}

	// Fetch a batch of posts_text entries
	$result = $_CLASS['core_db']->query('SELECT COUNT(*) AS total FROM ' . FORUMS_POSTS_TABLE);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if ($total_posts = $row['total'])
	{
		require_once(SITE_FILE_ROOT . 'includes/forums/message_parser.php');
		$fulltext = new fulltext_search();

		$sql = 'SELECT post_id, post_subject, post_text
			FROM ' . FORUMS_POSTS_TABLE . '
			ORDER BY post_id';
		$result = $_CLASS['core_db']->query_limit($sql, $limit, $start);
	
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$fulltext->add('admin', $row['post_id'], $row['post_text'], $row['post_subject']);
			$count++;
		}
		$_CLASS['core_db']->free_result($result);

		if (($start + $count) < $total_posts)
		{
			redirect(generate_link('Forums&amp;file=admin_search&position=' . ($start + $count), array('admin' => true)), 3);
		}
		else
		{
			// search tidy
			$fulltext->search_tidy();
			$_CLASS['core_db']->optimize_tables();
		}
	}

	adm_page_header($_CLASS['core_user']->get_lang('SEARCH_INDEX'));

?>

<h1><?php echo $_CLASS['core_user']->get_lang('SEARCH_INDEX'); ?></h1>

<p><?php echo $_CLASS['core_user']->get_lang('SEARCH_INDEX_COMPLETE'); ?></p>

<?php

	adm_page_footer();

	die;
}
else if (isset($_POST['cancel']))
{
	adm_page_header($_CLASS['core_user']->lang['SEARCH_INDEX']);

?>

<h1><?php echo $_CLASS['core_user']->lang['SEARCH_INDEX']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['SEARCH_INDEX_CANCEL']; ?></p>

<?php

	adm_page_footer();
	die;

}

adm_page_header($_CLASS['core_user']->lang['SEARCH_INDEX']);

?>

<h1><?php echo $_CLASS['core_user']->lang['SEARCH_INDEX']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['SEARCH_INDEX_EXPLAIN']; ?></p>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_search', array('admin' => true)); ?>"><table cellspacing="1" cellpadding="4" border="0" align="center" bgcolor="#98AAB1">
	<tr>
		<td class="cat" height="28" align="center">&nbsp;<input type="submit" name="start" value="<?php echo $_CLASS['core_user']->lang['START']; ?>" class="btnmain" /> &nbsp; <input type="submit" name="cancel" value="<?php echo $_CLASS['core_user']->lang['CANCEL']; ?>" class="btnmain" />&nbsp;</td>
	</tr>
</table></form>

<?php

adm_page_footer();

?>