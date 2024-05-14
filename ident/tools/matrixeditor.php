<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyMatrixEditor.php');
include_once($SERVER_ROOT . '/content/lang/ident/tools/matrixeditor.' . $LANG_TAG . '.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/tools/matrixeditor.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$clid = $_REQUEST['clid'];
$taxonFilter = array_key_exists("tf",$_REQUEST)?$_REQUEST["tf"]:'';
$generaOnly = array_key_exists("generaonly",$_POST)?$_POST["generaonly"]:0;
$cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:'';
$removeAttrs = array_key_exists("r",$_REQUEST)?$_REQUEST["r"]:"";
$addAttrs = array_key_exists("a",$_REQUEST)?$_REQUEST["a"]:"";
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";

if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($taxonFilter)) $taxonFilter = 0;
if(!is_numeric($cidValue)) $cidValue = 0;

$muManager = new KeyMatrixEditor();
$muManager->setClid($clid);
if($langValue) $muManager->setLang($langValue);
if($cidValue) $muManager->setCid($cidValue);

$isEditor = false;
if($IS_ADMIN || array_key_exists("KeyEditor",$USER_RIGHTS) || array_key_exists("KeyAdmin",$USER_RIGHTS)){
	$isEditor = true;
}

if($isEditor){
	if($removeAttrs || $addAttrs){
		$muManager->processAttributes($removeAttrs,$addAttrs);
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' '; ?> <?php echo $LANG['MTRX_EDIT']; ?> </title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script>
		var addAttrArr = [];
		var removeAttrArr = [];
		var dataChanged = false;

		window.onbeforeunload = verifyClose();

		function verifyClose() {
			if(dataChanged == true) {
				return <?php echo (isset($LANG['DATA_CHANGED']) ? $LANG['DATA_CHANGED'] : 'You will lose any unsaved data if you don\'t first save your changes!'); ?>;
			}
		}

		function attrChanged(cbElem,target){
			if(cbElem.checked == true){
				if(removeAttrArr.indexOf(target) > -1) removeAttrArr.splice(removeAttrArr.indexOf(target),1);
				else if(addAttrArr.indexOf(target) == -1) addAttrArr.push(target);
			}
			else{
				if(addAttrArr.indexOf(target) > -1) addAttrArr.splice(addAttrArr.indexOf(target),1);
				else if(removeAttrArr.indexOf(target) == -1) removeAttrArr.push(target);
			}
		}

		function submitAttrs(){
			var sform = document.submitform;
			var a;
			var r;
			var submitForm = false;

			if(addAttrArr.length > 0){
				for(a in addAttrArr){
					var addValue = addAttrArr[a];
					if(addValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","a[]");
						newInput.setAttribute("value",addValue);
						sform.appendChild(newInput);
					}
				}
				submitForm = true;
			}

			if(removeAttrArr.length > 0){
				for(r in removeAttrArr){
					var removeValue = removeAttrArr[r];
					if(removeValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","r[]");
						newInput.setAttribute("value",removeValue);
						sform.appendChild(newInput);
					}
				}
				submitForm = true;
			}
			if(submitForm) sform.submit();
			else alert(<?php echo (isset($LANG['NO_EDITS_MADE']) ? $LANG['NO_EDITS_MADE'] : 'It doesn\'t appear that any edits have been made'); ?>);
		}
	</script>
	<style>
		table {
			text-align: left;
			position: relative;
		}
		th {
			position: sticky;
			top: 0;
		}
		.gridlike-form-no-margin {
			display: flex;
			flex-direction: column;
		}
		.nativity-div {
			margin-top: 1em;
			font-size: 125%;
			font-weight: bold;
		}
	</style>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href="../../index.php"> <?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'); ?> </a> &gt;&gt;
	<a href="../../checklists/checklist.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"> <?php echo (isset($LANG['OPEN_CHKLIST']) ? $LANG['OPEN_CHKLIST'] : 'Open Checklist'); ?> </a> &gt;&gt;
	<a href="../key.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&taxon=All+Species"> <?php echo (isset($LANG['OPEN_KEY']) ? $LANG['OPEN_KEY'] : 'Open Key'); ?> </a> &gt;&gt;
	<?php
	if($cidValue){
		?>
		<a href='matrixeditor.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&tf=' . htmlspecialchars($taxonFilter, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&lang=' . htmlspecialchars($langValue, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
		<?php echo (isset($LANG['RETURN_TO_LIST']) ? $LANG['RETURN_TO_LIST'] : 'Return to Character List'); ?>
		</a> &gt;&gt;
		<?php
	}
	?>
	<b> <?php echo (isset($LANG['MTRX_EDIT']) ? $LANG['MTRX_EDIT'] : 'Matrix Editor'); ?> </b>
</div>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['MTRX_EDIT']; ?></h1>
	<?php
	if($clid && $isEditor){
		if(!$cidValue){
			?>
			<form id="filterform" action="matrixeditor.php" method="post">
				<fieldset>
					<legend> <?php echo (isset($LANG['CHAR_EDIT']) ? $LANG['CHAR_EDIT'] : 'Character Edit'); ?></legend>
					<div class="gridlike-form-no-margin">
						<label for="selection" class="bottom-breathing-room-rel"> <?php echo (isset($LANG['SELECT_CHAR']) ? $LANG['SELECT_CHAR'] : 'Select character to edit'); ?> </label>
						<select name="tf" id="selection">
							<option value=""> <?php echo (isset($LANG['ALL_TAXA']) ? $LANG['ALL_TAXA'] : 'All Taxa'); ?> </option>
							<option value="">--------------------------</option>
							<?php
							$selectList = $muManager->getTaxaQueryList();
							foreach($selectList as $tid => $scinameValue){
								echo '<option value="'.$tid.'" '.($tid==$taxonFilter?"SELECTED":"").'>'.$scinameValue."</option>";
							}
							?>
						</select>
					</div>
					<div style="margin: 10px 0px;">
						<input type="checkbox" id="generaonly" name="generaonly" value="1" <?php if($generaOnly) echo "checked"; ?> />
						<label for="generaonly">
							<?php echo (isset($LANG['EXCLUDE_RANK']) ? $LANG['EXCLUDE_RANK'] : 'Exclude Species Rank'); ?>
						</label>
					</div>
			 		<?php
	 				$cList = $muManager->getCharList($taxonFilter);
					foreach($cList as $h => $charData){
						echo "<div class='nativity-div bottom-breathing-room-rel'>$h</div>\n";
						ksort($charData);
						foreach($charData as $cidKey => $charValue){
							echo '<div> <input name="cid" type="radio" id="' . $cidKey . '" value="' . $cidKey . '" onclick="this.form.submit()">' . '<label for="' . $cidKey . '">' . $charValue . '</label></div>'."\n";
						}
					}
			 		?>
					<script src="../../js/symb/ident.tools.matrixeditor.js"></script>
					<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
					<input type="hidden" name="lang" value="<?php echo $langValue; ?>" />

					<button type="submit" class="top-breathing-room-rel" name="action" value="Submit Observation"><?php echo (isset($LANG['SUBMIT']) ? $LANG['SUBMIT'] : 'Submit') ?></button>
			 	</fieldset>
			</form>
			<?php
		}
		else{
			$inheritStr = '&nbsp;<span title="' . (isset($LANG['STATE_INHERITED']) ? $LANG['STATE_INHERITED'] : 'State has been inherited from parent taxon') . '"><b>(i)</b></span>';
			?>
			<div><?php echo $inheritStr; ?> <?php echo (isset($LANG['INHERITED_TRUE']) ? $LANG['INHERITED_TRUE'] : ' = character state is inherited as true from a parent taxon (genus, family, etc)') ?> </div>
		 	<table class="styledtable" style="font-family:Arial;font-size:12px;">
				<?php
				$muManager->echoTaxaList($taxonFilter,$generaOnly);
				?>
			</table>
			<form name="submitform" action="matrixeditor.php" method="post">
				<input type='hidden' name='tf' value='<?php echo $taxonFilter; ?>' />
				<input type='hidden' name='cid' value='<?php echo $cidValue; ?>' />
				<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
				<input type='hidden' name='lang' value='<?php echo $langValue; ?>' />
				<input type='hidden' name='generaonly' value='<?php echo $generaOnly; ?>' />
			</form>
			<?php
	 	}
	}
	else{
		echo "<h1>" . (isset($LANG['NO_PERMISSION']) ? $LANG['NO_PERMISSION'] : 'You appear not to have necessary premissions to edit character data.') . "</h1>";
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>