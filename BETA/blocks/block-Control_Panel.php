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

// Convert this to template 
// but them it will only be for templete based themes ! make a add onto no templaye themes so it can use it..
global $_CLASS;

if (!isset($_CLASS['template']->_tpl_vars['ucp_section']))
{
	return;
}

if ($_CLASS['template']->_tpl_vars['S_SHOW_PM_BOX'] && $_CLASS['template']->_tpl_vars['S_POST_ACTION']) :

$this->content .= '<form action="'.$_CLASS['template']->_tpl_vars['S_POST_ACTION'].'" method="post" name="post"'.$_CLASS['template']->_tpl_vars['S_FORM_ENCTYPE'].'>
	<table class="tablebg" width="100%" cellspacing="1">
		<tr>
			<th>'.$_CLASS['template']->_tpl_vars['L_PM_TO'].'</th>
		</tr>
		<tr>
			<td class="row1"><b class="genmed">'. $_CLASS['template']->_tpl_vars['L_USERNAME'] .'
:</b></td>
		</tr>
		<tr>
			<td class="row2"><input class="post" type="text" name="username" size="20" maxlength="40" value="" />&nbsp;<input class="post" type="submit" name="add_to" value="'.$_CLASS['template']->_tpl_vars['L_ADD'].'" /></td>
		</tr>';


if ($_CLASS['template']->_tpl_vars['S_ALLOW_MASS_PM']) :

$this->content .= '<tr>
			<td class="row1"><b class="genmed"><'. $_CLASS['template']->_tpl_vars['L_USERNAMES'].':</b></td>
		</tr>
		<tr>
			<td class="row2"><textarea name="username_list" rows="5" cols="20"></textarea><br />
				<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">
					<li>&#187; <a href="'. $_CLASS['template']->_tpl_vars['U_SEARCH_USER'] .'
" onclick="window.open(\''. $_CLASS['template']->_tpl_vars['U_SEARCH_USER'] .'\', \'_phpbbsearch\', \'HEIGHT=500,resizable=yes,scrollbars=yes,WIDTH=740\');return false">'. $_CLASS['template']->_tpl_vars['L_FIND_USERNAME'].'
</a></li>
				</ul>
			</td>
		</tr>';
endif;

if ($_CLASS['template']->_tpl_vars['S_GROUP_OPTIONS']):
		
$this->content .= '<tr>
			<td class="row1"><b class="genmed">'. $_CLASS['template']->_tpl_vars['L_USERGROUPS'].'
:</b></td>
		</tr>
		<tr>
			<td class="row2"><select name="group_list[]" multiple="true" size="5" style="width:150px">'.$_CLASS['template']->_tpl_vars['S_GROUP_OPTIONS'].'
</select></td>
		</tr>';
endif;

if ($_CLASS['template']->_tpl_vars['S_ALLOW_MASS_PM']):
$this->content .= '<tr>
			<td class="row1"><div style="float:left">&nbsp;<input class="post" type="submit" name="add_bcc" value="'.$_CLASS['template']->_tpl_vars['L_ADD_BCC'].'
" />&nbsp;</div><div style="float:right">&nbsp;<input class="post" type="submit" name="add_to" value="'.$_CLASS['template']->_tpl_vars['L_ADD_TO'].'
" />&nbsp;</div></td>
		</tr>';
endif;
	
$this->content .= '</table>
	<div style="padding: 2px;"></div>';
	
endif;

$this->content .= '<table class="tablebg" width="100%" cellspacing="1">
	<tr>
		<th>'.$_CLASS['template']->_tpl_vars['L_OPTIONS'].'
</th>
	</tr>';
unset($_CLASS['template']->_sections['ucp_sectionloop']);

$_CLASS['template']->_sections['ucp_sectionloop']['name'] = 'ucp_sectionloop';
$_CLASS['template']->_sections['ucp_sectionloop']['loop'] = is_array($_loop=$_CLASS['template']->_tpl_vars['ucp_section']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['template']->_sections['ucp_sectionloop']['show'] = true;
$_CLASS['template']->_sections['ucp_sectionloop']['max'] = $_CLASS['template']->_sections['ucp_sectionloop']['loop'];
$_CLASS['template']->_sections['ucp_sectionloop']['step'] = 1;
$_CLASS['template']->_sections['ucp_sectionloop']['start'] = $_CLASS['template']->_sections['ucp_sectionloop']['step'] > 0 ? 0 : $_CLASS['template']->_sections['ucp_sectionloop']['loop']-1;
if ($_CLASS['template']->_sections['ucp_sectionloop']['show']) {
    $_CLASS['template']->_sections['ucp_sectionloop']['total'] = $_CLASS['template']->_sections['ucp_sectionloop']['loop'];
    if ($_CLASS['template']->_sections['ucp_sectionloop']['total'] == 0)
        $_CLASS['template']->_sections['ucp_sectionloop']['show'] = false;
} else
    $_CLASS['template']->_sections['ucp_sectionloop']['total'] = 0;
if ($_CLASS['template']->_sections['ucp_sectionloop']['show']):

            for ($_CLASS['template']->_sections['ucp_sectionloop']['index'] = $_CLASS['template']->_sections['ucp_sectionloop']['start'], $_CLASS['template']->_sections['ucp_sectionloop']['iteration'] = 1;
                 $_CLASS['template']->_sections['ucp_sectionloop']['iteration'] <= $_CLASS['template']->_sections['ucp_sectionloop']['total'];
                 $_CLASS['template']->_sections['ucp_sectionloop']['index'] += $_CLASS['template']->_sections['ucp_sectionloop']['step'], $_CLASS['template']->_sections['ucp_sectionloop']['iteration']++):
$_CLASS['template']->_sections['ucp_sectionloop']['rownum'] = $_CLASS['template']->_sections['ucp_sectionloop']['iteration'];
$_CLASS['template']->_sections['ucp_sectionloop']['index_prev'] = $_CLASS['template']->_sections['ucp_sectionloop']['index'] - $_CLASS['template']->_sections['ucp_sectionloop']['step'];
$_CLASS['template']->_sections['ucp_sectionloop']['index_next'] = $_CLASS['template']->_sections['ucp_sectionloop']['index'] + $_CLASS['template']->_sections['ucp_sectionloop']['step'];
$_CLASS['template']->_sections['ucp_sectionloop']['first']      = ($_CLASS['template']->_sections['ucp_sectionloop']['iteration'] == 1);
$_CLASS['template']->_sections['ucp_sectionloop']['last']       = ($_CLASS['template']->_sections['ucp_sectionloop']['iteration'] == $_CLASS['template']->_sections['ucp_sectionloop']['total']);

$this->content .= '<tr>';

if ($_CLASS['template']->_tpl_vars['ucp_section'][$_CLASS['template']->_sections['ucp_sectionloop']['index']]['S_SELECTED']):
$this->content .= '<td class="row1"><b class="phpbbnav">'.$_CLASS['template']->_tpl_vars['ucp_section'][$_CLASS['template']->_sections['ucp_sectionloop']['index']]['L_TITLE'].'
</b>

			<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';
unset($_CLASS['template']->_sections['ucp_subsectionloop']);

$_CLASS['template']->_sections['ucp_subsectionloop']['name'] = 'ucp_subsectionloop';
$_CLASS['template']->_sections['ucp_subsectionloop']['loop'] = is_array($_loop=$_CLASS['template']->_tpl_vars['ucp_subsection']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['template']->_sections['ucp_subsectionloop']['show'] = true;
$_CLASS['template']->_sections['ucp_subsectionloop']['max'] = $_CLASS['template']->_sections['ucp_subsectionloop']['loop'];
$_CLASS['template']->_sections['ucp_subsectionloop']['step'] = 1;
$_CLASS['template']->_sections['ucp_subsectionloop']['start'] = $_CLASS['template']->_sections['ucp_subsectionloop']['step'] > 0 ? 0 : $_CLASS['template']->_sections['ucp_subsectionloop']['loop']-1;
if ($_CLASS['template']->_sections['ucp_subsectionloop']['show']) {
    $_CLASS['template']->_sections['ucp_subsectionloop']['total'] = $_CLASS['template']->_sections['ucp_subsectionloop']['loop'];
    if ($_CLASS['template']->_sections['ucp_subsectionloop']['total'] == 0)
        $_CLASS['template']->_sections['ucp_subsectionloop']['show'] = false;
} else
    $_CLASS['template']->_sections['ucp_subsectionloop']['total'] = 0;
if ($_CLASS['template']->_sections['ucp_subsectionloop']['show']):

            for ($_CLASS['template']->_sections['ucp_subsectionloop']['index'] = $_CLASS['template']->_sections['ucp_subsectionloop']['start'], $_CLASS['template']->_sections['ucp_subsectionloop']['iteration'] = 1;
                 $_CLASS['template']->_sections['ucp_subsectionloop']['iteration'] <= $_CLASS['template']->_sections['ucp_subsectionloop']['total'];
                 $_CLASS['template']->_sections['ucp_subsectionloop']['index'] += $_CLASS['template']->_sections['ucp_subsectionloop']['step'], $_CLASS['template']->_sections['ucp_subsectionloop']['iteration']++):
$_CLASS['template']->_sections['ucp_subsectionloop']['rownum'] = $_CLASS['template']->_sections['ucp_subsectionloop']['iteration'];
$_CLASS['template']->_sections['ucp_subsectionloop']['index_prev'] = $_CLASS['template']->_sections['ucp_subsectionloop']['index'] - $_CLASS['template']->_sections['ucp_subsectionloop']['step'];
$_CLASS['template']->_sections['ucp_subsectionloop']['index_next'] = $_CLASS['template']->_sections['ucp_subsectionloop']['index'] + $_CLASS['template']->_sections['ucp_subsectionloop']['step'];
$_CLASS['template']->_sections['ucp_subsectionloop']['first']      = ($_CLASS['template']->_sections['ucp_subsectionloop']['iteration'] == 1);
$_CLASS['template']->_sections['ucp_subsectionloop']['last']       = ($_CLASS['template']->_sections['ucp_subsectionloop']['iteration'] == $_CLASS['template']->_sections['ucp_subsectionloop']['total']);

$this->content .= '<li>&#187;';
if ($_CLASS['template']->_tpl_vars['ucp_subsection'][$_CLASS['template']->_sections['ucp_subsectionloop']['index']]['S_SELECTED']):

$this->content .= '<b>'.$_CLASS['template']->_tpl_vars['ucp_subsection'][$_CLASS['template']->_sections['ucp_subsectionloop']['index']]['L_TITLE'].'
</b>';

else: 

$this->content .= '<a href="'.$_CLASS['template']->_tpl_vars['ucp_subsection'][$_CLASS['template']->_sections['ucp_subsectionloop']['index']]['U_TITLE'].'">'.$_CLASS['template']->_tpl_vars['ucp_subsection'][$_CLASS['template']->_sections['ucp_subsectionloop']['index']]['L_TITLE'].'
</a>';

endif;

$this->content .= '</li>';

endfor;
endif;
			$this->content .= '</ul>';

else:
		
$this->content .= '<td class="row2" nowrap="nowrap" onmouseover="this.className=\'row1\'" onmouseout="this.className=\'row2\'" onclick="location.href=\''.$_CLASS['template']->_tpl_vars['ucp_section'][$_CLASS['template']->_sections['ucp_sectionloop']['index']]['U_TITLE'].'\'"><a class="phpbbnav" href="'.$_CLASS['template']->_tpl_vars['ucp_section'][$_CLASS['template']->_sections['ucp_sectionloop']['index']]['U_TITLE'].'
">'.$_CLASS['template']->_tpl_vars['ucp_section'][$_CLASS['template']->_sections['ucp_sectionloop']['index']]['L_TITLE'].'
</a>';

endif;
		
		$this->content .= '</td>
	</tr>';
endfor;
endif;

$this->content .= '</table>

<div style="padding: 2px;"></div>';

if ($_CLASS['template']->_tpl_vars['S_SHOW_COLOUR_LEGEND']):

$this->content .= '<table class="tablebg" width="100%" cellspacing="1" cellpadding="0">
	<tr>
		<th colspan="2">'.$_CLASS['template']->_tpl_vars['L_MESSAGE_COLOURS'].'
</th>
	</tr>';
	
unset($_CLASS['template']->_sections['pm_colour_infoloop']);
$_CLASS['template']->_sections['pm_colour_infoloop']['name'] = 'pm_colour_infoloop';
$_CLASS['template']->_sections['pm_colour_infoloop']['loop'] = is_array($_loop=$_CLASS['template']->_tpl_vars['pm_colour_info']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['template']->_sections['pm_colour_infoloop']['show'] = true;
$_CLASS['template']->_sections['pm_colour_infoloop']['max'] = $_CLASS['template']->_sections['pm_colour_infoloop']['loop'];
$_CLASS['template']->_sections['pm_colour_infoloop']['step'] = 1;
$_CLASS['template']->_sections['pm_colour_infoloop']['start'] = $_CLASS['template']->_sections['pm_colour_infoloop']['step'] > 0 ? 0 : $_CLASS['template']->_sections['pm_colour_infoloop']['loop']-1;
if ($_CLASS['template']->_sections['pm_colour_infoloop']['show']) {
    $_CLASS['template']->_sections['pm_colour_infoloop']['total'] = $_CLASS['template']->_sections['pm_colour_infoloop']['loop'];
    if ($_CLASS['template']->_sections['pm_colour_infoloop']['total'] == 0)
        $_CLASS['template']->_sections['pm_colour_infoloop']['show'] = false;
} else
    $_CLASS['template']->_sections['pm_colour_infoloop']['total'] = 0;
if ($_CLASS['template']->_sections['pm_colour_infoloop']['show']):

            for ($_CLASS['template']->_sections['pm_colour_infoloop']['index'] = $_CLASS['template']->_sections['pm_colour_infoloop']['start'], $_CLASS['template']->_sections['pm_colour_infoloop']['iteration'] = 1;
                 $_CLASS['template']->_sections['pm_colour_infoloop']['iteration'] <= $_CLASS['template']->_sections['pm_colour_infoloop']['total'];
                 $_CLASS['template']->_sections['pm_colour_infoloop']['index'] += $_CLASS['template']->_sections['pm_colour_infoloop']['step'], $_CLASS['template']->_sections['pm_colour_infoloop']['iteration']++):
$_CLASS['template']->_sections['pm_colour_infoloop']['rownum'] = $_CLASS['template']->_sections['pm_colour_infoloop']['iteration'];
$_CLASS['template']->_sections['pm_colour_infoloop']['index_prev'] = $_CLASS['template']->_sections['pm_colour_infoloop']['index'] - $_CLASS['template']->_sections['pm_colour_infoloop']['step'];
$_CLASS['template']->_sections['pm_colour_infoloop']['index_next'] = $_CLASS['template']->_sections['pm_colour_infoloop']['index'] + $_CLASS['template']->_sections['pm_colour_infoloop']['step'];
$_CLASS['template']->_sections['pm_colour_infoloop']['first']      = ($_CLASS['template']->_sections['pm_colour_infoloop']['iteration'] == 1);
$_CLASS['template']->_sections['pm_colour_infoloop']['last']       = ($_CLASS['template']->_sections['pm_colour_infoloop']['iteration'] == $_CLASS['template']->_sections['pm_colour_infoloop']['total']);

	$this->content .= '<tr>';
	
if (! $_CLASS['template']->_tpl_vars['pm_colour_info'][$_CLASS['template']->_sections['pm_colour_infoloop']['index']]['IMG']):

			$this->content .= '<td class="row1 '.$_CLASS['template']->_tpl_vars['pm_colour_info'][$_CLASS['template']->_sections['pm_colour_infoloop']['index']]['CLASS'].'
" width="5"><img src="images/spacer.gif" width="5" alt="'.$_CLASS['template']->_tpl_vars['pm_colour_info'][$_CLASS['template']->_sections['pm_colour_infoloop']['index']]['LANG'].'
" border="0" /></td>';

else:
			
	$this->content .= '<td class="row1" width="25" align="center">'. $_CLASS['template']->_tpl_vars['pm_colour_info'][$_CLASS['template']->_sections['pm_colour_infoloop']['index']]['IMG'].'
</td>';

endif;

		$this->content .= '<td class="row1"><span class="genmed">'.$_CLASS['template']->_tpl_vars['pm_colour_info'][$_CLASS['template']->_sections['pm_colour_infoloop']['index']]['LANG'].'
</span></td>
	</tr>';
	
endfor;
endif;

$this->content .= '</table>

<div style="padding: 2px;"></div>';
endif;

$this->content .= '<table class="tablebg" width="100%" cellspacing="1">
	<tr>
		<th>'.$_CLASS['template']->_tpl_vars['L_FRIENDS'].'
</th>
	</tr>
	<tr>
		<td class="row1" align="center">
		
			<b class="genmed" style="color:green">'.$_CLASS['template']->_tpl_vars['L_FRIENDS_ONLINE'].'
</b>

			<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';
			
unset($_CLASS['template']->_sections['friends_onlineloop']);
$_CLASS['template']->_sections['friends_onlineloop']['name'] = 'friends_onlineloop';
$_CLASS['template']->_sections['friends_onlineloop']['loop'] = is_array($_loop=$_CLASS['template']->_tpl_vars['friends_online']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['template']->_sections['friends_onlineloop']['show'] = true;
$_CLASS['template']->_sections['friends_onlineloop']['max'] = $_CLASS['template']->_sections['friends_onlineloop']['loop'];
$_CLASS['template']->_sections['friends_onlineloop']['step'] = 1;
$_CLASS['template']->_sections['friends_onlineloop']['start'] = $_CLASS['template']->_sections['friends_onlineloop']['step'] > 0 ? 0 : $_CLASS['template']->_sections['friends_onlineloop']['loop']-1;
if ($_CLASS['template']->_sections['friends_onlineloop']['show']) {
    $_CLASS['template']->_sections['friends_onlineloop']['total'] = $_CLASS['template']->_sections['friends_onlineloop']['loop'];
    if ($_CLASS['template']->_sections['friends_onlineloop']['total'] == 0)
        $_CLASS['template']->_sections['friends_onlineloop']['show'] = false;
} else
    $_CLASS['template']->_sections['friends_onlineloop']['total'] = 0;
if ($_CLASS['template']->_sections['friends_onlineloop']['show']):

            for ($_CLASS['template']->_sections['friends_onlineloop']['index'] = $_CLASS['template']->_sections['friends_onlineloop']['start'], $_CLASS['template']->_sections['friends_onlineloop']['iteration'] = 1;
                 $_CLASS['template']->_sections['friends_onlineloop']['iteration'] <= $_CLASS['template']->_sections['friends_onlineloop']['total'];
                 $_CLASS['template']->_sections['friends_onlineloop']['index'] += $_CLASS['template']->_sections['friends_onlineloop']['step'], $_CLASS['template']->_sections['friends_onlineloop']['iteration']++):
$_CLASS['template']->_sections['friends_onlineloop']['rownum'] = $_CLASS['template']->_sections['friends_onlineloop']['iteration'];
$_CLASS['template']->_sections['friends_onlineloop']['index_prev'] = $_CLASS['template']->_sections['friends_onlineloop']['index'] - $_CLASS['template']->_sections['friends_onlineloop']['step'];
$_CLASS['template']->_sections['friends_onlineloop']['index_next'] = $_CLASS['template']->_sections['friends_onlineloop']['index'] + $_CLASS['template']->_sections['friends_onlineloop']['step'];
$_CLASS['template']->_sections['friends_onlineloop']['first']      = ($_CLASS['template']->_sections['friends_onlineloop']['iteration'] == 1);
$_CLASS['template']->_sections['friends_onlineloop']['last']       = ($_CLASS['template']->_sections['friends_onlineloop']['iteration'] == $_CLASS['template']->_sections['friends_onlineloop']['total']);

				$this->content .= '<li><a href="'.$_CLASS['template']->_tpl_vars['friends_online'][$_CLASS['template']->_sections['friends_onlineloop']['index']]['U_PROFILE'].'
">'.$_CLASS['template']->_tpl_vars['friends_online'][$_CLASS['template']->_sections['friends_onlineloop']['index']]['USERNAME'].'
</a>';

if ($_CLASS['template']->_tpl_vars['S_SHOW_PM_BOX']):
					$this->content .= '&nbsp;[ <input class="post" type="submit" name="add_to['.$_CLASS['template']->_tpl_vars['friends_online'][$_CLASS['template']->_sections['friends_onlineloop']['index']]['USER_ID'].'
]" value="'.$_CLASS['template']->_tpl_vars['L_ADD'].'
" /> ]';

endif;

				$this->content .= '</li>';
endfor;
else:
			$this->content .= '<li>'.$_CLASS['template']->_tpl_vars['L_NO_FRIENDS_ONLINE'].'
</li>';
			endif;
			$this->content .= '</ul>

			<hr />

			<b class="genmed" style="color:red">'.$_CLASS['template']->_tpl_vars['L_FRIENDS_OFFLINE'].'
</b>

			<ul class="phpbbnav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';

unset($_CLASS['template']->_sections['friends_offlineloop']);
$_CLASS['template']->_sections['friends_offlineloop']['name'] = 'friends_offlineloop';
$_CLASS['template']->_sections['friends_offlineloop']['loop'] = is_array($_loop=$_CLASS['template']->_tpl_vars['friends_offline']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['template']->_sections['friends_offlineloop']['show'] = true;
$_CLASS['template']->_sections['friends_offlineloop']['max'] = $_CLASS['template']->_sections['friends_offlineloop']['loop'];
$_CLASS['template']->_sections['friends_offlineloop']['step'] = 1;
$_CLASS['template']->_sections['friends_offlineloop']['start'] = $_CLASS['template']->_sections['friends_offlineloop']['step'] > 0 ? 0 : $_CLASS['template']->_sections['friends_offlineloop']['loop']-1;
if ($_CLASS['template']->_sections['friends_offlineloop']['show']) {
    $_CLASS['template']->_sections['friends_offlineloop']['total'] = $_CLASS['template']->_sections['friends_offlineloop']['loop'];
    if ($_CLASS['template']->_sections['friends_offlineloop']['total'] == 0)
        $_CLASS['template']->_sections['friends_offlineloop']['show'] = false;
} else
    $_CLASS['template']->_sections['friends_offlineloop']['total'] = 0;
if ($_CLASS['template']->_sections['friends_offlineloop']['show']):

            for ($_CLASS['template']->_sections['friends_offlineloop']['index'] = $_CLASS['template']->_sections['friends_offlineloop']['start'], $_CLASS['template']->_sections['friends_offlineloop']['iteration'] = 1;
                 $_CLASS['template']->_sections['friends_offlineloop']['iteration'] <= $_CLASS['template']->_sections['friends_offlineloop']['total'];
                 $_CLASS['template']->_sections['friends_offlineloop']['index'] += $_CLASS['template']->_sections['friends_offlineloop']['step'], $_CLASS['template']->_sections['friends_offlineloop']['iteration']++):
$_CLASS['template']->_sections['friends_offlineloop']['rownum'] = $_CLASS['template']->_sections['friends_offlineloop']['iteration'];
$_CLASS['template']->_sections['friends_offlineloop']['index_prev'] = $_CLASS['template']->_sections['friends_offlineloop']['index'] - $_CLASS['template']->_sections['friends_offlineloop']['step'];
$_CLASS['template']->_sections['friends_offlineloop']['index_next'] = $_CLASS['template']->_sections['friends_offlineloop']['index'] + $_CLASS['template']->_sections['friends_offlineloop']['step'];
$_CLASS['template']->_sections['friends_offlineloop']['first']      = ($_CLASS['template']->_sections['friends_offlineloop']['iteration'] == 1);
$_CLASS['template']->_sections['friends_offlineloop']['last']       = ($_CLASS['template']->_sections['friends_offlineloop']['iteration'] == $_CLASS['template']->_sections['friends_offlineloop']['total']);

			$this->content .= '<li><a href="'.$_CLASS['template']->_tpl_vars['friends_offline'][$_CLASS['template']->_sections['friends_offlineloop']['index']]['U_PROFILE'].'
">'.$_CLASS['template']->_tpl_vars['friends_offline'][$_CLASS['template']->_sections['friends_offlineloop']['index']]['USERNAME'].'
</a>';

if ($_CLASS['template']->_tpl_vars['S_SHOW_PM_BOX']):
					$this->content .= '&nbsp;[ <input class="post" type="submit" name="add_to['.$_CLASS['template']->_tpl_vars['friends_offline'][$_CLASS['template']->_sections['friends_offlineloop']['index']]['USER_ID'].'
]" value="'.$_CLASS['template']->_tpl_vars['L_ADD'].'
" /> ]';
				endif;
			endfor; else:
			$this->content .= '<li>'.$_CLASS['template']->_tpl_vars['L_NO_FRIENDS_OFFLINE'].'
</li>';
			endif;
			$this->content .= '</ul>

		</td>
	</tr>
</table>';

?>