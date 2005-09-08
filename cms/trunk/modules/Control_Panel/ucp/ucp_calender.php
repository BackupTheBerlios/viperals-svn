<?php

class ucp_calender extends module  
{
	function ucp_calender($id, $mode)
	{
		global $_CLASS, $site_file_root;

		$link = 'Control_Panel&amp;i='.$id;
		
		$day = get_variable('day', 'REQUEST', false, 'integer');
		$month = get_variable('month', 'REQUEST', false, 'integer');
		$year = get_variable('year', 'REQUEST', false, 'integer');
		
		load_class($site_file_root.'includes/display/calender.php', 'calender');
		$_CLASS['calender']->set_date($day, $month, $year);
		
		if (isset($_GET['mode']) && $_GET['mode'] == 'details')
		{
			$mode = 'details';
		}
		
		switch ($mode)
		{
			case 'day_view':
				$_CLASS['calender']->month_view($link);
				$_CLASS['calender']->get_events_day($link);

				$day_flanks = $_CLASS['calender']->flank_days();
				$month_flanks = $_CLASS['calender']->flank_months();

				$previous_day = generate_link($link.'&amp;mode=day_view&amp;year='.$day_flanks['previous_day']['year'].'&amp;month='.$day_flanks['previous_day']['month'].'&amp;day='.$day_flanks['previous_day']['day']);
				$next_day = generate_link($link.'&amp;mode=day_view&amp;year='.$day_flanks['next_day']['year'].'&amp;month='.$day_flanks['next_day']['month'].'&amp;day='.$day_flanks['next_day']['day']);
				$previous_month = generate_link($link.'&amp;mode=day_view&amp;year='.$month_flanks['previous_month']['year'].'&amp;month='.$month_flanks['previous_month']['month']);
				$next_month = generate_link($link.'&amp;mode=day_view&amp;year='.$month_flanks['next_month']['year'].'&amp;month='.$month_flanks['next_month']['month']);
				
				$_CLASS['core_template']->assign(array(
					'L_SUNDAY'				=> $_CLASS['core_user']->lang['datetime']['Sun'],
					'L_MONDAY'				=> $_CLASS['core_user']->lang['datetime']['Mon'],
					'L_TUESDAY'				=> $_CLASS['core_user']->lang['datetime']['Tue'],
					'L_WEDNESDAY'			=> $_CLASS['core_user']->lang['datetime']['Wed'],
					'L_THURSDAY'			=> $_CLASS['core_user']->lang['datetime']['Thu'],
					'L_FRIDAY'				=> $_CLASS['core_user']->lang['datetime']['Fri'],
					'L_SATURDAY'			=> $_CLASS['core_user']->lang['datetime']['Sat'],
					'L_TODAY'				=> $_CLASS['core_user']->lang['datetime']['TODAY'],
					'THIS_DAY'				=> date('F j, Y', mktime(0, 0, 0, $_CLASS['calender']->month, $_CLASS['calender']->day, $_CLASS['calender']->year)),
					'PREVIOUS_DAY_LINK'		=> $previous_day,
					'NEXT_DAY_LINK'			=> $next_day,
				));

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_day.html');
			break;
				
			case 'add_event':
				if (isset($_POST['submit']))
				{
					$this->add_event();
					break;
				}

				$_CLASS['core_template']->assign(array(
					'S_UCP_ACTION'	=> generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"),
				));

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_add.html');
			break;
				
			case 'details':
			
				$id = get_variable('id', 'GET', false, 'integer');
				$data = false;
				$data = $_CLASS['calender']->get_events_details($id);
				
				$_CLASS['core_template']->assign(array(
					'CAL_TITLE'			=> $data['title'],
					'CAL_DESCRIPTION'	=> $data['description'],
					'CAL_START_TIME'	=> $_CLASS['core_user']->format_date($data['start_time']),
					'CAL_END_TIME'		=> $_CLASS['core_user']->format_date($data['end_time']),
				));
			
				$_CLASS['core_template']->display('modules/Control_Panel/ucp_calender_details.html');
				$_CLASS['core_display']->display_footer();
			break;
			
			case 'month_view':
			default:
				
				$_CLASS['calender']->get_events_month($link);
				$_CLASS['calender']->month_view($link);

				$month_flanks = $_CLASS['calender']->flank_months();

				$_CLASS['core_template']->assign(array(
					'L_SUNDAY'				=> $_CLASS['core_user']->lang['datetime']['Sunday'],
					'L_MONDAY'				=> $_CLASS['core_user']->lang['datetime']['Monday'],
					'L_TUESDAY'				=> $_CLASS['core_user']->lang['datetime']['Tuesday'],
					'L_WEDNESDAY'			=> $_CLASS['core_user']->lang['datetime']['Wednesday'],
					'L_THURSDAY'			=> $_CLASS['core_user']->lang['datetime']['Thursday'],
					'L_FRIDAY'				=> $_CLASS['core_user']->lang['datetime']['Friday'],
					'L_SATURDAY'			=> $_CLASS['core_user']->lang['datetime']['Saturday'],
					'L_TODAY'				=> $_CLASS['core_user']->lang['datetime']['TODAY'],
					
					'THIS_MONTH_NAME'		=> $_CLASS['core_user']->lang['datetime'][date('F', mktime(0, 0, 0, $_CLASS['calender']->month, 1, $_CLASS['calender']->year))],
					'NEXT_MONTH_NAME'		=> $_CLASS['core_user']->lang['datetime'][date('F', mktime(0, 0, 0, $month_flanks['next_month']['month'], 1, $_CLASS['calender']->year))],
					'PREVIOUS_MONTH_NAME'	=> $_CLASS['core_user']->lang['datetime'][date('F', mktime(0, 0, 0, $month_flanks['previous_month']['month'], 1, $_CLASS['calender']->year))],
					'NEXT_MONTH_YEAR'		=> $month_flanks['next_month']['year'],
					'PREVIOUS_MONTH_YEAR'	=> $month_flanks['previous_month']['year'],
					'CURRENT_YEAR'			=> $_CLASS['calender']->year,
					'PREVIOUS_MONTH'		=> generate_link($link.'&amp;mode=month_view&amp;year='.$month_flanks['previous_month']['year'].'&amp;month='.$month_flanks['previous_month']['month']),
					'NEXT_MONTH'			=> generate_link($link.'&amp;mode=month_view&amp;year='.$month_flanks['next_month']['year'].'&amp;month='.$month_flanks['next_month']['month']),
				));

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_main.html');
			break;	
		}
	}

	function add_event()
	{
		global $_CLASS;

		$data_array = array(
			'title'			=> get_variable('title', 'POST', false),
			'description'	=> get_variable('description', 'POST', false),
			'note'			=> get_variable('note', 'POST', false),
			'start_time'	=> get_variable('start', 'POST', false),
			'end_time'		=> get_variable('end', 'POST', false),
			'recur'			=> false,
		);

		$error = '';

		if (($start_time = strtotime($data_array['start_time'])) === -1)
		{
			$error .= $_CLASS['core_user']->get_lang('ERROR_START_TIME').'<br />';
		}

		if (($end_time = strtotime($data_array['end_time'])) === -1)
		{
			$error .= $_CLASS['core_user']->get_lang('ERROR_END_TIME').'<br />';
		}
		
		if (!$error && $start_time > $end_time)
		{
			$error .= $_CLASS['core_user']->get_lang('ERROR_').'<br />';
		}

		if (!$error)
		{
			//$duration = $start_time - $end_time;
			//$start_time = $date = implode(''. explode(':', date('H:i', $start_time)));;

			$data_array['start_time'] = $data_array['start_date'] = $start_time;
			$data_array['end_time'] = $data_array['end_date'] = $end_time;
			
			$_CLASS['core_db']->sql_query('INSERT INTO cms_calender ' . $_CLASS['core_db']->sql_build_array('INSERT', $data_array));
		}
	}
}

?>