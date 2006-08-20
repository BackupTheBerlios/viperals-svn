<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005, 2006								||
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

if (!function_exists('eaccelerator_info'))
{

}

global $_CLASS, $sort_by;


if (isset($_REQUEST['mode']))
{
	$setting = (isset($_REQUEST['setting']) && $_REQUEST['setting']) ? true : false;

	switch ($_REQUEST['mode'])
	{
		case 'caching':
			eaccelerator_caching($setting);
		break;
	
		case 'optimizer':
			eaccelerator_optimizer($setting);
		break;
	
		case 'clear_cache':
			eaccelerator_clear();
		break;
	
		case 'clean_cache':
			eaccelerator_clean();
		break;
	
		case 'purge_cache':
			eaccelerator_purge();
		break;

		case 'keys':
		case 'scripts':
		case 'removed_scripts':
			$_CLASS['core_template']->assign('eaccelerator_info', false);

			eaccelerator_display($_REQUEST['mode'], get_variable('start', 'REQUEST', 0, 'integer'), 10);

			$_CLASS['core_template']->display('admin/eaccelerator/index.html');
			die;
		break;
	}
}

$info = eaccelerator_info();

$rename_array = array(
	'memory_size'		=> 'memorySize',
	'memory_available'	=> 'memoryAvailable',
	'memory_allocated'	=> 'memoryAllocated',
	'cached_scripts'	=> 'cachedScripts',
	'cached_keys'		=> 'cachedKeys',
	'removed_scripts'	=> 'removedScripts'
);

$info['memory_usage'] = number_format(100 * $info['memoryAllocated'] / $info['memorySize'], 2);

foreach ($rename_array as $new_name => $old_name)
{
	if (strpos($new_name, 'memory_') === 0)
	{
		$info[$new_name] = generate_size($info[$old_name]);
	}
	else
	{
		$info[$new_name] = $info[$old_name];
	}

	unset($info[$old_name]);
}

$info += array(
	'eaccelerator_info'		=> true,
	'caching_link_title'	=> (($info['cache']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE']) . ' Caching',
	'caching_link'			=> generate_link('eaccelerator&amp;mode=caching&amp;setting='.(($info['cache']) ? 0 : 1), array('admin' => true)),
	'optimizer_link_title'	=> (($info['optimizer']) ? $_CLASS['core_user']->lang['DEACTIVATE'] : $_CLASS['core_user']->lang['ACTIVATE']) . ' Optimizer',
	'optimizer_link'		=> generate_link('eaccelerator&amp;mode=optimizer&amp;setting='.(($info['optimizer']) ? 0 : 1), array('admin' => true)),
	'clear_cache_link_title'=> 'Clear cache',
	'clear_cache_link'		=> generate_link('eaccelerator&amp;mode=clear_cache', array('admin' => true)),
	'clean_cache_link_title'=> 'Clean cache',
	'clean_cache_link'		=> generate_link('eaccelerator&amp;mode=clean_cache', array('admin' => true)),
	'purge_cache_link_title'=> 'Purge cache',
	'purge_cache_link'		=> generate_link('eaccelerator&amp;mode=purge_cache', array('admin' => true)),

	'link_scripts'			=> generate_link('eaccelerator&amp;mode=scripts', array('admin' => true)),
	'link_removed_scripts'	=> generate_link('eaccelerator&amp;mode=removed_scripts', array('admin' => true)),
	'link_keys'				=> generate_link('eaccelerator&amp;mode=keys', array('admin' => true)),
);

$_CLASS['core_template']->assign_array($info);

eaccelerator_display('keys');
eaccelerator_display('scripts');
eaccelerator_display('removed_scripts');

$_CLASS['core_template']->display('admin/eaccelerator/index.html');

function eaccelerator_display($mode, $start = 0, $limit = 5, $sort = false)
{
	global $_CLASS, $sort_by;

	$total_count = 0;

	switch ($mode)
	{
		case 'scripts':
		case 'removed_scripts':
			$scripts_array = ($mode === 'scripts') ? eaccelerator_cached_scripts() : eaccelerator_removed_scripts();

			if (!empty($scripts_array))
			{
				$sort_by = 'mtime';
				usort($scripts_array, 'eaccelerator_compare');
				$total_count = count($scripts_array);

				$end = min($start + $limit, $total_count);

				for ($i = $start; $i < $end; $i++)
				{
					$scripts_array[$i]['time'] = date('Y/m/d H:i', $scripts_array[$i]['mtime']);
					$scripts_array[$i]['size'] = generate_size($scripts_array[$i]['size']);

					$_CLASS['core_template']->assign_vars_array($mode.'_array', $scripts_array[$i]);
				}
			}
		break;

		case 'keys':
			$keys_array = eaccelerator_list_keys();

			if (!empty($keys_array))
			{
				$sort_by = 'created';
				usort($keys_array, 'eaccelerator_compare');
				$total_count = count($keys_array);

				$end = min($start + $limit, $total_count);

				for ($i = $start; $i < $end; $i++)
				{
					$keys_array[$i]['created'] = date('Y/m/d H:i', $keys_array[$i]['created']);
					$keys_array[$i]['size'] = generate_size($keys_array[$i]['size']);
				
					if ($keys_array[$i]['ttl'] == 0)
					{
						$keys_array[$i]['expire_time'] = 'none';
					}
					elseif ($keys_array[$i]['ttl'] == -1)
					{
						$keys_array[$i]['expire_time'] = 'expired';
					}
					else
					{
						$keys_array[$i]['expire_time'] = date('Y/m/d H:i', $keys_array[$i]['ttl']);
					}

					$_CLASS['core_template']->assign_vars_array('keys_array', $keys_array[$i]);
				}
			}
		break;
	}

	$pagination = generate_pagination('eaccelerator&amp;mode='.$mode, $total_count, $limit, $start, true);

	$_CLASS['core_template']->assign_array(array(
		$mode.'_more'				=> ($total_count < $limit) ? false : true,
		$mode.'_pagination'			=> $pagination['formated'],
		$mode.'_pagination_array'	=> $pagination['array'],
	));
}

function eaccelerator_compare($x, $y)
{
	global $sort_by;

	if ($x[$sort_by] == $y[$sort_by])
	{
		return 0;
	}
	elseif ($x[$sort_by] < $y[$sort_by])
	{
		return 1;
	}

	return -1;
}
?>