<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceDownload.php');
include_once($SERVER_ROOT . '/classes/OccurrenceMapManager.php');
include_once($SERVER_ROOT . '/classes/DwcArchiverCore.php');


$sourcePage = array_key_exists("sourcepage", $_REQUEST) ? $_REQUEST["sourcepage"] : "specimen";
$schema = array_key_exists("schema", $_REQUEST) ? $_REQUEST["schema"] : "symbiota";
$cSet = array_key_exists("cset", $_POST) ? $_POST["cset"] : '';

if ($schema == 'backup') {
	$collid = $_POST['collid'];
	if ($collid && is_numeric($collid)) {
		//check permissions due to sensitive localities not being redacted
		if ($IS_ADMIN || (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin']))) {
			$dwcaHandler = new DwcArchiverCore();
			$dwcaHandler->setSchemaType('backup');
			$dwcaHandler->setCharSetOut($cSet);
			$dwcaHandler->setVerboseMode(0);
			$dwcaHandler->setIncludeDets(1);
			$dwcaHandler->setIncludeImgs(1);
			$dwcaHandler->setIncludeAttributes(1);
			if ($dwcaHandler->hasMaterialSamples()) $dwcaHandler->setIncludeMaterialSample(1);
			if ($dwcaHandler->hasIdentifiers()) $dwcaHandler->setIncludeIdentifiers(1);
			if ($dwcaHandler->hasAssociations()) $dwcaHandler->setIncludeAssociations(1);
			$dwcaHandler->setRedactLocalities(0);
			$dwcaHandler->setCollArr($collid);

			$archiveFile = $dwcaHandler->createDwcArchive();

			if ($archiveFile) {
				ob_start();
				ob_clean();
				ob_end_flush();
				header('Content-Description: Symbiota Occurrence Backup File (DwC-Archive data package)');
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename=' . basename($archiveFile));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($archiveFile));
				//od_end_clean();
				readfile($archiveFile);
				unlink($archiveFile);
			} else {
				echo 'ERROR creating output file. Query probably did not include any records.';
			}
		}
	}
} else {
	$zip = (array_key_exists('zip', $_POST) ? $_POST['zip'] : 0);
	$allowedFormats = ['csv', 'tab'];
	$formatFromPost = (array_key_exists('format', $_POST) ? $_POST['format'] : 'csv');
	$format = in_array($formatFromPost, $allowedFormats) ? $formatFromPost : 'csv';
	$extended = (array_key_exists('extended', $_POST) ? $_POST['extended'] : 0);
	$overrideConditionLimit = (array_key_exists('overrideconditionlimit', $_POST) ? $_POST['overrideconditionlimit'] : 0);

	$redactLocalities = 1;
	$rareReaderArr = array();
	if ($IS_ADMIN || array_key_exists("CollAdmin", $USER_RIGHTS)) {
		$redactLocalities = 0;
	} elseif (array_key_exists("RareSppAdmin", $USER_RIGHTS) || array_key_exists("RareSppReadAll", $USER_RIGHTS)) {
		$redactLocalities = 0;
	} else {
		if (array_key_exists('CollEditor', $USER_RIGHTS)) {
			$rareReaderArr = $USER_RIGHTS['CollEditor'];
		}
		if (array_key_exists('RareSppReader', $USER_RIGHTS)) {
			$rareReaderArr = array_unique(array_merge($rareReaderArr, $USER_RIGHTS['RareSppReader']));
		}
	}
	$occurManager = null;
	if ($sourcePage == 'specimen') {
		//Search variables are set with the initiation of OccurrenceManager object
		$occurManager = new OccurrenceManager();
	} else {
		$occurManager = new OccurrenceMapManager();
	}
	if ($schema == 'georef') {
		$dlManager = new OccurrenceDownload();
		if (array_key_exists('publicsearch', $_POST)) $dlManager->setIsPublicDownload();
		if (array_key_exists('publicsearch', $_POST) && $_POST["publicsearch"]) {
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
		$dlManager->setSchemaType($schema);
		$dlManager->setExtended($extended);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
		$dlManager->addCondition('decimalLatitude', 'NOT_NULL', '');
		$dlManager->addCondition('decimalLongitude', 'NOT_NULL', '');
		if (array_key_exists('targetcollid', $_POST) && $_POST['targetcollid']) {
			$dlManager->addCondition('collid', 'EQUALS', $_POST['targetcollid']);
		}
		if (array_key_exists('processingstatus', $_POST) && $_POST['processingstatus']) {
			$dlManager->addCondition('processingstatus', 'EQUALS', $_POST['processingstatus']);
		}
		if (array_key_exists('customfield1', $_POST) && $_POST['customfield1']) {
			$dlManager->addCondition($_POST['customfield1'], $_POST['customtype1'], $_POST['customvalue1']);
		}
		$dlManager->downloadData();
	} elseif ($schema == 'checklist') {
		$dlManager = new OccurrenceDownload();
		if (array_key_exists('publicsearch', $_POST) && $_POST['publicsearch']) {
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
		$dlManager->setSchemaType($schema);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
		$dlManager->setTaxonFilter(array_key_exists("taxonFilterCode", $_POST) ? $_POST["taxonFilterCode"] : 0);
		$dlManager->downloadData();
	} else {
		$dwcaHandler = new DwcArchiverCore();
		$dwcaHandler->setVerboseMode(0);
		if ($schema == 'coge') {
			$dwcaHandler->setCollArr($_POST['collid']);
			$dwcaHandler->setCharSetOut('UTF-8');
			$dwcaHandler->setSchemaType('coge');
			$dwcaHandler->setExtended(false);
			$dwcaHandler->setDelimiter('csv');
			$dwcaHandler->setRedactLocalities(0);
			$dwcaHandler->setIncludeDets(0);
			$dwcaHandler->setIncludeImgs(0);
			$dwcaHandler->setIncludeAttributes(0);
			$dwcaHandler->setIncludeMaterialSample(0);
			$dwcaHandler->setIncludeIdentifiers(0);
			$dwcaHandler->setIncludeAssociations(0);
			$dwcaHandler->setOverrideConditionLimit(true);
			$dwcaHandler->addCondition('catalognumber', 'NOT_NULL');
			$dwcaHandler->addCondition('locality', 'NOT_NULL');
			if (array_key_exists('processingstatus', $_POST) && $_POST['processingstatus']) {
				$dwcaHandler->addCondition('processingstatus', 'EQUALS', $_POST['processingstatus']);
			}
			for ($i = 1; $i < 4; $i++) {
				if (array_key_exists('customfield' . $i, $_POST) && $_POST['customfield' . $i]) {
					$dwcaHandler->addCondition($_POST['customfield' . $i], $_POST['customtype' . $i], $_POST['customvalue' . $i]);
				}
			}
		} else {
			//Is an occurrence download
			if (array_key_exists('publicsearch', $_POST)) $dwcaHandler->setIsPublicDownload();
			$dwcaHandler->setCharSetOut($cSet);
			$dwcaHandler->setSchemaType($schema);
			$dwcaHandler->setExtended($extended);
			$dwcaHandler->setOverrideConditionLimit($overrideConditionLimit);
			$dwcaHandler->setDelimiter($format);
			$dwcaHandler->setRedactLocalities($redactLocalities);
			if ($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);

			if (array_key_exists('publicsearch', $_POST) && $_POST['publicsearch']) {
				$dwcaHandler->setCustomWhereSql($occurManager->getSqlWhere());
			} else {
				//Request is coming from exporter.php for collection manager tools
				if(isset($_POST['targetcollid'])) $dwcaHandler->setCollArr($_POST['targetcollid']);
				if (array_key_exists('processingstatus', $_POST) && $_POST['processingstatus']) {
					$dwcaHandler->addCondition('processingstatus', 'EQUALS', $_POST['processingstatus']);
				}
				if (array_key_exists('customfield1', $_POST) && $_POST['customfield1']) {
					$dwcaHandler->addCondition($_POST['customfield1'], $_POST['customtype1'], $_POST['customvalue1']);
				}
				if (array_key_exists('customfield2', $_POST) && $_POST['customfield2']) {
					$dwcaHandler->addCondition($_POST['customfield2'], $_POST['customtype2'], $_POST['customvalue2']);
				}
				if (array_key_exists('customfield3', $_POST) && $_POST['customfield3']) {
					$dwcaHandler->addCondition($_POST['customfield3'], $_POST['customtype3'], $_POST['customvalue3']);
				}
				if (array_key_exists('stateid', $_POST) && $_POST['stateid']) {
					$dwcaHandler->addCondition('stateid', 'EQUALS', $_POST['stateid']);
				} elseif (array_key_exists('traitid', $_POST) && $_POST['traitid']) {
					$dwcaHandler->addCondition('traitid', 'EQUALS', $_POST['traitid']);
				}
				if (array_key_exists('newrecs', $_POST) && $_POST['newrecs'] == 1) {
					$dwcaHandler->addCondition('dbpk', 'IS_NULL');
					$dwcaHandler->addCondition('catalognumber', 'NOT_NULL');
				}
			}
		}
		$outputFile = null;
		if ($zip) {
			//Ouput file is a zip file
			$includeIdent = (array_key_exists('identifications', $_POST) ? 1 : 0);
			$dwcaHandler->setIncludeDets($includeIdent);
			$includeImages = (array_key_exists('images', $_POST) ? 1 : 0);
			$dwcaHandler->setIncludeImgs($includeImages);
			$includeAttributes = (array_key_exists('attributes', $_POST) ? 1 : 0);
			$dwcaHandler->setIncludeAttributes($includeAttributes);
			$includeMaterialSample = (array_key_exists('materialsample', $_POST) ? 1 : 0);
			$dwcaHandler->setIncludeMaterialSample($includeMaterialSample);
			$includeIdentifiers = (array_key_exists('identifiers', $_POST) ? 1 : 0);
			$dwcaHandler->setIncludeIdentifiers($includeIdentifiers);
			$includeAssociations = (array_key_exists('associations', $_POST) ? 1 : 0);
			$dwcaHandler->setIncludeAssociations($includeAssociations);

			$outputFile = $dwcaHandler->createDwcArchive();
		} else {
			//Output file is a flat occurrence file (not a zip file)
			$outputFile = $dwcaHandler->getOccurrenceFile();
		}
		if ($outputFile) {
			// ob_start();
			$contentDesc = '';
			if ($schema == 'dwc') {
				$contentDesc = 'Darwin Core ';
			} else {
				$contentDesc = 'Symbiota ';
			}
			$contentDesc .= 'Occurrence ';
			if ($zip) {
				$contentDesc .= 'Archive ';
			}
			$contentDesc .= 'File';
			ob_start();
			ob_clean();
			ob_end_flush();
			header('Content-Description: ' . $contentDesc);

			if ($zip) {
				header('Content-Type: application/zip');
			} elseif ($format == 'csv') {
				header('Content-Type: text/csv; charset=' . $CHARSET);
			} else {
				header('Content-Type: text/html; charset=' . $CHARSET);
			}

			header('Content-Disposition: attachment; filename=' . basename($outputFile));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($outputFile));
			// ob_clean();
			flush();
			//od_end_clean();
			readfile($outputFile);
			unlink($outputFile);
		} else {
			header("Content-type: text/plain");
			header("Content-Disposition: attachment; filename=NoData.txt");
			echo 'The query failed to return records. Please modify query criteria and try again.';
		}
	}
}
