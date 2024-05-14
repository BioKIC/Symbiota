<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GamesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$oodID = array_key_exists('oodid', $_REQUEST) ? filter_var($_REQUEST['oodid'], FILTER_SANITIZE_NUMBER_INT) : 1;
$clidStr = array_key_exists('cl', $_REQUEST) ? $_REQUEST['cl'] : 0;
$ootdTitle = array_key_exists("title",$_REQUEST)?$_REQUEST["title"]:'Organism of the Day';
$ootdType = array_key_exists("type",$_REQUEST)?$_REQUEST["type"]:'organism';
$familyAnswer = array_key_exists('family_answer',$_POST)&&$_POST['family_answer']!='Family'?trim(strtolower($_POST['family_answer'])):'';
$scinameAnswer = array_key_exists('sciname_answer',$_POST)&&$_POST['sciname_answer']!='Genus species'?trim(strtolower($_POST['sciname_answer'])):'';
$submitAction = array_key_exists("submitaction",$_POST)?$_POST["submitaction"]:'';

//Sanitation
if(!preg_match('/^[\d,]+$/', $clidStr)) $clidStr = 0;
$ootdTitle = htmlspecialchars($ootdTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$ootdType = htmlspecialchars($ootdType, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$familyAnswer = htmlspecialchars($familyAnswer, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$scinameAnswer = htmlspecialchars($scinameAnswer, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

$gameManager = new GamesManager();
$gameInfo = $gameManager->setOOTD($oodID,$clidStr);
$imageArr = $gameInfo['images'];
$synArr = $gameManager->getSynonymArr($gameInfo['tid']);
$cacheRefresh = date('YdmH');
foreach($imageArr as $k => $imgValue){
	$imageArr[$k] = $imgValue.'?ver='.$cacheRefresh;
}
$genusAnswer = strtok($scinameAnswer, " ");
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $ootdTitle; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";
		var giveUp = false;
		var imgIndex = 0;

		$(document).ready(function() {
			var dialogArr = new Array("game");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "center top", at: "right bottom", of: "#"+dialogStr }
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

			initiateTaxonSuggest("family_answer", 140, 0);
			initiateTaxonSuggest("sciname_answer", 180, 221);
		});

		function verifyAnswerForm(f){
			if(giveUp) return true;
			if((f.sciname_answer.value == "" || f.sciname_answer.value == "Genus species") && (f.family_answer.value == "" || f.family_answer.value == "Family")){
				alert("Please enter a guess within the Scientific Name or Family fields!");
				return false;
			}
			return true;
		}

		function toggleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
			}
		}

		function openTaxonProfile(){
			giveUp = true;
			window.open('../../taxa/index.php?tid=<?php echo $gameInfo['tid']; ?>','plantwindow','width=1200,height=650');
		}

		function chgImg(direction){
			var imgArr = <?php echo json_encode($imageArr); ?>;
			var imgLength = imgArr.length - 1;
			if(document.images){
				imgIndex = imgIndex + direction;
				if(imgIndex > imgLength) imgIndex = 0;
				else if(imgIndex < 0) imgNum = imgLength;
				document.getElementById('slideshow').src = imgArr[imgIndex];
			}
		}
	</script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js" type="text/javascript"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($indexMenu)?$indexMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div role="main" id="innertext" style="">
		<!-- This is inner text! -->
		<div style="width:80%;margin-left:auto;margin-right:auto;">
			<div style="text-align:center;margin-bottom:20px;">
				<h1 class="page-heading"><?php echo $ootdTitle; ?></h1>
			</div>
			<?php
			if(!$submitAction){
				?>
				<div style="z-index:1;width:500px;margin-left:auto;margin-right:auto;" >
					<div class = "dailypicture" align = "center">
						<div>
							<div style="vertical-align:middle;">
								<a href="#" onclick="chgImg(1); return false;"><img src="<?php echo $imageArr[0]; ?>" name="slideshow" id="slideshow" style="width:500px;" ></a><br />
							</div><br />
							<a href="#" onclick="chgImg(-1); return false;">Previous</a> &nbsp;|&nbsp;
							<a href="#" onclick="chgImg(1); return false;">Next</a>
						</div>
					</div>
					<div style="margin-left:auto;margin-right:auto;font-size:18px;text-align:center;margin-top:20px;margin-bottom:20px;" >
						<b>Name that <?php echo $ootdType; ?>!</b>
						<a id="gameinfo" href="#" onclick="return false" title="How to Play?">
							<img src="../../images/qmark.png" style="width:2em;"/>
						</a>
						<div id="gameinfodialog" title="How to Play">
							Look at the picture, and see if you can figure out what the <?php echo $ootdType; ?> is. If you get completely stumped, you can
							click the "I give up" button. A new <?php echo $ootdType; ?> is updated daily, so make sure you check back every day to test your knowledge!
						</div>
					</div>
					<div>
						<form name="answers" id="answers" method="post" action="index.php" onsubmit="return verifyAnswerForm(this)">
							<div style="width:500px;margin-left:auto;margin-right:auto;" >
								<div style="float:left;" >
									<div>
										<b>Family:</b> <input type="text" id="family_answer" name="family_answer" style="width:200px;color:#888;font-weight:bold;" value="Family" onfocus="if(this.value=='Family') {this.value='', this.style.color='black', this.style.fontWeight='normal'}" onblur="if(this.value=='') {this.value='Family', this.style.color='#888', this.style.fontWeight='bold'}" />
									</div>
									<div style="margin-top:20px;" >
										<b>Scientific name:</b> <input type="text" id="sciname_answer" style="width:200px;color:#888;font-weight:bold;" name="sciname_answer" value="Genus species" onfocus="if(this.value=='Genus species') {this.value='', this.style.color='black', this.style.fontWeight='normal'}" onblur="if(this.value=='') {this.value='Genus species', this.style.color='#888', this.style.fontWeight='bold'}" />
									</div>
								</div>
								<div style="float:right;">
									<div>
										<button name="submitaction" type="submit" value="giveup" onClick="openTaxonProfile()" >I give up!</button>
									</div>
									<div style="margin-top:10px;">
										<button name="submitaction" type="submit" value="Submit" >Submit Answer</button>
									</div>
								</div>
								<div style="clear:both">
									<input name="oodid" type="hidden" value="<?php echo $oodID; ?>" />
									<input name="cl" type="hidden" value="<?php echo $clidStr; ?>" />
									<input name="title" type="hidden" value="<?php echo $ootdTitle; ?>" />
									<input name="type" type="hidden" value="<?php echo $ootdType; ?>" />
								</div>
							</div>
						</form>
					</div>
				</div>
				<?php
			}
			else{
				if($submitAction == 'giveup'){
					?>
					<div id="giveup">
						<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
							<div style="margin-top:25px;font-size:18px;" >
								It was <br /><br />
								<b><?php echo $gameInfo['family']; ?></b><br />
								<i><?php echo $gameInfo['sciname']; ?></i>
							</div>
							<div style="margin-top:40px;font-size:16px;" >
								<a href = "#" onClick="openTaxonProfile()" >-Click here to learn more about this <?php echo $ootdType; ?>-</a>
								<div>Thank you for playing!</div>
								<div>Check back tomorrow for a new <?php echo $ootdType; ?>!</div>
							</div>
						</div>
					</div>
					<?php
				}
				else{
					if($scinameAnswer == strtolower($gameInfo['sciname']) || in_array($scinameAnswer, $synArr)){
						?>
						<div id="correct" style="text-align:center;clear:both;">
							<div style="width:700px;margin-top:20px;margin-left:auto;margin-right:auto;display:table;">
								<div style="display:table-row;" >
									<div style="width:160px;float:left;display:table-cell;" >
										<img src = "../../images/games/ootd/balloons-150.png">
									</div>
									<div style="width:350px;font-size:15px;float:left;margin-top:50px;display:table-cell;" >
										<b>Congratulations! That is correct!</b>
										<?php
										if($scinameAnswer != strtolower($gameInfo['sciname']))
											echo '<div style="margin-top:10px"><i>'.ucfirst($scinameAnswer).'</i> is a synonym of <i>'.$gameInfo['sciname'].'</i></div>';
										if($familyAnswer && $familyAnswer != strtolower($gameInfo['family']))
											echo '<div style="margin-top:10px">However, '.ucfirst($familyAnswer).' is not the correct family</div>';
										?>
									</div>
									<div style="width:160px;float:right;display:table-cell;" >
										<img src = "../../images/games/ootd/balloons-150.png">
									</div>
								</div>
							</div>
							<div style="margin-left:auto;margin-right:auto;" >
								<div style="font-size:18px;" ><b><?php echo $gameInfo['family']; ?></b><br />
									<i><?php echo $gameInfo['sciname']; ?></i>
								</div>
								<div style="margin-top:30px;font-size:16px;" >
									<a href = "#" onClick="openTaxonProfile()" >Click here to learn more about this <?php echo $ootdType; ?></a>
								</div>
							</div>
						</div>
						<?php
					}
					else{
						?>
						<div id="incorrect_sciname">
							<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
								<div style="font-size:15px;" >
									<b>Sorry, <i><?php echo ucfirst($scinameAnswer); ?></i> is not correct</b>
								</div>
								<div style="font-size:15px;margin-top:10px" >
									<?php
									$howeverStr = '';
									if($familyAnswer == strtolower($gameInfo['family'])) $howeverStr = 'family ('.$gameInfo['family'].') ';
									if($genusAnswer == strtolower($gameInfo['genus'])) $howeverStr .= ($howeverStr?'and ':'').'genus ('.$gameInfo['genus'].') ';
									if($howeverStr) echo 'However, you did get the '.$howeverStr.'correct!'
									?>
								</div>
								<div style="margin-top:40px;font-size:16px;" >
									<div>
										<a href = "index.php?oodid=<?php echo htmlspecialchars($oodID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&cl=' . htmlspecialchars($clidStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&title=' . htmlspecialchars($ootdTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&type=' . htmlspecialchars($ootdType, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">Click here to try again!</a>
									</div>
									<div>-- OR --</div>
									<div>
										<a href = "index.php?submitaction=giveup?oodid=<?php echo htmlspecialchars($oodID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'&cl=' . htmlspecialchars($clidStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&title=' . htmlspecialchars($ootdTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&type=' . htmlspecialchars($ootdType, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" onClick="openTaxonProfile()" >-Click here reveal what the <?php echo htmlspecialchars($ootdType, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> was-</a>
									</div>
								</div>
							</div>
						</div>
						<?php
					}
				}
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>