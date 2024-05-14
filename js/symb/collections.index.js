//Used in /collections/index.php and /imagelib/index.php
$("html").hide();
$(document).ready(function () {
  $("html").show();
});

function toggle(target) {
  var ele = document.getElementById(target);
  if (ele) {
    if (ele.style.display === "none") {
      if (
        ele.id.substring(0, 5) === "minus" ||
        ele.id.substring(0, 4) === "plus"
      ) {
        ele.style.display = "inline";
      } else {
        ele.style.display = "block";
      }
    } else {
      ele.style.display = "none";
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

function togglePid(pid) {
  toggle("minus-" + pid);
  toggle("plus-" + pid);
  toggle("cat-" + pid);
  toggle("pid-ptext-" + pid);
  toggle("pid-mtext-" + pid);
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
      f.elements[i].name == "current-collid"
    )
      f.elements[i].checked = boxesChecked;
  }
}

function uncheckAll() {
  if (document.getElementById("dballcb")) {
    document.getElementById("dballcb").checked = false;
  }
  if (document.getElementById("dballspeccb")) {
    document.getElementById("dballspeccb").checked = false;
  }
  if (document.getElementById("dballobscb")) {
    document.getElementById("dballobscb").checked = false;
  }
}

function selectAllCat(cb, target) {
  var boxesChecked = true;
  if (!cb.checked) {
    boxesChecked = false;
    uncheckAll();
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
  if (catObj) catObj.checked = false;
  uncheckAll();
}

function selectAllPid(cb) {
  var boxesChecked = true;
  if (!cb.checked) {
    boxesChecked = false;
  }
  var target = "pid-" + cb.value;
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
  return formVerified;
}

function verifyOtherCatForm(f) {
  var pidElems = document.getElementsByName("pid[]");
  for (i = 0; i < pidElems.length; i++) {
    var pidElem = pidElems[i];
    if (pidElem.checked) return true;
  }
  var clidElems = document.getElementsByName("clid[]");
  for (i = 0; i < clidElems.length; i++) {
    var clidElem = clidElems[i];
    if (clidElem.checked) return true;
  }
  alert("Please choose at least one search region!");
  return false;
}

function checkKey(e) {
  var key;
  if (window.event) {
    key = window.event.keyCode;
  } else {
    key = e.keyCode;
  }
  if (key == 13) {
    document.collections.submit();
  }
}
