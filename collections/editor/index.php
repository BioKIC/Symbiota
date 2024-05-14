<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/prohibit.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $LANG['NO_ACCESS']; ?></title>
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
			<h1 class="page-heading"><?= $LANG['FORBIDDEN']; ?></h1>
			<div style="font-weight:bold;">
				<?php echo $LANG['NO_PERMISSION']; ?>.
			</div>
			<div style="font-weight:bold;margin:10px;">
				<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?php echo htmlspecialchars($LANG['RETURN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
			</div>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>