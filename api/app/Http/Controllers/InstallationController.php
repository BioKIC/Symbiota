<?php
namespace App\Http\Controllers;

use App\Models\PortalIndex;
use Illuminate\Http\Request;

class InstallationController extends Controller{
	/**
	 * Installation controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/installation",
	 *	 operationId="/api/v2/installation",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="limit",
	 *		 in="query",
	 *		 description="Pagination parameter: maximum number of records per page",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="offset",
	 *		 in="query",
	 *		 description="Pagination parameter: page number",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns list of installations registered within system",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function showAllPortals(Request $request){
		$this->validate($request, [
			'limit' => 'integer',
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$fullCnt = PortalIndex::count();
		$result = PortalIndex::skip($offset)->take($limit)->get();

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
	 *	 path="/api/v2/installation/{identifier}",
	 *	 operationId="/api/v2/installation/identifier",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="Installation ID or GUID associated with target installation",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns metabase on installation registered within system with matching ID",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Installation identifier is required.",
	 *	 ),
	 * )
	 */
	public function showOnePortal($id, Request $request){
		$portalObj = null;
		if(is_numeric($id)) $portalObj = PortalIndex::find($id);
		else $portalObj = PortalIndex::where('guid',$id)->first();
		if(!$portalObj->count()) $portalObj = ["status"=>false,"error"=>"Unable to locate installation based on identifier"];
		return response()->json($portalObj);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/installation/status",
	 *	 operationId="/api/v2/installation/status",
	 *	 tags={""},
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns installation metadata",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. ",
	 *	 ),
	 * )
	 */
	public function portalStatus(Request $request){
		$portalObj = null;
		if(isset($_ENV['DEFAULT_TITLE']) && isset($_ENV['PORTAL_GUID'])){
			$portalObj['status'] = true;
			$portalObj['portalName'] = $_ENV['DEFAULT_TITLE'];
			$portalObj['guid'] = $_ENV['PORTAL_GUID'];
			$portalObj['managerEmail'] = $_ENV['ADMIN_EMAIL'];
			$portalObj['urlRoot'] = $this->getServerDomain().$_ENV['CLIENT_ROOT'];
			$portalObj['symbiotaVersion'] = $_ENV['SYMBIOTA_VERSION'];
			$portalObj['apiVersion'] = $_ENV['API_VERSION'];
		}
		else{
			$portalObj['status'] = false;
			if(!isset($_ENV['DEFAULT_TITLE'])) $portalObj['error'][] = 'Portal title is NULL';
			if(!isset($_ENV['PORTAL_GUID'])) $portalObj['error'][] = 'Portal GUID is NULL';
			$portalObj['status'] = false;
		}
		return response()->json($portalObj);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/installation/{identifier}/handshake",
	 *	 operationId="/api/v2/installation/identifier/handshake",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="Identifier of the remote installation (GUID)",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="endpoint",
	 *		 in="query",
	 *		 description="Url to Symbiota root of remote installation",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns metabase remote installation, if successfully registered",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Identifier of remote installation is required.",
	 *	 ),
	 * )
	 */
	public function portalHandshake($id, Request $request){
		$responseArr = array( 'status' => 500, 'error' => 'Unknown error' );
		$portalObj = PortalIndex::where('guid',$id)->get();
		if($portalObj->count()){
			$responseArr['status'] = 200;
			$responseArr['message'] = 'Portal previously registered';
			unset($responseArr['error']);
		}
		elseif($id == $_ENV['PORTAL_GUID']){
			//Make sure handshake isn't referring to self; portals can't handshake with themselves
			$responseArr['status'] = 400;
			$responseArr['error'] = 'Registration failed: handshake is referencing self';
		}
		elseif($request->has('endpoint')){
			//Remote installation not yet in system, thus add and then process list from remote
			if($baseUrl = $request->input('endpoint')){
				//Insert portal
				$urlPing = $baseUrl.'/api/v2/installation/status';
				if($remote = $this->getAPIResponce($urlPing)){
					if($id == $remote['guid']){
						//Shake back just to makes sure remote knows about self
						$remoteHandshake = $baseUrl.'/api/v2/installation/'.$_ENV['PORTAL_GUID'].'/handshake?endpoint='.htmlentities($this->getServerDomain().$_ENV['CLIENT_ROOT']);
						$this->getAPIResponce($remoteHandshake, true);
						try {
							//Register remote
							$portalObj = PortalIndex::create($remote);
							$responseArr['status'] = 200;
							$responseArr['message'] = 'Remote portal registered successfully';
							unset($responseArr['error']);
							//Register all portals listed within remote, if not alreay registered
							$urlInstallation = $baseUrl.'/api/v2/installation';
							if($remoteInstallationArr = $this->getAPIResponce($urlInstallation)){
								$currentRegistered = 0;
								$newRegistration = 0;
								foreach($remoteInstallationArr['results'] as $portal){
									if(PortalIndex::where('guid',$portal['guid'])->count()) $currentRegistered++;
									elseif($portal['guid'] != $_ENV['PORTAL_GUID']){
										//If remote exists, add by retriving info directly from source
										$remotePing = $portal['urlRoot'].'/api/v2/installation/status';
										if($newRemote = $this->getAPIResponce($remotePing)){
											PortalIndex::create($newRemote);
											//Handshake remote installation but don't wait for a response because propagation across a large network can take awhile
											$urlHandshake = $portal['urlRoot'].'/api/v2/installation/'.$_ENV['PORTAL_GUID'].'/handshake?endpoint='.htmlentities($this->getServerDomain().$_ENV['CLIENT_ROOT']);
											$this->getAPIResponce($urlHandshake, true);
											$newRegistration++;
										}
									}
								}
								$responseArr['Currently registered remotes'] = $currentRegistered;
								$responseArr['Newly registrations remotes'] = $newRegistration;
							}
							else $responseArr['error'] = 'Unable to obtain remote installation listing: '.$urlInstallation;
						}
						catch(\Illuminate\Database\QueryException $ex){
							$responseArr['status'] = 503;
							$responseArr['error'] = 'Registration failed: Unable insert database record: '.$ex->getMessage();
						}
					}
					else{
						$responseArr['status'] = 422;
						$responseArr['error'] = 'Registration failed: Supplied and returned remote GUIDs not matching ('.$id.' != '.$remote['guid'].')  ';
					}
				}
				else{
					$responseArr['status'] = 422;
					$responseArr['error'] = 'Registration failed: Endpoint illegal or non-functional: '.$urlPing;
				}
			}
		}
		else{
			$responseArr['status'] = 422;
			$responseArr['error'] = 'Registration failed: Unable to obtain portal endpoint';
		}
		$responseArr['results'] = $portalObj;
		return response()->json($responseArr);
	}

	/**
	 * @OA\Get(
	 *	 path="/api/v2/installation/{identifier}/occurrence",
	 *	 operationId="/api/v2/installation/identifier/occurrence",
	 *	 tags={""},
	 *	 @OA\Parameter(
	 *		 name="identifier",
	 *		 in="path",
	 *		 description="Identifier of the remote installation",
	 *		 required=true,
	 *		 @OA\Schema(type="string")
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="limit",
	 *		 in="query",
	 *		 description="Pagination parameter: maximum number of records per page",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=100)
	 *	 ),
	 *	 @OA\Parameter(
	 *		 name="offset",
	 *		 in="query",
	 *		 description="Pagination parameter: page number",
	 *		 required=false,
	 *		 @OA\Schema(type="integer", default=0)
	 *	 ),
	 *	 @OA\Response(
	 *		 response="200",
	 *		 description="Returns list of occurrences associated with an installations",
	 *		 @OA\JsonContent()
	 *	 ),
	 *	 @OA\Response(
	 *		 response="400",
	 *		 description="Error: Bad request. Identifier of remote installation is required.",
	 *	 ),
	 * )
	 */
	public function showOccurrences($id, Request $request){
		$this->validate($request, [
			'verification' => ['integer'],
			'limit' => ['integer', 'max:1000'],
			'offset' => 'integer'
		]);
		$limit = $request->input('limit',100);
		$offset = $request->input('offset',0);

		$portalObj = null;
		if(is_numeric($id)) $portalObj = PortalIndex::find($id);
		else $portalObj = PortalIndex::where('guid',$id)->first();

		$retObj = [];
		if($portalObj){
			$conditions = [];
			if($request->has('verification')){
				if($request->verification) $conditions[] = ['verification',1];
				else $conditions[] = ['verification',0];
			}
			$fullCnt = $portalObj->portalOccurrences()->where($conditions)->count();
			$result = $portalObj->portalOccurrences()->where($conditions)->skip($offset)->take($limit)->get();
			$eor = false;
			$retObj = [
				"offset" => (int)$offset,
				"limit" => (int)$limit,
				"endOfRecords" => $eor,
				"count" => $fullCnt,
				"results" => $result
			];
		}
		else $retObj = ["status"=>false,"error"=>"Unable to locate installation based on identifier"];
		return response()->json($retObj);
	}

	public function create(Request $request){
		/*
		$validated = $request->validate([
			'portalName' => 'required|max:45',
			'urlRoot' => 'required|max:150',
		]);
		$portalIndex = PortalIndex::create($request->all());

		return response()->json($portalIndex, 201);
		*/
	}

	public function update($id, Request $request){
		/*
		$portalIndex = PortalIndex::findOrFail($id);
		$portalIndex->update($request->all());

		return response()->json($portalIndex, 200);
		*/
	}

	public function delete($id){
		/*
		PortalIndex::findOrFail($id)->delete();
		return response('Portal Index deleted successfully', 200);
		*/
	}

	//Helper functions
	private function getAPIResponce($url, $asyc = false){
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

	private function getServerDomain(){
		$domain = 'http://';
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domain = 'https://';
		if(!empty($GLOBALS['SERVER_HOST'])){
			if(substr($GLOBALS['SERVER_HOST'], 0, 4) == 'http') $domain = $GLOBALS['SERVER_HOST'];
			else $domain .= $GLOBALS['SERVER_HOST'];
		}
		else $domain .= $_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 && !strpos($domain, ':'.$_SERVER['SERVER_PORT'])){
			$domain .= ':'.$_SERVER['SERVER_PORT'];
		}
		$domain = filter_var($domain, FILTER_SANITIZE_URL);
		return $domain;
	}
}