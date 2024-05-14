<!-- Importing google api -->
<script
   src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?= (!empty($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY != 'DEV' ? 'key=' . $GOOGLE_MAP_KEY : '') ?>">
</script>

<!-- Importing google map api js helper class and functions -->
<script
   src="<?php echo $CLIENT_ROOT; ?>/js/symb/googleMap.js"
   type="text/javascript">
</script>

<script src="<?php echo $CLIENT_ROOT; ?>/js/heatmap/heatmap.js" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT; ?>/js/heatmap/google-heatmap.js" type="text/javascript"></script>

<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/markerclusterer.js?ver=1" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/oms.min.js" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/keydragzoom.js" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/infobox.js" type="text/javascript"></script>
