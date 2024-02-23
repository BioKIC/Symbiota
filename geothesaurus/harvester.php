<?php
include_once ('../config/symbini.php');
include_once ($SERVER_ROOT . '/classes/GeographicThesaurus.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$geoThesID = array_key_exists('geoThesID', $_REQUEST) ? filter_var($_REQUEST['geoThesID'], FILTER_SANITIZE_NUMBER_INT) : '';
$gbAction = array_key_exists('gbAction', $_REQUEST) ? htmlspecialchars($_REQUEST['gbAction'], HTML_SPECIAL_CHARS_FLAGS) : '';
$submitAction = array_key_exists('submitaction', $_POST) ? htmlspecialchars($_POST['submitaction'], HTML_SPECIAL_CHARS_FLAGS) : '';
$addIfMissing = array_key_exists('addgeounit', $_POST)? filter_var($_POST['addgeounit'], FILTER_VALIDATE_BOOLEAN) : false;
$baseParent = array_key_exists('baseParent', $_POST) && !empty($_POST['baseParent']) ? filter_var($_POST['baseParent'], FILTER_SANITIZE_NUMBER_INT) : null;

$geoManager = new GeographicThesaurus();

$isEditor = false;

if(!isset($IS_ADMIN) || (!$IS_ADMIN && !array_key_exists('CollAdmin',$USER_RIGHTS))) {
   header("Location: ". $CLIENT_ROOT . '/index.php');
} else {
   $isEditor = true;
}

$statusStr = '';

if($isEditor && $submitAction) {
	if($submitAction == 'transferDataFromLkupTables'){
		if($geoManager->transferDeprecatedThesaurus()) $statusStr = '<span style="color:green;">Geographic Lookup tables transferred into new Geographic Thesaurus</span>';
		else $statusStr = '<span style="color:green;">'.implode('<br/>',$geoManager->getWarningArr()).'<span style="color:green;">';
	}
	elseif($submitAction == 'submitCountryForm'){
      //This Call can Take a very long time depending on the size of the
      //geoJson and how many children are within the feature collection past
      set_time_limit(600);
      $geoJsonLinks = array_key_exists('geoJson', $_POST) && is_array($_POST['geoJson'])? $_POST['geoJson'] : [];

      foreach($geoJsonLinks as $geojson) {
         try {
            $results = $geoManager->addGeoBoundary($geojson, $addIfMissing, $baseParent);
            if(is_array($results) && count($results) === 1) {
               $baseParent = $results[0];
            }
         } catch(Execption $e) {
            $statusStr = 'The harvester encountered an issue';
            break;
         }
      }
	}
}

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Geographic Thesaurus Havester</title>
	<?php
	include_once ($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
	<style type="text/css">
		fieldset{ margin: 10px; padding: 15px; }
		legend{ font-weight: bold; }
		label{ text-decoration: underline; }
		#edit-legend{ display: none }
		.field-div{ margin: 3px 0px }
		.editIcon{  }
		.editTerm{ }
		.editFormElem{ display: none }
		#editButton-div{ display: none }
		#unitDel-div{ display: none }
		.button-div{ margin: 15px }
		.link-div{ margin:0px 30px }
		#status-div{ margin:15px; padding: 15px; }
	</style>
   <script type="text/javascript">
      function submit_loading() {
         let spinner = document.getElementById("submit-loading")
         let helpText = document.getElementById("submit-loading-text")

         if(spinner) spinner.style.display = "block";
         if(helpText) helpText.style.display = "block";
      }
   </script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt;
		<a href="index.php">Geographic Thesaurus Listing</a> &gt;&gt;
		<b>Geographic Harvester</b>
	</div>
	<div id='innertext'>
		<?php
		if($statusStr){
			echo '<div id="status-div">'.$statusStr.'</div>';
		}

		if($statusReport = $geoManager->getThesaurusStatus()){
			$geoRankArr = $geoManager->getGeoRankArr();
			echo '<fieldset style="width: 800px">';
			echo '<legend>Active Geographic Thesaurus</legend>';
			if(isset($statusReport['active'])){
				foreach($statusReport['active'] as $geoRank => $cnt){
					echo '<div><b>'.$geoRankArr[$geoRank].':</b> '.$cnt.'</div>';
				}
				echo '<div style="margin-top:20px"><a href="index.php">Go to Geographic Thesaurus</a></div>';
			}
			else echo '<div>Active thesaurus is empty</div>';
			echo '</fieldset>';
			if(isset($statusReport['lkup'])){
				?>
				<fieldset>
					<legend>Geopraphic Lookup Tables - deprecated</legend>
					<p>There appears to be records within the deprecated Geographic lookup tables that are no longer used.<br/>Do you want to transfer this data into the new geographic thesaurus?</p>
					<?php
					foreach($statusReport['lkup'] as $k => $v){
						echo '<div><b>'.$k.':</b> '.$v.'</div>';
					}
					?>
					<hr/>
					<form name="transThesForm" action="harvester.php" method="post" style="margin-top:15px">
						<button name="submitaction" type="submit" value="transferDataFromLkupTables">Transfer Lookup Tables</button>
					</form>
				</fieldset>
				<?php
			}
			?>
			<fieldset>
				<legend>geoBoundaries Harvesting Tools</legend>
				<?php
				if(!$gbAction){
					?>
					<div>
						<div style="float:right;margin-left:15px"><input name="displayRadio" type="radio" onclick="$('.nopoly').hide();" /> Show no polygon only</div>
						<div style="float:right;margin-left:15px"><input name="displayRadio" type="radio" onclick="$('.nodb').hide();" /> Show not in database only</div>
						<div style="float:right;margin-left:15px"><input name="displayRadio" type="radio" onclick="$('.nopoly').show();$('.nodb').show();" /> Show all</div>
					</div>
					<table class="styledtable">
						<tr>
							<th>Name</th><th>ISO</th><th>In Database</th><th>Has Polygon</th><th>ID</th><th>Canonical</th><th>License</th><th>Region</th><th>Preview Image</th>
						</tr>
						<?php
						$countryList = $geoManager->getGBCountryList();
						foreach($countryList as $cArr){
							echo '<tr class="'.(isset($cArr['geoThesID'])?'nodb':'').(isset($cArr['polygon'])?' nopoly':'').'">';
							echo '<td><a href="harvester.php?gbAction=' . $cArr['iso'] . '">' . htmlspecialchars($cArr['name'], HTML_SPECIAL_CHARS_FLAGS) . '</a></td>';
							echo '<td>'.$cArr['iso'].'</td>';
							echo '<td>'.(isset($cArr['geoThesID'])?'Yes':'No').'</td>';
							echo '<td>'.(isset($cArr['polygon'])?'Yes':'No').'</td>';
							echo '<td>'.$cArr['id'].'</td>';
							echo '<td>'.$cArr['canonical'].'</td>';
							echo '<td>'.$cArr['license'].'</td>';
							echo '<td>'.$cArr['region'].'</td>';
							//echo '<td><a href="' . $cArr['link'] . '" target="_blank">link</a></td>';
							echo '<td><a href="' . $cArr['img'] . '" target="_blank">IMG</a></td>';
							echo '</tr>';
						}
						?>
					</table>
					<?php
				}
				else{
               $geoList = $geoManager->getGBGeoList($gbAction);
					?>
					<ul>
						<li><a href="harvester.php">Return to Country List</a></li>
					</ul>
            <script type="text/javascript">
               function checkHierarchy(e) {
                  let ranked_inputs = document.getElementsByName("geoJson[]")
                  let shouldCheck = true;

                  for(let i = 0; i < ranked_inputs.length; i++) {
                     if(e.value === ranked_inputs[i].value) {
                        shouldCheck = false;
                     } else {
                        ranked_inputs[i].checked = shouldCheck;
                     }
                  }
               }
            </script>
					<form name="" method="post" action="harvester.php">
                  <input style="display:none" name="baseParent" value="<?= isset($geoList['ADM0']['geoThesID'])? $geoList['ADM0']['geoThesID'] : null?>">
						<table class="styledtable">
							<tr>
								<th></th><th>Type</th><th>ID</th><th>Database Count</th><th>Geoboundaries Count</th><th>Has Polygon</th><th>Canonical</th><th>Region</th><th>License</th><th>Full Link</th><th>Preview Image</th>
							</tr>
							<?php
							$prevGeoThesID = 0;
							foreach($geoList as $type => $gArr){
								echo '<tr class="'.(isset($gArr['geoThesID'])?'nodb':'').(isset($gArr['polygon'])?' nopoly':'').'">';
								echo '<td><input name="geoJson[]" onchange="checkHierarchy(this)" type="checkbox" value="'.$gArr['geoJson'].'" '.(isset($gArr['polygon'])?'DISABLED':'').' /></td>';
								echo '<td>'.$type.'</td>';
								echo '<td>'.$gArr['id'].'</td>';
								$isInDbStr = 'No';
								if(isset($gArr['geoThesID'])){
									$isInDbStr = 1;
									if(is_numeric($gArr['geoThesID'])){
							         $isInDbStr = '<a href="index.php?geoThesID='.$gArr['geoThesID'].'" target="_blank">1</a>';
										$prevGeoThesID = $gArr['geoThesID'];
									}
									else{
										$isInDbStr = substr($gArr['geoThesID'], 4);
										if($prevGeoThesID) $isInDbStr = '<a href="index.php?parentID='.$prevGeoThesID.'" target="_blank">'.$isInDbStr.'</a>';
									}

                        //echo '<input name="parentID" style="display:none" value="'. $gArr['geoThesID'] .'"/>';
								}
								echo '<td>'.$gArr['gbCount'].'</td>';
								echo '<td>'.$isInDbStr.'</td>';
								echo '<td>'.(isset($gArr['polygon'])?'Yes':'No').'</td>';
								echo '<td>'.$gArr['canonical'].'</td>';
								echo '<td>'.$gArr['region'].'</td>';
								echo '<td>'.$gArr['license'].'</td>';
								echo '<td><a href="' . $gArr['link'] . '" target="_blank">link</a></td>';
								echo '<td><a href="' . $gArr['img'] . '" target="_blank">IMG</a></td>';
								echo '</tr>';
							}
							?>
						</table>
                  <div style="margin-top:1rem">
                     <input type="checkbox" id="addgeounit" name="addgeounit" value="true" checked >
                     <label style="text-decoration: none" for="addgeounit">Add geographical units if missing</label>
                  </div>
						<input name="gbAction" type="hidden" value="<?php echo $gbAction; ?>" />
                  <span style="display:inline-flex;vertical-align:middle;margin-top:1rem">
                     <button name="submitaction" onclick="submit_loading()" type="submit" value="submitCountryForm">
                        Add Boundaries 
                     </button>
                     <img id="submit-loading"style="border:0px;width:2rem;height:2rem;display:none" src="../images/ajax-loader.gif" />
                  </span>
                  <div id="submit-loading-text" style="display:none">
                     This process may take a while to process the geographical data
                  </div> 
					</form>
					<?php
				}
			?>
			</fieldset>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
