<?php
include_once('../../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/assocsppaid.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/assocsppaid.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/assocsppaid.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['ASSOC_SPP_AID']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">

		$(document).ready(function() {
			$("#taxonname").autocomplete({
				source: "rpc/getspeciessuggest.php",
				minLength: 3,
				autoFocus: true,
				delay: 200
			});

			$("#taxonname").focus();
		});

		function addName(){
		    var nameElem = document.getElementById("taxonname");
		    if(nameElem.value){
		    	var asStr = opener.document.fullform.associatedtaxa.value;
		    	if(asStr) asStr = asStr + ", ";
		    	opener.document.fullform.associatedtaxa.value = asStr + nameElem.value;
		    	nameElem.value = "";
		    	nameElem.focus();
		    }
	    }

	</script>
</head>

<body style="background-color:white">
	<!-- This is inner text! -->
	<div role="main" id="innertext" style="background-color:white;">
		<h1 class="page-heading screen-reader-only"><?php echo $LANG['ASSOC_SPP_AID']; ?></h1>
		<fieldset style="width:450px;">
			<legend><b><?php echo $LANG['ASSOC_SPP_AID']; ?></b></legend>
			<div style="">
				<label for="taxonname"><?php echo $LANG['TAXON']; ?>:</label>
				<input id="taxonname" type="text" style="width:350px;" /><br/>
				<button id="transbutton" type="button" value="Add Name" onclick="addName();"><?php echo $LANG['ADD_NAME']; ?></button>
			</div>
		</fieldset>
	</div>
</body>
</html>

