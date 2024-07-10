<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklistadmin.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/checklistadmin.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/checklistadmin.en.php');
header('Content-Type: text/html; charset='.$CHARSET);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../checklists/checklistadmin.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$targetClid = array_key_exists('targetclid', $_REQUEST) ? filter_var($_REQUEST['targetclid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$transferMethod = array_key_exists('transmethod', $_POST) ? filter_var($_POST['transmethod'], FILTER_SANITIZE_NUMBER_INT) : 0;
$parentClid = array_key_exists('parentclid', $_REQUEST) ? filter_var($_REQUEST['parentclid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$targetPid = array_key_exists('targetpid', $_REQUEST) ? filter_var($_REQUEST['targetpid'], FILTER_SANITIZE_NUMBER_INT) : '';
$copyAttributes = array_key_exists('copyattributes', $_REQUEST) ? filter_var($_REQUEST['copyattributes'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitaction', $_REQUEST) ? htmlspecialchars($_REQUEST['submitaction'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$delclid = array_key_exists('delclid', $_POST) ? htmlspecialchars($_POST['delclid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$editoruid = array_key_exists('editoruid', $_POST) ? htmlspecialchars($_POST['editoruid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$pointtid = array_key_exists('pointtid', $_POST) ? htmlspecialchars($_POST['pointtid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$pointlat = array_key_exists('pointlat', $_POST) ? htmlspecialchars($_POST['pointlat'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$pointlng = array_key_exists('pointlng', $_POST) ? htmlspecialchars($_POST['pointlng'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$notes = array_key_exists('notes', $_POST) ? htmlspecialchars($_POST['notes'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$clidadd = array_key_exists('clidadd', $_POST) ? htmlspecialchars($_POST['clidadd'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$parsetid = array_key_exists('parsetid', $_POST) ? filter_var($_POST['parsetid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxon = array_key_exists('taxon', $_POST) ? htmlspecialchars($_POST['taxon'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';


$clManager = new ChecklistAdmin();
if(!$clid && $delclid) $clid = $delclid;
$clManager->setClid($clid);

$statusStr = '';
if($action == 'submitAdd'){
	//Conform User Checklist permission
	if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin'])) || array_key_exists('ClCreate',$USER_RIGHTS)){
		$newClid = $clManager->createChecklist($_POST);
		if($newClid) header('Location: checklist.php?clid='.$newClid);
	}
	//If we made it here the user does not have any checklist roles. cancel further execution.
	$statusStr = $LANG['NO_PERMISSIONS'];
}

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin']))){
	$isEditor = 1;
	//Submit checklist MetaData edits
	if($action == 'submitEdit'){
		if($clManager->editChecklist($_POST)){
			header('Location: checklist.php?clid=' . $clid . '&pid=' . $pid);
		}
		else{
			$statusStr = $clManager->getErrorMessage();
		}
	}
	elseif($action == 'deleteChecklist'){
		if($clManager->deleteChecklist($delclid)){
			header('Location: ../index.php');
		}
		else $statusStr = $LANG['ERR_DELETING_CHECKLIST'] . ': ' . $clManager->getErrorMessage();
	}
	elseif($action == 'addEditor'){
		$statusStr = $clManager->addEditor($editoruid);
	}
	elseif(array_key_exists('deleteuid',$_REQUEST)){
		$statusStr = $clManager->deleteEditor($_REQUEST['deleteuid']);
	}
	elseif($action == 'addToProject'){
		$statusStr = $clManager->addProject($pid);
	}
	elseif($action == 'deleteProject'){
		$statusStr = $clManager->deleteProject($pid);
	}
	elseif($action == 'addPoint'){
		if(!$clManager->addPoint($pointtid, $pointlat, $pointlng, $notes)){
			$statusStr = $clManager->getErrorMessage();
		}
	}
	elseif($action && array_key_exists('clidadd',$_POST)){
		if(!$clManager->addChildChecklist($clidadd)){
			$statusStr = $LANG['ERR_ADDING_CHILD'];
		}
	}
	elseif($action && array_key_exists('cliddel',$_GET)){
		if(!$clManager->deleteChildChecklist($_GET['cliddel'])){
			$statusStr = $clManager->getErrorMessage();
		}
	}
	elseif($action == 'parseChecklist'){
		$resultArr = $clManager->parseChecklist($parsetid, $taxon, $targetClid, $parentClid, $targetPid, $transferMethod, $copyAttributes);
		if($resultArr){
			$statusStr = '<div>' . $LANG['CHECK_PARSED_SUCCESS'] . '</div>';
			if(isset($resultArr['targetPid'])){
				$targetPid = $resultArr['targetPid'];
				$statusStr .= '<div style="margin-left:15px"><a href="../projects/index.php?pid=' . $targetPid . '" target="_blank" rel="noopener" >' . $LANG['TARGET_PROJ'] . '</a></div>';
			}
			if(isset($resultArr['targetClid'])) $statusStr .= '<div style="margin-left:15px"><a href="checklist.php?clid=' . $resultArr['targetClid'] . '&pid=' . $targetPid . '" target="_blank" rel="noopener" >' . $LANG['TARGET_CHECKLIST'] . '</a></div>';
			if(isset($resultArr['parentClid'])){
				$parentClid = $resultArr['parentClid'];
				$statusStr .= '<div style="margin-left:15px"><a href="checklist.php?clid=' . $resultArr['parentClid'] . '&pid=' . $targetPid . '" target="_blank" rel="noopener" >' . $LANG['PARENT_CHECKLIST'] . '</a></div>';
			}
		}
	}
}
$clArray = $clManager->getMetaData();
$clArray = $clManager->cleanOutArray($clArray);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $CHARSET ?>"/>
	<title><?= $DEFAULT_TITLE . ' - ' . $LANG['CHECKLIST_ADMIN'] ?></title>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../js/tinymce/tinymce.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;

		tinymce.init({
			selector: "textarea",
			width: "100%",
			height: 300,
			menubar: false,
			plugins: "link,charmap,code,paste",
			toolbar : ["bold italic underline | cut copy paste | outdent indent | subscript superscript | undo redo removeformat | link | charmap | code"],
			default_link_target: "_blank",
			paste_as_text: true
		});

		function verifyAddUser(f){
			if(f.editoruid.value == ""){
				alert("<?php echo $LANG['SELECTUSER']; ?>");
				return false;
			}
			return true;
		}

		function validateAddProjectForm(f){
			if(f.pid.value == ""){
				alert("<?php echo $LANG['SELECTPROJECT']; ?>");
				return false;
			}
			return true;
		}

	</script>
	<script type="text/javascript" src="../js/symb/shared.js"></script>
	<script type="text/javascript" src="../js/symb/checklists.checklistadmin.js?ver=2"></script>
	<style>
		.tox-dialog { min-height: 400px }
		fieldset{ padding:15px; margin:40px 10px; }
		legend{ font-weight: bold; }
	</style>
</head>
<body>
<?php
//$HEADER_URL = '';
//if(isset($clArray['headerurl']) && $clArray['headerurl']) $HEADER_URL = $CLIENT_ROOT.$clArray['headerurl'];
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../index.php"><?php echo $LANG['NAV_HOME'];?></a> &gt;&gt;
	<a href="checklist.php?clid=<?php echo $clid . '&pid=' . $pid; ?>"><?php echo $LANG['RETURNCHECK']; ?></a> &gt;&gt;
	<b><?php echo $LANG['CHECKLIST_ADMIN']; ?></b>
</div>
<div id='innertext'>
	<h1 class="page-heading">Manage Checklist</h1>
	<div style="color:#990000;font-size:125%;font-weight:bold;margin:0px 10px 10px 0px;">
		<a href="checklist.php?clid=<?php echo $clid . '&pid=' . $pid; ?>">
			<?php echo $clManager->getClName(); ?>
		</a>
	</div>
	<?php
	if($statusStr){
		$statusColor = 'green';
		if(strpos($statusStr, $LANG['ERROR']) !== false) $statusColor = 'red';
		?>
		<hr />
		<div style="margin:20px;font-weight:bold;color:<?php echo $statusColor;?>;">
			<?php echo $statusStr; ?>
		</div>
		<hr />
		<?php
	}

	if($clid && $isEditor){
		$varBase = 'clid='.$clid.'&pid='.$pid;
		$varChildren = $varBase.'&targetclid='.$targetClid.'&parentclid='.$parentClid.'&targetpid='.$targetPid.'&transmethod='.$transferMethod.'&copyattributes='.$copyAttributes;
		?>
		<div id="tabs" style="margin:10px;">
			<ul>
				<li><a href="#admintab"><span><?= $LANG['ADMIN']; ?></span></a></li>
				<li><a href="checklistadminmeta.php?<?php echo $varBase; ?>"><span><?php echo $LANG['DESCRIPTION'];?></span></a></li>
				<!-- <li><a href="#pointtab"><span>Non-vouchered Points</span></a></li> -->
				<li><a href="checklistadminchildren.php?<?php echo $varChildren; ?>"><span><?php echo $LANG['RELATEDCHECK'];?></span></a></li>

				<?php
				if($clManager->hasVoucherProjects()) echo '<li><a href="imgvouchertab.php?clid=' . $clid . '">' . $LANG['ADDIMGVOUCHER'] . '</a></li>';
				?>
			</ul>
			<div id="admintab">
				<div style="margin:20px;">
					<div style="font-weight:bold;font-size:120%;"><?php echo $LANG['CURREDIT'];?></div>
					<?php
					$editorArr = $clManager->getEditors();
					if($editorArr){
						?>
						<ul>
							<?php
							foreach($editorArr as $uid => $uNameArr){
								?>
								<li>
									<div style="display: flex; align-items: center;">
										<?php echo '<span title="'.($uNameArr['assignedby'] ? $LANG['ASSIGNED_BY'] . ' ' . $uNameArr['assignedby']:'') . '">' . $uNameArr['name'] . '</span>'; ?>
										<form name="delEditorForm-<?php echo $uid; ?>" action="checklistadmin.php" method="post" onclick="return confirm(<?php echo $LANG['REMOVEEDITPRIVCONFIRM']; ?>);" title="<?php echo $LANG['DELETETHISU'];?>" style="display:inline">
											<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
											<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
											<input name="deleteuid" type="hidden" value="<?php echo $uid; ?>" />
											<input name="submitaction" type="hidden" value="DeleteEditor" />
											<input name="submit" type="image" src="../images/drop.png" style="width:1em; margin:0;" onclick="return confirm('<?php echo ($LANG['EDITOR_DELETE']) . '\n' . htmlspecialchars($uNameArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '?' ?>');" alt="<?php echo $LANG['DROP_ICON_FOR_EDITOR']; ?>" />
										</form>
									</div>
								</li>
								<?php
							}
							?>
						</ul>
						<?php
					}
					else{
						echo "<div>" . $LANG['NOEDITOR'] . "</div>\n";
					}
					?>
                    <section class="fieldset-like" style="width:fit-content">
							<h3><span><?php echo $LANG['ADDNEWUSER']; ?></span></h3>
						<form name="adduser" action="checklistadmin.php" method="post" onsubmit="return verifyAddUser(this)" style="display:flex; gap:0.5rem; align-items: center; flex-wrap: wrap">
								<div style="display:flex; gap:0.5rem; align-items: center; flex-wrap: nowrap">
							    <label style="white-space:nowrap"for="editoruid"><?php echo $LANG['SELECTUSER']; ?></label>
								<select style="width:100%" id="editoruid" name="editoruid">
									<option value=""><?php echo $LANG['SELECTUSER']; ?></option>
									<option value="">------------------------------</option>
									<?php
									$userArr = $clManager->getUserList();
									foreach($userArr as $uid => $uName){
										echo '<option value="'.$uid.'">'.$uName.'</option>';
									}
									?>
								</select>
								</div>
								<button name="submitaction" type="submit" value="addEditor" aria-label="<?php echo $LANG['ADDEDITOR'];?>"><?php echo $LANG['ADDEDITOR'];?></button>
								<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
								<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
						</form>
					</section>
				</div>
				<hr/>
				<div style="margin:20px;">
					<div style="font-weight:bold;font-size:120%;"><?php echo $LANG['INVENTORYPROJECTS'];?></div>
					<ul>
						<?php
						$projArr = $clManager->getInventoryProjects();
						if($projArr){
							foreach($projArr as $pid => $pName){
								?>
								<li>
									<a href="../projects/index.php?pid=<?= $pid ?>"><?= htmlspecialchars($pName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
									<?php
									if(isset($USER_RIGHTS['ProjAdmin']) && in_array($pid, $USER_RIGHTS['ProjAdmin'])){
										?>
										<form name="delProjectForm-<?php echo $pid; ?>" action="checklistadmin.php" method="post" onclick="return confirm(<?php echo $LANG['REMOVEPROJECTCONFIRM']; ?>);" title="<?php echo $LANG['REMOVEPROJECT'];?>" style="display:inline">
											<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
											<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
											<input name="submitaction" type="hidden" value="deleteProject" />
											<input name="submit" type="image" src="../images/drop.png" style="width:1em;" onclick="return confirm('<?php echo ($LANG['PROJECT_DELETE']) . ' ' . htmlspecialchars($pName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '?'?>');" alt="<?php echo $LANG['DROP_ICON_FOR_DELETE_PROJECT']; ?>" />
										</form>
										<?php
									}
									?>
								</li>
								<?php
							}
						}
						else{
							echo '<li>' . $LANG['CHECKNOTASSIGNED'] . '</li>';
						}
						?>
					</ul>
					<?php
					if(array_key_exists('ProjAdmin',$USER_RIGHTS)){
						if($potentialProjects = array_diff_key($clManager->getPotentialProjects($USER_RIGHTS['ProjAdmin']),$projArr)){
							?>
							<section class="fieldset-like">
								<h3><span><?php echo $LANG['LINKTOPROJECT']; ?></span></h3>
								<form name="addtoprojectform" action="checklistadmin.php" method="post" onsubmit="return validateAddProjectForm(this)">
								    <label for="pid"><?php echo $LANG['SELECTPROJECT']; ?></label>
									<select id="pid" name="pid">
										<option value=""><?php echo $LANG['SELECTPROJECT']; ?></option>
										<option value="">---------------------------------</option>
										<?php
										foreach($potentialProjects as $pid => $pName){
											echo '<option value="'.$pid.'">'.$pName.'</option>';
										}
										?>
									</select>
									<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
									<button name="submitaction" type="submit" value="addToProject" aria-label="<?php echo $LANG['SUBMIT_BUTTON'];?>"><?php echo $LANG['SUBMIT']; ?></button>
								</form>
							</section>
							<?php
						}
					}

					?>
				</div>
				<hr/>
				<div style="margin:20px;">
					<div style="font-weight:bold;font-size:120%;"><?php echo $LANG['PERMREMOVECHECK'];?></div>
					<div style="margin:10px;">
						<?php echo $LANG['REMOVEUSERCHECK'];?><br/>
						<b><?php echo $LANG['WARNINGNOUN'];?></b>
					</div>
					<div style="margin:15px;">
						<form name="deleteclform" action="checklistadmin.php" method="post" onsubmit="return window.confirm('<?php echo $LANG['CONFIRMDELETE'];?>')">
							<input name="delclid" type="hidden" value="<?php echo $clid; ?>" />
							<button class="button-danger" name="submitaction" type="submit" value="deleteChecklist"  aria-label="<?php echo $LANG['DELETECHECK'];?>" <?php if($projArr || count($editorArr) > 1) echo 'DISABLED'; ?>><?php echo $LANG['DELETECHECK'];?></button>
						</form>
					</div>
				</div>
			</div>
			<!--
			<div id="pointtab">
				<section class="fieldset-like">
					<h3><span>Add New Point</span></h3>
					<form name="pointaddform" target="checklistadmin.php" method="post" onsubmit="return verifyPointAddForm(this)">
						Taxon<br/>
						<label for="pointtid">Select Taxon</label>
						<select id="pointtid" name="pointtid" onchange="togglePoint(this.form);">
							<option value="">Select Taxon</option>
							<option value="">-----------------------</option>
							<?php
							$taxaArr = $clManager->getTaxa();
							foreach($taxaArr as $tid => $sn){
								echo '<option value="'.$tid.'">'.$sn.'</option>';
							}
							?>
						</select>
						<div id="pointlldiv" style="display:none;">
							<div style="float:left;">
								Latitude Centroid<br/>
								<input id="latdec" type="text" name="pointlat" style="width:110px;" value="" />
							</div>
							<div style="float:left;margin-left:5px;">
								Longitude Centroid<br/>
								<input id="lngdec" type="text" name="pointlng" style="width:110px;" value="" />
							</div>
							<div style="float:left;margin:15px 0px 0px 10px;cursor:pointer;" onclick="openPointAid(<?php echo $clArray["latcentroid"].','.$clArray["longcentroid"]?>);">
								<img src="../images/world.png" style="width:1.2em;" />
							</div>
							<div style="clear:both;">
								Notes:<br/>
								<input type="text" name="notes" style="width:95%" value="" />
							</div>
							<div>
								<button name="submitaction" type="submit" value="addPoint">Add Point</button>
								<input type="hidden" name="tabindex" value="2" />
								<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
								<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							</div>
						</div>
					</form>
				</section>
			</div>
			-->
		</div>
		<?php
	}
	else{
		if(!$clid){
			echo '<div><span style="font-weight:bold;font-size:110%;">' . $LANG['ERROR_LOWER'] . ': ' . '</span>' . $LANG['IDNOTSET'] . '</div>';
		}
		else{
			echo '<div><span style="font-weight:bold;font-size:110%;">' . $LANG['ERROR_LOWER'] . ': '. '</span>' . $LANG['NOADMINPERM'] . '</div>';
		}
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
