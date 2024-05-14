$(document).ready(function () {
  $("#acceptedstr").autocomplete({
    source: "rpc/getacceptedsuggest.php",
    focus: function (event, ui) {
      $("#tidaccepted").val("");
    },
    select: function (event, ui) {
      if (ui.item) $("#tidaccepted").val(ui.item.id);
    },
    change: function (event, ui) {
      if (!$("#tidaccepted").val()) {
        alert(
          "You must select a name from the list. If accepted name is not in the list, it needs to be added or it is in the system as a non-accepted synonym"
        );
      }
    },
    minLength: 2,
    autoFocus: true,
  });

  $("#parentname").autocomplete({
    source: function (request, response) {
      $.getJSON(
        "rpc/gettaxasuggest.php",
        { term: request.term, rhigh: $("#rankid").val() },
        response
      );
    },
    focus: function (event, ui) {
      $("#parenttid").val("");
    },
    select: function (event, ui) {
      if (ui.item) $("#parenttid").val(ui.item.id);
    },
    change: function (event, ui) {
      if (!$("#parenttid").val()) {
        alert(
          "You must select a name from the list. If parent name is not in the list, it may need to be added"
        );
      }
    },
    minLength: 2,
    autoFocus: true,
  });
});

function verifyLoadForm(f) {
  if (f.sciname.value == "") {
    alert("Scientific Name field required.");
    return false;
  }
  if (f.unitname1.value == "") {
    alert("Unit Name 1 (genus or uninomial) field required.");
    return false;
  }
  var rankId = f.rankid.value;
  if (rankId == "") {
    alert("Taxon rank field required.");
    return false;
  }
  if (f.parentname.value == "" && rankId > "10") {
    alert("Parent taxon required");
    return false;
  }
  if (f.parenttid.value == "" && rankId > "10") {
    alert(
      "Parent identifier is not set! Make sure to select parent taxon from the list"
    );
    return false;
  }

  //If name is not accepted, verify accetped name
  var accStatusObj = f.acceptstatus;
  if (accStatusObj[0].checked == false) {
    if (f.acceptedstr.value == "") {
      alert("Accepted name needs to have a value");
      return false;
    }
  }

  return true;
}

function parseName(f){
	let sciNameInput = f.sciname.value;
	sciNameInput = sciNameInput.replace(/^\s+|\s+$/g,"");
	f.reset();
	let sciNameArr = new Array(); 
	sciNameArr = sciNameInput.split(' ');
	let activeIndex = 0;
	let rankId = "";

	if(sciNameArr[activeIndex].length == 1){
		//Is a generic hybrid or extinct
		f.unitind1.value = sciNameArr[activeIndex];
		if(sciNameArr[activeIndex].toLowerCase() == "x" || sciNameArr[activeIndex] == "×"){
			f.unitind1.selectedIndex = 1;
		}
		else if(sciNameArr[activeIndex].toLowerCase() == "†"){
			f.unitind1.selectedIndex = 2;
		}
		activeIndex = 1;
	}
	f.unitname1.value = sciNameArr[activeIndex];
	activeIndex = activeIndex + 1;
	if(sciNameArr.length > activeIndex){
		if(sciNameArr[activeIndex].length == 1){
			//Is a hybrid
			if(sciNameArr[activeIndex].toLowerCase() == "x" || sciNameArr[activeIndex] == "×"){
				f.unitind2.selectedIndex = 1;
			}
			activeIndex = activeIndex + 1;
		}
		if(sciNameArr[activeIndex].substring(0, 1) == "(" && sciNameArr[activeIndex].substring(sciNameArr[activeIndex].length - 1) == ")"){
			//active unit is a subgeneric designation, append to unitname1
			f.unitname1.value = f.unitname1.value + " " + sciNameArr[activeIndex];
			activeIndex = activeIndex + 1;
			rankId = 190;
		}
		if(sciNameArr.length > activeIndex){
			f.unitname2.value = sciNameArr[activeIndex];
		}
		activeIndex = activeIndex + 1;
	}
	if(sciNameArr.length > activeIndex){
		let subjectUnit = sciNameArr[activeIndex];
		if(subjectUnit == 'ssp.') subjectUnit = 'subsp.';
		if(subjectUnit == 'fo.') subjectUnit = 'f.';
		if(subjectUnit == "subsp." || subjectUnit == "var." || subjectUnit == "f."){
			f.unitind3.value = subjectUnit;
			f.unitname3.value = sciNameArr[activeIndex + 1];
			activeIndex = activeIndex + 2;
		}
		else if(sciNameArr[activeIndex].length == 1){
			f.unitind3.value = sciNameArr[activeIndex];
			activeIndex = activeIndex + 1;
			while(sciNameArr.length > activeIndex){
				f.unitname3.value = (f.unitname3.value + " " + sciNameArr[activeIndex]).trim();
				activeIndex = activeIndex + 1;
			}			
		}
		else{
			let firstChar = sciNameArr[activeIndex].substring(0, 1);
			if(firstChar != firstChar.toUpperCase()){
				f.unitname3.value = sciNameArr[activeIndex];
				activeIndex = activeIndex + 1;
			}
		}
	}
	let author = '';
	while(sciNameArr.length > activeIndex){
		//Place remain taxon units into the author field
		author = author + " " + sciNameArr[activeIndex];
		activeIndex = activeIndex + 1;
	}
	f.author.value = author.trim();
	let unitName1 = f.unitname1.value;
	//If rankid is not set, determine rank 
	if(f.unitname2.value == ""){
		if(rankId == "" && unitName1.length > 4){
			if(unitName1.indexOf("aceae") == (unitName1.length - 5) || unitName1.indexOf("idae") == (unitName1.length - 4)){
				rankId = 140;
			}
			else if(unitName1.indexOf("oideae") == (unitName1.length - 6) || unitName1.indexOf("inae") == (unitName1.length - 4)){
				rankId = 150;
			}
			else if(unitName1.indexOf("ineae") == (unitName1.length - 5)){
				rankId = 110;
			}
			else if(unitName1.indexOf("ales") == (unitName1.length - 4)){
				rankId = 100;
			}
		}
	}
	else{
		rankId = 220;
		if(f.unitname3.value != ""){
			rankId = 230;
			if(f.unitind3.value == "var.") rankId = 240;
			else if(f.unitind3.value == "f.") rankId = 260;
			else if(f.unitind3.value == "×") rankId = 220;
		}
	}
	//Deal with problematic subgeneric ranks 
	let parentName = "";
	if(unitName1.indexOf("(") > -1){
		if(unitName1.substring(0 , 1) == "(" && unitName1.substring(unitName1.length - 1) == ")"){
			unitName1 = unitName1.substring( 1, unitName1.length - 1) + " " + unitName1;
			f.unitname1.value = unitName1;
			rankId = 190;
		}
		if(rankId == 190){
			parentName = unitName1.substring(0, unitName1.indexOf("(")).trim();
		}
		else if(rankId > 190){
			if(rankId == 220) parentName = unitName1;
			f.unitname1.value = unitName1.substring(0, unitName1.indexOf("(")).trim();
		}
	}
	f.rankid.value = rankId;
	if(unitName1.substring(0, 1) == "×" || unitName1.substring(0, 1) == "†"){
		if(f.unitind1.value == ""){
			if(unitName1.substring(0, 1) == "×") f.unitind1.selectedIndex = 1;
			if(unitName1.substring(0, 1) == "†") f.unitind1.selectedIndex = 2;
		}
		f.unitname1.value = f.unitname1.value.substring(1);
	}
	if(f.unitname2.value.substring(0, 1) == "×"){
		if(f.unitind2.value == ""){
			if(f.unitname2.value.substring(0, 1) == "×") f.unitind2.selectedIndex = 1;
		}
		f.unitname2.value = f.unitname2.value.substring(1);
	}
	if(parentName == ""){
		//Set parent name 
		if(rankId > 180){
			if(rankId == 220) parentName = f.unitname1.value; 
			else if(rankId > 220) parentName = f.unitname1.value + " " + f.unitname2.value; 
		}
	}
	if(parentName != "") setParent(parentName, f.unitind1.value);
	updateFullname(f);
}

function setParent(parentName, unitind1) {
  $.ajax({
    type: "POST",
    url: "rpc/gettid.php",
    async: true,
    data: { sciname: parentName },
  }).done(function (msg) {
    if (msg == 0) {
      if (!unitind1)
        alert(
          "Parent taxon '" +
            parentName +
            "' does not exist. Please first add parent to system."
        );
      else {
        setParent(unitind1 + " " + parentName, "");
      }
    } else {
      if (msg.indexOf(",") == -1) {
        document.getElementById("parentname").value = parentName;
        document.getElementById("parenttid").value = msg;
      } else
        alert(
          "Parent taxon '" +
            parentName +
            "' is matching two different names in the thesaurus. Please select taxon with the correct author."
        );
    }
  });
}

function updateFullname(f){
	let sciname = f.unitind1.value + f.unitname1.value + " " + f.unitind2.value + f.unitname2.value + " ";
	if(f.unitname3.value){
		sciname = sciname + (f.unitind3.value + " " + f.unitname3.value).trim();
	}
	f.sciname.value = sciname.trim();
	checkNameExistence(f);
}

function checkNameExistence(f) {
  $.ajax({
    type: "POST",
    url: "rpc/gettid.php",
    async: false,
    data: {
      sciname: f.sciname.value,
      rankid: f.rankid.value,
      author: f.author.value,
    },
  }).done(function (msg) {
    if (msg != "0") {
      alert(
        "Taxon " +
          f.sciname.value +
          " " +
          f.author.value +
          " (" +
          msg +
          ") already exists in database"
      );
      return false;
    }
  });
}

function acceptanceChanged(f) {
  var accStatusObj = f.acceptstatus;
  if (accStatusObj[0].checked)
    document.getElementById("accdiv").style.display = "none";
  else document.getElementById("accdiv").style.display = "block";
}

// listener for taxon rank

document.getElementById("rankid").addEventListener("change", function () {
  const selectedValue = this.value; // Get the chosen value
  $rankId = selectedValue;

  const div1 = document.getElementById("div1hide");
  const div2 = document.getElementById("div2hide");
  const label = document.getElementById("unitind1label");

  if (selectedValue > 150) {
    div1.style.display = "block";
    div2.style.display = "block";
  } else {
    div1.style.display = "none";
    div2.style.display = "none";
  }
  if (selectedValue <= 180) {
    // Get the name of selected option
    const selectedOption = this.options[this.selectedIndex];
    const selectedOptionText = selectedOption.textContent.trim();

    // Set the label for "UnitName1" based on the selected option text
    label.textContent = selectedOptionText + " Name";
  } else {
    label.textContent = "Genus Name";
  }
});
