<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\UserAccessToken;
use App\Models\UserRole;

include_once('../config/symbini.php');
$_ENV['DEFAULT_TITLE'] = $DEFAULT_TITLE;
$_ENV['PORTAL_GUID'] = $PORTAL_GUID;
$_ENV['SECURITY_KEY'] = $SECURITY_KEY;
$_ENV['DEFAULT_TITLE'] = $DEFAULT_TITLE;
$_ENV['ADMIN_EMAIL'] = $ADMIN_EMAIL;
$_ENV['CLIENT_ROOT'] = $CLIENT_ROOT;
$_ENV['SYMBIOTA_VERSION'] = $CODE_VERSION;
$_ENV['API_VERSION'] = '2.0';


class Controller extends BaseController{
	/**
	 * @OA\Info(
	 *   title="Symbiota API",
	 *   version="2.0",
	 *   @OA\Contact(
	 *     email="symbiota@asu.edu",
	 *     name="Symbiota Support Hub Team"
	 *   )
	 * )
	 */

	/**
	 * @OA\Server(url="../")
	 */

	public function __construct(){
	}

	/**
	 * Authorization of writable actions or sensitive locality requests
	 *
	 * Input: security token
	 * Return: user object with role/permission settings
	 */
	public function authenticate(Request $request){
		$this->validate($request, [
			'apiToken' => 'required'
		]);
		$apiToken = $request->input('apiToken');
		//TODO: convert to an actual user object
		$userArr = false;

		if($_ENV['SECURITY_KEY'] == $apiToken){
			//Matches portal's global security key set by administrator within the symbini.php, which provides administrative level access
			$userArr = array();
			$userArr['roles'][] = array('role' => 'SuperAdmin');
		}
		else{
			//See if security key matches keys associated with collections, if so, request is an admin of collection
			$collid = Collection::where('securityKey', $apiToken)->value('collid');
			$userArr = array();
			if($collid){
				$userArr['roles'][] = array('role' => 'CollAdmin', 'tableName' => 'omcollections', 'tablePK' => $collid);
			}
			else{
				$uid = UserAccessToken::where('token', $apiToken)->value('uid');
				if($uid){
					//Check user security tokens
					$userArr['uid'] = $uid;
					$userArr['roles'] = UserRole::where('uid', $uid)->get(['role', 'tableName', 'tablePk'])->toArray();
				}
			}
		}
		return $userArr;
	}
}
