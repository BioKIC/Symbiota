<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyDataManager.php');
include_once($SERVER_ROOT.'/content/lang/ident/key.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$isEditor = false;
if($IS_ADMIN || array_key_exists("KeyEditor",$USER_RIGHTS)){
	$isEditor = true;
}

$attrsValues = Array();

$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:"";
if(!$clValue && array_key_exists('clid',$_REQUEST)) $clValue = $_REQUEST['clid'];
$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$action = array_key_exists("submitbutton",$_REQUEST)?$_REQUEST["submitbutton"]:"";
$rv = array_key_exists("rv",$_REQUEST)?$_REQUEST["rv"]:"";
$pid = array_key_exists('pid',$_REQUEST)?$_REQUEST['pid']:'';
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
$displayCommon = array_key_exists('displaycommon',$_REQUEST)?$_REQUEST['displaycommon']:0;
if(!$action && array_key_exists("attr",$_REQUEST) && is_array($_REQUEST["attr"])){
	$attrsValues = $_REQUEST["attr"];	//Array of: cid + "-" + cs (ie: 2-3)
}

//Sanitation
if(!is_numeric($dynClid)) $dynClid = 0;
if(!is_numeric($pid)) $pid = 0;
if(!is_numeric($rv)) $rv = '';
$langValue = 'English';
if(!is_numeric($displayCommon)) $displayCommon = 0;

$dataManager = new KeyDataManager();

//if(!$langValue) $langValue = $DEFAULT_LANG;
if($displayCommon) $dataManager->setDisplayCommon(true);
$dataManager->setLanguage($langValue);
if($pid) $dataManager->setProject($pid);
if($dynClid) $dataManager->setDynClid($dynClid);
$clid = $dataManager->setClValue($clValue);
if($taxonValue) $dataManager->setTaxonFilter($taxonValue);
if($attrsValues) $dataManager->setAttrs($attrsValues);
if($rv) $dataManager->setRelevanceValue($rv);

$data = $dataManager->getData();
$chars = $data["chars"];  				//$chars = Array(HTML Strings)
$taxa = $data["taxa"];					//$taxa  = Array(family => array(TID => DisplayName))

//Harevest and remove language list from $chars
$languages = Array();
if($chars){
	$languages = $chars["Languages"];
	unset($chars["Languages"]);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['WEBKEY'] . ' ' . preg_replace('/\<[^\>]+\>/','',$dataManager->getClName()); ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script type="text/javascript" src="../js/symb/ident.key.js"></script>
	<style>
		#keycharcolumn { vertical-align: top; width: 30%; }
		#keymidcolumn { width: 20px; }
		#keytaxacolumn { vertical-align: top; width: 65%; }
		.dynamlang { margin-top: 0.5em; font-weight: bold; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($ident_keyMenu)?$ident_keyMenu:true);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($ident_keyCrumbs)){
		if($ident_keyCrumbs){
			echo '<div class="navpath">';
			if($dynClid){
				if($dataManager->getClType() == 'Specimen Checklist'){
					echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/list.php?tabindex=0">';
					echo 'Occurrence Checklist';
					echo '</a> &gt; ';
				}
			}
			else{
				echo $ident_keyCrumbs;
			}
			echo ' <b>'.$dataManager->getClName().' Key</b>';
			echo '</div>';
		}
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../index.php">' . htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		if($dynClid){
			if($dataManager->getClType() == 'Specimen Checklist'){
				echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/list.php?tabindex=0">';
				echo 'Occurrence Checklist';
				echo '</a> &gt;&gt; ';
			}
		}
		elseif($clid){
			echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/checklists/checklist.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
			echo 'Checklist: '.$dataManager->getClName();
			echo '</a> &gt;&gt; ';
		}
		elseif($pid){
			echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/projects/index.php?pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
			echo 'Project Checklists';
			echo '</a> &gt;&gt; ';
		}
		echo '<b>Key: '.$dataManager->getClName().'</b>';
		echo '</div>';
	}

?>
<div role="main" id="innertext">
	<h1 class="page-heading screen-reader-only">Key</h1>
	<?php
	if($isEditor){
		?>
		<div style="float:right;margin:15px;" title="Edit Character Matrix">
			<a href="tools/matrixeditor.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><img src="../images/edit.png" style="width:1.2em" /><span style="font-size:70%;">CM</span></a>
		</div>
		<?php
	}
	?>
	<form name="keyform" id="keyform" action="key-v1.php" method="get">
		<table id="keytable">
			<tr>
				<td id="keycharcolumn">
					<div>
						<div style="font-weight:bold;margin-top:0.5em;"><?php echo $LANG['TAXON'];?>:</div>
						<select name="taxon">
							<?php
							echo "<option value='All Species'>".$LANG['SELECTTAX']."</option>\n";
							$selectList = Array();
							$selectList = $dataManager->getTaxaFilterList();
							foreach($selectList as $value){
								$selectStr = ($value==$taxonValue?"SELECTED":"");
								echo "<option $selectStr>$value</option>\n";
							}
							?>
						</select>
					</div>
					<div style='font-weight:bold; margin-top:0.5em;'>
						<input type="hidden" id="cl" name="clid" value="<?php echo $clid; ?>" />
						<input type="hidden" id="dynclid" name="dynclid" value="<?php echo $dynClid; ?>" />
						<input type="hidden" id="pid" name="pid" value="<?php echo $pid; ?>" />
						<input type="hidden" id="rv" name="rv" value="<?php echo $dataManager->getRelevanceValue(); ?>" />
						<button type="submit" name="submitbutton" id="submitbutton" value="displaySpeciesList"><?php echo $LANG['DISPRESSPEC'];?></button>
					</div>
					<hr size="2" />

					<?php
					//echo "<div style=''>Relevance value: <input name='rv' type='text' size='3' title='Only characters with > ".($rv*100)."% relevance to the active spp. list will be displayed.' value='".$dataManager->getRelevanceValue()."'></div>";
					//List char Data with selected states checked
					if(count($languages) > 1){
						echo "<div id='langlist' style='margin:0.5em;'>Languages: <select name='lang' onchange='setLang(this);'>\n";
						foreach($languages as $l){
						    echo "<option value='".$l."' ".($DEFAULT_LANG == $l?"SELECTED":"").">$l</option>\n";
						}
						echo "</select></div>\n";
					}
					echo '<div style="margin:5px">'.$LANG['DISPLAY'].': ';
					echo '<select name="displaycommon" onchange="this.form.submit();"><option value="0">'.$LANG['SCINAME'].'</option><option value="1"'.($displayCommon?' SELECTED':'').'>'.$LANG['COMMON'].'</option></select>';
					echo '</div>';
					if($chars){
						//echo "<div id='showall' class='dynamControl' style='display:none'><a href='#' onclick='javascript: toggleAll();'>Show All Characters</a></div>\n";
						//echo "<div class='dynamControl' style='display:block'><a href='#' onclick='javascript: toggleAll();'>Hide Advanced Characters</a></div>\n";
						foreach($chars as $key => $htmlStrings){
							echo $htmlStrings."\n";
						}
					}
					?>
				</td>
				<td id="keymidcolumn"></td>
				<td id="keytaxacolumn">
					<?php
					//List taxa by family/sci name
					if(($clid && $taxonValue) || $dynClid){
						?>
						<table border='0' width='300px'>
							<tr><td colspan='2'>
								<h2>
									<?php
									if($FLORA_MOD_IS_ACTIVE){
										echo '<a href="../checklists/checklist.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&dynclid=' . htmlspecialchars($dynClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
									}
									echo $dataManager->getClName()." ";
									if($FLORA_MOD_IS_ACTIVE){
										echo "</a>";
									}
									?>
								</h2>
								<?php
								if(!$dynClid) echo "<div>".$dataManager->getClAuthors()."</div>";
								?>
							</td></tr>
							<?php
							$count = $dataManager->getTaxaCount();
							if($count > 0){
								echo "<tr><td colspan='2'>".$LANG['SPECCOUNT'].": ".$count."</td></tr>\n";
							}
							else{
								echo "<tr><td colspan='2'>".$LANG['NOMATCH']."</td></tr>\n";
							}
							ksort($taxa);
							foreach($taxa as $family => $species){
								echo "<tr><td colspan='2'><h3 style='margin-bottom:0px;margin-top:10px;'>$family</h3></td></tr>\n";
								natcasesort($species);
								foreach($species as $tid => $disName){
									$newSpLink = '../taxa/index.php?taxon='.$tid."&clid=".($dataManager->getClType()=="static"?$dataManager->getClid():"");
									echo "<tr><td><div style='margin:0px 5px 0px 10px;'><a href='" . htmlspecialchars($newSpLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "' target='_blank'><i>$disName</i></a></div></td>\n";
									echo "<td align='right'>\n";
									if($isEditor){
										echo "<a href='tools/editor.php?tid=$tid&lang=" . htmlspecialchars($DEFAULT_LANG, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "' target='_blank'><img src='../images/edit.png' width='1.2em' border='0' title='" . htmlspecialchars($LANG['EDITMORP'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "' /></a>\n";
									}
									echo "</td></tr>\n";
								}
							}
							?>
						</table>
						<?php
					}
					else{
						echo $dataManager->getIntroHtml();
					}
					?>
				</td>
			</tr>
		</table>
		<?php
		if(array_key_exists("crumburl",$_REQUEST)) echo "<input type='hidden' name='crumburl' value='".$_REQUEST["crumburl"]."' />";
		if(array_key_exists("crumbtitle",$_REQUEST)) echo "<input type='hidden' name='crumbtitle' value='".$_REQUEST["crumbtitle"]."' />";
		?>
	</form>
</div>
<?php
	include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>

