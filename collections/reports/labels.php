<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = filter_var($_POST['collid'], FILTER_SANITIZE_NUMBER_INT);
$hPrefix = $_POST['lhprefix'];
$hMid = filter_var($_POST['lhmid'], FILTER_SANITIZE_NUMBER_INT);
$hSuffix = $_POST['lhsuffix'];
$lFooter = $_POST['lfooter'];
$columnCount = $_POST['columncount'];
$includeSpeciesAuthor = ((array_key_exists('speciesauthors', $_POST) && $_POST['speciesauthors']) ? 1 : 0);
$showCatalogNumbers = ((array_key_exists('catalognumbers', $_POST) && $_POST['catalognumbers']) ? 1 : 0);
$useBarcode = array_key_exists('bc', $_POST) ? filter_var($_POST['bc'], FILTER_SANITIZE_NUMBER_INT) : 0;
$useSymbBarcode = array_key_exists('symbbc',$_POST) ? filter_var($_POST['symbbc'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

//Sanitation
if(!is_numeric($columnCount) && $columnCount != 'packet') $columnCount = 2;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN) $isEditor = 1;
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($labelManager->getCollid(),$USER_RIGHTS["CollAdmin"])) $isEditor = 1;
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($labelManager->getCollid(),$USER_RIGHTS["CollEditor"])) $isEditor = 1;
}
if($action == 'Export to CSV'){
	$labelManager->exportLabelCsvFile($_POST);
}
else{
	?>
	<!DOCTYPE html>
	<html lang="<?php echo $LANG_TAG ?>">
		<head>
			<title><?php echo $DEFAULT_TITLE; ?> Labels</title>
			<style type="text/css">
				body { background-color:#ffffff;font-family:arial,sans-serif; font-size:10pt; }

				table.labels { table-layout:fixed; width:100%; page-break-before:auto; page-break-inside:avoid; }
				table.labels td { width: 600px; }

				p.printbreak { page-break-after:always; }

				.lheader { text-align:center; font:bold 14pt arial,sans-serif; margin-bottom:10px; }

				.family { text-align:right; }
				.scientificnamediv { font-size:11pt; }
				.identifiedbydiv { margin-left:15px; }
				.identificationreferences { margin-left:15px; }
				.identificationremarks { margin-left:15px; }
				.taxonremarks { margin-left:15px; }
				.loc1div { font-size:11pt; }
				.country { font-weight:bold; }
				.stateprovince { font-weight:bold; }
				.county { font-weight:bold; }
				.municipality { font-weight:bold; }
				.associatedtaxa { font-style:italic; }
				.collectordiv { margin-top:10px; }
				.recordnumber { margin-left:10px; }
				.associatedcollectors { margin-left:15px; clear:both; }

				.lfooter { text-align:center; font:bold 12pt arial,sans-serif; padding-top:10px; clear:both; }

				.cnbarcode { width:100%; text-align:center; }
				.symbbarcode { width:100%; text-align:center; margin-top:10px; }
				.screen-reader-only {
					position: absolute;
					left: -10000px;
				}
				<?php
				if($columnCount == 'packet'){
					?>
					.foldMarks1 { clear:both;padding-top:285px; }
					.foldMarks1 span { margin-left:77px; margin-right:80px; }
					.foldMarks2 { clear:both;padding-top:355px;padding-bottom:10px; }
					.foldMarks2 span { margin-left:77px; margin-right:80px; }
					table.labels { clear:both; margin-top: 10px; margin-left: auto; margin-right: auto; width: 500px; page-break-before:auto; page-break-inside:avoid; }
					table.labels td { width:500px; margin:50px; padding:10px 50px; font-size: 80%; }
					.family { display:none }
					<?php
				}
				elseif($columnCount != 1){
					?>
					table.labels td { width:<?php echo (100/$columnCount); ?>%; font-size:10pt; }
					table.labels td:first-child { padding:10px 23px 10px 0px; }
					table.labels td:not(:first-child):not(:last-child) { padding:10px 23px 10px 23px; }
					table.labels td:last-child { padding:10px 0px 10px 23px; }
					<?php
				}
				?>
			</style>
		</head>
		<body>
			<h1 class="page-heading screen-reader-only">Labels</h1>
			<div>
				<?php
				if($action && $isEditor){
					$labelArr = $labelManager->getLabelArray($_POST['occid'], $includeSpeciesAuthor);
					$totalLabelCnt = count($labelArr);
					$labelCnt = 0;
					$rowCnt = 0;
					foreach($labelArr as $occid => $occArr){
						$midStr = '';
						if($hMid == 1) $midStr = $occArr['country'];
						elseif($hMid == 2) $midStr = $occArr['stateprovince'];
						elseif($hMid == 3) $midStr = $occArr['county'];
						elseif($hMid == 4) $midStr = $occArr['family'];
						$headerStr = '';
						if($hPrefix || $midStr || $hSuffix){
							$headerStrArr = array();
							$headerStrArr[] = trim($hPrefix);
							$headerStrArr[] = trim($midStr);
							$headerStrArr[] = trim($hSuffix);
							$headerStr = implode(" ",$headerStrArr);
						}

						$dupCnt = $_POST['q-'.$occid];
						for($i = 0;$i < $dupCnt;$i++){
							$labelCnt++;
							if($columnCount == 'packet'){
								echo '<div class="foldMarks1"><span style="float:left;">+</span><span style="float:right;">+</span></div>';
								echo '<div class="foldMarks2"><span style="float:left;">+</span><span style="float:right;">+</span></div>';
							}
							elseif($labelCnt%$columnCount == 1){
								if($labelCnt > 1) echo '</tr></table>';
								echo '<table class="labels"><tr>';
								$rowCnt++;
							}
							?>
							<td valign="top">
								<?php
								if($headerStr){
									?>
									<div class="lheader">
										<?= htmlspecialchars($headerStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>
									</div>
									<?php
								}
								if($hMid != 4) echo '<div class="family">'.$occArr['family'].'</div>'; ?>
								<div class="scientificnamediv">
									<?php
									if($occArr['identificationqualifier']) echo '<span class="identificationqualifier">'.$occArr['identificationqualifier'].'</span> ';
									$scinameStr = $occArr['scientificname'];
									$parentAuthor = (array_key_exists('parentauthor',$occArr)?' '.$occArr['parentauthor']:'');
									$scinameStr = str_replace(' subsp. ','</i></b>'.$parentAuthor.' <b>subsp. <i>',$scinameStr);
									$scinameStr = str_replace(' ssp. ','</i></b>'.$parentAuthor.' <b>ssp. <i>',$scinameStr);
									$scinameStr = str_replace(' var. ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
									$scinameStr = str_replace(' variety ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
									$scinameStr = str_replace(' Variety ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
									$scinameStr = str_replace(' v. ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
									$scinameStr = str_replace(' f. ','</i></b>'.$parentAuthor.' <b>f. <i>',$scinameStr);
									$scinameStr = str_replace(' cf. ','</i></b>'.$parentAuthor.' <b>cf. <i>',$scinameStr);
									$scinameStr = str_replace(' aff. ','</i></b>'.$parentAuthor.' <b>aff. <i>',$scinameStr);
									?>
									<span class="sciname">
										<b><i><?php echo $scinameStr; ?></i></b>
									</span>
									<span class="scientificnameauthorship"><?php echo $occArr['scientificnameauthorship']; ?></span>
								</div>
								<?php
								if($occArr['identifiedby']){
									?>
									<div class="identifiedbydiv">
										Det by:
										<span class="identifiedby"><?php echo $occArr['identifiedby']; ?></span>
										<span class="dateidentified"><?php echo $occArr['dateidentified']; ?></span>
									</div>
									<?php
									if($occArr['identificationreferences'] || $occArr['identificationremarks'] || $occArr['taxonremarks']){
										?>
										<div class="identificationreferences">
											<?php echo $occArr['identificationreferences']; ?>
										</div>
										<div class="identificationremarks">
											<?php echo $occArr['identificationremarks']; ?>
										</div>
										<div class="taxonremarks">
											<?php echo $occArr['taxonremarks']; ?>
										</div>
										<?php
									}
								}
								?>
								<div class="loc1div" style="margin-top:10px;">
									<span class="country"><?php echo $occArr['country'].($occArr['country']?', ':''); ?></span>
									<span class="stateprovince"><?php echo $occArr['stateprovince'].($occArr['stateprovince']?', ':''); ?></span>
									<?php
									$countyStr = trim($occArr['county']);
									if($countyStr){
										//if(!stripos($occArr['county'],' County') && !stripos($occArr['county'],' Parish')) $countyStr .= ' County';
										$countyStr .= ', ';
									}
									?>
									<span class="county"><?php echo $countyStr; ?></span>
									<span class="municipality"><?php echo $occArr['municipality'].($occArr['municipality']?', ':''); ?></span>
									<span class="locality">
										<?php
										$locStr = trim($occArr['locality']);
										if(substr($locStr,-1) != '.'){
											$locStr .= '.';
										}
										echo $locStr;
										?>
									</span>
								</div>
								<?php
								if($occArr['decimallatitude'] || $occArr['verbatimcoordinates']){
									?>
									<div class="loc2div">
										<?php
										if($occArr['decimallatitude'] && $occArr['decimallatitude']){
											echo '<span class="decimallatitude">'.$occArr['decimallatitude'].'</span>'.($occArr['decimallatitude']>0?'N':'S');
											echo '<span class="decimallongitude" style="margin-left:10px;">'.$occArr['decimallongitude'].'</span>'.($occArr['decimallongitude']>0?'E':'W').' ';
										}
										if($occArr['coordinateuncertaintyinmeters']) echo '<span style="margin-left:10px;">+-'.$occArr['coordinateuncertaintyinmeters'].' meters</span>';
										if($occArr['verbatimcoordinates']){
											if($occArr['decimallatitude']) echo ' [';
											?>
											<span class="verbatimcoordinates">
												<?php echo $occArr['verbatimcoordinates']; ?>
											</span>
											<?php
											if($occArr['decimallatitude']) echo ']';
										}
										if($occArr['geodeticdatum']) echo '<span style="margin-left:10px;">['.$occArr['geodeticdatum'].']</span>';
										?>
									</div>
									<?php
								}
								if($occArr['elevationinmeters']){
									?>
									<div class="elevdiv">
										Elev:
										<?php
										echo '<span class="elevationinmeters">'.$occArr['elevationinmeters'].'m.</span> ';
										if($occArr['verbatimelevation']) echo ' ('.$occArr['verbatimelevation'].')';
										?>
									</div>
									<?php
								}
								if($occArr['habitat']){
									?>
									<div class="habitat">
										<?php
										$habStr = trim($occArr['habitat']);
										if(substr($habStr,-1) != '.'){
											$habStr .= '.';
										}
										echo $habStr;
										?>
									</div>
									<?php
								}
								if($occArr['substrate']){
									?>
									<div class="substrate">
										<?php
										$substrateStr = trim($occArr['substrate']);
										if(substr($substrateStr,-1) != '.'){
											$substrateStr .= '.';
										}
										echo $substrateStr;
										?>
									</div>
									<?php
								}
								if($occArr['verbatimattributes'] || $occArr['establishmentmeans']){
									?>
									<div>
										<span class="verbatimattributes"><?php echo $occArr['verbatimattributes']; ?></span>
										<?php echo ($occArr['verbatimattributes'] && $occArr['establishmentmeans']?'; ':''); ?>
										<span class="establishmentmeans">
											<?php echo $occArr['establishmentmeans']; ?>
										</span>
									</div>
									<?php
								}
								if($occArr['associatedtaxa']){
									?>
									<div>
										Associated species:
										<span class="associatedtaxa"><?php echo $occArr['associatedtaxa']; ?></span>
									</div>
									<?php
								}
								if($occArr['occurrenceremarks']){
									?>
									<div class="occurrenceremarks"><?php echo $occArr['occurrenceremarks']; ?></div>
									<?php
								}
								if($occArr['typestatus']){
									?>
									<div class="typestatus"><?php echo $occArr['typestatus']; ?></div>
									<?php
								}
								?>
								<div class="collectordiv">
									<div class="collectordiv1" style="float:left;">
										<span class="recordedby"><?php echo $occArr['recordedby']; ?></span>
										<span class="recordnumber"><?php echo $occArr['recordnumber']; ?></span>
									</div>
									<div class="collectordiv2" style="float:right;">
										<span class="eventdate"><?php echo $occArr['eventdate']; ?></span>
									</div>
									<?php
									if($occArr['associatedcollectors']){
										?>
										<div class="associatedcollectors" style="clear:both;margin-left:10px;">
											With: <?php echo $occArr['associatedcollectors']; ?>
										</div>
										<?php
									}
									?>
								</div>
								<?php
								if($useBarcode && $occArr['catalognumber']){
									?>
									<div class="cnbarcode" style="clear:both;padding-top:15px;">
										<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
									</div>
									<?php
									if($occArr['othercatalognumbers']){
										?>
										<div class="othercatalognumbers" style="clear:both;text-align:center;">
											<?php echo $occArr['othercatalognumbers']; ?>
										</div>
										<?php
									}
								}
								elseif($showCatalogNumbers){
									if($occArr['catalognumber']){
										?>
										<div class="catalognumber" style="clear:both;text-align:center;">
											<?php echo $occArr['catalognumber']; ?>
										</div>
										<?php
									}
									if($occArr['othercatalognumbers']){
										?>
										<div class="othercatalognumbers" style="clear:both;text-align:center;">
											<?php echo $occArr['othercatalognumbers']; ?>
										</div>
										<?php
									}
								}
								?>
								<div class="lfooter"><?= htmlspecialchars($lFooter, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?></div>
								<?php
								if($useSymbBarcode){
									?>
									<hr style="border:dashed;" />
									<div class="symbbarcode" style="padding-top:10px;">
										<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occid; ?>" />
									</div>
									<?php
									if($occArr['catalognumber']){
										?>
										<div class="catalognumber" style="clear:both;text-align:center;">
											<?php echo $occArr['catalognumber']; ?>
										</div>
										<?php
									}
								}
								?>
							</td>
							<?php
						}
					}
					//Add missing <td> tags and close the table
					if(is_numeric($columnCount) && $labelCnt%$columnCount){
						$remaining = $columnCount-$labelCnt%$columnCount;
						for($i = 0;$i < $remaining;$i++){
							echo '<td>&nbsp;</td>';
						}
					}
					echo '</tr></table>';
					if(!$labelCnt) echo '<div style="font-weight:bold;text-size: 120%">No records were retrieved. Perhaps the quantity values were all set to 0?</div>';
				}
				?>
			</div>
		</body>
	</html>
	<?php
}
?>