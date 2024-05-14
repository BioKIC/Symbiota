$(document).ready(function() {

	$("input[name=country]").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/getGeography.php", { term: request.term }, response );
		},
		minLength: 1,
		autoFocus: true,
		change: function(event, ui){
			if(typeof fieldChanged === "function") fieldChanged("country");
		}
	});

	$("input[name=stateprovince]").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/getGeography.php", { term: request.term, target: "state", parentTerm: $('input[name="country"]').val() }, response );
		},
		minLength: 1,
		autoFocus: true,
		change: function(event, ui){
			if(typeof fieldChanged === "function") fieldChanged("stateprovince");
		}
	});

	$("input[name=county]").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/getGeography.php", { term: request.term, target: "county", parentTerm: $('input[name="stateprovince"]').val() }, response );
		},
		minLength: 1,
		autoFocus: true,
		change: function(event, ui){
			if(typeof fieldChanged === "function") fieldChanged("county");
		}
	});

	$("input[name=municipality]").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/getGeography.php", { term: request.term, target: "municipality", parentTerm: $('input[name="stateprovince"]').val() }, response );
		},
		minLength: 1,
		autoFocus: true,
		change: function(event, ui){
			if(typeof fieldChanged === "function") fieldChanged("municipality");
		}
	});
});