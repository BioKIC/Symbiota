<?php
$LANG = array();
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydelete.'.$LANG_TAG.'.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydelete.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydelete.' . $LANG_TAG . '.php');
	else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydelete.en.php');

$tid = filter_var($_REQUEST['tid'], FILTER_SANITIZE_NUMBER_INT) ?? '';
$genusStr = $_REQUEST['genusstr'] ?? '';

$taxonEditorObj = new TaxonomyEditorManager();
$taxonEditorObj->setTid($tid);
$verifyArr = $taxonEditorObj->verifyDeleteTaxon();

//Sanitation
$genusStr = $taxonEditorObj->cleanOutStr($genusStr);
?>
<script>
	$(document).ready(function() {

		$("#remapvalue").autocomplete({
				source: "rpc/gettaxasuggest.php",
				minLength: 2
			}
		);
	});

	function submitRemapTaxonForm(f){
		if(f.remapvalue.value == ""){
			alert("<?php echo $LANG['NO_TARGET_TAXON'] ?>");
			return false;
		}
		$.ajax({
			type: "POST",
			url: "rpc/gettid.php",
			data: { sciname: f.remapvalue.value }
		}).done(function( msg ) {
			if(msg == 0){
				alert("<?php echo $LANG['TAXON_NOT_FOUND']; ?>");
				f.remaptid.value = "";
			}
			else{
				f.remaptid.value = msg;
				f.submit();
			}
		});
	}
</script>
<div style="min-height:400px; height:auto !important; height:400px; ">
	<div style="margin:15px 0px">
		<?php echo $LANG['TAXON_MUST_BE_EVALUATED']; ?>

	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['CHILDREN_TAXA']; ?></b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('child',$verifyArr)){
				$childArr = $verifyArr['child'];
				echo '<div style="color:red;">' . $LANG['CHILDREN_EXIST'] . '</div>';
				foreach($childArr as $childTid => $childSciname){
					echo '<div style="margin:3px 10px;"><a href="taxoneditor.php?tid=' . htmlspecialchars($childTid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($childSciname, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
				}
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_CHILDREN']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['SYN_LINKS']; ?></b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('syn',$verifyArr)){
				$synArr = $verifyArr['syn'];
				echo '<div style="color:red;">' . $LANG['SYN_EXISTS'] . '</div>';
				foreach($synArr as $synTid => $synSciname){
					echo '<div style="margin:3px 10px;"><a href="taxoneditor.php?tid=' . htmlspecialchars($synTid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($synSciname, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
				}
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_SYNS']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b>Images</b>
		<div style="margin:10px">
			<?php
			if($verifyArr['img'] > 0){
				?>
				<span style="color:red;"><?php echo $LANG['WARNING'] . ": " . $verifyArr['img'] . ' ' . $LANG['IMGS_LINKED']; ?></span>
				<?php
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_IMGS']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['VERNACULARS']; ?></b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('vern',$verifyArr)){
				$displayStr = implode(', ',$verifyArr['vern']);
				?>
				<span style="color:red;"><?php echo $LANG['LINKED_VERNACULAR']; ?>:</span> <?php echo $displayStr; ?>
				<?php
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_VERNACULAR']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['TEXT_DESCRIPTIONS']; ?></b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('tdesc',$verifyArr)){
				?>
				<span style="color:red;"><?php echo $LANG['DESC_EXISTS']; ?>:</span>
				<ul>
					<?php
					echo '<li>'.implode('</li><li>',$verifyArr['tdesc']).'</li>';
					?>

				</ul>
				<?php
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_DESCS']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['OCC_RECORDS']; ?>:</b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('occur',$verifyArr)){
				?>
				<span style="color:red;"><?php echo $LANG['LINKED_OCC_EXIST']; ?>:</span>
				<ul>
					<?php
					foreach($verifyArr['occur'] as $occid){
						echo '<li>';
						echo '<a href="../../collections/individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">#' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
						echo '</li>';
					}
					?>
				</ul>
				<?php
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_OCCS_LINKED']; ?>
				<?php
			}
			?>
			<?php
			if(array_key_exists('dets',$verifyArr)){
				?>
				<span style="color:red;"><?php echo $LANG['DETS_EXIST']; ?>:</span>
				<ul>
					<?php
					foreach($verifyArr['dets'] as $occid){
						echo '<li>';
						echo '<a href="../../collections/individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">#' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
						echo '</li>';
					}
					?>
				</ul>
				<?php
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_DETS_LINKED']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['CHECKLISTS']; ?>:</b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('cl',$verifyArr)){
				$clArr = $verifyArr['cl'];
				?>
				<span style="color:red;"><?php echo $LANG['CHECKLISTS_EXIST']; ?>:</span>
				<ul>
					<?php
					foreach($clArr as $k => $v){
						echo '<li><a href="../../checklists/checklist.php?clid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">';
						echo $v;
						echo '</a></li>';
					}
					?>
				</ul>
				<?php
			}
			else{
				echo '<span style="color:green;">' . $LANG['APPROVED'] . ':</span> ';
				echo $LANG['NO_CHECKLISTS'];
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?= $LANG['MORPHO_CHARACTERS']; ?>:</b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('kmdecr',$verifyArr)){
				echo '<span style="color:red;">';
				echo $LANG['WARNING'] . ': ' . $verifyArr['kmdecr'] . $LANG['LINKED_MORPHO'];
				echo '</span>';
			}
			else{
				echo '<span style="color:green;">' . $LANG['APPROVED'] . ':</span> ';
				echo $LANG['NO_MORPHO'];
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<b><?php echo $LANG['LINKED_RESOURCES']; ?>:</b>
		<div style="margin:10px">
			<?php
			if(array_key_exists('link',$verifyArr)){
				?>
				<span style="color:red;"><?php echo $LANG['LINKED_RESOURCES_EXIST']; ?></span>
				<ul>
					<?php
					echo '<li>'.implode('</li><li>',$verifyArr['link']).'</li>';
					?>

				</ul>
				<?php
			}
			else{
				?>
				<span style="color:green;"><?php echo $LANG['APPROVED']; ?>:</span> <?php echo $LANG['NO_RESOURCES']; ?>
				<?php
			}
			?>
		</div>
	</div>
	<div style="margin:15px;">
		<fieldset style="padding:15px;">
			<legend><b><?php echo $LANG['REMAP_RESOURCES']; ?></b></legend>
			<form name="remaptaxonform" method="post" action="taxoneditor.php">
				<div style="margin-bottom:5px;">
					<?php echo $LANG['TARGET_TAXON']; ?>:
					<input id="remapvalue" name="remapvalue" type="text" value="" style="width:550px;" /><br/>
					<input name="remaptid" type="hidden" value="" />
				</div>
				<div>
					<button name="submitbutton" type="button" onclick="submitRemapTaxonForm(this.form)"><?php echo $LANG['REMAP_TAXON']; ?></button>
					<input name="submitaction" type="hidden" value="remapTaxon" />
					<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
					<input name="genusstr" type="hidden" value="<?php echo $genusStr; ?>" />
				</div>
			</form>
		</fieldset>
	</div>
	<div style="margin:15px;">
		<fieldset style="padding:15px;">
			<legend><b><?php echo $LANG['DELETE_TAX_AND_RES']; ?></b></legend>
			<div style="margin:10px 0px;">
			</div>
			<form name="deletetaxonform" method="post" action="taxoneditor.php" onsubmit="return confirm('<?php echo $LANG['SURE_DELETE']; ?>')">
				<?php
				$deactivateStr = '';
				if(array_key_exists('child',$verifyArr)) $deactivateStr = 'disabled';
				if(array_key_exists('syn',$verifyArr)) $deactivateStr = 'disabled';
				if($verifyArr['img'] > 0) $deactivateStr = 'disabled';
				if(array_key_exists('tdesc',$verifyArr)) $deactivateStr = 'disabled';
				echo '<button class="button-danger" name="submitaction" type="submit" value="deleteTaxon" ' . $deactivateStr . '>' . $LANG['DELETE_TAXON'] . '</button>';
				?>
				<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
				<input name="genusstr" type="hidden" value="<?php echo $genusStr; ?>" />
				<div style="margin:15px 5px">
					<?php
					if($deactivateStr){
						?>
						<div style="font-weight:bold;">
							<?php echo $LANG['CANNOT_DELETE_TAXON']; ?>
						</div>
						<?php
					}
					else{
						if(array_key_exists('vern',$verifyArr)){
							?>
							<div style="color:red;">
								<?php echo $LANG['VERNACULARS_DELETE']; ?>
							</div>
							<?php
						}
						if(array_key_exists('kmdecr',$verifyArr)){
							?>
							<div style="color:red;">
								<?php echo $LANG['MORPH_DELETE']; ?>
							</div>
							<?php
						}
						if(array_key_exists('cl',$verifyArr)){
							?>
							<div style="color:red;">
								<?php echo $LANG['CHECKLIST_DELETE']; ?>
							</div>
							<?php
						}
						if(array_key_exists('link',$verifyArr)){
							?>
							<div style="color:red;">
								<?php echo $LANG['LINKED_RES_DELETE']; ?>
							</div>
							<?php
						}
					}
					?>
				</div>
			</form>
		</fieldset>
	</div>
</div>
