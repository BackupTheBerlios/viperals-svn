<?php
if (!CPG_NUKE) {
    Header("Location: ../../");
    die();
}

/************************************************************************/
/* PHP-NUKE: Web Portal System                                          */
/* ===========================                                          */
/*                                                                      */
/* Copyright (c) 2002 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* postgres fix by Rubén Campos - Oscar Silla                           */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/
/* Modifications made by CPG Dev Team http://cpgnuke.com                */
/* Last modification notes:                                             */
/*                                                                      */
/*   $Id: sql_layer.php,v 1.4 2004/04/23 21:07:54 gtroll Exp $          */
/*                                                                      */
/************************************************************************/

global $dbi;
$dbi = "";

/*
 * sql_query($query, $id)
 * executes an SQL statement, returns a result identifier
 */

function sql_query($query, $id)
{
    global $db;
    return $db->sql_query($query);
}

/*
 * sql_num_rows($res)
 * given a result identifier, returns the number of affected rows
 */

function sql_num_rows($res)
{
    global $db;
    return $db->sql_numrows($res);
}

/*
 * sql_fetch_row(&$res,$row)
 * given a result identifier, returns an array with the resulting row
 * Needs also a row number for compatibility with postgres
 */

function sql_fetch_row(&$res, $nr=0)
{
    global $db;
    return $db->sql_fetchrow($res);
}

/*
 * sql_fetch_array($res,$row)
 * given a result identifier, returns an associative array
 * with the resulting row using field names as keys.
 * Needs also a row number for compatibility with postgres.
 */

function sql_fetch_array(&$res, $nr=0)
{
    global $db;
    return $db->sql_fetchrow($res);
}

function sql_fetch_object(&$res, $nr=0)
{
    die("The function sql_fetch_object() isn't supported anymore. Use $db->sql_fetchrow instead!");
}

/*** Function Free Result for function free the memory ***/
function sql_free_result($res) {
    global $db;
    return $db->sql_freeresult($res);
}

?>