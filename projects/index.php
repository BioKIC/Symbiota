<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImInventories.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');
include_once($SERVER_ROOT.'/content/lang/projects/index.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$pid = array_key_exists('pid',$_REQUEST)?$_REQUEST['pid']:'';
if(!$pid && array_key_exists('proj',$_GET)) $pid = $_GET['proj'];
$editMode = array_key_exists('emode',$_REQUEST)?$_REQUEST['emode']:0;
$newProj = array_key_exists('newproj',$_REQUEST)?1:0;
$projSubmit = array_key_exists('projsubmit',$_REQUEST)?$_REQUEST['projsubmit']:'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$statusStr = '';

//Sanitation
if(!is_numeric($pid)) $pid = 0;
if(!is_numeric($editMode)) $editMode = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;

$projManager = new ImInventories($projSubmit?'write':'readonly');
$projManager->setPid($pid);

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('ProjAdmin', $USER_RIGHTS) && in_array($pid, $USER_RIGHTS['ProjAdmin']))){
	$isEditor = 1;
}

if($isEditor && $projSubmit){
	if($projSubmit == 'addNewProject'){
		$pid = $projManager->insertProject($_POST);
		if(!$pid) $statusStr = $projManager->getErrorMessage();
	}
	elseif($projSubmit == 'submitEdit'){
		$projManager->updateProject($_POST);
	}
	elseif($projSubmit == 'submitDelete'){
		if($projManager->deleteProject($_POST['pid'])){
			$pid = 0;
		}
		else{
			$statusStr = $projManager->getErrorMessage();
		}
	}
	elseif($projSubmit == 'deluid'){
		if(!$projManager->deleteUserRole('ProjAdmin', $pid, $_GET['uid'])){
			$statusStr = $projManager->getErrorMessage();
		}
	}
	elseif($projSubmit == 'Add to Manager List'){
		if(!$projManager->insertUserRole($_POST['uid'], 'ProjAdmin', 'fmprojects', $pid, $SYMB_UID)){
			$statusStr = $projManager->getErrorMessage();
		}
	}
	elseif($projSubmit == 'Add Checklist'){
		if(!$projManager->insertChecklistProjectLink($_POST['clid'])){
			$statusStr = $projManager->getErrorMessage();
		}
	}
	elseif($projSubmit == 'Delete Checklist'){
		if(!$projManager->deleteChecklistProjectLink($_POST['clid'])){
			$statusStr = $projManager->getErrorMessage();
		}
	}
}

$projArr = $projManager->getProjectMetadata();
$researchList = $projManager->getChecklistArr($pid);
foreach($researchList as $clid => $clArr){
	if(strpos($clArr['access'], 'private') !== false && (!isset($USER_RIGHTS['ClAdmin']) || !in_array($clid, $USER_RIGHTS['ClAdmin']))) unset($researchList[$clid]);
}

$managerArr = $projManager->getManagers('ProjAdmin', 'fmprojects', 'fmprojects', $pid);
if(!$researchList && !$editMode){
	$editMode = 1;
	$tabIndex = 2;
	if(!$managerArr) $tabIndex = 1;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['INVPROJ'];?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/tinymce/tinymce.min.js"></script>
	<script type="text/javascript">
		tinymce.init({
			selector: "textarea",
			width: "100%",
			height: 300,
			menubar: false,
			plugins: "link,charmap,code,paste,image",
			toolbar : ["bold italic underline | cut copy paste | outdent indent | subscript superscript | undo redo removeformat | link | image | charmap | code"],
			default_link_target: "_blank",
			paste_as_text: true
		});
	</script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;

		$(document).ready(function() {
			$('#tabs').tabs(
				{ active: tabIndex }
			);
		});

		function toggleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
			}
		}

		function toggleResearchInfoBox(anchorObj){
			var obj = document.getElementById("researchlistpopup");
			var pos = findPos(anchorObj);
			var posLeft = pos[0];
			if(posLeft > 550){
				posLeft = 550;
			}
			obj.style.left = posLeft - 40;
			obj.style.top = pos[1] + 25;
			if(obj.style.display=="block"){
				obj.style.display="none";
			}
			else {
				obj.style.display="block";
			}
			var targetStr = "document.getElementById('researchlistpopup').style.display='none'";
			var t=setTimeout(targetStr,25000);
		}

		function findPos(obj){
			var curleft = 0;
			var curtop = 0;
			if(obj.offsetParent) {
				do{
					curleft += obj.offsetLeft;
					curtop += obj.offsetTop;
				}while(obj = obj.offsetParent);
			}
			return [curleft,curtop];
		}

		function validateProjectForm(f){
			if(f.projname.value == ""){
				alert("<?php echo $LANG['PROJNAMEEMP'];?>.");
				return false;
			}
			else if(!isNumeric(f.sortsequence.value)){
				alert("<?php echo $LANG['ONLYNUMER'];?>.");
				return false;
			}
			else if(f.fulldescription.value.length > 2000){
				alert("<?php echo $LANG['DESCMAXCHAR'];?>" + f.fulldescription.value.length + " <?php echo $LANG['CHARLONG'];?>.");
				return false;
			}
			return true;
		}

		function validateChecklistForm(f){
			if(f.clid.value == ""){
				alert("<?php echo $LANG['SELECTCHECKPULL'];?>");
				return false;
			}
			return true;
		}

		function validateManagerAddForm(f){
			if(f.uid.value == ""){
				alert("<?php echo $LANG['CHOOSEUSER'];?>");
				return false;
			}
			return true;
		}

		function isNumeric(sText){
		   	var validChars = "0123456789-.";
		   	var ch;

		   	for(var i = 0; i < sText.length; i++){
				ch = sText.charAt(i);
				if(validChars.indexOf(ch) == -1) return false;
		   	}
			return true;
		}
	</script>
	<style>
		fieldset.form-color{ background-color:#f2f2f2; margin:15px; padding:20px; }
		fieldset.form-color legend{ font-weight: bold; }
		.gridlike-form-row-label {
			width: 100px;
		}
		.gridlike-form-row-input {
			width: 40%;
		}
		.background-gray-light {
			background-color: #f2f2f2;
		}
		.max-width-fit-65 {
			max-width: 100%;
			width: 65rem;
		}
		.genericpopup {
			position: absolute;
			display: none;
			width: 300px;
			background-color: #efefef;
			padding: 10px;
			-moz-border-radius: 5px;
			-webkit-border-radius: 5px;
			border: black solid 3px;
		}
	</style>
</head>
<body>
	<?php
	$HEADER_URL = '';
	if(isset($projArr['headerurl']) && $projArr['headerurl']) $HEADER_URL = $CLIENT_ROOT.$projArr['headerurl'];
	$displayLeftMenu = (isset($projects_indexMenu)?$projects_indexMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	echo "<div class='navpath'>";
	if(isset($projects_indexCrumbs) && $projArr){
		if($projects_indexCrumbs) echo $projects_indexCrumbs.' &gt;&gt; ';
	}
	else{
		echo "<a href='" . $CLIENT_ROOT . "'>" . (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home') . "</a> &gt;&gt; ";
	}
	echo '<b><a href="index.php?pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars(($projArr?$projArr['projname']: (isset($LANG['INVPROJLIST']) ? $LANG['INVPROJLIST'] : 'Inventory Project List')), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></b>';
	echo "</div>";
	?>

	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading screen-reader-only">View Inventory Project</h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;font-weight:bold;color:<?php echo (stripos($statusStr,'error')!==false?'red':'green');?>;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($projArr || $newProj){
			if($isEditor && !$newProj){
				?>
				<div style="float:right;" title="<?php echo $LANG['TOGGLEEDIT'];?>">
					<a href="#" onclick="toggleById('tabs');return false;">
							<?php echo $LANG['EDIT'] ?>
							<img src="../images/edit.png" style="width:1.2em;" alt="<?php echo $LANG['PENCIL_ALT'] ?>" />
					</a>
				</div>
				<?php
			}
			if($projArr){
				?>
				<h1><?php echo $projArr["projname"]; ?></h1>
				<div style='margin: 10px;'>
					<div>
						<b><?php echo $LANG['PROJMANAG'];?></b>
						<?php echo $projArr["managers"];?>
					</div>
					<div style='margin-top:10px;'>
						<?php echo $projArr["fulldescription"];?>
					</div>
					<div style='margin-top:10px;'>
						<?php echo $projArr["notes"]; ?>
					</div>
				</div>
				<?php
			}
			if($isEditor){
				?>
				<div id="tabs" style="height:auto;margin:10px;display:<?php echo ($newProj||$editMode?'block':'none'); ?>;">
					<ul>
						<li><a href="#mdtab"><span><?php echo htmlspecialchars($LANG['METADATA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></span></a></li>
						<?php
						if($pid){
							?>
							<li><a href="managertab.php?pid=<?php echo $pid ?>"><span><?php echo $LANG['INVMANAG'] ?></span></a></li>
							<li><a href="checklisttab.php?pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><span><?php echo $LANG['CHECKMANAG']?></span></a></li>
							<?php
						}
						?>
					</ul>
					<div id="mdtab">
						<section class="fieldset-like background-gray-light">
							<h2> <span> <?php echo ($newProj ? (isset($LANG['ADD_NEW']) ? $LANG['ADD_NEW'] : 'Add New Project') : (isset($LANG['EDIT']) ? $LANG['EDIT'] : 'Edit Project'));  ?> </span> </h2>
							<form name='projeditorform' action='index.php' method='post' onsubmit="return validateProjectForm(this)">
								<section class="gridlike-form">
									<div class="bottom-breathing-room gridlike-form-row">
										<label for="projname" class="gridlike-form-row-label"  > <?php echo (isset($LANG['PROJNAME']) ? $LANG['PROJNAME'] : 'Project Name'); ?>: </label>
										<input id="projname" class="gridlike-form-row-input max-width-fit-65" type="text" name="projname"  value="<?php if($projArr) echo htmlspecialchars($projArr["projname"]?? '') ?>"/>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="managers" class="gridlike-form-row-label" > <?php echo (isset($LANG['MANAG']) ? $LANG['MANAG'] : 'Managers'); ?>: </label>
										<input id="managers" class="gridlike-form-row-input max-width-fit-65" type="text" name="managers" value="<?php if($projArr) echo htmlspecialchars($projArr["managers"]??''); ?>"/>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="fulldescription" class="gridlike-form-row-label"> <?php echo (isset($LANG['DESCRIP']) ? $LANG['DESCRIP'] : 'Description'); ?>: </label>
										<textarea class="gridlike-form-row-input max-width-fit-65" rows="8" cols="45" id="fulldescription"  name="fulldescription" maxlength="5000"><?php if($projArr) echo htmlspecialchars($projArr["fulldescription"]?? '');?></textarea>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="notes" class="gridlike-form-row-label"> <?php echo (isset($LANG['NOTES']) ? $LANG['NOTES'] : 'Notes'); ?>: </label>
										<input type="text" class="gridlike-form-row-input max-width-fit-65" id="notes" name="notes" value="<?php if($projArr) echo htmlspecialchars($projArr["notes"] ?? '');?>"/>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="ispublic" class="gridlike-form-row-label"> <?php echo (isset($LANG['ACCESS']) ? $LANG['ACCESS'] : 'Access'); ?>: </label>
										<select id="ispublic" name="ispublic">
											<option value="0"><?php echo $LANG['PRIVATE'];?></option>
											<option value="1" <?php echo ($projArr&&$projArr['ispublic']?'SELECTED':''); ?>><?php echo $LANG['PUBLIC'];?></option>
										</select>
									</div>
									<div style="margin:15px;">
										<?php
										if($newProj){
											?>
											<button name="projsubmit" type="submit" value="addNewProject"><?php echo $LANG['ADDNEWPR'];?></button>
											<?php
										}
										else{
											?>
											<input type="hidden" name="pid" value="<?php echo $pid;?>">
											<button name="projsubmit" type="submit" value="submitEdit"><?php echo $LANG['SUBMITEDIT'];?></button>
											<?php
										}
										?>
									</div>
								</section>
							</form>
						</section>
						<?php
						if($pid){
							?>
							<fieldset class="form-color">
								<legend><?php echo (isset($LANG['DELPROJECT'])?$LANG['DELPROJECT']:'Delete Project') ?></legend>
								<form action="index.php" method="post" onsubmit="return confirm('<?php echo (isset($LANG['CONFIRMDEL'])?$LANG['CONFIRMDEL']:'Are you sure you want to delete this inventory Project') ?>')">
									<input type="hidden" name="pid" value="<?php echo $pid;?>">
									<input type="hidden" name="projsubmit" value="submitDelete" />

							<button class="button-danger" type="submit" name="submit" <?= (count($managerArr)>1 || $researchList)?'disabled':'' ?> >
								<?= isset($LANG['SUBMITDELETE'])?$LANG['SUBMITDELETE']:'Delete Project'?>
							</button>
									<?php
									echo '<div style="margin:10px;color:orange">';
									if(count($managerArr) > 1){
										if(isset($LANG['DELCONDITION1'])) echo $LANG['DELCONDITION1'];
										else echo 'Inventory project cannot be deleted until all other managers are removed as project managers';
									}
									elseif($researchList){
										if(isset($LANG['DELCONDITION2'])) echo $LANG['DELCONDITION2'];
										else echo 'Inventory project cannot be deleted until all checklists are removed from the project';
									}
									echo '</div>';
									?>
								</form>
							</fieldset>
							<?php
						}
						?>
					</div>
				</div>
				<?php
			}
			if($pid){
				?>
				<div style="margin:20px;">
					<?php
					if($researchList){
						?>
						<div style="font-weight:bold;font-size:130%;">
							<?php echo $LANG['RESCHECK'];?>
							<span onclick="toggleResearchInfoBox(this);" title="<?php echo $LANG['QUESRESSPEC'];?>" style="cursor:pointer;">
								<img src="../images/qmark.png" style="width:1em;" alt="<?php echo $LANG['QUESTION_ALT'] ?>" />
							</span>
							<a href="../checklists/clgmap.php?pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>" title="<?php echo htmlspecialchars($LANG['MAPCHECK'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>">
								<?php echo $LANG['MAPCHECK'] ?>
								<img src='../images/world.png' style="width:1em; height:1em;" alt="<?php echo $LANG['GLOBE_ALT'] ?>"/>
							</a>
						</div>
						<div id="researchlistpopup" class="genericpopup" style="display:none;">
							<img src="../images/qmark.png" style="width:1.3em;" alt="<?php echo $LANG['QUESTION_ALT'] ?>" />
							<?php echo $LANG['RESCHECKQUES'];?>
						</div>
						<?php
						if($KEY_MOD_IS_ACTIVE){
							?>
							<div style="margin-left:15px;font-size:90%">
								<?php echo $LANG['THE'];?>
								<img src="../images/key.png" style="width: 1.3em;" alt="<?php echo $LANG['KEY_SYMBOL'] ?>" />
								<?php echo $LANG['SYMBOLOPEN'];?>.
							</div>
							<?php
						}
						$coordArr = array();
						$cnt = 0;
						foreach($researchList as $listArr){
							if($cnt < 50 && $listArr['lat']){
								$coordArr[] = $listArr['lat'].','.$listArr['lng'];
							}
							$cnt++;
						}
						if($coordArr){
							$tnUrl = MapSupport::getStaticMap($coordArr);
							$tnWidth = 200;
							if(strpos($tnUrl,$CLIENT_ROOT) === 0) $tnWidth = 100;
							$mapTitle = '';
							if(isset($LANG['MAPREP'])) $mapTitle = $LANG['MAPREP'];
							?>
							<div style="float:right;text-align:center;">
								<a href="../checklists/clgmap.php?pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>" title="<?php echo htmlspecialchars($mapTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<img src="<?php echo htmlspecialchars($tnUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" style="width:<?php echo $tnWidth; ?>px;" alt="<?php echo htmlspecialchars($mapTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" />
									<br/>
									<?php echo $LANG['OPENMAP'];?>
								</a>
							</div>
							<?php
						}
						?>
						<div>
							<ul>
								<?php
								foreach($researchList as $key => $listArr){
									?>
									<li>
										<a href='../checklists/checklist.php?clid=<?php echo $key."&pid=".$pid; ?>'>
											<?php echo $listArr['name'].(strpos($listArr['access'], 'private') !== false?' <span title="Viewable only to editors">(private)</span>':''); ?>
										</a>
										<?php
										if($KEY_MOD_IS_ACTIVE){
											?>
											<span> | </span>
											<a href='../ident/key.php?clid=<?php echo htmlspecialchars($key, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&taxon=All+Species'>
												<?php echo $LANG['KEY'] ?>
												<img style='width:1.2em; margin-left: 0.5rem;' src='../images/key.png' alt="<?php echo $LANG['KEY_SYMBOL'] ?>" />
											</a>
											<?php
										}
										?>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
		}
		else{
			echo '<h2>'.(isset($LANG['INVPROJ'])?$LANG['INVPROJ']:'Inventory Projects').'</h2>';
			$projectArr = $projManager->getProjectList();
			foreach($projectArr as $pid => $projList){
				?>
				<h2><a href="index.php?pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($projList["projname"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></h2>
				<div style="margin:0px 0px 30px 15px;">
					<div><b><?php echo $LANG['MANAG'];?>:</b> <?php echo ($projList["managers"]?$projList["managers"]:'Not defined'); ?></div>
					<div style='margin-top:10px;'><?php echo $projList["descr"]; ?></div>
				</div>
				<?php
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
