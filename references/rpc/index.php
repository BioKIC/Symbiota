<?php
include_once('../../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/prohibit.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/prohibit.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/prohibit.en.php');

header('Content-Type: text/html; charset=' . $CHARSET);
header('Location: '.$CLIENT_ROOT.'/index.php');
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Forbidden</title>
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
			<h1 class="page-heading"><?php echo $LANG['FORBIDDEN']; ?></h1>
			<div style="font-weight:bold;">
				You don't have permission to access this page.
			</div>
			<div style="font-weight:bold;margin:10px;">
				<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php">Return to index page</a>
			</div>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>