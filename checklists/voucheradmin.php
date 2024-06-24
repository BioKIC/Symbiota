<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/voucheradmin.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/voucheradmin.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/voucheradmin.en.php');
header('Content-Type: text/html; charset='.$CHARSET);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../checklists/voucheradmin.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';
$startPos = array_key_exists('start', $_REQUEST) ? filter_var($_REQUEST['start'], FILTER_SANITIZE_NUMBER_INT) : 0;
$excludeVouchers = !empty($_POST['excludevouchers']) && $_POST['excludevouchers'] ? 1 : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';
$displayMode = (array_key_exists('displaymode', $_REQUEST) ? filter_var($_REQUEST['displaymode'], FILTER_SANITIZE_NUMBER_INT) : 0);

$clManager = new ChecklistVoucherReport();
$clManager->setClid($clid);

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin']))){
	$isEditor = 1;
	if($action == 'SaveSearch'){
		$statusStr = $clManager->saveQueryVariables($_POST);
	}
	elseif($action == 'DeleteVariables'){
		$statusStr = $clManager->deleteQueryVariables();
	}
	elseif($action == 'addVouchers'){
		$statusStr = $clManager->linkVouchers($_POST['occids']);
		$statusStr .= ' vouchers linked';
	}
	elseif($action == 'submitVouchers'){
		$useCurrentTaxonomy = false;
		if(array_key_exists('usecurrent',$_POST) && $_POST['usecurrent']) $useCurrentTaxonomy = true;
		$linkVouchers = $excludeVouchers ? false : true;
		$clManager->linkTaxaVouchers($_POST['occids'], $useCurrentTaxonomy, $linkVouchers);
	}
	elseif($action == 'resolveconflicts'){
		$clManager->batchTransferConflicts($_POST['occid'], (array_key_exists('removetaxa',$_POST) ? true : false));
	}
	elseif($action == 'linkExternalVouchers'){
		$clManager->setClid($clid);
		$cnt = 0;
		foreach($_POST as $key => $value) {
			if(substr($key, 0, 2) == 'i-') {
				$tid = substr($key, 2);
				if(is_numeric($tid) && !empty($_POST[$tid])) {
					if($clManager->addExternalVouchers($tid, urldecode($_POST[$tid]))){
						$cnt++;
					}
					else{
						$statusStr .= $clManager->getErrorMessage().'<br>';
					}
				}
			}
		}
		if($cnt){
			$statusStr = $cnt . ' ' . $LANG['EXT_VOUCHERS_LINKED'];
		}
	}
}
$clManager->setCollectionVariables();
$clMetaArr = $clManager->getClMetadata();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['CHECKLIST_ADMIN']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
		var footprintwktExists = <?php echo ($clManager->getClFootprintWkt()?'true':'false') ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.voucheradmin.js?ver=2"></script>
	<style>
		li{ margin:5px; }
		.family-div{ font-weight: bold; }
		.taxa-block{ margin: 10px; font-style: italic; }
		.taxon-input{ width: 200px; }
		.styledtable{ font-size: 1rem; }
		.voucher-admin-header {
			font-size: 2em;
			font-weight: bold;
			margin: 0px 10px 10px 0px;
		}
	</style>
</head>
<body>
<?php
//$HEADER_URL = '';
//if(isset($clArray['headerurl']) && $clArray['headerurl']) $HEADER_URL = $CLIENT_ROOT.$clArray['headerurl'];
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../index.php"><?php echo htmlspecialchars($LANG['NAV_HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)?></a> &gt;&gt;
	<a href="checklist.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['RETURNCHECK'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a> &gt;&gt;
	<b><?php echo $LANG['CHECKLIST_ADMIN'];?></b>
</div>
<!-- This is inner text! -->
<div id='innertext'>
	<h1 class="page-heading"><?php echo $LANG['VOUCHER_ADMIN']; ?></h1>
<div class="voucher-admin-header">
	<a href="checklist.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
		<?php echo $clManager->getClName(); ?>
	</a>
</div>
<?php
if($statusStr){
	$textColor = 'green';
	if(stripos($statusStr, 'ERROR') !== false) $textColor = 'red';
	?>
	<hr />
	<div style="margin:1.25rem; font-weight:bold; color: <?= $textColor ?>;">
		<?= $statusStr; ?>
	</div>
	<hr />
<?php
}
if($clid && $isEditor){
	$termArr = $clManager->getQueryVariableArr();
	$collList = $clManager->getCollectionList();
	if($termArr){
		?>
		<div style="margin:10px;">
			<?php
			echo $clManager->getQueryVariableStr();
			?>
			<span style="margin-left:10px;">
				<a href="#" onclick="toggle('sqlbuilderdiv');return false;" title="<?php echo $LANG['EDITSEARCH'] ?>" aria-label="<?php echo $LANG['EDITSEARCH'] ?>">
					<img src="../images/edit.png" style="width:1.2em;border:0px;" alt="<?php echo $LANG['IMG_EDIT'] ?>"/>
				</a>
			</span>
		</div>
		<?php
	}
	?>
	<div id="sqlbuilderdiv" style="display:<?php echo ($termArr?'none':'block'); ?>;margin-top:15px;">
		<fieldset>
			<legend><b><?php echo $LANG['EDITSEARCH'];?></b></legend>
			<form name="sqlbuilderform" action="voucheradmin.php" method="post" onsubmit="return validateSqlFragForm(this);">
				<div style="margin:10px;">
					<?php echo $LANG['CHECKVOUCINSTRUC'];?>
				</div>
				<table style="margin:15px;">
					<tr>
						<td style="vertical-align: middle">
							<div style="margin:2px;">
								<b><?php echo $LANG['COUNTRY'];?>:</b>
								<input type="text" name="country" value="<?php echo isset($termArr['country'])?$termArr['country']:''; ?>" title="<?php echo $LANG['ENTER_MULT_COUNTRIES']; ?>" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['STATE'];?>:</b>
								<input type="text" name="state" value="<?php echo isset($termArr['state'])?$termArr['state']:''; ?>" title="<?php echo $LANG['ENTER_MULT_STATES'];?>" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['COUNTY'];?>:</b>
								<input type="text" name="county" value="<?php echo isset($termArr['county'])?$termArr['county']:''; ?>" title="<?php echo $LANG['ENTER_MULT_COUNTIES'];?>" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['LOCALITY'];?>:</b>
								<input type="text" name="locality" value="<?php echo isset($termArr['locality'])?$termArr['locality']:''; ?>" />
							</div>
							<div style="margin:2px;" title="<?php echo $LANG['GEN_FAM_HIGHER'];?>">
								<b><?php echo $LANG['TAXON'];?>:</b>
								<input type="text" name="taxon" value="<?php echo isset($termArr['taxon'])?$termArr['taxon']:''; ?>" />
							</div>
							<div>
								<b><?php echo $LANG['COLLECTION'];?>:</b>
								<select name="collid" style="width:275px;">
									<option value=""><?php echo $LANG['TARGETCOLL'];?></option>
									<option value="">-------------------------------------</option>
									<?php
									$selCollid = isset($termArr['collid'])?$termArr['collid']:'';
									foreach($collList as $id => $name){
										echo '<option value="'.$id.'" '.($selCollid==$id?'SELECTED':'').'>'.$name.'</option>';
									}
									?>
								</select>
							</div>
							<div>
								<b><?php echo $LANG['COLLECTOR'];?>:</b>
								<input name="recordedby" type="text" value="<?php echo isset($termArr['recordedby'])?$termArr['recordedby']:''; ?>" style="width:250px" title="<?php echo $LANG['MULTIPLE_COLLECTORS'] ?>" />
							</div>
						</td>
						<td style="padding-left:20px; vertical-align: middle">
							<div style="float:left;">
								<div>
									<b><?php echo $LANG['LATN'];?>:</b>
									<input id="upperlat" type="text" name="latnorth" style="width:80px;" value="<?php echo isset($termArr['latnorth'])?$termArr['latnorth']:''; ?>" title="<?php echo $LANG['LAT_NORTH'] ?>" />
									<?php
									$coordAidUrl = '../collections/tools/mapcoordaid.php?map_mode_strict=true&mapmode=rectangle&latdef='.$clMetaArr['latcentroid'].'&lngdef='.$clMetaArr['longcentroid'];
									?>
									<a href="#" onclick="openPopup('<?php echo htmlspecialchars($coordAidUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','boundingbox')"><img src="../images/world.png" style="width:1.2em" title="<?php echo $LANG['FIND_COORD'] ?>" /></a>
								</div>
								<div>
									<b><?php echo $LANG['LATS'];?>:</b>
									<input id="bottomlat" type="text" name="latsouth" style="width:80px;" value="<?php echo isset($termArr['latsouth'])?$termArr['latsouth']:''; ?>" title="<?php echo $LANG['LAT_SOUTH'] ?>" />
								</div>
								<div>
									<b><?php echo $LANG['LONGE'];?>:</b>
									<input id="rightlong" type="text" name="lngeast" style="width:80px;" value="<?php echo isset($termArr['lngeast'])?$termArr['lngeast']:''; ?>" title="<?php echo $LANG['LONG_EAST'] ?>" />
								</div>
								<div>
									<b><?php echo $LANG['LONGW'];?>:</b>
									<input id="leftlong" name="lngwest" type="text" style="width:80px;" value="<?php echo isset($termArr['lngwest'])?$termArr['lngwest']:''; ?>" title="<?php echo $LANG['LONG_WEST'] ?>" />
								</div>
								<div>
									<input name="onlycoord" value="1" type="checkbox" <?php if(isset($termArr['onlycoord'])) echo 'CHECKED'; ?> onclick="coordInputSelected(this)" />
									<?php echo $LANG['ONLYCOORD'];?>
								</div>
								<div>
									<input name="includewkt" value="1" type="checkbox" <?php if(isset($termArr['includewkt'])) echo 'CHECKED'; ?> onclick="coordInputSelected(this)" />
									<?php echo $LANG['POLYGON_SEARCH']; ?>
									<a href="#"  onclick="openPopup('tools/mappolyaid.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','mappopup');return false;" title="<?php echo $LANG['EDIT_META_POLYGON'] ?>"><img src="../images/edit.png" style="width:1.2em" /></a>
								</div>
								<div>
									<input name="excludecult" value="1" type="checkbox" <?php if(isset($termArr['excludecult'])) echo 'CHECKED'; ?> />
									<?php echo $LANG['EXCLUDE']; ?>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div style="margin:10px;">
								<button type="submit">
									<?php echo $LANG['SAVESEARCH'];?>
								</button>
								<input type="hidden" name="submitaction" value="SaveSearch" />
								<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
								<input type='hidden' name='pid' value='<?php echo $pid; ?>' />
							</div>
						</td>
					</tr>
				</table>
			</form>
		</fieldset>
		<?php
		if($termArr){
			?>
			<fieldset>
				<legend><b><?php echo $LANG['REMOVESEARCH'];?></b></legend>
				<form name="sqldeleteform" action="voucheradmin.php" method="post" onsubmit="return confirm('<?php echo $LANG['SURE_DELETE_QUERY'];?>');">
					<div style="margin:20px">
					<button class="button-danger" type="submit">
						<?php echo $LANG['DELETEVARIABLES'];?>
					</button>
					<input type="hidden" name="submitaction" value="DeleteVariables" />
					</div>
					<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
					<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
				</form>
			</fieldset>
			<?php
		}
		?>
	</div>
	<?php
	if($termArr){
		?>
		<div id="tabs" style="margin-top:25px;">
			<ul>

				<li><a href="nonvoucheredtab.php?clid=<?= $clid . '&pid=' . $pid . '&start=' . $startPos . '&displaymode=' . $displayMode; ?>"><span><?= $LANG['NON_VOUCHERED'];?></span></a></li>
				<li><a href="vamissingtaxa.php?clid=<?= $clid . '&pid=' . $pid . '&start=' . $startPos . '&displaymode=' . ($tabIndex==1?$displayMode:0) . '&excludevouchers=' . $excludeVouchers; ?>"><span><?= $LANG['MISSINGTAXA'];?></span></a></li>
				<li><a href="vaconflicts.php?clid=<?= $clid . '&pid=' . $pid . '&start=' . $startPos; ?>"><span><?= $LANG['VOUCHCONF'];?></span></a></li>
				<?php
				if($clManager->getAssociatedExternalService()) echo '<li><a href="externalvouchers.php?clid=' . $clid . '&pid=' . $pid . '"><span>' . $LANG['EXTERNALVOUCHERS'] . '</span></a></li>';
				if($clManager->hasVoucherProjects()) echo '<li><a href="imgvouchertab.php?clid=' . $clid . '">' . (isset($LANG['ADDIMGV'])?$LANG['ADDIMGV']:'Add Image Voucher') . '</a></li>';
				?>
				<li><a href="#reportDiv"><span><?= $LANG['REPORTS'] ?></span></a></li>
			</ul>
			<div id="reportDiv">
				<div style="margin:25px;height:400px;">
					<div style="margin:10px 5px;"><?php echo $LANG['ADDITIONAL'];?>.</div>
					<ul>
						<li><a href="voucherreporthandler.php?rtype=fullcsv&clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['FULLSPECLIST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
						<?php
						$vouchersExist = $clManager->vouchersExist();
						if($vouchersExist){
							?>
							<li><a href="voucherreporthandler.php?rtype=fullvoucherscsv&clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['FULLSPECLISTVOUCHER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<li>
								<a href="#" onclick="openPopup('../collections/download/index.php?searchvar=<?php echo urlencode('clid=' . htmlspecialchars($clManager->getClidFullStr(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)); ?>&noheader=1','repvouchers');return false;">
									<?php echo $LANG['VOUCHERONLY']; ?>
								</a>
							</li>
							<?php
						}
						?>
						<li><a href="voucherreporthandler.php?rtype=fullalloccurcsv&clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['FULLSPECLISTALLOCCUR'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
						<li><a href="voucherreporthandler.php?rtype=pensoftxlsx&clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><?php echo htmlspecialchars($LANG['PENSOFT_XLSX_EXPORT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
						<li><?php echo $LANG['SPECMISSINGTITLE'];?></li>
					</ul>
					<ul style="list-style-type:circle">
						<li><a href="voucherreporthandler.php?rtype=missingoccurcsv&clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['SPECMISSTAXA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
						<li><a href="voucherreporthandler.php?rtype=problemtaxacsv&clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['SPECMISSPELLED'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
					</ul>
				</div>
			</div>
		</div>
	<?php
	}
}
else{
	if(!$clid){
		echo '<div><span style="font-weight:bold;font-size:110%;">' . $LANG['ERROR'] . ':</span>' . $LANG['CHECKIDNOTSET'] . '</div>';
	}
	else{
		echo '<div><span style="font-weight:bold;font-size:110%;">' . $LANG['ERROR'] . ':</span>' . $LANG['NOADMINPERM'] . '</div>';
	}
}
?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
