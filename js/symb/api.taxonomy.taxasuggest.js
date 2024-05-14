var acUrlBase = "/rpc/taxasuggest.php";
var acUrl = acUrlBase;

function initTaxaSuggest() {

	if(typeof clientRoot !== 'undefined') acUrl = clientRoot + acUrlBase;
	else{
		var dirArr = window.location.pathname.split('/');
		dirArr.shift(); dirArr.pop();
		var loopCnt = 0;
		if(urlExists("/portal"+acUrlBase)) acUrl = "/portal"+acUrlBase;
		else{
			while(!urlExists(acUrl) && dirArr.length > loopCnt){
				var newUrl = '';
				for(i = 0; i <= loopCnt; i++){
					newUrl = newUrl + "/" + dirArr[i];
				}
				acUrl = newUrl + acUrlBase;
				loopCnt = loopCnt + 1;
			}
		}
	}
	
	function extractLast( term ) {
		return term.split( /,\s*/ ).pop();
	}

	$( "#taxa" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			// don't honor ENTER key if an autocomplete is not selected yet
			/*
			if (event.keyCode === $.ui.keyCode.ENTER) {
				if (this.autocomplete_stage != 0) {
				    event.preventDefault();
				}
		    } else
		    */
			// don't navigate away from the field on tab when selecting an item
			if (event.keyCode === $.ui.keyCode.TAB) {
				if ($(this).autocomplete('widget').is(':visible')) {
					$(this).trigger("select");
					event.preventDefault();
				}
			} else {
				this.autocomplete_stage = 1;
			}
		})
		.autocomplete({
			source: $.proxy(function( request, response ) {
				$.getJSON( acUrl, {
					term: extractLast( request.term ), t: function() { return $("#taxontype").val(); }
				}, response );
				this.autocomplete_stage = 0;
			},$('#taxa')[0]),
			autoFocus: true,
			search: function() {
				// custom minLength
				this.autocomplete_stage = 2;
				var term = extractLast( this.value );
				if ( term.length < 4 ) {
					return false;
				}
				this.autocomplete_stage = 3;
				return true;
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = this.value.split( /,\s*/ );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				this.value = terms.join( ", " );
				return false;
			}
		},{});
}

$(document).ready(initTaxaSuggest);

function initiateTaxonSuggest(inputID, rLow, rHigh){
	$( "#"+inputID )
		.autocomplete({
			source: function( request, response ) {
				$.getJSON( acUrl, { term: request.term, ranklow: rLow, rankhigh: rHigh }, response );
			},
			autoFocus: true
		},{});
}

function urlExists(url){
    var http = new XMLHttpRequest();
    http.open('HEAD', url, false);
    http.send();
    return http.status!=404;
}

function verifyQuickSearch(f){
	if(f.taxa.value == ""){
		alert("Scientific name?");
		return false;
	}
	return true;
}

function validateTaxon(f,submitForm){ 
	if(f.taxa.value == ""){
		//alert("Enter a scientific name");
		return false;
	}
	else{
		$.ajax({
			type: "POST",
			url: "../rpc/gettaxon.php",
			dataType: "json",
			data: { sciname: f.taxa.value }
		}).done(function( taxaObj ) {
			//alert(JSON.stringify(taxaObj));
			//alert(Object.keys(taxaObj).length)
			var retCnt = Object.keys(taxaObj).length;
			if(retCnt == 0){
				alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, contact your data administrator to add this species to the Taxonomic Thesaurus.");
			}
			else{
				if(retCnt == 1){
					f.tid.value = Object.keys(taxaObj)[0];
					if(submitForm) f.submit();
				}
				else{
					f.tid.value = Object.keys(taxaObj)[0];
					if(submitForm) f.submit();
					//alert(Object.keys(taxaObj)[0]);
					//alert(Object.keys(taxaObj)[1]);
				}
			}
		});
		return false;
	}
}
