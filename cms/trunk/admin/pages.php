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

if (VIPERAL != 'Admin') 
{
	die;
}

global $_CLASS;

$_CLASS['core_user']->add_lang('admin/blocks', null);

$mode = get_variable('mode', 'GET', false);

function check_type(&$type, $redirect = true)
{
	$appoved_type = array(PAGE_TEMPLATE);
	$type = (int) $type;
	
	if (!in_array($type, $appoved_type, true))
	{
		if ($redirect)
		{
			redirect(generate_link('pages', array('admin' => true, 'full' => true)));
		}
		return false;
	}

	return true;
}
		
if (isset($_REQUEST['mode']))
{
	if ($_REQUEST['mode'] === 'upload')
	{
		//$page_name = get_variable('page_name', 'POST', false);
		$page_title = get_variable('page_title', 'POST', false);
		$page_name = mb_strtolower(str_replace(' ', '_', $page_title));

		if (!empty($_FILES['file_upload']) && $page_name)
		{

			if (!is_uploaded_file($_FILES['file_upload']['tmp_name']))
			{
				trigger_error('FILE_UPLOAD_ERROR');
				die;
			}

			$destination = SITE_FILE_ROOT.'includes/templates/pages/'.$_FILES['file_upload']['name'];
			if (file_exists($destination))
			{
				trigger_error('FILE_UPLOAD_ERROR');
				die;
			}

			if (!@move_uploaded_file($_FILES['file_upload']['tmp_name'], $destination))
			{
				trigger_error('FILE_UPLOAD_ERROR');
				die;
			}

			$insert_array =  array(
				'page_name'			=> (string) $page_name,
				'page_title'		=> (string) $page_title,
				'page_type'			=> PAGE_TEMPLATE,
				'page_location'		=> 'pages/'.$_FILES['file_upload']['name'],
				'page_status'		=> STATUS_PENDING,
			);

			$_CLASS['core_db']->sql_query_build('INSERT', $insert_array, CORE_PAGES_TABLE);
			unset($insert_array, $page_name, $page_title);
		}
		else
		{
			$_CLASS['core_template']->assign_array(array(
				'ACTION_SUBMIT'			=> generate_link('pages&amp;mode=upload', array('admin' => true)),
			));
	
			$_CLASS['core_display']->display(false, 'admin/pages/upload.html');
		}
	}
	elseif ($id = get_variable('id', 'GET', false, 'int'))
	{
		switch ($_REQUEST['mode'])
		{
			case 'change':
				require_once SITE_FILE_ROOT.'admin/functions/page_functions.php';

				page_change($id);
			break;

			case 'edit':
			case 'edit_content':
				$result = $_CLASS['core_db']->query('SELECT * FROM '. CORE_PAGES_TABLE .' WHERE page_id = '.$id);
				$page = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if (!$page)
				{
					trigger_error('PAGE_NOT_FOUND');
				}

				if ($page['page_status'] != STATUS_ACTIVE && $page['page_status'] != STATUS_DISABLED)
				{
					trigger_error('PAGE_NOT_INSTALLED');
				}
	
				check_type($page['page_type']);

				if ($_REQUEST['mode'] === 'edit_content')
				{
					if (isset($_POST['submit']))
					{
						$data = array();
						$data['page_title'] = get_variable('title', 'POST', null);
	
						$_CLASS['core_db']->query('UPDATE '.CORE_PAGES_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE page_id = '.$id);
	
						unset($data);
						
						$content = get_variable('file_content', 'POST', null);
						file_put_contents(SITE_FILE_ROOT.'includes/templates/'.$page['page_location'], $content);

						break;
					}
					
					$content = file_get_contents(SITE_FILE_ROOT.'includes/templates/'.$page['page_location']);

					$_CLASS['core_template']->assign_array(array(
						'ADMIN_PAGE_ACTION'			=> generate_link('pages&amp;mode=edit_content&id='.$id, array('admin' => true)),
						'ADMIN_PAGE_NAME'			=> mb_convert_case(preg_replace('/_/', ' ', $page['page_name']), MB_CASE_TITLE),
						'ADMIN_PAGE_TITLE'			=> $page['page_title'],
						'ADMIN_PAGE_CONTENT'		=> $content,
						'ADMIN_PAGE_ERROR'			=> '',
					));

					unset($content);

					$_CLASS['core_display']->display(false, 'admin/pages/edit_content.html');

					break;
				}
				
				if (isset($_POST['submit']))
				{
					$blocks_array = get_variable('blocks_array', 'POST', array(), 'array:int');
					$active = get_variable('active', 'POST', 0, 'int');

					$data = array();
					$data['page_title'] = get_variable('title', 'POST', null);
					$data['page_status'] = ($active) ? STATUS_ACTIVE : STATUS_DISABLED;

					$data['page_blocks'] = 0;

					foreach ($blocks_array as $value)
					{
						$data['page_blocks'] |= (1 << $value);
					}

					$_CLASS['core_db']->query('UPDATE '.CORE_PAGES_TABLE.' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $data) .'  WHERE page_id = '.$id);

					unset($data, $blocks_array, $active);

					break;
				}

				settype($page['page_status'], 'int');
				settype($page['page_blocks'], 'int');

				$page_blocks_array = array(
					array(
						'value' => BLOCK_RIGHT,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_RIGHT'),
						'checked' => ($page['page_blocks'] & (1 << BLOCK_RIGHT))
					),
					array(
						'value' => BLOCK_TOP,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_TOP'),
						'checked' => ($page['page_blocks'] & (1 << BLOCK_TOP))
					),
					array(
						'value' => BLOCK_BOTTOM,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_BOTTOM'),
						'checked' => ($page['page_blocks'] & (1 << BLOCK_BOTTOM))
					),
					array(
						'value' => BLOCK_LEFT,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_LEFT'),
						'checked' => ($page['page_blocks'] & (1 << BLOCK_LEFT))
					),
					array(
						'value' => BLOCK_MESSAGE_TOP,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_MESSAGE_TOP'),
						'checked' => ($page['page_blocks'] & (1 << BLOCK_MESSAGE_TOP))
					),
					array(
						'value' => BLOCK_MESSAGE_BOTTOM,
						'name' => $_CLASS['core_user']->get_lang('BLOCK_MESSAGE_BOTTOM'),
						'checked' => ($page['page_blocks'] & (1 << BLOCK_MESSAGE_BOTTOM))
					)
				);
				
				$_CLASS['core_template']->assign_array(array(
					'ADMIN_PAGE_ACTION'			=> generate_link('pages&amp;mode=edit&id='.$id, array('admin' => true)),
					'ADMIN_PAGE_TITLE'			=> $page['page_title'],
					'ADMIN_PAGE_NAME'			=> mb_convert_case(preg_replace('/_/', ' ', $page['page_name']), MB_CASE_TITLE),
					'ADMIN_PAGE_BLOCKS_ARRAY'	=> $page_blocks_array,
					'ADMIN_PAGE_ACTIVE'			=> ($page['page_status'] == STATUS_ACTIVE) ? true : false,
					'ADMIN_PAGE_ERROR'			=> '',
				));

				$_CLASS['core_display']->display(false, 'admin/pages/edit.html');
			break;

			case 'remove':
				require_once SITE_FILE_ROOT.'admin/functions/page_functions.php';
				
				page_remove($id);
			break;

			case 'auth':
				require_once SITE_FILE_ROOT.'admin/functions/page_functions.php';

				page_auth($id);
			break;

			case 'install':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$page = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);

				if (!$page || $page['page_status'] != STATUS_PENDING)
				{
					trigger_error($page ? 'PAGE_ALREADY_INSTALLED' : 'PAGE_NOT_FOUND');
				}

				check_type($page['page_type']);

				if (display_confirmation())
				{
					$_CLASS['core_db']->query('UPDATE '.CORE_PAGES_TABLE.' set page_status = '.STATUS_DISABLED.' WHERE page_id = '.$id);
				}
			break;

			case 'uninstall':
				$result = $_CLASS['core_db']->query('SELECT page_status, page_name, page_type FROM '.CORE_PAGES_TABLE.' WHERE page_id = '.$id);
				$page = $_CLASS['core_db']->fetch_row_assoc($result);
				$_CLASS['core_db']->free_result($result);
			
				if (!$page || $page['page_status'] == STATUS_PENDING)
				{
					trigger_error($page ? 'PAGE_NOT_UNINSTALLABLE' : 'PAGE_NOT_FOUND');
				}
			
				check_type($page['page_type']);
				
				if (display_confirmation())
				{
					$_CLASS['core_db']->query('UPDATE ' . CORE_PAGES_TABLE . ' set page_status = ' . STATUS_PENDING . ' WHERE page_id = '.$id);
				}
			break;
		}
	}
}

$sql = 'SELECT * FROM '.CORE_PAGES_TABLE.'
			WHERE page_type = '. PAGE_TEMPLATE .'
				ORDER BY page_name';

$result = $_CLASS['core_db']->query($sql);

$pages = array();
$admin_auth = false;

while ($pages = $_CLASS['core_db']->fetch_row_assoc($result))
{
	settype($pages['page_status'], 'int');

	$active = ($pages['page_status'] === STATUS_ACTIVE);
	if ($pages['page_status'] === STATUS_PENDING)
	{
		$installed = false;
		$installer_link = generate_link('pages&amp;mode=install&amp;id='.$pages['page_id'], array('admin' => true));
		$remove_link = generate_link('pages&amp;mode=remove&amp;id='.$pages['page_id'], array('admin' => true));
	}
	else
	{
		$installed = true;
		$installer_link = generate_link('pages&amp;mode=uninstall&amp;id='.$pages['page_id'], array('admin' => true));
		$remove_link = '';
	}

	$_CLASS['core_template']->assign_vars_array('normal_pages', array(
			'ACTIVE'		=> ($active),
			'CHANGE'		=> ($active) ? $_CLASS['core_user']->get_lang('DEACTIVATE') : $_CLASS['core_user']->get_lang('ACTIVATE'),
			'ERROR'			=> '',
			'INSTALLED'		=> $installed,
			'TITLE'			=> ($pages['page_title']) ? $pages['page_title'] : mb_convert_case(preg_replace('/_/', ' ', $pages['page_name']), MB_CASE_TITLE),

			'LINK_ACTIVE'		=> generate_link('pages&amp;mode=change&amp;id='.$pages['page_id'], array('admin' => true)),
			'LINK_EDIT'			=> generate_link('pages&amp;mode=edit&amp;id='.$pages['page_id'], array('admin' => true)),
			'LINK_EDIT_CONTENT'	=> generate_link('pages&amp;mode=edit_content&amp;id='.$pages['page_id'], array('admin' => true)),
			'LINK_AUTH'			=> generate_link('pages&amp;mode=auth&amp;id='.$pages['page_id'], array('admin' => true)),
			'LINK_ADMIN_AUTH'	=> ($admin_auth) ? generate_link('pages&amp;mode=admin_auth&amp;id='.$pages['page_id'], array('admin' => true)) : '' ,
			'LINK_INSTALLER'	=> $installer_link,
			'LINK_REMOVE'		=> $remove_link,
	));
}

$_CLASS['core_template']->assign_array(array(
	'LINK_UPLOAD_PAGE'		=> generate_link('pages&amp;mode=upload', array('admin' => true)),
	'LINK_CREATE_PAGE'		=> generate_link('pages&amp;mode=create', array('admin' => true)),
	'LINK_GENERATE_PAGE'	=> generate_link('pages&amp;mode=generate', array('admin' => true)),
));

$_CLASS['core_display']->display(false, 'admin/pages/index.html');

?>