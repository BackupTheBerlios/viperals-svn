<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Handle insert tags
 *
 * @param array $args
 * @return string
 */
function smarty_core_run_insert_handler($params, &$this)
{

    require_once(SMARTY_CORE_DIR . 'core.get_microtime.php');
    if ($this->debugging) {
        $_params = array();
        $_debug_start_time = smarty_core_get_microtime($_params, $this);
    }

    if ($this->caching) {
        $_arg_string = serialize($params['args']);
        $_name = $params['args']['name'];
        if (!isset($this->_cache_info['insert_tags'][$_name])) {
            $this->_cache_info['insert_tags'][$_name] = array('insert',
                                                             $_name,
                                                             $this->_plugins['insert'][$_name][1],
                                                             $this->_plugins['insert'][$_name][2],
                                                             !empty($params['args']['script']) ? true : false);
        }
        return $this->_smarty_md5."{insert_cache $_arg_string}".$this->_smarty_md5;
    } else {
        if (isset($params['args']['script'])) {
            $_params = array('resource_name' => $this->_dequote($params['args']['script']));
            require_once(SMARTY_CORE_DIR . 'core.get_php_resource.php');
            if(!smarty_core_get_php_resource($_params, $this)) {
                return false;
            }

            if ($_params['resource_type'] == 'file') {
                $this->_include($_params['php_resource'], true);
            } else {
                $this->_eval($_params['php_resource']);
            }
            unset($params['args']['script']);
        }

        $_funcname = $this->_plugins['insert'][$params['args']['name']][0];
        $_content = $_funcname($params['args'], $this);
        if ($this->debugging) {
            $_params = array();
            require_once(SMARTY_CORE_DIR . 'core.get_microtime.php');
            $this->_smarty_debug_info[] = array('type'      => 'insert',
                                                'filename'  => 'insert_'.$params['args']['name'],
                                                'depth'     => $this->_inclusion_depth,
                                                'exec_time' => smarty_core_get_microtime($_params, $this) - $_debug_start_time);
        }

        if (!empty($params['args']["assign"])) {
            $this->assign($params['args']["assign"], $_content);
        } else {
            return $_content;
        }
    }
}

/* vim: set expandtab: */

?>