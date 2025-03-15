/**
 * GLOBAL VARIABLES
 */
const criteriaPanel = document.getElementById("criteria-panel") || null;
const allCollections = document.getElementById("dballcb") || null;
const form = document.getElementById("params-form") || null;
const formColls = document.getElementById("search-form-colls") || null;
const formSites = document.getElementById("site-list") || null;
const searchFormColls = document.getElementById("search-form-colls") || null;
const searchFormPaleo = document.getElementById("search-form-geocontext") || null;

// list of parameters to be passed to url, modified by getSearchUrl method
let paramNames = [
  "db",
  "datasetid",
  "catnum",
  "includeothercatnum",
  "hasimages",
  "hasaudio",
  "typestatus",
  "hasgenetic",
  "hascoords",
  "includecult",
  "country",
  "state",
  "county",
  "local",
  "elevlow",
  "elevhigh",
  "llbound",
  "footprintGeoJson",
  "llpoint",
  "eventdate1",
  "eventdate2",
  "taxa",
  "usethes",
  "taxontype",
  "collnum",
  "collector",
  "attr[]",
  "materialsampletype",
  "association-type",
  "associated-taxa",
  "taxontype-association",
  "usethes-associations",
  "earlyInterval",
  "lateInterval",
  "lithogroup",
  "formation",
  "member",
  "bed",
];
const uLat = document.getElementById("upperlat") || null;
const uLatNs = document.getElementById("upperlat_NS") || null;
const bLat = document.getElementById("bottomlat") || null;
const bLatNs = document.getElementById("bottomlat_NS") || null;
const lLng = document.getElementById("leftlong") || null;
const lLngEw = document.getElementById("leftlong_EW") || null;
const rLng = document.getElementById("rightlong") || null;
const rLngEw = document.getElementById("rightlong_EW") || null;
const pLat = document.getElementById("pointlat") || null;
const pLatNs = document.getElementById("pointlat_NS") || null;
const pLng = document.getElementById("pointlong") || null;
const pLngEw = document.getElementById("pointlong_EW") || null;
const pRadius = document.getElementById("radius") || null;
const pRadiusUn = document.getElementById("radiusunits") || null;

let paramsArr = {};
//////////////////////////////////////////////////////////////////////////

/**
 * METHODS
 */

/**
 * Toggles tab selection for collection picking options in modal
 * Uses jQuery
 */
$('input[type="radio"]')?.click(function () {
  var inputValue = $(this)?.attr("value");
  var targetBox = $("#" + inputValue);
  $(".box")?.not(targetBox)?.hide();
  $(targetBox)?.show();
  $(this)?.parent()?.addClass("tab-active");
  $(this)?.parent()?.siblings()?.removeClass("tab-active");
});

/**
 * Opens modal with id selector
 * @param {String} elementid Selector for modal to be opened
 */
function openModal(elementid) {
  $(elementid)?.css("display", "block");
  $(document.body)?.css("overflow: hidden");
}

/**
 * Closes modal with id selector
 * @param {String} elementid Selector for modal to be opened
 */
function closeModal(elementid) {
  $(elementid)?.css("display", "none");
}

/**
 * Chips
 */

/**
 * Adds default chips
 * @param {HTMLObjectElement} element Input for which chips are going to be created by default
 */
function addChip(element) {
  if (!element || (!element.name && element?.tagName !== "OPTION")) return;
  let inputChip = document.createElement("span") || null;
  if (!inputChip) return;
  inputChip?.classList?.add("chip");
  let chipBtn = document.createElement("button") || null;
  if (!chipBtn) return;
  chipBtn?.setAttribute("type", "button");
  chipBtn?.classList?.add("chip-remove-btn");

  // if element is domain or site, pass other content
  if (element?.name == "some-datasetid") {
    if (element.text != "" && inputChip && chipBtn) {
      inputChip.id = "chip-some-datasetids";
      inputChip.textContent = element?.text;
      chipBtn.onclick = function () {
        uncheckAll(document.getElementById("all-sites"));
        removeChip(inputChip);
      };
    }
  } else if (
    (element.name == "neonext-collections-list") |
    (element.name == "ext-collections-list") |
    (element.name == "taxonomic-cat") |
    (element.name == "neon-theme") |
    (element.name == "sample-type")
  ) {
    inputChip.id = `chip-some-${element.name}-collids`;
    inputChip.textContent = element.text;
    chipBtn.onclick = function () {
      uncheckAll(document.getElementById(element.name));
      removeChip(inputChip);
    };
  }
  else if (element.tagName === "OPTION") {
    inputChip.id = "chip-" + element.dataset.chip;
    inputChip.textContent = (element.dataset.chip ? element.dataset.chip + ": " : "") + element.textContent;
    chipBtn.onclick = () => handleRemoval(element, inputChip);
  }
  else {
    inputChip.id = "chip-" + element.id;
    let isTextOrNum = (element.type == "text") | (element.type == "number");
    isTextOrNum
      ? (inputChip.textContent = `${element.dataset.chip}: ${element.value}`)
      : (inputChip.textContent = element.dataset.chip);
    chipBtn.onclick = () => handleRemoval(element, inputChip);
  }
  let screenReaderSpan = document.createElement("span");
  const dataChipText = element.getAttribute("data-chip");
  const screenReaderText = dataChipText
    ? dataChipText
    : element?.text || "Unknown chip";
  screenReaderSpan.textContent =
    "Remove " + screenReaderText + " from search criteria";
  screenReaderSpan?.classList?.add("screen-reader-only");
  chipBtn.appendChild(screenReaderSpan);
  inputChip.appendChild(chipBtn);
  document.getElementById("chips").appendChild(inputChip);
}

function handleRemoval(element, inputChip) {
  element.type === "checkbox"
    ? (element.checked = false)
    : (element.value = element.defaultValue);
    if (element.tagName === "OPTION") {
      const selectElement = element.closest('select');
      if (selectElement) {
        element.selected = false;
      }
    }
  if (element.getAttribute("id") === "dballcb") {
    const targetCategoryCheckboxes =
      document.querySelectorAll('input[id^="cat-"]');
    targetCategoryCheckboxes.forEach((collection) => {
      collection.checked = false;
    });
    const targetCheckboxes = document.querySelectorAll('input[id^="coll-"]');
    targetCheckboxes.forEach((collection) => {
      collection.checked = false;
    });
    //do the same for collections with slightly different format
    const targetCheckboxAlts = document.querySelectorAll(
      'input[id^="collection-"]'
    );
    targetCheckboxAlts.forEach((collection) => {
      collection.checked = false;
    });
  }
  setAssociationRelationshipTypeToDefault(element);
  setMaterialSampleToDefault(element);
  setTaxonTypeToDefault(element);
  setAssociationTaxonTypeToDefault(element);
  // uncheckAllChip(element); // @TODO test this out
  element.dataset.formId ? uncheckAll(element) : "";
  removeChip(inputChip);
}

function setMaterialSampleToDefault(element) {
  if (element?.getAttribute("id")?.startsWith("materialsampletype")) {
    const targetIndex = document.getElementById(
      "materialsampletype-none"
    ).selectedIndex;
    document.getElementById("materialsampletype").selectedIndex = targetIndex;
  }
}

function setTaxonTypeToDefault(element) {
  if (element?.getAttribute("id")?.startsWith("taxontype")) {
    const targetIndex = document.getElementById("taxontype-any")?.selectedIndex;
    document.getElementById("taxontype").selectedIndex = targetIndex;
  }
}

function setAssociationTaxonTypeToDefault(element) {
  if (element?.getAttribute("id")?.startsWith("taxontype-association-")) {
    const targetIndex = document.getElementById(
      "taxontype-association-scientific"
    )?.selectedIndex;
    document.getElementById("taxontype-association").selectedIndex =
      targetIndex;
  }
}

function setAssociationRelationshipTypeToDefault(element) {
  if (element?.getAttribute("id")?.startsWith("association-type-")) {
    const targetIndex = document.getElementById(
      "association-type-none"
    )?.selectedIndex;
    document.getElementById("association-type").selectedIndex = targetIndex;
  }
}

/**
 * Removes chip
 * @param {HTMLObjectElement} chip Chip element
 */
function removeChip(chip) {
  chip != null ? chip.remove() : "";
}

/**
 * Updateds chips based on selected options
 * @param {Event} e
 */
function updateChip(e) {
  document.getElementById("chips").innerHTML = "";
  // first go through collections and sites

  // No sites in Symbiota, so this stuff just gets ignored
  // if any domains (except for "all") is selected, then add chip
  let dSList = document.querySelectorAll("#site-list input[type=checkbox]");
  let dSChecked = document.querySelectorAll(
    "#site-list input[type=checkbox]:checked"
  );
  if (
    dSList &&
    dSChecked &&
    dSChecked.length > 0 &&
    dSChecked.length < dSList.length
  ) {
    addChip(getDomainsSitesChips());
  }
  // if any collections are selected (except for "all"), then add chip; this logic is alternatively handled in the formInputs for loop below
  let allCollectionsChecked = document?.getElementById("dballcb")?.checked;
  let individualCollectionsChecked = Array.from(
    document.querySelectorAll(`#search-form-colls input[name="db"]:checked`)
  );
  if (!allCollectionsChecked && individualCollectionsChecked.length > 0) {
    addChip(getCollsChips(getCriterionSelected(), "Some Biorepo Colls"));
  }
  // if any additional NEON colls are selected (except for "all"), then add chip
  let addCols = document.querySelectorAll(
    "#neonext-collections-list input[type=checkbox]"
  );
  let addColsChecked = document.querySelectorAll(
    "#neonext-collections-list input[type=checkbox]:checked"
  );
  if (addColsChecked.length > 0 && addColsChecked.length < addCols.length) {
    addChip(getCollsChips("neonext-collections-list", "Some Add NEON Colls"));
  }
  // if any external NEON colls are selected (expect for "all"), then add chip
  let extCols = document.querySelectorAll(
    "#ext-collections-list input[type=checkbox]"
  );
  let extColsChecked = document.querySelectorAll(
    "#ext-collections-list input[type=checkbox]:checked"
  );
  if (extColsChecked.length > 0 && extColsChecked.length < extCols.length) {
    addChip(getCollsChips("ext-collections-list", "Some Ext NEON Colls"));
  }
  // then go through remaining inputs (exclude db and datasetid)
  // go through entire form and find selected items
  formInputs.forEach((item) => {
    if ((item.name != "db") | (item.name != "datasetid")) {
      if (
        (item.type == "checkbox" && item.checked) |
        (item.type == "text" && item.value != "") |
        (item.type == "number" && item.value != "")
      ) {
        if (
          allCollectionsChecked &&
          item.name === "db[]" &&
          item.id !== "dballcb"
        ) {
          // don't add these chips;
        } else {
          // add chips depending on type of item
          item.hasAttribute("data-chip") ? addChip(item) : "";
        }
      }
    }
    // print inputs checked or filled in
  });

  // then go through remaining options and find selected items
  const optionElements = document.querySelectorAll(".content option");
  optionElements.forEach((item) => {
    if (item.selected && item.value && item.hasAttribute("data-chip")) {
      addChip(item);
    }
  });
}

/**
 * Gets collections chips
 * @param {String} listId id of coll list element
 * @param {String} chipText explanatory text to be addded to chip
 * @returns {Object} chipEl chip element with text and name props
 */
function getCollsChips(listId, chipText) {
  // Goes through list of collection options
  let collOptions = document.querySelectorAll(
    `#${listId} input[type=checkbox]`
  );
  let collSelected = document.querySelectorAll(
    `#${listId} input[type=checkbox]:checked`
  );
  // If 'all' is not selected, picks which are selected
  collsArr = [];
  let chipEl = {};

  if (collOptions.length > collSelected.length) {
    // Generates chip element object
    collSelected.forEach((coll) => {
      // check if we're inside biorepo coll form
      let isColl = coll.dataset.cat != undefined;
      if (isColl) {
        let isCatSel = document.getElementById(coll.dataset.cat).checked;
        isCatSel ? "" : collsArr.push(coll.dataset.ccode);
      } else {
        collsArr.push(coll.dataset.ccode);
      }
    });
  }
  chipEl.text = `${chipText}: ${collsArr.join(", ")}`;
  chipEl.name = listId;
  return chipEl;
}

/**
 * Gets selected domains and sites to generate chips
 * @returns {Object} chipEl chip element with text and name props
 */
function getDomainsSitesChips() {
  let boxes = document.getElementsByName("datasetid");
  let dArr = [];
  let sArr = [];
  boxes.forEach((box) => {
    if (box.checked) {
      let isSite = box.dataset.domain != undefined;
      if (isSite) {
        let isDomainSel = document.getElementById(box.dataset.domain).checked;
        isDomainSel ? "" : sArr.push(box.id);
      } else {
        dArr.push(box.id);
      }
    }
  });
  let dStr = "";
  let sStr = "";
  dArr.length > 0 ? (dStr = `Domain(s): ${dArr.join(", ")} `) : "";
  sArr.length > 0 ? (sStr = `Sites: ${sArr.join(", ")}`) : "";
  let chipEl = {
    text: dStr + sStr,
    name: "some-datasetid",
  };
  return chipEl;
}
/////////

/**
 * Toggles state of checkboxes in nested lists when clicking an "all-selector" element
 * Uses jQuery
 */
function toggleAllSelector() {
  $(this)
    .siblings()
    .find("input[type=checkbox]:enabled")
    .prop("checked", this.checked)
    .attr("checked", this.checked);
}

/**
 * Triggers toggling of checked/unchecked boxes in nested lists
 * Default is all boxes are checked in HTML.
 * @param {String} e.data.element Selector for element containing
 * list, should be passed when binding function to element
 */
function autoToggleSelector(e) {
  if (e.type == "click" || e.type == "change") {
    let isChild = e.target.classList.contains("child");
    if (isChild) {
      let nearParentNode = e.target.closest("ul").parentNode;
      let nearParentOpt = e.target
        .closest("ul")
        .parentNode.querySelector(".all-selector");
      let numOptions = nearParentNode.querySelectorAll(
        "ul > li input.child:not(.all-selector):enabled"
      ).length;
      let numOpChecked = nearParentNode.querySelectorAll(
        "ul > li input.child:not(.all-selector):checked"
      ).length;
      numOptions == numOpChecked
        ? (nearParentOpt.checked = true)
        : (nearParentOpt.checked = false);

      if (nearParentOpt.classList.contains("child")) {
        let parentAllNode = nearParentNode.closest("ul").parentNode;
        let parentAllOpt = parentAllNode.querySelector(".all-selector");
        let numOptionsAll = parentAllNode.querySelectorAll(
          "input.child:enabled"
        ).length;
        let numOpCheckedAll = parentAllNode.querySelectorAll(
          "input.child:checked"
        ).length;
        numOptionsAll == numOpCheckedAll
          ? (parentAllOpt.checked = true)
          : (parentAllOpt.checked = false);
      }
    }
  }
}

/**
 * Unchecks children of 'all-selector' checkboxes or all checkboxes in list
 * when criterion chip is removed
 * Uses 'data-form-id' property in .php
 * @param {Object} element HTML Node Object
 */
function uncheckAllChip(element) {
  let isAllSel = element.classList.contains("specobs");
  if (isAllSel) {
    let selChildren = document.querySelectorAll(
      `#${element.dataset.formId} input[type=checkbox]:checked`
    );
    selChildren.forEach((item) => {
      item.checked = false;
    });
  } else {
    let item = document.querySelector(
      `input[id^="${element.className}"][name="cat[]"]`
    );
    if (item) item.checked = false;
  }
}

/**
 * Finds all collections selected
 * Uses active tab in modal
 */
function getCollsSelected() {
  let query = 'input[name="db"]:checked';
  let selectedInForm = Array.from(
    document.querySelectorAll(
      '#search-form-colls input[name="db"]:checked, ' +
        '#search-form-colls input[name="db[]"]:checked'
    )
  );
  let collsArr = selectedInForm;
  return collsArr;
}

function getTraitsSelected() {
  return Array.from(document.querySelectorAll('input[name="attr[]"]:checked'));
}

/**
 * Searches specified fields and capture values
 * @param {String} paramName Name of parameter to be looked for in form
 * Passes objects to `paramsArr`
 * Passes default objects
 */
function getParam(paramName) {
  //Default country
  // paramsArr['country'] = 'USA';
  const elements = document.getElementsByName(paramName);
  const firstEl = elements[0];

  let elementValues = "";

  // for traits
  if (paramName === "attr[]") {
    const selectedTraits = getTraitsSelected();
    elementValues = selectedTraits?.map((selectedTrait) => selectedTrait.value);
  }

  // for db and datasetid
  if (paramName === "db") {
    const selectedCollections = getCollsSelected();
    elementValues = selectedCollections?.map(
      (selectedCollection) => selectedCollection.value
    );
  } else if (paramName === "datasetid") {
    // won't happen in vanilla symbiota
    let datasetArr = [];
    elements.forEach((el) => {
      if (el.checked) {
        let isSite = el.dataset.domain != undefined;
        if (isSite) {
          let isDomainSel = document.getElementById(el.dataset.domain).checked;
          isDomainSel ? "" : datasetArr.push(el.value);
        } else {
          datasetArr.push(el.value);
        }
      }
    });
    elementValues = datasetArr;
  } else if (paramName === "llbound") {
    // Only if inputs aren't empty
    if (
      uLat.value != "" &&
      bLat.value != "" &&
      lLng.value != "" &&
      rLng.value != ""
    ) {
      let uLatVal = uLatNs.value == "S" ? uLat.value * -1 : uLat.value * 1;
      let bLatVal = bLatNs.value == "S" ? bLat.value * -1 : bLat.value * 1;
      let lLngVal = lLngEw.value == "W" ? lLng.value * -1 : lLng.value * 1;
      let rLngVal = rLngEw.value == "W" ? rLng.value * -1 : rLng.value * 1;
      elementValues = `${uLatVal};${bLatVal};${lLngVal};${rLngVal}`;
    }
  } else if (paramName === "llpoint") {
    if (
      pLat.value != "" &&
      pLng.value != "" &&
      pRadius.value != "" &&
      pRadiusUn.value != ""
    ) {
      let pLatVal =
        pLatNs.value == "S"
          ? Math.round(pLat.value * -1 * 100000) / 100000
          : Math.round(pLat.value * 100000) / 100000;
      let pLngVal =
        pLngEw.value == "W"
          ? Math.round(pLng.value * -1 * 100000) / 100000
          : Math.round(pLng.value * 100000) / 100000;
      let pRadiusVal = pRadius.value + ";" + pRadiusUn.value;
      elementValues = `${pLatVal};${pLngVal};${pRadiusVal}`;
    }
  } else if (paramName === "elevlow" || paramName === "elevhigh") {
    firstEl.type === "number" && firstEl != ""
      ? (elementValues = firstEl.value)
      : "";
  } else if (elements[0] != undefined) {
    switch (firstEl.tagName) {
      case "INPUT":
        (firstEl.type === "checkbox" && firstEl.checked) ||
        (firstEl.type === "text" && firstEl != "")
          ? (elementValues = firstEl.value)
          : "";
        break;
      case "SELECT":
        elementValues = firstEl.options[firstEl.selectedIndex].value;
        break;
      case "TEXTAREA":
        elementValues = firstEl.value;
        break;
    }
  }
  elementValues != "" ? (paramsArr[paramName] = elementValues) : "";
  return paramsArr;
}

/**
 * Creates search URL with parameters
 * Define parameters to be looked for in `paramNames` array
 */
function getSearchUrl() {
  const formatPreference = document.getElementById("list-button").checked
    ? "list"
    : "table";
  const harvestUrl = location.href.slice(0, location.href.indexOf("search"));
  const urlSuffix =
    formatPreference === "list" ? "list.php" : "listtabledisplay.php";

  const baseUrl = new URL(harvestUrl + urlSuffix);

  // Clears array temporarily to avoid redundancy
  paramsArr = {};

  // Grabs params from form for each param name
  paramNames.forEach((param, i) => {
    return getParam(paramNames[i]);
  });

  // Appends each key value for each param in search url
  let queryString = Object.keys(paramsArr).map((key) => {
		baseUrl.searchParams.append(key, paramsArr[key]);
  });

  baseUrl.searchParams.append("comingFrom", "newsearch");
  return baseUrl.href;
}

/**
 * Form validation functions
 * @returns {Array} errors Array of errors objects with form element it refers to (elId), for highlighting, and errorMsg
 */
function validateForm() {
  errors = [];
  // DB
  let anyCollsSelected = getCollsSelected();
  if (anyCollsSelected.length === 0) {
    errors.push({
      elId: "search-form-colls",
      errorMsg: "Please select at least one collection.",
    });
  }
  // HTML5 built-in validation
  let invalidInputs = document.querySelectorAll("input:invalid");
  if (invalidInputs.length > 0) {
    invalidInputs.forEach((inp) => {
      errors.push({
        elId: inp.id,
        errorMsg: `Please check values in field ${inp.dataset.chip}.`,
      });
    });
  }
  // Bounding Box
  let bBoxNums = document.querySelectorAll(
    "#bounding-box-form input[type=number]"
  );
  let bBoxNumArr = [];
  bBoxNums.forEach((el) => {
    el.value != "" ? bBoxNumArr.push(el.value) : false;
  });
  let bBoxCardinals = document.querySelectorAll("#bounding-box-form select");
  selectedCardinals = [];
  bBoxCardinals.forEach((hItem) => {
    hItem.value != "" ? selectedCardinals.push(hItem.id) : false;
  });
  if (bBoxNumArr.length > 0 && bBoxNumArr.length < bBoxNums.length) {
    errors.push({
      elId: "bounding-box-form",
      errorMsg:
        "Please make sure either all Lat/Long bounding box values contain a value, or all are empty.",
    });
  } else if (bBoxNumArr.length > 0 && selectedCardinals.length == 0) {
    errors.push({
      elId: "bounding-box-form",
      errorMsg: "Please select hemisphere values.",
    });
  } else if (bBoxNumArr.length > 0 && selectedCardinals.length > 0) {
    let uLatVal = uLat.value;
    let uLatNsVal = uLatNs.value;
    let bLatVal = bLat.value;
    let bLatNsVal = bLatNs.value;

    if (uLatNsVal == "S" && bLatNsVal == "S") {
      uLatVal = uLatVal * -1;
      bLatVal = bLatVal * -1;
      if (uLatVal < bLatVal) {
        errors.push({
          elId: "bounding-box-form",
          errorMsg:
            "Your northern latitude value is less than your southern latitude value.",
        });
      }
    }

    let lLngVal = lLng.value;
    let lLngEwVal = lLngEw.value;
    let rLngVal = rLng.value;
    let rLngEwVal = rLngEw.value;

    if (lLngEwVal == "W" && rLngEwVal == "W") {
      lLngVal = lLngVal * -1;
      rLngVal = rLngVal * -1;
      if (lLngVal > rLngVal) {
        errors.push({
          elId: "bounding-box-form",
          errorMsg:
            "Your western longitude value is greater than your eastern longitude value. Note that western hemisphere longitudes in the decimal format are negative.",
        });
      }
    }
  }

  // Geo Context
  if (searchFormPaleo) {
    let early = form.earlyInterval.value;
    let late = form.lateInterval.value;
    if ((early !== "" && late === "") || (early === "" && late !== "")) {
      errors.push({
        elId: "search-form-geocontext",
        errorMsg:
          translations.INTERVAL_MISSING,
      });
    }

    if (early in paleoTimes && late in paleoTimes && paleoTimes[early].myaStart <= paleoTimes[late].myaEnd) {
      errors.push({
        elId: "search-form-geocontext",
        errorMsg:
          translations.INTERVALS_WRONG_ORDER,
      });
    }
  }

  return errors;
}

/**
 * Gets validation errors, outputs alerts with error messages and highlights form element with error
 * @param {Array} errors Array with error objects with form element it refers to (elId), for highlighting, and errorMsg
 */
function handleValErrors(errors) {
  const errorDiv = document.getElementById("error-msgs");
  errorDiv.innerHTML = "";
  errors.map((err) => {
    let element = document.getElementById(err.elId);
    element.classList.add("invalid");
    errorDiv.classList.remove("visually-hidden");
    let errorP = document.createElement("p");
    errorP.classList.add("error");
    errorP.innerText = err.errorMsg + " Click to dismiss.";
    errorP.onclick = function () {
      errorP.remove();
      element.classList.remove("invalid");
    };
    errorDiv.appendChild(errorP);
  });
}

/**
 * Calls methods to validate form and build URL that will redirect search
 */
function simpleSearch() {
  let alerts = document.getElementById("alert-msgs");
  alerts != null ? (alerts.innerHTML = "") : "";
  let errors = [];
  errors = validateForm();
  let isValid = errors.length == 0;
  if (isValid) {
    let searchUrl = getSearchUrl();
    window.location = searchUrl;
  } else {
    handleValErrors(errors);
  }
}

/**
 * Hides selected collections checkboxes (for whatever reason)
 * @param {integer} collid
 */
function hideColCheckbox(collid) {
  let colsToHide = document.querySelectorAll(
    `input[type='checkbox'][value='${collid}']`
  );
  colsToHide.forEach((col) => {
    let li = col?.closest("li");
    if (li) {
      li.style.display = "none";
    }
  });
}

function uncheckEverything() {
  const checkUncheckAllElem = document.getElementById("dballcb");
  checkUncheckAllElem.checked = false;
  const categoryCollectionsChecked = Array.from(
    document.querySelectorAll(`#search-form-colls input[name="cat[]"]:checked`)
  );
  categoryCollectionsChecked.forEach((individualCollectionChecked) => {
    individualCollectionChecked.checked = false;
  });

  const individualCollectionsChecked = Array.from(
    document.querySelectorAll(`#search-form-colls input[name="db[]"]:checked`)
  );
  individualCollectionsChecked.forEach((individualCollectionChecked) => {
    individualCollectionChecked.checked = false;
  });
}

function checkTheCollectionsThatShouldBeChecked(queriedCollections) {
  queriedCollections.forEach((queriedCollection) => {
    let targetElem = document.getElementById("collection-" + queriedCollection);
    if (!targetElem) {
      // get elements if categories exist
      const prefix = "coll-" + queriedCollection + "-";
      const candidateTargetElems =
        document.querySelectorAll(`[id^="${prefix}"]`) || [];
      if (candidateTargetElems.length > 0) {
        targetElem = candidateTargetElems[0]; // there should only be one match; get the first one
      }
    } 
    if(targetElem){
      targetElem.checked = true;
    }
  });
}

function setSearchForm(frm) {
  if (sessionStorage.querystr) {
    var urlVar = parseUrlVariables(sessionStorage.querystr);

    if (
      typeof urlVar.usethes !== "undefined" &&
      (urlVar.usethes == "" || urlVar.usethes == "0")
    ) {
      frm.usethes.checked = false;
    }
    if (
      typeof urlVar["usethes-associations"] !== "undefined" &&
      (urlVar["usethes-associations"] == "" ||
        urlVar["usethes-associations"] == "0")
    ) {
      frm["usethes-associations"].checked = false;
    }
    if (urlVar.taxontype) {
      if (frm?.taxontype) {
        frm.taxontype.value = urlVar.taxontype;
      }
    }
    if (urlVar.taxa) {
      if (frm?.taxa) {
        frm.taxa.value = urlVar.taxa;
      }
    }

    if (urlVar["associated-taxa"]) {
      if (frm["associated-taxa"]) {
        frm["associated-taxa"].value = urlVar["associated-taxa"];
      }
    }
    if (urlVar["association-type"]) {
      if (frm["association-type"]) {
        frm["association-type"].value = urlVar["association-type"];
      }
    }

    if (urlVar["associated-taxon-type"]) {
      if (frm["taxontype-association"]) {
        frm["taxontype-association"].value = urlVar["associated-taxon-type"];
      }
    }

    if (urlVar["earlyInterval"]) {
      if (frm["earlyInterval"]) {
        frm["earlyInterval"].value = urlVar["earlyInterval"];
      }
    }
    if (urlVar["lateInterval"]) {
      if (frm["lateInterval"]) {
        frm["lateInterval"].value = urlVar["lateInterval"];
      }
    }
    if (urlVar["lithogroup"]) {
      if (frm["lithogroup"]) {
        frm["lithogroup"].value = urlVar["lithogroup"];
      }
    }
    if (urlVar["formation"]) {
      if (frm["formation"]) {
        frm["formation"].value = urlVar["formation"];
      }
    }
    if (urlVar["member"]) {
      if (frm["member"]) {
        frm["member"].value = urlVar["member"];
      }
    }
    if (urlVar["bed"]) {
      if (frm["bed"]) {
        frm["bed"].value = urlVar["bed"];
      }
    }

    if (urlVar.country) {
      countryStr = urlVar.country;
      countryArr = countryStr.split(";");
      if (countryArr.indexOf("USA") > -1 || countryArr.indexOf("usa") > -1)
        countryStr = countryArr[0];
      //if(countryStr.indexOf('United States') > -1) countryStr = 'United States';
      if (frm?.country) {
        frm.country.value = countryStr;
      }
    }
    if (urlVar.state) {
      frm.state.value = urlVar.state;
    }
    if (urlVar.county) {
      frm.county.value = urlVar.county;
    }
    if (urlVar.local) {
      frm.local.value = urlVar.local;
    }
    if (urlVar.elevlow) {
      frm.elevlow.value = urlVar.elevlow;
    }
    if (urlVar.elevhigh) {
      frm.elevhigh.value = urlVar.elevhigh;
    }
    if (urlVar.llbound) {
      var coordArr = urlVar.llbound.split(";");
      frm.upperlat.value = Math.abs(parseFloat(coordArr[0]));
      frm.upperlat_NS.value = parseFloat(coordArr[0]) > 0 ? "N" : "S";

      frm.bottomlat.value = Math.abs(parseFloat(coordArr[1]));
      frm.bottomlat_NS.value = parseFloat(coordArr[1]) > 0 ? "N" : "S";

      frm.leftlong.value = Math.abs(parseFloat(coordArr[2]));
      frm.leftlong_EW.value = parseFloat(coordArr[2]) > 0 ? "E" : "W";

      frm.rightlong.value = Math.abs(parseFloat(coordArr[3]));
      frm.rightlong_EW.value = parseFloat(coordArr[3]) > 0 ? "E" : "W";
    }
    if (urlVar.footprintwkt) {
      frm.footprintwkt.value = urlVar.footprintwkt;
    }
    if (urlVar.llpoint) {
      var coordArr = urlVar.llpoint.split(";");
      frm.pointlat.value = Math.abs(parseFloat(coordArr[0]));
      frm.pointlat_NS.value = parseFloat(coordArr[0]) > 0 ? "N" : "S";

      frm.pointlong.value = Math.abs(parseFloat(coordArr[1]));
      frm.pointlong_EW.value = parseFloat(coordArr[1]) > 0 ? "E" : "W";

      frm.radius.value = Math.abs(parseFloat(coordArr[2]));
      if (coordArr[3] === "mi") frm.radiusunits.value = "mi";
      else if (coordArr[3] === "km") frm.radiusunits.value = "km";
    }
    if (urlVar.collector) {
      frm.collector.value = urlVar.collector;
    }
    if (urlVar.collnum) {
      frm.collnum.value = urlVar.collnum;
    }
    if (urlVar.eventdate1) {
      frm.eventdate1.value = urlVar.eventdate1;
    }
    if (urlVar.eventdate2) {
      frm.eventdate2.value = urlVar.eventdate2;
    }
    if (urlVar.catnum) {
      frm.catnum.value = urlVar.catnum;
    }
    //if(!urlVar.othercatnum){frm.includeothercatnum.checked = false;}
    if (urlVar.eventdate2) {
      frm.eventdate2.value = urlVar.eventdate2;
    }
    if (urlVar.materialsampletype) {
      frm.materialsampletype.value = urlVar.materialsampletype;
    }
    if (typeof urlVar.typestatus !== "undefined") {
      frm.typestatus.checked = true;
    }
    if (typeof urlVar.hasimages !== "undefined") {
      frm.hasimages.checked = true;
    }
    if (typeof urlVar.hasgenetic !== "undefined") {
      frm.hasgenetic.checked = true;
    }
    if (typeof urlVar.hascoords !== "undefined") {
      frm.hascoords.checked = true;
    }
    if (typeof urlVar.includecult !== "undefined") {
      if (frm?.includecult) {
        frm.includecult.checked = true;
      }
    }
    if (urlVar.db) {
      const queriedCollections = urlVar.db.split(",");
      if (queriedCollections.length > 0) {
        uncheckEverything();
        checkTheCollectionsThatShouldBeChecked(queriedCollections);
      }
    }
    for (var i in urlVar) {
      if (`${i}`.indexOf("traitid-") == 0) {
        var traitInput = document.getElementById("traitstateid-" + urlVar[i]);
        if (traitInput.type == "checkbox" || traitInput.type == "radio") {
          traitInput.checked = true;
        }
        // if(traitInput.type == 'select') { traitInput.value = urlVar[i]; }; // Must improve this to deal with multiple possible selections
      }
    }
    updateChip();
  }
}

function parseUrlVariables(varStr) {
  var result = {};
  varStr.split("&").forEach(function (part) {
    if (!part) return;
    part = part.split("+").join(" ");
    var eq = part.indexOf("=");
    var key = eq > -1 ? part.substr(0, eq) : part;
    var val = eq > -1 ? decodeURIComponent(part.substr(eq + 1)) : "";
    result[key] = val;
  });
  return result;
}

function toggleTheNonDefaultsClosed(defaultId) {
  const categoryButtons = document.querySelectorAll('a[id^="condense-"]');
  categoryButtons.forEach((categoryButton) => {
    const regexPattern = new RegExp(`^condense-\\d+-${defaultId}$`);
    if (!regexPattern.test(categoryButton.id)) {
      const idToToggle = categoryButton.id
        .replace("condense-", "")
        .replace("-" + defaultId, "");
      toggleCat(idToToggle);
    }
  });
}

function toggleAccordionsFromSessionStorage(accordionIds) {
  const accordions = document.querySelectorAll(
    'input[class="accordion-selector"]'
  );
  accordions.forEach((accordion) => {
    if(accordion.id !== "taxonomy") accordion.checked = false;
    if(accordion.id === "taxonomy" && localStorage.getItem("taxonomyAccordionClosed")) accordion.checked = false;
  });
  accordions.forEach((accordion) => {
    const currentId = accordion.getAttribute("id");
    if (accordionIds.includes(currentId)) {
      accordion.checked = true;
    }
  });
}

//////////////////////////////////////////////////////////////////////////

/**
 * EVENT LISTENERS/INITIALIZERS
 */

// Reset button
document
  .getElementById("reset-btn")
  .addEventListener("click", function (event) {
    document.getElementById("params-form").reset();
    updateChip();
  });
// When checking "all neon collections" box, toggle checkboxes in modal
$("#all-neon-colls-quick").click(function () {
  let isChecked = $(this).prop("checked");
  $(".all-neon-colls").prop("checked", isChecked);
  $(".all-neon-colls").siblings().find(".child").prop("checked", isChecked);
});
// When checking any 'all-selector', toggle children checkboxes
$(".all-selector").click(toggleAllSelector);
formColls.addEventListener("click", autoToggleSelector, false);
formColls.addEventListener("change", autoToggleSelector, false);
formSites?.addEventListener("click", autoToggleSelector, false);
searchFormColls?.addEventListener("click", autoToggleSelector, false);
searchFormColls?.addEventListener("change", autoToggleSelector, false);
//////// Binds Update chip on event change
const formInputs = document.querySelectorAll(".content input");
formInputs.forEach((formInput) => {
  formInput.addEventListener("change", updateChip);
});

const selectionElements = document.querySelectorAll(".content select");
selectionElements.forEach((selectionElement) => {
  selectionElement.addEventListener("change", updateChip);
});

// on default (on document load): All Neon Collections, All Domains & Sites, Include other IDs, All Domains & Sites
document.addEventListener("DOMContentLoaded", updateChip);
// Binds expansion function to plus and minus icons in selectors, uses jQuery
$(".expansion-icon").click(function () {
  if ($(this).siblings("ul").hasClass("collapsed")) {
    $(this)
      .html("indeterminate_check_box")
      .siblings("ul")
      .removeClass("collapsed");
  } else {
    $(this).html("add_box").siblings("ul").addClass("collapsed");
  }
});
// Hides MOSC-BU checkboxes
hideColCheckbox(58);

const accordions = document.querySelectorAll(
  'input[class="accordion-selector"]'
);
accordions.forEach((accordion) => {
  accordion.addEventListener("click", (event) => {
    const currentAccordionIds = localStorage?.accordionIds?.split(",") || [];
    const currentId = event.target.id;
    if (currentAccordionIds.includes(currentId)) {
      const targetIdx = currentAccordionIds.indexOf(currentId);
      currentAccordionIds.splice(targetIdx, 1);
      if(currentId==="taxonomy") {
        localStorage.setItem("taxonomyAccordionClosed", true);
      }
    } else {
      currentAccordionIds.push(currentId);
      if(currentId==="taxonomy") localStorage.setItem("taxonomyAccordionClosed", false)
    }
    localStorage.setItem("accordionIds", currentAccordionIds);
  });
});
