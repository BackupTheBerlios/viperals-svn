<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

function confirmation_image($code = false)
{
	global $_CLASS, $site_file_root;

	if (extension_loaded('zlib') && !ob_get_length())
	{
		ob_start('ob_gzhandler');
	}

	if (!$code && !($code = $_CLASS['core_user']->get_data('confirmation_code')))
	{
		$code = 'test';
		//return;
	}

	$size = 24;

	header('Content-type: image/png');
	header('Cache-control: no-cache, no-store');

	if (function_exists('imagettfbbox'))
	{
		$image_width = 5;
		$image_height = 0;
		$font = $site_file_root.'includes/fonts/angltrr.ttf';
		//$font = $site_file_root.'includes/fonts/tomnr.ttf';

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
		
		$white = imagecolorallocate($im, 255, 255, 255);
		$trans = imagecolortransparent($im, $white);
		
		$grey = imagecolorallocate($im, 128, 128, 128);
		$black = imagecolorallocate($im, 0, 0, 0);
		
		$x = 5;
		
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
		$image_height = imagefontheight($font) + 10;
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

?>