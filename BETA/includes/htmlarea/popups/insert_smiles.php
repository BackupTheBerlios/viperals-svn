<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>Smiles</title>

<script type="text/javascript" src="popup.js"></script>

<script type="text/javascript">

function onOK(url) {
	__dlg_close(url);
	return false;
};

function onCancel() {

  __dlg_close(null);

  return false;

};
</script>

<style type="text/css">

html, body {
  background: ButtonFace;
  margin: 0px;
  padding: 5px;
}

img {
  border: 0px;
}

</style>

</head>

<body onload="__dlg_init()">

<table>
<?php
define('CPG_NUKE', 'XMLFEED');

require('../../../config.php');
require('../../../includes/db/'.$sitedb['dbtype'].'.'.$phpEx);

$_CLASS['db'] =& new sql_db();
$_CLASS['db']->sql_connect($sitedb['dbhost'], $sitedb['dbuname'], $sitedb['dbpass'], $sitedb['dbname'], $sitedb['dbport'], false);
unset($sitedb);

$sql = 'SELECT emoticon, smile_url FROM '.$prefix.'_smilies ORDER BY smile_id LIMIT 20';
$result = $_CLASS['db']->sql_query($sql);
$count = 0;

while ($row = $_CLASS['db']->sql_fetchrow($result)) {
	if ($count == 5)
    {
        echo '</tr><tr>';
        $count = 0;
	}
	$url = 'http://www.viperal.com/images/smiles/'.$row['smile_url'];
	echo "<td><img  onclick=\"onOK('$url');\" src=\"$url\" alt=\"".$row['emoticon']."\"></td>";
	
	$count ++;
}

$_CLASS['db']->sql_close();

?>
</table><br clear="all" />
<div style="text-align: center;"><a href="" onclick="return onCancel();">Close</a></div>
<br clear="all" /><br clear="all" />
</body>
</html>