<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUpload.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/specuploadmanagement.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/specuploadmanagement.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$uspid = array_key_exists('uspid',$_REQUEST)?$_REQUEST['uspid']:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($uspid)) $uspid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$DIRECTUPLOAD = 1; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5; $DWCAUPLOAD = 6; $SKELETAL = 7; $IPTUPLOAD = 8; $NFNUPLOAD = 9; $SYMBIOTA = 13;

$duManager = new SpecUpload();

$duManager->setCollId($collid);
$duManager->setUspid($uspid);

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
if($isEditor){
	if($action == 'saveEdits'){
		if($duManager->editUploadProfile($_POST)){
			$statusStr = (isset($LANG['SUCCESS_IMP'])?$LANG['SUCCESS_IMP']:'SUCCESS: Edits to import profile have been applied');
		}
		else{
			$statusStr = $duManager->getErrorStr();
		}
		$action = '';
	}
	elseif($action == 'createProfile'){
		if($duManager->createUploadProfile($_POST)){
			$statusStr = (isset($LANG['SUCCESS_UP'])?$LANG['SUCCESS_UP']:'SUCCESS: New upload profile added');
		}
		else{
			$statusStr = $duManager->getErrorStr();
		}
		$action = '';
	}
	elseif($action == 'Delete Profile'){
		if($duManager->deleteUploadProfile($uspid)){
			$statusStr = (isset($LANG['SUCCESS_DEL'])?$LANG['SUCCESS_DEL']:'SUCCESS: Upload Profile Deleted');
		}
		else{
			$statusStr = $duManager->getErrorStr();
		}
		$action = '';
	}
}
$duManager->readUploadParameters();
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['UP_PROF_MAN'])?$LANG['UP_PROF_MAN']:'Specimen Upload Profile Manager'); ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script>
		function checkUploadListForm(f){
			if(f.uspid.length == null){
				if(f.uspid.checked) return true;
			}
			else{
				var radioCnt = f.uspid.length;
				for(var counter = 0; counter < radioCnt; counter++){
					if (f.uspid[counter].checked) return true;
				}
			}
			alert("<?php echo (isset($LANG['OPT_PLZ'])?$LANG['OPT_PLZ']:'Please select an Upload Option'); ?>");
			return false;
		}

		function checkParameterForm(f){
			if(f.title.value == ""){
				alert("<?php echo (isset($LANG['TITLE_REQ'])?$LANG['TITLE_REQ']:'Profile title is required'); ?>");
				return false;
			}
			else if(f.uploadtype.value == ""){
				alert("<?php echo (isset($LANG['SELECT_TYPE'])?$LANG['SELECT_TYPE']:'Select Upload Type'); ?>");
				return false;
			}
			else if(f.uploadtype.value == 8 || f.uploadtype.value == 13){
				if(f.path.value == ""){
					alert("<?php echo (isset($LANG['SELECT_PATH'])?$LANG['SELECT_PATH']:'Path to data provider is required'); ?>");
					return false;
				}
			}
			return true;
		}

		function adjustParameterForm(){
			//Hide all
			document.getElementById("platformDiv").style.display='none';
			document.getElementById("serverDiv").style.display='none';
			document.getElementById("portDiv").style.display='none';
			document.getElementById("codeDiv").style.display='none';
			document.getElementById("pathDiv").style.display='none';
			document.getElementById("pkfieldDiv").style.display='none';
			document.getElementById("usernameDiv").style.display='none';
			document.getElementById("passwordDiv").style.display='none';
			document.getElementById("schemanameDiv").style.display='none';
			document.getElementById("cleanupspDiv").style.display='none';
			document.getElementById("querystrDiv").style.display='none';
			document.getElementById("dwca_notes").style.display='none';
			//Then open according to upload type selection
			selValue = document.parameterform.uploadtype.value;
			if(selValue == 1){ //Direct Upload
				document.getElementById("platformDiv").style.display='block';
				document.getElementById("serverDiv").style.display='block';
				document.getElementById("portDiv").style.display='block';
				document.getElementById("usernameDiv").style.display='block';
				document.getElementById("passwordDiv").style.display='block';
				document.getElementById("schemanameDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 3){ //File Upload
				document.getElementById("cleanupspDiv").style.display='block';
			}
			else if(selValue == 4){ //Stored Procedure
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 5){ //Script Upload
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 6){ //Darwin Core Archive Manual Upload
				//document.getElementById("pathDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
			}
			else if(selValue == 7){ //Skeletal File Upload
				document.getElementById("cleanupspDiv").style.display='block';
			}
			else if(selValue == 8){ //IPT or DwC-A resource
				document.getElementById("pathDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("dwca_notes").style.display='block';
			}
			else if(selValue == 13){ //Symbiota resource
				document.getElementById("pathDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("dwca_notes").style.display='block';
			}
		}
	</script>
</head>
<body onload="<?php if($uspid && $action) echo 'adjustParameterForm()'; ?>">
<?php
	$displayLeftMenu = (isset($collections_admin_specuploadMenu)?$collections_admin_specuploadMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($collections_admin_specuploadCrumbs)){
		if($collections_admin_specuploadCrumbs){
			?>
			<div class="navpath">
				<a href="../../index.php"><?php echo (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a> &gt;&gt;
				<?php echo $collections_admin_specuploadCrumbs; ?>
				<b><?php echo (isset($LANG['SPEC_LOADER'])?$LANG['SPEC_LOADER']:'Specimen Loader'); ?></b>
			</div>
			<?php
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php"><?php echo (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a> &gt;&gt;
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo (isset($LANG['COL_MAN_PAN'])?$LANG['COL_MAN_PAN']:'Collection Management Panel'); ?></a> &gt;&gt;
			<b><?php echo (isset($LANG['SPEC_LOADER'])?$LANG['SPEC_LOADER']:'Specimen Loader'); ?></b>
		</div>
		<?php
	}
?>
<!-- This is inner text! -->
<div id="innertext">
	<h1><?php echo (isset($LANG['DAT_UP_MAN'])?$LANG['DAT_UP_MAN']:'Data Upload Management'); ?></h1>
	<?php
	if($statusStr){
		echo '<hr />';
		echo '<div>'.$statusStr.'</div>';
		echo '<hr />';
	}
	if($isEditor){
		if($collid){
			echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
			if($duManager->getCollInfo("uploaddate")) {
				echo '<div style="margin:0px 0px 15px 15px;"><b>Last Upload Date:</b> '.$duManager->getCollInfo('uploaddate').'</div>';
			}
			if(!$action){
			 	$profileList = $duManager->getUploadList();
				?>
				<form name="uploadlistform" action="specupload.php" method="post" onsubmit="return checkUploadListForm(this);">
					<fieldset>
						<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['UP_OPT'])?$LANG['UP_OPT']:'Upload Options'); ?></legend>
						<div style="float:right;">
							<?php
							echo '<a href="specuploadmanagement.php?collid='.$collid.'&action=addprofile"><img src="'.$CLIENT_ROOT.'/images/add.png" style="width:15px;border:0px;" title="'.(isset($LANG['ADD_PROF'])?$LANG['ADD_PROF']:'Add a New Upload Profile').'" /></a>';
							?>
						</div>
						<?php
						if($profileList){
						 	foreach($profileList as $id => $v){
						 		?>
						 		<div style="margin:10px;">
									<input type="radio" name="uspid" value="<?php echo $id.'-'.$v['uploadtype'];?>" />
									<?php echo $v['title']; ?>
									<a href="specuploadmanagement.php?action=editprofile&collid=<?php echo $collid.'&uspid='.$id; ?>" title="<?php echo (isset($LANG['VIEW_PARS'])?$LANG['VIEW_PARS']:'View/Edit Parameters'); ?>"><img src="../../images/edit.png" /></a>
									<input type="hidden" name="uploadtype" value="<?php echo $v['uploadtype']; ?>" />
								</div>
								<?php
						 	}
							?>
							<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
							<div style="margin:10px;">
								<input type="submit" name="action" value="Initialize Upload..." />
							</div>
							<?php
						}
					 	else{
					 		?>
							<div style="padding:30px;">
								<?php echo (isset($LANG['NO_PROFS'])?$LANG['NO_PROFS']:'There are no Upload Profiles associated with this collection'); ?>. <br />
								<?php echo (isset($LANG['CLICK'])?$LANG['CLICK']:'Click'); ?> <a href="specuploadmanagement.php?collid=<?php echo ($collid);?>&action=addprofile"><?php echo (isset($LANG['HERE'])?$LANG['HERE']:'here'); ?></a> <?php echo (isset($LANG['TO_ADD'])?$LANG['TO_ADD']:'to add a new profile'); ?>.
							</div>
							<?php
					 	}
					 	 ?>
					</fieldset>
				</form>
				<hr />
				<?php
			}
			else{
		 		?>
				<div style="clear:both;">
					<fieldset>
						<legend><b><?php echo (isset($LANG['UPLOAD_PARS'])?$LANG['UPLOAD_PARS']:'Upload Parameters'); ?></b></legend>
						<div style="float:right;">
							<?php
							echo '<a href="specuploadmanagement.php?collid='.$collid.'">View All</a> ';
							?>
						</div>
						<form name="parameterform" action="specuploadmanagement.php" method="post" onsubmit="return checkParameterForm(this)">
							<div id="updatetypeDiv" style="">
								<b><?php echo (isset($LANG['UP_TYPE'])?$LANG['UP_TYPE']:'Upload Type'); ?>:</b>
								<select name="uploadtype" onchange="adjustParameterForm()" <?php if($uspid) echo 'DISABLED'; ?>>
									<option value=""><?php echo (isset($LANG['SELECT_TYPE'])?$LANG['SELECT_TYPE']:'Select an Upload Type'); ?></option>
									<option value="">----------------------------------</option>
									<?php
									$uploadType = $duManager->getUploadType();
									echo '<option value="'.$DWCAUPLOAD.'" '.($uploadType==$DWCAUPLOAD?'SELECTED':'').'>'.(isset($LANG['MANUAL_DWCA'])?$LANG['MANUAL_DWCA']:'Darwin Core Archive Manual Upload').'</option>';
									echo '<option value="'.$IPTUPLOAD.'" '.($uploadType==$IPTUPLOAD?'SELECTED':'').'>'.(isset($LANG['IPT_DWCA'])?$LANG['IPT_DWCA']:'IPT Resource / Darwin Core Archive Provider').'</option>';
									echo '<option value="'.$SYMBIOTA.'" '.($uploadType==$SYMBIOTA?'SELECTED':'').'>'.(isset($LANG['SYMBIOTA_DWCA'])?$LANG['SYMBIOTA_DWCA']:'Symbiota Import').'</option>';
									echo '<option value="'.$FILEUPLOAD.'" '.($uploadType==$FILEUPLOAD?'SELECTED':'').'>'.(isset($LANG['FILE'])?$LANG['FILE']:'File Upload').'</option>';
									echo '<option value="'.$SKELETAL.'" '.($uploadType==$SKELETAL?'SELECTED':'').'>'.(isset($LANG['SKELETAL_FILE'])?$LANG['SKELETAL_FILE']:'Skeletal File Upload').'</option>';
									echo '<option value="'.$NFNUPLOAD.'" '.($uploadType==$NFNUPLOAD?'SELECTED':'').'>'.(isset($LANG['NFN_UPLOAD'])?$LANG['NFN_UPLOAD']:'NfN File Upload').'</option>';
									echo '<option value="">......................................</option>';
									echo '<option value="'.$DIRECTUPLOAD.'" '.($uploadType==$DIRECTUPLOAD?'SELECTED':'').'>'.(isset($LANG['DIRECT_DB'])?$LANG['DIRECT_DB']:'Direct Database Mapping').'</option>';
									echo '<option value="'.$STOREDPROCEDURE.'" '.($uploadType==$STOREDPROCEDURE?'SELECTED':'').'>'.(isset($LANG['STORED_PROC'])?$LANG['STORED_PROC']:'Stored Procedure').'</option>';
									echo '<option value="'.$SCRIPTUPLOAD.'" '.($uploadType==$SCRIPTUPLOAD?'SELECTED':'').'>'.(isset($LANG['SCRIPT_UP'])?$LANG['SCRIPT_UP']:'Script Upload').'</option>';
									?>
								</select>
							</div>
							<div id="titleDiv" style="">
								<b><?php echo (isset($LANG['TITLE'])?$LANG['TITLE']:'Title'); ?>:</b>
								<input name="title" type="text" value="<?php echo $duManager->getTitle(); ?>" style="width:400px;" maxlength="45" />
							</div>
							<div id="platformDiv" style="display:none">
								<b><?php echo (isset($LANG['DB_PLATFORM'])?$LANG['DB_PLATFORM']:'Database Platform'); ?>:</b>
								<select name="platform">
									<option value=""><?php echo (isset($LANG['NONE_SEL'])?$LANG['NONE_SEL']:'None Selected'); ?></option>
									<option value="">--------------------------------------------</option>
									<option value="mysql" <?php echo ($duManager->getPlatform()=='mysql'?'SELECTED':''); ?>><?php echo (isset($LANG['MYSQL'])?$LANG['MYSQL']:'MySQL Database'); ?></option>
								</select>
							</div>
							<div id="serverDiv" style="display:none">
								<b><?php echo (isset($LANG['SERVER'])?$LANG['SERVER']:'Server'); ?>:</b>
								<input name="server" type="text" size="50" value="<?php echo $duManager->getServer(); ?>" style="width:400px;" />
							</div>
							<div id="portDiv" style="display:none">
								<b><?php echo (isset($LANG['PORT'])?$LANG['PORT']:'Port'); ?>:</b>
								<input name="port" type="text" value="<?php echo $duManager->getPort(); ?>" />
							</div>
							<div id="pathDiv" style="display:none">
								<b><?php echo (isset($LANG['PATH'])?$LANG['PATH']:'Path'); ?>:</b>
								<input name="path" type="text" size="50" value="<?php echo $duManager->getPath(); ?>" style="width:700px;" />
							</div>
							<div id="codeDiv" style="display:none">
								<b><?php echo (isset($LANG['CODE'])?$LANG['CODE']:'Code'); ?>:</b>
								<input name="code" type="text" value="<?php echo $duManager->getCode(); ?>" />
							</div>
							<div id="pkfieldDiv" style="display:none">
								<b><?php echo (isset($LANG['PRIMARY_KEY'])?$LANG['PRIMARY_KEY']:'Primary Key Field'); ?>:</b>
								<input name="pkfield" type="text" value="<?php echo $duManager->getPKField(); ?>" />
							</div>
							<div id="usernameDiv" style="display:none">
								<b><?php echo (isset($LANG['USERNAME'])?$LANG['USERNAME']:'Username'); ?>:</b>
								<input name="username" type="text" value="<?php echo $duManager->getUsername(); ?>" />
							</div>
							<div id="passwordDiv" style="display:none">
								<b><?php echo (isset($LANG['PWORD'])?$LANG['PWORD']:'Password'); ?>:</b>
								<input name="password" type="text" value="<?php echo $duManager->getPassword(); ?>" />
							</div>
							<div id="schemanameDiv" style="display:none">
								<b><?php echo (isset($LANG['SCHEMA'])?$LANG['SCHEMA']:'Schema Name'); ?>:</b>
								<input name="schemaname" type="text" size="65" value="<?php echo $duManager->getSchemaName(); ?>" />
							</div>
							<div id="cleanupspDiv" style="display:none">
								<b><?php echo (isset($LANG['STORED_PROC'])?$LANG['STORED_PROC']:'Stored Procedure'); ?>:</b>
								<input name="cleanupsp" type="text" size="40" value="<?php echo $duManager->getStoredProcedure(); ?>" style="width:400px;" />
							</div>
							<div id="querystrDiv" style="display:none">
								<b><?php echo (isset($LANG['QUERY'])?$LANG['QUERY']:'Query/Command String'); ?>: </b><br/>
								<textarea name="querystr" cols="75" rows="6" ><?php echo $duManager->getQueryStr(); ?></textarea>
							</div>
							<div style="margin:15px">
								<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
								<input type="hidden" name="collid" value="<?php echo $collid;?>" />
								<?php
								if($uspid){
									?>
									<button type="submit" name="action" value="saveEdits"><?php echo $LANG['SAVE_PROFILE']; ?></button>
									<?php
								}
								else{
									?>
									<button type="submit" name="action" value="createProfile"><?php echo $LANG['CREATE_PROFILE']; ?></button>
									<?php
								}
								?>
							</div>
							<div id="dwca_notes" style="display: none">
								<?php echo '* '.(isset($LANG['PATH_EXPLAIN'])?$LANG['PATH_EXPLAIN']:'Path can be URL or local path leading to a DwC-Archive zip file or a directory path to a pre-extracted DwC-Archive data package.'); ?>
							</div>
						</form>
					</fieldset>
				</div>
				<?php
				if($uspid){
					?>
					<form action="specuploadmanagement.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this profile?')">
						<fieldset>
							<legend><b><?php echo (isset($LANG['DEL_PROFILE'])?$LANG['DEL_PROFILE']:'Delete this Profile'); ?></b></legend>
							<div>
								<input type="hidden" name="uspid" value="<?php echo $uspid; ?>" />
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<button type="submit" name="action" value="Delete Profile" ><?php echo (isset($LANG['DEL_PR'])?$LANG['DEL_PR']:'Delete Profile'); ?></button>
							</div>
						</fieldset>
					</form>
					<?php
				}
		 	}
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">ERROR: collection identifier not set</div>';
		}
	}
	else{
		?>
		<div style="font-weight:bold;font-size:120%;">
			<?php echo (isset($LANG['ERROR_AUTH'])?$LANG['ERROR_AUTH']:'ERROR: you are not authorized to upload to this collection'); ?>
		</div>
		<?php
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
