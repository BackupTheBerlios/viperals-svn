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

    function get_vars($name)
    {
		return (!empty($this->_vars[$name])) ? $this->_vars[$name] : '';
    }
    
	function assign_vars_array($name, $value)
	{
		$this->_vars[$name][] = $value;
	}

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
    
	function display($name, $allow_theme = true)
	{
		if (!$this->generate_dirs($name))
		{
			return;
		}

		if ($file = $this->is_compiled($name))
		{
			include($file);
			//return;
		}
		else
		{
			if ($this->compile($name))
			{
				//echo $this->_compiled_code;
				if ($this->compile_save($name))
				{
					include($file);
				}
				else
				{
					eval(' ?>' . $this->_compiled_code . '<?php ');
				}

				unset($this->_compiled_code);
			}
		}
    }

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
// should we make a sub dir method ?
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
		Idea Based from phpBB3
	*/
    function compile($name)
    {
		$code = file_get_contents($this->template_dir.$name);

		if ($code === false)
		{
			return false;
		}

		preg_match_all('/\<!--[ ]*(.*?) (.*?)?[ ]*--\>/', $code, $tag_blocks);
		$content_blocks = preg_split('/\<!--[ ]*(.*?) (.*?)?[ ]*--\>/', $code);
		
		$size = count($content_blocks);

		for ($loop = 0; $loop < $size; $loop++)
		{
			$content_blocks[$loop] = $this->_parse_content($content_blocks[$loop]);
		}

		$compile_blocks = array();
		//print_r($tag_blocks);die;
		$size = count($tag_blocks[1]);

		for ($loop = 0; $loop < $size; $loop++)
		{
			switch (strtoupper(trim($tag_blocks[1][$loop])))
			{
				case 'IF':
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_if($tag_blocks[2][$loop]) . ' ?>';
					break;

				case 'ELSE':
					$tag_blocks[0][$loop] = '<?php } else { ?>';
					break;

				case 'ELSEIF':
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_if($tag_blocks[2][$loop], true) . ' ?>';
					break;

				case 'ENDIF':
					$tag_blocks[0][$loop] = '<?php } ?>';
					break;
				
				case 'LOOP':
					$in_loop =  true;
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_loop($tag_blocks[2][$loop]) . ' ?>';
					break;

				case 'LOOPELSE':
					$in_loop =  false;
					$tag_blocks[0][$loop] = '<?php } } else {  ?>';
					break;
					
				case 'ENDLOOP':
					$tag_blocks[0][$loop] = '<?php }'.(($in_loop) ? ' } array_pop($this->_loop);' : '').' ?>';
					$in_loop =  false;
					break;
				
				case 'INCLUDE':
					$tag_blocks[0][$loop] = '<?php ' . $this->compile_tag_include($tag_blocks[2][$loop]) . ' ?>';
					break;
					
				default:
					//$tag_blocks[0][$loop] = ''; // wow this was hard to figure out
					//$compile_blocks[] = '<!-- '.$tag_blocks[1][$loop].' '.$tag_blocks[2][$loop].' -->';
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
		//echo htmlentities($code);
    }

	// Handles template language
	function _get_lang($lang)
	{
		global $_CLASS;

		return $_CLASS['core_user']->get_lang(substr($lang, 2));
	}

	function _parse_content($code)
	{
		//$code = trim($code);
		if (is_integer($code) || (strpos($code, '{') === false))
		{
			return $code;
		}
		
		preg_match_all('/\{(.*?)\}/', $code, $values);
		
		//print_r($values);

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
		//print_r($values);
		$values[0] = array_unique($values[0]);
		$values[1] = array_unique($values[1]);
		
		return str_replace($values[0], $values[1], $code);
	}

	function _parse_var($var)
    {
		// Is it something to be parsed ?
		$var = trim($var);

		if (!$var || strpos($var, '$') !== 0)
		{
			//return $var;
			return false;
		}
		
		$var = substr(trim($var), 1);

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
				$loop_name = '_'.strtolower($vars[($loop - 1)]);
				$_output .= "[\$this->_loop['$loop_name']]['$vars[$loop]']";
			}
		}
		
		//print_r($vars);die;
        return $_output;
    }
    
	function compile_tag_if($options, $elseif = false)
	{
		//<!-- if isset({ $S_FORUM_RULES }) || { $S_FORUM_RULES } -->
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

		$values[0] = array_unique($values[0]);
		$values[1] = array_unique($values[1]);
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
		$loop_name = '_'.strtolower($options);

        $output = "if (isset($loop_code) && is_array($loop_code)) {\n";
        $output .=  "\${$loop_name}_count =  count($loop_code);";
        
//for ($i = 1; $i <= 10; $i++) {
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

