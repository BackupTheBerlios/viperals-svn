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
*/

function confirmation_image($code = false, $size = false)
{
	global $_CLASS, $site_file_root;

	if (!$code && !($code = $_CLASS['core_user']->session_data_get('confirmation_code')))
	{
		return;
	}

	if (extension_loaded('zlib') && !ob_get_length())
	{
		ob_start('ob_gzhandler');
	}

	$size = 24;

	header('Content-type: image/png');
	header('Cache-control: no-cache, no-store');

	if (function_exists('imagettfbbox'))
	{
		$image_width = 5;
		$image_height = 0;
		//$font = $site_file_root.'includes/fonts/angltrr.ttf';
		$font = $site_file_root.'includes/fonts/tomnr.ttf';

		$count = strlen($code);
		for ($loop = 0; $loop < $count; $loop++)
		{
			// current char.
			$char = substr($code, $loop, 1);
			// random angle
			$angle = mt_rand(-20, 20);

			$borders = imagettfbbox($size, $angle, $font, $char);

			for ($i = 0; $i < 8; $i++)
			{
				$borders[$i] = ($borders[$i] > 0) ? $borders[$i] : -$borders[$i];
			}

			// Heighest point for the current char..
			$this_height = max($borders[5], $borders[7]);
			// Weight of the current char + a random number
			$this_width = max($borders[2], $borders[4]) - min($borders[0], $borders[6]) + mt_rand(10, 20);

			// Set and modify image size
			$image_width += $this_width;
			$image_height = max($this_height, $image_height);

			$holding[] = array('char' => $char, 'height' => $this_height, 'width' => $this_width, 'angle' => $angle);
		}

		$image_height += 10;
		$im = imagecreate($image_width, $image_height);

		// Set white as background then make it transparent
		$white = imagecolorallocate($im, 255, 255, 255);
		$trans = imagecolortransparent($im, $white);

		// Add needed colors
		$grey = imagecolorallocate($im, 128, 128, 128);
		$black = imagecolorallocate($im, 0, 0, 0);

		$x = 5;

		// Add each word char one at a time, with it's random rotation and hieght
		foreach ($holding as $info)
		{
			$y = $image_height - mt_rand(5, ($image_height - $info['height']));

			imagettftext($im, $size, $info['angle'], $x + 1, $y + 1, $grey, $font, $info['char']);
			imagettftext($im, $size, $info['angle'], $x, $y, $black, $font, $info['char']);

			$x += $info['width'];
		}

		imagepng($im);
		imagedestroy($im);

		return;
	}

	$font = imageloadfont($site_file_root.'includes/fonts/tomnr.gdf');

	// needs work
	if (!$font)
	{
		$font = $size;
		$width_addition = true;
		$font_width = imagefontwidth($font);
	
		$image_width = strlen($code) * $font_width * 4;
		$image_height = imagefontheight($font) * 2;
		$min_y = imagefontheight($font);
		$max_y = 0;
	}
	else
	{
		$width_addition = false;
		$font_width = imagefontwidth($font);
	
		$image_width = strlen($code) * $font_width;
		$image_height = imagefontheight($font) + 5;
		$max_y = -($image_height - imagefontheight($font));
		$min_y = $image_height - imagefontheight($font);
	}

	//$font = $size;

	$im = imagecreate($image_width, $image_height);

	$white = imagecolorallocate($im, 255, 255, 255);
	$trans = imagecolortransparent($im, $white);

	$grey = imagecolorallocate($im, 128, 128, 128);
	$black = imagecolorallocate($im, 0, 0, 0);

	$x = 2;
	$count = strlen($code);

	for ($loop = 0; $loop < $count; $loop++)
	{
		imagestring ($im, $font, $x, mt_rand($max_y, $min_y), substr($code, $loop, 1), $black);
		$x += $font_width * (($width_addition) ? mt_rand(2, 5) : 1);
	}

	imagepng($im);
	imagedestroy($im);
}

function activate()
{
	global $_CLASS;

	$user_id = get_variable('user_id', 'GET', 0, 'integer');
	$key = get_variable('key', 'GET', false);

	if (!$user_id || !$key)
	{
		trigger_error('CANT_ACTIVATED');
	}
	
	$sql = 'SELECT username, user_status, user_email, user_new_password, user_new_password_encoding, user_act_key
		FROM ' . USERS_TABLE . " WHERE user_id = $user_id AND user_type = ".USER_NORMAL;

	$result = $_CLASS['core_db']->sql_query($sql);
	$row = $_CLASS['core_db']->sql_fetchrow($result);
	$_CLASS['core_db']->sql_freeresult($result);

	if (!$row)
	{
		trigger_error('NO_USER');
	}
	
	if ($row['user_status'] != USER_UNACTIVATED && !$row['user_new_password'])
	{
		trigger_error(($row['user_status'] == USER_ACTIVE) ? 'ALREADY_ACTIVATED' : 'CANT_ACTIVATED');
	}
	
	if ($row['user_act_key'] != $key)
	{
		trigger_error('WRONG_ACTIVATION_KEY');
	}

	$sql_ary = array(
		'user_act_key'				=> null,
		'user_new_password'			=> null,
		'user_new_password_encoding'=> null
	);

	if ($row['user_new_password'])
	{
		$sql_ary += array(
			'user_password'				=> $row['user_new_password'],
			'user_password_encoding'	=> $row['user_new_password_encoding'],
		);
	}
	else
	{
		include_once($site_file_root.'includes/functions_user.php');
		user_activate($user_id);
		
		set_core_config('user', 'newest_user_id', $row['user_id'], false);
		set_core_config('user', 'newest_username', $row['username'], false);
	}

	$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $_CLASS['core_db']->sql_build_array('UPDATE', $sql_ary) . '
		WHERE user_id = ' . $row['user_id'];
	$result = $_CLASS['core_db']->sql_query($sql);
}

function login()
{
	global $_CLASS;

	$login_options = array('full_screen' => isset($_REQUEST['full']));
		
	$_CLASS['core_auth']->do_login($login_options, false);
}

?>