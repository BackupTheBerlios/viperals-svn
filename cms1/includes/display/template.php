<?php
$site_file_root = '';
$test = new core_template();
$test->compile('viewforum_body.html');

class core_template
{
	var $template_dir;
	var $compile_check = true;

    function assign_vars($var, $value = false)
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
    
	function assign_vars_array($var, $value)
	{
			$this->_vars[$var][] = $value;
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

		if ($file = $this->is_compiled($name) || $this->_compile($name))
		{
			include($file);
		}
    }

	function generate_dirs($name)
	{
		global $_CLASS, $site_file_root;

		$set = false;

		if (!empty($_CLASS['core_display']) && file_exists($site_file_root."themes/{$_CLASS['core_display']->theme}/template/$name.php"))
		{
			$this->template_dir = "themes/{$_CLASS['core_display']->theme}/template/";
			$this->cache_dir = 'cache/'.$_CLASS['core_display']->theme.'/';

			$set = true;
		}
		elseif (file_exists($site_file_root."includes/templates/$name.php"))
		{
			$this->template_dir = 'includes/templates/';
			$this->cache_dir = 'cache/';

			$set = true;
		}
		
		if ($set)
		{
			$this->template_dir = $site_file_root.$this->template_dir;
			$this->cache_dir = $site_file_root.$this->cache_dir;
		}

		return $set;
	}

	function generate_name($name)
	{
		return $name;
	}
	
    function is_compiled($name)
    {
		$cache_location = $this->cache_dir.generate_name($name);

		if (file_exists($cache_location))
        {
			if (!$this->compile_check)
            {
                return true;
            }
            elseif (filemtime($this->template_dir.$name) > filemtime($cache_location))
            {
				return true;
			}
		}

		return false;
    }

    function compile($name)
    {
		$code = file_get_contents($this->template_dir.$name);

		if ($code === false)
		{
			return false;
		}
		preg_match_all('/\<\!-- (.*?) (.*?) --\>/', $code, $blocks);
		preg_match_all('/\{ (.*?) \}/', $code, $values);

		$values[0] = array_unique($values[0]);
		$values[1] = array_unique($values[1]);
		
		$size = sizeof($values[1]);

		for ($j = 0; $j < $size; $j++)
		{
			$parse = $this->_parse_var($values[1][$j]);

			if (!$parse)
			{
				unset($values[1][$j], $values[0][$j]);
				continue;
			}
			
			$values[1][$j] = '<?php echo '.$parse.'; ?>';
		}

		$code = str_replace($values[0], $values[1], $code);


	//	echo htmlentities($text_blocks);
    }

	// Handles template language
	function _get_lang($lang)
	{
		global $_CLASS;

		return $_CLASS['core_user']->get_lang(substr($lang, 2));
	}
	
	function _parse_var($var)
    {
		// If it something to be parsed
		if (strpos($var, '$') !== 0)
		{
			return false;
		}
		
		$var = substr(trim($var), 1);

		//
		if (strpos($var, 'L_') !== false)
		{
			$_output = "(isset(\$this->_vars['$var'])) ? \$this->_vars['$var'] : \$this->_get_lang('$var')";
		}
		else
		{
			$_output = "\$this->_vars['$var']";
		}

        return $_output;
    }
}

