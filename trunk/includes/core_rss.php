<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal©	)								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
// Add channel support, rss_data[channel][item][item_branch]
// nested items

class core_rss
{
	var $rss_data = array();
	var $rss_info;
	var $feed_type = false;
	var $item_tags = array('title', 'link', 'description', 'author');
	var $channel_tags = array('title', 'link', 'description', 'author');
	var $send_cookie = false;

	var $error = '';
	var $item = 0;

	function setup($channel = false, $item_tags = false, $channel_tags = false)
	{
		$this->feed_type = $this->send_cookie = false;
		$this->rss_info = $this->rss_data = array();
		$this->item = 0;

		$this->item_tags = (is_array($item_tags)) ? $item_tags : array('title', 'link', 'description', 'author');
		$this->channel_tags = (is_array($channel_tags)) ? $channel_tags : array('title', 'link', 'description', 'author');
	}

	function get_rss($url, $items_limit = 10)
	{
		return $this->get_rss_array($url, $items_limit);
	}

	function get_rss_data_raw()
	{
		global $_CLASS;
		
		return array('data' => $this->rss_data, 'rss_other_data' => $this->rss_other_data);
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
		if ($loop == 3)
		{
			return false;
		}

		$loop++;
		$this->items_limit = $items_limit - 1;

		$parsed_url = array('host' => '', 'path' => '/', 'port' => 80, 'query' => '', 'user' => false, 'pass' => false);
		$parsed_url = array_merge($parsed_url, parse_url($url));

		if (!$parsed_url['host'])
		{
			$this->error = 'Host name error';
			return false;
		}

		if (!($this->fp = @fsockopen($parsed_url['host'], $parsed_url['port'], $errno, $errstr, 5)))
		{
			$this->error = "Can't connection";
			return false;
		}

		fwrite($this->fp, 'GET '.$parsed_url['path'].(($parsed_url['query']) ? '?'.$parsed_url['query'] : '')." HTTP/1.0\r\n");
		fwrite($this->fp, "User-Agent: Viperal CMS RSS Reader\r\n");

		if (extension_loaded('zlib'))
		{
			fwrite($this->fp, "Accept-Encoding: gzip;\r\n");
		}

		if (!empty($parsed_url['user']))
		{
			fwrite($this->fp, 'Authorization: BASIC '.base64_encode($parsed_url['user'].':'.$parsed_url['pass'])."\r\n");
		}

		if ($this->send_cookie && is_array($this->send_cookie))
		{
			$cookie = '';
			// wonder if this really works :-)
			foreach ($this->send_cookie as $name => $value)
			{
				$cookie .= "$name=$value;\r\n";
			}
			fwrite($this->fp, "Cookie: $cookie");
		}

		fwrite($this->fp, 'HOST: '.$parsed_url['host']."\r\n\r\n");

		$data = strtolower(trim(fgets($this->fp, 300)));

		if (strpos($data, '200') === false)
		{
			echo $data;

			if (strpos($data, '301') === true || strpos($data, '301') === true)
			{
				while (!empty($data))
				{
					$data = strtolower(trim(fgets($this->fp, 300)));

					if (strpos('location:', $data) == true)
					{
						$new_url = trim(eregi_replace('location:', '', $data));
						
						$this->Close_connection();
					
						if ($new_url != $url)
						{
							// I don't think I want to do this
							return $this->get_rss_array($new_url, $items_limit, $loop);
						}
						return false;
					}
				}
			}

			$this->Close_connection();
			return false;
		}

		$compressed = false;

		while (!empty($data))
		{
			$data = strtolower(trim(fgets($this->fp, 300)));
			
			if (strpos($data, 'content-type') !== false && strpos($data, 'xml') === false)
			{
				$this->Close_connection();
				$this->error = 'Document type is invalid';
				return false;
			}
			
			if (strpos($data, 'last-modified') !== false)
			{
				//
			}

			if (strpos($data, 'content-encoding') !== false && strpos($data, 'gzip') !== false)
			{
				$compressed = true;
			}
		}

		$this->rss_parser = xml_parser_create();
		xml_set_object( $this->rss_parser, $this );
		xml_set_element_handler($this->rss_parser, 'tag_open', 'tag_close');
		xml_set_character_data_handler($this->rss_parser, 'cdata');

		$this->item_open = false;
		$this->title_type = false;

		$data = '';

		while(!feof($this->fp))
		{
			$data .= fread($this->fp, 1024);
		}

		if ($compressed)
		{
			$data = gzinflate(substr($data,10));
		}

		$status = xml_parse($this->rss_parser, $data, true);

		if (!$status)
		{
			$error_code = xml_get_error_code($this->rss_parser);
			
			if ($error_code != XML_ERROR_NONE) // XML_ERROR_NO_MEMORY
			{
				$error_string = xml_error_string($error_code);
				$error_line = xml_get_current_line_number($this->rss_parser);
				$error_col = xml_get_current_column_number($this->rss_parser);

				$this->error = "$error_string at line $error_line, column $error_col";
				//echo $this->error;
			}
		}

		xml_parser_free($this->rss_parser);

		$this->Close_connection();
		return ($this->error) ? false : true;
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
			$elements = explode(':', $element, 2);
			$element = $elements[0];
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
				
				Case 'feed':
					$this->feed_type = 'ATOM';
					break;

				default:
					$this->Close_connection();
					die('unhandled type');
			}
			
			$attrs = array_change_key_case($attrs, CASE_LOWER);
			
			$this->rss_info['format'] = $element;
			$this->rss_info['version'] = (isset($attrs['version'])) ? $attrs['version'] : '';
			return;
		}

		if ($element == 'item' || $element == 'entry')
		{
			$this->item_open = true;
			return;
		}
		elseif ($element == 'channel' || $element == 'feed')
		{
			$this->channel_open = true;
			return;
		}

		if ($this->item_open)
		{
			if (in_array($element, $this->item_tags))
			{
				$this->title_type = $element;
			}
			return;
		}
		else
		{
			$this->title_type = $element;
		}
	}

	function cdata($parser, $cdata)
	{
		if ($this->title_type)
		{
			$cdata = htmlspecialchars(html_entity_decode($cdata));

			if ($this->item_open)
			{
				if (empty($this->rss_data[$this->item][$this->title_type]))
				{
					$this->rss_data[$this->item][$this->title_type] = '';
				}
				$this->rss_data[$this->item][$this->title_type] .= $cdata;
			}
			elseif ($this->channel_open)
			{
				if (empty($this->rss_info[$this->title_type]))
				{
					$this->rss_info[$this->title_type] = '';
				}
				$this->rss_info[$this->title_type] .= $cdata;
			}
		}
	}

	function tag_close($parser, $element)
	{
		$element = strtolower($element);
		
		if (strpos($element, ':' ))
		{
			list($element) = explode(':', $element, 2);
		}
			
		if ($element == 'item' || $element == 'entry')
		{
			$this->item_open = false;
			$this->item++;
		}
		elseif ($element == 'channel' || $element == 'feed')
		{
			$this->channel_open = false;
		}
		
		$this->title_type = false;
	}

	function Close_connection()
	{
		fwrite($this->fp, "Connection: close\r\n\r\n");
		fclose($this->fp);
	}
}