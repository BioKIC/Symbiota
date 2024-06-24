<?php
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

abstract class TaxaSearchType {
	const  ANY_NAME				= 1;
	const  SCIENTIFIC_NAME		= 2;
	const  FAMILY_ONLY			= 3;
	const  TAXONOMIC_GROUP		= 4;
	const  COMMON_NAME			= 5;

	public static $_list		   = array(1,2,3,4,5);

	public static function anyNameSearchTag ( $taxaSearchType ) {
		global $LANG;
		$key = 'SELECT_1-'.$taxaSearchType;
		if (array_key_exists($key,$LANG)) {
			return $LANG[$key];
		}
		return "Unsupported";
	}

	public static function taxaSearchTypeFromAnyNameSearchTag ( $searchTag ) {
		foreach (TaxaSearchType::$_list as $taxaSearchType) {
			if (TaxaSearchType::anyNameSearchTag($taxaSearchType) == $searchTag) {
				return $taxaSearchType;
			}
		}
		return 3;
	}
}

class OccurrenceTaxaManager {

	protected $conn	= null;
	protected $taxaArr = array();
	protected $taxAuthId = 1;
	private $exactMatchOnly = false;
	private $taxaSearchTerms = array();

	public function __construct($type='readonly'){
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}
	public function __destruct(){
		if ((!($this->conn === false)) && (!($this->conn === null))) {
			$this->conn->close();
			$this->conn = null;
		}
	}

	public function setTaxonRequestVariable($inputArr = null, $exactMatchOnly = false){
		if($exactMatchOnly) $this->exactMatchOnly = true;
		//Set taxa search terms
		if(isset($inputArr['taxa']) && $inputArr['taxa']){
			$taxaStr = $this->cleanInputStr($inputArr['taxa']);
		}
		else{
			$taxaStr = str_replace(';',',',$this->cleanInputStr($_REQUEST['taxa']));
		}
		if($taxaStr){
			$this->taxaArr['search'] = $taxaStr;
			//Set usage of taxonomic thesaurus
			$this->taxaArr['usethes'] = 0;
			if(isset($inputArr['usethes']) && $inputArr['usethes']){
				$this->taxaArr['usethes'] = 1;
			}
			elseif(array_key_exists('usethes',$_REQUEST) && $_REQUEST['usethes']){
				$this->taxaArr['usethes'] = 1;
			}
			//Set default taxa type
			$defaultTaxaType = TaxaSearchType::SCIENTIFIC_NAME;
			if(isset($inputArr['taxontype']) && is_numeric($inputArr['taxontype'])){
				$defaultTaxaType = $inputArr['taxontype'];
			}
			elseif(array_key_exists('taxontype',$_REQUEST) && is_numeric($_REQUEST['taxontype'])){
				$defaultTaxaType = $_REQUEST['taxontype'];
			}
			$this->taxaArr['taxontype'] = $defaultTaxaType;
			//Initerate through taxa and process
			$this->taxaSearchTerms = explode(',',$taxaStr);
			foreach($this->taxaSearchTerms as $k => $term){
				$searchTerm = $this->cleanInputStr($term);
				if(!$searchTerm){
					unset($this->taxaSearchTerms);
					continue;
				}
				$this->taxaSearchTerms[$k] = $searchTerm;
				$taxaType = $defaultTaxaType;
				if($defaultTaxaType == TaxaSearchType::ANY_NAME) {
					$n = explode(': ',$searchTerm);
					if (count($n) > 1) {
						$taxaType = TaxaSearchType::taxaSearchTypeFromAnyNameSearchTag($n[0]);
						$searchTerm = $n[1];
					}
					else{
						$taxaType = TaxaSearchType::SCIENTIFIC_NAME;
					}
				}
				$this->setSciNamesByVerns($searchTerm);
				$sql = 'SELECT t.sciname, t.tid, t.rankid FROM taxa t ';
				if(is_numeric($searchTerm)){
					$searchTerm = filter_var($searchTerm, FILTER_SANITIZE_NUMBER_INT);
					if($this->taxaArr['usethes']){
						$sql .= 'INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$searchTerm.')';
					}
					else{
						$sql .= 'WHERE (t.tid = '.$searchTerm.')';
					}
				}
				else{
					if($this->taxaArr['usethes']){
						$sql .= 'INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted
							INNER JOIN taxa t2 ON ts.tid = t2.tid
							WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (t2.sciname IN("'.$this->cleanInStr($searchTerm).'"))';
					}
					else{
						$sql .= 'WHERE t.sciname IN("'.$this->cleanInStr($searchTerm).'")';
					}
				}
				if($rs = $this->conn->query($sql)){
					if($rs->num_rows){
						while($r = $rs->fetch_object()){
							$this->taxaArr['taxa'][$r->sciname]['tid'][$r->tid] = $r->rankid;
							if($r->rankid == 140){
								$taxaType = TaxaSearchType::FAMILY_ONLY;
							}
							elseif($r->rankid < 180){
								$taxaType = TaxaSearchType::TAXONOMIC_GROUP;
							}
							else{
								$taxaType = TaxaSearchType::SCIENTIFIC_NAME;
							}
							$this->taxaArr['taxa'][$r->sciname]['taxontype'] = $taxaType;
						}
					}
					else{
						$this->taxaArr['taxa'][$searchTerm]['taxontype'] = $taxaType;
					}
					$rs->free();
				}
			}
			if($this->taxaArr['usethes']){
				$this->setSynonyms();
			}
		}
	}

	private function setSciNamesByVerns(&$searchTerm) {
		if(preg_match('/^(.+)\s{1}\((.+)\)$/', $searchTerm, $m)){
			$searchTerm = $m[2];
		}
		else{
			$sql = 'SELECT DISTINCT v.VernacularName, t.tid, t.sciname, t.rankid
				FROM taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID
				INNER JOIN taxa t ON t.TID = ts.tidaccepted
				WHERE (ts.taxauthid = ?) AND (v.VernacularName IN(?))
				ORDER BY t.rankid LIMIT 10';
			if ($statement = $this->conn->prepare($sql)) {
				$statement->bind_param("ss", $this->taxAuthId, $searchTerm);
				$statement->execute();
				$result = $statement->get_result();
				while($row = $result->fetch_object()){
					$vernName = $row->VernacularName;
					if($row->rankid == 140){
						$this->taxaArr['taxa'][$vernName]['families'][] = $row->sciname;
					}
					else{
						$this->taxaArr['taxa'][$vernName]['scinames'][] = $row->sciname;
					}
					$this->taxaArr['taxa'][$vernName]['tid'][$row->tid] = $row->rankid;
				}
				$result->free();
				$statement->close();
			}
		}
	}

	private function setSynonyms(){
		if(isset($this->taxaArr['taxa'])){
			foreach($this->taxaArr['taxa'] as $searchStr => $searchArr){
				if(isset($searchArr['tid']) && $searchArr['tid']){
					foreach($searchArr['tid'] as $tid => $rankid){
						$accArr = array();
						$accArr[] = $tid;
						if($rankid >= 180 && $rankid <= 220){
							//Get accepted children
							$sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid
								FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
								INNER JOIN taxaenumtree e ON t.tid = e.tid
								WHERE (e.parenttid IN('.$tid.')) AND (ts.TidAccepted = ts.tid) AND (ts.taxauthid = ' . $this->taxAuthId . ') AND (e.taxauthid = ' . $this->taxAuthId . ')' ;
							/*
							$sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid '.
								'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
								'WHERE (ts.parenttid IN('.$tid.')) AND (ts.TidAccepted = ts.tid) AND (ts.taxauthid = ' . $this->taxAuthId . ') ' ;
							*/
							//echo 'sql1: '.$sql1.'<br>';
							$rs1 = $this->conn->query($sql1);
							while($r1 = $rs1->fetch_object()){
								$accArr[] = $r1->tid;
								if(!isset($this->taxaArr['taxa'][$r1->sciname])){
									if($rankid == 220) $this->taxaArr['taxa'][$r1->sciname]['tid'][$r1->tid] = $r1->rankid;
									else $this->taxaArr['taxa'][$searchStr]['TID_BATCH'][$r1->tid] = '';
								}
							}
							$rs1->free();
						}
						//Get synonyms of all accepted taxa
						$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t2.sciname as accepted '.
							'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
							'INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid '.
							'WHERE (ts.TidAccepted != ts.tid) AND (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tidaccepted IN('.implode(',',$accArr).')) ';
						$rs2 = $this->conn->query($sql2);
						while($r2 = $rs2->fetch_object()) {
 							if($rankid >= 220) $this->taxaArr['taxa'][$r2->accepted]['synonyms'][$r2->tid] = $r2->sciname;
 							else $this->taxaArr['taxa'][$searchStr]['TID_BATCH'][$r2->tid] = '';
						}
						$rs2->free();
					}
				}
			}
		}
	}

	public function getTaxonWhereFrag(){
		$sqlWhereTaxa = '';
		if(isset($this->taxaArr['taxa'])){
			$tidInArr = array();
			$taxonType = $this->taxaArr['taxontype'];
			foreach($this->taxaArr['taxa'] as $searchTaxon => $searchArr){
				$cleanedSearchTaxon = $this->cleanInStr($searchTaxon);
				if(isset($searchArr['taxontype'])) $taxonType = $searchArr['taxontype'];
				if($taxonType == TaxaSearchType::TAXONOMIC_GROUP){
					//Class, order, or other higher rank
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						//$sqlWhereTaxa .= 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE (taxauthid = '.$this->taxAuthId.') AND (parenttid IN('.trim($tidStr,',').') OR (tid = '.trim($tidStr,',').')))) ';
						$sqlWhereTaxa .= 'OR (e.parenttid IN('.implode(',', $tidArr).') ';
						$sqlWhereTaxa .= 'OR (e.tid IN('.implode(',', $tidArr).')) ';
						if(isset($searchArr['synonyms'])) $sqlWhereTaxa .= 'OR (e.tid IN('.implode(',',array_keys($searchArr['synonyms'])).')) ';
						//$tidInArr = array_merge($tidInArr,$tidArr);
						//if(isset($searchArr['synonyms'])) $tidInArr = array_merge($tidInArr,array_keys($searchArr['synonyms']));
						$sqlWhereTaxa .= ') ';
					}
					else{
						//Unable to find higher taxon within taxonomic tree, thus return nothing
						$sqlWhereTaxa .= 'OR (o.tidinterpreted = 0) ';
					}
				}
				elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
					//$sqlWhereTaxa .= 'OR ((o.family = "'.$searchTaxon.'") OR (o.sciname = "'.$searchTaxon.'")) ';
					//$sqlWhereTaxa .= 'OR (((ts.family = "'.$searchTaxon.'") AND (ts.taxauthid = '.$this->taxAuthId.')) OR (o.family = "'.$searchTaxon.'") OR (o.sciname = "'.$searchTaxon.'")) ';
					//$sqlWhereTaxa .= 'OR (((ts.family = "'.$searchTaxon.'") AND (ts.taxauthid = '.$this->taxAuthId.')) OR o.sciname = "'.$searchTaxon.'") ';
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						$sqlWhereTaxa .= 'OR ((ts.family = "'.$cleanedSearchTaxon.'") OR (ts.tid IN('.implode(',', $tidArr).'))) ';
					}
					else{
						$sqlWhereTaxa .= 'OR ((o.family = "'.$cleanedSearchTaxon.'") OR (o.sciname = "'.$cleanedSearchTaxon.'")) ';
					}
				}
				else{
					if($taxonType == TaxaSearchType::COMMON_NAME){
						$famArr = $this->setCommonNameWhereTerms($searchArr, $tidInArr);
						if($famArr) $sqlWhereTaxa .= 'OR (o.family IN("'.implode('","',$famArr).'")) ';
					}
					if(isset($searchArr['TID_BATCH'])){
						$tidInArr = array_merge($tidInArr, array_keys($searchArr['TID_BATCH']));
						if(isset($searchArr['tid'])) $tidInArr = array_merge($tidInArr, array_keys($searchArr['tid']));
					}
					else{
						$term = $this->cleanInStr(trim($searchTaxon,'%'));
						//$term = preg_replace('/\s{1}.{1,2}\s{1}/', ' _ ', $term);
						$term = preg_replace(array('/\s{1}x\s{1}/','/\s{1}X\s{1}/','/\s{1}\x{00D7}\s{1}/u'), ' _ ', $term);
						if(array_key_exists('tid',$searchArr)){
							$rankid = current($searchArr['tid']);
							$tidArr = array_keys($searchArr['tid']);
							//$sqlWhereTaxa .= "OR (o.tidinterpreted IN(".implode(',',$tidArr).")) ";
							$tidInArr = array_merge($tidInArr, $tidArr);
							//Return matches that are not linked to thesaurus
							if($rankid > 179){
								if($this->exactMatchOnly) $sqlWhereTaxa .= 'OR (o.sciname = "' . $term . '") ';
								else $sqlWhereTaxa .= 'OR (o.sciname LIKE "' . $term . '%") ';
							}
						}
						else{
							//Protect against someone trying to download big pieces of the occurrence table through the user interface
							if(strlen($term) < 4) $term .= ' ';
							/*
							if(strpos($term, ' ') || strpos($term, '%')){
								//Return matches for "Pinus a"
								$sqlWhereTaxa .= "OR (o.sciname LIKE '" . $term . "%') ";
							}
							else{
								$sqlWhereTaxa .= "OR (o.sciname LIKE '" . $term . " %') ";
							}
							*/
							if($this->exactMatchOnly){
								$sqlWhereTaxa .= 'OR (o.sciname = "' . $term . '") ';
							}
							else{
								$sqlWhereTaxa .= 'OR (o.sciname LIKE "' . $term . '%") ';
								if(!strpos($term,' _ ')){
									//Accommodate for formats of hybrid designations within input and target data (e.g. x, multiplication sign, etc)
									$term2 = preg_replace('/^([^\s]+\s{1})/', '$1 _ ', $term);
									$sqlWhereTaxa .= 'OR (o.sciname LIKE "' . $term2 . '%") ';
								}
							}
						}
					}
					if(array_key_exists('synonyms',$searchArr)){
						$synArr = $searchArr['synonyms'];
						if($synArr){
							if($taxonType == TaxaSearchType::SCIENTIFIC_NAME || $taxonType == TaxaSearchType::COMMON_NAME){
								foreach($synArr as $synTid => $sciName){
									if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
										$sqlWhereTaxa .= 'OR (o.family = "' . $sciName . '") ';
									}
								}
							}
							//$sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',array_keys($synArr)).')) ';
							$tidInArr = array_merge($tidInArr,array_keys($synArr));
						}
					}
				}
			}
			if($tidInArr) $sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',array_unique($tidInArr)).')) ';
			$sqlWhereTaxa = 'AND ('.trim(substr($sqlWhereTaxa,3)).') ';
			if(strpos($sqlWhereTaxa,'e.parenttid')) $sqlWhereTaxa .= 'AND (e.taxauthid = '.$this->taxAuthId.') ';
			if(strpos($sqlWhereTaxa,'ts.family')) $sqlWhereTaxa .= 'AND (ts.taxauthid = '.$this->taxAuthId.') ';
		}
		if($sqlWhereTaxa) return $sqlWhereTaxa;
		else return false;
	}

	private function setCommonNameWhereTerms($searchArr, &$tidInArr){
		$famArr = array();
		if(array_key_exists('families',$searchArr)){
			$famArr = $searchArr['families'];
		}
		if(array_key_exists('tid',$searchArr)){
			$tidArr = array();
			foreach($searchArr['tid'] as $tid => $rankid){
				$tidInArr[] = $tid;  //add tid to search records at that rank
				if($rankid <= 140) $tidArr[] = $tid;
			}
			if($tidArr){
				$tidStr = implode(',', $tidArr);
				$sql = 'SELECT DISTINCT t.sciname '.
					'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
					'WHERE (t.rankid = 140) AND (t.tid IN(' . $tidStr . ')) OR ((e.taxauthid = ' . $this->taxAuthId . ') AND (e.parenttid IN(' . $tidStr . ')))';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$famArr[] = $r->sciname;
				}
				$rs->free();
			}
		}
		return array_unique($famArr);
	}

	//setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)) $this->taxAuthId = $id;
	}

	//Misc functions
	public function getTaxaSearchStr(){
		$returnArr = Array();
		if(isset($this->taxaArr['taxa'])){
			foreach($this->taxaArr['taxa'] as $taxonName => $taxonArr){
				$str = '';
				if(isset($taxonArr['taxontype']) && $this->taxaArr['taxontype'] == TaxaSearchType::ANY_NAME) $str .= TaxaSearchType::anyNameSearchTag($taxonArr['taxontype']).': ';
				$str .= $taxonName;
				if(array_key_exists("scinames",$taxonArr)){
					$str .= " => ".implode(",",$taxonArr["scinames"]);
				}
				if(array_key_exists("synonyms",$taxonArr)){
					$str .= " (".implode(", ",$taxonArr["synonyms"]).")";
				}
				$returnArr[] = $str;
			}
		}
		return implode(", ", $returnArr);
	}

	public function getTaxaSearchTerm(){
		if(isset($this->taxaArr['search'])) return $this->cleanOutStr($this->taxaArr['search']);
		return '';
	}

	public function cleanOutArray($inputArray){
		if(is_array($inputArray)){
			foreach($inputArray as $key => $value){
				if(is_array($value)){
					$inputArray[$key] = $this->cleanOutArray($value);
				}
				else{
					$inputArray[$key] = $this->cleanOutStr($value);
				}
			}
		}
		return $inputArray;
	}

	public function cleanOutStr($str){
		if(!is_string($str) && !is_numeric($str) && !is_bool($str)) $str = '';
		return htmlspecialchars($str, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
	}

	protected function cleanInputStr($str){
		if(!is_string($str) && !is_numeric($str) && !is_bool($str)) return '';
		if(stripos($str, 'sleep(') !== false) return '';
		$str = preg_replace('/%%+/', '%',$str);
		$str = preg_replace('/^[\s%]+/', '',$str);
		$str = trim($str,' ,;');
		if($str == '%') $str = '';
		$str = strip_tags($str);
		$str = htmlspecialchars($str, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML401);
		return $str;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>