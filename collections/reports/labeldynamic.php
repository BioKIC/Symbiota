<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');

$collid = $_POST['collid'];
$hPrefix = $_POST['hprefix'];
$hMid = $_POST['hmid'];
$hSuffix = $_POST['hsuffix'];
$lFooter = $_POST['lfooter'];
$columnCount = $_POST['labeltype'];
$labelformatindex = (isset($_POST['labelformatindex'])?$_POST['labelformatindex']:'');
$showcatalognumbers = ((array_key_exists('catalognumbers',$_POST) && $_POST['catalognumbers'])?1:0);
$useBarcode = array_key_exists('bc',$_POST)?$_POST['bc']:0;
$useSymbBarcode = array_key_exists('symbbc',$_POST)?$_POST['symbbc']:0;
$barcodeOnly = array_key_exists('bconly',$_POST)?$_POST['bconly']:0;
$includeSpeciesAuthor = ((array_key_exists('speciesauthors',$_POST) && $_POST['speciesauthors'])?1:0);
$outputType = array_key_exists('outputtype',$_POST)?$_POST['outputtype']:'html';
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

//Sanitation
$hPrefix = strip_tags($hPrefix, '<br><b><u><i>');
$hMid = strip_tags($hMid, '<br><b><u><i>');
$hSuffix = strip_tags($hSuffix, '<br><b><u><i>');
$lFooter = strip_tags($lFooter, '<br><b><u><i>');
$labelCat = substr($labelformatindex,0,1);
if($labelCat == 'g') $labelCat = 'global';
elseif($labelCat == 'c') $labelCat = 'coll';
elseif($labelCat == 'u') $labelCat = 'user';
else $labelCat = '';
$labelIndex = substr($labelformatindex,2);
if(!is_numeric($labelIndex)) $labelIndex = '';
if(!is_numeric($columnCount) && $columnCount != 'packet') $columnCount = 2;
if(!is_numeric($showcatalognumbers)) $showcatalognumbers = 0;
if(!is_numeric($useBarcode)) $useBarcode = 0;
if(!is_numeric($useSymbBarcode)) $useSymbBarcode = 0;
if(!is_numeric($barcodeOnly)) $barcodeOnly = 0;
if(!is_numeric($includeSpeciesAuthor)) $includeSpeciesAuthor = 0;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

if($outputType == 'word'){
	header("Content-Type: application/vnd.ms-word; charset=".$CHARSET);
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("content-disposition: attachment;filename=labels.doc");
}
elseif($action == 'Export to CSV'){
	$labelManager->exportLabelCsvFile($_POST);
	exit;
}
else{
	header("Content-Type: text/html; charset=".$CHARSET);
}

$targetLabelFormatArr = $labelManager->getLabelFormatByID($labelCat,$labelIndex);

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
			.row { display: flex; flex-wrap: nowrap; margin-left: auto; margin-right: auto;}
			.label { page-break-before: auto; page-break-inside: avoid; }
			.screen-reader-only {
				position: absolute;
				left: -10000px;
			}
			<?php
			if($columnCount == 'packet'){
				?>
				.foldMarks1 { clear:both;padding-top:285px; }
				.foldMarks1 span { margin-left:77px; margin-right:80px; }
				.foldMarks2 { clear:both;padding-top:355px;padding-bottom:10px; }
				.foldMarks2 span { margin-left:77px; margin-right:80px; }
				.label {
					margin-left: auto;
					margin-right: auto;
					width: 500px;
					page-break-before:auto;
					page-break-inside:avoid;
				}
				.label {
					width:500px;
					margin:50px;
					padding:10px 50px;
					font-size: 80%;
				}
				.family { display:none }
				<?php
			}
			elseif($columnCount != 1){
				?>
				.label { width:<?php echo (floor(90/$columnCount)-floor($columnCount/4));?>%;padding:10pt; }
				<?php
			}
			?>
			/* Move to custom? Move to packets? */
			/* .cnBarcodeDiv { clear:both; padding-top:15px; }
			.catalogNumber { clear:both; text-align:center; }
			.otherCatalogNumbers { clear:both; text-align:center; }
			.symbBarcode { padding-top:10px; } */
			.label-header { clear:both; text-align: center }
			.label-footer { clear:both; text-align: center; font-weight: bold; font-size: 12pt; }
			@media print { .controls { display: none; } }
		</style>
		<?php
		if(isset($targetLabelFormatArr['defaultCss']) && $targetLabelFormatArr['defaultCss']){
			$cssPath = $targetLabelFormatArr['defaultCss'];
			if(substr($cssPath,0,1) == '/' && !file_exists($cssPath)){
				if(file_exists($SERVER_ROOT.$targetLabelFormatArr['defaultCss'])) $cssPath = $CLIENT_ROOT.$targetLabelFormatArr['defaultCss'];
			}
			echo '<link href="' . htmlspecialchars($cssPath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" type="text/css" rel="stylesheet" />'."\n";
		}
		if(isset($targetLabelFormatArr['customCss']) && $targetLabelFormatArr['customCss']){
			$cssPath = $targetLabelFormatArr['customCss'];
			if(substr($cssPath,0,1) == '/' && !file_exists($cssPath)){
				if(file_exists($SERVER_ROOT.$targetLabelFormatArr['customCss'])) $cssPath = $CLIENT_ROOT.$targetLabelFormatArr['customCss'];
			}
			echo '<link href="' . htmlspecialchars($cssPath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" type="text/css" rel="stylesheet" />'."\n";
		}
		?>
		<style>
			<?php
			if(isset($targetLabelFormatArr['customStyles'])) echo $targetLabelFormatArr['customStyles'];
			?>
		</style>
	</head>
	<body style="background-color:#ffffff;">
		<h1 class="page-heading screen-reader-only">Labels</h1>
		<?php
		echo '<div class="body'.(isset($targetLabelFormatArr['pageSize'])?' '.$targetLabelFormatArr['pageSize']:'').'">'  ;
		if($targetLabelFormatArr && $isEditor){
			$labelArr = $labelManager->getLabelArray($_POST['occid'], $includeSpeciesAuthor);
			$labelCnt = 0;
			$rowCnt = 0;
			foreach($labelArr as $occid => $occArr){
				if($barcodeOnly){
					if($occArr['catalognumber']){
						?>
						<div class="barcodeonly">
							<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
						</div>
						<?php
						$labelCnt++;
					}
				}
				else{
					//Build label header string
					$midStr = '';
					if($hMid == 1) $midStr = $occArr['country'];
					elseif($hMid == 2) $midStr = $occArr['stateprovince'];
					elseif($hMid == 3) $midStr = $occArr['county'];
					elseif($hMid == 4) $midStr = $occArr['family'];
					$headerStr = '';
					if($hPrefix || $midStr || $hSuffix){
						$headerStrArr = array();
						$headerStrArr[] = $hPrefix;
						$headerStrArr[] = trim($midStr ?? '');
						$headerStrArr[] = $hSuffix;
						$headerStr = implode("",$headerStrArr);
					}

					$dupCnt = $_POST['q-'.$occid];
					for($i = 0;$i < $dupCnt;$i++){
						$labelCnt++;
						if($columnCount == 'packet'){
							echo '<div class="page"><div class="foldMarks1"><span style="float:left;">+</span><span style="float:right;">+</span></div>';
							echo '<div class="foldMarks2"><span style="float:left;">+</span><span style="float:right;">+</span></div>';
						}
						elseif($labelCnt%$columnCount == 1){
							if($labelCnt > 1) echo '</div>';
							echo '<div class="row">';
							$rowCnt++;
						}
						echo '<div class="label'.(isset($targetLabelFormatArr['labelDiv']['className'])?' '.$targetLabelFormatArr['labelDiv']['className']:'').'">';
						$attrStr = 'class="label-header';
						if(isset($targetLabelFormatArr['labelHeader']['className'])) $attrStr .= ' '.$targetLabelFormatArr['labelHeader']['className'];
						$attrStr .= '"';
						if(isset($targetLabelFormatArr['labelHeader']['style']) && $targetLabelFormatArr['labelHeader']['style']) $attrStr .= ' style="'.$targetLabelFormatArr['labelHeader']['style'].'"';
						echo '<div '.trim($attrStr).'>'.$headerStr.'</div>';
						//Output field data
						echo $labelManager->getLabelBlock($targetLabelFormatArr['labelBlocks'],$occArr);
						if($useBarcode && $occArr['catalognumber']){
							?>
							<div class="cn-barcode">
								<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
							</div>
							<?php
							if($occArr['othercatalognumbers']){
								?>
								<div class="other-catalog-numbers">
									<?php echo $occArr['othercatalognumbers']; ?>
								</div>
								<?php
							}
						}
						elseif($showcatalognumbers){
							if($occArr['catalognumber']){
								?>
								<div class="catalog-number">
									<?php echo $occArr['catalognumber']; ?>
								</div>
								<?php
							}
							if($occArr['othercatalognumbers']){
								?>
								<div class="other-catalog-numbers">
									<?php echo $occArr['othercatalognumbers']; ?>
								</div>
								<?php
							}
						}
						if($lFooter) echo '<div class="label-footer'.(isset($targetLabelFormatArr['labelFooter']['className'])?' '.$targetLabelFormatArr['labelFooter']['className']:'').'" '.(isset($targetLabelFormatArr['labelFooter']['style'])?'style="'.$targetLabelFormatArr['labelFooter']['style'].'"':'').'>'.$lFooter.'</div>';
						if($useSymbBarcode){
							?>
							<hr style="border:dashed;" />
							<div class="symb-barcode">
								<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occid; ?>" />
							</div>
							<?php
							if($occArr['catalognumber']){
								?>
								<div class="catalog-number">
									<?php echo $occArr['catalognumber']; ?>
								</div>
								<?php
							}
						}
						if($columnCount == 'packet'){
							echo '</div>';
						}
						echo '</div>';
					}
				}
			}
			echo '</div>'; //Closing row
			if(!$labelCnt) echo '<div style="font-weight:bold;text-size: 120%">No records were retrieved. Perhaps the quantity values were all set to 0?</div>';
		}
		else{
			echo '<div style="font-weight:bold;text-size: 120%">';
			if($targetLabelFormatArr) echo 'ERROR: Unable to parse JSON that defines the label format profile ';
			else 'ERROR: Permissions issue';
			echo '</div>';
		}
		echo '</div>';
		?>
	</body>
	<?php
	if(isset($targetLabelFormatArr['customJS']) && $targetLabelFormatArr['customJS']){
		$jsPath = $targetLabelFormatArr['customJS'];
		if(substr($jsPath,0,1) == '/' && !file_exists($jsPath)){
			if(file_exists($SERVER_ROOT.$targetLabelFormatArr['customJS'])) $jsPath = $CLIENT_ROOT.$targetLabelFormatArr['customJS'];
		}
		echo '<script src="'.$jsPath.'"></script>'."\n";
	}
	?>
	<script src="../../js/symb/collections.labeldynamic.js"></script>
</html>
