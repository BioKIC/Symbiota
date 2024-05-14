<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomyloader.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/taxa/taxonomy/taxonomyloader.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomyloader.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../taxa/taxonomy/taxonomyloader.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$tid = array_key_exists('tid',$_REQUEST) ? $_REQUEST['tid'] : '';
$status = '';

//Sanitation
if(!is_numeric($tid)) $tid = 0;

$loaderObj = new TaxonomyEditorManager();

$isEditor = false;
if($IS_ADMIN || array_key_exists('Taxonomy',$USER_RIGHTS)){
	$isEditor = true;
}

if($isEditor){
	if(array_key_exists('sciname',$_POST)){
		$status = $loaderObj->loadNewName($_POST);
		if(is_int($status)){
		 	header('Location: taxoneditor.php?tid='.$status);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['TAXON_LOADER']; ?>: </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/taxa.taxonomyloader.js?ver=4"></script>
	<style>
		.search-bar-long {
			width: 35rem;
		}
		.search-bar-short {
			width: 15rem;
		}
		.search-bar-extraShort {
			width: 5rem;
		}
		.left-column {
			float: left;
			width: 170px;
		}
	</style>
</head>
<body>
<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="taxonomydisplay.php"><?php echo htmlspecialchars($LANG['TAX_TREE_VIEW'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<b><?php echo $LANG['TAXONOMY_LOADER']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['TAXON_LOADER']; ?></h1>
		<?php
		if($status){
			echo '<div style="color:red;font-size:120%;">'.$status.'</div>';
		}
		if($isEditor){
			?>
			<form id="loaderform" name="loaderform" action="taxonomyloader.php" method="post" onsubmit="return verifyLoadForm(this)">
				<fieldset>
					<legend><b><?php echo $LANG['ADD_NEW_TAXON']; ?></b></legend>
					<div>
						<div class="left-column">
							<label for="sciname"> 
								<?php echo $LANG['TAXON_NAME']; ?>: 
							</label>
						</div>
						<input type="text" id="sciname" name="sciname" class="search-bar-long" value="" onchange="parseName(this.form)"/>
					</div>
					<div>
						<div class="left-column">
							<label for="author">
								<?php echo $LANG['AUTHOR']; ?>:
							</label>
						</div>
						<input type='text' id='author' name='author' class='search-bar-long' />
					</div>
					<div style="clear:both;">
						<div class="left-column"> 
							<label for="rankid">
								 <?php echo $LANG['TAXON_RANK']; ?>: 
								</label>
						</div>
						<select id="rankid" name="rankid" title="Rank ID" class='search-bar-short bottom-breathing-room-rel-sm'>
							<option value=""><?php echo $LANG['SEL_TAX_RANK']; ?></option>
							<option value="0"><?php echo $LANG['NON_RANKED_NODE']; ?></option>
							<option value="">--------------------------------</option>
							<?php
							$tRankArr = $loaderObj->getRankArr();
							foreach($tRankArr as $rankId => $nameArr){
								foreach($nameArr as $rName){
									echo '<option value="' . $rankId . '" ' . ($rankId == 220 ? ' SELECTED' : '') . '>' . $rName . '</option>';
								}
							}
							?>
						</select>
						<script src="../../js/symb/taxa.taxonomyloader.js"> </script>
					</div>
					<div style="clear:both;">
						<div class="left-column">
							<label id="unitind1label" for="unitind1">
								<?php echo $LANG['GENUS_NAME']; ?>:
							</label>
						</div>
						<select id="unitind1" name="unitind1" onchange="updateFullname(this.form)">
							<option value=""></option>
							<option value="&#215;">&#215;</option>
							<option value="&#8224;">&#8224;</option>
						</select>
						<input type='text' id='unitname1' name='unitname1' onchange="updateFullname(this.form)" class='search-bar' aria-label="<?php echo $LANG['GENUS_OR_BASE']; ?>" title="<?php echo $LANG['GENUS_OR_BASE']; ?>"/>
					</div>
					<?php
					if ($rankId > 150){
						?>
							<div id="div1hide" style="clear:both;">
								<div class="left-column">
									<label for="unitind2">
										<?php echo $LANG['UNITNAME2']; ?>:
									</label>
								</div>
								<select id="unitind2" name="unitind2" onchange="updateFullname(this.form)">
									<option value=""></option>
									<option value="&#215;">&#215;</option>
								</select>
								<input type='text' id='unitname2' name='unitname2' onchange="updateFullname(this.form)" class='search-bar' aria-label="<?php echo (isset($LANG['SPECIF_EPITHET_FIELD']) ? $LANG['SPECIF_EPITHET_FIELD'] : 'Specific Epithet Field'); ?>" title="<?php echo (isset($LANG['SPECIF_EPITHET_FIELD']) ? $LANG['SPECIF_EPITHET_FIELD'] : 'Specific Epithet Field'); ?>"/>
							</div>
							<div id="div2hide" style="clear:both;">
								<div class="left-column">
									<label for="unitind3">
										<?php echo $LANG['UNITNAME3']; ?>:
									</label>
								</div>
								<input type='text' id='unitind3' name='unitind3' onchange="updateFullname(this.form)" class='search-bar-extraShort' aria-label='<?php echo $LANG['UNITNAME3']; ?>:' title='<?php echo $LANG['RANK_FIELD']; ?>'/>
								<input type='text' id='unitname3' name='unitname3' onchange="updateFullname(this.form)" class='search-bar' aria-label="<?php echo $LANG['INFRA_EPITHET_FIELD']; ?>" title="<?php echo $LANG['INFRA_EPITHET_FIELD']; ?>" />
							</div>
					<?php
					}?>

					<div style="clear:both;">
						<div class="left-column">
							<label for="parentname">
								<?php echo $LANG['PARENT_TAXON']; ?>:
							</label>
						</div>
						<input type="text" id="parentname" name="parentname" class='search-bar' />
						<span id="addparentspan" style="display:none;">
							<a id="addparentanchor" href="taxonomyloader.php?target=" target="_blank">
								<?php echo htmlspecialchars($LANG['ADD_PARENT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE	); ?>
							</a>
						</span>
						<input id="parenttid" name="parenttid" type="hidden" value="" />
					</div>
					<div style="clear:both;">
						<div class="left-column">
							<label for="notes">
								<?php echo $LANG['NOTES']; ?>:
							</label>
						</div>
						<input type='text' id='notes' name='notes' class='search-bar-long'/>
					</div>
					<div style="clear:both;">
						<div class="left-column">
							<label for="source"> <?php echo $LANG['SOURCE']; ?>:
							</label>
						</div>
						<input type='text' id='source' name='source' class='search-bar-long'/>
					</div>
					<div style="clear:both;">
						<div class="left-column">
							<label for="securitystatus"> <?php echo $LANG['LOC_SECURITY']; ?>:
							</label>
						</div>
						<select id="securitystatus" name="securitystatus" class="search-bar-short">
							<option value="0"><?php echo $LANG['NO_SECURITY']; ?></option>
							<option value="1"><?php echo $LANG['HIDE_LOC_DETAILS']; ?></option>
						</select>
					</div>
					<div style="clear:both;">
						<fieldset>
							<legend><b><?php echo $LANG['ACCEPT_STATUS']; ?></b></legend>
							<div>
								<input type="radio" id="isaccepted" name="acceptstatus" value="1" onchange="acceptanceChanged(this.form)" checked> <label for="isaccepted">  <?php echo $LANG['ACCEPTED']; ?> </label>
								<input type="radio" id="isnotaccepted" name="acceptstatus" value="0" onchange="acceptanceChanged(this.form)"> <label for="isnotaccepted">  <?php echo $LANG['NOT_ACCEPTED']; ?> </label>
							</div>
							<div id="accdiv" style="display:none;margin-top:3px;">
								<div>
									<div class="left-column">
										<label for="acceptedstr"> <?php echo $LANG['ACCEPTED_TAXON']; ?>:
										</label>
									</div>
									<input id="acceptedstr" name="acceptedstr" type="text" class="search-bar-long" />
									<input id="tidaccepted" name="tidaccepted" type="hidden" />
								</div>
								<div>
									<div class="left-column">
										<label for="unacceptabilityreason"> <?php echo $LANG['UNACCEPT_REASON']; ?>:
										</label>
									</div>
									<input type='text' id='unacceptabilityreason' name='unacceptabilityreason' class='search-bar-long' />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="top-breathing-room-rel">
						<button type="submit" name="submitaction" value="submitNewName" ><?php echo $LANG['SUBMIT_NEW_NAME']; ?></button>
					</div>
				</fieldset>
			</form>
			<?php
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				<?php echo $LANG['NOT_AUTH']; ?>
			</div>
			<?php
		}
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</div>
</body>
</html>