<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/datasets/duplicatemanager.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/datasets/duplicatemanager.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/datasets/duplicatemanager.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collId = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$dupeDepth = array_key_exists('dupedepth', $_REQUEST) ? filter_var($_REQUEST['dupedepth'], FILTER_SANITIZE_NUMBER_INT) : 0;
$start = array_key_exists('start', $_REQUEST) ? filter_var($_REQUEST['start'], FILTER_SANITIZE_NUMBER_INT) : 0;
$limit = array_key_exists('limit', $_REQUEST) ? filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT) : 1000;
$action = array_key_exists('action', $_REQUEST) ? filter_var($_REQUEST['action'], FILTER_SANITIZE_STRING) : '';
$formSubmit = array_key_exists('formsubmit' , $_POST) ? $_POST['formsubmit'] : '';

if(!$SYMB_UID){
	header('Location: ../../profile/index.php?refurl=../collections/datasets/duplicatemanager.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));
}

$dupManager = new OccurrenceDuplicate();
$collMap = $dupManager->getCollMap($collId);

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collId, $USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
elseif($collMap['colltype'] == 'General Observations' && array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collId, $USER_RIGHTS['CollEditor'])){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations') $dupManager->setObsUid($SYMB_UID);

if($isEditor && $formSubmit){
	if($formSubmit == 'clusteredit'){
		$statusStr = $dupManager->editCluster($_POST['dupid'],$_POST['title'],$_POST['description'],$_POST['notes']);
	}
	elseif($formSubmit == 'clusterdelete'){
		$statusStr = $dupManager->deleteCluster($_POST['deldupid']);
	}
	elseif($formSubmit == 'occdelete'){
		$statusStr = $dupManager->deleteOccurFromCluster($_POST['dupid'],$_POST['occid']);
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['DUP_CLUSTERING']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function verifyEditForm(f){
			if(f.title == ""){
				alert("<?php echo $LANG['TITLE_VALUE']; ?>");
				return false;
			}
			return true;
		}

		function openOccurPopup(occid) {
			occWindow=open("../individual/index.php?occid="+occid,"occwin"+occid,"resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
			if(occWindow.opener == null) occWindow.opener = self;
		}

		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="block"){
					ele.style.display="none";
		  		}
			 	else {
			 		ele.style.display="block";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="inline";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}
	</script>
    <style type="text/css">
		table.styledtable td { white-space: nowrap; }
		fieldset{ min-height: 400px }
    </style>
</head>
<body>
	<?php
	$displayLeftMenu = true;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<?php
		if($collMap['colltype'] == 'General Observations'){
			echo '<a href="../../profile/viewprofile.php?tabindex=1">'.$LANG['PERS_MANAGE_MENU'].'</a> &gt;&gt; ';
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid='.$collId.'&emode=1">'.$LANG['COL_MANAGE'].'</a> &gt;&gt; ';
		}
		if($action){
			echo '<a href="duplicatemanager.php?collid='.$collId.'">'.$LANG['DUP_MANAGE'].'</a> &gt;&gt; ';
			echo '<b>'.$LANG['DUP_CLUSTERS'].'</b>';
		}
		else{
			echo '<b>'.$LANG['DUP_MANAGE'].'</b>';
		}
		?>
	</div>

	<!-- inner text -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			if(!$action){
				?>
				<fieldset style="padding:20px;">
					<legend><b><?php echo $LANG['DUP_LINKAGES']; ?></b></legend>
					<div>
						<?php echo $LANG['DUP_EXPLANATION']; ?>
					</div>
					<div style="margin:25px;">
						<a href="duplicatemanager.php?collid=<?php echo $collId; ?>&action=listdupes">
							<?php echo $LANG['SPEC_DUP_CLUSTERS']; ?>
						</a>
					</div>
					<div style="margin:25px;">
						<a href="duplicatemanager.php?collid=<?php echo $collId; ?>&dupedepth=2&action=listdupeconflicts">
							<?php echo $LANG['DUP_CLUSTERS_CONFLICTING']; ?>
						</a>
					</div>
					<div style="margin:25px;">
						<a href="duplicatemanager.php?collid=<?php echo $collId; ?>&action=batchlinkdupes">
							<?php echo $LANG['BATCH_LINK_DUPS']; ?>
						</a> - <?php echo $LANG['BATCH_LINK_EXPLANATION']; ?>
					</div>
					<?php
					if(!empty($ACTIVATE_EXSICCATI) && $collMap['colltype'] == 'Preserved Specimens'){
						?>
						<div style="margin:25px;">
							<a href="../exsiccati/index.php?collid=<?php echo $collId; ?>" target="_blank">
								<?php echo $LANG['EXS_DUPS']; ?>
							</a> - <?php echo $LANG['EXS_DUP_EXPLANATION']; ?>
						</div>
						<div style="margin:25px;">
							<a href="../exsiccati/index.php?collid=<?php echo $collId; ?>&formsubmit=dlexs">
								<?php echo $LANG['EXS_DOWNLOAD']; ?>
							</a> - <?php echo $LANG['EXS_DOWNLOAD_EXPLANATION']; ?>
						</div>
						<?php
					}
					?>
				</fieldset>
				<?php
			}
			else{
				if($action == 'batchlinkdupes'){
					?>
					<ul>
						<?php
						$dupManager->batchLinkDuplicates($collId,true);
						?>
					</ul>
					<?php
				}
				elseif($action == 'listdupes' || $action == 'listdupeconflicts'){
					$clusterArr = $dupManager->getDuplicateClusterList($collId, $dupeDepth, $start, $limit);
					$totalCnt = $clusterArr['cnt'];
					unset($clusterArr['cnt']);
					if($clusterArr){
						$paginationStr = '<span>';
						if($start) $paginationStr .= '<a href="duplicatemanager.php?collid='.$collId.'&dupeDepth='.$dupeDepth.'&action='.$action.'&start='.($start - $limit).'&limit='.$limit.'">';
						$paginationStr .= '&lt;&lt; '.$LANG['PREVIOUS'];
						if($start) $paginationStr .= '</a>';
						$paginationStr .= '</span>';
						$paginationStr .= ' || '.($start+1).' - '.(count($clusterArr)<$limit?$totalCnt:($start + $limit)).' || ';
						$paginationStr .= '<span>';
						if($totalCnt >= ($start+$limit)) $paginationStr .= '<a href="duplicatemanager.php?collid='.$collId.'&dupeDepth='.$dupeDepth.'&action='.$action.'&start='.($start + $limit).'&limit='.$limit.'">';
						$paginationStr .= $LANG['NEXT'].' &gt;&gt;';
						if($totalCnt >= ($start+$limit)) $paginationStr .= '</a>';
						$paginationStr .= '</span>';
						?>
						<div style="clear:both;font-weight:bold;font-size:140%;">
							<?php echo $collMap['collectionname']; ?>
						</div>
						<div style="float:right;">
							<?php echo $paginationStr; ?>
						</div>
						<div style="font-weight:bold;margin-left:15px;">
							<?php echo $totalCnt.' '.$LANG['DUP_CLUSTERS'].' '.($action == 'listdupeconflicts'?$LANG['WITH_ID_DIFFERENCES']:''); ?>
						</div>
						<div style="margin:20px 0px;clear:both;">
							<?php
							foreach($clusterArr as $dupId => $dupArr){
								?>
								<div style="clear:both;margin:10px 0px;">
									<div style="font-weight:bold;font-size:120%;">
										<?php echo $dupArr['title']; ?>
										<span onclick="toggle('editdiv-<?php echo $dupId; ?>')" title="<?php echo $LANG['DISP_EDIT_CONTROLS']; ?>"><img src="../../images/edit.png" style="width:13px;" /></span>
									</div>
									<?php
									if(isset($dupArr['desc'])) echo '<div style="margin-left:10px;">'.$dupArr['desc'].'</div>';
									if(isset($dupArr['notes'])) echo '<div style="margin-left:10px;">'.$dupArr['notes'].'</div>';
									?>
									<div class="editdiv-<?php echo $dupId; ?>" style="display:none;">
										<fieldset style="margin:20px;padding:15px;">
											<legend><b>Edit Cluster</b></legend>
											<form name="dupeditform-<?php echo $dupId; ?>" method="post" action="duplicatemanager.php" onsubmit="return verifyEditForm(this);">
												<b>Title:</b> <input name="title" type="text" value="<?php echo $dupArr['title']; ?>" style="width:300px;" /><br/>
												<b>Description:</b> <input name="description" type="text" value="<?php echo $dupArr['desc']; ?>" style="width:400px;" /><br/>
												<b>Notes:</b> <input name="notes" type="text" value="<?php echo $dupArr['notes']; ?>" style="width:400px;" /><br/>
												<input name="dupid" type="hidden" value="<?php echo $dupId; ?>" />
												<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
												<input name="start" type="hidden" value="<?php echo $start; ?>" />
												<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
												<input name="action" type="hidden" value="<?php echo $action; ?>" />
												<input name="formsubmit" type="hidden" value="clusteredit" />
												<button name="submit" type="submit" value="Save Edits" ><?php echo $LANG['SAVE_EDITS']; ?></button>
											</form>
											<form name="dupdelform-<?php echo $dupId; ?>" method="post" action="duplicatemanager.php" onsubmit="return confirm('<?php echo $LANG['SURE_DEL_DUP']; ?>');">
												<input name="deldupid" type="hidden" value="<?php echo $dupId; ?>" />
												<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
												<input name="start" type="hidden" value="<?php echo $start; ?>" />
												<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
												<input name="action" type="hidden" value="<?php echo $action; ?>" />
												<input name="formsubmit" type="hidden" value="clusterdelete" />
												<button name="submit" type="submit" value="Delete Cluster" ><?php echo $LANG['DEL_CLUSTER']; ?></button>
											</form>
										</fieldset>
									</div>
									<div style="margin:7px 10px;">
										<?php
										unset($dupArr['title']);
										unset($dupArr['desc']);
										unset($dupArr['notes']);
										foreach($dupArr as $occid => $oArr){
											?>
											<div style="margin:10px">
												<div style="float:left;">
													<a href="#" onclick="openOccurPopup(<?php echo $occid; ?>); return false;"><b><?php echo $oArr['id']; ?></b></a> =&gt;
													<?php echo $oArr['recby']; ?>
												</div>
												<div class="editdiv-<?php echo $dupId; ?>" style="display:none;float:left;" title="<?php echo $LANG['DEL_SPEC_FROM_CLUSTER']; ?>">
													<form name="dupdelform-<?php echo $dupId.'-'.$occid; ?>" method="post" action="duplicatemanager.php" onsubmit="return confirm('<?php echo $LANG['SURE_DEL_SPEC_FROM_CLUSTER']; ?>');" style="display:inline;">
														<input name="dupid" type="hidden" value="<?php echo $dupId; ?>" />
														<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
														<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
														<input name="start" type="hidden" value="<?php echo $start; ?>" />
														<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
														<input name="action" type="hidden" value="<?php echo $action; ?>" />
														<input name="formsubmit" type="hidden" value="occdelete" />
														<input name="submit" type="image" src="../../images/del.png" style="width:15px;" />
													</form>
												</div>
												<div style="margin-left:15px;clear:both;">
													<?php
													echo '<b>'.$oArr['sciname'].'</b><br/>';
													if($oArr['idby']) echo $LANG['DET_BY'].': '.$oArr['idby'].' '.$oArr['dateid'];
													?>
												</div>
											</div>
											<?php
										}
										?>
									</div>
								</div>
								<?php
							}
							?>
						</div>
						<?php
						echo $paginationStr;
					}
					else{
						 echo '<div><b>'.$LANG['NO_DUP_CLUSTERS'].'</b></div>';
					}
				}
				?>
				<div>
					<a href="duplicatemanager.php?collid=<?php echo $collId; ?>"><?php echo $LANG['RETURN_MAIN']; ?></a>
				</div>
				<?php
			}
		}
		else{
			echo '<h2>'.$LANG['NOT_AUTH'].'</h2>';
		}
		?>
	</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>