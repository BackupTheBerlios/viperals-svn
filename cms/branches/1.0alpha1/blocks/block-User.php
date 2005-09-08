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

global $_CLASS, $config, $_CORE_CONFIG;

if ($_CLASS['core_user']->is_user)
{
    $this->content = '<div style="text-align: center;">';
      
	if ($_CLASS['core_user']->data['user_avatar'])
	{
		$avatar_img = '';
		switch ($_CLASS['core_user']->data['user_avatar_type'])
		{
			case AVATAR_UPLOAD:
				$avatar_img = $config['avatar_path'] . '/';
				break;
			case AVATAR_GALLERY:
				$avatar_img = $config['avatar_gallery_path'] . '/';
				break;
		}
		
		$avatar_img .= $_CLASS['core_user']->data['user_avatar'];

		$this->content .= '<img src="' . $avatar_img . '" width="' . $_CLASS['core_user']->data['user_avatar_width'] . '" height="' . $_CLASS['core_user']->data['user_avatar_height'] . '" border="0" alt="avatar" />';
	
	}
	
	$this->content .= '<br />'.$_CLASS['core_user']->lang['WELCOME'].'<br />' . $_CLASS['core_user']->data['username'].'<br /><hr /></div><b>Your Info</b><br /><br />'
	.'<div style="margin-left: 12px;"><a href="'.generate_link('Control_Panel&amp;i=2').'">'.$_CLASS['core_user']->lang['PRIVATE_MESSAGE'].'</a><hr />'.sprintf($_CLASS['core_user']->lang['NEW_PMS'], $_CLASS['core_user']->data['user_new_privmsg']) . '<br />'
	.sprintf($_CLASS['core_user']->lang['UNREAD_PM'], $_CLASS['core_user']->data['user_unread_privmsg']) . '<br /><br />'
	//.$_CLASS['core_user']->format_date($_CLASS['core_user']->data['user_lastvisit'])
	.'<a href="'.generate_link('Control_Panel').'">Control Panel</a><br />'
	.'<a href="'.generate_link('Control_Panel&amp;mode=logout').'">'.$_CLASS['core_user']->lang['LOGOUT'].'</a><br /></div>';
}
else
{
    $this->content .= '<br /><form action="'.generate_link('Control_Panel&amp;mode=login').'" method="post"><table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>
    '.$_CLASS['core_user']->lang['USERNAME'].'</td><td align="right"><input class="post" type="text" name="username" size="10" maxlength="25" /></td></tr><tr><td>
    '.$_CLASS['core_user']->lang['PASSWORD'].'</td><td align="right"><input class="post" type="password" name="password" size="10" maxlength="20" /></td></tr><tr><td>';

    $this->content .= (($_CORE_CONFIG['user']['activation'] != USER_ACTIVATION_DISABLE) ? '(<a href="'.generate_link('Control_Panel&amp;mode=register').'">'.$_CLASS['core_user']->lang['REGISTER'].'</a>)' : '') . '</td>
		<td align="right">
			<input type="hidden" name="redirect" value="'.htmlspecialchars($_CLASS['core_user']->url).'" />
			<input class="button" type="submit" name="login" value="'.$_CLASS['core_user']->lang['LOGIN'].'" /><br/>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input name="autologin" type="checkbox"><span class="gensmall">Remmeber me</span>
		</td>
	</tr>
	</table>
</form>';
}

$who_where['guest'] = $who_where['user'] = $who_where['staff'] = false;
$online['guest'] = $online['user'] = $online['hidden'] = $online['total'] = 0;
$prev_ip = $prev_id = array();

$session_users = session_users();

foreach ($session_users as $row)
{
	if ($row['user_id'] != ANONYMOUS && !in_array($row['user_id'], $prev_ip))
  	{
		if (!$_CLASS['core_user']->is_admin && (!$row['user_allow_viewonline'] || $row['session_hidden']))
		{
			$online['hidden']++;
			continue;
		}
		else
		{
			$online['user']++;
		}
		
		if ($row['user_colour'])
		{
			$row['username'] = '<b style="color:#' . $row['user_colour'] . '">' . $row['username'] . '</b>';
		}
		
		$prev_id[] = $row['user_id'];
	}
	elseif ($row['user_id'] == ANONYMOUS && !in_array($row['session_ip'], $prev_ip))
	{
		$online['guest']++;		
	}
	else
	{
		continue;	
	}
	
	$prev_ip[] = $row['session_ip'];
	$online['total']++;

	if (!$row['session_page'])
	{
		$row['session_page'] = 'Home';
		$row['session_url'] = generate_link();
	}	
	else
	{
		$row['session_page'] = eregi_replace('_',' ', $row['session_page']);
		$row['session_url'] = generate_link($row['session_url']);
	}
	
	if ($row['user_id'] != ANONYMOUS)
	{
		$link = (($row['user_type'] & USER_BOT) ? '<a href="'.generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']).'">'.$row['username'].'</a>' : $row['username']).' &gt;';
		$who_where['user'] .= $online['user'] .': '.$link.' <a href="'.$row['session_url'].'">'.$row['session_page'].'</a><br />';
	}
	else
	{
		$who_where['guest'] .= $online['guest'] .': <a href="'.$row['session_url'].'">'.$row['session_page'].'</a><br />';
	}

}

unset($session_users);
unset($prev_id);
unset($prev_ip);

$this->content .= '
<hr /><table >
  <tr> 
	  <td style="padding: 4px;" align="left" colspan="2">
		<b>Statistics</b>
	  </td>
  </tr>
  <tr> 
	<td align="center" valign="middle" rowspan="1"><img src="images/blocks/user/stats.gif" alt="statistics" border="0" /></td>
	<td class="gensmall" align="left" width="100%">Members&nbsp;<b>'.$_CORE_CONFIG['user']['total_users'].'</b><br />Latest:&nbsp;<a href="' . generate_link('Members_List&amp;mode=viewprofile&amp;u='.$_CORE_CONFIG['user']['newest_user_id']) . '">'. $_CORE_CONFIG['user']['newest_username']. '</a>
	<br /><hr />
	</td>
  </tr>
  <tr> 
	  <td style="padding: 4px;" class="gensmall" align="left" colspan="2">
		<b>Who\'s Online</b>
	  </td>
  </tr>
  <tr> 
	<td align="center" valign="middle" rowspan="1"><img src="images/blocks/user/online.gif" alt="Who\'s Online" border="0" /></td>
	<td class="gensmall" align="left">
		Members&nbsp;'.$online['user'].'
		<br />
		Guest&nbsp;'.$online['guest'].'
		<br />
		Hidden&nbsp;'.$online['hidden'].'
		<br />
		<b>Total</b>&nbsp;'.$online['total'].'
		<br /><hr />
	</td>
  </tr>
  <tr> 
	  <td style="padding: 4px;" class="gensmall" align="left" colspan="2">
		<b>User\'s Locations</b>
	  </td>
  </tr>
  <tr> 
	<td class="gensmall" colspan="2">
	<hr /><b>Members</b><br />
'.(($who_where['user']) ? $who_where['user'] : '<em><b>&nbsp;None Online</b></em>').'<br /><b>Guests</b><br />
'.(($who_where['guest']) ? $who_where['guest'] : '<em><b>&nbsp;None Online</b></em>').'
		
	</td>
  </tr>
</table>';

?>