<?php

namespace App\Http\Controllers;

use App\Models\Occurrence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OccurrenceBaseController extends Controller{

	protected $useThesaurus;
	protected $conditions = [];
	protected $limit;
	protected $offset;

	public function __construct(){
	}

	public function setRequestVariables(Request $request){
		$this->validate($request, [
			'family' => 'alpha',
			'sciname' => 'alpha',
			'modifiedFromDate' => 'date',
			'modifiedToDate' => 'date',
			'useThesaurus' => ['integer', 'max:1'],
			'limit' => ['integer', 'max:500'],
			'offset' => 'integer'
		]);
		$this->useThesaurus = $request->input('useThesaurus',0);

		//Omoccurrence fields
		if($request->has('collid')) $this->conditions[] = ['collid', $request->collid];
		if($request->has('datasetID')) $this->conditions[] = ['datasetID', $request->datasetID];
		if($request->has('catalogNumber')) $this->conditions[] = ['catalogNumber', $request->catalogNumber];
		if($request->has('occurrenceID')) $this->conditions[] = ['occurrenceID', $request->occurrenceID];
		if($request->has('country')) $this->conditions[] = ['country', $request->country];
		if($request->has('stateProvince')) $this->conditions[] = ['stateProvince', $request->stateProvince];
		if($request->has('county')) $this->conditions[] = ['county', 'LIKE', $request->county.'%'];
		if($request->has('family')) $this->conditions[] = ['family', $request->family];
		if($request->has('sciname')) $this->conditions[] = ['sciname', 'LIKE', $request->sciname.'%'];
		if($request->has('eventDate')) $this->conditions[] = ['eventDate', 'LIKE', $request->eventDate.'%'];
		if($request->has('modifiedFromDate')) $this->conditions[] = ['dateLastModified', '>', $request->modifiedFromDate];
		if($request->has('modifiedToDate')) $this->conditions[] = ['dateLastModified', '<', $request->modifiedToDate];

		//Taxonomy fields

		//Other relationships



		elseif(array_key_exists('db',$_REQUEST) && $_REQUEST['db']){
			$dbStr = $this->cleanInputStr(OccurrenceSearchSupport::getDbRequestVariable($_REQUEST));
			if(preg_match('/^[0-9,;]+$/', $dbStr)) $this->searchTermArr['db'] = $dbStr;
		}
		if(array_key_exists('datasetid',$_REQUEST) && $_REQUEST['datasetid']){
			if(is_array($_REQUEST['datasetid'])){
				$dsStr = implode(',',$_REQUEST['datasetid']);
				if(preg_match('/^[\d,]+$/',$dsStr)) $this->searchTermArr['datasetid'] = $dsStr;
			}
			elseif(preg_match('/^[\d,]+$/',$_REQUEST['datasetid'])) $this->searchTermArr['datasetid'] = $_REQUEST['datasetid'];
		}
		if(array_key_exists('taxa',$_REQUEST) && $_REQUEST['taxa']){
			$this->setTaxonRequestVariable();
		}
		if(array_key_exists('country',$_REQUEST)){
			$country = $this->cleanInputStr($_REQUEST['country']);
			if($country){
				$str = str_replace(',',';',$country);
				if(stripos($str, 'USA') !== false || stripos($str, 'United States') !== false || stripos($str, 'U.S.A.') !== false || stripos($str, 'United States of America') !== false){
					if(stripos($str, 'USA') === false){
						$str .= ';USA';
					}
					if(stripos($str, 'United States') === false){
						$str .= ';United States';
					}
					if(stripos($str, 'U.S.A.') === false){
						$str .= ';U.S.A.';
					}
					if(stripos($str, 'United States of America') === false){
						$str .= ';United States of America';
					}
				}
				$this->searchTermArr['country'] = $str;
			}
			else unset($this->searchTermArr['country']);
		}
		if(array_key_exists('state',$_REQUEST)){
			$state = $this->cleanInputStr($_REQUEST['state']);
			if($state){
				if(strlen($state) == 2 && (!isset($this->searchTermArr['country']) || stripos($this->searchTermArr['country'],'USA') !== false)){
					$sql = 'SELECT s.statename, c.countryname '.
							'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
							'WHERE c.countryname IN("USA","United States") AND (s.abbrev = "'.$state.'")';
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$state = $r->statename;
					}
					$rs->free();
				}
				$str = str_replace(',',';',$state);
				$this->searchTermArr['state'] = $str;
			}
			else{
				unset($this->searchTermArr['state']);
			}
		}
		if(array_key_exists('county',$_REQUEST)){
			$county = $this->cleanInputStr($_REQUEST['county']);
			$county = str_ireplace(' Co.','',$county);
			$county = str_ireplace(' County','',$county);
			if($county){
				$str = str_replace(',',';',$county);
				$this->searchTermArr['county'] = $str;
			}
			else{
				unset($this->searchTermArr['county']);
			}
		}
		if(array_key_exists('local',$_REQUEST)){
			$local = $this->cleanInputStr($_REQUEST['local']);
			if($local){
				$str = str_replace(',',';',$local);
				$this->searchTermArr['local'] = $str;
			}
			else{
				unset($this->searchTermArr['local']);
			}
		}
		if(array_key_exists('elevlow',$_REQUEST)){
			$elevLow = filter_var(trim($_REQUEST['elevlow']), FILTER_SANITIZE_NUMBER_INT);
			if(is_numeric($elevLow)) $this->searchTermArr['elevlow'] = $elevLow;
			else unset($this->searchTermArr['elevlow']);
		}
		if(array_key_exists('elevhigh',$_REQUEST)){
			$elevHigh = filter_var(trim($_REQUEST['elevhigh']), FILTER_SANITIZE_NUMBER_INT);
			if(is_numeric($elevHigh)) $this->searchTermArr['elevhigh'] = $elevHigh;
			else unset($this->searchTermArr['elevhigh']);
		}
		if(array_key_exists('collector',$_REQUEST)){
			$collector = $this->cleanInputStr($_REQUEST['collector']);
			$collector = str_replace('%', '', $collector);
			if($collector){
				$str = str_replace(',',';',$collector);
				$this->searchTermArr['collector'] = $str;
			}
			else{
				unset($this->searchTermArr['collector']);
			}
		}
		if(array_key_exists('collnum',$_REQUEST)){
			$collNum = $this->cleanInputStr($_REQUEST['collnum']);
			if($collNum){
				$str = str_replace(',',';',$collNum);
				$this->searchTermArr['collnum'] = $str;
			}
			else{
				unset($this->searchTermArr['collnum']);
			}
		}
		if(array_key_exists('eventdate1',$_REQUEST)){
			if($eventDate = $this->cleanInputStr($_REQUEST['eventdate1'])){
				$this->searchTermArr['eventdate1'] = $eventDate;
				if(array_key_exists('eventdate2',$_REQUEST)){
					if($eventDate2 = $this->cleanInputStr($_REQUEST['eventdate2'])){
						if($eventDate2 != $eventDate){
							$this->searchTermArr['eventdate2'] = $eventDate2;
						}
					}
					else{
						unset($this->searchTermArr['eventdate2']);
					}
				}
			}
			else{
				unset($this->searchTermArr['eventdate1']);
			}
		}
		if(array_key_exists('catnum',$_REQUEST)){
			$catNum = $this->cleanInputStr(str_replace(',', ';', $_REQUEST['catnum']));
			if($catNum){
				$this->searchTermArr['catnum'] = $catNum;
				if(array_key_exists('includeothercatnum',$_REQUEST)) $this->searchTermArr['includeothercatnum'] = '1';
			}
			else{
				unset($this->searchTermArr['catnum']);
			}
		}
		if(array_key_exists('typestatus',$_REQUEST)){
			if($_REQUEST['typestatus']) $this->searchTermArr['typestatus'] = true;
			else unset($this->searchTermArr['typestatus']);
		}
		if(array_key_exists('hasimages',$_REQUEST)){
			if($_REQUEST['hasimages']) $this->searchTermArr['hasimages'] = true;
			else unset($this->searchTermArr['hasimages']);
		}
		if(array_key_exists('hasgenetic',$_REQUEST)){
			if($_REQUEST['hasgenetic']) $this->searchTermArr['hasgenetic'] = true;
			else unset($this->searchTermArr['hasgenetic']);
		}
		if(array_key_exists('hascoords',$_REQUEST)){
			if($_REQUEST['hascoords']) $this->searchTermArr['hascoords'] = true;
			else unset($this->searchTermArr['hascoords']);
		}
		if(array_key_exists('includecult',$_REQUEST)){
			if($_REQUEST['includecult']) $this->searchTermArr['includecult'] = true;
			else unset($this->searchTermArr['includecult']);
		}
		if(array_key_exists('attr',$_REQUEST)){
			//Occurrence trait attributed passed as stateIDs
			$stateIdStr = $_REQUEST['attr'];
			if(is_array($_REQUEST['attr'])) $stateIdStr = implode(',',array_unique($_REQUEST['attr']));
			if(preg_match('/^[0-9,]+$/', $stateIdStr)) $this->searchTermArr['attr'] = $stateIdStr;
		}
		$llPattern = '-?\d+\.{0,1}\d*';
		if(array_key_exists('upperlat',$_REQUEST)){
			$upperLat = ''; $bottomlat = ''; $leftLong = ''; $rightlong = '';
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['upperlat']), $m1)){
				$upperLat = round($m1[1],5);
				$uLatDir = (isset($_REQUEST['upperlat_NS'])?strtoupper($_REQUEST['upperlat_NS']):'');
				if(($uLatDir == 'N' && $upperLat < 0) || ($uLatDir == 'S' && $upperLat > 0)) $upperLat *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['bottomlat']), $m2)){
				$bottomlat = round($m2[1],5);
				$bLatDir = (isset($_REQUEST['bottomlat_NS'])?strtoupper($_REQUEST['bottomlat_NS']):'');
				if(($bLatDir == 'N' && $bottomlat < 0) || ($bLatDir == 'S' && $bottomlat > 0)) $bottomlat *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['leftlong']), $m3)){
				$leftLong = round($m3[1],5);
				$lLngDir = (isset($_REQUEST['leftlong_EW'])?strtoupper($_REQUEST['leftlong_EW']):'');
				if(($lLngDir == 'E' && $leftLong < 0) || ($lLngDir == 'W' && $leftLong > 0)) $leftLong *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['rightlong']), $m4)){
				$rightlong = round($m4[1],5);
				$rLngDir = (isset($_REQUEST['rightlong_EW'])?strtoupper($_REQUEST['rightlong_EW']):'');
				if(($rLngDir == 'E' && $rightlong < 0) || ($rLngDir == 'W' && $rightlong > 0)) $rightlong *= -1;
			}

			if(is_numeric($upperLat) && is_numeric($bottomlat) && is_numeric($leftLong) && is_numeric($rightlong)){
				$latLongStr = $upperLat.';'.$bottomlat.';'.$leftLong.';'.$rightlong;
				$this->searchTermArr['llbound'] = $latLongStr;
			}
			else{
				unset($this->searchTermArr['llbound']);
			}
		}
		if(array_key_exists('llbound',$_REQUEST) && $_REQUEST['llbound']){
			$this->searchTermArr['llbound'] = $this->cleanInputStr($_REQUEST['llbound']);
		}
		if(array_key_exists('pointlat',$_REQUEST)){
			$pointLat = '';
			$pointLong = '';
			$radius = '';
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['pointlat']), $m1)){
				$pointLat = $m1[1];
				if(isset($_REQUEST['pointlat_NS'])){
					if($_REQUEST['pointlat_NS'] == 'S' && $pointLat > 0) $pointLat *= -1;
					elseif($_REQUEST['pointlat_NS'] == 'N' && $pointLat < 0) $pointLat *= -1;
				}
			}
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['pointlong']), $m2)){
				$pointLong = $m2[1];
				if(isset($_REQUEST['pointlong_EW'])){
					if($_REQUEST['pointlong_EW'] == 'W' && $pointLong > 0) $pointLong *= -1;
					elseif($_REQUEST['pointlong_EW'] == 'E' && $pointLong < 0) $pointLong *= -1;
				}
			}
			if(preg_match('/(\d+)/', $_REQUEST['radius'], $m3)){
				$radius = $m3[1];
			}
			if($pointLat && $pointLong && is_numeric($radius)){
				$radiusUnits = (isset($_REQUEST['radiusunits'])?$this->cleanInputStr($_REQUEST['radiusunits']):'mi');
				$pointRadiusStr = $pointLat.';'.$pointLong.';'.$radius.';'.$radiusUnits;
				$this->searchTermArr['llpoint'] = $pointRadiusStr;
			}
			else{
				unset($this->searchTermArr['llpoint']);
			}
		}
		if(array_key_exists('llpoint',$_REQUEST) && $_REQUEST['llpoint']){
			$this->searchTermArr['llpoint'] = $this->cleanInputStr($_REQUEST['llpoint']);
		}
		if(array_key_exists('footprintwkt',$_REQUEST) && $_REQUEST['footprintwkt']){
			$this->searchTermArr['footprintwkt'] = $this->cleanInputStr($_REQUEST['footprintwkt']);
		}


		if(array_key_exists('searchvar',$_REQUEST)){
			$parsedArr = array();
			$taxaArr = array();
			parse_str($this->cleanInputStr($_REQUEST['searchvar']), $parsedArr);
			if(isset($parsedArr['taxa'])){
				$taxaArr['taxa'] = $parsedArr['taxa'];
				unset($parsedArr['taxa']);
				if(isset($parsedArr['usethes']) && is_numeric($parsedArr['usethes'])){
					$taxaArr['usethes'] = $parsedArr['usethes'];
					unset($parsedArr['usethes']);
				}
				if(isset($parsedArr['taxontype']) && is_numeric($parsedArr['taxontype'])){
					$taxaArr['taxontype'] = $parsedArr['taxontype'];
					unset($parsedArr['taxontype']);
				}
				$this->setTaxonRequestVariable($taxaArr);
			}
			if($parsedArr) $this->searchTermArr = $parsedArr;
		}
		//Search will be confinded to a clid vouchers, collid, catid, or will remain open to all collection
		if(array_key_exists('targetclid',$_REQUEST) && is_numeric($_REQUEST['targetclid'])){
			$this->searchTermArr['targetclid'] = $_REQUEST['targetclid'];
			$this->setChecklistVariables($_REQUEST['targetclid']);
		}




		$this->limit = $request->input('limit',100);
		$this->offset = $request->input('offset',0);
	}

	//Helper functions
	protected function getOccid($id){
		if(!is_numeric($id)){
			$occid = Occurrence::where('occurrenceID', $id)->value('occid');
			if(!$occid) $occid = DB::table('guidoccurrences')->where('guid', $id)->value('occid');
			if(is_numeric($occid)) $id = $occid;
		}
		return $id;
	}

	protected function getAPIResponce($url, $asyc = false){
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
}
