<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * called for included php files within templates
 *
 * @param string $this_file
 * @param string $this_assign variable to assign the included template's
 *               output into
 * @param boolean $this_once uses include_once if this is true
 * @param array $this_include_vars associative array of vars from
 *              {include file="blah" var=$var}
 */

//  $file, $assign, $once, $_smarty_include_vars

function smarty_core_smarty_include_php($params, &$this)
{
    $_params = array('resource_name' => $params['smarty_file']);
    require_once(SMARTY_CORE_DIR . 'core.get_php_resource.php');
    smarty_core_get_php_resource($_params, $this);
    $_smarty_resource_type = $_params['resource_type'];
    $_smarty_php_resource = $_params['php_resource'];

    if (!empty($params['smarty_assign'])) {
        ob_start();
        if ($_smarty_resource_type == 'file') {
            $this->_include($_smarty_php_resource, $params['smarty_once'], $params['smarty_include_vars']);
        } else {
            $this->_eval($_smarty_php_resource, $params['smarty_include_vars']);
        }
        $this->assign($params['smarty_assign'], ob_get_contents());
        ob_end_clean();
    } else {
        if ($_smarty_resource_type == 'file') {
            $this->_include($_smarty_php_resource, $params['smarty_once'], $params['smarty_include_vars']);
        } else {
            $this->_eval($_smarty_php_resource, $params['smarty_include_vars']);
        }
    }
}


/* vim: set expandtab: */

?>