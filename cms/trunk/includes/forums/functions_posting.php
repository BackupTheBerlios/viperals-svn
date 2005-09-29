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

// -------------------------------------------------------------
//
// $Id: functions_posting.php,v 1.124 2004/09/17 09:11:48 acydburn Exp $
//
// FILENAME  : functions_posting.php
// STARTED   : Sun Jul 14, 2002
// COPYRIGHT : © 2001, 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

// Fill smiley templates (or just the variables) with smileys, either in a window or inline
function generate_smilies($mode, $forum_id)
{
	global $_CLASS;
// add option for all smiles in window
	$display_link = false;
	$mode = ($mode == 'window') ? 'window' : 'inline';

	if ($mode == 'inline')
	{
		$sql = 'SELECT smiley_id
			FROM ' . SMILIES_TABLE . '
			WHERE smiley_type = 1';
		$result = $_CLASS['core_db']->query_limit($sql, 1, 0);

		if ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$display_link = true;
		}
		$_CLASS['core_db']->free_result($result);
	}

	if (is_null($smiley = $_CLASS['core_cache']->get('smiley_'.$mode)))
	{
		$smiley = array();

		$sql = 'SELECT *
			FROM ' . SMILIES_TABLE .' 
				WHERE smiley_type ='.(($mode == 'inline') ? '0' : '1') . '
					ORDER BY smiley_order';
		$result = $_CLASS['core_db']->query($sql);
	
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$smiley[] = array(
				'SMILEY_CODE' 	=> $row['smiley_code'],
				'SMILEY_IMG' 	=> $row['smiley_src'],
				'SMILEY_WIDTH'  => $row['smiley_width'],
				'SMILEY_HEIGHT' => $row['smiley_height'],
				'SMILEY_DESC'   => $row['smiley_description']
			);
		}

		$_CLASS['core_cache']->put('smiley_'.$mode, $smiley);
		$_CLASS['core_db']->free_result($result);
	}

	$_CLASS['core_template']->assign('smiley', $smiley);

	if ($mode == 'inline')
	{
		$_CLASS['core_template']->assign_array(array(
			'S_SHOW_SMILEY_LINK' 	=> ($display_link) ? true : false,
			'U_MORE_SMILIES' 		=> generate_link('Forums&amp;file=posting&amp;mode=smilies&amp;f='.$forum_id))
		);
	}

	if ($mode == 'window')
	{
		global $config;

		$_CLASS['core_template']->assign('T_SMILIES_PATH', "{$config['smilies_path']}/");
		$_CLASS['core_template']->display('modules/Forums/posting_smilies.html');

		script_close();
	}
}

// Update Last Post Informations
function update_last_post_information($type, $id)
{
	global $_CLASS;

	$update_sql = array();

	$sql = 'SELECT MAX(p.post_id) as last_post_id
		FROM ' . FORUMS_POSTS_TABLE . ' p, ' . FORUMS_TOPICS_TABLE . " t
		WHERE p.topic_id = t.topic_id
			AND p.post_approved = 1
			AND t.topic_approved = 1
			AND p.{$type}_id = $id";
			
	$result = $_CLASS['core_db']->query($sql);
	$row = $_CLASS['core_db']->fetch_row_assoc($result);

	if ((int) $row['last_post_id'])
	{
		$sql = 'SELECT p.post_id, p.poster_id, p.post_time, u.username, p.post_username
			FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
			WHERE p.poster_id = u.user_id
				AND p.post_id = ' . $row['last_post_id'];
		$result = $_CLASS['core_db']->query($sql);
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);

		$update_sql[] = $type . '_last_post_id = ' . (int) $row['post_id'];
		$update_sql[] =	$type . '_last_post_time = ' . (int) $row['post_time'];
		$update_sql[] = $type . '_last_poster_id = ' . (int) $row['poster_id'];
		$update_sql[] = "{$type}_last_poster_name = '" . (($row['poster_id'] == ANONYMOUS) ? $_CLASS['core_db']->escape($row['post_username']) : $_CLASS['core_db']->escape($row['username'])) . "'";
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
	global $_CLASS, $config;

	$filedata = array();
	$filedata['error'] = array();

	include_once(SITE_FILE_ROOT. 'includes/forums/functions_upload.php');
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

	$extensions = obtain_attach_extensions($forum_id);
	
	if (!empty($extensions['_allowed_']))
	{
		$upload->set_allowed_extensions(array_keys($extensions['_allowed_']));
	}

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
		
	if (!empty($file->error))
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

	if (($width < $new_width) && ($height < $new_height))
	{
		return false;
	}

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

	$icons = obtain_icons();

	$_CLASS['core_template']->assign('S_NO_ICON_CHECKED', ((!$icon_id) ? ' checked="checked"' : ''));

	if (!empty($icons))
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
					'S_ICON_CHECKED' => ($id == $icon_id) ? ' checked="checked"' : ''
				));
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

	if (empty($attachment_data))
	{
		return false;
	}

	$s_inline_attachment_options = '';
	
	foreach ($attachment_data as $i => $attachment)
	{
		$s_inline_attachment_options .= '<option value="' . $i . '">' . $attachment['real_filename'] . '</option>';
	}

	$_CLASS['core_template']->assign('S_INLINE_ATTACHMENT_OPTIONS', $s_inline_attachment_options);

	return true;
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

		$_CLASS['core_template']->assign_array(array(
			'S_TOPIC_TYPE_STICKY'	=> ($_CLASS['auth']->acl_get('f_sticky', $forum_id)),
			'S_TOPIC_TYPE_ANNOUNCE'	=> ($_CLASS['auth']->acl_get('f_announce', $forum_id)))
		);
	}

	return $toggle;
}

function posting_gen_attachment_entry(&$attachment_data, &$filename_data)
{
	global $_CLASS, $config;
		
	$_CLASS['core_template']->assign('S_SHOW_ATTACH_BOX', true);

	if (!empty($attachment_data))
	{
		$_CLASS['core_template']->assign('S_HAS_ATTACHMENTS', true);
		
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

	$_CLASS['core_template']->assign_array(array(
		'FILE_COMMENT'  => $filename_data['filecomment'],
		'FILESIZE'		=> $config['max_filesize'])
	);

	return count($attachment_data);
}

// Load Drafts
function load_drafts($topic_id = 0, $forum_id = 0, $id = 0)
{
	global $_CLASS;

	// Only those fitting into this forum...
	if ($forum_id || $topic_id)
	{
		$sql = 'SELECT d.draft_id, d.topic_id, d.forum_id, d.draft_subject, d.save_time, f.forum_name
			FROM ' . FORUMS_DRAFTS_TABLE . ' d, ' . FORUMS_TABLE . ' f
				WHERE d.user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND f.forum_id = d.forum_id ' . 
				(($forum_id) ? " AND f.forum_id = $forum_id" : '') . '
			ORDER BY d.save_time DESC';
	}
	else
	{
		$sql = 'SELECT *
			FROM ' . FORUMS_DRAFTS_TABLE . '
				WHERE user_id = ' . $_CLASS['core_user']->data['user_id'] . '
				AND forum_id = 0
				AND topic_id = 0
			ORDER BY save_time DESC';
	}
	$result = $_CLASS['core_db']->query($sql);

	$draftrows = $topic_ids = array();

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		if ($row['topic_id'])
		{
			$topic_ids[] = (int) $row['topic_id'];
		}
		$draftrows[] = $row;
	}
	$_CLASS['core_db']->free_result($result);
				
	if (!empty($topic_ids))
	{
		$sql = 'SELECT topic_id, forum_id, topic_title
			FROM ' . FORUMS_TOPICS_TABLE . '
			WHERE topic_id IN (' . implode(',', array_unique($topic_ids)) . ')';
		$result = $_CLASS['core_db']->query($sql);

		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			$topic_rows[$row['topic_id']] = $row;
		}
		$_CLASS['core_db']->free_result($result);
	}
	unset($topic_ids);
	
	if (!empty($draftrows))
	{
		$_CLASS['core_template']->assign_array('S_SHOW_DRAFTS', true);

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
	else
	{
		$_CLASS['core_template']->assign('S_SHOW_DRAFTS', false);
	}
}

// Topic Review
function topic_review($topic_id, $forum_id, $mode = 'topic_review', $cur_post_id = 0, $show_quote_button = true)
{
	global $_CLASS, $bbcode, $config;

	// Go ahead and pull all data for this topic
	$sql = 'SELECT u.username, u.user_id, p.post_id, p.post_username, p.post_subject, p.post_text, p.enable_smilies, p.bbcode_uid, p.bbcode_bitfield, p.post_time
		FROM ' . FORUMS_POSTS_TABLE . ' p, ' . USERS_TABLE . " u
		WHERE p.topic_id = $topic_id
			AND p.poster_id = u.user_id
			" . ((!$_CLASS['auth']->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . '
			' . (($mode == 'post_review') ? " AND p.post_id > $cur_post_id" : '') . '
		ORDER BY p.post_time DESC';
	$result = $_CLASS['core_db']->query_limit($sql, $config['posts_per_page']);

	if (!$row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		return false;
	}

	$bbcode_bitfield = 0;
	do
	{
		$rowset[] = $row;
		$bbcode_bitfield |= $row['bbcode_bitfield'];
	}
	while ($row = $_CLASS['core_db']->fetch_row_assoc($result));
	$_CLASS['core_db']->free_result($result);

	// Instantiate BBCode class
	if (!isset($bbcode) && $bbcode_bitfield)
	{
		require_once(SITE_FILE_ROOT.'includes/forums/bbcode.php');
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
	global $config, $_CORE_CONFIG, $_CLASS;

	$titles = array(
		'notify_topic'		=> 'Topic Reply Notification - '. $topic_title,
		'notify_newtopic'	=> 'New Topic Notification - '. $topic_title,
		'notify_forum'		=> 'Forum Post Notification - '. $forum_name,
	);

	if ($mode == 'reply' || $mode == 'quote')
	{
		$topic_title = $subject;

		$notify_type = 'topic';
		$template = 'notify_topic'; //notify_forum
		$where = "(w.forum_id = $forum_id OR w.topic_id = $topic_id)";
	}
	else
	{
		$topic_title = $topic_title;

		$notify_type = 'forum';
		$template = 'notify_newtopic';
		$where = 'w.forum_id = '.$forum_id;
	}

	$topic_title = censor_text($topic_title);
	$holding = array();

// Add use of notification type

	// Lets get all the users that are set to be notified
	$sql = 'SELECT w.notify_type, w.forum_id, u.user_id, u.username, u.user_email, u.user_lang
		FROM '.FORUMS_WATCH_TABLE.' w, ' . USERS_TABLE . " u
		WHERE $where
			AND w.notify_status = 0
			AND u.user_status = ". STATUS_ACTIVE . '
			AND u.user_id = w.user_id';
	$result = $_CLASS['core_db']->query($sql);

	while ($user = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$ignore_array[$user['user_id']] = $user['user_id'];

		$holding[$user['user_id']] = $user;
		$holding[$user['user_id']]['template'] = ($notify_type == 'topic' && $user['forum_id']) ? 'notify_forum' : $template;
		
		if ($notify_type == 'topic' && $user['forum_id'])
		{
			$holding[$user['user_id']]['template'] = 'notify_forum';
			$holding[$user['user_id']]['update'] = 'forum';
		}
		else
		{
			$holding[$user['user_id']]['template'] = $template;
			$holding[$user['user_id']]['update'] = $notify_type;
		}
	}
	$_CLASS['core_db']->free_result($result);
	
	if (empty($holding))
	{
		return;
	}

	// Now we remove the users that aren't allowed to read the forum
	$acl_list = $_CLASS['auth']->acl_get_list(array_keys($ignore_array), 'f_read', $forum_id);

	if (!empty($acl_list))
	{
		foreach ($acl_list[$forum_id]['f_read'] as $user_id)
		{
			unset($ignore_array[$user_id]);
		}
	}
	$processed = $delete_array = $update_array = array();

	foreach ($holding as $user)
	{
		if (!in_array($user['user_id'], $ignore_array))
		{
			$processed[$user['template']][] = $user;
			$update_array[$user['update']][] = $user['user_id'];
		}
		else
		{
			$delete_array[$user['update']] = $user['user_id'];
		}
	}
	unset($holding, $ignore_array);

	// Now delete the user_ids not authorized to receive notifications on this topic/forum
	if (!empty($delete_array['topic']))
	{
		$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_WATCH_TABLE . "
			WHERE topic_id = $topic_id
				AND user_id IN (" . implode(', ', $delete_array['topic']) . ")");
	}

	if (!empty($delete_array['forum']))
	{
		$_CLASS['core_db']->query('DELETE FROM ' . FORUMS_WATCH_TABLE . "
			WHERE forum_id = $forum_id
				AND user_id IN (" . implode(', ', $delete_array['forum']) . ")");
	}

	if (empty($processed))
	{
		return;
	}

	$email_sig = str_replace('<br />', "\n", "-- \n" . $config['board_email_sig']);

	require_once(SITE_FILE_ROOT.'includes/mailer.php');

	foreach ($processed as $template => $user_list)
	{
		$mailer = new core_mailer;

		$count = count($user_list);
		for ($i = 0; $i < $count; $i++)
		{
			$mailer->to($user_list[$i]['user_email'], $user_list[$i]['username']);
		}

		$mailer->subject($titles[$template]);

		$_CLASS['core_template']->assign_array(array(
			'EMAIL_SIG'		=> $email_sig,
			'SITENAME'		=> $_CORE_CONFIG['global']['site_name'],
			'TOPIC_TITLE'	=> $topic_title,  
			'FORUM_NAME'	=> $forum_name,

			'U_FORUM'				=> generate_link("Forums&file=viewforum&f=$forum_id&e=0", array('sid' => false, 'full' => true)),
			'U_TOPIC'				=> generate_link("Forums&file=viewtopic&t=$topic_id&e=0", array('sid' => false, 'full' => true)),
			'U_NEWEST_POST'			=> generate_link("Forums&file=viewtopic&t=$topic_id&p=$post_id&e=$post_id", array('sid' => false, 'full' => true)),
			'U_STOP_WATCHING_TOPIC' => generate_link("Forums&file=viewtopic&t=$topic_id&unwatch=topic", array('sid' => false, 'full' => true)),
			'U_STOP_WATCHING_FORUM' => generate_link("Forums&file=viewforum&f=$forum_id&unwatch=forum", array('sid' => false, 'full' => true)), 
		));
		
		$mailer->message = trim($_CLASS['core_template']->display("email/forums/$template.txt", true));

		if (!$mailer->send())
		{
			//echo $mailer->error;
		}
	}

	$_CLASS['core_db']->transaction();

	if (!empty($update_array['topic']))
	{
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_WATCH_TABLE . "
			SET notify_status = 1
			WHERE topic_id = $topic_id
				AND user_id IN (" . implode(', ', $update_array['topic']) . ")");
	}

	if (!empty($update_array['forum']))
	{
		$_CLASS['core_db']->query('UPDATE ' . FORUMS_WATCH_TABLE . "
			SET notify_status = 1
			WHERE forum_id = $forum_id
				AND user_id IN (" . implode(', ', $update_array['forum']) . ")");
	}

	$_CLASS['core_db']->transaction('commit');
}

?>