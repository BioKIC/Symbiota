<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/AdminConfig.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/admin/configmanager.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT.'/content/lang/admin/configmanager.' . $LANG_TAG . '.php');
}
else include_once($SERVER_ROOT . '/content/lang/admin/configmanager.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$action = isset($_POST['action']) ? $_POST['action'] : '';

if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../admin/adminconfig.php');

$adminConfig = new AdminConfig();

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
}

?>
<html lang="<?= $LANG_TAG ?>">
	<head>
		<title><?= $LANG['CONFIG_MANAGER'] ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script>
			function verifyEditPropertyForm(f){
				if(f.prop.value == ""){
					alert("<?= $LANG['SELECT_PROPERTY']; ?>");
					return false;
				}
				else if(f.propvalue.value == ""){
					alert("<?= $LANG['ENTER_PROPERTY_VALUE']; ?>");
					return false;
				}
				return true;
			}
		</script>
		<style type="text/css">
			label{ font-weight:bold; }
			fieldset{ padding: 15px }
			fieldset legend{ font-weight:bold; }
			.info-div{ margin:5px 5px 20px 5px; }
			.form-section{ margin: 5px 10px; }
			button{ margin: 15px; }
		</style>
	</head>
	<body>
		<?php
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class='navpath'>
			<a href='../../index.php'><?= $LANG['HOME'] ?></a> &gt;&gt;
			<b><?= $LANG['CONFIG_MANAGER'] ?></b>
		</div>
		<div role="main" id="innertext">
			<h1><?= $LANG['CONFIG_MANAGER'] ?></h1>
			<?php
			if($IS_ADMIN){

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
