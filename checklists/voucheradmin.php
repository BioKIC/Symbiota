<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');
include_once($SERVER_ROOT.'/content/lang/checklists/voucheradmin.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../checklists/voucheradmin.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';
$startPos = array_key_exists('start', $_REQUEST) ? filter_var($_REQUEST['start'], FILTER_SANITIZE_NUMBER_INT) : 0;
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
		$linkVouchers = true;
		if(array_key_exists('excludevouchers',$_POST) && $_POST['excludevouchers']) $linkVouchers = false;
		$clManager->linkTaxaVouchers($_POST['occids'], $useCurrentTaxonomy, $linkVouchers);
	}
	elseif($action == 'resolveconflicts'){
		$clManager->batchTransferConflicts($_POST['occid'], (array_key_exists('removetaxa',$_POST) ? true : false));
	}
}
$clManager->setCollectionVariables();
$clMetaArr = $clManager->getClMetadata();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['CHECKLIST_ADMIN'];?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
		var footprintwktExists = <?php echo ($clManager->getClFootprintWkt()?'true':'false') ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.voucheradmin.js?ver=2"></script>
	<style type="text/css">
		li{ margin:5px; }
		.family-div{ font-weight: bold; }
		.taxa-block{ margin: 10px; text-decoration: italic; }
		.taxon-input{ width: 200px; }
		.styledtable{ font-family:Arial; font-size:12px; }
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
	<a href="../index.php"><?php echo $LANG['NAV_HOME']?></a> &gt;&gt;
	<a href="checklist.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><?php echo $LANG['RETURNCHECK'];?></a> &gt;&gt;
	<b><?php echo $LANG['CHECKLIST_ADMIN'];?></b>
</div>
<!-- This is inner text! -->
<div id='innertext'>
<div style="color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
	<a href="checklist.php?clid=<?php echo $clid.'&pid='.$pid; ?>">
		<?php echo $clManager->getClName(); ?>
	</a>
</div>
<?php
if($statusStr){
	?>
	<hr />
	<div style="margin:20px;font-weight:bold;color:red;">
		<?php echo $statusStr; ?>
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
			<span style="margin-left:10px;"><a href="#" onclick="toggle('sqlbuilderdiv');return false;" title="Edit Search Statement"><img src="../images/edit.png" style="width:15px;border:0px;"/></a></span>
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
						<td>
							<div style="margin:2px;">
								<b><?php echo $LANG['COUNTRY'];?>:</b>
								<input type="text" name="country" value="<?php echo isset($termArr['country'])?$termArr['country']:''; ?>" title="Enter multiple countries separated by semicolons" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['STATE'];?>:</b>
								<input type="text" name="state" value="<?php echo isset($termArr['state'])?$termArr['state']:''; ?>" title="Enter multiple states separated by semicolons" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['COUNTY'];?>:</b>
								<input type="text" name="county" value="<?php echo isset($termArr['county'])?$termArr['county']:''; ?>" title="Enter multiple counties separated by semicolons" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['LOCALITY'];?>:</b>
								<input type="text" name="locality" value="<?php echo isset($termArr['locality'])?$termArr['locality']:''; ?>" />
							</div>
							<div style="margin:2px;" title="Genus, family, or higher rank">
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
								<input name="recordedby" type="text" value="<?php echo isset($termArr['recordedby'])?$termArr['recordedby']:''; ?>" style="width:250px" title="Enter multiple collectors separated by semicolons" />
							</div>
						</td>
						<td style="padding-left:20px;">
							<div style="float:left;">
								<div>
									<b><?php echo $LANG['LATN'];?>:</b>
									<input id="upperlat" type="text" name="latnorth" style="width:80px;" value="<?php echo isset($termArr['latnorth'])?$termArr['latnorth']:''; ?>" title="Latitude North" />
									<?php
									$coordAidUrl = '../collections/tools/mapcoordaid.php?mapmode=rectangle&latdef='.$clMetaArr['latcentroid'].'&lngdef='.$clMetaArr['longcentroid'];
									?>
									<a href="#" onclick="openPopup('<?php echo $coordAidUrl; ?>','boundingbox')"><img src="../images/world.png" style="width:12px" title="Find Coordinate" /></a>
								</div>
								<div>
									<b><?php echo $LANG['LATS'];?>:</b>
									<input id="bottomlat" type="text" name="latsouth" style="width:80px;" value="<?php echo isset($termArr['latsouth'])?$termArr['latsouth']:''; ?>" title="Latitude South" />
								</div>
								<div>
									<b><?php echo $LANG['LONGE'];?>:</b>
									<input id="rightlong" type="text" name="lngeast" style="width:80px;" value="<?php echo isset($termArr['lngeast'])?$termArr['lngeast']:''; ?>" title="Longitude East" />
								</div>
								<div>
									<b><?php echo $LANG['LONGW'];?>:</b>
									<input id="leftlong" name="lngwest" type="text" style="width:80px;" value="<?php echo isset($termArr['lngwest'])?$termArr['lngwest']:''; ?>" title="Longitude West" />
								</div>
								<div>
									<input name="onlycoord" value="1" type="checkbox" <?php if(isset($termArr['onlycoord'])) echo 'CHECKED'; ?> onclick="coordInputSelected(this)" />
									<?php echo (isset($LANG['ONLYCOORD'])?$LANG['ONLYCOORD']:'Only include occurrences with coordinates');?>
								</div>
								<div>
									<input name="includewkt" value="1" type="checkbox" <?php if(isset($termArr['includewkt'])) echo 'CHECKED'; ?> onclick="coordInputSelected(this)" />
									<?php echo (isset($LANG['POLYGON_SEARCH'])?$LANG['POLYGON_SEARCH']:'Search based on polygon defining checklist research boundaries'); ?>
									<a href="#"  onclick="openPopup('tools/mappolyaid.php?clid=<?php echo $clid; ?>','mappopup');return false;" title="Edit Metadata and polygon"><img src="../images/edit.png" style="width:12px" /></a>
								</div>
								<div>
									<input name="excludecult" value="1" type="checkbox" <?php if(isset($termArr['excludecult'])) echo 'CHECKED'; ?> />
									<?php echo (isset($LANG['EXCLUDE'])?$LANG['EXCLUDE']:'Exclude cultivated/captive records');?>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div style="margin:10px;">
								<input type="submit" name="submit" value="<?php echo $LANG['SAVESEARCH'];?>" />
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
				<form name="sqldeleteform" action="voucheradmin.php" method="post" onsubmit="return confirm('Are you sure you want to delete query variables?');">
					<div style="margin:20px">
						<input type="submit" name="submit" value="<?php echo $LANG['DELETEVARIABLES'];?>" />
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
				<li><a href="nonvoucheredtab.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos.'&displaymode='.$displayMode; ?>"><span><?php echo $LANG['NON_VOUCHERED'];?></span></a></li>
				<li><a href="vamissingtaxa.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos.'&displaymode='.($tabIndex==1?$displayMode:0).'&excludevouchers='.(isset($_POST['excludevouchers'])?$_POST['excludevouchers']:''); ?>"><span><?php echo $LANG['MISSINGTAXA'];?></span></a></li>
				<li><a href="vaconflicts.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span><?php echo $LANG['VOUCHCONF'];?></span></a></li>
				<?php
				if($clManager->hasVoucherProjects()) echo '<li><a href="imgvouchertab.php?clid='.$clid.'">'.(isset($LANG['ADDIMGV'])?$LANG['ADDIMGV']:'Add Image Voucher').'</a></li>';
				?>
				<li><a href="#reportDiv"><span><?php echo $LANG['REPORTS'];?></span></a></li>
			</ul>
			<div id="reportDiv">
				<div style="margin:25px;height:400px;">
					<div style="margin:10px 5px;"><?php echo $LANG['ADDITIONAL'];?>.</div>
					<ul>
						<li><a href="voucherreporthandler.php?rtype=fullcsv&clid=<?php echo $clid; ?>"><?php echo $LANG['FULLSPECLIST'];?></a></li>
						<?php
						$vouchersExist = $clManager->vouchersExist();
						if($vouchersExist){
							?>
							<li><a href="voucherreporthandler.php?rtype=fullvoucherscsv&clid=<?php echo $clid; ?>"><?php echo $LANG['FULLSPECLISTVOUCHER'];?></a></li>
							<li>
								<a href="#" onclick="openPopup('../collections/download/index.php?searchvar=<?php echo urlencode('clid='.$clManager->getClidFullStr()); ?>&noheader=1','repvouchers');return false;">
									<?php echo (isset($LANG['VOUCHERONLY'])?$LANG['VOUCHERONLY']:'Occurrence vouchers only (DwC-A, CSV, Tab-delimited)'); ?>
								</a>
							</li>
							<?php
						}
						?>
						<li><a href="voucherreporthandler.php?rtype=fullalloccurcsv&clid=<?php echo $clid; ?>"><?php echo $LANG['FULLSPECLISTALLOCCUR'];?></a></li>
						<li><a href="voucherreporthandler.php?rtype=pensoftxlsx&clid=<?php echo $clid; ?>" target="_blank"><?php echo (isset($LANG['PENSOFT_XLSX_EXPORT'])?$LANG['PENSOFT_XLSX_EXPORT']:'Pensoft Excel Export');?></a></li>
						<li><?php echo $LANG['SPECMISSINGTITLE'];?></li>
					</ul>
					<ul style="margin:-10 0px 0px 25px;list-style-type:circle">
						<li><a href="voucherreporthandler.php?rtype=missingoccurcsv&clid=<?php echo $clid; ?>"><?php echo $LANG['SPECMISSTAXA'];?></a></li>
						<li><a href="voucherreporthandler.php?rtype=problemtaxacsv&clid=<?php echo $clid; ?>"><?php echo $LANG['SPECMISSPELLED'];?></a></li>
					</ul>
				</div>
			</div>
		</div>
	<?php
	}
}
else{
	if(!$clid){
		echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['CHECKIDNOTSET'].'</div>';
	}
	else{
		echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['NOADMINPERM'].'</div>';
	}
}
?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>