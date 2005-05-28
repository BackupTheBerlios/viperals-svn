<?php
//**************************************************************//
//  Vipeal CMS:													//
//**************************************************************//
//																//
//  Copyright © 2004 by Viperal									//
//  http://www.viperal.com										//
//																//
//  Viperal CMS is released under the terms and conditions		//
//  of the GNU General Public License version 2					//
//																//
//**************************************************************//
class calender
{
	function day_view($link)
	{
		global $_CLASS;
		
		$day = get_variable('day', 'GET', false, 'integer');
		$month = get_variable('month', 'GET', false, 'integer');
		$year = get_variable('year', 'GET', date('Y'), 'integer');
		
		if (!$month || $month > 12 || $month < 1)
		{
			$month = date('n');
		}
		
		if ($year != date('Y'))
		{
			$newyear = true;
		}
		
		$lastday = date("t",mktime(0, 0, 0, $month, 1, $year));
		$first_day = date("w",mktime(0, 0, 0, $month, 1, $year)) + 1;
		
		if ($day < 1 || $day > $lastday)
		{
			$day = date('j');
		}
		
		if ($day == $lastday)
		{
			//get next month and link it
			$tommorrow = generate_link("$link&amp;mode=day_view&amp;month=&amp;day=1");
		} else {
			$tommorrow = generate_link($link.'&amp;mode=day_view&amp;day='.($day + 1));
		}
		
		
		$data = array('day' => $day, 'month' => $month, 'year' => $year, 'last' => $lastday, 'first' => $first_day);
		
		$this->month_view($link, $data);
		
		$_CLASS['core_template']->assign(array(
			'L_SUNDAY'				=> $_CLASS['core_user']->lang['datetime']['Sun'],
			'L_MONDAY'				=> $_CLASS['core_user']->lang['datetime']['Mon'],
			'L_TUESDAY'				=> $_CLASS['core_user']->lang['datetime']['Tue'],
			'L_WEDNESDAY'			=> $_CLASS['core_user']->lang['datetime']['Wed'],
			'L_THURSDAY'			=> $_CLASS['core_user']->lang['datetime']['Thu'],
			'L_FRIDAY'				=> $_CLASS['core_user']->lang['datetime']['Fri'],
			'L_SATURDAY'			=> $_CLASS['core_user']->lang['datetime']['Sat'],
			'L_TODAY'				=> $_CLASS['core_user']->lang['datetime']['TODAY'],
			'L_EVENTS'				=> 'Events',
			'L_NO_EVENTS'			=> 'No Events',
			'L_SCHEDULE'			=> 'Schedule',
			'L_NO_SCHEDULE'			=> 'Nothing Schedule for today',
			'L_TOMORROW'			=> $_CLASS['core_user']->lang['datetime']['TOMORROW'],
			'L_YESTERDAY'			=> $_CLASS['core_user']->lang['datetime']['YESTERDAY'],
			'THIS_DAY'				=> date('F j, Y', mktime(0, 0, 0, $month, $day, $year)),
			'YESTERDAY_LINK'		=> generate_link("$link&amp;mode=day_view&amp;day=".$nextmonth['link']),
			'TOMORROW_LINK'			=> $tommorrow,
			'PREVIOUS_MONTH'		=> generate_link("$link&amp;mode=month_view&amp;month=".$previousmonth['link']),
			)
		);
		

	}

	function month_view($link, $data = false)
	{
		global $_CLASS;
		
		$newyear = false;
		
		if (!$data)
		{
			//get a timestap for the first day then use the built-in last day check in php.
			$month = get_variable('month', 'GET', false, 'integer');
			$year = get_variable('year', 'GET', date('Y'), 'integer');
					
			if (!$month || $month > 12 || $month < 1)
			{
				$month = date('n');
			}
			
			$day = date('j');
			$lastday = date("t",mktime(0, 0, 0, $month, 1, $year));
			$first_day = date("w",mktime(0, 0, 0, $month, 1, $year)) + 1;
			
		} else {
			$day = $data['day'];
			$month = $data['month'];
			$year = $data['year'];
			$lastday = $data['last'];
			$first_day = $data['first'];
		}
	
		$count = ceil(($lastday + $first_day) / 7) * 7;
		//$count = 42;
		$num = false;
		
		for($i=1; $i<=$count; $i++)
		{
			if ($i == $first_day)
			{
				$num = 1;
			}
			
			$_CLASS['core_template']->assign_vars_array('days', array(
				'NUMBER'				=> ($num) ? $num : false,
				'LINK'					=> ($num && $num != $day) ? generate_link("$link&amp;mode=day_view&amp;day=".$num) : false,
				//'L_OPTIONS'				=> 'Options',
				)
			);
			if ($num)
			{
				if ($num == $lastday)
				{
					$num = false;
				} else {
					$num++;
				}
			}
		}
		
		if ($data)
		{
			return;
		}
		
		if ($month != 12)
		{
			$nextmonth['month'] =  $month + 1;
			$nextmonth['link'] = $nextmonth['month'].(($newyear) ? '&amp;year='.$year : '');
			$nextmonth['year']	= $year;
		} else {
			$nextmonth['month'] = 1;
			$nextmonth['year']	= $year + 1;
			$nextmonth['link'] = $nextmonth['month'].'&amp;year='.$nextmonth['year'];
		}
		
		if ($month != 1)
		{
			$previousmonth['month'] = $month - 1;
			$previousmonth['link'] = $previousmonth['month'].(($newyear) ? '&amp;year='.$year : '');
			$previousmonth['year']	= $year;
			
		} else {
			$previousmonth['month'] = 12;
			$previousmonth['year']	= $year - 1;
			$previousmonth['link'] = $previousmonth['month'].'&amp;year='.$previousmonth['year'];
		}
		
		$_CLASS['core_template']->assign(array(
			'L_SUNDAY'				=> $_CLASS['core_user']->lang['datetime']['Sunday'],
			'L_MONDAY'				=> $_CLASS['core_user']->lang['datetime']['Monday'],
			'L_TUESDAY'				=> $_CLASS['core_user']->lang['datetime']['Tuesday'],
			'L_WEDNESDAY'			=> $_CLASS['core_user']->lang['datetime']['Wednesday'],
			'L_THURSDAY'			=> $_CLASS['core_user']->lang['datetime']['Thursday'],
			'L_FRIDAY'				=> $_CLASS['core_user']->lang['datetime']['Friday'],
			'L_SATURDAY'			=> $_CLASS['core_user']->lang['datetime']['Saturday'],
			'L_TODAY'				=> $_CLASS['core_user']->lang['datetime']['TODAY'],
			'CURRENT_YEAR'			=> $year,
			'THIS_MONTH_NAME'		=> $_CLASS['core_user']->lang['datetime'][date('F', mktime(0, 0, 0, $month, 1, $year))],
			'NEXT_MONTH_NAME'		=> $_CLASS['core_user']->lang['datetime'][date('F', mktime(0, 0, 0, $nextmonth['month'], 1, $year))],
			'PREVIOUS_MONTH_NAME'	=> $_CLASS['core_user']->lang['datetime'][date('F', mktime(0, 0, 0, $previousmonth['month'], 1, $year))],
			'NEXT_MONTH_YEAR'		=> $nextmonth['year'],
			'PREVIOUS_MONTH_YEAR'	=> $previousmonth['year'],
			'NEXT_MONTH'			=> generate_link("$link&amp;mode=month_view&amp;month=".$nextmonth['link']),
			'PREVIOUS_MONTH'		=> generate_link("$link&amp;mode=month_view&amp;month=".$previousmonth['link']),
			)
		);
	}
	
	function add_event($link)
	{
		global $_CLASS;
		
		$day = get_variable('day', 'GET', false, 'integer');
		$month = get_variable('month', 'GET', false, 'integer');
		$year = get_variable('year', 'GET', date('Y'), 'integer');
		
		if (!$month || $month > 12 || $month < 1)
		{
			$month = date('n');
		}
		
		if ($year != date('Y'))
		{
			$newyear = true;
		}
		
		$lastday = date("t",mktime(0, 0, 0, $month, 1, $year));
		$first_day = date("w",mktime(0, 0, 0, $month, 1, $year)) + 1;
	}
}
?>