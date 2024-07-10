<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
include_once($SERVER_ROOT.'/content/lang/collections/download/index.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

$sourcePage = array_key_exists('sourcepage', $_REQUEST) ? $_REQUEST['sourcepage'] : 'specimen';
$downloadType = array_key_exists('dltype', $_REQUEST) ? $_REQUEST['dltype'] : 'specimen';
$taxonFilterCode = array_key_exists('taxonFilterCode', $_REQUEST) ? filter_var($_REQUEST['taxonFilterCode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$displayHeader = array_key_exists('displayheader', $_REQUEST) ? filter_var($_REQUEST['displayheader'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchVar = array_key_exists('searchvar', $_REQUEST) ? htmlspecialchars($_REQUEST['searchvar'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE| ENT_QUOTES) : '';

$dwcManager = new DwcArchiverCore();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title> <?php echo (isset($LANG['COLL_SEARCH_DWNL']) ? $LANG['COLL_SEARCH_DWNL'] : 'Collections Search Download'); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script>
		$(document).ready(function() {
			var dialogArr = new Array("schemanative","schemadwc");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "left top", at: "center", of: "#"+dialogStr }
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

			<?php
			if(!$searchVar){
				?>
				if(sessionStorage.querystr){
					window.location = "index.php?"+sessionStorage.querystr;
				}
				<?php
			}
			?>
		});

		function extensionSelected(obj){
			if(obj.checked == true){
				obj.form.zip.checked = true;
			}
		}

		function zipSelected(obj){
			if(obj.checked == false){
				obj.form.images.checked = false;
				obj.form.identifications.checked = false;
				if(obj.form.attributes) obj.form.attributes.checked = false;
				if(obj.form.materialsample) obj.form.materialsample.checked = false;
			}
		}

		function validateDownloadForm(f){
			workingcircle
			document.getElementById("workingcircle").style.display = "inline";
			return true;
		}

		function closePage(timeToClose){
			setTimeout(function () {
				window.close();
			}, timeToClose);
		}
	</script>
	<style>
		fieldset{ margin:10px; padding:10px }
		legend{ font-weight:bold }
		.sectionDiv{ clear:both; margin:20px; overflow:auto; }
		.labelDiv{ float:left; font-weight:bold; width:200px }
		.formElemDiv{ float:left }
	</style>
</head>
<body style="width:700px;min-width:700px;margin-left:auto;margin-right:auto;background-color:#ffffff">
	<?php
	if($displayHeader){
		$displayLeftMenu = (isset($collections_download_downloadMenu) ? $collections_download_downloadMenu:false);
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php"> <?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'); ?> </a> &gt;&gt;
			<a href="#" onclick="closePage(0)"> <?php echo (isset($LANG['RETURN']) ? $LANG['RETURN'] : 'Return to Search Page'); ?> </a> &gt;&gt;
			<b> <?php echo (isset($LANG['OCC_DOWNLOAD']) ? $LANG['OCC_DOWNLOAD'] : 'Occurrence Record Download'); ?> </b>
		</div>
		<?php
	}
	?>
	<div style="width:100%; background-color:white;">
		<h1 class="page-heading"><?php echo (isset($LANG['DATA_GUIDE']) ? $LANG['DATA_GUIDE'] : 'Data Usage Guidelines'); ?></h1>
		<div style="margin:15px 0px;">
		<?php echo (isset($LANG['GUIDE_ONE']) ? $LANG['GUIDE_ONE'] : 'By downloading data, the user confirms that he/she has read and agrees with the general'); ?> <a href="../../includes/usagepolicy.php#images"> <?php echo (isset($LANG['GUIDE_LINK']) ? $LANG['GUIDE_LINK'] : 'data usage terms'); ?> </a>.
			<?php echo (isset($LANG['GUIDE_TWO']) ? $LANG['GUIDE_TWO'] : 'Note that additional terms of use specific to the individual collections may be distributed with the data download. When present, the terms
			supplied by the owning institution should take precedence over the general terms posted on the website.'); ?>
		</div>
		<div style='margin:30px 15px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<legend>
						<?php
						if($downloadType == 'checklist') echo (isset($LANG['DOWNLOAD_CHECKL']) ? $LANG['DOWNLOAD_CHECKL'] : 'Download Checklist');
						elseif($downloadType == 'georef') echo (isset($LANG['DOWNLOAD_GEO_DATA']) ? $LANG['DOWNLOAD_GEO_DATA'] : 'Download Georeference Data');
						else echo (isset($LANG['DOWNLOAD_SPEC_REC']) ? $LANG['DOWNLOAD_SPEC_REC'] : 'Download Specimen Records');
						?>
					</legend>
					<?php
					if($downloadType == 'specimen'){
						?>
						<fieldset class="sectionDiv">
							<legend>  <?php echo (isset($LANG['STRUCTURE']) ? $LANG['STRUCTURE'] : 'Structure'); ?>:</legend>
							<div class="formElemDiv">
								<input type="radio" name="schema" id="symbiota-native" value="symbiota" onclick="georefRadioClicked(this)" CHECKED />
								<label for="symbiota-native">  <?php echo (isset($LANG['SYMB_NATIVE']) ? $LANG['SYMB_NATIVE'] : 'Symbiota Native'); ?>  </label>
								<a id="schemanativeinfo" aria-label="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'); ?>" href="#" onclick="return false" title="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'); ?>">
									<img src="../../images/info.png" alt=" <?php echo (isset($LANG['IMG_NATIVE_INFO']) ? $LANG['IMG_NATIVE_INFO'] : 'Info icon clarifying that Symbiota native is similar to Darwin Core plus some fields'); ?> " style="width:1.2em;" />
								</a><br/>
								<div id="schemanativeinfodialog">
									<?php echo (isset($LANG['SYMB_NATIVE_INFO']) ? $LANG['SYMB_NATIVE_INFO'] : 'Symbiota native is very similar to Darwin Core except with the addtion of a few fields
									such as substrate, associated collectors, verbatim description.'); ?>
								</div>
								<input type="radio" name="schema" id="darwin-core" value="dwc" onclick="georefRadioClicked(this)" />
								<label for="darwin-core">  <?php echo (isset($LANG['DARWIN_CORE']) ? $LANG['DARWIN_CORE'] : 'Darwin Core'); ?> </label>
								<a id="schemadwcinfo" href="#" title="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'); ?>" aria-label="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'); ?>">
									<img src="../../images/info.png" alt=" <?php echo (isset($LANG['IMG_DARWIN_INFO']) ? $LANG['IMG_DARWIN_INFO'] : 'Info icon: DwC is a TDWG endorsed standard for biodata. Link to DwC quick ref guide in the dialog.'); ?>" style="width:1.2em;" />
								</a><br/>
								<div id="schemadwcinfodialog">
									<?php echo (isset($LANG['DARWIN_GUIDE']) ? $LANG['DARWIN_GUIDE'] : 'Darwin Core (DwC) is a TDWG endorsed exchange standard specifically for biodiversity datasets.
									For more information on what data fields are included in DwC, visit the'); ?>
									<a href="http://rs.tdwg.org/dwc/index.htm"target='_blank'> <?php echo (isset($LANG['DARWIN_GUIDE_LINK']) ? $LANG['DARWIN_GUIDE_LINK'] : 'DwC Quick Reference Guide'); ?></a>.
								</div>
								*<a href='http://rs.tdwg.org/dwc/index.htm' class='bodylink' target='_blank'> <?php echo (isset($LANG['WHAT_IS_DARWIN_LINK']) ? $LANG['WHAT_IS_DARWIN_LINK'] : 'Ho What is Darwin Core?me'); ?></a>
							</div>
						</fieldset>
						<fieldset class="sectionDiv">
							<legend>  <?php echo (isset($LANG['DATA_EXTS']) ? $LANG['DATA_EXTS'] : 'Data Extensions'); ?>:</legend>
							<div class="formElemDiv">
								<input type="checkbox" name="identifications" id="identifications" value="1" onchange="extensionSelected(this)" checked />
								<label for="identifications"> <?php echo (isset($LANG['INCLUDE_HISTORY']) ? $LANG['INCLUDE_HISTORY'] : 'include Determination History'); ?> </label>
								<br/>
								<input type="checkbox" name="images" id="images" value="1" onchange="extensionSelected(this)" checked />
								<label for="images"> <?php echo (isset($LANG['INCLUDE_IMG']) ? $LANG['INCLUDE_IMG'] : 'include Image Records'); ?> </label>
								<br/>
								<?php
								if($dwcManager->hasAttributes()) echo '<input type="checkbox" name="attributes" id="attributes" value="1" onchange="extensionSelected(this)" checked /> <label for="attributes">' . (isset($LANG['INCLUDE_ATTR']) ? $LANG['INCLUDE_ATTR'] : 'include Occurrence Trait Attributes') . '</label><br/>';
								if($dwcManager->hasMaterialSamples()) echo '<input type="checkbox" name="materialsample" id="materialsample" value="1" onchange="extensionSelected(this)" checked /><label for="materialsample">' . (isset($LANG['IMCLUDE_MAT']) ? $LANG['IMCLUDE_MAT'] : 'include Material Samples') . '</label><br/>';
								?>
								*<?php echo (isset($LANG['DATA_EXT_NOTE']) ? $LANG['DATA_EXT_NOTE'] : 'Output must be a compressed archive'); ?>
							</div>
						</fieldset>
						<?php
					}
					?>
					<fieldset class="sectionDiv">
						<legend> <?php echo (isset($LANG['FILE_FORMAT']) ? $LANG['FILE_FORMAT'] : 'File Format'); ?>:</legend>
						<div class="formElemDiv">
							<input type="radio" name="format" id="csv-format" value="csv" CHECKED /><label for="csv-format">  <?php echo (isset($LANG['COMMA_DELIM']) ? $LANG['COMMA_DELIM'] : 'Comma Delimited (CSV)'); ?> </label><br/>
							<input type="radio" name="format" id="tab-delimited-format" value="tab" /><label for="tab-delimited-format">  <?php echo (isset($LANG['TAB_DELIM']) ? $LANG['TAB_DELIM'] : 'Tab Delimited'); ?> </label><br/>
						</div>
					</fieldset>
					<fieldset class="sectionDiv">
						<legend>  <?php echo (isset($LANG['CHAR_SET']) ? $LANG['CHAR_SET'] : 'Character Set'); ?>: </legend>
						<div class="formElemDiv">
							<?php
							//$cSet = strtolower($CHARSET);
							$cSet = 'iso-8859-1';
							?>
							<input type="radio" name="cset" id="iso-8859" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1' ? 'checked' : ''); ?> />
							<label for="iso-8859"> <?php echo (isset($LANG['ISO']) ? $LANG['ISO'] : 'ISO-8859-1 (western)'); ?> </label>
							<br/>
							<input type="radio" name="cset" id="utf-8" value="utf-8" <?php echo ($cSet=='utf-8' ? 'checked' : ''); ?> />
							<label for="utf-8"> <?php echo (isset($LANG['UTF_8']) ? $LANG['UTF_8'] : 'UTF-8 (unicode)'); ?> </label>
						</div>
					</fieldset>
					<fieldset class="sectionDiv">
						<legend>  <?php echo (isset($LANG['HCOMPRESSIONOME']) ? $LANG['COMPRESSION'] : 'Compression'); ?>: </legend>
						<div class="formElemDiv">
							<input type="checkbox" name="zip" id="zip" value="1" onchange="zipSelected(this)" checked />
							<label for="zip"> <?php echo (isset($LANG['COMPRESSED_ZIP']) ? $LANG['COMPRESSED_ZIP'] : 'Compressed ZIP file'); ?> </label><br/>
						</div>
					</fieldset>
					<div class="sectionDiv">
						<?php
						if($downloadType == 'checklist') echo '<input name="schema" type="hidden" value="checklist" />';
						elseif($downloadType == 'georef') echo '<input name="schema" type="hidden" value="georef" />';
						?>
						<input name="publicsearch" type="hidden" value="1" />
						<input name="taxonFilterCode" type="hidden" value="<?= $taxonFilterCode; ?>" />
						<input name="sourcepage" type="hidden" value="<?= htmlspecialchars($sourcePage); ?>" />
						<input name="searchvar" type="hidden" value="<?= $searchVar ?>" />
						<button type="submit" name="submitaction"> <?= $LANG['DOWNLOAD_DATA'] ?></button>
						<img id="workingcircle" src="../../images/ajax-loader_sm.gif" style="margin-bottom:-4px;width:20px;display:none;" />
					</div>
					<div class="sectionDiv">
						*  <?php echo (isset($LANG['LIMIT_NOTE']) ? $LANG['LIMIT_NOTE'] : 'There is a 1,000,000 record limit to occurrence downloads'); ?>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
	<?php
	if($displayHeader) include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>