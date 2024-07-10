<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
include_once($SERVER_ROOT.'/content/lang/collections/cleaning/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST) ? $_REQUEST['collid'] : 0;

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = current($cleanManager->getCollMap());

$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])) || ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations'){
	$cleanManager->setObsUid($SYMB_UID);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title> <?php echo $DEFAULT_TITLE . ' ' . (isset($LANG['OCC_CLEANER']) ? $LANG['OCC_CLEANER'] : 'Occurrence Cleaner')?> </title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<style>
		.max-width-fit-65 {
			max-width: 100%;
			width: 65rem;
		}
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php"> <?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home') ?> </a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"> <?php echo (isset($LANG['COLL_MNGMT']) ? $LANG['COLL_MNGMT'] : 'Collection Management') ?> </a> &gt;&gt;
		<b> <?php echo (isset($LANG['DATA_CLEAN']) ? $LANG['DATA_CLEAN'] : 'Data Cleaning Module') ?> </b>
	</div>
	
	<!-- inner text -->
	<div role="main" id="innertext" style="background-color:white;">
		<?php
		if($isEditor){
			echo '<h1 class="page-heading">Data Cleaning Tools: ' . $collMap['collectionname'] .' (' . $collMap['code'] . ')</h1>';
			?>
			<div style="color:orange;margin:20px 0px"> <?php echo (isset($LANG['DOWNLOAD_BACKUP']) ? $LANG['DOWNLOAD_BACKUP'] : 'Downloading a backup of your collection data before running any batch updates is strongly recommended') ?> </div>
			<?php
			if($collMap['colltype'] != 'General Observations'){
				?>
				<h2> <?php echo (isset($LANG['DUPLICATE_RECS']) ? $LANG['DUPLICATE_RECS'] : 'Duplicate Records') ?> </h2>
				<div style="margin:0px 0px 40px 15px;">
					<div>
						<?php echo (isset($LANG['DUPL_REC_DESCR']) ? $LANG['DUPL_REC_DESCR'] : 'These tools will assist in searching this collection of records for duplicate records of the same specimen.
																								If duplicate records exist, this feature offers the ability to merge record values, images,
																								and data relationships into a single record.') ?>
					</div>
					<section class="fieldset-like max-width-fit-65 ">
						<h3>
							<span>
								<?php echo (isset($LANG['LIST_DUPLICATES']) ? $LANG['LIST_DUPLICATES'] : 'List Duplicates based on...') ?>
							</span>
						</h3>
						<ul>
							<li>
								<a href="duplicatesearch.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&action=listdupscatalog">
									<?php echo (isset($LANG['CAT_NUMS']) ? $LANG['CAT_NUMS'] : 'Catalog Numbers') ?>
								</a>
							</li>
							<li>
								<a href="duplicatesearch.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&action=listdupsothercatalog">
									<?php echo (isset($LANG['OTHER_CAT_NUMS']) ? $LANG['OTHER_CAT_NUMS'] : 'Other Catalog Numbers') ?>
								</a>
							</li>
							<!--
							<li>
								<a href="duplicatesearch.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&action=listdupsrecordedby">
									Collector/Observer and numbers
								</a>
							</li>
							 -->
						</ul>
					</section>
				</div>
				<?php
			}
			?>

			<h2> <?php echo $LANG['POLITIC_GEO'] ?> </h2>
			<div style="margin:0px 0px 40px 15px;">
				<div>
					<?php echo (isset($LANG['POLITIC_GEO_DESCR']) ? $LANG['POLITIC_GEO_DESCR'] : 'These tools help standardize country, state/province, and county designations.
																					They are also useful for locating and correcting misspelled geographical political units,
																					and even mismatched units, such as a state designation that does not match the wrong country.') ?>
				</div>
				<section class="fieldset-like max-width-fit-65 ">
						<h3>
							<span>
								<?php echo (isset($LANG['STAT_ACT_PANEL']) ? $LANG['STAT_ACT_PANEL'] : 'Statistics and Action Panel') ?>
							</span>
						</h3>
					<ul>
						<li>
							<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&stat=geography#geographystats" target="_blank"> <?php echo (isset($LANG['GEO_DISTR']) ? $LANG['GEO_DISTR'] : 'Geographic Distributions') ?> </a>
						</li>
						<li>
							<a href="politicalunits.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"> <?php echo (isset($LANG['GEO_CLEAN_TOOLS']) ? $LANG['GEO_CLEAN_TOOLS'] : 'Geography Cleaning Tools') ?> </a>
						</li>
					</ul>
				</section>
			</div>
<!--
			<h2>Specimen Coordinates</h2>
			<div style="margin:0px 0px 40px 15px;">
				<div>
					These tools are to aid collection managers in verifying, ranking, and managing coordinate information associated with occurrence records.
				</div>
				<fieldset style="margin:10px 0px;padding:5px;width:550px">
					<legend style="font-weight:bold">Statistics and Action Panel</legend>
					<ul>
						<?php
						$statsArr = $cleanManager->getCoordStats();
						?>
						<li>Georeferenced: <?php echo $statsArr['coord']; ?>
							<?php
							if($statsArr['coord']){
								?>
								<a href="../editor/occurrencetabledisplay.php?collid=<?= $collid ?>&occindex=0&q_catalognumber=&q_customfield1=decimallatitude&q_customtype1=NOT_NULL" style="margin-left:5px;" title="Open Editor" target="_blank">
									<img src="../../images/edit.png" style="width:10px" />
								</a>
								<?php
							}
							?>
						</li>
						<li>Lacking coordinates: <?php echo $statsArr['noCoord']; ?>
							<?php
							if($statsArr['noCoord']){
								?>
								<a href="../editor/occurrencetabledisplay.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&occindex=0&q_catalognumber=&q_customfield1=decimallatitude&q_customtype1=IS_NULL" style="margin-left:5px;" title="Open Editor" target="_blank">
									<img src="../../images/edit.png" style="width:10px" />
								</a>
								<a href="../georef/batchgeoreftool.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" style="margin-left:5px;" title="Open Batch Georeference Tool" target="_blank">
									<img src="../../images/edit.png" style="width:10px" /><span style="font-size:70%;margin-left:-3;">b-geo</span>
								</a>
								<?php
							}
							?>
						</li>
						<li style="margin-left:15px">Lacking coordinates with verbatim coordinates: <?php echo $statsArr['noCoord_verbatim']; ?>
							<?php
							if($statsArr['noCoord_verbatim']){
								?>
								<a href="../editor/occurrencetabledisplay.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&occindex=0&q_catalognumber=&q_customfield1=decimallatitude&q_customtype1=IS_NULL&q_customfield2=verbatimcoordinates&q_customtype2=NOT_NULL" style="margin-left:5px;" title="Open Editor" target="_blank">
									<img src="../../images/edit.png" style="width:10px" />
								</a>
								<?php
							}
							?>
						</li>
						<li style="margin-left:15px">Lacking coordinates without verbatim coordinates: <?php echo $statsArr['noCoord_noVerbatim']; ?>
							<?php
							if($statsArr['noCoord_noVerbatim']){
								?>
								<a href="../editor/occurrencetabledisplay.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&occindex=0&q_catalognumber=&q_customfield1=decimallatitude&q_customtype1=NULL&q_customfield2=verbatimcoordinates&q_customtype2=NULL" style="margin-left:5px;" title="Open Editor" target="_blank">
									<img src="../../images/edit.png" style="width:10px" />
								</a>
								<?php
							}
							?>
						</li>
						<li>
							<a href="coordinatevalidator.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">Verify coordinates against political boundaries</a>
						</li>
					</ul>
				</fieldset>
			</div>
 -->
			<h2> <?php echo (isset($LANG['TAXONOMY']) ? $LANG['TAXONOMY'] : 'Taxonomy') ?> </h2>
			<div style="margin:0px 0px 40px 15px;">
				<div>
					<?php echo (isset($LANG['TAXONOMY_DESCR']) ? $LANG['TAXONOMY_DESCR'] : 'These tools are meant to aid in locating and fixing taxonomic errors and inconsistencies.') ?>
				</div>
				<section class="fieldset-like max-width-fit-65 ">
					<h2> <span> <?php echo (isset($LANG['STAT_ACT_PANEL']) ? $LANG['STAT_ACT_PANEL'] : 'Statistics and Action Panel') ?> </span> </h2>
					<ul>
						<li><a href="taxonomycleaner.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"> <?php echo (isset($LANG['ANALYZE_NAMES']) ? $LANG['ANALYZE_NAMES'] : 'Analyze taxonomic names...') ?> </a></li>
						<li><a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&stat=taxonomy#taxonomystats"> <?php echo (isset($LANG['TAXON_DISTR']) ? $LANG['TAXON_DISTR'] : 'Taxonomic Distributions...') ?> </a></li>
						<?php
						if($cleanManager->hasDuplicateClusters()){
							echo '<li><a href="../datasets/duplicatemanager.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&dupedepth=3&action=listdupeconflicts">';
							echo (isset($LANG['DUPLICATE_SPECIMENS']) ? $LANG['DUPLICATE_SPECIMENS'] : 'Duplicate specimens with potential identification conflicts...');
							echo '</a></li>';
						}
						?>
					</ul>
				</section>
			</div>
			<!--
			<h3>Identification</h3>
			<div style="margin:0px 0px 40px 15px;">
				<div>
					These tools are to aid collection managers in identifications associated with occurrence records.

				</div>
				<div style="margin:15px 0px;color:orange">
					-- IN DEVELOPMENT - more to come soon --
				</div>
				<div>
					<div style="font-weight:bold">Ranking Statistics</div>
					<?php
					/*
					$idRankingArr = $cleanManager->getRankingStats('identification');
					$rankArr = current($idRankingArr);
					echo '<table class="styledtable">';
					echo '<tr><th>Ranking</th><th>Protocol</th><th>Count</th></tr>';
					foreach($rankArr as $rank => $protocolArr){
						foreach($protocolArr as $protocol => $cnt){
							echo '<tr>';
							echo '<td>'.$rank.'</td>';
							echo '<td>'.$protocol.'</td>';
							echo '<td>'.$cnt.'</td>';
							echo '</tr>';
						}
					}
					echo '</table>';
					*/
					?>
				</div>
			</div>
			 -->
			<?php
		}
		else{
			echo '<h2> ' . (isset($LANG['NOT_AUTH']) ? $LANG['NOT_AUTH'] : 'You are not authorized to access this page.') . ' </h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
