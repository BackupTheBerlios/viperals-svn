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

/*
	What's this, Multibyte support is not avalible,
	next your going to say you can't use a database *
*/

if (!function_exists('mb_internal_encoding'))
{
	function mb_internal_encoding()
	{
	}
}

if (!function_exists('mb_http_output'))
{
	function mb_http_output()
	{
	}
}

if (!function_exists('mb_strlen'))
{
	function mb_strlen($string, $encoding = null)
	{
		return strlen($string);
	}
}

if (!function_exists('mb_strpos'))
{
	function mb_strpos($haystack, $needle, $offset = false, $encoding = null)
	{
		if ($offset)
		{
			return strpos($haystack, $needle, $offset);
		}

		return strpos($haystack, $needle);
	}
}

if (!function_exists('mb_substr'))
{
	function mb_substr($string, $start, $length = false, $encoding = null)
	{
		if ($length)
		{
			return substr($string, $start, $length);
		}

		return substr($string, $start);
	}
}

if (!function_exists('mb_convert_case'))
{
	function mb_convert_case($string, $mode = null, $encoding = null)
	{
		// maybe add modes
		return ucfirst(strtolower($string));
	}
}


?>