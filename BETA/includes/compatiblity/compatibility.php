<?php
if (!CPG_NUKE) {
    Header('Location: ../../');
    die();
}

// Import GET/POST/Cookie variables for older modules
if (!ini_get('register_globals')) {
	import_request_variables('GPC');
}

?>