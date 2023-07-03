<?php
//Variables needing security

//Server root is needed if scripts are to be run from outside current directory (folder)
//Leave empty if scripts are triggered from within Symbiota strcuture
//This is the path to the base folder of the Symbiota portal
$serverRoot = '';

//Path to folder containing source images; read access needed at minimum, write access to remove processed images
$sourcePathBase = '';
//Folder where images are to be placed; write access needed
$targetPathBase = '';

//Url base needed to build image URL that will be saved in DB
//Only needed if scripts are run on an exteral server
$imgUrlBase = '';

//Log title and Path to where log files will be placed
$logTitle = '';
$logProcessorPath = $sourcePathBase.'/logs';
//0 = silent, 1 = html, 2 = log file, 3 = html and log file
$logMode = 2;

//If record matching record identifier is not found, should a new blank record be created?
$createNewRec = true;

//Change to true if you want to search otherCatalogNumbers for matching identifier
$matchOtherCatalogNumbers = false;

//Weather to copyover images with matching names (includes path) or rename new image and keep both
// 0 = skip import, 1 = rename image and save both, 2 = replace image
$imgExists = 2;

$webPixWidth = 1400;
$tnPixWidth = 200;
$lgPixWidth = 3600;
$webFileSizeLimit = 500000;
$lgFileSizeLimit = 10000000;

//Value between 0 and 100
$jpgQuality = 80;

$webImg = 1;			// 1 = evaluate source and import, 2 = import source and use as is, 3 = map to source
$tnImg = 1;				// 1 = create from source, 2 = import source, 3 = map to source, 0 = exclude
$lgImg = 1;				// 1 = import source, 2 = map to source, 3 = import large version (_lg.jpg), 4 = map large version (_lg.jpg), 0 = exclude

$keepOrig = 1;

//0 = write image metadata to file; if email contact is added to collArr array, file will be emailed after processing
//1 = write metadata to a Symbiota database (connection variables must be set)
$dbMetadata = 1;

/**
 * Array of parameters for collections to process.
 * collArr => array(
 *     'sourcePathFrag' => 'asu/lichens/'	// optional path fragment appended to $sourcePathBase that is specific to particular collection. Not typcially needed.
 *     'pmterm' => '/A(\d{8})\D+/', 		// regular expression to match collectionCode and catalogNumber in filename, first backreference is used as the catalogNumber.
 *     'prpatt' => '/^/',           		// optional regular expression for match on catalogNumber to be replaced with prrepl.
 *     'prrepl' => 'barcode-',       		// optional replacement to apply for prpatt matches on catalogNumber.
 *     										// given above description, 'A01234567.jpg' will yield catalogNumber = 'barcode-01234567'
 *     'email' => 'collectionManager@asu.edu'
 * )
 *
 */

$collArr = array(
	1 => array('sourcePathFrag' => 'abc/lichens', 'pmterm' => '/^ABC(\d{8})\D*/', 'email' => 'collectionmanager@abc.edu'),
	2 => array('sourcePathFrag' => 'def/vasc/', 'pmterm' => '/^DEF(\d{8})\D*/')
);
?>