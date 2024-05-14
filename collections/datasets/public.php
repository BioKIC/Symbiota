<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/datasets/public.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/datasets/public.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/datasets/public.en.php');

header("Content-Type: text/html; charset=".$CHARSET);

// Datasets
$datasetid = array_key_exists('datasetid',$_REQUEST)?$_REQUEST['datasetid']:0;

if(!is_numeric($datasetid)) $datasetid = 0;

$datasetManager = new OccurrenceDataset();
$dArr = $datasetManager->getPublicDatasetMetadata($datasetid);
$searchUrl = '../../collections/list.php?datasetid='.$datasetid;
$tableUrl = '../../collections/listtabledisplay.php?datasetid='.$datasetid;
$taxaUrl = '../../collections/list.php?datasetid='.$datasetid.'&tabindex=0';
// $downloadUrl = '../../collections/download/index.php?datasetid='.$datasetid;
$ocArr = $datasetManager->getOccurrences($datasetid);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $LANG['DATASET']; ?>: <?php echo $dArr['name'] ;?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
			<b><?php echo $LANG['DATASET']; ?>: <?php echo $dArr['name'] ;?></b>
		</div>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
    	<h1 class="page-heading"><?php echo $LANG['DATASET']; ?>: <?php echo $dArr['name'] ;?></h1>
    <ul>
      <!-- Metadata -->
      <div><?php echo $dArr['description'] ;?></div>
      <!-- Occurrences Summary -->
      <p><?php echo $LANG['INCLUDES']; ?> <?php echo count($ocArr); ?> <?php echo $LANG['RECORDS']; ?></p>

      <p><a class="btn" href="<?php echo htmlspecialchars($searchUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ;?>"><?php echo $LANG['VIEW_AND_DOWNLOAD']; ?></a></p>
      <p><a class="btn" href="<?php echo htmlspecialchars($tableUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ;?>"><?php echo $LANG['VIEW_SAMPLE']; ?></a></p>
      <p><a class="btn" href="<?php echo htmlspecialchars($taxaUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ;?>"><?php echo $LANG['VIEW_LIST']; ?></a></p>
      <!-- <p><a href="#">Download this Dataset</a></p> -->
    </ul>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
