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

// -------------------------------------------------------------
//
// $Id: faq.php,v 1.27 2004/07/08 22:40:42 acydburn Exp $
//
// FILENAME  : faq.php 
// STARTED   : Mon Jul 8, 2001
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

if (!defined('VIPERAL'))
{
    die();
}

$mode = request_var('mode', '');

// Load the appropriate faq file
switch ($mode)
{
	case 'bbcode':
		$_CLASS['core_user']->add_lang('help_bbcode','Forums');
		$l_title = $_CLASS['core_user']->get_lang('BBCODE_GUIDE');
		$link = '&amp;mode=bbcode';
	break;

	default:
		$_CLASS['core_user']->add_lang('help_faq','Forums');
		$l_title = $_CLASS['core_user']->get_lang('FAQ');
		$link = '';
	break;
}

// Pull the array data from the lang pack
$j = 0;
$counter = 0;
$counter_2 = 0;
$help_block = array();
$help_block_titles = array();

foreach ($_CLASS['core_user']->lang['help'] as $help_ary)
{
	if ($help_ary[0] != '--')
	{
		$help_block[$j][$counter]['id'] = $counter_2;
		$help_block[$j][$counter]['question'] = $help_ary[0];
		$help_block[$j][$counter]['answer'] = $help_ary[1];

		$counter++;
		$counter_2++;
	}
	else
	{
		$j = ($counter != 0) ? $j + 1 : 0;

		$help_block_titles[$j] = $help_ary[1];

		$counter = 0;
	}
}

//
// Lets build a page ...
$_CLASS['core_template']->assign('L_FAQ_TITLE', $l_title);

$size = sizeof($help_block);
for ($i = 0, $size; $i < $size; $i++)
{
	if (sizeof($help_block[$i]))
	{
		$_size = sizeof($help_block[$i]);
		$faq_row_link = $faq_row = array();
		
		for ($j = 0, $_size; $j < $_size; $j++)
		{
			$faq_row[] = array(
				'FAQ_SECTION' 		=> $i,
				'FAQ_QUESTION' 		=> $help_block[$i][$j]['question'],
				'FAQ_ANSWER'		=> $help_block[$i][$j]['answer'],

				'U_FAQ_ID' 			=> $help_block[$i][$j]['id']
			);

			$faq_row_link[] = array(
				'FAQ_LINK' 			=> $help_block[$i][$j]['question'],
				'FAQ_SECTION' 		=> $i,
				'U_FAQ_LINK'		=> generate_link('Forums&amp;file=faq'.$link.'#' . $help_block[$i][$j]['id'])
			);
		}
		
		$_CLASS['core_template']->assign_vars_array('faq_block', array(
			'BLOCK_TITLE'	=> $help_block_titles[$i],
			'faq_row'		=> $faq_row
		));

		$_CLASS['core_template']->assign_vars_array('faq_block_link', array(
			'BLOCK_TITLE'		=> $help_block_titles[$i],
			'faq_row_link'		=> $faq_row_link
		));
		
	}
}

$_CLASS['core_template']->assign(array(
	'L_BACK_TO_TOP'			=> $_CLASS['core_user']->lang['BACK_TO_TOP'],
	'L_JUMP_TO'				=> $_CLASS['core_user']->lang['JUMP_TO'],
	'L_GO'					=> $_CLASS['core_user']->lang['GO'],
	'S_BACK_TO_TOP'			=> generate_link('Forums&amp;file=faq'.$link.'#Top'))
);


$_CLASS['core_template']->assign('DISPLAY_STYLESHEET_LINK', ($mode == 'bbcode'));
page_header();

if ($mode == 'bbcode')
{
	$_CLASS['core_template']->display('modules/Forums/faq_body.html');
	
	script_close();
	exit;
}
else
{
	$_CLASS['core_display']->display_head($l_title);

	make_jumpbox(generate_link('Forums&amp;file=viewforum'));
	
	$_CLASS['core_template']->display('modules/Forums/faq_body.html');
	
	$_CLASS['core_display']->display_footer();
}

?>