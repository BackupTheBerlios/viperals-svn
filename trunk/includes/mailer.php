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

class core_mailer
{
	var $message;
	var $subject;
	var $address_arrays = array('to' => array(), 'cc' => array(), 'bcc' => array());
	var $extra_headers;

	var $encoding = 'UTF-8';
	
	// Needed for some windows sendmail emulator/ SMTP
	// Set to false is you have problems sending
	var $named_addresses = true;
	var $error = '';

	function core_mailer($html = false)
	{	
		$this->html = false;
	}

	function to($address, $name = '')
	{
		$this->address_arrays['to'][] = array(
				'address'	=> $address,
				'name'		=> $name
			);
	}

	function cc($address, $name = '')
	{
		$this->address_arrays['cc'][] = array(
				'address'	=> $address,
				'name'		=> $name
			);
	}

	function bcc($address, $name = '')
	{
		$this->address_arrays['bcc'][] = array(
				'address'	=> $address,
				'name'		=> $name
			);
	}

	function reply_to($address, $name = '')
	{
		$this->address_arrays['reply_to'] = array(
			'address'	=> $address,
			'name'		=> $name
		);
	}

	function from($address, $name = '')
	{
		$this->address_arrays['from'] = array(
			'address'	=> $address,
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
//"=?UTF-8?B?VmlwZXJhbA==?="
			$formatted[] = ($array['name'] && $this->named_addresses) ?  '"' . mail_encode($array['name'], $this->encoding) . '" <' . $array['address'] . '>' : $array['address'];
		}
		return implode(', ', $formatted);
	}

	function send()
	{
		global $_CLASS, $_CORE_CONFIG;

		$to = $cc = $bcc = $reply_to = $from = false;

		foreach ($this->address_arrays as $type => $address)
		{
			if (empty($address))
			{
				continue;
			}

			$$type = $this->format_address($address);
		}

		$_CORE_CONFIG['email']['site_mail'] = trim($_CORE_CONFIG['email']['site_mail']);

		if (!$from)
		{
			// modify_lines ?
			$from = '<' . $_CORE_CONFIG['email']['site_mail'] . '>';
		}

		$headers[] = "From: $from";
		$headers[] = 'Date: ' . gmdate('D, d M Y H:i:s T');

		if (!$_CORE_CONFIG['email']['smtp'])
		{
			if ($cc)
			{
				$headers[] = "Cc: $cc";
			}

			if ($bcc)
			{
				$headers[] = "Bcc: $bcc";
			}
		}

		if ($reply_to)
		{
			$headers[] = "Reply-to: $reply_to";
		}

		$headers[] = 'Return-Path: <' . $_CORE_CONFIG['email']['site_mail'] . ">";
		$headers[] = 'Sender: <' . $_CORE_CONFIG['email']['site_mail'] . ">";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = 'Message-ID: <' . ((function_exists('sha1')) ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true))) . "@" . $_CORE_CONFIG['global']['site_name'] . ">";

		if ($this->html)
		{
			// multipart
			$text_boundary = trim('--'.((function_exists('sha1')) ? sha1(uniqid(mt_rand(), true)) : md5(uniqid(mt_rand(), true))));

			$headers[] = 'Content-Type: multipart/alternative;';
			$headers[] = "\tboundary=\"$text_boundary\"";

			$message .= 'This is a multi-part message in MIME format, Please use a MIME-compatible client';

			// Plain text
			$message .= "\n\n$text_boundary\n";
			$message .= 'Content-Type: text/plain; charset='.$this->encoding."\n"; //format=
			$message .= "Content-Transfer-Encoding: 8bit\n\n";
			$message .= html_entity_decode(strip_tags(preg_replace('#<br */?>#i', "\n", modify_lines($this->message))), ENT_QUOTES);

			// HTML
			$message .= "\n\n$text_boundary\n";
			$message .= 'Content-Type: text/html; charset='.$this->encoding."\n";
			$message .= "Content-Transfer-Encoding: 8bit\n\n";
			$message .= $this->message;

			$message .= "\n\n$text_boundary--\n";
		}
		else
		{
			$headers[] = 'Content-Type: text/plain; charset='.$this->encoding;
			$headers[] = 'Content-Transfer-Encoding: 8bit';
			$message = "\n".strip_tags(preg_replace('#<br */?>#i', "\n", modify_lines($this->message)))."\n";
		}

		if ($_CORE_CONFIG['email']['smtp'])
		{
			$smtp = new smtp_mailer;
			if ($connect = $smtp->connect($_CORE_CONFIG['email']['smtp_host'], $_CORE_CONFIG['email']['smtp_port']))
			{
				$login = $smtp->login($_CORE_CONFIG['email']['smtp_username'], $_CORE_CONFIG['email']['smtp_password']);
			}

			if (!$connect || !$login)
			{
				$this->error = $smtp->error;
				return false;
			}

			$smtp->subject = $this->subject;
			$smtp->headers = $headers;
			$smtp->message = $message;
			$smtp->recipients = array_merge($this->address_arrays['to'], $this->address_arrays['cc'] , $this->address_arrays['bcc']);

			if (!$smtp->send_mail())
			{
				$this->error = $smtp->error;
				return false;
			}

			return true;			
		}

		if (function_exists($_CORE_CONFIG['email']['email_function_name']))
		{//mb_send_mail
			$result = $_CORE_CONFIG['email']['email_function_name']($to, $this->subject, $message, implode("\n", $headers));

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
	var $error;

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

		//$host = 'tls://smtp.gmail.com';
		//$port = 587;

		$this->connection = fsockopen($host, $port, $errno, $errstr, 5);

		if (!$this->connection || !$this->check_response(220))
		{
			$this->disconnect();

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
		$user = ''; $password='';
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
				$this->error = "Server doesn't like you";
				return false;
			}
		}
		
		if (!$user || !$password)
		{
			return true;
		}

		//base64_encode($username . " " . bin2hex(mhash(MHASH_MD5,$challenge,$password));
		fwrite($this->connection, "AUTH LOGIN\r\n");

		if (!$this->check_response(334))
		{
			// 503 AUTH previously succeeded
			if (substr($this->response, 0, 3) == 503)
			{
				return true;
			}

			$this->disconnect();
			$this->error = "Auth not supported";
			return false;
		}

		fwrite($this->connection, base64_encode($user)."\r\n");

		if (!$this->check_response(334))
		{
			$this->disconnect();
			$this->error = "User name denied";
			return false;
		}

		fwrite($this->connection, base64_encode($password)."\r\n");

		if (!$this->check_response(235))
		{
			$this->disconnect();
			$this->error = "Password denied";
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
			$name = false;
			
			if (is_array($email))
			{
				$name = $email['name'];
				$email = $email['address'];
			}

			$email = trim($email);

			fwrite($this->connection, 'RCPT TO: <'.$email.">\r\n");

			if ($this->check_response(250))
			{
				$to_header[] = ($name) ? "$name <$email>" : "<$email>";
			}
		}

		// Was any recipients accepted ?
		if (empty($to_header))
		{
			$this->error = 'Nothing to send';
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

		fwrite($this->connection, 'Subject: '.$this->subject."\r\n");
		fwrite($this->connection, 'To: '.implode(', ', $to_header)."\r\n");
		fwrite($this->connection, implode("\r\n", $this->headers)."\r\n\r\n");

		fwrite($this->connection, $this->message."\r\n");

		fwrite($this->connection, '.'."\r\n");

		$status = $this->check_response(250);
		$this->disconnect();

		return $status;
	}

	function check_response($code)
	{
		$this->response = '';

		while ($buffer = fgets($this->connection, 515))
		{
			$this->response .= $buffer;

			if (substr($buffer, 3, 1) == ' ')
			{
				break;
			}
		}

		$this->response = trim($this->response);
		echo $this->response.'<br/>';

		if ($code && substr($this->response, 0, 3) != $code)
		{
			return false;
		}

		return true;
	}
}

// Encodes the given string for proper display for this encoding ... nabbed 
// from php.net and modified by phpBB.
function mail_encode($str, $encoding)
{
	if ($encoding == '')
	{
		return $str;
	}

	// define start delimimter, end delimiter and spacer
	$end = "?=";
	$start = "=?$encoding?B?";
	$spacer = "$end\r\n $start";

	// determine length of encoded text within chunks and ensure length is even
	$length = 75 - strlen($start) - strlen($end);
	$length = floor($length / 2) * 2;

	// encode the string and split it into chunks with spacers after each chunk
	$str = chunk_split(base64_encode($str), $length, $spacer);

	// remove trailing spacer and add start and end delimiters
	$str = preg_replace('#' . preg_quote($spacer) . '$#', '', $str);

	return $start . $str . $end;
}

?>