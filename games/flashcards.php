<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GamesManager.php');
header('Content-Type: text/html; charset='.$CHARSET);

$clid = array_key_exists('clid', $_REQUEST) ? $_REQUEST['clid'] : 0;
$dynClid = array_key_exists('dynclid', $_REQUEST) ? $_REQUEST['dynclid'] : 0;
$taxonFilter = array_key_exists('taxonfilter', $_REQUEST) ? htmlspecialchars($_REQUEST['taxonfilter'], HTML_SPECIAL_CHARS_FLAGS) : '';
$showCommon = array_key_exists('showcommon', $_REQUEST) ? $_REQUEST['showcommon'] : 0;
$lang = array_key_exists('lang', $_REQUEST) ? htmlspecialchars($_REQUEST['lang'], HTML_SPECIAL_CHARS_FLAGS) : $defaultLang;

//Sanitation
if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($dynClid)) $dynClid = 0;
if(!is_numeric($showCommon)) $showCommon = 0;

$fcManager = new GamesManager();
$fcManager->setClid($clid);
$fcManager->setDynClid($dynClid);
$fcManager->setTaxonFilter($taxonFilter);
$fcManager->setShowCommon($showCommon);
$fcManager->setLang($lang);

$sciArr = array();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Flash Cards</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script type="text/javascript">
		var imageArr = new Array();
		var sciNameArr = new Array();
		var toBeIdentified = new Array();
		var activeIndex = 0;
		var activeImageArr = new Array();
		var activeImageIndex = 0;
		var totalCorrect = 0;
		var totalTried = 0;
		var firstTry = true;

		function init(){
			<?php
				$imagesArr = $fcManager->getFlashcardImages();
				if($imagesArr){
					foreach($imagesArr as $imgArr){
						if(array_key_exists('url',$imgArr)){
							$scinameStr = $imgArr['sciname'];
							if($showCommon && array_key_exists('vern',$imgArr)){
								$scinameStr .= ' ('.$imgArr['vern'].')';
							}
							$sciArr[$imgArr['tid']] = $scinameStr;
							echo 'sciNameArr.push('.$imgArr['tid'].');'."\n";
							echo 'imageArr['.$imgArr['tid'].'] = new Array("'.implode('","',$imgArr['url']).'");'."\n";
						}
					}
				}
			?>
			reset();
		}

		function reset(){
			toBeIdentified = new Array();
			if(sciNameArr.length == 0){
				alert("Sorry, there are no images for the species list you have defined");
			}
			else{
				toBeIdentified = sciNameArr.slice();
				document.getElementById("numtotal").innerHTML = sciNameArr.length;
				document.getElementById("numcomplete").innerHTML = 0;
				document.getElementById("numcorrect").innerHTML = 0;
				insertNewImage();
			}
		}

		function insertNewImage(){
			activeIndex = toBeIdentified.shift();
			activeImageArr = imageArr[activeIndex];
			document.getElementById("activeimage").src = activeImageArr[0];
			document.getElementById("imageanchor").href = activeImageArr[0];
			activeImageIndex = 0;
			document.getElementById("imageindex").innerHTML = 1;
			document.getElementById("imagecount").innerHTML = activeImageArr.length;
		}

		function nextImage(){
			activeImageIndex++;
			if(activeImageIndex >= activeImageArr.length){
				activeImageIndex = 0;
			}
			document.getElementById("activeimage").src = activeImageArr[activeImageIndex];
			document.getElementById("imageanchor").href = activeImageArr[activeImageIndex];
			document.getElementById("imageindex").innerHTML = activeImageIndex + 1;
			document.getElementById("imagecount").innerHTML = activeImageArr.length;
			document.getElementById("scinameselect").options[0].selected = "1";
		}

		function checkId(idSelect){
			var idIndexSelected = idSelect.value;
			if(idIndexSelected > 0){
				totalTried++;
				if(idIndexSelected == activeIndex){
					alert("Correct! Try another");
					document.getElementById("numcomplete").innerHTML = sciNameArr.length - toBeIdentified.length;
					if(firstTry){
						totalCorrect++;
						document.getElementById("numcorrect").innerHTML = totalCorrect;
					}
					firstTry = true;
					if(toBeIdentified.length > 0){
						insertNewImage();
						document.getElementById("scinameselect").value = "-1";
					}
					else{
						alert("Nothing left to identify. Hit reset to start again.");
					}
				}
				else{
					alert("Sorry, incorrect. Try Again.");
					firstTry = false;
				}
			}
		}

		function tellMe(){
			var wWidth = 900;
			if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
			if(wWidth > 1200) wWidth = 1200;
			newWindow = window.open("../taxa/index.php?taxon="+activeIndex,"activetaxon",'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			firstTry = false;
		}
	</script>
</head>

<body onload="init();">
<?php
	$displayLeftMenu = (isset($checklists_flashcardsMenu)?$checklists_flashcardsMenu:'true');
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="../index.php">Home</a> &gt;&gt; ';
	if(isset($checklists_flashcardsCrumbs) && $checklists_flashcardsCrumbs){
		echo $checklists_flashcardsCrumbs;
	}
	else{
		echo '<a href="../checklists/checklist.php?clid='.$clid.'&dynclid='.$dynClid.'">';
		echo $fcManager->getClName();
		echo '</a> &gt;&gt; ';
	}
	echo ' <b>Flashcard Game</b>';
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<div style="width:420px;margin-left:auto;margin-right:auto;">
			<div style="width:420px;height:420px;text-align:center;">
				<div>
					<a id="imageanchor" href="" target="_blank">
						<img id="activeimage" src="" style="height:97%;max-width:450px" />
					</a>
				</div>
			</div>
			<div style="width:450px;text-align:center;">
				<div style="width:100%;">
					<div style="float:left;cursor:pointer;text-align:center;" onclick="insertNewImage()">
						<img src="../images/skipthisone.png" title="Skip to Next Species" />
					</div>
					<div id="rightarrow" style="float:right;cursor:pointer;text-align:center;" onclick="nextImage()">
						<img src="../images/rightarrow.png" title="Show Next Image" />
					</div>
					<div style="width:200px;margin-left:auto;margin-right:auto;">
						Image <span id="imageindex">1</span> of <span id="imagecount">?</span>
					</div>
				</div>
				<div style="clear:both;margin-top:10px;">
					<select id="scinameselect" onchange="checkId(this)">
						<option value="0">Name of Above Organism</option>
						<option value="0">-------------------------</option>
						<?php
						asort($sciArr);
						foreach($sciArr as $t => $s){
							echo "<option value='".$t."'>".$s."</option>";
						}

						?>
					</select>
				</div>
				<div style="clear:both;margin-top:10px;">
					<div>
						<b><span id="numcomplete">0</span></b> out of <b><span id="numtotal">0</span></b> Species Identified
					</div>
					<div>
						<b><span id="numcorrect">0</span></b> Identified Correctly on First Try
					</div>
				</div>
				<div style="cursor:pointer;margin-top:10px;color:green;" onclick="tellMe()"><b>Tell Me What It Is!</b></div>
				<div style="margin-left:auto;margin-right:auto;margin-top:10px;width:300px;">
					<form id="taxonfilterform" name="taxonfilterform" action="flashcards.php" method="post">
						<fieldset>
							<legend>Options</legend>
							<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							<input type="hidden" name="lang" value="<?php echo $lang; ?>" />
							<div>
								<select name="taxonfilter" onchange="document.getElementById('taxonfilterform').submit();">
									<option value="0">Filter Quiz by Taxonomic Group</option>
									<?php
										$fcManager->echoFlashcardTaxonFilterList();
									?>
								</select>
							</div>
							<div style='margin-top:3px;'>
								<?php
									//Display Common Names: 0 = false, 1 = true
									if($displayCommonNames){
										echo '<input id="showcommon" name="showcommon" type="checkbox" value="1" '.($showCommon?"checked":"").' onchange="document.getElementById(\'taxonfilterform\').submit();"/> Display Common Names'."\n";
									}
								?>
							</div>
						</fieldset>
					</form>
				</div>
				<div style="cursor:pointer;color:red;" onclick="reset()"><b>Reset Game</b></div>
			</div>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
