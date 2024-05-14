<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/InstitutionManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/colladdress.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/colladdress.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;

if(preg_match('/[^a-zA-Z\s]+/', $action)) $action = '';
if(!is_numeric($collid)) $collid = 0;

$isEditor = 0;
if($IS_ADMIN) $isEditor = 1;
elseif($collid){
	if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
		$isEditor = 1;
	}
}

$addressManager = new InstitutionManager();
$addressManager->setCollid($collid);

$statusStr = '';
if($isEditor){
	if($action == 'Link Address'){
		if(!$addressManager->linkAddress($_POST['iid'])){
			$statusStr = $addressManager->getErrorMessage();
		}
	}
	elseif(array_key_exists('removeiid',$_GET)){
		if(!$addressManager->removeAddress($_GET['removeiid'])){
			$statusStr = $addressManager->getErrorMessage();
		}
	}
}
$collData = current($collManager->getCollectionMetadata());
$collManager->cleanOutArr($collData);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE." " . ($collid?$collData["collectionname"]:"") . ' '. $LANG['MAILING_ADD']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script>

		$(function() {
			var dialogArr = new Array("instcode","collcode","pedits","pubagg","rights","rightsholder","accessrights","guid","colltype","management","icon","collectionid","sourceurl","sort");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "right", at: "center", of: "#"+dialogStr }
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

		});

		function openMappingAid() {
			mapWindow=open("../tools/mappointaid.php?errmode=0","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
			if (mapWindow.opener == null) mapWindow.opener = self;
		}

		function verifyCollEditForm(f){
			if(f.institutioncode.value == ''){
				alert("<?php echo (isset($LANG['NEED_INST_CODE'])?$LANG['NEED_INST_CODE']:'Institution Code must have a value'); ?>");
				return false;
			}
			else if(f.collectionname.value == ''){
				alert("<?php echo (isset($LANG['NEED_COLL_VALUE'])?$LANG['NEED_COLL_VALUE']:'Collection Name must have a value'); ?>");
				return false;
			}
			else if(f.managementtype.value == "Snapshot" && f.guidtarget.value == "symbiotaUUID"){
				alert("<?php echo (isset($LANG['CANNOT_GUID'])?$LANG['CANNOT_GUID']:'The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process.'); ?>");
				return false;
			}
			else if(!isNumeric(f.latitudedecimal.value) || !isNumeric(f.longitudedecimal.value)){
				alert("<?php echo (isset($LANG['NEED_DECIMAL'])?$LANG['NEED_DECIMAL']:'Latitude and longitude values must be in the decimal format (numeric only)'); ?>");
				return false;
			}
			else if(f.rights.value == ""){
				alert("<?php echo (isset($LANG['NEED_RIGHTS'])?$LANG['NEED_RIGHTS']:'Rights field (e.g. Creative Commons license) must have a selection'); ?>");
				return false;
			}
			try{
				if(!isNumeric(f.sortseq.value)){
					alert("<?php echo (isset($LANG['SORT_NUMERIC'])?$LANG['SORT_NUMERIC']:'Sort sequence must be numeric only'); ?>");
					return false;
				}
			}
			catch(ex){}
			return true;
		}

		function mtypeguidChanged(f){
			if(f.managementtype.value == "Snapshot" && f.guidtarget.value == "symbiotaUUID"){
				alert("<?php echo (isset($LANG['CANNOT_GUID'])?$LANG['CANNOT_GUID']:'The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process.'); ?>");
			}
			else if(f.managementtype.value == "Aggregate" && f.guidtarget.value != "" && f.guidtarget.value != "occurrenceId"){
				alert("<?php echo (isset($LANG['AGG_GUID'])?$LANG['AGG_GUID']:'An Aggregate dataset (e.g. specimens coming from multiple collections) can only have occurrenceID selected for the GUID source'); ?>");
				f.guidtarget.value = 'occurrenceId';
			}
			if(!f.guidtarget.value){
				f.publishToGbif.checked = false;
			}
		}

		function checkGUIDSource(f){
			if(f.publishToGbif.checked == true){
				if(!f.guidtarget.value){
					alert("<?php echo (isset($LANG['NEED_GUID'])?$LANG['NEED_GUID']:'You must select a GUID source in order to publish to data aggregators.'); ?>");
					f.publishToGbif.checked = false;
				}
			}
		}

		function verifyAddAddressForm(f){
			if(f.iid.value == ""){
				alert("<?php echo (isset($LANG['SEL_INST'])?$LANG['SEL_INST']:'Select an institution to be linked'); ?>");
				return false;
			}
			return true;
		}

		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
				var divs = document.getElementsByTagName("div");
				for (var h = 0; h < divs.length; h++) {
				var divObj = divs[h];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
						else {
							divObj.style.display="none";
						}
					}
				}
			}
		}

		function verifyIconImage(f){
			var iconImageFile = document.getElementById("iconfile").value;
			if(iconImageFile){
				var iconExt = iconImageFile.substr(iconImageFile.length-4);
				iconExt = iconExt.toLowerCase();
				if((iconExt != '.jpg') && (iconExt != 'jpeg') && (iconExt != '.png') && (iconExt != '.gif')){
					document.getElementById("iconfile").value = '';
					alert("<?php echo (isset($LANG['NOT_SUPP'])?$LANG['NOT_SUPP']:'The file you have uploaded is not a supported image file. Please upload a jpg, png, or gif file.'); ?>");
				}
				else{
					var fr = new FileReader;
					fr.onload = function(){
						var img = new Image;
						img.onload = function(){
							if((img.width>350) || (img.height>350)){
								document.getElementById("iconfile").value = '';
								img = '';
								alert("<?php echo (isset($LANG['MUST_SMALL'])?$LANG['MUST_SMALL']:'The image file must be less than 350 pixels in both width and height.'); ?>");
							}
						};
						img.src = fr.result;
					};
					fr.readAsDataURL(document.getElementById("iconfile").files[0]);
				}
			}
		}

		function verifyIconURL(f){
			var iconImageFile = document.getElementById("iconurl").value;
			if(iconImageFile && (iconImageFile.substr(iconImageFile.length-4) != '.jpg') && (iconImageFile.substr(iconImageFile.length-4) != '.png') && (iconImageFile.substr(iconImageFile.length-4) != '.gif')){
				alert("<?php echo (isset($LANG['NOT_SUPP_URL'])?$LANG['NOT_SUPP_URL']:'The url you have entered is not for a supported image file. Please enter a url for a jpg, png, or gif file.'); ?>");
			}
		}

		function isNumeric(sText){
		   	var ValidChars = "0123456789-.";
		   	var IsNumber = true;
		   	var Char;

		   	for(var i = 0; i < sText.length && IsNumber == true; i++){
			   Char = sText.charAt(i);
				if(ValidChars.indexOf(Char) == -1){
					IsNumber = false;
					break;
			  	}
		   	}
			return IsNumber;
		}
	</script>
	<style type="text/css">
	 	fieldset { background-color: #f2f2f2 }
	 	legend { font-weight: bold }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="../../index.php">Home</a> &gt;&gt; ';
	if($collid){
		echo '<a href="collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars((isset($LANG['COL_MGMNT'])?$LANG['COL_MGMNT']:'Collection Management'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		echo '<b>'.$collData['collectionname'].' '.(isset($LANG['MAILING_ADD'])?$LANG['MAILING_ADD']:'Mailing Address').' </b>';
	}
	else echo '<b>'.(isset($LANG['MAILING_ADDS'])?$LANG['MAILING_ADDS']:'Mailing Addresses').'</b>';
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo ($collid?$collData["collectionname"]:"") . ' '. $LANG['MAILING_ADD']; ?></h1>
		<?php
		if($statusStr){
			?>
			<hr />
			<div style="margin:20px;font-weight:bold;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<hr />
			<?php
		}
		if($isEditor){
			if($collid) echo '<h1>'.$collData['collectionname'].(array_key_exists('institutioncode',$collData)?' ('.$collData['institutioncode'].')':'').'</h1>';
			?>
			<div>
				<fieldset>
					<legend><?php echo (isset($LANG['MAILING_ADD'])?$LANG['MAILING_ADD']:'Mailing Address'); ?></legend>
					<?php
					if($instArr = $addressManager->getAddress()){
						?>
						<div style="margin:25px;">
							<?php
							echo '<div>';
							echo $instArr['institutionname'].($instArr['institutioncode']?' ('.$instArr['institutioncode'].')':'');
							?>
							<a href="institutioneditor.php?emode=1&targetcollid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&iid=' . htmlspecialchars($instArr['iid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="<?php echo htmlspecialchars((isset($LANG['EDIT_ADD'])?$LANG['EDIT_ADD']:'Edit Institution Address'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
								<img src="../../images/edit.png" style="width:1.3em" />
							</a>
							<a href="collmetadata.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&removeiid=' . htmlspecialchars($instArr['iid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="<?php echo htmlspecialchars((isset($LANG['UNLINK_ADD'])?$LANG['UNLINK_ADD']:'Unlink institution address'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
								<img src="../../images/drop.png" style="width:1.3em" />
							</a>
							<?php
							echo '</div>';
							if($instArr['address1']) echo '<div>'.$instArr['address1'].'</div>';
							if($instArr['address2']) echo '<div>'.$instArr['address2'].'</div>';
							if($instArr['city'] || $instArr['stateprovince']) echo '<div>'.$instArr['city'].', '.$instArr['stateprovince'].' '.$instArr['postalcode'].'</div>';
							if($instArr['country']) echo '<div>'.$instArr['country'].'</div>';
							if($instArr['phone']) echo '<div>'.$instArr['phone'].'</div>';
							if($instArr['contact']) echo '<div>'.$instArr['contact'].'</div>';
							if($instArr['email']) echo '<div>'.$instArr['email'].'</div>';
							if($instArr['url']) echo '<div><a href="' . htmlspecialchars($instArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($instArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
							if($instArr['notes']) echo '<div>'.$instArr['notes'].'</div>';
							?>
						</div>
						<?php
					}
					else{
						//Link new institution
						?>
						<div style="margin:40px;"><b><?php echo (isset($LANG['NO_ADDS'])?$LANG['NO_ADDS']:'No addesses linked'); ?></b></div>
						<div style="margin:20px;">
							<form name="addaddressform" action="collmetadata.php" method="post" onsubmit="return verifyAddAddressForm(this)">
								<select name="iid" style="width:425px;">
									<option value=""><?php echo (isset($LANG['SEL_ADD'])?$LANG['SEL_ADD']:'Select Institution Address'); ?></option>
									<option value="">------------------------------------</option>
									<?php
									$addrArr = $addressManager->getInstitutionArr();
									foreach($addrArr as $iid => $name){
										echo '<option value="'.$iid.'">'.$name.'</option>';
									}
									?>
								</select>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="action" type="submit" value="Link Address" />
							</form>
							<div style="margin:15px;">
								<a href="institutioneditor.php?emode=1&targetcollid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="Add a new address not on the list">
									<b><?php echo (isset($LANG['ADD_INST'])?$LANG['ADD_INST']:'Add an institution not on list'); ?></b>
								</a>
							</div>
						</div>
						<?php
					}
					?>
				</fieldset>
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