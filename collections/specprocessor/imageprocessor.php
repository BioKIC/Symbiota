<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/ImageProcessor.php');

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
<html>
	<head>
		<title>Image Processor</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<style type="text/css">.profileDiv{ clear:both; margin:2px 0px } </style>
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
		<link href="../../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
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
					alert("Image Mapping/Import type must be selected");
					return false;
				}
				if(f.projecttype.value == 'file'){
					var fileName = f.uploadfile.value;
					var fileExt = fileName.split('.').pop().toLowerCase();
					if(fileName == ""){
						alert("Select a CSV file to upload");
						return false;
					}
					else if(fileExt != "csv" && fileExt != "zip"){
						alert("Input file must be a CSV spreadsheet (comma or tab delimited), or ZIP file containing a CSV file");
						return false;
					}
				}
				else{
					if(f.speckeypattern.value == ""){
						alert("Pattern matching term must have a value");
						return false;
					}
					if(f.speckeypattern.value.substr(f.speckeypattern.value.length-3).toLowerCase() != "csv"){
						if(f.speckeypattern.value.indexOf("(") < 0 || f.speckeypattern.value.indexOf(")") < 0){
							alert("Catalog portion of pattern matching term must be enclosed in parenthesis");
							return false;
						}
					}
				}
				if(f.projecttype.value == 'local'){
					if(!isNumeric(f.webpixwidth.value)){
						alert("Central image pixel width can only be a numeric value");
						return false;
					}
					else if(!isNumeric(f.tnpixwidth.value)){
						alert("Thumbnail pixel width can only be a numeric value");
						return false;
					}
					else if(!isNumeric(f.lgpixwidth.value)){
						alert("Large image pixel width can only be a numeric value");
						return false;
					}
					else if(f.title.value == ""){
						alert("Title cannot be empty");
						return false;
					}
					else if(!isNumeric(f.jpgcompression.value) || f.jpgcompression.value < 30 || f.jpgcompression.value > 100){
						alert("JPG compression needs to be a numeric value between 30 and 100");
						return false;
					}
				}
				if(f.patternreplace.value == "-- Optional --") f.patternreplace.value = "";
				if(f.replacestr.value == "-- Optional --") f.replacestr.value = "";
				if(f.sourcepath.value == "-- Use Default Path --") f.sourcepath.value = "";
				return true;
			}

			function validateProcForm(f){
				if(f.projtype.value == 'iplant'){
					var regexObj = /^\d{4}-\d{2}-\d{2}$/;
					var startDate = f.startdate.value;
					if(startDate != "" && !regexObj.test(startDate)){
						alert("Processing Start Date needs to be in the format YYYY-MM-DD (e.g. 2015-10-18)");
						return false;
					}
				}
				if(f.matchcatalognumber.checked == false && f.matchothercatalognumbers.checked == false){
					alert("At least one of the Match Term checkboxes need to be checked");
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
								alert("ERROR: Target field names must be unique (duplicate field: "+obj.value+")");
								return false;
							}
							tfArr[tfArr.length] = obj.value;
						}
					}
					if(obj.name.indexOf("sf[") == 0){
						if(obj.value){
							if(sfArr.indexOf(obj.value) > -1){
								alert("ERROR: Source field names must be unique (duplicate field: "+obj.value+")");
								return false;
							}
							sfArr[sfArr.length] = obj.value;
						}
					}
				}
				if(tfArr.indexOf("catalognumber") < 0 && tfArr.indexOf("othercatalognumbers") < 0){
					alert("Catalog Number or Other Catalog Numbers must be mapped to an import field");
					return false;
				}
				if(tfArr.indexOf("originalurl") < 0){
					alert("Large Image URL must both be mapped to an import field");
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
		<div id="innertext" style="background-color:white;">
			<div style="padding:15px;">
				These tools are designed to aid collection managers in batch processing specimen images. Contact portal manager for help in setting up a new workflow.
				 Once a profile is established, the collection manager can use this form to manually trigger image processing. For more information, see the Symbiota documentation for
				 <b><a href="https://biokic.github.io/symbiota-docs/coll_manager/images/batch/" target="_blank">recommended practices</a></b> for integrating images.
			</div>
			<?php
			if($SYMB_UID){
				if($collid){
					if($fileName){
						?>
						<form name="filemappingform" action="processor.php" method="post" onsubmit="return validateFileUploadForm(this)">
							<fieldset>
								<legend><b>Image File Upload Map</b></legend>
								<div style="margin:15px;">
									<table class="styledtable" style="width:600px;font-family:Arial;font-size:12px;">
										<tr><th>Source Field</th><th>Target Field</th></tr>
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
											echo '<select name="tf['.$i.']" style="background:'.(!array_key_exists($sourceField,$translationMap)?'yellow':'').'">';
											echo '<option value="">Select Target Field</option>';
											echo '<option value="">-------------------------</option>';
											echo '<option value="catalognumber" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='catalognumber'?'SELECTED':'').'>Catalog Number</option>';
											echo '<option value="othercatalognumbers" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='othercatalognumbers'?'SELECTED':'').'>Other Catalog Numbers</option>';
											echo '<option value="originalurl" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='originalurl'?'SELECTED':'').'>Large Image URL (required)</option>';
											echo '<option value="url" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='url'?'SELECTED':'').'>Web Image URL</option>';
											echo '<option value="thumbnailurl" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='thumbnailurl'?'SELECTED':'').'>Thumbnail URL</option>';
											echo '<option value="sourceurl" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='sourceurl'?'SELECTED':'').'>Source URL</option>';
											echo '</select>';
											echo '</td></tr>';
										}
										?>
									</table>
								</div>
								<div style="margin:15px;">
									<input name="createnew" type="checkbox" value ="1" /> Link image to new blank record if catalog number does not exist
								</div>
								<div style="margin:15px;">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="tabindex" type="hidden" value="0" />
									<input name="filename" type="hidden" value="<?php echo $fileName; ?>" />
									<button name="submitaction" type="submit" value="mapImageFile">Map Images</button>
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
										<legend><b>Saved Image Processing Profiles</b></legend>
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
									<legend><b><?php echo ($spprid?'Edit':'New'); ?> Profile</b></legend>
									<?php
									if($spprid){
										?>
										<div style="float:right;" onclick="toggle('editdiv');toggle('imgprocessdiv')" title="Close Editor">
											<img src="../../images/edit.png" style="border:0px" />
										</div>
										<input name="projecttype" type="hidden" value="<?php echo $projectType; ?>" />
										<?php
									}
									else{
										?>
										<div>
											<label>Processing Type:</label>
											<div style="float:left;">
												<select name="projecttype" id="projecttype" onchange="uploadTypeChanged(this.form)" <?php echo ($spprid?'DISABLED':'');?>>
													<option value="">----------------------</option>
													<option value="local">Map Images from a Local or Remote Server</option>
													<option value="file">Image URL Mapping File</option>
													<option value="iplant">iPlant Image Harvest</option>
												</select>
											</div>
										</div>
										<?php
									}
									?>
									<div id="titleDiv" style="display:<?php echo ($spprid?'block':'none'); ?>;clear:left;">
										<label>Title:</label>
										<div style="float:left;">
											<input name="title" type="text" style="width:300px;" value="<?php echo $specManager->getTitle(); ?>" />
										</div>
									</div>
									<div id="specKeyPatternDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<label>Pattern match term:</label>
										<div style="float:left;">
											<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $specManager->getSpecKeyPattern(); ?>" />
											<a id="speckeypatterninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="speckeypatterninfodialog">
												Regular expression needed to extract the unique identifier from source text.
												For example, regular expression /^(WIS-L-\d{7})\D*/ will extract catalog number WIS-L-0001234
												from image file named WIS-L-0001234_a.jpg. For more information on creating regular expressions,
												Google &quot;Regular Expression PHP Tutorial&quot;
											</div>
										</div>
									</div>
									<div id="patternReplaceDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<label>Replacement term:</label>
										<div style="float:left;">
											<input name="patternreplace" type="text" style="width:300px;" value="<?php echo ($specManager->getPatternReplace()?$specManager->getPatternReplace():'-- Optional --'); ?>" />
											<a id="patternreplaceinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="patternreplaceinfodialog">
												Optional regular expression for match on Catalog Number to be replaced with replacement term.
												Example 1: expression replace term = '/^/' combined with replace string = 'barcode-' will convert 0001234 => barcode-0001234.
												Example 2: expression replace term = '/XYZ-/' combined with empty replace string will convert XYZ-0001234 => 0001234.
											</div>
										</div>
									</div>
									<div id="replaceStrDiv" class="profileDiv" style="display:<?php echo ($projectType?'block':'none'); ?>">
										<label>Replacement string:</label>
										<div style="float:left;">
											<input name="replacestr" type="text" style="width:300px;" value="<?php echo ($specManager->getReplaceStr()?$specManager->getReplaceStr():'-- Optional --'); ?>" />
											<a id="replacestrinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="replacestrinfodialog">
												Optional replacement string to apply for Expression replacement term matches on catalogNumber.
											</div>
										</div>
									</div>
									<div id="sourcePathDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'||$projectType=='iplant'?'block':'none'); ?>">
										<label>Image source path:</label>
										<div style="float:left;">
											<input name="sourcepath" type="text" style="width:600px;" value="<?php echo $specManager->getSourcePath(); ?>" />
											<a id="sourcepathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
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
										<label>Image target path:</label>
										<div style="float:left;">
											<input name="targetpath" type="text" style="width:600px;" value="<?php echo ($specManager->getTargetPath()?$specManager->getTargetPath():$IMAGE_ROOT_PATH); ?>" />
											<a id="targetpathinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="targetpathinfodialog">
												Web server path to where the image derivatives will be depositied.
												The web server (e.g. apache user) must have read/write access to this directory.
												If this field is left blank, the portal's default image target (imageRootPath) will be used.
											</div>
										</div>
									</div>
									<div id="urlBaseDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label>Image URL base:</label>
										<div style="float:left;">
											<input name="imgurl" type="text" style="width:600px;" value="<?php echo ($specManager->getImgUrlBase()?$specManager->getImgUrlBase():$IMAGE_ROOT_URL); ?>" />
											<a id="imgurlinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="imgurlinfodialog">
												Image URL prefix that will access the target folder from the browser.
												This will be used to create the image URLs that will be stored in the database.
												If absolute URL is supplied without the domain name, the portal domain will be assumed.
												If this field is left blank, the portal's default image url will be used ($imageRootUrl).
											</div>
										</div>
									</div>
									<div id="centralWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label>Central pixel width:</label>
										<div style="float:left;">
											<input name="webpixwidth" type="text" style="width:75px;" value="<?php echo ($specManager->getWebPixWidth()?$specManager->getWebPixWidth():$IMG_WEB_WIDTH); ?>" />
											<a id="webpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="webpixwidthinfodialog">
												Width of the standard web image.
												If the source image is smaller than this width, the file will simply be copied over without resizing.
											</div>
										</div>
									</div>
									<div id="thumbWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label>Thumbnail pixel width:</label>
										<div style="float:left;">
											<input name="tnpixwidth" type="text" style="width:75px;" value="<?php echo ($specManager->getTnPixWidth()?$specManager->getTnPixWidth():$IMG_TN_WIDTH); ?>" />
											<a id="tnpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="tnpixwidthinfodialog">
												Width of the image thumbnail. Width should be greater than image sizing within the thumbnail display pages.
											</div>
										</div>
									</div>
									<div id="largeWidthDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label>Large pixel width:</label>
										<div style="float:left;">
											<input name="lgpixwidth" type="text" style="width:75px;" value="<?php echo ($specManager->getLgPixWidth()?$specManager->getLgPixWidth():$IMG_LG_WIDTH); ?>" />
											<a id="lgpixwidthinfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="lgpixwidthinfodialog">
												Width of the large version of the image.
												If the source image is smaller than this width, the file will simply be copied over without resizing.
												Note that resizing large images may be limited by the PHP configuration settings (e.g. memory_limit).
												If this is a problem, having this value greater than the maximum width of your source images will avoid
												errors related to resampling large images.
											</div>
										</div>
									</div>
									<div id="jpgQualityDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<label>JPG quality:</label>
										<div style="float:left;">
											<input name="jpgcompression" type="text" style="width:75px;" value="<?php echo $specManager->getJpgQuality(); ?>" />
											<a id="jpgcompressioninfo" href="#" onclick="return false" title="More Information">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="jpgcompressioninfodialog">
												JPG quality refers to amount of compression applied.
												Value should be numeric and range from 0 (worst quality, smaller file) to
												100 (best quality, biggest file).
												If null, 75 is used as the default.
											</div>
										</div>
									</div>
									<div id="thumbnailDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div>
											<b>Thumbnail:</b>
											<div style="margin:5px 15px;">
												<input name="createtnimg" type="radio" value="1" <?php echo ($specManager->getCreateTnImg()==1?'CHECKED':''); ?> /> Create new thumbnail from source image<br/>
												<input name="createtnimg" type="radio" value="2" <?php echo ($specManager->getCreateTnImg()==2?'CHECKED':''); ?> /> Import thumbnail from source location (source name with _tn.jpg suffix)<br/>
												<input name="createtnimg" type="radio" value="3" <?php echo ($specManager->getCreateTnImg()==3?'CHECKED':''); ?> /> Map to thumbnail at source location (source name with _tn.jpg suffix)<br/>
												<input name="createtnimg" type="radio" value="0" <?php echo (!$specManager->getCreateTnImg()?'CHECKED':''); ?> /> Exclude thumbnail <br/>
											</div>
										</div>
									</div>
									<div id="largeImageDiv" class="profileDiv" style="display:<?php echo ($projectType=='local'?'block':'none'); ?>">
										<div>
											<b>Large Image:</b>
											<div style="margin:5px 15px;">
												<input name="createlgimg" type="radio" value="1" <?php echo ($specManager->getCreateLgImg()==1?'CHECKED':''); ?> /> Import source image as large version<br/>
												<input name="createlgimg" type="radio" value="2" <?php echo ($specManager->getCreateLgImg()==2?'CHECKED':''); ?> /> Map to source image as large version<br/>
												<input name="createlgimg" type="radio" value="3" <?php echo ($specManager->getCreateLgImg()==3?'CHECKED':''); ?> /> Import large version from source location (source name with _lg.jpg suffix)<br/>
												<input name="createlgimg" type="radio" value="4" <?php echo ($specManager->getCreateLgImg()==4?'CHECKED':''); ?> /> Map to large version at source location (source name with _lg.jpg suffix)<br/>
												<input name="createlgimg" type="radio" value="0" <?php echo (!$specManager->getCreateLgImg()?'CHECKED':''); ?> /> Exclude large version<br/>
											</div>
										</div>
									</div>
									<div id="chooseFileDiv" class="profileDiv" style="clear:both;padding:15px 0px;display:none">
										<div style="margin:5px 15px;">
											<b>Select URL mapping file:</b>
											<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
											<input name='uploadfile' type='file' size='70' value="Choose File" />
										</div>
									</div>
									<div id="submitDiv" class="profileDiv" style="clear:both;padding:15px;display:<?php echo ($projectType?'block':'none'); ?>">
										<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="tabindex" type="hidden" value="0" />
										<input id="profileEditSubmit" name="submitaction" type="submit" value="Save Profile" />
									</div>
								</fieldset>
							</form>
							<?php
							if($spprid){
								?>
								<form id="delform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this image processing profile?')" >
									<fieldset style="padding:25px">
										<legend><b>Delete Project</b></legend>
										<div>
											<input name="sppriddel" type="hidden" value="<?php echo $spprid; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="tabindex" type="hidden" value="0" />
											<input name="submitaction" type="submit" value="Delete Profile" />
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
										<div style="float:right" title="Show all saved profiles or add a new one...">
											<a href="index.php?tabindex=0&collid=<?php echo $collid; ?>"><img src="../../images/add.png" style="border:0px" /></a>
										</div>
										<div style="float:right" title="Open Editor">
											<a href="#" onclick="toggle('editdiv');toggle('imgprocessdiv');return false;"><img src="../../images/edit.png" style="border:0px;width:15px;" /></a>
										</div>
										<?php
										if($projectType == 'iplant'){
											$lastRunDate = ($specManager->getLastRunDate()?$specManager->getLastRunDate():'no run date');
											?>
											<div style="margin-top:10px">
												<label>Last Run Date:</label>
												<div style="float:left;">
													<?php echo $lastRunDate; ?>
												</div>
											</div>
											<div style="margin-top:10px;clear:both;">
												<label>Processing start date:</label>
												<div style="float:left;">
													<input name="startdate" type="text" value="<?php echo ($lastRunDate=='no run date'?'':$lastRunDate); ?>" />
												</div>
											</div>
											<?php
										}
										?>
										<div style="margin-top:10px;clear:left;">
											<label>Pattern match term:</label>
											<div style="float:left;">
												<?php echo $specManager->getSpecKeyPattern(); ?>
												<input type='hidden' name='speckeypattern' value='<?php echo $specManager->getSpecKeyPattern();?>' />
											</div>
										</div>
										<div style="clear:both;">
											<label>Match term on:</label>
											<div style="float:left;">
												<input name="matchcatalognumber" type="checkbox" value="1" checked /> Catalog Number
												<input name="matchothercatalognumbers" type="checkbox" value="1" style="margin-left:30px;" /> Other Catalog Numbers
											</div>
										</div>
										<div style="margin-top:10px;clear:both;">
											<label>Replacement term:</label>
											<div style="float:left;">
												<?php echo $specManager->getPatternReplace(); ?>
												<input type='hidden' name='patternreplace' value='<?php echo $specManager->getPatternReplace();?>' />
											</div>
										</div>
										<div style="margin-top:10px;clear:both;">
											<label>Replacement string:</label>
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
												<label>Source path:</label>
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
												<label>Target folder:</label>
												<div style="float:left;">
													<?php echo ($specManager->getTargetPath()?$specManager->getTargetPath():$IMAGE_ROOT_PATH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label>URL prefix:</label>
												<div style="float:left;">
													<?php echo ($specManager->getImgUrlBase()?$specManager->getImgUrlBase():$IMAGE_ROOT_URL); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label>Web image width:</label>
												<div style="float:left;">
													<?php echo ($specManager->getWebPixWidth()?$specManager->getWebPixWidth():$IMG_WEB_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label>Thumbnail width:</label>
												<div style="float:left;">
													<?php echo ($specManager->getTnPixWidth()?$specManager->getTnPixWidth():$IMG_TN_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label>Large image width:</label>
												<div style="float:left;">
													<?php echo ($specManager->getLgPixWidth()?$specManager->getLgPixWidth():$IMG_LG_WIDTH); ?>
												</div>
											</div>
											<div style="clear:both;">
												<label>JPG quality (1-100): </label>
												<div style="float:left;">
													<?php echo ($specManager->getJpgQuality()?$specManager->getJpgQuality():80); ?>
												</div>
											</div>
											<div style="clear:both;padding-top:10px;">
												<div>
													<b>Web Image:</b>
													<div style="margin:5px 15px">
														<input name="webimg" type="radio" value="1" CHECKED /> Evaluate and import source image<br/>
														<input name="webimg" type="radio" value="2" /> Import source image as is without resizing<br/>
														<input name="webimg" type="radio" value="3" /> Map to source image without importing<br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b>Thumbnail:</b>
													<div style="margin:5px 15px">
														<input name="createtnimg" type="radio" value="1" <?php echo ($specManager->getCreateTnImg() == 1?'CHECKED':'') ?> /> Create new from source image<br/>
														<input name="createtnimg" type="radio" value="2" <?php echo ($specManager->getCreateTnImg() == 2?'CHECKED':'') ?> /> Import existing source thumbnail (source name with _tn.jpg suffix)<br/>
														<input name="createtnimg" type="radio" value="3" <?php echo ($specManager->getCreateTnImg() == 3?'CHECKED':'') ?> /> Map to existing source thumbnail (source name with _tn.jpg suffix)<br/>
														<input name="createtnimg" type="radio" value="0" <?php echo (!$specManager->getCreateTnImg()?'CHECKED':'') ?> /> Exclude thumbnail <br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b>Large Image:</b>
													<div style="margin:5px 15px">
														<input name="createlgimg" type="radio" value="1" <?php echo ($specManager->getCreateLgImg() == 1?'CHECKED':'') ?> /> Import source image as large version<br/>
														<input name="createlgimg" type="radio" value="2" <?php echo ($specManager->getCreateLgImg() == 2?'CHECKED':'') ?> /> Map to source image as large version<br/>
														<input name="createlgimg" type="radio" value="3" <?php echo ($specManager->getCreateLgImg() == 3?'CHECKED':'') ?> /> Import existing large version (source name with _lg.jpg suffix)<br/>
														<input name="createlgimg" type="radio" value="4" <?php echo ($specManager->getCreateLgImg() == 4?'CHECKED':'') ?> /> Map to existing large version (source name with _lg.jpg suffix)<br/>
														<input name="createlgimg" type="radio" value="0" <?php echo (!$specManager->getCreateLgImg()?'CHECKED':'') ?> /> Exclude large version<br/>
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div title="Unable to match primary identifer with an existing database record">
													<b>Missing record:</b>
													<div style="margin:5px 15px">
														<input type="radio" name="createnewrec" value="0" />
														Skip image import and go to next<br/>
														<input type="radio" name="createnewrec" value="1" CHECKED />
														Create empty record and link image
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div title="Image with exact same name already exists">
													<b>Image already exists:</b>
													<div style="margin:5px 15px">
														<input type="radio" name="imgexists" value="0" CHECKED />
														Skip import<br/>
														<input type="radio" name="imgexists" value="1" />
														Rename image and save both<br/>
														<input type="radio" name="imgexists" value="2" />
														Replace existing image
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div>
													<b>Look for and process skeletal files (allowed extensions: csv, txt, tab, dat):</b>
													<div style="margin:5px 15px">
														<input type="radio" name="skeletalFileProcessing" value="0" CHECKED />
														Skip skeletal files<br/>
														<input type="radio" name="skeletalFileProcessing" value="1" />
														Process skeletal files<br/>
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
					echo '<div>ERROR: collection identifier not defined. Contact administrator</div>';
				}
				?>
				<div style="margin:20px;">
					<fieldset style="padding:15px;">
						<legend><b>Log Files</b></legend>
						<?php
						$logArr = $specManager->getLogListing();
						if($logArr){
							$logPath = '../../content/logs/';
							foreach($logArr as $logCat => $logList){
								echo '<div style="font-weight:bold;margin: 10px 0px 5px 0px">';
								if($logCat=='imgProccessing') echo 'General Processing';
								elseif($logCat=='iplant') echo 'iPlant (pre-CyVerse)';
								elseif($logCat=='cyverse') echo 'CyVerse';
								elseif($logCat=='processing/imgmap') echo 'Image Mapping File';
								echo '</div><div style="margin:5px 0px 15px 10px">';
								foreach($logList as $logFile){
									echo '<div><a href="'.$logPath.$logCat.'/'.$logFile.'" target="_blank">'.$logFile.'</a></div>';
								}
								echo '</div>';
							}
						}
						else echo '<div>No logs exist for this collection</div>';
						?>
					</fieldset>
				</div>
				<?php
			}
			?>
		</div>
	</body>
</html>
