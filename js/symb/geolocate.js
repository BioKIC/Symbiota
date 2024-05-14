var cogeUrl = "https://coge.geo-locate.org/symbiota/";
var t;
var t2;
var datasetList = {};

//CoGe GeoLocate functions
function cogeCheckAuthentication(){
	//$("#cogeStatus").html("");
	$("#coge-status").css('color', 'orange');
	$("#coge-status").html("Checking status...");
	$("#coge-importcomplete").hide();
	$("#coge-importstatus").html("");
	$("#coge-dwcalink").html("");
	$("#coge-guid").html("");
	$("input[name=cogename]").val("");
	$("input[name=cogedescr]").val("");
	
	$.ajax({
		type: "GET",
		url: cogeUrl,
		crossDomain: true,
		xhrFields: { withCredentials: true },
		dataType: 'json'
	}).done(function( response ) {
		var result = response.result;
		if(result == "authentication required"){
			$("#coge-status").html("Unauthorized");
			$("#coge-status").css("color", "red");
			$("#builddwcabutton").prop("disabled",true);
			$("#coge-commlist").html('<span style="color:orange;">Login to GeoLocate and click check status button to list available communities</span>');
		}
		else{
			clearInterval(t);
			$("#coge-status").css('color', 'green');
			$("#coge-status").html("Connected");
			cogeGetUserCommunityList();
		}
	}).fail(function(jqXHR, textStatus, errorThrown ){
		clearInterval(t);
		$("#coge-status").html("Unauthorized");
		$("#coge-status").css("color", "red");
		alert( "ERROR: it may be that GeoLocate has not been configured to automatically accept files from this Symbiota portal. Please contact your portal adminstrator to setup automated GeoLocate submissions. " );
	});
}

function startAuthMonitoring(){
	//every 3 seconds, check authenication
	t = setInterval(cogeCheckAuthentication,3000);
}

function cogePublishDwca(f){
	if($("#countdiv").html() == 0){
		alert("No records exist matching search criteria");
		return false;
	}
	if($('input[name=cogecomm]:checked').length == 0) {
		alert("You must select a target community");
		return false;
	}
	if(f.cogename.value == ""){
		alert("You must enter a data source identifier");
		return false;
	}
	/*
	var rpcCall = "rpc/coge_build_dwca.php?collid="+f.collid.value+"&ps="+f.processingstatus.value+"&cf1="+f.customfield1.value+"&ct1="+f.customtype1.value+"&cv1="+f.customvalue1.value;
	rpcCall = rpcCall + "&cf2="+f.customfield2.value+"&ct2="+f.customtype2.value+"&cv2="+f.customvalue2.value+"&cf3="+f.customfield3.value+"&ct3="+f.customtype3.value+"&cv3="+f.customvalue3.value;
	rpcCall = rpcCall + "&cogecomm="+f.cogecomm.value+"&cogename="+f.cogename.value+"&cogedescr="+f.cogedescr.value;
	alert(rpcCall);
	*/
	$("#builddwcabutton").prop("disabled",true);
	$("#coge-download").show();
	$.ajax({
		type: "POST",
		url: "rpc/coge_build_dwca.php",
		dataType: "json",
		data: { 
			collid: f.collid.value, 
			ps: f.processingstatus.value, 
			cf1: f.customfield1.value, 
			ct1: f.customtype1.value,
			cv1: f.customvalue1.value,
			cf2: f.customfield2.value, 
			ct2: f.customtype2.value,
			cv2: f.customvalue2.value,
			cf3: f.customfield3.value, 
			ct3: f.customtype3.value,
			cv3: f.customvalue3.value,
			cogecomm: f.cogecomm.value,
			cogename: f.cogename.value,
			cogedescr: f.cogedescr.value
		}
	}).done(function( response ) {
		var result = response.result;
		$("#coge-download").hide();
		if(result.status == "ERROR"){
			alert(result.message);
		}
		else{
			var dwcaPath =  result.path;
			if(dwcaPath){
				$("#coge-dwcalink").html("<u>Data package (DwC-Archive)</u>: <a href='"+dwcaPath+"'>"+dwcaPath+"</a>");
				cogeSubmitData(dwcaPath);
			}
			else{

			}
		}
	});
}

function cogeUpdateCount(formObj){
	var f = formObj.form;
	var objName = formObj.name;
	if(objName == "customtype1" || objName == "customvalue1"){
		if(f.customfield1.value == '') return false;
		if(f.customtype1.value == "EQUALS" || f.customtype1.value == "STARTS_WITH" || f.customtype1.value == "LIKE" || f.customtype1.value == "NOT_LIKE" || f.customtype1.value == "LIKE"){
			if(objName == "customtype1" && f.customvalue1.value == '') return false;
		}
	}
	if(objName == "customtype2" || objName == "customvalue2"){
		if(f.customfield2.value == '') return false;
		if(f.customtype2.value == "EQUALS" || f.customtype2.value == "STARTS_WITH" || f.customtype2.value == "LIKE" || f.customtype2.value == "NOT_LIKE" || f.customtype2.value == "LIKE"){
			if(objName == "customtype2" && f.customvalue2.value == '') return false;
		}
	}
	if(objName == "customtype3" || objName == "customvalue3"){
		if(f.customfield3.value == '') return false;
		if(f.customtype3.value == "EQUALS" || f.customtype3.value == "STARTS_WITH" || f.customtype3.value == "LIKE" || f.customtype3.value == "NOT_LIKE" || f.customtype3.value == "LIKE"){
			if(objName == "customtype3" && f.customvalue3.value == '') return false;
		}
	}
	$("#recalspan").show();
	//alert("rpc/coge_getCount.php?collid="+f.collid.value+"&ps="+f.processingstatus.value+"&cf1="+f.customfield1.value+"&ct1="+f.customtype1.value+"&cv1="+f.customvalue1.value+"&cf2="+f.customfield2.value+"&ct2="+f.customtype2.value+"&cv2="+f.customvalue2.value+"&cf3="+f.customfield3.value+"&ct3="+f.customtype3.value+"&cv3="+f.customvalue3.value);
	$.ajax({
		type: "POST",
		url: "rpc/coge_getCount.php",
		dataType: "json",
		data: { 
			collid: f.collid.value, 
			ps: f.processingstatus.value, 
			cf1: f.customfield1.value, 
			ct1: f.customtype1.value,
			cv1: f.customvalue1.value,
			cf2: f.customfield2.value, 
			ct2: f.customtype2.value,
			cv2: f.customvalue2.value,
			cf3: f.customfield3.value, 
			ct3: f.customtype3.value,
			cv3: f.customvalue3.value
		}
	}).done(function( response ) {
		if(response == 0) f.builddwcabutton.disalbed = true;
		$("#countdiv").html(response);
		$("#recalspan").hide();
	});
}

function cogeSubmitData(dwcaPath){
	$("#coge-push2coge").show();
	$.ajax({
		type: "GET",
		url: cogeUrl,
		crossDomain: true,
		xhrFields: { withCredentials: true },
		dataType: 'json',
		data: { t: "import", q: dwcaPath }
	}).done(function( response ) {
		//{"result":{"datasourceId":"7ab8ffb8-032a-4f7a-8968-a012ce287c2d"}}
		var result = response.result;
		var dataSourceGuid = result.datasourceId;
		if(dataSourceGuid){
			$("#coge-push2coge").hide();
			$("#coge-guid").html("<u>Dataset identifier</u>: " + dataSourceGuid);
			window.setTimeout(cogeCheckStatus(dataSourceGuid),3000);
		}
	});
}		

function cogeCheckStatus(id){
	$.ajax({
		type: "GET",
		url: cogeUrl,
		crossDomain: true,
		xhrFields: { withCredentials: true },
		dataType: 'json',
		data: { t: "importstatus", q: id }
	}).done(function( response ) {
		//{"result":{"importProgess":{"state":"portal interation required"}}}
		var result = response.result;
		if(result == "authentication required"){
			$("#coge-status").html("Unauthorized");
			$("#coge-status").css("color", "red");
			alert("Authentication Required! Login may have timed out, please login back into GeoLocate website");
			t2 = setInterval(cogeCheckStatus(id),3000);
		}
		else {
			clearInterval(t2);
			var iStatus = result.importStatus.state;
			if(iStatus == "portal_interaction_required"){
				$("#coge-importcomplete").show();
				//Default import status will be displayed in #coge-importstatus
				$("#coge-importstatus").show();
				cogeGetUserCommunityList();
			}
			else if(iStatus == "ready"){
				$("#coge-importstatus").html("Dataset ready for processing");
				$("#coge-importstatus").show();
			}
			else if(iStatus == "unspecified"){
				$("#coge-importstatus").html("Unbable to locate dataset");
				$("#coge-importstatus").show();
			}
			else if(iStatus == "retrieval" || iStatus == "extraction" || iStatus == "discovery" || iStatus == "datasource_creation"){
				//Import is still processing
				window.setTimeout(cogeCheckStatus(id),3000);
			}
			else{
				alert(iStatus);
				$("#coge-importstatus").html("Unknown Error: Visit GeoLocate for details");
				$("#coge-importstatus").show();
			}
		}
	});
}

function cogeGetUserCommunityList(){
	$.ajax({
		type: "GET",
		url: cogeUrl,
		crossDomain: true,
		xhrFields: { withCredentials: true },
		dataType: 'json',
		data: { t: "comlist" }
	}).done(function( response ) {
		/*
		{"result":[{"name":"Phoenix","description":"General Areas around Phoenix that need coordinates","role":"Owner",
		"dataSources":[{"name":"Fabaceae test","description":"","uploadedBy":"egbott","uploadType":"csv"},
		{"guid":"95b7fdb7-8667-469f-88c5-ad1bf3a6ea29","name":"Arizona Fabaceae","description":"","uploadedBy":"egbott","uploadType":"Symbiota (DwCA)"},
		{"guid":"19e68aae-b870-4f81-aa08-ab17a827985e","name":"Fabaceae","description":"test upload of Fabaceae","uploadedBy":"egbott","uploadType":"Symbiota (DwCA)"}]}]}
		*/
		var result = response.result;
		if(result == "authentication required"){
			alert("Authentication Required! Login may have timed out, please login back into GeoLocate website");
		}
		else{
			$("#coge-communities").show();
			var htmlOut = "";
			for(var i in result){
				var role = result[i].role;
				htmlOut = htmlOut + '<div style="margin:5px">';
				var name = result[i].name;
				var subText = 'title="You are not able to submit datasets with User level permissions. Contact the GeoLocate project administrator to adjust your permissions" disabled';
				if(role == "Owner" || role == "Admin" || role == "Reviewer" || role == "Contributor" || role == "Contributor_Reviewer"){
					subText = 'onclick="verifyDataSourceIdentifier(this.form)"';
					$("#builddwcabutton").prop("disabled",false);
					$("#coge-fieldDiv").show();
				}
				htmlOut = htmlOut + '<input name="cogecomm" type="radio" value="'+name+'" ' + subText + ' />';
				htmlOut = htmlOut + "<u>"+name+"</u>";
				htmlOut = htmlOut + " (" + role + ")";
				var descr = result[i].description;
				if(descr) htmlOut = htmlOut + ": " + descr;
				var dataSources = result[i].dataSources;
				if(dataSources){
					htmlOut = htmlOut + '<fieldset style="margin:0px 30px;padding:10px"><legend><b>Datasets</b></legend>';
					datasetList[name] = {};
					for(var j in dataSources){
						datasetList[name][j] = dataSources[j].name;
						htmlOut = htmlOut + "<div><b>" + dataSources[j].name + "</b> (";
						
						var uploadType = dataSources[j].uploadType;
						if(uploadType == "csv"){
							htmlOut = htmlOut + "manual CSV upload";
						}
						else{
							if(uploadType == "Symbiota (DwCA)"){
								var guid = dataSources[j].guid;
								htmlOut = htmlOut + 'Symbiota upload [<a href="#" onclick="cogeCheckGeorefStatus(\''+guid+'\');return false;">check status</a>]';
							}
						}
						var uploadedBy = dataSources[j].uploadedBy;
						if(uploadedBy) htmlOut = htmlOut + "; " + uploadedBy;

						htmlOut = htmlOut + ")";
						var dsDescr = dataSources[j].description;
						if(dsDescr) htmlOut = htmlOut + ": " + dsDescr;
						htmlOut = htmlOut + "</div>";
						if(uploadType == "Symbiota (DwCA)"){
							htmlOut = htmlOut + '<div id="coge-'+guid+'" style="margin-left:10px;"></div>';
						}
					} 
					htmlOut = htmlOut + '</fieldset>';
				}
				htmlOut = htmlOut + '</div>';
			}
			if(htmlOut == "") htmlOut = "<div>There appears to be no projects currently associated with your GeoLocate user profile</div>";
			$("#coge-commlist").html(htmlOut);
		}
	});
}

function cogeCheckGeorefStatus(id){
	$.ajax({
		type: "GET",
		url: cogeUrl,
		crossDomain: true,
		xhrFields: { withCredentials: true },
		dataType: 'json',
		data: { t: "dsstatus", q: id }
	}).done(function( response ) {
		//{"result":{"datasource":"0a289c73-5317-45f1-9486-656597f98626","stats":{"specimens":{"total":48004,"corrected":774,"skipped":0},"localities":{"total":18876,"corrected":226,"skipped":0}}}}
		var result = response.result;
		if(result == "authentication required"){
			alert("Authentication Required! Login may have timed out, please login back into GeoLocate website");
		}
		else{
			var specStats = result.stats.specimens;
			var localStats = result.stats.localities;
			var htmlOut = '<div style="border:1px solid black">';
			htmlOut = htmlOut + "<div>Specimens: total: " + specStats.total + ", corrected: " + specStats.corrected + ", skipped: " + specStats.skipped;
			if(specStats.total == 0 && specStats.corrected == 0 && specStats.skipped == 0){
				htmlOut = htmlOut + "<span style=\"margin-left:30px;color:orange;\">GeoLocate interaction may be required to activate data</span>";
			}
			htmlOut = htmlOut + "</div>";
			htmlOut = htmlOut + "<div>Localities: total: " + specStats.total + ", corrected: " + specStats.corrected + ", skipped: " + specStats.skipped + "</div>";
			htmlOut = htmlOut + "</div>";
			$("#coge-"+id).html(htmlOut);
		}
	});
}

function verifyDataSourceIdentifier(f){
	var newProjName = $("input[name=cogename]").val();
	if(newProjName != "" && $('input[name=cogecomm]:checked').length > 0){
		if($('input[name=cogecomm]:checked').val() in datasetList){
			var projList = datasetList[$('input[name=cogecomm]:checked').val()];
			for(var h in projList){
				if(projList[h] == newProjName){
					alert("Dataset name already exists for selected community");
					return false;
				}
			}
		}
	}
}