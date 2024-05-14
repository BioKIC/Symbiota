<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GamesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$dynClid = array_key_exists('dynclid', $_REQUEST) ? filter_var($_REQUEST['dynclid'], FILTER_SANITIZE_NUMBER_INT) : 0;

$gameManager = new GamesManager();
if($clid) $gameManager->setClid($clid);
elseif($dynClid) $gameManager->setDynClid($dynClid);
$clName = $gameManager->getClName();

$imgloc = "../images/games/namegame/";
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Name Game</title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
    ?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<style>
		.lettertable{border:1px solid #000000;border-spacing:3px;}
		.tableplain{border:1px}
		#charactertable td{margin-left: auto;margin-right: auto;vertical-align: middle;border:1px solid #000000;width:50px;cursor:pointer;font-family:times new roman;font-size:2.5rem;font-weight:bold;color:#000000}
		.buttonover{border:5px outset gray;cursor:pointer;font-weight:normal;font-weight:bold}
		.buttonout{border:5px outset #CCCC99;font-weight:normal}
		.buttondown{border:5px inset gray;cursor:pointer;font-weight:bold}
		.buttonup{border:5px outset #CCCC99;cursor:pointer;font-weight:normal;font-weight:bold}
		.question{font-size:2.5rem}
		#rw{margin-left:auto;margin-right:auto}
		.tr-center {
			text-align: center;
		}
	</style>
	<script>
		//COLLAPSE MENU
		function toggle(divID, linkID) {
			var ele = document.getElementById(divID);
			var text = document.getElementById(linkID);
			if(ele.style.display == "block") {
		    	ele.style.display = "none";
			}
			else {
				ele.style.display = "block";
			}
		}

		//CHANGE WORDLIST SCRIPT
		function getWordList(){
			$.ajax({
				method: "POST",
				url: "rpc/getwordlist.php",
				dataType: "json",
				data: {
					clid : <?php echo $clid; ?>,
					dynclid: <?php echo $dynClid; ?>
				},
				success: function(data) {
					mainList = data;
					generate();
				}
			});
		}

		function openPopup(urlStr,windowName){
			var wWidth = 900;
			try{
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
			}
			catch(e){
			}
			newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}

		mainList = [['','']];
		hangpics=[
			["<?php echo $imgloc; ?>man1_0.gif","<?php echo $imgloc; ?>man1_1.gif","<?php echo $imgloc; ?>man1_2.gif","<?php echo $imgloc; ?>man1_3.gif","<?php echo $imgloc; ?>man1_4.gif","<?php echo $imgloc; ?>man1_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>man1win.gif"],
			["<?php echo $imgloc; ?>woman1_0.gif","<?php echo $imgloc; ?>woman1_1.gif","<?php echo $imgloc; ?>woman1_2.gif","<?php echo $imgloc; ?>woman1_3.gif","<?php echo $imgloc; ?>woman1_4.gif","<?php echo $imgloc; ?>woman1_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>woman1win.gif"],
			["<?php echo $imgloc; ?>man2_0.gif","<?php echo $imgloc; ?>man2_1.gif","<?php echo $imgloc; ?>man2_2.gif","<?php echo $imgloc; ?>man2_3.gif","<?php echo $imgloc; ?>man2_4.gif","<?php echo $imgloc; ?>man2_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>man2win.gif"],
			["<?php echo $imgloc; ?>woman2_0.gif","<?php echo $imgloc; ?>woman2_1.gif","<?php echo $imgloc; ?>woman2_2.gif","<?php echo $imgloc; ?>woman2_3.gif","<?php echo $imgloc; ?>woman2_4.gif","<?php echo $imgloc; ?>woman2_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>woman2win.gif"],
			["<?php echo $imgloc; ?>wwoman0.gif","<?php echo $imgloc; ?>wwoman1.gif","<?php echo $imgloc; ?>wwoman2.gif","<?php echo $imgloc; ?>wwoman3.gif","<?php echo $imgloc; ?>wwoman4.gif","<?php echo $imgloc; ?>wwoman5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>wwomanwin.gif"],
			["<?php echo $imgloc; ?>flower0.gif","<?php echo $imgloc; ?>flower1.gif","<?php echo $imgloc; ?>flower2.gif","<?php echo $imgloc; ?>flower3.gif","<?php echo $imgloc; ?>flower4.gif","<?php echo $imgloc; ?>flower5.gif","<?php echo $imgloc; ?>flower6.gif","<?php echo $imgloc; ?>flower7.gif","<?php echo $imgloc; ?>flower8.gif","<?php echo $imgloc; ?>flower9.gif","<?php echo $imgloc; ?>flower10.gif","<?php echo $imgloc; ?>flower11.gif","<?php echo $imgloc; ?>flower12.gif","<?php echo $imgloc; ?>flowerwin.gif"],
			["<?php echo $imgloc; ?>plant0.gif","<?php echo $imgloc; ?>plant1.gif","<?php echo $imgloc; ?>plant2.gif","<?php echo $imgloc; ?>plant3.gif","<?php echo $imgloc; ?>plant4.gif","<?php echo $imgloc; ?>plant5.gif","<?php echo $imgloc; ?>plant6.gif","<?php echo $imgloc; ?>plant7.gif","<?php echo $imgloc; ?>plant8.gif","<?php echo $imgloc; ?>plant9.gif","<?php echo $imgloc; ?>plant10.gif","<?php echo $imgloc; ?>plant11.gif","<?php echo $imgloc; ?>plant12.gif","<?php echo $imgloc; ?>plantwin.gif"],
			["<?php echo $imgloc; ?>tempcover0.jpg","<?php echo $imgloc; ?>tempcover1.jpg","<?php echo $imgloc; ?>tempcover2.jpg","<?php echo $imgloc; ?>tempcover3.jpg","<?php echo $imgloc; ?>tempcover4.jpg","<?php echo $imgloc; ?>tempcover5.jpg","<?php echo $imgloc; ?>tempcover6.jpg","<?php echo $imgloc; ?>plant7.gif","<?php echo $imgloc; ?>plant8.gif","<?php echo $imgloc; ?>plant9.gif","<?php echo $imgloc; ?>plant10.gif","<?php echo $imgloc; ?>plant11.gif","<?php echo $imgloc; ?>plant12.gif","<?php echo $imgloc; ?>tempcover0.jpg"],
			["<?php echo $imgloc; ?>apple_0.gif","<?php echo $imgloc; ?>apple_1.gif","<?php echo $imgloc; ?>apple_2.gif","<?php echo $imgloc; ?>apple_3.gif","<?php echo $imgloc; ?>apple_4.gif","<?php echo $imgloc; ?>apple_5.gif","<?php echo $imgloc; ?>apple_6.gif","<?php echo $imgloc; ?>apple_7.gif","<?php echo $imgloc; ?>apple_8.gif","<?php echo $imgloc; ?>apple_9.gif","<?php echo $imgloc; ?>apple_10.gif","<?php echo $imgloc; ?>apple_11.gif","<?php echo $imgloc; ?>apple_12.gif","<?php echo $imgloc; ?>apple_win.gif"]
		];

		PreImage0 = new Image();
		PreImage1 = new Image();
		PreImage2 = new Image();
		PreImage3 = new Image();
		PreImage4 = new Image();
		PreImage5 = new Image();
		PreImage6 = new Image();

		defaultImage="<?php echo $imgloc; ?>plant7.gif";
		maxWildCards=1;

		imgSetId="imageset"; //  default avatar
		lastImgId = "img7";
		imgSetVal="6";
		lastImg="";

		firstload="1";
		levelSet="levelset"; //  default level
		lastLevelId = "level2";
		levelSetVal="6";
		lastLevelImg="";
		won=0;
		gameover=0;
		played=0;
		running=0;
		lastChar="";
		hintShown=0;
		wordChosen="";
		ns=document.getElementById&&!document.all;

		step=5;
		repeat="";

		function initNameGame(){

			mClick(imgSetId,lastImgId,imgSetVal,'<?php echo $imgloc; ?>plant_on.gif','<?php echo $imgloc; ?>plant_off.gif');
			mClick(levelSet,lastLevelId,levelSetVal,'<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif');

			tds=document.getElementById("charactertable").getElementsByTagName("TD");

			for(var i=0 ; i<tds.length ; i++){
				tds[i].getElementsByTagName("span")[0].onmouseover=function(){this.offsetParent.bgColor='#CCCC99';};
				tds[i].getElementsByTagName("span")[0].onmousedown=function(){/*this.offsetParent.bgColor='#FFFFFF';*/this.offsetParent.style.color='#FFFFFF';};
				tds[i].getElementsByTagName("span")[0].onmouseout=function(){this.offsetParent.bgColor='';this.offsetParent.style.color='#000000';};

				if(i<tds.length-1){
					tds[i].getElementsByTagName("span")[0].onclick=function(){getKey(this.id);};
				}

				if(i==tds.length-1){
					tds[i].getElementsByTagName("span")[0].onclick=function(){wildCard();};
				}
			}
			getWordList();
			generate();
		}

		function mOver(setId,imgId,imgOn){
			if(setId==imgSetId&&running==0){
				(lastImgId != imgId?document.getElementById(imgId).src = imgOn:"");
			}
		}

		function mOut(setId,imgId,imgOff){
			if(setId==imgSetId&&running==0){
				(lastImgId != imgId?document.getElementById(imgId).src = imgOff:"");
			}
		}

		function mClick(setId,imgId,imgVal,imgOn,imgOff){
			if(running==1) return;

			if(setId==imgSetId){
				document.getElementById(imgId).src = imgOn;

				if (lastImgId != ""){
					(lastImgId != imgId?document.getElementById(lastImgId).src = lastImg:"");
				}

				lastImgId = imgId;
				lastImg=imgOff;
				avatar=imgVal;
			}

			if(setId==levelSet){
				document.getElementById(imgId).src = imgOn;

				if (lastLevelId != ""){
					(lastLevelId != imgId?document.getElementById(lastLevelId).src = lastLevelImg:"");
				}

				lastLevelId = imgId;
				lastLevelImg=imgOff;
				levelSetVal=imgVal;
			}
			level();
		}

		function generate(){
			numbersRange=mainList.length; //range
			firstRun=true;
			selectedNums=new Array();

			for(var i=0;i<numbersRange;i++){
				wordChosen=false;
				rndnum=Math.floor(Math.random()*numbersRange);

				if(!firstRun){
					for(var j=0;j<selectedNums.length;j++){
						if(rndnum==selectedNums[j]){
							wordChosen=true;
							i--;
						}
					}
				}

				if(!wordChosen){
					selectedNums[i]=rndnum;
					firstRun=false;
				}
			}
			wordCount=0;
			newWord();
		}

		function newWord(){
			if(wordCount==selectedNums.length){
				// generate random list
				// Does this when you run out of words in current list
				getWordList();
				return;
			}

			lastChar="";
			running=0;
			clearTimeout(repeat);  // splash
			done=0;
			temp="";
			wildCount=0;
			hintShown=0;
			document.getElementById("hintdisplay").innerHTML="";
			charDisplay=document.getElementById("charactertable").getElementsByTagName('span');

			for(var k=0;k<charDisplay.length;k++){
				charDisplay[k].style.visibility="visible"; // show all hidden alphabet characters
			}

			currentNum=selectedNums[wordCount];
			chosenWord=mainList[currentNum][0].toLowerCase(); // chosen word
			RealName=chosenWord;

			/////////////////////////TAKE OUT THE VAR. or SUBSP. BECUASE IT'S TOO LONG///////////////////////////
			tempWord=chosenWord;
			varpos=tempWord.indexOf(" var.");
			ssppos=tempWord.indexOf(" subsp.");
			secondWord='';
			if (varpos != -1){
				subWord=tempWord.substring(0, varpos);
				secondWord=tempWord.substring(varpos);
				secondWord=secondWord.toUpperCase();
				chosenWord=subWord;
			}
			else if (ssppos != -1){
				subWord=tempWord.substring(0, ssppos);
				secondWord=tempWord.substring(ssppos);
				secondWord=secondWord.toUpperCase();
				chosenWord=subWord;
			}
			lengthofword=chosenWord;
			wordanswer=chosenWord;
			tempchosen = chosenWord;
			templength = 0;
			tempbuilder = "";
			wordLength = tempchosen.length;
			for(var m=0;m<wordLength;m++){
				if(tempchosen.charAt(m)!=" "){
					tempbuilder+=tempchosen.charAt(m);
				}
				else{
					tempbuilder+="\u00A0\u00A0\u00A0\u00A0";
					templength+=3;
				}
			}
			chosenWord = tempbuilder;

			initWildCard(chosenWord);
			wordLength=lengthofword.length; // word length
			wordLength+=templength;
			temp="";
			addedlength = 0;

			for(var n=0;n<wordLength;n++){
				if((chosenWord.charAt(n)!=" ")&&(chosenWord.charAt(n)!="\u00A0")){
					if(chosenWord.charAt(n)==".")
						temp+="."; // displays the periods
					else
						temp+="_"; // replace the characters in chosen word with empty places
				}
				else{
					temp+="\u00A0";
				}
			}
			document.getElementById("attempt").innerHTML=temp; // display empty places
			if ( firstload == "1"){
				firstload = "0";
			}
			last=temp; // remember last attempt
			wordChosen=1; // word has been selected
			wordCount++;
			document.getElementById("showhint").disabled=false;

			var now = new Date();

			PreImage0.src = 'tempcover0.jpg?' + now.getTime();
			PreImage1.src = 'tempcover1.jpg?' + now.getTime();
			PreImage2.src = 'tempcover2.jpg?' + now.getTime();
			PreImage3.src = 'tempcover3.jpg?' + now.getTime();
			PreImage4.src = 'tempcover4.jpg?' + now.getTime();
			PreImage5.src = 'tempcover5.jpg?' + now.getTime();
			PreImage6.src = 'tempcover6.jpg?' + now.getTime();
			level();
		}

		function newGame (){
			if (gameover == 0)
				played++;
			document.getElementById("plays").innerHTML=played;
			document.getElementById("rate").innerHTML=((won/played)*100).toFixed(2)+"%";
			gameover = 0;
			newWord();
		}

		function level(){
			if(running==1)return;
			guessCount=levelSetVal;

			if(avatar=="5"){
				if(guessCount=="12")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>flower12.gif";
				else if(guessCount=="6")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>flower6.gif";
				else if(guessCount=="3")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>flower3.gif";
			}
			else if(avatar=="6"){
				if(guessCount=="12")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant12.gif";
				else if(guessCount=="6")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant7.gif";
				else if(guessCount=="3")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant4.gif";
			}
			else if(avatar=="7"){				// IF IT'S THE REVEALING PLANT PICTURE
				if(guessCount=="12")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant12.gif";
				else if(guessCount=="6")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>tempcover6.jpg";
				else if(guessCount=="3")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>tempcover3.jpg";
			}
			else if(avatar=="8"){				// IF IT'S THE REVEALING PLANT PICTURE
				if(guessCount=="12")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>apple_12.gif";
				else if(guessCount=="6")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>apple_6.gif";
				else if(guessCount=="3")
					document.getElementById("hpic").src="<?php echo $imgloc; ?>apple_6.gif";
			}
			else if(guessCount=="12"){
				document.getElementById("hpic").src="<?php echo $imgloc; ?>spacer.gif";
			}
			else if((avatar>="0")&&(avatar<="4")){
				document.getElementById("hpic").src="<?php echo $imgloc; ?>gallows.gif";
			}

			document.getElementById("counter").innerHTML="Chances left = "+guessCount; // visual counter
			document.getElementById("splash").style.display="none";  // splash
		}

		function getKey(e){
			running=1;
			keyCode=(!ns)?event.keyCode:e.which; // which key has been pressed
			chkChar=e;
			temp="";

			if(keyCode){
				if(keyCode>=65&&keyCode<=90){ // if caps on
					keyCode+=32;
				}
				currentChar=keyCode; // character chosen by key
			}
			else{
				currentChar=chkChar.charCodeAt(0) // character chosen by mouse
			}

			if(currentChar==13){ // press return key to choose a new word
				newWord();
				return;
			}

			if(wordChosen==0){return;} // word not selected

			for(var k=0;k<wildCardArr.length;k++){ // remove from wildCardArr
				if(wildCardArr[k]==String.fromCharCode(currentChar)){
					wildCardArr.splice(k, 1);
				}
			}

			if(lastChar==currentChar||currentChar<44||currentChar>46&&currentChar<97||currentChar>122){
				return; // if not lowercase alphabet or if current key is the same as the last key pressed
			}

			if(document.getElementById(String.fromCharCode(currentChar)).style.visibility=="hidden"){return;}

			correct=false
			document.getElementById(String.fromCharCode(currentChar)).style.visibility="hidden"; // hide chosen character

			for(var n=0;n<last.length;n++){ // run through chosen word characters
				if(String.fromCharCode(currentChar)==chosenWord.charAt(n)){ // if selected character is in the chosen word
					temp+=chosenWord.charAt(n); // replace empty place with character
					correct=true;
				}
				else{
					temp+=last.charAt(n); // replace with empty place
				}
			}

			if(correct==false&&guessCount>0){ // if selected character not correct
				guessCount--; // deduct 1 from guesses left
				document.getElementById("hpic").src=hangpics[avatar][guessCount]; // show pic
			}

			document.getElementById("attempt").innerHTML=temp.toUpperCase(); // change correct character chosen to uppercase
			document.getElementById("counter").innerHTML="Chances left = "+guessCount; // visual counter

			last=temp; // remember last attempt
			lastChar=currentChar; // remember last character selected

			if(guessCount==0){ // if counter reaches zero
				wordChosen=0;
				gameover=1;
				document.getElementById("counter").innerHTML="<div id='rw' style='width:190px;text-align:center;' onmouseover=\"this.className='buttonover'\" onmouseout=\"this.className='buttonout'\" onmousedown=\"this.className='buttondown'\" onmouseup=\"this.className='buttonup'\" class='buttonout' onclick='showWord()'><b>Reveal the Species</b></div>";
				played++;
				document.getElementById("plays").innerHTML=played;
				var myNewString = RealName.replace(/\u00A0\u00A0\u00A0\u00A0/g, "%20");
				document.getElementById("splash").innerHTML="<div style='font-size:20px;color:red;text-align:center;'>Too Bad</div><div style='font-size:16px;color:#0000FF;text-align:center;'><a href='#' onClick=\"openPopup('../taxa/index.php?taxon="+myNewString+"','tpwin');\"><b>Click here for more about this species</b></a></div>"; // splash
				document.getElementById("splash").style.display="";  // splash
				document.getElementById("rate").innerHTML=((won/played)*100).toFixed(0)+"%";
				gameEnd(); // splash
			}

			if(temp==chosenWord){ // if correct word found
				wordChosen=0;
				gameover=1;
				played++;
				document.getElementById("plays").innerHTML=played;
				won++;
				document.getElementById("wins").innerHTML=won;
				var myNewString = RealName.replace(/\u00A0\u00A0\u00A0\u00A0/g, "%20");
				if (secondWord!='')
					document.getElementById("attempt").innerHTML=chosenWord.toUpperCase()+"<br><span style=\"font-size:12px\">"+secondWord+"</span>"; // change chosen word to uppercase
				else
					document.getElementById("attempt").innerHTML=chosenWord.toUpperCase(); // change chosen word to uppercase
				document.getElementById("splash").innerHTML="<font color = \"#336699\">You Win!</font><br><a href = \"#\" onClick=\"openPopup('../taxa/index.php?taxon="+myNewString+"','tpwin')\"> <font size = \"4\" color = \"#0000FF\"><center><u><b><br>Click here for more about this species</b></u></center></font></a><br>"; // splash
				document.getElementById("hintdisplay").innerHTML=mainList[currentNum][1]; //show the family
				document.getElementById("splash").style.display="";  // splash
				document.getElementById("rate").innerHTML=((won/played)*100).toFixed(0)+"%";
				document.getElementById("hpic").src=hangpics[avatar][13];
				gameEnd(); // splash
			}

			if(guessCount==1){
				document.getElementById("showhint").disabled=true;
				document.getElementById("?").style.visibility="hidden";
			}
		}

		function showHint(){
			running=1;
			if(guessCount<=1||done==1){return;}
			if(hintShown==0){
				guessCount--;
				hintShown=1;
				document.getElementById("hintdisplay").innerHTML=mainList[currentNum][1]; //show the family
				document.getElementById("counter").innerHTML="Chances left = "+guessCount; // visual counter
				document.getElementById("hpic").src=hangpics[avatar][guessCount];
			}
			document.getElementById("showhint").disabled=true;
		}

		function showWord(){ // reveals the chosen word if counter reaches zero
			if(wordChosen==0&&guessCount!=0)
				{return;}
			if (secondWord!='')
				document.getElementById("attempt").innerHTML=chosenWord.toUpperCase()+"<br><span style=\"font-size:12px\">"+secondWord+"</span>"; // change chosen word to uppercase
			else
				document.getElementById("attempt").innerHTML=chosenWord.toUpperCase(); // change chosen word to uppercase
			hintShown=1;
			document.getElementById("hintdisplay").innerHTML=/*list+"<br>"+*/mainList[currentNum][1];
			clearTimeout(repeat);  // splash
			document.getElementById("rw").style.display="none";
		}

		function gameEnd(){
			done=1;
			sFont=1; // Smallest font size
			lFont=50; // Largest font size
			goSplash();
		}

		function goSplash(){
			document.getElementById("splash").style.visibility="visible";
			document.getElementById("splash").style.fontSize=30;
			sFont+=step;
			repeat=setTimeout("goSplash()",10); // Speed
			if(sFont>lFont){
				clearTimeout(repeat);
			}
		}

		function initWildCard(str){
			wildCardArr=[];
			wildCardArr[0]=str.charAt(0);
			for(var i=0;i<str.length;i++){
				isIn=0;
				for(var j=0;j<wildCardArr.length;j++){
					if(str.charAt(i)==wildCardArr[j]){
						isIn=1;
					}
				}
				if(isIn==0&&str.charAt(i)!=" "){
					wildCardArr[wildCardArr.length]=str.charAt(i);
				}
			}
			wildWordLength=wildCardArr.length; // for checking how may wild cards used against length of word
		}

		function wildCard(){
			if(wildCardArr.length==0||guessCount<=1){return;}
			wildCount++;
			rdm=Math.floor(Math.random()*wildCardArr.length);
			wildCardChar=wildCardArr[rdm]; //.splice(rdm, 1).toString()
			if(wildCount==maxWildCards){
				document.getElementById("?").style.visibility="hidden";
			}
			guessCount--;
			document.getElementById("hpic").src=hangpics[avatar][guessCount];
			getKey(wildCardChar);
		}

	</script>
</head>
<body onload="initNameGame()">
	<?php
	$displayLeftMenu = (isset($games_namegameMenu)?$games_namegameMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="../index.php">Home</a> &gt;&gt; ';
	if(isset($games_namegameCrumbs) && $games_namegameCrumbs){
		echo $games_namegameCrumbs;
	}
	else{
		echo '<a href="../checklists/checklist.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&dynclid=' . htmlspecialchars($dynClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
		echo $clName;
		echo '</a> &gt;&gt; ';
	}
	echo ' <b>Name Game</b>';
	echo '</div>';
	?>
	<div role="main" id="innertext">
		<div style="width:100%;text-align:center;">
			<h1 class="page-heading screen-reader-only">Name Guessing Game</h1>
		</div>
		<div style="width:100%;text-align:center;margin:10px;">
			I am thinking of a species found within the following checklist: <b><?php echo $clName;?></b><br/>
			What am I thinking of?
		</div>
		<div style="width:140px;margin-left:auto;margin-right:auto;margin-top:20px;">
			<div id="imageset" style="cursor:pointer;">
				<img onclick="mClick(this.parentNode.id,this.id,'6','<?php echo $imgloc; ?>plant_on.gif','<?php echo $imgloc; ?>plant_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>plant_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>plant_off.gif')" src="<?php echo $imgloc; ?>plant_off.gif" id="img7" alt="Image of a plant">
				<img onclick="mClick(this.parentNode.id,this.id,'5','<?php echo $imgloc; ?>flower_on.gif','<?php echo $imgloc; ?>flower_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>flower_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>flower_off.gif')" src="<?php echo $imgloc; ?>flower_off.gif" id="img6" alt="Image of a flower">
				<img onclick="mClick(this.parentNode.id,this.id,'8','<?php echo $imgloc; ?>apple_on.gif','<?php echo $imgloc; ?>apple_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>apple_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>apple_off.gif')" src="<?php echo $imgloc; ?>apple_off.gif" id="img8" alt="Image of an apple">
				<!--<img onclick="mClick(this.parentNode.id,this.id,'0','<?php echo $imgloc; ?>man1_head_on.gif','<?php echo $imgloc; ?>man1_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man1_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man1_head_off.gif')" src="<?php echo $imgloc; ?>man1_head_off.gif" id="img1">-->
				<!--<img onclick="mClick(this.parentNode.id,this.id,'1','<?php echo $imgloc; ?>woman1_head_on.gif','<?php echo $imgloc; ?>woman1_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman1_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman1_head_off.gif')" src="<?php echo $imgloc; ?>woman1_head_off.gif" id="img2">-->
				<!--<img onclick="mClick(this.parentNode.id,this.id,'2','<?php echo $imgloc; ?>man2_head_on.gif','<?php echo $imgloc; ?>man2_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man2_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man2_head_off.gif')" src="<?php echo $imgloc; ?>man2_head_off.gif" id="img3">-->
				<!--<img onclick="mClick(this.parentNode.id,this.id,'3','<?php echo $imgloc; ?>woman2_head_on.gif','<?php echo $imgloc; ?>woman2_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman2_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman2_head_off.gif')" src="<?php echo $imgloc; ?>woman2_head_off.gif" id="img4">-->
				<!--<img onclick="mClick(this.parentNode.id,this.id,'4','<?php echo $imgloc; ?>wwoman_head_on.gif','<?php echo $imgloc; ?>wwoman_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>wwoman_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>wwoman_head_off.gif')" src="<?php echo $imgloc; ?>wwoman_head_off.gif" id="img5">-->
				<!--<img onclick="mClick(this.parentNode.id,this.id,'7','<?php echo $imgloc; ?>hidden_on.gif','<?php echo $imgloc; ?>hidden_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>hidden_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>hidden_off.gif')" src="<?php echo $imgloc; ?>hidden_off.gif" id="img8">-->
			</div>
		</div>
		<div style="width:600px;margin-left:auto;margin-right:auto;margin-top:20px;">
			<div style="float:left;width:250px;">
				<div style="width:150px;margin-left:auto;margin-right:auto;">
					<img id="hpic" style="width:150px;" src="<?php echo $imgloc; ?>plant7.gif" alt="Image of a plant">
				</div>
				<div id="counter" style="text-align:center;width:190px;margin-left:auto;margin-right:auto;">Chances left = 6</div>
			</div>
			<div style="float:right;width:250px;">
				<div style="clear:both;width:150px;margin-left:auto;margin-right:auto;margin-top:20px;">
					<div style="margin-top:30px;">
						<b>Difficulty</b>
					</div>
					<div id="levelset" style="cursor:pointer">
						Hard <img onclick="mClick(this.parentNode.id,this.id,'3','<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif')" src="<?php echo $imgloc; ?>radio_off4.gif" id="level1" alt="Hard">
						<img onclick="mClick(this.parentNode.id,this.id,'6','<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif')" src="<?php echo $imgloc; ?>radio_off4.gif" id="level2" alt="Medium">
						<img onclick="mClick(this.parentNode.id,this.id,'12','<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif')" src="<?php echo $imgloc; ?>radio_off4.gif" id="level3" alt="Easy"> Easy
					</div>
					<div style="margin-top:10px;">
						Games Played <span id="plays">0</span><br>
						Games Won <span id="wins">0</span><br>
						Success Rate <span id="rate">0</span>
					</div>
				</div>
				<div style="width:250px;margin-left:auto;margin-right:auto;margin-top:15px;">
					<div id="showhint" style="text-align:center;width:110px;float:left;" onclick="showHint();" onmouseover="this.className='buttonover'" onmouseout="this.className='buttonout'" onmousedown="this.className='buttondown'" onmouseup="this.className='buttonup'" class="buttonout">Show Family</div>
					<div id="newgame" style="text-align:center;width:90px;float:right;" onclick="newGame();" onmouseover="this.className='buttonover'" onmouseout="this.className='buttonout'" onmousedown="this.className='buttondown'" onmouseup="this.className='buttonup'" class="buttonout">New Game</div>
				</div>
			</div>
		</div>
		<div style="clear:both;width:100%;text-align:center;padding-top:2rem;">
			<div id="hintdisplay" style="font-size:2rem;"></div>
		</div>
		<div style="clear:both;width:100%;text-align:center;padding-top:2rem;">
			<div id="attempt" style="letter-spacing:0.75rem;font-weight:bold;font-size:2rem"></div>
		</div>
		<div style="clear:both;width:100%;text-align:center;padding-top:2rem;">
			<div id="splash" style="color:#336699"></div>
		</div>
		<div style="clear:both;width:450px;margin-left:auto;margin-right:auto;margin-top:10px;">
			<table id="charactertable" class="lettertable">
				<tr class="tr-center">
					<td><span id="a" style="display:block">A</span></td>
					<td><span id="b" style="display:block">B</span></td>
					<td><span id="c" style="display:block">C</span></td>
					<td><span id="d" style="display:block">D</span></td>
					<td><span id="e" style="display:block">E</span></td>
					<td><span id="f" style="display:block">F</span></td>
					<td><span id="g" style="display:block">G</span></td>
					<td><span id="h" style="display:block">H</span></td>
					<td><span id="i" style="display:block">I</span></td>
				</tr>
				<tr class="tr-center">
					<td><span id="j" style="display:block">J</span></td>
					<td><span id="k" style="display:block">K</span></td>
					<td><span id="l" style="display:block">L</span></td>
					<td><span id="m" style="display:block">M</span></td>
					<td><span id="n" style="display:block">N</span></td>
					<td><span id="o" style="display:block">O</span></td>
					<td><span id="p" style="display:block">P</span></td>
					<td><span id="q" style="display:block">Q</span></td>
					<td><span id="r" style="display:block">R</span></td>
				</tr>
				<tr class="tr-center">
					<td><span id="s" style="display:block">S</span></td>
					<td><span id="t" style="display:block">T</span></td>
					<td><span id="u" style="display:block">U</span></td>
					<td><span id="v" style="display:block">V</span></td>
					<td><span id="w" style="display:block">W</span></td>
					<td><span id="x" style="display:block">X</span></td>
					<td><span id="y" style="display:block">Y</span></td>
					<td><span id="z" style="display:block">Z</span></td>
					<td>
						<span id="qmark" style="display:block;" class="question"  title="Wild Card">?</span>
					</td>
				</tr>
			</table>
		</div>
		<div style="width:450px;margin-left:auto;margin-right:auto;margin-top:20px;">
			<div>
				How to play:
				<ul>
					<li>Type or click on a letter to guess
					<li>Difficulty level affects how many chances you get: 3, 6, or 12
					<li>Showing the family uses one of your chances
					<li>Using the wild card [?] uses one of your chances
					<li>Spaces are already displayed for you
					<li>You cannot change settings settings while in the middle of a game
					<li>The "Show Family"/wild card cannot be used if you are down to your last guess
				</ul>
			</div>
		</div>
	</div>
	<!-- This ends inner text! -->
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>