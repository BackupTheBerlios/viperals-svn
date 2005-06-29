<?php

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
					'LINK'		=> ($num != $this->day) ? generate_link("$link&amp;mode=day_view&amp;day=".$num) : false,
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

		//mktime(hour,minute,second,month,day,year,is_dst)
		if (!$time)
		{
			$time = mktime(12, 0, 0, $this->month, $this->day, $this->year);
		}
		
		$date = explode(',', date('n,d,y', $time));
		
		$day['start'] = mktime(0, 0, 0, $date[0], $date[1], $date[2]);
		$day['end'] = mktime(24, 0, 0, $date[0], $date[1], $date[2]) - 1;

		$sql = 'SELECT * FROM '. $this->table .'
					WHERE start_time <= '. $day['end'] .' 
					AND end_time >= '. $day['start'];
					
		$result = $_CLASS['core_db']->sql_query($sql);
		
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			$_CLASS['core_template']->assign_vars_array($template_name, array(
					'TITLE'			=> $row['title'],
					'ID'			=> $row['id'],
					'DESCRIPTION'	=> $row['description'],
					'LINK'			=> generate_link("$link&amp;mode=details&amp;id=".$row['id']),
					'START_TIME'	=> $_CLASS['core_user']->format_date($row['start_time'], 'g:i A'),
					'END_TIME'		=> $_CLASS['core_user']->format_date($row['end_time'], 'g:i A'),
			));
		}
		$_CLASS['core_db']->sql_freeresult($result);
		
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
					WHERE start_time <= '. $month['end'] .' 
					AND end_time >= '. $month['start'];
		
		$result = $_CLASS['core_db']->sql_query($sql);

		$this->month_data_array = array_fill(1, $date[1], '');
		
		while ($row = $_CLASS['core_db']->sql_fetchrow($result))
		{
			// Does time between start and end span more than one day ?
			//if (($row['end_time'] - $row['start_time']) >= 86400)
			if (($row['end_time'] - $row['start_time']) < 86400)
			{
				$days = $this->generate_days($row['start_time'], $row['end_time'], 30,  $row['recur']);

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
				$day = date('j', $row['start_time']) - 1;
				if (count($this->month_data_array[$day]) >= $limit)
				{
					continue;
				}
	
				$row['link']  = generate_link($link.'&amp;mode=details&amp;id='.$row['id']);
				$this->month_data_array[$day][] = $row;
			}
		}
		
		$_CLASS['core_db']->sql_freeresult($result);		
		//print_r($this->month_data_array);
	}

	function generate_days($start, $end, $limit = 30, $recurring = 86400)
	{
		global $_CLASS;

		// how well, would this work
		// we don't want useless loops if the recurrence is 1hr, etc
		if ($recurring < 86400)
		{
			$recurring = 86400;
		}

		settype($recurring, 'integer');
		$time = $start;
		$loop = 0;

		While (($time < $end) && $loop < $limit)
		{
			$days[date('j', $time)] = true;

			$time += $recurring;
			$loop++;
		}
		
		return $days;
	}

	function get_events_details($id)
	{
		global $_CLASS;
		
		$sql = 'SELECT * FROM '. $this->table .'
					WHERE id = '.$id;
					
		$result = $_CLASS['core_db']->sql_query($sql);
		
		$row = $_CLASS['core_db']->sql_fetchrow($result);
		$_CLASS['core_db']->sql_freeresult($result);

		return $row;
	}
}
?>