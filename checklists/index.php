<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/content/lang/checklists/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$pid = array_key_exists('pid',$_REQUEST)?$_REQUEST['pid']:0;

//Sanitation
$pid = htmlspecialchars($pid, HTML_SPECIAL_CHARS_FLAGS);
if(!is_numeric($pid)) $pid = 0;


$clManager = new ChecklistManager();
$clManager->setProj($pid);
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Species Lists</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($checklists_indexMenu)?$checklists_indexMenu:'true');
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php"><?php echo (isset($LANG['NAV_HOME'])?$LANG['NAV_HOME']:'Home'); ?></a> &gt;&gt;
		<b><?php echo (isset($LANG['SPECIES_INVENTORIES'])?$LANG['SPECIES_INVENTORIES']:'Species Inventories'); ?></b>
	</div>
	<div id="innertext">
		<h1><?php echo (isset($LANG['SPECIES_INVENTORIES'])?$LANG['SPECIES_INVENTORIES']:'Species Inventories'); ?></h1>
        <div style="margin:20px;">
			<?php
            $researchArr = $clManager->getChecklists();
			if($researchArr){
				foreach($researchArr as $pid => $projArr){
					?>
					<h3>
						<?php
						$projName = $projArr['name'];
						if($projName == 'Miscellaneous Inventories') $projName = (isset($LANG['MISC_INVENTORIES'])?$LANG['MISC_INVENTORIES']:'Miscellaneous Inventories');
						echo $projName;
						?>
						<a href="<?php echo "clgmap.php?pid=" . $pid; ?>" title='<?php echo (isset($LANG['SHOW_MAP'])?$LANG['SHOW_MAP']:'Show inventories on map'); ?>'>
							<img src='../images/world.png' style='width:10px;border:0' />
						</a>
					</h3>
					<ul>
						<?php
						foreach($projArr['clid'] as $clid => $clName){
							echo '<li><a href="checklist.php?clid='.$clid.'&pid=' . $pid.'">' . $clName.'</a></li>';
						}
						?>
					</ul>
					<?php
				}
			}
			else echo '<div><b>'.(isset($LANG['NO_INVENTORIES'])?$LANG['NO_INVENTORIES']:'No inventories returned').'</b></div>';
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>