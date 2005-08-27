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

/*
	this is temp.. until I know if I want to make this a class and how i'm going to do the styles
*/
// add spacer

$menu['blocks'][] = array('lang' => $_CLASS['core_user']->get_lang('BLOCKS'), 'link' => generate_link('blocks', array('admin' => true)));
$menu['blocks'][] = array('lang' => $_CLASS['core_user']->get_lang('MANAGE'), 'link' => generate_link('blocks', array('admin' => true)));
$menu['blocks'][] = '';
$menu['blocks'][] = array('lang' => 'Add Regular Block', 'link' => generate_link('blocks&mode=add&type=0', array('admin' => true)));
$menu['blocks'][] = array('lang' => 'Add Feed Block', 'link' => generate_link('blocks&mode=add&type=1', array('admin' => true)));
$menu['blocks'][] = array('lang' => 'Add HTML Block', 'link' => generate_link('blocks&mode=add&type=2', array('admin' => true)));


$menu['messages'][] = array('lang' => $_CLASS['core_user']->get_lang('MESSAGES'), 'link' => generate_link('messages', array('admin' => true)));
$menu['messages'][] = array('lang' => $_CLASS['core_user']->get_lang('MANAGE'), 'link' => generate_link('messages', array('admin' => true)));
$menu['messages'][] = '';
$menu['messages'][] = array('lang' => 'Add Message', 'link' => generate_link('messages&amp;mode=add', array('admin' => true)));

$menu['modules'][] = array('lang' => $_CLASS['core_user']->get_lang('MODULES'), 'link' => generate_link('modules', array('admin' => true)));
$menu['modules'][] = array('lang' => $_CLASS['core_user']->get_lang('MANAGE'), 'link' => generate_link('modules', array('admin' => true)));
$menu['modules'][] = '';

$menu['forums'][] = array('lang' => $_CLASS['core_user']->get_lang('FORUMS'), 'link' => generate_link('Forums', array('admin' => true)));
$menu['forums'][] = array('lang' => $_CLASS['core_user']->get_lang('ADMIN_INDEX'), 'link' => generate_link('Forums', array('admin' => true)));
$menu['forums'][] = '';
$menu['forums'][] = array('lang' => $_CLASS['core_user']->get_lang('MANAGE_FORUMS'), 'link' => generate_link('Forums&amp;file=admin_forums', array('admin' => true)));
$menu['forums'][] = array('lang' => $_CLASS['core_user']->get_lang('PERMISSIONS'), 'link' => generate_link('Forums&amp;file=admin_permissions&amp;mode=forum', array('admin' => true)));
$menu['forums'][] = array('lang' => $_CLASS['core_user']->get_lang('ADMINISTRATORS'), 'link' => generate_link('Forums&amp;file=admin_permissions&amp;mode=admin', array('admin' => true)));
$menu['forums'][] = array('lang' => $_CLASS['core_user']->get_lang('MODERATORS'), 'link' => generate_link('Forums&amp;file=admin_permissions&amp;mode=mod', array('admin' => true)));

$menu['system'][] = array('lang' => $_CLASS['core_user']->get_lang('SYSTEM'), 'link' => generate_link('system', array('admin' => true)));
$menu['system'][] = array('lang' => $_CLASS['core_user']->get_lang('SITE_SETTINGS'), 'link' => generate_link('system&amp;mode=site', array('admin' => true)));
$menu['system'][] = array('lang' => $_CLASS['core_user']->get_lang('SYSTEM_SETTINGS'), 'link' => generate_link('system&amp;mode=system', array('admin' => true)));
$menu['system'][] = array('lang' => $_CLASS['core_user']->get_lang('BOTS'), 'link' => generate_link('users&amp;mode=bots', array('admin' => true)));

$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('USERS'), 'link' => generate_link('users', array('admin' => true)));
$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('MANAGE'), 'link' => generate_link('users', array('admin' => true)));
$menu['users'][] = '';
$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('VIEW_DISABLED'), 'link' => generate_link('users&mode=disabled', array('admin' => true)));
$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('VIEW_UNACTIVATED'), 'link' => generate_link('users&mode=unactivated', array('admin' => true)));
$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('VIEW_BOTS'), 'link' => generate_link('users&amp;mode=bots', array('admin' => true)));
$menu['users'][] = '';
$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('ADD_USER'), 'link' => generate_link('users&mode=add_user', array('admin' => true)));
$menu['users'][] = array('lang' => $_CLASS['core_user']->get_lang('ADD_BOT'), 'link' => generate_link('users&mode=add_bot', array('admin' => true)));

function build_menu($menu)
{
	$names = array_keys($menu);
	
	$script = '<script type="text/javascript">';
	$menu_items = '';

	foreach ($names as $name)
	{
		if (isset($menu[$name][0]))
		{
			$menu_items .= '<td id="'.$name.'" nowrap="nowrap" class="row2"><a href="'.$menu[$name][0]['link'].'">'.$menu[$name][0]['lang'].'</a></td>';
			unset($menu[$name][0]);
		}

		$content[$name] = '<div id="'.$name.'_menu" style="display: none;"><table class="tablebg" border="0" cellpadding="4" cellspacing="1">';

		foreach ($menu[$name] as $item)
		{
			if (is_array($item))
			{
				$content[$name] .= '
				<tr>
					<td class="row1" onmouseover="this.className=\'row2\'" onmouseout="this.className=\'row1\'"  onclick="location.href=\''.$item['link'].'\'" nowrap="nowrap"><a href="'.$item['link'].'" title="">'.$item['lang'].'</a></td>
				</tr>
				';
			}
			else
			{
				$content[$name] .= '
				<tr>
					<td class="row2" nowrap="nowrap"></td>
				</tr>
				';
			}
		}

		$content[$name] .= '</table></div>';

		$script .= "menu_init('$name');\n";
	}

	$script .= '</script>';

	$return['content'] = implode("\n", $content)."\n$script";
	$return['menu'] = $menu_items;

	return $return;
}
?>