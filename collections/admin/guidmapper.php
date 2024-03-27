<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/admin/guidmapper.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/admin/guidmapper.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/admin/guidmapper.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/guidmapper.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:'';

$isEditor = 0;
if($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])){
	$isEditor = 1;
}

$uuidManager = new UuidFactory();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?= $LANG['UID_MAP'] ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
			  	var divs = document.getElementsByTagName("div");
			  	for (var h = 0; h < divs.length; h++) {
			  	var divObj = divs[h];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
			return false;
		}

		function verifyGuidForm(f){

			return true;
    	}

		function verifyGuidAdminForm(f){

			return true;
    	}
    </script>
</head>
<body>
<?php
$displayLeftMenu = (isset($admin_guidmapperMenu)?$admin_guidmapperMenu:"true");
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collId, HTML_SPECIAL_CHARS_FLAGS); ?>&emode=1"><?php echo htmlspecialchars($LANG['COL_MGMNT'], HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
	<b><?= $LANG['UID_MAP']; ?></b>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($isEditor){
		?>
		<h3><?= $LANG['GUID_CP'] ?></h3>
		<div style="margin:10px;">

		</div>
		<?php
		if($action == 'Populate Collection GUIDs'){
			echo '<ul>';
			$uuidManager->populateGuids($collId);
			echo '</ul>';
		}
		elseif($action == 'Populate GUIDs'){
			echo '<ul>';
			$uuidManager->populateGuids();
			echo '</ul>';
		}

		//$collCnt = $uuidManager->getCollectionCount();
		$occCnt = $uuidManager->getOccurrenceCount($collId);
		$detCnt = $uuidManager->getDeterminationCount($collId);
		$imgCnt = $uuidManager->getImageCount($collId);
		?>
		<?php if($collId) echo '<h3>'.$uuidManager->getCollectionName($collId).'</h3>'; ?>
		<div style="font-weight:bold;"><?= $LANG['REC_WO_GUIDS']; ?></div>
		<div style="margin:10px;">
			<div><?php echo '<b>' . $LANG['OCCS'] . ': </b>' . $occCnt; ?></div>
			<div><?php echo '<b>' . $LANG['DETS'] . ': </b>' . $detCnt; ?></div>
			<div><?php echo '<b>' . $LANG['IMGS'] . ': </b>' . $imgCnt; ?></div>
		</div>
		<?php
		if($collId){
			?>
			<form name="guidform" action="guidmapper.php" method="post" onsubmit="return verifyGuidForm(this)">
				<fieldset style="padding:15px;">
					<legend><b><?php echo $LANG['UID_MAP']; ?></b></legend>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="formsubmit" value="<?php echo $LANG['POP_COLL_GUID']; ?>" />
					</div>
				</fieldset>
			</form>
			<?php
		}
		elseif($IS_ADMIN){
			?>
			<div id="guidadmindiv">
				<form name="dwcaguidform" action="guidmapper.php" method="post" onsubmit="return verifyGuidAdminForm(this)">
					<fieldset style="padding:15px;">
						<legend><b><?php echo $LANG['UID_MAP']; ?></b></legend>
						<div style="clear:both;margin:10px;">
							<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
							<input type="submit" name="formsubmit" value="<?php echo $LANG['POP_GUID']; ?>" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php
		}
	}
	else{
		echo '<h2>' . $LANG['NOT_AUTH'] . '</h2>';
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>