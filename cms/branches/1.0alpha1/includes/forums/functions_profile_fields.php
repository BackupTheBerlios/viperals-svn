<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright � 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//

// -------------------------------------------------------------
//
// $Id: functions_profile_fields.php,v 1.10 2004/09/19 20:40:20 acydburn Exp $
//
// FILENAME  : functions_profile_fields.php
// STARTED   : Tue Oct 21, 2003
// COPYRIGHT : � 2003 phpBB Group
// WWW       : http://www.phpbb.com/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

class custom_profile
{
	var $profile_types = array(1 => 'int', 2 => 'string', 3 => 'text', 4 => 'bool', 5 => 'dropdown', 6 => 'date');
	var $profile_cache = array();
	var $options_lang = array();

	// Build language options cache, useful for viewtopic display
	function build_cache()
	{
		global $_CLASS;

		$this->profile_cache = array();

		// Display hidden/no_view fields for admin/moderator
		$sql = 'SELECT l.*, f.*
			FROM ' . PROFILE_LANG_TABLE . ' l, ' . PROFILE_FIELDS_TABLE . ' f 
			WHERE l.lang_id = ' . $_CLASS['core_user']->get_iso_lang_id() . '
				AND f.field_active = 1 ' .
				((!$_CLASS['auth']->acl_gets('a_', 'm_')) ? '     AND f.field_hide = 0 AND f.field_no_view = 0 ' : '') . '
				AND l.field_id = f.field_id 
			GROUP BY f.field_id
			ORDER BY f.field_order';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$this->profile_cache[$row['field_ident']] = $row;
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}

	// Get language entries for options and store them here for later use
	function get_option_lang($field_id, $lang_id, $field_type, $preview)
	{
		global $_CLASS;

		if ($preview)
		{
			$lang_options = (!is_array($this->vars['lang_options'])) ? explode("\n", $this->vars['lang_options']) : $this->vars['lang_options'];
			
			foreach ($lang_options as $num => $var)
			{
				$this->options_lang[$field_id][$lang_id][($num+1)] = $var;
			}
		}
		else
		{
			$sql = 'SELECT option_id, value
				FROM ' . PROFILE_FIELDS_LANG_TABLE . "
					WHERE field_id = $field_id
					AND lang_id = $lang_id
					AND field_type = $field_type
				ORDER BY option_id";
			$result = $_CLASS['core_db']->sql_query($sql);

			while ($row = $_CLASS['core_db']->sql_fetchrow($result))
			{
				$this->options_lang[$field_id][$lang_id][$row['option_id']] = $row['value'];
			}
			$_CLASS['core_db']->sql_freeresult($result);
		}
	}

	// Functions performing operations on register/profile/profile admin
	function submit_cp_field($mode, $lang_id, &$cp_data, &$cp_error)
	{
		global $_CLASS;

		$sql = 'SELECT l.*, f.*
			FROM ' . PROFILE_LANG_TABLE . ' l, ' . PROFILE_FIELDS_TABLE . " f 
			WHERE l.lang_id = $lang_id
				AND f.field_active = 1
				" . (($mode == 'register') ? ' AND f.field_show_on_reg = 1' : '') .
				(($_CLASS['auth']->acl_gets('a_', 'm_') && $mode == 'profile') ? '' : ' AND f.field_hide = 0') . '
				AND l.field_id = f.field_id 
			GROUP BY f.field_id
			ORDER BY f.field_order';
		$result = $_CLASS['core_db']->sql_query($sql);
					
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$cp_data[$row['field_ident']] = $this->get_profile_field($row);

			// get_profile_field returns an array with values for TEXT fields.
			if(is_array($cp_data[$row['field_ident']]))
			{
				// Contains the original text without bbcode processing etc
				$check_value = $cp_data[$row['field_ident']]['submitted'];
				
				foreach($cp_data[$row['field_ident']] as $key => $value)
				{
					if($key != 'submitted')
					{
						$cp_data[$key] = $value;
					}
				}
			}
			else
			{
				$check_value = $cp_data[$row['field_ident']];
			}
			
			if (($cp_result = $this->validate_profile_field($row['field_type'], $check_value, $row)) !== false)
			{
				// If not and only showing common error messages, use this one
				$error = false;

				switch ($cp_result)
				{
					case 'FIELD_INVALID_DATE':
					case 'FIELD_REQUIRED':
						$error = sprintf($_CLASS['core_user']->lang[$cp_result], $row['lang_name']);
						break;
					case 'FIELD_TOO_SHORT':
					case 'FIELD_TOO_SMALL':
						$error = sprintf($_CLASS['core_user']->lang[$cp_result], $row['lang_name'], $row['field_minlen']);
						break;
					case 'FIELD_TOO_LONG':
					case 'FIELD_TOO_LARGE':
						$error = sprintf($_CLASS['core_user']->lang[$cp_result], $row['lang_name'], $row['field_maxlen']);
						break;
					case 'FIELD_INVALID_CHARS':
						switch ($row['field_validation'])
						{
							case '[0-9]+':
								$error = sprintf($_CLASS['core_user']->lang[$cp_result . '_NUMBERS_ONLY'], $row['lang_name']);
								break;
							case '[\w]+':
								$error = sprintf($_CLASS['core_user']->lang[$cp_result . '_ALPHA_ONLY'], $row['lang_name']);
								break;
							case '[\w_\+\. \-\[\]]+':
								$error = sprintf($_CLASS['core_user']->lang[$cp_result . '_SPACERS_ONLY'], $row['lang_name']);
								break;
						}
						break;
				}

				if ($error)
				{
					$cp_error[] = $error;
				}
			}
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}
	
	// Assign fields to template, mode can be profile (for profile change) or register (for registration)
//	function generate_profile_fields($mode, $lang_id, $cp_error)
	function generate_profile_fields($mode, $lang_id)
	{
		global $_CLASS;

		$sql = 'SELECT l.*, f.*
			FROM ' . PROFILE_LANG_TABLE . ' l, ' . PROFILE_FIELDS_TABLE . " f 
			WHERE l.lang_id = $lang_id
				AND f.field_active = 1
				" . (($mode == 'register') ? ' AND f.field_show_on_reg = 1' : '') .
				(($_CLASS['auth']->acl_gets('a_', 'm_') && $mode == 'profile') ? '' : ' AND f.field_hide = 0') . '
				AND l.field_id = f.field_id 
			GROUP BY f.field_id
			ORDER BY f.field_order';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$_CLASS['core_template']->assign_vars_array('profile_fields', array(
				'LANG_NAME' => $row['lang_name'],
				'LANG_EXPLAIN' => $row['lang_explain'],
				'FIELD' => $this->process_field_row('change', $row))
//				'ERROR' => $error)
			);
		}
		$_CLASS['core_db']->sql_freeresult($result);
	}

	// Assign fields to template, used for viewprofile, viewtopic and memberlist (if load setting is enabled)
	// This is directly connected to the user -> mode == grab is to grab the user specific fields, mode == show is for assigning the row to the template
	function generate_profile_fields_template($mode, $user_id = 0, $profile_row = false)
	{
		global $_CLASS;

		if ($mode == 'grab')
		{
			if (!is_array($user_id))
			{
				$user_id = array($user_id);
			}
			
			if (!sizeof($this->profile_cache))
			{
				$this->build_cache();
			}

			if (!implode(', ', $user_id))
			{
				return array();
			}

			$sql = 'SELECT *
				FROM ' . PROFILE_DATA_TABLE . '
				WHERE user_id IN (' . implode(', ', array_map('intval', $user_id)) . ')';
			$result = $_CLASS['core_db']->sql_query($sql);

			if (!($row = $_CLASS['core_db']->sql_fetchrow($result)))
			{
				return array();
			}
			
			$user_fields = array();
			do
			{
				foreach ($row as $ident => $value)
				{
					if (isset($this->profile_cache[$ident]))
					{
						$user_fields[$row['user_id']][$ident]['value'] = $value;
						$user_fields[$row['user_id']][$ident]['data'] = $this->profile_cache[$ident];
					}
					else if($i = strpos($ident, '_bbcode'))
					{
						// Add extra data (bbcode_uid and bbcode_bitfield) to the data for this profile field.
						// TODO: Maybe we should try to make this a bit more generic (not limited to bbcode)?
						$field = substr($ident, 0, $i);
						$subfield = substr($ident, $i+1);
						$user_fields[$row['user_id']][$field]['data'][$subfield] = $value;
					}
				}
			} 
			while ($row = $_CLASS['core_db']->sql_fetchrow($result));
			
			$_CLASS['core_db']->sql_freeresult($result);

			return $user_fields;

		}
		elseif ($mode == 'show')
		{
			// $profile_row == $user_fields[$row['user_id']];
			$tpl_fields = array();
			$tpl_fields['row'] = $tpl_fields['blockrow'] = array();

			foreach ($profile_row as $ident => $ident_ary)
			{
				$tpl_fields['row'] += array(
					'PROFILE_' . strtoupper($ident) . '_VALUE'	=> $this->get_profile_value($ident_ary),
					'PROFILE_' . strtoupper($ident) . '_TYPE'	=> $ident_ary['data']['field_type'],
					'PROFILE_' . strtoupper($ident) . '_NAME'	=> $ident_ary['data']['lang_name'],
					'PROFILE_' . strtoupper($ident) . '_EXPLAIN'=> $ident_ary['data']['lang_explain'],

					'S_PROFILE_' . strtoupper($ident)			=> true
				);
				
				$tpl_fields['blockrow'][] = array(
					'PROFILE_FIELD_VALUE'   => $this->get_profile_value($ident_ary),
					'PROFILE_FIELD_TYPE'    => $ident_ary['data']['field_type'],
					'PROFILE_FIELD_NAME'    => $ident_ary['data']['lang_name'],
					'PROFILE_FIELD_EXPLAIN' => $ident_ary['data']['lang_explain'],
					'S_PROFILE_' . strtoupper($ident)               => true
				);
			}
		
			return $tpl_fields;
		}
	}
	
	// VALIDATE Function - validate entered data
	function validate_profile_field($field_type, &$field_value, $field_data)
	{
		switch ($field_type)
		{
			case FIELD_INT:
			case FIELD_DROPDOWN:
				$field_value = (int) $field_value;
				break;

			case FIELD_BOOL:
				$field_value = (bool) $field_value;
				break;
		}

		switch ($field_type)
		{
			case FIELD_DATE:
				$field_validate = explode('-', $field_value);
				
				$day = (int) $field_validate[0];
				$month = (int) $field_validate[1];
				$year = (int) $field_validate[2];

				if ((!$day || !$month || !$year) && !$field_data['field_required'])
				{
					return false;
				}

				if ((!$day || !$month || !$year) && $field_data['field_required'])
				{
					return 'FIELD_REQUIRED';
				}

				if ($day < 0 || $day > 31 || $month < 0 || $month > 12 || ($year < 1901 && $year > 0) || $year > gmdate('Y', time()))
				{
					return 'FIELD_INVALID_DATE';
				}
				break;

			case FIELD_INT:
				if (empty($field_value) && !$field_data['field_required'])
				{
					return false;
				}

				if ($field_value < $field_data['field_minlen'])
				{
					return 'FIELD_TOO_SMALL';
				}
				else if ($field_value > $field_data['field_maxlen']) 
				{
					return 'FIELD_TOO_LARGE';
				}
				break;
		
			case FIELD_DROPDOWN:
				if ($field_value == $field_data['field_novalue'] && $field_data['field_required'])
				{
					return 'FIELD_REQUIRED';
				}
				break;
			
			case FIELD_STRING:
			case FIELD_TEXT:
				if (empty($field_value) && !$field_data['field_required'])
				{
					return false;
				}
				else if (empty($field_value) && $field_data['field_required'])
				{
					return 'FIELD_REQUIRED';
				}

				if ($field_data['field_minlen'] && strlen($field_value) < $field_data['field_minlen'])
				{
					return 'FIELD_TOO_SHORT';
				}
				else if ($field_data['field_maxlen'] && strlen($field_value) > $field_data['field_maxlen'])
				{
					return 'FIELD_TOO_LONG';
				}

				if (!empty($field_data['field_validation']) && $field_data['field_validation'] != '.*')
				{
					$field_validate = ($field_type == FIELD_STRING) ? $field_value : str_replace("\n", ' ', $field_value);
					if (!preg_match('#^' . str_replace('\\\\', '\\', $field_data['field_validation']) . '$#i', $field_validate))
					{
						return 'FIELD_INVALID_CHARS';
					}
				}
				break;
		}

		return false;
	}

	// Get Profile Value for display
	function get_profile_value($ident_ary)
	{
		$value = $ident_ary['value'];
		$field_type = $ident_ary['data']['field_type'];
		
		switch ($this->profile_types[$field_type])
		{
			case 'int':
				return (int) $value;
				break;
			case 'string':
				return str_replace("\n", '<br />', $value);
				break;
			case 'text':
				// Prepare further, censor_text, smilies, bbcode, html, whatever
				if ($ident_ary['data']['bbcode_bitfield'])
				{
					$bbcode = new bbcode($ident_ary['data']['bbcode_bitfield']);
					$bbcode->bbcode_second_pass($value, $ident_ary['data']['bbcode_uid'], $ident_ary['data']['bbcode_bitfield']);
					$value = smiley_text($value);
					$value = censor_text($value);
				}
				
				return str_replace("\n", '<br />', $value);
				break;
			case 'date':
				break;
			case 'dropdown':
				$field_id = $ident_ary['data']['field_id'];
				$lang_id = $ident_ary['data']['lang_id'];
				if (!isset($this->options_lang[$field_id][$lang_id]))
				{
					$this->get_option_lang($field_id, $lang_id, FIELD_DROPDOWN, false);
				}

				return $this->options_lang[$field_id][$lang_id][(int) $value];
				break;
			case 'bool':
				break;
			default:
				trigger_error('Unknown profile type');
				break;
		}
	}

	// Get field value for registration/profile
	function get_var($field_validation, &$profile_row, $default_value, $preview)
	{
		global $_CLASS;

		$profile_row['field_ident'] = (isset($profile_row['var_name'])) ? $profile_row['var_name'] : 'pf_' . $profile_row['field_ident'];
		
		// checkbox - only testing for isset
		if ($profile_row['field_type'] == FIELD_BOOL && $profile_row['field_length'] == 2)
		{
			$value = (isset($_REQUEST[$profile_row['field_ident']])) ? true : ((!isset($_CLASS['core_user']->profile_fields[$profile_row['field_ident']]) || $preview) ? $default_value : $_CLASS['core_user']->profile_fields[$profile_row['field_ident']]);
		}
		else
		{
			$value = (isset($_REQUEST[$profile_row['field_ident']])) ? request_var($profile_row['field_ident'], $default_value) : ((!isset($_CLASS['core_user']->profile_fields[str_replace('pf_', '', $profile_row['field_ident'])]) || $preview) ? $default_value : $_CLASS['core_user']->profile_fields[str_replace('pf_', '', $profile_row['field_ident'])]);
		}

		switch ($field_validation)
		{
			case 'int':
				return (int) $value;
				break;
		}

		return $value;
	}
	
	// GENERATE_* Functions - return templated, storable profile fields
	function generate_int($profile_row, $preview = false)
	{
		$value = $this->get_var('int', $profile_row, $profile_row['field_default_value'], $preview);
		$this->set_tpl_vars($profile_row, $value);

		return $this->get_cp_html();
	}

	function generate_date($profile_row, $preview = false)
	{
		global $_CLASS;

		$profile_row['field_ident'] = (isset($profile_row['var_name'])) ? $profile_row['var_name'] : 'pf_' . $profile_row['field_ident'];
		$now = getdate();

		if (!isset($_REQUEST[$profile_row['field_ident'] . '_day']))
		{
			if ($profile_row['field_default_value'] == 'now')
			{
				$profile_row['field_default_value'] = sprintf('%2d-%2d-%4d', $now['mday'], $now['mon'], $now['year']);
			}
			list($day, $month, $year) = explode('-', ((!isset($_CLASS['core_user']->profile_fields[$profile_row['field_ident']]) || $preview) ? $profile_row['field_default_value'] : $_CLASS['core_user']->profile_fields[$profile_row['field_ident']]));
		}
		else
		{
			if ($preview && $profile_row['field_default_value'] == 'now')
			{
				$profile_row['field_default_value'] = sprintf('%2d-%2d-%4d', $now['mday'], $now['mon'], $now['year']);
				list($day, $month, $year) = explode('-', ((!isset($_CLASS['core_user']->profile_fields[$profile_row['field_ident']]) || $preview) ? $profile_row['field_default_value'] : $_CLASS['core_user']->profile_fields[$profile_row['field_ident']]));
			}
			else
			{
				$day = request_var($profile_row['field_ident'] . '_day', 0);
				$month = request_var($profile_row['field_ident'] . '_month', 0);
				$year = request_var($profile_row['field_ident'] . '_year', 0);
			}
		}

		$profile_row['s_day_options'] = '<option value="0"' . ((!$day) ? ' selected="selected"' : '') . '>--</option>';
		for ($i = 1; $i < 32; $i++)
		{
			$profile_row['s_day_options'] .= '<option value="' . $i . '"' . (($i == $day) ? ' selected="selected"' : '') . ">$i</option>";
		}

		$profile_row['s_month_options'] = '<option value="0"' . ((!$month) ? ' selected="selected"' : '') . '>--</option>';
		for ($i = 1; $i < 13; $i++)
		{
			$profile_row['s_month_options'] .= '<option value="' . $i . '"' . (($i == $month) ? ' selected="selected"' : '') . ">$i</option>";
		}

		$profile_row['s_year_options'] = '<option value="0"' . ((!$year) ? ' selected="selected"' : '') . '>--</option>';
		for ($i = $now['year'] - 100; $i <= $now['year']; $i++)
		{
			$profile_row['s_year_options'] .= '<option value="' . $i . '"' . (($i == $year) ? ' selected="selected"' : '') . ">$i</option>";
		}
		unset($now);
		
		$this->set_tpl_vars($profile_row, 0);
		return $this->get_cp_html();
	}

	function generate_bool($profile_row, $preview = false)
	{
		global $_CLASS;

		$value = $this->get_var('int', $profile_row, $profile_row['field_default_value'], $preview);

		$this->set_tpl_vars($profile_row, $value);

		if ($profile_row['field_length'] == 1)
		{
			if (!isset($this->options_lang[$profile_row['field_id']][$profile_row['lang_id']]) || !sizeof($this->options_lang[$profile_row['field_id']][$profile_row['lang_id']]))
			{
				$this->get_option_lang($profile_row['field_id'], $profile_row['lang_id'], FIELD_BOOL, $preview);
			}

			foreach ($this->options_lang[$profile_row['field_id']][$profile_row['lang_id']] as $option_id => $option_value)
			{
				$_CLASS['core_template']->assign_vars_array('bool.options', array(
					'OPTION_ID' => $option_id,
					'CHECKED' => ($value == $option_id) ? ' checked="checked"' : '',
					'VALUE' => $option_value)
				);
			}
		}

		return $this->get_cp_html();
	}

	// Get the data associated with this field for this user
	function generate_string($profile_row, $preview = false)
	{
		$value = $this->get_var('', $profile_row, $profile_row['lang_default_value'], $preview);
		$this->set_tpl_vars($profile_row, $value);

		return $this->get_cp_html();
	}

	function generate_text($profile_row, $preview = false)
	{
		global $_CLASS, $site_file_root;
		
		$value = $this->get_var('', $profile_row, $profile_row['lang_default_value'], $preview);
		
		if($preview == false)
		{
			include_once($site_file_root.'includes/forums/message_parser.php');
			include_once($site_file_root.'includes/forums/functions_posting.php');
			
			$message_parser = new parse_message();
			$message_parser->message = $value;
			$message_parser->decode_message($_CLASS['core_user']->profile_fields[str_replace('pf_', '', $profile_row['field_ident']) . '_bbcode_uid']);
			$value = $message_parser->message;
		}
		
		$field_length = explode('|', $profile_row['field_length']);
		$profile_row['field_rows'] = $field_length[0];
		$profile_row['field_cols'] = $field_length[1];

		$this->set_tpl_vars($profile_row, $value);

		return $this->get_cp_html();
	}

	function generate_dropdown($profile_row, $preview = false)
	{
		global $_CLASS;

		$value = $this->get_var('int', $profile_row, $profile_row['field_default_value'], $preview);

		if (!isset($this->options_lang[$profile_row['field_id']]) || !sizeof($this->options_lang[$profile_row['field_id']][$profile_row['lang_id']]))
		{
			$this->get_option_lang($profile_row['field_id'], $profile_row['lang_id'], FIELD_DROPDOWN, $preview);
		}

		$this->set_tpl_vars($profile_row, $value);

		foreach ($this->options_lang[$profile_row['field_id']][$profile_row['lang_id']] as $option_id => $option_value)
		{
			$_CLASS['core_template']->assign_vars_array('dropdown.options', array(
				'OPTION_ID' => $option_id,
				'SELECTED' => ($value == $option_id) ? ' selected="selected"' : '',
				'VALUE' => $option_value)
			);
		}

		return $this->get_cp_html();
	}


	// Return Templated value (change == user is able to set/enter profile values; show == just show the value)
	function process_field_row($mode, $profile_row)
	{
		$preview = false;

		switch ($mode)
		{
			case 'preview':
				$preview = true;
			case 'change':
				$type_func = 'generate_' . $this->profile_types[$profile_row['field_type']];
				break;
			default:
				return;
		}

		return $this->$type_func($profile_row, $preview);
	}

	// Build Array for user insertion into custom profile fields table
	function build_insert_sql_array($cp_data)
	{
		global $_CLASS;

		$sql = 'SELECT f.field_type, f.field_ident, f.field_default_value, l.lang_default_value
			FROM ' . PROFILE_LANG_TABLE . ' l, ' . PROFILE_FIELDS_TABLE . ' f 
			WHERE l.lang_id = ' . $_CLASS['core_user']->get_iso_lang_id() . ' 
				AND f.field_active = 1
				AND f.field_show_on_reg = 0
				' . (($_CLASS['auth']->acl_gets('a_', 'm_')) ? '' : ' AND f.field_hide = 0') . '
				AND l.field_id = f.field_id 
			GROUP BY f.field_id';
		$result = $_CLASS['core_db']->sql_query($sql);

		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			if ($row['field_default_value'] == 'now' && $row['field_type'] == FIELD_DATE)
			{
				$now = getdate();
				$row['field_default_value'] = sprintf('%2d-%2d-%4d', $now['mday'], $now['mon'], $now['year']);
			}
			$cp_data[$row['field_ident']] = (in_array($row['field_type'], array(FIELD_TEXT, FIELD_STRING))) ? $row['lang_default_value'] : $row['field_default_value'];
		}
		$_CLASS['core_db']->sql_freeresult($result);
		
		return $cp_data;
	}

	function get_profile_field($profile_row)
	{
		global $site_file_root;
		
		switch ($profile_row['field_type'])
		{
			case FIELD_DATE:

				if (!isset($_REQUEST[$var_name . '_day']))
				{
					if ($profile_row['field_default_value'] == 'now')
					{
						$now = getdate();
						$profile_row['field_default_value'] = sprintf('%2d-%2d-%4d', $now['mday'], $now['mon'], $now['year']);
					}
					list($day, $month, $year) = explode('-', $profile_row['field_default_value']);
				}
				else
				{
					$day = request_var($var_name . '_day', 0);
					$month = request_var($var_name . '_month', 0);
					$year = request_var($var_name . '_year', 0);
				}
				
				$var = sprintf('%2d-%2d-%4d', $day, $month, $year);
				break;

			case FIELD_TEXT:
				include_once($site_file_root.'includes/forums/message_parser.php');
				$message_parser = new parse_message(request_var($var_name, ''));
				
				// Get the allowed settings from the global settings. Magic URLs are always set to true.
				// TODO: It might be nice to make this a per field setting.
				$message_parser->parse($config['allow_html'], $config['allow_bbcode'], true, $config['allow_smilies']);
				
				$var = array(
					$profile_row['field_ident'] => $message_parser->message,
					$profile_row['field_ident'] . '_bbcode_uid' => $message_parser->bbcode_uid,
					$profile_row['field_ident'] . '_bbcode_bitfield' => $message_parser->bbcode_bitfield,
					 'submitted' => request_var($var_name, '')
				);
				break;

			default:
				$var = request_var($var_name, $profile_row['field_default_value']);
				break;
		}

		return $var;
	}

	function set_tpl_vars($profile_row, $field_value)
	{
		global $_CLASS;
		
		foreach ($this->profile_types as $field_case => $field_type)
		{
			unset($_CLASS['core_template']->_tpl_vars[$field_type]);
		}

		foreach ($profile_row as $key => $value)
		{
			unset($profile_row[$key]);
			$profile_row[strtoupper($key)] = $value;
		}

		$profile_row['FIELD_VALUE'] = $field_value;

		$_CLASS['core_template']->assign_vars_array($this->profile_types[$profile_row['FIELD_TYPE']], $profile_row);
	}

	function get_cp_html()
	{
		global $_CLASS;

		ob_start();

		$_CLASS['core_template']->display('forums/custom_profile_fields.html');

		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}
}

class custom_profile_admin extends custom_profile
{
	var $vars = array();
	

	function validate_options()
	{
		global $_CLASS;

		$validate_ary = array('CHARS_ANY' => '.*', 'NUMBERS_ONLY' => '[0-9]+', 'ALPHA_ONLY' => '[\w]+', 'ALPHA_SPACERS' => '[\w_\+\. \-\[\]]+');

		$validate_options = '';
		foreach ($validate_ary as $lang => $value)
		{
			$selected = ($this->vars['field_validation'] == $value) ? ' selected="selected"' : '';
			$validate_options .= '<option value="' . $value . '"' . $selected . '>' . $_CLASS['core_user']->lang[$lang] . '</option>';
		}

		return $validate_options;
	}
	
	// GET_* get admin options for second step
	function get_string_options()
	{
		global $_CLASS;

		$options = array(
			0 => array('TITLE' => $_CLASS['core_user']->lang['FIELD_LENGTH'], 'FIELD' => '<input class="post" type="text" name="field_length" size="5" value="' . $this->vars['field_length'] . '" />'),
			1 => array('TITLE' => $_CLASS['core_user']->lang['MIN_FIELD_CHARS'], 'FIELD' => '<input class="post" type="text" name="field_minlen" size="5" value="' . $this->vars['field_minlen'] . '" />'),
			2 => array('TITLE' => $_CLASS['core_user']->lang['MAX_FIELD_CHARS'], 'FIELD' => '<input class="post" type="text" name="field_maxlen" size="5" value="' . $this->vars['field_maxlen'] . '" />'),
			3 => array('TITLE' => $_CLASS['core_user']->lang['FIELD_VALIDATION'], 'FIELD' => '<select name="field_validation">' . $this->validate_options() . '</select>')
		);

		return $options;
	}

	function get_text_options()
	{
		global $_CLASS;

		$options = array(
			0 => array('TITLE' => $_CLASS['core_user']->lang['FIELD_LENGTH'], 'FIELD' => '<table border=0><tr><td><input name="rows" size="5" value="' . $this->vars['rows'] . '" class="post" /></td><td>[ ' . $_CLASS['core_user']->lang['ROWS'] . ' ]</td></tr><tr><td><input name="columns" size="5" value="' . $this->vars['columns'] . '" class="post" /></td><td>[ ' . $_CLASS['core_user']->lang['COLUMNS'] . ' ] <input type="hidden" name="field_length" value="' . $this->vars['field_length'] . '" /></td></tr></table>'),
			1 => array('TITLE' => $_CLASS['core_user']->lang['MIN_FIELD_CHARS'], 'FIELD' => '<input class="post" type="text" name="field_minlen" size="10" value="' . $this->vars['field_minlen'] . '" />'),
			2 => array('TITLE' => $_CLASS['core_user']->lang['MAX_FIELD_CHARS'], 'FIELD' => '<input class="post" type="text" name="field_maxlen" size="10" value="' . $this->vars['field_maxlen'] . '" />'),
			3 => array('TITLE' => $_CLASS['core_user']->lang['FIELD_VALIDATION'], 'FIELD' => '<select name="field_validation">' . $this->validate_options() . '</select>')
		);

		return $options;
	}

	function get_int_options()
	{
		global $_CLASS;

		$options = array(
			0 => array('TITLE' => $_CLASS['core_user']->lang['FIELD_LENGTH'], 'FIELD' => '<input class="post" type="text" name="field_length" size="5" value="' . $this->vars['field_length'] . '" />'),
			1 => array('TITLE' => $_CLASS['core_user']->lang['MIN_FIELD_NUMBER'], 'FIELD' => '<input class="post" type="text" name="field_minlen" size="5" value="' . $this->vars['field_minlen'] . '" />'),
			2 => array('TITLE' => $_CLASS['core_user']->lang['MAX_FIELD_NUMBER'], 'FIELD' => '<input class="post" type="text" name="field_maxlen" size="5" value="' . $this->vars['field_maxlen'] . '" />'),
			3 => array('TITLE' => $_CLASS['core_user']->lang['DEFAULT_VALUE'], 'FIELD' => '<input class="post" type="post" name="field_default_value" value="' . $this->vars['field_default_value'] . '" />')
		);

		return $options;
	}

	function get_bool_options()
	{
		global $_CLASS, $config, $lang_defs;

		$default_lang_id = $lang_defs['iso'][$config['default_lang']];

		$profile_row = array(
			'var_name'				=> 'field_default_value',
			'field_id'				=> 1,
			'lang_name'				=> $this->vars['lang_name'],
			'lang_explain'			=> $this->vars['lang_explain'],
			'lang_id'				=> $default_lang_id,
			'field_default_value'	=> $this->vars['field_default_value'],
			'field_ident'			=> 'field_default_value',
			'field_type'			=> FIELD_BOOL,
			'field_length'			=> $this->vars['field_length'],
			'lang_options'			=> $this->vars['lang_options']
		);

		$options = array(
			0 => array('TITLE' => $_CLASS['core_user']->lang['FIELD_TYPE'], 'EXPLAIN' => $_CLASS['core_user']->lang['BOOL_TYPE_EXPLAIN'], 'FIELD' => '<input type="radio" name="field_length" value="1"' . (($this->vars['field_length'] == 1) ? ' checked="checked"' : '') . ' />' . $_CLASS['core_user']->lang['RADIO_BUTTONS'] . '&nbsp; &nbsp;<input type="radio" name="field_length" value="2"' . (($this->vars['field_length'] == 2) ? ' checked="checked"' : '') . ' />' . $_CLASS['core_user']->lang['CHECKBOX'] . '&nbsp; &nbsp;'),
			1 => array('TITLE' => $_CLASS['core_user']->lang['DEFAULT_VALUE'], 'FIELD' => $this->generate_bool($profile_row, true))
		);

		return $options;
	}

	function get_dropdown_options()
	{
		global $_CLASS, $config, $lang_defs;

		$default_lang_id = $lang_defs['iso'][$config['default_lang']];

		$profile_row[0] = array(
			'var_name'				=> 'field_default_value',
			'field_id'				=> 1,
			'lang_name'				=> $this->vars['lang_name'],
			'lang_explain'			=> $this->vars['lang_explain'],
			'lang_id'				=> $default_lang_id,
			'field_default_value'	=> $this->vars['field_default_value'],
			'field_ident'			=> 'field_default_value',
			'field_type'			=> FIELD_DROPDOWN,
			'lang_options'			=> $this->vars['lang_options']
		);

		$profile_row[1] = $profile_row[0];
		$profile_row[1]['var_name'] = 'field_novalue';
		$profile_row[1]['field_ident'] = 'field_novalue';
		$profile_row[1]['field_default_value']	= $this->vars['field_novalue'];


		$options = array(
			0 => array('TITLE' => $_CLASS['core_user']->lang['DEFAULT_VALUE'], 'FIELD' => $this->generate_dropdown($profile_row[0], true)),
			1 => array('TITLE' => $_CLASS['core_user']->lang['NO_VALUE_OPTION'], 'EXPLAIN' => $_CLASS['core_user']->lang['NO_VALUE_OPTION_EXPLAIN'], 'FIELD' => $this->generate_dropdown($profile_row[1], true))
		);

		return $options;
	}

	function get_date_options()
	{
		global $_CLASS, $config, $lang_defs;

		$default_lang_id = $lang_defs['iso'][$config['default_lang']];

		$profile_row = array(
			'var_name'				=> 'field_default_value',
			'lang_name'				=> $this->vars['lang_name'],
			'lang_explain'			=> $this->vars['lang_explain'],
			'lang_id'				=> $default_lang_id,
			'field_default_value'	=> $this->vars['field_default_value'],
			'field_ident'			=> 'field_default_value',
			'field_type'			=> FIELD_DATE,
			'field_length'			=> $this->vars['field_length']
		);

		$options = array(
			0 => array('TITLE' => $_CLASS['core_user']->lang['DEFAULT_VALUE'], 'FIELD' => $this->generate_date($profile_row, true) . '<br /><input type="checkbox" name="always_now"' . ((isset($_REQUEST['always_now']) || $this->vars['field_default_value'] == 'now') ? ' checked="checked"' : '') . ' />&nbsp; ' . $_CLASS['core_user']->lang['ALWAYS_TODAY'])
		);

		return $options;
	}
}

?>