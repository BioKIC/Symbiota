<?php
//Relies on symbini being called already

//Adds Array of all valid portals urls and origins
if(empty($EXTERNAL_PORTAL_HOSTS)) {
	$EXTERNAL_PORTAL_HOSTS = [];

	$conn = MySQLiConnectionFactory::getCon('readonly');
	$portals = $conn->query(<<<sql
		SELECT portalName, urlRoot from portalindex p;
	sql)->fetch_all(MYSQLI_ASSOC);

	foreach($portals as $portal) {
		$origin_parts = preg_split('/[\/]+/', $portal['urlRoot']);

		if(count($origin_parts) >= 2) {
			$portal['origin'] = $origin_parts[0] . "//" . $origin_parts[1];
			array_push($EXTERNAL_PORTAL_HOSTS, $portal);
		}
	}
}

//Adds Origin if it is a valid portal origin
if (isset($_SERVER['HTTP_ORIGIN']) && ($idx = array_search($_SERVER['HTTP_ORIGIN'], array_column($EXTERNAL_PORTAL_HOSTS, 'origin'))) !== false) {

	$origin = $EXTERNAL_PORTAL_HOSTS[$idx]['origin'];

	header('Access-Control-Allow-Origin: ' . $origin);
	header('Access-Control-Allow-Headers: ' . $origin);
}
?>
