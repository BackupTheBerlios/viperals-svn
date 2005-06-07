<?php
/*
To do for version 1:
	SEction to add events
	add limit to to data in events month { also morelinks } and maybe day
	EVent details view
	Implement recurring events table
	Complete manage calender
	Time Zones -------
	Fix-up view, add new stuff
	Move langs out of calender class
	Caching //maybe after my caching system is done
*/
class ucp_calender extends module  
{
	function ucp_calender($id, $mode)
	{
		global $_CLASS, $site_file_root;

		loadclass($site_file_root.'includes/display/calender.php', 'calender');
		$link = 'Control_Panel&amp;i='.$id;
		
		$day = get_variable('day', 'REQUEST', false, 'integer');
		$month = get_variable('month', 'REQUEST', false, 'integer');
		$year = get_variable('year', 'REQUEST', date('Y'), 'integer');
		
		$_CLASS['calender']->set_date($day, $month, $year);
		
		if ($_GET['mode'] == 'details')
		{
			$mode = 'details';
		}
		
		switch ($mode)
		{
			case 'day_view':
				$_CLASS['calender']->month_view($link);
				$_CLASS['calender']->day_view($link);
				$_CLASS['calender']->get_events_day($link);

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_day.html');
				break;
				
			case 'add_event':
				$_CLASS['calender']->add_event($link);
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

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_main.html');
				break;	
		}
	}
	
	function add_event($link)
	{
		global $_CLASS;
	}
	
	function get_data()
	{
		$data_array = array(
		'title'			=> get_variable('title', 'POST', false),
		'description'	=> get_variable('description', 'POST', false),
		'note'			=> get_variable('note', 'POST', false),
		'event_start'	=> get_variable('start', 'POST', false),
		'event_time'	=> get_variable('end', 'POST', false),
		'recurring'		=> '',
		'start_time'	=>'',
		'end_time'		=>'',
			);
		
	}
}

?>