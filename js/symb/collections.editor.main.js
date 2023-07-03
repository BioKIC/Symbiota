var imgAssocCleared = false;
var voucherAssocCleared = false;

$(document).ready(function() {
	
	var editForm = document.fullform;
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	if(/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){
		var ffversion=new Number(RegExp.$1);
		if(ffversion < 7 ) alert("You are using an older version of Firefox. For best results, we recommend that you update your browser.");
	}
	
	$("form#fullform :input").on('input', function() {
		var skipFields = ["carryover","assocrelation","targetcollid","clonecount"];
		if(jQuery.inArray( $(this).attr('name'), skipFields ) == -1){
			$("button").prop("disabled",false);
		}
	});
	
	$("#occedittabs").tabs({
		select: function(event, ui) {
			if(verifyLeaveForm()){
				editForm.submitaction.disabled = true;
			}
			else{
				return false;
			}
			var statusObj = document.getElementById("statusdiv");
			if(statusObj){
				statusObj.style.display = "none";
			}
			return true;
		},
		active: tabTarget,
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});

	$( "#exstitleinput" ).autocomplete({
		source: "rpc/exsiccatisuggest.php",
		minLength: 2,
		autoFocus: true,
		select: function( event, ui ) {
			if(ui.item){
				$( "#ometidinput" ).val(ui.item.id);
				fieldChanged('ometid');
			}
			else{
				$( "#ometidinput" ).val("");
				fieldChanged('ometid');
			}
		},
		change: function( event, ui ) {
			if($( this ).val() == ""){
				$( "#ometidinput" ).val("");
			}
			else{
				if($( "#ometidinput" ).val() == ""){
					$.ajax({
						type: "POST",
						url: "rpc/exsiccativalidation.php",
						data: { term: $( this ).val() }
					}).done(function( msg ) {
						if(msg == ""){
							alert("Exsiccati title not found within system");
						}
						else{
							$( "#ometidinput" ).val(msg);
							fieldChanged('ometid');
						}
					});
				}
			}
		}
	});

	$("#ffsciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		autoFocus: true,
		change: function(event, ui) {
			$( "#tidinterpreted" ).val("");
			$( 'input[name=scientificnameauthorship]' ).val("");
			$( 'input[name=family]' ).val("");
			$( 'input[name=localitysecurity]' ).prop('checked', false);
			fieldChanged('sciname');
			fieldChanged('tidinterpreted');
			fieldChanged('scientificnameauthorship');
			fieldChanged('family');
			fieldChanged('localitysecurity');
			if($( "#ffsciname" ).val()){
				verifyFullFormSciName();
			}
		}
	});

	var cookies = document.cookie
	if(cookies.indexOf("localauto") > -1){
		var cookieName = "localauto=";
		var ca = document.cookie.split(';');
		for(var i = 0; i <ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') {
				c = c.substring(1);
			}
			if(c.indexOf(cookieName) == 0) {
				if(c.substring(cookieName.length) == "1") $( 'input[name=localautodeactivated]' ).prop('checked', true);
			}
		}
	}

	if(localityAutoLookup){
		$("#fflocality").autocomplete({ 
			source: function( request, response ) {
				$.ajax( {
					url: "rpc/getlocality.php",
					data: { 
						recordedby: $( "input[name=recordedby]" ).val(), 
						eventdate: $( "input[name=eventdate]" ).val(), 
						locality: request.term 
					},
					success: function( data ) {
						response( data );
					}
				});
			},
			minLength: 4,
			select: function( event, ui ) {
				$.each(ui.item, function(k, v) {
					var elem = $( "input[name="+k+"]" );
					if(!elem.length) elem = $( "textarea[name="+k+"]" );
					if(elem.val() == ""){
						elem.val(v);
						elem.css("backgroundColor","lightblue");
						fieldChanged(k);
					}
				});
				ui.item.value = ui.item.locality; 
			}
		});
		if($( "input[name=localautodeactivated]" ).is(':checked')){
			$( "#fflocality" ).autocomplete( "option", "disabled", true );
			$( "#fflocality" ).attr('autocomplete','on');
		}
	}

	$("#locationid").autocomplete({ 
		source: function( request, response ) {
			$.ajax( {
				url: "rpc/getlocality.php",
				data: { locationid: request.term },
				success: function( data ) {
					response( data );
				}
			});
		},
		minLength: 3,
		select: function( event, ui ) {
			event.preventDefault();
			$.each(ui.item, function(k, v) {
				var elem = $( "input[name="+k+"]" );
				if(!elem.length) elem = $( "textarea[name="+k+"]" );
				if(elem.val() == ""){
					elem.val(v);
					elem.css("backgroundColor","lightblue");
					fieldChanged(k);
				}
			});
			let baseValue = ui.item.value;
			baseValue = baseValue.substring(0,baseValue.indexOf(" || "));
			this.value = baseValue;
		}
	});

	//Misc fields with lookups
	$("#ffcountry").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/lookupCountry.php", { term: request.term }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("country");
		}
	});

	$("#ffstate").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/lookupState.php", { term: request.term, "country": editForm.country.value }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("stateprovince");
		}
	});

	$("#ffcounty").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/lookupCounty.php", { term: request.term, "state": editForm.stateprovince.value }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("county");
		}
	});

	$("#ffmunicipality").autocomplete({ 
		source: function( request, response ) {
			$.getJSON( "rpc/lookupMunicipality.php", { term: request.term, "state": editForm.stateprovince.value }, response );
		},
		minLength: 2,
		autoFocus: true,
		change: function(event, ui){
			fieldChanged("municipality");
		}
	});
	
	$("textarea[name=associatedtaxa]").autocomplete({
		source: function( request, response ) {
			$.getJSON( "rpc/getspeciessuggest.php", { term: extractLast( request.term ) }, response );
		},
		search: function() {
			// custom minLength
			var term = extractLast( this.value );
			if ( term.length < 4 ) return false;
		},
		focus: function() {
			// prevent value inserted on focus
			return false;
		},
		select: function( event, ui ) {
			var terms = split( this.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			this.value = terms.join( ", " );
			return false;
		}
	},{autoFocus: true});


	$("#catalognumber").keydown(function(evt){
		var evt  = (evt) ? evt : ((event) ? event : null);
		if(evt.keyCode == 13) return false;
	});
	
	if(document.getElementById('hostDiv')){
		$("#quickhost").autocomplete({
			source: function( request, response ) {
				var name = request.term.replace(" ","+");
				$.getJSON( "rpc/getcolspeciessuggest.php", { term: name }, response );
			},
			minLength: 4,
			autoFocus: true,
			change: function(event, ui){
				fieldChanged("host");
			}
		});
	}
	
	//Remember Auto Processing Status
	var apstatus = getCookie("autopstatus");
	if(getCookie("autopstatus")){
		editForm.autoprocessingstatus.value = apstatus;
		if(editForm.occid.value == 0) editForm.processingstatus.value = apstatus;
	}
	//Remember Auto Duplicate search status 
	if(getCookie("autodupe") == 1) editForm.autodupe.checked = true; 
});

//Field changed and verification functions
function verifyFullFormSciName(){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: $( "#ffsciname" ).val() }
	}).done(function( data ) {
		if(data){
			$( "#tidinterpreted" ).val(data.tid);
			$( 'input[name=family]' ).val(data.family);
			$( 'input[name=scientificnameauthorship]' ).val(data.author);
			/*
			if(data.rankid < 220){
				$( 'select[name=confidenceranking]' ).val(2);
			}
			else{
				$( 'select[name=confidenceranking]' ).val(8);
			}
			*/
			if(data.status == 1){
				$( 'input[name=localitysecurity]' ).prop('checked', true);
			}
			else{
				if(data.tid){
					var stateVal = $( 'input[name=stateprovince]' ).val();
					if(stateVal != ""){
						localitySecurityCheck();
					}
				}
			}
		}
		else{
			$( 'select[name=confidenceranking]' ).val(5);
			alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus by a taxonomic editor. You can continue entering this specimen using this name and the name will be resolved at a later date.");
		}
	});
}

function addIdentifierField(clickedObj){
	$(clickedObj).hide();
	var identDiv = document.getElementById("identifierBody");
	var insertHtml = '<div class="divTableRow"><div class="divTableCell"><input name="idkey[]" type="hidden" value="newidentifier" /><input name="idname[]" type="text" value="" onchange="fieldChanged(\'idname\');" autocomplete="off" /></div><div class="divTableCell"><input name="idvalue[]" type="text" value="" onchange="fieldChanged(\'idvalue\');searchOtherCatalogNumbers(this.form);" autocomplete="off" /><a href="#" onclick="addIdentifierField(this);return false"><img src="../../images/plus.png" /></a></div></div>';
	identDiv.insertAdjacentHTML('beforeend', insertHtml);
}

function deleteIdentifier(identID, occid){
	if(identID != ""){
		//alert("rpc/deleteIdentifier.php?identifierID="+identID+"&occid="+occid);
		$.ajax({
			type: "POST",
			url: "rpc/deleteIdentifier.php",
			dataType: "json",
			data: { identifierID: identID, occid: occid }
		}).done(function( response ) {
			if(response == 1) $("#idRow-"+identID).remove()
			//else alert("Error deleting identifier");
		});
	}
}

function localitySecurityCheck(){
	var tidIn = $( "input[name=tidinterpreted]" ).val();
	var stateIn = $( "input[name=stateprovince]" ).val();
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

function localAutoChanged(cbObj){
	if(cbObj.checked == true){
		$( "#fflocality" ).autocomplete( "option", "disabled", true );
		$( "#fflocality" ).attr('autocomplete','on');
		document.cookie = "localauto=1";
	}
	else{
		$( "#fflocality" ).autocomplete( "option", "disabled", false );
		$( "#fflocality" ).attr('autocomplete','off');
		document.cookie = "localauto=;expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	}
}

function fieldChanged(fieldName){
	try{
		document.fullform.editedfields.value = document.fullform.editedfields.value + fieldName + ";";
	}
	catch(ex){
	}
}

function recordNumberChanged(){
	fieldChanged('recordnumber');
	autoDupeSearch();
}

function stateProvinceChanged(stateVal){ 
	fieldChanged('stateprovince');
	var tidVal = $( "#tidinterpreted" ).val();
	if(tidVal != "" && stateVal != ""){
		localitySecurityCheck();
	}
}

function decimalLatitudeChanged(f){
	verifyDecimalLatitude(f);
	//verifyCoordinates(f);
	fieldChanged('decimallatitude');
}

function decimalLongitudeChanged(f){
	verifyDecimalLongitude(f);
	//verifyCoordinates(f);
	fieldChanged('decimallongitude');
}

function coordinateUncertaintyInMetersChanged(f){
	if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
		alert("Coordinate uncertainty field must be numeric only");
	}
	fieldChanged('coordinateuncertaintyinmeters');
}

function footPrintWktChanged(formObj){
	fieldChanged('footprintwkt');
	formObj.value = validatePolygon(formObj.value);
	if(formObj.value.length > 65000){
		formObj.value = "";
		alert("WKT footprint is too large to save in the database");
	}
}

function minimumElevationInMetersChanged(f){
	verifyMinimumElevationInMeters(f);
	fieldChanged('minimumelevationinmeters');
}

function maximumElevationInMetersChanged(f){
	verifyMaximumElevationInMeters(f);
	fieldChanged('maximumelevationinmeters');
}

function verbatimElevationChanged(f){
	if(!f.minimumelevationinmeters.value){
		parseVerbatimElevation(f);
	}
	fieldChanged("verbatimelevation");
}

function parseVerbatimElevation(f){
	if(f.verbatimelevation.value){
		var min = "";
		var max = "";
		var verbElevStr = f.verbatimelevation.value;
		verbElevStr = verbElevStr.replace(/,/g ,"");
		
		var regEx1 = /(\d+)\s*-\s*(\d+)\s*[ft|feet|']/i; 
		var regEx2 = /(\d+)\s*[ft|feet|']/i; 
		var regEx3 = /(\d+)\s*-\s*(\d+)\s{0,1}m{1}/i; 
		var regEx4 = /(\d+)\s{0,1}-\s{0,1}(\d+)\s{0,1}m{1}/i; 
		var regEx5 = /(\d+)\s{0,1}m{1}/i; 
		var extractStr = "";
		if(extractArr = regEx1.exec(verbElevStr)){
			min = Math.round(extractArr[1]*.3048);
			max = Math.round(extractArr[2]*.3048);
		}
		else if(extractArr = regEx2.exec(verbElevStr)){
			min = Math.round(extractArr[1]*.3048);
		}
		else if(extractArr = regEx3.exec(verbElevStr)){
			min = extractArr[1];
			max = extractArr[2];
		}
		else if(extractArr = regEx4.exec(verbElevStr)){
			min = extractArr[1];
			max = extractArr[2];
		}
		else if(extractArr = regEx5.exec(verbElevStr)){
			min = extractArr[1];
		}

		if(min){
			f.minimumelevationinmeters.value = min;
			fieldChanged("minimumelevationinmeters");
			if(max){
				f.maximumelevationinmeters.value = max;
				fieldChanged("maximumelevationinmeters");
			}
		}
	}
}

function minimumDepthInMetersChanged(f){
	if(!isNumeric(f.minimumdepthinmeters.value)){
		alert("Depth values must be numeric only");
		return false;
	}
	fieldChanged('minimumdepthinmeters');
}

function maximumDepthInMetersChanged(f){
	if(!isNumeric(f.maximumdepthinmeters.value)){
		alert("Depth values must be numeric only");
		return false;
	}
	fieldChanged('maximumdepthinmeters');
}

function verbatimCoordinatesChanged(f){
	if(!f.decimallatitude.value){
		parseVerbatimCoordinates(f,0);
	}
	fieldChanged("verbatimcoordinates");
}

function parseVerbatimCoordinates(f,verbose){
	if(f.verbatimcoordinates.value){
		var latDec = null;
		var lngDec = null;
		var verbCoordStr = f.verbatimcoordinates.value;
		verbCoordStr = verbCoordStr.replace(/’/g,"'");
		
		var tokenArr = verbCoordStr.split(" ");
		
		var z = null;
		var e = null;
		var n = null;
		var zoneEx = /^\D{0,5}(\d{1,2}[A-Z]{0,1})\D*$/i;
		var eEx1 = /^(\d{6,7})m{0,1}E/i;
		var nEx1 = /^(\d{7})m{0,1}N/i;
		var eEx2 = /^E(\d{6,7})\D*$/i;
		var nEx2 = /^N(\d{4,7})\D*$/i;
		var eEx3 = /^0{0,1}(\d{6})\D*$/i;
		var nEx3 = /^(\d{7})\D*$/i;
		for(var i = 0; i < tokenArr.length; i++) {
			if(extractArr = zoneEx.exec(tokenArr[i])){
				z = extractArr[1];
			}
			else if(extractArr = eEx1.exec(tokenArr[i])){
				e = extractArr[1];
			}
			else if(extractArr = nEx1.exec(tokenArr[i])){
				n = extractArr[1];
			}
			else if(extractArr = eEx2.exec(tokenArr[i])){
				e = extractArr[1];
			}
			else if(extractArr = nEx2.exec(tokenArr[i])){
				n = extractArr[1];
			}
			else if(extractArr = eEx3.exec(tokenArr[i])){
				e = extractArr[1];
			}
			else if(extractArr = nEx3.exec(tokenArr[i])){
				n = extractArr[1];
			}
		}
		
		if(z && e && n){
			var llStr = utm2LatLng(z, e, n, f.geodeticdatum.value, null);
			if(llStr){
				var llArr = llStr.split(",");
				if(llArr.length == 2){
					latDec = llArr[0];
					lngDec = llArr[1];
				}
			}
		}
		//Check to see if there are embedded lat/lng
		if(!latDec || !lngDec){
			var llEx1 = /([NSEW]{0,1})(\d{1,2})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*(\d{1,2}\.{0,1}\d*)['"]{1,2}\s*([NSEW]{0,1})[\D\s]*(\d{1,3})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*(\d{1,2}\.{0,1}\d*)['"]{1,2}\s*([NSEW]{0,1})/i 
			var llEx2 = /([NSEW]{0,1})(\d{1,2})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*([NSEW]{0,1})\D*\s*(\d{1,3})[\D\s]{1,2}(\d{1,2}\.{0,1}\d*)['m]{1}\s*([NSEW]{0,1})/i 
			var llEx3 = /([NSEW]{0,1})([-]{0,1}\d{1,2}\.\d+)[\s]{0,1}([NSEW]{0,1})[\s]+([-]{0,1}\d{1,3}\.\d+)[\s]{0,1}([NSEW]{0,1})/i 
			if(extractArr = llEx1.exec(verbCoordStr)){
				var latDeg = parseInt(extractArr[2]);
				var latMin = parseInt(extractArr[3]);
				var latSec = parseFloat(extractArr[4]);
				if(latDeg > 90){
					alert("Latitude degrees cannot be greater than 90");
					return '';
				}
				if(latMin > 60){
					alert("Latitude minutes cannot be greater than 60");
					return '';
				}
				if(latSec > 60){
					alert("Latitude seconds cannot be greater than 60");
					return '';
				}
				var lngDeg = parseInt(extractArr[6]);
				var lngMin = parseInt(extractArr[7]);
				var lngSec = parseFloat(extractArr[8]);
				if(lngDeg > 180){
					alert("Longitude degrees cannot be greater than 180");
					return '';
				}
				if(lngMin > 60){
					alert("Longitude minutes cannot be greater than 60");
					return '';
				}
				if(lngSec > 60){
					alert("Longitude seconds cannot be greater than 60");
					return '';
				}
				//Convert to decimal format
				latDec = latDeg+(latMin/60)+(latSec/3600);
				lngDec = lngDeg+(lngMin/60)+(lngSec/3600);
				var dir1 = extractArr[1].toUpperCase();
				var latNS = extractArr[5].toUpperCase();
				var lngEW = extractArr[9].toUpperCase();
				if(dir1 && !lngEW){
					lngEW = latNS;
					latNS = dir1;
				}
				if((latNS == 'E' || latNS == 'W') && (lngEW == 'N' || lngEW == 'S')){
					var latTemp = lngDec;
					lngDec = latDec;
					latDec = latTemp;
					var tempHemi = lngEW;
					lngEW = latNS;
					latNS = tempHemi;
				}
				if(latNS == "S" && latDec > 0) latDec = latDec*-1;
				if(lngDec > 0 && lngEW != "E") lngDec = lngDec*-1;
			}
			else if(extractArr = llEx2.exec(verbCoordStr)){
				var latDeg = parseInt(extractArr[2]);
				var latMin = parseFloat(extractArr[3]);
				if(latDeg > 90){
					alert("Latitude degrees cannot be greater than 90");
					return '';
				}
				if(latMin > 60){
					alert("Latitude minutes cannot be greater than 60");
					return '';
				}
				var lngDeg = parseInt(extractArr[5]);
				var lngMin = parseFloat(extractArr[6]);
				if(lngDeg > 180){
					alert("Longitude degrees cannot be greater than 180");
					return '';
				}
				if(lngMin > 60){
					alert("Longitude minutes cannot be greater than 60");
					return '';
				}
				//Convert to decimal format
				latDec = latDeg+(latMin/60);
				lngDec = lngDeg+(lngMin/60);
				var dir1 = extractArr[1].toUpperCase();
				var latNS = extractArr[4].toUpperCase();
				var lngEW = extractArr[7].toUpperCase();
				if(dir1 && !lngEW){
					lngEW = latNS;
					latNS = dir1;
				}
				if((latNS == 'E' || latNS == 'W') && (lngEW == 'N' || lngEW == 'S')){
					var latTemp = lngDec;
					lngDec = latDec;
					latDec = latTemp;
					var tempHemi = lngEW;
					lngEW = latNS;
					latNS = tempHemi;
				}
				if(latNS == "S" && latDec > 0) latDec = latDec*-1;
				if(lngDec > 0 && lngEW != "E") lngDec = lngDec*-1;
			}
			else if(extractArr = llEx3.exec(verbCoordStr)){
				var latDec = parseFloat(extractArr[2]);
				var lngDec = parseFloat(extractArr[4]);

				var dir1 = extractArr[1].toUpperCase();
				var latNS = extractArr[3].toUpperCase();
				var lngEW = extractArr[5].toUpperCase();
				if((latNS == "E" || latNS == "W") && (lngEW == "N" || lngEW == "S")){
					var tempDec = lngDec;
					lngDec = latDec;
					latDec = tempDec;
					var tempHemi = lngEW;
					lngEW = latNS;
					latNS = tempHemi;
				}
				if(latNS != ""){
					if(latNS != "N" && latNS != "S" && lngEW != "E" && lngEW != "W"){
						alert("Invalid hemisphere designations (allowed: N, S, E, W");
						return '';
					}
				}
				if(latDec > 0 && latNS == "S") latDec = -1*latDec;
				if(lngDec > 0 && lngEW == "W") lngDec = -1*lngDec;
				if(latDec > 90 || latDec < -90){
					alert("Latitude must be between -90 and 90");
					return '';
				}
				if(lngDec > 180 || lngDec < -180){
					alert("Longitude must be between -180 and 180");
					return '';
				}
			}
		}

		if(latDec && lngDec){
			f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
			f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
			decimalLatitudeChanged(f);
			decimalLongitudeChanged(f);
		}
		else{
			if(verbose) alert("Unable to parse coordinates");
		}
	}
}

//Form verification code
function verifyFullForm(f){
	var validformat1 = /^\d{4}-[0]{1}[0-9]{1}-\d{1,2}$/; //Format: yyyy-mm-dd
	var validformat2 = /^\d{4}-[1]{1}[0-2]{1}-\d{1,2}$/; //Format: yyyy-mm-dd
	if(f.eventdate.value && !(validformat1.test(f.eventdate.value) || validformat2.test(f.eventdate.value))){
		alert("Event date is invalid");
		return false;
	}
	if(!isNumeric(f.year.value)){
		alert("Collection year field must be numeric only");
		return false;
	}
	if(!isNumeric(f.month.value)){
		alert("Collection month field must be numeric only");
		return false;
	}
	if(!isNumeric(f.day.value)){
		alert("Collection day field must be numeric only");
		return false;
	}
	if(!isNumeric(f.startdayofyear.value)){
		alert("Start day of year field must be numeric only");
		return false;
	}
	if(!isNumeric(f.enddayofyear.value)){
		alert("End day of year field must be numeric only");
		return false;
	}
	if(f.ometid && ((f.ometid.value != "" && f.exsnumber.value == "") || (f.ometid.value == "" && f.exsnumber.value != ""))){
		alert("You must have both an exsiccati title and number, or neither. If there is no number, s.n. can be entered.");
		return false;
	}
	if(!verifyDecimalLatitude(f)){
		return false;
	}
	if(!verifyDecimalLongitude(f)){
		return false;
	}
	if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
		alert("Coordinate uncertainty field must be numeric only");
		return false;
	}
	if(!verifyMinimumElevationInMeters(f)){
		return false;
	}
	if(!verifyMaximumElevationInMeters(f)){
		return false;
	}
	if(f.maximumelevationinmeters.value){
		if(!f.minimumelevationinmeters.value){
			alert("Maximun elevation field contains a value yet minumum does not. If elevation consists of a single value rather than a range, enter the value in the minimun field.");
			return false;
		}
		else if(parseInt(f.minimumelevationinmeters.value) > parseInt(f.maximumelevationinmeters.value)){
			alert("Maximun elevation value can not be greater than the minumum value.");
			return false;
		}
	}
	if(!isNumeric(f.duplicatequantity.value)){
		alert("Duplicate Quantity field must be numeric only");
		return false;
	}
	if(searchCatalogNumber(f,false)) return false;
	return true;
}

function verifyFullFormEdits(f){
	if(f.editedfields && f.editedfields.value == ""){
		setTimeout(function () { 
			if(f.editedfields.value){
				f.submitaction.click();
			}
			else{
				alert("No fields appear to have been changed. If you have just changed the scientific name field, there may not have enough time to verify name. Try to submit again.");
			}
		}, 1000);
		return false;
	}
	return true;
}

function prePopulateCatalogNumbers(){
	$("#cloneCatalogNumber-Fieldset").show();
	var catCnt = document.getElementById("clonecount").value;
	if(catCnt == "" || !isNumeric(catCnt)) return false;
	var cloneDiv = document.getElementById("cloneCatalogNumberDiv");
	cloneDiv.innerHTML = "";
	for(var i=0;i < catCnt;i++){
		var newInput = document.createElement("input");
		newInput.setAttribute("id", "clonecat-"+i);
		newInput.setAttribute("name", "clonecatnum[]");
		newInput.setAttribute("type", "text");
		var newDiv = document.createElement("div");
		var newText = document.createTextNode("Catalog Number "+(i+1)+": ");
		newDiv.appendChild(newText);
		newDiv.setAttribute("class", "fieldGroupDiv");
		newDiv.appendChild(newInput);
		if(i == 0){
			var newImg = document.createElement("img");
			newImg.setAttribute("src", "../../images/downarrow.png");
			newImg.setAttribute("style", "width:12px");
			var newAnchor = document.createElement("a");
			newAnchor.setAttribute("href", "#");
			newAnchor.setAttribute("onclick", "autoIncrementCat();return false");
			newAnchor.appendChild(newImg);
			newDiv.appendChild(newAnchor);
		}
		cloneDiv.appendChild(newDiv);
	}
	return false;
}

function autoIncrementCat(){
	let catSeed = document.getElementById("clonecat-0").value;
	if(catSeed != ""){
		let prefix = '';
		for(let h = 0; h < catSeed.length; h++) {
			let c = catSeed.charAt(h);
			if(c >= '0' && c <= '9') break;
			else prefix = prefix + c;
		}
		let suffix = ''; 
		for(let i = catSeed.length; i > 0; i--) {
			let c = catSeed.charAt(i-1);
			if(c >= '0' && c <= '9') break;
			else suffix =  c+suffix;
		}
		let seed = catSeed.substring(prefix.length,(catSeed.length-suffix.length));
		$("input[id^='clonecat']").each(function(){
			let cnt = parseInt($(this).attr("id").substr(9));
			if(cnt > 0){
				let newNum = parseInt(seed)+cnt;
				newNum = newNum.toString();
				if(seed.substr(0,1) == "0"){
					let numLength = seed.length;
					while(newNum.length < numLength) newNum = "0" + newNum;
				}
				$(this).val(prefix+newNum+suffix);				
			}
		});
	}
}

function verifyDecimalLatitude(f){
	if(!isNumeric(f.decimallatitude.value)){
		alert("Input value for Decimal Latitude must be a number value only! " );
		return false;
	}
	if(parseInt(f.decimallatitude.value) > 90){
		alert("Decimal Latitude can not be greater than 90 degrees " );
		return false;
	}
	if(parseInt(f.decimallatitude.value) < -90){
		alert("Decimal Latitude can not be less than -90 degrees " );
		return false;
	}
	return true;
}

function verifyDecimalLongitude(f){
	var lngValue = f.decimallongitude.value;
	if(!isNumeric(lngValue)){
		alert("Input value for Decimal Longitude must be a number value only! " );
		return false;
	}
	if(parseInt(lngValue) > 180){
		alert("Decimal Longitude can not be greater than 180 degrees " );
		return false;
	}
	if(parseInt(lngValue) < -180){
		alert("Decimal Longitude can not be less than -180 degrees " );
		return false;
	}
	return true;
}

function verifyMinimumElevationInMeters(f){
	if(!isNumeric(f.minimumelevationinmeters.value)){
		alert("Elevation values must be numeric only");
		return false;
	}
	if(parseInt(f.minimumelevationinmeters.value) > 8000){
		alert("Was this collection really made above the elevation of Mount Everest?" );
		return false;
	}
	return true;
}

function verifyMaximumElevationInMeters(f){
	if(!isNumeric(f.maximumelevationinmeters.value)){
		alert("Elevation values must be numeric only");
		return false;
	}
	if(parseInt(f.maximumelevationinmeters.value) > 8000){
		alert("Was this collection really made above the elevation of Mount Everest?" );
		return false;
	}
	return true;
}

function verifyDeletion(f){
	var occId = f.occid.value;
	//Restriction when images are linked
	document.getElementById("delverimgspan").style.display = "block";
	verifyAssocImages(occId);
	
	//Restriction when vouchers are linked
	document.getElementById("delvervouspan").style.display = "block";
	verifyAssocVouchers(occId);
}

function verifyAssocImages(occidIn){
	$.ajax({
		type: "POST",
		url: "rpc/getassocimgcnt.php",
		dataType: "json",
		data: { occid: occidIn }
	}).done(function( imgCnt ) {
		document.getElementById("delverimgspan").style.display = "none";
		if(imgCnt > 0){
			document.getElementById("delimgfailspan").style.display = "block";
		}
		else{
			document.getElementById("delimgappdiv").style.display = "block";
		}
		imgAssocCleared = true;
		displayDeleteSubmit();
	});
}

function verifyAssocVouchers(occidIn){
	$.ajax({
		type: "POST",
		url: "rpc/getassocvouchers.php",
		dataType: "json",
		data: { occid: occidIn }
	}).done(function( vList ) {
		document.getElementById("delvervouspan").style.display = "none";
		if(vList != ''){
			document.getElementById("delvoulistdiv").style.display = "block";
			var strOut = "";
			for(var key in vList){
				strOut = strOut + "<li><a href='../../checklists/checklist.php?clid="+key+"' target='_blank'>"+vList[key]+"</a></li>";
			}
			document.getElementById("voucherlist").innerHTML = strOut;
		}
		else{
			document.getElementById("delvouappdiv").style.display = "block";
		}
		voucherAssocCleared = true;
		displayDeleteSubmit();
	});
}

function displayDeleteSubmit(){
	if(imgAssocCleared && voucherAssocCleared){
		var elem = document.getElementById("delapprovediv");
		elem.style.display = "block";
	}
}

function eventDateChanged(eventDateInput){
	var dateStr = eventDateInput.value;
	if(dateStr != ""){
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
			if(dateArr['y'] > 0) distributeEventDate(dateArr['y'],dateArr['m'],dateArr['d']);
		}
	}
	fieldChanged('eventdate');
	var f = eventDateInput.form;
	if(!eventDateInput.form.recordnumber.value && f.recordedby.value) autoDupeSearch();
	return true;
}

function distributeEventDate(y,m,d){
	var f = document.fullform;
	if(y != "0000"){
		f.year.value = y;
		fieldChanged("year");
	}
	if(m == "00"){
		f.month.value = "";
	}
	else{
		f.month.value = m;
		fieldChanged("year");
	}
	if(d == "00"){
		f.day.value = "";
	}
	else{
		f.day.value = d;
		fieldChanged("day");
	}
	f.startdayofyear.value = "";
	try{
		if(m == 0 || d == 0){
			f.startdayofyear.value = "";
		}
		else{
			eDate = new Date(y,m-1,d);
			if(eDate instanceof Date && eDate != "Invalid Date"){
				var onejan = new Date(y,0,1);
				f.startdayofyear.value = Math.ceil((eDate - onejan) / 86400000) + 1;
				fieldChanged("startdayofyear");
			}
		}
	}
	catch(e){
	}
}

function verbatimEventDateChanged(vedObj){
	fieldChanged('verbatimeventdate');

	var f = vedObj.form;

	var vedValue = vedObj.value;
	vedValue = vedValue.replace(" - "," to ");
	vedValue = vedValue.replace(" / "," to ");
	
	if(vedValue.indexOf(" to ") > -1){
		if(f.eventdate.value == ""){
			var startDate = vedValue.substring(0,vedValue.indexOf(" to "));
			var startDateArr = parseDate(startDate);
			var mStr = startDateArr['m'];
			if(mStr.length == 1){
				mStr = "0" + mStr;
			}
			var dStr = startDateArr['d'];
			if(dStr.length == 1){
				dStr = "0" + dStr;
			}
			f.eventdate.value = startDateArr['y'] + "-" + mStr + "-" + dStr;
			distributeEventDate(startDateArr['y'],mStr,dStr);
		}
		var endDate = vedValue.substring(vedValue.indexOf(" to ")+4);
		var endDateArr = parseDate(endDate);
		try{
			var eDate = new Date(endDateArr["y"],endDateArr["m"]-1,endDateArr["d"]);
			if(eDate instanceof Date && eDate != "Invalid Date"){
				var onejan = new Date(endDateArr["y"],0,1);
				f.enddayofyear.value = Math.ceil((eDate - onejan) / 86400000) + 1;
				fieldChanged("enddayofyear");
			}
		}
		catch(e){
		}
	}
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

//Determination form methods 
function initDetAutocomplete(f){
	$( f.sciname ).autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		change: function(event, ui) {
			if(f.sciname.value){
				verifyDetSciName(f);
			}
			else{
				f.scientificnameauthorship.value = "";
				f.family.value = "";
				f.tidtoadd.value = "";
			}				
		}
	});
}

function verifyDetSciName(f){
	$.ajax({
		type: "POST",
		url: "rpc/verifysciname.php",
		dataType: "json",
		data: { term: f.sciname.value }
	}).done(function( data ) {
		if(data){
			f.scientificnameauthorship.value = data.author;
			f.family.value = data.family;
			f.tidtoadd.value = data.tid;
		}
		else{
			alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus by a taxonomic editor. Continue entering this specimen using this name and the name will be resolved at a later date.");
			f.scientificnameauthorship.value = "";
			f.family.value = "";
			f.tidtoadd.value = "";
		}
	});
} 

function detDateChanged(f){
	var isNew = false;
	var newDateStr = f.dateidentified.value;
	if(newDateStr){
		var dateIdentified = document.fullform.dateidentified.value;
		if(dateIdentified == "") dateIdentified = document.fullform.eventdate.value;
		if(dateIdentified){
			var yearPattern = /[1,2]{1}\d{3}/;
			var newYear = newDateStr.match(yearPattern);
			var curYear = dateIdentified.match(yearPattern);
			if(curYear && newYear && newYear[0] > curYear[0]){
				isNew = true;
			}
		}
		else{
			isNew = true;
		}
	}
	f.makecurrent.checked = isNew;
}

function verifyDetForm(f){
	if(f.sciname.value == ""){
		alert("Scientific Name field must have a value");
		return false;
	}
	if(f.identifiedby.value == ""){
		alert("Determiner field must have a value (enter 'unknown' if not defined)");
		return false;
	}
	if(f.dateidentified.value == ""){
		alert("Determination Date field must have a value (enter 's.d.' if not defined)");
		return false;
	}
	if(f.sortsequence && !isNumeric(f.sortsequence.value)){
		alert("Sort Sequence field must be a numeric value only");
		return false;
	}
	return true;
}

//Misc
function dwcDoc(dcTag){
	var language = getCookie("lang");
	if(language == "es"){
		dwcWindow=open("https://biokic.github.io/symbiota-docs/es/editor/edit/fields/#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	}
	else{
		dwcWindow=open("https://biokic.github.io/symbiota-docs/editor/edit/fields/#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	}
	//dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
	if(dwcWindow.opener == null) dwcWindow.opener = self;
	dwcWindow.focus();
	return false;
}

function openOccurrenceSearch(target) {
	collId = document.fullform.collid.value;
	occWindow=open("../misc/occurrencesearch.php?targetid="+target+"&collid="+collId,"occsearch","resizable=1,scrollbars=1,toolbar=0,width=750,height=600,left=200,top=40");
	occWindow.focus();
	if (occWindow.opener == null) occWindow.opener = self;
}

function securityChanged(f){
	fieldChanged('localitysecurity');
	$("#locsecreason").show();
}

function localitySecurityReasonChanged(){
	fieldChanged('localitysecurityreason');
	if($("input[name=localitysecurityreason]").val() == ''){
		$("input[name=lockLocalitySecurity]").prop('checked', false);
	}
	else{
		$("input[name=lockLocalitySecurity]").prop('checked', true);
	}
}

function securityLockChanged(cb){
	if(cb.checked == true){
		if($("input[name=localitysecurityreason]").val() == '') $("input[name=localitysecurityreason]").val("[Security Setting Locked]");
	}
	else{
		$("input[name=localitysecurityreason]").val("")
	}
	fieldChanged('localitysecurityreason');
}

function autoProcessingStatusChanged(selectObj){
	var selValue = selectObj.value;
	if(selValue){
		document.cookie = "autopstatus=" + selValue;
		var editForm = document.fullform;
		if(editForm.occid.value == 0) editForm.processingstatus.value = selValue;
	}
	else{
		document.cookie = "autopstatus=;expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	}
}

function autoDupeChanged(dupeCbObj){
	if(dupeCbObj.checked){
		document.cookie = "autodupe=1";
	}
	else{
		document.cookie = "autodupe=;expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	}
}

function inputIsNumeric(inputObj, titleStr){
	if(!isNumeric(inputObj.value)){
		alert("Input value for " + titleStr + " must be a number value only! " );
	}
}

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

function getCookie(cName){
	var i,x,y;
	var cookieArr = document.cookie.split(";");
	for(i=0;i<cookieArr.length;i++){
		x=cookieArr[i].substr(0,cookieArr[i].indexOf("="));
		y=cookieArr[i].substr(cookieArr[i].indexOf("=")+1);
		x=x.replace(/^\s+|\s+$/g,"");
		if (x==cName){
			return unescape(y);
		}
	}
}