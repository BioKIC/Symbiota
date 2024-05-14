<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpBryophyte.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpLichen.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpSalix.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$parserTarget = $_REQUEST['parser'];
$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);

$nlpManager = null;
if($parserTarget == 'lbcc'){
	$nlpManager = new SpecProcNlpLbcc();
}
else{
	$nlpManager = new SpecProcNlpSalix();
}
//$nlpManager->setCollId($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

$status = "";
if($isEditor){
	if($action == ''){
		//$status = $nlpManager->addProfile($_REQUEST);
	}
}
?>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1><?php echo $LANG['NLP_PROCESSOR']; ?></h1>
	<?php 
	if($status){ 
		?>
		<div style='margin:20px 0px 20px 0px;'>
			<hr/>
			<?php echo $status; ?>
			<hr/>
		</div>
		<?php 
	}
	if($isEditor && $collid){
		$unprocessedCnt = $procManager->getProcessingStatusCount('unprocessed');
		?>
		<div style="height:400px;">
			<div style="margin:5px;">
				<?php echo $LANG['UNPROCESSED_SPECS']; ?>: 
				<?php 
				echo $unprocessedCnt; 
				?>
			</div>
			<div style="margin:5px;">
				<?php echo $LANG['UNPROCESSED_SPECS_NO_IMGS']; ?>: 
				<?php 
				echo $procManager->getUnprocSpecNoImage(); 
				?>
			</div>
			<div style="margin:5px;">
				<?php echo $LANG['UNPROCESSED_SPECS_NO_OCR']; ?>: 
				<?php 
				echo $procManager->getSpecNoOcr(); 
				?>
			</div>
		</div>
		<?php
		if($unprocessedCnt){
			?>
			<div>
				
			</div>
			<?php
		}
		else{
			echo '<div>' . $LANG['NO_UNPROCESSED'] .' </div>';
		}
	}
	else{
		?>
		<div style='font-weight:bold;color:red;'>
			<?php echo $LANG['UNIDENTIFIED_ERROR']; ?>
		</div>
		<?php
	}
	?>
</div>
