<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
header("Content-Type: text/html; charset=".$CHARSET);

$sourcePage = array_key_exists('sourcepage', $_REQUEST) ? filter_var($_REQUEST['sourcepage'], FILTER_SANITIZE_STRING) : 'specimen';
$downloadType = array_key_exists('dltype', $_REQUEST) ? filter_var($_REQUEST['dltype'], FILTER_SANITIZE_STRING) : 'specimen';
$taxonFilterCode = array_key_exists('taxonFilterCode', $_REQUEST) ? filter_var($_REQUEST['taxonFilterCode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$displayHeader = array_key_exists('displayheader', $_REQUEST) ? filter_var($_REQUEST['displayheader'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchVar = array_key_exists('searchvar', $_REQUEST) ? filter_var($_REQUEST['searchvar'], FILTER_SANITIZE_STRING) : '';

$dwcManager = new DwcArchiverCore();
?>
<html>
<head>
	<title>Collections Search Download</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
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
		$displayLeftMenu = (isset($collections_download_downloadMenu)?$collections_download_downloadMenu:false);
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt;
			<a href="#" onclick="closePage(0)">Return to Search Page</a> &gt;&gt;
			<b>Occurrence Record Download</b>
		</div>
		<?php
	}
	?>
	<div style="width:100%; background-color:white;">
		<h2>Data Usage Guidelines</h2>
		<div style="margin:15px 0px;">
			By downloading data, the user confirms that he/she has read and agrees with the general <a href="../../includes/usagepolicy.php#images">data usage terms</a>.
			Note that additional terms of use specific to the individual collections may be distributed with the data download. When present, the terms
			supplied by the owning institution should take precedence over the general terms posted on the website.
		</div>
		<div style='margin:30px 15px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<legend>
						<?php
						if($downloadType == 'checklist') echo 'Download Checklist';
						elseif($downloadType == 'georef') echo 'Download Georeference Data';
						else echo 'Download Specimen Records';
						?>
					</legend>
					<?php
					if($downloadType == 'specimen'){
						?>
						<div class="sectionDiv">
							<div class="labelDiv">Structure:</div>
							<div class="formElemDiv">
								<input type="radio" name="schema" value="symbiota" onclick="georefRadioClicked(this)" CHECKED />
								Symbiota Native
								<a id="schemanativeinfo" href="#" onclick="return false" title="More Information">
									<img src="../../images/info.png" style="width:13px;" />
								</a><br/>
								<div id="schemanativeinfodialog">
									Symbiota native is very similar to Darwin Core except with the addtion of a few fields
									such as substrate, associated collectors, verbatim description.
								</div>
								<input type="radio" name="schema" value="dwc" onclick="georefRadioClicked(this)" />
								Darwin Core
								<a id="schemadwcinfo" href="#" target="" title="More Information">
									<img src="../../images/info.png" style="width:13px;" />
								</a><br/>
								<div id="schemadwcinfodialog">
									Darwin Core (DwC) is a TDWG endorsed exchange standard specifically for biodiversity datasets.
									For more information on what data fields are included in DwC, visit the
									<a href="http://rs.tdwg.org/dwc/index.htm"target='_blank'>DwC Quick Reference Guide</a>.
								</div>
								*<a href='http://rs.tdwg.org/dwc/index.htm' class='bodylink' target='_blank'>What is Darwin Core?</a>
							</div>
						</div>
						<div class="sectionDiv">
							<div class="labelDiv">Data Extensions:</div>
							<div class="formElemDiv">
								<input type="checkbox" name="identifications" value="1" onchange="extensionSelected(this)" checked /> include Determination History<br/>
								<input type="checkbox" name="images" value="1" onchange="extensionSelected(this)" checked /> include Image Records<br/>
								<?php
								if($dwcManager->hasAttributes()) echo '<input type="checkbox" name="attributes" value="1" onchange="extensionSelected(this)" checked /> include Occurrence Trait Attributes<br/>';
								if($dwcManager->hasMaterialSamples()) echo '<input type="checkbox" name="materialsample" value="1" onchange="extensionSelected(this)" checked /> include Material Samples<br/>';
								?>
								*Output must be a compressed archive
							</div>
						</div>
						<?php
					}
					?>
					<div class="sectionDiv">
						<div class="labelDiv">File Format:</div>
						<div class="formElemDiv">
							<input type="radio" name="format" value="csv" CHECKED /> Comma Delimited (CSV)<br/>
							<input type="radio" name="format" value="tab" /> Tab Delimited<br/>
						</div>
					</div>
					<div class="sectionDiv">
						<div class="labelDiv">Character Set:</div>
						<div class="formElemDiv">
							<?php
							//$cSet = strtolower($CHARSET);
							$cSet = 'iso-8859-1';
							?>
							<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
							<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
						</div>
					</div>
					<div class="sectionDiv">
						<div class="labelDiv">Compression:</div>
						<div class="formElemDiv">
							<input type="checkbox" name="zip" value="1" onchange="zipSelected(this)" checked />Compressed ZIP file<br/>
						</div>
					</div>
					<div class="sectionDiv">
						<?php
						if($downloadType == 'checklist') echo '<input name="schema" type="hidden" value="checklist" />';
						elseif($downloadType == 'georef') echo '<input name="schema" type="hidden" value="georef" />';
						?>
						<input name="publicsearch" type="hidden" value="1" />
						<input name="taxonFilterCode" type="hidden" value="<?php echo $taxonFilterCode; ?>" />
						<input name="sourcepage" type="hidden" value="<?php echo $sourcePage; ?>" />
						<input name="searchvar" type="hidden" value="<?php echo str_replace('"','&quot;',$searchVar); ?>" />
						<button type="submit" name="submitaction">Download Data</button>
						<img id="workingcircle" src="../../images/ajax-loader_sm.gif" style="margin-bottom:-4px;width:20px;display:none;" />
					</div>
					<div class="sectionDiv">
						* There is a 1,000,000 record limit to occurrence downloads
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