<?php
include_once('../../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/misc/collbackup.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT . '/content/lang/collections/misc/collbackup.' . $LANG_TAG . '.php');
}
else include_once($SERVER_ROOT . '/content/lang/collections/misc/collbackup.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$collid = isset($_REQUEST['collid']) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = isset($_REQUEST['formsubmit']) ? $_REQUEST['formsubmit'] : '';
$cSet = isset($_REQUEST['cset']) ? $_REQUEST['cset'] : '';

$isEditor = 0;
if($IS_ADMIN){
	$isEditor = 1;
}
elseif($collid && isset($USER_RIGHTS['CollAdmin']) && in_array($collid, $USER_RIGHTS['CollAdmin'])){
	$isEditor = 1;
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
<head>
	<title><?= $LANG['OCCURRENCE_DOWNLOAD'] ?></title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
    <script>
    	function submitBuForm(f){
			f.formsubmit.disabled = true;
			document.getElementById("workingdiv").style.display = "block";
			return true;
    	}
    </script>
    <style>
    	fieldset{ padding:15px;width:350px }
    	legend{ font-weight: bold }
    	label{ font-weight: bold }
    	#workingdiv{ float:left; margin-left:15px; font-weight: bold; }
    </style>
</head>
<body>
	<div role="main" id="innertext">
		<h1 class="page-heading">Download Backup File</h1>
		<?php
		if($isEditor){
			?>
			<form name="buform" action="../download/downloadhandler.php" method="post" onsubmit="return submitBuForm(this);">
				<fieldset>
					<legend><?= $LANG['DOWNLOAD_MODULE'] ?></legend>
					<div style="height:50px; margin: 10px">
						<input type="radio" id="cset1" name="cset" value="iso-8859-1" <?= (!$cSet || $cSet == 'iso88591' ? 'checked' : ''); ?> /> <label for="cset1">ISO-8859-1 (western)</label><br/>
						<input type="radio" id="cset2" name="cset" value="utf-8" <?= ($cSet == 'utf8' ? 'checked' : ''); ?> /> <label for="cset2">UTF-8 (unicode)</label>
					</div>
					<div>
						<div style="float:left">
							<input type="hidden" name="collid" value="<?= $collid; ?>">
							<input type="hidden" name="schema" value="backup">
							<button type="submit" name="formsubmit"><?= $LANG['DOWNLOAD'] ?></button>
						</div>
						<div id="workingdiv" style="display:<?= ($action == 'Perform Backup' ? 'block' : 'none') ?>;">
							<?= $LANG['DOWNLOADING'] ?>...
						</div>
					</div>
				</fieldset>
			</form>
			<?php
		}
		?>
	</div>
</body>
</html>