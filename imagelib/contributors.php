<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryBrowser.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/imagelib/contributors.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/imagelib/contributors.en.php');
else include_once($SERVER_ROOT.'/content/lang/imagelib/contributors.' . $LANG_TAG . '.php');

$imgManager = new ImageLibraryBrowser();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> <?=$LANG['CREATOR_LIST']?></title>
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
		<a href="../index.php"> <?= $LANG['NAV_HOME'] ?> </a> &gt;&gt;
		<a href="index.php"> <?= $LANG['NAV_IMG_LIB'] ?> </a> &gt;&gt;
		<b> <?= $LANG['NAV_IMG_CONTR'] ?> </b>
	</div>

	<!-- This is inner text! -->
	<div role="main" id="innertext" style="height:100%">
			<h1 class="page-heading"><?=$LANG['CREATOR_LIST']?></h1>
		<?php
		$pList = $imgManager->getPhotographerList();
		if($pList){
			echo '<div style="float:left; margin-right:40px;">';
			echo '<h2>' . $LANG['IMG_CONTR'] . '</h2>';
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
				echo '<h2>' . $LANG['SPECIMENS'] . '</h2>';
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
				echo '<h2>' . $LANG['OBS'] . '</h2>';
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
