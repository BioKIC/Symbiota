<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcDuplicates.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$fieldTarget = array_key_exists('fieldtarget',$_POST)?$_POST['fieldtarget']:'';
$matchMethod = array_key_exists('matchmethod',$_POST)?$_POST['matchmethod']:'';
$evaluationDate = array_key_exists('evaldate',$_POST)?$_POST['evaldate']:'';
$processingStatus = array_key_exists('processingstatus',$_POST)?$_POST['processingstatus']:'';
$limit = array_key_exists('limit',$_POST)?$_POST['limit']:100;
$action = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/duplicateharvest.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!in_array($fieldTarget,array('all','georef'))) $fieldTarget = '';
if(!in_array($matchMethod,array('dupe','exsiccati'))) $matchMethod = '';
if(!preg_match('/^\d{4}\/\d{2}\/\d{2}$/',$evaluationDate)) $evaluationDate = '';
if(!in_array($processingStatus,array('stage1','stage2','stage3','unprocessed'))) $processingStatus = '';
if(!is_numeric($limit)) $limit = 100;

$dupeManager = new SpecProcDuplicates();
if($collid) $dupeManager->setCollID($collid);

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))) $isEditor = 1;

$dupArr = array();
if($action == 'buildDuplicateArr'){
	$dupArr = $dupeManager->buildDuplicateArr($matchMethod, $evaluationDate, $processingStatus, $limit);
}
$collMetaArr = $dupeManager->getCollMetaArr();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE .  ' - ' . $LANG['DUP_GEOREFERENCE']; ?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">

	</script>
	<style type="text/css">
		#page-title{ margin-bottom: 10px; }
		fieldset{ margin: 10px; display: inline-block; min-width: 400px }
		legend{ font-weight: bold; }
		.fieldGroup{ clear:both; margin: 3px 0px; }
		.fieldLabel{ margin-left: 3px; }
		table{ border: 1px solid black; border-collapse: collapse; background-color: #ffffff; }
		table th{ border: 2px solid black; background-color: #dbe5f1; }
		table td{ border: 2px solid black; }
		.source-row{ background-color: #efefef; }
		.source-cell{ background-color: lightyellow }

	</style>
</head>
<body style="margin-left:0px;margin-right:0px">
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?emode=1&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['COL_MNG']; ?></a> &gt;&gt;
		<b><?php echo $LANG['BATCH_HARVEST_DUP']; ?></b>
	</div>
	<!-- inner text -->
	<div role="main" id="innertext" style="background-color:white;">
	<h1 class="page-heading"><?= $LANG['DUP_GEOREFERENCE']; ?></h1>
		<?php
		echo '<div id="page-title">'.$collMetaArr[$collid]['name'].' ('.$collMetaArr[$collid]['collcode'].')</div>';
		if($isEditor){
			?>
			<fieldset>
				<legend><?php echo $LANG['STAGING_VARIABLES']; ?></legend>
				<form name="stagingForm" method="post" action="duplicateharvest.php">
					<div class="fieldGroup">
						<div class="fieldLabel" style="float:left"><?php echo $LANG['TARGET_FIELDS']; ?>: </div>
						<div style="margin-left:5px;float:left">
							<input name="fieldtarget" type="radio" value="all" <?php echo (!$fieldTarget||$fieldTarget=='all'?'CHECKED':''); ?> /> <?php echo $LANG['ALL_FIELDS']; ?><br/>
							<input name="fieldtarget" type="radio" value="georef" <?php echo ($fieldTarget=='georef'?'CHECKED':''); ?> /> <?php echo $LANG['GEO_FIELDS']; ?>
						</div>
					</div>
					<div class="fieldGroup">
						<div class="fieldLabel" style="float:left"><?php echo $LANG['MATCH_METHOD']; ?>: </div>
						<div style="margin-left:5px;float:left">
							<input name="matchmethod" type="radio" value="dupe" <?php echo (!$matchMethod||$matchMethod=='dupe'?'CHECKED':''); ?> /> <?php echo $LANG['DUP_SPEC_TABLES']; ?><br/>
							<input name="matchmethod" type="radio" value="exsiccati" <?php echo ($matchMethod=='exsiccati'?'CHECKED':''); ?> /> <?php echo $LANG['EXS_TABLES']; ?>
						</div>
					</div>
					<div class="fieldGroup">
						<div class="fieldLabel" style="float:left"><?= $LANG['REC_NOT_EVAL_SINCE']; ?>: </div>
						<div style="margin-left: 5px">
							<input name="evaldate" type="date" value="" />
						</div>
					</div>
					<div class="fieldGroup">
						<span class="fieldLabel" style="float:left"><?= $LANG['PROC_STATUS']; ?>: </span>
						<span style="margin-left: 5px">
							<select name="processingstatus">
								<option value=""><?= $LANG['ALL_RECS']; ?></option>
								<option value="stage1" <?php echo ($processingStatus=='stage1'?'SELECTED':''); ?>><?= $LANG['STAGE_1']; ?></option>
								<option value="stage2" <?php echo ($processingStatus=='stage2'?'SELECTED':''); ?>><?= $LANG['STAGE_2']; ?></option>
								<option value="stage3" <?php echo ($processingStatus=='stage3'?'SELECTED':''); ?>><?= $LANG['STAGE_3']; ?></option>
								<option value="unprocessed"  <?php echo ($processingStatus===''||$processingStatus=='unprocessed'?'SELECTED':''); ?>><?= $LANG['UNPROCESSED']; ?></option>
							</select>
						</span>
					</div>
					<div class="buttonDiv" style="float:right;">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<button name="formsubmit" type="submit" value="buildDuplicateArr"><?= $LANG['BUILD_LIST']; ?></button>
					</div>
					<div style="">
						<span class="fieldLabel"><?= $LANG['REC_LIMIT']; ?>: </span>
						<span style="margin-left: 5px">
							<input name="limit" type="text" value="<?= $limit; ?>" style="width:100px" />
						</span>
					</div>
				</form>
			</fieldset>
			<?php
			if($action == 'buildDuplicateArr'){
				if($dupArr){
					$activeFieldArr = $dupeManager->getActiveFieldArr();
					?>
					<div>
						<form name="dupeUpdateForm" target="duplicateharvest.php" method="post">
							<table id="dupe-table">
								<tr>
									<th><input name="all" type="checkbox" title="Select All" /></th>
									<th>occid</th>
									<th><?= $LANG['COLL_CODE']; ?></th>
									<th><?= $LANG['CAT_BR_NUM']; ?></th>
									<?php
									foreach($activeFieldArr as $fieldName => $code){
										echo '<th>'.$fieldName.'</th>';
									}
									?>
								</tr>
								<?php
								foreach($dupArr as $occid => $dupeArr){
									//Output subject array
									echo '<tr class="source-row">';
									echo '<td><input name="all" type="checkbox" /></td>';
									echo '<td><a href="../editor/occurrenceeditor.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></td>';
									echo '<td></td>';
									echo '<td>'.$dupeArr[0]['catalogNumber']['v'].'</td>';
									foreach($activeFieldArr as $fieldName => $code){
										$currentValue = $dupeArr[0][$fieldName]['v'];
										$suggestedValue = '';
										if(isset($dupeArr[0][$fieldName]['p'])) $suggestedValue = $dupeArr[0][$fieldName]['p'];
										echo '<td title="Current value: '.htmlentities($currentValue).'">';
										if(!$currentValue && $suggestedValue) echo '<input name="'.$occid.'-'.$fieldName.'" value="'.htmlentities($suggestedValue).'" />';
										else echo $currentValue;
										echo '</td>';
									}
									echo '</tr>';
									//Output duplicate records
									for($i=1; $i < count($dupeArr); $i++){
										echo '<tr>';
										echo '<td></td>';
										echo '<td><a href="../individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($dupeArr[$i]['occid']['v'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></td>';
										echo '<td>'.$collMetaArr[$dupeArr[$i]['collid']['v']]['collcode'].'</td>';
										echo '<td>'.$dupeArr[$i]['catalogNumber']['v'].'</td>';
										foreach($activeFieldArr as $fieldName => $code){
											$classCode = '';
											if(isset($dupeArr[$i][$fieldName]['c']) && $dupeArr[$i][$fieldName]['v']) $classCode = 'source-cell';
											echo '<td class="'.$classCode.'">';
											echo htmlentities($dupeArr[$i][$fieldName]['v']);
											echo '</td>';
										}
										echo '</td>';
									}
								}
								?>
							</table>
						</form>
					</div>
					<?php
				}
			}
		}
		else{
			echo '<h2>' . $LANG['NOT_AUTH'] .'</h2>';
		}
		?>
	</div>
</body>
</html>