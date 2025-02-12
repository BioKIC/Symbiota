<?php
namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller{
	/**
	 * Inventory controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/inventory",
	 *	 operationId="/api/v2/inventory",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="limit",
	 *		 in="query",
	 *		 description="Controls the number of results in the page.",
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
	 *		 description="Returns list of inventories registered within system",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllInventories(Request $request){
		$this->validate($request, [
			'limit' => 'integer',
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$fullCnt = Inventory::count();
		$result = Inventory::skip($offset)->take($limit)->get();
		$result->makeHidden('footprintWkt')->toArray();

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
	 *	 path="/api/v2/inventory/{identifier}",
	 *	 operationId="/api/v2/inventory/identifier",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="PK, GUID, or recordID associated with target inventory",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns metabase on inventory registered within system with matching ID",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Inventory identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneInventory($id, Request $request){
		$id = $this->getClid($id);
		$inventoryObj = Inventory::find($id);
		if(!$inventoryObj->count()) $inventoryObj = ['status' =>false, 'error' => 'Unable to locate inventory based on identifier'];
		return response()->json($inventoryObj);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/inventory/{identifier}/taxa",
	 *	 operationId="/api/v2/inventory/identifier/taxa",
	 *	 tags={""},
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
	 *		 description="Returns list of inventories registered within system",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showOneInventoryTaxa($id, Request $request){
		$this->validate($request, [
			'limit' => 'integer',
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$id = $this->getClid($id);
		$inventoryObj = Inventory::find($id);
		$fullCnt = $inventoryObj->taxa()->count();
		$result = null;
		if($fullCnt){
			$result = $inventoryObj->taxa()->skip($offset)->take($limit)->get();
		}
		else $result = ['status' =>false, 'error' => 'Unable to locate inventory based on identifier'];

		$eor = false;
		if(($offset + $limit) >= $fullCnt) $eor = true;
		$retObj = [
			'offset' => (int)$offset,
			'limit' => (int)$limit,
			'endOfRecords' => $eor,
			'count' => $fullCnt,
			'results' => $result
		];
		return response()->json($retObj);
	}

	//Helper function
	protected function getClid($id){
		if(is_numeric($id)) $clid = $id;
		else{
			$clid = Inventory::where('recordID', $id)->first()->value('clid');
			if(!$clid) $clid = Inventory::where('guid', $id)->first()->value('clid');
		}
		return $clid;
	}
}