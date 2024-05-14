<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ' . $CLIENT_ROOT . '/profile/index.php?refurl=../collections/loans/specimennotes.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$occid = $_REQUEST['occid'];
$loanID = $_REQUEST['loanid'];

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($occid)) $occid = 0;
if(!is_numeric($loanID)) $loanID = 0;

$isEditor = 0;
if($SYMB_UID && $collid){
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))
		|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor']))){
		$isEditor = 1;
	}
}

$loanManager = new OccurrenceLoans();
$loanManager->setCollId($collid);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['LOAN_NOTES_EDITOR']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function submitNotesForm(f){
			self.close();
		}
	</script>
	<style>
		body{ width:800px; min-width:400px; max-width:1000px; background-color: #FFFFFF; }
		fieldset{ padding:20px }
		fieldset legend{ font-weight:bold }
	</style>
</head>
<body>
	<!-- This is inner text! -->
	<div id="popup-innertext" class="left-breathing-room-rel">
		<h1 class="page-heading"><?= $LANG['LOAN_NOTES_EDITOR']; ?></h1>
		<?php
		if($isEditor && $collid){
			$noteArr = $loanManager->getSpecimenDetails($loanID, $occid)
			?>
				<form name="noteEditor" action="outgoing.php" method="post" target="parentWin" onsubmit="submitNotesForm()">
					<div>
						<b><?php echo $LANG['DATE_RETURNED']; ?>:</b>
						<input name="returndate" type="datetime-local" value="<?php echo $noteArr['returnDate']; ?>" />
					</div>
					<div>
						<b><?php echo $LANG['SPEC_NOTES']; ?>:</b>
						<input name="notes" type="text" value="<?php echo $noteArr['notes']; ?>" style="width:100%" />
					</div>
					<div>
						<input name="loanid" type="hidden" value="<?php echo $loanID; ?>" />
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="tabindex" type="hidden" value="1" />
						<button name="formsubmit" type="submit" value="saveSpecimenDetails"><?php echo $LANG['SAVE_EDITS']; ?></button>
					</div>
				</form>
			<?php
		}
		else{
			if(!$isEditor) echo '<h2>' . $LANG['NOT_AUTH_LOANS'] . '</h2>';
			else echo '<h2>' . $LANG['UNKNOWN_ERROR'] . '</h2>';
		}
		?>
	</div>
</body>
</html>