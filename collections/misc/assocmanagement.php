<?php
require_once('../../config/symbini.php');
require_once($SERVER_ROOT.'/classes/OccurrenceAssociations.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/assocmanagement.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

//Use following ONLY if login is required
if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/misc/assocmanagement.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$assocHandler = new OccurrenceAssociations();
$collmeta = array();
if($collid) $collmeta = $assocHandler->getCollectionMetadata($collid);

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		//If a page related to collections, one maight want to...
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $LANG['OCC_BATCH']; ?></title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script type="text/javascript">

		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js?ver=140310" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php"><?php echo htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<?php
			if($collid) echo '<a href="collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">Collection Management</a> &gt;&gt; ';
			?>
			<b><?php echo (isset($LANG['OCC_AS_MAN'])?$LANG['OCC_AS_MAN']:'Occurrence Association Manager'); ?></b>
		</div>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h1 class="page-heading"><?= $LANG['OCC_BATCH']; ?></h1>
			<?php
			if($isEditor){
				if($formSubmit == 'Parse Associated Taxa'){
					$assocHandler->parseAssociatedTaxa($collid);
				}
				?>
				<fieldset style="margin:20px;padding:15px">
					<legend><b><?php echo (isset($LANG['TAXA_PARSING'])?$LANG['TAXA_PARSING']:'Associated Taxa Parsing'); ?></b></legend>
					<form name="" action="assocmanagement.php" method="post">
						<div>
							<?php
							$statArr = $assocHandler->getParsingStats($collid);
							echo '<div style="margin:10px 0px;font-weight:bold;font-size:120%;">';
							if($collmeta){
								echo $collmeta['collname'].' ('.$collmeta['instcode'].($collmeta['collcode']?'-'.$collmeta['instcode']:'').')';
							}
							else{
								echo 'All Collections';
							}
							echo '</div>';
							echo '<div style="margin:3px"><b>'.(isset($LANG['NUM_PARSED'])?$LANG['NUM_PARSED']:'Number of parsed specimens').':</b> '.$statArr['parsed'].'</div>';
							echo '<div style="margin:3px"><b>'.(isset($LANG['NUM_UNPARSED'])?$LANG['NUM_UNPARSED']:'Number of unparsed specimens').':</b> '.$statArr['unparsed'].'</div>';
							echo '<div style="margin:3px"><b>'.(isset($LANG['NON_INDEXED'])?$LANG['NON_INDEXED']:'Number of non-indexed parsing terms').':</b> '.$statArr['failed'].' (from '.$statArr['failedOccur'].' '.(isset($LANG['SPEC_RECS'])?$LANG['SPEC_RECS']:'specimen records').')'.'</div>';
							?>
						</div>
						<div style="margin:20px;">
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="formsubmit" type="submit" value="Parse Associated Taxa" />
						</div>
					</form>
				</fieldset>
				<?php
			}
			else{
				echo '<div style="font-weight:bold;font-size:130%;">'.(isset($LANG['PERM_PROB'])?$LANG['PERM_PROB']:'ERROR: permissions failure').'</div>';
			}
			?>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>