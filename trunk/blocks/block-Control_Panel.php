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

// Fix the PM compose add To problem, if fixable !
// Clean up un-needed stuff
global $_CLASS;

if (!isset($_CLASS['core_template']->_vars['ucp_section']))
{
	$this->info = 'Contol Panel Block';
	return;
}

$this->content .= '<table class="tablebg" width="100%" cellspacing="1">
	<tr>
		<th>'.$_CLASS['core_user']->lang['OPTIONS'].'
</th>
	</tr>';

$_CLASS['core_template']->_sections['ucp_sectionloop']['name'] = 'ucp_sectionloop';
$_CLASS['core_template']->_sections['ucp_sectionloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_vars['ucp_section']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['ucp_sectionloop']['show'] = true;
$_CLASS['core_template']->_sections['ucp_sectionloop']['max'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['loop'];
$_CLASS['core_template']->_sections['ucp_sectionloop']['step'] = 1;
$_CLASS['core_template']->_sections['ucp_sectionloop']['start'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['ucp_sectionloop']['loop']-1;
if ($_CLASS['core_template']->_sections['ucp_sectionloop']['show']) {
    $_CLASS['core_template']->_sections['ucp_sectionloop']['total'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['loop'];
    if ($_CLASS['core_template']->_sections['ucp_sectionloop']['total'] == 0)
        $_CLASS['core_template']->_sections['ucp_sectionloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['ucp_sectionloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['ucp_sectionloop']['show']):

            for ($_CLASS['core_template']->_sections['ucp_sectionloop']['index'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['start'], $_CLASS['core_template']->_sections['ucp_sectionloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['ucp_sectionloop']['iteration'] <= $_CLASS['core_template']->_sections['ucp_sectionloop']['total'];
                 $_CLASS['core_template']->_sections['ucp_sectionloop']['index'] += $_CLASS['core_template']->_sections['ucp_sectionloop']['step'], $_CLASS['core_template']->_sections['ucp_sectionloop']['iteration']++):
$_CLASS['core_template']->_sections['ucp_sectionloop']['rownum'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['iteration'];
$_CLASS['core_template']->_sections['ucp_sectionloop']['index_prev'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['index'] - $_CLASS['core_template']->_sections['ucp_sectionloop']['step'];
$_CLASS['core_template']->_sections['ucp_sectionloop']['index_next'] = $_CLASS['core_template']->_sections['ucp_sectionloop']['index'] + $_CLASS['core_template']->_sections['ucp_sectionloop']['step'];
$_CLASS['core_template']->_sections['ucp_sectionloop']['first']      = ($_CLASS['core_template']->_sections['ucp_sectionloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['ucp_sectionloop']['last']       = ($_CLASS['core_template']->_sections['ucp_sectionloop']['iteration'] == $_CLASS['core_template']->_sections['ucp_sectionloop']['total']);

$this->content .= '<tr>';

if ($_CLASS['core_template']->_vars['ucp_section'][$_CLASS['core_template']->_sections['ucp_sectionloop']['index']]['S_SELECTED']):
$this->content .= '<td class="row1"><b class="phpbbnav">'.$_CLASS['core_template']->_vars['ucp_section'][$_CLASS['core_template']->_sections['ucp_sectionloop']['index']]['L_TITLE'].'
</b>

			<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';
unset($_CLASS['core_template']->_sections['ucp_subsectionloop']);

$_CLASS['core_template']->_sections['ucp_subsectionloop']['name'] = 'ucp_subsectionloop';
$_CLASS['core_template']->_sections['ucp_subsectionloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_vars['ucp_subsection']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['ucp_subsectionloop']['show'] = true;
$_CLASS['core_template']->_sections['ucp_subsectionloop']['max'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['loop'];
$_CLASS['core_template']->_sections['ucp_subsectionloop']['step'] = 1;
$_CLASS['core_template']->_sections['ucp_subsectionloop']['start'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['ucp_subsectionloop']['loop']-1;
if ($_CLASS['core_template']->_sections['ucp_subsectionloop']['show']) {
    $_CLASS['core_template']->_sections['ucp_subsectionloop']['total'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['loop'];
    if ($_CLASS['core_template']->_sections['ucp_subsectionloop']['total'] == 0)
        $_CLASS['core_template']->_sections['ucp_subsectionloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['ucp_subsectionloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['ucp_subsectionloop']['show']):

            for ($_CLASS['core_template']->_sections['ucp_subsectionloop']['index'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['start'], $_CLASS['core_template']->_sections['ucp_subsectionloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['ucp_subsectionloop']['iteration'] <= $_CLASS['core_template']->_sections['ucp_subsectionloop']['total'];
                 $_CLASS['core_template']->_sections['ucp_subsectionloop']['index'] += $_CLASS['core_template']->_sections['ucp_subsectionloop']['step'], $_CLASS['core_template']->_sections['ucp_subsectionloop']['iteration']++):
$_CLASS['core_template']->_sections['ucp_subsectionloop']['rownum'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['iteration'];
$_CLASS['core_template']->_sections['ucp_subsectionloop']['index_prev'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['index'] - $_CLASS['core_template']->_sections['ucp_subsectionloop']['step'];
$_CLASS['core_template']->_sections['ucp_subsectionloop']['index_next'] = $_CLASS['core_template']->_sections['ucp_subsectionloop']['index'] + $_CLASS['core_template']->_sections['ucp_subsectionloop']['step'];
$_CLASS['core_template']->_sections['ucp_subsectionloop']['first']      = ($_CLASS['core_template']->_sections['ucp_subsectionloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['ucp_subsectionloop']['last']       = ($_CLASS['core_template']->_sections['ucp_subsectionloop']['iteration'] == $_CLASS['core_template']->_sections['ucp_subsectionloop']['total']);

$this->content .= '<li>&#187;';
if ($_CLASS['core_template']->_vars['ucp_subsection'][$_CLASS['core_template']->_sections['ucp_subsectionloop']['index']]['S_SELECTED']):

$this->content .= ' <b>'.$_CLASS['core_template']->_vars['ucp_subsection'][$_CLASS['core_template']->_sections['ucp_subsectionloop']['index']]['L_TITLE'].'
</b>';

else: 

$this->content .= '<a href="'.$_CLASS['core_template']->_vars['ucp_subsection'][$_CLASS['core_template']->_sections['ucp_subsectionloop']['index']]['U_TITLE'].'"> '.$_CLASS['core_template']->_vars['ucp_subsection'][$_CLASS['core_template']->_sections['ucp_subsectionloop']['index']]['L_TITLE'].'
</a>';

endif;

$this->content .= '</li>';

endfor;
endif;
			$this->content .= '</ul>';

else:
		
$this->content .= '<td class="row2" nowrap="nowrap" onmouseover="this.className=\'row1\'" onmouseout="this.className=\'row2\'" onclick="location.href=\''.$_CLASS['core_template']->_vars['ucp_section'][$_CLASS['core_template']->_sections['ucp_sectionloop']['index']]['U_TITLE'].'\'"><a class="phpbbnav" href="'.$_CLASS['core_template']->_vars['ucp_section'][$_CLASS['core_template']->_sections['ucp_sectionloop']['index']]['U_TITLE'].'
">'.$_CLASS['core_template']->_vars['ucp_section'][$_CLASS['core_template']->_sections['ucp_sectionloop']['index']]['L_TITLE'].'
</a>';

endif;
		
		$this->content .= '</td>
	</tr>';
endfor;
endif;

$this->content .= '</table>

<div style="padding: 2px;"></div>';

if ($_CLASS['core_template']->_vars['S_SHOW_COLOUR_LEGEND']):

$this->content .= '<table class="tablebg" width="100%" cellspacing="1" cellpadding="0">
	<tr>
		<th colspan="2">'.$_CLASS['core_user']->lang['MESSAGE_COLOURS'].'
</th>
	</tr>';
	
unset($_CLASS['core_template']->_sections['pm_colour_infoloop']);
$_CLASS['core_template']->_sections['pm_colour_infoloop']['name'] = 'pm_colour_infoloop';
$_CLASS['core_template']->_sections['pm_colour_infoloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_vars['pm_colour_info']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['pm_colour_infoloop']['show'] = true;
$_CLASS['core_template']->_sections['pm_colour_infoloop']['max'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['loop'];
$_CLASS['core_template']->_sections['pm_colour_infoloop']['step'] = 1;
$_CLASS['core_template']->_sections['pm_colour_infoloop']['start'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['pm_colour_infoloop']['loop']-1;
if ($_CLASS['core_template']->_sections['pm_colour_infoloop']['show']) {
    $_CLASS['core_template']->_sections['pm_colour_infoloop']['total'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['loop'];
    if ($_CLASS['core_template']->_sections['pm_colour_infoloop']['total'] == 0)
        $_CLASS['core_template']->_sections['pm_colour_infoloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['pm_colour_infoloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['pm_colour_infoloop']['show']):

            for ($_CLASS['core_template']->_sections['pm_colour_infoloop']['index'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['start'], $_CLASS['core_template']->_sections['pm_colour_infoloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['pm_colour_infoloop']['iteration'] <= $_CLASS['core_template']->_sections['pm_colour_infoloop']['total'];
                 $_CLASS['core_template']->_sections['pm_colour_infoloop']['index'] += $_CLASS['core_template']->_sections['pm_colour_infoloop']['step'], $_CLASS['core_template']->_sections['pm_colour_infoloop']['iteration']++):
$_CLASS['core_template']->_sections['pm_colour_infoloop']['rownum'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['iteration'];
$_CLASS['core_template']->_sections['pm_colour_infoloop']['index_prev'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['index'] - $_CLASS['core_template']->_sections['pm_colour_infoloop']['step'];
$_CLASS['core_template']->_sections['pm_colour_infoloop']['index_next'] = $_CLASS['core_template']->_sections['pm_colour_infoloop']['index'] + $_CLASS['core_template']->_sections['pm_colour_infoloop']['step'];
$_CLASS['core_template']->_sections['pm_colour_infoloop']['first']      = ($_CLASS['core_template']->_sections['pm_colour_infoloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['pm_colour_infoloop']['last']       = ($_CLASS['core_template']->_sections['pm_colour_infoloop']['iteration'] == $_CLASS['core_template']->_sections['pm_colour_infoloop']['total']);

	$this->content .= '<tr>';
	
if (! $_CLASS['core_template']->_vars['pm_colour_info'][$_CLASS['core_template']->_sections['pm_colour_infoloop']['index']]['IMG']):

			$this->content .= '<td class="row1 '.$_CLASS['core_template']->_vars['pm_colour_info'][$_CLASS['core_template']->_sections['pm_colour_infoloop']['index']]['CLASS'].'
" width="5"><img src="images/spacer.gif" width="5" alt="'.$_CLASS['core_template']->_vars['pm_colour_info'][$_CLASS['core_template']->_sections['pm_colour_infoloop']['index']]['LANG'].'
" border="0" /></td>';

else:
			
	$this->content .= '<td class="row1" width="25" align="center">'. $_CLASS['core_template']->_vars['pm_colour_info'][$_CLASS['core_template']->_sections['pm_colour_infoloop']['index']]['IMG'].'
</td>';

endif;

		$this->content .= '<td class="row1"><span class="genmed">'.$_CLASS['core_template']->_vars['pm_colour_info'][$_CLASS['core_template']->_sections['pm_colour_infoloop']['index']]['LANG'].'
</span></td>
	</tr>';
	
endfor;
endif;

$this->content .= '</table>

<div style="padding: 2px;"></div>';
endif;

$this->content .= '<table class="tablebg" width="100%" cellspacing="1">
	<tr>
		<th>'.$_CLASS['core_user']->lang['FRIENDS'].'
</th>
	</tr>
	<tr>
		<td class="row1" align="center">
		
			<b class="genmed" style="color:green">'.$_CLASS['core_user']->lang['FRIENDS_ONLINE'].'
</b>

			<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';
			
unset($_CLASS['core_template']->_sections['friends_onlineloop']);
$_CLASS['core_template']->_sections['friends_onlineloop']['name'] = 'friends_onlineloop';
$_CLASS['core_template']->_sections['friends_onlineloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_vars['friends_online']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['friends_onlineloop']['show'] = true;
$_CLASS['core_template']->_sections['friends_onlineloop']['max'] = $_CLASS['core_template']->_sections['friends_onlineloop']['loop'];
$_CLASS['core_template']->_sections['friends_onlineloop']['step'] = 1;
$_CLASS['core_template']->_sections['friends_onlineloop']['start'] = $_CLASS['core_template']->_sections['friends_onlineloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['friends_onlineloop']['loop']-1;
if ($_CLASS['core_template']->_sections['friends_onlineloop']['show']) {
    $_CLASS['core_template']->_sections['friends_onlineloop']['total'] = $_CLASS['core_template']->_sections['friends_onlineloop']['loop'];
    if ($_CLASS['core_template']->_sections['friends_onlineloop']['total'] == 0)
        $_CLASS['core_template']->_sections['friends_onlineloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['friends_onlineloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['friends_onlineloop']['show']):

            for ($_CLASS['core_template']->_sections['friends_onlineloop']['index'] = $_CLASS['core_template']->_sections['friends_onlineloop']['start'], $_CLASS['core_template']->_sections['friends_onlineloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['friends_onlineloop']['iteration'] <= $_CLASS['core_template']->_sections['friends_onlineloop']['total'];
                 $_CLASS['core_template']->_sections['friends_onlineloop']['index'] += $_CLASS['core_template']->_sections['friends_onlineloop']['step'], $_CLASS['core_template']->_sections['friends_onlineloop']['iteration']++):
$_CLASS['core_template']->_sections['friends_onlineloop']['rownum'] = $_CLASS['core_template']->_sections['friends_onlineloop']['iteration'];
$_CLASS['core_template']->_sections['friends_onlineloop']['index_prev'] = $_CLASS['core_template']->_sections['friends_onlineloop']['index'] - $_CLASS['core_template']->_sections['friends_onlineloop']['step'];
$_CLASS['core_template']->_sections['friends_onlineloop']['index_next'] = $_CLASS['core_template']->_sections['friends_onlineloop']['index'] + $_CLASS['core_template']->_sections['friends_onlineloop']['step'];
$_CLASS['core_template']->_sections['friends_onlineloop']['first']      = ($_CLASS['core_template']->_sections['friends_onlineloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['friends_onlineloop']['last']       = ($_CLASS['core_template']->_sections['friends_onlineloop']['iteration'] == $_CLASS['core_template']->_sections['friends_onlineloop']['total']);

				$this->content .= '<li><a href="'.$_CLASS['core_template']->_vars['friends_online'][$_CLASS['core_template']->_sections['friends_onlineloop']['index']]['U_PROFILE'].'
">'.$_CLASS['core_template']->_vars['friends_online'][$_CLASS['core_template']->_sections['friends_onlineloop']['index']]['USERNAME'].'
</a></li>';

endfor;
else:
			$this->content .= '<li>'.$_CLASS['core_user']->lang['NO_FRIENDS_ONLINE'].'
</li>';
			endif;
			$this->content .= '</ul>

			<hr />

			<b class="genmed" style="color:red">'.$_CLASS['core_user']->lang['FRIENDS_OFFLINE'].'
</b>

			<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';

unset($_CLASS['core_template']->_sections['friends_offlineloop']);
$_CLASS['core_template']->_sections['friends_offlineloop']['name'] = 'friends_offlineloop';
$_CLASS['core_template']->_sections['friends_offlineloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_vars['friends_offline']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['friends_offlineloop']['show'] = true;
$_CLASS['core_template']->_sections['friends_offlineloop']['max'] = $_CLASS['core_template']->_sections['friends_offlineloop']['loop'];
$_CLASS['core_template']->_sections['friends_offlineloop']['step'] = 1;
$_CLASS['core_template']->_sections['friends_offlineloop']['start'] = $_CLASS['core_template']->_sections['friends_offlineloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['friends_offlineloop']['loop']-1;
if ($_CLASS['core_template']->_sections['friends_offlineloop']['show']) {
    $_CLASS['core_template']->_sections['friends_offlineloop']['total'] = $_CLASS['core_template']->_sections['friends_offlineloop']['loop'];
    if ($_CLASS['core_template']->_sections['friends_offlineloop']['total'] == 0)
        $_CLASS['core_template']->_sections['friends_offlineloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['friends_offlineloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['friends_offlineloop']['show']):

            for ($_CLASS['core_template']->_sections['friends_offlineloop']['index'] = $_CLASS['core_template']->_sections['friends_offlineloop']['start'], $_CLASS['core_template']->_sections['friends_offlineloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['friends_offlineloop']['iteration'] <= $_CLASS['core_template']->_sections['friends_offlineloop']['total'];
                 $_CLASS['core_template']->_sections['friends_offlineloop']['index'] += $_CLASS['core_template']->_sections['friends_offlineloop']['step'], $_CLASS['core_template']->_sections['friends_offlineloop']['iteration']++):
$_CLASS['core_template']->_sections['friends_offlineloop']['rownum'] = $_CLASS['core_template']->_sections['friends_offlineloop']['iteration'];
$_CLASS['core_template']->_sections['friends_offlineloop']['index_prev'] = $_CLASS['core_template']->_sections['friends_offlineloop']['index'] - $_CLASS['core_template']->_sections['friends_offlineloop']['step'];
$_CLASS['core_template']->_sections['friends_offlineloop']['index_next'] = $_CLASS['core_template']->_sections['friends_offlineloop']['index'] + $_CLASS['core_template']->_sections['friends_offlineloop']['step'];
$_CLASS['core_template']->_sections['friends_offlineloop']['first']      = ($_CLASS['core_template']->_sections['friends_offlineloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['friends_offlineloop']['last']       = ($_CLASS['core_template']->_sections['friends_offlineloop']['iteration'] == $_CLASS['core_template']->_sections['friends_offlineloop']['total']);

			$this->content .= '<li><a href="'.$_CLASS['core_template']->_vars['friends_offline'][$_CLASS['core_template']->_sections['friends_offlineloop']['index']]['U_PROFILE'].'
">'.$_CLASS['core_template']->_vars['friends_offline'][$_CLASS['core_template']->_sections['friends_offlineloop']['index']]['USERNAME'].'
</a>';


			endfor; else:
			$this->content .= '<li>'.$_CLASS['core_user']->lang['NO_FRIENDS_OFFLINE'].'
</li>';
			endif;
			$this->content .= '</ul>

		</td>
	</tr>
</table></form>';

?>