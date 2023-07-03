<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class SpecProcessorManager {

	protected $conn;
	protected $collid = 0;
	protected $title;
	protected $collectionName;
	protected $institutionCode;
	protected $collectionCode;
	protected $projectType;
	protected $managementType;
	protected $specKeyPattern;
	protected $patternReplace;
	protected $replaceStr;
	protected $coordX1;
	protected $coordX2;
	protected $coordY1;
	protected $coordY2;
	protected $sourcePath;
	protected $targetPath;
	protected $imgUrlBase;
	protected $webPixWidth = '';
	protected $tnPixWidth = '';
	protected $lgPixWidth = '';
	protected $jpgQuality = 80;
	protected $webMaxFileSize = 500000;
	protected $lgMaxFileSize = 10000000;
	protected $webImg = 1;
	protected $createTnImg = 1;
	protected $createLgImg = 2;
	protected $customStoredProcedure;
	protected $lastRunDate = '';

	protected $dbMetadata = 1;			//Only used when run as a standalone script
	protected $processUsingImageMagick = 0;

	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	protected $mdOutputFH;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/';
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id){
		$this->collid = $id;
		if($this->collid && is_numeric($this->collid) && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, managementtype '.
				'FROM omcollections WHERE (collid = '.$this->collid.')';
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$this->collectionName = $row->collectionname;
					$this->institutionCode = $row->institutioncode;
					$this->collectionCode = $row->collectioncode;
					$this->managementType = $row->managementtype;
				}
				else{
					exit('ABORTED: unable to locate collection in data');
				}
				$rs->close();
			}
			else{
				exit('ABORTED: unable run SQL to obtain collectionName');
			}
		}
	}

	//Project management functions (create, edit, delete, etc)
	//Functions not needed for standalone scripts
	public function editProject($editArr){
		if(is_numeric($editArr['spprid'])){
			$sqlFrag = '';
			$targetFields = array('title','projecttype','speckeypattern','patternreplace','replacestr','sourcepath','targetpath','imgurl',
				'webpixwidth','tnpixwidth','lgpixwidth','jpgcompression','createtnimg','createlgimg','source');
			if(!isset($editArr['createtnimg'])) $editArr['createtnimg'] = 0;
			if(!isset($editArr['createlgimg'])) $editArr['createlgimg'] = 0;
			foreach($editArr as $k => $v){
				if(in_array($k,$targetFields)){
					if(is_numeric($v)){
						$sqlFrag .= ','.$k.' = '.$this->cleanInStr($v);
					}
					elseif($k == 'replacestr'){
						$sqlFrag .= ','.$k.' = "'.$this->conn->real_escape_string($v).'"';
					}
					elseif($v){
						$sqlFrag .= ','.$k.' = "'.$this->cleanInStr($v).'"';
					}
					else{
						$sqlFrag .= ','.$k.' = NULL';
					}
				}
			}
			$sql = 'UPDATE specprocessorprojects SET '.trim($sqlFrag,' ,').' WHERE (spprid = '.$editArr['spprid'].')';
			//echo '<br/>SQL: '.$sql; exit;
			if(!$this->conn->query($sql)){
				echo 'ERROR saving project: '.$this->conn->error;
				//echo '<br/>SQL: '.$sql;
			}
		}
	}

	public function addProject($addArr){
		$this->conn->query('DELETE FROM specprocessorprojects WHERE (title = "OCR Harvest") AND (collid = '.$this->collid.')');
		$sql = '';
		if(isset($addArr['projecttype'])){
			$sourcePath = $addArr['sourcepath'];
			if($sourcePath == '-- Use Default Path --') $sourcePath = '';
			if($addArr['projecttype'] == 'idigbio' || $addArr['projecttype'] == 'iplant'){
				$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,patternreplace,replacestr,projecttype,sourcepath) '.
					'VALUES('.$this->collid.',"'.$this->cleanInStr($addArr['title']).'","'.$this->cleanInStr($addArr['speckeypattern']).'",'.
					($addArr['patternreplace']?'"'.$this->cleanInStr($addArr['patternreplace']).'"':'NULL').','.
					($addArr['replacestr']?'"'.$this->conn->real_escape_string($addArr['replacestr']).'"':'NULL').','.
					($addArr['projecttype']?'"'.$this->cleanInStr($addArr['projecttype']).'"':'NULL').','.
					($sourcePath?'"'.$this->cleanInStr($sourcePath).'"':'NULL').')';
			}
			elseif($addArr['projecttype'] == 'local'){
				$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,patternreplace,replacestr,projecttype,sourcepath,targetpath,'.
					'imgurl,webpixwidth,tnpixwidth,lgpixwidth,jpgcompression,createtnimg,createlgimg) '.
					'VALUES('.$this->collid.',"'.$this->cleanInStr($addArr['title']).'","'.
					$this->cleanInStr($addArr['speckeypattern']).'",'.
					($addArr['patternreplace']?'"'.$this->cleanInStr($addArr['patternreplace']).'"':'NULL').','.
					($addArr['replacestr']?'"'.$this->conn->real_escape_string($addArr['replacestr']).'"':'NULL').','.
					($addArr['projecttype']?'"'.$this->cleanInStr($addArr['projecttype']).'"':'NULL').','.
					($sourcePath?'"'.$this->cleanInStr($sourcePath).'"':'NULL').','.
					(isset($addArr['targetpath'])&&$addArr['targetpath']?'"'.$this->cleanInStr($addArr['targetpath']).'"':'NULL').','.
					(isset($addArr['imgurl'])&&$addArr['imgurl']?'"'.$addArr['imgurl'].'"':'NULL').','.
					(isset($addArr['webpixwidth'])&&$addArr['webpixwidth']?$addArr['webpixwidth']:'NULL').','.
					(isset($addArr['tnpixwidth'])&&$addArr['tnpixwidth']?$addArr['tnpixwidth']:'NULL').','.
					(isset($addArr['lgpixwidth'])&&$addArr['lgpixwidth']?$addArr['lgpixwidth']:'NULL').','.
					(isset($addArr['jpgcompression'])&&$addArr['jpgcompression']?$addArr['jpgcompression']:'NULL').','.
					(isset($addArr['createtnimg'])&&$addArr['createtnimg']?$addArr['createtnimg']:'NULL').','.
					(isset($addArr['createlgimg'])&&$addArr['createlgimg']?$addArr['createlgimg']:'NULL').')';
			}
		}
		elseif($addArr['title'] == 'OCR Harvest' && $addArr['newprofile']){
			$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern) '.
				'VALUES('.$this->collid.',"'.$this->cleanInStr($addArr['title']).'","'.
				$this->cleanInStr($addArr['speckeypattern']).'")';
		}
		if($sql){
			if(!$this->conn->query($sql)){
				echo 'ERROR saving project: '.$this->conn->error;
				//echo '<br/>SQL: '.$sql;
			}
		}
	}

	public function deleteProject($spprid){
		$sql = 'DELETE FROM specprocessorprojects WHERE (spprid = '.$spprid.')';
		$this->conn->query($sql);
	}

	public function setProjVariables($crit){
		$sqlWhere = '';
		if(is_numeric($crit)){
			$sqlWhere .= 'WHERE (spprid = '.$crit.')';
		}
		elseif($crit == 'OCR Harvest' && $this->collid){
			$sqlWhere .= 'WHERE (collid = '.$this->collid.') ';
		}
		if($sqlWhere){
			$sql = 'SELECT collid, title, speckeypattern, patternreplace, replacestr, projecttype, coordx1, coordx2, coordy1, coordy2, sourcepath, targetpath, '.
				'imgurl, webpixwidth, tnpixwidth, lgpixwidth, jpgcompression, createtnimg, createlgimg, source, lastrundate '.
				'FROM specprocessorprojects '.$sqlWhere;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(!$this->collid) $this->setCollId($row->collid);
				$this->title = $row->title;
				$this->specKeyPattern = $row->speckeypattern;
				$this->patternReplace = $row->patternreplace;
				$this->replaceStr = $row->replacestr;
				$this->projectType = $row->projecttype;
				$this->coordX1 = $row->coordx1;
				$this->coordX2 = $row->coordx2;
				$this->coordY1 = $row->coordy1;
				$this->coordY2 = $row->coordy2;
				$this->sourcePath = $row->sourcepath;
				$this->targetPath = $row->targetpath;
				$this->imgUrlBase = $row->imgurl;
				if($row->webpixwidth) $this->webPixWidth = $row->webpixwidth;
				if($row->tnpixwidth) $this->tnPixWidth = $row->tnpixwidth;
				if($row->lgpixwidth) $this->lgPixWidth = $row->lgpixwidth;
				if($row->jpgcompression) $this->jpgQuality = $row->jpgcompression;
				$this->createTnImg = $row->createtnimg;
				$this->createLgImg = $row->createlgimg;
				$this->lastRunDate = $row->lastrundate;
				if(!$this->lastRunDate && preg_match('/\d{4}-\d{2}-\d{2}/', $row->source)) $this->lastRunDate = $row->source;
				if(!$this->projectType){
					if($this->title == 'iDigBio CSV upload'){
						$this->projectType = 'idigbio';
					}
					elseif($this->title == 'IPlant Image Processing'){
						$this->projectType = 'iplant';
					}
					elseif($this->title == 'OCR Harvest'){
						break;
					}
					else{
						$this->projectType = 'local';
					}
				}
			}
			$rs->free();

			//Temporary code until customStoredProcedure field is offically integrated into specprocessorprojects table
			$sql = 'SELECT customStoredProcedure FROM specprocessorprojects '.$sqlWhere;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()) $this->customStoredProcedure = $r->customStoredProcedure;
				$rs->free();
			}

			//if(!$this->targetPath) $this->targetPath = $GLOBALS['imageRootPath'];
			//if(!$this->imgUrlBase) $this->imgUrlBase = $GLOBALS['imageRootUrl'];
			if($this->sourcePath && substr($this->sourcePath,-1) != '/' && substr($this->sourcePath,-1) != '\\') $this->sourcePath .= '/';
			if($this->targetPath && substr($this->targetPath,-1) != '/' && substr($this->targetPath,-1) != '\\') $this->targetPath .= '/';
			if($this->imgUrlBase && substr($this->imgUrlBase,-1) != '/') $this->imgUrlBase .= '/';
		}
	}

	public function getProjects(){
		$projArr = array();
		if($this->collid){
			$sql = 'SELECT spprid, title FROM specprocessorprojects  WHERE (collid = '.$this->collid.') AND title != "OCR Harvest"';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$projArr[$row->spprid] = $row->title;
			}
			$rs->free();
		}
		return $projArr;
	}

	//OCR related counts
	public function getSpecWithImage($procStatus = ''){
		//Count of specimens with images but no OCR
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid WHERE (o.collid = '.$this->collid.') ';
			if($procStatus){
				if($procStatus == 'null') $sql .= 'AND processingstatus IS NULL';
				else $sql .= 'AND processingstatus = "'.$this->cleanInStr($procStatus).'"';
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getSpecNoOcr($procStatus = ''){
		//Count of specimens with images but no OCR
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'LEFT JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
				'WHERE o.collid = '.$this->collid.' AND r.imgid IS NULL ';
			if($procStatus){
				if($procStatus == 'null'){
					$sql .= 'AND processingstatus IS NULL';
				}
				else{
					$sql .= 'AND processingstatus = "'.$this->cleanInStr($procStatus).'"';
				}
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getProcessingStatusList(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT processingstatus FROM omoccurrences WHERE collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->processingstatus) $retArr[] = $r->processingstatus;
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
	}

	//Report functions
	public function getProcessingStats(){
		$retArr = array();
		$retArr['total'] = $this->getTotalCount();
		$retArr['ps'] = $this->getProcessingStatusCountArr();
		$retArr['noimg'] = $this->getSpecNoImageCount();
		$retArr['unprocnoimg'] = $this->getUnprocSpecNoImage();
		$retArr['noskel'] = $this->getSpecNoSkel();
		$retArr['unprocwithdata'] = $this->getUnprocWithData();
		return $retArr;
	}

	private function getTotalCount(){
		$totalCnt = 0;
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$totalCnt = $r->cnt;
			}
			$rs->free();
		}
		return $totalCnt;
	}

	public function getProcessingStatusCount($ps){
		$cnt = 0;
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid.' AND processingstatus = "'.$this->cleanInStr($ps).'"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getProcessingStatusCountArr(){
		$retArr = array();
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT processingstatus, count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid.' GROUP BY processingstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$psArr[strtolower($r->processingstatus)] = $r->cnt;
			}
			$rs->free();
			//Load into $retArr in a specific order
			$statusArr = array('unprocessed','stage 1','stage 2','stage 3','pending duplicate','pending review-nfn','pending review','expert required','reviewed','closed','empty status');
			foreach($statusArr as $v){
				if(array_key_exists($v,$psArr)){
					$retArr[$v] = $psArr[$v];
					unset($psArr[$v]);
				}
			}
			//Grab untraditional processing statuses
			foreach($psArr as $k => $cnt){
				$retArr[$k] = $cnt;
			}
		}
		return $retArr;
	}

	private function getSpecNoImageCount(){
		//Count specimens without images
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE o.collid = '.$this->collid.' AND i.imgid IS NULL ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getUnprocSpecNoImage(){
		//Count unprocessed specimens without images (e.g. generated from skeletal file)
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (i.imgid IS NULL) AND (o.processingstatus = "unprocessed") ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getSpecNoSkel(){
		//Count unprocessed specimens without skeletal data
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") '.
				'AND (o.sciname IS NULL) AND (o.stateprovince IS NULL)';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getUnprocWithData(){
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(*) AS cnt FROM omoccurrences '.
				'WHERE (processingstatus = "unprocessed") AND (stateProvince IS NOT NULL) AND (locality IS NOT NULL) AND (collid = '.$this->collid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	//Detailed user stats
	public function getUserList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT e.uid FROM omoccurrences o INNER JOIN omoccuredits e ON o.occid = e.occid WHERE (o.collid = '.$this->collid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = '';
		}
		$rs->free();

		$sql = 'SELECT DISTINCT uid, CONCAT(CONCAT_WS(", ", lastname, firstname)," (", username,")") AS username FROM users WHERE (uid IN('.implode(',', array_keys($retArr)).')) ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->username;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getFullStatReport($getArr){
		$retArr = array();
		$startDate = (preg_match('/^[\d-]+$/', $getArr['startdate'])?$getArr['startdate']:'');
		$endDate = (preg_match('/^[\d-]+$/', $getArr['enddate'])?$getArr['enddate']:'');
		$uid = (is_numeric($getArr['uid'])?$getArr['uid']:'');
		$interval = $getArr['interval'];
		$processingStatus = $this->cleanInStr($getArr['processingstatus']);

		$dateFormat = '';
		$dfgb = '';
		if($interval == 'hour'){
			$dateFormat = '%Y-%m-%d %Hhr, %W';
			$dfgb = '%Y-%m-%d %H';
		}
		elseif($interval == 'day'){
			$dateFormat= '%Y-%m-%d, %W';
			$dfgb = '%Y-%m-%d';
		}
		elseif($interval == 'week'){
			$dateFormat= '%Y-%m week %U';
			$dfgb = '%Y-%m-%U';
		}
		elseif($interval == 'month'){
			$dateFormat= '%Y-%m';
			$dfgb = '%Y-%m';
		}
		$sql = 'SELECT DATE_FORMAT(e.initialtimestamp, "'.$dateFormat.'") AS timestr, u.username';
		if($processingStatus) $sql .= ', e.fieldvalueold, e.fieldvaluenew, o.processingstatus';
		$sql .= ', count(DISTINCT o.occid) AS cnt ';
		$hasEditType = $this->hasEditType();
		if($hasEditType){
			$sql .= ', COUNT(DISTINCT CASE WHEN e.editType = 0 THEN o.occid ELSE NULL END) as cntexcbatch ';
		}
		$sql .= 'FROM omoccurrences o INNER JOIN omoccuredits e ON o.occid = e.occid '.
			'INNER JOIN users u ON e.uid = u.uid '.
			'WHERE (o.collid = '.$this->collid.') ';
		if($startDate && $endDate){
			$sql .= 'AND (e.initialtimestamp BETWEEN "'.$startDate.'" AND "'.$endDate.'") ';
		}
		elseif($startDate){
			$sql .= 'AND (DATE(e.initialtimestamp) > "'.$startDate.'") ';
		}
		elseif($endDate){
			$sql .= 'AND (DATE(e.initialtimestamp) < "'.$endDate.'") ';
		}
		if($uid){
			$sql .= 'AND (e.uid = '.$uid.') ';
		}
		if($processingStatus){
			$sql .= 'AND e.fieldname = "processingstatus" ';
			if($processingStatus != 'all'){
				$sql .= 'AND (e.fieldvaluenew = "'.$processingStatus.'") ';
			}
		}
		$sql .= 'GROUP BY DATE_FORMAT(e.initialtimestamp, "'.$dfgb.'"), u.username ';
		if($processingStatus) $sql .= ', e.fieldvalueold, e.fieldvaluenew, o.processingstatus ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->timestr][$r->username]['cnt'] = $r->cnt;
			if($hasEditType) $retArr[$r->timestr][$r->username]['cntexcbatch'] = $r->cntexcbatch;
			if($processingStatus){
				$retArr[$r->timestr][$r->username]['os'] = $r->fieldvalueold;
				$retArr[$r->timestr][$r->username]['ns'] = $r->fieldvaluenew;
				$retArr[$r->timestr][$r->username]['cs'] = $r->processingstatus;
			}
		}
		$rs->free();
		return $retArr;
	}

	public function hasEditType(){
		$hasEditType = false;
		$rsTest = $this->conn->query('SHOW COLUMNS FROM omoccuredits WHERE field = "editType"');
		if($rsTest->num_rows) $hasEditType = true;
		$rsTest->free();
		return $hasEditType;
	}

 	//Misc Stats functions
	public function downloadReportData($target){
		$fileName = 'SymbSpecNoImages_'.time().'.csv';
		header ('Content-Type: text/csv; charset='.$GLOBALS['CHARSET']);
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$headerArr = array('occid','catalogNumber','sciname','recordedBy','recordNumber','eventDate','country','stateProvince','county');
		$sqlFrag = '';
		if($target == 'dlnoimg'){
			$sqlFrag .= 'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid WHERE o.collid = '.$this->collid.' AND i.imgid IS NULL ';
		}
		elseif($target == 'unprocnoimg'){
			$sqlFrag .= 'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid WHERE (o.collid = '.$this->collid.') AND (i.imgid IS NULL) AND (o.processingstatus = "unprocessed") ';
		}
		elseif($target == 'noskel'){
			$sqlFrag .= 'FROM omoccurrences o WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") AND (o.sciname IS NULL) AND (o.stateprovince IS NULL)';
		}
		elseif($target == 'unprocwithdata'){
			$headerArr[] = 'locality';
			$sqlFrag .= 'FROM omoccurrences o WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") AND (stateProvince IS NOT NULL) AND (o.locality IS NOT NULL)';
		}
		$headerArr[] = 'processingstatus';
		$sql = 'SELECT o.'.implode(',',$headerArr).' '.$sqlFrag;
		//echo $sql;
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
    		$outstream = fopen("php://output", "w");
			fputcsv($outstream, $headerArr);
			while($row = $result->fetch_assoc()){
				fputcsv($outstream, $row);
			}
			fclose($outstream);
		}
		else{
			echo "Recordset is empty.\n";
		}
        if($result) $result->close();
	}

	public function getLogListing(){
		$retArr = array();
		if($this->collid){
			$dirArr = array('imgProccessing','cyverse','iplant','processing/imgmap');
			foreach($dirArr as $dirPath){
				if(file_exists($this->logPath.$dirPath)){
					if($fh = opendir($this->logPath.$dirPath)){
						while($fileName = readdir($fh)){
							if(strpos($fileName,$this->collid.'_') === 0){
								$retArr[$dirPath][] = $fileName;
							}
						}
						if(isset($retArr[$dirPath])) rsort($retArr[$dirPath]);
					}
				}
			}
		}
		return $retArr;
	}

	//Set and Get functions
	public function setTitle($t){
		$this->title = $t;
	}

	public function getTitle(){
		return $this->title;
	}

	public function setCollectionName($cn){
		$this->collectionName = $cn;
	}

	public function getCollectionName(){
		return $this->collectionName;
	}

	public function getInstitutionCode(){
		return $this->institutionCode;
	}

	public function getCollectionCode(){
		return $this->collectionCode;
	}

	public function setProjectType($t){
		$this->projectType = $t;
	}

	public function getProjectType(){
		return $this->projectType;
	}

	public function setManagementType($t){
		$this->managementType = $t;
	}

	public function getManagementType(){
		return $this->managementType;
	}

	public function setSpecKeyPattern($p){
		$this->specKeyPattern = $p;
	}

	public function getSpecKeyPattern(){
		return $this->specKeyPattern;
	}

	public function setPatternReplace($str){
		$this->patternReplace = $str;
	}

	public function getPatternReplace(){
		return $this->patternReplace;
	}

	public function setReplaceStr($str){
		$this->replaceStr = $str;
	}

	public function getReplaceStr(){
		return $this->replaceStr;
	}

	public function setCoordX1($x){
		$this->coordX1 = $x;
	}

	public function getCoordX1(){
		return $this->coordX1;
	}

	public function setCoordX2($x){
		$this->coordX2 = $x;
	}

	public function getCoordX2(){
		return $this->coordX2;
	}

	public function setCoordY1($y){
		$this->coordY1 = $y;
	}

	public function getCoordY1(){
		return $this->coordY1;
	}

	public function setCoordY2($y){
		$this->coordY2 = $y;
	}

	public function getCoordY2(){
		return $this->coordY2;
	}

	public function setSourcePath($p){
		$this->sourcePath = $p;
	}

	public function getSourcePath(){
		return $this->sourcePath;
	}

	public function getSourcePathDefault(){
		$sourcePath = $this->sourcePath;
		if(!$sourcePath && $this->projectType == 'iplant' && $GLOBALS['IPLANT_IMAGE_IMPORT_PATH']){
			$sourcePath = $GLOBALS['IPLANT_IMAGE_IMPORT_PATH'];
			if(strpos($sourcePath, '--INSTITUTION_CODE--')) $sourcePath = str_replace('--INSTITUTION_CODE--', $this->institutionCode, $sourcePath);
			if(strpos($sourcePath, '--COLLECTION_CODE--')) $sourcePath = str_replace('--COLLECTION_CODE--', $this->collectionCode, $sourcePath);
		}
		return $sourcePath;
	}

	public function setTargetPath($p){
		$this->targetPath = $p;
	}

	public function getTargetPath(){
		return $this->targetPath;
	}

	public function setImgUrlBase($u){
		if(substr($u,-1) != '/') $u = '/';
		$this->imgUrlBase = $u;
	}

	public function getImgUrlBase(){
		return $this->imgUrlBase;
	}

	public function setWebPixWidth($w){
		$this->webPixWidth = $w;
	}

	public function getWebPixWidth(){
		return $this->webPixWidth;
	}

	public function setTnPixWidth($tn){
		$this->tnPixWidth = $tn;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function setLgPixWidth($lg){
		$this->lgPixWidth = $lg;
	}

	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}

	public function setJpgQuality($jc){
		$this->jpgQuality = $jc;
	}

	public function getJpgQuality(){
		return $this->jpgQuality;
	}

	public function setWebMaxFileSize($s){
		$this->webMaxFileSize = $s;
	}

	public function getWebMaxFileSize(){
		return $this->webMaxFileSize;
	}

	public function setLgMaxFileSize($s){
		$this->lgMaxFileSize = $s;
	}

	public function getLgMaxFileSize(){
		return $this->lgMaxFileSize;
	}

	public function setWebImg($c){
		$this->webImg = $c;
	}

	public function getWebImg(){
		return $this->webImg;
	}

	public function setCreateTnImg($c){
		$this->createTnImg = $c;
	}

	public function getCreateTnImg(){
		return $this->createTnImg;
	}

	public function setCreateLgImg($c){
		$this->createLgImg = $c;
	}

	public function getCreateLgImg(){
		return $this->createLgImg;
	}

	public function setCustomStoredProcedure($c){
		$this->customStoredProcedure = $c;
	}

	public function getCustomStoredProcedure(){
		return $this->customStoredProcedure;
	}

	public function setLastRunDate($date){
		$this->lastRunDate = $date;
	}

	public function getLastRunDate(){
		return $this->lastRunDate;
	}

	public function setDbMetadata($v){
		$this->dbMetadata = $v;
	}

 	public function setUseImageMagick($useIM){
 		$this->processUsingImageMagick = $useIM;
 	}

 	public function getUseImageMagick(){
 		return $this->processUsingImageMagick;
 	}

 	public function getConn(){
 		return $this->conn;
 	}

 	//Misc functions
	protected function cleanInStr($str){
		$newStr = trim($str);
		//$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>