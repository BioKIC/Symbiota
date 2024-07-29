<?php
/*
------------------
Language: English
------------------
*/

// duplicateharvest.php

$LANG['DUP_GEOREFERENCE'] = 'Duplicate Georeferencing';
$LANG['COL_MNG'] = 'Collection Management';
$LANG['BATCH_HARVEST_DUP'] = 'Batch harvesting from Duplicates';
$LANG['STAGING_VARIABLES'] = 'Staging Variables';
$LANG['TARGET_FIELDS'] = 'Target Fields';
$LANG['ALL_FIELDS'] = 'All fields';
$LANG['GEO_FIELDS'] = 'Georeference fields';
$LANG['MATCH_METHOD'] = 'Matching method';
$LANG['DUP_SPEC_TABLES'] = 'Duplicate specimen tables';
$LANG['EXS_TABLES'] = 'Exsiccatae tables';
$LANG['REC_NOT_EVAL_SINCE'] = 'Records not evaluated since';
$LANG['PROC_STATUS'] = 'Processing status';
$LANG['ALL_RECS'] = 'All Records';
$LANG['STAGE_1'] = 'Stage 1';
$LANG['STAGE_2'] = 'Stage 2';
$LANG['STAGE_3'] = 'Stage 3';
$LANG['UNPROCESSED'] = 'Unprocessed';
$LANG['BUILD_LIST'] = 'Build List';
$LANG['REC_LIMIT'] = 'Record limit';
$LANG['COLL_CODE'] = 'Collection<br/>Code';
$LANG['CAT_BR_NUM'] = 'Catalog<br/>Number';
$LANG['NOT_AUTH'] = 'You are not authorized to access this page';

// imageprocessor.php

$LANG['IMG_PROCESSOR'] = 'Image Processor';
$LANG['SEL_IMPORT_TYPE'] = 'Image Mapping/Import type must be selected';
$LANG['USE_DEFAULT_PATH'] = "-- Use Default Path --";
$LANG['SEL_CSV'] = 'Select a CSV file to upload';
$LANG['CSV_OR_ZIP'] = 'Input file must be a comma-delimited file (CSV) or ZIP file containing a CSV file';
$LANG['NEED_PATTERN_MATCH'] = 'Pattern matching term must have a value';
$LANG['CATNUM_IN_PARENS'] = 'Catalog portion of pattern matching term must be enclosed in parenthesis';
$LANG['WEB_IMG_NUMERIC'] = 'Web-sixed image width can only be a numeric value';
$LANG['TN_IMG_NUMERIC'] = 'Thumbnail image width can only be a numeric value';
$LANG['LG_IMG_NUMERIC'] = 'Large image width can only be a numeric value';
$LANG['TITLE_NOT_EMPTY'] = 'Title cannot be empty';
$LANG['JPG_BETWEEN'] = 'JPG compression needs to be a numeric value between 30 and 100';
$LANG['PROC_DATE_FORMAT'] = 'Processing Start Date needs to be in the format YYYY-MM-DD (e.g. 2023-02-14)';
$LANG['CHECK_MATCH_TERM'] = 'At least one of the Match Term checkboxes need to be checked';
$LANG['TARGET_MUST_UNIQUE'] = 'ERROR: Target field names must be unique (duplicate field';
$LANG['SOURCE_MUST_UNIQUE'] = 'ERROR: Source field names must be unique (duplicate field';
$LANG['MUST_MAP_CATNUM'] = 'Catalog Number or Other Catalog Numbers must be mapped to an import field';
$LANG['LARGE_URL_MAPPED'] = 'Large Image URL must both be mapped to an import field';
$LANG['IMG_PROCESSOR_EXPLAIN'] = 'These tools are designed to aid collection managers in batch processing specimen images. Contact portal manager for help in setting up a new workflow.
				 Once a profile is established, the collection manager can use this form to manually trigger image processing. For more information, see the Symbiota documentation for
				 <b><a href="https://biokic.github.io/symbiota-docs/coll_manager/images/batch/" target="_blank">recommended practices</a></b> for integrating images.';
$LANG['IMG_FILE_UPLOAD_MAP'] = 'Image File Upload Map';
$LANG['SOURCE_FIELD'] = 'Source Field';
$LANG['TARGET_FIELD'] = 'Target Field';
$LANG['SEL_TARGET_FIELD'] = 'Select Target Field';
$LANG['CAT_NUM'] = 'Catalog Number';
$LANG['OTHER_CAT_NUMS'] = 'Other Catalog Numbers';
$LANG['LG_IMG_URL'] = 'Large Image URL (required)';
$LANG['WEB_IMG_URL'] = 'Web Image URL';
$LANG['TN_URL'] = 'Thumbnail URL';
$LANG['SOURCE_URL'] = 'Source URL';
$LANG['LINK_BLANK_RECORD'] = 'Link image to new blank record if catalog number does not exist';
$LANG['MAP_IMGS'] = 'Map Images';
$LANG['SAVED_PROCESSING_PROF'] = 'Saved Image Processing Profiles';
$LANG['EDIT'] = 'Edit';
$LANG['NEW'] = 'New';
$LANG['PROFILE'] = 'Profile';
$LANG['CLOSE_EDITOR'] = 'Close Editor';
$LANG['PROC_TYPE'] = 'Processing Type';
$LANG['MAP_FROM_SERVER'] = 'Map Images from a Local or Remote Server';
$LANG['URL_MAP_FILE'] = 'Image URL Mapping File';
$LANG['TITLE'] = 'Title';
$LANG['PATT_MATCH_TERM'] = 'Pattern match term';
$LANG['MORE_INFO'] = 'More Information';
$LANG['PATTERN_EXPLAIN'] = 'Regular expression needed to extract the unique identifier from source text.
							For example, regular expression /^(WIS-L-\d{7})\D*/ will extract catalog number WIS-L-0001234
							from image file named WIS-L-0001234_a.jpg. For more information on creating regular expressions,
							search the internet for &quot;Regular Expression PHP Tutorial&quot;';
$LANG['REPLACEMENT_TERM'] = 'Replacement term';
$LANG['OPTIONAL'] = 'Optional';
$LANG['PATT_REPLACE_EXPLAIN'] = "Optional regular expression for match on Catalog Number to be replaced with replacement term.
								Example 1: expression replace term = '/^/' combined with replace string = 'barcode-' will convert 0001234 => barcode-0001234.
								Example 2: expression replace term = '/XYZ-/' combined with empty replace string will convert XYZ-0001234 => 0001234.";
$LANG['REPLACE_EXPLAIN'] = 'Optional replacement string to apply for Expression replacement term matches on catalogNumber.';
$LANG['IMG_SOURCE_PATH'] = 'Image source path';
$LANG['IMG_TARGET_PATH'] = 'Image target path';
$LANG['TARGET_PATH_EXPLAIN'] = "Web server path to where the image derivatives will be depositied.
							The web server (e.g. apache user) must have read/write access to this directory.
							If this field is left blank, the portal's default image target (\$IMAGE_ROOT_PATH) will be used.";
$LANG['IMG_URL_BASE'] = 'Image URL base';
$LANG['IMG_URL_EXPLAIN'] = "Image URL prefix that will access the target folder from the browser.
							This will be used to create the image URLs that will be stored in the database.
							If absolute URL is supplied without the domain name, the portal domain will be assumed.
							If this field is left blank, the portal's default image url will be used (\$IMAGE_ROOT_URL).";
$LANG['WEB_IMG_WIDTH'] = 'Web-sized image width';
$LANG['WEB_IMG_EXPLAIN'] = 'Width of the standard web image in pixels. If the source image is smaller than this width, the file will simply be copied over without resizing.';
$LANG['TN_IMG_WIDTH'] = 'Thumbnail image width';
$LANG['TN_IMG_EXPLAIN'] = 'Width of the image thumbnail in pixels. Width should be greater than image sizing within the thumbnail display pages.';
$LANG['LG_IMG_WIDTH'] = 'Large image width';
$LANG['LG_IMG_EXPLAIN'] = 'Width of the large version of the image in pixels.
							If the source image is smaller than this width, the file will simply be copied over without resizing.
							Note that resizing large images may be limited by the PHP configuration settings (e.g. memory_limit).
							If this is a problem, having this value greater than the maximum width of your source images will avoid
							errors related to resampling large images.';
$LANG['JPG_QUALITY'] = 'JPG quality';
$LANG['JPG_QUALITY_EXPLAIN'] = 'JPG quality refers to amount of compression applied.
								Value should be numeric and range from 0 (worst quality, smaller file) to
								99 (best quality, biggest file). Do not use 100; it will erroneously increase the size of your image.
								If null, 75 is used as the default.';
$LANG['THUMBNAIL'] = 'Thumbnail';
$LANG['CREATE_NEW_TN'] = 'Create new thumbnail from source image';
$LANG['IMPORT_TN_SOURCE'] = 'Import thumbnail from source location (source name with _tn.jpg suffix)';
$LANG['MAP_TN_AT_SOURCE'] = 'Map to thumbnail at source location (source name with _tn.jpg suffix)';
$LANG['EXCLUDE_TN'] = 'Exclude thumbnail';
$LANG['LG_IMG'] = 'Large Image';
$LANG['IMPORT_LG_SOURCE'] = 'Import source image as large version';
$LANG['MAP_TO_LG_SOURCE'] = 'Map to source image as large version';
$LANG['IMPORT_LG_FROM_SOURCE'] = 'Import large version from source location (source name with _lg.jpg suffix)';
$LANG['MAP_LG_AT_SOURCE'] = 'Map to existing large version (source name with _lg.jpg suffix)';
$LANG['EXCLUDE_LG'] = 'Exclude large version';
$LANG['SEL_URL_MAP_FILE'] = 'Select URL mapping file';
$LANG['CHOOSE_FILE'] = 'Choose File';
$LANG['SAVE_PROFILE'] = 'Save Profile';
$LANG['SURE_DELETE_PROF'] = 'Are you sure you want to delete this image processing profile?';
$LANG['DELETE_PROJ'] = 'Delete Project';
$LANG['DELETE_PROF'] = 'Delete Profile';
$LANG['SHOW_ALL_OR_ADD'] = 'Show all saved profiles or add a new one...';
$LANG['OPEN_EDITOR'] = 'Open Editor';
$LANG['NO_RUN_DATE'] = 'no run date';
$LANG['LAST_RUN_DATE'] = 'Last Run Date';
$LANG['PROC_START_DATE'] = 'Processing start date';
$LANG['REPLACEMENT_STR'] = 'Replacement string';
$LANG['SOURCE_PATH'] = 'Source path';
$LANG['TARGET_FOLDER'] = 'Target folder';
$LANG['URL_PREFIX'] = 'URL prefix';
$LANG['WEB_IMG'] = 'Web Image';
$LANG['EVALUATE_IMPORT_SOURCE'] = 'Evaluate and import source image';
$LANG['IMPORT_WITHOUT_RESIZE'] = 'Import source image as is without resizing';
$LANG['MAP_SOURCE_NO_IMPORT'] = 'Map to source image without importing';
$LANG['UNABLE_MATCH_ID'] = 'Unable to match primary identifer with an existing database record';
$LANG['MISSING_RECORD'] = 'Missing record';
$LANG['SKIP_AND_NEXT'] = 'Skip image import and go to next';
$LANG['CREATE_AND_LINK'] = 'Create empty record and link image';
$LANG['IMG_EXISTS'] = 'Image already exists';
$LANG['SKIP_IMPORT'] = 'Skip import';
$LANG['RENAME_SAVE_BOTH'] = 'Rename image and save both';
$LANG['REPLACE_EXISTING'] = 'Replace existing image';
$LANG['LOOK_FOR_SKELETAL'] = 'Look for and process skeletal files (allowed extensions: csv, txt, tab, dat)';
$LANG['SKIP_SKELETAL'] = 'Skip skeletal files';
$LANG['PROCESS_SKELETAL'] = 'Process skeletal files';
$LANG['COLLID_NOT_DEFINED'] = 'ERROR: collection identifier not defined. Contact administrator';
$LANG['LOG_FILES'] = 'Log Files';
$LANG['GEN_PROCESSING'] = 'General Processing';
$LANG['IPLANT'] = 'iPlant (pre-CyVerse)';
$LANG['CYVERSE'] = 'CyVerse';
$LANG['IMG_MAP_FILE'] = 'Image Mapping File';
$LANG['NO_LOGS'] = 'No logs exist for this collection';

// nlpprocessor.php
$LANG['NLP_PROCESSOR'] = 'NLP Processor';
$LANG['UNPROCESSED_SPECS'] = 'Unprocessed Specimens';
$LANG['UNPROCESSED_SPECS_NO_IMGS'] = 'Unprocessed SpecimensUnprocessed Specimens without Images';
$LANG['UNPROCESSED_SPECS_NO_OCR'] = 'Unprocessed Specimens without OCR';
$LANG['NO_UNPROCESSED'] = 'There are no unprocessed records to';
$LANG['UNIDENTIFIED_ERROR'] = 'Unidentified Error';

// ocrprocessor.php
$LANG['PLS_SEL_PROC_STATUS'] = 'Please select a processing status';
$LANG['ENTER_PATT_MATCH'] = 'Please enter a pattern matching string for extracting the catalog number';
$LANG['SEL_OCR_INPUT'] = 'Please select/enter an OCR input source file';
$LANG['UPLOAD_MUST_ZIP'] = 'Upload file must be a ZIP file with a .zip extension';
$LANG['SPEC_IMG_STATS'] = 'Specimen Image Statistics';
$LANG['TOTAL_W_IMGS'] = 'Total specimens with images';
$LANG['SPEC_W_IMGS'] = 'specimens with images';
$LANG['W_OCR'] = 'with OCR';
$LANG['WO_OCR'] = 'without OCR';
$LANG['CUSTOM_QUERY'] = 'Custom Query';
$LANG['SEL_PROC_STATUS'] = 'Select Processing Status';
$LANG['NO_STATUS'] = 'No Status';
$LANG['RESET_STATS'] = 'Reset Statistics';
$LANG['BATCH_OCR_IMGS'] = 'Batch OCR Images using the Tesseract OCR Engine';
$LANG['PROC_STATUS'] = 'Processing Status';
$LANG['UNPROCESSED'] = 'unprocessed';
$LANG['NUM_RECORDS_PROCESS'] = 'Number of records to process';
$LANG['RUN_BATCH_OCR'] = 'Run Batch OCR';
$LANG['TESSERACT_DEPEND'] = 'Note: This feature is dependent on the proper installation of the Tesseract OCR Engine on the hosting server';
$LANG['NO_TESSERACT'] = 'The Tesseract OCR engine does not appear to be installed or the tesseractPath variable is not set within the Symbiota configuration file. ';
$LANG['CONTACT_SYSADMIN'] = 'Contact your system administrator to resolve these issues.';
$LANG['OCR_IMPORT_TOOL'] = 'OCR Batch Import Tool';
$LANG['OCR_IMPORT_EXPLAIN'] = 'This interface will upload OCR text files generated outside of the portal environment.
				For instance, ABBYY FineReader has the ability to batch OCR specimen images and output the results as separate text files (.txt) named after the source image.
				OCR text files are linked to specimen records by matching catalog numbers extracted from the file name and comparing OCR and image file names.';
$LANG['REQS'] = 'Requirements';
$LANG['REQ1'] = 'OCR files must be in a text format with a .txt extension. When using ABBYY, use the setting: "Create a separate document for each file", "Save as Text (*.txt)", and "Name as source file"';
$LANG['REQ2'] = 'Compress multiple OCR text files into a single zip file to be uploaded into the portal';
$LANG['REQ3'] = 'Files must be named using the Catalog Number. The regular expression below will be used to extract catalog number from file name. Click information symbol for more information.';
$LANG['REQ4'] = 'Since OCR text needs to be linked to source image, images must have been previously uploaded into portal';
$LANG['REQ5'] = 'If there are more than one image linked to a specimen, the full file name will be used to determine which image to link the OCR';
$LANG['REGEX'] = 'Regular Expression';
$LANG['REGEX_EXPLAIN'] = 'Regular expression needed to extract the unique identifier from source text.
						For example, regular expression /^(WIS-L-\d{7})\D*/ will extract catalog number WIS-L-0001234
						from image file named WIS-L-0001234_a.jpg. For more information on creating regular expressions,
						search the internet for "Regular Expression PHP Tutorial". It is recommended to have the portal manager
						help with the initial setup of batch processing.';
$LANG['ZIP_W_OCR'] = 'Zip file containing OCR';
$LANG['TOGGLE_FULL_PATH'] = 'toggle option to enter full path';
$LANG['FULL_PATH'] = 'full path option';
$LANG['BROWSE_SEL_ZIP'] = 'Browse and select zip file that contains the multiple OCR text files.';
$LANG['SOURCE_PATH_EXPLAIN'] = 'File path or URL to folder containing the OCR text files.
								If a URL (e.g. http://) is supplied, the web server needs to be configured to list
								all files within the directory, or the html output needs to list all images in anchor tags.
								Scripts will attempt to crawl through all child directories.';
$LANG['OCR_SOURCE'] = 'OCR Source';
$LANG['OCR_SOURCE_EXPLAIN'] = 'Short string describing OCR Source (e.g. ABBYY, Tesseract, etc). This value is placed in source field with current date appended.';
$LANG['LOAD_OCR_FILES'] = 'Load OCR Files';


?>
