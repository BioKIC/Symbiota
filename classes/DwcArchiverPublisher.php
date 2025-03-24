<?php
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

class DwcArchiverPublisher extends DwcArchiverCore{

	public function __construct(){
		parent::__construct('write');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function resetCollArr($id){
		unset($this->collArr);
		$this->collArr = array();
		$this->setCollArr($id);
		$this->conditionArr['collid'] = $id;
		$this->conditionSql = '';
	}

	public function verifyCollRecords($collId){
		$recArr = array();

		//Get NULL basisOfRecord
		$sql = 'SELECT COUNT(*) as cnt FROM omoccurrences WHERE basisofrecord IS NULL AND collid = '.$collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$recArr['nullBasisRec'] = $r->cnt;
		}
		$rs->free();

		//Get NULL GUID counts
		$guidTarget = ($this->collArr?$this->collArr[$collId]['guidtarget']:'');
		if($guidTarget == 'symbiotaUUID') $guidTarget = 'recordID';
		if($guidTarget){
			$sql = 'SELECT COUNT(occid) AS cnt FROM omoccurrences WHERE '.$guidTarget.' IS NULL AND collid = '.$collId;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$recArr['nullGUIDs'] = $r->cnt;
			}
			$rs->free();
		}

		return $recArr;
	}

	public function batchCreateDwca($collIdArr){
		$status = false;
		$this->logOrEcho("Starting batch process (".date('Y-m-d h:i:s A').")\n");
		$this->logOrEcho("\n-----------------------------------------------------\n\n");

		$successArr = array();
		$includeAttributes = $this->includeAttributes;
		$includeMatSample = $this->includeMaterialSample;
		$includeIdentifiers = $this->includeIdentifiers;
		$includeAssociations = $this->includeAssociations;
		foreach($collIdArr as $id){
			//Create a separate DWCA object for each collection
			if($includeAttributes){
				if($this->hasAttributes($id)) $this->includeAttributes = true;
				else $this->includeAttributes = false;
			}
			if($includeMatSample){
				if($this->hasMaterialSamples($id)) $this->includeMaterialSample = true;
				else $this->includeMaterialSample = false;
			}
			if($includeIdentifiers){
				if($this->hasIdentifiers($id)) $this->includeIdentifiers = true;
				else $this->includeIdentifiers = false;
			}
			if($includeAssociations){
				if($this->hasAssociations($id)) $this->includeAssociations = true;
				else $this->includeAssociations = false;
			}
			$this->resetCollArr($id);
			$this->conditionArr['collid'] = $id;
			$this->conditionSql = '';
			if($this->createDwcArchive()){
				$successArr[] = $id;
				$status = true;
			}
		}
		$this->includeAttributes = $includeAttributes;
		$this->includeMaterialSample = $includeMatSample;
		$this->includeIdentifiers = $includeIdentifiers;
		$this->includeAssociations = $includeAssociations;
		//Reset $this->collArr with all the collections ran successfully and then rebuild the RSS feed
		$this->resetCollArr(implode(',',$successArr));
		$this->writeRssFile();
		$this->logOrEcho("Batch process finished! (".date('Y-m-d h:i:s A').") \n");
		return $status;
	}

	public function writeRssFile(){

		$this->logOrEcho('Mapping data to RSS feed... ');

		//Create new document and write out to target
		$newDoc = new DOMDocument('1.0',$this->charSetOut);

		//Add root element
		$rootElem = $newDoc->createElement('rss');
		$rootAttr = $newDoc->createAttribute('version');
		$rootAttr->value = '2.0';
		$rootElem->appendChild($rootAttr);
		$newDoc->appendChild($rootElem);

		//Add Channel
		$channelElem = $newDoc->createElement('channel');
		$rootElem->appendChild($channelElem);

		//Add title, link, description, language
		$titleElem = $newDoc->createElement('title');
		$titleElem->appendChild($newDoc->createTextNode($GLOBALS['DEFAULT_TITLE'].' Darwin Core Archive rss feed'));
		$channelElem->appendChild($titleElem);

		$this->setServerDomain();
		$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');

		$linkElem = $newDoc->createElement('link');
		$linkElem->appendChild($newDoc->createTextNode($urlPathPrefix));
		$channelElem->appendChild($linkElem);
		$descriptionElem = $newDoc->createElement('description');
		$descriptionElem->appendChild($newDoc->createTextNode($GLOBALS['DEFAULT_TITLE'].' Darwin Core Archive rss feed'));
		$channelElem->appendChild($descriptionElem);
		$languageElem = $newDoc->createElement('language','en-us');
		$channelElem->appendChild($languageElem);

		//Create new item for target archives and load into array
		$itemArr = array();
		foreach($this->collArr as $collID => $cArr){
			$this->encodeArr($cArr);
			$itemElem = $newDoc->createElement('item');
			$itemAttr = $newDoc->createAttribute('collid');
			$itemAttr->value = $collID;
			$itemElem->appendChild($itemAttr);
			//Add title
			$instCode = $cArr['instcode'];
			if($cArr['collcode']) $instCode .= '-'.$cArr['collcode'];
			$title = $instCode.' DwC-Archive';
			$itemTitleElem = $newDoc->createElement('title');
			$itemTitleElem->appendChild($newDoc->createTextNode($title));
			$itemElem->appendChild($itemTitleElem);
			//Icon
			$imgLink = '';
			if(substr($cArr['icon'],0,17) == 'images/collicons/'){
				//Link is a
				$imgLink = $urlPathPrefix.$cArr['icon'];
			}
			elseif(substr($cArr['icon'],0,1) == '/') $imgLink = $this->serverDomain.$cArr['icon'];
			else $imgLink = $cArr['icon'];
			$iconElem = $newDoc->createElement('image');
			$iconElem->appendChild($newDoc->createTextNode($imgLink));
			$itemElem->appendChild($iconElem);

			//description
			$descTitleElem = $newDoc->createElement('description');
			$descTitleElem->appendChild($newDoc->createTextNode('Darwin Core Archive for '.$cArr['collname']));
			$itemElem->appendChild($descTitleElem);
			//GUIDs
			$guidElem = $newDoc->createElement('guid');
			$guidElem->appendChild($newDoc->createTextNode($urlPathPrefix.'collections/misc/collprofiles.php?collid='.$collID));
			$itemElem->appendChild($guidElem);
			$guidElem2 = $newDoc->createElement('guid');
			$guidElem2->appendChild($newDoc->createTextNode($cArr['collectionguid']));
			$itemElem->appendChild($guidElem2);
			//EML file link
			$fileNameSeed = str_replace(array(' ','"',"'"),'',$instCode).'_DwC-A';

			$emlElem = $newDoc->createElement('emllink');
			$emlElem->appendChild($newDoc->createTextNode($urlPathPrefix.'content/dwca/'.$fileNameSeed.'.eml'));
			$itemElem->appendChild($emlElem);
			//type
			$typeTitleElem = $newDoc->createElement('type','DWCA');
			$itemElem->appendChild($typeTitleElem);
			//recordType
			$recTypeTitleElem = $newDoc->createElement('recordType','DWCA');
			$itemElem->appendChild($recTypeTitleElem);
			//link
			$archivePath = $urlPathPrefix.'content/dwca/'.$fileNameSeed.'.zip';
			$linkTitleElem = $newDoc->createElement('link');
			$linkTitleElem->appendChild($newDoc->createTextNode($archivePath));
			$itemElem->appendChild($linkTitleElem);
			//pubDate
			//$dsStat = stat($this->targetPath.$instCode.'_DwC-A.zip');
			$pubDateTitleElem = $newDoc->createElement('pubDate');
			$pubDateTitleElem->appendChild($newDoc->createTextNode(date("D, d M Y H:i:s")));
			$itemElem->appendChild($pubDateTitleElem);
			$itemArr[$title] = $itemElem;

			//Add path to database
			$sql = 'UPDATE omcollections SET dwcaUrl = "'.$archivePath.'" WHERE collid = '.$collID;
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR updating dwcaUrl while adding new DWCA instance: '.$this->conn->error);
			}
		}

		//Add existing items
		$sourcePath = $GLOBALS['SERVER_ROOT'].'/content/dwca/rss.xml';
		$targetPath = $sourcePath;
		$deprecatedPath = $GLOBALS['SERVER_ROOT'].'/webservices/dwc/rss.xml';
		if(!file_exists($sourcePath) && file_exists($deprecatedPath)) $sourcePath = $deprecatedPath;
		if(file_exists($sourcePath)){
			//Get other existing DWCAs by reading and parsing current rss.xml
			$oldDoc = new DOMDocument();
			$oldDoc->load($sourcePath);
			$items = $oldDoc->getElementsByTagName('item');
			foreach($items as $i){
				//Filter out item for active collection
				$t = $i->getElementsByTagName("title")->item(0)->nodeValue;
				if(!array_key_exists($i->getAttribute('collid'),$this->collArr)) $itemArr[$t] = $newDoc->importNode($i,true);
			}
		}

		//Sort and add items to channel
		ksort($itemArr);
		foreach($itemArr as $i){
			$channelElem->appendChild($i);
		}
		$newDoc->save($targetPath);

		if($sourcePath == $deprecatedPath || !file_exists($deprecatedPath)){
			$redirectDoc = new DOMDocument();
			$redirectDoc->loadXML('<redirect><newLocation>' . GeneralUtil::getDomain() . $GLOBALS['CLIENT_ROOT'] . '/content/dwca/rss.xml</newLocation></redirect>');
			$redirectDoc->save($deprecatedPath);
		}

		$this->logOrEcho('Done!', 1);
		$this->logOrEcho('-----------------------------------------------------');
	}

	//Misc data retrival functions
	public function getDwcaItems($collid = 0){
		$retArr = Array();
		$rssFile = $GLOBALS['SERVER_ROOT'].'/content/dwca/rss.xml';
		if(file_exists($rssFile)){
			$xmlDoc = new DOMDocument();
			$xmlDoc->load($rssFile);
			$items = $xmlDoc->getElementsByTagName('item');
			$cnt = 0;
			foreach($items as $i ){
				$id = $i->getAttribute("collid");
				if(!$collid || $collid == $id){
					$titles = $i->getElementsByTagName("title");
					$retArr[$cnt]['title'] = $titles->item(0)->nodeValue;
					$descriptions = $i->getElementsByTagName("description");
					$retArr[$cnt]['description'] = $descriptions->item(0)->nodeValue;
					$types = $i->getElementsByTagName("type");
					$retArr[$cnt]['type'] = $types->item(0)->nodeValue;
					$recordTypes = $i->getElementsByTagName("recordType");
					$retArr[$cnt]['recordType'] = $recordTypes->item(0)->nodeValue;
					$links = $i->getElementsByTagName("link");
					$retArr[$cnt]['link'] = $links->item(0)->nodeValue;
					$pubDates = $i->getElementsByTagName("pubDate");
					$retArr[$cnt]['pubDate'] = $pubDates->item(0)->nodeValue;
					$retArr[$cnt]['collid'] = $id;
					$cnt++;
				}
			}
		}
		$this->aasort($retArr, 'description');
		return $retArr;
	}

	public function getCollectionList($catID){
		$retArr = array();
		$serverName = GeneralUtil::getDomain();
		$sql = 'SELECT c.collid, c.collectionname, CONCAT_WS("-",c.institutioncode,c.collectioncode) as instcode, c.guidtarget, c.dwcaurl, c.managementtype, c.dynamicProperties '.
			'FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid '.
			'LEFT JOIN omcollcatlink l ON c.collid = l.collid '.
			'WHERE (c.colltype = "Preserved Specimens") AND (s.recordcnt > 0) ';
		if($catID && preg_match('/^[,\d]+$/', $catID)) $sql .= 'AND (l.ccpk IN('.$catID.')) ';
		$sql .= 'ORDER BY c.collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['name'] = $r->collectionname.' ('.$r->instcode.')';
			$retArr[$r->collid]['guid'] = $r->guidtarget;
			$url = $r->dwcaurl;
			if($url) $url = substr($url,0,strpos($url,'/content')).'/collections/datasets/datapublisher.php';
			$retArr[$r->collid]['url'] = $url;
			if(!$r->guidtarget) $retArr[$r->collid]['err'] = 'MISSING_GUID';
			elseif($r->dwcaurl && !strpos($serverName, 'localhost') && strpos($r->dwcaurl, str_replace('www.', '', $serverName)) === false) $retArr[$r->collid]['err'] = 'ALREADY_PUB_DOMAIN';
		}
		$rs->free();
		return $retArr;
	}

	public function getAdditionalDWCA($catID){
		$retArr = array();
		if($catID && preg_match('/^[,\d]+$/', $catID)){
			$sql = 'SELECT substring_index(c.dwcaurl,"/content/",1)  as portalDomain, count(c.collid) as cnt '.
				'FROM omcollections c LEFT JOIN omcollcatlink l ON c.collid = l.collid '.
				'WHERE (c.colltype = "Preserved Specimens") AND (c.dwcaurl IS NOT NULL) AND (l.ccpk IS NULL OR l.ccpk NOT IN('.$catID.')) '.
				'GROUP BY portalDomain';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$domainName = parse_url($r->portalDomain, PHP_URL_HOST);
				if(substr($domainName,0,4) == 'www.') $domainName = substr($domainName,4);
				$domainName = parse_url($r->portalDomain, PHP_URL_SCHEME).'://'.$domainName;
				if(isset($retArr[$domainName])){
					$retArr[$domainName]['cnt'] += $r->cnt;
					if(strpos($retArr[$domainName]['url'],'/www.') && !strpos($r->portalDomain,'/www.')) $retArr[$domainName]['url'] = $r->portalDomain;
				}
				else{
					$retArr[$domainName]['cnt'] = $r->cnt;
					$retArr[$domainName]['url'] = $r->portalDomain;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	//Mics functions
	private function aasort(&$array, $key){
		$sorter = array();
		$ret = array();
		reset($array);
		foreach ($array as $ii => $va) {
			$sorter[$ii] = $va[$key];
		}
		asort($sorter);
		foreach ($sorter as $ii => $va) {
			$ret[$ii] = $array[$ii];
		}
		$array = $ret;
	}

	public function humanFileSize($filePath) {
		$x = false;
		if(substr($filePath,0,4)=='http') {
			if($headerArr = @get_headers($filePath, 1)){
				$x = array_change_key_case($headerArr, CASE_LOWER);
				if( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) {
					$x = $x['content-length'][1];
				}
				else {
					$x = $x['content-length'];
				}
			}
		}
		else {
			$x = @filesize($filePath);
		}
		if($x !== false){
			$x = round($x/1000000, 1);
			if(!$x) $x = 0.1;
			return $x.'M';
		}
		return '?M';
	}
}
?>