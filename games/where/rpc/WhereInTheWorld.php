<?php
include_once('../../../config/symbini.php');
include_once('../classes/GamesWhereManager.php');
include_once('../classes/SearchArea.php');
header("Content-Type: text/html; charset=".$CHARSET);

$debug = (isset($_GET['Debug'])?true:false);
$stateString = (isset($_GET['States']) ? htmlspecialchars($_GET['States']) : '');
$minLat = (isset($_GET['MinLat'])?$_GET['MinLat']:0);
$maxLat = (isset($_GET['MaxLat'])?$_GET['MaxLat']:0);
$minLon = (isset($_GET['MinLon'])?$_GET['MinLon']:0);
$maxLon = (isset($_GET['MaxLon'])?$_GET['MaxLon']:0);

$whereManager = new GamesWhereManager();
$whereManager->setStateStr($stateString);

//$debug=true;
if($debug){
	$whereManager->setDebugMode(true);
	echo "Error: MinLat=".substr($minLat,0,5)."&MaxLat=".substr($maxLat,0,5)."&MinLon=".substr($minLon,0,5)."&MaxLon=".substr($maxLon,0,5)."<br>";
	if($debug == 2) die();
}

//*************************************************************************
//Update hit list
$Endtime = getdate(strtotime('tomorrow-7 hours'));
$Today = getdate(time()-3600*7);
$Date = $Today[month]."-".$Today[mday]."-".$Today[year];
$Sessions = json_decode(file_get_contents("Sessions.txt"),true);

if(!isset($_COOKIE['Where']) || !isset($Sessions[$Date]))
	{
	if(array_key_exists($Date,$Sessions))
		$Sessions[$Date]++;
	else
		$Sessions[$Date] = 1;
	setcookie("Where","1", strtotime('tomorrow'));
	}
$Hits = json_decode(file_get_contents("Hits.txt"),true);
if(array_key_exists($Date,$Hits))
	$Hits[$Date]++;
else
	$Hits[$Date] = 1;
$OutString = json_encode($Hits);
$Result = file_put_contents("Hits.txt",$OutString);

$whereManager->setBoundingBox($minLat, $maxLat, $minLon, $maxLon);

if(!$stateString && ($whereManager->getMinLon > $whereManager->getMaxLon || $whereManager->getMinLat > $whereManager->getMaxLat))
	die("Error: Invalid region.  State ($stateString) not visible?");

//************************************************************************************************************
//**************************** Start of main routine *********************************************************
//************************************************************************************************************

//Load the coordinates that are known to be artifically georeferenced to the middle of a town, state, country, etc.
$TabooCoor = file_get_contents("TabooCoor.txt");
$QueryAdd .= "and CONCAT(decimalLatitude,'#',decimalLongitude) NOT IN ($TabooCoor)";

//Load the coordinates of the ocean exclusion rectangles.
if(!$whereManager->checkOcean()) die("Error: No significant specimens in the selected area.");

//************************************************************************
//  Region zeroed in to.  Find specific location and a list of plants.
//************************************************************************

$resultArray = $whereManager->getResultArr();

//************************************************************************
//  Assemble the list of plants to return to the html program.
//************************************************************************
$Plants = array();//This is an array where the keys are scinames, and the values are counts.
$Names = array();
$Count = array();
$LonArray = array(); //Used to find the median coordinates that determine the target location.
$LatArray = array();
$Content = ""; //The string that gets returned to WITW.
$Hint="";
foreach($resultArray as $One)
	{
	$SciName = $One['sciname'];
	$Preg = preg_split("/[\s]+/", $One['sciname']); //Convert any sciname to "GENUS species", ignoring var., ssp. etc.
	$SciName = $Preg[0]." ".$Preg[1];
	if(array_key_exists($SciName,$Plants))
		$Plants[$SciName]++; //Already in the array, increment.
	else
		$Plants[$SciName] = 1; //Add to the array.

	if($debug)
		$whereManager->printr($Plants,"Plants");

	$LonArray[] = $One['decimalLongitude'];
	$LatArray[] = $One['decimalLatitude'];
	if(strlen($Hint) < strlen($One['habitat']))
		{ //There might be ways to define the character set that would better resolve this.
		$Hint = str_replace('Ã','a',$One['habitat']);
		$Hint = str_replace('a§','c',$Hint);
		$Hint = str_replace('a£','a',$Hint);
		$Hint = str_replace('a¡','A',$Hint);
		$Hint = str_replace('©','c',$Hint);
		$Hint = str_replace('a³','a',$Hint);
		$Hint = str_replace('aº','a',$Hint);
		}
	}

//Determine median coordinates
sort($LonArray);
sort($LatArray);
$Lon = $LonArray[count($LonArray)/2];
$Lat = $LatArray[count($LatArray)/2];

//Randomize, then sort the array of species in descending order of quantity found.
//Randomize first to break up the alphabetical order.
uasort($Plants,function ($Plant1,$Plant2){
	//Pseudo-randomizes the list of plants while preserving the keys (SciName)
	return rand(-1,1);
});
arsort($Plants);

//Take the 20 with the highest count, or if fewer take as many as there are
$Plants = array_slice($Plants,0,20);

//Break the Plants array into Names and Count
$Names = array_keys($Plants);
$Count = array_values($Plants);
$Max = count($Names);

$Content = $Lon.",".$Lat."\r\n";
$Content .= implode(",",$Names)."\r\n";
$Content .= implode(",",$Count)."\r\n";

//************************************************************************
//  Find image urls
//************************************************************************
$Images = array();

for($i=0;$i<$Max;$i++)
	{
	//First look for a sortsequence = 1 image.
	$Images[$i] = $whereManager->GetImageURL($Names[$i],0,true);
	if($Images[$i] == "")
		$Images[$i] = $whereManager->GetImageURL($Names[$i],1,true);
	if($Images[$i] == "")
		{ //Didn't find a 1, now look for another, in diminishing order of likelihood.
		$Images[$i] = $whereManager->GetImageURL($Names[$i],0,false);
		if($Images[$i] == "")
			$Images[$i] = $whereManager->GetImageURL($Names[$i],1,false);
		}
	if($Images[$i] == "")
		$Images[$i] = "images/None.jpg";
	}
$Content .= implode(",",$Images)."\r\n";


$Content .= $Hint."\r\n";
echo $Content;
?>