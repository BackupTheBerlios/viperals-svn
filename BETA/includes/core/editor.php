<?php
/*
copyright 2004 viperal
viperal.com

To do Add kCEditor to the class and more :-)
*/


if (!defined('CPG_NUKE')) {
    Header('Location: ../../');
    die();
}

class editor {
	var $editorids = '';
	var $location = '<script type="text/javascript" src="includes/htmlarea/htmlarea.js"></script>';
		
	function setup($id='', $type='basic')
	{
		global $_CLASS, $MAIN_CFG;
		$this->id = $id;
		$this->type = $type;
		$_CLASS['display']->header['js'] .= '<script type="text/javascript">_editor_url = "http://'.getenv('HTTP_HOST').$MAIN_CFG['server']['path'].'includes/htmlarea/";_editor_lang = "'.$_CLASS['user']->lang['LANG'].'";</script>';

	}
	
	function add_area($id)
	{
		$this->editorids .= 'HTMLArea.replace("' . $id . '", config);';
	}

	function display()
	{
		global $MAIN_CFG, $prefix, $admin;
		
		if (is_admin()) {
			
			$this->display_editor();
						
		   } else {
		
				switch ($this->id) {
				
				case '1':
					$this->display_editor();
					break;
					
				case '2':
					if (is_user()) {
						$this->display_editor();
					}
					break;
					
				case '3':
					break;

				case '> 3' :
					if (isset($userinfo['_mem_of_groups'][($view-2)])) {
						$this->display_editor();
					}
				}
		}
			
		return;
		
    }
    
    function display_editor()
	{
		if (file_exists('includes/editor/editor-'.$this->type.'.php')) {
			require('includes/editor/editor-'.$this->type.'.php');
		} else {
			require('includes/editor/editor-basic.php');
		}
	} 
}
?>