<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyEditorManager.php');
header("Cache-control: private; Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/tools/editor.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$DEFAULT_LANG;
$charValue = array_key_exists("char",$_REQUEST)?$_REQUEST["char"]:"";
$childrenStr = array_key_exists("children",$_REQUEST)?$_REQUEST["children"]:"";
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";

$editorManager = new KeyEditorManager();

if(!$tid && $childrenStr){
	$childrenArr = explode(',',$childrenStr);
	$tid = array_pop($childrenArr);
	$childrenStr = implode(',',$childrenArr);
}
$editorManager->setLanguage($langValue);
$editorManager->setTid($tid);

$isEditor = false;
if($IS_ADMIN || array_key_exists("KeyEditor",$USER_RIGHTS) || array_key_exists("KeyAdmin",$USER_RIGHTS)){
	$isEditor = true;
}

if($isEditor && $action){
	if($action=="Submit Changes"){
		$addArr = array_key_exists('add',$_POST)?$_POST['add']:null;
		$removeArr = array_key_exists('remove',$_POST)?$_POST['remove']:null;
		$editorManager->processTaxa($addArr,$removeArr);
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Identification Character Editor</title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript" src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js"></script>
	<script type="text/javascript" src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js"></script>
	<script type="text/javascript">
		var dataChanged = false;
		var headingDivOpen = true;
		window.onbeforeunload = verifyClose;

		function verifyClose() {
			if (dataChanged == true) {
				return "You will lose any unsaved data if you don't first submit your changes!";
			}
		}

		function toggle(target){
			$("#"+target).toggle();
			$("#plus-"+target).toggle();
			$("#minus-"+target).toggle();
		}

		function toggleAll(){
			if(headingDivOpen){
				$(".headingDiv").hide();
				headingDivOpen = false;
			}
			else{
				$(".headingDiv").show();
				headingDivOpen = true;
			}
		}

		function showSearch(){
			document.getElementById("searchDiv").style.display="block";
			document.getElementById("searchDisplay").style.display="none";
		}

		function openPopup(urlStr,windowName){
			var wWidth = 900;
			if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
			if(wWidth > 1200) wWidth = 1200;
			newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=500,left=50,top=150');
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
</head>
<body>
<div role="main" id="innertext">
	<h1 class="page-heading">Identification Character Editor</h1>
	<?php
	if($isEditor && $tid){
		?>
		<form action="editor.php" method="post" onsubmit="dataChanged=false;">
			<?php
			$sn = $editorManager->getTaxonName();
			if($editorManager->getRankId() > 140) $sn = "<i>$sn</i>";
			echo "<div style='float:right;'>";
			if($editorManager->getRankId() > 140){
				echo "<a href='editor.php?tid=" . htmlspecialchars($editorManager->getParentTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&children=" . htmlspecialchars(($childrenStr?$childrenStr.',':''), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>edit parent</a>&nbsp;&nbsp;";
			}
			if($childrenStr){
				echo "<br><a href='editor.php?children=" . htmlspecialchars($childrenStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>back to child</a>";
			}
			echo '</div>';
			echo '<h2>'.$sn.'</h2>';
			$cList = $editorManager->getCharList();
			$depArr = $editorManager->getCharDepArray();
			$charStatesList = $editorManager->getCharStates();
			if($cList){
				echo '<div><a href="#" onclick="toggleAll();return false;">open/close all</a></div>';
				$count = 0;
				foreach($cList as $heading => $charArray){
					$headingID = str_replace(array(' ','&'),'_',$heading);
					if(!$charValue){
						echo '<fieldset>';
						echo '<legend style="font-weight:bold;font-size:120%;color:#990000;">';
						echo '<span id="minus-'.$headingID.'" onclick="toggle(\''.$headingID.'\')" style="display:none;"><img src="../../images/minus.png" style="width:1em;" ></span> ';
						echo '<span id="plus-'.$headingID.'" onclick="toggle(\''.$headingID.'\')"><img src="../../images/plus.png" style="width:1em;" ></span> ';
						echo $heading.'</legend>';
					}
					echo '<div class="headingDiv" id="'.$headingID.'" style="text-indent:1em;">';
					foreach($charArray as $cidKey => $charNameStr){
						if(isset($charStatesList[$cidKey]) && (!$charValue || $charValue == $cidKey)){
							echo "<div id='chardiv".$cidKey."' style='display:".(array_key_exists($cidKey,$depArr)?"hidden":"block").";'>";
							echo "<div style='margin-top:1em;'><span style='font-weight:bold;'>$charNameStr</span>\n";
							if($editorManager->getRankId() > 140){
								echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='font-size:smaller;'>";
								echo "<a href=\"#\" onclick=\"openPopup('editor.php?tid=" . htmlspecialchars($editorManager->getParentTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&char=" . htmlspecialchars($cidKey, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "','technical');\">parent</a>";
								echo "</span>\n";
							}
							echo "</div>\n";
							echo "<div style='font-size:smaller; text-indent:2.5em;'>Add&nbsp;&nbsp;Remove</div>\n";
							$cStates = $charStatesList[$cidKey];
							foreach($cStates as $csKey => $csValue){
								$testStr = $cidKey."_".$csKey;
								$charPresent = $editorManager->isSelected($testStr);
								$inh = $editorManager->getInheritedStr($testStr);
								$displayStr = ($charPresent?"<span style='font-weight:bold;'>":"").$csValue.$inh.($charPresent?"</span>":"");
								echo "<div style='text-indent:2em;'><input type='checkbox' name='add[]' ".($charPresent && !$inh?"disabled='true' ":" ")." value='".$testStr."' onChange='dataChanged=true;'/>";
								echo "&nbsp;&nbsp;&nbsp;<input type='checkbox' name='remove[]' ".(!$charPresent || $inh?"disabled='true' ":" ")."value='".$testStr."'  onChange='dataChanged=true;'/>";
								echo "&nbsp;&nbsp;&nbsp;$displayStr</div>\n";
							}
							echo '</div>';
							$count++;
							if($count%3 == 0) echo "<div style='margin-top:1em;'><input type='submit' name='action' value='Submit Changes'/></div>\n";
						}
					}
					if(!$charValue) echo '</fieldset>';
				}
				echo '<div style="margin-top:1em;"><input type="submit" name="action" value="Submit Changes"/></div>';
				//Hidden values to maintain values and display mode
				if($charValue){
					echo "<div><br><b>Note:</b> changes made here will not be reflected on child page until page is refreshed.</div>";
					echo "<div><input type='hidden' name='char' value='".$charValue."'/></div>";
				}
				?>
				<div>
					<input type="hidden" name="tid" value="<?php echo $editorManager->getTid(); ?>" />
					<input type="hidden" name="children" value="<?php echo $childrenStr; ?>" />
					<input type="hidden" name="lang" value="<?php echo $langValue; ?>" />
				</div>
				<?php
			}
			?>
		</form>
		<?php
	}
	else{
		echo "<h1>You do not have authority to edit character data or there is a problem with the database connection.</h1>";
	}
	?>
</div>
</body>
</html>