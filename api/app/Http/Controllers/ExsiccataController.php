<?php

namespace App\Http\Controllers;

use App\Models\Exsiccata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExsiccataController extends Controller {

    /**
     * Exsiccata controller instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @OA\Get(
     *	 path="/api/v2/exsiccata",
     *	 operationId="/api/v2/exsiccata",
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
    public function showAllExsiccata(Request $request) {
        $this->validate($request, [
            'limit' => 'integer',
            'offset' => 'integer'
        ]);
        $limit = $request->input('limit', 100);
        $offset = $request->input('offset', 0);

        $fullCnt = Exsiccata::count();
        $result = Exsiccata::skip($offset)->take($limit)->get();

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
     *     @OA\Get(
     *	   path="/api/v2/exsiccata/{identifier}",
     *	   operationId="/api/v2/exsiccata/{identifier}",
     *	   tags={""},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="Exsiccata ID or record ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns exsiccata record with matching identifier",
     *         @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *		response="400",
    *		description="Error: Bad request. Valid Exsiccata identifier required",
    *      ),
    *     @OA\Response(
    *         response=404,
    *         description="Record not found"
    *     )
    * )
    */

    public function showExsiccata($identifier) {
        $record = DB::table('omexsiccatititles')
            ->where('ometid', $identifier)
            ->orWhere('recordID', $identifier)
            ->first();

        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        unset($record['lasteditedby']);

        return response()->json($record);
    }
}