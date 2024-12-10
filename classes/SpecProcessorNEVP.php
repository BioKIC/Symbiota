<?php
/**
 * This file contains code to read an rdf/xml file that uses OA, OAD, and dwcFP
 * as used in the New England Vascular Plants TCN.  For each OA:Annotation, this
 * code extracts the dwc and dwcFP occurrence data and populate PDOs reflecting
 * Symbiota's Occurrence and Determination tables.
 *
 * The purpose of this file is to allow Symbiota records to be created from
 * NEVP New Occurrence Annotation files uploaded with image links.
 *
 * For an example new occurrence annotation in the expected form see:
 * http://sourceforge.net/p/filteredpush/svn/2221/tree/FP-Design/trunk/ontologies/oa/NEVP_specimen_example.xml
 *
 * Example invocation:
 *
 * $xml = XMLReader::open($file);
 * if ($xml->read()) {
 *    $hasAnnotation = $xml->lookupNamespace("oa");
 *    $hasDataAnnotation = $xml->lookupNamespace("oad");
 *    $hasdwcFP = $xml->lookupNamespace("dwcFP");
 *    // Note: contra the PHP xmlreader documentation, lookupNamespace
 *    // returns the namespace string not a boolean.
 *    if ($xml->name=="rdf:XML" && $hasAnnotation && $hasDataAnnotation && $hasdwcFP) {
 *       $processor = new NEVPProcessor();
 *       $result = $processor->process($file);
 *    }
 * }
 *
 * @author Paul J. Morris
 */

include_once("$SERVER_ROOT/classes/OmOccurrences.php");
include_once("$SERVER_ROOT/classes/OmOccurDeterminations.php");
if (file_exists("$SERVER_ROOT/classes/Gazeteer.php")) {
   include_once("$SERVER_ROOT/classes/Gazeteer.php");
}
include_once("$SERVER_ROOT/classes/iPlantUtility.php");

define ("DEFAULT_NEVP_COUNTRY","United States of America");

// Check to see if included classes match the current schema version.
$uptodate = true;
$testOcc = new OmOccurrences();
if (!$testOcc->checkSchema()) {
   echo "[Warning: classes/OmOccurrences.php does not support the Symbiota schema version.]\n";
   $uptodate = false;
}
$testOccDet = new OmOccurDeterminations();
if (!$testOccDet->checkSchema()) {
   echo "[Warning: classes/OmOccurDeterminations.php does not support the Symbiota schema version.]\n";
   $uptodate = false;
}
if (!$uptodate) {
   echo "[Warning: Ingest of data may fail.  Contact a Symbiota developer.]\n";
}

if (!class_exists('Result')) {
   /**
    * To record and sumarize the results of processing an xml file.
    *
    * @author mole
    *
    */
    class Result {
        public $couldparse = false; // document compliant with expectations
	    public $success = false; // completed without errors
	    public $errors = "";     // error messages
        public $recordcount = 0;  // number of core records encountered
	    public $successcount = 0; // number of records successfully processed
	    public $failurecount = 0; // number of records with errors in processing
	    public $insertcount = 0; // number of records successfully inserted
	    public $updatecount = 0; // number of records successfully updated
        public $imagecount = 0 ;  // number of images records encountered
        public $imagefailurecount = 0 ;  // number of images with errors in processing
        public $imageinsertcount = 0 ;  //  number of images inserted
        public $imageupdatecount = 0 ;  //  number of images updated

        function __construct() {
           $couldparse = false; // document compliant with expectations
	       $success = false; // completed without errors
	       $errors = "";     // error messages
           $recordcount = 0;  // number of core records encountered
	       $successcount = 0; // number of records successfully processed
	       $failurecount = 0; // number of records with errors in processing
	       $insertcount = 0; // number of records successfully inserted
	       $updatecount = 0; // number of records successfully updated
           $imagecount = 0 ;  // number of images records encountered
           $imagefailurecount = 0 ;  // number of images with errors in processing
           $imageinsertcount = 0 ;  //  number of images inserted
           $imageupdatecount = 0 ;  //  number of images updated
        }
    }
}

$result = new Result();

class EVENT {
   public $eventdate;
   public $verbatimeventdate;
   public $startday;
   public $startmonth;
   public $startyear;

   public function setEventDate($date) {
      $this->eventdate = $date;
      if (strlen($date)>3) { $this->startyear = substr($date,0,4);  }
      if (strlen($date)>6) { $this->startmonth = substr($date,5,2); }
      if (strlen($date)>9) { $this->startday = substr($date,8,2);   }
   }

   public function write() {
      return "$this->verbatimeventdate [$this->eventdate]";
   }
}

class IDENTIFICATION {
   public $occid;
   public $family;
   public $taxonrank;
   public $scientificname;
   public $genus;
   public $specificepithet;
   public $identificationqualifier;
   public $scientificnameauthorship;
   public $infraspecificrank;
   public $infraspecificepithet;
   public $infraspecificauthor;
   public $nomenclaturalcode = "ICNafp";
   public $identifiedby;
   public $dateidentified;
   public $typestatus;
   public $isfiledundernameincollection;
   public $taxonid;
   public $taxonguid;

   public function write() {
     $date = "";
     if ($this->dateidentified!= null) { $date = $this->dateidentified->writeAll(); }
     $id = new OmOccurDeterminations();
     $id->keyvalueset('occid',$this->occid);
     $id->keyvalueset('sciname',$this->scientificname);
     if (strlen($this->infraspecificauthor)>0) {
         $id->keyvalueset('scientificNameAuthorship',$this->infraspecificauthor);
     } else {
         $id->keyvalueset('scientificNameAuthorship',$this->scientificnameauthorship);
     }
     $code ="";
     if ($this->nomenclaturalcode!=null && strlen($this->nomenclaturalcode)>0) {
        $code = "[" + $this->nomenclaturalcode + "]";
     }
     $id->keyvalueset('identificationRemarks',$this->typestatus + $code);
     $id->keyvalueset('identifiedBy',$this->identifiedby);
     $id->keyvalueset('dateIdentified',$date);
     $id->keyvalueset('identificationQualifier',trim($this->identificationqualifier));
     // TODO: Handle taxonid in authorityfile

     $id->save();
   }

   public function getNameWithoutAuthor() {
      $result = "";
      if (strpos($this->scientificname,$this->scientificnameauthorship)===FALSE) {
           // scientificName doesn't contain authorship
     	   $result = $this->scientificname;
      } else {
           // scientificName does contain authorship, remove
           $result = trim(substr($this->scientificname,0,strpos($this->scientificname,$this->scientificnameauthorship)-1));
      }
      return $result;
   }

   public function getNameWithAuthor() {
      $result = "";
      if (strpos($this->scientificname,$this->scientificnameauthorship)===FALSE) {
           // scientificName doesn't contain authorship, add
     	   $result = trim($this->scientificname . " " . $this->scientificnameauthorship);
      } else {
           $result = $this->scientificname;
      }
      return $result;
   }

}

class ANNOTATION {
   public $id;
   public $expectation;
   public $annotatedat;
   public $motivations = array();
   public $annotator;
   public $serializer;
   public $serializedat;
}

class ANNOTATOR {
  public $mbox_sha1sum;
  public $name;
  public $workplacehomepage;
}

class OCCURRENCE {
   public $occurrenceid = null;
   public $basisofrecord = "PreservedSpecimen";
   public $collectioncode = null;
   public $collectionid;
   public $institutioncode = null;
   public $catalognumber;
   public $datelastmodified;
   public $identifications = array();
   public $recordedby;
   public $collectorid;
   public $recordnumber; // collectornumber
   public $country = null;
   public $stateprovince;
   public $county;
   public $municipality;
   public $locality = null;
   public $notes;
   public $minimumelevationinmeters = null;
   public $maximumelevationinmeters = null;
   public $collectingevent = null;
   public $datemodified;
   public $storagelocation;
   public $associatedmedia = array();
   public $recordenteredby;
   public $containingDocument = null;
   public $fundingsource = null;

   public function getFiledUnderID() {
        $result = null;
        foreach($this->identifications as $id) {
           if ($result==null) {
              $result = $id;
           }
           if ($id->isfiledundernameincollection==$this->collectioncode) {
              $result = $id;
           }
        }
        return $result;
   }

   public function write() {
       global $createNewRec, $result, $debug;
       // set up writer class instances
       $occ = new OmOccurrences();  // Note: We may create a new instance below.

       $det = new OmOccurDeterminations();

       // Special case handling of HUH double barcode errors
       $cc = $this->collectioncode;
       if ($cc=='A' || $cc=='GH' || $cc=='FH' || $cc=='AMES' || $cc=='ECON' || $cc=='NEVP') {
          // Known cases of sheets where a new barcode number was added in addition to an existing number for the sheet.
          // original existing number is the correct number.
          if ($this->catalognumber=='00447650') { $this->catalognumber='00354371'; $this->occurrenceid = '4b11b05c-03f3-4f72-a917-a4a48fc2fb33'; }
          if ($this->catalognumber=='00447657') { $this->catalognumber='00354391'; $this->occurrenceid = '96dc15ca-6224-4d63-9039-9633cafca7be'; }
          if ($this->catalognumber=='00448806') { $this->catalognumber='00348333'; $this->occurrenceid = '1c2e1397-614a-44c0-bfa4-1edc1d4bb455'; }
          if ($this->catalognumber=='00448807') { $this->catalognumber='00348334'; $this->occurrenceid = 'b5551dfc-53b4-4792-96bc-d66d748f2de2'; }
          if ($this->catalognumber=='00448821') { $this->catalognumber='00348335'; $this->occurrenceid = '251eb221-b8f8-4d30-a3b7-fafb067262e5'; }
          if ($this->catalognumber=='00448775') { $this->catalognumber='00348331'; $this->occurrenceid = '032c0d22-78b2-4c8e-88f6-9ef6638fb707'; }
          if ($this->catalognumber=='00448797') { $this->catalognumber='00348332'; $this->occurrenceid = 'ba20e76f-4d97-4943-b150-b7141b9b24e3'; }
          if ($this->catalognumber=='00448506') { $this->catalognumber='00415842'; $this->occurrenceid = '1688968c-dd07-4483-b20c-6137d465443a'; }
          if ($this->catalognumber=='00448485') { $this->catalognumber='00415838'; $this->occurrenceid = '591ddf2e-b92a-46a7-85de-63f628688598'; }
       }
       // End Special case
       if ($debug) { echo "Preparing to save [$cc][$this->catalognumber]"; }
       $collid = $occ->lookupCollId($this->institutioncode, $this->collectioncode);
       $collreportmatch = "collid=[$collid], institutionCode=[$this->institutioncode], collectionCode=[$this->collectioncode]\n";
       // Record exists if there is a matching DarwinCore triplet.
       $exists = $occ->loadByDWCTriplet($this->institutioncode, $this->collectioncode, $this->catalognumber);
       if ($debug) { echo "Exists=[$exists]"; }
       if (!$exists) {
           // Image upload process in TcnImageTools creates omoccurrences records where
           // collectioncode is not populated, but collid is. Need to handle both cases.
           $exists = $occ->loadByCollidCatalog($collid, $this->catalognumber);
           // Guidance from Ed: omoccurrences.institutioncode and omoccurrences.collectioncode should only
           // be populated if different from the values in omcollections for the omoccurrences.collid.
       }
       if (!$exists) {
       	  $occ = new OmOccurrences();
          $occ->setcollid($collid);
          $occ->setcollectionCode($this->collectioncode);
          $occ->setcollectionID($this->collectionid);
          $occ->setinstitutionCode($this->institutioncode);
          $occ->setcatalogNumber($this->catalognumber);
          $occ->setprocessingStatus("unprocessed");
          // Provide for link to authoritative database record (which doesn't yet exist).
          // extract just the uuid from the urn:uuid:{uuid} string.
          $matches = explode(':',$this->occurrenceid);
          if (count($matches)==3) {
             $uuid = $matches[2];
             if ($debug) { echo "extracted uuid=[$uuid]"; }
             $occ->setdbpk($uuid);
          } else {
             // if the expected uuid wasn't found, use the catalog number as the best available proxy for dbbk
             $occ->setdbpk($this->catalognumber);
          }
          // if creating a new occurrence record, assume it was created by the annotator on the dc:created date.
          $occ->setrecordenteredby($this->recordenteredby);
          $occ->setdateentered($this->datemodified);
          $occ->setstorageLocation($this->storagelocation);  // NEVP ingest
          $occ->setfundingSource($this->fundingsource);      // NEVP ingest
          $occ->setdocumentGuid($this->containingDocument->guid);
          $occ->setdocumentDate($this->containingDocument->date);
       }
       $occ->setoccurrenceId($this->occurrenceid);
       $occ->setbasisOfRecord($this->basisofrecord);

       // Separators in name strings may be amperstand (HUH standard for name pairs), semicolon, or pipe (AppleCore).
       // convert all to semicolon and split on that.
       $this->recordedby = str_replace("|",";",$this->recordedby);
       $this->recordedby = str_replace("&",";",$this->recordedby);
       if (strpos($this->recordedby,";")>0) {
           // split on first semicolon.
           $occ->setrecordedBy(trim(substr($this->recordedby,0,strpos($this->recordedby,";"))));
           $occ->setassociatedCollectors(trim(substr($this->recordedby,strpos($this->recordedby,";")+1,strlen($this->recordedby))));
       } else {
           $occ->setrecordedBy(trim($this->recordedby));
           $occ->setassociatedCollectors("");
       }
       $occ->setcollectorid($this->collectorid);
       if ($this->recordnumber!=null) { $occ->setrecordNumber($this->recordnumber); }
       $occ->settypeStatus($this->getTypeStatusList());
       if ($this->country==null) {
          $occ->setcountry(DEFAULT_NEVP_COUNTRY);
       } else {
          $occ->setcountry($this->country);
       }
       if ($this->stateprovince!=null) { $occ->setstateProvince($this->stateprovince); }
       if ($this->county!=null) { $occ->setcounty($this->county); }
       $occ->setmunicipality($this->municipality);
       if ($this->locality!=null) { $occ->setlocality($this->locality); }
       if ($this->collectingevent != null) {
          // Symbiota event date is a mysql date field, thus less
          // expressive than an ISO date field, but also has
          // startdayofyear and enddayofyear fields for handling ranges within a year.
          //
          // Pass off responsibility for parsing range to OmOccurrences implementation.
          $occ->seteventDate($this->collectingevent->eventdate);
          $occ->setyear($this->collectingevent->startyear);
          $occ->setmonth($this->collectingevent->startmonth);
          $occ->setday($this->collectingevent->startday);
          $occ->setverbatimEventDate($this->collectingevent->verbatimeventdate);
       }
       if (class_exists('Gazeteer')) {
          $gazeteer = new Gazeteer();
          // For NEVP, lookup georeference from state+municipality
          $georef = $gazeteer->lookup($this->stateprovince, $this->municipality,$occ->getyear());
          if ($georeference->foundMatch===true) {
             $occ->setdecimalLatitude($georef->latitude);
             $occ->setdecimalLongitude($georef->longitude);
             $occ->setcoordinateUncertaintyInMeters($georef->errorRadius);
             $occ->setgeodeticDatum($georef->geodeticDatum);
             $occ->setgeoreferencedBy($georef->georeferencedBy);
             $occ->setgeoreferenceProtocol($georef->georeferenceProtocol);
             $occ->setgeoreferenceSources($georef->georeferenceSources);
          }
       }
       if ($this->minimumelevationinmeters!=null) { $occ->setminimumElevationInMeters($this->minimumelevationinmeters); }
       if ($this->maximumelevationinmeters!=null) { $occ->setmaximumElevationInMeters($this->maximumelevationinmeters); }
       $filedUnder = $this->getFiledUnderID();
       if ($filedUnder!=null) {
           $occ->setsciname($filedUnder->getNameWithoutAuthor());  // without author
           $occ->setscientificName($filedUnder->getNameWithAuthor());  // with author
           // Set locality security based on TID of accepted name of filed under name
           $sectid = $det->lookupAcceptedTID($filedUnder->scientificname);
           if ($sectid==null) {
              $sectid = $det->lookupTID($filedUnder->scientificname);
           }
           if ($sectid==null) {
              $occ->setlocalitySecurity(0);
           } else {
               $occ->setlocalitySecurity($det->checkSecurityStatus($sectid));
           }

           if ($det->lookupAcceptedTID($filedUnder->scientificname)>0) {
               $occ->settidinterpreted($det->lookupAcceptedTID($filedUnder->scientificname));
           }
           $occ->setfamily($filedUnder->family);
           if (strlen($occ->getfamily())==0) {
               $occ->setfamily($det->lookupFamilyForTID($det->lookupTID($filedUnder->scientificname)));
           }
           $occ->setgenus($filedUnder->genus);
           $occ->setspecificEpithet($filedUnder->specificepithet);
           $occ->setinfraSpecificEpithet($filedUnder->infraspecificepithet);
           $occ->setidentificationQualifier($filedUnder->identificationqualifier);
           $occ->setscientificNameAuthorship($filedUnder->scientificnameauthorship);
           $occ->settaxonGuid($filedUnder->taxonguid);
           // work out value for taxon rank.
           $occ->settaxonRank($filedUnder->infraspecificrank);
           if (strlen($filedUnder->infraspecificepithet)>0 && strlen($filedUnder->infraspecificrank)==0) {
                $occ->settaxonRank("subspecies");
           }
           if (strlen($filedUnder->infraspecificepithet)==0 && strlen($filedUnder->specificepithet)>0) {
                $occ->settaxonRank("species");
           }
           if (strlen($filedUnder->infraspecificepithet)==0 && strlen($filedUnder->specificepithet)==0 && strlen($filedUnder->genus)>0) {
                $occ->settaxonRank("genus");
           }
           if ($filedUnder->dateidentified!=NULL) {
               $occ->setdateIdentified($filedUnder->identificationdate->writeAll());
           }
       }
       // TODO: Handle motivations (transcribing and NSF abstract numbers).
       // NSF abstract number handled here with fundingsource (but not
       // implemented in symbiota).
       // motivations extracted, but nothing done with here.

       if ($debug) { echo ",createnew=[$createNewRec]\n"; }
       if (!$exists || $createNewRec==1) {
           // if record exists, then TcnImageConf variable createNewRec specifies policy for update.
           $saveresult = $occ->save();
           $occid = $occ->getoccid();
           if ($debug) { echo "occid=[$occid],saveresult=[$saveresult]\n"; }
           if ($saveresult===TRUE) {
               if (preg_match('/Barcode .* exists\./',$occ->errorMessage())) {
                   $result->updatecount++;
               } else {
               if ($exists) {
                    if ($debug) { echo "Updated occid: [".$occ->getoccid()."]\n"; }
                   $result->updatecount++;
               } else {
                    if ($debug) { echo "Added occid: [".$occ->getoccid()."]\n"; }
                   $result->insertcount++;
               }
               foreach($this->identifications as $id) {
                    $id->occid = $occ->getoccid();
                    $id->write();
               }
               }
               // Create image records from associatedmedia array.
               foreach($this->associatedmedia as $media) {
                  $sourceUrl = null;
                  $imgWebUrl = null;
                  $sourceID = null;
                  $imgWebID = null;
                  $mediaguid = $media->guid;
                  if (is_array($media->accesspoints))  {
                     foreach($media->accesspoints as $accesspoint) {
                        if (strlen($accesspoint->accessURI)>0) {
                           $irodsPath = str_replace('/','\/',$accesspoint->accessURI);
                           $irodsPath = preg_replace('/^file:/','irods:',$irodsPath);
                           $imageresult = getiPlantID($accesspoint->accessURI,$irodsPath);
                           if ($imageresult->statusok===FALSE) {
                              echo "Error: " . $imageresult->error . "\n";
                              $result->imagefailurecount++;
                           } else {
                              if ($debug) { echo "[$accesspoint->format]"; }
                              $iPlantID = $imageresult->resource_uniq;
                              if ($accesspoint->format=="dng") {
                                 // Original dng file
                                 $sourceUrl = "https://bisque.cyverse.org/image_service/image/$iPlantID";
                                 $sourceID = $iPlantID;
                              }
                              if ($accesspoint->format=="jpg") {
                                 // Preconstructed derivative JPG file
                                 $imgWebUrl = "https://bisque.cyverse.org/image_service/image/$iPlantID?resize=1250&format=jpeg";
                                 $imgWebID = $iPlantID;
                                 $imgTnUrl = "https://bisque.cyverse.org/image_service/image/$iPlantID?thumbnail=225,225";
                                 // Because this is a JPEG, no need to request ?rotate=guess&format=jpeg,quality,100
                                 // and the folks at iPlant are requesting that this request be made without the
                                 // un-needed transformation parameters.
                                 $imgLgUrl = "https://bisque.cyverse.org/image_service/image/$iPlantID";
                              }
                           }
                        } // end if accesspoint has accessURL
                     } // end for each accesspoint
                  } // end isarray
                  // Workaround to handle new case of only a JPEG being provided by some insitutions
                  if ($sourceUrl==null && $imgLgUrl!=null) {
                      // a jpeg was provided, but not a DNG, use the large version of the jpeg as the source.
                      $sourceUrl = $imgLgUrl;
                  }
                  if ($sourceUrl!=null && $imgWebUrl!=null) {
                     // found something to save.
                     $result->imagecount++;
                     $tid = $det->lookupTID($filedUnder->scientificname);
                     $caption = "$this->collectioncode $this->catalognumber $filedUnder->scientificname";
                     $locality = $occ->getcountry() ." $this->stateprovince $this->county $this->municipality $this->locality";
                     $sortsequence = 50;  // default number
                     $copyright = $media->rights;
                     $owner = $media->owner;
                     $notes = $media->usageterms;
                     $imgid = $this->getImgIDForSourceURL($sourceUrl);
                     if ($imgid=="") {
                        // add this image record
   	                    $isaveresult = $this->databaseImageRecord($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$this->recordenteredby,null,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortsequence,"specimen","",$sourceID,$copyright,"");
                        if ($isaveresult=="") {
                           $result->imageinsertcount++;
                        } else {
                           $result->imagefailurecount++;
                        }
                     } else {
   	                    $isaveresult = $this->updateImageRecord($imgid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$this->recordenteredby,null,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortsequence,"specimen","",$sourceID,$copyright,"");
                        if ($isaveresult=="") {
                           $result->imageupdatecount++;
                        } else {
                           $result->imagefailurecount++;
                        }
                     }
                     if ($debug) { echo "imagesaveresult=[$isaveresult]\n"; }
                  }
               }

               $result->successcount++;
           } else {
               $result->errors .= "Error in ". $this->collectioncode  ."-".  $this->catalognumber .": [" . $occ->errorMessage() . "]\n";
               if ($occ->errorMessage()=="Cannot add or update a child row: a foreign key constraint fails (`symbiota`.`omoccurrences`, CONSTRAINT `FK_omoccurrences_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE)") {
                   $result->errors .= "Interpretation: Record contains a collectionCode and institutionCode combination which was not found in omcollections (or only a collectionCode that was not found in omcollections or omoccurrences). \n";
                   $result->errors .= $collreportmatch;
               }
               $result->failurecount++;
           }
       } else {
           echo "Skipping, record exists and specified policy is to not update. [".$occ->getoccid()."]\n";
       }


   }

   /**
    * Update an existing record into the image table.
    * author: Paul J. Morris
    *
    * return: an empty string on success, otherwise a string containing an error message.
    */
   private function updateImageRecord($imgid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$creator,$creatorUid,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortSequence,$imagetype,$anatomy, $sourceIdentifier, $rights, $accessRights){
   	$status = "";
   	$sql = 'update media set tid=?, url=?, thumbnailurl=?, originalurl=?, creator=?, creatorUid=?, caption=?, '.
     	'owner=?, sourceurl=?, copyright=?, locality=?, occid=?, notes=?, username=?, sortsequence=?, imagetype=?, anatomy=?, '.
     	'sourceIdentifier=?, rights=?, accessrights=? '.
     	'where mediaID = ? ';
   	if ($statement = $this->conn->prepare($sql)) {
   		//If central images are on remote server and new ones stored locally, then we need to use full domain
   		//e.g. this portal is sister portal to central portal
   		if($GLOBALS['MEDIA_DOMAIN']){
   			$urlPrefix = $this->getDomainUrl();
   			if(substr($imgWebUrl,0,1) == '/'){
   				$imgWebUrl = $urlPrefix.$imgWebUrl;
   			}
   			if(substr($imgTnUrl,0,1) == '/'){
   				$imgTnUrl = $urlPrefix.$imgTnUrl;
   			}
   			if(substr($imgLgUrl,0,1) == '/'){
   				$imgLgUrl = $urlPrefix.$imgLgUrl;
   			}
   		}

   		$statement->bind_param("issssisssssississsssi",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$creator,$creatorUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy, $sourceIdentifier, $rights, $accessRights, $imgid);

   		$statement->execute();
   		$rows = $statement->affected_rows;
   		if ($rows!==1) {
   			$status = $statement->error;
   		}
   		$statement->close();
   	} else {
   		$status = mysqli_error($this->conn);
   		// Likely case for error conditions if schema changes affect field names
   		// or if updates to field list produce incorrect sql.
   		$this->errArr[] = $status;
   	}
   	if($status!=""){
   		$status = "loadImageData: $status<br/>SQL: ".$sql;
   	}
   	return $status;
   }

   /**
    * Insert a record into the image table.
    * author: Paul J. Morris
    *
    * return: an empty string on success, otherwise a string containing an error message.
    */
   private function databaseImageRecord($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$phototrapher,$creatorUid,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortSequence,$imagetype,$anatomy,$sourceIdentifier,$rights,$accessRights){
   	$status = "";
   	$sql = 'INSERT INTO media (tid, url, thumbnailurl, originalurl, creator, creatorUid, caption, '.
     	'owner, sourceurl, copyright, locality, occid, notes, username, sortsequence, imagetype, anatomy, '.
     	'sourceIdentifier, rights, accessrights, mediaType) '.
     	'VALUES (?,?,?,?,?,?,? ,?,?,?,?,?,?,?,?,?,? ,?,?,?, "image")';
   	if ($statement = $this->conn->prepare($sql)) {
   		//If central images are on remote server and new ones stored locally, then we need to use full domain
   		//e.g. this portal is sister portal to central portal
   		if($GLOBALS['MEDIA_DOMAIN']){
   			$urlPrefix = $this->getDomainUrl();
   			if(substr($imgWebUrl,0,1) == '/'){
   				$imgWebUrl = $urlPrefix.$imgWebUrl;
   			}
   			if(substr($imgTnUrl,0,1) == '/'){
   				$imgTnUrl = $urlPrefix.$imgTnUrl;
   			}
   			if(substr($imgLgUrl,0,1) == '/'){
   				$imgLgUrl = $urlPrefix.$imgLgUrl;
   			}
   		}
   		$statement->bind_param("issssisssssississsss",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$creator,$creatorUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy, $sourceIdentifier, $rights, $accessRights);

   		$statement->execute();
   		$rows = $statement->affected_rows;
   		if ($rows!==1) {
   			$status = $statement->error;
   		}
   		$statement->close();
   	} else {
   		$status = mysqli_error($this->conn);
   		// Likely case for error conditions if schema changes affect field names
   		// or if updates to field list produce incorrect sql.
   		$this->errArr[] = $status;
   	}
   	if($status!=""){
   		$status = "loadImageData: $status<br/>SQL: ".$sql;
   	}
   	return $status;
   }

   /**
    * Given a sourceURI, return the first imgid if at least one images record exists with
    * that sourceURI.
    * @author Paul J. Morris
    * @param sourceUrl the images.sourceurl to query for.
    * @returns an empty string if no images records have the provided sourceURL, otherwise,
    * the images.imgid for the first encountered images record with that sourceURL.
    */
   private function getImgIDForSourceURL($sourceUrl) {
   	$result = "";
   	$sql = "select mediaID from media where sourceurl = ? order by mediaID limit 1 ";
   	if ($statement = $this->conn->prepare($sql)) {
   		$statement->bind_param("s",$sourceUrl);
   		$statement->execute();
   		$statement->bind_result($result);
   		$statement->fetch();
   		$statement->close();
   	}
   	return $result;
   }

   private function getDomainUrl(){
   	$domainPath = "http://";
   	if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domainPath = "https://";
   	$domainPath .= $_SERVER["SERVER_NAME"];
   	if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80 && $_SERVER['SERVER_PORT'] != 443) $domainPath .= ':'.$_SERVER["SERVER_PORT"];
   	return $domainPath;
   }

   public function addIdentification($id) {
      $this->identifications[] = clone $id;
   }

   public function getTypeStatusList() {
      $result = "";
      $statusValues = array();
      $comma = "";
      foreach ($this->identifications as $id) {
          if(strlen($id->typestatus)>1) {
             if (!array_key_exists($id->typestatus,$statusValues)) {
                $statusValues[$id->typestatus]=1;
                $result = $comma . $id->typestatus;
                $comma = ",";
             }
          }
      }
      return $result;
   }


}

class ASSOCIATEDMEDIA {
   public $guid;
   public $accesspoints;
   public $rights;
   public $owner;
   public $usageterms;
}

class ACCESSPOINT {
   public $variant;
   public $accessURI;
   public $format;
   public $hashFunction;
   public $hashValue;
}

class DOCUMENT {
   public $guid = null;
   public $date = null;

}

class NEVPProcessor {

    public $currentAnnotation = null;
    public $currentAnnotator = null;
    public $cureentMedia = null;
    public $cureentAP = null;
    public $currentOcc = null;
    public $currentId = null;
    public $currentDate = null;
    public $currentDocument = null;
    public $currentSerializer = null;
    public $currentTag = "";
    public $acount = 0;       // number of annotations expected
    public $countfound = 0;  // number of annotations found

    function startElement($parser, $name, $attrs) {
        global $depth, $currentOcc, $currentTag, $currentId, $currentDate, $currentAnnotator, $currentAnnotation, $currentMedia, $currentAP,$currentDocument,$currentSerializer;
        $currentTag = $name;

        // echo "[$name]";
        switch ($name) {
         case "RDF:DESCRIPTION":
             // There are multiple RDF:Descriptions, but the first one has the document guid.
             if ($this->currentDocument==null)  {
                $this->currentDocument = new Document();
                $this->currentDocument->guid = $attrs['RDF:ABOUT'];
             }
             break;
           case "OA:ANNOTATION":
              $currentAnnotation = new ANNOTATION();
              $currentAnnotation->id = $attrs['RDF:ABOUT'];
              break;
           case "OAD:EXPECTATION_INSERT":
              $currentAnnotation->expectation = $name;
              break;
           case "OAD:EXPECTATION_UPDATE":
              $currentAnnotation->expectation = $name;
              break;
           case "OAD:TRANSCRIBING":
           case "OA:TRANSCRIBING":
              $currentAnnotation->motivations[] = $name;
              break;
           case "OA:MOTIVATEDBY":
              if (@strlen($attrs['RDF:RESOURCE'])>0) {
                  $currentAnnotation->motivations[] = $attrs['RDF:RESOURCE'];
              }
              break;
           case "OA:ANNOTATEDBY":
              $currentAnnotator = new ANNOTATOR();
              break;
           case "OA:SERIALIZEDBY":
              $currentSerializer = new ANNOTATOR();
              break;
           case "DWCFP:OCCURRENCE":
           case "DWCFP:OCCURENCE":
              $currentOcc = new OCCURRENCE();
              $currentOcc->occurrenceid .= $attrs['RDF:ABOUT'];
              break;
           case "DWCFP:IDENTIFICATION":
              $currentId = new IDENTIFICATION();
              break;
           case "DWCFP:TAXON":
              $currentId->taxonguid .= $attrs['RDF:ABOUT'];
              break;
           case "DWCFP:HASCOLLECTOR":
              $currentOcc->collectorid .= $attrs['RDF:RESOURCE'];
              break;
           case "DWCFP:EVENT":
              $currentDate = new EVENT();
              break;
           case "DWCFP:HASCOLLECTIONBYID":
              $currentOcc->collectionid = $attrs['RDF:RESOURCE'];
              break;
           case "DWCFP:HASASSOCIATEDMEDIA":
              $currentMedia = new ASSOCIATEDMEDIA();
              break;
           case "DCMITYPE:IMAGE":
              $currentMedia->guid = $attrs['RDF:ABOUT'];
              break;
           case "AC:HASACCESSPOINT":
              $currentAP = new ACCESSPOINT();
              break;
           case "DWCFP:HASBASISOFRECORD":
              $basis = $attrs['RDF:RESOURCE'];
              if (strpos($basis,'http://')!==false) {
                  $bits = explode('/',$basis);
                  $basis = $bits[count($bits)-1];
              }
              $currentOcc->basisofrecord = $basis;
              break;
           case "VIVO:HASFUNDINGVEHICLE":
              $currentOcc->fundingsource = $attrs['RDF:RESOURCE'];
              break;
        }

        if (!in_array((int) $parser,$depth)) {
          $depth[(int) $parser] = 0;
        }
        $depth[(int) $parser]++;
    }

    function endElement($parser, $name) {
        global $depth, $currentOcc, $currentId, $currentDate, $result, $currentAnnotator, $currentAnnotation, $currentMedia, $currentAP,$currentSerializer, $currentDocument;
        $depth[(int) $parser]--;

        switch ($name) {
           case "DWCFP:OCCURRENCE":
           case "DWCFP:OCCURENCE":
              // Nothing to do, assuming one occurrence per annotation.
              $result->recordcount++;
              break;
           case "DWCFP:IDENTIFICATION":
              $currentOcc->addIdentification($currentId);
              $currentId = null;
              break;
           case "DWCFP:HASCOLLECTINGEVENT":
              $currentOcc->collectingevent = clone $currentDate;
              $currentDate = null;
              break;
           case "OA:ANNOTATION":
              $this->countfound++;
              $currentOcc->recordedby = trim($currentOcc->recordedby);
              $currentOcc->recordenteredby=$currentAnnotation->annotator->name;
              $currentOcc->containingDocument = clone $this->currentDocument;
              $currentOcc->write();
              $currentOcc = null;
              break;
           case "OA:ANNOTATEDBY":
              $currentAnnotation->annotator = $currentAnnotator;
              $currentAnnotator = null;
              break;
           case "OA:SERIALIZEDBY":
              $currentAnnotation->serializer = $currentSerializer->name;
              $currentSerializer = null;
              break;
           case "DWCFP:HASASSOCIATEDMEDIA":
              $currentOcc->associatedmedia[] = clone $currentMedia;
              $currentMedia = null;
              break;
           case "AC:HASACCESSPOINT":
              $currentMedia->accesspoints[] = clone $currentAP;
              $currentAP = null;
              break;
        }
    }

    function value($parser, $data) {
       global $depth, $currentOcc, $currentTag, $currentId, $currentDate, $currentAnnotator, $currentAnnotation, $currentMedia,$currentAP,$currentDocument,$currentSerializer, $debug;

// Top level of document: [RDF:RDF][RDF:DESCRIPTION][RDFS:COMMENT][RDFS:COMMENT][CO:COUNT]
// Annotation: [OA:ANNOTATION][OA:HASTARGET][OA:SPECIFICRESOURCE][OA:HASSELECTOR][OAD:KVPAIRQUERYSELECTOR][DWC:COLLECTIONCODE][DWC:INSTITUTIONCODE][OA:HASSOURCE][OA:HASBODY]
// New Occurrence: [DWCFP:OCCURRENCE][DC:TYPE][DWCFP:HASBASISOFRECORD][DWC:CATALOGNUMBER][DWCFP:HASCOLLECTIONBYID][DWC:COLLECTIONCODE][DWCFP:HASIDENTIFICATION][DWCFP:IDENTIFICATION][DWCFP:ISFILEDUNDERNAMEINCOLLECTION][DWC:SCIENTIFICNAME][DWC:GENUS][DWC:SPECIFICEPITHET][DWCFP:INFRASPECIFICRANK][DWC:INFRASPECIFICEPITHET][DWC:SCIENTIFICNAMEAUTHORSHIP][DWC:IDENTIFICATIONQUALIFIER][DWCFP:USESTAXON][DWCFP:TAXON][DWCFP:HASTAXONID][DWC:RECORDEDBY][DWC:RECORDNUMBER][DWCFP:HASCOLLECTINGEVENT][DWCFP:EVENT][DWC:EVENTDATE][DWC:VERBATIMEVENTDATE][DWC:COUNTRY][DWC:STATEPROVINCE][DWC:COUNTY][DWC:MUNICIPALITY][DC:CREATED][DWC:MODIFIED][OBO:OBI_0000967][DWCFP:HASASSOCIATEDMEDIA]
// Annotation, following occurrence in body: [OAD:HASEVIDENCE][OAD:HASEXPECTATION][OAD:EXPECTATION_INSERT][OA:MOTIVATEDBY][OAD:TRANSCRIBING][OA:MOTIVATEDBY][OA:ANNOTATEDBY][FOAF:PERSON][OA:ANNOTATEDAT][OA:SERIALIZEDBY][FOAF:AGENT][FOAF:NAME][OA:SERIALIZEDAT]
// [DC:TYPE] -- nowhere to put in symbiota
// [DWCFP:HASIDENTIFICATION] -- nowhere to put in symbiota
       if ($data!=null) { $data = trim($data); }
       switch ($currentTag) {
         case "CO:COUNT":
             if (strlen($data)>0) {
                $this->acount = $data;
             }
             break;
         case "DC:CREATED":
             if ($this->currentDocument->date==null) {
                $this->currentDocument->date = substr($data,0,10);
                if ($debug) { echo "date=[$this->currentDocument->date]"; }
             }
             break;


         case "DWC:INSTITUTIONCODE":
             $currentOcc->institutioncode .= $data;
             break;
         case "DWC:COLLECTIONCODE":
             $currentOcc->collectioncode .= $data;
             break;
         case "DWC:CATALOGNUMBER":
             $currentOcc->catalognumber .= $data;
             break;
         case "DC:CREATED":
             $currentOcc->datelastmodified .= $data;
             break;
         case "DWC:RECORDEDBY":
             if ($currentOcc->recordedby=="") {
                $currentOcc->recordedby .= $data;
             } else {
                if (substr($data,0,1)=="&") {
                   // add a space back in if removed by parser
                   $currentOcc->recordedby .= " $data ";
                } else {
                   $currentOcc->recordedby .= $data;
                }
             }
             break;
         case "DWC:RECORDNUMBER":
             $currentOcc->recordnumber .= $data;
             break;
         case "DWC:COUNTRY":
             if ($data=="United States") {
                 $currentOcc->country .= DEFAULT_NEVP_COUNTRY;
             } else {
                 $currentOcc->country .= $data;
             }
             break;
         case "DWC:STATEPROVINCE":
             $currentOcc->stateprovince .= $data;
             break;
         case "DWC:COUNTY":
             $currentOcc->county .= $data;
             break;
         case "DWC:MUNICIPALITY":
             $currentOcc->municipality .= $data;
             break;
         case "DWC:LOCALITY":
             $currentOcc->locality .= $data;
             break;
         case "NOTES":
             $currentOcc->notes .= $data;
             break;
         case "DWC:MINIMUMELEVATIONINMETERS":
             $currentOcc->minimumelevationinmeters .= $data;
             break;
         case "DWC:MAXIMUMELEVATIONINMETERS":
             $currentOcc->maximumelevationinmeters .= $data;
             break;
         case "DWC:MODIFIED":
             $currentOcc->datemodified .= $data;
             break;
         case "OBO:OBI_0000967":
             $currentOcc->storagelocation .= $data;
             break;

         case "DWC:SCIENTIFICNAME":
             if (strlen(trim($currentId->scientificname))>0) {
                 // handle split of scientific name into parts on multiplication sign
                 // add a space back in.
                 $currentId->scientificname .= " ";
             }
             $currentId->scientificname .= $data;
             $currentId->scientificname = trim($currentId->scientificname);
             break;
         case "DWC:FAMILY":
             $currentId->family .= $data;
             break;
         case "DWC:GENUS":
             $currentId->genus .= $data;
             break;
         case "DWC:IDENTIFICATIONQUALIFIER":
             $currentId->identificationqualifier .= $data;
             break;
         case "DWC:SPECIFICEPITHET":
             $currentId->specificepithet .= $data;
             break;
         case "DWC:SCIENTIFICNAMEAUTHORSHIP":
              if (substr($data,0,1)=="&") {
                 // add a space back in if removed by parser
                 $currentId->scientificnameauthorship .= " $data ";
              } else {
                 $currentId->scientificnameauthorship .= $data;
              }
             break;
         case "DWC:INFRASPECIFICEPITHET":
             $currentId->infraspecificepithet .= $data;
             break;
         case "DWC:INFRASPECIFICRANK":
         case "DWCFP:INFRASPECIFICRANK":
             $currentId->infraspecificrank .= $data;
             break;
         case "DWC:NOMENCLATURALCODE":
             $currentId->nomenclaturalcode .= $data;
             break;
         case "DWC:IDENTIFIEDBY":
             $currentId->identifier .= $data;
             break;
         case "DWC:DATEIDENTIFED":
             $currentId->dateidentified .= $data;
             break;
         case "DWCFP:HASTAXONID":
             $currentId->taxonid .= $data;
             break;
         case "DWCFP:ISFILEDUNDERNAMEINCOLLECTION":
             $currentId->isfiledundernameincollection .= $data;
             break;
         case "DWC:TYPESTATUS":
             $currentId->typestatus .= $data;
             break;

         // [Collecting] Event
         case "DWC:EVENTDATE":
             if (strlen(trim($data))>0) {
                $currentDate->setEventDate($data);
             }
             break;
         case "DWC:VERBATIMEVENTDATE":
             $currentDate->verbatimeventdate .= $data;
             break;
         case "DWC:YEAR":
             $currentDate->startyear .= $data;
             break;
         case "DWC:DAY":
             $currentDate->startday .= $data;
             break;
         case "DWC:MONTH":
             $currentDate->startmonth .= $data;
             break;

         // Annotator
         case "FOAF:NAME":
             if ($currentAnnotator!=null) {
                // we are inside the annotated by, not serialized by
                $currentAnnotator->name .= $data;
             }
             if ($currentSerializer!=null) {
                // we are inside the serialized by
                $currentSerializer->name .= $data;
             }
             break;
         case "FOAF:WORLPLACEHOMEPAGE":
             if ($currentAnnotator!=null) {
                // we are inside the annotated by, not serialized by
                $currentAnnotator->workplacehomepage .= $data;
             }
             break;
         case "FOAF:MBOX_SHA1SUM":
             if ($currentAnnotator!=null) {
                // we are inside the annotated by, not serialized by
                $currentAnnotator->mbox_sha1sum .= $data;
             }
             break;
         case "OA:SEARIALIZEDAT":
             $currentAnnotation->serializedAt .= $data;
             break;

         // AssociatedMedia Image
         case "DC:RIGHTS":
             $currentMedia->rights .= $data;
             break;
         case "XMPRIGHTS:OWNER":
             $currentMedia->owner .= $data;
             break;
         case "XMPRIGHTS:USAGETERMS":
             $currentMedia->usageterms .= $data;
             break;

         // AssociatedMedia Image AccessPoint
         case "AC:VARIANT":
             $currentAP->variant .= $data;
             break;
         case "AC:ACCESSURI":
             $currentAP->accessURI .= $data;
             break;
         case "DC:FORMAT":
             $currentAP->format .= $data;
             break;
         case "AC:HASHFUNCTION":
             $currentAP->hashFunction .= $data;
             break;
         case "AC:HASHVALUE":
             $currentAP->hashValue .= $data;
             break;
       }

    }

    public function process($file) {
        global $result,$depth;
        $result = new Result();
        $result->insertcount = 0;
        $result->successcount = 0;
        $result->failurecount = 0;
        $result->updatecount = 0;
        $result->couldparse = true;

        $this->currentDocument = null;

        $parser = xml_parser_create('UTF-8');

        $depth = array();

        xml_set_element_handler($parser, "NEVPProcessor::startElement", "NEVPProcessor::endElement");
        xml_set_character_data_handler($parser, "NEVPProcessor::value");

        if (!($fp = fopen($file, "r"))) {
            $result->couldparse = false;
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($parser, $data, feof($fp))) {
                $result->couldparse = false;
                // sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
            }
        }

        if ($this->countfound!=$this->acount) {
           // TODO: Report discrepancy.
           echo "ERROR: Expected $this->acount annotations, found $this->countfound \n";
        }

        xml_parser_free($parser);

        return $result;
    }

}

?>
