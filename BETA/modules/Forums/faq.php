<?php
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

if (!defined('CPG_NUKE')) {
    header('location: ../../');
    die();
}
requireOnce('includes/forums/functions.'.$phpEx);
loadclass('includes/forums/auth.'.$phpEx, 'auth');

$_CLASS['auth']->acl($_CLASS['user']->data);

$mode = request_var('mode', '');

// Load the appropriate faq file
switch ($mode)
{
	case 'bbcode':
		$l_title = $_CLASS['user']->lang['BBCODE_GUIDE'];
		$_CLASS['user']->add_lang('help_bbcode','Forums');
		$link = '&amp;mode=bbcode';
		break;

	default:
		$l_title = $_CLASS['user']->lang['FAQ'];
		$link = '';
		
		/*Add languae check
		$_CLASS['template']->caching = true;
		
		if($_CLASS['template']->is_cached('modules/Forums/faq_body.html')) {

			require('header.php');
			
			$_CLASS['template']->display('modules/Forums/faq_body.html');
			$_CLASS['template']->caching = false;

			require('footer.php');

			return;
		}
		
		$_CLASS['user']->add_lang('help_faq','Forums');
		*/

		break;
}

// Pull the array data from the lang pack
$j = 0;
$counter = 0;
$counter_2 = 0;
$help_block = array();
$help_block_titles = array();

foreach ($_CLASS['user']->lang['help'] as $help_ary)
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
$_CLASS['template']->assign(array(
	'L_FAQ_TITLE'	=> $l_title,
	'L_BACK_TO_TOP'	=> $_CLASS['user']->lang['BACK_TO_TOP'])
);

$size = sizeof($help_block);
for ($i = 0, $size; $i < $size; $i++)
{
	if (sizeof($help_block[$i]))
	{
		$_CLASS['template']->assign_vars_array('faq_block', array(
			'BLOCK_TITLE' => $help_block_titles[$i])
		);

		$_CLASS['template']->assign_vars_array('faq_block_link', array(
			'BLOCK_TITLE'		=> $help_block_titles[$i])
		);
		
		$_size = sizeof($help_block[$i]);
		for ($j = 0, $_size; $j < $_size; $j++)
		{
			$_CLASS['template']->assign_vars_array('faq_row', array(
				'FAQ_SECTION' 		=> $i,
				'FAQ_QUESTION' 		=> $help_block[$i][$j]['question'],
				'FAQ_ANSWER'		=> $help_block[$i][$j]['answer'],

				'U_FAQ_ID' 			=> $help_block[$i][$j]['id'])
			);

			$_CLASS['template']->assign_vars_array('faq_row_link', array(
				'FAQ_LINK' 			=> $help_block[$i][$j]['question'],
				'FAQ_SECTION' 		=> $i,
				'U_FAQ_LINK'		=> getlink('Forums&amp;file=faq'.$link.'#' . $help_block[$i][$j]['id']))
			);
		}
	}
}

$_CLASS['template']->assign(array(
	'L_BACK_TO_TOP'			=> $_CLASS['user']->lang['BACK_TO_TOP'],
	'L_JUMP_TO'				=> $_CLASS['user']->lang['JUMP_TO'],
	'L_GO'					=> $_CLASS['user']->lang['GO'],
	'S_BACK_TO_TOP'			=> getlink('Forums&amp;file=faq'.$link.'#Top'))
);

$_CLASS['display']->display_head($l_title);

page_header();
page_footer();

make_jumpbox(getlink('Forums&amp;file=viewforum'));

$_CLASS['template']->display('modules/Forums/faq_body.html');

$_CLASS['display']->display_footer();

?>