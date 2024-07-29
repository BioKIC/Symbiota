<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$buildThumbnails = array_key_exists("buildthumbnails",$_REQUEST)?$_REQUEST["buildthumbnails"]:0;

$verifyPathsObj = new VerifyPaths();

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Verify Image Paths</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_misc_verifypathsMenu)?$imagelib_misc_verifypathsMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($imagelib_misc_verifypathsCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_misc_verifypathsCrumbs;
		echo " <b>Verify Paths</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Image Path Verification</h1>
		<div>Clicking the button below will go through all the images in the image directory and
		verify mapping within the database. Two following two error files will be produced:</div>

		<div style="margin:10px 0px 0px 10px;">Images in Directory but not in Database (= absent link): <?php echo $verifyPathsObj->getErrorAbsentDbRecordsLogPath(); ?></div>
		<div style="margin:5px 0px 20px 10px;">Images in Database but not in Directory (= broken link): <?php echo $verifyPathsObj->getErrorAbsentFilesLogPath(); ?></div>

		<?php
			if($action){
				$verifyPathsObj->searchAndVerify($buildThumbnails);
			}
		?>

		<form name="startprocessform" action="verifypaths.php" method="post">
			<div>
				<input type="checkbox" name="buildthumbnails" value="1"> Build thumbnails for images without
			</div>
			<div>
				<input type="submit" name="action" value="Start Verification Process">
			</div>
		</form>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>

</body>
</html>

<?php
class VerifyPaths{

	private $rootPath = "";
	private $urlPath = "";
	private $errorAbsentFilesLogName = "ErrorAbsentImageFiles.log";
	private $errorAbsentDbRecordsLogName = "ErrorAbsentDbRecords.log";
	private $absentDbRecordsFH;
	private $orphanedImgCnt = 0;
	private $buildThumbnails = true;
	private $tempRoot = "";
	private $conn;
	private $imageArr = Array();
	private $thumbnailArr = Array();

	function __construct() {
		$this->rootPath = $GLOBALS['IMAGE_ROOT_PATH'];
		if(substr($this->rootPath,-1) != "/") $this->rootPath .= "/";
		$this->urlPath = $GLOBALS['IMAGE_ROOT_URL'];
		if(substr($this->urlPath,-1) != "/") $this->urlPath .= "/";
		$this->tempRoot = $GLOBALS['TEMP_DIR_ROOT'];
		if(!$this->tempRoot){
			$this->tempRoot = ini_get('upload_tmp_dir');
		}
		if(substr($this->tempRoot,-1) != "/") $this->tempRoot .= "/";
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
		$this->conn->close();
	}

	public function getErrorAbsentFilesLogPath(){
		return $this->tempRoot.$errorAbsentFilesLogName;
	}

	public function getErrorAbsentDbRecordsLogPath(){
		return $this->tempRoot.$errorAbsentDbRecordsLogName;
	}

	public function searchAndVerify($buildTns = true){
		$this->buildThumbnails = $buildTns;
		$errorAbsentFilesLog = $this->tempRoot."images/".$this->errorAbsentFilesLogName;
		$errorAbsentDbRecordsLog = $this->tempRoot."images/".$this->errorAbsentDbRecordsLogName;
		$absentFilesFH = fopen($errorAbsentFilesLog, 'w')
			or die("Can't open file: ".$errorAbsentFilesLog);
		$this->absentDbRecordsFH = fopen($errorAbsentDbRecordsLog, 'w')
			or die("Can't open file: ".$errorAbsentDbRecordsLog);
		set_time_limit(600);
		$this->loadImageArr();
		$this->evaluateFolder($this->rootPath);
		foreach($this->imageArr as $k => $v){
			echo "Not in database:\t ".$k."\t ".$v."<br/>";
			fwrite($absentFilesFH, $k."\t".$v."\n");
		}
		fclose($absentFilesFH);
		fclose($this->absentDbRecordsFH);
	}

	private function evaluateFolder($dirPath){
		if($handle = opendir($dirPath)){
			$urlPath = str_replace($this->rootPath,$this->urlPath,$dirPath);
			while(false !== ($file = readdir($handle))){
        		if($file != "." && $file != ".." && !stripos($file,"_tn.jpg")) {
        			if(is_file($dirPath.$file)){
						if(stripos($file,".jpg")){
							$imgId = $this->inImageArr($urlPath.$file);
							if($imgId){
								if(!array_key_exists($imgId,$this->thumbnailArr)){
									if($this->buildThumbnails) $this->createThumbnail($dirPath.$file, $imgId);
								}
							}
							else{
								$this->orphanedImgCnt++;
								echo "<div>Orphaned #$this->orphanedImgCnt: $urlPath$file</div>";
								fwrite($this->absentDbRecordsFH, "Orphaned #$this->orphanedImgCnt: ".$urlPath.$file."\n");
							}
						}
					}
					elseif(is_dir($dirPath.$file)){
						$this->evaluateFolder($dirPath.$file."/");
					}
        		}
			}
		}
   		closedir($handle);
	}

	private function createThumbnail($filePath, $imgId){
		$newThumbnailPath = str_ireplace(".jpg","_tn.jpg",$filePath);
		$newThumbnailUrl = str_replace($this->rootPath,$this->urlPath,$newThumbnailPath);
		$idealWidth = 250;
		$maxHeight = 300;
		if(file_exists($filePath)){
			if(!file_exists($newThumbnailPath)){
				list($sourceWidth, $sourceHeight, $imageType) = getimagesize(str_replace(' ', '%20', $filePath));
	        	$newWidth = $idealWidth;
	        	$newHeight = round($sourceHeight*($idealWidth/$sourceWidth));
	        	if($newHeight > $maxHeight){
	        		$newHeight = $maxHeight;
	        		$newWidth = round($sourceWidth*($maxHeight/$sourceHeight));
	        	}

			    switch ($imageType){
			        case 1:
			        	$sourceImg = imagecreatefromgif($filePath);
			        	break;
			        case 2:
			        	$sourceImg = imagecreatefromjpeg($filePath);
			        	break;
			        case 3:
			        	$sourceImg = imagecreatefrompng($filePath);
			        	break;
			        default: return '';  break;
			    }

	    		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);

			    /* Check if this image is PNG or GIF to preserve its transparency */
			    if(($imageType == 1) || ($imageType==3)){
			        imagealphablending($tmpImg, false);
			        imagesavealpha($tmpImg,true);
			        $transparent = imagecolorallocatealpha($tmpImg, 255, 255, 255, 127);
			        imagefilledrectangle($tmpImg, 0, 0, $newWidth, $newHeight, $transparent);
			    }
				imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

				switch ($imageType){
			        case 1:
			        	if(!imagegif($tmpImg,$newThumbnailPath)){
			        		echo "<div>Failed to write GIF thumbnail: $newThumbnailPath</div>";
			        	}
			        	break;
			        case 2:
			        	if(!imagejpeg($tmpImg, $newThumbnailPath, 50)){
			        		echo "<div>Failed to write JPG thumbnail: $newThumbnailPath</div>";
			        	}
			        	break; // best quality
			        case 3:
			        	if(!imagepng($tmpImg, $newThumbnailPath, 0)){
			        		echo "<div>Failed to write PNG thumbnail: $newThumbnailPath</div>";
			        	}
			        	break; // no compression
			    }
				imagedestroy($tmpImg);
			}

		    if(file_exists($newThumbnailPath)){
			    //Insert thumbnail path into database
				$con = MySQLiConnectionFactory::getCon("write");
		    	$sql = "UPDATE images ti SET ti.thumbnailurl = '".$newThumbnailUrl."' WHERE ti.imgid = ".$imgId;
			    $con->query($sql);
			    echo "<div>Thumbnail Created: $imgId - $newThumbnailUrl</div>";
		    }
		}
	}

	private function loadImageArr(){
		$sql = "SELECT ti.imgid, ti.url, ti.thumbnailurl FROM images ti ";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$this->imageArr[$row->imgid] = $row->url;
			if($row->thumbnailurl){
				$thumbnailArr[$row->imgid] = $row->thumbnailurl;
			}
		}
		$result->close();
	}

	private function inImageArr($str){
    	foreach($this->imageArr as $k=>$v){
        	if(strtolower($v)==strtolower($str)){
            	unset($this->imageArr[$k]);
            	return $k;
        	}
    	}
		return false;
	}
}
?>