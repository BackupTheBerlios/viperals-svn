<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * determines if a resource is secure or not.
 *
 * @param string $resource_type
 * @param string $resource_name
 * @return boolean
 */

//  $resource_type, $resource_name

function smarty_core_is_secure($params, &$this)
{
    if (!$this->security || $this->security_settings['INCLUDE_ANY']) {
        return true;
    }

    if ($params['resource_type'] == 'file') {
        $_rp = realpath($params['resource_name']);
        if (isset($params['resource_base_path'])) {
            foreach ((array)$params['resource_base_path'] as $curr_dir) {
                if ( ($_cd = realpath($curr_dir)) !== false &&
                     strncmp($_rp, $_cd, strlen($_cd)) == 0 &&
                     $_rp{strlen($_cd)} == DIRECTORY_SEPARATOR ) {
                    return true;
                }
            }
        }
        if (!empty($this->secure_dir)) {
            foreach ((array)$this->secure_dir as $curr_dir) {
                if ( ($_cd = realpath($curr_dir)) !== false &&
                     strncmp($_rp, $_cd, strlen($_cd)) == 0 &&
                     $_rp{strlen($_cd)} == DIRECTORY_SEPARATOR ) {
                    return true;
                }            
            }
        }
    } else {
        // resource is not on local file system
        return call_user_func_array(
            $this->_plugins['resource'][$params['resource_type']][0][2],
            array($params['resource_name'], &$this));
    }

    return false;
}

/* vim: set expandtab: */

?>