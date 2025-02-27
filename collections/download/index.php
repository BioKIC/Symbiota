<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/download/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/download/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/download/index.en.php');

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
	<title> <?= $LANG['COLL_SEARCH_DWNL'] ?> </title>
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
				if(obj.form.identifiers) obj.form.identifiers.checked = false;
				if(obj.form.associations) obj.form.associations.checked = false;
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
		button { display: inline; }
		.sectionDiv{ clear:both; margin:20px; }
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
			<a href="../../index.php"> <?= $LANG['HOME'] ?> </a> &gt;&gt;
			<a href="#" onclick="closePage(0)"> <?= $LANG['RETURN'] ?> </a> &gt;&gt;
			<b> <?= $LANG['OCC_DOWNLOAD'] ?> </b>
		</div>
		<?php
	}
	?>
	<div style="width:100%; background-color:white;">
		<h1 class="page-heading"><?= $LANG['DATA_GUIDE'] ?></h1>
		<div style="margin:15px 0px;">
		<?= $LANG['GUIDE_ONE'] ?> <a href="../../includes/usagepolicy.php#images"> <?= $LANG['GUIDE_LINK'] ?> </a>.
			<?= $LANG['GUIDE_TWO'] ?>
		</div>
		<div style='margin:30px 15px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<legend>
						<?php
						if($downloadType == 'checklist') echo $LANG['DOWNLOAD_CHECKL'];
						elseif($downloadType == 'georef') echo $LANG['DOWNLOAD_GEO_DATA'];
						else echo $LANG['DOWNLOAD_SPEC_REC'];
						?>
					</legend>
					<?php
					if($downloadType == 'specimen'){
						?>
						<fieldset class="sectionDiv">
							<legend>  <?= $LANG['STRUCTURE'] ?>:</legend>
							<div class="formElemDiv">
								<input type="radio" name="schema" id="symbiota-native" value="symbiota" onclick="georefRadioClicked(this)" CHECKED />
								<label for="symbiota-native">  <?= $LANG['SYMB_NATIVE'] ?>  </label>
								<a id="schemanativeinfo" aria-label="<?= $LANG['MORE_INFO'] ?>" href="#" onclick="return false" title="<?= $LANG['MORE_INFO']; ?>">
									<img src="../../images/info.png" alt=" <?= $LANG['IMG_NATIVE_INFO']; ?> " style="width:1.2em;" />
								</a><br/>
								<div id="schemanativeinfodialog">
									<?= $LANG['SYMB_NATIVE_INFO']; ?>
								</div>
								<input type="radio" name="schema" id="darwin-core" value="dwc" onclick="georefRadioClicked(this)" />
								<label for="darwin-core">  <?= $LANG['DARWIN_CORE'] ?> </label>
								<a id="schemadwcinfo" href="#" title="<?= $LANG['MORE_INFO'] ?>" aria-label="<?= $LANG['MORE_INFO'] ?>">
									<img src="../../images/info.png" alt=" <?= $LANG['IMG_DARWIN_INFO'] ?>" style="width:1.2em;" />
								</a><br/>
								<div id="schemadwcinfodialog">
									<?= $LANG['DARWIN_GUIDE'] ?>
									<a href="http://rs.tdwg.org/dwc/index.htm"target='_blank'> <?= $LANG['DARWIN_GUIDE_LINK'] ?></a>.
								</div>
							</div>
						</fieldset>
						<fieldset class="sectionDiv">
							<legend>  <?= $LANG['DATA_EXTS'] ?>:</legend>
							<div class="formElemDiv">
								<input type="checkbox" name="identifications" id="identifications" value="1" onchange="extensionSelected(this)" checked />
								<label for="identifications"> <?= $LANG['INCLUDE_HISTORY'] ?> </label>
								<br/>
								<input type="checkbox" name="images" id="images" value="1" onchange="extensionSelected(this)" checked />
								<label for="images"> <?= $LANG['INCLUDE_IMG'] ?> </label>
								<br/>
								<?php
								if($dwcManager->hasAttributes()) echo '<input type="checkbox" name="attributes" id="attributes" value="1" onchange="extensionSelected(this)" checked /> <label for="attributes">' . $LANG['INCLUDE_ATTR'] . '</label><br/>';
								if($dwcManager->hasMaterialSamples()) echo '<input type="checkbox" name="materialsample" id="materialsample" value="1" onchange="extensionSelected(this)" checked /><label for="materialsample">' . $LANG['IMCLUDE_MAT'] . '</label><br/>';
								if($dwcManager->hasIdentifiers()) echo '<input type="checkbox" name="identifiers" id="identifiers" value="1" onchange="extensionSelected(this)" checked /> <label for="identifiers">' . $LANG['INCLUDE_IDENT'] . '</label><br/>';
								if($dwcManager->hasAssociations()) echo '<input type="checkbox" name="associations" id="associations" value="1" onchange="extensionSelected(this)" checked /> <label for="associations">' . $LANG['INCLUDE_ASSOCIATIONS'] . '</label><br/>';
								?>
								*<?= $LANG['DATA_EXT_NOTE'] ?>
							</div>
						</fieldset>
						<?php
					}
					?>
					<fieldset class="sectionDiv">
						<legend> <?= $LANG['FILE_FORMAT'] ?>:</legend>
						<div class="formElemDiv">
							<input type="radio" name="format" id="csv-format" value="csv" CHECKED /><label for="csv-format">  <?= $LANG['COMMA_DELIM'] ?> </label><br/>
							<input type="radio" name="format" id="tab-delimited-format" value="tab" /><label for="tab-delimited-format">  <?= $LANG['TAB_DELIM'] ?> </label><br/>
						</div>
					</fieldset>
					<fieldset class="sectionDiv">
						<legend>  <?= $LANG['CHAR_SET'] ?>: </legend>
						<div class="formElemDiv">
							<?php
							//$cSet = strtolower($CHARSET);
							$cSet = 'iso-8859-1';
							?>
							<input type="radio" name="cset" id="iso-8859" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1' ? 'checked' : ''); ?> />
							<label for="iso-8859"> <?= $LANG['ISO'] ?> </label>
							<br/>
							<input type="radio" name="cset" id="utf-8" value="utf-8" <?php echo ($cSet=='utf-8' ? 'checked' : ''); ?> />
							<label for="utf-8"> <?= $LANG['UTF_8'] ?> </label>
						</div>
					</fieldset>
					<fieldset class="sectionDiv">
						<legend>  <?= $LANG['COMPRESSION'] ?>: </legend>
						<div class="formElemDiv">
							<input type="checkbox" name="zip" id="zip" value="1" onchange="zipSelected(this)" checked />
							<label for="zip"> <?= $LANG['COMPRESSED_ZIP'] ?> </label><br/>
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
						<button type="submit" name="submitaction"><?= $LANG['DOWNLOAD_DATA'] ?></button>
						<img id="workingcircle" src="../../images/ajax-loader_sm.gif" style="margin-bottom:-4px;width:20px;display:none;" />
					</div>
					<div class="sectionDiv">
						*  <?= $LANG['LIMIT_NOTE'] ?>
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
