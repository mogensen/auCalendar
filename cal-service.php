<?php

include('cal-api.php');

$format = strtolower($_GET['format']) == 'xml' ? 'xml' : 'json'; //json is the default
$date = date("Y/m/d", mktime(0, 0, 0, date("m"), date("d")+intval($_GET['date']), date("y")));
$building = $_GET['building'];

$usage = 'The followering GET parameters are implemented:
building = "' . implode(" | ", $GLOBALS['validBuildings']) . '"
format = "json | xml"';

if ($building == "") {

    if($format == 'json') {
	header('Content-type: application/json');
	echo json_encode(
		array( 
		    "error" => array ( 
			"Code" => "1", 
			"Message" => "No building parsed",
			"Usage" => $usage
			)
		    )
		);
    } else {
	echo '
	    <?xml version="1.0" encoding="utf-8"?>
	    <Error>
	    <Code>1</Code>
	    <Message>No building parsed</Message>
	    <Usage>',$usage,'</Usage>
	    </Error>
	    ';
    }
    exit;
}

$agendas = getRoomArray($building, $date);

/* output in necessary format */
if($format == 'json') {
    header('Content-type: application/json');
    echo json_encode($agendas);
} else {
    header('Content-type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<rooms>';
    foreach($agendas as $index => $post) {
	echo '<room>';
	if(is_array($post)) {
	    foreach($post as $key => $value) {
		echo '<',$key,'>';
		if(is_array($value)) {
		    foreach($value as $tag => $val) {
			if(is_array($val)) {
			    echo '<entry>';
			    foreach($val as $innerKey => $innerVal) {
				echo '<',$innerKey,'>',$innerVal,'</',$innerKey,'>';
			    }
			    echo '</entry>';
			} else {
			    echo '<',$tag,'>',$val,'</',$tag,'>';
			}
		    }
		} else {
		    echo $value;
		}
		echo '</'.$key.'>';
	    }
	}
	echo '</room>';
    }
    echo '</rooms>';
}
?>
