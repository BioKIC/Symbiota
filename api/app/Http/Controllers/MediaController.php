<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Occurrence;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MediaController extends Controller{

	private $rulesInsert = [
			'apiToken' => 'required',
			'originalUrl' => 'required',
			'format' => 'required',
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
		unset($this->rulesUpdate['format']);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/media",
	 *	 operationId="/api/v2/media",
	 *	 tags={""},
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns list of media records",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllMedia(){
		return response()->json(Media::skip(0)->take(100)->get());
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/media/{identifier}",
	 *	 operationId="/api/v2/media/identifier",
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
	 *		 description="Returns single media record",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Media identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOneMedia($id){
		return response()->json(Media::find($this->getImgid($id)));
	}

	/**
	 * @OA\Post(
	 *	 path="/api/v2/media",
	 *	 operationId="/api/v2/media",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="apiToken",
	 *		 in="query",
	 *		 description="API security token to authenticate post action",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="format",
	 *		 in="query",
	 *		 description="Media Type (MIME type)",
	 *		 required=true,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="originalUrl",
	 *		 in="query",
	 *		 description="URL returning original large image; image should be a web-ready JPG  ",
	 *		 required=true,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="mediumUrl",
	 *		 in="query",
	 *		 description="URL returning medium sized image of original large image; image should be a web-ready JPG  ",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="thumbnailUrl",
	 *		 in="query",
	 *		 description="URL returning thumbnail derivative of original large image; image should be a web-ready JPG  ",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="archiveUrl",
	 *		 in="query",
	 *		 description="URL returning large archival image (e.g. DNG, TIFF, etc), if web accessible ",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="referenceUrl",
	 *		 in="query",
	 *		 description="",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="occid",
	 *		 in="query",
	 *		 description="Primary Key of occurrence record that media object is associated with. Should be left null if record is not associated with an occurrence.",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="tid",
	 *		 in="query",
	 *		 description="Primary Key of taxon record (e.g. scientific name reference) associated with media object. Should be left NULL if record is associated with an occurrence, since media will inherit occurrence identification. Required if media is not associated with an occurrence record (e.g. field image)",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="photographer",
	 *		 in="query",
	 *		 description="Verbatim name of author/photographer media object. Leave NULL if photographerUid is supplied",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="photographerUid",
	 *		 in="query",
	 *		 description="User Primary Key of photographer, who must have a user account register in the portal",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="imageType",
	 *		 in="query",
	 *		 description="Term name of image type (e.g. stillImage)",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="caption",
	 *		 in="query",
	 *		 description="Consist, free-form text describing content of media object",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="copyright",
	 *		 in="query",
	 *		 description="Copyright Owner",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="rights",
	 *		 in="query",
	 *		 description="A URI pointing to structured information about rights held in and over the resource (e.g. https://creativecommons.org/publicdomain/zero/1.0/, https://creativecommons.org/licenses/by/4.0/legalcode)",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="accessRights",
	 *		 in="query",
	 *		 description="Information about who can access the resource or an indication of its security status",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="locality",
	 *		 in="query",
	 *		 description="Short description of locality. Leave null if locality is available via the occurrence record",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=250)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="notes",
	 *		 in="query",
	 *		 description="General notes",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=350)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="sourceIdentifier",
	 *		 in="query",
	 *		 description="The source identifier, if media was harvested from an external resource",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=150)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="hashFunction",
	 *		 in="query",
	 *		 description="Cryptographic hash function used to compute the value given in the Hash Value",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="hashValue",
	 *		 in="query",
	 *		 description="Value computed by a hash function applied to the media that will be delivered at the access point",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="201",
	 *		 description="Returns UUID recordID (GUID)",
	 *		 @OA\JsonContent()
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
			$request->validate($this->rulesInsert);

			$inputArr = $request->all();
			$this->adjustInputData($inputArr);
			$media = Media::create($inputArr);
			return response()->json($media, 201);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

	/**
	 * @OA\Patch(
	 *	 path="/api/v2/media/{identifier}",
	 *	 operationId="/api/v2/media/identifier",
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
	 *	 @OA\Parameter(
	 *		 name="format",
	 *		 in="query",
	 *		 description="Media Type (MIME type)",
	 *		 required=true,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="originalUrl",
	 *		 in="query",
	 *		 description="URL returning original large image; image should be a web-ready JPG  ",
	 *		 required=true,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="mediumUrl",
	 *		 in="query",
	 *		 description="URL returning medium sized image of original large image; image should be a web-ready JPG  ",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="thumbnailUrl",
	 *		 in="query",
	 *		 description="URL returning thumbnail derivative of original large image; image should be a web-ready JPG  ",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="archiveUrl",
	 *		 in="query",
	 *		 description="URL returning large archival image (e.g. DNG, TIFF, etc), if web accessible ",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="referenceUrl",
	 *		 in="query",
	 *		 description="",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="occid",
	 *		 in="query",
	 *		 description="Primary Key of occurrence record that media object is associated with. Should be left null if record is not associated with an occurrence.",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="tid",
	 *		 in="query",
	 *		 description="Primary Key of taxon record (e.g. scientific name reference) associated with media object. Should be left NULL if record is associated with an occurrence, since media will inherit occurrence identification. Required if media is not associated with an occurrence record (e.g. field image)",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="photographer",
	 *		 in="query",
	 *		 description="Verbatim name of author/photographer media object. Leave NULL if photographerUid is supplied",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="photographerUid",
	 *		 in="query",
	 *		 description="User Primary Key of photographer, who must have a user account register in the portal",
	 *		 required=false,
	 *		 @OA\Schema(type="integer")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="imageType",
	 *		 in="query",
	 *		 description="Term name of image type (e.g. stillImage)",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="caption",
	 *		 in="query",
	 *		 description="Consist, free-form text describing content of media object",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="copyright",
	 *		 in="query",
	 *		 description="Copyright Owner",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="rights",
	 *		 in="query",
	 *		 description="A URI pointing to structured information about rights held in and over the resource (e.g. https://creativecommons.org/publicdomain/zero/1.0/, https://creativecommons.org/licenses/by/4.0/legalcode)",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="accessRights",
	 *		 in="query",
	 *		 description="Information about who can access the resource or an indication of its security status",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=255)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="locality",
	 *		 in="query",
	 *		 description="Short description of locality. Leave null if locality is available via the occurrence record",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=250)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="notes",
	 *		 in="query",
	 *		 description="General notes",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=350)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="sourceIdentifier",
	 *		 in="query",
	 *		 description="The source identifier, if media was harvested from an external resource",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=150)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="hashFunction",
	 *		 in="query",
	 *		 description="Cryptographic hash function used to compute the value given in the Hash Value",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="hashValue",
	 *		 in="query",
	 *		 description="Value computed by a hash function applied to the media that will be delivered at the access point",
	 *		 required=false,
	 *		 @OA\Schema(type="string", maxLength=45)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="201",
	 *		 description="Returns UUID recordID (GUID)",
	 *		 @OA\JsonContent()
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
			$media = Media::findOrFail($id);
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
	 *	 operationId="/api/v2/media/identifier",
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
	 *		 response="200",
	 *		 description="Record deleted successfully",
	 *		 @OA\JsonContent()
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
			Media::findOrFail($id)->delete();
			return response('Media object deleted successfully', 200);
		}
		return response()->json(['error' => 'Unauthorized'], 401);
	}

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
}