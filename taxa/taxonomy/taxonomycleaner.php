<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomycleaner.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomycleaner.' . $LANG_TAG . '.php');
	else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomycleaner.en.php');

$collId = $_REQUEST['collid'];
$displayIndex = array_key_exists('displayindex',$_REQUEST)?$_REQUEST['displayindex']:0;
$analyzeIndex = array_key_exists('analyzeindex',$_REQUEST)?$_REQUEST['analyzeindex']:0;
$taxAuthId = array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:1;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($collId)) $collId = 0;
if(!is_numeric($displayIndex)) $displayIndex = 0;
if(!is_numeric($analyzeIndex)) $analyzeIndex = 0;
if(!is_numeric($taxAuthId)) $taxAuthId = 1;

$cleanManager = null;
$collName = '';

if($collId){
	$cleanManager = new TaxonomyCleaner();
	$cleanManager->setCollId($collId);
	$collName = $cleanManager->getCollectionName();
}
else{
	$cleanManager = new TaxonomyCleaner();
}
if($taxAuthId){
	$cleanManager->setTaxAuthId($taxAuthId);
}

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}
else{
	if($collId){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])){
			$isEditor = true;
		}
	}
	else{
		if(array_key_exists("Taxonomy",$USER_RIGHTS)) $isEditor = true;
	}
}

$status = "";

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['TAX_NAME_CLEANER']; ?></title>
		<?php

		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script language="javascript">
			function toggle(divName){
				divObj = document.getElementById(divName);
				if(divObj != null){
					if(divObj.style.display == "block"){
						divObj.style.display = "none";
					}
					else{
						divObj.style.display = "block";
					}
				}
				else{
					divObjs = document.getElementsByTagName("div");
					divObjLen = divObjs.length;
					for(i = 0; i < divObjLen; i++) {
						var obj = divObjs[i];
						if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
							else {
								obj.style.display="none";
							}
						}
					}
				}
			}

		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($taxa_admin_taxonomycleanerMenu)?$taxa_admin_taxonomycleanerMenu:'true');
		include($SERVER_ROOT.'/includes/header.php');
		if(isset($taxa_admin_taxonomycleanerCrumbs)){
			?>
			<div class='navpath'>
				<?php echo $taxa_admin_taxonomycleanerCrumbs; ?>
				<b><?php echo $LANG['TAX_NAME_CLEANER']; ?></b>
			</div>
			<?php
		}
		?>
		<!-- inner text block -->
		<div role="main" id="innertext">
			<h1 class="page-heading"><?php echo $LANG['TAX_THES_VALIDATOR']; ?></h1>
			<?php
			if($SYMB_UID){
				if($status){
					?>
					<div style='float:left;margin:20px 0px 20px 0px;'>
						<hr/>
						<?php echo $status; ?>
						<hr/>
					</div>
					<?php
				}
				if($isEditor){
					if($collId){
						?>
						<h1><?php echo $collName; ?></h1>
						<div>
							<?php echo $LANG['TAX_CLEANER_EXPLAIN']; ?>
						</div>
						<div>
							<?php echo $LANG['NUMBER_MISMAPPED'] . ": " . $cleanManager->getTaxaCount(); ?>
						</div>
						<?php
						if(!$action){
							?>
							<form name="occurmainmenu" action="taxonomycleaner.php" method="post">
								<fieldset>
									<legend><b><?php echo $LANG['MAIN_MENU']; ?></b></legend>
									<div>
										<input type="radio" name="submitaction" value="displaynames" />
										<?php echo $LANG['DISPLAY_UNVERIFIED']; ?>
										<div style="margin-left:15px;"><?php echo $LANG['START_INDEX']; ?>:
											<input name="displayindex" type="text" value="0" style="width:25px;" />
											<?php echo $LANG['500_NAMES']; ?>
										</div>
									</div>
									<div>
										<input type="radio" name="submitaction" value="analyzenames" />
										<?php echo $LANG['ANALYZE_NAMES']; ?>
										<div style="margin-left:15px;"><?php echo $LANG['START_INDEX']; ?>:
											<input name="analyzeindex" type="text" value="0" style="width:25px;" />
											<?php echo $LANG['10_NAMES']; ?>
										</div>
									</div>
									<div>
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="submit" name="submitbut" value="Perform Action" />
									</div>
								</fieldset>
							</form>
							<?php
						}
						elseif($action == 'displaynames'){
							$nameArr = $cleanManager->getTaxaList($displayIndex);
							echo '<ul>';
							foreach($nameArr as $k => $sciName){
								echo '<li>';
								echo '<a href="spectaxcleaner.php?submitaction=analyzenames&analyzeindex=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
								echo '<b><i>'.$sciName.'</i></b>';
								echo '</a>';
								echo '</li>';
							}
							echo '</ul>';
						}
						elseif($action == 'analyzenames'){
							$nameArr = $cleanManager->analyzeTaxa($analyzeIndex);
							echo '<ul>';
							foreach($nameArr as $sn => $snArr){
								echo '<li>'.$sn.'</li>';
								if(array_key_exists('col',$snArr)){

								}
								else{
									echo '<div style="margin-left:15px;font-weight:bold;">';
									echo '<form name="taxaremapform" method="get" action="" >';
									echo $LANG['REMAP_TO'] . ': ';
									echo '<input type="input" name="remaptaxon" value="' . $sn . '" />';
									echo '<input type="submit" name="submitaction" value="Remap" />';
									echo '</form>';
									echo '</div>';
									if(array_key_exists('soundex',$snArr)){
										foreach($snArr['soundex'] as $t => $s){
											echo '<div style="margin-left:15px;font-weight:bold;">';
											echo $s;
											echo ' <a href="" title="' . htmlspecialchars($LANG['REMAP_TO_NAME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '...">==>></a>';
											echo '</div>';
										}
									}
								}
							}
							echo '</ul>';
						}
					}
					else{
						?>
						<div style="margin:15px;">
							<?php echo $LANG['VALIDATOR_EXPLAIN']; ?>.
						</div>
						<?php
						$taxonomyAction = array_key_exists('taxonomysubmit',$_POST)?$_POST['taxonomysubmit']:'';
						if($taxonomyAction == 'Validate Names'){
							?>
							<div style="margin:15px;">
								<b><?php echo $LANG['VAL_STATUS']; ?>:</b>
								<ul>
									<?php //$cleanManager->verifyTaxa($_POST['versource']); ?>
								</ul>
							</div>
							<?php
						}
						?>
						<div style="margin:15px;">
							<fieldset>
								<legend><b><?php echo $LANG['VER_STATUS']; ?></b></legend>
								<?php
								$vetArr = $cleanManager->getVerificationCounts();
								?>
								<?php echo $LANG['FULL_VER'] . ': ' . $vetArr[1]; ?><br/>
								<?php echo $LANG['SUSPECT_STATUS'] . ': ' . $vetArr[2]; ?><br/>
								<?php echo $LANG['VALIDATE_ONLY'] . ': ' . $vetArr[3]; ?><br/>
								<?php echo $LANG['UNTESTED'] . ': ' . $vetArr[0]; ?>
							</fieldset>
						</div>
						<div style="margin:15px;">
							<form name="taxonomymainmenu" action="taxonomycleaner.php" method="post">
								<fieldset>
									<legend><b><?php echo $LANG['MAIN_MENU']; ?></b></legend>
									<div>
										<b><?php echo $LANG['TESTING_RESOURCE']; ?>:</b><br/>
										<input type="radio" name="versource" value="col" CHECKED />
										<?php echo $LANG['CAT_OF_LIFE']; ?><br/>
									</div>
									<div>
										<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId; ?>" />
										<button type="submit" name="taxonomysubmit" value="Validate Names" ><?php echo $LANG['VALIDATE_NAMES']; ?></button>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
				}
				else{
					?>
					<div style="margin:20px;font-weight:bold;font-size:120%;">
						<?php echo $LANG['ERROR_NOPERM']; ?>.
					</div>
					<?php
				}
			}
			else{
				?>
				<div style="font-weight:bold;">
					<?php echo $LANG['PLEASE'] . "<a href='../../profile/index.php?refurl=" . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "/taxa/taxonomy/taxonomycleaner.php?collid=" . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ">" . htmlspecialchars($LANG['LOGIN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a>!" ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php include($SERVER_ROOT.'/includes/footer.php');?>
	</body>
</html>
