<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=<?php echo $CHARSET; ?>");
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<?php
	$activateJQuery = true;
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<meta name='keywords' content='' />
	<link href="css/quicksearch.css" type="text/css" rel="Stylesheet" />
	<script src="js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	<script src="js/symb/api.taxonomy.taxasuggest.js" type="text/javascript"></script>
	<script src="js/jquery.slides.js"></script>
	<script type="text/javascript">
		<?php include_once('/includes/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
        <!-- This is inner text! -->
        <div  id="innertext">
		<div style="float:right;margin: 15px 20px;width:320px;">
			<div id="quicksearchdiv">
				<!-- -------------------------QUICK SEARCH SETTINGS--------------------------------------- -->
				<form name="quicksearch" id="quicksearch" action="<?php echo $CLIENT_ROOT; ?>/taxa/index.php" method="get" onsubmit="return verifyQuickSearch(this);">
					<div id="quicksearchtext" ><?php echo (isset($LANG['QSEARCH_SEARCH'])?$LANG['QSEARCH_SEARCH']:'Search Taxon'); ?></div>
					<input id="taxa" type="text" name="taxon" />
					<button name="formsubmit"  id="quicksearchbutton" type="submit" value="Search Terms"><?php echo (isset($LANG['QSEARCH_SEARCH_BUTTON'])?$LANG['QSEARCH_SEARCH_BUTTON']:'Search'); ?></button>
				</form>
			</div>
			<div style="">
				<?php
				$ssId = 1;
				$numSlides = 10;
				$width = 300;
				$dayInterval = 7;
				$clId = 40;
				$imageType = "field";
				$numDays = 240;
				ini_set('max_execution_time', 120); //300 seconds = 5 minutes
				include_once($SERVER_ROOT.'/classes/PluginsManager.php');
				$pluginManager = new PluginsManager();
				echo $pluginManager->createSlideShow($ssId,$numSlides,$width,$numDays,$imageType,$clId,$dayInterval);
				?>
			</div>
		</div>
		<h1>Consortium of North American Bryophyte Herbaria</h1>
		<div style="padding: 0px 10px;font-size:120%;">
            		The Consortium of North American Bryophyte Herbaria (CNABH) was created to
			serve as a gateway to distributed data resources
			of interest to the taxonomic and environmental research
			community in North America. Through a common web interface, we offer tools
			to locate, access and work with a variety of data, starting
			with searching databased herbarium records.
		</div>
		<div style="background-color:#ffffff;float:left;margin:20px;width:200px;padding:15px;-moz-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;border:#990000 solid 1px;">
			<div style="font-weight:bold;font-size:130%;margin-left:20px;">News and Events</div>
			<ul>
				<li>
					<b>January 2021</b> - Welcome our new portal manager Katie Pearson (ASU), who will continue to manage CNABH under the direction of Blanka Aguero (Duke University) & Matt von Konrat (The Field Museum).
				</li>
				<li>
					<b>September 2020</b> - ASU and collaborators were recently awarded a new <a href="https://www.nsf.gov/awardsearch/showAward?AWD_ID=2001394" target="_blank">NSF Grant</a> to Build a <a href="https://www.idigbio.org/wiki/index.php/Building_a_global_consortium_of_bryophytes_and_lichens:_keystones_of_cryptobiotic_communities" target="_blank"><b>Global Consortium of Bryophytes and Lichens</b></a>.
				</li>
                <li>
                    <b>August 2018</b> - ASU receives a multi-million dollar grant from NSF to create the new <b><a href="https://biorepo.neonscience.org/">NEON Biorepository</a></b>; these funds will substantially strengthen
                                        the Symbiota Software platform and add new functionality from which CNABH will benefit as well.
			</ul>
		</div>
		<div style="margin-top:10px;padding: 0px 10px;font-size:120%">
			The CNABH data portal is more than just a web site - it is a suite of data
			access technologies and a distributed network of universities,
			museums and agencies that provide taxonomic and environmental information.
			Initially created with financial assistance from the American
			Bryological and Lichenological Society, the consortium is growing to extend
			its network to other partners within North America.
		</div>
		<div style="margin-top:10px;padding: 0px 10px; font-size:120%">
            		Join the Consortium of North American Bryophyte Herbaria as a regular visitor and please send your feedback to
			<a class="bodylink" href="mailto:CNABH.help@gmail.com">CNABH.help@gmail.com</a>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
