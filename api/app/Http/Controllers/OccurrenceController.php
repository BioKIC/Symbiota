<?php

namespace App\Http\Controllers;

use App\Models\Occurrence;
use App\Models\PortalIndex;
use App\Models\PortalOccurrence;
use App\Helpers\OccurrenceHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OccurrenceController extends Controller{

	/**
	 * Occurrence controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/occurrence/search",
	 *	 operationId="/api/v2/occurrence/search",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="collid",
	 *		 in="query",
	 *		 description="collid(s) - collection identifier(s) in portal",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="catalogNumber",
	 *		 in="query",
	 *		 description="catalogNumber",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="occurrenceID",
	 *		 in="query",
	 *		 description="occurrenceID",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="family",
	 *		 in="query",
	 *		 description="family",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="sciname",
	 *		 in="query",
	 *		 description="Scientific Name - binomen only without authorship",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="recordedBy",
	 *		 in="query",
	 *		 description="Collector/observer of occurrence",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="recordedByLastName",
	 *		 in="query",
	 *		 description="Last name of collector/observer of occurrence",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="recordNumber",
	 *		 in="query",
	 *		 description="Personal number of the collector or observer of the occurrence",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="eventDate",
	 *		 in="query",
	 *		 description="Date as YYYY, YYYY-MM or YYYY-MM-DD",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="country",
	 *		 in="query",
	 *		 description="country",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="stateProvince",
	 *		 in="query",
	 *		 description="State, Province, or second level political unit",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="county",
	 *		 in="query",
	 *		 description="County, parish, or third level political unit",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="datasetID",
	 *		 in="query",
	 *		 description="dataset ID within portal",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="limit",
	 *		 in="query",
	 *		 description="Controls the number of results per page",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="offset",
	 *		 in="query",
	 *		 description="Determines the starting point for the search results. A limit of 100 and offset of 200, will display 100 records starting the 200th record.",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns list of occurrences",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllOccurrences(Request $request){
		$this->validate($request, [
			'collid' => 'regex:/^[\d,]+?$/',
			'limit' => ['integer', 'max:300'],
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$occurrenceModel = Occurrence::query();
		if($request->has('collid')){
			$occurrenceModel->whereIn('collid', explode(',', $request->collid));
		}
		if($request->has('catalogNumber')){
			$occurrenceModel->where('catalogNumber', $request->catalogNumber);
		}
		if($request->has('occurrenceID')){
			//$occurrenceModel->where('occurrenceID', $request->occurrenceID);
			$occurrenceID = $request->occurrenceID;
			$occurrenceModel->where(function ($query) use ($occurrenceID) {$query->where('occurrenceID', $occurrenceID)->orWhere('recordID', $occurrenceID);});

		}
		//Taxonomy
		if($request->has('family')){
			$occurrenceModel->where('family', $request->family);
		}
		if($request->has('sciname')){
			$occurrenceModel->where('sciname', $request->sciname);
		}
		//Collector units
		if($request->has('recordedBy')){
			$occurrenceModel->where('recordedBy', $request->recordedBy);
		}
		if($request->has('recordedByLastName')){
			$occurrenceModel->where('recordedBy', 'LIKE', '%' . $request->recordedByLastName . '%');
		}
		if($request->has('recordNumber')){
			$occurrenceModel->where('recordNumber', $request->recordNumber);
		}
		if($request->has('eventDate')){
			$occurrenceModel->where('eventDate', $request->eventDate);
		}
		if($request->has('datasetID')){
			$occurrenceModel->where('datasetID', $request->datasetID);
		}
		//Locality place names
		if($request->has('country')){
			$occurrenceModel->where('country', $request->country);
		}
		if($request->has('stateProvince')){
			$occurrenceModel->where('stateProvince', $request->stateProvince);
		}
		if($request->has('county')){
			$occurrenceModel->where('county', $request->county);
		}

		$fullCnt = $occurrenceModel->count();
		$result = $occurrenceModel->skip($offset)->take($limit)->get();

		$eor = false;
		$retObj = [
			'offset' => (int)$offset,
			'limit' => (int)$limit,
			'endOfRecords' => $eor,
			'count' => $fullCnt,
			'results' => $result
		];
		return response()->json($retObj);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/occurrence/{identifier}",
	 *	 operationId="/api/v2/occurrence/identifier",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="occid or specimen GUID (occurrenceID) associated with target occurrence",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="includeMedia",
	 *		 in="query",
	 *		 description="Whether to include media within output",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="includeIdentifications",
	 *		 in="query",
	 *		 description="Whether to include full Identification History within output",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns single occurrence record",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Occurrence identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneOccurrence($id, Request $request){
		$this->validate($request, [
			'includeMedia' => 'integer',
			'includeIdentifications' => 'integer'
		]);
		$id = $this->getOccid($id);
		$occurrence = Occurrence::find($id);
		if($occurrence){
			if(!$occurrence->occurrenceID) $occurrence->occurrenceID = $occurrence->recordID;
			if($request->input('includeMedia')) $occurrence->media;
			if($request->input('includeIdentifications')) $occurrence->identification;
		}
		return response()->json($occurrence);
	}


	/**
	 * @OA\Get(
	 *	 path="/api/v2/occurrence/{identifier}/identification",
	 *	 operationId="/api/v2/occurrence/identifier/identification",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="occid or specimen GUID (occurrenceID) associated with target occurrence",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns identification records associated with a given occurrence record",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Occurrence identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneOccurrenceIdentifications($id, Request $request){
		$id = $this->getOccid($id);
		$identification = Occurrence::find($id)->identification;
		return response()->json($identification);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/occurrence/{identifier}/media",
	 *	 operationId="/api/v2/occurrence/identifier/media",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="occid or specimen GUID (occurrenceID) associated with target occurrence",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns media records associated with a given occurrence record",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Occurrence identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneOccurrenceMedia($id, Request $request){
		$id = $this->getOccid($id);
		$media = Occurrence::find($id)->media;
		return response()->json($media);
	}

	//Write funcitons
	public function insert(Request $request){
		if($user = $this->authenticate($request)){
			$this->validate($request, [
				'collid' => 'required|integer'
			]);
			$collid = $request->input('collid');
			//Check to see if user has the necessary permission edit/add occurrences for target collection
			if(!$this->isAuthorized($user, $collid)){
				return response()->json(['error' => 'Unauthorized to add new records to target collection (collid = ' . $collid . ')'], 401);
			}
			$inputArr = $request->all();
			$inputArr['recordID'] = (string) Str::uuid();
			$inputArr['dateEntered'] = date('Y-m-d H:i:s');

			//$occurrence = Occurrence::create($inputArr);
			//return response()->json($occurrence, 201);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	public function update($id, Request $request){
		if($user = $this->authenticate($request)){
			$id = $this->getOccid($id);
			$occurrence = Occurrence::find($id);
			if(!$occurrence){
				return response()->json(['status' => 'failure', 'error' => 'Occurrence resource not found'], 400);
			}
			if(!$this->isAuthorized($user, $occurrence['collid'])){
				return response()->json(['error' => 'Unauthorized to edit target collection (collid = ' . $occurrence['collid'] . ')'], 401);
			}
			//$occurrence->update($request->all());
			//return response()->json($occurrence, 200);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	public function delete($id, Request $request){
		if($user = $this->authenticate($request)){
			$id = $this->getOccid($id);
			$occurrence = Occurrence::find($id);
			if(!$occurrence){
				return response()->json(['status' => 'failure', 'error' => 'Occurrence resource not found'], 400);
			}
			if(!$this->isAuthorized($user, $occurrence['collid'])){
				return response()->json(['error' => 'Unauthorized to delete target collection (collid = ' . $occurrence['collid'] . ')'], 401);
			}
			//$occurrence->delete();
			//return response('Occurrence Deleted Successfully', 200);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	/**
	 * @OA\Post(
	 *	 path="/api/v2/occurrence/skeletal",
	 *	 operationId="skeletalImport",
	 *	 description="If an existing record can be located within target collection based on matching the input identifier, empty (null) target fields will be updated with Skeletal Data.
			If the target field contains data, it will remain unaltered.
			If multiple records are returned matching the input identifier, data will be added only to the first record.
			If an identifier is not provided or a matching record can not be found, a new Skeletal record will be created and primed with input data.
			Note that catalogNumber or otherCatalogNumber must be provided to create a new skeletal record. If processingStatus is not defined, new skeletal records will be set as 'unprocessed'",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		name="apiToken",
	 *		in="query",
	 *		description="API security token to authenticate post action",
	 *		required=true,
	 *		@OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		name="collid",
	 *		in="query",
	 *		description="primary key of target collection dataset",
	 *		required=true,
	 *		@OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		name="identifier",
	 *		in="query",
	 *		description="catalog number, other identifiers, occurrenceID, or recordID GUID (UUID) used to locate target occurrence occurrence",
	 *		required=false,
	 *		@OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		name="identifierTarget",
	 *		in="query",
	 *		description="Target field for matching identifier: catalog number, other identifiers (aka otherCatalogNumbers), GUID (occurrenceID or recordID), occid (primary key for occurrence). If identifier field is null, a new skeletal record will be created, given that a catalog number is provided.",
	 *		required=false,
	 *		@OA\Schema(
	 *			type="string",
	 *			default="CATALOGNUMBER",
	 *			enum={"CATALOGNUMBER", "IDENTIFIERS", "GUID", "OCCID", "NONE"}
	 *		)
	 *	 ),
	 *	 @OA\RequestBody(
	 *		required=true,
	 *		description="Occurrence data to be inserted",
	 *		@OA\MediaType(
	 *			mediaType="application/json",
	 *			@OA\Schema(
	 *				@OA\Property(
	 *					property="catalogNumber",
	 *					type="string",
	 *					description="Primary catalog number",
	 *					maxLength=32
	 *				),
	 *				@OA\Property(
	 *					property="otherCatalogNumbers",
	 *					type="string",
	 *					description="Additional catalog numbers",
	 *					maxLength=75
	 *				),
	 *				@OA\Property(
	 *					property="sciname",
	 *					type="string",
	 *					description="Scientific name, without the author",
	 *					maxLength=255
	 *				),
	 *				@OA\Property(
	 *					property="scientificNameAuthorship",
	 *					type="string",
	 *					description="The authorship information of scientific name",
	 *					maxLength=255
	 *				),
	 *				@OA\Property(
	 *					property="family",
	 *					type="string",
	 *					description="Taxonomic family of the scientific name",
	 *					maxLength=255
	 *				),
	 *				@OA\Property(
	 *					property="recordedBy",
	 *					type="string",
	 *					description="Primary collector or observer",
	 *					maxLength=255
	 *				),
	 *				@OA\Property(
	 *					property="recordNumber",
	 *					type="string",
	 *					description="Identifier given at the time occurrence was recorded; typically the personal identifier of the primary collector or observer",
	 *					maxLength=45
	 *				),
	 *				@OA\Property(
	 *					property="eventDate",
	 *					type="string",
	 *					description="Date the occurrence was collected or observed, or earliest date if a range was provided"
	 *				),
	 *				@OA\Property(
	 *					property="eventDate2",
	 *					type="string",
	 *					description="Last date the occurrence was collected or observed. Used when a date range is provided"
	 *				),
	 *				@OA\Property(
	 *					property="country",
	 *					type="string",
	 *					description="The name of the country or major administrative unit",
	 *					maxLength=64
	 *				),
	 *				@OA\Property(
	 *					property="stateProvince",
	 *					type="string",
	 *					description="The name of the next smaller administrative region than country (state, province, canton, department, region, etc.)",
	 *					maxLength=255
	 *				),
	 *				@OA\Property(
	 *					property="county",
	 *					type="string",
	 *					description="The full, unabbreviated name of the next smaller administrative region than stateProvince (county, shire, department, etc.",
	 *					maxLength=255
	 *				),
	 *				@OA\Property(
	 *					property="processingStatus",
	 *					type="string",
	 *					description="Processing status of the specimen record",
	 *					maxLength=45
	 *				),
	 *			),
	 *		)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns full JSON object of the of media record that was edited"
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request.",
	 *	 ),
	 *	 @OA\Response(
	 *		 response="401",
	 *		 description="Unauthorized",
	 *	 ),
	 * )
	 */
	public function skeletalImport(Request $request){
		$this->validate($request, [
			'collid' => 'required|integer',
			'eventDate' => 'date',
			'eventDate2' => 'date',
			'identifierTarget' => 'in:CATALOGNUMBER,IDENTIFIERS,GUID,OCCID,NONE',
		]);
		if($user = $this->authenticate($request)){
			$collid = $request->input('collid');
			$identifier = $request->input('identifier');
			$identifierTarget = $request->input('identifierTarget', 'CATALOGNUMBER');

			//Check to see if user has the necessary permission edit/add occurrences for target collection
			if(!$this->isAuthorized($user, $collid)){
				return response()->json(['error' => 'Unauthorized to edit target collection (collid = ' . $collid . ')'], 401);
			}

			//Remove fields with empty values and non-approved target fields
			$updateArr = $request->all();
			$skeletalFieldsAllowed = array('catalogNumber', 'otherCatalogNumbers', 'sciname', 'scientificNameAuthorship', 'family', 'recordedBy', 'recordNumber', 'eventDate', 'eventDate2', 'country', 'stateProvince', 'county', 'processingStatus');
			foreach($updateArr as $fieldName => $fieldValue){
				if(!$fieldValue) unset($updateArr[$fieldName]);
				elseif(!in_array($fieldName, $skeletalFieldsAllowed)) unset($updateArr[$fieldName]);
			}
			if(!$updateArr){
				return response()->json(['error' => 'Bad request: input data empty or does not contains allowed fields'], 400);
			}

			//Get target record, if exists
			$targetOccurrence = null;
			if($identifier){
				$occurrenceModel = null;
				if($identifierTarget == 'OCCID'){
					$occurrenceModel = Occurrence::where('occid', $identifier);
				}
				elseif($identifierTarget == 'GUID'){
					$occurrenceModel = Occurrence::where('occurrenceID', $identifier)->orWhere('recordID', $identifier);
				}
				elseif($identifierTarget == 'CATALOGNUMBER'){
					$occurrenceModel = Occurrence::where('catalogNumber', $identifier);
				}
				elseif($identifierTarget == 'IDENTIFIERS'){
					$occurrenceModel = Occurrence::where('otherCatalogNumbers', $identifier);
				}
				if($occurrenceModel){
					$targetOccurrence = $occurrenceModel->where('collid', $collid)->first();
				}
			}
			if($targetOccurrence){
				foreach($updateArr as $fieldName => $fieldValue){
					//Remove input if target field already contains data
					if($targetOccurrence[$fieldName]){
						unset($updateArr[$fieldName]);
					}
				}
				if(!empty($updateArr['eventDate'])){
					$updateArr['eventDate'] = OccurrenceHelper::formatDate($updateArr['eventDate']);
				}
				if(!empty($updateArr['eventDate2'])){
					$updateArr['eventDate2'] = OccurrenceHelper::formatDate($updateArr['eventDate2']);
				}
				$responseObj = ['number of fields affected' => count($updateArr), 'fields affected' => $updateArr];
				if($updateArr){
					$targetOccurrence->update($updateArr);
				}
				return response()->json($responseObj, 200);
			}
			else{
				//Record doesn't exist, thus create a new skeletal records, given that a catalog number exists
				$updateArr['collid'] = $collid;
				if(empty($updateArr['catalogNumber']) && empty($updateArr['otherCatalogNumbers'])){
					return response()->json(['error' => 'Bad request: catalogNumber or otherCatalogNumbers required when creating a new record'], 400);
				}
				if(empty($updateArr['processingStatus'])) $updateArr['processingStatus'] = 'unprocessed';
				$updateArr['recordID'] = (string) Str::uuid();
				$updateArr['dateEntered'] = date('Y-m-d H:i:s');
				$newOccurrence = Occurrence::create($updateArr);
				return response()->json($newOccurrence, 201);
			}
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	/**
	 * @off_OA\Get(
	 *	 path="/api/v2/occurrence/{identifier}/reharvest",
	 *	 operationId="/api/v2/occurrence/identifier/reharvest",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="occid or specimen GUID (occurrenceID) associated with target occurrence",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Triggers a reharvest event of a snapshot record. If record is Live managed, request is ignored",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request: Occurrence identifier is required, API can only be triggered locally (at this time).",
	 *	 ),
	 *	 @OA\Response(
	 *		 response="500",
	 *		 description="Error: unable to locate record",
	 *	 ),
	 * )
	 */
	public function oneOccurrenceReharvest($id, Request $request){
		$responseArr = array();
		$host = '';
		if(!empty($GLOBALS['SERVER_HOST'])) $host = $GLOBALS['SERVER_HOST'];
		else $host = $_SERVER['SERVER_NAME'];
		if($host && $request->getHttpHost() != $host){
			$responseArr['status'] = 400;
			$responseArr['error'] = 'At this time, API call can only be triggered locally';
			return response()->json($responseArr);
		}
		$id = $this->getOccid($id);
		$occurrence = Occurrence::find($id);
		if(!$occurrence){
			$responseArr['status'] = 500;
			$responseArr['error'] = 'Unable to locate occurrence record (occid = '.$id.')';
			return response()->json($responseArr);
		}
		if($occurrence->collection->managementType == 'Live Data'){
			$responseArr['status'] = 400;
			$responseArr['error'] = 'Updating a Live Managed record is not allowed ';
			return response()->json($responseArr);
		}
		$publications = $occurrence->portalPublications;
		foreach($publications as $pub){
			if($pub->direction == 'import'){
				$sourcePortalID = $pub->portalID;
				$remoteOccid = $pub->pivot->remoteOccid;
				if($sourcePortalID && $remoteOccid){
					//Get remote occurrence data
					$urlRoot = PortalIndex::where('portalID', $sourcePortalID)->value('urlRoot');
					$url = $urlRoot.'/api/v2/occurrence/'.$remoteOccid;
					if($remoteOccurrence = $this->getAPIResponce($url)){
						unset($remoteOccurrence['modified']);
						if(!$remoteOccurrence['occurrenceRemarks']) unset($remoteOccurrence['occurrenceRemarks']);
						unset($remoteOccurrence['dynamicProperties']);
						$updateObj = $this->update($id, new Request($remoteOccurrence));
						$ts = date('Y-m-d H:i:s');
						$changeArr = $updateObj->getOriginalContent()->getChanges();
						$responseArr['status'] = $updateObj->status();
						$responseArr['dataStatus'] = ($changeArr?count($changeArr).' fields modified':'nothing modified');
						$responseArr['fieldsModified'] = $changeArr;
						$responseArr['sourceDateLastModified'] = $remoteOccurrence['dateLastModified'];
						$responseArr['dateLastModified'] = $ts;
						$responseArr['sourceCollectionUrl'] = $urlRoot.'/collections/misc/collprofiles.php?collid='.$remoteOccurrence['collid'];
						$responseArr['sourceRecordUrl'] = $urlRoot.'/collections/individual/index.php?occid='.$remoteOccid;
						//Reset Portal Occurrence refreshDate
						$portalOccur = PortalOccurrence::where('occid', $id)->where('pubid', $pub->pubid)->first();
						$portalOccur->refreshTimestamp = $ts;
						$portalOccur->save();
					}
					else {
						$responseArr['status'] = 400;
						$responseArr['error'] = 'Unable to locate remote/source occurrence (sourceID = '.$id.')';
						$responseArr['sourceUrl'] = $url;
					}
				}
			}
		}
		return response()->json($responseArr);
	}

	//Helper functions
	protected function getOccid($id){
		if(!is_numeric($id)){
			$occid = Occurrence::where('occurrenceID', $id)->orWhere('recordID', $id)->value('occid');
			if(is_numeric($occid)) $id = $occid;
		}
		return $id;
	}

	private function isAuthorized($user, $collid){
		foreach($user['roles'] as $roles){
			if($roles['role'] == 'SuperAdmin') return true;
			elseif($roles['role'] == 'CollAdmin' && $roles['tablePK'] == $collid) return true;
			elseif($roles['role'] == 'CollEditor' && $roles['tablePK'] == $collid) return true;
		}
		return false;
	}

	protected function getAPIResponce($url, $asyc = false){
		$resJson = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if($asyc) curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
		$resJson = curl_exec($ch);
		if(!$resJson){
			$this->errorMessage = 'FATAL CURL ERROR: '.curl_error($ch).' (#'.curl_errno($ch).')';
			return false;
			//$header = curl_getinfo($ch);
		}
		curl_close($ch);
		return json_decode($resJson,true);
	}
}
