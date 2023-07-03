<?php
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/geotools.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/geotools.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/geotools.en.php');
?>

<div id="coordAidDiv">
	<div id="dmsAidDiv">
		<div>
			<?php echo $LANG['LAT']; ?>:
			<input id="latdeg" style="width:35px;" title="<?php echo $LANG['LAT_DEG']; ?>" />&deg;
			<input id="latmin" style="width:50px;" title="<?php echo $LANG['LAT_MIN']; ?>" />'
			<input id="latsec" style="width:50px;" title="<?php echo $LANG['LAT_SEC']; ?>" />&quot;
			<select id="latns">
				<option><?php echo $LANG['N']; ?></option>
				<option><?php echo $LANG['S']; ?></option>
			</select>
		</div>
		<div>
			<?php echo $LANG['LONG']; ?>:
			<input id="lngdeg" style="width:35px;" title="<?php echo $LANG['LONG_DEG']; ?>" />&deg;
			<input id="lngmin" style="width:50px;" title="<?php echo $LANG['LONG_MIN']; ?>" />'
			<input id="lngsec" style="width:50px;" title="<?php echo $LANG['LONG_SEC']; ?>" />&quot;
			<select id="lngew">
				<option><?php echo $LANG['E']; ?></option>
				<option SELECTED><?php echo $LANG['W']; ?></option>
			</select>
		</div>
		<div style="margin:5px;">
			<button type="button" onclick="insertLatLng(this.form)" ><?php echo $LANG['INSERT_VALUES']; ?></button>
		</div>
	</div>
	<div id="utmAidDiv">
		<?php echo $LANG['ZONE']; ?>: <input id="utmzone" style="width:40px;" /><br/>
		<?php echo $LANG['EAST']; ?>: <input id="utmeast" type="text" style="width:100px;" /><br/>
		<?php echo $LANG['NORTH']; ?>: <input id="utmnorth" type="text" style="width:100px;" /><br/>
		<?php echo $LANG['HEMISPHERE']; ?>: <select id="hemisphere" title="<?php echo $LANG['USE_HEMI_DESIGN']; ?> ">
			<option value="N"><?php echo $LANG['NORTH']; ?></option>
			<option value="S"><?php echo $LANG['SOUTH']; ?></option>
		</select><br/>
		<div style="margin-top:5px;">
			<button type="button" onclick="insertUtm(this.form)" ><?php echo $LANG['INSERT_UTMS']; ?></button>
		</div>
	</div>
	<div id="trsAidDiv">
		<?php echo $LANG['T']; ?><input id="township" style="width:30px;" title="<?php echo $LANG['TOWNSHIP']; ?>" />
		<select id="townshipNS">
			<option><?php echo $LANG['N']; ?></option>
			<option><?php echo $LANG['S']; ?></option>
		</select>&nbsp;&nbsp;&nbsp;&nbsp;
		<?php echo $LANG['R']; ?><input id="range" style="width:30px;" title="<?php echo $LANG['RANGE']; ?>" />
		<select id="rangeEW">
			<option><?php echo $LANG['E']; ?></option>
			<option><?php echo $LANG['W']; ?></option>
		</select><br/>
		<?php echo $LANG['SEC']; ?>:
		<input id="section" style="width:30px;" title="<?php echo $LANG['SECTION']; ?>" />&nbsp;&nbsp;&nbsp;
		<?php echo $LANG['DETAILS']; ?>:
		<input id="secdetails" style="width:90px;" title="<?php echo $LANG['SECTION_DETAILS']; ?>" /><br/>
		<select id="meridian" title="Meridian">
			<option value=""><?php echo $LANG['MERIDIAN_SEL']; ?></option>
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
		<div style="margin:5px;">
			<button  type="button" onclick="insertTRS(this.form)"><?php echo $LANG['INSERT_TRS']; ?></button>
		</div>
	</div>
</div>
<script type="text/javascript">
	function insertLatLng(f) {
		var latDeg = document.getElementById("latdeg").value.trim();
		var latMin = document.getElementById("latmin").value.trim();
		var latSec = document.getElementById("latsec").value.trim();
		var latNS = document.getElementById("latns").value;
		var lngDeg = document.getElementById("lngdeg").value.trim();
		var lngMin = document.getElementById("lngmin").value.trim();
		var lngSec = document.getElementById("lngsec").value.trim();
		var lngEW = document.getElementById("lngew").value;
		if(latDeg && latMin && lngDeg && lngMin){
			if(latMin == "") latMin = 0;
			if(latSec == "") latSec = 0;
			if(lngMin == "") lngMin = 0;
			if(lngSec == "") lngSec = 0;
			if(isNumeric(latDeg) && isNumeric(latMin) && isNumeric(latSec) && isNumeric(lngDeg) && isNumeric(lngMin) && isNumeric(lngSec)){
				if(latDeg < 0 || latDeg > 90){
					alert("<?php echo $LANG['LAT_BETWEEN']; ?>");
				}
				else if(lngDeg < 0 || lngDeg > 180){
					alert("<?php echo $LANG['LONG_BETWEEN']; ?>");
				}
				else if(latMin < 0 || latMin > 60 || lngMin < 0 || lngMin > 60 || latSec < 0 || latSec > 60 || lngSec < 0 || lngSec > 60){
					alert("<?php echo $LANG['MIN_BETWEEN']; ?>");
				}
				else{
					var vcStr = f.verbatimcoordinates.value;
					vcStr = vcStr.replace(/-*\d{2}[°\u00B0]+[NS\d\.\s\'\"-°\u00B0]+[EW;]+/g, "");
					vcStr = vcStr.replace(/^\s+|\s+$/g, "");
					vcStr = vcStr.replace(/^;|;$/g, "");
					if(vcStr != "") vcStr = vcStr + "; ";
					var dmsStr = latDeg + "\u00B0 " + latMin + "' ";
					if(latSec > 0) dmsStr += latSec + '" ';
					dmsStr += latNS + "  " + lngDeg + "\u00B0 " + lngMin + "' ";
					if(lngSec) dmsStr += lngSec + '" ';
					dmsStr += lngEW;
					f.verbatimcoordinates.value = vcStr + dmsStr;
					var latDec = parseInt(latDeg) + (parseFloat(latMin)/60) + (parseFloat(latSec)/3600);
					var lngDec = parseInt(lngDeg) + (parseFloat(lngMin)/60) + (parseFloat(lngSec)/3600);
					if(latNS == "S") latDec = latDec * -1;
					if(lngEW == "W") lngDec = lngDec * -1;
					f.decimallatitude.value = Math.round(latDec*1000000)/1000000;
					f.decimallongitude.value = Math.round(lngDec*1000000)/1000000;

					fieldChanged("decimallatitude");
					fieldChanged("decimallongitude");
					fieldChanged("verbatimcoordinates");
				}
			}
			else{
				alert("<?php echo $LANG['NUMERIC_ONLY']; ?>");
			}
		}
		else{
			alert("<?php echo $LANG['DMS_MUST_VALUE']; ?>");
		}
	}

	function insertUtm(f) {
		var zValue = document.getElementById("utmzone").value.trim();
		var hValue = document.getElementById("hemisphere").value;
		var eValue = document.getElementById("utmeast").value.trim();
		var nValue = document.getElementById("utmnorth").value.trim();
		if(zValue && eValue && nValue){
			if(isNumeric(eValue) && isNumeric(nValue)){
				//Remove prior UTM references from verbatimCoordinates field
				var vcStr = f.verbatimcoordinates.value;
				vcStr = vcStr.replace(/\d{2}.*\d+E\s+\d+N[;\s]*/g, "");
				vcStr = vcStr.replace(/(Northern)|(Southern)/g, "");
				vcStr = vcStr.replace(/^\s+|\s+$/g, "");
				vcStr = vcStr.replace(/^;|;$/g, "");
				//put UTM into verbatimCoordinate field
				if(vcStr != "") vcStr = vcStr + "; ";
				var utmStr = zValue;
				if(isNumeric(zValue)) utmStr = utmStr + hValue;
				utmStr = utmStr + " " + eValue + "E " + nValue + "N ";
				f.verbatimcoordinates.value = vcStr + utmStr;
				//Convert to Lat/Lng values
				var zNum = parseInt(zValue);
				if(isNumeric(zNum)){
					var latLngStr = utm2LatLng(zNum, eValue, nValue, f.geodeticdatum.value, hValue);
					var llArr = latLngStr.split(',');
					if(llArr){
						f.decimallatitude.value = llArr[0];
						f.decimallongitude.value = llArr[1];
					}
				}
				fieldChanged("decimallatitude");
				fieldChanged("decimallongitude");
				fieldChanged("verbatimcoordinates");
			}
			else{
				alert("<?php echo $LANG['EN_NUMERIC']; ?>");
			}
		}
		else{
			alert("<?php echo $LANG['ZEN_NOT_EMPTY']; ?>");
		}
	}

	function insertTRS(f) {
		var township = document.getElementById("township").value.trim();
		var townshipNS = document.getElementById("townshipNS").value.trim();
		var range = document.getElementById("range").value.trim();
		var rangeEW = document.getElementById("rangeEW").value.trim();
		var section = document.getElementById("section").value.trim();
		var secdetails = document.getElementById("secdetails").value.trim();
		var meridian = document.getElementById("meridian").value.trim();

		if(!township || !range){
			alert("<?php echo $LANG['TR_NOT_EMPTY']; ?>");
			return false;
		}
		else if(!isNumeric(township)){
			alert("<?php echo $LANG['NUMERIC_TOWNSHIP']; ?>");
			return false;
		}
		else if(!isNumeric(range)){
			alert("<?php echo $LANG['NUMERIC_RANGE']; ?>");
			return false;
		}
		else if(!isNumeric(section)){
			alert("<?php echo $LANG['NUMERIC_SECTION']; ?>");
			return false;
		}
		else if(section > 36){
			alert("<?php echo $LANG['SECTION_BETWEEN']; ?>");
			return false;
		}
		else{
			//Insert into verbatimCoordinate field
			vCoord = f.verbatimcoordinates;
			if(vCoord.value) vCoord.value = vCoord.value + "; ";
			vCoord.value = vCoord.value + "TRS: T"+township+townshipNS+" R"+range+rangeEW+" sec "+section+" "+secdetails+" "+meridian;
			fieldChanged("verbatimcoordinates");
		}
	}
</script>
