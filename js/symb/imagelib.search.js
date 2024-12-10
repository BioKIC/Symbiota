function showCollections(){
	$("#display-collections-span").hide();
	$("#hide-collections-span").show();
	$("#collection-div").show();
}

function hideCollections(){
	$("#display-collections-span").show();
	$("#hide-collections-span").hide();
	$("#collection-div").hide();
}

function activateCollectionControl(){
	$("#collections-control-span").show();
	$("input[name='db[]']").prop('checked', true);
	$("input[name='cat[]']").prop('checked', true);
}

function deactivateCollectionControl(){
	$("#collections-control-span").hide();
	$("#collection-div").hide();
	$("input[name='db[]']").prop('checked', false);
	$("input[name='cat[]']").prop('checked', false);
}

function openIndPU(occId,clid){
	openPopup("../collections/individual/index.php?occid="+occId, "indspec" + occId);
	return false;
}

function openTaxonPopup(tid){
	openPopup("../taxa/index.php?taxon="+tid, 'taxon'+tid);
	return false;
}

function openImagePopup(imageId){
	openPopup("imgdetails.php?mediaid="+imageId, 'image'+imageId);
	return false;
}

function openPopup(url,nameStr){
	var wWidth = 1100;
	if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.95;
	if(wWidth > 1200) wWidth = 1200;
	newWindow = window.open(url,nameStr,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}