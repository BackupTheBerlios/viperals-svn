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
		global $_CORE_CONFIG;

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

		$headers = 'Date: ' . gmdate('D, d M Y H:i:s T')) . "\n";
		$headers .= "From: $from \n";
		$headers .= isset($cc) ? "Cc: $cc\n" : '';
		$headers .= isset($bcc) ? "Bcc: $bcc\n" : ''; 
		$headers .= isset($reply_to) ? "Reply-to: $reply_to \n" : '';
		$headers .= 'Return-Path: <' . $_CORE_CONFIG['email']['site_mail'] . ">\n";
		$headers .= 'Sender: <' .  . ">\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= 'Message-ID: <' . md5(unique_id()) . "@" . $_CORE_CONFIG['global']['site_name'] . ">\n";

		if ($this->html)
		{
			// multipart
			$text_boundary = trim('----part_'.md5(unique_id()));

			$headers .= "Content-Type: multipart/alternative;\nboundary=$text_boundary\n"; 
			$headers .= 'Content-Type: text/html; charset='.$this->encoding."\n";
			
			// Plain text
			$message = "\n$text_boundary\n";
			$message .= 'Content-type: text/plain; charset='.$this->encoding."\n"; //format=
			$message .= "Content-transfer-encoding: 7bit\n";
			$message .= "\n".strip_tags(preg_replace('/<br[/]?>/', "/n", $this->message)."\n";

			// HTML
			$message = "\n$text_boundary\n";
			$message .= 'Content-type: text/html; charset='.$this->encoding."\n";
			$message .= "Content-transfer-encoding: 7bit\n";
			$message .= "\n".$this->message."\n";

			$message = "\n$text_boundary\n";
		}
		else
		{
			$headers .= 'Content-type: text/plain; charset='.$this->encoding."\n";
			$headers .= "Content-transfer-encoding: 7bit\n";
			$message .= "\n".strip_tags(preg_replace('/<br[/]?>/', "/n", $this->message)."\n";
		}

		if (function_exists($_CORE_CONFIG['email']['email_function_name'])
		{
			$result = $_CORE_CONFIG['email']['email_function_name']($to, $this->subject, $message, $headers);
			//ini_set('SMTP', );

			if (!$result)
			{
				return false;
			}
			
			return true;
		}

		return false;
	}
}

?>