<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once('OccurrenceAccessStats.php');

class OccurrenceDownload{

	private $conn;
	private $redactLocalities = true;
	private $rareReaderArr = array();
	private $securityArr = array();
	private $schemaType = 'symbiota';			//symbiota,dwc,georef,checklist
	private $extended = 0;
	private $delimiter = ',';
	private $charSetSource = '';
	private $charSetOut = '';
	private $zipFile = false;
	private $sqlWhere = '';
	private $conditionArr = array();
	private $taxonFilter;
	private $isPublicDownload = false;
	private $errorArr = array();

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');

		//Set rare species variables
		$this->securityArr = Array('locality','locationRemarks','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
			'decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','footprintWKT','verbatimCoordinates',
			'georeferenceRemarks','georeferencedBy','georeferenceProtocol','georeferenceSources','georeferenceVerificationStatus','habitat');
		if($GLOBALS['IS_ADMIN'] || array_key_exists('CollAdmin', $GLOBALS['USER_RIGHTS']) || array_key_exists('RareSppAdmin', $GLOBALS['USER_RIGHTS']) || array_key_exists('RareSppReadAll', $GLOBALS['USER_RIGHTS'])){
			$this->redactLocalities = false;
		}
		if(array_key_exists('CollEditor', $GLOBALS['USER_RIGHTS'])){
			$this->rareReaderArr = $GLOBALS['USER_RIGHTS']['CollEditor'];
		}
		if(array_key_exists('RareSppReader', $GLOBALS['USER_RIGHTS'])){
			$this->rareReaderArr = array_unique(array_merge($this->rareReaderArr,$GLOBALS['USER_RIGHTS']['RareSppReader']));
		}

		//Character set
		$this->charSetSource = strtoupper($GLOBALS['CHARSET']);
		$this->charSetOut = $this->charSetSource;
	}

	public function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
		//if($this->zipArchive) $this->zipArchive->close();
	}

	public function downloadData(){
		$contentDesc = '';
		$filePath = $this->getOutputFilePath();
		$fileName = $this->getOutputFileName();
		if($this->schemaType == 'checklist'){
			$contentDesc = 'Symbiota Checklist File';
		}
		elseif($this->schemaType == 'georef'){
			$contentDesc = 'Symbiota Occurrence Georeference Data';
		}
		if($this->zipFile){
			//Create zip file, load data, then stream to user
			$zipArchive = null;
			if(class_exists('ZipArchive')){
				$zipArchive = new ZipArchive;
				$zipArchive->open($filePath.$fileName, ZipArchive::CREATE);
			}
			else{
				$this->errorArr[] = 'ERROR: Zip File creation not supported, see portal manager';
				$contentDesc = 'Output file ERROR: Zip File creation not supported';
			}
			if($zipArchive){
				$tempName = '';
				if($this->schemaType == 'checklist'){
					$tempName = 'checklist';
				}
				elseif($this->schemaType == 'georef'){
					$tempName = 'georef';
				}
				else{
					$tempName = 'occurrence';
				}
				$tempPath = $filePath.$tempName.'_'.time();
				if($this->delimiter=="\t"){
					$tempPath .= '.tab';
					$tempName .= '.tab';
				}
				elseif($this->delimiter==','){
					$tempPath .= '.csv';
					$tempName .= '.csv';
				}
				else{
					$tempPath .= '.txt';
					$tempName .= '.txt';
				}
				$fh = fopen($tempPath, "w");
				$this->writeOutData($fh);
				fclose($fh);
				if(file_exists($tempPath)){
					$zipArchive->addFile($tempPath,$tempName);
					$zipArchive->close();
					unlink($tempPath);
				}
			}
		}
		else{
			//Create regular file and then streamed out to user
			$fh = fopen($filePath.$fileName, "w");
			$this->writeOutData($fh);
			fclose($fh);
		}
		//Send data file out
		header('Content-Description: '.$contentDesc);
		header('Content-Type: '.$this->getContentType());
		header('Content-Disposition: attachment; filename='.$fileName);
		header('Content-Transfer-Encoding: '.$this->charSetOut);
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: '.filesize($filePath.$fileName));
		ob_clean();
		flush();
		readfile($filePath.$fileName);
		//Clean up
		if(file_exists($filePath.$fileName)) unlink($filePath.$fileName);
	}

	private function writeOutData($outstream){
		$recCnt = 0;
		if($outstream){
			$sql = $this->getSql();
			$result = $this->conn->query($sql,MYSQLI_USE_RESULT);
			if($result){
				$statsManager = new OccurrenceAccessStats();
				$occurAccessID = $statsManager->insertAccessEvent('download', substr($sql, strpos($sql, 'WHERE ')));
				$outputHeader = true;
				while($row = $result->fetch_assoc()){
					if($outputHeader){
						//Write column names out to file
						if($this->delimiter == ","){
							fputcsv($outstream, array_keys($row));
						}
						else{
							fwrite($outstream, implode($this->delimiter, array_keys($row))."\n");
						}
						$outputHeader = false;
					}
					//$this->stripSensitiveFields($row);
					$this->encodeArr($row);
					if($this->delimiter == ","){
						fputcsv($outstream, $row);
					}
					else{
						fwrite($outstream, implode($this->delimiter,$row)."\n");
					}
					//Set access statistics
					if($this->isPublicDownload){
						if($this->schemaType != 'checklist') if(array_key_exists('occid',$row)) $statsManager->insertAccessOccurrence($occurAccessID, $row['occid']);
					}
					$recCnt++;
				}
			}
			else{
				echo "Recordset is empty.\n";
			}
			if($result) $result->close();
		}
		return $recCnt;
	}
	/*
	 private function writeXmlFile($sql){
	 //$this->downloadPath .= ".xml";
	 //$this->downloadUrl .= ".xml";

	 header("Content-Type: text/html/force-download");
	 header("Content-Disposition: attachment; filename='symbiotadownload".time().".xml'");
	 $out = new XMLWriter();
	 $out->openURI('php://output');
	 $out->xmlwriter_start_document("1.0","ISO-8859-1");



	 <?xml version="1.0" encoding="UTF-8"?>
	 $xw->startElementNS('ns0', 'approvePOrderResponse',
	 'http://PhpRESTAppLib/POrderApprovalHtIF');
	 <SimpleDarwinRecordSet
	 xmlns="http://rs.tdwg.org/dwc/dwcrecord/"
	 xmlns:dc="http://purl.org/dc/terms/"
	 xmlns:dwc="http://rs.tdwg.org/dwc/terms/"
	 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	 xsi:schemaLocation="http://rs.tdwg.org/dwc/dwcrecord/ http://rs.tdwg.org/dwc/xsd/tdwg_dwc_simple.xsd">
	 <SimpleDarwinRecord>
	 <dc:type>Taxon</dc:type>

	 xmlwriter_start_attribute($xml_resource , 'access_year');
	 xmlwriter_write_attribute($xml_resource, 'access_year' , '2008');
	 xmlwriter_end_attribute($xml_resource);

	 StreamResult sr = new StreamResult(this.downloadPath);
	 SAXTransformerFactory tf = (SAXTransformerFactory)SAXTransformerFactory.newInstance();
	 TransformerHandler th = tf.newTransformerHandler();
	 Transformer t = th.getTransformer();
	 t.setOutputProperty(OutputKeys.ENCODING,"ISO-8859-1");
	 th.setResult(sr);
	 th.startDocument();
	 AttributesImpl ai = new AttributesImpl();
	 ai.clear();
	 th.startElement("","","Document",ai);
	 th.startElement("","","Specimens",ai);
	 Statement st = con.createStatement();
	 rs = st.executeQuery(sql);
	 ResultSetMetaData rsmd = rs.getMetaData();
	 int columnCnt = rsmd.getColumnCount();
	 while(rs.next()){
	 ai.clear();
	 th.startElement("","","SpecimenRecord",ai);
	 for(int x = 1;x <= columnCnt;++x){
	 String columnName = rsmd.getColumnName(x);
	 if(this.isAdmin || rs.getInt("LocalitySecurity") == 1 || !this.securityColumns.contains(columnName) || (this.userRights != null && this.userRights.contains(rs.getString("CollectionCode")))){
	 String outStr = rs.getString(x);
	 if(outStr != null && !outStr.equals("")){
	 char[] charArr = outStr.toCharArray();
	 ai.clear();
	 th.startElement("","",columnName,ai);
	 th.characters(charArr,0,charArr.length);
	 th.endElement("","",columnName);
	 }
	 }
	 }
	 th.endElement("","","SpecimenRecord");
	 }
	 th.endElement("","","Specimens");
	 th.endElement("","","Document");
	 th.endDocument();
	 st.close();
	 rs.close();
	 }
	 catch(SQLException sqle){
	 System.out.println("DownloadCollections: writeXmlFile: sqle = " + sqle);
	 System.out.println("SQL: " + sql);
	 }
	 catch(Exception e){
	 System.out.println("DownloadCollections: writeXmlFile: e = " + e);
	 }
	 this.closeConnection();
	 $result->close();
	 }*/

	//Return latest activity
	public function getDataEntryActivity($format='rss',$days=0, $limit=0){
		//format: rss, json
		if($format == 'json'){
			$xml = simplexml_load_string($this->getDataEntryXML($days,$limit));
			return json_encode($xml);
		}
		else{
			return $this->getDataEntryXML($days,$limit);
		}
	}

	private function getDataEntryXML($days, $limit){

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
		$titleElem->appendChild($newDoc->createTextNode($GLOBALS['DEFAULT_TITLE'].' New Occurrence Records'));
		$channelElem->appendChild($titleElem);

		$serverDomain = "http://";
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $serverDomain = "https://";
		$serverDomain .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80 && $_SERVER['SERVER_PORT'] != 443) $serverDomain .= ':'.$_SERVER["SERVER_PORT"];
		$urlPathPrefix = '';
		if($serverDomain){
			$urlPathPrefix = $serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
		}

		$linkElem = $newDoc->createElement('link');
		$linkElem->appendChild($newDoc->createTextNode($urlPathPrefix));
		$channelElem->appendChild($linkElem);
		$descriptionElem = $newDoc->createElement('description');
		$descriptionElem->appendChild($newDoc->createTextNode('An RSS feed that lists summary information for new occurrence records recently entered into the '.$GLOBALS['DEFAULT_TITLE'].' portal'));
		$channelElem->appendChild($descriptionElem);
		$languageElem = $newDoc->createElement('language','en-us');
		$channelElem->appendChild($languageElem);
		//Create new item for target archives and load into array
		$sql = 'SELECT o.occid, CONCAT_WS("-",c.institutioncode, c.collectioncode) as instcode, c.collectionname, o.recordID, c.guidtarget, '.
			'o.occurrenceid, o.catalognumber, o.sciname, o.recordedby, o.recordnumber, IFNULL(CAST(o.eventdate AS CHAR),o.verbatimeventdate) as eventdate, '.
			'o.decimallatitude, o.decimallongitude, o.datelastmodified, o.recordenteredby, o.genericcolumn2, '.
			'IFNULL(i.thumbnailurl,i.url) AS thumbnailurl, o.processingstatus '.
			'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid '.
			'INNER JOIN images i ON o.occid = i.occid '.
			'WHERE c.colltype = "Preserved Specimens" '.
			'AND o.processingstatus IN("pending review","reviewed", "closed") AND (o.localitysecurity IS NULL OR o.localitysecurity = 0) ';
		if($days && is_numeric($days)) $sql .= 'AND (o.datelastmodified > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)) ';
		$sql .= 'ORDER BY o.datelastmodified DESC ';
		if(!$days && !$limit) $limit = '100';
		if($limit && is_numeric($limit)) $sql .= 'LIMIT '.$limit;
		$rs = $this->conn->query($sql);
		//echo $sql;
		while($r = $rs->fetch_object()){
			$itemElem = $newDoc->createElement('item');
			$channelElem->appendChild($itemElem);

			$itemTitleElem = $newDoc->createElement('title');
			$titleStr = $r->sciname.' - '.$r->recordedby.' '.($r->recordnumber?'#'.$r->recordnumber:'');
			$itemTitleElem->appendChild($newDoc->createTextNode($titleStr));
			$itemElem->appendChild($itemTitleElem);
			$collLinkElem = $newDoc->createElement('collectionName',$r->collectionname.' ('.$r->instcode.')');
			$itemElem->appendChild($collLinkElem);
			$catalogLinkElem = $newDoc->createElement('catalogNumber',$r->catalognumber);
			$itemElem->appendChild($catalogLinkElem);

			if($r->guidtarget){
				$occID = $r->recordID;
				if($r->guidtarget == 'occurrenceId'){
					$occID = $r->occurrenceid;
				}
				if($r->guidtarget == 'catalogNumber'){
					$occID = $r->catalognumber;
				}
				$guidLinkElem = $newDoc->createElement('occurrenceID',$occID);
				$itemElem->appendChild($guidLinkElem);
			}

			$itemLinkElem = $newDoc->createElement('link');
			$itemLinkElem->appendChild($newDoc->createTextNode($serverDomain.'/collections/individual/index.php?occid='.$r->occid));
			$itemElem->appendChild($itemLinkElem);

			$tnUrl = $r->thumbnailurl;
			if(substr($tnUrl,0,1) == '/'){
				if(isset($GLOBALS['IMAGE_DOMAIN']) && $GLOBALS['IMAGE_DOMAIN']){
					$tnUrl = $GLOBALS['IMAGE_DOMAIN'].$tnUrl;
				}
				else{
					$tnUrl = $serverDomain.$tnUrl;
				}
			}
			$tnLinkElem = $newDoc->createElement('thumbnailUri');
			$tnLinkElem->appendChild($newDoc->createTextNode($tnUrl));
			$itemElem->appendChild($tnLinkElem);

			$latLinkElem = $newDoc->createElement('decimalLatitude',$r->decimallatitude);
			$itemElem->appendChild($latLinkElem);
			$lngLinkElem = $newDoc->createElement('decimalLongitude',$r->decimallongitude);
			$itemElem->appendChild($lngLinkElem);
			$eventDateLinkElem = $newDoc->createElement('verbatimEventDate');
			$eventDateLinkElem->appendChild($newDoc->createTextNode($r->eventdate));
			$itemElem->appendChild($eventDateLinkElem);
			//$pubDateLinkElem = $newDoc->createElement('pubDate',$r->datelastmodified);
			$pubDateLinkElem = $newDoc->createElement('pubDate',gmdate(DATE_RSS, strtotime($r->datelastmodified)));
			$itemElem->appendChild($pubDateLinkElem);
			$creatorLinkElem = $newDoc->createElement('creator',$r->recordenteredby);
			$itemElem->appendChild($creatorLinkElem);

			if($r->genericcolumn2){
				$ipAddr = $newDoc->createElement('ipAddress',$r->genericcolumn2);
				$itemElem->appendChild($ipAddr);
				//<decimalLatitudeTranscribing>Transcription Lat</decimalLatitudeTranscribing>
				//<decimalLongitudeTranscribing>Transcription Long</decimalLongitudeTranscribing>
			}
		}
		return $newDoc->saveXML();
	}

	public function setSqlWhere($sqlStr){
		$this->sqlWhere = $sqlStr;
	}

	public function addCondition($field, $cond, $value = ''){
		if($field){
			if(!trim($cond)) $cond = 'EQUALS';
			if($value || ($cond == 'NULL' || $cond == 'NOTNULL')){
				$this->conditionArr[$field][$cond][] = $this->cleanInStr($value);
			}
		}
	}

	private function applyConditions(){
		$sqlFrag = '';
		if($this->conditionArr){
			foreach($this->conditionArr as $field => $condArr){
				$sqlFrag2 = '';
				foreach($condArr as $cond => $valueArr){
					$sqlFrag2 .= $this->getSqlFragment($field, $cond, $valueArr);
				}
				$sqlFrag .= 'AND ('.substr($sqlFrag2,3).') ';
			}

		}
		if($sqlFrag) $this->sqlWhere .= $sqlFrag;
		if($this->sqlWhere){
			//Make sure it starts with WHERE
			if(substr($this->sqlWhere,0,4) == 'AND '){
				$this->sqlWhere = 'WHERE'.substr($this->sqlWhere,3);
			}
			elseif(substr($this->sqlWhere,0,6) != 'WHERE '){
				$this->sqlWhere = 'WHERE '.$this->sqlWhere;
			}
		}
	}

	private function getSqlFragment($field, $cond, $valueArr){
		$sqlFrag = '';
		if($cond == 'NULL'){
			$sqlFrag .= 'OR o.'.$field.' IS NULL ';
		}
		elseif($cond == 'NOTNULL'){
			$sqlFrag .= 'OR o.'.$field.' IS NOT NULL ';
		}
		elseif($cond == 'EQUALS'){
			$sqlFrag .= 'OR o.'.$field.' IN("'.implode('","',$valueArr).'") ';
		}
		elseif($cond == 'NOTEQUALS'){
			$sqlFrag .= 'OR o.'.$field.' NOT IN("'.implode('","',$valueArr).'") OR o.'.$field.' IS NULL ';
		}
		else{
			foreach($valueArr as $value){
				if($cond == 'STARTS'){
					$sqlFrag .= 'OR o.'.$field.' LIKE "'.$value.'%" ';
				}
				elseif($cond == 'LIKE'){
					$sqlFrag .= 'OR o.'.$field.' LIKE "%'.$value.'%" ';
				}
				elseif($cond == 'NOTLIKE'){
					$sqlFrag .= 'OR o.'.$field.' NOT LIKE "%'.$value.'%" OR o.'.$field.' IS NULL ';
				}
				elseif($cond == 'LESSTHAN'){
					$sqlFrag .= 'OR o.'.$field.' < "'.$value.'" ';
				}
				elseif($cond == 'GREATERTHAN'){
					$sqlFrag .= 'OR o.'.$field.' > "'.$value.'" ';
				}
			}
		}
		return $sqlFrag;
	}

	private function getSql(){
		$sql = '';
		if($this->schemaType == 'checklist'){
			if($this->taxonFilter){
				$sql = 'SELECT DISTINCT ts.family, t.sciname AS scientificName, CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus, '.
					'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet, t.unitind3 AS taxonRank, t.unitname3 AS infraSpecificEpithet, t.author AS scientificNameAuthorship '.
					'FROM omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid '.
					'INNER JOIN taxa t ON ts.TidAccepted = t.Tid ';
				$sql .= $this->setTableJoins($this->sqlWhere);
				$sql .= $this->sqlWhere.'AND t.RankId > 140 AND (ts.taxauthid = '.$this->taxonFilter.') ';
				if($this->redactLocalities){
					if($this->rareReaderArr){
						$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$this->rareReaderArr).')) ';
					}
					else{
						$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
					}
				}
				$sql .= 'ORDER BY ts.family, t.SciName ';
			}
			else{
				$sql = 'SELECT DISTINCT IFNULL(o.family,"not entered") AS family, o.sciname, CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus, '.
					'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet, t.unitind3 AS taxonRank, t.unitname3 AS infraSpecificEpithet, t.author AS scientificNameAuthorship '.
					'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
				$sql .= $this->setTableJoins($this->sqlWhere);
				$sql .= $this->sqlWhere.'AND o.SciName NOT LIKE "%aceae" AND o.SciName NOT LIKE "%idea" AND o.SciName NOT IN ("Plantae","Polypodiophyta") ';
				if($this->redactLocalities){
					if($this->rareReaderArr){
						$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$this->rareReaderArr).')) ';
					}
					else{
						$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
					}
				}
				$sql .= 'ORDER BY IFNULL(IFNULL(ts.family, o.family),"not entered"), o.SciName ';
			}
		}
		elseif($this->schemaType == 'georef'){
			$sql = 'SELECT IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, '.
				'o.catalogNumber, o.occurrenceId, o.decimalLatitude, o.decimalLongitude, '.
				'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.verbatimCoordinates, ';
			if($this->extended){
				$sql .= 'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, '.
					'o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, '.
					'o.localitySecurity, o.localitySecurityReason, IFNULL(o.modified,o.datelastmodified) AS modified, '.
					'o.processingStatus, o.collId, o.dbpk AS sourcePrimaryKey, o.occid, CONCAT("urn:uuid:", o.recordID) AS recordID ';
			}
			else{
				$sql .= 'o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, '.
					'o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, '.
					'IFNULL(o.modified,o.datelastmodified) AS modified, o.occid, CONCAT("urn:uuid:", o.recordID) AS recordID ';
			}
			$sql .= 'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid LEFT JOIN taxa t ON o.tidinterpreted = t.tid ';
			$sql .= $this->setTableJoins($this->sqlWhere);
			$this->applyConditions();
			$sql .= $this->sqlWhere;
			if($this->redactLocalities){
				if($this->rareReaderArr){
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$this->rareReaderArr).')) ';
				}
				else{
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
				}
			}
			$sql .= 'ORDER BY o.collid';
		}
		//echo $sql; exit;
		return $sql;
	}

	private function setTableJoins($sqlWhere){
		$sqlJoin = '';
		if(strpos($sqlWhere,'e.taxauthid')) $sqlJoin .= 'INNER JOIN taxaenumtree e ON o.tidinterpreted = e.tid ';
		if(strpos($sqlWhere,'ctl.clid')) $sqlJoin .= 'INNER JOIN fmvouchers v ON o.occid = v.occid INNER JOIN fmchklsttaxalink ctl ON v.clTaxaID = ctl.clTaxaID ';
		if(strpos($sqlWhere,'p.point')) $sqlJoin .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid ';
		if(strpos($sqlWhere,'MATCH(f.recordedby)') || strpos($sqlWhere,'MATCH(f.locality)')){
			$sqlJoin .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		return $sqlJoin;
	}

	private function getOutputFilePath(){
		$retStr = $GLOBALS['tempDirRoot'];
		if(!$retStr){
			$retStr = $GLOBALS['serverRoot'];
			if(substr($retStr,-1) != '/' && substr($retStr,-1) != "\\") $retStr .= '/';
			$retStr .= 'temp/';
		}
		if(substr($retStr,-1) != '/' && substr($retStr,-1) != "\\") $retStr .= '/';
		if(file_exists($retStr.'downloads/')){
			$retStr .= 'downloads/';
		}
		return $retStr;
	}

	private function getOutputFileName(){
		$retStr = '';
		$fileName = str_replace(Array(".",":"),"",$GLOBALS['DEFAULT_TITLE']);
		if(stripos($fileName,'the ') === 0) $fileName = substr($fileName,4);
		if(strlen($fileName) > 15){
			if($p = strpos($fileName,'(')) $fileName = substr($filename,0,$p);
			if(strpos($fileName,' ')){
				$nameArr = explode(" ",trim($fileName));
				$fileName = '';
				foreach($nameArr as $v){
					$fileName .= substr($v,0,1);
				}
			}
			else{
				$fileName = substr($fileName,0,15);
			}
		}
		if($fileName){
			$retStr = str_replace(" ","",$fileName).'_';
		}
		elseif($this->schemaType == 'georef'){
			$retStr .= "Georef_";
		}
		elseif($this->schemaType == 'checklist'){
			$retStr .= "Checklist_";
		}
		$retStr .= '_'.time();
		//Set extension
		if($this->zipFile){
			$retStr .= ".zip";
		}
		elseif($this->delimiter=="\t"){
			$retStr .= ".tab";
		}
		elseif($this->delimiter==','){
			$retStr .= ".csv";
		}
		else{
			$retStr .= ".txt";
		}
		return $retStr;
	}

	public function getCollectionMetadata($collid){
		$retArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, managementtype '.
					'FROM omcollections '.
					'WHERE collid = '.$collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['instcode'] = $r->institutioncode;
				$retArr['collcode'] = $r->collectioncode;
				$retArr['collname'] = $r->collectionname;
				$retArr['manatype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getProcessingStatusList($collid = 0){
		$psArr = array();
		$sql = 'SELECT DISTINCT processingstatus FROM omoccurrences ';
		if($collid){
			$sql .= 'WHERE collid = '.$collid;
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->processingstatus) $psArr[] = $r->processingstatus;
		}
		$rs->free();
		//Special sort
		$templateArr = array('unprocessed','unprocessed-nlp','pending duplicate','stage 1','stage 2','stage 3','pending review','reviewed');
		//Get all active processing statuses and then merge all extra statuses that may exists for one reason or another
		return array_merge(array_intersect($templateArr,$psArr),array_diff($psArr,$templateArr));
	}

	public function getAttributeTraits($collid = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT t.traitid, t.traitname, s.stateid, s.statename '.
			'FROM tmtraits t INNER JOIN tmstates s ON t.traitid = s.traitid '.
			'INNER JOIN tmattributes a ON s.stateid = a.stateid '.
			'INNER JOIN omoccurrences o ON a.occid = o.occid ';
		if($collid) $sql .= 'WHERE o.collid = '.$collid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->traitid]['name'] = $r->traitname;
			$retArr[$r->traitid]['state'][$r->stateid] = $r->statename;
		}
		$rs->free();
		return $retArr;
	}

	public function hasMaterialSamples($collid = 0){
		$bool = false;
		$sql = 'SELECT occid FROM ommaterialsample LIMIT 1';
		if($collid && is_numeric($collid)){
			$sql .= 'SELECT o.occid FROM ommaterialsample m INNER JOIN omoccurrences o ON m.occid = o.occid WHERE (o.collid = '.$collid.') LIMIT 1';
		}
		if($rs = $this->conn->query($sql)){
			if($rs->num_rows) $bool = true;
			$rs->free();
		}
		return $bool;
	}

	//General setter, getters, and other configurations
	public function setSchemaType($t){
		$this->schemaType = $t;
	}

	public function setExtended($e){
		$this->extended = $e;
	}

	public function setDelimiter($d){
		if($d == 'tab' || $d == "\t"){
			$this->delimiter = "\t";
		}
		elseif($d == 'csv' || $d == 'comma' || $d == ','){
			$this->delimiter = ",";
		}
		else{
			$this->delimiter = $d;
		}
	}

	private function getContentType(){
		if($this->zipFile){
			return 'application/zip; charset='.$this->charSetOut;
		}
		elseif($this->delimiter == 'comma' || $this->delimiter == ','){
			return 'text/csv; charset='.$this->charSetOut;
		}
		else{
			return 'text/html; charset='.$this->charSetOut;
		}
	}

	public function setCharSetOut($cs){
		$cs = strtoupper($cs);
		if($cs == 'ISO-8859-1' || $cs == 'UTF-8'){
			$this->charSetOut = $cs;
		}
	}

	public function setZipFile($c){
		$this->zipFile = $c;
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	public function setRedactLocalities($cond){
		if($cond == 0 || $cond === false){
			$this->redactLocalities = false;
		}
	}

	public function setTaxonFilter($filter){
		if(is_numeric($filter)){
			$this->taxonFilter = $filter;
		}
	}

	public function setIsPublicDownload(){
		$this->isPublicDownload = true;
	}

	//Misc functions
	private function stripSensitiveFields(&$row){
		if($row["localitySecurity"] == 1 && $this->redactLocalities && !in_array($row["collid"],$this->rareReaderArr)){
			foreach($this->securityArr as $fieldName){
				$row[$fieldName] = '[redacted]';
			}
		}
	}

	private function encodeArr(&$inArr){
		if($this->charSetSource && $this->charSetOut != $this->charSetSource){
			foreach($inArr as $k => $v){
				$inArr[$k] = $this->encodeStr($v);
			}
		}
	}

	private function encodeStr($inStr){
		$retStr = $inStr;
		if($this->charSetSource){
			if($this->charSetOut == 'UTF-8' && $this->charSetSource == 'ISO-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($this->charSetOut == "ISO-8859-1" && $this->charSetSource == 'UTF-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		return $retStr;
	}

	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = preg_replace('/\s\s+/', ' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>