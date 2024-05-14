<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/content/lang/ident/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$proj = array_key_exists('proj',$_REQUEST)?$_REQUEST['proj']:'';
$pid = array_key_exists('pid',$_REQUEST)?$_REQUEST['pid']:'';
if(!$pid && is_numeric($proj)) $pid = $proj;

//Sanitation
if($pid && !is_numeric($pid)) $pid = '';

if($pid === '' && isset($DEFAULT_PROJ_ID)) $pid = $DEFAULT_PROJ_ID;

$clManager = new ChecklistManager();
$clManager->setPid($pid);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['IDKEY'];?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($ident_indexMenu)?$ident_indexMenu:'true');
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($ident_indexCrumbs)){
		echo "<div class='navpath'>";
		echo $ident_indexCrumbs;
		echo "<b>".$LANG['IDKEYLIST']."</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 classes="page-heading"><?php echo $LANG['IDKEYS']; ?></h1>
	    <div style='margin:20px;'>
	        <?php
	        $projArr = $clManager->getChecklists(true);
			foreach($projArr as $pidKey => $pArr){
				$clArr = $pArr['clid'];
				echo '<div style="margin:3px 0px 0px 15px;">';
				echo '<h3>'.$pArr['name'].' <a href="../checklists/clgmap.php?pid=' . htmlspecialchars($pidKey, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&target=keys"><img src="../images/world.png" style="width:10px;border:0" /></a></h3>';
				echo '<div><ul>';
				foreach($clArr as $clid => $clName){
					echo '<li><a href="key.php?clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pidKey, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxon=All+Species">' . htmlspecialchars($clName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
				}
				echo "</ul></div>";
				echo "</div>";
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>