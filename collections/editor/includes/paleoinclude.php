<?php
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/paleoinclude.'.$LANG_TAG.'.php'))
	include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/paleoinclude.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/paleoinclude.en.php');

$gtsTermArr = $occManager->getPaleoGtsTerms();
//include_once($SERVER_ROOT.'/collections/editor/includes/config/paleoVars.php');
?>
<script>
	var gtsArr = { <?php $d=''; foreach($gtsTermArr as $term => $rankid){ echo $d.'"'.$term.'":'.$rankid; $d=','; } ?> };
	function earlyIntervalChanged(f){
		let earlyTerm = f.earlyInterval.value;
		let lateTerm = f.lateInterval.value;
		setPaleoTable(earlyTerm, lateTerm);
		fieldChanged('earlyInterval');
	}

	function lateIntervalChanged(f){
		let earlyTerm = f.earlyInterval.value;
		let lateTerm = f.lateInterval.value;
		setPaleoTable(earlyTerm, lateTerm);
		fieldChanged('lateInterval');
	}

	async function setPaleoTable(earlyTerm, lateTerm) {
		let postData = new FormData();
		postData.append("earlyInterval", earlyTerm);
		postData.append("lateInterval", lateTerm);
		postData.append("format", "simple_map");
		const settings = {
			method: "POST",
			mode: "no-cors",
			headers: {
				"Content-Type": "application/json"
			},
			body: postData
		};
		try {
			const fetchResponse = await fetch('rpc/getPaleoGtsTable.php', settings);
			const responce = await fetchResponse.json();
			if(responce.tableStr != "undefined"){
				document.getElementById("table-div").innerHTML = responce.tableStr;
			}
		} catch (e) {
			alert(e);
		}
	}
</script>
<style>
	#paelo-gts-table{ border: 1px solid #ddd; padding: 8px; }
	#paelo-gts-table th{ background-color: #04AA6D; color: white; padding: 8px; }
	#paelo-gts-table td{ border: 1px solid; padding: 6px; }
</style>
<fieldset>
	<legend>Paleontology</legend>
	<div id="table-div" style="clear:both">

	</div>
	<div>
		<div id="earlyIntervalDiv">
			<?= $LANG['EARLY_INTERVAL_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('earlyInterval')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<select name="earlyInterval" onchange="earlyIntervalChanged(this.form)">
				<option value=""></option>
				<?php
				$earlyIntervalTerm = '';
				if(isset($occArr['earlyInterval'])) $earlyIntervalTerm = $occArr['earlyInterval'];
				if($earlyIntervalTerm && !array_key_exists($earlyIntervalTerm, $gtsTermArr)){
					echo '<option value="'.$earlyIntervalTerm.'" SELECTED>'.$earlyIntervalTerm.' - mismatched term</option>';
					echo '<option value="">---------------------------</option>';
				}
				foreach($gtsTermArr as $term => $rankid){
					echo '<option value="'.$term.'" '.($earlyIntervalTerm==$term?'SELECTED':'').'>'.$term.'</option>';
				}
				?>
			</select>
		</div>
		<div id="lateIntervalDiv">
			<?= $LANG['LATE_INTERVAL_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('lateInterval')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<select name="lateInterval" onchange="lateIntervalChanged(this.form)">
				<option value=""></option>
				<?php
				$lateIntervalTerm = '';
				if(isset($occArr['lateInterval'])) $lateIntervalTerm = $occArr['lateInterval'];
				if($lateIntervalTerm && !array_key_exists($lateIntervalTerm, $gtsTermArr)){
					echo '<option value="'.$lateIntervalTerm.'" SELECTED>'.$lateIntervalTerm.' - mismatched term</option>';
					echo '<option value="">---------------------------</option>';
				}
				foreach($gtsTermArr as $term => $rankid){
					echo '<option value="'.$term.'" '.($lateIntervalTerm==$term?'SELECTED':'').'>'.$term.'</option>';
				}
				?>
			</select>
		</div>
	</div>
	<div style="clear:both">
		<div id="absoluteAgeDiv">
			<?= $LANG['ABSOLUTE_AGE_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('absoluteAge')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="absoluteAge" value="<?php echo isset($occArr['absoluteAge'])?$occArr['absoluteAge']:''; ?>" onchange="fieldChanged('absoluteAge');" />
		</div>
		<div id="storageAgeDiv">
			<?= $LANG['STORAGE_AGE_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('storageAge')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="storageAge" value="<?php echo isset($occArr['storageAge'])?$occArr['storageAge']:''; ?>" onchange="fieldChanged('storageAge');" />
		</div>
		<div id="localStageDiv">
			<?= $LANG['LOCAL_STAGE_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('localStage')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="localStage" value="<?php echo isset($occArr['localStage'])?$occArr['localStage']:''; ?>" onchange="fieldChanged('localStage');" />
		</div>
	</div>
	<div style="clear:both">
		<div id="biotaDiv">
			<?= $LANG['BIOTA_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('biota')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="biota" value="<?php echo isset($occArr['biota'])?$occArr['biota']:''; ?>" onchange="fieldChanged('biota');" />
		</div>
		<div id="biostratigraphyDiv">
			<?= $LANG['BIOSTRATIGRAPHY_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('biostratigraphy')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="biostratigraphy" value="<?php echo isset($occArr['biostratigraphy'])?$occArr['biostratigraphy']:''; ?>" onchange="fieldChanged('biostratigraphy');" />
		</div>
		<div id="taxonEnvironmentDiv">
			<?= $LANG['TAXON_ENVIRONMENT_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('taxonEnvironment')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<?php
			$taxonEnvir = '';
			if(isset($occArr['taxonEnvironment'])) $taxonEnvir = $occArr['taxonEnvironment'];
			?>
			<select name="taxonEnvironment" onchange="fieldChanged('taxonEnvironment');">
				<option value=""></option>
				<option <?php if($taxonEnvir=='marine') echo 'SELECTED'; ?>>marine</option>
				<option<?php if($taxonEnvir=='non-marine') echo 'SELECTED'; ?>>non-marine</option>
				<option<?php if($taxonEnvir=='marine and non-marine') echo 'SELECTED'; ?>>marine and non-marine</option>
			</select>
		</div>
	</div>
	<div style="clear:both">
		<div id="lithogroupDiv">
			<?= $LANG['LITHOGROUP_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('group')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="lithogroup" value="<?php echo isset($occArr['lithogroup'])?$occArr['lithogroup']:''; ?>" onchange="fieldChanged('lithogroup');" />
		</div>
		<div id="formationDiv">
			<?= $LANG['FORMATION_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('formation')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="formation" value="<?php echo isset($occArr['formation'])?$occArr['formation']:''; ?>" onchange="fieldChanged('formation');" />
		</div>
		<div id="memberDiv">
			<?= $LANG['MEMBER_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('member')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="member" value="<?php echo isset($occArr['member'])?$occArr['member']:''; ?>" onchange="fieldChanged('member');" />
		</div>
		<div id="bedDiv">
			<?= $LANG['BED_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('bed')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="bed" value="<?php echo isset($occArr['bed'])?$occArr['bed']:''; ?>" onchange="fieldChanged('bed');" />
		</div>
	</div>
	<div style="clear:both">
		<div id="lithologyDiv">
			<?= $LANG['LITHOLOGY_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('lithology')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="lithology" value="<?php echo isset($occArr['lithology'])?$occArr['lithology']:''; ?>" onchange="fieldChanged('lithology');" />
		</div>
	</div>
	<div style="clear:both">
		<div id="stratRemarksDiv">
			<?= $LANG['STRAT_REMARKS_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('stratRemarks')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="stratRemarks" value="<?php echo isset($occArr['stratRemarks'])?$occArr['stratRemarks']:''; ?>" onchange="fieldChanged('stratRemarks');" />
		</div>
	</div>
	<div style="clear:both">
		<div id="elementDiv">
			<?= $LANG['ELEMENT_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('element')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="element" value="<?php echo isset($occArr['element'])?$occArr['element']:''; ?>" onchange="fieldChanged('element');" />
		</div>
		<div id="slidePropertiesDiv">
			<?= $LANG['SLIDE_PROPERTIES_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('slideProperties')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="slideProperties" value="<?php echo isset($occArr['slideProperties'])?$occArr['slideProperties']:''; ?>" onchange="fieldChanged('slideProperties');" />
		</div>
		<div id="geologicalContextIDDiv">
			<?= $LANG['GEOLOGICAL_CONTEXT_ID_LABEL'] ?>
			<a href="#" onclick="return dwcDoc('geologicalContextID')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
			<input type="text" name="geologicalContextID" value="<?php echo isset($occArr['geologicalContextID'])?$occArr['geologicalContextID']:''; ?>" onchange="fieldChanged('geologicalContextID');" />
		</div>
	</div>
</fieldset>