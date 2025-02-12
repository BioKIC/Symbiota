<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\TaxonomyController;

class MediaController extends Controller{

	private $rulesInsert = [
		'apiToken' => 'required',
		'originalUrl' => 'required',
		'occid' => 'integer|exists:omoccurrences,occid',
		'tid' => 'integer|exists:taxa,tid',
		'photographerUid' => 'integer|exists:users,uid'
	];

	private $rulesUpdate = [];

	/**
	 * Media controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
		parent::__construct();
		$this->rulesUpdate = $this->rulesInsert;
		unset($this->rulesUpdate['originalUrl']);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/media",
	 *	 operationId="showAllMedia",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="tid",
	 *		 in="query",
	 *		 description="Display media filtered by target taxon ID (PK key of taxon)",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="includeSynonyms",
	 *		 in="query",
	 *		 description="Include media linked to synonyms of target taxon (0 = false, 1 = true)",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="includeChildren",
	 *		 in="query",
	 *		 description="Include media linked to direct children of target taxon (0 = false, 1 = true)",
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
	 *		 description="Determines the starting point for the search results. A limit of 100 and offset of 200, will display 100 records starting the 200th record.",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Success: Returns list of media records",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllMedia(Request $request){

		$this->validate($request, [
			'tid' => 'integer',
			'includeSynonyms' => 'integer',
			'includeChildren' => 'integer',
			'limit' => 'integer',
			'offset' => 'integer'
		]);
		$tid = $request->input('tid');
		$includeSynonyms = $request->input('includeSynonyms');
		$includeChildren = $request->input('includeChildren');
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$mediaModel = Media::query();
		if($tid){
			$tidArr = array($tid);
			if($includeSynonyms){
				$tidArr = TaxonomyController::getSynonyms($tid);
			}
			if($includeChildren){
				$tidArr = array_merge($tidArr, TaxonomyController::getChildren($tid));
			}
			$mediaModel->whereIn('tid', $tidArr);
		}
		$fullCnt = $mediaModel->count();
		$result = $mediaModel->skip($offset)->take($limit)->get();

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
	 *	 path="/api/v2/media/{identifier}",
	 *	 operationId="showOneMedia",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="primary key or record GUID (UUID) associated with target media object",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Success: Returns single media record",
	 *		 @OA\JsonContent(type="application/json")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Media identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneMedia($id){
		$media = Media::find($this->getImgid($id));
		if(!$media){
			return response()->json(['status' => 'failure', 'error' => 'Media resource not found'], 400);
		}
		return response()->json($media);
	}

	/**
	 * @OA\Post(
	 *	 path="/api/v2/media",
	 *	 operationId="insertMedia",
	 *	 summary="Creates a new Media record",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="apiToken",
	 *		 in="query",
	 *		 description="API security token to authenticate post action",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\RequestBody(
	 *		required=true,
	 *		description="Media object to create",
     *		@OA\MediaType(
     *			mediaType="application/json",
     *			@OA\Schema(
	 *				required={"originalUrl"},
     *				@OA\Property(
     *					property="format",
     *					type="string",
	 *					description="Media Type (MIME type)",
	 *					maxLength=45,
	 *					enum={"image/jpeg", "image/png", "image/tiff", "audio/wav"}
     *				),
     *				@OA\Property(
     *					property="originalUrl",
     *					type="string",
	 *					description="URL of original media file; images should be a web-ready JPG",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="mediumUrl",
     *					type="string",
	 *					description="URL of medium sized image of original image",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="thumbnailUrl",
     *					type="string",
	 *					description="URL of thumbnail representation original media file",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="archiveUrl",
     *					type="string",
	 *					description="URL of archival media file (e.g. DNG, TIFF, etc), if publicly web accessible",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="referenceUrl",
     *					type="string",
	 *					description="URL of a web representation of media file. Maybe consist of html wrapper, viewer, player, etc",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="occid",
     *					type="integer",
	 *					description="Primary Key of occurrence record that media object is associated with. Should be left null if record is not associated with an occurrence.",
     *				),
     *				@OA\Property(
     *					property="tid",
     *					type="integer",
	 *					description="Primary Key of taxon record (e.g. scientific name reference) associated with media object. Should be left NULL if record is associated with an occurrence, since media will inherit occurrence identification. Required if media is not associated with an occurrence record (e.g. field image)",
     *				),
     *				@OA\Property(
     *					property="photographer",
     *					type="string",
	 *					description="Verbatim name of author/photographer media object. Leave NULL if photographerUid is supplied",
	 *					maxLength=100
     *				),
     *				@OA\Property(
     *					property="photographerUid",
     *					type="integer",
	 *					description="User Primary Key of photographer, who must have a user account register in the portal",
     *				),
     *				@OA\Property(
     *					property="imageType",
     *					type="string",
	 *					description="Term name of image type (e.g. stillImage)",
	 *					maxLength=45
     *				),
     *				@OA\Property(
     *					property="caption",
     *					type="string",
	 *					description="Consist, free-form text describing content of media object",
	 *					maxLength=100
     *				),
     *				@OA\Property(
     *					property="copyright",
     *					type="string",
	 *					description="Copyright Owner",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="rights",
     *					type="string",
	 *					description="A URI pointing to structured information about rights held in and over the resource (e.g. https://creativecommons.org/publicdomain/zero/1.0/, https://creativecommons.org/licenses/by/4.0/legalcode)",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="accessRights",
     *					type="string",
	 *					description="Information about who can access the resource or an indication of its security status",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="locality",
     *					type="string",
	 *					description="Short description of locality. Leave null if locality is available via the occurrence record",
	 *					maxLength=250
     *				),
     *				@OA\Property(
     *					property="notes",
     *					type="string",
	 *					description="General notes",
	 *					maxLength=350
     *				),
     *				@OA\Property(
     *					property="sourceIdentifier",
     *					type="string",
	 *					description="The source identifier, if media was harvested from an external resource",
	 *					maxLength=150
     *				),
     *				@OA\Property(
     *					property="hashFunction",
     *					type="string",
	 *					description="Cryptographic hash function used to compute the value given in the Hash Value",
	 *					maxLength=45
     *				),
     *				@OA\Property(
     *					property="hashValue",
     *					type="string",
	 *					description="Value computed by a hash function applied to the media that will be delivered at the access point",
	 *					maxLength=45
     *				),
     *				@OA\Property(
     *					property="sortSequence",
     *					type="integer",
	 *					description="Media sort control within the taxon profile page"
     *				),
     *				@OA\Property(
     *					property="sortOccurrence",
     *					type="integer",
	 *					description="Media sort control within the occurrence profile page"
     *				),
     *			),
	 *		)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="201",
	 *		 description="Success: Returns JSON object of the of media record that was created"
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
	public function insert(Request $request){
		if($user = $this->authenticate($request)){
			$this->validate($request, $this->rulesInsert);
			$inputArr = $request->all();
			$this->adjustInputData($inputArr);
			$inputArr['recordID'] = (string) Str::uuid();
			if(!isset($inputArr['format'])){
				$mimeType = $this->getMimeType($inputArr['originalUrl']);
				if($mimeType && strpos($mimeType, 'text/html') === false){
					$inputArr['format'] = $mimeType;
				}
				else{
					return response()->json(['error' => 'format field required; unable to determine mime type dynamically'], 401);
				}
			}
			$media = Media::create($inputArr);
			return response()->json($media, 201);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	/**
	 * @OA\Patch(
	 *	 path="/api/v2/media/{identifier}",
	 *	 operationId="updateMedia",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="primary key or record GUID (UUID) associated with target media object",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="apiToken",
	 *		 in="query",
	 *		 description="API security token to authenticate post action",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\RequestBody(
	 *		required=true,
	 *		description="Media object to be updated",
     *		@OA\MediaType(
     *			mediaType="application/json",
     *			@OA\Schema(
     *				@OA\Property(
     *					property="format",
     *					type="string",
	 *					description="Media Type (MIME type)",
	 *					maxLength=45,
	 *					enum={"image/jpeg", "image/png", "image/tiff", "audio/wav"}
     *				),
     *				@OA\Property(
     *					property="originalUrl",
     *					type="string",
	 *					description="URL of original media file; images should be a web-ready JPG",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="mediumUrl",
     *					type="string",
	 *					description="URL of medium sized image of original image",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="thumbnailUrl",
     *					type="string",
	 *					description="URL of thumbnail representation original media file",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="archiveUrl",
     *					type="string",
	 *					description="URL of archival media file (e.g. DNG, TIFF, etc), if publicly web accessible",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="referenceUrl",
     *					type="string",
	 *					description="URL of a web representation of media file. Maybe consist of html wrapper, viewer, player, etc",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="occid",
     *					type="integer",
	 *					description="Primary Key of occurrence record that media object is associated with. Should be left null if record is not associated with an occurrence.",
     *				),
     *				@OA\Property(
     *					property="tid",
     *					type="integer",
	 *					description="Primary Key of taxon record (e.g. scientific name reference) associated with media object. Should be left NULL if record is associated with an occurrence, since media will inherit occurrence identification. Required if media is not associated with an occurrence record (e.g. field image)",
     *				),
     *				@OA\Property(
     *					property="photographer",
     *					type="string",
	 *					description="Verbatim name of author/photographer media object. Leave NULL if photographerUid is supplied",
	 *					maxLength=100
     *				),
     *				@OA\Property(
     *					property="photographerUid",
     *					type="integer",
	 *					description="User Primary Key of photographer, who must have a user account register in the portal",
     *				),
     *				@OA\Property(
     *					property="imageType",
     *					type="string",
	 *					description="Term name of image type (e.g. stillImage)",
	 *					maxLength=45
     *				),
     *				@OA\Property(
     *					property="caption",
     *					type="string",
	 *					description="Consist, free-form text describing content of media object",
	 *					maxLength=100
     *				),
     *				@OA\Property(
     *					property="copyright",
     *					type="string",
	 *					description="Copyright Owner",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="rights",
     *					type="string",
	 *					description="A URI pointing to structured information about rights held in and over the resource (e.g. https://creativecommons.org/publicdomain/zero/1.0/, https://creativecommons.org/licenses/by/4.0/legalcode)",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="accessRights",
     *					type="string",
	 *					description="Information about who can access the resource or an indication of its security status",
	 *					maxLength=255
     *				),
     *				@OA\Property(
     *					property="locality",
     *					type="string",
	 *					description="Short description of locality. Leave null if locality is available via the occurrence record",
	 *					maxLength=250
     *				),
     *				@OA\Property(
     *					property="notes",
     *					type="string",
	 *					description="General notes",
	 *					maxLength=350
     *				),
     *				@OA\Property(
     *					property="sourceIdentifier",
     *					type="string",
	 *					description="The source identifier, if media was harvested from an external resource",
	 *					maxLength=150
     *				),
     *				@OA\Property(
     *					property="hashFunction",
     *					type="string",
	 *					description="Cryptographic hash function used to compute the value given in the Hash Value",
	 *					maxLength=45
     *				),
     *				@OA\Property(
     *					property="hashValue",
     *					type="string",
	 *					description="Value computed by a hash function applied to the media that will be delivered at the access point",
	 *					maxLength=45
     *				),
     *				@OA\Property(
     *					property="sortSequence",
     *					type="integer",
	 *					description="Media sort control within the taxon profile page"
     *				),
     *				@OA\Property(
     *					property="sortOccurrence",
     *					type="integer",
	 *					description="Media sort control within the occurrence profile page"
     *				),
     *			),
	 *		)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Success: Returns full JSON object of the of media record that was edited"
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
	public function update($id, Request $request){
		if($user = $this->authenticate($request)){
			$media = Media::find($this->getImgid($id));
			if(!$media){
				return response()->json(['status' => 'failure', 'error' => 'Media resource not found'], 400);
			}
			$this->validate($request, $this->rulesUpdate);
			$inputArr = $request->all();
			$this->adjustInputData($inputArr);
			$media->update($inputArr);
			return response()->json($media, 200);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	/**
	 * @OA\Delete(
	 *	 path="/api/v2/media/{identifier}",
	 *	 operationId="deleteMedia",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="primary key or record GUID (UUID) associated with target media object",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="apiToken",
	 *		 in="query",
	 *		 description="API security token to authenticate post action",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="204",
	 *		 description="Success: Record deleted successfully"
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Media identifier is required.",
	 *	 ),
	 *	 @OA\Response(
	 *		 response="401",
	 *		 description="Unauthorized",
	 *	 ),
	 * )
	 */
	public function delete($id, Request $request){
		if($user = $this->authenticate($request)){
			$media = Media::find($this->getImgid($id));
			if(!$media){
				return response()->json(['status' => 'failure', 'error' => 'Media resource not found'], 400);
			}
			$media->delete();
			return response('', 200);
		}
		return response()->json(['status' => 'failure', 'error' => 'Unauthorized'], 401);
	}

	//Support functions
	private function adjustInputData(&$data){
		if(!empty($data['mediumUrl'])){
			//remap mediumUrl to url field
			$data['url'] = $data['mediumUrl'];
			unset($data['mediumUrl']);
		}
	}

	private function getImgid($id){
		if(!is_numeric($id)){
			$imgId = Media::where('recordID', $id)->value('imgid');
			if(is_numeric($imgId)) $id = $imgId;
		}
		return $id;
	}

	private function getMimeType($url){
		$mimeType = '';
		$headerArr = get_headers($url);
		foreach($headerArr as $value){
			if(preg_match('/Content-Type: (.*)/', $value, $m)){
				$mimeType = $m[1];
			}
		}
		return $mimeType;
	}
}