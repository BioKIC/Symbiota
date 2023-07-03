<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Occurrence;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MediaController extends Controller{
	/**
	 * Media controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
		parent::__construct();
	}

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
	 *		 in="path",
	 *		 description="API security token to authenicate post action",
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
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns UUID recordID (GUID)",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request.",
	 *	 ),
	 * )
	 */
	public function insertOccurrenceMedia(Request $request){
		if($user = $this->authenicate($request)){
			$this->validate($request, [
				'occid' => 'integer',
				'originalUrl' => 'required',
				//'format' => '',
				'occid' => 'integer',
				'tid' => 'integer',
				'photographerUid' => 'integer'
			]);
			$inputArr = $request->all();
			$occid = $request->input('occid');
			if(!$occid){
				if($guid = $request->input('guid')){
					$occid = Occurrence::where('occurrenceID', $guid)->value('occid');
					if(!$occid) $occid = DB::table('guidoccurrences')->where('guid', $guid)->value('occid');
				}
				if(!$occid){
					if($catalogNumber = $request->input('catalogNumber')){
						$occid = Occurrence::where('catalogNumber', $catalogNumber)->value('occid');
					}
				}
				if($occid) $inputArr['occid'] = $occid;
			}
			if($occid){
				//Media object is to be linked to an occurrence
				//Check if user is authorized to modify target collection
				Occurrence::where()->value('');

				$media = Media::create($inputArr);
			}
			else{
				return response()->json(['error' => 'Unauthorized'], 401);
			}

			return response()->json($media, 201);
		}

		return response()->json(['error' => 'Unauthorized'], 401);
	}

	public function update($id, Request $request){
		$media = Media::findOrFail($id);
		$media->update($request->all());

		return response()->json($media, 200);
	}

	public function delete($id){
		Media::findOrFail($id)->delete();
		return response('Media object deleted successfully', 200);
	}

	private function getImgid($id){
		if(!is_numeric($id)){
			$imgId = Media::where('recordID', $id)->value('imgid');
			if(is_numeric($imgId)) $id = $imgId;
		}
		return $id;
	}
}