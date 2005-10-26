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

global $_CLASS;

$this->content .= '<div id="nav"><ul style="margin: 0px; padding: 0px; list-style-type: none; line-height: 150%;">';
$this->content .= '<li><a href="'.generate_link().'">Home</a></li>';

$result = $_CLASS['core_db']->query('SELECT page_name, page_title, page_auth FROM '.CORE_PAGES_TABLE.' WHERE page_type = '.MODULE_NORMAL.' AND page_status = ' . STATUS_ACTIVE . ' ORDER BY page_name ASC');

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
// Add Auth here
	$row['page_title'] = ($row['page_title']) ? $_CLASS['core_user']->get_lang($row['page_title']) : $_CLASS['core_user']->get_lang($row['page_name']);
	
	if ($row['page_name'] == $_CLASS['core_display']->page['page_name'] && !$_CLASS['core_display']->homepage)
	{
		$this->content .= '<li><b class="active">'.$row['page_title'].'</b></li>';
	}
	else
	{
		$this->content .= '<li><a href="'.generate_link($row['page_name']).'"> '.$row['page_title'].'</a></li>';
	}
}

$this->content .= '</ul></div>';
$_CLASS['core_db']->free_result($result);

?>