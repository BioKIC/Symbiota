<?php
include_once('../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/listtabledisplay.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/listtabledisplay.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/listtabledisplay.en.php');

include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT = $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ?? false;

$page = array_key_exists('page',$_REQUEST) ? $_REQUEST['page'] : 1;
$tableCount= array_key_exists('tablecount',$_REQUEST) ? $_REQUEST['tablecount'] : 1000;
$sortField1 = array_key_exists('sortfield1',$_REQUEST) ? $_REQUEST['sortfield1'] : 'collectionname';
$sortField2 = array_key_exists('sortfield2',$_REQUEST) ? $_REQUEST['sortfield2'] : '';
$sortOrder = array_key_exists('sortorder',$_REQUEST) ? $_REQUEST['sortorder'] : '';
$comingFrom =  (array_key_exists('comingFrom', $_REQUEST) ? $_REQUEST['comingFrom'] : '');
if($comingFrom != 'harvestparams' && $comingFrom != 'newsearch'){
	//If not set via a valid input variable, use setting set within symbini
	$comingFrom = !empty($SHOULD_USE_HARVESTPARAMS) ? 'harvestparams' : 'newsearch';
}

//Sanitation
if(!is_numeric($page) || $page < 1) $page = 1;
if(!is_numeric($tableCount)) $tableCount = 1000;
$sortField1 = htmlspecialchars($sortField1, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$sortField2 = htmlspecialchars($sortField2, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$sortOrder = htmlspecialchars($sortOrder, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

$collManager = new OccurrenceListManager();
$searchVar = $collManager->getQueryTermStr();
$searchVar .= '&comingFrom=' . $comingFrom;
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['COL_RESULTS']) ? $LANG['COL_RESULTS'] : 'Collections Search Results Table'); ?></title>
	<style>
		table.styledtable td {
			white-space: nowrap;
		}
	</style>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			<?php
			if($searchVar){
				?>
				sessionStorage.querystr = "<?php echo $searchVar; ?>";
				<?php
			}
			?>
		});
	</script>
	<script src="../js/symb/collections.list.js?ver=1" type="text/javascript"></script>
</head>
<body style="margin-left: 0px; margin-right: 0px;background-color:white;">
	<h1 class="page-heading left-breathing-room-rel"><?php echo $LANG['SEARCH_RES_TABLE'] ?></h1>
	<div>
		<div style="width:65rem;margin-bottom:5px;">
			<div style="float:right;">
				<!--
				<div style="float:left">
					<button class="icon-button" onclick="$('.dataset-div').toggle();" title="Dataset Management">
						<img src="../images/dataset.png" style="width:15px;" />
					</button>
				</div>
				-->
				<form action="list.php" method="post" style="float:left">
					<input name="comingFrom" type="hidden" value="<?= $comingFrom; ?>" />
					<button type="submit" class="icon-button" style="margin:5px;padding:5px;" title="<?php echo (isset($LANG['LIST_DISPLAY']) ? $LANG['LIST_DISPLAY'] : 'List Display'); ?>"  aria-label="<?php echo (isset($LANG['LIST_DISPLAY']) ? $LANG['LIST_DISPLAY'] : 'List Display'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" style="width:1.3em;height:1.3em" alt="<?php echo (isset($LANG['LIST_DISPLAY']) ? $LANG['LIST_DISPLAY'] : 'List Display'); ?>" height="24" viewBox="0 -960 960 960" width="24"> <path d="M280-600v-80h560v80H280Zm0 160v-80h560v80H280Zm0 160v-80h560v80H280ZM160-600q-17 0-28.5-11.5T120-640q0-17 11.5-28.5T160-680q17 0 28.5 11.5T200-640q0 17-11.5 28.5T160-600Zm0 160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520q17 0 28.5 11.5T200-480q0 17-11.5 28.5T160-440Zm0 160q-17 0-28.5-11.5T120-320q0-17 11.5-28.5T160-360q17 0 28.5 11.5T200-320q0 17-11.5 28.5T160-280Z"/></svg>
					</button>
					<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
				</form>
				<form action="download/index.php" method="post" style="float:left" onsubmit="targetPopup(this)">
					<button class="icon-button" style="margin:5px;padding:5px;" title="<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>" aria-label="<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>">
					<svg xmlns="http://www.w3.org/2000/svg" style="width:1.3em;height:1.3em" alt="<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>" height="24" viewBox="0 -960 960 960" width="24"> <path d="M480-320 280-520l56-58 104 104v-326h80v326l104-104 56 58-200 200ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z"/></svg>
					</button>
					<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
					<input name="dltype" type="hidden" value="specimen" />
				</form>
				<div style="float:left">
					<button class="icon-button" style="margin:5px;padding:5px;" onclick="copyUrl()" title="<?php echo (isset($LANG['COPY_TO_CLIPBOARD']) ? $LANG['COPY_TO_CLIPBOARD'] : 'Copy URL to Clipboard'); ?>" aria-label="<?php echo (isset($LANG['COPY_TO_CLIPBOARD']) ? $LANG['COPY_TO_CLIPBOARD'] : 'Copy URL to Clipboard'); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" style="width:1.3em;height:1.3em" alt="<?php echo (isset($LANG['COPY_TO_CLIPBOARD']) ? $LANG['COPY_TO_CLIPBOARD'] : 'Copy URL to Clipboard'); ?>" height="24" viewBox="0 -960 960 960" width="24"> <path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"/></svg>
					</button>
				</div>
			</div>
			<div style="padding:5px;width:650px;">
				<section class="fieldset-like">
				<h2>
					<span><?php echo (isset($LANG['SORT']) ? $LANG['SORT'] : 'Sort Results'); ?></span>
				</h2>
					<form name="sortform" action="listtabledisplay.php" method="post">
						<div>
							<label for="sortfield1"><?php echo (isset($LANG['SORT_BY']) ? $LANG['SORT_BY'] : 'Sort By'); ?>:</label>
							<select name="sortfield1" id="sortfield1">
								<?php
								$sortFields = array('c.collectionname' => (isset($LANG['COLLECTION']) ? $LANG['COLLECTION'] : 'Collection'), 'o.catalogNumber' => (isset($LANG['CATALOG_NUMBER']) ? $LANG['CATALOG_NUMBER'] : 'Catalog Number'), 'o.family' => (isset($LANG['FAMILY']) ? $LANG['FAMILY'] : 'Family'), 'o.sciname' => (isset($LANG['SCINAME']) ? $LANG['SCINAME'] : 'Scientific Name'), 'o.recordedBy' => (isset($LANG['COLLECTOR']) ? $LANG['COLLECTOR'] : 'Collector'),
									'o.recordNumber' => (isset($LANG['NUMBER']) ? $LANG['NUMBER'] : 'Number'), 'o.eventDate' => (isset($LANG['EVENTDATE']) ? $LANG['EVENTDATE'] : 'Date'), 'o.country' => (isset($LANG['COUNTRY']) ? $LANG['COUNTRY'] : 'Country'), 'o.StateProvince' => (isset($LANG['STATE_PROVINCE']) ? $LANG['STATE_PROVINCE'] : 'State/Province'), 'o.county' => (isset($LANG['COUNTY']) ? $LANG['COUNTY'] : 'County'), 'o.minimumElevationInMeters' => (isset($LANG['ELEVATION']) ? $LANG['ELEVATION'] : 'Elevation'),
									'p.formation' => (isset($LANG['FORMATION']) ? $LANG['FORMATION'] : 'Formation'), 'p.earlyInterval' => (isset($LANG['EARLY_INT']) ? $LANG['EARLY_INT'] : 'Early Interval'), 'p.lateInterval' => (isset($LANG['LATE_INT']) ? $LANG['LATE_INT'] : 'Late IntervaL'));
								foreach($sortFields as $k => $v){
									echo '<option value="'.$k.'" '.($k==$sortField1?'SELECTED':'').'>'.$v.'</option>';
								}
								?>
							</select>
						</div>
						<div>
							<label for="sortfield2"><?php echo (isset($LANG['THEN_BY']) ? $LANG['THEN_BY'] : 'Then Sort By'); ?>:</label>
							<select name="sortfield2" id="sortfield2">
								<option value=""><?php echo (isset($LANG['SEL_FIELD']) ? $LANG['SEL_FIELD'] : 'Select Field Name'); ?></option>
								<?php
								foreach($sortFields as $k => $v){
									echo '<option value="'.$k.'" '.($k==$sortField2?'SELECTED':'').'>'.$v.'</option>';
								}
								?>
							</select>
						</div>
						<div>
							<label for="sortorder"> <b><?php echo (isset($LANG['ORDER']) ? $LANG['ORDER'] : 'Order'); ?>:</b> </label>
							<select id="sortorder" name="sortorder">
								<option value=""><?php echo (isset($LANG['ASCENDING']) ? $LANG['ASCENDING'] : 'Ascending'); ?></option>
								<option value="desc" <?php echo ($sortOrder=="desc"?'SELECTED':''); ?>><?php echo (isset($LANG['DESCENDING']) ? $LANG['DESCENDING'] : 'Descending'); ?></option>
							</select>
						</div>
						<div>
							<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
							<input name="formsubmit" type="submit" value="<?php echo (isset($LANG['SORT']) ? $LANG['SORT'] : 'Sort'); ?>" />
						</div>
					</form>
				</section>
			</div>
		</div>
		<?php
		$searchVar .= '&sortfield1='.$sortField1.'&sortfield2='.$sortField2.'&sortorder='.$sortOrder;
		$collManager->addSort($sortField1, $sortOrder);
		if($sortField2) $collManager->addSort($sortField2, $sortOrder);
		$recArr = $collManager->getSpecimenMap((($page-1)*$tableCount), $tableCount);

		$targetClid = $collManager->getSearchTerm("targetclid");

		$qryCnt = $collManager->getRecordCnt();
		$navStr = '<div style="float:right;">';
		if($page > 1){
			$navStr .= '<a href="listtabledisplay.php?' . htmlspecialchars($searchVar, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&page=' . htmlspecialchars(($page-1), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($LANG['PAGINATION_PREVIOUS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($tableCount, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($LANG['PAGINATION_RECORDS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&lt;&lt;</a>';
		}
		$navStr .= ' | ';
		$navStr .= ($page==1 ? 1 : (($page-1)*$tableCount)).'-'.($qryCnt<$tableCount*$page ? $qryCnt : $tableCount*$page).' '.$LANG['PAGINATION_OF'].' '.$qryCnt.' '.$LANG['PAGINATION_RECORDS'];
		$navStr .= ' | ';
		if($qryCnt > ($page*$tableCount)){
			$navStr .= '<a href="listtabledisplay.php?' . htmlspecialchars($searchVar, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&page=' . htmlspecialchars(($page+1), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($LANG['PAGINATION_NEXT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($tableCount, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($LANG['PAGINATION_RECORDS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&gt;&gt;</a>';
		}
		$navStr .= '</div>';
		?>
		<div style="width:850px;clear:both;">
			<div style="float:right">
				<?php
				echo $navStr;
				?>
			</div>
			<div class="navpath">
				<a href="../index.php"><?php echo htmlspecialchars((isset($LANG['NAV_HOME']) ? $LANG['NAV_HOME'] : 'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
				<?php
				if($comingFrom == 'harvestparams'){
					?>
					<a href="index.php"><?php echo htmlspecialchars((isset($LANG['NAV_COLLECTIONS']) ? $LANG['NAV_COLLECTIONS'] : 'Collections'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
					<a href="<?php echo $CLIENT_ROOT . '/collections/harvestparams.php' ?>"><?php echo htmlspecialchars((isset($LANG['NAV_SEARCH']) ? $LANG['NAV_SEARCH'] : 'Search Criteria'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
					<?php
				}else{
					?>
					<a href="<?php echo $CLIENT_ROOT . '/collections/search/index.php' ?>"><?php echo htmlspecialchars((isset($LANG['NAV_SEARCH']) ? $LANG['NAV_SEARCH'] : 'Search Criteria'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
					<?php
				}
				?>
				<b><?php echo (isset($LANG['SPEC_REC_TAB']) ? $LANG['SPEC_REC_TAB'] : 'Specimen Records Table'); ?></b>
			</div>
		</div>
		<form name="occurListForm" method="post" action="datasets/index.php" onsubmit="return validateOccurListForm(this)" target="_blank">
			<?php include('datasetinclude.php'); ?>
			<div id="tablediv">
				<?php
				if($recArr){
					?>
					<div style="clear:both;height:5px;"></div>
					<table class="styledtable" style="font-size:12px;">
						<tr>
							<th><?php echo (isset($LANG['SYMB_ID']) ? $LANG['SYMB_ID'] : 'Symbiota ID'); ?></th>
							<th><?php echo (isset($LANG['COLLECTION']) ? $LANG['COLLECTION'] : 'Collection'); ?></th>
							<th><?php echo (isset($LANG['CATALOGNUMBER']) ? $LANG['CATALOGNUMBER'] : 'Catalog Number'); ?></th>
							<th><?php echo (isset($LANG['FAMILY']) ? $LANG['FAMILY'] : 'Family'); ?></th>
							<th><?php echo (isset($LANG['SCINAME']) ? $LANG['SCINAME'] : 'Scientific Name'); ?></th>
							<th><?php echo (isset($LANG['COLLECTOR']) ? $LANG['COLLECTOR'] : 'Collector'); ?></th>
							<th><?php echo (isset($LANG['NUMBER']) ? $LANG['NUMBER'] : 'Number'); ?></th>
							<th><?php echo (isset($LANG['EVENT_DATE']) ? $LANG['EVENT_DATE'] : 'Date'); ?></th>
							<th><?php echo (isset($LANG['COUNTRY']) ? $LANG['COUNTRY'] : 'Country'); ?></th>
							<th><?php echo (isset($LANG['STATE_PROVINCE']) ? $LANG['STATE_PROVINCE'] : 'State/Province'); ?></th>
							<th><?php echo (isset($LANG['COUNTY']) ? $LANG['COUNTY'] : 'County'); ?></th>
							<th><?php echo (isset($LANG['LOCALITY']) ? $LANG['LOCALITY'] : 'Locality'); ?></th>
							<th><?php echo (isset($LANG['DEC_LAT']) ? $LANG['DEC_LAT'] : 'Decimal Lat.'); ?></th>
							<th><?php echo (isset($LANG['DEC_LONG']) ? $LANG['DEC_LONG'] : 'Decimal Long.'); ?></th>
							<th><?php echo (isset($LANG['HABITAT']) ? $LANG['HABITAT'] : 'Habitat'); ?></th>
							<th><?php echo (isset($LANG['SUBSTRATE']) ? $LANG['SUBSTRATE'] : 'Substrate'); ?></th>
							<th><?php echo (isset($LANG['ELEVATION']) ? $LANG['ELEVATION'] : 'Elevation'); ?></th>
							<?php if (!empty($ACTIVATE_PALEO)): ?>
								<th><?php echo isset($LANG['FORMATION']) ? $LANG['FORMATION'] : 'Formation'; ?></th>
								<th><?php echo isset($LANG['EARLY_INT']) ? $LANG['EARLY_INT'] : 'Early Interval'; ?></th>
								<th><?php echo isset($LANG['LATE_INT']) ? $LANG['LATE_INT'] : 'Late Interval'; ?></th>
							<?php endif; ?>
						</tr>
						<?php
						$recCnt = 0;
						foreach($recArr as $occid => $occArr){
							$isEditor = false;
							if($SYMB_UID && ($IS_ADMIN
									|| (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollAdmin']))
									|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollEditor'])))){
								$isEditor = true;
							}
							$collection = $occArr['instcode'];
							if($occArr['collcode']) $collection .= ':'.$occArr['collcode'];
							if($occArr['sciname']) $occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
							?>
							<tr <?php echo ($recCnt%2?'class="alt"':''); ?>>
								<td>
									<div class="dataset-div" style="float:left;display:none"><input name="occid[]" type="checkbox" value="<?php echo $occid; ?>" /></div>
									<?php
									echo '<a href="#" onclick="return openIndPU('.$occid.",".($targetClid ? $targetClid : "0").');">'.$occid.'</a> ';
									if($isEditor || ($SYMB_UID && $SYMB_UID == $occArr['obsuid'])){
										echo '<a href="editor/occurrenceeditor.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">';
										echo '<img src="../images/edit.png" style="height:1.3em;" title="'.(isset($LANG['EDIT_REC']) ? $LANG['EDIT_REC'] : 'Edit Record').'" />';
										echo '</a>';
									}
									if(isset($occArr['img'])){
										echo '<img src="../images/image.png" style="height:1.3em;margin-left:5px;" title="'.(isset($LANG['HAS_IMAGE']) ? $LANG['HAS_IMAGE'] : 'Has Image').'" />';
									}
									?>
								</td>
								<td><?php echo $collection; ?></td>
								<td><?php echo $occArr['catnum']; ?></td>
								<td><?php echo $occArr['family']; ?></td>
								<td><?php echo $occArr['sciname'].($occArr['author']?' '.$occArr['author'] : ''); ?></td>
								<td><?php echo $occArr['collector']; ?></td>
								<td><?php echo (array_key_exists('collnum',$occArr) ? $occArr['collnum'] : ''); ?></td>
								<td><?php echo (array_key_exists('date',$occArr) ? $occArr['date'] : ''); ?></td>
								<td><?php echo $occArr['country']; ?></td>
								<td><?php echo $occArr['state']; ?></td>
								<td><?php echo $occArr['county']; ?></td>
								<td>
								<?php
								$locStr = preg_replace('/<div.*?>.*?<\/div>/', '', $occArr['locality']);
								if(strlen($locStr)>80) $locStr = substr($locStr,0,80).'...';
								echo $locStr;
								?></td>
								<td><?php if(isset($occArr['declat'])) echo $occArr['declat']; ?></td>
								<td><?php if(isset($occArr['declong'])) echo $occArr['declong']; ?></td>
								<td><?php if(isset($occArr['habitat'])) echo ((strlen($occArr['habitat'])>80) ? substr($occArr['habitat'],0,80).'...':$occArr['habitat']); ?></td>
								<td><?php if(isset($occArr['substrate'])) echo ((strlen($occArr['substrate'])>80) ? substr($occArr['substrate'],0,80).'...':$occArr['substrate']); ?></td>
								<td><?php echo (array_key_exists('elev',$occArr) ? $occArr['elev'] : ''); ?></td>
								<?php if (!empty($ACTIVATE_PALEO)): ?>
									<td><?php if(isset($occArr['formation'])) echo $occArr['formation']; ?></td>
									<td><?php if(isset($occArr['earlyInterval'])) echo $occArr['earlyInterval']; ?></td>
									<td><?php if(isset($occArr['lateInterval'])) echo $occArr['lateInterval']; ?></td>
								<?php endif; ?>
							</tr>
							<?php
							$recCnt++;
						}
						?>
					</table>
					<div style="clear:both;height:5px;"></div>
					<div style="width:790px;"><?php echo $navStr; ?></div>
					*<?php echo (isset($LANG['CLICK_SYMB']) ? $LANG['CLICK_SYMB'] : 'Click on the Symbiota identifier in the first column to see Full Record Details'); ?>.';
					<?php
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;">'.(isset($LANG['NONE_FOUND']) ? $LANG['NONE_FOUND'] : 'No records found matching the query').'</div>';
				}
				?>
			</div>
		</form>
	</div>
</body>
</html>
