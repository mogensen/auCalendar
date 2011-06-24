<!DOCTYPE html>
<html>
<head>
<title>CS Lokaler</title>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="css/ui.css" type="text/css" media="screen">
<link rel="stylesheet" href="css/main.css" type="text/css" media="screen">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<link rel="apple-touch-icon" href="img/au_logo.png"/>
</head>
<body>

<?php

include('simplehtmldom/simple_html_dom.php');

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

function getRoomArray($roomArray, $date = "") {
    foreach( $roomArray as $key => $value) {
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

function drawTableWithBuilding($building, $date) {

    $confArray['ithus'] = array( 
	    "ITHuset Store Aud" => "DI-5510.103 (Store Aud. Åbogade 15)",
	    "ITHuset Lille Aud" => "DI-5510.104 (Lille Aud. Åbogade 15)",
	    "ITHuset 112" => "DI-5520.112 (Undervisningslokale. Åbogade 15)",
	    "ITHuset 120" => "DI-5523.120",
	    "ITHuset 121" => "DI-5523.121",
	    "ITHuset 129" => "DI-5523.129",
	    "ITHuset 131" => "DI-5523.131",
	    "ITHuset 137 (IMV)" => "DI-5524.137 (IMV)",
	    "ITHuset 139 (IMV)" => "DI-5524.139 (IMV)",
	    "ITHuset 143 (IMV)" => "DI-5524.143 (IMV)",
	    "ITHuset 147 (IMV)" => "DI-5524.147 (IMV)");

    $confArray['stibitz'] = array( 
	    "Stibitz 123 (Lærestedet-1)" => "DI-Stibitz-123 (Lærestedet-1)",
	    "Stibitz 113 (Lærestedet-2)" => "DI-Stibitz-113 (Lærestedet-2)",
	    "Stibitz 117 (Lærestedet-gennemgang)" => "DI-Stibitz-117 (Lærestedet-gennemgang)");

    $confArray['zuse'] = array( 
	    "Zuse 127 (Øvelser)" => "DI-Zuse-127 (Øvelser)",
	    "Zuse 128c Legolab" => "DI-Zuse-128c Legolab");

    $agendas = getRoomArray($confArray[$building], $date);

    $now = date("Hi");
    $i = 0;

    echo("<h2>Agenda for ". $building . " den " .$date . "</h2>");
    echo('<table width="100%">');

    foreach($agendas as $key => $value){
	$i = ($i + 1 )% 2;
	$busy = false;

	if ($value['noMeetings']) {
	    $content .= '<tr class="zebra'.$i.'"><td>';
	    $content .= 'Dette rum er frit hele dagen.';
	    $content .= "</td></tr>";
	} else {
	    foreach($value['agendas'] as $innerKey => $innerValue){
		$content .= '<tr class="zebra'.$i.'"><td>';
		$content .= $innerValue['start'] . " - " . $innerValue['end'] ."<br>";
		$content .= $innerValue['info'];
		$content .= "</td></tr>";
		if ($innerValue['start'] < $now && $innerValue['end'] > $now && $busy != true) {
		    $busy = true;
		}
	    }
	}

	echo('<tr class="zebra'.$i.'"><td>');

	if( $busy == false ) {
	    echo('<h2 class="free">'. $value['name'] . '</h2>');
	} else {
	    echo('<h2>'. $value['name'] . '</h2>');
	}

	echo("</td></tr>");
	echo $content;
	$content = "";
    }

    echo("</table>");
}

if(! isset($_GET['building'])) {
    echo('<input type="button" name="button1" value="ITHuset" ONCLICK="window.location.href=\'?building=ithus\'">');
    echo('<input type="button" name="button1" value="ITHuset i morgen" ONCLICK="window.location.href=\'?building=ithus&date=1\'">');
    echo('<input type="button" name="button1" value="Stibitz" ONCLICK="window.location.href=\'?building=stibitz\'">');
    echo('<input type="button" name="button1" value="Stibitz i morgen" ONCLICK="window.location.href=\'?building=stibitz&date=1\'">');
    echo('<input type="button" name="button1" value="Zuse" ONCLICK="window.location.href=\'?building=zuse\'">');
    echo('<input type="button" name="button1" value="Zuse i morgen" ONCLICK="window.location.href=\'?building=zuse&date=1\'">');

} else {
    echo('<input type="button" name="button1" value="Tilbage" ONCLICK="history.go(-1)">');
    $date = mktime(0, 0, 0, date("m"), date("d")+intval($_GET['date']), date("y"));
    drawTableWithBuilding($_GET['building'], date("Y/m/d", $date));
}

?>
</body> 
</html>
