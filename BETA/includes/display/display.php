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

class display
{

	var $header = array('js'=>'', 'regular'=>'', 'meta'=>'', 'body'=>'');
	var $displayed = array('header'=> false, 'footer'=> false);
	var $message = '';
	var $theme = false;
	var $homepage = false;
	
	function display() {
		global $_CLASS, $MAIN_CFG, $SID;
		
		$this->siteurl = getenv('HTTP_HOST').$MAIN_CFG['server']['path'];
		$this->copyright = 'Powered by <a href="http://www.viperal.com">Viperal CMS</a> (c) 2004 Viperal';
		
		$this->themeprev = get_variable('prevtheme', 'POST', false);
		$this->themeprev = ($this->themeprev) ? $this->themeprev : get_variable('prevtheme', 'GET', false);
		
		$this->theme = $_CLASS['user']->get_data('theme');
		
		if ($this->themeprev && ($this->themeprev != $this->theme) && $this->check_theme($this->themeprev))
		{
			$this->theme = $this->themeprev;
			define('THEMEPLATE', $this->temp);
			$_CLASS['user']->set_data('theme', $this->theme);
			
		}
		elseif ($this->theme && $this->check_theme($this->theme))
		{
			define('THEMEPLATE', $this->temp);
		}
		else
		{
           	$this->theme = ($_CLASS['user']->data['theme']) ? $_CLASS['user']->data['theme'] : $MAIN_CFG['global']['default_theme'];     
	
			if ($this->check_theme($this->theme))
			{
				define('THEMEPLATE', $this->temp);
				
			} elseif ($this->check_theme($MAIN_CFG['global']['default_theme'])) {
				
				$this->theme = $MAIN_CFG['global']['default_theme'];
				define('THEMEPLATE', $this->temp);
				
			} else {
				// error here the site has no theme
			}
		}
    	
    	//dam initalizing
    	$_CLASS['template']->assign(array(
			'messageblock'		=>	false,
			'centerblock'		=>	false,
			'bottomblock'		=>	false,
			'MAIN_CONTENT'		=>	false,
			)
		);
				
		if (THEMEPLATE)
		{
			require('themes/'.$this->theme.'/index.php');
		} else {
			require('themes/'.$this->theme.'/theme.php');
		}
	}
	
	function check_theme($theme)
	{
		if (is_dir('themes/'.$theme))
		{
			if (file_exists('themes/'.$theme.'/index.php'))
			{
				$this->temp = true;
				return '1';
			}
			elseif (file_exists('themes/'.$this->themeprev.'/theme.php'))
			{
				$this->temp = false;
				return '2';
			}
		}
		return false;
	}
	
	function display_head($title = false) {
		global $_CLASS, $MAIN_CFG, $Module;
		
		if ($this->displayed['header'])
		{
			return;
		}
		
		$this->displayed['header'] = true;

		if ($title)
		{
			$Module['custom_title'] = $title;
		}
		
		//ini_set('default_mimetype', 'text/html');
		//ini_set('default_charset', $_CLASS['user']->lang['ENCODING']);
		
		header('Content-Type: text/html; charset='.$_CLASS['user']->lang['ENCODING']);
		header('Content-language: ' . $_CLASS['user']->lang['LANG']);
		
		header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . " GMT" );
		header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0, max-age=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		if (is_user() && $_CLASS['user']->data['user_new_privmsg'] && $_CLASS['user']->optionget('popuppm'))
		{
			if (!$_CLASS['user']->data['user_last_privmsg'] || $_CLASS['user']->data['user_last_privmsg'] > $_CLASS['user']->data['session_last_visit'])
			{
				$this->header['js'] .= '<script type="text/javascript">window.open(\''. ereg_replace('&amp;', '&', getlink('Control_Panel&i=pm&mode=popup', false, true))."', '_phpbbprivmsg', 'HEIGHT=225,resizable=yes,WIDTH=400');</script>";

				if (!$_CLASS['user']->data['user_last_privmsg'] || $_CLASS['user']->data['user_last_privmsg'] > $_CLASS['user']->data['session_last_visit'])
				{
					$sql = 'UPDATE ' . USERS_TABLE . '
						SET user_last_privmsg = ' . time() . '
						WHERE user_id = ' . $_CLASS['user']->data['user_id'];
					$_CLASS['db']->sql_query($sql);
				}
			}
		}

		$this->header['regular'] .= '<meta name="generator" content="Viperal CMS ( www.viperal.com ) Copyright(c) '.date('Y').'" />';
		
		if (file_exists('favicon.ico')) {
			$this->header['regular'] .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';
		}
		
		$this->header['regular'] .= '<link rel="alternate" type="application/xml" title="RSS" href="http://'.$this->siteurl.'backend.php?feed=rdf" />';
		
		if ($MAIN_CFG['global']['block_frames']) {
			$this->header['js'] .= '<script type="text/javascript">if (self != top) top.location.replace(self.location)</script>';
		}
		
		if ($MAIN_CFG['global']['maintenance']) {
			$this->message = 'Note your in Maintenance mode<br />';
		}
	
		$_CLASS['template']->assign(array(
			'SITE_LANG'			=>	$_CLASS['user']->lang['LANG'],
			'SITE_TITLE'		=>	$MAIN_CFG['global']['sitename'].': '.$Module['custom_title'],
			'SITE_BASE'			=>	$this->siteurl,
			'SITE_CHARSET'		=>	$_CLASS['user']->lang['ENCODING'],
			'SITE_NAME'			=>	$MAIN_CFG['global']['sitename'],
			'SITE_URL'			=>	$MAIN_CFG['global']['nukeurl'],
			'HEADER_MESSAGE'	=>	$this->message,
			'HEADER_REGULAR'	=>	$this->header['meta'].$this->header['regular'],
			'HEADER_JS' 		=>	$this->header['js'],
			'HEADER_BODY' 		=>	$this->header['body']				
			)
		);
		
		if (THEMEPLATE) {
		
			themehead();
			
		} else {
			$_CLASS['template']->display('head.html');
			themeheader();
			
			if ($this->message)
			{
				echo '<div style="text-align: center; font-size: 16px; color: #FF0000">'.$this->message.'</div>';
			}
		}
		
		$_CLASS['blocks']->display(BLOCK_MESSAGE);
		
		if ($this->homepage)
		{  
			$_CLASS['blocks']->display(BLOCK_TOP);
		}
		
		if (THEMEPLATE)
		{
			$_CLASS['template']->display('header.html');
		}
		
		if ($_CLASS['editor'])
		{
			$_CLASS['editor']->display();
		}

	}
	
	function display_footer() {
		global $_CLASS, $phpEx, $MAIN_CFG;
		
		if ($this->displayed['footer'])
		{
			return;
		}
		
		$this->displayed['footer'] = true;
		
		if ($this->homepage)
		{
			$_CLASS['blocks']->display(BLOCK_BOTTOM);
		}
		
		if ($this->displayed['header'])
		{
			themefooter();
			echo '</body></html>';

		}
		
		script_close();

		die;	
	}
	
	function footer_debug() {
	
		global $MAIN_CFG, $SID, $mainindex, $SID, $_CLASS, $starttime;
	
		$mtime = explode(' ', microtime());
		$totaltime = ($mtime[0] + $mtime[1] - $starttime) - $_CLASS['db']->sql_time;
	
		$debug_output = 'Code Time : '.round($totaltime, 4).'s | Queries Time '.round($_CLASS['db']->sql_time, 4).'s | ' . $_CLASS['db']->sql_num_queries() . ' Queries  ] <br /> [ GZIP : ' .  ((array_key_exists('1' , ob_list_handlers())) ? 'On' : 'Off' ) . ' | Load : '  . (($_CLASS['user']->load) ? $_CLASS['user']->load : 'N/A');
		
		if (function_exists('memory_get_usage'))
		{
			if ($memory_usage = memory_get_usage())
			{
				global $base_memory_usage;
				$memory_usage -= $base_memory_usage;
				$memory_usage = ($memory_usage >= 1048576) ? round((round($memory_usage / 1048576 * 100) / 100), 2) . ' ' . $_CLASS['user']->lang['MB'] : (($memory_usage >= 1024) ? round((round($memory_usage / 1024 * 100) / 100), 2) . ' ' . $_CLASS['user']->lang['KB'] : $memory_usage . ' ' . $_CLASS['user']->lang['BYTES']);
		
				$debug_output .= ' | Memory Usage: ' . $memory_usage;	
			}
		}

		return $debug_output;
	}
	
	function footmsg() {
	
		global $MAIN_CFG;
		
		$footer = '';
		
		if ($MAIN_CFG['global']['foot1']) {
			$footer .= $MAIN_CFG['global']['foot1'] . '<br />';
		}
		if ($MAIN_CFG['global']['foot2']) {
			$footer .= $MAIN_CFG['global']['foot2']. '<br />';
		}
		if ($MAIN_CFG['global']['foot3']) {
			$footer .= $MAIN_CFG['global']['foot3'] . '<br />';
		}
				
		return $footer.'[ '.$this->footer_debug(). ']<br />'.$this->copyright;
	}
	
	function meta_refresh($time, $url)
	{
		global $_CLASS;
		$this->header['meta'] = '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '">';
	}
	
	function overLIB()
	{
		static $displayed = false;
		
		if ($displayed)
		{
			return;
		}
		
		$displayed = true;
		$this->header['js'] .= '<script type="text/javascript" src="/includes/javascript/overlib_mini.js"></script>';
		$this->header['body'] .= '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:10;"></div>';
	}
}

/*
$hiddenblocks = array();
if (isset($_COOKIE["hiddenblocks"])) {
	$tmphidden = explode(":", $_COOKIE["hiddenblocks"]);
	$tempcount = count($tmphidden);
	for($i=0; $i<$tempcount; $i++) {
		$hiddenblocks[$tmphidden[$i]] = true;
	}
	unset($tempcount);
}*/

function hideblock($id) {
    static $hiddenblocks = array();
    if (empty($hiddenblocks)) {
        if (isset($_COOKIE['hiddenblocks'])) {
            $tmphidden = explode(':', $_COOKIE['hiddenblocks']);
			$tempcount = count($tmphidden);
			for($i=0; $i< $tempcount; $i++) {
                $hiddenblocks[$tmphidden[$i]] = true;
            }
        }
    }
    return (empty($hiddenblocks[$id]) ? false : true);
}

function get_theme() {
    global $_CLASS;
    return $_CLASS['display']->theme;
}

function yesno_option($name, $value=0) {
    if (function_exists('theme_yesno_option')) {
        return theme_yesno_option($name, $value);
    } else {
        $sel[intval($value)] = ' checked="checked"';
        return '<input type="radio" name="'.$name.'" value="1"'.$sel[1].' />'._YES.' &nbsp; <input type="radio" name="'.$name.'" value="0" '.$sel[0].' />'._NO;
    }
}

function select_option($name, $default, $options) {
    if (function_exists('theme_select_option')) {
        return theme_select_option($name, $default, $options);
    } else {
        $select = '<select name="'.$name."\">\n";
        foreach($options as $var) {
            $select .= '<option'.(($var == $default)?' selected="selected"':'').">$var</option>\n";
        }
        return $select.'</select>';
    }
}

function select_box($name, $default, $options) {
    if (function_exists('theme_select_box')) {
        return theme_select_box($name, $default, $options);
    } else {
        $select = '<select name="'.$name."\">\n";
        foreach($options as $val => $title) {
			$var = (isset($val)) ? $val : '';
            $select .= "<option value=\"$val\"".(($var == $default)?' selected="selected"':'').">$title</option>\n";
        }
        return $select.'</select>';
    }
}

?>