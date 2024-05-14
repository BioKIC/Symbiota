<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = filter($_POST['collid'], FILTER_SANITIZE_NUMBER_INT);
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN) $isEditor = 1;
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($labelManager->getCollid(),$USER_RIGHTS["CollAdmin"])) $isEditor = 1;
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($labelManager->getCollid(),$USER_RIGHTS["CollEditor"])) $isEditor = 1;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Labels</title>
		<style type="text/css">
			body { background-color:#ffffff;font-family:arial,sans-serif; font-size:10pt; }
			.barcode { width:220px; height:50px; float:left; padding:10px; text-align:center; }
			.screen-reader-only {
				position: absolute;
				left: -10000px;
			}
		</style>
	</head>
	<body>
		<h1 class="page-heading screen-reader-only">Labels</h1>
		<div>
			<?php
			if($action && $isEditor){
				$labelArr = $labelManager->getLabelArray($_POST['occid']);
				$labelCnt = 0;
				echo '<table class="labels"><tr>';
				foreach($labelArr as $occid => $occArr){
					if($occArr['catalognumber']){
						?>
						<div class="barcode">
							<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
						</div>
						<?php
						$labelCnt++;
					}
				}
				if(!$labelCnt) echo '<div style="font-weight:bold;text-size: 120%">No records were retrieved. Perhaps the quantity values were all set to 0?</div>';
			}
			?>
		</div>
	</body>
</html>