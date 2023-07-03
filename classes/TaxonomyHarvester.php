<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/EOLUtilities.php');

class TaxonomyHarvester extends Manager{

	private $taxonomicResources = array();
	private $taxAuthId = 1;
	private $defaultAuthor;
	private $defaultFamily = '';
	private $defaultFamilyTid = 0;
	private $kingdomName;
	private $kingdomTid;
	private $rankIdArr = array();
	private $fullyResolved;
	private $taxaFieldArr = array();
	private $langArr = false;
	private $transactionCount = 0;

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function processSciname($term){
		$newTid = 0;
		$term = trim($term);
		if($term){
			$this->fullyResolved = true;
			$taxonArr = $this->parseCleanCheck($term);
			if(isset($taxonArr['tid']) && $taxonArr['tid']) return $taxonArr['tid'];
			if(!$this->taxonomicResources){
				$this->logOrEcho('External taxonomic resource checks not activated ',1);
				return false;
			}
			foreach($this->taxonomicResources as $authCode => $apiKey){
				$newTid = $this->addSciname($taxonArr, $authCode);
				if($newTid) break;
			}
		}
		return $newTid;
	}

	private function parseCleanCheck($term){
		$tid = 0;
		$taxonArr = array('sciname' => $term);
		$tid = $this->getTid($taxonArr);
		if($tid) $taxonArr['tid'] = $tid;
		else{
			$this->buildTaxonArr($taxonArr);
			if(isset($taxonArr['rankid']) && $taxonArr['rankid'] > 220 && $taxonArr['unitname2'] == $taxonArr['unitname3']){
				//Taxon is an infraspecific tautonym.
				$sql = 'SELECT tid FROM taxa WHERE (unitname1 = "'.$taxonArr['unitname1'].'") AND (unitname2 = "'.$taxonArr['unitname2'].'") AND (unitname3 IS NOT NULL) ';
				if(isset($taxonArr['unitind3']) && $taxonArr['unitind3']) $sql .= 'AND (unitind3 = "'.$taxonArr['unitind3'].'") ';
				$rs = $this->conn->query($sql);
				if($rs->num_rows){
					//Automatically add if another infraspecific taxon already exists for that rank
					if($parentArr = $this->getParentArr($taxonArr)){
						if($parentTid = $this->getTid($parentArr)){
							$taxonArr['parent']['tid'] = $parentTid;
							$parentTidAccepted = $this->getTidAccepted($parentTid);
							if($parentTidAccepted == $parentTid){
								$tid = $this->loadNewTaxon($taxonArr);
								if($tid) $taxonArr['tid'] = $tid;
							}
							else{
								$tid = $this->loadNewTaxon($taxonArr,$parentTidAccepted);
								if($tid) $taxonArr['tid'] = $tid;
							}
						}
					}
				}
				$rs->free();
			}
		}
		return $taxonArr;
	}

	private function addSciname($taxonArr, $resourceKey){
		if(!$taxonArr || !isset($taxonArr['sciname'])) return false;
		$newTid = 0;
		if($resourceKey== 'col'){
			$this->logOrEcho('Checking <b>Catalog of Life</b>...',1);
			$newTid= $this->addColTaxon($taxonArr);
		}
		elseif($resourceKey== 'worms'){
			$this->logOrEcho('Checking <b>WoRMS</b>...',1);
			$newTid= $this->addWormsTaxon($taxonArr['sciname']);
		}
		elseif($resourceKey== 'tropicos'){
			$this->logOrEcho('Checking <b>TROPICOS</b>...',1);
			$newTid= $this->addTropicosTaxon($taxonArr);
		}
		elseif($resourceKey== 'bryonames'){
			$this->logOrEcho('Checking <b>Bryophyte Nomenclator (https://bryonames.org)</b>...',1);
			$newTid= $this->addBryoNamesTaxon($taxonArr);
		}
		elseif($resourceKey== 'fdex'){
			$this->logOrEcho('Checking <b>fdex</b>...',1);
			$newTid= $this->addFdexTaxon($taxonArr);
		}
		elseif($resourceKey== 'eol'){
			$this->logOrEcho('Checking <b>EOL</b>...',1);
			$newTid= $this->addEolTaxon($taxonArr);
		}
		if(!$newTid) return false;
		return $newTid;
	}

	/*
	 * INPUT: scientific name
	 *   Example: 'Pinus ponderosa var. arizonica'
	 * OUTPUT: tid taxon loaded into thesaurus
	 *   Example: array('id' => '34554'
	 *   				'sciname' => 'Pinus arizonica',
	 *   				'scientificName' => 'Pinus arizonica (Engelm.) Shaw',
	 *					'unitind1' => '', 'unitname1' => 'Pinus', 'unitind2' => '', 'unitname2' => 'arizonica', 'unitind3'=>'', 'unitname3'=>'',
	 *					'author' => '(Engelm.) Shaw',
	 *					'rankid' => '220',
	 *					'taxonRank' => 'Species',
	 *					'source' => '',
	 *					'sourceURL' => '',
	 *  				'verns' => array(array('vernacularName'=>'Arizona Ponderosa Pine','language'=>'en'), array(etc...)),
	 *  				'syns' => array(array('sciname'=>'Pinus ponderosa var. arizonica','acceptanceReason'=>'synonym'...), array(etc...)),
	 *  				'parent' => array(
	 *  					'id' => '43463',
	 *  					'tid' => 12345,
	 *  					'sciname' => 'Pinus',
	 *  					'taxonRank' => 'Genus',
	 *  					'sourceURL' => 'http://eol.org/pages/1905/hierarchy_entries/43463/overview',
	 *  					'parentID' => array( etc...)
	 *  				)
	 *			   )
	 */
	private function addColTaxon($taxonArr){
		$tid = 0;
		if(preg_match('/^[A-Z){1}[a-z]+\s{1}\(([A-Z){1}[a-z]+)\)$/', $taxonArr['sciname'], $m)){
			//Remove Genus from subgeneric name
			$taxonArr['sciname'] = $m[1];
		}
		$sciName = $taxonArr['sciname'];
		if($sciName){
			//$url = 'https://api.catalogueoflife.org/nameusage/search?content=SCIENTIFIC_NAME&q='.str_replace(' ','%20',$sciName).'&offset=0&limit=10';
			$url = 'https://webservice.catalogueoflife.org/col/webservice?response=full&format=json&name='.str_replace(' ','%20',$sciName);
			//echo $url.'<br/>';
			$retArr = $this->getContentString($url);
			$content = $retArr['str'];
			$resultArr = json_decode($content,true);
			$numResults = $resultArr['number_of_results_returned'];
			if($numResults){
				//Evaluate and rank each result to determine which is the best suited target
				$adjustedName = $sciName;
				if(isset($taxonArr['rankid']) && $taxonArr['rankid'] > 220) $adjustedName = trim($taxonArr['unitname1'].' '.$taxonArr['unitname2'].' '.$taxonArr['unitname3']);
				$targetKey = 0;
				$submitArr = array();
				$rankingArr = array();
				foreach($resultArr['result'] as $k => $tArr){
					$rankingArr[$k] = 0;
					if($sciName != $tArr['name'] && $adjustedName != $tArr['name']){
						unset($rankingArr[$k]);
						continue;
					}
					$this->setColClassification($tArr,$taxonArr);
					if(isset($tArr['formattedClassification'])) $resultArr['result'][$k]['formattedClassification'] = $tArr['formattedClassification'];
					$taxonKingdom = $this->getColParent($tArr, 'Kingdom');
					if($this->kingdomName && $taxonKingdom && $this->kingdomName != $taxonKingdom){
						//Skip if kingdom doesn't match target kingdom
						unset($rankingArr[$k]);
						$msg = $sciName;
						$id = '';
						if(isset($resultArr['result'][$k]['id'])){
							$id = $resultArr['result'][$k]['id'];
							$msg = '<a href="https://www.catalogueoflife.org/data/taxon/'.$id.'" target="_blank">#'.$id.' - '.$sciName.'</a>';
						}
						$msg = 'Target taxon ('.$msg.') skipped due to not matching targeted kingdom: '.$this->kingdomName.' (!= '.$taxonKingdom.')';
						$this->logOrEcho($msg,2);
						continue;
					}
					if($taxonArr['unitind3'] && isset($tArr['infraspeciesMarker']) && $taxonArr['unitind3'] != $tArr['infraspeciesMarker']){
						//Skip because it's not the correct infraspecific rank
						unset($rankingArr[$k]);
						continue;
					}
					if(isset($taxonArr['taxonRank']) && isset($tArr['rank']) && $taxonArr['taxonRank'] == $tArr['rank']) $rankingArr[$k] += 2;
					if($this->defaultFamily && $this->defaultFamily == $this->getColParent($tArr, 'Family')) $rankingArr[$k] += 2;
					if($tArr['name_status'] == 'accepted name')  $rankingArr[$k] += 2;
					if(isset($tArr['author']) && $tArr['author']){
						if(stripos($tArr['author'],'nom. illeg.') !== false){
							//Skip if name is an illegal homonym
							unset($rankingArr[$k]);
							continue;
						}
						//Gets 2 points if author is the same, 1 point if 80% similar
						if($this->defaultAuthor){
							$author1 = str_replace(array(' ','.'), '', $this->defaultAuthor);
							$author2 = str_replace(array(' ','.'), '', $tArr['author']);
							$percent = 0;
							similar_text($author1, $author2, $percent);
							if($author1 == $author2) $rankingArr[$k] += 2;
							elseif($percent > 80) $rankingArr[$k] += 1;
						}
					}
					$submitArr[$k] = $resultArr['result'][$k];
				}
				if($rankingArr){
					asort($rankingArr);
					end($rankingArr);
					$targetKey = key($rankingArr);
					if(isset($rankingArr[0]) && $rankingArr[$targetKey] == $rankingArr[0]) $targetKey = 0;
				}
				//If taxon has an accepted taxon that is the same taxon with a subgeneric linkage, use the taxon object to be submitted
				if(isset($submitArr[$targetKey]['accepted_name'])){
					if(preg_match('/^([A-Z]{1}[a-z]+)\s{1}\(\D+\)\s{1}([a-z .]+)/', $submitArr[$targetKey]['accepted_name']['name'], $m)){
						$acceptedBaseName = $m[1].' '.$m[2];
						if($submitArr[$targetKey]['name'] == $acceptedBaseName){
							$submitArr[0] = $submitArr[$targetKey]['accepted_name'];
							$targetKey = 0;
						}
					}
				}
				//Process selected result
				$this->logOrEcho('<i>'.$sciName.'</i> found within Catalog of Life',2);
				if(array_key_exists($targetKey, $submitArr) && $submitArr[$targetKey]){
					$tid = $this->addColTaxonByResult($submitArr[$targetKey]);
				}
				else $this->logOrEcho('Targeted taxon return does not exist',2);
			}
			else $this->logOrEcho('Taxon not found',2);
		}
		else $this->logOrEcho('ERROR harvesting COL name: null input name',1);
		return $tid;
	}

	private function addColTaxonByResult($baseArr){
		$taxonArr = array();
		if($baseArr){
			$taxonArr = $this->translateColNode($baseArr);
			$this->setColClassification($baseArr, $taxonArr);
			if($taxonArr['rankid'] == 190){
				if(!strpos($taxonArr['sciname'], ' (') && isset($baseArr['formattedClassification'][180]['sciname'])){
					$reformattedName = $baseArr['formattedClassification'][180]['sciname'].' ('.$taxonArr['sciname'].')';
					$taxonArr['sciname'] = $reformattedName;
					$taxonArr['unitname1'] = $reformattedName;
				}
			}
			if(isset($baseArr['formattedClassification'][140]['sciname'])) $taxonArr['family'] = $baseArr['formattedClassification'][140]['sciname'];
			$tidAccepted = 0;
			if($baseArr['name_status'] == 'synonym' && isset($baseArr['accepted_name'])){
				$tidAccepted = $this->addColTaxonById($baseArr['accepted_name']);
			}
			//Get parent
			if(isset($baseArr['parent']['tid'])) $taxonArr['parent']['tid'] = $baseArr['parent']['tid'];
			elseif(isset($taxonArr['rankid']) && $taxonArr['rankid'] == 10) $taxonArr['parent']['tid'] = 'self';
			else{
				$directParentTid = 0;
				if(isset($baseArr['formattedClassification'])){
					foreach($baseArr['formattedClassification'] as $parRank => $parArr){
						if($parRank >= $taxonArr['rankid']) continue;
						if(isset($parArr['sciname'])){
							$parentTid = $this->getTid($parArr);
							if(!$parentTid){
								$parClass = $baseArr['formattedClassification'];
								foreach($parClass as $k => $a){
									if($k >= $parArr['rankid']) unset($parClass[$k]);
								}
								$parArr['formattedClassification'] = $parClass;
								if(isset($parArr['id'])) $parentTid = $this->addColTaxonById($parArr);
								else $parentTid = $this->addColTaxon($parArr);
							}
							if($parentTid){
								$directParentTid = $parentTid;
								break;
							}
						}
					}
				}
				else{
					$parentArr = $this->getParentArr($taxonArr);
					if($parentArr){
						$directParentTid = $this->addColTaxon(array('sciname'=>$parentArr['sciname']));
						if(!$directParentTid){
							//Bad return from COL, thus lets just add as accepted for now
							$parentArr['family'] = $this->getColParent($baseArr,'Family');
							$this->buildTaxonArr($parentArr);
							$directParentTid = $this->loadNewTaxon($parentArr);
						}
					}
				}
				if($directParentTid) $taxonArr['parent']['tid'] = $directParentTid;
			}
		}
		else $this->logOrEcho('ERROR harvesting COL name: null result',1);
		if(isset($taxonArr['source']) && $taxonArr['source']) $taxonArr['source'] = $taxonArr['source'].' (added via CoL API)';
		else $taxonArr['source'] = 'Catalog of Life (added via API)';
		return $this->loadNewTaxon($taxonArr, $tidAccepted);
	}

	private function addColTaxonById($taxonArr){
		$tid = 0;
		if($taxonArr['id']){
			$url = 'https://webservice.catalogueoflife.org/col/webservice?response=full&format=json&id='.$taxonArr['id'];
			$retArr = $this->getContentString($url);
			$content = $retArr['str'];
			$resultArr = json_decode($content,true);
			if(isset($resultArr['result'][0])){
				$baseArr = $resultArr['result'][0];
				if(isset($taxonArr['datasetKey'])) $baseArr['datasetKey'] = $taxonArr['datasetKey'];
				if(isset($taxonArr['formattedClassification'])) $baseArr['formattedClassification'] = $taxonArr['formattedClassification'];
				$tid = $this->addColTaxonByResult($baseArr);
			}
			else{
				$this->logOrEcho('Targeted taxon return does not exist(2)',2);
			}
		}
		else{
			$this->logOrEcho('ERROR harvesting COL name: null input identifier',1);
		}
		return $tid;
	}

	private function translateColNode($nodeArr){
		//Translates COL output from v1 (webservices) or v2 (api) to a Symbiota taxon object
		$taxonArr = array();
		if(isset($nodeArr['id'])) $taxonArr['id'] = $nodeArr['id'];
		if(isset($nodeArr['name'])) $taxonArr['sciname'] = $nodeArr['name'];
		elseif(isset($nodeArr['name']['scientificName'])) $taxonArr['sciname'] = $nodeArr['name']['scientificName'];
		if(isset($nodeArr['rank'])) $taxonArr['taxonRank'] = $nodeArr['rank'];
		elseif(isset($nodeArr['name']['rank'])) $taxonArr['taxonRank'] = $nodeArr['name']['rank'];
		if(isset($nodeArr['genus'])) $taxonArr['unitname1'] = $nodeArr['genus'];
		elseif(isset($nodeArr['name']['genus'])) $taxonArr['unitname1'] = $nodeArr['name']['genus'];
		elseif(isset($nodeArr['name']['uninomial'])) $taxonArr['unitname1'] = $nodeArr['name']['uninomial'];
		if(isset($nodeArr['species'])) $taxonArr['unitname2'] = $nodeArr['species'];
		elseif(isset($nodeArr['name']['specificEpithet'])) $taxonArr['unitname2'] = $nodeArr['name']['specificEpithet'];
		if(isset($nodeArr['infraspecies'])) $taxonArr['unitname3'] = $nodeArr['infraspecies'];
		elseif(isset($nodeArr['name']['infraSpecificEpithet'])) $taxonArr['unitname3'] = $nodeArr['name']['infraSpecificEpithet'];
		if(isset($nodeArr['infraspeciesMarker'])){
			$taxonArr['unitind3'] = $nodeArr['infraspeciesMarker'];
			$taxonArr['sciname'] = trim($taxonArr['unitname1'].' '.$taxonArr['unitname2'].($taxonArr['unitind3']?' '.$taxonArr['unitind3']:'').' '.$taxonArr['unitname3']);
		}
		if(isset($nodeArr['author'])) $taxonArr['author'] = $nodeArr['author'];
		elseif(isset($nodeArr['name']['authorship'])) $taxonArr['author'] = $nodeArr['name']['authorship'];
		if(isset($nodeArr['source_database'])) $taxonArr['source'] = $nodeArr['source_database'];
		elseif(isset($nodeArr['datasetKey'])) $taxonArr['source'] = '<a href="https://www.catalogueoflife.org/data/dataset/'.$nodeArr['datasetKey'].'" target="_blank">https://www.catalogueoflife.org/data/dataset/'.$nodeArr['datasetKey'].'</a>';
		if(isset($nodeArr['online_resource'])) $taxonArr['sourceURL'] = $nodeArr['online_resource'];
		elseif(isset($nodeArr['link'])) $taxonArr['sourceURL'] = $nodeArr['link'];
		if(isset($nodeArr['url'])) $taxonArr['resourceURL'] = $nodeArr['url'];
		$rankID = $this->getRankIdByTaxonArr($taxonArr);
		if($rankID){
			$taxonArr['rankid'] = $rankID;
			if($rankID > 190){
				if(preg_match('/^([A-Z]{1}[a-z]+)\s{1}\(\D+\)\s{1}(.*)/', $taxonArr['sciname'], $m)){
					$taxonArr['sciname'] = $m[1].' '.$m[2];
				}
			}
			if(!isset($taxonArr['unitname1'])) $taxonArr = array_merge(TaxonomyUtilities::parseScientificName($taxonArr['sciname'],$this->conn,$taxonArr['rankid'],$this->kingdomName),$taxonArr);
		}
		//$this->buildTaxonArr($taxonArr);
		return $taxonArr;
	}

	private function setColClassification(&$tArr, $subjectTaxonArr){
		if(isset($tArr['formattedClassification'])) return true;
		$classificationArr = array();
		$activeClassArr = array();
		if(isset($tArr['classification'])) $activeClassArr = $tArr['classification'];
		elseif(isset($tArr['accepted_name']['classification'])) $activeClassArr = $tArr['accepted_name']['classification'];
		if(!$activeClassArr){
			$name = '';
			if(isset($tArr['name'])) $name = $tArr['name'];
			if(!$name && isset($tArr['usage']['name']['scientificName'])) $name = $tArr['usage']['name']['scientificName'];
			if($name){
				$url = 'https://api.catalogueoflife.org/nameusage/search?content=SCIENTIFIC_NAME&q='.str_replace(' ','%20',$name).'&offset=0&limit=100&type=EXACT';
				//echo 'COL ULR: '.$url.'<br/>';
				$retArr = $this->getContentString($url);
				$content = $retArr['str'];
				$resultArr = json_decode($content,true);
				if(isset($resultArr['total']) && $resultArr['total']){
					foreach($resultArr['result'] as $k => $rArr){
						if(isset($rArr['id']) && $rArr['id'] == $tArr['id'] && isset($rArr['classification'])){
							$activeClassArr = $rArr['classification'];
							if(!isset($tArr['datasetKey']) || (isset($rArr['usage']['datasetKey']) && $rArr['usage']['datasetKey'] == $tArr['datasetKey'] )) break;
						}
					}
				}
			}
		}
		if($activeClassArr){
			foreach($activeClassArr as $classArr){
				$taxonNode = $this->translateColNode($classArr);
				if(isset($taxonNode['rankid']) && isset($subjectTaxonArr['rankid'])){
					if($taxonNode['rankid'] < $subjectTaxonArr['rankid']){
						if($taxonNode['rankid'] >= 180 && $taxonNode['unitname1'] != $subjectTaxonArr['unitname1']){
							$taxonNode['unitname1'] = $subjectTaxonArr['unitname1'];
							if($taxonNode['rankid'] == 220) $taxonNode['unitname2'] = $subjectTaxonArr['unitname2'];
							$taxonNode['sciname'] = trim($taxonNode['unitname1'].(isset($taxonNode['unitname2'])?' '.$taxonNode['unitname2']:''));
							unset($taxonNode['author']);
							unset($taxonNode['id']);
						}
					}
				}
				if(isset($taxonNode['rankid']) && $taxonNode['rankid']) $classificationArr[$taxonNode['rankid']] = $taxonNode;
			}
			if(isset($subjectTaxonArr['rankid'])){
				if($subjectTaxonArr['rankid'] > 180 && !isset($classificationArr[180])){
					$classificationArr[180] = Array ( 'unitname1' => $subjectTaxonArr['unitname1'], 'unitname2' => '', 'unitind3' => '', 'unitname3' => '', 'rankid' => 180, 'sciname' => $subjectTaxonArr['unitname1'], 'taxonRank' => 'Genus' );
				}
				if($subjectTaxonArr['rankid'] > 220 && !isset($classificationArr[220])){
					$classificationArr[220] = Array ( 'unitname1' => $subjectTaxonArr['unitname1'], 'unitname2' => $subjectTaxonArr['unitname2'], 'unitind3' => '', 'unitname3' => '', 'rankid' => 220, 'sciname' => $subjectTaxonArr['unitname1'].' '.$subjectTaxonArr['unitname2'], 'taxonRank' => 'Species' );
				}
			}
			krsort($classificationArr);
		}
		if(isset($classificationArr[190]['sciname']) && !strpos($classificationArr[190]['sciname'], ' (')){
			if(isset($classificationArr[180])) $classificationArr[190]['sciname'] = $classificationArr[180]['sciname'].' ('.$classificationArr[190]['sciname'].')';
		}
		if($classificationArr) $tArr['formattedClassification'] = $classificationArr;
	}

	private function getColParent($baseArr, $parentRank){
		$parentRank = strtolower($parentRank);
		//Returns parent name (e.g. family) obtained from accepted taxon
		if(isset($this->rankIdArr[$parentRank])){
			$parRankId = $this->rankIdArr[$parentRank];
			if(isset($baseArr['formattedClassification'][$parRankId]['sciname'])){
				return $baseArr['formattedClassification'][$parRankId]['sciname'];
			}
		}

		$classArr = array();
		if(array_key_exists('classification', $baseArr)) $classArr = $baseArr['classification'];
		elseif(isset($baseArr['accepted_name']['classification'])) $classArr = $baseArr['accepted_name']['classification'];

		foreach($classArr as $classNode){
			if(strtolower($classNode['rank']) == $parentRank) return $classNode['name'];
		}
		return '';
	}

	//CoL node batch add functions
	public function fetchColNode($nodeSciname){
		$retArr = array();
		if(!$nodeSciname){
			$this->logOrEcho('ABORT: target name is null',1);
			return false;
		}
		$url = 'https://api.catalogueoflife.org/nameusage/search?content=SCIENTIFIC_NAME&q='.str_replace(' ','%20',$nodeSciname).'&offset=0&limit=100&type=EXACT';
		//echo '<div>API link: <a href="'.$url.'" target="_blank">'.$url.'</a></div>';
		$contentArr = $this->getContentString($url);
		$content = $contentArr['str'];
		$resultArr = json_decode($content,true);
		if(!$resultArr['empty']){
			$retArr['number_results'] = $resultArr['total'];
			foreach($resultArr['result'] as $k => $tArr){
				$colID = $tArr['id'];
				$name = '';
				if(isset($tArr['usage']['name']['scientificName'])) $name = $tArr['usage']['name']['scientificName'];
				if(!$name){
					$retArr[$colID]['error'] = 'CoL ID-'.$colID.' skipped, unable to return name...';
					continue;
				}
				if($nodeSciname != $name){
					$retArr[$colID]['error'] = $name.' skipped, not an exact match...';
					continue;
				}
				$taxonKingdom = $this->getColParent($tArr, 'Kingdom');
				if($this->kingdomName && $this->kingdomName != $taxonKingdom){
					$retArr[$colID]['error'] = '<a href="https://www.catalogueoflife.org/data/taxon/'.$colID.'" target="_blank">'.$name.'</a> skipped, wrong kingdom: '.$this->kingdomName.' (!= '.$taxonKingdom.')';
					continue;
				}
				$retArr[$colID]['label'] = $tArr['usage']['labelHtml'];
				$retArr[$colID]['datasetKey'] = $tArr['usage']['datasetKey'];
				$retArr[$colID]['status'] = $tArr['usage']['status'];
				if(isset($tArr['usage']['accordingTo'])) $retArr[$colID]['accordingTo'] = $tArr['usage']['accordingTo'];
				if(isset($tArr['usage']['link'])) $retArr[$colID]['link'] = $tArr['usage']['link'];
				if(isset($tArr['usage']['scrutinizer'])) $retArr[$colID]['scrutinizer'] = $tArr['usage']['scrutinizer'];
				$retArr[$colID]['colUrl'] = 'https://www.catalogueoflife.org/data/taxon/'.$colID;

				$testUrl = 'https://webservice.catalogueoflife.org/col/webservice?response=full&format=json&id='.$colID;
				$testContentArr = $this->getContentString($testUrl);
				$testContent = $testContentArr['str'];
				$testArr = json_decode($testContent,true);
				if(isset($testArr['result'][0]['name_status']) && $testArr['result'][0]['name_status'] == 'accepted name'){
					$retArr[$colID]['isPreferred'] = true;
					$retArr[$colID]['webServiceUrl'] = $testUrl;
					$retArr[$colID]['apiUrl'] = 'https://api.catalogueoflife.org/dataset/'.$tArr['usage']['datasetKey'].'/taxon/'.$colID.'/children?offset=0&limit=100';
				}
				else $retArr[$colID]['isPreferred'] = false;
			}
		}
		else{
			$this->logOrEcho('ABORT: no results returned from CoL API',1);
			return false;
		}
		return $retArr;
	}

	public function addColNode($id, $datasetKey, $nodeSciname, $rankLimit){
		if($id && $datasetKey && $nodeSciname){
			$rootTid = $this->getTid(array('sciname' => $nodeSciname));
			if(!$rootTid){
				$this->logOrEcho('Taxon root ('.$nodeSciname.') not found within thesaurus, adding now...',1);
				$rootTid = $this->addColTaxonById(array('id'=>$id,'datasetKey'=>$datasetKey));
			}
			if($rootTid){
				$this->addColChildern($id, $datasetKey, $nodeSciname, $rootTid, $rankLimit);
			}
			else{
				$this->logOrEcho('ABORT: unable to set root node for '.$nodeSciname,1);
				return false;
			}
		}
		else{
			$this->logOrEcho('ABORT: CoL ID, datasetKey, and node name is required',1);
			return false;
		}
	}

	private function addColChildern($id, $datasetKey, $nodeSciname, $parentTid, $rankLimit){
		$url = 'https://api.catalogueoflife.org/dataset/'.$datasetKey.'/taxon/'.$id.'/children?offset=0&limit=100';
		//echo '<div>API link: <a href="'.$url.'" target="_blank">'.$url.'</a></div>';
		$contentArr = $this->getContentString($url);
		if(isset($contentArr['str'])){
			$content = $contentArr['str'];
			$resultArr = json_decode($content,true);
			if(!$resultArr['empty']){
				$this->logOrEcho('Will evaluate '.$resultArr['total'].' children of '.$nodeSciname.': '.$this->getChildrenStr($resultArr['result']),2);
				foreach($resultArr['result'] as $nodeArr){
					$this->transactionCount++;
					if($nodeArr['status']=='accepted'){
						if(isset($nodeArr['extinct']) && $nodeArr['extinct']){
							$this->logOrEcho($nodeArr['labelHtml'].' ('.$nodeArr['status'].') skipped due to extinct status',2);
							continue;
						}
						$taxonArr = $this->translateColNode($nodeArr);
						$tid = $this->getTid($taxonArr);
						if($tid){
							$display = '<a href="'.$GLOBALS['CLIENT_ROOT'].'/taxa/taxonomy/taxoneditor.php?tid='.$tid.'" target="_blank">'.$nodeArr['labelHtml'].'</a>';
							$this->logOrEcho($display.' already in thesaurus',2);
						}
						else{
							$taxonArr['parent']['tid'] = $parentTid;
							$tid = $this->loadNewTaxon($taxonArr);
						}
						if(!$rankLimit || $rankLimit > $taxonArr['rankid']) $this->addColChildern($taxonArr['id'], $datasetKey, $nodeArr['labelHtml'], $tid, $rankLimit);
					}
					else{
						$this->logOrEcho($nodeArr['labelHtml'].' ('.$nodeArr['status'].') skipped due to <b>not accepted</b> status',2);
					}
				}
			}
			else{
				$this->logOrEcho('ABORT: unable to get CoL node data: '.$url,1);
				return false;
			}
		}
		else{
			if($contentArr['code'] == 401) $this->logOrEcho('ABORT: CoL API authorization required:'.$url,1);
			else echo $this->logOrEcho('ABORT: unable to obtain CoL API object (code: '.$contentArr['code'].'):'.$url,1);
			return false;
		}
		return true;
	}

	private function getChildrenStr($resultArr){
		$childArr = array();
		foreach($resultArr as $itemArr){
			$childArr[] = $itemArr['name']['scientificName'];
		}
		return implode(', ',$childArr);
	}

	//WoRMS functions
	private function addWormsTaxon($sciName){
		$tid = 0;
		$url = 'https://marinespecies.org/rest/AphiaIDByName/'.rawurlencode($sciName).'?marine_only=false';
		$retArr = $this->getContentString($url);
		$id = $retArr['str'];
		if(is_numeric($id)){
			$this->logOrEcho('Taxon found within WoRMS',2);
			$tid = $this->addWormsTaxonByID($id);
		}
		else{
			$this->logOrEcho('Taxon not found',2);
		}
		return $tid;
	}

	private function addWormsTaxonByID($id){
		if(!is_numeric($id)){
			$this->logOrEcho('ERROR harvesting from worms: illegal identifier: '.$id,1);
			return 0;
		}
		$taxonArr= Array();
		$acceptedTid = 0;
		$url = 'https://marinespecies.org/rest/AphiaRecordByAphiaID/'.$id;
		if($resultStr = $this->getWormsReturnStr($this->getContentString($url),$url)){
			$taxonArr= $this->getWormsNode(json_decode($resultStr,true));

			$taxonKingdom = $taxonArr['kingdom'];
			if($this->kingdomName && $this->kingdomName != $taxonKingdom){
				//Skip if kingdom doesn't match target kingdom
				$msg = 'Target taxon (<a href="https://marinespecies.org/aphia.php?p=taxdetails&id='.$id.'&marine_only=false" target="_blank">';
				$msg .= $taxonArr['sciname'].'</a>) skipped due to not matching targeted kingdom: '.$this->kingdomName.' (!= '.$taxonKingdom.')';
					$this->logOrEcho($msg,2);
				return false;
			}


			if($taxonArr['acceptance'] == 'unaccepted' && isset($taxonArr['validID'])){
				//Get and set accepted taxon
				$acceptedTid = $this->addWormsTaxonByID($taxonArr['validID']);
			}
			//Get parent
			if($taxonArr['rankid'] == 10){
				$taxonArr['parent']['tid'] = 'self';
			}
			else{
				$url = 'https://marinespecies.org/rest/AphiaClassificationByAphiaID/'.$id;
				if($parentStr = $this->getWormsReturnStr($this->getContentString($url),$url)){
					$parentArr = json_decode($parentStr,true);
					if($parentID = $this->getWormParentID($parentArr, $id)){
						$parentTid = $this->getTid($parentArr);
						if(!$parentTid) $parentTid = $this->addWormsTaxonByID($parentID);
						if($parentTid){
							$taxonArr['parent'] = array('tid' => $parentTid);
						}
					}
				}
			}
		}
		$this->setWormsSource($taxonArr);
		return $this->loadNewTaxon($taxonArr, $acceptedTid);
	}

	private function setWormsSource(&$taxonArr){
		if(!isset($taxonArr['source']) || !$taxonArr['source']){
			if(isset($taxonArr['id'])){
				$url = 'https://marinespecies.org/rest/AphiaSourcesByAphiaID/'.$taxonArr['id'];
				if($sourceStr = $this->getWormsReturnStr($this->getContentString($url),$url)){
					$sourceArr = json_decode($sourceStr,true);
					foreach($sourceArr as $innerArr){
						if(isset($innerArr['reference']) && $innerArr['reference']) $taxonArr['source'] = $innerArr['reference'];
						break;
					}
				}
			}
		}
		if(!isset($taxonArr['source']) || !$taxonArr['source']){
			$taxonArr['source'] = 'WoRMS (added via API)';
		}
	}

	private function getWormsReturnStr($retArr,$url){
		$resultStr = '';
		if($retArr){
			if($retArr['code'] == 200){
				$resultStr = $retArr['str'];
			}
			elseif($retArr['code'] == 204){
				//$this->logOrEcho('Identifier not found within WoRMS: '.$url,2);
			}
			else{
				$this->logOrEcho('ERROR returning WoRMS object (code: '.$retArr['code'].'): '.$url,1);
			}
		}
		return $resultStr;
	}

	private function getWormsNode($nodeArr){
		$taxonArr = array();
		if(isset($nodeArr['AphiaID'])) $taxonArr['id'] = $nodeArr['AphiaID'];
		if(isset($nodeArr['scientificname'])) $taxonArr['sciname'] = $nodeArr['scientificname'];
		if(isset($nodeArr['authority'])) $taxonArr['author'] = $nodeArr['authority'];
		if(isset($nodeArr['kingdom'])) $taxonArr['kingdom'] = $nodeArr['kingdom'];
		if(isset($nodeArr['family'])) $taxonArr['family'] = $nodeArr['family'];
		if(isset($nodeArr['genus'])) $taxonArr['unitname1'] = $nodeArr['genus'];
		if(isset($nodeArr['status'])) $taxonArr['acceptance'] = $nodeArr['status'];
		if(isset($nodeArr['unacceptreason'])) $taxonArr['unacceptanceReason'] = $nodeArr['unacceptreason'];
		if(isset($nodeArr['valid_AphiaID'])) $taxonArr['validID'] = $nodeArr['valid_AphiaID'];
		if(isset($nodeArr['lsid'])) $taxonArr['guid'] = $nodeArr['lsid'];
		if(isset($nodeArr['rank'])) $taxonArr['taxonRank'] = $nodeArr['rank'];
		$taxonArr['rankid'] = $this->getRankIdByTaxonArr($taxonArr);
		$this->buildTaxonArr($taxonArr);
		return $taxonArr;
	}

	private function getWormParentID($wormNode, $stopID, $previousID = 0){
		$parentID = 0;
		if(array_key_exists('AphiaID', $wormNode)){
			$parentID = $wormNode['AphiaID'];
			if($stopID == $parentID) return $previousID;
			if(array_key_exists('child', $wormNode)){
				$parentID = $this->getWormParentID($wormNode['child'], $stopID, $parentID);
			}
		}
		return $parentID;
	}

	//WoRMS node batch add functions
	public function addWormsNode($postArr){
		$status = true;
		$nodeSciname = $postArr['sciname'];
		$rankLimit = $postArr['ranklimit'];
		if($nodeSciname){
			$rootTid = $this->getTid(array('sciname' => $nodeSciname));
			//Check if sciname already exists within thesaurus, if not add it
			if(!$rootTid){
				$this->logOrEcho('Taxon root ('.$nodeSciname.') not found within thesaurus, adding now...',1);
				$rootTid = $this->addWormsTaxon($nodeSciname);
			}
			if($rootTid){
				//Get children from WoRMS
				$url1 = 'https://marinespecies.org/rest/AphiaIDByName/'.rawurlencode($nodeSciname).'?marine_only=false';
				$resultStr1 = $this->getContentString($url1);
				$id = $resultStr1['str'];
				if(is_numeric($id)){
					$url2 = 'https://marinespecies.org/rest/AphiaRecordByAphiaID/'.$id;
					$this->logOrEcho($nodeSciname.' (#'.$id.') found within thesaurus',1);
					if($resultStr2 = $this->getWormsReturnStr($this->getContentString($url2),$url2)){
						$resultJson = json_decode($resultStr2);
						if($resultJson->status == 'accepted'){
							$this->logOrEcho('Starting to harvest children...',1);
							$status = $this->addWormsChildern($id, $rootTid, $rankLimit);
						}
						else{
							$this->logOrEcho('ERROR: node taxon must be an accepted taxon (status: '.$resultJson->status.')',1);
							return false;
						}
					}
				}
			}
			else{
				$this->logOrEcho('ABORT: unable to set root node for '.rawurlencode($nodeSciname),1);
				return false;
			}
		}
		else{
			$this->logOrEcho('ERROR: scientific name is null',1);
			return false;
		}
		return $status;
	}

	private function addWormsChildern($wormsID, $parentTid, $harvestRankLimit){
		$url = 'https://marinespecies.org/rest/AphiaChildrenByAphiaID/'.$wormsID;
		if($resultStr = $this->getWormsReturnStr($this->getContentString($url),$url)){
			$resultArr = json_decode($resultStr,true);
			foreach($resultArr as $nodeArr){
				if($nodeArr['status']=='accepted'){
					$this->transactionCount++;
					$taxonArr = $this->getWormsNode($nodeArr);
					$tid = $this->getTid($taxonArr);
					if($tid){
						$display = '<a href="'.$GLOBALS['CLIENT_ROOT'].'/taxa/taxonomy/taxoneditor.php?tid='.$tid.'" target="_blank">'.$nodeArr['scientificname'].'</a>';
						$this->logOrEcho($display.' already in thesaurus',2);
					}
					else{
						$this->setWormsSource($taxonArr);
						$taxonArr['parent']['tid'] = $parentTid;
						$tid = $this->loadNewTaxon($taxonArr);
					}
					if(!$harvestRankLimit || $harvestRankLimit > $taxonArr['rankid']) $this->addWormsChildern($taxonArr['id'], $tid, $harvestRankLimit);
				}
				else{
					$this->logOrEcho('NOTICE: '.$nodeArr['scientificname'].' ('.$nodeArr['status'].') skipped due to not being accepted',2);
				}
			}
		}
	}

	//TROPICOS functions
	private function addTropicosTaxon($taxonArr){
		$newTid = 0;
		$sciName = $taxonArr['sciname'];
		if(!$this->taxonomicResources['tropicos']){
			$this->logOrEcho('Error: TROPICOS API key required! Contact portal manager to establish key for portal',1);
			return false;
		}
		//Clean input string
		$searchType = 'exact';
		if(substr_count($sciName,' ') > 1) $searchType = 'wildcard';
		$sciName = str_replace(array(' subsp.',' ssp.',' var.',' f.'), '', $sciName);
		$sciName = str_replace('.','', $sciName);
		$sciName = str_replace(' ','%20', $sciName);
		$url = 'https://services.tropicos.org/Name/Search?type='.$searchType.'&format=json&name='.$sciName.'&apikey='.$this->taxonomicResources['tropicos'];
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$resultArr = json_decode($content,true);
			$id = 0;
			$closeMatchArr = array();
			foreach($resultArr as $arr){
				$unitSciname = $arr['ScientificName'];
				if(strpos($unitSciname, ' fo. ')) $unitSciname = str_replace(' fo. ', ' f. ', $unitSciname);
				if(array_key_exists('Error', $arr)){
					$this->logOrEcho('Taxon not found (code:1)',2);
					return;
				}
				if($taxonArr['sciname'] != $unitSciname){
					$pattern = '/^\D+\s{1}\D+\s{1}(subsp|ssp|var|f)\.\s{1}\D+$/';
					if(preg_match($pattern, $taxonArr['sciname'])){
						if(preg_match($pattern, $unitSciname)){
							$closeMatchArr[] = $arr['NameId'];
						}
					}
				}
				else{
					if(!array_key_exists('NomenclatureStatusID', $arr) || $arr['NomenclatureStatusID'] == 1){
						$id = $arr['NameId'];
						break;
					}
				}
			}
			if($id){
				$this->logOrEcho('Taxon found within TROPICOS',2);
				$newTid = $this->addTropicosTaxonByID($id);
			}
			else{
				$this->logOrEcho('Taxon not found (code:2)',2);
				if($closeMatchArr){
					foreach($closeMatchArr as $closeID){
						$this->addTropicosTaxonByID($closeID);
					}
				}
			}
		}
		else{
			$this->logOrEcho('ERROR: unable to connect to TROPICOS web services ('.$url.')',1);
		}
		return $newTid;
	}

	private function addTropicosTaxonByID($id){
		$taxonArr= Array();
		$url = 'https://services.tropicos.org/Name/'.$id.'?apikey='.$this->taxonomicResources['tropicos'].'&format=json';
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$resultArr = json_decode($content,true);
			$taxonArr = $this->getTropicosNode($resultArr);

			//Get parent
			if($taxonArr['rankid'] == 10){
				$taxonArr['parent']['tid'] = 'self';
			}
			else{
				$url = 'https://services.tropicos.org/Name/'.$id.'/HigherTaxa?apikey='.$this->taxonomicResources['tropicos'].'&format=json';
				if($fh = fopen($url, 'r')){
					$content = '';
					while($line = fread($fh, 1024)){
						$content .= trim($line);
					}
					fclose($fh);
					$parentArr = json_decode($content,true);
					$parentNode = $this->getTropicosNode(array_pop($parentArr));
					if(isset($parentNode['sciname']) && $parentNode['sciname']){
						$parentTid = $this->getTid($parentNode);
						if(!$parentTid){
							if(isset($parentNode['id'])){
								$parentTid= $this->addTropicosTaxonByID($parentNode['id']);
							}
						}
						if($parentTid) $parentNode['tid'] = $parentTid;
						$taxonArr['parent'] = $parentNode;
					}
				}
			}
			//Get accepted name
			$acceptedTid = 0;
			if($taxonArr['acceptedNameCount'] > 0 && $taxonArr['synonymCount'] == 0){
				$url = 'https://services.tropicos.org/Name/'.$id.'/AcceptedNames?apikey='.$this->taxonomicResources['tropicos'].'&format=json';
				if($fh = fopen($url, 'r')){
					$content = '';
					while($line = fread($fh, 1024)){
						$content .= trim($line);
					}
					fclose($fh);
					$resultArr = json_decode($content,true);
					if(isset($resultArr['Synonyms']['Synonym']['AcceptedName'])){
						$acceptedNode = $this->getTropicosNode($resultArr['Synonyms']['Synonym']['AcceptedName']);
						$this->buildTaxonArr($acceptedNode);
						$acceptedTid = $this->getTid($acceptedNode);
						if(!$acceptedTid){
							if(isset($acceptedNode['id'])){
								$acceptedTid= $this->addTropicosTaxonByID($acceptedNode['id']);
							}
						}
					}
				}
			}
		}
		if(isset($taxonArr['source']) && $taxonArr['source']){
			$taxonArr['source'] = $taxonArr['source'].' (added via TROPICOS API)';
		}
		else{
			$taxonArr['source'] = 'TROPICOS (added via API)';
		}
		return $this->loadNewTaxon($taxonArr);
	}

	private function getTropicosNode($nodeArr){
		$taxonArr = array();
		if(isset($nodeArr['NameId'])) $taxonArr['id'] = $nodeArr['NameId'];
		if(isset($nodeArr['ScientificName'])) $taxonArr['sciname'] = $nodeArr['ScientificName'];
		if(isset($nodeArr['ScientificNameWithAuthors'])) $taxonArr['scientificName'] = $nodeArr['ScientificNameWithAuthors'];
		if(isset($nodeArr['Author'])) $taxonArr['author'] = $nodeArr['Author'];
		if(isset($nodeArr['Family'])) $taxonArr['family'] = $nodeArr['Family'];
		if(isset($nodeArr['SynonymCount'])) $taxonArr['synonymCount'] = $nodeArr['SynonymCount'];
		if(isset($nodeArr['AcceptedNameCount'])) $taxonArr['acceptedNameCount'] = $nodeArr['AcceptedNameCount'];
		if(isset($nodeArr['Rank'])){
			$taxonArr['taxonRank'] = $nodeArr['Rank'];
		}
		elseif(isset($nodeArr['RankAbbreviation'])){
			$taxonArr['taxonRank'] = $nodeArr['RankAbbreviation'];
		}
		if(isset($nodeArr['Genus'])) $taxonArr['unitname1'] = $nodeArr['Genus'];
		if(isset($nodeArr['SpeciesEpithet'])) $taxonArr['unitname2'] = $nodeArr['SpeciesEpithet'];
		if(isset($nodeArr['source'])) $taxonArr['source'] = $nodeArr['source'];
		if(!isset($taxonArr['unitname1']) && $taxonArr['sciname'] && !strpos($taxonArr['sciname'],' ')) $taxonArr['unitname1'] = $taxonArr['sciname'];
		$taxonArr['rankid'] = $this->getRankIdByTaxonArr($taxonArr);
		if(isset($taxonArr['unitname2']) && isset($nodeArr['OtherEpithet'])){
			$taxonArr['unitname3'] = $nodeArr['OtherEpithet'];
			if($this->kingdomName != 'Animalia'){
				if($taxonArr['rankid'] == 230) $taxonArr['unitind3'] = 'subsp.';
				elseif($taxonArr['rankid'] == 240) $taxonArr['unitind3'] = 'var.';
				elseif($taxonArr['rankid'] == 260){
					$taxonArr['unitind3'] = 'f.';
					$taxonArr['sciname'] = str_replace(' fo. ', ' f. ', $taxonArr['sciname']);
				}

			}
		}
		return $taxonArr;
	}

	//Index Fungorum functions via MyCoPortal FdEx tools
	//http://www.indexfungorum.org/ixfwebservice/fungus.asmx/NameSearch?SearchText=Acarospora%20socialis&AnywhereInText=false&MaxNumber=10
	private function addFdexTaxon($taxonArr){
		$tid = 0;
		$sciName = $taxonArr['sciname'];
		if($sciName){
			$adjustedName = $sciName;
			if(isset($taxonArr['rankid']) && $taxonArr['rankid'] > 220) $adjustedName = trim($taxonArr['unitname1'].' '.$taxonArr['unitname2'].' '.$taxonArr['unitname3']);
			$url = 'https://mycoportal.org/fdex/services/api/query.php?qText='.str_replace(' ','%20',$adjustedName).'&qField=taxon';
			//echo $url.'<br/>';
			$retArr = $this->getContentString($url);
			$content = $retArr['str'];
			if($content == '0 results'){
				$this->logOrEcho('Taxon not found',2);
				return false;
			}
			else{
				$resultArr = json_decode($content,true);
				$numResults = count($resultArr);
				if($numResults){
					$tidAccepted = 0;
					foreach($resultArr as $unitArr){
						if($unitArr['recordSource'] == 'Index Fungorum'){
							$taxonArr['sciname'] = $unitArr['taxon'];
							$taxonArr['author'] = $unitArr['authors'];
							$rankID = $this->getRankId($unitArr['rank']);
							if($rankID) $taxonArr['rankid'] = $rankID;
							$taxonArr['source'] = 'Via fDex: '.$unitArr['recordSource'];
							$taxonArr['notes'] = 'taxonomicStatus: '.$unitArr['taxonomicStatus'].'; currentStatus: '.$unitArr['currentStatus'];
							if(isset($unitArr['parentTaxon'])){
								$parentTaxon = $unitArr['parentTaxon'];
								$parentTid = 0;
								$parentArr = $this->parseCleanCheck($parentTaxon);
								if(isset($parentArr['tid']) && $parentArr['tid']) $parentTid = $parentArr['tid'];
								else $parentTid = $this->addFdexTaxon($parentArr);
								if($parentTid) $taxonArr['parent']['tid'] = $parentTid;
							}
							if($unitArr['taxon'] != $unitArr['currentTaxon']){
								$acceptedArr = $this->parseCleanCheck($unitArr['currentTaxon']);
								if(isset($acceptedArr['tid']) && $acceptedArr['tid']) $tidAccepted = $acceptedArr['tid'];
								else $tidAccepted = $this->addFdexTaxon($acceptedArr);
							}
						}
						break;
					}
					if($taxonArr) $tid = $this->loadNewTaxon($taxonArr, $tidAccepted);
				}
			}
		}
		return $tid;
	}

	//The Bryophyte Nomenclator (ByroNames)
	//https://www.bryonames.org/api/search.json?api_key=just_for_Ed&name=Ceratolejeunea+baracoensis
	//https://www.bryonames.org/api/search.json?api_key=just_for_Ed&name=Bryum%20pendulum
	private function addBryoNamesTaxon($taxonArr){
		$tid = 0;
		$sciName = $taxonArr['sciname'];
		if($sciName){
			$adjustedName = $sciName;
			if(isset($taxonArr['rankid']) && $taxonArr['rankid'] > 220) $adjustedName = trim($taxonArr['unitname1'].' '.$taxonArr['unitname2'].' '.$taxonArr['unitname3']);
			$url = 'https://www.bryonames.org/api/search.json?api_key=just_for_Ed&name='.str_replace(' ','+',$adjustedName);
			//echo $url.'<br/>';
			$retArr = $this->getContentString($url);
			$content = $retArr['str'];
			$resultArr = json_decode($content, true);
			if($resultArr['matchCount']){
				$tidAccepted = 0;
				$unitArr = array();
				foreach($resultArr['matches'] as $matchArr){
					if(!isset($matchArr['nomenclaturalStatus']) || $matchArr['nomenclaturalStatus'] != 'nom. illeg.'){
						if($matchArr['scientificName'] == $adjustedName || $matchArr['scientificName'] == $sciName){
							if(!$unitArr){
								$unitArr = $matchArr;
							}
							elseif($unitArr['taxonomicStatus'] == 'synonym' && $matchArr['taxonomicStatus'] == 'accepted'){
								$unitArr = $matchArr;
							}
						}
					}
				}
				if($unitArr){
					$taxonArr['sciname'] = $unitArr['scientificName'];
					$taxonArr['author'] = $unitArr['scientificNameAuthorship'];
					$rankID = $this->getRankId($unitArr['taxonRank']);
					if($rankID) $taxonArr['rankid'] = $rankID;
					$taxonArr['source'] = 'Via BryoNames: '.$unitArr['namePublishedIn'];
					$taxonArr['notes'] = $unitArr['nameAccordingTo'];
					if(isset($unitArr['parentNameUsage'])){
						$parentTaxon = $unitArr['parentNameUsage'];
						if($rankID > 220) $parentTaxon = $unitArr['genus'].' '.$unitArr['specificEpithet'];
						elseif($rankID == 220) $parentTaxon = $unitArr['genus'];
						elseif(strpos($parentTaxon, ' ')){
							$unitName1 = trim(substr($parentTaxon, 0, strpos($parentTaxon, ' ')));
							if($unitName1 && $unitName1 != $taxonArr['sciname']) $parentTaxon = $unitName1;
						}
						$parentTid = 0;
						$parentArr = $this->parseCleanCheck($parentTaxon);
						if(isset($parentArr['tid']) && $parentArr['tid']) $parentTid = $parentArr['tid'];
						else $parentTid = $this->addBryoNamesTaxon($parentArr);
						if(!$parentTid){
							if(isset($taxonArr['family']) && $taxonArr['family']){
								$this->setDefaultFamily($taxonArr['family']);
								if($this->defaultFamilyTid) $parentTid = $this->defaultFamilyTid;
							}
						}
						if($parentTid) $taxonArr['parent']['tid'] = $parentTid;
					}
					if(isset($unitArr['taxonomicStatus']) && $unitArr['taxonomicStatus'] != 'accepted' && isset($unitArr['acceptedNameUsage'])){
						$acceptedArr = TaxonomyUtilities::parseScientificName($unitArr['acceptedNameUsage'], $this->conn, $this->kingdomName);
						$tidAccepted = $this->getTid($taxonArr);
						if(!$tidAccepted) $tidAccepted = $this->addBryoNamesTaxon($acceptedArr);
					}
				}
				if($taxonArr) $tid = $this->loadNewTaxon($taxonArr, $tidAccepted);
			}
			else{
				$this->logOrEcho('Taxon not found',2);
				return false;
			}
		}
		return $tid;
	}

	//EOL functions
	private function addEolTaxon($taxonArr){
		//Returns content for accepted name
		$tid = 0;
		$term = $taxonArr['sciname'];
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$searchRet = $eolManager->searchEOL($term);
			if(isset($searchRet['id'])){
				//Id of EOL preferred name is returned
				$searchSyns = ((strpos($searchRet['title'],$term) !== false)?false:true);
				$tid = $this->addEolTaxonById($searchRet['id'], $searchSyns, $term);
			}
			else{
				$this->logOrEcho('Taxon not found',2);
			}
		}
		else{
			$this->logOrEcho('EOL web services are not available ',1);
			return false;
		}
		return $tid;
	}

	private function addEolTaxonById($eolTaxonId, $searchSyns = false, $term = ''){
		//Returns content for accepted name
		$taxonArr = array();
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$taxonArr = $eolManager->getPage($eolTaxonId, false);
			if($searchSyns && isset($taxonArr['syns'])){
				//Only add synonym that was original target taxon; remove all others
				foreach($taxonArr['syns']as $k => $synArr){
					if(strpos($synArr['scientificName'],$term) !== 0) unset($taxonArr['syns'][$k]);
				}
			}
			if(isset($taxonArr['taxonConcepts'])){
				if($taxonConceptId = key($taxonArr['taxonConcepts'])){
					$conceptArr = $eolManager->getHierarchyEntries($taxonConceptId);
					if(isset($conceptArr['parent'])){
						$parentTid = $this->getTid($conceptArr['parent']);
						if(!$parentTid && isset($conceptArr['parent']['taxonConceptID'])){
							$parentTid = $this->addEolTaxonById($conceptArr['parent']['taxonConceptID']);
						}
						if($parentTid){
							$conceptArr['parent']['tid'] = $parentTid;
							$taxonArr['parent'] = $conceptArr['parent'];
						}
					}
				}
			}
			if(!isset($taxonArr['source'])) $taxonArr['source'] = 'EOL - '.date('Y-m-d G:i:s');
		}
		else{
			$this->logOrEcho('EOL web services are not available ',1);
			return false;
		}
		//Process taxonomic name
		if($taxonArr) $this->logOrEcho('Taxon found within EOL',2);
		else{
			$this->logOrEcho('Taxon ID not found ('.$eolTaxonId.')',2);
			return false;
		}
		if(isset($taxonArr['source']) && $taxonArr['source']){
			$taxonArr['source'] = $taxonArr['source'].' (added via EOL API)';
		}
		else{
			$taxonArr['source'] = 'EOL (added via API)';
		}
		return $this->loadNewTaxon($taxonArr);
	}

	//Shared functions
	private function getContentString($url){
		$retArr = array();
		if($url){
			if($fh = fopen($url, 'r')){
				stream_set_timeout($fh, 10);
				$contentStr = '';
				while($line = fread($fh, 1024)){
					$contentStr .= trim($line);
				}
				fclose($fh);
				$retArr['str'] = $contentStr;
			}
			//Get code
			$statusStr = $http_response_header[0];
			if(preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$statusStr, $out)){
				$retArr['code'] = intval($out[1]);
			}
		}
		return $retArr;
	}

	//Database functions
	private function loadNewTaxon($taxonArr, $tidAccepted = 0){
		$newTid = 0;
		if(!$taxonArr) return false;
		if(!$this->taxaFieldArr) $this->buildTaxaFieldArr();
		if((!isset($taxonArr['sciname']) || !$taxonArr['sciname']) && isset($taxonArr['scientificName']) && $taxonArr['scientificName']){
			$this->buildTaxonArr($taxonArr);
		}
		if(!$this->validateTaxonArr($taxonArr)) return false;
		//Check to see sciname is in taxon table, but perhaps not linked to current thesaurus
		$sql = 'SELECT tid FROM taxa WHERE (sciname = "'.$this->cleanInStr($taxonArr['sciname']).'") ';
		if($this->kingdomName) $sql .= 'AND (kingdomname = "'.$this->kingdomName.'" OR kingdomname IS NULL) ';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$newTid = $r->tid;
		}
		$rs->free();
		$loadTaxon = true;
		if($newTid){
			//Name already exists within taxa table, but need to check if it's part if target thesaurus which name is accepted
			$sql = 'SELECT tidaccepted FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$newTid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				//Taxon is already in this thesaurus, thus skip loading and jsut link synonyms to accepted name of this taxon
				$tidAccepted = $r->tidaccepted;
				$loadTaxon= false;
			}
			$rs->free();
		}
		if($loadTaxon){
			if(!$newTid){
				if(isset($taxonArr['source']) && strlen($taxonArr['source']) > $this->taxaFieldArr['source']['size']){
					$taxonArr['source'] = substr($taxonArr['source'],0,$this->taxaFieldArr['source']['size']);
				}
				$sqlInsert = 'INSERT INTO taxa(sciname, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, rankid, source, modifiedUid) '.
					'VALUES("'.$this->cleanInStr($taxonArr['sciname']).'",'.
					(isset($taxonArr['unitind1']) && $taxonArr['unitind1']?'"'.$this->cleanInStr($taxonArr['unitind1']).'"':'NULL').',"'.
					$this->cleanInStr($taxonArr['unitname1']).'",'.
					(isset($taxonArr['unitind2']) && $taxonArr['unitind2']?'"'.$this->cleanInStr($taxonArr['unitind2']).'"':'NULL').','.
					(isset($taxonArr['unitname2']) && $taxonArr['unitname2']?'"'.$this->cleanInStr($taxonArr['unitname2']).'"':'NULL').','.
					(isset($taxonArr['unitind3']) && $taxonArr['unitind3']?'"'.$this->cleanInStr($taxonArr['unitind3']).'"':'NULL').','.
					(isset($taxonArr['unitname3']) && $taxonArr['unitname3']?'"'.$this->cleanInStr($taxonArr['unitname3']).'"':'NULL').',"'.
					(isset($taxonArr['author']) && $taxonArr['author']?$this->cleanInStr($taxonArr['author']):'').'",'.
					(isset($taxonArr['rankid']) && is_numeric($taxonArr['rankid'])?$taxonArr['rankid']:0).','.
					(isset($taxonArr['source']) && $taxonArr['source']?'"'.$this->cleanInStr($taxonArr['source']).'"':'NULL').','.
					$GLOBALS['SYMB_UID'].')';
				if($this->conn->query($sqlInsert)){
					$newTid = $this->conn->insert_id;
				}
				else{
					$this->logOrEcho('ERROR inserting '.$taxonArr['sciname'].': '.$this->conn->error,1);
					return false;
				}
			}
			if($newTid){
				//Get parent identifier
				$parentTid = 0;
				if(isset($taxonArr['parent']['tid'])){
					if($taxonArr['parent']['tid'] == 'self') $parentTid = $newTid;
					elseif(is_numeric($taxonArr['parent']['tid'])) $parentTid = $taxonArr['parent']['tid'];
				}
				if(!$parentTid && isset($taxonArr['parent']['sciname'])){
					$parentTid = $this->getTid($taxonArr['parent']);
					if(!$parentTid){
						//$parentTid = $this->processSciname($taxonArr['parent']['sciname']);
					}
				}

				if(!$parentTid){
					$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': unable to get parentTid',1);
					return false;
				}

				//Establish acceptance
				if(!$tidAccepted) $tidAccepted = $newTid;
				$sqlInsert2 = 'INSERT INTO taxstatus(tid, tidAccepted, taxAuthId, parentTid, family, UnacceptabilityReason, modifiedUid)
					VALUES('.$newTid.','.$tidAccepted.','.$this->taxAuthId.','.$parentTid.','.
					(isset($taxonArr['family']) && $taxonArr['family']?'"'.$this->cleanInStr($taxonArr['family']).'"':'NULL').','.
					(isset($taxonArr['acceptanceReason']) && $taxonArr['acceptanceReason']?'"'.$this->cleanInStr($taxonArr['acceptanceReason']).'"':'NULL').','.$GLOBALS['SYMB_UID'].')';
				if($this->conn->query($sqlInsert2)){
					//Add hierarchy index
					$sqlHier = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) VALUES('.$newTid.','.$parentTid.','.$this->taxAuthId.')';
					if(!$this->conn->query($sqlHier)){
						$this->logOrEcho('ERROR adding new tid to taxaenumtree (step 1): '.$this->conn->error,1);
					}
					$sqlHier2 = 'INSERT IGNORE INTO taxaenumtree(tid,parenttid,taxauthid) '.
						'SELECT '.$newTid.' AS tid, parenttid, taxauthid FROM taxaenumtree WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$parentTid.')';
					if(!$this->conn->query($sqlHier2)){
						$this->logOrEcho('ERROR adding new tid to taxaenumtree (step 2): '.$this->conn->error,1);
					}
					$sqlKing = 'UPDATE taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
						'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
						'SET t.kingdomname = t2.sciname '.
						'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (t.tid = '.$newTid.') AND (t2.rankid = 10)';
					if(!$this->conn->query($sqlKing)){
						$this->logOrEcho('ERROR updating kingdom string: '.$this->conn->error,1);
					}
					//Display action message
					$taxonDisplay = $taxonArr['sciname'];
					if(isset($GLOBALS['USER_RIGHTS']['Taxonomy'])){
						$taxonDisplay = '<a href="'.$GLOBALS['CLIENT_ROOT'].'/taxa/taxonomy/taxoneditor.php?tid='.$newTid.'" target="_blank">'.$taxonArr['sciname'].'</a>';
					}
					$accStr = 'accepted';
					if($tidAccepted != $newTid){
						if(isset($GLOBALS['USER_RIGHTS']['Taxonomy'])){
							$accStr = 'synonym of taxon <a href="'.$GLOBALS['CLIENT_ROOT'].'/taxa/taxonomy/taxoneditor.php?tid='.$tidAccepted.'" target="_blank">#'.$tidAccepted.'</a>';
						}
						else{
							$accStr = 'synonym of taxon #'.$tidAccepted;
						}
					}
					$this->logOrEcho('Taxon <b>'.$taxonDisplay.'</b> added to thesaurus as '.$accStr,2);
				}
				//Load taxonomic resources identifiers
				if(isset($taxonArr['resourceURL'])){
					$this->insertTaxonomicResource($newTid, 'Catalog of Life', $taxonArr['id'], $taxonArr['resourceURL']);
				}
				if(isset($taxonArr['sourceURL'])){
					if(preg_match('/molluscabase.+\D+(\d+)$/', $taxonArr['sourceURL'], $m)){
						$this->insertTaxonomicResource($newTid, 'MolluscaBase', $m[1], $taxonArr['sourceURL']);
					}
				}
			}
		}
		//Add Synonyms
		if(isset($taxonArr['syns'])){
			foreach($taxonArr['syns'] as $synArr){
				if($synArr){
					if(isset($taxonArr['source']) && $taxonArr['source'] && (!isset($synArr['source']) || !$synArr['source'])) $synArr['source'] = $taxonArr['source'];
					$acceptanceReason = '';
					if(isset($taxonArr['acceptanceReason']) && $taxonArr['acceptanceReason']) $acceptanceReason = $taxonArr['acceptanceReason'];
					if(isset($synArr['synreason']) && $synArr['synreason']) $acceptanceReason = $synArr['synreason'];
					if($acceptanceReason == 'misspelling'){
						$this->logOrEcho('Name not added because it is marked as a misspelling',1);
						$this->fullyResolved = false;
					}
					else{
						if($acceptanceReason && (!isset($synArr['acceptanceReason']) || !$synArr['acceptanceReason'])) $synArr['acceptanceReason'] = $acceptanceReason;
						$this->loadNewTaxon($synArr,$newTid);
					}
				}
			}
		}
		//Add common names
		if(isset($taxonArr['verns'])){
			if($this->langArr === false) $this->setLangArr();
			foreach($taxonArr['verns'] as $vernArr){
				if(array_key_exists($vernArr['language'],$this->langArr)){
					$sqlVern = 'INSERT INTO taxavernaculars(tid,vernacularname,language) VALUES('.$newTid.',"'.$vernArr['vernacularName'].'",'.$this->langArr[$vernArr['vernacularName']].')';
					if(!$this->conn->query($sqlVern)){
						$this->logOrEcho('ERROR loading vernacular '.$taxonArr['sciname'].': '.$this->conn->error,1);
					}
				}
			}
		}
		return $newTid;
	}

	private function buildTaxaFieldArr(){
		$sql = 'SHOW COLUMNS FROM taxa';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$field = strtolower($r->Field);
			$type = $r->Type;
			if(strpos($type,'double') !== false || strpos($type,'int') !== false){
				$this->taxaFieldArr[$field]['type'] = 'numeric';
			}
			elseif(strpos($type,'decimal') !== false){
				$this->taxaFieldArr[$field]['type'] = 'decimal';
				if(preg_match('/\((.*)\)$/', $type, $matches)){
					$this->taxaFieldArr[$field]['size'] = $matches[1];
				}
			}
			elseif(strpos($type,'date') !== false){
				$this->taxaFieldArr[$field]['type'] = 'date';
			}
			else{
				$this->taxaFieldArr[$field]['type'] = 'string';
				if(preg_match('/\((\d+)\)$/', $type, $matches)){
					$this->taxaFieldArr[$field]['size'] = substr($matches[0],1,strlen($matches[0])-2);
				}
			}
		}
		$rs->free();
	}

	private function validateTaxonArr(&$taxonArr){
		if(!is_array($taxonArr)) return;
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			if(isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
				$taxonArr['rankid'] = $this->getRankIdByTaxonArr($taxonArr);
			}
		}
		if(isset($taxonArr['unitind3']) && $taxonArr['unitind3']){
			if($taxonArr['unitind3'] == 'ssp.') $taxonArr['unitind3'] = 'subsp.';
			if($this->kingdomName == 'Animalia' && $taxonArr['unitind3'] == 'subsp.'){
				$taxonArr['unitind3'] = '';
				$taxonArr['sciname'] = str_replace('subsp. ', '', $taxonArr['sciname']);
			}
		}
		if(!$this->kingdomTid) $this->setDefaultKingdom();
		if(!array_key_exists('parent',$taxonArr) || !$taxonArr['parent']){
			$taxonArr['parent'] = $this->getParentArr($taxonArr);
		}
		//Check to make sure required fields are present
		if(!isset($taxonArr['sciname']) || !$taxonArr['sciname']){
			$this->logOrEcho('ERROR loading: Input scientific name not defined',1);
			return false;
		}
		if(!isset($taxonArr['parent']) || !$taxonArr['parent']){
			$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': Parent name not definable',1);
			return false;
		}
		if(!isset($taxonArr['unitname1']) || !$taxonArr['unitname1']){
			$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': unitname1 not defined',1);
			return false;
		}
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			//Provide warning, but don't fail validation
			$this->logOrEcho('Warning: rankid not defined for '.$taxonArr['sciname'],1);
		}
		return true;
	}

	private function getParentArr($taxonArr){
		if(!is_array($taxonArr)) return;
		$parArr = array();
		if($taxonArr['sciname']){
			if(isset($taxonArr['rankid']) && $taxonArr['rankid']){
				if(!$this->kingdomTid) $this->setDefaultKingdom();
				if($this->kingdomName){
					//Set this as default parent
					$parArr = array( 'tid' => $this->kingdomTid, 'sciname' => $this->kingdomName, 'taxonRank' => 'kingdom', 'rankid' => '10' );
				}
				if($taxonArr['rankid'] > 140){
					if(isset($taxonArr['family']) && $taxonArr['family'] && $taxonArr['family'] != $this->defaultFamily) $this->setDefaultFamily($taxonArr['family']);
					if($this->defaultFamily){
						$parArr = array( 'tid' => $this->defaultFamilyTid, 'sciname' => $this->defaultFamily, 'taxonRank' => 'family', 'rankid' => '140' );
					}
				}
				if($taxonArr['rankid'] > 180){
					$parArr = array( 'sciname' => $taxonArr['unitname1'], 'taxonRank' => 'genus', 'rankid' => '180' );
				}
				if($taxonArr['rankid'] > 220){
					$parArr = array( 'sciname' => $taxonArr['unitname1'].' '.$taxonArr['unitname2'], 'taxonRank' => 'species', 'rankid' => '220' );
				}
			}
		}
		return $parArr;
	}

	private function insertTaxonomicResource($tid, $sourceName, $sourceIdentifier, $sourceUrl){
		$sql = 'INSERT INTO taxaresourcelinks(tid, sourceName, sourceIdentifier, url) VALUES(?, ?, ?, ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('isss', $tid, $sourceName, $sourceIdentifier, $sourceUrl);
			$stmt->execute();
			$stmt->close();
		}
	}

	//Misc functions
	public function buildTaxonArr(&$taxonArr){
		if(is_array($taxonArr)){
			$rankid = array_key_exists('rankid', $taxonArr)?$taxonArr['rankid']:0;
			$sciname = array_key_exists('sciname', $taxonArr)?$taxonArr['sciname']:'';
			if(!$sciname && array_key_exists('scientificName', $taxonArr)) $sciname = $taxonArr['scientificName'];
			if($sciname) $taxonArr = array_merge(TaxonomyUtilities::parseScientificName($sciname,$this->conn,$rankid,$this->kingdomName), $taxonArr);
		}
	}

	public function getCloseMatch($taxonStr){
		$retArr = array();
		$taxonStr = $this->cleanInStr($taxonStr);
		if($taxonStr){
			$infraArr = array('subsp.','ssp.','var.','f.');
			$unitArr = explode(' ', $taxonStr);
			$unitname1 = $this->cleanInStr(array_shift($unitArr));
			if(strlen($unitname1) == 1) $unitname1 = $this->cleanInStr(array_shift($unitArr));
			$unitname2 = $this->cleanInStr(array_shift($unitArr));
			if(strlen($unitname2) == 1) $unitname2 = $this->cleanInStr(array_shift($unitArr));
			$infraEpithetArr = array();
			foreach($unitArr as $str){
				if(!in_array($str, $infraArr)){
					$infraEpithetArr[] = $this->cleanInStr($str);
				}
			}
			if($infraEpithetArr){
				//Look for infraspecific species with different rank indicators
				$sql = 'SELECT tid, sciname FROM taxa WHERE (unitname1 = "'.$unitname1.'") AND (unitname2 = "'.$unitname2.'") ';
				if($this->kingdomName) $sql .= 'AND (kingdomname = "'.$this->cleanInStr($this->kingdomName).'" OR kingdomname IS NULL) ';
				$sql .= 'ORDER BY sciname';
				$rs = $this->conn->query($sql);
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->free();
			}
			if($unitname2){
				if(!$retArr){
					//Look for match where
					$searchStr = substr($unitname1, 0, 4).'%';
					$searchStr .= ' '.substr($unitname2, 0, 4).'%';
					if($infraEpithetArr) $searchStr .= ' '.substr($infraEpithetArr[0], 0, 5).'%';
					$sql = 'SELECT tid, sciname FROM taxa WHERE (sciname LIKE "'.$searchStr.'") ';
					if($this->kingdomName) $sql .= 'AND (kingdomname = "'.$this->cleanInStr($this->kingdomName).'" OR kingdomname IS NULL) ';
					$sql .= 'ORDER BY sciname LIMIT 15';
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$percent = 0;
						similar_text($taxonStr,$row->sciname,$percent);
						if($percent > 70) $retArr[$row->tid] = $row->sciname;
					}
					$rs->free();
				}

				if(!$retArr){
					//Look for matches based on same edithet but different genus
					$sql = 'SELECT tid, sciname FROM taxa WHERE (sciname LIKE "'.substr($unitname1,0,2).'% '.$unitname2.'") ';
					if($this->kingdomName) $sql .= 'AND (kingdomname = "'.$this->cleanInStr($this->kingdomName).'" OR kingdomname IS NULL) ';
					$sql .= 'ORDER BY sciname';
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$retArr[$row->tid] = $row->sciname;
					}
					$rs->free();
				}
			}
			//Get soundex matches
			$sql = 'SELECT tid, sciname FROM taxa WHERE SOUNDEX(sciname) = SOUNDEX("'.$this->cleanInStr($taxonStr).'") ';
			if($this->kingdomName) $sql .= 'AND (kingdomname = "'.$this->cleanInStr($this->kingdomName).'" OR kingdomname IS NULL) ';
			$sql .= 'ORDER BY sciname LIMIT 5';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(!strpos($taxonStr,' ') || strpos($row->sciname,' ')){
					$retArr[$row->tid] = $row->sciname;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTid($taxonArr){
		$sciname = '';
		if(isset($taxonArr['sciname']) && $taxonArr['sciname']) $sciname = $taxonArr['sciname'];
		if(!$sciname && isset($taxonArr['scientificname']) && $taxonArr['scientificname']) $sciname = $taxonArr['scientificname'];
		if(!$sciname) return 0;
		$tidArr = array();
		//Get tid, author, and rankid
		$sql = 'SELECT tid, author, rankid FROM taxa WHERE (sciname = "'.$this->cleanInStr($sciname).'") ';
		if($this->kingdomTid){
			$sql = 'SELECT t.tid, t.author, t.rankid
				FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid
				WHERE (t.sciname = "'.$this->cleanInStr($sciname).'") AND e.taxauthid = 1 AND e.parenttid = '.$this->kingdomTid;
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidArr[$r->tid]['author'] = $r->author;
			$tidArr[$r->tid]['rankid'] = $r->rankid;
		}
		$rs->free();
		if(!$tidArr) return 0;
		//Check if homonyms are returned
		if(count($tidArr) == 1) return key($tidArr);
		else{
			//Mulitple matches exist, get parents to determine which is best
			$sqlPar = 'SELECT DISTINCT e.tid, t.tid AS parenttid, t.sciname, t.rankid '.
				'FROM taxaenumtree e INNER JOIN taxa t ON e.parenttid = t.tid '.
				'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (e.tid IN('.implode(',',array_keys($tidArr)).')) AND (t.rankid IN (10,140)) ';
			$rsPar = $this->conn->query($sqlPar);
			while($rPar = $rsPar->fetch_object()){
				if($r->rankid == 10) $tidArr[$rPar->tid]['kingdom'] = $rPar->sciname;
				elseif($r->rankid == 140) $tidArr[$rPar->tid]['family'] = $rPar->sciname;
			}
			$rsPar->free();

			//Rate each name
			$goodArr = array();
			foreach($tidArr as $t => $tArr){
				//If rankid is same, then it gets a point
				$goodArr[$t] = 0;
				if(isset($taxonArr['rankid']) && $taxonArr['rankid']){
					if($tArr['rankid'] == $taxonArr['rankid']){
						$goodArr[$t] = 1;
					}
				}
				//Gets 2 points if family is the same
				if(isset($tArr['family']) && $tArr['family']){
					if(isset($taxonArr['family']) && $taxonArr['family']){
						if(strtolower($tArr['family']) == strtolower($taxonArr['family'])){
							$goodArr[$t] += 2;
						}
					}
					elseif($this->defaultFamily){
						if(strtolower($tArr['family']) == strtolower($this->defaultFamily)){
							$goodArr[$t] += 2;
						}
					}
				}
				//Gets 2 points if kingdom is the same
				if($this->kingdomName && isset($tArr['kingdom']) && $tArr['kingdom']){
					if(strtolower($tArr['kingdom']) == strtolower($this->kingdomName)){
						$goodArr[$t] += 2;
					}
				}
				//Gets 2 points if author is the same, 1 point if 80% similar
				if(isset($taxonArr['author']) && $taxonArr['author']){
					$author1 = str_replace(array(' ','.'), '', $taxonArr['author']);
					$author2 = str_replace(array(' ','.'), '', $tArr['author']);
					$percent = 0;
					similar_text($author1, $author2, $percent);
					if($author1 == $author2) $goodArr[$t] += 2;
					elseif($percent > 80) $goodArr[$t] += 1;
				}
			}
			asort($goodArr);
			end($goodArr);
			return key($goodArr);
		}
	}

	private function getTidAccepted($tid){
		$retTid = 0;
		$sql = 'SELECT tidaccepted FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retTid = $r->tidaccepted;
		}
		$rs->free();
		return $retTid;
	}

	private function setLangArr(){
		if($this->langArr === false){
			$this->langArr = array();
			$sql = 'SELECT langid, langname, iso639_1 FROM adminlanguages';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->langArr[$r->langname] = $r->langid;
				$this->langArr[$r->iso639_1] = $r->langid;
			}
			$rs->free();
		}
	}

	public function rebuildHierarchyEnumTree(){
		$status = TaxonomyUtilities::rebuildHierarchyEnumTree($this->conn);
		if($status === true){
			return true;
		}
		else{
			$this->errorMessage = $status;
		}
	}

	public function buildHierarchyEnumTree(){
		$status = TaxonomyUtilities::buildHierarchyEnumTree($this->conn);
		if($status === true){
			return true;
		}
		else{
			$this->errorMessage = $status;
		}
	}

	//Data retrival functions
	public function getKingdomArr(){
		$retArr = array();
		$sql = 'SELECT tid, sciname FROM taxa WHERE rankid = 10 ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}

	private function setDefaultKingdom(){
		if(!$this->kingdomName && !$this->kingdomTid){
			$kArr = $this->getDefaultKingdom();
			$this->kingdomName = $kArr['sciname'];
			$this->kingdomTid = $kArr['tid'];
		}
	}

	public function getDefaultKingdom(){
		$retArr = array();
		$sql = 'SELECT t.sciname, t.tid, COUNT(e.tid) as cnt FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
			'WHERE (t.rankid = 10) AND (e.taxauthid = '.$this->taxAuthId.') GROUP BY t.sciname ORDER BY cnt desc';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retArr['sciname'] = $r->sciname;
			$retArr['tid'] = $r->tid;
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}

	public function setKingdomTid($id){
		if(is_numeric($id)) $this->kingdomTid = $id;
	}

	public function setKingdomName($name){
		if(preg_match('/^(\d+):([a-zA-Z]+)$/', $name, $m)){
			$this->kingdomTid = $m[1];
			$this->kingdomName = $m[2];
		}
		elseif(preg_match('/^[a-zA-Z]+$/', $name)){
			$this->kingdomName = $name;
			if(!$this->kingdomTid){
				$sql = 'SELECT tid FROM taxa WHERE sciname = "'.$name.'" AND rankid = 10';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$this->kingdomTid = $r->tid;
				}
				$rs->free();
			}
		}
		$this->setRankIdArr();
	}

	private function getRankId($taxonRank){
		$rankID = 0;
		$taxonRank = strtolower($taxonRank);
		if(isset($this->rankIdArr[$taxonRank])) $rankID = $this->rankIdArr[$taxonRank];
		return $rankID;
	}

	private function getRankIdByTaxonArr($taxonArr){
		$rankID = 0;
		if(isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
			$taxonRank = strtolower($taxonArr['taxonRank']);
			if(array_key_exists($taxonRank, $this->rankIdArr)) $rankID = $this->rankIdArr[$taxonRank];
		}
		if(!$rankID && isset($taxonArr['unitind3']) && $taxonArr['unitind3']){
			$unitInd3 = strtolower($taxonArr['unitind3']);
			if(array_key_exists($unitInd3, $this->rankIdArr)) $rankID = $this->rankIdArr[$unitInd3];
		}
		return $rankID;
	}

	private function setRankIdArr(){
		if(!$this->rankIdArr && $this->kingdomName){
			$sql = 'SELECT rankid, rankname FROM taxonunits WHERE kingdomname = "'.$this->kingdomName.'"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->rankIdArr[strtolower($r->rankname)] = $r->rankid;
			}
			$rs->free();
			//Add default values
			$defaultRankArr = array('organism' => 1, 'kingdom' => 10, 'subkingdom' => 20, 'infrakingdom' => 25, 'superclass' => 50, 'class' => 60, 'subclass' => 70,
				'infraclass' => 80, 'subterclass' => 85, 'superorder' => 90, 'order' => 100, 'suborder' => 110, 'infraorder' => 120, 'superfamily' => 130, 'family' => 140,
				'subfamily' => 150, 'tribe' => 160, 'subtribe' => 170, 'genus' => 180, 'subgenus' => 190, 'species' => 220, 'subspecies' => 230,
				'variety' => 240, 'subvariety' => 250, 'form' => 260, 'subform' => 270, 'cultivated' => 300);
			foreach($defaultRankArr as $rName => $rid){
				if(!isset($this->rankIdArr[$rName]) && !in_array($rid,$this->rankIdArr)) $this->rankIdArr[$rName] = $rid;
			}
			if(!in_array(30,$this->rankIdArr)){
				if(!isset($this->rankIdArr['phylum'])) $this->rankIdArr['phylum'] = 30;
				if(!isset($this->rankIdArr['division'])) $this->rankIdArr['division'] = 30;
			}
			if(!in_array(40,$this->rankIdArr)){
				if(!isset($this->rankIdArr['subphylum'])) $this->rankIdArr['subphylum'] = 40;
				if(!isset($this->rankIdArr['infraphylum'])) $this->rankIdArr['infraphylum'] = 45;
				if(!isset($this->rankIdArr['subdivision'])) $this->rankIdArr['subdivision'] = 40;
			}
			if(strtolower($this->kingdomName) == 'animalia'){
				if(!isset($this->rankIdArr['section']) && !in_array(200,$this->rankIdArr)) $this->rankIdArr['section'] = 125;
				if(!isset($this->rankIdArr['subsection']) && !in_array(200,$this->rankIdArr)) $this->rankIdArr['subsection'] = 127;
			}
			else{
				if(!isset($this->rankIdArr['section']) && !in_array(200,$this->rankIdArr)) $this->rankIdArr['section'] = 200;
				if(!isset($this->rankIdArr['subsection']) && !in_array(200,$this->rankIdArr)) $this->rankIdArr['subsection'] = 210;
			}
			if(isset($this->rankIdArr['organism'])) $this->rankIdArr['biota'] = $this->rankIdArr['organism'];
			if(isset($this->rankIdArr['superclass'])) $this->rankIdArr['supercl.'] = $this->rankIdArr['superclass'];
			if(isset($this->rankIdArr['class'])) $this->rankIdArr['cl.'] = $this->rankIdArr['class'];
			if(isset($this->rankIdArr['subclass'])) $this->rankIdArr['subcl.'] = $this->rankIdArr['subclass'];
			if(isset($this->rankIdArr['superorder'])) $this->rankIdArr['superord.'] = $this->rankIdArr['superorder'];
			if(isset($this->rankIdArr['order'])) $this->rankIdArr['ord.'] = $this->rankIdArr['order'];
			if(isset($this->rankIdArr['suborder'])) $this->rankIdArr['subord.'] = $this->rankIdArr['suborder'];
			if(isset($this->rankIdArr['family'])) $this->rankIdArr['fam.'] = $this->rankIdArr['family'];
			if(isset($this->rankIdArr['genus'])) $this->rankIdArr['gen.'] = $this->rankIdArr['genus'];
			if(isset($this->rankIdArr['species'])) $this->rankIdArr['sp.'] = $this->rankIdArr['species'];
			if(isset($this->rankIdArr['subspecies'])){
				$this->rankIdArr['ssp.'] = $this->rankIdArr['subspecies'];
				$this->rankIdArr['subsp.'] = $this->rankIdArr['subspecies'];
			}
			if(isset($this->rankIdArr['variety'])){
				$this->rankIdArr['v.'] = $this->rankIdArr['variety'];
				$this->rankIdArr['var.'] = $this->rankIdArr['variety'];
				$this->rankIdArr['morph'] = $this->rankIdArr['variety'];
			}
			if(isset($this->rankIdArr['form'])){
				$this->rankIdArr['f.'] = $this->rankIdArr['form'];
				$this->rankIdArr['fo.'] = $this->rankIdArr['form'];
				$this->rankIdArr['forma'] = $this->rankIdArr['form'];
			}
		}
	}

	public function setTaxonomicResources($resourceArr){
		if(!$resourceArr){
			$this->logOrEcho('ERROR: Taxonomic Authority list not defined');
			return false;
		}
		if(!isset($GLOBALS['TAXONOMIC_AUTHORITIES']) || !$GLOBALS['TAXONOMIC_AUTHORITIES'] || !is_array($GLOBALS['TAXONOMIC_AUTHORITIES'])){
			$this->logOrEcho('ERROR activating Taxonomic Authority list (TAXONOMIC_AUTHORITIES) not configured correctly');
			return false;
		}
		$this->taxonomicResources = array_intersect_key(array_change_key_case($GLOBALS['TAXONOMIC_AUTHORITIES']),array_flip($resourceArr));
	}

	public function getTaxonomicResources(){
		return $this->taxonomicResources;
	}

	public function setDefaultAuthor($str){
		$this->defaultAuthor = $str;
	}

	public function setDefaultFamily($familyStr){
		$this->defaultFamily = $familyStr;
		$sql = 'SELECT tid FROM taxa WHERE (rankid = 140) AND (sciname = "'.$this->cleanInStr($familyStr).'")';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->defaultFamilyTid = $r->tid;
		}
		$rs->free();
	}

	public function isFullyResolved(){
		return $this->fullyResolved;
	}

	public function getTransactionCount(){
		return $this->transactionCount;
	}
}
?>