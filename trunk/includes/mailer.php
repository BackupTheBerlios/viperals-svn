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

class core_mailer
{
	var $from;
	var $reply_to;
	var $message;
	var $subject;
	var $bcc;
	var $to;
	var $extra_headers;

	function setup()
	{	
		$this->html = false;
		$this->message = $this->subject = '';
		$this->bcc = $this->to = $this->from = $this->reply_to = $this->extra_headers = array();
	}

	function to($address, $name = '')
	{
		$this->address_arrays['to'][] = array(
				'address'	=> $address
				'name'		=> $name
			);
	}

	function cc($address, $name = '')
	{
		$this->address_arrays['cc'][] = array(
				'address'	=> $address
				'name'		=> $name
			);
	}

	function bcc($address, $name = '')
	{
		$this->address_arrays['bcc'][] = array(
				'address'	=> $address
				'name'		=> $name
			);
	}

	function reply_to($address, $name = '')
	{
		$this->address_arrays['reply_to'] = array(
			'address'	=> $address
			'name'		=> $name
		);
	}

	function from($address, $name = '')
	{
		$this->address_arrays['from'] = array(
			'address'	=> $address
			'name'		=> $name
		);
	}

	function subject($subject)
	{
		$this->subject = trim($subject);
	}

	function extra_header($headers)
	{
		$this->extra_headers[] = trim($headers);
	}

	function format_address($address)
	{
		foreach ($address as $array)
		{
			$array['name'] = trim($array['name']);

			$formatted[] = (($array['name']) ? $array['name'] : '') . ' <' . trim($array['address']) . '> ';
		}

		return implode(', ', $formatted);
	}

	function send()
	{
		global $_CLASS, $_CORE_CONFIG;

		$to = $cc = $bcc = $reply_to = $from = '';

		foreach ($this->address_arrays as $type => $address)
		{
			$$type = format_address($address);
		}

		$_CORE_CONFIG['email']['site_mail'] = trim($_CORE_CONFIG['email']['site_mail'])

		if (!$from)
		{
			// modify_lines ?
			$from = '<' . $_CORE_CONFIG['email']['site_mail'] . '>';
		}

		$headers = "From: $from \n";
		$headers .= 'Date: ' . gmdate('D, d M Y H:i:s T')) . "\n";
		$headers .= isset($cc) ? "Cc: $cc\n" : '';
		$headers .= isset($bcc) ? "Bcc: $bcc\n" : ''; 
		$headers .= isset($reply_to) ? "Reply-to: $reply_to \n" : '';
		$headers .= 'Return-Path: <' . $_CORE_CONFIG['email']['site_mail'] . ">\n";
		$headers .= 'Sender: <' .  . ">\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= 'Message-ID: <' . ((function_exists('sha1')) ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true))) . "@" . $_CORE_CONFIG['global']['site_name'] . ">\n";

		if ($this->html)
		{
			// multipart
			$text_boundary = trim('----part_'.((function_exists('sha1')) ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true)));

			$headers .= "Content-Type: multipart/alternative;\nboundary=$text_boundary\n"; 
			$headers .= 'Content-Type: text/html; charset='.$this->encoding."\n";
			
			// Plain text
			$message = "\n$text_boundary\n";
			$message .= 'Content-type: text/plain; charset='.$this->encoding."\n"; //format=
			$message .= "Content-transfer-encoding: 8bit\n";
			$message .= "\n".html_entity_decode(strip_tags(preg_replace('#<br */?>#i', "/n", $this->message)), ENT_QUOTES)."\n";

			// HTML
			$message = "\n$text_boundary\n";
			$message .= 'Content-type: text/html; charset='.$this->encoding."\n";
			$message .= "Content-transfer-encoding: 8bit\n";
			$message .= "\n".$this->message."\n";

			$message = "\n$text_boundary\n";
		}
		else
		{
			$headers .= 'Content-type: text/plain; charset='.$this->encoding."\n";
			$headers .= "Content-transfer-encoding: 8bit\n";
			$message .= "\n".strip_tags(preg_replace('/<br[/]?>/', "/n", $this->message)."\n";
		}

		if (function_exists($_CORE_CONFIG['email']['email_function_name'])
		{
			$result = $_CORE_CONFIG['email']['email_function_name']($to, $this->subject, $message, $headers);

			if (!$result)
			{
				return false;
			}
			
			return true;
		}

		return false;
	}
}

class smtp_mailer
{
	var $connection;
	var $host;
	var $port;

	function connect($host, $port)
	{
		
		$this->connection = fsockopen($host, $port, $errno, $errstr, 15)

		if (!$this->connection)
		{
			$this->error = 'Could not connect';
			return false;
		}
		
		$this->host = $host;
		return $this->connection;
	}

	function login($user, $password)
	{
		if (!$this->connection)
		{
			$this->error = 'No connection';
			return false;
		}
//http://cr.yp.to/smtp/ehlo.html
		fputs($this->connection, "EHLO {$this->host} \r\n");
		//fputs($this->connection, "HELO [{$this->host}] \r\n"); // ip format
		
//220 heaven.af.mil ESMTP
		if (!($response = $this->check_response(250)))
		{
			fputs($this->connection, "HELO {$this->host} \r\n");

			if (!($response = $this->check_response(250)))
			{
				return false;
			}
		}

	}

	function check_response($code)
	{
		$response = '';

		while ($buffer = fgets($this->connection, 256))
		{
			if (substr($buffer, 3, 1) != ' ')
			{
				break;
			}
			$response .= $buffer;
		}

		$response = trim($response);

		if ($code && substr($response, 0, 3) != $code)
		{
			return false;
		}

		return $response;
	}
}
?>