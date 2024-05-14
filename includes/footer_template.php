<footer>
	<dialog id="accessibility-modal" class="accessibility-dialog" aria-label="<?= $LANG['ACCESSIBILITY_OPTIONS']; ?>">
		<h1><?= $LANG['ACCESSIBILITY_OPTIONS']; ?></h1>
		<p class="bottom-breathing-room-rel"><?= $LANG['ACCESSIBILITY_OPTIONS_DESCRIPTION']; ?></p>
		<button type="button" class="btn btn-primary bottom-breathing-room-rel" onclick="toggleAccessibilityStyles('<?php echo $CLIENT_ROOT . '/includes' . '/' ?>', '<?php echo $CSS_BASE_PATH ?>', '<?php echo $LANG['TOGGLE_508_OFF'] ?>', '<?php echo $LANG['TOGGLE_508_ON'] ?>')" id="accessibility-button" data-accessibility="accessibility-button">
			<?php echo (isset($LANG['TOGGLE_508_ON'])?$LANG['TOGGLE_508_ON']:'Switch Form Layout'); ?>
		</button>
		<form method="dialog">
			<button type="submit" class="btn btn-primary"><?= $LANG['CLOSE']; ?></button>
		</form>
	</dialog>
	<div class="logo-gallery">
		<button id="accessibility-options-button" type="button" class="btn btn-primary  accessibility-option-button">
			<span class="button__item-container">
				<span class="button__item-container__item-text">
					<?= $LANG['ACCESSIBILITY_OPTIONS']; ?>
				</span>
				<span>
					<img alt="accessibility icon of a person" src="<?php echo $CLIENT_ROOT ?>/images/accessibility_FILL0_wght400_GRAD0_opsz24.svg" />
				</span>
	        </span>
		</button>
		<a href="https://www.nsf.gov" target="_blank" aria-label="Visit National Science Foundation website">
			<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/logo_nsf.gif" alt="Logo for the National Science Foundation" />
		</a>
		<a href="http://idigbio.org" target="_blank" title="iDigBio" aria-label="Visit iDigBio website">
			<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/logo_idig.png" alt="Logo for iDigBio, or, Integrated Digitized Biocollections" />
		</a>
		<a href="https://biokic.asu.edu" target="_blank" title="Biodiversity Knowledge Integration Center" aria-label="Visit BioKIC website">
			<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/logo-asu-biokic.png"  alt="Logo for the Biodiversity Knowledge Integration Center" />
		</a>
	</div>
	<p>
		This project made possible by National Science Foundation Awards <a href="https://www.nsf.gov/awardsearch/showAward?AWD_ID=" target="_blank">#------</a>
		.
	</p>
	<p>
		For more information about Symbiota, <a href="https://symbiota.org/docs" target="_blank" rel="noopener noreferrer">read the docs</a> or contact the <a href="https://symbiota.org/contact-the-support-hub/" target="_blank" rel="noopener noreferrer">Symbiota Support Hub</a>
			.
	</p>
	<p>
		Powered by <a href="https://symbiota.org/" target="_blank">Symbiota</a>
		.
	</p>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', ()=>{
			document.getElementById('accessibility-button').disabled=false;
			updateButtonTextBasedOnEnabledStylesheet('<?php echo $LANG['TOGGLE_508_OFF'] ?>', '<?php echo $LANG['TOGGLE_508_ON'] ?>');
		});

		const openDialogButton = document.getElementById('accessibility-options-button');
		const accessibilityDialog = document.getElementById('accessibility-modal');

		openDialogButton.addEventListener('click', function() {
			accessibilityDialog.showModal();
		});
	</script>
</footer>