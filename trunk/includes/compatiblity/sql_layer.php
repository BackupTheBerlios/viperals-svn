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

if (!defined('VIPERAL'))
{
    Header('Location: ../../');
    die();
}

$dbi = '';

function sql_connect($host, $user, $password, $db)
{
	// connection to db should already be done if your in compatiblity mode. 
	return true;
}

function sql_logout($id)
{
	//! maybe some old phpnuke scripts would disconnect and connect to database often
	// check this out if it does, remove script_close
	script_close();
}

function sql_query($query)
{
    global $_CLASS;
	$_CLASS['db']->sql_query($query);
}

function sql_num_rows($result)
{
    global $_CLASS;
	$_CLASS['db']->sql_numrows($result);
}


function sql_fetch_row($result)
{
    global $_CLASS;
	$_CLASS['db']->sql_fetchrow($result);
}


function sql_fetch_array($result)
{
    global $_CLASS;
	$_CLASS['db']->sql_fetchrow($result);
}

function sql_fetch_object($result)
{
	//phpnuke and objects O_o;
	return ($result) ? mysql_fetch_object($result) : false;
}

function sql_free_result($result)
{
    global $_CLASS;
    return $_CLASS['db']->sql_freeresult($result);
}

?>