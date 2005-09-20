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

if (!defined('VIPERAL'))
{
    die;
}

if (!defined('CALENDER_TABLE'))
{
	define('CALENDER_TABLE', $table_prefix.'calender');
}

$_CLASS['core_user']->user_setup();
$_CLASS['core_user']->add_lang();

$mode = get_variable('mode', 'GET', false);

$link = 'calender';

$day = get_variable('day', 'REQUEST', false, 'integer');
$month = get_variable('month', 'REQUEST', false, 'integer');
$year = get_variable('year', 'REQUEST', false, 'integer');

load_class(SITE_FILE_ROOT.'includes/display/calender.php', 'calender');

$_CLASS['calender']->table = CALENDER_TABLE;
$_CLASS['calender']->set_date($day, $month, $year);

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

		$_CLASS['core_display']->display(false, 'modules/calender/calender_day.html');
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
	
		$_CLASS['core_display']->display(false, 'modules/calender/calender_details.html');

	break;
	
	case 'month_view':
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

		$_CLASS['core_display']->display(false, 'modules/calender/calender_main.html');

	break;	
}

?>