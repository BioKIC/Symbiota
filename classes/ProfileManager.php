<?php
include_once('Manager.php');
include_once('Person.php');
include_once('Encryption.php');
@include_once 'Mail.php';

class ProfileManager extends Manager{

	private $rememberMe = false;
	private $uid;
	private $userName;
	private $displayName;
	private $token;

	public function __construct($connType = 'readonly'){
		parent::__construct(null, $connType);
	}

 	public function __destruct(){
 		parent::__destruct();
	}

	private function resetConnection(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
	}

	public function reset(){
		$domainName = filter_var($_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL);
		if($domainName == 'localhost') $domainName = false;
		setcookie('SymbiotaCrumb', '', time() - 3600, ($GLOBALS['CLIENT_ROOT']?$GLOBALS['CLIENT_ROOT']:'/'), $domainName, false, true);
		setcookie('SymbiotaCrumb', '', time() - 3600, ($GLOBALS['CLIENT_ROOT']?$GLOBALS['CLIENT_ROOT']:'/'));
		unset($_SESSION['userrights']);
		unset($_SESSION['userparams']);
	}

	public function authenticate($pwdStr = ''){
		$status = false;
		unset($_SESSION['userrights']);
		unset($_SESSION['userparams']);
		if($this->userName){
			if($this->token){
				$status = $this->authenticateUsingToken();
			}
			elseif($pwdStr){
				$status = $this->authenticateUsingPassword($pwdStr);
			}
			else{
				if($GLOBALS['IS_ADMIN']) $status = $this->authenticateLoginAs();
				else return false;
			}
			if($status){
				if(strlen($this->displayName) > 15) $this->displayName = $this->userName;
				if(strlen($this->displayName) > 15) $this->displayName = substr($this->displayName,0,10).'...';
				$this->reset();
				$this->setUserRights();
				$this->setUserParams();
				if($this->rememberMe) $this->setTokenCookie();
				if(!isset($GLOBALS['SYMB_UID']) || !$GLOBALS['SYMB_UID']){
					$this->resetConnection();
					$sql = 'UPDATE users SET lastLoginDate = NOW() WHERE (uid = ?)';
					if($stmt = $this->conn->prepare($sql)){
						$stmt->bind_param('i', $this->uid);
						$stmt->execute();
						$stmt->close();
					}
				}
			}
		}
		return $status;
	}

	private function authenticateUsingToken(){
		$status = false;
		if($this->token){
			$sql = 'SELECT u.uid, u.firstname FROM users u INNER JOIN useraccesstokens t ON u.uid = t.uid WHERE (t.token = ?) AND ((u.username = ?) OR (u.email = ?)) ';
			if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('sss', $this->token, $this->userName, $this->userName)){
					$stmt->execute();
					$stmt->bind_result($this->uid, $this->displayName);
					if($stmt->fetch()) $status = true;
					$stmt->close();
				}
			}
		}
		return $status;
	}

	private function authenticateUsingPassword($pwdStr){
		$status = false;
		if($pwdStr){
			$sql = 'SELECT uid, firstname FROM users WHERE (password = PASSWORD(?)) AND (username = ? OR email = ?) ';
			if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('sss', $pwdStr, $this->userName, $this->userName)){
					$stmt->execute();
					$stmt->bind_result($this->uid, $this->displayName);
					if($stmt->fetch()) $status = true;
					$stmt->close();
				}
				else echo 'error binding parameters: '.$stmt->error;
			}
			else echo 'error preparing statement: '.$this->conn->error;
		}
		return $status;
	}

	private function authenticateLoginAs(){
		$status = false;
		if($this->userName){
			$sql = 'SELECT uid, firstname FROM users WHERE (username = ?) ';
			if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('s', $this->userName)){
					$stmt->execute();
					$stmt->bind_result($this->uid, $this->displayName);
					if($stmt->fetch()) $status = true;
					$stmt->close();
				}
				else echo 'error binding parameters: '.$stmt->error;
			}
			else echo 'error preparing statement: '.$this->conn->error;
		}
		return $status;
	}

	private function setTokenCookie(){
		$tokenArr = Array();
		if(!$this->token){
			$this->createToken();
		}
		if($this->token){
			$tokenArr[] = $this->userName;
			$tokenArr[] = $this->token;
			$cookieExpire = time() + 60 * 60 * 24 * 30;
			$domainName = filter_var($_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL);
			if ($domainName == 'localhost') $domainName = false;
			setcookie('SymbiotaCrumb', Encryption::encrypt(json_encode($tokenArr)), $cookieExpire, ($GLOBALS['CLIENT_ROOT'] ? $GLOBALS['CLIENT_ROOT'] : '/'), $domainName, false, true);
		}
	}

	public function getPerson(){
		$sqlStr = 'SELECT uid, firstname, lastname, title, institution, department, address, city, state, zip, country, phone, email, '.
			'url, guid, notes, username, lastlogindate FROM users WHERE (uid = '.$this->uid.')';
		$person = new Person();
		$rs = $this->conn->query($sqlStr);
		if($r = $rs->fetch_object()){
			$person->setUid($r->uid);
			$person->setUserName($r->username);
			$person->setLastLoginDate($r->lastlogindate);
			$person->setFirstName($r->firstname);
			$person->setLastName($r->lastname);
			$person->setTitle($r->title);
			$person->setInstitution($r->institution);
			$person->setDepartment($r->department);
			$person->setCity($r->city);
			$person->setState($r->state);
			$person->setZip($r->zip);
			$person->setCountry($r->country);
			$person->setPhone($r->phone);
			$person->setEmail($r->email);
			$person->setGUID($r->guid);
			$this->setUserTaxonomy($person);
		}
		$rs->free();
		return $person;
	}

	public function updateProfile($postArr){
		$firstName = strip_tags($postArr['firstname']);
		$lastName = strip_tags($postArr['lastname']);
		$email = filter_var($postArr['email'], FILTER_VALIDATE_EMAIL);

		$title = array_key_exists('title', $postArr) ? strip_tags($postArr['title']) : '';
		$institution = array_key_exists('institution', $postArr) ? strip_tags($postArr['institution']) : '';
		$city = array_key_exists('city', $postArr) ? strip_tags($postArr['city']) : '';
		$state = array_key_exists('state', $postArr) ? strip_tags($postArr['state']) : '';
		$zip = array_key_exists('zip', $postArr) ? strip_tags($postArr['zip']) : '';
		$country = array_key_exists('country', $postArr) ? strip_tags($postArr['country']) : '';
		$guid = array_key_exists('guid', $postArr) ? strip_tags($postArr['guid']) : '';

		$status = false;
		if($this->uid && $lastName && $email){
			$this->resetConnection();
			$sql = 'UPDATE users SET firstname = ?, lastname = ?, email = ?, title = ?, institution = ?, city = ?, state = ?, zip = ?, country = ?, guid = ? WHERE (uid = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('ssssssssssi', $firstName, $lastName, $email, $title, $institution, $city, $state, $zip, $country, $guid, $this->uid);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating user profile: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement user profile update: '.$this->conn->error;
		}
		return $status;
	}

	public function deleteProfile(){
		$status = false;
		if($this->uid){
			$this->resetConnection();
			$sql = 'DELETE FROM users WHERE (uid = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->uid);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR deleting user profile: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for user profile delete: '.$this->conn->error;
		}
		if($status && $this->uid == $GLOBALS['SYMB_UID']) $this->reset();
		return $status;
	}

	public function changePassword ($newPwd, $oldPwd = "", $isSelf = 0) {
		if($newPwd){
			$this->resetConnection();
			if($isSelf){
				$testStatus = true;
				$sql = 'SELECT uid FROM users WHERE (uid = ?) AND (password = PASSWORD(?))';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('is', $this->uid, $oldPwd);
					$stmt->execute();
					$stmt->store_result();
					if(!$stmt->num_rows){
						$testStatus = false;
					}
					$stmt->close();
					if(!$testStatus) return false;
				}
			}
			if($this->updatePassword($this->uid, $newPwd)) return true;
		}
		return false;
	}

	public function resetPassword($un){
		$newPassword = $this->generateNewPassword();
		$status = false;
		if($un && $newPassword){
			$uid = 0;
			$email = '';
			$un = $this->cleanInStr($un);
			$sql = 'SELECT uid, email FROM users WHERE (username = ?) OR (email = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ss', $un, $un);
				$stmt->execute();
				if($stmt->bind_result($uid, $email)){
					$stmt->fetch();
				}
				$stmt->close();
			}

			if($uid){
				$subject = 'RE: Password reset';
				$serverPath = $this->getDomain().$GLOBALS['CLIENT_ROOT'];
				$from = '';
				if (array_key_exists("SYSTEM_EMAIL", $GLOBALS) && !empty($GLOBALS["SYSTEM_EMAIL"])){
					$from = 'Reset Request <'.$GLOBALS["SYSTEM_EMAIL"].'>';
				}
				$body = 'Your '.$GLOBALS['DEFAULT_TITLE'].' password has been reset to: '.$newPassword.'<br/><br/> '.
					'After logging in, you can change your password by clicking on the My Profile link within the site menu and then selecting the Edit Profile tab. '.
					'If you have problems, contact the System Administrator: '.$GLOBALS['ADMIN_EMAIL'].'<br/><br/>'.
					'Data portal: <a href="'.$serverPath.'">'.$serverPath.'</a><br/>'.
					'Direct link to your user profile: <a href="'.$serverPath.'/profile/viewprofile.php?tabindex=2">'.$serverPath.'/profile/viewprofile.php</a>';

				if($this->sendEmail($email, $subject, $body, $from)){
					$this->resetConnection();
					if($this->updatePassword($uid, $newPassword)){
						$status = $email;
					}
					else{
						$status = false;
						$this->errorMessage = $stmt->error;
					}
				}
			}
		}
		return $status;
	}

	private function updatePassword($uid, $newPassword){
		$status = false;
		$sql = 'UPDATE users SET password = PASSWORD(?) WHERE (uid = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('si', $newPassword, $uid);
			$stmt->execute();
			if($stmt->affected_rows && !$stmt->error) $status = true;
			else $this->errorMessage = $stmt->error;
			$stmt->close();
		}
		return $status;
	}

	private function generateNewPassword(){
		// generate new random password
		$newPassword = "";
		$alphabet = str_split("0123456789abcdefghijklmnopqrstuvwxyz");
		for($i = 0; $i<8; $i++) {
			$newPassword .= $alphabet[rand(0,count($alphabet)-1)];
		}
		return $newPassword;
	}

	public function register($postArr){
		$status = false;

		$firstName = strip_tags($postArr['firstname']);
		$lastName = strip_tags($postArr['lastname']);
		$pwd = $postArr['pwd'];
		$email = filter_var($postArr['email'], FILTER_VALIDATE_EMAIL);

		$title = array_key_exists('title', $postArr) ? strip_tags($postArr['title']) : '';
		$institution = array_key_exists('institution', $postArr) ? strip_tags($postArr['institution']) : '';
		$city = array_key_exists('city', $postArr) ? strip_tags($postArr['city']) : '';
		$state = array_key_exists('state', $postArr) ? strip_tags($postArr['state']) : '';
		$zip = array_key_exists('zip', $postArr) ? strip_tags($postArr['zip']) : '';
		$country = array_key_exists('country', $postArr) ? strip_tags($postArr['country']) : '';
		$guid = array_key_exists('guid', $postArr) ? strip_tags($postArr['guid']) : '';

		$sql = 'INSERT INTO users(username, password, email, firstName, lastName, title, institution, country, city, state, zip, guid) VALUES(?,PASSWORD(?),?,?,?,?,?,?,?,?,?,?)';
		$this->resetConnection();
		if($stmt = $this->conn->prepare($sql)) {
			$stmt->bind_param('ssssssssssss', $this->userName, $pwd, $email, $firstName, $lastName, $title, $institution, $country, $city, $state, $zip, $guid);
			$stmt->execute();
			if($stmt->affected_rows){
				$this->uid = $stmt->insert_id;
				$this->displayName = $firstName;
				$this->reset();
				$this->authenticate($pwd);
				$status = true;
			}
			elseif($stmt->error) $this->errorMessage = 'ERROR inserting new user: '.$stmt->error;
			$stmt->close();
		}
		else $this->errorMessage = 'ERROR inserting new user: '.$this->conn->error;

		return $status;
	}

	public function lookupUserName($emailAddr){
		$status = false;
		$from = '';
		if (array_key_exists('SYSTEM_EMAIL', $GLOBALS) && !empty($GLOBALS['SYSTEM_EMAIL'])){
			$from = 'Reset Request <'.$GLOBALS["SYSTEM_EMAIL"].'>';
		}
		if(!$this->validateEmailAddress($emailAddr)) return false;
		$loginStr = '';
		$sql = 'SELECT uid, username, concat_ws("; ", lastname, firstname) FROM users WHERE (email = "'.$emailAddr.'")';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			if($loginStr) $loginStr .= '; ';
			$loginStr .= $row->username;
		}
		$rs->free();
		if($loginStr){
			$subject = $GLOBALS['DEFAULT_TITLE'].' Login Name';
			$serverPath = $this->getDomain().$GLOBALS['CLIENT_ROOT'];
			$bodyStr = 'Your '.$GLOBALS['DEFAULT_TITLE'].' (<a href="'.$serverPath.'">'.$serverPath.'</a>) login name is: '.
				$loginStr.'<br/><br/>If you continue to have login issues, contact the System Administrator: '.$GLOBALS['ADMIN_EMAIL'];
			$status = $this->sendEmail($emailAddr, $subject, $bodyStr, $from);
		}
		else{
			$this->errorMessage = 'There are no users registered to email address: '.$emailAddr;
		}
		return $status;
	}

	private function sendEmail($to, $subject, $body, $from = ''){
		$status = true;
		if (empty($from)){
			$from = 'portal admin <'.$GLOBALS["ADMIN_EMAIL"].'>';
		}
		$smtpArr = null;
		if(isset($GLOBALS['SMTP_ARR']) && $GLOBALS['SMTP_ARR']) $smtpArr = $GLOBALS['SMTP_ARR'];
		if(class_exists('Mail') && $smtpArr){
			$smtp = Mail::factory('smtp', $smtpArr);
			$headers = array ('From' => $from, 'To' => $to, 'Subject' => $subject);
			$mail = $smtp->send($to, $headers, $body);
			if(PEAR::isError($mail)){
				$status = false;
				$this->errorMessage = $mail->getMessage();
			}
		}
		else{
			$header = "Organization: ".$GLOBALS["DEFAULT_TITLE"]." \r\n".
				"MIME-Version: 1.0 \r\n".
				"Content-type: text/html; charset=iso-8859-1 \r\n";
			if(array_key_exists("ADMIN_EMAIL",$GLOBALS) && $GLOBALS["ADMIN_EMAIL"]){
				$header .= "From: ".$from." \r\n".
					"Reply-To: ".$GLOBALS["ADMIN_EMAIL"]." \r\n".
					"Return-Path: ".$GLOBALS["ADMIN_EMAIL"]." \r\n";
			}

			if(!mail($to,$subject,$body,$header)){
				$status = false;
				$this->errorMessage = 'mailserver might not be properly setup';
			}
		}
		return $status;
	}

	public function changeLogin($newLogin, $pwd = ''){
		$status = false;
		if($this->uid){
			$isSelf = true;
			if($this->uid != $GLOBALS['SYMB_UID']) $isSelf = false;
			$newLogin = trim($newLogin);
			if(!$this->validateUserName($newLogin)) return false;

			//Test if login exists
			if($this->loginExists($newLogin)){
				$this->errorMessage = 'loginExists';
				return false;
			}

			$this->setUserName();
			if($isSelf){
				if(!$this->authenticate($pwd)){
					$this->errorMessage = 'incorrectPassword';
					return false;
				}
			}
			//Change login
			$sql = 'UPDATE users SET username = ? WHERE (uid = ?) AND (username = ?)';
			//echo $sql;
			$this->resetConnection();
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('sis', $newLogin, $this->uid, $this->userName);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error){
					if($isSelf){
						$this->userName = $newLogin;
						$this->authenticate();
					}
					$status = true;
				}
				//else echo 'ERROR saving new login: '.$stmt->error;
				$stmt->close();
			}
			//else echo 'ERROR preparing statement for updating login name: '.$this->conn->error;
		}
		return $status;
	}

	public function loginExists($login){
		$status = false;
		$sql = 'SELECT username FROM users WHERE (username = ? OR email = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ss', $this->userName, $login);
			$stmt->execute();
			$username = '';
			$stmt->bind_result($username);
			if($stmt->fetch()){
				$status = true;
				if($username == $this->userName){
					$this->errorMessage = 'login_exists';
				}
				else{
					$this->errorMessage = 'email_registered';
				}
			}
			$stmt->close();
		}
		return $status;
	}

	private function setUserRights(){
		if($this->uid){
			$userRights = array();
			$sql = 'SELECT role, tablepk FROM userroles WHERE (uid = ?) ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->uid);
				$stmt->execute();
				$role = '';
				$tablePK = '';
				$stmt->bind_result($role, $tablePK);
				while($stmt->fetch()){
					$userRights[$role][] = $tablePK;
				}
				$stmt->close();
			}
			$_SESSION['userrights'] = $userRights;
			$GLOBALS['USER_RIGHTS'] = $userRights;
		}
	}

	private function setUserParams(){
		global $PARAMS_ARR;
		$_SESSION['userparams']['un'] = $this->userName;
		$_SESSION['userparams']['dn'] = $this->displayName;
		$_SESSION['userparams']['uid'] = $this->uid;
		$PARAMS_ARR = $_SESSION['userparams'];
		$GLOBALS['USERNAME'] = $this->userName;
	}

	//Personal and general specimen management
	public function getPersonalOccurrenceCount($collid){
		$retCnt = 0;
		if($this->uid){
			$sql = 'SELECT count(*) AS reccnt FROM omoccurrences WHERE observeruid = ? AND collid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$symbUid = $GLOBALS['SYMB_UID'];
				$stmt->bind_param('ii', $symbUid, $collid);
				$stmt->execute();
				$stmt->bind_result($retCnt);
				$stmt->fetch();
				$stmt->close();
			}
		}
		return $retCnt;
	}

	public function unreviewedCommentsExist($collid){
		$retCnt = 0;
		$sql = 'SELECT count(c.comid) AS reccnt '.
			'FROM omoccurrences o INNER JOIN omoccurcomments c ON o.occid = c.occid '.
			'WHERE (o.observeruid = ?) AND (o.collid = ?) AND (c.reviewstatus < 3)';
		if($stmt = $this->conn->prepare($sql)){
			$symbUid = $GLOBALS['SYMB_UID'];
			$stmt->bind_param('ii', $symbUid, $collid);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	//User Taxonomy functions
	private function setUserTaxonomy(&$person){
		$sql = 'SELECT ut.idusertaxonomy, t.tid, t.sciname, '.
			'ut.editorstatus, ut.geographicscope, ut.notes, ut.modifieduid, ut.modifiedtimestamp '.
			'FROM usertaxonomy ut INNER JOIN taxa t ON ut.tid = t.tid '.
			'WHERE ut.uid = ?';
		$statement = $this->conn->prepare($sql);
		$uid = $person->getUid();
		$statement->bind_param('i', $uid);
		$statement->execute();
		$statement->bind_result($id, $tid, $sciname, $editorStatus, $geographicScope, $notes, $modifiedUid, $modifiedtimestamp);
		while($statement->fetch()){
			$person->addUserTaxonomy($editorStatus, $id,'sciname',$sciname);
			$person->addUserTaxonomy($editorStatus, $id,'tid',$tid);
			$person->addUserTaxonomy($editorStatus, $id,'geographicScope',$geographicScope);
			$person->addUserTaxonomy($editorStatus, $id,'notes',$notes);
		}
		$statement->close();
	}

	public function deleteUserTaxonomy($utid,$editorStatus = ''){
		$statusStr = 'SUCCESS: Taxonomic relationship deleted';
		if(is_numeric($utid) || $utid == 'all'){
			$sql = 'DELETE FROM usertaxonomy ';
			if($utid == 'all'){
				$sql .= 'WHERE uid = '.$this->uid;
			}
			else{
				$sql .= 'WHERE idusertaxonomy = '.$utid;
			}
			if($editorStatus){
				$sql .= ' AND editorstatus = "'.$editorStatus.'" ';
			}
			$this->resetConnection();
			if($this->conn->query($sql)){
				if($this->uid == $GLOBALS['SYMB_UID']){
					$this->userName = $GLOBALS['USERNAME'];
					$this->authenticate();
				}
			}
			else{
				$statusStr = 'ERROR deleting taxonomic relationship: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function addUserTaxonomy($taxon, $editorStatus, $geographicScope, $notes){
		$statusStr = 'SUCCESS adding taxonomic relationship';

		$tid = 0;
		//Get tid for taxon
		$sql1 = 'SELECT tid FROM taxa WHERE sciname = ?';
		if($stmt1 = $this->conn->prepare($sql1)){
			$stmt1->bind_param('s', $taxon);
			$stmt1->execute();
			$stmt1->bind_result($tid);
			$stmt1->fetch();
			$stmt1->close();
		}
		if($tid){
			$sql = 'INSERT INTO usertaxonomy(uid, tid, taxauthid, editorstatus, geographicScope, notes, modifiedUid, modifiedtimestamp) VALUES(?,?,?,?,?,?,?,?)';
			$this->resetConnection();
			if($stmt = $this->conn->prepare($sql)) {
				$taxAuthID = 1;
				$symbUid = $GLOBALS['SYMB_UID'];
				$modDate = date('Y-m-d H:i:s');

				$stmt->bind_param('iiisssis', $this->uid, $tid, $taxAuthID, $editorStatus, $geographicScope, $notes, $symbUid, $modDate);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error){
					if($this->uid == $GLOBALS['SYMB_UID']){
						$this->userName = $GLOBALS['USERNAME'];
						$this->authenticate();
					}
				}
				elseif($stmt->error) $this->errorMessage = 'ERROR adding taxonomic relationship: '.$stmt->error;
				$stmt->fetch();
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for adding taxonomic relationship: '.$this->conn->error;
		}
		return $statusStr;
	}

	/**
	 *
	 * Obtain the list of specimens that have an identification verification status rank less than 6
	 * within the list of taxa for which this user is listed as a specialist.
	 *
	 */
	public function echoSpecimensPendingIdent($withImgOnly = 1){
		if($this->uid){
			$tidArr = array();
			$sqlt = 'SELECT t.tid, t.sciname '.
				'FROM usertaxonomy u INNER JOIN taxa t ON u.tid = t.tid '.
				'WHERE u.uid = '.$this->uid.' AND u.editorstatus = "OccurrenceEditor" '.
				'ORDER BY t.sciname ';
			$rst = $this->conn->query($sqlt);
			while($rt = $rst->fetch_object()){
				$tidArr[$rt->tid] = $rt->sciname;
			}
			$rst->free();
			if($tidArr){
				foreach($tidArr as $tid => $taxonName){
					echo '<div style="margin:10px;">';
					echo '<div><b><u>'.$taxonName.'</u></b></div>';
					echo '<ul style="margin:10px;">';
					$sql = 'SELECT DISTINCT o.occid, o.catalognumber, IFNULL(o.sciname,t.sciname) as sciname, o.stateprovince, '.
						'CONCAT_WS("-",IFNULL(o.institutioncode,c.institutioncode),IFNULL(o.collectioncode,c.collectioncode)) AS collcode '.
						'FROM omoccurrences o INNER JOIN omoccurverification v ON o.occid = v.occid '.
						'INNER JOIN omcollections c ON o.collid = c.collid '.
						'INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
						'INNER JOIN taxaenumtree e ON t.tid = e.tid ';
					if($withImgOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid ';
					$sql .= 'WHERE v.category = "identification" AND v.ranking < 6 AND e.taxauthid = 1 '.
						'AND (e.parenttid = '.$tid.' OR t.tid = '.$tid.') '.
						'ORDER BY o.sciname,t.sciname,o.catalognumber ';
					//echo '<div>'.$sql.'</div>';
					$rs = $this->conn->query($sql);
					if($rs->num_rows){
						while($r = $rs->fetch_object()){
							echo '<li><i>'.$r->sciname.'</i>, ';
							echo '<a href="../collections/editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">';
							echo $r->catalognumber.'</a> ['.$r->collcode.']'.($r->stateprovince?', '.$r->stateprovince:'');
							echo '</li>'."\n";
						}
					}
					else{
						echo '<li>No deficiently identified specimens were found within this taxon</li>';
					}
					echo '</ul>';
					echo '</div>';
					$rs->free();
					ob_flush();
					flush();
				}
			}
		}
	}

	public function echoSpecimensLackingIdent($withImgOnly = 1){
		if($this->uid){
			echo '<div style="margin:10px;">';
			echo '<div><b><u>Lacking Identifications</u></b></div>';
			echo '<ul style="margin:10px;">';
			$sql = 'SELECT DISTINCT o.occid, o.catalognumber, o.stateprovince, '.
				'CONCAT_WS("-",IFNULL(o.institutioncode,c.institutioncode),IFNULL(o.collectioncode,c.collectioncode)) AS collcode '.
				'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
			if($withImgOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			$sql .= 'WHERE (o.sciname IS NULL) '.
				'ORDER BY c.institutioncode, o.catalognumber LIMIT 2000';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				while($r = $rs->fetch_object()){
					echo '<li>';
					echo '<a href="../collections/editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">';
					echo $r->catalognumber.'</a> ['.$r->collcode.']'.($r->stateprovince?', '.$r->stateprovince:'');
					echo '</li>'."\n";
				}
			}
			else{
				echo '<li>No un-identified specimens were found</li>';
			}
			echo '</ul>';
			echo '</div>';
			$rs->free();
			ob_flush();
			flush();
		}
	}

	//Function needs to be replaced with current specimen backup function
	public function dlSpecBackup($collId, $characterSet, $zipFile = 1){
		global $PARAMS_ARR;

		$tempPath = $this->getTempPath();
		$buFileName = $PARAMS_ARR['un'].'_'.time();

 		$cSet = str_replace('-','',strtolower($GLOBALS['CHARSET']));
		$fileUrl = '';
		//If zip archive can be created, the occurrences, determinations, and image records will be added to single archive file
		//If not, then a CSV file containing just occurrence records will be returned
		echo '<li style="font-weight:bold;">Zip Archive created</li>';
		echo '<li style="font-weight:bold;">Adding occurrence records to archive...';
		ob_flush();
		flush();
		//Adding occurrence records
		$fileName = $tempPath.$buFileName;
		$specFH = fopen($fileName.'_spec.csv', "w");
		//Output header
		$headerStr = 'occid,dbpk,basisOfRecord,otherCatalogNumbers,ownerInstitutionCode, '.
			'family,scientificName,sciname,tidinterpreted,genus,specificEpithet,taxonRank,infraspecificEpithet,scientificNameAuthorship, '.
			'taxonRemarks,identifiedBy,dateIdentified,identificationReferences,identificationRemarks,identificationQualifier, '.
			'typeStatus,recordedBy,recordNumber,associatedCollectors,eventDate,year,month,day,startDayOfYear,endDayOfYear, '.
			'verbatimEventDate,habitat,substrate,occurrenceRemarks,informationWithheld,associatedOccurrences, '.
			'dataGeneralizations,associatedTaxa,dynamicProperties,verbatimAttributes,reproductiveCondition, '.
			'cultivationStatus,establishmentMeans,lifeStage,sex,individualCount,country,stateProvince,county,municipality, '.
			'locality,localitySecurity,localitySecurityReason,decimalLatitude,decimalLongitude,geodeticDatum, '.
			'coordinateUncertaintyInMeters,verbatimCoordinates,georeferencedBy,georeferenceProtocol,georeferenceSources, '.
			'georeferenceVerificationStatus,georeferenceRemarks,minimumElevationInMeters,maximumElevationInMeters,verbatimElevation, '.
			'previousIdentifications,disposition,modified,language,processingstatus,recordEnteredBy,duplicateQuantity,dateLastModified ';
		fputcsv($specFH, explode(',',$headerStr));
		//Query and output values
		$sql = 'SELECT '.$headerStr.' FROM omoccurrences WHERE collid = '.$collId.' AND observeruid = '.$this->uid;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_row()){
				if($characterSet && $characterSet != $cSet){
					$this->encodeArr($r,$characterSet);
				}
				fputcsv($specFH, $r);
			}
			$rs->free();
		}
		fclose($specFH);

		if($zipFile && class_exists('ZipArchive')){
			$zipArchive = new ZipArchive;
			$zipArchive->open($tempPath.$buFileName.'.zip', ZipArchive::CREATE);
			//Add occurrence file and then rename to
			$zipArchive->addFile($fileName.'_spec.csv');
			$zipArchive->renameName($fileName.'_spec.csv','occurrences.csv');

			//Add determinations
			/*
			echo 'Done!</li> ';
			echo '<li style="font-weight:bold;">Adding determinations records to archive...';
			ob_flush();
			flush();
			$detFH = fopen($fileName.'_det.csv', "w");
			fputcsv($detFH, Array('detid','occid','sciname','scientificNameAuthorship','identifiedBy','d.dateIdentified','identificationQualifier','identificationReferences','identificationRemarks','sortsequence'));
			//Add determination values
			$sql = 'SELECT d.detid,d.occid,d.sciname,d.scientificNameAuthorship,d.identifiedBy,d.dateIdentified, '.
				'd.identificationQualifier,d.identificationReferences,d.identificationRemarks,d.sortsequence '.
				'FROM omdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid '.
				'WHERE o.collid = '.$this->collId.' AND o.observeruid = '.$this->uid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_row()){
					fputcsv($detFH, $r);
				}
				$rs->close();
			}
			fclose($detFH);
			$zipArchive->addFile($fileName.'_det.csv');
			$zipArchive->renameName($fileName.'_det.csv','determinations.csv');
			*/

			echo 'Done!</li> ';
			ob_flush();
			flush();
			$fileUrl = str_replace($GLOBALS['SERVER_ROOT'],$GLOBALS['CLIENT_ROOT'],$tempPath.$buFileName.'.zip');
			$zipArchive->close();
			unlink($fileName.'_spec.csv');
			//unlink($fileName.'_det.csv');
		}
		else{
			$fileUrl = str_replace($GLOBALS['SERVER_ROOT'],$GLOBALS['CLIENT_ROOT'],$tempPath.$buFileName.'_spec.csv');
		}
		return $fileUrl;
	}

	//OAuth2 functions
	public function generateTokenPacket(){
		$pkArr = Array();
		$this->createToken();
		$person = $this->getPerson();
		if($this->token){
			$pkArr['uid'] = $this->uid;
			$pkArr['firstname'] = $person->getFirstName();;
			$pkArr['lastname'] = $person->getLastName();
			$pkArr['email'] = $person->getEmail();
			$pkArr['token'] = $this->token;
		}
		return $pkArr;
	}

	public function generateAccessPacket(){
		$pkArr = Array();
		$sql = 'SELECT r.role, r.tableName, r.tablePK, c.collectionName, c.collectionCode, c.institutionCode, fc.name, p.projName '.
			'FROM userroles r LEFT JOIN omcollections c ON r.tablepk = c.CollID '.
			'LEFT JOIN fmchecklists fc ON r.tablepk = fc.CLID '.
			'LEFT JOIN fmprojects p ON r.tablepk = p.pid '.
			'WHERE r.uid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->uid);
			$stmt->execute();
			if($stmt->bind_result($role, $tableName, $tablePK, $collectionName, $collectionCode, $institutionCode, $name, $projName)){
				if($role == 'CollAdmin' || $role == 'CollEditor' || $role == 'CollTaxon'){
					$pkArr['collections'][$role][$tablePK]['CollectionName'] = $collectionName;
					$pkArr['collections'][$role][$tablePK]['CollectionCode'] = $collectionCode;
					$pkArr['collections'][$role][$tablePK]['InstitutionCode'] = $institutionCode;
				}
				elseif($r->role == 'ClAdmin'){
					$pkArr['checklists'][$role][$tablePK]['ChecklistName'] = $name;
				}
				elseif($r->role == 'ProjAdmin'){
					$pkArr['projects'][$role][$tablePK]['ProjectName'] = $projName;
				}
				else{
					$pkArr['portal'][] = $role;
				}

			}
			$stmt->close();
		}
		if(in_array('SuperAdmin',$pkArr['portal'])){
			$pkArr['collections']['CollAdmin'] = $this->getCollectionArr();
			$pkArr['checklists']['ClAdmin'] = $this->getChecklistArr();
			$pkArr['projects']['ProjAdmin'] = $this->getProjectArr();
		}
		return $pkArr;
	}

	//Token functions
	public function createToken(){
		$token = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
		if($token){
			$this->resetConnection();
			$sql = 'INSERT INTO useraccesstokens (uid,token) VALUES (?, ?) ';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('is', $this->uid, $token);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error){
					$this->token = $token;
				}
				elseif($stmt->error) $this->errorMessage = 'ERROR inserting token: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for inserting token: '.$this->conn->error;
		}
	}

	public function deleteToken($uid, $token){
		$status = false;
		$this->resetConnection();
		$sql = 'DELETE FROM useraccesstokens WHERE uid = ? AND token = ? ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('is', $uid, $token);
			$stmt->execute();
			if($stmt->affected_rows && !$stmt->error){
				$status = true;
			}
			else{
				$this->errorMessage = $this->conn->error;
				$status = false;
			}
			$stmt->close();
		}
		return $status;
	}

	public function clearAccessTokens(){
		$status = false;
		$this->resetConnection();
		$sql = 'DELETE FROM useraccesstokens WHERE uid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->uid);
			$stmt->execute();
			if($stmt->affected_rows && !$stmt->error){
				$status = true;
			}
			else{
				$this->errorMessage = $this->conn->error;
				$status = false;
			}
			$stmt->close();
		}
		return $status;
	}

	public function getTokenCnt(){
		$cnt = 0;
		$sql = 'SELECT COUNT(token) AS cnt FROM useraccesstokens WHERE uid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->uid);
			$stmt->execute();
			$stmt->bind_result($cnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $cnt;
	}

	//Misc data retrieval functions
	public function getCollectionArr(){
		global $USER_RIGHTS;
		$retArr = Array();

		$cArr = array();
		if(array_key_exists('CollAdmin',$USER_RIGHTS)) $cArr = $USER_RIGHTS['CollAdmin'];
		if(array_key_exists('CollEditor',$USER_RIGHTS)) $cArr = array_merge($cArr,$USER_RIGHTS['CollEditor']);
		$collidStr = implode(',',$cArr);
		if(!$collidStr || !preg_match('/^[\d,]+$/', $collidStr)) return $retArr;

		$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, colltype FROM omcollections WHERE collid IN('.$collidStr.') ORDER BY collectionname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->collid]['collectionname'] = $r->collectionname;
				$retArr[$r->collid]['collectioncode'] = $r->collectioncode;
				$retArr[$r->collid]['institutioncode'] = $r->institutioncode;
				$retArr[$r->collid]['colltype'] = $r->colltype;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getChecklistArr(){
		$retArr = Array();
		$sql = 'SELECT clid, name FROM fmchecklists';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->clid]['ChecklistName'] = $r->name;
			}
			$rs->free();
		}

		return $retArr;
	}

	public function getProjectArr(){
		$retArr = Array();
		$sql = 'SELECT pid, projname FROM fmprojects';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->pid]['ProjectName'] = $r->projname;
			}
			$rs->free();
		}

		return $retArr;
	}

	public function getUid($un){
		$uid = '';
		$sql = 'SELECT uid FROM users WHERE username = ? OR email = ? ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ss', $un, $un);
			$stmt->execute();
			$stmt->bind_result($uid);
			$stmt->fetch();
			$stmt->close();
		}
		return $uid;
	}

	public function setUserName($un = ''){
		if($un){
			if(!$this->validateUserName($un)) return false;
			$this->userName = $un;
		}
		else{
			if($this->uid == $GLOBALS['SYMB_UID']){
				$this->userName = $GLOBALS['USERNAME'];
			}
			elseif($this->uid){
				$this->userName = $this->getUserName($this->uid);
			}
		}
		return true;
	}

	public function getUserName($uid){
		$un = '';
		$sql = 'SELECT username FROM users WHERE uid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $uid);
			$stmt->execute();
			$stmt->bind_result($un);
			$stmt->fetch();
			$stmt->close();
		}
		return $un;
	}

	private function getTempPath(){
		$tPath = $GLOBALS['SERVER_ROOT'];
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\') $tPath .= '/';
		$tPath .= "temp/";
		if(file_exists($tPath."downloads/")){
			$tPath .= "downloads/";
		}
		return $tPath;
	}

	//setter and getters
	public function setRememberMe($test){
		$this->rememberMe = $test;
	}

	public function getRememberMe(){
		return $this->rememberMe;
	}

	public function setToken($token){
		$this->token = $token;
	}

	public function setUid($uid){
		if(is_numeric($uid)){
			$this->uid = $uid;
		}
	}

	//Misc support functions
	public function validateEmailAddress($emailAddress){
		if(!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)){
			$this->errorMessage = 'email_invalid';
			return false;
		}
		return true;
	}

	private function validateUserName($un){
		$status = true;
		if (preg_match('/^[0-9A-Za-z_!@#$\s\.+\-]+$/', $un) == 0) $status = false;
		if (substr($un,0,1) == ' ') $status = false;
		if (substr($un,-1) == ' ') $status = false;
		if(!$status) $this->errorMessage = 'username not valid';
		return $status;
	}

	private function encodeArr(&$inArr,$cSet){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->encodeString($v,$cSet);
		}
	}
}
