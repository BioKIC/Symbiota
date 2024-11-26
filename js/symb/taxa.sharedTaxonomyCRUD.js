const standardizeCultivarEpithet = (unstandardizedCultivarEpithet) => {
  if (unstandardizedCultivarEpithet) {
    const cleanString = unstandardizedCultivarEpithet.replace(
      /(^['"“”]+)|(['"“”]+$)/g,
      ""
    );
    return "'" + cleanString + "'";
  } else {
    return "";
  }
};

const standardizeTradeName = (unstandardizedTradeName) => {
  if (unstandardizedTradeName) {
    return unstandardizedTradeName.toUpperCase();
  } else {
    return "";
  }
};

const debounce = (func, delay) => {
  // thanks for the idea, chatGtp!
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), delay);
  };
};

const rankIdsToHideUnit2From = {
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
  subgenus: 190,
  section: 200,
  subsection: 210,
};
const { ...rest } = rankIdsToHideUnit2From;
const rankIdsToHideUnit3From = { ...rest, species: 220 };
const { ...rest2 } = rankIdsToHideUnit3From;

const rankIdsToHideUnit4From = {
  ...rest2,
  subspecies: 230,
  variety: 240,
  subvariety: 250,
  form: 260,
  subform: 270,
};
const { ...rest3 } = rankIdsToHideUnit4From;
const rankIdsToHideUnit5From = { ...rest3 };

const allRankIds = { ...rest3, cultivar: 300 };

function removeFromSciName(targetForRemoval) {
  const oldValue = document.getElementById("sciname").value;
  const newValue = oldValue
    .replace(targetForRemoval, "")
    .replace("  ", " ")
    .trim();
  document.getElementById("sciname").value = newValue;
  const scinameDisplay = document.getElementById("scinamedisplay");
  if (scinameDisplay) scinameDisplay.textContent = newValue;
}

async function handleFieldChange(
  form,
  silent = false,
  submitButtonId,
  submitText,
  originalForm
) {
  document.getElementById("error-display").textContent = "";
  const submitButton = document.getElementById(submitButtonId);
  submitButton.disabled = true;
  submitButton.textContent = "Checking for errors...";
  const isOk = await verifyLoadForm(form, silent, originalForm);
  if (!isOk) {
    submitButton.textContent = "Button Disabled";
    submitButton.disabled = true;
  } else {
    await updateFullname(form, true, true);
    submitButton.textContent = submitText;
    submitButton.disabled = false;
  }
}

async function verifyLoadFormCore(f, silent = false, originalForm) {
  console.log("deleteMe verifyLoadFormCore entered");
  console.log("deleteMe form is now: ");
  console.log(f);
  console.log("deleteMe originalForm is now: ");
  console.log(originalForm);
  const entryHasNotChanged = await isTheSameEntryAsItStarted(f, originalForm);
  if (entryHasNotChanged) {
    return true;
  }
  console.log("deleteMe got past the entry has not changed check");
  const isUniqueEntry = await checkNameExistence(f, true, originalForm);
  if (!isUniqueEntry) {
    return false;
  }
  if (f.unitname1.value == "") {
    if (!silent) alert("Unit Name 1 (genus or uninomial) field required.");
    document.getElementById("error-display").textContent =
      "Unit Name 1 (genus or uninomial) field required.";
    return false;
  }
  var rankId = f.rankid.value;
  if (rankId == "") {
    if (!silent) alert("Taxon rank field required.");
    document.getElementById("error-display").textContent =
      "Taxon rank field required.";
    return false;
  }
  return true;
}

function checkNameExistence(f, silent = false) {
  return new Promise((resolve, reject) => {
    console.log("deleteMe checkNameExistence called");
    if (!f?.sciname?.value || !f?.rankid?.value) {
      resolve(false);
    } else {
      $.ajax({
        type: "POST",
        url: "rpc/gettid.php",
        data: {
          sciname: f.sciname.value,
          rankid: f.rankid.value,
          author: f.author.value,
        },
        success: function (msg) {
          if (msg != "0") {
            if (!silent) {
              alert(
                "Taxon " +
                  f.sciname.value +
                  " " +
                  f.author.value +
                  " (" +
                  msg +
                  ") already exists in database"
              );
            }
            document.getElementById("error-display").textContent =
              "Taxon " +
              f.sciname.value +
              " " +
              f.author.value +
              " (" +
              msg +
              ") already exists in database";
            resolve(false);
          } else {
            resolve(true);
          }
        },
        error: function (error) {
          console.error("Error during AJAX request", error);
          reject(error);
        },
      });
    }
  });
}

async function updateFullnameCore(f, silent = false, skipCheck = false) {
  console.log("deleteMe updateFullnameCore entered");
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
  if (f.cultivarEpithet.value) {
    sciname += " " + standardizeCultivarEpithet(f.cultivarEpithet.value);
  }
  if (f.tradeName.value) {
    sciname += " " + standardizeTradeName(f.tradeName.value);
  }
  f.sciname.value = sciname.trim();

  console.log("deleteMe got here 1");
  if (!skipCheck) {
    await checkNameExistence(f, silent);
    console.log("deleteMe got here 2");
  }
  return sciname;
}

function isTheSameEntryAsItStarted(f, originalForm) {
  return new Promise((resolve, reject) => {
    if (f != null && originalForm != null && !hasChanged(f, originalForm)) {
      console.log("deleteMe got here b1");
      document.getElementById("error-display").textContent = "";
      resolve(true);
      return;
    } else {
      resolve(false);
      // reject("Forms not populated yet"); // @TODO decide if this is needed
    }
  });
}

function hasChanged(f, originalForm) {
  const newFormData = getFormData(f);
  const originalFormData = getFormData(originalForm);
  const returnVal = !shallowEqual(newFormData, originalFormData);
  return returnVal;
}

function getFormData(f) {
  const formData = {};
  const formElements = Array.from(f.elements || []);
  formElements.forEach((element) => {
    if (
      !element.name ||
      element.type === "button" ||
      element.type === "submit"
    ) {
      return;
    }
    if (element.type === "checkbox" || element.type === "radio") {
      formData[element.name] = element.checked; // Use `checked` for boolean values
    } else {
      formData[element.name] = element.value; // For other input types, use `value`
    }
  });
  return formData;
}

function shallowEqual(obj1, obj2) {
  const keys1 = Object.keys(obj1);
  const keys2 = Object.keys(obj2);

  if (keys1.length !== keys2.length) {
    return false;
  }

  for (let key of keys1) {
    if (obj1[key] !== obj2[key]) {
      return false;
    }
  }

  return true;
}
