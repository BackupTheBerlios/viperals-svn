<?php
// -------------------------------------------------------------
//
// $Id: admin_viewlogs.php,v 1.13 2004/11/06 14:11:47 acydburn Exp $
//
// FILENAME  : admin_viewlogs.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Do we have styles admin permissions?
if (!$_CLASS['auth']->acl_get('a_'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Set some variables
$mode		= request_var('mode', 'admin');
$forum_id	= request_var('f', 0);
$start		= request_var('start', 0);
$deletemark = (isset($_POST['delmarked'])) ? true : false;
$deleteall	= (isset($_POST['delall'])) ? true : false;
$marked		= request_var('mark', array('' => ''));

// Sort keys
$sort_days	= request_var('st', 0);
$sort_key	= request_var('sk', 't');
$sort_dir	= request_var('sd', 'd');

// Define some vars depending on which logs we're looking at
$log_type = ($mode == 'admin') ? LOG_ADMIN : (($mode == 'mod') ? LOG_MOD : LOG_CRITICAL);

$_CLASS['core_user']->add_lang('mcp');

// Delete entries if requested and able
if (($deletemark || $deleteall) && $_CLASS['auth']->acl_get('a_clearlogs'))
{
	$where_sql = '';
	if ($deletemark && $marked)
	{
		$sql_in = array();
		foreach ($marked as $mark)
		{
			$sql_in[] =  $mark;
		}
		$where_sql = ' AND log_id IN (' . implode(', ', $sql_in) . ')';
		unset($sql_in);
	}

	$sql = 'DELETE FROM ' . LOG_TABLE . "
		WHERE log_type = $log_type 
			$where_sql";
	$_CLASS['core_db']->sql_query($sql);

	add_log('admin', 'LOG_' . strtoupper($mode) . '_CLEAR');
}

// Sorting
$limit_days = array(0 => $_CLASS['core_user']->lang['ALL_ENTRIES'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);
$sort_by_text = array('u' => $_CLASS['core_user']->lang['SORT_USERNAME'], 't' => $_CLASS['core_user']->lang['SORT_DATE'], 'i' => $_CLASS['core_user']->lang['SORT_IP'], 'o' => $_CLASS['core_user']->lang['SORT_ACTION']);
$sort_by_sql = array('u' => 'l.user_id', 't' => 'l.log_time', 'i' => 'l.log_ip', 'o' => 'l.log_operation');

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

// Define where and sort sql for use in displaying logs
$sql_where = ($sort_days) ? (time() - ($sort_days * 86400)) : 0;
$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

$l_title = $_CLASS['core_user']->lang[strtoupper($mode) . '_LOGS'];
$l_title_explain = $_CLASS['core_user']->lang[strtoupper($mode) . '_LOGS_EXPLAIN'];

// Output page
adm_page_header($l_title);

?>

<h1><?php echo $l_title; ?></h1>

<p><?php echo $l_title_explain; ?></p>

<form name="list" method="post" action="<?php echo generate_link('Forums&amp;file=admin_viewlogs&amp;mode='.$mode, array('admin' => true)); ?>">
<?php

// Define forum list if we're looking @ mod logs
if ($mode == 'mod')
{

	$forum_box = '<option value="0">' . $_CLASS['core_user']->lang['ALL_FORUMS'] . '</option>' . make_forum_select($forum_id);

?>
<table width="100%" cellpadding="1" cellspacing="1" border="0">
	<tr>
		<td align="right"><?php echo $_CLASS['core_user']->lang['SELECT_FORUM']; ?>: <select name="f" onchange="if(this.options[this.selectedIndex].value != -1){ this.form.submit() }"><?php echo $forum_box; ?></select> <input class="btnlite" type="submit" value="<?php echo $_CLASS['core_user']->lang['GO']; ?>" /></td>
	</tr>
</table>
<?php

}

//
// Grab log data
//
$log_data = array();
$log_count = 0;
view_log($mode, $log_data, $log_count, $config['topics_per_page'], $start, $forum_id, 0, 0, $sql_where, $sql_sort);

?>
<table width="100%" cellspacing="2" cellpadding="2" border="0" align="center">
<tr>
	<td align="left" valign="top">&nbsp;<span class="nav"><?php echo on_page($log_count, $config['topics_per_page'], $start); ?></span></td>
	<td align="right" valign="top" nowrap="nowrap">
		<span class="nav"><?php	echo generate_pagination("admin_viewlogs&amp;mode=$mode&amp;$u_sort_param", $log_count, $config['topics_per_page'], $start, true); ?></span>
	</td>
</tr>
</table>

<table class="tablebg" width="100%" cellpadding="4" cellspacing="1" border="0">
	<tr>
		<td class="cat" colspan="5" height="28" align="center"><?php echo $_CLASS['core_user']->lang['DISPLAY_LOG']; ?>: &nbsp;<?php echo $s_limit_days; ?>&nbsp;<?php echo $_CLASS['core_user']->lang['SORT_BY']; ?>: <?php echo $s_sort_key; ?> <?php echo $s_sort_dir; ?>&nbsp;<input class="btnlite" type="submit" value="<?php echo $_CLASS['core_user']->lang['GO']; ?>" name="sort" /></td>
	</tr>
	<tr>
		<th width="15%" height="25" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['USERNAME']; ?></th>
		<th width="15%" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['IP']; ?></th>
		<th width="20%" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['TIME']; ?></th>
		<th width="45%" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['ACTION']; ?></th>
		<th nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['MARK']; ?></th>
	</tr>
<?php

if ($log_count)
{
	for($i = 0; $i < sizeof($log_data); $i++)
	{
		$row_class = ($row_class == 'row1') ? 'row2' : 'row1';
		
?>
	<tr>
		<td class="<?php echo $row_class; ?>" nowrap="nowrap"><?php echo $log_data[$i]['username']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center" nowrap="nowrap"><?php echo $log_data[$i]['ip']; ?></td>
		<td class="<?php echo $row_class; ?>" align="center" nowrap="nowrap"><?php echo $_CLASS['core_user']->format_date($log_data[$i]['time']); ?></td>
		<td class="<?php echo $row_class; ?>"><?php 
			echo $log_data[$i]['action']; 

			$data = array();
				
			foreach (array('viewtopic', 'viewlogs', 'viewforum') as $check)
			{
				if ($log_data[$i][$check])
				{
					$data[] = '<a href="' . $log_data[$i][$check] . '">' . $_CLASS['core_user']->lang['LOGVIEW_' . strtoupper($check)] . '</a>';
				}
			}

			if (sizeof($data))
			{
				echo '<br />&#187; <span class="gensmall">[ ' . implode(' | ', $data) . ' ]</span>';
			}
?>
		</td>
		<td class="<?php echo $row_class; ?>" align="center" nowrap="nowrap"><input type="checkbox" name="mark[]" value="<?php echo $log_data[$i]['id']; ?>" /></td>
	</tr>
<?php

	}

	if ($_CLASS['auth']->acl_get('a_clearlogs'))
	{

?>
	<tr>
		<td class="cat" colspan="5" height="28" align="right"><input class="btnlite" type="submit" name="delmarked" value="<?php echo $_CLASS['core_user']->lang['DELETE_MARKED']; ?>" />&nbsp; <input class="btnlite" type="submit" name="delall" value="<?php echo $_CLASS['core_user']->lang['DELETE_ALL']; ?>" />&nbsp;</td>
	</tr>
<?php

	}
}
else
{
?>
	<tr>
		<td class="row1" colspan="5" align="center" nowrap="nowrap"><?php echo $_CLASS['core_user']->lang['NO_ENTRIES']; ?></td>
	</tr>
<?php

}

?>
</table>

<table width="100%" cellspacing="2" cellpadding="2" border="0" align="center">
	<tr>
		<td align="left" valign="top">&nbsp;<span class="nav"><?php echo on_page($log_count, $config['topics_per_page'], $start); ?></span></td>
		<td align="right" valign="top" nowrap="nowrap"><span class="nav"><?php

	if ($_CLASS['auth']->acl_get('a_clearlogs'))
	{


?><b><a href="javascript:marklist('list', true);"><?php echo $_CLASS['core_user']->lang['MARK_ALL']; ?></a> :: <a href="javascript:marklist('list', false);"><?php echo $_CLASS['core_user']->lang['UNMARK_ALL']; ?></a></b>&nbsp;<br /><br /><?php

	}

	echo generate_pagination("Forums&amp;file=admin_viewlogs&amp;mode=$mode&amp;$u_sort_param", $log_count, $config['topics_per_page'], $start, true);
	
?></span></td>
	</tr>
</table></form>

<script language="Javascript" type="text/javascript">
<!--
function marklist(match, status)
{
	len = eval('document.' + match + '.length');
	for (i = 0; i < len; i++)
	{
		eval('document.' + match + '.elements[i].checked = ' + status);
	}
}
//-->
</script>

<?php

adm_page_footer();

?>