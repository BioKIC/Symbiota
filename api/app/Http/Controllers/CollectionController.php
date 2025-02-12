<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller{
	/**
	 * Collection controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/collection",
	 *	 operationId="/api/v2/collection",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="managementType",
	 *		 in="query",
	 *		 description="live, snapshot, aggregate",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="collectionType",
	 *		 in="query",
	 *		 description="preservedSpecimens, observations, researchObservation",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="limit",
	 *		 in="query",
	 *		 description="Controls the number of results per page",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=1000)
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
	 *		 description="Returns list of collections",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllCollections(Request $request){
		$this->validate($request, [
			'limit' => ['integer', 'max:1000'],
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',1000);
		$offset = $request->input('offset',0);

		$conditions = [];
		if($request->has('managementType')){
			if($request->managementType == 'live') $conditions[] = ['managementType','Live Data'];
			elseif($request->managementType == 'snapshot') $conditions[] = ['managementType','Snapshot'];
			elseif($request->managementType == 'aggregate') $conditions[] = ['managementType','Aggregate'];
		}
		if($request->has('collectionType')){
			if($request->collectionType == 'specimens') $conditions[] = ['collType','Preserved Specimens'];
			elseif($request->collectionType == 'observations') $conditions[] = ['collType','Observations'];
			elseif($request->collectionType == 'researchObservations') $conditions[] = ['collType','General Observations'];
		}

		$fullCnt = Collection::where($conditions)->count();
		$result = Collection::where($conditions)->skip($offset)->take($limit)->get();

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
	 *	 path="/api/v2/collection/{identifier}",
	 *	 operationId="/api/v2/collection/identifier",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="Installation ID or GUID associated with target collection",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns collection data",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Collection identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneCollection($id, Request $request){
		$collectionObj = null;
		if(is_numeric($id)) $collectionObj = Collection::find($id);
		else $collectionObj = Collection::where('collectionGuid',$id)->first();
		if(!$collectionObj->count()) $collectionObj = ["status"=>false,"error"=>"Unable to locate collection based on identifier"];
		return response()->json($collectionObj);
	}

	public function create(Request $request){
		//$collection = Collection::create($request->all());
		//return response()->json($collection, 201);
	}

	public function update($id, Request $request){
		//$collection = Collection::findOrFail($id);
		//$collection->update($request->all());
		//return response()->json($collection, 200);
	}

	public function delete($id){
		//Collection::findOrFail($id)->delete();
		//return response('Collection Deleted Successfully', 200);
	}
}