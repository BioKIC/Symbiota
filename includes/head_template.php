<?php
/*
** Symbiota Redesign
** The version is determined by the number of the release
** set in config/symbini.php ($CSS_VERSION_RELEASE).
** To customize the styles, add your own CSS files to the
** css folder and include them here.
*/
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
$pHandler = new ProfileManager();
$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
// $_SESSION['active_stylesheet'] = null; // use this if you want to troubleshoot the behavior of just the persisted preference
$localSession = isset($_SESSION['active_stylesheet']) ? $_SESSION['active_stylesheet'] : null;
?>
<!-- Responsive viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Symbiota styles -->
<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/reset.css" type="text/css" rel="stylesheet">
<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/normalize.slim.css" type="text/css" rel="stylesheet">
<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/main.css" type="text/css" rel="stylesheet">
<script src="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/js/symb/lang.js" type="text/javascript"></script>
<script src="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/js/symb/accessibilityUtils.js" type="text/javascript"></script>
<style>
    .accessibility-button {
        font-size: 1.1em;
    }
    .welcome-text {
        margin-bottom: 0.75rem;
    }
    .accessibility-option-button {
        width: fit-content;
        padding: 10px;
        background-color: var(--link-color);
        color: var(--body-bg-color);
    }

    .accessibility-option-button:hover {
        cursor: pointer;
        background-color: var(--medium-color);
    }
    .accessibility-dialog{
        position: fixed;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        padding: 20px;
        border-radius: 5px;
    }
    .button__item-container{
        display: flex;
        justify-content: center;
    }
    .button__item-container__item-text{
        margin-top: 0.5rem;
    }
</style>
<?php 
    if($isAccessiblePreferred){
        if(isset($localSession) && strpos($localSession, "condensed.css")){
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <?php
        }else{
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <?php
        }
    } else{
        if(isset($localSession) && strpos($localSession, "accessibility-compliant.css")){
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <?php
        } else{
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <?php
        }
    }
?>
