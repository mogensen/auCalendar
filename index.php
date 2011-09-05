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

include('cal-api.php');

function drawTableWithBuilding($building, $date) {

    $agendas = getRoomArray($building, $date);

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

if(! isset($_GET['building']) || ! in_array($_GET['building'], $GLOBALS['validBuildings'] )) {
    foreach($GLOBALS['validBuildings'] as $key => $val) {
    echo('<input type="button" name="button1" value="'.$val.'" ONCLICK="window.location.href=\''.$val.'\'">');
    // echo('<input type="button" name="button1" value="'.$val.' i morgen" ONCLICK="window.location.href=\'?building='.$val.'&date=1\'">');
}

} else {
    echo('<input type="button" name="button1" value="Tilbage" ONCLICK="history.go(-1)">');
    $date = mktime(0, 0, 0, date("m"), date("d")+intval($_GET['date']), date("y"));
    drawTableWithBuilding($_GET['building'], date("Y/m/d", $date));
}

?>
</body> 
</html>
