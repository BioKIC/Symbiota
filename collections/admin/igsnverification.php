<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSesar.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/admin/igsnverification.'.$LANG_TAG.'.php'))
	include_once($SERVER_ROOT.'/content/lang/collections/admin/igsnverification.'.$LANG_TAG.'.php');
else
	include_once($SERVER_ROOT.'/content/lang/collections/admin/igsnverification.en.php');

header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/igsnmanagement.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$namespace = array_key_exists('namespace',$_REQUEST)?$_REQUEST['namespace']:'';
$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

//Variable sanitation
if(!is_numeric($collid)) $collid = 0;
if(preg_match('/[^A-Z]+/', $namespace)) $namespace = '';

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS))){
	$isEditor = 1;
}
$guidManager = new OccurrenceSesar();
$guidManager->setCollid($collid);
$guidManager->setCollArr();
$guidManager->setNamespace($namespace);

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['IGSN_GUID_MANAGE'] ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function syncIGSN(occid, catNum, igsn){
			$.ajax({
				method: "POST",
				data: { occid: occid, catnum: catNum, igsn: igsn },
				dataType: "json",
				url: "rpc/syncigsn.php"
			})
			.done(function(jsonRes) {
				if(jsonRes.status == 1){
					$("#syncDiv-"+occid).text("<?php echo $LANG['IGSN_ADDED'] ?>");
				}
				else{
					$("#syncDiv-"+occid).css('color', 'red');
					if(jsonRes.errCode == 1) $("#syncDiv-"+occid).text("<?php echo $LANG['OCCID_EXISTS'] ?>"+jsonRes.guid);
					else if(jsonRes.errCode == 2) $("#syncDiv-"+occid).text("<?php echo $LANG['CATNUM_NOT_MATCH'] ?>"+": "+jsonRes.catNum);
					else if(jsonRes.errCode == 3) $("#syncDiv-"+occid).text("<?php echo $LANG['OCC_NOT_FOUND'] ?>"+" (#"+occid+")");
					else if(jsonRes.errCode == 8) $("#syncDiv-"+occid).text("<?php echo $LANG['NOT_AUTH_TO_MOD'] ?>");
					else if(jsonRes.errCode == 9) $("#syncDiv-"+occid).text("<?php echo $LANG['MISS_VARS'] ?>");
				}
			})
		}
	</script>
	<style type="text/css">
		fieldset{ margin:10px; padding:15px; }
		fieldset legend{ font-weight:bold; }
		.form-label{  }
		button{ margin:15px; }
	</style>
</head>
<body>
	<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href="../../index.php"><?php echo $LANG['HOME'] ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COLL_MANAGE'] ?></a> &gt;&gt;
	<a href="igsnmapper.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['IGSN_GUID_GEN'] ?></a> &gt;&gt;
	<a href="igsnmanagement.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['IGSN_MANAGE'] ?></a> &gt;&gt;
	<b><?php echo $LANG['IGSN_VERIFY'] ?></b>
</div>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['IGSN_MANAGE'] . ': ' . $guidManager->getCollectionName(); ?></h1>
	<?php
	if($isEditor){
		if($statusStr){
			?>
			<fieldset>
				<legend><?php echo $LANG['ERROR_PANEL'] ?></legend>
				<?php echo $statusStr; ?>
			</fieldset>
			<?php
		}
		if(!$guidManager->getProductionMode()){
			echo '<h2 style="color:orange">-- ' . $LANG['DEV_MODE'] . ' --</h2>';
		}
		if($action == 'verifysesar'){
			echo '<fieldset><legend>' . $LANG['ACTION_PANEL'] . '</legend>';
			echo '<ul>';
			$guidManager->setVerboseMode(2);
			echo '<li>' . $LANG['VERIFYING'] . '</li>';
			$sesarArr = $guidManager->verifySesarGuids();
			echo '<li style="margin-left:15px">' . $LANG['RESULTS'] . '</li>';
			echo '<li style="margin-left:25px">' . $LANG['CHECKED'] . ' ' . $sesarArr['totalCnt'] . ' ' . $LANG['IGSNS'] . '</li>';
			if(isset($sesarArr['collid'])){
				echo '<li style="margin-left:25px">' . $LANG['REG_IGSN_BY_COLL'] . '</li>';
				foreach($sesarArr['collid'] as $id => $collArr){
					echo '<li style="margin-left:40px"><a href="../misc/collprofiles.php?collid=' . htmlspecialchars($id, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($collArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>: ' . htmlspecialchars($collArr['cnt'],) . ' ' . $LANG['IGSNS'] . '</li>';
				}
			}
			$missingCnt = 0;
			if(isset($sesarArr['missing'])) $missingCnt = count($sesarArr['missing']);
			echo '<li style="margin-left:25px">';
			echo $LANG['IGSN_NOT_IN_DB'] . ': '.$missingCnt;
			if($missingCnt) echo ' <a href="#" onclick="$(\'#missingGuidList\').show();return false;">(display list)</a>';
			echo '</li>';
			if($missingCnt){
				echo '<div id="missingGuidList" style="margin-left:40px;display:none">';
				foreach($sesarArr['missing'] as $igsn => $missingArr){
					echo '<li><a href="https://app.geosamples.org/sample/igsn/' . htmlspecialchars($igsn, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" title="' . $LANG['OPEN_IN_IGSN'] . '">' . htmlspecialchars($igsn, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ';
					if(isset($missingArr['occid'])){
						echo '=> <a href="../individual/index.php?occid=' . htmlspecialchars($missingArr['occid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" title="' . $LANG['OPEN_OCC'] . '">' . htmlspecialchars($missingArr['catNum'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ';
						echo '<a href="#" onclick="syncIGSN(' . $missingArr['occid'] . ',\'' . $missingArr['catNum'] . '\',\'' . $igsn . '\');return false" title="' . $LANG['ADD_IGSN'] . '"><img src="../../images/link.png" style="width:13px"/></a>';
						echo '<span id="syncDiv-'.$missingArr['occid'].'" style="margin-left:15px;color:green;"></span>';
					}
					echo '</li>';
				}
				echo '</div>';
			}
			echo '<li style="margin-left:15px">' . $LANG['FINISHED_VERIFY'] . '</li>';
			echo '</ul>';

			if($collid){
				echo '<ul style="margin-top:15px">';
				echo '<li>' . $LANG['VERIFYING_COLL'] . '</li>';
				ob_flush();
				flush();
				$localArr = $guidManager->verifyLocalGuids();
				$missingCnt = 0;
				if(isset($localArr)) $missingCnt = count($localArr);
				echo '<li style="margin-left:15px">';
				echo $LANG['IGSN_IN_PORTAL'] . ': '.$missingCnt;
				if($missingCnt) echo ' <a href="#" onclick="$(\'#unmappedGuidList\').show();return false;">' . '(' . $LANG['DISPLAY_LIST'] . ')' . '</a>';
				echo '</li>';
				if($missingCnt){
					echo '<div id="unmappedGuidList" style="margin-left:30px;display:none">';
					foreach($localArr as $occid => $guid){
						echo '<li><a href="../individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($guid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
					}
					echo '</div>';
				}
				echo '<li style="margin-left:15px">' . $LANG['FINISHED_LOCAL'] . '</li>';
				echo '</ul>';
			}
			echo '</fieldset>';
		}
	}
	else echo '<h2>' . $LANG['NOT_AUTH'] . '</h2>';
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>