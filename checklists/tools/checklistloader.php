<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistLoaderManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklistloader.'.$LANG_TAG.'.php'))
	include_once($SERVER_ROOT.'/content/lang/checklists/checklistloader.'.$LANG_TAG.'.php');
else
	include_once($SERVER_ROOT.'/content/lang/checklists/checklistloader.en.php');

header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../checklists/tools/checklistloader.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"";
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$thesId = array_key_exists("thes",$_REQUEST)?$_REQUEST["thes"]:0;
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$clLoaderManager = new ChecklistLoaderManager();
$clLoaderManager->setClid($clid);
$clMeta = $clLoaderManager->getChecklistMetadata();

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}
?>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . " " . $LANG['SPEC_CHECKLOAD']?> </title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function validateUploadForm(thisForm){
			var testStr = document.getElementById("uploadfile").value;
			if(testStr == ""){
				alert("<?php echo $LANG['SELECT_FILE']; ?>");
				return false;
			}
			testStr = testStr.toLowerCase();
			if(testStr.indexOf(".csv") == -1 && testStr.indexOf(".CSV") == -1){
				alert("<?php echo $LANG['DOCUMENT'] . " "; ?>" + document.getElementById("uploadfile").value + "<?php echo " " . $LANG['MUST_BE_CSV']; ?>");
				return false;
			}
			return true;
		}

		function displayErrors(clickObj){
			clickObj.style.display='none';
			document.getElementById('errordiv').style.display = 'block';
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = true;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'> <?php echo $LANG['HOME']; ?> </a> &gt;&gt;
		<?php
		if($pid) echo '<a href="'.$CLIENT_ROOT.'/projects/index.php?pid='.$pid.'">';
		echo '<a href="../checklist.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . $LANG['RETURN_CHECKLIST'] . '</a> &gt;&gt; ';
		?>
		<a href="checklistloader.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><b><?php echo $LANG['CHECK_LOADER']; ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1>
			<a href="<?php echo $CLIENT_ROOT."/checklists/checklist.php?clid=" . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
				<?php echo $clMeta['name']; ?>
			</a>
		</h1>
		<div style="margin:10px;">
			<b>Authors:</b> <?php echo $clMeta['authors']; ?>
		</div>
		<?php
			if($isEditor){
				if($action == "Upload Checklist"){
					?>
					<div style='margin:10px;'>
						<ul>
							<li><?php $LANG['LOAD_CHECKL'] ?></li>
							<?php
							$cnt = $clLoaderManager->uploadCsvList($thesId);
							$statusStr = $clLoaderManager->getErrorMessage();
							if(!$cnt && $statusStr){
								echo '<div style="margin:20px;font-weight:bold;">';
								echo '<div style="font-size:110%;color:red;">'.$statusStr.'</div>';
								echo '<div><a href="checklistloader.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . $LANG['RETURN_LOADER'] . '</a>' . $LANG['INPUT_MATCH'] . '</div>';
								echo '</div>';
								exit;
							}
							$probCnt = count($clLoaderManager->getProblemTaxa());
							$errorArr = $clLoaderManager->getWarningArr();
							?>
							<li> <?php echo $LANG['UPLOAD_STATUS']; ?></li>
							<li style="margin-left:10px;"><?php echo $LANG['TAXA_LOADED'] . ' ' . $cnt; ?></li>
							<li style="margin-left:10px;"><?php echo $LANG['PROBLEM_TAXA'] . ' ' . $probCnt.($probCnt?' (see below)':''); ?></li>
							<li style="margin-left:10px;"><?php echo $LANG['GENERAL_ERRORS'] . ' ' .  count($errorArr); ?></li>
							<li style="margin-left:10px;"><?php echo $LANG['UPLOAD_COMPLETE']; ?> <a href="../checklist.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['PROCEED_CHECKL'] ?></a></li>
						</ul>
						<?php
						if($probCnt){
							echo '<fieldset>';
							echo '<legend><b>' . $LANG['TAXA_RESOLUTION'] . '</b></legend>';
							$clLoaderManager->resolveProblemTaxa();
							echo '</fieldset>';
						}
						if($errorArr){
							?>
							<fieldset style="padding:20px;">
								<legend><b><?php echo $LANG['GENERAL_ERRORS']; ?></b></legend>
								<a href="#" onclick="displayErrors(this);return false;"><b> <?php echo $LANG['DISPLAY'] . " " . htmlspecialchars(count($errorArr), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . " " . $LANG['ERRORS']; ?> </b></a>
								<div id="errordiv" style="display:none">
									<ol style="margin-left:15px;">
										<?php
										foreach($errorArr as $errStr){
											echo '<li>'.$errStr.'</li>';
										}
										?>
									</ol>
								</div>
							</fieldset>
							<?php
						}
						?>
					</div>
					<?php
				}
				else{
					?>
					<form enctype="multipart/form-data" action="checklistloader.php" method="post" onsubmit="return validateUploadForm(this);">
						<fieldset style="padding:15px;width:800px;">
							<legend><b> <?php echo $LANG['UPLOAD_FORM']; ?></b></legend>
							<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
							<div style="font-weight:bold;">
								<?php echo $LANG['CHECKLIST_FILE']; ?>
								<input id="uploadfile" name="uploadfile" type="file" size="45" />
							</div>
							<div style="margin-top:10px;">
								<?php echo $LANG['TAXA_RESOLUTION']; ?>
								<select name="thes">
									<option value=""><?php echo $LANG['LEAVE_TAXA']; ?></option>
									<?php
									$thesArr = $clLoaderManager->getThesauri();
									foreach($thesArr as $k => $v){
										echo "<option value='".$k."'>".$v."</option>";
									}
									?>
								</select>

							</div>
							<div style="margin-top:10px;">
								<div> <?php echo $LANG['FILE_DESCR']; ?></div>
								<ul>
									<li><b><?php echo $LANG['SCINAME']; ?></b> <?php echo $LANG['REQUIRED']; ?></li>
									<li><b><?php echo $LANG['FAMILY']; ?></b> <?php echo $LANG['OPTIONAL']; ?></li>
									<li><b><?php echo $LANG['HABITAT']; ?></b> <?php echo $LANG['OPTIONAL']; ?></li>
									<li><b><?php echo $LANG['ABUNDANCE']; ?></b> <?php echo $LANG['OPTIONAL']; ?></li>
									<li><b><?php echo $LANG['NOTES']; ?></b> <?php echo $LANG['OPTIONAL']; ?></li>
									<li><b><?php echo $LANG['INTERNALNOTES']; ?></b> <?php echo $LANG['OPTIONAL_DISP']; ?></li>
									<li><b><?php echo $LANG['SOURCE']; ?></b> <?php echo $LANG['OPTIONAL']; ?></li>
								</ul>
							</div>
							<div style="margin:25px;">
								<input id="clloadsubmit" name="action" type="submit" value="Upload Checklist" />
								<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							</div>
						</fieldset>
					</form>
				<?php
				}
			}
			else{
				echo "<h2>" . $LANG['NO_RIGHTS'] . "</h2>";
			}
		?>
	</div>
	<?php
		include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>