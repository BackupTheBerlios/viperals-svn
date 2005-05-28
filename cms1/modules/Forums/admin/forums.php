<?php

if (!defined('CPG_NUKE')) { header('Location: ../../'); exit; }

global $db;
define('FORUMS_ADMIN', true);

$data = array(
	'title' => 'Forum Administration',
	'position' => BLOCK_LEFT,
	'file' => 'block-Admin_Forums.php',
);

$_CLASS['core_blocks']->add_block($data);

require_once($site_file_root.'includes/forums/functions.'.$phpEx);
loadclass($site_file_root.'includes/forums/auth.'.$phpEx, 'auth');

require($site_file_root.'admin/modules/forums/pagestart.' . $phpEx);

$file = get_variable('file', 'GET', 'index');

require($site_file_root.'admin/modules/forums/'.$file.'.php');

die;
?>
