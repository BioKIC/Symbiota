<?php
/*
------------------
Language: English
Translated by: Google Translate (2024-09-13)
------------------
*/

$LANG['OCC_EXP_MAN'] = 'Occurrence Export Manager';
$LANG['NEW_RECORDS_PROC_STATUS'] = 'New records cannot have an unprocessed or undefined processing status. Please select a valid processing status.';
$LANG['EXP_TYPE'] = 'Export Type';
$LANG['CUSTOM_EXP'] = 'Custom Export';
$LANG['GEO_EXP'] = 'Georeference Export';
$LANG['EXPORT_EXPLAIN'] = 'This download module is designed to aid collection managers in extracting specimen data				for import into local management or research systems.';
$LANG['MORE'] = 'more info';
$LANG['EXPORT_EXPLAIN_2'] = 'The export module is particularly useful for extracting data that has been added					using the digitization tools built into the web portal (crowdsourcing, OCR/NLP, basic data entry, etc).					Records imported from a local database are linked to the primary record					through a specimen unique identifier (barcode, primary key, UUID, etc).					This identifier is stored in the web portal database and gives collection managers the ability to update local records					with information added within the web portal.					New records digitized directly into the web portal (e.g. image to record data entry workflow) will have a null unique identifier,					which identifies the record as new and not yet synchronized to the central database.					When new records are extracted from the portal, imported into the central database,					and then the portal\'s data snapshot is refreshed, the catalog number will be used to automatically synchronized					the portal specimen records with those in the central database. Note that synchronization will only work if the primary identifier is					enforced as unique (e.g. no duplicates) within the local, central database.';
$LANG['EXPORT_BATCH_GEO'] = 'Export Batch Georeferenced Data';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_1'] = 'This module extracts coordinate data only for the records that have been georeferenced using the';
$LANG['BATCH_GEO_TOOLS'] = 'batch georeferencing tools';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_2'] = 'or the GeoLocate Community tools.					These downloads are particularly tailored for importing the new coordinates into their local database.					If no records have been georeferenced within the portal, the output file will be empty.';
$LANG['PROCESSING_STATUS'] = 'Processing Status';
$LANG['ALL_RECORDS'] = 'All Records';
$LANG['COMPRESSION'] = 'Compression';
$LANG['ARCHIVE_DATA_PACK'] = 'Archive Data Package (ZIP file)';
$LANG['FILE_FORMAT'] = 'File Format';
$LANG['CSV'] = 'Comma Delimited (CSV)';
$LANG['TAB_DELIMITED'] = 'Tab Delimited';
$LANG['CHAR_SET'] = 'Character Set';
$LANG['EXPORT_LACKING_GEO'] = 'Export Specimens Lacking Georeferencing Data';
$LANG['EXPORT_LACKING_GEO_EXPLAIN'] = 'This module extracts specimens that lack decimal coordinates or have coordinates that needs to be verified.					This download will result in a Darwin Core Archive containing a UTF-8 encoded CSV file containing					only georeferencing relevant data columns for the occurrences. By default, occurrences					will be limited to records containing locality information but no decimal coordinates.					This output is particularly useful for creating data extracts that will georeferenced using external tools.';
$LANG['COORDINATES'] = 'Coordinates';
$LANG['ARE_EMPTY'] = 'are empty (is null)';
$LANG['HAVE_VALUES'] = 'have values (e.g. need verification)';
$LANG['ADDITIONAL_FILTERS'] = 'Additional<br/>Filters';
$LANG['SELECT_FIELD'] = 'Select Field Name';
$LANG['DOWNLOAD_RECORDS'] = 'Download Records';
$LANG['DOWNLOAD_SPEC_RECORDS'] = 'Download Specimen Records';
$LANG['NEW_RECORDS_ONLY'] = 'New Records Only';
$LANG['EG_IN_PORTAL'] = '(e.g. records processed within portal)';
$LANG['MORE_INFO'] = 'More Information';
$LANG['MORE_INFO_TEXT'] = 'Limit to new records entered and processed directly within the					portal which have not yet imported into and synchonized with					the central database. Avoid importing unprocessed skeletal records since					future imports will involve more complex data coordination.';
$LANG['TRAIT_FILTER'] = 'Occurrence Trait<br/>Filter';
$LANG['OR_SPEC_ATTRIBUTE'] = 'OR select a specific Attribute State';
$LANG['HOLD_CTRL'] = 'Hold down the control (ctrl) or command button to select multiple options';
$LANG['STRUCTURE'] = 'Structure';
$LANG['SYMB_NATIVE'] = 'Symbiota Native';
$LANG['SYMB_NATIVE_EXPLAIN'] = 'Symbiota native is very similar to Darwin Core except with the addtion of a few fields					such as substrate, associated collectors, verbatim description.';
$LANG['DWC_EXPLAIN'] = 'Darwin Core is a TDWG endorsed exchange standard specifically for biodiversity datasets.					For more information, visit the <a href=';
$LANG['DATA_EXTENSIONS'] = 'Data Extensions';
$LANG['INCLUDE_DET'] = 'include Determination History';
$LANG['INCLUDE_IMAGES'] = 'include Media Records';
$LANG['INCLUDE_ATTRIBUTES'] = 'include Occurrence Trait Attributes (MeasurementOrFact extension)';
$LANG['OUTPUT_COMPRESSED'] = 'Output must be a compressed archive';
$LANG['ACCESS_DENIED'] = 'Access denied';
$LANG['INCLUDE_ASSOCIATIONS'] = 'include Resource Relationships (associations & linked resources)';

?>