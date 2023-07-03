var vStatusArr = new Array("reviewed - high confidence","reviewed - medium confidence","reviewed - low confidence",
	"not reviewed","expert needed","custom status 1","custom status 2","unable to georeference");

$(document).ready(function() {
	//Verification status query form
	$("#qvstatus").autocomplete({ source: vStatusArr }, { delay: 0, minLength: 1 });

	//Verification status autocomplete
	$("#georeferenceverificationstatus").autocomplete({ source: vStatusArr }, { delay: 0, minLength: 1 });
});

function verifyQueryForm(f){
	/* if(f.qlocality.value == ""){
	 * 	alert("Please enter a locality term");
	 * 	return false;
	 * }
	*/
	document.getElementById("qworkingspan").style.display = "inline";
	return true;
}

function verifyGeorefForm(f){
	if(f.locallist.selectedIndex == -1){
		alert("At least one locality within list must be selected");
		return false;
	}
	if(f.decimallatitude.value == "" || f.decimallongitude.value == ""){
		alert("Please enter coordinates into lat/long decimal fields");
		return false;
	}
	if(!isNumeric(f.decimallatitude.value) || !isNumeric(f.decimallongitude.value)){
		alert("Decimal coordinates must be numeric values only");
		return false;
	}
	if(f.decimallatitude.value > 90 || f.decimallatitude.value < -90){
		alert("Decimal Latitude must be between -90 and 90 degrees");
		return false;
	}
	if(f.decimallongitude.value > 180 || f.decimallongitude.value < -180){
		alert("Decimal Longitude must be between -180 and 180 degrees");
		return false;
	}
	if(!isNumeric(f.minimumelevationinmeters.value) || !isNumeric(f.maximumelevationinmeters.value)){
		alert("Elevation field can only contain numeric values");
		return false;
	}
	if(!isNumeric(f.coordinateuncertaintyinmeters.value)){
		alert("Coordinate Uncertainity can only contain numeric values");
		return false;
	}
	if(f.coordinateuncertaintyinmeters.value == ""){
		return confirm('An "Error (in meters)" value is strongly recommended. Select "OK" to submit without entering an error value?');
	}
	document.getElementById("workingspan").style.display = "inline";
	return true;
}

function verifyFootprintWKT(f) {
	return true;
}

function updateLatDec(f){
	var latDec = parseInt(f.latdeg.value);
	var latMin = parseFloat(f.latmin.value);
	var latSec = parseFloat(f.latsec.value);
	var latNS = f.latns.value;
	if(!isNumeric(latDec) || !isNumeric(latMin) || !isNumeric(latSec)){
		alert('Degree, minute, and second values must be numeric only');
		return false;
	}
	if(latDec > 90){
		alert("Latitude degrees cannot be greater than 90");
		return false;
	}
	if(latMin > 60){
		alert("The Minutes value cannot be greater than 60");
		return false;
	}
	if(latSec > 60){
		alert("The Seconds value cannot be greater than 60");
		return false;
	}
	if(latMin) latDec = latDec + (f.latmin.value / 60);
	if(latSec) latDec = latDec + (f.latsec.value / 3600);
	if(latNS == "S"){
		if(latDec > 0) latDec = -1*latDec;
	}
	else{
		if(latDec < 0) latDec = -1*latDec;
	}
	f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
}

function updateLngDec(f){
	var lngDec = parseInt(f.lngdeg.value);
	var lngMin = parseFloat(f.lngmin.value);
	var lngSec = parseFloat(f.lngsec.value);
	var lngEW = f.lngew.value;
	if(!isNumeric(lngDec) || !isNumeric(lngMin) || !isNumeric(lngSec)){
		alert("Degree, minute, and second values must be numeric only");
		return false;
	}
	if(lngDec > 180){
		alert("Longitude degrees cannot be greater than 180");
		return false;
	}
	if(lngMin > 60){
		alert("The Minutes value cannot be greater than 60");
		return false;
	}
	if(lngSec > 60){
		alert("The Seconds value cannot be greater than 60");
		return false;
	}
	if(lngMin) lngDec = lngDec + (lngMin / 60);
	if(lngSec) lngDec = lngDec + (lngSec / 3600);
	if(lngEW == "W"){
		if(lngDec > 0) lngDec = -1*lngDec;
	}
	else{
		if(lngDec < 0) lngDec = -1*lngDec;
	}
	f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
}

function verifyCoordUncertainty(inputObj){
	if(!isNumeric(inputObj.value)){
		alert("Coordinate Uncertainity can only contain numeric values");
	}
}

function geoLocateLocality(){
	var selObj = document.getElementById("locallist");
	if(selObj.selectedIndex > -1){
		var f = document.queryform;
		var locality = encodeURIComponent(selObj.options[selObj.selectedIndex].text);
		var country = encodeURIComponent(f.qcountry.value);
		var state = encodeURIComponent(f.qstate.value);
		var county = encodeURIComponent(f.qcounty.value);
		geolocWindow=open("geolocate.php?country="+country+"&state="+state+"&county="+county+"&locality="+locality,"geoloctool","resizable=1,scrollbars=1,toolbar=0,width=1050,height=700,left=20,top=20");
		if(geolocWindow.opener == null) geolocWindow.opener = self;
	}
	else{
		alert("Select a locality in list to open that record set in the editor");
	}
}

function geoLocateUpdateCoord(latValue,lngValue,coordErrValue,footprintWKTValue){
	var f = document.georefform;
	f.decimallatitude.value = latValue;
	f.decimallongitude.value = lngValue;
	if(!isNumeric(coordErrValue)) coordErrValue = "";
	f.coordinateuncertaintyinmeters.value = coordErrValue;
	f.geodeticdatum.value = "WGS84";
	if(footprintWKTValue == "Unavailable") footprintWKTValue = "";
	else if(footprintWKTValue.length > 65000) footprintWKTValue = "";	//WKT footprint is too large to save in the database
	else if(footprintWKTValue.indexOf("NaN") > -1) footprintWKTValue = "";
	else if(footprintWKTValue == "N/A") footprintWKTValue = "";
	f.footprintwkt.value = footprintWKTValue;
	var baseStr = f.georeferencesources.value;
	if(baseStr){
		var baseTokens = baseStr.split(";"); 
		baseStr = baseTokens[0]+"; ";
	}
	f.georeferencesources.value = baseStr+"GeoLocate";
}

function geoCloneTool(){
	var selObj = document.getElementById("locallist");
	if(selObj.selectedIndex > -1){
		var f = document.queryform;
		var url = "georefclone.php?";
		url = url + "locality=" + selObj.options[selObj.selectedIndex].text;
		url = url + "&country=" + f.qcountry.value;
		url = url + "&state=" + f.qstate.value;
		url = url + "&county=" + f.qcounty.value;
		url = url + "&collid=" + f.collid.value;
		cloneWindow=open(url,"geoclonetool","resizable=1,scrollbars=1,toolbar=0,width=800,height=600,left=20,top=20");
		if(cloneWindow.opener == null) cloneWindow.opener = self;
	}
	else{
		alert("Select a locality in list to open that record set in the editor");
	}
}

function analyseLocalityStr(){
	var selObj = document.getElementById("locallist");
	if(selObj.selectedIndex > -1){
		var sourceStr = '';
		var f = document.georefform;
		var locStr = selObj.options[selObj.selectedIndex].text;
		
		var utmRegEx5 = /(\d{1,2})\D{1}\s{1}(\d{2}\s{1}\d{2}\s{1}\d{3})mE\s{1}(\d{2}\s{1}\d{2}\s{1}\d{3})mN/ //Format: ##S ## ## ###mE ## ## ###mN ##
		var llRegEx1 = /(\d{1,2})[^\.\d]{1,2}(?:deg)*\s*(\d{1,2}(?:\.[0-9])*)[^\.\d]{1,2}\s*(\d{0,2}(?:\.[0-9])*)[^\.\d,;]{1,3}\s*[NS,;]{1}[\.,;]*\s*[EW]{0,1}\s*(\d{1,3})[^\.\d]{1}(?:deg)*\s*(\d{0,2}(?:\.[0-9]+)*)[^\.\d]{1,2}\s*(\d{0,2}(?:\.[0-9]+)*)[^\.\d]{1,3}/i 
		var llRegEx2 = /(\d{1,2})[^\.\d]{1,2}\s{0,1}(\d{1,2}(?:\.[0-9])*)[^\.\d,;]{1,2}\s{0,1}[NS,;]{1}[\s,;]{0,3}[EW]{0,1}\s{0,2}(\d{1,3})[^\.\d]{1,2}\s*(\d{1,2}(?:\.[0-9])*)[^\.\d]{1,2}/i 
		var llRegEx3 = /(-{0,1}\d{1,2}\.{1}\d+)[^\d]{1,2},{0,1}\s*(-{0,1}\d{1,3}\.{1}\d+)[^\d]{1}/	//Format: (##.#####, -###.#####)
		var utmRegEx1 = /(\d{7})N{0,1}\s+(\d{6,7})E{0,1}\s+(\d{1,2})/ 				//Format: #######N ######E ##
		var utmRegEx2 = /(\d{1,2})\D{0,2}\s+(\d{7})N\s+(\d{6,7})E/ 					//Format: ## #######N ######E 
		var utmRegEx3 = /(\d{6,7})E{0,1}\s+(\d{7})N{0,1}\s+(\d{1,2})/ 				//Format: ######E #######N ## 
		var utmRegEx4 = /(\d{1,2})\D{0,2}\s+(\d{6,7})E\s+(\d{7})N/ 					//Format: ## ######E #######N  
		var utmRegEx6 = /(\d{1,2})\D{0,2}\s*(\d{6})\D{0,2}\s*(\d{7})/ 				//Format: ## ###### #######  
		var utmRegEx7 = /(\d{1,2})\D{0,2}\s*(\d{7})\D{0,2}\s*(\d{6})/ 				//Format: ## ####### ######  
		var extractStr = "";
		if(extractArr = utmRegEx5.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmzone.value = extractArr[1];
			f.utmeast.value = extractArr[2].replace(/\s/g,'');
			f.utmnorth.value = extractArr[3].replace(/\s/g,'');
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else if(extractArr = llRegEx1.exec(locStr)){
			f.latdeg.value = extractArr[1];
			f.latmin.value = extractArr[2];
			f.latsec.value = extractArr[3];
			f.lngdeg.value = extractArr[4];
			f.lngmin.value = extractArr[5];
			f.lngsec.value = extractArr[6];
			updateLatDec(f);
			updateLngDec(f);
			sourceStr = 'lat/long (DMS) from label';
		}
		else if(extractArr = llRegEx2.exec(locStr)){
			f.latdeg.value = extractArr[1];
			f.latmin.value = extractArr[2];
			f.latsec.value = "";
			f.lngdeg.value = extractArr[3];
			f.lngmin.value = extractArr[4];
			f.lngsec.value = "";
			updateLatDec(f);
			updateLngDec(f);
			sourceStr = 'lat/long (DMS) from label';
		}
		else if(extractArr = llRegEx3.exec(locStr)){
			f.decimallatitude.value = extractArr[1];
			f.decimallongitude.value = extractArr[2];
		}
		else if(extractArr = utmRegEx1.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmnorth.value = extractArr[1];
			f.utmeast.value = extractArr[2];
			f.utmzone.value = extractArr[3];
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else if(extractArr = utmRegEx2.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmzone.value = extractArr[1];
			f.utmnorth.value = extractArr[2];
			f.utmeast.value = extractArr[3];
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else if(extractArr = utmRegEx3.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmeast.value = extractArr[1];
			f.utmnorth.value = extractArr[2];
			f.utmzone.value = extractArr[3];
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else if(extractArr = utmRegEx4.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmzone.value = extractArr[1];
			f.utmeast.value = extractArr[2];
			f.utmnorth.value = extractArr[3];
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else if(extractArr = utmRegEx6.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmzone.value = extractArr[1];
			f.utmeast.value = extractArr[2];
			f.utmnorth.value = extractArr[3];
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else if(extractArr = utmRegEx7.exec(locStr)){
			document.getElementById("utmdiv").style.display = "block";
			f.utmzone.value = extractArr[1];
			f.utmeast.value = extractArr[3];
			f.utmnorth.value = extractArr[2];
			insertUtm(f);
			sourceStr = 'UTM from label';
		}
		else{
			alert("Unable to parse UTM of DMS lat/long");
		}

		if(sourceStr){
			//Populate source field
			var baseStr = f.georeferencesources.value;
			if(baseStr){
				var baseTokens = baseStr.split(";"); 
				baseStr = baseTokens[0]+"; ";
			}
			f.georeferencesources.value = baseStr+sourceStr;
		}
	}
	else{
		alert("Select a locality");
	}
}

function openFirstRecSet(){
	var collId = document.georefform.collid.value;
	var selObj = document.getElementById("locallist");
	if(selObj.selectedIndex > -1){
		var occidStr = selObj.options[selObj.selectedIndex].value;
		occWindow=open("../editor/occurrenceeditor.php?q_catalognumber=occid"+occidStr+"&occindex=0","occsearch","resizable=1,scrollbars=1,toolbar=0,width=950,height=700,left=20,top=20");
		if(occWindow.opener == null) occWindow.opener = self;
	}
	else{
		alert("Select a locality in list to open that record set in the editor");
	}
}

function insertUtm(f) {
	var zValue = f.utmzone.value.replace(/^\s+|\s+$/g,"");
	var hValue = f.hemisphere.value;
	var eValue = f.utmeast.value.replace(/^\s+|\s+$/g,"");
	var nValue = f.utmnorth.value.replace(/^\s+|\s+$/g,"");
	if(zValue && eValue && nValue){
		if(isNumeric(eValue) && isNumeric(nValue)){
			//Convert to Lat/Lng values
			var latLngStr = utm2LatLng(zValue, eValue, nValue, f.geodeticdatum.value, hValue);
			var llArr = latLngStr.split(',');
			if(llArr){
				f.decimallatitude.value = llArr[0];
				f.decimallongitude.value = llArr[1];
			}
		}
		else{
			alert("Easting and northing fields must contain numeric values only");
		}
	}
	else{
		alert("Zone, Easting, and Northing fields must not be empty");
	}
}

function updateMinElev(minFeetValue){
	var f = document.georefform;
	f.minimumelevationinmeters.value = Math.round(minFeetValue*.0305)*10;
}

function updateMaxElev(maxFeetValue){
	var f = document.georefform;
	f.maximumelevationinmeters.value = Math.round(maxFeetValue*.0305)*10;
}

function checkSelectCollidForm(f){
	var formVerified = false;
	for(var h=0;h<f.length;h++){
		if(f.elements[h].name == "collid[]" && f.elements[h].checked){
			formVerified = true;
			break;
		}
	}
	if(!formVerified){
		alert("Please choose at least one collection!");
		return false;
	}
	return true;
}

//Misc functions
function selectAllCollections(cbObj){
	var cbStatus = cbObj.checked
	var f = cbObj.form;
	for(var i=0;i<f.length;i++){
		if(f.elements[i].name == "collid[]") f.elements[i].checked = cbStatus;
	}
}

function openMappingAid() {
	var f = document.georefform;
	var latDef = f.decimallatitude.value;
	var lngDef = f.decimallongitude.value;
	var zoom = 5;
	if(latDef && lngDef) zoom = 11;
	mapWindow=open("../tools/mappointaid.php","geomapaid","resizable=0,width=900,height=700,left=20,top=20");
	if (mapWindow.opener == null) mapWindow.opener = self;
}

function toggle(target){
	var objDiv = document.getElementById(target);
	if(objDiv){
		if(objDiv.style.display=="none"){
			objDiv.style.display = "block";
		}
		else{
			objDiv.style.display = "none";
		}
	}
	else{
	  	var divs = document.getElementsByTagName("div");
	  	for (var h = 0; h < divs.length; h++) {
	  	var divObj = divs[h];
			if(divObj.className == target){
				if(divObj.style.display=="none"){
					divObj.style.display="block";
				}
			 	else {
			 		divObj.style.display="none";
			 	}
			}
		}
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
