<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/ImageProcessor.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.en.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/collections/specprocessor/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprid = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
$fileName = array_key_exists('filename',$_REQUEST)?$_REQUEST['filename']:'';

$specManager = new SpecProcessorManager();
$specManager->setCollId($collid);

$editable = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$editable = true;
}

if($spprid) $specManager->setProjVariables($spprid);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $LANG['IMG_PROCESSOR']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<style type="text/css">.profileDiv{ clear:both; margin:2px 0px } </style>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script>
			$(function() {
				var dialogArr = new Array("speckeypattern","patternreplace","replacestr","sourcepath","targetpath","imgurl","webpixwidth","tnpixwidth","lgpixwidth","jpgcompression");
				var dialogStr = "";
				for(i=0;i<dialogArr.length;i++){
					dialogStr = dialogArr[i]+"info";
					$( "#"+dialogStr+"dialog" ).dialog({
						autoOpen: false,
						modal: true,
						position: { my: "left top", at: "right bottom", of: "#"+dialogStr }
					});

					$( "#"+dialogStr ).click(function() {
						$( "#"+this.id+"dialog" ).dialog( "open" );
					});
				}

				uploadTypeChanged();
			});

			function uploadTypeChanged(){
				var f = document.getElementById('editproj');
				var uploadType = f.projecttype.value;
				if(uploadType == 'local'){
					$("div.profileDiv").show();
					$("#titleDiv").show();
					$("#sourcePathInfoIplant").hide();
					$("#chooseFileDiv").hide();
					if(f.sourcepath.value == "-- Use Default Path --") f.sourcepath.value = "";
					f.profileEditSubmit.value = "Save Profile";
					$("#submitDiv").show();
				}
				else if(uploadType == 'file'){
					if(f.spprid.value){
						$("div.profileDiv").hide();
						$("#titleDiv").hide();
						$("#chooseFileDiv").show();
						//$("#specKeyPatternDiv").show();
						//$("#patternReplaceDiv").show();
						//$("#replaceStrDiv").show();
						f.profileEditSubmit.value = "Analyze Image Data File";
						$("#submitDiv").show();
					}
					else{
						$("#profileEditSubmit").val("Save Profile");

					}
				}
				else if(uploadType == 'iplant'){
					$("div.profileDiv").hide();
					$("#titleDiv").show();
					f.title.value = "iPlant Image Processing";
					$("#specKeyPatternDiv").show();
					$("#patternReplaceDiv").show();
					$("#replaceStrDiv").show();
					$("#sourcePathDiv").show();
					$("#sourcePathInfoIplant").show();
					if(f.sourcepath.value == "") f.sourcepath.value = "-- Use Default Path --";
					$("#profileEditSubmit").val("Save Profile");
					$("#submitDiv").show();
				}
				else{
					$("div.profileDiv").hide();
				}
			}

			function validateProjectForm(f){
				if(f.projecttype.value == ""){
					alert("<?php echo $LANG['SEL_IMPORT_TYPE']; ?>");
					return false;
				}
				if(f.projecttype.value == 'file'){
					var fileName = f.uploadfile.value;
					var fileExt = fileName.split('.').pop().toLowerCase();
					if(fileName == ""){
						alert("<?php echo $LANG['SEL_CSV']; ?>");
						return false;
					}
					else if(fileExt != "csv" && fileExt != "zip"){
						alert("<?php echo $LANG['CSV_OR_ZIP']; ?>");
						return false;
					}
				}
				else{
					if(f.speckeypattern.value == ""){
						alert("<?php echo $LANG['NEED_PATTERN_MATCH']; ?>");
						return false;
					}
					if(f.speckeypattern.value.substr(f.speckeypattern.value.length-3).toLowerCase() != "csv"){
						if(f.speckeypattern.value.indexOf("(") < 0 || f.speckeypattern.value.indexOf(")") < 0){
							alert("<?php echo $LANG['CATNUM_IN_PARENS']; ?>");
							return false;
						}
					}
				}
				if(f.projecttype.value == 'local'){
					if(!isNumeric(f.webpixwidth.value)){
						alert("<?php echo $LANG['WEB_IMG_NUMERIC']; ?>");
						return false;
					}
					else if(!isNumeric(f.tnpixwidth.value)){
						alert("<?php echo $LANG['TN_IMG_NUMERIC']; ?>");
						return false;
					}
					else if(!isNumeric(f.lgpixwidth.value)){
						alert("<?php echo $LANG['LG_IMG_NUMERIC']; ?>");
						return false;
					}
					else if(f.title.value == ""){
						alert("<?php echo $LANG['TITLE_NOT_EMPTY']; ?>");
						return false;
					}
					else if(!isNumeric(f.jpgcompression.value) || f.jpgcompression.value < 30 || f.jpgcompression.value > 100){
						alert("<?php echo $LANG['JPG_BETWEEN']; ?>");
						return false;
					}
				}
				if(f.sourcepath.value == "-- Use Default Path --") f.sourcepath.value = "";
				return true;
			}

			function validateProcForm(f){
				if(f.projtype.value == 'iplant'){
					var regexObj = /^\d{4}-\d{2}-\d{2}$/;
					var startDate = f.startdate.value;
					if(startDate != "" && !regexObj.test(startDate)){
						alert("<?php echo $LANG['PROC_DATE_FORMAT']; ?>");
						return false;
					}
				}
				if(f.matchcatalognumber.checked == false && f.matchothercatalognumbers.checked == false){
					alert("<?php echo $LANG['CHECK_MATCH_TERM']; ?>");
					return false;
				}
				return true;
			}

			function validateFileUploadForm(f){
				var sfArr = [];
				var tfArr = [];
				for(var i=0;i<f.length;i++){
					var obj = f.elements[i];
					if(obj.name.indexOf("tf[") == 0){
						if(obj.value){
							if(tfArr.indexOf(obj.value) > -1){
								alert("<?php echo $LANG['TARGET_MUST_UNIQUE']; ?>"+": "+obj.value+")");
								return false;
							}
							tfArr[tfArr.length] = obj.value;
						}
					}
					if(obj.name.indexOf("sf[") == 0){
						if(obj.value){
							if(sfArr.indexOf(obj.value) > -1){
								alert("<?php echo $LANG['SOURCE_MUST_UNIQUE']; ?>"+": "+obj.value+")");
								return false;
							}
							sfArr[sfArr.length] = obj.value;
						}
					}
				}
				if(tfArr.indexOf("catalognumber") < 0 && tfArr.indexOf("othercatalognumbers") < 0){
					alert("<?php echo $LANG['MUST_MAP_CATNUM']; ?>");
					return false;
				}
				if(tfArr.indexOf("originalurl") < 0){
					alert("<?php echo $LANG['LARGE_URL_MAPPED']; ?>");
					return false;
				}
				return true;
			}
		</script>
		<style>
			label { width:220px; float:left; margin-right:3px }
		</style>
	</head>
	<body>
		<!-- This is inner text! -->
		<div role="main" id="innertext" style="background-color:white;">
			<h1 class="page-heading screen-reader-only"><?= $LANG['IMG_PROCESSOR']; ?></h1>
			<div style="padding:15px;">
				<?php echo $LANG['IMG_PROCESSOR_EXPLAIN']; ?>
			</div>
			<?php
			if($SYMB_UID){
				if($collid){
					if($fileName){
						?>
						<form name="filemappingform" action="processor.php" method="post" onsubmit="return validateFileUploadForm(this)">
							<fieldset>
								<legend><b><?php echo $LANG['IMG_FILE_UPLOAD_MAP']; ?></b></legend>
								<div style="margin:15px;">
									<table class="styledtable" style="width:600px;font-family:Arial;font-size:12px;">
										<tr><th><?php echo $LANG['SOURCE_FIELD']; ?></th><th><?php echo $LANG['TARGET_FIELD']; ?></th></tr>
										<?php
										$translationMap = array('catalognumber' => 'catalognumber', 'othercatalognumbers' => 'othercatalognumbers', 'othercatalognumber' => 'othercatalognumbers', 'url' => 'url',
											'web' => 'url','webviewoptional' => 'url','thumbnailurl' => 'thumbnailurl', 'thumbnail' => 'thumbnailurl','thumbnailoptional' => 'thumbnailurl',
											'largejpg' => 'originalurl', 'originalurl' => 'originalurl', 'large' => 'originalurl', 'sourceurl' => 'sourceurl');
										$imgProcessor = new ImageProcessor();
										$headerArr = $imgProcessor->getHeaderArr($fileName);
										foreach($headerArr as $i => $sourceField){
											echo '<tr><td>';
											echo $sourceField;
											$sourceField = strtolower($sourceField);
											echo '<input type="hidden" name="sf['.$i.']" value="'.$sourceField.'" />';
											$sourceField = preg_replace('/[^a-z]+/','',$sourceField);
											echo '</td><td>';
											echo '<select name="tf[' . $i . ']" style="background:' . (!array_key_exists($sourceField,$translationMap) ? 'yellow' : '') . '">';
											echo '<option value="">' . $LANG['SEL_TARGET_FIELD'] . '</option>';
											echo '<option value="">-------------------------</option>';
											echo '<option value="catalognumber" ' . (isset($translationMap[$sourceField]) && $translationMap[$sourceField] == 'catalognumber' ? 'SELECTED' : '') . '>' . $LANG['CAT_NUM'] . '</option>';
											echo '<option value="othercatalognumbers" ' . (isset($translationMap[$sourceField]) && $translationMap[$sourceField] == 'othercatalognumbers' ? 'SELECTED' : '') . '>' . $LANG['OTHER_CAT_NUMS'] . '</option>';
											echo '<option value="originalurl" ' . (isset($translationMap[$sourceField]) && $translationMap[$sourceField] == 'originalurl' ? 'SELECTED' : '') . '>' . $LANG['LG_IMG_URL'] . '</option>';
											echo '<option value="url" ' . (isset($translationMap[$sourceField]) && $translationMap[$sourceField] == 'url' ? 'SELECTED' : '') . '>' . $LANG['WEB_IMG_URL'] . '</option>';
											echo '<option value="thumbnailurl" ' . (isset($translationMap[$sourceField]) && $translationMap[$sourceField] == 'thumbnailurl' ? 'SELECTED' : '') . '>' . $LANG['TN_URL'] . '</option>';
											echo '<option value="sourceurl" ' . (isset($translationMap[$sourceField]) && $translationMap[$sourceField] == 'sourceurl' ? 'SELECTED' : '') . '>' . $LANG['SOURCE_URL'] . '</option>';
											echo '</select>';
											echo '</td></tr>';
										}
										?>
									</table>
								</div>
								<div style="margin:15px;">
									<input name="createnew" type="checkbox" value ="1" /> <?php echo $LANG['LINK_BLANK_RECORD']; ?>
								</div>
								<div style="margin:15px;">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="tabindex" type="hidden" value="0" />
									<input name="filename" type="hidden" value="<?php echo $fileName; ?>" />
									<button name="submitaction" type="submit" value="mapImageFile"><?php echo $LANG['MAP_IMGS']; ?></button>
								</div>
							</fieldset>
						</form>
						<?php
					}
					else{
						if(!$spprid){
							$specProjects = $specManager->getProjects();
							if($specProjects){
								?>
								<form name="sppridform" action="index.php" method="post">
									<fieldset>
										<legend><b><?php echo $LANG['SAVED_PROCESSING_PROF']; ?></b></legend>
										<div style="margin:15px;">
											<?php
											foreach($specProjects as $id => $projTitle){
												echo '<input type="radio" name="spprid" value="'.$id.'" onchange="this.form.submit()" /> '.$projTitle.'<br/>';
											}
											?>
										</div>
										<div style="margin:15px;">
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="tabindex" type="hidden" value="0" />
										</div>
									</fieldset>
								</form>
								<?php
							}
						}

						$projectType = $specManager->getProjectType();
						?>
						<div id="editdiv" style="display:<?php echo ($spprid?'none':'block'); ?>;position:relative;">
							<form id="editproj" name="editproj" action="index.php" enctype="multipart/form-data" method="post" onsubmit="return validateProjectForm(this);">
								<fieldset style="padding:15px">
									<legend><b><?php echo ($spprid ? $LANG['EDIT'] : $LANG['NEW']) . ' ' . $LANG['PROFILE']; ?></b></legend>
									<?php
									if($spprid){
										?>
										<div style="float:right;" onclick="toggle('editdiv');toggle('imgprocessdiv')" title="<?php echo $LANG['CLOSE_EDITOR']; ?>">
											<img src="../../images/edit.png" style="width:1.5em;border:0px" />
										</div>
										<input name="projecttype" type="hidden" value="<?php echo $projectType; ?>" />
										<?php
									}
									else{
										?>
										<div>
											<label><?php echo $LANG['PROC_TYPE']; ?>:</label>
											<div style="float:left;">
												<select name="projecttype" id="projecttype" onchange="uploadTypeChanged(this.form)" <?php echo ($spprid?'DISABLED':'');?>>
													<option value="">----------------------</option>
													<option value="local"><?php echo $LANG['MAP_FROM_SERVER']; ?></option>
													<option value="file"><?php echo $LANG['URL_MAP_FILE']; ?></option>
													<!-- <option value="iplant">iPlant Image Harvest</option> -->
												</select>
											</div>
										</div>
										<?php
									}
									?>
									<div id="titleDiv" style="display:<?php echo ($spprid?'block':'none'); ?>;clear:left;">
										<label><?php echo $LANG['TITLE']; ?>:</label>
										<div style="float:left;">
											<input name="title" type="text" style="width:300px;" value="<?php echo $specManager->getTitle(); ?>" />
										</div>
									</div>
									<div id="specKeyPatternDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<label><?php echo $LANG['PATT_MATCH_TERM']; ?>:</label>
										<div style="float:left;">
											<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $specManager->getSpecKeyPattern(); ?>" />
											<a id="speckeypatterninfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="speckeypatterninfodialog">
												<?php echo $LANG['PATTERN_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="patternReplaceDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<label><?php echo $LANG['REPLACEMENT_TERM']; ?>:</label>
										<div style="float:left;">
											<input name="patternreplace" type="text" style="width:300px;" value="<?= ($specManager->getPatternReplace() ? $specManager->getPatternReplace() : '') ?>" placeholder="<?= $LANG['OPTIONAL'] ?>">
											<a id="patternreplaceinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="patternreplaceinfodialog">
												<?php echo $LANG['PATT_REPLACE_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="replaceStrDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<label><?php echo $LANG['REPLACEMENT_STR']; ?>:</label>
										<div style="float:left;">
											<input name="replacestr" type="text" style="width:300px;" value="<?= ($specManager->getReplaceStr() ? $specManager->getReplaceStr() : ''); ?>" placeholder="<?= $LANG['OPTIONAL'] ?>" >
											<a id="replacestrinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="replacestrinfodialog">
												<?php echo $LANG['REPLACE_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="sourcePathDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'||$projectType=='iplant'?'block':'none'); ?>">
										<label><?php echo $LANG['IMG_SOURCE_PATH']; ?>:</label>
										<div style="float:left;">
											<input name="sourcepath" type="text" style="width:600px;" value="<?php echo $specManager->getSourcePath(); ?>" />
											<a id="sourcepathinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="sourcepathinfodialog">
												<div id="sourcePathInfoIplant" class="profileDiv" style="display:<?php echo ($projectType == 'iplant'?'block':'none'); ?>">
													iPlant server path to source images. The path should be accessible to the iPlant Data Service API.
													Scripts will crawl through all child directories within the target.
													Instances of --INSTITUTION_CODE-- and --COLLECTION_CODE-- will be dynamically replaced with
													the institution and collection codes stored within collections metadata setup. For instance,
													/home/shared/sernec/--INSTITUTION_CODE--/ would target /home/shared/sernec/xyc/ for the XYZ collection.
													Contact portal manager for more details.
													Leave blank to use default path:
													<?php
													echo (isset($IPLANT_IMAGE_IMPORT_PATH)?$IPLANT_IMAGE_IMPORT_PATH:'Not Activated');
													?>
												</div>
												<div id="sourcePathInfoOther" class="profileDiv" style="display:<?php echo ($projectType == 'iplant'?'none':'block'); ?>">
													Server path or URL to source image location. Server paths should be absolute and writable to web server (e.g. apache).
													If a URL (e.g. http://) is supplied, the web server needs to be configured to publically list
													all files within the directory, or the html output can simply list all images within anchor tags.
													In all cases, scripts will attempt to crawl through all child directories.
												</div>
											</div>
										</div>
									</div>
									<div id="targetPathDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label><?php echo $LANG['IMG_TARGET_PATH']; ?>:</label>
										<div style="float:left;">
											<input name="targetpath" type="text" style="width:600px;" value="<?php echo ($specManager->getTargetPath()?$specManager->getTargetPath():$IMAGE_ROOT_PATH); ?>" />
											<a id="targetpathinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="targetpathinfodialog">
												<?php echo $LANG['TARGET_PATH_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="urlBaseDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label><?php echo $LANG['IMG_URL_BASE']; ?>:</label>
										<div style="float:left;">
											<input name="imgurl" type="text" style="width:600px;" value="<?php echo ($specManager->getImgUrlBase()?$specManager->getImgUrlBase():$IMAGE_ROOT_URL); ?>" />
											<a id="imgurlinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />

											</a>
											<div id="imgurlinfodialog">
												<?php echo $LANG['IMG_URL_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="centralWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label><?php echo $LANG['WEB_IMG_WIDTH']; ?>:</label>
										<div style="float:left;">
											<input name="webpixwidth" type="text" style="width:75px;" value="<?php echo ($specManager->getWebPixWidth()?$specManager->getWebPixWidth():$IMG_WEB_WIDTH); ?>" />
											<a id="webpixwidthinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />

											</a>
											<div id="webpixwidthinfodialog">
												<?php echo $LANG['WEB_IMG_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="thumbWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label><?php echo $LANG['TN_IMG_WIDTH']; ?>:</label>
										<div style="float:left;">
											<input name="tnpixwidth" type="text" style="width:75px;" value="<?php echo ($specManager->getTnPixWidth()?$specManager->getTnPixWidth():$IMG_TN_WIDTH); ?>" />
											<a id="tnpixwidthinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="tnpixwidthinfodialog">
												<?php echo $LANG['TN_IMG_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="largeWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label><?php echo $LANG['LG_IMG_WIDTH']; ?>:</label>
										<div style="float:left;">
											<input name="lgpixwidth" type="text" style="width:75px;" value="<?php echo ($specManager->getLgPixWidth()?$specManager->getLgPixWidth():$IMG_LG_WIDTH); ?>" />
											<a id="lgpixwidthinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="lgpixwidthinfodialog">
												<?php echo $LANG['LG_IMG_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="jpgQualityDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label><?php echo $LANG['JPG_QUALITY']; ?>:</label>
										<div style="float:left;">
											<input name="jpgcompression" type="text" style="width:75px;" value="<?php echo $specManager->getJpgQuality(); ?>" />
											<a id="jpgcompressioninfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a>
											<div id="jpgcompressioninfodialog">
												<?php echo $LANG['JPG_QUALITY_EXPLAIN']; ?>
											</div>
										</div>
									</div>
									<div id="thumbnailDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div>
											<b><?php echo $LANG['THUMBNAIL']; ?>:</b>
											<div style="margin:5px 15px;">
												<input name="createtnimg" type="radio" value="1" <?php echo ($specManager->getCreateTnImg()==1?'CHECKED':''); ?> /> <?php echo $LANG['CREATE_NEW_TN']; ?><br/>
												<input name="createtnimg" type="radio" value="2" <?php echo ($specManager->getCreateTnImg()==2?'CHECKED':''); ?> /> <?php echo $LANG['IMPORT_TN_SOURCE']; ?><br/>
												<input name="createtnimg" type="radio" value="3" <?php echo ($specManager->getCreateTnImg()==3?'CHECKED':''); ?> /> <?php echo $LANG['MAP_TN_AT_SOURCE']; ?><br/>
												<input name="createtnimg" type="radio" value="0" <?php echo (!$specManager->getCreateTnImg()?'CHECKED':''); ?> /> <?php echo $LANG['EXCLUDE_TN']; ?><br/>
											</div>
										</div>
									</div>
									<div id="largeImageDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div>
											<b><?php echo $LANG['LG_IMG']; ?>:</b>
											<div style="margin:5px 15px;">
												<input name="createlgimg" type="radio" value="1" <?php echo ($specManager->getCreateLgImg()==1?'CHECKED':''); ?> /> <?php echo $LANG['IMPORT_LG_SOURCE']; ?><br/>
												<input name="createlgimg" type="radio" value="2" <?php echo ($specManager->getCreateLgImg()==2?'CHECKED':''); ?> /> <?php echo $LANG['MAP_TO_LG_SOURCE']; ?><br/>
												<input name="createlgimg" type="radio" value="3" <?php echo ($specManager->getCreateLgImg()==3?'CHECKED':''); ?> /> <?php echo $LANG['IMPORT_LG_FROM_SOURCE']; ?><br/>
												<input name="createlgimg" type="radio" value="4" <?php echo ($specManager->getCreateLgImg()==4?'CHECKED':''); ?> /> <?php echo $LANG['MAP_LG_AT_SOURCE']; ?><br/>
												<input name="createlgimg" type="radio" value="0" <?php echo (!$specManager->getCreateLgImg()?'CHECKED':''); ?> /> <?php echo $LANG['EXCLUDE_LG']; ?><br/>
											</div>
										</div>
									</div>
									<div id="chooseFileDiv" class="profileDiv" style="clear:both;padding:15px 0px;display:none">
										<div style="margin:5px 15px;">
											<b><?php echo $LANG['SEL_URL_MAP_FILE']; ?>:</b>
											<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
											<button name='uploadfile' type='file' size='70' value="Choose File"><?php echo $LANG['CHOOSE_FILE']; ?></button>
										</div>
									</div>
									<div id="submitDiv" class="profileDiv" style="clear:both;padding:15px;display:<?php echo ($projectType?'block':'none'); ?>">
										<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="tabindex" type="hidden" value="0" />
										<button id="profileEditSubmit" name="submitaction" type="submit" value="Save Profile" ><?php echo $LANG['SAVE_PROFILE']; ?></button>
									</div>
								</fieldset>
							</form>
							<?php
							if($spprid){
								?>
								<form id="delform" action="index.php" method="post" onsubmit="return confirm('<?php echo $LANG['SURE_DELETE_PROF']; ?>')" >
									<fieldset style="padding:25px">
										<legend><b><?php echo $LANG['DELETE_PROJ']; ?></b></legend>
										<div>
											<input name="sppriddel" type="hidden" value="<?php echo $spprid; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="tabindex" type="hidden" value="0" />
											<button class="button-danger" name="submitaction" type="submit" value="Delete Profile" ><?php echo $LANG['DELETE_PROF']; ?></button>
										</div>
									</fieldset>
								</form>
								<?php
							}
							?>
						</div>
						<?php
						if($spprid){
							?>
							<div id="imgprocessdiv" style="position:relative;">
								<form name="imgprocessform" action="processor.php" method="post" enctype="multipart/form-data" onsubmit="return validateProcForm(this);">
									<fieldset style="padding:15px;">
										<legend><b><?php echo $specManager->getTitle(); ?></b></legend>
										<div style="float:right" title="<?php echo $LANG['SHOW_ALL_OR_ADD']; ?>">
											<a href="index.php?tabindex=0&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><img src="../../images/add.png" style="width:1.5em;border:0px" /></a>
										</div>
										<div style="float:right" title="<?php echo $LANG['OPEN_EDITOR']; ?>">
											<a href="#" onclick="toggle('editdiv');toggle('imgprocessdiv');return false;"><img src="../../images/edit.png" style="border:0px;width:1.5em;" /></a>
										</div>
										<?php
										if($projectType == 'iplant'){
											$lastRunDate = ($specManager->getLastRunDate()?$specManager->getLastRunDate() : $LANG['NO_RUN_DATE']);
											?>
											<div style="margin-top:10px">
												<label><?php echo $LANG['LAST_RUN_DATE']; ?>:</label>
												<div style="float:left;">
													<?php echo $lastRunDate; ?>
												</div>
											</div>
											<div style="margin-top:10px;clear:both;">
												<label><?php echo $LANG['PROC_START_DATE']; ?>:</label>
												<div style="float:left;">
													<input name="startdate" type="text" value="<?php echo ($lastRunDate == $LANG['NO_RUN_DATE'] ? '' : $lastRunDate); ?>" />
												</div>
											</div>
											<?php
										}
										?>
										<div style="margin-top:10px;clear:left;">
											<label><?php echo $LANG['PATT_MATCH_TERM']; ?>:</label>
											<div style="float:left;">
												<?php echo $specManager->getSpecKeyPattern(); ?>
												<input type='hidden' name='speckeypattern' value='<?php echo $specManager->getSpecKeyPattern();?>' />
											</div>
										</div>
										<div style="clear:both;">
											<label>Match term on:</label>
											<div style="float:left;">
												<input name="matchcatalognumber" type="checkbox" value="1" checked /> <?php echo $LANG['CAT_NUM']; ?>
												<input name="matchothercatalognumbers" type="checkbox" value="1" style="margin-left:30px;" /> <?php echo $LANG['OTHER_CAT_NUMS']; ?>
											</div>
										</div>
										<div style="margin-top:10px;clear:both;">
											<label><?php echo $LANG['REPLACEMENT_TERM']; ?>:</label>
											<div style="float:left;">
												<?php echo $specManager->getPatternReplace(); ?>
												<input type='hidden' name='patternreplace' value='<?php echo $specManager->getPatternReplace();?>' />
											</div>
										</div>
										<div style="margin-top:10px;clear:both;">
											<label><?php echo $LANG['REPLACEMENT_STR']; ?>:</label>
											<div style="float:left;">
												<?php
												echo str_replace(' ', '&lt;space&gt;', $specManager->getReplaceStr());
												?>
												<input type='hidden' name='replacestr' value='<?php echo $specManager->getReplaceStr(); ?>' />
											</div>
										</div>
										<?php
										if($projectType != 'idigbio'){
											?>
											<div style="clear:both;">
												<label><?php echo $LANG['SOURCE_PATH']; ?>:</label>
												<div style="float:left;">
													<?php
													echo '<input name="sourcepath" type="hidden" value="'.$specManager->getSourcePathDefault().'" />';
													echo $specManager->getSourcePathDefault();
													?>
												</div>
											</div>
											<?php
										}
										if($projectType != 'idigbio' && $projectType != 'iplant'){
											?>
											<div style="clear:both;">
												<label><?php echo $LANG['TARGET_FOLDER']; ?>:</label>
												<div style="float:left;">
													<?php echo ($specManager->getTargetPath()?$specManager->getTargetPath():$IMAGE_ROOT_PATH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label><?php echo $LANG['URL_PREFIX']; ?>:</label>
												<div style="float:left;">
													<?php echo ($specManager->getImgUrlBase()?$specManager->getImgUrlBase():$IMAGE_ROOT_URL); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label><?php echo $LANG['WEB_IMG_WIDTH']; ?>:</label>
												<div style="float:left;">
													<?php echo ($specManager->getWebPixWidth()?$specManager->getWebPixWidth():$IMG_WEB_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label><?php echo $LANG['TN_IMG_WIDTH']; ?>:</label>
												<div style="float:left;">
													<?php echo ($specManager->getTnPixWidth()?$specManager->getTnPixWidth():$IMG_TN_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label><?php echo $LANG['LG_IMG_WIDTH']; ?>:</label>
												<div style="float:left;">
													<?php echo ($specManager->getLgPixWidth()?$specManager->getLgPixWidth():$IMG_LG_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label><?php echo $LANG['JPG_QUALITY']; ?>: </label>
												<div style="float:left;">
													<?php echo ($specManager->getJpgQuality()?$specManager->getJpgQuality():80); ?>
												</div>
											</div>
											<div style="clear:both;padding-top:10px;">
												<div>
													<b><?php echo $LANG['WEB_IMG']; ?>:</b>
													<div style="margin:5px 15px">
														<input name="webimg" type="radio" value="1" CHECKED /> <?php echo $LANG['EVALUATE_IMPORT_SOURCE']; ?><br/>
														<input name="webimg" type="radio" value="2" /> <?php echo $LANG['IMPORT_WITHOUT_RESIZE']; ?><br/>
														<input name="webimg" type="radio" value="3" /> <?php echo $LANG['MAP_SOURCE_NO_IMPORT']; ?><br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b><?php echo $LANG['THUMBNAIL']; ?>:</b>
													<div style="margin:5px 15px">
														<input name="createtnimg" type="radio" value="1" <?php echo ($specManager->getCreateTnImg() == 1?'CHECKED':'') ?> /> <?php echo $LANG['CREATE_NEW_TN']; ?><br/>
														<input name="createtnimg" type="radio" value="2" <?php echo ($specManager->getCreateTnImg() == 2?'CHECKED':'') ?> /> <?php echo $LANG['IMPORT_TN_SOURCE']; ?><br/>
														<input name="createtnimg" type="radio" value="3" <?php echo ($specManager->getCreateTnImg() == 3?'CHECKED':'') ?> /> <?php echo $LANG['MAP_TN_AT_SOURCE'] ; ?><br/>
														<input name="createtnimg" type="radio" value="0" <?php echo (!$specManager->getCreateTnImg()?'CHECKED':'') ?> /> <?php echo $LANG['EXCLUDE_TN']; ?><br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b><?php echo $LANG['LG_IMG']; ?>:</b>
													<div style="margin:5px 15px">
														<input name="createlgimg" type="radio" value="1" <?php echo ($specManager->getCreateLgImg() == 1?'CHECKED':'') ?> /> <?php echo $LANG['IMPORT_LG_SOURCE']; ?><br/>
														<input name="createlgimg" type="radio" value="2" <?php echo ($specManager->getCreateLgImg() == 2?'CHECKED':'') ?> /> <?php echo $LANG['MAP_TO_LG_SOURCE']; ?><br/>
														<input name="createlgimg" type="radio" value="3" <?php echo ($specManager->getCreateLgImg() == 3?'CHECKED':'') ?> /> <?php echo $LANG['IMPORT_LG_FROM_SOURCE']; ?><br/>
														<input name="createlgimg" type="radio" value="4" <?php echo ($specManager->getCreateLgImg() == 4?'CHECKED':'') ?> /> <?php echo $LANG['MAP_LG_AT_SOURCE']; ?><br/>
														<input name="createlgimg" type="radio" value="0" <?php echo (!$specManager->getCreateLgImg()?'CHECKED':'') ?> /> <?php echo $LANG['EXCLUDE_LG']; ?><br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div title="<?php echo $LANG['UNABLE_MATCH_ID']; ?>">
													<b><?php echo $LANG['MISSING_RECORD']; ?>:</b>
													<div style="margin:5px 15px">
														<input type="radio" name="createnewrec" value="0" />
														<?php echo $LANG['SKIP_AND_NEXT']; ?><br/>
														<input type="radio" name="createnewrec" value="1" CHECKED />
														<?php echo $LANG['CREATE_AND_LINK']; ?>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div title="Image with exact same name already exists">
													<b><?php echo $LANG['IMG_EXISTS']; ?>:</b>
													<div style="margin:5px 15px">
														<input type="radio" name="imgexists" value="0" CHECKED />
														<?php echo $LANG['SKIP_IMPORT']; ?><br/>
														<input type="radio" name="imgexists" value="1" />
														<?php echo $LANG['RENAME_SAVE_BOTH']; ?><br/>
														<input type="radio" name="imgexists" value="2" />
														<?php echo $LANG['REPLACE_EXISTING']; ?>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b><?php echo $LANG['LOOK_FOR_SKELETAL']; ?>:</b>
													<div style="margin:5px 15px">
														<input type="radio" name="skeletalFileProcessing" value="0" CHECKED />
														<?php echo $LANG['SKIP_SKELETAL']; ?><br/>
														<input type="radio" name="skeletalFileProcessing" value="1" />
														<?php echo $LANG['PROCESS_SKELETAL']; ?><br/>
													</div>
												</div>
											</div>
											<?php
										}
										?>
										<div style="clear:both;padding:20px;">
											<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="projtype" type="hidden" value="<?php echo $projectType; ?>" />
											<input name="tabindex" type="hidden" value="0" />
											<input name="submitaction" type="submit" value="Process <?php echo ($projectType=='idigbio'?'Output File':'Images') ?>" />
										</div>
									</fieldset>
								</form>
							</div>
							<?php
						}
					}
				}
				else{
					echo '<div>' . $LANG['COLLID_NOT_DEFINED'] . '</div>';
				}
				?>
				<div id="tmp" class="top-breathing-room-rel">
					<fieldset style="padding:15px;">
						<legend><b><?= $LANG['LOG_FILES']; ?></b></legend>
						<?php
						$logArr = $specManager->getLogListing();
						if($logArr){
							$logPath = '../../content/logs/';
							foreach($logArr as $logCat => $logList){
								echo '<div style="font-weight:bold;margin: 10px 0px 5px 0px">';
								if($logCat=='imgProccessing') echo $LANG['GEN_PROCESSING'];
								elseif($logCat=='iplant') echo $LANG['IPLANT'];
								elseif($logCat=='cyverse') echo $LANG['CYVERSE'];
								elseif($logCat=='processing/imgmap') echo $LANG['IMG_MAP_FILE'];
								echo '</div><div style="margin:5px 0px 15px 10px">';
								foreach($logList as $logFile){
									echo '<div><a href="' . $logPath . $logCat . '/' . $logFile . '" target="_blank">' . $logFile . '</a></div>';
								}
								echo '</div>';
							}
						}
						else echo '<div>' . $LANG['NO_LOGS'] . '</div>';
						?>
					</fieldset>
				</div>
				<?php
			}
			?>
		</div>
	</body>
</html>
