<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/checklist.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceChecklistManager.php');

$taxonFilter = array_key_exists('taxonfilter',$_REQUEST) ? filter_var($_REQUEST['taxonfilter'], FILTER_SANITIZE_NUMBER_INT) : '';

$checklistManager = new OccurrenceChecklistManager();
$searchVar = $checklistManager->getQueryTermStr();
$searchVarEncoded = urlencode($searchVar);

?>
<div>
	<form action="download/index.php" method="post" style="float:right" onsubmit="targetPopup(this)">
		<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?php echo $LANG['DOWNLOAD_TITLE']; ?>">
			<img src="../images/dl2.png" style="width:15px" />
		</button>
		<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
		<input name="dltype" type="hidden" value="checklist" />
		<input name="taxonFilterCode" type="hidden" value="<?php echo $taxonFilter; ?>" />
	</form>
	<?php
	if($KEY_MOD_IS_ACTIVE){
		?>
		<form action="checklistsymbiota.php" method="post" style="float:right">
			<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?php echo $LANG['OPEN_KEY']; ?>">
				<img src="../images/key.png" style="width:1.3em" />
			</button>
			<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
			<input name="taxonfilter" type="hidden" value="<?php echo $taxonFilter; ?>" />
			<input name="interface" type="hidden" value="key" />
		</form>
		<?php
	}
	if($FLORA_MOD_IS_ACTIVE){
		?>
		<form action="checklistsymbiota.php" method="post" style="float:right">
			<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?php echo $LANG['OPEN_CHECKLIST_EXPLORER']; ?>">
				<img src="../images/list.png" style="width:1.3em" />
			</button>
			<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
			<input name="taxonfilter" type="hidden" value="<?php echo $taxonFilter; ?>" />
			<input name="interface" type="hidden" value="checklist" />
		</form>
		<?php
	}
	?>
	<div style='margin:10px;float:right;'>
		<form name="changetaxonomy" id="changetaxonomy" action="list.php" method="post">
			<?php echo $LANG['TAXONOMIC_FILTER']; ?>:
			<select id="taxonfilter" name="taxonfilter" onchange="this.form.submit();">
				<option value="0"><?php echo (isset($LANG['UNRESOLVED'])?$LANG['UNRESOLVED']:'Unresolved');?></option>
				<?php
					$taxonAuthList = $checklistManager->getTaxonAuthorityList();
					foreach($taxonAuthList as $taCode => $taValue){
						echo "<option value='".$taCode."' ".($taCode == $taxonFilter?"SELECTED":"").">".$taValue."</option>";
					}
					?>
			</select>
			<input name="tabindex" type="hidden" value="0" />
			<input name="searchvar" type="hidden" value='<?php echo $searchVar; ?>' />
		</form>
	</div>
	<div style="clear:both;"><hr/></div>
		<?php
		$checklistArr = $checklistManager->getChecklist($taxonFilter);
		echo '<div style="font-size:110%;margin-bottom: 10px">'.$LANG['TAXA_COUNT'].': '.$checklistManager->getChecklistTaxaCnt().'</div>';
		$undFamilyArray = Array();
		if(array_key_exists('undefined',$checklistArr)){
			$undFamilyArray = $checklistArr['undefined'];
			unset($checklistArr['undefined']);
		}
		ksort($checklistArr);
		foreach($checklistArr as $family => $sciNameArr){
			ksort($sciNameArr);
			echo '<div style="margin-left:5;margin-top:5;">'.$family.'</div>';
			foreach($sciNameArr as $sciName => $tid){
				echo '<div style="margin-left:20;font-style:italic;">';
				if($tid) echo '<a target="_blank" href="../taxa/index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
				echo $sciName;
				if($tid) echo '</a>';
				echo '</div>';
			}
		}
		if($undFamilyArray){
			echo '<div style="margin-left:5;margin-top:5;">'.$LANG['FAMILY_NOT_DEFINED'].'</div>';
			foreach($undFamilyArray as $sciName => $tid){
				echo '<div style="margin-left:20;font-style:italic;">';
				if($tid) echo '<a target="_blank" href="../taxa/index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
				echo $sciName;
				if($tid) echo '</a>';
				echo '</div>';
			}
		}
	?>
</div>