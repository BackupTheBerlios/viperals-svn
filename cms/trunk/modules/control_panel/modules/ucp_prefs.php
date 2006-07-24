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

// -------------------------------------------------------------
//
// $Id: ucp_prefs.php,v 1.15 2004/06/06 21:44:48 acydburn Exp $
//
// FILENAME  : ucp_prefs.php
// STARTED   : Mon May 19, 2003
// COPYRIGHT : 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------


global $config, $_CLASS, $site_file_root, $_CORE_CONFIG;

$submit = (isset($_POST['submit'])) ? true : false;
$error = $data = array();
$s_hidden_fields = '';
$mode = get_variable('mode', 'REQUEST', 'personal');
$id = $this->module;

switch($mode)
{
	case 'personal':

		if ($submit)
		{
			$time_format	= get_variable('time_format', 'REQUEST', null);
			$lang			= get_variable('lang', 'REQUEST', null);
			$tz				= get_variable('tz', 'REQUEST', 0);
			$dst			= get_variable('dst', 'REQUEST', null);

			$theme 		= get_variable('theme', 'REQUEST', null);

			$viewemail		= (bool) get_variable('viewemail', 'REQUEST', true, 'interger');
			$massemail		= (bool) get_variable('massemail', 'REQUEST', false, 'interger');
			$hideonline		= (bool) get_variable('hideonline', 'REQUEST', true, 'interger');
			$notify_pm		= (bool) get_variable('notifypm', 'REQUEST', true, 'interger');

			$popuppm	= (bool) get_variable('popuppm', 'REQUEST', true, 'interger');
			$allowpm	= (bool) get_variable('allowpm', 'REQUEST', true, 'interger');
			//$report_pm_notify	= (bool) get_variable('report_pm_notify', 'REQUEST', true, 'interger');

			if (empty($error))
			{
				$_CLASS['core_user']->user_data_set('popuppm', $popuppm);
				//$_CLASS['core_user']->user_data_set('report_pm_notify', $report_pm_notify);
				
				$sql_array = array(
					'user_allow_pm'			=> $allowpm, 
					'user_allow_viewemail'	=> $viewemail, 
					'user_allow_massemail'	=> $massemail, 
					'user_allow_viewonline'	=> !$hideonline, 
					//'user_notify_type'		=> $notifymethod, 
					'user_notify_pm'		=> $notify_pm,
					'user_data'				=> serialize($_CLASS['core_user']->data['user_data']), 

					'user_dst'				=> $dst,
					'user_time_format'		=> $time_format,
					'user_lang'				=> $lang,
					'user_timezone'			=> $tz * 3600,
					'user_theme'			=> $theme,
				);

				array_merge($_CLASS['core_user']->data, $sql_array);

				$sql = 'UPDATE ' . CORE_USERS_TABLE . ' 
					SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_array) . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->sql_query($sql);
				
				if ($theme !== $_CLASS['core_display']->theme_name)
				{
					$_CLASS['core_user']->session_data_remove('user_theme');
				}
				
				$_CLASS['core_display']->meta_refresh(3, generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"));
				$message = $_CLASS['core_user']->lang['PREFERENCES_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link("Control_Panel&amp;i=$id&amp;mode=$mode").'">', '</a>');

				trigger_error($message);
			}
		}

		$allowpm = isset($allowpm) ? $allowpm : $_CLASS['core_user']->data['user_allow_pm'];
		$dst = isset($dst) ? $dst : $_CLASS['core_user']->data['user_dst'];
		$hideonline = isset($hideonline) ? $hideonline : !$_CLASS['core_user']->data['user_allow_viewonline'];
		$massemail = isset($massemail) ? $massemail : $_CLASS['core_user']->data['user_allow_massemail'];
		$notifypm = isset($notifypm) ? $notifypm : $_CLASS['core_user']->data['user_notify_pm'];
		$popuppm = isset($popuppm) ? $popuppm : $_CLASS['core_user']->user_data_get('popuppm');
		$viewemail = isset($viewemail) ? $viewemail : $_CLASS['core_user']->data['user_allow_viewemail'];
		$report_pm_notify = isset($report_pm_notify) ? $report_pm_notify : $_CLASS['core_user']->user_data_get('report_pm_notify');
		$notifymethod = isset($notifymethod) ? $notifymethod : $_CLASS['core_user']->data['user_notify_type'];
		$dateformat = isset($dateformat) ? $dateformat : $_CLASS['core_user']->data['user_time_format'];
		$lang = isset($lang) ? $lang : $_CLASS['core_user']->data['user_lang'];
		$theme = isset($theme) ? $theme : $_CLASS['core_user']->data['user_theme'];
		$tz = isset($tz) ? $tz * 3600 : $_CLASS['core_user']->data['user_timezone'] / 3600;

		$view_email_yes = ($viewemail) ? ' checked="checked"' : '';
		$view_email_no = (!$viewemail) ? ' checked="checked"' : '';
		$mass_email_yes = ($massemail) ? ' checked="checked"' : '';
		$mass_email_no = (!$massemail) ? ' checked="checked"' : '';
		$allow_pm_yes = ($allowpm) ? ' checked="checked"' : '';
		$allow_pm_no = (!$allowpm) ? ' checked="checked"' : '';
		$hide_online_yes = ($hideonline) ? ' checked="checked"' : '';
		$hide_online_no = (!$hideonline) ? ' checked="checked"' : '';
		$notify_pm_yes = ($notifypm) ? ' checked="checked"' : '';
		$notify_pm_no = (!$notifypm) ? ' checked="checked"' : '';
		$popup_pm_yes = ($popuppm) ? ' checked="checked"' : '';
		$popup_pm_no = (!$popuppm) ? ' checked="checked"' : '';
		$report_pm_notify_yes = ($report_pm_notify) ? ' checked="checked"' : '';
		$report_pm_notify_no = (!$report_pm_notify) ? ' checked="checked"' : '';
		$dst_yes = ($dst) ? ' checked="checked"' : '';
		$dst_no = (!$dst) ? ' checked="checked"' : '';



		$_CLASS['core_template']->assign_array(array( 
			'ERROR'				=> (sizeof($error)) ? implode('<br />', $error) : '',

			'VIEW_EMAIL_YES'	=> $view_email_yes, 
			'VIEW_EMAIL_NO'		=> $view_email_no, 
			'ADMIN_EMAIL_YES'	=> $mass_email_yes, 
			'ADMIN_EMAIL_NO'	=> $mass_email_no, 
			'HIDE_ONLINE_YES'	=> $hide_online_yes, 
			'HIDE_ONLINE_NO'	=> $hide_online_no, 
			'ALLOW_PM_YES'		=> $allow_pm_yes, 
			'ALLOW_PM_NO'		=> $allow_pm_no, 
			'NOTIFY_PM_YES'		=> $notify_pm_yes, 
			'NOTIFY_PM_NO'		=> $notify_pm_no, 
			'POPUP_PM_YES'		=> $popup_pm_yes, 
			'POPUP_PM_NO'		=> $popup_pm_no,
			'REPORT_PM_NO'		=> $report_pm_notify_no,
			'REPORT_PM_YES'		=> $report_pm_notify_yes,
			'DST_YES'			=> $dst_yes, 
			'DST_NO'			=> $dst_no, 
			'NOTIFY_EMAIL'		=> ($notifymethod == NOTIFY_EMAIL) ? 'checked="checked"' : '', 
			'NOTIFY_IM'			=> ($notifymethod == NOTIFY_IM) ? 'checked="checked"' : '', 
			'NOTIFY_BOTH'		=> ($notifymethod == NOTIFY_BOTH) ? 'checked="checked"' : '', 

			'DATE_FORMAT'		=> $dateformat, 

			'S_LANG_OPTIONS'	=> select_language($lang), 
			'S_THEME_OPTIONS'	=> select_theme($theme, true),
			'S_TZ_OPTIONS'		=> select_tz($tz, true),
			'S_CAN_HIDE_ONLINE'	=> true, 
			'S_SELECT_NOTIFY'	=> ($config['jab_enable'] && $_CLASS['core_user']->data['user_jabber'] && @extension_loaded('xml')) ? true : false)
		);
		break;

	case 'view':

		if ($submit)
		{
			$topic_sk 	= get_variable('topic_sk', 'REQUEST', 't');
			$topic_sd	= get_variable('topic_sd', 'REQUEST', 'd');
			$topic_st	= get_variable('topic_st', 'REQUEST', 0, 'interger');
			
			$post_sk 	= get_variable('post_sk', 'REQUEST', 't');
			$post_sd	= get_variable('post_sd', 'REQUEST', 'd');
			$post_st	= get_variable('post_st', 'REQUEST', 0, 'interger');

			$images		= (bool) get_variable('images', 'REQUEST', true, 'interger');
			$flash		= (bool) get_variable('flash', 'REQUEST', false, 'interger');
			$smilies	= (bool) get_variable('smilies', 'REQUEST', true, 'interger');
			$sigs		= (bool) get_variable('sigs', 'REQUEST', true, 'interger');
			$avatars	= (bool) get_variable('avatars', 'REQUEST', true, 'interger');
			$wordcensor	= (bool) get_variable('wordcensor', 'REQUEST', true, 'interger');

			if (empty($error))
			{
				$_CLASS['core_user']->user_data_set('viewimg', $images);
				$_CLASS['core_user']->user_data_set('viewflash', $flash);
				$_CLASS['core_user']->user_data_set('viewsmilies', $smilies);
				$_CLASS['core_user']->user_data_set('viewsigs', $sigs);
				$_CLASS['core_user']->user_data_set('viewavatars', $avatars);

				if ($_CLASS['forums_auth']->acl_get('u_chgcensors'))
				{
					$_CLASS['core_user']->user_data_set('viewcensors', $wordcensor);
				}

				$sql_ary = array(
					'user_data'				=> serialize($_CLASS['core_user']->data['user_data']), 
					'user_topic_sortby_type'=> $topic_sk,
					'user_post_sortby_type'	=> $post_sk,
					'user_topic_sortby_dir'	=> $topic_sd,
					'user_post_sortby_dir'	=> $post_sd,

					'user_topic_show_days'	=> $topic_st,
					'user_post_show_days'	=> $post_st,
				);

				$sql = 'UPDATE ' . CORE_USERS_TABLE . ' 
					SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
				$_CLASS['core_db']->sql_query($sql);

				$_CLASS['core_display']->meta_refresh(3, generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"));
				$message = $_CLASS['core_user']->lang['PREFERENCES_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link("Control_Panel&amp;i=$id&amp;mode=$mode").'">', '</a>');
				trigger_error($message);
			}
		}

		$topic_sd = empty($_CLASS['core_user']->data['user_topic_sortby_dir']) ? 'd' : $_CLASS['core_user']->data['user_topic_sortby_dir'];
		$topic_sk = empty($_CLASS['core_user']->data['user_tpic_sortby_type']) ? 't' : $_CLASS['core_user']->data['user_topic_sortby_type'];
		$topic_st = empty($_CLASS['core_user']->data['user_topic_show_days']) ? 0 : $_CLASS['core_user']->data['user_topic_show_days'];

		$post_sd = empty($_CLASS['core_user']->data['user_post_sortby_dir']) ? 'd' : $_CLASS['core_user']->data['user_post_sortby_dir'];
		$post_sk = empty($_CLASS['core_user']->data['user_post_sortby_type']) ? 't' : $_CLASS['core_user']->data['user_post_sortby_type'];
		$post_st = empty($_CLASS['core_user']->data['user_post_show_days']) ? 0 : $_CLASS['core_user']->data['user_post_show_days'];

		$sort_dir_text = array('a' => $_CLASS['core_user']->lang['ASCENDING'], 'd' => $_CLASS['core_user']->lang['DESCENDING']);
		
		// Topic ordering options
		$limit_topic_days = array(0 => $_CLASS['core_user']->lang['ALL_TOPICS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);

		$sort_by_topic_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 'r' => $_CLASS['core_user']->lang['REPLIES'], 's' => $_CLASS['core_user']->lang['SUBJECT'], 'v' => $_CLASS['core_user']->lang['VIEWS']);
		$sort_by_topic_sql = array('a' => 't.topic_first_poster_name', 't' => 't.topic_last_post_time', 'r' => 't.topic_replies', 's' => 't.topic_title', 'v' => 't.topic_views');

		// Post ordering options
		$limit_post_days = array(0 => $_CLASS['core_user']->lang['ALL_POSTS'], 1 => $_CLASS['core_user']->lang['1_DAY'], 7 => $_CLASS['core_user']->lang['7_DAYS'], 14 => $_CLASS['core_user']->lang['2_WEEKS'], 30 => $_CLASS['core_user']->lang['1_MONTH'], 90 => $_CLASS['core_user']->lang['3_MONTHS'], 180 => $_CLASS['core_user']->lang['6_MONTHS'], 364 => $_CLASS['core_user']->lang['1_YEAR']);

		$sort_by_post_text = array('a' => $_CLASS['core_user']->lang['AUTHOR'], 't' => $_CLASS['core_user']->lang['POST_TIME'], 's' => $_CLASS['core_user']->lang['SUBJECT']);
		$sort_by_post_sql = array('a' => 'u.username', 't' => 'p.post_id', 's' => 'p.post_subject');

		foreach (array('topic', 'post') as $sort_option)
		{
			${'s_limit_' . $sort_option . '_days'} = '<select name="' . $sort_option . '_st">';
			foreach (${'limit_' . $sort_option . '_days'} as $day => $text)
			{
				$selected = (${$sort_option . '_st'} == $day) ? ' selected="selected"' : '';
				${'s_limit_' . $sort_option . '_days'} .= '<option value="' . $day . '"' . $selected . '>' . $text . '</option>';
			}
			${'s_limit_' . $sort_option . '_days'} .= '</select>';

			${'s_sort_' . $sort_option . '_key'} = '<select name="' . $sort_option . '_sk">';
			foreach (${'sort_by_' . $sort_option . '_text'} as $key => $text)
			{
				$selected = (${$sort_option . '_sk'} == $key) ? ' selected="selected"' : '';
				${'s_sort_' . $sort_option . '_key'} .= '<option value="' . $key . '"' . $selected . '>' . $text . '</option>';
			}
			${'s_sort_' . $sort_option . '_key'} .= '</select>';

			${'s_sort_' . $sort_option . '_dir'} = '<select name="' . $sort_option . '_sd">';
			foreach ($sort_dir_text as $key => $value)
			{
				$selected = (${$sort_option . '_sd'} == $key) ? ' selected="selected"' : '';
				${'s_sort_' . $sort_option . '_dir'} .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}
			${'s_sort_' . $sort_option . '_dir'} .= '</select>';
		}

		$images 	= $_CLASS['core_user']->user_data_get('viewimg');
		$flash		= $_CLASS['core_user']->user_data_get('viewflash');
		$smilies	= $_CLASS['core_user']->user_data_get('viewsmilies');
		$sigs		= $_CLASS['core_user']->user_data_get('viewsigs');
		$avatars	= $_CLASS['core_user']->user_data_get('viewavatars');
		$wordcensor = $_CLASS['core_user']->user_data_get('viewcensors');

		$images_yes = ($images) ? ' checked="checked"' : '';
		$images_no = (!$images) ? ' checked="checked"' : '';
		$flash_yes = ($flash) ? ' checked="checked"' : '';
		$flash_no = (!$flash) ? ' checked="checked"' : '';
		$smilies_yes = ($smilies) ? ' checked="checked"' : '';
		$smilies_no = (!$smilies) ? ' checked="checked"' : '';
		$sigs_yes = ($sigs) ? ' checked="checked"' : '';
		$sigs_no = (!$sigs) ? ' checked="checked"' : '';
		$avatars_yes = ($avatars) ? ' checked="checked"' : '';
		$avatars_no = (!$avatars) ? ' checked="checked"' : '';
		$wordcensor_yes = ($wordcensor) ? ' checked="checked"' : '';
		$wordcensor_no = (!$wordcensor) ? ' checked="checked"' : '';

		$_CLASS['core_template']->assign_array(array( 
			'ERROR'				=> empty($error) ? '' : implode('<br />', $error),
			
			'VIEW_IMAGES_YES'		=> $images_yes, 
			'VIEW_IMAGES_NO'		=> $images_no, 
			'VIEW_FLASH_YES'		=> $flash_yes, 
			'VIEW_FLASH_NO'			=> $flash_no, 
			'VIEW_SMILIES_YES'		=> $smilies_yes, 
			'VIEW_SMILIES_NO'		=> $smilies_no, 
			'VIEW_SIGS_YES'			=> $sigs_yes, 
			'VIEW_SIGS_NO'			=> $sigs_no, 
			'VIEW_AVATARS_YES'		=> $avatars_yes, 
			'VIEW_AVATARS_NO'		=> $avatars_no,
			'DISABLE_CENSORS_YES'	=> $wordcensor_yes, 
			'DISABLE_CENSORS_NO'	=> $wordcensor_no,

			'S_CHANGE_CENSORS'		=> $_CLASS['forums_auth']->acl_get('u_chgcensors'), 

			'S_TOPIC_SORT_DAYS'		=> $s_limit_topic_days,
			'S_TOPIC_SORT_KEY'		=> $s_sort_topic_key,
			'S_TOPIC_SORT_DIR'		=> $s_sort_topic_dir,
			'S_POST_SORT_DAYS'		=> $s_limit_post_days,
			'S_POST_SORT_KEY'		=> $s_sort_post_key,
			'S_POST_SORT_DIR'		=> $s_sort_post_dir
		));
	break;

	case 'post':
		if ($submit)
		{
			$bbcode 	= (bool) get_variable('bbcode', 'REQUEST', true, 'interger');
			$html		= (bool) get_variable('html', 'REQUEST', false, 'interger');
			$smilies	= (bool) get_variable('smilies', 'REQUEST', true, 'interger');
			$sig		= (bool) get_variable('sig', 'REQUEST', true, 'interger');
			$notify		= (bool) get_variable('notify', 'REQUEST', false, 'interger');

			$_CLASS['core_user']->user_data_set('bbcode', $bbcode);
			$_CLASS['core_user']->user_data_set('html', $html);
			$_CLASS['core_user']->user_data_set('smilies', $smilies);
			$_CLASS['core_user']->user_data_set('attachsig', $sig);

			$sql_ary = array(
				'user_data'		=> serialize($_CLASS['core_user']->data['user_data']),
				'user_notify'	=> $notify,
			);

			$sql = 'UPDATE ' . CORE_USERS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
						WHERE user_id = ' . $_CLASS['core_user']->data['user_id'];
			$_CLASS['core_db']->sql_query($sql);

			$_CLASS['core_display']->meta_refresh(3, generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"));
			$message = $_CLASS['core_user']->lang['PREFERENCES_UPDATED'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_UCP'], '<a href="'.generate_link("Control_Panel&amp;i=$id&amp;mode=$mode").'">', '</a>');
			trigger_error($message);
		}

		$bbcode = $_CLASS['core_user']->user_data_get('bbcode');
		$html = $_CLASS['core_user']->user_data_get('html');
		$smilies = $_CLASS['core_user']->user_data_get('smilies');
		$notify = $_CLASS['core_user']->data['user_notify'];

		$bbcode_yes = ($bbcode) ? ' checked="checked"' : '';
		$bbcode_no = (!$bbcode) ? ' checked="checked"' : '';		
		$html_yes = ($html) ? ' checked="checked"' : '';
		$html_no = (!$html) ? ' checked="checked"' : '';
		$smilies_yes = ($smilies) ? ' checked="checked"' : '';
		$smilies_no = (!$smilies) ? ' checked="checked"' : '';
		$sig = $_CLASS['core_user']->user_data_get('attachsig');
		$sig_yes = ($sig) ? ' checked="checked"' : '';
		$sig_no = (!$sig) ? ' checked="checked"' : '';
		$notify_yes = ($notify) ? ' checked="checked"' : '';
		$notify_no = (!$notify) ? ' checked="checked"' : '';

		$_CLASS['core_template']->assign_array(array(
			'ERROR'				=> empty($error) ? '' :  implode('<br />', $error),

			'DEFAULT_BBCODE_YES'	=> $bbcode_yes, 
			'DEFAULT_BBCODE_NO'		=> $bbcode_no, 
			'DEFAULT_HTML_YES'		=> $html_yes, 
			'DEFAULT_HTML_NO'		=> $html_no, 
			'DEFAULT_SMILIES_YES'	=> $smilies_yes, 
			'DEFAULT_SMILIES_NO'	=> $smilies_no, 
			'DEFAULT_SIG_YES'		=> $sig_yes, 
			'DEFAULT_SIG_NO'		=> $sig_no, 
			'DEFAULT_NOTIFY_YES'	=> $notify_yes, 
			'DEFAULT_NOTIFY_NO'		=> $notify_no,
		));
	break;
	
	default:
		die;
	break;
}

$_CLASS['core_template']->assign_array(array( 
	'L_TITLE'			=> $_CLASS['core_user']->lang['UCP_PREFS_' . strtoupper($mode)],
	'S_HIDDEN_FIELDS'	=> $s_hidden_fields,
	'S_UCP_ACTION'		=> generate_link("Control_Panel&amp;i=$id&amp;mode=$mode")
));

$_CLASS['core_display']->display($_CLASS['core_user']->lang['UCP_PROFILE'], 'modules/control_panel/ucp_prefs_' . $mode . '.html');

?>