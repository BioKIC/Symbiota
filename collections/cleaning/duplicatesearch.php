<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/cleaning/duplicatesearch.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/cleaning/duplicatesearch.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/cleaning/duplicatesearch.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:1000;

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/duplicatesearch.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
if(!is_numeric($start)) $start = 0;
if(!is_numeric($limit)) $limit = 1000;

$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = current($cleanManager->getCollMap());

$isEditor = 0;
if($collMap){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])) || ($collMap['colltype'] == 'General Observations')){
		$isEditor = 1;
	}

	//If collection is a general observation project, limit to User
	if($collMap['colltype'] == 'General Observations'){
		$cleanManager->setObsUid($SYMB_UID);
	}

	$limit = ini_get('max_input_vars')*0.2;
	if(!$limit || $limit > 1000) $limit = 1000;

	$dupArr = array();
	if($action == 'listdupscatalog'){
		$dupArr = $cleanManager->getDuplicateCatalogNumber('cat', $start, $limit);
	}
	if($action == 'listdupsothercatalog'){
		$dupArr = $cleanManager->getDuplicateCatalogNumber('other', $start, $limit);
	}
	elseif($action == 'listdupsrecordedby'){
		$dupArr = $cleanManager->getDuplicateCollectorNumber($start);
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['OCC_CLEANER']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<style>
		table.styledtable td { white-space: nowrap; }
	</style>
	<script type="text/javascript">
		function validateMergeForm(f){
			var dbElements = document.getElementsByName("dupid[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				if(dbElement.checked) return true;
			}
		   	alert("<?php echo $LANG['SEL_SPECIMENS']; ?>");
	      	return false;
		}

		function selectAllDuplicates(f){
			var boxesChecked = true;
			if(!f.selectalldupes.checked){
				boxesChecked = false;
			}
			var dbElements = document.getElementsByName("dupid[]");
			for(i = 0; i < dbElements.length; i++){
				dbElements[i].checked = boxesChecked;
			}
		}

		function batchSwitchTargetSpecimens(cbElem){
			cbElem.checked = false;
			var dbElements = document.getElementsByTagName("input");
			//var dbElements = $("input[type='radio']").val();
			var elemName = '';
			for(i = 0; i < dbElements.length; i++){
				if(dbElements[i].type == "radio"){
					if(dbElements[i].checked == false && elemName != dbElements[i].name){
						dbElements[i].checked = true;
						elemName = dbElements[i].name;
					}
				}
			}
		}
	</script>
</head>
<body style="margin-left:10px; width: 100%">
	<div class='navpath' style="margin:10px">
		<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['COL_MAN']; ?></a> &gt;&gt;
		<a href="index.php?collid=<?php echo $collid; ?>"><?php echo $LANG['CLEAN_MOD_INDEX']; ?></a> &gt;&gt;
		<b><?php echo $LANG['DUP_OCCS']; ?></b>
	</div>
	<div role="main" id="innertext" style="background-color:white; margin:10px; width: 100%; max-width: 100%; padding: 0px;">
		<h1 class="page-heading">Duplicate Catalog Number Cleaning Tool</h1>
		<?php
		if($collMap && $isEditor){
			if($IS_ADMIN && $limit < 900) echo '<div style="max-width: 1000px">'.$LANG['SUPERADMIN_NOTICE'].'</div>';
			if($action == 'listdupscatalog' || $action == 'listdupsothercatalog' || $action == 'listdupsrecordedby'){
				//Look for duplicate catalognumbers
				if($dupArr){
					?>
					<div style="max-width: 1000px; margin-bottom:10px;">
						<b><?php echo $LANG['DUP_INSTRUCTIONS']; ?></b>
					</div>
					<form name="mergeform" action="duplicatesearch.php" method="post" onsubmit="return validateMergeForm(this);">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<div style="max-width: 1000px">
							<?php
							$recCnt = count($dupArr);
							if($recCnt > $limit){
								$href = 'duplicatesearch.php?collid='.$collid.'&action='.$action.'&start='.($start+$limit);
								echo '<div style="float:right;"><a href="'.$href.'"><b>'.$LANG['NEXT'].' '.$limit.' '.$LANG['RECORDS'].' &gt;&gt;</b></a></div>';
							}
							echo '<div style="float:left;margin-bottom:4px;margin-left:15px;"><input name="action" type="submit" value="Merge Duplicate Records" /></div>';
							echo '<div style="float:left;margin-left:15px;"><b>'.($start+1).' '.$LANG['TO'].' '.($start+$recCnt).' '.$LANG['DUP_CLUSTERS'].' </b></div>';
							?>
						</div>
						<div style="clear: both">
							<table class="styledtable" style="font-family:Arial;font-size:12px;">
								<tr>
									<th style="width:40px;"><?php echo $LANG['ID']; ?></th>
									<th style="width:20px;"><input name="selectalldupes" type="checkbox" title="<?php echo $LANG['SEL_DESEL_ALL']; ?>" onclick="selectAllDuplicates(this.form)" /></th>
									<th><input type="checkbox" name="batchswitch" onclick="batchSwitchTargetSpecimens(this)" title="<?php echo $LANG['BATCH_SWITCH']; ?>" /></th>
									<th style="width:40px;"><?php echo $LANG['CAT_NUM']; ?></th>
									<th style="width:40px;"><?php echo $LANG['OTHER_CAT_NUM']; ?></th>
									<th><?php echo $LANG['SCI_NAME']; ?></th>
									<th><?php echo $LANG['COLLECTOR']; ?></th>
									<th><?php echo $LANG['COL_NUM']; ?></th>
									<th><?php echo $LANG['ASSOC_COL']; ?></th>
									<th><?php echo $LANG['COL_DATE']; ?></th>
									<th><?php echo $LANG['VERBAT_DATE']; ?></th>
									<th><?php echo $LANG['COUNTRY']; ?></th>
									<th><?php echo $LANG['STATE']; ?></th>
									<th><?php echo $LANG['COUNTY']; ?></th>
									<th><?php echo $LANG['LOCALITY']; ?></th>
									<th><?php echo $LANG['DATE_LAST_MOD']; ?></th>
								</tr>
								<?php
								$setCnt = 0;
								foreach($dupArr as $dupKey => $occArr){
									$setCnt++;
									$first = true;
									foreach($occArr as $occId => $occArr){
										echo '<tr '.(($setCnt % 2) == 1?'class="alt"':'').'>';
										echo '<td><a href="../editor/occurrenceeditor.php?occid='.$occId.'" target="_blank">'.$occId.'</a></td>'."\n";
										echo '<td><input name="dupid[]" type="checkbox" value="'.$dupKey.'|'.$occId.'" /></td>'."\n";
										echo '<td><input name="dup'.$dupKey.'target" type="radio" value="'.$occId.'" '.($first?'checked':'').'/></td>'."\n";
										echo '<td>'.$occArr['catalognumber'].'</td>'."\n";
										echo '<td>'.$occArr['othercatalognumbers'].'</td>'."\n";
										echo '<td>'.$occArr['sciname'].'</td>'."\n";
										echo '<td>'.$occArr['recordedby'].'</td>'."\n";
										echo '<td>'.$occArr['recordnumber'].'</td>'."\n";
										echo '<td>'.$occArr['associatedcollectors'].'</td>'."\n";
										echo '<td>'.$occArr['eventdate'].'</td>'."\n";
										echo '<td>'.$occArr['verbatimeventdate'].'</td>'."\n";
										echo '<td>'.$occArr['country'].'</td>'."\n";
										echo '<td>'.$occArr['stateprovince'].'</td>'."\n";
										echo '<td>'.$occArr['county'].'</td>'."\n";
										echo '<td>'.$occArr['locality'].'</td>'."\n";
										echo '<td>'.$occArr['datelastmodified'].'</td>'."\n";
										echo '</tr>';
										$first = false;
									}
								}
								?>
							</table>
						</div>
						<div style="margin:15px;">
							<button name="action" type="submit" value="Merge Duplicate Records"><?php echo $LANG['MERGE_DUPES']; ?></button>
						</div>
					</form>
					<?php
				}
				else{
					?>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<?php echo $LANG['NO_DUPES']; ?>
					</div>
					<?php
				}
			}
			elseif($action == 'Merge Duplicate Records'){
				?>
				<ul>
					<li><?php echo $LANG['DUPE_MERGING_STARTED']; ?></li>
					<?php
					$dupArr = array();
					foreach($_POST['dupid'] as $v){
						$vArr = explode('|',$v);
						if(count($vArr) > 1){
							$target = $_POST['dup'.str_replace(' ', '_', $vArr[0]).'target'];
							if($target != $vArr[1]) $dupArr[$target][] = $vArr[1];
						}
					}
					$cleanManager->mergeDupeArr($dupArr);
					?>
					<li><?php echo $LANG['DONE']; ?></li>
				</ul>
				<div style="margin-top:10px">
					<?php
					if((count($dupArr)+2)>$limit){
						?>
							<div>
								<a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['RETURN_TO_FORM'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
							</div>
							<?php
						}
					?>
					<div>
						<a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['RETURN_TO_MAIN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
					</div>
				</div>
				<?php
			}
		}
		else{
			echo '<h2>'.$LANG['NOT_AUTH'].'</h2>';
		}
		?>
	</div>
</body>
</html>