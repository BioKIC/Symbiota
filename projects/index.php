<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImInventories.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/projects/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/projects/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/projects/index.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';
if(!$pid && array_key_exists('proj',$_GET)) $pid = filter_var($_GET['proj'], FILTER_SANITIZE_NUMBER_INT);
$editMode = array_key_exists('emode', $_REQUEST) ? filter_var($_REQUEST['emode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$newProj = array_key_exists('newproj', $_REQUEST) ? 1 : 0;
$projSubmit = array_key_exists('projsubmit', $_REQUEST) ? $_REQUEST['projsubmit'] : '';
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$statusStr = '';

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
<html lang="<?= $LANG_TAG ?>">
<head>
	<title><?= $DEFAULT_TITLE ?> <?= $LANG['INVPROJ'] ?></title>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-ui.min.js" type="text/javascript"></script>
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
		var tabIndex = <?= $tabIndex ?>;

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
				alert("<?= $LANG['PROJNAMEEMP'] ?>.");
				return false;
			}
			else if(!isNumeric(f.sortsequence.value)){
				alert("<?= $LANG['ONLYNUMER'] ?>.");
				return false;
			}
			else if(f.fulldescription.value.length > 2000){
				alert("<?= $LANG['DESCMAXCHAR'] ?>" + f.fulldescription.value.length + " <?= $LANG['CHARLONG'] ?>.");
				return false;
			}
			return true;
		}

		function validateChecklistForm(f){
			if(f.clid.value == ""){
				alert("<?= $LANG['SELECTCHECKPULL'] ?>");
				return false;
			}
			return true;
		}

		function validateManagerAddForm(f){
			if(f.uid.value == ""){
				alert("<?= $LANG['CHOOSEUSER'] ?>");
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
	?>
	<div class="navpath">
		<a href="<?= $CLIENT_ROOT ?>/"><?= $LANG['NAV_HOME'] ?> </a> &gt;&gt; 
		<b><a href="index.php?pid=<?= $pid ?>"><?= $LANG['INVPROJLIST'] ?></a></b>
	</div>

	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading screen-reader-only"><?= $LANG['VIEW_PROJECT'] ?></h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;font-weight:bold;color:<?= (stripos($statusStr,'error')!==false?'red':'green') ?>;">
				<?= $statusStr ?>
			</div>
			<hr/>
			<?php
		}
		if($projArr || $newProj){
			if($isEditor && !$newProj){
				?>
				<div style="float:right;" title="<?= $LANG['TOGGLEEDIT'] ?>">
					<a href="#" onclick="toggleById('tabs');return false;">
							<?= $LANG['EDIT'] ?>
							<img src="../images/edit.png" style="width:1.2em;" alt="<?= $LANG['PENCIL_ALT'] ?>" />
					</a>
				</div>
				<?php
			}
			if($projArr){
				?>
				<h1><?= $projArr["projname"] ?></h1>
				<div style='margin: 10px;'>
					<div>
						<b><?= $LANG['PROJMANAG'] ?></b>
						<?= $projArr["managers"] ?>
					</div>
					<div style='margin-top:10px;'>
						<?= $projArr["fulldescription"] ?>
					</div>
					<div style='margin-top:10px;'>
						<?= $projArr["notes"] ?>
					</div>
				</div>
				<?php
			}
			if($isEditor){
				?>
				<div id="tabs" style="height:auto;margin:10px;display:<?= ($newProj||$editMode?'block':'none') ?>;">
					<ul>
						<li><a href="#mdtab"><span><?= $LANG['METADATA'] ?></span></a></li>
						<?php
						if($pid){
							?>
							<li><a href="managertab.php?pid=<?= $pid ?>"><span><?= $LANG['INVMANAG'] ?></span></a></li>
							<li><a href="checklisttab.php?pid=<?= $pid ?>"><span><?= $LANG['CHECKMANAG']?></span></a></li>
							<?php
						}
						?>
					</ul>
					<div id="mdtab">
						<section class="fieldset-like background-gray-light">
							<h2> <span> <?= ($newProj ? $LANG['ADD_NEW'] : $LANG['EDIT']);  ?> </span> </h2>
							<form name='projeditorform' action='index.php' method='post' onsubmit="return validateProjectForm(this)">
								<section class="gridlike-form">
									<div class="bottom-breathing-room gridlike-form-row">
										<label for="projname" class="gridlike-form-row-label"  > <?= $LANG['PROJNAME'] ?>: </label>
										<input id="projname" class="gridlike-form-row-input max-width-fit-65" type="text" name="projname"  value="<?php if($projArr) echo htmlspecialchars($projArr["projname"]?? '') ?>"/>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="managers" class="gridlike-form-row-label" > <?= $LANG['MANAG'] ?>: </label>
										<input id="managers" class="gridlike-form-row-input max-width-fit-65" type="text" name="managers" value="<?php if($projArr) echo htmlspecialchars($projArr["managers"]??''); ?>"/>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="fulldescription" class="gridlike-form-row-label"> <?= $LANG['DESCRIP'] ?>: </label>
										<textarea class="gridlike-form-row-input max-width-fit-65" rows="8" cols="45" id="fulldescription"  name="fulldescription" maxlength="5000"><?php if($projArr) echo htmlspecialchars($projArr["fulldescription"]?? ''); ?></textarea>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="notes" class="gridlike-form-row-label"> <?= $LANG['NOTES'] ?>: </label>
										<input type="text" class="gridlike-form-row-input max-width-fit-65" id="notes" name="notes" value="<?php if($projArr) echo htmlspecialchars($projArr["notes"] ?? ''); ?>"/>
									</div>

									<div class="bottom-breathing-room gridlike-form-row">
										<label for="ispublic" class="gridlike-form-row-label"> <?= $LANG['ACCESS'] ?>: </label>
										<select id="ispublic" name="ispublic">
											<option value="0"><?= $LANG['PRIVATE'] ?></option>
											<option value="1" <?= ($projArr&&$projArr['ispublic']?'SELECTED':'') ?>><?= $LANG['PUBLIC'] ?></option>
										</select>
									</div>
									<div style="margin:15px;">
										<?php
										if($newProj){
											?>
											<button name="projsubmit" type="submit" value="addNewProject"><?= $LANG['ADDNEWPR'] ?></button>
											<?php
										}
										else{
											?>
											<input type="hidden" name="pid" value="<?= $pid ?>">
											<button name="projsubmit" type="submit" value="submitEdit"><?= $LANG['SUBMITEDIT'] ?></button>
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
								<legend><?= $LANG['DELPROJECT'] ?></legend>
								<form action="index.php" method="post" onsubmit="return confirm('<?= $LANG['CONFIRMDEL'] ?>')">
									<input type="hidden" name="pid" value="<?= $pid ?>">
									<input type="hidden" name="projsubmit" value="submitDelete" />

									<button class="button-danger" type="submit" name="submit" <?= (count($managerArr)>1 || $researchList)?'disabled':'' ?> >
										<?= $LANG['SUBMITDELETE'] ?>
									</button>
									<?php
									echo '<div style="margin:10px;color:orange">';
									if(count($managerArr) > 1){
										echo $LANG['DELCONDITION1'];
									}
									elseif($researchList){
										echo $LANG['DELCONDITION2'];
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
							<?= $LANG['RESCHECK'] ?>
							<a href="#" onclick="toggleResearchInfoBox(this);" title="<?= $LANG['QUESRESSPEC'] ?>"><img src="../images/qmark.png" style="width:1em;" alt="<?= $LANG['QUESTION_ALT'] ?>" /></a>
							<a href="../checklists/clgmap.php?pid=<?= $pid ?>" title="<?= $LANG['MAPCHECK'] ?>"><img src='../images/world.png' style="width:1em; height:1em;" alt="<?= $LANG['GLOBE_ALT'] ?>"/></a>
						</div>
						<div id="researchlistpopup" class="genericpopup" style="display:none;">
							<img src="../images/qmark.png" style="width:1.3em;" alt="<?= $LANG['QUESTION_ALT'] ?>" />
							<?= $LANG['RESCHECKQUES'] ?>
						</div>
						<?php
						if($KEY_MOD_IS_ACTIVE){
							?>
							<div style="margin-left:15px;font-size:90%">
								<?= $LANG['THE'] ?>
								<img src="../images/key.png" style="width: 1.3em;" alt="<?= $LANG['KEY_SYMBOL'] ?>" />
								<?= $LANG['SYMBOLOPEN'] ?>.
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
							$mapTitle = $LANG['MAPREP'];
							?>
							<div style="float:right;text-align:center;">
								<a href="../checklists/clgmap.php?pid=<?= $pid ?>" title="<?= $mapTitle ?>">
									<img src="<?= $tnUrl ?>" style="width:<?= $tnWidth ?>px;" alt="<?= $mapTitle ?>" />
									<br/>
									<?= $LANG['OPENMAP'] ?>
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
										<a href='../checklists/checklist.php?clid=<?= $key . '&pid=' . $pid ?>'>
											<?= $listArr['name'].(strpos($listArr['access'], 'private') !== false?' <span title="' . $LANG['VIEWABLE_TO_EDITORS'] . '">(' . $LANG['PRIVATE'] . ')</span>':''); ?>
										</a>
										<?php
										if($KEY_MOD_IS_ACTIVE){
											?>
											<span> | </span>
											<a href='../ident/key.php?clid=<?= $key ?>&pid=<?= $pid ?>&taxon=All+Species'>
												<?= $LANG['KEY'] ?>
												<img style='width:1.2em; margin-left: 0.5rem;' src='../images/key.png' alt="<?= $LANG['KEY_SYMBOL'] ?>" />
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
			echo '<h2>' . $LANG['INVPROJ'] . '</h2>';
			$projectArr = $projManager->getProjectList();
			foreach($projectArr as $pid => $projList){
				?>
				<h2><a href="index.php?pid=<?= $pid ?>"><?= htmlspecialchars($projList["projname"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></h2>
				<div style="margin:0px 0px 30px 15px;">
					<div><b><?= $LANG['MANAG'] ?>:</b> <?= ($projList["managers"] ? $projList["managers"] : $LANG['NOT_DEFINED']); ?></div>
					<div style='margin-top:10px;'><?= $projList["descr"] ?></div>
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
