$(document).ready(async function () {
  const currentRankId = Number(document.getElementById("rankid").value);
  showOnlyRelevantFields(currentRankId);

  const form = document.getElementById("taxoneditform");
  await updateFullname(form);
  const originalForm = form.cloneNode(true);
  form.querySelectorAll("input, select, textarea").forEach((element) => {
    const debouncedChange = debounce(async () => {
      await updateFullname(form);
      handleFieldChange(
        form,
        true,
        "taxoneditsubmit",
        "Submit Edits",
        originalForm
      );
    }, 500);
    element.addEventListener("input", debouncedChange);
    element.addEventListener("change", debouncedChange);
  });

  $("#tabs").tabs({ active: tabIndex });

  $("#parentstr").autocomplete({
    source: function (request, response) {
      $.getJSON(
        "rpc/gettaxasuggest.php",
        {
          term: request.term,
          taid: document.taxauthidform.taxauthid.value,
          rhigh: document.taxoneditform.rankid.value,
        },
        response
      );
    },
    minLength: 3,
    autoFocus: true,
  });

  document.getElementById("rankid").addEventListener("change", function () {
    const selectedValue = Number(this.value);
    showOnlyRelevantFields(selectedValue);
  });

  $("#aefacceptedstr").autocomplete({
    source: "rpc/getacceptedsuggest.php",
    dataType: "json",
    minLength: 3,
    autoFocus: true,
    change: function (event, ui) {
      if (ui.item == null && this.value.trim() != "") {
        alert(
          "Name must be selected from list of accepted taxa currently in the system."
        );
        this.focus();
        this.form.tidaccepted.value = "";
      }
    },
    focus: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
    select: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
  });

  $("#ctnafacceptedstr").autocomplete({
    source: "rpc/getacceptedsuggest.php",
    dataType: "json",
    minLength: 3,
    autoFocus: true,
    change: function (event, ui) {
      if (ui.item == null && this.value.trim() != "") {
        alert(
          "Name must be selected from list of accepted taxa currently in the system."
        );
        this.focus();
        this.form.tidaccepted.value = "";
      }
    },
    focus: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
    select: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
  });
});

const toggleEditFields = () => {
  toggle("editfield");
  toggle("kingdomdiv");
  const selectedValue = Number(document.getElementById("rankid").value);
  showOnlyRelevantFields(selectedValue);
};

function showOnlyRelevantFields(rankId) {
  const currentCultivarEpithet =
    document.getElementById("cultivarEpithet").value;
  const currentTradeName = document.getElementById("tradeName").value;
  const label = document.getElementById("unitind1label");
  const unitind1Select = document.getElementById("unitind1-select");
  const div2Hide = document.getElementById("div2hide");
  const div3Hide = document.getElementById("div3hide");
  const div4Hide = document.getElementById("div4hide");
  const div5Hide = document.getElementById("div5hide");
  const div4Display = document.getElementById("unit4Display");
  const div5Display = document.getElementById("unit5Display");
  const authorDiv = document.getElementById("author-div");
  const parentNode = div5Hide.parentNode;

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

  if (rankId <= allRankIds.subsection) {
    const rankIdSelector = document.getElementById("rankid");
    const optionIdx = rankIdSelector.options.selectedIndex;
    const selectedOptionText = rankIdSelector.options[optionIdx].text.trim();

    // Set the label for "UnitName1" based on the selected option text
    label.textContent = selectedOptionText + " Name *: ";
  } else {
    label.textContent = "Genus Name *: ";
  }

  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    unitind1Select.style.display = "none";
  } else {
    unitind1Select.style.display = "inline-block";
  }

  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    document.getElementById("unitname2").value = null;
    document.getElementById("unitind2-select").value = null;
  }

  if (Object.values(rankIdsToHideUnit3From).includes(rankId)) {
    document.getElementById("unitind3").value = null;
    document.getElementById("unitname3").value = null;
  }

  if (Object.values(rankIdsToHideUnit4From).includes(rankId)) {
    removeFromSciName(standardizeCultivarEpithet(currentCultivarEpithet));
    document.getElementById("cultivarEpithet").value = null;
  }

  if (Object.values(rankIdsToHideUnit5From).includes(rankId)) {
    removeFromSciName(standardizeTradeName(currentTradeName));
    document.getElementById("tradeName").value = null;
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
  }
}

async function updateFullname(f) {
  await updateFullnameCore(f, true);
}

function toggle(target) {
  var ele = document.getElementById(target);
  if (ele) {
    if (ele.style.display == "none") {
      ele.style.display = "";
    } else {
      ele.style.display = "none";
    }
  } else {
    var divs = document.getElementsByTagName("div");
    var i;
    for (i = 0; i < divs.length; i++) {
      var divObj = divs[i];
      if (divObj.className == target) {
        if (divObj.style.display == "none") {
          divObj.style.display = "block";
        } else {
          divObj.style.display = "none";
        }
      }
    }

    var spans = document.getElementsByTagName("span");
    var j;
    for (j = 0; j < spans.length; j++) {
      var spanObj = spans[j];
      if (spanObj.className == target) {
        if (spanObj.style.display == "none") {
          spanObj.style.display = "inline";
        } else {
          spanObj.style.display = "none";
        }
      }
    }
  }
}

function validateTaxonEditForm(f) {
  if (!checkNameExistence(f)) {
    return false;
  }
  if (f.unitname1.value.trim() == "") {
    alert("Unitname 1 field must have a value");
    return false;
  }
  return true;
}

async function verifyLoadForm(f, silent = false, originalForm) {
  return verifyLoadFormCore(f, silent, originalForm); // wrapped because verifyLoadForm is what gets referenced in other shared functions with taxonomy creation
}

function verifyChangeToNotAcceptedForm(f) {
  if (f.acceptedstr.value == "") {
    alert("Please enter an accepted name to which this taxon will be linked!");
    return false;
  } else if (f.tidaccepted.value == "" || f.tidaccepted.value == "undefined") {
    alert(
      "Please select a name from the list. If name is not in the list, target taxon is not listed as accepted, or has not yet been entered in thesarurus."
    );
    return false;
  }
  return true;
}

function verifyLinkToAcceptedForm(f) {
  if (f.acceptedstr.value == "") {
    alert("Please enter an accepted name to which this taxon will be linked!");
    return false;
  } else if (f.tidaccepted.value == "" || f.tidaccepted.value == "undefined") {
    alert(
      "Taxon entered appears not to be in thesaurus or is not listed as an accepted taxon. Name must be selected from list."
    );
    return false;
  }
  return true;
}

function submitTaxStatusForm(f) {
  $.ajax({
    type: "POST",
    url: "rpc/gettid.php",
    data: { sciname: f.parentstr.value },
  }).done(function (msg) {
    if (msg == 0) {
      alert(
        "ERROR: Parent taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus."
      );
    } else {
      f.parenttid.value = msg;
      f.submit();
    }
  });
}
