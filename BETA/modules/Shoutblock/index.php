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

/*********************************************

  Copyright (c) 2002 by Quiecom
  http://www.Quiecom.com
  Javascript from www.Boxhead.net

  CPG-Nuke port by: DJ Maze

  Under the GNU General Public License version 2

*************************************************************/

if (!'CPG_NUKE')
{ 
	header('Location: /');
	exit;
}

global $db, $user, $prefix, $Submit, $MAIN_CFG;

//require_once('includes/nbbcode.php');

Function add_shout()
{
	if (empty($_POST['submit']))
	{
		return;
	}
	
	global $db, $_CLASS, $prefix, $Submit, $MAIN_CFG;
	
	$userid = (is_user()) ? $_CLASS['user']->data['user_id'] : '';
	$username = (is_user()) ? $_CLASS['user']->data['username'] : get_username();
	
    if(!$shout = strip_tags(get_variable('shout', 'POST', '')))
    {
		shouterror('notext'); 
    }
    
    $num = strlen($shout);

    if($num < 2)
    {
		shouterror('toshort'); 
    }
    
    if($num > $MAIN_CFG['Shoutblock']['maxlength'])
    { 
		shouterror('tolong');
    }
   
    //no more XSS....more or less...needs work..//
    if (eregi("javascript:(.*)", $shout)) 
    {
		shouterror('javascript');
    }
    
	$shout = put_string(htmlentities($shout, ENT_QUOTES));
	$username = put_string(htmlentities($username, ENT_QUOTES));
   
    // check if same message is posted last 10 minutes
    $result = $db->sql_query('SELECT shout FROM '.$prefix."_shoutblock WHERE shout='".$shout."' AND time >= '".time()."' LIMIT 1");
   
    if ($db->sql_numrows($result) > 0) {
		$db->sql_freeresult($result);
        shouterror('sameposting');
    }
    
    $db->sql_freeresult($result);


    //do ipblock test then error if on list
    /*if ($MAIN_CFG['Shoutblock']['ipblock']) {
        $result = $db->sql_query("select name from ".$prefix."_shoutblock_ipblock WHERE name = '$_SERVER[REMOTE_ADDR]' LIMIT 0,1");
        if ($db->sql_numrows($result) > 0) {
            $error ="bannedip";
         }
         $db->sql_freeresult($result);
     }

    //do name test then error if on list
    if($MAIN_CFG['Shoutblock']['nameblock']) {
        $result = $db->sql_query("select name from ".$prefix."_shoutblock_nameblock WHERE name = '$username' LIMIT 0,1");
        while ($badname = $db->sql_fetchrow($result)) {
            if($username == $badname[0]) $error = "bannedusername";
        }
        $db->sql_freeresult($result);

    }*/


	$sql = 'INSERT INTO '.$prefix.'_shoutblock ' . $db->sql_build_array('INSERT', array(
		'user_name'	=> $username,
		'user_id'	=> $userid ,
		'shout'	=> $shout,
		'time'	=> time()));
		
	$db->sql_query($sql);
	
	url_redirect($_POST['redirect']);
	exit;
}

    
function nav_shouts(){
    global $prefix, $_CLASS, $offset, $config, $bgcolor1, $bgcolor2, $MAIN_CFG;
	//$_CLASS['user']->setup('viewtopic');
	
	$per_page = 10;
	$start = get_variable('start', 'GET', '', 'integer');
	
	$sql = 'SELECT u.username, u.user_id, u.user_colour, u.user_karma, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_jabber, u.user_regdate, u.user_msnm, u.user_allow_viewemail, u.user_allow_viewonline, u.user_rank, u.user_sig, u.user_sig_bbcode_uid, u.user_sig_bbcode_bitfield, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, s.*
	FROM '.$prefix.'_shoutblock s 
	LEFT JOIN ' . USERS_TABLE . ' u  ON (u.user_id = s.user_id)
	order by time DESC LIMIT '.(($start) ? $start.',':'')." $per_page";
	$result = $_CLASS['db']->sql_query($sql);

	//$result = $_CLASS['db']->sql_query('select * from '.$prefix.'_shoutblock order by time DESC LIMIT '.(($start) ? $start.',':'')." $per_page");
	
	$bgcolor = '';
	while ($row = $_CLASS['db']->sql_fetchrow($result)) {
	
		if ($row['user_name'])
		{
			$username = $row['user_name'];
			$userlink = ($row['user_id'] && is_user()) ? getlink('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']) : false;
		} else {
			$username = $_CLASS['user']->lang['ANONYMOUS'];
			$userlink = false;
		}
		
		if ($row['user_avatar'] && $_CLASS['user']->optionget('viewavatars'))
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
	
		$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;
		
		$_CLASS['template']->assign_vars_array('shout', array(
			'USER_NAME'		=> $username,
			'USER_LINK'		=> $userlink,
			'SHOUT'			=> $row['shout'],
			'TIME'			=> ($MAIN_CFG['Shoutblock']['time']) ? $_CLASS['user']->format_date($row['time']) : '',
			'BGCOLOR'		=> $bgcolor,
			'KARMA_IMG'		=> ($row['user_id'] && $config['enable_karma']) ? $_CLASS['user']->img('karma_center', $_CLASS['user']->lang['KARMA'][$row['user_karma']], false, (int) $row['user_karma']) : '',
			'POSTER_AVATAR' => $avatar,

			//'ONLINE_IMG'		=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? '' : (($row['online']) ? $_CLASS['user']->img('btn_online', 'ONLINE') : $_CLASS['user']->img('btn_offline', 'OFFLINE')), 
			'ONLINE_IMG'		=> ($row['user_id']) ? $_CLASS['user']->img('btn_online', 'ONLINE') : '', 
	
			'U_PROFILE' 		=> ($row['user_id']) ? getlink('Members_List&amp;mode=viewprofile&amp;u='.$row['user_id']) : false,
			//'U_PM' 				=> getlink('Control_Panel&amp;i=pm&amp;mode=compose&amp;u='.$row['user_id']),
			'U_WWW' 			=> $row['user_website'],
			'U_MSN' 			=> ($row['user_msnm']) ? getlink('Members_List&amp;mode=contact&amp;action=msnm&amp;u='.$row['user_id']) : false,
			'U_YIM' 			=> ($row['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . $row['user_yim'] . '&.src=pg' : false,
			'U_JABBER'			=> ($row['user_jabber']) ? getlink('Members_List&amp;mode=contact&amp;action=jabber&amp;u='.$row['user_id']) : false,
		));
	}
	
	$_CLASS['template']->assign(array(
		//'EDIT_IMG' 		=> $_CLASS['user']->img('btn_edit', 'EDIT_POST'),
		//'DELETE_IMG' 		=> $_CLASS['user']->img('btn_delete', 'DELETE_POST'),
		'L_POSTED'			=> $_CLASS['user']->lang['POSTED'],
		'L_MESSAGE'			=> $_CLASS['user']->lang['MESSAGE'],
		'PROFILE_IMG'		=> $_CLASS['user']->img('btn_profile', 'READ_PROFILE'), 
		'WWW_IMG' 			=> $_CLASS['user']->img('btn_www', 'VISIT_WEBSITE'),
		'ICQ_IMG' 			=> $_CLASS['user']->img('btn_icq', 'ICQ'),
		'MSN_IMG' 			=> $_CLASS['user']->img('btn_msnm', 'MSNM'),
		'YIM_IMG' 			=> $_CLASS['user']->img('btn_yim', 'YIM'),
		'JABBER_IMG'		=> $_CLASS['user']->img('btn_jabber', 'JABBER'),
		)
	);
	$_CLASS['template']->display('modules/Shoutbox/index.html');	

	$result = $_CLASS['db']->sql_query('SELECT COUNT(*) AS total from '.$prefix.'_shoutblock');
	$num_items = ($row = $_CLASS['db']->sql_fetchrow($result)) ? $row['total'] : 0;
	$_CLASS['db']->sql_freeresult($result);	
	$base_url = 'Shoutblock';
	$seperator = ' | ';

	$total_pages = ceil($num_items/$per_page);

	$on_page = floor($start / $per_page) + 1;

	$page_string = ($on_page == 1) ? '<strong>1</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($on_page - 2) * $per_page)) . '">' . $_CLASS['user']->lang['PREVIOUS'] . '</a>&nbsp;&nbsp;<a href="' . getlink($base_url) . '">1</a>';

	if ($total_pages > 5)
	{
		$start_cnt = min(max(1, $on_page - 4), $total_pages - 5);
		$end_cnt = max(min($total_pages, $on_page + 4), 6);

		$page_string .= ($start_cnt > 1) ? ' ... ' : $seperator;

		for($i = $start_cnt + 1; $i < $end_cnt; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page)) . '">' . $i . '</a>';
			if ($i < $end_cnt - 1)
			{
				$page_string .= $seperator;
			}
		}

		$page_string .= ($end_cnt < $total_pages) ? ' ... ' : $seperator;
	}
	else
	{
		$page_string .= $seperator;

		for($i = 2; $i < $total_pages; $i++)
		{
			$page_string .= ($i == $on_page) ? '<strong>' . $i . '</strong>' : '<a href="' . getlink($base_url . "&amp;start=" . (($i - 1) * $per_page)) . '">' . $i . '</a>';
			if ($i < $total_pages)
			{
				$page_string .= $seperator;
			}
		}
	}

	$page_string .= ($on_page == $total_pages) ? '<strong>' . $total_pages . '</strong>' : '<a href="' . getlink($base_url . '&amp;start=' . (($total_pages - 1) * $per_page)) . '">' . $total_pages . '</a>&nbsp;&nbsp;<a href="' . getlink($base_url . "&amp;start=" . ($on_page * $per_page)) . '">' . $_CLASS['user']->lang['NEXT'] . '</a>';
	echo '<div align="center">'.$page_string.'</div>';

}

function get_username()
{

	global $MAIN_CFG;

	if (($MAIN_CFG['Shoutblock']['allow_anonymous'] != '2') || (empty($_POST['name'])))
	{
		return false;
	}
	
	$username = get_variable('name', 'POST', '');

	$leight = strlen($username);
	
	if ($leight < 2)
	{
		shouterror('uid to short');
	}
	
	if ($leight > 10)
	{
		shouterror('uid to long');
	}
	
	if (eregi("javascript:(.*)", $username))
	{
		shouterror('uid javascript');
	}
	
	return $username;

}

function all_shouts(){
    global $prefix, $user, $db, $MAIN_CFG ;
	$result = $_CLASS['db']->sql_query('select * from '.$prefix.'_shoutblock order by time DESC LIMIT '. $MAIN_CFG['Shoutblock']['number']);
    $post = 0;
    $loop = 0;
    echo '<table width="90%" border="0" align="center">';
    while ($row = $db->sql_fetchrow($result)){
    
       	$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;


        echo '<tr><td bgcolor="' . $bgcolor .'">';
        $row['comment'] = set_smilies($row['comment']);
        if ($username == "Anonymous") {
            echo '<b>' .$row['name'] .':</b> ' . $row['comment'] .'<br />';
        } else {
            echo '<a href="'.getlink('Your_Account&amp;op=userinfo&amp;username='. $row['name']).'"><b>' . $row['name'] . ':</b></a> ' . $row['comment'] . '<br />';
        }
       // $row['time'] -= date('Z');
        $row['time'] += (3600*intval($user->data['user_timezone']));
        if($MAIN_CFG['Shoutblock']['date']) { echo strftime('%d-%b-%Y ', $row['time']); } // date
        if($MAIN_CFG['Shoutblock']['time']) { echo strftime('%H:%M:%S', $row['time']); } // time
        echo "</td></tr>";
    }
    echo '</table>';
    $db->sql_freeresult($result);
    if(!$offset){ $number = 50; }
    else { $number = $loop + $offset; }
}

function  shouterror($error)
{

	OpenTable();
	echo '<div align="center">Sorry '. $error .'<br/><br/>[ <a href="javascript:history.go(-1)">Go Back</a> ]</div>';
	CloseTable();
	require('footer.php');
}

require('header.php');
	
switch (get_variable('option', 'GET', 'view'))
{
	case 'shout':
		add_shout();
		break;
	case 'all':
		all_shouts();
		break;
	case 'view':
		nav_shouts();
		break;
	default:
		nav_shouts();
}

require('footer.php');

?>