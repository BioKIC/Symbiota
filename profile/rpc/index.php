<?php
include_once('../../config/symbini.php');
@include_once($SERVER_ROOT.'/content/lang/prohibit.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo (isset($LANG['PAGE'])?$LANG['PAGE']:'Page'); ?></title>
		<?php

		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h1 class="page-heading"><?= $LANG['FORBIDDEN']; ?></h1>
			<div style="font-weight:bold;">
				<?php echo (isset($LANG['NO_PERMISSION'])?$LANG['NO_PERMISSION']:'You don\'t have permission to access this page'); ?>.
			</div>
			<div style="font-weight:bold;margin:10px;">
				<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?php echo htmlspecialchars((isset($LANG['RETURN'])?$LANG['RETURN']:'Return to index page'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
			</div>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>