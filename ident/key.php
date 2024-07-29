<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyDataManager.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/ident/key.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/ident/key.en.php');
else include_once($SERVER_ROOT.'/content/lang/ident/key.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$isEditor = false;
if($IS_ADMIN || array_key_exists('KeyEditor',$USER_RIGHTS)){
	$isEditor = true;
}

$attrsValues = Array();

$clValue = array_key_exists('cl',$_REQUEST)?$_REQUEST['cl']:'';
if(!$clValue && array_key_exists('clid',$_REQUEST)) $clValue = $_REQUEST['clid'];
$dynClid = array_key_exists('dynclid', $_REQUEST) ? filter_var($_REQUEST['dynclid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxonValue = array_key_exists('taxon',$_REQUEST)?$_REQUEST['taxon']:'';
$rv = array_key_exists('rv',$_REQUEST)?$_REQUEST['rv']:'';
$pid = array_key_exists('pid', $_REQUEST) ? FILTER_VAR($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';
$langValue = array_key_exists('lang',$_REQUEST)?$_REQUEST['lang']:'';
$sortBy = array_key_exists('sortby', $_REQUEST) ? FILTER_VAR($_REQUEST['sortby'], FILTER_SANITIZE_NUMBER_INT) : 0;
$displayCommon = array_key_exists('displaycommon', $_REQUEST) ? FILTER_VAR($_REQUEST['displaycommon'], FILTER_SANITIZE_NUMBER_INT) : 0;
$displayImages = array_key_exists('displayimages', $_REQUEST) ? FILTER_VAR($_REQUEST['displayimages'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitbutton',$_REQUEST)?$_REQUEST['submitbutton']:'';
if(!$action && array_key_exists('attr',$_REQUEST) && is_array($_REQUEST['attr'])){
	$attrsValues = $_REQUEST['attr'];	//Array of: cid + '-' + cs (ie: 2-3)
}

//Variable check
if(!is_numeric($rv)) $rv = '';
$langValue = 'English';

$dataManager = new KeyDataManager();

//if(!$langValue) $langValue = $DEFAULT_LANG;
if($sortBy) $dataManager->setSortBy($sortBy);
if($displayCommon) $dataManager->setDisplayCommon(1);
if($displayImages) $dataManager->setDisplayImages(true);
$dataManager->setLanguage($langValue);
if($pid) $dataManager->setProject($pid);
if($dynClid) $dataManager->setDynClid($dynClid);
$clid = $dataManager->setClValue($clValue);
if($taxonValue) $dataManager->setTaxonFilter($taxonValue);
if($attrsValues) $dataManager->setAttrs($attrsValues);
if($rv) $dataManager->setRelevanceValue($rv);

$taxa = $dataManager->getTaxaArr();
$chars = $dataManager->getCharArr();

//Harevest and remove language list from $chars
$languages = Array();
if($chars){
	$languages = $chars['Languages'];
	unset($chars['Languages']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo htmlspecialchars($DEFAULT_TITLE . ' ' . $LANG['WEBKEY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' '. htmlspecialchars(preg_replace('/\<[^\>]+\>/','',$dataManager->getClName()), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<link href="../css/alerts.css" type="text/css" rel="stylesheet" />
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../js/symb/ident.key.js" type="text/javascript"></script>
	<script type="text/javascript">
		$( function() {
			$( "#key-chars" ).resizable();
		} );

		function setLang(list){
			var langName = list.options[list.selectedIndex].value;
			var objs = document.getElementsByTagName("span");
			for (i = 0; i < objs.length; i++) {
				var obj = objs[i];
				if(obj.lang == langName) obj.style.display="";
				else if(obj.lang != "") obj.style.display="none";
			}
		}

		function resetForm(f) {
			var inputs = f.getElementsByTagName('input');
			for (var i = 0; i<inputs.length; i++) {
				switch (inputs[i].type) {
					case 'text':
						inputs[i].value = '';
						break;
					case 'radio':
					case 'checkbox':
						inputs[i].checked = false;
				}
			}

			var selects = f.getElementsByTagName('select');
			for (var i = 0; i<selects.length; i++)
				selects[i].selectedIndex = 0;
			f.submit();
			return false;
		}

		function openEditorPopup(tid){
			var url = 'tools/editor.php?tid='+tid;
			window.open(url,'keyeditor','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=1100,height=600,left=20,top=20');
		}

		function openGlossaryPopup(glossid){
			var urlStr = "../glossary/individual.php?glossid="+glossid;
			glossWindow = window.open(urlStr,'glosspopup','toolbar=0,status=1,scrollbars=1,width=900,height=450,left=20,top=20');
			if(glossWindow.opener == null) glossWindow.opener = self;
			return false;
		}
	</script>
	<style type="text/css">
		#title-div { font-weight: bold; font-size: 120% }
		#char-div {  }
		#key-chars { display: inline-block; float: right; max-width: 35%; overflow: hidden; }
		.infoAnchor img{ width: 12px; border: 0px; }
		fieldset { padding: 5px 10px; }
		legend { font-weight:bold }
		.editimg { width: 13px }
		#key-div {  }
		.char-heading { font-weight: bold; margin-top:1em; font-size:125%; }
		#key-taxa { vertical-align: top; }
		.charHeading {}
		.headingname { font-weight: bold; margin-top: 1em; font-size: 125%; }
		.cs-div { display: flex; }
		.cs-div input { margin-right: 5px }
		.characterStateName {}
		.dynam {}
		.dynamlang{ margin-top: 0.5em; font-weight: bold; }
		.dynamopt{}
		.editimg{ margin-left:10px; }
		.family-div{ font-weight: bold; margin-top: 10px; font-size: 1.3em; }
		.vern-span{  }
		<?php
		if($displayImages){
			?>
			.taxon-div{ display: inline-block; flex-flow: row wrap; }
			.img-div{ display: inline-block; position: relative; margin: 3px; width: 160px; height: 160px; border: 1px solid gray; overflow: hidden; }
			.img-div img{ position: absolute; max-height: 165px; max-width: 165px; top: -9999px; bottom: -9999px; left: -9999px; right: -9999px; margin: auto; }
			.img-div div{ text-align: center; margin-top: 25%; }
			.sciname-div{ text-align: center }
			<?php
		}
		else{
			?>
			.taxon-div{ flex-flow: row wrap; }
			.img-div{}
			.sciname-div{ margin-left: 10px; }
			<?php
		}
		?>
	</style>
</head>
<body>
<?php
$displayLeftMenu = (isset($ident_keyMenu)?$ident_keyMenu:true);
include($SERVER_ROOT.'/includes/header.php');
echo '<div class="navpath">';
echo '<a href="../index.php">' . $LANG['HOME'] . '</a> &gt;&gt; ';
if($dynClid){
	if($dataManager->getClType() == 'Specimen Checklist'){
		echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/list.php?tabindex=0">';
		echo $LANG['OCC_CHECKLIST'];
		echo '</a> &gt;&gt; ';
	}
}
elseif($clid){
	echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/checklists/checklist.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
	echo $LANG['CHECKLIST'] . ': ' . htmlspecialchars($dataManager->getClName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
	echo '</a> &gt;&gt; ';
}
elseif($pid){
	echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/projects/index.php?pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
	echo $LANG['PROJ_CHECKLISTS'];
	echo '</a> &gt;&gt; ';
}
echo '<a href="key-v1.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxon=All+Species" alt="' . htmlspecialchars($LANG['TRAD_KEY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($LANG['PREV_KEY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
echo '<b>' . htmlspecialchars($LANG['NEW_ID_KEY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ': ' . htmlspecialchars($dataManager->getClName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</b>';
echo '</div>';
?>
<div role="main" id="innertext">
	<h1 class="page-heading screen-reader-only">Key</h1>
	<?php
	if($clid || $dynClid){
		?>
		<div id="char-div">
			<fieldset id="key-chars">
				<legend><?php echo $LANG['FILTER_OPTIONS']; ?></legend>
				<form name="keyform" id="keyform" action="key.php" method="post">
					<div>
						<div class="float-right bottom-breathing-room-rel-sm"><button type="button" onclick="resetForm(this.form)">Reset</button></div>
						<div><span><?php echo (isset($LANG['TAXON_SEARCH'])?$LANG['TAXON_SEARCH']:'Family/Genus Filter');?>:</span></div>
						<select name="taxon" onchange="this.form.submit();">
							<?php
							echo '<option value="All Species">' . $LANG['SELECTTAX'] . '</option>';
							$selectList = $dataManager->getTaxaFilterList();
							foreach($selectList as $value){
								$selectStr = ($value==$taxonValue?'SELECTED':'');
								echo '<option ' . $selectStr . '>' . $value . '</option>';
							}
							?>
						</select>
					</div>
					<hr size="2" />
					<?php
					//echo "<div style=''>Relevance value: <input name='rv' type='text' size='3' title='Only characters with > ".($rv*100)."% relevance to the active spp. list will be displayed.' value='".$dataManager->getRelevanceValue()."'></div>";
					//List char Data with selected states checked
					if(count($languages) > 1){
						echo '<div id="langlist" style="margin:0.5em;">' . $LANG['LANGUAGES'] . ': <select name="lang" onchange="setLang(this);">';
						foreach($languages as $l){
						    echo '<option value="' . $l . '" ' . ($DEFAULT_LANG == $l?'SELECTED':'') . '>' . $l . '</option>';
						}
						echo '</select></div>';
					}
					?>
					<div style="margin:5px">
						<?php echo (isset($LANG['SORT'])?$LANG['SORT']:'Sort by') . ': '; ?>
						<select name="sortby" onchange="this.form.submit();">
							<?php
							echo '<option value="0">' . (isset($LANG['SORT_SCINAME_FAMILY'])?$LANG['SORT_SCINAME_FAMILY']:'Family/Scientific Name') . '</option>';
							echo '<option value="1" ' . ($sortBy?'SELECTED':'') . '>' . (isset($LANG['SORT_SCINAME'])?$LANG['SORT_SCINAME']:'Scientific Name') . '</option>';
							?>
						</select>
					</div>
					<?php
					if(!isset($DISPLAY_COMMON_NAMES) || $DISPLAY_COMMON_NAMES){
						?>
						<div style="margin:5px">
							<input name="displaycommon" type="checkbox" value="1" onchange="this.form.submit();" <?php if($displayCommon) echo 'checked'; ?> />
							<?php echo (isset($LANG['DISPLAY_COMMON'])?$LANG['DISPLAY_COMMON']:'Display Common Names'); ?>
						</div>
						<?php
					}
					?>
					<div style="margin:5px">
						<input name="displayimages" type="checkbox" value="1" onchange="this.form.submit();" <?php if($displayImages) echo 'checked'; ?> />
						<?php echo (isset($LANG['DISPLAY_IMAGES'])?$LANG['DISPLAY_IMAGES']:'Display images'); ?>
					</div>
					<?php
					if($chars){
						//echo "<div id='showall' class='dynamControl' style='display:none'><a href='#' onclick='toggleAll();'>Show All Characters</a></div>\n";
						//echo "<div class='dynamControl' style='display:block'><a href='#' onclick='toggleAll();'>Hide Advanced Characters</a></div>\n";
						foreach($chars as $key => $htmlStrings){
							echo $htmlStrings."\n";
						}
					}
					?>
					<div>
						<input type="hidden" id="cl" name="clid" value="<?php echo $clid; ?>" />
						<input type="hidden" id="dynclid" name="dynclid" value="<?php echo $dynClid; ?>" />
						<input type="hidden" id="pid" name="pid" value="<?php echo $pid; ?>" />
						<input type="hidden" id="rv" name="rv" value="<?php echo $dataManager->getRelevanceValue(); ?>" />
					</div>
				</form>
			</fieldset>
		</div>
		<?php
		if($clid && $isEditor){
			?>
			<div style="float:right;margin:15px;" title="<?php echo htmlspecialchars($LANG['EDIT_CHAR_MATRIX'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
				<a href="tools/matrixeditor.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><img class="editimg" src="../images/edit.png" style="width:1.2em" /><span style="font-size:70%;"><?= $LANG['EDIT_CHAR_MATRIX'] ?></span></a>
			</div>
			<?php
		}
		?>
		<div id="title-div">
			<?php
			if($FLORA_MOD_IS_ACTIVE) echo '<a href="../checklists/checklist.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&dynclid=' . htmlspecialchars($dynClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
			echo htmlspecialchars($dataManager->getClName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ';
			if($FLORA_MOD_IS_ACTIVE) echo '</a>';
			?>
		</div>
		<div id="key-taxa">
			<?php
			if(!$dynClid && $dataManager->getClAuthors()) echo '<div>' . $dataManager->getClAuthors() . '</div>';
			$count = $dataManager->getTaxaCount();
			if($count) echo '<div style="margin-bottom:15px;">' . $LANG['SPECCOUNT'] . ': ' . $count . '</div>';
			else echo '<div>' . $LANG['NOMATCH'] . '</div>';
			$clType =$dataManager->getClType();
			ksort($taxa);
			foreach($taxa as $family => $taxaArr){
				if($family) echo '<div class="family-div">' . $family . '</div>';
				//natcasesort($taxaArr);
				foreach($taxaArr as $tid => $taxonArr){
					echo '<div class="taxon-div">';
					if($displayImages){
						echo '<div class="img-div">';
						echo '<a href="../taxa/index.php?taxon=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&clid=" . htmlspecialchars(($clType=="static"?$clid:""), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">';
						if(isset($taxonArr['i'])) echo '<img src="' . $taxonArr['i'] . '" />';
						else echo '<div>' . htmlspecialchars($LANG['IMG_NOT_AVAILABLE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</div>';
						echo '</a>';
						echo '</div>';
					}
					echo '<div class="sciname-div">';
					echo '<a href="../taxa/index.php?taxon=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&clid=" . htmlspecialchars(($clType=="static"?$clid:""), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank"><i>' . htmlspecialchars($taxonArr['s'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</i></a>';
					if($displayCommon) echo ($displayImages?'<br/>':(isset($taxonArr['v'])?' - ':'')) . '<span class="vern-span">' . (isset($taxonArr['v'])?$taxonArr['v']:'&nbsp;') . '</span>';
					if($isEditor && !$displayImages){
						echo '<a href="#" onclick="openEditorPopup('.$tid.')">';
						echo '<img class="editimg" src="../images/edit.png" style="width:1.2em" title="' . $LANG['EDITMORP'] . '" />';
						echo '</a>';
					}
					echo '</div>';
					echo '</div>';
				}
			}
			?>
		</div>
		<?php
	}
	else echo '<div style="margin: 40px 20px; font-weight:bold">' . $LANG['ERROR_CLID_NULL'] . '</div>';
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
