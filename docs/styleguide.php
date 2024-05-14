<?php
include_once('../config/symbini.php');
include_once('../content/lang/index.' . $LANG_TAG . '.php');
header("Content-Type: text/html; charset=" . $CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<title><?php echo $DEFAULT_TITLE; ?> Style Guide</title>
	<?php
		include_once($SERVER_ROOT . '/includes/head.php');
		include_once($SERVER_ROOT . '/includes/googleanalytics.php');
		include_once($SERVER_ROOT . '/includes/header.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/accessibilityUtils.js" type="text/javascript"></script>
</head>

<body>
	<main id="innertext">
		<h1>Style Guide</h1>
		<hr>
		<h1 class="page-heading">Page Heading</h1>
		<h1>Heading 1</h1>
		<h2>Heading 2</h2>
		<h3>Heading 3</h3>
		<h4>Heading 4</h4>
		<p>Paragraph</p>
		<p><a href="#">Link</a></p>
		<p><button>Button</button></p>
		<p class="grid-3"><span class="button button-primary"><a href="#">Primary Button (Link)</a></span><span class="button button-secondary"><a href="#">Secondary Button (Link)</a></span><span class="button button-tertiary"><a href="#">Tertiary Button (Link)</a></span></p>
		<h1>Forms in accessibility mode vs condensed mode</h1>
		<section style="margin-bottom: 10;">
			<button style="font-size:14" onclick="toggleAccessibilityStyles('<?php echo $CLIENT_ROOT . '/includes' . '/' ?>', '<?php echo $CSS_BASE_PATH ?>', '<?php echo $LANG['TOGGLE_508_OFF'] ?>', '<?php echo $LANG['TOGGLE_508_ON'] ?>')" id="accessibility-button-2" name="accessibility-button-2" data-accessibility="accessibility-button" ?><?php echo (isset($LANG['TOGGLE_508_ON'])?$LANG['TOGGLE_508_ON']:'Accessibility Mode'); ?></button>
		</section>
		<section class="flex-form">
			<section>
				<label for="input-1">Input 1: </label>
				<input id="input-1" name="input-1" type="text" value="" required autocomplete="off">
			</section>
			<section>
				<label for="input-2">Input 2: </label>
				<input id="input-2" name="input-2" type="text" value="" required autocomplete="off">
			</section>
		</section>
	</main>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', ()=>{
			document.getElementById('accessibility-button-2').disabled=false;
			updateButtonTextBasedOnEnabledStylesheet('<?php echo $LANG['TOGGLE_508_OFF'] ?>', '<?php echo $LANG['TOGGLE_508_ON'] ?>', 'accessibility-button-2');
		});
	</script>
</body>

</html>