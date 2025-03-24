<?php
/*
 * Handler can be used to automate DwC-Archive publishing
 * PHP must be setup to run via command line. Automate by adding call to handler as a cron job, or scheduled task.
 * Variables in order: 	collids (required, multiple ids delimited by commas are allowed),
 * 						serverDomain (required),
 * 						includeIdentificationHistory (optional, 0 or 1, 1 = default),
 * 						includeImages (optional, 0 or 1, 1 = default),
 * 						redactLocalities (optional, 0 or 1, 1 = default),
 * 						includeAttributes (optional, 0 or 1, 1 = default),
 * 						includeMatSample (optional, 0 or 1, 1 = default),
 * 						includeIdentifiers (optional, 0 or 1, 1 = default),
 * ex: php dwcahandler.php 1 http://swbiodiversity.org 0 0 0 1
 * ex: php dwcahandler.php 160 http://nansh.org
 */

include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverPublisher.php');

if($argc){
	$collStr = $argv[1];
	$serverDomain = $argv[2];
	$includeDets = 1;
	$includeImgs = 1;
	$includeAttributes = 1;
	$inlcudeMaterialSample = 1;
	$includeIdentifiers = 1;
	$includeAssociations = 1;
	$redactLocalities = 1;
	if($argc > 3 && is_numeric($argv[3])){
		$includeDets = $argv[3];
		if($argc > 4 && is_numeric($argv[4])){
			$includeImgs = $argv[4];
			if($argc > 5 && is_numeric($argv[5])){
				$redactLocalities = $argv[5];
				if($argc > 6 && is_numeric($argv[6])){
					$includeAttributes = $argv[6];
					if($argc > 7 && is_numeric($argv[7])){
						$inlcudeMaterialSample = $argv[7];
						if($argc > 8 && is_numeric($argv[8])){
							$includeIdentifiers = $argv[8];
							if($argc > 9 && is_numeric($argv[9])){
								$includeAssociations = $argv[9];
							}
						}
					}
				}
			}
		}
	}

	if($collStr){
		$dwcaManager = new DwcArchiverPublisher();
		$dwcaManager->setIncludeDets($includeDets);
		$dwcaManager->setIncludeImgs($includeImgs);
		$dwcaManager->setIncludeAttributes($includeAttributes);
		if($dwcaManager->hasMaterialSamples()) $dwcaManager->setIncludeMaterialSample($inlcudeMaterialSample);
		if($dwcaManager->hasIdentifiers()) $dwcaManager->setIncludeIdentifiers($includeIdentifiers);
		if($dwcaManager->hasAssociations()) $dwcaManager->setIncludeAssociations($includeAssociations);
		$dwcaManager->setRedactLocalities($redactLocalities);
		$dwcaManager->setServerDomain($serverDomain);
		$dwcaManager->setTargetPath($SERVER_ROOT.(substr($SERVER_ROOT,-1)=='/'?'':'/').'content/dwca/');
		$dwcaManager->setVerboseMode(0);
		$collArr = explode(',',$collStr);
		$dwcaManager->batchCreateDwca($collArr);
	}
}
?>