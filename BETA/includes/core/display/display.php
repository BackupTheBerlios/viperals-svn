<?php
if (!CPG_NUKE) {
    Header('Location: ../../../');
    die();
}

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
		$this->copyright = 'Interactive software released under <a href="http://www.cpgnuke.com/index.php?name=GNUGPL" target="_new">GNU GPL 2</a>, <a href="'.getlink('Credits&amp;'.$SID).'">Code Credits</a>, <a href="'.getlink('Privacy_Policy&amp;'.$SID).'">Privacy Policy</a></div>';
		
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
           	$this->theme = ($_CLASS['user']->data['theme']) ? $_CLASS['user']->data['theme'] : $MAIN_CFG['global']['Default_Theme'];     
	
			if ($this->check_theme($this->theme))
			{
				define('THEMEPLATE', $this->temp);
			} else {
				define('THEMEPLATE', true);
				$this->theme = 'viperal';
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
	
	function display_head() {
		global $_CLASS, $MAIN_CFG, $Module;
		//global $modheade;
		
		if ($this->displayed['header'])
		{
			return;
		}

		header('Content-Type: text/html; charset='.$_CLASS['user']->lang['ENCODING']);
		header('Content-language: ' . $_CLASS['user']->lang['LANG']);
		header('P3P: CP="CAO DSP COR CURa ADMa DEVa OUR IND PHY ONL UNI COM NAV INT DEM PRE"');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . " GMT" );
		header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		if (is_user() && $_CLASS['user']->data['user_new_privmsg'] && $_CLASS['user']->optionget('popuppm'))
		{
			if (!$_CLASS['user']->data['user_last_privmsg'] || $_CLASS['user']->data['user_last_privmsg'] > $_CLASS['user']->data['session_last_visit'])
			{
				$this->header['js'] .= '<script type="text/javascript">window.open(\''. ereg_replace('&amp;', '&', getlink('Control_Panel&i=pm&mode=popup', false, true))."', '_phpbbprivmsg', 'HEIGHT=225,resizable=yes,WIDTH=400');</script>";

				$sql = 'UPDATE ' . USERS_TABLE . ' SET user_last_privmsg = ' . $_CLASS['user']->data['user_last_privmsg'] . ' WHERE user_id = ' . $_CLASS['user']->data['user_id'];
				$_CLASS['db']->sql_query($sql);
			}
		}

		
		$this->displayed['header'] = true;
		$year = date('Y');

		//require('includes/core/meta.php');
		$this->header['js'] .= '<script type="text/javascript" src="/includes/javascript/overlib_mini.js"></script>';
		
		$this->header['regular'] .= '<meta name="generator" content="CPG-Nuke - Copyright(c) '.$year.' by http://cpgnuke.com" />';
		
		// letts make a overDiv function for this remove it from being core core ;-0
		$this->header['body'] .= '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:10;"></div>';

		if (file_exists('themes/'.$this->theme.'/images/favicon.ico')) {
		
			$this->header['regular'] .= '<link rel="shortcut icon" href="themes/'.$this->theme.'/images/favicon.ico" type="image/x-icon" />';
		
		} elseif (file_exists('favicon.ico')) {
		
			$this->header['regular'] .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';
		
		}
		
		$this->header['regular'] .= '<link rel="alternate" type="application/xml" title="RSS" href="http://'.$this->siteurl.'backend.php?feed=rdf" />';
		
		if ($MAIN_CFG['global']['block_frames']) {
			$this->header['js'] .= '<script type="text/javascript">if (self != top) top.location.replace(self.location)</script>';
		}
		
		// Now what uses this !!
		//$this->header['regular'] .= $modheader;

		if ($MAIN_CFG['global']['maintenance']) {
			$this->message = 'Note admin your in Maintenance mode<br />';
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
		
		// Hell lets add this to the theme files so they can cutomize this.
		if ($this->homepage) {
			$_CLASS['template']->assign('PAGE_TITLE', ((CPG_NUKE == 'Admin') ? $Module['custom_title'] : _HOME));
		} else {
			$_CLASS['template']->assign('PAGE_TITLE', _HOME.' > '.$Module['custom_title']);
		}
		
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
		
		if (!defined('ADMIN_PAGES')) { require('includes/core/counter.php'); } // 2-3 queries

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

		// Now what uses this !!
		//unset($modheader);
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
			
		//Handle email/cron queue. // phpbb 2.1.2 only. add this to the forum section maybe !
		//Yeppy i should make my own, like the way it works lol
		//if (time() - $config['queue_interval'] >= $config['last_queue_run'] && !defined('IN_ADMIN'))
		//{
		// Arr crap pm and control panel uses this, :-S, add to script_close for now
			if (file_exists('cache/queue.' . $phpEx))
			{
				//requireOnce('includes/forums/
				requireOnce('includes/forums/functions_messenger.'.$phpEx);
				$queue = new queue();
				$queue->process();
			}
		//}
		
		script_close();
		die;	
	}
	
	function footer_debug() {
	
		global $MAIN_CFG, $_compression, $SID, $db, $mainindex, $SID, $_CLASS, $starttime;
	
		// Output page creation time directly from phpbb2.1.2
		$mtime = explode(' ', microtime());
		$totaltime = ($mtime[0] + $mtime[1] - $starttime) - $db->sql_time;
	
		$debug_output = 'Code Time : '.round($totaltime, 4).'s | Queries Time '.round($db->sql_time, 4).'s | ' . $db->sql_num_queries() . ' Queries  ] <br /> [ GZIP : ' .  (($_compression) ? 'On' : 'Off' ) . ' | Load : '  . (($_CLASS['user']->load) ? $_CLASS['user']->load : 'N/A');
		
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

// Meta refresh assignment


function get_theme() {
    global $_CLASS;
    return $_CLASS['display']->theme;
}

function get_rss($url) {
    $rdf = parse_url($url);
    $result = false;
    if ($fp = fsockopen($rdf['host'], 80, $errno, $errstr, 15)) {
        if ($rdf['query'] != '') $rdf['query'] = '?' . $rdf['query'];
        fputs($fp, 'GET ' . $rdf['path'] . $rdf['query'] . " HTTP/1.0\r\n");
        fputs($fp, "User-Agent: CPG-Nuke RSS/XML Reader\r\n"); // AlexM
        fputs($fp, 'HOST: ' . $rdf['host'] . "\r\n\r\n");
        $string = '';
        while(!feof($fp)) {
            $pagetext = fgets($fp, 300);
            $string .= chop($pagetext);
        }
        fputs($fp,"Connection: close\r\n\r\n");
        fclose($fp);
        $items = preg_replace('#\s#',' ',$items);
        $items = preg_split('#(</item>)#', $string, -1, PREG_SPLIT_NO_EMPTY);
        for ($i=0;$i<10;$i++) {
            $link = preg_replace('#(.*)<link>(.*)</link>(.*)#','\\2',$items[$i]);
            $title = preg_replace('#(.*)<title>(.*)</title>(.*)#','\\2',$items[$i]);
            if ($items[$i] != '' && strcmp($link,$title)) {
                $result[] = array('title'=>utf8_encode(strip_tags(urldecode($title))), 'link'=>strip_tags($link));
            }
        }
    }
    return $result;
}

function headlines($bid, $side=0, $row='') {
    global $prefix, $db;
    $bid = intval($bid);
    if (!is_array($row)) {
        $result = $db->sql_query('SELECT title, content, url, refresh, time FROM '.$prefix."_blocks WHERE bid='$bid'");
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
    }
    $title = $row['title'];
    $content = $row['content'];
    $url = $row['url'];
    $refresh = $row['refresh'];
    $otime = $row['time'];
    $past = time()-$refresh;
    if ($otime < $past) {
        $content = '';
        if ($items = get_rss($url)) {
            $content = '';
            for ($i=0;$i<count($items);$i++) {
                $link = $items[$i]['link'];
                $title2 = $items[$i]['title'];
                $content .= "<img src=\"images/arrow.gif\" border=\"0\" alt=\"\" title=\"\" width=\"9\" height=\"9\"><a href=\"$link\" target=\"new\">$title2</a><br />\n";
            }
        }
        $btime = time();
        $db->sql_query('UPDATE '.$prefix.'_blocks SET content=\''.addslashes($content)."', time='$btime' WHERE bid='$bid'");
    }
    $siteurl = ereg_replace('http://','',$url);
    $siteurl = explode('/',$siteurl);
    if ($content != '') {
        $content .= "<br /><a href=\"http://$siteurl[0]\" target=\"blank\"><b>"._HREADMORE.'</b></a>';
    } else {
        $content = _RSSPROBLEM;
    }
    $content = '<font class="content">'.$content.'</font>';
    if ($side == 'c' || $side == 'd') {
        themecenterbox($title, $content, $side);
    } else {
        themesidebox($title, $content, $bid);
    }
}

function hideblock($id) {
    static $hiddenblocks;
    if (!isset($hiddenblocks)) {
        $hiddenblocks = array();
        if (isset($_COOKIE['hiddenblocks'])) {
            $tmphidden = explode(':', $_COOKIE['hiddenblocks']);
			$tempcount = count($tmphidden);
			for($i=0; $i<$tempcount; $i++) {
                $hiddenblocks[$tmphidden[$i]] = true;
            }
        }
    }
    return (isset($hiddenblocks[$id]) ? $hiddenblocks[$id] : false);
}

function loginbox() {
// removed useless
}

/***********************************************************************************
 string yesno_option($name, $value=0)
 Creates 2 radio buttons with a Yes and No option
    $name : name for the <input>
    $value: current value, 1 = yes, 0 = no
************************************************************************************/
function yesno_option($name, $value=0) {
    if (function_exists('theme_yesno_option')) {
        return theme_yesno_option($name, $value);
    } else {
        $sel[intval($value)] = ' checked="checked"';
        return '<input type="radio" name="'.$name.'" value="1"'.$sel[1].' />'._YES.' &nbsp; <input type="radio" name="'.$name.'" value="0" '.$sel[0].' />'._NO;
    }
}

/***********************************************************************************
 string select_option($name, $value, $array)
 Creates a selection dropdown box of all given variables in the array
    $name : name for the <select>
    $value: current/default value
    $array: array like array("value1","value2")
************************************************************************************/
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

/***********************************************************************************
 string select_box($name, $value, $array)
 Creates a selection dropdown box of all given variables in the multi array
    $name : name for the <select>
    $value: current/default value
    $array: array like array("value1 => title1","value2 => title2")
************************************************************************************/
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