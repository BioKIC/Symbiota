function processGbifOrgKey(f){
	var status = true;
	$("#workingcircle").show();

	var gbifInstOrgKey = f.gbifInstOrgKey.value;
	var portalName = f.portalname.value;
	var collName = f.collname.value;
	var datasetKey = f.datasetKey.value;
	var organizationKey = f.organizationKey.value;
	var installationKey = f.installationKey.value;
	var dwcUri = f.dwcUri.value;

	if(gbifInstOrgKey && organizationKey){
		let submitForm = false;
		if(!installationKey){
			installationKey = createGbifInstallation(gbifInstOrgKey,portalName);
			if(installationKey){
				f.installationKey.value = installationKey;
				submitForm = true;
			}
		}
		if(installationKey){
			if(!datasetKey){
				datasetExists(f);
				if(f.datasetKey.value){
					alert("Dataset already appears to exist. Updating database.");
					submitForm = true;
				}
				else{
					datasetKey = createGbifDataset(installationKey, organizationKey, collName);
					f.datasetKey.value = datasetKey;
					if(datasetKey){
						if(dwcUri) f.endpointKey.value = createGbifEndpoint(datasetKey, dwcUri);
						else alert('Please create/refresh your Darwin Core Archive and try again.');
						submitForm = true;
					}
					else{
						alert('Invalid Organization Key or insufficient permissions. Please recheck your Organization Key and verify that this portal can create datasets for your organization with GBIF.');
					}
				}
			}
		}
		if(submitForm) f.submit();
		status = true;
	}
	else{
		alert('Please enter an Organization Key.');
		status = false;
	}
	$("#workingcircle").hide();
	return status;
}

function createGbifInstallation(gbifOrgKey,collName){
	let action = 'createGbifInstallation';
	let data = JSON.stringify({
		organizationKey: gbifOrgKey,
		title: collName
	});
	let instKey = callGbifCurl(data, action);
	if(!instKey){
		alert("ERROR: Contact administrator, creation of GBIF installation failed using data: "+data);
	}
	return instKey;
}

function createGbifDataset(gbifInstKey,gbifOrgKey,collName){
	let action = 'createGbifDataset';
	let data = JSON.stringify({
		installationKey: gbifInstKey,
		publishingOrganizationKey: gbifOrgKey,
		title: collName
	});
	let retStr = '';
	retStr = callGbifCurl(data,action);
	return retStr;
}

function createGbifEndpoint(gbifDatasetKey,dwcUri){
	let action = "createGbifEndpoint";
	let data = JSON.stringify({
		dwcUri: dwcUri,
		datasetkey: gbifDatasetKey
	});
	let retStr = '';
	retStr = callGbifCurl(data, action);
	if(isNaN(parseFloat(retStr))) retStr = "";
	return retStr;
}

function callGbifCurl(data, action = null){
	let response = "";
	let postbody = {data: data};
	if(action) postbody.action = action;
	let request = new XMLHttpRequest();
	request.open('POST', "rpc/getgbifcurl.php", false);
	request.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
	//request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

	request.onload = function () {
		if (this.status >= 200 && this.status <= 299  ) {
			response = this.response;
		} else {
			alert(`GBIF API RETURNED: ${this.status} ${this.response}`);
		}
	};
	request.onerror = function() {
		alert("ERROR: Something went wrong");
	};
	request.send(JSON.stringify(postbody));
	return response;
}

function datasetExists(f){
	if(f.collid.value != ""){
		let action = "datasetExists";
		let data = JSON.stringify({
			collid: f.collid.value
		});
		let retStr = '';
		retStr = callGbifCurl(data, action);
		if(retStr.indexOf(" ") > -1 || retStr.length < 34 || retStr.length > 40) retStr = "";
		return retStr;
	}
}
