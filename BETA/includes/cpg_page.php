<?
function cpg_header($cpginfo) {
//XHTML header:
//<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
//<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>CPG-Nuke '.$cpginfo.'</title>
<link rel="StyleSheet" href="includes/cpg.css" type="text/css">
</head>
<body bgcolor="#E5E5E5" text="#000000" link="#006699" vlink="#5493B4"><center>
<table border="0" cellspacing=0 cellpadding="0" width="96%"><tr><td align="center">
  <table border="0" cellspacing=2 cellpadding="2" width="100%" height="151" background="images/back.gif">
  <tr>
<!--    <td width=212 align="left"><img src="images/logo.gif" border="0" alt="CPG-Nuke" /></td> -->
    <td align="center"><font class="header">'.$cpginfo.'</font></td>
<!--    <td width=212 valign="bottom"><img align="right" height="22" width="202" src="images/shout.gif" alt="" /></td> -->
  </tr>
  </table>
  <table width="100%" border="0" cellspacing="1" cellpadding="0" bgcolor="#006699"><tr><td>
  <table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="FFFFFF"><tr><td align="center">
';
}

function cpg_footer() {
    if (defined('_GOBACK')) $goback = _GOBACK;
    else $goback = '[ <a href="javascript:history.go(-1)"><b>Go Back</b></a> ]';
    return '<br /><br />'.$goback.'
  </td></tr></table></td></tr></table>
</td></tr></table>
</center></body></html>';
}
?>