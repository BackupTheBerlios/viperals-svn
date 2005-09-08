<?php 

// Set externals (database, system, setc) time limit to none.
// Only needed if you database is slow or you have a huge site.
// set_time_limit(0);

// All the script to continue after the user browser disconnect
ignore_user_abort(true);

/*
	Note none of these aftect the max_execution_time,
	as the execution time doesn't take in account the db query time
*/

header('Connection: close');

?>