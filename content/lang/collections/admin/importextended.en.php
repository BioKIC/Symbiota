<?php
/*
------------------
Language: English
------------------
*/


include_once($SERVER_ROOT . '/content/lang/collections/associations.en.php');

$LANG['IMPORT_EXTEND'] = 'Import Extended Data';
$LANG['IMPORT_FILE'] = 'Import file';
$LANG['IS_TOO_BIG'] = 'MB) is larger than is allowed (current limit';
$LANG['MAYBE_ZIP'] = ' Note that import file size can be reduced by compressing within a zip file.';
$LANG['SELECT_FILE'] = 'Select a file to import';
$LANG['SELECT_IMPORT_TYPE'] = 'Select an import type';
$LANG['SELECT_ASSOC_TYPE'] = 'Select an Association type';
$LANG['ERR_DUPLICATE_SOURCE'] = 'ERROR: Source field names must be unique (duplicate field:';
$LANG['ERR_DUPLICATE_TARGET'] = "ERROR: Can't map to the same target field more than once (";
$LANG['ERR_NOT_AUTH'] = 'ERROR: not authorized to access this page';
$LANG['ERR_COLL_NOT_VALID'] = 'ERROR: Collection identifier not valid';
$LANG['SUBJECT_ID_REQUIRED'] = 'ERROR: subject identifier required (catalogNumber, occurrenceID, otherCatalogNumbers)';
$LANG['OBJECT_ID_REQUIRED'] = 'ERROR: object identifier required (catalogNumber, occurrenceID, occid)';
$LANG['SELECT_RELATIONSHIP'] = 'Select a Relationship (required)';
$LANG['COLLECTION_MENU'] = 'Collection Management Menu';
$LANG['DATA_IMPORTER'] = 'Extended Data Importer';
$LANG['INSTRUCTIONS'] = 'Batch upload instructions and mapping details';
$LANG['IMAGE_URLS'] = 'Image URLs';
$LANG['ACTION_PANEL'] = 'Action Panel';
$LANG['STARTING_PROCESS'] = 'Starting to process input file';
$LANG['DONE_PROCESSING'] = 'Done process input file';
$LANG['FIELD_MAPPING'] = 'Field Mapping';

$LANG['BATCH_DELETE'] = 'Batch Delete';
$LANG['NEW_BLANK_RECORD'] = 'Link image to new blank record if catalog number does not exist';
$LANG['MATCHING_IDENTIFIERS'] = 'Update or delete records with matching identifiers (subject identifier plus objectID or resourceUrl)';
$LANG['IMPORT_DATA'] = 'Import Data';
$LANG['ERR_SETTING_IMPORT'] = 'ERROR setting import file';
$LANG['INITIALIZE_IMPORT'] = 'Initialize Import';
$LANG['IMPORT_TYPE'] = 'Import Type';
$LANG['ASSOCIATIONS'] = 'Associations';
$LANG['DETERMINATIONS'] = 'Determinations';
$LANG['IMAGE_FIELD_MAP'] = 'Image Field Map';
$LANG['MATERIAL_SAMPLE'] = 'Material Sample';
//Language fragments located within OccurrenceImport class file
$LANG['PROCESSING_CATNUM'] = 'Processing Identifier';
$LANG['UPDATING_STATS'] = 'Updating statistics';
$LANG['IMAGE_LOADED'] = 'Image loaded successfully';
$LANG['ASSOC_DELETED'] = 'Association deleted';
$LANG['ERROR_DELETING'] = 'ERROR deleting Occurrence Association';
$LANG['ASSOC_UPDATED'] = 'Association updated';
$LANG['ERROR_UPDATING'] = 'ERROR updating Occurrence Association';
$LANG['TARGET_NOT_FOUND'] = 'Update failed: association target record not found';
$LANG['ASSOC_ADDED'] = 'Association added';
$LANG['ERROR_ADDING'] = 'ERROR loading Occurrence Association';
$LANG['MAT_SAMPLE_ADDED'] = 'Material Sample added';
$LANG['DETERMINATION_ADDED'] = 'Determination added';
$LANG['CHOOSE_FILE'] = 'Choose a file';
$LANG['ASSOCIATION_TYPE'] = 'Association Type';
$LANG['HOME'] = 'Home';
?>