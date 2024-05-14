<?php
include_once('../../config/symbini.php');
include_once ($SERVER_ROOT . '/classes/GeographicThesaurus.php');
header('Content-Type: application/json;charset='.$CHARSET);

ob_start();
$geoterm = array_key_exists('geoterm',$_REQUEST)? $_REQUEST['geoterm']:'';
$geolevel = array_key_exists('geolevel',$_REQUEST) && is_numeric($_REQUEST['geolevel'])? intval($_REQUEST['geolevel']): null;
$parent = array_key_exists('parent',$_REQUEST)? $_REQUEST['parent']: null;

$geoManager = new GeographicThesaurus();
$geoterms = $geoManager->searchGeothesaurus($geoterm, $geolevel, $parent);
echo json_encode($geoterms);
?>
