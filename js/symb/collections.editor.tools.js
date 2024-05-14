function openAssocSppAid(){
	var assocWindow = open("assocsppaid.php","assocaid","resizable=0,width=550,height=150,left=20,top=20");
	if(assocWindow != null){
		if (assocWindow.opener == null) assocWindow.opener = self;
		fieldChanged("associatedtaxa");
		assocWindow.focus();
	}
	else{
		alert("Unable to open associated species tool, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
}

function geoCloneTool(){
	var f = document.fullform;
	if(f.locality.value){
		var url = "../georef/georefclone.php?";
		url = url + "locality=" + f.locality.value;
		url = url + "&country=" + f.country.value;
		url = url + "&state=" + f.stateprovince.value;
		url = url + "&county=" + f.county.value;
		url = url + "&collid=" + f.collid.value;
		cloneWindow=open(url,"geoclonetool","resizable=1,scrollbars=1,toolbar=0,width=1000,height=600,left=20,top=20");
		if(cloneWindow.opener == null) cloneWindow.opener = self;
	}
	else{
		alert("Locality field must have a value to use this function");
		return false;
	} 
}

function toggleCoordDiv(){
	coordObj = document.getElementById("coordAidDiv");
	if(coordObj.style.display == "block"){
		coordObj.style.display = "none";
	}
	else{
		document.getElementById("georefExtraDiv").style.display = "block";
		coordObj.style.display = "block";
	}
}

function toggleCsMode(modeId){ 
	if(modeId == 1){
		document.getElementById("editorCssLink").href = "includes/config/occureditorcrowdsource.css?ver=170201";
		document.getElementById("longtagspan").style.display = "block";
		document.getElementById("shorttagspan").style.display = "none";
	}
	else{
		document.getElementById("editorCssLink").href = "../../css/occureditor.css";
		document.getElementById("longtagspan").style.display = "none";
		document.getElementById("shorttagspan").style.display = "block";
	}
}

function openMappingAid() {
	var f = document.fullform;
	var latDef = f.decimallatitude.value;
	var lngDef = f.decimallongitude.value;
	var errRadius = f.coordinateuncertaintyinmeters.value;
	var zoom = 5;
	if(latDef && lngDef) zoom = 11;
	var mapWindow=open("../tools/mappointaid.php","mappointaid","resizable=0,width=900,height=700,left=20,top=20");
	if(mapWindow != null){
		if (mapWindow.opener == null) mapWindow.opener = self;
		mapWindow.focus();
	}
	else{
		alert("Unable to open map, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
}

function openMappingPolyAid() {
   var mapWindow = open(
      "../tools/mapcoordaid.php?&map_mode_strict=true&mapmode=polygon",
      "polygon",
      "resizable=0,width=900,height=630,left=20,top=20"
   );
	if(mapWindow != null){
		if (mapWindow.opener == null) mapWindow.opener = self;
		mapWindow.focus();
	}
	else{
		alert("Unable to open map, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
	}
}

function geoLocateLocality(){
	var f = document.fullform;
	var country = encodeURIComponent(f.country.value);
	var state = encodeURIComponent(f.stateprovince.value);
	if(!state) state = "unknown";
	var county = encodeURIComponent(f.county.value);
	if(!county) county = "unknown";
	var municipality = encodeURIComponent(f.municipality.value);
	if(!municipality) municipality = "unknown";
	var locality = encodeURIComponent(f.locality.value);
	if(!locality){
		locality = country+"; "+state+"; "+county+"; "+municipality;
	}
	if(f.verbatimcoordinates.value) locality = locality + "; " + encodeURIComponent(f.verbatimcoordinates.value);
	var decLat = f.decimallatitude.value;
	var decLng = f.decimallongitude.value;
	var uncertainty = f.coordinateuncertaintyinmeters.value;

	if(!country){
		alert("Country is blank and it is a required field for GeoLocate");
	}
	else if(!locality){
		alert("Record does not contain any verbatim locality details for GeoLocate");
	}
	else{
		geolocWindow=open("../georef/geolocate.php?country="+country+"&state="+state+"&county="+county+"&locality="+locality+"&declat="+decLat+"&declng="+decLng+"&uncertainty="+uncertainty,"geoloctool","resizable=1,scrollbars=1,toolbar=0,width=1050,height=700,left=20,top=20");
		if(geolocWindow.opener == null){
			geolocWindow.opener = self;
		}
		geolocWindow.focus();
	}
}

function geoLocateUpdateCoord(latValue,lngValue,coordErrValue, footprintWKT){
	document.getElementById("georefExtraDiv").style.display = "block";

	var f = document.fullform;
	f.decimallatitude.value = latValue;
	f.decimallongitude.value = lngValue;
	if(!isNumeric(coordErrValue)) coordErrValue = "";
	f.coordinateuncertaintyinmeters.value = coordErrValue;
	if(footprintWKT.length > 0){
		if(footprintWKT == "Unavailable") footprintWKT = "";
		footprintWKT = validatePolygon(footprintWKT);
		if(footprintWKT.length > 65000) footprintWKT = "";  //WKT footprint is too large to save in the database
		else if(footprintWKT.indexOf("NaN") > 0) footprintWKT = "";
		f.footprintwkt.value = footprintWKT;
		fieldChanged('footprintwkt');
	}
	f.georeferencesources.value = "GeoLocate";
	f.geodeticdatum.value = "WGS84";

	verifyDecimalLatitude(f);
	fieldChanged('decimallatitude');
	verifyDecimalLongitude(f);
	fieldChanged('decimallongitude');
	//verifyCoordinates(f);
	f.coordinateuncertaintyinmeters.onchange();
	f.georeferencesources.onchange();
	f.geodeticdatum.onchange();
	//f.georeferenceverificationstatus.value = "reviewed - high confidence";
	//f.georeferenceverificationstatus.onchange();
	document.getElementById("saveEditsButton").disabled = false;
}

//Duplicate record searches
function searchCatalogNumber(f,verbose){
	var cnValue = f.catalognumber.value;
	if(cnValue){
		var occid = f.occid.value;
		if(verbose){
			document.getElementById("dupeMsgDiv").style.display = "block";
			document.getElementById("dupesearch").style.display = "block";
			document.getElementById("dupenone").style.display = "none";
		}

		$.ajax({
			type: "POST",
			url: "rpc/dupequerycatnum.php",
			data: { catnum: cnValue, collid: f.collid.value, occid: f.occid.value }
		}).done(function( msg ) {
			if(msg.trim()){
				if(confirm("Record(s) of same catalog number already exists. Do you want to view this record?")){
					var occWindow=open("dupesearch.php?occidquery=catnu:"+msg+"&collid="+f.collid.value+"&curoccid="+occid,"occsearch","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
					if(occWindow != null){
						if (occWindow.opener == null) occWindow.opener = self;
						occWindow.focus();
					}
					else{
						alert("Unable to display record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
					}
				}
				if(verbose){
					document.getElementById("dupesearch").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
				}
				return true;
			}
			else{
				if(verbose){
					document.getElementById("dupesearch").style.display = "none";
					document.getElementById("dupenone").style.display = "block";
					setTimeout(function () { 
						document.getElementById("dupenone").style.display = "none";
						document.getElementById("dupeMsgDiv").style.display = "none";
						}, 3000);
				}
				return false;
			}
		});
	}
}

function searchOtherCatalogNumbers(inputElem){
	var ocnValue = inputElem.value;
	var f = inputElem.form;
	if(ocnValue){
		document.getElementById("dupeMsgDiv").style.display = "block";
		document.getElementById("dupesearch").style.display = "block";
		document.getElementById("dupenone").style.display = "none";
		$.ajax({
			type: "POST",
			url: "rpc/dupequeryothercatnum.php",
			data: { othercatnum: ocnValue, collid: f.collid.value, occid: f.occid.value }
			}).done(function( msg ) {
				if(msg.length > 6){
					if(confirm("Record(s) using the same identifier already exists. Do you want to view this record?")){
						var occWindow=open("dupesearch.php?occidquery="+msg+"&collid="+f.collid.value+"&curoccid="+f.occid.value,"occsearch","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
						if(occWindow != null){
							if (occWindow.opener == null) occWindow.opener = self;
							occWindow.focus();
						}
						else{
							alert("Unable to show record, which is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
						}
					}						
					document.getElementById("dupesearch").style.display = "none";
					document.getElementById("dupeMsgDiv").style.display = "none";
				}
				else{
					document.getElementById("dupesearch").style.display = "none";
					document.getElementById("dupenone").style.display = "block";
					setTimeout(function () { 
						document.getElementById("dupenone").style.display = "none";
						document.getElementById("dupeMsgDiv").style.display = "none";
						}, 3000);
				}
			});
	}
}

//Duplicate specimen search
function searchDupes(f,silent){
	var cNameIn = f.recordedby.value;
	var cNumIn = f.recordnumber.value;
	var cDateIn = f.eventdate.value;
	var ometidIn = ""; var exsNumberIn = "";
	if(f.ometid){
		ometidIn = f.ometid.value;
		exsNumberIn = f.exsnumber.value;
	}
	var currOccidIn = f.occid.value;

	if((!cNameIn || (!cNumIn && !cDateIn)) && (!ometidIn || !exsNumberIn)){
		if(!silent) alert("Criteria not complete for duplicate search (collector name, number, date, or exsiccati");
		return false;
	}

	document.getElementById("dupeMsgDiv").style.display = "block";
	document.getElementById("dupesearch").style.display = "block";
	document.getElementById("dupenone").style.display = "none";

	$.ajax({
		type: "POST",
		url: "rpc/dupequery.php",
		data: { cname: cNameIn, cnum: cNumIn, cdate: cDateIn, ometid: ometidIn, exsnumber: exsNumberIn, curoccid: currOccidIn }
	}).done(function( msg ) {
		if(msg){
			var dupOccWindow = open("dupesearch.php?occidquery="+msg+"&collid="+f.collid.value+"&curoccid="+currOccidIn,"occsearch","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
			if(dupOccWindow != null){
				if(dupOccWindow.opener == null) dupOccWindow.opener = self;
				dupOccWindow.focus();
				document.getElementById("dupesearch").style.display = "none";
				document.getElementById("dupeMsgDiv").style.display = "none";
			}
			else{
				alert("Duplicate found but unable to display. This is likely due to your browser blocking popups. Please adjust your browser settings to allow popups from this website.");
				document.getElementById("dupeMsgDiv").style.display = "none";
				document.getElementById("dupesearch").style.display = "none";
			}
		}
		else{
			document.getElementById("dupesearch").style.display = "none";
			document.getElementById("dupenone").style.display = "block";
			setTimeout(function () { 
				document.getElementById("dupenone").style.display = "none";
				document.getElementById("dupeMsgDiv").style.display = "none";
				}, 5000);
		}
	});
}

function autoDupeSearch(){
	var f = document.fullform;
	if(f.autodupe && f.autodupe.checked == true){
		searchDupes(f,true);
	}
}
