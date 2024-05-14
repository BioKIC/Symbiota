<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSesar.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/admin/igsnmapper.'.$LANG_TAG.'.php'))
	include_once($SERVER_ROOT.'/content/lang/collections/admin/igsnmapper.'.$LANG_TAG.'.php');
else
	include_once($SERVER_ROOT.'/content/lang/collections/admin/igsnmapper.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/igsnmapper.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$username = array_key_exists('username',$_REQUEST)?$_REQUEST['username']:'';
$pwd = array_key_exists('pwd',$_REQUEST)?$_REQUEST['pwd']:'';
$registrationMethod = array_key_exists('registrationMethod',$_REQUEST)?$_REQUEST['registrationMethod']:'';
$igsnSeed = array_key_exists('igsnSeed',$_REQUEST)?$_REQUEST['igsnSeed']:'';
$processingCount = array_key_exists('processingCount',$_REQUEST)?$_REQUEST['processingCount']:10;
$action = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Variable sanitation
if(!is_numeric($collid)) $collid = 0;
if(!in_array($registrationMethod,array('api','csv','xml'))) $registrationMethod = '';
if(preg_match('/[^A-Z0-9]+/', $igsnSeed)) $igsnSeed = '';
if($processingCount && !is_numeric($processingCount)) $processingCount = 10;

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
$guidManager->setRegistrationMethod($registrationMethod);

$sesarProfile = $guidManager->getSesarProfile();
$namespace = '';
if(isset($sesarProfile['namespace'])) $namespace = $sesarProfile['namespace'];
$generationMethod = '';
if(isset($sesarProfile['generationMethod'])) $generationMethod = $sesarProfile['generationMethod'];

if($igsnSeed) $guidManager->setIgsnSeed($igsnSeed);
elseif($generationMethod == 'inhouse') $igsnSeed = $guidManager->getIgsnSeed();

if($action == 'populateGUIDs'){
	if($registrationMethod == 'xml'){
		$guidManager->setVerboseMode(0);
		if($guidManager->batchProcessIdentifiers($processingCount)){
			exit;
		}
		else{
			$statusStr = '<div><span style="color:red">Error Message:</span> '.$guidManager->getErrorMessage().'</div>';
			if($warningArr = $guidManager->getWarningArr()){
				foreach($warningArr as $errMsg){
					$statusStr .= '<div style="margin-left:10px">'.$errMsg.'</div>';
				}
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['IGSN_GUID_MAPPER'] ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function validateCredentials(f){
			if(f.username.value == "" || f.pwd.value == ""){
				alert("<?php echo $LANG['ENTER_VALID'] ?>");
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
					//$(xml).find('user_codes').each(function(){
						//$(this).find("user_code").each(function(){
							//var userCode = $(this).text();
						//});
					//});
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

		function generationMethodChanged(elem){
			if(elem.value == "sesar"){
				$("#igsnseed-div").hide();
			}
			else{
				generateIgsnSeed();
			}
		}

		function generateIgsnSeed(){
			var f = document.guidform;
			$("#igsnseed-div").show();
			$.ajax({
				method: "POST",
				data: { collid: f.collid.value },
				dataType: "text",
				url: "rpc/getigsnseed.php"
			})
			.done(function(responseStr) {
				f.igsnSeed.value = responseStr;
			});
		}

		function verifyGuidForm(f){
			if(f.registrationMethod.value == ""){
				alert("<?php echo $LANG['SELECT_REG'] ?>");
				return false;
			}
			<?php
			if($generationMethod == 'inhouse'){
				?>
				else if(f.igsnSeed.value == ""){
					alert("<?php echo $LANG['IGSN_NOT_GENERATED'] ?>");
					return false;
				}
				<?php
			}
			?>
			setTimeout(function(){
				//f.igsnSeed.value = "";
			}, 100);
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ margin:10px; padding:15px; }
		fieldset legend{ font-weight:bold; }
		.form-label{ font-weight: bold; }
		button{ margin:15px; }
	</style>
</head>
<body>
	<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href="../../index.php"><?php echo $LANG['HOME'] ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COLL_MANAGE'] ?></a> &gt;&gt;
	<a href="igsnmanagement.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['IGSN_GUID_MANAGE'] ?></a> &gt;&gt;
	<b><?php echo $LANG['IGSN_MAPPER'] ?></b>
</div>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['IGSN_GUID_MAPPER']; ?></h1>
	<?php
	if($isEditor && $collid){
		echo '<h3>'.$guidManager->getCollectionName().'</h3>';
		if($statusStr){
			?>
			<fieldset style="margin:10px;">
				<legend><?php echo $LANG['ERROR_PANEL'] ?></legend>
				<?php echo $statusStr; ?>
			</fieldset>
			<?php
		}
		if(!$guidManager->getProductionMode()){
			echo '<h2 style="color:orange">' . "-- " . $LANG['DEV_MODE'] . ' --</h2>';
		}
		if($namespace && $generationMethod){
			if($action == 'populateGUIDs'){
				if($registrationMethod == 'api'){
					echo '<fieldset>';
					echo '<legend>' . $LANG['ACTION_PANEL'] . '</legend>';
					echo '<ul>';
					$guidManager->batchProcessIdentifiers($processingCount);
					echo '<ul>';
					echo '</fieldset>';
				}
			}
			?>
			<form id="guidform" name="guidform" action="igsnmapper.php" method="post" onsubmit="return verifyGuidForm(this)">
				<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
				<fieldset>
					<legend><?php echo $LANG['IGSN_REG_PANEL'] ?></legend>
					<p><?php echo $LANG['REG_IGSN'] . " "?><a href="http://www.geosamples.org/interop" target="_blank"><?php echo $LANG['SESAR_API_WEB'] ?></a></p>
					<p>
						<div>
							<span class="form-label"><?php echo $LANG['USERNAME'] ?></span> <input name="username" type="text" value="<?php echo $username; ?>" />
							<span id="valid-span" style="display:none;color:green"><?php echo $LANG['CRED_VALID'] ?></span>
							<span id="notvalid-span" style="display:none;color:orange"><?php echo $LANG['CRED_NOT_VALID'] ?></span>
						</div>
						<div><span class="form-label"><?php echo $LANG['PASSWORD'] ?></span> <input name="pwd" type="password" value="<?php echo $pwd; ?>" /></div>
						<button id="validate-button" type="button" onclick="validateCredentials(this.form)"><?php echo $LANG['VALIDATE_CRED'] ?></button>
					</p>
					<div style="margin:10px 0px"><hr/></div>
					<div style="margin:10px 0px">
						<p><b><?php echo $LANG['OCC_WITHOUT_GUID'] ?></b> <?php echo $guidManager->getMissingGuidCount(); ?></p>
					</div>
					<div id="igsn-reg-div" style="margin-top:20px;display:none;">
						<p>
							<span class="form-label"><?php echo $LANG['IGSN_NAMESPACE'] ?></span>
							<?php echo $namespace; ?>
						</p>
						<p>
							<span class="form-label"><?php echo $LANG['IGSN_GEN_METHOD'] ?></span>
							<?php echo $generationMethod; ?>
						</p>
						<div id="igsnseed-div" style="display:<?php echo ($generationMethod=='inhouse'?'':'none'); ?>">
							<p>
								<span class="form-label"><?php echo $LANG['IGSN_SEED'] ?></span>
								<input name="igsnSeed" type="text" value="<?php echo $igsnSeed; ?>" />
								<span style=""><a href="#" onclick="generateIgsnSeed();return false;"><img src="../../images/refresh.png" style="width:1.4em;vertical-align: middle;" /></a></span>
							</p>
						</div>
						<p>
							<span class="form-label"><?php echo $LANG['REG_METHOD'] ?></span>
							<select name="registrationMethod">
								<option value=''><?php echo "-- " . $LANG['SELECT_METHOD'] . " --" ?></option>
								<option value=''>----------------------------</option>
								<option value='api' <?php echo ($registrationMethod=='api'?'SELECTED':''); ?>><?php echo $LANG['SESAR_API'] ?></option>
								<!--  <option value='csv' <?php echo ($registrationMethod=='csv'?'SELECTED':''); ?>>Export CSV</option>  -->
								<option value='xml' <?php echo ($registrationMethod=='xml'?'SELECTED':''); ?>><?php echo $LANG['EXPORT_XML'] ?></option>
							</select>
						</p>
						<p>
							<span class="form-label"><?php echo $LANG['NUM_GEN'] . ': ' ?></span>
							<input name="processingCount" type="text" value="10" /><?php echo $LANG['LEAVE_BLANK'] ?>
						</p>
						<p>
							<button name="formsubmit" type="submit" value="populateGUIDs" <?php echo ($namespace && $generationMethod?'':'disabled'); ?>><?php echo $LANG['POPULATE_COLL_GUID'] ?></button>
						</p>
					</div>
				</fieldset>
			</form>
			<?php
		}
		else{
			echo '<h2><span style="color:red">' .  $LANG['FATAL_ERROR'] . '</span> ' . $LANG['NAMESPACE_NOT_SET'] . '</h2>';
		}
	}
	else{
		echo '<h2>' . $LANG['NOT_AUTH'] . '</h2>';
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>