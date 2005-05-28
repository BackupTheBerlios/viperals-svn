<?php


class ucp_calender extends module  
{
	function ucp_calender($id, $mode)
	{
		global $_CLASS, $site_file_root;

		loadclass($site_file_root.'includes/display/calender.php', 'calender');
		$link = 'Control_Panel&amp;i='.$id;
		
		switch ($mode)
		{
			case 'day_view':
				$_CLASS['calender']->day_view($link);
				$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_day.html');
				break;
			
			case 'month_view':
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
}

?>