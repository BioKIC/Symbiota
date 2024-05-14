<?php
include_once('../../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/reports/annotationmanager.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/reports/annotationmanager.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/reports/annotationmanager.en.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/reports/annotationmanager.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST["collid"];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$datasetManager = new OccurrenceLabel();
$datasetManager->setCollid($collid);

$isEditor = 0;
$annoArr = array();
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
	$isEditor = 1;
}
if($isEditor){
	$annoArr = $datasetManager->getAnnoQueue();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['ANN_LAB_MAN']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script type="text/javascript">
			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("detid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function validateSelectForm(f){
				var dbElements = document.getElementsByName("detid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("<?php echo $LANG['SEL_ANN']; ?>");
			  	return false;
			}

			function openIndPopup(occid){
				openPopup('../individual/index.php?occid=' + occid);
			}

			function openEditorPopup(occid){
				openPopup('../editor/occurrenceeditor.php?occid=' + occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}

			function changeAnnoFormTarget(f, target){
				if(target == 'word'){
					f.action = 'defaultannotationsword.php';
					f.target = '_self';
				}
				else{
					//Print in browser
					f.action = 'defaultannotations.php';
					f.target = '_blank';
				}
			}
		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo htmlspecialchars($LANG['NAV_HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<?php
		if(stripos(strtolower($datasetManager->getMetaDataTerm('colltype')), "observation") !== false){
			echo '<a href="../../profile/viewprofile.php?tabindex=1">' . htmlspecialchars($LANG['PERS_MAN_MEN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($LANG['COL_MAN_PAN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		}
		?>
		<b><?php echo $LANG['ANN_LAB_PRINT']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['ANN_LAB_MAN']; ?></h1>
		<?php
		if($isEditor){
			$reportsWritable = false;
			if(is_writable($SERVER_ROOT.'/temp/report')) $reportsWritable = true;
			if(!$reportsWritable){
				?>
				<div style="padding:5px;">
					<span style="color:red;"><?php echo $LANG['CONTACT_FOR_DOC']; ?></span>
				</div>
				<?php
			}
			echo '<h2>'.$datasetManager->getCollName().'</h2>';
			?>
			<div>
				<?php
				if($annoArr){
					?>
					<form name="annoselectform" id="annoselectform" action="defaultannotations.php" method="post" onsubmit="return validateSelectForm(this);">
						<table class="styledtable" style="width:800px;">
							<tr>
								<th title="<?php echo $LANG['SEL_DESEL']; ?>" style="width:30px;"><input name="" value="" type="checkbox" onclick="selectAll(this);" /></th>
								<th style="width:25px;text-align:center;">#</th>
				<th style="width:125px;text-align:center;"><?php echo $LANG['COLLECTOR']; ?></th>
								<th style="width:300px;text-align:center;"><?php echo $LANG['SCI_NAME']; ?></th>
								<th style="width:400px;text-align:center;"><?php echo $LANG['DETERMINATION']; ?></th>
							</tr>
							<?php
							$trCnt = 0;
							foreach($annoArr as $detId => $recArr){
								$trCnt++;
								?>
								<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
									<td>
										<input type="checkbox" name="detid[]" value="<?php echo $detId; ?>" />
									</td>
									<td>
										<input type="text" name="q-<?php echo $detId; ?>" value="1" style="width:20px;border:inset;" />
									</td>
									<td>
										<a href="#" onclick="openIndPopup(<?php echo $recArr['occid']; ?>); return false;">
											<?php echo $recArr['collector']; ?>
										</a>
										<a href="#" onclick="openEditorPopup(<?php echo $recArr['occid']; ?>); return false;">
											<img src="../../images/edit.png" style="width:1.3em" />
										</a>
									</td>
									<td>
										<?php echo $recArr['sciname']; ?>
									</td>
									<td>
										<?php echo $recArr['determination']; ?>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<fieldset style="margin-top:15px;">
							<legend><b><?php echo $LANG['ANN_PRINT']; ?></b></legend>
							<div>
								<div style="margin:4px;">
									<b><?php echo $LANG['HEADER']; ?>:</b>
									<input type="text" name="lheading" value="" style="width:450px" />
								</div>
								<div style="margin:4px;">
									<b><?php echo $LANG['FOOTER']; ?>:</b>
									<input type="text" name="lfooter" value="<?php echo $datasetManager->getAnnoCollName(); ?>" style="width:450px" />
								</div>
							</div>
							<div style="float:left">
								<div style="margin:4px;">
									<input type="checkbox" name="speciesauthors" value="1" onclick="" />
									<b><?php echo $LANG['PRINT_INF_AUTH']; ?></b>
								</div>
								<div style="margin:4px;">
									<input type="checkbox" name="printcatnum" value="1" />
									<b><?php echo $LANG['PRINT_CATNUM']; ?></b>
								</div>
								<div style="margin:4px;">
									<input type="checkbox" name="clearqueue" value="1" onclick="" />
									<b><?php echo $LANG['REM_ANNO']; ?></b>
								</div>
							</div>
							<div style="float:left;margin-left:50px">
								<div style="">
									<b><?php echo $LANG['BORDER_WIDTH']; ?>:</b>
									<select name="borderwidth">
										<option value="0">0</option>
										<option value="1" selected>1</option>
										<option value="2">2</option>
										<option value="3">3</option>
									</select>
								</div>
								<div style="margin-top:4px;">
									<b><?php echo $LANG['ROWS_PER_PAGE']; ?>:</b>
									<select name="rowcount">
										<option value="1">1</option>
										<option value="2">2</option>
										<option value="3" selected>3</option>
									</select>
								</div>
								<div style="margin-top:4px;">
									<b><?php echo $LANG['SPACE_BW_LABELS']; ?>:</b>
									<input type="text" name="marginsize" value="5" style="width:25px" />
								</div>
							</div>
							<div style="float:left;margin-left:50px">
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<button type="submit" name="submitaction" onclick="changeAnnoFormTarget(this.form, 'browser')" value="Print in Browser"><?php echo $LANG['PRINT_IN_BROWSER']; ?></button>
								<?php
								if($reportsWritable){
									?>
									<div style="margin-top:5px"><button type="submit" name="submitaction" onclick="changeAnnoFormTarget(this.form, 'word');" value="Export to DOCX"><?php echo $LANG['EXPORT_TO_DOC']; ?></button></div>
									<?php
								}
								?>
							</div>
						</fieldset>
					</form>
					<?php
				}
				else{
					?>
					<div>
						<b> <?php echo $LANG['NO_ANNO']; ?> </b>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		else{
			?>
			<div>
				<b> <?php echo $LANG['NO_ANNO_PERMISSIONS']; ?> </b>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>