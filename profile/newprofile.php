<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
@include_once($SERVER_ROOT.'/content/lang/profile/newprofile.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
header('Cache-Control: no-cache, no-cache="set-cookie", no-store, must-revalidate');
header('Pragma: no-cache'); // HTTP 1.0.
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$login = array_key_exists('login', $_POST) ? htmlspecialchars($_POST['login'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$emailAddr = array_key_exists('email',$_POST) ? htmlspecialchars($_POST['email'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$action = array_key_exists('submit', $_POST) ? $_POST['submit'] : '';
$adminRegister = array_key_exists('adminRegister', $_POST) ? true : false;

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

$useRecaptcha = false;
if(isset($RECAPTCHA_PUBLIC_KEY) && $RECAPTCHA_PUBLIC_KEY && isset($RECAPTCHA_PRIVATE_KEY) && $RECAPTCHA_PRIVATE_KEY){
	$useRecaptcha = true;
}

if($action == 'Create Login'){
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
				if($pHandler->register($_POST, $adminRegister)){
					if(!$adminRegister){
						header("Location: ../index.php");
					} else{
						$_SESSION['adminRegisterSuccessfulUsername'] = $login;
						header("Location: ./usermanagement.php");
					}
				}
				else{
					$displayStr = (isset($LANG['FAILED_1'])?$LANG['FAILED_1']:'FAILED: Unable to create user').'.<div style="margin-left:55px;">'.(isset($LANG['FAILED_2'])?$LANG['FAILED_2']:'Please contact system administrator for assistance').'.</div>';
				}
			}
		}
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' - ' . $LANG['NEW_USER']; ?></title>
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
	<style>
		.gridlike-form-row-label {
			width: 100px;
		}
		.gridlike-form-row-input {
  			width: 40%;
		}
		.small-header {
			font-size: 1.3em;
			font-weight: bold;
		}
	</style>
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
	$shouldBeAbleToCreatePublicUser = true;
	if(isset($SHOULD_BE_ABLE_TO_CREATE_PUBLIC_USER) && !$SHOULD_BE_ABLE_TO_CREATE_PUBLIC_USER){
		$shouldBeAbleToCreatePublicUser = false;
		if($adminRegister) $shouldBeAbleToCreatePublicUser = true;
	}
	if($shouldBeAbleToCreatePublicUser){
		?>
		<div role="main" id="innertext">
		<?php
		echo '<h1 class="page-heading">'.(isset($LANG['CREATE_NEW']) ? $LANG['CREATE_NEW'] : 'Create New Profile') . '</h1>';
		if($displayStr){
			echo '<div style="margin:10px;color:red;">';
			if($displayStr == 'login_exists'){
				echo (isset($LANG['USERNAME_EXISTS_1']) ? $LANG['USERNAME_EXISTS_1'] : 'This username');
				echo '(' . $login . ') ' . (isset($LANG['USERNAME_EXISTS_2']) ? $LANG['USERNAME_EXISTS_2'] : 'is already being used') . '.<br>';
				echo (isset($LANG['USERNAME_EXISTS_3']) ? $LANG['USERNAME_EXISTS_3'] : 'Please choose a different login name or visit the');
				echo ' <a href="index.php?login=' . htmlspecialchars($login, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars((isset($LANG['LOGIN_PAGE']) ? $LANG['LOGIN_PAGE'] : 'login page'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ';
				echo (isset($LANG['USERNAME_EXISTS_4']) ? $LANG['USERNAME_EXISTS_4'] : 'if you believe this might be you').'.';
			}
			elseif($displayStr == 'email_registered'){
				?>
				<div>
					<?php echo (isset($LANG['ALREADY_REGISTERED']) ? $LANG['ALREADY_REGISTERED'] : 'A different login is already registered to this email address') . '.<br/>'.
					(isset($LANG['USE_BUTTON']) ? $LANG['USE_BUTTON'] : 'Use button below to have login emailed to') . ' ' . $emailAddr; ?>
					<div style="margin:15px">
						<form name="retrieveLoginForm" method="post" action="index.php">
							<input name="email" type="hidden" value="<?php echo $emailAddr; ?>" />
							<button name="action" type="submit" value="Retrieve Login"><?php echo (isset($LANG['RETRIEVE_LOGIN']) ? $LANG['RETRIEVE_LOGIN'] : 'Retrieve Login'); ?></button>
						</form>
					</div>
				</div>
				<?php
			}
			elseif($displayStr == 'email_invalid'){
				echo (isset($LANG['EMAIL_INVALID']) ? $LANG['EMAIL_INVALID'] : 'Email address not valid');
			}
			else{
				echo $displayStr;
			}
			echo '</div>';
		}
		?>
		<form action="newprofile.php" method="post" onsubmit="return validateform(this);">
			<fieldset style='margin:10px;width:95%;'>
				<legend><b><?php echo (isset($LANG['LOGIN_DETAILS']) ? $LANG['LOGIN_DETAILS'] : 'Login Details'); ?></b></legend>
				<div class="gridlike-form">
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="login"><?php echo (isset($LANG['USERNAME']) ? $LANG['USERNAME'] : 'Username'); ?>:</label>
						<input class="gridlike-form-row-input" name="login" id="login" value="<?php echo $login; ?>" type="text" size="20" required />
						<span style="color:red;">*</span>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="pwd"><?php echo (isset($LANG['PASSWORD']) ? $LANG['PASSWORD'] : 'Password'); ?>:</label>
						<input class="gridlike-form-row-input" name="pwd" id="pwd" value="" size="20" type="password" autocomplete="off" required />
						<span style="color:red;">*</span>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="pwd2"><?php echo (isset($LANG['PASSWORD_AGAIN']) ? $LANG['PASSWORD_AGAIN'] : 'Password Again'); ?>:</label>
						<input class="gridlike-form-row-input" id="pwd2" name="pwd2" value="" size="20" type="password" autocomplete="off" required />
						<span style="color:red;">*</span>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="firstname"><?php echo (isset($LANG['FIRST_NAME']) ? $LANG['FIRST_NAME'] : 'First Name'); ?>:</label>
						<input class="gridlike-form-row-input" id="firstname" name="firstname" type="text" size="40" value="<?php echo (isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname'],ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>" required />
						<span style="color:red;">*</span>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="lastname"><?php echo (isset($LANG['LAST_NAME'])?$LANG['LAST_NAME']:'Last Name'); ?>:</label>
						<input class="gridlike-form-row-input" id="lastname" name="lastname" type="text" size="40" value="<?php echo (isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>" required />
						<span style="color:red;">*</span>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="email"><?php echo (isset($LANG['EMAIL']) ? $LANG['EMAIL'] : 'Email Address'); ?>:</label>
						<input class="gridlike-form-row-input" name="email" id="email" type="email" size="40" value="<?php echo $emailAddr; ?>" required />
						<span style="color:red;">*</span>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<label class="gridlike-form-row-label" for="guid"><?php echo (isset($LANG['ORCID']) ? $LANG['ORCID'] : 'ORCID or other GUID'); ?>:</label>
						<input class="gridlike-form-row-input" name="guid" id="guid" type="text" size="40" value="<?php echo (isset($_POST['guid']) ? htmlspecialchars($_POST['guid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>" />
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
							<span class="gridlike-form-row-label"><?php echo (isset($LANG['ACCESSIBILITY_PREF']) ? $LANG['ACCESSIBILITY_PREF'] : 'Accessibility Preferences'); ?>:</span>
							<input type="checkbox" name="accessibility-pref" id="accessibility-pref" value="1" />
							<label for="accessibility-pref"><?php echo (isset($LANG['ACCESSIBILITY_PREF_DESC'])? $LANG['ACCESSIBILITY_PREF_DESC'] : 'Check to indicate a preference for accessibility-optimized styles'); ?></label>
					</section>
					<section class="bottom-breathing-room gridlike-form-row">
						<span style="color:red;">* <?php echo (isset($LANG['REQUIRED']) ? $LANG['REQUIRED'] : 'required fields'); ?></span>
					</section>
					<section>
						<section class="bottom-breathing-room gridlike-form-row">
							<h1 class="small-header"><?php echo (isset($LANG['OPTIONAL']) ? $LANG['OPTIONAL'] : 'Information below is optional, but encouraged'); ?></h1>
							<hr/>
						</section>
						<section class="gridlike-form-row">
							<label class="gridlike-form-row-label" for="title"><?php echo (isset($LANG['TITLE'])?$LANG['TITLE']:'Title'); ?>:</label>
							<input class="gridlike-form-row-input" name="title" id="title" type="text" size="40" value="<?php echo (isset($_POST['title']) ? htmlspecialchars($_POST['title'],ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>">
						</section>
						<section class="gridlike-form-row">
							<label class="gridlike-form-row-label" for="institution"><?php echo (isset($LANG['INSTITUTION'])?$LANG['INSTITUTION']:'Institution'); ?>:</label>
							<input class="gridlike-form-row-input" name="institution" id="institution"  type="text" size="40" value="<?php echo (isset($_POST['institution']) ? htmlspecialchars($_POST['institution'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '') ?>">
						</section>
						<section class="gridlike-form-row">
							<label class="gridlike-form-row-label" for="city"><?php echo (isset($LANG['CITY'])?$LANG['CITY']:'City'); ?>:</label>
							<input class="gridlike-form-row-input" id="city" name="city" type="text" size="40" value="<?php echo (isset($_POST['city']) ? htmlspecialchars($_POST['city'],ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>">
						</section>
						<section class="gridlike-form-row">
							<label class="gridlike-form-row-label" for="state"><?php echo (isset($LANG['STATE'])?$LANG['STATE']:'State'); ?>:</label>
							<input class="gridlike-form-row-input" id="state" name="state" type="text" size="40" value="<?php echo (isset($_POST['state']) ? htmlspecialchars($_POST['state'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>">
						</section>
						<section class="gridlike-form-row">
							<label class="gridlike-form-row-label" for="zip"><?php echo (isset($LANG['ZIP_CODE'])?$LANG['ZIP_CODE']:'Zip Code'); ?>:</label>
							<input class="gridlike-form-row-input" name="zip" id="zip" type="text" size="40" value="<?php echo (isset($_POST['zip']) ? htmlspecialchars($_POST['zip'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>">
						</section>
						<section class="gridlike-form-row">
							<label class="gridlike-form-row-label" for="country"><?php echo (isset($LANG['COUNTRY'])?$LANG['COUNTRY']:'Country'); ?>:</label>
							<input class="gridlike-form-row-input" id="country" name="country" type="text" size="40" value="<?php echo (isset($_POST['country']) ? htmlspecialchars($_POST['country'],ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : ''); ?>">
						</section>
							<div style="margin:10px;">
								<?php
								if($useRecaptcha) echo '<div class="g-recaptcha" data-sitekey="' . $RECAPTCHA_PUBLIC_KEY . '"></div>';
								?>
							</div>
							<?php if($adminRegister){ ?>
								<input type="hidden" id="adminRegister" name="adminRegister" value="1"></input>
							<?php } ?>
							<button id="submit" name="submit" type="submit" value="Create Login"><?php echo (isset($LANG['CREATE_LOGIN']) ? $LANG['CREATE_LOGIN'] : 'Create Login'); ?></button>
					</section>
				</div>
			</fieldset>
		</form>
		</div>
		<?php
	} else{
		?>
		<div role="main" id="innertext">
			<h1><?php echo htmlspecialchars((isset($LANG['IPROFILE_CREATION_DISABLED']) ? $LANG['PROFILE_CREATION_DISABLED'] : 'Public user creation has been disabled on this portal.'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></h1>
		</div>
		<?php
	}
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>