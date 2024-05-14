<?php
include_once('../../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
header('Location: '.$CLIENT_ROOT.'/index.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/prohibit.'.$LANG_TAG.'.php'))
include_once($SERVER_ROOT.'/content/lang/prohibit.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/prohibit.en.php');

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?= $LANG['FORBIDDEN'] ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h1 class="page-heading"><?= $LANG['FORBIDDEN'] ?></h1>
			<div style="font-weight:bold;">
				<?= $LANG['NO_PERMISSION'] ?>
			</div>
			<div style="font-weight:bold;margin:10px;">
				<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?= $LANG['RETURN'] ?></a>
			</div>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>