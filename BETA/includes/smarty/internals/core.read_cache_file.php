<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * read a cache file, determine if it needs to be
 * regenerated or not
 *
 * @param string $tpl_file
 * @param string $cache_id
 * @param string $compile_id
 * @param string $results
 * @return boolean
 */

//  $tpl_file, $cache_id, $compile_id, &$results

function smarty_core_read_cache_file(&$params, &$this)
{
    static  $content_cache = array();

    if ($this->force_compile) {
        // force compile enabled, always regenerate
        return false;
    }

    if (isset($content_cache[$params['tpl_file'].','.$params['cache_id'].','.$params['compile_id']])) {
        list($params['results'], $this->_cache_info) = $content_cache[$params['tpl_file'].','.$params['cache_id'].','.$params['compile_id']];
        return true;
    }

    if (!empty($this->cache_handler_func)) {
        // use cache_handler function
        call_user_func_array($this->cache_handler_func,
                             array('read', &$this, &$params['results'], $params['tpl_file'], $params['cache_id'], $params['compile_id'], null));
    } else {
        // use local cache file
        $_auto_id = $this->_get_auto_id($params['cache_id'], $params['compile_id']);
        $_cache_file = $this->_get_auto_filename($this->cache_dir, $params['tpl_file'], $_auto_id);
        $params['results'] = $this->_read_file($_cache_file);
    }

    if (empty($params['results'])) {
        // nothing to parse (error?), regenerate cache
        return false;
    }

    $cache_split = explode("\n", $params['results'], 2);
    $cache_header = $cache_split[0];

    $_cache_info = unserialize($cache_header);

    if ($this->caching == 2 && isset ($_cache_info['expires'])){
        // caching by expiration time
        if ($_cache_info['expires'] > -1 && (time() > $_cache_info['expires'])) {
            // cache expired, regenerate
            return false;
        }
    } else {
        // caching by lifetime
        if ($this->cache_lifetime > -1 && (time() - $_cache_info['timestamp'] > $this->cache_lifetime)) {
            // cache expired, regenerate
            return false;
        }
    }

    if ($this->compile_check) {
        $_params = array('get_source' => false, 'quiet'=>true);
        foreach (array_keys($_cache_info['template']) as $_template_dep) {
            $_params['resource_name'] = $_template_dep;
            if (!$this->_fetch_resource_info($_params) || $_cache_info['timestamp'] < $_params['resource_timestamp']) {
                // template file has changed, regenerate cache
                return false;
            }
        }

        if (isset($_cache_info['config'])) {
            $_params = array('resource_base_path' => $this->config_dir, 'get_source' => false, 'quiet'=>true);
            foreach (array_keys($_cache_info['config']) as $_config_dep) {
                $_params['resource_name'] = $_config_dep;
                if (!$this->_fetch_resource_info($_params) || $_cache_info['timestamp'] < $_params['resource_timestamp']) {
                    // config file has changed, regenerate cache
                    return false;
                }
            }
        }
    }

    foreach ($_cache_info['cache_serials'] as $_include_file_path=>$_cache_serial) {
        if (empty($this->_cache_serials[$_include_file_path])) {
            $this->_include($_include_file_path, true);
        }

        if ($this->_cache_serials[$_include_file_path] != $_cache_serial) {
            /* regenerate */
            return false;
        }
    }
    $params['results'] = $cache_split[1];
    $content_cache[$params['tpl_file'].','.$params['cache_id'].','.$params['compile_id']] = array($params['results'], $_cache_info);

    $this->_cache_info = $_cache_info;
    return true;
}

/* vim: set expandtab: */

?>