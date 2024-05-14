<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/EOLManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../taxa/profile/eolmapper.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$statusStr = array_key_exists('status',$_REQUEST)?$_REQUEST['status']:'';

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}

$eolManager = new EOLManager();

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . " EOL Manager: "; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>"/>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script language=javascript>

	</script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>";
	<a href="../index.php">Home</a> &gt;&gt;
	<b>Encyclopedia of Life Manager</b>
</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Encyclopedia of Life Linkage Manager</h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:red;margin:15px;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			if($submitAction){
				?>
				<hr/>
				<div style="margin:15px;">
					<?php
					if($submitAction == 'Map Taxa'){
						$makePrimary = 0;
						$restart = 0;
						if(array_key_exists('makeprimary',$_POST) && $_POST['makeprimary']){
							$makePrimary = 1;
						}
						if(array_key_exists('restart',$_POST) && $_POST['restart']){
							$restart = 1;
						}
						$eolManager->mapTaxa($makePrimary,$_POST['tidstart'],$restart);
					}
					elseif($submitAction == 'Map Images'){
						$restart = 0;
						if(array_key_exists('restart',$_POST) && $_POST['restart']){
							$restart = 1;
						}
						$eolManager->mapImagesForTaxa($_POST['startindex'],$restart);
					}
					?>
				</div>
				<hr/>
				<?php
			}
			?>
			<div style="color:red;margin:15px;">
				Note: these processes may take a great deal of time to complete
			</div>
			<div style="margin:15px;">
				<fieldset style="padding:15px;">
					<legend><b>Taxa Mapping</b></legend>
					<div>
						This module will query EOL for all accepted taxa that do not currently have an EOL link nor identifier assignment.
						If an EOL taxon object is found, a link to EOL will be created for that taxon.
					</div>
					<div style="margin:10px;">
						Number of taxa not mapped to EOL:
						<b><?php echo $eolManager->getEmptyIdentifierCount(); ?></b>
						<div style="margin:10px;">
							<form name="taxamappingform" action="eolmapper.php" method="post">
								<input type="submit" name="submitaction" value="Map Taxa" />
								<div style="margin:15px;">
									TID Start Index: <input type="text" name="tidstart" value="" /><br />
									<input type="checkbox" name="restart" value="1" CHECKED /> Restart where left off within the last week<br />
									<input type="checkbox" name="makeprimary" value="1" CHECKED /> Make EOL primary link (sort order = 1)
								</div>
							</form>
						</div>
					</div>
				</fieldset>
				<fieldset style="margin-top:15px;padding:15px;">
					<legend><b>Image Mapping</b></legend>
					<div>
						This module will query the EOL image library for all accepted taxa currently linked to EOL
						that do not have any field images.
						Up to 5 images will be automatically linked in the mapping procedure.
					</div>
					<div style="margin:10px;">
						Number of accpeted taxa without images:
						<b><?php echo $eolManager->getImageDeficiencyCount(); ?></b>
						<div style="margin:10px;">
							<form name="imagemappingform" action="eolmapper.php" method="post">
								TID Start Index: <input type="text" name="startindex" value="" /><br/>
								<input type="checkbox" name="restart" value="1" CHECKED /> Restart where left off within the last week<br />
								<input type="submit" name="submitaction" value="Map Images" />
							</form>
						</div>
					</div>
				</fieldset>
			</div>
			<?php
		}
		else{
			echo '<div>You need to be a portal administrator to use this module</div>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
