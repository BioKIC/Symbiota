<?php
//--------------------------------------------------------------------
//  This Symbiota enhancement was made possible with support from
//  the United States Institute of Museum and Library Services grant
//  MG-70-19-0057-19, to the New York Botanical Garden (NYBG).
// Programming performed by Christopher D. Tyrrell, all errors and
//  omissions are his.
//--------------------------------------------------------------------

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/content/lang/checklists/voucheradmin.'.$LANG_TAG.'.php');

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';

$clManager = new ChecklistManager();

$dynamPropsArr = array();
if($clid) {
	$clManager->setClid($clid);
	$metaArr = $clManager->getClMetaData();
	if(!empty($metaArr['dynamicProperties'])){
		$dynamPropsArr = json_decode($metaArr['dynamicProperties'], true);
	}
}

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('ClAdmin', $USER_RIGHTS) && in_array($clid, $USER_RIGHTS['ClAdmin']))){
	$isEditor = 1;
}
if($isEditor){
	?>
	<div id="externalServiceVoucherDiv">
	<div style="margin:10px;">
	<div style="clear:both;">
		<div style="margin:10px;">
			<?php echo $LANG['LISTEDBELOWEXTERNAL'];?>
		</div>
		<?php
		if($taxaArray = $clManager->getTaxaList()){
			?>
			<div style="margin:20px;">
				<style type="text/css">
					#extvoucher-taxalist-div {
						margin-bottom: 10px;
					}
					.extvoucher-label {
						display: inline-block;
						width: 250px;
						text-align: right;
					}
				</style>
				<script src="../js/symb/checklists.externalserviceapi.js"></script>
				<div id="extvoucher-taxalist-div">
					<form name="externalVoucherForm" action="voucheradmin.php" method="post">
						<?php
						$button = '<button name="submitaction" type="submit" value="linkExternalVouchers">'.$LANG['SAVEEXTVOUCH'].'</button>';
						$prevGroup = '';
						$arrForExternalServiceApi = '';
						$cnt = 1;
						foreach($taxaArray as $tid => $sppArr){
							$group = $sppArr['taxongroup'];
							if($group != $prevGroup){
								$famUrl = '../taxa/index.php?taxauthid=1&taxon='.strip_tags($group).'&clid='.$clid;
								//Edit family name display style here
								?>
								<div class="family-div" id="<?php echo strip_tags($group);?>">
									<a href="<?php echo $famUrl; ?>" target="_blank" style="color:black;"><?php echo $group;?></a>
								</div>
								<?php
								$prevGroup = $group;
							}
							$taxonWithDashes = str_replace(' ', '-', $sppArr['sciname']);
							echo '<div class="taxon-container">';
							echo '<a href="#" target="_blank" id="a-' . $taxonWithDashes . '" style="pointer-events:none;">';
							echo '<label class="extvoucher-label" id="l-' . $taxonWithDashes . '">' . $sppArr['sciname'] . ' ' . (isset($sppArr['author']) ? $sppArr['author'] : '') . '</label></a>&nbsp;';
							?>
							<input type="text" name="i-<?php echo $tid; ?>" id="i-<?php echo $taxonWithDashes; ?>" style="background-color:#E3E7EB">
							<input type="hidden" name="<?php echo $tid; ?>" id="v-<?php echo $taxonWithDashes; ?>">
							<span class="view-specimen-span printoff">
								<a style="text-decoration: none;" onclick="retrieveVoucherInfo('<?php echo $taxonWithDashes; ?>')">
									<?php echo (isset($LANG['LOOKUPEXT'])?$LANG['LOOKUPEXT']:'Lookup external vouchers'); ?>
								</a>
							</span>
							<span id="r-<?php echo $taxonWithDashes; ?>"></span>
							<?php
							echo "</div>\n";
							$scinameasid = str_replace(' ', '-', $sppArr['sciname']);
							$arrForExternalServiceApi .= ($arrForExternalServiceApi ? ',' : '') . "'" . $scinameasid . "'";
							if($cnt%15 == 0) echo $button;
							$cnt++;
						}
						echo $button;
						?>
						<input name="pid" type="hidden" value="<?= $pid ?>" >
						<input name="clid" type="hidden" value="<?= $clid ?>" >
					</form>
				</div>
				<?php
				if(isset($dynamPropsArr['externalservice']) && $dynamPropsArr['externalservice'] == 'inaturalist') {
					?>
					<script>
						<?php
						echo 'const checklisttaxa = [' . $arrForExternalServiceApi . '];';
						echo 'const externalProjID = "' . ($dynamPropsArr['externalserviceid']?$dynamPropsArr['externalserviceid']:'') . '";';
						echo 'const iconictaxon = "' . ($dynamPropsArr['externalserviceiconictaxon']?$dynamPropsArr['externalserviceiconictaxon']:'') . '";';
						?>
						// iNaturalist Integration
						// Note: the two part request (...Page1 vs ...AdditionalPages) is performed
						// to allow for a variable number of total results. There will always be a
						// first page, but there may be 0 or more additional pages. The answer is
						// extracted from the response to the first ("Page1") fetch request.
						fetchiNatPage1(externalProjID, iconictaxon)
							.then(pageone => {
								const totalresults = pageone.total_results;
								const perpage = pageone.per_page;
								const loopnum = Math.ceil(totalresults / perpage);
								const taxalist1 = extractiNatTaxaIdAndName(pageone.results);
								fetchiNatAdditionalPages(loopnum, externalProjID, iconictaxon)
								.then(pagestwoplus => {
									const taxalist2 = pagestwoplus.map(page => extractiNatTaxaIdAndName(page.results))
									taxalist = taxalist1.concat(taxalist2.flat());
									checklisttaxa.forEach( taxon => {
										let anchortag = document.getElementById('a-'+taxon);
										let txtboxtag = document.getElementById('i-'+taxon);
										let labeltag = document.getElementById('l-'+taxon);
										let taxonwithspaces = taxon.replaceAll('-', ' ');
										const idx = taxalist.findIndex( elem => elem.name === taxonwithspaces);
										if(idx >= 0) {
											anchortag.setAttribute("style", "pointer-events:auto;");
											txtboxtag.setAttribute("style", "background-color: #FFFFFF;");
											labeltag.setAttribute("style", "text-decoration: underline");
											anchortag.setAttribute("href", `https://www.inaturalist.org/observations?project_id=${externalProjID}&taxon_id=${taxalist[idx].id}&quality_grade=research`);
										}
									})
								})
								.catch(error => {
									error.message;
								})
							})
					</script>
					<?php
				}
				?>
			</div>
			<?php
		}
		else echo '<h2>'.$LANG['EMPTY_LIST'].'</h2>';
		?>
	</div>
	</div>
	</div>
	<?php
}
?>