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

if (!defined('VIPERAL')) {
    Header('Location: ../');
    die();
}

if (!defined('FORUMS_ADMIN'))
{
	return;
}


global $_CLASS, $phpEx, $SID;
loadclass('includes/forums/auth.'.$phpEx, 'auth');

require('admin/modules/forums/links.php');


$this->content .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td width="100%"><table width="100%" cellpadding="4" cellspacing="1" border="0">
			<tr>
				<th class="forummenu" height="25">&#0187; '.$_CLASS['user']->lang['RETURN_TO'].'</th>
			</tr>
			<tr>
				<td class="row1"><a class="genmed" href="'.getlink('Forums').'>'.$_CLASS['user']->lang['ADMIN_INDEX'].'</a></td>
			</tr>
			<tr>
				<td class="row2"><a class="genmed" href="../index'.$phpEx.$SID.'">'.$_CLASS['user']->lang['FORUM_INDEX'].'</a></td>
			</tr>';


	if (is_array($module))
	{
		@ksort($module);
		foreach ($module as $cat => $action_ary)
		{
			$cat = (!empty($_CLASS['user']->lang[$cat . '_CAT'])) ? $_CLASS['user']->lang[$cat . '_CAT'] : preg_replace('#_#', ' ', $cat);


$this->content .= '<tr>
				<th class="forummenu" height="25">&#0187; '. $cat.'</th>
			</tr>';


			@ksort($action_ary);

			$row_class = '';
			foreach ($action_ary as $action => $file)
			{
				if (!empty($file))
				{
					$action = (!empty($_CLASS['user']->lang[$action])) ? $_CLASS['user']->lang[$action] : preg_replace('#_#', ' ', $action);

					$row_class = ($row_class == 'row1') ? 'row2' : 'row1';

			$this->content .= '<tr>
				<td class="'.$row_class.'"><a class="genmed" href="'.$file.'">'. $action.'</a></td>
			</tr>';


				}
			}
		}
	}

		$this->content .= '</table></td>
	</tr>
</table>';

?>
