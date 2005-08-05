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

function user_get_id($names, &$difference = array())
{
	global $_CLASS;

	$data = array('ids' => array(), 'names' => array());

	$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . " 
				WHERE username IN ('" . implode("' ,'", $_CLASS['core_db']->escape_array($names)) . "')";
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$data['ids'][] = $row['user_id'];
		$data['names'][] = $row['username'];
	}
	$_CLASS['core_db']->free_result($result);

	$difference = array_diff($names, $data['names']);
	
	return $data['ids'];
}

function user_get_name($ids, &$difference = array())
{
	global $_CLASS;

	$data = array('ids' => array(), 'names' => array());

	$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . ' 
				WHERE user_id IN (' . implode(', ', array_map('intval', $ids)) . ')';
	$result = $_CLASS['core_db']->query($sql);

	while ($row = $_CLASS['core_db']->fetch_row_assoc($result))
	{
		$data['ids'][] = $row['user_id'];
		$data['names'][] = $row['username'];
	}
	$_CLASS['core_db']->free_result($result);

	$difference = array_diff($ids, $data['ids']);

	return $data['names'];
}

?>