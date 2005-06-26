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

if (!defined('VIPERAL'))
{
    die;
}

global $_CLASS;

require($site_file_root.'modules/Forums/admin/links.php');

$this->content .= '
<table class="tablebg" width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td width="100%"><table width="100%" cellpadding="4" cellspacing="1" border="0">
			<tr>
				<th class="forummenu" height="25">&#0187; '.$_CLASS['core_user']->lang['RETURN_TO'].'</th>
			</tr>
			<tr>
				<td class="row1"><a class="genmed" href="'.generate_link('Forums', array('admin' => true)).'">'.$_CLASS['core_user']->lang['FORUM_INDEX'].'</a></td>
			</tr>
			<tr>
				<td class="row1"><a class="genmed" href="'.generate_link('Forums').'">'.$_CLASS['core_user']->lang['FORUM_INDEX'].'</a></td>
			</tr>';


	if (is_array($module))
	{
		ksort($module);
		foreach ($module as $cat => $action_ary)
		{
			$cat = (!empty($_CLASS['core_user']->lang[$cat . '_CAT'])) ? $_CLASS['core_user']->lang[$cat . '_CAT'] : preg_replace('#_#', ' ', $cat);


			$this->content .= '
			<tr>
				<th class="forummenu" height="25">&#0187; '. $cat.'</th>
			</tr>';

			ksort($action_ary);

			$row_class = '';
			foreach ($action_ary as $action => $link)
			{
					$action = (!empty($_CLASS['core_user']->lang[$action])) ? $_CLASS['core_user']->lang[$action] : preg_replace('#_#', ' ', $action);

					$row_class = ($row_class == 'row1') ? 'row2' : 'row1';

			$this->content .= '
			<tr>
				<td class="'.$row_class.'" onmouseover="this.className=\''.(($row_class == 'row1') ? 'row1' : 'row2').'\'" onmouseout="this.className=\''.(($row_class == 'row1') ? 'row2' : 'row1').'\'" onclick="location.href=\''.$link.'\'"><a class="genmed" href="'.$link.'">'. $action.'</a></td>
			</tr>';
			}
		}
	}

		$this->content .= '</table></td>
	</tr>
</table>';

?>
