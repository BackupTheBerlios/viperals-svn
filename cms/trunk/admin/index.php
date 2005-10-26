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

if (VIPERAL !== 'Admin') 
{
	die;
}

if (isset($_REQUEST['user_mode']) && $_CLASS['core_auth']->admin_power('users') && display_confirmation())
{
	require_once($site_file_root.'includes/functions_user.php');
	$user_id = get_variable('id', 'REQUEST', false, 'integer');

	if ($user_id)
	{
		switch ($_REQUEST['user_mode'])
		{
			case 'remove':
				user_delete($user_id);
			break;

			case 'activate':
				user_activate($user_id);
			break;
		}
	}
}

if (is_null($cms_news = $_CLASS['core_cache']->get('cms_news')))
{
	$cms_news = array();
	
	load_class($site_file_root.'includes/core_rss.php', 'core_rss');
	
	if ($_CLASS['core_rss']->get_rss('http://www.php.net/news.rss', 3))
	{
		while ($data = $_CLASS['core_rss']->get_rss_data())
		{
			$cms_news[] = $data;
		}
	}

	$_CLASS['core_cache']->put('cms_news', $cms_news, 43200);
}

$server_name = empty($_SERVER['SERVER_NAME']) ? getenv('SERVER_NAME') : $_SERVER['SERVER_NAME'];
$server_addr = empty($_SERVER['SERVER_ADDR']) ? getenv('SERVER_ADDR') : $_SERVER['SERVER_ADDR'];
$server_software = empty($_SERVER['SERVER_SOFTWARE']) ? getenv('SERVER_SOFTWARE') : $_SERVER['SERVER_SOFTWARE'];

$_CLASS['core_template']->assign_array(array(
	'core_version'		=> 'CMS Apha 1',
	'server_host'		=> ($server_name) ? $server_name." ( $server_addr )": 'N.A. ',
	'server_software'	=> ($server_software) ? $server_software : 'N.A. ',
	'database_version'	=> $_CLASS['core_db']->version(true),
	'php_version'		=> PHP_VERSION,
));
	
$_CLASS['core_template']->assign('cms_news', $cms_news);

if ($_CLASS['core_auth']->admin_power('users'))
{
	$user_status = array(STATUS_PENDING, STATUS_DISABLED);
	$last_count = 0;

	foreach ($user_status as $status)
	{
		$limit = ($last_count) ? 10 : 20 - $last_count;

		$sql = 'SELECT COUNT(*)	FROM ' . CORE_USERS_TABLE . '
					WHERE user_type = '.USER_NORMAL.'
					AND user_status = '.$status;
		$result = $_CLASS['core_db']->query($sql);
		list($count) = $_CLASS['core_db']->fetch_row_num($result);

		$last_count = $last_count + min($count, $limit);

		if ($status === STATUS_PENDING)
		{
			$more = 'MORE_PENDING';
			$link = generate_link('users&amp;mode=unactivated', array('admin' => true));
		}
		else
		{
			$more = 'MORE_DISABLED';
			$link = generate_link('users&amp;mode=disabled', array('admin' => true));
		}
	
		$_CLASS['core_template']->assign_array(array(
			$more			=> ($count > $limit),
			'LINK_'.$more	=> $link,
		));
		
		$sql = 'SELECT user_id, username, user_reg_date
			FROM ' . CORE_USERS_TABLE . '
				WHERE user_type = '.USER_NORMAL.'
				AND user_status = '.$status;
		
		$result = $_CLASS['core_db']->query_limit($sql, $limit);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$type = ($status == STATUS_DISABLED) ? 'users_disabled' : 'users_unactivated';
		
			$_CLASS['core_template']->assign_vars_array($type, array(
					'user_id'		=> $row['user_id'],
					'user_name'		=> $row['username'],
					'registered'	=> $_CLASS['core_user']->format_time($row['user_regdate']),
					'link_profile'	=> generate_link('Members_List&amp;mode=viewprofile&amp;u=' . $row['user_id']),
					'link_activate'	=> generate_link('&amp;user_mode=activate&amp;id=' . $row['user_id'], array('admin' => true)),
					'link_remove'	=> generate_link('&amp;user_mode=remove&amp;id=' . $row['user_id'], array('admin' => true)),
					'link_remind'	=> generate_link('&amp;user_mode=remind&amp;id=' . $row['user_id'], array('admin' => true)),
					'link_details'	=> '',
			));
		}
		$_CLASS['core_db']->free_result($result);
	}
}

$_CLASS['core_template']->display('admin/index.html');

?>