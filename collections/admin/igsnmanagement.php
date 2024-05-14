<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSesar.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/admin/igsnmanagement.'.$LANG_TAG.'.php'))
	include_once($SERVER_ROOT.'/content/lang/collections/admin/igsnmanagement.'.$LANG_TAG.'.php');
else
	include_once($SERVER_ROOT.'/content/lang/collections/admin/igsnmanagement.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/igsnmanagement.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$username = array_key_exists('username',$_REQUEST)?$_REQUEST['username']:'';
$pwd = array_key_exists('pwd',$_REQUEST)?$_REQUEST['pwd']:'';
$namespace = array_key_exists('namespace',$_REQUEST)?$_REQUEST['namespace']:'';
$generationMethod = array_key_exists('generationMethod',$_REQUEST)?$_REQUEST['generationMethod']:'';
$action = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Variable sanitation
if(!is_numeric($collid)) $collid = 0;
if(preg_match('/[^A-Z]+/', $namespace)) $namespace = '';
if(!in_array($generationMethod,array('inhouse','sesar'))) $generationMethod = '';

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
$guidManager = new OccurrenceSesar();
$guidManager->setCollid($collid);
$guidManager->setCollArr();
$guidManager->setSesarUser($username);
$guidManager->setSesarPwd($pwd);
$guidManager->setNamespace($namespace);
$guidManager->setGenerationMethod($generationMethod);

if($action){
	if($action == 'saveProfile'){
		$guidManager->saveProfile();
	}
	elseif($action == 'deleteProfile'){
		$guidManager->deleteProfile();
		$namespace = '';
		$generationMethod = '';
	}
}

$sesarProfile = $guidManager->getSesarProfile();
if(isset($sesarProfile['namespace'])) $namespace = $sesarProfile['namespace'];
if(isset($sesarProfile['generationMethod'])) $generationMethod = $sesarProfile['generationMethod'];
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['IGSN_GUID'] ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script type="text/javascript">

		function validateCredentials(f){
			if(f.username.value == "" || f.pwd.value == ""){
				alert("<?php echo $LANG['INVALID_SESAR'] ?>");
				return false;
			}
			else if(f.username.value.indexOf("@") == -1){
				alert("<?php echo $LANG['MUST_BE_EMAIL'] ?>");
				return false;
			}
			$.ajax({
				method: "POST",
				data: { username: f.username.value, password: f.pwd.value },
				dataType: "xml",
				url: "https://app.geosamples.org/webservices/credentials_service_v2.php"
			})
			.done(function(xml) {
				var valid = $(xml).find('valid').text();
				if(valid == "yes"){
					$(xml).find('user_codes').each(function(){
	                    $(this).find("user_code").each(function(){
	                        var userCode = $(this).text();
	                        $('#nsSelect').append(new Option(userCode, userCode));
	                    });
	                });
                    $("#igsn-reg-div").show();
                    $("#validate-button").hide();
                    $("#valid-span").show();
                    $("#notvalid-span").hide();
				}
				else{
					alert($(xml).find('error').text());
	                $("#igsn-reg-div").hide();
	                $("#validate-button").show();
	                $("#valid-span").hide();
	                $("#notvalid-span").show();
				}
			})
			.fail(function() {
				alert("Validation call failed");
	            $("#igsn-reg-div").hide();
	            $("#validate-button").show();
	            $("#valid-span").hide();
	            $("#notvalid-span").show();
			});
		}

		function verifyProfileForm(f){
			if(f.namespace.value == ""){
				alert("Select a namespace");
				return false;
			}
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ margin:10px; padding:15px; }
		fieldset legend{ font-weight:bold; }
		.form-label{  }
		button{ margin:15px; }
	</style>
</head>
<body>
	<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href="../../index.php"> <?php echo $LANG['HOME'] ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"> <?php echo $LANG['COLL_MANAGE'] ?></a> &gt;&gt;
	<a href="igsnmapper.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"> <?php echo $LANG['IGSN_GUID_GEN'] ?></a> &gt;&gt;
	<b><?php echo $LANG['IGSN_MANAGE'] ?></b>
</div>
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['IGSN_MANAGE']; ?></h1>
	<?php
	if($isEditor && $collid){
		echo '<h3>' . $LANG['IGSN_MANAGE'] . ': '.$guidManager->getCollectionName().'</h3>';
		if($statusStr){
			?>
			<fieldset>
				<legend><?php echo $LANG['ERROR_PANEL'] ?></legend>
				<?php echo $statusStr; ?>
			</fieldset>
			<?php
		}
		if(!$guidManager->getProductionMode()){
			echo '<h2 style="color:orange">-- ' . $LANG['DEV_MODE'] . ' --</h2>';
		}
		if($namespace){
			$guidCnt = $guidManager->getGuidCount($collid);
			$guidMissingCnt = $guidManager->getMissingGuidCount();
			$guidAllCollCnt = $guidManager->getGuidCount();
			?>
			<fieldset>
				<legend><?php echo $LANG['IGSN_PROFILE'] ?></legend>
				<p><span class="form-label"> <?php echo $LANG['IGSN_NAMESPACE'] ?> </span> <?php echo $namespace; ?></p>
				<p><span class="form-label"> <?php echo $LANG['IGSN_GEN_METHOD'] ?> </span> <?php echo $generationMethod; ?></p>
				<p><span class="form-label"> <?php echo $LANG['IGSN_WITHIN_COLL'] ?> </span> <?php echo $guidCnt; ?></p>
				<p><span class="form-label"> <?php echo $LANG['OCC_WITHOUT_GUID'] ?> </span> <?php echo $guidMissingCnt; ?></p>
				<?php
				if($guidAllCollCnt > $guidCnt){
					?>
					<p><span class="form-label"><?php echo $LANG['GUID_USING_ABOVE'] ?></span> <?php echo $guidAllCollCnt; ?></p>
					<?php
				}
				?>
				<div style="margin:10px;">
					<form name="deleteform" action="igsnmanagement.php" method="post">
						<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
						<input type="hidden" name="namespace" value="<?php echo $namespace; ?>" />
						<span style="margin-left:10px;">
							<button class="button-danger" name="formsubmit" type="submit" value="deleteProfile" onclick="return confirm('<?php echo $LANG['DEL_CONFIRM'] ?>')"><?php echo $LANG['DEL_PROFILE'] ?></button>
						</span>
						<span style="margin-left:10px;">
							<a href="igsnmapper.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><button type="button"><?php echo $LANG['GO_TO_MAPPER'] ?></button></a>
						</span>
					</form>
				</div>
				<div style="margin:10px;">
					<form name="verifyigsnform" action="igsnverification.php" method="post">
						<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
						<input type="hidden" name="namespace" value="<?php echo $namespace; ?>" />
						<span style="margin-left:10px;">
							<button name="formsubmit" type="submit" value="verifysesar"><?php echo $LANG['VERIFY_SESAR'] ?></button>
						</span>
					</form>
				</div>
			</fieldset>
			<?php
		}
		else{
			?>
			<form name="profileform" action="igsnmanagement.php" method="post" onsubmit="return verifyProfileForm(this)">
				<fieldset>
					<legend><?php echo $LANG['IGSN_REG_PROFILE'] ?></legend>
					<p>
						<div>
							<span class="form-label"><?php echo $LANG['USERNAME'] ?></span> <input name="username" type="text" value="<?php echo $username; ?>" />
							<span id="valid-span" style="display:none;color:green"><?php echo $LANG['CRED_VALID'] ?></span>
							<span id="notvalid-span" style="display:none;color:orange"><?php echo $LANG['CRED_NOT_VALID'] ?></span>
						</div>
						<div><span class="form-label"><?php echo $LANG['PASSWORD'] ?></span> <input name="pwd" type="password" value="<?php echo $pwd; ?>" /></div>
						<button id="validate-button" type="button" onclick="validateCredentials(this.form)"><?php echo $LANG['VALIDATE_CRED'] ?></button>
					</p>
					<div id="igsn-reg-div" style="margin-top:20px;display:none">
						<p>
							<span class="form-label"><?php echo $LANG['IGSN_NAMESPACE'] ?></span>
							<select id="nsSelect" name="namespace">
								<option value=""><?php echo  " -- " . $LANG['SELECT_IGSN'] .  " --"?></option>
								<option value="">------------------------------</option>
							</select>
						</p>
						<p>
							<span class="form-label"><?php echo $LANG['IGSN_GEN_METHOD'] ?></span>
							<select name="generationMethod">
								<option value='sesar'><?php echo $LANG['SESAR_GEN_IGSN'] ?></option>
								<option value='inhouse'><?php echo $LANG['GEN_IGSN'] ?></option>
							</select>
						</p>
						<p>
							<button name="formsubmit" type="submit" value="saveProfile"><?php echo $LANG['SAVE_PROFILE'] ?></button>
							<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
						</p>
					</div>
				</fieldset>
			</form>
			<?php
		}
	}
	else echo '<h2>' . $LANG['NOT_AUTH'] . ' </h2>';
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
