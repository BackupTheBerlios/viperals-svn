<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * write the compiled resource
 *
 * @param string $compile_path
 * @param string $compiled_content
 * @return true
 */
function smarty_core_write_compiled_resource($params, &$this)
{
    if(!@is_dir($this->compile_dir)) {
		!@mkdir($this->compile_dir, $this->_dir_perms);
	}
	
    if(!@is_writable($this->compile_dir)) {
        // compile_dir not writable, see if it exists
        if(!@is_dir($this->compile_dir)) {
            $this->trigger_error('the $compile_dir \'' . $this->compile_dir . '\' does not exist, or is not a directory.', E_USER_ERROR);
            return false;
        }
        $this->trigger_error('unable to write to $compile_dir \'' . realpath($this->compile_dir) . '\'. Be sure $compile_dir is writable by the web server user.', E_USER_ERROR);
        return false;
    }

    $_params = array('filename' => $params['compile_path'], 'contents' => $params['compiled_content'], 'create_dirs' => true);
    require_once(SMARTY_CORE_DIR . 'core.write_file.php');
    smarty_core_write_file($_params, $this);
    return true;
}

/* vim: set expandtab: */

?>