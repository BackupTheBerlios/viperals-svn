<?php
/** 
*
* @package acp
* @version $Id: admin_disallow.php,v 1.6 2005/04/09 12:26:28 acydburn Exp $
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

// Check permissions
if (!$_CLASS['auth']->acl_get('a_names'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

require($site_file_root . 'includes/forums/functions_user.php');

if (isset($_POST['disallow']))
{
	$disallowed_user = (isset($_REQUEST['disallowed_user'])) ? htmlspecialchars($_REQUEST['disallowed_user']) : '';
	$disallowed_user = str_replace('*', '%', $disallowed_user);

	if (!$disallowed_user)
	{
		trigger_error('No_VALUE');
	}
	
	if (validate_username($disallowed_user))
	{
		$message = $_CLASS['core_user']->lang['Disallowed_already'];
	}
	else
	{
		$sql = 'INSERT INTO ' . DISALLOW_TABLE . " (disallow_username)
			VALUES('" . $_CLASS['core_db']->sql_escape(stripslashes($disallowed_user)) . "')";
		$result = $_CLASS['core_db']->sql_query($sql);

		$message = $_CLASS['core_user']->lang['Disallow_successful'];
	}

	add_log('admin', 'log_disallow_add', str_replace('%', '*', $disallowed_user));

	trigger_error($message);
}
else if (isset($_POST['allow']))
{
	$disallowed_id = (isset($_REQUEST['disallowed_id'])) ? intval($_REQUEST['disallowed_id']) : '';

	if (empty($disallowed_id))
	{
		trigger_error($_CLASS['core_user']->lang['No_user_selected']);
	}

	$sql = 'DELETE FROM ' . DISALLOW_TABLE . "
		WHERE disallow_id = $disallowed_id";
	$_CLASS['core_db']->sql_query($sql);

	add_log('admin', 'log_disallow_delete');

	trigger_error($_CLASS['core_user']->lang['Disallowed_deleted']);
}

// Grab the current list of disallowed usernames...
$sql = 'SELECT *
	FROM ' . DISALLOW_TABLE;
$result = $_CLASS['core_db']->sql_query($sql);

$disallow_select = '';
if ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	do
	{
		$disallow_select .= '<option value="' . $row['disallow_id'] . '">' . str_replace('%', '*', $row['disallow_username']) . '</option>';
	}
	while ($row = $_CLASS['core_db']->sql_fetchrow($result));
}

// Output page
adm_page_header($_CLASS['core_user']->lang['DISALLOW']);

?>

<h1><?php echo $_CLASS['core_user']->lang['DISALLOW']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['Disallow_explain']; ?></p>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_disallow', array('admin' => true)); ?>"><table class="tablebg" width="80%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['Add_disallow_title']; ?></th>
	</tr>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['USERNAME']; ?><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['Add_disallow_explain']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="disallowed_user" size="30" />&nbsp;</td>
	</tr>
	<tr>
		<td class="cat" colspan="2" align="center"><input class="btnmain" type="submit" name="disallow" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" />&nbsp;&nbsp;<input class="btnlite" type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" />
	</tr>
</table>

<h1><?php echo $_CLASS['core_user']->lang['Delete_disallow_title']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['Delete_disallow_explain']; ?></p>

<table class="tablebg" width="80%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['Delete_disallow_title']; ?></th>
	</tr>
<?php

	if ($disallow_select != '')
	{

?>
	<tr>
		<td class="row1"><?php echo $_CLASS['core_user']->lang['USERNAME']; ?></td>
		<td class="row2"><select class="post" name="disallowed_id"><?php echo $disallow_select; ?></select></td>
	</tr>
	<tr>
		<td class="cat" colspan="2" align="center"><input class="btnmain" type="submit" name="allow" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" />&nbsp;&nbsp;<input class="btnlite" type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" />
	</tr>
<?php

	}
	else
	{

?>
	<tr>
		<td class="row1" colspan="2" align="center"><?php echo $_CLASS['core_user']->lang['No_disallowed']; ?></td>
	</tr>
<?php

	}

?>
</table></form>

<?php

adm_page_footer();

?>