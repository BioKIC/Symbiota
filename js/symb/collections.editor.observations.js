$(document).ready(function() {
	$("#sciname").autocomplete({ 
		source: "rpc/getspeciessuggest.php", 
		minLength: 3,
		autoFocus: true,
		change: function(event, ui) {
			var f = document.obsform;
			if( f.sciname.value ){
				$.ajax({
					type: "POST",
					url: "rpc/verifysciname.php",
					dataType: "json",
					data: { term: f.sciname.value },
					autoFocus: true
				}).done(function( data ) {
					if(data){
						f.scientificnameauthorship.value = data.author;
						f.family.value = data.family;
					}
					else{
						f.scientificnameauthorship.value = "";
						f.family.value = "";
					}
				});
			}
			else{
				f.scientificnameauthorship.value = "";
				f.family.value = "";
			}				
		}
	});
});

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display == "block") ele.style.display="none";
	 	else ele.style.display="block";
	}
	else{
		var divObjs = document.getElementsByTagName("div");
	  	for (i = 0; i < divObjs.length; i++) {
	  		var divObj = divObjs[i];
	  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
				if(divObj.style.display=="none") divObj.style.display="block";
			 	else divObj.style.display="none";
			}
		}
	}
}

function insertLatLng(f) {
	var latDeg = document.getElementById("latdeg").value.replace(/^\s+|\s+$/g,"");
	var latMin = document.getElementById("latmin").value.replace(/^\s+|\s+$/g,"");
	var latSec = document.getElementById("latsec").value.replace(/^\s+|\s+$/g,"");
	var latNS = document.getElementById("latns").value;
	var lngDeg = document.getElementById("lngdeg").value.replace(/^\s+|\s+$/g,"");
	var lngMin = document.getElementById("lngmin").value.replace(/^\s+|\s+$/g,"");
	var lngSec = document.getElementById("lngsec").value.replace(/^\s+|\s+$/g,"");
	var lngEW = document.getElementById("lngew").value;
	if(latDeg && latMin && lngDeg && lngMin){
		if(latMin == "") latMin = 0;
		if(latSec == "") latSec = 0;
		if(lngMin == "") lngMin = 0;
		if(lngSec == "") lngSec = 0;
		if(isNumeric(latDeg) && isNumeric(latMin) && isNumeric(latSec) && isNumeric(lngDeg) && isNumeric(lngMin) && isNumeric(lngSec)){
			if(latDeg < 0 || latDeg > 90){
				alert("Latitude degree must be between 0 and 90 degrees");
			}
			else if(lngDeg < 0 || lngDeg > 180){
				alert("Longitude degree must be between 0 and 180 degrees");
			}
			else if(latMin < 0 || latMin > 60 || lngMin < 0 || lngMin > 60 || latSec < 0 || latSec > 60 || lngSec < 0 || lngSec > 60){
				alert("Minute and second values can only be between 0 and 60");
			}
			else{
				var latDec = parseInt(latDeg) + (parseFloat(latMin)/60) + (parseFloat(latSec)/3600);
				var lngDec = parseInt(lngDeg) + (parseFloat(lngMin)/60) + (parseFloat(lngSec)/3600);
				if(latNS == "S") latDec = latDec * -1; 
				if(lngEW == "W") lngDec = lngDec * -1; 
				f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
				f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
			}
		}
		else{
			alert("Field values must be numeric only");
		}
	}
	else{
		alert("DMS fields must contain a value");
	}
}

function convertElevFt(f){
	var elev = parseInt(f.verbatimelevation.value);
	if(elev) f.minimumelevationinmeters.value = Math.round(elev*.03048)*10;
}

function verifyObsForm(f){
    if(f.sciname.value == ""){
		window.alert("Observation must have an identification (scientific name) assigned to it, even if it is only to family, order, or even kingdom.");
		return false;
    }
	var validDate = /^\d{4}-\d{2}-\d{2}$/ //Format: yyyy-mm-dd
    if(!validDate.test(f.eventdate.value)){
    	window.alert("Observation date must follow format: yyyy-mm-dd");
		return false;
    }
    if(isNumeric(f.decimallatitude.value) == false){
		window.alert("Latitude must be in the decimal format with numeric characters only (34.5335). ");
		return false;
    }
    if(isNumeric(f.decimallongitude.value) == false){
		window.alert("Longitude must be in the decimal format with numeric characters only. Note that the western hemisphere is represented as a negitive number (-110.5335). ");
		return false;
    }
    if(parseInt(f.decimallongitude.value ) > 0 && (f.country == 'USA' || f.country == 'United States' || f.country == 'Canada' || f.country == 'Mexico')){
		window.alert("For North America, the decimal format of longitude should be negitive value. ");
		return false;
    }
    if(isNumeric(f.coordinateuncertaintyinmeters.value) == false){
		window.alert("Coordinate Uncertainty must be a numeric value only (in meters). ");
		return false;
    }
    if(isNumeric(f.minimumelevationinmeters.value) == false){
		window.alert("Elevation must be a numeric value only. ");
		return false;
    }
    return true;
}

function verifyDate(eventDateInput){
	//test date and return mysqlformat
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
		var validformat1 = /^\d{4}-\d{1,2}-\d{1,2}$/ //Format: yyyy-mm-dd
		var validformat2 = /^\d{1,2}\/\d{1,2}\/\d{2,4}$/ //Format: mm/dd/yyyy
		var validformat3 = /^\d{1,2} \D+ \d{2,4}$/ //Format: dd mmm yyyy
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

function verifyLatValue(f, client_root){
	var inputObj = f.decimallatitude.value;
	inputIsNumeric(inputObj, 'Decimal Latitude');
	if(inputObj.value > 90 || inputObj.value < -90){
		alert('Decimal latitude value should be between -90 and 90 degrees');
	}
	verifyCoordinates(f, client_root);
}

function verifyLngValue(f, client_root){
	var inputObj = f.decimallongitude.value;
	inputIsNumeric(inputObj, 'Decimal Longitude');
	if(inputObj.value > 180 || inputObj.value < -180){
		alert('Decimal longitude value should be between -180 and 180 degrees');
	}
	verifyCoordinates(f, client_root);
}

function verifyElevValue(inputObj){
	inputIsNumeric(inputObj, 'Coordinate Uncertainty');
	if(inputObj.value > 4000){
		alert('Are you sure your elevation value in meters. ' + inputObj.value + ' meters is a very high elevation.');
	}
}

function verifyImageSize(inputObj){
	if (!window.FileReader) {
		//alert("The file API isn't supported on this browser yet.");
		return;
	}

	var file = inputObj.files[0];
	if(file.size > maxUpload){
		alert("Image "+file.name+" file size ("+Math.round(file.size/100000)/10+"mb) is larger than is allowed ("+(maxUpload/1000000)+"mb)");
    }
}

function inputIsNumeric(inputObj, titleStr){
	if(!isNumeric(inputObj.value)){
		alert("Input value for " + titleStr + " must be a number value only! " );
	}
}

function isNumeric(sText){
   	var IsNumber = true;
 
	if(sText){
	   	var ValidChars = "0123456789-.";
	   	var Char;
	   	for(var i = 0; i < sText.length && IsNumber == true; i++){ 
		   Char = sText.charAt(i); 
			if(ValidChars.indexOf(Char) == -1){
				IsNumber = false;
				break;
	      	}
	   	}
	}
	return IsNumber;
}

function openMappingAid(targetForm,targetLat,targetLong) {
    mapWindow=open("../tools/mappointaid.php","mappointaid","resizable=0,width=900,height=700,left=20,top=20");
    if (mapWindow.opener == null) mapWindow.opener = self;
    mapWindow.focus();
    if(document.obsform.geodeticdatum.value == "") document.obsform.geodeticdatum.value = "WGS84"; 
}
