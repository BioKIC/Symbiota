<?php

namespace App\Http\Controllers;

use App\Models\Occurrence;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class OccurrenceAnnotationController extends OccurrenceController{
	/**
	 * Occurrence Annotation controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/occurrence/annotation",
	 *	 operationId="/api/v2/occurrence/annotation",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="collid",
	 *		 in="query",
	 *		 description="Internal identifier (PK) for collection",
	 *		 required=true,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="type",
	 *		 in="query",
	 *		 description="Annoration type (internal, external) ",
	 *		 required=true,
	 *		 @OA\Schema(type="string", default="internal", enum = {"internal", "external"})
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="source",
	 *		 in="query",
	 *		 description="External source of Annoration (e.g. geolocate) ",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="fieldName",
	 *		 in="query",
	 *		 description="Name of occurrence field that was annotated (e.g. recordedBy, eventDate) ",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="fromDate",
	 *		 in="query",
	 *		 description="The start date of a date range the annotation was created (e.g. 2022-02-05) ",
	 *		 required=false,
	 *		 @OA\Schema(type="date")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="toDate",
	 *		 in="query",
	 *		 description="The end date of a date range the annotation was created (e.g. 2022-02-05) ",
	 *		 required=false,
	 *		 @OA\Schema(type="date")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="limit",
	 *		 in="query",
	 *		 description="Controls the number of results per page",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=500)
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
	 *		 description="Returns list of occurrence edits",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllAnnotations(Request $request){
		$this->validate($request, [
			'collid' => ['required', 'integer'],
			'type' => [Rule::in(['internal', 'external'])],
			'source' => 'alpha',
			'fieldName' => 'alpha',
			'fromDate' => 'date',
			'toDate' => 'date',
			'limit' => ['integer', 'max:500'],
			'offset' => 'integer'
		]);
		$collid = $request->input('collid');
		$type = $request->input('type', 'internal');
		$source = $request->input('source');
		$fieldName = $request->input('fieldName');
		$fromDate = $request->input('fromDate');
		$toDate = $request->input('toDate');
		$limit = $request->input('limit', 100);
		$offset = $request->input('offset', 0);

		$annotation = null;
		$fullCnt = 0;
		$result = null;
		if($type == 'internal'){
			$annotation = DB::table('omoccuredits as e')->select('e.*', 'o.occurrenceID', 'o.recordID')
				->join('omoccurrences as o', 'e.occid', '=', 'o.occid')
				->where('o.collid', $collid);
			if($fieldName){
				$annotation = $annotation->where('e.fieldname', $fieldName);
			}
			if($fromDate){
				$annotation = $annotation->where('e.initialTimestamp', '>', $fromDate);
			}
			if($toDate){
				$annotation = $annotation->where('e.initialTimestamp', '<', $toDate);
			}
			$fullCnt = $annotation->count();
			$result = $annotation->skip($offset)->take($limit)->get();
			$result = $this->formatInternalResults($result);
		}
		elseif($type == 'external'){
			$annotation = DB::table('omoccurrevisions as r')->select('r.*', 'o.occurrenceID', 'o.recordID')
				->join('omoccurrences as o', 'o.occid', '=', 'r.occid')
				->where('o.collid', $collid);
			if($source){
				$annotation = $annotation->where('r.externalSource', $source);
			}
			if($fieldName){
				$annotation = $annotation->where('r.oldvalues', 'like', '%'.$fieldName.'%');
			}
			if($fromDate){
				$annotation = $annotation->where('r.initialTimestamp', '>', $fromDate);
			}
			if($toDate){
				$annotation = $annotation->where('r.initialTimestamp', '<', $toDate);
			}
			$fullCnt = $annotation->count();
			$result = $annotation->skip($offset)->take($limit)->get();
			$result = $this->formatExternalResults($result, $fieldName);
		}

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

	public function showOccurrenceAnnotations($id, Request $request){
		$this->validate($request, [
			'type' => [Rule::in(['internal', 'external'])]
		]);
		$type = $request->input('type', 'internal');

		$id = $this->getOccid($id);
		$annotation = null;
		if($type == 'internal'){
			$annotation = Occurrence::find($id)->annotationInternal;
		}
		elseif($type == 'external'){
			$annotation = Occurrence::find($id)->annotationExternal;
		}

		return response()->json($annotation);
	}

	//Helper funcitons
	private function formatExternalResults($resultObj, $fieldLimit){
		$retArr = array();
		foreach($resultObj as $unitKey => $unitObj){
			$unitArr = (array)$unitObj;
			$unitArr = array_change_key_case($unitArr);
			if(isset($unitArr['oldvalues'])){
				$newArr1 = array();
				$newArr2 = array();
				$newArr1['annotationID'] = $unitArr['orid'];
				$newArr1['occid'] = $unitArr['occid'];
				if($unitArr['occurrenceid']) $newArr1['occurrenceID'] = $unitArr['occurrenceid'];
				else $newArr1['occurrenceID'] = $unitArr['recordid'];
				$newArr2['externalSource'] = $unitArr['externalsource'];
				$newArr2['externalEditor'] = $unitArr['externaleditor'];
				$newArr2['reviewStatus'] = $unitArr['reviewstatus'];
				$newArr2['appliedStatus'] = $unitArr['appliedstatus'];
				$newArr2['recordID'] = $unitArr['guid'];
				$newArr2['externalError'] = $unitArr['errormessage'];
				$newArr2['externalTimestamp'] = $unitArr['externaltimestamp'];
				$newArr2['recordTimestamp'] = $unitArr['initialtimestamp'];
				$oldValueArr = json_decode($unitArr['oldvalues'], true);
				$newValueArr = json_decode($unitArr['newvalues'], true);
				foreach($oldValueArr as $fieldName => $oldValue){
					if(!$fieldLimit || $fieldLimit == $fieldName){
						if(array_key_exists($fieldName, $newValueArr)){
							$retArr[] = array_merge($newArr1, array('fieldName' => $fieldName, 'oldValue' => $oldValue, 'newValue' => $newValueArr[$fieldName]), $newArr2);
						}
					}
				}
			}
		}
		return $retArr;
	}

	private function formatInternalResults($resultObj){
		$retArr = array();
		foreach($resultObj as $unitKey => $unitObj){
			$unitArr = (array)$unitObj;
			$unitArr = array_change_key_case($unitArr);
			$retArr[$unitKey]['annotationID'] = $unitArr['ocedid'];
			$retArr[$unitKey]['occid'] = $unitArr['occid'];
			if($unitArr['occurrenceid']) $retArr[$unitKey]['occurrenceID'] = $unitArr['occurrenceid'];
			else $retArr[$unitKey]['occurrenceID'] = $unitArr['recordid'];
			$retArr[$unitKey]['fieldName'] = $unitArr['fieldname'];
			$retArr[$unitKey]['newValue'] = $unitArr['fieldvaluenew'];
			$retArr[$unitKey]['oldValue'] = $unitArr['fieldvalueold'];
			$retArr[$unitKey]['reviewStatus'] = $unitArr['reviewstatus'];
			$retArr[$unitKey]['appliedStatus'] = $unitArr['appliedstatus'];
			$retArr[$unitKey]['recordID'] = $unitArr['guid'];
			$retArr[$unitKey]['recordTimestamp'] = $unitArr['initialtimestamp'];
		}
		return $retArr;
	}
}
