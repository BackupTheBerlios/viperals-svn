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


$error = array();

global $_CLASS, $table_prefix, $site_file_root;

if (!defined('CALENDER_TABLE'))
{
	define('CALENDER_TABLE', $table_prefix.'calender');
}

$link = 'Control_Panel&amp;i='.$id;

$day = get_variable('day', 'REQUEST', false, 'integer');
$month = get_variable('month', 'REQUEST', false, 'integer');
$year = get_variable('year', 'REQUEST', false, 'integer');

load_class($site_file_root.'includes/display/calender.php', 'calender');
$_CLASS['calender']->table = CALENDER_TABLE;
$_CLASS['calender']->set_date($day, $month, $year);

if (isset($_GET['mode']) && $_GET['mode'] === 'details')
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
		
		$_CLASS['core_template']->assign_array(array(
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

		$_CLASS['core_display']->display(false, 'modules/control_panel/ucp_calender_day.html');
	break;
		
	case 'add_event':
		if (isset($_POST['submit']))
		{
			if ($this->add_event() !== false)
			{
				trigger_error('EVENT_ADDED');
			}			
		}

		$_CLASS['core_template']->assign_array(array(
			'ERROR'			=> empty($error) ? '' : implode('<br/>', $error),
			'S_UCP_ACTION'	=> generate_link("Control_Panel&amp;i=$id&amp;mode=$mode"),
		));

		$this->display($_CLASS['core_user']->lang['UCP_MAIN'], 'ucp_calender_add.html');
	break;
		
	case 'details':
	
		$id = get_variable('id', 'GET', false, 'integer');
		$data = false;
		$data = $_CLASS['calender']->get_events_details($id);
		
		$_CLASS['core_template']->assign_array(array(
			'CAL_TITLE'			=> $data['calender_title'],
			'CAL_DESCRIPTION'	=> $data['calender_text'],
			'CAL_START_TIME'	=> $_CLASS['core_user']->format_date($data['start_time']),
			'CAL_END_TIME'		=> $_CLASS['core_user']->format_date($data['end_time']),
		));
	
		$_CLASS['core_display']->display(false, 'modules/Control_Panel/ucp_calender_details.html');
	break;
	
	//case 'month_view':
	default:
		$_CLASS['calender']->get_events_month($link);
		$_CLASS['calender']->month_view($link);

		$month_flanks = $_CLASS['calender']->flank_months();

		$_CLASS['core_template']->assign_array(array(
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

		$_CLASS['core_display']->display(false, 'modules/control_panel/ucp_calender_main.html');
	break;	
}

function add_event()
{
	global $_CLASS;
	
	$data_array = array(
		'calender_title'	=> mb_strtolower(htmlentities(get_variable('title', 'POST', ''), ENT_QUOTES, 'UTF-8')),
		'calender_text'		=> strip_tags(get_variable('description', 'POST', false)),
		'calender_notes'	=> strip_tags(get_variable('note', 'POST', false)),
		'calender_starts'	=> get_variable('start', 'POST', false),
		'calender_expires'	=> get_variable('end', 'POST', false),
		//'recur'			=> false,
	);
	
	$error = array();
	
	if (empty($data_array['calender_title']))
	{
		$error[] = $_CLASS['core_user']->get_lang('NO_TITLE');
	}
	
	$start_time = strtotime($data_array['calender_starts']);
	
	if (!$start_time || $start_time === -1)
	{
		$error[] = $_CLASS['core_user']->get_lang('ERROR_START_TIME');
	}
	
	$end_time = strtotime($data_array['calender_expires']);
	
	if (!$end_time || $end_time === -1)
	{
		$error[] = $_CLASS['core_user']->get_lang('ERROR_END_TIME');
	}
	
	if (empty($error) && $start_time > $end_time)
	{
		$error[] = $_CLASS['core_user']->get_lang('ERROR_');
	}
	
	if (!empty($error))
	{
		return false;
	}
	
	//$duration = $start_time - $end_time;
	//$start_time = $date = implode(''. explode(':', date('H:i', $start_time)));;
	
	$data_array['calender_starts'] = $_CLASS['core_user']->time_convert($start_time, 'gmt');
	$data_array['calender_expires'] = $_CLASS['core_user']->time_convert($end_time, 'gmt');
	
	$_CLASS['core_db']->query('INSERT INTO ' . CALENDER_TABLE .' ' . $_CLASS['core_db']->sql_build_array('INSERT', $data_array));
	
	$data_array['calender_id'] = $_CLASS['core_db']->insert_id(CALENDER_TABLE, 'calender_id');
	
	return $data_array;
}

?>