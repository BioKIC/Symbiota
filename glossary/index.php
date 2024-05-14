<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/glossary/index.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/glossary/index.en.php');
else include_once($SERVER_ROOT.'/content/lang/glossary/index.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$glossId = array_key_exists('glossid', $_REQUEST) ? filter_var($_REQUEST['glossid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$language = array_key_exists('searchlanguage', $_REQUEST) ? htmlspecialchars($_REQUEST['searchlanguage'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$tid = array_key_exists('searchtaxa', $_REQUEST) ? filter_var($_REQUEST['searchtaxa'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchTerm = array_key_exists('searchterm', $_REQUEST) ? $_REQUEST['searchterm'] : '';
$deepSearch = array_key_exists('deepsearch', $_POST) ? filter_var($_POST['deepsearch'], FILTER_SANITIZE_NUMBER_INT) : 0;
$formSubmit = array_key_exists('formsubmit', $_POST) ? $_POST['formsubmit'] : '';

if(!$language) $language = $DEFAULT_LANG;
if($language == 'en') $language = 'English';
if($language == 'es') $language = 'Spanish';

$isEditor = false;
if($IS_ADMIN || array_key_exists('GlossaryEditor',$USER_RIGHTS)) $isEditor = true;

$glosManager = new GlossaryManager();

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Add Source'){
		if(!$glosManager->addSource($_POST)) $statusStr = $glosManager->getErrorMessage();
	}
	elseif($formSubmit == 'Edit Source'){
		if(!$glosManager->editSource($_POST)) $statusStr = $glosManager->getErrorMessage();
	}
	elseif($formSubmit == 'Delete Source'){
		if(!$glosManager->deleteSource($_POST['tid'])) $statusStr = $glosManager->getErrorMessage();
	}
}
$languageArr = $glosManager->getLanguageArr();
$langArr = $languageArr['all'];
unset($languageArr['all']);

$taxaArr = $glosManager->getTaxaGroupArr();
$taxonName = ($tid?$taxaArr[$tid]:'');
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['GLOSSARY']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var langArr = {
			<?php
			$d = '';
			foreach($languageArr as $k => $v){
				echo $d.'"'.$k.'":['.$v.']';
				$d = ',';
			}
			?>
		};

		resetLanguageSelect(document.searchform);

		function resetLanguageSelect(f){
			if($("#searchlanguage").is('select')){
				var tid = f.searchtaxa.value;
				if(tid == '') tid = 0;
				var oldLang = $("#searchlanguage").val();
				$("#searchlanguage").empty();
				$.each(langArr[tid], function(key,value) {
					$("#searchlanguage").append($("<option></option>").attr("value", value).text(value));
				});
				$("#searchlanguage").val(oldLang);
			}
		}

		function verifyDownloadForm(f){
			var searchForm = document.searchform;
			f.searchlanguage.value = searchForm.searchlanguage.value;
			f.searchtaxa.value = searchForm.searchtaxa.value;
			f.searchterm.value = searchForm.searchterm.value;
			if(searchForm.deepsearch.checked) f.deepsearch.value = 1;

			var downloadtype = f.exporttype.value;
			if(downloadtype == 'translation'){
				var numTranslations = 0;
				var e = f.getElementsByTagName("input");
				for(var i=0;i<e.length;i++){
					if(e[i].name == "language[]"){
						if(e[i].checked == true){
							numTranslations++;
						}
					}
				}
				if(numTranslations > 3){
					alert("<?php echo $LANG['PLEASE_TRANSL']; ?>");
					return false;
				}
				if(numTranslations === 0){
					alert("<?php echo $LANG['PLEASE_ONE']; ?>");
					return false;
				}
			}
			return true;
		}

		function openNewTermPopup(glossid,relation){
			var urlStr = 'addterm.php?rellanguage=<?php echo $language.'&taxatid='.$tid.'&taxaname='.$taxonName; ?>';
			newWindow = window.open(urlStr,'addnewpopup','toolbar=0,status=1,scrollbars=1,width=1250,height=700,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}

		function openTermPopup(glossid){
			var urlStr = 'individual.php?glossid='+glossid;
			newWindow = window.open(urlStr,'popup','toolbar=0,status=1,scrollbars=1,width=1100,height=550,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
		}

	</script>
	<script src="../js/symb/glossary.index.js?ver=2" type="text/javascript"></script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../index.php'><?php echo (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a> &gt;&gt;
		<a href='index.php'> <b><?php echo (isset($LANG['GLOSSARY'])?$LANG['GLOSSARY']:'Glossary'); ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<?php
		if($statusStr){
			?>
			<div style="margin:15px;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php
		}
		if(isset($GLOSSARY_BANNER) && $GLOSSARY_BANNER){
			$bannerUrl = $GLOSSARY_BANNER;
			if(substr($bannerUrl,0,4) != 'http' && substr($bannerUrl,0,1) != '/'){
				$bannerUrl = '../images/layout/'.$bannerUrl;
			}
			echo '<div id="glossaryBannerDiv"><img src="'.$bannerUrl.'" /></div>';
		}
		if(isset($GLOSSARY_DESCRIPTION) && $GLOSSARY_DESCRIPTION){
			echo '<div id="glossaryDescriptionDiv">'.$GLOSSARY_DESCRIPTION.'</div><div style="clear:both;"></div>';
		}
		?>
		<div style="float:right;width:360px;">
			<div style="float:right;">
				<?php
				if($isEditor){
					?>
					<div>
						<a onclick="openNewTermPopup();">
							<?php echo $LANG['ADD_TERM']; ?>
						</a>
					</div>
					<div>
						<!--
						<a href='glossaryloader.php'>Batch Upload Terms</a>
						 -->
					</div>
					<?php
				}
				?>
				<div>
					<a title="Show download options" onclick="toggle('downloadoptionsdiv');return false;">
						<?php echo (isset($LANG['DOWN_OP'])?$LANG['DOWN_OP']:'Download Options'); ?>
					</a>
				</div>
			</div>
			<div id="downloadoptionsdiv" style="display:none;clear:both;float:right;margin-top:15px;background-color:white;">
				<form name="downloadform" action="glossdocexport.php" method="post" onsubmit="return verifyDownloadForm(this);">
					<fieldset style="padding:8px">
						<legend><b><?php echo (isset($LANG['DOWN_OP'])?$LANG['DOWN_OP']:'Download Options'); ?></b></legend>
						<?php
						if(count($langArr) > 1){
							?>
							<div style="margin-bottom:8px;">
								<?php echo (isset($LANG['PRIM_WILL'])?$LANG['PRIM_WILL']:'Primary language will be language selected to the left'); ?>.
							</div>
							<div style="margin-bottom:8px;">
								<div>
									<input name="exporttype" type="radio" value="singlelanguage" checked /> <?php echo (isset($LANG['SING_LANG'])?$LANG['SING_LANG']:'Single Language'); ?> 
								</div>
								<div style="margin-left:25px;">
									<input name="images" type="checkbox" value="images" /> <?php echo (isset($LANG['INCL_IMG'])?$LANG['INCL_IMG']:'Include Images'); ?>
								</div>
							</div>
							<div>
								<div>
									<input name="exporttype" type="radio" value="translation" /> <?php echo (isset($LANG['TRANS_TAB'])?$LANG['TRANS_TAB']:'Translation Table'); ?>
								</div>
								<div style="float:left;margin-left:25px;">
									<b><?php echo (isset($LANG['TRANSS'])?$LANG['TRANSS']:'Translations'); ?></b><br />
									<?php
									foreach($langArr as $k => $v){
										echo '<input name="language[]" type="checkbox" value="'.$v.'" /> '.$v.'<br />';
									}
									?>
								</div>
								<div style="float:left;margin-left:15px;padding-top:1.1em;">
									<input name="definitions" type="radio" value="nodef" checked /> <?php echo (isset($LANG['NO_DEF'])?$LANG['NO_DEF']:'Without Definitions'); ?><br />
									<input name="definitions" type="radio" value="onedef" /> <?php echo (isset($LANG['ONE_DEF'])?$LANG['ONE_DEF']:'Primary Definition Only'); ?><br />
									<input name="definitions" type="radio" value="alldef" /> <?php echo (isset($LANG['ALL_DEF'])?$LANG['ALL_DEF']:'All Definitions'); ?>
								</div>
							</div>
							<?php
						}
						else{
							?>
							<div style="margin-left:5px;">
								<input name="exporttype" type="hidden" value="0" />
								<input name="images" type="checkbox" value="images" /> <?php echo (isset($LANG['INCL_IMG'])?$LANG['INCL_IMG']:'Include Images'); ?>
							</div>
							<?php
						}
						?>
						<div style="clear:both;padding:15px">
							<input name="searchlanguage" type="hidden" value="<?php echo $language; ?>" />
							<input name="searchtaxa" type="hidden" value="<?php echo $tid; ?>" />
							<input name="searchterm" type="hidden" value="<?php echo htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" />
							<input name="deepsearch" type="hidden" value="<?php echo $deepSearch; ?>" />
							<button name="formsubmit" type="submit" value="Download"><?php echo (isset($LANG['DOWNLOAD'])?$LANG['DOWNLOAD']:'Download'); ?></button>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
		<h1 class="page-heading"><?php echo (isset($LANG['SEARCH_GL'])?$LANG['SEARCH_GL']:'Search/Browse Glossary'); ?></h1>
		<div style="float:left;">
			<form id="searchform" name="searchform" action="index.php" method="post" onsubmit="return verifySearchForm(this);">
				<div style="height:25px;">
					<?php
					if($taxaArr){
						?>
						<div style="float:left;">
							<b><?php echo (isset($LANG['TAX_GROUP'])?$LANG['TAX_GROUP']:'Taxonomic Group'); ?>:</b>
							<select id="searchtaxa" name="searchtaxa" style="margin-top:2px;width:300px;" onchange="resetLanguageSelect(this.form)">
								<option value=""><?php echo (isset($LANG['ALL_GROUPS'])?$LANG['ALL_GROUPS']:'Show terms for all groups'); ?></option>
								<?php
								foreach($taxaArr as $k => $v){
									echo '<option value="'.$k.'" '.($k==$tid?'SELECTED':'').'>'.$v.'</option>';
								}
								?>
							</select>
						</div>
						<?php
					}
					if(count($langArr) > 1){
						?>
						<div style="float:left;margin-left:10px;">
							<b><?php echo (isset($LANG['LANG'])?$LANG['LANG']:'Language'); ?>:</b>
							<select id="searchlanguage" name="searchlanguage" style="margin-top:2px;" onchange="">
								<?php
								foreach($langArr as $k => $v){
									echo '<option value="'.$v.'" '.($v==$language||$k==$language?'SELECTED':'').'>'.$v.'</option>';
								}
								?>
							</select>
						</div>
						<?php
					}
					?>
				</div>
				<div style="clear:both;">
					<b><?php echo (isset($LANG['SEARCH_TERM'])?$LANG['SEARCH_TERM']:'Search Term'); ?>:</b>
					<input type="text" autocomplete="off" name="searchterm" size="25" value="<?php echo htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" />
				</div>
				<div style="margin-left:40px">
					<input id="deepsearch" name="deepsearch" type="checkbox" value="1" <?php echo $deepSearch?'checked':''; ?> />
					<label for="deepsearch"> <?php echo (isset($LANG['SEARCH_DEF'])?$LANG['SEARCH_DEF']:'Search within definitions'); ?> </label>
				</div>
				<div style="margin:20px">
					<button name="formsubmit" type="submit" value="Search Terms"><?php echo (isset($LANG['SEARCH_TERMS'])?$LANG['SEARCH_TERMS']:'Search/Browse Terms'); ?></button>
				</div>
			</form>
		</div>
		<div>
			<div style="min-height:200px;clear:left">
				<?php
				$termList = $glosManager->getTermSearch($searchTerm, $language, $tid, $deepSearch);
				if($termList){
					?>
					<div>
						<?php
						$title = $LANG['TERMS'];
						if($taxonName) $title .= ' '.$LANG['FOR'].' '.$taxonName;
						if($language) $title .= ' '.$LANG['IN'].' '.$language;
						if($searchTerm) $title .= ' '.$LANG['KEYWORD'].' &quot;'.htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE).'&quot;';
						echo '<div style="float:left;font-weight:bold;font-size:120%;">'.$title.'</div>';
						$sourceArrFull = $glosManager->getTaxonSources($tid);
						$sourceArr = current($sourceArrFull);
						if($sourceArr){
							?>
							<div style="float:left;margin-left:5px;">
								<div style="" onclick="toggle('sourcesdiv');return false;">
									(<a><?php echo (isset($LANG['DISP_SRC'])?$LANG['DISP_SRC']:'Display Sources'); ?></a>)
								</div>
							</div>
							<?php
						}
						else{
							if($isEditor){
								?>
								<div style="float:left;margin-left:5px;">
									(<a href="sources.php?emode=1&tid=<?php echo $tid.'&searchterm='.htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE).'&language='.$language.'&taxa='.$tid; ?>"><?php echo (isset($LANG['ADD_SRC'])?$LANG['ADD_SRC']:'Add Sources'); ?></a>)
								</div>
								<?php
							}
						}
						?>
					</div>
					<?php
					if($sourceArr){
						?>
						<div id="sourcesdiv" style="display:none;padding:5px">
							<fieldset style="margin:15px;padding:20px;">
								<legend><b><?php echo (isset($LANG['TAX_CONTR'])?$LANG['TAX_CONTR']:'Contributors for Taxonomic Group'); ?></b></legend>
								<?php
								if($isEditor){
									?>
									<div style="float:right;">
										<a href="sources.php?emode=1&tid=<?php echo $tid.'&searchterm='.htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE).'&language='.$language.'&taxa='.$tid; ?>"><img src="../images/edit.png" style="width:1.3em" /></a>
									</div>
									<?php
								}
								if($sourceArr['contributorTerm']){
									?>
									<div style="">
										<?php echo '<b>'.(isset($LANG['TERM_CONTR'])?$LANG['TERM_CONTR']:'Terms and Definitions contributed by').':</b> '.$sourceArr['contributorTerm']; ?>
									</div>
									<?php
								}
								if($sourceArr['contributorImage']){
									?>
									<div style="margin-top:8px;">
										<?php echo '<b>'.(isset($LANG['IMG_CONTR'])?$LANG['IMG_CONTR']:'Images contributed by').':</b> '.$sourceArr['contributorImage']; ?>
									</div>
									<?php
								}
								if($sourceArr['translator']){
									?>
									<div style="margin-top:8px;">
										<?php echo '<b>'.(isset($LANG['TRANS_BY'])?$LANG['TRANS_BY']:'Translations by').':</b> '.$sourceArr['translator']; ?>
									</div>
									<?php
								}
								if($sourceArr['additionalSources']){
									?>
									<div style="margin-top:8px;">
										<?php echo '<b>'.(isset($LANG['TRAN_IMG_BY'])?$LANG['TRAN_IMG_BY']:'Translations and images were also sourced from the following references').':</b> '.$sourceArr['additionalSources']; ?>
									</div>
									<?php
								}
								?>
							</fieldset>
						</div>
						<?php
					}
					echo '<div style="padding:10px;"><ul>';
					foreach($termList as $termArr){
						foreach($termArr as $glossId => $termObj){
							$termDisplay = '<a href="#" onclick="openTermPopup('.$glossId.'); return false;">'.$termObj['d'].'</a>';
							if(isset($termObj['goto'])){
								$gotoArr = $termObj['goto'];
								$termDisplay = $termObj['d'].' &equals;&gt; <a href="#" onclick="openTermPopup('.key($gotoArr).'); return false;">'.current($gotoArr).'</a>';
							}
							echo '<li>';
							echo $termDisplay;
							echo '</li>';
						}
					}
					echo '</ul></div>';
				}
				elseif($formSubmit){
					echo '<div style="margin-top:10px;font-weight:bold;font-size:120%;">'.$LANG['NO_TERMS'].'</div>';
				}
				?>
			</div>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>