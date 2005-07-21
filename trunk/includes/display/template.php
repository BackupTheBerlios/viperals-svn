<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright 2004 - 2005										//
//  By Ryan Marshall ( Viperal )								//
//																//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
/*
	To-do cache daa.
	Area code for content
*/

class core_template
{
	var $cache = false;
	var $cache_includes = true;
	var $cache_handler = 'file';
	var $compile_check = true;
	var $template_dir;

	var $_in_cache = 0;
	var $_compiled_code;
	var $_vars = array();
	var $_tag_holding = array();

    function core_template()
    {
		global $site_file_root;
		$this->cache_dir = $site_file_root.'cache/template/';
    }
    
    /*
		Assign variable to be used in template
    */
    function assign($var, $value = false)
    {
        if (is_array($var))
        {
			foreach ($var as $name => $value)
            {
				$this->_vars[$name] = $value;
			}
        }
        else
        {
			$this->_vars[$var] = $value;
		}
    }

	/*
		Assign variable that are part of a Loop
    */
	function assign_vars_array($name, $value)
	{
		$this->_vars[$name][] = $value;
	}

    /*
		Return Currently stored variable is any, NULL is it's unset
    */
    function get_vars($name)
    {
		return (!empty($this->_vars[$name])) ? $this->_vars[$name] : NULL;
    }
    
	/*
		Remove an asigned var or var_array
    */
	function remove_vars($var)
    {
		if (is_array($var))
		{
			foreach ($var as $name)
			{
				$this->remove_vars($name);
			}
		}
		else
		{
			unset($this->_vars[$var]);
		}
    }

    /*
		Display template
		This loads the template if it exsists else parses it before displaying
		
		To Do:
			Make an template cache load/save class extent.
				You never know what people my want to do.

    */
	function display($name, $return = false, $cache = true)
	{
		global $_CLASS;

		if (!$this->generate_dirs($name))
		{
			return;
		}

		if (($this->cache && $cache) || $return)
		{
			$this->_cache_start($cache);
		}

		if ($file = $this->is_compiled($name))
		{
			include($file);
		}
		else
		{
			if ($this->compile($name))
			{
				if (!$this->_compile_save($name));
				{
					// Admin error, but we only want this once.
				}

				eval(' ?>' . $this->_compiled_code . '<?php ');

				unset($this->_compiled_code);
			}
		}

		if ($this->_in_cache)
		{
			// return true if it's parent template
			// Maybe return the include level as an intergers ?
			if ($this->_cache_stop($name))
			{
				$cache = $this->_cache_process();

				if ($return)
				{
					return $cache;
				}
			}
		}
    }

    /*
		Display template
		This loads the template if it exsists else parses it before displaying
    */
	function generate_dirs($name)
	{
		global $_CLASS, $site_file_root;

		$set = false;

		if (!empty($_CLASS['core_display']) && file_exists($site_file_root."themes/{$_CLASS['core_display']->theme}/template/$name"))
		{
			$this->template_dir = "themes/{$_CLASS['core_display']->theme}/template/";
			$this->theme_themplate = true;
			$set = true;
		}
		elseif (file_exists($site_file_root."includes/templates/$name"))
		{
			$this->template_dir = 'includes/templates/';
			$this->theme_themplate = false;
			$set = true;
		}
		
		if ($set)
		{
			$this->template_dir = $site_file_root.$this->template_dir;
		}

		return $set;
	}

	function generate_name($name)
	{
		global $_CLASS;

		$file_name = str_replace(array('.', '/'), '#', $name);

		return (($this->theme_themplate) ? $_CLASS['core_display']->theme.'#' : '') . "$file_name.php";
	}

    function is_compiled($name)
    {
		$cache_location = $this->cache_dir.$this->generate_name($name);

		if (file_exists($cache_location))
        {
			if (!$this->compile_check)
            {
                return $cache_location;
            }
            elseif (filemtime($this->template_dir.$name) < filemtime($cache_location))
            {
				return $cache_location;
			}
		}

		return false;
    }

	/*
		Compile the template into a php friendly format
	*/
    function compile($name, $code = false)
    {
		if (!$code)
		{
			$code = file_get_contents($this->template_dir.$name);
	
			if ($code === false)
			{
				return false;
			}
		}

		preg_match_all('/\<!--[ ]*(.*?) (.*?)?[ ]*--\>/', $code, $tag_blocks);
		$content_blocks = preg_split('/\<!--[ ]*(.*?) (.*?)?[ ]*--\>/', $code);

		$size = count($content_blocks);

		$parse_content = $compile = true;
		$tag_holding = array();
		$line = 1;

		for ($loop = 0; $loop < $size; $loop++)
		{
			$line += substr_count($content_blocks[$loop], "\n");

			if (!$compile)
			{
				if (strtoupper(trim($tag_blocks[1][$loop])) == 'ENDIGNORE')
				{
					$compile = true;
				}
				continue;
			}

			if ($parse_content)
			{
				$content_blocks[$loop] = $this->_parse_content($content_blocks[$loop]);
			}

			if (empty($tag_blocks[1][$loop]))
			{
				continue;
			}

			switch (strtoupper(trim($tag_blocks[1][$loop])))
			{
				case 'IF':
					$this->_tag_holding_add('IF', $line);

					$tag_blocks[0][$loop] = $this->_compile_tag_if($tag_blocks[2][$loop]);
				break;

				case 'ELSE':
					$last_tag = $this->_tag_holding_view();

					if (!$last_tag || $last_tag['name'] != 'IF')
					{
						// Error here
					}

					$tag_blocks[0][$loop] = '<?php } else { ?>';
				break;

				case 'ELSEIF':
					$last_tag = $this->_tag_holding_view();

					if (!$last_tag || $last_tag['name'] != 'IF')
					{
						// Error here
					}

					$tag_blocks[0][$loop] = $this->_compile_tag_if($tag_blocks[2][$loop], true);
				break;

				case 'ENDIF':
					$last_tag = $this->_tag_holding_get();

					if (!$last_tag || $last_tag['name'] != 'IF')
					{
						// Error here
					}

					$tag_blocks[0][$loop] = '<?php } ?>';
				break;
				
				case 'LOOP':
					$this->_tag_holding_add('LOOP', $line);

					$tag_blocks[0][$loop] = $this->_compile_tag_loop($tag_blocks[2][$loop]);
				break;

				case 'LOOPELSE':
					$last_tag = $this->_tag_holding_get();

					if (!$last_tag || $last_tag['name'] != 'LOOP')
					{
						// Error here
					}

					$this->_tag_holding_add('LOOPELSE', $line);

					$tag_blocks[0][$loop] = '<?php } } else {  ?>';
				break;
					
				case 'ENDLOOP':
					$last_tag = $this->_tag_holding_get();

					if (!$last_tag || $last_tag['name'] != ('LOOP' || 'LOOPELSE'))
					{
						// Error here
					}

					$tag_blocks[0][$loop] = $this->_compile_tag_endloop($tag_blocks[2][$loop], $last_tag['name']);
				break;

				case 'PHP':
					$this->_tag_holding_add('PHP', $line);

					$parse_content = false;
					$tag_blocks[0][$loop] = '<?php ';
				break;

				case 'ENDPHP':
					$last_tag = $this->_tag_holding_get();
					
					if (!$last_tag || $last_tag['name'] != 'PHP')
					{
						// Error here
					}

					$parse_content = true;
					$tag_blocks[0][$loop] = ' ?>';
				break;

				case 'INCLUDE':
					$tag_blocks[0][$loop] = $this->_compile_tag_include($tag_blocks[2][$loop]);
				break;

				case 'DISPLAY_HEADER':
					$tag_blocks[0][$loop] = "<?php echo \$_CLASS['core_display']->display_header(); ?>";
				break;

				case 'DISPLAY_FOOTER':
					$tag_blocks[0][$loop] = "<?php echo \$_CLASS['core_display']->display_footer(); ?>";
				break;
	
				case 'IGNORE':
					$compile = false;
				break;

				case 'DEFINE':
					$tag_blocks[0][$loop] = $this->_compile_tag_define($tag_blocks[2][$loop]);
				break;

				case 'AREA':
					//$tag_blocks[0][$loop] = $this->_compile_tag_area($tag_blocks[2][$loop]);
				break;

				default:
					//$tag_blocks[0][$loop] = '<!-- '.$tag_blocks[1][$loop].' '.$tag_blocks[2][$loop].' -->';
				break;
			}
		}

		$this->_compiled_code = '';
		$size = count($content_blocks);

		for ($loop = 0; $loop < $size; $loop++)
		{
			$this->_compiled_code .= $content_blocks[$loop] . (isset($tag_blocks[0][$loop]) ? $tag_blocks[0][$loop] : '');
		}

		return true;
    }

	/*
		Handles template language
	*/
	
	function _tag_holding_add($name, $line)
	{
		$this->_tag_holding[] = array('name' => $name, 'line' => $line);
	}

	function _tag_holding_get($option = 'last')
	{
		return array_pop($this->_tag_holding);
	}

	function _tag_holding_view($option = 'last')
	{
		// array_pop kills the array :-(
		return is_array($this->_tag_holding) ? end($this->_tag_holding) : false;
	}

	function _get_lang($lang)
	{
		global $_CLASS;

		return $_CLASS['core_user']->get_lang($lang);
	}

	function _parse_content($code)
	{
		if (strpos($code, '{') === false)
		{
			return $code;
		}
		
		$size = (int) preg_match_all('/\{(.*?)\}/', $code, $values);
		
		for ($loop = 0; $loop < $size; $loop++)
		{
			$parse = $this->_parse_var($values[1][$loop]);

			if (!$parse)
			{
				unset($values[1][$loop], $values[0][$loop]);
				continue;
			}
			
			$values[1][$loop] = '<?php echo '.$parse.'; ?>';
		}
	
		return str_replace($values[0], $values[1], $code);
	}

	/*
		Parse all content enclosed in { }
		Asisgned variables { $VAR } 
		Loop array variables { $ARRAY:VAR }
		Specail array Var { $ARRAY:#LOOP_INDEX }
	*/

	function _parse_var($var)
    {
		// Is it something to be parsed ?
		$var = trim($var);

		if (!$var || (strpos($var, '$') !== 0 && strpos($var, '#') !== 0))
		{
			//return $var;
			return false;
		}
		
		if (strpos($var, '$L_') === 0)
		{
			$var = substr($var, 1);
			return "(isset(\$this->_vars['$var'])) ? \$this->_vars['$var'] : \$this->_get_lang('".substr($var, 2)."')";
		}

		$vars = explode(':', $var);
		$in_loop = false;
		
		$size = count($vars);

		for ($loop = 0; $loop < $size; $loop++)
		{
			if (!$in_loop)
			{
				if (strpos($vars[$loop], '#') !== 0)
				{
					$vars[$loop] = substr($vars[$loop], 1);

					$output = "\$this->_vars['$vars[$loop]']";
					$in_loop = true;
					
					continue;
				}

				$vars[$loop] = substr($vars[$loop], 1);
				$output = "\$this->_vars['defines']['$vars[$loop]']";
			}
			else
			{
				$loop_name = strtolower($vars[($loop - 1)]);

				if (strpos($vars[$loop], '#') !== 0)
				{
					$output .= "[\$this->_loop['$loop_name']['index']]['$vars[$loop]']";

					continue;
				}

				$vars[$loop] = substr($vars[$loop], 1);

				Switch ($vars[$loop])
				{
					case 'LOOP_INDEX':
						$output = "\$this->_loop['$loop_name']['index']";
					break;

					case 'LOOP_NUMBER':
						$output = "(\$this->_loop['$loop_name']['index'] + 1)";
					break;
					
					case 'LOOP_SIZE':
						$output = "\$this->_loop['$loop_name']['count']";
					break;
					
					default:
						$output = "\$this->_vars['defines']['$vars[$loop]']";
					break;
				}
				
			}
		}
        return $output;
    }

	/*
		Parse Define tags
		Format: 
		
		This is a templete made variable
    */
	function _compile_tag_define($options)
	{
		$options = explode('=', $options);
		$size = count($options);
		$output = '';

		for ($loop = 0; $loop < $size; $loop++)
		{
			//need to addslashed, check for variable type
			$value = trim($options[($loop + 1)]);
			$output .= "\$this->_vars['defines']['".trim($options[$loop])."'] = ".(is_integer($value) ? $value : "'".str_replace("'", "\'", $value)."'");
			$loop++;
		}

		return "<?php $output ?>";
	}
	
    /*
		Parse If tags
		Format: <!-- if isset({ $S_FORUM_RULES }) || { $S_FORUM_RULES } -->
		
		To Do: add user frendly tag NOT, etc
			<!-- if isset({ $S_FORUM_RULES }) || NOT { $S_FORUM_RULES } -->
    */
	function _compile_tag_if($options, $elseif = false)
	{
		//strtok()
		
		$size = (int) preg_match_all('/\{(.*?)\}/', $options, $values);

		for ($loop = 0; $loop < $size; $loop++)
		{
			$parse = $this->_parse_var($values[1][$loop]);

			if (!$parse)
			{
				unset($values[1][$loop], $values[0][$loop]);
				continue;
			}
			
			$values[1][$loop] = $parse;
		}

		$options = str_replace($values[0], $values[1], $options);

		return '<?php '.(($elseif) ? '} elseif (' : 'if (') . "$options) {  ?>";
	}

	//<!-- LOOP $loop_rules -->
	function _compile_tag_loop($options)
    {
		//too do
		//<!-- LOOP $loop_{ $blablaa } -->
		//	change loopcode to have (), isset(($loop_code))
		// Parse through the tag's attributes
		if ($count = preg_match_all('/[ ](.*?)="(.*?)"/', $options, $matches))
		{
			$options = str_replace($matches[0], '', $options);

			//print_r($matches[0]);
			for ($loop = 0; $loop < $count; $loop++)
			{
				${$matches[1][$loop]} = $matches[2][$loop];
			}
		}

		$options = trim($options);
		$postition = strrpos($options, ':');
		$loop_code = $this->_parse_var($options);

		$options = ($postition !== false) ? substr($options, $postition + 1) : substr($options, 1);
		$loop_name = strtolower(str_replace('#' , '_', $options));

        $output =  "\n\$this->_loop['$loop_name']['count'] = isset($loop_code) ? (is_integer($loop_code) ? $loop_code : (is_array($loop_code) ? count($loop_code) : false)) : false;\n";
        $output .= "if (\$this->_loop['$loop_name']['count']) {\n";

		$output .= "for (\$this->_loop['$loop_name']['index'] = 0; \$this->_loop['$loop_name']['index'] < \$this->_loop['$loop_name']['count']; \$this->_loop['$loop_name']['index']++) {";
        return "<?php $output ?>";
    }

    //<!-- ENDLOOP $loop_rules -->
	function _compile_tag_endloop($options, $last_tag)
    {
		$output = '}'.(($last_tag == 'LOOP') ? ' } ' : '');

		if ($options = trim($options))
		{
			$postition = strrpos($options, ':');
			$loop_code = $this->_parse_var($options);

			$options = ($postition !== false) ? substr($options, $postition + 1) : substr($options, 1);
			$loop_name = strtolower(str_replace('#' , '_', $options));
			$output .= " unset(\$this->_loop['$loop_name'], $loop_code);";
		}

        return "<?php $output ?>";
    }

	function _compile_tag_include($options)
	{
		return "<?php \$this->display('$options'); ?>";
	}

	function _compile_tag_area($options)
	{
		return "<?php \$this->set_area('$var', '$index'); ?>";
	}

	function _compile_save($name, $data = false)
	{
		$cache_location = $this->cache_dir.$this->generate_name($name);

		if ($fp = fopen($cache_location, 'wb'))
		{
			@flock($fp, LOCK_EX);
			fwrite ($fp, (($data) ? $data : $this->_compiled_code));
			@flock($fp, LOCK_UN);
			fclose($fp);
			
			return file_exists($cache_location);
		}

		return false;
	}
	
	function _cache_start($name)
	{
		if ($this->_in_cache)
		{
			if (!$this->cache_includes)
			{
				//echo '<<-- ?'.$this->_in_cache.'? -->>';
				//ob_start();
			}
		}
		else
		{
			ob_start();
		}
		
		$this->_in_cache++;
	}
	
	function _cache_stop($name)
	{
		$this->_in_cache--;

		if (!$this->_in_cache)
		{
			$this->_cache['main'] = ob_get_clean();
			return true;
		}
	}

	function _cache_process()
	{
		$output = $this->_cache['main'];
		$this->_cache = array();

		return $output;
	}
}