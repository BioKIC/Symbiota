<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryBrowser.php');
header("Content-Type: text/html; charset=".$CHARSET);

$imgManager = new ImageLibraryBrowser();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Photographer List</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<meta name='keywords' content='Contributors, Specimens' />
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_photographersMenu)?$imagelib_photographersMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php"> <?php echo htmlspecialchars($LANG['NAV_HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?> </a> &gt;&gt;
		<a href="index.php"> <?php echo htmlspecialchars($LANG['NAV_IMG_LIB'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?> </a> &gt;&gt;
		<b> <?php echo htmlspecialchars($LANG['NAV_IMG_CONTR'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?> </b>
	</div>

	<!-- This is inner text! -->
	<div role="main" id="innertext" style="height:100%">
		<h1 class="page-heading">Photographer List</h1>
		<?php
		$pList = $imgManager->getPhotographerList();
		if($pList){
			echo '<div style="float:left; margin-right:40px;">';
			echo '<h2>' . htmlspecialchars($LANG['IMG_CONTR'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</h2>';
			echo '<div style="margin-left:15px">';
			foreach($pList as $uid => $pArr){
				echo '<div>';
				$phLink = 'search.php?imagetype=all&phuid='.$uid.'&submitaction=search';
				echo '<a href="' . htmlspecialchars($phLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($pArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> (' . htmlspecialchars($pArr['imgcnt'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ')</div>';
			}
			echo '</div>';
			echo '</div>';
		}
		?>

		<div style="float:left">
			<?php
			ob_flush();
			flush();
			$collList = $imgManager->getCollectionImageList();
			$specList = $collList['coll'];
			if($specList){
				echo '<h2>' . htmlspecialchars($LANG['SPECIMENS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</h2>';
				echo '<div style="margin-left:15px;margin-bottom:20px">';
				foreach($specList as $k => $cArr){
					echo '<div>';
					$phLink = 'search.php?taxontype=2&imagecount=all&imagetype=all&submitaction=search&db[]='.$k;
					echo '<a href="' . htmlspecialchars($phLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($cArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> (' . htmlspecialchars($cArr['imgcnt'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ')</div>';
				}
				echo '</div>';
			}

			$obsList = $collList['obs'];
			if($obsList){
				echo '<h2>' . htmlspecialchars($LANG['OBS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</h2>';
				echo '<div style="margin-left:15px">';
				foreach($obsList as $k => $cArr){
					echo '<div>';
					$phLink = 'search.php?taxontype=2&imagecount=all&imagetype=all&submitaction=search&db[]='.$k;
					echo '<a href="' . htmlspecialchars($phLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($cArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> (' . htmlspecialchars($cArr['imgcnt'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ')</div>';
				}
				echo '</div>';
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>