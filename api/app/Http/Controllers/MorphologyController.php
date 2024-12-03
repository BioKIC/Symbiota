<?php
namespace App\Http\Controllers;

use App\Models\MorphologyCharacter;
//use App\Models\MorphologyAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MorphologyController extends Controller{
	/**
	 * Taxon Morphology controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/morphology",
	 *	 operationId="/api/v2/morphology",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="includeStates",
	 *		 in="query",
	 *		 description="Controls whether character traits are included within output: 0 [default] = do not include state, 1 = include states ",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
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
	 *		 description="Determines the offset for the search results. A limit of 200 and offset of 100, will get the third page of 100 results.",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns list of morphological characters within system",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllCharacters(Request $request){
		$this->validate($request, [
			'includeStates' => 'integer',
			'limit' => 'integer',
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$morphModel = MorphologyCharacter::query();
		if($request->input('includeStates')){
			$morphModel->with('states');
		}

		$fullCnt = $morphModel->count();
		$result = $morphModel->skip($offset)->take($limit)->get();

		$eor = false;
		$retObj = [
			'offset' => (int)$offset,
			'limit' => (int)$limit,
			//'endOfRecords' => $eor,
			'count' => $fullCnt,
			'results' => $result
		];
		return response()->json($retObj);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/morphology/{identifier}",
	 *	 operationId="/api/v2/morphology/identifier",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="Identifier (PK = cid) associated with morphological character",
	 *		 required=true,
	 *		 @OA\Schema(type="integer")
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
	public function showOneCharacter($id, Request $request){
		$morphObj = MorphologyCharacter::find($id);

		if($morphObj->count()) $morphObj->states;
		else $morphObj = ['status'=>false,'error'=>'Unable to locate morphological character based on identifier'];
		return response()->json($morphObj);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/morphology/{identifier}/attribute",
	 *	 operationId="/api/v2/morphology/identifier/attribute",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="Identifier (PK = cid) associated with morphological character",
	 *		 required=true,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="cs",
	 *		 in="query",
	 *		 description="Limit by character State ID (aka cs)",
	 *		 required=false,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="tid",
	 *		 in="query",
	 *		 description="Limit by taxon PK (aka tid)",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
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
	 *		 description="Determines the offset for the search results. A limit of 200 and offset of 100, will get the third page of 100 results.",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns list of attribute codings for a given morphological character",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showCharacterAttributes($id, Request $request){
		$this->validate($request, [
			'limit' => 'integer',
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$morphObj = DB::table('kmdescr as d')
			->select('d.cs', 'cs.charStateName', 't.tid', 't.sciname', 'd.inherited', 'd.source', 'd.notes', 'd.initialTimestamp')
			->leftJoin('taxa as t', 'd.tid', '=', 't.tid')
			->leftJoin('kmcs as cs', function($join){
				$join->on('d.cid', '=', 'cs.cid');
				$join->on('d.cs', '=', 'cs.cs');
			})
			->where('d.cid', $id);

		if($request->has('cs')){
			$morphObj->where('d.cs', $request->input('cs'));
		}

		if($request->has('tid')){
			$morphObj->where('d.tid', $request->input('tid'));
		}

		$fullCnt = $morphObj->count();
		$result = $morphObj->skip($offset)->take($limit)->get();

		$eor = false;
		$retObj = [
			'offset' => (int)$offset,
			'limit' => (int)$limit,
			//'endOfRecords' => $eor,
			'count' => $fullCnt,
			'results' => $result
		];
		return response()->json($retObj);
	}

	public function showCharacterAttributes_v2($id, Request $request){
		$this->validate($request, [
				'limit' => 'integer',
				'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$morphObj = MorphologyAttribute::query();
		$morphObj->where('cid',$id);
		$morphObj->with(['taxonomy' => function ($query) {
			$query->select('tid','sciName');
		}]);
			$morphObj->with(['state' => function ($query) {
				$query->select('stateID','charStateName');
			}]);
				$fullCnt = $morphObj->count();
				if($fullCnt){

				}
				$result = $morphObj->skip($offset)->take($limit)->get();

				$eor = false;
				$retObj = [
						'offset' => (int)$offset,
						'limit' => (int)$limit,
						//'endOfRecords' => $eor,
						'count' => $fullCnt,
						'results' => $result
				];
				return response()->json($retObj);
	}
}