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
	
	global $_CLASS, $prefix, $MAIN_CFG;
		
	$_CLASS['user']->add_lang();
	$MAIN_CFG['Shoutblock']['lastpost_check'] = 300;

	$user_id = (is_user()) ? $_CLASS['user']->data['user_id'] : '';
	$user_name = (is_user()) ? $_CLASS['user']->data['username'] : get_username();
	
    if(!$shout = get_variable('shout', 'POST', false))
    {
		trigger_error($_CLASS['user']->lang['NO_MESSAGE']); 
    }
    
    $length = strlen($shout);
    
    if($length < 2)
    {
		trigger_error($_CLASS['user']->lang['SHORT_MESSAGE']); 
    }
    
    if($length > $MAIN_CFG['Shoutblock']['maxlength']) // this shouldn't happen but still check
    { 
		trigger_error($_CLASS['user']->lang['LONG_MESSAGE']);
    }
   
	$shout 		= htmlentities($shout, ENT_QUOTES, 'UTF-8');
	$user_name 	= htmlentities($user_name, ENT_QUOTES, 'UTF-8');
   
    $result = $_CLASS['db']->sql_query('SELECT COUNT(*) as count FROM '.$prefix."_shoutblock WHERE shout='".$_CLASS['db']->sql_escape($shout)."' AND time >= '".(time() - $MAIN_CFG['Shoutblock']['lastpost_check'])."' LIMIT 1");
	$count = $_CLASS['db']->sql_fetchrow($result);
    $_CLASS['db']->sql_freeresult($result);

    if ($count['count'] > 0) // add a count check here so it admin ajustable
    {
        trigger_error(sprintf($_CLASS['user']->lang['SAME_MESSAGE'], $MAIN_CFG['Shoutblock']['lastpost_check'] / 60));
    }
    
    $_CLASS['db']->sql_freeresult($result);

	$sql = 'INSERT INTO '.$prefix.'_shoutblock ' . $_CLASS['db']->sql_build_array('INSERT', array(
		'user_name'	=> $user_name,
		'user_id'	=> $user_id ,
		'shout'		=> $shout,
		'time'		=> time(),
		'ip'		=> $_CLASS['user']->ip));
		
	$_CLASS['db']->sql_query($sql);
	
	url_redirect($_CLASS['user']->data['session_url']);
	exit;
}

    
function show_shouts($all = false)
{
    global $prefix, $_CLASS, $config, $MAIN_CFG;
	
	$_CLASS['user']->add_lang();
	$_CLASS['user']->add_img(false, 'Forums');

	$MAIN_CFG['Shoutblock']['simplemode'] = true;
	
	$start = ($all) ? '' : get_variable('start', 'GET', '', 'integer');
	
	$limit = ($all) ? '' : $MAIN_CFG['Shoutblock']['number'];
	
	if ($MAIN_CFG['Shoutblock']['simplemode'])
	{
		$sql = 'SELECT s.*, u.user_karma FROM '.$prefix.'_shoutblock s LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id) ORDER BY time DESC';

	} else {

		$sql = 'SELECT u.user_colour, u.user_karma,u.user_regdate, u.user_allow_viewonline, u.user_rank, u.user_sig, u.user_sig_bbcode_uid, u.user_sig_bbcode_bitfield, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, s.*
			FROM '.$prefix.'_shoutblock s 
			LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id)
			order by time DESC';
	}
	
	$result = $_CLASS['db']->sql_query_limit($sql, $limit, $start);
	
	$error = (!$row = $_CLASS['db']->sql_fetchrow($result)) ? $_CLASS['user']->lang['NO_POSTS'] : false;

	$_CLASS['template']->assign(array(
		'L_POSTER'			=> $_CLASS['user']->lang['POSTER'],
		'L_POSTED'			=> $_CLASS['user']->lang['POSTED'],
		'L_MESSAGE'			=> $_CLASS['user']->lang['MESSAGE'],
		'ERROR'				=> $error, 
		)
	);
	
	if ($error)
	{
		$_CLASS['display']->display_head();
		 
		if ($MAIN_CFG['Shoutblock']['simplemode'])
		{
			$_CLASS['template']->display('modules/Shoutbox/index.html');	
		}
		
		$_CLASS['display']->display_footer();
	}
	
	do {
	
		if ($row['user_name'])
		{
			$user_name = $row['user_name'];
			$userlink = ($row['user_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) : false;
		
		} else {
		
			$user_name = $_CLASS['user']->lang['ANONYMOUS'];
			$userlink = false;
		}

		if (!$MAIN_CFG['Shoutblock']['simplemode'] && $row['user_avatar'] && $_CLASS['user']->optionget('viewavatars'))
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
		
		$_CLASS['template']->assign_vars_array('shout', array(
			'USER_NAME'		=> $user_name,
			'USER_LINK'		=> $userlink,
			'SHOUT'			=> trim_text($row['shout'], '<br />'),
			'TIME'			=> ($MAIN_CFG['Shoutblock']['time']) ? $_CLASS['user']->format_date($row['time']) : '',
			'KARMA_IMG'		=> ($row['user_id'] && $config['enable_karma']) ? $_CLASS['user']->img('karma_center', $_CLASS['user']->lang['KARMA'][$row['user_karma']], false, (int) $row['user_karma']) : '',
			'POSTER_AVATAR' => $avatar,
			//'ONLINE_IMG'	=> ($row['user_id']) ? $_CLASS['user']->img('btn_online', 'ONLINE') : '', 
			'U_PROFILE' 	=> ($row['user_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u='.$row['user_id']) : false,
		));
		
	}
	while ($row = $_CLASS['db']->sql_fetchrow($result));


	$result = $_CLASS['db']->sql_query('SELECT COUNT(*) AS total from '.$prefix.'_shoutblock');
	$row = $_CLASS['db']->sql_fetchrow($result);
	$_CLASS['db']->sql_freeresult($result);	

	$_CLASS['user']->img['pagination_sep'] = ' | ';
	
	$pagination = generate_pagination('Shoutblock', $row['total'], $limit, $start, true, 'SHOUT_');
	$_CLASS['template']->assign('SHOUT_PAGINATION', $pagination);
	
	
	$_CLASS['display']->display_head();
	 
	if ($MAIN_CFG['Shoutblock']['simplemode'])
	{
		$_CLASS['template']->display('modules/Shoutbox/index.html');	
	}
	
	$_CLASS['display']->display_footer();
}

function get_username()
{

	global $MAIN_CFG, $_CLASS;
	
	$user_name = get_variable('user_name', 'POST', '');

	if (!$user_name)
	{
		if ($MAIN_CFG['Shoutblock']['allow_anonymous'] == '2')
		{
			return false;
			
		} else {
		
			trigger_error($_CLASS['user']->lang['NO_NAME']);
		}
	}
	
	$length = strlen($user_name);
	
	if ($length < 2)
	{
		trigger_error($_CLASS['user']->lang['SHORT_NAME']);
	}
	
	if ($length > 10) // this should happen but still check
	{
		trigger_error($_CLASS['user']->lang['LONG_NAME']);
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