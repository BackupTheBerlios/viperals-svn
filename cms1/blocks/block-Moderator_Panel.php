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

if (!isset($_CLASS['core_template']->_tpl_vars['mcp_section']))
{
	return;
}

$this->content .= '<table class="tablebg" width="100%" cellspacing="1">
				<tr>
					<th>'.$_CLASS['core_template']->_tpl_vars['L_OPTIONS'].'
</th>
				</tr>';
unset($_CLASS['core_template']->_sections['mcp_sectionloop']);
$_CLASS['core_template']->_sections['mcp_sectionloop']['name'] = 'mcp_sectionloop';
$_CLASS['core_template']->_sections['mcp_sectionloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_tpl_vars['mcp_section']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['mcp_sectionloop']['show'] = true;
$_CLASS['core_template']->_sections['mcp_sectionloop']['max'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['loop'];
$_CLASS['core_template']->_sections['mcp_sectionloop']['step'] = 1;
$_CLASS['core_template']->_sections['mcp_sectionloop']['start'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['mcp_sectionloop']['loop']-1;
if ($_CLASS['core_template']->_sections['mcp_sectionloop']['show']) {
    $_CLASS['core_template']->_sections['mcp_sectionloop']['total'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['loop'];
    if ($_CLASS['core_template']->_sections['mcp_sectionloop']['total'] == 0)
        $_CLASS['core_template']->_sections['mcp_sectionloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['mcp_sectionloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['mcp_sectionloop']['show']):

            for ($_CLASS['core_template']->_sections['mcp_sectionloop']['index'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['start'], $_CLASS['core_template']->_sections['mcp_sectionloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['mcp_sectionloop']['iteration'] <= $_CLASS['core_template']->_sections['mcp_sectionloop']['total'];
                 $_CLASS['core_template']->_sections['mcp_sectionloop']['index'] += $_CLASS['core_template']->_sections['mcp_sectionloop']['step'], $_CLASS['core_template']->_sections['mcp_sectionloop']['iteration']++):
$_CLASS['core_template']->_sections['mcp_sectionloop']['rownum'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['iteration'];
$_CLASS['core_template']->_sections['mcp_sectionloop']['index_prev'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['index'] - $_CLASS['core_template']->_sections['mcp_sectionloop']['step'];
$_CLASS['core_template']->_sections['mcp_sectionloop']['index_next'] = $_CLASS['core_template']->_sections['mcp_sectionloop']['index'] + $_CLASS['core_template']->_sections['mcp_sectionloop']['step'];
$_CLASS['core_template']->_sections['mcp_sectionloop']['first']      = ($_CLASS['core_template']->_sections['mcp_sectionloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['mcp_sectionloop']['last']       = ($_CLASS['core_template']->_sections['mcp_sectionloop']['iteration'] == $_CLASS['core_template']->_sections['mcp_sectionloop']['total']);

$this->content .= '<tr>';
					if ($_CLASS['core_template']->_tpl_vars['mcp_section'][$_CLASS['core_template']->_sections['mcp_sectionloop']['index']]['S_SELECTED']):
					$this->content .= '<td class="row1"><b class="nav">'.$_CLASS['core_template']->_tpl_vars['mcp_section'][$_CLASS['core_template']->_sections['mcp_sectionloop']['index']]['L_TITLE'].'
</b>

						<ul class="nav" style="margin: 0px; padding: 0px; list-style-type: none; line-height: 175%;">';

						unset($_CLASS['core_template']->_sections['mcp_subsectionloop']);
$_CLASS['core_template']->_sections['mcp_subsectionloop']['name'] = 'mcp_subsectionloop';
$_CLASS['core_template']->_sections['mcp_subsectionloop']['loop'] = is_array($_loop=$_CLASS['core_template']->_tpl_vars['mcp_subsection']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_CLASS['core_template']->_sections['mcp_subsectionloop']['show'] = true;
$_CLASS['core_template']->_sections['mcp_subsectionloop']['max'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['loop'];
$_CLASS['core_template']->_sections['mcp_subsectionloop']['step'] = 1;
$_CLASS['core_template']->_sections['mcp_subsectionloop']['start'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['step'] > 0 ? 0 : $_CLASS['core_template']->_sections['mcp_subsectionloop']['loop']-1;
if ($_CLASS['core_template']->_sections['mcp_subsectionloop']['show']) {
    $_CLASS['core_template']->_sections['mcp_subsectionloop']['total'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['loop'];
    if ($_CLASS['core_template']->_sections['mcp_subsectionloop']['total'] == 0)
        $_CLASS['core_template']->_sections['mcp_subsectionloop']['show'] = false;
} else
    $_CLASS['core_template']->_sections['mcp_subsectionloop']['total'] = 0;
if ($_CLASS['core_template']->_sections['mcp_subsectionloop']['show']):

            for ($_CLASS['core_template']->_sections['mcp_subsectionloop']['index'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['start'], $_CLASS['core_template']->_sections['mcp_subsectionloop']['iteration'] = 1;
                 $_CLASS['core_template']->_sections['mcp_subsectionloop']['iteration'] <= $_CLASS['core_template']->_sections['mcp_subsectionloop']['total'];
                 $_CLASS['core_template']->_sections['mcp_subsectionloop']['index'] += $_CLASS['core_template']->_sections['mcp_subsectionloop']['step'], $_CLASS['core_template']->_sections['mcp_subsectionloop']['iteration']++):
$_CLASS['core_template']->_sections['mcp_subsectionloop']['rownum'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['iteration'];
$_CLASS['core_template']->_sections['mcp_subsectionloop']['index_prev'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['index'] - $_CLASS['core_template']->_sections['mcp_subsectionloop']['step'];
$_CLASS['core_template']->_sections['mcp_subsectionloop']['index_next'] = $_CLASS['core_template']->_sections['mcp_subsectionloop']['index'] + $_CLASS['core_template']->_sections['mcp_subsectionloop']['step'];
$_CLASS['core_template']->_sections['mcp_subsectionloop']['first']      = ($_CLASS['core_template']->_sections['mcp_subsectionloop']['iteration'] == 1);
$_CLASS['core_template']->_sections['mcp_subsectionloop']['last']       = ($_CLASS['core_template']->_sections['mcp_subsectionloop']['iteration'] == $_CLASS['core_template']->_sections['mcp_subsectionloop']['total']);

if ($_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['SECTION'] == $_CLASS['core_template']->_sections['forumrowloop']['index']):
		$this->content .= '<li>&#187;'; if ($_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['S_SELECTED']): $this->content .= '<b>'.$_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['L_TITLE'];  if ($_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['ADD_ITEM']): $this->content .= ' ('. $_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['ADD_ITEM'].'
)'; endif; $this->content .= '</b>'; else: $this->content .= '<a href="'.$_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['U_TITLE'].'
">'.$_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['L_TITLE'];  if ($_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['ADD_ITEM']): $this->content .= ' ('. $_CLASS['core_template']->_tpl_vars['mcp_subsection'][$_CLASS['core_template']->_sections['mcp_subsectionloop']['index']]['ADD_ITEM'].'
)'; endif; $this->content .= '</a>'; endif; $this->content .= '</li>';
							endif;
						endfor; endif;
						$this->content .= '</ul>';

					else:
					$this->content .= '<td class="row2" nowrap="nowrap" onmouseover="this.className=\'row1\'" onmouseout="this.className=\'row2\'" onclick="location.href=\''.$_CLASS['core_template']->_tpl_vars['mcp_section'][$_CLASS['core_template']->_sections['mcp_sectionloop']['index']]['U_TITLE'].'
\'"><a class="nav" href="'.$_CLASS['core_template']->_tpl_vars['mcp_section'][$_CLASS['core_template']->_sections['mcp_sectionloop']['index']]['U_TITLE'].'
">'. $_CLASS['core_template']->_tpl_vars['mcp_section'][$_CLASS['core_template']->_sections['mcp_sectionloop']['index']]['L_TITLE'].'
</a>';
					endif;
					$this->content .= '</td>
				</tr>';
				endfor; endif;
			$this->content .= '</table>';

?>