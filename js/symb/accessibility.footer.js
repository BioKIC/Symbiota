document.addEventListener('DOMContentLoaded', ()=>{
	document.getElementById('accessibility-button').disabled=false;
	updateButtonTextBasedOnEnabledStylesheet(toggleOff508, toggleOn508);
});

const openDialogButton = document.getElementById('accessibility-options-button');
const accessibilityDialog = document.getElementById('accessibility-modal');

openDialogButton.addEventListener('click', function() {
	accessibilityDialog.showModal();
});
