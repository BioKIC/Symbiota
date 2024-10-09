$(document).ready(function () {
  const currentRankId = Number(document.getElementById("rankid").value);
  showOnlyRelevantFields(currentRankId);

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

  document.getElementById("rankid").addEventListener("change", function () {
    const selectedValue = Number(this.value); // Get the chosen value
    showOnlyRelevantFields(selectedValue);
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

function parseName(f) {
  if (f.rankid.value === "300") {
    return;
  }
  let sciNameInput = f.sciname.value;
  sciNameInput = sciNameInput.replace(/^\s+|\s+$/g, "");
  f.reset();
  let sciNameArr = new Array();
  sciNameArr = sciNameInput.split(" ");
  let activeIndex = 0;
  let rankId = "";

  if (sciNameArr[activeIndex].length == 1) {
    //Is a generic hybrid or extinct
    f.unitind1.value = sciNameArr[activeIndex];
    if (
      sciNameArr[activeIndex].toLowerCase() == "x" ||
      sciNameArr[activeIndex] == "×"
    ) {
      f.unitind1.selectedIndex = 1;
    } else if (sciNameArr[activeIndex].toLowerCase() == "†") {
      f.unitind1.selectedIndex = 2;
    }
    activeIndex = 1;
  }
  f.unitname1.value = sciNameArr[activeIndex];
  activeIndex = activeIndex + 1;
  if (sciNameArr.length > activeIndex) {
    if (sciNameArr[activeIndex].length == 1) {
      //Is a hybrid
      if (
        sciNameArr[activeIndex].toLowerCase() == "x" ||
        sciNameArr[activeIndex] == "×"
      ) {
        f.unitind2.selectedIndex = 1;
      }
      activeIndex = activeIndex + 1;
    }
    if (
      sciNameArr[activeIndex].substring(0, 1) == "(" &&
      sciNameArr[activeIndex].substring(sciNameArr[activeIndex].length - 1) ==
        ")"
    ) {
      //active unit is a subgeneric designation, append to unitname1
      f.unitname1.value = f.unitname1.value + " " + sciNameArr[activeIndex];
      activeIndex = activeIndex + 1;
      rankId = 190;
    }
    if (sciNameArr.length > activeIndex) {
      f.unitname2.value = sciNameArr[activeIndex];
    }
    activeIndex = activeIndex + 1;
  }
  if (sciNameArr.length > activeIndex) {
    let subjectUnit = sciNameArr[activeIndex];
    if (subjectUnit == "ssp.") subjectUnit = "subsp.";
    if (subjectUnit == "fo.") subjectUnit = "f.";
    if (
      subjectUnit == "subsp." ||
      subjectUnit == "var." ||
      subjectUnit == "f."
    ) {
      f.unitind3.value = subjectUnit;
      f.unitname3.value = sciNameArr[activeIndex + 1];
      activeIndex = activeIndex + 2;
    } else if (sciNameArr[activeIndex].length == 1) {
      f.unitind3.value = sciNameArr[activeIndex];
      activeIndex = activeIndex + 1;
      while (sciNameArr.length > activeIndex) {
        f.unitname3.value = (
          f.unitname3.value +
          " " +
          sciNameArr[activeIndex]
        ).trim();
        activeIndex = activeIndex + 1;
      }
    } else {
      let firstChar = sciNameArr[activeIndex].substring(0, 1);
      if (firstChar != firstChar.toUpperCase()) {
        f.unitname3.value = sciNameArr[activeIndex];
        activeIndex = activeIndex + 1;
      }
    }
  }
  let author = "";
  while (sciNameArr.length > activeIndex) {
    //Place remain taxon units into the author field
    author = author + " " + sciNameArr[activeIndex];
    activeIndex = activeIndex + 1;
  }
  f.author.value = author.trim();
  let unitName1 = f.unitname1.value;
  //If rankid is not set, determine rank
  if (f.unitname2.value == "") {
    if (rankId == "" && unitName1.length > 4) {
      if (
        unitName1.indexOf("aceae") == unitName1.length - 5 ||
        unitName1.indexOf("idae") == unitName1.length - 4
      ) {
        rankId = 140;
      } else if (
        unitName1.indexOf("oideae") == unitName1.length - 6 ||
        unitName1.indexOf("inae") == unitName1.length - 4
      ) {
        rankId = 150;
      } else if (unitName1.indexOf("ineae") == unitName1.length - 5) {
        rankId = 110;
      } else if (unitName1.indexOf("ales") == unitName1.length - 4) {
        rankId = 100;
      }
    }
  } else {
    rankId = 220;
    if (f.unitname3.value != "") {
      rankId = 230;
      if (f.unitind3.value == "var.") rankId = 240;
      else if (f.unitind3.value == "f.") rankId = 260;
      else if (f.unitind3.value == "×") rankId = 220;
    }
  }
  //Deal with problematic subgeneric ranks
  let parentName = "";
  if (unitName1.indexOf("(") > -1) {
    if (
      unitName1.substring(0, 1) == "(" &&
      unitName1.substring(unitName1.length - 1) == ")"
    ) {
      unitName1 =
        unitName1.substring(1, unitName1.length - 1) + " " + unitName1;
      f.unitname1.value = unitName1;
      rankId = 190;
    }
    if (rankId == 190) {
      parentName = unitName1.substring(0, unitName1.indexOf("(")).trim();
    } else if (rankId > 190) {
      if (rankId == 220) parentName = unitName1;
      f.unitname1.value = unitName1.substring(0, unitName1.indexOf("(")).trim();
    }
  }
  f.rankid.value = rankId;
  if (unitName1.substring(0, 1) == "×" || unitName1.substring(0, 1) == "†") {
    if (f.unitind1.value == "") {
      if (unitName1.substring(0, 1) == "×") f.unitind1.selectedIndex = 1;
      if (unitName1.substring(0, 1) == "†") f.unitind1.selectedIndex = 2;
    }
    f.unitname1.value = f.unitname1.value.substring(1);
  }
  if (f.unitname2.value.substring(0, 1) == "×") {
    if (f.unitind2.value == "") {
      if (f.unitname2.value.substring(0, 1) == "×")
        f.unitind2.selectedIndex = 1;
    }
    f.unitname2.value = f.unitname2.value.substring(1);
  }
  if (parentName == "") {
    //Set parent name
    if (rankId > 180) {
      if (rankId == 220) parentName = f.unitname1.value;
      else if (rankId > 220)
        parentName = f.unitname1.value + " " + f.unitname2.value;
    }
  }
  if (parentName != "") setParent(parentName, f.unitind1.value);
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

function updateFullname(f) {
  let sciname =
    f.unitind1.value +
    f.unitname1.value +
    " " +
    f.unitind2.value +
    f.unitname2.value +
    " ";
  if (f.unitname3.value) {
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
function showOnlyRelevantFields(rankId) {
  const label = document.getElementById("unitind1label");
  const unitind1Select = document.getElementById("unitind1");
  const div2Hide = document.getElementById("div2hide");
  const div3Hide = document.getElementById("div3hide");
  const div4Hide = document.getElementById("div4hide");
  const div5Hide = document.getElementById("div5hide");
  const div4Display = document.getElementById("unit4Display");
  const div5Display = document.getElementById("unit5Display");
  const authorDiv = document.getElementById("author-div");
  const parentNode = div5Hide.parentNode; // @TODO confirm
  const genusDiv = document.getElementById("genus-div");

  rankIdsToHideUnit2From = {
    "non-ranked node": 0,
    organism: 1,
    kingdom: 10,
    subkingdom: 20,
    division: 30,
    subdivision: 40,
    superclass: 50,
    class: 60,
    subclass: 70,
    order: 100,
    suborder: 110,
    family: 140,
    subfamily: 150,
    tribe: 160,
    subtribe: 170,
    genus: 180,
    section: 200,
    subsection: 210,
  }; // not 190 (subgenera)
  const { ...rest } = rankIdsToHideUnit2From;
  rankIdsToHideUnit3From = { ...rest, subgenus: 190, species: 220 };
  const { ...rest2 } = rankIdsToHideUnit3From;
  rankIdsToHideUnit4From = {
    ...rest2,
    subspecies: 230,
    variety: 240,
    subvariety: 250,
    form: 260,
    subform: 270,
  };
  const { ...rest3 } = rankIdsToHideUnit4From;
  rankIdsToHideUnit5From = { ...rest3 };

  allRankIds = { ...rest3, cultivar: 300 };

  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    div2Hide.style.display = "none";
  } else {
    div2Hide.style.display = "block";
  }

  if (Object.values(rankIdsToHideUnit3From).includes(rankId)) {
    div3Hide.style.display = "none";
  } else {
    div3Hide.style.display = "block";
  }

  if (rankId <= allRankIds.genus) {
    const rankIdSelector = document.getElementById("rankid");
    const optionIdx = rankIdSelector.options.selectedIndex;
    const selectedOptionText = rankIdSelector.options[optionIdx].text.trim();

    // Set the label for "UnitName1" based on the selected option text
    label.textContent = selectedOptionText + " Name";
  } else {
    label.textContent = "Genus Name"; // @TODO decide if this is still the best logic
  }

  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    unitind1Select.style.display = "none";
  } else {
    unitind1Select.style.display = "inline-block";
  }

  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    document.getElementById("unitname2").value = null;
    document.getElementById("unitind2").value = null;
  }

  if (Object.values(rankIdsToHideUnit3From).includes(rankId)) {
    document.getElementById("unitind3").value = null;
    document.getElementById("unitname3").value = null;
  }

  if (Object.values(rankIdsToHideUnit4From).includes(rankId)) {
    document.getElementById("cultivarEpithet").value = null;
  }
  if (Object.values(rankIdsToHideUnit5From).includes(rankId)) {
    document.getElementById("tradeName").value = null;
  }

  const unit2NameLabel = document.getElementById("unit-2-name-label");
  if (rankId === allRankIds.subgenus) {
    unit2NameLabel.textContent = "Subgenus Name: ";
  } else {
    unit2NameLabel.textContent = "Specific Epithet: ";
  }

  if (rankId == allRankIds.cultivar) {
    div4Display.style.display = "inline-block";
    div5Display.style.display = "inline-block";
    div4Hide.style.display = "block";
    div5Hide.style.display = "block";
    parentNode.insertBefore(authorDiv, div4Hide);
  } else {
    div4Hide.style.display = "none";
    div5Hide.style.display = "none";
    document.getElementById("cultivarEpithet").value = null;
    document.getElementById("tradeName").value = null;
    // parentNode.insertBefore(authorDiv, genusDiv); // @TODO maybe insert below unit2 if that exists and other wise below unit1
  }
}
