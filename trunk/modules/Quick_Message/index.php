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

if (!defined('VIPERAL'))
{
    die;
}

if (!defined('QUICK_MESSAGE_TABLE'))
{
	define('QUICK_MESSAGE_TABLE', 'test_quick_message');
}

$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_lang();

switch (get_variable('mode', 'GET', false))
{
	case 'add':
		if (empty($_POST['submit']) || $_CLASS['core_user']->is_bot)
		{
			url_redirect(generate_link($_CLASS['core_user']->data['session_url']), false);
		}

		$message = trim(get_variable('message', 'POST', false));

		if (!$message)
		{
			trigger_error('NO_MESSAGE'); 
		}

		$length = mb_strlen($message);

		if ($length > $_CORE_CONFIG['quick_message']['length_max'])
		{ 
			trigger_error('LONG_MESSAGE');
		}

		$message 	= htmlentities($message, ENT_QUOTES, 'UTF-8');

	// use limit
		$result = $_CLASS['core_db']->query('SELECT COUNT(*) as count FROM '.QUICK_MESSAGE_TABLE." WHERE message_text='".$_CLASS['core_db']->escape($message)."' AND message_time >= ".($_CLASS['core_user']->time - $_CORE_CONFIG['quick_message']['last_post_check']));
		$count = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

	// add a count check here so it admin ajustable
		if ($count['count'] > 0)
		{
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
			$user_name = get_username();
		}

		htmlentities($user_name, ENT_QUOTES, 'UTF-8');

		$sql = 'INSERT INTO '.QUICK_MESSAGE_TABLE.' ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
			'poster_name'	=> (string) $user_name,
			'poster_id'		=> (int) $user_id,
			'poster_ip'		=> (string) $_CLASS['core_user']->ip,
			'message_text'	=> (string) $message,
			'message_time'	=> (int) $_CLASS['core_user']->time,
		));

		$_CLASS['core_db']->query($sql);

		redirect(generate_link($_CLASS['core_user']->data['session_url']), false);
	break;

	case 'delete':
		global $_CORE_CONFIG, $_CLASS;

		$id = get_variable('id', 'GET', false, 'integer');

		if (!$id)
		{
			die;
		}

		$result = $_CLASS['core_db']->query_limit('SELECT message_id, poster_id, poster_name, poster_ip, message_time FROM '.QUICK_MESSAGE_TABLE.' ORDER BY message_time', 1);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if (!$row)
		{
			trigger_error('NO_MESSAGE');
		}

		$return = true;

		if ($row['message_id'] != $id)
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
		redirect(generate_link($_CLASS['core_user']->data['session_url']), false);
	break;
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
		$user_name = $row['poster_name'];
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
		'MESSAGE'		=> modify_lines($row['message_text'], '<br />'),
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

function get_username()
{

	global $_CORE_CONFIG, $site_file_root;
	
	$user_name = trim(get_variable('user_name', 'POST', ''));

	if (!$user_name)
	{
		if ($_CORE_CONFIG['quick_message']['anonymous_posting'] == '2')
		{
			return false;
			
		} else {
		
			trigger_error('NO_NAME');
		}
	}
	
	$length = mb_strlen($user_name);
	
	if ($length < 2)
	{
		trigger_error('SHORT_NAME');
	}
	
	if ($length > 10)
	{
		trigger_error('LONG_NAME');
	}
	
	require($site_file_root.'includes/forums/functions_user.php');

	if ($error = validate_username($user_name))
	{
		trigger_error($error);
	}
	return $user_name;
}

?>