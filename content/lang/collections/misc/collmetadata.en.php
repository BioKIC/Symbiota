<?php
/*
------------------
Language: English
------------------
*/

include_once($SERVER_ROOT.'/content/lang/collections/misc/sharedterms.en.php');

$LANG['ADD_SUCCESS'] = 'New collection added successfully';
$LANG['ADD_STUFF'] = 'Add contacts, resource links, or institution address below';
$LANG['COL_PROFS'] = 'Collection Profiles';
$LANG['LOADING'] = '<p>Loading...</p>';
$LANG['META_EDIT'] = 'Metadata Editor';
$LANG['CREATE_COLL'] = 'Create New Collection Profile';
$LANG['COL_META_EDIT'] = 'Collection Metadata Editor';
$LANG['CONT_RES'] = 'Contacts & Resources';
$LANG['COL_INFO'] = 'Collection Information';
$LANG['INST_CODE'] = 'Institution Code';
$LANG['MORE_INST_CODE'] = 'More information about Institution Code';
$LANG['NAME_ONE'] = 'The name (or acronym) in use by the institution having custody of the occurrence records. This field is required. For more details, see';
$LANG['DWC_DEF'] = 'Darwin Core definition';
$LANG['COLL_CODE'] = 'Collection Code';
$LANG['MORE_COLL_CODE'] = 'More information about Collection Code';
$LANG['NAME_ACRO'] = 'The name, acronym, or code identifying the collection or data set from which the record was derived. This field is optional. For more details, see';
$LANG['COLL_NAME'] = 'Collection Name';
$LANG['DESC'] = 'Description (2000 character max)';
$LANG['HOMEPAGE'] = 'Homepage';
$LANG['CONTACT'] = 'Contact';
$LANG['EMAIL'] = 'Email';
$LANG['LAT'] = 'Latitude';
$LANG['LONG'] = 'Longitude';
$LANG['MAP'] = 'Open map';
$LANG['MORE_INFO'] = 'Show more information';
$LANG['CATEGORY'] = 'Category';
$LANG['NO_CATEGORY'] = 'No Category';
$LANG['ALLOW_PUBLIC_EDITS'] = 'Allow Public Edits';
$LANG['MORE_PUB_EDITS'] = 'More information about Public Edits';
$LANG['EXPLAIN_PUBLIC'] = 'Checking public edits will allow any user logged into the system to modify specimen records
					and resolve errors found within the collection. However, if the user does not have explicit
					authorization for the given collection, edits will not be applied until they are
					reviewed and approved by collection administrator.';
$LANG['LICENSE'] = 'License';
$LANG['MORE_INFO_RIGHTS'] = 'More information about Rights';
$LANG['ORPHANED'] = 'orphaned term';
$LANG['LEGAL_DOC'] = 'A legal document giving official permission to do something with the resource.
					This field can be limited to a set of values by modifying the portal\'s central configuration file.
					For more details, see';
$LANG['RIGHTS_HOLDER'] = 'Rights Holder';
$LANG['MORE_INFO_RIGHTS_H'] = 'More information about Rights Holder';
$LANG['HOLDER_DEF'] = 'The organization or person managing or owning the rights of the resource.
					For more details, see';
$LANG['ACCESS_RIGHTS'] = 'Access Rights';
$LANG['MORE_INFO_ACCESS_RIGHTS'] = 'More information about Access Rights';
$LANG['ACCESS_DEF'] = 'Information or a URL link to page with details explaining
					how one can use the data. See';
$LANG['DATASET_TYPE'] = 'Dataset Type';
$LANG['PRES_SPECS'] = 'Preserved Specimens';
$LANG['OBSERVATIONS'] = 'Observations';
$LANG['PERS_OBS_MAN'] = 'Personal Observation Management';
$LANG['MORE_COL_TYPE'] = 'More information about Collection Type';
$LANG['COL_TYPE_DEF'] = 'Preserved Specimens signify a collection type that contains physical samples that are
						available for inspection by researchers and taxonomic experts. Use Observations when the record is not based on a physical specimen.
						Personal Observation Management is a dataset where registered users
						can independently manage their own subset of records. Records entered into this dataset are explicitly linked to the user&apos;s profile
						and can only be edited by them. This type of collection
						is typically used by field researchers to manage their collection data and print labels
						prior to depositing the physical material within a collection. Even though personal collections
						are represented by a physical sample, they are classified as &quot;observations&quot; until the
						physical material is publicly available within a collection.';
$LANG['MANAGEMENT'] = 'Management';
$LANG['SNAPSHOT'] = 'Snapshot';
$LANG['LIVE_DATA'] = 'Live Data';
$LANG['AGGREGATE'] = 'Aggregate';
$LANG['MORE_INFO_TYPE'] = 'More information about Management Type';
$LANG['SNAPSHOT_DEF'] = 'Use Snapshot when there is a separate in-house database maintained in the collection and the dataset
						within the Symbiota portal is only a periodically updated snapshot of the central database.
						A Live dataset is when the data is managed directly within the portal and the central database is the portal data.';
$LANG['GUID_SOURCE'] = 'GUID source';
$LANG['NOT_DEFINED'] = 'Not defined';
$LANG['MORE_INFO_GUID'] = 'More information about Global Unique Identifier';
$LANG['OCCURRENCE_ID'] = 'Occurrence Id';
$LANG['SYMB_GUID'] = 'Symbiota Generated GUID (UUID)';
$LANG['OCCID_DEF_1'] = 'Occurrence Id is generally used for
						Snapshot datasets when a Global Unique Identifier (GUID) field
						is supplied by the source database (e.g. Specify database) and the GUID is mapped to the';
$LANG['OCCURRENCEID'] = 'occurrenceId';
$LANG['OCCID_DEF_2'] = 'field. The use of the Occurrence Id as the GUID is not recommended for live datasets.
						Catalog Number can be used when the value within the catalog number field is globally unique.
						The Symbiota Generated GUID (UUID) option will trigger the Symbiota data portal to automatically
						generate UUID GUIDs for each record. This option is recommended for many for Live Datasets
						but not allowed for Snapshot collections that are managed in local management system.';
$LANG['PUBLISH_TO_AGGS'] = 'Publish to Aggregators';
$LANG['MORE_INFO_AGGREGATORS'] = 'More information about Publishing to Aggregators';
$LANG['ACTIVATE_GBIF'] = 'Activates GBIF publishing tools available within Darwin Core Archive Publishing menu option';
$LANG['SOURCE_REC_URL'] = 'Source Record URL';
$LANG['DYNAMIC_LINK_REC'] = 'Dynamic link to source database individual record page';
$LANG['MORE_INFO_SOURCE'] = 'More information about Source Records URL';
$LANG['ADVANCE_SETTING'] = 'Advance setting: Adding a
						URL template here will insert a link to the source record within the specimen details page.
						An optional URL title can be include with a colon delimiting the title and URL.
						For example, &quot;SEINet source record';
$LANG['ADVANCE_SETTING_2'] = 'will display the ID with the url pointing to the original
						record managed within SEINet. Or';
$LANG['ADVANCE_SETTING_3'] = 'can be used for an	iNaturalist import if you mapped their ID field as the source
						Identifier (e.g. dbpk) during import. Template patterns --CATALOGNUMBER--, --OTHERCATALOGNUMBERS--, and --OCCURRENCEID-- are additional options.';
$LANG['ICON_URL'] = 'Icon URL';
$LANG['WHAT_ICON'] = 'What is an Icon?';
$LANG['UPLOAD_ICON'] = 'Upload an icon image file or enter the URL of an image icon that represents the collection. If entering the URL of an image already located
						on a server, click on &quot;Enter URL&quot;. The URL path can be absolute or relative. The use of icons are optional.';
$LANG['ENTER_URL'] = 'Enter URL';
$LANG['UPLOAD_LOCAL'] = 'Upload Local Image';
$LANG['SORT_SEQUENCE'] = 'Sort Sequence';
$LANG['MORE_SORTING'] = 'More information about Sorting';
$LANG['LEAVE_IF_ALPHABET'] = 'Leave this field empty if you want the collections to sort alphabetically (default)';
$LANG['COLLECTION_ID'] = 'Collection ID (GUID)';
$LANG['MORE_INFO'] = 'More information';
$LANG['EXPLAIN_COLLID'] = 'Global Unique Identifier for this collection (see';
$LANG['DWC_COLLID'] = 'dwc:collectionID';
$LANG['EXPLAIN_COLLID_2'] = 'If your collection already has a previously assigned GUID, that identifier should be represented here.
						For physical specimens, the recommended best practice is to use an identifier from a collections registry such as the
						Global Registry of Biodiversity Repositories';
$LANG['SECURITY_KEY'] = 'Security Key';
$LANG['RECORDID'] = 'recordID';
$LANG['SAVE_EDITS'] = 'Save Edits';
$LANG['CREATE_COLL_2'] = 'Create New Collection';
$LANG['TINYMCE_INFO'] = 'Collection description. You can access the text editing toolbar for this description 
						by pressing key combination ALT + F10 on Windows or OPTION + F10 on MacOS.';

?>