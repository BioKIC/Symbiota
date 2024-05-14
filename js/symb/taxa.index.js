var imageArr = new Array();
var imgCnt = 0;

$(document).ready(function() {
	$('#desctabs').tabs();
	$("#desctabs").show();

	var imgDiv = document.getElementById("img-div");
	if(imgDiv.scrollHeight > imgDiv.clientHeight) document.getElementById("img-tab-div").style.display = 'block'; 

});

function toggle(target){
	var divObjs = document.getElementsByTagName("span");
  	for (i = 0; i < divObjs.length; i++) {
  		var divObj = divObjs[i];
  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
			if(divObj.style.display=="none"){
				divObj.style.display="";
			}
		 	else {
		 		divObj.style.display="none";
		 	}
		}
	}
}

function toggleLinks(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			ele.style.display="block";
        }
	 	else {
	 		ele.style.display="none";
        }
	}
	event.preventDefault();
	$('html,body').animate({scrollTop:$("#"+target).offset().top}, 500);
}

function toggleMap(mapObj){
	var roi = mapObj.value;
	var mapObjs = getElementByTagName("div");
	for(x=0;x<mapObjs.length;x++){
		var mObj = mapObjs[x];
		if(mObj.classname == "mapdiv"){
			if(mObj == mapObj){
				mObj.style.display = "block";
			}
			else{
				mObj.style.display = "none";
			}
		}
	}
}

function toggleImgInfo(target, anchorObj){
	//close all imgpopup divs
	var divs = document.getElementsByTagName("div");
	for(x=0;x<divs.length;x++){
		var d = divs[x];
		if(d.getAttribute("class") == "imgpopup" || d.getAttribute("className") == "imgpopup"){
			d.style.display = "none";
		}
	}

	//Open and place target imgpopup
	var obj = document.getElementById(target);
	var pos = findPos(anchorObj);
	var posLeft = pos[0];
	if(posLeft > 550){
		posLeft = 550;
	}
	obj.style.left = posLeft;
	obj.style.top = pos[1];
	if(obj.style.display=="block"){
		obj.style.display="none";
	}
	else {
		obj.style.display="block";
	}
	var targetStr = "document.getElementById('" + target + "').style.display='none'";
	var t=setTimeout(targetStr,10000);
}

function findPos(obj){
	var curleft = 0; 
	var curtop = 0;
	curleft = obj.offsetLeft;
	curtop = obj.offsetTop;
	return [curleft,curtop];
}	

function expandExtraImages(){
	document.getElementById("img-div").style.overflow = "visible";
	document.getElementById("img-tab-div").style.display = "none";
}

function openMapPopup(taxonVar,clid,leaflet=false){
	var popupMap = window.open(`../collections/map/${leaflet?'leafletmap.php': 'googlemap.php'}?usethes=1&taxa=${taxonVar}`,'gmap','toolbar=0,scrollbars=1,width=950,height=700,left=20,top=20');
    if (popupMap.opener == null) popupMap.opener = self;
    popupMap.focus();
}

function openPopup(url){
    var wWidth = 1000;
    if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
	if(wWidth > 1200) wWidth = 1200;
    newWindow = window.open(url,'genericPopup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
    if(newWindow.opener == null) newWindow.opener = self;
    return false;
}
