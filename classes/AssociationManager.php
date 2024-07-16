<?php

use function PHPUnit\Framework\returnValue;

include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class AssociationManager extends OccurrenceTaxaManager{



	// private $taxaArr = Array();
	// private $targetStr = '';
	// private $targetTid = 0;
	// private $targetRankId = 0;
	// private $taxAuthId = 1;
	// private $taxonomyMeta = array();
	// private $displayAuthor = false;
	// private $displayFullTree = false;
	// private $displaySubGenera = false;
	// private $matchOnWholeWords = true;
	// private $limitToOccurrences = false;
	private $isEditor = false;
	// private $nodeCnt = 0;

	function __construct(){
		parent::__construct();
		if($GLOBALS['USER_RIGHTS']){
			if($GLOBALS['IS_ADMIN'] || array_key_exists("Taxonomy",$GLOBALS['USER_RIGHTS'])){
				$this->isEditor = true;
			}
		}
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getRelationshipTypes(){
		$sql = 'SELECT DISTINCT relationship from omoccurassociations';
		if($statement = $this->conn->prepare($sql)){
			$statement->execute();
			$result = $statement->get_result();
			$relationshipTypes = [];
			while ($row = $result->fetch_assoc()) {
				$relationshipTypes[] = $row['relationship'];
			}
			$statement->close();
			return $relationshipTypes;
		}else{
			return [];
		}
	}

	public function getAssociatedRecords($relationshipType, $associationArr){
		// var_dump('entering getAssociatedRecords. $relationshipType is: ' . $relationshipType . ' and $taxonIdOrSciname is: ' . $taxonIdOrSciname);
		// "Forward" association
		// $sql = "AND (o.occid IN (SELECT DISTINCT occid FROM omoccurassociations WHERE relationship ='" . $relationshipType . "' AND ";
		$sql = "AND (o.occid IN (SELECT DISTINCT o.occid FROM omoccurrences o INNER JOIN omoccurassociations oa on o.occid=oa.occid WHERE oa.relationship ='" . $relationshipType . "' ";

		// echo "<div>Count getAssociatedTaxonWhereFrag: " . $this->getAssociatedTaxonWhereFrag($associationArr) . "</div>";

		$sql .= $this->getAssociatedTaxonWhereFrag($associationArr) . ')';

		// // TODO update taxon stuff to be more labile
		// if(is_numeric($taxonIdOrSciname)){
		// 	// $sql = $sql . "tid = '" . $taxonIdOrSciname . "')";
		// 	$sql = $sql . "o.tidInterpreted = '" . $taxonIdOrSciname . "')";
		// }
		// if(is_string($taxonIdOrSciname)){
		// 	// $sql = $sql . "verbatimSciname = '" . $taxonIdOrSciname . "')";
		// 	$sql = $sql . "o.sciname = '" . $taxonIdOrSciname . "')";
		// }

		// @TODO handle situation where the associationType is external and there's no occidAssociate; pull resourceUrl instead? // @TODO remove from the current query
		// $sql =. 'SELECT DISTINCT resourceUrl from omoccurassociations WHERE associationType="externalOccurrence" AND occidAssociate IS NULL AND relationship = ' . $relationshipType . ' AND ';
		// if(is_numeric($taxonIdOrSciname)){
		// 	$sql = $sql . 'tid = ' . $taxonIdOrSciname . ')';
		// }
		// if(is_string($taxonIdOrSciname)){
		// 	$sql = $sql . 'verbatimSciname = ' . $taxonIdOrSciname . ')';
		// }


		// "Reverse" association
		$reverseAssociationType = $this->getInverseRelationshipOf($relationshipType); // @TODO still have to do something with this
		// var_dump('$reverseAssociationType is: ' . $reverseAssociationType);
		$sql .= " OR o.occid IN (SELECT DISTINCT oa.occidAssociate FROM omoccurrences o INNER JOIN omoccurassociations oa on o.occid=oa.occid INNER JOIN omoccurdeterminations od ON oa.occid=od.occid where oa.relationship = '" . $reverseAssociationType . "' "; //isCurrent="1" AND my thought was that we want these results to be as relaxed as possible

		$sql .= $this->getAssociatedTaxonWhereFrag($associationArr) . ')';
		// if(is_numeric($taxonIdOrSciname)){
		// 	$sql .= "od.taxonConceptID = '" . $taxonIdOrSciname . "' OR od.tidInterpreted IN(" . @TODO . "))) ";
		// }
		// if(is_string($taxonIdOrSciname)){
		// 	$sql .= "od.sciname = '" . $taxonIdOrSciname . "' OR od.tidInterpreted IN (" . @TODO . "))) ";
		// }
		// var_dump('returning: ' . $sql);
		// echo "<div>Count at the end of getAssociatedRecords: " . $sql . "</div>";
		return $sql;
	}

	public function getAssociatedTaxonWhereFrag($associationArr){
		// echo "<div>getAssociatedTaxonWhereFrag called</div>";
		$sqlWhereTaxa = '';
		if(isset($associationArr['taxa'])){
			// var_dump($associationArr);
			$tidInArr = array();
			$taxonType = $associationArr['associated-taxa'];
			// var_dump($taxonType);
			foreach($associationArr['taxa'] as $searchTaxon => $searchArr){
				// var_dump($searchArr);
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
						$sqlWhereTaxa .= 'OR ((ts.family = "'.$searchTaxon.'") OR (ts.tid IN('.implode(',', $tidArr).'))) ';
					}
					else{
						$sqlWhereTaxa .= 'OR ((o.family = "'.$searchTaxon.'") OR (o.sciname = "'.$searchTaxon.'")) ';
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
								else $sqlWhereTaxa .= "OR (o.sciname LIKE '" . $term . "%') ";
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
									$sqlWhereTaxa .= "OR (o.sciname LIKE '" . $term2 . "%') ";
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
		// else{
		// 	var_dump('got here a');
		// }
		// var_dump($sqlWhereTaxa);
		if($sqlWhereTaxa) return $sqlWhereTaxa;
		else return false;
	}

	// public function getAssociatedTaxa($relationshipType, $taxonIdOrSciname){
	// 	$returnVal = [];

	// 	// @TODO still have to handle cases where $taxonIdOrSciname is less specific than genus + specific epithet

	// 	// "Forward" association
	// 	$sqlBase = 'SELECT DISTINCT occid FROM omoccurassociations WHERE relationship = ? AND ';
	// 	// $sqlBase = 'IN (SELECT DISTINCT occid FROM omoccurassociations WHERE relationship = ? AND ';
	// 	if(is_numeric($taxonIdOrSciname)){
	// 		// var_dump('got here 1');
	// 		$sql = $sqlBase . 'tid = ?';
	// 		$returnVal['sql'] = array_key_exists('sql', $returnVal) ? ($returnVal['sql'] . '; ' . $sql) : $sql;
	// 		if($statement = $this->conn->prepare($sql)){
	// 			$statement->bind_param('si', $relationshipType, $taxonIdOrSciname);
	// 			$returnVal = array_merge($returnVal, $this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 		}
	// 	}
	// 	if(is_string($taxonIdOrSciname)){
	// 		// var_dump('got here 2');
	// 		$sql = $sqlBase . 'verbatimSciname = ?';
	// 		$returnVal['sql'] = array_key_exists('sql', $returnVal) ? ($returnVal['sql'] . '; ' . $sql) : $sql;
	// 		if($statement = $this->conn->prepare($sql)){
	// 			$statement->bind_param('ss', $relationshipType, $taxonIdOrSciname);
	// 			$returnVal = array_merge($returnVal, $this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 		}
	// 	}

	// 	// @TODO handle situation where the associationType is external and there's no occidAssociate; pull resourceUrl instead?
	// 	$sqlBase = 'SELECT DISTINCT resourceUrl from omoccurassociations WHERE associationType="externalOccurrence" AND occidAssociate IS NULL AND relationship = ? AND ';
	// 	if(is_numeric($taxonIdOrSciname)){
	// 		// var_dump('got here 3');
	// 		$sql = $sqlBase . 'tid = ?';
	// 		$returnVal['sql'] = array_key_exists('sql', $returnVal) ? ($returnVal['sql'] . '; ' . $sql) : $sql;
	// 		if($statement = $this->conn->prepare($sql)){
	// 			$statement->bind_param('si', $relationshipType, $taxonIdOrSciname);
	// 			$returnVal = array_merge($returnVal, $this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 		}
	// 	}
	// 	if(is_string($taxonIdOrSciname)){
	// 		// var_dump('got here 4');
	// 		$sql = $sqlBase . 'verbatimSciname = ?';
	// 		$returnVal['sql'] = array_key_exists('sql', $returnVal) ? ($returnVal['sql'] . '; ' . $sql) : $sql;
	// 		if($statement = $this->conn->prepare($sql)){
	// 			$statement->bind_param('ss', $relationshipType, $taxonIdOrSciname);
	// 			$returnVal = array_merge($returnVal, $this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 		}
	// 	}


	// 	// "Reverse" association
	// 	$reverseAssociationType = $this->getInverseRelationshipOf($relationshipType); // @TODO still have to do something with this
	// 	// var_dump('$reverseAssociationType is: ');
	// 	// var_dump($reverseAssociationType);
	// 	$sqlBase = 'SELECT DISTINCT oa.occidAssociate FROM omoccurassociations oa INNER JOIN omoccurdeterminations od ON oa.occid=od.occid where relationship = ? AND '; //isCurrent="1" AND my thought was that we want these results to be as relaxed as possible
	// 	if(is_numeric($taxonIdOrSciname)){
	// 		// var_dump('got here 5');
	// 		$sql = $sqlBase . 'od.taxonConceptID = ?';
	// 		$returnVal['sql'] = array_key_exists('sql', $returnVal) ? ($returnVal['sql'] . '; ' . $sql) : $sql;
	// 		if($statement = $this->conn->prepare($sql)){
	// 			$statement->bind_param('si', $reverseAssociationType, $taxonIdOrSciname);
	// 			$returnVal = array_merge($returnVal, $this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 		}
	// 	}
	// 	if(is_string($taxonIdOrSciname)){
	// 		// var_dump('got here 6');
	// 		$sql = $sqlBase . 'od.sciname = ?';
	// 		$returnVal['sql'] = array_key_exists('sql', $returnVal) ? ($returnVal['sql'] . '; ' . $sql) : $sql;
	// 		if($statement = $this->conn->prepare($sql)){
	// 			$statement->bind_param('ss', $reverseAssociationType, $taxonIdOrSciname);
	// 			// var_dump($reverseAssociationType);
	// 			// var_dump($taxonIdOrSciname);
	// 			// var_dump($this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 			$returnVal = array_merge($returnVal, $this->fetchTargetCriteriaFromStatementWithBoundParams($statement));
	// 		}
	// 	}
	// 	$statement->close();
	// 	return $returnVal;
	// }

	public function getInverseRelationshipOf($relationship){
		// var_dump($relationship);
		$sql = 'SELECT inverseRelationship FROM ctcontrolvocabterm where term = ?';
		if($statement = $this->conn->prepare($sql)){
			$statement->bind_param('s', $relationship);
			$statement->execute();
			$result = $statement->get_result();
			$returnVal = '';
			if ($row = $result->fetch_assoc()) {
				$returnVal = $row['inverseRelationship'];
			}
			$statement->close();
			return $returnVal;
		}else{
			return '';
		}
	}

	public function fetchTargetCriteriaFromStatementWithBoundParams($statementWithBoundParams){
		if($statementWithBoundParams){
			$statementWithBoundParams->execute();
			$result = $statementWithBoundParams->get_result();
			// var_dump($result);
			$returnOccids = [];
			while ($row = $result->fetch_assoc()) {
				// var_dump($row);
				$returnOccids[] = $row;
			}
			// $statementWithBoundParams->close();
			return $returnOccids;
		}else{
			return [];
		}
	}


	// public function displayTaxonomyHierarchy(){
	// 	set_time_limit(300);
	// 	$taxaParentIndex = $this->setTaxa();
	// 	$this->adjustSubgenericNames();
	// 	$hierarchyArr = $this->buildHierarchyArr($taxaParentIndex);
	// 	if(!$hierarchyArr) return false;
	// 	$this->echoTaxonArray($hierarchyArr);
	// 	return true;
	// }

	// private function setTaxa(){
	// 	$this->primeTaxaEnumTree();
	// 	$taxaParentIndex = Array();
	// 	$zeroRank = array();
	// 	$sql = 'SELECT DISTINCT t.tid, ts.tidaccepted, t.sciname, t.author, t.rankid, ts.parenttid
	// 		FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
	// 	if($this->limitToOccurrences){
	// 		$sql .= 'INNER JOIN taxaenumtree pe ON t.tid = pe.parenttid INNER JOIN omoccurrences o ON pe.tid = o.tidInterpreted ';
	// 	}
	// 	$sql .= 'WHERE (ts.taxauthid = '.$this->taxAuthId.') ';
	// 	if($this->targetTid) $sql .= 'AND (ts.tid = '.$this->targetTid.') ';
	// 	elseif($this->targetStr){
	// 		$term = $this->targetStr;
	// 		$termArr = explode(' ',$term);
	// 		foreach($termArr as $k => $v){
	// 			if(mb_strlen($v) == 1) unset($termArr[$k]);
	// 		}
	// 		$sqlFrag = '';
	// 		if($unit1 = array_shift($termArr)) $sqlFrag =  't.unitname1 LIKE "'.$unit1.($this->matchOnWholeWords?'':'%').'" ';
	// 		if($unit2 = array_shift($termArr)) $sqlFrag .=  'AND t.unitname2 LIKE "'.$unit2.($this->matchOnWholeWords?'':'%').'" ';

	// 		if($this->matchOnWholeWords){
	// 			$sql .= 'AND ((t.sciname = "'.$this->cleanInStr($term).'") OR (t.sciname LIKE "'.$this->cleanInStr($term).' %") ';
	// 		}
	// 		else{
	// 			//Rankid >= species level and not will author included
	// 			$sql .= 'AND ((t.sciname LIKE "'.$this->cleanInStr($term).'%") ';
	// 		}
	// 		$sql .= 'OR (CONCAT(t.sciname," ",t.author) = "'.$this->cleanInStr($term).'") ';
	// 		if($sqlFrag) $sql .= 'OR ('.$sqlFrag.')';
	// 		$sql .= ') ';
	// 	}
	// 	else $sql .= 'AND (t.rankid = 10) ';
	// 	$sql .= 'ORDER BY t.rankid DESC ';
	// 	$tidAcceptedArr = array();
	// 	$rs = $this->conn->query($sql);
	// 	while($r = $rs->fetch_object()){
	// 		$tid = $r->tid;
	// 		if($tid == $r->tidaccepted || !$r->tidaccepted){
	// 			$this->taxaArr[$tid]['sciname'] = $r->sciname;
	// 			$this->taxaArr[$tid]['author'] = $r->author;
	// 			$this->taxaArr[$tid]['rankid'] = $r->rankid;
	// 			if(!$r->rankid) $zeroRank[] = $tid;
	// 			$this->taxaArr[$tid]['parenttid'] = $r->parenttid;
	// 			$this->targetRankId = $r->rankid;
	// 			$taxaParentIndex[$tid] = ($r->parenttid?$r->parenttid:0);
	// 		}
	// 		else{
	// 			$tidAcceptedArr[] = $r->tidaccepted;
	// 		}
	// 	}
	// 	$rs->free();
	// 	//Get details for synonyms
	// 	if($tidAcceptedArr){
	// 		$sql1 = 'SELECT t.tid, t.sciname, t.author, t.rankid, ts.parenttid FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.tid IN('.implode(',',$tidAcceptedArr).')';
	// 		$rs1 = $this->conn->query($sql1);
	// 		while($r1 = $rs1->fetch_object()){
	// 			$tid = $r1->tid;
	// 			$this->taxaArr[$tid]['sciname'] = $r1->sciname;
	// 			$this->taxaArr[$tid]['author'] = $r1->author;
	// 			$this->taxaArr[$tid]['rankid'] = $r1->rankid;
	// 			if(!$r1->rankid) $zeroRank[] = $tid;
	// 			$this->taxaArr[$tid]['parenttid'] = $r1->parenttid;
	// 			$this->targetRankId = $r1->rankid;
	// 			$taxaParentIndex[$tid] = ($r1->parenttid?$r1->parenttid:0);
	// 		}
	// 		$rs1->free();
	// 	}

	// 	if($this->taxaArr){
	// 		//Get direct children, but only accepted children
	// 		$tidStr = implode(',',array_keys($this->taxaArr));
	// 		$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid
	// 			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
	// 			INNER JOIN taxaenumtree e ON t.tid = e.tid ';
	// 		if($this->limitToOccurrences){
	// 			$sql2 .= 'INNER JOIN taxaenumtree pe ON t.tid = pe.parenttid INNER JOIN omoccurrences o ON pe.tid = o.tidInterpreted ';
	// 		}
	// 		$sql2 .= 'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = ts.tidaccepted) AND (e.taxauthid = '.$this->taxAuthId.')
	// 			AND ((e.parenttid IN('.$tidStr.')) OR (t.tid IN('.$tidStr.'))) ';
	// 		if(!$this->targetStr) $sql2 .= 'AND t.rankid <= 10 AND t.rankid != 0 ';
	// 		elseif($this->targetRankId < 140 && !$this->displayFullTree) $sql2 .= 'AND t.rankid <= 140 ';
	// 		$rs2 = $this->conn->query($sql2);
	// 		while($row2 = $rs2->fetch_object()){
	// 			$tid = $row2->tid;
	// 			$this->taxaArr[$tid]["sciname"] = $row2->sciname;
	// 			$this->taxaArr[$tid]["author"] = $row2->author;
	// 			$this->taxaArr[$tid]["rankid"] = $row2->rankid;
	// 			if(!$row2->rankid) $zeroRank[] = $tid;
	// 			$parentTid = $row2->parenttid;
	// 			$this->taxaArr[$tid]["parenttid"] = $parentTid;
	// 			if($parentTid) $taxaParentIndex[$tid] = $parentTid;
	// 		}
	// 		$rs2->free();

	// 		//Get all parent taxa
	// 		$sql3 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid '.
	// 			'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.parenttid '.
	// 			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
	// 			'WHERE (te.taxauthid = '.$this->taxAuthId.') AND (ts.taxauthid = '.$this->taxAuthId.') AND (te.tid IN('.$tidStr.')) ';
	// 		$rs3 = $this->conn->query($sql3);
	// 		while($row3 = $rs3->fetch_object()){
	// 			$tid = $row3->tid;
	// 			$parentTid = $row3->parenttid;
	// 			$this->taxaArr[$tid]["sciname"] = $row3->sciname;
	// 			$this->taxaArr[$tid]["author"] = $row3->author;
	// 			$this->taxaArr[$tid]["rankid"] = $row3->rankid;
	// 			if(!$row3->rankid) $zeroRank[] = $tid;
	// 			$this->taxaArr[$tid]["parenttid"] = $parentTid;
	// 			if($parentTid) $taxaParentIndex[$tid] = $parentTid;
	// 		}
	// 		$rs3->free();

	// 		//Get synonyms for all accepted taxa
	// 		$sqlSyns = 'SELECT ts.tidaccepted, t.tid, t.sciname, t.author, t.rankid
	// 			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
	// 			WHERE (ts.tid <> ts.tidaccepted) AND (ts.taxauthid = ' . $this->taxAuthId . ') AND (ts.tidaccepted IN(' . implode(',', array_keys($this->taxaArr)) . '))';
	// 		$rsSyns = $this->conn->query($sqlSyns);
	// 		while($row = $rsSyns->fetch_object()){
	// 			$synName = $row->sciname;
	// 			if($row->rankid > 140){
	// 				$synName = '<i>'.$row->sciname.'</i>';
	// 			}
	// 			if($this->displayAuthor) $synName .= ' '.$row->author;
	// 			$this->taxaArr[$row->tidaccepted]["synonyms"][$row->tid] = $synName;
	// 		}
	// 		$rsSyns->free();

	// 		//Grab parentTids that are not indexed in $taxaParentIndex. This would be due to a parent mismatch or a missing hierarchy definition
	// 		if($orphanTaxa = array_unique(array_diff($taxaParentIndex, array_keys($taxaParentIndex)))){
	// 			$sqlOrphan = 'SELECT t.tid, t.sciname, t.author, ts.parenttid, t.rankid '.
	// 				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
	// 				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = ts.tidaccepted) AND (t.tid IN ('.implode(',',$orphanTaxa).'))';
	// 			$rsOrphan = $this->conn->query($sqlOrphan);
	// 			while($row4 = $rsOrphan->fetch_object()){
	// 				$tid = $row4->tid;
	// 				$taxaParentIndex[$tid] = $row4->parenttid;
	// 				$this->taxaArr[$tid]["sciname"] = $row4->sciname;
	// 				$this->taxaArr[$tid]["author"] = $row4->author;
	// 				$this->taxaArr[$tid]["parenttid"] = $row4->parenttid;
	// 				$this->taxaArr[$tid]["rankid"] = $row4->rankid;
	// 				if(!$row4->rankid) $zeroRank[] = $tid;
	// 			}
	// 			$rsOrphan->free();
	// 		}

	// 		foreach($zeroRank as $tidToFix){
	// 			if(isset($this->taxaArr[$tid]['parenttid']) && $this->taxaArr[$this->taxaArr[$tid]['parenttid']]['rankid']){
	// 				$this->taxaArr[$tidToFix]['rankid'] = $this->taxaArr[$this->taxaArr[$tid]['parenttid']]['rankid'];
	// 			}
	// 			else $this->taxaArr[$tidToFix]['rankid'] = 60;
	// 		}
	// 	}
	// 	return $taxaParentIndex;
	// }

	// private function buildHierarchyArr($taxaParentIndex){
	// 	$hierarchyArr = Array();
	// 	//Build Hierarchy Array: grab leaf nodes and attach to parent until none are left
	// 	$leafTaxa = Array();
	// 	while($leafTaxa = array_diff(array_keys($taxaParentIndex),$taxaParentIndex)){
	// 		foreach($leafTaxa as $value){
	// 			if(array_key_exists($value,$hierarchyArr)){
	// 				$hierarchyArr[$taxaParentIndex[$value]][$value] = $hierarchyArr[$value];
	// 				unset($hierarchyArr[$value]);
	// 			}
	// 			else{
	// 				$hierarchyArr[$taxaParentIndex[$value]][$value] = $value;
	// 			}
	// 			unset($taxaParentIndex[$value]);
	// 		}
	// 	}
	// 	if(!$hierarchyArr && $this->taxaArr){
	// 		foreach($this->taxaArr as $t => $v){
	// 			$hierarchyArr[$t] = '';
	// 		}
	// 	}
	// 	return $hierarchyArr;
	// }

	// private function adjustSubgenericNames(){
	// 	$subGenera = array();
	// 	//Adjust scientific name display for subgenera
	// 	foreach($this->taxaArr as $tid => $tArr){
	// 		if(!empty($tArr['rankid']) && $tArr['rankid'] == 190){
	// 			$subGenera[] = $tid;
	// 			if(!strpos($tArr['sciname'], '(')){
	// 				$genusDisplay = $this->taxaArr[$tArr['parenttid']]['sciname'];
	// 				$subGenusDisplay = $genusDisplay . ' (' . $tArr['sciname'] . ')';
	// 				$this->taxaArr[$tid]['sciname'] = $subGenusDisplay;
	// 			}
	// 		}
	// 	}
	// 	//Add subgenera designation to children of subgenera (i.e. species)
	// 	if($this->displaySubGenera && $subGenera){
	// 		foreach($this->taxaArr as $tid => $tArr){
	// 			if(in_array($tArr['parenttid'], $subGenera)){
	// 				$sn = $this->taxaArr[$tid]['sciname'];
	// 				$pos = strpos($sn, ' ', 2);
	// 				if($pos) $this->taxaArr[$tid]['sciname'] = $this->taxaArr[$tArr['parenttid']]['sciname'].' '.trim(substr($sn, $pos));
	// 			}
	// 		}
	// 	}

	// }

	// private function echoTaxonArray($node){
	// 	if($node){
	// 		uksort($node, array($this, 'cmp'));
	// 		foreach($node as $key => $value){
	// 			$sciName = '';
	// 			$taxonRankId = 0;
	// 			if(array_key_exists($key, $this->taxaArr)){
	// 				$sciName = $this->taxaArr[$key]['sciname'];
	// 				$sciName = str_replace($this->targetStr, '<b>'.htmlspecialchars($this->targetStr).'</b>', $sciName);
	// 				$taxonRankId = $this->taxaArr[$key]['rankid'];
	// 				if($this->taxaArr[$key]['rankid'] >= 180){
	// 					$sciName = ' <i>' . $sciName . '</i> ';
	// 				}
	// 				if($this->displayAuthor) $sciName .= ' '.$this->taxaArr[$key]['author'];
	// 			}
	// 			elseif(!$key){
	// 				$sciName = '&nbsp;';
	// 			}
	// 			else{
	// 				$sciName = '<br/>Problematic Rooting (' . $key . ')';
	// 			}
	// 			$indent = $taxonRankId;
	// 			if($indent > 230) $indent -= 10;
	// 			echo '<div>' . str_repeat('&nbsp;', intval($indent/5));
	// 			if($taxonRankId > 139) echo '<a href="../index.php?taxon=' . $key . '" target="_blank">' . $sciName. '</a>';
	// 			else echo $sciName;
	// 			if($this->isEditor){
	// 				echo ' <a href="taxoneditor.php?tid=' . $key . '" target="_blank"><img class="icon-image" src="../../images/edit.png" alt="Edit"></a>';
	// 			}
	// 			if($this->limitToOccurrences){
	// 				echo ' <a href="../../collections/list.php?taxa=' . $key . '" target="_blank"><img class="icon-image" src="../../images/list.png" alt="Edit"></a>';
	// 			}
	// 			if(!$this->displayFullTree){
	// 				if(($this->targetRankId < 140 && $taxonRankId == 140) || !$this->targetStr && $taxonRankId == 10){
	// 					echo ' <a href="taxonomydisplay.php?target=' . htmlspecialchars($sciName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
	// 					echo '<img class="icon-image" src="../../images/tochild.png" alt="Go to child" >';
	// 					echo '</a>';
	// 				}
	// 			}
	// 			echo '</div>';
	// 			if(array_key_exists($key,$this->taxaArr) && array_key_exists('synonyms', $this->taxaArr[$key])){
	// 				$synNameArr = $this->taxaArr[$key]['synonyms'];
	// 				asort($synNameArr);
	// 				foreach($synNameArr as $synTid => $synName){
	// 					$synName = str_replace($this->targetStr, '<b>' . htmlspecialchars($this->targetStr) . '</b>', $synName);
	// 					echo '<div>' . str_repeat('&nbsp;', $indent/5) . str_repeat('&nbsp;', 7);
	// 					echo '[';
	// 					if($taxonRankId > 139) echo '<a href="../index.php?taxon=' . $synTid . '" target="_blank">';
	// 					echo $synName;
	// 					if($taxonRankId > 139) echo '</a>';
	// 					if($this->isEditor) echo ' <a href="taxoneditor.php?tid=' . $synTid . '" target="_blank"><img class="icon-image" src="../../images/edit.png" ></a>';
	// 					echo ']';
	// 					echo '</div>';
	// 				}
	// 			}
	// 			if(is_array($value)){
	// 				$this->echoTaxonArray($value);
	// 			}
	// 			$this->nodeCnt++;
	// 			if($this->nodeCnt%500 == 0){
	// 				ob_flush();
	// 				flush();
	// 			}
	// 		}
	// 	}
	// }

	//Dynamic tree display fucntions
	// public function getDynamicTreePath(){
	// 	$retArr = Array();
	// 	$this->primeTaxaEnumTree();
	// 	$tid = 0;

	// 	//Get target taxa (we don't want children and parents of non-accepted taxa, so we'll get those later)
	// 	$acceptedTid = '';
	// 	if($this->targetStr){
	// 		$sql1 = 'SELECT DISTINCT t.tid, ts.tidaccepted '.
	// 			'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
	// 			'LEFT JOIN taxstatus ts1 ON t.tid = ts1.tidaccepted '.
	// 			'LEFT JOIN taxa t1 ON ts1.tid = t1.tid '.
	// 			'WHERE (ts.taxauthid = '.$this->taxAuthId.' OR ts.taxauthid IS NULL) AND (ts1.taxauthid = '.$this->taxAuthId.' OR ts1.taxauthid IS NULL) ';
	// 		if($this->targetTid) $sql1 .= 'AND (t.tid IN('.$this->targetTid.') OR (ts1.tid = '.$this->targetTid.'))';
	// 		else{
	// 			$sql1 .= 'AND ((t.sciname = "'.$this->cleanInStr($this->targetStr).'") OR (t1.sciname = "'.$this->cleanInStr($this->targetStr).'") '.
	// 				'OR (CONCAT(t.sciname," ",t.author) = "'.$this->cleanInStr($this->targetStr).'") OR (CONCAT(t1.sciname," ",t1.author) = "'.$this->cleanInStr($this->targetStr).'")) ';
	// 		}
	// 		//echo "<div>".$sql1."</div>";
	// 		$rs1 = $this->conn->query($sql1);
	// 		while($row1 = $rs1->fetch_object()){
	// 			if($rs1->num_rows == 1){
	// 				$tid = $row1->tid;
	// 			}
	// 			elseif($row1->tid != $row1->tidaccepted){
	// 				$tid = $row1->tid;
	// 				$acceptedTid = $row1->tidaccepted;
	// 			}
	// 		}
	// 		$rs1->free();
	// 	}
	// 	//Set all parents
	// 	$sql2 = '';
	// 	if($tid){
	// 		$sql2 = 'SELECT t.rankid, ts.tidaccepted, ts.parenttid '.
	// 			'FROM taxaenumtree e INNER JOIN taxa t ON e.parenttid = t.tid '.
	// 			'INNER JOIN taxstatus ts ON e.parenttid = ts.tid '.
	// 			'WHERE e.tid = '.($acceptedTid?$acceptedTid:$tid).' AND e.taxauthid = '.$this->taxAuthId.' AND ts.taxauthid = '.$this->taxAuthId;
	// 	}
	// 	else{
	// 		$sql2 = 'SELECT t2.rankid, ts.tidaccepted, ts.parenttid '.
	// 			'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
	// 			'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
	// 			'INNER JOIN taxstatus ts ON e.parenttid = ts.tid '.
	// 			'WHERE t.rankid = 10 AND e.taxauthid = '.$this->taxAuthId.' AND ts.taxauthid = '.$this->taxAuthId;
	// 	}
	// 	$baseTid = 0;
	// 	$lowestRank = 400;
	// 	$parArr = array();
	// 	$rs2 = $this->conn->query($sql2);
	// 	while($row2 = $rs2->fetch_object()){
	// 		if($row2->rankid && $row2->rankid < $lowestRank){
	// 			$baseTid = $row2->tidaccepted;
	// 			$lowestRank = $row2->rankid;
	// 		}
	// 		if($row2->parenttid != $row2->tidaccepted) $parArr[$row2->parenttid] = $row2->tidaccepted;
	// 	}
	// 	$rs2->free();

	// 	$retArr[0] = 'root';
	// 	$retArr[1] = $baseTid;
	// 	$i = 2;
	// 	while(isset($parArr[$baseTid])){
	// 		$baseTid = $parArr[$baseTid];
	// 		$retArr[$i] = $baseTid;
	// 		$i++;
	// 	}
	// 	if($acceptedTid){
	// 		$retArr[$i] = $acceptedTid;
	// 		$i++;
	// 	}
	// 	if($tid) $retArr[$i] = $tid;
	// 	return $retArr;
	// }

	//Export taxonomic tree
	// public function exportCsv(){
	// 	$fullCount = 0;
	// 	$fileName = 'taxonomyExport_'.date('Y-m-d').'.csv';
	// 	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	// 	header ('Content-Type: text/csv');
	// 	header ('Content-Disposition: attachment; filename="'.$fileName.'"');
	// 	$out = fopen('php://output', 'w');
	// 	//Add BOM to fix UTF-8 in Excel
	// 	fputs($out, ( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	// 	$headerArr = array('taxonID', 'scientificName', 'scientificNameAuthorship', 'unitName1', 'unitName2', 'unitInd3', 'unitName3', 'rankID', 'kingdomName', 'family',
	// 		'parentTaxonID', 'parentScientificName', 'acceptance', 'acceptedTaxonID', 'acceptedScientificName');
	// 	fputcsv($out, $headerArr);
	// 	$this->setTaxa();
	// 	if($this->taxaArr){
	// 		$targetTids = implode(',', array_keys($this->taxaArr));
	// 		foreach($this->taxaArr as $tArr){
	// 			if(!empty($tArr['synonyms'])) $targetTids .= ',' . implode(',', array_keys($tArr['synonyms']));
	// 		}
	// 		$sql = 'SELECT DISTINCT t.tid, t.sciname AS scientificName, t.author AS scientificNameAuthorship,
	// 			CONCAT_WS(" ", t.unitInd1, t.unitName1) AS unitName1, CONCAT_WS(" ", t.unitInd2, t.unitName2) AS unitName2, t.unitInd3, t.unitName3,
	// 			t.rankid, t.kingdomName, ts.family, p.tid AS parentTid, p.sciname AS parentScientificName, IF(ts.tid = ts.tidaccepted, 1, 0) as acceptance,
	// 			a.tid AS acceptedTid, a.sciname AS acceptedScientificName
	// 			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
	// 			INNER JOIN taxa p ON ts.parenttid = p.tid
	// 			LEFT JOIN taxa a ON ts.tidaccepted = a.tid
	// 			WHERE ts.taxauthid = 1 AND t.tid IN(' . $targetTids . ')
	// 			ORDER BY t.rankid, t.sciname';
	// 		if($rs = $this->conn->query($sql)){
	// 			while($r = $rs->fetch_assoc()){
	// 				fputcsv($out, $r);
	// 			}
	// 		}
	// 	}
	// 	fclose($out);
	// 	return $fullCount;
	// }

	//Setters and getters
	// public function setTargetStr($target){
	// 	if(is_numeric($target)){
	// 		$this->targetTid = filter_var($target, FILTER_SANITIZE_NUMBER_INT);
	// 		$sql = 'SELECT sciname FROM taxa WHERE tid = '.$this->targetTid;
	// 		$rs = $this->conn->query($sql);
	// 		while($r = $rs->fetch_object()){
	// 			$this->targetStr = $r->sciname;
	// 		}
	// 		$rs->free();
	// 	}
	// 	elseif($target) $this->targetStr = ucfirst(trim($target));
	// }

	// public function setTaxAuthId($id){
	// 	if($id && is_numeric($id)){
	// 		$this->taxAuthId = $id;
	// 	}
	// 	else{
	// 		$sql = 'SELECT taxauthid FROM taxauthority WHERE isprimary = 1 ORDER BY taxauthid';
	// 		$rs = $this->conn->query($sql);
	// 		if($r = $rs->fetch_object()){
	// 			$this->taxAuthId = $r->taxauthid;
	// 		}
	// 		$rs->free();
	// 	}
	// 	if(!$this->taxAuthId) $this->taxAuthId = 1;
	// }

	// public function setTaxonomyMeta(){
	// 	if($this->taxAuthId){
	// 		$sql = 'SELECT name, description, editors, contact, email, url, notes, isprimary FROM taxauthority WHERE taxauthid = '.$this->taxAuthId;
	// 		$rs = $this->conn->query($sql);
	// 		if($r = $rs->fetch_object()){
	// 			$this->taxonomyMeta['name'] = $r->name;
	// 			if($r->description) $this->taxonomyMeta['description'] = $r->description;
	// 			if($r->editors) $this->taxonomyMeta['editors'] = $r->editors;
	// 			if($r->contact) $this->taxonomyMeta['contact'] = $r->contact;
	// 			if($r->email) $this->taxonomyMeta['email'] = $r->email;
	// 			if($r->url) $this->taxonomyMeta['url'] = $r->url;
	// 			if($r->notes) $this->taxonomyMeta['notes'] = $r->notes;
	// 			if($r->isprimary) $this->taxonomyMeta['isprimary'] = $r->isprimary;
	// 		}
	// 		$rs->free();
	// 	}
	// }

	// public function setDisplayAuthor($display){
	// 	if($display) $this->displayAuthor = true;
	// }

	// public function setDisplayFullTree($displayTree){
	// 	if($displayTree) $this->displayFullTree = true;
	// }

	// public function setDisplaySubGenera($displaySubg){
	// 	if($displaySubg) $this->displaySubGenera = true;
	// }

	// public function setLimitToOccurrences($limitToOccurrences){
	// 	if($limitToOccurrences) $this->limitToOccurrences = true;
	// }

	// public function getTargetStr(){
	// 	return $this->cleanOutStr($this->targetStr);
	// }

	// public function getTaxonomyMeta(){
	// 	if(!$this->taxonomyMeta) $this->setTaxonomyMeta();
	// 	return $this->taxonomyMeta;
	// }

	// public function setEditorMode($bool){
	// 	$this->isEditor = $bool;
	// }

	// public function setMatchOnWholeWords($bool){
	// 	$this->matchOnWholeWords = $bool;
	// }

	//Misc functions
	// private function primeTaxaEnumTree(){
	// 	//Temporary code: check to make sure taxaenumtree is populated
	// 	//This code can be removed somewhere down the line
	// 	$indexCnt = 0;
	//     $sql = 'SELECT tid FROM taxaenumtree LIMIT 1';
	// 	$rs = $this->conn->query($sql);
	// 	$indexCnt = $rs->num_rows;
	// 	$rs->free();
	// 	if(!$indexCnt){
	// 		echo '<div style="color:red;margin:30px;">';
	// 		echo 'NOTICE: Building new taxonomic hierarchy table (taxaenumtree).<br/>This may take a few minutes, but only needs to be done once.<br/>Do not terminate this process early.';
	// 		echo '</div>';
	// 		ob_flush();
	// 		flush();
	// 		$status = TaxonomyUtilities::buildHierarchyEnumTree(null, $this->taxAuthId);
	// 		if($status === true) echo '<div style="color:green;margin:30px;">Done! Taxonomic hierarchy index has been created</div>';
	// 		else echo $status;
	// 		ob_flush();
	// 		flush();
	// 	}
	// }

	// private function cmp($a, $b){
	// 	$sciNameA = (array_key_exists($a,$this->taxaArr)?$this->taxaArr[$a]["sciname"]:"unknown (".$a.")");
	// 	$sciNameB = (array_key_exists($b,$this->taxaArr)?$this->taxaArr[$b]["sciname"]:"unknown (".$b.")");
	// 	return strcmp($sciNameA, $sciNameB);
	// }
}
?>
