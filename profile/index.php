<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
include_once($SERVER_ROOT.'/content/lang/profile/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$login = array_key_exists('login',$_REQUEST)?$_REQUEST['login']:'';
$remMe = array_key_exists("remember",$_POST)?$_POST["remember"]:'';
$emailAddr = array_key_exists('email',$_POST)?$_POST['email']:'';
$resetPwd = ((array_key_exists("resetpwd",$_REQUEST) && is_numeric($_REQUEST["resetpwd"]))?$_REQUEST["resetpwd"]:0);
$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
if(!$action && array_key_exists('submit',$_REQUEST)) $action = $_REQUEST['submit'];

$refUrl = '';
if(array_key_exists('refurl',$_REQUEST)){
	$refGetStr = '';
	foreach($_GET as $k => $v){
		$k = filter_var($k, FILTER_SANITIZE_STRING);
		if($k != 'refurl'){
			if($k == 'attr' && is_array($v)){
				foreach($v as $v2){
					$v2 = filter_var($v2, FILTER_SANITIZE_STRING);
					$refGetStr .= '&attr[]='.$v2;
				}
			}
			else{
				$v = filter_var($v, FILTER_SANITIZE_STRING);
				$refGetStr .= '&'.$k.'='.$v;
			}
		}
	}
	$refUrl = str_replace('&amp;','&',htmlspecialchars(filter_var($_REQUEST['refurl'], FILTER_SANITIZE_STRING)));
	if(substr($refUrl,-4) == '.php') $refUrl .= '?'.substr($refGetStr,1);
	else $refUrl .= $refGetStr;
}

$pHandler = new ProfileManager();

$statusStr = '';
//Sanitation
if($login){
	if(!$pHandler->setUserName($login)){
		$login = '';
		$statusStr = (isset($LANG['INVALID_LOGIN'])?$LANG['INVALID_LOGIN']:'Invalid login name').'<ERR/>';
	}
}
if($emailAddr){
	if(!$pHandler->validateEmailAddress($emailAddr)){
		$emailAddr = '';
		$statusStr = (isset($LANG['INVALID_EMAIL'])?$LANG['INVALID_EMAIL']:'Invalid email').'<ERR/>';
	}
}
if(!is_numeric($resetPwd)) $resetPwd = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

if($remMe) $pHandler->setRememberMe(true);
if($action == 'logout'){
	$pHandler->reset();
	header('Location: ../index.php');
}
elseif($action == 'login'){
	if($pHandler->authenticate($_POST['password'])){
		if(!$refUrl || (strtolower(substr($refUrl,0,4)) == 'http') || strpos($refUrl,'newprofile.php')){
			header('Location: ../index.php');
		}
		else{
			header('Location: '.$refUrl);
		}
	}
	else{
		if($pHandler->getErrorMessage()){
			$statusStr = $pHandler->getErrorMessage();
		}
		else{
			if(isset($LANG['INCORRECT'])) $statusStr = $LANG['INCORRECT'];
			else $statusStr = 'Your username or password was incorrect. Please try again.<br/> If you are unable to remember your login credentials, use the controls below to retrieve your login or reset your password.';
			$statusStr .= '<ERR/>';
			error_log('Authorization of user <F-USER>' . $login . '</F-USER> to access ' . $_SERVER['PHP_SELF']. ' failed', 0);
		}
	}
}
elseif($action == 'Retrieve Login'){
	if($emailAddr){
		if($pHandler->lookupUserName($emailAddr)){
			if(isset($LANG['LOGIN_EMAILED'])) $statusStr = $LANG['LOGIN_EMAILED'];
			else $statusStr = 'Your login name will be emailed to';
			$statusStr .= ': '.$emailAddr;
		}
		else{
			$statusStr = (isset($LANG['EMAIL_ERROR'])?$LANG['EMAIL_ERROR']:'Error sending email, contact administrator').' ('.$pHandler->getErrorMessage().')<ERR/>';
		}
	}
}
elseif($resetPwd){
	if($email = $pHandler->resetPassword($login)){
		$statusStr = (isset($LANG['PWD_EMAILED'])?$LANG['PWD_EMAILED']:'Your new password was just emailed to').': '.$email.'<ERR/>';
	}
	else{
		$statusStr = (isset($LANG['RESET_FAILED'])?$LANG['RESET_FAILED']:'Reset Failed! Contact Administrator').'<ERR/>';
		if($pHandler->getErrorMessage()) $statusStr .= ' ('.$pHandler->getErrorMessage().')';
	}
}
else{
	$statusStr = $pHandler->getErrorMessage();
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['LOGIN_NAME'])?$LANG['LOGIN_NAME']:'Login'); ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		if(!navigator.cookieEnabled){
			<?php
			$alertStr = 'Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.';
			if(isset($LANG['COOKIES'])) $alertStr = $LANG['COOKIES'];
			?>
			alert("<?php echo $alertStr; ?>");
		}

		function resetPassword(){
			if(document.getElementById("login").value == ""){
				<?php
				$alertStr = 'Enter your login name in the Login field and leave the password blank';
				if(isset($LANG['ENTER_LOGIN_NO_PWD'])) $alertStr = $LANG['ENTER_LOGIN_NO_PWD'];
				?>
				alert("<?php echo $alertStr; ?>");
				return false;
			}
			document.getElementById("resetpwd").value = "1";
			document.forms["loginform"].submit();
		}

		function checkCreds(){
			if(document.getElementById("login").value == "" || document.getElementById("password").value == ""){
				<?php
				$alertStr = 'Please enter your login and password';
				if(isset($LANG['ENTER_LOGIN'])) $alertStr = $LANG['ENTER_LOGIN'];
				?>
				alert("<?php echo $alertStr; ?>");
				return false;
			}
			return true;
		}
	</script>
	<script src="../js/symb/shared.js" type="text/javascript"></script>
	<style type="text/css">
		fieldset { padding:20px; background-color:#F9F9F9; border:2px outset #808080; }
		legend { font-weight: bold; }
	</style>
</head>
<body>
<?php
$displayLeftMenu = (isset($profile_indexMenu)?$profile_indexMenu:'true');
include($SERVER_ROOT.'/includes/header.php');
?>
<!-- inner text -->
<div id="innertext" style="padding-left:0px;margin-left:0px;">
	<?php
	if($statusStr){
		$color = 'green';
		if(strpos($statusStr, '<ERR/>')) $color = 'red';
		?>
		<div style='color:<?php echo $color; ?>;margin: 1em 1em 0em 1em;'>
			<?php
			echo $statusStr;
			?>
		</div>
		<?php
	}
	?>
	<div style="width:300px;margin-right:auto;margin-left:auto;">
		<fieldset style="margin:20px;width:350px;">
			<legend><?php echo (isset($LANG['PORTAL_LOGIN'])?$LANG['PORTAL_LOGIN']:'Portal Login'); ?></legend>
			<form id="loginform" name="loginform" action="index.php" onsubmit="return checkCreds();" method="post">
				<div style="margin: 10px;">
					<?php echo (isset($LANG['LOGIN_NAME'])?$LANG['LOGIN_NAME']:'Login'); ?>: <input id="login" name="login" value="<?php echo $login; ?>" style="border-style:inset;" />
				</div>
				<div style="margin:10px;">
					<?php echo (isset($LANG['PASSWORD'])?$LANG['PASSWORD']:"Password"); ?>:
					<input type="password" id="password" name="password"  style="border-style:inset;" autocomplete="off" />
				</div>
				<div style="margin:10px">
					<input type="checkbox" value='1' name="remember" checked >
					<?php echo (isset($LANG['REMEMBER'])?$LANG['REMEMBER']:'Remember me on this computer'); ?>
				</div>
				<div style="margin:15px;">
					<input type="hidden" name="refurl" value="<?php echo $refUrl; ?>" />
					<input type="hidden" id="resetpwd" name="resetpwd" value="">
					<button name="action" type="submit" value="login"><?php echo (isset($LANG['SIGNIN'])?$LANG['SIGNIN']:'Sign In'); ?></button>
				</div>
			</form>
		</fieldset>
		<div style="width:300px;text-align:center;margin:20px;">
			<div style="font-weight:bold;">
				<?php echo (isset($LANG['NO_ACCOUNT'])?$LANG['NO_ACCOUNT']:"Don't have an Account?"); ?>
			</div>
			<div style="">
				<a href="newprofile.php?refurl=<?php echo $refUrl; ?>"><?php echo (isset($LANG['CREATE_ACCOUNT'])?$LANG['CREATE_ACCOUNT']:'Create an account'); ?></a>
			</div>
			<div style="font-weight:bold;margin-top:5px">
				<?php echo (isset($LANG['REMEMBER_PWD'])?$LANG['REMEMBER_PWD']:"Can't Remember your password?"); ?>
			</div>
			<div style="color:blue;cursor:pointer;" onclick="resetPassword();"><?php echo (isset($LANG['REST_PWD'])?$LANG['REST_PWD']:'Reset Password'); ?></div>
			<div style="font-weight:bold;margin-top:5px">
				<?php echo (isset($LANG['REMEMBER_LOGIN'])?$LANG['REMEMBER_LOGIN']:"Can't Remember Login Name?"); ?>
			</div>
			<div>
				<div><a href="#" onclick="toggle('emaildiv');"><?php echo (isset($LANG['RETRIEVE'])?$LANG['RETRIEVE']:'Retrieve Login'); ?></a></div>
				<div id="emaildiv" style="display:none;margin:10px 0px 10px 40px;">
					<fieldset>
						<form id="retrieveloginform" name="retrieveloginform" action="index.php" method="post">
							<div><?php echo (isset($LANG['YOUR_EMAIL'])?$LANG['YOUR_EMAIL']:'Your Email'); ?>: <input type="text" name="email" /></div>
							<div><button name="action" type="submit" value="Retrieve Login"><?php echo (isset($LANG['RETRIEVE'])?$LANG['RETRIEVE']:'Retrieve Login'); ?></button></div>
						</form>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include($SERVER_ROOT.'/includes/footer.php'); ?>
</body>
</html>