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
// $Id: functions_posting.php,v 1.124 2004/09/17 09:11:48 acydburn Exp $
//
// FILENAME  : functions_posting.php
// STARTED   : Sun Jul 14, 2002
// COPYRIGHT : � 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Fill smiley templates (or just the variables) with smileys, either in a window or inline
function generate_smilies($mode, $forum_id)
{
	global $SID, $_CLASS, $db, $config;
	global $phpEx;

	if ($mode == 'window')
	{
		if ($forum_id)
		{
			$sql = 'SELECT forum_style
				FROM ' . FORUMS_TABLE . "
				WHERE forum_id = $forum_id";
			$result = $db->sql_query_limit($sql, 1);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		
			$_CLASS['core_user']->setup('posting', (int) $row['forum_style']);
		}
		else
		{
			$_CLASS['core_user']->setup('posting');
		}

		page_header($_CLASS['core_user']->lang['SMILIES']);
		//$template->set_filenames(array(
		//	'body' => 'forums/posting_smilies.html')
		//);
	}

	$display_link = false;
	if ($mode == 'inline')
	{
		$sql = 'SELECT smiley_id
			FROM ' . SMILIES_TABLE . '
			WHERE display_on_posting = 0';
		$result = $db->sql_query_limit($sql, 1, 0, 3600);

		if ($row = $db->sql_fetchrow($result))
		{
			$display_link = true;
		}
		$db->sql_freeresult($result);
	}

	$sql = 'SELECT *
		FROM ' . SMILIES_TABLE . 
		(($mode == 'inline') ? ' WHERE display_on_posting = 1 ' : '') . '
		GROUP BY smiley_url
		ORDER BY smiley_order';
	$result = $db->sql_query($sql, 3600);

	while ($row = $db->sql_fetchrow($result))
	{
		$_CLASS['core_template']->assign_vars_array('smiley', array(
			'SMILEY_CODE' 	=> $row['code'],
			'SMILEY_IMG' 	=> $config['smilies_path'] . '/' . $row['smiley_url'],
			'SMILEY_WIDTH'  => $row['smiley_width'],
			'SMILEY_HEIGHT' => $row['smiley_height'],
			'SMILEY_DESC'   => $row['emotion'])
		);
	}
	$db->sql_freeresult($result);

	if ($mode == 'inline' && $display_link)
	{
		$_CLASS['core_template']->assign(array(
			'S_SHOW_SMILEY_LINK' 	=> true,
			'U_MORE_SMILIES' 		=> generate_link('Forums&amp;file=posting&amp;mode=smilies&amp;f='.$forum_id))
		);
	}

	if ($mode == 'window')
	{
		page_footer();
		$_CLASS['core_template']->display('forums/posting_smilies.html');
	}
}

// Update Last Post Informations
function update_last_post_information($type, $id)
{
	global $db;

	$update_sql = array();

	$sql = 'SELECT MAX(p.post_id) as last_post_id
		FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
		WHERE p.topic_id = t.topic_id
			AND p.post_approved = 1
			AND t.topic_approved = 1
			AND p.{$type}_id = $id";
			
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);

	if ((int) $row['last_post_id'])
	{
		$sql = 'SELECT p.post_id, p.poster_id, p.post_time, u.username, p.post_username
			FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
			WHERE p.poster_id = u.user_id
				AND p.post_id = ' . $row['last_post_id'];
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$update_sql[] = $type . '_last_post_id = ' . (int) $row['post_id'];
		$update_sql[] =	$type . '_last_post_time = ' . (int) $row['post_time'];
		$update_sql[] = $type . '_last_poster_id = ' . (int) $row['poster_id'];
		$update_sql[] = "{$type}_last_poster_name = '" . (($row['poster_id'] == ANONYMOUS) ? $db->sql_escape($row['post_username']) : $db->sql_escape($row['username'])) . "'";
	}
	else if ($type == 'forum')
	{
		$update_sql[] = 'forum_last_post_id = 0';
		$update_sql[] =	'forum_last_post_time = 0';
		$update_sql[] = 'forum_last_poster_id = 0';
		$update_sql[] = "forum_last_poster_name = ''";
	}

	return $update_sql;
}

function upload_attachment($form_name, $forum_id, $local = false, $local_storage = '', $is_message = false)
{
	global $_CLASS, $site_file_root, $config, $db;

	$filedata = array();
	$filedata['error'] = array();

	include_once($site_file_root. 'includes/forums/functions_upload.php');
	$upload = new fileupload();
	
	if (!$local)
	{
		$filedata['post_attach'] = ($upload->is_valid($form_name)) ? true : false;
	}
	else
	{
		$filedata['post_attach'] = true;
	}

	if (!$filedata['post_attach'])
	{
		$filedata['error'][] = 'No filedata found';
		return $filedata;
	}

	$extensions = array();
	obtain_attach_extensions($extensions, $forum_id);

	$upload->set_allowed_extensions(array_keys($extensions['_allowed_']));

	if ($local)
	{
		$file = $upload->local_upload($local_storage);
	}
	else
	{
		$file = $upload->form_upload($form_name);
	}

	if ($file->init_error)
	{
		$filedata['post_attach'] = false;
		return $filedata;
	}

	$cat_id = (isset($extensions[$file->get('extension')]['display_cat'])) ? $extensions[$file->get('extension')]['display_cat'] : ATTACHMENT_CATEGORY_NONE;

	// Do we have to create a thumbnail?
	$filedata['thumbnail'] = ($cat_id == ATTACHMENT_CATEGORY_IMAGE && $config['img_create_thumbnail']) ? 1 : 0;

	// Check Image Size, if it is an image
	if (!$_CLASS['auth']->acl_gets('m_', 'a_') && $cat_id == ATTACHMENT_CATEGORY_IMAGE)
	{
		$file->upload->set_allowed_dimensions(0, 0, $config['img_max_width'], $config['img_max_height']);		
	}

	if (!$_CLASS['auth']->acl_gets('a_', 'm_'))
	{
		$allowed_filesize = ($extensions[$file->get('extension')]['max_filesize'] != 0) ? $extensions[$file->get('extension')]['max_filesize'] : (($is_message) ? $config['max_filesize_pm'] : $config['max_filesize']);
		$file->upload->set_max_filesize($allowed_filesize);
	}
	
	$file->clean_filename('unique', $_CLASS['core_user']->data['user_id'] . '_');
	$file->move_file($config['upload_path']);
		
	if (sizeof($file->error))
	{
		$file->remove();
		$filedata['error'] = array_merge($filedata['error'], $file->error);
		$filedata['post_attach'] = false;

		return $filedata;
	}

	$filedata['filesize'] = $file->get('filesize');
	$filedata['mimetype'] = $file->get('mimetype');
	$filedata['extension'] = $file->get('extension');
	$filedata['physical_filename'] = $file->get('realname');
	$filedata['real_filename'] = $file->get('uploadname');
	$filedata['filetime'] = time();

	// Check our complete quota
	if ($config['attachment_quota'])
	{
		if ($config['upload_dir_size'] + $file->get('filesize') > $config['attachment_quota'])
		{
			$filedata['error'][] = $_CLASS['core_user']->lang['ATTACH_QUOTA_REACHED'];
			$filedata['post_attach'] = false;

			$file->remove();

			return $filedata;
		}
	}

	// Check free disk space
	if ($free_space = @disk_free_space($config['upload_path']))
	{
		if ($free_space <= $file->get('filesize'))
		{
			$filedata['error'][] = $_CLASS['core_user']->lang['ATTACH_QUOTA_REACHED'];
			$filedata['post_attach'] = false;

			$file->remove();
			
			return $filedata;
		}
	}

	// Create Thumbnail
	if ($filedata['thumbnail'])
	{
		$source = $file->get('destination_file');
		$destination = $file->get('destination_path') . '/thumb_' . $file->get('realname');

		if (!create_thumbnail($source, $destination, $file->get('mimetype')))
		{
			$filedata['thumbnail'] = 0;
		}
	}

	return $filedata;
}

// Calculate the needed size for Thumbnail
function get_img_size_format($width, $height)
{
	// Maximum Width the Image can take
	$max_width = 400;

	if ($width > $height)
	{
		return array(
			round($width * ($max_width / $width)),
			round($height * ($max_width / $width))
		);
	} 
	else 
	{
		return array(
			round($width * ($max_width / $height)),
			round($height * ($max_width / $height))
		);
	}
}

// Return supported image types
function get_supported_image_types($type = false)
{
	if (@extension_loaded('gd'))
	{
		$format = imagetypes();
		$new_type = 0;
		
		if ($type !== false)
		{
			switch ($type)
			{
				case 1:
					$new_type = ($format & IMG_GIF) ? IMG_GIF : 0;
					break;
				case 2:
				case 9:
				case 10:
				case 11:
				case 12:
					$new_type = ($format & IMG_JPG) ? IMG_JPG : 0;
					break;
				case 3:
					$new_type = ($format & IMG_PNG) ? IMG_PNG : 0;
					break;
				case 6:
				case 15:
					$new_type = ($format & IMG_WBMP) ? IMG_WBMP : 0;
					break;
			}
		}
		else
		{
			$new_type = array();
			$go_through_types = array(IMG_GIF, IMG_JPG, IMG_PNG, IMG_WBMP);
			
			foreach ($go_through_types as $check_type)
			{
				if ($format & $check_type)
				{
					$new_type[] = $check_type;
				}
			}
		}
		
		return array(
			'gd'		=> ($new_type) ? true : false,
			'format'	=> $new_type,
			'version'	=> (function_exists('imagecreatetruecolor')) ? 2 : 1
		);
	}

	return array('gd' => false);
}

// Create Thumbnail
function create_thumbnail($source, $destination, $mimetype)
{
	global $config;

	$min_filesize = (int) $config['img_min_thumb_filesize'];

	$img_filesize = (file_exists($source)) ? @filesize($source) : false;

	if (!$img_filesize || $img_filesize <= $min_filesize)
	{
		return false;
	}
    
	list($width, $height, $type, ) = getimagesize($source);

	if (!$width || !$height)
	{
		return false;
	}

	list($new_width, $new_height) = get_img_size_format($width, $height);

	$used_imagick = false;

	if (file_exists($destination))
	{
		passthru($config['img_imagick'] . 'convert' . ((defined('PHP_OS') && preg_match('#win#i', PHP_OS)) ? '.exe' : '') . ' -quality 85 -antialias -sample ' . $new_width . 'x' . $new_height . ' "' . str_replace('\\', '/', $source) . '" +profile "*" "' . str_replace('\\', '/', $destination) . '"');
		if (file_exists($new_file))
		{
			$used_imagick = true;
		}
	} 

	if (!$used_imagick) 
	{
		$type = get_supported_image_types($type);
		
		if ($type['gd'])
		{
			switch ($type['format']) 
			{
				case IMG_GIF:
					$image = imagecreatefromgif($source);
					break;
				case IMG_JPG:
					$image = imagecreatefromjpeg($source);
					break;
				case IMG_PNG:
					$image = imagecreatefrompng($source);
					break;
				case IMG_WBMP:
					$image = imagecreatefromwbmp($source);
					break;
			}

			if ($type['version'] == 1)
			{
				$new_image = imagecreate($new_width, $new_height);
				imagecopyresized($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			}
			else
			{
				$new_image = imagecreatetruecolor($new_width, $new_height);
				imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			}
			
			switch ($type['format'])
			{
				case IMG_GIF:
					imagegif($new_image, $destination);
					break;
				case IMG_JPG:
					imagejpeg($new_image, $destination, 90);
					break;
				case IMG_PNG:
					imagepng($new_image, $destination);
					break;
				case IMG_WBMP:
					imagewbmp($new_image, $destination);
					break;
			}

			imagedestroy($new_image);
		}
	}

	if (!file_exists($destination))
	{
		return false;
	}

	@chmod($destination, 0666);

	return true;
}

// DECODE TEXT -> This will/should be handled by bbcode.php eventually
function decode_message(&$message, $bbcode_uid = '')
{
	global $config;

	$match = array('<br />', "[/*:m:$bbcode_uid]", ":u:$bbcode_uid", ":o:$bbcode_uid", ":$bbcode_uid");
	$replace = array("\n", '', '', '', '');

	$message = ($bbcode_uid) ? str_replace($match, $replace, $message) : str_replace('<br />', "\n", $message);

	$match = array(
		'#<!\-\- e \-\-><a href="mailto:(.*?)">.*?</a><!\-\- e \-\->#',
		'#<!\-\- m \-\-><a href="(.*?)" target="_blank">.*?</a><!\-\- m \-\->#',
		'#<!\-\- w \-\-><a href="http:\/\/(.*?)" target="_blank">.*?</a><!\-\- w \-\->#',
		'#<!\-\- l \-\-><a href="(.*?)">.*?</a><!\-\- l \-\->#',
		'#<!\-\- s(.*?) \-\-><img src="\{SMILIES_PATH\}\/.*? \/><!\-\- s\1 \-\->#',
		'#<!\-\- h \-\-><(.*?)><!\-\- h \-\->#',
		'#<.*?>#s'
	);
	
	$replace = array('\1', '\1', '\1', '\1', '\1', '&lt;\1&gt;', '');
	
	$message = preg_replace($match, $replace, $message);

	return;
}

// Generate Topic Icons for display
function posting_gen_topic_icons($mode, $icon_id)
{
	global $config, $_CLASS;

	// Grab icons
	$icons = array();
	obtain_icons($icons);

	if (!$icon_id)
	{
		$_CLASS['core_template']->assign('S_NO_ICON_CHECKED', ' checked="checked"');
	}
	
	if (sizeof($icons))
	{
		foreach ($icons as $id => $data)
		{
			if ($data['display'])
			{
				$_CLASS['core_template']->assign_vars_array('topic_icon', array(
					'ICON_ID'		=> $id,
					'ICON_IMG'		=> $config['icons_path'] . '/' . $data['img'],
					'ICON_WIDTH'	=> $data['width'],
					'ICON_HEIGHT' 	=> $data['height'],
	
					'S_CHECKED'		=> ($id == $icon_id) ? true : false,
					'S_ICON_CHECKED' => ($id == $icon_id) ? ' checked="checked"' : '')
				);
			}
		}

		return true;
	}

	return false;
}

// Assign Inline attachments (build option fields)
function posting_gen_inline_attachments(&$attachment_data)
{
	global $_CLASS;

	if (sizeof($attachment_data))
	{
		$s_inline_attachment_options = '';
		
		foreach ($attachment_data as $i => $attachment)
		{
			$s_inline_attachment_options .= '<option value="' . $i . '">' . $attachment['real_filename'] . '</option>';
		}

		$_CLASS['core_template']->assign('S_INLINE_ATTACHMENT_OPTIONS', $s_inline_attachment_options);

		return true;
	}

	return false;
}

// Build topic types able to be selected
function posting_gen_topic_types($forum_id, $cur_topic_type = POST_NORMAL)
{
	global $_CLASS, $topic_type;

	$toggle = false;

	$topic_types = array(
		'sticky'	=> array('const' => POST_STICKY, 'lang' => 'POST_STICKY'),
		'announce'	=> array('const' => POST_ANNOUNCE, 'lang' => 'POST_ANNOUNCEMENT'),
		'global'	=> array('const' => POST_GLOBAL, 'lang' => 'POST_GLOBAL')
	);
	
	$topic_type_array = array();
	
	foreach ($topic_types as $auth_key => $topic_value)
	{
		// Temp - we do not have a special post global announcement permission
		$auth_key = ($auth_key == 'global') ? 'announce' : $auth_key;

		if ($_CLASS['auth']->acl_get('f_' . $auth_key, $forum_id))
		{
			$toggle = true;

			$topic_type_array[] = array(
				'VALUE'			=> $topic_value['const'],
				'S_CHECKED'		=> ($cur_topic_type == $topic_value['const'] || ($forum_id == 0 && $topic_value['const'] == POST_GLOBAL)) ? ' checked="checked"' : '',
				'L_TOPIC_TYPE'	=> $_CLASS['core_user']->lang[$topic_value['lang']]
			);
		}
	}

	if ($toggle)
	{
		$topic_type_array = array_merge(array(0 => array(
			'VALUE'			=> POST_NORMAL,
			'S_CHECKED'		=> ($topic_type == POST_NORMAL) ? ' checked="checked"' : '',
			'L_TOPIC_TYPE'	=> $_CLASS['core_user']->lang['POST_NORMAL'])), 
			
			$topic_type_array
		);
		
		foreach ($topic_type_array as $array)
		{
			$_CLASS['core_template']->assign_vars_array('topic_type', $array);
		}

		$_CLASS['core_template']->assign(array(
			'S_TOPIC_TYPE_STICKY'	=> ($_CLASS['auth']->acl_get('f_sticky', $forum_id)),
			'S_TOPIC_TYPE_ANNOUNCE'	=> ($_CLASS['auth']->acl_get('f_announce', $forum_id)))
		);
	}

	return $toggle;
}

function posting_gen_attachment_entry(&$attachment_data, &$filename_data)
{
	global $_CLASS, $config, $SID, $phpEx;
		
	$_CLASS['core_template']->assign(array(
		'S_SHOW_ATTACH_BOX'	=> true)
	);

	if (sizeof($attachment_data))
	{
		$_CLASS['core_template']->assign(array(
			'S_HAS_ATTACHMENTS'	=> true)
		);
		
		$count = 0;
		foreach ($attachment_data as $attach_row)
		{
			$hidden = '';
			$attach_row['real_filename'] = stripslashes(basename($attach_row['real_filename']));

			foreach ($attach_row as $key => $value)
			{
				$hidden .= '<input type="hidden" name="attachment_data[' . $count . '][' . $key . ']" value="' . $value . '" />';
			}
			
			$download_link = (!$attach_row['attach_id']) ? $config['upload_path'] . '/' . basename($attach_row['physical_filename']) : generate_link('Forums&amp;file=download&amp;id=' . intval($attach_row['attach_id']));
			
			$_CLASS['core_template']->assign_vars_array('attach_row', array(
				'FILENAME'			=> basename($attach_row['real_filename']),
				'ATTACH_FILENAME'	=> basename($attach_row['physical_filename']),
				'FILE_COMMENT'		=> $attach_row['comment'],
				'ATTACH_ID'			=> $attach_row['attach_id'],
				'ASSOC_INDEX'		=> $count,

				'U_VIEW_ATTACHMENT' => $download_link,
				'S_HIDDEN'			=> $hidden)
			);

			$count++;
		}
	}

	$_CLASS['core_template']->assign(array(
		'FILE_COMMENT'  => $filename_data['filecomment'],
		'FILESIZE'		=> $config['max_filesize'])
	);

	return sizeof($attachment_data);
}

// Load Drafts
function load_drafts($topic_id = 0, $forum_id = 0, $id = 0)
{
	global $db, $phpEx, $SID, $_CLASS;

	// Only those fitting into this forum...
	if ($forum_id || $topic_id)
	{
		$sql = 'SELECT d.draft_id, d.topic_id, d.forum_id, d.draft_subject, d.save_time, f.forum_name
			FROM ' . DRAFTS_TABLE . ' d, ' . FORUMS_TABLE . ' f
				WHERE d.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND f.forum_id = d.forum_id ' . 
				(($forum_id) ? " AND f.forum_id = $forum_id" : '') . '
			ORDER BY d.save_time DESC';
	}
	else
	{
		$sql = 'SELECT *
			FROM ' . DRAFTS_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND forum_id = 0
				AND topic_id = 0
			ORDER BY save_time DESC';
	}
	$result = $db->sql_query($sql);

	$draftrows = $topic_ids = array();

	while ($row = $db->sql_fetchrow($result))
	{
		if ($row['topic_id'])
		{
			$topic_ids[] = (int) $row['topic_id'];
		}
		$draftrows[] = $row;
	}
	$db->sql_freeresult($result);
				
	if (sizeof($topic_ids))
	{
		$sql = 'SELECT topic_id, forum_id, topic_title
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id IN (' . implode(',', array_unique($topic_ids)) . ')';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$topic_rows[$row['topic_id']] = $row;
		}
		$db->sql_freeresult($result);
	}
	unset($topic_ids);
	
	if (sizeof($draftrows))
	{
		$_CLASS['core_template']->assign('S_SHOW_DRAFTS', true);

		foreach ($draftrows as $draft)
		{
			$link_topic = $link_forum = $link_pm = false;
			$insert_url = $view_url = $title = '';

			if (isset($topic_rows[$draft['topic_id']]) && $_CLASS['auth']->acl_get('f_read', $topic_rows[$draft['topic_id']]['forum_id']))
			{
				$link_topic = true;
				$view_url = generate_link('Forums&amp;file=viewtopic&amp;f=' . $topic_rows[$draft['topic_id']]['forum_id'] . "&amp;t=" . $draft['topic_id']);
				$title = $topic_rows[$draft['topic_id']]['topic_title'];

				$insert_url = generate_link('Forums&amp;file=posting&amp;f=' . $topic_rows[$draft['topic_id']]['forum_id'] . '&amp;t=' . $draft['topic_id'] . '&amp;mode=reply&amp;d=' . $draft['draft_id'], false, false);
			}
			else if ($_CLASS['auth']->acl_get('f_read', $draft['forum_id']))
			{
				$link_forum = true;
				$view_url = generate_link('Forums&amp;file=viewtopic&amp;f=' . $draft['forum_id']);
				$title = $draft['forum_name'];

				$insert_url = generate_link('Forums&amp;file=posting&amp;f=' . $draft['forum_id'] . '&amp;mode=post&amp;d=' . $draft['draft_id'], false, false);
			}
			else
			{
				$link_pm = true;
				$insert_url = generate_link("Control_Panel&amp;i=$id&amp;mode=compose&amp;d=" . $draft['draft_id']);
			}
						
			$_CLASS['core_template']->assign_vars_array('draftrow', array(
				'DRAFT_ID'		=> $draft['draft_id'],
				'DATE'			=> $_CLASS['core_user']->format_date($draft['save_time']),
				'DRAFT_SUBJECT'	=> $draft['draft_subject'],

				'TITLE'			=> $title,
				'U_VIEW'		=> $view_url,
				'U_INSERT'		=> $insert_url,

				'S_LINK_PM'		=> $link_pm,
				'S_LINK_TOPIC'	=> $link_topic,
				'S_LINK_FORUM'	=> $link_forum)
			);
		}
	}
}

// Topic Review
function topic_review($topic_id, $forum_id, $mode = 'topic_review', $cur_post_id = 0, $show_quote_button = true)
{
	global $_CLASS, $db, $bbcode, $site_file_root;
	global $config, $phpEx, $SID;

	// Go ahead and pull all data for this topic
	$sql = 'SELECT u.username, u.user_id, p.post_id, p.post_username, p.post_subject, p.post_text, p.enable_smilies, p.bbcode_uid, p.bbcode_bitfield, p.post_time
		FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . " u
		WHERE p.topic_id = $topic_id
			AND p.poster_id = u.user_id
			" . ((!$_CLASS['auth']->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . '
			' . (($mode == 'post_review') ? " AND p.post_id > $cur_post_id" : '') . '
		ORDER BY p.post_time DESC';
	$result = $db->sql_query_limit($sql, $config['posts_per_page']);

	if (!$row = $db->sql_fetchrow($result))
	{
		return false;
	}

	$bbcode_bitfield = 0;
	do
	{
		$rowset[] = $row;
		$bbcode_bitfield |= $row['bbcode_bitfield'];
	}
	while ($row = $db->sql_fetchrow($result));
	$db->sql_freeresult($result);

	// Instantiate BBCode class
	if (!isset($bbcode) && $bbcode_bitfield)
	{
		require_once($site_file_root.'includes/forums/bbcode.'.$phpEx);
		$bbcode = new bbcode($bbcode_bitfield);
	}

	foreach ($rowset as $i => $row)
	{
		$poster_id = $row['user_id'];
		$poster = $row['username'];

		// Handle anon users posting with usernames
		if ($poster_id == ANONYMOUS && $row['post_username'])
		{
			$poster = $row['post_username'];
			$poster_rank = $_CLASS['core_user']->lang['GUEST'];
		}

		$post_subject = $row['post_subject'];
		$message = $row['post_text'];

		if ($row['bbcode_bitfield'])
		{
			$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
		}

		$message = smiley_text($message, !$row['enable_smilies']);

		$post_subject = censor_text($post_subject);
		$message = censor_text($message);

		$_CLASS['core_template']->assign_vars_array($mode . '_row', array(
			'POSTER_NAME' 	=> $poster,
			'POST_SUBJECT' 	=> $post_subject,
			'MINI_POST_IMG' => $_CLASS['core_user']->img('icon_post', $_CLASS['core_user']->lang['POST']),
			'POST_DATE' 	=> $_CLASS['core_user']->format_date($row['post_time']),
			'MESSAGE' 		=> str_replace("\n", '<br />', $message), 

			'U_POST_ID'		=> $row['post_id'],
			'U_MINI_POST'	=> generate_link('Forums&amp;file=viewtopic&amp;p=' . $row['post_id'] . '#' . $row['post_id']),
			'U_MCP_DETAILS'	=> ($_CLASS['auth']->acl_get('m_', $forum_id)) ? generate_link('Forums&amp;file=mcp&amp;mode=post_details&amp;p=' . $row['post_id']) : '',
			'U_QUOTE'		=> ($show_quote_button && $_CLASS['auth']->acl_get('f_quote', $forum_id)) ? 'javascript:addquote(' . $row['post_id'] . ", '" . str_replace("'", "\\'", $poster) . "')" : '')
		);
		unset($rowset[$i]);
	}

	if ($mode == 'topic_review')
	{
		$_CLASS['core_template']->assign('QUOTE_IMG', $_CLASS['core_user']->img('btn_quote', $_CLASS['core_user']->lang['REPLY_WITH_QUOTE']));
	}

	return true;
}

// User Notification
function user_notification($mode, $subject, $topic_title, $forum_name, $forum_id, $topic_id, $post_id)
{
	global $db, $config, $phpEx, $_CORE_CONFIG, $_CLASS, $site_file_root;

	$topic_notification = ($mode == 'reply' || $mode == 'quote');
	$forum_notification = ($mode == 'post');

	if (!$topic_notification && !$forum_notification)
	{
		trigger_error('WRONG_NOTIFICATION_MODE');
	}

	$topic_title = ($topic_notification) ? $topic_title : $subject;
	$topic_title = censor_text($topic_title);

	// Get banned User ID's
	$sql = 'SELECT ban_userid 
		FROM ' . BANLIST_TABLE;
	$result = $db->sql_query($sql);

	$sql_ignore_users = ANONYMOUS . ', ' . $_CLASS['core_user']->data['user_id'];
	while ($row = $db->sql_fetchrow($result))
	{
		if (isset($row['ban_userid']))
		{
			$sql_ignore_users .= ', ' . $row['ban_userid'];
		}
	}
	$db->sql_freeresult($result);

	$notify_rows = array();

	// -- get forum_userids	|| topic_userids
	$sql = 'SELECT u.user_id, u.username, u.user_email, u.user_lang, u.user_notify_type, u.user_jabber 
		FROM ' . (($topic_notification) ? TOPICS_WATCH_TABLE : FORUMS_WATCH_TABLE) . ' w, ' . USERS_TABLE . ' u
		WHERE w.' . (($topic_notification) ? 'topic_id' : 'forum_id') . ' = ' . (($topic_notification) ? $topic_id : $forum_id) . "
			AND w.user_id NOT IN ($sql_ignore_users)
			AND w.notify_status = 0
			AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')
			AND u.user_id = w.user_id';
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		$notify_rows[$row['user_id']] = array(
			'user_id'		=> $row['user_id'],
			'username'		=> $row['username'],
			'user_email'	=> $row['user_email'],
			'user_jabber'	=> $row['user_jabber'], 
			'user_lang'		=> $row['user_lang'], 
			'notify_type'	=> ($topic_notification) ? 'topic' : 'forum',
			'template'		=> ($topic_notification) ? 'topic_notify' : 'newtopic_notify',
			'method'		=> $row['user_notify_type'], 
			'allowed'		=> false
		);
	}
	$db->sql_freeresult($result);
	
	// forum notification is sent to those not receiving post notification
	if ($topic_notification)
	{
		if (sizeof($notify_rows))
		{
			$sql_ignore_users .= ', ' . implode(', ', array_keys($notify_rows));
		}

		$sql = 'SELECT u.user_id, u.username, u.user_email, u.user_lang, u.user_notify_type, u.user_jabber 
			FROM ' . FORUMS_WATCH_TABLE . ' fw, ' . USERS_TABLE . " u
			WHERE fw.forum_id = $forum_id
				AND fw.user_id NOT IN ($sql_ignore_users)
				AND fw.notify_status = 0
				AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')
				AND u.user_id = fw.user_id';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$notify_rows[$row['user_id']] = array(
				'user_id'		=> $row['user_id'],
				'username'		=> $row['username'],
				'user_email'	=> $row['user_email'],
				'user_jabber'	=> $row['user_jabber'], 
				'user_lang'		=> $row['user_lang'],
				'notify_type'	=> 'forum',
				'template'		=> 'forum_notify',
				'method'		=> $row['user_notify_type'], 
				'allowed'		=> false
			);
		}
		$db->sql_freeresult($result);
	}

	if (!sizeof($notify_rows))
	{
		return;
	}

	foreach ($_CLASS['auth']->acl_get_list(array_keys($notify_rows), 'f_read', $forum_id) as $forum_id => $forum_ary)
	{
		foreach ($forum_ary as $auth_option => $user_ary)
		{
			foreach ($user_ary as $user_id)
			{
				$notify_rows[$user_id]['allowed'] = true;
			}
		}
	}


	// Now, we have to do a little step before really sending, we need to distinguish our users a little bit. ;)
	$msg_users = $delete_ids = $update_notification = array();
	foreach ($notify_rows as $user_id => $row)
	{
		if (!$row['allowed'] || !trim($row['user_email']))
		{
			$delete_ids[$row['notify_type']][] = $row['user_id'];
		}
		else
		{
			$msg_users[] = $row;
			$update_notification[$row['notify_type']][] = $row['user_id'];
		}
	}
	unset($notify_rows);

	// Now, we are able to really send out notifications
	if (sizeof($msg_users))
	{
		require_once($site_file_root.'includes/forums/functions_messenger.'.$phpEx);
		$messenger = new messenger();

		$email_sig = str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']);

		$msg_list_ary = array();
		foreach ($msg_users as $row)
		{ 
			$pos = (!isset($msg_list_ary[$row['template']])) ? 0 : sizeof($msg_list_ary[$row['template']]);

			$msg_list_ary[$row['template']][$pos]['method']	= $row['method'];
			$msg_list_ary[$row['template']][$pos]['email']	= $row['user_email'];
			$msg_list_ary[$row['template']][$pos]['jabber']	= $row['user_jabber'];
			$msg_list_ary[$row['template']][$pos]['name']	= $row['username'];
			$msg_list_ary[$row['template']][$pos]['lang']	= $row['user_lang'];
		}
		unset($msg_users);

		foreach ($msg_list_ary as $email_template => $email_list)
		{
			foreach ($email_list as $addr)
			{
				$messenger->template($email_template, $addr['lang']);

				$messenger->replyto($config['board_email']);
				$messenger->to($addr['email'], $addr['name']);
				$messenger->im($addr['jabber'], $addr['name']);

				$messenger->assign_vars(array(
					'EMAIL_SIG'		=> $email_sig,
					'SITENAME'		=> $_CORE_CONFIG['global']['sitename'],
					'USERNAME'		=> $addr['name'],
					'TOPIC_TITLE'	=> $topic_title,  
					'FORUM_NAME'	=> $forum_name,

					'U_FORUM'				=> getlink("Forums&amp;file=viewtopic&f=$forum_id&e=0", true, true, false),
					'U_TOPIC'				=> getlink("Forums&amp;file=viewtopic&f=$forum_id&t=$topic_id&e=0", true, true, false),
					'U_NEWEST_POST'			=> getlink("Forums&amp;file=viewtopic&f=$forum_id&t=$topic_id&p=$post_id&e=$post_id", true, true, false),
					'U_STOP_WATCHING_TOPIC' => getlink("Forums&amp;file=viewtopic&f=$forum_id&t=$topic_id&unwatch=topic", true, true, false),
					'U_STOP_WATCHING_FORUM' => getlink("Forums&amp;file=viewforum&f=$forum_id&unwatch=forum", true, true, false), 
				));

				$messenger->send($addr['method']);
				$messenger->reset();
			}
		}
		unset($msg_list_ary);

		if ($messenger->queue)
		{
			$messenger->save_queue();
		}
	}

	// Handle the DB updates
	$db->sql_transaction();

	if (isset($update_notification['topic']) && sizeof($update_notification['topic']))
	{
		$db->sql_query('UPDATE ' . TOPICS_WATCH_TABLE . "
			SET notify_status = 1
			WHERE topic_id = $topic_id
				AND user_id IN (" . implode(', ', $update_notification['topic']) . ")");
	}

	if (isset($update_notification['forum']) && sizeof($update_notification['forum']))
	{
		$db->sql_query('UPDATE ' . FORUMS_WATCH_TABLE . "
			SET notify_status = 1
			WHERE forum_id = $forum_id
				AND user_id IN (" . implode(', ', $update_notification['forum']) . ")");
	}

	// Now delete the user_ids not authorized to receive notifications on this topic/forum
	if (isset($delete_ids['topic']) && sizeof($delete_ids['topic']))
	{
		$db->sql_query('DELETE FROM ' . TOPICS_WATCH_TABLE . "
			WHERE topic_id = $topic_id
				AND user_id IN (" . implode(', ', $delete_ids['topic']) . ")");
	}

	if (isset($delete_ids['forum']) && sizeof($delete_ids['forum']))
	{
		$db->sql_query('DELETE FROM ' . FORUMS_WATCH_TABLE . "
			WHERE forum_id = $forum_id
				AND user_id IN (" . implode(', ', $delete_ids['forum']) . ")");
	}

	$db->sql_transaction('commit');
}

?>