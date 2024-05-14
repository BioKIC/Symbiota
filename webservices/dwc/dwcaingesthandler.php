<?php
/*
 * Note: See http://www.php.net/manual/en/features.file-upload.common-pitfalls.php
 * for discussion of PHP server configuration directives that you will probably need
 * to set for file uploads.
 *
 * ****  Input Variables  ********************************************
 *
 * uploadtype (required): $FILEUPLOAD = 3; $DWCAUPLOAD = 6
 * key (required): security key used to authorize
 * filepath: URI path to locality where DWCA file was placed for retrieval (file must be read accessible to portal)
 * uploadfile: file streamed in for upload; POST protocol must be used when streaming file
 * importident (default = false): 0 = identification history NOT included for ingestion, 1 = identification history included for ingestion
 * importimage (default = false): 0 = image URLs NOT included for ingestion, 1 = image URLs included for ingestion
 */

date_default_timezone_set('America/Phoenix');
include_once('../../config/symbini.php');
require_once($SERVER_ROOT.'/classes/SpecUploadBase.php');
require_once($SERVER_ROOT.'/classes/SpecUploadFile.php');
require_once($SERVER_ROOT.'/classes/SpecUploadDwca.php');

$uploadType = isset($_REQUEST['uploadtype']) ? filter_var($_REQUEST['uploadtype'], FILTER_SANITIZE_NUMBER_INT) : '';
$securityKey = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
$filePath = array_key_exists('filepath', $_REQUEST) ? $_REQUEST['filepath'] : false;
$importIdent = array_key_exists('importident', $_REQUEST) ? filter_var($_REQUEST['importident'], FILTER_SANITIZE_NUMBER_INT) : 1;
$importImage = array_key_exists('importimage', $_REQUEST) ? filter_var($_REQUEST['importimage'], FILTER_SANITIZE_NUMBER_INT) : 1;
$sourceType = array_key_exists('sourcetype', $_REQUEST) ? $_REQUEST['sourcetype'] : '';

if(!$uploadType) exit('ERROR: uploadtype is required and is null ');

$duManager = null;
$FILEUPLOAD = 3; $DWCAUPLOAD = 6;
if($uploadType == $FILEUPLOAD){
	$duManager = new SpecUploadFile();
	//if($filePath) ;
}
elseif($uploadType == $DWCAUPLOAD){
	$duManager = new SpecUploadDwca();
	$duManager->setIncludeIdentificationHistory($importIdent);
	$duManager->setIncludeImages($importImage);
	$duManager->setUploadType($uploadType);
	if($filePath) $duManager->setPath($filePath);
	//For now, assume DWCA import is a specfy database
	if(!$sourceType) $sourceType = 'specify';
}
else exit('ERROR: illegal upload type = '.$uploadType.' (should be 3 = File Upload, 6 = DWCA upload)');

$duManager->setVerboseMode(2);
if(!$securityKey){
	$errStr = 'ERROR: security key is required and is null';
	$duManager->outputMsg($errStr);
	exit($errStr);
}
if(!$duManager->validateSecurityKey($securityKey)){
	$errStr = 'ERROR: security key validation failed!';
	$duManager->outputMsg($errStr);
	exit($errStr);
}
if($sourceType) $duManager->setSourceDatabaseType($sourceType);

$ulPath = $duManager->uploadFile();
if(!$ulPath){
	$errStr = 'ERROR uploading file: '.$duManager->getErrorStr();
	exit($errStr);
}
$duManager->loadFieldMap(true);

if(!$duManager->analyzeUpload()) exit('ERROR analyzing upload file: '.$duManager->getErrorStr());
if(!$duManager->uploadData(false)) exit('ERROR uploading file: '.$duManager->getErrorStr());
$transferCnt = $duManager->getTransferCount();
$duManager->finalTransfer();

if($transferCnt > 0) echo 'Transfer successful: '.$transferCnt.' records transferred';
else echo 'FAILED: 0 records uploaded';
?>