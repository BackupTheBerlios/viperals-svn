<?php
/*
||**************************************************************||
||  Viperal CMS © :												||
||**************************************************************||
||																||
||	Copyright (C) 2004, 2005									||
||  By Ryan Marshall ( Viperal )								||
||																||
||  Email: viperal1@gmail.com									||
||  Site: http://www.viperal.com								||
||																||
||**************************************************************||
||	LICENSE: ( http://www.gnu.org/licenses/gpl.txt )			||
||**************************************************************||
||  Viperal CMS is released under the terms and conditions		||
||  of the GNU General Public License version 2					||
||																||
||**************************************************************||

$Id$
*/

header('Content-Type: text/html');

$_CLASS['core_user']->user_setup();

require_once(SITE_FILE_ROOT.'includes/nusoap/nusoap.php');

$google_license_key = "dJ5XAtRQFHIlbfutrovVj3TizF1Q2TXP";

$query = get_variable('query', 'POST');
$search_type = get_variable('search_type', 'POST', 0, 'int');

$limit = 10;

if (!$query)
{
	script_close();
}

$query_command = $query;

if ($search_type === 1)
{
	$query_command .= ' site:viperals.berlios.de';
}

$params = array(
	'key' 		=> (string) $google_license_key,
	'q' 		=> (string) $query_command, //'viperal site:cpgnuke.com',
	'start'		=> (int) 0,	
	'maxResults' => (int) $limit,
	'filter' 	=> (boolean) true,
	'restricts'	=> (string)'', //'lang_en'
	'safeSearch'=> (boolean) false,
	'lr'		=> (string) '',	

	'ie'		=> 'UTF-8', // No longer used
	'oe'		=> 'UTF-8',	// No longer used
);		

$client = new soapclient('http://api.google.com/search/beta2');
$result = $client->call('doGoogleSearch', $params, 'urn:GoogleSearch');

if ($client->fault || $client->getError())
{
	script_close();
}

$pagination = generate_pagination('google_search&amp;query='.urlencode($query).'&amp;search_type='.$search_type, $result['estimatedTotalResultsCount'], $limit, 0);
$count = count($result['resultElements']);
$num = 1;

for ($i = 0; $i < $count; $i++)
{
	$_CLASS['core_template']->assign_vars_array('google_result', array(
		'num' 			=> $num,
		'url' 			=> $result['resultElements'][$i]['URL'],
		'title'			=> $result['resultElements'][$i]['title'],
		'snippet'		=> $result['resultElements'][$i]['snippet'],
	));

	$num++;
}
unset($result);

$params = array(
	'key' 		=> (string) $google_license_key,
	'phrase' 	=> (string) $query,
);

$spelling_suggestion = $client->call('doSpellingSuggestion', $params, 'urn:GoogleSearch');

if (is_array($spelling_suggestion))
{
	$spelling_suggestion = false;
}

$_CLASS['core_template']->assign_array(array(
	'spelling_suggestion'		=> $spelling_suggestion,
	'link_spelling_suggestion'	=> ($spelling_suggestion) ? generate_link('google_search&amp;query='.urlencode($spelling_suggestion).'&amp;search_type='.$search_type) : '',
	'google_pagination' 		=> $pagination['formated'],
	'google_pagination_array'	=> $pagination['array']
));

$_CLASS['core_template']->display('modules/google_search/results.html');

script_close();

?>