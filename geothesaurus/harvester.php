<?php
include_once ('../config/symbini.php');
include_once ($SERVER_ROOT . '/classes/GeographicThesaurus.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/geothesaurus/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/geothesaurus/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/geothesaurus/index.en.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/geothesaurus/harvester.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/geothesaurus/harvester.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/geothesaurus/harvester.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$geoThesID = array_key_exists('geoThesID', $_REQUEST) ? filter_var($_REQUEST['geoThesID'], FILTER_SANITIZE_NUMBER_INT) : '';
$gbAction = array_key_exists('gbAction', $_REQUEST) ? htmlspecialchars($_REQUEST['gbAction'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$submitAction = array_key_exists('submitaction', $_POST) ? htmlspecialchars($_POST['submitaction'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
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
      if($geoManager->transferDeprecatedThesaurus()) $statusStr = '<span style="color:green;">' . $LANG['TRANSFERRED_TO_GEOTHESAURUS'] . '</span>';
      else $statusStr = '<span style="color:green;">'.implode('<br/>',$geoManager->getWarningArr()).'<span style="color:green;">';
   }
   elseif($submitAction == 'submitCountryForm') {
      //This Call can Take a very long time depending on the size of the
      //geoJson and how many children are within the feature collection past
      set_time_limit(1200);
      //This script can consume a lot of memory loading the geoJson so this a
      //large buffer to prevent those issues from occuring
      ini_set("memory_limit", "512M");
      $geoJsonLinks = array_key_exists('geoJson', $_POST) && is_array($_POST['geoJson'])? $_POST['geoJson'] : [];
      $types = array_key_exists('type', $_POST) && is_array($_POST['type'])? $_POST['type'] : [];
      $potentialParents = [];

      if ($baseParent !== null && count($types) > 0) {
         $potentialParents = array_map(
            fn($val) => $val['geoThesID'],
            array_filter(
               $geoManager->getChildren([$baseParent]), 
               fn($val) => $val['geoLevel'] === ($geoManager->getGeoLevel($types[0]) - 10)
            )
         );
      }

      foreach($geoJsonLinks as $geojson) {
         try {
            $results = $geoManager->addGeoBoundary($geojson, $addIfMissing, $baseParent, $potentialParents);
            if(is_array($results)) {
               if(count($results) === 1) {
                  $baseParent = $results[0];
               }
               $potentialParents = $results;
            }

         } catch(Execption $e) {
            $statusStr = $LANG['HARVESTER_ISSUE'];
            break;
         }
      }
   } elseif($submitAction == 'harvestCountries') {
      //This Call can Take a very long time depending on the size of the
      //geoJson and how many children are within the feature collection past
      set_time_limit(1200);

      //This script can consume a lot of memory loading the geoJson so this a
      //large buffer to prevent those issues from occuring
      ini_set("memory_limit", "512M");

      $geoJsonLinks = array_key_exists('geoJson', $_POST) && is_array($_POST['geoJson'])? $_POST['geoJson'] : [];
      foreach($geoJsonLinks as $geojson) {
         try {
            $geoManager->addGeoBoundary($geojson, true);
         } catch(Execption $e) {
            $statusStr = $LANG['BATCH_HARVESTER_ISSUE'];
            break;
         }
      }
   }
}
?>

<!DOCTYPE html>
<html lang="<?=$LANG_TAG?>">
   <head>
      <title><?php echo $DEFAULT_TITLE . ' - ' . $LANG['GEOTHESAURUS_HARVESTER']; ?></title>
      <?php
      include_once ($SERVER_ROOT.'/includes/head.php');
      ?>
      <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
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
         const spinner = document.getElementById("submit-loading")
         const helpText = document.getElementById("submit-loading-text")

         if(spinner) spinner.style.display = "block";
         if(helpText) helpText.style.display = "block";
      }

      function checkHierarchy(e) {
         let ranked_inputs = document.getElementsByName("geoJson[]")
         let types = document.getElementsByName("type[]")
         let shouldCheck = true;

         for(let i = 0; i < ranked_inputs.length; i++) {
            if(e.value === ranked_inputs[i].value) {
               shouldCheck = false;
               if(!ranked_inputs[i].checked) types[i].disabled = true;
            } else {
               ranked_inputs[i].checked = shouldCheck;
               types[i].disabled = ranked_inputs[i].checked? ranked_inputs[i].disabled: true;
            }
         }
      }
      </script>
   </head>
   <body>
      <?php
      $displayLeftMenu = false;
      include($SERVER_ROOT.'/includes/header.php');
      ?>
      <div class="navpath">
         <a href="../index.php"><?= $LANG['NAV_HOME'] ?> </a> &gt;&gt;
         <a href="index.php"><b> <?= $LANG['NAV_GEOTHES'] ?> </b></a> &gt;&gt;
         <b><?= $LANG['GEOGRAPHIC_HARVESTER']?></b>
      </div>
      <div id='innertext'>
         <h1 class="page-heading"><?= $LANG['GEOTHESAURUS_HARVESTER']; ?></h1>
         <?php
         if($statusStr){
         echo '<div id="status-div">'.$statusStr.'</div>';
         }

         if($statusReport = $geoManager->getThesaurusStatus()){
         $geoRankArr = $geoManager->getGeoRankArr();
         echo '<fieldset style="width: 800px">';
         echo '<legend>' . $LANG['ACTIVE_GEOGRAPHIC_THESAURUS'] . '</legend>';
         if(isset($statusReport['active'])){
         foreach($statusReport['active'] as $geoRank => $cnt){
         echo '<div><b>'.$geoRankArr[$geoRank].':</b> '.$cnt.'</div>';
         }
         echo '<div style="margin-top:20px"><a href="index.php">' . $LANG['GO_TO_GEOGRAPHIC_THESAURUS'] . '</a></div>';
         }
         else echo '<div>' . $LANG['ACTIVE_THES_EMPTY'] . '</div>';
         echo '</fieldset>';
         if(isset($statusReport['lkup'])){
         ?>
         <fieldset>
            <legend><?=$LANG['LOOKUP_TABLES_TITLE']?></legend>
            <p><?=$LANG['LOOKUP_TABLES_DESC']?></p>
            <?php
            foreach($statusReport['lkup'] as $k => $v){
            echo '<div><b>' . $k . ':</b> ' . $v . '</div>';
            }
            ?>
            <hr/>
            <form name="transThesForm" action="harvester.php" method="post" style="margin-top:15px">
               <button name="submitaction" type="submit" value="transferDataFromLkupTables"><?= $LANG['TRANSFER_LOOKUP_TABLES']?></button>
            </form>
         </fieldset>
         <?php
         }
         ?>
         <fieldset>
            <legend><?= $LANG['ACTIVE_GEOGRAPHIC_THESAURUS']?></legend>
            <?php
            if(!$gbAction){
            ?>
            <form style="position:relative" name="" method="post" action="harvester.php">
               <span style="position:absoulte;top:0px;display:inline-flex;vertical-align:middle; margin-bottom: 1rem">
                  <button name="submitaction" onclick="submit_loading()" type="submit" value="harvestCountries"><?= $LANG['ADD_ALL_BOUNDARIES']?></button>
                  <img id="submit-loading"style="border:0px;width:2rem;height:2rem;display:none" src="../images/ajax-loader.gif" />
               </span>
               <span style="float:right;">
                  <input name="displayRadio" style="margin-left:1rem" type="radio" id="show-no-polygon" onclick="$('.nodb').show();$('.nopoly').hide();" value="no-polygon">
                  <label for="show-no-polygon"><?= $LANG['SHOW_NO_POLYGON']?></label>
                  <input name="displayRadio" style="margin-left:1rem" type="radio" id="show-no-database" onclick="$('.nopoly').show();$('.nodb').hide();" value="no-database">
                  <label for="show-no-database"><?= $LANG['SHOW_NO_DATABASE']?></label>
                  <input name="displayRadio" style="margin-left:1rem" type="radio" id="show-all" onclick="$('.nopoly').show();$('.nodb').show();" value="all">
                  <label for="show-all"><?= $LANG['SHOW_ALL']?></label>
               </span>
               <div id="submit-loading-text" style="display:none; margin-bottom:1.5rem">
                  <?=$LANG['LOADING_GEO_DATA_TEXT']?>
               </div> 
               <table class="styledtable">
                  <tr>
                     <th><?=$LANG['TABLE_NAME']?></th>
                     <th><?=$LANG['TABLE_ISO3']?></th>
                     <th><?=$LANG['TABLE_DATABASE']?></th>
                     <th><?=$LANG['TABLE_POLYGON']?></th>
                     <th><?=$LANG['TABLE_BOUNDARY_ID']?></th>
                     <th><?=$LANG['TABLE_CANONICAL_NAME']?></th>
                     <th><?=$LANG['TABLE_LICENSE']?></th>
                     <th><?=$LANG['TABLE_REGION']?></th>
                     <th><?=$LANG['TABLE_IMAGE_PREVIEW']?></th>
                  </tr>
                  <?php
                  $countryList = $geoManager->getGBCountryList();
                  foreach($countryList as $cArr){
                  echo '<tr class="' . (isset($cArr['geoThesID'])?'nodb':'') . (isset($cArr['polygon'])?' nopoly':'') . '">';
                  echo '<td><a href="harvester.php?gbAction=' . $cArr['iso'] . '">' . htmlspecialchars($cArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></td>';
                  echo '<input type="hidden" name="geoJson[]" value="' . $cArr['geoJson'] .'"' . (isset($cArr['polygon'])?'disabled':'') . '>';
                  echo '<td>'.$cArr['iso'] . '</td>';
                  echo '<td>'.(isset($cArr['geoThesID']) ? $LANG['YES'] : $LANG['NO']) . '</td>';
                  echo '<td>'.(isset($cArr['polygon']) ? $LANG['YES'] : $LANG['NO']) . '</td>';
                  echo '<td>'.$cArr['id'] . '</td>';
                  echo '<td>'.$cArr['canonical'] . '</td>';
                  echo '<td>'.$cArr['license'] . '</td>';
                  echo '<td>'.$cArr['region'] . '</td>';
                  //echo '<td><a href="' . $cArr['link'] . '" target="_blank">link</a></td>';
                  echo '<td><a href="' . $cArr['img'] . '" target="_blank">' . $LANG['IMG'] . '</a></td>';
                  echo '</tr>';
                  }
                  ?>
               </table>
            </form>
            <?php
            }
            else{
            $geoList = $geoManager->getGBGeoList($gbAction);
            ?>
            <div style="margin-bottom:1rem">
               <a href="harvester.php"><?= $LANG['COUNTRY_LIST_NAV']?></a>
            </div>
            <form name="" method="post" action="harvester.php">
               <input style="display:none" name="baseParent" value="<?= isset($geoList['ADM0']['geoThesID'])? $geoList['ADM0']['geoThesID'] : null?>">
               <table class="styledtable">
                  <tr>
                     <th></th>
                     <th><?=$LANG['TABLE_TYPE']?></th>
                     <th><?=$LANG['TABLE_BOUNDARY_ID']?></th>
                     <th><?=$LANG['TABLE_INCOMING_COUNT']?></th>
                     <th><?=$LANG['TABLE_DATABASE_COUNT']?></th>
                     <th><?=$LANG['TABLE_POLYGON']?></th>
                     <th><?=$LANG['TABLE_CANONICAL_NAME']?></th>
                     <th><?=$LANG['TABLE_REGION']?></th>
                     <th><?=$LANG['TABLE_LICENSE']?></th>
                     <th><?=$LANG['TABLE_FULL_LINK']?></th>
                     <th><?=$LANG['TABLE_IMAGE_PREVIEW']?></th>
                  </tr>
                  <?php
                  $prevGeoThesID = 0;

                  foreach($geoList as $type => $gArr){
                  echo '<tr class="' . (isset($gArr['geoThesID'])?'nodb':'') . (isset($gArr['polygon']) ? ' nopoly' : '') . '">';
                  echo '<td><input name="geoJson[]" onchange="checkHierarchy(this)" type="checkbox" value="'.$gArr['geoJson'].'" '.(isset($gArr['polygon'])?'DISABLED':'').' /></td>';
                  echo '<input name="type[]" type="hidden" value="' . $type . '"/>';
                  echo '<td>' . $type . '</td>';
                  echo '<td>' . $gArr['id'] . '</td>';
                  $isInDbStr = 'No';
                  if(isset($gArr['geoThesID'])){
                  $isInDbStr = 1;
                  if(is_numeric($gArr['geoThesID'])){
                  $isInDbStr = '<a href="index.php?geoThesID=' . $gArr['geoThesID'] . '" target="_blank">1</a>';
                  $prevGeoThesID = $gArr['geoThesID'];
                  } elseif(is_array($gArr['geoThesID'])) {
                  $isInDbStr = count($gArr['geoThesID']);
                  if($prevGeoThesID) $isInDbStr = '<a href="index.php?parentID=' . $prevGeoThesID . '" target="_blank">' . $isInDbStr . '</a>';
                  } else{
                  $isInDbStr = substr($gArr['geoThesID'], 4);
                  if($prevGeoThesID) $isInDbStr = '<a href="index.php?parentID=' . $prevGeoThesID . '" target="_blank">' . $isInDbStr . '</a>';
                  }

                  //echo '<input name="parentID" style="display:none" value="'. $gArr['geoThesID'] .'"/>';
                  }
                  echo '<td>' . $gArr['gbCount'] . '</td>';
                  echo '<td>' . $isInDbStr . '</td>';
                  echo '<td>' . (isset($gArr['polygon']) ? $LANG['YES'] : $LANG['NO']) . '</td>';
                  echo '<td>' . $gArr['canonical'] . '</td>';
                  echo '<td>' . $gArr['region'] . '</td>';
                  echo '<td>' . $gArr['license'] . '</td>';
                  echo '<td><a href="' . $gArr['link'] . '" target="_blank">' . $LANG['LINK'] . '</a></td>';
                  echo '<td><a href="' . $gArr['img'] . '" target="_blank">' . $LANG['IMG'] . '</a></td>';
                  echo '</tr>';
                  }
                  ?>
               </table>
               <div style="margin-top:1rem">
                  <input type="checkbox" id="addgeounit" name="addgeounit" value="true" checked >
                  <label style="text-decoration: none" for="addgeounit"><?=$LANG['ADD_IF_GEOUNITS_MISSING']?></label>
               </div>
               <input name="gbAction" type="hidden" value="<?php echo $gbAction; ?>" />
               <span style="display:inline-flex;vertical-align:middle;margin-top:1rem">
                  <button name="submitaction" onclick="submit_loading()" type="submit" value="submitCountryForm">
                     <?= $LANG['ADD_BOUNDARIES'] ?>
                  </button>
                  <img id="submit-loading"style="border:0px;width:2rem;height:2rem;display:none" src="../images/ajax-loader.gif" />
               </span>
               <div id="submit-loading-text" style="display:none">
                  <?= $LANG['LOADING_GEO_DATA_TEXT'] ?>
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
