<?php
if (!CPG_NUKE) {
    Header("Location: ../../");
    die();
}

// Import GET/POST/Cookie variables into the global scope
if (!ini_get("register_globals")) {
	$r_globals = intval(ini_get('register_globals'));
	if ($r_globals==0) {
		import_request_variables('GPC');
	}
}

?>