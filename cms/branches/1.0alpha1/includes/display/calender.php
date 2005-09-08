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

class calender
{
	var $day;
	var $month;
	var $year;
	var $month_data_array = array();
	var $table = 'cms_calender';
	
	// Use me, use me plz....
	var $time_offset = 0;
	
	/*
		Set the working date for this class
	*/
	function set_date($day, $month = false, $year = false)
	{
		//(int)
		$this->month = (!$month || $month > 12 || $month < 1) ? date('n') : $month;
		
		if ($year)
		{
			$this->year = $year;
			$this->year_current = ($this->year == date('Y')) ? true : false;
		}
		else
		{
			$this->year = date('Y');
			$this->year_current = true;
		}

		$this->last_day = date("t", mktime(0, 0, 0, $this->month, 1, $this->year));
		$this->first_day = date("w", mktime(0, 0, 0, $this->month, 1, $this->year)) + 1;
		$this->day = (!$day || $day > $this->last_day) ? date('j') : $day;
//maybe we should return errors
	}

	/*
		Returns an array of the preview and following days.
			$return['next_day']
			$return['previous_day']
	*/
	function flank_days($time = false)
	{
		if ($time)
		{
			//check if it's an array use the values in it, it that it's should be an (int) unix time
		}

		if ($this->day == $this->last_day)
		{
			$return['previous_day'] = array('month' => $this->month, 'day' => $this->day - 1, 'year' => $this->year + 1);

			if ($this->month == 12)
			{
				$return['next_day'] = array('month' => 1, 'day' => 1, 'year' => $this->year + 1);
			}
			else
			{
				$return['next_day'] = array('month' => $this->month + 1, 'day' => 1, 'year' => $this->year);
			}
		}
		else
		{
			$return['next_day'] = array('month' => $this->month, 'day' => $this->day + 1, 'year' => $this->year);
	
			if ($this->day == 1)
			{
				$month = ($this->month == 1) ? 12 : $this->month - 1;
				$year = ($month == 12) ? $this->year - 1 : $this->year;
				
				$day = date("t", mktime(0, 0, 0, $month, 1, ($year) ? $year : $this->year));

				$return['previous_day'] = array('month' => $month, 'day' => $day, 'year' => $year);
			}
			else
			{
				$return['previous_day'] = array('month' => $this->month, 'day' => $this->day - 1, 'year' => $this->year);
			}
		}
		
		return $return;
	}	

	/*
		Returns an array of the preview and following months.
			$return['next_month']
			$return['previous_month']
	*/
	function flank_months($time = false)
	{
		if ($time)
		{
			//check if it's an array use the values in it, it that it's should be an (int) unix time
		}

		if ($this->month != 12)
		{
			$return['next_month'] = array('month' => $this->month + 1, 'year' => $this->year);
		}
		else
		{
			$return['next_month'] = array('month' => 1, 'year' => $this->year + 1);
		}
		
		if ($this->month != 1)
		{
			$return['previous_month'] = array('month' => $this->month - 1, 'year' => $this->year);
		}
		else
		{
			$return['previous_month'] = array('month' => 12, 'year' => $this->year - 1);
		}
		
		return $return;
	}	
	
	function month_view($link, $template_name = 'days')
	{
		global $_CLASS;
		
		$count = ceil(($this->last_day + $this->first_day) / 7) * 7;
		$num = false;
		
		$current_month = ($this->year_current && ($this->month == date('n')));

		for($i = 1; $i <= $count; $i++)
		{
			if ($i == $this->first_day)
			{
				$num = 1;
			}
			
			if (!$num)
			{
				$_CLASS['core_template']->assign_vars_array($template_name, array(
					'NUMBER'	=> false
					));
				continue;
			}
			
			if ($template_name)
			{
				
				$_CLASS['core_template']->assign_vars_array($template_name, array(
					'NUMBER'	=> $num,
					'LINK'		=> ($num != $this->day || !$current_month) ? generate_link($link.'&amp;year='.$this->year.'&amp;month='.$this->month.'&amp;mode=day_view&amp;day='.$num) : false,
					'DATA'		=> empty($this->month_data_array[($num - 1)]) ? false : $this->month_data_array[($num - 1)],
					));
			}

			if ($num == $this->last_day)
			{
				$num = false;
			}
			else
			{
				$num++;
			}
		}
	}
	
	function get_events_day($link, $time = false, $template_name = 'events')
	{
		global $_CLASS;

		if (!$time)
		{
			$time = mktime(12, 0, 0, $this->month, $this->day, $this->year);
		}
		
		$date = explode(':', date('n:d:y', $time));
		
		$day['start'] = mktime(0, 0, 0, $date[0], $date[1], $date[2]);
		$day['end'] = mktime(24, 0, 0, $date[0], $date[1], $date[2]) - 1;

		$sql = 'SELECT * FROM '. $this->table .'
					WHERE start_date <= '. $day['end'] .' 
					AND end_date >= '. $day['start'];
					
		$result = $_CLASS['core_db']->query($sql);
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['recur'] && ($row['start_date'] < $day['start']))
			{
				$row['start_date'] = $row['start_date'] + ($row['recur'] * ceil(($day['start'] - $row['start_date']) / $row['recur']));
								
				if ($row['start_date'] > $day['end'])
				{
					continue;
				}
			}

			$time = explode(':', $row['start_time']);

			$start_time = mktime($time[0], $time[1], 0, $date[0], $date[1], $date[2]);
			$end_time = $start_time + $row['duration'];
 
			$_CLASS['core_template']->assign_vars_array($template_name, array(
					'TITLE'			=> $row['title'],
					'ID'			=> $row['id'],
					'DESCRIPTION'	=> $row['description'],
					'LINK'			=> generate_link("$link&amp;mode=details&amp;id=".$row['id']),
					'START_TIME'	=> $_CLASS['core_user']->format_date($start_time, 'g:i A'),
					'END_TIME'		=> $_CLASS['core_user']->format_date($end_time, 'g:i A'),
			));
		}
		$_CLASS['core_db']->free_result($result);
		
	}
	
	function get_events_month($link, $time = false, $return_array = false, $limit = 3)
	{
		global $_CLASS;
		
		if (!$time)
		{
			$time = mktime(12, 0, 0, $this->month, $this->day, $this->year);
		}
		
		$date = explode(',', date('n,t,Y', $time));
		$month['start'] = mktime(0, 0, 0, $date[0], 1, $date[2]);
		$month['end'] = mktime(24, 0, 0, $date[0], $date[1], $date[2]) - 1;

		$sql = 'SELECT * FROM '. $this->table .'
					WHERE start_date <= '. $month['end'] .' 
					AND end_date >= '. $month['start'];
		
		$result = $_CLASS['core_db']->query($sql);

		$this->month_data_array = array_fill(1, $date[1], '');
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			// Does time between start and end span more than one day ?
			if (($row['end_date'] - $row['start_date']) >= 86400)
			{
				$days = $this->generate_days($row['start_date'], $row['end_date'], $month['start'], $month['end'],  $row['recur']);

				foreach ($days as $day => $null)
				{
					$day = $day - 1;
	
					if (count($this->month_data_array[$day]) >= $limit)
					{
						continue;
					}
		
					$row['link']  = generate_link($link.'&amp;mode=details&amp;id='.$row['id']);
					$this->month_data_array[$day][] = $row;
				}
			}
			else
			{
				$day = date('j', $row['start_date']) - 1;
				if (count($this->month_data_array[$day]) >= $limit)
				{
					continue;
				}
	
				$row['link']  = generate_link($link.'&amp;mode=details&amp;id='.$row['id']);
				$this->month_data_array[$day][] = $row;
			}
		}
		
		$_CLASS['core_db']->free_result($result);		
	}

	function generate_days($start_time, $end_time, $start_date, $end_date, $recurring = 86400)
	{
		global $_CLASS;

		// Never know how some versions may treat things, or what other people may do.
		settype($start_time, 'integer');
		settype($start_date, 'integer');
		settype($end_time, 'integer');
		settype($end_date, 'integer');

		// we don't want useless loops if the recurrence less than 1 day
		if (is_numeric($recurring))
		{
			$recurring = ($recurring > 86400) ? (int) $recurring : 86400;
		}
// Add recurrence for months, years (maybe)..
// Damit why couldn't there always be 31 days in a month :-(

		$end_time = ($end_time < $end_date) ? (int) $end_time : (int) $end_date;

		// Get the closest time to our start_date, if start_time is before that start_date
		if ($start_time < $start_date)
		{
			$start_time = $start_time + ($recurring * ceil(($start_date - $start_time) / $recurring));
		}

		// mainly a check for the above, since recurrence can be out of the start/end date range
		if ($start_time > $end_date)
		{
			return array(); //return empty array.
		}

		$loop_time = $start_time;
		$days = array();

		While ($loop_time < $end_time)
		{
			$days[date('j', $loop_time)] = true;

			$loop_time += $recurring;
		}
		
		return $days;
	}

	function get_events_details($id)
	{
		global $_CLASS;
		
		$sql = 'SELECT * FROM '. $this->table .'
					WHERE id = '.$id;
					
		$result = $_CLASS['core_db']->query($sql);
		
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		$time = explode(':', $row['start_time']);
		//echo $row['start_time'];
// Needs fixing
		$row['start_time'] = mktime($time[0], $time[1], 0, 0, 0, 2005);
		//echo $row['start_time'];
		$row['end_time'] = $row['start_time'] + $row['duration'];

		return $row;
	}
}
?>