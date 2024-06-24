<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/dupesearch.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/dupesearch.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/dupesearch.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

$occidQuery = array_key_exists('occidquery',$_REQUEST) ? htmlspecialchars($_REQUEST['occidquery'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$curOccid = (array_key_exists('curoccid',$_GET) ? filter_var($_REQUEST['curoccid'], FILTER_SANITIZE_NUMBER_INT) : 0);
$collId = (array_key_exists('collid',$_GET) ? filter_var($_GET['collid'], FILTER_SANITIZE_NUMBER_INT) : 0);

$occIdMerge = (array_key_exists('occidmerge',$_GET) ? filter_var($_GET['occidmerge'], FILTER_SANITIZE_NUMBER_INT) : 0);
$submitAction = (array_key_exists('submitaction',$_GET) ? $_GET['submitaction'] : '');

$dupeManager = new OccurrenceDuplicate();

$dupeType = substr($occidQuery,0,5);
$occArr = array();
if(!$submitAction && $occidQuery){
	$occArr = $dupeManager->getDupesOccid(substr($occidQuery,6));
	unset($occArr[$curOccid]);
}

$onLoadStr = '';
$statusStr = '';
if($submitAction){
	$isEditor = 0;
	if($IS_ADMIN
		|| (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))
		|| (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"]))){
		$isEditor = 1;
	}
	if($isEditor){
		if($submitAction == 'mergerecs'){
			if(!$dupeManager->mergeRecords($occIdMerge,$curOccid, $collId)){
				$statusStr = $dupeManager->getErrorStr();

				//Add a unknown error message to user if no error is given
				if(empty($statusStr)) {
					$statusStr = $LANG['UNKNOWN_ERROR_WHILE_MERGING'];
				}
				//Fetch Occurrences Again for error display
				$occArr = $dupeManager->getDupesOccid(substr($occidQuery,6));
				unset($occArr[$curOccid]);
			} else {
				//Re route to merged occid if sucessful
				$onLoadStr = 'gotoMergedOccur(' . $occIdMerge . ');';
			}
		}
	}
}
//Get list of collections user can edit
$collRightsArr = array();
if(!$IS_ADMIN){
	if(array_key_exists('CollAdmin',$USER_RIGHTS)){
		$collRightsArr = $USER_RIGHTS['CollAdmin'];
	}
	if(array_key_exists('CollEditor',$USER_RIGHTS)){
		$collRightsArr = array_merge($collRightsArr,$USER_RIGHTS['CollEditor']);
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Duplicate Record Search</title>
		<?php

		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script type="text/javascript">
			var occArr = new Array();
			<?php
			if($occArr){
				foreach($occArr as $occId => $oArr){
					echo 'var oArr = new Array();'."\n";
					$tempOcc = array_change_key_case($oArr);
					unset($tempOcc['occid']);
					unset($tempOcc['institutioncode']);
					unset($tempOcc['collectioncode']);
					unset($tempOcc['ownerinstitutioncode']);
					unset($tempOcc['catalognumber']);
					unset($tempOcc['othercatalognumbers']);
					if($dupeType == 'event'){
						//Matching event, thus limit output
						unset($tempOcc['family']);
						unset($tempOcc['sciname']);
						unset($tempOcc['tidinterpreted']);
						unset($tempOcc['scientificnameauthorship']);
						unset($tempOcc['taxonremarks']);
						unset($tempOcc['identifiedby']);
						unset($tempOcc['dateidentified']);
						unset($tempOcc['identificationreferences']);
						unset($tempOcc['identificationremarks']);
						unset($tempOcc['identificationqualifier']);
						unset($tempOcc['typestatus']);
						unset($tempOcc['recordnumber']);
					}
					foreach($tempOcc as $k => $v){
						if($v) echo 'oArr["'.$k.'"] = "'.str_replace(array("\r\n", "\r", "\n", '"'),array(" "," "," ",'\"'),$v).'";'."\n";
					}
					echo 'occArr['.$occId.'] = oArr;'."\n";
				}
			}
			?>

			function transferRecord(occId,appendMode){
				var tArr = occArr[occId];
				var openerForm = opener.document.fullform;
				if(document.getElementById("linkdupe-"+occId).checked == true){
					openerForm.linkdupe.value = occId;
				}
				for(var k in tArr){
					try{
						var elem = openerForm.elements[k];
						if(elem.disabled == false && (elem.type != 'hidden' || k == "tidinterpreted") && (appendMode == false || elem.value == "")){
							elem.value = tArr[k];
							elem.style.backgroundColor = "lightblue";
							if(k != "tid") opener.fieldChanged(k);
						}
					}
					catch(err){
					}
				}
				window.close();
			}

			function gotoMergedOccur(occIdMerge){
				opener.pendingDataEdits = false;
				var qForm = opener.document.queryform;
				qForm.occid.value = occIdMerge;
				if(opener.document.fullform.occindex) {
					qForm.occindex.value = opener.document.fullform.occindex.value;
				}

				alert("<?= $LANG['CHECK_DETERMINATIONS_ALERT']?>");
				opener.document.queryform.submit();
				window.close();
			}

		</script>
		<style type="text/css">
			table.styledtable td { white-space: nowrap; }
		</style>
	</head>
	<body onload="<?php echo $onLoadStr; ?>">
		<div role="main" id="innertext">
			<h1 class="page-heading"><?php echo $LANG['DUPL_RECORD_SEARCH']; ?></h1>
			<?php
			if($statusStr){
				?>
				<hr/>
				<div style="margin:10px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php
			}
			if($occArr){
				echo '<div style="font-weight:bold;font-size:130%;">';
				if($dupeType == 'exsic'){
					echo '<span style="color:blue;">'.$LANG['EXS_DUPE'].'</span>';
				}
				elseif($dupeType == 'exact'){
					echo '<span style="color:green;">'.$LANG['POSSIBLE_EXACT_DUPES'].'</span>';
				}
				elseif($dupeType == 'catnu'){
					echo '<span>'.$LANG['DUPE_CAT_NUM'].'</span>';
				}
				elseif($dupeType == 'ocnum'){
					echo '<span>'.$LANG['DUPE_ALT_CAT_NUM'].'</span>';
				}
				else{
					echo '<span style="color:orange;">'.$LANG['POSS_MATCHING_EVENTS'].'</span>';
				}
				echo '</div><hr/>';
				//Experimental devleopment, not yet used
				/*
				?>
				<div id="tableview" style="display:none;">
					<table class="styledtable" style="font-size:12px;">
						<tr>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<?php
							$relFields = $dupeManager->getRelevantFields();
							foreach($relFields as $fieldName){
								echo '<th>'.$fieldName.'</th>';
							}
							?>
						</tr>
						<?php
						foreach($occArr as $id => $oArr){
							?>
							<tr>
								<td title="Transfer only to empty fields">
									<a href="" onclick="transferRecord(<?php echo $id; ?>,true);return false;">T-empty</a>
								</td>
								<td title="Transfer only to all fields that are open to editing">
									<a href="#" onclick="transferRecord(<?php echo $id; ?>,false);return false;">T-all</a>
								</td>
								<td>
									<?php
									if($curOccid){
										echo '<a href="dupesearch.php?submitaction=mergerecs&curoccid=' .htmlspecialchars($curOccid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&occidmerge=' . htmlspecialchars($id, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" onclick="return confirm(\'Are you sure you want to merge these two records?\')">Merge</a>';
									}
									?>
								</td>
								<td>
									<?php
									if($collId == $oArr['collid']){
										echo '<a href="occurrenceeditor.php?occid=' . htmlspecialchars($occId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/edit.png" /></a>';
									}
									?>
								</td>
								<?php
								foreach($relFields as $fieldName){
									echo '<td>'.$oArr[$fieldName].'</td>';
								}
								?>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
				<?php
				*/
				?>
				<div id="paragraphview" style="display:block;">
					<?php
					foreach($occArr as $occId => $occObj){
						if($IS_ADMIN || in_array($occObj['collid'],$collRightsArr)){
							//User can edit this specimen
							?>
							<div style="float:right;margin:10px;">
								<a href="occurrenceeditor.php?occid=<?php echo htmlspecialchars($occId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<img src="../../images/edit.png" style="width:1.2em" />
								</a>
							</div>
							<?php
						}
						?>
						<div style="clear:both;font-weight:bold;font-size:120%;">
							<?php echo $occObj['institutionCode'].($occObj['collectionCode']?':'.$occObj['collectionCode']:''); ?>
						</div>
						<?php if($collId == $occObj['collid'] && ($dupeType == 'exact' || $dupeType == 'exsic')){ ?>
							<div style="color:red;">
								<?php echo $LANG['NOTICE_EXACT_MATCH']; ?>
							</div>
							<div style="font-weight:bold;">
								<?php
								if($occObj['catalogNumber']) echo $occObj['catalogNumber'];
								if($occObj['otherCatalogNumbers']) echo ' ('.$occObj['otherCatalogNumbers'].')';
								?>
							</div>
						<?php } ?>
						<div>
							<?php
							echo '<span title="recordedby">'.($occObj['recordedBy']?$occObj['recordedBy']:'Collector field empty').'</span>';
							if($occObj['recordNumber']) echo '<span style="margin-left:20px;" title="recordnumber">'.$occObj['recordNumber'].'</span>';
							if($occObj['eventDate']){
								echo '<span style="margin-left:20px;" title="eventdate">'.$occObj['eventDate'].'</span>';
							}
							elseif($occObj['verbatimEventDate']){
								echo '<span style="margin-left:20px;" title="verbatimeventdate">'.$occObj['verbatimEventDate'].'</span>';
							}
							else{
								echo '<span style="margin-left:20px;" title="eventdate">'.$LANG['DATE_EMPTY'].'</span>';
							}
							if($occObj['associatedCollectors']) echo '<div style="margin-left:10px;" title="associatedCollectors">'.$LANG['ASSOC_COLL'].': '.$occObj['associatedCollectors'].'</div>';
							?>
						</div>
						<div>
							<?php
							if($occObj['sciname']){
								if($occObj['identificationQualifier']) echo '<span title="identificationQualifier">'.$occObj['identificationQualifier'].'</span> ';
								echo '<span title="sciname"><i>'.$occObj['sciname'].'</i></span> ';
								echo '<span title="scientificNameAuthorship">'.$occObj['scientificNameAuthorship'].'</span>';
								echo '<span style="margin-left:25px;color:red;" title="typeStatus">'.$occObj['typeStatus'].'</span>';
							}
							else{
								echo '<span title="sciname">'.$LANG['SCINAME_EMPTY'].'</span> ';
							}
							?>
						</div>
						<div style='margin-left:10px;'>
							<?php
							if($occObj['identificationRemarks'] || $occObj['identificationReferences']){
								echo '<span title="identificationRemarks">'.$occObj['identificationRemarks'].'</span>';
								if($occObj['identificationRemarks'] && $occObj['identificationReferences']) echo '<br/>';
								echo '<span title="identificationReferences">'.$occObj['identificationReferences'].'</span>';
							}
							?>
						</div>
						<div>
							<?php
							if($occObj['country']) echo '<span title="country">'.$occObj['country'].'</span>; ';
							if($occObj['stateProvince']) echo '<span title="stateProvince">'.$occObj['stateProvince'].'</span>; ';
							if($occObj['county']) echo '<span title="county">'.$occObj['county'].'</span>; ';
							echo '<span title="locality">'.($occObj['locality']?$occObj['locality']:$LANG['LOCALITY_EMPTY']).'</span>';
							?>
						</div>
						<?php
						if($occObj['habitat']) echo '<div title="habitat">'.$occObj['habitat'].'</div>';
						if($occObj['substrate']) echo '<div title="substrate">'.$occObj['substrate'].'</div>';
						if($occObj['decimalLatitude'] || $occObj['verbatimCoordinates']){
							?>
							<div>
								<?php
								echo '<span title="decimalLatitude">'.$occObj['decimalLatitude'].'</span>; ';
								echo '<span title="decimalLongitude">'.$occObj['decimalLongitude'].'</span>';
								if($occObj['coordinateUncertaintyInMeters']) echo ' +-<span title="coordinateUncertaintyInMeters">'.$occObj['coordinateUncertaintyInMeters'].'</span>m. ';
								if($occObj['geodeticDatum']) echo ' (<span title="geodeticDatum">'.$occObj['geodeticDatum'].'</span>)';
								if($occObj['verbatimCoordinates']) echo '<div style="margin-left:10px;" title="verbatimCoordinates">'.$occObj['verbatimCoordinates'].'</div>';
								$geoDetails = '';
								if($occObj['georeferenceProtocol']) $geoDetails = '; <span title="georeferenceProtocol">'.$occObj['georeferenceProtocol']."</span>";
								if($occObj['georeferenceSources']) $geoDetails = '; <span title="georeferenceSources">'.$occObj['georeferenceSources']."</span>";
								if($occObj['georeferenceRemarks']) $geoDetails = '; <span title="georeferenceRemarks">'.$occObj['georeferenceRemarks']."</span>";
								$geoDetails = trim($geoDetails,';');
								if($geoDetails) echo '<div style="margin-left:10px;">'.$geoDetails.'</div>';
								?>
							</div>
							<?php
						}
						if($occObj['minimumElevationInMeters'] || $occObj['verbatimElevation']){
							?>
							<div>
								<?php
								if($occObj['minimumElevationInMeters']){
									echo '<span title="minimumElevationInMeters">'.$occObj['minimumElevationInMeters'].'</span>';
									if($occObj['maximumElevationInMeters']) echo '-<span title="maximumElevationInMeters">'.$occObj['maximumElevationInMeters'].'</span>';
									echo ' meters ';
								}
								if($occObj['verbatimElevation']) echo 'Verbatim elev: <span title="verbatimElevation">'.$occObj['verbatimElevation'].'</span>';
								?>
							</div>
							<?php
						}
						if($occObj['occurrenceRemarks']) echo '<div title="occurrenceRemarks">Notes: '.$occObj['occurrenceRemarks'].'</div>';
						if($occObj['associatedTaxa']) echo '<div title="associatedTaxa">Associated Taxa: '.$occObj['associatedTaxa'].'</div>';
						if($occObj['dynamicProperties']) echo '<div title="dynamicProperties">Description: '.$occObj['dynamicProperties'].'</div>';
						if($occObj['reproductiveCondition'] || $occObj['establishmentMeans']){
							echo '<div>Misc: '.trim($occObj['reproductiveCondition'].'; '.$occObj['establishmentMeans'],'; ').'</div>';
						}
						?>
						<div style="margin:10px;">
							<div style="float:left">
								<a href="" onclick="transferRecord(<?php echo $occId; ?>,false);">
									<?php echo $LANG['TRANSFER_ALL']; ?>
								</a>
							</div>
							<div style="margin-left:30px;float:left">
								<a href="" onclick="transferRecord(<?php echo $occId; ?>,true);">
									<?php echo $LANG['TRANSFER_EMPTY']; ?>
								</a>
							</div>
							<?php
							if(!isset($ACTIVATE_DUPLICATES) || $ACTIVATE_DUPLICATES){
								?>
								<div style="margin-left:30px;float:left;">
									<input id="linkdupe-<?php echo $occId; ?>" type="checkbox" <?php echo ($dupeType == 'exact'?'checked':''); ?> /> 
									<label for="linkdupe-<?php echo $occId; ?>"><?php echo $LANG['LINK_DUPE']; ?></label>
								</div>
								<?php
							}
							if($collId == $occObj['collid']){
								?>
								<div style="margin-left:30px;float:left;">
									<a href="occurrenceeditor.php?occid=<?php echo htmlspecialchars($occId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo $LANG['GO_TO_RECORD']; ?>
									</a>
								</div>
								<?php
								if($curOccid){
									?>
									<div style="margin-left:30px;float:left;">
						<a href="dupesearch.php?submitaction=mergerecs&curoccid=<?php echo htmlspecialchars($curOccid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&occidmerge=' . htmlspecialchars($occId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&occidquery=' . htmlspecialchars($occidQuery, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); 
							?>" onclick="return confirm('<?php echo htmlspecialchars($LANG['SURE_MERGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>')">
											<?php echo $LANG['MERGE_RECORDS']; ?>
										</a>
									</div>
									<div style="margin-left:5px;float:left;">
										<a href="https://biokic.github.io/symbiota-docs/editor/edit/duplicates/#merge-records" id="mergeduplicateinfo" style="text-decoration:none;">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo $LANG['MORE_INFO_ALT']; ?>" title="<?php echo $LANG['MORE_INFO']; ?>" aria-label="<?php echo $LANG['MORE_INFO']; ?>"/>
										</a>
									</div>
									<?php
								}
							}
							?>
						</div>
						<div style="clear:both;"><hr/></div>
						<?php
					}
					?>
				</div>
				<?php
			}
			else{
				echo '<h2>'.$LANG['NO_DUPES'].'</h2>';
			}
			?>
		</div>
	</body>
</html>
