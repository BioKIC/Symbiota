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
	if($clArr['access'] == 'private' && (!isset($USER_RIGHTS['ClAdmin']) || !in_array($clid, $USER_RIGHTS['ClAdmin']))) unset($clArr[$clid]);
}

$managerArr = $projManager->getManagers('ProjAdmin', 'fmprojects', 'fmprojects', $pid);
if(!$researchList && !$editMode){
	$editMode = 1;
	$tabIndex = 2;
	if(!$managerArr) $tabIndex = 1;
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['INVPROJ'];?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
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
		echo "<a href='../index.php'>Home</a> &gt;&gt; ";
	}
	echo '<b><a href="index.php?pid='.$pid.'">'.($projArr?$projArr['projname']:'Inventory Project List').'</a></b>';
	echo "</div>";
	?>

	<!-- This is inner text! -->
	<div id="innertext">
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
					<a href="#" onclick="toggleById('tabs');return false;"><img src="../images/edit.png" srcset="../images/edit.svg" style="width:20px;height:20px;" /></a>
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
						<li><a href="#mdtab"><span><?php echo $LANG['METADATA'];?></span></a></li>
						<?php
						if($pid){
							?>
							<li><a href="managertab.php?pid=<?php echo $pid; ?>"><span><?php echo $LANG['INVMANAG'];?></span></a></li>
							<li><a href="checklisttab.php?pid=<?php echo $pid; ?>"><span><?php echo $LANG['CHECKMANAG'];?></span></a></li>
							<?php
						}
						?>
					</ul>
					<div id="mdtab">
						<fieldset class="form-color">
							<legend><?php echo ($newProj?'Add New':'Edit'); ?> Project</legend>
							<form name='projeditorform' action='index.php' method='post' onsubmit="return validateProjectForm(this)">
								<table style="width:100%;">
									<tr>
										<td>
											<?php echo $LANG['PROJNAME'];?>:
										</td>
										<td>
											<input type="text" name="projname" value="<?php if($projArr) echo htmlspecialchars($projArr["projname"]); ?>" style="width:95%;"/>
										</td>
									</tr>
									<tr>
										<td>
											<?php echo $LANG['MANAG'];?>:
										</td>
										<td>
											<input type="text" name="managers" value="<?php if($projArr) echo htmlspecialchars($projArr["managers"]); ?>" style="width:95%;"/>
										</td>
									</tr>
									<tr>
										<td>
											<?php echo $LANG['DESCRIP'];?>:
										</td>
										<td>
											<textarea rows="8" cols="45" name="fulldescription" maxlength="5000" style="width:95%"><?php if($projArr) echo htmlspecialchars($projArr["fulldescription"]);?></textarea>
										</td>
									</tr>
									<tr>
										<td>
											<?php echo $LANG['NOTES'];?>:
										</td>
										<td>
											<input type="text" name="notes" value="<?php if($projArr) echo htmlspecialchars($projArr["notes"]);?>" style="width:95%;"/>
										</td>
									</tr>
									<tr>
										<td>
											<?php echo $LANG['ACCESS'];?>:
										</td>
										<td>
											<select name="ispublic">
												<option value="0"><?php echo $LANG['PRIVATE'];?></option>
												<option value="1" <?php echo ($projArr&&$projArr['ispublic']?'SELECTED':''); ?>><?php echo $LANG['PUBLIC'];?></option>
											</select>
										</td>
									</tr>
									<!--
									<tr>
										<td>
											<?php echo $LANG['SORTSEQ'];?>:
										</td>
										<td>
											<input type="text" name="sortsequence" value="<?php if($projArr) echo $projArr["sortsequence"];?>" style="width:40;"/>
										</td>
									</tr>
									-->
									<tr>
										<td colspan="2">
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
										</td>
									</tr>
								</table>
							</form>
						</fieldset>
						<?php
						if($pid){
							?>
							<fieldset class="form-color">
								<legend><?php echo (isset($LANG['DELPROJECT'])?$LANG['DELPROJECT']:'Delete Project') ?></legend>
								<form action="index.php" method="post" onsubmit="return confirm('<?php echo (isset($LANG['CONFIRMDEL'])?$LANG['CONFIRMDEL']:'Are you sure you want to delete this inventory Project') ?>')">
									<input type="hidden" name="pid" value="<?php echo $pid;?>">
									<input type="hidden" name="projsubmit" value="submitDelete" />
									<?php
									echo '<input type="submit" name="submit" value="'.(isset($LANG['SUBMITDELETE'])?$LANG['SUBMITDELETE']:'Delete Project').'" '.((count($managerArr)>1 || $researchList)?'disabled':'').' />';
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
								<img src="../images/qmark_big.png" srcset="../images/help-circle.svg" style="width:15px; height:15px;" />
							</span>
							<a href="../checklists/clgmap.php?pid=<?php echo $pid;?>" title="<?php echo $LANG['MAPCHECK'];?>">
								<img src='../images/world.png'  srcset="../images/globe.svg" style="width:15px; height:15px;" />
							</a>
						</div>
						<div id="researchlistpopup" class="genericpopup" style="display:none;">
							<img src="../images/triangleup.png" style="position: relative; top: -22px; left: 30px;" />
							<?php echo $LANG['RESCHECKQUES'];?>
						</div>
						<?php
						if($KEY_MOD_IS_ACTIVE){
							?>
							<div style="margin-left:15px;font-size:90%">
								<?php echo $LANG['THE'];?> <img src="../images/key.png" style="width: 12px;" alt="Golden Key Symbol" />
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
								<a href="../checklists/clgmap.php?pid=<?php echo $pid;?>" title="<?php echo $mapTitle; ?>">
									<img src="<?php echo $tnUrl; ?>" style="width:<?php echo $tnWidth; ?>px;" alt="<?php echo $mapTitle; ?>" />
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
											<?php echo $listArr['name'].($listArr['access']=='private'?' <span title="Viewable only to editors">(private)</span>':''); ?>
										</a>
										<?php
										if($KEY_MOD_IS_ACTIVE){
											?>
											<a href='../ident/key.php?clid=<?php echo $key; ?>&pid=<?php echo $pid; ?>&taxon=All+Species'>
												<img style='width:12px;border:0px;' src='../images/key.png'/>
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
				<h2><a href="index.php?pid=<?php echo $pid; ?>"><?php echo $projList["projname"]; ?></a></h2>
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