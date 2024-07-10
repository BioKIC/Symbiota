<?php
include_once ('../config/symbini.php');
include_once ($SERVER_ROOT . '/classes/GeographicThesaurus.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/geothesaurus/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/geothesaurus/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/geothesaurus/index.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$geoThesID = array_key_exists('geoThesID', $_REQUEST) ? filter_var($_REQUEST['geoThesID'], FILTER_SANITIZE_NUMBER_INT) : '';
$parentID = array_key_exists('parentID', $_REQUEST) ? filter_var($_REQUEST['parentID'], FILTER_SANITIZE_NUMBER_INT) : '';
$submitAction = array_key_exists('submitaction', $_POST) ? $_POST['submitaction'] : '';

$geoManager = new GeographicThesaurus();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

$statusStr = '';
if($isEditor && $submitAction) {
   if($submitAction == 'submitGeoEdits'){
      $status = $geoManager->editGeoUnit($_POST);
      if(!$status) $statusStr = $geoManager->getErrorMessage();
   }
   elseif($submitAction == 'deleteGeoUnits'){
      $status = $geoManager->deleteGeoUnit($_POST['delGeoThesID']);
      if(!$status) $statusStr = $geoManager->getErrorMessage();
   }
   elseif($submitAction == 'addGeoUnit'){
      $status = $geoManager->addGeoUnit($_POST);
      if(!$status) $statusStr = $geoManager->getErrorMessage();
   }
}

$geoArr = $geoManager->getGeograpicList($geoThesID);
$geoUnit = $geoManager->getGeograpicUnit($geoThesID);
$rankArr = $geoManager->getGeoRankArr();
$childrenTitleStr = '';
$geoSubChildren = [];

if($geoThesID && $geoUnit) {
   $childLevel = intval($geoUnit['geoLevel']) + 10;               
   if($childLevel < 90) {
      $childrenTitleStr = '<b>'. $rankArr[$childLevel] . '</b> ' . $LANG['TERMS_WITHIN'] . ' <b>' . $geoUnit['geoTerm'] . '</b>';
   }
   $geoSubChildren = array_filter($geoArr, fn($val) => 10 < (intval($val['geoLevel']) - $geoUnit['geoLevel']));
} else {
   $childrenTitleStr = '<b>' . $LANG['ROOT_TERMS'] . '</b>';
}

function listGeoUnits($arr) {
   global $LANG;
   echo '<ul>';
   foreach($arr as $geoID => $unitArr){
      $geoTerm = $unitArr['geoTerm'];

      $codeStr = '';
      if($unitArr['abbreviation']) {
         $codeStr .= ' ('.$unitArr['abbreviation'].') ';
      } else {
         if($unitArr['iso2']) $codeStr = $unitArr['iso2'].', ';
         if($unitArr['iso3']) $codeStr .= $unitArr['iso3'].', ';
         if($unitArr['numCode']) $codeStr .= $unitArr['numCode'].', ';
         if($codeStr) $codeStr = ' ('.trim($codeStr,', ').') ';
      }

      $referenceHref = '';
      $referenceText = '';

      if($unitArr['acceptedTerm']) {
         $referenceText = '<a href="index.php?geoThesID=' . htmlspecialchars($unitArr['acceptedID']) . '">' . htmlspecialchars($unitArr['acceptedTerm'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
      } elseif(isset($unitArr['childCnt'])) {
         $referenceText = '- '. htmlspecialchars($unitArr['childCnt'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . $LANG['CHILDREN'];
      }

      $geoTerm = htmlspecialchars($geoTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
      $codeStr = htmlspecialchars($codeStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

      echo <<<HTML
      <li>
         <a href='index.php?geoThesID=$geoID'>$geoTerm</a>
         $codeStr
         $referenceText
      </li>
      HTML;
   }
   echo '</ul>';
}

?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
   <head>
      <title><?= $DEFAULT_TITLE . ' - ' . $LANG['GEOTHES_TITLE'] ?></title>
      <?php
      include_once ($SERVER_ROOT.'/includes/head.php');
      include_once ($SERVER_ROOT.'/includes/leafletMap.php');
      ?>

      <style>
      fieldset{ margin: 10px; padding: 15px; width: 600px }
      legend{ font-weight: bold; }
      #innertext{ min-height: 500px; }
      label{ text-decoration: underline; }
      #edit-legend{ display: none }
      .field-div{ margin: 3px 0px }
      .editIcon{ }
      .editTerm{ }
      .editFormElem{ display: none; }
      #editButton-div{ display: none; }
      #unitDel-div{ display: none; }
      .button-div{ margin: 15px }
      #status-div{ margin:15px; padding: 15px; color: red; }
      </style>
      <script src="<?php echo $CLIENT_ROOT?>/js/autocomplete-input.js" type="module"></script>
      <script type="text/javascript">
      function toggleEditor(){
         toggle(".editTerm");
         toggle(".editFormElem");
         toggle("#editButton-div", "block");
         toggle("#edit-legend");
         toggle("#unitDel-div", "block");
		 let map = document.getElementById("map_canvas");
		 if(map) map.style.display = map.style.display === 'none'?'block': 'none';
      }

      function toggle (target, defaultDisplay = "inline"){
         const targetList = document.querySelectorAll(target);
         for (let i = 0; i < targetList.length; i++) {
            let targetDisplay = window.getComputedStyle(targetList[i]).getPropertyValue('display');
            targetList[i].style.display = (targetDisplay == 'none') ? defaultDisplay : 'none';
         }
      }

      function leafletInit() {
         const wkt_form = document.getElementById('footprintwkt');
         const map_container = document.getElementById('map_canvas');

         if(!wkt_form || !wkt_form.value || !map_container) {
            if(map_container) map_container.style.display = "none";
            return;
         }
         else { 
            map_container.style.display = "block";
         }

         let map = new LeafletMap('map_canvas', {center: [0,0], zoom: 1});

         map.enableDrawing({
            polyline: false,
            control: false,
            circlemarker: false,
            marker: false,
            drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' }
         });

         map.drawShape({type: "geoJSON", geoJSON: JSON.parse(wkt_form.value)})
      }

      function openCoordAid(id="footprintwkt") {
         mapWindow = open(
            `../collections/tools/mapcoordaid.php?mapmode=polygon&map_mode_strict=true&geoJson&wkt_input_id=${id}`,
            "polygon",
            "resizable=0,width=900,height=630,left=20,top=20",
         );
         if (mapWindow.opener == null) mapWindow.opener = self;
         mapWindow.focus();
      }

      function init() {
         try {
            leafletInit();
         } catch(e) {
            console.log("Leaflet Map failed to load")
         }
      }

      function navigateGeothesaursSearch() {
         const auto_input = document.getElementById("geothesaurus-suggest");
         if(!auto_input || (auto_input.inputEl && !auto_input.inputEl.value)) return;
         window.location.href = `index.php?geoThesID=${auto_input.value}`
      }
      </script>
   </head>
   <body onload="init()">
      <div 
         id="service-container" 
		 data-geo-unit='<?php echo htmlspecialchars(json_encode($geoUnit))?>'
		>
      </div>
      <?php
      include($SERVER_ROOT.'/includes/header.php');
      ?>
      <div class="navpath">
         <a href="../index.php">
            <?= $LANG['NAV_HOME'] ?> </a> &gt;&gt;
         <?php if($geoThesID): ?>
         <a href="index.php"><b> <?= $LANG['NAV_GEOTHES'] ?> </b></a>&gt;&gt;
         <b> <?= $geoUnit["geoTerm"]?> </b>
         <?php else: ?>
         <b> <?= $LANG['NAV_GEOTHES'] ?> </b>
         <?php endif ?>
      </div>
      <div id='innertext'>
         <h1 class="page-heading"><?= $LANG['GEOTHES_TITLE']; ?></h1>
         <fieldset>
            <legend><?=$LANG["SEARCH_GEOTHESAURUS"]?></legend>
            <autocomplete-input 
               id="geothesaurus-suggest"
               name="external-taxa-input"
               response_type="json"
               json_label="label"
               json_value="geoThesID"
               multi="false"
               completeUrl="rpc/searchGeothesaurus.php?geoterm=??">
            </autocomplete-input>
            <button type="button" style="margin-left:2rem" onclick="navigateGeothesaursSearch()"><?=$LANG["SEARCH"]?></button>
         </fieldset>
         <div>
            <a href="harvester.php"><?= $LANG['GOTO_HARVESTER']?></a>
         </div>
         <?php
         if($statusStr){
         echo '<div id="status-div">'.$statusStr.'</div>';
         }
         ?>

         <!-- Add Form  -->
         <div id="addGeoUnit-div" style="clear:both;margin-bottom:10px;display:none">
            <form name="unitAddForm" action="<?= $geoThesID? '' : 'index.php' ?>" method="post">
               <fieldset id="new-fieldset">
                  <legend> <?= $LANG['ADD_GEO_UNIT'] ?> </legend>
                  <div class="field-div">
                     <label> <?= $LANG['GEO_UNIT_NAME'] ?> </label>:
                     <span><input type="text" name="geoTerm" style="width:200px;" required /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ABBR'] ?> </label>:
                     <span><input type="text" name="abbreviation" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ISO2'] ?> </label>:
                     <span><input type="text" name="iso2" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ISO3'] ?> </label>:
                     <span><input type="text" name="iso3" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['NUM_CODE'] ?> </label>:
                     <span><input type="number" min="0" name="numCode" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['GEO_RANK'] ?> </label>:
                     <span>
                        <select required name="geoLevel">
                           <option value=""> <?= $LANG['SELECT_RANK'] ?> </option>
                           <option value="">----------------------</option>
                           <?php
                           $defaultGeoLevel = false;
                           if($geoArr) $defaultGeoLevel = $geoArr[key($geoArr)]['geoLevel'];
                           $currentGeoRank = isset($geoUnit["geoLevel"])? intval($geoUnit["geoLevel"]): 0;

                           foreach($rankArr as $rankID => $rankValue){
                           if($currentGeoRank >= intval($rankID)) continue;
                           if($geoThesID){
                           //Grabs the next highest rankid when matched
                           if($defaultGeoLevel == 'getNextRankid') $defaultGeoLevel = $rankID;
                           if($rankID == $geoUnit['geoLevel']) $defaultGeoLevel = 'getNextRankid';
                           }
                           echo '<option value="' . $rankID . '" '. ($defaultGeoLevel === $rankID?'SELECTED':'') . '>' . $rankValue . '</option>';
                           }
                           ?>
                        </select>
                     </span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['NOTES'] ?> </label>:
                     <span>
                        <textarea  type="text" maxlength="250" name="notes" style="margin-top: 0.5rem; width:98%;height:45px;"></textarea>
                     </span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['PARENT_TERM'] ?> </label>:
                     <span>
                        <select name="parentID">
                           <option value=""> <?= $LANG['SELECT_PARENT'] ?> </option>
                           <option value="">----------------------</option>
                           <option value=""> <?= $LANG['IS_ROOT_TERM'] ?> </option>
                           <?php
                           $parentList = $geoManager->getParentGeoTermArr();
                           foreach($parentList as $id => $term){
                           echo '<option value="'.$id.'" '.($parentID == $id || $geoThesID == $id?'SELECTED':'').'>'.$term.'</option>';
                           }
                           ?>
                        </select>
                     </span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ACCEPTED_TERM'] ?> </label>:
                     <span>
                        <select name="acceptedID">
                           <option value=""> <?= $LANG['SELECT_ACCEPTED'] ?> </option>
                           <option value="">----------------------</option>
                           <option value=""> <?= $LANG['IS_ACCEPTED'] ?> </option>
                           <?php
                           $acceptedList = $geoManager->getAcceptedGeoTermArr();
                           foreach($acceptedList as $id => $term){
                           echo '<option value="'.$id.'">'.$term.'</option>';
                           }
                           ?>
                        </select>
                     </span>
                  </div>
                  <div class="field-div">
                     <label><?=$LANG['POLYGON']?></label>:
                     <a onclick="openCoordAid('addfootprintwkt')">
                        <img src='../images/world.png' style='width:10px;border:0' alt='<?= $LANG['IMG_OF_GLOBE'] ?>' /> <?= $LANG['EDIT_POLYGON']?> 
                     </a>
                     <span><textarea id="addfootprintwkt" name="polygon" style="margin-top: 0.5rem; width:98%;height:90px;"></textarea></span>
                  </div>
                  <div id="addButton-div" class="button-div">
                     <button type="submit" name="submitaction" value="addGeoUnit"> <?= $LANG['ADD_UNIT'] ?> </button>
                  </div>
               </fieldset>
            </form>
         </div>

         <!-- Geo Unit Info and Edit Form -->
         <?php
         if($geoThesID && $geoUnit) {
         ?>
         <div id="updateGeoUnit-div" style="margin-bottom:10px;">
            <form name="unitEditForm" action="index.php<?= $geoThesID? '?geoThesID=' . $geoThesID: '' ?>" method="post">
               <fieldset id="edit-fieldset">
                  <legend><span id="edit-legend"><?= $LANG['EDIT'] ?></span> <?= $LANG['GEO_UNIT'] ?> </legend>
                  <div style="float:right">
                     <span class="editIcon" title="<?= $LANG['EDIT_TERM'] ?>"><a href="#" onclick="toggleEditor()"><img class="editimg" src="../images/edit.png" alt="<?= $LANG['EDIT']; ?>"></a></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['GEO_UNIT_NAME'] ?> </label>:
                     <span class="editTerm"><?= $geoUnit['geoTerm']; ?></span>
                     <span class="editFormElem" style="display: none"><input type="text" name="geoTerm" value="<?php echo $geoUnit['geoTerm'] ?>" style="width:200px;" required /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ABBR'] ?> </label>:
                     <span class="editTerm"><?= $geoUnit['abbreviation']; ?></span>
                     <span class="editFormElem"><input type="text" name="abbreviation" value="<?= $geoUnit['abbreviation'] ?>" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ISO2'] ?> </label>:
                     <span class="editTerm"><?= $geoUnit['iso2']; ?></span>
                     <span class="editFormElem"><input type="text" name="iso2" value="<?= $geoUnit['iso2'] ?>" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['ISO3'] ?> </label>:
                     <span class="editTerm"><?= $geoUnit['iso3']; ?></span>
                     <span class="editFormElem"><input type="text" name="iso3" value="<?= $geoUnit['iso3'] ?>"style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['NUM_CODE'] ?> </label>:
                     <span class="editTerm"><?= $geoUnit['numCode']; ?></span>
                     <span class="editFormElem"><input type="number" min="0" name="numCode" value="<?= $geoUnit['numCode'] ?>" style="width:50px;" /></span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['GEO_RANK'] ?> </label>:
                     <span class="editTerm"><?= ($geoUnit['geoLevel']?$rankArr[$geoUnit['geoLevel']].' ('.$geoUnit['geoLevel'].')':''); ?></span>
                     <span class="editFormElem">
                        <select required name="geoLevel">
                           <option value=""> <?= $LANG['SELECT_RANK'] ?> </option>
                           <option value="">----------------------</option>
                           <?php
                           $currentGeoRank = intval($geoUnit["geoLevel"]);
                           foreach($rankArr as $rankID => $rankValue) {
                           if($currentGeoRank > intval($rankID) && $geoUnit["parentID"] !== null) continue;
                           echo '<option value="'.$rankID.'" '.($rankID==$geoUnit['geoLevel']?'selected':'').'>'.$rankValue.'</option>';
                           }
                           ?>
                        </select>
                     </span>
                  </div>
                  <div class="field-div">
                     <label> <?= $LANG['NOTES'] ?> </label>:
                     <span class="editTerm"><?= $geoUnit['notes']; ?></span>
                     <span class="editFormElem">
                        <textarea  type="text" maxlength="250" name="notes" style="margin-top: 0.5rem; width:98%;height:45px;"><?= $geoUnit['notes']?></textarea>
                     </span>
                  </div>
                  <?php
                  if($geoUnit['geoLevel']){
                  if($parentList = $geoManager->getParentGeoTermArr($geoUnit['geoLevel'])){
                  $parentStr = '';
                  if($geoUnit['parentTerm']) $parentStr = '<a href="index.php?geoThesID=' . $geoUnit['parentID'] . '">' . $geoUnit['parentTerm'] . '</a>';
                  ?>
                  <div class="field-div">
                     <label> <?= $LANG['PARENT_TERM'] ?> </label>:
                     <span class="editTerm"><?= $parentStr; ?></span>
                     <span class="editFormElem">
                        <select name="parentID">
                           <option value=""> <?= $LANG['IS_ROOT_TERM'] ?> </option>
                           <?php
                           foreach($parentList as $id => $term){
                           echo '<option value="'.$id.'" '.($id==$geoUnit['parentID']?'selected':'').'>'.$term.'</option>';
                           }
                           ?>
                        </select>
                     </span>
                  </div>
                  <?php
                  }
                  }
                  $acceptedStr = '';
                  if($geoUnit['acceptedTerm']) {
                  $acceptedStr = '<a href="index.php?geoThesID=' . $geoUnit['acceptedID'] . '">' . htmlspecialchars($geoUnit['acceptedTerm'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
                  }
                  ?>
                  <div class="field-div">
                     <label> <?= $LANG['ACCEPTED_TERM'] ?> </label>:
                     <span class="editTerm"><?php echo $acceptedStr; ?></span>
                     <span class="editFormElem">
                        <select name="acceptedID">
                           <option value=""> <?= $LANG['IS_ACCEPTED'] ?> </option>
                           <option value="">----------------------</option>
                           <?php
                           $acceptedList = $geoManager->getAcceptedGeoTermArr($geoUnit['geoLevel'], $geoUnit['parentID']);
                           foreach($acceptedList as $id => $term){
                           echo '<option value="'.$id.'" '.($id==$geoUnit['acceptedID']?'selected':'').'>'.$term.'</option>';
                           }
                           ?>
                        </select>
                     </span>
                  </div>
                  <div class="field-div">
                     <label><?= $LANG['POLYGON']?></label>:
                     <span class="editTerm">
                        <?= $geoUnit['geoJSON'] !== null? $LANG['YES_POLYGON']: $LANG['NO_POLYGON'] ?>
                     </span>
                     <div id="map_canvas" style="margin: 1rem 0; width:100%; height:20rem"></div>
                     <a class="editFormElem" onclick="openCoordAid()">
                        <img src='../images/world.png' style='width:10px;border:0' alt='<?= $LANG['IMG_OF_GLOBE'] ?>' /> <?= $LANG['EDIT_POLYGON']?> 
                     </a>
                     <span class="editFormElem" style="margin-top: 0.5rem">
                        <textarea id="footprintwkt" name="polygon" style="margin-top: 0.5rem; width:98%;height:90px;"><?= isset($geoUnit['geoJSON'])? trim($geoUnit['geoJSON']): null ?></textarea>
                     </span>
                  </div>
                  <div id="editButton-div" class="button-div">
                     <input name="geoThesID" type="hidden" value="<?= $geoThesID; ?>" />
                     <button type="submit" name="submitaction" value="submitGeoEdits"> <?= $LANG['SAVE_EDITS'] ?> </button>
                  </div>
               </fieldset>
            </form>
         </div>
         <div id="unitDel-div">
            <form name="unitDeleteForm" action="<?= $geoUnit && $geoUnit['parentID']? 'index.php?geoThesID=' . $geoUnit['parentID']: 'index.php' ?>" method="post">
               <fieldset>
                  <legend> <?= $LANG['DEL_GEO_UNIT'] ?> </legend>
                  <div class="button-div">
                     <input name="parentID" type="hidden" value="<?= $geoUnit['parentID']; ?>" />
                     <input name="delGeoThesID" type="hidden"  value="<?= $geoThesID; ?>" />
                     <!-- We need to decide if we want to allow folks to delete a term and all their children, or only can delete if no children or synonym exists. I'm thinking the later. -->
                     <button class="button-danger" type="submit" name="submitaction" value="deleteGeoUnits" onclick="return confirm(<?= $LANG['CONFIRM_DELETE'] ?>)" <?= ($geoUnit['childCnt'] ? 'disabled' : ''); ?>> <?= $LANG['DEL_GEO_UNIT'] ?> </button>
                  </div>
                  <?php
                  if($geoUnit['childCnt']) echo '<div>* ' . $LANG['CANT_DELETE'] . '</div>';
                  ?>
               </fieldset>
            </form>
         </div>

         <?php }?>

         <?php if(!empty($childrenTitleStr)):?>
         <div style="font-size:1.3em;margin: 10px 0px">
            <?= $childrenTitleStr ?>
            <span class="editIcon" title="<?= $LANG['ADD_TERM_LIST'] ?>">
               <a href="#" onclick="toggle('#addGeoUnit-div');"><img class="editimg" src="../images/add.png" alt="<?= $LANG['EDIT'] ?>" /></a>
            </span>
         </div >
         <div style="margin: 10px">
            <?php listGeoUnits($geoUnit? array_filter($geoArr, fn($val) => 10 >= (intval($val['geoLevel']) - $geoUnit['geoLevel'])): $geoArr) ?>
         </div>
         <?php endif?>

         <?php if(!empty($geoSubChildren)): ?>
         <div style="font-size:1.3em;margin: 10px 0px">
            <?= '<b>' . $LANG['OTHER'] . '</b>' . $LANG['TERMS_WITHIN'] . ' <b>' . $geoUnit['geoTerm'] . '</b>'?>
         </div >
         <div style="margin: 10px">
            <?php listGeoUnits($geoSubChildren)?>
         </div>
         <?php endif ?>
      </div>

      <?php
      include($SERVER_ROOT.'/includes/footer.php');
      ?>
   </body>
</html>
