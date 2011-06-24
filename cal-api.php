<?php

include('simplehtmldom/simple_html_dom.php');
include('conf.php');

foreach($GLOBALS['confArray'] as $key => $val) {
$validBuildings[] = $key;
}

function curl_download($Url){

    // is cURL installed yet?
    if (!function_exists('curl_init')){
	die('Sorry cURL is not installed!');
    }

    // OK cool - then let's create a new cURL resource handle
    $ch = curl_init();

    // Now set some options (most are optional)

    // Set URL to download
    curl_setopt($ch, CURLOPT_URL, $Url);

    // Set a referer
    curl_setopt($ch, CURLOPT_REFERER, "http://calendar.au.dk/cal.php");

    // User agent
    curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");

    // Include header in result? (0 = yes, 1 = no)
    curl_setopt($ch, CURLOPT_HEADER, 0);

    // Should cURL return or print out the data? (true = return, false = print)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Timeout in seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Download the given URL, and return output
    $output = curl_exec($ch);

    // Close the cURL resource, and free system resources
    curl_close($ch);

    return $output;
}

function getRoom($roomId, $date){

    $roomId = urlencode($roomId);

    $redirectPage = curl_download("http://calendar.au.dk/cal.php?res=".$roomId."&view=day");
    $html = str_get_html($redirectPage);

    // echo htmlspecialchars($html);

    $res = $html->find('meta');

    $url = substr($res[0]->attr['content'], 7);

    if ($date != "") {
	$calContent= curl_download($url."&date=" . $date);
    } else {
	$calContent= curl_download($url);
    }
    $html = str_get_html($calContent);

    $ret = $html->find('.swcCCalTableBody');

    foreach($ret as $element) {
	foreach($element->find('p') as $p){
	    $pElements[] = trim($p->plaintext);
	}
	foreach($element->find('font') as $p){
	    $pElements[] = trim($p->plaintext);
	}
    }

    foreach ($pElements as $key => $value) { 
	if (is_null($value) || trim($value) === "") { 
	    unset($pElements[$key]); 
	} 
    } 

    return array_unique($pElements);
}

function getRoomArray($name, $date = "") {
    foreach( $GLOBALS['confArray'][$name] as $key => $value) {
	$roomInfo = getRoom($value, $date);

	$resArray[$value]['roomCode'] = $value;
	$resArray[$value]['name'] = $key;

	foreach($roomInfo as $infoKey => $agenda) {

	    if ( trim($agenda) != "No Meetings"){
		$resArray[$value]['agendas'][$infoKey]['start'] = date("Hi", strtotime(substr($agenda, 0, 10)) );
		$resArray[$value]['agendas'][$infoKey]['end']   = date("Hi", strtotime(substr($agenda, 13, 10)) );
		$resArray[$value]['agendas'][$infoKey]['info']  = trim(substr($agenda, 23));
	    } else {
		$resArray[$value]['noMeetings'] = true;
	    }
	}
	if(is_array($resArray[$value]['agendas'])) {
	    uasort($resArray[$value]['agendas'], cmpAgenda);
	}
    }
    return $resArray;
}

function cmpAgenda($a, $b){
    if ($a['start'] == $b['start'] ) {
	return 0;
    }
    return ($a['start'] < $b['start']) ? -1 : 1 ;
}

?>
