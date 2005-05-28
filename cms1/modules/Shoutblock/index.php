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

function add_shout()
{
	if (empty($_POST['submit']))
	{
		return show_shouts();
	}
	
	global $_CLASS, $prefix, $_CORE_CONFIG;
		
	$_CLASS['core_user']->add_lang();
	$_CORE_CONFIG['Shoutblock']['lastpost_check'] = 300;

	$user_id = (is_user()) ? $_CLASS['core_user']->data['user_id'] : '';
	$user_name = (is_user()) ? $_CLASS['core_user']->data['username'] : get_username();
	
    if(!$shout = get_variable('shout', 'POST', false))
    {
		trigger_error($_CLASS['core_user']->lang['NO_MESSAGE']); 
    }
    
    $length = strlen($shout);
    
    if($length < 2)
    {
		trigger_error($_CLASS['core_user']->lang['SHORT_MESSAGE']); 
    }
    
    if($length > $_CORE_CONFIG['Shoutblock']['maxlength']) // this shouldn't happen but still check
    { 
		trigger_error($_CLASS['core_user']->lang['LONG_MESSAGE']);
    }
   
	$shout 		= htmlentities($shout, ENT_QUOTES, 'UTF-8');
	$user_name 	= htmlentities($user_name, ENT_QUOTES, 'UTF-8');
   
    $result = $_CLASS['core_db']->sql_query('SELECT COUNT(*) as count FROM '.$prefix."_shoutblock WHERE shout='".$_CLASS['core_db']->sql_escape($shout)."' AND time >= '".(time() - $_CORE_CONFIG['Shoutblock']['lastpost_check'])."' LIMIT 1");
	$count = $_CLASS['core_db']->sql_fetchrow($result);
    $_CLASS['core_db']->sql_freeresult($result);

    if ($count['count'] > 0) // add a count check here so it admin ajustable
    {
        trigger_error(sprintf($_CLASS['core_user']->lang['SAME_MESSAGE'], $_CORE_CONFIG['Shoutblock']['lastpost_check'] / 60));
    }
    
    $_CLASS['core_db']->sql_freeresult($result);

	$sql = 'INSERT INTO '.$prefix.'_shoutblock ' . $_CLASS['core_db']->sql_build_array('INSERT', array(
		'user_name'	=> $user_name,
		'user_id'	=> $user_id ,
		'shout'		=> $shout,
		'time'		=> time(),
		'ip'		=> $_CLASS['core_user']->ip));
		
	$_CLASS['core_db']->sql_query($sql);
	
	url_redirect($_CLASS['core_user']->data['session_url']);
	exit;
}

    
function show_shouts($all = false)
{
    global $prefix, $_CLASS, $config, $_CORE_CONFIG;
	
	$_CLASS['core_user']->add_lang();
	$_CLASS['core_user']->add_img(false, 'Forums');

	$_CORE_CONFIG['Shoutblock']['simplemode'] = true;
	
	$start = ($all) ? '' : get_variable('start', 'GET', '', 'integer');
	
	$limit = ($all) ? '' : $_CORE_CONFIG['Shoutblock']['number'];
	
	if ($_CORE_CONFIG['Shoutblock']['simplemode'])
	{
		$sql = 'SELECT s.*, u.user_karma FROM '.$prefix.'_shoutblock s LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id) ORDER BY time DESC';

	} else {

		$sql = 'SELECT u.user_colour, u.user_karma,u.user_regdate, u.user_allow_viewonline, u.user_rank, u.user_sig, u.user_sig_bbcode_uid, u.user_sig_bbcode_bitfield, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, s.*
			FROM '.$prefix.'_shoutblock s 
			LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id)
			order by time DESC';
	}
	
	$result = $_CLASS['core_db']->sql_query_limit($sql, $limit, $start);
	
	$error = (!$row = $_CLASS['core_db']->sql_fetchrow($result)) ? $_CLASS['core_user']->lang['NO_POSTS'] : false;

	$_CLASS['core_template']->assign(array(
		'L_POSTER'			=> $_CLASS['core_user']->lang['POSTER'],
		'L_POSTED'			=> $_CLASS['core_user']->lang['POSTED'],
		'L_MESSAGE'			=> $_CLASS['core_user']->lang['MESSAGE'],
		'ERROR'				=> $error, 
		)
	);
	
	if ($error)
	{
		$_CLASS['core_display']->display_head();
		 
		if ($_CORE_CONFIG['Shoutblock']['simplemode'])
		{
			$_CLASS['core_template']->display('modules/Shoutbox/index.html');	
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

		if (!$_CORE_CONFIG['Shoutblock']['simplemode'] && $row['user_avatar'] && $_CLASS['core_user']->optionget('viewavatars'))
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
		} else {
			$avatar = false;
		}

		if ($row['user_id'])
		{
			$row['shout'] = preg_replace('#\[url=([^\[]+?)\](.*?)\[/url\]#s', '<a href="$1" target="_blank">$2</a>', $row['shout']);
		}
		
		$_CLASS['core_template']->assign_vars_array('shout', array(
			'USER_NAME'		=> $user_name,
			'USER_LINK'		=> $userlink,
			'SHOUT'			=> trim_text($row['shout'], '<br />'),
			'TIME'			=> ($_CORE_CONFIG['Shoutblock']['time']) ? $_CLASS['core_user']->format_date($row['time']) : '',
			'KARMA_IMG'		=> ($row['user_id'] && $config['enable_karma']) ? $_CLASS['core_user']->img('karma_center', $_CLASS['core_user']->lang['KARMA'][$row['user_karma']], false, (int) $row['user_karma']) : '',
			'POSTER_AVATAR' => $avatar,
			//'ONLINE_IMG'	=> ($row['user_id']) ? $_CLASS['core_user']->img('btn_online', 'ONLINE') : '', 
			'U_PROFILE' 	=> ($row['user_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u='.$row['user_id']) : false,
		));
		
	}
	while ($row = $_CLASS['core_db']->sql_fetchrow($result));


	$result = $_CLASS['core_db']->sql_query('SELECT COUNT(*) AS total from '.$prefix.'_shoutblock');
	$row = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);	

	$_CLASS['core_user']->img['pagination_sep'] = ' | ';
	
	$pagination = generate_pagination('Shoutblock', $row['total'], $limit, $start, false, 'SHOUT_');
	$_CLASS['core_template']->assign('SHOUT_PAGINATION', $pagination);
	
	
	$_CLASS['core_display']->display_head();
	 
	if ($_CORE_CONFIG['Shoutblock']['simplemode'])
	{
		$_CLASS['core_template']->display('modules/Shoutbox/index.html');	
	}
	
	$_CLASS['core_display']->display_footer();
}

function get_username()
{

	global $_CORE_CONFIG, $_CLASS, $site_file_root, $phpEx;
	
	$user_name = trim(get_variable('user_name', 'POST', ''));

	if (!$user_name)
	{
		if ($_CORE_CONFIG['Shoutblock']['allow_anonymous'] == '2')
		{
			return false;
			
		} else {
		
			trigger_error($_CLASS['core_user']->lang['NO_NAME']);
		}
	}
	
	$length = strlen($user_name);
	
	if ($length < 2)
	{
		trigger_error($_CLASS['core_user']->lang['SHORT_NAME']);
	}
	
	if ($length > 10)
	{
		trigger_error($_CLASS['core_user']->lang['LONG_NAME']);
	}
	
	require($site_file_root.'includes/forums/functions_user.' . $phpEx);
	if ($error = validate_username($user_name))
	{
		trigger_error($error);
	}
	return $user_name;

}

switch (get_variable('mode', 'GET', ''))
{
	case 'shout':
		add_shout();
		break;
		
	case 'all':
		show_shouts(true);
	
	default:
		show_shouts();
		break;
}

?>