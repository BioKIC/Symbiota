<?php

namespace App\Http\Controllers;

use App\Models\Exsiccata;
use App\Models\ExsiccataNumber;
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
     *         response="200",
     *         description="Returns exsiccata record with matching identifier",
     *         @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *		response="400",
     *		description="Error: Bad request. Valid Exsiccata identifier required",
     *      ),
     *     @OA\Response(
     *         response="404",
     *         description="Record not found"
     *     )
     * )
     */

    public function showExsiccata($identifier) {
        $record = Exsiccata::where('ometid', $identifier)
            ->orWhere('recordID', $identifier)
            ->first();
        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        return response()->json($record);
    }


    /**
     * @OA\Get(
     *	 path="/api/v2/exsiccata/{identifier}/number",
     *	 operationId="/api/v2/exsiccata/identifier/number",
     *	 tags={""},
     *	 @OA\Parameter(
     *		 name="identifier",
     *		 in="path",
     *		 description="Identifier (ometid (PK) - currently does not accommodate recordID) associated with target exsiccata title",
     *		 required=true,
     *		 @OA\Schema(type="integer")
     *	 ),
     *   @OA\Parameter(
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
     *		 description="Returns all exsiccata numbers associated with a single exsiccati title corresponding to matching identifier",
     *		 @OA\JsonContent()
     *	 ),
     *	 @OA\Response(
     *		 response="400",
     *		 description="Error: Bad request. Exsiccata identifier is required.",
     *	 ),
     *   @OA\Response(
     *      response="404",
     *      description="Record not found"
     *   )
     * )
     */
    public function showOneExsiccataNumbers($identifier, Request $request) {
        $this->validate($request, [
            'limit' => 'integer',
            'offset' => 'integer'
        ]);
        $limit = $request->input('limit', 100);
        $offset = $request->input('offset', 0);

        $exsiccataQuery = Exsiccata::query();

        $exsiccataQuery->where('ometid', $identifier);

        // @TODO When recordID is added replace the ->where() statement above with this logic block
        // if(is_numeric($identifier)){
        //     $exsiccataQuery->where('ometid', $identifier);
        // } else{
        //     $exsiccataQuery->where('recordID', $identifier);
        // }
        $exsiccata = $exsiccataQuery->first();


        if (!$exsiccata) {
            return response()->json(["status" => false, "error" => "Unable to locate exsiccata based on identifier"], 404);
        }

        $numberQuery = ExsiccataNumber::where('ometid', $exsiccata->ometid)->select('omenid', 'exsnumber', 'notes', 'initialtimestamp')->skip($offset)->take($limit);

        $fullCnt = $numberQuery->count();
        $result = $numberQuery->get();

        $retObj = [
            'offset' => (int)$offset,
            'limit' => (int)$limit,
            'count' => $fullCnt,
            'results' => $result,
        ];
        return response()->json($retObj);
    }

    /**
     * @OA\Get(
     *	 path="/api/v2/exsiccata/{identifier}/number/{numberIdentifier}/",
     *	 operationId="/api/v2/exsiccata/identifier/number/{numberIdentifier}/",
     *	 tags={""},
     *	 @OA\Parameter(
     *		 name="identifier",
     *		 in="path",
     *		 description="Identifier (ometid (PK)) associated with target exsiccata number",
     *		 required=true,
     *		 @OA\Schema(type="integer")
     *	 ),
     *	 @OA\Parameter(
     *		 name="numberIdentifier",
     *		 in="path",
     *		 description="Identifier (omenid or recordID in the schema) associated with target exsiccata number",
     *		 required=true,
     *		 @OA\Schema(type="integer")
     *	 ),
     *   @OA\Parameter(
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
     *		 description="Returns all exsiccata numbers associated with a single exsiccati title corresponding to matching identifier",
     *		 @OA\JsonContent()
     *	 ),
     *	 @OA\Response(
     *		 response="400",
     *		 description="Error: Bad request. Exsiccata identifier is required.",
     *	 ),
     *   @OA\Response(
     *      response="404",
     *      description="Record not found"
     *   )
     * )
     */
    public function showOneExsiccataNumbersIdentifier($identifier, $numberIdentifier, Request $request) {
        $this->validate($request, [
            'limit' => 'integer',
            'offset' => 'integer'
        ]);
        $limit = $request->input('limit', 100);
        $offset = $request->input('offset', 0);

        $numbersQuery = DB::table('omexsiccatinumbers');
        if (is_numeric($numberIdentifier)) {
            $numbersQuery->where('omenid', $numberIdentifier);
        } else {
            $numbersQuery->where('recordID', $numberIdentifier);
        }
        $numbersResult = $numbersQuery->where('ometid', $identifier)->first();

        if (!$numbersResult) {
            return response()->json(["status" => false, "error" => "Unable to locate exsiccata number based on identifier and numberIdentifier"], 404);
        }

        $retObj = [
            'offset' => (int)$offset,
            'limit' => (int)$limit,
            'results' => $numbersResult,
        ];
        return response()->json($retObj);
    }
     /**
     *     path="/api/v2/exsiccati/{identifier}/number/{numberIdentifier}/occurrence",
     *     operationId="showOccurrencesByExsiccataNumber",
     *     tags={""},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="Exsiccata ID or record ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="numberIdentifier",
     *         in="path",
     *         required=true,
     *         description="Exsiccata number ID (omenid)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns list of occurrences associated with the given exsiccata number",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. Valid Exsiccata identifier and Number identifier required"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Record not found"
     *     )
     * )
     */
    public function showOccurrencesByExsiccataNumber($identifier, $numberIdentifier)
    {
        $record = DB::table('omexsiccatititles')
            ->where('ometid', $identifier)
            ->orWhere('recordID', $identifier)
            ->first();

        if (!$record) {
            return response()->json(['error' => 'Exsiccata record not found'], 404);
        }

        $occurrences = DB::table('omexsiccatiocclink')
            ->where('omenid', $numberIdentifier)
            ->select('occid', 'ranking', 'notes')
            ->get();

        if ($occurrences->isEmpty()) {
            return response()->json(['error' => 'Unable to locate occurrences based on exsiccata number'], 404);
        }

        return response()->json($occurrences);
    }
}
