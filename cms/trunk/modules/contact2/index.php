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

/*$this->test = 'test';
echo $this-> 'test';*/

class page_contact
{
	function page_contact()
	{
		// Add departments
		global $_CLASS;
		
		if (!defined('VIPERAL'))
		{
			die;
		}

		$_CLASS['core_user']->user_setup();
		$_CLASS['core_user']->add_lang();
		
		$this->error = '';
		$this->preview = !empty($_POST['preview']);
		
		if ($this->preview || !empty($_POST['contact']))
		{
			$this->data['MESSAGE'] = trim(get_variable('message', 'POST', ''));
			$this->data['NAME']	= get_variable('sender_name', 'POST', '');
			$this->data['EMAIL']	= get_variable('sender_email', 'POST', '');
		
			foreach ($this->data as $field => $value)
			{
				if (!$value)
				{
					$this->error .= $_CLASS['core_user']->lang['ERROR_'.$field].'<br />';
					unset($field, $value, $lang);
				}
				elseif ($field == 'EMAIL' && !check_email($value))
				{
					$this->error .= $_CLASS['core_user']->lang['BAD_EMAIL'].'<br />';
				}
			} 
		
			if (!$this->error)
			{
				$this->send_feedback();
			}
		}
		else
		{
			$this->data['NAME'] = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['username'] : '';
			$this->data['EMAIL'] = ($_CLASS['core_user']->is_user) ? $_CLASS['core_user']->data['user_email'] : '';
			$this->data['MESSAGE'] = '';
		}
		
		$_CLASS['core_template']->assign_array(array( 
			'ERROR' 				=> $this->error,
			'MESSAGE' 				=> $this->data['MESSAGE'],
			'ACTION' 				=> generate_link($_CLASS['core_display']->page['page_name']),
			'SENDER_EMAIL' 			=> $this->data['EMAIL'],
			'SENDER_NAME' 			=> $this->data['NAME'],
		));
		
		$_CLASS['core_template']->display('modules/Contact/index.html');
	}

	// remove this function
	function send_feedback()
	{
		global $_CLASS, $_CORE_CONFIG;
	
		$_CLASS['core_template']->assign_array(array(
			'SENT_FROM'		=> $this->data['NAME'],
			'SENDER_NAME'	=> $this->data['NAME'],
			'SENDER_EMAIL'	=> $this->data['EMAIL'],
			'SENDER_IP'		=> $_CLASS['core_user']->ip,
			'MESSAGE' 		=> $this->data['MESSAGE'],
		));
	
		$body = trim($_CLASS['core_template']->display('email/contact/index.txt', true));
	
		if ($this->preview)
		{
			$_CLASS['core_template']->assign('PREVIEW', modify_lines($body, '<br/>'));
	
			return;
		}
	
		require_once SITE_FILE_ROOT.'includes/mailer.php';
	
		$mailer = new core_mailer;
		$mailer->to($_CORE_CONFIG['email']['site_mail'], $_CORE_CONFIG['global']['site_name']);
		$mailer->subject($_CLASS['core_user']->get_lang('SITE_FEEDBACK'));
	
		$mailer->message = $body;
	
		trigger_error($mailer->send() ? 'SEND_SUCCESSFULL' : $mailer->error);
	}
}
?>