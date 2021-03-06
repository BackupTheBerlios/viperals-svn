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
// $Id: download.php,v 1.23 2004/09/17 09:11:31 acydburn Exp $
//
// FILENAME  : download.php
// STARTED   : Thu Apr 10, 2003
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------
if (!defined('VIPERAL'))
{
    die;
}

$download_id = request_var('id', 0);

// Thumbnails are not handled by this file by default - but for modders this should be interesting. ;)
$thumbnail = request_var('t', false);

// Start session management
$_CLASS['core_user']->add_lang('viewtopic');

if (!$download_id)
{
	trigger_error('NO_ATTACHMENT_SELECTED');
}

if (!$config['allow_attachments'] && !$config['allow_pm_attach'])
{
	trigger_error('ATTACHMENT_FUNCTIONALITY_DISABLED');
}

$sql = 'SELECT attach_id, in_message, post_msg_id, extension
	FROM ' . FORUMS_ATTACHMENTS_TABLE . "
	WHERE attach_id = $download_id";
$result = $_CLASS['core_db']->query_limit($sql, 1);

if (!($attachment = $_CLASS['core_db']->fetch_row_assoc($result)))
{
	trigger_error('ERROR_NO_ATTACHMENT');
}
$_CLASS['core_db']->free_result($result);

if ((!$attachment['in_message'] && !$config['allow_attachments']) || ($attachment['in_message'] && !$config['allow_pm_attach']))
{
	trigger_error('ATTACHMENT_FUNCTIONALITY_DISABLED');
}

$row = array();
if (!$attachment['in_message'])
{
	$sql = 'SELECT p.forum_id, f.forum_password, f.parent_id
		FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_FORUMS_TABLE . ' f
		WHERE p.post_id = ' . $attachment['post_msg_id'] . '
			AND p.forum_id = f.forum_id';
	$result = $_CLASS['core_db']->query_limit($sql, 1);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);
	$_CLASS['core_db']->free_result($result);

	if ($_CLASS['auth']->acl_gets('f_download', 'u_download', $row['forum_id']))
	{
		if ($row['forum_password'])
		{
			// Do something else ... ?
			login_forum_box($row);
		}
	}
	else
	{
		//trigger_error('SORRY_AUTH_VIEW_ATTACH');
	}
}
else
{
	$row['forum_id'] = 0;
	if (!$_CLASS['auth']->acl_get('u_pm_download') || !$config['auth_download_pm'])
	{
		trigger_error('SORRY_AUTH_VIEW_ATTACH');
	}
}

// disallowed ?
$extensions = array();
if (!extension_allowed($row['forum_id'], $attachment['extension'], $extensions))
{
	trigger_error(sprintf($_CLASS['core_user']->lang['EXTENSION_DISABLED_AFTER_POSTING'], $attachment['extension']));
}

if (!download_allowed())
{
	trigger_error($_CLASS['core_user']->lang['LINKAGE_FORBIDDEN']);
}

$download_mode = (int) $extensions[$attachment['extension']]['download_mode'];

// Fetching filename here to prevent sniffing of filename
$sql = 'SELECT attach_id, in_message, post_msg_id, extension, physical_filename, real_filename, mimetype
	FROM ' . FORUMS_ATTACHMENTS_TABLE . "
	WHERE attach_id = $download_id";
$result = $_CLASS['core_db']->query_limit($sql, 1);

if (!($attachment = $_CLASS['core_db']->fetch_row_assoc($result)))
{
	trigger_error('ERROR_NO_ATTACHMENT');
}
$_CLASS['core_db']->free_result($result);

$attachment['physical_filename'] = basename($attachment['physical_filename']);


if ($thumbnail)
{
	$attachment['physical_filename'] = 'thumb_' . $attachment['physical_filename'];
}
else
{
	// Update download count
	$sql = 'UPDATE ' . FORUMS_ATTACHMENTS_TABLE . ' 
		SET download_count = download_count + 1 
		WHERE attach_id = ' . $attachment['attach_id'];
	$_CLASS['core_db']->sql_query($sql);
}

// Determine the 'presenting'-method
if ($download_mode == PHYSICAL_LINK)
{
	if (!@is_dir($config['upload_path']))
	{
		trigger_error($_CLASS['core_user']->lang['PHYSICAL_DOWNLOAD_NOT_POSSIBLE']);
	}

	redirect($config['upload_path'] . '/' . $attachment['physical_filename']);
}
else
{
	send_file_to_browser($attachment, $config['upload_path'], $extensions[$attachment['extension']]['display_cat']);
	exit;
}


// ---------
// FUNCTIONS
//

function send_file_to_browser($attachment, $upload_dir, $category)
{
	global $_CLASS, $config;

	$filename = $upload_dir . '/' . $attachment['physical_filename'];

	if (!@file_exists($filename))
	{
		trigger_error($_CLASS['core_user']->lang['ERROR_NO_ATTACHMENT'] . '<br /><br />' . sprintf($_CLASS['core_user']->lang['FILE_NOT_FOUND_404'], $filename));
	}

	// Determine the Browser the User is using, because of some nasty incompatibilities.
	// borrowed from phpMyAdmin. :)
	$user_agent = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

	if (ereg('Opera(/| )([0-9].[0-9]{1,2})', $user_agent, $log_version))
	{
		$browser_version = $log_version[2];
		$browser_agent = 'opera';
	}
	else if (ereg('MSIE ([0-9].[0-9]{1,2})', $user_agent, $log_version))
	{
		$browser_version = $log_version[1];
		$browser_agent = 'ie';
	}
	else if (ereg('OmniWeb/([0-9].[0-9]{1,2})', $user_agent, $log_version))
	{
		$browser_version = $log_version[1];
		$browser_agent = 'omniweb';
    }
	else if (ereg('(Konqueror/)(.*)(;)', $user_agent, $log_version))
	{
		$browser_version = $log_version[2];
		$browser_agent = 'konqueror';
    }
	else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $user_agent, $log_version) && ereg('Safari/([0-9]*)', $user_agent, $log_version2))
	{
		$browser_version = $log_version[1] . '.' . $log_version2[1];
		$browser_agent = 'safari';
    }
	else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $user_agent, $log_version))
	{
		$browser_version = $log_version[1];
		$browser_agent = 'mozilla';
    }
	else
	{
		$browser_version = 0;
		$browser_agent = 'other';
    }

	// Correct the mime type - we force application/octetstream for all files, except images
	// Please do not change this, it is a security precaution
	if ($category == ATTACHMENT_CATEGORY_NONE && strpos($attachment['mimetype'], 'image') === false)
	{
		$attachment['mimetype'] = ($browser_agent == 'ie' || $browser_agent == 'opera') ? 'application/octetstream' : 'application/octet-stream';
	}

	if (@ob_get_length())
	{
		@ob_end_clean();
	}
	
	// Now the tricky part... let's dance
	header('Pragma: public');

	// Send out the Headers
	header('Content-Type: ' . $attachment['mimetype'] . '; name="' . $attachment['real_filename'] . '"');
	header('Content-Disposition: inline; filename="' . $attachment['real_filename'] . '"');

	// Now send the File Contents to the Browser
	$size = @filesize($filename);
	if ($size)
	{
		header("Content-length: $size");
	}
	$result = @readfile($filename);

	if (!$result)
	{
		trigger_error('Unable to deliver file.<br />Error was: ' . $php_errormsg, E_USER_WARNING);
	}
	if (!empty($_CLASS['core_cache']))
	{
		$_CLASS['core_cache']->unload();
	}
	
	$_CLASS['core_db']->sql_close();
	flush();
	exit;
}

function download_allowed()
{
	global $config, $_CLASS;

	if (!$config['secure_downloads'])
	{
		return true;
	}

	$url = (getenv('HTTP_REFERER')) ? trim(getenv('HTTP_REFERER')) : trim($_SERVER['HTTP_REFERER']);

	if (!$url)
	{
		return ($config['secure_allow_empty_referer']) ? true : false;
	}

	// Split URL into domain and script part
	$url = explode('?', str_replace(array('http://', 'https://'), array('', ''), $url));
	$hostname = trim($url[0]);
	unset($url);

	$allowed = ($config['secure_allow_deny']) ? false : true;
	$iplist = array();

	$ip_ary = gethostbynamel($hostname);

	foreach ($ip_ary as $ip)
	{
		if ($ip)
		{
			$iplist[] = $ip;
		}
	}
	
	// Check for own server...
	if (preg_match('#^.*?' . $config['server_name'] . '.*?$#i', $hostname))
	{
		$allowed = true;
	}
	
	// Get IP's and Hostnames
	if (!$allowed)
	{
		$sql = 'SELECT site_ip, site_hostname, ip_exclude
			FROM ' . SITELIST_TABLE;
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$site_ip = trim($row['site_ip']);
			$site_hostname = trim($row['site_hostname']);

			if ($site_ip)
			{
				foreach ($iplist as $ip)
				{
					if (preg_match('#^' . str_replace('*', '.*?', $site_ip) . '$#i', $ip))
					{
						if ($row['ip_exclude'])
						{
							$allowed = ($config['secure_allow_deny']) ? false : true;
							break 2;
						}
						else
						{
							$allowed = ($config['secure_allow_deny']) ? true : false;
						}
					}
				}
			}

			if ($site_hostname)
			{
				if (preg_match('#^' . str_replace('*', '.*?', $site_hostname) . '$#i', $hostname))
				{
					if ($row['ip_exclude'])
					{
						$allowed = ($config['secure_allow_deny']) ? false : true;
						break;
					}
					else
					{
						$allowed = ($config['secure_allow_deny']) ? true : false;
					}
				}
			}
		}

		$_CLASS['core_db']->free_result($result);
	}
	
	return $allowed;
}

//
// FUNCTIONS
// ---------

?>