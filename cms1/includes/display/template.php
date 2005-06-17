<?php

class core_template
{
	var $template_dir;
	var $compile_check = true;

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
    */
	function display($name, $allow_theme = true)
	{
		if (!$this->generate_dirs($name))
		{
			return;
		}

		if ($file = $this->is_compiled($name))
		{
			include($file);
			return;
		}
		else
		{
			if ($this->compile($name))
			{
				if ($this->_compile_save($name))
				{
					include($this->cache_dir.$this->generate_name($name));
				}
				else
				{
					eval(' ?>' . $this->_compiled_code . '<?php ');
				}

				unset($this->_compiled_code);
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
		
		Idea Based from phpBB3
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

		for ($loop = 0; $loop < $size; $loop++)
		{
			$content_blocks[$loop] = $this->_parse_content($content_blocks[$loop]);
		}

		$size = count($tag_blocks[1]);
		$tag_holding = array();

		for ($loop = 0; $loop < $size; $loop++)
		{
			switch (strtoupper(trim($tag_blocks[1][$loop])))
			{
				case 'IF':
					$tag_holding[] = 'IF';
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_if($tag_blocks[2][$loop]) . ' ?>';
					break;

				case 'ELSE':
					if (end($tag_holding)  != 'IF')
					{
						// Error here
					}
					
					$tag_blocks[0][$loop] = '<?php } else { ?>';
					break;

				case 'ELSEIF':
					if (end($tag_holding)  != 'IF')
					{
						// Error here
					}
					
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_if($tag_blocks[2][$loop], true) . ' ?>';
					break;

				case 'ENDIF':
					if (array_pop($tag_holding) != 'IF')
					{
						// Error here
					}

					$tag_blocks[0][$loop] = '<?php } ?>';
					break;
				
				case 'LOOP':
					$tag_holding[] = 'LOOP';
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_loop($tag_blocks[2][$loop]) . ' ?>';
					break;

				case 'LOOPELSE':
					if (array_pop($tag_holding) != 'LOOP')
					{
						// Error here
					}
					
					$tag_holding[] = 'LOOPELSE';
					$tag_blocks[0][$loop] = '<?php } } else {  ?>';
					break;
					
				case 'ENDLOOP':
					$last_tag = array_pop($tag_holding);
					
					if ($last_tag != ('LOOP' || 'LOOPELSE'))
					{
						// Error here
					}

					$tag_blocks[0][$loop] = '<?php }'.(($last_tag == 'LOOP') ? ' } ' : '').' ?>';
					break;
				
				case 'INCLUDE':
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_include($tag_blocks[2][$loop]) . ' ?>';
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
	function _get_lang($lang)
	{
		global $_CLASS;

		return $_CLASS['core_user']->get_lang(substr($lang, 2));
	}

	function _parse_content($code)
	{
		if (is_integer($code) || (strpos($code, '{') === false))
		{
			return $code;
		}
		
		preg_match_all('/\{(.*?)\}/', $code, $values);
		
		$size = count($values[1]);

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
		
		To DO: Defines
			{ #VAR }
	*/
	function _parse_var($var)
    {
		// Is it something to be parsed ?
		$var = trim($var);

		if (!$var || strpos($var, '$') !== 0)
		{
			//return $var;
			return false;
		}
		
		$var = substr($var, 1);

		if (strpos($var, 'L_') !== false)
		{
			return "(isset(\$this->_vars['$var'])) ? \$this->_vars['$var'] : \$this->_get_lang('$var')";
		}

		$vars = explode(':', $var);
		$in_loop = false;

		$size = count($vars);

		for ($loop = 0; $loop < $size; $loop++)
		{
			if (!$in_loop)
			{
				$_output = "\$this->_vars['$vars[$loop]']";
				$in_loop = true;
			}
			else
			{
				if (strpos($vars[$loop], '#') !== 0)
				{
					$loop_name = '_'.strtolower($vars[($loop - 1)]);
					$_output .= "[\$this->_loop['$loop_name']]['$vars[$loop]']";
				}
				else
				{
					$var = substr($vars[$loop], 1);
					Switch ($var)
					{
						case 'LOOP_INDEX':
							$loop_name = '_'.strtolower($vars[($loop - 1)]);
							$_output = "\$this->_loop['$loop_name']";
						break;

						case 'LOOP_NUMBER':
							$loop_name = '_'.strtolower($vars[($loop - 1)]);
							$_output = "\$this->_loop['$loop_name'] + 1";
						break;
						
						case 'LOOP_SIZE':
							$_output = "count(\$this->_vars['$vars[$loop]']);";
						break;
					}
				}
			}
		}
		
        return $_output;
    }
    
    /*
		Parse If tags
		Format: <!-- if isset({ $S_FORUM_RULES }) || { $S_FORUM_RULES } -->
		
		To Do: add user frendly tag NOT, etc
			<!-- if isset({ $S_FORUM_RULES }) || { NOT $S_FORUM_RULES } -->
    */
	function compile_tag_if($options, $elseif = false)
	{
		//strtok()
		
		preg_match_all('/\{(.*?)\}/', $options, $values);
		$size = count($values[1]);

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

		return (($elseif) ? '} elseif (' : 'if (') . ($options.') { ');
	}

	//<!-- LOOP $loop_rules -->
	function compile_tag_loop($options)
    {
		//too do
		//<!-- LOOP $loop_{ $blablaa } -->

		$optiosn = trim($options);
		$loop_code = $this->_parse_var(trim($options));

		$options = substr($options, 1);
		$loop_name = '_'.strtolower(str_replace(array(':', '#') , '_', $options));

        $output =  "\n\${$loop_name}_count = (isset($loop_code)) ? ((is_integer($loop_code)) ? $loop_code : count($loop_code)) : false;\n";
        $output .= "if (\${$loop_name}_count) {\n";

		$output .= "for (\$this->_loop['$loop_name'] = 0; \$this->_loop['$loop_name'] < \${$loop_name}_count; \$this->_loop['$loop_name']++) {";
        return $output;
    }
    
	function compile_tag_include($options)
	{
		return "\$this->display('$options');";
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
}