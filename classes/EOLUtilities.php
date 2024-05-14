<?php
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class EOLUtilities {

	private $targetLanguages = array('en');
	private $errorStr;

	function __construct() {
	}

	function __destruct(){
	}

	public function pingEOL(){
		//http://eol.org/api/docs/ping
		$pingUrl = 'http://eol.org/api/ping/1.0.json';
		if($fh = fopen($pingUrl, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			//Evaluate ping result
			$pingArr = json_decode($content);
			if($resObj = $pingArr->response){
				if($resObj->message && $resObj->message == 'Success'){
					return true;
				}
			}
		}
		else{
			$this->errorStr = 'ERROR opening EOL ping url: '.$url;
		}
		return false;
	}

	/*
	 * INPUT: scientific name
	 * OUTPUT: array representing taxonomy resource
	 *   Example: array('id' => '',
	 *        			'title' => '',
	 *        			'link' => ''
	 *        	   )
	 */
	public function searchEOL($sciName){
		//Returns accepted name
		//http://eol.org/api/docs/search
		//http://eol.org/api/search/1.0.json?q=Pinus+ponderosa&page=1&exact=true&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=
		//http://eol.org/api/search/1.0.json?q=Pinus+ponderosa+var.+arizonica&page=1&exact=true&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=
		$retArr = Array();
		if($sciName){
			$retArr['searchTaxon'] = $sciName;
			//$url = 'http://eol.org/api/search/1.0.json?q='.str_replace(" ","%20",$sciName).'&page=1&exact=true';
			$url = 'http://eol.org/api/search/1.0.json?q='.str_replace(" ","%20",$sciName).'&page=1';
			if($fh = fopen($url, 'r')){
				$content = "";
				while($line = fread($fh, 1024)){
					$content .= trim($line);
				}
				fclose($fh);
				//Process return
				$searchObj = json_decode($content);
				if($searchObj->totalResults){
					$resultObj = $searchObj->results;
					foreach($resultObj as $index => $result){
						$retArr['id'] = $result->id;
						$retArr['title'] = $result->title;
						$retArr['link'] = $result->link;
						if(stripos($result->title,$sciName) === 0){
							break;
						}
					}
				}
				else{
					$this->errorStr = "No EOL results returned";
					return false;
				}
			}
			else{
				$this->errorStr = 'ERROR opening EOL search url: '.$url;
			}
		}
		return $retArr;
	}

	public function getPage($id, $includeSynonyms = true, $includeCommonNames = false, $contentLimit = 1){
		//http://eol.org/api/docs/pages
		//http://eol.org/api/pages/1.0/205264.json?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true&common_names=false&synonyms=false&references=false&vetted=0&cache_ttl=
		$taxonArr = Array();
		$url = 'http://eol.org/api/pages/1.0/'.$id.'.json?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true';
		$url .= '&common_names='.($includeCommonNames?'true':'false').'&synonyms='.($includeSynonyms?'true':'false').'&references=false&vetted=0&cache_ttl=';
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			//Process return
			$eolObj = json_decode($content);
			//Get other stuff - to be added

			//Get taxonomic concepts
			$taxonArr = TaxonomyUtilities::parseScientificName($eolObj->scientificName);
			if($eolObj->scientificName) $taxonArr['scientificName'] = $eolObj->scientificName;
			if(isset($eolObj->taxonConcepts)){
				$cnt = 1;
				foreach($eolObj->taxonConcepts as $tcObj){
					$taxonArr['taxonConcepts'][$tcObj->identifier] = $tcObj->nameAccordingTo;
					if(!isset($taxonArr['taxonRank']) && isset($tcObj->taxonRank)){
						$taxonArr['taxonRank'] = $tcObj->taxonRank;
					}
					$cnt++;
					if($cnt > $contentLimit) break;
				}
			}
			//Add synonyms
			if($includeSynonyms && isset($eolObj->synonyms)){
				$cnt = 0;
				$uniqueList = array();
				foreach($eolObj->synonyms as $synObj){
					if(!in_array($synObj->synonym, $uniqueList)){
						$uniqueList[] = $synObj->synonym;
						$taxonArr['syns'][$cnt]['scientificName'] = $synObj->synonym;
						if(isset($synObj->relationship)) $taxonArr['syns'][$cnt]['synreason'] = $synObj->relationship;
						$cnt++;
					}
				}
			}
			//Add vernaculars
			if($includeCommonNames && isset($eolObj->vernacularNames)){
				foreach($eolObj->vernacularNames as $vernObj){
					if(in_array($vernObj->language,$this->targetLanguages)){
						$taxonArr['verns'][] = array('language' => $vernObj->language, 'vernacularName' => $vernObj->vernacularName);
					}
				}
			}
		}
		else{
			$this->errorStr = 'ERROR opening EOL page url: '.$url;
		}
		return $taxonArr;
	}

	public function getHierarchyEntries($id, $includeSynonyms = true, $includeCommonNames = true, $includeParents = true){
		//http://eol.org/api/docs/hierarchy_entries
		//http://eol.org/api/hierarchy_entries/1.0/52595368.json?common_names=true&synonyms=true&cache_ttl=
		$taxonArr = Array();
		if($id){
			//Get taxonomy
			$url = 'http://eol.org/api/hierarchy_entries/1.0/'.$id.'.json?common_names='.($includeCommonNames?'true':'false').'&synonyms='.($includeSynonyms?'true':'false');
			if($fh = fopen($url, 'r')){
				$content = "";
				while($line = fread($fh, 1024)){
					$content .= trim($line);
				}
				fclose($fh);

				//Process return
				$eolObj = json_decode($content);
				if($eolObj->scientificName){
					$taxonArr = TaxonomyUtilities::parseScientificName($eolObj->scientificName);
					$taxonArr['scientificName'] = $eolObj->scientificName;
					$taxonArr['taxonRank'] = $eolObj->taxonRank;
					if(isset($eolObj->nameAccordingTo)) $taxonArr['source'] = $eolObj->nameAccordingTo[0];
					if(isset($eolObj->source)) $taxonArr['sourceURL'] = $eolObj->source;

					//Add synonyms
					if($includeSynonyms){
						$synonyms = $eolObj->synonyms;
						foreach($synonyms as $synObj){
							$taxonArr['syns'][] = array('scientificName' => $synObj->scientificName,'synreason' => $synObj->taxonomicStatus);
						}
					}
					//Add vernaculars
					if($includeCommonNames){
						$vernacularNames = $eolObj->vernacularNames;
						foreach($vernacularNames as $vernObj){
							if(in_array($vernObj->language,$this->targetLanguages)){
								$taxonArr['verns'][] = array('language' => $vernObj->language, 'vernacularName' => $vernObj->vernacularName);
							}
						}
					}
					//Process ancestors
					if($eolObj->ancestors && $eolObj->parentNameUsageID){
						$ancArr = array_reverse((array)$eolObj->ancestors);
						$parArr = $this->getParentArray($ancArr,$eolObj->parentNameUsageID);
						if($parArr) $taxonArr['parent'] = $parArr;
					}
					$taxonArr['id'] = $id;
				}
			}
			else{
				$this->errorStr = 'ERROR opening EOL hierarchy url: '.$url;
			}
		}
		else{
			$this->errorStr = "Input ID is null";
			return false;
		}
		return $taxonArr;
	}

	private function getParentArray($ancestors, $parentId){
		$retArr = array();
		if(!$ancestors || !$parentId) return;
		foreach($ancestors as $k => $ancObj){
			if($ancObj->taxonID == $parentId){
				$retArr['id'] = $ancObj->taxonID;
				$retArr['sciname'] = $ancObj->scientificName;
				$retArr['taxonConceptID'] = $ancObj->taxonConceptID;
				if(isset($ancObj->taxonRank)) $retArr['taxonRank'] = $ancObj->taxonRank;
				if(isset($ancObj->source)) $retArr['sourceURL'] = $ancObj->source;
				$parentId = $ancObj->parentNameUsageID;
				unset($ancestors[$k]);
				break;
			}
		}
		if($ancestors && $parentId){
			$parArr = $this->getParentArray($ancestors,$parentId);
			if($parArr) $retArr['parent'] = $parArr;
		}
		return $retArr;
	}

	public function getImages($id, $vetted = 1){
		//http://eol.org/api/docs/pages
		//http://eol.org/api/pages/1.0/1061751.json?images=2&videos=0&sounds=0&maps=0&text=2&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=true&vetted=0&cache_ttl=
		//http://eol.org/api/pages/1.0/1061761.json?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=false&vetted=1&cache_ttl=
		$retArr = array();
		if(!is_numeric($vetted)) $vetted = 1;
		$url = 'http://eol.org/api/pages/1.0/'.$id.'.json?images=15&vetted='.$vetted.'&details=1 ';
		//echo $url;
		if($fh = fopen($url, 'r')){
			$content = '';
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$eolObj = json_decode($content, true);
			if($eolObj->dataObjects){
				$imgCnt = 0;
				foreach($eolObj->dataObjects as $objArr){
					if(array_key_exists('mimeType',$objArr) && $objArr['mimeType'] == 'image/jpeg'){
						$imageUrl = '';
						if(array_key_exists('mediaURL',$objArr)){
							$imageUrl = $objArr['mediaURL'];
						}
						elseif(isset($objArr['eolMediaURL'])){
							$imageUrl = $objArr['eolMediaURL'];
						}
						//if(array_key_exists('eolThumbnailURL',$objArr)) $resourceArr['urltn'] = $objArr['eolThumbnailURL'];

						if(array_key_exists('agents',$objArr)){
							$agentArr = array();
							$agentCnt = 0;
							foreach($objArr['agents'] as $agentObj){
								if($agentObj['full_name']){
									if($agentCnt < 2) $agentArr[] = $agentObj['full_name'];
									if($agentObj['role'] == 'photographer'){
										$retArr['photographer'] = $agentObj['full_name'];
										unset($agentArr);
										break;
									}
									$agentCnt++;
								}
							}
							if(isset($agentArr) && $agentArr) $retArr['photographer'] = implode('; ',array_unique($agentArr));
						}
						$noteStr = 'Harvest via EOL on '.date('Y-m-d');
						if(array_key_exists('description',$objArr)) $noteStr .= '; '.$objArr['description'];
						$retArr['notes'] = $noteStr;
						if(array_key_exists('title',$objArr)) $retArr['title'] = $objArr['title'];
						if(array_key_exists('rights',$objArr)) $retArr['copyright'] = $objArr['rights'];
						if(array_key_exists('rightsHolder',$objArr)) $retArr['owner'] = $objArr['rightsHolder'];
						if(array_key_exists('license',$objArr)) $retArr['rights'] = $objArr['license'];
						if(array_key_exists('source',$objArr)) $retArr['source'] = $objArr['source'];
						$locStr = '';
						if(array_key_exists('location',$objArr)) $locStr = $objArr['location'];
						if(array_key_exists('latitude',$objArr) && array_key_exists('longitude',$objArr)){
							$locStr .= ' ('.$objArr['latitude'].', '.$objArr['longitude'].')';
						}
						$retArr['locality'] = $locStr;
					}
				}
			}
			else{
				$this->errorStr = 'ERROR return EOL page detials';
				$retArr = false;
			}
		}
		else{
			$this->errorStr = 'ERROR opening EOL page url: '.$url;
			$retArr = false;
		}
		return $retArr;
	}

	//Setters and getters
	public function setTargetLanguages($langStr){
		$this->targetLanguages = explode(',',$langStr);
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	//Misc functions
	private function encodeString($inStr){
 		$retStr = $inStr;
 		if($inStr){
 			$retStr = trim($inStr);
 			$retStr = mb_convert_encoding($retStr, $GLOBALS['CHARSET'], mb_detect_encoding($retStr));
 		}
		return $retStr;
	}
}
?>