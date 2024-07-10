$(document).ready(function () {
  setHeight();

  $("#tabs1").tabs({
    beforeLoad: function (event, ui) {
      $(ui.panel).html("<p>Loading...</p>");
    },
    active: 1,
  });
  var hijax = function (panel) {
    $(".pagination a", panel).click(function () {
      $(panel).load(this.href, {}, function () {
        hijax(this);
      });
      return false;
    });
  };
  $("#tabs2").tabs({
    beforeLoad: function (event, ui) {
      $(ui.panel).html("<p>Loading...</p>");
    },
    load: function (event, ui) {
      hijax(ui.panel);
    },
  });
  $("#tabs3").tabs({
    beforeLoad: function (event, ui) {
      $(ui.panel).html("<p>Loading...</p>");
    },
  });

  $("#accordion").accordion({
    collapsible: true,
    heightStyle: "fill",
  });

  $("#loadingOverlay").popup({
    transition: "all 0.3s",
    scrolllock: true,
    opacity: 0.5,
    color: "white",
  });
});

$(window).resize(function () {
  setHeight();
  $("#accordion").accordion("refresh");
});

$(document).on("pageloadfailed", function (event, data) {
  event.preventDefault();
});

function setHeight() {
  var winHeight = window.innerHeight;
  var mapInterface = document.getElementById("mapinterface");
  var loadingOverlay = document.getElementById("loadingOverlay");
  if(mapInterface) mapInterface.style.height = winHeight + "px";
  if(loadingOverlay) loadingOverlay.style.height = winHeight + "px";
}

function checkRecordLimit(f) {
  var recordLimit = document.getElementById("recordlimit").value;
  if (!isNaN(recordLimit) && recordLimit > 0) {
    if (recordLimit > 50000) {
      alert("Record limit cannot exceed 50000.");
      document.getElementById("recordlimit").value = 5000;
      return;
    }
    if (recordLimit <= 50000) {
      if (recordLimit > 5000) {
        if (
          confirm(
            "Increasing the record limit can cause delays in loading the map, or for your browser to crash."
          )
        ) {
          return true;
        } else {
          document.getElementById("recordlimit").value = 5000;
        }
      }
    }
  } else {
    document.getElementById("recordlimit").value = 5000;
    alert("Record Limit must be set to a whole number greater than zero.");
  }
}

//Generate Random Color with luminance greater than 40 for good contrast on black text
function generateRandColor() {
	// How Bright we want the color
	// If this number needs to change extra work would need to be done to ensure variable luminance would work.
	const luminance = 40;

	const redLuminanceCoef = 0.299;
	const greenLuminanceCoef = 0.587;
	const blueLuminanceCoef = 0.114;

	// max red 76.245
	// min red is 0
	let r = Math.round(255 * Math.random());
	let redLuminance = redLuminanceCoef * r;

	//max is 29.07
	//min blue is 0
	let b = Math.round(255 * Math.random());
	let blueLuminance = blueLuminanceCoef * b;

	//max g is 149.685  min green 
	let minGreen = ((luminance * 3) - redLuminance - blueLuminance) / greenLuminanceCoef;
	let g = Math.round((255 - minGreen) * Math.random()) + minGreen;

	return (r < 16? "0": "") + parseInt(r).toString(16) + (g < 16? "0": "") + parseInt(g).toString(16) + (b < 16? "0": "") + parseInt(b).toString(16);
}

function toggleLatLongDivs() {
  var divs = document.getElementsByTagName("div");
  for (i = 0; i < divs.length; i++) {
    var obj = divs[i];
    if (
      obj.getAttribute("class") == "latlongdiv" ||
      obj.getAttribute("className") == "latlongdiv"
    ) {
      if (obj.style.display == "none") {
        obj.style.display = "block";
      } else {
        obj.style.display = "none";
      }
    }
  }
}

function toggle(target) {
  var ele = document.getElementById(target);
  if (ele) {
    if (ele.style.display == "none") {
      ele.style.display = "block";
    } else {
      ele.style.display = "none";
    }
  } else {
    var divObjs = document.getElementsByTagName("div");
    for (i = 0; i < divObjs.length; i++) {
      var divObj = divObjs[i];
      if (
        divObj.getAttribute("class") == target ||
        divObj.getAttribute("className") == target
      ) {
        if (divObj.style.display == "none") {
          divObj.style.display = "block";
        } else {
          divObj.style.display = "none";
        }
      }
    }
  }
}

function toggleCat(catid) {
  toggle("minus-" + catid);
  toggle("plus-" + catid);
  toggle("cat-" + catid);
  toggle("ptext-" + catid);
  toggle("mtext-" + catid);
}

function selectAll(cb) {
  var boxesChecked = true;
  if (!cb.checked) {
    boxesChecked = false;
  }
  var f = cb.form;
  for (var i = 0; i < f.length; i++) {
    if (
      f.elements[i].name == "db[]" ||
      f.elements[i].name == "cat[]" ||
      f.elements[i].name == "occid[]"
    ) {
      f.elements[i].checked = boxesChecked;
    }
    if (f.elements[i].name == "occid[]") {
      f.elements[i].onchange();
    }
  }
}

function uncheckAll(f) {
  document.getElementById("dballcb").checked = false;
}

function selectAllCat(cb, target) {
  var boxesChecked = true;
  if (!cb.checked) {
    boxesChecked = false;
  }
  var inputObjs = document.getElementsByTagName("input");
  for (i = 0; i < inputObjs.length; i++) {
    var inputObj = inputObjs[i];
    if (
      inputObj.getAttribute("class") == target ||
      inputObj.getAttribute("className") == target
    ) {
      inputObj.checked = boxesChecked;
    }
  }
}

function unselectCat(catTarget) {
  var catObj = document.getElementById(catTarget);
  catObj.checked = false;
  uncheckAll();
}

function verifyCollForm(f) {
  var formVerified = false;
  for (var h = 0; h < f.length; h++) {
    if (f.elements[h].name == "db[]" && f.elements[h].checked) {
      formVerified = true;
      break;
    }
    if (f.elements[h].name == "cat[]" && f.elements[h].checked) {
      formVerified = true;
      break;
    }
  }

  if (!formVerified) {
    alert("Please choose at least one collection!");
    return false;
  } else {
    for (var i = 0; i < f.length; i++) {
      if (f.elements[i].name == "cat[]" && f.elements[i].checked) {
        if (document.getElementById("cat-" + f.elements[i].value)) {
          //Uncheck all db input elements within cat div
          var childrenEle = document.getElementById(
            "cat-" + f.elements[i].value
          ).children;
          for (var j = 0; j < childrenEle.length; j++) {
            if (childrenEle[j].tagName == "DIV") {
              var divChildren = childrenEle[j].children;
              for (var k = 0; k < divChildren.length; k++) {
                var divChildren2 = divChildren[k].children;
                for (var l = 0; l < divChildren2.length; l++) {
                  if (divChildren2[l].tagName == "INPUT") {
                    divChildren2[l].checked = false;
                  }
                }
              }
            }
          }
        }
      }
    }
  }
  //make sure they have filled out at least one field.
  if (
    f.taxa.value == "" &&
    f.country.value == "" &&
    f.state.value == "" &&
    f.county.value == "" &&
    f.locality.value == "" &&
    f.upperlat.value == "" &&
    f.pointlat.value == "" &&
    f.polycoords.value == "" &&
    f.collector.value == "" &&
    f.collnum.value == "" &&
    f.eventdate1.value == "" &&
    f.catnum.value == ""
  ) {
    alert("Please fill in at least one search parameter!");
    return false;
  }

  if (
    f.upperlat.value != "" ||
    f.bottomlat.value != "" ||
    f.leftlong.value != "" ||
    f.rightlong.value != ""
  ) {
    // if Lat/Long field is filled in, they all should have a value!
    if (
      f.upperlat.value == "" ||
      f.bottomlat.value == "" ||
      f.leftlong.value == "" ||
      f.rightlong.value == ""
    ) {
      alert(
        "Error: Please make all Lat/Long bounding box values contain a value or all are empty"
      );
      return false;
    }

    // Check to make sure lat/longs are valid.
    if (
      Math.abs(f.upperlat.value) > 90 ||
      Math.abs(f.bottomlat.value) > 90 ||
      Math.abs(f.pointlat.value) > 90
    ) {
      alert("Latitude values can not be greater than 90 or less than -90.");
      return false;
    }
    if (
      Math.abs(f.leftlong.value) > 180 ||
      Math.abs(f.rightlong.value) > 180 ||
      Math.abs(f.pointlong.value) > 180
    ) {
      alert("Longitude values can not be greater than 180 or less than -180.");
      return false;
    }
    if (parseFloat(f.upperlat.value) < parseFloat(f.bottomlat.value)) {
      alert(
        "Your northern latitude value is less then your southern latitude value. Please correct this."
      );
      return false;
    }
    if (parseFloat(f.leftlong.value) > parseFloat(f.rightlong.value)) {
      alert(
        "Your western longitude value is greater then your eastern longitude value. Please correct this. Note that western hemisphere longitudes in the decimal format are negitive."
      );
      return false;
    }
  }

  //Same with point radius fields
  if (
    f.pointlat.value != "" ||
    f.pointlong.value != "" ||
    f.radius.value != ""
  ) {
    if (
      f.pointlat.value == "" ||
      f.pointlong.value == "" ||
      f.radius.value == ""
    ) {
      alert(
        "Error: Please make all Lat/Long point-radius values contain a value or all are empty"
      );
      return false;
    }
  }
  return true;
}

function resetQueryForm(f) {
  $("input[name=taxa]").val("");
  $("input[name=country]").val("");
  $("input[name=state]").val("");
  $("input[name=county]").val("");
  $("input[name=local]").val("");
  $("input[name=collector]").val("");
  $("input[name=collnum]").val("");
  $("input[name=eventdate1]").val("");
  $("input[name=eventdate2]").val("");
  $("input[name=catnum]").val("");
  $("input[name=othercatnum]").val("");
  $("input[name=typestatus]").attr("checked", false);
  $("input[name=hasimages]").attr("checked", false);
  $("input[name=hasgenetic]").attr("checked", false);
  $("input[name=includecult]").attr("checked", false);
  const crossPortalForm = $("autocomplete-input[name=external-taxa-input]");
  if(crossPortalForm && crossPortalForm[0]) {
    crossPortalForm[0].getInputElement().value="";
  }
  document.dispatchEvent(new Event('deleteShape'));
  document.dispatchEvent(new Event('resetMap'));
}

function prepSelectionKml(f) {
  if (f.kmltype.value == "dsselectionquery") {
    if (dsselections.length != 0) {
      var jsonSelections = JSON.stringify(dsselections);
    } else {
      alert("Please select records from the dataset to create KML file.");
      return;
    }
  } else {
    var jsonSelections = JSON.stringify(selections);
  }
  f.selectionskml.value = jsonSelections;
  f.starrkml.value = starr;
  f.submit();
}

function openRecord(record) {
   let url = record.host? 
      `${record.host}/collections/individual/index.php?occid=${record.occid}` :
      "../individual/index.php?occid=" + record.occid 
   openPopup(url);
}

function openPopup(urlStr) {
  var wWidth = 1000;
  try {
    if (opener.document.body.offsetWidth)
      wWidth = opener.document.body.offsetWidth * 0.95;
    if (wWidth > 1400) wWidth = 1400;
  } catch (err) {}
  newWindow = window.open(
    urlStr,
   "_blank",
    "scrollbars=1,toolbar=0,resizable=1,width=" +
      wWidth +
      ",height=600,left=20,top=20"
  );
  if (newWindow.opener == null) newWindow.opener = self;
  return false;
}
