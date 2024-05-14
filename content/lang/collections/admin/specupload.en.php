<?php
/*
------------------
Language: English
------------------
*/
include_once($SERVER_ROOT . '/content/lang/collections/customsearchtype.en.php');

$LANG['SPEC_UPLOAD'] = 'Specimen Uploader';
$LANG['PATH_EMPTY'] = 'File path is empty. Please select the file that is to be loaded.';
$LANG['MUST_CSV'] = 'File must be comma separated (.csv), tab delimited (.txt or .tab), ZIP file (.zip), or a URL to an IPT Resource';
$LANG['IMPORT_FILE'] = 'Import File ';
$LANG['IS_BIGGER'] = 'MB) is larger than is allowed (current limit: ';
$LANG['MAYBE_ZIP'] = ' Note that import file size can be reduced by compressing within a zip file. ';
$LANG['ERR_UNIQUE_D'] = 'ERROR: Source field names must be unique (duplicate field: ';
$LANG['ERR_UNIQUE_ID'] = 'ERROR: Source field names must be unique (Identification: ';
$LANG['ERR_UNIQUE_IM'] = 'ERROR: Source field names must be unique (Image: ';
$LANG['SAME_TARGET_D'] = "ERROR: Can't map to the same target field more than once (";
$LANG['SAME_TARGET_ID'] = "ERROR: Can't map to the same target field more than once (Identification: ";
$LANG['SAME_TARGET_IM'] = "ERROR: Can't map to the same target field more than once (Images: ";
$LANG['NEED_CAT'] = 'ERROR: catalogNumber or otherCatalogNumbers is required for Skeletal File Uploads';
$LANG['SEL_MATCH'] = 'ERROR: select which identifier will be used for record matching (required for Skeletal File imports)';
$LANG['ID_NOT_MATCH'] = 'ERROR: identifier record matching does not match import fields (required for Skeletal File imports)';
$LANG['SEL_TAR_USER'] = 'Since this is a group managed observation project, you need to select a target user to which the occurrence will be linked';
$LANG['FIRST_ROW'] = 'Does the first row of the input file contain the column names? It appears that you may be mapping directly to the first row of active data rather than a header row. If so, the first row of data will be lost and some columns might be skipped. Select OK to proceed, or cancel to abort';
$LANG['ENTER_PROF'] = 'Review/modify profile name and click the Save Mapping button again to create the new profile';
$LANG['COL_MGMNT'] = 'Collection Management Panel';
$LANG['LIST_UPLOAD'] = 'List of Upload Profiles';
$LANG['UP_MODULE'] = 'Data Upload Module';
$LANG['CAUTION'] = 'Caution';
$LANG['REC_REPLACE'] = 'Matching records will be replaced with incoming records';
$LANG['NOT_REC'] = 'not recorded';
$LANG['UP_STATUS'] = 'Upload Status';
$LANG['PENDING_REPORT'] = 'Pending Data Transfer Report';
$LANG['OCCS_TRANSFERING'] = 'Occurrences pending transfer';
$LANG['PREVIEW'] = 'Preview 1st 1000 Records';
$LANG['DOWNLOAD_RECS'] = 'Download Records';
$LANG['RECORDS_UPDATED'] = 'Records to be updated';
$LANG['CAUTION_REPLACE'] = 'incoming records will replace existing records';
$LANG['MISMATCHED'] = 'Mismatched records';
$LANG['NEW_RECORDS'] = 'New records';
$LANG['FAILED_LINK'] = 'Records failed to link to records within this collection and will not be imported';
$LANG['MATCHING_CATALOG'] = 'Records matching on catalog number that will be appended';
$LANG['WARNING'] = 'WARNING';
$LANG['WARNING_DUPES'] = 'This will result in records with duplicate catalog numbers';
$LANG['RECS_SYNC'] = 'Records that will be synchronized with central database';
$LANG['EXPL_SYNC'] = 'These are typically records that have been originally processed within the portal, exported and integrated into a local management database, and then reimported and synchronized with the portal records by matching on catalog number';
$LANG['WARNING_REPLACE'] = 'Incoming records will replace portal records by matching on catalog numbers. Make sure incoming records are the most up to date!';
$LANG['NOT_MATCHING'] = 'Previous loaded records not matching incoming records';
$LANG['EXPECTED'] = 'Note: If you are doing a partial upload, this is expected';
$LANG['FULL_REFRESH'] = 'If you are doing a full data refresh, these may be records that were deleted within your local database but not within the portal.';
$LANG['NULL_RM'] = 'Records that will be removed due to NULL Primary Identifier';
$LANG['DUP_RM'] = 'Records that will be removed due to DUPLICATE Primary Identifier';
$LANG['IDENT_TRANSFER'] = 'Identification history count';
$LANG['IMAGE_TRANSFER'] = 'Image count';
$LANG['FINAL_TRANSFER'] = 'Are you sure you want to transfer records from temporary table to central specimen table?';
$LANG['TRANS_RECS'] = 'Transfer Records to Central Specimen Table';
$LANG['REC_START'] = 'Record Start';
$LANG['REC_LIM'] = 'Record Limit';
$LANG['MATCH_CAT'] = 'Match on Catalog Number';
$LANG['MATCH_ON_CAT'] = 'Match on Other Catalog Numbers';
$LANG['APPENDED'] = 'Incoming skeletal data will be appended only if targeted field is empty';
$LANG['BOTH_CATS'] = 'If both checkboxes are selected, matches will first be made on catalog numbers and secondarily on other catalog numbers';
$LANG['ID_SOURCE'] = 'Identify Data Source';
$LANG['IPT_URL'] = 'IPT Resource URL';
$LANG['RESOURCE_URL'] = 'Resource Path or URL';
$LANG['WORKAROUND'] = 'This option is for pointing to a data file that was manually uploaded to a server. This option offers a workaround for importing files that are larger than what is allowed by server upload limitations (e.g. PHP configuration limits)';
$LANG['DISPLAY_OPS'] = 'Display Additional Options';
$LANG['AUTOMAP'] = 'Automap Fields';
$LANG['ANALYZE_FILE'] = 'Analyze File';
$LANG['UNPROC'] = 'Unprocessed';
$LANG['STAGE_1'] = 'Stage 1';
$LANG['STAGE_2'] = 'Stage 2';
$LANG['STAGE_3'] = 'Stage 3';
$LANG['PEND_REV'] = 'Pending Review';
$LANG['EXP_REQ'] = 'Expert Required';
$LANG['PEND_NFN'] = 'Pending Review-NfN';
$LANG['SOURCE_ID'] = 'Source Unique Identifier / Primary Key';
$LANG['REQ'] = 'required';
$LANG['IMPORT_OCCS'] = 'Import Occurrence Records';
$LANG['VIEW_DETS'] = 'view details';
$LANG['UNVER'] = 'Unverified mappings are displayed in yellow';
$LANG['CUSTOM_FILT'] = 'Custom Occurrence Record Import Filters';
$LANG['FIELD'] = 'Field';
$LANG['SEL_FIELD'] = 'Select Field Name';
$LANG['COND'] = 'Condition';
$LANG['VALUE'] = 'Value';
$LANG['MULT_TERMS'] = 'Adding multiple terms separated by semi-colon will filter as an OR condition';
$LANG['IMPORT_ID'] = 'Import Identification History';
$LANG['UNVER'] = 'Unverified mappings are displayed in yellow';
$LANG['NOT_IN_DWC'] = 'not present in DwC-Archive';
$LANG['IMP_IMG'] = 'Import Images';
$LANG['RESET_MAP'] = 'Reset Field Mapping';
$LANG['NEW_PROF_TITLE'] = 'New profile title';
$LANG['TARGET_USER'] = 'Target User';
$LANG['SEL_TAR_USER'] = 'Select Target User';
$LANG['VER_LINKS'] = 'Verify image links';
$LANG['PROC_STATUS'] = 'Processing Status';
$LANG['NO_SETTING'] = 'Leave as is / No Explicit Setting';
$LANG['UNK_ERR'] = 'Unknown error analyzing upload';
$LANG['NFN_IMPORT'] = 'Notes from Nature File Import';
$LANG['START_UPLOAD'] = 'Start Upload';
$LANG['SEL_KEY'] = 'Select Source Primary Key';
$LANG['SKIPPED'] = 'Record will be skipped when all of the following fields are empty: catalogNumber, otherCatalogNumbers, occurrenceID, recordedBy (collector), eventDate, scientificName, dbpk';
$LANG['LEARN_MORE'] = 'To learn more about mapping to Symbiota fields (and Darwin Core)';
$LANG['LOADING_DATA'] = 'Loading Data into Symbiota';
$LANG['VER_MAPPING'] = 'Verify Mapping';
$LANG['SAVE_MAP'] = 'Save Mapping';
$LANG['VERSION_DATA_CHANGES'] = 'Version data changes';
$LANG['VER_LINKS_MEDIA'] = 'Verify image links from associatedMedia field';
$LANG['SKEL_EXPLAIN'] = 'Skeletal Files consist of stub data that is easy to capture in bulk during the imaging process.
	This data is used to seed new records to which images are linked.
	Skeletal fields typically collected include filed by or current scientific name, country, state/province, and sometimes county, though any supported field can be included.
	Skeletal file uploads are similar to regular uploads though differ in several ways.';
$LANG['SKEL_EXPLAIN_P1'] = 'General file uploads typically consist of full records, while skeletal uploads will almost always be an annotated record with data for only a few selected fields';
$LANG['SKEL_EXPLAIN_P2'] = 'The catalog number field is required for skeletal file uploads since this field is used to find matches on images or existing records';
$LANG['SKEL_EXPLAIN_P3'] = 'In cases where a record already exists, a general file upload will completely replace the existing record with the data in the new record.
	On the other hand, a skeletal upload will augment the existing record only with new field data. Fields are only added if data does not already exist within the target field.';
$LANG['SKEL_EXPLAIN_P4'] = 'If a record DOES NOT already exist, a new record will be created in both cases, but only the skeletal record will be tagged as unprocessed';
$LANG['NOT_AUTH'] = 'ERROR: you are not authorized to upload to this collection';
$LANG['PAGE_ERROR'] = 'ERROR: Either you have tried to reach this page without going through the collection management menu or you have tried to upload a file that is too large.
	You may want to breaking the upload file into smaller files or compressing the file into a zip archive (.zip extension).
	You may want to contact portal administrator to request assistance in uploading the file (hint to admin: increasing PHP upload limits may help, current upload_max_filesize';
$LANG['USE_BACK'] = 'Use the back arrows to get back to the file upload page.';
$LANG['UPLOAD'] = 'Upload File';

?>