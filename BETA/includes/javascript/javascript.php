<?php
     
if (!defined('CPG_NUKE')) 
{ 
	header('Location: ../index.php');
	exit;
}

global $module, $name;

if ($module == 1 AND file_exists("modules/$name/copyright.php")) {
    echo "<script type=\"text/javascript\">\n";
    echo "<!--\n";
    echo "function openwindow(){\n";
    echo "    window.open (\"modules/$name/copyright.php\",\"Copyright\",\"toolbar=no,location=no,directories=no,status=no,scrollbars=yes,resizable=no,copyhistory=no,width=400,height=200\");\n";
    echo "}\n";
    echo "//-->\n";
    echo "</SCRIPT>\n\n";
}

?>