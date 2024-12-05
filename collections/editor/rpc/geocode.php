<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/GeographicThesaurus.php');
header("Content-Type: application/json; charset=".$CHARSET);
include_once($SERVER_ROOT . '/rpc/crossPortalHeaders.php');

$lat = $_REQUEST['lat'] ?? false;
$lng = $_REQUEST['lng'] ?? false;

if(!$lat || !$lng) {
	return json_encode([]);
}

$geoThesaurus = new GeographicThesaurus();
echo json_encode($geoThesaurus->geocode($lng, $lat));
