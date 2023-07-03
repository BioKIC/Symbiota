<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
include_once($SERVER_ROOT.'/content/lang/checklists/checklistadmin.'.$LANG_TAG.'.php');
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
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';

$clManager = new ChecklistAdmin();
if(!$clid && isset($_POST['delclid'])) $clid = $_POST['delclid'];
$clManager->setClid($clid);

$statusStr = '';
if($action == 'submitAdd'){
	//Conform User Checklist permission
	if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin'])) || array_key_exists('ClCreate',$USER_RIGHTS)){
		$newClid = $clManager->createChecklist($_POST);
		if($newClid) header('Location: checklist.php?clid='.$newClid);
	}
	//If we made it here the user does not have any checklist roles. cancel further execution.
	$statusStr = 'You do not have permission to create a Checklist. Please contact an administrator.';
}

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin']))){
	$isEditor = 1;
	//Submit checklist MetaData edits
	if($action == 'submitEdit'){
		if($clManager->editChecklist($_POST)){
			header('Location: checklist.php?clid='.$clid.'&pid='.$pid);
		}
		else{
			$statusStr = $clManager->getErrorMessage();
		}
	}
	elseif($action == 'deleteChecklist'){
		if($clManager->deleteChecklist($_POST['delclid'])){
			header('Location: ../index.php');
		}
		else $statusStr = 'ERROR deleting checklist: '.$clManager->getErrorMessage();
	}
	elseif($action == 'addEditor'){
		$statusStr = $clManager->addEditor($_POST['editoruid']);
	}
	elseif(array_key_exists('deleteuid',$_REQUEST)){
		$statusStr = $clManager->deleteEditor($_REQUEST['deleteuid']);
	}
	elseif($action == 'addToProject'){
		$statusStr = $clManager->addProject($_POST['pid']);
	}
	elseif($action == 'deleteProject'){
		$statusStr = $clManager->deleteProject($_POST['pid']);
	}
	elseif($action == 'addPoint'){
		$statusStr = $clManager->addPoint($_POST['pointtid'],$_POST['pointlat'],$_POST['pointlng'],$_POST['notes']);
	}
	elseif($action && array_key_exists('clidadd',$_POST)){
		if(!$clManager->addChildChecklist($_POST['clidadd'])){
			$statusStr = 'ERROR adding child checklist link';
		}
	}
	elseif($action && array_key_exists('cliddel',$_GET)){
		if(!$clManager->deleteChildChecklist($_GET['cliddel'])){
			$statusStr = $clManager->getErrorMessage();
		}
	}
	elseif($action == 'parseChecklist'){
		$parseTid = 0;
		if(array_key_exists('parsetid',$_POST) && is_numeric($_POST['parsetid'])) $parseTid = $_POST['parsetid'];
		$taxon = '';
		if(array_key_exists('taxon',$_POST)) $taxon = filter_var($_POST['taxon'], FILTER_SANITIZE_STRING);
		$resultArr = $clManager->parseChecklist($parseTid, $taxon, $targetClid, $parentClid, $targetPid, $transferMethod, $copyAttributes);
		if($resultArr){
			$statusStr = '<div>Checklist parsed successfully!</div>';
			if(isset($resultArr['targetPid'])){
				$targetPid = $resultArr['targetPid'];
				$statusStr .= '<div style="margin-left:15px"><a href="../projects/index.php?pid='.$targetPid.'" target="_blank">Target project</a></div>';
			}
			if(isset($resultArr['targetClid'])) $statusStr .= '<div style="margin-left:15px"><a href="checklist.php?clid='.$resultArr['targetClid'].'&pid='.$targetPid.'" target="_blank">Target checklist</a></div>';
			if(isset($resultArr['parentClid'])){
				$parentClid = $resultArr['parentClid'];
				$statusStr .= '<div style="margin-left:15px"><a href="checklist.php?clid='.$resultArr['parentClid'].'&pid='.$targetPid.'" target="_blank">Parent checklist</a></div>';
			}
		}
	}
}
$clArray = $clManager->getMetaData();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<title><?php echo $DEFAULT_TITLE.' - '.$LANG['CHECKLIST_ADMIN'];?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../js/jquery.js" type="text/javascript"></script>
	<script src="../js/jquery-ui.js" type="text/javascript"></script>
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
				alert("<?php echo isset($LANG['SELECTUSER'])?$LANG['SELECTUSER']:'Select User'; ?>");
				return false;
			}
			return true;
		}

		function validateAddProjectForm(f){
			if(f.pid.value == ""){
				alert("<?php echo isset($LANG['SELECTPROJECT'])?$LANG['SELECTPROJECT']:'Select a project'; ?>");
				return false;
			}
			return true;
		}

	</script>
	<script type="text/javascript" src="../js/symb/shared.js"></script>
	<script type="text/javascript" src="../js/symb/checklists.checklistadmin.js?ver=2"></script>
	<style type="text/css">
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
	<a href="checklist.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><?php echo $LANG['RETURNCHECK'];?></a> &gt;&gt;
	<b><?php echo $LANG['CHECKLIST_ADMIN']; ?></b>
</div>
<div id='innertext'>
	<div style="color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
		<a href="checklist.php?clid=<?php echo $clid.'&pid='.$pid; ?>">
			<?php echo $clManager->getClName(); ?>
		</a>
	</div>
	<?php
	if($statusStr){
		$statusColor = 'green';
		if(strpos($statusStr, 'ERROR') !== false) $statusColor = 'red';
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
				<li><a href="#admintab"><span><?php echo $LANG['ADMIN'];?></span></a></li>
				<li><a href="checklistadminmeta.php?<?php echo $varBase; ?>"><span><?php echo $LANG['DESCRIPTION'];?></span></a></li>
				<!-- <li><a href="#pointtab"><span>Non-vouchered Points</span></a></li> -->
				<li><a href="checklistadminchildren.php?<?php echo $varChildren; ?>"><span><?php echo $LANG['RELATEDCHECK'];?></span></a></li>

				<?php
				if($clManager->hasVoucherProjects()) echo '<li><a href="imgvouchertab.php?clid='.$clid.'">'.(isset($LANG['ADDIMGVOUCHER'])?$LANG['ADDIMGVOUCHER']:'Add Image Voucher').'</a></li>';
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
									<?php echo '<span title="'.($uNameArr['assignedby']?'Assigned by '.$uNameArr['assignedby']:'').'">'.$uNameArr['name'].'</span>'; ?>
									<form name="delEditorForm-<?php echo $uid; ?>" action="checklistadmin.php" method="post" onclick="return confirm(<?php echo $LANG['REMOVEEDITPRIVCONFIRM']; ?>);" title="<?php echo $LANG['DELETETHISU'];?>" style="display:inline">
										<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
										<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
										<input name="deleteuid" type="hidden" value="<?php echo $uid; ?>" />
										<input name="submitaction" type="hidden" value="DeleteEditor" />
										<input name="submit" type="image" src="../images/drop.png" style="width:12px;" />
									</form>
								</li>
								<?php
							}
							?>
						</ul>
						<?php
					}
					else{
						echo "<div>".$LANG['NOEDITOR']."</div>\n";
					}
					?>
					<fieldset>
						<legend><?php echo (isset($LANG['ADDNEWUSER'])?$LANG['ADDNEWUSER']:'Add New User'); ?></legend>
						<form name="adduser" action="checklistadmin.php" method="post" onsubmit="return verifyAddUser(this)">
							<div>
								<select name="editoruid">
									<option value=""><?php echo (isset($LANG['SELECTUSER'])?$LANG['SELECTUSER']:'Select User'); ?></option>
									<option value="">------------------------------</option>
									<?php
									$userArr = $clManager->getUserList();
									foreach($userArr as $uid => $uName){
										echo '<option value="'.$uid.'">'.$uName.'</option>';
									}
									?>
								</select>
								<button name="submitaction" type="submit" value="addEditor"><?php echo $LANG['ADDEDITOR'];?></button>
								<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
								<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							</div>
						</form>
					</fieldset>
				</div>
				<hr/>
				<div style="margin:20px;">
					<div style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['INVENTORYPROJECTS'])?$LANG['INVENTORYPROJECTS']:'Inventory Project Assignments');?></div>
					<ul>
						<?php
						$projArr = $clManager->getInventoryProjects();
						if($projArr){
							foreach($projArr as $pid => $pName){
								?>
								<li>
									<a href="../projects/index.php?pid=<?php echo $pid; ?>"><?php echo $pName; ?></a>
									<?php
									if(isset($USER_RIGHTS['ProjAdmin']) && in_array($pid, $USER_RIGHTS['ProjAdmin'])){
										?>
										<form name="delProjectForm-<?php echo $pid; ?>" action="checklistadmin.php" method="post" onclick="return confirm(<?php echo $LANG['REMOVEPROJECTCONFIRM']; ?>);" title="<?php echo $LANG['REMOVEPROJECT'];?>" style="display:inline">
											<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
											<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
											<input name="submitaction" type="hidden" value="deleteProject" />
											<input name="submit" type="image" src="../images/drop.png" style="width:12px;" />
										</form>
										<?php
									}
									?>
								</li>
								<?php
							}
						}
						else{
							echo '<li>'.(isset($LANG['CHECKNOTASSIGNED'])?$LANG['CHECKNOTASSIGNED']:'Checklist has not been assigned to any inventory projects').'</li>';
						}
						?>
					</ul>
					<?php
					if(array_key_exists('ProjAdmin',$USER_RIGHTS)){
						if($potentialProjects = array_diff_key($clManager->getPotentialProjects($USER_RIGHTS['ProjAdmin']),$projArr)){
							?>
							<fieldset>
								<legend><?php echo (isset($LANG['LINKTOPROJECT'])?$LANG['LINKTOPROJECT']:'Link to a Project'); ?></legend>
								<form name="addtoprojectform" action="checklistadmin.php" method="post" onsubmit="return validateAddProjectForm(this)">
									<select name="pid">
										<option value=""><?php echo (isset($LANG['SELECTPROJECT'])?$LANG['SELECTPROJECT']:'Select User'); ?></option>
										<option value="">---------------------------------</option>
										<?php
										foreach($potentialProjects as $pid => $pName){
											echo '<option value="'.$pid.'">'.$pName.'</option>';
										}
										?>
									</select>
									<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
									<button name="submitaction" type="submit" value="addToProject"><?php echo isset($LANG['SUBMIT'])?$LANG['SUBMIT']:'Submit';?></button>
								</form>
							</fieldset>
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
							<button name="submitaction" type="submit" value="deleteChecklist" <?php if($projArr || count($editorArr) > 1) echo 'DISABLED'; ?>><?php echo $LANG['DELETECHECK'];?></button>
						</form>
					</div>
				</div>
			</div>
			<!--
			<div id="pointtab">
				<fieldset>
					<legend>Add New Point</legend>
					<form name="pointaddform" target="checklistadmin.php" method="post" onsubmit="return verifyPointAddForm(this)">
						Taxon<br/>
						<select name="pointtid" onchange="togglePoint(this.form);">
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
								<img src="../images/world.png" style="width:12px;" />
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
				</fieldset>
			</div>
			-->
		</div>
		<?php
	}
	else{
		if(!$clid){
			echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['IDNOTSET'].'</div>';
		}
		else{
			echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['NOADMINPERM'].'</div>';
		}
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>