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

global $table_prefix, $site_file_root, $_CORE_CONFIG;

/*
if (!defined('QUICK_MESSAGE_TABLE'))
{
	define('QUICK_MESSAGE_TABLE', $table_prefix.'quick_message');
}
*/

require_once($site_file_root.'modules/Quick_Message/functions.php');

$this->content = '<div id="qm_block">'.qm_block_content().'</div>';

$this->content .= '<script type="text/javascript" src="javascript/quick_messages.js"></script><div align="center"><a href="'.generate_link('Quick_Message').'">Message History</a><br />';

if (!$_CLASS['core_user']->is_user && !$_CORE_CONFIG['quick_message']['anonymous_posting'])
{
	$this->content .= '<br/>Only registered users can post<br />[ <a href="'.generate_link('Control_Panel&amp;mode=register').'">Register</a>&nbsp;|&nbsp;<a href="'.generate_link('Control_Panel').'">Login</a> ]<br /></div>';

	return;
}

$this->content .= '<form onsubmit="return quick_message_submit();" method="post" action="'.generate_link('Quick_Message&amp;mode=add').'">';

if (!$_CLASS['core_user']->is_user && $_CORE_CONFIG['quick_message']['anonymous_posting'] == '2')
{
	$this->content .= 'Name: <br /><input class="post" type="text" style="width:90%;" id="poster_name" name="poster_name" size="10" maxlength="10" /><br />';
}

$this->content .= 'Message <br/> <textarea id="message" name="message" style="width:90%;" rows="3"></textarea><br /><br />
			<input class="button" type="submit" name="submit" value="Post" />
			<input class="button" type="button" name="submit" onclick="quick_message_refresh()" value="Refresh" />
		</div></form>';

?>