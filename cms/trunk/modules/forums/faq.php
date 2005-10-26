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

if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'bbcode')
{
	$_CLASS['core_user']->add_lang('help_bbcode','Forums');
	$l_title = $_CLASS['core_user']->get_lang('BBCODE_GUIDE');
	$link = '&amp;mode=bbcode';
	$mode = 'bbcode';
}
else
{
	$_CLASS['core_user']->add_lang('help_faq','Forums');
	$l_title = $_CLASS['core_user']->get_lang('FAQ');
	$link = $mode = '';
}

$_CLASS['core_template']->assign_array(array(
	'L_FAQ_TITLE'	=> $l_title,
	'S_BACK_TO_TOP'	=> generate_link('Forums&amp;file=faq'.$link.'#Top')
));

$id = 0;
$next_title = false;

$size = count($_CLASS['core_user']->lang['help']);

for ($i = 0; $i < $size; $i++)
{
	if ($_CLASS['core_user']->lang['help'][$i][0] == '--')
	{
		if ($next_title !== false)
		{
			$_CLASS['core_template']->assign_vars_array('faq_block', array(
				'BLOCK_TITLE'	=> $_CLASS['core_user']->lang['help'][$next_title][1],
				'faq_row'		=> $faq_row
			));
	
			$_CLASS['core_template']->assign_vars_array('faq_block_link', array(
				'BLOCK_TITLE'		=> $_CLASS['core_user']->lang['help'][$next_title][1],
				'faq_row_link'		=> $faq_row_link
			));
		}
		
		$next_title = $i;
		$faq_row = $faq_row_link = array();
	}
	else
	{
		$id ++;

		$faq_row[] = array(
			'FAQ_QUESTION' 		=> $_CLASS['core_user']->lang['help'][$i][0],
			'FAQ_ANSWER'		=> $_CLASS['core_user']->lang['help'][$i][1],

			'U_FAQ_ID' 			=> $id
		);

		$faq_row_link[] = array(
			'FAQ_LINK' 			=> $_CLASS['core_user']->lang['help'][$i][0],
			'U_FAQ_LINK'		=> generate_link('Forums&amp;file=faq'.$link.'#' . $id)
		);
	}
}

if ($next_title !== false)
{
	$_CLASS['core_template']->assign_vars_array('faq_block', array(
		'BLOCK_TITLE'	=> $_CLASS['core_user']->lang['help'][$next_title][1],
		'faq_row'		=> $faq_row
	));
	
	$_CLASS['core_template']->assign_vars_array('faq_block_link', array(
		'BLOCK_TITLE'		=> $_CLASS['core_user']->lang['help'][$next_title][1],
		'faq_row_link'		=> $faq_row_link
	));
}

$_CLASS['core_template']->assign('DISPLAY_STYLESHEET_LINK', ($mode == 'bbcode'));

page_header();

$_CLASS['core_template']->display('modules/Forums/faq_body.html');

?>