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

global $table_prefix;

if (!defined('QUICK_MESSAGE_TABLE'))
{
	define('QUICK_MESSAGE_TABLE', $table_prefix.'quick_message');
}

$_CLASS['core_user']->user_setup();
$mode = get_variable('mode', 'REQUEST', false);

switch ($mode)
{
	case 'ajax_refresh':
		require_once(SITE_FILE_ROOT.'modules/Quick_Message/functions.php');

		echo qm_block_content();

		script_close();
	break;

	case 'ajax_add':
		if ($_CLASS['core_user']->is_bot)
		{
			die;
		}

		$message = trim(get_variable('message', 'POST', false));

		if (!$message)
		{
			die;
		}

		$length = mb_strlen($message);

		if ($length > $_CORE_CONFIG['quick_message']['length_max'])
		{ 
			die;
		}

	// use limit
		$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM '.QUICK_MESSAGE_TABLE." WHERE message_text='".$_CLASS['core_db']->escape($message)."' AND message_time >= ".($_CLASS['core_user']->time - $_CORE_CONFIG['quick_message']['last_post_check']));
		$count = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

	// add a count check here so it admin ajustable
		if ($count['count'] > 0)
		{
			die;
		}

		$_CLASS['core_db']->free_result($result);

		if ($_CLASS['core_user']->is_user)
		{
			$user_id =  $_CLASS['core_user']->data['user_id'];
			$user_name = $_CLASS['core_user']->data['username'];
		}
		else
		{
			$user_id = 0;
			$user_name = get_variable('poster_name', 'POST', false);

			if (!$user_name)
			{
				if ($_CORE_CONFIG['quick_message']['anonymous_posting'] != '2')
				{
					die;
				}
			}
			else
			{
				$length = mb_strlen($user_name);

				if ($length < 2)
				{
					die;
				}

				if ($length > 10)
				{
					die;
				}

				require(SITE_FILE_ROOT.'includes/functions_user.php');
				$status = validate_username($user_name);

				if ($status !== true)
				{
					die;
				}
			}
		}

		$sql = 'INSERT INTO '.QUICK_MESSAGE_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'poster_name'	=> (string) $user_name,
			'poster_id'		=> (int) $user_id,
			'poster_ip'		=> (string) $_CLASS['core_user']->ip,
			'message_text'	=> (string) $message,
			'message_time'	=> (int) $_CLASS['core_user']->time,
		));

		$_CLASS['core_db']->query($sql);

		require_once(SITE_FILE_ROOT.'modules/Quick_Message/functions.php');

		echo qm_block_content();

		script_close();

	break;

	case 'delete':
		global $_CORE_CONFIG, $_CLASS;

		$id = get_variable('id', 'GET', false, 'integer');

		if (!$id)
		{
			die;
		}

		$result = $_CLASS['core_db']->query_limit('SELECT message_id, poster_id, poster_name, poster_ip, message_time FROM '.QUICK_MESSAGE_TABLE.' ORDER BY message_time DESC', 1);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$row)
		{
			trigger_error('NO_MESSAGE');
		}

		$return = true;

		if ($row['message_id'] == $id)
		{
			if ($row['message_time'] > ($_CLASS['core_user']->time - $_CORE_CONFIG['quick_message']['delete_time']))
			{
				if (($row['poster_id'] && $row['poster_id'] == $_CLASS['core_user']->data['user_id']) || (!$row['poster_id'] && $row['poster_ip'] == $_CLASS['core_user']->ip))
				{
					$return = false;
				}
			}
		}

		if ($return)
		{
			trigger_error('MESSAGE_NOT_DELETABLE');
		}

		$sql = 'DELETE FROM ' . QUICK_MESSAGE_TABLE . ' WHERE message_id = '.$id;
		$_CLASS['core_db']->query($sql);

		//trigger_error('MESSAGE_DELETED');
		redirect(($_CLASS['core_user']->data['session_url']) ? generate_link($_CLASS['core_user']->data['session_url'], array('full' => true)) : '');
	break;
}

script_close(false);

?>