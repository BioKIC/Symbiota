$(document).ready(function() {

	$("#sciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		change: function(event, ui) {
			$( "#tidinterpreted" ).val("");
			$( 'input[name=scientificnameauthorship]' ).val("");
			$( 'input[name=family]' ).val("");
			$( 'input[name=localitysecurity]' ).prop('checked', false);
			if($( "#sciname" ).val()){
				verifySciName();
			}
			else{
				$( "#tidinterpreted" ).val("");
				$( 'input[name=scientificnameauthorship]' ).val("");
				$( 'input[name=family]' ).val("");
				$( 'input[name=localitysecurity]' ).prop('checked', false);
			}
		}
	});

	$("#catalognumber").keydown(function(evt){
		var evt  = (evt) ? evt : ((event) ? event : null);
		if ((evt.keyCode == 13)) { return false; }
	});
});

//Field changed and verification functions
function verifySciName(){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: $( "#sciname" ).val() }
	}).done(function( data ) {
		if(data){
			$( "#tidinterpreted" ).val(data.tid);
			$( 'input[name=family]' ).val(data.family);
			$( 'input[name=scientificnameauthorship]' ).val(data.author);
			if(data.status == 1){ 
				$( 'input[name=localitysecurity]' ).prop('checked', true);
			}
			else{
				if(data.tid){
					var stateVal = $( 'input[name=stateprovince]' ).val();
					if(stateVal != ""){
						localitySecurityCheck($( "#imgoccurform" ));
					}
				}
			}
		}
		else{
            alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus by a taxonomic editor.");
		}
	});
}

function localitySecurityCheck(f){
	var tidIn = f.tidinterpreted.value;
	var stateIn = f.stateprovince.value;
	if(tidIn != "" && stateIn != ""){
		$.ajax({
			type: "POST",
			url: "rpc/localitysecuritycheck.php",
			dataType: "json",
			data: { tid: tidIn, state: stateIn }
		}).done(function( data ) {
			if(data == "1"){
				$( 'input[name=localitysecurity]' ).prop('checked', true);
			}
		});
	}
}

//Validate forms
function validateImgOccurForm(f){
	if(f.imgurl.value == "" && f.imgfile.value == ""){
		alert("Local image must be select or a image URL entered");
		return false;
	}
	
	return true;
}

//Misc
function dwcDoc(dcTag){
	dwcWindow=open("https://biokic.github.io/symbiota-docs/editor/edit/fields/#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	//dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	if(dwcWindow.opener == null) dwcWindow.opener = self;
	dwcWindow.focus();
	return false;
}