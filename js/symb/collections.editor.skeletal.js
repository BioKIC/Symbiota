var sec = 0;
var count = 0;

$(document).ready(function() {

	$("#fsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		autoFocus: true,
		change: function(event, ui) {
			$( "#ftidinterpreted" ).val("");
			$( '#fscientificnameauthorship' ).val("");
			$( '#ffamily' ).val("");
			$( '#flocalitysecurity' ).prop('checked', false);
			if($( "#fsciname" ).val()){
				verifySciName();
			}
		}
	});

	$( "#fexstitle" ).autocomplete({
		source: "rpc/exsiccatisuggest.php",
		minLength: 2,
		autoFocus: true,
		select: function( event, ui ) {
			if(ui.item) $( "#fometid" ).val(ui.item.id);
			else $( "#fometid" ).val("");
		},
		change: function( event, ui ) {
			if(!ui.item){
				$( "#fometid" ).val("");
				if($( this ).val()) alert("Please select an exsiccate title from the list");
			} 
		}
	});

	//Initiate timer
	setInterval( function(){
		$("#seconds").html(pad(++sec%60));
		$("#minutes").html(pad(parseInt(sec/60,10)));
	}, 1000);
});

function showOptions(){
	$( "#optiondiv" ).toggle();
}

function hideOptions(){
	$( "#optiondiv" ).hide();
}

//Field changed and verification functions
function verifySciName(){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: $( "#fsciname" ).val() }
	}).done(function( data ) {
		if(data){
			$( "#ftidinterpreted" ).val(data.tid);
			$( '#ffamily' ).val(data.family);
			$( '#fscientificnameauthorship' ).val(data.author);
			if(data.status == 1){ 
				$( '#flocalitysecurity' ).prop('checked', true);
			}
			else{
				if(data.tid){
					var stateVal = $( '#fstateprovince' ).val();
					if(stateVal != ""){
						localitySecurityCheck($( "#faultform" ));
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
	var tidIn = $( "#ftidinterpreted" ).val();
	var stateIn = $( "#stateprovince" ).val();
	if(tidIn != "" && stateIn != ""){
		$.ajax({
			type: "POST",
			url: "rpc/localitysecuritycheck.php",
			dataType: "json",
			data: { tid: tidIn, state: stateIn }
		}).done(function( data ) {
			if(data == "1"){
				$( '#flocalitysecurity' ).prop('checked', true);
			}
		});
	}
}

function stateProvinceChanged(stateVal){ 
	var tidVal = $( "#ftidinterpreted" ).val();
	if(tidVal != "" && stateVal != ""){
		localitySecurityCheck($( "#faultform" ));
	}
}

function submitDefaultForm(f){
	var continueSubmit = true;
	if($("#feventdate").val() != ""){
		var dateStr = $("#feventdate").val();
		try{
			var validformat1 = /^\s*[<>]{0,1}\s{0,1}\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd, >yyyy-mm-dd, <yyyy-mm-dd
			if(!validformat1.test(dateStr)){
				alert("Event date must follow YYYY-MM-DD format. Note that 00 can be entered for a non-determined month or day.");
				continueSubmit = false;
			}
		}
		catch(ex){
		}
	}
	if($( "#fcatalognumber" ).val() == ""){
		alert("Catalog number field must have a value!");
		continueSubmit = false;
	}
	if($( "#fexstitle" ).val() && $( "#fometid" ).val() == ""){
		alert("Exsiccate title not matching item from list");
		continueSubmit = false;
	}
	if($( "#fometid" ).val() && !$( "#fexsnumber" ).val()){
		alert("Exsiccate title (#"+$( "#fometid" ).val()+") defined without a number. Please enter the exsiccate number");
		continueSubmit = false;
	}
	
	if(continueSubmit){
		/*
		url = 'rpc/occurAddData.php?sciname='+$( "#fsciname" ).val()+'&scientificnameauthorship='+$( "#fscientificnameauthorship" ).val()+'&family='+$( "#ffamily" ).val()+'&localitysecurity='+($( "#flocalitysecurity" ).prop('checked')?"1":"0");
		url = url + '&country='+$( "#fcountry" ).val()+'&stateprovince='+$( "#fstateprovince" ).val()+'&county='+$( "#fcounty" ).val();
		url = url + '&processingstatus='+$( "#fprocessingstatus" ).val()+'&recordedby='+$( "#frecordedby" ).val()+'&recordnumber='+$( "#frecordnumber" ).val(); 
		url = url + '&eventdate='+$( "#feventdate" ).val()+'&language='+$( "#flanguage" ).val()+'&ometid='+$( "#fometid" ).val()+'&exsnumber='+$( "#fexsnumber" ).val()+'&othercatalognumbers='+$( "#fothercatalognumbers" ).val();
		url = url + '&catalognumber='+$( "#fcatalognumber" ).val()+'&collid='+$( "#fcollid" ).val()+'&addaction='+$( "input[name=addaction]:checked" ).val();
		alert(url);
		*/

		//Add new occurrence 
		$.ajax({
			type: "POST",
			url: "rpc/occurAddData.php",
			dataType: "json",
			data: { 
				sciname: $( "#fsciname" ).val(), 
				scientificnameauthorship: $( "#fscientificnameauthorship" ).val(), 
				family: $( "#ffamily" ).val(), 
				localitysecurity: ($( "#flocalitysecurity" ).prop('checked')?"1":"0"),
				country: $( "#fcountry" ).val(), 
				stateprovince: $( "#fstateprovince" ).val(), 
				county: $( "#fcounty" ).val(), 
				processingstatus: $( "#fprocessingstatus" ).val(), 
				recordedby: $( "#frecordedby" ).val(), 
				recordnumber: $( "#frecordnumber" ).val(), 
				eventdate: $( "#feventdate" ).val(), 
				labelproject: $( "#flabelproject" ).val(), 
				language: $( "#flanguage" ).val(), 
				ometid: $( "#fometid" ).val(),
				exsnumber: $( "#fexsnumber" ).val(),
				othercatalognumbers: $( "#fothercatalognumbers" ).val(),
				catalognumber: $( "#fcatalognumber" ).val(),
				collid: $( "#fcollid" ).val(),
				addaction: $( "input[name=addaction]:checked" ).val()
			}
		}).done(function( retObj ) {
			if(retObj.status == "true"){
				var newDiv = createOccurDiv($( "#fcatalognumber" ).val(), retObj.occid, retObj.action);

				var listElem = document.getElementById("occurlistdiv");
				listElem.insertBefore(newDiv,listElem.childNodes[0]);

				incrementCount();
				catalognumber: $( "#fcatalognumber" ).val("");
				othercatalognumbers: $( "#fothercatalognumbers" ).val("");
			}
			else{
				if(retObj.error){
					if(retObj.error == 'dupeCatalogNumber'){
						if(confirm("Another record exists with the same catalog number, which is set as not allowed within options. Do you want to view the other record(s)?")){
							openEditPopup(retObj.occid);
						}
					}
					else{
						alert(retObj.error);
					}
				}
				else{
					alert('Failed: unknown error');
				}
			}
		});
	}
	
	$( "#fcatalognumber" ).focus();
	return false;
}

function resetForm(){
	$( "#fometid" ).val("");
}

function createOccurDiv(catalogNumber, occid, action){

	var newAnchor = document.createElement('a');
	newAnchor.setAttribute("id", "a-"+occid);
	newAnchor.setAttribute("href", "#");
	newAnchor.setAttribute("onclick", "openEditPopup("+occid+",false);return false;");
	var newText = document.createTextNode(catalogNumber);
	newAnchor.appendChild(newText);

	//Image submission  
	var newAnchor2 = document.createElement('a');
	newAnchor2.setAttribute("id", "a2-"+occid);
	newAnchor2.setAttribute("href", "#");
	newAnchor2.setAttribute("onclick", "openEditPopup("+occid+",true);return false;");
	var newImg = document.createElement('img');
	newImg.setAttribute("src", "../../images/jpg.png");
	newImg.setAttribute("style", "width:13px;margin-left:5px;");
	newAnchor2.appendChild(newImg);

	var newDiv = document.createElement('div');
	newDiv.setAttribute("id", "o-"+occid);
	newDiv.appendChild(newAnchor);
	newDiv.appendChild(newAnchor2);
	if(action == "update"){
		var newSpan = document.createElement("span");
		var spanText = document.createTextNode(" (update of existing record)");
		newSpan.appendChild(spanText);
		newDiv.appendChild(newSpan);
	}

	return newDiv;
}

function deleteOccurrence(occid){
	if(imgAssocCleared && voucherAssocCleared){
		var elem = document.getElementById("delapprovediv");
		elem.style.display = "block";
	}
}

function toggleFieldDiv(divName){
	toggle(divName);
	var allInputs = $("#"+divName+" > :input");
	allInputs.each(function(){
		if(this.type == "checkbox") $(this).prop("checked", false);
		else this.value = "";
	});
}

function eventDateChanged(eventDateInput){
	var dateStr = eventDateInput.value;
	if(dateStr == "") return true;

	var dateArr = parseDate(dateStr);
	if(dateArr['y'] == 0){
		alert("Unable to interpret Date. Please use the following formats: yyyy-mm-dd, mm/dd/yyyy, or dd mmm yyyy");
		return false;
	}
	else{
		//Check to see if date is in the future 
		try{
			var testDate = new Date(dateArr['y'],dateArr['m']-1,dateArr['d']);
			var today = new Date();
			if(testDate > today){
				alert("Was this plant really collected in the future? The date you entered has not happened yet. Please revise.");
				return false;
			}
		}
		catch(e){
		}

		//Invalid format is month > 12
		if(dateArr['m'] > 12){
			alert("Month cannot be greater than 12. Note that the format should be YYYY-MM-DD");
			return false;
		}

		//Check to see if day is valid
		if(dateArr['d'] > 28){
			if(dateArr['d'] > 31 
				|| (dateArr['d'] == 30 && dateArr['m'] == 2) 
				|| (dateArr['d'] == 31 && (dateArr['m'] == 4 || dateArr['m'] == 6 || dateArr['m'] == 9 || dateArr['m'] == 11))){
				alert("The Day (" + dateArr['d'] + ") is invalid for that month");
				return false;
			}
		}

		//Enter date into date fields
		var mStr = dateArr['m'];
		if(mStr.length == 1){
			mStr = "0" + mStr;
		}
		var dStr = dateArr['d'];
		if(dStr.length == 1){
			dStr = "0" + dStr;
		}
		eventDateInput.value = dateArr['y'] + "-" + mStr + "-" + dStr;
	}
	return true;
}

function parseDate(dateStr){
	var y = 0;
	var m = 0;
	var d = 0;
	try{
		var validformat1 = /^\d{4}-\d{1,2}-\d{1,2}$/; //Format: yyyy-mm-dd
		var validformat2 = /^\d{1,2}\/\d{1,2}\/\d{2,4}$/; //Format: mm/dd/yyyy
		var validformat3 = /^\d{1,2} \D+ \d{2,4}$/; //Format: dd mmm yyyy
		if(validformat1.test(dateStr)){
			var dateTokens = dateStr.split("-");
			y = dateTokens[0];
			m = dateTokens[1];
			d = dateTokens[2];
		}
		else if(validformat2.test(dateStr)){
			var dateTokens = dateStr.split("/");
			m = dateTokens[0];
			d = dateTokens[1];
			y = dateTokens[2];
			if(y.length == 2){
				if(y < 20){
					y = "20" + y;
				}
				else{
					y = "19" + y;
				}
			}
		}
		else if(validformat3.test(dateStr)){
			var dateTokens = dateStr.split(" ");
			d = dateTokens[0];
			mText = dateTokens[1];
			y = dateTokens[2];
			if(y.length == 2){
				if(y < 15){
					y = "20" + y;
				}
				else{
					y = "19" + y;
				}
			}
			mText = mText.substring(0,3);
			mText = mText.toLowerCase();
			var mNames = new Array("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");
			m = mNames.indexOf(mText)+1;
		}
		else if(dateObj instanceof Date && dateObj != "Invalid Date"){
			var dateObj = new Date(dateStr);
			y = dateObj.getFullYear();
			m = dateObj.getMonth() + 1;
			d = dateObj.getDate();
		}
	}
	catch(ex){
	}
	var retArr = new Array();
	retArr["y"] = y.toString();
	retArr["m"] = m.toString();
	retArr["d"] = d.toString();
	return retArr;
}

function openEditPopup(occidStr,targetImgTab){
	collid = $( "#fcollid" ).val();
	var urlStr = "occurrenceeditor.php?collid="+collid+"&q_catalognumber=occid"+occidStr+"&occindex=0";
	if(targetImgTab) urlStr = urlStr + '&tabtarget=2';
	var wWidth = 900;
	if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
	if(wWidth > 1200) wWidth = 1200;
	var newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if(newWindow != null){
		if (newWindow.opener == null) newWindow.opener = self;
	}
	else{
		alert("Unable to display record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
	return false;
}

//Misc functions
function isNumeric(sText){
   	var validChars = "0123456789-.";
   	var isNumber = true;
   	var charVar;

   	for(var i = 0; i < sText.length && isNumber == true; i++){ 
   		charVar = sText.charAt(i); 
		if(validChars.indexOf(charVar) == -1){
			isNumber = false;
			break;
	  	}
   	}
	return isNumber;
}

function pad( val ){ 
	return val > 9 ? val : "0" + val; 
}

function incrementCount(){
	$("#count").html(++count);
	$("#rate").html(Math.round(3660*count/sec));
}