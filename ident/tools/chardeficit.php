<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyCharDeficitManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"";
$cfValue = array_key_exists("cf",$_REQUEST)?$_REQUEST["cf"]:"";
$cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:"";

$cdManager = new KeyCharDeficitManager();
if($langValue) $cdManager->setLanguage($langValue);
if($projValue) $cdManager->setProject($projValue);
$editable = false;
if($IS_ADMIN || array_key_exists("KeyEditor",$USER_RIGHTS) || array_key_exists("KeyAdmin",$USER_RIGHTS)){
	$editable = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Character Deficit Finder</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function openPopup(urlStr,windowName){
			var wWidth = 900;
			try{
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
			}
			catch(e){
			}
			newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($ident_tools_chardeficitMenu)?$ident_tools_chardeficitMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($ident_tools_chardeficitCrumbs)){
		echo "<div class='navpath'>";
		echo $ident_tools_chardeficitCrumbs;
		echo "<b>Character Deficit Editor</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Character Deficit Finder</h1>
  		<form action="chardeficit.php" method="get">
			<?php
		 	if($editable){
				?>
		<table width="700" border="0">
			<tr>
				<td width="200" valign="top">
					<div style='margin-top:1em;font-weight:bold;'>Checklist:</div>
				  	<select name="clid">
				  		<?php
				  			$selectList = Array();
				  			$selectList = $cdManager->getClQueryList();
				  			echo "<option>--Select a Checklist--</option>";
				  			foreach($selectList as $key => $value){
				  				$selectStr = $key==$clid?"SELECTED":"";
				  				echo "<option value='".$key."' $selectStr>$value</option>";
				  			}
				  		?>
				  	</select>
				  	<br/>
					<div style='margin-top:1em;font-weight:bold;'>Filter Character List:</div>
						<select name="cf">
				  		<?php
				  			$selectList = Array();
				  			$selectList = $cdManager->getTaxaQueryList();
				  			echo "<option>--Select a Taxon--</option>";
				  			foreach($selectList as $key => $value){
				  				$selectStr = $key==$cfValue?"SELECTED":"";
				  				echo "<option value='".$key."' $selectStr>$value</option>\n";
				  			}
				  		?>
				  	</select><br/>
						<div style='margin-top:1em;'><input type='submit' name='action' id='submit' value='Get Characters' /></div>

			  		<hr size="2"/>
					<input type='submit' name='action' id='submit' value='Get Species List'/>
					<div style="margin:10px 0px 10px 0px;height:250px; width:190; overflow : auto;border:black solid 1px;">
			  		<?php
						if($cfValue != "--Select a Taxon--"){
			  				if($action=="Get Characters" || $action=="Get Species List"){
					  			$cList = $cdManager->getCharList($cfValue, $cidValue);
					  			foreach($cList as $value){
					  				echo $value."\n";
					  			}
				  			}
				  			else{
				  				echo "<h2>Character List Empty</h2>";
				  			}
						}
						else{
			  				echo "<h2>Select as Taxon</h2>";
						}
			  		?>
					</div>
					<input type='submit' name='action' id='submit' value='Get Species List' />
				</td>
				<td style="border-left: 1px solid black">&nbsp;</td>
				<td valign="top">
			  		<?php
				  	if($action=="Get Species List" && $cfValue != "--Select a Taxon--"){
				  		$cdManager->setClid($clid);
				  		$tList = $cdManager->getTaxaList($cidValue, $cfValue);								//family => Array(tid => sciname)
				  		if($tList){
							echo "<h3>Species Count: ".$cdManager->getTaxaCount()."</h3>\n";
				  			foreach($tList as $f=>$sArr){
				  				echo "<div style='margin-top:1em;font-size:125%;'>$f</div>\n";
				  				foreach($sArr as $idValue => $spValue){
				  					echo "<div style=''>&nbsp;&nbsp;<a href='editor.php?tid=" . htmlspecialchars($idValue, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&lang=English&lang=English' target='_blank'>$spValue</a> ";
				  					echo "(<a href=\"#\" onclick=\"openPopup('editor.php?tid=" . htmlspecialchars($idValue, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&char=" . htmlspecialchars($cidValue, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "','technical');\">@</a>)</div>\n";
				  				}
				  			}
				  		}
				  		else{
				  			echo "<h2>No taxa were returned.</h2>";
				  		}
				  	}
			  		else{
			  			echo "<h2>List Empty.</h2>";
			  		}
					?>
				</td>
			</tr>
		</table>
	</form>
<?php
 }
 else{  //Not editable
	echo "<h1>You do not have authority to edit character data or there is a problem with the connection.</h1> <h3>You must first login to the system.</h3>";
 }
 ?>
</div>
<?php include($SERVER_ROOT.'/includes/footer.php'); ?>
</body>
</html>