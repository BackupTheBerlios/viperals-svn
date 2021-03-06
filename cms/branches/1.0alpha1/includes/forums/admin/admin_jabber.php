<?php
/** 
*
* @package acp
* @version $Id: admin_jabber.php,v 1.9 2005/04/09 12:26:30 acydburn Exp $
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
* @todo Check/enter/update transport info
*/
if (!defined('VIPERAL') || VIPERAL != 'Admin')
{
	die; 
}

// Do we have general permissions?
if (!$_CLASS['auth']->acl_get('a_server'))
{
	trigger_error($_CLASS['core_user']->lang['NO_ADMIN']);
}

// Grab some basic parameters
$submit = (isset($_POST['submit'])) ? true : false;

$jab_enable		= request_var('jab_enable', $config['jab_enable']);
$jab_host		= request_var('jab_host', $config['jab_host']);
$jab_port		= request_var('jab_port', $config['jab_port']);
$jab_username	= request_var('jab_username', $config['jab_username']);
$jab_password	= request_var('jab_password', $config['jab_password']);
$jab_resource	= request_var('jab_resource', $config['jab_resource']);

include($site_file_root.'includes/forums/functions_jabber.php');

$jabber = new jabber();
$error = array();

// Setup the basis vars for jabber connection
$jabber->server		= $jab_host;
$jabber->port		= ($jab_port) ? $jab_port : 5222;
$jabber->username	= $jab_username;
$jabber->password	= $jab_password;
$jabber->resource	= $jab_resource;

// Are changing (or initialising) a new host or username? If so run some checks and 
// try to create account if it doesn't exist
if ($jab_enable)
{
	if ($jab_host != $config['jab_host'] || $jab_username != $config['jab_username'])
	{
		if (!$jabber->connect())
		{
			trigger_error('Could not connect to Jabber server', E_USER_ERROR);
		}
		// First we'll try to authorise using this account, if that fails we'll
		// try to create it.
		if (!($result = $jabber->send_auth()))
		{
			if (($result = $jabber->account_registration($config['board_email'], $_CORE_CONFIG['global']['site_name'])) <> 2)
			{
				$error[] = ($result == 1) ? $_CLASS['core_user']->lang['ERR_JAB_USERNAME'] : sprintf($_CLASS['core_user']->lang['ERR_JAB_REGISTER'], $result);
			}
			else
			{
				$message = $_CLASS['core_user']->lang['JAB_REGISTERED'];
				$log = 'JAB_REGISTER';
			}
		}
		else
		{
			$message = $_CLASS['core_user']->lang['JAB_CHANGED'];
			$log = 'JAB_CHANGED';
		}

		sleep(1);
		$jabber->disconnect();
	}
	else if ($jab_password != $config['jab_password'])
	{echo 'test';
		
		if (!$jabber->connect())
		{
			trigger_error('Could not connect to Jabber server', E_USER_ERROR);
		}

		if (!$jabber->send_auth())
		{
			trigger_error('Could not authorise on Jabber server', E_USER_ERROR);
		}
		$jabber->send_presence(NULL, NULL, 'online');

		if (($result = $jabber->change_password($jab_password))  <> 2)
		{
			$error[] = ($result == 1) ? $_CLASS['core_user']->lang['ERR_JAB_PASSCHG'] : sprintf($_CLASS['core_user']->lang['ERR_JAB_PASSFAIL'], $result);
		}
		else
		{
			$message = $_CLASS['core_user']->lang['JAB_PASS_CHANGED'];
			$log = 'JAB_PASSCHG';
		}

		sleep(1);
		$jabber->disconnect();
	}
}

// Pull relevant config data
$sql = 'SELECT *
	FROM ' . CONFIG_TABLE . "
	WHERE config_name LIKE 'jab_%'";
$result = $_CLASS['core_db']->sql_query($sql);

while ($row = $_CLASS['core_db']->sql_fetchrow($result))
{
	$config_name = $row['config_name'];
	$config_value = $row['config_value'];

	$default_config[$config_name] = $config_value;
	$new[$config_name] = (isset($_POST[$config_name])) ? request_var($config_name, '') : $default_config[$config_name];

	if ($submit && !sizeof($error))
	{
		set_config($config_name, $new[$config_name]);
	}
}

if ($submit && !sizeof($error))
{
	add_log('admin', 'LOG_' . $log);
	trigger_error($message);
}



// Output the page
adm_page_header($_CLASS['core_user']->lang['IM']);

$jab_enable_yes		= ($new['jab_enable']) ? 'checked="checked"' : '';
$jab_enable_no		= (!$new['jab_enable']) ? 'checked="checked"' : '';

?>
<h1><?php echo $_CLASS['core_user']->lang['IM']; ?></h1>

<p><?php echo $_CLASS['core_user']->lang['IM_EXPLAIN']; ?></p>

<form method="post" action="<?php echo generate_link('Forums&amp;file=admin_jabber', array('admin' => true)); ?>"><table class="tablebg" width="95%" cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
		<th colspan="2"><?php echo $_CLASS['core_user']->lang['IM']; ?></th>
	</tr>
<?php

	if (sizeof($error))
	{

?>
	<tr>
		<td class="row3" colspan="2" align="center"><span style="color:red"><?php echo implode('<br />', $error); ?></td>
	</tr>
<?php

	}

?>
	<tr>
		<td class="row1" width="40%"><b><?php echo $_CLASS['core_user']->lang['JAB_ENABLE']; ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['JAB_ENABLE_EXPLAIN']; ?></span></td>
		<td class="row2"><input type="radio" name="jab_enable" value="1"<?php echo $jab_enable_yes; ?> /><?php echo $_CLASS['core_user']->lang['ENABLED']; ?>&nbsp; &nbsp;<input type="radio" name="jab_enable" value="0"<?php echo $jab_enable_no; ?> /><?php echo $_CLASS['core_user']->lang['DISABLED']; ?></td>
	</tr>
	<tr>
		<td class="row1" width="40%"><b><?php echo $_CLASS['core_user']->lang['JAB_SERVER']; ?>: </b><br /><span class="gensmall"><?php echo sprintf($_CLASS['core_user']->lang['JAB_SERVER_EXPLAIN'], '<a href="http://www.jabber.org/user/publicservers.php" target="_blank">', '</a>'); ?></span></td>
		<td class="row2"><input class="post" type="text" name="jab_host" value="<?php echo $new['jab_host']; ?>" /></td>
	</tr>
	<tr>
		<td class="row1" width="40%"><b><?php echo $_CLASS['core_user']->lang['JAB_PORT']; ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['JAB_PORT_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="jab_port" value="<?php echo $new['jab_port']; ?>" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['JAB_USERNAME']; ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['JAB_USERNAME_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="jab_username" value="<?php echo $new['jab_username']; ?>" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['JAB_PASSWORD']; ?>: </b></td>
		<td class="row2"><input class="post" type="text" name="jab_password" value="<?php echo $new['jab_password']; ?>" /></td>
	</tr>
	<tr>
		<td class="row1"><b><?php echo $_CLASS['core_user']->lang['JAB_RESOURCE']; ?>: </b><br /><span class="gensmall"><?php echo $_CLASS['core_user']->lang['JAB_RESOURCE_EXPLAIN']; ?></span></td>
		<td class="row2"><input class="post" type="text" name="jab_resource" value="<?php echo $new['jab_resource']; ?>" /></td>
	</tr>
	<tr>
		<td class="cat" colspan="2" align="center"><input class="btnmain" type="submit" name="submit" value="<?php echo $_CLASS['core_user']->lang['SUBMIT']; ?>" />&nbsp;&nbsp;<input class="btnlite" type="reset" value="<?php echo $_CLASS['core_user']->lang['RESET']; ?>" /></td>
	</tr>
</table></form>

<?php

	adm_page_footer();

?>