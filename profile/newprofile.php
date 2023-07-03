<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
@include_once($SERVER_ROOT.'/content/lang/profile/newprofile.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
header('Cache-Control: no-cache, no-cache="set-cookie", no-store, must-revalidate');
header('Pragma: no-cache'); // HTTP 1.0.
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$login = array_key_exists('login',$_POST)?$_POST['login']:'';
$emailAddr = array_key_exists('email',$_POST)?$_POST['email']:'';
$action = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:'';

$pHandler = new ProfileManager();
$displayStr = '';

//Sanitation
if($login){
	if(!$pHandler->setUserName($login)){
		$login = '';
		$displayStr = (isset($LANG['INVALID_USERNAME'])?$LANG['INVALID_USERNAME']:'Invalid username');
	}
}
if($emailAddr){
	if(!$pHandler->validateEmailAddress($emailAddr)){
		$emailAddr = '';
		$displayStr = (isset($LANG['INVALID_EMAIL'])?$LANG['INVALID_EMAIL']:'Invalid email address');
	}
}
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$useRecaptcha = false;
if(isset($RECAPTCHA_PUBLIC_KEY) && $RECAPTCHA_PUBLIC_KEY && isset($RECAPTCHA_PRIVATE_KEY) && $RECAPTCHA_PRIVATE_KEY){
	$useRecaptcha = true;
}

if($action == "Create Login"){
	$okToCreateLogin = true;
	if($useRecaptcha){
		$captcha = urlencode($_POST['g-recaptcha-response']);
		if($captcha){
			//Verify with Google
			$response = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$RECAPTCHA_PRIVATE_KEY.'&response='.$captcha.'&remoteip='.$_SERVER['REMOTE_ADDR']), true);
			if($response['success'] == false){
				echo '<h2>'.(isset($LANG['RECAPTCHA_FAILED'])?$LANG['RECAPTCHA_FAILED']:'Recaptcha verification failed').'</h2>';
				$okToCreateLogin = false;
			}
		}
		else{
			$okToCreateLogin = false;
			$displayStr = '<h2>'.(isset($LANG['PLEASE_CHECK'])?$LANG['PLEASE_CHECK']:'Please check the the captcha form').'</h2>';
		}
	}

	if($okToCreateLogin){
		if($pHandler->validateEmailAddress($emailAddr)){
			if($pHandler->loginExists($emailAddr)){
				$displayStr = $pHandler->getErrorMessage();
			}
			else{
				if($pHandler->register($_POST)){
					header("Location: ../index.php");
				}
				else{
					$displayStr = (isset($LANG['FAILED_1'])?$LANG['FAILED_1']:'FAILED: Unable to create user').'.<div style="margin-left:55px;">'.(isset($LANG['FAILED_2'])?$LANG['FAILED_2']:'Please contact system administrator for assistance').'.</div>';
				}
			}
		}
	}
}

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' - '.(isset($LANG['NEW_USER'])?$LANG['NEW_USER']:'New User Profile'); ?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function validateform(f){
			<?php
			if($useRecaptcha){
				?>
				if(grecaptcha.getResponse() == ""){
					alert("<?php echo (isset($LANG['CHECK_CAPTCHA'])?$LANG['CHECK_CAPTCHA']:"You must first check the reCAPTCHA checkbox (I'm not a robot)"); ?>");
					return false;
				}
				<?php
			}
			?>
			var pwd1 = f.pwd.value.trim();
			var pwd2 = f.pwd2.value.trim();
			if(pwd1 == "" || pwd2 == ""){
				alert("<?php echo (isset($LANG['BOTH_PASSWORDS'])?$LANG['BOTH_PASSWORDS']:'Both password fields must contain a value'); ?>");
				return false;
			}
			if(pwd1.charAt(0) == " " || pwd1.slice(-1) == " "){
				alert("<?php echo (isset($LANG['NO_SPACE'])?$LANG['NO_SPACE']:'Password cannot start or end with a space, but they can include spaces within the password'); ?>");
				return false;
			}
			if(pwd1.length < 7){
				alert("<?php echo (isset($LANG['GREATER_THAN_SIX'])?$LANG['GREATER_THAN_SIX']:'Password must be greater than 6 characters'); ?>");
				return false;
			}
			if(pwd1 != pwd2){
				alert("<?php echo (isset($LANG['NO_MATCH'])?$LANG['NO_MATCH']:'Passwords do not match, please enter again'); ?>");
				f.pwd.value = "";
				f.pwd2.value = "";
				f.pwd2.focus();
				return false;
			}
			if( /[^0-9A-Za-z_!@#$-+.]/.test( f.login.value ) ) {
		        alert("<?php echo (isset($LANG['NO_SPECIAL_CHARS'])?$LANG['NO_SPECIAL_CHARS']:'Username should only contain 0-9A-Za-z_.!@ (spaces are not allowed)'); ?>");
		        return false;
		    }
			return true;
		}
	</script>
	<?php
	if($useRecaptcha) echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
	?>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_newprofileMenu)?$profile_newprofileMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($profile_newprofileCrumbs)){
		echo "<div class='navpath'>";
		echo $profile_newprofileCrumbs;
		echo '<b>'.(isset($LANG['CREATE_NEW'])?$LANG['CREATE_NEW']:'Create New Profile').'</b>';
		echo "</div>";
	}
	?>
	<div id="innertext">
	<?php
	echo '<h1>'.(isset($LANG['CREATE_NEW'])?$LANG['CREATE_NEW']:'Create New Profile').'</h1>';
	if($displayStr){
		echo '<div style="margin:10px;color:red;">';
		if($displayStr == 'login_exists'){
			echo (isset($LANG['USERNAME_EXISTS_1'])?$LANG['USERNAME_EXISTS_1']:'This username');
			echo '('.$login.') '.(isset($LANG['USERNAME_EXISTS_2'])?$LANG['USERNAME_EXISTS_2']:'is already being used').'.<br>';
			echo (isset($LANG['USERNAME_EXISTS_3'])?$LANG['USERNAME_EXISTS_3']:'Please choose a different login name or visit the');
			echo ' <a href="index.php?login='.$login.'">'.(isset($LANG['LOGIN_PAGE'])?$LANG['LOGIN_PAGE']:'login page').'</a> ';
			echo (isset($LANG['USERNAME_EXISTS_4'])?$LANG['USERNAME_EXISTS_4']:'if you believe this might be you').'.';
		}
		elseif($displayStr == 'email_registered'){
			?>
			<div>
				<?php echo (isset($LANG['ALREADY_REGISTERED'])?$LANG['ALREADY_REGISTERED']:'A different login is already registered to this email address').'.<br/>'.
				(isset($LANG['USE_BUTTON'])?$LANG['USE_BUTTON']:'Use button below to have login emailed to').' '.$emailAddr; ?>
				<div style="margin:15px">
					<form name="retrieveLoginForm" method="post" action="index.php">
						<input name="email" type="hidden" value="<?php echo $emailAddr; ?>" />
						<button name="action" type="submit" value="Retrieve Login"><?php echo (isset($LANG['RETRIEVE_LOGIN'])?$LANG['RETRIEVE_LOGIN']:'Retrieve Login'); ?></button>
					</form>
				</div>
			</div>
			<?php
		}
		elseif($displayStr == 'email_invalid'){
			echo (isset($LANG['EMAIL_INVALID'])?$LANG['EMAIL_INVALID']:'Email address not valid');
		}
		else{
			echo $displayStr;
		}
		echo '</div>';
	}
	?>
	<fieldset style='margin:10px;width:95%;'>
		<legend><b><?php echo (isset($LANG['LOGIN_DETAILS'])?$LANG['LOGIN_DETAILS']:'Login Details'); ?></b></legend>
		<form action="newprofile.php" method="post" onsubmit="return validateform(this);">
			<div style="margin:15px;">
				<table cellspacing='3'>
					<tr>
						<td style="width:120px;">
							<b><?php echo (isset($LANG['USERNAME'])?$LANG['USERNAME']:'Username'); ?>:</b>
						</td>
						<td>
							<input name="login" value="<?php echo $login; ?>" type="text" size="20" required />
							<span style="color:red;">*</span>
							<br/>&nbsp;
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo (isset($LANG['PASSWORD'])?$LANG['PASSWORD']:'Password'); ?>:</b>
						</td>
						<td>
							<input name="pwd" id="pwd" value="" size="20" type="password" autocomplete="off" required />
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo (isset($LANG['PASSWORD_AGAIN'])?$LANG['PASSWORD_AGAIN']:'Password Again'); ?>:</b>
						</td>
						<td>
							<input id="pwd2" name="pwd2" value="" size="20" type="password" autocomplete="off" required />
							<span style="color:red;">*</span>
							<br/>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['FIRST_NAME'])?$LANG['FIRST_NAME']:'First Name'); ?>:</span></td>
						<td>
							<input id="firstname" name="firstname" type="text" size="40" value="<?php echo (isset($_POST['firstname'])?htmlspecialchars($_POST['firstname']):''); ?>" />
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['LAST_NAME'])?$LANG['LAST_NAME']:'Last Name'); ?>:</span></td>
						<td>
							<input id="lastname" name="lastname" type="text" size="40" value="<?php echo (isset($_POST['lastname'])?htmlspecialchars($_POST['lastname']):''); ?>" required />
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['EMAIL'])?$LANG['EMAIL']:'Email Address'); ?>:</span></td>
						<td>
							<span class="profile"><input name="email" type="email" size="40" value="<?php echo $emailAddr; ?>" required /></span>
							<span style="color:red;">*</span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['ORCID'])?$LANG['ORCID']:'ORCID or other GUID'); ?>:</span></td>
						<td>
							<span class="profile"><input name="guid" type="text" size="40" value="<?php echo (isset($_POST['guid'])?htmlspecialchars($_POST['guid']):''); ?>" /></span>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><span style="color:red;">* <?php echo (isset($LANG['REQUIRED'])?$LANG['REQUIRED']:'required fields'); ?></span></td>
					</tr>
				</table>
				<div style="margin:15px 0px 10px 0px;"><b><u><?php echo (isset($LANG['OPTIONAL'])?$LANG['OPTIONAL']:'Information below is optional, but encouraged'); ?></u></b></div>
				<table cellspacing='3'>
					<tr>
						<td><b><?php echo (isset($LANG['TITLE'])?$LANG['TITLE']:'Title'); ?>:</b></td>
						<td>
							<span class="profile"><input name="title" type="text" size="40" value="<?php echo (isset($_POST['title'])?htmlspecialchars($_POST['title']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b><?php echo (isset($LANG['INSTITUTION'])?$LANG['INSTITUTION']:'Institution'); ?>:</b></td>
						<td>
							<span class="profile"><input name="institution"  type="text" size="40" value="<?php echo (isset($_POST['institution'])?htmlspecialchars($_POST['institution']):'') ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['CITY'])?$LANG['CITY']:'City'); ?>:</span></td>
						<td>
							<span class="profile"><input id="city" name="city" type="text" size="40" value="<?php echo (isset($_POST['city'])?$_POST['city']:''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['STATE'])?$LANG['STATE']:'State'); ?>:</span></td>
						<td>
							<span class="profile"><input id="state" name="state" type="text" size="40" value="<?php echo (isset($_POST['state'])?htmlspecialchars($_POST['state']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><b><?php echo (isset($LANG['ZIP_CODE'])?$LANG['ZIP_CODE']:'Zip Code'); ?>:</b></td>
						<td>
							<span class="profile"><input name="zip" type="text" size="40" value="<?php echo (isset($_POST['zip'])?htmlspecialchars($_POST['zip']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td><span style="font-weight:bold;"><?php echo (isset($LANG['COUNTRY'])?$LANG['COUNTRY']:'Country'); ?>:</span></td>
						<td>
							<span class="profile"><input id="country" name="country" type="text" size="40" value="<?php echo (isset($_POST['country'])?htmlspecialchars($_POST['country']):''); ?>"></span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div style="margin:10px;">
								<?php
								if($useRecaptcha) echo '<div class="g-recaptcha" data-sitekey="'.$RECAPTCHA_PUBLIC_KEY.'"></div>';
								?>
							</div>
							<div style="float:right;margin:20px;">
								<button id="submit" name="submit" type="submit" value="Create Login"><?php echo (isset($LANG['CREATE_LOGIN'])?$LANG['CREATE_LOGIN']:'Create Login'); ?></button>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</fieldset>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>