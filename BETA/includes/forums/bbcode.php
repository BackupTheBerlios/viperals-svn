<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// $Id: bbcode.php,v 1.79 2004/09/16 18:33:18 acydburn Exp $
//
// FILENAME  : bbcode.php 
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// BBCODE - able to be used standalone

class bbcode
{
	var $bbcode_uid = '';
	var $bbcode_bitfield = 0;
	var $bbcode_cache = array();
	var $bbcode_template = array();
	
	var $bbcodes = array();
	var $template_bitfield = 0;
	var $template_filename = '';

	function bbcode($bitfield = 0)
	{
		if ($bitfield)
		{
			$this->bbcode_bitfield = $bitfield;
			$this->bbcode_cache_init();
		}
	}

	function bbcode_second_pass(&$message, $bbcode_uid = '', $bbcode_bitfield = false)
	{
		if ($bbcode_uid)
		{
			$this->bbcode_uid = $bbcode_uid;
		}

		if ($bbcode_bitfield !== false)
		{
			$this->bbcode_bitfield = $bbcode_bitfield;
			// Init those added with a new bbcode_bitfield (already stored codes will not get parsed again)
			$this->bbcode_cache_init();
		}

		if (!$this->bbcode_bitfield)
		{
			return $message;
		}

		$str = array('search' => array(), 'replace' => array());
		$preg = array('search' => array(), 'replace' => array());

		$bitlen = strlen(decbin($this->bbcode_bitfield));
		for ($bbcode_id = 0; $bbcode_id < $bitlen; ++$bbcode_id)
		{
			if ($this->bbcode_bitfield & (1 << $bbcode_id))
			{
				if (!empty($this->bbcode_cache[$bbcode_id]))
				{
					foreach ($this->bbcode_cache[$bbcode_id] as $type => $array)
					{
						foreach ($array as $search => $replace)
						{
							${$type}['search'][] = str_replace('$uid', $this->bbcode_uid, $search);
							${$type}['replace'][] = $replace;
						}
					}
				}
			}
		}

		if (count($str['search']))
		{
			$message = str_replace($str['search'], $str['replace'], $message);
		}
		if (count($preg['search']))
		{
			$message = preg_replace($preg['search'], $preg['replace'], $message);
		}
		
		// Remove the uid from tags that have not been transformed into HTML
		$message = str_replace(':' . $this->bbcode_uid, '', $message);
		$message = $this->decode_bbcode_php($message);
	}
	
	function decode_bbcode_php($text)
	{
		$code_start_html = '<div class="codetitle"><b>PHP:</b></div><div class="codecontent">';
		$code_end_html =  '</div>';
		$matches = array();
		$match_count = preg_match_all("#\<!--php-->(.*?)\<!--/php-->#si", $text, $matches);
	
		for ($i = 0; $i < $match_count; $i++) {
			$before_replace = $matches[1][$i];
			$after_replace = trim($matches[1][$i]);

			$str_to_match = '<!--php-->' . $before_replace . '<!--/php-->';
			$replacement = $code_start_html;
			

			$after_replace = str_replace('&lt;', '<', $after_replace);
			$after_replace = str_replace('&gt;', '>', $after_replace);
			$after_replace = str_replace("<br />\r\n", "\n", $after_replace);
			$after_replace = str_replace("<br />\n", "\n", $after_replace);
			$after_replace = str_replace('&amp;', '&', $after_replace);
			$after_replace = str_replace('&quot;', '"', $after_replace);
			$after_replace = str_replace('&#39;', "'", $after_replace);

			$added = FALSE;
			if (preg_match('/^<\?.*?\?>$/si', $after_replace) <= 0) {
				$after_replace = "<?php $after_replace ?>";
				$added = TRUE;
			}

			$after_replace = highlight_string($after_replace, TRUE);

			if ($added == TRUE) {
				$after_replace = str_replace('<font color="#0000BB">&lt;?php', '<font color="#0000BB">', $after_replace);
				$after_replace = str_replace('&lt;?php', '', $after_replace); // php 5
				$after_replace = str_replace('<font color="#0000BB">?&gt;</font>', '', $after_replace);
				$after_replace = str_replace('?&gt;', '', $after_replace); // php 5

			}
			$after_replace = preg_replace('/<font color="(.*?)">/si', '<span style="color: \\1;">', $after_replace);
			$after_replace = str_replace('</font>', '</span>', $after_replace);
			$after_replace = str_replace("\n", '', $after_replace);
			$replacement .= $after_replace;
			$replacement .= $code_end_html;
	
			$text = str_replace($str_to_match, $replacement, $text);
		}
	
		//$text = str_replace('<!--php-->', $code_start_html, $text);
		//$text = str_replace('<!--/php-->', $code_end_html, $text);
	
		return $text;
	}
	//
	// bbcode_cache_init()
	//
	// requires: $this->bbcode_bitfield
	// sets: $this->bbcode_cache with bbcode templates needed for bbcode_bitfield
	//
	function bbcode_cache_init()
	{
		global $_CLASS;

		if (empty($this->template_filename))
		{
			$style = 'primary';
			if (!empty($_CLASS['user']->theme['secondary']))
			{
				// If the primary style has custom templates for BBCodes then we'll make sure
				// the bbcode.html file is present, otherwise we'll use the secondary style
			}

			$this->template_bitfield = $_CLASS['user']->theme['bbcode_bitfield'];
			$this->template_filename = file_exists('themes/' . $_CLASS['display']->theme . '/template/forums/bbcode.html') ? 'themes/' . $_CLASS['display']->theme . '/template/forums/bbcode.html' : 'includes/templates/forums/bbcode.html';
		}

		$sql = '';
		$bbcode_ids = array();
		$bitlen = strlen(decbin($this->bbcode_bitfield));

		for ($bbcode_id = 0; $bbcode_id < $bitlen; ++$bbcode_id)
		{
			if (isset($this->bbcode_cache[$bbcode_id]) || !($this->bbcode_bitfield & (1 << $bbcode_id)))
			{
				// do not try to re-cache it if it's already in
				continue;
			}
			$bbcode_ids[] = $bbcode_id;

			if ($bbcode_id > NUM_CORE_BBCODES)
			{
				$sql .= (($sql) ? ',' : '') . $bbcode_id;
			}
		}

		if ($sql)
		{
			global $db;
			$rowset = array();

			$sql = 'SELECT *
				FROM ' . BBCODES_TABLE . "
				WHERE bbcode_id IN ($sql)";

			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$rowset[$row['bbcode_id']] = $row;
			}
			$db->sql_freeresult($result);
		}

		foreach ($bbcode_ids as $bbcode_id)
		{
			switch ($bbcode_id)
			{
				case 0:
					$this->bbcode_cache[$bbcode_id] = array(
						'str' => array(
							'[quote:$uid]'	=>	$this->bbcode_tpl('quote_open', $bbcode_id),
							'[/quote:$uid]'	=>	$this->bbcode_tpl('quote_close', $bbcode_id)
						),
						'preg' => array(
							'#\[quote=&quot;(.*?)&quot;:$uid\]#'	=>	$this->bbcode_tpl('quote_username_open', $bbcode_id)
						)
					);
				break;
				case 1:
					$this->bbcode_cache[$bbcode_id] = array('str' => array(
						'[b:$uid]'	=>	$this->bbcode_tpl('b_open', $bbcode_id),
						'[/b:$uid]'	=>	$this->bbcode_tpl('b_close', $bbcode_id)
					));
				break;
				case 2:
					$this->bbcode_cache[$bbcode_id] = array('str' => array(
						'[i:$uid]'	=>	$this->bbcode_tpl('i_open', $bbcode_id),
						'[/i:$uid]'	=>	$this->bbcode_tpl('i_close', $bbcode_id)
					));
				break;
				case 3:
					$this->bbcode_cache[$bbcode_id] = array('preg' => array(
						'#\[url:$uid\]((.*?))\[/url:$uid\]#s'		=>	$this->bbcode_tpl('url', $bbcode_id),
						'#\[url=([^\[]+?):$uid\](.*?)\[/url:$uid\]#s'	=>	$this->bbcode_tpl('url', $bbcode_id)
					));
				break;
				case 4:
					if ($_CLASS['user']->optionget('viewimg'))
					{
						$this->bbcode_cache[$bbcode_id] = array('preg' => array(
							'#\[img:$uid\](.*?)\[/img:$uid\]#s'		=>	$this->bbcode_tpl('img', $bbcode_id)
						));
					}
					else
					{
						$this->bbcode_cache[$bbcode_id] = array('preg' => array(
							'#\[img:$uid\](.*?)\[/img:$uid\]#s'		=>	str_replace('$2', '[ img ]', $this->bbcode_tpl('url', $bbcode_id))
						));
					}
				break;
				case 5:
					$this->bbcode_cache[$bbcode_id] = array('preg' => array(
						'#\[size=([\-\+]?[1-2]?[0-9]):$uid\](.*?)\[/size:$uid\]#s'	=>	$this->bbcode_tpl('size', $bbcode_id)
					));
				break;
				case 6:
					$this->bbcode_cache[$bbcode_id] = array('preg' => array(
						'!\[color=(#[0-9A-F]{6}|[a-z\-]+):$uid\](.*?)\[/color:$uid\]!s'	=>	$this->bbcode_tpl('color', $bbcode_id)
					));
				break;
				case 7:
					$this->bbcode_cache[$bbcode_id] = array('str' => array(
						'[u:$uid]'	=>	$this->bbcode_tpl('u_open', $bbcode_id),
						'[/u:$uid]'	=>	$this->bbcode_tpl('u_close', $bbcode_id)
					));
				break;
				case 8:
					$this->bbcode_cache[$bbcode_id] = array('preg' => array(
						'#\[code(?:=([a-z]+))?:$uid\](.*?)\[/code:$uid\]#ise'	=>	"\$this->bbcode_second_pass_code('\$1', '\$2')"
					));
				break;
				case 9:
					$this->bbcode_cache[$bbcode_id] = array(
						'str' => array(
							'[list:$uid]'		=>	$this->bbcode_tpl('ulist_open_default', $bbcode_id),
							'[/list:u:$uid]'	=>	$this->bbcode_tpl('ulist_close', $bbcode_id),
							'[/list:o:$uid]'	=>	$this->bbcode_tpl('olist_close', $bbcode_id),
							'[*:$uid]'			=>	$this->bbcode_tpl('listitem', $bbcode_id),
							'[/*:$uid]'			=>	$this->bbcode_tpl('listitem_close', $bbcode_id),
							'[/*:m:$uid]'		=>	$this->bbcode_tpl('listitem_close', $bbcode_id)
						),
						'preg' => array(
							'#\[list=([^\[]+):$uid\]#e'	=>	"\$this->bbcode_list('\$1')",
						)
					);
				break;
				case 10:
					$this->bbcode_cache[$bbcode_id] = array('preg' => array(
							'#\[email:$uid\]((.*?))\[/email:$uid\]#is'				=>	$this->bbcode_tpl('email', $bbcode_id),
							'#\[email=([^\[]+):$uid\](.*?)\[/email:$uid\]#is'	=>	$this->bbcode_tpl('email', $bbcode_id)
					));
				break;
				case 11:
					if ($_CLASS['user']->optionget('viewflash'))
					{
						$this->bbcode_cache[$bbcode_id] = array('preg' => array(
							'#\[flash=([0-9]+),([0-9]+):$uid\](.*?)\[/flash:$uid\]#'	=>	$this->bbcode_tpl('flash', $bbcode_id)
						));
					}
					else
					{
						$this->bbcode_cache[$bbcode_id] = array('preg' => array(
							'#\[flash=([0-9]+),([0-9]+):$uid\](.*?)\[/flash:$uid\]#'	=>	str_replace('$1', '$3', str_replace('$2', '[ flash ]', $this->bbcode_tpl('url', $bbcode_id)))
						));
					}
				break;
				case 12:
					$this->bbcode_cache[$bbcode_id] = array(
						'str'	=> array(
							'[/attachment:$uid]'	=> $this->bbcode_tpl('inline_attachment_close', $bbcode_id)),
						'preg'	=> array(
							'#\[attachment=([0-9]+):$uid\]#'	=> $this->bbcode_tpl('inline_attachment_open', $bbcode_id))
					);
					break;
				default:
					if (isset($rowset[$bbcode_id]))
					{
						if ($this->template_bitfield & (1 << $bbcode_id))
						{
							// The bbcode requires a custom template to be loaded

							if (!$bbcode_tpl = $this->bbcode_tpl($rowset[$bbcode_id]['bbcode_tag'], $bbcode_id))
							{
								// For some reason, the required template seems not to be available,
								// use the default template

								$bbcode_tpl = (!empty($rowset[$bbcode_id]['second_pass_replace'])) ? $rowset[$bbcode_id]['second_pass_replace'] : $rowset[$bbcode_id]['bbcode_tpl'];
							}
							else
							{
								// In order to use templates with custom bbcodes we need
								// to replace all {VARS} to corresponding backreferences
								// Note that backreferences are numbered from bbcode_match

								if (preg_match_all('/\{(URL|EMAIL|TEXT|COLOR|NUMBER)[0-9]*\}/', $rowset[$bbcode_id]['bbcode_match'], $m))
								{
									foreach ($m[0] as $i => $tok)
									{
										$bbcode_tpl = str_replace($tok, '$' . ($i + 1), $bbcode_tpl);
									}
								}
							}
						}
						else
						{
							// Default template

							$bbcode_tpl = (!empty($rowset[$bbcode_id]['second_pass_replace'])) ? $rowset[$bbcode_id]['second_pass_replace'] : $rowset[$bbcode_id]['bbcode_tpl'];
						}

						// Replace {L_*} lang strings
						$bbcode_tpl = preg_replace('/{L_([A-Z_]+)}/e', "(!empty(\$_CLASS['user']->lang['\$1'])) ? \$_CLASS['user']->lang['\$1'] : ucwords(strtolower(str_replace'_', ' ', '\$1')))", $bbcode_tpl);

						if (!empty($rowset[$bbcode_id]['second_pass_replace']))
						{
							// The custom BBCode requires second-pass pattern replacements

							$this->bbcode_cache[$bbcode_id] = array(
								'preg' => array($rowset[$bbcode_id]['second_pass_match'] => $bbcode_tpl)
							);
						}
						else
						{
							$this->bbcode_cache[$bbcode_id] = array(
								'str' => array($rowset[$bbcode_id]['second_pass_match'] => $bbcode_tpl)
							);
						}
					}
					else
					{
						$this->bbcode_cache[$bbcode_id] = FALSE;
					}
			}
		}
	}

	function bbcode_tpl($tpl_name, $bbcode_id = -1)
	{
		if (empty($bbcode_hardtpl))
		{
			static $bbcode_hardtpl = array(
				'b_open'	=>	'<span style="font-weight: bold">',
				'b_close'	=>	'</span>',
				'i_open'	=>	'<span style="font-style: italic">',
				'i_close'	=>	'</span>',
				'u_open'	=>	'<span style="text-decoration: underline">',
				'u_close'	=>	'</span>',
				'url'		=>	'<a href="$1" target="_blank">$2</a>',
				'img'		=>	'<img src="$1" border="0" />',
				'size'		=>	'<span style="font-size: $1px; line-height: normal">$2</span>',
				'color'		=>	'<span style="color: $1">$2</span>',
				'email'		=>	'<a href="mailto:$1">$2</a>'
			);
		}

		if ($bbcode_id != -1 && !($this->template_bitfield & (1 << $bbcode_id)))
		{
			return (isset($bbcode_hardtpl[$tpl_name])) ? $bbcode_hardtpl[$tpl_name] : FALSE;
		}

		if (empty($this->bbcode_template))
		{
			if (!($fp = @fopen($this->template_filename, 'rb')))
			{
				trigger_error('Could not load bbcode template');
			}
			$tpl = fread($fp, filesize($this->template_filename));
			@fclose($fp);

			// replace \ with \\ and then ' with \'.
			$tpl = str_replace('\\', '\\\\', $tpl);
			$tpl = str_replace("'", "\'", $tpl);
			
			// strip newlines and indent
			$tpl = preg_replace("/\n[\n\r\s\t]*/", '', $tpl);

			// Turn template blocks into PHP assignment statements for the values of $bbcode_tpl..
			$tpl = preg_replace('#<!-- BEGIN (.*?) -->(.*?)<!-- END (.*?) -->#', "\n" . "\$this->bbcode_template['\$1'] = \$this->bbcode_tpl_replace('\$1','\$2');", $tpl);

			$this->bbcode_template = array();
			eval($tpl);
		}

		return (isset($this->bbcode_template[$tpl_name])) ? $this->bbcode_template[$tpl_name] : ((isset($bbcode_hardtpl[$tpl_name])) ? $bbcode_hardtpl[$tpl_name] : FALSE);
	}
	
	function bbcode_tpl_replace($tpl_name, $tpl)
	{
		global $_CLASS;
		
		static $replacements = array(
			'quote_username_open'	=>	array('{USERNAME}'	=>	'$1'),
			'color'					=>	array('{COLOR}'		=>	'$1', '{TEXT}'			=>	'$2'),
			'size'					=>	array('{SIZE}'		=>	'$1', '{TEXT}'			=>	'$2'),
			'img'					=>	array('{URL}'		=>	'$1'),
			'flash'					=>	array('{WIDTH}'		=>	'$1', '{HEIGHT}'		=>	'$2', '{URL}'	=>	'$3'),
			'url'					=>	array('{URL}'		=>	'$1', '{DESCRIPTION}'	=>	'$2'),
			'email'					=>	array('{EMAIL}'		=>	'$1', '{DESCRIPTION}'	=>	'$2')
		);

		$tpl = preg_replace('/{L_([A-Z_]+)}/e', "(!empty(\$_CLASS['user']->lang['\$1'])) ? \$_CLASS['user']->lang['\$1'] : ucwords(strtolower(str_replace('_', ' ', '\$1')))", $tpl);

		if (!empty($replacements[$tpl_name]))
		{
			$tpl = strtr($tpl, $replacements[$tpl_name]);
		}

		return trim($tpl);
	}
	
	function bbcode_list($type)
	{
		if ($type == '')
		{
			$tpl = 'ulist_open_default';
			$type = 'default';
			$start = 0;
		}
		elseif ($type == 'i')
		{
			$tpl = 'olist_open';
			$type = 'lower-roman';
			$start = 1;
		}
		elseif ($type == 'I')
		{
			$tpl = 'olist_open';
			$type = 'upper-roman';
			$start = 1;
		}
		elseif (preg_match('#^(disc|circle|square)$#i', $type))
		{
			$tpl = 'ulist_open';
			$type = strtolower($type);
			$start = 1;
		}
		elseif (preg_match('#^[a-z]$#', $type))
		{
			$tpl = 'olist_open';
			$type = 'lower-alpha';
			$start = ord($type) - 96;
		}
		elseif (preg_match('#[A-Z]#', $type))
		{
			$tpl = 'olist_open';
			$type = 'upper-alpha';
			$start = ord($type) - 64;
		}
		elseif (is_numeric($type))
		{
			$tpl = 'olist_open';
			$type = 'arabic-numbers';
			$start = intval($chr);
		}
		else
		{
			$tpl = 'olist_open';
			$type = 'arabic-numbers';
			$start = 1;
		}

		return str_replace('{LIST_TYPE}', $type, $this->bbcode_tpl($tpl));
	}

	function bbcode_second_pass_code($type, $code)
	{
		// when using the /e modifier, preg_replace slashes double-quotes but does not
		// seem to slash anything else
		$code = str_replace('\"', '"', $code);

		switch ($type)
		{
			case 'php':
				// Not the english way, but valid because of hardcoded syntax highlighting
				if (strpos($code, '<span class="syntaxdefault"><br /></span>') === 0)
				{
					$code = substr($code, 41);
				}

			default:
				$code = str_replace("\t", '&nbsp; &nbsp;', $code);
				$code = str_replace('  ', '&nbsp; ', $code);
				$code = str_replace('  ', ' &nbsp;', $code);

				// remove newline at the beginning
				if ($code{0} == "\n")
				{
					$code = substr($code, 1);
				}
		}

		$code = $this->bbcode_tpl('code_open') . $code . $this->bbcode_tpl('code_close');

		return $code;
	}
}

?>