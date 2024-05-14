<?php
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
?>

<form name="reportsform" onsubmit="return ProcessReport();" method="post" target="_blank">
	<fieldset>
		<legend><?php echo $LANG['GENERATE_LOAN_PAPERWORK']; ?></legend>
		<div style="float:right;">
			<b><?php echo $LANG['MAILING_ACCT_NO']; ?>:</b> <input type="text" autocomplete="off" name="mailaccnum" maxlength="32" style="width:100px;" value="" />
		</div>
		<div style="padding-bottom:2px;">
			<b><?php echo $LANG['PRINT_METHOD']; ?>:</b>
			<input type="radio" name="outputmode" id="printbrowser" value="browser" checked /> <?php echo $LANG['PRINT_BROWSER']; ?>
			<input type="radio" name="outputmode" id="printdoc" value="doc" /> <?php echo $LANG['EXPORT_TO_DOC']; ?>
		</div>
		<div style="padding-bottom:8px;">
			<b>Invoice Language:</b> <input type="radio" name="languagedef" value="0" checked /> <?php echo $LANG['ENGLISH']; ?>
			<input type="radio" name="languagedef" value="1" /> <?php echo $LANG['ENG_SPN']; ?>
			<input type="radio" name="languagedef" value="2" /> <?php echo $LANG['SPANISH']; ?>
		</div>
		<input name="loantype" type="hidden" value="<?php echo $loanType; ?>" />
		<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
		<input name="identifier" type="hidden" value="<?php echo $identifier; ?>" />
		<button name="formsubmit" type="submit" onclick="this.form.action ='reports/defaultinvoice.php'" value="invoice"><?php echo $LANG['INVOICE']; ?></button>
		<?php
		if(isset($specimenTotal) && $specimenTotal){
			?>
			<button name="formsubmit" type="submit" onclick="this.form.action ='reports/defaultspecimenlist.php'" value="spec"><?php echo $LANG['SPEC_LIST']; ?></button>
			<?php
		}
		?>
		<button name="formsubmit" type="submit" onclick="this.form.action ='reports/defaultmailinglabel.php'" value="label"><?php echo $LANG['MAILING_LABEL']; ?></button>
		<button name="formsubmit" type="submit" onclick="this.form.action ='reports/defaultenvelope.php'" value="envelope"><?php echo $LANG['ENVELOPE']; ?></button>
	</fieldset>
</form>