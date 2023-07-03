<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/SiteMapManager.php');
include_once($SERVER_ROOT.'/content/lang/sitemap.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$smManager = new SiteMapManager();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['SITEMAP'];?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');

	//detect custom css file
	if(file_exists($_SERVER['DOCUMENT_ROOT'].$CSS_BASE_PATH.'/symbiota/sitemap.css')){
		echo '<link href="' . $CSS_BASE_PATH . '/symbiota/sitemap.css" type="text/css" rel="stylesheet">'."\r\n";
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
</head>
<body>
	<?php
	$displayLeftMenu = (isset($sitemapMenu)?$sitemapMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="index.php">'.$LANG['HOME'].'</a> &gt; ';
	echo ' <b>'.$LANG['SITEMAP'].'</b>';
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1><?php echo $LANG['SITEMAP']; ?></h1>
		<div id="sitemap">
			<h2><?php echo $LANG['COLLECTIONS']; ?></h2>
			<ul>
				<li><a href="collections/index.php"><?php echo $LANG['SEARCHENGINE'];?></a> - <?php echo $LANG['SEARCH_COLL'];?></li>
				<li><a href="collections/misc/collprofiles.php"><?php echo $LANG['COLLECTIONS'];?></a> - <?php echo $LANG['LISTOFCOLL'];?></li>
				<li><a href="collections/misc/collstats.php"><?php echo $LANG['COLLSTATS'];?></a></li>
				<?php
				if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
					echo '<li><a href="collections/exsiccati/index.php">'.$LANG['EXSICC'].'</a></li>';
				}
				?>
				<li><?php echo (isset($LANG['DATA_PUBLISHING'])?$LANG['DATA_PUBLISHING']:'Data Publishing');?></li>
				<ul>
					<li><a href="collections/datasets/rsshandler.php" target="_blank"><?php echo $LANG['COLLECTIONS_RSS'];?></a></li>
					<li><a href="collections/datasets/datapublisher.php"><?php echo $LANG['DARWINCORE'];?></a> - <?php echo $LANG['PUBDATA'];?></li>
				</ul>
				<?php
				$rssPath = 'content/dwca/rss.xml';
				$deprecatedRssPath = 'webservices/dwc/rss.xml';
				if(!file_exists($GLOBALS['SERVER_ROOT'].$rssPath) && file_exists($GLOBALS['SERVER_ROOT'].$deprecatedRssPath)) $rssPath = $deprecatedRssPath;
				if(file_exists($GLOBALS['SERVER_ROOT'].$rssPath)) echo '<li style="margin-left:15px;"><a href="'.$GLOBALS['CLIENT_ROOT'].$rssPath.'" target="_blank">'.$LANG['RSS'].'</a></li>';
				?>
				<li><a href="collections/misc/protectedspecies.php"><?php echo $LANG['PROTECTED_SPECIES'];?></a> - <?php echo $LANG['LISTOFTAXA'];?></li>
			</ul>
			<div id="imglib"><h2><?php echo $LANG['IMGLIB'];?></h2></div>
			<ul>
				<li><a href="imagelib/index.php"><?php echo $LANG['IMGLIB'];?></a></li>
				<li><a href="imagelib/search.php"><?php echo ($LANG['IMAGE_SEARCH']?$LANG['IMAGE_SEARCH']:'Interactive Search Tool'); ?></a></li>
				<li><a href="imagelib/contributors.php"><?php echo $LANG['CONTRIB'];?></a></li>
				<li><a href="includes/usagepolicy.php"><?php echo $LANG['USAGEPOLICY'];?></a></li>
			</ul>

			<div id="resources"><h2><?php echo isset($LANG['ADDITIONAL_RESOURCES'])?$LANG['ADDITIONAL_RESOURCES']:'Additional Resources';?></h2></div>
			<ul>
				<?php
				if($smManager->hasGlossary()){
					?>
					<li><a href="glossary/index.php"><?php echo isset($LANG['GLOSSARY'])?$LANG['GLOSSARY']:'Glossary';?></a></li>
					<?php
				}
				?>
				<li><a href="taxa/taxonomy/taxonomydisplay.php"><?php echo $LANG['TAXTREE'];?></a></li>
				<li><a href="taxa/taxonomy/taxonomydynamicdisplay.php"><?php echo $LANG['DYNTAXTREE'];?></a></li>
			</ul>

			<?php
			$clList = $smManager->getChecklistList((array_key_exists('ClAdmin',$USER_RIGHTS)?$USER_RIGHTS['ClAdmin']:0));
			$clAdmin = array();
			if($clList && isset($USER_RIGHTS['ClAdmin'])){
				$clAdmin = array_intersect_key($clList,array_flip($USER_RIGHTS['ClAdmin']));
			}
			?>
			<div id="bioinventory"><h2><?php echo (isset($LANG['BIOTIC_INVENTORIES'])?$LANG['BIOTIC_INVENTORIES']:'Biotic Inventory Projects'); ?></h2></div>
			<ul>
				<?php
				$projList = $smManager->getProjectList();
				if($projList){
					foreach($projList as $pid => $pArr){
						echo "<li><a href='projects/index.php?pid=".$pid."'>".$pArr["name"]."</a></li>\n";
						echo "<ul><li>Manager: ".$pArr["managers"]."</li></ul>\n";
					}
				}
				?>
				<li><a href="checklists/index.php"><?php echo (isset($LANG['ALL_CHECKLISTS'])?$LANG['ALL_CHECKLISTS']:'All Public Checklists'); ?></a></li>
			</ul>

			<h2><?php echo (isset($LANG['DATASETS'])?$LANG['DATASETS']:'Datasets') ;?></h2>
			<ul>
				<li><a href="collections/datasets/publiclist.php"><?php echo (isset($LANG['ALLPUBDAT'])?$LANG['ALLPUBDAT']:'All Publicly Viewable Datasets') ;?></a></li>
			</ul>
			<div id="dynamiclists"><h2><?php echo $LANG['DYNAMIC'];?></h2></div>
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

			<fieldset id="admin">
				<legend><b><?php echo $LANG['MANAGTOOL'];?></b></legend>
				<?php
				if($SYMB_UID){
					if($IS_ADMIN){
						?>
						<h3><?php echo $LANG['ADMIN'];?></h3>
						<ul>
							<li>
								<a href="profile/usermanagement.php"><?php echo $LANG['USERPERM'];?></a>
							</li>
							<li>
								<a href="profile/usertaxonomymanager.php"><?php echo $LANG['TAXINTER'];?></a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/misc/collmetadata.php">
									<?php echo $LANG['CREATENEWCOLL'];?>
								</a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/geothesaurus/index.php">
									<?php echo isset($LANG['GEOTHESAURUS'])?$LANG['GEOTHESAURUS']:'Geographic Thesaurus'; ?>
								</a>
							</li>
							<!--
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/cleaning/coordinatevalidator.php">
									<?php echo isset($LANG['COORDVALIDATOR'])?$LANG['COORDVALIDATOR']:'Verify coordinates against political boundaries';?>
								</a>
							</li>
							-->
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/admin/thumbnailbuilder.php">
									<?php echo $LANG['THUMBNAIL_BUILDER'];?>
								</a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/admin/guidmapper.php">
									<?php echo $LANG['GUIDMAP'];?>
								</a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/specprocessor/salix/salixhandler.php">
									<?php echo $LANG['SALIX'];?>
								</a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/glossary/index.php">
									<?php echo $LANG['GLOSSARY'];?>
								</a>
							</li>
						</ul>
						<?php
					}
					if($KEY_MOD_IS_ACTIVE || array_key_exists("KeyAdmin",$USER_RIGHTS)){
						echo '<h3>'.$LANG['IDKEYS'].'</h3>';
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
									<?php echo $LANG['AUTHOKEY'];?> <a href="<?php echo $CLIENT_ROOT; ?>/ident/admin/index.php"><?php echo $LANG['CHARASTATES'];?></a>
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
									echo '<li>'.$LANG['CODINGCHARA'].'</li>';
									echo '<ul>';
									foreach($clAdmin as $vClid => $name){
										echo "<li><a href='".$CLIENT_ROOT."/ident/tools/matrixeditor.php?clid=".$vClid."'>".$name."</a></li>";
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
					<h3><?php echo $LANG['IMAGES'];?></h3>
					<div id="images">
						<p class="description">
							<?php echo $LANG['SEESYMBDOC'];?>
							<a href="https://biokic.github.io/symbiota-docs/editor/images/"><?php echo $LANG['IMGSUB'];?></a>
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

					<h3><?php echo $LANG['BIOTIC_INVENTORIES'];?></h3>
					<ul>
						<?php
						if($IS_ADMIN){
							echo '<li><a href="projects/index.php?newproj=1">'.$LANG['ADDNEWPROJ'].'</a></li>';
							if($projList){
								echo '<li><b>'.$LANG['LISTOFCURR'].'</b> '.$LANG['CLICKEDIT'].'</li>';
								echo '<ul>';
								foreach($projList as $pid => $pArr){
									echo '<li><a href="'.$CLIENT_ROOT.'/projects/index.php?pid='.$pid.'&emode=1">'.$pArr['name'].'</a></li>';
								}
								echo '</ul>';
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

					<h3><?php echo (isset($LANG['DATASETS'])?$LANG['DATASETS']:'Datasets') ;?></h3>
					<ul>
						<li><a href="collections/datasets/index.php"><?php echo (isset($LANG['DATMANPAG'])?$LANG['DATMANPAG']:'Dataset Management Page</a> - datasets you are authorized to edit') ;?></li>
					</ul>
					<h3><?php echo $LANG['TAXONPROF'];?></h3>
					<?php
					if($IS_ADMIN || array_key_exists("TaxonProfile",$USER_RIGHTS)){
						?>
						<p class="description">
							<?php echo $LANG['THEFOLLOWINGSPEC'];?>
					</p>
						<ul>
							<li><a href="taxa/profile/tpeditor.php?taxon="><?php echo $LANG['SYN_COM'];?></a></li>
							<li><a href="taxa/profile/tpeditor.php?taxon=&tabindex=4"><?php echo $LANG['TEXTDESC'];?></a></li>
							<li><a href="taxa/profile/tpeditor.php?taxon=&tabindex=1"><?php echo $LANG['EDITIMG'];?></a></li>
							<ul>
								<li><a href="taxa/profile/tpeditor.php?taxon=&category=imagequicksort&tabindex=2"><?php echo $LANG['IMGSORTORD'];?></a></li>
								<li><a href="taxa/profile/tpeditor.php?taxon=&category=imageadd&tabindex=3"><?php echo $LANG['ADDNEWIMG'];?></a></li>
							</ul>
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
					<h3><?php echo $LANG['TAXONOMY'];?></h3>
					<ul>
						<?php
						if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
							?>
							<li><?php echo $LANG['EDITTAXPL'];?> <a href="taxa/taxonomy/taxonomydisplay.php"><?php echo $LANG['TAXTREEVIEW'];?></a></li>
							<li><a href="taxa/taxonomy/taxonomyloader.php"><?php echo $LANG['ADDTAXANAME'];?></a></li>
							<li><a href="taxa/taxonomy/batchloader.php"><?php echo $LANG['BATCHTAXA'];?></a></li>
							<?php
							if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
								?>
								<li><a href="taxa/profile/eolmapper.php"><?php echo $LANG['EOLLINK'];?></a></li>
								<?php
							}
						}
						else{
							echo '<li>'.$LANG['NOTEDITTAXA'].'</li>';
						}
						?>
					</ul>

					<h3><?php echo $LANG['CHECKLISTS'];?></h3>
					<p class="description">
						<?php echo $LANG['TOOLSFORMANAGE'];?>.
					</p>
					<ul>
						<?php
						if($clAdmin){
							foreach($clAdmin as $k => $v){
								echo "<li><a href='".$CLIENT_ROOT."/checklists/checklist.php?clid=".$k."&emode=1'>$v</a></li>";
							}
						}
						else{
							echo "<li>".$LANG['NOTEDITCHECK']."</li>";
						}
						?>
					</ul>

					<?php
					if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
						?>
						<h3><?php echo $LANG['EXSICCATII'];?></h3>
						<p class="description">
							<?php echo $LANG['ESCMOD'];?>.
						</p>
						<ul>
							<li><a href="collections/exsiccati/index.php"><?php echo $LANG['EXSICC'];?></a></li>
						</ul>
						<?php
					}
					?>

					<h3><?php echo $LANG['COLLECTIONS'];?></h3>
					<p class="description">
						<?php echo $LANG['PARA1'];?>
					</p>
					<div id="admincollection">
						<h4>
							<?php echo $LANG['COLLLIST'];?>
						</h4>
						<ul>
						<?php
						$smManager->setCollectionList();
						if($collList = $smManager->getCollArr()){
							foreach($collList as $k => $cArr){
								echo '<li>';
								echo '<a href="'.$CLIENT_ROOT.'/collections/misc/collprofiles.php?collid='.$k.'&emode=1">';
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

					<h3><?php echo $LANG['OBSERV'];?></h3>
					<p class="description">
						<?php echo $LANG['PARA2'];?>
						<a href="https://symbiota.org/specimen-data-management/" target="_blank"><?php echo $LANG['SYMBDOCU'];?></a> <?php echo $LANG['FORMOREINFO'];?>.
					<p class="description">
					<div id="adminobservation">
						<h4>
							<?php echo $LANG['OIVS'];?>
						</h4>
						<ul>
							<?php
							$obsList = $smManager->getObsArr();
							$genObsList = $smManager->getGenObsArr();
							$obsManagementStr = '';

							if($obsList){
								foreach($genObsList as $k => $oArr){
									?>
									<li>
										<a href="collections/editor/observationsubmit.php?collid=<?php echo $k; ?>">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
									if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid='.$k.'&emode=1">'.$oArr['name']."</a></li>\n";
								}
								foreach($obsList as $k => $oArr){
									?>
									<li>
										<a href="collections/editor/observationsubmit.php?collid=<?php echo $k; ?>">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
									if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid='.$k.'&emode=1">'.$oArr['name']."</a></li>\n";
								}
							}
							else{
								echo "<li>".$LANG['NOOBSPROJ']."</li>";
							}
							?>
						</ul>
						<?php
						if($genObsList){
							?>
							<h4>
								<?php echo $LANG['PERSONAL'];?>
							</h4>
							<ul>
								<?php
								foreach($genObsList as $k => $oArr){
									?>
									<li>
										<a href="collections/misc/collprofiles.php?collid=<?php echo $k; ?>&emode=1">
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
							<h4>
								<?php echo $LANG['OPM'];?>
							</h4>
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
					echo ''.$LANG['PLEASE'].' <a href="'.$CLIENT_ROOT.'/profile/index.php?refurl=../sitemap.php">'.$LANG['LOGIN'].'</a>'.$LANG['TOACCESS'].'<br/>'.$LANG['CONTACTPORTAL'].'.';
				}
			?>
			</fieldset>
			<div id="symbiotaschema">
				<img src="https://img.shields.io/badge/Symbiota-v<?php echo $CODE_VERSION; ?>-blue.svg" />
				<img src="https://img.shields.io/badge/Schema-<?php echo 'v'.$smManager->getSchemaVersion(); ?>-blue.svg" />
			</div>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
