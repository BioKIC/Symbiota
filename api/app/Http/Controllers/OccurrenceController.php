<?php

namespace App\Http\Controllers;

use App\Models\Occurrence;
use App\Models\PortalIndex;
use App\Models\PortalOccurrence;
use Illuminate\Http\Request;
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
	 *		 name="collid",
	 *		 in="query",
	 *		 description="collid - collection identifier in portal",
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
	 *		 name="eventDate",
	 *		 in="query",
	 *		 description="Date as YYYY, YYYY-MM or YYYY-MM-DD",
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
	 *		 description="Determines the offset for the search results. A limit of 200 and offset of 100, will get the third page of 100 results.",
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
			'limit' => ['integer', 'max:300'],
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$conditions = [];
		if($request->has('catalogNumber')) $conditions[] = ['catalogNumber',$request->catalogNumber];
		if($request->has('occurrenceID')) $conditions[] = ['occurrenceID',$request->occurrenceID];
		if($request->has('country')) $conditions[] = ['country',$request->country];
		if($request->has('stateProvince')) $conditions[] = ['stateProvince',$request->stateProvince];
		if($request->has('county')) $conditions[] = ['county','LIKE',$request->county.'%'];
		if($request->has('collid')) $conditions[] = ['collid',$request->collid];
		if($request->has('family')) $conditions[] = ['family',$request->family];
		if($request->has('sciname')) $conditions[] = ['sciname','LIKE',$request->sciname.'%'];
		if($request->has('datasetID')) $conditions[] = ['datasetID',$request->datasetID];
		if($request->has('eventDate')) $conditions[] = ['eventDate','LIKE',$request->eventDate.'%'];


		$fullCnt = Occurrence::where($conditions)->count();
		$result = Occurrence::where($conditions)->skip($offset)->take($limit)->get();

		$eor = false;
		$retObj = [
			"offset" => (int)$offset,
			"limit" => (int)$limit,
			"endOfRecords" => $eor,
			"count" => $fullCnt,
			"results" => $result
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

	//Write funcitons
	public function create(Request $request){
		//$occurrence = Occurrence::create($request->all());
		//return response()->json($occurrence, 201);
	}

	public function update($id, Request $request){
		$occurrence = Occurrence::findOrFail($id);
		$occurrence->update($request->all());
		//if($occurrence->wasChanged()) ;
		return response()->json($occurrence, 200);
	}

	public function delete($id){
		//Occurrence::findOrFail($id)->delete();
		//return response('Occurrence Deleted Successfully', 200);
	}

	//Helper functions
	protected function getOccid($id){
		if(!is_numeric($id)){
			$occid = Occurrence::where('occurrenceID', $id)->orWhere('recordID', $id)->value('occid');
			if(is_numeric($occid)) $id = $occid;
		}
		return $id;
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
