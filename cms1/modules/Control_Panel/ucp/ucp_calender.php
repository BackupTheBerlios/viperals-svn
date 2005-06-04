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
		
		switch ($mode)
		{
			case 'day_view':
				$_CLASS['calender']->month_view($link);
				$_CLASS['calender']->day_view($link);
				$_CLASS['calender']->get_events_day();

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_day.html');
				break;
			
			case 'month_view':
				$_CLASS['calender']->get_events_month();
				$_CLASS['calender']->month_view($link);

				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_main.html');
				break;
			
			case 'add_event':
				$_CLASS['calender']->add_event($link);
				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_add.html');
				break;
				
			default:
				$_CLASS['calender']->month_view($link);
				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_main.html');
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