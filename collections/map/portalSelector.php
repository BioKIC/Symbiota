<?php
include_once('../../config/symbini.php');

if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/portalSelector.' . $LANG_TAG . '.php')) {
   include_once($SERVER_ROOT . '/content/lang/collections/portalSelector.' . $LANG_TAG . '.php');
}

$conn = MySQLiConnectionFactory::getCon('readonly');

//Using heredoc for Highlighting. Do not use it to query construction
$portals = $conn->query(<<<sql
   SELECT * from(
   SELECT portalName, urlRoot,
      SUBSTRING_INDEX(symbiotaVersion, '.', 1) as major,
      SUBSTRING_INDEX(SUBSTRING_INDEX(symbiotaVersion, '.', 2), '.', -1) as minor,
      SUBSTRING_INDEX(SUBSTRING_INDEX(symbiotaVersion, '.', 3), '.', -1) as patch
   from portalindex p) version where major > 3 or major = 3 and minor > 1;
   sql)->fetch_all(MYSQLI_ASSOC);

//Kinda a getto way of ensuring unique id's if multiple of this file is
//included. 
$PORTAL_SELECTOR_ID = !isset($PORTAL_SELECTOR_ID) || !is_int($PORTAL_SELECTOR_ID)? 0: $PORTAL_SELECTOR_ID + 1;

?>

<div>
   <?php if(count($portals) > 0):?>
   <script src="<?php echo $CLIENT_ROOT?>/js/autocomplete-input.js" type="module"></script>
   <div>
      <input 
         onchange="onEnablePortalSelector(this.checked)"
         data_role="none" 
         type="checkbox" 
         id="cross_portal_switch_<?php echo $PORTAL_SELECTOR_ID?>"
         autocomplete='off'
         name="cross_portal_switch"
      />
      <label for="cross_portal_switch">
         <?php echo (isset($LANG['ENABLE_CROSS_PORTAL_SEARCH'])? $LANG['ENABLE_CROSS_PORTAL_SEARCH']: 'Enable Cross Portal Search')?>
      </label>
   </div>
   <div id="portal-selector-<?php echo $PORTAL_SELECTOR_ID?>" style="display:none">
      <div style="margin-top: 5px">   
         <input 
            data_role="none" 
            type="hidden" 
            name="cross_portal_label"
            id="portal-selector-name-<?php echo $PORTAL_SELECTOR_ID?>"
            value="<?= htmlspecialchars($portals[0]['portalName'])?>"
         />
         <select name="cross_portal" onchange="onPortalSelect(this)">
            <?php foreach($portals as $portal): ?>
            <option value="<?= htmlspecialchars($portal['urlRoot'])?>"><?= htmlspecialchars($portal['portalName'])?></option>
            <?php endforeach; ?>
         </select>
      </div>
      <div style="margin-top: 5px">
         <label for="portal-taxa-suggest-<?php echo $PORTAL_SELECTOR_ID?>">Taxa:</label>
         <input name="" type="hidden">
         <autocomplete-input 
            id="portal-taxa-suggest-<?php echo $PORTAL_SELECTOR_ID?>"
            name="external-taxa-input"
            response_type="json"
            json_label="value"
            json_value="id"
            completeUrl="<?= $portals[0]['urlRoot'] . '/rpc/taxasuggest.php?term=??'?>">
         </autocomplete-input>
      </div>
   </div>
   <script type="text/javascript" defer>
   function onPortalSelect(el) {
      let input = document.getElementById("portal-taxa-suggest-<?php echo $PORTAL_SELECTOR_ID?>")
      let hiddenInput = document.getElementById("portal-selector-name-<?php echo $PORTAL_SELECTOR_ID?>")
      if(hiddenInput) {
         hiddenInput.value = el.options[el.selectedIndex].innerHTML;
      }
      input.completeUrl = el.value + '/rpc/taxasuggest.php?term=??';
   }

   function onEnablePortalSelector(on) {
      let selector = document.getElementById("portal-selector-<?php echo $PORTAL_SELECTOR_ID?>")
      selector.style.display= on ?'block': 'none';
   }
   </script>
   <?php else: ?>
   <?= (isset($LANG['NO_EXTERNAL_PORTALS_SEARCH_COMPATIBLE'])? $LANG['NO_EXTERNAL_PORTALS_SEARCH_COMPATIBLE']: 'No external portals are search compatible')?>
   <?php endif; ?>
</div>
