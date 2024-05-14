<?php
/*
 * Customize styling by adding or modifying CSS file links below
 * Default styling for individual page is defined within /css/symb/
 * Individual styling can be customized by:
 *     1) Uncommenting the $CUSTOM_CSS_PATH variable below
 *     2) Copying individual CCS file to the /css/symb/custom directory
 *     3) Modifying the sytle definiation within the file
 */

//$CUSTOM_CSS_PATH = '/css/symb/custom';
?>
<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />
<link href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/css/base.css?ver=1" type="text/css" rel="stylesheet">
<link href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/css/symb/main.css?ver=1" type="text/css" rel="stylesheet">
<!-- Symbiota Tooltips -->
<link href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/css/symb/tooltips.css?ver=1" type="text/css" rel="stylesheet">
<script src="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/js/symb/symbiota.tooltips.js" defer></script>
<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", async function(){
    const relFilePath = <?php echo (json_encode($relFilePath)); ?>;
    const langTag = <?php echo (json_encode($LANG_TAG)); ?>;
    // console.log('relfilepath ' + relFilePath);
    const pageTooltipText = await getTooltip(relFilePath, langTag);
    const pageTitle = document.querySelector('#innertext h1');
    addTooltip(pageTitle.parentNode, pageTooltipText);
  })
</script>