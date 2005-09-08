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

// This is temp
if (!defined('VIPERAL'))
{
    die;
}

global $_CLASS, $_CORE_MODULE;

$this->content .= '<div id="nav"><ul style="margin: 0px; padding: 0px; list-style-type: none; line-height: 150%;">';
$this->content .= '<li><a href="'.generate_link().'">Home</a></li>';

$result = $_CLASS['core_db']->query('SELECT module_name, module_title, module_auth FROM '.CORE_MODULES_TABLE.' WHERE module_type = '.MODULE_NORMAL.' AND module_status = ' . STATUS_ACTIVE . ' ORDER BY module_name ASC');

while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
{
// Add Auth here
	$row['module_title'] = ($row['module_title']) ? $_CLASS['core_user']->get_lang($row['module_title']) : $_CLASS['core_user']->get_lang($row['module_name']);
	
	if ($row['module_name'] == $_CORE_MODULE['module_name'] && !$_CLASS['core_display']->homepage)
	{
		$this->content .= '<li><b class="active">'.$row['module_title'].'</b></li>';
	}
	else
	{
		$this->content .= '<li><a href="'.generate_link($row['module_name']).'"> '.$row['module_title'].'</a></li>';
	}
}

$this->content .= '</ul></div>';
$_CLASS['core_db']->free_result($result);

?>