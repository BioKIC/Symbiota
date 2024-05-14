//Table function only
function detectBatchUpdateField(){
	var fieldSelected = document.getElementById('bufieldname').value;
	if(fieldSelected == "processingstatus"){
		var buNewValue = '<select name="bunewvalue">';
		buNewValue += '<option value="unprocessed">Unprocessed</option>';
		buNewValue += '<option value="unprocessed/nlp">Unprocessed/NLP</option>';
		buNewValue += '<option value="stage 1">Stage 1</option>';
		buNewValue += '<option value="stage 2">Stage 2</option>';
		buNewValue += '<option value="stage 3">Stage 3</option>';
		buNewValue += '<option value="pending review-nfn">Pending Review-NfN</option>';
		buNewValue += '<option value="pending review">Pending Review</option>';
		buNewValue += '<option value="expert required">Expert Required</option>';
		buNewValue += '<option value="reviewed">Reviewed</option>';
		buNewValue += '<option value="closed">Closed</option>';
		buNewValue += '<option value="">No Set Status</option>';
		buNewValue += '</select>';
		document.getElementById("bunewvaluediv").innerHTML = buNewValue;
	}
	else if(!$("input[name='bunewvalue']").val()){
		document.getElementById("bunewvaluediv").innerHTML = '<input name="bunewvalue" type="text" value="" />';
	}
}

function submitBatchUpdate(f){
	var fieldName = f.bufieldname.options[f.bufieldname.selectedIndex].value;
	var oldValue = f.buoldvalue.value;
	var newValue = f.bunewvalue.value;
	var buMatch = 0;
	if(f.bumatch[1].checked) buMatch = 1;
	if(!fieldName){
		alert("Please select a target field name");
		return false;
	}
	if(!oldValue && !newValue){
		alert("Please enter a value in the current or new value fields");
		return false;
	}
	if(oldValue == newValue){
		alert("The values within current and new fields cannot be equal to one another");
		return false;
	}

	$.ajax({
		type: "POST",
		url: "rpc/batchupdateverify.php",
		dataType: "json",
		data: { collid: f.collid.value, fieldname: fieldName, oldvalue: oldValue, bumatch: buMatch }
	}).done(function( retCnt ) {
		if(confirm("You are about to update "+retCnt+" records.\nNote that you won't be able to undo this Replace operation!\nDo you want to continue?")){
			f.submit();
		}
	});
}

function toggleSearch(){
	const batchupdatediv = document.getElementById("batchupdatediv");

	if(batchupdatediv){
		document.getElementById("batchupdatediv").style.display = "none";
	}
	toggle("querydiv");
}

function toggleBatchUpdate(){
	const querydiv = document.getElementById("querydiv");
	if(querydiv){
		querydiv.style.display = "none";
	}

	toggle("batchupdatediv");
}
