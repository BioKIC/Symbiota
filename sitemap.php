<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/SiteMapManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/sitemap.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/sitemap.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/sitemap.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$SHOULD_USE_HARVESTPARAMS = $SHOULD_USE_HARVESTPARAMS ?? false;
$actionPage = $SHOULD_USE_HARVESTPARAMS ? "collections/index.php" : "collections/search/index.php";
$smManager = new SiteMapManager();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['SITEMAP'];?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');

	//detect custom css file
	if(file_exists($_SERVER['DOCUMENT_ROOT'].$CSS_BASE_PATH.'/symbiota/sitemap.css')){
		echo '<link href="' . htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/symbiota/sitemap.css" type="text/css" rel="stylesheet">' . "\r\n";
	}
	?>
	<script type="text/javascript">
		function submitTaxaNoImgForm(f){
			if(f.clid.value != ""){
				f.submit();
			}
			return false;
		}
	</script>
	<script type="text/javascript" src="js/symb/shared.js"></script>
	<style>
		.nested-li {
			margin-left: 1.5em;
		}
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($sitemapMenu)?$sitemapMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="index.php">' . $LANG['HOME'] . '</a> &gt; ';
	echo ' <b>' . $LANG['SITEMAP'] . '</b>';
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['SITEMAP']; ?></h1>
		<div id="sitemap">
			<h2><?php echo $LANG['COLLECTIONS']; ?></h2>
			<ul>
				<li><a href="<?php echo $actionPage ?>"><?php echo htmlspecialchars($LANG['SEARCHENGINE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a> - <?php echo htmlspecialchars($LANG['SEARCH_COLL'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></li>
				<li><a href="collections/misc/collprofiles.php"><?php echo htmlspecialchars($LANG['COLLECTIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a> - <?php echo htmlspecialchars($LANG['LISTOFCOLL'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></li>
				<li><a href="collections/misc/collstats.php"><?php echo htmlspecialchars($LANG['COLLSTATS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
				<?php
				if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
					echo '<li><a href="collections/exsiccati/index.php">' . htmlspecialchars($LANG['EXSICC'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
				}
				?>
				<li><?php echo $LANG['DATA_PUBLISHING']; ?></li>
				<li class="nested-li"><a href="collections/datasets/rsshandler.php" target="_blank"><?php echo htmlspecialchars($LANG['COLLECTIONS_RSS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
				<li class="nested-li"><a href="collections/datasets/datapublisher.php"><?php echo htmlspecialchars($LANG['DARWINCORE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a> - <?php echo htmlspecialchars($LANG['PUBDATA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></li>
				<?php
				$rssPath = 'content/dwca/rss.xml';
				$deprecatedRssPath = 'webservices/dwc/rss.xml';
				if(!file_exists($GLOBALS['SERVER_ROOT'].$rssPath) && file_exists($GLOBALS['SERVER_ROOT'].$deprecatedRssPath)) $rssPath = $deprecatedRssPath;
				if(file_exists($GLOBALS['SERVER_ROOT'].$rssPath)) echo '<li style="margin-left:15px;"><a href="' . htmlspecialchars($GLOBALS['CLIENT_ROOT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . htmlspecialchars($rssPath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($LANG['RSS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
				?>
				<li><a href="collections/misc/protectedspecies.php"><?php echo htmlspecialchars($LANG['PROTECTED_SPECIES'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a> - <?php echo htmlspecialchars($LANG['LISTOFTAXA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></li>
			</ul>
			<div id="imglib">
				<h2><?php echo $LANG['IMGLIB'];?></h2>
			</div>
			<ul>
				<li><a href="imagelib/index.php"><?php echo htmlspecialchars($LANG['IMGLIB'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
				<li><a href="imagelib/search.php"><?php echo htmlspecialchars(($LANG['IMAGE_SEARCH']), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
				<li><a href="imagelib/contributors.php"><?php echo htmlspecialchars($LANG['CONTRIB'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
				<li><a href="includes/usagepolicy.php"><?php echo htmlspecialchars($LANG['USAGEPOLICY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
			</ul>

			<div id="resources">
				<h2><?php echo $LANG['ADDITIONAL_RESOURCES']; ?></h2>
			</div>
			<ul>
				<?php
				if($smManager->hasGlossary()){
					?>
					<li><a href="glossary/index.php"><?php echo htmlspecialchars($LANG['GLOSSARY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
					<?php
				}
				?>
				<li><a href="taxa/taxonomy/taxonomydisplay.php"><?php echo htmlspecialchars($LANG['TAXTREE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
				<li><a href="taxa/taxonomy/taxonomydynamicdisplay.php"><?php echo htmlspecialchars($LANG['DYNTAXTREE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
			</ul>

			<?php
			$clList = $smManager->getChecklistList((array_key_exists('ClAdmin',$USER_RIGHTS)?$USER_RIGHTS['ClAdmin']:0));
			$clAdmin = array();
			if($clList && isset($USER_RIGHTS['ClAdmin'])){
				$clAdmin = array_intersect_key($clList,array_flip($USER_RIGHTS['ClAdmin']));
			}
			?>
			<div id="bioinventory">
				<h2><?php echo $LANG['BIOTIC_INVENTORIES']; ?></h2>
			</div>
			<ul>
				<?php
				$projList = $smManager->getProjectList();
				if($projList){
					foreach($projList as $pid => $pArr){
						echo "<li><a href='projects/index.php?pid=" . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>" . $pArr["name"] . "</a></li>\n";
						echo "<li class='nested-li'>Manager: " . $pArr["managers"] . "</li>\n";
					}
				}
				?>
				<li><a href="checklists/index.php"><?php echo htmlspecialchars($LANG['ALL_CHECKLISTS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
			</ul>

			<div id="datasets">
				<h2><?php echo $LANG['DATASETS'] ;?></h2>
			</div>
			<ul>
				<li><a href="collections/datasets/publiclist.php"><?php echo htmlspecialchars($LANG['ALLPUBDAT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ;?></a></li>
			</ul>
			<div id="dynamiclists"><h2><?php echo $LANG['DYNAMIC']; ?></h2></div>
			<ul>
				<li>
					<a href="checklists/dynamicmap.php?interface=checklist">
						<?php echo $LANG['CHECKLIST'];?>
					</a> - <?php echo $LANG['BUILDCHECK'];?>
				</li>
				<li>
					<a href="checklists/dynamicmap.php?interface=key">
						<?php echo $LANG['DYNAMICKEY'];?>
					</a> - <?php echo $LANG['BUILDDKEY'];?>
				</li>
			</ul>

			<section id="admin" class="fieldset-like">
				<h1>
					<span>
						<?php echo $LANG['MANAGTOOL'];?>
					</span>
				</h1>
			</br>
				<?php
				if($SYMB_UID){
					if($IS_ADMIN){
						?>
						<h2>
							<span>
								<?php echo $LANG['ADMIN'];?>
							</span>
						</h2>
						<ul>
							<li>
								<a href="profile/usermanagement.php"><?php echo htmlspecialchars($LANG['USERPERM'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a>
							</li>
						<?php // TODO: Identification Editor features need to be reviewed and refactored 
						/*
							<li>
								<a href="profile/usertaxonomymanager.php"><?php echo htmlspecialchars($LANG['TAXINTER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a>
							</li>
						*/ 
						?>
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/misc/collmetadata.php">
									<?php echo htmlspecialchars($LANG['CREATENEWCOLL'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>
								</a>
							</li>
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/geothesaurus/index.php">
									<?php echo htmlspecialchars($LANG['GEOTHESAURUS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<!--
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/cleaning/coordinatevalidator.php">
									<?php echo htmlspecialchars($LANG['COORDVALIDATOR'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>
								</a>
							</li>
							-->
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/imagelib/admin/thumbnailbuilder.php">
									<?php echo htmlspecialchars($LANG['THUMBNAIL_BUILDER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>
								</a>
							</li>
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/admin/guidmapper.php">
									<?php echo htmlspecialchars($LANG['GUIDMAP'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>
								</a>
							</li>
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/specprocessor/salix/salixhandler.php">
									<?php echo htmlspecialchars($LANG['SALIX'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>
								</a>
							</li>
							<li>
								<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/glossary/index.php">
									<?php echo htmlspecialchars($LANG['GLOSSARY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<li>
								<a href="collections/map/staticmaphandler.php"><?php echo htmlspecialchars($LANG['MANAGE_TAXON_THUMBNAILS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a>
							</li>
						</ul>
						<?php
					}
					if($KEY_MOD_IS_ACTIVE || array_key_exists("KeyAdmin",$USER_RIGHTS)){
						echo '</br><h2><span>' . $LANG['IDKEYS'] . '<span></h2>';
						if(!$KEY_MOD_IS_ACTIVE && array_key_exists("KeyAdmin",$USER_RIGHTS)){
							?>
							<div id="keymodule">
								<?php echo $LANG['KEYMODULE'];?>
							</div>
							<?php
						}
						?>
						<ul>
							<?php
							if($IS_ADMIN || array_key_exists("KeyAdmin",$USER_RIGHTS)){
								?>
								<li>
									<?php echo htmlspecialchars($LANG['AUTHOKEY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?> <a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/ident/admin/index.php"><?php echo htmlspecialchars($LANG['CHARASTATES'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a>
								</li>
								<?php
							}
							if($IS_ADMIN || array_key_exists("KeyEditor",$USER_RIGHTS) || array_key_exists("KeyAdmin",$USER_RIGHTS)){
								?>
								<li>
									<?php echo $LANG['AUTHIDKEY'];?>
								</li>
								<?php
								//Show Checklists that user has explicit editing rights
								if($clAdmin){
									echo '<li>' . $LANG['CODINGCHARA'] . '</li>';
									echo '<ul>';
									foreach($clAdmin as $vClid => $name){
										echo "<li><a href='" . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "/ident/tools/matrixeditor.php?clid=" . htmlspecialchars($vClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>" . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></li>";
									}
									echo '</ul>';
								}
							}
							else{
								?>
								<li><?php echo $LANG['NOTAUTHIDKEY'];?></li>
								<?php
							}
							?>
						</ul>
						<?php
					}
					?>
					</br>
					<h2>
						<span>
							<?php echo $LANG['IMAGES'];?>
						</span>
					</h2>
					<div id="images">
						<p class="description">
							<?php echo $LANG['SEESYMBDOC'];?>
							<a href="https://biokic.github.io/symbiota-docs/editor/images/"><?php echo htmlspecialchars($LANG['IMGSUB'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a>
							<?php echo $LANG['FORANOVERVIEW'];?>
						</p>
					</div>
					<ul>
						<?php
						if($IS_ADMIN || array_key_exists('TaxonProfile',$USER_RIGHTS)){
							?>
							<li>
								<a href="taxa/profile/tpeditor.php?tabindex=1" target="_blank">
									<?php echo $LANG['BASICFIELD'];?>
								</a>
							</li>
							<?php
						}
						if($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS) || array_key_exists("CollEditor",$USER_RIGHTS)){
							?>
							<li>
								<a href="collections/editor/observationsubmit.php">
									<?php echo $LANG['IMGOBSER'];?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
					</br>
					<h2>
						<span>
							<?php echo $LANG['BIOTIC_INVENTORIES'];?>
						</span>
					</h2>
					<ul>
						<?php
						if($IS_ADMIN){
							echo '<li><a href="projects/index.php?newproj=1">' . htmlspecialchars($LANG['ADDNEWPROJ'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
							if($projList){
								echo '<li><b>' . $LANG['LISTOFCURR'] . '</b> ' . $LANG['CLICKEDIT'] . '</li>';
								foreach($projList as $pid => $pArr){
									echo '<li class="nested-li"><a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/projects/index.php?pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($pArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
								}
							}
							else{
								echo '<li>'.$LANG['NOPROJ'].'</li>';
							}
						}
						else{
							echo '<li>'.$LANG['NOTEDITPROJ'].'</li>';
						}
						?>
					</ul>

					</br>
					<h2>
						<span>
							<?php echo $LANG['DATASETS'] ;?>
						</span>
					</h2>
					<ul>
						<li><a href="collections/datasets/index.php"><?php echo htmlspecialchars($LANG['DATMANPAG'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ;?></a> - <?php echo htmlspecialchars($LANG['DATA_AUTHORIZED_TO_EDIT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?></li>
					</ul>
					</br>
					<h2>
						<span>
							<?php echo $LANG['TAXONPROF'];?>
						</span>
					</h2>
					<?php
					if($IS_ADMIN || array_key_exists("TaxonProfile",$USER_RIGHTS)){
						?>
						<p class="description">
							<?php echo $LANG['THEFOLLOWINGSPEC'];?>
					</p>
						<ul>
							<li><a href="taxa/profile/tpeditor.php?taxon="><?php echo htmlspecialchars($LANG['SYN_COM'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<li><a href="taxa/profile/tpeditor.php?taxon=&tabindex=4"><?php echo htmlspecialchars($LANG['TEXTDESC'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<li><a href="taxa/profile/tpeditor.php?taxon=&tabindex=1"><?php echo htmlspecialchars($LANG['EDITIMG'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<li class="nested-li"><a href="taxa/profile/tpeditor.php?taxon=&category=imagequicksort&tabindex=2"><?php echo $LANG['IMGSORTORD'];?></a></li>
							<li class="nested-li"><a href="taxa/profile/tpeditor.php?taxon=&category=imageadd&tabindex=3"><?php echo htmlspecialchars($LANG['ADDNEWIMG'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
						</ul>
						<?php
					}
					else{
						?>
						<ul>
							<li><?php echo $LANG['NOTAUTHOTAXONPAGE'];?></li>
						</ul>
						<?php
					}
					?>
					</br>
					<h2>
						<span>
							<?php echo $LANG['TAXONOMY'];?>
						</span>
					</h2>
					<ul>
						<?php
						if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
							?>
							<li><?php echo $LANG['EDITTAXPL'];?> <a href="taxa/taxonomy/taxonomydisplay.php"><?php echo htmlspecialchars($LANG['TAXTREEVIEW'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<li><a href="taxa/taxonomy/taxonomyloader.php"><?php echo htmlspecialchars($LANG['ADDTAXANAME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<li><a href="taxa/taxonomy/batchloader.php"><?php echo htmlspecialchars($LANG['BATCHTAXA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
							<?php
							if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
								?>
								<li><a href="taxa/profile/eolmapper.php"><?php echo htmlspecialchars($LANG['EOLLINK'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
								<?php
							}
						}
						else{
							echo '<li>' . $LANG['NOTEDITTAXA'] . '</li>';
						}
						?>
					</ul>

					</br>
					<h2>
						<span>
							<?php echo $LANG['CHECKLISTS'];?>
						</span>
					</h2>
					<p class="description">
						<?php echo $LANG['TOOLSFORMANAGE'];?>.
					</p>
					<ul>
						<?php
						if($clAdmin){
							foreach($clAdmin as $k => $v){
								echo "<li><a href='" . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "/checklists/checklist.php?clid=" . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&emode=1'>$v</a></li>";
							}
						}
						else{
							echo "<li>" . $LANG['NOTEDITCHECK'] . "</li>";
						}
						?>
					</ul>

					<?php
					if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
						?>
						</br>
						<h2>
							<span>
								<?php echo $LANG['EXSICCATII'];?>
							</span>
						</h2>
						<p class="description">
							<?php echo $LANG['ESCMOD'];?>.
						</p>
						<ul>
							<li><a href="collections/exsiccati/index.php"><?php echo htmlspecialchars($LANG['EXSICC'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a></li>
						</ul>
						<?php
					}
					?>

					</br>
					<h2>
						<span>
							<?php echo $LANG['COLLECTIONS'];?>
						</span>
					</h2>
					<p class="description">
						<?php echo $LANG['PARA1'];?>
					</p>
					</br>
					<h3>
						<span>
							<?php echo $LANG['COLLLIST'];?>
						</span>
					</h3>
					<div>
						<ul>
						<?php
						$smManager->setCollectionList();
						if($collList = $smManager->getCollArr()){
							foreach($collList as $k => $cArr){
								echo '<li>';
								echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">';
								echo $cArr['name'];
								echo '</a>';
								echo '</li>';
							}
						}
						else{
							echo "<li>".$LANG['NOEDITCOLL']."</li>";
						}
						?>
						</ul>
					</div>

					</br>
					<h2>
						<span>
							<?php echo $LANG['OBSERV'];?>
						</span>
					</h2>
					<p class="description">
						<?php echo $LANG['PARA2'];?>
						<a href="https://biokic.github.io/symbiota-docs/col_obs/" target="_blank"><?php echo htmlspecialchars($LANG['SYMBDOCU'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a> <?php echo htmlspecialchars($LANG['FORMOREINFO'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>.
					<p class="description">
					</br>
					<h3>
						<span>
							<?php echo $LANG['OIVS'];?>
						</span>
					</h3>
					<div>
						<ul>
							<?php
							$obsList = $smManager->getObsArr();
							$genObsList = $smManager->getGenObsArr();
							$obsManagementStr = '';

							if($obsList){
								foreach($genObsList as $k => $oArr){
									?>
									<li>
										<a href="collections/editor/observationsubmit.php?collid=<?php echo htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
									if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($oArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></li>\n";
								}
								foreach($obsList as $k => $oArr){
									?>
									<li>
										<a href="collections/editor/observationsubmit.php?collid=<?php echo htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
									if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($oArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></li>\n";
								}
							}
							else{
								echo "<li>" . $LANG['NOOBSPROJ'] ."</li>";
							}
							?>
						</ul>
						<?php
						if($genObsList){
							?>
							</br>
							<h3><span>
									<?php echo $LANG['PERSONAL'];?>
								</span>
							</h3>
							<ul>
								<?php
								foreach($genObsList as $k => $oArr){
									?>
									<li>
										<a href="collections/misc/collprofiles.php?collid=<?php echo htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
								}
								?>
							</ul>
							<?php
						}
						if($obsManagementStr){
							?>
							</br>
							<h3>
								<span>
									<?php echo $LANG['OPM'];?>
								</span>
							</h3>
							<ul>
								<?php echo $obsManagementStr; ?>
							</ul>
						<?php
						}
					?>
					</div>
					<?php
				}
				else{
					echo '' . $LANG['PLEASE'] . ' <a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/profile/index.php?refurl=../sitemap.php">' . htmlspecialchars($LANG['LOGIN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>' . htmlspecialchars($LANG['TOACCESS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '<br/>' . htmlspecialchars($LANG['CONTACTPORTAL'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '.';
				}
			?>
			</section>
			<div id="symbiotaschema">
				<img src="https://img.shields.io/badge/Symbiota-v<?php echo $CODE_VERSION; ?>-blue.svg" alt="a blue badge depicting Symbiota software version" />
				<img src="https://img.shields.io/badge/Schema-<?php echo 'v'.$smManager->getSchemaVersion(); ?>-blue.svg" alt="a blue badge depicting Symbiota database schema version" />
			</div>
		</div>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
</html>
