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
// Add channel support, rss_data[channel][item][item_branch]
//$rss = new core_rss;
//$rss->get_rss('http://rss.cpgnuke.com/news.php');
//Yes yes i still love you :-)
//print_r($rss->rss_data);
//print_r($rss->rss_info);


class core_rss
{

	var $rss_data = array();
	var $rss_expire;
	var $rss_other_data;
	var $feed_type = false;
	var $item_tags = array('title', 'link', 'description', 'author');
	var $channel_tags = array('title', 'link', 'description', 'author');

	function get_rss($url = false, $data_Value = false, $items_limit = 10)
	{
		$this->rss_data = array();
		$this->rss_expire = 0;
		$this->rss_info = array();

		if ($data_Value)
		{
			@eval('$data_Value='.$data_Value.';');
			
			if (is_array($data_Value) && ($data_Value['expire'] == 0 || $data_Value['expire'] > time()))
			{
				
				$this->rss_data = $data_Value['data'];
				$this->rss_expire = $data_Value['expire'];
				$this->rss_other_data = $data_Value['rss_other_data'];

				unset($data_Value);
								
				return true;
			}
			unset($data_Value);
		}
		
		if ($url && $this->get_rss_array($url, $items_limit))
		{
			return true;
		}
		
		return false;
	}
	
	function get_rss_data_raw($expire = 0)
	{
		global $_CLASS;
		
		$expire = time() + $expire;
		return $_CLASS['core_cache']->format_array(array('data' => $this->rss_data, 'expire' => $expire, 'rss_other_data' => $this->rss_other_data));
	}
	
	function get_rss_data($line = false)
	{
		if ($line)
		{
			if (!empty($this->rss_data[$line]))
			{
				return $this->rss_data[$line];
			}
			return false;
		}
		return array_shift($this->rss_data);
	}
	
	function get_rss_array($url, $items_limit = 10, $loop = 1)
	{
		if ($loop == 5)
		{
			return false;
		}
		
		$loop++;
		$this->items_limit = $items_limit - 1;
		
		$parsed_url = array('host' => '', 'path' => '/', 'port' => 80, 'query' => '');
		$parsed_url = array_merge($parsed_url, parse_url($url));

		if (!$parsed_url['host'])
		{
			return false;
		}
		
		
		if ($this->fp = fsockopen($parsed_url['host'], $parsed_url['port'], $errno, $errstr, 15))
		{
			fputs($this->fp, 'GET '.$parsed_url['path'].$parsed_url['query']." HTTP/1.0\r\n");
			fputs($this->fp, "User-Agent: Viperal CMS RSS Reader\r\n");
			
			/*if (extension_loaded('zlib'))
			{
				fputs($this->fp, "Accept-Encoding: gzip;q=0.9\r\n");
			}*/
		
			fputs($this->fp, "HOST: $parsed_url[host]\r\n\r\n");
			
			$data = rtrim(fgets($this->fp, 300));
			
			preg_match('#.* ([0-9]+) (.*)#i', $data, $head);
			// 301 = Moved Permanently, 302 = Found, 307 = Temporary Redirect
			if ($head[1] == 301 || $head[1] == 307)
			{
			
				while (!empty($data))
				{
					$data = rtrim(fgets($this->fp, 300)); // read lines
					if (ereg('Location: ', $data))
					{
						$new_url = trim(eregi_replace('Location: ', '', $data));
						
						$this->Close_connection();
					
						if ($new_url != $url)
						{
							return $this->get_rss_array($new_url, $items_limit, $loop);
						}
						
						return false;
					}
				}
				
				$this->Close_connection();
				
				return false;
				
			} elseif ($head[1] != 200) {
			
				$this->Close_connection();
				return false;
			}
			
			$file['utf8'] = $compressed = false;
		
			// Get Headers
			while (!empty($data))
			{
				$data = strtolower(rtrim(fgets($this->fp, 300))); // read lines
				
				// May need a better way than this
				if (strpos($data, 'content-type:') !== false && strpos($data, 'text/xml') === false)
				{
					$this->Close_connection();					
					return false;
				}
				
				if (strpos($data, 'last-modified:') !== false)
				{
					// Check the last mod. date, so maybe we can just update the db update time.
					// echo trim(eregi_replace('Last-Modified: ', '', $data));
				}
					//	if (eregi('Content-Encoding: gzip', $data) || eregi('Content-Encoding: x-gzip', $data))
		
				/*if (strpos($data, 'content-encoding:') !== false && strpos($data, 'gzip') !== false)
				{
					$compressed = true;
				}*/
			}
	
			$this->rss_parser = xml_parser_create();
			xml_set_object( $this->rss_parser, $this );
			xml_set_element_handler($this->rss_parser, 'tag_open', 'tag_close');
			xml_set_character_data_handler($this->rss_parser, 'cdata');

			$this->feed_type = false;
			$this->item = 0;
			$this->itemopen = false;
			$this->title_type = false;
			$this->rss_data = array();
			$this->rss_expire = 0;
			$this->rss_info = array();
		
			while(!feof($this->fp))
			{
				//$data = fread($this->fp, 1024);
				if ($this->item > $this->items_limit)
				{
					break;
				}
				
				$status = xml_parse($this->rss_parser, fread($this->fp, 1024), false);
				
				if (!$status)
				{
					$this->Close_connection();
					return false;
					/*&$errorcode = xml_get_error_code( $this->parser );
					if ( $errorcode != XML_ERROR_NONE )
					{
						$xml_error = xml_error_string( $errorcode );
						$error_line = xml_get_current_line_number($this->parser);
						$error_col = xml_get_current_column_number($this->parser);
						$errormsg = "$xml_error at line $error_line, column $error_col";
	
						$this->error( $errormsg );
					}*/
				}
			}
			
			/*if ($compressed)
			{
				$data = gzinflate(substr($data,10,-4));
			}*/

			xml_parser_free($this->rss_parser);
			
			$this->Close_connection();
			return true;
		}
	}
		
	function tag_open($parser, $element, $attrs)
	{
		if ($this->item > $this->items_limit)
		{
			$this->title_type = false;
			return;
		}
		
		$element = strtolower($element);
		
		if (strpos($element, ':'))
		{
			list($element) = explode(':', $element, 2);
		}
			
		if (!$this->feed_type)
		{
			switch($element)
			{
				Case 'rdf':
					$this->feed_type = 'RSS';
					break;
				
				Case 'rss':
					$this->feed_type = 'RSS';
					break;
				
				default:
					$this->Close_connection();
					die('unhandled type');
			}
			
			$attrs = array_change_key_case($attrs, CASE_LOWER);
			
			$this->rss_info['format'] = $element;
			$this->rss_info['version'] = $attrs['version'];
			
			return;
		}
		if ($element == 'item')
		{
			$this->itemopen = true;
			return;
		}
		
		if ($this->itemopen)
		{
			if (in_array($element, $this->item_tags))
			{
				$this->title_type = $element;
				//echo $this->title_type.'<br />';
			} else {
				$this->title_type = false;
			}
		} else  {
			$this->title_type = $element;
		}
	}
	
	function cdata($parser, $cdata)
	{
		if ($this->title_type)
		{
			// Look into whys of romoving unwanted code.
			// No html/jave/etc code should run with when the rss is parsed
			$cdata = htmlspecialchars(html_entity_decode($cdata));

			if ($this->itemopen)
			{
				$this->rss_data[$this->item][$this->title_type] = $cdata;
			} else {
				$this->rss_info[$this->title_type] = $cdata;
			}
			
			// We can do "if (trim($cdata))", but this is better
			$this->title_type = false;
		}
	}
	
	function tag_close($parser, $element)
	{
		$element = strtolower($element);
		if (strpos( $element, ':' ))
		{
			list($element) = explode(':', $element, 2);
		}
			
		if ($element == 'item')
		{
			$this->itemopen = false;
			$this->item++;
		}
	}
	
	function Close_connection()
	{
			fputs($this->fp,"Connection: close\r\n\r\n");
			fclose($this->fp);
	}
}