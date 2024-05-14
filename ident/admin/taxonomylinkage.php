<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyCharAdmin.php');
header("Content-Type: text/html; charset=".$CHARSET);

$cid = array_key_exists('cid',$_REQUEST)?$_REQUEST['cid']:0;
$langId = array_key_exists('langid',$_REQUEST)?$_REQUEST['langid']:'';

$keyManager = new KeyCharAdmin();
$keyManager->setLangId($langId);
$keyManager->setCid($cid);
$tLinks = $keyManager->getTaxonRelevance();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$( "#relevanceinput" ).autocomplete({
				source: "rpc/taxasuggest.php",
				minLength: 2,
				autoFocus: true,
				select: function( event, ui ) {
					if(ui.item){
						$( "#relevancetidinput" ).val(ui.item.id);
					}
					else{
						$( "#relevancetidinput" ).val("");
					}
				},
				change: function( event, ui ) {
					if($( "#relevancetidinput" ).val() == ""){
						$.ajax({
							type: "POST",
							url: "rpc/taxonvalidation.php",
							data: { term: $( this ).val() }
						}).done(function( msg ) {
							if(msg == ""){
								alert("Taxonomic name not found with thesaurus ");
							}
							else{
								$( "#relevancetidinput" ).val(msg);
							}
						});
					}
				}
			});
		});

		$( "#relevanceinput" ).focus(function() {
			$( "#relevancetidinput" ).val("");
		});

		function validateRelevanceForm(f){
			if(f.relsciname.value == ""){
				alert("Taxon field is empty");
				return false;
			}
			if(f.tid.value == ""){
				alert("unable to obtain taxonomic thesaurus identifier for " + f.relsciname.value);
				return false;
			}
			return true;
		}
	</script>
</head>
<body>
	<h1 class="page-heading">Link Character to Taxa</h1>
	<div id="tlinkdiv" style="margin:15px;">
		<div style="margin:10px;">
			<b>Taxonomic relevance of character</b> -
			Tag taxonomic nodes where character is most relevant.
			Taxonomic branches can also be excluded (e.g. relevant to order A by exclude families X, Y, and Z).
		</div>
		<div style="margin:20px;">
			<?php
			if($tLinks){
				if(isset($tLinks['include'])){
					?>
					<fieldset style="padding:20px;">
						<legend><b>Relevant Taxa</b></legend>
						<?php
						foreach($tLinks['include'] as $tid => $tArr){
							?>
							<div style="margin:3px;clear:both;">
								<?php
								echo '<div style="float:left;"><b>'.$tArr['sciname'].'</b>'.($tArr['notes']?' - '.$tArr['notes']:'').'</div> ';
								?>
								<form name="delTaxonForm" action="chardetails.php" method="post" style="float:left;margin-left:5px;" onsubmit="return comfirm('Are you sure you want to delete this relationship?')">
									<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
									<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
									<input name="formsubmit" type="hidden" value="deltaxon" />
									<input type="image" src="../../images/del.png" style="width:1.3em;" />
								</form>
							</div>
							<?php
						}
						?>
					</fieldset>
					<?php
				}
				if(isset($tLinks['exclude'])){
					?>
					<fieldset style="padding:20px;">
						<legend><b>Excluding Taxa</b></legend>
						<?php
						foreach($tLinks['exclude'] as $tid => $tArr){
							?>
							<div style="margin:3px;">
								<?php
								echo '<div style="float:left;"><b>'.$tArr['sciname'].'</b>'.($tArr['notes']?' - '.$tArr['notes']:'').'</div> ';
								?>
								<form name="delTaxonForm" action="chardetails.php" method="post" style="float:left;margin-left:5px;" onsubmit="return comfirm('Are you sure you want to delete this relationship?')">
									<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
									<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
									<input name="formsubmit" type="hidden" value="deltaxon" />
									<input type="image" src="../../images/del.png" style="width:1.3em;" />
								</form>
							</div>
							<?php
						}
						?>
					</fieldset>
					<?php
				}
			}
			else{
				?>
				<div style="font-weight:bold">
					This character has not yet been linked to the taxonomic tree.
					This character will not be available until at least one relevant link is established.
				</div>
				<?php
			}
			?>
		</div>
		<div style="margin:20px;">
			<form name="taxonAddForm" action="chardetails.php" method="post" onsubmit="return validateRelevanceForm(this)">
				<fieldset style="padding:20px;">
					<legend><b>Add Taxonomic Relevance Definition</b></legend>
					<div style="height:15px;">
						<div style="margin:3px;">
							<b>Taxon Name:</b>
							<input type="text" id="relevanceinput" name="relsciname" style="width:300px" />
							<input type="hidden" id="relevancetidinput" name="tid" />
						</div>
						<div style="float:left;margin:3px;">
							<b>Relevance to taxon:</b>
							<select name="relation">
								<option value="include">Relevant</option>
								<option value="exclude">Exclude</option>
							</select>
						</div>
					</div>
					<div style="margin:3px;clear:both;">
						<b>Editor notes:</b>
						<input name="notes" type="text" value="" style="width:80%;" />
					</div>
					<div style="margin:15px;">
						<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
						<button name="formsubmit" type="submit" value="Save Taxonomic Relevance">Save Taxonomic Relevance</button>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</body>
</html>