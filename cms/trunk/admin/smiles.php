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

global $_CLASS, $_CORE_MODULE, $_CORE_CONFIG;

$_CLASS['core_user']->add_lang('admin/system', null);

$notice = '';
$mode =	get_variable('mode', 'GET', false);
$page_title = 'SMILES';

if (!$mode || !in_array($mode, array('edit', 'delete', 'move_up', 'move_down')))
{
	$mode = '';
}

$smiley_id = get_variable('id', 'REQUEST', false, 'int');

switch ($mode)
{
	case 'edit':
		
		if (isset($_POST['submit']))
		{

			$smiley_id 			= get_variable('id', 'POST', array(), 'array:int');
			$image_width		= get_variable('width', 'POST', array(), 'array:int');
			$image_height		= get_variable('height', 'POST', array(), 'array:int');
			$image_code			= get_variable('code', 'POST', array(), 'array');
			$image_description	= get_variable('description', 'POST', array(), 'array');
			//$image_display_on_posting = (isset($_POST['display_on_posting'])) ? array_map('intval', $_POST['display_on_posting']) : array();
	
			$sql = 'SELECT * 
				FROM ' . CORE_SMILIES_TABLE .'
				WHERE smiley_id IN ('. implode(', ', $smiley_id).')';
			$result = $_CLASS['core_db']->query($sql);

			while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
			{
				if (!$image_width[$row['smiley_id']] || !$image_height[$row['smiley_id']])
				{
					$img_size = @getimagesize($_CORE_CONFIG['global']['path_smilies'] . '/' . $row['smiley_src']);
					$image_width[$row['smiley_id']] = $img_size[0];
					$image_height[$row['smiley_id']] = $img_size[1];
				}

				$img_sql = array(
					'smiley_width'			=> (int) $image_width[$row['smiley_id']],
					'smiley_height'			=> (int) $image_height[$row['smiley_id']],
					'smiley_description'	=> $image_description[$row['smiley_id']],
					'smiley_code'			=> $image_code[$row['smiley_id']]
				);

				$sql = 'UPDATE '.CORE_SMILIES_TABLE.'
					SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $img_sql) . " 
					WHERE smiley_id = " . $row['smiley_id'];
				$_CLASS['core_db']->query($sql);
			}
			$_CLASS['core_db']->free_result($result);
			
			break;
		}

		$sql = 'SELECT * 
			FROM ' . CORE_SMILIES_TABLE .'
			WHERE smiley_id ='.$smiley_id;
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$_CLASS['core_template']->assign_vars_array('smilies', array(
				'IMG'		=> $row['smiley_src'],
				'IMG_SRC'	=> $_CORE_CONFIG['global']['path_smilies'] . '/' . $row['smiley_src'],

				'CODE'		=> $row['smiley_code'],
				'EMOTION'	=> $row['smiley_description'],

				'ID'				=> $row['smiley_id'],
				'WIDTH'				=> $row['smiley_width'],
				'HEIGHT'			=> $row['smiley_height'],
				'POSTING_CHECKED'	=> (!empty($row['display_on_posting']) || $mode === 'add') ? ' checked="checked"' : '')
			);
		}
		$_CLASS['core_db']->free_result($result);

		$_CLASS['core_template']->assign_array(array(
			'S_EDIT'		=> true,
			'S_ADD'			=> false,
			'ID'			=> $smiley_id,
			'U_ACTION'		=> generate_link('smiles&amp;mode='.$mode, array('admin' => true)),
		));

		$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'admin/smiles/index.html');
	break;
	
	case 'delete':
		if (display_confirmation())
		{
			$sql = 'DELETE FROM '.CORE_SMILIES_TABLE.'
				WHERE smiley_id = '.$smiley_id;
			$_CLASS['core_db']->query($sql);
	
			$notice = $_CLASS['core_user']->lang['ICONS_DELETED'];
	
			$_CLASS['core_cache']->destroy('smiley_window');
			$_CLASS['core_cache']->destroy('smiley_inline');
		}
	break;
	
	case 'move_up':
	case 'move_down':
		$image_order = get_variable('order', 'REQUEST', 0, 'int');
		$order_total = $image_order * 2 + (($mode === 'move_up') ? -1 : 1);

		$sql = 'UPDATE '.CORE_SMILIES_TABLE."
			SET smiley_order = $order_total - smiley_order
			WHERE smiley_order IN ($image_order, " . (($mode == 'move_up') ? $image_order - 1 : $image_order + 1) . ')';
		$_CLASS['core_db']->query($sql);

		$_CLASS['core_cache']->destroy('smiley_window');
		$_CLASS['core_cache']->destroy('smiley_inline');
	break;
}

$lang = 'ICONS';

global $_CLASS, $_CORE_CONFIG;

$_CLASS['core_template']->assign_array(array(
	'S_EDIT'			=> false,
	'L_NOT_DISPLAYED'	=> $_CLASS['core_user']->lang[$lang . '_NOT_DISPLAYED'],
	'NOTICE'			=> $notice,
	'U_ACTION'			=> generate_link('smiles&amp;mode='.$mode, array('admin' => true)),
));


$sql = 'SELECT * 
	FROM '.CORE_SMILIES_TABLE.'
	ORDER BY smiley_order ASC';
$result = $_CLASS['core_db']->query($sql);

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
	$_CLASS['core_template']->assign_vars_array('smilies', array(
		'ALT_TEXT'		=> $row['smiley_code'],
		'IMG_SRC'		=> $_CORE_CONFIG['global']['path_smilies'] . '/' . $row['smiley_src'],
		'WIDTH'			=> $row['smiley_width'],
		'HEIGHT'		=> $row['smiley_height'],
		'CODE'			=> $row['smiley_code'],
		'EMOTION'		=> $row['smiley_description'],
		'U_EDIT'		=> generate_link('smiles&amp;mode=edit&amp;id=' . $row['smiley_id'], array('admin' => true)),
		'U_DELETE'		=> generate_link('smiles&amp;mode=delete&amp;id=' . $row['smiley_id'], array('admin' => true)),
		'U_MOVE_UP'		=> generate_link('smiles&amp;mode=move_up&amp;order=' . $row['smiley_order'], array('admin' => true)),
		'U_MOVE_DOWN'	=> generate_link('smiles&amp;mode=move_down&amp;order=' . $row['smiley_order'], array('admin' => true))
	));
}
$_CLASS['core_db']->free_result($result);

$_CLASS['core_display']->display($_CLASS['core_user']->get_lang($page_title), 'admin/smiles/index.html');

?>