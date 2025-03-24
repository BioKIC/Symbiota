<?php
/*
 * Base URL: <domain plus path to Symbiota root directory>/webservices/dwc/dwcapubhandler.php
 *
 * Variables:
 *   collid (default: 0 [all collections]): PK for targeted collection. More than one codes can be supplied by separating them commas (e.g. collid=2,3,77)
 *   cond: search variable conditions to limit return based on the indexed Darwin Core fields
 *       Format: cond=<field1>-<optional operator>:<value1>;<field2>-<optional operator>:<value2>
 *       Fields allowed: catalogNumber, otherCatalogNumbers, occurrenceID, family, sciname, country, stateProvince, county, municipality, recordedBy, recordNumber, eventDate,
 *       	decimalLatitude, decimalLongitude, minimumElevationInMeters, maximumElevationInMeters, cultivationStatus, processingStatus, dateLastModified, dateEntered, dbpk
 *       Optional operators: EQUALS, IS_NULL, NOT_NULL, START_WITH, LIKE, LESS_THAN, GREATER_THAN
 *       Note the dash separating the field and operator; operator is not case specific
 *       Multiple conditions can be supplied separated by semicolons
 *       Multiple values can be supplied separated by commas
 *   colltype (specimens [default], observations): limit output to specimens or observation projects
 *   usethes (0 [default] or 1): when searching on a taxonomic name, setting usethes to 1 will run search term through the taxonomic thesaurus to include synonym within the search
 *   schema (dwc [default], symbiota): schema type
 *   extended (0 [default], 1): true outputs extended data fields such as: collectionID,rights fields, storageLocation, observerUid, processingStatus, duplicateQuantity, dateEntered, dateLastModified
 *   imgs (0, 1 [default]): Output image URLs within an media extension file
 *   dets (0, 1 [default]): Output determination history within an identification extension file
 *   attr (0 [default], 1): Output occurrence attribute values within a MeasurementOrFact extension file
 *   ident (0 [default], 1): Output occurrence identifiers values within an Alternative Identifiers extension file
 *
 * Return: Darwin Core Archive of matching occurrences, associated images, identification history, and other associated data extensions
 *   Occurrence record return limited to 1,000,000 records
 *
 * Examples:
 *
 *   https://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?collid=17
 *   https://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?collid=17&cond=stateProvince:New%20Mexico
 *   https://mycoportal.org/portal/webservices/dwc/dwcapubhandler.php?collid=15&cond=dateentered-greaterthan=2015-08-01
 *   https://lichenportal.org/portal/webservices/dwc/dwcapubhandler.php?collid=22&cond=processingstatus-equals:reviewed;dateentered-greaterthan:2015-04-01
 *   https://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?cond=sciname:Berberis%20repens
 *   https://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?cond=sciname:Berberis%20repens&usethes=1
 *
 */

include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/DwcArchiverCore.php');

$collid = array_key_exists('collid', $_REQUEST) ? $_REQUEST['collid'] : 0;
$cond = array_key_exists('cond', $_REQUEST) ? $_REQUEST['cond'] : '';
$collType = array_key_exists('colltype', $_REQUEST) ? $_REQUEST['colltype'] : '';
$schemaType = array_key_exists('schema', $_REQUEST) ? $_REQUEST['schema'] : 'dwc';
$extended = !empty($_REQUEST['extended']) ? 1 : 0;
$includeDets = isset($_REQUEST['dets']) && !$_REQUEST['dets'] ? 0 : 1;
$includeImgs = isset($_REQUEST['imgs']) && !$_REQUEST['imgs'] ? 0 : 1;
$includeAttributes = !empty($_REQUEST['attr']) ? 1 : 0;
$includeMaterialSample = !empty($_REQUEST['matsample']) ? 1 : 0;
$includeIdentifiers = !empty($_REQUEST['ident']) ? 1 : 0;
$includeAssociations = !empty($_REQUEST['assoc']) ? 1 : 0;
$pubGuid = array_key_exists('publicationguid', $_REQUEST) ? $_REQUEST['publicationguid'] : 0;
$requestPortalGuid = array_key_exists('portalguid', $_REQUEST) ? $_REQUEST['portalguid'] : 0;

$_SESSION['citationvar'] = 'collid=' . $collid;

$dwcaHandler = new DwcArchiverCore();

$dwcaHandler->setVerboseMode(0);
$dwcaHandler->setCollArr($collid, $collType);

$redactLocalities = 1;
if ($IS_ADMIN || array_key_exists('CollAdmin', $USER_RIGHTS)) {
	$redactLocalities = 0;
} elseif (array_key_exists('RareSppAdmin', $USER_RIGHTS) || array_key_exists('RareSppReadAll', $USER_RIGHTS)) {
	$redactLocalities = 0;
} elseif (array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollEditor'])) {
	$redactLocalities = 0;
} elseif (array_key_exists('RareSppReader', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['RareSppReader'])) {
	$redactLocalities = 0;
}
$dwcaHandler->setRedactLocalities($redactLocalities);

if ($cond) {
	//String of cond-key/value pairs (e.g. country:USA,United States;stateprovince:Arizona,New Mexico;county-start:Pima,Eddy
	$cArr = explode(';', $cond);
	foreach ($cArr as $rawV) {
		$tok = explode(':', $rawV);
		if ($tok) {
			$field = $tok[0];
			$cond = 'EQUALS';
			$valueArr = array();
			if ($p = strpos($tok[0], '-')) {
				$field = substr($tok[0], 0, $p);
				$cond = substr($tok[0], $p + 1);
			}
			if (isset($tok[1]) && $tok[1]) {
				$valueArr = explode(',', $tok[1]);
			}
			if ($valueArr) {
				foreach ($valueArr as $v) {
					$dwcaHandler->addCondition($field, $cond, $v);
				}
			} else {
				$dwcaHandler->addCondition($field, $cond);
			}
		}
	}
}
$dwcaHandler->setSchemaType($schemaType);
$dwcaHandler->setExtended($extended);
$dwcaHandler->setIncludeDets($includeDets);
$dwcaHandler->setIncludeImgs($includeImgs);
$dwcaHandler->setIncludeAttributes($includeAttributes);
$dwcaHandler->setIncludeMaterialSample($includeMaterialSample);
$dwcaHandler->setIncludeIdentifiers($includeIdentifiers);
$dwcaHandler->setIncludeAssociations($includeAssociations);
$dwcaHandler->setPublicationGuid($pubGuid);
$dwcaHandler->setRequestPortalGuid($requestPortalGuid);

$archiveFile = $dwcaHandler->createDwcArchive();
ob_start();
ob_clean();
ob_end_flush();
if ($archiveFile) {
	header('Content-Description: DwC-A File Transfer');
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename=' . basename($archiveFile));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($archiveFile));
	ob_clean();
	flush();
	ob_end_clean();
	readfile($archiveFile);
	unlink($archiveFile);
	exit;
} else {
	header('Content-Description: DwC-A File Transfer Error');
	header('Content-Type: text/plain');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	if ($dwcaHandler->getErrorMessage()) echo $dwcaHandler->getErrorMessage();
	else echo 'ERROR: unable to create archive';
}
