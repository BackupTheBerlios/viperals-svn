<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||

$Id$
*/

class calender
{
	var $day;
	var $month;
	var $year;
	var $month_data_array = array();
	var $table = false;
	
	// Use me, use me plz....
	var $time_offset = 0;
	
	/*
		Set the working date for this class
	*/
	function set_date($day, $month = false, $year = false)
	{
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
		//checkdate ( int month, int day, int year )

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
		
		$day['start'] = mktime(0, 0, 0, $date[0], $date[1], $date[2]) + 60;
		$day['end'] = mktime(24, 0, 0, $date[0], $date[1], $date[2]) - 1;

		$sql = 'SELECT * FROM '. $this->table .'
					WHERE calender_starts <= '. $day['end'] .' 
					AND calender_expires >= '. $day['start'];
					
		$result = $_CLASS['core_db']->query($sql);
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			if ($row['calender_recur_rate'] && ($row['calender_starts'] < $day['start']))
			{
				$row['calender_starts'] = $row['calender_starts'] + ($row['calender_recur_rate'] * ceil(($day['start'] - $row['calender_starts']) / $row['calender_recur_rate']));
								
				if ($row['calender_starts'] > $day['end'])
				{
					continue;
				}
			}

			if ($row['calender_start_time'])
			{
				$temp_time = explode(':', $row['calender_start_time']);

				$start_time = mktime($temp_time[0], $temp_time[1], 0, $date[0], $date[1], $date[2]);
				$end_time = $start_time + $row['duration'];
			}
			else
			{
				$start_time = ($row['calender_starts'] > $day['start']) ? $day['start'] : $row['calender_starts'];
				$end_time = $row['calender_expires'];
			}
 
			$_CLASS['core_template']->assign_vars_array($template_name, array(
					'TITLE'				=> $row['calender_title'],
					'ID'				=> $row['calender_id'],
					'DESCRIPTION'		=> $row['calender_text'],
					'LINK'				=> generate_link("$link&amp;mode=details&amp;id=".$row['calender_id']),
					'START_TIME'		=> $_CLASS['core_user']->format_date($start_time, 'g:i A'),
					'END_TIME'			=> $_CLASS['core_user']->format_date($end_time, 'g:i A'),
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
					WHERE calender_starts <= '. $month['end'] .' 
					AND calender_expires >= '. $month['start'];
		
		$result = $_CLASS['core_db']->query($sql);

		$this->month_data_array = array_fill(1, $date[1], '');
		
		while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
		{
			// Does time between start and end span more than one day ?
			if (($row['calender_expires'] - $row['calender_starts']) >= 86400)
			{
				$days = $this->generate_days($row['calender_starts'], $row['calender_expires'], $month['start'], $month['end'],  $row['calender_recur_rate']);

				foreach ($days as $day => $null)
				{
					$day = $day - 1;
	
					if (count($this->month_data_array[$day]) >= $limit)
					{
						continue;
					}
		
					$row['calender_link']  = generate_link($link.'&amp;mode=details&amp;id='.$row['calender_id']);
					$this->month_data_array[$day][] = $row;
				}
			}
			else
			{
				$day = date('j', $row['calender_starts']) - 1;

				if (count($this->month_data_array[$day]) >= $limit)
				{
					continue;
				}
	
				$row['calender_link']  = generate_link($link.'&amp;mode=details&amp;id='.$row['calender_id']);
				$this->month_data_array[$day][] = $row;
			}
		}
		
		$_CLASS['core_db']->free_result($result);		
	}

	function generate_days($calender_start_time, $end_time, $calender_starts, $calender_expires, $calender_recur = 86400)
	{
		global $_CLASS;

		// Never know how some versions may treat things, or what other people may do.
		settype($calender_start_time, 'integer');
		settype($calender_starts, 'integer');
		settype($end_time, 'integer');
		settype($calender_expires, 'integer');

		$calender_recurring = false;

		if (is_string($calender_recur) && in_array($calender_recur, 'everyday', 'every_other', 'weekly', 'monthly', 'years'))
		{
			Switch ($calender_recur)
			{
				Case 'weekly':
					
				Case 'monthly':
			}
		}
		
		// we don't want useless loops if the calender_recurrence less than 1 day
		if (!$calender_recurring)
		{
			$calender_recurring = ($calender_recurring > 86400) ? (int) $calender_recurring : 86400;
		}

		$end_time = ($end_time < $calender_expires) ? (int) $end_time : (int) $calender_expires;

		// Get the closest time to our calender_starts, if calender_start_time is before that calender_starts
		if ($calender_start_time < $calender_starts)
		{
			$calender_start_time = $calender_start_time + ($calender_recurring * ceil(($calender_starts - $calender_start_time) / $calender_recurring));
		}

		// mainly a check for the above, since calender_recurrence can be out of the start/end date range
		if ($calender_start_time > $calender_expires)
		{
			return array(); //return empty array.
		}

		$loop_time = $calender_start_time;
		$days = array();

		While ($loop_time < $end_time)
		{
			$days[date('j', $loop_time)] = true;

			$loop_time += $calender_recurring;
		}
		
		return $days;
	}

	function get_events_details($id)
	{
		global $_CLASS;
		
		$sql = 'SELECT * FROM '. $this->table .'
					WHERE calender_id = '.$id;
					
		$result = $_CLASS['core_db']->query($sql);
		
		$row = $_CLASS['core_db']->fetch_row_assoc($result);
		$_CLASS['core_db']->free_result($result);
		
		if ($row['calender_start_time'])
		{
			if (!$time)
			{
				$time = mktime(12, 0, 0, $this->month, $this->day, $this->year);
			}
	
			$date = explode(',', date('n,t,Y', $time));

			$temp_time = explode(':', $row['calender_start_time']);

			$row['start_time'] = mktime($temp_time[0], $temp_time[1], 0, $date[0], $date[1], $date[2]);
			$row['end_time'] = $start_time + $row['duration'];
		}
		else
		{
			$row['start_time'] = $row['calender_starts'];
			$row['end_time'] = $row['calender_expires'];
		}

		return $row;
	}
}
?>