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

/*
	Protocol resources:

	http://cr.yp.to/smtp.html
	http://www.faqs.org/rfcs/rfc821.html
*/
class smtp_mailer
{
	var $connection;
	var $host;
	var $port;

	/*
		PHP5 destructor
	*/
	function __destruct()
	{
		$this->disconnect();
	}
   
	function connect($host, $port = 25)
	{
		$port = ((int) $port) ? (int) $port : 25;

		$this->connection = fsockopen($host, $port, $errno, $errstr, 15)

		if (!$this->connection)
		{
			$this->error = 'Could not connect';
			return false;
		}
		
		$this->host = $host;
		return $this->connection;
	}

	function disconnect()
	{
		if (!$this->connection)
		{
			return;
		}

		fwrite($this->connection, "QUIT\r\n");
		fclose($this->connection);

		$this->connection = false;
	}
	
	// If login fails we disconnect, may do it differently later on
	function login($user, $password)
	{
		if (!$this->connection)
		{
			$this->error = 'No connection';
			return false;
		}
 	    
  	    $this_host = gethostbyaddr(($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : gethostbyname($_SERVER['SERVER_NAME']));

		//fputs($this->connection, "HELO [{$this->host}] \r\n"); // ip format
		fwrite($this->connection, "EHLO $this_host \r\n");

		if (!$this->check_response(250))
		{
			fwrite($this->connection, "HELO $this_host \r\n");

			if (!$this->check_response(250))
			{
				$this->disconnect();
				return false;
			}
		}

		fwrite($this->connection, "AUTH LOGIN\r\n");

		if (!$this->check_response(334))
		{
			// 503 AUTH previously succeeded
			if (substr($this->response, 0, 3) == 503)
			{
				return true;
			}

			$this->disconnect();
			return false;
		}

		fwrite($this->connection, base64_encode($user)."\r\n");

		if (!$this->check_response(334))
		{
			$this->disconnect();
			return false;
		}

		fwrite($this->connection, base64_encode($password)."\r\n");

		if (!$this->check_response(235))
		{
			$this->disconnect();
			return false;
		}

		return true;
	}

	function send_mail()
	{
		global $_CORE_CONFIG;

		fwrite($this->connection, 'MAIL FROM: <'.$_CORE_CONFIG['email']['site_mail'].'>'."\r\n");

		if (!$this->check_response(250))
		{
			$this->disconnect();
			return false;
		}

		$to_header = array();

		// Let tell the server who to send this to.
		foreach ($this->recipients as $email)
		{
			$email = trim($email);
			$name = false;
			
			if (is_array($email))
			{
				$name = $email['name'];
				$email = $email['address'];
			}

			fwrite($this->connection, 'RCPT TO: <'.$email.">\r\n");

			if (!$this->check_response(250))
			{
				$to_header[] = ($name) ? "$name <$email>" : "<$email>";
			}
		}

		// Was any recipients accepted ?
		if (empty($to_header))
		{
			$this->disconnect();
			return false;
		}

		// We start sending from here
		fwrite($this->connection, "DATA\r\n");

		if (!$this->check_response(354))
		{
			$this->disconnect();
			return false;
		}

		//fwrite($this->connection, "To: ".implode(', ', $to_header)."\r\n");
		$sending_header = ($this->subject) ? 'Subject: '.$this->subject."\r\n" : ''; // should add something here
		$sending_header .= "To: ".implode(', ', $to_header)."\r\n";
		$sending_header .= $this->headers."\r\n\r\n";

// Do my html and plain text mail thing, change it to a function maybe ?
		fwrite($this->connection, $sending_header.$this->message."\r\n");

		fwrite($this->connection, '.'."\r\n");

		$status = $this->check_response(250)
		$this->disconnect();

		return $status;
	}

	function check_response($code, $full = false)
	{
		$this->response = '';

		while ($buffer = fgets($this->connection, 256))
		{
			$this->response .= $buffer;

			//$buffer{strlen($this->response) - 1} == ' ' .... .maybe just check the ending ?
			if ((!$full && strlen($this->response) >= 3) || substr($buffer, 3, 1) != ' ')
			{
				break;
			}
		}

		$this->response = trim($this->response);

		if ($code && substr($this->response, 0, 3) != $code)
		{
			return false;
		}

		return true;
	}
}
?>