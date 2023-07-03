<?php
include_once('../../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Coordinate Converter</title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../../../js/symb/collections.georef.js?ver=1" type="text/javascript"></script>
	<script>
		function fomatDWS(f) {
			var latDeg = f.latdeg.value.replace(/^\s+|\s+$/g,"");
			var latMin = f.latmin.value.replace(/^\s+|\s+$/g,"");
			var latSec = f.latsec.value.replace(/^\s+|\s+$/g,"");
			var latNS = f.latns.value;
			var lngDeg = f.lngdeg.value.replace(/^\s+|\s+$/g,"");
			var lngMin = f.lngmin.value.replace(/^\s+|\s+$/g,"");
			var lngSec = f.lngsec.value.replace(/^\s+|\s+$/g,"");
			var lngEW = f.lngew.value;
			var datum = f.lldatum.value.replace(/^\s+|\s+$/g,"");
			if(latDeg && latMin && lngDeg && lngMin){
				alert("DMS fields must contain a value");
				return false;
			}
			if(latMin == "") latMin = 0;
			if(latSec == "") latSec = 0;
			if(lngMin == "") lngMin = 0;
			if(lngSec == "") lngSec = 0;
			if(!isNumeric(latDeg) || !isNumeric(latMin) || !isNumeric(latSec) || !isNumeric(lngDeg) || !isNumeric(lngMin) || !isNumeric(lngSec)){
				alert("Field values must be numeric only");
				return false;
			}
			if(latDeg < 0 || latDeg > 90){
				alert("Latitude degree must be between 0 and 90 degrees");
				return false;
			}
			else if(lngDeg < 0 || lngDeg > 180){
				alert("Longitude degree must be between 0 and 180 degrees");
				return false;
			}
			else if(latMin < 0 || latMin > 60 || lngMin < 0 || lngMin > 60 || latSec < 0 || latSec > 60 || lngSec < 0 || lngSec > 60){
				alert("Minute and second values can only be between 0 and 60");
				return false;
			}
			var targetForm = '';
			//Prepare and enter verbatimCoordinates
			var vcStr = "";
			//var vcStr = targetForm.verbatimcoordinates.value;
			vcStr = vcStr.replace(/-*\d{2}[�\u00B0]+[NS\d\.\s\'\"-�\u00B0]+[EW;]+/g, "");
			vcStr = vcStr.replace(/^\s+|\s+$/g, "");
			vcStr = vcStr.replace(/^;|;$/g, "");
			if(vcStr != "") vcStr = vcStr + "; ";
			var dmsStr = latDeg + "\u00B0 " + latMin + "' ";
			if(latSec) dmsStr += latSec + '" ';
			dmsStr += latNS + "  " + lngDeg + "\u00B0 " + lngMin + "' ";
			if(lngSec) dmsStr += lngSec + '" ';
			dmsStr += lngEW;
			//targetForm.verbatimcoordinates.value = vcStr + dmsStr;
			alert(vcStr + dmsStr);
			//Prepare and enter decimal values
			var latDec = parseInt(latDeg) + (parseFloat(latMin)/60) + (parseFloat(latSec)/3600);
			var lngDec = parseInt(lngDeg) + (parseFloat(lngMin)/60) + (parseFloat(lngSec)/3600);
			if(latNS == "S") latDec = latDec * -1;
			if(lngEW == "W") lngDec = lngDec * -1;
			//targetForm.decimallatitude.value = Math.round(latDec*1000000)/1000000;
			//targetForm.decimallongitude.value = Math.round(lngDec*1000000)/1000000;
			alert(Math.round(latDec*1000000)/1000000 + " " + Math.round(lngDec*1000000)/1000000);

			try{
				targetForm.fieldChanged("decimallatitude");
				targetForm.fieldChanged("decimallongitude");
				targetForm.fieldChanged("verbatimcoordinates");
			}
			catch(err){  }
		}

		function formatUTM(f) {
			var zValue = f.utmzone.value.replace(/^\s+|\s+$/g,"");
			var hValue = f.hemisphere.value;
			var eValue = f.utmeast.value.replace(/^\s+|\s+$/g,"");
			var nValue = f.utmnorth.value.replace(/^\s+|\s+$/g,"");
			var datum = f.utmdatum.value;
			if(!zValue || !eValue || !nValue){
				alert("Zone, Easting, and Northing fields must not be empty");
				return false;
			}
			if(!isNumeric(eValue) || !isNumeric(nValue)){
				alert("Easting and northing fields must contain numeric values only");
				return false;
			}
			//Remove prior UTM references from verbatimCoordinates field
			//var vcStr = targetForm.verbatimcoordinates.value;
			var vcStr = '';
			vcStr = vcStr.replace(/\d{2}.*\d+E\s+\d+N[;\s]*/g, "");
			vcStr = vcStr.replace(/(Northern)|(Southern)/g, "");
			vcStr = vcStr.replace(/^\s+|\s+$/g, "");
			vcStr = vcStr.replace(/^;|;$/g, "");
			//put UTM into verbatimCoordinate field
			if(vcStr != "") vcStr = vcStr + "; ";
			var utmStr = zValue;
			if(isNumeric(zValue)) utmStr = utmStr + hValue;
			utmStr = utmStr + " " + eValue + "E " + nValue + "N ";
			//targetForm.verbatimcoordinates.value = vcStr + utmStr;
			alert(vcStr + utmStr);
			//Convert to Lat/Lng values
			var zNum = parseInt(zValue);
			if(isNumeric(zNum)){
				var latLngStr = utm2LatLng(zNum, eValue, nValue, datum, hValue);
				var llArr = latLngStr.split(',');
				if(llArr){
					//targetForm.decimallatitude.value = llArr[0];
					//targetForm.decimallongitude.value = llArr[1];
					alert(llArr[0] + " " + llArr[1]);
				}
			}
			try{
				targetForm.fieldChanged("decimallatitude");
				targetForm.fieldChanged("decimallongitude");
				targetForm.fieldChanged("verbatimcoordinates");
			}
			catch(err) { }
		}

		function formatTRS(f) {
			var township = f.township.value.replace(/^\s+|\s+$/g,"");
			var townshipNS = f.townshipNS.value.replace(/^\s+|\s+$/g,"");
			var range = f.range.value.replace(/^\s+|\s+$/g,"");
			var rangeEW = f.rangeEW.value.replace(/^\s+|\s+$/g,"");
			var section = f.section.value.replace(/^\s+|\s+$/g,"");
			var secdetails = f.secdetails.value.replace(/^\s+|\s+$/g,"");
			var meridian = f.meridian.value.replace(/^\s+|\s+$/g,"");

			if(!township || !range){
				alert("Township and Range fields must have values");
				return false;
			}
			else if(!isNumeric(township)){
				alert("Numeric value expected for Township field. If non-standardize format is used, enter directly into the Verbatim Coordinate Field");
				return false;
			}
			else if(!isNumeric(range)){
				alert("Numeric value expected for Range field. If non-standardize format is used, enter directly into the Verbatim Coordinate Field");
				return false;
			}
			else if(!isNumeric(section)){
				alert("Numeric value expected for Section field. If non-standardize format is used, enter directly into the Verbatim Coordinate Field");
				return false;
			}
			else if(section > 36){
				alert("Section field must contain a numeric value between 1-36");
				return false;
			}
			else{
				//Insert into verbatimCoordinate field
				//vCoord = targetForm.verbatimcoordinates;
				var targetForm = '';
				if(vCoord.value) vCoord.value = vCoord.value + "; ";
				vCoord.value = vCoord.value + "TRS: T"+township+townshipNS+" R"+range+rangeEW+" sec "+section+" "+secdetails+" "+meridian;
				try{
					fieldChanged("verbatimcoordinates");
				}
				catch(err) { }
			}
		}

		function isNumeric(sText){
		   	var bool = true;
		   	var validChars = "0123456789-.";
		   	for(var i = 0; i < sText.length; i++){
				if(validChars.indexOf(sText.charAt(i)) == -1){
					bool = false;
					break;
			  	}
		   	}
			return bool;
		}
	</script>
	<style type="text/css">
		body{ background-color: #ffffff; }
		#coordAidDiv{  }
		.toolDiv{ float: left; padding: 15px 10px; }
		#dmsAidDiv{ }
		#utmAidDiv{  }
		#trsAidDiv{  }
		.fieldDiv{ padding: 3px 0px }
		.labelSpan{  }
		.buttonDiv{ margin-top: 5px }
	</style>
</head>
<body>
	<div id="coordAidDiv">
		<form name="formatterForm" onsubmit="return false">
			<div id="dmsAidDiv" class="toolDiv">
				<fieldset>
					<legend>DMS Converter</legend>
					<div class="fieldDiv">
						<span class="labelSpan">Lat:</span>
						<input name="latdeg" style="width:35px;" title="Latitude Degree" />&deg;
						<input name="latmin" style="width:50px;" title="Latitude Minutes" />'
						<input name="latsec" style="width:50px;" title="Latitude Seconds" />&quot;
						<select name="latns">
							<option>N</option>
							<option>S</option>
						</select>
					</div>
					<div class="fieldDiv">
						<span class="labelSpan">Long:</span>
						<input name="lngdeg" style="width:35px;" title="Longitude Degree" />&deg;
						<input name="lngmin" style="width:50px;" title="Longitude Minutes" />'
						<input name="lngsec" style="width:50px;" title="Longitude Seconds" />&quot;
						<select name="lngew">
							<option>E</option>
							<option SELECTED>W</option>
						</select>
					</div>
					<div class="fieldDiv">
						<span class="labelSpan">Datum:</span>
						<select name="lldatum">
							<option value="WGS84" selected>WGS84</option>
							<option value="NAD27">NAD27</option>
							<option value="NAD83">NAD83</option>
						</select>
					</div>
					<div style="margin:5px;">
						<button type="button" onclick="fomatDWS(this.form)">Insert Lat/Long Values</button>
					</div>
				</fieldset>
			</div>
			<div id="utmAidDiv" class="toolDiv">
				<fieldset>
					<legend>UTM Converter</legend>
					<div class="fieldDiv">
						<span class="labelSpan">Zone:</span>
						<input name="utmzone" style="width:40px;" />
					</div>
					<div class="fieldDiv">
						<span class="labelSpan">East:</span>
						<input name="utmeast" type="text" style="width:100px;" />
					</div>
					<div class="fieldDiv">
						<span class="labelSpan">North:</span>
						<input name="utmnorth" type="text" style="width:100px;" />
					</div>
					<div class="fieldDiv">
						<span class="labelSpan">Hemisphere:</span>
						<select name="hemisphere" title="Use hemisphere designator (e.g. 12N) rather than grid zone ">
							<option value="N">North</option>
							<option value="S">South</option>
						</select>
					</div>
					<div class="fieldDiv">
						<span class="labelSpan">Datum:</span>
						<select name="utmdatum">
							<option value="WGS84" selected>WGS84</option>
							<option value="NAD27">NAD27</option>
							<option value="NAD83">NAD83</option>
						</select>
					</div>
					<div class="buttonDiv">
						<button type="button" onclick="formatUTM(this.form)">Insert UTM Values</button>
					</div>
				</fieldset>
			</div>
			<div id="trsAidDiv" class="toolDiv">
				<fieldset>
					<legend>TRS Converter</legend>
					<div class="fieldDiv">
						T<input name="township" style="width:30px;" title="Township" />
						<select name="townshipNS">
							<option>N</option>
							<option>S</option>
						</select>
						R<input name="range" style="width:30px;" title="Range" />
						<select name="rangeEW">
							<option>E</option>
							<option>W</option>
						</select>
					</div>
					<div class="fieldDiv">
						Sec:
						<input name="section" type="input" style="width:30px;" title="Section" />
						Details:
						<input name="secdetails" type="input" style="width:90px;" title="Section Details" />
					</div>
					<select name="meridian" title="Meridian">
						<option value="">Meridian Selection</option>
						<option value="">----------------------------------</option>
						<option value="G-AZ">Arizona, Gila &amp; Salt River</option>
						<option value="NAAZ">Arizona, Navajo</option>
						<option value="F-AR">Arkansas, Fifth Principal</option>
						<option value="H-CA">California, Humboldt</option>
						<option value="M-CA">California, Mt. Diablo</option>
						<option value="S-CA">California, San Bernardino</option>
						<option value="NMCO">Colorado, New Mexico</option>
						<option value="SPCO">Colorado, Sixth Principal</option>
						<option value="UTCO">Colorado, Ute</option>
						<option value="B-ID">Idaho, Boise</option>
						<option value="SPKS">Kansas, Sixth Principal</option>
						<option value="F-MO">Missouri, Fifth Principal</option>
						<option value="P-MT">Montana, Principal</option>
						<option value="SPNE">Nebraska, Sixth Principal</option>
						<option value="M-NV">Nevada, Mt. Diablo</option>
						<option value="NMNM">New Mexico, New Mexico</option>
						<option value="F-ND">North Dakota, Fifth Principal</option>
						<option value="C-OK">Oklahoma, Cimarron</option>
						<option value="I-OK">Oklahoma, Indian</option>
						<option value="W-OR">Oregon, Willamette</option>
						<option value="BHSD">South Dakota, Black Hills</option>
						<option value="F-SD">South Dakota, Fifth Principal</option>
						<option value="SPSD">South Dakota, Sixth Principal</option>
						<option value="SLUT">Utah, Salt Lake</option>
						<option value="U-UT">Utah, Uinta</option>
						<option value="W-WA">Washington, Willamette</option>
						<option value="SPWY">Wyoming, Sixth Principal</option>
						<option value="WRWY">Wyoming, Wind River</option>
					</select>
					<div class="buttonDiv">
						<button type="button" onclick="formatTRS(this.form)">Insert TRS Values</button>
					</div>
				</fieldset>
			</div>
		</form>
	</div>
</body>