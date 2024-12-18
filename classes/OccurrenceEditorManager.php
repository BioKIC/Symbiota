<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');
include_once($SERVER_ROOT.'/utilities/SymbUtil.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.en.php');

class OccurrenceEditorManager {

	protected $conn;
	protected $collId = false;
	protected $collMap = array();
	protected $occid = 0;
	private $occIndex = 0;
	private $direction = '';
	private $occidIndexArr = array();

	protected $occurrenceMap = array();
	private $fieldArr = array();
	private $sqlWhere;
	private $otherCatNumIsNum = false;
	private $qryArr = array();
	private $crowdSourceMode = 0;
	protected $isPersonalManagement = false;	//e.g. General Observations and owned by user
	private $catNumIsNum;
	protected $errorArr = array();
	protected $isShareConn = false;

	public function __construct($conn = null){
		if($conn){
			$this->conn = $conn;
			$this->isShareConn = true;
		}
		else $this->conn = MySQLiConnectionFactory::getCon("write");
		$this->fieldArr['omoccurrences'] = array('basisofrecord' => 's', 'catalognumber' => 's', 'othercatalognumbers' => 's', 'occurrenceid' => 's', 'ownerinstitutioncode' => 's',
			'institutioncode' => 's', 'collectioncode' => 's', 'eventid' => 's',
			'family' => 's', 'sciname' => 's', 'tidinterpreted' => 'n', 'scientificnameauthorship' => 's', 'identifiedby' => 's', 'dateidentified' => 's',
				'identificationreferences' => 's', 'identificationremarks' => 's', 'taxonremarks' => 's', 'identificationqualifier' => 's', 'typestatus' => 's', 'recordedby' => 's', 'recordnumber' => 's',
			'associatedcollectors' => 's', 'eventdate' => 'd', 'eventdate2' => 'd', 'year' => 'n', 'month' => 'n', 'day' => 'n', 'startdayofyear' => 'n', 'enddayofyear' => 'n',
			'verbatimeventdate' => 's', 'habitat' => 's', 'substrate' => 's', 'fieldnumber' => 's', 'occurrenceremarks' => 's', 'datageneralizations' => 's',
			'associatedtaxa' => 's', 'verbatimattributes' => 's', 'behavior' => 's', 'vitality' => 's', 'dynamicproperties' => 's', 'reproductivecondition' => 's', 'cultivationstatus' => 's', 'establishmentmeans' => 's',
			'lifestage' => 's', 'sex' => 's', 'individualcount' => 's', 'samplingprotocol' => 's', 'preparations' => 's',
			'continent' => 's', 'waterbody' => 's', 'islandgroup' => 's', 'island' => 's', 'countrycode' => 's',
			'country' => 's', 'stateprovince' => 's', 'county' => 's', 'municipality' => 's', 'locationid' => 's', 'locality' => 's', 'localitysecurity' => 'n', 'localitysecurityreason' => 's',
			'locationremarks' => 's', 'decimallatitude' => 'n', 'decimallongitude' => 'n', 'geodeticdatum' => 's', 'coordinateuncertaintyinmeters' => 'n', 'verbatimcoordinates' => 's',
			'footprintwkt' => 's', 'georeferencedby' => 's', 'georeferenceprotocol' => 's', 'georeferencesources' => 's', 'georeferenceverificationstatus' => 's',
			'georeferenceremarks' => 's', 'minimumelevationinmeters' => 'n', 'maximumelevationinmeters' => 'n','verbatimelevation' => 's',
			'minimumdepthinmeters' => 'n', 'maximumdepthinmeters' => 'n', 'verbatimdepth' => 's','disposition' => 's', 'language' => 's', 'duplicatequantity' => 'n',
			'labelproject' => 's','processingstatus' => 's', 'recordenteredby' => 's', 'observeruid' => 'n', 'dateentered' => 'd');
		$this->fieldArr['omoccurpaleo'] = array('eon','era','period','epoch','earlyinterval','lateinterval','absoluteage','storageage','stage','localstage','biota',
			'biostratigraphy','lithogroup','formation','taxonenvironment','member','bed','lithology','stratremarks','element','slideproperties','geologicalcontextid');
		$this->fieldArr['omoccuridentifiers'] = array('idname','idvalue');
		$this->fieldArr['omexsiccatiocclink'] = array('ometid','exstitle','exsnumber');
	}

	public function __destruct(){
		if(!$this->isShareConn && $this->conn !== false) $this->conn->close();
	}

	public function getCollMap(){
		if(!$this->collMap) $this->setCollMap();
		return $this->collMap;
	}

	private function setCollMap(){
		if(!$this->collMap){
			if(!$this->occid && !$this->collId) return false;
			if($this->collId===false){
				$sql = 'SELECT collid, observeruid FROM omoccurrences WHERE occid = '.$this->occid;
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$this->collId = $r->collid;
				}
				$rs->free();
			}
			if($this->collId){
				//$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, colltype, managementtype, publicedits, dynamicproperties FROM omcollections WHERE (collid = '.$this->collId.')';
				$sql = 'SELECT * FROM omcollections WHERE (collid = '.$this->collId.')';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_assoc()){
					$this->collMap = array_change_key_case($row);
					$this->collMap['collectionname'] = $this->cleanOutStr($this->collMap['collectionname']);
				}
				$rs->free();
				$this->getDynamicPropertiesArr();
			}
			else return false;
		}
	}

	private function getDynamicPropertiesArr(){
		$retArr = array();
		$propArr = array();
		if(!empty($this->collMap['dynamicproperties'])){
			$propArr = json_decode($this->collMap['dynamicproperties'], true);
			if(isset($propArr['editorProps'])){
				$retArr = $propArr['editorProps'];
				if(isset($retArr['modules-panel'])){
					foreach($retArr['modules-panel'] as $module){
						if(!empty($module['paleo']['status'])){
							$this->collMap['paleoActivated'] = true;
						}
						if(!empty($module['matSample']['status'])){
							$this->collMap['matSampleActivated'] = true;
						}
					}
				}
			}
		}
	}

	//Query functions
	public function setQueryVariables($overrideQry = false){
		if($overrideQry){
			$this->qryArr = $overrideQry;
			unset($_SESSION['editorquery']);
		}
		elseif(array_key_exists('q_catalognumber',$_REQUEST) || array_key_exists('reset',$_REQUEST)){
			if(array_key_exists('q_catalognumber',$_REQUEST) && $_REQUEST['q_catalognumber']) $this->qryArr['cn'] = trim($_REQUEST['q_catalognumber']);
			if(array_key_exists('q_othercatalognumbers',$_REQUEST) && $_REQUEST['q_othercatalognumbers']) $this->qryArr['ocn'] = trim($_REQUEST['q_othercatalognumbers']);
			if(array_key_exists('q_recordedby',$_REQUEST) && $_REQUEST['q_recordedby']) $this->qryArr['rb'] = trim($_REQUEST['q_recordedby']);
			if(array_key_exists('q_recordnumber',$_REQUEST) && $_REQUEST['q_recordnumber']) $this->qryArr['rn'] = trim($_REQUEST['q_recordnumber']);
			if(array_key_exists('q_eventdate',$_REQUEST) && $_REQUEST['q_eventdate']) $this->qryArr['ed'] = trim($_REQUEST['q_eventdate']);
			if(array_key_exists('q_recordenteredby',$_REQUEST) && $_REQUEST['q_recordenteredby']) $this->qryArr['eb'] = trim($_REQUEST['q_recordenteredby']);
			if(array_key_exists('q_returnall',$_REQUEST) && is_numeric($_REQUEST['q_returnall'])) $this->qryArr['returnall'] = $_REQUEST['q_returnall'];
			if(array_key_exists('q_processingstatus',$_REQUEST) && $_REQUEST['q_processingstatus']) $this->qryArr['ps'] = trim($_REQUEST['q_processingstatus']);
			if(array_key_exists('q_datelastmodified',$_REQUEST) && $_REQUEST['q_datelastmodified']) $this->qryArr['dm'] = trim($_REQUEST['q_datelastmodified']);
			if(array_key_exists('q_exsiccatiid',$_REQUEST) && is_numeric($_REQUEST['q_exsiccatiid'])) $this->qryArr['exsid'] = $_REQUEST['q_exsiccatiid'];
			if(array_key_exists('q_dateentered',$_REQUEST) && $_REQUEST['q_dateentered']) $this->qryArr['de'] = trim($_REQUEST['q_dateentered']);
			if(array_key_exists('q_ocrfrag',$_REQUEST) && $_REQUEST['q_ocrfrag']) $this->qryArr['ocr'] = trim($_REQUEST['q_ocrfrag']);
			if(array_key_exists('q_imgonly',$_REQUEST) && $_REQUEST['q_imgonly']) $this->qryArr['io'] = 1;
			if(array_key_exists('q_withoutimg',$_REQUEST) && $_REQUEST['q_withoutimg']) $this->qryArr['woi'] = 1;
			for($x=1; $x<9; $x++){
				if(array_key_exists('q_customandor'.$x,$_REQUEST) && $_REQUEST['q_customandor'.$x]) $this->qryArr['cao'.$x] = $_REQUEST['q_customandor'.$x];
                if(array_key_exists('q_customopenparen'.$x,$_REQUEST) && $_REQUEST['q_customopenparen'.$x]) $this->qryArr['cop'.$x] = $_REQUEST['q_customopenparen'.$x];
				if(array_key_exists('q_customfield'.$x,$_REQUEST) && $_REQUEST['q_customfield'.$x]) $this->qryArr['cf'.$x] = $_REQUEST['q_customfield'.$x];
				if(array_key_exists('q_customtype'.$x,$_REQUEST) && $_REQUEST['q_customtype'.$x]) $this->qryArr['ct'.$x] = $_REQUEST['q_customtype'.$x];
				if(array_key_exists('q_customvalue'.$x,$_REQUEST)) $this->qryArr['cv'.$x] = trim($_REQUEST['q_customvalue'.$x]);
				if(array_key_exists('q_customcloseparen'.$x,$_REQUEST) && $_REQUEST['q_customcloseparen'.$x]) $this->qryArr['ccp'.$x] = $_REQUEST['q_customcloseparen'.$x];
			}
			if(array_key_exists('orderby',$_REQUEST)) $this->qryArr['orderby'] = trim($_REQUEST['orderby']);
			if(array_key_exists('orderbydir',$_REQUEST)) $this->qryArr['orderbydir'] = trim($_REQUEST['orderbydir']);

			if(array_key_exists('occidlist',$_POST) && $_POST['occidlist']) $this->setOccidIndexArr($_POST['occidlist']);
			if(array_key_exists('direction',$_POST)) $this->direction = trim($_POST['direction']);
			unset($_SESSION['editorquery']);
		}
		elseif(isset($_SESSION['editorquery'])){
			$this->qryArr = json_decode($_SESSION['editorquery'],true);
		}
		$this->setSqlWhere();
	}

	private function setSqlWhere(){
		$this->setCollMap();
		if ($this->qryArr==null) {
			// supress warnings on array_key_exists(key,null) calls below
			$this->qryArr=array();
		}
		$sqlWhere = '';
		$this->catNumIsNum = false;
		if(array_key_exists('cn',$this->qryArr)){
			$idTerm = $this->qryArr['cn'];
			if(strtolower($idTerm) == 'is null'){
				$sqlWhere .= 'AND (o.catalognumber IS NULL) ';
			}
			else{
				$isOccid = false;
				if(substr($idTerm,0,5) == 'occid'){
					$idTerm = trim(substr($idTerm,5));
					$isOccid = true;
				}
				$iArr = explode(',',$idTerm);
				$iBetweenFrag = array();
				$iInFrag = array();
				foreach($iArr as $v){
					$v = trim($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if($p = strpos($v,' - ')){
						$term1 = $this->cleanInStr(substr($v,0,$p));
						$term2 = $this->cleanInStr(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$this->catNumIsNum = true;
							if($isOccid){
								$iBetweenFrag[] = '(o.occid BETWEEN '.$term1.' AND '.$term2.')';
							}
							else{
								$iBetweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
							}
						}
						else{
							$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2);
							$iBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$vStr = $this->cleanInStr($v);
						if(is_numeric($vStr)){
							if($iInFrag){
								//Only tag as numeric if there are more than one term (if not, it doesn't match what the sort order is)
								$this->catNumIsNum = true;
							}
							if(substr($vStr,0,1) == '0'){
								//Add value with left padded zeros removed
								$iInFrag[] = ltrim($vStr,0);
							}
						}
						$iInFrag[] = $vStr;
					}
				}
				$iWhere = '';
				if($iBetweenFrag){
					$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				}
				if($iInFrag){
					if($isOccid){
						foreach($iInFrag as $term){
							if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
								$iWhere .= 'OR (o.occid '.substr($term,0,1).' '.trim(substr($term,1)).') ';
							}
							else{
								$iWhere .= 'OR (o.occid = '.$term.') ';
							}
						}
					}
					else{
						foreach($iInFrag as $term){
							if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
								$tStr = trim(substr($term,1));
								if(!is_numeric($tStr)) $tStr = '"'.$tStr.'"';
								$iWhere .= 'OR (o.catalognumber '.substr($term,0,1).' '.$tStr.') ';
							}
							elseif(strpos($term,'%')){
								$iWhere .= 'OR (o.catalognumber LIKE "'.$term.'") ';
							}
							else{
								$iWhere .= 'OR (o.catalognumber = "'.$term.'") ';
							}
						}
					}
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
			}
		}
		//otherCatalogNumbers
		$this->otherCatNumIsNum = false;
		if(array_key_exists('ocn',$this->qryArr)){
			if(strtolower($this->qryArr['ocn']) == 'is null'){
				$sqlWhere .= 'AND (o.othercatalognumbers IS NULL) AND (id.identifierValue IS NULL) ';
			}
			else{
				$ocnArr = explode(',',$this->qryArr['ocn']);
				$ocnBetweenFrag = array();
				$ocnInFrag = array();
				foreach($ocnArr as $v){
					$v = $this->cleanInStr($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if(strpos('%',$v) !== false){
						$ocnBetweenFrag[] = '((o.othercatalognumbers LIKE "'.$v.'") OR (id.identifierValue LIKE "'.$v.'"))';
					}
					elseif($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$this->otherCatNumIsNum = true;
							$ocnBetweenFrag[] = '((o.othercatalognumbers BETWEEN '.$term1.' AND '.$term2.') OR (id.identifierValue BETWEEN '.$term1.' AND '.$term2.'))';
						}
						else{
							$ocnTerm = '(o.othercatalognumbers BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $ocnTerm .= ' AND length(o.othercatalognumbers) = '.strlen($term2);
							$ocnTerm .= ') OR (id.identifierValue BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $ocnTerm .= ' AND length(id.identifierValue) = '.strlen($term2);
							$ocnTerm .= ')';
							$ocnBetweenFrag[] = '('.$ocnTerm.')';
						}
					}
					else{
						$ocnInFrag[] = $v;
						if(is_numeric($v)){
							$this->otherCatNumIsNum = true;
							if(substr($v,0,1) == '0'){
								//Add value with left padded zeros removed
								$ocnInFrag[] = ltrim($v,0);
							}
						}
					}
				}
				$ocnWhere = '';
				if($ocnBetweenFrag){
					$ocnWhere .= 'OR '.implode(' OR ',$ocnBetweenFrag);
				}
				if($ocnInFrag){
					foreach($ocnInFrag as $term){
						if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
							$tStr = trim(substr($term,1));
							if(!is_numeric($tStr)) $tStr = '"'.$tStr.'"';
							$ocnWhere .= 'OR (o.othercatalognumbers '.substr($term,0,1).' '.$tStr.') OR (id.identifierValue '.substr($term,0,1).' '.$tStr.') ';
						}
						elseif(strpos($term,'%') !== false){
							$ocnWhere .= 'OR (o.othercatalognumbers LIKE "'.$term.'") OR (id.identifierValue LIKE "'.$term.'") ';
						}
						else{
							$ocnWhere .= 'OR (o.othercatalognumbers = "'.$term.'") OR (id.identifierValue = "'.$term.'") ';
						}
					}
				}
				$sqlWhere .= 'AND ('.substr($ocnWhere,3).') ';
			}
		}
		//recordNumber: collector's number
		if(array_key_exists('rn',$this->qryArr)){
			if(strtolower($this->qryArr['rn']) == 'is null'){
				$sqlWhere .= 'AND (o.recordnumber IS NULL) ';
			}
			else{
				$rnArr = explode(',',$this->qryArr['rn']);
				$rnBetweenFrag = array();
				$rnInFrag = array();
				foreach($rnArr as $v){
					$v = $this->cleanInStr($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$rnBetweenFrag[] = '(o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
						}
						else{
							$catTerm = 'o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.recordnumber) = '.strlen($term2);
							$rnBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$condStr = '=';
						if(substr($v,0,1) == '<' || substr($v,0,1) == '>'){
							$condStr = substr($v,0,1);
							$v = trim(substr($v,1));
						}
						if(is_numeric($v)){
							$rnInFrag[] = $condStr.' '.$v;
						}
						else{
							$rnInFrag[] = $condStr.' "'.$v.'"';
						}
					}
				}
				$rnWhere = '';
				if($rnBetweenFrag){
					$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
				}
				if($rnInFrag){
					foreach($rnInFrag as $term){
						$rnWhere .= 'OR (o.recordnumber '.$term.') ';
					}
				}
				$sqlWhere .= 'AND ('.substr($rnWhere,3).') ';
			}
		}
		//recordedBy: collector
		if(array_key_exists('rb',$this->qryArr)){
			if(strtolower($this->qryArr['rb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordedby IS NULL) ';
			}
			elseif(substr($this->qryArr['rb'],0,1) == '%'){
				$collStr = $this->cleanInStr(substr($this->qryArr['rb'],1));
				if(strlen($collStr) < 4 || in_array(strtolower($collStr),array('best','little'))){
					//Need to avoid FULLTEXT stopwords interfering with return
					$sqlWhere .= 'AND (o.recordedby LIKE "%'.$collStr.'%") ';
				}
				else{
					$sqlWhere .= 'AND (MATCH(f.recordedby) AGAINST("'.$collStr.'")) ';
				}
			}
			else{
				$sqlWhere .= 'AND (o.recordedby LIKE "'.$this->cleanInStr($this->qryArr['rb']).'%") ';
			}
		}
		//eventDate: collection date
		if(array_key_exists('ed',$this->qryArr)){
			if(strtolower($this->qryArr['ed']) == 'is null'){
				$sqlWhere .= 'AND (o.eventdate IS NULL) ';
			}
			else{
				$edv = $this->cleanInStr($this->qryArr['ed']);
				if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$edv)){
					//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
					$edv = str_ireplace(array('>',' and ','<'),array('',' - ',''),$edv);
				}
				$edv = str_replace(' to ',' - ',$edv);
				if($p = strpos($edv,' - ')){
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.trim(substr($edv,0,$p)).'" AND "'.trim(substr($edv,$p+3)).'") ';
				}
				elseif(substr($edv,0,1) == '<' || substr($edv,0,1) == '>'){
					$sqlWhere .= 'AND (o.eventdate '.substr($edv,0,1).' "'.trim(substr($edv,1)).'") ';
				}
				else{
					$sqlWhere .= 'AND (o.eventdate = "'.$edv.'") ';
				}
			}
		}
		if(array_key_exists('eb',$this->qryArr)){
			if(strtolower($this->qryArr['eb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordEnteredBy IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordEnteredBy = "'.$this->cleanInStr($this->qryArr['eb']).'") ';
			}
		}
		if(array_key_exists('de',$this->qryArr)){
			$de = $this->cleanInStr($this->qryArr['de']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$de)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$de = str_ireplace(array('>',' and ','<'),array('',' - ',''),$de);
			}
			$de = str_replace(' to ',' - ',$de);
			if($p = strpos($de,' - ')){
				$sqlWhere .= 'AND (DATE(o.dateentered) BETWEEN "'.trim(substr($de,0,$p)).'" AND "'.trim(substr($de,$p+3)).'") ';
			}
			elseif(substr($de,0,1) == '<' || substr($de,0,1) == '>'){
				$sqlWhere .= 'AND (o.dateentered '.substr($de,0,1).' "'.trim(substr($de,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.dateentered) = "'.$de.'") ';
			}
		}
		if(array_key_exists('dm',$this->qryArr)){
			$dm = $this->cleanInStr($this->qryArr['dm']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$dm)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$dm = str_ireplace(array('>',' and ','<'),array('',' - ',''),$dm);
			}
			$dm = str_replace(' to ',' - ',$dm);
			if($p = strpos($dm,' - ')){
				$sqlWhere .= 'AND (DATE(o.datelastmodified) BETWEEN "'.trim(substr($dm,0,$p)).'" AND "'.trim(substr($dm,$p+3)).'") ';
			}
			elseif(substr($dm,0,1) == '<' || substr($dm,0,1) == '>'){
				$sqlWhere .= 'AND (o.datelastmodified '.substr($dm,0,1).' "'.trim(substr($dm,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.datelastmodified) = "'.$dm.'") ';
			}
		}
		//Processing status
		if(array_key_exists('ps',$this->qryArr)){
			if($this->qryArr['ps'] == 'isnull'){
				$sqlWhere .= 'AND (o.processingstatus IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.processingstatus = "'.$this->cleanInStr($this->qryArr['ps']).'") ';
			}
		}
		//Without images
		if(array_key_exists('woi',$this->qryArr)){
			$sqlWhere .= 'AND (i.imgid IS NULL) ';
		}
		//OCR
		if(array_key_exists('ocr',$this->qryArr)){
			//Used when OCR frag comes from set field within queryformcrowdsourcing
			$sqlWhere .= 'AND (ocr.rawstr LIKE "%'.$this->cleanInStr($this->qryArr['ocr']).'%") ';
		}
		//Exsiccati ID
		if(array_key_exists('exsid',$this->qryArr) && is_numeric($this->qryArr['exsid'])){
			//Used to find records linked to a specific exsiccati
			$sqlWhere .= 'AND (exn.ometid = '.$this->qryArr['exsid'].') ';
		}
		//Custom search fields
		$customWhere = '';
		for($x=1; $x<9; $x++){
			$cao = (array_key_exists('cao'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cao'.$x]):'');
            $cop = (array_key_exists('cop'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cop'.$x]):'');
			$customField = (array_key_exists('cf'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cf'.$x]):'');
			$customTerm = (array_key_exists('ct'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['ct'.$x]):'');
			$customValue = (array_key_exists('cv'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cv'.$x]):'');
			$ccp = (array_key_exists('ccp'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['ccp'.$x]):'');
            if(!$cao) $cao = 'AND';
            if($customField){
            	if($customField == 'ocrFragment'){
					//Used when OCR frag comes from custom field search within basic query form
					$customField = 'ocr.rawstr';
				}
				elseif($customField == 'username'){
					//Used when Modified By comes from custom field search within basic query form
					$customField = 'u.username';
				}
				else{
					$customField = 'o.'.$customField;
				}
				if($customField == 'o.otherCatalogNumbers'){
					$customWhere .= $cao.' ('.substr($this->setCustomSqlFragment($customField, $customTerm, $customValue, $cao, $cop, $ccp),3).' ';
					if($customTerm != 'NOT_EQUALS' && $customTerm != 'NOT_LIKE'){
						$caoOverride = 'OR';
						if($customTerm == 'IS_NULL') $caoOverride = 'AND';
						$customWhere .= $this->setCustomSqlFragment('id.identifierValue', $customTerm, $customValue, $caoOverride, $cop, $ccp);
					}
					else{
						$customWhere .= 'AND o.occid NOT IN(SELECT occid FROM omoccuridentifiers WHERE identifierValue ';
						if($customTerm == 'NOT_LIKE') $customWhere .= 'NOT LIKE';
						else $customWhere .= '!=';
						$customWhere .= ' "'.$this->cleanInStr($customValue).'")';
					}
					$customWhere .= ') ';
				}
				else $customWhere .= $this->setCustomSqlFragment($customField, $customTerm, $customValue, $cao, $cop, $ccp);
			}
			elseif($x > 1 && !$customField && $ccp){
				$customWhere .= ' '.$ccp.' ';
    		}
		}
		if($customWhere) $sqlWhere .= 'AND ('.substr($customWhere,3).') ';
		if($this->crowdSourceMode){
			$sqlWhere .= 'AND (q.reviewstatus = 0) ';
		}
		if($this->collMap && $this->collMap['colltype'] == 'General Observations' && !isset($this->qryArr['returnall'])){
			//Ensure that General Observation projects edits are limited to active user
			$sqlWhere .= 'AND (o.observeruid = '.$GLOBALS['SYMB_UID'].') ';
		}
		if($this->collId) $sqlWhere .= 'AND (o.collid ='.$this->collId.') ';
		if($sqlWhere) $sqlWhere = 'WHERE '.substr($sqlWhere,4);
		$this->sqlWhere = $sqlWhere;
	}

	private function setCustomSqlFragment($customField, $customTerm, $customValue, $cao, $cop, $ccp){
		$sqlFrag = '';
		if($customTerm == 'IS_NULL'){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' IS NULL) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'NOT_NULL'){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' IS NOT NULL) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'NOT_EQUALS'){
			if(!is_numeric($customValue)) $customValue = '"'.$customValue.'"';
			$sqlFrag .= $cao.($cop?' '.$cop:'').' (('.$customField.' != '.$customValue.') OR ('.$customField.' IS NULL)) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'GREATER_THAN'){
			if(!is_numeric($customValue)) $customValue = '"'.$customValue.'"';
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' > '.$customValue.') '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'LESS_THAN'){
			if(!is_numeric($customValue)) $customValue = '"'.$customValue.'"';
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' < '.$customValue.') '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'LIKE' && $customValue){
			$sqlFrag .= $cao . ($cop ? ' ' . $cop : '') . ' (' . $customField . ' LIKE "%' . $customValue . '%") ' . ($ccp ? $ccp . ' ' : '');
		}
		elseif($customTerm == 'NOT_LIKE' && $customValue){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' (('.$customField.' NOT LIKE "%'.trim($customValue,'%').'%") OR ('.$customField.' IS NULL)) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'STARTS_WITH' && $customValue){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' LIKE "'.trim($customValue,'%').'%") '.($ccp?$ccp.' ':'');
		}
		elseif($customValue !== ''){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' = "'.$customValue.'") '.($ccp?$ccp.' ':'');
		}
		return $sqlFrag;
	}

	private function setSqlOrderBy(&$sql){
		if(isset($this->qryArr['orderby'])){
			$sqlOrderBy = '';
			$orderBy = $this->cleanInStr($this->qryArr['orderby']);
			if($orderBy == 'catalognumber'){
				if($this->catNumIsNum){
					$sqlOrderBy = 'catalogNumber+1';
				}
				else{
					$sqlOrderBy = 'catalogNumber';
				}
			}
			elseif($orderBy == 'othercatalognumbers'){
				if($this->otherCatNumIsNum){
					$sqlOrderBy = 'othercatalognumbers+1';
				}
				else{
					$sqlOrderBy = 'othercatalognumbers';
				}
			}
			elseif($orderBy == 'recordnumber'){
				$sqlOrderBy = 'recordnumber+1';
			}
			else{
				$sqlOrderBy = $orderBy;
			}
			if($sqlOrderBy) $sql .= 'ORDER BY (o.'.$sqlOrderBy.') '.$this->qryArr['orderbydir'].' ';
		}
	}

	public function getQueryRecordCount($reset = 0){
		if(!$reset && array_key_exists('rc',$this->qryArr)) return $this->qryArr['rc'];
		$recCnt = false;
		if($this->sqlWhere){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS reccnt FROM omoccurrences o ';
			$this->addTableJoins($sql);
			$sql .= $this->sqlWhere;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$recCnt = $r->reccnt;
			}
			$rs->free();
			$this->qryArr['rc'] = (int)$recCnt;
			$_SESSION['editorquery'] = json_encode($this->qryArr);
		}
		return $recCnt;
	}

	public function getOccurMap($start = 0, $limit = 0){
		if(!is_numeric($start)) $start = 0;
		if(!is_numeric($limit)) $limit = 0;
		if(!$this->occurrenceMap){
			if($this->direction){
				$indexKey = array_search($this->occid, $this->occidIndexArr);
				$repeat = false;
				do{
					if($this->direction == 'forward'){
						if($indexKey !== false) $indexKey++;
						$this->occIndex++;
					}
					elseif($this->direction == 'back'){
						if($indexKey !== false) $indexKey--;
						$this->occIndex--;
					}
					if($indexKey !== false && array_key_exists($indexKey, $this->occidIndexArr)){
						$this->occid = $this->occidIndexArr[$indexKey];
					}
					else{
						$this->occid = 0;
						unset($this->occidIndexArr);
						$this->occidIndexArr = array();
					}
					$this->setOccurArr();
					if(!$this->occurrenceMap && $this->occid && $this->occidIndexArr){
						//echo 'skipping: '.$indexKey.':'.$this->occid.'<br/>';
						//occid no longer belongs within where query domain
						unset($this->occidIndexArr[$indexKey]);
						if($this->direction == 'forward'){
							$this->occIndex--;
						}
						$repeat = true;
					}
					else{
						$repeat = false;
					}
				}while($repeat);
			}
			else{
				$this->setOccurArr($start, $limit);
			}
		}
		return $this->occurrenceMap;
	}

	protected function setOccurArr($start = 0, $limit = 0){
		$retArr = Array();
		$localIndex = false;
		$sqlFrag = '';
		if($this->occid && !$this->direction){
			$sqlFrag .= 'WHERE (o.occid = '.$this->occid.')';
		}
		elseif($this->sqlWhere){
			$this->addTableJoins($sqlFrag);
			$sqlFrag .= $this->sqlWhere;
			if($limit){
				$this->setSqlOrderBy($sqlFrag);
				$sqlFrag .= 'LIMIT '.$start.','.$limit;
			}
			elseif($this->occid){
				$sqlFrag .= 'AND (o.occid = '.$this->occid.') ';
				$this->setSqlOrderBy($sqlFrag);
			}
			elseif(is_numeric($this->occIndex)){
				$this->setSqlOrderBy($sqlFrag);
				$localLimit = 500;
				$localStart = floor($this->occIndex/$localLimit)*$localLimit;
				$localIndex = $this->occIndex - $localStart;
				$sqlFrag .= 'LIMIT '.$localStart.','.$localLimit;
			}
		}
		if($sqlFrag){
			$sql = 'SELECT DISTINCT o.occid, o.collid, o.'.implode(',o.',array_keys($this->fieldArr['omoccurrences'])).', datelastmodified FROM omoccurrences o '.$sqlFrag;
			$previousOccid = 0;
			$rs = $this->conn->query($sql);
			$rsCnt = 0;
			$indexArr = array();
			while($row = $rs->fetch_assoc()){
				if($previousOccid == $row['occid']) continue;
				if($row['localitysecurityreason'] == '<Security Setting Locked>') $row['localitysecurityreason'] = '[Security Setting Locked]';
				if($limit){
					//Table request, thus load all within query
					$retArr[$row['occid']] = $row;
				}
				elseif($this->occid == $row['occid']){
					//Is target specimen
					$retArr[$row['occid']] = $row;
					if($this->collMap && $this->collMap['colltype'] == 'General Observations' && $row['observeruid'] == $GLOBALS['SYMB_UID']) $this->isPersonalManagement = true;
				}
				elseif(is_numeric($localIndex)){
					if($localIndex == $rsCnt || (($rsCnt+1) == $rs->num_rows && !$this->occid)){
						$retArr[$row['occid']] = $row;
						$this->occid = $row['occid'];
					}
				}
				if($this->direction && !$this->occidIndexArr) $indexArr[] = $row['occid'];
				$previousOccid = $row['occid'];
				$rsCnt++;
			}
			$rs->free();
			if($indexArr) $this->occidIndexArr = $indexArr;
			if($retArr && count($retArr) == 1){
				if(!$this->occid) $this->occid = key($retArr);
				if(!$this->collMap) $this->setCollMap();
				if($this->collMap){
					if(!$retArr[$this->occid]['institutioncode']) $retArr[$this->occid]['institutioncode'] = $this->collMap['institutioncode'];
					if(!$retArr[$this->occid]['collectioncode']) $retArr[$this->occid]['collectioncode'] = $this->collMap['collectioncode'];
					if(!$retArr[$this->occid]['ownerinstitutioncode']) $retArr[$this->occid]['ownerinstitutioncode'] = $this->collMap['institutioncode'];
				}
			}
			$this->setAdditionalIdentifiers($retArr);
			$this->cleanOutArr($retArr);
			$this->occurrenceMap = $retArr;
			if($this->occid) $this->setPaleoData();
		}
	}

	private function addTableJoins(&$sql){

		if(strpos($this->sqlWhere,'ocr.rawstr')){
			if(strpos($this->sqlWhere,'ocr.rawstr IS NULL') && array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			elseif(strpos($this->sqlWhere,'ocr.rawstr IS NULL')){
				$sql .= 'LEFT JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			else{
				$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
		}
		elseif(array_key_exists('io',$this->qryArr)){
			$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
		}
		elseif(array_key_exists('woi',$this->qryArr)){
			$sql .= 'LEFT JOIN images i ON o.occid = i.occid ';
		}
		if(strpos($this->sqlWhere,'id.identifierValue')){
			$sql .= 'LEFT JOIN omoccuridentifiers id ON o.occid = id.occid ';
		}
		if(strpos($this->sqlWhere,'u.username')){
			$sql .= 'LEFT JOIN omoccuredits ome ON o.occid = ome.occid LEFT JOIN users u ON ome.uid = u.uid ';
		}
		if(strpos($this->sqlWhere,'exn.ometid')){
			$sql .= 'INNER JOIN omexsiccatiocclink exocc ON o.occid = exocc.occid INNER JOIN omexsiccatinumbers exn ON exocc.omenid = exn.omenid ';
		}
		if(strpos($this->sqlWhere,'MATCH(f.recordedby)') || strpos($this->sqlWhere,'MATCH(f.locality)')){
			$sql.= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		if($this->crowdSourceMode){
			$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
		}
	}

	private function setAdditionalIdentifiers(&$occurrenceArr){
		if($occurrenceArr){
			//Set identifiers for all occurrences
			$identifierArr = $this->getIdentifiers(implode(',',array_keys($occurrenceArr)));
			foreach($identifierArr as $occid => $iArr){
				$occurrenceArr[$occid]['identifiers'] = $iArr;
			}
			//Iterate through occurrences and merge addtional identifiers and otherCatalogNumbers field values
			foreach($occurrenceArr as $occid => $occurArr){
				$otherCatNumArr = array();
				if($occurArr['othercatalognumbers']){
					$ocnStr = trim($occurArr['othercatalognumbers'],',;| ');
					$ocnStr = str_replace(array(',',';'),'|',$ocnStr);
					$ocnArr = explode('|',$ocnStr);
					foreach($ocnArr as $identUnit){
						if($identUnit){
							$tag = '';
							$value = $identUnit;
							if(preg_match('/^([A-Za-z\s]+[\s#:]+)(\d+)$/', $identUnit, $m)){
								$tag = trim($m[1], ': ');
								$value = $m[2];
							}
							$otherCatNumArr[$value] = $tag;
						}
					}
				}
				if(isset($occurArr['identifiers'])){
					//Remove otherCatalogNumber values that are already within the omoccuridentifiers
					foreach($occurArr['identifiers'] as $idKey => $idArr){
						$idName = $idArr['name'];
						$idValue = $idArr['value'];
						foreach($otherCatNumArr as $ocnValue => $ocnTag){
							if($ocnValue == $idValue || $ocnValue == $idName . ' ' . $idValue){
								if(!$idName && $otherCatNumArr[$idValue]) $occurrenceArr[$occid]['identifiers'][$idKey]['name'] = $otherCatNumArr[$idValue];
								unset($otherCatNumArr[$ocnValue]);
							}
						}
					}
				}
				$newCnt = 0;
				foreach($otherCatNumArr as $newValue => $newTag){
					$occurrenceArr[$occid]['identifiers']['ocnid-'.$newCnt]['value'] = $newValue;
					$occurrenceArr[$occid]['identifiers']['ocnid-'.$newCnt]['name'] = $newTag;
					$newCnt++;
				}
			}
			foreach($occurrenceArr as $occid => $occurArr){
				if(isset($occurArr['identifiers'])){
					$idStr = '';
					foreach($occurArr['identifiers'] as $idValueArr){
						if($idValueArr['name']) $idStr .= $idValueArr['name'].': ';
						$idStr .= $idValueArr['value'].', ';
					}
					$occurrenceArr[$occid]['othercatalognumbers'] = trim($idStr,', ');
				}
			}
		}
	}

	//Edit functions
	public function editOccurrence($postArr, $editorStatus){
		global $USER_RIGHTS, $LANG;
		$status = '';
		if($this->occid && $editorStatus){
			$quickHostEntered = false;
			$autoCommit = false;
			if($editorStatus == 1 || $editorStatus == 2){
				//Is assigned admin or editor for collection
				$autoCommit = true;
			}
			elseif($editorStatus == 3){
				//Is a Taxon Editor, but without explicit rights to edit this occurrence
				$autoCommit = false;
			}
			elseif($editorStatus == 4){
				if($this->crowdSourceMode){
					//User can edit this crowdsource record
					$autoCommit = true;
				}
				else{
					//User does not have editing rights, but collection is open to public edits
					$autoCommit = false;
				}
			}
			//Processing edit
			$editedFields = trim($postArr['editedfields']);
			$editArr = array_unique(explode(';',$editedFields));
			foreach($editArr as $k => $fName){
				if(trim($fName) == 'host' || trim($fName) == 'hostassocid'){
					$quickHostEntered = true;
					unset($editArr[$k]);
				}
				if(!trim($fName)){
					unset($editArr[$k]);
				}
				else if(strcasecmp($fName, 'exstitle') == 0) {
					unset($editArr[$k]);
					$editArr[$k] = 'title';
				}
			}
			if($editArr || $quickHostEntered){
				$identArr = $this->getIdentifiers($this->occid);
				$oldValueArr = array();
				//Get current values to be saved within versioning tables
				$editFieldArr = array();
				$editFieldArr['omoccurrences'] = array_intersect($editArr,array_keys($this->fieldArr['omoccurrences']));
				if($editFieldArr['omoccurrences']){
					$sql = 'SELECT o.collid, '.implode(',',$editFieldArr['omoccurrences']).(in_array('processingstatus',$editFieldArr['omoccurrences'])?'':',processingstatus').
						(in_array('recordenteredby',$editFieldArr['omoccurrences'])?'':',recordenteredby').' FROM omoccurrences o WHERE o.occid = '.$this->occid;
					$rs = $this->conn->query($sql);
					$oldValueArr['omoccurrences'] = $rs->fetch_assoc();
					$rs->free();
				}
				//Get current paleo values to be saved within versioning tables
				$editFieldArr['omoccurpaleo'] = array_intersect($editArr, $this->fieldArr['omoccurpaleo']);
				if(isset($this->collMap['paleoActivated']) && $editFieldArr['omoccurpaleo']){
					$sql = 'SELECT '.implode(',',$editFieldArr['omoccurpaleo']).' FROM omoccurpaleo WHERE occid = '.$this->occid;
					$rs = $this->conn->query($sql);
					if($rs->num_rows) $oldValueArr['omoccurpaleo'] = $rs->fetch_assoc();
					$rs->free();
				}
				//Get current identifiers values to be saved within versioning tables
				$editFieldArr['omoccuridentifiers'] = array_intersect($editArr, $this->fieldArr['omoccuridentifiers']);
				if($editFieldArr['omoccuridentifiers'] && $identArr){
					foreach($identArr[$this->occid] as $idKey => $idArr){
						$idStr = '';
						if($idArr['name']) $idStr = $idArr['name'].': ';
						$idStr .= $idArr['value'];
						$oldValueArr['omoccuridentifiers'][$idKey] = $idStr;
					}
				}
				//Get current exsiccati values to be saved within versioning tables
				$editFieldArr['omexsiccatiocclink'] = array_intersect($editArr, $this->fieldArr['omexsiccatiocclink']);
				if($editFieldArr['omexsiccatiocclink']){
					$sql = 'SELECT et.ometid, et.title, exsnumber '.
						'FROM omexsiccatiocclink el INNER JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
						'INNER JOIN omexsiccatititles et ON en.ometid = et.ometid '.
						'WHERE el.occid = '.$this->occid;
					$rs = $this->conn->query($sql);
					$oldValueArr['omexsiccatiocclink'] = $rs->fetch_assoc();
					$rs->free();
				}
				if($editArr){
					//Deal with scientific name changes if the AJAX code fails
					if(in_array('sciname',$editArr) && $postArr['sciname'] && !$postArr['tidinterpreted']){
						$sql2 = 'SELECT t.tid, t.author, ts.family '.
							'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
							'WHERE ts.taxauthid = 1 AND sciname = "'.$this->cleanInStr($postArr['sciname']).'"';
						$rs2 = $this->conn->query($sql2);
						if($r2 = $rs2->fetch_object()){
							$postArr['tidinterpreted'] = $r2->tid;
							if(!$postArr['scientificnameauthorship']) $postArr['scientificnameauthorship'] = $r2->author;
							if(!$postArr['family']) $postArr['family'] = $r2->family;
						}
						$rs2->free();
					}
					//If additional identifiers exist, NULL otherCatalogNumbers
					if($postArr['idvalue'][0]) $postArr['othercatalognumbers'] = '';

					//If processing status was "unprocessed" and recordEnteredBy is null, populate with user login
					$oldProcessingStatus = isset($oldValueArr['omoccurrences']['processingstatus'])?$oldValueArr['omoccurrences']['processingstatus']:'';
					$oldRecordEnteredBy = isset($oldValueArr['omoccurrences']['recordenteredby'])?$oldValueArr['omoccurrences']['recordenteredby']:'';
					if(!$oldRecordEnteredBy && ($oldProcessingStatus == 'unprocessed' || $oldProcessingStatus == 'stage 1')){
						$postArr['recordenteredby'] = $GLOBALS['USERNAME'];
						$editFieldArr['omoccurrences'][] = 'recordenteredby';
					}
					//Version edits; add edits to omoccuredits
					$sqlEditsBase = 'INSERT INTO omoccuredits(occid,reviewstatus,appliedstatus,uid,fieldname,fieldvaluenew,fieldvalueold) '.
						'VALUES ('.$this->occid.',1,'.($autoCommit?'1':'0').','.$GLOBALS['SYMB_UID'].',';
					foreach($editFieldArr as $tableName => $fieldArr){
						if($tableName == 'omoccuridentifiers'){
							if($fieldArr){
								foreach($postArr['idkey'] as $idIndex => $idKey){
									$newValue = $postArr['idname'][$idIndex].($postArr['idname'][$idIndex]?': ':'').$postArr['idvalue'][$idIndex];
									$oldValue = '';
									if(is_numeric($idKey)) $oldValue = $oldValueArr['omoccuridentifiers'][$idKey];
									if($oldValue != $newValue){
										$sqlEdit = $sqlEditsBase.'"omoccuridentifiers","'.$newValue.'","'.$oldValue.'")';
										if(!$this->conn->query($sqlEdit)){
											$this->errorArr[] = ''.$this->conn->error;
										}
									}
								}
							}
						}
						else{
							foreach($fieldArr as $fieldName){
								$prefix = $tableName.':';
								if($prefix == 'omoccurrences:') $prefix = '';
								if(!array_key_exists($fieldName,$postArr)){
									//Field is a checkbox that is unchecked: cultivationstatus, localitysecurity
									$postArr[$fieldName] = 0;
								}
								$newValue = $postArr[$fieldName];
								$oldValue = '';
								if(isset($oldValueArr[$tableName][$fieldName])) $oldValue = $oldValueArr[$tableName][$fieldName];
								//Version edits only if value has changed
								if($oldValue != $newValue){
									if($fieldName != 'tidinterpreted'){
										$sqlEdit = $sqlEditsBase.'"'.$prefix.$fieldName.'","'.$this->cleanInStr($newValue).'","'.$this->cleanInStr($oldValue).'")';
										if(!$this->conn->query($sqlEdit)){
											$this->errorArr[] = ''.$this->conn->error;
										}
									}
								}
							}
						}
					}
				}
				//Edit record only if user is authorized to autoCommit
				if($autoCommit){
					$status = $LANG['SUCCESS_EDITS_SUBMITTED'].' ';
					$postArr = array_merge($postArr, $this->getDatefields($postArr));
					$sql = '';
					//Apply autoprocessing status if set
					if(array_key_exists('autoprocessingstatus',$postArr) && $postArr['autoprocessingstatus']){
						$postArr['processingstatus'] = $postArr['autoprocessingstatus'];
					}
					if($this->collMap){
						if(isset($postArr['institutioncode']) && $postArr['institutioncode'] == $this->collMap['institutioncode']) $postArr['institutioncode'] = '';
						if(isset($postArr['collectioncode']) && $postArr['collectioncode'] == $this->collMap['collectioncode']) $postArr['collectioncode'] = '';
						if(isset($postArr['ownerinstitutioncode']) && $postArr['ownerinstitutioncode'] == $this->collMap['institutioncode']) $postArr['ownerinstitutioncode'] = '';
					}
					$occurFieldArr = array_keys($this->fieldArr['omoccurrences']);
					foreach($postArr as $oField => $ov){
						if(in_array($oField,$occurFieldArr) && $oField != 'observeruid'){
							$vStr = $this->cleanInStr($ov);
							$sql .= ','.$oField.' = '.($vStr!==''?'"'.$vStr.'"':'NULL');
							//Adjust occurrenceMap which was generated but edit was submitted and will not be re-harvested afterwards
							if(array_key_exists($this->occid,$this->occurrenceMap) && array_key_exists($oField,$this->occurrenceMap[$this->occid])){
								$this->occurrenceMap[$this->occid][$oField] = $vStr;
							}
						}
					}
					//If sciname was changed, update image tid link
					if(in_array('tidinterpreted',$editArr)){
						//Remap images
						$sqlImgTid = 'UPDATE images SET tid = '.(is_numeric($postArr['tidinterpreted'])?$postArr['tidinterpreted']:'NULL').' WHERE occid = ('.$this->occid.')';
						$this->conn->query($sqlImgTid);
					}
					//If host was entered in quickhost field, update record
					if($quickHostEntered){
						if($postArr['hostassocid']){
							if($postArr['host']) $sqlHost = 'UPDATE omoccurassociations SET verbatimsciname = "'.$postArr['host'].'" WHERE associd = '.$postArr['hostassocid'].' ';
							else $sqlHost = 'DELETE FROM omoccurassociations WHERE associd = '.$postArr['hostassocid'].' ';
						}
						else $sqlHost = 'INSERT INTO omoccurassociations(occid,associationType,relationship,verbatimsciname) VALUES('.$this->occid.',"observational","host","'.$postArr['host'].'")';
						$this->conn->query($sqlHost);
					}
					//Update occurrence record
					$sql = 'UPDATE IGNORE omoccurrences SET '.substr($sql,1).' WHERE (occid = '.$this->occid.')';
					if($this->conn->query($sql)){
						if(strtolower($postArr['processingstatus']) != 'unprocessed'){
							//UPDATE uid within omcrowdsourcequeue, only if not yet processed
							$isVolunteer = true;
							if(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($this->collId, $USER_RIGHTS['CollAdmin'])) $isVolunteer = false;
							elseif(array_key_exists('CollEditor',$USER_RIGHTS) && in_array($this->collId, $USER_RIGHTS['CollEditor'])) $isVolunteer = false;
							$sql = 'UPDATE omcrowdsourcequeue SET uidprocessor = '.$GLOBALS['SYMB_UID'].', reviewstatus = 5 ';
							if(!$isVolunteer) $sql .= ', isvolunteer = 0 ';
							$sql .= 'WHERE (uidprocessor IS NULL) AND (occid = '.$this->occid.')';
							if(!$this->conn->query($sql)){
								$status = $LANG['ERROR_TAGGING_USER'].' (#'.$this->occid.'): '.$this->conn->error.' ';
							}
						}
						//Deal with additional identifiers
						if(isset($postArr['idvalue'])) $this->updateIdentifiers($postArr, $identArr);
						//Deal with paleo fields
						if(isset($this->collMap['paleoActivated']) && array_key_exists('eon',$postArr)){
							//Check to see if paleo record already exists
							$paleoRecordExist = false;
							$paleoSql = 'SELECT paleoid FROM omoccurpaleo WHERE occid = '.$this->occid;
							$paleoRS = $this->conn->query($paleoSql);
							if($paleoRS){
								if($paleoRS->num_rows) $paleoRecordExist = true;
								$paleoRS->free();
							}
							if($paleoRecordExist){
								//Edit existing record
								$paleoHasValue = false;
								$paleoFrag = '';
								foreach($this->fieldArr['omoccurpaleo'] as $paleoField){
									if(array_key_exists($paleoField,$postArr)){
										$paleoFrag .= ','.$paleoField.' = '.($postArr[$paleoField]?'"'.$this->cleanInStr($postArr[$paleoField]).'"':'NULL');
										if($postArr[$paleoField]) $paleoHasValue = true;
									}
								}
								$paleoSql = '';
								if($paleoHasValue){
									if($paleoFrag) $paleoSql = 'UPDATE omoccurpaleo SET '.substr($paleoFrag, 1).' WHERE occid = '.$this->occid;
									$this->conn->query('UPDATE omoccurpaleo SET '.substr($paleoFrag, 1).' WHERE occid = '.$this->occid);
								}
								else{
									$paleoSql = 'DELETE FROM omoccurpaleo WHERE occid = '.$this->occid;
								}
								if($paleoSql){
									if(!$this->conn->query($paleoSql)){
										$status = $LANG['ERROR_EDITING_PALEO'].': '.$this->conn->error;
									}
								}
							}
							else{
								//Add new record
								$paleoFrag1 = '';
								$paleoFrag2 = '';
								foreach($this->fieldArr['omoccurpaleo'] as $paleoField){
									if(array_key_exists($paleoField,$postArr) && $postArr[$paleoField]){
										$paleoFrag1 .= ','.$paleoField;
										$paleoFrag2 .= ',"'.$this->cleanInStr($postArr[$paleoField]).'" ';
									}
								}
								if($paleoFrag1){
									$paleoSql = 'INSERT INTO omoccurpaleo(occid'.$paleoFrag1.') VALUES('.$this->occid.$paleoFrag2.')';
									if(!$this->conn->query($paleoSql)){
										$status = $LANG['ERROR_ADDING_PALEO'].': '.$this->conn->error;
									}
								}
							}
						}

						//Deal with exsiccati
						if(in_array('ometid',$editArr) || in_array('exsnumber',$editArr)){
							$ometid = $this->cleanInStr($postArr['ometid']);
							$exsNumber = $this->cleanInStr($postArr['exsnumber']);
							if($ometid && $exsNumber){
								//Values have been submitted, thus try to add ometid and omenid
								//Get exsiccati number id
								$exsNumberId = '';
								$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$ometid.' AND exsnumber = "'.$exsNumber.'"';
								$rs = $this->conn->query($sql);
								if($r = $rs->fetch_object()){
									$exsNumberId = $r->omenid;
								}
								$rs->free();
								if(!$exsNumberId){
									//There is no exsnumber for that title, thus lets add it and grab new omenid
									$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) VALUES('.$ometid.',"'.$exsNumber.'")';
									if($this->conn->query($sqlNum)){
										$exsNumberId = $this->conn->insert_id;
									}
									else{
										$status = $LANG['ERROR_ADDING_EXS_NO'].': '.$this->conn->error.' ';
									}
								}
								//Exsiccati was editted
								if($exsNumberId){
									//Use REPLACE rather than INSERT so that if record with occid already exists, it will be removed before insert
									$sql1 = 'REPLACE INTO omexsiccatiocclink(omenid, occid) VALUES('.$exsNumberId.','.$this->occid.')';
									//echo $sql1;
									if(!$this->conn->query($sql1)){
										$status = $LANG['ERROR_ADDING_EXS'].': '.$this->conn->error.' ';
									}
								}
							}
							else{
								//No exsiccati title or number values, thus need to remove
								$sql = 'DELETE FROM omexsiccatiocclink WHERE occid = '.$this->occid;
								$this->conn->query($sql);
							}
						}
						//Deal with duplicate clusters
						if(isset($postArr['linkdupe']) && $postArr['linkdupe']){
							$dupTitle = $postArr['recordedby'].' '.$postArr['recordnumber'].' '.$postArr['eventdate'];
							$status .= $this->linkDuplicates($postArr['linkdupe'],$dupTitle);
						}
					}
					else{
						$status = $LANG['FAILED_TO_EDIT_OCC'].' (#'.$this->occid.'): '.$this->conn->error;
					}
				}
				else{
					$status = $LANG['EDIT_SUBMITTED_NOT_ACTIVATED'];
				}
			}
			else{
				$status = $LANG['ERROR_EDITS_EMPTY'].' #'.$this->occid.': '.$this->conn->error;
			}
		}
		return $status;
	}

	private function getIdentifiers($occidStr){
		$retArr = array();
		if($occidStr){
			$sql = 'SELECT occid, idomoccuridentifiers, identifierName, identifierValue FROM omoccuridentifiers WHERE occid IN('.$occidStr.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid][$r->idomoccuridentifiers]['name'] = $r->identifierName;
				$retArr[$r->occid][$r->idomoccuridentifiers]['value'] = $r->identifierValue;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function addLatestIdentToDetermination($occid) : void {
		//If determination is already in omoccurdeterminations, INSERT will fail
		$guid = UuidFactory::getUuidV4();
		$sqlInsert = 'INSERT IGNORE INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, identificationReferences, identificationRemarks, recordID, sortsequence, isCurrent) '.
			'SELECT occid, IFNULL(identifiedby,"unknown") AS idby, IFNULL(dateidentified,"s.d.") AS di, '.
			'sciname, scientificnameauthorship, identificationqualifier, identificationreferences, identificationremarks, "'.$guid.'", 10 AS sortseq, (SELECT IF(COUNT(*) > 0, 0, 1) AS isCur from omoccurdeterminations where isCurrent = 1 and occid = '. $occid . ') '.
			'FROM omoccurrences WHERE (occid = ' . $occid . ') AND (identifiedBy IS NOT NULL OR dateIdentified IS NOT NULL OR sciname IS NOT NULL)';
		try {
			$this->conn->query($sqlInsert);
		} catch (mysqli_sql_exception $e) {
			echo 'Duplicate: '.$this->conn->error;
			error_log('Error Duplicate determination from latest identification:' . $e->getMessage());
		}
	}

	// Function is only exists to move otherCatalogNumber when merging records
	// This should be removed when otherCatalogNumber is no longer in omoccurrences
	private function addLegacyIdentifers($occid) : void {
		$sql_cnt = 'SELECT COUNT(*) AS cnt FROM omoccuridentifiers oi join omoccurrences o on o.occid = oi.occid WHERE o.occid = ?';
		$sql_insert = 'INSERT INTO omoccuridentifiers(occid, identifierName, identifierValue, notes, modifiedUid) select occid,"legacyOtherCatalogNumber" as identifierName, otherCatalogNumbers as identifierValue, "Auto generated during record merge" as notes, ? as modifiedUid from omoccurrences where occid = ?';
		try {
			$result_cnt = SymbUtil::execute_query($this->conn,$sql_cnt, [$occid]);
			$cnt = ($result_cnt->fetch_assoc())["cnt"];
			if($cnt === 0) {
				SymbUtil::execute_query($this->conn,$sql_insert,[$GLOBALS['SYMB_UID'], $occid]);
			}
		} catch (mysqli_sql_exception $e) {
			error_log('Error: Failed to add otherCatalogNumbers to omoccuridentifiers for occid '. $occid . ' :' . $e->getMessage());
		}
	}

	// Copy of updateBaseOccurrence in OccurrenceEditorDeterminations for temporary utility till 3.2
	// TODO (Logan) remove once latest Identification section in editor is removed
	private function updateBaseOccurrence($detId){
		if(is_numeric($detId)){
			$taxonArr = $this->getTaxonVariables($detId);
			$sql = 'UPDATE omoccurrences o INNER JOIN omoccurdeterminations d ON o.occid = d.occid
				SET o.identifiedBy = d.identifiedBy, o.dateIdentified = d.dateIdentified, o.sciname = d.sciname, o.scientificNameAuthorship = d.scientificnameauthorship,
				o.identificationQualifier = d.identificationqualifier, o.identificationReferences = d.identificationReferences, o.identificationRemarks = d.identificationRemarks,
				o.taxonRemarks = d.taxonRemarks, o.genus = NULL, o.specificEpithet = NULL, o.taxonRank = NULL, o.infraspecificepithet = NULL, o.scientificname = NULL ';
			if(isset($taxonArr['family']) && $taxonArr['family']) $sql .= ', o.family = "'.$this->cleanInStr($taxonArr['family']).'"';
			if(isset($taxonArr['tid']) && $taxonArr['tid']) $sql .= ', o.tidinterpreted = '.$taxonArr['tid'];
			if(isset($taxonArr['security']) && $taxonArr['security']) $sql .= ', o.localitysecurity = '.$taxonArr['security'].', o.localitysecurityreason = "<Security Setting Locked>"';
			$sql .= ' WHERE (d.iscurrent = 1) AND (d.detid = '.$detId.')';
			$updated_base = $this->conn->query($sql);

			//Whenever occurrence is updated also update associated images
			if($updated_base && isset($taxonArr['tid']) && $taxonArr['tid']) {
				$sql = <<<'SQL'
				UPDATE images i
				INNER JOIN omoccurdeterminations od on od.occid = i.occid
				SET tid = ? WHERE detid = ?;
				SQL;
				SymbUtil::execute_query($this->conn,$sql, [$taxonArr['tid'], $detId]);
			}
		}
	}

	// Copy of getTaxonVariables in OccurrenceEditorDeterminations for temporary utility till 3.2
	// TODO (Logan) remove once latest Identification section in editor is removed
	private function getTaxonVariables($detId){
		$retArr = array();
		$sqlTid = 'SELECT t.tid, t.securitystatus, ts.family
			FROM omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			WHERE (d.detid = '.$detId.') AND (taxauthid = 1)';
		$rs = $this->conn->query($sqlTid);
		if($r = $rs->fetch_object()){
			$retArr['tid'] = $r->tid;
			$retArr['family'] = $r->family;
			$retArr['security'] = ($r->securitystatus == 1 ? 1 : 0);
		}
		$rs->free();
		if($retArr && !$retArr['security'] && $retArr['tid']){
			$sql2 = 'SELECT c.clid
				FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid
				INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid
				INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted
				INNER JOIN omoccurrences o ON c.locality = o.stateprovince
				WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1
				AND (ts2.tid = '.$retArr['tid'].') AND (o.occid = '.$this->occid.')';
			$rs2 = $this->conn->query($sql2);
			if($rs2->num_rows){
				$retArr['security'] = 1;
			}
			$rs2->free();
		}
		return $retArr;
	}

	public function addOccurrence($postArr){
		global $LANG;
		$status = $LANG['SUCCESS_NEW_OCC_SUBMITTED'];
		if($postArr){
			$postArr = array_merge($postArr, $this->getDatefields($postArr));
			$guid = UuidFactory::getUuidV4();
			$sql = 'INSERT IGNORE INTO omoccurrences(collid, recordID, '.implode(',',array_keys($this->fieldArr['omoccurrences'])).') VALUES ('.$postArr['collid'].', "'.$guid.'"';
			//if(array_key_exists('cultivationstatus',$postArr) && $postArr['cultivationstatus']) $postArr['cultivationstatus'] = $postArr['cultivationstatus'];
			//if(array_key_exists('localitysecurity',$postArr) && $postArr['localitysecurity']) $postArr['localitysecurity'] = $postArr['localitysecurity'];
			if(!isset($postArr['dateentered']) || !$postArr['dateentered']) $postArr['dateentered'] = date('Y-m-d H:i:s');
			if(!isset($postArr['basisofrecord']) || !$postArr['basisofrecord']) $postArr['basisofrecord'] = (strpos($this->collMap['colltype'],'Observations') !== false?'HumanObservation':'PreservedSpecimen');
			if(isset($postArr['institutioncode']) && $postArr['institutioncode'] == $this->collMap['institutioncode']) $postArr['institutionCode'] = '';
			if(isset($postArr['collectioncode']) && $postArr['collectioncode'] == $this->collMap['collectioncode']) $postArr['collectionCode'] = '';

			foreach($this->fieldArr['omoccurrences'] as $fieldStr => $fieldType){
				$fieldValue = '';
				if(array_key_exists($fieldStr,$postArr)) $fieldValue = $postArr[$fieldStr];
				if($fieldValue){
					if($fieldType == 'n'){
						if(is_numeric($fieldValue)) $sql .= ', '.$fieldValue;
						else $sql .= ', NULL';
					}
					else $sql .= ', "'.$this->cleanInStr($fieldValue).'"';		//Is string or date
				}
				else $sql .= ', NULL';
			}

			$sql .= ')';
			if($this->conn->query($sql)){
				$this->occid = $this->conn->insert_id;
				//Update collection stats
				$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt + 1 WHERE collid = '.$this->collId);
				//Deal with identifiers
				if(isset($postArr['idvalue'])) $this->updateIdentifiers($postArr);
				//Deal with paleo
				if(isset($this->collMap['paleoActivated']) && array_key_exists('eon',$postArr)){
					//Add new record
					$paleoFrag1 = '';
					$paleoFrag2 = '';
					foreach($this->fieldArr['omoccurpaleo'] as $paleoField){
						if(array_key_exists($paleoField,$postArr)){
							$paleoFrag1 .= ','.$paleoField;
							$paleoFrag2 .= ','.($postArr[$paleoField]?'"'.$this->cleanInStr($postArr[$paleoField]).'"':'NULL');
						}
					}
					if($paleoFrag1){
						$paleoSql = 'INSERT INTO omoccurpaleo(occid'.$paleoFrag1.') VALUES('.$this->occid.$paleoFrag2.')';
						$this->conn->query($paleoSql);
					}
				}
				//Deal with Exsiccati
				if(isset($postArr['ometid']) && isset($postArr['exsnumber'])){
					//If exsiccati titie is submitted, trim off first character that was used to force Google Chrome to sort correctly
					$ometid = $this->cleanInStr($postArr['ometid']);
					$exsNumber = $this->cleanInStr($postArr['exsnumber']);
					if($ometid && $exsNumber){
						$exsNumberId = '';
						$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$ometid.' AND exsnumber = "'.$exsNumber.'"';
						$rs = $this->conn->query($sql);
						if($r = $rs->fetch_object()){
							$exsNumberId = $r->omenid;
						}
						$rs->free();
						if(!$exsNumberId){
							//There is no exsnumber for that title, thus lets add it and record exsomenid
							$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
								'VALUES('.$ometid.',"'.$exsNumber.'")';
							if($this->conn->query($sqlNum)){
								$exsNumberId = $this->conn->insert_id;
							}
							else{
								$status .= '('.$LANG['WARNING_ADD_EXS_NO'].': '.$this->conn->error.') ';
							}
						}
						if($exsNumberId){
							//Add exsiccati
							$sql1 = 'INSERT INTO omexsiccatiocclink(omenid, occid) '.
								'VALUES('.$exsNumberId.','.$this->occid.')';
							if(!$this->conn->query($sql1)){
								$status .= '('.$LANG['WARNING_ADD_EXS'].': '.$this->conn->error.') ';
							}
						}
					}
				}
				//Deal with host data
				if(array_key_exists('host',$postArr)){
					$sql = 'INSERT INTO omoccurassociations(occid, associationType, relationship, verbatimsciname)
						VALUES('.$this->occid.', "observational", "host", "'.$this->cleanInStr($postArr['host']).'")';
					if(!$this->conn->query($sql)){
						$status .= '(WARNING adding host: '.$this->conn->error.') ';
					}
				}

				if(isset($postArr['confidenceranking']) && $postArr['confidenceranking']){
					$this->editIdentificationRanking($postArr['confidenceranking'],'');
				}
				//Deal with checklist voucher
				if(isset($postArr['clidvoucher']) && isset($postArr['tidinterpreted'])){
					$status .= $this->linkChecklistVoucher($postArr['clidvoucher'],$postArr['tidinterpreted']);
				}
				//Deal with duplicate clustering
				if(isset($postArr['linkdupe']) && $postArr['linkdupe']){
					$dupTitle = $postArr['recordedby'].' '.$postArr['recordnumber'].' '.$postArr['eventdate'];
					$status .= $this->linkDuplicates($postArr['linkdupe'],$dupTitle);
				}
			}
			else{
				$status = $LANG['FAILED_ADD_OCC'].": ".$this->conn->error.'<br/>SQL: '.$sql;
			}
		}

		$this->addLatestIdentToDetermination($this->occid);

		return $status;
	}

	private function updateIdentifiers($identArr, $existingIdentArr = null){
		foreach($identArr['idvalue'] as $key => $idValue){
			$idValue = trim($idValue);
			if($idValue){
				$idKey = $identArr['idkey'][$key];
				$idName = trim($identArr['idname'][$key]);
				$sql = 'UPDATE omoccuridentifiers
					SET identifierName = "'.$this->cleanInStr($idName).'", identifierValue = "'.$this->cleanInStr($idValue).'", modifiedUid = '.$GLOBALS['SYMB_UID'].
					' WHERE occid = '.$this->occid.' AND idomoccuridentifiers = '.$idKey;
				if(!is_numeric($idKey)){
					if($existingIdentArr){
						foreach($existingIdentArr[$this->occid] as $valueArr){
							//If identifier name and value already exists, thus skip to evaluate next identifier
							if($valueArr['name'] == $idName && $valueArr['value'] == $idValue) continue 2;
						}
					}
					$sql = 'INSERT INTO omoccuridentifiers(occid, identifierName, identifierValue, modifiedUid)
						VALUE('.$this->occid.',"'.$this->cleanInStr($idName).'","'.$this->cleanInStr($idValue).'", '.$GLOBALS['SYMB_UID'].') ';
				}
				if(!$this->conn->query($sql)){
					$this->errorArr[] = 'ERROR updating/adding identifier: '.$this->conn->error;
					echo implode('; ',$this->errorArr);
				}
			}
		}
	}

	private function getDatefields($occurrenceArr){
		$dateArr = array();
		if(isset($occurrenceArr['eventdate'])){
			$dateArr['year'] = '';
			$dateArr['month'] = '';
			$dateArr['day'] = '';
			$dateArr['startdayofyear'] = '';
			$dateArr['enddayofyear'] = '';
			if(preg_match('/(\d{4})-(\d{2})-(\d{2})/', $occurrenceArr['eventdate'], $m)){
				if(!empty((int) $m[1])) $dateArr['year'] = (int) $m[1];
				if(!empty((int) $m[2])) $dateArr['month'] = (int) $m[2];
				if(!empty((int) $m[3])) $dateArr['day'] = (int) $m[3];
			}
			if(!empty((int)$dateArr['day'])){
				if($dayOfYear = date('z', strtotime($occurrenceArr['eventdate']))){
					$dayOfYear++;
					$dateArr['startdayofyear'] = $dayOfYear;
					$endDayOfYear = $dayOfYear;
					if(!empty($occurrenceArr['eventdate2'])){
						if($dayOfYear = date('z', strtotime($occurrenceArr['eventdate2']))){
							$dayOfYear++;
							$endDayOfYear = $dayOfYear;
						}
					}
					$dateArr['enddayofyear'] = $endDayOfYear;
				}
			}
		}
		return $dateArr;
	}

	public function deleteOccurrence($delOccid){
		global $CHARSET, $USER_DISPLAY_NAME, $LANG;

		if(!is_numeric($delOccid)) return true;

		$stage = $LANG['ERROR_CREATING_TRANSACTION'];
		try {
			//Archive data, first grab occurrence data
			$archiveArr = array();
			$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$delOccid;
			$stage = $LANG['ERROR_FETCHING_OCCURRENCE_DATA'];
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_assoc()){
				foreach($r as $k => $v){
					if($v) $archiveArr[$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
				}
			}
			$rs->free();
			if($archiveArr){
				//Archive determinations history
				$sql = 'SELECT * FROM omoccurdeterminations WHERE occid = '.$delOccid;
				$stage = $LANG['ERROR_ARCHIVING_DET_HISTORY'];
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_assoc()){
					$detId = $r['detid'];
					foreach($r as $k => $v){
						if($v) $archiveArr['dets'][$detId][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
					}
				}
				$rs->free();

				//Archive image history
				$sql = 'SELECT * FROM images WHERE occid = '.$delOccid;
				$stage = $LANG['ERROR_ARCHIVING_IMG_HISTORY'];
				if($rs = $this->conn->query($sql)){
					$imgidStr = '';
					while($r = $rs->fetch_assoc()){
						$imgId = $r['imgid'];
						$imgidStr .= ','.$imgId;
						foreach($r as $k => $v){
							if($v) $archiveArr['imgs'][$imgId][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
					//Delete images
					$stage = $LANG['ERROR_DELETING_IMGS'];
					if($imgidStr){
						$imgidStr = trim($imgidStr, ', ');
						//Remove any OCR text blocks linked to the image
						if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE (imgid IN('.$imgidStr.'))')){
							$this->errorArr[] = $LANG['ERROR_REMOVING_OCR'].': '.$this->conn->error;
						}
						//Remove image tags
						if(!$this->conn->query('DELETE FROM imagetag WHERE (imgid IN('.$imgidStr.'))')){
							$this->errorArr[] = $LANG['ERROR_REMOVING_IMAGETAGS'].': '.$this->conn->error;
						}
						//Remove images
						if(!$this->conn->query('DELETE FROM images WHERE (imgid IN('.$imgidStr.'))')){
							$this->errorArr[] = $LANG['ERROR_REMOVING_LINKS'].': '.$this->conn->error;
						}
					}
				}

				//Archive paleo
				if(isset($this->collMap['paleoActivated'])){
					$sql = 'SELECT * FROM omoccurpaleo WHERE occid = '.$delOccid;
					$stage = $LANG['ERROR_ARCHIVING_PALEO'];
					if($rs = $this->conn->query($sql)){
						if($r = $rs->fetch_assoc()){
							foreach($r as $k => $v){
								if($v) $archiveArr['paleo'][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
							}
						}
						$rs->free();
					}
				}

				//Archive Exsiccati info
				$sql = 'SELECT t.ometid, t.title, t.abbreviation, t.editor, t.exsrange, t.startdate, t.enddate, t.source, t.notes as titlenotes, '.
					'n.omenid, n.exsnumber, n.notes AS numnotes, l.notes, l.ranking '.
					'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
					'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
					'WHERE l.occid = '.$delOccid;
				$stage = $LANG['ERROR_ARCHIVING_EXSICCATI'];
				if($rs = $this->conn->query($sql)){
					if($r = $rs->fetch_assoc()){
						foreach($r as $k => $v){
							if($v) $archiveArr['exsiccati'][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
				}

				//Archive associations info
				$sql = 'SELECT * FROM omoccurassociations WHERE occid = '.$delOccid;
				$stage = $LANG['ERROR_ARCHIVING_ASSOC'];
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_assoc()){
					$id = $r['associd'];
						foreach($r as $k => $v){
							if($v) $archiveArr['assoc'][$id][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
				}

				//Archive Material Sample info
				$sql = 'SELECT * FROM ommaterialsample WHERE occid = '.$delOccid;
				$stage = $LANG['ERROR_ARCHIVING_MAT_SAMPLE'];
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_assoc()){
						foreach($r as $k => $v){
							$id = $r['matSampleID'];
							if($v) $archiveArr['matSample'][$id][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
				}

				//Archive complete occurrence record
				$archiveArr['dateDeleted'] = date('r').' by '.$USER_DISPLAY_NAME;
				$archiveObj = json_encode($archiveArr);
				$stage = 'Create Archive';
				$sqlArchive = 'REPLACE INTO omoccurarchive(archiveobj, occid, catalogNumber, occurrenceID, recordID) '.
					'VALUES ("'.$this->cleanInStr($this->encodeStrTargeted($archiveObj,'utf8',$CHARSET)).'", '.$delOccid.','.
					(isset($archiveArr['catalogNumber']) && $archiveArr['catalogNumber']?'"'.$this->cleanInStr($archiveArr['catalogNumber']).'"':'NULL').', '.
					(isset($archiveArr['occurrenceID']) && $archiveArr['occurrenceID']?'"'.$this->cleanInStr($archiveArr['occurrenceID']).'"':'NULL').', '.
					(isset($archiveArr['recordID']) && $archiveArr['recordID']?'"'.$this->cleanInStr($archiveArr['recordID']).'"':'NULL').')';
				$this->conn->query($sqlArchive);
			}

			//Go ahead and delete
			// Associated records will be deleted from:
			// omexsiccatiocclink
			// omoccurdeterminations
			// fmvouchers
			// omoccurpaleo
			// omoccuridentifiers
			// omogenetic
			// omoccuridentifiers
			// omoccurloanslink
			// (Note this list can and like is not comprehensive of all the Cascade Keys)
			$sqlDel = <<<SQL
				DELETE FROM omoccurrences WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_TRYING_TO_DELETE'];
			SymbUtil::execute_query($this->conn, $sqlDel, [$delOccid]);

			$sql = <<<SQL
				UPDATE omcollectionstats SET recordcnt = recordcnt - 1 WHERE collid = ?
			SQL;
			$stage = $LANG['ERROR_TRYING_TO_UPDATE_COL_CNT'];
			SymbUtil::execute_query($this->conn, $sql, [$this->collId]);

			return true;
		} catch (\Throwable $th) {
			error_log(
				"Error deleting occid " . $delOccid
					. ", Line: " . $th->getLine()
					. " : " . $th->getMessage()
			);
			$this->errorArr[] = $stage . ': ' . $th->getMessage();
			return false;
		}
	}

	public function cloneOccurrence($postArr){
		global $LANG;
		$retArr = array();
		if(isset($postArr['clonecount']) && $postArr['clonecount']){
			$postArr['recordenteredby'] = $GLOBALS['USERNAME'];
			$sourceOccid = $this->occid;
			$clearAllArr = array('ownerinstitutioncode','institutioncode','collectioncode','catalognumber','othercatalognumbers','occurrenceid','individualcount','duplicatequantity','processingstatus','dateentered');
			$postArr = array_diff_key($postArr,array_flip($clearAllArr));
			if(isset($postArr['targetcollid']) && $postArr['targetcollid'] && $postArr['targetcollid'] != $this->collId){
				$clearCollArr = array('basisofrecord');
				$postArr = array_diff_key($postArr,array_flip($clearCollArr));
				$postArr['collid'] = $postArr['targetcollid'];
			}
			if(isset($postArr['carryover']) && $postArr['carryover'] == 1){
				$clearEventArr = array('family','sciname','tidinterpreted','scientificnameauthorship','identifiedby','dateidentified','identificationreferences','identificationremarks',
					'taxonremarks','identificationqualifier','recordnumber','occurrenceremarks','verbatimattributes','dynamicproperties','lifestage','sex','reproductivecondition','behavior','preparations');
				$postArr = array_diff_key($postArr,array_flip($clearEventArr));
			}
			$cloneCatNum = array();
			if(isset($postArr['clonecatnum'])) $cloneCatNum = $postArr['clonecatnum'];
			for($i=0; $i < $postArr['clonecount']; $i++){
				if(isset($cloneCatNum[$i]) && $cloneCatNum[$i]) $postArr['catalognumber'] = $cloneCatNum[$i];
				$this->addOccurrence($postArr);
				if($sourceOccid != $this->occid && !in_array($this->occid,$retArr)){
					$retArr[$this->occid] = $this->occid;
					if(isset($postArr['assocrelation']) && $postArr['assocrelation']){
						$sql = 'INSERT INTO omoccurassociations(occid, associationType, occidAssociate, relationship, createdUid, RecordID) VALUES(?, "internalOccurrence", ?, ?, ?, ? ) ';
						if($stmt = $this->conn->prepare($sql)){
							$guid = UuidFactory::getUuidV4();
							$stmt->bind_param('iisis', $this->occid, $sourceOccid, $postArr['assocrelation'], $GLOBALS['SYMB_UID'], $guid);
							$stmt->execute();
							if($stmt->error){
								$this->errorArr[] = $LANG['ERROR_ADDING_REL'].': '.$this->conn->error;
							}
							$stmt->close();
						}
					}
					if(isset($postArr['carryoverimages']) && $postArr['carryoverimages']){
						$sql = 'INSERT INTO images(occid, tid, url, thumbnailurl, originalurl, archiveurl, photographer, photographeruid, imagetype, format, caption, owner,
							sourceurl, referenceUrl, copyright, rights, accessrights, locality, notes, anatomy, username, sourceIdentifier, mediaMD5, dynamicProperties,
							defaultDisplay, sortsequence, sortOccurrence)
							SELECT '.$this->occid.', tid, url, thumbnailurl, originalurl, archiveurl, photographer, photographeruid, imagetype, format, caption, owner, sourceurl, referenceUrl,
							copyright, rights, accessrights, locality, notes, anatomy, username, sourceIdentifier, mediaMD5, dynamicProperties, defaultDisplay, sortsequence, sortOccurrence
							FROM images WHERE occid = '.$sourceOccid;
						if(!$this->conn->query($sql)){
							$this->errorArr[] = $LANG['ERROR_ADDING_IMAGES'].': '.$this->conn->error;
						}
					}
				}
			}
			$this->occid = $sourceOccid;
		}
		return $retArr;
	}

	// Note source is record that started duplicate lookup and is deleted up success
	public function mergeRecords($targetOccid,$sourceOccid){
		global $LANG;
		$status = true;
		if(!$targetOccid || !$sourceOccid){
			$this->errorArr[] = $LANG['TARGET_SOURCE_NULL'];
			return false;
		}
		if($targetOccid == $sourceOccid){
			$this->errorArr[] = $LANG['TARGET_SOURCE_EQUAL'];
			return false;
		}

		/* Start transaction */
		// This will autocommit if not rollback explicitly
		$this->conn->begin_transaction();

		//Add Latest Determination if missing
		$this->addLatestIdentToDetermination($targetOccid);
		$this->addLatestIdentToDetermination($sourceOccid);

		//Add otherCatalogNumbers to Identifiers if missing
		$this->addLegacyIdentifers($targetOccid);
		$this->addLegacyIdentifers($sourceOccid);
		$stage = '';
		try {
			$oArr = array();
			//Merge records
			$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_assoc()){
				$tempArr = array_change_key_case($r);
				$id = $tempArr['occid'];
				unset($tempArr['occid']);
				unset($tempArr['collid']);
				unset($tempArr['dbpk']);
				unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
			}
			$rs->free();

			$tArr = $oArr[$targetOccid];
			$sArr = $oArr[$sourceOccid];
			$sqlFrag = '';

			foreach($sArr as $k => $v){
				if(($v != '') && $tArr[$k] == ''){
					$sqlFrag .= ','.$k.'="'.$this->cleanInStr($v).'"';
				}
			}
			if($sqlFrag){
				$sqlIns = 'UPDATE IGNORE omoccurrences SET ' . substr($sqlFrag,1) . ' WHERE occid = ?';
				$stage = $LANG['ABORT_DUE_TO_ERROR'];
				SymbUtil::execute_query($this->conn, $sqlIns, [$targetOccid]);
			}

			// Anon function for util of merging determinations
			$get_current_determinations = function ($occid) {
				$sql =<<<'SQL'
				SELECT detid FROM omoccurdeterminations where occid = ? and isCurrent = 1;
				SQL;
				$result = SymbUtil::execute_query($this->conn, $sql, [$occid]);
				return array_map(fn($v) => $v[0], $result->fetch_all());
			};

			//Fetch List of Old Current Determinations
			$currentDeterminations = $get_current_determinations($targetOccid);

			//Remap determinations
			$sql = <<<'SQL'
			UPDATE omoccurdeterminations 
			SET occid = ? WHERE occid = ?
			AND detid NOT IN (
				SELECT source.detid FROM omoccurdeterminations source
				JOIN omoccurdeterminations target ON target.occid = ? 
				WHERE source.occid = ? 
				AND source.sciname = target.sciname
				AND source.dateIdentified = target.dateIdentified
				AND source.identifiedBy = target.identifiedBy
			);
			SQL;
			SymbUtil::execute_query($this->conn, $sql, [
				//Update To This Occid
				$targetOccid,
				//From Options of This Occid
				$sourceOccid,
				//Check This Occid Determinations for Duplicates
				$targetOccid,
				//Of this Occid Record
				$sourceOccid
			]);

			//Downgrade old determinations if new determinations have a current determination
			if(count($currentDeterminations) > 0) {

				$parameters = str_repeat('?,', count($currentDeterminations) - 1) . '?';
				$sql = <<<"SQL"
				UPDATE omoccurdeterminations 
				SET isCurrent = 0
				WHERE occid = ? AND isCurrent = 1 AND detid NOT IN ($parameters);
				SQL;
				SymbUtil::execute_query($this->conn, $sql, array_merge([$targetOccid], $currentDeterminations));
			}

			// Get New Current determination and updateBaseOccurrence to match
			$currentDeterminations = $get_current_determinations($targetOccid);
			if(is_array($currentDeterminations) && count($currentDeterminations) > 0) {
				$this->updateBaseOccurrence($currentDeterminations[0]);
			}

			//Remap images
			$sql = <<<'SQL'
			UPDATE images SET occid = ? WHERE occid = ?;
			SQL;
			$stage = $LANG['ERROR_REMAPPING_IMAGES'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap paleo
			if(isset($this->collMap['paleoActivated'])){
				$sql = <<<'SQL'
				UPDATE IGNORE omoccurpaleo SET occid = ? WHERE occid = ?;
				SQL;
				$stage = $LANG['ERROR_REMAPPING_PALEOS'];
				SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);
			}

			//Delete source occurrence edits
			$sql = <<<'SQL'
			DELETE FROM omoccuredits WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_OCC_EDITS'];
			SymbUtil::execute_query($this->conn, $sql, [$sourceOccid]);

			//Remap associations
			$sql = <<<'SQL'
			UPDATE IGNORE omoccurassociations SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_ASSOCS_1'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			$sql = <<<'SQL'
			UPDATE IGNORE omoccurassociations SET occidAssociate = ? WHERE occidAssociate = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_ASSOCS_2'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap comments
			$sql = <<<'SQL'
			UPDATE IGNORE omoccurcomments SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_COMMENTS'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap genetic resources
			$sql = <<<'SQL'
			UPDATE IGNORE omoccurgenetic SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_GENETIC'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap identifiers
			$sql = <<<'SQL'
			UPDATE IGNORE omoccuridentifiers SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_OCCIDS'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap exsiccati
			$sql = <<<'SQL'
			UPDATE IGNORE omexsiccatiocclink SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_EXS'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap occurrence dataset links
			$sql = <<<'SQL'
			UPDATE IGNORE omoccurdatasetlink SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_DATASET'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap loans
			$sql = <<<'SQL'
			UPDATE IGNORE omoccurloanslink SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_LOANS'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			//Remap checklists voucher links
			$sql = <<<'SQL'
			UPDATE IGNORE fmvouchers SET occid = ? WHERE occid = ?
			SQL;
			$stage = $LANG['ERROR_REMAPPING_VOUCHER'];
			SymbUtil::execute_query($this->conn, $sql, [$targetOccid, $sourceOccid]);

			if(!$this->deleteOccurrence($sourceOccid)){
				error_log(
					'Error: Could not delete ' . $sourceOccid
						. ' while trying to merge into '. $targetOccid
				);
				$this->errorArr[] = $LANG['ERROR_DELETING_OCCURRENCE'];
				return false;
			}

			$this->conn->commit();
			return true;
		} catch (\Throwable $th) {
			error_log(
				'Error: Merging Record ' . $sourceOccid . ' into '. $targetOccid
					. ' at '. $stage
					.', line: ' . $th->getLine()
					. ' : ' . $th->getMessage()
			);
			$this->errorArr[] = $stage . ' : ' . $th->getMessage();
			return false;
		} finally {
			//Explicit Rollback if not committed
			$this->conn->rollback();
		}
	}

	public function transferOccurrence($targetOccid,$transferCollid){
		global $LANG;
		$status = true;
		if(is_numeric($targetOccid) && is_numeric($transferCollid)){
			$sql = 'UPDATE omoccurrences SET collid = '.$transferCollid.' WHERE occid = '.$targetOccid;
			if(!$this->conn->query($sql)){
				$this->errorArr[] = $LANG['ERROR_TRYING_TO_DELETE'].': '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	public function getLoanData(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT l.loanid, l.datedue, i.institutioncode '.
				'FROM omoccurloanslink ll INNER JOIN omoccurloans l ON ll.loanid = l.loanid '.
				'INNER JOIN institutions i ON l.iidBorrower = i.iid '.
				'WHERE ll.returndate IS NULL AND l.dateclosed IS NULL AND occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['id'] = $r->loanid;
				$retArr['date'] = $r->datedue;
				$retArr['code'] = $r->institutioncode;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function setPaleoData(){
		if(isset($this->collMap['paleoActivated'])){
			$sql = 'SELECT '.implode(',',$this->fieldArr['omoccurpaleo']).' FROM omoccurpaleo WHERE occid = '.$this->occid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_assoc()){
				foreach($this->fieldArr['omoccurpaleo'] as $term){
					$this->occurrenceMap[$this->occid][$term] = $r[$term];
				}
			}
			$rs->free();
		}
	}

	public function getExsiccati(){
		$retArr = array();
		if(isset($GLOBALS['ACTIVATE_EXSICCATI']) && $GLOBALS['ACTIVATE_EXSICCATI'] && $this->occid){
			$sql = 'SELECT l.notes, l.ranking, l.omenid, n.exsnumber, t.ometid, t.title, t.abbreviation, t.editor '.
				'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
				'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
				'WHERE l.occid = '.$this->occid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['ometid'] = $r->ometid;
				$retArr['exstitle'] = $r->title.($r->abbreviation?' ['.$r->abbreviation.']':'');
				$retArr['exsnumber'] = $r->exsnumber;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getExsiccatiTitleArr(){
		$retArr = array();
		$sql = 'SELECT ometid, title, abbreviation FROM omexsiccatititles ORDER BY title ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->ometid] = $this->cleanOutStr($r->title.($r->abbreviation?' ['.$r->abbreviation.']':''));
		}
		return $retArr;
	}

	public function getObserverUid(){
		$obsId = 0;
		if($this->occurrenceMap && array_key_exists('observeruid',$this->occurrenceMap[$this->occid])){
			$obsId = $this->occurrenceMap[$this->occid]['observeruid'];
		}
		elseif($this->occid){
			$sql = 'SELECT observeruid FROM omoccurrences WHERE occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$obsId = $r->observeruid;
			}
			$rs->free();
		}
		return $obsId;
	}

	public function batchUpdateField($fieldName,$oldValue,$newValue,$buMatch){
		global $LANG;
		$statusStr = '';
		$fn = $this->cleanInStr($fieldName);
		$ov = $this->conn->real_escape_string($oldValue);
		$nv = $this->conn->real_escape_string($newValue);
		if($fn && ($ov || $nv)){
			//Get occids (where statement can't be part of UPDATE query without error being thrown)
			$occidArr = array();
			$sqlOccid = 'SELECT DISTINCT o.occid FROM omoccurrences o ';
			$this->addTableJoins($sqlOccid);
			$sqlOccid .= $this->getBatchUpdateWhere($fn,$ov,$buMatch);
			//echo $sqlOccid.'<br/>';
			$rs = $this->conn->query($sqlOccid);
			while($r = $rs->fetch_object()){
				$occidArr[] = $r->occid;
			}
			$rs->free();
			//Batch update records
			if($occidArr){
				//Set full replace or replace fragment
				$nvSqlFrag = '';
				if(!$buMatch || $ov===''){
					$nvSqlFrag = ($nv===''?'NULL':'"'.trim($nv).'"');
				}
				else{
					//Selected "Match any part of field"
					$nvSqlFrag = 'REPLACE('.$fn.',"'.$ov.'","'.$nv.'")';
				}

				$sqlWhere = 'WHERE occid IN('.implode(',',$occidArr).')';
				//Add edits to the omoccuredit table
				$sql = 'INSERT INTO omoccuredits(occid,fieldName,fieldValueOld,fieldValueNew,appliedStatus,uid,editType) '.
					'SELECT occid, "'.$fn.'" AS fieldName, IFNULL('.$fn.',"") AS oldValue, IFNULL('.$nvSqlFrag.',"") AS newValue, '.
					'1 AS appliedStatus, '.$GLOBALS['SYMB_UID'].' AS uid, 1 FROM omoccurrences '.$sqlWhere;
				if(!$this->conn->query($sql)){
					$statusStr = $LANG['ERROR_ADDING_UPDATE'].': '.$this->conn->error;
				}
				//Apply edits to core tables
				if(isset($this->collMap['paleoActivated']) && array_key_exists($fn, $this->fieldArr['omoccurpaleo'])){
					$sql = 'UPDATE omoccurpaleo SET '.$fn.' = '.$nvSqlFrag.' '.$sqlWhere;
				}
				else{
					$sql = 'UPDATE omoccurrences SET '.$fn.' = '.$nvSqlFrag.' '.$sqlWhere;
				}
				if(!$this->conn->query($sql)){
					$statusStr = $LANG['ERROR_APPLYING_BATCH_EDITS'].': '.$this->conn->error;
				}
			}
			else{
				$statusStr = $LANG['ERROR_BATCH_NO_RECORDS'];
			}
		}
		return $statusStr;
	}

	public function getBatchUpdateCount($fieldName,$oldValue,$buMatch){
		$retCnt = 0;

		$fn = $this->cleanInStr($fieldName);
		$ov = $this->conn->real_escape_string($oldValue);

		$sql = 'SELECT COUNT(DISTINCT o.occid) AS retcnt FROM omoccurrences o ';
		$this->addTableJoins($sql);
		$sql .= $this->getBatchUpdateWhere($fn,$ov,$buMatch);

		$result = $this->conn->query($sql);
		while ($row = $result->fetch_object()) {
			$retCnt = $row->retcnt;
		}
		$result->free();
		return $retCnt;
	}

	private function getBatchUpdateWhere($fn,$ov,$buMatch){
		$sql = $this->sqlWhere;

		if(!$buMatch || $ov===''){
			$sql .= ' AND (o.'.$fn.' '.($ov===''?'IS NULL':'= "'.$ov.'"').') ';
		}
		else{
			//Selected "Match any part of field"
			$sql .= ' AND (o.'.$fn.' LIKE "%'.$ov.'%") ';
		}
		return $sql;
	}

	public function carryOverValues($fArr){
		$locArr = Array('recordedby','associatedcollectors','eventdate','eventdate2','verbatimeventdate',
			'country','stateprovince','county','municipality','locationid','locality','decimallatitude','decimallongitude',
			'verbatimcoordinates','coordinateuncertaintyinmeters','footprintwkt','geodeticdatum','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks',
			'minimumelevationinmeters','maximumelevationinmeters','verbatimelevation','minimumdepthinmeters','maximumdepthinmeters','verbatimdepth',
			'habitat','substrate','lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'associatedtaxa','basisofrecord','language','labelproject','eon','era','period','epoch','earlyinterval','lateinterval','absoluteage','storageage','stage','localstage','biota',
			'biostratigraphy','lithogroup','formation','taxonenvironment','member','bed','lithology','stratremarks','element');
		$retArr = array_intersect_key($fArr,array_flip($locArr));
		$this->cleanOutArr($retArr);
		return $retArr;
	}

	//Verification functions
	public function getIdentificationRanking(){
		//Get Identification ranking
		$retArr = array();
		$sql = 'SELECT v.ovsid, v.ranking, v.notes, u.username '.
			'FROM omoccurverification v LEFT JOIN users u ON v.uid = u.uid '.
			'WHERE v.category = "identification" AND v.occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		//There can only be one identification ranking per specimen
		if($r = $rs->fetch_object()){
			$retArr['ovsid'] = $r->ovsid;
			$retArr['ranking'] = $r->ranking;
			$retArr['notes'] = $r->notes;
			$retArr['username'] = $r->username;
		}
		$rs->free();
		return $retArr;
	}

	public function editIdentificationRanking($ranking,$notes=''){
		global $LANG;
		$statusStr = '';
		if(is_numeric($ranking)){
			//Will be replaced if an identification ranking already exists for occurrence record
			$sql = 'REPLACE INTO omoccurverification(occid,category,ranking,notes,uid) '.
				'VALUES('.$this->occid.',"identification",'.$ranking.','.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.$GLOBALS['SYMB_UID'].')';
			if(!$this->conn->query($sql)){
				$statusStr .= $LANG['WARNING_EDIT_ADD_FAILED'].' ('.$this->conn->error.') ';
				//echo $sql;
			}
		}
		return $statusStr;
	}

	public function linkChecklistVoucher($clid,$tid){
		global $LANG;
		$status = '';
		if(is_numeric($clid) && is_numeric($tid)){
			//Check to see it the name is in the list, if not, add it
			$clTaxaID = 0;
			$sql = 'SELECT cl.clTaxaID '.
				'FROM fmchklsttaxalink cl INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ?) AND (cl.clid = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ii', $tid, $clid);
				$stmt->execute();
				$stmt->bind_result($clTaxaID);
				$stmt->fetch();
				$stmt->close();
			}
			if(!$clTaxaID){
				$sql = 'INSERT INTO fmchklsttaxalink(tid,clid) VALUES(?,?)';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('ii', $tid, $clid);
					$stmt->execute();
					if($stmt->affected_rows) $clTaxaID = $stmt->insert_id;
					else $status .= '('.$LANG['WARNING_ADD_SCINAME'].': '.$stmt->error.'); ';
					$stmt->close();
				}
			}
			//Add voucher
			if($clTaxaID){
				$sql = 'INSERT INTO fmvouchers(clTaxaID, occid) VALUES(?,?) ';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('ii', $clTaxaID, $this->occid);
					$stmt->execute();
					if(!$stmt->affected_rows) $status .= '('.$LANG['WARNING_ADD_VOUCHER'].': '.$stmt->error.'); ';
					$stmt->close();
				}
			}
		}
		return $status;
	}

	public function deleteChecklistVoucher($clid){
		global $LANG;
		$status = '';
		if(is_numeric($clid)){
			$sql = 'DELETE v.* FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.clTaxaID = c.clTaxaID WHERE c.clid = '.$clid.' AND v.occid = '.$this->occid;
			if(!$this->conn->query($sql)){
				$status = $LANG['ERROR_DELETING_VOUCHER'].': '.$this->conn->error;
			}
		}
		return $status;
	}

	public function getUserChecklists(){
		// Return list of checklists to which user has editing writes
		$retArr = Array();
		if(ISSET($GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$sql = 'SELECT clid, name, access FROM fmchecklists WHERE (clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).')) ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name.($r->access == 'private'?' (private)':'');
			}
			$rs->free();
			asort($retArr);
		}
		return $retArr;
	}

	//Duplicate functions
	private function linkDuplicates($occidStr,$dupTitle){
		$status = '';
		$dupManager = new OccurrenceDuplicate();
		$dupManager->linkDuplicates($this->occid,$occidStr,$dupTitle);
		return $status;
	}

	//Genetic link functions
	public function getGeneticArr(){
		global $LANG;
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT idoccurgenetic, identifier, resourcename, locus, resourceurl, notes FROM omoccurgenetic WHERE occid = '.$this->occid;
			$result = $this->conn->query($sql);
			if($result){
				while($r = $result->fetch_object()){
					$retArr[$r->idoccurgenetic]['id'] = $r->identifier;
					$retArr[$r->idoccurgenetic]['name'] = $r->resourcename;
					$retArr[$r->idoccurgenetic]['locus'] = $r->locus;
					$retArr[$r->idoccurgenetic]['resourceurl'] = $r->resourceurl;
					$retArr[$r->idoccurgenetic]['notes'] = $r->notes;
				}
				$result->free();
			}
			else{
				trigger_error($LANG['UNABLE_GENETIC_DATA'].'; '.$this->conn->error,E_USER_WARNING);
			}
		}
		return $retArr;
	}

	public function editGeneticResource($genArr){
		global $LANG;
		$genId = $genArr['genid'];
		if(is_numeric($genId)){
			$sql = 'UPDATE omoccurgenetic SET '.
				'identifier = "'.$this->cleanInStr($genArr['identifier']).'", '.
				'resourcename = "'.$this->cleanInStr($genArr['resourcename']).'", '.
				'locus = '.($genArr['locus']?'"'.$this->cleanInStr($genArr['locus']).'"':'NULL').', '.
				'resourceurl = '.($genArr['resourceurl']?'"'.$genArr['resourceurl'].'"':'NULL').', '.
				'notes = '.($genArr['notes']?'"'.$this->cleanInStr($genArr['notes']).'"':'NULL').' '.
				'WHERE idoccurgenetic = '.$genArr['genid'];
			if(!$this->conn->query($sql)){
				return $LANG['ERROR_EDITING_GENETIC'].' #'.$genArr['genid'].': '.$this->conn->error;
			}
			return $LANG['GEN_RESOURCE_EDIT_SUCCESS'];
		}
		return false;
	}

	public function deleteGeneticResource($id){
		global $LANG;
		if(is_numeric($id)){
			$sql = 'DELETE FROM omoccurgenetic WHERE idoccurgenetic = '.$id;
			if(!$this->conn->query($sql)){
				return $LANG['ERROR_DELETING_GENETIC'].' #'.$id.': '.$this->conn->error;
			}
			return $LANG['GEN_RESOURCE_DEL_SUCCESS'];
		}
		return false;
	}

	public function addGeneticResource($genArr){
		global $LANG;
		$sql = 'INSERT INTO omoccurgenetic(occid, identifier, resourcename, locus, resourceurl, notes) '.
			'VALUES('.$this->cleanInStr($genArr['occid']).',"'.$this->cleanInStr($genArr['identifier']).'","'.
			$this->cleanInStr($genArr['resourcename']).'",'.
			($genArr['locus']?'"'.$this->cleanInStr($genArr['locus']).'"':'NULL').','.
			($genArr['resourceurl']?'"'.$this->cleanInStr($genArr['resourceurl']).'"':'NULL').','.
			($genArr['notes']?'"'.$this->cleanInStr($genArr['notes']).'"':'NULL').')';
		if(!$this->conn->query($sql)){
			return $LANG['ERROR_ADDING_GEN'].': '.$this->conn->error;
		}
		return $LANG['GEN_RES_ADD_SUCCESS'];
	}

	//OCR label processing methods
	public function getRawTextFragments(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT r.prlid, r.imgid, r.rawstr, r.notes, r.source '.
				'FROM specprocessorrawlabels r INNER JOIN images i ON r.imgid = i.imgid '.
				'WHERE i.occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->imgid][$r->prlid]['raw'] = $this->cleanOutStr($r->rawstr);
				$retArr[$r->imgid][$r->prlid]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->imgid][$r->prlid]['source'] = $this->cleanOutStr($r->source);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function insertTextFragment($imgId,$rawFrag,$notes,$source){
		global $LANG;
		if($imgId && $rawFrag){
			$statusStr = '';
			//$rawFrag = preg_replace('/[^(\x20-\x7F)]*/','', $rawFrag);
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes,source) '.
				'VALUES ('.$imgId.',"'.$this->cleanRawFragment($rawFrag).'",'.
				($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.
				($source?'"'.$this->cleanInStr($source).'"':'NULL').')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = $this->conn->insert_id;
			}
			else{
				$statusStr = $LANG['ERROR_UNABLE_INSERT'].'; '.$this->conn->error;
				$statusStr .= '; SQL = '.$sql;
			}
			return $statusStr;
		}
	}

	public function saveTextFragment($prlId,$rawFrag,$notes,$source){
		global $LANG;
		if(is_numeric($prlId) && $rawFrag){
			$statusStr = '';
			//$rawFrag = preg_replace('/[^(\x20-\x7F)]*/','', $rawFrag);
			$sql = 'UPDATE specprocessorrawlabels '.
				'SET rawstr = "'.$this->cleanRawFragment($rawFrag).'", '.
				'notes = '.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').', '.
				'source = '.($source?'"'.$this->cleanInStr($source).'"':'NULL').' '.
				'WHERE (prlid = '.$prlId.')';
			if(!$this->conn->query($sql)){
				$statusStr = $LANG['ERROR_UNABLE_UPDATE'].'; '.$this->conn->error;
				$statusStr .= '; SQL = '.$sql;
			}
			return $statusStr;
		}
	}

	public function deleteTextFragment($prlId){
		global $LANG;
		if(is_numeric($prlId)){
			$statusStr = '';
			$sql = 'DELETE FROM specprocessorrawlabels WHERE (prlid = '.$prlId.')';
			if(!$this->conn->query($sql)){
				$statusStr = $LANG['ERROR_UNABLE_DELETE'].'; '.$this->conn->error;
			}
			return $statusStr;
		}
	}

	public function getImageMap($imgId = 0){
		$imageMap = Array();
		if($this->occid){
			$sql = 'SELECT imgid, url, thumbnailurl, originalurl, caption, photographer, photographeruid, sourceurl, copyright, notes, occid, username, sortoccurrence, initialtimestamp FROM images ';
			if($imgId) $sql .= 'WHERE (imgid = '.$imgId.') ';
			else $sql .= 'WHERE (occid = '.$this->occid.') ';
			$sql .= 'ORDER BY sortoccurrence';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$imageMap[$row->imgid]['url'] = $row->url;
				$imageMap[$row->imgid]['tnurl'] = $row->thumbnailurl;
				$imageMap[$row->imgid]['origurl'] = $row->originalurl;
				$imageMap[$row->imgid]['caption'] = $row->caption;
				$imageMap[$row->imgid]['photographer'] = $row->photographer;
				$imageMap[$row->imgid]['photographeruid'] = $row->photographeruid;
				$imageMap[$row->imgid]['sourceurl'] = $row->sourceurl;
				$imageMap[$row->imgid]['copyright'] = $row->copyright;
				$imageMap[$row->imgid]['notes'] = $row->notes;
				$imageMap[$row->imgid]['occid'] = $row->occid;
				$imageMap[$row->imgid]['username'] = $row->username;
				$imageMap[$row->imgid]['sort'] = $row->sortoccurrence;
			}
			$result->free();
		}
		$this->cleanOutArr($imageMap);
		return $imageMap;
	}

	protected function getImageTags($imgIdStr){
		$retArr = array();
		$sql = 'SELECT t.imgid, k.tagkey, k.shortlabel, k.description_en FROM imagetag t INNER JOIN imagetagkey k ON t.keyvalue = k.tagkey WHERE t.imgid IN('.$imgIdStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->imgid][$r->tagkey] = $r->shortlabel;
		}
		$rs->free();
		return $retArr;
	}

	public function getEditArr(){
		$retArr = array();
		$this->setOccurArr();
		$sql = 'SELECT e.ocedid, e.fieldname, e.fieldvalueold, e.fieldvaluenew, e.reviewstatus, e.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) as editor, e.initialtimestamp '.
			'FROM omoccuredits e INNER JOIN users u ON e.uid = u.uid '.
			'WHERE e.occid = '.$this->occid.' ORDER BY e.initialtimestamp DESC ';
		$result = $this->conn->query($sql);
		if($result){
			while($r = $result->fetch_object()){
				$k = substr($r->initialtimestamp,0,16);
				if(!isset($retArr[$k])){
					$retArr[$k]['editor'] = $r->editor;
					$retArr[$k]['ts'] = $r->initialtimestamp;
					$retArr[$k]['reviewstatus'] = $r->reviewstatus;
				}
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['fieldname'] = $r->fieldname;
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['old'] = $r->fieldvalueold;
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['new'] = $r->fieldvaluenew;
				$currentCode = 0;
				if(isset($this->occurrenceMap[$this->occid][strtolower($r->fieldname)])){
					$fName = $this->occurrenceMap[$this->occid][strtolower($r->fieldname)];
					if($fName == $r->fieldvaluenew) $currentCode = 1;
					elseif($fName == $r->fieldvalueold) $currentCode = 2;
				}
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['current'] = $currentCode;
			}
			$result->free();
		}
		return $retArr;
	}

	public function getExternalEditArr(){
		$retArr = Array();
		$sql = 'SELECT r.orid, r.oldvalues, r.newvalues, r.externalsource, r.externaleditor, r.reviewstatus, r.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) AS username, r.externaltimestamp, r.initialtimestamp '.
			'FROM omoccurrevisions r LEFT JOIN users u ON r.uid = u.uid '.
			'WHERE (r.occid = '.$this->occid.') ORDER BY r.initialtimestamp DESC ';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$editor = $r->externaleditor;
			if($r->username) $editor .= ' ('.$r->username.')';
			$retArr[$r->orid][$r->appliedstatus]['editor'] = $editor;
			$retArr[$r->orid][$r->appliedstatus]['source'] = $r->externalsource;
			$retArr[$r->orid][$r->appliedstatus]['reviewstatus'] = $r->reviewstatus;
			$retArr[$r->orid][$r->appliedstatus]['ts'] = $r->initialtimestamp;

			$oldValues = json_decode($r->oldvalues,true);
			$newValues = json_decode($r->newvalues,true);
			foreach($oldValues as $fieldName => $value){
				$retArr[$r->orid][$r->appliedstatus]['edits'][$fieldName]['old'] = $value;
				$retArr[$r->orid][$r->appliedstatus]['edits'][$fieldName]['new'] = (isset($newValues[$fieldName])?$newValues[$fieldName]:'ERROR');
			}
		}
		$rs->free();
		return $retArr;
	}

	//Edit locking functions (session variables)
	public function getLock(){
		$isLocked = false;
		//Delete all expired locks and other locks associated with current user
		$delSql = 'DELETE FROM omoccureditlocks WHERE (ts < ' . (time()-900) . ') OR (uid = ?)';
		if($stmt = $this->conn->prepare($delSql)){
			$stmt->bind_param('i', $GLOBALS['SYMB_UID']);
			$stmt->execute();
			$stmt->close();
		}
		//Try to insert lock for this record; if a lock exists for another user, INSERT will silently fail with no rows affected
		$sql = 'INSERT IGNORE INTO omoccureditlocks(occid,uid,ts) VALUES(?,?,' . time() . ')';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->occid, $GLOBALS['SYMB_UID']);
			$stmt->execute();
			if(!$stmt->affected_rows) $isLocked = true;
			$stmt->close();
		}
		return $isLocked;
	}

	/*
	 * Return: 0 = false, 2 = full editor, 3 = taxon editor, but not for this collection
	 */
	public function isTaxonomicEditor(){
		global $USER_RIGHTS;
		$isEditor = 0;

		//Get list of userTaxonomyIds that user has been aproved for this collection
		$udIdArr = array();
		if(array_key_exists('CollTaxon',$USER_RIGHTS)){
			foreach($USER_RIGHTS['CollTaxon'] as $vStr){
				$tok = explode(':',$vStr);
				if($tok[0] == $this->collId){
					//Collect only userTaxonomyIds that are relevant to current collid
					$udIdArr[] = $tok[1];
				}
			}
		}
		//Grab taxonomic node id and geographic scopes
		$editTidArr = array();
		$sqlut = 'SELECT idusertaxonomy, tid, geographicscope '.
			'FROM usertaxonomy '.
			'WHERE editorstatus = "OccurrenceEditor" AND uid = '.$GLOBALS['SYMB_UID'];
		//echo $sqlut;
		$rsut = $this->conn->query($sqlut);
		while($rut = $rsut->fetch_object()){
			if(in_array('all',$udIdArr) || in_array($rut->idusertaxonomy,$udIdArr)){
				//Is an approved editor for given collection
				$editTidArr[2][$rut->tid] = $rut->geographicscope;
			}
			else{
				//Is a taxonomic editor, but not explicitly approved for this collection
				$editTidArr[3][$rut->tid] = $rut->geographicscope;
			}
		}
		$rsut->free();
		//Get relevant tids for active occurrence
		if($editTidArr){
			$occTidArr = array();
			$tid = 0;
			$sciname = '';
			$family = '';
			if($this->occurrenceMap && $this->occurrenceMap['tidinterpreted']){
				$tid = $this->occurrenceMap['tidinterpreted'];
				$sciname = $this->occurrenceMap['sciname'];
				$family = $this->occurrenceMap['family'];
			}
			if(!$tid && !$sciname && !$family){
				$sql = 'SELECT tidinterpreted, sciname, family '.
					'FROM omoccurrences '.
					'WHERE occid = '.$this->occid;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$tid = $r->tidinterpreted;
					$sciname = $r->sciname;
					$family = $r->family;
				}
				$rs->free();
			}
			//Get relevant tids
			if($tid){
				$occTidArr[] = $tid;
				$rs2 = $this->conn->query('SELECT parenttid FROM taxaenumtree WHERE (taxauthid = 1) AND (tid = '.$tid.')');
				while($r2 = $rs2->fetch_object()){
					$occTidArr[] = $r2->parenttid;
				}
				$rs2->free();
			}
			elseif($sciname || $family){
				//Get all relevant tids within the taxonomy hierarchy
				$sqlWhere = '';
				if($sciname){
					//Try to isolate genus
					$taxon = $sciname;
					$tok = explode(' ',$sciname);
					if(count($tok) > 1){
						if(strlen($tok[0]) > 2) $taxon = $tok[0];
					}
					$sqlWhere .= '(t.sciname = "'.$this->cleanInStr($taxon).'") ';
				}
				elseif($family){
					$sqlWhere .= '(t.sciname = "'.$this->cleanInStr($family).'") ';
				}
				if($sqlWhere){
					$sql2 = 'SELECT e.parenttid '.
						'FROM taxaenumtree e INNER JOIN taxa t ON e.tid = t.tid '.
						'WHERE e.taxauthid = 1 AND ('.$sqlWhere.')';
					//echo $sql2;
					$rs2 = $this->conn->query($sql2);
					while($r2 = $rs2->fetch_object()){
						$occTidArr[] = $r2->parenttid;
					}
					$rs2->free();
				}
			}
			if($occTidArr){
				//Check to see if approved tids have overlap
				if(array_key_exists(2,$editTidArr) && array_intersect(array_keys($editTidArr[2]),$occTidArr)){
					$isEditor = 2;
					//TODO: check to see if specimen is within geographic scope
				}
				//If not, check to see if unapproved tids have overlap (e.g. taxon editor, but w/o explicit rights
				if(!$isEditor){
					if(array_key_exists(3,$editTidArr) && array_intersect(array_keys($editTidArr[3]),$occTidArr)){
						$isEditor = 3;
						//TODO: check to see if specimen is within geographic scope
					}
				}
			}
		}
		return $isEditor;
	}

	//Misc data support functions
	public function getCollectionList($limitToUser = true){
		$retArr = array();
		$sql = 'SELECT collid, collectionname FROM omcollections ';
		if($limitToUser){
			$collArr = array('0');
			if(isset($GLOBALS['USER_RIGHTS']['CollAdmin'])) $collArr = $GLOBALS['USER_RIGHTS']['CollAdmin'];
			$sql .= 'WHERE (collid IN('.implode(',',$collArr).')) ';
			if(isset($GLOBALS['USER_RIGHTS']['CollEditor'])){
				$sql .= 'OR (collid IN('.implode(',',$GLOBALS['USER_RIGHTS']['CollEditor']).') AND colltype = "General Observations")';
			}
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getPaleoGtsTerms(){
		$retArr = array();
		if(isset($this->collMap['paleoActivated'])){
			$sql = 'SELECT gtsterm, rankid FROM omoccurpaleogts ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->gtsterm] = $r->rankid;
			}
			$rs->free();
			ksort($retArr);
		}
		return $retArr;
	}

	public function getExsiccatiList(){
		$retArr = array();
		if($this->collId){
			$sql = 'SELECT DISTINCT t.ometid, t.title, t.abbreviation '.
				'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
				'INNER JOIN omexsiccatiocclink l ON n.omenid = l.omenid '.
				'INNER JOIN omoccurrences o ON l.occid = o.occid '.
				'WHERE (o.collid = '.$this->collId.') '.
				'ORDER BY t.title ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->ometid] = $r->title.($r->abbreviation?' ['.$r->abbreviation.']':'');
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getQuickHost(){
		$retArr = Array();
		if($this->occid){
			$sql = 'SELECT associd, verbatimsciname FROM omoccurassociations WHERE relationship = "host" AND occid = '.$this->occid.' ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['associd'] = $r->associd;
				$retArr['verbatimsciname'] = $r->verbatimsciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getAssociationControlVocab(){
		$retArr = array();
		$sql = 'SELECT t.cvTermID, t.term '.
			'FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v ON t.cvID = v.cvID '.
			'WHERE v.tablename = "omoccurassociations" AND v.fieldName = "relationship" ORDER BY term';
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				$retArr[$r->cvTermID] = $r->term;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function isCrowdsourceEditor(){
		$isEditor = false;
		if($this->occid){
			$sql = 'SELECT reviewstatus, uidprocessor FROM omcrowdsourcequeue WHERE occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->reviewstatus == 0){
					//crowdsourcing status is open for editing
					$isEditor = true;
				}
				elseif($r->reviewstatus == 5 && $r->uidprocessor == $GLOBALS['SYMB_UID']){
					//CS status is pending (=5) and active user was original editor
					$isEditor = true;
				}
			}
			$rs->free();
		}
		return $isEditor;
	}

	public function traitCodingActivated(){
		$bool = false;
		$sql = 'SELECT traitid FROM tmtraits LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $bool = true;
		$rs->free();
		return $bool;
	}

	//Setters and getters
	public function setOccId($id){
		if(is_numeric($id)){
			$this->occid = $id;
		}
	}

	public function getOccId(){
		return $this->occid;
	}

	public function setOccIndex($index){
		if(is_numeric($index)){
			$this->occIndex = $index;
		}
	}

	public function getOccIndex(){
		return $this->occIndex;
	}

	public function setDirection($cnt){
		if(is_numeric($cnt) && $cnt){
			$this->direction = $cnt;
		}
	}

	private function setOccidIndexArr($occidStr){
		if(preg_match('/^[,\d]+$/', $occidStr)){
			$this->occidIndexArr = explode(',',$occidStr);
		}
	}

	public function getOccidIndexStr(){
		return implode(',', $this->occidIndexArr);
	}

	public function setCollId(&$collid){
		if(is_numeric($collid)) $this->collId = $collid;
		if($this->collId !== 0 || $collid != $this->collId){
			unset($this->collMap);
			$this->collMap = array();
			$this->setCollMap();
			$collid = $this->collId;
		}
	}

	public function getCollId(){
		if($this->collId === false) $this->setCollMap();
		return $this->collId;
	}

	public function getQueryVariables(){
		return $this->qryArr;
	}

	public function isPersonalManagement(){
		return $this->isPersonalManagement;
	}

	public function setCrowdSourceMode($m){
		if(is_numeric($m)) $this->crowdSourceMode = $m;
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	public function getErrorStr(){
		if($this->errorArr) return implode(';<br/>',$this->errorArr);
		else return '';
	}

	//Misc functions
	private function encodeStrTargeted($inStr, $inCharset, $outCharset){
		if($inCharset == $outCharset) return $inStr;
		$retStr = $inStr;
		$retStr = mb_convert_encoding($retStr, $outCharset, mb_detect_encoding($retStr));
		return $retStr;
	}

	protected function encodeStr($inStr){
		$retStr = $inStr;
		if($inStr){
			$retStr = mb_convert_encoding($retStr, $GLOBALS['CHARSET'], mb_detect_encoding($retStr));
 		}
		return $retStr;
	}

	protected function cleanOutArr(&$arr){
		foreach($arr as $k => $v){
			if(is_array($v)) $this->cleanOutArr($arr[$k]);
			else $arr[$k] = $this->cleanOutStr($v);
		}
	}

	protected function cleanOutStr($str){
		if(!is_string($str) && !is_numeric($str) && !is_bool($str)) $str = '';
		$str = htmlspecialchars($str, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
		return $str;
	}

	protected function cleanInStr($str){
		$newStr = $str;
		if($newStr){
			$newStr = trim($str);
			$newStr = preg_replace('/\s\s+/', ' ',$newStr);
			$newStr = $this->conn->real_escape_string($newStr);
		}
		return $newStr;
	}

	protected function cleanRawFragment($str){
		$newStr = $str;
		if($newStr){
			$newStr = trim($str);
			$newStr = $this->encodeStr($newStr);
			$newStr = $this->conn->real_escape_string($newStr);
		}
		return $newStr;
	}
}
?>
