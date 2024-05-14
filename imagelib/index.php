<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryBrowser.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/imagelib/index.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/imagelib/index.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/imagelib/index.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$taxon = array_key_exists('taxon', $_REQUEST) ? $_REQUEST['taxon'] : '';
$target = array_key_exists('target', $_REQUEST) ? trim($_REQUEST['target']):'';

$imgManager = new ImageLibraryBrowser();
$imgManager->setSearchTerm($taxon);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['IMG_LIBRARY']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="../js/symb/imagelib.search.js?ver=201902" type="text/javascript"></script>
	<style>
		.sciname-search {
			float: left;
			margin: 10px 0px 10px 30px;
		}
		.sciname-search-container {
			float: right;
			width: 30rem;
			margin-bottom: 1rem;
		}
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_indexMenu)?$imagelib_indexMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<b><?php echo $LANG['IMG_LIBRARY']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $LANG['TAXA_W_IMGS']; ?></h1>
		<div style="margin:0px 0px 5px 20px;"><?php echo $LANG['TAXA_IMG_EXPLAIN']; ?>
		</div>
		<div class="sciname-search">
			<div>
				<a href='index.php?target=family'><?php echo htmlspecialchars($LANG['BROWSE_FAMILY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
			</div>
			<div style='margin-top:10px;'>
				<a href='index.php?target=genus'><?php echo htmlspecialchars($LANG['BROWSE_GENUS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
			</div>
			<div style='margin-top:10px;'>
				<?php echo $LANG['BROWSE_SPECIES']; ?>
			</div>
			<div style='margin:2px 0px 0px 10px;'>
				<div><a href='index.php?taxon=A'>A</a>|<a href='index.php?taxon=B'>B</a>|<a href='index.php?taxon=C'>C</a>|<a href='index.php?taxon=D'>D</a>|<a href='index.php?taxon=E'>E</a>|<a href='index.php?taxon=F'>F</a>|<a href='index.php?taxon=G'>G</a>|<a href='index.php?taxon=H'>H</a></div>
				<div><a href='index.php?taxon=I'>I</a>|<a href='index.php?taxon=J'>J</a>|<a href='index.php?taxon=K'>K</a>|<a href='index.php?taxon=L'>L</a>|<a href='index.php?taxon=M'>M</a>|<a href='index.php?taxon=N'>N</a>|<a href='index.php?taxon=O'>O</a>|<a href='index.php?taxon=P'>P</a>|<a href='index.php?taxon=Q'>Q</a></div>
				<div><a href='index.php?taxon=R'>R</a>|<a href='index.php?taxon=S'>S</a>|<a href='index.php?taxon=T'>T</a>|<a href='index.php?taxon=U'>U</a>|<a href='index.php?taxon=V'>V</a>|<a href='index.php?taxon=W'>W</a>|<a href='index.php?taxon=X'>X</a>|<a href='index.php?taxon=Y'>Y</a>|<a href='index.php?taxon=Z'>Z</a></div>
			</div>
		</div>
		<div class="sciname-search-container">
			<div style="margin:10px 0px 0px 0px;">
				<form name="searchform1" action="index.php" method="post">
					<fieldset style="background-color:#FFFFCC;padding:10px;">
						<legend style="font-weight:bold;"><?= $LANG['SCINAME_SEARCH'] ?></legend>
						<label for="taxon">Taxon: </label>
						<input type="text" name="taxon" value="<?= $imgManager->cleanOutStr($taxon) ?>" title="<?= $LANG['ENTER_TAXON_NAME'] ?>" placeholder="<?= $LANG['ENTER_TAXON_NAME'] ?>" >
						<button name="submit" value="Search" type="submit"><?= $LANG['SEARCH'] ?></button>
					</fieldset>
				</form>
			</div>
			<div style="font-weight:bold;margin:15px 10px 0px 20px;">
				<div>
					<a href="../includes/usagepolicy.php#images"><?php echo htmlspecialchars($LANG['IMG_CP_POLICY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
				</div>
				<div>
					<a href="contributors.php"><?php echo htmlspecialchars($LANG['IMG_CONTRIBUTORS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
				</div>
				<div>
					<a href="search.php"><?php echo htmlspecialchars($LANG['IMG_SEARCH'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
				</div>
			</div>
		</div>
		<div style='clear:both;'><hr/></div>
		<?php
			$taxaList = Array();
			if($target == 'genus'){
				$taxaList = $imgManager->getGenusList();
				if($taxaList){
					echo '<h2>'.$LANG['SELECT_GENUS'].'</h2>';
					foreach($taxaList as $value){
						echo "<div style='margin-left:30px;'><a href='index.php?taxon=" . htmlspecialchars($value, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>" . htmlspecialchars($value, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></div>";
					}
				}
				else{
					echo '<h2>'.$LANG['NO_TAXA_RETURNED'].'</h2>';
				}
			}
			elseif($target == 'species' || $taxon){
				$taxaList = $imgManager->getSpeciesList();
				if($taxaList){
					echo '<h2>'.$LANG['SELECT_SPECIES'].'</h2>';
					foreach($taxaList as $key => $value){
						echo '<div style="margin-left:30px;font-style:italic;">';
						echo '<a href="#" onclick="openTaxonPopup(' . htmlspecialchars($key, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ');return false;">' . htmlspecialchars($value, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ';
						echo '<a href="search.php?taxa=' . htmlspecialchars($key, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&usethes=1&taxontype=2&submitaction=search" target="_blank"> <img src="../images/image.png" style="width:1.5em;" /></a> ';
						echo '</div>';
					}
				}
				else{
					echo '<h2>'.$LANG['NO_TAXA_RETURNED'].'</h2>';
				}
			}
			else{ //Family display
				$taxaList = $imgManager->getFamilyList();
				if($taxaList){
					echo '<h2>'.$LANG['SELECT_FAMILY'].'.</h2>';
					foreach($taxaList as $value){
						echo '<div style="margin-left:30px;"><a href="index.php?target=genus&taxon=' . htmlspecialchars($value, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars(strtoupper($value), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
					}
				}
				else{
					echo '<h2>'.$LANG['NO_TAXA_RETURNED'].'</h2>';
				}
			}
	?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>