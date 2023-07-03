<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageImport.php');
include_once($SERVER_ROOT.'/content/lang/imagelib/admin/imageloader.en.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/imagelib/admin/imageloader.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/imagelib/admin/imageloader.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists('action', $_POST) ? $_POST['action'] : '';
$ulFileName = array_key_exists('ulfilename', $_POST) ? filter_var($_POST['ulfilename'], FILTER_SANITIZE_STRING) : '';

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

$importManager = new ImageImport();

$fieldMap = Array();			//array(sourceField => symbIndex)
if($isEditor){
	if($action){
		$importManager->setUploadFile($ulFileName);
	}
	if(array_key_exists('sf', $_POST)){
		//Grab field mapping, if mapping form was submitted
		$sourceFields = $_POST['sf'];
		$targetFields = $_POST['tf'];
		for($x = 0; $x < count($targetFields); $x++){
			if($sourceFields[$x] && $targetFields[$x] !== ''){
				$sourceField = filter_var($sourceFields[$x], FILTER_SANITIZE_STRING);
				$targetField = filter_var($targetFields[$x], FILTER_SANITIZE_STRING);
				$fieldMap[$sourceField] = $targetField;
			}
		}
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['IMG_LOADER']; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function verifyUploadForm(f){
			return true;
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = true;
include($SERVER_ROOT.'/includes/header.php');

?>
<div class="navpath">
	<b><a href="../../index.php"><?php echo $LANG['HOME']; ?></a></b> &gt;&gt;
	<b><?php echo $LANG['IMG_IMPORTER']; ?></b>
</div>

<h1><?php echo $LANG['IMG_IMPORTER']; ?></h1>
<div  id="innertext">
	<div style="margin-bottom:30px;">

	</div>
	<div>
		<form name="uploadform" action="imageloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
			<fieldset style="width:90%;">
				<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['IMG_UPLOAD_FORM']; ?></legend>
				<div style="margin:10px;">
				<?php echo $LANG['IMG_UPLOAD_EXPLAIN']; ?>
				</div>
				<input type="hidden" name="ulfilename" value="<?php echo $importManager->getUploadFileName();?>" />
				<?php
				if(!$importManager->getUploadFileName()){
					?>
					<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
					<div>
						<div>
							<b><?php echo $LANG['UPLOAD_FILE']; ?>:</b>
							<div style="margin:10px;">
								<input name="uploadfile" type="file" size="40" />
							</div>
						</div>
						<div style="margin:10px;">
							<button type="submit" name="action" value="analyzeInputFile" ><?php echo $LANG['ANALYZE_INPUT_FILE']; ?></button>
						</div>
					</div>
					<?php
				}
				else{
					?>
					<div id="mdiv" style="margin:15px;">
						<table border="1" cellpadding="2" style="border:1px solid black">
							<tr>
								<th>
									<?php echo $LANG['SOURCE_FIELD']; ?>
								</th>
								<th>
									<?php echo $LANG['TARGET_FIELD']; ?>
								</th>
							</tr>
							<?php
							$sArr = $importManager->getSourceArr();
							$tArr = $importManager->getTargetArr();
							foreach($sArr as $sKey => $sField){
								?>
								<tr>
									<td style='padding:2px;'>
										<?php echo $sField; ?>
										<input type="hidden" name="sf[]" value="<?php echo $sField; ?>" />
									</td>
									<td>
										<select name="tf[]" style="background:<?php echo (array_key_exists(strtolower($sField),$fieldMap)?'':'yellow');?>">
											<option value=""><?php echo $LANG['SELECT_TARGET']; ?></option>
											<?php
											$sField = strtolower($sField);
											//Check to see if field is mapped
											$symbIndex = '';
											if(array_key_exists($sField,$fieldMap)){
												//Field is mapped
												$symbIndex = $fieldMap[$sField];
											}
											if($symbIndex === ''){
												$transStr = $importManager->getTranslation($sField);
												if($transStr) $sField = $transStr;
											}
											$selStr = "";
											echo "<option value='unmapped' ".($symbIndex=="unmapped"?'SELECTED':'').">".$LANG['LEAVE_UNMAPPED']."</option>";
											echo '<option value="">-------------------------</option>';
											foreach($tArr as $tKey => $tField){
												if($selStr !== 0){
													if($symbIndex === '' && $sField == strtolower($tField)){
														$selStr = "SELECTED";
													}
													elseif(is_numeric($symbIndex) && $symbIndex == $tKey){
														$selStr = "SELECTED";
													}
												}
												echo '<option value="'.$tKey.'" '.($selStr?$selStr:'').'>'.$tField."</option>\n";
												if($selStr) $selStr = 0;
											}
											?>
										</select>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<div>
							* <?php echo $LANG['FIELDS_YELLOW']; ?>
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Verify Mapping" /><br/>
							<fieldset>
								<legend><?php echo $LANG['LRG_IMG']; ?></legend>
								<input name="lgimg" type="radio" value="0" checked /> <?php echo $LANG['LEAVE_BLANK']; ?><br/>
								<input name="lgimg" type="radio" value="1" /> <?php echo $LANG['MAP_REMOTE_IMGS']; ?><br/>
								<input name="lgimg" type="radio" value="2" /> <?php echo $LANG['IMPORT_LOCAL']; ?>
							</fieldset>
							<?php echo $LANG['BASE_PATH']; ?>: <input name="basepath" type="text" value="" /><br/>
							<button name="action" type="submit" value="Upload Images" ><?php echo $LANG['UPLOAD_IMGS']; ?></button>
						</div>
					</div>
					<?php
				}
				?>
			</fieldset>
		</form>
	</div>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>