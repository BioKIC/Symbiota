<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/collstats.' . $LANG_TAG . '.php');
header("Content-Type: text/html; charset=" . $CHARSET);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

$catID = array_key_exists('catid', $_REQUEST) ? filter_var($_REQUEST['catid'], FILTER_SANITIZE_NUMBER_INT) : 0;
if(!$catID && isset($DEFAULTCATID) && $DEFAULTCATID) $catID = $DEFAULTCATID;
$collId = array_key_exists('collid', $_REQUEST) ? $_REQUEST['collid'] : 0; // can't sanitize here as int because this could be a comma-delimited set of collIds

$cPartentTaxon = isset($_REQUEST['taxon']) ? htmlspecialchars($_REQUEST['taxon'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$cCountry = isset($_REQUEST['country']) ? htmlspecialchars($_REQUEST['country'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$days = array_key_exists('days', $_REQUEST) ? filter_var($_REQUEST['days'], FILTER_SANITIZE_NUMBER_INT) : 365;
$months = array_key_exists('months', $_REQUEST)? filter_var($_REQUEST['months'], FILTER_SANITIZE_NUMBER_INT) : 12;
$action = array_key_exists('submitaction', $_REQUEST) ? htmlspecialchars($_REQUEST['submitaction'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';

$collManager = new OccurrenceCollectionProfile();

//Variable sanitation
if(!preg_match('/^[0-9,]+$/',$catID)) $catID = 0;
if(!preg_match('/^[0-9,]+$/',$collId)) $collId = 0;

//if($collId) $collManager->setCollectionId($collId);
$collList = $collManager->getStatCollectionList($catID);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

$collIdArr = array();
$resultsTemp = array();
$familyArr = array();
$countryArr = array();
$results = array();
$collStr = '';
if($collId){
	$collIdArr = explode(",",$collId);

	if($action == "Run Statistics" && (!$cPartentTaxon && !$cCountry)){
		$resultsTemp = $collManager->runStatistics($collId);
		$results['FamilyCount'] = $resultsTemp['familycnt'];
		$results['GeneraCount'] = $resultsTemp['genuscnt'];
		$results['SpeciesCount'] = $resultsTemp['speciescnt'];
		$results['TotalTaxaCount'] = $resultsTemp['TotalTaxaCount'];
		$results['TotalImageCount'] = $resultsTemp['TotalImageCount'];
		unset($resultsTemp['familycnt']);
		unset($resultsTemp['genuscnt']);
		unset($resultsTemp['speciescnt']);
		unset($resultsTemp['TotalTaxaCount']);
		unset($resultsTemp['TotalImageCount']);
		ksort($resultsTemp, SORT_STRING | SORT_FLAG_CASE);
		$c = 0;
		foreach($resultsTemp as $k => $collArr){
			$dynPropTempArr = array();
			$familyTempArr = array();
			$countryTempArr = array();
			if($c>0) $collStr .= ", ";
			$collStr .= $collArr['CollectionName'];
			if(array_key_exists("SpecimenCount",$results)){
				$results['SpecimenCount'] = $results['SpecimenCount'] + $collArr['recordcnt'];
			}
			else{
				$results['SpecimenCount'] = $collArr['recordcnt'];
			}

			if(array_key_exists("GeorefCount",$results)){
				$results['GeorefCount'] = $results['GeorefCount'] + $collArr['georefcnt'];
			}
			else{
				$results['GeorefCount'] = $collArr['georefcnt'];
			}

			if($collArr['dynamicProperties']){
				try {
					$dynPropTempArr = json_decode($collArr['dynamicProperties'], true);
				} catch (Exception $e) {
					error_log('Exception: ' . $e->getMessage());
				}

				if(is_array($dynPropTempArr)){
					$resultsTemp[$k]['speciesID'] = $dynPropTempArr['SpecimensCountID'];
					$resultsTemp[$k]['types'] = $dynPropTempArr['TypeCount'];

					if(array_key_exists("SpecimensCountID",$results)){
						$results['SpecimensCountID'] = $results['SpecimensCountID'] + $dynPropTempArr['SpecimensCountID'];
					}
					else{
						$results['SpecimensCountID'] = $dynPropTempArr['SpecimensCountID'];
					}

					if(array_key_exists("TypeCount",$results)){
						$results['TypeCount'] = $results['TypeCount'] + $dynPropTempArr['TypeCount'];
					}
					else{
						$results['TypeCount'] = $dynPropTempArr['TypeCount'];
					}

					if(array_key_exists("families",$dynPropTempArr)){
						$familyTempArr = $dynPropTempArr['families'];
						foreach($familyTempArr as $k => $famArr){
							if(array_key_exists($k,$familyArr)){
								$familyArr[$k]['SpecimensPerFamily'] = $familyArr[$k]['SpecimensPerFamily'] + $famArr['SpecimensPerFamily'];
								$familyArr[$k]['GeorefSpecimensPerFamily'] = $familyArr[$k]['GeorefSpecimensPerFamily'] + $famArr['GeorefSpecimensPerFamily'];
								$familyArr[$k]['IDSpecimensPerFamily'] = $familyArr[$k]['IDSpecimensPerFamily'] + $famArr['IDSpecimensPerFamily'];
								$familyArr[$k]['IDGeorefSpecimensPerFamily'] = $familyArr[$k]['IDGeorefSpecimensPerFamily'] + $famArr['IDGeorefSpecimensPerFamily'];
							}
							else{
								$familyArr[$k]['SpecimensPerFamily'] = $famArr['SpecimensPerFamily'];
								$familyArr[$k]['GeorefSpecimensPerFamily'] = $famArr['GeorefSpecimensPerFamily'];
								$familyArr[$k]['IDSpecimensPerFamily'] = $famArr['IDSpecimensPerFamily'];
								$familyArr[$k]['IDGeorefSpecimensPerFamily'] = $famArr['IDGeorefSpecimensPerFamily'];
							}
						}
						ksort($familyArr, SORT_STRING | SORT_FLAG_CASE);
					}

					if(array_key_exists("countries",$dynPropTempArr)){
						$countryTempArr = $dynPropTempArr['countries'];
						foreach($countryTempArr as $k => $countArr){
							if(array_key_exists($k,$countryArr)){
								$countryArr[$k]['CountryCount'] = $countryArr[$k]['CountryCount'] + $countArr['CountryCount'];
								$countryArr[$k]['GeorefSpecimensPerCountry'] = $countryArr[$k]['GeorefSpecimensPerCountry'] + $countArr['GeorefSpecimensPerCountry'];
								$countryArr[$k]['IDSpecimensPerCountry'] = $countryArr[$k]['IDSpecimensPerCountry'] + $countArr['IDSpecimensPerCountry'];
								$countryArr[$k]['IDGeorefSpecimensPerCountry'] = $countryArr[$k]['IDGeorefSpecimensPerCountry'] + $countArr['IDGeorefSpecimensPerCountry'];
							}
							else{
								$countryArr[$k]['CountryCount'] = $countArr['CountryCount'];
								$countryArr[$k]['GeorefSpecimensPerCountry'] = $countArr['GeorefSpecimensPerCountry'];
								$countryArr[$k]['IDSpecimensPerCountry'] = $countArr['IDSpecimensPerCountry'];
								$countryArr[$k]['IDGeorefSpecimensPerCountry'] = $countArr['IDGeorefSpecimensPerCountry'];
							}
						}
						ksort($countryArr, SORT_STRING | SORT_FLAG_CASE);
					}
				}
			}
			$c++;
		}
		$results['SpecimensNullLatitude'] = $results['SpecimenCount'] - $results['GeorefCount'];
	}
    elseif($action == "Run Statistics" && ($cPartentTaxon || $cCountry)){
        $resultsTemp = $collManager->runStatisticsQuery($collId,$cPartentTaxon,$cCountry);
		if ($resultsTemp){
			if (array_key_exists('families', $resultsTemp)){
				$familyArr = $resultsTemp['families'];
				ksort($familyArr, SORT_STRING | SORT_FLAG_CASE);
				unset($resultsTemp['families']);
			}
			if (array_key_exists('countries', $resultsTemp)){
				$countryArr = $resultsTemp['countries'];
				ksort($countryArr, SORT_STRING | SORT_FLAG_CASE);
				unset($resultsTemp['countries']);
			}
			ksort($resultsTemp, SORT_STRING | SORT_FLAG_CASE);
			$c = 0;
			foreach($resultsTemp as $k => $collArr){
				if($c>0) $collStr .= ", ";
				$collStr .= $collArr['CollectionName'];
				if(array_key_exists("SpecimenCount",$results)){
					$results['SpecimenCount'] = $results['SpecimenCount'] + $collArr['recordcnt'];
				}
				else{
					$results['SpecimenCount'] = $collArr['recordcnt'];
				}

				if(array_key_exists("GeorefCount",$results)){
					$results['GeorefCount'] = $results['GeorefCount'] + $collArr['georefcnt'];
				}
				else{
					$results['GeorefCount'] = $collArr['georefcnt'];
				}

				if(array_key_exists("FamilyCount",$results)){
					$results['FamilyCount'] = $results['FamilyCount'] + $collArr['familycnt'];
				}
				else{
					$results['FamilyCount'] = $collArr['familycnt'];
				}

				if(array_key_exists("GeneraCount",$results)){
					$results['GeneraCount'] = $results['GeneraCount'] + $collArr['genuscnt'];
				}
				else{
					$results['GeneraCount'] = $collArr['genuscnt'];
				}

				if(array_key_exists("SpeciesCount",$results)){
					$results['SpeciesCount'] = $results['SpeciesCount'] + $collArr['speciescnt'];
				}
				else{
					$results['SpeciesCount'] = $collArr['speciescnt'];
				}

				if(array_key_exists("TotalTaxaCount",$results)){
					$results['TotalTaxaCount'] = $results['TotalTaxaCount'] + $collArr['TotalTaxaCount'];
				}
				else{
					$results['TotalTaxaCount'] = $collArr['TotalTaxaCount'];
				}

				if(array_key_exists("TotalImageCount",$results)){
					$results['TotalImageCount'] = $results['TotalImageCount'] + $collArr['OccurrenceImageCount'];
				}
				else{
					$results['TotalImageCount'] = $collArr['OccurrenceImageCount'];
				}

				if(array_key_exists("SpecimensCountID",$results)){
					$results['SpecimensCountID'] = $results['SpecimensCountID'] + $collArr['speciesID'];
				}
				else{
					$results['SpecimensCountID'] = $collArr['speciesID'];
				}

				if(array_key_exists("TypeCount",$results)){
					$results['TypeCount'] = $results['TypeCount'] + $collArr['types'];
				}
				else{
					$results['TypeCount'] = $collArr['types'];
				}
				$c++;
			}
			$results['SpecimensNullLatitude'] = $results['SpecimenCount'] - $results['GeorefCount'];
		}
    }
	if($action == "Update Statistics"){
		$collManager->batchUpdateStatistics($collId);
		echo '<script type="text/javascript">window.location="collstats.php?collid='.$collId.'"</script>';
	}
    $_SESSION['statsFamilyArr'] = $familyArr;
    $_SESSION['statsCountryArr'] = $countryArr;
}
if($action != "Update Statistics"){
	?>
	<!DOCTYPE html>
	<html lang="<?php echo $LANG_TAG ?>">
		<head>
			<meta name="keywords" content="Natural history collections statistics" />
			<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['COL_STATS']; ?></title>
			<link href="<?php echo $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
			<?php
			include_once($SERVER_ROOT.'/includes/head.php');
			?>
			<link href="<?= $CSS_BASE_PATH; ?>/symbiota/collections/listdisplay.css" type="text/css" rel="stylesheet" />
			<link href="<?= $CSS_BASE_PATH ?>/symbiota/collections/sharedCollectionStyling.css" type="text/css" rel="stylesheet" />
            <script src="<?= $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
			<script src="<?= $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
			<script src="../../js/symb/collections.index.js" type="text/javascript"></script>
			<script type="text/javascript">
				$(document).ready(function() {
					if(!navigator.cookieEnabled){
						alert("<?php echo $LANG['NEED_COOKIES']; ?>");
					}
					$("#tabs").tabs({<?php echo ($action == "Run Statistics"?'active: 1':''); ?>});

                    function split( val ) {
                        return val.split( /,\s*/ );
                    }
                    function extractLast( term ) {
                        return split( term ).pop();
                    }

                    $( "#taxon" )
                    // don't navigate away from the field on tab when selecting an item
						.on( "keydown", function( event ) {
                            if ( event.keyCode === $.ui.keyCode.TAB &&
                                $( this ).data( "autocomplete" ).menu.active ) {
                                event.preventDefault();
                            }
                        })
                        .autocomplete({
                            source: function( request, response ) {
                                $.getJSON( "rpc/speciessuggest.php", {
                                    term: extractLast( request.term )
                                }, response );
                            },
                            search: function() {
                                // custom minLength
                                var term = extractLast( this.value );
                                if ( term.length < 4 ) {
                                    return false;
                                }
                            },
                            focus: function() {
                                // prevent value inserted on focus
                                return false;
                            },
                            select: function( event, ui ) {
                                var terms = split( this.value );
                                // remove the current input
                                terms.pop();
                                // add the selected item
                                terms.push( ui.item.value );
                                this.value = terms.join( ", " );
                                return false;
                            }
                        },{});
				});

				function toggleDisplayListOfCollectionsAnalyzed(){
					toggleById("colllist");
					toggleById("colllistlabel");
				}

				function toggleStatsPerColl(){
					toggleById("statspercollbox");
					toggleById("showstatspercoll");
					toggleById("hidestatspercoll");

					document.getElementById("geodistbox").style.display="none";
					document.getElementById("showgeodist").style.display="block";
					document.getElementById("hidegeodist").style.display="none";
					document.getElementById("famdistbox").style.display="none";
					document.getElementById("showfamdist").style.display="block";
					document.getElementById("hidefamdist").style.display="none";
					return false;
				}

				function toggleFamilyDist(){
					toggleById("famdistbox");
					toggleById("showfamdist");
					toggleById("hidefamdist");

					document.getElementById("geodistbox").style.display="none";
					document.getElementById("showgeodist").style.display="block";
					document.getElementById("hidegeodist").style.display="none";
					document.getElementById("statspercollbox").style.display="none";
					document.getElementById("showstatspercoll").style.display="block";
					document.getElementById("hidestatspercoll").style.display="none";
					return false;
				}

				function toggleGeoDist(){
					toggleById("geodistbox");
					toggleById("showgeodist");
					toggleById("hidegeodist");

					document.getElementById("famdistbox").style.display="none";
					document.getElementById("showfamdist").style.display="block";
					document.getElementById("hidefamdist").style.display="none";
					document.getElementById("statspercollbox").style.display="none";
					document.getElementById("showstatspercoll").style.display="block";
					document.getElementById("hidestatspercoll").style.display="none";
					return false;
				}

				function toggleById(target){
					if(target != null){
						var obj = document.getElementById(target);
						var style = window.getComputedStyle(obj);

						if(style.display=="none" || style.display==""){
							obj.style.display="block";
						}
						else {
							obj.style.display="none";
						}
					}
					return false;
				}

				function changeCollForm(f){
					var dbElements = document.getElementsByName("db[]");
					var c = false;
					var collid = "";
					for(i = 0; i < dbElements.length; i++){
						var dbElement = dbElements[i];
						if(dbElement.checked && !isNaN(dbElement.value)){
							if(c == true) collid = collid+",";
							collid = collid + dbElement.value;
							c = true;
						}
					}
					if(c == true){
						var collobj = document.getElementById("colltxt");
						collobj.value = collid;
						document.getElementById("collform").submit();
					}
					else{
						alert("<?php echo $LANG['CHOOSE_ONE']; ?>");
						return false;
					}
				}
			</script>
			<style>
				.icon-mrgn-rel {
					margin-bottom: 0.6rem;
				}
				.gridlike-form-row-align {
					flex: 1;
					text-align: center;
				}
				.gridlike-form-no-margin {
					display: flex;
					flex-direction: column;
				}
			</style>
		</head>
		<body>
			<?php
			$displayLeftMenu = (isset($collections_misc_collstatsMenu)?$collections_misc_collstatsMenu:false);
			include($SERVER_ROOT.'/includes/header.php');
			if(isset($collections_misc_collstatsCrumbs)){
				if($collections_misc_collstatsCrumbs){
					echo "<div class='navpath'>";
					echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
					echo $collections_misc_collstatsCrumbs.' &gt;&gt; ';
					echo "<b>" . $LANG['COL_STATS'] . "</b>";
					echo "</div>";
				}
			}
			else{
				?>
				<div class='navpath'>
					<a href='../../index.php'><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
					<a href='collprofiles.php'><?php echo htmlspecialchars($LANG['COLLECTIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
					<b><?php echo $LANG['COL_STATS']; ?></b>
				</div>
				<?php
			}
			?>
			<!-- This is inner text! -->
			<div role="main" id="innertext">
				<h1 class="page-heading"><?= $LANG['SELECT_COLS']; ?></h1>
				<div id="tabs" class="tabby">
					<ul class="full-tab">
						<li><a href="#specobsdiv"><?php echo htmlspecialchars($LANG['COLLECTIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
						<?php
                        if($action == "Run Statistics"){
							echo '<li><a href="#statsdiv">' . htmlspecialchars($LANG['STATISTICS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
						}
						?>
					</ul>

					<div id="specobsdiv" class="pin-things-here">
						<?php
						if($specArr || $obsArr){
							?>
							<form name="collform" id="collform" action="collstats.php" method="post" onsubmit="return changeCollForm(this);">
								<div>
									<?php
									if($SYMB_UID && ($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS))){
										?>
										<fieldset class="fieldset-padding flex-form">
											<legend><b><?php echo $LANG['REC_CRITERIA']; ?></b></legend>
											<div class="record-criteria-inputs">
												<label for="taxon"><?php echo $LANG['PARENT_CRITERIA']; ?>: </label>
												<input type="text" id="taxon" name="taxon" size="43" value="<?php echo $cPartentTaxon; ?>" />
											</div>
											<div class="record-criteria-inputs">
												<label for="country"><?php echo $LANG['COUNTRY']; ?>: </label>
												<input type="text" id="country" name="country" size="43" value="<?php echo $cCountry; ?>" />
											</div>
										</fieldset>
										<?php
									}
									?>
									<div class="specimen-header-margin">
										<hr/>
										<h2><?php echo $LANG['SPECIMEN_COLLECTIONS'] ?></h2>
									</div>
									<div>
										<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" />
										<label for="dballcb"><?php echo $LANG['SEL_OR_DESEL']; ?> <a href="collprofiles.php"><?php echo htmlspecialchars($LANG['COLLECTIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></label>
									</div>
									<?php
									$collArrIndex = 0;
									if($specArr){
										$collCnt = 0;
										if(isset($specArr['cat'])){
											$categoryArr = $specArr['cat'];
											?>
											<div class="sticky-buttons" id="statistics-button-panel">
												<div>
													<button type="submit" name="submitaction" value="Run Statistics"><?php echo $LANG['VIEW_STATS']; ?></button>
												</div>
												<?php
												if($SYMB_UID && $IS_ADMIN){
													?>
													<div class="submit-button-div">
														<button type="submit" name="submitaction" value="Update Statistics"><?php echo $LANG['UPDATE_STATS']; ?></button>
													</div>
													<?php
												}
												?>
											</div>
											<section class="gridlike-form">
												<?php
												$cnt = 0;
												foreach($categoryArr as $catid => $catArr){
													$name = $catArr['name'];
													if($catArr['acronym']) $name .= ' ('.$catArr['acronym'].')';
													$catIcon = $catArr['icon'];
													unset($catArr['name']);
													unset($catArr['acronym']);
													unset($catArr['icon']);
													$idStr = $collArrIndex.'-'.$catid;
													?>
													<section class="gridlike-form-row">
														<div>
															<input style="margin:0" id="cat-<?php echo $idStr; ?>-Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ($collIdArr&&($collIdArr==array_keys($catArr))?'checked':''); ?> />
															<label for="cat-<?php echo $idStr; ?>-Input">
																<?php echo $name; ?> (<?php echo isset($LANG['SPECIMEN']) ? $LANG['SPECIMEN'] : "Specimen" ?>) <?php echo $LANG['SELECT_DESELECT'] ?>
															</label>
														</div>
														<div>
															<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;" class="condense-expand-flex" >
																<div class="condense-expand-button-set">
																	<img id="plus-<?php echo $idStr; ?>" src="../../images/plus.png" alt="plus sign to expand menu" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'':'display:none;') ?> width:1em;" />
																	<img id="minus-<?php echo $idStr; ?>" src="../../images/minus.png" alt="minus sign to condense menu" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?> width:1em;" />
																	<p id="ptext-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'':'display:none;') ?>">
																		<?php echo $LANG['EXPAND'] ?>
																	</p>
																	<p id="mtext-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?>" >
																		<?php echo $LANG['CONDENSE'] ?>
																	</p>
																</div>
															</a>
														</div>
													</section>
													<section class="gridlike-form-row bottom-breathing-room-rel">
														<div>
															<div id="cat-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?>">
																<section class="gridlike-form">
																	<fieldset class="fieldset-padding-rem">
																		<legend>
																			<?php echo $name; ?> (<?php echo isset($LANG['SPECIMEN']) ? $LANG['SPECIMEN'] : "Specimen" ?>)
																		</legend>
																	<?php
																	foreach($catArr as $collid => $collName2){
																		?>
																		<div class="gridlike-form-row bottom-breathing-room-rel">
																				<input style="margin:0" id="db-<?php echo $collid?>" name="db[]" value="<?php echo $collid; ?>" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat-<?php echo $idStr; ?>-Input')" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
																				<label for="db-<?php echo $collid?>">
																					<a href='collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
																						<?php
																						$codeStr = ' ('.$collName2['instcode'];
																						if($collName2['collcode']) $codeStr .= '-'.$collName2['collcode'];
																						$codeStr .= ')';
																						echo $collName2["collname"].$codeStr;
																						?>
																						 - <?php echo $LANG['MORE_INFO']; ?>
																					</a>
																				</label>
																			</div>
																			<?php
																		$collCnt++;
																	}
																	?>
																	</fieldset>
																</section>
															</div>
														</div>
													</section>
													<?php
													$cnt++;
												}
												?>
											</section>
											<?php
										}
										if(isset($specArr['coll'])){
											$collArr = $specArr['coll'];
											?>
											<section class="gridlike-form">
												<?php
												foreach($collArr as $collid => $cArr){
													?>
													<div class="gridlike-form-row bottom-breathing-room-rel">
														<div>
															<input id="current-collid-<?= $collid; ?>" name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll();" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
															<label class="screen-reader-only" for="current-collid-<?= $collid; ?>"><?= $LANG['COLLECTION'] . '-' . $cArr['instcode']; ?></label>
														</div>
														<div>
															<a href='collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
																<?php
																$codeStr = ' ('.$cArr['instcode'];
																if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
																$codeStr .= ')';
																echo $cArr["collname"].$codeStr;
																?>
																 - <?php echo $LANG['MORE_INFO']; ?>
															</a>
														</div>
													</div>
													<?php
													$collCnt++;
												}
												?>
											<div>
												<div>
													<button type="submit" name="submitaction" value="Run Statistics"><?php echo $LANG['VIEW_STATS']; ?></button>
												</div>
												<?php
												if($SYMB_UID && $IS_ADMIN){
													?>
													<div class="submit-button-div">
														<button type="submit" name="submitaction" value="Update Statistics"><?php echo $LANG['UPDATE_OBSERVATION_STATS']; ?></button>
													</div>
													<?php
												}
												?>
											</div>
											<?php
											if(!isset($specArr['cat'])){
												echo '</section>';
											}
										}
										$collArrIndex++;
									}
									if($specArr && $obsArr) echo "<hr style=\"clear:both;margin:20px 0px;\"/><h2>{$LANG['OBSERVATION_COLLECTIONS']}</h2>";
									if($obsArr){
										$collCnt = 0;
										if(isset($obsArr['cat'])){
											$categoryArr = $obsArr['cat'];
											?>
											<section class="gridlike-form">
												<?php
												$cnt = 0;
												foreach($categoryArr as $catid => $catArr){
													$name = $catArr['name'];
													if($catArr['acronym']) $name .= ' ('.$catArr['acronym'].')';
													$catIcon = $catArr['icon'];
													unset($catArr['name']);
													unset($catArr['acronym']);
													unset($catArr['icon']);
													$idStr = $collArrIndex.'-'.$catid;
													?>
													<div class="gridlike-form-row bottom-breathing-room-rel">
														<div>
															<input id="cat-<?php echo $idStr; ?>-Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ($collIdArr&&($collIdArr==array_keys($catArr))?'checked':''); ?> />
															<label for="cat-<?php echo $idStr; ?>-Input">
																<?php echo $name; ?> (<?php echo isset($LANG['OBSERVATION']) ? $LANG['OBSERVATION'] : "Observation" ?>)  <?php echo $LANG['SELECT_DESELECT'] ?>
															</label>
														</div>
														<div>
															<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;" class="condense-expand-flex">
																<div class="condense-expand-button-set">
																	<img id="plus-<?php echo $idStr; ?>" src="../../images/plus.png" alt="plus sign to expand menu" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'':'display:none;') ?> width:1em;" />
																	<img id="minus-<?php echo $idStr; ?>" src="../../images/minus.png" alt="minus sign to condense menu" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?> width:1em;" />
																	<p id="ptext-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'':'display:none;') ?>">
																		<?php echo $LANG['EXPAND'] ?>
																	</p>
																	<p id="mtext-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?>" >
																		<?php echo $LANG['CONDENSE'] ?>
																	</p>
																</div>
															</a>
														</div>
													</div>
													<div>
														<div class="gridlike-form-row bottom-breathing-room-rel" id="cat-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?>">
															<section class="gridlike-form">
																<fieldset class="fieldset-observation-padding-rem">
																	<legend>
																		<?php echo $name; ?> (<?php echo isset($LANG['OBSERVATION']) ? $LANG['OBSERVATION'] : "Observation" ?>)
																	</legend>
																	<?php
																		foreach($catArr as $collid => $collName2){
																			?>
																				<div class="gridlike-form-row bottom-breathing-room-rel">
																					<div>
																						<input class="input-margin cat-<?php echo $idStr; ?>" id="db-<?php echo $collid ?>" name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="unselectCat('cat-<?php echo $idStr; ?>-Input')" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
																						<label for="db-<?php echo $collid ?>">
																							<a href = 'collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
																								<?php
																								$codeStr = ' ('.$collName2['instcode'];
																								if($collName2['collcode']) $codeStr .= '-'.$collName2['collcode'];
																								$codeStr .= ')';
																								echo $collName2["collname"].$codeStr;
																								?>
																								 - <?php echo $LANG['MORE_INFO']; ?>
																							</a>
																						</label>
																					</div>
																				</div>
																			<?php
																			$collCnt++;
																		}
																	?>
																</fieldset>
															</section>
														</div>
														<?php
														$cnt++;
														}
														?>
													</div>
											</section>
											<?php
										}
										if(isset($obsArr['coll'])){
											$collArr = $obsArr['coll'];
											?>
											<section class="gridlike-form-row">
												<fieldset class="observation-fieldset">
													<?php
													foreach($collArr as $collid => $cArr){
														?>
															<div>
																<input id="db-<?php echo $collid ?>" name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll();" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
																<label for="db-<?php echo $collid ?>"><?php echo $LANG['SELECT_DESELECT'] ?></label>
															</div>
															<div class="gridlike-form-row bottom-breathing-room-rel">
																<div class="collectiontitle">
																	<a href = 'collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
																		<?php
																		$codeStr = ' ('.$cArr['instcode'];
																		if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
																		$codeStr .= ')';
																		echo $cArr["collname"].$codeStr;
																		?>
																		- <?php echo $LANG['MORE_INFO']; ?>
																	</a>
																</div>
															</div>
														<?php
														$collCnt++;
													}
													?>
												</fieldset>
											</section>
											<div class="run-stat-btn">
												<div>
													<button type="submit" name="submitaction" value="Run Statistics"><?php echo $LANG['VIEW_STATS']; ?></button>
												</div>
												<?php
												if($SYMB_UID && $IS_ADMIN){
													?>
													<div class="submit-button-div">
														<button type="submit" name="submitaction" value="Update Statistics" /><?php echo $LANG['UPDATE_STATS']; ?></button>
													</div>
													<?php
												}
												?>
											</div>
											<?php
										}
										$collArrIndex++;
									}
									?>
									<div class="clr">&nbsp;</div>
									<input type="hidden" name="collid" id="colltxt" value="" />
									<input type="hidden" name="days" value="<?php echo $days; ?>" />
									<input type="hidden" name="months" value="<?php echo $months; ?>" />
								</div>
                            </form>
                            <?php
                        }
						else{
							echo '<div class="top-marg"><div class="heavy-txt">' . $LANG['NO_COLLECTIONS'] . '</div></div>';
						}
						?>
					</div>

                    <?php
					if($action == "Run Statistics"){
						?>
						<div id="statsdiv">
							<div class="mn-ht">
								<div>
									<h1><?php echo $LANG['SEL_COL_STATS']; ?></h1>
									<div class="big-fnt-margin">
										<div id="colllistlabel"><a href="#" onclick="return toggleDisplayListOfCollectionsAnalyzed();"><?php echo $LANG['DISPLAY_LIST']; ?></a></div>
										<div id="colllist" class="dsply-none">
											<?php echo $collStr; ?>
										</div>
									</div>
									<fieldset class="stats-display-fieldset">
										<legend><?php echo $LANG['GENERAL_STATISTICS']?></legend>
										<ul class="stats-display-ul">
											<?php
												if ($results){
													echo "<li>";
													echo ($results['SpecimenCount'] ? number_format($results['SpecimenCount']) : 0) . " " . $LANG['OCC_RECORDS'];
													echo "</li>";
													echo "<li>";
													$percGeo = '';
													if($results['SpecimenCount'] && $results['GeorefCount'] && $results['SpecimenCount'] !== 0){
														try {
															$percGeo = (100* ($results['GeorefCount'] / $results['SpecimenCount']));
														} catch (Exception $e) {
															error_log('Exception: ' . $e->getMessage());
														}
													}
													echo ($results['GeorefCount'] ? number_format($results['GeorefCount']) : 0) . ($percGeo ? " (" . ($percGeo>1 ? round($percGeo) : round($percGeo,2)) . "%)" : '') . " " . $LANG['GEOREFERENCED'];
													echo "</li>";
													echo "<li>";
													$percImg = '';
													if($results['SpecimenCount'] && $results['TotalImageCount'] && $results['SpecimenCount'] !== 0){
														try {
															$percImg = (100* ($results['TotalImageCount'] / $results['SpecimenCount']));
														} catch (Exception $e) {
															error_log('Exception: ' . $e->getMessage());
														}
													}
													echo ($results['TotalImageCount'] ? number_format($results['TotalImageCount']) : 0) . ($percImg ? " (" . ($percImg>1 ? round($percImg) : round($percImg,2)) . "%)" : '') . " " . $LANG['OCCS_IMAGED'];
													echo "</li>";
													echo "<li>";
													$percId = '';
													if($results['SpecimenCount'] && $results['SpecimensCountID'] && $results['SpecimenCount'] !== 0){
														try {
															$percId = (100* ($results['SpecimensCountID'] / $results['SpecimenCount']));
														} catch (Exception $e) {
															error_log('Exception: ' . $e->getMessage());
														}
													}
													echo ($results['SpecimensCountID'] ? number_format($results['SpecimensCountID']) : 0) . ($percId?" (" . ($percId>1 ? round($percId) : round($percId,2)) . "%)" : '') . " " . $LANG['IDED_TO_SP'];
													echo "</li>";
													echo "<li>";
													echo ($results['FamilyCount'] ? number_format($results['FamilyCount']) : 0) . " " . $LANG['FAMILIES'];
													echo "</li>";
													echo "<li>";
													echo ($results['GeneraCount'] ? number_format($results['GeneraCount']) : 0) . " " . $LANG['GENERA'];
													echo "</li>";
													echo "<li>";
													echo ($results['SpeciesCount'] ? number_format($results['SpeciesCount']) : 0) . " " . $LANG['SPECIES'];
													echo "</li>";
													echo "<li>";
													echo ($results['TotalTaxaCount'] ? number_format($results['TotalTaxaCount']) : 0) . " " . $LANG['TOTAL_TAXA'];
													echo "</li>";
													/*echo "<li>";
													echo ($results['TypeCount'] ? number_format($results['TypeCount']) : 0)." type specimens";
													echo "</li>";*/
												}
											?>
										</ul>
										<form name="statscsv" id="statscsv" action="collstatscsv.php" method="post" onsubmit="">
											<div class="stat-csv-margin gridlike-form-no-margin">
												<div class="gridlike-form-row">
													<div id="showstatspercoll" class="float-and-block" >
														<a href="#" onclick="return toggleStatsPerColl()"><?php echo $LANG['SHOW_PER_COL']; ?></a>
													</div>
													<div id="hidestatspercoll" class="float-and-no-display" >
														<a href="#" onclick="return toggleStatsPerColl()"><?php echo $LANG['HIDE_STATS']; ?></a>
													</div>
													<div class="stat-csv-float-margins icon-mrgn-rel" title="<?php echo $LANG['SAVE_CSV']; ?>">
														<input type="hidden" name="collids" id="collids" value='<?php echo $collId; ?>' />
														<input type="hidden" name="taxon" value='<?php echo $cPartentTaxon; ?>' />
														<input type="hidden" name="country" value='<?php echo $cCountry; ?>' />
														<input type="hidden" name="action" id="action" value='<?php echo $LANG['DOWNLOAD_STATS']; ?>' />
														<input type="image" name="action" src="../../images/dl.png" style="width:1.3em" onclick="" />
														<!--input type="submit" name="action" value="Download Stats per Coll" src="../../images/dl.png" / -->
													</div>
												</div>
											</div>
										</form>
									</fieldset>
										<fieldset class="extra-stats bottom-breathing-room-rel">
											<legend><?php echo $LANG['EXTRA_STATS']; ?></legend>
											<form name="famstatscsv" id="famstatscsv" action="collstatscsv.php" method="post" onsubmit="">
												<!-- <div class='legend'> -->
												<!-- </div> -->
												<div class="gridlike-form-no-margin">
													<div class="stat-csv-margin gridlike-form-row">
														<div id="showfamdist" class="float-and-block" >
															<a href="#" onclick="return toggleFamilyDist()"><?php echo $LANG['SHOW_FAMILY']; ?></a>
														</div>
														<div id="hidefamdist" class="float-and-no-display" >
															<a href="#" onclick="return toggleFamilyDist()"><?php echo $LANG['HIDE_FAMILY']; ?></a>
														</div>
														<div class="stat-csv-float-margins icon-mrgn-rel" title="<?php echo $LANG['SAVE_CSV']; ?>">
															<input type="hidden" name="action" value='Download Family Dist'/>
															<input type="image" name="action" src="../../images/dl.png" style="width:1.3em" onclick="" />
														</div>
													</div>
												</div>
											</form>
											<form name="geostatscsv" id="geostatscsv" action="collstatscsv.php" method="post" onsubmit="">
												<div class="clr gridlike-form-no-margin">
													<div class="gridlike-form-row">
														<div id="showgeodist" class="float-and-block" >
															<a href="#" onclick="return toggleGeoDist()"><?php echo $LANG['SHOW_GEO']; ?></a>
														</div>
														<div id="hidegeodist" class="float-and-no-display">
															<a href="#" onclick="return toggleGeoDist();"><?php echo $LANG['HIDE_GEO']; ?></a>
														</div>
														<div class="stat-csv-float-margins icon-mrgn-rel" title="<?php echo $LANG['SAVE_CSV']; ?>">
															<input type="hidden" name="action" value='Download Geo Dist' />
															<input type="image" name="action" src="../../images/dl.png" style="width:1.3em" onclick="" />
														</div>
													</div>
												</div>
											</form>
                                            <?php
                                            if(!$cPartentTaxon && !$cCountry){
                                                ?>
                                                <div class="top-breathing-room-rel">
                                                    <form name="orderstats" class="no-btm-mrgn" action="collorderstats.php" method="post" target="_blank">
                                                        <input type="hidden" name="collid" id="collid" value='<?php echo $collId; ?>'/>
                                                        <input type="hidden" name="totalcnt" id="totalcnt" value='<?php echo $results['SpecimenCount']; ?>'/>
                                                        <button type="submit" name="action" value="Load Order Distribution"><?php echo $LANG['LOAD_ORDER']; ?></button>
                                                    </form>
                                                </div>
                                                <?php
                                            }
                                            ?>
										</fieldset>
										<?php
										if(!$cPartentTaxon && !$cCountry){
                                            if ($SYMB_UID && ($IS_ADMIN || array_key_exists("CollAdmin", $USER_RIGHTS))) {
                                                ?>
                                                <fieldset id="yearstatsbox" class="yearstatbox-width">
                                                    <legend><b><?php echo $LANG['YEAR_STATS']; ?></b></legend>
                                                    <form name="yearstats" class="no-btm-mrgn" action="collyearstats.php" method="post" target="_blank" class="flex-form">
                                                        <input type="hidden" name="collid" id="collid" value='<?php echo $collId; ?>'/>
                                                        <input type="hidden" name="days" value="<?php echo $days; ?>"/>
                                                        <input type="hidden" name="months" value="<?php echo $months; ?>"/>
                                                        <div class="yearstatbox-left-float">
                                                            <?php echo $LANG['YEARS']; ?>: <input type="text" id="years" size="5" name="years" value="1" />
                                                        </div>
                                                        <div class="yearstatbox-submit-btn-margin">
                                                            <button type="submit" name="action" value="Load Stats"><?php echo $LANG['LOAD_STATS']; ?></button>
                                                        </div>
                                                    </form>
                                                </fieldset>
                                                <?php
                                            }
                                        }
                                        ?>
									<div class="clr"> </div>
								</div>

								<fieldset id="statspercollbox" class="statspercollbox">
									<legend><b><?php echo $LANG['STATS_PER_COL']; ?></b></legend>
									<section class="gridlike-form">
										<section class="gridlike-form-row bottom-breathing-room-rel">
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['COLLECTION']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['OCCS']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['G_GEOREFERENCED']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['IMAGED']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['SPECIES_ID']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['F_FAMILIES']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['G_GENERA']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['S_SPECIES']; ?></div>
											<div class="cntr-text gridlike-form-row-align"><?php echo $LANG['T_TOTAL_TAXA']; ?></div>
											<!-- <th class="cntr-text">Types</th> -->
										</section>
										<?php
										foreach($resultsTemp as $name => $data){
											echo '<section class="gridlike-form-row bottom-breathing-room-rel">';
											echo '<div class="gridlike-form-row-align">'.wordwrap($name,40,"<br />\n",true).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('recordcnt',$data)?$data['recordcnt']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('georefcnt',$data)?$data['georefcnt']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('OccurrenceImageCount',$data)?$data['OccurrenceImageCount']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('speciesID',$data)?$data['speciesID']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('familycnt',$data)?$data['familycnt']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('genuscnt',$data)?$data['genuscnt']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('speciescnt',$data)?$data['speciescnt']:0).'</div>';
											echo '<div class="gridlike-form-row-align">'.(array_key_exists('TotalTaxaCount',$data)?$data['TotalTaxaCount']:0).'</div>';
											//echo '<td>'.(array_key_exists('types',$data)?$data['types']:0).'</td>';
											echo '</section>';
										}
										?>
									</section>
								</fieldset>
								<fieldset id="famdistbox" class="famdistbox">
									<legend><b><?php echo $LANG['FAM_DIST']; ?></b></legend>
									<section class="gridlike-form">
										<section class="gridlike-form-row bottom-breathing-room-rel">
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['FAMILY']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['SPECIMENS']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['G_GEOREFERENCED']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['SPECIES_ID']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
												<?php echo $LANG['G_GEOREFERENCED']; ?>
												<br />
												<?php echo $LANG['AND']; ?>
												<br />
												<?php echo $LANG['SPECIES_ID']; ?>
											</div>
										</section>
										<?php
										$total = 0;
										foreach($familyArr as $name => $data){
											echo '<section class="gridlike-form-row">';
											echo '<div class="gridlike-form-row-align">'.wordwrap($name,52,"<br />\n",true).'</div>';
											echo '<div class="gridlike-form-row-align">';
											if(count($resultsTemp) == 1){
												echo '<a href="../list.php?db[]=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&reset=1&taxa=' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener noreferrer">';
											}
											echo number_format($data['SpecimensPerFamily']);
											if(count($resultsTemp) == 1){
												echo '</a>';
											}
											echo '</div>';
											try {
												echo '<div class="gridlike-form-row-align">'.($data['GeorefSpecimensPerFamily'] ? round(100*($data['GeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])) : 0).'%</div>';
												echo '<div class="gridlike-form-row-align">'.($data['IDSpecimensPerFamily'] ? round(100*($data['IDSpecimensPerFamily']/$data['SpecimensPerFamily'])) : 0).'%</div>';
												echo '<div class="gridlike-form-row-align">'.($data['IDGeorefSpecimensPerFamily'] ? round(100*($data['IDGeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])) : 0).'%</div>';
											} catch (Exception $e) {
												error_log('Exception: ' . $e->getMessage());
											}
											echo '</section>';
											$total = $total + $data['SpecimensPerFamily'];
										}
										?>
									</section>
									<div class="top-marg">
										<b><?php echo $LANG['SPEC_W_FAMILY']; ?>:</b> <?php echo number_format($total); ?><br />
										<?php
										if ($results){
											echo $LANG['SPEC_WO_FAMILY']; ?>: <?php echo number_format($results['SpecimenCount']-$total);
										}?><br />
									</div>
								</fieldset>
								<fieldset id="geodistbox" class="geodistbox">
									<legend><b><?php echo $LANG['GEO_DIST']; ?></b></legend>
									<section class="gridlike-form">
										<section class="gridlike-form-row bottom-breathing-room-rel">
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['COUNTRY']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['SPECIMENS']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['G_GEOREFERENCED']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
											<?php echo $LANG['SPECIES_ID']; ?>
										</div>
											<div class="cntr-text gridlike-form-row-align">
												<?php echo $LANG['G_GEOREFERENCED']; ?>
												<br />
												<?php echo $LANG['AND']; ?>
												<br />
												<?php echo $LANG['SPECIES_ID']; ?>
											</div>
										</section>
										<?php
										$total = 0;
										foreach($countryArr as $name => $data){
											echo '<section class="gridlike-form-row">';
											echo '<div class="gridlike-form-row-align">'.wordwrap($name,52,"<br />\n",true).'</div>';
											echo '<div class="gridlike-form-row-align">';
											if(count($resultsTemp) == 1){
												echo '<a href="../list.php?db[]=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&reset=1&country=' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener noreferrer">';
											}
											echo number_format($data['CountryCount']);
											if(count($resultsTemp) == 1){
												echo '</a>';
											}
											echo '</div>';
											try {
												echo '<div class="gridlike-form-row-align">'.($data['GeorefSpecimensPerCountry'] ? round(100*($data['GeorefSpecimensPerCountry']/$data['CountryCount'])) : 0).'%</div>';
												echo '<div class="gridlike-form-row-align">'.($data['IDSpecimensPerCountry'] ? round(100*($data['IDSpecimensPerCountry']/$data['CountryCount'])) : 0).'%</div>';
												echo '<div class="gridlike-form-row-align">'.($data['IDGeorefSpecimensPerCountry'] ? round(100*($data['IDGeorefSpecimensPerCountry']/$data['CountryCount'])) : 0).'%</div>';
											} catch (Exception $e) {
												error_log('Exception: ' . $e->getMessage());
											}
											echo '</section>';
											$total = $total + $data['CountryCount'];
										}
										?>
									</section>
									<div class="top-marg">
										<b><?php echo $LANG['SPEC_W_COUNTRY']; ?>:</b> <?php echo number_format($total); ?><br />
										<?php 
										if ($results){
											echo $LANG['SPEC_WO_COUNTRY']; ?>: <?php echo number_format(($results['SpecimenCount']-$total)+$results['SpecimensNullLatitude']);
										}
										?><br />
									</div>
								</fieldset>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<!-- end inner text -->
			<?php
				include($SERVER_ROOT.'/includes/footer.php');
			?>
		</body>
	</html>
	<?php
}
?>
