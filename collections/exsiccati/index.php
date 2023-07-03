<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceExsiccatae.php');
header('Content-Type: text/html; charset='.$CHARSET);

$ometid = array_key_exists('ometid',$_REQUEST)?$_REQUEST['ometid']:0;
$omenid = array_key_exists('omenid',$_REQUEST)?$_REQUEST['omenid']:0;
$occidToAdd = array_key_exists('occidtoadd',$_REQUEST)?$_REQUEST['occidtoadd']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$specimenOnly = array_key_exists('specimenonly',$_REQUEST)?$_REQUEST['specimenonly']:0;
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$imagesOnly = array_key_exists('imagesonly',$_REQUEST)?$_REQUEST['imagesonly']:0;
$sortBy = array_key_exists('sortby',$_REQUEST)?$_REQUEST['sortby']:0;
$formSubmit = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

//Sanitation
if(!is_numeric($ometid)) $ometid = 0;
if(!is_numeric($omenid)) $omenid = 0;
if(!is_numeric($occidToAdd)) $occidToAdd = 0;
$searchTerm = filter_var($searchTerm,FILTER_SANITIZE_STRING);
if(!is_numeric($specimenOnly)) $specimenOnly = 0;
if(!is_numeric($collId)) $collId = 0;
if(!is_numeric($imagesOnly)) $imagesOnly = 0;
if(!is_numeric($sortBy)) $sortBy = 0;

if(!$specimenOnly && !$ometid && !array_key_exists('searchterm', $_POST)){
	//Make specimen only the default action
	$specimenOnly = 1;
}

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || array_key_exists('CollAdmin',$USER_RIGHTS)){
	$isEditor = 1;
}

$exsManager = new OccurrenceExsiccatae($formSubmit?'write':'readonly');
if($isEditor && $formSubmit){
	if($formSubmit == 'Add Exsiccata Title'){
		$statusStr = $exsManager->addTitle($_POST,$PARAMS_ARR['un']);
	}
	elseif($formSubmit == 'Save'){
		$statusStr = $exsManager->editTitle($_POST,$PARAMS_ARR['un']);
	}
	elseif($formSubmit == 'Delete Exsiccata'){
		$statusStr = $exsManager->deleteTitle($ometid);
		if(!$statusStr) $ometid = 0;
	}
	elseif($formSubmit == 'Merge Exsiccatae'){
		$statusStr = $exsManager->mergeTitles($ometid,$_POST['targetometid']);
		if(!$statusStr) $ometid = $_POST['targetometid'];
	}
	elseif($formSubmit == 'Add New Number'){
		$statusStr = $exsManager->addNumber($_POST);
	}
	elseif($formSubmit == 'Save Edits'){
		$statusStr = $exsManager->editNumber($_POST);
	}
	elseif($formSubmit == 'Delete Number'){
		$statusStr = $exsManager->deleteNumber($omenid);
		$omenid = 0;
	}
	elseif($formSubmit == 'Transfer Number'){
		$statusStr = $exsManager->transferNumber($omenid,trim($_POST['targetometid'],'k'));
	}
	elseif($formSubmit == 'Add Specimen Link'){
		$statusStr = $exsManager->addOccLink($_POST);
	}
	elseif($formSubmit == 'Save Specimen Link Edit'){
		$exsManager->editOccLink($_POST);
	}
	elseif($formSubmit == 'Delete Link to Specimen'){
		$exsManager->deleteOccLink($omenid,$_POST['occid']);
	}
	elseif($formSubmit == 'Transfer Specimen'){
		$statusStr = $exsManager->transferOccurrence($omenid,$_POST['occid'],trim($_POST['targetometid'],'k'),$_POST['targetexsnumber']);
	}
}
if($formSubmit == 'dlexs' || $formSubmit == 'dlexs_titleOnly'){
	$titleOnly = false;
	if($formSubmit == 'dlexs_titleOnly') $titleOnly = true;
	$exsManager->exportExsiccatiAsCsv($searchTerm, $specimenOnly, $imagesOnly, $collId, $titleOnly);
	exit;
}
$selectLookupArr = array();
if($ometid || $omenid) $selectLookupArr = $exsManager->getSelectLookupArr();
if($ometid) unset($selectLookupArr[$ometid]);
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Exsiccatae</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="../../js/jquery-3.2.1.min.js?ver=3" type="text/javascript"></script>
	<script src="../../js/jquery-ui/jquery-ui.min.js?ver=1" type="text/javascript"></script>
	<link href="../../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="../../js/symb/shared.js?ver=130926" type="text/javascript"></script>
	<script type="text/javascript">
		function toggleExsEditDiv(){
			toggle('exseditdiv');
			document.getElementById("numadddiv").style.display = "none";
		}

		function toggleNumAddDiv(){
			toggle('numadddiv');
			document.getElementById("exseditdiv").style.display = "none";
		}

		function toggleNumEditDiv(){
			toggle('numeditdiv');
			document.getElementById("occadddiv").style.display = "none";
		}

		function toggleOccAddDiv(){
			toggle('occadddiv');
			document.getElementById("numeditdiv").style.display = "none";
		}

		function verfifyExsAddForm(f){
			if(f.title.value == ""){
				alert("Title can't be empty");
				return false;
			}
			return true;
		}

		function verifyExsEditForm(f){
			if(f.title.value == ""){
				alert("Title can't be empty");
				return false;
			}
			return true;
		}

		function verifyExsMergeForm(f){
			if(t.targetometid == ""){
				alert("You need to select a target exsiccata to merge into");
				return false;
			}
			else{
				return confirm("Are you sure you want to merge this exsiccata into the target below?");
			}
		}

		function verifyNumAddForm(f){
			if(f.exsnumber.value == ""){
				alert("Number can't be empty");
				return false;
			}
			return true;
		}

		function verifyNumEditForm(f){
			if(f.exsnumber.value == ""){
				alert("Number can't be empty");
				return false;
			}
			return true;
		}

		function verifyNumTransferForm(f){
			if(t.targetometid == ""){
				alert("You need to select a target exsiccata to merge into");
				return false;
			}
			else{
				return confirm("Are you sure you want to transfer this exsiccata into the target exsiccata?");
			}
		}

		function verifyOccAddForm(f){
			if(f.occaddcollid.value == ""){
				alert("Please select a collection");
				return false;
			}
			if(f.identifier.value == "" && (f.recordedby.value == "" || f.recordnumber.value == "")){
				alert("Catalog Number or Collector needs to be filled in");
				return false;
			}
			if(f.ranking.value && !isNumeric(f.ranking.value)){
				alert("Ranking can only be a number");
				return false;
			}
			return true;
		}

		function verifyOccEditForm(f){
			if(f.collid.options[0].selected == true || f.collid.options[1].selected){
				alert("The Collection pulldown need to be selected");
				return false;
			}
			if(f.occid.value == ""){
				alert("Occurrences ID can't be empty");
				return false;
			}
			return true;
		}

		function verifyOccTransferForm(f){
			if(f.targetometid.value == ""){
				alert("Please select an exsiccata title");
				return false;
			}
			if(f.targetexsnumber.value == ""){
				alert("Please enter an exsiccata number");
				return false;
			}
			return true;
		}

		function specimenOnlyChanged(cbObj){
			var divObj = document.getElementById('qryextradiv');
			var f = cbObj.form;
			if(cbObj.checked == true){
				divObj.style.display = "block";
			}
			else{
				divObj.style.display = "none";
				f.imagesonly.checked = false;
				f.collid.options[0].selected = true;
			}
			f.submit();
		}

		function initiateExsTitleLookup(inputObj){
			//To be used to convert title lookups to jQuery autocomplete functions

		}

		function openIndPU(occId){
			var wWidth = 900;
			if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
			if(wWidth > 1200) wWidth = 1200;
			newWindow = window.open('../individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if(newWindow.opener == null) newWindow.opener = self;
			return false;
		}

		<?php
		if($omenid){
			//Exsiccata number section can have a large number of ometid select look ups; using javascript makes page more efficient
			$selectValues = '';
			//Added "k" prefix to key so that Chrom would maintain the correct sort order
			foreach($selectLookupArr as $k => $vStr){
				$selectValues .= ',k'.$k.': "'.$vStr.'"';
			}
			?>
			function buildExsSelect(selectObj){
				var selectValues = {<?php echo substr($selectValues,1); ?>};

				for(key in selectValues) {
					try{
						selectObj.add(new Option(selectValues[key], key), null);
					}
					catch(e){ //IE
						selectObj.add(new Option(selectValues[key], key));
					}
				}
			}
			<?php
		}
		?>
	</script>
	<style type="text/css">
		#option-div { margin: 5px; width: 300px; text-align: left; float: right; min-height: 325px; }
		#option-div fieldset { background-color:#f2f2f2; }
		.field-div { margin: 2px 0px; }
		.exs-div { margin-bottom: 5px }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_exsiccati_index)?$collections_exsiccati_index:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<?php
		if($ometid || $omenid){
			echo '<a href="index.php"><b>Return to main Exsiccatae Index</b></a>';
		}
		else{
			echo '<a href="index.php"><b>Exsiccatae Index</b></a>';
		}
		?>
	</div>
	<!-- This is inner text! -->
	<div id="innertext" style="width:95%;">
		<?php
		if($statusStr){
			echo '<hr/>';
			echo '<div style="margin:10px;color:'.(strpos($statusStr,'SUCCESS') === false?'red':'green').';">'.$statusStr.'</div>';
			echo '<hr/>';
		}
		if(!$ometid && !$omenid){
			?>
			<div id="option-div">
				<form name="optionform" action="index.php" method="post">
					<fieldset>
					    <legend><b>Options</b></legend>
				    	<div>
				    		<b>Search:</b>
							<input type="text" name="searchterm" value="<?php echo $searchTerm;?>" size="20" onchange="this.form.submit()" />
						</div>
						<div title="including without linked specimen records">
							<input type="checkbox" name="specimenonly" value="1" <?php echo ($specimenOnly?"CHECKED":"");?> onchange="specimenOnlyChanged(this)" />
							Display only those w/ specimens
						</div>
						<div id="qryextradiv" style="margin-left:15px;display:<?php echo ($specimenOnly?'block':'none'); ?>;" title="including without linked specimen records">
							<div>
								Limit to:
								<select name="collid" style="width:230px;" onchange="this.form.submit()">
									<option value="">All Collections</option>
									<option value="">-----------------------</option>
									<?php
									$acroArr = $exsManager->getCollArr('all');
									foreach($acroArr as $id => $collTitle){
										echo '<option value="'.$id.'" '.($id==$collId?'SELECTED':'').'>'.$collTitle.'</option>';
									}
									?>
								</select>
							</div>
							<div>
							    <input name='imagesonly' type='checkbox' value='1' <?php echo ($imagesOnly?"CHECKED":""); ?> onchange="this.form.submit()" />
							    Display only those w/ images
							</div>
						</div>
						<div style="margin:5px 0px 0px 5px;">
							Display and sort by:<br />
							<input type="radio" name="sortby" value="0" <?php echo ($sortBy == 0?"CHECKED":""); ?> onchange="this.form.submit()"> Title
							<input type="radio" name="sortby" value="1" <?php echo ($sortBy == 1?"CHECKED":""); ?> onchange="this.form.submit()"> Abbreviation
						</div>
						<div style="margin-top:5px">
							<div style="float:right;">
								<span title="Exsiccata download: titles only"><button name="formsubmit" type="submit" value="dlexs_titleOnly"><img src="../../images/dl.png" style="width:15px" /></button></span>
								<span title="Exsiccata download: with numbers and occurrences"><button name="formsubmit" type="submit" value="dlexs"><img src="../../images/dl.png" style="width:15px" /></button></span>
							</div>
							<div>
								<button name="formsubmit" type="submit" value="rebuildList">Rebuild List</button>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div style="font-weight:bold;font-size:120%;">Exsiccatae Titles</div>
			<?php
			if($isEditor){
				?>
				<div style="cursor:pointer;float:right;" onclick="toggle('exsadddiv');" title="Edit Exsiccata Number">
					<img style="border:0px;" src="../../images/add.png" />
				</div>
				<div id="exsadddiv" style="display:none;">
					<form name="exsaddform" action="index.php" method="post" onsubmit="return verfifyExsAddForm(this)">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Add New Exsiccata</b></legend>
							<div class="field-div">
								Title:<br/><input name="title" type="text" value="" style="width:90%;" />
							</div>
							<div class="field-div">
								Abbr:<br/><input name="abbreviation" type="text" value="" style="width:480px;" />
							</div>
							<div class="field-div">
								Editor:<br/><input name="editor" type="text" value="" style="width:300px;" />
							</div>
							<div class="field-div">
								Number Range:<br/><input name="exsrange" type="text" value="" />
							</div>
							<div class="field-div">
								Date range:<br/>
								<input name="startdate" type="text" value="" /> -
								<input name="enddate" type="text" value="" />
							</div>
							<div class="field-div">
								Source:<br/><input name="source" type="text" value="" style="width:480px;" />
							</div>
							<div class="field-div">
								Source Identifier (e.g. <b>Ind</b>Exs URL):<br/><input name="sourceidentifier" type="text" value="" style="width:90%;" />
							</div>
							<div class="field-div">
								Notes:<br/><input name="notes" type="text" value="" style="width:90%" />
							</div>
							<div style="margin:10px;">
								<input name="formsubmit" type="submit" value="Add Exsiccata Title" />
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			?>
			<ul>
				<?php
				$titleArr = $exsManager->getTitleArr($searchTerm, $specimenOnly, $imagesOnly, $collId, $sortBy);
				if($titleArr){
					foreach($titleArr as $k => $tArr){
						?>
						<li>
							<?php
							echo '<div class="exs-div">';
							echo '<div class="exstitle-div"><a href="index.php?ometid='.$k.'&specimenonly='.$specimenOnly.'&imagesonly='.$imagesOnly.'&collid='.$collId.'&sortBy='.$sortBy.'">';
							echo $tArr['title'];
							echo '</a></div>';
							$extra = '';
							if($tArr['editor']) $extra  = $tArr['editor'];
							if($tArr['exsrange']) $extra .= ' ['.$tArr['exsrange'].']';
							if($extra) echo '<div class="exseditor-div" style="margin-left:15px;">'.$extra.'</div>';
							echo '</div>';
							?>
						</li>
						<?php
					}
				}
				else echo '<div style="margin:20px;font-size:120%;">There are no exsiccatae matching your request</div>';
				?>
			</ul>
			<?php
		}
		elseif($ometid){
			if($exsArr = $exsManager->getTitleObj($ometid)){
				?>
				<div>
					<?php
					if($isEditor){
						?>
						<div style="float:right;">
							<span style="cursor:pointer;" onclick="toggleExsEditDiv('exseditdiv');" title="Edit Exsiccata">
								<img style="border:0px;" src="../../images/edit.png" />
							</span>
							<span style="cursor:pointer;" onclick="toggleNumAddDiv('numadddiv');" title="Add Exsiccata Number">
								<img style="border:0px;" src="../../images/add.png" />
							</span>
						</div>
						<?php
					}
					echo '<div style="font-weight:bold;font-size:120%;">'.$exsArr['title'].'</div>';
					if(isset($exsArr['sourceidentifier'])){
						if(preg_match('/^http.+IndExs.+={1}(\d+)$/', $exsArr['sourceidentifier'], $m)) echo ' (<a href="'.$exsArr['sourceidentifier'].'" target="_blank">IndExs #'.$m[1].'</a>)';
					}
					if($exsArr['abbreviation']) echo '<div>Abbreviation: '.$exsArr['abbreviation'].'</div>';
					if($exsArr['editor']) echo '<div>Editor(s): '.$exsArr['editor'].'</div>';
					if($exsArr['exsrange']) echo '<div>Range: '.$exsArr['exsrange'].'</div>';
					if($exsArr['notes']) echo '<div>Notes: '.$exsArr['notes'].'</div>';
					?>
				</div>
				<div id="exseditdiv" style="display:none;">
					<form name="exseditform" action="index.php" method="post" onsubmit="return verifyExsEditForm(this);">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Edit Title</b></legend>
							<div class="field-div">
								Title:<br/><input name="title" type="text" value="<?php echo $exsArr['title']; ?>" style="width:90%;" />
							</div>
							<div class="field-div">
								Abbr:<br/><input name="abbreviation" type="text" value="<?php echo $exsArr['abbreviation']; ?>" style="width:500px;" />
							</div>
							<div class="field-div">
								Editor:<br/><input name="editor" type="text" value="<?php echo $exsArr['editor']; ?>" style="width:300px;" />
							</div>
							<div class="field-div">
								Number range:<br/><input name="exsrange" type="text" value="<?php echo $exsArr['exsrange']; ?>" />
							</div>
							<div class="field-div">
								Date range:<br/>
								<input name="startdate" type="text" value="<?php echo $exsArr['startdate']; ?>" /> -
								<input name="enddate" type="text" value="<?php echo $exsArr['enddate']; ?>" />
							</div>
							<div class="field-div">
								Source:<br/><input name="source" type="text" value="<?php echo $exsArr['source']; ?>" style="width:480px;" />
							</div>
							<div class="field-div">
								Source Identifier (e.g. <b>Ind</b>Exs URL):<br/><input name="sourceidentifier" type="text" value="<?php echo $exsArr['sourceidentifier']; ?>" style="width:90%" />
							</div>
							<div class="field-div">
								Notes:<br/><input name="notes" type="text" value="<?php echo $exsArr['notes']; ?>" style="width:90%" />
							</div>
							<div style="margin:10px;">
								<input name="ometid" type="hidden" value="<?php echo $ometid; ?>" />
								<input name="formsubmit" type="submit" value="Save" />
							</div>
						</fieldset>
					</form>
					<form name="exdeleteform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this exsiccata?');">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Delete Exsiccata</b></legend>
							<div style="margin:10px;">
								<input name="ometid" type="hidden" value="<?php echo $ometid; ?>" />
								<input name="formsubmit" type="submit" value="Delete Exsiccata" />
							</div>
						</fieldset>
					</form>
					<form name="exmergeform" action="index.php" method="post" onsubmit="return verifyExsMergeForm(this);">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Merge Exsiccatae</b></legend>
							<div style="margin:10px;">
								Target Exsiccata:<br/>
								<select name="targetometid" style="max-width:90%;">
									<option value="">-------------------------------</option>
									<?php
									foreach($selectLookupArr as $titleId => $titleStr){
										echo '<option value="'.$titleId.'">'.$titleStr.'</option>';
									}
									?>
								</select>
							</div>
							<div style="margin:10px;">
								<input name="ometid" type="hidden" value="<?php echo $ometid; ?>" />
								<input name="formsubmit" type="submit" value="Merge Exsiccatae" />
							</div>
						</fieldset>
					</form>
				</div>
				<hr/>
				<div id="numadddiv" style="display:none;">
					<form name="numaddform" action="index.php" method="post" onsubmit="return verifyNumAddForm(this);">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Add Exsiccata Number</b></legend>
							<div style="margin:2px;">
								Exsiccata Number: <input name="exsnumber" type="text" />
							</div>
							<div style="margin:2px;">
								Notes: <input name="notes" type="text" style="width:90%" />
							</div>
							<div style="margin:10px;">
								<input name="ometid" type="hidden" value="<?php echo $ometid; ?>" />
								<input name="formsubmit" type="submit" value="Add New Number" />
							</div>
						</fieldset>
					</form>
				</div>
				<div style="margin-left:10px;">
					<ul>
						<?php
						$exsNumArr = $exsManager->getExsNumberArr($ometid,$specimenOnly,$imagesOnly,$collId);
						if($exsNumArr){
							foreach($exsNumArr as $k => $numArr){
								?>
								<li>
									<?php
									echo '<div><a href="index.php?omenid='.$k.'">';
									echo '#'.$numArr['number'];
									if($numArr['sciname']) echo ' - <i>'.$numArr['sciname'].'</i>';
									if($numArr['occurstr']) echo ', '.$numArr['occurstr'];
									echo '</a></div>';
									if($numArr['notes']) echo '<div style="margin-left:15px;">'.$numArr['notes'].'</div>';
									?>
								</li>
								<?php
							}
						}
						else{
							echo '<div style="font-weight:bold;font-size:110%;">';
							echo 'There are no exsiccata numbers in database ';
							echo '</div>';
						}
						?>
					</ul>
				</div>
				<?php
			}
			else{
				echo '<div style="font-weight:bold;font-size:110%;">';
				echo 'Unable to locate exsicati record';
				echo '</div>';
			}
		}
		elseif($omenid){
			if($mdArr = $exsManager->getExsNumberObj($omenid)){
				if($isEditor){
					?>
					<div style="float:right;">
						<span style="cursor:pointer;" onclick="toggleNumEditDiv('numeditdiv');" title="Edit Exsiccata Number">
							<img style="border:0px;" src="../../images/edit.png"/>
						</span>
						<span style="cursor:pointer;" onclick="toggleOccAddDiv('occadddiv');" title="Add Occurrence to Exsiccata Number">
							<img style="border:0px;" src="../../images/add.png" />
						</span>
					</div>
					<?php
				}
				?>
				<div style="font-weight:bold;font-size:120%;">
					<?php
					echo '<a href="index.php?ometid='.$mdArr['ometid'].'">'.$mdArr['title'].'</a> #'.$mdArr['exsnumber'];
					?>
				</div>
				<div style="margin-left:15px;">
					<?php
					echo $mdArr['abbreviation'].'</br>';
					echo $mdArr['editor'];
					if($mdArr['exsrange']) echo ' ['.$mdArr['exsrange'].']';
					if($mdArr['notes']) echo '</br>'.$mdArr['notes'];
					if(isset($mdArr['sourceidentifier'])){
						if(preg_match('/^http.+IndExs.+={1}(\d+)$/', $mdArr['sourceidentifier'], $m)) echo '<br/><a href="'.$mdArr['sourceidentifier'].'" target="_blank">IndExs #'.$m[1].'</a>';
					}
					?>
				</div>
				<div id="numeditdiv" style="display:none;">
					<form name="numeditform" action="index.php" method="post" onsubmit="return verifyNumEditForm(this)">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Edit Exsiccata Number</b></legend>
							<div style="margin:2px;">
								Number: <input name="exsnumber" type="text" value="<?php echo $mdArr['exsnumber']; ?>" />
							</div>
							<div style="margin:2px;">
								Notes: <input name="notes" type="text" value="<?php echo $mdArr['notes']; ?>" style="width:90%;" />
							</div>
							<div style="margin:10px;">
								<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
								<input name="formsubmit" type="submit" value="Save Edits" />
							</div>
						</fieldset>
					</form>
					<form name="numdelform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this exsiccata number?')">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Delete exsiccata Number</b></legend>
							<div style="margin:10px;">
								<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
								<input name="ometid" type="hidden" value="<?php echo $mdArr['ometid']; ?>" />
								<input name="formsubmit" type="submit" value="Delete Number" />
							</div>
						</fieldset>
					</form>
					<form name="numtransferform" action="index.php" method="post" onsubmit="return verifyNumTransferForm(this);">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Transfer Exsiccata Number</b></legend>
							<div style="margin:10px;">
								Target Exsiccata<br/>
								<select name="targetometid" style="max-width:90%;" onfocus="buildExsSelect(this)">
									<option value="">-------------------------------</option>
								</select>
							</div>
							<div style="margin:10px;">
								<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
								<input name="ometid" type="hidden" value="<?php echo $mdArr['ometid']; ?>" />
								<input name="formsubmit" type="submit" value="Transfer Number" />
							</div>
						</fieldset>
					</form>
				</div>
				<div id="occadddiv" style="display:<?php echo ($occidToAdd?'block':'none') ?>;">
					<form name="occaddform" action="index.php" method="post" onsubmit="return verifyOccAddForm(this)">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Add Occurrence Record to Exsiccata Number</b></legend>
							<div style="margin:2px;">
								Collection:  <br/>
								<select name="occaddcollid">
									<option value="">Select a Collection</option>
									<option value="">----------------------</option>
									<?php
									$collArr = $exsManager->getCollArr();
									foreach($collArr as $id => $collName){
										echo '<option value="'.$id.'">'.$collName.'</option>';
									}
									?>
									<option value="occid">Symbiota Primary Key (occid)</option>
								</select>
							</div>
							<div style="margin:10px 0px;height:40px;">
								<div style="margin:2px;float:left;">
									Catalog Number <br/>
									<input name="identifier" type="text" value="" />
								</div>
								<div style="padding:10px;float:left;">
									<b>- OR -</b>
								</div>
								<div style="margin:2px;float:left;">
									Collector (last name): <br/>
									<input name="recordedby" type="text" value="" />
								</div>
								<div style="margin:2px;float:left;">
									Number: <br/>
									<input name="recordnumber" type="text" value="" />
								</div>
							</div>
							<div style="margin:2px;clear:both;">
								Ranking: <br/>
								<input name="ranking" type="text" value="" />
							</div>
							<div style="margin:2px;">
								Notes: <br/>
								<input name="notes" type="text" value="" style="width:500px;" />
							</div>
							<div style="margin:10px;">
								<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
								<input name="formsubmit" type="submit" value="Add Specimen Link" />
							</div>
						</fieldset>
					</form>
				</div>
				<hr/>
				<div style="margin:15px 10px 0px 0px;">
					<?php
					$occurArr = $exsManager->getExsOccArr($omenid);
					if($exsOccArr = array_shift($occurArr)){
						?>
						<table style="width:90%;">
							<?php
							foreach($exsOccArr as $k => $occArr){
								?>
								<tr>
									<td>
										<div style="font-weight:bold;">
											<?php
											echo $occArr['collname'];
											?>
										</div>
										<div style="">
											<div style="">
												Catalog #: <?php echo $occArr['catalognumber']; ?>
											</div>
											<?php
											if($occArr['occurrenceid']){
												echo '<div style="float:right;">';
												echo $occArr['occurrenceid'];
												echo '</div>';
											}
											?>
										</div>
										<div style="clear:both;">
											<?php
											echo $occArr['recby'];
											echo ($occArr['recnum']?' #'.$occArr['recnum'].' ':' s.n. ');
											echo '<span style="margin-left:70px;">'.$occArr['eventdate'].'</span> ';
											?>
										</div>
										<div style="clear:both;">
											<?php
											echo '<i>'.$occArr['sciname'].'</i> ';
											echo $occArr['author'];
											?>
										</div>
										<div>
											<?php
											echo $occArr['country'];
											echo (($occArr['country'] && $occArr['state'])?', ':'').$occArr['state'];
											echo ($occArr['county']?', '.$occArr['county']:'');
											echo ($occArr['locality']?', '.$occArr['locality']:'');
											?>
										</div>
										<div>
											<?php echo ($occArr['notes']?$occArr['notes']:''); ?>
										</div>
										<div>
											<a href="#" onclick="openIndPU(<?php echo $k; ?>)">
												Full Record Details
											</a>
										</div>
									</td>
									<td style="width:100px;">
										<?php
										if(array_key_exists('img',$occArr)){
											$imgArr = array_shift($occArr['img']);
											?>
											<a href="<?php echo $imgArr['url']; ?>">
												<img src="<?php echo $imgArr['tnurl']; ?>" style="width:75px;" />
											</a>
											<?php
										}
										if($isEditor){
											?>
											<div style="cursor:pointer;float:right;" onclick="toggle('occeditdiv-<?php echo $k; ?>');" title="Edit Occurrence Link">
												<img style="border:0px;" src="../../images/edit.png"/>
											</div>
											<?php
										}
										?>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div id="occeditdiv-<?php echo $k; ?>" style="display:none;">
											<form name="occeditform-<?php echo $k; ?>" action="index.php" method="post" onsubmit="return verifyOccEditForm(this)">
												<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
													<legend><b>Edit Specimen Link</b></legend>
													<div style="margin:2px;">
														Ranking: <input name="ranking" type="text" value="<?php echo $occArr['ranking']; ?>" />
													</div>
													<div style="margin:2px;">
														Notes: <input name="notes" type="text" value="<?php echo $occArr['notes']; ?>" style="width:450px;" />
													</div>
													<div style="margin:10px;">
														<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
														<input name="occid" type="hidden" value="<?php echo $k; ?>" />
														<input name="formsubmit" type="submit" value="Save Specimen Link Edit" />
													</div>
												</fieldset>
											</form>
											<form name="occdeleteform-<?php echo $k; ?>" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete the link to this specimen?')">
												<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
													<legend><b>Delete Specimen Link</b></legend>
													<div style="margin:10px;">
														<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
														<input name="occid" type="hidden" value="<?php echo $k; ?>" />
														<input name="formsubmit" type="submit" value="Delete Link to Specimen" />
													</div>
												</fieldset>
											</form>
											<form name="occtransferform-<?php echo $k; ?>" action="index.php" method="post" onsubmit="return verifyOccTransferForm(this)">
												<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
													<legend><b>Transfer Specimen Link</b></legend>
													<div style="margin:10px;">
														Target Exsiccata<br/>
														<select name="targetometid" style="max-width:90%;" onfocus="buildExsSelect(this)">
															<option value="">Select the Target Exsiccata</option>
															<option value="">-------------------------------</option>
														</select>
													</div>
													<div style="margin:10px;">
														Target Exsiccata Number<br/>
														<input name="targetexsnumber" type="text" value="" />
													</div>
													<div style="margin:10px;">
														<input name="omenid" type="hidden" value="<?php echo $omenid; ?>" />
														<input name="occid" type="hidden" value="<?php echo $k; ?>" />
														<input name="formsubmit" type="submit" value="Transfer Specimen" />
													</div>
												</fieldset>
											</form>
										</div>
										<div style="margin:10px 0px 10px 0px;">
											<hr/>
										</div>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<?php
					}
					else{
						echo '<li>There are no specimens linked to this exsiccata number</li>';
					}
					?>
				</div>
				<?php
			}
			else{
				echo '<div style="font-weight:bold;font-size:110%;">';
				echo 'Unable to locate number for that exsicati record';
				echo '</div>';
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>