<?php 
function sig()
{
	define('VIPERAL', true);
	
	ob_start('ob_gzhandler');
	header('Content-Type: image/png'); 

	global $sitedb;

	require('config.php');
	require('includes/db/connect.'.$phpEx);
	
	$numviews = $numreplies = $numtopics = 0;
	
	$result = $_CLASS['db']->sql_query('SELECT topic_views, topic_replies FROM '.$prefix.'_bb2topics');
	
	while( $post3 = $db->sql_fetchrow($result) ) {
		
	   $numviews = $numviews + $post3['topic_views'];
	   $numreplies = $numreplies + $post3['topic_replies'];
	   $numtopics++;
	}
	
	$_CLASS['db']->sql_freeresult($result);
	
	$numposts = $numtopics + $numreplies;
	
	$image = 'images/signature2.png'; 
	$im = imagecreatefrompng($image); 
	$tc = ImageColorAllocate ($im, 0, 0, 0); 
	$tc2 = ImageColorAllocate ($im, 0, 0, 255); 
	 
	ImageString($im, 3, 155, 5, 'Here\'s the Live statistics', $tc); 
	ImageString($im, 2, 190, 20, 'Total Topics :', $tc);
	ImageString($im, 2, 280, 20, $numtopics, $tc2);
	ImageString($im, 2, 190, 30, 'Total Posts  :', $tc);
	ImageString($im, 2, 280, 30, $numposts, $tc2); 
	ImageString($im, 2, 190, 40, 'Total Views  :', $tc);
	ImageString($im, 2, 280, 40, $numviews, $tc2); 
	
	Imagepng($im,'',100); 
	ImageDestroy ($im); 

	$_CLASS['db']->sql_close();
}

sig();
?>