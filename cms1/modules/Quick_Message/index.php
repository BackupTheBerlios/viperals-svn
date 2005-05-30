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
    header('location: ../../');
    die();
}
switch (get_variable('mode', 'GET', false))
{
	case 'add':
		add_message();
		break;
		
	case 'all':
		show_messages(true);
		
	case 'delete':
		delete_message();
	
	default:
		show_messages();
		break;
}

function add_message()
{
	if (empty($_POST['submit']))
	{
		return show_messages();
	}
	
	global $_CLASS, $prefix, $_CORE_CONFIG;
		
	$_CLASS['core_user']->add_lang();
	$_CORE_CONFIG['quick_message']['lastpost_check'] = 300;

	$user_id = $_CLASS['core_user']->data['user_id'];
	$user_name = (is_user()) ? $_CLASS['core_user']->data['username'] : get_username();
	
    if(!$message = get_variable('message', 'POST', false))
    {
		trigger_error($_CLASS['core_user']->lang['NO_MESSAGE']); 
    }
    
    $length = strlen($message);
    
    if($length < 2)
    {
		trigger_error($_CLASS['core_user']->lang['SHORT_MESSAGE']); 
    }
    
    if($length > $_CORE_CONFIG['quick_message']['maxlength'])
    { 
		trigger_error($_CLASS['core_user']->lang['LONG_MESSAGE']);
    }
   
	$message 	= htmlentities($message, ENT_QUOTES, 'UTF-8');
	$user_name 	= htmlentities($user_name, ENT_QUOTES, 'UTF-8');
   
    $result = $_CLASS['core_db']->sql_query('SELECT COUNT(*) as count FROM '.$prefix."_quick_message WHERE message='".$_CLASS['core_db']->sql_escape($message)."' AND time >= '".(time() - $_CORE_CONFIG['quick_message']['lastpost_check'])."' LIMIT 1");
	$count = $_CLASS['core_db']->sql_fetchrow($result);
    $_CLASS['core_db']->sql_freeresult($result);

// add a count check here so it admin ajustable
    if ($count['count'] > 0)
    {
        trigger_error(sprintf($_CLASS['core_user']->lang['SAME_MESSAGE'], $_CORE_CONFIG['quick_message']['lastpost_check'] / 60));
    }
    
    $_CLASS['core_db']->sql_freeresult($result);

	$sql = 'INSERT INTO '.$prefix.'_quick_message ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
		'user_name'	=> $user_name,
		'user_id'	=> $user_id ,
		'message'	=> $message,
		'time'		=> time(),
		'ip'		=> $_CLASS['core_user']->ip));
		
	$_CLASS['core_db']->sql_query($sql);
	
	url_redirect($_CLASS['core_user']->data['session_url']);
	exit;
}

    
function show_messages($all = false)
{
    global $prefix, $_CLASS, $config, $_CORE_CONFIG;
	
	$_CLASS['core_user']->add_lang();
	$_CLASS['core_user']->add_img(false, 'Forums');

	$_CORE_CONFIG['quick_message']['simplemode'] = true;
	
	$start = ($all) ? '' : get_variable('start', 'GET', '', 'integer');
	
	$limit = ($all) ? '' : $_CORE_CONFIG['quick_message']['number'];
	
	if ($_CORE_CONFIG['quick_message']['simplemode'])
	{
		$sql = 'SELECT s.*, u.user_karma FROM '.$prefix.'_quick_message s LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id) ORDER BY time DESC';

	} else {

		$sql = 'SELECT u.user_colour, u.user_karma,u.user_regdate, u.user_allow_viewonline, u.user_rank, u.user_sig, u.user_sig_bbcode_uid, u.user_sig_bbcode_bitfield, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, s.*
			FROM '.$prefix.'_quick_message s 
			LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id)
			order by time DESC';
	}
	
	$result = $_CLASS['core_db']->sql_query_limit($sql, $limit, $start);
	
	$error = (!$row = $_CLASS['core_db']->sql_fetchrow($result)) ? $_CLASS['core_user']->lang['NO_POSTS'] : false;

	$_CLASS['core_template']->assign(array(
		'L_POSTER'			=> $_CLASS['core_user']->lang['POSTER'],
		'L_POSTED'			=> $_CLASS['core_user']->lang['POSTED'],
		'L_MESSAGE'			=> $_CLASS['core_user']->lang['MESSAGE'],
		'L_DELETE'			=> $_CLASS['core_user']->get_lang('DELETE'),
		'ERROR'				=> $error, 
		)
	);
	
	if ($error)
	{
		$_CLASS['core_display']->display_head();
		 
		if ($_CORE_CONFIG['quick_message']['simplemode'])
		{
			$_CLASS['core_template']->display('modules/Quick_Message/index.html');	
		}
		
		$_CLASS['core_display']->display_footer();
	}
	
	do {
	
		if ($row['user_name'])
		{
			$user_name = $row['user_name'];
			$userlink = ($row['user_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) : false;
		
		} else {
		
			$user_name = $_CLASS['core_user']->lang['ANONYMOUS'];
			$userlink = false;
		}

		if (!$_CORE_CONFIG['quick_message']['simplemode'] && $row['user_avatar'] && $_CLASS['core_user']->optionget('viewavatars'))
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
			
		}
		else
		{
			$avatar = false;
		}

		if ($row['user_id'])
		{
			$row['message'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['message']);
		}
		
		$_CLASS['core_template']->assign_vars_array('quick_message', array(
			'USER_NAME'		=> $user_name,
			'USER_LINK'		=> $userlink,
			'DELETE_LINK'	=> ($row['time'] > ($_CLASS['core_user']->time - 18000)) ? generate_link('quick_message&amp;mode=delete&amp;id='.$row['id']) : '',
			'MESSAGE'		=> trim_text($row['message'], '<br />'),
			'TIME'			=> $_CLASS['core_user']->format_date($row['time']),
			'KARMA_IMG'		=> ($row['user_id'] && $config['enable_karma']) ? $_CLASS['core_user']->img('karma_center', $_CLASS['core_user']->lang['KARMA'][$row['user_karma']], false, (int) $row['user_karma']) : '',
			'POSTER_AVATAR' => $avatar,
			//'ONLINE_IMG'	=> ($row['user_id']) ? $_CLASS['core_user']->img('btn_online', 'ONLINE') : '', 
			'U_PROFILE' 	=> ($row['user_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u='.$row['user_id']) : false,
		));
		
	}
	while ($row = $_CLASS['core_db']->sql_fetchrow($result));

	$result = $_CLASS['core_db']->sql_query('SELECT COUNT(*) AS total from '.$prefix.'_quick_message');
	$row = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);	

	$_CLASS['core_user']->img['pagination_sep'] = ' | ';
	
	$_CLASS['core_template']->assign(array(
		'Q_MESSAGE_PAGINATION'	=> generate_pagination('quick_message', $row['total'], $limit, $start, false, 'Q_MESSAGE_'),
		'Q_PAGE_NUMBER'			=> on_page($row['total'], $limit, $start),
		'Q_TOTAL_MESSAGES'		=> $row['total']
	));
	
	$_CLASS['core_display']->display_head();
	 
	if ($_CORE_CONFIG['quick_message']['simplemode'])
	{
		$_CLASS['core_template']->display('modules/Quick_Message/index.html');	
	}
	
	$_CLASS['core_display']->display_footer();
}

function delete_message()
{

	global $_CORE_CONFIG, $_CLASS, $prefix;
	
	$id = (int) get_variable('id', 'GET');
	
	$result = $_CLASS['core_db']->sql_query('SELECT * FROM '.$prefix.'_quick_message WHERE id='.$id);
	$row = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);
	
	if (!$row)
	{
		trigger_error('MESSAGE_NOT_FOUND');
	}
	
	if ($row['time'] < ($_CLASS['core_user']->time - 18000))
	{
	
	}
	
	if ($row['id'] != ANONYMOUS)
	{
		if ($row['user_id'] != $_CLASS['core_user']->data['user_id'])
		{
			trigger_error('NOT_USER_MESSAGE');
		}
	}
	elseif ($_CLASS['core_user']->ip != $row['ip'])
	{
		trigger_error('NOT_USER_MESSAGE');
	}
	
	$sql = 'DELETE FROM '.$prefix.'_quick_message WHERE id='.$id;
	$_CLASS['core_db']->sql_query($sql);
	
	trigger_error('MESSAGE_DELETED');
}

function get_username()
{

	global $_CORE_CONFIG, $site_file_root;
	
	$user_name = trim(get_variable('user_name', 'POST', ''));

	if (!$user_name)
	{
		if ($_CORE_CONFIG['quick_message']['allow_anonymous'] == '2')
		{
			return false;
			
		} else {
		
			trigger_error('NO_NAME');
		}
	}
	
	$length = strlen($user_name);
	
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