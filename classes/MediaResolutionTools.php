<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
class MediaResolutionTools extends Manager {

	//Archiver variables
	private $imgidArr;
	private $archiveImages = false;
	private $archiveDir;
	private $deleteThumbnail = false;
	private $deleteWeb = false;
	private $deleteOriginal = false;

	//Image migration variables
	private $collMetaArr;
	private $transferThumbnail = false;
	private $transferWeb = false;
	private $transferLarge = false;
	private $matchTermThumbnail;
	private $matchTermWeb;
	private $matchTermLarge;
	private $deleteSource = false;
	private $imgRootUrl;
	private $imgRootPath;
	private $imgSubPath;
	private $sourcePathPrefix;

	private $debugMode = false;

	function __construct() {
		parent::__construct(null,'write');
		set_time_limit(600);
		$this->verboseMode = 3;
		$this->setLogFH('../../../temp/logs/imgMigration_'.date('Ym').'.log');
	}

	function __destruct(){
		parent::__destruct();
	}

	//Archiver functions
	public function archiveImageFiles($imgidStart, $limit){
		//Set stage
		if(!$imgidStart) $imgidStart = 0;
		if(!$this->imgidArr){
			$this->logOrEcho('ABORTED: Image ids (imgid) not supplied');
			return false;
		}
		$this->archiveDir = $GLOBALS['IMAGE_ROOT_PATH'].'/archive_'.date('Y-m-d');
		if(!file_exists($this->archiveDir)){
			if(!mkdir($this->archiveDir)) {
				$this->logOrEcho('ABORTED: unalbe to create archive directory ('.$this->archiveDir.')');
				return false;
			}
		}
		$createHeader = true;
		if(file_exists($this->archiveDir.'/mediaArchiveReport.csv')) $createHeader = false;
		$csvReportFH = fopen($this->archiveDir.'/mediaArchiveReport.csv', 'a');
		if(!$csvReportFH){
			$this->logOrEcho('ABORTED: unalbe to create archive file ('.$this->archiveDir.')');
			return false;
		}
		if($createHeader) fputcsv($csvReportFH, array('imgid','insertSQL'));
		//Remove images
		$imgidFinal = $imgidStart;
		$cnt = 0;
		$sql = 'SELECT i.* FROM images i ';
		if($this->collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.imgid IN('.trim(implode(',',$this->imgidArr),', ').')) AND (i.imgid > '.$imgidStart.') ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		echo '<ul>';
		while($r = $rs->fetch_assoc()){
			$imgId = $r['imgid'];
			$derivArr = array('tn'=>1,'web'=>1,'lg'=>1);
			$delArr = array();
			if(!$r['thumbnailurl']) unset($derivArr['tn']);
			if(!$r['url']) unset($derivArr['web']);
			if(!$r['originalurl']) unset($derivArr['lg']);
			//Transfer images to archive folder
			if($this->deleteThumbnail && isset($derivArr['tn'])){
				if($this->archiveImage($r['thumbnailurl'], $imgId)){
					$delArr['tn'] = 1;
					unset($derivArr['tn']);
				}
			}
			if($this->deleteWeb && isset($derivArr['web'])){
				if($this->archiveImage($r['url'], $imgId)){
					$delArr['web'] = 1;
					unset($derivArr['web']);
				}
			}
			if($this->deleteOriginal && isset($derivArr['lg'])){
				if($this->archiveImage($r['originalurl'], $imgId)){
					$delArr['lg'] = 1;
					unset($derivArr['lg']);
				}
			}
			//Place INSERT sql into file in case record needs to be reintalled
			$insertArr = $r;
			unset($insertArr['imgid']);
			unset($insertArr['initialtimestamp']);
			$insertStr = '';
			foreach($insertArr as $v){
				if($v) $insertStr .= ', "'.$v.'"';
				else $insertStr .= ', NULL';
			}
			$insSql = 'INSERT INTO images('.implode(',', array_keys($insertArr)).') VALUES('.substr($insertStr,1).');';
			fputcsv($csvReportFH,array($imgId,'record deleted',$insSql));
			//Adjust database record
			$sqlImg = '';
			if($derivArr){
				if(isset($delArr['tn'])) $sqlImg .= ', thumbnailurl = NULL';
				if(isset($delArr['web'])) $sqlImg .= ', url = "empty"';
				if(isset($delArr['lg'])) $sqlImg .= ', originalurl = NULL';
				if($sqlImg) $sqlImg = 'UPDATE images SET '.substr($sqlImg,1).' WHERE imgid = '.$imgId;
			}
			else{
				$sqlImg = 'DELETE FROM images WHERE imgid = '.$imgId;
			}
			if($sqlImg){
				if(!$this->conn->query($sqlImg)){
					$this->logOrEcho('ERROR: '.$this->conn->error,1);
					$this->logOrEcho('sqlImg: '.$sqlImg,2);
				}
			}
			if($cnt && $cnt%100 == 0){
				$this->logOrEcho($cnt.' images checked');
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $imgId;
		}
		echo '</ul>';
		$rs->free();
		fclose($csvReportFH);
		$this->logOrEcho('Done! '.$cnt.' images handled');
		return $imgidFinal;
	}

	private function archiveImage($imgFilePath, $imgid){
		$status = false;
		if($imgFilePath){
			if(substr($imgFilePath,0,4) == 'http') {
				$imgFilePath = substr($imgFilePath,strpos($imgFilePath,"/",9));
			}
			$path = str_replace($GLOBALS['IMAGE_ROOT_URL'], $GLOBALS['IMAGE_ROOT_PATH'], $imgFilePath);
			if(is_writable($path)){
				if($this->archiveImages){
					$fileName = substr($path, strrpos($path, '/'));
					if(rename($path,$this->archiveDir.'/'.$fileName)) $status = true;
				}
				else{
					if(unlink($path)) $status = true;
				}
			}
			else{
				$this->logOrEcho('ERROR: image unwritable (imgid: <a href="'.$GLOBALS['CLIENT_ROOT'].'/imagelib/imgdetails.php?imgid='.$imgid.'" target="_blank">'.$imgid.'</a>, path: '.$path.')');
			}
		}
		return $status;
	}

	//Image migration functions
	public function migrateFieldDerivatives($imgIdStart, $limit){
		//Needs to be reworked
		ini_set('max_execution_time', 3600);
		$this->debugMode = true;
		$imgId = 0;
		if(is_numeric($limit) && is_numeric($this->collid) && $this->imgRootUrl && $this->imgRootPath){
			if($this->transferThumbnail && $this->transferWeb && $this->transferLarge){
				if($this->matchTermThumbnail || $this->matchTermWeb || $this->matchTermLarge){
					echo '<ul>';
					$this->setTargetPaths();
					$dirCnt = 0;
					do{
						$imgArr = array();
						$pathFrag = date('Ym');
						if(!file_exists($this->imgRootPath.$pathFrag)) mkdir($this->imgRootPath.$pathFrag);
						$subDir = str_pad($dirCnt,4,'0',STR_PAD_LEFT);
						while(file_exists($this->imgRootPath.$pathFrag.'/'.$subDir)){
							$dirCnt ++;
							$subDir = str_pad($dirCnt,4,'0',STR_PAD_LEFT);
						}
						$pathFrag .= '/'.$subDir;
						$dirCnt ++;
						$sql = 'SELECT imgid, thumbnailurl, url, originalurl FROM images WHERE occid IS NULL ';
						if($this->collid) $sql = 'SELECT i.thumbnailurl, i.url, i.originalurl FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid WHERE o.collid = '.$this->collid;
						if($this->matchTermThumbnail) $sql .= ' AND thumbnailurl LIKE "'.$this->matchTermThumbnail.'%" ';
						if($this->matchTermWeb) $sql .= ' AND url LIKE "'.$this->matchTermWeb.'%" ';
						if($this->matchTermLarge) $sql .= ' AND originalurl LIKE "'.$this->matchTermLarge.'%" ';
						if($imgIdStart && is_numeric($imgIdStart)) $sql .= 'AND imgid > '.$imgIdStart.' ';
						$sql .= 'ORDER BY imgid ';
						$sql .= 'LIMIT 1000';
						echo $sql.'<br/>';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$imgId = $r->imgid;
							if($this->transferThumbnail){
								$filePath = $pathFrag;
								if(substr($r->thumbnailurl,-1) != '/') $filePath .= '/';
								echo $r->thumbnailurl.' => '.$this->imgRootPath.$filePath.'<br/>';
/*
								if(copy($r->thumbnailurl,$this->imgRootPath.$filePath)){
									$imgArr[$r->imgid]['tn'] = $filePath;
									$this->logOrEcho('Copied: '.$r->thumbnailurl);
								}
*/
							}
							if($this->transferWeb){
								$filePath = $pathFrag;
								if(substr($r->url,-1) != '/') $filePath .= '/';
								echo $r->url.' => '.$this->imgRootPath.$filePath.'<br/>';
								/*
								 if(copy($r->url,$this->imgRootPath.$filePath)){
									$imgArr[$r->imgid]['web'] = $filePath;
									$this->logOrEcho('Copied: '.$r->url);
								}
*/
							}
							if($this->transferLarge){
								$filePath = $pathFrag;
								if(substr($r->originalurl,-1) != '/') $filePath .= '/';
								echo $r->originalurl.' => '.$this->imgRootPath.$filePath.'<br/>';
/*
								if(copy($r->originalurl,$this->imgRootPath.$filePath)){
									$imgArr[$r->imgid]['lg'] = $filePath;
									$this->logOrEcho('Copied: '.$r->originalurl);
								}
*/
							}
							$limit--;
							if($limit < 1) break;
						}
						$rs->free();
						$this->databaseImageArr($imgArr);
						$cnt = count($imgArr);
						$this->logOrEcho($cnt.' image records remapped');
						unset($imgArr);
					}while($cnt && $limit);
					echo '</ul>';
				}
			}
		}
		return $imgId;
	}

	public function migrateCollectionDerivatives($imgIdStart, $limit){
		//Migrates images based on catalog number; NULL or weak catalogNumbers are skipped
		ini_set('max_execution_time', 3600);
		if($this->collid && is_numeric($limit) && $this->imgRootUrl && $this->imgRootPath){
			if($this->transferThumbnail || $this->transferWeb || $this->transferLarge){
				if($this->matchTermThumbnail || $this->matchTermWeb || $this->matchTermLarge){
					echo '<ul>';
					$this->setTargetPaths();
					$processingCnt = 0;
					$sqlBase = 'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid WHERE o.collid = '.$this->collid.' ';
					if($this->matchTermThumbnail) $sqlBase .= 'AND thumbnailurl LIKE "'.$this->matchTermThumbnail.'%" ';
					if($this->matchTermWeb) $sqlBase .= 'AND url LIKE "'.$this->matchTermWeb.'%" ';
					if($this->matchTermLarge) $sqlBase .= 'AND originalurl LIKE "'.$this->matchTermLarge.'%" ';
					$targetCount = 0;
					$sqlCount = 'SELECT COUNT(i.imgid) as cnt '.$sqlBase.' ';
					if($imgIdStart && is_numeric($imgIdStart)) $sqlCount .= 'AND imgid > '.$imgIdStart.' ';
					$rsCount = $this->conn->query($sqlCount);
					while($rCount = $rsCount->fetch_object()){
						$targetCount = $rCount->cnt;
					}
					$rsCount->free();
					$this->logOrEcho('Starting remapping of '.$limit.' out of '.$targetCount.' possible target images');
					do{
						$imgArr = array();
						$sql = 'SELECT i.imgid, i.thumbnailurl, i.url, i.originalurl, o.catalognumber, o.occid '.$sqlBase;
						if($imgIdStart && is_numeric($imgIdStart)) $sql .= 'AND imgid > '.$imgIdStart.' ';
						$sql .= 'ORDER BY imgid LIMIT 100';
						//$this->logOrEcho('sql used: '. $sql);
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$imgIdStart = $r->imgid;
							$pathFrag = '';
							if(preg_match('/^(\D*).*(\d{4,})/', $r->catalognumber, $m)){
								$catNum = $m[2];
								if($catNum){
									if(strlen($catNum)<8) $catNum = str_pad($catNum,8,'0',STR_PAD_LEFT);
									$pathFrag = $m[1].substr($catNum,0,strlen($catNum)-4).'/';
								}
							}
							if(!$pathFrag) $pathFrag = date('Ymd').'/';
							if(!file_exists($this->imgRootPath.$pathFrag)) mkdir($this->imgRootPath.$pathFrag);
							$this->logOrEcho($processingCnt.': Processing: <a href="../../individual/index.php?occid='.$r->occid.'" target="_blank">'.$r->occid.'</a>');
							if($this->transferThumbnail && $r->thumbnailurl){
								$fileName = basename($r->thumbnailurl);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$thumbPath = $this->getLocalPath($r->thumbnailurl);
								if(copy($thumbPath, $targetPath)){
									$imgArr[$r->imgid]['tn'] = $targetUrl;
									$this->logOrEcho('Copied: '.$thumbPath.' => '.$targetPath,1);
									if($this->deleteSource){
										if(unlink($thumbPath)){
											$this->logOrEcho('Source deleted: '.$thumbPath,1);
										}
										else{
											$this->logOrEcho('ERROR deleting source (file permissions?): '.$thumbPath,1);
										}
									}
								}
							}
							if($this->transferWeb && $r->url){
								$fileName = basename($r->url);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$urlPath = $this->getLocalPath($r->url);
								if(copy($urlPath, $targetPath)){
									$imgArr[$r->imgid]['web'] = $targetUrl;
									$this->logOrEcho('Copied: '.$urlPath.' => '.$targetPath,1);
									if($this->deleteSource){
										if(unlink($urlPath)){
											$this->logOrEcho('Source delete: '.$urlPath,1);
										}
										else{
											$this->logOrEcho('ERROR deleting source (file permissions?): '.$urlPath,1);
										}
									}
								}
							}
							if($this->transferLarge && $r->originalurl){
								$fileName = basename($r->originalurl);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$origPath = $this->getLocalPath($r->originalurl);
								if(copy($origPath, $targetPath)){
									$imgArr[$r->imgid]['lg'] = $targetUrl;
									$this->logOrEcho('Copied: '.$origPath.' => '.$targetPath,1);
									if($this->deleteSource){
										if(unlink($origPath)){
											$this->logOrEcho('Source deleted: '.$origPath,1);
										}
										else{
											$this->logOrEcho('ERROR deleting source (file permissions?): '.$origPath,1);
										}
									}
								}
							}
							$processingCnt++;
							$limit--;
							if($limit < 1) break;
						}
						$rs->free();
						$this->databaseImageArr($imgArr);
						$cnt = count($imgArr);
						$this->logOrEcho($processingCnt.' image records remapped ('.date('Y-m-d H:i:s').')');
						unset($imgArr);
					}while($cnt && $limit);
					echo '</ul>';
				}
			}
		}
		return $imgIdStart;
	}

	private function getLocalPath($imageUrl){
		if($this->sourcePathPrefix){
			$adjustedUrl = str_replace($this->sourcePathPrefix, $GLOBALS['IMAGE_ROOT_PATH'], $imageUrl);
			if(file_exists($adjustedUrl)) return $adjustedUrl;
		}
		if(file_exists($imageUrl)){
			return $imageUrl;
		}
		if(strpos($imageUrl, $GLOBALS['IMAGE_ROOT_URL']) !== false){
			$adjustedUrl = str_replace($GLOBALS['IMAGE_ROOT_URL'], $GLOBALS['IMAGE_ROOT_PATH'], $imageUrl);
			if(file_exists($adjustedUrl)) return $adjustedUrl;
		}
		$prefix = substr($GLOBALS['IMAGE_ROOT_PATH'], 0, strlen($GLOBALS['IMAGE_ROOT_PATH']) - strlen($GLOBALS['IMAGE_ROOT_URL']));
		if(file_exists($prefix.$imageUrl)){
			$this->sourcePathPrefix = $prefix;
			return $prefix.$imageUrl;
		}
		return $imageUrl;
	}

	private function databaseImageArr($imgArr){
		foreach($imgArr as $imgID => $iArr){
			$sqlFrag = '';
			if(isset($iArr['tn'])) $sqlFrag .= 'thumbnailurl = "'.$iArr['tn'].'"';
			if(isset($iArr['web'])) $sqlFrag .= ',url = "'.$iArr['web'].'"';
			if(isset($iArr['lg'])) $sqlFrag .= ',originalurl = "'.$iArr['lg'].'"';
			if($sqlFrag){
				$sql = 'UPDATE images SET '.trim($sqlFrag,' ,').' WHERE imgid = '.$imgID;
				if($this->debugMode) $this->logOrEcho($sql);
				if(!$this->conn->query($sql)) $this->logOrEcho('ERROR saving new paths: '.$this->conn->error,1);
			}
		}
	}

	private function setTargetPaths(){
		if($this->imgRootPath && $this->imgRootUrl){
			if($this->collid){
				$this->imgRootPath .= $this->collMetaArr['code'].'/';
				$this->imgRootUrl .= $this->collMetaArr['code'].'/';
			}
			elseif($this->collid === 0){
				$this->imgRootPath .= 'fieldimg/';
				$this->imgRootUrl .= 'fieldimg/';
			}
			if(!file_exists($this->imgRootPath)) mkdir($this->imgRootPath);
		}
	}

	//Navigates through iDigBio media links and fixes bad full derivative links that were the result of a disk crash
	public function checkImageLinks($imgidStart, $limit, $collid){
		$imgidFinal = $imgidStart;
		$cnt = 1;
		$sql = 'SELECT i.imgid, i.originalurl FROM images i ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.originalurl LIKE "https://api.idigbio.org/v2/media/%size=fullsize") AND (i.imgid > '.$imgidStart.') ';
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$url = $r->originalurl;
			if($this->isBrokenUrl($url)){
				if($newUrl = substr($url,0,-14)){
					if(!$this->isBrokenUrl($newUrl)){
						$sql2 = 'UPDATE images SET originalurl = "'.$newUrl.'" WHERE imgid = '.$r->imgid;
						$this->conn->query($sql2);
						echo '<li>'.$cnt.': Remapping image #'.$r->imgid.' to: '.$newUrl.'</li>';
						ob_flush();
						flush();
					}
				}
			}
			//echo '<li>Image is good (imgid: '.$r->imgid.'): '.$url.'</li>';
			if($cnt%500 == 0){
				echo '<li>'.$cnt.' image checked (imgid: '.$r->imgid.')</li>';
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $r->imgid;
		}
		$rs->free();
		return $imgidFinal;
	}

	private function isBrokenUrl($url){
		$status = false;
		$handle = curl_init($url);
		if(false === $handle){
			$status = true;
		}
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_NOBODY, true);
		curl_setopt($handle, CURLOPT_FAILONERROR, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true );
		//curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_exec($handle);
		$retCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		//print_r(curl_getinfo($handle));
		if($retCode == 403) $status = true;
		curl_close($handle);
		return $status;
	}

	//Misc data return functions
	public function getCollectionMeta(){
		$retArr = array();
		$sql = 'SELECT collid, collectionname, CONCAT_WS(":",institutioncode,collectioncode) as instcode FROM omcollections ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]= $r->collectionname.' ('.$r->instcode.')';
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$sql = 'SELECT collectionname, CONCAT_WS("_",institutioncode,collectioncode) as instcode FROM omcollections WHERE collid = '.$id;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collMetaArr['name']= $r->collectionname;
				$this->collMetaArr['code']= $r->instcode;
			}
			$rs->free();
		}
	}

	//Archiver setters and getters
	public function setImgidArr($imgidStr){
		$imgidStr = str_replace(';', ' ', $imgidStr);
		$imgidStr = str_replace(',', ' ', $imgidStr);
		$imgidStr = trim(preg_replace('/\s\s+/',' ',$imgidStr),',');
		if($imgidStr){
			if(preg_match('/^[\d\s]+$/',$imgidStr)){
				$this->imgidArr = explode(' ',$imgidStr);
			}
		}
	}

	public function setArchiveImages($b){
		if($b) $this->archiveImages = true;
	}

	public function setDeleteThumbnail($delTn){
		if($delTn) $this->deleteThumbnail = true;
		else $this->deleteThumbnail = false;
	}

	public function setDeleteWebImage($delWeb){
		if($delWeb) $this->deleteWeb = true;
		else $this->deleteWeb = false;
	}

	public function setDeleteOriginal($delOrig){
		if($delOrig) $this->deleteOriginal = true;
		else $this->deleteOriginal = false;
	}

	//Image migration setters and getter
	public function setTransferThumbnail($bool){
		if($bool) $this->transferThumbnail = true;
		else $this->transferThumbnail = false;
	}

	public function setTransferWeb($bool){
		if($bool) $this->transferWeb = true;
		else $this->transferWeb = false;
	}

	public function setTransferLarge($bool){
		if($bool) $this->transferLarge = true;
		else $this->transferLarge = false;
	}

	public function setMatchTermThumbnail($str){
		$this->matchTermThumbnail = $str;
	}

	public function setMatchTermWeb($str){
		$this->matchTermWeb = $str;
	}

	public function setMatchTermLarge($str){
		$this->matchTermLarge = $str;
	}

	public function setDeleteSource($bool){
		$this->deleteSource = $bool;
	}

	public function setImgRootUrl($url){
		if(substr($url, -1) != '/') $url .= '/';
		$this->imgRootUrl = $url;
	}

	public function setImgRootPath($url){
		if(substr($url, -1) != '/') $url .= '/';
		$this->imgRootPath = $url;
	}

	public function setImgSubPath($path){
		$this->imgSubPath = $path;
	}
}
?>