<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
include_once($SERVER_ROOT.'/classes/Person.php');
@include_once($SERVER_ROOT.'/content/lang/profile/viewprofile.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$action = array_key_exists('action', $_REQUEST) ? htmlspecialchars($_REQUEST['action'], HTML_SPECIAL_CHARS_FLAGS) : '';
$userId = array_key_exists('userid', $_REQUEST) ? filter_var($_REQUEST['userid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tabIndex = array_key_exists('tabindex',$_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;

//Sanitation
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$isSelf = 0;
$isEditor = 0;
if(isset($SYMB_UID) && $SYMB_UID){
	if(!$userId){
		$userId = $SYMB_UID;
	}
	if($userId == $SYMB_UID){
		$isSelf = 1;
	}
	if($isSelf || $IS_ADMIN){
		$isEditor = 1;
	}
}
if(!$userId) header('Location: index.php?refurl=viewprofile.php');

$pHandler = new ProfileManager();
$pHandler->setUid($userId);

$statusStr = '';
$person = null;
if($isEditor){
	if($action == 'Submit Edits'){
		if(!$pHandler->updateProfile($_POST)){
			$statusStr = (isset($LANG['FAILED'])?$LANG['FAILED']:'Profile update failed!');
		}
		$person = $pHandler->getPerson();
		$tabIndex = 2;
	}
	elseif($action == 'Change Password'){
		$newPwd = $_REQUEST['newpwd'];
		$updateStatus = false;
		if($isSelf){
			$oldPwd = $_REQUEST['oldpwd'];
			$updateStatus = $pHandler->changePassword($newPwd, $oldPwd, $isSelf);
		}
		else{
			$updateStatus = $pHandler->changePassword($newPwd);
		}
		if($updateStatus){
			$statusStr = '<span style="color:green">'.(isset($LANG['PWORD_SUCCESS'])?$LANG['PWORD_SUCCESS']:'Password update successful').'!</span>';
		}
		else{
			$statusStr = '<span style="color:red">'.(isset($LANG['PWORD_FAILED'])?$LANG['PWORD_FAILED']:'Password update failed! Are you sure you typed the old password correctly?').'</span>';
		}
		$person = $pHandler->getPerson();
		$tabIndex = 2;
	}
	elseif($action == 'changeLogin'){
		$pwd = '';
		if($isSelf && isset($_POST['newloginpwd'])) $pwd = $_POST['newloginpwd'];
		if($pHandler->changeLogin($_POST['newlogin'], $pwd)){
			$statusStr = '<span style="color:green">'.$LANG['UPDATE_SUCCESSFUL'].'</span>';
		}
		else{
			$statusStr = '<span style="color:red">';
			if($pHandler->getErrorMessage() == 'loginExists') $statusStr .= $LANG['LOGIN_USED'];
			elseif($pHandler->getErrorMessage() == 'incorrectPassword') $statusStr .= $LANG['INCORRECT_PWD'];
			else $statusStr .= $LANG['ERROR_SAVING_LOGIN'];
			$statusStr .= '</span>';
		}
		$person = $pHandler->getPerson();
		$tabIndex = 2;
	}
	elseif($action == 'Clear Tokens'){
		if($pHandler->clearAccessTokens()) $statusStr = '<span color="green">'.$LANG['TOKENS_CLEARED'].'</span>';
		else $statusStr = '<span style="color:red">'.$LANG['TOKENS_ERROR'].': '.$pHandler->getErrorMessage().'</span>';
		$person = $pHandler->getPerson();
		$tabIndex = 2;
	}
	elseif($action == 'deleteProfile'){
		if($pHandler->deleteProfile()){
			if($isSelf) header('Location: ../index.php');
			else header('Location: usermanagement.php');
		}
		else{
			$statusStr = $LANG['DELETE_FAILED'].' ';
			if(strpos($pHandler->getErrorMessage(), 'foreign key constraint fails')){
				$statusStr .= $LANG['DATA_CONFLICT'].' ';
			}
			$statusStr .= $LANG['CONTACT_ADMIN'];
			if($IS_ADMIN) $statusStr .= '<br>'.$pHandler->getErrorMessage();
			$statusStr = '<span style="color:red">'.$statusStr.'</span>';
		}
	}
	elseif($action == 'delusertaxonomy'){
		$statusStr = $pHandler->deleteUserTaxonomy($_GET['utid']);
		$person = $pHandler->getPerson();
		$tabIndex = 2;
	}
	elseif($action == 'Add Taxonomic Relationship'){
		$statusStr = $pHandler->addUserTaxonomy($_POST['taxon'], $_POST['editorstatus'], $_POST['geographicscope'], $_POST['notes']);
		$person = $pHandler->getPerson();
		$tabIndex = 2;
	}

	if(!$person) $person = $pHandler->getPerson();
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' - '. (isset($LANG['VIEW_PROFILE'])?$LANG['VIEW_PROFILE']:'View User Profile'); ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/profile.viewprofile.js?ver=20170530"></script>
	<script type="text/javascript" src="../js/symb/shared.js"></script>
	<style type="text/css">
		fieldset{ padding:15px;margin:15px; }
		legend{ font-weight: bold; }
		.tox-dialog { min-height: 400px }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_viewprofileMenu)?$profile_viewprofileMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href='../index.php'><?php echo (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a> &gt;&gt;
		<a href="../profile/viewprofile.php"><?php echo (isset($LANG['MY_PROFILE'])?$LANG['MY_PROFILE']:'My Profile'); ?></a>
	</div>
	<div id="innertext">
		<?php
		if($isEditor){
			if($statusStr) echo $statusStr;
			?>
			<div id="tabs" style="margin:10px;">
				<ul>
					<?php
					if($floraModIsActive){
						?>
						<li><a href="../checklists/checklistadminmeta.php?userid=<?php echo $userId; ?>"><?php echo (isset($LANG['SPEC_CHECKLIST'])?$LANG['SPEC_CHECKLIST']:'Species Checklists'); ?></a></li>
						<?php
					}
					?>
					<li><a href="occurrencemenu.php"><?php echo (isset($LANG['OCC_MGMNT'])?$LANG['OCC_MGMNT']:'Occurrence Management'); ?></a></li>
					<li><a href="userprofile.php?userid=<?php echo $userId; ?>"><?php echo (isset($LANG['USER_PROFILE'])?$LANG['USER_PROFILE']:'User Profile'); ?></a></li>
					<?php
					if($person->getIsTaxonomyEditor()) {
						echo '<li><a href="specimenstoid.php?userid='.$userId.'&action='.$action.'">'.(isset($LANG['IDS_NEEDED'])?$LANG['IDS_NEEDED']:'IDs Needed').'</a></li>';
						echo '<li><a href="imagesforid.php">'.(isset($LANG['IMAGES_ID'])?$LANG['IMAGES_ID']:'Images for ID').'</a></li>';
					}
					?>
				</ul>
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