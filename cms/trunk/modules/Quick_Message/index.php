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
$_CLASS['core_user']->add_lang();
if ($mode = get_variable('mode', 'REQUEST', false))
{
	switch ($mode)
	{
		case 'ajax_refresh':
			require_once($site_file_root.'modules/Quick_Message/functions.php');
	
			echo qm_block_content();
	
			script_close();
		break;
	
		case 'add':
		case 'ajax_add':
			if ($_CLASS['core_user']->is_bot)
			{
				redirect(generate_link($_CLASS['core_user']->data['session_url'], array('full' => true)));
			}
	
			$message = trim(get_variable('message', 'POST', false));
	
			if (!$message)
			{
				if ($mode === 'ajax_add')
				{
					die;
				}
	
				trigger_error('NO_MESSAGE');
			}
	
			$length = mb_strlen($message);
	
			if ($length > $_CORE_CONFIG['quick_message']['length_max'])
			{ 
				if ($mode === 'ajax_add')
				{
					die;
				}
	
				trigger_error('LONG_MESSAGE');
			}
	
		// use limit
			$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM '.QUICK_MESSAGE_TABLE." WHERE message_text='".$_CLASS['core_db']->escape($message)."' AND message_time >= ".($_CLASS['core_user']->time - $_CORE_CONFIG['quick_message']['last_post_check']));
			$count = $_CLASS['core_db']->fetch_row_assoc($result);
			$_CLASS['core_db']->free_result($result);
	
		// add a count check here so it admin ajustable
			if ($count['count'] > 0)
			{
				if ($mode === 'ajax_add')
				{
					die;
				}
	
				trigger_error(sprintf($_CLASS['core_user']->lang['SAME_MESSAGE'], $_CORE_CONFIG['quick_message']['last_post_check'] / 60));
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
						if ($mode === 'ajax_add')
						{
							die;
						}
						trigger_error('NO_NAME');
					}
				}
				else
				{
					$length = mb_strlen($user_name);
	
					if ($length < 2)
					{
						if ($mode === 'ajax_add')
						{
							die;
						}
						trigger_error('SHORT_NAME');
					}
	
					if ($length > 10)
					{
						if ($mode = 'ajax_add')
						{
							die;
						}
						trigger_error('LONG_NAME');
					}
	
					require($site_file_root.'includes/functions_user.php');
				
					if ($error = validate_username($user_name))
					{
						if ($mode === 'ajax_add')
						{
							die;
						}
						trigger_error($error);
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
	
			if ($mode === 'ajax_add')
			{
				require_once($site_file_root.'modules/Quick_Message/functions.php');
	
				echo qm_block_content();
	
				script_close();
			}
	
			redirect(generate_link($_CLASS['core_user']->data['session_url'], array('full' => true)));
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
		
		default:
			script_close();
		break;
	}
}

$start = get_variable('start', 'GET', 0, 'integer');
$limit = 20;

//$sql = 'SELECT s.*, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height FROM '.$prefix.'quick_message s LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id) ORDER BY time DESC';
$result = $_CLASS['core_db']->query_limit('SELECT * FROM '.QUICK_MESSAGE_TABLE.' ORDER BY message_time DESC', $limit, $start);
$row = $_CLASS['core_db']->fetch_row_assoc($result);

if (!$row)
{
	$_CLASS['core_template']->assign_array(array(
		'Q_MESSAGE_PAGINATION'	=> generate_pagination('Quick_Message', $row['total'], $limit, $start, false, 'Q_MESSAGE_'),
		'Q_PAGE_NUMBER'			=> on_page($row['total'], $limit, $start),
		'Q_TOTAL_MESSAGES'		=> $row['total']
	));

	$_CLASS['core_display']->display(false, 'modules/Quick_Message/index.html');
	script_close();
}

$delete_link = '';

if ($row['message_time'] > ($_CLASS['core_user']->time - $_CORE_CONFIG['quick_message']['delete_time']))
{
	if (($row['poster_id'] && $row['poster_id'] == $_CLASS['core_user']->data['user_id']) || (!$row['poster_id'] && $row['poster_ip'] == $_CLASS['core_user']->ip))
	{
		$delete_link = generate_link('Quick_Message&amp;mode=delete&amp;id='.$row['message_id']);
	}
}

do
{
	if ($row['poster_name'])
	{
		$user_name = htmlentities($row['poster_name'], ENT_QUOTES, 'UTF-8');
		$userlink = ($row['poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['poster_id']) : false;
	}
	else
	{
		$user_name = $_CLASS['core_user']->lang['ANONYMOUS'];
		$userlink = false;
	}

	$avatar = false;
	/*if ($row['user_avatar'] && $_CLASS['core_user']->optionget('viewavatars'))
	{
		$avatar_img = '';
		
		switch ($row['user_avatar_type'])
		{
			case AVATAR_UPLOAD:
				$avatar_img = $config['avatar_path'] . '/';
				break;
			case AVATAR_GALLERY:
				$avatar_img = $config['avatar_gallery_path'] . '/';
				break;
		}
		
		$avatar_img .= $row['user_avatar'];
		$avatar = '<img src="' . $avatar_img . '" width="' . $row['user_avatar_width'] . '" height="' . $row['user_avatar_height'] . '" border="0" alt="" />';
		
	}*/

	if ($row['poster_id'])
	{
		$row['message'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['message_text']);
	}
	
	$_CLASS['core_template']->assign_vars_array('quick_message', array(
		'USER_NAME'		=> $user_name,
		'USER_LINK'		=> $userlink,
		'DELETE_LINK'	=> $delete_link,
		'MESSAGE'		=> modify_lines(htmlentities($row['message_text'], ENT_QUOTES, 'UTF-8'), '<br />'),
		'TIME'			=> $_CLASS['core_user']->format_date($row['message_time']),
		'POSTER_AVATAR' => $avatar,
		'U_PROFILE' 	=> ($row['poster_id']) ? generate_link('Members_List&amp;mode=viewprofile&amp;u='.$row['poster_id']) : false,
	));
	
	$delete_link = '';
}
while ($row = $_CLASS['core_db']->fetch_row_assoc($result));

$result = $_CLASS['core_db']->query('SELECT COUNT(*) AS total from '.QUICK_MESSAGE_TABLE);
$row = $_CLASS['core_db']->fetch_row_assoc($result);
$_CLASS['core_db']->free_result($result);	

$pagination = generate_pagination('Quick_Message', $row['total'], $limit, $start);

$_CLASS['core_template']->assign_array(array(
	'Q_MESSAGE_PAGINATION'			=> $pagination['formated'],
	'Q_MESSAGE_PAGINATION_ARRAY'	=> $pagination['array'],
	'Q_PAGE_NUMBER'					=> on_page($row['total'], $limit, $start),
	'Q_TOTAL_MESSAGES'				=> $row['total']
));

$_CLASS['core_display']->display(false, 'modules/Quick_Message/index.html');
	
script_close();

?>