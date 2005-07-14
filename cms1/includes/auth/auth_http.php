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

class auth_db extends core_auth
{
	function do_login($login_options, $template)
	{
		global $_CLASS, $_CORE_CONFIG;

		$user_name = (!empty($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : getenv('PHP_AUTH_USER');
		$user_password = (!empty($_SERVER['PHP_AUTH_PW'])) ? $_SERVER['PHP_AUTH_PW'] : getenv('PHP_AUTH_PW');
		//list($user_name, $user_password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		$error = '';

		$login_array = array(
			'redirect' 		=> false,
			'explain' 	 	=> false,
			'success'  		=> '',
			'admin_login'	=> false,
			'full_login'	=> true,
			'full_screen'	=> false,
		);
	
		if (is_array($login_options))
		{
			$login_array = array_merge($login_array, $login_options);
		}

		if ($user_name || $user_password)
		{
			if (!$user_name || !$user_password)
			{
				$error = 'INCOMPLETE_LOGIN_INFO';
			}

			if (!$error)
			{
				$result = $this->user_auth($user_name,  $user_password);

				if (is_numeric($result))
				{
					$_CLASS['core_user']->login($result, $login_array['admin_login'], false);

					$login_array['redirect'] = generate_link(get_variable('redirect', 'POST', $login_array['redirect']), array('admin' => $data['admin_login']));	

					$_CLASS['core_display']->meta_refresh(5, $login_array['redirect']);
					$message = (($login_array['success']) ? $_CLASS['core_user']->get_lang($login_array['success']) : $_CLASS['core_user']->lang['LOGIN_REDIRECT']) . '<br /><br />' . sprintf($_CLASS['core_user']->lang['RETURN_PAGE'], '<a href="' . $login_array['redirect'] . '">', '</a> ');

					trigger_error($message);
				}

				$error = (is_string($result)) ? $result : 'LOGIN_ERROR';
			}
		}

		if (!$login_array['redirect'])
		{
			$login_array['redirect'] = htmlspecialchars($_CLASS['core_user']->url);
		}

// better realm needed, logout support needed
// Random realm for spoofers ?
		header('WWW-Authenticate: Basic realm="Site Login"');
		header('HTTP/1.0 401 Unauthorized');
		
		//echo $error
	}
}
?>