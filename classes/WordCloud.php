<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class WordCloud{

	private $conn;
	private $frequencyArr = array();
	private $commonWordArr = array();

	//custom parameters
	private $displayedWordCount;
	private $tagUrl;
	private $backgroundImage;
	private $backgroundColor;
	private $cloudWidth;
	private $wordColors;
	private $supportUtf8 = true;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');

		$this->displayedWordCount = 150;
		if($GLOBALS['CHARSET'] == 'ISO-8859-1') $this->supportUtf8 = false;
		//$this->tagUrl = "https://www.google.com/search?hl=en&q=";
		$this->tagUrl = $GLOBALS['CLIENT_ROOT'].'/collections/editor/occurrencetabledisplay.php?occindex=0&reset=1&q_processingstatus=unprocessed';
		$this->backgroundColor = "#000";
		$this->wordColors[0] = "#5122CC";
		$this->wordColors[1] = "#229926";
		$this->wordColors[2] = "#330099";
		$this->wordColors[3] = "#819922";
		$this->wordColors[4] = "#22CCC3";
		$this->wordColors[5] = "#99008D";
		$this->wordColors[6] = "#943131";
		$this->wordColors[7] = "#B23B3B";
		$this->wordColors[8] = "#229938";
		$this->wordColors[9] = "#419922";

		$commonWordStr = 'a,able,about,across,after,all,almost,also,am,among,an,and,any,are,arent,as,at,be,because,been,but,by,can,cant,cannot,could,couldve,couldnt,dear,did,didnt,do,'.
			'does,doesnt,dont,either,else,ever,every,for,from,get,got,had,has,hasnt,have,he,her,him,his,how,however,i,if,in,into,is,isnt,it,its,just,least,let,like,likely,may,me,might,'.
			'most,must,my,neither,no,nor,not,of,off,often,on,only,or,other,our,own,rather,said,say,says,she,should,since,so,some,than,that,the,their,them,then,there,theres,these,they,this.'.
			'to,too,us,wants,was,wasnt,we,were,werent,what,when,when,where,which,while,who,whom,why,will,with,wont,would,wouldve,wouldnt,yet,you,your';
		$this->commonWordArr = explode(',', $commonWordStr);
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function batchBuildWordClouds($csMode = 0){
		$processingArr = array();
		$sql = 'SELECT DISTINCT c.collid FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid ';
		if($csMode) $sql .= 'INNER JOIN omcrowdsourcequeue q ON o.occid = q.occid ';
		$sql .= 'WHERE o.processingstatus = "unprocessed" AND o.locality IS NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$processingArr[] = $r->collid;
		}
		$rs->free();
		foreach($processingArr as $collid){
			$this->buildWordCloud($collid, $csMode);
		}
	}

	public function buildWordCloud($collid, $csMode = 0){
		$retPath = '';
		//Reset frequency array
		unset($this->frequencyArr);
		$this->frequencyArr = array();
		$this->tagUrl .= '&collid='.$collid.'&q_customfield1=ocrFragment&q_customtype1=LIKE&q_customvalue1=';
		$sql = 'SELECT DISTINCT r.rawstr FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid ';
		if($csMode) $sql .= 'INNER JOIN omcrowdsourcequeue q ON o.occid = q.occid ';
		$sql .= 'WHERE o.processingstatus = "unprocessed" AND o.locality IS NULL ';
		if($collid) $sql .= 'AND o.collid = '.$collid;
		//echo $sql; exit;
		//Process all raw OCR strings for collection
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->addTagsFromText($r->rawstr);
		}
		$rs->free();
		//Get Word cloud
		$cloudStr = $this->getWordCloud();
		if(!$cloudStr){
			echo '<div>No phrases created</div><div>sql: '.$sql.'</div>';
			exit;
		}
		//echo $cloudStr.'<br/><br/>';
		//$cloudHtml = $this->getCloudHtmlWrapper($cloudStr);
		$serverRoot = $GLOBALS['SERVER_ROOT'];
		if(substr($serverRoot,-1) != '/') $serverRoot .= '/';
		$wcPath = 'content/collections/wordclouds/ocrcloud_'.$collid.($csMode?'_cs':'').'.php';
		if($wcFH = fopen($serverRoot.$wcPath, 'w')){
			if(fwrite($wcFH, $cloudStr) === FALSE) {
				echo 'Cannot write to file ('.$wcPath.')';
				exit;
			}
			fclose($wcFH);
			$clientRoot = $GLOBALS['CLIENT_ROOT'];
			if(substr($clientRoot,-1) != '/') $clientRoot .= '/';
			$retPath = $clientRoot.$wcPath;
		}
		else{
			echo 'Cannot open file for writing ('.$wcPath.')';
			exit;
		}
		return $retPath;
	}

	private function addTagsFromText($seedText){
		//$text = strtolower($seedText);
		//$text = strip_tags($text);

		$seedText = preg_replace('/[;,\r\t]/',"\n",$seedText);
		//$seedText = preg_replace('/[^\p{L}0-9\s.-]|\n|\r/u',' ',$seedText);

		/* remove extra spaces created */
		$seedText = preg_replace('/\s+/',' ',trim($seedText));

		//$wordArr = array_diff(explode(" ", $seedText),$this->commonWordArr);
		$phraseArr = explode("\n", $seedText);

		foreach($phraseArr as $phrase){
			$tag = '';
			$wordCnt = 0;
			foreach(explode(' ',$phrase) as $word){
				if($this->keepWord($word) && $wordCnt < 3){
					$tag .= $word.' ';
					$wordCnt++;
				}
				elseif($tag){
					if($wordCnt > 1) $this->addTag(trim($tag,' .'));
					$tag = '';
					$wordCnt = 0;
				}
			}
			//if(strlen($value) > 3 && !is_numeric($value)) $this->addTag($value);
		}
	}

	private function keepWord($word){
		if(strlen($word) < 3) return false;
		if(!preg_match('/^[A-Z]{1}[A-Za-z.]+/',$word)) return false;
		if(in_array(strtolower($word),$this->commonWordArr)) return false;
		return true;
	}

	private function addTag($tag, $useCount = 1){
		//$tag = strtolower($tag);
		if (array_key_exists($tag, $this->frequencyArr)){
			$this->frequencyArr[$tag] += $useCount;
		}
		else{
			$this->frequencyArr[$tag] = $useCount;
		}
	}

	private function getWordCloud(){
		$retStr = '';
		if($this->frequencyArr){
			$retStr = '<div id="id_tag_cloud" style="' . (isset($this->cloudWidth) ? ("width:". $this->cloudWidth. ";") : "") .
				'line-height:normal"><div style="border-style:solid;border-width:1px;' .
				(isset($this->backgroundImage) ? ("background:url('". $this->backgroundImage ."');") : "") .
				'border-color:#888;margin-top:20px;margin-bottom:10px;padding:5px 5px 20px 5px;background-color:'.$this->backgroundColor.';">';
			arsort($this->frequencyArr);
			$topTags = array_slice($this->frequencyArr, 0, $this->displayedWordCount);

			/* randomize the order of elements */
			uasort($topTags, function ($a, $b){ return rand(-1, 1); });

			$maxCount = max($this->frequencyArr);
			foreach ($topTags as $tag => $useCount){
				$grade = $this->gradeFrequency(($useCount * 100) / $maxCount);
				$retStr .= ('<a href="'. $this->tagUrl.urlencode($tag).'" style="color:' . $this->wordColors[$grade] . ';" target="_blank">'.
					'<span style="color:'.$this->wordColors[$grade].'; letter-spacing:3px; '.
					'padding:4px;  font-weight:900; font-size:'.
					(0.6 + 0.1 * $grade).'em">'.$tag.'</span></a> ');
			}
			$retStr .= '</div>';
			$retStr .= '<div style="width:100%;text-align:center;background-color:white">Created on '.date('Y-m-d H:i:s').'</div>';
			$retStr .= '</div>';
		}
		return $retStr;
	}

	private function getCloudHtmlWrapper($cloudStr){
		$htmlStr = '<!DOCTYPE html><html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>'.$GLOBALS['DEFAULT_TITLE'].' - Word Cloud </title>';
		$htmlStr .= '<?php

		include_once($SERVER_ROOT."/includes/head.php");
		?>';
		$htmlStr .= '
	</head>
	<body>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Word Cloud</h1>';
		$htmlStr .= $cloudStr;
		$htmlStr .= '		</div>
	</body>
</html>';
		return $htmlStr;
	}

	private function gradeFrequency($frequency){
		$grade = 0;
		if ($frequency >= 90)
			$grade = 9;
		else if ($frequency >= 70)
			$grade = 8;
		else if ($frequency >= 60)
			$grade = 7;
		else if ($frequency >= 50)
			$grade = 6;
		else if ($frequency >= 40)
			$grade = 5;
		else if ($frequency >= 30)
			$grade = 4;
		else if ($frequency >= 20)
			$grade = 3;
		else if ($frequency >= 10)
			$grade = 2;
		else if ($frequency >= 5)
			$grade = 1;

		return $grade;
	}

	//Setters and getters
	public function setDisplayedWordCount($cnt){
		$this->displayedWordCount = $cnt;
	}

	public function setSearchURL($searchURL){
		$this->tagUrl = $searchURL;
	}

	public function setUTF8($bUTF8){
		$this->supportUtf8 = $bUTF8;
	}

	public function setWidth($width){
		$this->cloudWidth = $width;
	}

	public function setBackgroundImage($backgroundImage){
		$this->backgroundImage = $backgroundImage;
	}

	public function setBackgroundColor($backgroundColor){
		$this->backgroundColor = $backgroundColor;
	}

	public function setTextColors($colors){
		if(is_array($colors)){
			$this->wordColors = $colors;
		}
	}
}
?>