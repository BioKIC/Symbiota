<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ReferenceManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../references/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$refId = array_key_exists('refid',$_REQUEST)?$_REQUEST['refid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$refManager = new ReferenceManager();
$refArr = '';
$refExist = false;

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

$statusStr = '';
if($formSubmit && $isEditor){
	if($formSubmit == 'Delete Reference'){
		$statusStr = $refManager->deleteReference($refId);
	}
	elseif($formSubmit == 'Search References'){
		$refArr = $refManager->getRefList($_POST['searchtitlekeyword'],$_POST['searchauthor']);
		foreach($refArr as $refName => $valueArr){
			if($valueArr["title"]){
				$refExist = true;
			}
		}
	}
}
if(!$formSubmit || $formSubmit != 'Search References'){
	$refArr = $refManager->getRefList('','');
	foreach($refArr as $refName => $valueArr){
		if($valueArr["title"]){
			$refExist = true;
		}
	}
}

?>
<!DOCTYPE HTML>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE; ?> Reference Management</title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/symb/references.index.js"></script>
	<script type="text/javascript">
		function verifyNewRefForm(f){
			if(document.getElementById("newreftitle").value == ""){
				alert("Please enter the title of the reference.");
				return false;
			}
			if(document.getElementById("newreftype").selectedIndex < 2){
				alert("Please select the type of reference.");
				return false;
			}
			return true;
		}
	</script>

</head>
<body>
	<?php
	$displayLeftMenu = (isset($reference_indexMenu)?$reference_indexMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt;
		<a href="index.php"> <b>Reference Management</b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Reference Management</h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:15px;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php
		}
		?>
		<div id="" style="float:right;width:240px;">
			<form name="filterrefform" action="index.php" method="post">
				<fieldset style="background-color:#f2f2f2;">
				    <legend><b>Filter List</b></legend>
			    	<div>
						<div>
							<b>Title Keyword:</b>
							<input type="text" autocomplete="off" name="searchtitlekeyword" id="searchtitlekeyword" size="25" value="<?php echo ($formSubmit == 'Search References'?$_POST['searchtitlekeyword']:''); ?>" />
						</div>
						<div>
							<b>Author's Last Name:</b>
							<input type="text" name="searchauthor" id="searchauthor" size="25" value="<?php echo ($formSubmit == 'Search References'?$_POST['searchauthor']:''); ?>" />
						</div>
						<div style="padding-top:8px;float:right;">
							<button name="formsubmit" type="submit" value="Search References">Filter List</button>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
		<div id="reflistdiv" style="min-height:200px;">
			<div style="float:right;margin:10px;">
				<a href="#" onclick="toggle('newreferencediv');">
					<img src="../images/add.png" style="width:1.3em" alt="Create New Reference" />
				</a>
			</div>
			<div id="newreferencediv" style="display:none;">
				<form name="newreferenceform" action="refdetails.php" method="post" onsubmit="return verifyNewRefForm(this.form);">
					<fieldset>
						<legend><b>Add New Reference</b></legend>
						<div style="clear:both;padding-top:4px;float:left;">
							<div style="">
								<b>Title: </b>
							</div>
							<div style="margin-left:35px;margin-top:-14px;">
								<textarea name="newreftitle" id="newreftitle" rows="10" style="width:380px;height:40px;resize:vertical;" ></textarea>
							</div>
						</div>
						<div style="clear:both;padding-top:6px;float:left;">
							<span>
								<b>Reference Type: </b><select name="newreftype" id="newreftype" style="width:400px;">
									<option value="">Select Reference Type</option>
									<option value="">------------------------------------------</option>
									<?php
									$typeArr = $refManager->getRefTypeArr();
									foreach($typeArr as $k => $v){
										echo '<option value="'.$k.'">'.$v.'</option>';
									}
									?>
								</select>
							</span>
						</div>
						<div style="clear:both;padding-top:8px;float:right;">
							<input name="ispublished" type="hidden" value="1" />
							<button name="formsubmit" type="submit" value="Create Reference">Create Reference</button>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
			if($refExist){
				echo '<div style="font-weight:bold;font-size:120%;">References</div>';
				echo '<div><ul>';
				foreach($refArr as $refId => $recArr){
					echo '<li>';
					echo '<a href="refdetails.php?refid=' . htmlspecialchars($refId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><b>' . htmlspecialchars($recArr["title"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</b></a>';
					if($recArr["ReferenceTypeId"] == 27){
						echo ' series.';
					}
					if($recArr["tertiarytitle"] != $recArr["title"]){
						echo ($recArr["tertiarytitle"]?', '.$recArr["tertiarytitle"]:'');
					}
					echo ($recArr["volume"]?' Vol. '.$recArr["volume"].'.':'');
					echo ($recArr["number"]?' No. '.$recArr["number"].'.':'');
					if(($recArr["tertiarytitle"] != $recArr["secondarytitle"]) && ($recArr["title"] != $recArr["secondarytitle"])){
						echo ($recArr["secondarytitle"]?', '.$recArr["secondarytitle"].'.':'.');
					}
					echo ($recArr["edition"]?' '.$recArr["edition"].' Ed.':'');
					echo ($recArr["pubdate"]?' '.$recArr["pubdate"].'.':'');
					echo ($recArr["authline"]?' '.$recArr["authline"]:'');
					echo '</li>';
				}
				echo '</ul></div>';
			}
			elseif(($formSubmit && $formSubmit == 'Search References') && !$refExist){
				echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There were no references matching your criteria.</div></div>';
			}
			else{
				echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are currently no references in the database.</div></div>';
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>