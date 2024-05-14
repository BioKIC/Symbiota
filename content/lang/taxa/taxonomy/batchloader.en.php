<?php
/*
------------------
Language: English
------------------
*/
$LANG['TAXA_LOADER'] = 'Taxa Loader';
$LANG['ENTER_PATH'] = 'Please enter a path value of the file you wish to upload';
$LANG['UPLOAD_ZIP'] = 'Upload file must be a CSV or ZIP file';
$LANG['SEL_KINGDOM'] = 'Select Target Kingdom';
$LANG['ERROR_SOURCE_DUP'] = 'ERROR: Source field names must be unique (duplicate field:';
$LANG['ERROR_TARGET'] = "ERROR: Can't map to the same target field more than once";
$LANG['ENTER_TAX_NODE'] = 'Please enter a valid taxonomic node';
$LANG['SEL_THESAURUS'] = 'Please select the target taxonomic thesaurus';
$LANG['PLS_SEL_KINGDOM'] = 'Please select the target kingdom';
$LANG['SEL_AUTHORITY'] = 'Please select a taxonomic authority that will be used to harvest';
$LANG['HOME'] = 'Home';
$LANG['BASIC_TREE_VIEWER'] = 'Basic Tree Viewer';
$LANG['DYN_TREE_VIEWER'] = 'Dynamic Tree Viewer';
$LANG['TAX_BATCH_LOADER'] = 'Taxa Batch Loader';
$LANG['TAX_NAME_BATCH_LOADER'] = 'Taxonomic Name Batch Loader';
$LANG['TAX_UPLOAD_EXPLAIN1'] = 'This page allows a Taxonomic Administrator to batch upload taxonomic data files. See';
$LANG['SYMB_DOC'] = 'Symbiota Documentation';
$LANG['TAX_UPLOAD_EXPLAIN2'] = 'pages for more details on the Taxonomic Thesaurus layout.';
$LANG['TAX_UPLOAD'] = 'Taxa Upload';
$LANG['SOURCE_FIELD'] = 'Source Field';
$LANG['TARGET_FIELD'] = 'Target Field';
$LANG['FIELD_UNMAPPED'] = 'Field Unmapped';
$LANG['LEAVE_UNMAPPED'] = 'Leave Field Unmapped';
$LANG['YELLOW_FIELDS'] = 'Fields in yellow have not yet been verified';
$LANG['TARGET_KINGDOM'] = 'Target Kingdom';
$LANG['TARGET_THESAURUS'] = 'Target Thesaurus';
$LANG['VERIFY_MAPPING'] = 'Verify Mapping';
$LANG['UPLOAD_TAXA'] = 'Upload Taxa';
$LANG['TRANSFER_TO_CENTRAL'] = 'Transfer Taxa To Central Table';
$LANG['REVIEW_BEFORE_ACTIVATE'] = 'Review upload statistics below before activating. Use the download option to review and/or adjust for reload if necessary.';
$LANG['TAXA_UPLOADED'] = 'Taxa uploaded';
$LANG['TOTAL_TAXA'] = 'Total taxa';
$LANG['INCLUDES_PARENTS'] = 'includes new parent taxa';
$LANG['TAXA_IN_THES'] = 'Taxa already in thesaurus';
$LANG['NEW_TAXA'] = 'New taxa';
$LANG['ACCEPTED_TAXA'] = 'Accepted taxa';
$LANG['NON_ACCEPTED_TAXA'] = 'Non-accepted taxa';
$LANG['PROBLEM_TAXA'] = 'Problematic taxa';
$LANG['TAXA_FAILED'] = 'These taxa are marked as FAILED and will not load until problems have been resolved. You may want to download the data (link below), fix the bad relationships, and then reload.';
$LANG['STATS_NOT_AVAIL'] = 'Upload statistics are unavailable';
$LANG['ACTIVATE_TAXA'] = 'Activate Taxa';
$LANG['DOWNLOAD_CSV'] = 'Download CSV Taxa File';
$LANG['TAX_UPLOAD_SUCCESS'] = 'Taxa upload appears to have been successful';
$LANG['GO_TO'] = 'Go to';
$LANG['TAX_TREE_SEARCH'] = 'Taxonomic Tree Search';
$LANG['TO_QUERY'] = 'page to query for a loaded name';
$LANG['ACTION_PANEL'] = 'Action Panel';
$LANG['RESULT_TARGETS'] = 'Result Targets';
$LANG['TARGET_TAXON'] = 'Target taxon';
$LANG['KINGDOM'] = 'Kingdom';
$LANG['LOWEST_RANK'] = 'Lowest rank limit';
$LANG['SOURCE_LINK'] = 'Source link';
$LANG['TOTAL_RESULTS'] = 'Total results';
$LANG['ID'] = 'ID';
$LANG['ERROR'] = 'ERROR';
$LANG['NAME'] = 'Name';
$LANG['DATSET_KEY'] = 'Dataset key';
$LANG['STATUS'] = 'Status';
$LANG['ACC_TO'] = 'According to';
$LANG['SCRUTINIZER'] = 'Scrutinizer';
$LANG['NOT_PREF'] = 'not preferred';
$LANG['PREF_TARGET'] = 'preferred target';
$LANG['TARGET_STATUS'] = 'Target status';
$LANG['WEB_SERVICE_URL'] = 'Web Service URL';
$LANG['API_URL'] = 'API URL';
$LANG['COL_URL'] = 'CoL URL';
$LANG['IMPORT_THIS_NODE'] = 'Import this node and child taxa';
$LANG['NO_VALID_COL'] = 'ERROR: no valid CoL targets returned';
$LANG['TAXA_LOADED_SUCCESS'] = 'taxa within the target node have been loaded successfully';
$LANG['TAX_UPLOAD_INSTRUCTIONS'] = 'Flat structured, CSV (comma delimited) text files can be uploaded here.
							Scientific name is the only required field below genus rank.
							However, family, author, and rankid (as defined in taxonunits table) are always advised.
							For upper level taxa, parents and rankids need to be included in order to build the taxonomic hierarchy.
							Large data files can be compressed as a ZIP file before import.
							If the file upload step fails without displaying an error message, it is possible that the
							file size exceeds the file upload limits set within your PHP installation (see your php configuration file).';
$LANG['FULL_FILE_PATH'] = 'Full File Path';
$LANG['FULL_FILE_EXPLAIN'] = 'This option is for manual upload of a data file. Enter full path to data file located on working server.';
$LANG['MAP_INPUT_FILE'] = 'Map Input File';
$LANG['TOGGLE_MANUAL'] = 'Toggle Manual Upload Option';
$LANG['CLEAN_ANALYZE'] = 'Clean and Analyze';
$LANG['CLEAN_ANALYZE_EXPLAIN'] = 'If taxa information was loaded into the UploadTaxa table using other means, one can use this form to clean and analyze taxa names in preparation to loading into the taxonomic tables (taxa, taxstatus).';
$LANG['API_NODE_LOADER'] = 'API Node Loader';
$LANG['API_NODE_LOADER_EXPLAIN'] = 'This form will batch load a taxonomic node from a selected Taxonomic Authority via their API resources.<br/>
							This function currently only works for Catalog of Life and WoRMS.';
$LANG['TAX_RESOURCE'] = 'Taxonomic Resource';
$LANG['TARGET_NODE'] = 'Target node';
$LANG['ANALYZE_TAXA'] = 'Analyze Taxa';
$LANG['TAX_THESAURUS'] = 'Taxonomic Thesaurus';
$LANG['ALL_RANKS'] = 'All Taxon Ranks';
$LANG['LOAD_NODE'] = 'Load node';
$LANG['NO_PERMISSIONS'] = 'You do not have permissions to batch upload taxonomic data';

?>
