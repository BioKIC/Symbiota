<?php
include_once('TPEditorManager.php');
include_once('ImageShared.php');

class TPImageEditorManager extends TPEditorManager{

 	public function __construct(){
 		parent::__construct();
		set_time_limit(120);
		ini_set('max_input_time',120);
 	}

 	public function __destruct(){
 		parent::__destruct();
 	}

	public function getImages(){
		$imageArr = array();
		$tidArr = array($this->tid);
		if($this->rankid == 220){
			$sql1 = 'SELECT DISTINCT tid FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = tidaccepted) AND (parenttid = '.$this->tid.')';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$tidArr[] = $r1->tid;
			}
			$rs1->free();
		}

		$sql = 'SELECT m.mediaID, m.url, m.thumbnailurl, m.originalurl, m.caption, m.creator, m.creatorUid, CONCAT_WS(" ",u.firstname,u.lastname) AS creatorDisplay,
			m.owner, m.locality, m.occid, m.notes, m.sortsequence, m.sourceurl, m.copyright, t.tid, t.sciname ';
		if($this->acceptance){
			$sql .= 'FROM media m INNER JOIN taxstatus ts ON m.tid = ts.tid
				INNER JOIN taxa t ON m.tid = t.tid
				LEFT JOIN users u ON m.creatoruid = u.uid
				WHERE ts.taxauthid = '.$this->taxAuthId.' AND (ts.tidaccepted IN('.implode(",",$tidArr).')) AND m.SortSequence < 500 ';
		}
		else{
			$sql .= 'FROM media i INNER JOIN taxa t ON m.tid = t.tid
				LEFT JOIN users u ON m.creatorUid = u.uid
				WHERE (t.tid IN('.implode(",",$tidArr).')) AND m.SortSequence < 500 ';
		}
		$sql .= 'ORDER BY m.sortsequence';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		$imgCnt = 0;
		while($r = $rs->fetch_object()){
			$imageArr[$imgCnt]['mediaid'] = $r->mediaID;
			$imageArr[$imgCnt]['url'] = $r->url;
			$imageArr[$imgCnt]['thumbnailurl'] = $r->thumbnailurl;
			$imageArr[$imgCnt]['originalurl'] = $r->originalurl;
			$imageArr[$imgCnt]['creator'] = $r->creator;
			$imageArr[$imgCnt]['creatorUid'] = $r->creatorUid;
			if($r->creatorDisplay) $imageArr[$imgCnt]['creatorDisplay'] = $r->creatorDisplay;
			else $imageArr[$imgCnt]['creatorDisplay'] = $r->creator;
			$imageArr[$imgCnt]['caption'] = $r->caption;
			$imageArr[$imgCnt]['owner'] = $r->owner;
			$imageArr[$imgCnt]['locality'] = $r->locality;
			$imageArr[$imgCnt]['sourceurl'] = $r->sourceurl;
			$imageArr[$imgCnt]['copyright'] = $r->copyright;
			$imageArr[$imgCnt]['occid'] = $r->occid;
			$imageArr[$imgCnt]['notes'] = $r->notes;
			$imageArr[$imgCnt]['tid'] = $r->tid;
			$imageArr[$imgCnt]['sciname'] = $r->sciname;
			$imageArr[$imgCnt]['sortsequence'] = $r->sortsequence;
			$imgCnt++;
		}
		$rs->free();
		return $imageArr;
	}

	public function echoPhotographerSelect($userId = 0){
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS fullname FROM users u ORDER BY u.lastname, u.firstname ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			echo '<option value="'.$r->uid.'" '.($r->uid == $userId?'SELECTED':'').'>'.$r->fullname.'</option>';
		}
		$rs->free();
	}

	public function editImageSort($imgSortEdits){
		$status = "";
		foreach($imgSortEdits as $editKey => $editValue){
			if(is_numeric($editKey) && is_numeric($editValue)){
				$sql = 'UPDATE media SET sortsequence = '.$editValue.' WHERE mediaID = '.$editKey;
				//echo $sql;
				if(!$this->conn->query($sql)){
					$status .= $this->conn->error."\nSQL: ".$sql."; ";
				}
			}
		}
		if($status) $status = "with editImageSort method: ".$status;
		return $status;
	}

	public function loadImage($postArr){
		$status = true;
		$imgManager = new ImageShared();
		$imgManager->setTid($this->tid);
		$imgManager->setCaption($postArr['caption']);
		if($postArr['photographer']){
			$imgManager->setPhotographer($postArr['photographer']);
		} else {
			$imgManager->setPhotographerUid($postArr['photographeruid']);
		}
		$imgManager->setSourceUrl($postArr['sourceurl']);
		$imgManager->setCopyright($postArr['copyright']);
		$imgManager->setOwner($postArr['owner']);
		$imgManager->setLocality($postArr['locality']);
		$imgManager->setOccid($postArr['occid']);
		$imgManager->setNotes($postArr['notes']);
		$sort = $postArr['sortsequence'];
		if(!$sort) $sort = 40;
		$imgManager->setSortSeq($sort);

		$imgManager->setTargetPath(($this->family?$this->family.'/':'').date('Ym').'/');
		$imgPath = $postArr['filepath'];
		if($imgPath){
			$imgManager->setMapLargeImg(true);
			$imgManager->parseUrl($imgPath);
			$importUrl = (array_key_exists('importurl',$postArr) && $postArr['importurl']==1?true:false);
			if($importUrl) $imgManager->copyImageFromUrl();
		}
		else{
			$createLargeImg = false;
			if(array_key_exists('createlargeimg',$postArr) && $postArr['createlargeimg']==1) $createLargeImg = true;
			$imgManager->setMapLargeImg($createLargeImg);
			if(!$imgManager->uploadImage()){
				//echo implode('; ',$imgManager->getErrArr());
			}
		}
		if(!$imgManager->processImage()){
			$this->errorMessage = implode('<br/>',$imgManager->getErrArr());
			$status = false;
		}
		return $status;
	}
}
?>
