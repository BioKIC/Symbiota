<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = $_POST["collid"];
$lHeader = $_POST['lheading'];
$lFooter = $_POST['lfooter'];
$detIdArr = $_POST['detid'];
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';
$rowsPerPage = array_key_exists('rowcount',$_POST)?$_POST['rowcount']:3;;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])) || (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"]))){
		$isEditor = 1;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Default Annotations</title>
		<style type="text/css">
			body {font-family:arial,sans-serif;}
			table.labels { page-break-before:auto; }
			table.labels tr td { page-break-inside: avoid; white-space: nowrap; }
			<?php
			$marginSize = 5;
			if(array_key_exists('marginsize',$_POST) && $_POST['marginsize']) $marginSize = $_POST['marginsize'];
			echo 'table.labels {border-spacing:'.$marginSize.'px;}';
			$widthStr = '600px';
			if($rowsPerPage > 1) $widthStr = 100/$rowsPerPage.'%';
			$borderWidth = 1;
			if(array_key_exists('borderwidth',$_POST)) $borderWidth = $_POST['borderwidth'];
			?>
			table.labels td {width:<?php echo $widthStr; ?>;padding:8px;border:<?php echo $borderWidth; ?>px solid black;}
			p.printbreak {page-break-after:always;}
			.lheader {width:100%;margin-bottom:5px;text-align:center;font:bold 9pt arial,sans-serif;}
			.scientificnamediv {clear:both;font-size:10pt;}
			.subfielddiv {font-size:8pt;margin-top:5px;clear:both;}
			.lfooter {clear:both;width:100%;text-align:center;font:bold 9pt arial,sans-serif;margin-top:10px;}
			.screen-reader-only {
				position: absolute;
				left: -10000px;
			}
		</style>
	</head>
	<body style="background-color:#ffffff;">
		<h1 class="page-heading screen-reader-only">Default Annotations</h1>
		<div>
			<?php
			if($isEditor){
				if($action){
					$speciesAuthors = ((array_key_exists('speciesauthors',$_POST) && $_POST['speciesauthors'])?1:0);
					$labelArr = $labelManager->getAnnoArray($_POST['detid'], $speciesAuthors);
					if(array_key_exists('clearqueue',$_POST) && $_POST['clearqueue']){
						$labelManager->clearAnnoQueue($_POST['detid']);
					}
					$labelCnt = 0;
					echo '<table class="labels">';
					foreach($labelArr as $occid => $occArr){
						$headerStr = trim($lHeader);
						$footerStr = trim($lFooter);
						$dupCnt = $_POST['q-'.$occid];
						for($i = 0;$i < $dupCnt;$i++){
							$labelCnt++;
							if($rowsPerPage == 1 || $labelCnt%$rowsPerPage == 1) echo '<tr>'."\n";
							?>
							<td class="" valign="top">
								<?php
								if($headerStr){
									?>
									<div class="lheader">
										<?php echo $headerStr; ?>
									</div>
									<?php
								}
								?>
								<div class="scientificnamediv">
									<?php
									if($occArr['identificationqualifier']) echo '<span class="identificationqualifier">'.$occArr['identificationqualifier'].'</span> ';
									$scinameStr = $occArr['sciname'];
									$parentAuthor = (array_key_exists('parentauthor',$occArr)?' '.$occArr['parentauthor']:'');
									$scinameStr = str_replace(' sp. ','</i></b>'.$parentAuthor.' <b>sp.</b>',$scinameStr);
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
								if($occArr['identifiedby'] || $occArr['dateidentified']){
									if($occArr['identifiedby']){
										?>
										<div class="subfielddiv">
											<?php
											if($occArr['dateidentified']){
												?>
												<div style="float:right">
													<?php echo $occArr['dateidentified']; ?>
												</div>
												<?php
											}
											?>
											<div>
												Det: <?php echo $occArr['identifiedby']; ?>
											</div>
										</div>
										<?php
									}
									if(array_key_exists('printcatnum',$_POST) && $_POST['printcatnum'] && $occArr['catalognumber']){
										?>
										<div class="subfielddiv">
											Catalog #: <?php echo $occArr['catalognumber']; ?>
										</div>
										<?php
									}
									if($occArr['identificationreferences']){
										?>
										<div class="subfielddiv"><?php echo $occArr['identificationreferences']; ?></div>
										<?php
									}
									if($occArr['identificationremarks']){
										?>
										<div class="subfielddiv"><?php echo $occArr['identificationremarks']; ?></div>
										<?php
									}
								}
								if($footerStr){
									?>
									<div class="lfooter">
										<?php echo $footerStr; ?>
									</div>
									<?php
								}
								?>
							</td>
							<?php
							if($labelCnt%$rowsPerPage == 0){
								echo '</tr>'."\n";
							}
						}
					}
					echo '</table>';
					if($labelCnt%$rowsPerPage){
						$remaining = $rowsPerPage-($labelCnt%$rowsPerPage);
						for($i = 0;$i < $remaining;$i++){
							echo '<td></td>';
						}
						echo '</tr></table>'."\n"; //If label count is odd, close final labelrowdiv
					}
				}
			}
			?>
		</div>
	</body>
</html>