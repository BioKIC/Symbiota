<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/misc/aboutproject.' . $LANG_TAG . '.php'))
include_once($SERVER_ROOT . '/content/lang/misc/aboutproject.en.php');
else include_once($SERVER_ROOT . '/content/lang/misc/aboutproject.' . $LANG_TAG . '.php');
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
	<head>
		<title><?= $LANG['ABOUT_PROJECT'] ?></title>
		<?php

		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../index.php"><?= $LANG['HOME']; ?></a> &gt;&gt;
			<b><?= $LANG['ABOUT_PROJECT']; ?></b>
		</div>
		<!-- This is inner text! -->
		<div role="main" id="innertext" style="margin:10px 20px">
			<h1 class="page-heading"><?= $LANG['ABOUT_PROJECT']; ?>:</h1>

			<p></p>

			<h1><?= $LANG['FUNDING']; ?>:</h1>

			<p>This portal has been supported by the following NSF Awards:</p>

			<p>
				Thematic Collections Network Award:
				<a href="https://www.nsf.gov/awardsearch/showAward?AWD_ID=------" target="_blank">-----</a>
			</p>

		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>